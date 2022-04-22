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
    $js .= get_js_open_window(1100, 500);
}
if (user_use_date_picker()) {
    $js .= get_js_date_picker();
}

page(_("Remittance Entry"), false, false, '', $js);

//-----------------------------------------------------------------------------------------------

if (isset($_GET['AddedID'])) {

    $trans_no = $_GET['AddedID'];
    $reference = $_GET['Ref'];

    display_notification_centered(_("Remittance Entry has been processed!"));
    display_note(get_trans_view_str(ST_REMITTANCE, $trans_no, _("&View this Remittance Entry"), false, '', '', false, $reference));

	hyperlink_params($_SERVER['PHP_SELF'], _("Enter &Another Remittance Entry"), "");
    hyperlink_params("$path_to_root/gl/inquiry/remittance_list.php", _("Back to Remittance Entry Inquiry List"), "");

    display_footer_exit();
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

if (get_post('chk_date')) {
	$Ajax->activate('pmt_header');
    $Ajax->activate('remit_items');
}
else {
    $Ajax->activate('pmt_header');
    $Ajax->activate('remit_items');
}

if (check_value('chk_date') == 1) {
    $_POST['date_2'] = '';
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
        get_post('memo_'),
        get_post('trans_type'),
        get_post('date_2') == null || !isset($_POST['date_2']) ? '' : get_post('date_2')
    );

    if ($trans_no) {
        meta_forward($_SERVER['PHP_SELF'], "AddedID=". $trans_no . "&Ref=" . get_post('ref'));
    }
}

//-----------------------------------------------------------------------------------------------

start_form();

display_heading(_("Select Transaction/s to be Remitted:"));
br();

div_start('pmt_header');
start_outer_table(TABLESTYLE2, "width='40%'");

table_section(1);
check_row(_('All Transaction Date/s : '), 'chk_date', get_post('chk_date'), true);

if (check_value('chk_date') == 0) {

    date_row(_("Date:"), 'date_2', '', true, 0, 0, 0, null, true);
}

table_section(2);
if (check_value('chk_date') == 0) {
    label_row(null, ''); label_row(null, ''); label_row(null, ''); label_row(null, '');
}

value_type_list(_("Transaction Type:"), 'trans_type', 
    array(
        ST_BANKPAYMENT => 'Disbursement Entries', 
        ST_BANKDEPOSIT => 'Receipt Entries',
        ST_CUSTPAYMENT => 'Office Collection Receipt'
    ), 'label', null, true, _("All Transaction Types"), true
);

end_outer_table(1);
div_end();

start_table(TABLESTYLE_NOBORDER);
start_row();

global $Ajax;
$Ajax->activate('remit_items');

end_row();
end_table(); 

div_start("remit_items");
start_table(TABLESTYLE, "width='85%'");

$sql = db_query(
    _bank_transactions(
        null, 
        $_SESSION["wa_current_user"]->user, 
        false,
        get_post('trans_type'),
        get_post('date_2') == null ? '' : get_post('date_2')
    )
);

$th = array(
    _(""),
    _('Transaction Type'),
    _('Entry Type'),
    _('Trans #'),
    _('Reference'),
    _('Payment To'),
    _('Date'),
    _('Receipt No.'),
    _('Payment Type'),
    _('Document Total')
);

table_header($th);

$total = 0;
$k = $count = 0;

while ($row = db_fetch_assoc($sql)) {

    $count++;
    $total += $row['amt'];
    $color = $row['amt'] > 0 ? "" : "style='color: red'";

    alt_table_row_color($k);

    label_cell($count . ".)", "nowrap align='left'");
    label_cell(_systype_name($row['type']), "nowrap align='left'");
    label_cell(has_interbranch_entry($row['trans_no'], $row['type']) ? "Interbranch Entry" : "Normal Entry", "nowrap align='left'");
    label_cell($row['trans_no'], "nowrap align='center'");
    label_cell(get_trans_view_str($row["type"], $row["trans_no"], $row['ref']), "nowrap align='center'");
    label_cell(payment_person_name($row['person_type_id'], $row['person_id']), "nowrap align='left'");
    label_cell(sql2date($row['trans_date']), "nowrap align='center'");
    label_cell($row['receipt_no'], "nowrap align='center'");
    label_cell($row['pay_type'], "nowrap align='center'");
    amount_cell($row['amt'], false, $color);
}

label_row(_("Pending Remittance Total: "), number_format2($total, user_price_dec()), 
    "align=right colspan=9; style='font-weight:bold';", "style='font-weight:bold'; align=right", 0
);

end_table();
div_end();

br();
display_heading(_("New Remittance Entry"));
br();

start_outer_table(TABLESTYLE2, "width='50%'");

table_section(1);
ref_row(_("Transaction Reference: "), 'ref', '', 
    $Refs->get_next(ST_REMITTANCE, null, null), true, ST_REMITTANCE
);

table_section(2);
date_row(_("Date:"), 'date_', '', true, 0, 0, 0, null, true);

table_section(3);
sql_type_list(_("Remit To:"), 'cashier_', 
	allowed_dcpr_users(), 'id', 'real_name', 
	'label', null, true, 'Select Cashier'
);

end_outer_table(1);

gl_options_controls();

br();
submit_center('Process', _("Process This Remittance Transaction"));
end_form();

//-----------------------------------------------------------------------------------------------

end_page();
