<?php
//----------------------------------------------------------------------------
// helper functions for script execution control
//
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/admin/db/company_db.inc");
include_once($path_to_root . "/sales/includes/db/customers_db.inc");
include_once($path_to_root . "/sales/includes/db/cust_trans_db.inc");
include_once($path_to_root . "/sales/includes/db/branches_db.inc");
include_once($path_to_root . "/includes/db/crm_contacts_db.inc");
include_once($path_to_root . "/sales/includes/db/cust_trans_details_db.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");

include_once($path_to_root . "/repossess/includes/db/repo_transaction_db.inc");

include_once($path_to_root . "/repossess/includes/db/items_repo_db.inc");

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
                                    'gl_account'=>$conn[$i]['gl_account']
                                );
        }

    }
    return $branch_arr;
}

function check_isempty($param){
    if(empty($param)){
        $value = 0;
    }else{
        $value = $param;
    }
    return $value;
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

function isempty($param){
    if(empty($param)){
        $value = 0;
    }else{
        $value = $param;
    }
    return $value;
}

// validate number
function validate_number($Amount){
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

//Remove UTF8 Bom
function remove_utf8_bom($text){
    $bom = pack('H*','EFBBBF');
    $text = preg_replace("/^$bom/", '', $text);
    return $text;
}

function check_two_dates($trans_date, $repo_date)
{
    $date1 =  new DateTime($trans_date);
    $date2 =  new DateTime($repo_date);

    $diffdate = date_diff($date1, $date2);

    return $diffdate->format("%r%a");
}

function mos_interval_r($due_date, $trans_date)
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
