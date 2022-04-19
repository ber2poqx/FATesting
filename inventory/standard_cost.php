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
$page_security = 'SA_STANDARDCOST';

if (@$_GET['page_level'] == 1)
	$path_to_root = "../..";
else	
	$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");
include_once($path_to_root . "/includes/ui/items_cart.inc");

$js = "";
if ($SysPrefs->use_popup_windows && $SysPrefs->use_popup_search)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker()) 
	$js .= get_js_date_picker();
page(_($help_context = "Inventory Item SRP Amount"), false, false, "", $js);

//---------------------------------------------------------------------------------------------------

check_db_has_stock_items(_("There are no items defined in the system."));

check_db_has_SRP_area_types(_("There are no SRP types in the system. Please set up SRP types/area first."));

simple_page_mode(true);
//---------------------------------------------------------------------------------------------------
$input_error = 0;

if (isset($_GET['stock_id']))
{
	$_POST['stock_id'] = $_GET['stock_id'];
}
if (isset($_GET['Item']))
{
	$_POST['stock_id'] = $_GET['Item'];
}

if (!isset($_POST['curr_abrev']))
{
	$_POST['curr_abrev'] = get_company_currency();
}

//---------------------------------------------------------------------------------------------------
$action = $_SERVER['PHP_SELF'];
if ($page_nested)
	$action .= "?stock_id=".get_post('stock_id');
start_form(false, false, $action);

if (!isset($_POST['stock_id']))
	$_POST['stock_id'] = get_global_stock_item();

if (!$page_nested)
{
	echo "<center>" . _("Item:"). "&nbsp;";
	//modified on feb. 24, 2021 to view all setup stocks by progjr
	//echo sales_items_list('stock_id', $_POST['stock_id'], false, true, '', array('editable' => false));
	echo stock_items_list('stock_id', $_POST['stock_id'], false, true);
	echo "<hr></center>";
}
else
	br(2);
set_global_stock_item($_POST['stock_id']);

//----------------------------------------------------------------------------------------------------

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{

	if (!check_num('stdcost', 0))
	{
		$input_error = 1;
		display_error( _("The cost entered must be numeric."));
		set_focus('stdcost');
	}elseif ($_POST['supplier_id'] == 0)
	{
		$input_error = 1;
		display_error(_("Please select supplier."));
		set_focus('supplier_id');
		return false;
	}
	elseif ($_POST['srptype_id'] == 0)
	{
		$input_error = 1;
		display_error(_("Please select SRP type first."));
		set_focus('srptype_id');
		return false;
	}
	/*elseif ($_POST['stdcost'] == 0)
	{
		$input_error = 1;
		display_error(_("Cost must be greater than 0."));
		set_focus('stdcost');
		return false;
	}*/
   	elseif ($Mode == 'ADD_ITEM' && get_stock_stdcost_type_currency($_POST['stock_id'], $_POST['srptype_id'], $_POST['curr_abrev'],$_POST['supplier_id']))
   	{
      	$input_error = 1;
      	display_error( _("The standard cost for this item, area policy and currency has already been added."));
		set_focus('supplier_id');
	}

	if ($input_error != 1)
	{
    	if ($selected_id != -1) 
		{
			//editing an existing cost
			update_item_stdcost($selected_id, $_POST['srptype_id'], $_POST['curr_abrev'], input_num('stdcost'), $_POST['supplier_id'], date2sql($_POST['date_epic']));
			
			//for price archiving
			update_pricehistory($_POST['stock_id'], $_POST['supplier_id'], 0, 0, 0, $_POST['srptype_id'], 0, 'SRPPLCY');
			add_pricehistory($_POST['stock_id'], input_num('stdcost',0), $selected_id, $_POST['supplier_id'], 0, 0, 0, $_POST['srptype_id'], 0, 'SRPPLCY', date("Y-m-d H:i:s"), date2sql(get_post('date_epic')));
			
			$msg = _("This cost has been updated.");
		}
		else
		{
			add_item_stdcost($_POST['stock_id'], $_POST['srptype_id'], $_POST['curr_abrev'], input_num('stdcost'), $_POST['supplier_id'], date2sql($_POST['date_epic']));
			
			//for price archiving
			$lastInsrtID = db_insert_id();
			update_pricehistory($_POST['stock_id'], $_POST['supplier_id'], 0, 0, 0, $_POST['srptype_id'], 0, 'SRPPLCY');
			add_pricehistory($_POST['stock_id'], input_num('stdcost',0), $lastInsrtID, $_POST['supplier_id'], 0, 0, 0, $_POST['srptype_id'], 0, 'SRPPLCY', date("Y-m-d H:i:s"), date2sql(get_post('date_epic')));
			
			$msg = _("The new cost has been added.");
		}
		display_notification($msg);
		$Mode = 'RESET';
	}
}

//------------------------------------------------------------------------------------------------------

if ($Mode == 'Delete')
{
	//the link to delete a selected record was clicked
	delete_item_stdcost($selected_id);

	//for price archiving
	remove_id_pricehistory($selected_id, 'SRPPLCY'); //, 'plcysrp_id'

	display_notification(_("The selected cost has been deleted."));
	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	$selected_id = -1;
	$_POST['stdcost'] = "";
	$_POST['supplier_id'] = "";
}

if (list_updated('stock_id')) {
	$Ajax->activate('stdcost_table');
	$Ajax->activate('stdcost_details');
}
if (list_updated('stock_id') || isset($_POST['_curr_abrev_update']) || isset($_POST['_srptype_id_update'])) {
	// after change of stock, currency or srp type selector
	// display default calculated cost for new settings. 
	// If we have this cost already in db it is overwritten later.
	unset($_POST['stdcost']);
	$Ajax->activate('stdcost_details');
}

//---------------------------------------------------------------------------------------------------

$srp_list = get_stdcost($_POST['stock_id']);

div_start('stdcost_table');
start_table(TABLESTYLE, "width='50%'");

$th = array(_("Supplier"), _("Currency"), _("Cost Code"), _("Standard Cost"), "E", "D");
table_header($th);
$k = 0; //row colour counter
$calculated = false;
while ($myrow = db_fetch($srp_list))
{
	alt_table_row_color($k);

	label_cell($myrow["supp_name"]);
	label_cell($myrow["curr_abrev"]);
    label_cell($myrow["srp_type"]);
    amount_cell($myrow["standard_cost"]);
 	edit_button_cell("Edit".$myrow['id'], _("Edit"));
 	delete_button_cell("Delete".$myrow['id'], _("Delete"));
    end_row();
}
end_table();

if (db_num_rows($srp_list) == 0)
{
	if (get_company_pref('add_pct') != -1)
		$calculated = true;
	display_note(_("There are no standard cost set up for this part."), 1);
}
div_end();
//------------------------------------------------------------------------------------------------

if ($Mode == 'Edit')
{
	$myrow = get_stock_stdcost($selected_id);

	$_POST['supplier_id'] = $myrow["supplier_id"];
	$_POST['curr_abrev'] = $myrow["curr_abrev"];
	$_POST['srptype_id'] = $myrow["srptype_id"];
	$_POST['stdcost'] = price_format($myrow["standard_cost"]);
	$_POST['date_epic'] = sql2date($myrow["date_epic"]); //Added by albert 04/18/2022
}

echo "<br>";
hidden('selected_id', $selected_id);

div_start('stdcost_details');
start_table(TABLESTYLE2);

supplier_list_row(_("Supplier:"), 'supplier_id', null, false, true);
currencies_list_row(_("Currency:"), 'curr_abrev', null, true);
srp_types_row(_("SRP Type:"), 'srptype_id', null, true, false, $_POST['supplier_id']);

if (!isset($_POST['stdcost'])) {
	$_POST['stdcost'] = price_format(get_kit_price(get_post('stock_id'), get_post('curr_abrev'),get_post('srptype_id')));
}

$kit = get_item_code_dflts($_POST['stock_id']);
small_amount_row(_("Standard Cost:"), 'stdcost', null, '', _('per') .' '.$kit["units"]);
/*Added by albert 04/18/2022*/
date_row(
	"Date Epic:",
	'date_epic',
	_('Date of Effectivity'),
	'',
	0,
	0,
	0,
	null,
	true
);
/* */

end_table(1);
if ($calculated)
	display_note(_("The cost is calculated."), 0, 1);

submit_add_or_update_center($selected_id == -1, '', 'both');

div_end();

end_form();
end_page();