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
$page_security = 'SA_ALLOCPYMNT';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/lending/includes/lending_cfunction.inc");

//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root ."/js/allocation_payment.js");

//----------------------------------------------: for store js :---------------------------------------
if(isset($_GET['getReference'])){
    $reference = $Refs->get_next(ST_CUSTPAYMENT, GetReferenceID('ALCN'), array('date' => Today()), true, ST_CUSTPAYMENT);
    echo '({"success":"true","reference":"'.$reference.'"})';
    //echo $_POST['debtor_id'];
    return;
}
if(isset($_GET['get_Customer']))
{
    if($_GET['module'] == "DP"){
        $result = get_AllocDP_customer();
        while ($myrow = db_fetch($result)) {
            $status_array[] = array('debtor_no'=>$myrow["debtor_no"],
                'debtor_ref'=>$myrow["debtor_ref"],
                'name'=>htmlentities($myrow["name"]),
                'amount'=>$myrow["ov_amount"],
                'trans_no'=>$myrow["trans_no"],
                'brcode'=>0,
                'brdate'=>'0000-00-00',
                'pay_type'=>$myrow["type"]
            );
        }
    }else{
        $result = get_interB_customer();
        while ($myrow = db_fetch($result)) {
            $branch = get_branch_info($myrow['branch_code_from']);
            $status_array[] = array('debtor_no'=>$myrow["debtor_no"],
                'debtor_ref'=>$myrow["debtor_ref"],
                'name'=>htmlentities($myrow["name"]),
                'amount'=>$myrow["amount"],
                'trans_no'=>$myrow["id"],
                'brcode'=>array_column($branch, 'gl_account'),
                'brdate'=>$myrow["trans_date"],
                'pay_type'=>ST_CUSTPAYMENT /** temporary */
            );
        }
    }
    
    $total = DB_num_rows($result);
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
                               'status'=>$myrow["status"],
                               'pay_location'=>$myrow["payment_location"]
                            );
    }
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
    $PartialPayment = $Penalty = $PartialBal = $RebateAmount = $TotalBalance = $DP_Discount = $grossPM = $comptdGPM = 0;

    $ar_balance = check_ar_balance($_GET['transNo'], $_GET['transtype']);

    if($_GET['pay_type'] == "down"){
        if($_GET['payloc'] == "Lending"){
            $result = get_NoSchedDownPaymnt($_GET['transNo'], $_GET['debtor_no']);
        }else{
            $result = get_amort_downpayment($_GET['transNo'], $_GET['debtor_no'], $_GET['transtype']);
        }
        
        $total = DB_num_rows($result);
        $dprow = db_fetch($result);
        
        if($_GET['payloc'] == "Lending"){
            $DP_Discount = ($dprow["discount_downpayment"] + $dprow["discount_downpayment2"]);
            $Totalpayment = ($dprow["downpayment_amount"] - $DP_Discount);
            $month_no = $PartialPayment = 0;
            $total_runbal = $ar_balance;
            $loansched_id = $_GET['transNo'];
            $date_due = date('m-d-Y', strtotime($dprow["invoice_date"]));
        }else{
            if($dprow['status'] == 'unpaid'){
                $DP_Discount = ($dprow["discount_downpayment"] + $dprow["discount_downpayment2"]);
            }
            $Totalpayment = ($dprow["downpayment_amount"] - $DP_Discount);
            $paymentAppld = get_payment_appliedSUM($_GET['transtype'],$_GET['transNo'], $dprow["loansched_id"]);
            $total_runbal = $dprow["total_runbal"];
            $month_no = $dprow["month_no"];
            $date_due = date('m-d-Y', strtotime($dprow["date_due"]));
            $loansched_id = $dprow["loansched_id"];
        }
        
        if($paymentAppld != 0){
            $PartialPayment = $paymentAppld;
            $Totalpayment -= $paymentAppld;
        }
        $grossPM = $dprow["profit_margin"];

        //check if can avail rebate
        //$RebateAmount = GetRebate($_GET['transdate'], $dprow["date_due"], $dprow["rebate"]);

        $status_array[] = array('loansched_id'=>$loansched_id,
            'debtor_id'=>$_GET['debtor_no'],
            'trans_no'=>$_GET['transNo'],
            'date_due'=>$date_due,
            'maturity_date'=>date('m-d-Y', strtotime($dprow["maturity_date"])),
            'mosterm'=>$dprow["month_no"],
            'downpayment'=>$dprow["downpayment_amount"],
            'amortization'=>0,
            'ar_due'=>$total_runbal,
            'rebate'=>0,
            'penalty'=>0,
            'penaltyBal'=>0,
            'partialpayment'=>$PartialPayment,
            'totalpayment'=>$Totalpayment,
            'alloc_amount'=>0,
            'dp_discount'=>$DP_Discount,
            'grossPM'=>$grossPM,
            'balance'=>$ar_balance
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
        'alloc_amount'=>0,
        'downpayment'=>0,
        'dp_discount'=>0,
        'grossPM'=>$grossPM,
        'balance'=>$ar_balance
        );
    }

    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_custPayment']))
{
    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
    
    //echo "as".$_GET['transno'];

    $result = get_debtor_payment_info(ST_CUSTPAYMENT, $_GET['module_type'], $start, $limit, $_GET['query'], $_GET['transno']);
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $trans_typeTo = get_Trans_Type($myrow["trans_no"]);
        if($myrow["payment_type"] == "down"){
            $paymentType = "Down Payment";
        }elseif($myrow["payment_type"] == "amort"){
            $paymentType = "Amort Payment";
        }elseif($myrow["payment_type"] == "other"){
            $paymentType = "Other Payment";
        }
        $status_array[] = array('trans_no'=>$myrow["trans_no"],
                                'invoice_no'=>$myrow["masterfile"],
                                'tran_date'=>$myrow["tran_date"],
                                'trans_typeFr'=>$myrow["type"],
                                'trans_typeTo'=>$trans_typeTo,
                                'debtor_no'=>$myrow["debtor_no"],
                                'debtor_ref'=>$myrow["debtor_ref"],
                                'customer_name'=>htmlentities($myrow["name"]),
                                'reference'=>$myrow["reference"],
                                'receipt_no'=>$myrow["receipt_no"],
                                'total_amount'=>$myrow["ov_amount"],
                                'discount'=>$myrow["ov_discount"],
                                'Bank_account_id'=>$myrow["bank_act"],
                                'Bank_account'=>$myrow["bank_account_name"],
                                'pay_type'=>$myrow["pay_type"],
                                'pay_amount'=>$myrow["pay_amount"],
                                'check_date'=>$myrow["check_date"],
                                'check_no'=>$myrow["check_no"],
                                'Bank_branch'=>$myrow["bank_branch"],
                                'remarks'=>$myrow["memo_"],
                                'module_type'=>$myrow["module_type"],
                                'prepared_by'=>$myrow["prepared_by"],
                                'check_by'=>$myrow["checked_by"],
                                'approved_by'=>$myrow["approved_by"],
                                'payment_type_v'=>$paymentType,
                                'payment_type'=>$myrow["payment_type"],
                                'collect_type'=>$myrow["collect_id"],
                                'cashier'=>$myrow["cashier_user_id"],
                                'cashier_name'=>$myrow["real_name"]
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_loan_ledger']))
{
    $result = get_loan_ledger_payment($_GET['trans_no'], ST_CUSTPAYMENT, $_GET['typeTo']);
    $total = DB_num_rows($result);
    
    while ($myrow = db_fetch($result)) {
        $paymentAppld = get_payment_appliedSUM($_GET['typeTo'], $myrow['trans_no'], 0);

        $status_array[] = array('loansched_id'=>$myrow["loansched_id"],
                'debtor_id'=>$myrow['debtor_no'],
                'trans_no'=>$myrow['trans_no'],
                'date_due'=>date('m-d-Y', strtotime($myrow["date_due"])),
                'mosterm'=>$myrow["month_no"],
                'amortization'=>$myrow["principal_due"],
                'rebate'=>$myrow["rebate"],
                'penalty'=>$myrow["penalty"],
                'alloc_amount'=>$myrow["PayAmount"],
                'total_alloc'=>$paymentAppld,
                'balance'=>($myrow["ar_amount"]-$paymentAppld)
            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['getCOA']))
{
    $result = get_List_COA();
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
            $status_array[] = array('code'=>$myrow["account_code"],
                                    'name'=>$myrow["account_name"],
                                    'group'=>$myrow["name"]
                                );
    }

    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['submitAllocDP']))
{
    if (empty($_POST['syspk']) || empty($_POST['moduletype']) || empty($_POST['transtype']) || empty($_POST['ref_no']) || empty($_POST['paymentType'])) {
        $InputError = 1;
        $dsplymsg = _('Some fields are empty or contain an improper value. Please reload the page and fill up the required field.');
    }
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
        $dsplymsg = _('Tendered amount must not be empty.');
    }
    if ($_POST['tenderd_amount'] == 0) {
        $InputError = 1;
        $dsplymsg = _('Tendered amount must be greater than 0.');
    }
    if (empty($_POST['remarks'])) {
        $InputError = 1;
        $dsplymsg = _('remarks must not be empty.');
    }
    if (empty($_POST['cashier'])) {
        $InputError = 1;
        $dsplymsg = _('Cashier must not be empty.');
    }
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

    //check data
	if(check_done_alloc_trans($_POST['syspk'], $_POST['moduletype'])){
        $InputError = 1;
        $dsplymsg = _("This payment has already been allocated.");
    }
    //check balance amount > tendered amount
    $ar_balance = check_ar_balance($_POST['InvoiceNo'], $_POST['transtype']);
    if($ar_balance < $_POST['tenderd_amount']){
        $InputError = 1;
        $dsplymsg = _('Tendered amount must be lesser than or equal to A/R amount.');
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
            $ARAmount = 0;

            $branch_data = get_branch_accounts($BranchNo['branch_code']);
            $company_prefs = get_company_prefs();

            $payment_no = write_customer_trans(ST_CUSTPAYMENT, 0, $_POST['customername'], check_isempty($BranchNo['branch_code']), $_POST['trans_date'], $_POST['ref_no'],
                                        $_POST['tenderd_amount'], 0 , 0, 0, 0, 0, 0, 0, null, 0, 0, 0, 0, null, 0, 0, 0, $_POST['paymentType'], $_POST['collectType'], $_POST['moduletype']);
            
            add_bank_trans(ST_CUSTPAYMENT, $payment_no, 0, $_POST['ref_no'], $_POST['trans_date'], $_POST['tenderd_amount'], PT_CUSTOMER, $_POST['customername'],
                            $_POST['cashier'], $_POST['paymentType'], '0000-00-00', 0, null, $_POST['InvoiceNo'], $_POST['syspk'], $_POST['preparedby']);
        
            add_comments(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $_POST['remarks']);

            /* Bank account entry first */
            $GLtotal += add_gl_trans(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $company_prefs["downpaymnt_act"], 0, 0, '', $_POST['tenderd_amount'], null, PT_CUSTOMER, $_POST['customername'], "", 0, null, null, 0, $_POST['InvoiceNo']);

            if($_POST['paylocation'] != "Lending"){
                $result = get_amort_downpayment($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype']);
                while ($myrow = db_fetch($result)) {
                    if($tenderd_amount > 0){
                        $ARAmount = $myrow["principal_due"];
                        $allocatedAmount = $tenderd_amount + $dp_discount;

                        if($myrow["principal_due"] == ($tenderd_amount + $dp_discount)){

                            add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $myrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, ($_POST['tenderd_amount'] + check_isempty($dp_discount)), 0, 0, 0, $trans_date, $payment_no);
                            update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "paid", 0, "paid");
        
                        }elseif($myrow["principal_due"] > ($tenderd_amount + $dp_discount)){

                            $nextDPBal = ($myrow["principal_due"] - $_POST['tenderd_amount']);

                            add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $myrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, ($_POST['tenderd_amount'] + check_isempty($dp_discount)), 0, 0, 0, $trans_date, $payment_no);
                            update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "partial");
        
                        }else{

                            if($myrow["principal_due"] == $tenderd_amount){
                                add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $myrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, $_POST['tenderd_amount'], 0, 0, 0, $trans_date, $payment_no);
                                update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "paid", 0, "paid");
                            }
                            if($dp_discount > 0){
                                //auto allocate to firstdue

                                $debtor_loans = get_debtor_loans_info($_POST['InvoiceNo'], $_POST['customername']);
                                $schedresult = get_loan_schedule($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype']);

                                while ($schedrow = db_fetch($schedresult)) {
                                    $RebateAmount = GetRebate($trans_date, $schedrow["date_due"], $debtor_loans["rebate"]);

                                    if($dp_discount == $schedrow["principal_due"]){

                                        add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $schedrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, $dp_discount, 0, $RebateAmount, 0, $trans_date, $payment_no);
                                        update_loan_schedule($schedrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "paid", 0, "paid");
                                        
                                        $GLRebate += $RebateAmount;
                                        $tenderd_amount = $dp_discount = 0;

                                    }elseif($dp_discount < $schedrow["principal_due"]){

                                        add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $schedrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, $dp_discount, 0, 0, 0, $trans_date, $payment_no);
                                        update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "partial", 0);
                                        
                                        $tenderd_amount = $dp_discount = $GLRebate = 0;

                                    }
                                }
                            }

                            //add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $myrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, ($_POST['tenderd_amount'] + check_isempty($dp_discount)), 0, 0, 0, $trans_date, $payment_no);
                            //update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "partial");
        
                        }
                        $tenderd_amount = 0;

                        if($tenderd_amount <= 0){
                            $tenderd_amount = 0;
                            break;
                        }
                    }
                }
            }else{
                $allocatedAmount = $tenderd_amount + $dp_discount;
            }
                
            //allocate payment to trans number sales invoice
            //($GLRebate + ($_POST['tenderd_amount'] - $GLPenalty))
            add_cust_allocation($allocatedAmount, ST_CUSTPAYMENT, $payment_no, $_POST['transtype'], $_POST['InvoiceNo'], $_POST['customername'], $_POST['trans_date']);
            update_debtor_trans_allocation($_POST['transtype'], $_POST['InvoiceNo'], $_POST['customername']);

            $term = get_mos_term($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype']);
            if($term <= 3) {
                $debtors_account = $company_prefs["ar_reg_current_account"];
            }else{
                $debtors_account = $company_prefs["debtors_act"];
            }

            if ($_POST['tenderd_amount'] != 0)	{
                /* Now Credit Debtors account with receipts + discounts */
                //$GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $branch_data["receivables_account"], 0, 0, -($GLRebate + ($_POST['tenderd_amount'] - $GLPenalty)), $_POST['customername'], "Cannot insert a GL transaction for the debtors account credit");
                $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $debtors_account, 0, 0, -($_POST['tenderd_amount'] + check_isempty($data['dp_discount'])), $_POST['customername'], "Cannot insert a GL transaction for the debtors account credit", 0, null, null, 0, $_POST['InvoiceNo']);
            }

            //for dp discount
            if($data['dp_discount'] != 0){
                $dp_discount1_acct = $company_prefs["discount_dp_act"];
                $dp_discount2_acct = $company_prefs["dp_discount2_act"];

                $row_dpd = get_dp_discount($_POST['InvoiceNo'], $_POST['customername']);

                if($row_dpd["discount_downpayment"] != 0){
                    $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $dp_discount1_acct, 0, 0, $row_dpd["discount_downpayment"], $_POST['customername'], "Cannot insert a GL transaction for the downpayment discount 1", 0, null, null, 0, $_POST['InvoiceNo']);
                }
                if($row_dpd["discount_downpayment2"] != 0){
                    //get supplier for gl reference
                    $itmsrlt = get_item_to_supplier($_POST['InvoiceNo'], ST_SALESINVOICE);
                    $supplier = db_fetch($itmsrlt);
                    $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $dp_discount2_acct, 0, 0, $row_dpd["discount_downpayment2"], $_POST['customername'], "Cannot insert a GL transaction for the downpayment discount 2", 0, $supplier["supplier_id"], $supplier["supp_name"], 0, $_POST['InvoiceNo']);
                }
            }

            //deferred -> debit; realized -> credit
            if($grossPM > 0){
                $PM_amount = (($_POST['tenderd_amount'] + $data['dp_discount']) *  $grossPM);
                if($PM_amount != 0){
                    $GLtotal += add_gl_trans(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $company_prefs["dgp_account"], 0, 0, '', check_isempty($PM_amount), null, PT_CUSTOMER, $_POST['customername'], "", 0, null, null, 0, $_POST['InvoiceNo']);
                    $GLtotal += add_gl_trans(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $company_prefs["rgp_account"], 0, 0, '', -check_isempty($PM_amount), null, PT_CUSTOMER, $_POST['customername'], "", 0, null, null, 0, $_POST['InvoiceNo']);
                }
            }
            if ($GLRebate != 0)	{
                /* Now Debit discount account with discounts allowed*/
                $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $company_prefs["payment_discount_account"], 0, 0, $GLRebate, $_POST['customername'], "Cannot insert a GL transaction for the payment discount debit", 0, null, null, 0, $_POST['InvoiceNo']);
            }
            /*Post a balance post if $total != 0 due to variance in AR and bank posted values*/
            if ($GLtotal != 0)
            {
                $variance_act = get_company_pref('exchange_diff_act');
                add_gl_trans(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'],	$variance_act, 0, 0, '', -$GLtotal, null, PT_CUSTOMER, $_POST['customername'], "", 0, null, null, 0, $_POST['InvoiceNo']);
            }

            update_status_debtor_trans($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype'], "part-paid");
            update_status_debtor_loans($_POST['InvoiceNo'], $_POST['customername'], "part-paid");
            update_status_debtor_trans($_POST['syspk'], $_POST['customername'], $_POST['pay_transtype'], "Closed");

            //allocate payment to
            update_debtor_trans_allocation(ST_CUSTPAYMENT, $payment_no, $_POST['customername']);

            $dsplymsg = _("Customer payment has been allocated successfully...");        
        }
        echo '({"success":"true","message":"'.$dsplymsg.'"})';
    }else{
        echo '({"failure":"false","message":"'.$dsplymsg.'"})';
    }
    return;
}

if(isset($_GET['submitAllocInterB']))
{
    if (empty($_POST['syspk_aib']) || empty($_POST['moduletype_aib']) || empty($_POST['transtype_aib']) || empty($_POST['debit_acct_aib']) || empty($_POST['ref_no_aib']) || empty($_POST['paymentType_aib'])) {
        $InputError = 1;
        $dsplymsg = _('Some fields are empty or contain an improper value. Please reload the page and fill up the required field.');
    }
    if (empty($_POST['customercode_aib'])) {
        $InputError = 1;
        $dsplymsg = _('Customer code must not be empty.');
    }
    if (empty($_POST['customername_aib'])) {
        $InputError = 1;
        $dsplymsg = _('Customer name must not be empty.');
    }
    if (empty($_POST['trans_date_aib'])) {
        $InputError = 1;
        $dsplymsg = _('Transaction date must not be empty.');
    }else{
        $trans_date = date('Y-m-d',strtotime($_POST['trans_date_aib']));
    }
    if (empty($_POST['InvoiceNo_aib'])) {
        $InputError = 1;
        $dsplymsg = _('Invoice number must not be empty.');
    }
    if (empty($_POST['total_amount_aib'])) {
        $InputError = 1;
        $dsplymsg = _('Total amount must not be empty.');
    }
    if ($_POST['total_amount_aib'] == 0) {
        $InputError = 1;
        $dsplymsg = _('Total amount must be greater than 0.');
    }
    if (empty($_POST['tenderd_amount_aib'])) {
        $InputError = 1;
        $dsplymsg = _('Tendered amount must not be empty.');
    }
    if ($_POST['tenderd_amount_aib'] == 0) {
        $InputError = 1;
        $dsplymsg = _('Tendered amount must be greater than 0.');
    }
    if (empty($_POST['remarks_aib'])) {
        $InputError = 1;
        $dsplymsg = _('remarks must not be empty.');
    }
    if (empty($_POST['cashier_aib'])) {
        $InputError = 1;
        $dsplymsg = _('Cashier must not be empty.');
    }
    if (empty($_POST['preparedby_aib'])) {
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

        //check data
	if(check_done_alloc_inq($_POST['syspk_aib'])){
        $InputError = 1;
        $dsplymsg = _("This payment has already been allocated.");
    }

    if ($InputError != 1){
        
        begin_transaction();
        $BranchNo = get_newcust_branch($_POST['customername_aib'], $_POST['customercode_aib']);
        $debtor_loans = get_debtor_loans_info($_POST['InvoiceNo_aib'], $_POST['customername_aib']);

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
            $tenderd_amount = $_POST['tenderd_amount_aib'];

            $branch_data = get_branch_accounts($BranchNo['branch_code']);
            $company_prefs = get_company_prefs();

            $payment_no = write_customer_trans(ST_CUSTPAYMENT, 0, $_POST['customername_aib'], check_isempty($BranchNo['branch_code']), $_POST['trans_date_aib'], $_POST['ref_no_aib'],
                                        $_POST['tenderd_amount_aib'], 0 , 0, 0, 0, 0, 0, 0, null, 0, 0, 0, 0, null, 0, 0, 0, $_POST['paymentType_aib'], $_POST['collectType_aib'], $_POST['moduletype_aib']);
            
            add_bank_trans(ST_CUSTPAYMENT, $payment_no, 0, $_POST['ref_no_aib'], $_POST['trans_date_aib'], $_POST['tenderd_amount_aib'], PT_CUSTOMER, $_POST['customername_aib'],
                            $_POST['cashier_aib'], $_POST['paymentType_aib'], '0000-00-00', 0, null, $_POST['InvoiceNo_aib'], $_POST['syspk_aib'], $_POST['preparedby_aib']);
        
            add_comments(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date_aib'], $_POST['remarks_aib']);

            /* Bank account entry first */
            $GLtotal += add_gl_trans(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date_aib'], $_POST['debit_acct_aib'], 0, 0, '', $_POST['tenderd_amount_aib'], null, PT_CUSTOMER, $_POST['customername_aib'], "", 0, null, null, 0, $_POST['InvoiceNo_aib']);

            $result = get_loan_schedule($_POST['InvoiceNo_aib'], $_POST['customername_aib'], $_POST['transtype_aib']);
            while ($myrow = db_fetch($result)) {
                if($tenderd_amount > 0){
                    if($total_penalty > 0){
                        //penalty
                        if($penaltyBal != 0){
                            $tenderd_amount -= $myrow["penalty_balance"];
                            $total_penalty -= $myrow["penalty_balance"];

                            if($tenderd_amount > 0){
                                add_loan_ledger($_POST['InvoiceNo_aib'], $_POST['customername_aib'], $myrow["loansched_id"], $_POST['transtype_aib'], ST_CUSTPAYMENT, 0, $myrow["penalty_balance"], 0, 0, $trans_date, $payment_no);
                                update_loan_schedule($myrow["loansched_id"], $_POST['customername_aib'], $_POST['InvoiceNo_aib'], $_POST['transtype_aib'], "unpaid", 0, "paid");
                                
                                $penaltyBal = 0;
                                $GLPenalty += $myrow["penalty_balance"];
                            }else{
                                $nextPenaltyBal = ($myrow["penalty_balance"] - $_POST['tenderd_amount_aib']);
                                add_loan_ledger($_POST['InvoiceNo_aib'], $_POST['customername_aib'], $myrow["loansched_id"], $_POST['transtype_aib'], ST_CUSTPAYMENT, 0, $_POST['tenderd_amount_aib'], 0, 0, $trans_date, $payment_no);
                                update_loan_schedule($myrow["loansched_id"], $_POST['customername_aib'], $_POST['InvoiceNo_aib'], $_POST['transtype_aib'], "unpaid", $nextPenaltyBal, "partial");
                                
                                $penaltyBal = $nextPenaltyBal;
                                $nextPenaltyBal = $tenderd_amount = 0;
                                $GLPenalty += $_POST['tenderd_amount_aib'];
                            }
                        }else{
                            if($tenderd_amount > $total_penalty){
                                $tenderd_amount -= $total_penalty;
                                $bal_penalty = $total_penalty;

                                //set penalty status paid in table schedule
                                $pnty_result = get_loan_schedule_penalty($_POST['InvoiceNo_aib'], $_POST['customername_aib'], $_POST['transtype_aib']);
                                while ($pntyrow = db_fetch($pnty_result)){
                                    $MonthNo = CalcMonthsDue_pnlty($_POST['trans_date_aib'], $pntyrow["date_due"], $maturity_date);
                                    if($MonthNo != 0){
                                        $penalty = per_Penalty($MonthNo, ($pntyrow["principal_due"]- $partialpay));
                                        
                                        if($penalty > 0){
                                            add_loan_ledger($_POST['InvoiceNo_aib'], $_POST['customername_aib'], $pntyrow["loansched_id"], $_POST['transtype_aib'], ST_CUSTPAYMENT, 0, $penalty, 0, 0, $trans_date, $payment_no);
                                            update_loan_schedule($pntyrow["loansched_id"], $_POST['customername_aib'], $_POST['InvoiceNo_aib'], $_POST['transtype_aib'], "unpaid", 0, "paid");
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
                                $pnty_result = get_loan_schedule_penalty($_POST['InvoiceNo_aib'], $_POST['customername_aib'], $_POST['transtype_aib']);
                                while ($pntyrow = db_fetch($pnty_result)){
                                    $MonthNo = CalcMonthsDue_pnlty($_POST['trans_date_aib'], $pntyrow["date_due"], $maturity_date);
                                    if($MonthNo != 0){
                                        $penalty = per_Penalty($MonthNo, ($pntyrow["principal_due"] - $partialpay));
                                        $bal_tenderd_amount -= $penalty;

                                        if($bal_tenderd_amount >= 0){
                                            add_loan_ledger($_POST['InvoiceNo_aib'], $_POST['customername_aib'], $pntyrow["loansched_id"], $_POST['transtype_aib'], ST_CUSTPAYMENT, 0, $penalty, 0, 0, $trans_date, $payment_no);
                                            update_loan_schedule($pntyrow["loansched_id"], $_POST['customername_aib'], $_POST['InvoiceNo_aib'], $_POST['transtype_aib'], "unpaid", 0, "paid");
                                            $tenderd_amount -= $penalty;
                                            $GLPenalty += $penalty;
                                            $partialpay = 0;
                                        }else{
                                            $nextPenaltyBal = ($penalty - $tenderd_amount);
                                            add_loan_ledger($_POST['InvoiceNo_aib'], $_POST['customername_aib'], $pntyrow["loansched_id"], $_POST['transtype_aib'], ST_CUSTPAYMENT, 0, $tenderd_amount, 0, 0, $trans_date, $payment_no);
                                            update_loan_schedule($pntyrow["loansched_id"], $_POST['customername_aib'], $_POST['InvoiceNo_aib'], $_POST['transtype_aib'], "unpaid", $nextPenaltyBal, "partial");
                                            
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
                        $RebateAmount = GetRebate($_POST['trans_date_aib'], $myrow["date_due"], $debtor_loans["rebate"]);
                        
                        if($myrow["status"] == "partial"){
                            $thismonthAmort = ($myrow["principal_due"] - $partialpayment);

                            if(($tenderd_amount + $RebateAmount) == $thismonthAmort){

                                add_loan_ledger($_POST['InvoiceNo_aib'], $_POST['customername_aib'], $myrow["loansched_id"], $_POST['transtype_aib'], ST_CUSTPAYMENT, $thismonthAmort, 0, $RebateAmount, 0, $trans_date, $payment_no);
                                update_loan_schedule($myrow["loansched_id"], $_POST['customername_aib'], $_POST['InvoiceNo_aib'], $_POST['transtype_aib'], "paid", 0, "paid");
                                
                                $GLRebate += $RebateAmount;
                                $allocatedAmount += $thismonthAmort;
                                $thismonthAmort = $tenderd_amount = 0;

                            }elseif(($tenderd_amount + $RebateAmount) < $thismonthAmort){

                                add_loan_ledger($_POST['InvoiceNo_aib'], $_POST['customername_aib'], $myrow["loansched_id"], $_POST['transtype_aib'], ST_CUSTPAYMENT, $tenderd_amount, 0, 0, 0, $trans_date, $payment_no);
                                update_loan_schedule($myrow["loansched_id"], $_POST['customername_aib'], $_POST['InvoiceNo_aib'], $_POST['transtype_aib'], "partial");
                                
                                $allocatedAmount += $tenderd_amount;
                                $tenderd_amount = 0;

                            }else{

                                add_loan_ledger($_POST['InvoiceNo_aib'], $_POST['customername_aib'], $myrow["loansched_id"], $_POST['transtype_aib'], ST_CUSTPAYMENT, $thismonthAmort, 0, $RebateAmount, 0, $trans_date, $payment_no);
                                update_loan_schedule($myrow["loansched_id"], $_POST['customername_aib'], $_POST['InvoiceNo_aib'], $_POST['transtype_aib'], "paid", 0, "paid");
                                
                                $GLRebate += $RebateAmount;
                                $allocatedAmount += $thismonthAmort;
                                $tenderd_amount += $RebateAmount;
                                $tenderd_amount -= $thismonthAmort;
                                
                            }

                        }else{

                            if($tenderd_amount == ($myrow["principal_due"] - $RebateAmount)){

                                add_loan_ledger($_POST['InvoiceNo_aib'], $_POST['customername_aib'], $myrow["loansched_id"], $_POST['transtype_aib'], ST_CUSTPAYMENT, $myrow["principal_due"], 0, $RebateAmount, 0, $trans_date, $payment_no);
                                update_loan_schedule($myrow["loansched_id"], $_POST['customername_aib'], $_POST['InvoiceNo_aib'], $_POST['transtype_aib'], "paid", 0, "paid");
                                
                                $allocatedAmount += $myrow["principal_due"];
                                $GLRebate += $RebateAmount;
                                $tenderd_amount = 0;

                            }elseif($tenderd_amount < ($myrow["principal_due"] - $RebateAmount)){
                                
                                add_loan_ledger($_POST['InvoiceNo_aib'], $_POST['customername_aib'], $myrow["loansched_id"], $_POST['transtype_aib'], ST_CUSTPAYMENT, $tenderd_amount, 0, 0, 0, $trans_date, $payment_no);
                                update_loan_schedule($myrow["loansched_id"], $_POST['customername_aib'], $_POST['InvoiceNo_aib'], $_POST['transtype_aib'], "partial");
                                
                                $allocatedAmount += $tenderd_amount;
                                $tenderd_amount = 0;

                            }else{
                                add_loan_ledger($_POST['InvoiceNo_aib'], $_POST['customername_aib'], $myrow["loansched_id"], $_POST['transtype_aib'], ST_CUSTPAYMENT, $myrow["principal_due"], 0, $RebateAmount, 0, $trans_date, $payment_no);
                                update_loan_schedule($myrow["loansched_id"], $_POST['customername_aib'], $_POST['InvoiceNo_aib'], $_POST['transtype_aib'], "paid", 0, "paid");
                                
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
            add_cust_allocation($allocatedAmount, ST_CUSTPAYMENT, $payment_no, $_POST['transtype_aib'], $_POST['InvoiceNo_aib'], $_POST['customername_aib'], $_POST['trans_date_aib']);
            update_debtor_trans_allocation($_POST['transtype_aib'], $_POST['InvoiceNo_aib'], $_POST['customername_aib']);

            $term = get_mos_term($_POST['InvoiceNo_aib'], $_POST['customername_aib'], $_POST['transtype_aib']);
            if($term <= 3) {
                $debtors_account = $company_prefs["ar_reg_current_account"];
            }else{
                $debtors_account = $company_prefs["debtors_act"];
            }

            if ($_POST['tenderd_amount_aib'] != 0)	{
                /* Now Credit Debtors account with receipts + discounts */
                //$GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $branch_data["receivables_account"], 0, 0, -($GLRebate + ($_POST['tenderd_amount'] - $GLPenalty)), $_POST['customername'], "Cannot insert a GL transaction for the debtors account credit");
                $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date_aib'], $debtors_account, 0, 0, -$_POST['tenderd_amount_aib'], $_POST['customername_aib'], "Cannot insert a GL transaction for the debtors account credit", 0, null, null, 0, $_POST['InvoiceNo_aib']);
            }
            //deferred -> debit; realized -> credit
            if($grossPM > 0){
                $PM_amount = ($_POST['tenderd_amount_aib'] *  $grossPM);
                if($PM_amount != 0){
                    $GLtotal += add_gl_trans(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date_aib'], $company_prefs["dgp_account"], 0, 0, '', check_isempty($PM_amount), null, PT_CUSTOMER, $_POST['customername_aib'], "", 0, null, null, 0, $_POST['InvoiceNo_aib']);
                    $GLtotal += add_gl_trans(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date_aib'], $company_prefs["rgp_account"], 0, 0, '', -check_isempty($PM_amount), null, PT_CUSTOMER, $_POST['customername_aib'], "", 0, null, null, 0, $_POST['InvoiceNo_aib']);
                }
            }
            /*Post a balance post if $total != 0 due to variance in AR and bank posted values*/
            if ($GLtotal != 0)
            {
                $variance_act = get_company_pref('exchange_diff_act');
                add_gl_trans(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date_aib'],	$variance_act, 0, 0, '', -$GLtotal, null, PT_CUSTOMER, $_POST['customername_aib'], "", 0, null, null, 0, $_POST['InvoiceNo_aib']);
            }

            update_status_debtor_trans($_POST['InvoiceNo_aib'], $_POST['customername_aib'], $_POST['transtype_aib'], "part-paid");
            update_status_debtor_loans($_POST['InvoiceNo_aib'], $_POST['customername_aib'], "part-paid");

            //allocate payment to
            update_debtor_trans_allocation(ST_CUSTPAYMENT, $payment_no, $_POST['customername_aib']);
            update_status_interbranch_trans($_POST['syspk_aib'], $_SESSION["wa_current_user"]->username, 'approved', $payment_no, ST_CUSTPAYMENT, null);
            
            $interBTrans = get_interB_transNo_from($_POST['syspk_aib']);
            update_status_interbranch_trans_HO($interBTrans['ref_no'], $_SESSION["wa_current_user"]->username, 'approved', $payment_no, ST_CUSTPAYMENT, $interBTrans['transno_from_branch'], $interBTrans['trantype_from_branch'] );

            $dsplymsg = _("Customer payment has been allocated successfully...");        
        }
        echo '({"success":"true","message":"'.$dsplymsg.'"})';
    }else{
        echo '({"failure":"false","message":"'.$dsplymsg.'"})';
    }
    return;
}

page(_($help_context = "Payments Allocation Inquiry"));

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

