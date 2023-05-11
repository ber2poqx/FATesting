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

    if ($InputError != 1){
        $trans_date = sql2date(date('Y-m-d', strtotime(Today())));
        //loan ledger
        $result = get_loan_ledger_payment_per_transno($_GET['trans_type'], $_GET['trans_no']);
        while ($myrow = db_fetch($result)) {

            $invoice_trans_no = $myrow["trans_no"];
            $invoice_trans_type = $myrow["trans_type"];
            $debtor_no = $myrow["debtor_no"];

            if($myrow["payment_applied"] = $myrow["principal_due"]){
                //update schedule status to partial
                update_loan_sched_void($myrow["id"], $myrow["debtor_no"], $myrow["trans_no"], 'unpaid');
            }else{
                update_loan_sched_void($myrow["id"], $myrow["debtor_no"], $myrow["trans_no"], 'partial');
            }
        }

        if($invoice_trans_no != 0 && $invoice_trans_type != 0){
            //delete loan ledger
            delete_loan_ledger_void($invoice_trans_type, $invoice_trans_no, $_GET['trans_no']);
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
        update_void($_GET['void_id'], $_GET['trans_no'], 'Approved', $_GET['note']);

        $dsplymsg = _("Payment su approved.<b>".$reference."</b>");
        echo '({"success":"true","message":"'.$dsplymsg.'"})';

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