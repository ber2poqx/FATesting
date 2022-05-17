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
/*
Price History
Created by Albert 05/17/2022

*/

$page_security = 'SA_SCASHPRICE';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");

$js = "";
if ($SysPrefs->use_popup_windows && $SysPrefs->use_popup_search)
	$js .= get_js_open_window(900, 500);

if (isset($_GET['price_code']) && isset($_GET['stock_id']))
	$_POST['price_code'] = $_GET['price_code'];
	$_POST['stock_id'] = $_GET['stock_id'];



page(_($help_context = "Inventory Item Status"), isset($_GET['stock_id']), false, "", $js);

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/inventory/includes/inventory_db.inc");
include_once($path_to_root . "/includes/db/manufacturing_db.inc");

if (list_updated('stock_id')) 
	$Ajax->activate('status_tbl');
//----------------------------------------------------------------------------------------------------

check_db_has_stock_items(_("There are no items defined in the system."));

function get_price_history($price_code, $stock_id){


		$sql = "SELECT 
					case when a.plcycashprice_id = b.id then b.scash_type
						when a.plcyprice_id = c.id then c.sales_type
						when a.plcycost_id = d.id then d.cost_type
						when a.plcysrp_id = e.id then e.srp_type
						else ''end as price_code,
					a.*

				FROM ".TB_PREF."price_cost_archive a
				Left JOIN ".TB_PREF."sales_cash_type b on a.plcycashprice_id = b.id
				Left JOIN ".TB_PREF."sales_types c on a.plcyprice_id = c.id
				Left JOIN ".TB_PREF."supp_cost_types d on a.plcycost_id = d.id
				Left JOIN ".TB_PREF."item_srp_area_types e on a.plcysrp_id = e.id
				where a.stock_id =".db_escape($stock_id);

		$sql.= " AND (b.scash_type like ".db_escape($price_code)."
					OR c.sales_type like ".db_escape($price_code)."
					OR d.cost_type like ".db_escape($price_code)." 
					OR e.srp_type like ".db_escape($price_code).")";

		$sql.= " order by a.date_defined desc, a.id desc";


return db_query($sql,"The Price History could not be retreived");

}



start_form();

if (!isset($_POST['stock_id']))
	$_POST['stock_id'] = get_global_stock_item();
if (!$page_nested)
{
	echo "<center> " . _("Item:"). " ";
	echo stock_costable_items_list('stock_id', $_POST['stock_id'], false, true);
}
echo "<br>";

echo "<hr></center>";

set_global_stock_item($_POST['stock_id']);

$mb_flag = get_mb_flag($_POST['stock_id']);
$kitset_or_service = false;

div_start('status_tbl');
if (is_service($mb_flag))
{
	display_note(_("This is a service and cannot have a stock holding, only the total quantity on outstanding sales orders is shown."), 0, 1);
	$kitset_or_service = true;
}

$loc_details = get_price_history( $_POST['price_code'] ,$_POST['stock_id']);

start_table(TABLESTYLE);

$th = array(_("Item_code"), _("Price code"), _("Amount"), _("Created Date"), _("Effectivity date"));

table_header($th);
$j = 1;
$k = 0; //row colour counter

while ($myrow = db_fetch($loc_details))
{

	alt_table_row_color($k);

		label_cell($myrow["stock_id"]);
		label_cell($myrow["price_code"]);
		label_cell($myrow["amount"]);
		label_cell($myrow["date_defined"]);
		label_cell($myrow["date_epic"]);

		
        end_row();

	$j++;

}

end_table();
div_end();
end_form();
end_page();

