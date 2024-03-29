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
$page_security = 'SA_COLLECT_REP'; //Modified by spyrax10 20 Jun 2022
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	RobertGwapo
// date:	2023-06-27
// Title:	Daily Summary Of Collection - Allocate V2
// ----------------------------------------------------------------

/**
 * Note: Update the variable $group
 * Reason: Function group_list() deleted
 * Updated By: spyrax10
 * Updated Date: 21 Mar 2022 
 */


$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/taxes/tax_calc.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/inventory/includes/db/items_category_db.inc");

//----------------------------------------------------------------------------------------------------

print_PO_Report();

function getTransactions($from, $to, $cust_name = "", $collector, $cashier, $Type, $group = 0)
{
	$from = date2sql($from);
	$to = date2sql($to);
	$advanceDate = endCycle($to, 1);
	
	$sql ="SELECT A.type AS No_type, A.trans_no AS No_trans, A.masterfile, A.trans_date, A.ref, A.receipt_no, abs(A.amount) AS amt, 
			A.pay_type AS PayType, A.person_id AS debtor_nos, B.ov_discount AS rebate, B.payment_type AS Type, B.module_type AS Type_Alloc, 
			C.name, E.payment_applied AS payment, E.penalty, E.trans_no AS loan_trans, E.date_paid, F.month_no, F.principal_due, F.date_due, 
			I.collection, K.account_name AS Coa_name, M.description AS AREA, O.real_name AS Collector_Name, P.memo_,

			CASE
				WHEN B.module_type = 'ALCN-DP' THEN 'Allocate Down-payment'
				WHEN B.module_type = 'ALCN-INTERB' THEN 'Allocate Inter-branch Payment'
				WHEN B.module_type = 'ALCN-ADJ' THEN 'Allocate Other Adjustment'
			END AS 'alolocate_type',

			CASE 
			    WHEN A.type = " . ST_CUSTPAYMENT . " THEN 'Office Collection Receipt'
			END AS 'receipt_type'

			FROM ".TB_PREF."bank_trans A
			LEFT JOIN ".TB_PREF."debtor_trans B ON B.type = A.type AND B.trans_no = A.trans_no
			LEFT JOIN ".TB_PREF."debtors_master C ON C.debtor_no = A.person_id 
			LEFT JOIN ".TB_PREF."areas D ON D.area_code = C.area 
			LEFT JOIN ".TB_PREF."debtor_loan_ledger E ON E.payment_trans_no = B.trans_no 
			AND E.trans_type_from = B.type
			LEFT JOIN ".TB_PREF."debtor_loan_schedule F ON F.id = E.loansched_id
			LEFT JOIN ".TB_PREF."collection_types I ON I.collect_id = B.collect_id
			LEFT JOIN ".TB_PREF."cust_branch J ON J.debtor_no = A.person_id
			LEFT JOIN ".TB_PREF."chart_master K ON K.account_code = J.receivables_account
			LEFT JOIN ".TB_PREF."users L ON A.cashier_user_id = L.id
			LEFT JOIN ".TB_PREF."areas M ON M.area_code = C.area 
			LEFT JOIN ".TB_PREF."users O ON O.user_id = M.collectors_id
			LEFT JOIN ".TB_PREF."comments P ON P.id = A.trans_no AND P.type = A.type
			WHERE A.trans_date>='$from'
			AND A.trans_date<='$to'
			AND A.type IN (" . ST_CUSTPAYMENT . ")
            AND A.pay_type = 'alloc'
            ";

			if ($cust_name != 'ALL') {
            $sql .= " AND C.name =".db_escape($cust_name);              
            }

			if ($collector != '') {
			$sql .= " AND O.user_id =".db_escape($collector);
			}

			if ($cashier != '') {
			$sql .= " AND L.id =".db_escape($cashier);
			}

            if ($group == 1) {
                $sql .= "GROUP BY A.ref, A.trans_no, K.account_name, Type";                
                $sql .= " ORDER BY A.ref DESC";
            }
            else if ($group == 2) {
                $sql .= "GROUP BY A.ref, A.trans_no, O.real_name, Type";                
                $sql .= " ORDER BY O.real_name DESC, A.ref DESC";
            } else {
                $sql .= "GROUP BY A.ref, A.trans_no, M.description, Type";                
                $sql .= " ORDER BY M.description DESC, A.ref DESC";
            }

    return db_query($sql, "No transactions were returned");
}

function get_category_invoice_date($trans_no)
{
    $sql = "SELECT A.invoice_date FROM debtor_loans A
		LEFT JOIN stock_category B ON B.category_id = A.category_id
		WHERE A.trans_no = '".$trans_no."'";

    $result = db_query($sql, "QOH calculation failed");

	$myrow = db_fetch_row($result);

	$invcdate =  $myrow[0];
	return $invcdate;
}

function get_category_descrpton($trans_no)
{
    $sql = "SELECT B.description FROM debtor_loans A
		LEFT JOIN stock_category B ON B.category_id = A.category_id
		WHERE A.trans_no = '".$trans_no."'";

   	$result = db_query($sql, "QOH calculation failed");

	$myrow = db_fetch_row($result);

	$category =  $myrow[0];
	return $category;
}

function get_collector_name($id)
{
	$sql = "SELECT real_name FROM ".TB_PREF."users WHERE user_id=".db_escape($id);

	$result = db_query($sql, "could not get sales type");

	$row = db_fetch_row($result);
	return $row[0];
}

function get_cashier_name($id)
{
	$sql = "SELECT real_name FROM ".TB_PREF."users WHERE id=".db_escape($id);

	$result = db_query($sql, "could not get sales type");

	$row = db_fetch_row($result);
	return $row[0];
}

/*function get_payment_applied($type, $trans_no)
{
    $sql = "SELECT SUM(payment_applied) AS applied_pay FROM debtor_loan_ledger
			WHERE trans_type_from = '".$type."' AND payment_trans_no = '".$trans_no."'";

    $result = db_query($sql, "Payment applied failed");

	$myrow = db_fetch_row($result);

	$payment_appl =  $myrow[0];
	return $payment_appl;
}*/

function get_penalty_applied($type, $trans_no)
{
    $sql = "SELECT SUM(penalty) AS penalty_pay FROM debtor_loan_ledger
			WHERE trans_type_from = '".$type."' AND payment_trans_no = '".$trans_no."'";

    $result = db_query($sql, "Penalty applied failed");

	$myrow = db_fetch_row($result);

	$penalty_appl =  $myrow[0];
	return $penalty_appl;
}

function get_rabate_applied($type, $trans_no)
{
    $sql = "SELECT SUM(rebate) AS rebate_pay FROM debtor_loan_ledger
			WHERE trans_type_from = '".$type."' AND payment_trans_no = '".$trans_no."'";

    $result = db_query($sql, "Penalty applied failed");

	$myrow = db_fetch_row($result);

	$rebate_appl =  $myrow[0];
	return $rebate_appl;
}

/*function get_partial_applied($trans_no, $from)
{
    $sql = "SELECT SUM(A.payment_applied)
				FROM ".TB_PREF."debtor_loan_ledger A	
			WHERE A.trans_no = '".$trans_no."' AND A.date_paid < '$from'";

    $result = db_query($sql, "Partial applied failed");

	$myrow = db_fetch_row($result);

	$partial_appl = $myrow[0];
	return $partial_appl;
}

function get_principal_applied($trans_no, $from)
{
    $sql = "SELECT A.principal_due, A.date_due, A.id, B.date_paid
				FROM ".TB_PREF."debtor_loan_schedule A	
				INNER JOIN ".TB_PREF."debtor_loan_ledger B ON B.loansched_id = A.id
			WHERE A.trans_no = '".$trans_no."' AND (B.date_paid < '$from') AND (A.date_due < '$from') GROUP BY A.id";
    $result = db_query($sql, "Partial applied failed");

	//$myrow = db_fetch_row($result);

	while ($myrow = db_fetch($result)) {
		$principal_appl +=  $myrow['principal_due'];
	}	
	return $principal_appl;
}*/

function get_advance_payment($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date) {

    $total = 0;

    $sql = "SELECT SUM(A.payment_applied) AS payment
        FROM ".TB_PREF."debtor_loan_ledger A
            INNER JOIN ".TB_PREF."debtor_loan_schedule B ON A.loansched_id = B.id AND A.trans_no = B.trans_no 
	            AND A.debtor_no = B.debtor_no
                AND DATE_FORMAT(B.date_due, '%Y-%m') >= DATE_FORMAT(DATE_ADD('$end_date', INTERVAL +1 MONTH), '%Y-%m') ";

    $sql .= " WHERE B.month_no <> 0 AND A.debtor_no = ".db_escape($debtor_no) . 
        " AND A.payment_trans_no = ".db_escape($trans_no) .
        " AND A.trans_type_from = ".db_escape($trans_type);

    $sql .= " AND DATE_FORMAT(A.date_paid, '%Y-%m') = DATE_FORMAT('$end_date', '%Y-%m')";
    
    $res = db_query($sql, 'Cant get advance payment!');
    $row = db_fetch_row($res);
	$total = $row[0];

    return $total != null ? $total : 0;
}

function get_payment_this_month($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date) {

    $total = 0;

    $sql = "SELECT SUM(A.payment_applied) AS payment
        FROM ".TB_PREF."debtor_loan_ledger A
            INNER JOIN ".TB_PREF."debtor_loan_schedule B ON A.loansched_id = B.id AND A.trans_no = B.trans_no 
	            AND A.debtor_no = B.debtor_no
				AND DATE_FORMAT(B.date_due, '%Y-%m') <= DATE_FORMAT('$end_date', '%Y-%m') ";

    $sql .= " WHERE B.month_no <> 0 AND A.debtor_no = ".db_escape($debtor_no) . 
        " AND A.payment_trans_no = ".db_escape($trans_no) .
        " AND A.trans_type_from = ".db_escape($trans_type);

	$sql .= " AND DATE_FORMAT(A.date_paid, '%Y-%m') <= DATE_FORMAT('$end_date', '%Y-%m')";

    $res = db_query($sql, 'Cant get advance payment!');
    $row = db_fetch_row($res);
	$total = $row[0];

    return $total != null ? $total : 0;
}

function print_PO_Report()
{
    global $path_to_root;
	
    $from 		= $_POST['PARAM_0'];
	$to 		= $_POST['PARAM_1'];
	$customer = $_POST['PARAM_2'];
	$collector 	= $_POST['PARAM_3'];
	$cashier 	= $_POST['PARAM_4'];
    //$group = $_POST['PARAM_5'];
	//$orientation= $_POST['PARAM_6'];
	$destination= $_POST['PARAM_5'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
		
		
	$orientation = 'L';
	
    $dec = user_price_dec();

    if ($customer == ALL_TEXT)
        $cust = _('ALL');
    else
        $cust = get_customer_name($customer);
        $dec = user_price_dec();

    if ($collector == '')
		$collector_collection = _('ALL');
	else
   		$collector_collection = get_collector_name($collector);

   	if ($cashier == '')
		$cashier_collection = _('ALL');
	else
   		$cashier_collection = get_cashier_name($cashier);

	/*if($group == 1) {
		$groupName = 'Chart of Accounts';
	}elseif($group == 2) {
		$groupName = 'Collector';
	}else{
		$groupName = 'Area';
	}*/

	$date = explode('/', $to);
	$year1 = $date[2];
	$year2 = $year1 - 1;
	$year3 = $year2 - 1;
	$year4 = $year3 - 1;
	$year5 = $year4 - 1;
	$year6 = $year5 - 1;
	$year7 = $year6 - 1;
	$year8 = $year7 - 1;
	$year9 = $year8 - 1;

	$params = array(0 => $comments,
		1 => array('text' => _('Transaction Date'),'from' => $from, 'to' => $to),
		2 => array('text' => _('Customer'), 'from' => htmlentities($cust), 'to' => ''),
		3 => array('text' => _('Collector'), 'from' => $collector_collection, 'to' => ''),
		4 => array('text' => _('Cashier'), 'from' => $cashier_collection, 'to' => '')
		//5 => array('text' => _('Group by'), 'from' => $groupName, 'to' => '')
	);

	$cols = array(0, 50, 110, 230, 430, 530, 570, 640, 700, 
	760, 820, 880, 940, 1000, 1060, 1120, 1180, 1240, 1300);

	$headers = array(
		_('Date'), 
		_('OR Num'),
		_('Name'),
		_('Particulars'),
		_('Collection Type'),
		_('Type'),
		_('Category'),
		_('Buy Date'),
		_('Amount'),
		_('Advance'),
		_('A/R' . $year1),
		_('A/R' . $year2),
		_('A/R' . $year3),
		_('A/R' .$year4. '' . '&' . 'blw'),
		_('Rebate'),
		_('Penalty'),
		_('Credit'),
		_('Debit'));

	$aligns = array('left', 'left', 'left', 'left', 'left', 
	'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 
	'left', 'left', 'left', 'left');

    $rep = new FrontReport(_('Daily Summary Of Collection - Allocation V2'), "DailySummaryCollectionAllocation", "Legal", 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
	
	$rep->fontSize -= 1;
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    if ($destination) {
        $rep->SetHeaderType('PO_Header');
    }
    else {
        $rep->SetHeaderType('COLLECTION_Header');     
    }
	$rep->NewPage();

	$Tot_Val = $totaladvance = $ar1 = $ar2 = $ar3 = $ar4 = $rb = $pn = $cr = $dr = 0.0;  
	$Ar1 = $Ar2 = $Ar3 = $Ar4 = $cr_remittance = $remit1 = $remit2 = $remit3 = $remit4 = $remit5 = $remit6 = $remit7 = $remit8 = 0.0;

	$grandtotal = $grandtotaladvance = $grandar1 = $grandar2 = $grandar3 = $grandar4 = $grandrebate = $grandpenalty = $grandcr = 
	$remit_total = $grandtotalall = $grandcrall = 0.0;

	$res = getTransactions($from, $to, $cust, $collector, $cashier, $Type, $group);
    $Alloc_name = '';

	while ($DSOC = db_fetch($res))
	{
		if ($Alloc_name != $DSOC['alolocate_type']) {
			if ($Alloc_name != '') {
				$rep->NewLine(2);
				$rep->Font('bold');
				$rep->Line($rep->row  - 4);
				$rep->TextCol(5, 8, _('Total Per') . " " .$Alloc_name);
				$rep->AmountCol(8, 9, $Tot_Val, $dec);
				$rep->AmountCol(9, 10, $totaladvance, $dec);
				$rep->AmountCol(10, 11, $ar1, $dec);
				$rep->AmountCol(11, 12, $ar2, $dec);
				$rep->AmountCol(12, 13, $ar3, $dec);
				$rep->AmountCol(13, 14, $ar4, $dec);
				$rep->AmountCol(14, 15, $rb, $dec);
				$rep->AmountCol(15, 16, $pn, $dec);
				$rep->AmountCol(16, 17, $cr, $dec);
				$rep->AmountCol(17, 18, $dr, $dec);
				$rep->Line($rep->row  - 4);
				$rep->Font();
				$rep->NewLine(2);
				$Tot_Val = 0.0;
				$totaladvance = 0.0;
				$ar1 = 0.0;
				$ar2 = 0.0;
				$ar3 = 0.0;
				$ar4 = 0.0;
				$pn = 0.0;
				$rb = 0.0;
				$cr = 0.0;
			}

			$rep->Font('bold');
			$rep->SetTextColor(0, 0, 255);
			//$rep->TextCol(0, 1, $DSOC['user_id']);		
			$rep->TextCol(0, 3, $DSOC['alolocate_type']);
			$Alloc_name = $DSOC['alolocate_type'];
			$rep->Font();
			$rep->SetTextColor(0, 0, 0);
			$rep->NewLine();	 
		}

		$datetime1 = new DateTime($DSOC['trans_date']);
		$datetime2 = new DateTime($DSOC['date_due']);
		$datedifferent = $datetime1->diff($datetime2)->format("%r%a");
        
		//$payment_appl = get_payment_applied($DSOC['No_type'], $DSOC['No_trans']);
		$penalty_appl = get_penalty_applied($DSOC['No_type'], $DSOC['No_trans']);
		$rebate_appl  =	get_rabate_applied($DSOC['No_type'], $DSOC['No_trans']);
		//$partial_appl = get_partial_applied($DSOC['loan_trans'], $DSOC['trans_date']);
		//$principal_appl = get_principal_applied($DSOC['loan_trans'], $DSOC['trans_date']);

		$get_payment_this_month	= get_payment_this_month($DSOC['No_trans'], $DSOC['No_type'], $DSOC['debtor_nos'], $DSOC['trans_date']);
		$get_advance_payment = get_advance_payment($DSOC['No_trans'], $DSOC['No_type'], $DSOC['debtor_nos'], $DSOC['trans_date']);

		//$partial_pay = $principal_appl - $partial_appl;
		//$principal_due_late = $DSOC['principal_due'];

		if($penalty_appl < 0) {
			$penalty = -$penalty_appl;
		}else{
			$penalty = $penalty_appl;
		}

		$ar_total = $DSOC['amt'] - $penalty + $rebate_appl;
		$downtotal = $DSOC['amt'] + $rebate_appl;

		if ($get_payment_this_month & $get_advance_payment == 0) {
			$ar_payment = $ar_total;
		}else{
			$ar_payment = $get_payment_this_month;
		}

		$Type = $DSOC['PayType'];
	    if ($Type == 'amort'){
			$Ar = $get_payment_this_month;
			$Dw = 0;
	    }elseif($Type == 'down'){
			$Dw = $downtotal;
	   		$Ar = 0;
		}elseif($Type == 'other'){
			$Dw = $downtotal;
	   		$Ar1 = 0;
	   		$Ar2 = 0;
	   		$Ar3 = 0;
	   		$Ar4 = 0;
	   		$Ar = 0;
		}elseif ($Type == 'alloc') {
			if($DSOC['Type_Alloc'] == 'ALCN-DP') {
				$Dw = $downtotal;
				$Ar1 = 0;
				$Ar2 = 0;
				$Ar3 = 0;
				$Ar4 = 0;
				$Ar = 0;
			}else{
				$Dw = 0;
				$Ar1 = 0;
				$Ar2 = 0;
				$Ar3 = 0;
				$Ar4 = 0;
				$Ar = $ar_payment;
			}		
		}elseif($Type == '') {
			$Dw = $DSOC['amt'];
	   		$Ar1 = 0;
	   		$Ar2 = 0;
	   		$Ar3 = 0;
	   		$Ar4 = 0;
	   		$Ar = 0;
		} 

		if($DSOC['collection'] == '') {
			$collection = $DSOC['receipt_type'];
		}else{
			$collection = $DSOC['collection'];
		}

		$category = get_category_descrpton($DSOC['masterfile']);
		$invcdate = get_category_invoice_date($DSOC['masterfile']);


		$invoice_date = $invcdate;
		if (date('Y', strtotime($invoice_date)) == $year1){
			if(isset($Ar)){ $Ar1 = $Ar; $Ar2 = ''; $Ar3 = ''; $Ar4 = ''; }else{ $Ar1 = ''; $Ar2 = ''; $Ar3 = ''; $Ar4 = ''; }
	    }else if (date('Y', strtotime($invoice_date)) == $year2){
			if(isset($Ar)){ $Ar1 = ''; $Ar2 = $Ar; $Ar3 = ''; $Ar4 = ''; }else{ $Ar1 = ''; $Ar2 = ''; $Ar3 = ''; $Ar4 = ''; }
	    }else if (date('Y', strtotime($invoice_date)) == $year3){
			if(isset($Ar)){ $Ar1 = ''; $Ar2 = ''; $Ar3 = $Ar; $Ar4 = ''; }else{ $Ar1 = ''; $Ar2 = ''; $Ar3 = ''; $Ar4 = ''; }
	    }else if (date('Y', strtotime($invoice_date)) == $year4){
			if(isset($Ar)){ $Ar1 = ''; $Ar2 = ''; $Ar3 = ''; $Ar4 = $Ar; }else{ $Ar1 = ''; $Ar2 = ''; $Ar3 = ''; $Ar4 = ''; }
	    }else if (date('Y', strtotime($invoice_date)) == $year5){
			if(isset($Ar)){ $Ar1 = ''; $Ar2 = ''; $Ar3 = ''; $Ar4 = $Ar; }else{ $Ar1 = ''; $Ar2 = ''; $Ar3 = ''; $Ar4 = ''; }
	    }else if (date('Y', strtotime($invoice_date)) == $year6){
			if(isset($Ar)){ $Ar1 = ''; $Ar2 = ''; $Ar3 = ''; $Ar4 = $Ar; }else{ $Ar1 = ''; $Ar2 = ''; $Ar3 = ''; $Ar4 = ''; }
	    }else if (date('Y', strtotime($invoice_date)) == $year7){
			if(isset($Ar)){ $Ar1 = ''; $Ar2 = ''; $Ar3 = ''; $Ar4 = $Ar; }else{ $Ar1 = ''; $Ar2 = ''; $Ar3 = ''; $Ar4 = ''; }
	    }else if (date('Y', strtotime($invoice_date)) == $year8){
			if(isset($Ar)){ $Ar1 = ''; $Ar2 = ''; $Ar3 = ''; $Ar4 = $Ar; }else{ $Ar1 = ''; $Ar2 = ''; $Ar3 = ''; $Ar4 = ''; }
	    }else if (date('Y', strtotime($invoice_date)) == $year9){
			if(isset($Ar)){ $Ar1 = ''; $Ar2 = ''; $Ar3 = ''; $Ar4 = $Ar; }else{ $Ar1 = ''; $Ar2 = ''; $Ar3 = ''; $Ar4 = ''; }
	    }

		$amt = $DSOC['amt'];
		//$rebate = $DSOC['rebate'];
		//$penalty = $DSOC['penalty'];

		if($category == '') {
			$category = '';
			$invcdate = $DSOC['trans_date'];
		}else{
			$category = $category;
			$invcdate = $invcdate;
		}

		$rep->NewLine();
		$rep->TextCol(0, 1, sql2date($DSOC['trans_date']));
		//$rep->TextCol(1, 2, str_replace(getCompDet('branch_code') . "-", "", $DSOC['ref'])); 
		$rep->TextCol(1, 2, $DSOC['ref']);
        $rep->SetTextColor(0, 102, 0);
		$rep->TextCol(2, 3, htmlentities($DSOC['name']));
        $rep->SetTextColor(0, 0, 0);
		$rep->TextCol(3, 4, $DSOC['memo_']);
		$rep->TextCol(4, 5, $collection);
		$rep->TextCol(5, 6, $DSOC['Type']);
		$rep->TextCol(6, 7, $category);
		$rep->TextCol(7, 8, sql2date($invcdate));
		$rep->AmountCol(8, 9, $amt, $dec);
		$rep->AmountCol(9, 10, $get_advance_payment, $dec);
		$rep->AmountCol(10, 11, $Ar1, $dec);
		$rep->AmountCol(11, 12, $Ar2, $dec);
		$rep->AmountCol(12, 13, $Ar3, $dec);
		$rep->AmountCol(13, 14, $Ar4, $dec);
		$rep->AmountCol(14, 15, $rebate_appl, $dec);
		$rep->SetTextColor(255, 0, 0);
		$rep->AmountCol(15, 16, $penalty, $dec);
        $rep->SetTextColor(0, 0, 0);
		$rep->AmountCol(16, 17, $Dw, $dec);
		$rep->AmountCol(17, 18, $DSOC[''], $dec);
        $rep->NewLine(0.5);


		$Tot_Val += $amt;
		$grandtotal += $amt;

		$totaladvance += $get_advance_payment;
		$grandtotaladvance += $get_advance_payment;

		$ar1 += $Ar1;
		$grandar1 += $Ar1;

		$ar2 += $Ar2;
		$grandar2 += $Ar2;

		$ar3 += $Ar3;
		$grandar3 += $Ar3;

		$ar4 += $Ar4;
		$grandar4 += $Ar4;

		$rb += $rebate_appl;
		$grandrebate += $rebate_appl;

		$pn += $penalty;
		$grandpenalty += $penalty;

		$cr += $Dw;
		$grandcr += $Dw;
	}

	$rep->NewLine(0);

	
	if ($Alloc_name != $DSOC['alolocate_type']) {

		if ($Alloc_name != '') {
			$rep->NewLine(2);
			$rep->Font('bold');
			$rep->Line($rep->row  - 4);
			$rep->TextCol(5, 8, _('Total Per') . " " .$Alloc_name);
			$rep->AmountCol(8, 9, $Tot_Val, $dec);
			$rep->AmountCol(9, 10, $totaladvance, $dec);
			$rep->AmountCol(10, 11, $ar1, $dec);
			$rep->AmountCol(11, 12, $ar2, $dec);
			$rep->AmountCol(12, 13, $ar3, $dec);
			$rep->AmountCol(13, 14, $ar4, $dec);
			$rep->AmountCol(14, 15, $rb, $dec);
			$rep->AmountCol(15, 16, $pn, $dec);
			$rep->AmountCol(16, 17, $cr, $dec);
			$rep->AmountCol(17, 18, $dr, $dec);
			$rep->Line($rep->row  - 4);
			$rep->Font();
			$rep->NewLine(2);
			$Tot_Val = 0.0;
			$totaladvance = 0.0;
			$ar1 = 0.0;
			$ar2 = 0.0;
			$ar3 = 0.0;
			$ar4 = 0.0;
			$pn = 0.0;
			$rb = 0.0;
			$cr = 0.0;
		}

		$rep->Font('bold');
		$rep->SetTextColor(0, 0, 255);
		//$rep->TextCol(0, 1, $DSOC['user_id']);		
		$rep->TextCol(0, 3, $DSOC['alolocate_type']);
		$Alloc_name = $DSOC['alolocate_type'];
		$rep->Font();
		$rep->SetTextColor(0, 0, 0);
		$rep->NewLine();	 
	}
    
	$grandtotalall = $grandtotal;
	$grandcrall = $grandcr;
	
	$rep->NewLine(2);
	$rep->Line($rep->row - 2);
	$rep->Font('bold');
	$rep->fontSize += 0;	
	$rep->TextCol(6, 8, _('Grand Total'));
	$rep->AmountCol(8, 9, $grandtotalall, $dec);
	$rep->AmountCol(9, 10, $grandtotaladvance, $dec);
	$rep->AmountCol(10, 11, $grandar1, $dec);
	$rep->AmountCol(11, 12, $grandar2, $dec);
	$rep->AmountCol(12, 13, $grandar3, $dec);
	$rep->AmountCol(13, 14, $grandar4, $dec);
	$rep->AmountCol(14, 15, $grandrebate, $dec);
	$rep->AmountCol(15, 16, $grandpenalty, $dec);
	$rep->AmountCol(16, 17, $grandcrall, $dec);
	$rep->AmountCol(17, 18, $dr, $dec);
	//$rep->SetFooterType('compFooter');
    $rep->End();
}
