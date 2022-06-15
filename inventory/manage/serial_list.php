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
    $js .= get_js_open_window(900, 500);
}

page(_($help_context = "PNP Clearance Monitoring"), false, false, "", $js);
//--------------------------------------------------------------------------------------------------

function get_all_serial($search = '') {

    global $db_connections;
    $sql = "";
    for ($i = 0; $i < count($db_connections); $i++) {
        if (check_db_exists($i)) {
            $branch_code = $db_connections[$i]['branch_code'];
            $db_name = $db_connections[$i]['dbname'];

            $sql .= "SELECT '$branch_code' AS branch, 
                $branch_code.* 
            FROM $db_name.item_serialise $branch_code";

            if ($search != '') {
                $sql .= " 
                WHERE serialise_item_code LIKE " . db_escape('%' . trim($search) . '%') . " OR 
				serialise_lot_no LIKE " . db_escape('%' . trim($search) . '%') . " OR 
				serialise_chasis_no LIKE " . db_escape('%' . trim($search) . '%');
            }

            if ($i < count($db_connections) - 1) {
                $sql .= " UNION ALL "; 
            }
        }
    }

    return db_query($sql, _("get_all_serial()"));
}

//--------------------------------------------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);
start_row();

ref_cells(_("Search Here: &nbsp;"), 'searchval', '', null, '', true);

end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

global $Ajax;
$Ajax->activate('item_tbl');

end_row();
end_table(); 

$res_details = get_all_serial(get_post('searchval'));

div_start('item_tbl');
start_table(TABLESTYLE, "width='98%'");

$th = array(
    _("Origin Branch"),
    _("Category"),
    _("Item Code"),
    _("Item Description"),
    _("Serial/Engine Number"),
    _("Chassis Number")
);

table_header($th);

$k = 0;

while ($row = db_fetch_assoc($res_details)) {
    alt_table_row_color($k);

    $stock_row = db_fetch_assoc(get_stock_by_itemCode($row['serialise_item_code']));

    label_cell(get_company_value(get_comp_id($row['branch']), 'name'));
    label_cell(get_category_name($stock_row['category_id']));
    label_cell($stock_row['stock_id']);
    label_cell($stock_row['description']);
    label_cell($row['serialise_lot_no']);
    label_cell($row['serialise_chasis_no']);
}


end_table();
div_end();

end_form();
//--------------------------------------------------------------------------------------------------
end_page();