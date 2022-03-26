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
$page_security = 'SA_DAILYCASH';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	RobertGwapo
// date_:	2005-05-19
// Modified By: spyrax10
// Date Modified: 2021-12-14
// Title:	Daily Cash Position Report
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_category_db.inc");

//----------------------------------------------------------------------------------------------------

print_dailycash_sales();

function disbursement_transactions($from, $cashier = '', $cashier_name = '')
{
	$from = date2sql($from);
	$DE = ST_BANKPAYMENT;

	$sql = "SELECT A.ref, A.type, A.trans_date, abs(A.amount) AS amt, A.person_id, A.cashier_user_id, B.name, 
			C.memo_, D.real_name, D.user_id, A.person_type_id, A.masterfile
			
			FROM ".TB_PREF."bank_trans A
				LEFT JOIN ".TB_PREF."debtors_master B ON B.debtor_no = A.person_id
				LEFT JOIN ".TB_PREF."comments C ON C.id = A.trans_no AND C.type = A.type
				LEFT JOIN ".TB_PREF."users D ON D.real_name = A.cashier_user_id
			WHERE A.trans_date = '$from' AND A.type = $DE ";
	
	if ($cashier != '') {
		$sql .= " AND A.cashier_user_id = ".db_escape($cashier);
	}
			
	$sql .= " GROUP BY A.ref, A.type ORDER BY A.trans_date DESC";

    return db_query($sql,"No transactions were returned");
}

function get_dailycash_balance_to($from, $cashier = '', $cashier_name = '')
{
	$date = date2sql($from);

	$sql = "SELECT SUM(A.amount), A.cashier_user_id, B.real_name, B.user_id 
		FROM ".TB_PREF."bank_trans A 
			LEFT JOIN ".TB_PREF."users B ON B.real_name = A.cashier_user_id
		WHERE A.type <> 0 AND A.trans_date < '$date' ";

	if ($cashier != '') {
		$sql .= " AND A.cashier_user_id = ".db_escape($cashier);
	}

	$result = db_query($sql, "The starting balance on hand could not be calculated");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_breakdown_balance($cash = false, $from, $cashier = '', $cashier_name = '') {

	$date = date2sql($from);

	$sql = "SELECT SUM(A.amount), A.cashier_user_id
		FROM ".TB_PREF."bank_trans A 
			LEFT JOIN ".TB_PREF."users B ON B.real_name = A.cashier_user_id
		WHERE A.type <> 0 AND A.opening_balance = 0";

	if ($cash) {
		$sql .= " AND A.pay_type = 'Cash' ";
	}
	else {
		$sql .= " AND A.pay_type = 'Cheque' OR A.pay_type = 'Check' ";
	}

	if ($cashier != '') {
		$sql .= " AND A.cashier_user_id = ".db_escape($cashier);
	}

	$sql .= " AND A.trans_date <= '$date' ";

	$result = db_query($sql, "Cant calculate breakdown balance!");
	$row = db_fetch_row($result);
	return $row[0];

}

//----------------------------------------------------------------------------------------------------

function print_dailycash_sales()
{
    global $path_to_root;

	$from = $_POST['PARAM_0'];
	$cashier = $_POST['PARAM_1'];
	$comments = $_POST['PARAM_2'];
	$destination = $_POST['PARAM_3'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = 'P';
    $dec = user_price_dec();
	$cashier_name = $cashier_display = '';

	if ($cashier != '') {
		$cashier_name = $cashier_display = get_user_name($cashier);
	}
	else {
		$cashier_display = _("ALL CASHIER");
		$cashier_name = '';
	}
	
	$cols = array(3, 70, 170, 360, 0);

	$headers = array(_('Date'), _('Customer'), _('Remarks'), _('Receipt Number'), _('Amount'));

	$aligns = array('left', 'left',	'left', 'left', 'right');

    $params =  array( 
		0 => $comments,
    	1 => array('text' => _('Transaction Date'),'from' => $from),
    	2 => array('text' => _('Cashier'), 'from' => $cashier_display)
	);

    $rep = new FrontReport(_('Daily Cash Position Report'), "DCPR", user_pagesize(), 9, $orientation);
   	if ($orientation == 'L')
    	recalculate_cols($cols);

	$rep->Font('bold');
	//$rep->SetCommonData($myrow, null, $myrow, $baccount, ST_BANKPAYMENT, $contacts);
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->SetHeaderType('PO_Header');
    $rep->NewPage();
	$rep->Font();

	//-----FOR PREVIOUS BALANCE-----//
	$prev_balance = 0;
	$prev_balance = get_dailycash_balance_to($from, $cashier, $cashier_name);
	
	$rep->NewLine(.5);
	$rep->Font('bold');
	$rep->TextCol(0, 4, _('OPENING BALANCE PREVIOUS DAY:'));
	$rep->AmountCol(4, 5, $prev_balance, $dec);
	$rep->Line($rep->row - 2);
	$rep->Font();
	$rep->NewLine(.5);

	//---------------------------//

	$rep->NewLine();

	$res = get_bank_transactions($from, $cashier);
	$disburse_res = disbursement_transactions($from, $cashier, $cashier_name);

	$total = $sum_receipt = $sub_total = $sum_dis = 0.0;
	$trans_type = '';

	//Office Collection Receipt
	while ($trans = db_fetch($res)) {
		if ($trans_type != $trans['receipt_type']) {
			
			if ($trans_type != '') {
				$rep->NewLine(1);
    			$rep->Font('bold');
    			$rep->TextCol(0, 1, _('Sub Total'));
				$rep->AmountCol(4, 5, $sub_total, $dec);
				$rep->Line($rep->row  - 4);
				$rep->NewLine(1.5);
				$sub_total = 0.0;
			}

			$rep->NewLine();
			$rep->fontSize += 1;
			$rep->Font('bold');
			$rep->SetTextColor(0, 0, 255);
			$rep->TextCol(0, 10, strtoupper($trans['receipt_type']));
			$trans_type = $trans['receipt_type'];
			$rep->Font();
			$rep->fontSize -= 1;
			$rep->SetTextColor(0, 0, 0);
			$rep->NewLine();
		}

		$rep->NewLine(1);
		$rep->TextCol(0, 1, sql2date($trans['trans_date']));
		$rep->TextCol(1, 2,	get_person_name($trans['person_type_id'], $trans['person_id']));
		$rep->TextCol(2, 3, $trans['memo_']);
		$rep->TextCol(3, 4, $trans['ref']);
		$rep->AmountCol(4, 5, ABS($trans['amt']), $dec);
		$rep->NewLine(1);
		
		/*$curr = get_customer_currency($trans['debtor_no']);
		$rate = get_exchange_rate_from_home_currency($curr, sql2date($trans['trans_date']));
		$trans['amt'] *= $rate;

		$rep->NewLine(0.5);
		$rep->fontSize = 9;*/

		$sub_total += ABS($trans['amt']);
		$total += ABS($trans['amt']);
		$sum_receipt = $total;
		//------------------------------//
	}
	// End of Office Collection Receipt

	if ($trans_type != '') {
		$rep->NewLine(1);
		$rep->Font('bold');
		$rep->TextCol(0, 1, _('Sub Total'));
		$rep->AmountCol(4, 5, $sub_total, $dec);
		$rep->Line($rep->row  - 4);
		$rep->NewLine(1.5);
	}

	//Disbursement Entry

	$rep->NewLine(1);
	$rep->fontSize += 1;
	$rep->Font('bold');
	$rep->SetTextColor(255, 0, 0);
	$rep->TextCol(0, 4, _('Less : Disbursement Entries'));
	$rep->SetTextColor(0, 0, 0);
	$rep->NewLine(.5);
	$rep->fontSize -= 1;
	$rep->Font();
	
	while ($dis_trans = db_fetch($disburse_res)) {

		$rep->NewLine(1.5);
		$rep->TextCol(0, 1, sql2date($dis_trans['trans_date']));
		$rep->TextCol(1, 2, get_person_name($dis_trans['person_type_id'], $dis_trans['person_id']));
		$rep->TextCol(2, 3, $dis_trans['memo_']);
		$rep->TextCol(3, 4, $dis_trans['ref']);
		$rep->SetTextColor(255, 0, 0);
		$rep->AmountCol(4, 5, $dis_trans['amt'], $dec);
		$rep->SetTextColor(0, 0, 0);
	
		$sum_dis += $dis_trans['amt'];
		$rep->NewLine(1);
	}

	$rep->NewLine(1);
	$rep->Font('bold');
	$rep->SetTextColor(255, 0, 0);
	$rep->TextCol(0, 1, _('Sub Total'));
	$rep->AmountCol(4, 5, $sum_dis, $dec);
	$rep->SetTextColor(0, 0, 0);
	$rep->Line($rep->row  - 4);
	$rep->NewLine(.5);

	//End Disburesement Entry

	$rep->fontSize += 1.5;
	$rep->NewLine(2.5);
	$rep->Font('bold');
	$rep->TextCol(0, 4, _('ENDING BALANCE: '));
	$rep->AmountCol(4, 5, $prev_balance + $sum_receipt - $sum_dis, $dec);
	$rep->NewLine(.5);
	$rep->fontSize -= 1.5;

	$rep->Line($rep->row  - 1);
	$rep->NewLine(2);
	
	$coc = get_breakdown_balance(true, $from, $cashier, $cashier_name);
	$coci = get_breakdown_balance(false, $from, $cashier, $cashier_name);

	$rep->fontSize += 1;
	$rep->TextCol(0, 4, _('ENDING BALANCE BREAKDOWN: '));
	$rep->Font();
	$rep->NewLine(2);
	$rep->TextCol(1, 3, _('Cash On Hand (COH): '));
	$rep->AmountCol(4, 5, $coc, $dec);
	$rep->NewLine(1.5);
	$rep->TextCol(1, 3, _('Cheque & Other Cash Items (COCI): '));
	$rep->AmountCol(4, 5, $coci, $dec);
	
	$rep->NewLine(2);
	$rep->Font('bold');
	$rep->TextCol(1, 3, _('Total Ending Balance: '));
	$rep->AmountCol(4, 5, $coc + $coci, $dec);
	$rep->Font();
	$rep->fontSize -= 1;

	//$rep->SetFooterType('compFooter');
    $rep->End();
}
