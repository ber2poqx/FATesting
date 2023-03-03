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
start_table(TABLESTYLE, "width='95%'");
start_row();

label_cells(_("Reference: "), $res_head['remit_ref'], "class='tableheader2'");
label_cells(_("Remittance Date: "), phil_short_date($res_head['remit_date']), "class='tableheader2'");
label_cells(_("Remittance Status: "), $res_head['remit_stat'], "class='tableheader2'", "colspan=4");
end_row();

start_row();
label_cells(_("Remittance From: "), get_user_name($res_head['remit_from']), "class='tableheader2'");
label_cells(_("Remitted To: "), get_user_name($res_head['remit_to']), "class='tableheader2'");
end_row();

comments_display_row(ST_REMITTANCE, $trans_no);

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
    _('Total Amount')
);

table_header($th);

$total = 0;
$k = $count = 0;

while ($row = db_fetch_assoc($res_details)) {

    $count++;
    $cashier = $row['remit_stat'] == 'Approved' ? $row['remit_to'] : $row['remit_from'];

    $bank_ = db_query(get_banking_transactions(null, '', '', null, null, '', '', '', 0, '','', $row['remit_num']));
    //$bank_row = db_fetch_assoc($bank_);
    $total += $row['amount'];
    while ($bank_row = db_fetch_assoc($bank_)) {
        alt_table_row_color($k);
        $color = $bank_row['amount'] > 0 ? "" : "style='color: red'";
        label_cell($count . ".)", "nowrap align='left'");
        label_cell(_systype_name($row['type']), "nowrap align='left'");
        //label_cell($bank_row['trans_no']);
        label_cell(get_trans_view_str($row["type"], $bank_row["trans_no"], ''), "nowrap align='center'");
        label_cell(payment_person_name($bank_row['person_type_id'], $bank_row['person_id']), "nowrap align='left'");
        label_cell(phil_short_date($row['remit_date']), "nowrap align='center'; style='color: blue';");
        label_cell($bank_row['receipt_no'], "nowrap align='center'");
        label_cell($bank_row['prepared_by'], "nowrap align='center'");
        label_cell($bank_row['pay_type'], "nowrap align='center'");
        amount_cell($bank_row['amount'], false, $color);
    }
}

label_row(_("Document Total: "), number_format2($total, user_price_dec()), 
    "align=right colspan=8; style='font-weight:bold';", "style='font-weight:bold'; align=right", 0
);

end_table();
br();
end_form();
end_page(true);