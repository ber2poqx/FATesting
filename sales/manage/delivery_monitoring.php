<?php
/**
 * Added by: spyrax10
 * Date Added: 23 Sep 2022
*/

$page_security = 'SA_FIX_DEL';
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
function get_transactions($trans_no = '', $category_id = '') {

    set_global_connection();
    
    $ret_arr = array();

    $sql = "SELECT DT.order_, DTT.debtor_trans_no AS dtt_no, DTT.debtor_trans_type AS dtt_type, 
	    SUM(DTT.standard_cost * quantity) AS del_cost,
        DT.trans_no AS dt_no, DT.type AS dt_type, DT.debtor_no AS dt_debtor, DM.name,
        DT.tran_date AS dt_date, SO.category_id
    FROM " . TB_PREF . "debtor_trans_details DTT
	    LEFT JOIN " . TB_PREF . "debtor_trans DT ON DTT.debtor_trans_no = DT.trans_no 
		    AND DTT.debtor_trans_type = DT.type
        LEFT JOIN " . TB_PREF . "debtors_master DM ON DT.debtor_no = DM.debtor_no
        LEFT JOIN " . TB_PREF . "sales_orders SO ON DT.order_ = SO.order_no
    WHERE DTT.debtor_trans_type = " . ST_CUSTDELIVERY . "";

    if ($trans_no != '') {
        $sql .= " AND DTT.debtor_trans_no = " .db_escape($trans_no);
    }

    if ($category_id != '') {
        $sql .= " AND SO.category_id = " .db_escape($category_id);
    }

    $sql .= " GROUP BY DTT.debtor_trans_no, DTT.debtor_trans_type";
    $main = db_query($sql, "del_monitoring(main)");

    return $main;
}

function fix_delivery_gl($trans_no) {

    $del_gl = clear_gl_trans(ST_CUSTDELIVERY, $trans_no);

    if ($del_gl) {
        $si_details = get_SI_details($trans_no);
        $del_row = db_fetch(get_transactions($trans_no));

        while ($data = db_fetch_assoc($si_details)) {
            $stock_gl_code = get_stock_gl_code($data['stock_id']);
            $del_cost = $data['quantity'] * $data['standard_cost'];

            add_gl_trans_std_cost(
                ST_CUSTDELIVERY,
                $trans_no,
                sql2date($del_row['dt_date']),
                get_stock_catID($data['stock_id']) == 17 ? getCompDet('cos_free_item') : $stock_gl_code["cogs_account"],
                0, 0,
                $data['stock_id'],
                $del_cost,
                PT_CUSTOMER,
                $del_row['dt_debtor'],
                "The cost of sales GL posting could not be inserted"
            );

            add_gl_trans_std_cost(
                ST_CUSTDELIVERY,
                $trans_no,
                sql2date($del_row['dt_date']),
                $stock_gl_code["inventory_account"],
                0, 0,
                $data['stock_id'],
                -$del_cost,
                PT_CUSTOMER,
                $del_row['dt_debtor'],
                "The cost of sales GL posting could not be inserted"
            );
        }

        return $trans_no;
    }
}

//----------------------------------------------------------------------
if (get_post('trans')) {
    $count = 0;
    foreach(get_post('trans') as $key => $val) {
        if ($val != "Select Action") {
            fix_delivery_gl($key);
            $count++;
        }
    }
    if ($count > 0) {
        display_notification(_("Transaction Successfully Fixed!"));
        $Ajax->activate('del_tbl');
    }
}

//----------------------------------------------------------------------
start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);
start_row();

sql_type_list(_("Select Category: "), 'category', 
	get_category_list(), 'category_id', 'description', 
	'', null, true, _("All Categories"), false, true
);

end_row();
end_table(); 


start_table(TABLESTYLE_NOBORDER);
start_row();

$Ajax->activate('del_tbl');

end_row();
end_table(); 


$result = get_transactions('', get_post('category'));

div_start('del_tbl');
start_table(TABLESTYLE, "width='70%'");

$th = array(
    _("SO #"),
    _("Customer"),
    _("Delivery Date"),
    _("Category"),
    _("Delivery #"),
    _("Unit Cost Total"),
    _("Inventory Total"),
    _("GL Total"),
    _(""), _("")
);

table_header($th);

$k = $gl_total = $del_cost = $invty_ = 0;
$delivery_total = $gl_ = $inty_total = 0;

while ($data = db_fetch_assoc($result)) {
    alt_table_row_color($k);

    $del_cost = price_format($data['del_cost']);
    $gl_total = price_format(get_gl_total($data['dtt_type'], $data['dtt_no']));
    $invty_ = price_format(get_SMO_total($data['dtt_type'], $data['dtt_no']));
    $status = $del_cost == $gl_total ? 0 : 1;

    if ($status == 1) {
        label_cell(get_customer_trans_view_str(ST_SALESORDER, $data['order_']));
        label_cell($data['name']);
        label_cell(phil_short_date($data['dt_date']), "nowrap align='center'; style='color: blue'");
        label_cell(get_category_name($data['category_id']), "align='center'");
        label_cell(get_trans_view_str($data['dtt_type'], $data['dtt_no']), "align='right'");
        label_cell($del_cost, "align='right'");
        label_cell($invty_, "align='right'");
        label_cell($gl_total, "align='right'");
        label_cell(get_gl_view_str($data['dtt_type'], $data['dtt_no']));

        if ($del_cost == $invty_) {
            label_cell(
                value_type_list(null, "trans[" . $data['dtt_no'] . "]", 
                    array(
                        "DEFAULT" => "Select Action",
                        1 => "Fix This Transaction",
                    ), '', null, true
                )
            );
        }
        else {
            label_cell(_("Subject For ReEntry"), "style='color: red'");
        }

        $delivery_total += price_format($del_cost);
        $gl_ += price_format($gl_total);
        $inty_total += price_format($invty_);
    }
}

label_row(_("Delivery Unit Cost Total: "), price_format($delivery_total),
	"align=right colspan=5; style='font-weight:bold';", "style='font-weight:bold'; align=right", 0
);

label_row(_("Inventory Total: "), price_format($inty_total),
	"align=right colspan=6; style='font-weight:bold';", "style='font-weight:bold'; align=right", 0
);

label_row(_("GL Total: "), price_format($gl_), 
	"align=right colspan=7; style='font-weight:bold';", "style='font-weight:bold'; align=right", 0
);

end_table();
div_end();

//----------------------------------------------------------------------

end_form();
end_page();
