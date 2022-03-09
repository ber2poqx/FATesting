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
/**********************************************************************
  Page for searching item list and select it to item selection
  in pages that have the item dropdown lists.
  Author: bogeyman2007 from Discussion Forum. Modified by Joe Hunt
 ***********************************************************************/

/**
 * Modified by: spyrax10
 * Date Modified: 11-01-2022
 */

$page_security = "SA_ITEMPOPUPVIEW";
$path_to_root = "../..";

include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/inventory/includes/db/items_db.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");

$mode = get_company_pref('no_item_list');
if ($mode != 0)
	$js = get_js_set_combo_item();
else
	$js = get_js_select_combo_item();

page(_($help_context = "Items"), true, false, "", $js);

if (get_post("search")) {
	$Ajax->activate("item_tbl");
}

//Added by spyrax10
if (get_post("category")) {
	$Ajax->activate("item_tbl");
}

#----------------------------------------------#
function select_cell($row) {
	
	$mode = get_company_pref('no_item_list');
	$cell = "";
	$name = $_GET["client_id"];
	$value = $row['stock_id'];

	if ($mode != 0) {
		$text = $row['description'];
		ahref_cell(_("Select"), 'javascript:void(0)', '', 'setComboItem(window.opener.document, "' . $name . '",  "' . $value . '", "' . $text . '")');
	} else {
		ahref_cell(_("Select"), 'javascript:void(0)', '', 'selectComboItem(window.opener.document, "' . $name . '", "' . $value . '")');
	}

	return $cell;
}

function qoh_cell($row) {
	//Added Herald - 09-01-2020 for available qty 
	$loc_details = get_loc_details($row['stock_id']);
	$myrow1 = db_fetch($loc_details);

	//Modified by spyrax10
	$demand_qty = get_demand_qty($row['stock_id'], null);
	//$demand_qty += get_demand_asm_qty($myrow['stock_id'], $myrow1["loc_code"]);
	$demand_qty += get_demand_asm_qty($row['stock_id'], null);
	$qoh = get_qoh_on_date($row['stock_id'], null);
	//

	return $qoh - $demand_qty;
}

function item_code($row) {
	return $row['stock_id'];
}

function item_desc($row) {
	return $row['description'];
}

function item_unit($row) {
	return $row['units'];
}

function item_cat($row) {
	return $row['category'];
}

function item_brand($row) {
	return $row['brand_name'];
}

function item_suppliers($row) {
	return $row["manufacturer_name"] == 'NULL' ? '' : $row["manufacturer_name"];
}

function item_sub_cat($row) {
	return $row["distributor_name"] == 'NULL' ? '' : $row["distributor_name"];
}

function item_class($row) {
	return $row["importer_name"] == 'NULL' ? '' : $row["importer_name"];
}

function price_total($row) {

	return  price_format(Get_Policy_CashPrice(getCompDet('branch_code'), get_stock_catID($row['stock_id']), $row['stock_id']));
}

#----------------------------------------------#
$category_id = $_GET["itemgroup"] != '' && is_numeric($_GET['itemgroup']) ? $_GET["itemgroup"] : get_post("category");
//

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);

start_row();

text_cells(_("Description"), "description");

/* Modified Ronelle 9/28/2020 */
if ($_GET["itemgroup"] == '') { //Added by spyrax10
	if ($_GET["type"] != "pr" && $_GET['type'] != "sales") {
		stock_categories_list_cells(_("Category"), "category", null, _("All Categories"), 
			true //Added by spyrax10
		);
	}
}
else if (!is_numeric($_GET['itemgroup'])) {
	stock_categories_list_cells(_("Category"), "category", null, _("All Categories"), 
		true //Added by spyrax10
	);
}

/* */

//stock_brand_list_row(_('Brand:'), 'brand', null,_('All Brand'));
submit_cells("search", _("Search"), "", _("Search items"), "default");

end_row();

end_table();

//Added by spyrax10
if ($_GET["itemgroup"] != '' && is_numeric($_GET['itemgroup'])) {
	display_heading("Category: " . get_category_name($category_id));
	br();
}
//

$sql = ($_GET['type'] == "pr"|| $_GET['type'] == "sales") ?
get_items_search(get_post("description"), @$_GET['type'], @$_GET['itemgroup'], @$_GET['supplier']) :
get_items_search(get_post("description"), @$_GET['type'], $category_id, @$_GET['supplier']);

$cols = array (
	_("Item Code") => array('fun' => 'item_code'),
	_("Description") => array('fun' => 'item_desc'),
	_("Avail Qty") => array('fun' => 'qoh_cell', 'align' => 'center'),
	_("Units") => array('fun' => 'item_unit', 'align' => 'center'),
	_("Category") => array('fun' => 'item_cat', 'align' => 'center'),
	_("Brand") => array('fun' => 'item_brand'),
	_("Suppliers") => array('fun' => 'item_suppliers'),
	_("Sub-Category") => array('fun' => 'item_sub_cat'),
	_("Classification") => array('fun' => 'item_class'),
	_("Unit Price") => array('align' => 'center', 'fun' => 'price_total'),
	array('insert' => true, 'fun' => 'select_cell', 'align' => 'center')
);


$table = &new_db_pager('item_tbl', $sql, $cols, null, null, 20);

$table->width = "98%";

display_db_pager($table);

end_form();

end_page(true);
