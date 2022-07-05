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

$js = '';

if ($SysPrefs->use_popup_windows) {
    $js .= get_js_open_window(1000, 500);
}
if (user_use_date_picker()) {
    $js .= get_js_date_picker();
}

$page_title = "";

if (isset($_GET['trans_no'])) {
	$trans_no = $_GET['trans_no'];
    $page_title = _("Review Remittance Transaction #" . $trans_no);
}

if (isset($_GET['reference'])) {
	$reference = $_GET['reference'];
}

if (isset($_GET['AddedID'])) {
    $trans_no = $_GET['AddedID'];
    $page_title = _("Posted Remittance Transaction #" . $trans_no);
}

page($page_title, false, false, "", $js);

//-----------------------------------------------------------------------------------

if (isset($_GET['AddedID'])) {
    $trans_no = $_GET['AddedID'];
    $reference = $_GET['Ref'];

    display_notification_centered(_("Remittance Entry has been processed!"));
    display_note(get_trans_view_str(ST_REMITTANCE, $trans_no, _("&View this Remittance Entry"), false, '', '', false, $reference));
    br();
    display_note(get_gl_view_str(ST_REMITTANCE, $trans_no, _("&View the GL Postings for this Remittance transaction"), false, '', '', 1));
    
	hyperlink_params($_SERVER['PHP_SELF'], _("Enter &Another Remittance Entry"), "");
    hyperlink_params("$path_to_root/gl/inquiry/remittance_list.php", _("Back to Remittance Entry Inquiry List"), "");

    display_footer_exit();
}

//-----------------------------------------------------------------------------------

function can_proceed() {

	if (!is_date_in_fiscalyear($_POST['date_'])) {
        display_error(_("The Entered Date is OUT of FISCAL YEAR or is CLOSED for further data entry!"));
		return false;
    }

    if (!allowed_posting_date($_POST['date_'])) {
		display_error(_("The Entered Date is currently LOCKED for further data entry!"));
		return false;
	}

    if (get_post('memo_') == '') {
        display_error(_("Please Enter Remarks!"));
        set_focus('memo_');
        return false;
    }

    return true;
}

//-----------------------------------------------------------------------------------

if (isset($_POST['Approved']) && can_proceed()) {

    $res_head = db_fetch(db_query(get_remit_transactions($reference)));
    $res_details = get_remit_transactions($reference, '', null, null, true);
    $total = 0;

    if (remit_status($reference) == 'Draft') {
        update_remit_trans('Approved', $_GET['reference'], get_post('memo_'));

        while ($row = db_fetch_assoc($res_details)) {

            $total += $row['amount'];

            remit_bank_trans(_('Approved'), $row['from_ref'], 
                $res_head['remit_from'], $_SESSION["wa_current_user"]->user
            );
        }

        add_gl_trans(
            ST_REMITTANCE,
            $res_head['remit_num'],
            $_POST['date_'],
            1050,
            0, 0, 
            $_POST['memo_'],
            $total,
            null,
            PT_EMPLOYEE,
            $_SESSION["wa_current_user"]->user,
            _("Cant add gl_trans! (Remittance Entry)"),
            0,
            $_SESSION["wa_current_user"]->user,
            get_user_name($_SESSION["wa_current_user"]->user)
        );

        add_gl_trans(
            ST_REMITTANCE,
            $res_head['remit_num'],
            $_POST['date_'],
            1050,
            0, 0, 
            $_POST['memo_'],
            -$total,
            null,
            PT_EMPLOYEE,
            $res_head['remit_from'],
            _("Cant add gl_trans! (Remittance Entry)"),
            0,
            $res_head['remit_from'],
            get_user_name($res_head['remit_from'])
        );

        add_comments(ST_REMITTANCE, $trans_no, $_POST['date_'], $memo_);
        $Refs->save(ST_REMITTANCE, $trans_no, $reference);
	    add_audit_trail(ST_REMITTANCE, $trans_no, $_POST['date_'], _("Draft ref: " . $reference));

        meta_forward($_SERVER['PHP_SELF'], "AddedID=". $trans_no . "&Ref=" . $reference);
    }
    else {
		display_error(_("Invalid Transaction!"));
		die();
	}
}

if (isset($_POST['Disapproved']) && can_proceed()) {

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

start_table(TABLESTYLE);
start_row();
date_row(_("Posting Date:"), 'date_', '', true, 0, 0, 0, null, true);
end_row();
end_table();

echo "<center>";

echo "<br>";

start_table(TABLESTYLE, "width='95%'");
start_row();

label_cells(_("Reference: "), $res_head['remit_ref'], "class='tableheader2'");
label_cells(_("Remittance Date: "), phil_short_date($res_head['remit_date']), "class='tableheader2'");
label_cells(_("Remittance From: "), get_user_name($res_head['remit_from']), "class='tableheader2'");

end_row();

start_row();
comments_display_row(ST_REMITTANCE, $trans_no, 4);
label_cells(_("Remittance Status: "), $res_head['remit_stat'], "class='tableheader2'", "colspan=4");
end_row();

end_table();

br();

start_table(TABLESTYLE, "width='95%'");

$th = array(
    _(""),
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
$k = $count = 0;

while ($row = db_fetch_assoc($res_details)) {

    $count++;
    $cashier = $row['remit_stat'] == 'Approved' ? $row['remit_to'] : $row['remit_from'];

    $bank_ = db_query(get_banking_transactions($row['type'], $row['from_ref'], '', null, null, $cashier, '', ''));
    $bank_row = db_fetch_assoc($bank_);

    alt_table_row_color($k);
    $total += $row['amount'];
    $color = $row['amount'] > 0 ? "" : "style='color: red'";

    label_cell($count . ".)", "nowrap align='left'");
    label_cell(_systype_name($row['type']), "nowrap align='left'");
    //label_cell($bank_row['trans_no']);
    label_cell(get_trans_view_str($row["type"], $bank_row["trans_no"], $bank_row['ref']), "nowrap align='center'");
    label_cell(payment_person_name($bank_row['person_type_id'], $bank_row['person_id']), "nowrap align='left'");
    label_cell(phil_short_date($row['trans_date']), "nowrap align='center'; style='color: blue';");
    label_cell($bank_row['receipt_no'], "nowrap align='center'");
    label_cell($bank_row['prepared_by']);
    label_cell($bank_row['pay_type'], "nowrap align='center'");
    amount_cell($row['amount'], false, $color);
}

label_row(_("Document Total: "), number_format2($total, user_price_dec()), 
    "align=right colspan=8; style='font-weight:bold';", "style='font-weight:bold'; align=right", 0
);

end_table();

br();

start_table();
textarea_row(_("Remarks: "), 'memo_', null, 50, 3);
end_table();

br();

display_note(_("Approving this Remittance Entry will automatically transfer to you the listed Transaction/s from: " . get_user_name($res_head['remit_from'])), 
    0, 1, "class='currentfg'"
);

br();

if ($_GET['status'] == 0) {
    submit_center_first('Approved', _("Approved"), '', 'default');
    submit_center_last('Disapproved', _("Disapproved"), '', 'default', ICON_DELETE);
}

br();
end_form();

end_page();