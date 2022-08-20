<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
$page_security = 'SA_INVTY_ADJ_ENTRY';
$path_to_root = "..";

include_once($path_to_root . "/includes/ui/items_cart.inc");

include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/fixed_assets/includes/fixed_assets_db.inc");
include_once($path_to_root . "/modules/Inventory_Beginning_Balances/includes/item_adjustments_ui.inc"); //modified by spyrax10
include_once($path_to_root . "/inventory/includes/inventory_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_db.inc");

$js = ''; 

if ($SysPrefs->use_popup_windows) {
	$js .= get_js_open_window(1000, 600);
}

if (user_use_date_picker()) {
	$js .= get_js_date_picker();
}

if (isset($_GET['NewAdjustment'])) {
	if (isset($_GET['FixedAsset'])) {
		$page_security = 'SA_ASSETDISPOSAL';
		$_SESSION['page_title'] = _($help_context = "Fixed Assets Disposal");
	} else {
		$_SESSION['page_title'] = _($help_context = "Inventory Adjustment Note");
	}
}
else if (isset($_GET['RepoAdjustment'])) {
	$_SESSION['page_title'] = _($help_context = "Inventory Adjustment - Repo Note");
}

page($_SESSION['page_title'], false, false, "", $js);

//--------------------------------------------------------------------------------------------------
function get_item_type() {

	$item_type = "";

	if ($_SESSION['page_title'] == "Inventory Adjustment - Repo Note") {
		$item_type = "repo";
	}
	else if ($_SESSION['page_title'] == "Inventory Adjustment Note") {
		$item_type = "new";
	}

	return $item_type;
}

function line_start_focus() {
  global $Ajax;

  $Ajax->activate('items_table2');
  set_focus('_stock_id_edit');
}

$adj = &$_SESSION['adj_items'];
if (get_post("category") && count($adj->line_items) <= 0) {
	$Ajax->activate("items_table2");
}

if (get_post("adj_type") && count($adj->line_items) <= 0) {
	$Ajax->activate("stock_id");
}

if (get_post('StockLocation')) {
	$Ajax->activate("stock_id");
}

//-----------------------------------------------------------------------------------------------

function handle_new_order() {
	global $Refs;

	if (isset($_SESSION['adj_items'])) {
		$_SESSION['adj_items']->clear_items();
		unset ($_SESSION['adj_items']);
	}

    $_SESSION['adj_items'] = new items_cart(ST_INVADJUST);
    $_SESSION['adj_items']->fixed_asset = isset($_GET['FixedAsset']);
	$_POST['AdjDate'] = new_doc_date();

	if (!is_date_in_fiscalyear($_POST['AdjDate'])) {
		$_POST['AdjDate'] = end_fiscalyear();
	}

	$_SESSION['adj_items']->tran_date = $_POST['AdjDate'];	
	$_SESSION['adj_items']->reference = $Refs->get_next(ST_INVADJUST, null, array('location'=>get_post('StockLocation'), 'date'=>get_post('AdjDate')));
}

//-----------------------------------------------------------------------------------------------

if (get_post('adj_type') == 2 && list_updated('stock_id')) {

	$selected_items = array();
	$selected_items = explode(',', $_POST["stock_id"]);
	
	foreach ($selected_items as $key => $val) {
		if ($val != '') {
			$res = get_smo($val); 
			while ($row = db_fetch($res)) {
				add_to_order($_SESSION['adj_items'], 
					$row['stock_id'], 
					is_Serialized($row['stock_id']) == 0 ? 0 : $row['qty'], 
					$row['standard_cost'], 
					'', 
					'', 
					$row['lot_no'] != '' ? $row['lot_no'] : '',
					$row['chassis_no'] != '' ? $row['chassis_no'] : '', 
					$row['color_code'] != '' ? $row['color_code'] : '', 
					$row['reference']
				); 
			}
		}
	}

	$_POST['stock_id'] = '';
	$_POST['qty'] = 0;
	$Ajax->activate('items_table2');
}

function can_process() {

	global $SysPrefs;

	$adj = &$_SESSION['adj_items'];

	if (count($adj->line_items) == 0) {
		display_error(_("You must enter at least one non empty item line."));
		set_focus('stock_id');
		return false;
	}

	foreach ($_SESSION['adj_items']->line_items as $items) {
		if ($items->quantity == 0) {
			display_error(_("Can't Proceed! Some Lines have 0 qty!"));
			return false;
		}
	}

	if (!check_reference($_POST['ref'], ST_INVADJUST)) {
		set_focus('ref');
		return false;
	}

	if (!is_date($_POST['AdjDate'])) {
		display_error(_("The entered date for the adjustment is invalid."));
		set_focus('AdjDate');
		return false;
	} 
	else if (!is_date_in_fiscalyear($_POST['AdjDate'])) {
		display_error(_("The Entered Date is OUT of FISCAL YEAR or is CLOSED for further data entry!"));
		set_focus('AdjDate');
		return false;
	}
	else if (!allowed_posting_date($_POST['AdjDate'])) {
		display_error(_("The Entered Date is currently LOCKED for further data entry!"));
		set_focus('AdjDate');
		return false;
	}
	
	if (!$SysPrefs->allow_negative_stock()) {
		$low_stock = $adj->check_qoh($_POST['StockLocation'], $_POST['AdjDate']);

		if ($low_stock) {
    		display_error(_("The adjustment cannot be processed because it would cause negative inventory balance for marked items as of document date or later."));
			unset($_POST['Process']);
			return false;
		}
	}
	return true;
}

//-------------------------------------------------------------------------------

if (isset($_POST['Process']) && can_process()) {

  	$fixed_asset = $_SESSION['adj_items']->fixed_asset; 

	$adj_id = get_next_adjID();
	$adj_type = get_post('adj_type') == 2 ? "OUT" : "IN";
	$count = $trans_no_out = $trans_type_no = 0;
	$trans_type = get_item_type() == "repo" ? ST_RRREPO : ST_INVADJUST;

	foreach ($_SESSION['adj_items']->line_items as $items) {

		$count++;
		$stock_id = $items->stock_id;
		$row = get_smo_details($items->stock_ref, $stock_id, $items->lot_no);

		$trans_no_out = $adj_type == "OUT" ? $row['transno_out'] : $adj_id;
		$trans_type_out = $adj_type == "OUT" ? $row['type_out'] : $trans_type;

		$line_id = $count;

		add_stock_adjust(ST_INVADJUST, $items->stock_id, $adj_id, $line_id, $_POST['StockLocation'],
			$_POST['AdjDate'], $_POST['ref'], $items->quantity, $items->standard_cost, 
			0, $items->lot_no, $items->chasis_no, $items->category_id,
			$items->color, $adj_type, 'Draft', $_POST['memo_'], '', '0000-00-00', '', 
			$trans_no_out, $trans_type_out, get_item_type()
		); 
	}

	new_doc_date($_POST['AdjDate']);
	$_SESSION['adj_items']->clear_items();
	unset($_SESSION['adj_items']);

	if (get_item_type() == "new") {
		meta_forward($path_to_root . "/inventory/inquiry/adjustment_view.php?");
	}
	else if (get_item_type() == "repo") {
		meta_forward($path_to_root . "/inventory/inquiry/adjustment_repo_view.php?");
	}

} /*end of process credit note */

//-----------------------------------------------------------------------------------------------

function check_item_data() {

	$demand_qty = get_demand_qty($_POST['stock_id'], get_post("StockLocation"));
	$demand_qty += get_demand_asm_qty($_POST['stock_id'], get_post("StockLocation"));
	$qoh = get_qoh_on_date($_POST['stock_id'], get_post("StockLocation"), null, get_item_type());
	$qty = $qoh - $demand_qty;
	$serial_count = get_qoh_on_date('', get_post("StockLocation"), null, get_item_type(), user_company(), $_POST['lot_no']);
	$chassis_count = get_qoh_on_date('', get_post("StockLocation"), null, get_item_type(), user_company(), '', $_POST['chasis_no']);

	if (input_num('qty') == 0) {
		display_error(_("The quantity entered is invalid."));
		set_focus('qty');
		return false;
	}

	if (is_Serialized($_POST['stock_id']) == 1 && input_num('qty') != 1) {
		display_error(_("Only ONE quantity per serialized item!"));
		set_focus('qty');
		return false;
	}

	if (is_Serialized($_POST['stock_id']) == 1 && get_post('adj_type') == 1) {
		if ($_POST['lot_no'] == '') {
			display_error(_("Serial # cant be empty for this item!"));
			set_focus('lot_no');
			return false;
		}
		else if (get_post('category') == 14 && $_POST['chasis_no'] == '') {
			display_error('Chassis # cant be empty for this item!');
			set_focus('chassis_no');
            return false;
		}
	}

	if (is_Serialized($_POST['stock_id']) == 1 && $serial_count > 0 && get_post('adj_type') == 1) {
		display_error("Serial # Already Registered in the System!");
		return false;
	}
	else if (is_Serialized($_POST['stock_id']) == 1 && $chassis_count > 0 && get_post('adj_type') == 1) {
		display_error("Chassis # Already Registered in the System");
		return false;
	}

	if (is_Serialized($_POST['stock_id']) == 1 && serial_exist_adj($_POST['lot_no'], $_POST['chasis_no']) > 0 
		&& get_post('adj_type') == 1) {
		display_error("Serial / Chassis # Already Pending in Inventory Adjustment!");
		return false;
	}

	if (get_post('adj_type') == 2 && $qty < input_num('qty')) {
		display_error(_("Insufficient quantity!"));
		set_focus('qty');
		return false;
	}
	
	if (input_num('std_cost') == 0 && get_post('category') != 17 && get_post('adj_type') == 1) {
		display_error(_("Only PROMO ITEMS are allowed to have zero cost!"));
		set_focus('std_cost');
		return false;
	}

	if (get_post('category') == 14 && get_post('adj_type') == 1 && !get_post('color')) {
		display_error(_("Color cant be empty for this item!"));
		set_focus('color');
		return false;
	}

   	return true;
}

//-----------------------------------------------------------------------------------------------

function handle_update_item() {
	$id = $_POST['LineNo'];
	$_SESSION['adj_items']->update_cart_item($id, input_num('qty'), 
		input_num('std_cost'), 
		$_POST['manufacture_date'], 
		$_POST['expire_date'],
		get_post("lot_no") != '' ? $_POST['lot_no'] : '',
		get_post("chasis_no") != '' ? $_POST['chasis_no'] : '',  
		get_post('color') != '' ? $_POST['color'] : ''
	); 
	
	unset($_POST['_stock_id_edit'], $_POST['stock_id'], $_POST['qty'], $_POST['std_cost'], 
		$_POST['lot_no'], $_POST['chasis_no'], $_POST['color']
	);

	line_start_focus();
}

//-----------------------------------------------------------------------------------------------

function handle_delete_item($id) {
	$_SESSION['adj_items']->remove_from_cart($id);
	
	unset($_POST['_stock_id_edit'], $_POST['stock_id'], $_POST['qty'], $_POST['std_cost'], 
		$_POST['lot_no'], $_POST['chasis_no'], $_POST['color']
	); 

	line_start_focus();
}

//-----------------------------------------------------------------------------------------------

function handle_new_item() {
	add_to_order($_SESSION['adj_items'], 
		$_POST['stock_id'], 
		input_num('qty'), 
		input_num('std_cost'), 
		$_POST['manufacture_date'], 
		$_POST['expire_date'], 
		get_post("lot_no") != '' ? $_POST['lot_no'] : '',
		get_post("chasis_no") != '' ? $_POST['chasis_no'] : '', 
		get_post('color') != '' ? $_POST['color'] : '',
		get_post('stock_ref')	
	); 

	unset($_POST['_stock_id_edit'], $_POST['stock_id'], $_POST['qty'], $_POST['std_cost'], 
		$_POST['lot_no'], $_POST['chasis_no'], $_POST['color']
	);
	line_start_focus();
}

//-----------------------------------------------------------------------------------------------

$id = find_submit('Delete');
if ($id != -1) {
	handle_delete_item($id);
}

if (isset($_POST['AddItem']) && check_item_data()) {
	handle_new_item();
	unset($_POST['selected_id']);
}
if (isset($_POST['UpdateItem']) && check_item_data()) {
	handle_update_item();
	unset($_POST['selected_id']);
}
if (isset($_POST['CancelItemChanges'])) {
	unset($_POST['selected_id']);
	line_start_focus();
}

//-----------------------------------------------------------------------------------------------

if (isset($_GET['NewAdjustment']) || !isset($_SESSION['adj_items'])) {
	if (isset($_GET['FixedAsset'])) {
		check_db_has_disposable_fixed_assets(_("There are no fixed assets defined in the system."));
	}
	// else {
	// 	check_db_has_costable_items(_("There are no inventory items defined in the system which can be adjusted (Purchased or Manufactured)."));
	// }

	handle_new_order();
}

//-----------------------------------------------------------------------------------------------
start_form();

if ($_SESSION['adj_items']->fixed_asset) {
	$items_title = _("Disposal Items");
	$button_title = _("Process Disposal");
} 

else if (isset($_GET['RepoAdjustment'])) {
	$items_title = _("Adjustment - Repo Items");
	$button_title = _("Process Adjustment - Repo");
}
else {
	$items_title = _("Adjustment Items");
	$button_title = _("Process Adjustment");
}

display_order_header($_SESSION['adj_items'], 1);

start_outer_table(TABLESTYLE, "width='95%'", 10);
$trans_no = get_next_adjID();

display_adjustment_items($items_title, $_SESSION['adj_items'], 1, $trans_no, get_item_type());
adjustment_options_controls();

end_outer_table(1, false);

submit_center_first('Update', _("Update"), '', null);
submit_center_last('Process', $button_title, '', 'default');

end_form();
end_page();

