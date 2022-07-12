<?php
//----------------------------------------------------------------------------
// helper functions for script execution control
//
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/sales/includes/db/customers_db.inc");
include_once($path_to_root . "/sales/includes/db/branches_db.inc");
include_once($path_to_root . "/includes/db/crm_contacts_db.inc");
include_once($path_to_root . "/admin/db/company_db.inc");
include_once($path_to_root . "/sales/includes/db/cust_trans_db.inc");
include_once($path_to_root . "/sales/includes/db/cust_trans_details_db.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/lending/includes/db/invoice_inquiry_db.inc");
include_once($path_to_root . "/lending/includes/db/customers_payment_db.inc");
include_once($path_to_root . "/lending/includes/db/inter_branch_payment_db.inc");

function get_branch_info($branch_code)
{
    global $db_connections;
    $conn = $db_connections;
    $total = count($conn);

    unset($branch_arr);

	for ($i = 0; $i < $total; $i++)
	{
        if($branch_code == $conn[$i]['branch_code']) {
            
            $branch_arr[] = array('id'=>$conn[$i]['branch_code'],
                                    'name'=>$conn[$i]['name'],
                                    'area'=>$conn[$i]['branch_area'],
                                    'gl_account'=>$conn[$i]['gl_account'],
                                    'b_type'=>$conn[$i]['type']
                                );
        }

    }
    return $branch_arr;
}

function array_Amortization($Term, $MonthlyPayment, $OutstandingAR, $FirstDueDate, $dateApprove, $totalAmount, $downpaymnt)
{
    unset($amort_array);
    
    $weekday = date('D', strtotime($dateApprove));
    $runtotalAmount = $totalAmount - $downpaymnt;
    $amort_array[] = array('no'=>0,
                'datedue'=>$dateApprove,
                'weekday'=>$weekday,
                'amortization'=>$downpaymnt,
                'runbalance'=>$OutstandingAR,
                'totalamort'=>$totalAmount,
                'runtotalamort'=>$runtotalAmount
            );

    foreach (range(1, $Term) as $number) {
        if($number == 1){
            $datedue = $FirstDueDate;
            $RunBalance = $OutstandingAR - $MonthlyPayment;
        }else{
            $countr = $number-1;
            $datedue = date('Y-m-d', strtotime("+1 months", strtotime($datedue)));
            $RunBalance = $RunBalance - $MonthlyPayment;
        }
        $countr = 0;
        $weekday = date('D', strtotime($datedue));

        $amort_array[] = array('no'=>$number,
                                'datedue'=>$datedue,
                                'weekday'=>$weekday,
                                'amortization'=>$MonthlyPayment,
                                'runbalance'=>$RunBalance,
                                'totalamort'=>$MonthlyPayment,
                                'runtotalamort'=>$RunBalance
                            );
    }
    return $amort_array;
}

function check_isempty($param){
    if(empty($param)){
        $value = 0;
    }else{
        $value = $param;
    }
    return $value;
}

// validate number
function validateNumber($Amount){
    if (strpos($Amount, 'PhP') !== false) {
        $value = trim(str_replace('PhP', '', $Amount));
    } elseif(strpos($Amount, 'PHP') !== false){
        $value = trim(str_replace('PHP', '', $Amount));
    }else{
        $value = preg_replace("/[^0-9.]/","",$Amount);
    }
    
    $NewValue = trim(str_replace(',', '', $value));
    return $NewValue;
}

function GetRebate($TransDate, $DueDate, $RebateAmount){
    //Rebate DATE for payments
    $DaysRebate = get_company_pref('default_rebate_valid_days');
    $MonthRebate = get_company_pref('default_rebate_valid_month');

    $year  = date('Y',strtotime($DueDate));
    $month = date('m',strtotime($DueDate));
    $day   = date('d',strtotime($DueDate)) + $DaysRebate;

    $RebateDate = date('Y-m-d', strtotime($year .'-'. $month .'-'. $day));
    
    //for sunday +1 day
    //modify rebate no more holiday and sunday. on 09-07-2021
    /*$weekday = date('D', strtotime($RebateDate));
    if($weekday =='Sun'){
        $day = date('d',strtotime($DueDate))+ 4;
        $RebateDate = date('Y-m-d', strtotime($year .'-'. $month .'-'. $day));
    }*/

    if(date('Y-m-d',strtotime($RebateDate)) >= date('Y-m-d',strtotime($TransDate))){
        $Rebate = $RebateAmount;
    }else{
        $Rebate = 0;
    }

    //check for whole month rebate or else
    if($MonthRebate != 0){
        $monthtrans_date = date('m',strtotime($TransDate));
        if($MonthRebate == $monthtrans_date){
            $Rebate = $RebateAmount;
        }
    }

    return $Rebate;
}

function CalcMonthsDue_pnlty($TransDate, $DueDate, $MaturityDate){

    if(date('Y-m-d', strtotime($TransDate)) > date('Y-m-d', strtotime($DueDate))){
        $StrDueDate = strtotime($DueDate);
        $StrTransDate = strtotime($TransDate);
        
        $StrDueYear = date('Y', $StrDueDate);
        $StrTransYear = date('Y', $StrTransDate);
        
        $StrDueMonth = date('m', $StrDueDate);
        $StrTransMonth = date('m', $StrTransDate);
        
        $StrDueDay = date('d', strtotime($DueDate));
        $StrTransDay = date('d', strtotime($TransDate));
        
        $DateDiff = ($StrTransDay - $StrDueDay);
        $MonthDiff = (($StrTransYear - $StrDueYear) * 12) + ($StrTransMonth - $StrDueMonth);
        
        if($DateDiff <= 0){
            $MonthNo = ($MonthDiff - 1);
        }else{
            $MonthNo = $MonthDiff;
        }
        
        //check if accounts is past due then subtract the number to get exact month until maturity date
        $PastDueNo = CalculateMonthsPastDue($TransDate, $DueDate, $MaturityDate);
        
        //echo $PastDueNo . ' - da -' . $MonthNo . ' --- ';
        if($PastDueNo != 0){
            $MonthNo -= $PastDueNo;
        }
    }else{
        $MonthNo = 0;
    }
    return $MonthNo;
}

function CalculatePenalty($TransDate, $MaturityDate, $PrincipalDue, $TotalBalance, $MonthNo, $Remarks, $Flag){
    /*
        * -> NOT PAST DUE
            if transaction date is greater than the due date, count the transaction month and multiply to amount due. ex.
            - due date = jan. 03, 2016						- transaction date = march. 04, 2016
            - amount due = P1127							- months exceed = 2 months
            - penalty amount for the month of january = P1127 * 2 months
            - penalty amount for the month of february = P21,408 * 1 months
        
        * -> PAST DUE
            if transaction date is greater than the maturity date, count the exceeded months and use the balance amount to get penalty amount. ex.
            - maturity date = may 03, 2016					- transaction date = sept. 19, 2016
            - balance amount = P21,408						- months exceed = 4 months
            - penalty amount = P21,408 * 4 months
    */
    //def_penalty = 0.04
    $company_prefs = get_company_prefs();

    if(date('Y-m-d', strtotime($TransDate)) > date('Y-m-d', strtotime($MaturityDate)) AND $Remarks == 'PASTDUE'){
        //Past Due Accounts...
        $Penalty = (($TotalBalance * check_isempty($company_prefs["penalty_rate"])) * $MonthNo);
    }else{
        //Not Past Due Accounts...
        if($Flag == true){
            if($TotalBalance != 0){
                $Penalty = (($TotalBalance * check_isempty($company_prefs["penalty_rate"])) * $MonthNo);
                $TotalBalance = 0;
            }else{
                $Penalty = (($PrincipalDue * check_isempty($company_prefs["penalty_rate"])) * $MonthNo);
            }
        }else{
            for ($MonthNo; $MonthNo >= 1; $MonthNo--) {
                if($TotalBalance != 0){
                    $Penalty += (($TotalBalance * check_isempty($company_prefs["penalty_rate"])) * $MonthNo);
                    $TotalBalance = 0;
                }else{
                    $Penalty += (($PrincipalDue * check_isempty($company_prefs["penalty_rate"])) * $MonthNo);
                   // echo $MonthNo.'-'.$Penalty.'</br>';
                }
            }
        }
    }
    return $Penalty;
}

function CalculateMonthsPastDue($TransDate, $DueDate, $MaturityDate){
    
    if(date('Y-m-d', strtotime($TransDate)) > date('Y-m-d', strtotime($MaturityDate))){
        $StrDueDate = strtotime($MaturityDate);
        $StrTransDate = strtotime($TransDate);
        
        $StrDueYear = date('Y', $StrDueDate);
        $StrTransYear = date('Y', $StrTransDate);
        
        $StrDueMonth = date('m', $StrDueDate);
        $StrTransMonth = date('m', $StrTransDate);
        
        $StrDueDay = date('d', strtotime($DueDate));
        $StrTransDay = date('d', strtotime($TransDate));
        
        $DateDiff = ($StrTransDay - $StrDueDay);
        $MonthDiff = (($StrTransYear - $StrDueYear) * 12) + ($StrTransMonth - $StrDueMonth);
        
        if($DateDiff <= 0){
            $MonthNo = ($MonthDiff - 1);
            if($MonthNo < 0){
                $MonthNo = 0;
            }
        }else{
            $MonthNo = $MonthDiff;
        }
    }else{
        $MonthNo = 0;
    }
    return $MonthNo;
    
}

//Remove UTF8 Bom
function remove_utf8_bom($text){
    $bom = pack('H*','EFBBBF');
    $text = preg_replace("/^$bom/", '', $text);
    return $text;
}

function GetReferenceID($tags)
{
	$sql = "SELECT A.id 
		FROM ".TB_PREF."reflines A 
		WHERE A.prefix = ".db_escape($tags);
			
	$result = db_query($sql, "could not get reference id");
	$row = db_fetch($result);
	return $row[0];
}

function per_Penalty($MonthNo, $PrincipalDue)
{
    $company_prefs = get_company_prefs();
    $Penalty = (($PrincipalDue * $company_prefs["penalty_rate"]) * $MonthNo);
    return round($Penalty);
}

function mos_interval($due_date, $trans_date)
{
    $date1 =  new DateTime($due_date);
    $date2 =  new DateTime($trans_date);

    $interval = date_diff($date1, $date2);

    if($interval->format("%R%a") < 0){
        $mos = 0;
    }else{
        $mos = $interval->m + ($interval->y * 12);
    }
    //echo $mos;
    return $mos;
}

function check_transdate($due_date, $trans_date)
{
    $date_due = strtotime($due_date);
    $date_trans = strtotime($trans_date);

    if ($date_due > $date_trans) {
        //echo 'greater than';
        $value = 1;
    }else{
        //echo 'Less than';
        $value = 0;
    }

    return $value;
}

function check_two_dates($trans_date, $repo_date)
{
    $date1 =  new DateTime($trans_date);
    $date2 =  new DateTime($repo_date);

    $diffdate = date_diff($date1, $date2);

    return $diffdate->format("%r%a");
}