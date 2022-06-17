<?php

/**
 * Added by: spyrax10
 * Date Added: 15 Jun 2022
*/

$page_security = 'SA_FORITEMCODE';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/inventory/includes/inventory_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows && $SysPrefs->use_popup_search) {
    $js .= get_js_open_window(1200, 500);
}

page(_($help_context = "PNP Clearance Monitoring"), false, false, "", $js);
//--------------------------------------------------------------------------------------------------

if (get_post('comp_id')) {
    $default_table_count = count_columns(0, 'item_serialise');
    $company_check = get_post('comp_id') != null 
        && $default_table_count == count_columns(get_post('comp_id'), 'item_serialise');
    
    if(!$company_check) {
        display_error(_("No rows return in Selected Branch! Displaying all data..."));
    }
}
//--------------------------------------------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);
start_row();

ref_cells(_("Search Here: &nbsp;"), 'searchval', '', null, '', true);
company_list_row(_('&nbsp; Origin Branch: '), 'comp_id', true, false, false);
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

global $Ajax;
$Ajax->activate('item_tbl');

end_row();
end_table(); 

$res_details = get_all_serial(
    get_post('searchval'), 
    get_post('comp_id'), null,
    get_post('cleared_id')
);

div_start('item_tbl');
start_table(TABLESTYLE, "width='99%'");

$th = array(
    _("ID"),
    _("Origin Branch"),
    _("Category"),
    _("Item Code"),
    _("Color | Description"),
    _("Serial/Engine Number"),
    _("Chassis Number"),
    _("PNP Cleared"),
    _("Note"),
    _("")
);

table_header($th);

$k = 0;

while ($row = db_fetch_assoc($res_details)) {
    alt_table_row_color($k);

    $stock_row = db_fetch_assoc(get_stock_by_itemCode($row['serialise_item_code']));
    $is_cleared = $row['cleared'] == 1 ? _("Yes") : _("No");

    label_cell($row['serialise_id']);
    label_cell(get_company_value(get_comp_id($row['branch']), 'name'));
    label_cell(get_category_name($stock_row['category_id']));
    label_cell($stock_row['stock_id']);
    label_cell($stock_row['color'] != '' ? $stock_row['color'] . " | " . 
        get_color_description($row['serialise_item_code'], $stock_row['stock_id']) : 
        get_color_description($row['serialise_item_code'], $stock_row['stock_id'])
    );
    label_cell($row['serialise_lot_no'], "nowrap");
    label_cell($row['serialise_chasis_no'], "nowrap");
    label_cell($is_cleared, "align='center'");
    label_cell($row['pnp_note'], "nowrap");
    label_cell(serial_update_cell(get_comp_id($row['branch']), $row['serialise_id']), "nowrap");
}


end_table();
div_end();

end_form();
//--------------------------------------------------------------------------------------------------
end_page();