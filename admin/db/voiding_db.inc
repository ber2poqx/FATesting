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
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");
include_once($path_to_root . "/manufacturing/includes/manufacturing_db.inc");

//Modfied by spyrax10 22 Jun 2022
function void_transaction($type, $type_no, $date_, $memo_) {

	global $Refs;
	$void_entry = get_voided_entry($type, $type_no, null, false, 'Voided');

	if ($void_entry != null) {
		return _('This transaction was already voided before.');
	}

	switch ($type) {
		case ST_JOURNAL : // it's a journal entry
			if (!exists_gl_trans($type, $type_no)) {
				return _('Selected transaction does not exists.');
			}
			if ($wo = check_wo_costing($type, $type_no)) {
				return sprintf(_('This transaction cannot be voided because it is part of Work Order %s costs.'), $wo);
			}
			void_journal_trans($type, $type_no);
			break;

		case ST_BANKDEPOSIT : // it's a deposit
		case ST_BANKTRANSFER : // it's a transfer
			if (!check_void_bank_trans($type, $type_no)) {
				return _('This transaction cannot be voided because the operation would decrease account balance below allowed limit in some point of account history.');
			}
		case ST_BANKPAYMENT : // it's a payment
			if (!exists_bank_trans($type, $type_no)) {
				return _('Selected transaction does not exists.');
			}
			void_bank_trans($type, $type_no);
			break;

		case ST_CUSTPAYMENT : // it's a customer payment
			if (!check_void_bank_trans($type, $type_no)) {
				return _('This transaction cannot be voided because the operation would decrease account balance below allowed limit in some point of account history.');
			}
		case ST_SALESINVOICE : // it's a customer invoice
 			if (is_cust_invoice_credited($type_no)) {
				return _('This invoice cannot be voided because it was already credited.');
			}
		case ST_CUSTCREDIT : // it's a customer credit note
		case ST_CUSTDELIVERY : // it's a customer dispatch
			if (!exists_customer_trans($type, $type_no)) {
				return _('Selected transaction does not exists.');
			}
			if ($type == ST_CUSTDELIVERY) { // added 04 Oct 2008 by Joe Hunt. If delivery note has a not voided invoice, then NO.
				$childs = get_sales_child_lines($type, $type_no, false); // 2011-03-17 This had been changed. Joe
				if ($childs && db_num_rows($childs)) {
					return _('This delivery cannot be voided because it was already invoiced.');
				}
			}

			post_void_customer_trans($type, $type_no);
			break;

		case ST_LOCTRANSFER : // it's a stock transfer
			if (get_stock_transfer_items($type_no) == null) {
				return _('Selected transaction does not exists.');
			}

			void_stock_transfer($type_no);
			break;
		case ST_MERCHANDISETRANSFER:
		    $mt_rec = get_stock_merchandise_transfer($type_no);
		    if ($mt_rec == null) {
				return _('Selected transaction does not exists.');
			}
		    if (!is_null(check_mt_status($mt_rec["reference"])) || !empty(check_mt_status($mt_rec["reference"]))) {
				return _('This MT cannot be voided because it was already received.');
			}

		    void_stock_transfer($type_no);
		    break;
		case ST_MERCHANDISETRANSFERREPO:
		    $mt_rec = get_stock_merchandise_transfer_repo($type_no);
		    if ($mt_rec == null) {
				return _('Selected transaction does not exists.');
			}
		    if (!is_null(check_mt_status($mt_rec["reference"])) || !empty(check_mt_status($mt_rec["reference"]))) {
				return _('This MT Repo cannot be voided because it was already received.');
			}
		            
		    void_stock_transfer_repo($type_no);
		    break;
		case ST_RRBRANCH:
		    if (get_stock_rrbranch_transfer($type_no) == null) {
				return _('Selected transaction does not exists.');
			}

		    void_rrbranch_transfer($type_no);
		    break;
		case ST_INVADJUST : // it's a stock adjustment
			if (get_stock_adjustment_items($type_no) == null) {
				return _('Selected transaction does not exists.');
			}

			void_stock_adjustment($type_no);
			break;

		case ST_PURCHORDER : // it's a PO
			return _('This transaction type cannot be voided.');
			break;

		case ST_SUPPRECEIVE : // it's a GRN
			if (exists_grn_on_invoices($type_no)) {
				return _('This GRN cannot be voided because it was already invoiced.');
			}
			if (!post_void_supp_trans($type, $type_no)) {
				return _('Error encountered when voiding transaction.');
			}
			break;

		case ST_SUPPINVOICE : // it's a suppler invoice
		case ST_SUPPCREDIT : // it's a supplier credit note
		case ST_SUPPAYMENT : // it's a supplier payment
			if (!exists_supp_trans($type, $type_no)) {
				return _('Selected transaction does not exists.');
			}
			if (!post_void_supp_trans($type, $type_no)) {
				return _('Error encountered when voiding transaction.');
			}
			break;

		case ST_WORKORDER : // it's a work order
			if (!get_work_order($type_no, true)) {
				return _('Selected transaction does not exists.');
			}

			void_work_order($type_no);
			break;

		case ST_MANUISSUE : // it's a work order issue
			if (!exists_work_order_issue($type_no)) {
				return _('Selected transaction does not exists.');
			}
			if ($wo = check_void_wo_issue($type_no)) {
				return sprintf(_('Selected document cannot be voided because related Work Order %s is already closed.'), $wo);
			}

			void_work_order_issue($type_no);
			break;

		case ST_MANURECEIVE : // it's a work order production
			if (!exists_work_order_produce($type_no)) {
				return _('Selected transaction does not exists.');
			}
			if ($wo = check_void_wo_production($type_no)) {
				return sprintf(_('Selected document cannot be voided because related Work Order %s is already closed.'), $wo);
			}

			void_work_order_produce($type_no);
			break;

		case ST_SALESORDER: // it's a sales order
		case ST_SALESQUOTE: // it's a sales quotation
			return _('This transaction type cannot be voided.');

		case ST_COSTUPDATE : // it's a stock cost update
			return _('This transaction type cannot be voided.');
			break;
	}

	// only add an entry if it's actually been voided
	add_audit_trail($type, $type_no, $date_, _("Voided.")."\n".$memo_);
	$Refs->restore_last($type, $type_no);
	//add_voided_entry($type, $type_no, $date_, $memo_);
	update_void_status($type, $type_no, $date_, "Voided", $memo_, $_SESSION["wa_current_user"]->user);
	return false;
}

//--------------------------------------------------------------------------------------------------
//Modified by spyrax10 22 Jun 2022
function get_voided_entry($type = '', $type_no = null, $reference = null, $list = false, 
	$status = 'ALL', $from_date = null, $to_date = null, $void_id = 0) {

	set_global_connection();

    $sql = "SELECT * FROM ".TB_PREF."voided WHERE IFNULL(id, '') <> ''";

	if ($type != -1) {
		$sql .= " AND type = " .db_escape($type);
	}

	if ($type_no != null) {
		$sql .= " AND id = " .db_escape($type_no);
	}

	if ($void_id != 0) {
		$sql .= " AND void_id = " .db_escape($void_id);
	}

	if ($reference != null) {
		$sql .= " AND reference_from = " .db_escape($reference);
	}

	if ($status != 'ALL') {
		
		if ($status == 1) {
			$sql .= " AND cancel = 1";
		}
		else {
			$sql .= " AND cancel = 0 AND void_status = " .db_escape($status);
		}
	}

	if ($from_date != null || $to_date != null) {
		$sql .= " AND date_ >= '" . date2sql($from_date) . "' 
			AND date_ <= '" . date2sql($to_date) . "'";
	}

	$sql .= " ORDER BY id DESC";

	if ($list) {
		return $sql;
	}
	else {
		$result = db_query($sql, "could not query voided transaction table");
		return db_fetch($result);
	}

}

//--------------------------------------------------------------------------------------------------

function add_voided_entry($type, $type_no, $date_, $memo_, $branch_id = 0, $reference = '', $ref_to = '', $status = 'Draft', $cancel = 0,
	$voided_by = 0) {
	
	set_global_connection();
	
	$date = date2sql($date_);

	$sql = "INSERT INTO ".TB_PREF."voided (type, id, date_, memo_, branch_id, reference_from, reference_to, void_status, cancel, voided_by) ";
	$sql .= " VALUES (
			". db_escape($type) .", 
			". db_escape($type_no) .", 
			". db_escape($date) .", 
			". db_escape($memo_) .", 
			".db_escape($branch_id) .",
			".db_escape($reference) .",
			".db_escape($ref_to) .",
			".db_escape($status) . ",
			".db_escape($cancel) . ",
			".db_escape($voided_by);
	
	$sql .= ")";

	db_query($sql, "could not add voided transaction entry");

	return $type_no;
}

function update_void_status($type, $trans_no, $date, $status = "Draft", $note = "", $user_id = 0, $ref_to = '') {
	
	set_global_connection();
	$update_date = date2sql($date);

	$sql = "UPDATE ".TB_PREF."voided SET void_status = " .db_escape($status);

	if ($status == "Approved" || $status == "Disapproved") {
		$sql .= ", approved_by = " .db_escape($user_id) . ", date_approved = " .db_escape($update_date) 
			. ", status_note = " .db_escape($note);
	}
	else if ($status == "Voided") {
		$sql .= ", voided_by = " .db_escape($user_id) . ", date_voided = " .db_escape($update_date) 
			. ", reference_to = " .db_escape($ref_to);
	}

	$sql .= " WHERE type = " .db_escape($type) . " AND id = " .db_escape($trans_no);

	db_query($sql, "update_void_status()");

	return $trans_no;
}

function void_header($void_id, $type) {

	$document_total = 0;
	$void_row = get_voided_entry($type, null, null, false, 'ALL', null, null, $void_id);
	$banking = $void_row["type"] == ST_BANKPAYMENT || $void_row["type"] == ST_BANKDEPOSIT;
	$journal = $void_row["type"] == ST_JOURNAL;

	if ($banking) {
		$sql_row = db_fetch_assoc(db_query(get_banking_transactions($void_row["type"], '', '', null, null, '', '', '', $void_row["id"])));
		$source_ref_text = $void_row["type"] == ST_BANKDEPOSIT ? "Receipt No.: &nbsp;" : "Disbursement No.: &nbsp;";
		$document_total = $sql_row['amount'];
	}
	else if ($journal) {
		$sql_row = db_fetch_assoc(db_query(get_journal_transactions('', '', null, null, '', $void_row["id"])));
		$receipt_no = $sql_row['source_ref'];
		$source_ref_text= "Source Reference: &nbsp;";
		$document_total = $sql_row['amount'];
	}
	
	display_heading(_("Transaction to be Voided: "));
	start_outer_table(TABLESTYLE2, "width='80%'");

	table_section(1);
	label_row("Reference: &nbsp;", get_trans_view_str($void_row["type"], $void_row["id"], $void_row['reference_from']));
	label_row("Date: &nbsp;", phil_short_date($void_row['date_']));
	
	if ($banking) {
		label_row(_("Pay To: "), $sql_row['opening_balance'] == 1 ? "OPENING BALANCE" 
			: payment_person_name($sql_row['person_type_id'], $sql_row['masterfile'])
		);
		$receipt_no = $sql_row['receipt_no'];
		label_row(_("Cashier/Teller: &nbsp;"), get_user_name($sql_row['cashier_user_id']));
	}
	
	label_row($source_ref_text, $receipt_no);
	label_row("Void Remarks: &nbsp;", $void_row['memo_']);

	table_section(2);
	label_row("Approved By: &nbsp;", get_user_name($void_row['approved_by']));
	label_row("Date Approved: &nbsp;", phil_short_date($void_row['date_approved']));
	label_row("Approval Remarks: &nbsp;", $void_row['status_note']);
	label_row("Document Total: &nbsp;", price_format($document_total));

	hidden('void_memo', $void_row['memo_']);
	hidden('void_type', $void_row['type']);
	hidden('void_no', $void_row['id']);

	end_outer_table(1);
}

function void_entries($void_id, $trans_type, $ref_to = '') {

	$void_row = get_voided_entry($trans_type, null, null, false, 'ALL', null, null, $void_id);
	$gl_sql = get_gl_trans($void_row['type'], $void_row['id'], "DESC");
	$memo_ = $void_row['cancel'] == 1 ? "Cancelled" : "Voided";

	if ($trans_type == ST_JOURNAL) {
		$debtor_row = get_SI_by_reference($void_row['reference_from']);
		$source_si = db_fetch(db_query(get_journal_transactions($void_row['reference_from'])));
		$si_row = get_SI_by_reference($source_si['source_ref2']);	
	}

	if ($trans_type == ST_SALESINVOICE || $trans_type == ST_SALESINVOICEREPO) {
		$debtor_row = get_SI_by_reference('', $void_row['id'], $void_row['type']);
		$delivery_row = get_SI_by_reference($debtor_row['delivery_ref_no']);
		$delivery_gl = get_gl_trans($delivery_row['type'], $delivery_row['trans_no'], "DESC");
		$promo_item = get_promo_item_delivery($delivery_row['order_']); //Added by Albert Delete Dispatch qty out 05/06/2023
	}

	while ($row = db_fetch($gl_sql)) {
	
		update_gl_memo($row['counter'], $row['memo_'] . " - " . $memo_);

		add_gl_trans(
			$row['type'],
			$row['type_no'],
			sql2date($row['tran_date']),
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
		sql2date($void_row['date_']), "Voided", 
		$void_row['memo_'], 
		$_SESSION["wa_current_user"]->user,
		$ref_to
	);

	if ($trans_type != ST_SALESINVOICE || $trans_type != ST_SALESINVOICEREPO) {
		if (has_interbranch_entry($void_row['id'], $void_row['type'])) {
	
			$comp_id = get_comp_id(has_interbranch_entry($void_row['id'], $void_row['type']));
			$interb_status = bank_interB_stat($comp_id, $void_row['reference_from'], $void_row['type']);
	
			if ($interb_status == "Draft") {
				void_interB($comp_id, $void_row['reference_from'], $void_row['type'], 1);
			}
		}	
	}

	if ($trans_type == ST_JOURNAL) {
		if ($debtor_row["trans_no"] != null)  {
			if ($debtor_row['alloc'] > 0 && $debtor_row['ov_amount'] == 0) {
				void_payments_thru_JE($void_row['reference_from'], $void_row['id'], true);
				update_debtor_trans_allocation($si_row["type"], $si_row["trans_no"], $si_row["debtor_no"]);
			}
			else if ($debtor_row['ov_amount'] > 0 && $debtor_row['alloc'] == 0) {
				void_payments_thru_JE($void_row['reference_from'], $void_row['id']);
				update_AR_amount($si_row["trans_no"], $si_row["type"], $si_row["debtor_no"], -$debtor_row['ov_amount']);
			}
		}
	}

	if ($delivery_row['trans_no'] > 0) {
		
		$trans_no = add_voided_entry(
			$delivery_row['type'], 
			$delivery_row['trans_no'], 
			sql2date($void_row['date_']), 
			$void_row['memo_'], 
			user_company(),
			$debtor_row['delivery_ref_no'],
			'', 'Voided', $void_row['cancel'],
			$_SESSION["wa_current_user"]->user
		);

		update_status_debtor_trans($debtor_row['trans_no'], $debtor_row['debtor_no'], $debtor_row['type'], 'Cancelled','');

		while ($row = db_fetch($delivery_gl)) {
			
			update_gl_memo($row['counter'], $row['memo_'] . " - " . $memo_);

			add_gl_trans(
				$row['type'],
				$row['type_no'],
				sql2date($row['tran_date']),
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

		//Added by Albert Delete Dispatch qty out 05/06/2023
		if($promo_item['trans_no'] > 0){

			add_voided_entry(
				$promo_item['type'], 
				$promo_item['trans_no'], 
				sql2date($void_row['date_']), 
				$void_row['memo_'], 
				user_company(),
				$promo_item['reference'],
				'', 'Voided', $void_row['cancel'],
				$_SESSION["wa_current_user"]->user
			);
			/*modified by Albert 5/10/2023*/
			$delivery_item = get_SI_details($promo_item['trans_no']);
			$void = get_voided_entry($promo_item['type'], $promo_item['trans_no']);
			// delete_smo($promo_item['trans_no'], $promo_item['type']);
			foreach ($delivery_item as $line=>$item){
				$row_delivery = db_fetch(get_stock_moves($promo_item['type'], $promo_item['trans_no'], '', '', '', $item['stock_id']));
				add_stock_move(
					ST_VOID,
					$item['stock_id'],
					$void['void_id'],
					$row_delivery['loc_code'],
					sql2date($void_row['date_']),
					$debtor_row['reference'],
					$item['quantity'],
					$item['standard_cost'],
					0,
					$item['lot_no'],
					$item['chassis_no'],
					$row_delivery['category_id'],
					$item['stock_id'], 
					ST_VOID,
					$void['void_id'],
					$row_delivery['item_type']
				);
			}
		}
		
			$delivery_item = get_SI_details($delivery_row['trans_no']);
			$void = get_voided_entry($delivery_row['type'], $delivery_row['trans_no']);
			
			foreach ($delivery_item as $line=>$item){
				$row_delivery = db_fetch(get_stock_moves($delivery_row['type'], $delivery_row['trans_no'], '', '', '', $item['stock_id']));
				add_stock_move(
					ST_VOID,
					$item['stock_id'],
					$void['void_id'],
					$row_delivery['loc_code'],
					sql2date($void_row['date_']),
					$debtor_row['reference'],
					$item['quantity'],
					$item['standard_cost'],
					0,
					$item['lot_no'],
					$item['chassis_no'],
					$row_delivery['category_id'],
					$item['lot_no'] == null ? $item['stock_id'] : $item['color_code'], 
					ST_VOID,
					$void['void_id'],
					$row_delivery['item_type']
				);
			}

		
		/**/
			// delete_smo($delivery_row['trans_no'], $delivery_row['type']);
			add_audit_trail($void_row['type'], $void_row['id'], sql2date($void_row['date_']), 
				_("Voided From Reference: " . $void_row['reference_from'])."\n". $void_row['memo_']
			);
	}

	add_audit_trail($void_row['type'], $void_row['id'], sql2date($void_row['date_']), _("Voided.")."\n". $void_row['memo_']);
	return $void_id;
}

//Added by Albert Delete Dispatch qty out 05/06/2023
function get_promo_item_delivery($order_no){
	set_global_connection();

	$sql =" SELECT DISTINCT a.trans_no, a.type, a.reference
			FROM ".TB_PREF." debtor_trans a 
			INNER JOIN ".TB_PREF." debtor_trans_details b  on a.trans_no = b.debtor_trans_no
			WHERE b.item_type = 'Promo' AND a.type = 13";
	$sql .= " AND a.order_ = ".db_escape($order_no);
	$result = db_query($sql, "Cant get promo item delivery!");
	
	return db_fetch($result);;
}
//--------------------------------------------------------------------------------------------------

