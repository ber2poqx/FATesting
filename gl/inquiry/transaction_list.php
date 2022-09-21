<?php

/**
 * Added by: spyrax10
 * Date Added: 16 Sep 2022
*/

$page_security = "SA_SEARCHTRANS";
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/gl/includes/db/gl_db_banking.inc");
include_once($path_to_root . "/admin/db/transactions_db.inc");
include_once($path_to_root . "/admin/db/voiding_db.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows) {
    $js .= get_js_open_window(1000, 500);
}

$js .= get_js_set_combo_item();

page(_($help_context = "List of Transaction References"), true, false, "", $js);
#--------------------------------------------------------------------------------
if (get_post('trans_type')) {
    $Ajax->activate('ref_tbl');
}

#--------------------------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);
global $systypes_array;

start_table(TABLESTYLE_NOBORDER);

start_row();

value_type_list(null, 'trans_type', 
    array(
        99 => 'Journal Entries',
        ST_BANKPAYMENT => 'Disbursement Entries',
        ST_BANKDEPOSIT => 'Receipts Entries',
        ST_SALESINVOICE => 'Sales Invoice Entries',
        ST_INVADJUST => 'Inventory Adjustment Entries',
        ST_COMPLIMENTARYITEM => 'Complimentary Entries'
    ), '', null, true, _("All Transaction Types"), true
);

ref_cells(_("Transaction Reference: &nbsp;"), 'ref', '', null, '', true);

end_row();

end_table();

end_form();

div_start("ref_tbl");

start_table(TABLESTYLE);

$th = array("", _("#"), _("Type"), _("Transaction Date"), _("Reference"), _("Amount"), _(""));

table_header($th);

$k = 0;
$name = $_GET["client_id"];

$result = get_all_transactions(get_post('trans_type'), get_post('ref')); 

while ($row = db_fetch_assoc($result)) {
    alt_table_row_color($k);
	
    $value = $row['counter'];
    $text = $row['ref'];
    $reference = $row['reference'] != null ? $row['reference'] : $row['si_ref'];
  	
    ahref_cell(_("Select"), 'javascript:void(0)', '', 'setComboItem(window.opener.document, "'.$name.'",  "'.$value.'", "'.$text.'")');
    label_cell($row['type_no']);
    label_cell($systypes_array[$row['type']]);
    label_cell(phil_short_date($row['tran_date']), "nowrap align='center'; style='color: blue'");
    label_cell(get_trans_view_str($row['type'], $row['type_no'], $reference));
    amount_cell(ABS($row['amount']));
    label_cell(get_gl_view_str($row['type'], $row["type_no"]));

}
end_table(1);

end_page(true);
