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
$page_security = 'SA_INVCINQ';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/lending/includes/lending_cfunction.inc");

add_access_extensions();

//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root ."/js/void_payments.js");

//----------------------------------------------: for grid js :---------------------------------------
if(isset($_GET['get_voidPayment'])){

    $result = get_debtor_payment_per_transno($_GET['trans_type'], $_GET['trans_no']);
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        if($myrow["payment_type"] == "down"){
            $paymentType = "Down Payment";
        }elseif($myrow["payment_type"] == "amort"){
            $paymentType = "Amort Payment";
        }elseif($myrow["payment_type"] == "other"){
            $paymentType = "Other Payment";
        }elseif($myrow["payment_type"] == "adjmt"){
            $paymentType = "Adjustment";
        }
        $status_array[] = array('trans_no'=>$myrow["trans_no"],
                            'type'=>$myrow["type"],
                            'debtor_no'=>$myrow["debtor_no"],
                            'customer_code'=>$myrow["debtor_ref"],
                            'customer_name'=>htmlentities($myrow["name"]),
                            'tran_date'=>$myrow["tran_date"],
                            'reference'=>$myrow["reference"],
                            'recpt_no'=>$myrow["receipt_no"],
                            'total'=>$myrow["ov_amount"],
                            'payment_type'=>$paymentType,
                            'module_type'=>$myrow["module_type"],
                            'Bank_account'=>$myrow["bank_account_name"],
                            'remarks'=>$myrow["memo_"],
                            'prepared_by'=>$myrow["prepared_by"],
                            'check_by'=>$myrow["checked_by"],
                            'approved_by'=>$myrow["approved_by"],
                            'collect_type'=>$myrow["collection"],
                            'cashier'=>$myrow["cashier_user_id"],
                            'cashier_name'=>$myrow["real_name"]
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
if(isset($_GET['get_AmortLedger'])){
    $result = get_loan_ledger_payment_per_transno($_GET['trans_type'], $_GET['trans_no']);
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
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

//----------------------------------------------------: insert, update, delete :-------------------------------------------
if(isset($_GET['submit']))
{
    //initialise no input errors assumed initially before we proceed
    //0 is by default no errors
    $InputError = 0;
    
    if (empty($_POST['customercode'])) {
        $InputError = 1;
        $dsplymsg = _('Customer code must not be empty.');
    }
    if (empty($_POST['customername'])) {
        $InputError = 1;
        $dsplymsg = _('Customer name must not be empty.');
    }
    if (empty($_POST['amort_amount'])) {
        $InputError = 1;
        $dsplymsg = _('Amortization must not be empty.');
    }
    if (empty($_POST['ar_amount'])) {
        $InputError = 1;
        $dsplymsg = _('A/R amount must not be empty.');
    }
    if (empty($_POST['comments'])) {
        $InputError = 1;
        $dsplymsg = _('Description must not be empty.');
    }
    if (empty($_POST['months_term'])) {
        $InputError = 1;
        $dsplymsg = _('Term must not be empty.');
    }
    if (empty($_POST['total_amount'])) {
        $InputError = 1;
        $dsplymsg = _('Total amount must not be empty.');
    }
    if (empty($_POST['firstdue_date'])) {
        $InputError = 1;
        $dsplymsg = _('first due date must not be empty.');
    }
    if (empty($_POST['invoice_no'])) {
        $InputError = 1;
        $dsplymsg = _('invoice number must not be empty.');
    }
    if (empty($_POST['invoice_date'])) {
        $InputError = 1;
        $dsplymsg = _('invoice date must not be empty.');
    }
    /*if(check_customer_exist($_POST['invoice_no'], $_POST['id'])){
        $InputError = 1;
        $dsplymsg = _('Invoice number for this customer already exists.');
    }*/

    //get loans info
    $loanresult = get_debtor_per_transNo($_POST['id']);
    $loansrow = db_fetch($loanresult);
    
    $result = get_Approve_deptor_trans($_POST['id'], ST_ARINVCINSTLITM);
    $count = db_fetch($result);

    if ($count != 0 || $count != null) {
        $InputError = 1;
        $dsplymsg = _('This invoice has already been processed');
    }

    //first we need to get company prefference for entries side.
    $company_prefs = get_company_prefs();

    if (empty($company_prefs["default_loan_rcvble"]) || empty($company_prefs["default_int_income"])){
        $InputError = 1;
        $dsplymsg = _('Sorry, Account title was not found in GL Setup.');
    }
    
    if(empty($company_prefs["ap_account"])){
        $InputError = 1;
        $dsplymsg = _('Sorry, Account title was not found in Company preferences.');
    }

    //$trans_no = get_next_trans_no(ST_ARINVCINSTLITM);
    if (isset($_POST['customername']) && isset($_POST['invoice_no']) && isset($_POST['financing_rate']) &&  $InputError != 1){

        $BranchNo = get_newcust_branch($_POST['customername'], $_POST['customercode']);
        $reference = $Refs->get_next(ST_ARINVCINSTLITM);

        $approved_date = date("Y-m-d", strtotime($_POST['invoice_date'])); //date("Y-m-d");
        $firstdue_date = date("Y-m-d", strtotime($_POST['firstdue_date']));
        $maturity_date = date("Y-m-d", strtotime($_POST['maturity_date']));
        $invoice_date = date("Y-m-d", strtotime($_POST['invoice_date']));

        $conn = $db_connections[user_company()];
        //$reference - tanggal ug replace sa original invoice ref no kay nag term mode as requested by albert
        $trans_no = write_customer_trans(ST_ARINVCINSTLITM, 0, $_POST['customername'], check_isempty($BranchNo['branch_code']), date("m/d/Y", strtotime($approved_date)), $_POST['invoice_no'], 
                            check_isempty($_POST['total_amount']), 0, 0, 0, 0, $loansrow["tpe"], check_isempty($_POST['id']), 0, date("m/d/Y", strtotime($approved_date)), 0, 0, 0, 0, $loansrow["payment_terms"], 0, 0);

        //detailed A/R info
        if(isset($trans_no)){

            add_ar_installment($trans_no, $_POST['customername'], $_POST['invoice_no'], $reference, $invoice_date, $_POST['branch_code'],
                            $_POST['invoice_type'], check_isempty($_POST['policy_id']), $_POST['months_term'], $_POST['rebate'],
                            $_POST['financing_rate'], $firstdue_date, $maturity_date, $_POST['outs_ar_amount'], $_POST['ar_amount'], $_POST['lcp_amount'],
                            $_POST['dp_amount'], $_POST['amort_amount'], $_POST['total_amount'], check_isempty($_POST['category_id']), check_isempty($_POST['delivery_no']),
                            'unpaid', $loansrow["warranty_code"], $loansrow["fsc_series"], $loansrow["co_maker"], $loansrow["discount_downpayment"], $loansrow["discount_downpayment2"],
                            $loansrow["deferred_gross_profit"], $loansrow["profit_margin"], $loansrow["ref_no"], $loansrow["old_trans_no"]);
                            
            add_comments(ST_ARINVCINSTLITM, $trans_no, date("m/d/Y", strtotime($approved_date)), $_POST['comments']);

            //for a/r items
            $result = get_debtor_items($_POST['id']);
            $total = DB_num_rows($result);
            while ($myrow = db_fetch($result)) {
                add_ar_item_details(ST_ARINVCINSTLITM, $trans_no, $myrow["stock_id"], $myrow["description"] , $myrow["quantity"], check_isempty($myrow["unit_price"]), check_isempty($myrow["unit_tax"]),
                                    0, check_isempty($myrow["standard_cost"]), 0, $myrow["lot_no"], $myrow["chassis_no"], $myrow["color_code"], $myrow["item_type"], $myrow["discount1"], $myrow["discount2"],
                                    $myrow["qty_replace"], $myrow["smi"], $myrow["incentives"], $myrow["qty_done"]);
            }

            //now for amortization schedule
            //get array amortization schedule
            if(isset($_POST['months_term']) && isset($_POST['ar_amount']) && isset($_POST['amort_amount']) && isset($_POST['dp_amount']) && isset($approved_date)){
                $array_result = array_Amortization($_POST['months_term'], $_POST['amort_amount'], $_POST['outs_ar_amount'], $_POST['firstdue_date'],
                                                $approved_date, $_POST['ar_amount'], $_POST['dp_amount']);

                foreach($array_result as $item) {

                    //insert amort schedule
                    $datedue = date("Y-m-d", strtotime($item["datedue"]));

                    if($item["amortization"] == $_POST['dp_amount']){
                        $status = "paid";
                    }else{
                        $status = "unpaid";
                    }
                    add_loan_schedule(ST_ARINVCINSTLITM, $trans_no, $_POST['customername'], check_isempty($item["no"]), $datedue, $item["weekday"], 
                                check_isempty($item["amortization"]), check_isempty($item["runbalance"]), check_isempty($item["totalamort"]), check_isempty($item["runtotalamort"]), 0, 0, $status);
                }
            }

            //now for gl entries
            $unearned_amount = 0;
            if(isset($_POST['outs_ar_amount']) &&  $_POST['outs_ar_amount'] != 0){
                //AR amount = outstanding ar amount "/" or // AR amount - down-payment amount
                $unearned_amount = add_gl_trans_customer(ST_ARINVCINSTLITM, $trans_no, date("m/d/Y", strtotime($approved_date)), $company_prefs["default_loan_rcvble"], 0, 0,
                             $_POST['outs_ar_amount'], $_POST['customername'], "The outstanding a/r amount GL posting could not be inserted");
                
            }
            if(isset($_POST['lcp_amount']) &&  $_POST['lcp_amount'] != 0){
                //AP - DES CAPITAL account = LCP amount - Down-payment amount
                $ap_capital = ($_POST['lcp_amount'] - $_POST['dp_amount']);

                add_gl_trans_customer(ST_ARINVCINSTLITM, $trans_no, date("m/d/Y", strtotime($approved_date)), $company_prefs["ap_account"], 0, 0,
                                -$ap_capital, $_POST['customername'], "The a/p amount GL posting could not be inserted");
                
            }
            if(isset($_POST['ar_amount']) &&  $_POST['ar_amount'] != 0){
                //DGP = AR amount - LCP amount / or // total financing charge + rebate
                $DGP_amount = ($_POST['ar_amount'] - $_POST['lcp_amount']);
                add_gl_trans_customer(ST_ARINVCINSTLITM, $trans_no, date("m/d/Y", strtotime($approved_date)), $company_prefs["deferred_income_act"], 0, 0,
                                -$DGP_amount, $_POST['customername'], "The unearned interest amount GL posting could not be inserted");
            }

            //pay to branch
            interbranch_send_payment_add($_POST['branch_code'], $_POST['descustcode'], $_POST['descustname'], date("Y-m-d", strtotime($approved_date)), $reference, $ap_capital,
            $_POST['comments'], $_SESSION["wa_current_user"]->name, $conn['branch_code'], $trans_no, ST_ARINVCINSTLITM, $_POST['branch_code'], 1);

            $Refs->save(ST_ARINVCINSTLITM, $trans_no, $reference, null);
            Update_debtor_trans_status(ST_ARINVCINSTLITM, $trans_no, "unpaid", null);
            Update_debtor_trans_status(ST_SALESINVOICE, $_POST['id'], "Approved", $_POST['branch_code']);

            //incoming_invoice_logs(check_isempty($_POST['invoice_no']), $_POST['descustcode'], $approved_date, $_SESSION["wa_current_user"]->name, 1);
            add_audit_trail(ST_ARINVCINSTLITM, $trans_no, date("m/d/Y", strtotime($approved_date)),'Approved A/R Invoice Installment from '.$_POST['branch_code']);
            
            $dsplymsg = _("The A/R invoice installment has been approved. <br />Reference number: <b>".$reference."</b>");
            echo '({"success":"true","message":"'.$dsplymsg.'"})';

        }else{

            $dsplymsg = _("Failed to write/insert debtors transaction.");
            //echo '({"failure":"true","message":"'.$loan_account.'"})';
            echo '({"failure":"false","message":"'.$dsplymsg.'"})';
        }
    }else{

        echo '({"failure":"false","message":"'.$dsplymsg.'"})';
    }
    return;

}elseif(isset($_GET['DisApproved']))
{
    if (empty($_GET['id'])){
        $InputError = 1;
        $dsplymsg = _('Some fields are empty. Please reload the page and try again. Thank you...');
    }
    if (empty($_GET['branch_code'])){
        $InputError = 1;
        $dsplymsg = _('Some fields are empty. Please reload the page and try again. Thank you...');
    }
    if ($InputError !=1){
        //now for disapproved invoice
        $approved_date = date("Y-m-d");
        //update_ar_logs(check_isempty($_GET['invid']), $_GET['CCODE'], $approved_date, $_SESSION["wa_current_user"]->user, 2);
        Update_debtor_trans_status(ST_SALESINVOICE, $_GET['id'], "Disapproved", $_GET['branch_code']);

        $dsplymsg = _("Invoice has been rejected.");
        echo '({"success":"true","message":"'.$dsplymsg.'"})';
    }else{
        echo '({"success":"false","message":"'.$dsplymsg.'"})';
    }

    return;
}

//------------------------------------------------------------------------------------------------------
//simple_page_mode(true);
page(_($help_context = "Void Payments"), false, false, "", null);

start_table(TABLESTYLE, "width='100%'");
   echo "<div id='voidform'></div>";
end_table();
display_note(_(""), 0, 0, "class='overduefg'");
end_form();
end_page();