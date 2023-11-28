<?php

$page_security = 'SA_DISBURMENT_UPDATE_STATUS';
$path_to_root = "../..";

include_once($path_to_root . "/includes/ui/items_cart.inc");
include($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/admin/db/fiscalyears_db.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
    $js .= get_js_open_window(900, 500);
if (user_use_date_picker())
    $js .= get_js_date_picker();
page(_($help_context = "Draft Disbursement Status # " . $_GET['DisburseNo']), false, false, "", $js);

if (isset($_GET['DisburseNo']) && is_numeric($_GET['DisburseNo'])) {
    $trans_no = $_GET['DisburseNo'];
    create_cart(ST_BANKPAYMENT,$_GET['DisburseNo']);
}
function create_cart($type, $trans_no, $void_id = 0) {
	global $Refs;

	if (isset($_SESSION['pay_items'])) {
		unset($_SESSION['pay_items']);
	}

	$cart = new items_cart($type);
	$cart->order_id = $trans_no;
    
    $bank_trans = db_fetch(get_bank_trans($type, $trans_no));
    $cart->reference = $bank_trans["ref"];

    $_POST['trans_no'] = $cart->order_id;
    $_POST['ref'] = $cart->reference;
	$_POST['date_'] = $cart->tran_date;
	$_SESSION['pay_items'] = &$cart;
}

function update_disbursement_status(&$bp_obj)
{
	begin_transaction();
	hook_db_prewrite($bp_obj, ST_BANKPAYMENT);

	/*Update the sales order draft status */
	$sql = "UPDATE " . TB_PREF . "bank_trans SET status=" . db_escape($bp_obj->status);
	$sql .= " WHERE type = ".ST_BANKPAYMENT." and  trans_no = " . $bp_obj->order_id;
	db_query($sql, "The sales order could not be updated");

	add_audit_trail(ST_BANKPAYMENT, $bp_obj->order_id, Today(), _("Update Status."));
	hook_db_postwrite($bp_obj, ST_BANKPAYMENT);
	commit_transaction();

	return "Successfully Approved!";
}


if (isset($_POST['Approved'])) {
    $_SESSION['pay_items']->status = "Approved";
    display_error($_GET['DisburseNo']);
    // $_SESSION['pay_items']->memo_ = $_POST['Comments'];
    $update_message = update_disbursement_status($_SESSION['pay_items']);
    
    meta_forward($path_to_root . "/gl/inquiry/disbursement_list.php?");
}

if (isset($_POST['Disapproved'])) {
    $_SESSION['pay_items']->status = "Disapproved";
	// $_SESSION['pay_items']->memo_ = $_POST['Comments'];
    $update_message = update_disbursement_status($_SESSION['pay_items']);
    meta_forward($path_to_root . "/gl/inquiry/disbursement_list.php?");
}



start_form();
$result = get_bank_trans(ST_BANKPAYMENT, $trans_no);

if (db_num_rows($result) != 1) {
	display_db_error("duplicate payment bank transaction found", "");
}

$from_trans = db_fetch($result);
$person_type = get_person_type($from_trans['person_type_id'], false);

$company_currency = get_company_currency();

$show_currencies = false;

if ($from_trans['bank_curr_code'] != $from_trans['settle_curr'])
{
	$show_currencies = true;
}

display_heading(_("Disbursement Voucher") . " #$trans_no");

echo "<br>";

start_table(TABLESTYLE, "width='95%'");

if ($show_currencies)
{
	$colspan1 = 1;
	$colspan2 = 7;
}
else
{
	$colspan1 = 3;
	$colspan2 = 5;
}
start_row();
label_cells(_("From Bank Account"), $from_trans['bank_account_name'], "class='tableheader2'");
if ($show_currencies)
	label_cells(_("Currency"), $from_trans['bank_curr_code'], "class='tableheader2'");
label_cells(_("Amount"), number_format2(-$from_trans['amount'], user_price_dec()), "class='tableheader2'", "align=right");
label_cells(_("Date"), phil_short_date($from_trans['trans_date']), "class='tableheader2'");
end_row();
start_row();
//Modified by spyrax10 22 Mar 2022
label_cells(_("Pay To"), payment_person_name($from_trans['person_type_id'], $from_trans['person_id']), "class='tableheader2'", "colspan=$colspan1");
//
if ($show_currencies)
{
	label_cells(_("Settle currency"), $from_trans['settle_curr'], "class='tableheader2'");
	label_cells(_("Settled amount"), number_format2($from_trans['settled_amount'], user_price_dec()), "class='tableheader2'");
}
label_cells(_("Payment Type"), $bank_transfer_types[$from_trans['account_type']], "class='tableheader2'");

end_row();
start_row();
label_cells(_("Reference"), $from_trans['ref'], "class='tableheader2'", "colspan=$colspan1");
//Added by spyrax10 10 Feb 2022
label_cells(_("Cashier / Teller: "), get_user_name($from_trans['cashier_user_id'], true), "class='tableheader2'");
//
end_row();

start_row();
label_cells(_("Disbursement no: &nbsp;"), $from_trans['receipt_no'], "class='tableheader2'");
label_cells(_("Pay To: &nbsp;"), 
	$person_type . get_person_name($from_trans['person_type_id'], $from_trans['person_id']), 
	"class='tableheader2'", "colspan=$colspan2"
);

end_row();

comments_display_row(ST_BANKPAYMENT, $trans_no);

end_table(1);

$voided = is_voided_display(ST_BANKPAYMENT, $trans_no, _("This payment has been voided."));

$items = get_gl_trans(ST_BANKPAYMENT, $trans_no);

if (db_num_rows($items) == 0) {
	display_note(_("There are no items for this payment."));
}
else {

	display_heading2(_("Items for this Payment"));
	if ($show_currencies)
		display_heading2(_("Item Amounts are Shown in:") . " " . $company_currency);

    echo "<br>";
    start_table(TABLESTYLE, "width='80%'");
    $dim = get_company_pref('use_dimension');
    if ($dim == 2)
        $th = array(_("Account Code"), _("Account Description"), _("Dimension")." 1", _("Dimension")." 2",
            _("Amount"), _("Memo"));
    elseif ($dim == 1)
        $th = array(_("Account Code"), _("Account Description"), _("Dimension"),
            _("Amount"), _("Memo"));
    else
        $th = array(_("Account Code"), _("Account Description"),
            _("Amount"), _("Memo"));
	table_header($th);

    $k = 0; //row colour counter
	$total_amount = 0;

    while ($item = db_fetch($items)) {
        display_error($item["account"]);
        display_error($from_trans["account_code"]);
		if ($item["account"] != $from_trans["account_code"])
		{
    		alt_table_row_color($k);

        	label_cell($item["account"]);
    		label_cell($item["account_name"]);
            if ($dim >= 1)
                label_cell(get_dimension_string($item['dimension_id'], true));
            if ($dim > 1)
                label_cell(get_dimension_string($item['dimension2_id'], true));
    		amount_cell($item["amount"]);
    		label_cell($item["memo_"]);
    		end_row();
    		$total_amount += $item["amount"];
		}
	}

	label_row(_("Total"), number_format2($total_amount, user_price_dec()),"colspan=".(2+$dim)." align=right", "align=right");

	end_table(1);

	if (!$voided)
		display_allocations_from($from_trans['person_type_id'], $from_trans['person_id'], 1, $trans_no, $from_trans['settled_amount']);
}
start_table(TABLESTYLE, "width='80%'");
    textarea_row(_("Remarks:"), 'Comments', null, 130, 4);
end_table(2);
    submit_center_first('Approved', _("Approved"), '', 'default');
    submit_center_last('Disapproved', _("Disapproved"), '', 'default', ICON_DELETE);
end_form();
end_page();