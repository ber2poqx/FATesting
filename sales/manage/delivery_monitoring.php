<?php
/**
 * Added by: spyrax10
 * Date Added: 23 Sep 2022
*/

$page_security = 'SA_SALESDELIVERY';
$path_to_root = "../..";

include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/sales/includes/cart_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

$js = '';

if ($SysPrefs->use_popup_windows) {
    $js .= get_js_open_window(1100, 600);
}
if (user_use_date_picker()) {
    $js .= get_js_date_picker();
}

page(_($help_context = "Delivery Discrepancy Monitoring"), false, false, '', $js);
//----------------------------------------------------------------------
function get_transactions() {
    set_global_connection();
    
    $ret_arr = array();

    $sql = "SELECT DT.order_, DTT.debtor_trans_no AS dtt_no, DTT.debtor_trans_type AS dtt_type, 
	    SUM(DTT.standard_cost * quantity) AS del_cost,
        DT.trans_no AS dt_no, DT.type AS dt_type, DT.debtor_no AS dt_debtor, DM.name,
        DT.tran_date AS dt_date
    FROM " . TB_PREF . "debtor_trans_details DTT
	    LEFT JOIN " . TB_PREF . "debtor_trans DT ON DTT.debtor_trans_no = DT.trans_no 
		    AND DTT.debtor_trans_type = DT.type
        LEFT JOIN " . TB_PREF . "debtors_master DM ON DT.debtor_no = DM.debtor_no
    WHERE DTT.debtor_trans_type = " . ST_CUSTDELIVERY . " 
    GROUP BY DTT.debtor_trans_no, DTT.debtor_trans_type";

    $main = db_query($sql, "del_monitoring(main)");

    return $main;
}

//----------------------------------------------------------------------
start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);
start_row();

$Ajax->activate('del_tbl');

end_row();
end_table(); 


$result = get_transactions();

div_start('del_tbl');
start_table(TABLESTYLE, "width='45%'");

$th = array(
    _("SO #"),
    _("Customer"),
    _("Delivery Date"),
    _("Delivery #"),
    _("Delivery Total"),
    _("GL Total"),
    _("")
);

table_header($th);

$k = $gl_total = $del_cost = 0;
$delivery_total = $gl_ = 0;

while ($data = db_fetch_assoc($result)) {
    alt_table_row_color($k);

    $del_cost = price_format($data['del_cost']);
    $gl_total = price_format(get_gl_total($data['dtt_type'], $data['dtt_no']));
    $status = $del_cost == $gl_total ? 0 : 1;

    if ($status == 1) {
        label_cell(get_customer_trans_view_str(ST_SALESORDER, $data['order_']));
        label_cell($data['name']);
        label_cell(phil_short_date($data['dt_date']), "align='center'");
        label_cell(get_trans_view_str($data['dtt_type'], $data['dtt_no']), "align='right'");
        label_cell($del_cost, "align='right'");
        label_cell($gl_total, "align='right'");
        label_cell(get_gl_view_str($data['dtt_type'], $data['dtt_no']));
        $delivery_total += price_format($del_cost);
        $gl_ += price_format($gl_total);
    }
}

label_row(_("Delivery Total Cost: "), price_format($delivery_total),
	"align=right colspan=4; style='font-weight:bold';", "style='font-weight:bold'; align=right", 0
);

label_row(_("GL Total: "), price_format($gl_), 
	"align=right colspan=5; style='font-weight:bold';", "style='font-weight:bold'; align=right", 0
);

end_table();
div_end();

//----------------------------------------------------------------------

end_form();
end_page();
