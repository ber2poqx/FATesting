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
//-----------------------------------------------------------------------------
//
//	Entry/Modify Sales Quotations
//	Entry/Modify Sales Order
//	Entry Direct Delivery
//	Entry Direct Invoice
//
$path_to_root = "..";
$page_security = 'SA_SALESINVOICEREPO'; //Modified by spyrax10 13 Jul 2022

include_once($path_to_root . "/sales/includes/cart_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/ui/si_repo_cash_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_types_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/includes/cost_and_pricing.inc");

include_once($path_to_root . "/sales/includes/db/sales_installment_policy_db.inc");	// Added by Ronelle 2/25/2021

set_page_security(
	@$_SESSION['Items']->trans_type,
	array(
		ST_SALESORDER => 'SA_SALESORDER',
		ST_SALESQUOTE => 'SA_SALESQUOTE',
		ST_CUSTDELIVERY => 'SA_SALESDELIVERY',
		ST_SALESINVOICEREPO => 'SA_SALESINVOICEREPO'
	),
	array(
		'NewOrder' => 'SA_SALESORDER',
		'ModifyOrderNumber' => 'SA_SALESORDER',
		'AddedID' => 'SA_SALESORDER',
		'UpdatedID' => 'SA_SALESORDER',
		'NewQuotation' => 'SA_SALESQUOTE',
		'ModifyQuotationNumber' => 'SA_SALESQUOTE',
		'NewQuoteToSalesOrder' => 'SA_SALESQUOTE',
		'AddedQU' => 'SA_SALESQUOTE',
		'UpdatedQU' => 'SA_SALESQUOTE',
		'NewDelivery' => 'SA_SALESDELIVERY',
		'AddedDN' => 'SA_SALESDELIVERY',
		'NewInvoiceRepo' => 'SA_SALESINVOICEREPO',
		'AddedDI' => 'SA_SALESINVOICEREPO'
	)
);

$js = '';

if ($SysPrefs->use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}

if (user_use_date_picker()) {
	$js .= get_js_date_picker();
}
add_js_ufile($path_to_root . "/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root . '/js/serial_items_sales_entry.js');

if (isset($_GET['NewDelivery']) && is_numeric($_GET['NewDelivery'])) {

	$_SESSION['page_title'] = _($help_context = "Direct Sales Delivery");
	create_cart(ST_CUSTDELIVERY, $_GET['NewDelivery']);
} elseif (isset($_GET['NewInvoiceRepo']) && is_numeric($_GET['NewInvoiceRepo'])) {

	create_cart(ST_SALESINVOICEREPO, $_GET['NewInvoiceRepo']);

	if (isset($_GET['FixedAsset'])) {
		$_SESSION['page_title'] = _($help_context = "Fixed Assets Sale");
		$_SESSION['Items']->fixed_asset = true;
	} else
	$_SESSION['page_title'] = _($help_context = "Sales Invoice Repo Cash");
} elseif (isset($_GET['ModifyOrderNumber']) && is_numeric($_GET['ModifyOrderNumber'])) {

	$help_context = 'Modifying Sales Order';
	$_SESSION['page_title'] = sprintf(_("Modifying Sales Order # %d"), $_GET['ModifyOrderNumber']);
	create_cart(ST_SALESORDER, $_GET['ModifyOrderNumber']);
} elseif (isset($_GET['ModifyQuotationNumber']) && is_numeric($_GET['ModifyQuotationNumber'])) {

	$help_context = 'Modifying Sales Quotation';
	$_SESSION['page_title'] = sprintf(_("Modifying Sales Quotation # %d"), $_GET['ModifyQuotationNumber']);
	create_cart(ST_SALESQUOTE, $_GET['ModifyQuotationNumber']);
} elseif (isset($_GET['NewOrder'])) {

	$_SESSION['page_title'] = _($help_context = "New Sales Order Repo Cash");
	create_cart(ST_SALESORDER, 0);
} elseif (isset($_GET['NewQuotation'])) {

	$_SESSION['page_title'] = _($help_context = "New Sales Quotation Entry");
	create_cart(ST_SALESQUOTE, 0);
} elseif (isset($_GET['NewQuoteToSalesOrder'])) {
	$_SESSION['page_title'] = _($help_context = "Sales Order Entry");
	create_cart(ST_SALESQUOTE, $_GET['NewQuoteToSalesOrder']);
}

page($_SESSION['page_title'], false, false, "", $js);

if (isset($_GET['ModifyOrderNumber']) && is_prepaid_order_open($_GET['ModifyOrderNumber'])) {
	display_error(_("This order cannot be edited because there are invoices or payments related to it, and prepayment terms were used."));
	end_page();
	exit;
}
if (isset($_GET['ModifyOrderNumber']))
	check_is_editable(ST_SALESORDER, $_GET['ModifyOrderNumber']);
elseif (isset($_GET['ModifyQuotationNumber']))
	check_is_editable(ST_SALESQUOTE, $_GET['ModifyQuotationNumber']);

//-----------------------------------------------------------------------------

if (list_updated('branch_id')) {
	// when branch is selected via external editor also customer can change
	$br = get_branch(get_post('branch_id'));
	$_POST['customer_id'] = $br['debtor_no'];
	$Ajax->activate('customer_id');
}

if (isset($_GET['AddedID'])) {
	$order_no = $_GET['AddedID'];
	display_notification_centered(sprintf(_("Order # %d has been entered."), $order_no));

	submenu_view(_("&View This Order"), ST_SALESORDER, $order_no);

	submenu_print(_("&Print This Order"), ST_SALESORDER, $order_no, 'prtopt');
	submenu_print(_("&Email This Order"), ST_SALESORDER, $order_no, null, 1);
	set_focus('prtopt');

	submenu_option(_("Enter a &New Order"),	"/sales/si_repo_cash.php?NewOrder=0");

	display_footer_exit();
} elseif (isset($_GET['UpdatedID'])) {
	$order_no = $_GET['UpdatedID'];

	display_notification_centered(sprintf(_("Order # %d has been updated."), $order_no));

	submenu_view(_("&View This Order"), ST_SALESORDER, $order_no);

	submenu_print(_("&Print This Order"), ST_SALESORDER, $order_no, 'prtopt');
	submenu_print(_("&Email This Order"), ST_SALESORDER, $order_no, null, 1);
	set_focus('prtopt');

	display_footer_exit();
} elseif (isset($_GET['AddedQU'])) {
	$order_no = $_GET['AddedQU'];
	display_notification_centered(sprintf(_("Quotation # %d has been entered."), $order_no));

	submenu_view(_("&View This Quotation"), ST_SALESQUOTE, $order_no);

	submenu_print(_("&Print This Quotation"), ST_SALESQUOTE, $order_no, 'prtopt');
	submenu_print(_("&Email This Quotation"), ST_SALESQUOTE, $order_no, null, 1);
	set_focus('prtopt');

	submenu_option(
		_("Make &Sales Order Against This Quotation"),
		"/sales/si_repo_cash.php?NewQuoteToSalesOrder=$order_no"
	);

	submenu_option(_("Enter a New &Quotation"),	"/sales/si_repo_cash.php?NewQuotation=0");

	display_footer_exit();
} elseif (isset($_GET['UpdatedQU'])) {
	$order_no = $_GET['UpdatedQU'];

	display_notification_centered(sprintf(_("Quotation # %d has been updated."), $order_no));

	submenu_view(_("&View This Quotation"), ST_SALESQUOTE, $order_no);

	submenu_print(_("&Print This Quotation"), ST_SALESQUOTE, $order_no, 'prtopt');
	submenu_print(_("&Email This Quotation"), ST_SALESQUOTE, $order_no, null, 1);
	set_focus('prtopt');

	submenu_option(
		_("Make &Sales Order Against This Quotation"),
		"/sales/si_repo_cash.php?NewQuoteToSalesOrder=$order_no"
	);

	submenu_option(
		_("Select A Different &Quotation"),
		"/sales/inquiry/sales_orders_repo_view.php?type=" . ST_SALESQUOTE
	);

	display_footer_exit();
} elseif (isset($_GET['AddedDN'])) {
	$delivery = $_GET['AddedDN'];

	display_notification_centered(sprintf(_("Delivery # %d has been entered."), $delivery));

	echo "<center><a href='#' onclick='window_show($delivery);'>Serial Item Entry</a></center><br/>";
	display_note(viewer_link("Print Delivery Slip", "reports/delivery_slip.php?trans_no=$delivery"), 0, 1);
	display_note(viewer_link("Print Delivery Receipt", "reports/delivery_receipt.php?trans_no=$delivery"), 0, 1);
	display_note(viewer_link("Print Release Slip", "reports/release_slip.php?trans_no=$delivery"), 0, 1);


	submenu_view(_("&View This Delivery"), ST_CUSTDELIVERY, $delivery);

	submenu_print(_("&Print Delivery Note"), ST_CUSTDELIVERY, $delivery, 'prtopt');
	submenu_print(_("&Email Delivery Note"), ST_CUSTDELIVERY, $delivery, null, 1);
	submenu_print(_("P&rint as Packing Slip"), ST_CUSTDELIVERY, $delivery, 'prtopt', null, 1);
	submenu_print(_("E&mail as Packing Slip"), ST_CUSTDELIVERY, $delivery, null, 1, 1);
	set_focus('prtopt');

	display_note(get_gl_view_str(ST_CUSTDELIVERY, $delivery, _("View the GL Journal Entries for this Dispatch")), 0, 1);

	submenu_option(
		_("Make &Invoice Against This Delivery"),
		"/sales/customer_invoice.php?DeliveryNumber=$delivery"
	);

	if ((isset($_GET['Type']) && $_GET['Type'] == 1))
		submenu_option(
			_("Enter a New Template &Delivery"),
			"/sales/inquiry/sales_orders_repo_view.php?DeliveryTemplates=Yes"
		);
	else
		submenu_option(
			_("Enter a &New Delivery"),
			"/sales/si_repo_cash.php?NewDelivery=0"
		);

	display_footer_exit();
} elseif (isset($_GET['AddedDI'])) {
	$invoice = $_GET['AddedDI'];

	display_notification_centered(sprintf(_("Invoice # %d has been entered."), $invoice));

	submenu_view(_("&View This Invoice"), ST_SALESINVOICEREPO, $invoice);

	submenu_print(_("&Print Sales Invoice"), ST_SALESINVOICEREPO, $invoice . "-" . ST_SALESINVOICEREPO, 'prtopt');
	submenu_print(_("&Email Sales Invoice"), ST_SALESINVOICEREPO, $invoice . "-" . ST_SALESINVOICEREPO, null, 1);
	set_focus('prtopt');

	$row = db_fetch(get_allocatable_from_cust_transactions(null, $invoice, ST_SALESINVOICEREPO));
	if ($row !== false)
		submenu_print(_("Print &Receipt"), $row['type'], $row['trans_no'] . "-" . $row['type'], 'prtopt');

	display_note(get_gl_view_str(ST_SALESINVOICEREPO, $invoice, _("View the GL &Journal Entries for this Invoice")), 0, 1);

	display_footer_exit();
} else
	check_edit_conflicts(get_post('cart_id'));
//-----------------------------------------------------------------------------

function copy_to_cart()
{
	$cart = &$_SESSION['Items'];

	$cart->reference = $_POST['ref'];
	if ($cart->trans_type == ST_SALESINVOICEREPO){
		$cart->dr_ref = $_POST['dr_ref'];
		$cart->dr_ref_no = $_POST['dr_ref_no'];
	}
	$cart->Comments =  $_POST['Comments'];

	$cart->document_date = $_POST['OrderDate'];

	//new added by progjr on 2-20-2021
	if ($cart->trans_type == ST_SALESINVOICEREPO)
		$cart->document_ref = $_POST['document_ref'];
	$cart->salesman_id = $_POST['salesman_id'];
	$cart->category_id = $_POST['category_id'];

	$newpayment = false;

	if (isset($_POST['payment']) && ($cart->payment != $_POST['payment'])) {
		$cart->payment = $_POST['payment'];
		$cart->payment_terms = get_payment_terms($_POST['payment']);
		$newpayment = true;
	}
	if ($cart->payment_terms['cash_sale']) {
		if ($newpayment) {
			$cart->due_date = $cart->document_date;
			$cart->phone = $cart->cust_ref = $cart->delivery_address = '';
			$cart->ship_via = 0;
			$cart->deliver_to = '';
			$cart->prep_amount = 0;
		}
	} else {
		$cart->due_date = $_POST['delivery_date'];
		$cart->cust_ref = $_POST['cust_ref'];
		$cart->deliver_to = $_POST['deliver_to'];
		$cart->delivery_address = $_POST['delivery_address'];
		$cart->phone = $_POST['phone'];
		$cart->ship_via = $_POST['ship_via'];
		if (!$cart->trans_no || ($cart->trans_type == ST_SALESORDER && !$cart->is_started()))
			$cart->prep_amount = input_num('prep_amount', 0);
	}
	$cart->Location = $_POST['Location'];
	$cart->freight_cost = input_num('freight_cost');
	if (isset($_POST['email']))
		$cart->email = $_POST['email'];
	else
		$cart->email = '';
	$cart->customer_id	= $_POST['customer_id'];
	$cart->Branch = $_POST['branch_id'];
	$cart->sales_type = $_POST['sales_type'];

	if ($cart->trans_type != ST_SALESORDER && $cart->trans_type != ST_SALESQUOTE) { // 2008-11-12 Joe Hunt
		$cart->dimension_id = $_POST['dimension_id'];
		$cart->dimension2_id = $_POST['dimension2_id'];
	}
	$cart->ex_rate = input_num('_ex_rate', null);

	$cart->payment = 0;
	$cart->dp_amount = 0;
	$cart->amortization = 0;
	$cart->warranty_code = $_POST['waranty_code'];
	$cart->fsc_series = $_POST['fsc_series'];
	$cart->ar_amount = $_POST['total_amount'];
	$cart->first_due_date = "0000-00-00";
	$cart->maturity_date = "0000-00-00";
	$cart->lcp_amount = $_POST['lcp_amount'];
	$cart->co_maker = $_POST['co_maker'];
	$cart->stype_id = $_POST['stype_id'];
	$cart->so_item_type = 'repo';
	$cart->previous_owner = $_POST['previous_owner'];
}

//-----------------------------------------------------------------------------

function copy_from_cart()
{
	$cart = &$_SESSION['Items'];
	$_POST['ref'] = $cart->reference;
	if ($cart->trans_type == ST_SALESINVOICEREPO)
		$_POST['dr_ref'] = $cart->dr_ref;
	$_POST['Comments'] = $cart->Comments;
	$_POST['account_specialist_remarks'] = $cart->account_specialist_remarks ;
	$_POST['so_item_type'] = $cart->so_item_type ;

	$_POST['OrderDate'] = $cart->document_date;
	$_POST['delivery_date'] = $cart->due_date;
	$_POST['cust_ref'] = $cart->cust_ref;
	$_POST['freight_cost'] = price_format($cart->freight_cost);

	$_POST['deliver_to'] = $cart->deliver_to;
	$_POST['delivery_address'] = $cart->delivery_address;
	$_POST['phone'] = $cart->phone;
	$_POST['Location'] = $cart->Location;
	$_POST['ship_via'] = $cart->ship_via;

	$_POST['customer_id'] = $cart->customer_id;

	//new added by progjr on 2-20-2021
	if ($cart->trans_type == ST_SALESINVOICEREPO)
		$_POST['document_ref'] = $cart->document_ref;
	$_POST['salesman_id'] = $cart->salesman_id;
	$_POST['category_id'] = $cart->category_id;

	$_POST['branch_id'] = $cart->Branch;
	$_POST['sales_type'] = $cart->sales_type;
	$_POST['prep_amount'] = price_format($cart->prep_amount);
	// POS 
	$_POST['payment'] = $cart->payment;
	if ($cart->trans_type != ST_SALESORDER && $cart->trans_type != ST_SALESQUOTE) { // 2008-11-12 Joe Hunt
		$_POST['dimension_id'] = $cart->dimension_id;
		$_POST['dimension2_id'] = $cart->dimension2_id;
	}
	$_POST['cart_id'] = $cart->cart_id;
	$_POST['_ex_rate'] = $cart->ex_rate;
	$_POST['down_pay'] = 0;
	$_POST['waranty_code'] = $cart->warranty_code;
	$_POST['fsc_series'] = $cart->fsc_series;
	$_POST['discount1'] = 0;
	$_POST['discount2'] = 0;
	$_POST['total_amount'] = $cart->ar_amount;
	$_POST['lcp_amount'] = $cart->lcp_amount;
	$_POST['co_maker'] = $cart->co_maker;
	$_POST['stype_id'] = $cart->stype_id;
}
//--------------------------------------------------------------------------------

function line_start_focus()
{
	global 	$Ajax;
	$Ajax->activate('items_table');
	set_focus('_stock_id_edit');
}

//Added by spyrax10
if (get_post('StockLocation')) {
	$cart = &$_SESSION['Items'];
	$_POST['Location'] = get_post('StockLocation');
	$cart->Location = $_POST['Location'];
	$Ajax->activate('delivery');
}

//--------------------------------------------------------------------------------
function can_process()
{

	global $Refs, $SysPrefs;

	copy_to_cart();

	if (!get_post('customer_id')) {
		display_error(_("There is no customer selected."));
		set_focus('customer_id');
		return false;
	}

	if (!get_post('branch_id')) {
		display_error(_("This customer has no branch defined."));
		set_focus('branch_id');
		return false;
	}
	if ($_SESSION['Items']->trans_type == ST_SALESORDER && get_post('Comments')== ""){
		display_error(_("Comments is cannot be empty!"));
		set_focus('Comments');
		return false;
	}
	if ($_SESSION['Items']->line_items[0]->price == 0){
		display_error(_("Invoice total amount cannot be less than zero."));
	return false;
	}
	if ($_SESSION['Items']->trans_type == ST_SALESINVOICEREPO && reference_exist(get_post('document_ref'))) {
		display_error(_("Reference # is already exist!"));
		set_focus('document_ref');
		return false;
	}

	if (!is_date($_POST['OrderDate'])) {
		display_error(_("The entered date is invalid."));
		set_focus('OrderDate');
		return false;
	}
	if ($_SESSION['Items']->trans_type != ST_SALESORDER && $_SESSION['Items']->trans_type != ST_SALESQUOTE && !is_date_in_fiscalyear($_POST['OrderDate'])) {
		display_error(_("The entered date is out of fiscal year or is closed for further data entry."));
		set_focus('OrderDate');
		return false;
	}
	if (count($_SESSION['Items']->line_items) == 0) {
		display_error(_("You must enter at least one non empty item line."));
		set_focus('AddItem');
		return false;
	}
	if (!$SysPrefs->allow_negative_stock() && ($low_stock = $_SESSION['Items']->check_qoh())) {
		display_error(_("This document cannot be processed because there is insufficient quantity for items marked."));
		return false;
	}
	if ($_SESSION['Items']->payment_terms['cash_sale'] == 0) {
		if (!$_SESSION['Items']->is_started() && ($_SESSION['Items']->payment_terms['days_before_due'] == -1) && ((input_num('prep_amount') <= 0) ||
			input_num('prep_amount') > $_SESSION['Items']->get_trans_total())) {
			display_error(_("Pre-payment required have to be positive and less than total amount."));
			set_focus('prep_amount');
			return false;
		}
		if (strlen($_POST['deliver_to']) <= 1) {
			display_error(_("You must enter the person or company to whom delivery should be made to."));
			set_focus('deliver_to');
			return false;
		}

		if ($_SESSION['Items']->trans_type != ST_SALESQUOTE && strlen($_POST['delivery_address']) <= 1) {
			display_error(_("You should enter the street address in the box provided. Orders cannot be accepted without a valid street address."));
			set_focus('delivery_address');
			return false;
		}

		// if ($_POST['freight_cost'] == "")
		// 	$_POST['freight_cost'] = price_format(0);

		// if (!check_num('freight_cost',0)) {
		// 	display_error(_("The shipping cost entered is expected to be numeric."));
		// 	set_focus('freight_cost');
		// 	return false;
		// }
		if (!is_date($_POST['delivery_date'])) {
			if ($_SESSION['Items']->trans_type == ST_SALESQUOTE)
				display_error(_("The Valid date is invalid."));
			else
				display_error(_("The delivery date is invalid."));
			set_focus('delivery_date');
			return false;
		}
		if (date1_greater_date2($_POST['OrderDate'], $_POST['delivery_date'])) {
			if ($_SESSION['Items']->trans_type == ST_SALESQUOTE)
				display_error(_("The requested valid date is before the date of the quotation."));
			else
				display_error(_("The requested delivery date is before the date of the order."));
			set_focus('delivery_date');
			return false;
		}
	} else {
		if (!db_has_cash_accounts()) {
			display_error(_("You need to define a cash account for your Sales Point."));
			return false;
		}
	}

	if (!$Refs->is_valid($_POST['ref'], $_SESSION['Items']->trans_type)) {
		display_error(_("You must enter a reference."));
		set_focus('ref');
		return false;
	}
	
	/* Commented by spyrax10 for Mantis Issue #619 11-24-2021
	if (!db_has_currency_rates($_SESSION['Items']->customer_currency, $_POST['OrderDate'])) {
		display_error(_("Exchange Rate Setup Missing!"));
		return false;
	} */
		
	if ($_SESSION['Items']->get_items_total() < 0) {
		display_error("Invoice total amount cannot be less than zero.");
		return false;
	}

	if ($_SESSION['Items']->trans_type == ST_SALESINVOICEREPO && get_post('waranty_code') == "" && $_SESSION['Items']->category_id == 14) {
		display_error("You must enter a Warranty Code");
		return false;
	}

	if ($_SESSION['Items']->trans_type == ST_SALESINVOICEREPO && $_SESSION['Items']->ar_amount < 0) {
		display_error("The LCP Amount is 0");
		return false;
	}

	if ($_SESSION['Items']->payment_terms['cash_sale'] &&
		($_SESSION['Items']->trans_type == ST_CUSTDELIVERY || $_SESSION['Items']->trans_type == ST_SALESINVOICEREPO))
	{
		$_SESSION['Items']->due_date = $_SESSION['Items']->document_date;
	}

	return true;
}

//-----------------------------------------------------------------------------
if (isset($_POST['update'])) {
	copy_to_cart();
	$Ajax->activate('items_table');
}

if (isset($_POST['ProcessOrder']) && can_process()) {

	$modified = ($_SESSION['Items']->trans_no != 0);
	$so_type = $_SESSION['Items']->so_type;
	$ret = $_SESSION['Items']->write(1, 0, 0, get_post('waranty_code'), get_post('fsc_series'));
	if ($ret == -1) {
		display_error(_("The entered reference is already in use."));
		$ref = $Refs->get_next($_SESSION['Items']->trans_type, null, array('date' => Today()));
		if ($ref != $_SESSION['Items']->reference) {
			unset($_POST['ref']); // force refresh reference
			display_error(_("The reference number field has been increased. Please save the document again."));
		}
		set_focus('ref');
	} else {
		if (count($messages)) { // abort on failure or error messages are lost
			$Ajax->activate('_page_body');
			display_footer_exit();
		}
		$trans_no = key($_SESSION['Items']->trans_no);
		$trans_type = $_SESSION['Items']->trans_type;
		new_doc_date($_SESSION['Items']->document_date);
		processing_end();
		if ($modified) {
			if ($trans_type == ST_SALESQUOTE)
				meta_forward($_SERVER['PHP_SELF'], "UpdatedQU=$trans_no");
			else
				meta_forward($_SERVER['PHP_SELF'], "UpdatedID=$trans_no");
		} elseif ($trans_type == ST_SALESORDER) {
			meta_forward($_SERVER['PHP_SELF'], "AddedID=$trans_no");
		} elseif ($trans_type == ST_SALESQUOTE) {
			meta_forward($_SERVER['PHP_SELF'], "AddedQU=$trans_no");
		} elseif ($trans_type == ST_SALESINVOICEREPO) {
			meta_forward($_SERVER['PHP_SELF'], "AddedDI=$trans_no&Type=$so_type");
		} else {
			meta_forward($_SERVER['PHP_SELF'], "AddedDN=$trans_no&Type=$so_type");
		}
	}
}

//--------------------------------------------------------------------------------
function check_item_data()
{
	global $SysPrefs;

	$is_inventory_item = is_inventory_item(get_post('stock_id'));
	if (!get_post('stock_id_text', true)) {
		display_error(_("Item description cannot be empty."));
		set_focus('stock_id_edit');
		return false;
	}
	// elseif (!check_num('qty', 0) || !check_num('Disc', 0, 100)) {
	// 	display_error( _("The item could not be updated because you are attempting to set the quantity ordered to less than 0, or the discount percent to more than 100."));
	// 	set_focus('qty');
	// 	return false;
	// } 
	elseif (!check_num('price', 0) && (!$SysPrefs->allow_negative_prices() || $is_inventory_item)) {
		display_error(_("Price for inventory item must be entered and can not be less than 0"));
		set_focus('price');
		return false;
	} elseif (
		isset($_POST['LineNo']) && isset($_SESSION['Items']->line_items[$_POST['LineNo']])
		&& !check_num('qty', $_SESSION['Items']->line_items[$_POST['LineNo']]->qty_done)
	) {

		set_focus('qty');
		display_error(_("You attempting to make the quantity ordered a quantity less than has already been delivered. The quantity delivered cannot be modified retrospectively."));
		return false;
	} elseif ($_POST['category_id'] == -1) {
		display_error(_("Select Category"));
		set_focus('category_id');
		return false;
	}
	/* elseif(isset($_POST['category_id']) == -1){
		display_error(_("Please select category first before searching item stock."));
		set_focus('category_id');
		return false;
	}
	echo get_post('category_id');
*/
	$cost_home = get_unit_cost(get_post('stock_id')); // Added 2011-03-27 Joe Hunt
	$cost = $cost_home / get_exchange_rate_from_home_currency($_SESSION['Items']->customer_currency, $_SESSION['Items']->document_date);
	if (input_num('price') < $cost) {
		$dec = user_price_dec();
		$curr = $_SESSION['Items']->customer_currency;
		$price = number_format2(input_num('price'), $dec);
		if ($cost_home == $cost)
			$std_cost = number_format2($cost_home, $dec);
		else {
			$price = $curr . " " . $price;
			$std_cost = $curr . " " . number_format2($cost, $dec);
		}
		display_warning(sprintf(_("Price %s is below Standard Cost %s"), $price, $std_cost));
	}
	return true;
}

//--------------------------------------------------------------------------------
//Added by spyrax10
function update_header() {
	$total = 0;
	$total_lcp = $dis1 = $dis2 = $lcp = 0;
	foreach ($_SESSION['Items']->line_items as $order_item) {
		$total += $order_item->price * $order_item->qty_dispatched;
		$total_lcp += $order_item->price * $order_item->qty_dispatched - ($order_item->discount1 + $order_item->discount2);
		$dis1 += $order_item->discount1 * $order_item->qty_dispatched;
		$dis2 += $order_item->discount2 * $order_item->qty_dispatched;
		//$total += ($order_item->price - ($order_item->discount1 + $order_item->discount2)) * $order_item->qty_dispatched;
		//$total_lcp += $order_item->price * $order_item->qty_dispatched;
	}
	$_POST['total_amount'] = $total;
	$_POST['total_discount'] = $dis1 + $dis2;
	$_POST['lcp_amount'] = $total_lcp;
	$_POST['discount1'] = $dis1;
	$_POST['discount2'] = $dis2;

	global $Ajax;
	$Ajax->activate('_page_body');
}

//Modified by spyrax10
function handle_update_item()
{
	if ($_POST['UpdateItem'] != '' && check_item_data()) {
		$_SESSION['Items']->update_cart_item(
			$_POST['LineNo'],
			input_num('qty'),
			input_num('price'),
			input_num('Disc') / 100,
			$_POST['item_description'],
			input_num('discount1'),
			input_num('discount2'),
			input_num('lcp_price'),
			//Added by spyrax10
			'',
			''
		);
	}

	update_header();
	
	page_modified();
	line_start_focus();
}

//--------------------------------------------------------------------------------
//Modified by spyrax10
function handle_delete_item($line_no)
{
	if ($_SESSION['Items']->some_already_delivered($line_no) == 0) {
		$_SESSION['Items']->remove_from_cart($line_no);
	} else {
		display_error(_("This item cannot be deleted because some of it has already been delivered."));
	}

	update_header();
	unset($_POST['_stock_id_edit'], $_POST['stock_id'], $_POST['serialeng_no'], $_POST['chassis_no'], $_POST['color_desc'], 
		$_POST['lcp_price']);
	line_start_focus();
}

//--------------------------------------------------------------------------------
//Modified by spyrax10
function handle_new_item()
{

	if (!check_item_data()) {
		return;
	}
	add_to_order(
		$_SESSION['Items'],
		get_post('stock_id'),
		input_num('qty'),
		input_num('price'),
		input_num('Disc') / 100,
		get_post('stock_id_text'),
		$_POST['serialeng_no'],
		$_POST['chassis_no'],
		$_POST['color_desc'],
		$_POST['item_type'],
		input_num('discount1'),
		input_num('discount2'),
		input_num('lcp_price')
	);

	update_header();
	
	unset($_POST['_stock_id_edit'], $_POST['stock_id'], $_POST['serialeng_no'], $_POST['chassis_no'], $_POST['color_desc'], 
		$_POST['lcp_price']);
	page_modified();
	line_start_focus();
}

//--------------------------------------------------------------------------------

function  handle_cancel_order()
{
	global $path_to_root, $Ajax;


	if ($_SESSION['Items']->trans_type == ST_CUSTDELIVERY) {
		display_notification(_("Direct delivery entry has been cancelled as requested."), 1);
		submenu_option(_("Enter a New Sales Delivery"),	"/sales/si_repo_cash.php?NewDelivery=1");
	} elseif ($_SESSION['Items']->trans_type == ST_SALESINVOICEREPO) {
		display_notification(_("Direct invoice entry has been cancelled as requested."), 1);
		submenu_option(_("Enter a New Sales Invoice"),	"/sales/si_repo_cash.php?NewInvoiceRepo=1");
	} elseif ($_SESSION['Items']->trans_type == ST_SALESQUOTE) {
		if ($_SESSION['Items']->trans_no != 0)
			delete_sales_order(key($_SESSION['Items']->trans_no), $_SESSION['Items']->trans_type);
		display_notification(_("This sales quotation has been cancelled as requested."), 1);
		submenu_option(_("Enter a New Sales Quotation"), "/sales/si_repo_cash.php?NewQuotation=Yes");
	} else { // sales order
		if ($_SESSION['Items']->trans_no != 0) {
			$order_no = key($_SESSION['Items']->trans_no);
			if (sales_order_has_deliveries($order_no)) {
				close_sales_order($order_no);
				display_notification(_("Undelivered part of order has been cancelled as requested."), 1);
				submenu_option(_("Select Another Sales Order for Edition"), "/sales/inquiry/sales_orders_repo_view.php?type=" . ST_SALESORDER);
			} else {
				delete_sales_order(key($_SESSION['Items']->trans_no), $_SESSION['Items']->trans_type);

				display_notification(_("This sales order has been cancelled as requested."), 1);
				submenu_option(_("Enter a New Sales Order"), "/sales/si_repo_cash.php?NewOrder=Yes");
			}
		} else {
			processing_end();
			meta_forward($path_to_root . '/index.php', 'application=orders');
		}
	}
	processing_end();
	display_footer_exit();
}

//--------------------------------------------------------------------------------

function create_cart($type, $trans_no)
{
	global $Refs, $SysPrefs;

	if (!$SysPrefs->db_ok) // create_cart is called before page() where the check is done
		return;

	processing_start();
	if (isset($_GET['NewQuoteToSalesOrder'])) {
		$trans_no = $_GET['NewQuoteToSalesOrder'];
		$doc = new Cart(ST_SALESQUOTE, $trans_no, true);
		$doc->Comments = _("Sales Quotation") . " # " . $trans_no;
		$_SESSION['Items'] = $doc;
	} elseif ($type != ST_SALESORDER && $type != ST_SALESQUOTE && $trans_no != 0) { // this is template

		$doc = new Cart(ST_SALESORDER, array($trans_no), false, true);
		$doc->trans_type = $type;
		$doc->trans_no = 0;
		$doc->document_date = new_doc_date();
		if ($type == ST_SALESINVOICEREPO) {
			$doc->due_date = get_invoice_duedate($doc->payment, $doc->document_date);
			$doc->pos = get_sales_point(user_pos());
		} else
			$doc->due_date = $doc->document_date;
		$doc->reference = $Refs->get_next($doc->trans_type, null, array('date' => Today()));
		//$doc->Comments='';
		foreach ($doc->line_items as $line_no => $line) {
			$doc->line_items[$line_no]->qty_done = 0;
		}
		$_SESSION['Items'] = $doc;
	} else {

		$_SESSION['Items'] = new Cart($type, array($trans_no));

		$company = get_company_prefs();

		$branch_code = $company["branch_code"];
		$_SESSION['Items']->Location = $branch_code;
	}
	copy_from_cart();
}

//--------------------------------------------------------------------------------
if (isset($_POST['CancelOrder']))
	handle_cancel_order();

$id = find_submit('Delete');
if ($id != -1)
	handle_delete_item($id);

if (isset($_POST['UpdateItem']))
	handle_update_item();

if (isset($_POST['AddItem']))
	handle_new_item();

if (isset($_POST['CancelItemChanges'])) {
	line_start_focus();
}

//--------------------------------------------------------------------------------
if ($_SESSION['Items']->fixed_asset)
	check_db_has_disposable_fixed_assets(_("There are no fixed assets defined in the system."));
else
	check_db_has_stock_items(_("There are no inventory items defined in the system."));

check_db_has_customer_branches(_("There are no customers, or there are no customers with branches. Please define customers and customer branches."));

if ($_SESSION['Items']->trans_type == ST_SALESINVOICEREPO) {
	$idate = _("Invoice Date:");
	$orderitems = _("Sales Invoice Items");
	$deliverydetails = _("Enter Delivery Details and Confirm Invoice");
	$cancelorder = _("Cancel Invoice");
	$porder = _("Place Invoice");
} elseif ($_SESSION['Items']->trans_type == ST_CUSTDELIVERY) {
	$idate = _("Delivery Date:");
	$orderitems = _("Delivery Note Items");
	$deliverydetails = _("Enter Delivery Details and Confirm Dispatch");
	$cancelorder = _("Cancel Delivery");
	$porder = _("Place Delivery");
} elseif ($_SESSION['Items']->trans_type == ST_SALESQUOTE) {
	$idate = _("Quotation Date:");
	$orderitems = _("Sales Quotation Items");
	$deliverydetails = _("Enter Delivery Details and Confirm Quotation");
	$cancelorder = _("Cancel Quotation");
	$porder = _("Place Quotation");
	$corder = _("Commit Quotations Changes");
} else {
	$idate = _("Order Date:");
	$orderitems = _("Sales Order Items");
	$deliverydetails = _("Enter Delivery Details and Confirm Order");
	$cancelorder = _("Cancel Order");
	$porder = _("Place Order");
	$corder = _("Commit Order Changes");
}
start_form();

hidden('cart_id');
$customer_error = display_order_header($_SESSION['Items'], !$_SESSION['Items']->is_started(), $idate);

if ($customer_error == "") {
	start_table(TABLESTYLE, "width='80%'", 10);
	echo "<tr><td>";
	/*modified by Albert*/
	if (get_so_repo_status(get_post('serialeng_no')) == ''|| get_post('serialeng_no') == ''){
		display_order_summary($orderitems, $_SESSION['Items'], true);
	}else if (get_so_repo_status(get_post('serialeng_no')) == 'Cancelled' || get_so_repo_status(get_post('serialeng_no')) == 'Closed'){
		display_order_summary($orderitems, $_SESSION['Items'], true);
	}else{
		display_warning('This Item Already Reserved!!! (Albert)');
	}  
	/*-------End */
	echo "</td></tr>";
	echo "<tr><td>";
	display_delivery_details($_SESSION['Items']);
	echo "</td></tr>";
	end_table(1);

	if ($_SESSION['Items']->trans_no == 0) {

		submit_center_first(
			'ProcessOrder',
			$porder,
			_('Check entered data and save document'),
			'default'
		);
		submit_center_last(
			'CancelOrder',
			$cancelorder,
			_('Cancels document entry or removes sales order when editing an old document')
		);
		submit_js_confirm('CancelOrder', _('You are about to void this Document.\nDo you want to continue?'));
	} else {
		submit_center_first(
			'ProcessOrder',
			$corder,
			_('Validate changes and update document'),
			'default'
		);
		submit_center_last(
			'CancelOrder',
			$cancelorder,
			_('Cancels document entry or removes sales order when editing an old document')
		);
		if ($_SESSION['Items']->trans_type == ST_SALESORDER)
			submit_js_confirm('CancelOrder', _('You are about to cancel undelivered part of this order.\nDo you want to continue?'));
		else
			submit_js_confirm('CancelOrder', _('You are about to void this Document.\nDo you want to continue?'));
	}
} else {
	display_error($customer_error);
}

end_form();
end_page();
