<?php

/**
 * Created by: spyrax10
 * Date Created: 21 Mar 2022
 */

$page_security = 'SA_DISBURSE_LIST';
$path_to_root = "../..";

include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/admin/db/fiscalyears_db.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");

$js = '';

if ($SysPrefs->use_popup_windows) {
    $js .= get_js_open_window(800, 500);
}
if (user_use_date_picker()) {
    $js .= get_js_date_picker();
}

page(_($help_context = "Disbursement Entry Inquiry List"), false, false, '', $js);

//---------------------------------------------------------------

function trans_num($row) {
    return $row['trans_no'];
}

function reference_row($row) {
    return get_trans_view_str($row["type"], $row["trans_no"], $row['ref']);
}

function trans_date($row) {
    return phil_short_date($row['trans_date']);
}

function cashier_name($row) {
    return get_user_name($row['cashier_user_id']);
}

function preparer_name($row) {
    return $row['prepared_by'];
}

function pay_type($row) {
    return strtoupper($row['pay_type']);
}

function is_interbranch($row) {
    return has_interbranch_entry($row['trans_no'], ST_BANKPAYMENT) ? "Interbranch Entry" : "Normal Entry";
}

function doc_ref($row) {
    return $row['receipt_no'];
}

function amount_total($row) {
    return abs($row['amount']);
}

function gl_view($row) {
	return get_gl_view_str(ST_BANKPAYMENT, $row["trans_no"], '', false, '', '', 1);
}

//Added by Prog6(03/31/2022)
function print_voucher($row)
{
	return pager_link(
		_("Print: Disbursement Voucher"),
		"/reports/disbursement_voucher.php?trans_num=" . $row["trans_no"],
		ICON_PRINT
	);
}

//---------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);
start_row();

ref_cells(_("Reference #:"), 'reference', '', null, '', true);
ref_cells(_("Disbursement #:"), 'doc_ref', '', null, '', true);

sql_type_list(_("Cashier / Teller: "), 'cashier', 
	get_dcpr_users(), 
	'cashier_user_id', 'real_name', '', null, true, 
	_("All Cashiers")
);

value_type_list(_("Entry Type: "), 'interbranch', 
    array(
        1 => 'Interbranch Entry', 
        0 => 'Normal Entry' 
    ), '', null, true, _('All Entry Types'), true
);
end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

date_cells(_("From:"), 'from_date', '', null, -user_transaction_days());
date_cells(_("To:"), 'to_date');

submit_cells('btn_search', _("Search"),'',_('Search documents'), 'default');

end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();
ahref_cell(_("Enter New Disbursement Entry"), "../gl_bank.php?NewPayment=Yes", "SA_PAYMENT");
end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

global $Ajax;
$Ajax->activate('bank_items');

end_row();
end_table(); 

$sql = get_banking_transactions(ST_BANKPAYMENT, 
    get_post('reference'),
    get_post('doc_ref'), 
    get_post('from_date'), 
    get_post('to_date'),
    get_post('cashier'),
    get_post('interbranch'),
    ''
);

$cols = array(
    _('Entry Type') => array('align' => 'left', 'fun' => 'is_interbranch'),
    _('Trans #') => array('align' => 'left', 'fun' => 'trans_num'),
    _('Reference') => array('align' => 'center', 'fun' => 'reference_row'),
    _('Date') => array('align' => 'center', 'fun' => 'trans_date'),
    _('Disbursement #') => array('align' => 'center', 'fun' => 'doc_ref'),
    _('Cashier / Teller') => array('align' => 'center', 'fun' => 'cashier_name'),
    _('Prepared By') => array('align' => 'center', 'fun' => 'preparer_name'),
    _('Payment Type') => array('align' => 'center', 'fun' => 'pay_type'),
    _('Document Total') => array('align' => 'right', 'type' => 'amount', 'fun' => 'amount_total'),
    array('insert' => true, 'fun' => 'gl_view', 'align' => 'center'),
	array('insert'=>true, 'fun'=>'print_voucher') //Added by Prog6(03/31/2022)
);

$table = &new_db_pager('bank_items', $sql, $cols, null, null, 25);

$table->width = "80%";

display_db_pager($table);

end_form();
end_page();