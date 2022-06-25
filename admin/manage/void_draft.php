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
else {
    $trans_no = $_GET['trans_no'];
    if ($_GET['status'] == 1) {
        $header_text = _("Pending");
    } 
    else {
        $header_text = _("Create Request To");
    }
}

page(_($help_context = $header_text ." Void Transaction # " . $trans_no), false, false, "", $js);
//---------------------------------------------------------------------------------------------
if (isset($_GET['AddedID'])) {
    $trans_no = $_GET['AddedID'];

    display_notification_centered(sprintf(_("Request to Void this Transaction Success!"), $trans_no));
    hyperlink_params("$path_to_root/admin/inquiry/void_inquiry_list.php", _("Back to Void Transactions List"), "");
	display_footer_exit();
}
else if (isset($_GET['ApprovedID'])) {
    $trans_no = $_GET['ApprovedID'];

    display_notification_centered(sprintf(_("Request to Void this Transaction has been Approved!"), $trans_no));
    hyperlink_params("$path_to_root/admin/inquiry/void_inquiry_list.php", _("Back to Void Transactions List"), "");
	display_footer_exit();
}
//---------------------------------------------------------------------------------------------

function display_menu($trans_no, $type) {

    $display_sql = "";
    $void_row = get_voided_entry($type, $trans_no);
    $banking = $type == ST_BANKPAYMENT || $type == ST_BANKDEPOSIT;
    $journal = $type == ST_JOURNAL;

    if ($banking) {
        $display_sql = db_query(get_banking_transactions($type, '', '', null, null, '', '', '', $trans_no));
    }
    else if ($journal) {
        $display_sql = "";
    }

    div_start("menu_head");
    start_outer_table(TABLESTYLE2, "width='75%'");

    if ($display_sql != "") {

        while ($row = db_fetch_assoc($display_sql)) {
            
            if ($banking) {
                $trans_date = $row['trans_date'];
                $reference = $row['ref'];
                $source_ref = $row['receipt_no'];
                $cashier = get_user_name($row['cashier_user_id']);
                $bank_name = get_bank_lists($row['bank_act']);
                $pay_type = $row['pay_type'];
                $pay_to = payment_person_name($row['person_type_id'], $row['masterfile']);
                if ($type == ST_BANKPAYMENT) {
                    if ($pay_type != 'Cash') {
                        $check_date = phil_short_date($row['check_date']);
                        $check_no = $row['check_no'];
                        $check_branch = $row['bank_branch'];
                    }
                }
                $total_amount = $row['amount'];
            }

            table_section(1);
            label_row("Reference: &nbsp;", $reference);
            label_row("Transaction Date: &nbsp;", phil_short_date($trans_date));
            if ($banking) {
                label_row("Pay To: ", $pay_to);
            }

            if ($void_row != null) {
                label_row("Document Remarks: &nbsp;", $void_row['memo_']);
            }
            
            table_section(2);
            label_row("Receipt No: &nbsp;", $source_ref);
            label_row("Cashier / Teller : &nbsp;", $cashier);

            table_section(3);
            if ($banking) {
                label_row("Debit To: &nbsp;", $bank_name);
                if ($pay_type != 'Cash') {
                    label_row("Check Date: &nbsp;", $check_date);
                    label_row("Check #: &nbsp;", $check_no);
                    label_row("Bank Branch: &nbsp;", $check_branch);
                }
            }

            label_row("Document Total: &nbsp;", price_format($total_amount));

            hidden("reference", $reference);
        }
    }
    end_outer_table(1);
    div_end();
}

function menu_rows($trans_no, $type) {

    $display_sql = get_gl_trans($type, $trans_no);

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

    while ($row = db_fetch($display_sql)) {
        alt_table_row_color($k);

        label_cell($row['counter']);
        label_cell(phil_short_date($row['tran_date']), "align='center'");
        label_cell($row['account'], "align='center'");
        label_cell(get_gl_account_name($row['account']), "nowrap align='left'");
        label_cell($row['mcode'], "align='center'");
        label_cell($row['master_file'], "nowrap align='left'");
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

if (isset($_POST['Process'])) {

    $trans_no = add_voided_entry(
        $_GET['type'], 
        $_GET['trans_no'], 
        Today(), 
        get_post('memo_'), 
        user_company(),
        get_post('reference'),
        ''
    );

    if ($trans_no) {
        meta_forward($_SERVER['PHP_SELF'], "AddedID=" . $trans_no);
    }

}

if (isset($_POST['Approved'])) {
    if (get_post('memo_') == null) {
        display_error(_("Remarks is needed for this transaction..."));
    }
    else {
        $trans_no = update_void_status(
            $_GET['type'], 
            $_GET['trans_no'], 
            Today(),
            "Approved",
            get_post('memo_'),
            $_SESSION["wa_current_user"]->user
        );

        if ($trans_no) {
            meta_forward($_SERVER['PHP_SELF'], "ApprovedID=" . $trans_no);
        }
    }
}

if (isset($_POST['Disapproved'])) {
    if (get_post('memo_') == null) {
        display_error(_("Remarks is needed for this transaction..."));
    }
    else {
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
}

//---------------------------------------------------------------------------------------------
start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

$void_row = get_voided_entry($_GET['type'], $_GET['trans_no']);

display_menu($_GET['trans_no'], $_GET['type']);

menu_rows($_GET['trans_no'], $_GET['type']);
void_remarks($_GET['status']);

if ($_GET['status'] == 0 && $void_row == null) {
    submit_center_last('Process', _("Request to Void this Transaction"), '', 'default');
}
else if ($_GET['status'] ==  1 && $void_row['void_status'] == "Draft") {
    submit_center_first('Approved', _("Approved"), '', 'default');
    submit_center_last('Disapproved', _("Disapproved"), '', 'default', ICON_DELETE);
}

	
end_form();
//---------------------------------------------------------------------------------------------
end_page();