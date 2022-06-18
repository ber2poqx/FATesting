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
$page_security = 'SA_SR_APPROVAL'; //Modified by spyrax10 18 Jun 2022
$path_to_root = "..";

include_once($path_to_root . "/sales/includes/cart_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

include_once($path_to_root . "/sales/includes/db/sales_installment_policy_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
    $js .= get_js_open_window(900, 500);
if (user_use_date_picker())
    $js .= get_js_date_picker();
page(_($help_context = "Draft Return Status # " . $_GET['SONumber']), false, false, "", $js);

if (isset($_GET['SONumber']) && is_numeric($_GET['SONumber'])) {
	create_cart(ST_SALESORDER, $_GET['SONumber']);
}

//--------------------------------------------------------------------------------

function create_cart($type, $trans_no)
{ 
	global $Refs, $SysPrefs;

	if (!$SysPrefs->db_ok) // create_cart is called before page() where the check is done
		return;

	processing_start();
	$_SESSION['Items'] = new Cart($type, array($trans_no));

	$company = get_company_prefs();

	$branch_code = $company["branch_code"];
	$_SESSION['Items']->Location = $branch_code;
	copy_from_cart();
}

//-----------------------------------------------------------------------------

function copy_from_cart()
{
	$cart = &$_SESSION['Items'];
	$_POST['ref'] = $cart->reference;
	$_POST['Comments'] = $cart->Comments='';

	$_POST['OrderDate'] = $cart->document_date;
	$_POST['delivery_date'] = $cart->due_date;
	$_POST['cust_ref'] = $cart->cust_ref;
	$_POST['freight_cost'] = price_format($cart->freight_cost);

	$_POST['deliver_to'] = $cart->deliver_to;
	$_POST['delivery_address'] = $cart->delivery_address;
	$_POST['phone'] = $cart->phone;
	$_POST['Location'] = $cart->Location;
	$_POST['ship_via'] = $cart->ship_via;

	$_POST['customer_id'] = -1;

	//new added by progjr on 2-20-2021
	// $_POST['document_ref'] = $cart->document_ref;
	$_POST['salesman_id'] = $cart->salesman_id;
	$_POST['category_id'] = $cart->category_id;

	$_POST['branch_id'] = $cart->Branch;
	$_POST['sales_type'] = $cart->sales_type;
	$_POST['prep_amount'] = price_format($cart->prep_amount);
	// POS 
	$_POST['payment'] = $cart->payment;
	if ($cart->trans_type!=ST_SALESORDER && $cart->trans_type!=ST_SALESQUOTE) { // 2008-11-12 Joe Hunt
		$_POST['dimension_id'] = $cart->dimension_id;
		$_POST['dimension2_id'] = $cart->dimension2_id;
	}
	$_POST['cart_id'] = $cart->cart_id;
	$_POST['_ex_rate'] = $cart->ex_rate;
	$_POST['down_pay'] = 0;
}

function display_sales_order_update_status_header(&$cart)
{
    global $Refs;

    start_outer_table(TABLESTYLE2, "width='80%'");

    table_section(1);
    label_row(_("Customer"), $cart->customer_name);
    label_row(_("Invoice #"), $cart->reference);
    // label_row(_("Reference No"), $cart->document_ref);
    label_row(_("Order Date"), $cart->document_date);
	label_row(_("Sale's Type"), $cart->stype_name);
    table_section(2);
    // label_row(_("WRC/EW Code"), $cart->warranty_code);
    // label_row(_("FSC Series"), $cart->fsc_series);
    label_row(_("Category"), $cart->category);
	label_row(_("Payment Type"), $cart->payment_policy == 0 ? "CASH" : "INSTALLMENT");
	label_row(_("Co-maker"), get_comaker($cart->customer_id, $cart->co_maker));
	if ($cart->payment_policy != 0) {
		label_row(_("1st DownPay"), number_format($cart->dp_amount));
		label_row(_("Discount DownPay"), number_format($cart->discount_dp_amount));
    	label_row(_("First Due Date"), $cart->first_due_date);
    	label_row(_("Maturity Date"), $cart->maturity_date);
	}
    
    table_section(3);
	if ($cart->payment_policy != 0) {
		label_row(_("Months Term"), $cart->months_term);
		label_row(_("Rebate"), $cart->rebate);
		label_row(_("Financing Rate"), $cart->financing_rate . "%");
		label_row(_("LCP Amount"), number_format($cart->lcp_amount));
		label_row(_("Due/Amortization"), number_format($cart->amortization));
	} else {
		label_row(_("Total LCP Amount"), number_format($cart->lcp_amount));
	}
	label_row(_("A/R Amount"), number_format($cart->ar_amount));
    end_outer_table(1); // outer table
}

function display_sales_order_update_status_items(&$cart)
{
	display_heading("Sales Order Items");
	div_start('so_items');
    start_table(TABLESTYLE, "colspan=7 width='90%'");
    $th = array(_("Item Code"), _("Item Description"), _("Color") ,_("Item Type"), _("Quantity"), _("Unit"), _("Unit Price"), _("Discount"), _("Other Discount"), _("Sub Total"), _("Serial/Engine Num"), _("Chassis Num"));
    table_header($th);
	$total = 0;
	foreach ($cart->get_items() as $line_no=>$stock_item)
	{

		$line_total = round($stock_item->qty_dispatched * $stock_item->price, user_price_dec());
		view_stock_status_cell($stock_item->stock_id);

		label_cell($stock_item->item_description);
		$dec = get_qty_dec($stock_item->stock_id);
		label_cell($stock_item->color_desc);
		label_cell($stock_item->item_type);
		qty_cell($stock_item->qty_dispatched, false, $dec);

		label_cell($stock_item->units);
		amount_cell($stock_item->price);

		// percent_cell($stock_item->discount_percent * 100);
		amount_cell($stock_item->discount1);
		amount_cell($stock_item->discount2);
		amount_cell($line_total - (($stock_item->discount1 + $stock_item->discount2) * $stock_item->qty_dispatched));
		label_cell($stock_item->lot_no);
		label_cell($stock_item->chasis_no);

		$total += $line_total;
		end_row();
	}
    end_table();
    div_end();
}

//Added by spyrax10
//------------------------------------------------------------------
function can_proceed() {

	if (get_post('Comments') == '') {
		display_error(_("Please Enter Remarks!"));
		set_focus('Comments');
		return false;
	}

	return true;
}
//------------------------------------------------------------------
$return_link = get_serial_no($_SESSION['Items']->line_items[0]->lot_no);

if (isset($_POST['Approved'])) 
{
    $_SESSION['Items']->status = "Approved";
	$_SESSION['Items']->approval_remarks = ($_POST['Comments'] = 1);
    $update_message = update_return_status($_SESSION['Items']);
	processing_end();

	if($return_link != '')
	{
		meta_forward($path_to_root . "/sales/si_repo.php?");
	}else
	{
		meta_forward($path_to_root . "/sales/sales_invoice_list.php?");
	}
}

if (isset($_POST['Disapproved'])) 
{
    $_SESSION['Items']->status = "Disapproved";
	$_SESSION['Items']->approval_remarks = ($_POST['Comments'] = 2);
    $update_message = update_return_status($_SESSION['Items']);
	processing_end();

	if($return_link != '')
	{
		meta_forward($path_to_root . "/sales/si_repo.php?");
	}else
	{
		meta_forward($path_to_root . "/sales/sales_invoice_list.php?");
	}
}
start_form();

display_sales_order_update_status_header($_SESSION['Items']);
display_sales_order_update_status_items($_SESSION['Items']);

start_table(TABLESTYLE2);

end_table(1);

//Modified by spyrax10

	submit_center_first('Approved', _("Approved"), '', 'default');
    submit_center_last('Disapproved', _("Disapproved"), '', 'default', ICON_DELETE);

//

end_form();
end_page();