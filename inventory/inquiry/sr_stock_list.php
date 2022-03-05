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
$page_security = "SA_SRSTOCKLIST";
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/inventory/includes/db/items_db.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");

add_js_ufile($path_to_root . "/inventory/includes/js/sr_stock_list.js");

page(_($help_context = "Items"), true, false, "");
if (get_post("search")) {
	$Ajax->activate("item_tbl");
}

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);

start_row();

text_cells(_("#"), "searchval");

submit_cells("search", _("Search"), "", _("Search items"), "default");

end_row();

end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();


global $Ajax;
$Ajax->activate('item_tbl');

end_row();
end_table();

end_form();

div_start("item_tbl");
start_table(TABLESTYLE);

/* */

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
	_("Unit Price"),
	_("Unit Cost")
);
table_header($th);

$k = 0;
$name = $_GET["client_id"];
$category_id = $_GET["category"];
$loc_code = $_GET['location'];

$serial_input = "serialeng_no";
$serialized = $_GET['serialized'];
$repo = $_GET["repo"];
$result = [];
$result = get_available_item_for_sr($category_id, $serialized, get_post('searchval'), $loc_code, $repo);
while ($myrow = db_fetch_assoc($result)) {
	alt_table_row_color($k);
	$value = $myrow['stock_id'];

	//Modified by spyrax10
	$price = $repo == "new" ? Get_Policy_Installment_Price($loc_code, $category_id, $myrow["stock_id"]) : 
		Get_Repo_Installment_Price($myrow["stock_id"], $myrow["serialeng_no"]);
	//

	$text = $myrow['description'];
	$ref_doc = $myrow['reference'];

	if ($serialized == 1) {
		ahref_cell(_("Select"), 'javascript:void(0)', '', 'setComboItem(window.opener.document, 
			"' . $name . '",  "' . $value . '", "' . $text . '", "' . $myrow["serialeng_no"] . '", "' . $myrow["chassis_no"] . '"
			, "' . $myrow["color_code"] . '", "' . $ref_doc . '", "' . $price . '", "' . $myrow["standard_cost"] . '",
			"' . $myrow["trans_no"] . '", "' . $myrow["type"] . '", "' . $myrow["qoh"] . '")');
	} else {
		ahref_cell(_("Select"), 'javascript:void(0)', '', 'setComboItem(window.opener.document, 
			"' . $name . '",  "' . $value . '", "' . $text . '", "", "", "", "' . $ref_doc . '", 
			"' . $price . '", "' . $myrow["standard_cost"] . '",
			"' . $myrow["trans_no"] . '", "' . $myrow["type"] . '", "' . $myrow["qoh"] . '")'); //modified by spyrax10
	}
	label_cell($myrow["reference"]);
	label_cell($myrow["units"]);
	label_cell($myrow["stock_id"]);
	label_cell($myrow["description"]);
	label_cell(get_color_description($myrow["color_code"], $myrow["stock_id"]));
	label_cell($myrow["serialeng_no"]);
	label_cell($myrow["chassis_no"]);
	label_cell($myrow["category"]);
	label_cell($myrow["brand"]);
	label_cell($myrow["qoh"]);
	label_cell(price_format($price));
	label_cell($myrow["standard_cost"]);
	end_row();
}

end_table(1);


div_end();
end_page(true);
