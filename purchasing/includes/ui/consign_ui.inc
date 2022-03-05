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

function consign_copy_from_cart()
{
	$cart = &$_SESSION['RCON'];

	$_POST['supplier_id'] = $cart->supplier_id;
	$_POST['ConsignDate'] = $cart->orig_order_date;
	$_POST['due_date'] = $cart->due_date;
	// $_POST['supp_ref'] = $cart->supp_ref;
	$_POST['StkLocation'] = $cart->Location;
	$_POST['delivery_address'] = $cart->delivery_address;
	$_POST['reference'] = $cart->reference;
	$_POST['Comments'] = $cart->Comments;
	$_POST['category'] = $cart->category_id;
	$_POST['suppl_ref_no'] = $cart->suppl_ref_no;
}

function consign_copy_to_cart()
{
	$cart = &$_SESSION['RCON'];
	$cart->orig_order_date = $_POST['ConsignDate'];
	$cart->supplier_id = $_POST['supplier_id'];
	$cart->due_date = $_POST['due_date'];
	$cart->reference = $_POST['reference'];
	// $cart->supp_ref = $_POST['supp_ref'];
	$cart->Comments = $_POST['Comments'];
	$cart->category_id = $_POST['category'];
	$cart->suppl_ref_no = $_POST['suppl_ref_no'];
}

//---------------------------------------------------------------------------------------------------

function create_new_consign($trans_type, $trans_no, $serial_id = 0)
{
    global $Refs, $db_connections;
	$branchcode = $db_connections[user_company()]["branch_code"];
	if (isset($_SESSION['RCON']))
		unset($_SESSION['RCON']->line_items, $_SESSION['RCON']);

	$cart = new receive_consignment;
	$_POST['ConsignDate'] = new_doc_date();

	if (!is_date_in_fiscalyear($_POST['ConsignDate']))
		$_POST['ConsignDate'] = end_fiscalyear();

	$cart->due_date = $cart->orig_order_date = $_POST['ConsignDate'];

	$cart->trans_type = $trans_type;
	$cart->reference = $trans_no;
	/*read in all the selected order into the Items cart  */
	if ($trans_no) {
		read_consignment($trans_no, $cart, $serial_id);
		$cart->reference = $trans_no;
	} else
		$cart->reference = $Refs->get_next(
			$trans_type,
			null,
		    array('supplier_id' => $cart->supplier_id, 'date' => get_post('ConsignDate'), 'branchcode'=>$branchcode));
	$_SESSION['RCON'] = &$cart;
}

//---------------------------------------------------------------------------------------------------

function display_consign_header(&$consignment)
{
    global $Ajax, $Refs, $db_connections;
	$branchcode = $db_connections[user_company()]["branch_code"];
	
	$editable = ($consignment->consign_no == 0);

	start_outer_table(TABLESTYLE2, "width='80%'");

	table_section(1);
	if ($editable) {
		if (!isset($_POST['supplier_id']) && (get_global_supplier() != ALL_TEXT))
			$_POST['supplier_id'] = get_global_supplier();

		supplier_list_row(_("Supplier:"), 'supplier_id', null, false, true, false, true);
		stock_categories_list_row(_("Category"), "category", null, false, true);
	} else {
		hidden('supplier_id', $consignment->supplier_id);
		label_row(_("Supplier:"), $consignment->supplier_name);
		label_row(_("Category"), get_category_name($consignment->category_id));
		hidden('category', $consignment->category_id);
	}

	if ($consignment->supplier_id != get_post('supplier_id', -1)) {
		$old_supp = $consignment->supplier_id;
		get_supplier_details_to_order($consignment, $_POST['supplier_id']);
		// get_duedate_from_terms($consignment);
		$_POST['due_date'] = $consignment->due_date;

		// supplier default price update
		foreach ($consignment->line_items as $line_no => $item) {
			$line = &$consignment->line_items[$line_no];
			$line->price =  get_purchase_price($consignment->supplier_id, $line->stock_id);
			$line->quantity =
				$line->quantity / get_purchase_conversion_factor($old_supp, $line->stock_id)
				* get_purchase_conversion_factor($consignment->supplier_id, $line->stock_id);
		}
		$Ajax->activate('items_table');
		$Ajax->activate('due_date');
	}
	set_global_supplier($_POST['supplier_id']);

	// date_row($consignment->trans_type==ST_PURCHREQUEST ? _("Required Date:") :
	// 	($consignment->trans_type==ST_SUPPRECEIVE ? _("Delivery Date:") : _("Invoice Date:")),
	// 	'ConsignDate', '', true, 0, 0, 0, null, true);

	if (isset($_POST['_ConsignDate_changed'])) {
		$consignment->orig_order_date = $_POST['ConsignDate'];
		get_duedate_from_terms($consignment);
		$_POST['due_date'] = $consignment->due_date;
		$Ajax->activate('due_date');
	}

	if ($editable) {
		// label_row(_("PR #:"), $consignment->reference);
	    ref_row(_("Consignment #:"), 'reference', '', null, false, $consignment->trans_type, array('date' => @$_POST['ConsignDate'],'branchcode'=>$branchcode));
	} else {
		hidden('reference', $consignment->reference);
		label_row(_("Consignment #:"), $consignment->reference);
	}

	ref_row(_("Supplier's Ref. #"), 'suppl_ref_no', '', null);

	table_section(2);
	date_row(_("Consignment Date:"), 'ConsignDate', '', true, 0, 0, 0, null, true);

	end_outer_table(); // outer table
}

function display_consign_items(&$consignment, $editable = true)
{
	display_heading(_("Consignment Items"));

	div_start('items_table');
	start_table(TABLESTYLE, "width='80%'");

	$new = $consignment->consign_no == 0;

	$th = array(_("Item Code"), _("Item Description"), _("Color Description - (Code)"), _("Quantity"), "");

	if (get_post("category") != 14)
		array_remove($th, 2);

	if (count($consignment->line_items)) $th[] = '';

	table_header($th);

	$id = find_submit('Edit');
	$total = 0;
	$k = 0;
	foreach ($consignment->line_items as $line_no => $consignment_line) {
		$line_total =	round($consignment_line->quantity * $consignment_line->price,  user_price_dec());
		if (!$editable || ($id != $line_no)) {
			alt_table_row_color($k);
			label_cell($consignment_line->stock_id);
			label_cell($consignment_line->item_description);

			if (get_post("category") == 14)
				label_cell(get_color_description($consignment_line->color_code, $consignment_line->stock_id));

			if ($consignment->fixed_asset)
				label_cell(1);
			else
				qty_cell($consignment_line->quantity, false, get_qty_dec($consignment_line->stock_id));

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
			consign_item_controls($consignment, $k, $line_no);
		}
		// $total += $line_total;
	}

	if ($id == -1 && $editable)
		consign_item_controls($consignment, $k);

	$colspan = count($th) - 2;
	if (count($consignment->line_items))
		$colspan--;


	end_table(1);
	div_end();
}

//--------------------------------------------------------------------------------

function consign_item_controls(&$consignment, &$rowcounter, $line_no = -1)
{
	global $Ajax, $SysPrefs;

	alt_table_row_color($rowcounter);
	$new = $consignment->consign_no == 0;

	$dec2 = 0;
	$id = find_submit('Edit');
	if (($id != -1) && $line_no == $id) {

		$_POST['stock_id'] = $consignment->line_items[$id]->stock_id;
		$dec = get_qty_dec($_POST['stock_id']);
		$_POST['qty'] = qty_format($consignment->line_items[$id]->quantity, $_POST['stock_id'], $dec);
		$_POST['item_description'] = $consignment->line_items[$id]->item_description;

		hidden('stock_id', $_POST['stock_id']);
		label_cell($_POST['stock_id']);

		if ($consignment->line_items[$id]->descr_editable)
			text_cells(null, 'item_description', null, 45, 150);
		else {
			hidden('item_description', $_POST['item_description']);
			label_cell($consignment->line_items[$id]->item_description);
		}
		$_POST['color_code'] = $consignment->line_items[$id]->color_code;
		$Ajax->activate('items_table');
		$qty_rcvd = $consignment->line_items[$id]->qty_ordered;
	} else {

		// Manufactured item can be purchased
		if ($consignment->fixed_asset)
			stock_purchasable_fa_list_cells(null, 'stock_id', null, false, true, false, true, $consignment->line_items);
		else
			pr_stock_items_list_cells($_POST['supplier_id'], get_post("category"), null, 'stock_id', null, false, true, false, true, array('editable' => 30, 'where' => array("NOT no_purchase")));

		if (list_updated('stock_id')) {
			// $Ajax->activate('units');
			$Ajax->activate('qty');
		}
		$item_info = get_item_edit_info($_POST['stock_id']);
		// $_POST['units'] = $item_info["units"];

		$dec = $item_info["decimals"];
		$_POST['qty'] =	number_format2(get_purchase_conversion_factor($consignment->supplier_id, $_POST['stock_id']), $dec);
		$qty_rcvd = '';
	}

	if ($_POST['category'] == 14)
		color_list_cells($_POST['stock_id'], null, 'color_code');

	if ($consignment->fixed_asset) {
		label_cell(1, '', 'qty');
		hidden('qty', 1);
	} else
		qty_cells(null, 'qty', null, null, null, $dec);

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

function display_consign_summary(&$consign, $is_self = false, $editable = false)
{
	start_table(TABLESTYLE, "width='90%'");

	start_row();
	label_cells(_("Consignment #"), $consign->reference, "class='tableheader2'");

	label_cells(_("Supplier"), $consign->supplier_name, "class='tableheader2'");
	label_cells(_("Category"), get_category_name($consign->category_id), "class='tableheader2'");

	end_row();
	start_row();
	label_cells(_("Supplier Ref #"), $consign->suppl_ref_no, "class='tableheader2'");
	label_cells(_("Consignment Date"), $consign->orig_order_date, "class='tableheader2'");
	label_cells(_("Remarks"), $consign->Comments, "class='tableheader2'");


	// if (!$editable)
	// 	label_row(
	// 		_("Required Date"),
	// 		$consign->due_date,
	// 		"class='tableheader2'",
	// 		"colspan=9"
	// 	);
	end_row();
	end_table(1);
}
