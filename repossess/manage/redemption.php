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
$page_security = 'SA_INTLRDEM';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/repossess/includes/repossessed.inc");

add_access_extensions();
simple_page_mode(true);
//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root ."/js/redemption.js");


//----------------------------------------------: for store js :---------------------------------------
if(isset($_GET['getReference'])){
    $reference = $Refs->get_next(ST_INTLRDEM);
    echo '({"success":"true","reference":"'.$reference.'"})';
    return;
}

if(isset($_GET['get_Customer']))
{
    $result = get_repo_customer($_GET['debtor_no']);
    $total = DB_num_rows($result);
    
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('debtor_no'=>$myrow["debtor_no"],
                                'debtor_ref'=>$myrow["debtor_ref"],
                                'name'=>$myrow["name"]
                            );
    }

    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}

if(isset($_GET['get_Item_details']))
{
    $result = get_repo_item_detials($_GET['repo_id']);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('stock_id'=>$myrow["stock_id"],
                               'description'=>$myrow["description"],
                               'qty'=>$myrow["qty"],
                               'unit_price'=>$myrow["unit_price"],
                               'serial_no'=>$myrow["serial_no"],
                               'chassis_no'=>$myrow["chassis_no"],
                               'color_code'=>$myrow["color_code"],
                            );
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
if(isset($_GET['get_RepoAccnt']))
{
    $result = get_repo_debtor_accounts($_GET['debtor_no']);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow["id"],
                               'type'=>$myrow["type"],
                               'repo_date'=>$myrow["repo_date"],
                               'repo_ref'=>$myrow["reference_no"],
                               'invoice_date'=>$myrow["invoice_date"],
                               'invoice_ref'=>$myrow["invoice_ref"],
                               'term'=>$myrow["term"],
                               'dp_amount'=>$myrow["downpayment"],
                               'out_ar'=>$myrow["outstanding_ar"],
                               'monthly_amort'=>$myrow["monthly_amount"],
                               'balance'=>$myrow["balance"],
                               'first_duedate'=>$myrow["firstdue_date"],
                               'maturity_date'=>$myrow["maturity_date"],
                               'lcp_amount'=>$myrow["lcp_amount"],
                               'unit_cost'=>$myrow["spot_cash_amount"],
                               'category_id'=>$myrow["category_id"],
                               'category_desc'=>$myrow["cat_desc"],
                               'addon_amount'=>$myrow["addon_amount"],
                               'unrecoverd'=>$myrow["unrecovered_cost"],
                               'totalunrecoverd'=>$myrow["total_unrecovered"],
                               'over_due'=>$myrow["over_due"],
                               'past_due'=>$myrow["past_due"],
                               'GPM'=>$myrow["gpm"],
                               'total_amount'=>$myrow["total_amount"],
                               'remarks'=>$myrow["comments"],
                               'ar_transno'=>$myrow["ar_trans_no"],
                               'ar_transtype'=>$myrow["ar_trans_type"]
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
if(isset($_GET['get_Redemption']))
{
    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);

    $result = get_demption_info($start, $limit, $_GET['query']);
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow["redem_no"],
                               'ar_trans_no'=>$myrow["ar_trans_no"],
                               'ar_trans_type'=>$myrow["ar_trans_type"],
                               'trans_date'=>$myrow["redem_date"],
                               'type'=>ST_INTLRDEM,
                               'repo_id'=>$myrow["id"],
                               'repo_type'=>$myrow["type"],
                               'repo_date'=>$myrow["repo_date"],
                               'reference_no'=>$myrow["redem_ref"],
                               'debtor_no'=>$myrow["debtor_no"],
                               'debtor_ref'=>$myrow["debtor_ref"],
                               'name'=>$myrow["name"],
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
                               'category_desc'=>$myrow["category"],
                               'branch_code'=>$myrow["branch_code"],
                               'comments'=>$myrow["redem_comment"],
                               'gpm'=>$myrow["gpm"],
                               'transfer_id'=>$myrow["transfer_id"],
                               'accu_amount'=>$myrow["accu_amount"],
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
    

    if (empty($_POST['transtype'])) {
        $InputError = 1;
        $dsplymsg = _('Some fields are empty or contain an improper value. Please reload the page and fill up the required field.');
    }
    if (empty($_POST['RepoNo'])) {
        $InputError = 1;
        $dsplymsg = _('Repo reference number must not be empty.');
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
    /*if (empty($_POST['reference_no'])) {
        $InputError = 1;
        $dsplymsg = _('Reference no must not be empty.');
    }*/
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
    if (empty($_POST['remarks'])) {
        $InputError = 1;
        $dsplymsg = _('remarks must not be empty.');
    }
    if (empty($_POST['addon_cost'])) {
        $_POST['addon_cost'] = 0;
    }
    if (empty($_POST['trans_date'])) {
        $InputError = 1;
        $dsplymsg = _('Transaction date must not be empty.');
    }else{
        $trans_date = date('Y-m-d',strtotime($_POST['trans_date']));
    }
    
    //check if entries balance
    if(($_POST['unrecovrd_cost'] + $_POST['Accuamount']) == $_POST['lcp_amount']){
        $InputError = 1;
        $dsplymsg = _('Entries Not Balance...');
    }

    $loc_code = get_default_location();
    if (empty($loc_code)) {
        $InputError = 1;
        $dsplymsg = _('cannot find default location. Please contact Automation team regarding this matter. Thank you...');
    }

    if ($InputError != 1){
        $GLtotal = 0;
        //$trans_date = date('m/d/Y');
        $company_record = get_company_prefs();
        $branch_code = get_company_pref("branch_code");
        $reference = $Refs->get_next(ST_INTLRDEM);

        $redem_id = add_repo_redemption(ST_INTLRDEM, $_POST['RepoNo'], $reference, $_POST['trans_date'], $_POST['remarks']);

        $item_row = db_fetch(get_repo_item_detials($_GET['repo_id']));

        add_stock_move(ST_INTLRDEM, $item_row['stock_id'], $redem_id, $loc_code, $_POST['trans_date'], $_POST['reference_no'], -$item_row['qty'], $_POST['unrecovrd_cost'],
                        0, $item_row['serial_no'], $item_row['chassis_no'], $_POST['category'], $item_row['color_code'], 0, 0, "item redem");

            //item serialize
            //add_item_serialise(ST_INTLRDEM, $item_row['color_code'], $repo_id, $loc_code, $_POST['reference_no'], $quantity, $serial, $repo_item_id, $item_row['chassis_no'], $item_row['description'], '');

            //for gl entry
            //Repossessed Inventory - debit
            $repo_invty_act =  get_repo_invty_act($_POST['category']);
            if(isset($repo_invty_act)){

                $GLtotal += add_gl_trans_customer(ST_INTLRDEM, $redem_id, $_POST['trans_date'], $repo_invty_act, 0, 0, -$_POST['unrecovrd_cost'], $_POST['customername'], "Cannot insert a GL transaction for the repossessed inventory", 0, $_POST['customername'], $_POST['custname'], 0, $_POST['base_transno']);
        
            }

            //deferred account - debit
            $dgp_account = $company_record["dgp_account"];
            if(isset($dgp_account)){

                $deferred_amount = ($_POST['balance'] - $_POST['unrecovrd_cost']);
                $GLtotal += add_gl_trans_customer(ST_INTLRDEM, $redem_id, $_POST['trans_date'], $dgp_account, 0, 0, -$deferred_amount, $_POST['customername'], "Cannot insert a GL transaction for the deferred account", 0, $_POST['customername'], $_POST['custname'], 0, $_POST['base_transno']);
            
            }

            //for A/R customer - credit
            if($_POST['months_term'] <= 3) {
                $debtors_account = $company_record["ar_reg_current_account"];
            }else{
                $debtors_account = $company_record["debtors_act"];
            }

            if(isset($debtors_account)){
                $GLtotal += add_gl_trans_customer(ST_INTLRDEM, $redem_id, $_POST['trans_date'], $debtors_account, 0, 0, $_POST['balance'], $_POST['customername'], "Cannot insert a GL transaction for the A/R account", 0, $_POST['customername'], $_POST['custname'], 0, $_POST['base_transno']);
            }

            //allocate A/R balance 
            delete_repo_alloc(ST_RRREPO, $_POST['RepoNo'], $_POST['balance']);
            update_debtor_trans_allocation($_POST['base_transtype'], $_POST['base_transno'], $_POST['customername']);

            //update module to repo and status to close
            update_redem_account($_POST['base_transno'], $_POST['base_transtype']);
            update__redem_repo_account($_POST['RepoNo'], $_POST['base_transno']);
            update__redem_repo_account_details($_POST['RepoNo'], $_POST['base_transno']);

        $dsplymsg = _("Redemption was created successfully...");

        echo '({"success":"true","message":"'.$dsplymsg.'"})';
    }else{
        echo '({"failure":"false","message":"'.$dsplymsg.'"})';
    }
    return;
}

page(_($help_context = "Installment Redemption"));

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