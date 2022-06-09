<?php
/**
 * added by: spyrax10
 * copied from: si_stock_list.php
 */

$page_security = "SA_SISTOCKLIST";
$path_to_root = "../..";

include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/inventory/includes/db/items_db.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");

//$mode = get_company_pref('no_item_list');

// if ($mode != 0) {
// 	$js = inty_get_js_set_combo_item();
// }
// else {
// 	$js = get_js_select_combo_item();
// }

$js = get_js_item();

if ($SysPrefs->use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}

page(_($help_context = "Items"), true, false, "", $js);

if (get_post("search")) {
	$Ajax->activate("item_tbl");
}

if (get_post('serialized') == 1) {
	$_POST['serialized'] = 1;
} else {
	$_POST['serialized'] = 0;
}

#----------------------------------------------#
function price_total($row) {

	return  price_format($_GET['type'] == 1 ? Get_Policy_CashPrice(getCompDet('branch_code'), $_GET["category"], $row['stock_id']) : 
		Get_System_Cost($row['stock_id'], $row['type_out'], $row['trans_no']));
}

function qoh_cell($row) {
	$loc_code = $_GET['location'];
	$item_type = $_GET["item"];

	$demand_qty = get_demand_qty($row['stock_id'], $loc_code);
	$demand_qty += get_demand_asm_qty($row['stock_id'], $loc_code);
	$qoh = get_qoh_on_date($row['stock_id'], $loc_code, null, $item_type);

	return $qoh - $demand_qty;
}

function multiple_selection($row) {
	return check_cells(null, 'smo[' . $row['trans_id'] . ']', null, false);
}

function select_cell($row) {

	$mode = get_company_pref('no_item_list');
	
	$cell = "";
	$value = $row['stock_id'];
	$name = $_GET["client_id"];
	
	if ($_GET['type'] == 2) {
		if ($mode != 0) {

			$text = $row['description'];
	
			if (get_post('serialized') == 1) {
				$cell = ahref_cell(_("Select"), 'javascript:void(0)', '', 'setComboItem(window.opener.document, 
					"' . $name . '",  "' . $value . '", "' . $text . '", "' . $row["serialeng_no"] . '", "' . $row["chassis_no"] . '"
					, "' . $row["color_code"] . '", "' . price_total($row) . '", "' .$row['reference'] .'")');
			} else if (get_post('serialized') == 0) {
				$cell = ahref_cell(_("Select"), 'javascript:void(0)', '', 'setComboItem(window.opener.document, 
					"' . $name . '",  "' . $value . '", "' . $text . '", "", "", "", "' . price_total($row) . '", "' .$row['reference'] .'" )');
			} else {
				$cell = ahref_cell(_("Select"), 'javascript:void(0)', '', 'setComboItem(window.opener.document, 
					"' . $name . '",  "' . $value . '", "' . $text . '", "", "", "", "' . price_total($row) . '", "' .$row['reference'] .'" )');
			}
		} else {
			$cell = ahref_cell(_("Select"), 'javascript:void(0)', '', 'selectComboItem(window.opener.document, "' . $name . '", "' . $value . '")');
		}
	
	}
	else {
		if ($mode != 0) {

			$text = $row['description'];
	
			if (get_post('serialized') == 1) {
				$cell = ahref_cell(_("Select"), 'javascript:void(0)', '', 'setComboItem(window.opener.document, 
					"' . $name . '",  "' . $value . '", "' . $text . '", "' . $row["serialeng_no"] . '", "' . $row["chassis_no"] . '"
					, "' . $row["color_code"] . '", "0")');
			} else if (get_post('serialized') == 0) {
				$cell = ahref_cell(_("Select"), 'javascript:void(0)', '', 'setComboItem(window.opener.document, 
					"' . $name . '",  "' . $value . '", "' . $text . '", "", "", "", "0" )');
			} else {
				$cell = ahref_cell(_("Select"), 'javascript:void(0)', '', 'setComboItem(window.opener.document, 
					"' . $name . '",  "' . $value . '", "' . $text . '", "", "", "", "0" )');
			}
		} else {
			$cell = ahref_cell(_("Select"), 'javascript:void(0)', '', 'selectComboItem(window.opener.document, "' . $name . '", "' . $value . '")');
		}
	}
	
	return $cell;
}

function reference_row($row) {
	return get_trans_view_str($row['type'], $row["trans_no"], $row['reference']);
}

function trans_id($row) {
	return $row['trans_id'];
}

#----------------------------------------------#

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);

start_row();

ref_cells(_("#: &nbsp;"), 'searchval', '', null, '', true);

if ($_GET['type'] == 1 && $_GET['supplier'] == '') {
	supplier_list_cells(_("Supplier: "), 'supplier_id', null, false, true, false, false, true, $_GET['category']);
}

check_cells(_("Serialized"), 'serialized', $_POST['serialized'], true);
submit_cells("search", _("Search"), "", _("Search items"), "default");
end_row();

end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

global $Ajax;
$Ajax->activate('item_tbl');

end_row();
end_table(); 

//display_heading("Category: " . get_category_name($_GET['category']));

ahref_cell(_("Continue with Selected Item/s"), 
	'javascript:void(0)', '', 'setValue(window.opener.document, "' . $_GET["client_id"] . '")'
);
br(2);

$sql = 
get_available_item_for_inty(
	$_GET["category"],
	$_POST['serialized'],
	get_post("searchval"), $_GET['type'], $_GET['location'], $_GET["supplier"] == '' ? get_post('supplier_id') : $_GET["supplier"],
	$_GET["item"]
);

if ($_POST['serialized'] == 1 && $_GET['type'] == 1) {
	$cols = array (
		_("Brand") => array('name' => 'brand'),
		_("Item Code") => array('name' => 'stock_id'),
		_("Description") => array('name' => 'description'),
		_("Color") => array('name' => 'color_code'),
		_("Unit Price") => array('align' => 'center', 'fun' => 'price_total'),
		array('insert' => true, 'fun' => 'select_cell', 'align' => 'center')
	);
}
else if ($_POST['serialized'] == 1 && $_GET['type'] == 2) {
	$cols = array (
		_("ID") => array('fun' => 'trans_id'),
		_("Reference") => array('fun' => 'reference_row'),
		_("Brand") => array('name' => 'brand'),
		_("Item Code") => array('name' => 'stock_id'),
		_("Description") => array('name' => 'description'),
		_("Color") => array('name' => 'color_code'),
		_("Serial/Engine No") => array('name' => 'serialeng_no'),
		_("Chassis No") => array('name' => 'chassis_no'),
		_("Unit Price") => array('align' => 'center', 'fun' => 'price_total'),
		array('insert' => true, 'fun' => 'multiple_selection', 'align' => 'center')
	);
}
else if ($_POST['serialized'] == 0 && $_GET['type'] == 2) {
	$cols = array (
		_("ID") => array('fun' => 'trans_id'),
		_("Reference") => array('fun' => 'reference_row'),
		_("Brand") => array('name' => 'brand'),
		_("Item Code") => array('name' => 'stock_id'),
		_("Description") => array('name' => 'description'),
		_("Color") => array('name' => 'color_code'),
		_("Unit Price") => array('align' => 'center', 'fun' => 'price_total'),
		array('insert' => true, 'fun' => 'multiple_selection', 'align' => 'center')
	);
}
else {
	$cols = array (
		_("Brand") => array('name' => 'brand'),
		_("Item Code") => array('name' => 'stock_id'),
		_("Description") => array('name' => 'description'),
		_("QoH") => array('fun' => 'qoh_cell', 'align' => 'center'),
		_("Unit Price") => array('align' => 'center', 'fun' => 'price_total'),
		array('insert' => true, 'fun' => 'select_cell', 'align' => 'center')
	);
}

$table = &new_db_pager('item_tbl', $sql, $cols, null, null, 100);

$table->width = "98%";

display_db_pager($table);

end_form();
end_page(true);
