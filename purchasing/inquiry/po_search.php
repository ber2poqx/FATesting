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
$page_security = 'SA_RR_LIST'; //Modified by spyrax10 24 Jun 2022
$path_to_root = "../..";
include($path_to_root . "/includes/db_pager.inc");
include($path_to_root . "/includes/session.inc");

include($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
page(_($help_context = "Search Outstanding Purchase Orders"), false, false, "", $js);

if (isset($_GET['order_number']))
{
	$_POST['order_number'] = $_GET['order_number'];
}
//-----------------------------------------------------------------------------------
// Ajax updates
//
if (get_post('SearchOrders')) 
{
	$Ajax->activate('orders_tbl');
} elseif (get_post('_order_number_changed')) 
{
	$disable = get_post('order_number') !== '';

	$Ajax->addDisable(true, 'OrdersAfterDate', $disable);
	$Ajax->addDisable(true, 'OrdersToDate', $disable);
	$Ajax->addDisable(true, 'StockLocation', $disable);
	$Ajax->addDisable(true, '_SelectStockFromList_edit', $disable);
	$Ajax->addDisable(true, 'SelectStockFromList', $disable);

	if ($disable) {
		$Ajax->addFocus(true, 'order_number');
	} else
		$Ajax->addFocus(true, 'OrdersAfterDate');

	$Ajax->activate('orders_tbl');
}


//---------------------------------------------------------------------------------------------

start_form();

start_table(TABLESTYLE_NOBORDER);
start_row();
ref_cells(_("#:"), 'order_number', '',null, '', true);

date_cells(_("from:"), 'OrdersAfterDate', '', null, -user_transaction_days());
date_cells(_("to:"), 'OrdersToDate');

locations_list_cells(_("Location:"), 'StockLocation', null, true);
end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

stock_items_list_cells(_("Item:"), 'SelectStockFromList', null, true);

//Modified by spyrax10
supplier_list_cells(_("Select a supplier: "), 'supplier_id', null, true, true, true, false, false, null);

submit_cells('SearchOrders', _("Search"),'',_('Select documents'), 'default');
end_row();
end_table(1);
//---------------------------------------------------------------------------------------------
function trans_view($trans)
{
	return get_trans_view_str(ST_PURCHORDER, $trans["reference"]);
}

function trans_ref_view($trans)
{
	return get_trans_view_str(
		$trans["is_consign"] == "Non-Consignment" ? ST_PURCHREQUEST : ST_RECEIVECONSIGN,
		$trans["trans_ref"]
	);
}

function edit_link($row) 
{
	return trans_editor_link(ST_PURCHORDER, $row["order_no"]);
}

//Modified by spyrax10 20 Aug 2022
function prt_link($row) {
	if ($_SESSION["wa_current_user"]->can_access_page('SA_RR_PRINT')) {
		return print_document_link($row['order_no'], _("Print"), true, ST_PURCHORDER, ICON_PRINT);
	}
}

function receive_link($row) {
	if ($_SESSION["wa_current_user"]->can_access_page('SA_GRN')) {
		return pager_link( _("Receive"),
			"/purchasing/po_receive_items.php?PONumber=" . $row["order_no"], ICON_RECEIVE
		);
	}
}

function receive_new_link($row) {
	if ($_SESSION["wa_current_user"]->can_access_page('SA_GRN')) {
		return pager_link( _("Receive"),
			"/purchasing/po_receive_items_new.php?PONumber=" . $row["order_no"], ICON_RECEIVE
		);
	}
}
//

function check_overdue($row)
{
	return $row['OverDue']==1;
}
//---------------------------------------------------------------------------------------------

//figure out the sql required from the inputs available
$sql = get_sql_for_po_search(get_post('OrdersAfterDate'), get_post('OrdersToDate'), get_post('supplier_id'), get_post('StockLocation'),
	$_POST['order_number'], get_post('SelectStockFromList'));

//$result = db_query($sql,"No orders were returned");

/*show a table of the orders returned by the sql */
$cols = array(
		_("#"), 
		_("PO #") => array('fun'=>'trans_view', 'ord'=>''), 
		_("Supplier") => array('ord'=>''),
		_("Category"), //Added by spyrax10 19/Oct/2021
		_("Location"),
		_("Is Consignment"),
		_("Trans Reference #") => array('fun'=>'trans_ref_view', 'ord'=>''), 
		_("Order Date") => array('name'=>'ord_date', 'type'=>'date', 'ord'=>'desc'),
		_("Currency") => array('align'=>'center'), 
		_("Order Total") => 'amount',
		array('insert'=>true, 'fun'=>''),
		// array('insert'=>true, 'fun'=>'receive_link'),
		array('insert'=>true, 'fun'=>'receive_new_link'),
		array('insert'=>true, 'fun'=>'prt_link')
);

if (get_post('StockLocation') != ALL_TEXT) {
	$cols[_("Location")] = 'skip';
}

$table =& new_db_pager('orders_tbl', $sql, $cols, null, null, 25);
$table->set_marker('check_overdue', _("Marked orders have overdue items."));

$table->width = "90%"; //Modified by spyrax10 19/Oct/2021

display_db_pager($table);

end_form();
end_page();
