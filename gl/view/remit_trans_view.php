<?php

/**
 * Created by: spyrax10
 * Date Created: 25 Mar 2022
 */

$page_security = 'SA_REMIT_VIEW';
$path_to_root = "../..";

include($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");

if (isset($_GET["trans_no"])) {
	$trans_no = $_GET["trans_no"];
}

if (isset($_GET["ref"])) {
	$reference = $_GET["ref"];
}

page(_($help_context = "Remittance Entry Viewer"), true);

//---------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

$res_head = db_fetch(db_query(get_remit_transactions($reference)));
$res_details = get_remit_transactions($reference, '', null, null, true);

echo "<center>";

display_heading(_("Remittance Entry ") . " #$trans_no");

echo "<br>";
start_table(TABLESTYLE, "width='80%'");
start_row();

label_cells(_("Reference: "), $res_head['remit_ref'], "class='tableheader2'");
label_cells(_("Remittance Date: "), sql2date($res_head['remit_date']), "class='tableheader2'");
label_cells(_("Remittance From: "), get_user_name($res_head['remit_from']), "class='tableheader2'");

end_row();

comments_display_row(ST_REMITTANCE, $trans_no);

end_table();

start_table(TABLESTYLE, "width='80%'");

$th = array(
    _('Transaction Type'),
    _('Transaction #'),
    _('Reference'),
    _('Date'),
    _('Receipt No.'),
    _('Prepared By'),
    _('Payment Type'),
    _('Total Amount')
);

table_header($th);

$total = 0;
$k = 0;

while ($row = db_fetch_assoc($res_details)) {

    $bank_row = db_query(get_banking_transactions($row['type'], $row['from_ref'], '', null, null, $row['remit_from'], '', ''));

    alt_table_row_color($k);
    $total += $row['amount'];

    label_cell(_systype_name($row['type']));
    label_cell($bank_row['trans_no']);
    label_cell($row['from_ref'], "nowrap align='center'");
    label_cell(sql2date($row['trans_date']), "nowrap align='center'");
    label_cell($bank_row['receipt_no'], "nowrap align='center'");
    label_cell($bank_row['prepared_by']);
    label_cell($bank_row['pay_type']);
    label_cell($row['amount'], "nowrap align='right'");
}

end_table();

end_form();
end_page(true);