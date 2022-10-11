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
$page_security = 'SA_DCPR';
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

function remittance_transactions($from, $fcashier = '', $tcashier = '') {
	
	$from = date2sql($from);

	$sql = "SELECT RT.*, SUM(RT.amount) AS total_amt
		FROM ".TB_PREF."remittance RT";

	$sql .= " WHERE RT.trans_date = '$from'";

	if ($fcashier != '') {
		$sql .= " AND RT.remit_from = ".db_escape($fcashier);
	}

	if ($tcashier != '') {
		$sql .= " AND RT.remit_to = ".db_escape($tcashier);
	} 

	$sql .= " AND RT.remit_stat <> 'Disapproved'"; 

	$sql .= " GROUP BY RT.remit_ref";

	return db_query($sql, "No transactions were returned");
}

function disbursement_transactions($from, $cashier = '') {
	
	$from = date2sql($from);

	$sql = "SELECT A.ref, A.type, A.trans_date, abs(A.amount) AS amt, A.person_id, A.cashier_user_id, B.name, 
			C.memo_, D.real_name, D.user_id, A.person_type_id, A.masterfile, A.receipt_no, A.trans_no, A.bank_act
			
			FROM ".TB_PREF."bank_trans A
				LEFT JOIN ".TB_PREF."debtors_master B ON B.debtor_no = A.person_id
				LEFT JOIN ".TB_PREF."comments C ON C.id = A.trans_no AND C.type = A.type
				LEFT JOIN ".TB_PREF."users D ON D.id = A.cashier_user_id
			WHERE A.trans_date = '$from' AND A.type = " . ST_BANKPAYMENT . " ";
	
	if ($cashier != '') {
		$sql .= " AND (A.cashier_user_id = ".db_escape($cashier) . " || 
			A.remit_from = ".db_escape($cashier) . ")";
	}
	
	$sql .= " AND A.remit_stat <> 'Disapproved'";
			
	$sql .= " GROUP BY A.ref, A.type ORDER BY A.trans_date DESC, A.receipt_no";

    return db_query($sql,"No transactions were returned");
}

function opening_balance($from, $cashier = '') {
	$date = date2sql($from);

	$sql = "SELECT SUM(A.amount), A.cashier_user_id, B.real_name, B.user_id 
		FROM ".TB_PREF."bank_trans A 
			LEFT JOIN ".TB_PREF."users B ON B.id = A.cashier_user_id
			LEFT JOIN  ".TB_PREF."voided C ON A.type = C.type AND A.trans_no = C.id 
				AND C.void_status = 'Voided' 
		WHERE A.type <> 0 AND A.trans_date < '$date' AND ISNULL(C.void_id)";

	if ($cashier != '') {
		$sql .= " AND A.cashier_user_id = ".db_escape($cashier);
	}

	$result = db_query($sql, "The starting balance on hand could not be calculated");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_breakdown_balance($bank_id = '', $from, $cashier = '') {

	$date = date2sql($from);

	$sql = "SELECT SUM(A.amount), A.cashier_user_id
		FROM ".TB_PREF."bank_trans A 
			LEFT JOIN ".TB_PREF."users B ON B.id = A.cashier_user_id
			LEFT JOIN  ".TB_PREF."voided C ON A.type = C.type AND A.trans_no = C.id 
				AND C.void_status = 'Voided' 

		WHERE A.type <> 0 AND A.opening_balance = 0 AND ISNULL(C.void_id)";

	if ($bank_id != '') {
		$sql .= " AND A.bank_act = " .db_escape($bank_id);
	}
	
	if ($cashier != '') {
		$sql .= " AND A.cashier_user_id = ".db_escape($cashier);
	}

	$sql .= " AND A.trans_date <= '$date' ";

	$result = db_query($sql, "get_breakdown_balance()");
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

	if ($destination) {
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	}
	else {
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	}

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

	$cols = array(0, 45, 170, 420, 480, 530, 0);

	$headers = array(
		_('Date'), 
		_('Customer'), 
		_('Remarks'),
		_('Reference'), 
		_('Receipt Number'), 
		_('Amount')
	);

	$aligns = array('left', 'left', 'left', 'center', 'center', 'right');

    $params =  array( 
		0 => $comments,
    	1 => array('text' => _('Transaction Date'),'from' => $from),
    	2 => array('text' => _('Cashier'), 'from' => $cashier_display)
	);

    $rep = new FrontReport(_('Daily Cash Position Report'), "DCPR", "LETTER", 9, $orientation);
   	if ($orientation == 'L') {
		recalculate_cols($cols);
	}

	$rep->Font('bold');
	//$rep->SetCommonData($myrow, null, $myrow, $baccount, ST_BANKPAYMENT, $contacts);
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->SetHeaderType('PO_Header');
    $rep->NewPage();
	$rep->Font();

	//-----FOR PREVIOUS BALANCE-----//
	$prev_balance = 0;
	$prev_balance = opening_balance($from, $cashier);
	
	$rep->NewLine(.5);
	$rep->Font('bold');
	$rep->TextCol(2, 3, _('OPENING BALANCE PREVIOUS DAY:'));
	$rep->AmountCol(5, 6, $prev_balance, $dec);
	//$rep->Line($rep->row - 2);
	$rep->Font();
	$rep->NewLine(.5);

	//---------------------------//

	$rep->NewLine();

	$res = _bank_transactions($from, $cashier);
	$disburse_res = disbursement_transactions($from, $cashier);
	$remit_res = remittance_transactions($from, $cashier);
	$remit_resT = remittance_transactions($from, '', $cashier);

	$total = $rtotal = $sum_receipt = $sub_total = $sub_rtotal = $sum_dis = $sum_remit = 0.0;
	$trans_type = $reference = '';
	$void_bank = $pre_subB = $void_remit = $pre_subR = $void_dis = $pre_subD = 0;

	$Tpre_subR = $Trtotal = $Tsub_rtotal = $Tsum_remit = 0;

	$remitF = 0;

	$rep->fontSize -= 1;

	//Office Collection || Receipt Entries
	while ($trans = db_fetch($res)) {
		$void_entry = get_voided_entry($trans['type'], $trans['trans_no']); 

		if ($trans["remit_from"] == $cashier) {
			if ($trans_type != $trans['receipt_type']) {
			
				if ($trans_type != '') {
					$rep->NewLine();
					// $rep->Font('bold');
					// $rep->TextCol(0, 1, _('Sub Total'));
					// $rep->AmountCol(5, 6, $sub_total, $dec);
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
			if ($trans['bank_act'] == 1 || $trans['bank_act'] == 2) {
				$entry_amt = $trans['amt'];
			}
			else {
				$entry_amt = 0;
			}
	
			if ($void_entry['void_status'] == 'Voided') {
				$void_bank += ABS($entry_amt);
			}
	
			$rep->NewLine(1.2);
			$rep->TextCol(0, 1, sql2date($trans['trans_date']));
			$rep->TextCol(1, 2,	get_person_name($trans['person_type_id'], $trans['person_id']));
			$rep->TextCol(2, 3, $trans['memo_']);
			$rep->TextCol(3, 4, str_replace(getCompDet('branch_code') . "-", "", $trans['ref']));
			$rep->SetTextColor(0, 0, 255);
			$rep->TextCol(4, 5, $trans['receipt_no']);
			$rep->SetTextColor(0, 0, 0);
			$rep->AmountCol(5, 6, ABS($entry_amt), $dec);
	
	
			if ($void_entry['void_status'] == 'Voided') {
	
				$rep->NewLine(1.2);
				$rep->TextCol(0, 1, sql2date($trans['trans_date']));
				$rep->TextCol(1, 2,	get_person_name($trans['person_type_id'], $trans['person_id']));
				$rep->TextCol(2, 3, $trans['memo_']);
				$rep->TextCol(3, 4, str_replace(getCompDet('branch_code') . "-", "", $trans['ref']));
				$rep->SetTextColor(0, 0, 255);
				$rep->TextCol(4, 5, $trans['receipt_no']);
				$rep->SetTextColor(0, 0, 0);
				$rep->SetTextColor(255, 0, 0);
				$rep->TextCol(5, 6, "(" . price_format(ABS($entry_amt)) . ")");
				$rep->SetTextColor(0, 0, 0);
			}
	
			$pre_subB += ABS($entry_amt);
			$total += ABS($entry_amt);
	
			$sub_total = $pre_subB - $void_bank;
			$sum_receipt = $total - $void_bank;
		}
		else {
			if ($trans["remit_from"] == 0) {
				if ($trans_type != $trans['receipt_type']) {
			
					if ($trans_type != '') {
						$rep->NewLine();
						// $rep->Font('bold');
						// $rep->TextCol(0, 1, _('Sub Total'));
						// $rep->AmountCol(5, 6, $sub_total, $dec);
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
				if ($trans['bank_act'] == 1 || $trans['bank_act'] == 2) {
					$entry_amt = $trans['amt'];
				}
				else {
					$entry_amt = 0;
				}
		
				if ($void_entry['void_status'] == 'Voided') {
					$void_bank += ABS($entry_amt);
				}
		
				$rep->NewLine(1.2);
				$rep->TextCol(0, 1, sql2date($trans['trans_date']));
				$rep->TextCol(1, 2,	get_person_name($trans['person_type_id'], $trans['person_id']));
				$rep->TextCol(2, 3, $trans['memo_']);
				$rep->TextCol(3, 4, str_replace(getCompDet('branch_code') . "-", "", $trans['ref']));
				$rep->SetTextColor(0, 0, 255);
				$rep->TextCol(4, 5, $trans['receipt_no']);
				$rep->SetTextColor(0, 0, 0);
				$rep->AmountCol(5, 6, ABS($entry_amt), $dec);
		
		
				if ($void_entry['void_status'] == 'Voided') {
		
					$rep->NewLine(1.2);
					$rep->TextCol(0, 1, sql2date($trans['trans_date']));
					$rep->TextCol(1, 2,	get_person_name($trans['person_type_id'], $trans['person_id']));
					$rep->TextCol(2, 3, $trans['memo_']);
					$rep->TextCol(3, 4, str_replace(getCompDet('branch_code') . "-", "", $trans['ref']));
					$rep->SetTextColor(0, 0, 255);
					$rep->TextCol(4, 5, $trans['receipt_no']);
					$rep->SetTextColor(0, 0, 0);
					$rep->SetTextColor(255, 0, 0);
					$rep->TextCol(5, 6, "(" . price_format(ABS($entry_amt)) . ")");
					$rep->SetTextColor(0, 0, 0);
				}
		
				$pre_subB += ABS($entry_amt);
				$total += ABS($entry_amt);
		
				$sub_total = $pre_subB - $void_bank;
				$sum_receipt = $total - $void_bank;				
			}
		}
	}
	// End of Office Collection Receipt

	if ($trans_type != '') {
		$rep->NewLine(2);
		$rep->Font('bold');
		$rep->TextCol(0, 1, _('Sub Total'));
		$rep->AmountCol(5, 6, $sub_total, $dec);
		$rep->Line($rep->row  - 4);
		$rep->NewLine(1.5);
	}

	//Remittance Entry
	$rep->NewLine(2);
	$rep->fontSize += 1;
	$rep->Font('bold');
	$rep->SetTextColor(0, 0, 255);
	$rep->TextCol(0, 4, _('REMITTANCE ENTRIES:'));
	$rep->SetTextColor(0, 0, 0);
	$rep->fontSize -= 1;
	$rep->Font();
	$rep->NewLine(1);

	while ($remit_transT = db_fetch($remit_resT)) {

		$bank_row = db_fetch(get_bank_trans($remit_transT['type'], null, null, null, $remit_transT['remit_ref']));
		$void_entry = get_voided_entry(ST_REMITTANCE, $remit_transT['id']); 

		$rep->NewLine(1.2);
		$rep->TextCol(0, 1, sql2date($remit_transT['trans_date']));
		$rep->TextCol(1, 2,	get_user_name($remit_transT['remit_from']));
		$rep->TextCol(2, 3, $remit_transT['remit_memo']);

		$rep->TextCol(3, 4, str_replace(getCompDet('branch_code') . "-", "", $remit_transT['remit_ref']));
		$rep->TextCol(4, 5, '');

		$rep->AmountCol(5, 6, $remit_transT['total_amt'], $dec);

		$Tpre_subR += $remit_transT['total_amt'];
		$Trtotal += $remit_transT['total_amt'];

		$Tsub_rtotal = $Tpre_subR - $void_remit;
		$Tsum_remit = $Trtotal - $void_remit;
	}

	while ($remit_trans = db_fetch($remit_res)) {

		$bank_row = db_fetch(get_bank_trans($remit_trans['type'], null, null, null, $remit_trans['remit_ref']));
		$void_entry = get_voided_entry(ST_REMITTANCE, $remit_trans['id']); 

		$rep->NewLine(1.2);
		$rep->TextCol(0, 1, sql2date($remit_trans['trans_date']));
		$rep->TextCol(1, 2,	get_user_name($remit_trans['remit_from']));
		$rep->TextCol(2, 3, $remit_trans['remit_memo']);

		$rep->TextCol(3, 4, str_replace(getCompDet('branch_code') . "-", "", $remit_trans['remit_ref']));
		$rep->TextCol(4, 5, '');

		$rep->SetTextColor(255, 0, 0);
		$rep->TextCol(5, 6, "(" . price_format($remit_trans['total_amt']) . ")");
		$rep->SetTextColor(0, 0, 0);

		$pre_subR += -$remit_trans['total_amt'];
		$rtotal += -$remit_trans['total_amt'];

		$sub_rtotal = $pre_subR - $void_remit;
		$sum_remit = $rtotal - $void_remit;
	}

	$rep->NewLine(2);
	$rep->Font('bold');
	$rep->TextCol(0, 1, _('Sub Total'));
	if (($Tsub_rtotal + $sub_rtotal) > 0) {
		$rep->AmountCol(5, 6, $Tsub_rtotal + $sub_rtotal, $dec);
	}
	else {
		$rep->SetTextColor(255, 0, 0);
		$rep->TextCol(5, 6, "(" . price_format($Tsub_rtotal + ABS($sub_rtotal)) . ")");
		$rep->SetTextColor(0, 0, 0);
	}
	$rep->Line($rep->row  - 4);
	$rep->NewLine(1.5);
	

	//End Remittance Entry

	$rep->NewLine(1);
	$rep->fontSize += 2;
	$rep->Font('bold');
	$rep->SetTextColor(0, 0, 255);
	$rep->TextCol(0, 4, _('Collection Breakdown:'));
	$rep->SetTextColor(0, 0, 0);
	$rep->fontSize -= 2;
	$rep->Font();
	$rep->NewLine(1.5);

	$rep->fontSize += 1;
	$rep->TextCol(1, 3, _("Opening Balance"));
	$rep->AmountCol(5, 6, $prev_balance, $dec);
	$rep->NewLine(1.2);
	$rep->TextCol(1, 3, _("Collection Receipts"));
	$rep->AmountCol(5, 6, $sum_receipt, $dec);
	$rep->NewLine(1.2);
	$rep->TextCol(1, 3, _("Remittance"));
	$rep->AmountCol(5, 6, ($Tsum_remit + $sum_remit), $dec);
	$rep->NewLine(2);
	$rep->fontSize -= 1;
	$rep->Font('bold');
	$rep->fontSize += 2;
	$rep->TextCol(1, 3, _('Total Collection: '));
	$rep->AmountCol(5, 6, $prev_balance + $sum_receipt + ($Tsum_remit + $sum_remit), $dec);
	$rep->fontSize -= 2;
	$rep->NewLine(.5);
	$rep->Line($rep->row  - 1);

	$rep->NewLine(2);
	//Disbursement Entry

	$rep->NewLine(1);
	$rep->fontSize += 1;
	$rep->Font('bold');
	$rep->SetTextColor(255, 0, 0);
	$rep->TextCol(0, 4, _('Less : Disbursement Entries:'));
	$rep->SetTextColor(0, 0, 0);
	$rep->fontSize -= 1;
	$rep->Font();
	$rep->NewLine(1);
	
	while ($dis_trans = db_fetch($disburse_res)) {

		$void_entry = get_voided_entry($dis_trans['type'], $dis_trans['trans_no']); 

		if ($trans["remit_from"] == $cashier) {
			if ($dis_trans['bank_act'] == 1 || $dis_trans['bank_act'] == 2) {
				$entry_amt = $dis_trans['amt'];
			}
			else {
				$entry_amt = 0;
			}
	
			$rep->NewLine(1.2);
			$rep->TextCol(0, 1, sql2date($dis_trans['trans_date']));
			$rep->TextCol(1, 2, get_person_name($dis_trans['person_type_id'], $dis_trans['person_id']));
			$rep->TextCol(2, 3, $dis_trans['memo_']);
			$rep->TextCol(3, 4, str_replace(getCompDet('branch_code') . "-", "", $dis_trans['ref']));
			$rep->SetTextColor(0, 0, 255);
			$rep->TextCol(4, 5, $dis_trans['receipt_no']);
			$rep->SetTextColor(0, 0, 0);
			$rep->SetTextColor(255, 0, 0);
			$rep->AmountCol(5, 6, $entry_amt, $dec);
	
			if ($void_entry['void_status'] == 'Voided') {
				$rep->NewLine(1.2);
				$rep->TextCol(0, 1, sql2date($dis_trans['trans_date']));
				$rep->TextCol(1, 2, get_person_name($dis_trans['person_type_id'], $dis_trans['person_id']));
				$rep->TextCol(2, 3, $dis_trans['memo_']);
				$rep->TextCol(3, 4, str_replace(getCompDet('branch_code') . "-", "", $dis_trans['ref']));
				$rep->SetTextColor(0, 0, 255);
				$rep->TextCol(4, 5, $dis_trans['receipt_no']);
				$rep->SetTextColor(0, 0, 0);
				$rep->TextCol(5, 6, "(" . price_format($entry_amt) . ")", $dec);
				$void_dis += $entry_amt;
			}
			$rep->SetTextColor(0, 0, 0);
	
			$pre_subD += $entry_amt;
			$sum_dis = $pre_subD - $void_dis;
		}
		else {
			if ($dis_trans['bank_act'] == 1 || $dis_trans['bank_act'] == 2) {
				$entry_amt = $dis_trans['amt'];
			}
			else {
				$entry_amt = 0;
			}
	
			$rep->NewLine(1.2);
			$rep->TextCol(0, 1, sql2date($dis_trans['trans_date']));
			$rep->TextCol(1, 2, get_person_name($dis_trans['person_type_id'], $dis_trans['person_id']));
			$rep->TextCol(2, 3, $dis_trans['memo_']);
			$rep->TextCol(3, 4, str_replace(getCompDet('branch_code') . "-", "", $dis_trans['ref']));
			$rep->SetTextColor(0, 0, 255);
			$rep->TextCol(4, 5, $dis_trans['receipt_no']);
			$rep->SetTextColor(0, 0, 0);
			$rep->SetTextColor(255, 0, 0);
			$rep->AmountCol(5, 6, $entry_amt, $dec);
	
			if ($void_entry['void_status'] == 'Voided') {
				$rep->NewLine(1.2);
				$rep->TextCol(0, 1, sql2date($dis_trans['trans_date']));
				$rep->TextCol(1, 2, get_person_name($dis_trans['person_type_id'], $dis_trans['person_id']));
				$rep->TextCol(2, 3, $dis_trans['memo_']);
				$rep->TextCol(3, 4, str_replace(getCompDet('branch_code') . "-", "", $dis_trans['ref']));
				$rep->SetTextColor(0, 0, 255);
				$rep->TextCol(4, 5, $dis_trans['receipt_no']);
				$rep->SetTextColor(0, 0, 0);
				$rep->TextCol(5, 6, "(" . price_format($entry_amt) . ")", $dec);
				$void_dis += $entry_amt;
			}
			$rep->SetTextColor(0, 0, 0);
	
			$pre_subD += $entry_amt;
			$sum_dis = $pre_subD - $void_dis;
		}
	}

	$rep->NewLine(2);
	$rep->Font('bold');
	$rep->SetTextColor(255, 0, 0);
	$rep->TextCol(0, 1, _('Sub Total'));
	$rep->AmountCol(5, 6, $sum_dis, $dec);
	$rep->SetTextColor(0, 0, 0);
	$rep->Line($rep->row  - 4);
	$rep->NewLine(.5);

	//End Disburesement Entry

	$rep->fontSize += 1.5;
	$rep->NewLine(2.5);
	$rep->Font('bold');
	$rep->TextCol(0, 4, _('ENDING BALANCE: '));
	$rep->AmountCol(5, 6, $prev_balance + $sum_receipt + $Tsum_remit + $sum_remit - $sum_dis, $dec);
	$rep->NewLine(.5);
	$rep->fontSize -= 1.5;

	$rep->Line($rep->row  - 1);
	$rep->NewLine(2);

	$rep->fontSize += 1;
	$rep->TextCol(0, 4, _('ENDING BALANCE BREAKDOWN: '));
	$rep->NewLine();
	$rep->Font();

	$bank_sql = get_bank_accounts();
	$bank_ = 0;

	while ($bank_row = db_fetch($bank_sql)) {

		$bank_total = get_breakdown_balance($bank_row['id'], $from, $cashier);
		$rep->NewLine(1.2);
		$rep->TextCol(1, 3, _($bank_row['bank_account_name']));
		$rep->AmountCol(5, 6, $bank_total, $dec);
		$bank_ += $bank_total;
	}
	
	$rep->NewLine(2);
	$rep->Font('bold');
	$rep->TextCol(1, 3, _('Total Ending Balance: '));
	$rep->AmountCol(5, 6, $bank_, $dec);
	$rep->Font();
	$rep->fontSize -= 1;

    $rep->End();
}
