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
$page_security = 'SA_GRNREPO';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/repossess/includes/repossessed.inc");

add_access_extensions();
simple_page_mode(true);
//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");

if($_GET['rtype']=='REPOBEG'){
    add_js_ufile($path_to_root ."/js/repo_accounts_beg.js");
}else{
    add_js_ufile($path_to_root ."/js/repo_accounts.js");
}


//----------------------------------------------: for store js :---------------------------------------
if(isset($_GET['getReference'])){
    $reference = $Refs->get_next(ST_RRREPO);
    echo '({"success":"true","reference":"'.$reference.'"})';
    return;
}

if(isset($_GET['get_Customer']))
{
    if(!empty($_GET['debtor_ref'])){
        $myrow = get_customer_by_ref($_GET['debtor_ref']);
        
        $status_array[] = array('debtor_no'=>$myrow["debtor_no"],
                    'debtor_ref'=>$myrow["debtor_ref"],
                    'name'=>htmlentities($myrow["name"])
                );
    }else{
        if($_GET['rtype'] == 'replcmnt'){
            $result = get_customer_from_replacement();
        }else if($_GET['rtype'] == 'mt'){
            $result = get_mt_customer($_GET['repo_id'], $_GET['from_branch']);
        }else if($_GET['rtype'] == 'all'){
            $result = get_all_customer();
        }else{
            $result = get_customer_account_repo($_GET['rtype']);
        }

        $total = DB_num_rows($result);
        
        while ($myrow = db_fetch($result)) {
            $status_array[] = array('debtor_no'=>$myrow["debtor_no"],
                                   'debtor_ref'=>$myrow["debtor_ref"],
                                   'name'=>htmlentities($myrow["name"])
                                );
        }
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_InvoiceNo']))
{
    if($_GET['rtype'] == 'mt') {
        $result = get_mt_repo_account($_GET['repo_id'], $_GET['from_branch']);
        while ($myrow = db_fetch($result)) {
            $status_array[] = array('id'=>$myrow["id"],
                                   'name'=>($myrow["reference_no"].' > '.$myrow["category"].' > '.$myrow["stock_id"].' > '.$myrow["description"]),
                                   'type'=>$myrow["ar_trans_type"],
                                   'tran_date'=>$myrow["trans_date"],
                                   'term'=>$myrow["term"],
                                   'dp_amount'=>$myrow["downpayment"],
                                   'out_ar'=>$myrow["outstanding_ar"],
                                   'monthly_amort'=>$myrow["monthly_amount"],
                                   'balance'=>$myrow["balance"],
                                   'first_duedate'=>$myrow["firstdue_date"],
                                   'maturty_date'=>$myrow["maturity_date"],
                                   'lcp_amount'=>$myrow["lcp_amount"],
                                   'unit_cost'=>$myrow["unit_price"],
                                   'category_id'=>$myrow["category_id"],
                                   'category_desc'=>$myrow["category"],
                                   'addon_amount'=>$myrow["addon_amount"],
                                   'unrecoverd'=>$myrow["unrecovered_cost"],
                                   'totalunrecoverd'=>$myrow["total_unrecovered"],
                                   'overdue'=>$myrow["over_due"],
                                   'pastdue'=>$myrow["past_due"],
                                   'GPM'=>$myrow["gpm"],
                                   'CGPM'=>$CGPM,
                                   'remarks'=>$myrow["comments"],
                                   'base_transno'=>$myrow["id"],
                                   'base_transtype'=>$myrow["type"]
                                );
        }
    }else{
        if($_GET['rtype'] == 'trmode') {
            $result = get_invtermode_to_repo($_GET['debtor_id'], true);
        }else if($_GET['rtype'] == 'replcmnt') {
            $result = get_replace_item_loans($_GET['debtor_id']);
        }else{
            $result = get_invoice_per_customer_repo($_GET['debtor_id'], $_GET['rtype']);
        }
        
        $total = DB_num_rows($result);
        $addon_amount = get_company_pref('addon_amount');
    
        while ($myrow = db_fetch($result)) {
            /**** 
                SI new -- unrecovered cost = ((total payment * (1-profit margin)) - cost of sales)
                SI Openbal -- unrecovered cost = (total unit cost(/cost of sales) - recovered cost)
            *****/
            $totalpayment = get_payment_applied($myrow["type"], $myrow["trans_no"]);
            $costofsales = get_cost_Sales($myrow["type"], $myrow["trans_no"], $_GET['debtor_id']);
            
            $balance = ($myrow["ar_amount"] - $totalpayment);
    
            $CGPM = (1 - $myrow["profit_margin"]);
            $dgp = ($totalpayment * $CGPM);
            //echo "total:".$totalpayment. "-margin:".$CGPM."-cost:".$costofsales;
            if($myrow["opening_balances"] == 1){
                $unrecoverd = ($myrow["total_amount"] - $myrow["recovered_cost"]);
            }else{
                $unrecoverd = ($costofsales - $dgp);
            }

            if($myrow["category_id"] != 14){
                $addon_amount = 0;
            }
            $totalunrecoverd = (abs($unrecoverd) + $addon_amount);
            
            //**** overdue amount = (unpaid months to date repo) */
            //**** pastdue amount = (AR - total payment  or balance) */
            if(date('Y-m-d', strtotime($myrow["maturity_date"])) < date('Y-m-d', strtotime($_GET['repo_date']))){
                //maturity :: pastdue
                $month_due = mos_interval_r($myrow["maturity_date"], $_GET['repo_date']);
                $amount_due = ($myrow["amortization_amount"] * $month_due);
    
                $PastDue = ($balance + $amount_due);
                $OverDue = 0;
            }else{
                //overdue
                $OverDue = (get_sched_loans_due($myrow["type"], $myrow["trans_no"], date('Y-m-d', strtotime($_GET['repo_date']))) - get_total_payment_applied($myrow["type"], $myrow["trans_no"]));
                $PastDue = 0;
            }
    
            if($myrow["total_amount"] == 0){
                $unit_cost = get_unitcost($myrow["invoice_ref_no"]);
            }else{
                $unit_cost = $myrow["total_amount"];
            }
            if($_GET['rtype'] == 'trmode') {
                $invoice_no = $myrow["invoice_no"];
                $invoice_type = $myrow["debtor_trans_type"];
            }else{
                $invoice_no = $myrow["trans_no"];
                $invoice_type = $myrow["type"];
            }
    
            $status_array[] = array('id'=>$myrow["trans_no"],
                                   'name'=>($myrow["reference"].' > '.$myrow["category"].' > '.$myrow["stock_id"].' > '.$myrow["itemdesc"]),
                                   'type'=>$myrow["type"],
                                   'tran_date'=>$myrow["tran_date"],
                                   'term'=>$myrow["months_term"],
                                   'dp_amount'=>$myrow["downpayment_amount"],
                                   'out_ar'=>$myrow["outstanding_ar_amount"],
                                   'monthly_amort'=>$myrow["amortization_amount"],
                                   'balance'=>$balance,
                                   'first_duedate'=>$myrow["firstdue_date"],
                                   'maturty_date'=>$myrow["maturity_date"],
                                   'lcp_amount'=>$myrow["lcp_amount"],
                                   'unit_cost'=>$unit_cost,
                                   'category_id'=>$myrow["category_id"],
                                   'category_desc'=>$myrow["category"],
                                   'addon_amount'=>$addon_amount,
                                   'unrecoverd'=>abs($unrecoverd),
                                   'totalunrecoverd'=>$totalunrecoverd,
                                   'overdue'=>$OverDue,
                                   'pastdue'=>$PastDue,
                                   'GPM'=>$myrow["profit_margin"],
                                   'CGPM'=>$CGPM,
                                   'remarks'=>"",
                                   'base_transno'=>$invoice_no,
                                   'base_transtype'=>$invoice_type
                                );
        }
    }

    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_Item_details']))
{
    if($_GET['tag'] == 'add'){
        $DataOnGrid = stripslashes(html_entity_decode($_GET['DataitmGrid']));
        $objDataGrid = json_decode($DataOnGrid, true);
        $counter = 0;

        $result = get_stock_items($_GET['category'], $_GET['stockid'], $_GET['itemcode']);
        $total = DB_num_rows($result);

        if (count($objDataGrid) != 0){
            foreach($objDataGrid as $value=>$data) {
                $counter++;
                $status_array[] = array('id'=>$counter,
                                'repo_id'=>$data["repo_id"],
                                'ar_trans_no'=>$data["ar_trans_no"],
                                'stock_id'=>$data['stock_id'],
                                'description'=>$data['description'],
                                'qty'=>$data['qty'],
                                'unit_price'=>$data['unit_price'],
                                'serial_no'=>$data['serial_no'],
                                'chassis_no'=>$data['chassis_no'],
                                'color_code'=>$data['color_code'],
                                'status'=>$data['status']
                            );
            }
        }

        while ($myrow = db_fetch($result)) {
            $status_array[] = array('id'=>$myrow["id"],
                'repo_id'=>$myrow["repo_id"],
                'ar_trans_no'=>$myrow["ar_trans_no"],
                'stock_id'=>$myrow["stock_id"],
                'description'=>$myrow["description"],
                'qty'=>1,
                'unit_price'=>0,
                'serial_no'=>'',
                'chassis_no'=>'',
                'color_code'=>$myrow["item_code"],
                'status'=>0
            );
        }
    }else{
        if($_GET['rtype'] == 'mt'){
            $result = get_mt_repo_account($_GET['repo_id'], $_GET['from_branch']);
        }else{
            if(isset($_GET['repo_id'])){
                $result = get_repo_accounts_item_details($_GET['repo_id']);
            }else if($_GET['rtype'] == 'replcmnt') {
                $result = get_replace_item_to_repo($_GET['transNo']);
            }else{
                if($_GET['rtype'] == 'trmode') {
                    $transNo = $_GET['base_transno'];
                    $transtype = $_GET['base_transtype'];
                }else{
                    $transNo = $_GET['transNo'];
                    $transtype = $_GET['transtype'];
                }
    
                $result = get_item_detials_to_repo($transNo, $transtype);
            }
        }
        
        $total = DB_num_rows($result);
    
        while ($myrow = db_fetch($result)) {
            if(isset($_GET['repo_id'])){
                $serial = $myrow["serial_no"];
                $qty = $myrow["qty"];
            }else{
                $serial = $myrow["lot_no"];
                $qty = $myrow["quantity"];
            }
            $status_array[] = array('id'=>$myrow["id"],
                                   'repo_id'=>$myrow["repo_id"],
                                   'ar_trans_no'=>$myrow["ar_trans_no"],
                                   'stock_id'=>$myrow["stock_id"],
                                   'description'=>$myrow["description"],
                                   'qty'=>$qty,
                                   'unit_price'=>$myrow["unit_price"],
                                   'serial_no'=>$serial,
                                   'chassis_no'=>$myrow["chassis_no"],
                                   'color_code'=>$myrow["color_code"],
                                   'status'=>$myrow["status"]
                                );
        }
    }
   
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['getcategory'])){
    $result = get_itemcategory($_GET['getcategory']);
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow['category_id'],
                    'name'=>$myrow['description']);
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_repodetails']))
{
    $result = get_repo_accounts();
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        if($myrow["transfer_id"] != 0){
            $branch = get_branch_info($myrow['branch_code']);

            $name = $branch[0]["name"];
            $debtor_no = $myrow['branch_code'];
            $debtor_ref = $myrow['branch_code'];
        }else{
            $name = htmlentities($myrow["name"]);
            $debtor_no = $myrow["debtor_no"];
            $debtor_ref = $myrow["debtor_ref"];
        }
        $status_array[] = array('id'=>$myrow["id"],
                               'ar_trans_no'=>$myrow["ar_trans_no"],
                               'ar_trans_type'=>$myrow["ar_trans_type"],
                               'trans_date'=>$myrow["trans_date"],
                               'type'=>ST_RRREPO,
                               'repo_date'=>$myrow["repo_date"],
                               'repo_type'=>$myrow["repo_type"],
                               'reference_no'=>$myrow["reference_no"],
                               'debtor_no'=>$debtor_no,
                               'debtor_ref'=>$debtor_ref,
                               'name'=>$name,
                               'lcp_amount'=>$myrow["lcp_amount"],
                               'downpayment'=>$myrow["downpayment"],
                               'outstanding_ar'=>$myrow["outstanding_ar"],
                               'amortization_amount'=>$myrow["monthly_amount"],
                               'term'=>$myrow["term"],
                               'release_date'=>$myrow["release_date"],
                               'firstdue_date'=>$myrow["firstdue_date"],
                               'maturity_date'=>$myrow["maturity_date"],
                               'balance'=>$myrow["balance"],
                               'spot_cash_amount'=>$myrow["spot_cash_amount"],
                               'total_amount'=>$myrow["total_amount"],
                               'unrecovered_cost'=>$myrow["unrecovered_cost"],
                               'addon_amount'=>$myrow["addon_amount"],
                               'total_unrecovered'=>$myrow["total_unrecovered"],
                               'over_due'=>$myrow["over_due"],
                               'past_due'=>$myrow["past_due"],
                               'category_id'=>$myrow["category_id"],
                               'category_desc'=>$myrow["cat_desc"],
                               'branch_code'=>$myrow["branch_code"],
                               'comments'=>$myrow["comments"],
                               'gpm'=>$myrow["gpm"],
                               'transfer_id'=>$myrow["transfer_id"],
                               'accu_amount'=>$myrow["accu_amount"],
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['getbranch'])){
    global $db_connections;
    $conn = $db_connections;
    $total = count($conn);

	for ($i = 0; $i < $total; $i++)
	{
        $status_array[] = array('id'=>$conn[$i]['branch_code'],
                                'name'=>$conn[$i]['name'],
                                'area'=>$conn[$i]['branch_area'],
                                'gl_account'=>$conn[$i]['gl_account']);
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;

}
if(isset($_GET['getmtItem']))
{
    $branch_code = get_company_pref("branch_code");
    $result = get_mt_per_branch($branch_code, $_GET['from_branch']);
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow["mt_header_id"],
                               'desc'=>($myrow["mt_header_reference"] . ' - ' . $myrow["category"] . ' - ' . $myrow["mt_details_stock_id"] . ' - ' . $myrow["description"]),
                               'fk_id'=>$myrow["mt_header_repo_account_id"]
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['getStockItems']))
{
    $result = get_stock_items($_GET['category']);
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('stockid'=>$myrow["stock_id"],
                               'itemcode'=>$myrow["item_code"],
                               'description'=>$myrow["description"]
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}


if(isset($_GET['submit']))
{
    //initialise no input errors assumed initially before we proceed
    //0 is by default no errors
    $InputError = 0;
    $unit_price = 0;
    
    if($_POST['repo_type'] != 'ffe') {
        if (empty($_POST['transtype'])) {
            $InputError = 1;
            $dsplymsg = _('Some fields are empty or contain an improper value. Please reload the page and fill up the required field.');
        }
        if (empty($_POST['InvoiceNo'])) {
            $InputError = 1;
            $dsplymsg = _('Invoice number must not be empty.');
        }
    }
    if (empty($_POST['customercode'])) {
        $InputError = 1;
        $dsplymsg = _('Customer code must not be empty.');
    }
    if (empty($_POST['customername'])) {
        $InputError = 1;
        $dsplymsg = _('Customer name must not be empty.');
    }
    if (empty($_POST['repo_date'])) {
        $InputError = 1;
        $dsplymsg = _('Repo date must not be empty.');
    }
    if (empty($_POST['repo_type'])) {
        $InputError = 1;
        $dsplymsg = _('Type must not be empty.');
    }
    if($_POST['repo_type'] == "mt"){
        $branch_code = $_POST['cBranch'];
    }else{
        $branch_code = get_company_pref("branch_code");
    }
    if (empty($_POST['release_date'])) {
        $InputError = 1;
        $dsplymsg = _('Invoice/Release date must not be empty.');
    }
    if (empty($_POST['months_term'])) {
        $InputError = 1;
        $dsplymsg = _('Term must not be empty.');
    }
    if ($_POST['months_term'] == 0) {
        $InputError = 1;
        $dsplymsg = _('Term must be greater than 0.');
    }
    if (empty($_POST['downpayment'])) {
        $InputError = 1;
        $dsplymsg = _('downpayment amount must not be empty.');
    }
    if (empty($_POST['outs_ar_amount'])) {
        $InputError = 1;
        $dsplymsg = _('Outstanding AR mus not be empty.');
    }
    if (empty($_POST['amort_amount'])) {
        $InputError = 1;
        $dsplymsg = _('Amortization must not be empty.');
    }
    if (empty($_POST['balance'])) {
        $InputError = 1;
        $dsplymsg = _('Balance must not be empty.');
    }
    if (empty($_POST['firstdue_date'])) {
        $InputError = 1;
        $dsplymsg = _('Firstdue date must not be empty.');
    }
    if (empty($_POST['maturity_date'])) {
        $InputError = 1;
        $dsplymsg = _('Maturity date must not be empty.');
    }
    if (empty($_POST['reference_no'])) {
        $InputError = 1;
        $dsplymsg = _('Reference no must not be empty.');
    }
    if (empty($_POST['category'])) {
        $InputError = 1;
        $dsplymsg = _('Category must not be empty.');
    }
    if (empty($_POST['lcp_amount'])) {
        $InputError = 1;
        $dsplymsg = _('LCP amount must not be empty.');
    }
    if (empty($_POST['spotcash'])) {
        $InputError = 1;
        $dsplymsg = _('Spot cash must not be empty.');
    }
    if (empty($_POST['total_amount'])) {
        $InputError = 1;
        $dsplymsg = _('Total amount must not be empty.');
    }
    if (empty($_POST['unrecovrd_cost'])) {
        $InputError = 1;
        $dsplymsg = _('Unrecovered cost must not be empty.');
    }
    if (empty($_POST['total_unrecovrd'])) {
        $InputError = 1;
        $dsplymsg = _('Total Unrecovered cost must not be empty.');
    }
    if (empty($_POST['gpm'])) {
        $InputError = 1;
        $dsplymsg = _('Profit margin must not be empty.');
    }
    if (empty($_POST['remarks'])) {
        $InputError = 1;
        $dsplymsg = _('remarks must not be empty.');
    }
    if (empty($_POST['addon_cost'])) {
        $_POST['addon_cost'] = 0;
    }
    if (empty($_POST['past_due'])) {
        $_POST['past_due'] = 0;
    }
    if (empty($_POST['over_due'])) {
        $_POST['over_due'] = 0;
    }

    if($_POST['repo_type'] == 'mt') {
        if (empty($_POST['cBranch'])) {
            $InputError = 1;
            $dsplymsg = _('Branch must not be empty.');
        }

        if (empty($_POST['mt_ref'])) {
            $InputError = 1;
            $dsplymsg = _('MT reference no must not be empty.');
        }

    }

    $DataOnGrid = stripslashes(html_entity_decode($_POST['DataOnGrid']));
    $objDataGrid = json_decode($DataOnGrid, true);
    
    //var_dump($objDataGrid);
    if (count($objDataGrid) == 0){
        $InputError = 1;
        $dsplymsg = _('Unit price must not be empty! Please try again.');
    }
    
    //if($_POST['repo_type'] == 'ffe') {
        if (count($objDataGrid) != 0){
            foreach($objDataGrid as $value=>$itmdata) {
                $unit_price = $itmdata['unit_price'];
                if($itmdata['unit_price'] == 0){
                    $InputError = 1;
                    $dsplymsg = _($itmdata['description'].' Unit price must not be empty! Please try again.');
                }
                if($_POST['category'] == '14'){
                    if (empty($itmdata['serial_no']) || empty($itmdata['chassis_no'])) {
                        $InputError = 1;
                        $dsplymsg = _('Serail/chasis number must not be empty! Please try again.');
                    }
                }
            }
        }
    if($_POST['repo_type'] != 'ffe') {
        if($_POST['repo_type'] == 'replcmnt') {
            $result = get_replace_item_to_repo($_POST['base_transno']);
        }else if($_POST['repo_type'] == 'mt') {
                $result = get_mt_repo_account($_POST['base_transno'], $_POST['cBranch']);
        }else{
            $result = get_item_detials_to_repo($_POST['base_transno'], $_POST['base_transtype']);
        }
        
        $item_row = db_fetch($result);
        if (empty($item_row['stock_id']) && empty($item_row['color_code'])) {
            $InputError = 1;
            $dsplymsg = _('Stock id and color code must not be empty.');
        }
    }

    $loc_code = get_default_location();
    if (empty($loc_code)) {
        $InputError = 1;
        $dsplymsg = _('cannot find default location. Please contact Automation team regarding this matter. Thank you...');
    }

    if ($InputError != 1){
        $GLtotal = 0;
        $trans_date = date('m/d/Y');
        $company_record = get_company_prefs();

        //new added field naming 03/02/2023 zhunx...
        if($_POST['repo_type'] == 'replcmnt') {
            $quantity = $item_row['quantity'];
        }else if($_POST['repo_type'] == 'mt') {
            $quantity = $item_row['qty'];
        }else{
            $quantity = $item_row['quantity'];
        }

        if($_POST['repo_type'] == 'mt') {

            $branch = get_branch_info($_POST['cBranch']);
            $PerBranchGL = $branch[0]["gl_account"];
            $Branch_name = $branch[0]["name"];

            //auto create customer
            $result = get_mt_customer($_POST['mtrep_fkid'], $_POST['cBranch']);
            $mtcustomer = db_fetch($result);

            $ncustomerst = check_existing_customer($mtcustomer['name'], $mtcustomer['age']);
            if($ncustomerst['debtor_no'] == 0){
                auto_create_customer($_POST['customercode'], $_POST['cBranch']);
                //retrieve new added customer
                $ncustomerst = get_new_added_customer($_POST['customercode'], $_POST['custname']);
                $debtor_no = $ncustomerst['debtor_no'];
            }else{
                $debtor_no = $_POST['customername'];
            }

            //copy info from originating branch
            $result = get_mt_repo_account($_POST['base_transno'], $_POST['cBranch']);
            $repoacct_row = db_fetch($result);

            $repo_id = add_repo_accounts(ST_RRREPO, $repoacct_row['ar_trans_no'], $repoacct_row['ar_trans_type'], $trans_date, date('m/d/Y',strtotime($repoacct_row['repo_date'])), $_POST['repo_type'], $_POST['reference_no'],
                                            $debtor_no, $repoacct_row['lcp_amount'], $repoacct_row['downpayment'], $repoacct_row['outstanding_ar'], $repoacct_row['monthly_amount'],
                                            $repoacct_row['term'], date('m/d/Y', strtotime($repoacct_row['release_date'])), date('m/d/Y', strtotime($repoacct_row['firstdue_date'])), date('m/d/Y', strtotime($repoacct_row['maturity_date'])), $repoacct_row['balance'],
                                            $repoacct_row['spot_cash_amount'], $repoacct_row['total_amount'], $repoacct_row['unrecovered_cost'], $repoacct_row['addon_amount'], $repoacct_row['total_unrecovered'],
                                            $repoacct_row['over_due'], $repoacct_row['past_due'], $repoacct_row['category_id'], $branch_code, $_POST['remarks'], $repoacct_row['gpm'], $_POST['mt_ref']);

            $repo_item_id = add_repo_item($repoacct_row['ar_trans_no'], $repo_id, $item_row['stock_id'], $item_row['description'], $quantity, $unit_price, $_POST['unrecovrd_cost'],
                                            $item_row['lot_no'], $item_row['chassis_no'], $item_row['color_code']);

            add_stock_move(ST_RRREPO, $item_row['stock_id'], $repo_id, $loc_code, $_POST['repo_date'], $_POST['reference_no'], $quantity, $_POST['unrecovrd_cost'],
                            0, $item_row['lot_no'], $item_row['chassis_no'], $_POST['category'], $item_row['color_code'], 0, 0, "repo");

            //item serialize
            add_item_serialise(ST_RRREPO, $item_row['color_code'], $repo_id, $loc_code, $_POST['reference_no'], $quantity, $item_row['lot_no'], $repo_item_id, $item_row['chassis_no'], $item_row['description'], '');
            //for gl entry
            //Repossessed Inventory - debit
            $repo_invty_act =  get_repo_invty_act($_POST['category']);
            if(isset($repo_invty_act)){

                $GLtotal += add_gl_trans_customer(ST_RRREPO, $repo_id, $_POST['repo_date'], $repo_invty_act, 0, 0, $_POST['unrecovrd_cost'], $_POST['customername'], "Cannot insert a GL transaction for the repossessed inventory", 0, $_POST['cBranch'], $Branch_name, 0, $_POST['mt_ref']);
            
            }

            // Branch Current -credit
            if(isset($PerBranchGL)){
                $GLtotal += add_gl_trans_customer(ST_RRREPO, $repo_id, $_POST['repo_date'], $PerBranchGL, 0, 0, -$_POST['unrecovrd_cost'], $_POST['customername'], "Cannot insert a GL transaction for the A/R account", 0, $_POST['cBranch'], $Branch_name, 0, $_POST['mt_ref']);
            }

            //update mt from branch/ho
            set_mt_repo_status($_POST['mt_ref'], $item_row['stock_id'], $_POST['cBranch']);

        }else if($_POST['repo_type'] == 'ffe'){

            $repo_id = add_repo_accounts(ST_RRREPO, 0, $_POST['transtype'], $trans_date, $_POST['repo_date'], $_POST['repo_type'], $_POST['reference_no'],
                        $_POST['customername'], $_POST['lcp_amount'], $_POST['downpayment'], $_POST['outs_ar_amount'], $_POST['amort_amount'],
                        $_POST['months_term'], $_POST['release_date'], $_POST['firstdue_date'], $_POST['maturity_date'], $_POST['balance'],
                        $_POST['spotcash'], $_POST['total_amount'], $_POST['unrecovrd_cost'], $_POST['addon_cost'], $_POST['total_unrecovrd'],
                        check_isempty($_POST['over_due']), check_isempty($_POST['past_due']), $_POST['category'], $branch_code, $_POST['remarks'], $_POST['gpm'], 0, $_POST['Accuamount']);

            if (count($objDataGrid) != 0){
                foreach($objDataGrid as $value=>$itmdata) {
                    $repo_item_id = add_repo_item(0, $repo_id, $itmdata['stock_id'], $itmdata['description'], $itmdata['qty'], $itmdata['unit_price'], $_POST['unrecovrd_cost'],
                                                    $itmdata['serial_no'], $itmdata['chassis_no'], $itmdata['color_code']);

                    add_stock_move(ST_RRREPO, $itmdata['stock_id'], $repo_id, $loc_code, $_POST['repo_date'], $_POST['reference_no'], $itmdata['qty'], $_POST['unrecovrd_cost'],
                                    0, $itmdata['serial_no'], $itmdata['chassis_no'], $_POST['category'], $itmdata['color_code'], 0, 0, "repo");

                    //item serialize
                    add_item_serialise(ST_RRREPO, $itmdata['color_code'], $repo_id, $loc_code, $_POST['reference_no'], $itmdata['qty'], $itmdata['serial_no'], $repo_item_id, $itmdata['chassis_no'], $itmdata['description'], '');
                }
            }
            //for gl entry
            //Repossessed Inventory - debit
            $repo_invty_act =  get_repo_invty_act($_POST['category']);
            if(isset($repo_invty_act)){
                $GLtotal += add_gl_trans_customer(ST_RRREPO, $repo_id, $_POST['repo_date'], $repo_invty_act, 0, 0, $_POST['unrecovrd_cost'], $_POST['customername'], "Cannot insert a GL transaction for the repossessed inventory", 0, $_POST['customername'], $_POST['custname'], 0, 0);
            }
            //accumulated -debit
            $accum_repo_act = $company_record["ffe_accum_repo_act"];
            if(isset($repo_invty_act)){
                $GLtotal += add_gl_trans_customer(ST_RRREPO, $repo_id, $_POST['repo_date'], $accum_repo_act, 0, 0, $_POST['Accuamount'], $_POST['customername'], "Cannot insert a GL transaction for the repossessed inventory", 0, $_POST['customername'], $_POST['custname'], 0, 0);
            }
            //ffe_repo_act -credit
            $ffe_repo_act = $company_record["ffe_repo_act"];
            if(isset($repo_invty_act)){
                $GLtotal += add_gl_trans_customer(ST_RRREPO, $repo_id, $_POST['repo_date'], $ffe_repo_act, 0, 0, -$_POST['lcp_amount'], $_POST['customername'], "Cannot insert a GL transaction for the repossessed inventory", 0, $_POST['customername'], $_POST['custname'], 0, 0);
            }

        }else{

            $repo_id = add_repo_accounts(ST_RRREPO, $_POST['InvoiceNo'], $_POST['transtype'], $trans_date, $_POST['repo_date'], $_POST['repo_type'], $_POST['reference_no'],
                                            $_POST['customername'], $_POST['lcp_amount'], $_POST['downpayment'], $_POST['outs_ar_amount'], $_POST['amort_amount'],
                                            $_POST['months_term'], $_POST['release_date'], $_POST['firstdue_date'], $_POST['maturity_date'], $_POST['balance'],
                                            $_POST['spotcash'], $_POST['total_amount'], $_POST['unrecovrd_cost'], $_POST['addon_cost'], $_POST['total_unrecovrd'],
                                            check_isempty($_POST['over_due']), check_isempty($_POST['past_due']), $_POST['category'], $branch_code, $_POST['remarks'], $_POST['gpm']);

            $repo_item_id = add_repo_item($_POST['InvoiceNo'], $repo_id, $item_row['stock_id'], $item_row['description'], $quantity, $unit_price, $_POST['unrecovrd_cost'],
                                            $item_row['lot_no'], $item_row['chassis_no'], $item_row['color_code']);

            add_stock_move(ST_RRREPO, $item_row['stock_id'], $repo_id, $loc_code, $_POST['repo_date'], $_POST['reference_no'], $quantity, $_POST['unrecovrd_cost'],
                            0, $item_row['lot_no'], $item_row['chassis_no'], $_POST['category'], $item_row['color_code'], 0, 0, "repo");
        
            //item serialize
            add_item_serialise(ST_RRREPO, $item_row['color_code'], $repo_id, $loc_code, $_POST['reference_no'], $quantity, $item_row['lot_no'], $repo_item_id, $item_row['chassis_no'], $item_row['description'], '');

            //for gl entry
            //Repossessed Inventory - debit
            $repo_invty_act =  get_repo_invty_act($_POST['category']);
            if(isset($repo_invty_act)){

                $GLtotal += add_gl_trans_customer(ST_RRREPO, $repo_id, $_POST['repo_date'], $repo_invty_act, 0, 0, $_POST['unrecovrd_cost'], $_POST['customername'], "Cannot insert a GL transaction for the repossessed inventory", 0, $_POST['customername'], $_POST['custname'], 0, $_POST['InvoiceNo']);
        
            }

            //deferred account - debit
            $dgp_account = $company_record["dgp_account"];
            if(isset($dgp_account)){

                $deferred_amount = ($_POST['balance'] - $_POST['unrecovrd_cost']);
                $GLtotal += add_gl_trans_customer(ST_RRREPO, $repo_id, $_POST['repo_date'], $dgp_account, 0, 0, $deferred_amount, $_POST['customername'], "Cannot insert a GL transaction for the deferred account", 0, $_POST['customername'], $_POST['custname'], 0, $_POST['InvoiceNo']);
            
            }

            //for A/R customer - credit
            if($_POST['months_term'] <= 3) {
                $debtors_account = $company_record["ar_reg_current_account"];
            }else{
                $debtors_account = $company_record["debtors_act"];
            }

            if(isset($debtors_account)){

                $GLtotal += add_gl_trans_customer(ST_RRREPO, $repo_id, $_POST['repo_date'], $debtors_account, 0, 0, -$_POST['balance'], $_POST['customername'], "Cannot insert a GL transaction for the A/R account", 0, $_POST['customername'], $_POST['custname'], 0, $_POST['InvoiceNo']);
                //$GLtotal += add_gl_trans(ST_RRREPO, $repo_id, $trans_date, $debtors_account, 0, 0, '', $_POST['balance'], get_customer_currency($_POST['customername']), PT_CUSTOMER, $_POST['customername'],
                                            //'', 0, '','',0, 0, 0);
            
            }

            //allocate A/R balance 
            add_cust_allocation($_POST['balance'], ST_RRREPO, $repo_id, $_POST['transtype'], $_POST['InvoiceNo'], $_POST['customername'], $_POST['repo_date']);
            update_debtor_trans_allocation($_POST['transtype'], $_POST['InvoiceNo'], $_POST['customername']);

            //update module to repo and status to close
            update_debtor_trans_to_repo("REPO", $_POST['repo_date'], $_POST['InvoiceNo'], $_POST['transtype']);
        }

        $dsplymsg = _("Repo transaction has been successfully entered...");

        echo '({"success":"true","message":"'.$dsplymsg.'"})';
    }else{
        echo '({"failure":"false","message":"'.$dsplymsg.'"})';
    }
    return;
}

page(_($help_context = "Receiving Report Repo"));

start_table(TABLESTYLE, "width='100%'");
   echo "<div id='ext-form'></div>";
   echo "<style type='text/css' media='screen'>
            .x-form-text-default.x-form-textarea {
                line-height: 20px;
                min-height: 30px;
            }
        </style>";
end_table();

end_form();
end_page();