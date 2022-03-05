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
$page_security = "SA_SISTOCKLIST";
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/inventory/includes/db/items_db.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");

$mode = get_company_pref('no_item_list');
if ($mode != 0)
	$js = si_get_js_set_combo_item();
else
	$js = get_js_select_combo_item();

page(_($help_context = "Items"), true, false, "", $js);

if (get_post("search")) {
	$Ajax->activate("item_tbl");
}

if (get_post('serialized') == 1) {
	$_POST['serialized'] = 1;
} else {
	$_POST['serialized'] = 0;
}

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);

start_row();

text_cells(_("#"), "searchval");

check_cells(_("Serialized"), 'serialized', $_POST['serialized'], true);
submit_cells("search", _("Search"), "", _("Search items"), "default");

end_row();

end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

if (get_post('promo_cb') == 1) {
	$_POST['promo_cb'] = 1;
} else {
	$_POST['promo_cb'] = 0;
}


global $Ajax;
$Ajax->activate('item_tbl');

check(_("Free Items"), 'promo_cb', $_POST['promo_cb'], true);

end_row();
end_table();

end_form();

div_start("item_tbl");
start_table(TABLESTYLE);

/* */

$th = array();
if ($_POST['serialized'] == 1) {
	$th = array(
		"",
		_("Transaction Ref #"),
		_("Units"),
		_("Item Code"),
		_("Description"),
		_("Color"),
		_("Serial/Engine No"),
		_("Chassis No"),
		_("Category"),
		_("Brand"),
		_("Qty"),
	);
}

if ($_POST['promo_cb'] == 1 || $_POST['serialized'] == 0) {
	$th = array(
		"",
		_("Units"),
		_("Item Code"),
		_("Description"),
		_("Avail Qty"),
		_("Category"),
		_("Brand"),
	);
}
table_header($th);

$k = 0;
$name = $_GET["client_id"];
$category_id = $_GET["category"];
//Added by spyrax10
$loc_code = $_GET['location'];

$serial_input = "serialeng_no";
$result = get_available_item_for_si(
	get_post('promo_cb') == 1 ? 17 : $category_id,
	$_POST['serialized'],
	$_POST['promo_cb'],
	get_post("searchval"),
	$loc_code //Added by spyrax10
);
while ($myrow = db_fetch_assoc($result)) {

	//Modified by spyrax10
	alt_table_row_color($k);
	$value = $myrow['stock_id'];

	if ($mode != 0) {
		$text = $myrow['description'];
		if (get_post('promo_cb') == 0 && get_post('serialized') == 1) {
			ahref_cell(_("Select"), 'javascript:void(0)', '', 'setComboItem(window.opener.document, 
			"' . $name . '",  "' . $value . '", "' . $text . '", "' . $myrow["serialeng_no"] . '", "' . $myrow["chassis_no"] . '"
			, "' . $myrow["color_code"] . '", "Regular")');
		} else if (get_post('promo_cb') == 0 && get_post('serialized') == 0) {
			ahref_cell(_("Select"), 'javascript:void(0)', '', 'setComboItem(window.opener.document, 
			"' . $name . '",  "' . $value . '", "' . $text . '", "", "", "", "Regular")'); //modified by spyrax10
		} else {
			ahref_cell(_("Select"), 'javascript:void(0)', '', 'setComboItem(window.opener.document, 
			"' . $name . '",  "' . $value . '", "' . $text . '", "", "", "", "Promo")');
		}
	} else {
		ahref_cell(_("Select"), 'javascript:void(0)', '', 'selectComboItem(window.opener.document, "' . $name . '", "' . $value . '")');
	}
	if (get_post('promo_cb') == 0 && get_post('serialized') == 1) {
		label_cell($myrow["reference"]);
	}
	label_cell($myrow["units"]);
	label_cell($myrow["stock_id"]);
	label_cell($myrow["description"]);

	if (get_post('promo_cb') == 0 && get_post('serialized') == 1) {
		label_cell(get_color_description($myrow["color_code"], $myrow["stock_id"]));
		label_cell($myrow["serialeng_no"]);
		label_cell($myrow["chassis_no"]);
	} else {
		$brcode = $db_connections[user_company()]["branch_code"];
		$loc_details = get_loc_details($myrow['stock_id']);
		$myrow1 = db_fetch($loc_details);
		$qoh = get_qoh_si($myrow['stock_id'], $loc_code);
		qty_cell($qoh, false);
	}
	label_cell($myrow["category"]);
	label_cell($myrow["brand"]);
	if (get_post('promo_cb') == 0 && get_post('serialized') == 1) {
		label_cell($myrow["qoh"]);
	}
	end_row();
}

end_table(1);


div_end();
end_page(true);
