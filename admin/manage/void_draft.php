<?php

/**
 * Created by: spyrax10
 * Date Created: 22 Jun 2022     
*/

$page_security = 'SA_VOID_APPROVED';
$path_to_root = "../..";

include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/admin/db/transactions_db.inc");

include_once($path_to_root . "/admin/db/voiding_db.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/includes/aging.inc");

$js = "";

if (user_use_date_picker()) {
    $js .= get_js_date_picker();
}
if ($SysPrefs->use_popup_windows) {
    $js .= get_js_open_window(800, 500);
}

if (isset($_GET['AddedID'])) {
    $trans_no = $_GET['AddedID'];
    $header_text = _("Request to ");
}
else if (isset($_GET['ApprovedID'])) {
    $trans_no = $_GET['ApprovedID'];
    $header_text = _("Approved to");
}
else if (isset($_GET['CancelID'])) {
    $trans_no = $_GET['CancelID'];
    $header_text = _("Request to ");
}
else {
    $trans_no = $_GET['trans_no'];
    if ($_GET['status'] == 1) {
        $header_text = _("Pending");
    } 
    else {
        $header_text = _("Create Request To");
    }
}

if ($_GET['cancel'] == 1) {
    $type_text = "Cancel";
}
else if ($_GET['cancel'] == 0) {
    $type_text = "Void";
}

page(_($help_context = $header_text ." $type_text Transaction # " . $trans_no), false, false, "", $js);
//---------------------------------------------------------------------------------------------
if (isset($_GET['AddedID'])) {
    $trans_no = $_GET['AddedID'];
    $type_text = $_GET['cancel'] == 1 ? "Cancel" : "Void";

    display_notification_centered(sprintf(_("Request to $type_text this Transaction Success!"), $trans_no));
    hyperlink_params("$path_to_root/admin/inquiry/void_inquiry_list.php", _("Back to Void Transactions List"), "");
	display_footer_exit();
}
else if (isset($_GET['ApprovedID'])) {
    $trans_no = $_GET['ApprovedID'];
    $type_text = $_GET['cancel'] == 1 ? "Cancel" : "Void";

    display_notification_centered(sprintf(_("Request to $type_text this Transaction has been Approved!"), $trans_no));
    hyperlink_params("$path_to_root/admin/inquiry/void_inquiry_list.php", _("Back to Void Transactions List"), "");
	display_footer_exit();
}
else if (isset($_GET['CancelID'])) {
    $trans_no = $_GET['CancelID'];
    $trans_type = $_GET['CancelType'];

    display_notification_centered(sprintf(_("This Transaction has been cancelled!"), $trans_no));
    display_note(get_gl_view_str($trans_type, $trans_no, _("&View the GL Postings for this Transaction"), false, '', '', 1));
    hyperlink_params("$path_to_root/admin/inquiry/void_inquiry_list.php", _("Back to Void Transactions List"), "");
	display_footer_exit();
}
//---------------------------------------------------------------------------------------------

function display_menu($trans_no, $type) {

    $display_sql = $debtor_no = ""; 
    $void_row = get_voided_entry($type, $trans_no);
    $banking = $type == ST_BANKPAYMENT || $type == ST_BANKDEPOSIT;
    $journal = $type == ST_JOURNAL;
    $sales_inv = $type == ST_SALESINVOICE || $type == ST_SALESINVOICEREPO;

    if ($banking) {
        $display_sql = db_query(get_banking_transactions($type, '', '', null, null, '', '', '', $trans_no));
    }
    else if ($journal) {
        $display_sql = db_query(get_journal_transactions('', '', null, null, '', $trans_no));
    }
    else if ($sales_inv) {
        $display_sql = db_query(get_sales_invoices($trans_no, '', -1, -1, 1, '', true));
    }

    div_start("menu_head");
    start_outer_table(TABLESTYLE2, "width='75%'");

    if ($display_sql != "") {

        while ($row = db_fetch_assoc($display_sql)) {
            
            if ($banking) {
                $trans_date = phil_short_date($row['trans_date']);
                $reference = $row['ref'];
                $source_ref = $row['receipt_no'];
                $cashier = get_user_name($row['cashier_user_id']);
                $bank_name = get_bank_accounts(false, $row['bank_act']);
                $pay_type = $row['pay_type'];
                $pay_to = payment_person_name($row['person_type_id'], $row['masterfile']);
                if ($type == ST_BANKPAYMENT) {
                    if ($pay_type != 'Cash') {
                        $check_date = phil_short_date($row['check_date']);
                        $check_no = $row['check_no'];
                        $check_branch = $row['bank_branch'];
                    }
                }
                $source_ref_text = $type == ST_BANKPAYMENT ? "Receipt No.: &nbsp;" : "Disbursement No.: &nbsp;";
                $total_amount = $row['amount'];
            }
            else if ($journal) {
                $reference = $row['reference'];
                $trans_date = phil_short_date($row['tran_date']);
                $source_ref = $row['source_ref'];
                $total_amount = $row['amount'];
                $source_ref_text= "Source Reference: &nbsp;";
            }
            else if ($sales_inv) {
                $reference = $row['reference'];
                $trans_date = phil_short_date($row['tran_date']);
                $source_ref = $row['debtor_ref'] . ' - ' . $row['name'];
                $total_amount = $row['ov_amount'];
                $source_ref_text= "Customer's Name: &nbsp;";
            }

            table_section(1);
            label_row("Transaction Reference: &nbsp;", get_trans_view_str($type, $trans_no, $reference));
            label_row("Transaction Date: &nbsp;", phil_short_date($trans_date));
            if ($banking) {
                label_row("Pay To: ", $pay_to);
            }
            else if ($sales_inv) {
                label_row("Reference: &nbsp;", $row['ref_no']);
            }

            if ($void_row != null) {
                label_row("Document Remarks: &nbsp;", $void_row['memo_']);
            }
            
            table_section(2);
            label_row($source_ref_text, $source_ref);
            
            if ($banking) {
                label_row("Cashier / Teller: &nbsp;", $cashier);
            }
            else if ($sales_inv) {
                label_row("Invoice Type: &nbsp;", strtoupper($row['invoice_type']));
                label_row("Payment Type: &nbsp;", $row['payment_type']);
                label_row("Category: &nbsp;", get_category_name($row['category_id']));
                label_row("Payment Location: &nbsp;", $row['payment_location']);
            }

            table_section(3);
            if ($banking) {
                label_row("Debit To: &nbsp;", $bank_name);
                if ($pay_type != 'Cash') {
                    label_row("Check Date: &nbsp;", $check_date);
                    label_row("Check #: &nbsp;", $check_no);
                    label_row("Bank Branch: &nbsp;", $check_branch);
                }
            }

            if ($sales_inv) {
                label_row("Payment Terms: &nbsp;", get_policy_name($row['months_term'], $row['category_id']));
                label_row("Months Term: &nbsp;", $row['months_term']);
                label_row("A/R Amount: &nbsp;", price_format($total_amount));
                label_row("LCP Amount: &nbsp;", price_format($row['lcp_2']));
                label_row("Amortization: &nbsp;", price_format($row['amortization_amount']));
                label_row("Downpayment: &nbsp;", price_format($row['downpayment_amount']));
            }
            else {
                label_row("Document Total: &nbsp;", price_format($total_amount));
            }

            if ($sales_inv) {
                $debtor_no = $row['debtor_no'];
                hidden("debtor_no", $row['debtor_no']);
            }
            hidden("reference", $reference);
        }
    }
    end_outer_table(1);

    menu_rows($_GET['trans_no'], $_GET['type'], $debtor_no);
    div_end();
}

function menu_rows($trans_no, $type, $debtor_no = '') {
    $display_sql = get_gl_trans($type, $trans_no);

    if ($debtor_no != '') {
        $cust_id = $debtor_no;
        $cust_name = get_customer_name($debtor_no);
    }

    div_start("menu_rows");
    start_table(TABLESTYLE, "width='75%'");

    $th = array(
        _("ID"),
        _("Journal Date"),
        _("Account Code"),
        _("Account Name"),
        _("MCode"),
        _("Masterfile"),
        _("Debit"),
        _("Credit"),
        _("Memo")
    );
    
    table_header($th);
    
    $k = $debit = $credit = 0;
    $masterfile = '';
    while ($row = db_fetch($display_sql)) {
        alt_table_row_color($k);
        if (($type == ST_SALESINVOICE || $type == ST_SALESINVOICEREPO) && $row['mcode'] == '') {
            $mcode = $cust_id;
            $masterfile = $cust_name;
        }
        else {
            $mcode = $row['mcode']; $masterfile = $row['master_file'];
        }

        label_cell($row['counter']);
        label_cell(phil_short_date($row['tran_date']), "align='center'");
        label_cell($row['account'], "align='center'");
        label_cell(get_gl_account_name($row['account']), "nowrap align='left'");
        label_cell($mcode, "align='center'");
        label_cell($masterfile, "nowrap align='left'");
        display_debit_or_credit_cells($row['amount'], true);
        label_cell($row['memo_'], "align='right'");

        if ($row['amount'] > 0 ) {
			$debit += $row['amount'];
		} 
    	
    	else {
			$credit += $row['amount'];
		}
    }

    start_row("class='inquirybg' style='font-weight:bold'");
    label_cell(_("Total"), "colspan=6");
    
    amount_cell($debit);
    amount_cell(-$credit);
    end_row();

    end_table();
    div_end();

}

function draft_remarks($remarks = '') {
    start_table();
    label_row("Document Remarks: &nbsp;", $remarks);
    end_table(1);

}

function void_remarks($status) {

    $text = $status == 0 ? _("Remarks: &nbsp;") : _("Approval Remarks: &nbsp;");
    br();
	start_table();
    textarea_row($text, 'memo_', null, 50, 3);
	end_table(1);
}

//---------------------------------------------------------------------------------------------
function can_proceed() {

    $void_row = get_voided_entry($_GET['type'], null, null, false, 'ALL', null, null, $_GET['void_id']);

    if (!is_date_in_fiscalyear(Today())) {
        display_error(_("The Entered Date is OUT of FISCAL YEAR or is CLOSED for further data entry!"));
		return false;
    }
    else if (!allowed_posting_date(Today())) {
		display_error(_("The Entered Date is currently LOCKED for further data entry!"));
		return false;
	}
    
    if (get_post('memo_') == null) {
        display_error(_("Remarks is needed for this transaction..."));
        return false;
    }

    if ($void_row['void_status'] == 'Voided') {
        display_error(_("This transaction is already Voided..."));
        return false;
    }

    if ($_GET['type'] == ST_SALESINVOICE || $_GET['type'] == ST_SALESINVOICEREPO) {
        $debtor_row = get_SI_by_reference($void_row['reference_from']);

        $payment_sql = get_customer_payments($debtor_row['trans_no'], $_GET['type'], $debtor_row['debtor_no']);

        while ($row = db_fetch($payment_sql)) {
            $void_pay = get_voided_entry($row['trans_type_from'], $row['trans_no']);

            if ($void_pay['void_status'] != 'Voided') {
                display_error(_("Can't Proceed! Payments are not fully voided..."));
                return false;
            }
        }
    }
    
    return true;
}

//---------------------------------------------------------------------------------------------

if (isset($_POST['Process'])) {
    $cancel = $type_text == "Cancel" ? 1 : 0;
    $trans_no = add_voided_entry(
        $_GET['type'], 
        $_GET['trans_no'], 
        Today(), 
        get_post('memo_'), 
        user_company(),
        get_post('reference'),
        '', 'Draft', $cancel
    );

    if ($trans_no) {
        meta_forward($_SERVER['PHP_SELF'], "AddedID=" . $trans_no . "&cancel=" . $cancel);
    }

}

if (isset($_POST['Approved']) && can_proceed()) {
    $cancel = $type_text == "Cancel" ? 1 : 0;

    if ($cancel == 0) {
        $trans_no = update_void_status(
            $_GET['type'], 
            $_GET['trans_no'], 
            Today(),
            "Approved",
            get_post('memo_'),
            $_SESSION["wa_current_user"]->user,
            ''
        );
    
        if ($trans_no) {
            meta_forward($_SERVER['PHP_SELF'], "ApprovedID=" . $trans_no . "&cancel=" . $cancel);
        }
    }
    else {
        $void_id = void_entries($_GET['void_id'], $_GET['type']);

        if ($void_id) {
            meta_forward($_SERVER['PHP_SELF'], "CancelID=" . $trans_no . "&cancel=" . $cancel . "&CancelType=" . $_GET['type']);
        }
    }
}

if (isset($_POST['Disapproved']) && can_proceed()) {
    $trans_no = update_void_status(
        $_GET['type'], 
        $_GET['trans_no'], 
        Today(),
        "Disapproved",
        get_post('memo_'),
        $_SESSION["wa_current_user"]->user
    );

    if ($trans_no) {
        meta_forward($path_to_root . "/admin/inquiry/void_inquiry_list.php?");
    }
}

//---------------------------------------------------------------------------------------------
start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

$void_row = get_voided_entry($_GET['type'], $_GET['trans_no']);

display_menu($_GET['trans_no'], $_GET['type']);

void_remarks($_GET['status']);

if ($_GET['status'] == 0 && $void_row == null) {
    submit_center_last('Process', _("Request to $type_text this Transaction"), '', 'default');
}
else if ($_GET['status'] ==  1 && $void_row['void_status'] == "Draft") {
    submit_center_first('Approved', _("Approved"), '', 'default');
    submit_center_last('Disapproved', _("Disapproved"), '', 'default', ICON_DELETE);
}

br();
end_form();
//---------------------------------------------------------------------------------------------
end_page();