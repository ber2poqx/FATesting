<?php

/**
 * Added by: spyrax10
 * Date Added: 15 Jun 2022
*/

$page_security = 'SA_SERIAL_LIST';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/inventory/includes/inventory_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows && $SysPrefs->use_popup_search) {
    $js .= get_js_open_window(1200, 300);
}

page(_($help_context = "PNP Clearance Monitoring"), false, false, "", $js);
//--------------------------------------------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);
start_row();

ref_cells(_("Search Here: &nbsp;"), 'searchval', '', null, '', true);
company_list_row(_('&nbsp; Origin Branch: '), 'comp_id', true, true, false);
value_type_list(_("&nbsp; Clearance Status: "), 'cleared_id', 
    array(
        'ALL' => 'All Clearance Status',
        1 => 'Cleared',
        0 => 'Not Cleared'
    ), '', null, true, '', true
);

end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

check_cells(_('Show Complete Transaction:'), 'show_all', null, true);

end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

$Ajax->activate('item_tbl');

end_row();
end_table(); 

$res_details = get_serial_list(
    get_post('searchval'), 
    get_post('comp_id'), null,
    get_post('cleared_id'),
    get_post('show_all')
);

div_start('item_tbl');
start_table(TABLESTYLE, "width='99%'");

$th = array(
    _("ID"),
    _("Origin Branch"),
    _("Reference"),
    _("Category"),
    check_value('show_all') == 1 ? _("Transaction Date") : _("Date Registered"),
    _("Item Code | Color | Color Description"),
    _("Serial/Engine Number"),
    _("Chassis Number"),
    _("PNP Cleared"),
    _("Note"),
    _("")
);


if (check_value('show_all') == 1) {
    array_splice($th, 8, 0, 'QoH');
}

table_header($th);

$k = 0;

$serial_no = '';

foreach ($res_details as $value => $data) {

    if ($data['qoh'] < 0 && check_value('show_all') == 1) {
        start_row("class='overduebg'");
    }
    else {
        alt_table_row_color($k);
    }

    $stock_row = db_fetch_assoc(get_stock_by_itemCode($data['serialise_item_code']));
    $is_cleared = $data['cleared'] == 1 ? _("Yes") : _("No");
   
    label_cell($data['trans_id']);
    label_cell(get_company_value(get_comp_id($data['branch']), 'name'), "align='left'");
    label_cell($data['reference'], "nowrap align='center'; style='color: blue'");
    label_cell(get_category_name($data['category_id']), "nowrap align='center'");
    label_cell(phil_short_date($data['trans_date']), "nowrap align='center'; style='color: blue'");
    label_cell($stock_row['color'] != '' ? 
        $data['stock_id'] . " | ".  $stock_row['color'] . " | " . get_color_description($data['serialise_item_code'], $data['stock_id']) 
        : $data['stock_id']

    );

    label_cell($data['serialise_lot_no'], "nowrap");
    label_cell($data['serialise_chasis_no'], "nowrap");
    
    if (check_value('show_all') == 1) {
        label_cell($data['qoh'], "nowrap align='center'");
    }
    label_cell($is_cleared, "align='center'");
    label_cell($data['pnp_note']);

    if ($serial_no != $data['serialise_lot_no']) {
        if ($data['cleared'] != null) {
            label_cell(serial_update_cell(get_comp_id($data['branch']), $data['serialise_id'], $data['serialise_lot_no']), "nowrap");
        }
        else {
            label_cell("N/A", "nowrap align='center'");
        }
        $serial_no = $data['serialise_lot_no'];
    }
}

end_table();
div_end();

start_table(TABLESTYLE_NOBORDER);
start_row();

display_note(_("Marked Rows are OUT Transactions"), 0, 0, "class='overduefg'");

end_row();
end_table();

br();

end_form();
//--------------------------------------------------------------------------------------------------
end_page();