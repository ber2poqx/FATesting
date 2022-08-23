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
$page_security = 'SA_SALESORDER';

include_once($path_to_root . "/sales/includes/cart_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/ui/sales_order_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_types_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/includes/cost_and_pricing.inc");
include_once($path_to_root . "/includes/aging.inc"); //added by spyrax10

include_once($path_to_root . "/sales/includes/db/sales_installment_policy_db.inc");	// Added by Ronelle 2/25/2021

set_page_security(
	@$_SESSION['Items']->trans_type,
	array(
		ST_SALESORDER => 'SA_SALESORDER',
		ST_SALESQUOTE => 'SA_SALESQUOTE',
		ST_CUSTDELIVERY => 'SA_SALESDELIVERY',
		ST_SALESINVOICE => 'SA_SALESINVOICE',
		ST_SALESINVOICEREPO => 'SA_SALESINVOICEREPO',
		ST_SITERMMOD => 'SA_SITERMMOD',
		ST_RESTRUCTURED => 'SA_RESTRUCTURED'

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
		'NewInvoice' => 'SA_SALESINVOICE',
		'NewInvoiceRepo' => 'SA_SALESINVOICEREPO',
		'AddedDI' => 'SA_SALESINVOICE',
		'NewChangeTerm' => 'SA_SITERMMOD',
		'AddedCT' => 'SA_SITERMMOD',
		'NewRestructured' => 'SA_RESTRUCTURED',
		'AddedRE' => 'SA_RESTRUCTURED'
	)
);

$js = '';

if ($SysPrefs->use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}

if (user_use_date_picker()) {
	$js .= get_js_date_picker();
}
//-----------------------------------------------------------------------------
//Added by spyrax10
function get_OB_status() {

	$ob = 0;
	
	if ($_SESSION['page_title'] == "Sales Invoice - OB Term Modification") {
		$ob = 1;
	}
	else if ($_SESSION['page_title'] == "Sales Invoice - OB Restructured") {
		$ob = 1;
	}
	else {
		$ob = 0;
	}
	return $ob;
}

//-----------------------------------------------------------------------------

if (isset($_GET['NewDelivery']) && is_numeric($_GET['NewDelivery'])) {

	$_SESSION['page_title'] = _($help_context = "Direct Sales Delivery");
	create_cart(ST_CUSTDELIVERY, $_GET['NewDelivery']);
} elseif (isset($_GET['NewInvoice']) && is_numeric($_GET['NewInvoice'])) {

	create_cart(ST_SALESINVOICE, $_GET['NewInvoice']);

	if (isset($_GET['FixedAsset'])) {
		$_SESSION['page_title'] = _($help_context = "Fixed Assets Sale");
		$_SESSION['Items']->fixed_asset = true;
	} else {
		$_SESSION['page_title'] = _($help_context = "Sales Invoice Installment");
	}


} elseif (isset($_GET['CancelInvoice']) && is_numeric($_GET['CancelInvoice'])) {

	create_cart(ST_SALESINVOICE, $_GET['CancelInvoice']);
	$_SESSION['page_title'] = _($help_context = "Cancel Sales Invoice Installment");

}
//Modified by spyrax10 
elseif (isset($_GET['NewChangeTerm']) && is_numeric($_GET['NewChangeTerm'])) {

	$_SESSION['page_title'] = $_GET['opening_balance'] == 1 ? _($help_context = "Sales Invoice - OB Term Modification") : 
		_($help_context = "Sales Invoice Term Modification");

	create_cart(ST_SITERMMOD, $_GET['NewChangeTerm']);

}
//
elseif (isset($_GET['NewRestructured']) && is_numeric($_GET['NewRestructured'])) {

	create_cart(ST_RESTRUCTURED, $_GET['NewRestructured']);
	$_SESSION['page_title'] = $_GET['opening_balance'] == 1 && $_GET['NewRestructured'] ? _($help_context = "Sales Invoice - OB Restructured")
	: _($help_context = "Sales Invoice Restructured");

} elseif (isset($_GET['ModifyOrderNumber']) && is_numeric($_GET['ModifyOrderNumber'])) {

	$help_context = 'Modifying Sales Order';
	$_SESSION['page_title'] = sprintf(_("Modifying Sales Order # %d"), $_GET['ModifyOrderNumber']);
	create_cart(ST_SALESORDER, $_GET['ModifyOrderNumber']);
} elseif (isset($_GET['ModifyQuotationNumber']) && is_numeric($_GET['ModifyQuotationNumber'])) {

	$help_context = 'Modifying Sales Quotation';
	$_SESSION['page_title'] = sprintf(_("Modifying Sales Quotation # %d"), $_GET['ModifyQuotationNumber']);
	create_cart(ST_SALESQUOTE, $_GET['ModifyQuotationNumber']);
} elseif (isset($_GET['NewOrder'])) {

	$_SESSION['page_title'] = _($help_context = "New Sales Order Entry");
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

if (list_updated('stock_id')) {
	$Ajax->activate('items_table');
}

//Added by spyrax10
if (get_post('co_maker')) {
	$_POST['co_maker'] = get_post('co_maker');
	$Ajax->activate('co_maker');
}
//

//-----------------------------------------------------------------------------


if (isset($_GET['AddedID'])) {
	$order_no = $_GET['AddedID'];
	display_notification_centered(sprintf(_("Order # %d has been entered."), $order_no));

	submenu_view(_("&View This Order"), ST_SALESORDER, $order_no);

	submenu_print(_("&Print This Order"), ST_SALESORDER, $order_no, 'prtopt');
	submenu_print(_("&Email This Order"), ST_SALESORDER, $order_no, null, 1);
	set_focus('prtopt');

	submenu_option(_("Enter a &New Order"),	"/sales/sales_order_entry.php?NewOrder=0");
	
	//Added by spyrax10
	hyperlink_params("$path_to_root/sales/inquiry/sales_orders_view.php?type=30", _("Back to Sales Order List"), "");
	hyperlink_params("$path_to_root/sales/sales_invoice_list.php?", _("Back to Sales Invoice List"), "");
	//

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
		"/sales/sales_order_entry.php?NewQuoteToSalesOrder=$order_no"
	);

	submenu_option(_("Enter a New &Quotation"),	"/sales/sales_order_entry.php?NewQuotation=0");

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
		"/sales/sales_order_entry.php?NewQuoteToSalesOrder=$order_no"
	);

	submenu_option(
		_("Select A Different &Quotation"),
		"/sales/inquiry/sales_orders_view.php?type=" . ST_SALESQUOTE
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
			"/sales/inquiry/sales_orders_view.php?DeliveryTemplates=Yes"
		);
	else
		submenu_option(
			_("Enter a &New Delivery"),
			"/sales/sales_order_entry.php?NewDelivery=0"
		);

	display_footer_exit();
} elseif (isset($_GET['AddedDI'])) {
	$invoice = $_GET['AddedDI'];

	display_notification_centered(sprintf(_("Invoice # %d has been entered."), $invoice));

	submenu_view(_("&View This Invoice"), ST_SALESINVOICE, $invoice);

	submenu_print(_("&Print Sales Invoice"), ST_SALESINVOICE, $invoice . "-" . ST_SALESINVOICE, 'prtopt');
	submenu_print(_("&Email Sales Invoice"), ST_SALESINVOICE, $invoice . "-" . ST_SALESINVOICE, null, 1);
	set_focus('prtopt');

	$row = db_fetch(get_allocatable_from_cust_transactions(null, $invoice, ST_SALESINVOICE));
	if ($row !== false)
		submenu_print(_("Print &Receipt"), $row['type'], $row['trans_no'] . "-" . $row['type'], 'prtopt');

	display_note(get_gl_view_str(ST_SALESINVOICE, $invoice, _("View the GL &Journal Entries for this Invoice")), 0, 1);

	//Added by spyrax10
	hyperlink_params("$path_to_root/sales/inquiry/sales_orders_view.php?type=30", _("Back to Sales Order List"), "");
	hyperlink_params("$path_to_root/sales/sales_invoice_list.php?", _("Back to Sales Invoice List"), "");
	//

	display_footer_exit();
} elseif (isset($_GET['AddedCT'])) {
	$invoice = $_GET['AddedCT'];

	display_notification_centered(sprintf(_("Invoice CT # %d has been entered."), $invoice));

	submenu_view(_("&View This Invoice CT"), ST_SITERMMOD, $invoice);

	// submenu_print(_("&Print Sales Invoice CT"), ST_SITERMMOD, $invoice . "-" . ST_SITERMMOD, 'prtopt');
	// submenu_print(_("&Email Sales Invoice CT"), ST_SITERMMOD, $invoice . "-" . ST_SITERMMOD, null, 1);
	// set_focus('prtopt');

	// $row = db_fetch(get_allocatable_from_cust_transactions(null, $invoice, ST_SALESINVOICE));
	// if ($row !== false)
	// 	submenu_print(_("Print &Receipt"), $row['type'], $row['trans_no'] . "-" . $row['type'], 'prtopt');

	display_note(get_gl_view_str(ST_SITERMMOD, $invoice, _("View the GL &Journal Entries for this Invoice CT")), 0, 1);

	display_footer_exit();
} elseif (isset($_GET['AddedRE'])) {
	$invoice = $_GET['AddedRE'];

	display_notification_centered(sprintf(_("Invoice Restructured # %d has been entered."), $invoice));

	submenu_view(_("&View This Invoice Restructured"), ST_RESTRUCTURED, $invoice);

	// submenu_print(_("&Print Sales Invoice CT"), ST_SITERMMOD, $invoice . "-" . ST_SITERMMOD, 'prtopt');
	// submenu_print(_("&Email Sales Invoice CT"), ST_SITERMMOD, $invoice . "-" . ST_SITERMMOD, null, 1);
	// set_focus('prtopt');

	// $row = db_fetch(get_allocatable_from_cust_transactions(null, $invoice, ST_SALESINVOICE));
	// if ($row !== false)
	// 	submenu_print(_("Print &Receipt"), $row['type'], $row['trans_no'] . "-" . $row['type'], 'prtopt');

	display_note(get_gl_view_str(ST_RESTRUCTURED, $invoice, _("View the GL &Journal Entries for this Invoice Restructured")), 0, 1);

	display_footer_exit();
} else
	check_edit_conflicts(get_post('cart_id'));
//-----------------------------------------------------------------------------

function copy_to_cart()
{
	$cart = &$_SESSION['Items'];
	$cart->reference = $_POST['ref'];
	if ($cart->trans_type == ST_SALESINVOICE || $cart->trans_type == ST_SITERMMOD || $cart->trans_type == ST_RESTRUCTURED) {
		$cart->dr_ref = $_POST['dr_ref'];
		$cart->document_ref = $_POST['document_ref'];
	}

	if ($cart->trans_type == ST_SALESINVOICE) {
		$cart->deferred_gross_profit = $_POST['deferred_gross_profit'];
		$cart->profit_margin = $_POST['profit_margin'];
	}

	if ($cart->trans_type == ST_SITERMMOD || $cart->trans_type == ST_RESTRUCTURED) {
		$cart->new_deferred_gross_profit = $_POST['new_deferred_gross_profit'];
		$cart->new_profit_margin = $_POST['new_profit_margin'];

	}
	//Added By Albert 03/10/2022
	if ($cart->trans_type == ST_RESTRUCTURED) {
		$cart->calculation_id = $_POST['calculation_id'];
		$cart->first_due_date = $_POST['new_first_due_date'];

	}
	/* */
	$cart->Comments =  $_POST['Comments'];

	$cart->document_date = $_POST['OrderDate'];
	$cart->salesman_id = $_POST['salesman_id'];
	$cart->category_id = $_POST['category_id'];

	$newpayment = false;

	if (isset($_POST['payment']) && ($cart->payment != $_POST['payment'])) {
		$cart->payment = $_POST['payment'];
		$cart->payment_terms = get_payment_terms($_POST['payment']);
		$newpayment = true;
	}
	if ($cart->trans_type != ST_SITERMMOD|| $cart->trans_type != ST_RESTRUCTURED) {
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

	$cart->payment = $_POST['installment_policy_id'];
	$cart->dp_amount = $_POST['down_pay'];
	$cart->discount_dp_amount2 = $_POST['discount_dp2_amount'];
	$cart->amortization = $_POST['due_amort'];
	$cart->warranty_code = $_POST['waranty_code'];
	$cart->fsc_series = $_POST['fsc_series'];
	$cart->ar_amount = $_POST['ar_amount'];
	$cart->first_due_date = $_POST['first_due_date'];
	$cart->maturity_date = $_POST['maturity_date'];
	$cart->lcp_amount = $_POST['lcp_amount'];
	$cart->months_term = $_POST['count_term'];
	$cart->rebate = $_POST['rebate'];
	$cart->financing_rate = $_POST['financing_rate'];
	$cart->payment_policy = $_POST['installment_policy_id'];
	$cart->co_maker = $_POST['co_maker'];
	$cart->discount_dp_amount = $_POST['discount_dp_amount'];
	$cart->stype_id = $_POST['stype_id'];

	if ($cart->trans_type == ST_SITERMMOD || $cart->trans_type == ST_RESTRUCTURED) {
		$cart->payment = $_POST['new_installment_policy_id'];
		$cart->dp_amount = $_POST['down_pay'];
		$cart->amortization = $_POST['new_due_amort'];
		$cart->ar_amount = $_POST['new_ar_amount'];
		// $cart->first_due_date = $_POST['new_first_due_date'];
		$cart->maturity_date = $_POST['new_maturity_date'];
		$cart->months_term = $_POST['new_count_term'];
		$cart->rebate = $_POST['new_rebate'];
		$cart->financing_rate = $_POST['new_financing_rate'];
		$cart->payment_policy = $_POST['new_installment_policy_id'];

		$cart->amort_diff = $_POST['amort_diff'];
		$cart->months_paid = $_POST['months_paid'];
		$cart->amort_delay = $_POST['amort_delay'];
		$cart->adj_rate = $_POST['adj_rate'];
		$cart->opportunity_cost = $_POST['opportunity_cost'];
		$cart->amount_to_be_paid = $_POST['amount_to_be_paid'];
		$cart->outstanding_ar_amount = $_POST['outstanding_ar_amount'];

		$cart->prev_months_term = $_POST['count_term'];
		$cart->prev_ar_balance = $_POST['ar_amount'] - $_POST['alloc'];
		$cart->prev_ar_amount = $_POST['ar_amount'];
	}
}

//-----------------------------------------------------------------------------

function copy_from_cart()
{
	$cart = &$_SESSION['Items'];
	$_POST['ref'] = $cart->reference;
	if ($cart->trans_type == ST_SALESINVOICE || $cart->trans_type == ST_SITERMMOD || $cart->trans_type == ST_RESTRUCTURED)
		$_POST['dr_ref'] = $cart->dr_ref;
	$_POST['Comments'] = $cart->Comments;
	// added by Albert
	$_POST['account_specialist_remarks'] = $cart->account_specialist_remarks;
	$_POST['so_item_type'] = $cart->so_item_type;

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
	if ($cart->trans_type == ST_SALESINVOICE || $cart->trans_type == ST_SITERMMOD || $cart->trans_type == ST_RESTRUCTURED)
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

	$_POST['waranty_code'] = $cart->warranty_code;
	$_POST['fsc_series'] = $cart->fsc_series;
	$_POST['lcp_amount'] = $cart->lcp_amount;
	$_POST['down_pay'] = $cart->dp_amount;
	$_POST['discount_dp2_amount'] = $cart->discount_dp_amount2;
	$_POST['amortization'] = $cart->amortization;

	$_POST['installment_policy_id'] = $cart->payment;
	$_POST['first_due_date'] = $cart->first_due_date;
	$_POST['maturity_date'] = $cart->maturity_date;
	$_POST['count_term'] = $cart->months_term;
	$_POST['rebate'] = $cart->rebate;
	$_POST['financing_rate'] = $cart->financing_rate;
	$_POST['co_maker'] = $cart->co_maker;
	$_POST['discount_dp_amount'] = $cart->discount_dp_amount;
	$_POST['stype_id'] = $cart->stype_id;

	if ($cart->trans_type == ST_SITERMMOD || $cart->trans_type == ST_RESTRUCTURED) {
		$_POST['amort_diff'] = $cart->amort_diff;
		$_POST['months_paid'] = $cart->months_paid;
		$_POST['amort_delay'] = $cart->amort_delay;
		$_POST['adj_rate'] = $cart->adj_rate;
		$_POST['opportunity_cost'] = $cart->opportunity_cost;
		$_POST['amount_to_be_paid'] = $cart->amount_to_be_paid;
		//Restructured 
		if($cart->calculation_id <> 1){
		$_POST['alloc'] = $cart->alloc;
		}
	}
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

	$row = get_DL_by_reference(get_post('document_ref'), ST_SALESINVOICE); //Added by spyrax10

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

	//Added by spyrax10
	if ($_SESSION['Items']->trans_type == ST_SALESINVOICE && get_post('document_ref') == "") {
		display_error(_("Reference # is cannot be empty!"));
		set_focus('document_ref');
		return false;
	}

	if ($_SESSION['Items']->trans_type == ST_SALESINVOICE && reference_exist(get_post('document_ref'))) {
		display_error(_("Reference # is already exist!"));
		set_focus('document_ref');
		return false;
	}

	if (($_SESSION['Items']->trans_type == ST_SALESINVOICE) 
		&& !get_post('co_maker')) {
	 	display_error(_("Co-maker is cannot be empty!"));
	 	set_focus('co_maker');
		return false;
	}

	/*Added by Albert 10/28/2021*/
	if ($_SESSION['Items']->trans_type == ST_SALESINVOICE && date('Y,m,d', strtotime(get_so_date($_SESSION['Items']->order_no))) >  date('Y,m,d', strtotime(get_post('OrderDate')))) {
		display_error(_("SI date should not be lower than SO date"));
		set_focus('OrderDate');
		return false;
	}


	// modified by Albert 09/23/2021
	if ($_SESSION['Items']->trans_type == ST_SALESINVOICE && date('Y,m,d', strtotime(get_post('first_due_date'))) < date('Y,m,d', strtotime(get_post('OrderDate'))) && $porder = _("Place Order")) {

		display_error("Not allowed first duedate before Invoice Date");
		set_focus('first_due_date');
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

	//Added by spyrax10 30 Jun 2022
	if ($_SESSION['Items']->trans_type != ST_SALESORDER && $_SESSION['Items']->trans_type != ST_SALESQUOTE && !allowed_posting_date($_POST['OrderDate'])) {
		display_error(_("The Entered Date is currently LOCKED for further data entry!"));
		set_focus('OrderDate');
		return false;
	}
	//
	
	if (count($_SESSION['Items']->line_items) == 0) {
		display_error(_("You must enter at least one non empty item line."));
		set_focus('AddItem');
		return false;
	}
	if ($low_stock = $_SESSION['Items']->check_qoh() && $_SESSION['Items']->trans_type != ST_SALESORDER && $_SESSION['Items']->trans_type != ST_SALESQUOTE) {
		display_error(_("This document cannot be processed because there is insufficient quantity for items marked."));
		return false;
	}
	if ($_SESSION['Items']->trans_type != ST_SITERMMOD || $_SESSION['Items']->trans_type != ST_RESTRUCTURED) {
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
	if (get_post('installment_policy_id') == -1 || !get_post('installment_policy_id')) {
		display_error("You need to select payment terms");
		return false;
	}

	if (($_SESSION['Items']->trans_type == ST_SITERMMOD || $_SESSION['Items']->trans_type == ST_RESTRUCTURED) && (get_post('new_installment_policy_id') == -1 || !get_post('new_installment_policy_id'))) {
		display_error("You need to select new payment terms");
		return false;
	}

	if ($_SESSION['Items']->trans_type == ST_SALESINVOICE && get_post('waranty_code') == "" && $_SESSION['Items']->category_id == 14) {
		display_error("You must enter a Warranty Code");
		return false;
	}

	if ($_SESSION['Items']->trans_type == ST_SALESINVOICE && $_SESSION['Items']->ar_amount < 0) {
		display_error("The LCP Amount is 0");
		return false;
	}

	if ($_SESSION['Items']->payment_terms['cash_sale'] &&
		($_SESSION['Items']->trans_type == ST_CUSTDELIVERY || $_SESSION['Items']->trans_type == ST_SALESINVOICE)
	) {
		$_SESSION['Items']->due_date = $_SESSION['Items']->document_date;
	}

	//Added by spyrax10
	if ($_SESSION['Items']->trans_type == ST_SITERMMOD && 
		get_post('installment_policy_id') == get_post('new_installment_policy_id')) {
		display_error(_("Old Payment Term cant be equal to New Payment Term!"));
		return false;
	}

	// if ($_SESSION['Items']->trans_type == ST_SITERMMOD && 
	// 	advance_payment($row['trans_no'], ST_SALESINVOICE, $row['debtor_no'], date2sql(get_post('OrderDate'))) > 0) {
	// 	display_error(_("Cant proceed! Transaction has already advance payment!"));
	// 	return false;
	// }

	if ($_SESSION['Items']->trans_type == ST_SITERMMOD && 
		debtor_last_month_balance($row['trans_no'], ST_SALESINVOICE, $row['debtor_no'], date2sql(get_post('OrderDate')), true) != 0) {
		display_error(_("Cant proceed! Last month amortization must be fully paid!"));
		return false;
	}

	/*Added by Albert*/
	//amortization - payment this month 
	if (total_payment_this_month($row['trans_no'], ST_SALESINVOICE, $row['debtor_no'], date2sql(get_post('OrderDate'))) != 0)
	{
		if ($_SESSION['Items']->trans_type == ST_SITERMMOD && 
			(amort_this_month($row['trans_no'], ST_SALESINVOICE, $row['debtor_no'], date2sql(get_post('OrderDate')))
			- last_month_payment($row['trans_no'], ST_SALESINVOICE, $row['debtor_no'], date2sql(get_post('OrderDate')), true) 
			) > 0) 
		{
			display_error(_("Cant proceed! Current month amortization must be fully paid!"));
			return false;
		}
	}

	if ($_SESSION['Items']->trans_type == ST_SITERMMOD && 
		(total_current_payment($row['trans_no'], ST_SALESINVOICE, $row['debtor_no'], date2sql(get_post('OrderDate')))
		+ get_post('amount_to_be_paid')) > get_post('new_ar_amount')) {
		display_error(_("Cant proceed! amortization paid greater than new Gross!"));
		return false;
	}	
			
	if (($_SESSION['Items']->trans_type == ST_SITERMMOD || $_SESSION['Items']->trans_type == ST_RESTRUCTURED) && get_post('alloc') < get_post('down_pay') ) {
		display_error(_("Cant proceed! down payment is not yet paid!"));
		return false;
	}

	if ($_SESSION['Items']->trans_type == ST_RESTRUCTURED && get_post('ar_genarate_by_amort') <> get_post('new_ar_amount') && get_post('calculation_id') == 1 ) {
		display_error(_("Cant proceed! ar_balance not equal to ar_amount genarated by amortazation!".get_post('ar_genarate_by_amort')));
		return false;
	}

	//Added by spyrax10 (Mantis Issue #867) 27 Apr 2022
	if ($_SESSION['Items']->trans_type == ST_SALESINVOICE && get_post('down_pay') == 0) {

		$total_discount = get_post('discount_dp_amount') + get_post('discount_dp2_amount');

		if ($total_discount > 0) {
			display_error(_("Item is discounted! Please enter downpayment"));
			return false;
		}
	}
	//23 Aug 2022
	if ($_SESSION['Items']->trans_type == ST_SALESINVOICE && get_post('dr_ref') == '') {
		display_error(_("Delivery Reference cannot be empty!"));
		set_focus('dr_ref');
		return false;
	}
	//
	
	/*----------*/
	return true;
}

//-----------------------------------------------------------------------------
if (isset($_POST['installment_policy_id'])) {
	installment_computation();
}
/*modified by Albert*/
if (isset($_POST['new_installment_policy_id']) && $_SESSION['Items']->trans_type == ST_SITERMMOD) {
	new_installment_computation();
}else{
	if (isset($_POST['new_installment_policy_id']) && $_SESSION['Items']->trans_type == ST_RESTRUCTURED){
		restuctured_computation();
	}
}
/**/
if (isset($_POST['update'])) {
	copy_to_cart();
	$Ajax->activate('items_table');
}

if (isset($_POST['calculate_btn'])) {
	installment_computation();
}

if (isset($_POST['ProcessOrder']) && can_process()) {
	
	$modified = ($_SESSION['Items']->trans_no != 0);
	$so_type = $_SESSION['Items']->so_type;

	if ($_SESSION['Items']->payment_policy != 0) {
		$_SESSION['Items']->payment_location = get_payment_location_by_category(get_post('category_id')) ? "Lending" : "Branch";
	}									//Modified by spyrax10
	$ret = $_SESSION['Items']->write(1, 0, get_OB_status(), get_post('waranty_code'), get_post('fsc_series'));
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
		unset($_POST['customer_id']);
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
		} elseif ($trans_type == ST_SALESINVOICE) {
			meta_forward($_SERVER['PHP_SELF'], "AddedDI=$trans_no&Type=$so_type");
		} elseif ($trans_type == ST_SITERMMOD) {
			meta_forward($_SERVER['PHP_SELF'], "AddedCT=$ret&Type=$trans_type");
		} elseif ($trans_type == ST_RESTRUCTURED) {
			meta_forward($_SERVER['PHP_SELF'], "AddedRE=$ret&Type=$trans_type");
		} else {
			meta_forward($_SERVER['PHP_SELF'], "AddedDN=$trans_no&Type=$so_type");
		}
	}
}

function installment_computation()
{
	$price = 0;
	foreach ($_SESSION['Items']->get_items() as $line_no => $line) {
		$price += $line->price * $line->qty_dispatched;
	}
	$_POST['lcp_amount'] = $price;

	/*  Computation of LCP, AR AMOUNT, Amortization */
	$policy_detail = db_fetch(get_instlpolicy_by_id(get_post('installment_policy_id')));

	$count_term_arr = explode("-", $policy_detail["plcydtl_code"]);
	$terms = floatval($count_term_arr[0]);
	$financing_rate = floatval($policy_detail["financing_rate"]);
	$rebate = floatval($policy_detail["rebate"]);
	$quotient_financing_rate = floatval($financing_rate) / 100;
	$diff_lcp_downpayment = floatval($_POST['lcp_amount']) - floatval($_POST['down_pay']);

	$amount_to_be_finance = floatval($_POST['lcp_amount']) - floatval($_POST['down_pay']);
	$interest_charge = $quotient_financing_rate * $amount_to_be_finance;

	$sum_of_interest_charge_and_atbf = $interest_charge + $amount_to_be_finance;

	$amort_wo_rebate = $sum_of_interest_charge_and_atbf / $terms;

	$amort = round($amort_wo_rebate + $rebate);

	$total_amount = $amort * $terms + floatval($_POST['down_pay']);
	$_POST['total_amount'] = $total_amount;
	$_POST['ar_amount'] = $total_amount;
	$_POST['due_amort'] = $amort;

	//modified by spyrax10
	$mature_date = add_months($_POST['first_due_date'], $terms);
	$_POST['maturity_date'] = add_months($mature_date, -1);
	//

	$_POST['rebate'] = $rebate;
	$_POST['financing_rate'] = $financing_rate;
	$_POST['count_term'] = $terms;
	/* */
	global $Ajax;
	$Ajax->activate('_page_body');
}

function new_installment_computation()
{
	$company = get_company_prefs();
	$price = 0;
	foreach ($_SESSION['Items']->get_items() as $line_no => $line) {
		$price += $line->price * $line->qty_dispatched;
	}
	$_POST['new_lcp_amount'] = $price;

	/*  Computation of LCP, AR AMOUNT, Amortization */
	$policy_detail = db_fetch(get_instlpolicy_by_id(get_post('new_installment_policy_id')));

	$count_term_arr = explode("-", $policy_detail["plcydtl_code"]);
	$terms = floatval($count_term_arr[0]);
	$financing_rate = floatval($policy_detail["financing_rate"]);
	$rebate = floatval($policy_detail["rebate"]);
	$quotient_financing_rate = floatval($financing_rate) / 100;
	$diff_lcp_downpayment = floatval($_POST['lcp_amount']) - floatval($_POST['down_pay']);

	$amount_to_be_finance = floatval($_POST['lcp_amount']) - floatval($_POST['down_pay']);
	$interest_charge = $quotient_financing_rate * $amount_to_be_finance;

	$sum_of_interest_charge_and_atbf = $interest_charge + $amount_to_be_finance;

	$amort_wo_rebate = $sum_of_interest_charge_and_atbf / $terms;

	$amort = round($amort_wo_rebate + $rebate);

	$total_amount = $amort * $terms + floatval($_POST['down_pay']);
	$_POST['new_due_amort'] = $amort;

	//modified by spyrax10
	$mature_date = add_months($_POST['first_due_date'], $terms);
	$_POST['new_maturity_date'] = add_months($mature_date, -1);
	//

	$_POST['new_rebate'] = $rebate;
	$_POST['new_financing_rate'] = $financing_rate;
	$_POST['new_count_term'] = $terms;

	$_POST['amort_diff'] = $_POST['new_due_amort'] >= $_POST['due_amort']
		? $_POST['new_due_amort'] - $_POST['due_amort']
		: $_POST['due_amort'] - $_POST['new_due_amort'];

	$_POST['months_paid'] = count_months_paid($_POST['document_ref']);

	$_POST['amort_delay'] = $_POST['new_due_amort'] > $_POST['due_amort']
		? $_POST['amort_diff'] * $_POST['months_paid']
		: 0;

	// $_POST['adj_rate'] = $_POST['new_financing_rate'] >= $_POST['financing_rate']
	// 	? $_POST['new_financing_rate'] - $_POST['financing_rate']
	// 	: $_POST['financing_rate'] - $_POST['new_financing_rate'];
	$_POST['adj_rate'] = $company["penalty_rate"];
	/*modified by Albert*/
	if($_POST['due_amort'] > $_POST['new_due_amort']){
		$_POST['opportunity_cost'] = 0;
		$_POST['amount_to_be_paid'] = 0;
	}else{
		$_POST['opportunity_cost'] = round(($_POST['amort_diff'] * $_POST['months_paid']) * ($_POST['adj_rate']));//modified by Albert
		$_POST['amount_to_be_paid'] = round($_POST['amort_delay'] + $_POST['opportunity_cost']);
	}
	/* */
	$_POST['new_total_amount'] = $total_amount;
	$_POST['new_ar_amount'] = $total_amount; //Modified by Albert
	$_POST['outstanding_ar_amount'] = $_POST['new_ar_amount'] - $_POST['alloc'];
	global $Ajax;
	$Ajax->activate('_page_body');
}
/*Added by Albert */
function restuctured_computation(){
	$company = get_company_prefs();
	$price = 0;

	if(get_post('calculation_id') == 1){
		foreach ($_SESSION['Items']->get_items() as $line_no => $line) {
			$price += $line->price * $line->qty_dispatched;
		}
		$_POST['new_lcp_amount'] = $price;

		$policy_detail = db_fetch(get_instlpolicy_by_id(get_post('new_installment_policy_id')));

		$count_term_arr = explode("-", $policy_detail["plcydtl_code"]);
		$terms = floatval($count_term_arr[0]);
		$financing_rate = floatval($policy_detail["financing_rate"]);
		$rebate = floatval($policy_detail["rebate"]);
		$quotient_financing_rate = floatval($financing_rate) / 100;

		$mature_date = add_months(get_post('new_first_due_date'), $terms);
		$_POST['new_maturity_date'] = add_months($mature_date, -1);
		//
		$_POST['alloc'] = 0;
		$_POST['new_rebate'] = $rebate;
		$_POST['new_financing_rate'] = $financing_rate;
		$_POST['new_count_term'] = $terms;

		$_POST['outstanding_ar_amount'] = round(input_num('outstanding_ar_amount_',0)); 
		$_POST['new_ar_amount'] = $_POST['outstanding_ar_amount'];
		$_POST['new_due_amort'] = round($_POST['new_ar_amount'] / $terms);
		//for blocking
		$_POST['ar_genarate_by_amort'] = $_POST['new_due_amort'] * $terms;

		$_POST['down_pay'] = 0;

		$_POST['amort_diff'] = 0;

		$_POST['months_paid'] = 0;
		$_POST['amort_delay'] = 0;

		$_POST['adj_rate'] = 0;
		$_POST['opportunity_cost'] = 0;
		$_POST['amount_to_be_paid'] = 0;
	}else{

		foreach ($_SESSION['Items']->get_items() as $line_no => $line) {
			$price += $line->price * $line->qty_dispatched;
		}
		$_POST['new_lcp_amount'] = $price;
	
		/*  Computation of LCP, AR AMOUNT, Amortization */
		$policy_detail = db_fetch(get_instlpolicy_by_id(get_post('new_installment_policy_id')));
	
		$count_term_arr = explode("-", $policy_detail["plcydtl_code"]);
		$terms = floatval($count_term_arr[0]);
		$financing_rate = floatval($policy_detail["financing_rate"]);
		$rebate = floatval($policy_detail["rebate"]);
		$quotient_financing_rate = floatval($financing_rate) / 100;
		$diff_lcp_downpayment = floatval($_POST['lcp_amount']) - floatval($_POST['down_pay']);
	
		$amount_to_be_finance = floatval($_POST['lcp_amount']) - floatval($_POST['down_pay']);
		$interest_charge = $quotient_financing_rate * $amount_to_be_finance;
	
		$sum_of_interest_charge_and_atbf = $interest_charge + $amount_to_be_finance;
	
		$amort_wo_rebate = $sum_of_interest_charge_and_atbf / $terms;
	
		$amort = round($amort_wo_rebate + $rebate);
	
		$total_amount = $amort * $terms + floatval($_POST['down_pay']);
		$_POST['new_due_amort'] = $amort;
	
		//modified by spyrax10
		$mature_date = add_months(get_post('new_first_due_date'), $terms);
		$_POST['new_maturity_date'] = add_months($mature_date, -1);
		//
	
		$_POST['new_rebate'] = $rebate;
		$_POST['new_financing_rate'] = $financing_rate;
		$_POST['new_count_term'] = $terms;
	
		$_POST['amort_diff'] = 0;
	
		$_POST['months_paid'] = count_months_paid($_POST['document_ref']);
	
		$_POST['amort_delay'] = 0;
	
		// $_POST['adj_rate'] = $_POST['new_financing_rate'] >= $_POST['financing_rate']
		// 	? $_POST['new_financing_rate'] - $_POST['financing_rate']
		// 	: $_POST['financing_rate'] - $_POST['new_financing_rate'];
		$_POST['adj_rate'] = 0;
		$_POST['opportunity_cost'] = 0;//modified by Albert
		$_POST['amount_to_be_paid'] = 0;
	
		$_POST['new_total_amount'] = $total_amount;
		$_POST['new_ar_amount'] = $total_amount; //Modified by Albert
		$_POST['outstanding_ar_amount'] = $_POST['new_ar_amount'] - $_POST['alloc'];
	}


	global $Ajax;
	$Ajax->activate("_page_body");

}

function reset_computation()
{
	$_POST['lcp_amount'] = 0;
	$_POST['total_amount'] = 0;
	$_POST['ar_amount'] = 0;
	$_POST['due_amort'] = 0;
	$_POST['down_pay'] = 0;
	$_POST['discount_dp2_amount'] = 0;
	$_POST['count_term'] = 0;
	global $Ajax;
	$Ajax->activate('_page_body');
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
	} elseif (!check_num('price', 0) && (!$SysPrefs->allow_negative_prices() || $is_inventory_item)) {
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

	if (($_POST['serialeng_no'] != "" || $_POST['chassis_no']) && $_POST['qty'] != 1) {
		display_error(_("Quantity for serialized item must be entered and can not be less than 1"));
		set_focus('qty');
		return false;
	}
	return true;
}

//--------------------------------------------------------------------------------

function handle_update_item()
{

	//Modified by spyrax10 7 Feb 2022
	if ($_POST['UpdateItem'] != '' && check_item_data()) {
		$_SESSION['Items']->update_cart_item(
			$_POST['LineNo'],
			input_num('qty'),
			input_num('price'),
			input_num('Disc') / 100,
			$_POST['item_description'],
			$_POST['serialeng_no'],
			$_POST['chassis_no'],
			$_POST['color_desc'],
			input_num('discount1'),
			input_num('discount2'),
			input_num('lcp_price'),
			$_POST['smi'],
			$_POST['incentives']
		);
	}
	//
	calculate_dp_discount();
	page_modified();
	line_start_focus();
}

//--------------------------------------------------------------------------------

function handle_delete_item($line_no)
{
	if ($_SESSION['Items']->some_already_delivered($line_no) == 0) {
		$_SESSION['Items']->remove_from_cart($line_no);
	} else {
		display_error(_("This item cannot be deleted because some of it has already been delivered."));
	}
	calculate_dp_discount();
	reset_computation();
	line_start_focus();
}

//--------------------------------------------------------------------------------

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
		$_POST['smi'],
		$_POST['incentives']
	);

	calculate_dp_discount();
	
	//Modified by spyrax10 26 Mar 2022
	$_POST['_stock_id_edit'] = $_POST['stock_id'] = $_POST['serialeng_no'] = $_POST['chassis_no'] = $_POST['lcp_price'] = 
	$_POST['color_desc'] = '';
	$_POST['qty'] = 0;
	//

	page_modified();
	global $Ajax;
	$Ajax->activate('_page_body');
	line_start_focus();
}

//--------------------------------------------------------------------------------

function  handle_cancel_order()
{
	global $path_to_root, $Ajax;


	if ($_SESSION['Items']->trans_type == ST_CUSTDELIVERY) {
		display_notification(_("Direct delivery entry has been cancelled as requested."), 1);
		submenu_option(_("Enter a New Sales Delivery"),	"/sales/sales_order_entry.php?NewDelivery=1");
	} elseif ($_SESSION['Items']->trans_type == ST_SALESINVOICE) {
		display_notification(_("Direct invoice entry has been cancelled as requested."), 1);
		submenu_option(_("Enter a New Sales Invoice"),	"/sales/sales_order_entry.php?NewInvoice=1");
	} elseif ($_SESSION['Items']->trans_type == ST_SALESQUOTE) {
		if ($_SESSION['Items']->trans_no != 0)
			delete_sales_order(key($_SESSION['Items']->trans_no), $_SESSION['Items']->trans_type);
		display_notification(_("This sales quotation has been cancelled as requested."), 1);
		submenu_option(_("Enter a New Sales Quotation"), "/sales/sales_order_entry.php?NewQuotation=Yes");
	} else { // sales order
		if ($_SESSION['Items']->trans_no != 0) {
			$order_no = key($_SESSION['Items']->trans_no);
			if (sales_order_has_deliveries($order_no)) {
				close_sales_order($order_no);
				display_notification(_("Undelivered part of order has been cancelled as requested."), 1);
				submenu_option(_("Select Another Sales Order for Edition"), "/sales/inquiry/sales_orders_view.php?type=" . ST_SALESORDER);
			} else {
				delete_sales_order(key($_SESSION['Items']->trans_no), $_SESSION['Items']->trans_type);

				display_notification(_("This sales order has been cancelled as requested."), 1);
				submenu_option(_("Enter a New Sales Order"), "/sales/sales_order_entry.php?NewOrder=Yes");
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

//--------------------------------------------------------------------------------

function calculate_dp_discount()
{
	$_POST['discount_dp_amount'] = 0;
	$_POST['discount_dp2_amount'] = 0;
	foreach ($_SESSION['Items']->get_items() as $line_no => $line) {
		/*  Added by Ronelle Get DP discount 1 and 2 from Item Discount Tables */
		if ($line->item_type == "Regular") {
			$_POST['discount_dp_amount'] += Get_DP_Discount1($line->stock_id);
			$_POST['discount_dp2_amount'] += Get_DP_Discount2($line->stock_id);
		}
		/* */
	}
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
		$doc = new Cart($type == ST_SALESINVOICE ? ST_SALESORDER : ST_SALESINVOICE, array($trans_no), false, true);
		$doc->trans_type = $type;
		$doc->trans_no = 0;
		$doc->document_date = new_doc_date();
		if ($type == ST_SALESINVOICE) {
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

if ($_SESSION['Items']->trans_type == ST_SALESINVOICE) {
	$idate = _("Invoice Date:");
	$orderitems = _("Sales Invoice Items");
	$deliverydetails = _("Enter Delivery Details and Confirm Invoice");
	$cancelorder = _("Cancel Invoice");
	$porder = _("Place Invoice");
} elseif ($_SESSION['Items']->trans_type == ST_SITERMMOD) {
	$idate = _("Change Term Date:");
	$orderitems = _("Items");
	// $deliverydetails = _("Enter Delivery Details and Confirm Dispatch");
	$cancelorder = _("Cancel");
	$porder = _("Commit Change Term Changes");
} elseif ($_SESSION['Items']->trans_type == ST_RESTRUCTURED) {
	$idate = _("Restructured Date:");
	$orderitems = _("Items");
	// $deliverydetails = _("Enter Delivery Details and Confirm Dispatch");
	$cancelorder = _("Cancel");
	$porder = _("Commit Restructured Changes");
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
//modified by albert
if($_SESSION['Items']->trans_type == ST_SITERMMOD){
	$customer_error = display_change_term_header($_SESSION['Items'], !$_SESSION['Items']->is_started(), $idate, 
						get_OB_status()); // Added by spyrax10
}else{
	$customer_error =$_SESSION['Items']->trans_type == ST_RESTRUCTURED
		? display_restructured_header($_SESSION['Items'], !$_SESSION['Items']->is_started(), $idate, get_OB_status())
		: display_order_header($_SESSION['Items'], !$_SESSION['Items']->is_started(), $idate, $_SESSION['page_title']);	
}
if ($customer_error == "") {
	start_table(TABLESTYLE, "width='95%'", 10);
	echo "<tr><td>";
	display_order_summary($orderitems, $_SESSION['Items'], true);
	echo "</td></tr>";
	if ($_SESSION['Items']->trans_type != ST_SITERMMOD || $_SESSION['Items']->trans_type != ST_RESTRUCTURED ) {
		echo "<tr><td>";
		display_delivery_details($_SESSION['Items']);
		echo "</td></tr>";
	} else {
		start_table(TABLESTYLE2);

		textarea_row(_("Remarks:"), 'Comments', null, 70, 4);

		end_table(1);
	}
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
			_('Cancels document entry or removes sales order when editing an old document'), false, ICON_CANCEL
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
			_('Cancels document entry or removes sales order when editing an old document'), false, ICON_CANCEL
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
