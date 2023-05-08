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
    $coy = user_company();

    $status_array[] = array('branch_no'=>"all",
        'branch_code'=>"All"
    );
	for ($i = 0; $i < $total; $i++)
	{
        if($i!=$coy){
            $status_array[] = array('branch_no'=>Get_db_coy($conn[$i]['branch_code']),
                'branch_code'=>$conn[$i]['branch_code'],
                'branch_name'=>$conn[$i]['branch_code']. '-'.$conn[$i]['name']);
        }
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}

if(isset($_GET['get_datadesc'])){

    $status_array[] = array('id'=>"clasftn",
    'description'=>"Brand"
    );
    $status_array[] = array('id'=>"plcy",
        'description'=>"Branch Policy"
    );
    $status_array[] = array('id'=>"bank",
        'description'=>"Bank Accounts"
    );
    $status_array[] = array('id'=>"brand",
        'description'=>"Classification"
    );
    $status_array[] = array('id'=>"creditsis",
        'description'=>"Credit/Sales Incentive Setup"
    );
    $status_array[] = array('id'=>"gl",
        'description'=>"GL Account"
    );
    $status_array[] = array('id'=>"gl_grp_cls",
        'description'=>"GL Account Group / GL Account Classes"
    );
    $status_array[] = array('id'=>"item",
        'description'=>"Products"
    );
    $status_array[] = array('id'=>"color",
        'description'=>"Product Codes"
    );
    $status_array[] = array('id'=>"salcash",
        'description'=>"Sales Price / Cash Price"
    );
    $status_array[] = array('id'=>"salestrct",
        'description'=>"Sales Type/Reason & Collection Type"
    );
    $status_array[] = array('id'=>"suplr",
        'description'=>"Suppliers"
    );
    $status_array[] = array('id'=>"Subcatgry",
        'description'=>"Sub Category"
    );
    $status_array[] = array('id'=>"syst_cst",
        'description'=>"Sytem Cost Type"
    );
    $status_array[] = array('id'=>"user",
        'description'=>"Users"
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
            //$branchcode = is_array($_POST['branch']) or is_object($_POST['branch']) ? $_POST['branch'] : [] ;
            $branchcode = (isset($_POST['branch']) ? $_POST['branch'] : $_GET['branch']);
            $user_name = $_SESSION["wa_current_user"]->username;
            $currentdate = date('Y-m-d');
            $remarks = 'Product' . '  -  ' . 'Filter Date: ' . date('Y-m-d',strtotime($_POST['date_from'])) . ' - ' . date('Y-m-d',strtotime($_POST['date_to']));
            
            if (is_array($branchcode) || is_object($branchcode))
            {
                foreach($branchcode as $value=>$data)
                {
                    $result = get_items_to_transfer(date('Y-m-d',strtotime($_POST['date_from'])), date('Y-m-d',strtotime($_POST['date_to'])));
                    while ($myrow = db_fetch($result)) 
                    {
                        $counter = 0;
                        
                        $checkres = check_item($myrow["stock_id"], $data);
                        while ($checkrow = db_fetch($checkres))
                        {
                            $counter++;
                        }

                        if ($counter > 0){
                            update_to_stockmaster($myrow["stock_id"], $myrow["category_id"], $myrow["tax_type_id"], $myrow["description"], $myrow["long_description"], 
                            $myrow["units"], $myrow["mb_flag"], $myrow["sales_account"], $myrow["installment_sales_account"], $myrow["regular_sales_account"], 
                            $myrow["cogs_account"], $myrow["inventory_account"], $myrow["adjustment_account"], $myrow["wip_account"], $myrow["dimension_id"], $myrow["dimension2_id"],
                            $myrow["brand"], $myrow["manufacturer"], $myrow["distributor"], $myrow["importer"], $myrow["old_code"], $myrow["sap_code"], $myrow["serialised"], 
                            $myrow["date_modified"], $data);
                        }else{                                
                            add_to_stockmaster($myrow["stock_id"], $myrow["category_id"], $myrow["tax_type_id"], $myrow["description"], $myrow["long_description"], 
                            $myrow["units"], $myrow["mb_flag"], $myrow["sales_account"], $myrow["installment_sales_account"], $myrow["regular_sales_account"], 
                            $myrow["cogs_account"], $myrow["inventory_account"], $myrow["adjustment_account"], $myrow["wip_account"], $myrow["dimension_id"], $myrow["dimension2_id"],
                            $myrow["brand"], $myrow["manufacturer"], $myrow["distributor"], $myrow["importer"], $myrow["old_code"], $myrow["sap_code"], $myrow["serialised"], 
                            $myrow["date_modified"], $data);
                        }                                                                
                    }
                }
            }
            add_to_datalogs($currentdate, $remarks, $user_name);
            $dsplymsg = _("The Product has been successfully entered...");
        }elseif($_POST['datadesc'] == "color") {
            $branchcode = (isset($_POST['branch']) ? $_POST['branch'] : $_GET['branch']);
            $user_name = $_SESSION["wa_current_user"]->username;
            $currentdate = date('Y-m-d');
            $remarks = 'Product Codes' . '  -  ' . 'Filter Date: ' . date('Y-m-d',strtotime($_POST['date_from'])) . ' - ' . date('Y-m-d',strtotime($_POST['date_to']));
            
            if (is_array($branchcode) || is_object($branchcode))
            {
                foreach($branchcode as $value=>$data)
                {
                    $result = get_itemcodes_to_transfer(date('Y-m-d',strtotime($_POST['date_from'])), date('Y-m-d',strtotime($_POST['date_to'])));
                    while ($myrow = db_fetch($result)) {
                        $counter = 0;

                        $checkrescode = check_item_codes($myrow["item_code"], $data);
                        while ($checkrow = db_fetch($checkrescode))
                        {
                            $counter++;
                        }

                        if ($counter > 0) {
                            update_to_itemcodes($myrow["item_code"], $myrow["stock_id"], $myrow["description"], $myrow["category_id"], $myrow["quantity"], $myrow["is_foreign"],
                            $myrow["inactive"], $myrow["brand"], $myrow["manufacturer"], $myrow["distributor"], $myrow["importer"], $myrow["product_status"], $myrow["pnp_color"],
                            $myrow["color"], $myrow["old_code"], $myrow["date_modified"], $data);
                        }else{
                            add_to_itemcodes($myrow["item_code"], $myrow["stock_id"], $myrow["description"], $myrow["category_id"], $myrow["quantity"], $myrow["is_foreign"],
                            $myrow["inactive"], $myrow["brand"], $myrow["manufacturer"], $myrow["distributor"], $myrow["importer"], $myrow["product_status"], $myrow["pnp_color"],
                            $myrow["color"], $myrow["old_code"], $myrow["date_modified"], $data);
                        }
                    }
                }
            }           
            add_to_datalogs($currentdate, $remarks, $user_name);
            $dsplymsg = _("The Product codes has been successfully entered...");
        }elseif ($_POST['datadesc'] == "plcy") {
            $branchcode = (isset($_POST['branch']) ? $_POST['branch'] : $_GET['branch']);
            $user_name = $_SESSION["wa_current_user"]->username;
            $currentdate = date('Y-m-d');
            $remarks = 'Branch Policy' . '  -  ' . 'Filter Date: ' . date('Y-m-d',strtotime($_POST['date_from'])) . ' - ' . date('Y-m-d',strtotime($_POST['date_to']));
            
            if (is_array($branchcode) || is_object($branchcode))
            {
                foreach($branchcode as $value=>$data)
                {
                    $result = get_policy_to_transfer(date('Y-m-d',strtotime($_POST['date_from'])), date('Y-m-d',strtotime($_POST['date_to'])));
                    while ($myrow = db_fetch($result)) {
                        $counter = 0;

                        $checkplcy = check_plcy_code($myrow["plcy_code"], $data);
                        while ($checkrow = db_fetch($checkplcy)) {
                            $counter++;
                        }

                        if ($counter == 0) {
                            add_to_policy($myrow["plcy_code"], $myrow["name"], $myrow["tax_included"], $myrow["factor"], $myrow["date_defined"], $myrow["remarks"],
                            $myrow["module_type"], $myrow["inactive"], $myrow["category_id"], $myrow["branch_area_id"],  $data);
                        }
                    }

                    $result1 = get_policy_details_to_transfer(date('Y-m-d',strtotime($_POST['date_from'])), date('Y-m-d',strtotime($_POST['date_to'])));
                    while ($myrow1 = db_fetch($result1)) {
                        $counter1 = 0;

                        $checkplcydtls = check_plcydetails_code($myrow1["plcydtl_code"], $data);
                        while ($checkrow1 = db_fetch($checkplcydtls)) {
                            $counter1++;
                        }

                        if ($counter1 == 0) {
                            add_to_policy_details($myrow1["plcydtl_code"], $myrow1["description"], $myrow1["tax_included"], $myrow1["factor"], $myrow1["date_defined"], $myrow1["remarks"],
                            $myrow1["module_type"], $myrow1["inactive"], $myrow1["term"], $myrow1["financing_rate"], $myrow1["rebate"], $myrow1["penalty"], $myrow1["category_id"],
                            $myrow1["policyhdr_id"], $data);
                        }
                    }
                }
            }          
           add_to_datalogs($currentdate, $remarks, $user_name);
           $dsplymsg = _("The Policy has been successfully entered...");
        }elseif ($_POST['datadesc'] == "suplr") {
            $branchcode = (isset($_POST['branch']) ? $_POST['branch'] : $_GET['branch']);
            $user_name = $_SESSION["wa_current_user"]->username;
            $currentdate = date('Y-m-d');
            $remarks = 'Supplier' . '  -  ' . 'Filter Date: ' . date('Y-m-d',strtotime($_POST['date_from'])) . ' - ' . date('Y-m-d',strtotime($_POST['date_to']));
            
            if (is_array($branchcode) || is_object($branchcode))
            {
                foreach($branchcode as $value=>$data)
                {
                    $result = get_supplier_to_transfer();
                    while ($myrow = db_fetch($result)) {
                        $counter = 0;

                        $checksuppl = check_supplier($myrow["supplier_id"], $data);
                        while ($checkrow = db_fetch($checksuppl)) {
                            $counter++;
                        }

                        if ($counter > 0) {
                            update_to_supplier($myrow["supplier_id"], $myrow["supp_name"], $myrow["supp_ref"], $myrow["address"], $myrow["supp_address"], $myrow["gst_no"], $myrow["contact"],
                            $myrow["supp_account_no"], $myrow["website"], $myrow["bank_account"], $myrow["curr_code"], $myrow["payment_terms"], $myrow["tax_included"], 
                            $myrow["dimension_id"], $myrow["dimension2_id"], $myrow["tax_group_id"], $myrow["credit_limit"], $myrow["purchase_account"], 
                            $myrow["payable_account"], $myrow["payment_discount_account"], $myrow["notes"], $myrow["inactive"], $myrow["supplier_group"], 
                            $myrow["SAPcode"], $myrow["supplier_type"], $data);
                        }else{
                            add_to_supplier($myrow["supp_name"], $myrow["supp_ref"], $myrow["address"], $myrow["supp_address"], $myrow["gst_no"], $myrow["contact"],
                            $myrow["supp_account_no"], $myrow["website"], $myrow["bank_account"], $myrow["curr_code"], $myrow["payment_terms"], $myrow["tax_included"], 
                            $myrow["dimension_id"], $myrow["dimension2_id"], $myrow["tax_group_id"], $myrow["credit_limit"], $myrow["purchase_account"], 
                            $myrow["payable_account"], $myrow["payment_discount_account"], $myrow["notes"], $myrow["inactive"], $myrow["supplier_group"], 
                            $myrow["SAPcode"], $myrow["supplier_type"], $data);
                        }    
                    }
                }
            }
            add_to_datalogs($currentdate, $remarks, $user_name);
           $dsplymsg = _("The Supplier has been successfully entered...");
        }elseif ($_POST['datadesc'] == "clasftn") {
            $branchcode = (isset($_POST['branch']) ? $_POST['branch'] : $_GET['branch']);
            $user_name = $_SESSION["wa_current_user"]->username;
            $currentdate = date('Y-m-d');
            $remarks = 'Brand' . '  -  ' . 'Filter Date: ' . date('Y-m-d',strtotime($_POST['date_from'])) . ' - ' . date('Y-m-d',strtotime($_POST['date_to']));
           
            if (is_array($branchcode) || is_object($branchcode))
            {
                foreach($branchcode as $value=>$data)
                {
                    $result = get_brand_to_transfer();
                    while ($myrow = db_fetch($result)) {
                        $counter = 0;

                        $checkbrand = check_brand($myrow["id"], $data);
                        while ($checkrow = db_fetch($checkbrand)) {
                            $counter++;
                        }

                        if ($counter > 0) {
                            update_to_brand($myrow["id"], $myrow["code"], $myrow["name"], $myrow["cat_id"], $myrow["inactive"], $data);
                        }else {
                            add_to_brand($myrow["code"], $myrow["name"], $myrow["cat_id"], $myrow["inactive"], $data);
                        }
                    }
                }
            }
           add_to_datalogs($currentdate, $remarks, $user_name);
           $dsplymsg = _("The Brand has been successfully entered...");
        }elseif ($_POST['datadesc'] == "brand") {
            $branchcode = (isset($_POST['branch']) ? $_POST['branch'] : $_GET['branch']);
            $user_name = $_SESSION["wa_current_user"]->username;
            $currentdate = date('Y-m-d');
            $remarks = 'Classification' . '  -  ' . 'Filter Date: ' . date('Y-m-d',strtotime($_POST['date_from'])) . ' - ' . date('Y-m-d',strtotime($_POST['date_to']));
            
            if (is_array($branchcode) || is_object($branchcode))
            {
                foreach($branchcode as $value=>$data)
                {
                    $result = get_classification_to_transfer();
                    while ($myrow = db_fetch($result)) {
                        $counter = 0;

                        $checkclass = check_class($myrow["id"], $data);
                        while ($checkrow = db_fetch($checkclass)) {
                            $counter++;
                        }

                        if ($counter > 0) {
                            update_to_classification($myrow["id"], $myrow["code"], $myrow["name"], $myrow["inactive"], $data);
                        }else{
                            add_to_classification($myrow["code"], $myrow["name"], $myrow["inactive"], $data);
                        }
                    }
                }
            }
           add_to_datalogs($currentdate, $remarks, $user_name);
           $dsplymsg = _("The Classification has been successfully entered...");
        }elseif ($_POST['datadesc'] == "Subcatgry") {
            $branchcode = (isset($_POST['branch']) ? $_POST['branch'] : $_GET['branch']);
            $user_name = $_SESSION["wa_current_user"]->username;
            $currentdate = date('Y-m-d');
            $remarks = 'Sub Categoty' . '  -  ' . 'Filter Date: ' . date('Y-m-d',strtotime($_POST['date_from'])) . ' - ' . date('Y-m-d',strtotime($_POST['date_to']));
            
            if (is_array($branchcode) || is_object($branchcode))
            {
                foreach($branchcode as $value=>$data)
                {
                    $result = get_subcategory_to_transfer();
                    while ($myrow = db_fetch($result)) {
                        $counter = 0;

                        $checksubcateg = check_sub_categ($myrow["id"], $data);
                        while ($checkrow = db_fetch($checksubcateg)) {
                            $counter++;
                        }

                        if ($counter > 0) {
                            update_to_subcategory($myrow["id"], $myrow["code"], $myrow["name"], $myrow["inactive"], $data);
                        }else{
                            add_to_subcategory($myrow["code"], $myrow["name"], $myrow["inactive"], $data);
                        }
                    }
                }
            }
           add_to_datalogs($currentdate, $remarks, $user_name);
           $dsplymsg = _("The Sub-category has been successfully entered...");
        }elseif ($_POST['datadesc'] == "gl") {
            $branchcode = (isset($_POST['branch']) ? $_POST['branch'] : $_GET['branch']);
            $user_name = $_SESSION["wa_current_user"]->username;
            $currentdate = date('Y-m-d');
            $remarks = 'GL Accouts' . '  -  ' . 'Filter Date: ' . date('Y-m-d',strtotime($_POST['date_from'])) . ' - ' . date('Y-m-d',strtotime($_POST['date_to']));
            
            if (is_array($branchcode) || is_object($branchcode))
            {
                foreach($branchcode as $value=>$data)
                {
                    $result = get_glaccount_to_transfer();
                    while ($myrow = db_fetch($result)) {
                        $counter = 0;

                        $checkgl = check_glaccount($myrow["account_code"], $data);
                        while ($checkrow = db_fetch($checkgl)) {
                            $counter++;
                        }

                        if ($counter > 0) {
                            update_to_glaccount($myrow["account_code"], $myrow["account_code2"], $myrow["account_name"], $myrow["account_type"], $myrow["inactive"], 
                            $myrow["control"], $myrow["normal_balance"], $data);
                        }else {
                            add_to_glaccount($myrow["account_code"], $myrow["account_code2"], $myrow["account_name"], $myrow["account_type"], $myrow["inactive"], 
                            $myrow["control"], $myrow["normal_balance"], $data);
                        }
                    }
                }
            }
           add_to_datalogs($currentdate, $remarks, $user_name);
           $dsplymsg = _("The Gl Account has been successfully entered...");
        }elseif ($_POST['datadesc'] == "bank") {
            $branchcode = (isset($_POST['branch']) ? $_POST['branch'] : $_GET['branch']);
            $user_name = $_SESSION["wa_current_user"]->username;
            $currentdate = date('Y-m-d');
            $remarks = 'Bank Accouts' . '  -  ' . 'Filter Date: ' . date('Y-m-d',strtotime($_POST['date_from'])) . ' - ' . date('Y-m-d',strtotime($_POST['date_to']));
            
            if (is_array($branchcode) || is_object($branchcode))
            {
                foreach($branchcode as $value=>$data)
                {
                    $result = get_bankaccount_to_transfer();
                    while ($myrow = db_fetch($result)) {
                        $counter = 0;

                        $checkbank = check_bankaccount($myrow["id"], $data);
                        while ($checkrow = db_fetch($checkbank)) {
                            $counter++;
                        }

                        if ($counter > 0) {
                            update_to_bankaccount($myrow["account_code"], $myrow["account_type"], $myrow["bank_account_name"], $myrow["bank_account_number"], $myrow["bank_name"], 
                            $myrow["bank_address"], $myrow["bank_curr_code"], $myrow["dflt_curr_act"], $myrow["id"], $myrow["bank_charge_act"], $myrow["last_reconciled_date"], 
                            $myrow["ending_reconcile_balance"], $myrow["inactive"], $data);
                        }else {
                            add_to_bankaccount($myrow["account_code"], $myrow["account_type"], $myrow["bank_account_name"], $myrow["bank_account_number"], $myrow["bank_name"], 
                            $myrow["bank_address"], $myrow["bank_curr_code"], $myrow["dflt_curr_act"], $myrow["bank_charge_act"], $myrow["last_reconciled_date"], 
                            $myrow["ending_reconcile_balance"], $myrow["inactive"], $data);
                        }
                    }
                }
            }
           add_to_datalogs($currentdate, $remarks, $user_name);
           $dsplymsg = _("The Bank Account has been successfully entered...");
        }elseif ($_POST['datadesc'] == "gl_grp_cls") {
            $branchcode = (isset($_POST['branch']) ? $_POST['branch'] : $_GET['branch']);
            $user_name = $_SESSION["wa_current_user"]->username;
            $currentdate = date('Y-m-d');
            $remarks = 'GL Accouts Group/Classes' . '  -  ' . 'Filter Date: ' . date('Y-m-d',strtotime($_POST['date_from'])) . ' - ' . date('Y-m-d',strtotime($_POST['date_to']));
            
            if (is_array($branchcode) || is_object($branchcode))
            {
                foreach($branchcode as $value=>$data)
                {
                    $result = get_glaccountgroup_to_transfer();
                    while ($myrow = db_fetch($result)) {
                        $counter = 0;

                        $checkgroup = check_groupaccount($myrow["id"], $data);
                        while ($checkrow = db_fetch($checkgroup)) {
                            $counter++;
                        }

                        if ($counter > 0) {
                            update_to_acountgrp($myrow["id"], $myrow["name"], $myrow["class_id"], $myrow["parent"], $myrow["inactive"], $data);
                        }else {
                            add_to_accountgrp($myrow["id"], $myrow["name"], $myrow["class_id"], $myrow["parent"], $myrow["inactive"], $data);
                        }
                    }

                    $result1 = get_glaccountclass_to_transfer();
                    while ($myrow1 = db_fetch($result1)) {
                        $counter = 0;

                        $checkclass = check_classaccount($myrow1["cid"], $data);
                        while ($checkrow = db_fetch($checkclass)) {
                            $counter++;
                        }

                        if ($counter > 0) {
                            update_to_classaccount($myrow1["cid"], $myrow1["class_name"], $myrow1["ctype"], $myrow1["inactive"], $data);
                        }else {
                            add_to_classaccount($myrow1["cid"], $myrow1["class_name"], $myrow1["ctype"], $myrow1["inactive"], $data);
                        }
                    }
                }
            }
           add_to_datalogs($currentdate, $remarks, $user_name);
           $dsplymsg = _("The GL Group/Classes Account has been successfully entered...");
        }elseif ($_POST['datadesc'] == "syst_cst") {
            $branchcode = (isset($_POST['branch']) ? $_POST['branch'] : $_GET['branch']);
            $user_name = $_SESSION["wa_current_user"]->username;
            $currentdate = date('Y-m-d');
            $remarks = 'System Cost' . '  -  ' . 'Filter Date: ' . date('Y-m-d',strtotime($_POST['date_from'])) . ' - ' . date('Y-m-d',strtotime($_POST['date_to']));
            
            if (is_array($branchcode) || is_object($branchcode))
            {
                foreach($branchcode as $value=>$data)
                {    
                    $result = get_systemcost_to_transfer();
                    while ($myrow = db_fetch($result)) {
                        $counter = 0;

                        $checkcost = check_systemcost($myrow["id"], $data);
                        while ($checkrow = db_fetch($checkcost)) {
                            $counter++;
                        }

                        if ($counter > 0) {
                            update_to_systemcost($myrow["id"], $myrow["cost_type"], $myrow["tax_included"], $myrow["factor"], $myrow["inactive"], $data);
                        }else {
                            add_to_systemcost($myrow["cost_type"], $myrow["tax_included"], $myrow["factor"], $myrow["inactive"], $data);
                        }
                    }
                }
            }
           add_to_datalogs($currentdate, $remarks, $user_name);
           $dsplymsg = _("The System Cost has been successfully entered...");
        }elseif ($_POST['datadesc'] == "user") {
            $branchcode = (isset($_POST['branch']) ? $_POST['branch'] : $_GET['branch']);
            $user_name = $_SESSION["wa_current_user"]->username;
            $currentdate = date('Y-m-d');
            $remarks = 'User' . '  -  ' . 'Filter Date: ' . date('Y-m-d',strtotime($_POST['date_from'])) . ' - ' . date('Y-m-d',strtotime($_POST['date_to']));
            
            if (is_array($branchcode) || is_object($branchcode))
            {
                foreach($branchcode as $value=>$data)
                {  
                    $result = get_user_to_transfer();
                    while ($myrow = db_fetch($result)) {
                        $counter = 0;

                        $checkuser = check_datauser($myrow["user_id"], $data);
                        while ($checkrow = db_fetch($checkuser)) {
                            $counter++;
                        }

                        if ($counter > 0) {
                            update_to_user($myrow["user_id"], $myrow["real_name"], $myrow["role_id"], $myrow["phone"], $myrow["email"],
                            $myrow["language"], $myrow["date_format"], $myrow["date_sep"], $myrow["tho_sep"], $myrow["dec_sep"], $myrow["theme"], $myrow["page_size"],
                            $myrow["prices_dec"], $myrow["qty_dec"], $myrow["rates_dec"], $myrow["percent_dec"], $myrow["show_gl"], $myrow["show_codes"], $myrow["show_hints"], 
                            $myrow["query_size"], $myrow["graphic_links"], $myrow["pos"], $myrow["print_profile"], $myrow["rep_popup"], $myrow["sticky_doc_date"], 
                            $myrow["startup_tab"], $myrow["transaction_days"], $myrow["save_report_selections"], $myrow["use_date_picker"], $myrow["def_print_destination"], 
                            $myrow["def_print_orientation"], $myrow["inactive"], $myrow["passupdate"], $data);
                        }else {
                            add_to_user($myrow["user_id"], $myrow["password"], $myrow["real_name"], $myrow["role_id"], $myrow["phone"], $myrow["email"],
                            $myrow["language"], $myrow["date_format"], $myrow["date_sep"], $myrow["tho_sep"], $myrow["dec_sep"], $myrow["theme"], $myrow["page_size"],
                            $myrow["prices_dec"], $myrow["qty_dec"], $myrow["rates_dec"], $myrow["percent_dec"], $myrow["show_gl"], $myrow["show_codes"], $myrow["show_hints"], 
                            $myrow["query_size"], $myrow["graphic_links"], $myrow["pos"], $myrow["print_profile"], $myrow["rep_popup"], 
                            $myrow["sticky_doc_date"], $myrow["startup_tab"], $myrow["transaction_days"], $myrow["save_report_selections"], $myrow["use_date_picker"], 
                            $myrow["def_print_destination"], $myrow["def_print_orientation"], $myrow["inactive"], $myrow["passupdate"], $data);
                        }
                    }
                }
            }
           add_to_datalogs($currentdate, $remarks, $user_name);
           $dsplymsg = _("The User has been successfully entered...");
        }elseif ($_POST['datadesc'] == "salcash") {
            $branchcode = (isset($_POST['branch']) ? $_POST['branch'] : $_GET['branch']);
            $user_name = $_SESSION["wa_current_user"]->username;
            $currentdate = date('Y-m-d');
            $remarks = 'Sales Price/Cash Price Setup' . '  -  ' . 'Filter Date: ' . date('Y-m-d',strtotime($_POST['date_from'])) . ' - ' . date('Y-m-d',strtotime($_POST['date_to']));
           
            if (is_array($branchcode) || is_object($branchcode))
            {
                foreach($branchcode as $value=>$data)
                {
                    $result = get_salesprice_to_transfer();
                    while ($myrow = db_fetch($result)) {
                        $counter = 0;

                        $checkslprc = check_salesprice($myrow["id"], $data);
                        while ($checkrow = db_fetch($checkslprc)) {
                            $counter++;
                        }

                        if ($counter > 0) {
                            update_to_salesprice($myrow["id"], $myrow["sales_type"], $myrow["remarks"], $myrow["tax_included"], $myrow["factor"], $myrow["inactive"], $data);
                        }else {
                            add_to_salesprice($myrow["sales_type"], $myrow["remarks"], $myrow["tax_included"], $myrow["factor"], $myrow["inactive"], $data);
                        }
                    }

                    $result1 = get_cashprice_to_transfer();
                    while ($myrow1 = db_fetch($result1)) {
                        $counter = 0;

                        $checkcash = check_cashprice($myrow1["id"], $data);
                        while ($checkrow = db_fetch($checkcash)) {
                            $counter++;
                        }

                        if ($counter > 0) {
                            update_to_cashprice($myrow1["id"], $myrow1["scash_type"], $myrow1["tax_included"], $myrow1["factor"], $myrow1["inactive"], $data);
                        }else {
                            add_to_cashprice($myrow1["scash_type"], $myrow1["tax_included"], $myrow1["factor"], $myrow1["inactive"], $data);
                        }
                    }
                }
            }
           add_to_datalogs($currentdate, $remarks, $user_name);
           $dsplymsg = _("The Sales Price/Cash Price Setup has been successfully entered...");
        }elseif ($_POST['datadesc'] == "salestrct") {
            $branchcode = (isset($_POST['branch']) ? $_POST['branch'] : $_GET['branch']);
            $user_name = $_SESSION["wa_current_user"]->username;
            $currentdate = date('Y-m-d');
            $remarks = 'Sales Type/Reason & Collection Type' . '  -  ' . 'Filter Date: ' . date('Y-m-d',strtotime($_POST['date_from'])) . ' - ' . date('Y-m-d',strtotime($_POST['date_to']));
           
            if (is_array($branchcode) || is_object($branchcode))
            {
                foreach($branchcode as $value=>$data)
                {
                    $result = get_salestype_to_transfer();
                    while ($myrow = db_fetch($result)) {
                        $counter = 0;

                        $checksltype = check_salestype($myrow["id"], $data);
                        while ($checkrow = db_fetch($checksltype)) {
                            $counter++;
                        }

                        if ($counter > 0) {
                            update_to_salestype($myrow["id"], $myrow["code"], $myrow["name"], $myrow["inactive"], $data);
                        }else {
                            add_to_salestype($myrow["code"], $myrow["name"], $myrow["inactive"], $data);
                        }
                    }

                    $result1 = get_salesreason_to_transfer();
                    while ($myrow1 = db_fetch($result1)) {
                        $counter = 0;

                        $checkreason = check_salesreason($myrow1["id"], $data);
                        while ($checkrow = db_fetch($checkreason)) {
                            $counter++;
                        }

                        if ($counter > 0) {
                            update_to_salesreason($myrow1["id"], $myrow1["reason"], $myrow1["inActive"], $data);
                        }else {
                            add_to_salesreason($myrow1["reason"], $myrow1["inActive"], $data);
                        }
                    }

                    $result2 = get_collectiontype_to_transfer();
                    while ($myrow2 = db_fetch($result2)) {
                        $counter = 0;

                        $checkclctype = check_collectiontype($myrow2["collect_id"], $data);
                        while ($checkrow = db_fetch($checkclctype)) {
                            $counter++;
                        }

                        if ($counter > 0) {
                            update_to_collectiontype($myrow2["collect_id"], $myrow2["collection"], $myrow2["inactive"], $data);
                        }else {
                            add_to_collectiontype($myrow2["collection"], $myrow2["inactive"], $data);
                        }
                    }
                }
            }
           add_to_datalogs($currentdate, $remarks, $user_name);
           $dsplymsg = _("Sales Type/Reason & Collection Type has been successfully entered...");
        }elseif ($_POST['datadesc'] == "creditsis") {
            $branchcode = (isset($_POST['branch']) ? $_POST['branch'] : $_GET['branch']);
            $user_name = $_SESSION["wa_current_user"]->username;
            $currentdate = date('Y-m-d');
            $remarks = 'Credit/Sales Incentive Setup' . '  -  ' . 'Filter Date: ' . date('Y-m-d',strtotime($_POST['date_from'])) . ' - ' . date('Y-m-d',strtotime($_POST['date_to']));
            
            if (is_array($branchcode) || is_object($branchcode))
            {
                foreach($branchcode as $value=>$data)
                {
                    $result = get_credit_to_transfer();
                    while ($myrow = db_fetch($result)) {
                        $counter = 0;

                        $checkcredit = check_credit($myrow["id"], $data);
                        while ($checkrow = db_fetch($checkcredit)) {
                            $counter++;
                        }

                        if ($counter > 0) {
                            update_to_credit($myrow["id"], $myrow["reason_description"], $myrow["dissallow_invoices"], $myrow["inactive"], $data);
                        }else {
                            add_to_credit($myrow["reason_description"], $myrow["dissallow_invoices"], $myrow["inactive"], $data);
                        }
                    }

                    $result1 = get_salesincentive_to_transfer();
                    while ($myrow1 = db_fetch($result1)) {
                        $counter = 0;

                        $checkincentive = check_incentive($myrow1["id"], $data);
                        while ($checkrow = db_fetch($checkincentive)) {
                            $counter++;
                        }

                        if ($counter > 0) {
                            update_to_incentive($myrow1["id"], $myrow1["description"], $myrow1["module_type"], $myrow1["inactive"], $data);
                        }else {
                            add_to_incentive($myrow1["description"], $myrow1["module_type"], $myrow1["inactive"], $data);
                        }
                    } 
                }
            }             
           add_to_datalogs($currentdate, $remarks, $user_name);
           $dsplymsg = _("Credit/Sales Incentive Setup has been successfully entered...");
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