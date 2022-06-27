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
//----------------------------------------------------------------------------------

// add a bank transaction
// $amount is in $currency
// $date_ is display date (non-sql)


//modify by jr on 06-21-2021
//Modified by robert
function add_bank_trans($type, $trans_no, $bank_act, $ref, $date_, $amount, $person_type_id, $person_id, $cashier_id = 0,
	$pay_type = null, $check_date = '0000-00-00', $check_no = 0, $bank_branch = null, $masterfile = 0, $receipt_no = 0, 
	$prepared_by = null, $checked_by = null, $approved_by = null, $module_type = null, 
	$currency = "", $err_msg = "", $open_bal = 0, $company_id = '', $rate = 0)
{
	//Added by spyrax10
	$checksql = $checkval = '';
	$coy = user_company();

	$db_coy = Get_db_coy($company_id != '' ? get_company_value($company_id, 'branch_code') :
		get_company_value($coy, 'branch_code'));

    set_global_connection($db_coy);

	$sqlDate = date2sql($date_);
	// convert $amount to the bank's currency
	if ($currency != "")
	{
    	$bank_account_currency = get_bank_account_currency($bank_act);
    	if ($rate == 0)
    		$to_bank_currency = get_exchange_rate_from_to($currency, $bank_account_currency, $date_);
    	else
    		$to_bank_currency = 1 / $rate;
    	$amount_bank = ($amount / $to_bank_currency);
	}
	else
		$amount_bank = $amount;
	$amount_bank = round2($amount_bank, user_price_dec());	

	if($module_type != 'CLTN-BEGDP'){
		if($pay_type != 'Cash' && $type == ST_CUSTPAYMENT){
			$sql_checkDate = date2sql($check_date);

			$checksql = ", check_date, check_no, bank_branch ";
			$checkval = ", ".db_escape($sql_checkDate).", ".db_escape($check_no).", ".db_escape($bank_branch);
		}
		else if ($bank_act == BT_CHECK) { //Added by spyrax10
			$sql_checkDate = date2sql($check_date);

			$checksql = ", check_date, check_no, bank_branch ";
			$checkval = ", ".db_escape($sql_checkDate).", ".db_escape($check_no).", ".db_escape($bank_branch);
		}
	}

	//Modified by spyrax10 23 Feb 2022 (opening_balance column added)
	$sql = "INSERT INTO ".TB_PREF."bank_trans (type, trans_no, bank_act, ref, trans_date, amount, person_type_id, person_id,
					cashier_user_id, pay_type, prepared_by, checked_by, approved_by, masterfile, receipt_no, 
					opening_balance ".$checksql.") ";

	$sql .= "VALUES ($type, $trans_no, '$bank_act', ".db_escape($ref).", '$sqlDate', ".db_escape($amount_bank). ", ".db_escape($person_type_id).",
		".db_escape($person_id).", ".db_escape($cashier_id).", ".db_escape($pay_type).", ".db_escape($prepared_by). ",
		".db_escape($checked_by).", ".db_escape($approved_by).", ".db_escape($masterfile).", ".db_escape($receipt_no). ",
		".db_escape($open_bal). $checkval.")";
	//

	if ($err_msg == "")
		$err_msg = "The bank transaction could not be inserted";

	db_query($sql, $err_msg);
}

//----------------------------------------------------------------------------------------

//Added by spyrax10 : copied from interbranch_send_payment_add function
function bank_interbranch_entry(
	$target_branch, 
	$sug_mcode,
	$mcode, 
	$masterfile, 
	$trans_date, 
	$ref_no, 
	$amount, 
	$remarks, 
	$prepared_by, 
	$branch_code,
	$trans_no, 
	$tran_type, 
	$branch_to,
	$hocbc_id,
	$gl_line 
) {
	$db_coy = Get_db_coy($target_branch);
    set_global_connection($db_coy);

	$sql = "INSERT INTO ".TB_PREF."bank_interbranch_trans (
					branch_code_from, sug_mcode, mcode, masterfile, trans_date, ref_no, amount, remarks, prepared_by,
					transno_from_branch, trantype_from_branch, branch_code_to, hocbc_id, gl_line_id
			) VALUES (
			        ".db_escape($branch_code).", ".db_escape($sug_mcode).", ".db_escape($mcode).", ".db_escape($masterfile).", ".db_escape($trans_date)."
					,".db_escape($ref_no).", ".db_escape($amount)." ,".db_escape($remarks)." ,".db_escape($prepared_by)."
					,".db_escape($trans_no).", ".db_escape($tran_type).", ".db_escape($branch_to). ", ".db_escape($hocbc_id)."
					, ".db_escape($gl_line)."
			)";
	
	$ErrMsg = _('Could not send data to other branch because ');
	db_query($sql, $ErrMsg);

	set_global_connection();

}

function next_interbank_trans_no($trans_type, $target_branch) {

	$db_coy = Get_db_coy($target_branch);
    set_global_connection($db_coy);

	$sql = "SELECT MAX(A.trans_no) 
		FROM " . TB_PREF . "bank_trans A 
		WHERE A.type = " .db_escape($trans_type);
	
	$sql .= "GROUP BY A.type";

	$result = db_query($sql, "Can't get max bank trans_no! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0] + 1;
}

function max_gl_counter() {

	set_global_connection();
	$sql = "SELECT MAX(A.counter) 
		FROM " . TB_PREF . "gl_trans A ";
		
	$result = db_query($sql, "Can't get max gl counter! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}
//----------------------------------------------------------------------------------------


function exists_bank_trans($type, $type_no)
{
	$sql = "SELECT trans_no
		FROM ".TB_PREF."bank_trans
		WHERE type=".db_escape($type)
		." AND trans_no=".db_escape($type_no);
	$result = db_query($sql, "Cannot retreive a bank transaction");

    return (db_num_rows($result) > 0);
}

//----------------------------------------------------------------------------------------

function get_bank_trans($type, $trans_no=null, $person_type_id=null, $person_id=null)
{
	$sql = "SELECT bt.*, act.*,
		IFNULL(abs(dt.ov_amount), IFNULL(ABS(st.ov_amount), bt.amount)) settled_amount,
		IFNULL(abs(dt.ov_amount/bt.amount), IFNULL(ABS(st.ov_amount/bt.amount), 1)) settle_rate,
		IFNULL(debtor.curr_code, IFNULL(supplier.curr_code, act.bank_curr_code)) settle_curr

		FROM ".TB_PREF."bank_trans bt
				 LEFT JOIN ".TB_PREF."debtor_trans dt ON dt.type=bt.type AND dt.trans_no=bt.trans_no
				 LEFT JOIN ".TB_PREF."debtors_master debtor ON debtor.debtor_no = dt.debtor_no
				 LEFT JOIN ".TB_PREF."supp_trans st ON st.type=bt.type AND st.trans_no=bt.trans_no
				 LEFT JOIN ".TB_PREF."suppliers supplier ON supplier.supplier_id = st.supplier_id,
			 ".TB_PREF."bank_accounts act
		WHERE act.id=bt.bank_act ";
	if (isset($type))
		$sql .= " AND bt.type=".db_escape($type);
	if (isset($trans_no))
		$sql .= " AND bt.trans_no = ".db_escape($trans_no);
	if (isset($person_type_id))
		$sql .= " AND bt.person_type_id = ".db_escape($person_type_id);
	if (isset($person_id))
		$sql .= " AND bt.person_id = ".db_escape($person_id);
	$sql .= " ORDER BY trans_date, bt.id";

	return db_query($sql, "query for bank transaction");
}

//----------------------------------------------------------------------------------------

function get_bank_trans_for_bank_account($bank_account, $from, $to) 
{
	$from = date2sql($from);
	$to = date2sql($to);

	$sql = "SELECT t.* 
		FROM ".TB_PREF."bank_trans t 
			LEFT JOIN ".TB_PREF."voided v ON t.type=v.type AND t.trans_no=v.id
		WHERE t.bank_act = ".db_escape($bank_account) . "
			AND ISNULL(v.date_)
			AND trans_date >= '$from'
			AND trans_date <= '$to'
			AND amount != 0
		ORDER BY trans_date, t.id";

	return db_query($sql,"The transactions for '" . $bank_account . "' could not be retrieved");
}

//----------------------------------------------------------------------------------------

function get_balance_before_for_bank_account($bank_account, $from) 
{
	$from = date2sql($from);
	$sql = "SELECT SUM(amount) FROM " . TB_PREF . "bank_trans
		WHERE bank_act = " .db_escape($bank_account) . 
		" AND trans_date < '$from' ";

	$before_qty = db_query($sql, "The starting balance on hand could not be calculated");
	$bfw_row = db_fetch_row($before_qty);
	return $bfw_row[0];
}
//----------------------------------------------------------------------------------------

function get_gl_trans_value($account, $type, $trans_no)
{
	$sql = "SELECT SUM(amount)
		FROM " . TB_PREF . "gl_trans
		WHERE account=".db_escape($account)
			." AND type=".db_escape($type)
			." AND type_no=".db_escape($trans_no);

	$result = db_query($sql, "query for gl trans value");

	$row = db_fetch_row($result);
	return $row[0];
}

//----------------------------------------------------------------------------------------

function void_bank_trans($type, $type_no, $nested=false)
{

	if (!$nested)
		begin_transaction();

	$sql = "UPDATE ".TB_PREF."bank_trans 
			SET amount=0
			WHERE type=".db_escape($type)." AND trans_no=".db_escape($type_no);

	db_query($sql, "could not void bank transactions for type=$type and trans_no=$type_no");

	void_gl_trans($type, $type_no, true);

	// in case it's a customer trans - probably better to check first
	void_cust_allocations($type, $type_no);
	void_customer_trans($type, $type_no);

	// in case it's a supplier trans - probably better to check first
	void_supp_allocations($type, $type_no);
	void_supp_trans($type, $type_no);

	void_trans_tax_details($type, $type_no);

	if (!$nested)
		commit_transaction();
}

/**
*	Check account history to find transaction which would exceed authorized overdraft for given account.
*	Returns null or transaction in conflict. Running balance is checked on daily basis only, to enable ID change after edition.
*	$delta_amount - tested change in bank balance at $date.
**/
function check_bank_account_history($delta_amount, $bank_account, $date=null, $user=null, $balance_offset = 0)
{
	if ($delta_amount >= 0 && isset($date))
		 return null;	// amount increase is always safe

	$balance = $date ? get_bank_account_limit($bank_account, $date, $user) : 0;

	if (!isset($balance) && isset($date))
		return null;	// unlimited account

	$balance += $balance_offset;
	if (floatcmp($balance, -$delta_amount) < 0)
		return array('amount' => $balance + $delta_amount, 'trans_date'=> date2sql($date));

	$balance += $delta_amount;

	$sql = "SELECT sum(amount) as amount, trans_date, trans_no, type
			FROM ".TB_PREF."bank_trans
			WHERE bank_act=".db_escape($bank_account);
	if ($date)
	{
		$date = date2sql($date);
		$sql .= " AND trans_date > '$date'";
	}
	$sql .= " GROUP BY trans_date ORDER BY trans_date ASC";

	$history = db_query($sql, "cannot retrieve cash account history");

	while ($trans = db_fetch($history)) {
		$balance += $trans['amount'];
		if (round2($balance, user_price_dec()) < 0)
		{
			$trans['amount'] = $balance;
			return $trans;
		}
	}

	return null;
}

/**
*	Check bank transfer, deposit or customer deposit before voiding.
**/
function check_void_bank_trans($type, $type_no)
{
	$moves = get_bank_trans($type, $type_no);
	while ($trans = db_fetch($moves)) {
		if ($trans['amount'] > 0) { // skip transfer input part
			return check_bank_account_history(-$trans['amount'], $trans['bank_act'], sql2date($trans['trans_date'])) == null;
		}
	}
	return true;
}

//added by jr
function get_bank_trans_branch()
{
	$sql = "SELECT DISTINCT bank_branch FROM ".TB_PREF."bank_trans A WHERE A.bank_branch <> '' ";
	return db_query($sql, "could not get bank trans info");
}

//Added by spyrax10 17 Feb 2022
function get_bank_lists($bank_id = null) {
	
	$sql = "SELECT id, bank_account_name, bank_curr_code, inactive
		FROM ".TB_PREF."bank_accounts WHERE IFNULL(id, '') <> ''";

	if ($bank_id != null) {
		$sql .= " AND id = " .db_escape($bank_id);
	}
	
	$result = db_query($sql, "Cant get Bank Account List!"); 
	$row = db_fetch_row($result);

	if ($bank_id != null) {
		return $row[1];
	}
	else {
		return $result;
	}
}

function auto_disburse_no() {

	$trans_type = ST_BANKPAYMENT;

	$sql = "SELECT MAX(A.trans_no), receipt_no 
		FROM " . TB_PREF . "bank_trans A 
		WHERE A.type = " . db_escape($trans_type);

	$result = db_query($sql, "Cant get gl reference! (spyrax10)");
    set_global_connection();
	$row = db_fetch_row($result);
	$trans_no = $row[0] + 1;
	
	$receipt_no = sprintf("%06s", $trans_no);

	return $receipt_no;
}

function _systype_name($type)
{
	global $systypes_array;
	
	return $systypes_array[$type];
}

function gl_interbranch_status($trans_no, $trans_type) {

	set_global_connection();
	$sql = "SELECT A.status FROM " . TB_PREF . "bank_interbranch_trans A 
		WHERE A.transno_from_branch =" . db_escape($trans_no) . " 
			AND A.trantype_from_branch =" .db_escape($trans_type);
	
	$sql .= " ORDER BY id DESC";

	$result = db_query($sql, "Cant get gl reference! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_gl_reference($trans_no) {

	set_global_connection();
    $sql = "SELECT A.ref_no FROM " . TB_PREF . "bank_interbranch_trans A 
		WHERE A.transno_from_branch =" . db_escape($trans_no);

	$result = db_query($sql, "Cant get gl reference! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_interbranch_total($trans_no, $trans_type) {

	set_global_connection();
    $sql = "SELECT SUM(amount) FROM " . TB_PREF . "bank_interbranch_trans 
        WHERE transno_from_branch = ".db_escape($trans_no) . " 
            AND trantype_from_branch = " .db_escape($trans_type);
	
	$sql .= " AND amount > 0";	

	if ($trans_type != ST_JOURNAL) {
		$sql .=  " GROUP BY transno_from_branch, trantype_from_branch";
	}
    
    $result = db_query($sql, "Cant get gl Total! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_gl_line($line_id) {
	
    set_global_connection();

    $sql = "SELECT A.*
            FROM " . TB_PREF . "bank_interbranch_trans A 
            WHERE A.id =" .db_escape($line_id);

    $result = db_query($sql, "No Items return for Banking Interbranch Details! (spyrax10)");
    return db_fetch($result);
}

function banking_transaction_details($reference = '', $branch) {
	
	$db_coy = Get_db_coy($branch);
    set_global_connection($db_coy);

	$sql = "SELECT A.*
        FROM " . TB_PREF . "bank_trans A 
        WHERE A.ref = " .db_escape($reference);

	$result = db_query($sql, "No Items return for Banking Trans! (spyrax10)");
	return db_fetch($result);
}

function get_gl_line_memo($counter, $branch) {

	$db_coy = Get_db_coy($branch);
    set_global_connection($db_coy);

	$sql = "SELECT A.memo_
        FROM " . TB_PREF . "gl_trans A 
        WHERE A.counter =" .db_escape($counter);
	
	$sql .= " GROUP BY A.counter";

	$result = db_query($sql, "Can't get gl line memo! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}

function has_COH_entry($trans_no, $trans_type) {

	$result = get_gl_items($trans_no, $trans_type, false, false);

	while ($row = db_fetch($result)) {
		if ($trans_type == ST_BANKDEPOSIT || $trans_type == ST_JOURNAL) {
			if ($row['sug_mcode'] == 1050) {
				return true;
			}
		}
		else {
			return false;
		}
	}
	return false;
}

function is_RE_opening($trans_no) {

	set_global_connection();

	$sql = "SELECT A.opening_balance FROM " . TB_PREF . "bank_trans A
		WHERE A.type = 2 AND A.trans_no = " .db_escape($trans_no);

	$result = db_query($sql, "No details return from bank_trans! (spyrax10)");
	$row = db_fetch_row($result);

	if ($row[0] == 1) {
		return true;
	}
	else {
		return false;
	}
}

function has_interbranch_entry($trans_no, $trans_type) {
	
	set_global_connection();

	$sql = "SELECT mcode, interbranch 
		FROM gl_trans 
		WHERE type_no = " .db_escape($trans_no) . " 
			AND type = " .db_escape($trans_type);
	
	$result = db_query($sql, "No details return from gl_trans! (spyrax10)");

	while ($row = db_fetch($result)) {
		if ($row['interbranch'] == 1) {
			return $row['mcode'];
		}
		else {
			return false;
		}

		return false;
	}
}

function bank_interB_stat($company_id, $reference = '', $type = '') {

	set_global_connection($company_id);

	$sql = "SELECT status FROM " . TB_PREF . " bank_interbranch_trans 
		WHERE IFNULL(id, '') <> ''";

	if ($reference != '') {
		$sql .= " AND ref_no = " .db_escape($reference);
	}

	if ($type != '') {
		$sql .= " AND trantype_from_branch = " .db_escape($type);
	}

	$result = db_query($sql, "bank_interB_stat()");
	$row = db_fetch_row($result);
	set_global_connection();

	return $row[0];
}

function void_interB($company_id, $reference = '', $type = '', $void = 0) {
	
	set_global_connection($company_id);

	$sql = "UPDATE ".TB_PREF."bank_interbranch_trans SET voided = " .db_escape($void);

	$sql .= " WHERE trantype_from_branch = " .db_escape($type) . " AND ref_no = " .db_escape($reference);
	
	db_query($sql, "void_interB()");
	set_global_connection();
	
	return $reference;
}

function max_interbranch_no($trans_type, $branch = '') {

	set_global_connection($branch);

	$sql = "SELECT MAX(A.transno_from_branch) 
		FROM " . TB_PREF . "bank_interbranch_trans A 
		WHERE A.trantype_from_branch = ".db_escape($trans_type);

	$result = db_query($sql, "Can't get max Banking Trans_no! (spyrax10)");
	$row = db_fetch_row($result);
	
	return $row[0] != null ? $row[0] : 0;
}

function max_journal_no() {

	set_global_connection();

	$sql = "SELECT MAX(A.trans_no) 
		FROM " . TB_PREF . "journal A ";

	$result = db_query($sql, "Can't get max journal Trans_no! (spyrax10)");
	$row = db_fetch_row($result);
	
	return $row[0] != null ? $row[0] : 0;
}

function get_max_bank_trans($trans_type) {

	set_global_connection();
	
	$sql = "SELECT MAX(A.trans_no) 
		FROM " . TB_PREF . "bank_trans A 
		WHERE A.type = ".db_escape($trans_type);

	$result = db_query($sql, "Can't get max Banking Trans_no! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_journal_transactions($reference = '', $doc_ref = '', $from_date, $to_date, $interbranch = '') {

	set_global_connection();

	$sql = "SELECT JOR.* 
		FROM " . TB_PREF . "journal JOR ";
	
	if ($interbranch != '') {
		$sql .= " LEFT JOIN (
			SELECT GL.* FROM " . TB_PREF . "gl_trans GL GROUP BY GL.type, GL.type_no
		) GL ON JOR.trans_no = GL.type_no AND JOR.type = GL.type";
	} 

	$sql .= " WHERE JOR.type = " . ST_JOURNAL . " ";

	if ($reference != '') {
		$sql .= " AND JOR.reference = " .db_escape($reference);
	} 

	if ($doc_ref != '') {
		$sql .= " AND JOR.source_ref = " .db_escape($doc_ref);
	}

	if ($interbranch != '') {
		$sql .= " AND GL.interbranch = " .db_escape($interbranch);
	}

	$sql .= " AND JOR.tran_date >= '" . date2sql($from_date) . "' 
		AND JOR.tran_date <= '" . date2sql($to_date) . "'";

	if ($interbranch != '') {
		$sql .= " GROUP BY GL.type, GL.type_no";
	}

	$sql .= " ORDER BY JOR.tran_date DESC, JOR.trans_no DESC";

	return $sql;

}

function get_banking_transactions($type = null, $reference = '', $doc_ref = '', $from_date = null, $to_date = null, $cashier_id = '', 
$interbranch = '', $open_bal = '', $trans_no = 0) {
	
	set_global_connection();

	$sql = "SELECT BT.* 
		FROM " . TB_PREF . "bank_trans BT ";
	
	if ($interbranch != '') {
		$sql .= " LEFT JOIN (
			SELECT GL.* FROM " . TB_PREF . "gl_trans GL GROUP BY GL.type, GL.type_no
		) GL ON BT.trans_no = GL.type_no AND BT.type = GL.type";
	} 

	$sql .= " WHERE BT.type <> 0 AND BT.type = " .db_escape($type);
	
	if ($reference != '') {
		$sql .= " AND BT.ref = " .db_escape($reference);
	} 

	if ($doc_ref != '') {
		$sql .= " AND BT.receipt_no = " .db_escape($doc_ref);
	}

	if ($trans_no != 0) {
		$sql .= " AND BT.trans_no = " .db_escape($trans_no);
	}

	if ($cashier_id != '') {
		$sql .= " AND BT.cashier_user_id = " .db_escape($cashier_id);
	}

	if ($interbranch != '') {
		$sql .= " AND GL.interbranch = " .db_escape($interbranch);
	}

	if ($open_bal != '') {
		$sql .= " AND BT.opening_balance = " .db_escape($open_bal);
	}

	if ($from_date != null || $to_date != null) {
		$sql .= " AND BT.trans_date >= '" . date2sql($from_date) . "' 
			AND BT.trans_date <= '" . date2sql($to_date) . "'";
	}

	if ($interbranch != '') {
		$sql .= " GROUP BY GL.type, GL.type_no";
	}
	else {
		$sql .= " GROUP BY BT.ref";
	}

	$sql .= " ORDER BY BT.trans_date DESC, BT.trans_no DESC";

	return $sql;
}

function get_gl_items($trans_no = null, $trans_type = null, $group = false, $head = false) {

	set_global_connection();
	
    $sql = "SELECT A.*
        FROM " . TB_PREF . "bank_interbranch_trans A 
        WHERE A.trantype_from_branch = " .db_escape($trans_type) . " 
            AND A.transno_from_branch = " .db_escape($trans_no);
	
	if ($group) {
		$sql .= " GROUP BY A.transno_from_branch, A.trantype_from_branch";
	}

	$result = db_query($sql, "No Items return for Banking Interbranch Summary! (spyrax10)");

	if ($group && $head == false) {
		return db_fetch($result);
	}
	else {
		return $result;
	}
}

function display_gl_draft($trans_no = null, $trans_type, $title = '') {

    $resHead = get_gl_items($trans_no, $trans_type, true, true);
	
    start_table(TABLESTYLE, "width='78%'");

	display_heading($title);
	br();

    $th = array(
        _('Transaction Details'),
        _('From Branch'),
        _('Reference'),
        _('Transaction Date'),
		_('Pay Type')
    );

    table_header($th);	
    while ($row = db_fetch($resHead)) {
        
		$interbranch = banking_transaction_details($row['ref_no'], $row['branch_code_from']);

        start_row();
        label_cell(_systype_name($trans_type) . " #" . $trans_no);
        label_cell(get_db_location_name($row['branch_code_from']), "align = 'center'");
        label_cell($row['ref_no'], "align = 'center'");
        label_cell(phil_short_date($row['trans_date']), "align = 'center'");
		label_cell($interbranch['pay_type'], "align = 'center'");
        end_row();

        start_row();
        label_cells(_('Prepared By: '), $row['prepared_by'], "class='tableheader2'", "colspan=4");
        end_row();

        start_row();
        label_cell($row['remarks'], "colspan=5");
        end_row();
    }
    end_table(1);

    display_gl_details($trans_no, $trans_type, 0);
}

function display_gl_details($trans_no, $trans_type, $post = 0) {

    $resDetails = get_gl_items($trans_no, $trans_type, false, false);
    $width = $post == 0 ? 95 : 95;

	div_start('gl_items');
    start_table(TABLESTYLE, "width = '$width%'");
    
    if ($post == 1) {
        display_heading(_('Transaction Line Details: '));
    }
    $th = array (
		_('ID'),
        $post == 0 ? _('Suggested Entry') : _('Account Entry'),
        _('MCODE'),
        _('Masterfile'),
		_('HOC/BC'),
        _('Debit'), 
        _('Credit'), 
		_('Line Memo'),
		_('')
    );
            
    $k = $total = $debit = $credit = 0;

    table_header($th);	
    while ($row = db_fetch($resDetails)) {

        $amount = abs($row['amount']);
        if ($row['amount'] < 0) {
            $credit += abs($amount);
        }
        else {
            $debit += abs($amount);
        }

		$account_name = $row['sug_mcode'] != null ? get_gl_account_name($row['sug_mcode']) : '';
        $total += $amount;
		//$suggested_entry = $row['sug_mcode'] == 1050 ? 'HOC' : 'BC';

        alt_table_row_color($k);
		label_cell($row['id']);

		label_cell($row['sug_mcode'] . " " . $account_name, '', 'sug_mcode');
		// gl_all_accounts_list_cells(null, 'account_id', '', false, false, _("Default Account"), true);

        label_cell($row['mcode']);
        label_cell($row['masterfile']);
		label_cell(get_hocbc_type($row['hocbc_id'])["hocbc"], "align = 'center'");
        
        amount_cell($row['amount'] > 0 ? abs($row['amount']) : 0, false, "style='color: green'");
        amount_cell($row['amount'] < 0 ? abs($row['amount']) : 0, false, "style='color: red'");

		label_cell(get_gl_line_memo($row['gl_line_id'], $row['branch_code_from']), "align = 'center'");
		
		if ($post == 1) {
			edit_button_cell("Edit".$row['id'], _("Edit"), _('Edit document line'));
		}	
    }
    start_row();
		label_cell(_("Document Total: "), "align=right colspan=5 style='font-weight:bold'");
		amount_cell($debit, true, "style='color: green'");
		amount_cell($credit, true, "style='color: red'");
		label_cell('', "colspan=6");
	end_row();

    end_table(2);
	div_end();
}

function get_dcpr_users() {
	
	set_global_connection();

	$sql = "SELECT BT.cashier_user_id, USR.real_name 
		FROM " . TB_PREF . "bank_trans BT 
			LEFT JOIN " . TB_PREF . "users USR ON BT.cashier_user_id = USR.id

		GROUP BY BT.cashier_user_id
		ORDER BY USR.real_name";

	return db_query($sql, _("Cant get DCPR users!"));
}

function collection_type_list() {

	set_global_connection();

	$sql = "SELECT collect_id, collection, inactive 
		FROM ".TB_PREF."collection_types 
		ORDER BY collect_id";

	return db_query($sql, _("Cant get list of collection types!"));
}

function hocbc_type_list() {
	
	set_global_connection();

	$sql = "SELECT id, hocbc 
		FROM ".TB_PREF."hocbc_types WHERE inactive = 0";

	return db_query($sql, _("Cant get list of HOC/BC types!"));
}

//