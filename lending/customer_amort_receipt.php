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
$page_security = 'SA_LCUSTAMORT';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/lending/includes/lending_cfunction.inc");

//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root ."/js/customer_amort_receipt.js");

//----------------------------------------------: for grid js :---------------------------------------
if(isset($_GET['getReference'])){
    $reference = $Refs->get_next(ST_CUSTPAYMENT, GetReferenceID('CR'), array('date' => Today()), true, ST_CUSTPAYMENT);
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
        $status_array[] = array('id'=>"other",
                                'name'=>"Other Payment"
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
                               'status'=>$myrow["status"],
                               'paylocation'=>$myrow["payment_location"]
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

                if($myrow["remarks"] == 'pastdue'){
                    $RebateAmount = 0;
                }

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
            'mosterm'=>$month_no,
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
            'comptdGPM'=>0,
            'balance'=>$ar_balance
        );
    }else{
        if($_GET['colltype'] == 5){
            //for term mode first payment
            $result = get_deptor_loan_schedule($_GET['transNo'], $_GET['debtor_no'], $_GET['transtype'], false);
            $total = DB_num_rows($result);
            $schedrow = db_fetch($result);
            
            $result = get_deptors_termmode($_GET['transNo'], $_GET['debtor_no'], $_GET['transtype']);
            $termoderow = db_fetch($result);

            $AmortDelay = $termoderow["amort_delay"];
            $Penalty = $termoderow["opportunity_cost"];
            $Totalpayment = $termoderow["amount_to_be_paid"];

            $TotalRunBal = $schedrow["total_runbal"];

            $status_array[] = array('loansched_id'=>$schedrow["loansched_id"],
                'debtor_id'=>$_GET['debtor_no'],
                'trans_no'=>$_GET['transNo'],
                'date_due'=>date('m-d-Y', strtotime($schedrow["date_due"])),
                'maturity_date'=>$schedrow["maturity_date"],
                'mosterm'=>$schedrow["month_no"],
                'amortization'=>$AmortDelay,
                'ar_due'=>$TotalRunBal,
                'rebate'=>$RebateAmount,
                'penalty'=>$Penalty,
                'penaltyBal'=>$PenaltyBal,
                'partialpayment'=>$PartialPayment,
                'totalpayment'=>$Totalpayment,
                'alloc_amount'=>0,
                'downpayment'=>0,
                'dp_discount'=>0,
                'grossPM'=>$termoderow["profit_margin"],
                'comptdGPM'=>$comptdGPM,
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
                
                if($schedrow["remarks"] == 'pastdue'){
                    $RebateAmount = 0;
                }

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
            //check if pastdue account
            $pdvalue = check_transdate($schedrow["maturity_date"], $_GET['transdate']);
            if($pdvalue == 0){
                $PastDueNo = CalculateMonthsPastDue($_GET['transdate'], $schedrow["date_due"], $schedrow["maturity_date"]);
                $Penalty += per_Penalty($PastDueNo, ($schedrow["amortization_amount"] * $PastDueNo));
                //echo 'pastduex-'.$PastDueNo;
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
    }

    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_custPayment']))
{
    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
    
    $result = get_debtor_payment_info(ST_CUSTPAYMENT, $_GET['module_type'], $start, $limit, $_GET['query']);
     //for pagination
    $total_result = get_debtor_payment_info(ST_CUSTPAYMENT, $_GET['module_type'], $start, $limit, $_GET['query'], 0, true);

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
                                'cashier'=>$myrow["cashier_user_id"],
                                'cashier_name'=>$myrow["real_name"]
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
                                    'sl_name'=>array_column($branch, 'name'),
                                    'debtor_id'=>$myrow['person_id'],
                                    'debit_amount'=>$debit,
                                    'credit_amount'=>$credit
                                );
        }
    }else{
        //into bank/debit to bank //COH
        if(isset($_GET['debitTo'])){
            $gl_row = get_gl_account($_GET['debitTo']);
            //$intoB_result = get_CPbank_accounts($_GET['debitTo']);
            //$intoB_row = db_fetch($intoB_result);
            //echo $intoB_row['account_code'];
            $status_array[] = array('trans_date'=>date('Y-m-d',strtotime($_GET['date_issue'])),
                                    'gl_code'=>$gl_row["account_code"],
                                    'gl_name'=>$gl_row["account_name"],
                                    'sl_code'=>$_GET['branch_code'], //$intoB_row['account_code'],
                                    'sl_name'=>$_GET['branch_name'], //$intoB_row['bank_account_name'],
                                    'debtor_id'=>$_GET['debtor_id'],
                                    'debit_amount'=>$_GET['amount'],
                                    'credit_amount'=>0,
                                );
        
        }
        //customer deposit
        $company_prefs = get_company_prefs();

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
//amortization type

if(isset($_GET['submit']))
{
    //initialise no input errors assumed initially before we proceed
    //0 is by default no errors
    $InputError = 0;
    $islastPay = 0;
    
    if (empty($_POST['transtype']) || empty($_POST['ref_no'])) {
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
    if (empty($_POST['receipt_no'])) {
        $InputError = 1;
        $dsplymsg = _('CR number must not be empty.');
    }
    if (empty($_POST['intobankacct'])) {
        $InputError = 1;
        $dsplymsg = _('Into bank account must not be empty.');
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
    if (empty($_POST['paymentType'])) {
        $InputError = 1;
        $dsplymsg = _('Payment type must not be empty.');
    }
    if (empty($_POST['cashier'])) {
        $InputError = 1;
        $dsplymsg = _('Cashier must not be empty.');
    }
    if (empty($_POST['preparedby'])) {
        $InputError = 1;
        $dsplymsg = _('Prepared by must not be empty.');
    }
    if (empty($_POST['collectType'])) {
        $InputError = 1;
        $dsplymsg = _('Collection type must not be empty.');
    }
    
    $invoice_date = get_debtor_invoice_date($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype']);
    if(check_two_dates($invoice_date, $_POST['trans_date']) < 0){
        $InputError = 1;
        $dsplymsg = _('Transaction date must be greater than invoice date.');
    }

    if($_POST['pay_type'] == "Check"){
        if (empty($_POST['check_date'])) {
            $InputError = 1;
            $dsplymsg = _('Check date must not be empty.');
        }
        if (empty($_POST['check_no'])) {
            $InputError = 1;
            $dsplymsg = _('Check number must not be empty.');
        }
        if (empty($_POST['bank_branch'])) {
            $InputError = 1;
            $dsplymsg = _('Bank branch must not be empty.');
        }
    }
    if($_POST['paymentType'] == "down"){
        if($_POST['total_amount'] < $_POST['tenderd_amount']){
            $InputError = 1;
            $dsplymsg = _('Tendered amount and total down payment amount must be equal');
        }
    }
    if($_POST['collectType'] == 5){
        if($_POST['total_amount'] != $_POST['tenderd_amount']){
            $InputError = 1;
            $dsplymsg = _('Tendered amount and total payment amount must be equal');
        }
    }

    $DataOnGrid = stripslashes(html_entity_decode($_POST['DataOnGrid']));
    $objDataGrid = json_decode($DataOnGrid, true);
    
    //var_dump($objDataGrid);
    if (count($objDataGrid) == 0){
        $InputError = 1;
        $dsplymsg = _('Credit amount must not be empty! Please try again.');
    }

    //check data
	if(check_cr_number($_POST['receipt_no'])){
        $InputError = 1;
        $dsplymsg = _("CR number already exists.");
    }
    //check balance amount > tendered amount
    $ar_balance = check_ar_balance($_POST['InvoiceNo'], $_POST['transtype']);

    //but first we need to check if pastdue or not
    $maturity_date = get_AR_maturity($_POST['InvoiceNo'], $_POST['customername']);
    
    $pdvalue = check_transdate($maturity_date, $_GET['transdate']);
    if($pdvalue != 0){
        if($_POST['total_amount']  < $_POST['tenderd_amount']){
            if($ar_balance < $_POST['tenderd_amount']){
                $InputError = 1;
                $dsplymsg = _('Tendered amount must be lesser than or equal to A/R amount balance.');
            }elseif(($ar_balance - $_POST['totalrebate']) < $_POST['tenderd_amount']){
                $InputError = 1;
                $dsplymsg = _('Tendered amount must be lesser than or equal to A/R amount balance.');
            }
        }
    }

    if ($InputError != 1){

        begin_transaction();
        
        global $Refs;
        $company_record = get_company_prefs();

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
            $GPM = $data['grossPM'];

            //CHECK IF LAST PAYMENT
            if(($ar_balance - $total_rebate) == $_POST['tenderd_amount']){
                $islastPay = 1;
            }elseif(($ar_balance + $total_penalty) == $_POST['tenderd_amount']){
                $islastPay = 1;
            }

            set_global_connection();
            
            if($_POST['paymentType'] == "down"){
            //---->>>>>>> down payment -------------------------
                //$result = get_amort_downpayment($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype']);
                //$dprow = db_fetch($result);
                $row_dpd = get_dp_discount($_POST['InvoiceNo'], $_POST['customername']);

                $payment_no = write_customer_payment(0, $_POST['customername'], check_isempty($BranchNo['branch_code']), $_POST['intobankacct'], $_POST['trans_date'], $_POST['ref_no'],
                                                $_POST['tenderd_amount'], check_isempty($dp_discount), $_POST['remarks'], 0, 0, input_num('bank_amount', $_POST['tenderd_amount']),
                                                0, $_POST['paymentType'], $_POST['collectType'], $_POST['moduletype'], $_POST['cashier'], $_POST['pay_type'],
                                                $_POST['check_date'], $_POST['check_no'], $_POST['bank_branch'], $_POST['InvoiceNo'], $_POST['receipt_no'], $_POST['preparedby'], null, null,
                                                $row_dpd["discount_downpayment"], $row_dpd["discount_downpayment2"]);

                add_cust_allocation(($_POST['tenderd_amount'] + check_isempty($dp_discount)), ST_CUSTPAYMENT, $payment_no, $_POST['transtype'], $_POST['InvoiceNo'], $_POST['customername'], $_POST['trans_date']);
                update_debtor_trans_allocation($_POST['transtype'], $_POST['InvoiceNo'], $_POST['customername']);

                if($_POST['paylocation'] =! "Lending"){
                    if($_POST['total_amount'] == $_POST['tenderd_amount']){
                        add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $Loansched_ID, $_POST['transtype'], ST_CUSTPAYMENT, ($_POST['tenderd_amount'] + check_isempty($dp_discount)), 0, 0, 0, $trans_date, $payment_no);
                        update_loan_schedule($Loansched_ID, $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "paid", 0, "paid");
                        update_dp_status($_POST['InvoiceNo'], $_POST['transtype']);
    
                        $tenderd_amount = 0;
    
                    }elseif($_POST['total_amount'] > $_POST['tenderd_amount']) {
                        $nextDPBal = ($_POST['total_amount'] - $_POST['tenderd_amount']);
    
                        add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $Loansched_ID, $_POST['transtype'], ST_CUSTPAYMENT, $_POST['tenderd_amount'], 0, 0, 0, $trans_date, $payment_no);
                        update_loan_schedule($Loansched_ID, $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "partial");
                    }
                }else{
                    update_dp_status($_POST['InvoiceNo'], $_POST['transtype']);
                }

                if($GPM != 0){
                    $dgp_account = $company_record["dgp_account"];
                    $rgp_account = $company_record["rgp_account"];

                    $DeferdAmt = ($_POST['tenderd_amount'] + $dp_discount) * $GPM;

                    $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $dgp_account, 0, 0, $DeferdAmt, $_POST['customername'], "Cannot insert a GL transaction for the DGP account debit", 0, null, null, 0, $_POST['InvoiceNo']);
                    $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $rgp_account, 0, 0, -$DeferdAmt, $_POST['customername'], "Cannot insert a GL transaction for the RGP account credit", 0, null, null, 0, $_POST['InvoiceNo']);
                }

                $dsplymsg = _("Down-payment has been successfully entered...");

            }else{
            //---->>>>>>> amortization payment -------------------------
                $GLPenalty = $GLRebate = $GLtotal = $partialpay = $allocatedAmount = $RebateAmount = $pdlast_insrtd_id = 0;

                $partialpay = $partialpayment;
                $tenderd_amount = $_POST['tenderd_amount'];

                $BranchNo = get_newcust_branch($_POST['customername'], $_POST['customercode']);
                $bank = get_bank_account($_POST['intobankacct']);
                $bank_gl_account = get_bank_gl_account($_POST['intobankacct']);
                $branch_data = get_branch_accounts($BranchNo['branch_code']);

                //check if pastdue account
                $pdvalue = check_transdate($schedrow["maturity_date"], $_GET['transdate']);
                if($pdvalue == 0){
                    $PastDueMos = CalculateMonthsPastDue($_GET['transdate'], $schedrow["date_due"], $schedrow["maturity_date"]);
                    //$Penalty += per_Penalty($PastDueNo, ($schedrow["amortization_amount"] * $PastDueNo));
                }
                if($_POST['collectType'] == 5){
                    //term mode adjustment
                    $payment_no = write_customer_trans(ST_CUSTPAYMENT, 0, $_POST['customername'], check_isempty($BranchNo['branch_code']), $_POST['trans_date'], $_POST['ref_no'],
                                                $_POST['tenderd_amount'], 0 , 0, 0, 0, 0, 0, 0, null, 0, 0, 0, 0, null, 0, 0, 0, $_POST['paymentType'], $_POST['collectType'], $_POST['moduletype']);
                    
                    add_bank_trans(ST_CUSTPAYMENT, $payment_no, $_POST['intobankacct'], $_POST['ref_no'], $_POST['trans_date'], $_POST['tenderd_amount'], PT_CUSTOMER, $_POST['customername'],
                                        $_POST['cashier'], $_POST['pay_type'], $_POST['check_date'], $_POST['check_no'], $_POST['bank_branch'], $_POST['InvoiceNo'], $_POST['receipt_no'], $_POST['preparedby']);

                    add_comments(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $_POST['remarks']);

                    /* Bank account entry first */
                    $GLtotal += add_gl_trans(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $bank_gl_account, 0, 0, '', $_POST['tenderd_amount'],  $bank['bank_curr_code'], PT_CUSTOMER, $_POST['customername'], "", 0, null, null, 0, $_POST['InvoiceNo']);

                    $result = get_loan_schedule($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype']);
                    $lastpayment_date = get_debtor_last_payment_date($_POST['InvoiceNo'], $_POST['transtype'], $_POST['customername']);
                    $count_paid_penalty = count_paid_penalty($_POST['InvoiceNo'], $_POST['transtype'], $_POST['customername']);
                    $penltymos = mos_interval($lastpayment_date, $_POST['trans_date']);
    
                    $trmdresult = get_deptors_termmode($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype']);
                    $termoderow = db_fetch($trmdresult);
                    
                    while ($myrow = db_fetch($result)) {
                        add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $myrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, $termoderow["amort_delay"], $termoderow["opportunity_cost"], 0, 0, $trans_date, $payment_no);
                        update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "paid", 0, "paid");

                        break;
                    }
                    
                    //allocate payment to trans number sales invoice
                    add_cust_allocation($termoderow["amort_delay"], ST_CUSTPAYMENT, $payment_no, $_POST['transtype'], $_POST['InvoiceNo'], $_POST['customername'], $_POST['trans_date']);
                    update_debtor_trans_allocation($_POST['transtype'], $_POST['InvoiceNo'], $_POST['customername']);

                    //save reference
                    $Refs->save(ST_CUSTPAYMENT, $payment_no, $_POST['ref_no']);

                    /* Now credit bank account with penalty */
                    $penalty_act = get_company_pref('penalty_act');
                    $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $penalty_act, 0, 0, -$termoderow["opportunity_cost"], $_POST['customername'], "Cannot insert a GL transaction for the payment penalty credit", 0, null, null, 0, $_POST['InvoiceNo']);
                    
                    $term = get_mos_term($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype']);
                    if($term <= 3) {
                        $debtors_account = $company_record["ar_reg_current_account"];
                    }else{
                        $debtors_account = $company_record["debtors_act"];
                    }
                    //A/R
                    $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $debtors_account, 0, 0, -$termoderow["amort_delay"], $_POST['customername'], "Cannot insert a GL transaction for the debtors account credit", 0, null, null, 0, $_POST['InvoiceNo']);

                    if($GPM != 0){
                        $dgp_account = $company_record["dgp_account"];
                        $rgp_account = $company_record["rgp_account"];
    
                        $DeferdAmt = $termoderow["amort_delay"] * $GPM;
    
                        $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $dgp_account, 0, 0, $DeferdAmt, $_POST['customername'], "Cannot insert a GL transaction for the DGP account debit", 0, null, null, 0, $_POST['InvoiceNo']);
                        $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $rgp_account, 0, 0, -$DeferdAmt, $_POST['customername'], "Cannot insert a GL transaction for the RGP account credit", 0, null, null, 0, $_POST['InvoiceNo']);
                    }

                    update_status_debtor_trans($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype'], "part-paid");
                    update_termmode_status($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype'], "part-paid");

                }else{
                    
                    $payment_no = write_customer_trans(ST_CUSTPAYMENT, 0, $_POST['customername'], check_isempty($BranchNo['branch_code']), $_POST['trans_date'], $_POST['ref_no'],
                                                $_POST['tenderd_amount'], 0 , 0, 0, 0, 0, 0, 0, null, 0, 0, 0, 0, null, 0, 0, 0, $_POST['paymentType'], $_POST['collectType'], $_POST['moduletype']);

                    add_bank_trans(ST_CUSTPAYMENT, $payment_no, $_POST['intobankacct'], $_POST['ref_no'], $_POST['trans_date'], $_POST['tenderd_amount'], PT_CUSTOMER, $_POST['customername'],
                                    $_POST['cashier'], $_POST['pay_type'], $_POST['check_date'], $_POST['check_no'], $_POST['bank_branch'], $_POST['InvoiceNo'], $_POST['receipt_no'], $_POST['preparedby']);
                    
                    add_comments(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $_POST['remarks']);
    
                    /* Bank account entry first */
                    $GLtotal += add_gl_trans(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $bank_gl_account, 0, 0, '', $_POST['tenderd_amount'],  $bank['bank_curr_code'], PT_CUSTOMER, $_POST['customername'], "", 0, null, null, 0, $_POST['InvoiceNo']);
                    
                    $result = get_loan_schedule($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype']);
                    
                    $lastpayment_date = get_debtor_last_payment_date($_POST['InvoiceNo'], $_POST['transtype'], $_POST['customername']);
                    $count_paid_penalty = count_paid_penalty($_POST['InvoiceNo'], $_POST['transtype'], $_POST['customername']);

                    //change date to due date
                    $lpmos = date('m', strtotime($lastpayment_date));
                    $lpyer = date('Y', strtotime($lastpayment_date));
                    $duedate = date('d', date('Y/m/d', strtotime($date_due)));
                    
                    $lastpayment_date = date('Y/m/d', strtotime($lpyer.'-'.$lpmos.'-'.$duedate));

                    $penltymos = mos_interval($lastpayment_date, $_POST['trans_date']);

                    while ($myrow = db_fetch($result)) {
                        //if($myrow["status"] == 'partial'){
                        //    $penltymos = mos_interval($myrow["date_due"], $_POST['trans_date']);
                        //}
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
                                    //echo $tenderd_amount .'-b'.'</br>';
                                }else{
                                    if($tenderd_amount > $total_penalty){
                                        $tenderd_amount -= $total_penalty;  //modify on 1-4-2022 kay sayop ang ma post didto sa schedule. dapat ang pinalty gyud nga na post dili ang katong sa viewing grid
                                        $bal_penalty = $total_penalty;
    
                                        //check penalty for unpaid monthly amort
                                        if($count_paid_penalty != 0){
                                            //echo "sad".$count_paid_penalty.'</br>';
                                            $pnty_result = get_paid_penalty($_POST['InvoiceNo'], $_POST['transtype'], $_POST['customername']);
    
                                            while ($pntyrow = db_fetch($pnty_result)){
                                                $penalty = per_Penalty($penltymos, ($pntyrow["principal_due"]- $partialpay));
    
                                                if($penalty > 0){
                                                    add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $pntyrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, 0, $penalty, 0, 0, $trans_date, $payment_no);
                                                    //echo "paid-penalty-".$penalty.'</br>';
                                                    $total_penalty -= $penalty;
                                                    $GLPenalty += $penalty;
                                                    $partialpay = 0;
                                                }
                                            }
                                            $count_paid_penalty = $penltymos = 0;
                                        }
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
                                        //past due check
                                        if($PastDueMos != 0){
                                            if($total_penalty != 0){
                                                $sched_maxinfo = get_debtor_schedule_maxinfo($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype']);
                                                $datedue = date('Y-m-d', strtotime("+1 months", strtotime($sched_maxinfo['date_due'])));
                                                $weekday = date('D', strtotime($datedue));
                                                $month_no = ($sched_maxinfo['month_no'] + 1);
                                                //need to insert record to table schedule for penalty past due reference
                                                add_loan_schedule($_POST['transtype'], $_POST['InvoiceNo'], $_POST['customername'], $month_no,
                                                                    $datedue, $weekday, $sched_maxinfo['principal_due'], 0, 0, 0, 0, 0, 'unpaid', 'pastdue');
                                                //SELECT LAST_INSERT_ID();
                                                $pdlast_insrtd_id = get_debtor_schedule_last_inserted_id_pd($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype'], $month_no);

                                                if($pdlast_insrtd_id != 0){
                                                    add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $pdlast_insrtd_id, $_POST['transtype'], ST_CUSTPAYMENT, 0, $total_penalty, 0, 0, $trans_date, $payment_no);
                                                    update_loan_schedule($pdlast_insrtd_id, $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "unpaid", 0, "paid");
                                                
                                                    $total_penalty = 0;
                                                }else{
                                                    echo $total_penalty;
                                                }
                                            }
                                        }
                                        if($total_penalty != 0){
                                            $tenderd_amount += $total_penalty;
                                        }else{
                                            $bal_penalty = $total_penalty = 0;
                                        }
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
                                //$tenderd_amount -= $GLPenalty;
                            }
                            //no more penalty
                            if($tenderd_amount > 0){
                                //check if maka kuha ba ug rebate
                                if($_POST['collectType'] != 2){
                                    $RebateAmount = GetRebate($_POST['trans_date'], $myrow["date_due"], $debtor_loans["rebate"]);
                                }
                                $penaltyBal = get_Penalty_balance($_POST['transtype'], $_POST['InvoiceNo'], $myrow["loansched_id"]);
    
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
                                        update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "partial", 0, $penaltyBal['penalty_status']);
                                        
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
                                        update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "partial", 0, $penaltyBal['penalty_status']);
                                        
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
                    //for pastdue
                    if($tenderd_amount > 0){
                        $resultpd = get_loan_schedule_pastdue($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype']);

                        while ($pdrow = db_fetch($resultpd)){
                            $RebateAmount = 0;

                            if($pdrow["status"] == "partial"){
                                $thismonthAmort = ($pdrow["principal_due"] - $partialpayment);

                                if(($tenderd_amount + $RebateAmount) == $thismonthAmort){

                                    add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $pdrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, $thismonthAmort, 0, $RebateAmount, 0, $trans_date, $payment_no);
                                    update_loan_schedule($pdrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "paid", 0, "paid");
                                    
                                    $GLRebate += $RebateAmount;
                                    $allocatedAmount += $thismonthAmort;
                                    $thismonthAmort = $tenderd_amount = 0;

                                }elseif(($tenderd_amount + $RebateAmount) < $thismonthAmort){

                                    add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $pdrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, $tenderd_amount, 0, 0, 0, $trans_date, $payment_no);
                                    update_loan_schedule($pdrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "partial", 0, $penaltyBal['penalty_status']);
                                    
                                    $allocatedAmount += $tenderd_amount;
                                    $tenderd_amount = 0;

                                }else{

                                    add_loan_ledger($_POST['InvoiceNo'], $_POST['customername'], $pdrow["loansched_id"], $_POST['transtype'], ST_CUSTPAYMENT, $thismonthAmort, 0, $RebateAmount, 0, $trans_date, $payment_no);
                                    update_loan_schedule($pdrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "paid", 0, "paid");
                                    
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
                                    update_loan_schedule($myrow["loansched_id"], $_POST['customername'], $_POST['InvoiceNo'], $_POST['transtype'], "partial", 0, $penaltyBal['penalty_status']);
                                    
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
                    //allocate payment to trans number sales invoice
                    //($GLRebate + ($_POST['tenderd_amount'] - $GLPenalty))
                    add_cust_allocation($allocatedAmount, ST_CUSTPAYMENT, $payment_no, $_POST['transtype'], $_POST['InvoiceNo'], $_POST['customername'], $_POST['trans_date']);
                    update_debtor_trans_allocation($_POST['transtype'], $_POST['InvoiceNo'], $_POST['customername']);
    
                    //save reference
                    $Refs->save(ST_CUSTPAYMENT, $payment_no, $_POST['ref_no']);
    
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
    
                    if ($GLRebate != 0)	{
                        /* Now Debit discount account with discounts allowed*/
                        $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $branch_data["payment_discount_account"], 0, 0, $GLRebate, $_POST['customername'], "Cannot insert a GL transaction for the payment discount debit", 0, null, null, 0, $_POST['InvoiceNo']);
                    }
    
                    if (($GLRebate + ($_POST['tenderd_amount'] - $GLPenalty)) != 0)	{
                        /* Now Credit Debtors account with receipts + discounts */
                        //$GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $branch_data["receivables_account"], 0, 0, -($GLRebate + ($_POST['tenderd_amount'] - $GLPenalty)), $_POST['customername'], "Cannot insert a GL transaction for the debtors account credit");
                        $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $debtors_account, 0, 0, -($GLRebate + ($_POST['tenderd_amount'] - $GLPenalty)), $_POST['customername'], "Cannot insert a GL transaction for the debtors account credit", 0, null, null, 0, $_POST['InvoiceNo']);
                    }
    
                    if($GPM != 0){
                        $dgp_account = $company_record["dgp_account"];
                        $rgp_account = $company_record["rgp_account"];
                        
                        if($islastPay != 0){
                            $DeferdAmt = get_deferdBal($_POST['InvoiceNo'], $dgp_account);
                        }else{
                            $ARValue = ($GLRebate + ($_POST['tenderd_amount'] - $GLPenalty));
                            $DeferdAmt = $ARValue * $GPM;
                        }
    
                        $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $dgp_account, 0, 0, $DeferdAmt, $_POST['customername'], "Cannot insert a GL transaction for the DGP account debit", 0, null, null, 0, $_POST['InvoiceNo']);
                        $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $rgp_account, 0, 0, -$DeferdAmt, $_POST['customername'], "Cannot insert a GL transaction for the RGP account credit", 0, null, null, 0, $_POST['InvoiceNo']);
                    }
                    
                    if($GLPenalty != 0){
                        /* Now credit bank account with penalty */
                        $penalty_act = get_company_pref('penalty_act');
                        $GLtotal += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'], $penalty_act, 0, 0, -$GLPenalty, $_POST['customername'], "Cannot insert a GL transaction for the payment penalty credit", 0, null, null, 0, $_POST['InvoiceNo']);
                    }
    
                    /*Post a balance post if $total != 0 due to variance in AR and bank posted values*/
                    if ($GLtotal != 0)
                    {
                        $variance_act = get_company_pref('exchange_diff_act');
                        add_gl_trans(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date'],	$variance_act, 0, 0, '', -$GLtotal, null, PT_CUSTOMER, $_POST['customername'], "", 0, null, null, 0, $_POST['InvoiceNo']);
                    }
    
                    if(check_schedule_status($_POST['InvoiceNo'], $_POST['transtype'], $_POST['customername']) == 0){
                        update_status_debtor_trans($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype'], "fully-paid");
                        update_status_debtor_loans($_POST['InvoiceNo'], $_POST['customername'], "fully-paid");
                    }else{
                        update_status_debtor_trans($_POST['InvoiceNo'], $_POST['customername'], $_POST['transtype'], "part-paid");
                        update_status_debtor_loans($_POST['InvoiceNo'], $_POST['customername'], "part-paid");
                    }
                }
                $dsplymsg = _("The customer payment has been successfully entered...");
            }
            //allocate payment to
            update_debtor_trans_allocation(ST_CUSTPAYMENT, $payment_no, $_POST['customername']);          
        }
        echo '({"success":"true","message":"'.$dsplymsg.'"})';
    }else{
        echo '({"failure":"false","message":"'.$dsplymsg.'"})';
    }
    return;
}

if(isset($_GET['submitInterB']))
{
    //initialise no input errors assumed initially before we proceed
    //0 is by default no errors
    $InputError = 0;

    if (empty($_POST['pay_type_inb']) || empty($_POST['moduletype_inb']) || empty($_POST['name_inb']) || empty($_POST['ref_no_inb'])) {
        $InputError = 1;
        $dsplymsg = _('Some fields are empty or contain an improper value. Please reload the page and fill up the required field.');
    }
    if (empty($_POST['customercode_inb'])) {
        $InputError = 1;
        $dsplymsg = _('Customer code must not be empty.');
    }
    if (empty($_POST['customername_inb'])) {
        $InputError = 1;
        $dsplymsg = _('Customer name must not be empty.');
    }
    if (empty($_POST['trans_date_inb'])) {
        $InputError = 1;
        $dsplymsg = _('Transaction date must not be empty.');
    }else{
        $trans_date = date('Y-m-d',strtotime($_POST['trans_date_inb']));
    }
    if (empty($_POST['branch_inb'])) {
        $InputError = 1;
        $dsplymsg = _('Branch must not be empty.');
    }
    if (empty($_POST['receipt_no_inb'])) {
        $InputError = 1;
        $dsplymsg = _('CR number must not be empty.');
    }
    if (empty($_POST['intobankacct_inb'])) {
        $InputError = 1;
        $dsplymsg = _('Into bank account must not be empty.');
    }
    if (empty($_POST['total_amount_inb'])) {
        $InputError = 1;
        $dsplymsg = _('Total amount must not be empty.');
    }
    if (empty($_POST['tenderd_amount_inb'])) {
        $InputError = 1;
        $dsplymsg = _('Tendered amount must not be empty.');
    }
    if (empty($_POST['remarks_inb'])) {
        $InputError = 1;
        $dsplymsg = _('remarks must not be empty.');
    }
    if (empty($_POST['paymentType_inb'])) {
        $InputError = 1;
        $dsplymsg = _('Payment type must not be empty.');
    }
    if (empty($_POST['cashier_inb'])) {
        $InputError = 1;
        $dsplymsg = _('Cashier must not be empty.');
    }
    if (empty($_POST['preparedby_inb'])) {
        $InputError = 1;
        $dsplymsg = _('Prepared by must not be empty.');
    }
    if (empty($_POST['collectType_inb'])) {
        $InputError = 1;
        $dsplymsg = _('Collection type must not be empty.');
    }

    if($_POST['pay_type_inb'] == "Check"){
        if (empty($_POST['check_date_inb'])) {
            $InputError = 1;
            $dsplymsg = _('Check date must not be empty.');
        }
        if (empty($_POST['check_no_inb'])) {
            $InputError = 1;
            $dsplymsg = _('Check number must not be empty.');
        }
        if (empty($_POST['bank_branch_inb'])) {
            $InputError = 1;
            $dsplymsg = _('Bank branch must not be empty.');
        }
    }

    $DataOnGrid = stripslashes(html_entity_decode($_POST['InterBDataOnGrid']));
    $objDataGrid = json_decode($DataOnGrid, true);
    
    //var_dump($objDataGrid);
    if (count($objDataGrid) == 0){
        $InputError = 1;
        $dsplymsg = _('Credit amount must not be empty! Please try again.');
    }

    //check data
	if(check_cr_number($_POST['receipt_no_inb'])){
        $InputError = 1;
        $dsplymsg = _("CR number already exists.");
    }

    if ($InputError != 1){
        //begin_transaction();
        global $Refs;

        $BranchNo = get_newcust_branch($_POST['customername_inb'], $_POST['customercode_inb']);
        $bank = get_bank_account($_POST['intobankacct_inb']);

        $payment_no = write_customer_trans(ST_CUSTPAYMENT, 0, $_POST['customername_inb'], check_isempty($BranchNo['branch_code']), $_POST['trans_date_inb'], $_POST['ref_no_inb'],
                                    $_POST['tenderd_amount_inb'], 0 , 0, 0, 0, 0, 0, 0, null, 0, 0, 0, 0, null, 0, 0, 0, $_POST['paymentType_inb'], $_POST['collectType_inb'], $_POST['moduletype_inb']);

        add_bank_trans(ST_CUSTPAYMENT, $payment_no, $_POST['intobankacct_inb'], $_POST['ref_no_inb'], $_POST['trans_date_inb'], $_POST['tenderd_amount_inb'], PT_CUSTOMER, $_POST['customername_inb'],
                        $_POST['cashier_inb'], $_POST['pay_type_inb'], $_POST['check_date_inb'], $_POST['check_no_inb'], $_POST['bank_branch_inb'], $_POST['branch_inb'], $_POST['receipt_no_inb'], $_POST['preparedby_inb']);

        add_comments(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date_inb'], $_POST['remarks_inb']);

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

                add_gl_trans(ST_CUSTPAYMENT, $payment_no, $_POST['trans_date_inb'], $data['gl_code'], 0, 0, '', $amount, $bank['bank_curr_code'], PT_CUSTOMER, $_POST['customername_inb'], '', 0, $data['sl_code'], '', 0, 0);
            }
        }
        //commit_transaction();
        
        //send data to target branch
        $conn = $db_connections[user_company()];
        $interb_ref = get_debtor_interb_ref($_POST['customername_inb']);
       
        if(!empty($company_prefs["deployment_status"])){
            interbranch_send_payment_add($_POST['branch_inb'], $interb_ref, $_POST['name_inb'], $trans_date, $_POST['ref_no_inb'], $_POST['total_amount_inb'],
                                            $_POST['remarks_inb'], $_POST['preparedby_inb'], $conn['branch_code'], $payment_no, ST_CUSTPAYMENT, $_POST['branch_inb']);

            //make a copy to HO database
            //interbranch_send_payment_add("HO", $interb_ref, $_POST['name_inb'], $trans_date, $_POST['ref_no_inb'], $_POST['total_amount_inb'],
            //            $_POST['remarks_inb'], $_POST['preparedby_inb'], $conn['branch_code'], $payment_no, ST_CUSTPAYMENT, $_POST['branch_inb']);
        }
        //auto allocate payment para dili na makita sa customer allocation
        auto_allocate_payment($payment_no, ST_CUSTPAYMENT, $_POST['total_amount_inb']);
        
        $Refs->save(ST_CUSTPAYMENT, $payment_no, $_POST['ref_no_inb']);

        $dsplymsg = _("Inter branch payment has been successfully entered...</br> Reference No.: ".$payment_no);
        echo '({"success":"true","message":"'.$dsplymsg.'"})';
    }else{
        echo '({"failure":"false","message":"'.$dsplymsg.'"})';
    }
    return;
}

if(isset($_GET['submitDPnoAmort']))
{
    //initialise no input errors assumed initially before we proceed
    //0 is by default no errors
    $InputError = 0;

    if (empty($_POST['pay_type_dp']) || empty($_POST['moduletype_dp']) || empty($_POST['name_dp']) || empty($_POST['ref_no_dp'])) {
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
    if (empty($_POST['intobankacct_dp'])) {
        $InputError = 1;
        $dsplymsg = _('Into bank account must not be empty.');
    }
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

    if($_POST['pay_type_dp'] == "Check"){
        if (empty($_POST['check_date_dp'])) {
            $InputError = 1;
            $dsplymsg = _('Check date must not be empty.');
        }
        if (empty($_POST['check_no_dp'])) {
            $InputError = 1;
            $dsplymsg = _('Check number must not be empty.');
        }
        if (empty($_POST['bank_branch_dp'])) {
            $InputError = 1;
            $dsplymsg = _('Bank branch must not be empty.');
        }
    }

    $DataOnGrid = stripslashes(html_entity_decode($_POST['DPDataOnGrid']));
    $objDataGrid = json_decode($DataOnGrid, true);
    
    //var_dump($objDataGrid);
    if (count($objDataGrid) == 0){
        $InputError = 1;
        $dsplymsg = _('Credit amount must not be empty! Please try again.');
    }

    //check data
	if(check_cr_number($_POST['receipt_no_dp'])){
        $InputError = 1;
        $dsplymsg = _("CR number already exists.");
    }

    if ($InputError != 1){
        //begin_transaction();
        global $Refs;
        
        $BranchNo = get_newcust_branch($_POST['customername_dp'], $_POST['customercode_dp']);
        $bank = get_bank_account($_POST['intobankacct_dp']);

        $payment_no = write_customer_trans(ST_CUSTPAYMENT, 0, $_POST['customername_dp'], check_isempty($BranchNo['branch_code']), $_POST['trans_date_dp'], $_POST['ref_no_dp'],
                                    $_POST['tenderd_amount_dp'], 0 , 0, 0, 0, 0, 0, 0, null, 0, 0, 0, 0, null, 0, 0, 0, $_POST['paymentType_dp'], $_POST['collectType_dp'], $_POST['moduletype_dp']);

        add_bank_trans(ST_CUSTPAYMENT, $payment_no, $_POST['intobankacct_dp'], $_POST['ref_no_dp'], $_POST['trans_date_dp'], $_POST['tenderd_amount_dp'], PT_CUSTOMER, $_POST['customername_dp'],
                        $_POST['cashier_dp'], $_POST['pay_type_dp'], $_POST['check_date_dp'], $_POST['check_no_dp'], $_POST['bank_branch_dp'], 0, $_POST['receipt_no_dp'], $_POST['preparedby_dp']);

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

if(isset($_GET['submitSICash']))
{
    $InputError = 0;
    
    if (empty($_POST['transtype_cash']) || empty($_POST['ref_no_cash'])) {
        $InputError = 1;
        $dsplymsg = _('Some fields are empty or contain an improper value. Please reload the page and fill up the required field.');
    }
    if (empty($_POST['pay_type_cash'])) {
        $InputError = 1;
        $dsplymsg = _('Some fields are empty or contain an improper value. Please reload the page and fill up the required field.');
    }
    if (empty($_POST['moduletype_cash'])) {
        $InputError = 1;
        $dsplymsg = _('Some fields are empty or contain an improper value. Please reload the page and fill up the required field.');
    }
    if (empty($_POST['customercode_cash'])) {
        $InputError = 1;
        $dsplymsg = _('Customer code must not be empty.');
    }
    if (empty($_POST['customername_cash'])) {
        $InputError = 1;
        $dsplymsg = _('Customer name must not be empty.');
    }
    if (empty($_POST['trans_date_cash'])) {
        $InputError = 1;
        $dsplymsg = _('Transaction date must not be empty.');
    }else{
        $trans_date = date('Y-m-d',strtotime($_POST['trans_date_cash']));
    }
    if (empty($_POST['InvoiceNo_cash'])) {
        $InputError = 1;
        $dsplymsg = _('Invoice number must not be empty.');
    }
    if (empty($_POST['receipt_no_cash'])) {
        $InputError = 1;
        $dsplymsg = _('CR number must not be empty.');
    }
    if (empty($_POST['intobankacct_cash'])) {
        $InputError = 1;
        $dsplymsg = _('Into bank account must not be empty.');
    }
    if (empty($_POST['total_amount_cash'])) {
        $InputError = 1;
        $dsplymsg = _('Total amount must not be empty.');
    }
    if ($_POST['total_amount_cash'] == 0) {
        $InputError = 1;
        $dsplymsg = _('Total amount must be greater than 0.');
    }
    if (empty($_POST['tenderd_amount_cash'])) {
        $InputError = 1;
        $dsplymsg = _('Tendered amount must not be empty.');
    }
    if ($_POST['tenderd_amount_cash'] == 0) {
        $InputError = 1;
        $dsplymsg = _('Tendered amount must be greater than 0.');
    }
    if (empty($_POST['remarks_cash'])) {
        $InputError = 1;
        $dsplymsg = _('remarks must not be empty.');
    }
    if (empty($_POST['paymentType_cash'])) {
        $InputError = 1;
        $dsplymsg = _('Payment type must not be empty.');
    }
    if (empty($_POST['cashier_cash'])) {
        $InputError = 1;
        $dsplymsg = _('Cashier must not be empty.');
    }
    if (empty($_POST['preparedby_cash'])) {
        $InputError = 1;
        $dsplymsg = _('Prepared by must not be empty.');
    }
    if (empty($_POST['collectType_cash'])) {
        $InputError = 1;
        $dsplymsg = _('Collection type must not be empty.');
    }
    if($_POST['total_amount_cash'] < $_POST['tenderd_amount_cash']){
        $InputError = 1;
        $dsplymsg = _('Tendered amount and total down payment amount must be equal');
    }

    $invoice_date = get_debtor_invoice_date($_POST['InvoiceNo_cash'], $_POST['customername_cash'], $_POST['transtype_cash']);
    if(check_two_dates($invoice_date, $_POST['trans_date_cash']) < 0){
        $InputError = 1;
        $dsplymsg = _('Transaction date must be greater than invoice date.');
    }

    if($_POST['pay_type_cash'] == "Check"){
        if (empty($_POST['check_date_cash'])) {
            $InputError = 1;
            $dsplymsg = _('Check date must not be empty.');
        }
        if (empty($_POST['check_no_cash'])) {
            $InputError = 1;
            $dsplymsg = _('Check number must not be empty.');
        }
        if (empty($_POST['bank_branch_cash'])) {
            $InputError = 1;
            $dsplymsg = _('Bank branch must not be empty.');
        }
    }

    $DataOnGrid = stripslashes(html_entity_decode($_POST['DataOnGrid']));
    $objDataGrid = json_decode($DataOnGrid, true);
    
    //var_dump($objDataGrid);
    if (count($objDataGrid) == 0){
        $InputError = 1;
        $dsplymsg = _('Credit amount must not be empty! Please try again.');
    }

    //check data
	if(check_cr_number($_POST['receipt_no_cash'])){
        $InputError = 1;
        $dsplymsg = _("CR number already exists.");
    }

    if ($InputError != 1){
        
        begin_transaction();
        $BranchNo = get_newcust_branch($_POST['customername_cash'], $_POST['customercode_cash']);

        foreach($objDataGrid as $value=>$data) {
            $debtor_id = $data['debtor_id'];
            $trans_no = $data['trans_no'];
            $due_date = $data['due_date'];
            $ar_due = $data['ar_due'];
            $partialpayment = $data['partialpayment'];
            $totalpayment = $data['totalpayment'];
            $alloc_amount_cash = $data['alloc_amount_cash'];
            $cash_discount = $data['cash_discount'];

            set_global_connection();

            $payment_no = write_customer_payment(0, $_POST['customername_cash'], check_isempty($BranchNo['branch_code']), $_POST['intobankacct_cash'], $_POST['trans_date_cash'], $_POST['ref_no_cash'],
                                            $_POST['tenderd_amount_cash'], 0, $_POST['remarks_cash'], 0, 0, input_num('bank_amount', $_POST['tenderd_amount_cash']),
                                            0, $_POST['paymentType_cash'], $_POST['collectType_cash'], $_POST['moduletype_cash'], $_POST['cashier_cash'], $_POST['pay_type_cash'],
                                            $_POST['check_date_cash'], $_POST['check_no_cash'], $_POST['bank_branch_cash'], $_POST['InvoiceNo_cash'], $_POST['receipt_no_cash'], $_POST['preparedby_cash']);

            add_cust_allocation($_POST['tenderd_amount_cash'], ST_CUSTPAYMENT, $payment_no, $_POST['transtype_cash'], $_POST['InvoiceNo_cash'], $_POST['customername_cash'], $_POST['trans_date_cash']);
            update_debtor_trans_allocation($_POST['transtype_cash'], $_POST['InvoiceNo_cash'], $_POST['customername_cash']);

            //allocate payment to
            update_debtor_trans_allocation(ST_CUSTPAYMENT, $payment_no, $_POST['customername_cash']);

            if((check_ar_balance($_POST['InvoiceNo_cash'], $_POST['transtype_cash']) - $cash_discount) == 0){
                update_status_debtor_trans($_POST['InvoiceNo_cash'], $_POST['customername_cash'], $_POST['transtype_cash'], "fully-paid");
                update_status_debtor_loans($_POST['InvoiceNo_cash'], $_POST['customername_cash'], "fully-paid");
            }else{
                update_status_debtor_trans($_POST['InvoiceNo_cash'], $_POST['customername_cash'], $_POST['transtype_cash'], "part-paid");
                update_status_debtor_loans($_POST['InvoiceNo_cash'], $_POST['customername_cash'], "part-paid");
            }

            $dsplymsg = _("Payment has been successfully entered...");
        }
        echo '({"success":"true","message":"'.$dsplymsg.'"})';
    }else{
        echo '({"failure":"false","message":"'.$dsplymsg.'"})';
    }
    return;
}

//------------------------------------------------------------------------------------------------------
//simple_page_mode(true);
page(_($help_context = "Collection Receipt"), false, false, "", null);

start_table(TABLESTYLE, "width='100%'");
   echo "<div id='ext-form'></div>";
   echo "<style type='text/css' media='screen'>
            .x-form-text-default.x-form-textarea {
                line-height: 19px;
                min-height: 30px;
            }
            .rptbtn {
                background-color: #4CAF50;
                padding: 10px 20px;
                border-radius:5px;
            }
        </style>";

end_table();
display_note(_(""), 0, 0, "class='overduefg'");
end_form();
end_page();