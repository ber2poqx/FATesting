<?php
/**
 * Added by: spyrax10
 * Date Added: 14 Sep 2022
*/

$page_security = 'SA_SALESDELIVERY';
$path_to_root = "../..";

include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/sales/includes/cart_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/taxes/tax_calc.inc");

$js = '';

if ($SysPrefs->use_popup_windows) {
    $js .= get_js_open_window(1100, 600);
}
if (user_use_date_picker()) {
    $js .= get_js_date_picker();
}

page(_($help_context = "Pending Delivery Inquiry List"), false, false, '', $js);

//----------------------------------------------------------------------

function get_pending_deliveries($order_no = '', $pay_type = '') {
    
    set_global_connection();

    $sql = "SELECT SO.order_no, SO.reference AS so_ref, DT.reference AS dt_ref, 
        DM.name, CASE WHEN SO.months_term > 0 THEN 'INSTALLMENT' ELSE 'CASH' END AS pay_type, 
        SO.ord_date, SO.total AS order_total, SO.trans_type AS so_type, 
        DT.type AS del_type, DT.trans_no AS dt_no
    FROM " . TB_PREF . "sales_orders SO
        LEFT JOIN " . TB_PREF . "sales_order_details SOD ON SO.order_no = SOD.order_no
        LEFT JOIN " . TB_PREF . "debtor_trans DT ON SO.order_no = DT.order_ AND DT.type = 13
        LEFT JOIN " . TB_PREF . "debtors_master DM ON SO.debtor_no = DM.debtor_no
    WHERE SOD.qty_sent = 0 AND SO.status = 'Closed'";

    if ($order_no != '') {
        $sql .= " AND SOD.order_no = " .db_escape($order_no);
    }

    if ($pay_type != '') {
        $sql .= " AND (CASE WHEN SO.months_term > 0 THEN 'INSTALLMENT' ELSE 'CASH' END) = " .db_escape($pay_type);
    }
    
    $sql .= " GROUP BY SO.order_no";
    return $sql;
}

//----------------------------------------------------------------------
function soref_view($row) {
    return get_trans_view_str($row["so_type"], $row["order_no"], $row['so_ref']);
}

function delref_view($row) {
    return get_trans_view_str($row["del_type"], $row["dt_no"], $row['dt_ref']);
}

function so_date($row) {
    return phil_short_date($row['ord_date']);
}

function edit_row($row) {
    $del_link = '';

    if ($_SESSION["wa_current_user"]->can_access_page('SA_SALESDELIVERY')) {
        $del_link = trans_editor_link2($row['so_type'], $row['order_no'], $row['pay_type'], 1);
    }
    else {
        $del_link = '';
    }

    return $del_link;
}

//----------------------------------------------------------------------
start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);
start_row();

ref_cells(_("SO #:"), 'so_no', '', null, '', true);

value_type_list(_("&nbsp; Payment Type: "), 'pay_type', 
    array(
        "INSTALLMENT" => 'Installment',
        "CASH" => 'Cash'
    ), '', null, true, _("All Payment Types"), true
);

end_row();
end_table();

if ($_SESSION["wa_current_user"]->can_access_page('SA_SALESDELIVERY')) {
    start_table(TABLESTYLE_NOBORDER);
    start_row();
    ahref_cell(_("Enter New Direct Sales Delivery"), "../sales_order_entry.php?NewDelivery=0", "SA_SALESDELIVERY");
    end_row();
    end_table();
}

start_table(TABLESTYLE_NOBORDER);
start_row();

global $Ajax;
$Ajax->activate('del_items');

end_row();
end_table(); 

$sql = get_pending_deliveries(get_post('so_no'), get_post('pay_type'));


$cols = array(
    _('Order #'),
    _('Order Ref') => array('align' => 'left', 'fun' => 'soref_view'),
    _('Delivery Ref') => array('align' => 'left', 'fun' => 'delref_view'),
    _('Customer'),
    _('Payment Type') => array('align' => 'center'),
    _("Order Date") => array('align' => 'center', 'fun' => 'so_date'),
    _('Order Total') => array('type' => 'amount'),
    array('insert' => true, 'fun' => 'edit_row', 'align' => 'center')
);

$table = &new_db_pager('del_items', $sql, $cols, null, null, 25);
$table->width = "70%";

display_db_pager($table);


//----------------------------------------------------------------------
end_form();
end_page();

