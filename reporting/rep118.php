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
// date:	2021-04-29
// Title:	Daily Summary Of Collection V2
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
	
	$sql ="SELECT A.masterfile, A.trans_date, A.ref, A.amount AS amt, B.ov_discount AS rebate,
			B.payment_type AS Type, C.name, E.payment_applied AS payment, E.penalty,
			F.month_no, I.collection, K.account_name AS Coa_name, M.description AS AREA,
			O.real_name AS Collector_Name, P.memo_,

			A.amount - IFNULL(E.penalty, 0) + B.ov_discount AS artotal,
			A.amount + B.ov_discount AS downtotal,
			A.amount - IFNULL(E.penalty, 0) + B.ov_discount AS advancetotal,
			A.amount - E.payment_applied + B.ov_discount - IFNULL(E.penalty, 0) AS advance,

			CASE 
			    WHEN A.type = " . ST_CUSTPAYMENT . " THEN 'Office Collection Receipt'
			    WHEN A.type = " . ST_BANKDEPOSIT . " THEN 'Receipt Entries'
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
			AND A.type IN (" . ST_BANKDEPOSIT . ", " . ST_CUSTPAYMENT . ")";

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
                $sql .= "GROUP BY A.ref, A.trans_no, Type";                
                $sql .= " ORDER BY A.ref DESC";
            }
            else if ($group == 2) {
                $sql .= "GROUP BY A.ref, A.trans_no, Type";                
                $sql .= " ORDER BY A.ref DESC";
            } else {
                $sql .= "GROUP BY A.ref, A.trans_no, Type";                
                $sql .= " ORDER BY A.ref DESC";
            }

    return db_query($sql, "No transactions were returned");
}

function get_category_invoice_date($trans_no){

    $sql = "SELECT A.invoice_date FROM debtor_loans A
		LEFT JOIN stock_category B ON B.category_id = A.category_id
		WHERE A.trans_no = '".$trans_no."'";

    $result = db_query($sql, "QOH calculation failed");

	$myrow = db_fetch_row($result);

	$invcdate =  $myrow[0];
	return $invcdate;
}

function get_category_descrpton($trans_no){

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

function print_PO_Report()
{
    global $path_to_root;
	
    $from 		= $_POST['PARAM_0'];
	$to 		= $_POST['PARAM_1'];
	$customer = $_POST['PARAM_2'];
	$collector 	= $_POST['PARAM_3'];
	$cashier 	= $_POST['PARAM_4'];
    $group = $_POST['PARAM_5'];
	$orientation= $_POST['PARAM_6'];
	$destination= $_POST['PARAM_7'];

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
		1 => array('text' => _('Period'),'from' => $from, 'to' => $to),
		2 => array('text' => _('Customer'), 'from' => $cust, 'to' => ''),
		3 => array('text' => _('Collector'), 'from' => $collector_collection, 'to' => ''),
		4 => array('text' => _('Cashier'), 'from' => $cashier_collection, 'to' => '')
	);

	$cols = array(0, 35, 90, 195, 250, 273, 323, 360, 
	410, 450, 492, 535, 572, 615, 647, 679, 720);

	$headers = array(
		_('Date'), 
		_('OR Num'),
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
		_('CR'),
		_('DR'));

	$aligns = array('left', 'left', 'left', 'left', 'left', 
	'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 
	'left', 'left', 'left',);

    $rep = new FrontReport(_('Daily Summary Of Collection V2'), "DailySummaryCollection", "Legal", 9, $orientation);
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
	$Ar1 = $Ar2 = $Ar3 = $Ar4 = 0.0;
	$grandtotal = $grandtotaladvance = $grandar1 = $grandar2 = $grandar3 = $grandar4 = $grandrebate = $grandpenalty = $grandcr = 0.0;

	$res = getTransactions($from, $to, $cust, $collector, $cashier, $Type, $group);
    $Collector_Name = $Coa_name = '';

	while ($DSOC = db_fetch($res))
	{
		if ($group == 1) {
            if ($Coa_name != $DSOC['Coa_name']) {

                if ($Coa_name != '') {
                    $rep->NewLine(2);
					$rep->Font('bold');
					$rep->Line($rep->row  - 4);
					$rep->TextCol(0, 7, _('Total Per Collector'));
					$rep->AmountCol(7, 8, $Tot_Val);
					$rep->AmountCol(8, 9, $totaladvance);
					$rep->AmountCol(9, 10, $ar1);
					$rep->AmountCol(10, 11, $ar2);
					$rep->AmountCol(11, 12, $ar3);
					$rep->AmountCol(12, 13, $ar4);
					$rep->AmountCol(13, 14, $rb);
					$rep->AmountCol(14, 15, $pn);
					$rep->AmountCol(15, 16, $cr);
					$rep->AmountCol(16, 17, $dr);
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
				$rep->TextCol(0, 3, $DSOC['Coa_name']);
				$Coa_name = $DSOC['Coa_name'];
				$rep->Font();
	            $rep->SetTextColor(0, 0, 0);
	            $rep->NewLine();	 
            }
        } else if ($group == 2){
            if ($Collector_Name != $DSOC['Collector_Name']) {

                if ($Collector_Name != '') {
                    $rep->NewLine(2);
					$rep->Font('bold');
					$rep->Line($rep->row  - 4);
					$rep->TextCol(0, 7, _('Total Per Collector'));
					$rep->AmountCol(7, 8, $Tot_Val);
					$rep->AmountCol(8, 9, $totaladvance);
					$rep->AmountCol(9, 10, $ar1);
					$rep->AmountCol(10, 11, $ar2);
					$rep->AmountCol(11, 12, $ar3);
					$rep->AmountCol(12, 13, $ar4);
					$rep->AmountCol(13, 14, $rb);
					$rep->AmountCol(14, 15, $pn);
					$rep->AmountCol(15, 16, $cr);
					$rep->AmountCol(16, 17, $dr);
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
				$rep->TextCol(0, 1, $DSOC['user_id']);		
				$rep->TextCol(1, 3, $DSOC['Collector_Name']);
				$Collector_Name = $DSOC['Collector_Name'];
				$rep->Font();
	            $rep->SetTextColor(0, 0, 0);
	            $rep->NewLine();	 
            }
        } else {
            if ($AREA != $DSOC['AREA']) {

                if ($AREA != '') {
                    $rep->NewLine(2);
					$rep->Font('bold');
					$rep->Line($rep->row  - 4);
					$rep->TextCol(0, 7, _('Total Per Collector'));
					$rep->AmountCol(7, 8, $Tot_Val);
					$rep->AmountCol(8, 9, $totaladvance);
					$rep->AmountCol(9, 10, $ar1);
					$rep->AmountCol(10, 11, $ar2);
					$rep->AmountCol(11, 12, $ar3);
					$rep->AmountCol(12, 13, $ar4);
					$rep->AmountCol(13, 14, $rb);
					$rep->AmountCol(14, 15, $pn);
					$rep->AmountCol(15, 16, $cr);
					$rep->AmountCol(16, 17, $dr);
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
				$rep->TextCol(0, 3, $DSOC['AREA']);
				$AREA = $DSOC['AREA'];
				$rep->Font();
	            $rep->SetTextColor(0, 0, 0);
	            $rep->NewLine();	 
            }
        }

		$month_no = $DSOC['month_no'];
		if ($month_no == 0) {
			$advance = 0;
		}else {
			$advance = $DSOC['advance'];
		}

		$Type = $DSOC['Type'];
	    if ($Type == 'amort'){
			$Ar = $DSOC['artotal'] - $DSOC['advance'];
			$Dw = '';
	    }elseif($Type == 'down'){
			$Dw = $DSOC['downtotal'] - $DSOC['advance'];
	   		$Ar = '';
		}elseif($Type == 'other'){
			$Dw = $DSOC['downtotal'];
	   		$Ar1 = 0;
	   		$Ar2 = 0;
	   		$Ar3 = 0;
	   		$Ar4 = 0;
		}elseif ($Type == 'alloc') {
			$Dw = $DSOC['downtotal'] - $DSOC['advance'];
	   		$Ar1 = 0;
	   		$Ar2 = 0;
	   		$Ar3 = 0;
	   		$Ar4 = 0;
	   		$Ar = 0;
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
		$rebate = $DSOC['rebate'];
		$penalty = $DSOC['penalty'];

		if($DSOC['collection'] == '') {
			$category = '';
			$invcdate = $DSOC['trans_date'];
		}else{
			$category = $category;
			$invcdate = $invcdate;
		}

		$rep->NewLine();
		$rep->TextCol(0, 1, sql2date($DSOC['trans_date']));
		$rep->TextCol(1, 2, str_replace(getCompDet('branch_code') . "-", "", $DSOC['ref']));
		$rep->NewLine(0.8);
        $rep->SetTextColor(0, 102, 0);
		$rep->TextCol(0, 3, $DSOC['name']);
        $rep->SetTextColor(0, 0, 0);
		$rep->TextCol(2, 3, $DSOC['memo_']);
		$rep->TextCol(3, 4, $collection);
		$rep->TextCol(4, 5, $DSOC['Type']);
		$rep->TextCol(5, 6, $category);
		$rep->TextCol(6, 7, sql2date($invcdate));
		$rep->AmountCol(7, 8, $amt);
		$rep->AmountCol(8, 9, $advance);
		$rep->AmountCol(9, 10, $Ar1);
		$rep->AmountCol(10, 11, $Ar2);
		$rep->AmountCol(11, 12, $Ar3);
		$rep->AmountCol(12, 13, $Ar4);
		$rep->AmountCol(13, 14, $rebate);
		$rep->SetTextColor(255, 0, 0);
		$rep->AmountCol(14, 15, $penalty);
        $rep->SetTextColor(0, 0, 0);
		$rep->AmountCol(15, 16, $Dw);
		$rep->AmountCol(16, 17, $DSOC['']);
        $rep->NewLine(0.5);


		$Tot_Val += $amt;
		$grandtotal += $amt;

		$totaladvance += $advance;
		$grandtotaladvance += $advance;

		$ar1 += $Ar1;
		$grandar1 += $Ar1;

		$ar2 += $Ar2;
		$grandar2 += $Ar2;

		$ar3 += $Ar3;
		$grandar3 += $Ar3;

		$ar4 += $Ar4;
		$grandar4 += $Ar4;

		$rb += $rebate;
		$grandrebate += $rebate;

		$pn += $penalty;
		$grandpenalty += $penalty;

		$cr += $Dw;
		$grandcr += $Dw;
	}


	$rep->NewLine(0);

	if ($group == 1) {
        if ($Coa_name != $DSOC['Coa_name']) {

            if ($Coa_name != '') {
                $rep->NewLine(2);
				$rep->Font('bold');
				$rep->Line($rep->row  - 4);
				$rep->TextCol(0, 7, _('Total Per Collector'));
				$rep->AmountCol(7, 8, $Tot_Val);
				$rep->AmountCol(8, 9, $totaladvance);
				$rep->AmountCol(9, 10, $ar1);
				$rep->AmountCol(10, 11, $ar2);
				$rep->AmountCol(11, 12, $ar3);
				$rep->AmountCol(12, 13, $ar4);
				$rep->AmountCol(13, 14, $rb);
				$rep->AmountCol(14, 15, $pn);
				$rep->AmountCol(15, 16, $cr);
				$rep->AmountCol(16, 17, $dr);
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
			$rep->TextCol(0, 3, $DSOC['Coa_name']);
			$Coa_name = $DSOC['Coa_name'];
			$rep->Font();
            $rep->SetTextColor(0, 0, 0);
            $rep->NewLine();	 
        }
    } else if ($group == 2){
        if ($Collector_Name != $DSOC['Collector_Name']) {

            if ($Collector_Name != '') {
                $rep->NewLine(2);
				$rep->Font('bold');
				$rep->Line($rep->row  - 4);
				$rep->TextCol(0, 7, _('Total Per Collector'));
				$rep->AmountCol(7, 8, $Tot_Val);
				$rep->AmountCol(8, 9, $totaladvance);
				$rep->AmountCol(9, 10, $ar1);
				$rep->AmountCol(10, 11, $ar2);
				$rep->AmountCol(11, 12, $ar3);
				$rep->AmountCol(12, 13, $ar4);
				$rep->AmountCol(13, 14, $rb);
				$rep->AmountCol(14, 15, $pn);
				$rep->AmountCol(15, 16, $cr);
				$rep->AmountCol(16, 17, $dr);
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
			$rep->TextCol(0, 1, $DSOC['user_id']);		
			$rep->TextCol(1, 3, $DSOC['Collector_Name']);
			$Collector_Name = $DSOC['Collector_Name'];
			$rep->Font();
            $rep->SetTextColor(0, 0, 0);
            $rep->NewLine();	 
        }
    } else {
        if ($AREA != $DSOC['AREA']) {

            if ($AREA != '') {
                $rep->NewLine(2);
				$rep->Font('bold');
				$rep->Line($rep->row  - 4);
				$rep->TextCol(0, 7, _('Total Per Collector'));
				$rep->AmountCol(7, 8, $Tot_Val);
				$rep->AmountCol(8, 9, $totaladvance);
				$rep->AmountCol(9, 10, $ar1);
				$rep->AmountCol(10, 11, $ar2);
				$rep->AmountCol(11, 12, $ar3);
				$rep->AmountCol(12, 13, $ar4);
				$rep->AmountCol(13, 14, $rb);
				$rep->AmountCol(14, 15, $pn);
				$rep->AmountCol(15, 16, $cr);
				$rep->AmountCol(16, 17, $dr);
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
			$rep->TextCol(0, 3, $DSOC['AREA']);
			$AREA = $DSOC['AREA'];
			$rep->Font();
            $rep->SetTextColor(0, 0, 0);
            $rep->NewLine();	 
        }
    }

	
	$rep->NewLine(2);
	$rep->Line($rep->row - 2);
	$rep->Font('bold');
	$rep->fontSize += 0;	
	$rep->TextCol(0, 7, _('Grand Total'));
	$rep->AmountCol(7, 8, $grandtotal);
	$rep->AmountCol(8, 9, $grandtotaladvance);
	$rep->AmountCol(9, 10, $grandar1);
	$rep->AmountCol(10, 11, $grandar2);
	$rep->AmountCol(11, 12, $grandar3);
	$rep->AmountCol(12, 13, $grandar4);
	$rep->AmountCol(13, 14, $grandrebate);
	$rep->AmountCol(14, 15, $grandpenalty);
	$rep->AmountCol(15, 16, $grandcr);
	$rep->AmountCol(16, 17, $dr);
	//$rep->SetFooterType('compFooter');
    $rep->End();
}
