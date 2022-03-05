<?php

include_once($path_to_root . "/sales/includes/cart_class.inc");

//--------------------------------------------------------------------------------
function add_to_order(
	&$order,
	$new_item,
	$new_item_qty,
	$price,
	$discount,
	$description = '',
	$lot_no = '',
	$chasis_no = '',
	$color_desc = '',
	$item_type = "new",
	$discount1 = 0,
	$discount2 = 0,
	$standard_cost = 0,
	$qoh = 0,
	$trans_no = 0,
	$trans_type = 0
) {


	// calculate item price to sum of kit element prices factor for 
	// value distribution over all exploded kit items

	//$std_price = get_kit_price($new_item, $order->customer_currency, $order->sales_type,	$order->price_factor, get_post('OrderDate'), true);
	$std_price = $price;

	if ($std_price == 0)
		$price_factor = 0;
	else
		$price_factor = $price / $std_price;

	$kit = get_item_kit($new_item);
	$item_num = db_num_rows($kit);
	while ($item = db_fetch($kit)) {
		//$std_price = get_kit_price($item['stock_id'], $order->customer_currency, $order->sales_type,	$order->price_factor, get_post('OrderDate'), true);
		$std_price = $price;

		// rounding differences are included in last price item in kit
		$item_num--;
		if ($item_num) {
			$price -= $item['quantity'] * $std_price * $price_factor;
			$item_price = $std_price * $price_factor;
		} else {
			if ($item['quantity'])
				$price = $price / $item['quantity'];
			$item_price = $price;
		}
		$item_price = round($item_price, user_price_dec());

		if (!$item['is_foreign'] && $item['item_code'] != $item['stock_id']) {	// this is sales kit - recurse 
			add_to_order(
				$order,
				$item['stock_id'],
				$new_item_qty * $item['quantity'],
				$item_price,
				$discount
			);
		} else {	// stock item record eventually with foreign code

			// check duplicate stock item
			foreach ($order->line_items as $order_item) {
				if (strcasecmp($order_item->stock_id, $item['stock_id']) == 0) {
					display_warning(_("For Part :") . $item['stock_id'] . " "
						. _("This item is already on this document. You have been warned."));
					break;
				}
			}
			$order->add_to_cart(
				count($order->line_items),
				$item['stock_id'],
				$new_item_qty,
				$item_price,
				$discount,
				0,
				$standard_cost,
				$description,
				0,
				0,
				0,
				$lot_no,
				$chasis_no,
				$color_desc,
				$item_type,
				$discount1,
				$discount2,
				0,
				0,
				0,
				$trans_no,
				$trans_type
			);
		}
	}
}
//---------------------------------------------------------------------------------


function display_sr_header(&$cart)
{
	global $Ajax, $Refs, $db_connections;
	$branchcode = $db_connections[user_company()]["branch_code"];
	div_start('return_head');
	start_outer_table(TABLESTYLE2, "width='80%'");

	table_section(1);
	hidden('Location');
	hidden('category_id');
	table_section_title(_("Customer Information"));
	label_row(_("Customer"), $cart->customer_name);
	hidden('customer_id', $cart->customer_id);
	if ($cart->customer_id != get_post('customer_id', -1)) {
		// customer has changed
		$Ajax->activate('branch_id');
	}
	customer_branches_list_row(_("Cust Branch:"), $_POST['customer_id'], 'branch_id', null, false, true, true, true);
	saleorder_reason_row(_('Reason Type:'), 'sales_reason', $_POST['sales_reason'], true);   //albertP 7-29-2021
	table_section(2);
	table_section_title(_("Transaction Reference"));
	label_row(_("From Reference #"), get_trans_view_str($cart->trans_type_ref, $cart->trans_no_ref, $cart->si_no_ref));
	if (
		$_SESSION['Items']->trans_type_ref == ST_SALESINVOICE
		|| $_SESSION['Items']->trans_type_ref == ST_SALESINVOICEREPO
	)
		label_row(_("DR #"), $cart->dr_ref);
	hidden('si_no_ref');
	hidden('dr_no_ref');
	label_row(_("Payment Type"), $cart->payment_policy != 0 ? "INSTALLMENT" : "CASH");
	hidden('installment_policy_id', $cart->payment_policy);

	table_section(3);
	table_section_title(_("LCP & Cost"));
	label_row(_("Return LCP Amount"), number_format($_POST['total_prev_lcp'], 2));
	hidden('total_prev_lcp');
	label_row(_("Return Unit Cost"), number_format($_POST['total_prev_cost'], 2));
	hidden('total_prev_cost');
	label_row(_("Replace LCP Amount"), number_format($_POST['total_new_lcp'], 2));
	hidden('total_new_lcp');
	label_row(_("Replace Unit Cost"), number_format($_POST['total_new_cost'], 2));
	hidden('total_new_cost');

	table_section(4);
	table_section_title(_("Sales Return Information"));
	label_row(_("Category"), get_category_name($cart->category_id));
	label_row(_("SR Type (new or repo)"), $cart->repo_type);
	date_row(_("Date Returned:"), 'ReturnedDate', '', true, 0, 0, 0, null, true);
	ref_row(_("SR #:"), 'ref', '', null, false, ST_SALESRETURN, array('date' => @$_POST['ReturnedDate'], 'branchcode' => $branchcode));
	hidden('total_payable');
	label_row(_("Total Payable"), number_format($_POST['total_payable'], 2), "", 'style="font-weight:bold"');
	hidden('total_receivables');
	label_row(_("Total Receivable"), number_format($_POST['total_receivables'], 2), "", 'style="font-weight:bold"');

	end_outer_table(1); // outer table
	div_end();

	if (isset($_POST['_ReturnedDate_changed'])) {
		$Ajax->activate('replace_items');
	}
}

function display_returned_items(&$cart)
{
	global $Ajax;

	$_POST['error'] = 0; //Added by spyrax10

	display_heading("Return Items");

	div_start('return_items');
	start_table(TABLESTYLE, "colspan=7 width='90%'");

	$th = array(
		_("Item Code"), _("Item Description"), _("Color Code"),
		_("Color"), _("Item Type"), _("Quantity"), _("Return Qty"),
		_("Unit"), _("LCP"), _("Line Total LCP"),
		_("Cost"), _("Line Total Cost"),
		_("Serial/Engine Num"), _("Chassis Num")
	);
	table_header($th);
	foreach ($cart->get_items() as $line_no => $stock_item) {

		//Modified by spyrax10
		/*$row = get_DT_details_by_serial($_GET['NewSalesReturn'], $_GET['Filter_type'], 
			$stock_item->stock_id, $stock_item->lot_no, $stock_item->chasis_no
		); */
		$qoh = is_Serialized($stock_item->stock_id) == 1 ? 1 : $stock_item->quantity;

		$qty_outstanding = $qoh - get_total_return_unit($stock_item->id, $_SESSION['Items']->trans_type_ref);
		//
		
		if ($qty_outstanding > 0) {

			view_stock_status_cell($stock_item->stock_id);

			label_cell($stock_item->item_description);
			$dec = get_qty_dec($stock_item->stock_id);
			label_cell($stock_item->color_desc);
			label_cell(get_color_description($stock_item->color_desc, $stock_item->stock_id));
			label_cell($stock_item->item_type);

			label_cell($qty_outstanding, "nowrap align=right");
			custom_qty_cells(null, "return_" . $stock_item->id, 0, "align=right", null, $dec);
			$stock_item->qty_dispatched = input_num("return_" . $stock_item->id);
			label_cell($stock_item->units);

			$line_total_lcp = round(input_num("return_" . $stock_item->id) * $stock_item->price, user_price_dec());
			$line_total_cost = round(input_num("return_" . $stock_item->id) * $stock_item->standard_cost, user_price_dec());

			amount_cell($stock_item->price, true);
			amount_cell($line_total_lcp);
			amount_cell($stock_item->standard_cost, true);
			amount_cell($line_total_cost);
			label_cell($stock_item->lot_no);
			label_cell($stock_item->chasis_no);

			end_row();
		}
	}
	end_table();
	div_end();
}

function display_replace_items($title, &$replace_items, $editable_items = false)
{
	global $Ajax;
	display_heading($title);
	div_start('replace_items');
	$serialized = 0;
	if ($_SESSION["Items"]->line_items[0]->lot_no !== "")
		$serialized = 1;

	//Modified by spyrax10
	if ($_POST['error'] == 0) {
		search_item(
			"..",
			"stock_id",
			$_SESSION['Items']->category_id,
			$_POST['Location'],
			$_SESSION['Items']->repo_type,
			$serialized
		);
	}
	//

	start_table(TABLESTYLE, "width='90%'");
	$th = array(
		_("Item Code"), _("Item Description"), _("Color Code"),
		_("Color"), _("Item Type"),
		_("Quantity"), _("Replace Qty"),
		_("Units"), _("LCP"), _("Line Total LCP"),
		_("Cost"), _("Line Total Cost"),
		_("Serial/Engine Num"), _("Chassis Num")
	);
	if (count($replace_items->line_items))
		$th[] = '';
	table_header($th);

	$k = 0;  //row colour counter
	$repo_date = '0000-00-00';
	$id = find_submit('Edit');
	$low_stock = $replace_items->check_qoh($_POST['ReturnedDate'], $_POST['Location']);
	foreach ($replace_items->get_items() as $line_no => $stock_item) {
		if (in_array($stock_item->stock_id, $low_stock))
			start_row("class='stockmankobg'");	// notice low stock status
		else
			alt_table_row_color($k);

		//Added by spyrax10
		if ($_SESSION['Items']->repo_type == 'repo') {
			$repo = get_repo_by_reference($_POST['item_type']);
			$repo_date = $repo['repo_date'];
		}
		//
		view_stock_status_cell($stock_item->stock_id);

		label_cell($stock_item->item_description);
		$dec = get_qty_dec($stock_item->stock_id);
		label_cell($stock_item->color_desc);
		label_cell(get_color_description($stock_item->color_desc, $stock_item->stock_id));
		label_cell($stock_item->item_type);

		label_cell(
			$stock_item->quantity,
			"class='amount active'; nowrap align=right"
		);

		custom_qty_cells(null, "replace_" . $line_no, 0, "align=right", null, $dec);
		$stock_item->qty_dispatched = input_num("replace_" . $line_no);
		label_cell($stock_item->units);

		$line_total_lcp = round(input_num("replace_" . $line_no) * $stock_item->price, user_price_dec());
		$line_total_cost = round(input_num("replace_" . $line_no) * $stock_item->standard_cost, user_price_dec());

		amount_cell($stock_item->price, true);
		//
		amount_cell($line_total_lcp);
		amount_cell($stock_item->standard_cost, true);
		amount_cell($line_total_cost);
		label_cell($stock_item->lot_no);
		label_cell($stock_item->chasis_no);
		delete_button_cell(
			"Delete$line_no",
			_("Delete"),
			_('Remove line from document')
		);
		end_row();
	}
	hidden('trans_no');
	hidden('trans_type');
	hidden2('stock_id', '_stock_id_edit');
	hidden('description');
	hidden('color_desc');
	hidden('item_type');
	hidden('price');
	hidden('standard_cost');
	hidden('serialeng_no');
	hidden('chassis_no');
	hidden('qoh');
	hidden('stock_repo_date', $repo_date);

	end_table();
	if ($low_stock)
		display_note(_("Marked items have insufficient quantities in stock as on day of delivery."), 0, 1, "class='stockmankofg'");
	div_end();
}

function replacement_item_controls(&$order, &$rowcounter, $line_no = -1)
{
	global $Ajax;

	alt_table_row_color($rowcounter);

	$id = find_submit('Edit');
	if ($line_no != -1 && $line_no == $id) // edit old line
	{
		$_POST['stock_id'] = $order->line_items[$id]->stock_id;
		$dec = get_qty_dec($_POST['stock_id']);
		$_POST['qty'] = number_format2($order->line_items[$id]->qty_dispatched, $dec);
		$_POST['price'] = price_format($order->line_items[$id]->price);
		$_POST['standard_cost'] = $order->line_items[$id]->standard_cost;
		$_POST['Disc'] = percent_format($order->line_items[$id]->discount_percent * 100);
		$units = $order->line_items[$id]->units;
		$_POST['item_description'] = $order->line_items[$id]->item_description;
		hidden('stock_id', $_POST['stock_id']);
		label_cell($_POST['stock_id']);
		if ($order->line_items[$id]->descr_editable)
			text_cells(null, 'item_description', null, 45, 50);
		else {
			hidden('item_description', $_POST['item_description']);
			label_cell($_POST['item_description']);
		}
		$Ajax->activate('replace_items');
	} else	// prepare new line
	{
		if ($order->fixed_asset)
			stock_disposable_fa_list_cells(null, 'stock_id', null, _('[Select item]'), true, $order->line_items);
		else {
			if (!isset($_POST['stock_id'])) {   // Check if stock_id dosesn't exist
				$_POST['stock_id'] = last_sales_order_detail($order, 'stk_code');  //get the llast sales stock id.
			}
			//Modified by spyrax10
			si_list_cells(null, 'stock_id', getCompDet('branch_code'), $_POST['stock_id'], $_SESSION['Items']->category_id, false, true, true);
		}
		if (list_updated('stock_id')) {
			$Ajax->activate('price');
			$Ajax->activate('standard_cost');
			$Ajax->activate('units');
			$Ajax->activate('qty');
			$Ajax->activate('line_total');
			$Ajax->activate('replace_items');
		}

		$item_info = get_item_edit_info($_POST['stock_id']);
		$units = $item_info["units"];
		$dec = $item_info['decimals'];
		$_POST['qty'] = number_format2(1, $dec);

		$price = Get_Policy_Installment_Price($_SESSION['Items']->Location, $_SESSION['Items']->category_id, $_POST['stock_id']);

		if ($order->payment_policy == 0 && get_category_name($order->category_id) == "MOTORCYCLE")
			$price = Get_Policy_CashPrice($_SESSION['Items']->Location, $_SESSION['Items']->category_id, $_POST['stock_id']);

		$unit_cost = 0;
		if ($price > 0) {
			$unit_cost = get_unit_cost_average(
				$_POST['stock_id'],
				$_SESSION['Items']->Location,
				$_POST['serialeng_no'],
				$_POST['chassis_no']
			);
		}
		// $unit_cost = get_unit_cost_average($_POST['stock_id'], $_SESSION['Items']->Location);
		$_POST['standard_cost'] = $unit_cost;
		$_POST['price'] = price_format($price);
		// default to the customer's discount %
		$_POST['Disc'] = percent_format($order->default_discount * 100);
	}

	label_cell(get_post('color_desc'), "colspan='2'");
	hidden('color_desc');
	label_cell(get_post('item_type'));
	hidden('item_type');

	qty_cells(null, 'qty', $_POST['qty'], null, null, $dec);

	label_cell($units, '', 'units');

	label_cell(get_post('price'));
	hidden('price');
	label_cell(price_format(get_post('standard_cost')));
	hidden('standard_cost');
	// small_amount_cells(null, 'Disc', percent_format($_POST['Disc']), null, null, user_percent_dec());

	$line_total = input_num('qty') * input_num('price');

	amount_cell($line_total, false, '', 'line_total');

	label_cell(get_post('serialeng_no'));
	hidden('serialeng_no');
	label_cell(get_post('chassis_no'));
	hidden('chassis_no');

	if ($id != -1) {
		button_cell(
			'UpdateItem',
			_("Update"),
			_('Confirm changes'),
			ICON_UPDATE
		);
		button_cell(
			'CancelItemChanges',
			_("Cancel"),
			_('Cancel changes'),
			ICON_CANCEL
		);
		hidden('LineNo', $line_no);
		set_focus('qty');
	} else {
		submit_cells(
			'AddItem',
			_("Add Item"),
			"colspan=2 align='center'",
			_('Add new item to document'),
			true
		);
	}

	hidden('stock_trans_no');
	hidden('stock_trans_type');

	end_row();
}

/* ADDED BY ALBERT 11/17/2021 */
function display_replace_repo_items($title, &$replace_items, $editable_items = false)
{
	global $Ajax;
	display_heading($title);
	div_start('replace_items');
	start_table(TABLESTYLE, "width='90%'");
	$th = array(
		_("Item Code"), _("Item Description"), _("Color Code"),
		_("Color"), _("Item Type"), _("Quantity"),
		_("Units"), _("Unit Price"), _("Unit Cost"),
		_("Sub Total"), _("Serial/Engine Num"), _("Chassis Num"), ""
	);
	if (count($replace_items->line_items))
		$th[] = '';
	table_header($th);

	$total = 0;
	$k = 0;  //row colour counter
	$id = find_submit('Edit');
	foreach ($replace_items->get_items() as $line_no => $stock_item) {
		$line_total = round($stock_item->qty_dispatched * $stock_item->price, user_price_dec());

		if (!$editable_items || $id != $line_no) {
			alt_table_row_color($k);
			view_stock_status_cell($stock_item->stock_id);

			label_cell($stock_item->item_description);
			$dec = get_qty_dec($stock_item->stock_id);
			label_cell($stock_item->color_desc);
			label_cell(get_color_description($stock_item->color_desc, $stock_item->stock_id));
			label_cell($stock_item->item_type);
			qty_cell($stock_item->qty_dispatched, false, $dec);
			label_cell($stock_item->units);
			amount_cell($stock_item->price, true);
			amount_cell($stock_item->standard_cost, true);
			// percent_cell($stock_item->discount_percent * 100);
			amount_cell($line_total);
			label_cell($stock_item->lot_no);
			label_cell($stock_item->chasis_no);
			if ($editable_items) {
				if (empty($_GET['NewInvoice'])) {
					edit_button_cell(
						"Edit$line_no",
						_("Edit"),
						_('Edit document line')
					);
					delete_button_cell(
						"Delete$line_no",
						_("Delete"),
						_('Remove line from document')
					);
				} else {
					label_cell("");
					label_cell("");
				}
			}
			end_row();
		} else {
			replacement__repo_item_controls($replace_items, $k,  $line_no);
		}

		$total += $line_total;
	}

	if ($id == -1 && $editable_items) {
		if (!count($replace_items->line_items))
			replacement__repo_item_controls($replace_items, $k);
	}
	end_table();
	div_end();
}

function replacement__repo_item_controls(&$order, &$rowcounter, $line_no = -1)
{
	global $Ajax;

	alt_table_row_color($rowcounter);

	$id = find_submit('Edit');
	if ($line_no != -1 && $line_no == $id) // edit old line
	{
		$_POST['stock_id'] = $order->line_items[$id]->stock_id;
		$_POST['lot_no'] = $order->line_items[$id]->lot_no; //Added by Albert
		$dec = get_qty_dec($_POST['stock_id']);
		$_POST['qty'] = number_format2($order->line_items[$id]->qty_dispatched, $dec);
		$_POST['price'] = price_format($order->line_items[$id]->price);
		$_POST['standard_cost'] = $order->line_items[$id]->standard_cost;
		$_POST['Disc'] = percent_format($order->line_items[$id]->discount_percent * 100);
		$units = $order->line_items[$id]->units;
		$_POST['item_description'] = $order->line_items[$id]->item_description;
		hidden('stock_id', $_POST['stock_id']);
		label_cell($_POST['stock_id']);
		if ($order->line_items[$id]->descr_editable)
			text_cells(null, 'item_description', null, 45, 50);
		else {
			hidden('item_description', $_POST['item_description']);
			label_cell($_POST['item_description']);
		}
		$Ajax->activate('replace_items');
	} else	// prepare new line
	{
		if ($order->fixed_asset)
			stock_disposable_fa_list_cells(null, 'stock_id', null, _('[Select item]'), true, $order->line_items);
		else {
			if (!isset($_POST['stock_id'])) {   // Check if stock_id dosesn't exist
				$_POST['stock_id'] = last_sales_order_detail($order, 'stk_code');  //get the llast sales stock id.
			}
			//Modified by Albert
			si_repo_list_cells(null, 'stock_id', get_post('StockLocation'), $_POST['stock_id'], $_POST['category_id'], false, true, true);
		}
		if (list_updated('stock_id')) {
			$Ajax->activate('price');
			$Ajax->activate('standard_cost');
			$Ajax->activate('units');
			$Ajax->activate('qty');
			$Ajax->activate('line_total');
			$Ajax->activate('replace_items');
		}

		$item_info = get_item_edit_info($_POST['stock_id']);
		$units = $item_info["units"];
		$dec = $item_info['decimals'];
		$_POST['qty'] = number_format2(1, $dec);
		$lot_no = get_post('serialeng_no');
		$price = get_returned_repo_installment_price($_POST['stock_id'], $lot_no)
			? get_returned_repo_installment_price($_POST['stock_id'], $lot_no) : 0;

		if ($order->payment_policy == 0 && get_category_name($order->category_id) == "MOTORCYCLE")
			$price = get_returned_repo_installment_price($_POST['stock_id'], $lot_no)
				? get_returned_repo_installment_price($_POST['stock_id'], $lot_no) : 0;

		$unit_cost = 0;
		if ($price > 0) {
			$unit_cost = get_repo_cost_average(
				$_POST['stock_id'],
				$_SESSION['Items']->Location,
				$_POST['serialeng_no'],
				$_POST['chassis_no']
			);
		}
		// $unit_cost = get_unit_cost_average($_POST['stock_id'], $_SESSION['Items']->Location);
		$_POST['standard_cost'] = $unit_cost;
		$_POST['price'] = price_format($price);
		// default to the customer's discount %
		$_POST['Disc'] = percent_format($order->default_discount * 100);
	}

	label_cell(get_post('color_desc'), "colspan='2'");
	hidden('color_desc');
	label_cell(get_post('item_type'));
	hidden('item_type');

	qty_cells(null, 'qty', $_POST['qty'], null, null, $dec);

	label_cell($units, '', 'units');

	label_cell(get_post('price'));
	hidden('price');
	label_cell(price_format(get_post('standard_cost')));
	hidden('standard_cost');
	// small_amount_cells(null, 'Disc', percent_format($_POST['Disc']), null, null, user_percent_dec());

	$line_total = input_num('qty') * input_num('price');

	amount_cell($line_total, false, '', 'line_total');

	label_cell(get_post('serialeng_no'));
	hidden('serialeng_no');
	label_cell(get_post('chassis_no'));
	hidden('chassis_no');

	if ($id != -1) {
		button_cell(
			'UpdateItem',
			_("Update"),
			_('Confirm changes'),
			ICON_UPDATE
		);
		button_cell(
			'CancelItemChanges',
			_("Cancel"),
			_('Cancel changes'),
			ICON_CANCEL
		);
		hidden('LineNo', $line_no);
		set_focus('qty');
	} else {
		submit_cells(
			'AddItem',
			_("Add Item"),
			"colspan=2 align='center'",
			_('Add new item to document'),
			true
		);
	}

	hidden('stock_trans_no');
	hidden('stock_trans_type');

	end_row();
}
/*----end by Albert---- */
