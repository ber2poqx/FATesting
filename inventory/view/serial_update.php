<?php

/**
 * Added by: spyrax10
 * Date Added: 16 Jun 2022
*/

$page_security = 'SA_SERIAL_UPDATE';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/inventory/includes/inventory_db.inc");

simple_page_mode(true);

$js = "";
if ($SysPrefs->use_popup_windows && $SysPrefs->use_popup_search) {
    $js .= get_js_open_window(900, 300);
}

page(_($help_context = "Serial Update"), true, false, "", $js);

//----------------------------------------------------------------------------------------------------

function get_transactions($company_id, $trans_no) {

    set_global_connection($company_id);
    $branch_code = get_company_value($company_id, 'branch_code');

    $sql = "SELECT '$branch_code' AS branch, $branch_code.* 
    FROM ".TB_PREF."item_serialise AS $branch_code";

    $sql .= " WHERE IFNULL(serialise_item_code, '') <> ''";

    if ($trans_no != null) {
        $sql .= " AND serialise_id = " .db_escape($trans_no);
    }

    $result = db_query($sql, _("get_all_serial()"));

    set_global_connection();

	return $result;
}

//----------------------------------------------------------------------------------------------------

function serial_pnp_update($serial_no, $cleared = 0, $pnp_note = '') {

    global $Ajax, $db_connections;

    $added = 0;
    $default_table_count = count_columns(0, 'item_serialise');

    $sql = "UPDATE ".TB_PREF."item_serialise 
        SET cleared = $cleared";
    
    if ($pnp_note != '') {
        $sql .= ", pnp_note = '$pnp_note'";
    }

    $sql .= " WHERE serialise_lot_no = " .db_escape($serial_no);

    for ($i = 0; $i < count($db_connections); $i++) {

        $not_include = $db_connections[$i]['type'] == 'LENDING' 
        || $db_connections[$i]['branch_code'] == 'HO' 
        || $db_connections[$i]['branch_code'] == 'DESIHOFC'
        || str_contains_val($db_connections[$i]['branch_code'], 'LEND')
        || $default_table_count != count_columns($i, 'item_serialise');
    
        if (!$not_include) {
            set_global_connection($i);
            db_query($sql, "serial_pnp_update()");
            $added++;
        }
    }

    set_global_connection();

    $Ajax->activate('_page_body');
    if ($added > 0) {
        display_notification(_("Serial #: " . $serial_no . " sucessfully updated!"));
    }
    else {
        display_error(_("Serial # " . $serial_no . " is not Properly Updated!"));
    }
}

//----------------------------------------------------------------------------------------------------

function display_details($company_id, $trans_no) {
    
    $result = _item_serialise($company_id, '', $trans_no, 'ALL');

    div_start('serial_head');
    start_table(TABLESTYLE, "width='99%'");

    $th = array(
        _("ID"),
        _("Origin Branch"),
        _("Category"),
        _("Item Code"),
        _("Color | Description"),
        _("Serial/Engine Number"),
        _("Chassis Number"),
        _("PNP Cleared")
    );

    table_header($th);

    $k = 0;

    while ($row = db_fetch_assoc($result)) {
        alt_table_row_color($k);

        $stock_row = db_fetch_assoc(get_stock_by_itemCode($row['serialise_item_code']));
        $is_cleared = $row['cleared'] == 1 ? _("Yes") : _("No");
    
        label_cell($row['trans_id']);
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
        edit_button_cell("Edit".$row['serialise_id'], _("Edit"), _('Update line'));
        end_row();

        start_row();
        label_cells(_('Trasaction Note: &nbsp;&nbsp;'), $row['pnp_note'], 
            "class='tableheader2'", "colspan='7'"
        );
        end_row();
    }

    end_table();
    div_end();
}

function display_edit_form($company_id, $status) {
    global $def_coy;
    set_global_connection($company_id);

    $serial_row = db_fetch_assoc(get_serial_details($company_id, $status));

    div_start("update_serial");
    start_table(TABLESTYLE2);

    check_row(_('PNP Cleared: '), 'cleared', null, false);
    textarea_row(_("Note: "), 'memo_', null, 50, 3);

    end_table(1);
    div_end();
    set_global_connection($def_coy);

    submit_add_or_update_center(false, '', 'both', false, false);
}

//----------------------------------------------------------------------------------------------------

if (isset($_POST['UPDATE_ITEM'])) {
    global $def_coy;
    set_global_connection($_GET['coy']);

    serial_pnp_update(
        $_GET['serial'],
        $_POST['cleared'] != null ? $_POST['cleared'] : 0, 
        $_POST['memo_']
    );

    set_global_connection($def_coy);
}

//----------------------------------------------------------------------------------------------------
start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

global $def_coy;

//$_SESSION["wa_current_user"]->company = $_GET['coy'];

display_details($_GET['coy'], $_GET['trans_no']);
br();

$edit_id = find_submit('Edit');
if ($edit_id != -1) {
    $id = get_post('selected_id', find_submit('Edit'));
    display_edit_form($_GET['coy'], $id);
}
br();
//----------------------------------------------------------------------------------------------------
end_form();
end_page(true);
