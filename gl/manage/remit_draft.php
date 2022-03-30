<?php

/**
 * Created by: spyrax10
 * Date Created: 29 Mar 2022
 */

 $path_to_root="../..";
 $page_security = 'SA_REMIT_DRAFT';

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

if (isset($_GET['reference'])) {
	$reference = $_GET['reference'];
}

page(_("Review Remittance Transaction #" . $trans_no), false, false, "", $js);

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

//-----------------------------------------------------------------------------------

if (isset($_POST['Approved']) && can_proceed(1)) {

    $res_details = get_remit_transactions($reference, '', null, null, true);

    if (remit_status($reference) == 'Draft') {
        update_remit_trans('Approved', $_GET['reference'], get_post('memo_'));

        while ($row = db_fetch_assoc($res_details)) {
            remit_bank_trans(_('Approved'), $row['from_ref']);
        }

        meta_forward("../inquiry/remittance_list.php?");
    }
    else {
		display_error(_("Invalid Transaction!"));
		die();
	}
}

if (isset($_POST['Disapproved']) && can_proceed(2)) {

    $res_details = get_remit_transactions($reference, '', null, null, true);
    
    if (remit_status($reference) == 'Draft') {
        update_remit_trans('Disapproved', $_GET['reference'], get_post('memo_'));

        while ($row = db_fetch_assoc($res_details)) {
            remit_bank_trans(_('Open'), $row['from_ref']);
        }

        meta_forward("../inquiry/remittance_list.php?");
    }
    else {
		display_error(_("Invalid Transaction!"));
		die();
	}
}

//-----------------------------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

$res_head = db_fetch(db_query(get_remit_transactions($reference)));
$res_details = get_remit_transactions($reference, '', null, null, true);

echo "<center>";

echo "<br>";
start_table(TABLESTYLE, "width='95%'");
start_row();

label_cells(_("Reference: "), $res_head['remit_ref'], "class='tableheader2'");
label_cells(_("Remittance Date: "), sql2date($res_head['remit_date']), "class='tableheader2'");
label_cells(_("Remittance From: "), get_user_name($res_head['remit_from']), "class='tableheader2'");

end_row();

comments_display_row(ST_REMITTANCE, $trans_no);

end_table();

start_table(TABLESTYLE, "width='95%'");

$th = array(
    _('Transaction Type'),
    //_('Transaction #'),
    _('Reference'),
    _('Payment To'),
    _('Date'),
    _('Receipt No.'),
    _('Prepared By'),
    _('Payment Type'),
    _('Sub Total')
);

table_header($th);

$total = 0;
$k = 0;

while ($row = db_fetch_assoc($res_details)) {

    $bank_ = db_query(get_banking_transactions($row['type'], $row['from_ref'], '', null, null, $row['remit_from'], '', ''));
    $bank_row = db_fetch_assoc($bank_);

    alt_table_row_color($k);
    $total += abs($row['amount']);

    label_cell(_systype_name($row['type']), "nowrap align='left'");
    //label_cell($bank_row['trans_no']);
    label_cell(get_trans_view_str($row["type"], $bank_row["trans_no"], $bank_row['ref']), "nowrap align='center'");
    label_cell(payment_person_name($bank_row['person_type_id'], $bank_row['person_id']), "nowrap align='left'");
    label_cell(sql2date($row['trans_date']), "nowrap align='center'");
    label_cell($bank_row['receipt_no'], "nowrap align='center'");
    label_cell($bank_row['prepared_by']);
    label_cell($bank_row['pay_type'], "nowrap align='center'");
    amount_cell(abs($row['amount']));
}

label_row(_("Document Total: "), number_format2($total, user_price_dec()), 
    "align=right colspan=7; style='font-weight:bold';", "style='font-weight:bold'; align=right", 0
);

end_table();

start_table();
textarea_row(_("Remarks: "), 'memo_', null, 50, 3);
end_table();


br();

if ($_GET['status'] == 0) {
    submit_center_first('Approved', _("Approved"), '', 'default');
    submit_center_last('Disapproved', _("Disapproved"), '', 'default', ICON_DELETE);
}

br();
end_form();

end_page();