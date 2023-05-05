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
$page_security = 'SA_APCUSTDPOPEN';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/lending/includes/lending_cfunction.inc");

//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root ."/js/customer_ap_opening.js");

//----------------------------------------------: for grid js :---------------------------------------
if(isset($_GET['getReference'])){
    //$reference = $Refs->get_next(ST_CUSTPAYMENT, GetReferenceID('CR'), array('date' => Today()), true, ST_CUSTPAYMENT);
    $reference = $Refs->get_next(ST_CUSTPAYMENT, null, array('date' => Today()));
    echo '({"success":"true","reference":"'.$reference.'"})';
    //echo $_POST['debtor_id'];
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
if(isset($_GET['get_PaymentType']))
{
    if($_GET['type'] == "amort"){
        $status_array[] = array('id'=>"down",
                               'name'=>"Down Payment"
                            );
        $status_array[] = array('id'=>"amort",
                                'name'=>"Amort Payment"
                            );
    }elseif($_GET['type'] == "interb"){
        $status_array[] = array('id'=>"other",
                                'name'=>"Other Payment"
                            );
    }elseif($_GET['type'] == "downp"){
        $status_array[] = array('id'=>"down",
                               'name'=>"Down Payment"
                            );
    }elseif($_GET['type'] == "cash"){
        $status_array[] = array('id'=>"other",
                                'name'=>"Other Payment"
                            );
        $status_array[] = array('id'=>"adjmt",
                            'name'=>"Adjustment"
                        );
    }

    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_CollectionType']))
{
    /*if($_GET['type'] == "amort"){
        $status_array[] = array('id'=>"office",
                               'name'=>"Office Collection"
                            );
        $status_array[] = array('id'=>"field",
                                'name'=>"Field Collection"
                            );
    }elseif($_GET['type'] == "interb"){
        $status_array[] = array('id'=>"interb",
                                'name'=>"Inter-branch Collection"
                            );
    }*/
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
if(isset($_GET['get_IntoBank']))
{
    $result = get_CPbank_accounts();
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow["id"],
                               'name'=>$myrow["bank_account_name"],
                               'type'=>$myrow["account_code"]
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_BankB']))
{
    $result = get_bank_trans_branch();
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('value'=>$myrow["bank_branch"]);
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return; 
}
if(isset($_GET['get_InvoiceNo']))
{
    if($_GET['tag'] == "adjustment"){
        $result = get_invoice_per_adjustment($_GET['debtor_id']);
    }else{
        $result = get_invoice_per_customer($_GET['debtor_id'], $_GET['tag']);
    }
    
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
if(isset($_GET['get_Item_details']))
{
    if($_GET['transtype'] == ST_SALESRETURN){
        $result = get_item_detials_adjustment($_GET['transNo'], $_GET['transtype']);
    }else{
        $result = get_item_detials($_GET['transNo'], $_GET['transtype']);
    }
    
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
if(isset($_GET['get_Customer']))
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
if(isset($_GET['get_schedule']))
{
    //$colltype = $_GET['colltype'];
    $TotalRebate = 0;

    $result = get_deptor_loan_schedule($_GET['transNo'],$_GET['debtor_no'], $_GET['transtype'], false);
    $total = DB_num_rows($result);
    $absAmount = 0;

    $lastpayment_date = get_debtor_last_payment_date($_GET['transNo'], $_GET['transtype'], $_GET['debtor_no']);
    $penltymos = mos_interval($lastpayment_date, $_GET['transdate']);

    while ($myrow = db_fetch($result)) {
        $PartialPayment = $Penalty = $PenaltyBal = $RebateAmount = 0;
        
        $Totalpayment = $myrow["amortization_amount"];
        $TotalRunBal = $myrow["total_runbal"];
        $PenaltyBal = $myrow["penalty_balance"];
        
        $totalAR = get_total_due($_GET['transtype'], $_GET['transNo']);
        $totalPaymentApplied = get_payment_appliedSUM($_GET['transtype'],$_GET['transNo'], 0);
        
        $TotalBalance = ($totalAR - $totalPaymentApplied);

        if($myrow['status'] != 'paid'){
            //check if can avail rebate
            if($_GET['colltype'] != 2){
                $RebateAmount = GetRebate($_GET['transdate'], $myrow["date_due"], $myrow["rebate"]);
                if($RebateAmount != 0){
                    $Totalpayment -= $RebateAmount;
                    $TotalRebate += $RebateAmount;
                }
            }
            //get amount applied if partial payment
            if($myrow['status'] == 'partial'){
                $PartialPayment = get_payment_appliedSUM($_GET['transtype'],$_GET['transNo'], $myrow["loansched_id"]);
                $TotalRunBal -= $PartialPayment;
                $Totalpayment -= $PartialPayment;
            }else{
                $PartialPayment=0;
            }
            //check if there's a penaltybalance
            if($myrow["penalty_balance"] != 0){
                $Totalpayment += $myrow["penalty_balance"];
            }else{
                if(date('Y-m-d', strtotime($myrow['maturity_date'])) < date('Y-m-d', strtotime($_GET['transdate']))){
                    $payAppliedInfo = get_payment_appliedInfo($_GET['transtype'],$_GET['transNo'], $myrow["loansched_id"]);

                    $MonthNo = CalculateMonthsPastDue($_GET['transdate'], $myrow["date_due"], $payAppliedInfo['date_paid']);
                    if($MonthNo != 0){
                        $Penalty = CalculatePenalty($_GET['transdate'], $myrow["date_due"], $payAppliedInfo['date_paid'], $_GET['transNo'], $myrow["amortization_amount"], $TotalBalance, $MonthNo, 'PASTDUE', false);
                        //echo "x1> ".$Penalty;
                    }
                }else{
                    $MonthNo = CalcMonthsDue_pnlty($_GET['transdate'], $myrow["date_due"], $myrow["maturity_date"]);
                    if($MonthNo != 0){
                        $TotalBalance = $Totalpayment;
                        //$Penalty = CalculatePenalty($_GET['transdate'], $myrow["maturity_date"], $myrow["amortization_amount"], $TotalBalance, $MonthNo, 'DUE', false);
                        //echo $myrow['penalty_status'];
                        if($myrow['penalty_status'] == 'paid'){

                            $Penalty = per_Penalty($penltymos, ($myrow["amortization_amount"] - $PartialPayment));
                            //echo "-penltymos-". $penltymos."</br>";
                        }else{
                            $Penalty = per_Penalty($MonthNo, ($myrow["amortization_amount"] - $PartialPayment));
                            //echo "MonthNo-". $MonthNo."</br>";
                        }
                    }
                }
                $Totalpayment += $Penalty;
            }
        }

        $status_array[] = array('loansched_id'=>$myrow["loansched_id"],
                               'debtor_id'=>$myrow["debtor_no"],
                               'trans_no'=>$myrow["trans_no"],
                               'date_due'=>date('m-d-Y', strtotime($myrow["date_due"])),
                               'maturity_date'=>date('m-d-Y', strtotime($myrow["maturity_date"])),
                               'mosterm'=>$myrow["month_no"],
                               'amortization'=>$myrow["amortization_amount"],
                               'rebate'=>$RebateAmount,
                               'totalrebate'=>$TotalRebate,
                               'penalty'=>$Penalty,
                               'penaltyBal'=>$PenaltyBal,
                               'partialpayment'=>$PartialPayment,
                               'totalpayment'=>$Totalpayment,
                               'runningbalance'=>$TotalRunBal,
                               'status'=>$myrow["status"]
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_aloc']))
{
    $PartialPayment = $Penalty = $PartialBal = $RebateAmount = $TotalBalance = $DP_Discount = 0;
    
    $ar_balance = check_ar_balance($_GET['transNo'], $_GET['transtype']);

    if($_GET['pay_type'] == "down"){
        $result = get_amort_downpayment($_GET['transNo'], $_GET['debtor_no'], $_GET['transtype']);
        $total = DB_num_rows($result);
        $dprow = db_fetch($result);
        
        if($dprow['status'] == 'unpaid'){
            $DP_Discount = ($dprow["discount_downpayment"] + $dprow["discount_downpayment2"]);
        }

        $Totalpayment = ($dprow["downpayment_amount"] - $DP_Discount);

        $paymentAppld = get_payment_appliedSUM($_GET['transtype'],$_GET['transNo'], $dprow["loansched_id"]);
        
        if($paymentAppld != 0){
            $PartialPayment = $paymentAppld;
            $Totalpayment -= $paymentAppld;
        }
        $grossPM = $dprow["profit_margin"];

        //check if can avail rebate
        //$RebateAmount = GetRebate($_GET['transdate'], $dprow["date_due"], $dprow["rebate"]);

        $status_array[] = array('loansched_id'=>$dprow["loansched_id"],
            'debtor_id'=>$_GET['debtor_no'],
            'trans_no'=>$_GET['transNo'],
            'date_due'=>date('m-d-Y', strtotime($dprow["date_due"])),
            'maturity_date'=>date('m-d-Y', strtotime($dprow["maturity_date"])),
            'mosterm'=>$dprow["month_no"],
            'downpayment'=>$dprow["downpayment_amount"],
            'amortization'=>0,
            'ar_due'=>$dprow["total_runbal"],
            'rebate'=>0,
            'penalty'=>0,
            'penaltyBal'=>0,
            'partialpayment'=>$PartialPayment,
            'totalpayment'=>$Totalpayment,
            'alloc_amount'=>0,
            'dp_discount'=>$DP_Discount,
            'grossPM'=>$grossPM,
            'comptdGPM'=>0,
            'balance'=>$ar_balance
        );
    }else{
        $result = get_deptor_loan_schedule($_GET['transNo'], $_GET['debtor_no'], $_GET['transtype'], false);
        
        $total = DB_num_rows($result);
        $absAmount = 0;
        $schedrow = db_fetch($result);
      
        $mos = mos_interval($schedrow["date_due"], $_GET['transdate']);
        if($mos > 0){
            $value = check_transdate($schedrow["maturity_date"], $_GET['transdate']);
            if($value == 1){
                $Totalpayment = ($schedrow["amortization_amount"] * ($mos + 1));
            }else{
                $mos = mos_interval($schedrow["date_due"], $schedrow["maturity_date"]);
                if($mos > 0){
                    $Totalpayment = ($schedrow["amortization_amount"] * ($mos + 1));
                }
            }
            
        }else{
            $Totalpayment = $schedrow["amortization_amount"];
        }
        //echo "ads".$Totalpayment;
        $TotalRunBal = $schedrow["total_runbal"];
        $PenaltyBal = $schedrow["penalty_balance"];
    
        $payAppliedInfo = get_payment_appliedInfo($_GET['transtype'],$_GET['transNo'], $schedrow["loansched_id"]);
        $paymentAppld = get_payment_appliedSUM($_GET['transtype'],$_GET['transNo'], $schedrow["loansched_id"]);

        $lastpayment_date = get_debtor_last_payment_date($_GET['transNo'], $_GET['transtype'], $_GET['debtor_no']);
        $count_paid_penalty = count_paid_penalty($_GET['transNo'], $_GET['transtype'], $_GET['debtor_no']);

        if($paymentAppld != 0){
            if($schedrow["amortization_amount"] > $paymentAppld){
                $PartialPayment = $paymentAppld;
                $TotalRunBal -= $PartialPayment;
                $Totalpayment -= $PartialPayment;
            }
        }
    
        //check if can avail rebate
        if($_GET['colltype'] != 2){
            $RebateAmount = GetRebate($_GET['transdate'], $schedrow["date_due"], $schedrow["rebate"]);
            if($RebateAmount != 0){
                $Totalpayment -= $RebateAmount;
            }
        }

        //for penalty
        //get month interval penalty last payment and trans date
        $penltymos = mos_interval($lastpayment_date, $_GET['transdate']);

        $MonthNo = CalcMonthsDue_pnlty($_GET['transdate'], $schedrow["date_due"], $schedrow["maturity_date"]);
        if($MonthNo != 0){
            $TotalBalance = $Totalpayment;
            $withpartial = $PartialPayment;
            $count=0;
            for ($MonthNo; $MonthNo >= 1; $MonthNo--) {
                //$Penalty += CalculatePenalty($_GET['transdate'], $schedrow["maturity_date"], $schedrow["amortization_amount"], $TotalBalance, $MonthNo, 'DUE', false);
                //echo $MonthNo." <:> ";

                if($withpartial != 0){
                    $due = ($schedrow["amortization_amount"] - $withpartial);
                    //echo "withpartial-".$withpartial." <:> ";
                    $withpartial = 0;
                }else{
                    $due = $schedrow["amortization_amount"];
                }
                if($count_paid_penalty != 0){
                    //echo "penltymos-".$penltymos." <:> ";
                    $Penalty += per_Penalty($penltymos, $due);
                    $count_paid_penalty--;
                    //echo "Penalty b-".$Penalty." = ". $penltymos. " * ". $due;
                }else{
                    $Penalty += per_Penalty($MonthNo, $due);
                    //echo "Penalty a-".$Penalty." = ". $MonthNo. " * ". $due;
                }
               
                //echo "</br>";
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
        'comptdGPM'=>$comptdGPM,
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
    
    $result = get_debtor_payment_info(ST_CUSTPAYMENT, $_GET['module_type'], $start, $limit, $_GET['query'], 0, false, 1);
     //for pagination
    $total_result = get_debtor_payment_info(ST_CUSTPAYMENT, $_GET['module_type'], $start, $limit, $_GET['query'], 0, true, 1);

    while ($myrow = db_fetch($result)) {
        $trans_typeTo = get_Trans_Type($myrow["trans_no"]);
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
                                'cashier'=>$myrow["cashier_user_id"]
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.DB_num_rows($total_result).'","result":'.$jsonresult.'})';
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
if(isset($_GET['get_interBPaymnt']))
{
    //into bank/debit to bank
    if(isset($_GET['debitTo'])){
        $gl_row = get_gl_account($_GET['debitTo']);
        $intoB_result = get_CPbank_accounts($_GET['debitTo']);
        $intoB_row = db_fetch($intoB_result);
        //echo $intoB_row['account_code'];
        $status_array[] = array('trans_date'=>date('Y-m-d',strtotime($_GET['date_issue'])),
                                'gl_code'=>$gl_row["account_code"],
                                'gl_name'=>$gl_row["account_name"],
                                'sl_code'=>$intoB_row['account_code'],
                                'sl_name'=>$intoB_row['bank_account_name'],
                                'debtor_id'=>$_GET['debtor_id'],
                                'debit_amount'=>$_GET['amount'],
                                'credit_amount'=>0,
                            );
    }
    //branch current/credit to
    if(isset($_GET['gl_account'])){
        $gl_row = get_gl_account($_GET['gl_account']);
        $status_array[] = array('trans_date'=>date('Y-m-d',strtotime($_GET['date_issue'])),
                                'gl_code'=>$gl_row["account_code"],
                                'gl_name'=>$gl_row["account_name"],
                                'sl_code'=>$_GET['branch_code'],
                                'sl_name'=>$_GET['branch_name'],
                                'debtor_id'=>$_GET['debtor_id'],
                                'debit_amount'=>0,
                                'credit_amount'=>$_GET['amount']
                            );
    }

    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_DownPaymnt']))
{
    $company_prefs = get_company_prefs();

    if($_GET['isview'] == "zHun"){
        $result = get_DP_NoAmort(ST_CUSTPAYMENT, $_GET['trans_no']);
        $total = DB_num_rows($result);

        while ($myrow = db_fetch($result)) {
            $branch = get_branch_info($myrow['mcode']);
            //var_dump(array_column($branch, 'name'));
            if($myrow["amount"] < 0){
                $credit = abs($myrow["amount"]);
                $debit = 0;
            }else{
                $credit = 0;
                $debit = $myrow["amount"];
            }
            $status_array[] = array('trans_date'=>date('Y-m-d',strtotime($myrow['trans_date'])),
                                    'gl_code'=>$myrow["account_code"],
                                    'gl_name'=>$myrow["account_name"],
                                    'sl_code'=>$myrow['mcode'],
                                    'sl_name'=>$_GET['branch_name'],
                                    'debtor_id'=>$myrow['person_id'],
                                    'debit_amount'=>$debit,
                                    'credit_amount'=>$credit
                                );
        }
    }else{
        //into bank/debit to bank //COH
        if(!empty($company_prefs["open_inty"])){
            $gl_row = get_gl_account($company_prefs["open_inty"]);
            $status_array[] = array('trans_date'=>date('Y-m-d',strtotime($_GET['date_issue'])),
                                    'gl_code'=>$gl_row["account_code"],
                                    'gl_name'=>$gl_row["account_name"],
                                    'sl_code'=>$_GET['branch_code'],
                                    'sl_name'=>$_GET['branch_name'],
                                    'debtor_id'=>$_GET['debtor_id'],
                                    'debit_amount'=>$_GET['amount'],
                                    'credit_amount'=>0,
                                );
        
        }
        //customer deposit
        if(!empty($company_prefs["downpaymnt_act"])){
            $gl_row = get_gl_account($company_prefs["downpaymnt_act"]);
            $status_array[] = array('trans_date'=>date('Y-m-d',strtotime($_GET['date_issue'])),
                                    'gl_code'=>$gl_row["account_code"],
                                    'gl_name'=>$gl_row["account_name"],
                                    'sl_code'=>$_GET['branch_code'],
                                    'sl_name'=>$_GET['branch_name'],
                                    'debtor_id'=>$_GET['debtor_id'],
                                    'debit_amount'=>0,
                                    'credit_amount'=>$_GET['amount']
                                );
        }
    }

    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_interb_view']))
{
    $result = get_interB_view(ST_CUSTPAYMENT, $_GET['trans_no']);
    $total = DB_num_rows($result);
    
    while ($myrow = db_fetch($result)) {
        $branch = get_branch_info($myrow['mcode']);
        //var_dump(array_column($branch, 'name'));
        $status_array[] = array('trans_date'=>date('Y-m-d',strtotime($myrow['trans_date'])),
                                'gl_code'=>$myrow["account_code"],
                                'gl_name'=>$myrow["account_name"],
                                'sl_code'=>$myrow['mcode'],
                                'sl_name'=>array_column($branch, 'name'),
                                'debtor_id'=>$myrow['person_id'],
                                'debit_amount'=>$myrow["amount"],
                                'credit_amount'=>$myrow["amount"]
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_alocCash']))
{
    $Totalpayment = $paymentAppld = $PartialPayment = $discount = 0;

    if($_GET['transtype'] == ST_SALESRETURN){
        $result = get_ar_adjustment($_GET['transNo'], $_GET['debtor_no'], $_GET['transtype']);
    }else{
        $result = get_ar_cash($_GET['transNo'], $_GET['debtor_no'], $_GET['transtype']);
    }
    
    $total = DB_num_rows($result);
    $cashrow = db_fetch($result);

    $paymentAppld = get_alloc_payment($_GET['transNo'], $_GET['debtor_no'], $_GET['transtype']);
    $discount = get_cash_discount($_GET['transNo'], $_GET['transtype']);

    if($discount != 0){
        $Totalpayment = ($cashrow["ar_amount"] - $discount);
    }else{
        $Totalpayment = $cashrow["ar_amount"];
    }
    
    if($paymentAppld != 0){
        $PartialPayment = $paymentAppld;
        $Totalpayment -= $paymentAppld;
    }

    $status_array[] = array('debtor_id'=>$cashrow["debtor_no"],
        'trans_no'=>$cashrow['trans_no'],
        'due_date'=>date('m-d-Y', strtotime($cashrow["tran_date"])),
        'ar_due'=>$cashrow["ar_amount"],
        'partialpayment'=>$PartialPayment,
        'totalpayment'=>$Totalpayment,
        'alloc_amount_cash'=>0,
        'balance'=>0,
        'cash_discount'=>$discount
    );

    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_alocCash_view']))
{
    $result = get_ar_cash_alloc($_GET['transNo'], $_GET['debtor_no'], $_GET['transtype']);
    $total = DB_num_rows($result);
    $cashrow = db_fetch($result);

    $status_array[] = array('debtor_id'=>$cashrow["debtor_no"],
        'trans_no'=>$cashrow['trans_no'],
        'due_date'=>date('m-d-Y', strtotime($cashrow["tran_date"])),
        'ar_due'=>$cashrow["ov_amount"],
        'partialpayment'=>0,
        'totalpayment'=>0,
        'alloc_amount_cash'=>$cashrow["amt"],
        'balance'=>($cashrow["ov_amount"] - $cashrow["alloc"])
    );

    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
//----------------------------------------------------: insert, update, delete :-------------------------------------------

if(isset($_GET['submitBEGDP']))
{
    //initialise no input errors assumed initially before we proceed
    //0 is by default no errors
    $InputError = 0;

    if (empty($_POST['moduletype_dp']) || empty($_POST['name_dp']) || empty($_POST['ref_no_dp'])) {
        $InputError = 1;
        $dsplymsg = _('Some fields are empty or contain an improper value. Please reload the page and fill up the required field.');
    }
    if (empty($_POST['customercode_dp'])) {
        $InputError = 1;
        $dsplymsg = _('Customer code must not be empty.');
    }
    if (empty($_POST['customername_dp'])) {
        $InputError = 1;
        $dsplymsg = _('Customer name must not be empty.');
    }
    if (empty($_POST['trans_date_dp'])) {
        $InputError = 1;
        $dsplymsg = _('Transaction date must not be empty.');
    }else{
        $trans_date = date('Y-m-d',strtotime($_POST['trans_date_inb']));
    }
    if (empty($_POST['receipt_no_dp'])) {
        $InputError = 1;
        $dsplymsg = _('CR number must not be empty.');
    }
    /*if (empty($_POST['intobankacct_dp'])) {
        $InputError = 1;
        $dsplymsg = _('Into bank account must not be empty.');
    }*/
    if (empty($_POST['total_amount_dp'])) {
        $InputError = 1;
        $dsplymsg = _('Total amount must not be empty.');
    }
    if (empty($_POST['tenderd_amount_dp'])) {
        $InputError = 1;
        $dsplymsg = _('Tendered amount must not be empty.');
    }
    if (empty($_POST['remarks_dp'])) {
        $InputError = 1;
        $dsplymsg = _('remarks must not be empty.');
    }
    if (empty($_POST['paymentType_dp'])) {
        $InputError = 1;
        $dsplymsg = _('Payment type must not be empty.');
    }
    if (empty($_POST['cashier_dp'])) {
        $InputError = 1;
        $dsplymsg = _('Cashier must not be empty.');
    }
    if (empty($_POST['preparedby_dp'])) {
        $InputError = 1;
        $dsplymsg = _('Prepared by must not be empty.');
    }
    if (empty($_POST['collectType_dp'])) {
        $InputError = 1;
        $dsplymsg = _('Collection type must not be empty.');
    }

    $DataOnGrid = stripslashes(html_entity_decode($_POST['DPDataOnGrid']));
    $objDataGrid = json_decode($DataOnGrid, true);
    
    //var_dump($objDataGrid);
    if (count($objDataGrid) == 0){
        $InputError = 1;
        $dsplymsg = _('Credit amount must not be empty! Please try again.');
    }

    //check data
	if(check_cr_number($_POST['receipt_no_dp'], 'CR')){
        $InputError = 1;
        $dsplymsg = _("CR number already exists.");
    }

    if ($InputError != 1){
        //begin_transaction();
        global $Refs;
        
        $BranchNo = get_newcust_branch($_POST['customername_dp'], $_POST['customercode_dp']);
        $bank = get_bank_account($_POST['intobankacct_dp']);

        $payment_no = write_customer_trans(ST_CUSTPAYMENT, 0, $_POST['customername_dp'], check_isempty($BranchNo['branch_code']), $_POST['trans_date_dp'], $_POST['ref_no_dp'],
                                    $_POST['tenderd_amount_dp'], 0 , 0, 0, 0, 0, 0, 0, null, 0, 0, 0, 0, null, 0, 0, 1, $_POST['paymentType_dp'], $_POST['collectType_dp'], $_POST['moduletype_dp']);

        add_bank_trans(ST_CUSTPAYMENT, $payment_no, $_POST['intobankacct_dp'], $_POST['ref_no_dp'], $_POST['trans_date_dp'], $_POST['tenderd_amount_dp'], PT_CUSTOMER, $_POST['customername_dp'],
                        $_POST['cashier_dp'], null, null, 0, null, 0, $_POST['receipt_no_dp'], $_POST['preparedby_dp'], null, null, $_POST['moduletype_dp']);

        add_comments(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date_dp'], $_POST['remarks_dp']);

        //gl entries
        foreach($objDataGrid as $value=>$data) {
            /* Now credit bank account with penalty */
            $company_prefs = get_company_prefs();
            if(!empty($data['gl_code'])){
                if($data['credit_amount'] != 0){
                    $amount = -$data['credit_amount'];
                }else{
                    $amount = $data['debit_amount'];
                }

                add_gl_trans(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date_dp'], $data['gl_code'], 0, 0, '', $amount, $bank['bank_curr_code'],
                                        PT_CUSTOMER, $_POST['customername_dp'], '', 0, $data['sl_code'], '', 0, 0);
            }
        }

        //auto allocate payment para dili na makita sa customer allocation
        auto_allocate_payment($payment_no, ST_CUSTPAYMENT, $_POST['tenderd_amount_dp']);

        $Refs->save(ST_CUSTPAYMENT, $payment_no, $_POST['ref_no_dp']);

        $dsplymsg = _("Downpayment has been successfully entered...</br> Reference No.: ".$payment_no);
        echo '({"success":"true","message":"'.$dsplymsg.'"})';
    }else{

        echo '({"failure":"false","message":"'.$dsplymsg.'"})';
    }
    return;
}

//------------------------------------------------------------------------------------------------------
//simple_page_mode(true);
page(_($help_context = "AP Customer Deposit Opening"), false, false, "", null);

start_table(TABLESTYLE, "width='100%'");
   echo "<div id='ext-form'></div>";
   echo "<style type='text/css' media='screen'>
            .x-form-text-default.x-form-textarea {
                line-height: 19px;
                min-height: 30px;
            }
        </style>";

end_table();
display_note(_(""), 0, 0, "class='overduefg'");
end_form();
end_page();