<?php
/**
 * Added by: spyrax10
 */

$page_security = 'SA_INVTY_UPDATE';
$path_to_root = "..";

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/ui/items_cart.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/sweetalert.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");

simple_page_mode(true);

$js = "";
if ($SysPrefs->use_popup_windows)
    $js .= get_js_open_window(1000, 600);
if (user_use_date_picker())
    $js .= get_js_date_picker();

$page_title = is_adj_repo($_GET['trans_no']) ? "Update Inventory Adjustment - Repo #" . $_GET['trans_no'] :
    "Update Inventory Adjustment #" . $_GET['trans_no'];
    
page(_($help_context = $page_title), false, false, "", $js);

$trans_no = $_GET['trans_no'];
$status = $_GET['status'];

//-----------------------------------------------------------------------------

if (smo_exists($trans_no, ST_INVADJUST)) 
{
	$trans_type = ST_INVADJUST;
    $sub_title = is_adj_repo($_GET['trans_no']) ? "Adjustment - Repo" : "Adjustment";

    $result = get_stock_adjustment_items($trans_no);
    $row = db_fetch($result);

    if (is_fixed_asset($row['mb_flag'])) {
        display_notification_centered(_("Fixed Assets disposal has been processed!"));
        display_note(get_trans_view_str($trans_type, $trans_no, _("&View this disposal")));

        display_note(get_gl_view_str($trans_type, $trans_no, _("View the GL &Postings for this Disposal")), 1, 0);
	    hyperlink_params($_SERVER['PHP_SELF'], _("Enter &Another Disposal"), "NewAdjustment=1&FixedAsset=1");
    }
    else {
        display_notification_centered(_("Inventory ". $sub_title . " has been processed!"));
        display_note(get_trans_view_str($trans_type, $trans_no, _("&View this " . $sub_title)));

        display_note(get_gl_view_str($trans_type, $trans_no, _("View the GL &Postings for this " . $sub_title)), 1, 0);

	    hyperlink_params("$path_to_root/inventory/adjustments.php", _("Enter &Another " . $sub_title), 
            is_adj_repo($_GET['trans_no']) ? "RepoAdjustment=1" : "NewAdjustment=1");
    }

    if (is_adj_repo($_GET['trans_no'])) {
        hyperlink_params("$path_to_root/inventory/inquiry/adjustment_repo_view.php?", _("Back to Inventory Adjustment List"), "");
    }
    else if (is_adj_repo($_GET['trans_no']) == false) {
        hyperlink_params("$path_to_root/inventory/inquiry/adjustment_view.php?", _("Back to Inventory Adjustment List"), "");
    }

	hyperlink_params("$path_to_root/admin/attachments.php", _("Add an Attachment"), "filterType=$trans_type&trans_no=$trans_no");
	display_footer_exit();
}

//-----------------------------------------------------------------------------
function get_adjust_head($trans_no) {
	
	$sql = "SELECT A.*, B.description
            FROM " . TB_PREF . "stock_adjustment A 
                INNER JOIN " . TB_PREF . "stock_category B ON A.category_id = B.category_id
            WHERE A.trans_no=" .db_escape($trans_no);
    
    $sql .= " GROUP BY A.trans_no, A.trans_id";
    $sql .= " ORDER BY A.trans_id DESC LIMIT 1";

	$result = db_query($sql, "No Items return for stock_adjustments Head! (spyrax10)");
	set_global_connection();
	return $result;
}

function get_adjust_list($trans_no) {
	
	$sql = "SELECT A.trans_id, A.line_id, A.trans_no, A.item_type, A.stock_id, A.color_code, A.loc_code, 
                A.tran_date, A.reference, abs(A.qty) AS qty, A.standard_cost, A.lot_no, A.chassis_no,
                A.category_id, A.adjustment_type, A.status, SM.description, SM.units
        FROM " . TB_PREF . "stock_adjustment A 
            LEFT JOIN " . TB_PREF . "stock_master SM ON A.stock_id = SM.stock_id
        WHERE A.trans_no=" .db_escape($trans_no);
    
	$result = db_query($sql, "No Items return for stock_adjustments Details! (spyrax10)");
	set_global_connection();
	return $result;
}

function get_adjust_item($trans_id) {
    $sql = "SELECT A.trans_id, A.line_id, A.trans_no, A.item_type, A.stock_id, A.color_code, A.loc_code, 
                A.tran_date, A.reference, abs(A.qty) AS qty, A.standard_cost, A.lot_no, A.chassis_no,
                A.category_id, A.adjustment_type, A.status, SM.description, SM.units
        FROM " . TB_PREF . "stock_adjustment A 
            LEFT JOIN " . TB_PREF . "stock_master SM ON A.stock_id = SM.stock_id
        WHERE A.trans_id=" .db_escape($trans_id);

    $result = db_query($sql, "No Item Details return for stock_adjustments! (spyrax10)");
	set_global_connection();
	return db_fetch($result);
}

function update_adjustment($trans_no, $trans_id, $color_code, $lot_no, $chassis_no, $qty, $unit_price, 
   $saved_cust = '', $adj_type = "IN", $stock_id = '', $date = '', $memo = '') 
{
    global $Ajax;
    $qty = $adj_type == "OUT" ? -$qty : $qty;
    $date = date2sql($date);
    
    $sql = "UPDATE ".TB_PREF."stock_adjustment 
        SET standard_cost = '$unit_price', qty = '$qty' ";

    if ($stock_id != '') {
        $sql .= ", stock_id = '$stock_id' ";
    }

    if ($color_code != '') {
        $sql .= ", color_code = '$color_code' ";
    }

    if ($lot_no != '') {
        $sql .= ", lot_no = '$lot_no' ";
    }

    if ($chassis_no != '') {
        $sql .= ", chassis_no = '$chassis_no' ";
    }

    if ($date != '') {
        $sql .= ", tran_date = '$date' ";
    }

    if ($memo != '') {
        $sql .= ", memo = '$memo' ";
    }
   
    $sql .= " WHERE trans_no=".db_escape($trans_no) . " AND trans_id =" .db_escape($trans_id);

    set_global_connection();
    db_query($sql, "Cannot update stock_adjustment!");
    display_notification(_("Transaction ID #" . $trans_id . " sucessfully updated!"));
    clear_items();
    $Ajax->activate('_page_body');
}

function delete_adjustment($trans_no, $trans_id, $line_id) {
    global $Ajax;
    $sql = "DELETE FROM ".TB_PREF."stock_adjustment WHERE trans_no = '$trans_no' AND trans_id = '$trans_id'";
    set_global_connection();
    db_query($sql, "Cannot delete stock_adjustment");

    delete_adjGL($trans_no, $line_id);

    display_notification(_("Transaction ID #" . $trans_id . " sucessfully deleted!"));
    $Ajax->activate('_page_body');

    if (count_adj_items($trans_no) == 0) {
        meta_forward("../inventory/inquiry/adjustment_view.php?");
    }
}

function check_status_adj($trans_no) {
    $sql = "SELECT A.status FROM " . TB_PREF . "stock_adjustment A 
		WHERE A.trans_no=" . db_escape($trans_no);

	$result = db_query($sql, "Cant get adjustment status! (spyrax10)");
    set_global_connection();
	$row = db_fetch_row($result);
	return $row[0];
}

function get_debtor_ref($cust_name) {
    $sql = "SELECT A.debtor_ref FROM " . TB_PREF . "debtors_master A 
		WHERE A.name=" . db_escape($cust_name);

	$result = db_query($sql, "Cant get debtor_ref! (spyrax10)");
    set_global_connection();
	$row = db_fetch_row($result);
	return $row[0];
}

function count_adj_items($trans_no) {

    $sql = "SELECT COUNT(*) FROM " . TB_PREF . "stock_adjustment A 
    WHERE A.trans_no=" . db_escape($trans_no);

    $result = db_query($sql, "Cant get stock_adjustment trans_no count! (spyrax10)");
    set_global_connection();
	$row = db_fetch_row($result);
	return $row[0];
}

function adj_serial_count($trans_id, $adj_type, $lot_no, $chassis_no = '') {
    $sql = "SELECT COUNT(*) FROM " . TB_PREF . "stock_adjustment A 
    WHERE A.trans_id != " .db_escape($trans_id) . " AND adjustment_type = " .db_escape($adj_type) . " 
        AND A.lot_no =" . db_escape($lot_no);
    
    if ($chassis_no != '') {
        $sql .= " AND A.chassis_no = " .db_escape($chassis_no);
    }

    $result = db_query($sql, "Cant get stock_adjustment serial count! (spyrax10)");
    set_global_connection();
	$row = db_fetch_row($result);
	return $row[0];
}

function clear_items() {
    $_POST['upd_stock_id'] = '';
    $_POST['_upd_stock_id_edit'] = '';
    $_POST['color'] = '';
    $_POST['lot_no'] = '';
    $_POST['std_cost'] = '';
} 

//-----------------------------------------------------------------------------

function display_adjustment_header($trans_no)
{
    $result = get_adjust_head($trans_no);
    start_outer_table(TABLESTYLE2, "width='80%'");

    while ($row = db_fetch_assoc($result)) {
        $date = $row['tran_date'];
        $type = $row['adjustment_type'];
        
        table_section(1);
        locations_list_row(_("Location: "), 'StockLocation', $row['loc_code'], false, true);
        date_row(_("Date: "), 'AdjDate', '', true);
        label_row(_("Adjustment Type: "), $type, "", "", 0, 'adj_type');
       
        table_section(2);
        label_row(_("Reference: "), $row['reference']);
        stock_categories_list_row(_("Category: "), "category", $row['category_id'], false, true);
        textarea_row(_("Memo:"), 'memo', $row['memo'], 25, 3);
        
    }
    end_outer_table(1);
}

function display_adjustment_items($trans_no)
{
	display_heading("Adjustment Items");
	div_start('adj_items');
    start_table(TABLESTYLE, "colspan=7 width='90%'");
    
    $result = get_adjust_list($trans_no);

    $th = array(
            _("ID"),
            //_("GL"),
            _("Item Code"), 
            _("Item Description"), 
            _("Color"), 
            _("Quantity"), 
            _("Unit"),  
            _("Serial/Engine Num"), 
            _("Chassis Num"),
            _("Unit Cost"), 
            _("Sub Total"), _(""), ("")
    );

    table_header($th);

	$total = 0;
    $k = 0;

	while ($row = db_fetch_assoc($result)) {
        alt_table_row_color($k);

        $sub_total = $row['qty'] * $row['standard_cost'];
        $total += $sub_total;
        $adj_type = $row['adjustment_type'] == "IN" ? 1 : 2;

        label_cell($row['trans_id'], "", 'stock_trans_id');
        //view_JE_adj_cell($trans_no, $row['line_id'], 1);
        label_cell($row['stock_id'], "", 'stock_id');
        label_cell($row['description'], "", 'stock_desc');
        label_cell($row['color_code'], "", 'stock_color');
        label_cell($row['qty'], "nowrap align='center'", 'stock_qty');
        label_cell($row['units'], "nowrap align='center'", 'stock_units');
        label_cell($row['lot_no'], "", 'stock_lot');
        label_cell($row['chassis_no'], "", 'stock_chassis');
        amount_cell($row['standard_cost'], false, "", 'stock_cost');
        amount_cell($sub_total, false, "", 'stock_sub');
        edit_button_cell("Edit".$row['trans_id'], _("Edit"), _('Edit document line'));
        delete_button_cell("Delete".$row['trans_id'], _("Delete"), _('Remove line from document'));

        if ($sub_total > 0) {
            $person_id = get_sup_id_by_stock($row['stock_id']);
		    $masterfile = get_sup_name_by_sup(get_sup_id_by_stock($row['stock_id']));

		    $stock_gl_codes = get_stock_gl_code($row['stock_id']);
		    $amount = $adj_type == 1 ? $row['qty'] * $row['standard_cost'] :
			    -$row['qty'] * $row['standard_cost'];

            if (adjGL_line_exists($trans_no, $row['line_id']) == 0) {
                add_adj_gl (
                    $trans_no, 
                    $row['line_id'], 
                    $adj_type, 
                    $row['reference'], 
                    $row['stock_id'], 
                    $row['color_code'], 
                    $row['lot_no'], 
                    $row['chassis_no'], 
                    $amount, 
                    $person_id, 
                    $masterfile,
                    $stock_gl_codes['inventory_account'], 'DEFAULT', $row['item_type']
                );
            }
            
        }
    }

    label_row(_("Document Total: "), number_format2($total, user_price_dec()), 
        "align=right colspan=9; style='font-weight:bold';", "style='font-weight:bold'; align=right", 0
    );
    
    end_table();
    div_end();
}

//-----------------------------------------------------------------------------
function display_update_item($trans_id) {
    
    global $Ajax;

    div_start("upd_tbl");
    start_table(TABLESTYLE2);

    display_heading("Updating Transaction ID #" . $trans_id);
    echo "<br>";

    $row = get_adjust_item($trans_id);
    $_POST['trans_id'] = $row['trans_id'];
    $_POST['adj_type'] = $row['adjustment_type'];

    table_section(1);

    hidden('trans_id');
    hidden('stock_id');

    if ($row['adjustment_type'] == "OUT") {

        $stock_id = list_updated('upd_stock_id') ? get_post('upd_stock_id') : $row['stock_id'];
        $lot_no = list_updated('upd_stock_id') ? get_post('lot_no') : '';
        $chassis_no = list_updated('upd_stock_id') ? get_post('chasis_no') : '';
        $qty = list_updated('upd_stock_id') ? 0 : $row['qty'];
        $unit_price = list_updated('upd_stock_id') ? get_post('std_cost') : '';
        $color_code = list_updated('upd_stock_id') ? get_color_description(get_post('color'), $stock_id) : '';

        inty_list_cells(_("New Item: "), 'upd_stock_id', null, $row['category_id'], false, true, true, $row['adjustment_type'] == "OUT" ? 2 : 1, $row['loc_code']);

        if (list_updated('upd_stock_id')) {
            $Ajax->activate('upd_tbl');
        }

        if ($row['category_id'] == 14) {
            label_row(_("Color Description: "), $color_code, "", "", 0, 'color');
        }

        if (is_Serialized($row['stock_id']) == 1) {
            label_row(_("Serial # / Engine #: "), $lot_no, "", "", 0, 'lot_no');

            if ($row['category_id'] == 14) {
                label_row(_("Chassis #: "), $chassis_no, "", "", 0, 'chasis_no');
            }
        }

        if (is_Serialized($row['stock_id']) == 1) {
            $_POST['qty'] = 1;
            hidden('qty');
        } 
        else {
            text_row(_("Total Quantity: "), 'qty', $qty, 10, 10);
        }
       
        label_row(_("Unit Price: "), $unit_price, "", "", 0, 'std_cost');

        hidden('color');
        hidden('lot_no');
        hidden('chasis_no');
        hidden('adj_type');
        hidden('std_cost');
    }
    else {
        if ($row['category_id'] == 14) {
            sql_type_list(_("Color Description:"), 'color', 
				get_color_list($row['stock_id']), 'item_code', 'ColorDesc', 
				'label', $row['color_code'], true
			);
        }
        else {
            hidden('color');
        }
        
        if ($row['lot_no'] != '') {
            text_row(_("Serial # / Engine #: "), 'lot_no', $row['lot_no'], 50, 50);
        }
        else {
            hidden('lot_no');
        }
    
        if ($row['chassis_no'] != '') {
            text_row(_("Chassis #: "), 'chasis_no', $row['chassis_no'], 50, 50);
        }
        else {
            hidden('chassis_no');
        }
    
        if (is_Serialized($row['stock_id']) == 1) {
            $_POST['qty'] = 1;
            hidden('qty');
        } 
        else {
            text_row(_("Total Quantity: "), 'qty', $row['qty'], 10, 10);
        }
    
        text_row(_("Unit Price: "), 'std_cost', $row['standard_cost'], 10, 10);
    }
    
    end_table(1);
    div_end();
    submit_add_or_update_center(false, '', 'both');
    echo "<br> <br>";
}

function display_delete_item($trans_id) {
 
    start_table(TABLESTYLE2);

    $row = get_adjust_item($trans_id);
    $_POST['trans_id'] = $row['trans_id'];
    hidden('trans_id');

    display_heading(_("Are you sure to DELETE Transaction #" . $trans_id . "?"));
    end_table(1);
    submit_add_or_update_center(false, '', 'both', false, true);
}

//-----------------------------------------------------------------------------
function can_proceed() {

    $row = get_adjust_item(get_post('trans_id'));
    $stock_id = get_post('upd_stock_id') != '' ? get_post('upd_stock_id') : $row['stock_id'];

    $demand_qty = get_demand_qty($stock_id, get_post("StockLocation"));
	$demand_qty += get_demand_asm_qty($stock_id, get_post("StockLocation"));
	$qoh = get_qoh_on_date($stock_id, get_post("StockLocation"), null, $row['item_type']);
	$qty = $qoh - $demand_qty;

    if (get_post('adj_type') == "OUT" && $qty < input_num('qty')) {
        if (is_Serialized($stock_id) == 0) {
            display_error('Insufficient Quantity in stocks!');
            set_focus('qty');
            return false;
        }
    }

    if (is_Serialized($stock_id) == 1 &&
        adj_serial_count(get_post('trans_id'), $row['adjustment_type'], get_post('lot_no'), get_post('chasis_no')) != 0) {
        display_error('Adjustment Serial Item is already exists! ');
        return false;
    }

    if (input_num('std_cost') == 0 && get_post('category') != 17)
	{
		display_error(_("Only PROMO ITEMS are allowed to have zero cost!"));
		set_focus('std_cost');
		return false;
	}

    if (is_Serialized($stock_id) == 0 && input_num('qty') == 0) {
        display_error('Quantity cant be zero!');
        set_focus('qty');
        return false;
    }

    if (is_Serialized($stock_id) == 1 && $row['adjustment_type'] == "IN") {
        if (get_post('lot_no') == '') {
            display_error('Serial # cant be empty for this item!');
            return false;
        }
        else if ($row['category_id'] == 14 && get_post('chasis_no') == '') {
            display_error('Chassis # cant be empty for this item!');
            return false;
        }
    }

    return true;
}
//-----------------------------------------------------------------------------

if (isset($_POST['UPDATE_ITEM']) && can_proceed()) {

    $row = get_adjust_item(get_post('trans_id'));

    update_adjustment($trans_no, get_post('trans_id'), 
        get_post('color') != '' ? get_post('color') : '', 
        get_post('lot_no') != '' ? get_post('lot_no') : $row['lot_no'], 
        get_post('chasis_no') != '' ? get_post('chasis_no') : $row['chassis_no'], 
        get_post('qty'), 
        get_post('std_cost') != '' ? get_post('std_cost') : $row['standard_cost'], 
        get_post('saved_cust') == "NONE" ? '' : get_post('saved_cust'),
        get_post('adj_type'), 
        get_post('upd_stock_id') != '' ? get_post('upd_stock_id') : $row['stock_id'], 
        !get_post('AdjDate') ? $row['tran_date'] : get_post('AdjDate'),
        get_post('memo')
    ); 

}

if (isset($_POST['DELETE_ITEM'])) {

    $row = get_adjust_item(get_post('trans_id'));

    delete_adjustment($trans_no, get_post('trans_id'), $row['line_id']);
}

//-----------------------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

display_adjustment_header($trans_no);
display_adjustment_items($trans_no);
echo "<br>";

$edit_id = find_submit('Edit');
$delete_id = find_submit('Delete');

if ($edit_id != -1) {
    $id = get_post('selected_id', find_submit('Edit'));
    display_update_item($id);
}
else if ($delete_id != -1){
    $id = get_post('selected_id', find_submit('Delete'));
    display_delete_item($id);
}
else if (get_post('upd_stock_id')) {
    display_update_item(get_post('trans_id'));
}

end_form();
end_page();