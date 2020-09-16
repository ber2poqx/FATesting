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
$page_security = "SA_ITEM";
$path_to_root = "../..";
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

if(get_post("search")) {
  $Ajax->activate("item_tbl");
}

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);

start_row();

text_cells(_("Description"), "description");
stock_categories_list_cells(_("Category"), "category",null,_("All Categories"));
//stock_brand_list_row(_('Brand:'), 'brand', null,_('All Brand'));
submit_cells("search", _("Search"), "", _("Search items"), "default");

end_row();

end_table();

end_form();

div_start("item_tbl");
start_table(TABLESTYLE);

$th = array("", _("Item Code"), _("Description"), _("Avail Qty"), _("Units"), _("Category"), _("Brand"), _("Suppliers"), _("Sub-Category"), _("Classification"));
table_header($th);

$k = 0;
$name = $_GET["client_id"];
$result = get_items_search(get_post("description"), @$_GET['type'],get_post("category"), @$_GET['supplier']);

while ($myrow = db_fetch_assoc($result))
{
	alt_table_row_color($k);
	$value = $myrow['item_code'];
	 //Added Herald - 09-01-2020 for available qty 
    $loc_details = get_loc_details($myrow['stock_id']);
    $myrow1 = db_fetch($loc_details);

    $demand_qty = get_demand_qty($myrow['stock_id'], 0);
	//$demand_qty += get_demand_asm_qty($myrow['stock_id'], $myrow1["loc_code"]);
    $demand_qty += get_demand_asm_qty($myrow['stock_id'],0);
    $qoh = get_qoh_on_date($myrow['stock_id'], 0);

	if ($mode != 0) {
		$text = $myrow['description'];
  		ahref_cell(_("Select"), 'javascript:void(0)', '', 'setComboItem(window.opener.document, "'.$name.'",  "'.$value.'", "'.$text.'")');
	}
	else {
  		ahref_cell(_("Select"), 'javascript:void(0)', '', 'selectComboItem(window.opener.document, "'.$name.'", "'.$value.'")');
	}
  	label_cell($myrow["item_code"]);
	label_cell($myrow["description"]);
	qty_cell($qoh - $demand_qty, false, $dec);
	label_cell($myrow["units"]);
	label_cell($myrow["category"]);
	label_cell($myrow["brand_name"]);
	$suppl = ($myrow["manufacturer_name"]=='NULL')?'':$myrow["manufacturer_name"];
	label_cell($suppl);
	$subcar = ($myrow["distributor_name"]=='NULL')?'':$myrow["distributor_name"];
	label_cell($subcar);
	$classification = ($myrow["importer_name"]=='NULL')?'':$myrow["importer_name"];
	label_cell($classification);
	end_row();
}

end_table(1);

div_end();
end_page(true);
