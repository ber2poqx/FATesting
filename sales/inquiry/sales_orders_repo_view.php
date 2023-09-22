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
$path_to_root = "../..";

include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$page_security = 'SA_SO_REPO_VIEW'; //Modified by spyrax10 18 Jun 2022

set_page_security( @$_POST['order_view_mode'],
	array(	'OutstandingOnly' => 'SA_SALESDELIVERY',
			'InvoiceTemplates' => 'SA_SALESINVOICE',
			'DeliveryTemplates' => 'SA_SALESDELIVERY',
			'PrepaidOrders' => 'SA_SALESINVOICE'),
	array(	'OutstandingOnly' => 'SA_SALESDELIVERY',
			'InvoiceTemplates' => 'SA_SALESINVOICE',
			'DeliveryTemplates' => 'SA_SALESDELIVERY',
			'PrepaidOrders' => 'SA_SALESINVOICE')
);

if (get_post('type'))
	$trans_type = $_POST['type'];
elseif (isset($_GET['type']) && $_GET['type'] == ST_SALESQUOTE)
	$trans_type = ST_SALESQUOTE;
else
	$trans_type = ST_SALESORDER;

if ($trans_type == ST_SALESORDER)
{
	if (isset($_GET['OutstandingOnly']) && ($_GET['OutstandingOnly'] == true))
	{
		$_POST['order_view_mode'] = 'OutstandingOnly';
		$_SESSION['page_title'] = _($help_context = "Search Outstanding Sales Orders");
	}
	elseif (isset($_GET['InvoiceTemplates']) && ($_GET['InvoiceTemplates'] == true))
	{
		$_POST['order_view_mode'] = 'InvoiceTemplates';
		$_SESSION['page_title'] = _($help_context = "Search Template for Invoicing");
	}
	elseif (isset($_GET['DeliveryTemplates']) && ($_GET['DeliveryTemplates'] == true))
	{
		$_POST['order_view_mode'] = 'DeliveryTemplates';
		$_SESSION['page_title'] = _($help_context = "Select Template for Delivery");
	}
	elseif (isset($_GET['PrepaidOrders']) && ($_GET['PrepaidOrders'] == true))
	{
		$_POST['order_view_mode'] = 'PrepaidOrders';
		$_SESSION['page_title'] = _($help_context = "Invoicing Prepayment Orders");
	}
	elseif (!isset($_POST['order_view_mode']))
	{
		$_POST['order_view_mode'] = false;
		$_SESSION['page_title'] = _($help_context = "Search All Sales Orders Repo");
	}
}
else
{
	$_POST['order_view_mode'] = "Quotations";
	$_SESSION['page_title'] = _($help_context = "Search All Sales Quotations");
}

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 600);
if (user_use_date_picker())
	$js .= get_js_date_picker();
page($_SESSION['page_title'], false, false, "", $js);
//---------------------------------------------------------------------------------------------
//	Query format functions
//
function check_overdue($row)
{
	global $trans_type;
	if ($trans_type == ST_SALESQUOTE)
		return (date1_greater_date2(Today(), sql2date($row['delivery_date'])));
	else
		return ($row['type'] == 0
			&& date1_greater_date2(Today(), sql2date($row['delivery_date']))
			&& ($row['TotDelivered'] < $row['TotQuantity']));
}

function view_link($dummy, $order_no)
{
	global $trans_type;
	return  get_customer_trans_view_str($trans_type, $order_no);
}

function prt_link($row)
{
	global $trans_type;
	if ($_SESSION["wa_current_user"]->can_access_page('SA_PRINT_SO')) {
		return print_document_link($row['order_no'], _("Print"), true, $trans_type, ICON_PRINT);
	}else {
		return null;
	}
}

function edit_link($row) 
{
	global $page_nested;

	if (is_prepaid_order_open($row['order_no']))
		return '';

	return $page_nested ? '' : trans_editor_link($row['trans_type'], $row['order_no']);
}

function edit_link2($row) 
{
	global $page_nested;

	if ($_SESSION["wa_current_user"]->can_access_page('SA_SALESORDER')) {
		if (is_prepaid_order_open($row['order_no'])) {
			$edit_link = '';
		}
		else {
			$edit_link = 
				$page_nested 
				|| $row['status'] == "Closed" 
				|| $row['status'] == "Cancelled" ? '' 
				: trans_editor_link2_repo($row['trans_type'], $row['order_no'], $row['payment_type']);
		}
	}else {
		$edit_link = '';
	}

	return $edit_link;
}
//Added by Albert 10/25/2021
function account_specialist_approval_link($row)
{
	global $page_nested;
	//modified by Albert 07/13/2022
	if ($_SESSION["wa_current_user"]->can_access_page('SA_SALES_ORDER_APPROVAL')) {
		return ($row["status"] == "Draft" || $row["status"] == "Approved") &&  $row["payment_type"] <> "CASH" ? pager_link(
			'Approval',
			"/sales/sales_order_approval_account_specialist.php?SONumber=" . $row["order_no"],
			ICON_DOC
		) :'';
	}else{
		return NULL;
	}
}

function dispatch_link($row)
{
	global $trans_type, $page_nested;

	if ($row['ord_payments'] + $row['inv_payments'] < $row['prep_amount'])
		return '';

	if ($trans_type == ST_SALESORDER)
	{
		if ($row['TotDelivered'] < $row['TotQuantity'] && !$page_nested)
			if ($row["status"] == "Closed") {
				return pager_link( _("Dispatch"),
				"/sales/customer_delivery.php?OrderNumber=" .$row['order_no'], ICON_DOC);
			} else {
				return '';
			}
		else
			return '';
	}		
	else
		return pager_link( _("Sales Order"),
			"/sales/sales_order_entry.php?OrderNumber=" .$row['order_no'], ICON_DOC);
}

function invoice_link($row)
{
	global $trans_type;
	$path =  $row["payment_type"] == "CASH" ? 'sales_invoice_cash' : 'sales_order_entry';
	
	if ($_SESSION["wa_current_user"]->can_access_page('SA_SALESINVOICEREPO')) {
		if ($trans_type == ST_SALESORDER &&  $row["invoice_type"]== "repo")
			// albert invoive repo
			return $row["status"] == "Approved" && $row["invoice_type"]== "repo"? pager_link( _("Invoice Repo"),
			$row["payment_type"] == "CASH" ? "/sales/si_repo_cash.php?NewInvoiceRepo=" .$row["order_no"] 
			: "/sales/si_repo_install.php?NewInvoiceRepo=" .$row["order_no"], ICON_DOC) : '';
	
	}else{

		return null;
	}
}

function delivery_link($row)
{
  return pager_link( _("Delivery"),
	"/sales/sales_order_entry.php?NewDelivery=" .$row['order_no'], ICON_DOC);
}

function order_link($row)
{
  return pager_link( _("Sales Order"),
	"/sales/sales_order_entry.php?NewQuoteToSalesOrder=" .$row['order_no'], ICON_DOC);
}

function tmpl_checkbox($row)
{
	global $trans_type, $page_nested;

	if ($trans_type == ST_SALESQUOTE || !check_sales_order_type($row['order_no']))
		return '';

	if ($page_nested)
		return '';
	$name = "chgtpl" .$row['order_no'];
	$value = $row['type'] ? 1:0;

// save also in hidden field for testing during 'Update'

 return checkbox(null, $name, $value, true,
 	_('Set this order as a template for direct deliveries/invoices'))
	. hidden('last['.$row['order_no'].']', $value, false);
}

function invoice_prep_link($row)
{
	// invoicing should be available only for partially allocated orders
	return 
		$row['inv_payments'] < $row['total'] ?
		pager_link($row['ord_payments']  ? _("Prepayment Invoice") : _("Final Invoice"),
		"/sales/customer_invoice.php?InvoicePrepayments=" .$row['order_no'], ICON_DOC) : '';
}

function update_status_link($row)
{
	global $page_nested;
	
	if ($_SESSION["wa_current_user"]->can_access_page('SA_SALES_ORDER_UPDATE_STATUS')) {
		$status_link = 
		$row["status"] == "Draft" ? pager_link(
			$row['status'],
			"/sales/sales_order_update_status.php?SONumber=" . $row["order_no"]."&repo=1",
			false
		) : $row["status"];
	}
	else {
		$status_link = $row["status"];
	}

	return $status_link;
}
//Added by Albert
function cancel_link($row) {
	global $page_nested;
	if ($_SESSION["wa_current_user"]->can_access_page('SA_SALES_ORDER_UPDATE_STATUS')) {

		return ($row["status"] == "Approved" || $row["status"] == "Draft")? pager_link(
			'Cancel This Transaction',
			"/sales/sales_order_update_status.php?SONumber=" . $row["order_no"] . "&cancel=1",
			ICON_CANCEL
		) :'';
	}else{
		return null;
	}
}

function category_name($row)
{
	return get_category_name($row["category_id"]);
}

$id = find_submit('_chgtpl');
if ($id != -1)
{
	sales_order_set_template($id, check_value('chgtpl'.$id));
	$Ajax->activate('orders_tbl');
}

if (isset($_POST['Update']) && isset($_POST['last'])) {
	foreach($_POST['last'] as $id => $value)
		if ($value != check_value('chgtpl'.$id))
			sales_order_set_template($id, !check_value('chgtpl'.$id));
}

$show_dates = !in_array($_POST['order_view_mode'], array('OutstandingOnly', 'InvoiceTemplates', 'DeliveryTemplates'));
//---------------------------------------------------------------------------------------------
//	Order range form
//
if (get_post('_OrderNumber_changed') || get_post('_OrderReference_changed')) // enable/disable selection controls
{
	$disable = get_post('OrderNumber') !== '' || get_post('OrderReference') !== '';

  	if ($show_dates) {
		$Ajax->addDisable(true, 'OrdersAfterDate', $disable);
		$Ajax->addDisable(true, 'OrdersToDate', $disable);
	}

	$Ajax->activate('orders_tbl');
}

start_form();

start_table(TABLESTYLE_NOBORDER);
start_row();
ref_cells(_("#:"), 'OrderNumber', '',null, '', true);
ref_cells(_("Ref"), 'OrderReference', '',null, '', true);
if ($show_dates)
{
  	date_cells(_("from:"), 'OrdersAfterDate', '', null, -user_transaction_days());
  	date_cells(_("to:"), 'OrdersToDate', '', null, 1);
}
// locations_list_cells(_("Location:"), 'StockLocation', null, true, true);

if($show_dates) {
	end_row();
	end_table();

	start_table(TABLESTYLE_NOBORDER);
	start_row();
}
stock_items_list_cells(_("Item:"), 'SelectStockFromList', null, true, true);

if (!$page_nested)
	customer_list_cells(_("Select a customer: "), 'customer_id', null, true, true);
if ($trans_type == ST_SALESQUOTE)
	check_cells(_("Show All:"), 'show_all');

submit_cells('SearchOrders', _("Search"),'',_('Select documents'), 'default');
hidden('order_view_mode', $_POST['order_view_mode']);
hidden('type', $trans_type);

end_row();

end_table(1);

if ($_SESSION["wa_current_user"]->can_access_page('SA_SALESORDER')) {
start_table(TABLESTYLE_NOBORDER);
start_row();
ahref_cell(_("New Sales Order Repo Installment"), "../si_repo_install.php?NewOrder=0");
ahref_cell(_("New Sales Order Repo Cash"), "../si_repo_cash.php?NewOrder=0");
end_row();
end_table(1);
}
//---------------------------------------------------------------------------------------------
//	Orders inquiry table
//
$sql = get_sql_for_sales_orders_repo_view($trans_type, get_post('OrderNumber'), get_post('order_view_mode'),
	get_post('SelectStockFromList'), get_post('OrdersAfterDate'), get_post('OrdersToDate'), get_post('OrderReference'), get_post('StockLocation'),
	get_post('customer_id'));

if ($trans_type == ST_SALESORDER)
	$cols = array(
		_("Order #") => array('fun'=>'view_link', 'align'=>'right', 'ord' =>''),
		_("Ref") => array('type' => 'sorder.reference', 'ord' => '') ,
		_("Status") => array('insert' => true, 'fun' => 'update_status_link'),
		'dummy' => 'skip',
		_("Invoice Type"),
		_("Customer") => array('type' => 'debtor.name' , 'ord' => '') ,
		_("Payment Type"), 
		_("Category"), 
		_("Approval Remarks"),
		_("Cust Order Ref"),
		_("Order Date") => array('type' =>  'date', 'ord' => ''),
		_("Required By") =>array('type'=>'date', 'ord'=>''),
		_("Delivery To"), 
		_("Order Total") => array('type'=>'amount', 'ord'=>''),
		'Type' => 'skip',
		_("Currency") => array('align'=>'center'),
		_("") => array('insert'=>true, 'fun'=>'edit_link2')
	);
else
	$cols = array(
		_("Quote #") => array('fun'=>'view_link', 'align'=>'right', 'ord' => ''),
		_("Ref"),
		_("Customer"),
		_("Branch"), 
		_("Cust Order Ref"),
		_("Quote Date") => 'date',
		_("Valid until") =>array('type'=>'date', 'ord'=>''),
		_("Delivery To"), 
		_("Quote Total") => array('type'=>'amount', 'ord'=>''),
		'Type' => 'skip',
		_("Currency") => array('align'=>'center')
	);
if ($_POST['order_view_mode'] == 'OutstandingOnly') {
	array_append($cols, array(
		array('insert'=>true, 'fun'=>'edit_link'),
		array('insert'=>true, 'fun'=>'dispatch_link'),
		array('insert'=>true, 'fun'=>'prt_link')));

} elseif ($_POST['order_view_mode'] == 'InvoiceTemplates') {
	array_substitute($cols, 4, 1, _("Description"));
	array_append($cols, array( array('insert'=>true, 'fun'=>'invoice_link')));

} else if ($_POST['order_view_mode'] == 'DeliveryTemplates') {
	array_substitute($cols, 4, 1, _("Description"));
	array_append($cols, array(
			array('insert'=>true, 'fun'=>'delivery_link'))
	);
} else if ($_POST['order_view_mode'] == 'PrepaidOrders') {
	array_append($cols, array(
			array('insert'=>true, 'fun'=>'invoice_prep_link'))
	);

} elseif ($trans_type == ST_SALESQUOTE) {
	 array_append($cols,array(
					array('insert'=>true, 'fun'=>'edit_link'),
					array('insert'=>true, 'fun'=>'order_link'),
					array('insert'=>true, 'fun'=>'prt_link')));
} elseif ($trans_type == ST_SALESORDER) {
	 array_append($cols,array(
			// _("Tmpl") => array('insert'=>true, 'fun'=>'tmpl_checkbox'),
					array('insert'=>true, 'fun'=>'account_specialist_approval_link'),
					array('insert'=>true, 'fun'=>'invoice_link'),
					array('insert'=>true, 'fun'=>'dispatch_link'),
					array('insert'=>true, 'fun'=>'cancel_link'),
					array('insert'=>true, 'fun'=>'prt_link')));
};


$table =& new_db_pager('orders_tbl', $sql, $cols);
$table->set_marker('check_overdue', _("Marked items are overdue."));

$table->width = "80%";

display_db_pager($table);
submit_center('Update', _("Update"), true, '', null);

end_form();
end_page();