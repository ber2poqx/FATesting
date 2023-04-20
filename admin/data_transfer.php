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
$page_security = 'SA_CDATATRANS';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/admin/db/data_transfer_db.inc");

//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root ."/js/data_transfer.js");


//----------------------------------------------: for store js :---------------------------------------

if(isset($_GET['get_branch'])){
    global $db_connections;
    $conn = $db_connections;
    $total = count($conn);

    $status_array[] = array('branch_no'=>"all",
        'branch_name'=>"All"
    );
	for ($i = 0; $i < $total; $i++)
	{
        $status_array[] = array('branch_no'=>Get_db_coy($conn[$i]['branch_code']),
                                'branch_code'=>$conn[$i]['branch_code'],
                                'branch_name'=>$conn[$i]['branch_code']. '-'.$conn[$i]['name']);
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}

if(isset($_GET['get_datadesc'])){

    $status_array[] = array('id'=>"item",
        'description'=>"Products"
    );
    $status_array[] = array('id'=>"color",
        'description'=>"Product Codes"
    );
    $status_array[] = array('id'=>"suplr",
        'description'=>"Suppliers"
    );
    $status_array[] = array('id'=>"Subcatgry",
        'description'=>"Sub Category"
    );
    $status_array[] = array('id'=>"clasftn",
        'description'=>"Brand"
    );
    $status_array[] = array('id'=>"brand",
        'description'=>"Classification"
    );
    $status_array[] = array('id'=>"plcy",
        'description'=>"Branch Policy"
    );

    $total = count($status_array);
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}

if(isset($_GET['get_datalogs']))
{
    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
    
    $result = get_data_logs($start, $limit, false);
     //for pagination
    $total_result = get_data_logs($start, $limit, true);

    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow["id"],
                                'date_upload'=>date('m/d/Y', strtotime($myrow["date_upload"])),
                                'remarks'=>$myrow["remarks"],
                                'prepared'=>$myrow["real_name"]
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.DB_num_rows($total_result).'","result":'.$jsonresult.'})';
    return;
}
//----------------------------------------------------: insert, update, delete :-------------------------------------------
if(isset($_GET['submit'])){
    //0 is by default no errors
    $InputError = 0;

    if (empty($_POST['datadesc'])) {
        $InputError = 1;
        $dsplymsg = _('Description must not be empty');
    }
    if (empty($_POST['branch'])){
        $InputError = 1;
        $dsplymsg = _('Branch must not be empty');
    }
    if (empty($_POST['date_from'])){
        $InputError = 1;
        $dsplymsg = _('Date from must not be empty');
    }
    if (empty($_POST['date_to'])){
        $InputError = 1;
        $dsplymsg = _('Date to must not be empty');
    }

    if($InputError !=1) {
        if($_POST['datadesc'] == "item"){
            $user_name = $_SESSION["wa_current_user"]->username;
            $currentdate = date('Y-m-d');
            $remarks = 'Product' . '  -  ' . 'Filter Date: ' . date('Y-m-d',strtotime($_POST['date_from'])) . ' - ' . date('Y-m-d',strtotime($_POST['date_to']));
            //foreach($_POST['branch'] as $value)
            //{
                $result = get_items_to_transfer(date('Y-m-d',strtotime($_POST['date_from'])), date('Y-m-d',strtotime($_POST['date_to'])));
                while ($myrow = db_fetch($result)) 
                {
                    $counter = 0;
                    
                    $checkres = check_item($myrow["stock_id"], $_POST['branch']);
                    while ($checkrow = db_fetch($checkres))
                    {
                        $counter++;
                    }

                    if ($counter > 0){
                        update_to_stockmaster($myrow["stock_id"], $myrow["category_id"], $myrow["tax_type_id"], $myrow["description"], $myrow["long_description"], 
                        $myrow["units"], $myrow["mb_flag"], $myrow["sales_account"], $myrow["installment_sales_account"], $myrow["regular_sales_account"], 
                        $myrow["cogs_account"], $myrow["inventory_account"], $myrow["adjustment_account"], $myrow["wip_account"], $myrow["dimension_id"], $myrow["dimension2_id"],
                        $myrow["brand"], $myrow["manufacturer"], $myrow["distributor"], $myrow["importer"], $myrow["old_code"], $myrow["sap_code"], $myrow["serialised"], 
                        $myrow["date_modified"], $_POST['branch']);
                    }else{                                
                        add_to_stockmaster($myrow["stock_id"], $myrow["category_id"], $myrow["tax_type_id"], $myrow["description"], $myrow["long_description"], 
                        $myrow["units"], $myrow["mb_flag"], $myrow["sales_account"], $myrow["installment_sales_account"], $myrow["regular_sales_account"], 
                        $myrow["cogs_account"], $myrow["inventory_account"], $myrow["adjustment_account"], $myrow["wip_account"], $myrow["dimension_id"], $myrow["dimension2_id"],
                        $myrow["brand"], $myrow["manufacturer"], $myrow["distributor"], $myrow["importer"], $myrow["old_code"], $myrow["sap_code"], $myrow["serialised"], 
                        $myrow["date_modified"], $_POST['branch']);
                    }                                                                
                }
            //}
            add_to_datalogs($currentdate, $remarks, $user_name);
            $dsplymsg = _("The Product has been successfully entered...");
        }elseif($_POST['datadesc'] == "color") {
            $user_name = $_SESSION["wa_current_user"]->username;
            $currentdate = date('Y-m-d');
            $remarks = 'Product Codes' . '  -  ' . 'Filter Date: ' . date('Y-m-d',strtotime($_POST['date_from'])) . ' - ' . date('Y-m-d',strtotime($_POST['date_to']));
            //foreach($_POST['branch'] as $value)
            //{
                $result = get_itemcodes_to_transfer(date('Y-m-d',strtotime($_POST['date_from'])), date('Y-m-d',strtotime($_POST['date_to'])));
                while ($myrow = db_fetch($result)) {
                    $counter = 0;

                    $checkrescode = check_item_codes($myrow["item_code"], $_POST['branch']);
                    while ($checkrow = db_fetch($checkrescode))
                    {
                        $counter++;
                    }

                    if ($counter > 0) {
                        update_to_itemcodes($myrow["item_code"], $myrow["stock_id"], $myrow["description"], $myrow["category_id"], $myrow["quantity"], $myrow["is_foreign"],
                        $myrow["inactive"], $myrow["brand"], $myrow["manufacturer"], $myrow["distributor"], $myrow["importer"], $myrow["product_status"], $myrow["pnp_color"],
                        $myrow["color"], $myrow["old_code"], $myrow["date_modified"], $_POST['branch']);
                    }else{
                        add_to_itemcodes($myrow["item_code"], $myrow["stock_id"], $myrow["description"], $myrow["category_id"], $myrow["quantity"], $myrow["is_foreign"],
                        $myrow["inactive"], $myrow["brand"], $myrow["manufacturer"], $myrow["distributor"], $myrow["importer"], $myrow["product_status"], $myrow["pnp_color"],
                        $myrow["color"], $myrow["old_code"], $myrow["date_modified"], $_POST['branch']);
                    }
                }
            //}
            add_to_datalogs($currentdate, $remarks, $user_name);
            $dsplymsg = _("The Product codes has been successfully entered...");
        }elseif ($_POST['datadesc'] == "plcy") {
            $user_name = $_SESSION["wa_current_user"]->username;
            $currentdate = date('Y-m-d');
            $remarks = 'Branch Policy' . '  -  ' . 'Filter Date: ' . date('Y-m-d',strtotime($_POST['date_from'])) . ' - ' . date('Y-m-d',strtotime($_POST['date_to']));
           //foreach($_POST['branch'] as $value)
           //{
                $result = get_policy_to_transfer(date('Y-m-d',strtotime($_POST['date_from'])), date('Y-m-d',strtotime($_POST['date_to'])));
                while ($myrow = db_fetch($result)) {
                    $counter = 0;

                    $checkplcy = check_plcy_code($myrow["plcy_code"], $_POST['branch']);
                    while ($checkrow = db_fetch($checkplcy)) {
                        $counter++;
                    }

                    if ($counter == 0) {
                        add_to_policy($myrow["plcy_code"], $myrow["name"], $myrow["tax_included"], $myrow["factor"], $myrow["date_defined"], $myrow["remarks"],
                        $myrow["module_type"], $myrow["inactive"], $myrow["category_id"], $myrow["branch_area_id"],  $_POST['branch']);
                    }
                }

                $result1 = get_policy_details_to_transfer(date('Y-m-d',strtotime($_POST['date_from'])), date('Y-m-d',strtotime($_POST['date_to'])));
                while ($myrow1 = db_fetch($result1)) {
                    $counter1 = 0;

                    $checkplcydtls = check_plcydetails_code($myrow1["plcydtl_code"], $_POST['branch']);
                    while ($checkrow1 = db_fetch($checkplcydtls)) {
                        $counter1++;
                    }

                    if ($counter1 == 0) {
                        add_to_policy_details($myrow1["plcydtl_code"], $myrow1["description"], $myrow1["tax_included"], $myrow1["factor"], $myrow1["date_defined"], $myrow1["remarks"],
                        $myrow1["module_type"], $myrow1["inactive"], $myrow1["term"], $myrow1["financing_rate"], $myrow1["rebate"], $myrow1["penalty"], $myrow1["category_id"],
                        $myrow1["policyhdr_id"], $_POST['branch']);
                    }
                }
           //}
           add_to_datalogs($currentdate, $remarks, $user_name);
           $dsplymsg = _("The Policy has been successfully entered...");
        }elseif ($_POST['datadesc'] == "suplr") {
            $user_name = $_SESSION["wa_current_user"]->username;
            $currentdate = date('Y-m-d');
            $remarks = 'Supplier' . '  -  ' . 'Filter Date: ' . date('Y-m-d',strtotime($_POST['date_from'])) . ' - ' . date('Y-m-d',strtotime($_POST['date_to']));
            //foreach($_POST['branch'] as $value)
           //{
                $result = get_supplier_to_transfer();
                while ($myrow = db_fetch($result)) {
                    $counter = 0;

                    $checksuppl = check_supplier($myrow["supp_name"], $_POST['branch']);
                    while ($checkrow = db_fetch($checksuppl)) {
                        $counter++;
                    }

                    if ($counter > 0) {
                        update_to_supplier($myrow["supp_name"], $myrow["supp_ref"], $myrow["address"], $myrow["supp_address"], $myrow["gst_no"], $myrow["contact"],
                        $myrow["supp_account_no"], $myrow["website"], $myrow["bank_account"], $myrow["curr_code"], $myrow["payment_terms"], $myrow["tax_included"], 
                        $myrow["dimension_id"], $myrow["dimension2_id"], $myrow["tax_group_id"], $myrow["credit_limit"], $myrow["purchase_account"], 
                        $myrow["payable_account"], $myrow["payment_discount_account"], $myrow["notes"], $myrow["inactive"], $myrow["supplier_group"], 
                        $myrow["SAPcode"], $myrow["supplier_type"], $_POST['branch']);
                    }else{
                        add_to_supplier($myrow["supp_name"], $myrow["supp_ref"], $myrow["address"], $myrow["supp_address"], $myrow["gst_no"], $myrow["contact"],
                        $myrow["supp_account_no"], $myrow["website"], $myrow["bank_account"], $myrow["curr_code"], $myrow["payment_terms"], $myrow["tax_included"], 
                        $myrow["dimension_id"], $myrow["dimension2_id"], $myrow["tax_group_id"], $myrow["credit_limit"], $myrow["purchase_account"], 
                        $myrow["payable_account"], $myrow["payment_discount_account"], $myrow["notes"], $myrow["inactive"], $myrow["supplier_group"], 
                        $myrow["SAPcode"], $myrow["supplier_type"], $_POST['branch']);
                    }    
                }
            
            //}
            add_to_datalogs($currentdate, $remarks, $user_name);
           $dsplymsg = _("The Supplier has been successfully entered...");
        }elseif ($_POST['datadesc'] == "clasftn") {
            $user_name = $_SESSION["wa_current_user"]->username;
            $currentdate = date('Y-m-d');
            $remarks = 'Brand' . '  -  ' . 'Filter Date: ' . date('Y-m-d',strtotime($_POST['date_from'])) . ' - ' . date('Y-m-d',strtotime($_POST['date_to']));
            //foreach($_POST['branch'] as $value)
           //{
                $result = get_brand_to_transfer();
                while ($myrow = db_fetch($result)) {
                    $counter = 0;

                    $checkbrand = check_brand($myrow["name"], $_POST['branch']);
                    while ($checkrow = db_fetch($checkbrand)) {
                        $counter++;
                    }

                    if ($counter > 0) {
                        update_to_brand($myrow["code"], $myrow["name"], $myrow["cat_id"], $myrow["inactive"], $_POST['branch']);
                    }else {
                        add_to_brand($myrow["code"], $myrow["name"], $myrow["cat_id"], $myrow["inactive"], $_POST['branch']);
                    }
                }
           //}
           add_to_datalogs($currentdate, $remarks, $user_name);
           $dsplymsg = _("The Brand has been successfully entered...");
        }elseif ($_POST['datadesc'] == "brand") {
            $user_name = $_SESSION["wa_current_user"]->username;
            $currentdate = date('Y-m-d');
            $remarks = 'Classification' . '  -  ' . 'Filter Date: ' . date('Y-m-d',strtotime($_POST['date_from'])) . ' - ' . date('Y-m-d',strtotime($_POST['date_to']));
            //foreach($_POST['branch'] as $value)
           //{
                $result = get_classification_to_transfer();
                while ($myrow = db_fetch($result)) {
                    $counter = 0;

                    $checkclass = check_class($myrow["name"], $_POST['branch']);
                    while ($checkrow = db_fetch($checkclass)) {
                        $counter++;
                    }

                    if ($counter > 0) {
                        update_to_classification($myrow["code"], $myrow["name"], $myrow["inactive"], $_POST['branch']);
                    }else{
                        add_to_classification($myrow["code"], $myrow["name"], $myrow["inactive"], $_POST['branch']);
                    }
                }
           //}
           add_to_datalogs($currentdate, $remarks, $user_name);
           $dsplymsg = _("The Classification has been successfully entered...");
        }elseif ($_POST['datadesc'] == "Subcatgry") {
            $user_name = $_SESSION["wa_current_user"]->username;
            $currentdate = date('Y-m-d');
            $remarks = 'Sub Categoty' . '  -  ' . 'Filter Date: ' . date('Y-m-d',strtotime($_POST['date_from'])) . ' - ' . date('Y-m-d',strtotime($_POST['date_to']));
            //foreach($_POST['branch'] as $value)
           //{
                $result = get_subcategory_to_transfer();
                while ($myrow = db_fetch($result)) {
                    $counter = 0;

                    $checksubcateg = check_sub_categ($myrow["name"], $_POST['branch']);
                    while ($checkrow = db_fetch($checksubcateg)) {
                        $counter++;
                    }

                    if ($counter > 0) {
                        update_to_subcategory($myrow["code"], $myrow["name"], $myrow["inactive"], $_POST['branch']);
                    }else{
                        add_to_subcategory($myrow["code"], $myrow["name"], $myrow["inactive"], $_POST['branch']);
                    }
                }
           //}
           add_to_datalogs($currentdate, $remarks, $user_name);
           $dsplymsg = _("The Sub-category has been successfully entered...");
        }
        echo '({"success":"true","message":"'.$dsplymsg.'"})';
    }else{
        echo '({"failure":"true","message":"'.$dsplymsg.'"})';
    }
    return;

    $Mode = 'RESET';
}

//------------------------------------------------------------------------------------------------------
//simple_page_mode(true);
page(_($help_context = "Data Synchronization - Data Transfer From HO to Branch"), false, false, "", null);

start_table(TABLESTYLE, "width='80%'");
   echo "<div id='ext-form'></div>";
end_table();
end_form();
end_page();

/*if (DB_num_rows($result)==1){
                        if (isset($syspk)){
                            $dsplymsg = _('No data changed.');
                        }else{
                            $InputError = 1;
                            $dsplymsg = _('The entered information is a duplicate.').'. '. '<br />'. _('Please enter different values.');
                        }
                    }*/