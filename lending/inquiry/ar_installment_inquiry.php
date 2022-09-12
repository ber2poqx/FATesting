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
$page_security = 'SA_ARINVCINQ';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/lending/includes/db/ar_installment_db.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root ."/js/ar_installment_inquiry.js");

//----------------------------------------------: for grid js :---------------------------------------
if(isset($_GET['get_AmortSched'])){
    $result = get_AmortSched($_GET['trans_no'], $_GET['trans_type'],$_GET['debtor_no']);
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow["id"],
                               'debtor_no'=>$myrow["debtor_no"],
                               'trans_no'=>$myrow["trans_no"],
                               'trans_type'=>$myrow["trans_type"],
                               'month_no'=>$myrow["month_no"],
                               'date_due'=>date('m/d/Y',strtotime($myrow["date_due"])),
                               'weekday'=>$myrow["weekday"],
                               'principal_due'=>$myrow["principal_due"],
                               'principal_runbal'=>$myrow["principal_runbal"],
                               'total_principaldue'=>$myrow["total_principaldue"],
                               'total_runbal'=>$myrow["total_runbal"],
                               'interest_due'=>$myrow["interest_due"],
                               'interest_runbal'=>$myrow["interest_runbal"],
                               'status'=>$myrow["status"]
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return; 
}

if(isset($_GET['get_arinstallment'])){

    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
    
    $result = get_ar_installment($_GET['InvType'], $start, $limit, $_GET['query'], filter_var($_GET['showZeroB'], FILTER_VALIDATE_BOOLEAN), false, filter_var($_GET['showlend'], FILTER_VALIDATE_BOOLEAN));
    $total_result = get_ar_installment($_GET['InvType'], $start, $limit, $_GET['query'], filter_var($_GET['showZeroB'], FILTER_VALIDATE_BOOLEAN), true, filter_var($_GET['showlend'], FILTER_VALIDATE_BOOLEAN));
    $total = DB_num_rows($total_result);

    while ($myrow = db_fetch($result)) {

        switch ($myrow["type"]) {
            case 10:
                if($myrow["opening_balances"] == 1){
                    $type_module = "Sales Invoice Opening Balances";
                }else{
                    $type_module = "Sales Invoice Installment";
                }
                break;
            case 56:
                $type_module = "Sales Invoice Term Modification";
                break;
            case 57:
                $type_module = "Sales Invoice Repossessed";
                break;
            case 70:
                $type_module = "A/R Installment Lending";
                break;
            default:
                $type_module = "un·known";
          } 
        
        $status_array[] = array('trans_no'=>$myrow["trans_no"],
                               'type'=>$myrow["type"],
                               'debtor_no'=>$myrow["debtor_no"],
                               'debtor_ref'=>$myrow["debtor_ref"],
                               'debtor_name'=>htmlentities($myrow["name"]),
                               'Status'=>$myrow["status"],
                               'gender'=>$myrow["gender"],
                               'age'=>$myrow["age"],
                               'name_father'=>$myrow["name_father"],
                               'name_mother'=>$myrow["name_mother"],
                               'address'=>$myrow["address"],
                               'collector'=>$myrow["collectors_name"],
                               'phone'=>'',
                               'email'=>'',
                               'branch_no'=>$myrow["branch_code"],
                               'tran_date'=>date('m/d/Y',strtotime($myrow["tran_date"])),
                               'reference'=>$myrow["reference"],
                               'order'=>$myrow["order_"],
                               'ov_amount'=>$myrow["ov_amount"],
                               'trans_status'=>$myrow["status"],
                               'debtor_loan_id'=>$myrow["B.id"],
                               'invoice_ref_no'=>$myrow["invoice_ref_no"],
                               'delivery_ref_no'=>$myrow["delivery_ref_no"],
                               'invoice_date'=>date('m/d/Y',strtotime($myrow["invoice_date"])),
                               'orig_branch_code'=>$myrow["orig_branch_code"],
                               'invoice_type'=>$myrow["invoice_type"],
                               'installplcy_id'=>$myrow["installmentplcy_id"],
                               'months_term'=>$myrow["months_term"],
                               'rebate'=>$myrow["rebate"],
                               'fin_rate'=>$myrow["financing_rate"],
                               'firstdue_date'=>date('m/d/Y',strtotime($myrow["firstdue_date"])),
                               'maturity_date'=>date('m/d/Y',strtotime($myrow["maturity_date"])),
                               'outs_ar_amount'=>$myrow["outstanding_ar_amount"],
                               'ar_amount'=>$myrow["ar_amount"],
                               'ar_balance'=>($myrow["ov_amount"] - $myrow["alloc"]),
                               'lcp_amount'=>$myrow["lcp_amount"],
                               'dp_amount'=>$myrow["downpayment_amount"],
                               'amortn_amount'=>$myrow["amortization_amount"],
                               'total_amount'=>$myrow["total_amount"],
                               'category_id'=>$myrow["category_id"],
                               'category_desc'=>$myrow["description"],
                               'comments'=>$myrow["memo_"],
                               'pay_status'=>$myrow["payment_status"],
                               'module_type'=>$myrow["module_type"],
                               'unrecovered'=>$myrow["unrecovered_cost"],
                               'addon_amount'=>$myrow["addon_amount"],
                               'total_unrecovrd'=>$myrow["total_unrecovered"],
                               'repo_date'=>$myrow["repo_date"],
                               'past_due'=>$myrow["past_due"],
                               'over_due'=>$myrow["over_due"],
                               'repo_remark'=>$myrow["comments"],
                               'type_module'=>$type_module,
                               'term_mod_date'=>$myrow["term_mod_date"],
                               'amort_diff'=>$myrow["amort_diff"],
                               'months_paid'=>$myrow["months_paid"],
                               'amort_delay'=>$myrow["amort_delay"],
                               'adj_rate'=>$myrow["adj_rate"],
                               'oppnity_cost'=>$myrow["opportunity_cost"],
                               'amount_to_be_paid'=>$myrow["amount_to_be_paid"],
                               'Termremarks'=>$myrow["termremarks"],
                               'profit_margin'=>$myrow["profit_margin"],
                               'payment_loc'=>$myrow["payment_location"]
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return; 
}

if(isset($_GET['get_AREntry'])){
    $result = get_gl_trans($_GET['trans_type'], $_GET['trans_no']);
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        if ($myrow['amount'] > 0 ){
            $debit = $myrow['amount'];
            $credit = 0;
        }else {
            $debit = 0;
            $credit = -$myrow['amount'];
        }
        $status_array[] = array('id'=>$myrow["id"],
                               'trans_no'=>$myrow["type_no"],
                               'type'=>$myrow["type"],
                               'entry_date'=>date('m/d/Y',strtotime($myrow["tran_date"])),
                               'acct_code'=>$myrow["account"],
                               'descrption'=>$myrow["account_name"],
                               'debit_amount'=>$debit,
                               'credit_amount'=>$credit
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return; 
}

if(isset($_GET['get_deferedLed'])){
    $company_record = get_company_prefs();
    $balance = 0;

    $result = get_gl_deferred($_GET['type'], $_GET['trans_no'], $company_record["deferred_income_act"]);
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        if ($balance == 0 ){
            $balance = abs($myrow['amount']);
        }else {
            $balance -= abs($myrow['amount']);
        }
        $status_array[] = array('transno'=>$_GET['trans_no'],
                               'docno'=>$myrow["type_no"],
                               'type'=>$myrow["type"],
                               'tran_date'=>date('m/d/Y',strtotime($myrow["tran_date"])),
                               'debtor_no'=>$myrow["debtor_no"],
                               'name'=>$myrow["name"],
                               'account'=>$myrow["account_code"],
                               'account_name'=>$myrow["account_code"] .' - '. $myrow["account_name"],
                               'debit'=>abs($myrow["debit"]),
                               'credit'=>abs($myrow["credit"]),
                               'balance'=>$balance,
                               'reference'=>$myrow["ref_no"]
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return; 
}

if(isset($_GET['get_AmortLedger'])){
    $result = get_loan_amortization_ledger($_GET['type'], $_GET['trans_no']);
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        if ($myrow['amount'] > 0 ){
            $debit = $myrow['amount'];
            $credit = 0;
        }else {
            $debit = 0;
            $credit = -$myrow['amount'];
        }
        $status_array[] = array('loansched_id'=>$myrow["id"],
                               'trans_no'=>$myrow["trans_no"],
                               'debtor_no'=>$myrow["debtor_no"],
                               'ledger_id'=>$myrow["ledger_id"],
                               'month_no'=>$myrow["month_no"],
                               'due_date'=>date('m/d/Y',strtotime($myrow["date_due"])),
                               'date_paid'=>$myrow["date_paid"],
                               'amortization'=>$myrow["principal_due"],
                               'pay_ref_no'=>$myrow["reference"],
                               'payment_appld'=>$myrow["payment_applied"],
                               'rebate'=>$myrow["rebate"],
                               'penalty'=>$myrow["penalty"]
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return; 
}

if(isset($_GET['get_LedgerEntry'])){
    $result = get_ar_ledger_entries($_GET['type'], $_GET['trans_no']);
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        if ($myrow['amount'] > 0 ){
            $balance = abs($myrow['amount']);
        }else {
            $balance -= abs($myrow['amount']);
        }
        $status_array[] = array('transno'=>$myrow["trans_no"],
                               'docno'=>$myrow["type_no"],
                               'type'=>$myrow["type"],
                               'tran_date'=>date('m/d/Y',strtotime($myrow["tran_date"])),
                               'debtor_no'=>$myrow["debtor_no"],
                               'name'=>$myrow["name"],
                               'account'=>$myrow["account_code"],
                               'account_name'=>$myrow["account_name"],
                               'debit'=>abs($myrow["debit"]),
                               'credit'=>abs($myrow["credit"]),
                               'balance'=>$balance,
                               'reference'=>$myrow["ref_no"]
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return; 
}

if(isset($_GET['get_Item_details']))
{
    if($_GET['transtype'] == ST_SITERMMOD){
        $result = get_ar_item_detials($_GET['invoice_ref'], $_GET['transtype']);
    }else{
        $result = get_ar_item_detials($_GET['transNo'], $_GET['transtype']);
    }
    
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('stock_id'=>$myrow["stock_id"],
                               'description'=>$myrow["description"],
                               'qty'=>$myrow["quantity"],
                               'unit_price'=>$myrow["unit_price"],
                               'serial'=>$myrow["lot_no"],
                               'chasis'=>$myrow["chassis_no"],
                               'type'=>$_GET['transtype']
                            );
    }
    $replaced_items = get_replace_item($_GET['transNo']);
    if (db_num_rows($replaced_items) > 0) {
        while ($myrow = db_fetch($replaced_items)) {
            $status_array[] = array('stock_id'=>$myrow["stock_id"],
            'description'=>$myrow["description"],
            'qty'=>$myrow["quantity"],
            'unit_price'=>$myrow["unit_price"],
            'serial'=>$myrow["lot_no"],
            'chasis'=>$myrow["chassis_no"],
            'type'=>"XX"
         );
        }
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
//------------------------------------------------------------------------------------------------------
//simple_page_mode(true);
page(_($help_context = "A/R Installment Inquiry"), false, false, "", null);

start_table(TABLESTYLE, "width='100%'");
   echo "<div id='ARINQRY'></div>";
   echo "<style type='text/css' media='screen'>
            .x-form-text-default.x-form-textarea {
                line-height: 14px;
                min-height: 25px;
            }
        </style>";
end_table();
display_note(_(""), 0, 0, "class='overduefg'");
end_form();
end_page();