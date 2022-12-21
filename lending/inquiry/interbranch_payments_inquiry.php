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
$page_security = 'SA_INTRBPAYINQ';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/lending/includes/lending_cfunction.inc");

simple_page_mode(true);
//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
if($_GET['type']=='aloneinb'){
    add_js_ufile($path_to_root ."/js/interbranch_paysalone.js");
}else{
    add_js_ufile($path_to_root ."/js/interbranch_payments_inquiry.js");
}

//----------------------------------------------: for store js :---------------------------------------
if(isset($_GET['getReference'])){
    if($_GET['getReference'] == 'paysalone'){
        $reference = $Refs->get_next(ST_CUSTPAYMENT, GetReferenceID('NTFA'), array('date' => Today()), true, ST_CUSTPAYMENT);
    }else{
        $reference = $Refs->get_next(ST_CUSTPAYMENT, GetReferenceID('ALCN'), array('date' => Today()), true, ST_CUSTPAYMENT);
    }
    echo '({"success":"true","reference":"'.$reference.'"})';
    return;
}
if(isset($_GET['getbranch'])){
    global $db_connections;
    $conn = $db_connections;
    $total = count($conn);

    $status_array[] = array('id'=>"zHun",
                            'name'=>"All",
                            'area'=>"All");
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
if(isset($_GET['get_Customer']))
{
    $result = get_all_customer();
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        if($myrow["debtor_no"] == $_GET['debtor_id'] || $myrow["name"] == $_GET['name']){
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
    $result = get_invoice_per_customer($_GET['debtor_id'], $_GET['tag']);
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow["trans_no"],
                               'name'=>($myrow["reference"].' > '.$myrow["category"].' > '.$myrow["stock_id"].' > '.$myrow["itemdesc"]),
                               'type'=>$myrow["type"],
                               'status'=>$myrow["status"]
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_PaymentType']))
{
    $status_array[] = array('id'=>"down",
                            'name'=>"Down Payment"
                        );
    $status_array[] = array('id'=>"amort",
                            'name'=>"Amort Payment"
                        );

    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_Item_details']))
{
    $result = get_item_detials($_GET['transNo'], $_GET['transtype']);
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('stock_id'=>$myrow["stock_id"],
                               'description'=>$myrow["description"],
                               'qty'=>$myrow["quantity"],
                               'unit_price'=>$myrow["unit_price"],
                               'serial'=>$myrow["lot_no"],
                               'chasis'=>$myrow["chassis_no"]
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_cashierPrep']))
{
    $user_role = check_user_role($_SESSION["wa_current_user"]->username);
	if($user_role == 15){
		$cashier = $_SESSION["wa_current_user"]->name;
	}else{
        $result = check_operator_builder($_SESSION["wa_current_user"]->username);
        if(DB_num_rows($result) != 0){
            $user_row = db_fetch($result);
            $cashier = $user_row["cashier_name"];
        }else{
            $cashier = $_SESSION["wa_current_user"]->name;
        }
    }

    $prepare = $_SESSION["wa_current_user"]->name;

    echo '({"success":"true","cashier":"'.$cashier.'","prepare":"'.$prepare.'"})';
    return;
}
if(isset($_GET['get_CashierTellerCol']))
{
    $user_role = check_user_role($_SESSION["wa_current_user"]->username);
    $result = get_casheirCol($_SESSION["wa_current_user"]->username);

    if($user_role == 15 || $user_role == 11){
        while ($user_row = db_fetch($result)) {
            if(strtoupper($_SESSION["wa_current_user"]->username) == strtoupper($user_row["user_id"])){
                $status_array[] = array('id'=>$user_row["id"],
                                            'name'=> $user_row["real_name"],
                                            'type'=>$user_row["role_id"]
                                        );
            }
        }
    }else{
        if($user_role == 2){
            while ($user_row = db_fetch($result)) {
                $status_array[] = array('id'=>$user_row["id"],
                                            'name'=> $user_row["real_name"],
                                            'type'=>$user_row["role_id"]
                                        );
            }
        }else{
            $op_result = check_operator_builder($_SESSION["wa_current_user"]->username);
            if(DB_num_rows($op_result) != 0){
                $op_user_row = db_fetch($op_result);

                $status_array[] = array('id'=>$op_user_row["usersid"],
                                            'name'=> $op_user_row["cashier_name"],
                                            'type'=>$op_user_row["role_id"]
                                        );
            }else{
                $row = get_user($_SESSION["wa_current_user"]->user);
                $status_array[] = array('id'=>$row["id"],
                                            'name'=> $row["real_name"],
                                            'type'=>$row["role_id"]
                                        );
            }
        }
    }

    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_CollectionType']))
{
    $result = get_collection_types(false);
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
            $status_array[] = array('id'=>$myrow["collect_id"],
                                    'name'=>$myrow["collection"]
                                );
    }

    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_aloc']))
{
    $PartialPayment = $Penalty = $RebateAmount = $TotalBalance = $DP_Discount = 0;
    
    if($_GET['islending'] ==  1){
        $result = get_ARinvoice_debtor_trans($_GET['transNo'], $_GET['debtor_no']);
        $invoicerow = db_fetch($result);

        $status_array[] = array('loansched_id'=>0,
                                'debtor_id'=>$_GET['debtor_no'],
                                'trans_no'=>$_GET['transNo'],
                                'date_due'=>date('m-d-Y', strtotime($invoicerow["due_date"])),
                                'maturity_date'=>$invoicerow["maturity_date"],
                                'mosterm'=>$invoicerow["months_term"],
                                'amortization'=>$invoicerow["amortization_amount"],
                                'ar_due'=>$invoicerow["outstanding_ar_amount"],
                                'rebate'=>$RebateAmount,
                                'penalty'=>$Penalty,
                                'penaltyBal'=>$Penalty,
                                'partialpayment'=>$PartialPayment,
                                'totalpayment'=>$invoicerow["outstanding_ar_amount"],
                                'alloc_amount'=>0, //$alloc_amount,
                                'grossPM'=>$invoicerow["profit_margin"]
                            );
    }else{
        $result = get_deptor_loan_schedule($_GET['transNo'], $_GET['debtor_no'], $_GET['transtype'], false);
        
        $total = DB_num_rows($result);
        $absAmount = 0;
        $schedrow = db_fetch($result);

        $mos = mos_interval($schedrow["date_due"], $_GET['transdate']);
        if($mos > 0){
            $Totalpayment = ($schedrow["amortization_amount"] * $mos);
        }else{
            $Totalpayment = $schedrow["amortization_amount"];
        }

        $TotalRunBal = $schedrow["total_runbal"];
        $PenaltyBal = $schedrow["penalty_balance"];
        $alloc_amount = $_GET['alloc_amount'];

        $payAppliedInfo = get_payment_appliedInfo($_GET['transtype'],$_GET['transNo'], $schedrow["loansched_id"]);
        $paymentAppld = get_payment_appliedSUM($_GET['transtype'],$_GET['transNo'], $schedrow["loansched_id"]);

        if($paymentAppld != 0){
            if($schedrow["amortization_amount"] > $paymentAppld){
                $PartialPayment = $paymentAppld;
                $TotalRunBal -= $PartialPayment;
                $Totalpayment -= $PartialPayment;
            }
        }
        //check if can avail rebate
        $RebateAmount = GetRebate($_GET['transdate'], $schedrow["date_due"], $schedrow["rebate"]);
        if($RebateAmount != 0){
            $Totalpayment -= $RebateAmount;
        }
        //for penalty
        if(date('Y-m-d', strtotime($schedrow['maturity_date'])) < date('Y-m-d', strtotime($_GET['transdate']))){
            $MonthNo = CalculateMonthsPastDue($_GET['transdate'], $schedrow["date_due"], $payAppliedInfo['date_paid']);
            if($MonthNo != 0){
                $TotalBalance = $TotalRunBal;
                $Penalty = CalculatePenalty($_GET['transdate'], $schedrow["date_due"], $payAppliedInfo['date_paid'], $_GET['transNo'], $schedrow["amortization_amount"], $TotalBalance, $MonthNo, 'PASTDUE', false);
            }
        }else{
            $MonthNo = CalcMonthsDue_pnlty($_GET['transdate'], $schedrow["date_due"], $schedrow["maturity_date"]);
            if($MonthNo != 0){
                $TotalBalance = $Totalpayment;
                $count=0;
                for ($MonthNo; $MonthNo >= 1; $MonthNo--) {
                    //$Penalty += CalculatePenalty($_GET['transdate'], $schedrow["maturity_date"], $schedrow["amortization_amount"], $TotalBalance, $MonthNo, 'DUE', false);
                    //echo $MonthNo."</br>";
                    if($count == 0){
                        $due = ($schedrow["amortization_amount"] - $PartialPayment);
                    }else{
                        $due = $schedrow["amortization_amount"];
                    }
                    $Penalty += per_Penalty($MonthNo, $due);
                    $count++;
                }
            }
        }
        $Penalty += $PenaltyBal;
        $Totalpayment += $Penalty;
        $grossPM = $schedrow["profit_margin"];

        $status_array[] = array('loansched_id'=>$schedrow["loansched_id"],
                                'debtor_id'=>$_GET['debtor_no'],
                                'trans_no'=>$_GET['transNo'],
                                'date_due'=>date('m-d-Y', strtotime($schedrow["date_due"])),
                                'maturity_date'=>$schedrow["maturity_date"],
                                'mosterm'=>$schedrow["month_no"],
                                'amortization'=>$schedrow["amortization_amount"],
                                'ar_due'=>$TotalRunBal,
                                'rebate'=>$RebateAmount,
                                'penalty'=>$Penalty,
                                'penaltyBal'=>$PenaltyBal,
                                'partialpayment'=>$PartialPayment,
                                'totalpayment'=>$Totalpayment,
                                'alloc_amount'=>0, //$alloc_amount,
                                'grossPM'=>$grossPM
                            );
    }

    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}

if(isset($_GET['get_incoming_interb'])){

    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);

    if($_GET['islending'] == 'true'){
        $islending=1;
    }else{
        $islending=0;
    }
    $result = get_incoming_interb($_GET['status'], $start, $limit, $_GET['branch'], $_GET['query'], $islending);
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $branch = get_branch_info($myrow['branch_code_from']);
        $status_array[] = array('id'=>$myrow['id'],
                                'branch_code_from'=>$myrow['branch_code_from'],
                                'branch_name'=>array_column($branch, 'name'),
                                'branch_gl_code'=>array_column($branch, 'gl_account'),
                                'debtor_id'=>$myrow['debtor_no'],
                                'debtor_ref'=>$myrow['debtor_ref'],
                                'debtor_name'=>$myrow['debtor_name'],
                                'trans_date'=>$myrow['trans_date'],
                                'ref_no'=>$myrow['ref_no'],
                                'amount'=>$myrow['amount'],
                                'remarks'=>$myrow['remarks'],
                                'prepared_by'=>$myrow['prepared_by'],
                                'status'=>$myrow['status'],
                                'approved_by'=>$myrow['approved_by'],
                                'type'=>$myrow['type']
                            );
     }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';

    set_global_connection();
    return;
}

if(isset($_GET['submit']))
{
    //initialise no input errors assumed initially before we proceed
    //0 is by default no errors
    $InputError = 0;
    
    if (empty($_POST['transtype']) || empty($_POST['ref_no']) || empty($_POST['debit_acct']) || empty($_POST['syspk'])) {
        $InputError = 1;
        $dsplymsg = _('Some fields are empty or contain an improper value. Please reload the page and fill up the required field.');
    }
    if (empty($_POST['pay_type'])) {
        $InputError = 1;
        $dsplymsg = _('Some fields are empty or contain an improper value. Please reload the page and fill up the required field.');
    }
    if (empty($_POST['moduletype'])) {
        $InputError = 1;
        $dsplymsg = _('Some fields are empty or contain an improper value. Please reload the page and fill up the required field.');
    }
    /*if (empty($_POST['islending'])) {
        $InputError = 1;
        $dsplymsg = _('Some fields are empty or contain an improper value. Please reload the page and fill up the required field.');
    }*/
    if (empty($_POST['customercode'])) {
        $InputError = 1;
        $dsplymsg = _('Customer code must not be empty.');
    }
    if (empty($_POST['customername'])) {
        $InputError = 1;
        $dsplymsg = _('Customer name must not be empty.');
    }
    if (empty($_POST['trans_date'])) {
        $InputError = 1;
        $dsplymsg = _('Transaction date must not be empty.');
    }else{
        $trans_date = date('Y-m-d',strtotime($_POST['trans_date']));
    }
    if (empty($_POST['InvoiceNo'])) {
        $InputError = 1;
        $dsplymsg = _('Invoice number must not be empty.');
    }
    if (empty($_POST['total_amount'])) {
        $InputError = 1;
        $dsplymsg = _('Total amount must not be empty.');
    }
    if ($_POST['total_amount'] == 0) {
        $InputError = 1;
        $dsplymsg = _('Total amount must be greater than 0.');
    }
    if (empty($_POST['tenderd_amount'])) {
        $InputError = 1;
        $dsplymsg = _('Allocate amount must not be empty.');
    }
    if ($_POST['tenderd_amount'] == 0) {
        $InputError = 1;
        $dsplymsg = _('Allocate amount must be greater than 0.');
    }
    if (empty($_POST['remarks'])) {
        $InputError = 1;
        $dsplymsg = _('remarks must not be empty.');
    }
    if (empty($_POST['paymentType'])) {
        $InputError = 1;
        $dsplymsg = _('Payment type must not be empty.');
    }
    /*if (empty($_POST['cashier'])) {
        $InputError = 1;
        $dsplymsg = _('Cashier must not be empty.');
    }*/
    if (empty($_POST['preparedby'])) {
        $InputError = 1;
        $dsplymsg = _('Prepared by must not be empty.');
    }

    $DataOnGrid = stripslashes(html_entity_decode($_POST['DataOnGrid']));
    $objDataGrid = json_decode($DataOnGrid, true);
    
    //var_dump($objDataGrid);
    if (count($objDataGrid) == 0){
        $InputError = 1;
        $dsplymsg = _('Credit amount must not be empty! Please try again.');
    }
    
    if ($InputError != 1){
        
        begin_transaction();
        $BranchNo = get_newcust_branch($_POST['customername'], $_POST['customercode']);
        $debtor_loans = get_debtor_loans_info($_POST['InvoiceNo'], $_POST['customername']);

        foreach($objDataGrid as $value=>$data) {
            $Loansched_ID = $data['loansched_id'];
            $debtor_id = $data['debtor_id'];
            $trans_no = $data['trans_no'];
            $date_due = $data['date_due'];
            $maturity_date = $data['maturity_date'];
            $mosterm = $data['mosterm'];
            $ar_due = $data['ar_due'];
            $total_rebate = $data['rebate'];
            $total_penalty = $data['penalty'];
            $penaltyBal = $data['penaltyBal'];
            $partialpayment = $data['partialpayment'];
            $amortization = $data['amortization'];
            $Alloc_Amount = $data['alloc_amount'];
            $dp_discount = $data['dp_discount'];
            $grossPM = $data['grossPM'];

            set_global_connection();
            
            $GLPenalty = $GLRebate = $GLtotal = $partialpay = $allocatedAmount = 0;
            
            $partialpay = $partialpayment;
            $tenderd_amount = $_POST['tenderd_amount'];

            $branch_data = get_branch_accounts($BranchNo['branch_code']);
            $company_record = get_company_prefs();

            if($_POST['islending'] == 1){
                //from lending
                $payment_no = write_customer_trans(ST_CUSTPAYMENT, 0, $_POST['customername'], check_isempty($BranchNo['branch_code']), $_POST['trans_date'], $_POST['ref_no'],
                $_POST['tenderd_amount'], 0 , 0, 0, 0, 0, 0, 0, null, 0, 0, 0, 0, null, 0, 0, 0, $_POST['paymentType'], $_POST['collectType'], $_POST['moduletype']);
                
                add_bank_trans(ST_CUSTPAYMENT, $payment_no, 0, $_POST['ref_no'], $_POST['trans_date'], $_POST['tenderd_amount'], PT_CUSTOMER, $_POST['customername'],
                    0, $_POST['pay_type'], '0000-00-00', 0, null, $_POST['InvoiceNo'], $_POST['syspk'], $_POST['preparedby']);

                add_comments(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $_POST['remarks']);

                //AR-lending -> Debit
                $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $company_record["arlending_account"], 0, 0, ($_POST['tenderd_amount']), $_POST['customername'], "Cannot insert a GL transaction for the debtors account credit", 0, null, null, 0, $_POST['InvoiceNo']);
                //AR-cash Sales -> Credit
                $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $company_record["ar_cash_sales_account"], 0, 0, -($_POST['tenderd_amount']), $_POST['customername'], "Cannot insert a GL transaction for the debtors account credit", 0, null, null, 0, $_POST['InvoiceNo']);

                //allocate payment to
                add_cust_allocation($_POST['tenderd_amount'], ST_CUSTPAYMENT, $payment_no, $_POST['transtype'], $_POST['InvoiceNo'], $_POST['customername'], $_POST['trans_date']);
                update_debtor_trans_allocation($_POST['transtype'], $_POST['InvoiceNo'], $_POST['customername']);
                
                update_status_debtor_trans($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype'], "fully-paid");
                update_status_debtor_loans($_POST['InvoiceNo'], $_POST['customername'], "fully-paid");

                update_status_interbranch_trans($_POST['syspk'], $_SESSION["wa_current_user"]->username, 'approved', $payment_no, ST_CUSTPAYMENT, null);

            }else{
                $payment_no = write_customer_trans(ST_CUSTPAYMENT, 0, $_POST['customername'], check_isempty($BranchNo['branch_code']), $_POST['trans_date'], $_POST['ref_no'],
                                                    $_POST['tenderd_amount'], 0 , 0, 0, 0, 0, 0, 0, null, 0, 0, 0, 0, null, 0, 0, 0, $_POST['paymentType'], $_POST['collectType'], $_POST['moduletype']);

                add_bank_trans(ST_CUSTPAYMENT, $payment_no, 0, $_POST['ref_no'], $_POST['trans_date'], $_POST['tenderd_amount'], PT_CUSTOMER, $_POST['customername'],
                                /*$_POST['cashier']*/ 0, $_POST['pay_type'], '0000-00-00', 0, null, $_POST['InvoiceNo'], $_POST['syspk'], $_POST['preparedby']);

                add_comments(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $_POST['remarks']);

                /* Bank account entry first */
                $GLtotal += add_gl_trans(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $_POST['debit_acct'], 0, 0, '', $_POST['tenderd_amount'], null, PT_CUSTOMER, $_POST['customername'], "", 0, null, null, 0, $_POST['InvoiceNo']);

                $result = get_loan_schedule($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype']);
                while ($myrow = db_fetch($result)) {
                    if($tenderd_amount > 0){
                        if($total_penalty > 0){
                            //penalty
                            if($penaltyBal != 0){
                                $tenderd_amount -= $myrow["penalty_balance"];
                                $total_penalty -= $myrow["penalty_balance"];

                                if($tenderd_amount > 0){
                                    add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $myrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, 0, $myrow["penalty_balance"], 0, 0, $trans_date, $payment_no);
                                    update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "unpaid", 0, "paid");
                                    
                                    $penaltyBal = 0;
                                    $GLPenalty += $myrow["penalty_balance"];
                                }else{
                                    $nextPenaltyBal = ($myrow["penalty_balance"] - $_POST['tenderd_amount']);
                                    add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $myrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, 0, $_POST['tenderd_amount'], 0, 0, $trans_date, $payment_no);
                                    update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "unpaid", $nextPenaltyBal, "partial");
                                    
                                    $penaltyBal = $nextPenaltyBal;
                                    $nextPenaltyBal = $tenderd_amount = 0;
                                    $GLPenalty += $_POST['tenderd_amount'];
                                }

                            }else{

                                if($tenderd_amount > $total_penalty){
                                    $tenderd_amount -= $total_penalty;
                                    $bal_penalty = $total_penalty;

                                    //set penalty status paid in table schedule
                                    $pnty_result = get_loan_schedule_penalty($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype']);
                                    while ($pntyrow = db_fetch($pnty_result)){
                                        $MonthNo = CalcMonthsDue_pnlty($_POST['trans_date'], $pntyrow["date_due"], $maturity_date);
                                        if($MonthNo != 0){
                                            $penalty = per_Penalty($MonthNo, ($pntyrow["principal_due"]- $partialpay));
                                            
                                            if($penalty > 0){
                                                add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $pntyrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, 0, $penalty, 0, 0, $trans_date, $payment_no);
                                                update_loan_schedule($pntyrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "unpaid", 0, "paid");
                                                $total_penalty -= $penalty;
                                                $GLPenalty += $penalty;
                                                $partialpay = 0;
                                            }
                                        }
                                        if($total_penalty <= 0){
                                            $total_penalty = $penalty = 0;
                                            break;
                                        }
                                    }
                                    $bal_penalty = $total_penalty = 0;
                                }else{
                                    //dako ang $total_penalty kaysa $tenderd_amount
                                    $total_penalty -= $tenderd_amount;
                                    $bal_tenderd_amount = $tenderd_amount;

                                    //set penalty status paid in table schedule
                                    $pnty_result = get_loan_schedule_penalty($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype']);
                                    while ($pntyrow = db_fetch($pnty_result)){
                                        $MonthNo = CalcMonthsDue_pnlty($_POST['trans_date'], $pntyrow["date_due"], $maturity_date);
                                        if($MonthNo != 0){
                                            $penalty = per_Penalty($MonthNo, ($pntyrow["principal_due"] - $partialpay));
                                            $bal_tenderd_amount -= $penalty;

                                            if($bal_tenderd_amount >= 0){
                                                add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $pntyrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, 0, $penalty, 0, 0, $trans_date, $payment_no);
                                                update_loan_schedule($pntyrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "unpaid", 0, "paid");
                                                $tenderd_amount -= $penalty;
                                                $GLPenalty += $penalty;
                                                $partialpay = 0;
                                            }else{
                                                $nextPenaltyBal = ($penalty - $tenderd_amount);
                                                add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $pntyrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, 0, $tenderd_amount, 0, 0, $trans_date, $payment_no);
                                                update_loan_schedule($pntyrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "unpaid", $nextPenaltyBal, "partial");
                                                
                                                $GLPenalty += $tenderd_amount;
                                                $tenderd_amount = $bal_tenderd_amount = $penalty = $partialpay = 0;

                                            }
                                            if($tenderd_amount <= 0){
                                                $tenderd_amount = $bal_tenderd_amount = $penalty = $partialpay = 0;
                                                break;
                                            }
                                        }
                                    }
                                    $tenderd_amount = 0;
                                }
                            }
                        }
                        //no more penalty
                        if($tenderd_amount > 0){
                            //check if maka kuha ba ug rebate
                            $RebateAmount = GetRebate($_POST['trans_date'], $myrow["date_due"], $debtor_loans["rebate"]);

                            if($myrow["status"] == "partial"){
                                $thismonthAmort = ($myrow["principal_due"] - $partialpayment);

                                if(($tenderd_amount + $RebateAmount) == $thismonthAmort){

                                    add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $myrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, $thismonthAmort, 0, $RebateAmount, 0, $trans_date, $payment_no);
                                    update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "paid", 0, "paid");
                                    
                                    $GLRebate += $RebateAmount;
                                    $allocatedAmount += $thismonthAmort;
                                    $thismonthAmort = $tenderd_amount = 0;

                                }elseif(($tenderd_amount + $RebateAmount) < $thismonthAmort){

                                    add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $myrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, $tenderd_amount, 0, 0, 0, $trans_date, $payment_no);
                                    update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "partial");
                                    
                                    $allocatedAmount += $tenderd_amount;
                                    $tenderd_amount = 0;

                                }else{

                                    add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $myrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, $thismonthAmort, 0, $RebateAmount, 0, $trans_date, $payment_no);
                                    update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "paid", 0, "paid");
                                    
                                    $GLRebate += $RebateAmount;
                                    $allocatedAmount += $thismonthAmort;
                                    $tenderd_amount += $RebateAmount;
                                    $tenderd_amount -= $thismonthAmort;
                                    
                                }

                            }else{

                                if($tenderd_amount == ($myrow["principal_due"] - $RebateAmount)){

                                    add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $myrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, $myrow["principal_due"], 0, $RebateAmount, 0, $trans_date, $payment_no);
                                    update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "paid", 0, "paid");
                                    
                                    $allocatedAmount += $myrow["principal_due"];
                                    $GLRebate += $RebateAmount;
                                    $tenderd_amount = 0;

                                }elseif($tenderd_amount < ($myrow["principal_due"] - $RebateAmount)){
                                    
                                    add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $myrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, $tenderd_amount, 0, 0, 0, $trans_date, $payment_no);
                                    update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "partial");
                                    
                                    $allocatedAmount += $tenderd_amount;
                                    $tenderd_amount = 0;

                                }else{
                                    add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $myrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, $myrow["principal_due"], 0, $RebateAmount, 0, $trans_date, $payment_no);
                                    update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "paid", 0, "paid");
                                    
                                    $GLRebate += $RebateAmount;
                                    $allocatedAmount += $myrow["principal_due"];
                                    $tenderd_amount += $RebateAmount;
                                    $tenderd_amount -= $myrow["principal_due"];
                                    
                                }
                            }
                            if($tenderd_amount <= 0){
                                $tenderd_amount = 0;
                                break;
                            }
                        }
                    }
                }
                //allocate payment to trans number sales invoice
                //($GLRebate + ($_POST['tenderd_amount'] - $GLPenalty))
                add_cust_allocation($allocatedAmount, ST_CUSTPAYMENT, $payment_no, $_POST['transtype'], $_POST['InvoiceNo'], $_POST['customername'], $_POST['trans_date']);
                update_debtor_trans_allocation($_POST['transtype'], $_POST['InvoiceNo'], $_POST['customername']);

                //allocate discount
                if($GLRebate != 0){
                    update_alloc_rebate(ST_CUSTPAYMENT, $payment_no, $GLRebate);
                }

                $term = get_mos_term($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype']);
                if($term <= 3) {
                    $debtors_account = $company_record["ar_reg_current_account"];
                }else{
                    $debtors_account = $company_record["debtors_act"];
                }

                if (($GLRebate + ($_POST['tenderd_amount'] - $GLPenalty)) != 0)	{
                    /* Now Credit Debtors account with receipts + discounts */
                    $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $debtors_account, 0, 0, -($GLRebate + ($_POST['tenderd_amount'] - $GLPenalty)), $_POST['customername'], "Cannot insert a GL transaction for the debtors account credit", 0, null, null, 0, $_POST['InvoiceNo']);
                }

                //deferred -> debit; realized -> credit
                if($grossPM > 0){
                    $PM_amount = (($GLRebate + ($_POST['tenderd_amount'] - $GLPenalty)) *  $grossPM);
                    if($PM_amount != 0){
                        $GLtotal += add_gl_trans(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $company_record["dgp_account"], 0, 0, '', check_isempty($PM_amount), null, PT_CUSTOMER, $_POST['customername'], "", 0, null, null, 0, $_POST['InvoiceNo']);
                        $GLtotal += add_gl_trans(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $company_record["rgp_account"], 0, 0, '', -check_isempty($PM_amount), null, PT_CUSTOMER, $_POST['customername'], "", 0, null, null, 0, $_POST['InvoiceNo']);
                    }
                }

                if ($GLRebate != 0)	{
                    /* Now Debit discount account with discounts allowed*/
                    $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $branch_data["payment_discount_account"], 0, 0, $GLRebate, $_POST['customername'], "Cannot insert a GL transaction for the payment discount debit", 0, null, null, 0, $_POST['InvoiceNo']);
                }

                if($GLPenalty != 0){
                    /* Now credit bank account with penalty */
                    $penalty_act = get_company_pref('penalty_act');
                    $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $penalty_act, 0, 0, -$GLPenalty, $_POST['customername'], "Cannot insert a GL transaction for the payment penalty credit", 0, null, null, 0, $_POST['InvoiceNo']);
                }

                /*Post a balance post if $total != 0 due to variance in AR and bank posted values*/
                if ($GLtotal != 0){
                    $variance_act = get_company_pref('exchange_diff_act');
                    add_gl_trans(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'],	$variance_act, 0, 0, '', -$GLtotal, null, PT_CUSTOMER, $_POST['customername'], "", 0, null, null, 0, $_POST['InvoiceNo']);
                }

                //allocate payment to
                update_debtor_trans_allocation(ST_CUSTPAYMENT, $payment_no, $_POST['customername']);

                if(check_schedule_status($_POST['InvoiceNo'], $_POST['transtype'], $_POST['customername']) == 0){
                    update_status_debtor_trans($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype'], "fully-paid");
                    update_status_debtor_loans($_POST['InvoiceNo'], $_POST['customername'], "fully-paid");
                }else{
                    update_status_debtor_trans($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype'], "part-paid");
                    update_status_debtor_loans($_POST['InvoiceNo'], $_POST['customername'], "part-paid");
                }

                update_status_interbranch_trans($_POST['syspk'], $_SESSION["wa_current_user"]->username, 'approved', $payment_no, ST_CUSTPAYMENT, null);

                $interBTrans = get_interB_transNo_from($_POST['syspk']);
                update_status_interbranch_trans_HO($interBTrans['ref_no'], $_SESSION["wa_current_user"]->username, 'approved', $payment_no, ST_CUSTPAYMENT, $interBTrans['transno_from_branch'], $interBTrans['trantype_from_branch'] );

            }
            $dsplymsg = _("Customer payment has been allocated successfully...");
        }
        echo '({"success":"true","message":"'.$dsplymsg.'", "payno":"'.$payment_no.'"})';
    }else{
        echo '({"failure":"false","message":"'.$dsplymsg.'"})';
    }
    return;
}

//------------------------------------------alone with you :> ------------------------------------

if(isset($_GET['getFrbranch'])){
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
if(isset($_GET['get_aloneCustomer']))
{
    $result = get_all_customer();
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('debtor_no'=>$myrow["debtor_no"],
                               'debtor_ref'=>$myrow["debtor_ref"],
                               'name'=>htmlentities($myrow["name"])
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_notfa_interb']))
{

    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);

    $result = get_notfa_interb($_GET['branch'], $_GET['query'], $start, $limit);
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $branch = get_branch_info($myrow['branch_code_from']);
        $status_array[] = array('id'=>$myrow['transno_to_branch'],
                                'branch_code_from'=>$myrow['branch_code_from'],
                                'branch_name'=>array_column($branch, 'name'),
                                'branch_gl_code'=>array_column($branch, 'gl_account'),
                                'debtor_id'=>$myrow['debtor_no'],
                                'debtor_ref'=>$myrow['debtor_ref'],
                                'debtor_name'=>$myrow['debtor_name'],
                                'trans_date'=>$myrow['trans_date'],
                                'ref_no'=>$myrow['ref_no'],
                                'amount'=>$myrow['amount'],
                                'remarks'=>$myrow['remarks'],
                                'prepared_by'=>$myrow['prepared_by'],
                                'or_ref_no'=>$myrow['receipt_no'],
                                'approved_by'=>$myrow['approved_by'],
                                'type'=>$myrow['type']
                            );
     }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';

    set_global_connection();
    return;
}

if(isset($_GET['submit_inbpaysalone']))
{
    //initialise no input errors assumed initially before we proceed
    //0 is by default no errors
    $InputError = 0;
    $isdoneledger = 0;
    
    if (empty($_POST['transtype']) || empty($_POST['ref_no']) || empty($_POST['debit_acct']) || empty($_POST['custname'])) {
        $InputError = 1;
        $dsplymsg = _('Some fields are empty or contain an improper value. Please reload the page and fill up the required field.');
    }
    if (empty($_POST['paymentType'])) {
        $InputError = 1;
        $dsplymsg = _('Some fields are empty or contain an improper value. Please reload the page and fill up the required field.');
    }
    if (empty($_POST['moduletype'])) {
        $InputError = 1;
        $dsplymsg = _('Some fields are empty or contain an improper value. Please reload the page and fill up the required field.');
    }
    /*if (empty($_POST['islending'])) {
        $InputError = 1;
        $dsplymsg = _('Some fields are empty or contain an improper value. Please reload the page and fill up the required field.');
    }*/
    if (empty($_POST['customercode'])) {
        $InputError = 1;
        $dsplymsg = _('Customer code must not be empty.');
    }
    if (empty($_POST['customername'])) {
        $InputError = 1;
        $dsplymsg = _('Customer name must not be empty.');
    }
    if (empty($_POST['trans_date'])) {
        $InputError = 1;
        $dsplymsg = _('Transaction date must not be empty.');
    }else{
        $trans_date = date('Y-m-d',strtotime($_POST['trans_date']));
    }
    if (empty($_POST['InvoiceNo'])) {
        $InputError = 1;
        $dsplymsg = _('Invoice number must not be empty.');
    }
    if (empty($_POST['total_amount'])) {
        $InputError = 1;
        $dsplymsg = _('Total amount must not be empty.');
    }
    if ($_POST['total_amount'] == 0) {
        $InputError = 1;
        $dsplymsg = _('Total amount must be greater than 0.');
    }
    if (empty($_POST['tenderd_amount'])) {
        $InputError = 1;
        $dsplymsg = _('Allocate amount must not be empty.');
    }
    if ($_POST['tenderd_amount'] == 0) {
        $InputError = 1;
        $dsplymsg = _('Allocate amount must be greater than 0.');
    }
    if (empty($_POST['remarks'])) {
        $InputError = 1;
        $dsplymsg = _('remarks must not be empty.');
    }
    /*if (empty($_POST['cashier'])) {
        $InputError = 1;
        $dsplymsg = _('Cashier must not be empty.');
    }*/
    if (empty($_POST['preparedby'])) {
        $InputError = 1;
        $dsplymsg = _('Prepared by must not be empty.');
    }

    $DataOnGrid = stripslashes(html_entity_decode($_POST['DataOnGrid']));
    $objDataGrid = json_decode($DataOnGrid, true);
    
    //var_dump($objDataGrid);
    if (count($objDataGrid) == 0){
        $InputError = 1;
        $dsplymsg = _('Credit amount must not be empty! Please try again.');
    }
    
    if ($InputError != 1){
        
        begin_transaction();
        $BranchNo = get_newcust_branch($_POST['customername'], $_POST['customercode']);
        $debtor_loans = get_debtor_loans_info($_POST['InvoiceNo'], $_POST['customername']);
        $company_record = get_company_prefs();

        foreach($objDataGrid as $value=>$data) {
            $Loansched_ID = $data['loansched_id'];
            $debtor_id = $data['debtor_id'];
            $trans_no = $data['trans_no'];
            $date_due = $data['date_due'];
            $maturity_date = $data['maturity_date'];
            $mosterm = $data['mosterm'];
            $ar_due = $data['ar_due'];
            $total_rebate = $data['rebate'];
            $total_penalty = $data['penalty'];
            $penaltyBal = $data['penaltyBal'];
            $partialpayment = $data['partialpayment'];
            $amortization = $data['amortization'];
            $Alloc_Amount = $data['alloc_amount'];
            $dp_discount = $data['dp_discount'];
            $grossPM = $data['grossPM'];

            set_global_connection();
            
            if($_POST['paymentType2'] == "down"){
                //---->>>>>>> down payment -------------------------
                //$result = get_amort_downpayment($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype']);
                //$dprow = db_fetch($result);
                $row_dpd = get_dp_discount($_POST['InvoiceNo'], $_POST['customername']);

                $payment_no = write_customer_payment(0, $_POST['customername'], check_isempty($BranchNo['branch_code']), $_POST['intobankacct'], $_POST['trans_date'], $_POST['ref_no'],
                                                $_POST['tenderd_amount'], check_isempty($dp_discount), $_POST['remarks'], 0, 0, input_num('bank_amount', $_POST['tenderd_amount']),
                                                0, $_POST['paymentType'], 0, $_POST['moduletype'], 0, 'Cash', '0000-00-00', 0, null, $_POST['InvoiceNo'], $_POST['receipt_no'], $_POST['preparedby'], null, null,
                                                $row_dpd["discount_downpayment"], $row_dpd["discount_downpayment2"], $_POST['transtype'], null, 0);

                add_cust_allocation(($_POST['tenderd_amount'] + check_isempty($dp_discount)), ST_CUSTPAYMENT, $payment_no, $_POST['transtype'], $_POST['InvoiceNo'], $_POST['customername'], $_POST['trans_date']);
                update_debtor_trans_allocation($_POST['transtype'], $_POST['InvoiceNo'], $_POST['customername']);
                

                if($_POST['total_amount'] == $_POST['tenderd_amount']){
                    add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $Loansched_ID, $_POST['transtype'], ST_CUSTPAYMENT, ($_POST['tenderd_amount']  + check_isempty($dp_discount)), 0, 0, 0, $trans_date, $payment_no);
                    update_loan_schedule($Loansched_ID, $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "paid", 0, "paid");
                    update_dp_status($_POST['InvoiceNo'], $_POST['transtype']);

                    $tenderd_amount = 0;

                }elseif($_POST['total_amount'] > $_POST['tenderd_amount']) {
                    $nextDPBal = ($_POST['total_amount'] - $_POST['tenderd_amount']);

                    add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $Loansched_ID, $_POST['transtype'], ST_CUSTPAYMENT, $_POST['tenderd_amount'], 0, 0, 0, $trans_date, $payment_no);
                    update_loan_schedule($Loansched_ID, $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "partial");
                
                }

                if($grossPM != 0){
                    $dgp_account = $company_record["dgp_account"];
                    $rgp_account = $company_record["rgp_account"];

                    $DeferdAmt = ($_POST['tenderd_amount'] + $dp_discount) * $grossPM;

                    $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $dgp_account, 0, 0, $DeferdAmt, $_POST['customername'], "Cannot insert a GL transaction for the DGP account debit", 0, null, null, 0, $_POST['InvoiceNo']);
                    $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $rgp_account, 0, 0, -$DeferdAmt, $_POST['customername'], "Cannot insert a GL transaction for the RGP account credit", 0, null, null, 0, $_POST['InvoiceNo']);
                }

                interbranch_notfa_add($_POST['branch_inb'], $db_connections[user_company()]["branch_code"], $_POST['customercode'], $_POST['custname'], $trans_date, $_POST['ref_no'], $_POST['tenderd_amount'], $_POST['remarks'],
                                        $_POST['preparedby'], 'approved', $_SESSION["wa_current_user"]->username, $payment_no, ST_CUSTPAYMENT, 3);

                $dsplymsg = _("Down-payment has been successfully entered...");
    
            }else{
                $GLPenalty = $GLRebate = $GLtotal = $partialpay = $allocatedAmount = 0;
                $partialpay = $partialpayment;
                $tenderd_amount = $_POST['tenderd_amount'];

                $branch_data = get_branch_accounts($BranchNo['branch_code']);

                $payment_no = write_customer_trans(ST_CUSTPAYMENT, 0, $_POST['customername'], check_isempty($BranchNo['branch_code']), $_POST['trans_date'], $_POST['ref_no'],
                $_POST['tenderd_amount'], 0 , 0, 0, 0, 0, 0, 0, null, 0, 0, 0, 0, null, 0, 0, 0, $_POST['paymentType'], 0, $_POST['moduletype']);

                add_bank_trans(ST_CUSTPAYMENT, $payment_no, 0, $_POST['ref_no'], $_POST['trans_date'], $_POST['tenderd_amount'], PT_CUSTOMER, $_POST['customername'],
                /*$_POST['cashier']*/ 0, $_POST['pay_type'], '0000-00-00', 0, null, $_POST['InvoiceNo'], $_POST['receipt_no'], $_POST['preparedby']);

                add_comments(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $_POST['remarks']);

                /* Bank account entry first */
                $GLtotal += add_gl_trans(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $_POST['debit_acct'], 0, 0, '', $_POST['tenderd_amount'], null, PT_CUSTOMER, $_POST['customername'], "", 0, null, null, 0, $_POST['InvoiceNo']);

                $result = get_loan_schedule($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype']);
                while ($myrow = db_fetch($result)) {
                    if($tenderd_amount > 0){
                        if($total_penalty > 0){
                            //penalty
                            if($penaltyBal != 0){
                                $tenderd_amount -= $myrow["penalty_balance"];
                                $total_penalty -= $myrow["penalty_balance"];
    
                                if($tenderd_amount > 0){
                                    add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $myrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, 0, $myrow["penalty_balance"], 0, 0, $trans_date, $payment_no);
                                    update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "unpaid", 0, "paid");
                                    
                                    $penaltyBal = 0;
                                    $GLPenalty += $myrow["penalty_balance"];
                                }else{
                                    $nextPenaltyBal = ($myrow["penalty_balance"] - $_POST['tenderd_amount']);
                                    add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $myrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, 0, $_POST['tenderd_amount'], 0, 0, $trans_date, $payment_no);
                                    update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "unpaid", $nextPenaltyBal, "partial");
                                    
                                    $penaltyBal = $nextPenaltyBal;
                                    $nextPenaltyBal = $tenderd_amount = 0;
                                    $GLPenalty += $_POST['tenderd_amount'];
                                }
    
                            }else{
    
                                if($tenderd_amount > $total_penalty){
                                    $tenderd_amount -= $total_penalty;
                                    $bal_penalty = $total_penalty;
    
                                    //set penalty status paid in table schedule
                                    $pnty_result = get_loan_schedule_penalty($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype']);
                                    while ($pntyrow = db_fetch($pnty_result)){
                                        $MonthNo = CalcMonthsDue_pnlty($_POST['trans_date'], $pntyrow["date_due"], $maturity_date);
                                        if($MonthNo != 0){
                                            $penalty = per_Penalty($MonthNo, ($pntyrow["principal_due"]- $partialpay));
                                            
                                            if($penalty > 0){
                                                add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $pntyrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, 0, $penalty, 0, 0, $trans_date, $payment_no);
                                                update_loan_schedule($pntyrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "unpaid", 0, "paid");
                                                $total_penalty -= $penalty;
                                                $GLPenalty += $penalty;
                                                $partialpay = 0;
                                            }
                                        }
                                        if($total_penalty <= 0){
                                            $total_penalty = $penalty = 0;
                                            break;
                                        }
                                    }
                                    $bal_penalty = $total_penalty = 0;
                                }else{
                                    //dako ang $total_penalty kaysa $tenderd_amount
                                    $total_penalty -= $tenderd_amount;
                                    $bal_tenderd_amount = $tenderd_amount;
    
                                    //set penalty status paid in table schedule
                                    $pnty_result = get_loan_schedule_penalty($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype']);
                                    while ($pntyrow = db_fetch($pnty_result)){
                                        $MonthNo = CalcMonthsDue_pnlty($_POST['trans_date'], $pntyrow["date_due"], $maturity_date);
                                        if($MonthNo != 0){
                                            $penalty = per_Penalty($MonthNo, ($pntyrow["principal_due"] - $partialpay));
                                            $bal_tenderd_amount -= $penalty;
    
                                            if($bal_tenderd_amount >= 0){
                                                add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $pntyrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, 0, $penalty, 0, 0, $trans_date, $payment_no);
                                                update_loan_schedule($pntyrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "unpaid", 0, "paid");
                                                $tenderd_amount -= $penalty;
                                                $GLPenalty += $penalty;
                                                $partialpay = 0;
                                            }else{
                                                $nextPenaltyBal = ($penalty - $tenderd_amount);
                                                add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $pntyrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, 0, $tenderd_amount, 0, 0, $trans_date, $payment_no);
                                                update_loan_schedule($pntyrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "unpaid", $nextPenaltyBal, "partial");
                                                
                                                $GLPenalty += $tenderd_amount;
                                                $tenderd_amount = $bal_tenderd_amount = $penalty = $partialpay = 0;
    
                                            }
                                            if($tenderd_amount <= 0){
                                                $tenderd_amount = $bal_tenderd_amount = $penalty = $partialpay = 0;
                                                break;
                                            }
                                        }
                                    }
                                    $tenderd_amount = 0;
                                }
                            }
                        }
                        //no more penalty
                        if($tenderd_amount > 0){
                            //check if maka kuha ba ug rebate
                            $RebateAmount = GetRebate($_POST['trans_date'], $myrow["date_due"], $debtor_loans["rebate"]);
    
                            if($myrow["status"] == "partial"){
                                $thismonthAmort = ($myrow["principal_due"] - $partialpayment);
    
                                if(($tenderd_amount + $RebateAmount) == $thismonthAmort){
    
                                    add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $myrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, $thismonthAmort, 0, $RebateAmount, 0, $trans_date, $payment_no);
                                    update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "paid", 0, "paid");
                                    
                                    $GLRebate += $RebateAmount;
                                    $allocatedAmount += $thismonthAmort;
                                    $thismonthAmort = $tenderd_amount = 0;
    
                                }elseif(($tenderd_amount + $RebateAmount) < $thismonthAmort){
    
                                    add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $myrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, $tenderd_amount, 0, 0, 0, $trans_date, $payment_no);
                                    update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "partial");
                                    
                                    $allocatedAmount += $tenderd_amount;
                                    $tenderd_amount = 0;
    
                                }else{
    
                                    add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $myrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, $thismonthAmort, 0, $RebateAmount, 0, $trans_date, $payment_no);
                                    update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "paid", 0, "paid");
                                    
                                    $GLRebate += $RebateAmount;
                                    $allocatedAmount += $thismonthAmort;
                                    $tenderd_amount += $RebateAmount;
                                    $tenderd_amount -= $thismonthAmort;
                                    
                                }
    
                            }else{
    
                                if($tenderd_amount == ($myrow["principal_due"] - $RebateAmount)){
    
                                    add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $myrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, $myrow["principal_due"], 0, $RebateAmount, 0, $trans_date, $payment_no);
                                    update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "paid", 0, "paid");
                                    
                                    $allocatedAmount += $myrow["principal_due"];
                                    $GLRebate += $RebateAmount;
                                    $tenderd_amount = 0;
    
                                }elseif($tenderd_amount < ($myrow["principal_due"] - $RebateAmount)){
                                    
                                    add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $myrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, $tenderd_amount, 0, 0, 0, $trans_date, $payment_no);
                                    update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "partial");
                                    
                                    $allocatedAmount += $tenderd_amount;
                                    $tenderd_amount = 0;
    
                                }else{
                                    add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $myrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, $myrow["principal_due"], 0, $RebateAmount, 0, $trans_date, $payment_no);
                                    update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "paid", 0, "paid");
                                    
                                    $GLRebate += $RebateAmount;
                                    $allocatedAmount += $myrow["principal_due"];
                                    $tenderd_amount += $RebateAmount;
                                    $tenderd_amount -= $myrow["principal_due"];
                                    
                                }
                            }
                            $isdoneledger = 1;
                            if($tenderd_amount <= 0){
                                $tenderd_amount = 0;
                                break;
                            }
                        }
                    }
                }

                if($isdoneledger != 0) {

                    //allocate payment to trans number sales invoice
                    //($GLRebate + ($_POST['tenderd_amount'] - $GLPenalty))
                    add_cust_allocation($allocatedAmount, ST_CUSTPAYMENT, $payment_no, $_POST['transtype'], $_POST['InvoiceNo'], $_POST['customername'], $_POST['trans_date']);
                    update_debtor_trans_allocation($_POST['transtype'], $_POST['InvoiceNo'], $_POST['customername']);
                    
                    //allocate discount
                    if($GLRebate != 0){
                        update_alloc_rebate(ST_CUSTPAYMENT, $payment_no, $GLRebate);
                    }
    
                    $term = get_mos_term($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype']);
                    if($term <= 3) {
                        $debtors_account = $company_record["ar_reg_current_account"];
                    }else{
                        $debtors_account = $company_record["debtors_act"];
                    }
    
                    if (($GLRebate + ($_POST['tenderd_amount'] - $GLPenalty)) != 0)	{
                        /* Now Credit Debtors account with receipts + discounts */
                        $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $debtors_account, 0, 0, -($GLRebate + ($_POST['tenderd_amount'] - $GLPenalty)), $_POST['customername'], "Cannot insert a GL transaction for the debtors account credit", 0, null, null, 0, $_POST['InvoiceNo']);
                    }
    
                    //deferred -> debit; realized -> credit
                    if($grossPM > 0){
                        $PM_amount = (($GLRebate + ($_POST['tenderd_amount'] - $GLPenalty)) *  $grossPM);
                        if($PM_amount != 0){
                            $GLtotal += add_gl_trans(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $company_record["dgp_account"], 0, 0, '', check_isempty($PM_amount), null, PT_CUSTOMER, $_POST['customername'], "", 0, null, null, 0, $_POST['InvoiceNo']);
                            $GLtotal += add_gl_trans(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $company_record["rgp_account"], 0, 0, '', -check_isempty($PM_amount), null, PT_CUSTOMER, $_POST['customername'], "", 0, null, null, 0, $_POST['InvoiceNo']);
                        }
                    }
    
                    if ($GLRebate != 0)	{
                        /* Now Debit discount account with discounts allowed*/
                        $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $branch_data["payment_discount_account"], 0, 0, $GLRebate, $_POST['customername'], "Cannot insert a GL transaction for the payment discount debit", 0, null, null, 0, $_POST['InvoiceNo']);
                    }
    
                    if($GLPenalty != 0){
                        /* Now credit bank account with penalty */
                        $penalty_act = get_company_pref('penalty_act');
                        $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $penalty_act, 0, 0, -$GLPenalty, $_POST['customername'], "Cannot insert a GL transaction for the payment penalty credit", 0, null, null, 0, $_POST['InvoiceNo']);
                    }
    
                    /*Post a balance post if $total != 0 due to variance in AR and bank posted values*/
                    if ($GLtotal != 0){
                        $variance_act = get_company_pref('exchange_diff_act');
                        add_gl_trans(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'],	$variance_act, 0, 0, '', -$GLtotal, null, PT_CUSTOMER, $_POST['customername'], "", 0, null, null, 0, $_POST['InvoiceNo']);
                    }
    
                    //allocate payment to
                    update_debtor_trans_allocation(ST_CUSTPAYMENT, $payment_no, $_POST['customername']);
                    
                    if(check_schedule_status($_POST['InvoiceNo'], $_POST['transtype'], $_POST['customername']) == 0){
                        update_status_debtor_trans($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype'], "fully-paid");
                        update_status_debtor_loans($_POST['InvoiceNo'], $_POST['customername'], "fully-paid");
                    }else{
                        update_status_debtor_trans($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype'], "part-paid");
                        update_status_debtor_loans($_POST['InvoiceNo'], $_POST['customername'], "part-paid");
                    }
                    
                    interbranch_notfa_add($_POST['branch_inb'], $db_connections[user_company()]["branch_code"], $_POST['customercode'], $_POST['custname'], $trans_date, $_POST['ref_no'], $_POST['tenderd_amount'], $_POST['remarks'],
                                                $_POST['preparedby'], 'approved', $_SESSION["wa_current_user"]->username, $payment_no, ST_CUSTPAYMENT, 3);
                
                }
                
                $dsplymsg = _("Customer payment has been allocated successfully...");
            }
        }
        echo '({"success":"true","message":"'.$dsplymsg.'", "payno":"'.$payment_no.'"})';
    }else{
        echo '({"failure":"false","message":"'.$dsplymsg.'"})';
    }
    return;
}

if($_GET['type']=='aloneinb'){
    page(_($help_context = "Inter-branch (From Not FA)"));
}else{
    page(_($help_context = "Incoming Inter-branch Payments Inquiry"));
}

start_table(TABLESTYLE, "width='100%'");
   echo "<div id='ext-form'></div>";
   echo "<style type='text/css' media='screen'>
            .x-form-text-default.x-form-textarea {
                line-height: 28px;
                min-height: 30px;
            }
        </style>";
end_table();

end_form();
end_page();

