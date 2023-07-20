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
    $result = get_loan_ledger_payment_per_transno(trim($_GET['trans_type']), trim($_GET['trans_no']));
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
    $InputError = 0;
    $invoice_trans_no = 0;
    $invoice_trans_type = 0;
    $debtor_no = 0;
    $sum_alloc = 0;

    if (empty($_GET['trans_type']) || empty($_GET['trans_no']) || empty($_GET['void_id'])) {
        $InputError = 1;
        $dsplymsg = _('Some fields are empty or contain an improper value. Please reload the page and fill up the required field.');
    }
    //check if done voiding transaction
    $isdone = check_if_void_done($_GET['void_id']);
    if($isdone['void_status'] == 'Voided'){
        $InputError = 1;
        $dsplymsg = _('Error Payment already voided.');
    }
    //check if cash or dili
    $info = get_debtor_trans_all($_GET['trans_type'], $_GET['trans_no']);
    $banktrans_info = get_bank_trans_perno($_GET['trans_type'], $_GET['trans_no']);

    //$module_type = substr($info['module_type'], 0, 2);
    $module_type = $info['module_type'];
    $trans_date = sql2date(date('Y-m-d', strtotime(Today())));
    $debtor_no = $info["debtor_no"];

    $invoice_trans_no = $banktrans_info['masterfile'];
    $invoice_trans_type = $banktrans_info['masterfile_type'];

    if($invoice_trans_type == 0){
        $InputError = 1;
        $dsplymsg = _('Error. 0 trans type');
    }

    switch ($module_type) {
        case 'CI-CASH':
            if ($InputError != 1){
                //delete cust allocation
                if($invoice_trans_no != 0){
                    delete_cust_allocations_void($_GET['trans_type'], $_GET['trans_no'], $invoice_trans_no);
                }

                //get gltrans to reverse entry
                $result = get_gl_trans_void($_GET['trans_type'], $_GET['trans_no']);
                while ($myrow = db_fetch($result)) {
                    
                    if($myrow["amount"] < 0){
                        $amount = abs($myrow["amount"]);
                    }else{
                        $amount = -$myrow["amount"];
                    }
                    //reverse gl
                    add_gl_trans($myrow["type"], $myrow["type_no"], $trans_date, $myrow["account"], 0, 0, $myrow["memo_"].'-Cancelled', $amount,  null, $myrow["person_type_id"], $myrow["person_id"], "", 0, null, null, 0, $myrow["loan_trans_no"]);
                    update_gl_trans_void($myrow["counter"], $_GET['trans_type'], $_GET['trans_no'], $_GET['void_id']);
                }
    
                //re-allocate payment to
                if($invoice_trans_no != 0 && $invoice_trans_type != 0){
                    update_debtor_trans_allocation($invoice_trans_type, $invoice_trans_no, $debtor_no);
                }
    
                //update debtor trans
                update_debtor_trans_void($_GET['trans_type'], $_GET['trans_no'], 'Cancelled');
                update_void($_GET['void_id'], $_GET['trans_no'], 'Voided', $_GET['note']);
        
                // only add an entry if it's actually been voided
                add_audit_trail($_GET['trans_type'], $_GET['trans_no'], sql2date(date('Y-m-d', strtotime(Today()))), _("Voided.")."\n".$_GET['note']);
                //update debtor trans to open status
                if($invoice_trans_no != 0 && $invoice_trans_type != 0){
                    update_debtor_trans_void($invoice_trans_type, $invoice_trans_no, 'Open');
                }

                $dsplymsg = _("Payment was successfully voided.");
                echo '({"success":"true","message":"'.$dsplymsg.'"})';

            }else{
                echo '({"failure":"false","message":"'.$dsplymsg.'"})';
            }

            break;

        case 'NTFA-INTERB':
        case 'ALCN-INTERB':
        case 'ALCN-DP':
        case 'CR-ADJ':
        //case 'ALCN-ADJ':
        case 'CR-AMORT':
            //check kung last payment ba sa ledger
            $loaninfo = get_loan_ledger_payment_per_transno($_GET['trans_type'], $_GET['trans_no']);
            $loanrow = db_fetch($loaninfo);
            $chkresult = check_ledger_to_void($loanrow["trans_type"], $loanrow["trans_no"]);
            $total = DB_num_rows($chkresult);
            $chkres = db_fetch($result);

            if($total != 0){
                if($chkres['payment_trans_no'] != $_GET['trans_no']){
                    $InputError = 1;
                    $info = get_debtor_trans_all($_GET['trans_type'], $chkres['payment_trans_no']);
    
                    $dsplymsg = _('Error. Make sure to void this payment reference no. <u>'.$info['reference'].'</u> first. ');
                }
            }

            if ($InputError != 1){
                //loan ledger
                $result = get_loan_ledger_payment_per_transno($_GET['trans_type'], $_GET['trans_no']);
                while ($myrow = db_fetch($result)) {
        
                    $invoice_trans_no = $myrow["trans_no"];
                    $invoice_trans_type = $myrow["trans_type"];
        
                    if($myrow["payment_applied"] = $myrow["principal_due"]){
                        //update schedule status to partial
                        update_loan_sched_void($myrow["id"], $myrow["debtor_no"], $myrow["trans_no"], 'unpaid');
                    }else{
                        update_loan_sched_void($myrow["id"], $myrow["debtor_no"], $myrow["trans_no"], 'partial');
                    }
                }
        
                if($invoice_trans_no != 0 && $invoice_trans_type != 0){
                    //delete loan ledger
                    delete_loan_ledger_void($_GET['trans_type'], $invoice_trans_no, $_GET['trans_no']);
                    //delete cust allocation
                    delete_cust_allocations_void($_GET['trans_type'], $_GET['trans_no'], $invoice_trans_no);
                }
        
                //get gltrans to reverse entry
                $result = get_gl_trans_void($_GET['trans_type'], $_GET['trans_no']);
                while ($myrow = db_fetch($result)) {
                    if($myrow["amount"] < 0){
                        $amount = abs($myrow["amount"]);
                    }else{
                        $amount = -$myrow["amount"];
                    }
                    //reverse gl
                    add_gl_trans($myrow["type"], $myrow["type_no"], $trans_date, $myrow["account"], 0, 0, $myrow["memo_"].'-Cancelled', $amount,  null, $myrow["person_type_id"], $myrow["person_id"], "", 0, null, null, 0, $myrow["loan_trans_no"]);
                    update_gl_trans_void($myrow["counter"], $_GET['trans_type'], $_GET['trans_no'], $_GET['void_id']);
                }
        
                if($invoice_trans_no != 0 && $invoice_trans_type != 0 && $debtor_no != 0){
                    //re-allocate payment to
                    update_debtor_trans_allocation($invoice_trans_type, $invoice_trans_no, $debtor_no);  
                }
        
                //update debtor trans
                update_debtor_trans_void($_GET['trans_type'], $_GET['trans_no'], 'Cancelled');
                update_void($_GET['void_id'], $_GET['trans_no'], 'Voided', $_GET['note']);
        
                // only add an entry if it's actually been voided
                add_audit_trail($_GET['trans_type'], $_GET['trans_no'], sql2date(date('Y-m-d', strtotime(Today()))), _("Voided.")."\n".$_GET['note']);
        
                $dsplymsg = _("Payment was successfully voided.");
                echo '({"success":"true","message":"'.$dsplymsg.'"})';
        
            }else{
                echo '({"failure":"false","message":"'.$dsplymsg.'"})';
            }
            break;

        case 'CR-INTERB':
        case 'CR-DPWOSI':
            break;
    }
    return;
}
elseif(isset($_GET['declined']))
{
    if (empty($_GET['trans_type']) || empty($_GET['trans_no']) || empty($_GET['void_id'])) {
        $InputError = 1;
        $dsplymsg = _('Some fields are empty or contain an improper value. Please clear browser cache and reload the page again.');
    }
    if ($InputError !=1){
        //now for disapproved invoice
        disapproved_void($_GET['trans_type'], $_GET['trans_no'], $_GET['void_id']);

        $dsplymsg = _("Void request declined.");
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