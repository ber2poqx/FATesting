<?php

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/ui/items_cart.inc");

//--------------------------------------------------------------------------------

function add_to_order(&$order, $new_item, $new_item_qty, $standard_cost, $manufacture_date = '', $expire_date = '', 
	$lot_no = '', $chasis_no = '', $color = '', $stock_ref = '', $mcode = '', $masterfile = '', $ob_date = null)
{
	if (is_Serialized($new_item) == 1 && $order->find_cart_serial($lot_no)) {
		display_error(_("For Part: ") . $lot_no . " || " . "Serial / Engine # already existed!");
	}
	else if (is_Serialized($new_item) == 1 && get_stock_catID($new_item) == 14 && 
		$order->find_cart_chassis($chasis_no)) {
		display_error(_("For Part: ") . $chasis_no . " || " . "Chassis # already existed!");
	}
	else if (is_Serialized($new_item) == 0 && $order->find_cart_stock($new_item) && $ob_date == null) {
		display_error(_("For Part: ") . $new_item . " || " . "Stock Already Exist!");
	}
	else {
		$order->add_to_cart(
			count($order->line_items), 
			$new_item, 
			$new_item_qty, 
			$standard_cost, null, 
			$manufacture_date, 
			$expire_date, 
			$lot_no, 
			$chasis_no,
			$color,
			'', 0, 0, 0, '', 'new', 
			$stock_ref,
			$mcode, $masterfile, '', $ob_date
		);
	}
}

//--------------------------------------------------------------------------------

function display_order_header(&$order, $id = 0, $line_items = 0)
{
	global  $Ajax, $Refs;

	div_start('item_head');
	start_outer_table(TABLESTYLE2, "width='80%'");
	table_section(1);

	sql_type_list(_("Location: "), 'StockLocation', 
		get_location_list(), 'loc_code', 'location_name', 
		'label', null, true
	);
	
	if ($id == 0) {
		date_row(_("Date: "), 'AdjDate', '', true, 0, 0, 0, null, true);
	}
	else {
		date_row(_("Date: "), 'AdjDate', '', true);
	} 
	/*Added by Albert 5/13/2023*/
	adjustment_list_row(_("Adjustment Types:"), "adjustment_id", null, _("All Types"),true);

	if(get_post('adjustment_id') == 1){
		$Ajax->activate('item_head');
		$_POST['rr_ref'] = '';
		foreach ($order->line_items as $line_no=>$stock_item)
		{
			$_POST['rr_ref'] = $stock_item->stock_ref;	
		}
		label_row(_("RR Reference #: &nbsp;"), get_post('rr_ref'));
		hidden('rr_ref', get_post('rr_ref'));
	}else{
		$Ajax->activate('item_head');
	}
	/**/
	
	table_section(2, "50%");
	$_POST['ref'] = $order->reference;
	label_row(_("Reference #: &nbsp;"), get_post('ref'));
	hidden('ref');
	
	sql_type_list(_("Category: "), 'category', 
		get_category_list(), 'category_id', 'description', 
		'label', null, true, '', false, true
	);

	if ($id == 1) {
		numeric_type_list(_("Adjustment Status: "), 'adj_type', 
			array(
				_('Inventory In'),
				_('Inventory Out')
			), null, true, '', 'label', false, false, true
		);
	}
	end_outer_table(1);

	div_end();
}

//---------------------------------------------------------------------------------

function display_adjustment_items($title, &$order, $adj_id = 0, $trans_no, $item_type, $ob = 0)
{
	global $path_to_root;

	display_heading($title);	
    div_start('items_table2');
	start_table(TABLESTYLE, "width='98%'");
	
	if (get_post("category") == 14) {
		if ($order->fixed_asset) {
			$th = array(_("Item Code"), _("Item Description"), _("Color Description - (Code)"), _("Quantity"),
			_("Unit"), _("Unit Cost"), _("Initial Value"), "");
		} else {
			$th = array(_("Item Code"), _("Item Description"), _("Color Description - (Code)"),  _("Quantity"),
			_("Unit"), _("Serial/Engine No."), _("Chasis No."), _("Unit Cost"), _("Total"), "");
			if ( count($order->line_items)) $th[] = ''; $th[] = '';
		}	
	}
	else if (get_post("category") == 15 || get_post("category") == 16 || 
	get_post("category") == 21 || get_post("category") == 19) {
		if ($order->fixed_asset) {
			$th = array(_("Item Code"), _("Item Description"), _("Quantity"),
			_("Unit"), _("Unit Cost"), _("Initial Value"), "");
		} else {
			$th = array(_("Item Code"), _("Item Description"), _("Quantity"),
			_("Unit"), _("Serial/Engine No."), _("Unit Cost"), _("Total"), "");
			if ( count($order->line_items)) $th[] = ''; $th[] = '';
		}
	}
	else {
		if ($order->fixed_asset) {
			$th = array(_("Item Code"), _("Item Description"), _("Quantity"),
			_("Unit"), _("Unit Cost"), _("Initial Value"), "");
		} else {
			$th = array(_("Item Code"), _("Item Description"), _("Quantity"),
			_("Unit"),  _("Unit Cost"), _("Total"), "");
			if ( count($order->line_items)) $th[] = ''; $th[] = '';
		}
	}
	
	table_header($th);
	$total = 0;
	$k = 0;  //row colour counter

	$low_stock = $order->check_qoh($_POST['StockLocation'], $_POST['AdjDate']);
	$id = get_post('selected_id', find_submit('Edit'));
	foreach ($order->line_items as $line_no=>$stock_item)
	{

		$total += ($stock_item->standard_cost * $stock_item->quantity);

		if ($id != $line_no)
		{
			if (in_array($stock_item->stock_id, $low_stock))
				start_row("class='stockmankobg'");	// notice low stock status
			else 
				alt_table_row_color($k);

    		if ($order->fixed_asset)
				label_cell($stock_item->stock_id);
      		else
				view_stock_status_cell($stock_item->stock_id);

			label_cell($stock_item->item_description);
			if (get_post("category") == 14) {
				label_cell(get_color_description($stock_item->color, $stock_item->stock_id));
			}

    		qty_cell($stock_item->quantity, false, 0);
			label_cell($stock_item->units);

			
			//label_cell($stock_item->manufacture_date, "nowrap align=center");
			//label_cell($stock_item->expire_date, "nowrap align=center");
			
			if ($_POST['category'] == 14 || $_POST['category'] == 15 || get_post("category") == 16 || 
				get_post("category") == 21 || get_post("category") == 19) {
				label_cell($stock_item->lot_no, "nowrap align=left");
			}
			
			if ($_POST['category'] == 14) {
				label_cell($stock_item->chasis_no, "nowrap align=left");
			}
				
			amount_decimal_cell($stock_item->standard_cost);

			if ($order->fixed_asset) {
    	   		$price = get_purchase_value($stock_item->stock_id);
				amount_cell($price);
      		} else
				amount_cell($stock_item->standard_cost * $stock_item->quantity);

			$val = get_adjGL_id($trans_no, $stock_item->stock_id, $stock_item->lot_no);
		
      		if (!$order->fixed_asset) {
      			if ($id == -1 ){
					edit_button_cell("Edit".$line_no, _("Edit"), _('Edit document line'));
				}	
	        	else {
					label_cell('');
				}
      		}
    		if ($id == -1) {
				delete_button_cell("Delete".$line_no, _("Delete"), _('Remove line from document'));
			}
        	else {
				label_cell('');
			}
			end_row();
		}
		else
		{
			adjustment_edit_item_controls($order, $line_no, $item_type, $ob);
		}
	}

	if ($id == -1)
		adjustment_edit_item_controls($order, -1, $item_type, $ob);

	$col = get_post('adj_type') == 1 ? count($th) - 3 : count($th) - 4;

	label_row(_("Document Total: "), number_format2($total, user_price_dec()), 
		"align=right colspan='$col'; style='font-weight:bold';", 
		"style='font-weight:bold'; align=right", 0
	);

    end_table();

	if ($id != -1)
	    hidden('selected_id', $id);
	if ($low_stock)
		display_note(_("Marked items have insufficient quantities in stock as on day of adjustment."), 0, 1, "class='stockmankofg'");
	div_end();
}

//---------------------------------------------------------------------------------

function adjustment_edit_item_controls(&$order, $line_no=-1, $item_type, $ob = 0)
{
	global $Ajax;
	start_row();

	$dec2 = 0;
	$id = find_submit('Edit');
	if ($line_no != -1)
	{
		if (find_submit('Edit') != -1) {
			$_POST['stock_id'] = $order->line_items[$line_no]->stock_id;
			$_POST['qty'] = qty_format($order->line_items[$line_no]->quantity, 
				$order->line_items[$line_no]->stock_id, $dec);
		//$_POST['std_cost'] = price_format($order->line_items[$line_no]->standard_cost);
			$_POST['std_cost'] = price_decimal_format($order->line_items[$line_no]->standard_cost, $dec2);
		}
		$_POST['units'] = $order->line_items[$line_no]->units;
		$_POST['manufacture_date'] =  $order->line_items[$line_no]->manufacture_date;
		$_POST['expire_date'] =  $order->line_items[$line_no]->expire_date;
		
		hidden('stock_id', $_POST['stock_id']);
		label_cell($_POST['stock_id']);
		label_cell($order->line_items[$line_no]->item_description, 'nowrap');
		if ($_POST['category'] == 14) {
			$_POST['color'] = $order->line_items[$line_no]->color;
		}

		if ($_POST['category'] == 14 || $_POST['category'] == 15 || get_post("category") == 16 || 
			get_post("category") == 21 || get_post("category") == 19) {
			$_POST['lot_no'] =  $order->line_items[$line_no]->lot_no;
		}	

		if ($_POST['category'] == 14) {
			$_POST['chasis_no'] =  $order->line_items[$line_no]->chasis_no;
		}

		$_POST['stock_ref'] = $order->line_items[$line_no]->stock_ref;
	
	    $Ajax->activate('items_table2');
	}
	else
	{
		if ($order->fixed_asset) {
			stock_disposable_fa_list_cells(null, 'stock_id', null, false, true, $order->line_items);
		}	
		else {
			
			if (get_post("adj_type") == 2) {
				inty_list_cells(null, 'stock_id', null, $_POST['category'], false, true, true, 2, $_POST['StockLocation'], 
					null, $item_type
				);
			}
			else {
				if ($ob == 1) {
					inty_list_cells(null, 'stock_id', null, $_POST['category'], false, true, true, 1, $_POST['StockLocation'], 
						$_POST['supplier_id'], $item_type
					);
					/*pr_stock_items_list_cells($_POST['supplier_id'], get_post("category"), null, 'stock_id', null, false, true, false, true, 
						array('editable' => 30, 'where' => array("NOT no_purchase"))
					);*/
					

				}
				else {
					stock_items_list_cells(null, 'stock_id', null, false, true, null, false, $_POST['category'], 
	  					array('fixed_asset' => get_post('fixed_asset'))
					);
				}
			}
			
		}
			
		if (list_updated('stock_id')) {

			$_POST['qty'] = 1; 
			if (is_Serialized($_POST['stock_id']) == 1) {
				set_focus("lot_no");
			}
			else {
				set_focus("qty");
			}

			if (get_post("adj_type") == 1) {
				$_POST['lot_no'] = '';
				$_POST['chasis_no'] = '';
				$_POST['std_cost'] = 0;

				$Ajax->activate('std_cost');
				$Ajax->activate('lot_no');
				$Ajax->activate('chasis_no');
			}

			$Ajax->activate('items_table2');
		}

		$_POST['manufacture_date']='';
		$_POST['expire_date']='';
		$_POST['units']=''; 	
	}

	$dec = 0;

	if ($_POST['category'] == 14)
		if (get_post('adj_type') == 2) {
			hidden('color');
			label_cell(get_post('color'));
		}
		else {
			sql_type_list(null, 'color', 
				get_color_list($_POST['stock_id']), 'item_code', 'ColorDesc', 
				'label', null, true, _("Select Color Code")
			);
		}		
	else {
		hidden('color');
	} 

	if ($order->fixed_asset) {
		hidden('qty', -1);
		qty_cell(1, false, 0);
	} 
	else if (is_Serialized(get_post('stock_id')) == 1) {
		label_cell(get_post('qty'), "align = 'center'");
		hidden('qty');
	}
	else {
		label_cell( text_input('qty', get_post('qty', '0'), 15, 15, '',  'class="amount active" dec='.$dec), "nowrap align=right");
	}
		
	label_cell(get_post('units'), '', 'units');

	if ($_POST['category'] == 14 || $_POST['category'] == 15 || get_post("category") == 16 || 
		get_post("category") == 21 || get_post("category") == 19) {
		if (get_post('adj_type') == 2 || $id != -1) {
			hidden('lot_no');
			label_cell(get_post('lot_no'));
		}
		else {
			text_cells(null, "lot_no", null, 40, 50, false, "", "", 'placeholder="Serial No."');
		}	
	}
	else {
		hidden('lot_no');
	}

	hidden('manufacture_date');
	hidden('expire_date');
	
	if ($_POST['category'] == 14) {
		if (get_post('adj_type') == 2 || $id != -1) {
			hidden('chasis_no');
			label_cell(get_post('chasis_no'));
		}
		else {
			text_cells(null, "chasis_no", null, 40, 50, false, "", "", 'placeholder="Chassis No."');
		}	
	}
	else {
		hidden('chasis_no');
	}

	hidden('stock_ref');
	
	if (get_post("adj_type") == 2) {
		hidden('std_cost');
		label_cell(get_post('std_cost'), "nowrap align=right");
	}
	else {		
		if ($order->fixed_asset) {
			hidden('std_cost');
			label_cell($_POST['std_cost'], "nowrap align=right ", 'std_cost_view');
			$price = get_purchase_value($_POST['stock_id']);
			amount_cell($price, false, '', 'initial_price');
		} else {
			if (get_post('_qty_changed')) {
				$Ajax->activate('items_table2');		
				set_focus(input_num('qty') >= 0 ? 'std_cost' : 'AddItem');
			}
			if (input_num('qty') >= 0){
				unit_amount_cells(null, 'std_cost', null, null, null, $dec);
			}else {
				hidden('std_cost');
				label_cell($_POST['std_cost'], "nowrap align=right");
			}
			label_cell("&nbsp;");
		}
	
	}

	if ($line_no != -1)
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
		submit_cells('AddItem', _("Add Item"), "colspan=2",
		    _('Add new item to document'), true);
	}

	end_row();
}


//---------------------------------------------------------------------------------

function adjustment_options_controls() {
	echo "<br>";
	start_table();

	textarea_row(_("Memo : &nbsp;"), 'memo_', null, 50, 3);

	end_table(1);
}

//---------------------------------------------------------------------------------

