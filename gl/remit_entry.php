<?php

/**
 * Created by: spyrax10
 * Date Created: 25 Mar 2022
 */

$path_to_root = "..";
$page_security = 'SA_REMIT';

include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/ui/items_cart.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/gl/includes/ui/gl_bank_ui.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");
include_once($path_to_root . "/admin/db/attachments_db.inc");

$js = '';

if ($SysPrefs->use_popup_windows) {
    $js .= get_js_open_window(800, 500);
}
if (user_use_date_picker()) {
    $js .= get_js_date_picker();
}

page(_("Remittance Entry"), false, false, '', $js);

//-----------------------------------------------------------------------------------------------

if (isset($_GET['AddedID'])) {

    $trans_no = $_GET['AddedID'];

    display_notification_centered(_("Remittance has been processed!"));
    display_note(get_gl_view_str(ST_REMITTANCE, $trans_no, _("View the GL &Postings for this Remittance Entry")), 1, 0);
	hyperlink_params($_SERVER['PHP_SELF'], _("Enter &Another Remittance Entry"), "");
    hyperlink_params("$path_to_root/gl/inquiry/remittance_list.php", _("Back to Remittance Entry Inquiry List"), "");

    display_footer_exit();
}

//-----------------------------------------------------------------------------------------------

function trans_num($row) {
    return $row['trans_no'];
}

function reference_row($row) {
    return get_trans_view_str($row["type"], $row["trans_no"], $row['ref']);
}

function trans_date($row) {
    return sql2date($row['trans_date']);
}

function pay_type($row) {
    return strtoupper($row['pay_type']);
}

function is_interbranch($row) {
    return has_interbranch_entry($row['trans_no'], $row['type']) ? "Interbranch Entry" : "Normal Entry";
}

function doc_ref($row) {
    return $row['receipt_no'];
}

function amount_total($row) {
    return ABS($row['amt']);
}

function systype_name($row) {
	global $systypes_array;
	
	return $systypes_array[$row['type']];
}

//-----------------------------------------------------------------------------------------------

function can_process() {

    if (!check_reference($_POST['ref'], ST_REMITTANCE)) {
		set_focus('ref');
		return false;
	}

    if (!is_date($_POST['date_'])) {
		display_error(_("The entered date for this transaction is invalid!"));
		set_focus('date_');
		return false;
	}

    if (!is_date_in_fiscalyear($_POST['date_'])) {
		display_error(_("The Entered Date is OUT of FISCAL YEAR or is CLOSED for further data entry!"));
		set_focus('date_');
		return false;
	}

    if (get_post('cashier_') == '') {
        display_error(_("Please Select a Cashier!"));
        set_focus('cashier_');
        return false;
    }

    if (get_post('memo_') == '') {
        display_error(_("Please enter a memo for this transaction!"));
        set_focus('cashier_');
        return false;
    }

    return true;
}

//-----------------------------------------------------------------------------------------------
if (isset($_POST['Process']) && can_process()) {

    if (!isset($_POST['date_'])) {
        $_POST['date_'] = Today();
    }
    
    $trans_no = write_remit_transactions(
        get_post('ref'), 
        get_post('date_'),
        $_SESSION["wa_current_user"]->user,
        get_post('cashier_'),
        get_post('memo_')
    );

    if ($trans_no) {
        meta_forward($_SERVER['PHP_SELF'], "AddedID=$trans_no");
    }
}
//-----------------------------------------------------------------------------------------------

start_form();

start_table(TABLESTYLE2, "width = 30%");

table_section_title(_("Remittance Details:"));

ref_row(_("Transaction Reference: "), 'ref', '', 
	$Refs->get_next(ST_REMITTANCE, null, null), false, ST_REMITTANCE
);

date_row(_("Date:"), 'date_', '', true, 0, 0, 0, null, true);

sql_type_list(_("Remit To:"), 'cashier_', 
	allowed_dcpr_users(), 'id', 'real_name', 
	'label', null, true, 'Select Cashier'
);

end_table(1);

start_table(TABLESTYLE_NOBORDER);
start_row();

global $Ajax;
$Ajax->activate('remit_items');

end_row();
end_table(); 

$sql = get_bank_transactions(null, $_SESSION["wa_current_user"]->user, false);

$cols = array(
    _('Transaction Type') => array('align' => 'left', 'fun' => 'systype_name'),
    _('Entry Type') => array('align' => 'left', 'fun' => 'is_interbranch'),
    _('Trans #') => array('align' => 'left', 'fun' => 'trans_num'),
    _('Reference') => array('align' => 'center', 'fun' => 'reference_row'),
    _('Date') => array('align' => 'center', 'fun' => 'trans_date'),
    _('Disbursement #') => array('align' => 'center', 'fun' => 'doc_ref'),
    _('Payment Type') => array('align' => 'center', 'fun' => 'pay_type'),
    _('Document Total') => array('align' => 'right', 'type' => 'amount', 'fun' => 'amount_total'),
);

$table = &new_db_pager('remit_items', $sql, $cols, null, null, 25);

$table->width = "70%";

display_db_pager($table);

gl_options_controls();

br();
submit_center('Process', _("Process This Remittance Transaction"));
end_form();

//-----------------------------------------------------------------------------------------------

end_page();
