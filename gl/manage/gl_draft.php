<?php

/**
 * Added by spyrax10
 */


$path_to_root="../..";

if (isset($_GET['status'])) {
	$page_security = $_GET['status'] == 1 ? 'SA_INTER_BANK_POST' : 'SA_INTER_BANK_DRAFT';
}
else {
	$page_security = 'SA_INTER_BANK_POST';
}


include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/includes/ui/items_cart.inc");

$js = "";
if ($SysPrefs->use_popup_windows) {
	$js .= get_js_open_window(800, 500);
}
if (user_use_date_picker()) {
	$js .= get_js_date_picker();	
}

if (isset($_GET['trans_no'])) {
	$trans_no = $_GET['trans_no'];
}

if (isset($_GET['type'])) {
	$trans_type = _systype_name($_GET['type']);
}

$help_context = '';
if (isset($_GET['trans_no'])) {
	$help_context = $_GET['status'] == 0 ? "Review Interbranch Transaction #" . $trans_no : 
    	"Post Interbranch Transaction #" . $_GET['trans_no'];    
}
else {
	if (isset($_GET['AddedJE'])) {
		$trans_no = $_GET['AddedJE'];
		$help_context = 'Posted Journal Entry #' . $trans_no;
	}
	else if (isset($_GET['AddedRE'])) {
		$trans_no = $_GET['AddedRE'];
		$help_context = 'Posted Receipts Entry #' . $trans_no;
	}
	else if (isset($_GET['AddedDE'])) {
		$trans_no = $_GET['AddedDE'];
		$help_context = 'Posted Disbursement Entry #' . $trans_no;
	}
}

page(_($help_context), false, false, "", $js);

//-----------------------------------------------------------------------------------
if (isset($_GET['AddedJE'])) {
	$trans_no = $_GET['AddedJE'];

	display_notification_centered(_("Journal Entry has been entered ") . " #$trans_no");
	display_note(get_gl_view_str(ST_JOURNAL, $trans_no, _("&View the GL Postings for this transaction"), false, '', '', 1));
	hyperlink_params("$path_to_root/gl/inquiry/interbranch_list.php?", _("Back to Banking Interbranch Inquiry"), "");
	display_footer_exit();
}
else if (isset($_GET['AddedRE'])) {
	$trans_no = $_GET['AddedRE'];

	display_notification_centered(_("Receipt Entry has been entered ") . " #$trans_no");
	display_note(get_gl_view_str(ST_BANKDEPOSIT, $trans_no, _("&View the GL Postings for this transaction"), false, '', '', 1));
	hyperlink_params("$path_to_root/gl/inquiry/interbranch_list.php?", _("Back to Banking Interbranch Inquiry"), "");
	display_footer_exit();
}
else if (isset($_GET['AddedDE'])) {
	$trans_no = $_GET['AddedDE'];

	display_notification_centered(_("Disbursement entry has been entered ") . " #$trans_no");
	display_note(get_gl_view_str(ST_BANKPAYMENT, $trans_no, _("&View the GL Postings for this transaction"), false, '', '', 1));
	hyperlink_params("$path_to_root/gl/inquiry/interbranch_list.php?", _("Back to Banking Interbranch Inquiry"), "");
	display_footer_exit();
}


//-----------------------------------------------------------------------------------

global $Ajax;
if (list_updated('PersonDetailID')) {
	$br = get_branch(get_post('PersonDetailID'));
	$_POST['person_id'] = $br['debtor_no'];
	$Ajax->activate('person_id');
}

if (get_post('person_id') ) {
	if (get_post('PayType') != PT_CUSTOMER) {
		$_POST['PersonDetailID'] = get_post('person_id');
		$Ajax->activate('PersonDetailID');
	}
}

if (get_post('PayType') || get_post('bank_account')) {
    $Ajax->activate('gl_head');
}

if (get_post('trans_type')) {
	$Ajax->activate('gl_head');
}
else {
	$Ajax->activate('gl_head');
}

if (get_post('cashier_')) {
	global 	$Ajax;
	$_POST['cashier_teller'] = get_post('cashier_');
	$Ajax->activate('cashier_teller');
}

if (get_post('bank_account')) {
	global $Ajax;
	$Ajax->activate('bank_account');
}

//-----------------------------------------------------------------------------------

function update_gl_account_code($line_id, $account_code) {

	global $Ajax;

	$sql = "UPDATE " . TB_PREF . "bank_interbranch_trans SET 
		sug_mcode = '$account_code'";

	$sql .= " WHERE id =" .db_escape($line_id);

	set_global_connection();
    db_query($sql, "Cannot update gl_status! (spyrax10)");
	display_notification(_('Account Entry Successfully Updated!'));
	$Ajax->activate('gl_items');
}

function update_gl_status($trans_no, $trans_type, $approved = 0, $comments) {
	
	global $Refs;

    $status = $approved == 0 ? "Disapproved" : "Approved";
    $date = date('Y-m-d', strtotime(Today()));
    $approver = get_current_user_fullname();

    $sql = "UPDATE " . TB_PREF . "bank_interbranch_trans SET 
        status = '$status', comments = '$comments', 
        approved_date = '$date', approved_by = '$approver' ";
    
    $sql .= " WHERE transno_from_branch =" .db_escape($trans_no) . " 
        AND trantype_from_branch=" .db_escape($trans_type);

    set_global_connection();
    db_query($sql, "Cannot update gl_status! (spyrax10)");

	add_audit_trail($trans_type, $trans_no, Today(), "Update Banking Interbranch Entry Status to " 
        . $status);
	
}

function post_gl_status($trans_no, $from_type, $to_type, $new_no) {
	
	$date = date('Y-m-d', strtotime(Today()));
	$sql = "UPDATE " . TB_PREF . "bank_interbranch_trans SET 
		status = 'Closed', post_date = '$date', transno_to_branch = '$new_no', 
		trantype_to_branch = '$to_type'";

	$sql .= " WHERE transno_from_branch =" .db_escape($trans_no) . " 
		AND trantype_from_branch=" .db_escape($from_type);

	set_global_connection();
	db_query($sql, "Cannot update stock_adjustment! (spyrax10)");
}

//-----------------------------------------------------------------------------------

function display_gl_post($trans_no, $trans_type) {

    global $Refs, $Ajax;

    $payment = ST_BANKPAYMENT;
    $row = get_gl_items($_GET['trans_no'], $_GET['type'], true, false);
    
	$journal_ref = $Refs->get_next($_GET['type'], null, sql2date($row['trans_date']));

    div_start('gl_head');
    start_outer_table(TABLESTYLE2, "width='95%'");

	table_section(1, "21%");
	label_row(_("From Branch: "), get_db_location_name($row['branch_code_from']));
	label_row(_("Originating Type: "), _systype_name($row['trantype_from_branch']));
	label_row(null, ''); label_row(null, ''); label_row(null, '');
	label_row(_("Transaction Date: "), sql2date($row['trans_date']));

	if (has_COH_entry($_GET['trans_no'], $_GET['type'])) {
		label_row(_("Transaction Type: "), _systype_name(ST_BANKPAYMENT));
		$trans_type = ST_BANKPAYMENT;
		hidden('trans_type', ST_BANKPAYMENT + 1);
	}
	else {
		numeric_type_list(_("Transaction Type: "), 'trans_type', 
			array(
				_('Journal Entry'),
				_('Disbursement Entry'),
				_('Receipts Entry')
			), null, true, _('----- SELECT -----'), 'label', false
		);
		label_row(null, '');
	}

	$reference = $Refs->get_next($trans_type, null, sql2date($row['trans_date']));
	
	if ($trans_type != -1) {
		table_section(2, "27%");

		if ($trans_type != 0) {
			if (!isset($_POST['PayType'])) {
				if (isset($_GET['PayType'])) {
					$_POST['PayType'] = $_GET['PayType'];
				}		
				else {
					$_POST['PayType'] = "";
				}		
			}
	
			if (!isset($_POST['person_id'])) {
				if (isset($_GET['PayPerson'])) {
					$_POST['person_id'] = $_GET['PayPerson'];
				}	
				else {
					$_POST['person_id'] = "";
				}			
			}
	
			if (isset($_POST['_PayType_update'])) {
				$_POST['person_id'] = '';
				$Ajax->activate('gl_head');
				$Ajax->activate('code_id');
				$Ajax->activate('pagehelp');
				$Ajax->activate('editors');
				$Ajax->activate('footer');
			}
			value_type_list(_("Pay To:"), 'PayType', 
    			array(
        			PT_CUSTOMER => 'Customer', 
        			PT_SUPPLIER => 'Suppier',
					PT_BRANCH => 'Branch'
    			), 'label', null, true, '', true
			);
	
			switch ($_POST['PayType']) {

				case PT_SUPPLIER:
					supplier_list_row(_("Supplier:"), 'person_id', null, false, true, false, true);
					break;
	
				case PT_CUSTOMER:
					customer_list_row(_("Customer:"), 'person_id', null, false, true, false, true);
	
					if (db_customer_has_branches($_POST['person_id'])) {
						customer_branches_list_row(_("Branch:"), $_POST['person_id'], 'PersonDetailID',
							null, false, true, true, true
						);
					} else {
						$_POST['PersonDetailID'] = ANY_NUMERIC;
						hidden('PersonDetailID');
					}
	
					$trans = get_customer_habit($_POST['person_id']); // take care of customers on hold
					if ($trans['dissallow_invoices'] != 0) {
						if ($payment) {
							$customer_error = true;
							display_error(_("This customer account is on hold."));
						} else
							display_warning(_("This customer account is on hold."));
					}
					break;

					case PT_BRANCH:
						company_list_row(_('Branch: '), 'person_id', true, false, true, true, true);
						break;

				default:
					break;
			}
			ref_row(_("Transaction #:"), 'ref', '', $reference, false, $trans_type);
	
		
		}
		else {
			date_row(_("Journal Date:"), 'journal_date', '');
			if (input_changed('journal_date'))
			{
				unset($_POST['ref']);
				$Ajax->activate('ref');
			}
			
			ref_row(_("Transaction #:"), 'ref', '', $reference, false, $trans_type);
			hidden('ref_original');		
		}
    
    	table_section(3, "24%");

		if ($trans_type != 0) {
			
			date_row(_("Posting Date:"), 'date_', '', true, 0, 0, 0, null, true);
			label_row($trans_type == ST_BANKDEPOSIT ? _("Receipts #: ") : _("Disbursement #: "), $row['ref_no']);
			hidden('receipt_no', $row['ref_no']);

			if (get_user_role($_SESSION["wa_current_user"]->user) == 'Cashier/Teller') {
				
				$_POST['cashier_teller'] = $_SESSION["wa_current_user"]->user;
				label_row(_("Cashier/Teller:"), $_POST['cashier_teller'] . " | " . get_current_user_fullname());
				hidden('cashier_teller');
			}
			else {
				sql_type_list(_("Cashier/Teller:"), 'cashier_', 
					allowed_dcpr_users(), 'id', 'real_name', 
					'label', null, true, 'Select Cashier'
				);
			}
	
			if ($trans_type == ST_BANKDEPOSIT) {
				sql_type_list(_("Collection Type:"), 'typecollection', 
					collection_type_list(), 'collect_id', 'collection', 
					'label', null, true, '', false, true
				);
			}
		}
		else {
			date_row(_("Document Date:"), 'doc_date', '');
    		date_row(_("Event Date:"), 'event_date', '');
			label_row(_("Source ref: "),  $row['ref_no']);
		}

    	table_section(4, "24%");

		if ($trans_type != 0) {
			
			sql_type_list(_("Debit To:"), 'bank_account', get_bank_lists(), 'id', 'bank_account_name', 
				'label', null, true, _("Select Bank Account")
			);

			label_row("Total Amount: ", price_format(get_interbranch_total($trans_no, $_GET['type'])));
	
			if (get_post('bank_account') == BT_CHECK) {
				date_row(_("Check Date:"), 'check_date', '');
				text_row(_("Check #:"), 'check_no', null, 35, 35);
				text_row(_("Bank Branch:"), 'bank_branch', null, 35, 35);
			}
	
			if ($payment) {
				bank_balance_row($_POST['bank_account']); 
			}
		}
	}

	end_outer_table(1);

	display_gl_details($trans_no, $_GET['type'], 1);
}

function display_update_item($line_id) {

	global $Ajax;
	$row = get_gl_line($line_id);

	div_start("upd_tbl");

	start_table(TABLESTYLE2);
	display_heading("Update Account Entry for ID #" . $line_id);
    echo "<br>";

	table_section(1);
	$_POST ['line_id'] = $line_id;
	hidden('line_id');
	gl_all_accounts_list_row(_('Account Entry: '), 'account_upd', $row['sug_mcode'], false, false, _("Default Account"), false);

	end_table(1);
    div_end();
    submit_add_or_update_center(false, '', 'both');
    echo "<br> <br>";

}

//-----------------------------------------------------------------------------------
function can_proceed($approve_stat = 0) {

	if (!is_date_in_fiscalyear(Today())) {
        display_error(_("The Today's Date is OUT of FISCAL YEAR or is CLOSED for further data entry!"));
		return false;
    }

    if (get_post('memo_') == '' && $approve_stat == 2) {
        display_error(_("Comments cannot be empty."));
        set_focus('memo_');
        return false;
    }

    return true;
}


function check_trans($trans_no, $trans_type)
{
	global $Refs, $systypes_array;

	$input_error = 0;

	if ($trans_type == -1) {
		display_error(_("Please Select Transaction Type!"));
		set_focus('trans_type');
		$input_error = 1;
	}
	else if ($trans_type != ST_JOURNAL) {

		$limit = get_bank_account_limit($_POST['bank_account'], $_POST['date_']);
    	$amnt_chg = abs(get_interbranch_total($trans_no, $_GET['type']));

		if ($limit !== null && floatcmp($limit, -$amnt_chg) < 0) {
			display_error(sprintf(_("The total bank amount exceeds allowed limit (%s)."), price_format($limit -$amnt_chg)));
			set_focus('code_id');
			$input_error = 1;
		}
		if ($trans = check_bank_account_history($amnt_chg, $_POST['bank_account'], $_POST['date_'])) {
	
			if (isset($trans['trans_no'])) {
				display_error(sprintf(_("The bank transaction would result in exceed of authorized overdraft limit for transaction: %s #%s on %s."),
					$systypes_array[$trans['type']], $trans['trans_no'], sql2date($trans['trans_date'])
				));
				set_focus('amount');
				$input_error = 1;
			}
		}
		if (!check_reference($_POST['ref'], $trans_type, get_max_bank_trans($trans_type))) {
			set_focus('ref');
			$input_error = 1;
		}
		if (!is_date($_POST['date_'])) {
			display_error(_("The entered date for the payment is invalid."));
			set_focus('date_');
			$input_error = 1;
		} elseif (!is_date_in_fiscalyear($_POST['date_'])) {
			display_error(_("The Entered Date is OUT of FISCAL YEAR or is CLOSED for further data entry!"));
			set_focus('date_');
			$input_error = 1;
		}
	
		if (get_post('PayType') == PT_CUSTOMER && (!get_post('person_id') || !get_post('PersonDetailID'))) {
			display_error(_("You have to select customer and customer branch."));
			set_focus('person_id');
			$input_error = 1;
		} elseif (get_post('PayType') == PT_SUPPLIER && (!get_post('person_id'))) {
			display_error(_("You have to select supplier."));
			set_focus('person_id');
			$input_error = 1;
		}
		if (!db_has_currency_rates(get_bank_account_currency($_POST['bank_account']), $_POST['date_'], true)) {
			$input_error = 1;
		}
			
		if (get_post('receipt_no') == '') {
			display_error(_("Please Enter Receipt # for this transaction!"));
			set_focus('receipt_no');
			$input_error = 1;
		}

		if (get_post('bank_account') == BT_CHECK && (!get_post('check_no'))) {
			display_error(_("Check # cannot be empty."));
			set_focus('check_no');
			$input_error = 1;
		}

		if (cheque_exists($_POST['check_no']) && get_post('check_no') != '' && 
			get_post('bank_account') == BT_CHECK) {
			display_error(_("Cheque already been used!"));
			set_focus('check_no');
			$input_error = 1;
		}
	
		if (get_post('bank_account') == BT_CHECK && (!get_post('bank_branch'))) {
			display_error(_("Bank Branch cannot be empty."));
			set_focus('bank_branch');
			$input_error = 1;
		}
	
		if ($trans_type == ST_BANKPAYMENT && get_post('memo_') == '') {
			display_error(_("Please Enter Memo for this transaction!"));
			set_focus('memo_');
			$input_error = 1;
		}

		if ($_POST['cashier_teller'] == '') {
			display_error(_("Please Select Cashier/Teller!"));
			$input_error = 1;
		}

		if (!get_post('bank_account')) {
			display_error(_("Please Select Bank Account!"));
			$input_error = 1;
		}
	}

	return $input_error;
}

//-----------------------------------------------------------------------------------
if (isset($_POST['UPDATE_ITEM'])) {
	
	$row = get_gl_items($_GET['trans_no'], $_GET['type'], true);

	update_gl_account_code(get_post('line_id'), 
		get_post('account_upd') != null ? get_post('account_upd') : $row['sug_mcode']
	);
}

if (isset($_POST['Approved']) && can_proceed(1)) { 
	if (gl_interbranch_status($_GET['trans_no'], $_GET['type']) == 'Draft') {
		update_gl_status($_GET['trans_no'], $_GET['type'], 1, get_post('memo_'));
    	meta_forward("../inquiry/interbranch_list.php?");
	}
	else {
		display_error(_('Invalid Transaction!'));
	} 
}

if (isset($_POST['Disapproved']) && can_proceed(2)) {
	if (gl_interbranch_status($_GET['trans_no'], $_GET['type']) == 'Draft') {
		update_gl_status($_GET['trans_no'], $_GET['type'], 0, get_post('memo_'));
    	meta_forward("../inquiry/interbranch_list.php?");
	}
	else {
		display_error(_('Invalid Transaction!'));
	}
}

function post_trans_entries($trans_type) {

	$row = get_gl_items($_GET['trans_no'], $_GET['type'], true, false);
	$row_line = get_gl_items($_GET['trans_no'], $_GET['type'], false, false);

	if ($trans_type == ST_JOURNAL) {
		if (isset($_SESSION['journal_items'])) {

			unset ($_SESSION['journal_items']);
		}
		
		$cart = new items_cart($trans_type);
		$_SESSION['journal_items'] = &$cart;
		
		$cart = &$_SESSION['journal_items'];
		$cart->reference = $_POST['ref'];
		$cart->tran_date = $_POST['journal_date'];
		$cart->doc_date = $_POST['doc_date'];
		$cart->event_date = $_POST['event_date'];
		$cart->source_ref = $row['ref_no'];
		$cart->trans_db = user_company();
		$cart->memo_ = $_POST['memo_'];
		$cart->currency = 'PHP';

		if ($cart->currency != get_company_pref('curr_default')) {
			$cart->rate = input_num('_ex_rate');
		}

		$cart->tax_info = false;

		while ($row = db_fetch($row_line)) {

			$_SESSION['journal_items']->add_gl_item(
				$row['sug_mcode'], 
				0, 
				0, 
				$row['amount'], 
				get_gl_line_memo($row['gl_line_id'], $row['branch_code_from']), 
				'', 
				null, 
				null, 
				$row['mcode'], 
				$row['masterfile'],
				$row['hocbc_id'], 
				user_company(),
				''
			);
		}

		$trans_no = write_journal_entries($cart, true);

		$cart->clear_items();
		unset($_SESSION['journal_items']);

		if ($trans_no) {
			post_gl_status($_GET['trans_no'], $_GET['type'], $trans_type, $trans_no);
			meta_forward($_SERVER['PHP_SELF'], "AddedJE=$trans_no");
		}
	}
	else {

		if (isset($_SESSION['pay_items'])) {

			unset ($_SESSION['pay_items']);
		}

		$cart = new items_cart($trans_type);
		$_SESSION['pay_items'] = &$cart;
		$total_amount  = $trans_type == ST_BANKPAYMENT ? get_interbranch_total($_GET['trans_no'], $_GET['type']) : 
			-get_interbranch_total($_GET['trans_no'], $_GET['type']);

		while ($row = db_fetch($row_line)) {

			$_SESSION['pay_items']->add_gl_item(
				$row['sug_mcode'], 
				0,
				0,
				$row['amount'],
				"",
				null,
				null,
				null,
				$row['mcode'],
				$row['masterfile'],
				$row['hocbc_id'],
				user_company(),
				'' 
			);
		}

		begin_transaction();

		$_SESSION['pay_items'] = &$_SESSION['pay_items'];
		$new = $_SESSION['pay_items']->order_id == 0;

		add_new_exchange_rate(get_bank_account_currency(get_post('bank_account')), get_post('date_'), input_num('_ex_rate'));

		$trans = write_bank_transaction (
			$trans_type,
			$_SESSION['pay_items']->order_id,
			$_POST['bank_account'],
			$_SESSION['pay_items'],
			$_POST['receipt_no'],
			$_POST['date_'] != null ? $_POST['date_'] : $row['approved_date'],
			$_POST['cashier_teller'],
			get_post('bank_account') == BT_CHECK ? $_POST['check_no'] : '',
			get_post('bank_account') == BT_CHECK? $_POST['bank_branch'] : '',
			get_post('bank_account') == BT_CHECK ? 'Cheque' : 'Cash',
			get_post('PayType') != null ? get_post('PayType') : 0,
			get_post('person_id') != null ? get_post('person_id') : 0,
			get_post('PersonDetailID') != null ? get_post('PersonDetailID') : '',
			$_POST['ref'],
			$_POST['memo_'],
			true,
			null,
			"COH",
			get_bank_account_name($_POST['bank_account']),
			get_post('bank_account') == BT_CHECK ? $_POST['check_date'] : null,
			true, 0,
			$total_amount
		);

		$trans_type = $trans[0];
		$trans_no = $trans[1];
		new_doc_date($_POST['date_']);

		$_SESSION['pay_items']->clear_items();
		unset($_SESSION['pay_items']);

		commit_transaction();

		if ($trans_no) {
			post_gl_status($_GET['trans_no'], $_GET['type'], $trans_type, $trans_no);

			if ($trans_type == ST_BANKPAYMENT) {
				meta_forward($_SERVER['PHP_SELF'], "AddedDE=$trans_no");
			}
			else if ($trans_type == ST_BANKDEPOSIT) {
				meta_forward($_SERVER['PHP_SELF'], "AddedRE=$trans_no");
			}
			
		}
	}
}

if (isset($_POST['Process']) && !check_trans($_GET['trans_no'], (int)get_post('trans_type') - 1)) {
	
	$trans_type = get_post('trans_type') - 1;

	if (gl_interbranch_status($_GET['trans_no'], $_GET['type']) == 'Approved') {
		
		post_trans_entries($trans_type);

	}
	else {
		display_error(_("Transaction is not approved yet."));
	}
}

//-----------------------------------------------------------------------------------
start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

$edit_id = find_submit('Edit');

if ($_GET['status'] == 0) {
	if (gl_interbranch_status($_GET['trans_no'], $_GET['type']) == 'Draft') {
		display_gl_draft($_GET['trans_no'], $_GET['type'], '');
	} 
	else {
		display_error(_("Invalid Transaction!"));
		die();
	}
    
}
else if ($_GET['status'] == 1) {
	if (gl_interbranch_status($_GET['trans_no'], $_GET['type']) == 'Approved') {
		display_gl_post($_GET['trans_no'], (int)get_post('trans_type') - 1);
	}
	else {
		display_error(_("Invalid Transaction!"));
		die();
	}
}

if ($edit_id != -1) {
    $id = get_post('selected_id', find_submit('Edit'));
    display_update_item($id);
}

start_table();
textarea_row(_("Remarks: "), 'memo_', null, 50, 3);
end_table();

echo "<br> <br>";

if ($_GET['status'] == 0) {
    submit_center_first('Approved', _("Reviewed"), '', 'default');
    submit_center_last('Disapproved', _("Disapproved"), '', 'default', ICON_DELETE);
}
else if ($_GET['status'] == 1) {
    submit_center_last('Process', _("Process Transaction"), '', 'default');
}

end_form();
end_page();