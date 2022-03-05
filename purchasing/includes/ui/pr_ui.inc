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
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");

function pr_copy_from_cart()
{
	$cart = &$_SESSION['PR'];

	$_POST['supplier_id'] = $cart->supplier_id;
	$_POST['PRDate'] = $cart->orig_order_date;
	$_POST['due_date'] = $cart->due_date;
	// $_POST['supp_ref'] = $cart->supp_ref;
	$_POST['po_reference'] = $cart->po_reference;
	$_POST['StkLocation'] = $cart->Location;
	$_POST['delivery_address'] = $cart->delivery_address;
	$_POST['reference'] = $cart->reference;
	$_POST['Comments'] = $cart->Comments;
	$_POST['purch_type_id'] = $cart->purch_type_id;
	$_POST['category'] = $cart->category_id;
	$_POST['served_status'] = $cart->served_status;
}

function pr_copy_to_cart()
{
	$cart = &$_SESSION['PR'];
	$cart->orig_order_date = $_POST['PRDate'];
	$cart->supplier_id = $_POST['supplier_id'];
	$cart->due_date = $_POST['due_date'];
	$cart->reference = $_POST['reference'];
	// $cart->supp_ref = $_POST['supp_ref'];
	$cart->Comments = $_POST['Comments'];
	$cart->purch_type_id = $_POST['purch_type_id'];
	$cart->category_id = $_POST['category'];
	$cart->served_status = $_POST['served_status'];
}

//---------------------------------------------------------------------------------------------------

function create_new_pr($trans_type, $trans_no)
{
	global $Refs, $db_connections;
	$branchcode = $db_connections[user_company()]["branch_code"];
	if (isset($_SESSION['PR']))
		unset($_SESSION['PR']->line_items, $_SESSION['PR']);

	$cart = new purch_request;
	$_POST['PRDate'] = new_doc_date();

	if (!is_date_in_fiscalyear($_POST['PRDate']))
		$_POST['PRDate'] = end_fiscalyear();

	$cart->due_date = $cart->orig_order_date = $_POST['PRDate'];

	$cart->trans_type = $trans_type;
	$cart->reference = $trans_no;
	/*read in all the selected order into the Items cart  */
	if ($trans_no) {
		read_pr($trans_no, $cart);
		$cart->reference = $trans_no;
	} else
		$cart->reference = $Refs->get_next(
			$trans_type,
			null,
			array('supplier_id' => $cart->supplier_id, 'date' => get_post('PRDate'), 'branchcode' => $branchcode)
		);
	$_SESSION['PR'] = &$cart;
}

//---------------------------------------------------------------------------------------------------

function display_pr_header(&$request)
{
	global $Ajax, $Refs, $db_connections;
	$branchcode = $db_connections[user_company()]["branch_code"];

	$editable = ($request->pr_no == 0);

	start_outer_table(TABLESTYLE2, "width='80%'");

	table_section(1);
	if ($editable) {
		if (!isset($_POST['supplier_id']) && (get_global_supplier() != ALL_TEXT))
			$_POST['supplier_id'] = get_global_supplier();

		supplier_list_row(_("Supplier:"), 'supplier_id', null, false, true, false, true);
		stock_categories_list_row(_("Category"), "category", null, false, true);
	} else {
		hidden('supplier_id', $request->supplier_id);
		label_row(_("Supplier:"), $request->supplier_name);
		label_row(_("Category"), get_category_name($request->category_id));
		hidden('category', $request->category_id);
	}

	if ($request->supplier_id != get_post('supplier_id', -1)) {
		$old_supp = $request->supplier_id;
		get_supplier_details_to_order($request, $_POST['supplier_id']);
		// get_duedate_from_terms($request);
		$_POST['due_date'] = $request->due_date;

		// supplier default price update
		foreach ($request->line_items as $line_no => $item) {
			$line = &$request->line_items[$line_no];
			$line->price =  get_purchase_price($request->supplier_id, $line->stock_id);
			$line->quantity =
				$line->quantity / get_purchase_conversion_factor($old_supp, $line->stock_id)
				* get_purchase_conversion_factor($request->supplier_id, $line->stock_id);
		}
		$Ajax->activate('items_table');
		$Ajax->activate('due_date');
	}
	set_global_supplier($_POST['supplier_id']);

	// date_row($request->trans_type==ST_PURCHREQUEST ? _("Required Date:") :
	// 	($request->trans_type==ST_SUPPRECEIVE ? _("Delivery Date:") : _("Invoice Date:")),
	// 	'PRDate', '', true, 0, 0, 0, null, true);

	if (isset($_POST['_PRDate_changed'])) {
		$request->orig_order_date = $_POST['PRDate'];
		get_duedate_from_terms($request);
		$_POST['due_date'] = $request->due_date;
		$Ajax->activate('due_date');
	}

	if ($editable) {
		// label_row(_("PR #:"), $request->reference);
		ref_row(_("PR #:"), 'reference', '', null, false, $request->trans_type, array('date' => @$_POST['PRDate'], 'branchcode' => $branchcode));
	} else {
		hidden('reference', $request->reference);
		label_row(_("PR #:"), $request->reference);
	}
	$_POST['purch_type_id'] = $request->purch_type_id;
	purch_types_list_row(
		_("Purchase Type:"),
		'purch_type_id',
		null
	);

	table_section(2);
	date_row(_("PR Date:"), 'PRDate', '', true, 0, 0, 0, null, true);
	$_POST['served_status'] = $request->served_status;
	if ($editable) {
		echo "<tr><td class='label'>" . _("Served Status") . "</td><td><select id='served_status' name='served_status'>\n";
		echo "<option value='0'>Normal Served</option>";
		echo "<option value='1'>Overserved</option>";
		echo "<option value='2'>Wrong Served</option>";
		echo "</select>\n";
		echo "</td></tr>";
	} else {
		$served_status = "";
		switch ($request->served_status) {
			case 0:
				$served_status = "Normal Served";
				break;
			case 1:
				$served_status = "Overserved";
				break;
			case 2:
				$served_status = "Wrong Served";
				break;
			default:
				$served_status = "";
		}
		label_row(_("Served Status:"), $served_status);
		hidden('served_status', $request->served_status);
	}


	end_outer_table(); // outer table
}

function display_pr_items(&$request, $editable = true)
{
	display_heading(_("Request Items"));

	div_start('items_table');
	start_table(TABLESTYLE, "width='80%'");

	$new = $request->pr_no == 0;

	$th = array(_("Item Code"), _("Item Description"), _("Color Description - (Code)"), _("Quantity"), "");

	if (get_post("category") != 14)
		array_remove($th, 2);

	if (count($request->line_items)) $th[] = '';

	table_header($th);

	$id = find_submit('Edit');
	$total = 0;
	$k = 0;
	foreach ($request->line_items as $line_no => $pr_line) {
		$line_total =	round($pr_line->quantity * $pr_line->price,  user_price_dec());
		if (!$editable || ($id != $line_no)) {
			alt_table_row_color($k);
			label_cell($pr_line->stock_id);
			label_cell($pr_line->item_description);

			if (get_post("category") == 14)
				label_cell(get_color_description($pr_line->color_code, $pr_line->stock_id));

			if ($request->fixed_asset)
				label_cell(1);
			else
				qty_cell($pr_line->quantity, false, get_qty_dec($pr_line->stock_id));

			if ($editable) {
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
			}
			end_row();
		} else {
			pr_item_controls($request, $k, $line_no);
		}
		// $total += $line_total;
	}

	if ($id == -1 && $editable)
		pr_item_controls($request, $k);

	$colspan = count($th) - 2;
	if (count($request->line_items))
		$colspan--;


	end_table(1);
	div_end();
}

//--------------------------------------------------------------------------------

function pr_item_controls(&$request, &$rowcounter, $line_no = -1)
{
	global $Ajax, $SysPrefs;

	alt_table_row_color($rowcounter);
	$new = $request->pr_no == 0;

	$dec2 = 0;
	$id = find_submit('Edit');
	if (($id != -1) && $line_no == $id) {

		$_POST['stock_id'] = $request->line_items[$id]->stock_id;
		$dec = get_qty_dec($_POST['stock_id']);
		$_POST['qty'] = qty_format($request->line_items[$id]->quantity, $_POST['stock_id'], $dec);
		// $_POST['units'] = $request->line_items[$id]->units;
		$_POST['item_description'] = $request->line_items[$id]->item_description;

		hidden('stock_id', $_POST['stock_id']);
		label_cell($_POST['stock_id']);

		if ($request->line_items[$id]->descr_editable)
			text_cells(null, 'item_description', null, 45, 150);
		else {
			hidden('item_description', $_POST['item_description']);
			label_cell($request->line_items[$id]->item_description);
		}
		$_POST['color_code'] = $request->line_items[$id]->color_code;
		$Ajax->activate('items_table');
		$qty_rcvd = $request->line_items[$id]->qty_ordered;
	} else {

		// Manufactured item can be purchased
		if ($request->fixed_asset)
			stock_purchasable_fa_list_cells(null, 'stock_id', null, false, true, false, true, $request->line_items);
		else
			pr_stock_items_list_cells($_POST['supplier_id'], get_post("category"), null, 'stock_id', null, false, true, false, true, array('editable' => 30, 'where' => array("NOT no_purchase")));

		if (list_updated('stock_id')) {
			// $Ajax->activate('units');
			$Ajax->activate('qty');
		}
		$item_info = get_item_edit_info($_POST['stock_id']);
		// $_POST['units'] = $item_info["units"];

		$dec = $item_info["decimals"];
		$_POST['qty'] =	number_format2(get_purchase_conversion_factor($request->supplier_id, $_POST['stock_id']), $dec);
		$qty_rcvd = '';
	}

	if ($_POST['category'] == 14)
		color_list_cells($_POST['stock_id'], null, 'color_code');

	if ($request->fixed_asset) {
		label_cell(1, '', 'qty');
		hidden('qty', 1);
	} else
		qty_cells(null, 'qty', null, null, null, $dec);

	// if (!$new)
	// 	qty_cell($qty_rcvd, false, $dec);

	// label_cell($_POST['units'], '', 'units');
	// if ($qty_rcvd > 0)
	// {
	// 	amount_decimal_cell($_POST['price']);
	// 	hidden('price', $_POST['price']);
	// }	
	// else	
	// 	amount_cells(null, 'price', null, null, null, $dec2);

	// $line_total = round(input_num('qty') * input_num('price'),  user_price_dec());
	// amount_cell($line_total, false, '','line_total');

	if ($id != -1) {
		button_cell(
			'UpdateLine',
			_("Update"),
			_('Confirm changes'),
			ICON_UPDATE
		);
		button_cell(
			'CancelUpdate',
			_("Cancel"),
			_('Cancel changes'),
			ICON_CANCEL
		);
		hidden('line_no', $line_no);
		set_focus('qty');
	} else {
		submit_cells(
			'EnterLine',
			_("Add Item"),
			"colspan=2 align='center'",
			_('Add new item to document'),
			true
		);
	}

	end_row();
}


//---------------------------------------------------------------------------------------------------

function display_pr_summary(&$pr, $is_self = false, $editable = false)
{
	start_table(TABLESTYLE, "width='90%'");

	start_row();
	label_cells(_("PR No."), $pr->reference, "class='tableheader2'");
	label_cells(_("Supplier"), $pr->supplier_name, "class='tableheader2'");
	label_cells(_("Category"), get_category_name($pr->category_id), "class='tableheader2'");
	end_row();

	start_row();
	label_cells(_("Purchase Type"), $pr->purch_type, "class='tableheader2'");
	$served_status = "";
	switch ($pr->served_status) {
		case 0:
			$served_status = "Normal Served";
			break;
		case 1:
			$served_status = "Overserved";
			break;
		case 2:
			$served_status = "Wrong Served";
			break;
		default:
			$served_status = "";
	}
	label_cells(_("Served Status"), $served_status, "class='tableheader2'");
	label_cells(_("PR Status"), $pr->pr_status, "class='tableheader2'");
	end_row();

	start_row();
	label_cells(_("PR Date"), $pr->orig_order_date, "class='tableheader2'");
	label_cells(_("Remarks"), $pr->Comments, "class='tableheader2'");
	end_row();

	end_table(1);
}
