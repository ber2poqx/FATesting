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
include_once($path_to_root . "/sales/includes/cart_class.inc");

//--------------------------------------------------------------------------------
function add_to_order(&$order, $new_item, $new_item_qty, $price, $discount, $description='', $lot_no='', $chasis_no='', $color_desc='', $item_type="Regular")
{
	// calculate item price to sum of kit element prices factor for 
	// value distribution over all exploded kit items

	 //$std_price = get_kit_price($new_item, $order->customer_currency, $order->sales_type,	$order->price_factor, get_post('OrderDate'), true);
	 $stat = true;
	 $std_price = $price;

	if ($std_price == 0)
		$price_factor = 0;
	else
		$price_factor = $price/$std_price;

	$kit = get_item_kit($new_item);
	$item_num = db_num_rows($kit);

	while($item = db_fetch($kit)) {
		//$std_price = get_kit_price($item['stock_id'], $order->customer_currency, $order->sales_type,	$order->price_factor, get_post('OrderDate'), true);
		$std_price = $price;

		// rounding differences are included in last price item in kit
		$item_num--;
		if ($item_num) {
			$price -= $item['quantity']*$std_price*$price_factor;
			$item_price = $std_price*$price_factor;
		} else {
			if ($item['quantity']) 
				$price = $price/$item['quantity'];
			$item_price = $price;
		}
		$item_price = round($item_price, user_price_dec());

		if (!$item['is_foreign'] && $item['item_code'] != $item['stock_id'])
		{	// this is sales kit - recurse 
			add_to_order($order, $item['stock_id'], $new_item_qty*$item['quantity'],
				$item_price, $discount);
		}
		else
		{	// stock item record eventually with foreign code

			// check duplicate stock item
			foreach ($order->line_items as $order_item) {

				//-----------------AlbertP 1 isto 1 in serialised-------------------------------
				if (
					listofserialize($order_item->stock_id) == 1 && listofserialize($item['stock_id']) == 1
					&& $item_type == "Regular"
				) {

					$stat = false;
				} else {

					if (strcasecmp($order_item->stock_id, $item['stock_id']) == 0) {
						display_warning(_("For Part :") . $item['stock_id'] . " "
							. _("This item is already on this document. You have been warned."));
						break;
					}
				}
			}
			if ($stat == true) {
				$order->add_to_cart (
					count($order->line_items),	
					$item['stock_id'], 
					$new_item_qty*$item['quantity'], 
					$item_price, 
					$discount, 
					0,
					0, 
					$description, 
					0, 
					0, 
					0, 
					$lot_no, 
					$chasis_no,  
					$color_desc,
					$item_type,
					0, 0, 0, 0, 0, 0, 0
				);
		
			}else{
				display_error(_("only one serialized can process."));

				$stat = true;
			}
		}
	}

}
//---------------------------------------------------------------------------------
function listofserialize($stock_id)
{
	$sql = "SELECT A.serialised FROM " . TB_PREF . "stock_master A 
		WHERE A.stock_id=" . db_escape($stock_id);

	$result = db_query($sql, "could'nt get data!");
	$row = db_fetch_row($result);
	return $row[0];
}
function get_customer_details_to_order(&$order, $customer_id, $branch_id)
{
	global $SysPrefs;
	
	$ret_error = "";

	$myrow = get_customer_to_order($customer_id);

	$name = $myrow['name'];

	if ($myrow['dissallow_invoices'] == 1)
		$ret_error = _("The selected customer account is currently on hold. Please contact the credit control personnel to discuss.");

	$deliver = $myrow['address']; // in case no branch address use company address

	$order->set_customer($customer_id, $name, $myrow['curr_code'], 
		$myrow['discount'], $myrow['payment_terms'], $myrow['pymt_discount']);

	// the sales type determines the price list to be used by default
	$order->set_sales_type($myrow['salestype'], $myrow['sales_type'], $myrow['tax_included'],
	    $myrow['factor']);

	$order->credit = $myrow['cur_credit'];

	if ($order->trans_type != ST_SALESORDER && $order->trans_type != ST_SALESQUOTE)
	{
		$order->dimension_id = $myrow['dimension_id'];
		$order->dimension2_id = $myrow['dimension2_id'];
	}
	$result = get_branch_to_order($customer_id, $branch_id);

	if (db_num_rows($result) == 0)
	{
		return _("The selected customer and branch are not valid, or the customer does not have any branches.");
	}

	$myrow = db_fetch($result);

	// FIX - implement editable contact selector in sales order 
	$contact = get_branch_contacts($branch_id, 'order', $customer_id);
	$order->set_branch($branch_id, $myrow["tax_group_id"],
	$myrow["tax_group_name"], @$contact["phone"], @$contact["email"]);

	$address = trim($myrow["br_post_address"]) != '' ? $myrow["br_post_address"]
		: (trim($myrow["br_address"]) != '' ? $myrow["br_address"]:$deliver);

	$order->set_delivery($myrow["default_ship_via"], $myrow["br_name"],
		$address);
	if ($order->trans_type == ST_SALESINVOICE) {
		$order->due_date = get_invoice_duedate($order->payment, $order->document_date);
	}
	elseif ($order->trans_type == ST_SALESORDER)
		$order->due_date = add_days($order->document_date, $SysPrefs->default_delivery_required_by());
	elseif ($order->trans_type == ST_SALESQUOTE)
		$order->due_date = add_days($order->document_date, $SysPrefs->default_quote_valid_days());

	if($order->payment_terms['cash_sale']) {
		$order->set_location($order->pos["pos_location"], $order->pos["location_name"]);
	} else
		$order->set_location($myrow["default_location"], $myrow["location_name"]);

	return $ret_error;
}

//---------------------------------------------------------------------------------

function display_order_summary($title, &$order, $editable_items=false)
{

	display_heading($title);

    div_start('items_table');
	
	start_table(TABLESTYLE, "width='90%'");
	$th = array(_("Item Code"), _("Item Description"), _("Color Description"), _("Quantity"), _("Unit Price"), _("Serial/Engine Num"), _("Chassis Num"), "");

	if (count($order->line_items))
	     $th[]= '';

	table_header($th);

	$total = 0;
	$k = 0;  //row colour counter

	$id = find_submit('Edit');
	foreach ($order->get_items() as $line_no=>$stock_item)
	{

		$line_total = round($stock_item->qty_dispatched * $stock_item->price, user_price_dec());

		$qoh_msg = '';
		if (!$editable_items || $id != $line_no)
		{
			alt_table_row_color($k);

			view_stock_status_cell($stock_item->stock_id);

			label_cell($stock_item->item_description);
			$dec = get_qty_dec($stock_item->stock_id);
			label_cell($stock_item->color_desc);
			qty_cell($stock_item->qty_dispatched, false, $dec);

			if ($order->trans_no!=0)
				qty_cell($stock_item->qty_done, false, $dec);

			// label_cell($stock_item->units);
			amount_cell($stock_item->price);

			// percent_cell($stock_item->discount_percent * 100);
			// amount_cell($line_total);
			label_cell($stock_item->lot_no);
			label_cell($stock_item->chasis_no);
			if ($editable_items)
			{
				if(empty($_GET['NewInvoice'])) {
					edit_button_cell("Edit$line_no", _("Edit"),
					_('Edit document line'));
					delete_button_cell("Delete$line_no", _("Delete"),
					_('Remove line from document'));
					
				} else {
					label_cell("");
					label_cell("");
				}
				
			}
			end_row();
		}
		else
		{
			sales_order_item_controls($order, $k,  $line_no);
		}

		$total += $line_total;
	}

	if ($id==-1 && $editable_items) {
		sales_order_item_controls($order, $k);	
	}
		
	end_table();

    div_end();
}

// ------------------------------------------------------------------------------

function display_order_header(&$order, $editable, $date_text)
{
	global $Ajax, $SysPrefs;

	div_start("order_header");
	start_outer_table(TABLESTYLE2, "width='80%'");

	table_section(1);
	
	$customer_error = "";
	$change_prices = 0;
	
	if (isset($order) && !$editable)
	{
		label_row(null, $order->customer_name . " - " . $order->deliver_to);
		hidden('customer_id', $order->customer_id);
		hidden('branch_id', $order->Branch);
		hidden('sales_type', $order->sales_type);
		if ($order->trans_type != ST_SALESORDER  && $order->trans_type != ST_SALESQUOTE) {
			hidden('dimension_id', $order->dimension_id); // 2008-11-12 Joe Hunt
			hidden('dimension2_id', $order->dimension2_id);
		}	
	}
	else
	{
		customer_list_row(_("Customer:"), 'customer_id', null, false, true, false, true);

		if ($order->customer_id != get_post('customer_id', -1))
		{
			// customer has changed
			$Ajax->activate('branch_id');
  		}
		customer_branches_list_row(_("Cust Branch:"), $_POST['customer_id'], 'branch_id', null, false, true, true, true);

		if( ($order->customer_id != get_post('customer_id', -1)) ||
			($order->Branch != get_post('branch_id', -1)) ||
				list_updated('customer_id')) 
		{

			if (!isset($_POST['branch_id']) || $_POST['branch_id'] == "")
			{
				// ignore errors on customer search box call
				// if ($_POST['customer_id'] == '')
				// 	$customer_error = _("No customer found for entered text.");
				// else
				// 	$customer_error = _("The selected customer does not have any branches. Please create at least one branch.");
				// $customer_error = _("The selected customer does not have any branches. Please create at least one branch.");
				unset($_POST['branch_id']);
				$order->Branch = 0;
			} 
			else
			{

				$old_order = (PHP_VERSION<5) ? $order : clone $order;

				$customer_error = get_customer_details_to_order($order, $_POST['customer_id'], $_POST['branch_id']);
				$_POST['Location'] = $order->Location;
				$_POST['deliver_to'] = $order->deliver_to;
				$_POST['delivery_address'] = $order->delivery_address;
				$_POST['phone'] = $order->phone;
				$_POST['delivery_date'] = $order->due_date;

				if (!in_array($order->trans_type, array(ST_SALESQUOTE, ST_SALESORDER))
					&& ($order->pos['cash_sale'] != $order->pos['credit_sale'])
					&& (($order->payment_terms['cash_sale'] && !$order->pos['cash_sale']) ||
						(!$order->payment_terms['cash_sale'] && !$order->pos['credit_sale']))) {
							// force payment terms refresh if terms are editable
							// and pos have no permitions for terms selected in customer record.
							// Terms are set to first terms in allowed category below.
							display_warning(
								sprintf(_("Customer's payment terms '%s' cannot be selected on this POS"),
									$order->payment_terms['terms']));
							$order->payment = '';
				} elseif (get_post('payment') !== $order->payment) {
					$_POST['payment'] = $order->payment;
					$Ajax->activate('delivery');
					$Ajax->activate('payment');
				} else {
					if ($order->trans_type == ST_SALESINVOICE)
					{
						$_POST['delivery_date'] = $order->due_date;
						$Ajax->activate('delivery_date');
					}
					$Ajax->activate('Location');
					$Ajax->activate('deliver_to');
					$Ajax->activate('phone');
					$Ajax->activate('delivery_address');
				}
				// change prices if necessary
				// what about discount in template case?
				if ($old_order->customer_currency != $order->customer_currency) {
					$change_prices = 1;
				}
				if ($old_order->sales_type != $order->sales_type) {
				//  || $old_order->default_discount!=$order->default_discount
					$_POST['sales_type'] = $order->sales_type;
					$Ajax->activate('sales_type');
					$change_prices = 1;
				}
				if ($old_order->dimension_id != $order->dimension_id) {
					$_POST['dimension_id'] = $order->dimension_id;
				    $Ajax->activate('dimension_id');
				}
				if ($old_order->dimension2_id != $order->dimension2_id) {
					$_POST['dimension2_id'] = $order->dimension2_id;
				    $Ajax->activate('dimension2_id');
				}
				unset($old_order);
			}
			set_global_customer($_POST['customer_id']);
		} // changed branch
		else
		{
			$row = get_customer_to_order($_POST['customer_id']);
			if ($row['dissallow_invoices'] == 1)
				$customer_error = _("The selected customer account is currently on hold. Please contact the credit control personnel to discuss.");
		}
	}
	if ($order->trans_type == ST_SALESQUOTE) {
		ref_row(_("Quotation No.").':', 'ref', _('Reference number unique for this document type'), null, '', $order->trans_type, array('date'=> @$_POST['OrderDate']));
	}elseif($order->trans_type == ST_SALESORDER) {
		ref_row(_("Order No.").':', 'ref', _('Reference number unique for this document type'), null, '', $order->trans_type, array('date'=> @$_POST['OrderDate']));
	}else{
		ref_row(_("Invoice No.").':', 'ref', _('Reference number unique for this document type'), null, '', $order->trans_type, array('date'=> @$_POST['OrderDate']));
	}
	text_row_ex(_("Reference No.").':', 'document_ref', 16);
	text_row_ex(_("Old Trans No.").':', 'old_trans_no', 16);

	
	if ($editable)
	{
		if (!isset($_POST['OrderDate']) || $_POST['OrderDate'] == "")
			$_POST['OrderDate'] = $order->document_date;

		date_row($date_text, 'OrderDate', _('Date of order receive'),
			$order->trans_no==0, 0, 0, 0, null, true);
		if (isset($_POST['_OrderDate_changed']) || list_updated('payment')) {
			if (!is_company_currency($order->customer_currency) 
				&& (get_base_sales_type()>0)) {
					$change_prices = 1;
			}
			$Ajax->activate('_ex_rate');
			if ($order->trans_type == ST_SALESINVOICE) {
				$_POST['delivery_date'] = get_invoice_duedate(get_post('payment'), get_post('OrderDate'));
			} else 
				$_POST['delivery_date'] = add_days(get_post('OrderDate'), $SysPrefs->default_delivery_required_by());
			$Ajax->activate('items_table');
			$Ajax->activate('delivery_date');

			//Added by Albert
			$policy_detail = db_fetch(get_instlpolicy_by_id(get_post('installment_policy_id')));
			$count_term_arr = explode("-", $policy_detail["plcydtl_code"]);
			$terms = floatval($count_term_arr[0]);

			$_POST['first_due_date'] = add_months($_POST['OrderDate'], 1);
			$mature_date = add_months($_POST['first_due_date'], $terms);
			$_POST['maturity_date'] = add_months($mature_date, -1);

			$Ajax->activate('first_due_date');
			$Ajax->activate('maturity_date');

		}
		if ($order->trans_type != ST_SALESORDER && $order->trans_type != ST_SALESQUOTE)
		{	// 2008-11-12 Joe Hunt added dimensions
			$dim = get_company_pref('use_dimension');
			if ($dim > 0)
				dimensions_list_row(_("Dimension").":", 'dimension_id', 
					null, true, ' ', false, 1, false);
			else
				hidden('dimension_id', 0);
			if ($dim > 1)
				dimensions_list_row(_("Dimension")." 2:", 'dimension2_id', 
					null, true, ' ', false, 2, false);
			else
				hidden('dimension2_id', 0);
		}	
	}
	else
	{
		label_row($date_text, $order->document_date);
		hidden('OrderDate', $order->document_date);
	}

	if ($order->trans_type == ST_SALESQUOTE){
		customer_credit_row($_POST['customer_id'], $order->credit);
		label_row(_("Customer Discount:"), ($order->default_discount * 100) . "%");
	}else{

	}

	//modify for new quotation/sales cash invoice by prog jr
	if (isset($_POST['salesman_id']) == 0){
		$_POST['salesman_id'] = 1;
	}
	sales_persons_list_row( _("Sales Person:"), 'salesman_id', $_POST['salesman_id']);
	saleorder_types_row(_("Sale Type:"), 'sales_type', $_POST['sales_type'], true);

	table_section(2);

	start_row();
	if (($order->pos['cash_sale'] || $order->pos['credit_sale']) 
		&& !$order->is_started()) {
		 // editable payment type 
		if (get_post('payment') !== $order->payment) {
			$order->payment = get_post('payment');
			$order->payment_terms = get_payment_terms($order->payment);
			$order->due_date = get_invoice_duedate($order->payment, $order->document_date);
			if ($order->payment_terms['cash_sale']) {
				$_POST['Location'] = $order->Location = $order->pos['pos_location'];
				$order->location_name = $order->pos['location_name'];
			}
			$Ajax->activate('items_table');
			$Ajax->activate('delivery');
		}
		$paymcat = !$order->pos['cash_sale'] ? PM_CREDIT : 
			(!$order->pos['credit_sale'] ? PM_CASH : PM_ANY);
		// all terms are available for SO
		$is_order = in_array($order->trans_type, array(ST_SALESQUOTE, ST_SALESORDER));
		// sale_payment_list_cells(_('Payment Term:'), 'payment', $is_order ? PM_ANY : $paymcat, null, true, $is_order);
	} else {
		label_cells(_('Payment Term:'), $order->payment_terms['terms'], "class='label'");
	}
	end_row();
	
	if (!is_company_currency($order->customer_currency) && in_array($order->trans_type, array(ST_SALESINVOICE, ST_CUSTDELIVERY)))
	{
		label_row(_("Customer Currency:"), $order->customer_currency);
		exchange_rate_display(get_company_currency(), $order->customer_currency,
			($editable ? $_POST['OrderDate'] : $order->document_date));
	}

	if ($order->trans_type != ST_SALESQUOTE) {
		text_row_ex(_("WRC/EW Code").':', 'waranty_code', 16);
		text_row_ex(_("FSC Series").':', 'fsc_series', 16);
		
	}

	$category_disabled = false;
	// if (count($order->get_items()) != 0) {
	// 	$category_disabled = true;
	// }
	
	stock_categories_list_row(_("Category:"), 'category_id', $_POST['category_id'], "Select", true, false, $category_disabled);
	if ($order->category_id != $_POST['category_id']) {
		$order->set_category($order->category_id, get_category_name($order->category_id));
		$Ajax->activate('items_table');
	}
	text_row_ex1(_("1st DownPay:"), 'down_pay', 16);
	date_row("First Due Date:", 'first_due_date', _('Date of first due'),
			$order->trans_no==0, 0, 1, 0, null, true);
	date_row("Maturity Date:", 'maturity_date', _('Date of maturity'),
			$order->trans_no==0, 0, 0, 0, null, true);
	if (count($order->get_items()) != 0) {
		table_section(3);
	
		installment_policy_list_row(_("Payment Term:"), 'installment_policy_id', $_POST['category_id'], get_post('installment_policy_id'), "Select", true);

		//Modified by Albert
		$half = 0.5 / pow(10, 2); 
		$profit_margin = round(number_format(get_post('deferred_gross_profit') / get_post('ar_amount'), 5) - $half,2,PHP_ROUND_HALF_DOWN);	
		$policy_detail = db_fetch(get_instlpolicy_by_id($_POST['installment_policy_id']));
		$count_term_arr = explode("-", $policy_detail["plcydtl_code"]);
		label_row(_("Months Term:"), $count_term_arr[0]);
		label_row(_("Rebate:"), price_format($policy_detail["rebate"]));
		label_row(_("Financing Rate:"), $policy_detail["financing_rate"] . "%");


		hidden('lcp_amount');
		hidden('total_amount');
		hidden('due_amort');
		hidden('ar_amount');
		hidden('count_term');
		hidden('rebate');
		hidden('financing_rate');

		$_POST['count_term'] = $count_term_arr[0];
		$_POST['rebate'] = $policy_detail["rebate"];
		$_POST['financing_rate'] = $policy_detail["financing_rate"];
		$oustanding_ar_balance = get_post('ar_amount') - get_post('total_amount_paid');

		/*Modified by Albert*/
		// label_row(_("LCP Amount:"), price_format(get_post('lcp_amount')));
		text_row_ex1(_("Total Unit Cost:"), 'ob_standard_cost', 16); //Modified by spyrax10
		text_row_ex1(_("DGP Balance:"),'deferred_gross_profit', 16);
	
		text_row_ex(_("Profit Margin:"), 'profit_margin', 16,20,20,$profit_margin);
		$Ajax->activate('profit_margin');
		label_row(_("Due/Amortization:"), price_format(get_post('due_amort')));
		label_row(_("A/R Amount:"), price_format(get_post('ar_amount')));
		text_row_ex1(_("Total Amount Paid:"), 'total_amount_paid', 16); //modified by Albert
		label_row(_("Oustanding A/R Balance:"), price_format($oustanding_ar_balance));

		submit_cells('calculate_btn', _("Calculate"), "", _("Compute AmortizaTion"), true);
	}

	end_outer_table(1); // outer table
	div_end();

	if ($change_prices != 0) {
		foreach ($order->line_items as $line_no=>$item) {
			$line = &$order->line_items[$line_no];
			//$line->price = get_kit_price($line->stock_id, $order->customer_currency, $order->sales_type, $order->price_factor, get_post('OrderDate'));
			$line->price = Get_Policy_Installment_Price($_POST['Location'], $_POST['category_id'], $line->stock_id);
		}
	    $Ajax->activate('items_table');
	}

	return $customer_error;
}

//--------------------------------------------------------------------------------

function sales_order_item_controls(&$order, &$rowcounter, $line_no=-1)
{
    global $Ajax;

	alt_table_row_color($rowcounter);

	$id = find_submit('Edit');
	if ($line_no!=-1 && $line_no == $id) // edit old line
	{
		$_POST['stock_id'] = $order->line_items[$id]->stock_id;
		$dec = get_qty_dec($_POST['stock_id']);
		$_POST['qty'] = number_format2($order->line_items[$id]->qty_dispatched, $dec);
		$_POST['price'] = price_format($order->line_items[$id]->price);
		$_POST['Disc'] = percent_format($order->line_items[$id]->discount_percent*100);
		$units = $order->line_items[$id]->units;
		$_POST['item_description'] = $order->line_items[$id]->item_description;
		$_POST['color_desc'] = $order->line_items[$id]->color_desc;
	
		hidden('stock_id', $_POST['stock_id']);
		label_cell($_POST['stock_id']);
		if ($order->line_items[$id]->descr_editable)
			text_cells(null,'item_description', null, 45, 50);
		else {
			hidden('item_description', $_POST['item_description']);
			label_cell($_POST['item_description']);
		}
	    $Ajax->activate('items_table');
	}
	else	// prepare new line
	{
		if ($order->fixed_asset)
            stock_disposable_fa_list_cells(null,'stock_id', null, _('[Select item]'), true, $order->line_items);
        else {
			//commented by spyrax10
            /*if (!isset($_POST['stock_id'])) {   // Check if stock_id dosesn't exist
				$_POST['stock_id'] = last_sales_order_detail($order, 'stk_code');  //get the llast sales stock id.
			} */
			//Modified by spyrax10
            stock_items_list_cells(null,'stock_id', null, false, true, true, 
				false, get_post('category_id'));
			//
		}
        if (list_updated('stock_id')) {            
            $Ajax->activate('price');
            $Ajax->activate('units');
            $Ajax->activate('qty');
            // $Ajax->activate('line_total');
        }		

		$item_info = get_item_edit_info($_POST['stock_id']);
		$units = $item_info["units"];
		$dec = $item_info['decimals'];
		$_POST['qty'] = number_format2(1, $dec);
		
		//get price base on policy - progjr
		//$price = get_kit_price($_POST['stock_id'], $order->customer_currency, $order->sales_type, $order->price_factor, get_post('OrderDate'));
		// $price = Get_Policy_Installment_Price($_POST['Location'], $_POST['category_id'], $_POST['stock_id']);

		$_POST['price'] = 0;
		// default to the customer's discount %
		$_POST['Disc'] = percent_format($order->default_discount * 100);
	}

	text_cells(null, 'color_desc');

	qty_cells(null, 'qty', null, null, null, $dec);
	
	if ($order->trans_no!=0) {
		qty_cell($line_no==-1 ? 0 :$order->line_items[$line_no]->qty_done, false, $dec);
	}

	// label_cell($units, '', 'units');

	amount_cells(null, 'price', null, null, null, $dec);

	// small_amount_cells(null, 'Disc', percent_format($_POST['Disc']), null, null, user_percent_dec());

	// $line_total = input_num('qty') * input_num('price');

	// amount_cell($line_total, false, '','line_total');

	/*----Modified BY ALBERT */
	if(get_post('category_id') == 14)
	{
		text_cells(null, 'serialeng_no');
		text_cells(null, 'chassis_no');
	}

	if(get_post('category_id') == 15 || get_post('category_id') == 19 )
	{
		text_cells(null, 'serialeng_no');
	}
	/*----END BY ALBERT */

	if ($id!=-1)
	{
		button_cell('UpdateItem', _("Update"),
				_('Confirm changes'), ICON_UPDATE);
		button_cell('CancelItemChanges', _("Cancel"),
				_('Cancel changes'), ICON_CANCEL);
		hidden('LineNo', $line_no);
		set_focus('qty');
	}
	else
	{
		submit_cells('AddItem', _("Add Item"), "colspan=2 align='center'",
		    _('Add new item to document'), true);
	}

	end_row();
}

//--------------------------------------------------------------------------------

function display_delivery_details(&$order)
{
	global $Ajax;

	div_start('delivery');	


	if ($order->payment_terms['cash_sale']) {	// Direct payment sale
		$Ajax->activate('items_table');
		display_heading(_('Cash payment'));
		start_table(TABLESTYLE2, "width='60%'");

		locations_list_row(_("Deliver from Location:"), 'Location', null, false, true);
 		if (list_updated('Location'))
 			$Ajax->activate('items_table');
		label_row(_("Cash account:"), $order->pos['bank_account_name']);
		textarea_row(_("Comments:"), "Comments", $order->Comments, 31, 5);
		end_table();
		hidden('delivery_date', $order->due_date);
	} else {
		if ($order->trans_type==ST_SALESINVOICE)
		{
			$title = _("Delivery Details");
			$delname = _("Due Date").':';
		}
		elseif ($order->trans_type==ST_CUSTDELIVERY)
		{
			$title = _("Invoice Delivery Details");
			$delname = _("Invoice before").':';
		}
		elseif ($order->trans_type==ST_SALESQUOTE)
		{
			$title = _("Quotation Delivery Details");
			$delname = _("Valid until").':';
		}
		else
		{
			$title = _("Order Delivery Details");
			$delname = _("Required Delivery Date").':';
		}
		display_heading($title);
		start_outer_table(TABLESTYLE2, "width='90%'");
		table_section(1);
		hidden('Location');
		if ($_POST['Location'] != "HO") {
			label_row(_("Deliver from Location: ") , get_location_name($order->Location));
		} else {
			locations_list_row(_("Deliver from Location:"), 'Location', null, false, true, $order->fixed_asset);
		}
			
		if ($order->payment_terms['days_before_due'] == -1)
		{
			$Ajax->addUpdate('items_table', 'prep_amount', price_format($order->get_trans_total())); // bind to items_table update
			if (!$order->is_started())
				amount_row(_("Pre-Payment Required:"), 'prep_amount');
			else
				label_row(_("Pre-Payment Required:"), price_format($order->prep_amount), "class='label'");
		}
		if (list_updated('Location'))
			$Ajax->activate('items_table');


		date_row($delname, 'delivery_date',
			$order->trans_type==ST_SALESORDER ?  _('Enter requested day of delivery') 
				: $order->trans_type==ST_SALESQUOTE ? _('Enter Valid until Date') : '');
		text_row(_("Deliver To:"), 'deliver_to', $order->deliver_to, 50, 60,
			_('Additional identifier for delivery e.g. name of receiving person'));

		textarea_row(_("Address:"), 'delivery_address', $order->delivery_address, 35, 5,
			_('Delivery address. Default is address of customer branch'));

		table_section(2);

		text_row(_("Contact Phone Number:"), 'phone', $order->phone, 25, 25,
		    _('Phone number of ordering person. Defaults to branch phone number'));
		text_row(_("Customer Reference:"), 'cust_ref', $order->cust_ref, 25, 25,
		  _('Customer reference number for this order (if any)'));
		textarea_row(_("Comments:"), "Comments", $order->Comments, 31, 5);

		shippers_list_row(_("Shipping Company:"), 'ship_via', $order->ship_via);

		end_outer_table(1);
	}
	div_end();
}

