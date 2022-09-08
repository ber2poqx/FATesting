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
function add_journal($type, $trans_no, $amount, $tran_date, $currency, $reference, $source_ref='', $rate=1,
	 $event_date = '', $doc_date = '', $source_ref2 = '')
{
	$sql = "INSERT INTO ".TB_PREF."journal(
		`type`, `trans_no`, `amount`, `currency`, `rate`, `reference`, 
		`source_ref`, `tran_date`,`event_date`, `doc_date`, `source_ref2`
	)
	  VALUES("
	  .db_escape($type).","
	  .db_escape($trans_no).","
	  .db_escape($amount).","
	  .db_escape($currency).","
	  .db_escape($rate).","
	  .db_escape($reference).","
	  .db_escape($source_ref).","
	  ."'".date2sql($tran_date)."','"
	  . ($event_date === '' ? '0000-00-00' : date2sql($event_date))."','"
	  . ($doc_date === '' ? '0000-00-00' : date2sql($doc_date))."','" 
	  . ($source_ref2) . "')";

	return db_query($sql, 'cannot add journal entry');
}

function update_journal($type, $trans_no, $amount, $tran_date, $currency, $reference, $source_ref='', $rate=1,
	 $event_date='', $doc_date='')
{
	$sql = "UPDATE ".TB_PREF."journal SET "
	  ."`amount`=".db_escape($amount).","
	  ."`currency`=".db_escape($currency).","
	  ."`rate`=".db_escape($rate).","
	  ."`reference`=".db_escape($reference).","
	  ."`source_ref`=".db_escape($source_ref).","
	  ."`tran_date`='".date2sql($tran_date)."',"
	  ."`event_date`='".($event_date === '' ? '0000-00-00' : date2sql($event_date))."',"
	  ."`doc_date`='".($doc_date === '' ? '0000-00-00' : date2sql($doc_date))."'
	   WHERE `type`=".db_escape($type)." AND " ."`trans_no`=".db_escape($trans_no);

	return db_query($sql, 'cannot update journal entry');
}

function delete_journal($type, $trans_no)
{
	$sql = "DELETE FROM ".TB_PREF."journal 
	   WHERE `type`=".db_escape($type)." AND " ."`trans_no`=".db_escape($trans_no);

	return db_query($sql, 'cannot delete journal entry');
}

function get_journal($type, $trans_no)
{
	$sql = "SELECT * FROM ".TB_PREF."journal 
	   WHERE `type`=".db_escape($type)." AND " ."`trans_no`=".db_escape($trans_no);

	$myrow= db_query($sql, 'cannot retrieve journal entry');
	return db_fetch($myrow);
}

//
// Write headers in debtor_trans for allocation purposes.
// Amount in customer currency
//
function write_cust_journal($trans_type, $trans_no, $branch_id, $date, $ref, $amount, $rate)
{
	// we have to select any branch_id for given AR_act - in fact w
	$sql = "INSERT INTO ".TB_PREF."debtor_trans (trans_no, type, debtor_no, branch_code, tran_date, reference, ov_amount, rate)
		SELECT "
		.db_escape($trans_no).", ".db_escape($trans_type).", debtor_no,"
		.db_escape($branch_id).",'".date2sql($date)."', ".db_escape($ref).","
		.db_escape($amount).", ".db_escape($rate)."
		FROM ".TB_PREF."cust_branch WHERE branch_code=".db_escape($branch_id);

	return db_query($sql, 'cannot write cust_journal');
}

//
// Write headers in supp_trans for allocation purposes.
// Amount in supplier currency
//
function write_supp_journal($trans_type, $trans_no, $supp_id, $date, $ref, $amount, $rate, $src_ref)
{
	$sql = "INSERT INTO ".TB_PREF."supp_trans (trans_no, type, supplier_id, tran_date, reference, ov_amount, rate, supp_reference)
		VALUES (".db_escape($trans_no).", ".db_escape($trans_type).", ".db_escape($supp_id).", '".date2sql($date)."',"
		.db_escape($ref).", ".db_escape($amount).",".db_escape($rate).",".db_escape($src_ref).")";

	return db_query($sql, 'cannot write supp_journal');
}

//--------------------------------------------------------------------------------
// Write/update journal entries.
//
function write_journal_entries(&$cart, $interbranch = false)
{
	global $Refs;

	$rebate_tot = $penalty_tot = 0;

	$date_ = $cart->tran_date;
	$ref   = $cart->reference;
	$memo_ = $cart->memo_;
	$trans_type = $cart->trans_type;
	$new = $cart->order_id == 0;

	begin_transaction();

	if (!$new && $interbranch == false)
	{
		$msg = void_transaction($trans_type, $cart->order_id, Today(), _("Document reentered."));
		if ($msg)
		{
			display_error($msg);
			return false;
		}
	}

	$trans_id = $cart->order_id = $interbranch ? max_journal_no() + 1 : 
		get_next_trans_no($trans_type);
		
	$total = $cart->gl_items_total_debit();

	$cart->write_gl();

	add_journal($trans_type, $trans_id, $total,	$date_ , $cart->currency, $ref,
		$cart->source_ref, $cart->rate, $cart->event_date, $cart->doc_date, $cart->source_ref2
	);

	if ($cart->ar_alloc == 1) {
		insert_customer_alloc(
			$date_,
			$trans_id, 
			$cart->ar_trans_no,
			$cart->ar_trans_type,
			$cart->ar_debtor_no,
			$ref,
			$cart->ar_type == 2 ? -$total : $total
		);
	}

	$Refs->save($trans_type, $trans_id, $ref);
	if ($new) {
		add_comments($trans_type, $trans_id, $date_, $memo_);
	} else {
		update_comments($trans_type, $trans_id, null, $memo_);
	}

	add_audit_trail($trans_type, $trans_id, $date_);

	commit_transaction();

	return $trans_id;
}

//----------------------------------------------------------------------------------------

function void_journal_trans($type, $type_no, $use_transaction=true)
{
	if ($use_transaction)
		begin_transaction();

	$sql = "UPDATE ".TB_PREF."journal SET amount=0
		WHERE type=".db_escape($type)." AND trans_no=".db_escape($type_no);

	db_query($sql, "could not void journal transaction for type=$type and trans_no=$type_no");

	void_bank_trans($type, $type_no, true);

	if ($use_transaction) 
		commit_transaction();
}

//Added by spyrax10 11 Jun 2022

function void_journal($void_id, $ar_trans_no = 0, $ar_trans_type = 0, $ar_debtor_no = 0, $ref_to = '') {
	
	global $Refs;

	$void_row = get_voided_entry(0, null, null, false, 'ALL', null, null, $void_id);
	$gl_sql = get_gl_trans($void_row['type'], $void_row['id'], "DESC");
	$debtor_row = get_SI_by_reference($void_row['reference_from']);

	if ($ar_trans_no > 0) {
		$si_row = get_SI_by_reference('', $ar_trans_no, $ar_trans_type, $ar_debtor_no);	
	}

	while ($row = db_fetch($gl_sql)) {

		add_gl_trans(
			$row['type'],
			$row['type_no'],
			$void_row['date_'],
			$row['account'],
			0, 0,
			$void_row['memo_'],
			-$row['amount'],
			'PHP',
			$row['person_type_id'],
			$row['person_id'],
			"",
			0,
			$row['mcode'],
			$row['master_file'],
			$row['hocbc_id'],
			$row['loan_trans_no'],
			$row['interbranch'],
			false,
			1
		);
	}

	update_void_status(
		$void_row['type'], 
		$void_row['id'], 
		$void_row['date_'], "Voided", 
		$void_row['memo_'], 
		$_SESSION["wa_current_user"]->user,
		$ref_to 
	);

	if (has_interbranch_entry($void_row['id'], $void_row['type'])) {
	
		$comp_id = get_comp_id(has_interbranch_entry($void_row['id'], $void_row['type']));
		$interb_status = bank_interB_stat($comp_id, $void_row['reference_from'], $void_row['type']);

		if ($interb_status == "Draft") {
			void_interB($comp_id, $void_row['reference_from'], $void_row['type'], 1);
		}
	}

	if ($debtor_row["trans_no"] != null && $ar_trans_no > 0)  {
		if ($debtor_row['alloc'] > 0 && $debtor_row['ov_amount'] == 0) {
			void_payments_thru_JE($void_row['reference_from'], $void_row['id'], true);
			update_debtor_trans_allocation($si_row["type"], $si_row["trans_no"], $si_row["debtor_no"]);
		}
		else if ($debtor_row['ov_amount'] > 0 && $debtor_row['alloc'] == 0) {
			void_payments_thru_JE($void_row['reference_from'], $void_row['id']);
			update_AR_amount($si_row["trans_no"], $si_row["type"], $si_row["debtor_no"], -$debtor_row['ov_amount']);
		}
	}

	add_audit_trail($void_row['type'], $void_row['id'], $void_row['date_'], _("Voided.")."\n". $void_row['memo_']);
	$Refs->restore_last($void_row['type'], $void_row['id']);
}

function gl_account_exists($type, $trans_no, $account) {
	
	set_global_connection();

	$sql = "SELECT account FROM " . TB_PREF . "gl_trans
	WHERE type = " .db_escape($type) . " AND type_no = " .db_escape($trans_no) 
		. " AND account = " .db_escape($account);

	$result = db_query($sql, "gl_account_exists()");

	if (db_num_rows($result) > 0) {
		return true;
	}
	else {
		return false;
	}
}

function JE_exists($trans_no, $reference = '') {

	set_global_connection();

	$sql = "SELECT JE.* FROM " . TB_PREF . "journal JE
		INNER JOIN " . TB_PREF . "gl_trans GL ON JE.trans_no = GL.type_no 
			AND GL.type = " .db_escape(ST_JOURNAL);
	
	$sql .= " WHERE JE.trans_no = " .db_escape($trans_no);

	if ($reference != '') {
		$sql .= " OR JE.reference = " .db_escape($reference);
	}

	$result = db_query($sql, "JE_exists()");

	if (db_num_rows($result) > 0) {
		return true;
	}
	else {
		return false;
	}
}

function get_JE_transactions($trans_no, $header = false) {
    
    set_global_connection();

    $sql = $header ? "SELECT JE.*" : 
        "SELECT JE.*, GL.*, GL.amount AS line_amount";
    
    $sql .= " FROM " . TB_PREF . "journal JE";

    if (!$header) {
        $sql .= " INNER JOIN " . TB_PREF . "gl_trans GL ON JE.trans_no = GL.type_no 
            AND GL.type = " .db_escape(ST_JOURNAL);
    }

    $sql .= " WHERE JE.trans_no = " .db_escape($trans_no);

    $result = db_query($sql, "get_JE_header()");

    if ($header) {
        return db_fetch($result);
    }
    else {
        return $result;
    }
}

