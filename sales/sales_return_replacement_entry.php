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
$path_to_root = "..";
$page_security = 'SA_SALES_RETURN_REPLACEMENT';
include_once($path_to_root . "/sales/includes/cart_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
// include_once($path_to_root . "/includes/date_functions.inc");
// include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/sales/includes/ui/sales_return_replacement_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_types_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/includes/cost_and_pricing.inc");

include_once($path_to_root . "/sales/includes/db/sales_installment_policy_db.inc");	// Added by Ronelle 2/25/2021

set_page_security(
	ST_SALESRETURN,
	array(
		ST_SALESRETURN => 'SA_SALES_RETURN_REPLACEMENT'
	),
	array(
		'NewSalesReturn' => 'SA_SALES_RETURN_REPLACEMENT',
		'AddedID' => 'SA_SALES_RETURN_REPLACEMENT'
	)
);

$js = '';
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

$_SESSION['page_title'] = _($help_context = "Sales Return Replacement Entry");

/* modified by Albert 10/12/2021*/
if (isset($_GET['NewSalesReturn']) && isset($_GET['Filter_type'])) {
	create_cart(ST_SALESRETURN, $_GET['NewSalesReturn'], $_GET['Filter_type']);
}

/* end by Albert 10/12/2021*/
page($_SESSION['page_title'], false, false, "", $js);
if (isset($_GET['AddedID'])) {
	$sales_return = $_GET['AddedID'];

	display_notification_centered(sprintf(_("Sales Return # %d has been entered."), $sales_return));

	submenu_view(_("&View This Sales Return"), ST_SALESRETURN, $sales_return);

	submenu_print(_("&Print Sales Return"), ST_SALESRETURN, $sales_return . "-" . ST_SALESRETURN, 'prtopt');
	submenu_print(_("&Email Sales Return"), ST_SALESRETURN, $sales_return . "-" . ST_SALESRETURN, null, 1);
	set_focus('prtopt');

	display_note(get_gl_view_str(ST_SALESRETURN, $sales_return, _("View the GL &Journal Entries for this Sales Return")), 0, 1);

	display_footer_exit();
}

function copy_to_cart()
{
	$cart = &$_SESSION['Items'];

	$cart->reference = $_POST['ref'];

	//AlbertP 7-28-21
	$cart->Comments =  $_POST['sales_reason'];

	$cart->document_date = $_POST['ReturnedDate'];

	//new added by progjr on 2-20-2021
	// $cart->document_ref = $_POST['document_ref'];
	// $cart->salesman_id = $_POST['salesman_id'];
	$cart->category_id = $_POST['category_id'];

	// $newpayment = false;

	// if (isset($_POST['payment']) && ($cart->payment != $_POST['payment'])) {
	// 	$cart->payment = $_POST['payment'];
	// 	$cart->payment_terms = get_payment_terms($_POST['payment']);
	// 	$newpayment = true;
	// }
	// if ($cart->payment_terms['cash_sale']) {
	// 	if ($newpayment) {
	// 		$cart->due_date = $cart->document_date;
	// 		$cart->phone = $cart->cust_ref = $cart->delivery_address = '';
	// 		$cart->ship_via = 0;
	// 		$cart->deliver_to = '';
	// 		$cart->prep_amount = 0;
	// 	}
	// } else {
	// 	$cart->due_date = $_POST['delivery_date'];
	// 	$cart->cust_ref = $_POST['cust_ref'];
	// 	$cart->deliver_to = $_POST['deliver_to'];
	// 	$cart->delivery_address = $_POST['delivery_address'];
	// 	$cart->phone = $_POST['phone'];
	// 	$cart->ship_via = $_POST['ship_via'];
	// 	if (!$cart->trans_no || ($cart->trans_type == ST_SALESORDER && !$cart->is_started()))
	// 		$cart->prep_amount = input_num('prep_amount', 0);
	// }
	$cart->Location = $_POST['Location'];
	// $cart->freight_cost = input_num('freight_cost');
	// if (isset($_POST['email']))
	// 	$cart->email = $_POST['email'];
	// else
	// 	$cart->email = '';
	$cart->customer_id	= $_POST['customer_id'];
	$cart->Branch = $_POST['branch_id'];
	// $cart->sales_type = $_POST['sales_type'];

	// if ($cart->trans_type != ST_SALESORDER && $cart->trans_type != ST_SALESQUOTE) { // 2008-11-12 Joe Hunt
	// 	$cart->dimension_id = $_POST['dimension_id'];
	// 	$cart->dimension2_id = $_POST['dimension2_id'];
	// }
	// $cart->ex_rate = input_num('_ex_rate', null);

	$cart->payment_policy = $_POST['installment_policy_id'];
	// $cart->dp_amount = $_POST['down_pay'];
	// $cart->amortization = $_POST['due_amort'];
	// $cart->warranty_code = $_POST['waranty_code'];
	// $cart->fsc_series = $_POST['fsc_series'];
	// $cart->ar_amount = $_POST['ar_amount'];
	// $cart->first_due_date = $_POST['first_due_date'];
	// $cart->maturity_date = $_POST['maturity_date'];
	// $cart->lcp_amount = $_POST['lcp_amount'];
	// $cart->months_term = $_POST['count_term'];
	// $cart->rebate = $_POST['rebate'];
	// $cart->financing_rate = $_POST['financing_rate'];
	// $cart->payment_policy = $_POST['installment_policy_id'];

	// Sale Return Variable
	$cart->dr_trans_no = 0;
	$cart->total_payable = $_POST['total_payable'];
	$cart->total_receivable = $_POST['total_receivables'];
	$cart->total_prev_lcp = $_POST['total_prev_lcp'];
	$cart->total_new_lcp = $_POST['total_new_lcp'];
	$cart->si_no_ref = $_POST['si_no_ref'];
	$cart->dr_no_ref = $_POST['dr_no_ref'];
	$cart->total_prev_cost = $_POST['total_prev_cost'];
	$cart->total_new_cost = $_POST['total_new_cost'];
}

//--------------------------------------------------------------------------------
function check_item_data()
{
	global $SysPrefs;

	$is_inventory_item = is_inventory_item(get_post('stock_id'));

	$exists_item = 0;
	foreach ($_SESSION['ReplaceItems']->line_items as $line_no => $stock_item) {
		if (
			//Modified by spyrax10 17 Feb 2022
			$stock_item->transno_out == get_post('trans_no')
			&& $stock_item->stock_id == get_post('_stock_id_edit')
		)
			$exists_item = 1;
	}
	if (!check_num('price', 0) && (!$SysPrefs->allow_negative_prices() || $is_inventory_item)) {
		display_error(_("Price for inventory item must be entered and can not be less than 0"));
		set_focus('price');
		return false;
	} elseif (
		isset($_POST['LineNo']) && isset($_SESSION['ReplaceItems']->line_items[$_POST['LineNo']])
		&& !check_num('qty', $_SESSION['ReplaceItems']->line_items[$_POST['LineNo']]->qty_done)
	) {
		set_focus('qty');
		display_error(_("You attempting to make the quantity ordered a quantity less than has already been delivered. The quantity delivered cannot be modified retrospectively."));
		return false;
	} elseif ($exists_item) {
		display_error(_("For Part :") . get_post('_stock_id_edit') . " "
			. _("This item is already on this document."));
		return false;
	}
	return true;
}

//--------------------------------------------------------------------------------
// modified by Albert
function handle_update_item()
{
	if ($_POST['UpdateItem'] != '' && check_item_data()) {
		$_SESSION['ReplaceItems']->update_cart_item(
			$_POST['LineNo'],
			input_num('qty'),
			input_num('price'),
			input_num('Disc') / 100,
			$_POST['item_description'],
			input_num('discount1'),
			input_num('discount2'),
			input_num('lcp_price'),
			$_POST['stock_trans_no'],
			$_POST['stock_trans_type'],
			//Added by spyrax10
			'',
			''
		);
	}
	page_modified();
	line_start_focus();
}

//--------------------------------------------------------------------------------

function handle_delete_item($line_no)
{
	if ($_SESSION['ReplaceItems']->some_already_delivered($line_no) == 0) {
		$_SESSION['ReplaceItems']->remove_from_cart($line_no);
	} else {
		display_error(_("This item cannot be deleted because some of it has already been delivered."));
	}
	global $Ajax;
	$Ajax->activate('_page_body');

	line_start_focus();
}
function compute_sr_amount($new_lcp, $prev_lcp, $new_cost, $prev_cost)
{
	$total_payable = 0;
	$total_receivable = 0;
	if ($new_lcp > $prev_lcp) {
		$total_receivable = $new_lcp - $prev_lcp;
	} else {
		$total_payable = $prev_lcp - $new_lcp;
	}
	$_POST['total_payable'] = $total_payable;
	$_POST['total_receivables'] = $total_receivable;
}
//--------------------------------------------------------------------------------

function handle_new_item()
{
	if (!check_item_data()) {
		return;
	}

	//Modified by spyrax10 2 Apr 2022
	if (get_post('_stock_id_edit')) {
		add_to_order(
			$_SESSION['ReplaceItems'],
			get_post('_stock_id_edit'),
			get_post('qoh'),
			get_post('price'),
			0,
			get_post('description'),
			$_POST['serialeng_no'],
			$_POST['chassis_no'],
			$_POST['color_desc'],
			"new",
			0,
			0,
			$_POST['standard_cost'],
			get_post('qoh'),
			get_post('trans_no'),
			get_post('trans_type')
		);

		page_modified();
		global $Ajax;
		$Ajax->activate('replace_items');
		$Ajax->activate('_page_body');
		line_start_focus();
	}
	
}

function copy_from_cart()
{
	$cart = &$_SESSION['Items'];
	$_POST['ref'] = $cart->reference;

	//albertP 7-28-21
	$_POST['sales_reason'] = $cart->Comments;

	$_POST['ReturnedDate'] = $cart->document_date;
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
	$_POST['down_pay'] = $cart->dp_amount;
	$_POST['amortization'] = $cart->amortization;

	$_POST['installment_policy_id'] = $cart->payment_policy;
	$_POST['first_due_date'] = $cart->first_due_date;
	$_POST['maturity_date'] = $cart->maturity_date;
	$_POST['count_term'] = $cart->months_term;
	$_POST['rebate'] = $cart->rebate;
	$_POST['financing_rate'] = $cart->financing_rate;
	$_POST['total_new_lcp'] = 0;
	$_POST['total_payable'] = 0;
	$_POST['total_receivables'] = 0;
	$_POST['si_no_ref'] = $cart->si_no_ref;
	$_POST['dr_no_ref'] = $cart->dr_ref;
	$_POST['total_new_cost'] = $cart->total_new_cost;

	$_POST['trans_no_ref'] = $cart->trans_no_ref;
	$_POST['trans_type_ref'] = $cart->trans_type_ref;
}

//Modified by spyrax10
if (isset($_POST['_stock_id_edit'])) {
	handle_new_item();
}


if (isset($_POST['ProcessReturn']) && can_process()) {

	// update_return_qty();
	
	$ret = $_SESSION['Items']->write(0, 0, 0, "", "", true);
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
		processing_end();

		meta_forward($_SERVER['PHP_SELF'], "AddedID=$ret");
	}
}
//--------------------------------------------------------------------------------

function line_start_focus()
{
	global 	$Ajax;
	$Ajax->activate('replace_items');
	set_focus('_stock_id_edit');
}

//--------------------------------------------------------------------------------

//Added by spyrax10
function return_item_has_qty() {

	foreach ($_SESSION['Items']->line_items as $line_no => $stock_item) {
		$qty_ret = input_num("return_" . $stock_item->id);
		if ($qty_ret > 0) {
			return true;
		}
	}
	return false;
}

function total_return_item_qty() {

	$total = 0;

	foreach ($_SESSION['Items']->line_items as $line_no => $stock_item) {
		$qty_ret = input_num("return_" . $stock_item->id);
		$total = $total + $qty_ret;
	}
	return $total;
}
//

function can_process()
{

	global $Refs, $SysPrefs;
	copy_to_cart();

	if (!get_post('customer_id')) {
		display_error(_("There is no customer selected."));
		set_focus('customer_id');
		return false;
	}

	// if (count($_SESSION['ReplaceItems']->line_items) == 0) {
	// 	display_error(_("You must enter at least one non empty item line."));
	// 	set_focus('AddItem');
	// 	return false;
	// }

	$total_qty_replace = 0;
	foreach ($_SESSION['ReplaceItems']->line_items as $line_no => $stock_item) {
		$total_qty_replace += input_num("replace_" . $line_no);
		$qoh = get_qoh_si(
			$stock_item->stock_id,
			$_SESSION['Items']->Location,
			get_post('ReturnedDate'),
			$stock_item->lot_no,
			$stock_item->chasis_no,
			$_SESSION['Items']->repo_type
		);
		if ($qoh < input_num("replace_" . $line_no)) {
			display_error(_("This document cannot be processed because there is insufficient quantity for items marked."));
			return false;
			break;
		}
		if (input_num("replace_" . $line_no) <= 0) {
			display_error(_("no qty input in replace items"));
			return false;
			break;
		}

		//Added by spyrax10
		// if (input_num("replace_" . $line_no) != total_return_item_qty()) {
		// 	display_error(_("The total quantity of REPLACE ITEMS should be equal to total quantity of RETURN ITEMS!"));
		// 	return false;
		// 	break;
		// }
		
		if ($stock_item->price == 0) {
			display_error(_("Cant Proceed! LCP Price of Replaced Item is Zero!"));
			return false;
		}

		if ($_SESSION['Items']->repo_type == 'repo') {
			if (get_post("stock_repo_date") > date2sql(get_post("ReturnedDate"))) {
				display_error(_("Cant Proceed! Replace Item is not yet available!"));
				return false;
			}
		}
 		//

	}
	//Added by Albert 09/01/2023
	if(total_return_item_qty() < $total_qty_replace ){
		if ($total_qty_replace != total_return_item_qty() && $total_qty_replace != 0) {
			display_error(_("The total quantity of REPLACE ITEMS should be equal to total quantity of RETURN ITEMS!"));
			return false;
		}
	}
	foreach ($_SESSION['Items']->line_items as $line_no => $stock_item) {
		$qty = input_num("return_" . $stock_item->id);
		if ($qty <= 0 && return_item_has_qty() != 1) { //Added by spyrax10
			display_error(_("no input qty in return items"));
			return false;
			break;
		}
	}

	return true;
}

function create_cart($type, $trans_no)
{
	global $Refs, $SysPrefs;

	if (!$SysPrefs->db_ok) // create_cart is called before page() where the check is done
		return;

	processing_start();
	$_POST['ReturnedDate'] = new_doc_date();

	if (!is_date_in_fiscalyear($_POST['ReturnedDate']))
		$_POST['ReturnedDate'] = end_fiscalyear();

	/* modified by Albert 10/12/2021*/
	$type_no = ($_GET['Filter_type']);
	$doc = new Cart($type_no, array($trans_no), false, true);
	/*end by Albert */
	$doc->trans_type_ref = $type_no;
	$doc->trans_type = $type;
	$doc->trans_no_ref = $trans_no;
	$doc->trans_no = 0;
	$doc->document_date = new_doc_date();
	$doc->si_no_ref = $doc->reference;
	$doc->dr_no_ref = $doc->dr_ref;
	$doc->item_type = "Regular"; //Added by spyrax10

	$doc->reference = $Refs->get_next($doc->trans_type, null, array('date' => Today()));
	foreach ($doc->line_items as $line_no => $line) {
		$doc->line_items[$line_no]->qty_done = 0;
		$doc->line_items[$line_no]->item_type = $type_no == ST_SALESINVOICE ? "new" : "repo";
		$doc->total_prev_cost += $doc->line_items[$line_no]->standard_cost * $doc->line_items[$line_no]->qty_dispatched;
	}
	$_SESSION['Items'] = $doc;
	$_SESSION['ReplaceItems'] = new Cart(ST_SALESRETURN, 0, false, true);
	$_SESSION['ReplaceItems']->category_id = $doc->category_id;
	$_SESSION['ReplaceItems']->payment_policy = $doc->payment_policy;
	copy_from_cart();
}

function compute_refund_info()
{
	// Prev
	$sum_total_prev_cost = 0;
	$sum_total_prev_lcp = 0;
	// New
	$sum_total_new_cost = 0;
	$sum_total_new_lcp = 0;
	foreach ($_SESSION['Items']->line_items as $line_no => $stock_item) {
		$qty_outstanding = $stock_item->quantity - get_total_return_unit($stock_item->id, $_SESSION['Items']->trans_type_ref);
		if (
			isset($_POST["return_" . $stock_item->id]) &&
			($_POST["return_" . $stock_item->id] > $qty_outstanding ||
				$_POST["return_" . $stock_item->id] < 0)
		) {
			$_POST["return_" . $stock_item->id] = $qty_outstanding;
		}

		$total_prev_lcp = $stock_item->price * input_num("return_" . $stock_item->id);
		$total_prev_cost = $stock_item->standard_cost * input_num("return_" . $stock_item->id);
		$sum_total_prev_lcp += $total_prev_lcp;
		$sum_total_prev_cost += $total_prev_cost;
	}
	foreach ($_SESSION['ReplaceItems']->line_items as $line_no => $stock_item) {
		if (
			isset($_POST["replace_" . $line_no]) &&
			($_POST["replace_" . $line_no] > $stock_item->quantity ||
				$_POST["replace_" . $line_no] < 0)
		) {
			$_POST["replace_" . $line_no] = $stock_item->quantity;
		}
		$total_new_lcp = $stock_item->price * input_num("replace_" . $line_no);
		$total_new_cost = $stock_item->standard_cost * input_num("replace_" . $line_no);
		$sum_total_new_lcp += $total_new_lcp;
		$sum_total_new_cost += $total_new_cost;
	}
	// Prev
	$_POST['total_prev_lcp'] = $sum_total_prev_lcp;
	$_POST['total_prev_cost'] = $sum_total_prev_cost;
	// New
	$_POST['total_new_lcp'] = $sum_total_new_lcp;
	$_POST['total_new_cost'] = $sum_total_new_cost;

	compute_sr_amount(
		$sum_total_new_lcp,
		$sum_total_prev_lcp,
		$sum_total_new_cost,
		$sum_total_prev_cost
	);
	global $Ajax;
	$Ajax->activate("_page_body");
}

function update_return_qty()
{
	global $Ajax;
	foreach ($_SESSION['Items']->line_items as $line_no => $stock_item) {
		if (($stock_item->quantity - get_total_return_unit(get_post('qtyRet' . $line_no), $_SESSION['Items']->trans_type_ref)) > 0) {
			$_POST[$stock_item->id] = max(get_post('qtyRet' . $line_no), 0);
			if (!check_num(get_post('qtyRet' . $line_no)))
				$_POST[$stock_item->id] = number_format2(0, get_qty_dec(get_post('qtyRet' . $line_no)));
			$_SESSION['Items']->line_items[$line_no]->qty_dispatched = number_format2(get_post('qtyRet' . $line_no), 0);
		}
	}
	$Ajax->activate('return_items');
}

$id = find_submit('Delete');
if ($id != -1)
	handle_delete_item($id);

//-----------------------------------------------------------------------
start_form();
if (!isset($_POST['ProcessReturn']))
	compute_refund_info();
display_sr_header($_SESSION['Items']);
display_returned_items($_SESSION['Items']);


echo '</br>';

display_replace_items("Replace Items", $_SESSION['ReplaceItems'], true);

echo '</br>';

submit_center_first('ProcessReturn', "Return", '', 'default');


end_form();
end_page();
