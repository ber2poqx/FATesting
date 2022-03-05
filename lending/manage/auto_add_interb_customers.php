<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
    Released under the terms of the GNU General Public License, GPL, 
    as published by the Free Software Foundation, either version 3 
    of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
$page_security = 'SA_ADDCUSTINTERB';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/lending/includes/lending_cfunction.inc");

simple_page_mode(true);
//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root ."/js/auto_add_interb_customers.js");

//----------------------------------------------: for store js :---------------------------------------
if(isset($_GET['getbranch'])){
    global $db_connections;
    $conn = $db_connections;
    $total = count($conn);

	for ($i = 0; $i < $total; $i++)
	{
        $status_array[] = array('id'=>$conn[$i]['branch_code'],
                                'name'=>$conn[$i]['name'],
                                'area'=>$conn[$i]['branch_area']);
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}

if(isset($_GET['get_area'])){
    $result = get_areas();
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow['area_code'],
                                'name'=>$myrow['description'],
                                'value'=>$myrow['collectors_id']);
    }

    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_collector'])){
    $result = get_Collector_by_area($_GET['user_id']);
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow['user_id'],
                                'name'=>$myrow['real_name'],
                                'value'=>0);
    }

    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}

if(isset($_GET['get_InterB_Customers']) AND $_GET['branch'] != ""){

    $result = search_CustomerFromOtherDB($_GET['query'], $_GET['branch']);
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        //for installment
        $loan_raw = get_debtor_per_customer($myrow['debtor_ref'], $_GET['branch']);
        
        $status_array[] = array('customer_code'=>$myrow['debtor_ref'],
                                'customer_name'=>htmlentities($myrow['name']),
                                'address'=>$myrow['address'],
                                'tel_no'=>$myrow['phone'],
                                'barangay'=>$myrow['barangay'],
                                'Province'=>$myrow['province'],
                                'Municipality'=>$myrow['munic'],
                                'Zip_Code'=>$myrow['zipcode'],
                                'TINNo'=>$myrow['tax_id'],
                                'Age'=>$myrow['age'],
                                'Gender'=>$myrow['gender'],
                                'Status'=>$myrow['status'],
                                'father_name'=>$myrow['name_father'],
                                'mother_name'=>$myrow['name_mother'],
                                'branch_code'=>$_GET['branch'],
                                'trans_no'=>$loan_raw['trans_no'],
                                'type'=>$loan_raw['type']
                            );
     }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';

    set_global_connection();
    return;
}

if(isset($_GET['submit'])){
    //0 is by default no errors
    $InputError = 0;

    if (empty($_POST['cust_code']) || empty($_POST['branch_code'])) {
        $InputError = 1;
        $dsplymsg = _('Some fields are empty. Please reload the page and try again. Thank you...');
    }
    if (empty($_POST['customer_name'])) {
        $InputError = 1;
        $dsplymsg = _('Customer name must not be empty');
    }
    if (empty($_POST['address'])){
        $InputError = 1;
        $dsplymsg = _('Address must not be empty');
    }
    if (empty($_POST['maritalstatus'])){
        $InputError = 1;
        $dsplymsg = _('Marital status must not be empty');
    }
    if (empty($_POST['gender'])){
        $InputError = 1;
        $dsplymsg = _('Gender must not be empty');
    }
    if (empty($_POST['age'])){
        $InputError = 1;
        $dsplymsg = _('Birth date must not be empty');
    }
    if (empty($_POST['Municipality'])){
        $InputError = 1;
        $dsplymsg = _('Municipality must not be empty');
    }
    if (empty($_POST['province'])){
        $InputError = 1;
        $dsplymsg = _('Province must not be empty');
    }
    if (empty($_POST['barangay'])){
        $InputError = 1;
        $dsplymsg = _('Barangay must not be empty');
    }
    if (empty($_POST['Zip_Code'])){
        $InputError = 1;
        $dsplymsg = _('Zip Code must not be empty');
    }
    if (empty($_POST['phone'])){
        $InputError = 1;
        $dsplymsg = _('Phone no. must not be empty');
    }
    if (empty($_POST['father'])){
        $InputError = 1;
        $dsplymsg = _('Father name must not be empty');
    }
    if (empty($_POST['mother'])){
        $InputError = 1;
        $dsplymsg = _('Mother name must not be empty');
    }
    if (empty($_POST['area'])){
        $InputError = 1;
        $dsplymsg = _('Area must not be empty');
    }
    if (empty($_POST['collector'])){
        $InputError = 1;
        $dsplymsg = _('Collector must not be empty');
    }
    //check if already exist in db
    if(check_customer_exist($_POST['customer_name'])){
        $InputError = 1;
        $dsplymsg = _('Customer already exists.');
    }
    
    if ($InputError !=1) {
        
        begin_transaction();
        $result = Get_CustomerFromOtherDB($_POST['cust_code'], $_POST['branch_code']);
        $customerrow = db_fetch($result);

        $birth_date = date('Y-m-d',strtotime($_POST['age']));
        $cust_ref = get_Customer_AutoGenerated_Code();

        add_customer($_POST['customer_name'], $cust_ref, $_POST['address'], $_POST['barangay'], $_POST['Municipality'], $_POST['province'],
                    $_POST['Zip_Code'], $customerrow['tax_id'], $birth_date, $_POST['gender'], $_POST['maritalstatus'], $customerrow['spouse'], $_POST['father'],
                    $_POST['mother'], $_POST['collector'], $customerrow['curr_code'], $_POST['area'], 0, 0, 0, $customerrow['payment_terms'], 0, 0, $customerrow['credit_limit'],
                    $customerrow['sales_type'], 'AutoCreatedCustomer-InterBranch-'.$customerrow['notes'], $customerrow['debtor_ref']);

        $selected_id = db_insert_id();

        if (isset($SysPrefs->auto_create_branch) && $SysPrefs->auto_create_branch == 1)
        {
            add_branch($selected_id, $_POST['customer_name'], $cust_ref, $_POST['address'], 0, 0, $customerrow['tax_group_id'], '', get_company_pref('default_sales_discount_act'), 
                        get_company_pref('debtors_act'), get_company_pref('default_prompt_payment_act'), get_company_pref("branch_code"), $_POST['address'], 0, 0, 
                        'AutoCreatedCustomer-InterBranch'.$customerrow['notes'], $customerrow['bank_account']);

            $selected_branch = db_insert_id();

            add_crm_person($cust_ref, $_POST['customer_name'], '', $_POST['address'], $_POST['phone'], $customerrow['phone2'], $customerrow['fax'],
                        $customerrow['email'], '', '', 'AutoCreatedCustomer-InterBranch');

            $pers_id = db_insert_id();

            add_crm_contact('cust_branch', 'general', $selected_branch, $pers_id);

            add_crm_contact('customer', 'general', $selected_id, $pers_id);
        }

        commit_transaction();
        
        $dsplymsg = _("New customer has been added.");
        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;

    }else{
        //$dsplymsg = ('Could not insert the new record. Please check the data and try again...');
        //$dsplymsg = ('The entered information is a duplicate. Please go back and enter different values.');

        echo '({"failure":"true","message":"'.$dsplymsg.'"})';
        return;
    }

    unset($_POST['cashier']);
    unset($_POST['tabang_user']);
    unset($_POST['inactive']);
    $Mode = 'RESET';

}

page(_($help_context = "Add Inter-Branch Customers"));

start_table(TABLESTYLE, "width='100%'");
   echo "<div id='ext-form'></div>";
end_table();

end_form();
end_page();

