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
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/ui/items_cart.inc");
include_once($path_to_root . "/includes/cost_and_pricing.inc");

//--------------------------------------------------------------------------------

function add_to_order(&$order, $new_item, $new_item_qty, $standard_cost)
{
	$kit = get_item_kit($new_item);
	foreach($kit as $item) {
		if ($order->find_cart_item($item['stock_id']))
			display_error(_("For Part :") . $item['stock_id'] . " " . "This item is already on this document. You can change the quantity on the existing line if necessary.");
		else
			$order->add_to_cart (count($order->line_items), $item['stock_id'], $new_item_qty * $item['quantity'], $standard_cost);
	}
}

function add_to_order_new(&$order, $model, $new_item)
{
    //set_global_connection();
    $kit = get_item_serial_kit($new_item);
    
    foreach($kit as $item) {
        if ($order->find_cart_item_new($new_item,$item['serialise_lot_no'])){
            //display_error(_("For Part :") . $item['item_code'] . " " . "This item is already on this document. You can change the quantity on the existing line if necessary.");
        }else{
                $supplier_id = get_item_supplier_id($model);
            
                $standard_cost = Get_Policy_Cost($order->from_loc,$item['category_id'],$model,$supplier_id['supplier_id']);
                
                $order->add_to_cart(count($order->line_items), $model, $item['serialise_qty'],$standard_cost,$item['sdescription'],'0000-00-00','0000-00-00',$item['serialise_lot_no'],$item['serialise_chasis_no'],$item['idescription'],$item['item_code'],$item['mt_details_id']);
        }
                //$group_array[] = array('serialise_id'=>$item['item_code'],
                //    'color' => $item['serialise_lot_no']
                //);
    }
    
    //$jsonresult = json_encode($group_array);
    //echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
}
function add_to_mt_order(&$order, $model, $new_item, $serialised, $type_out=0, $transno_out=0, $item_type='new',$qty=1,$rr_date=null, $trans_date=null)
{
    //set_global_connection();
    $line_item_header = rand();
    if($serialised){
        $kit = get_MTitem_serial_kit($new_item);
        
        foreach($kit as $item) {
            if ($order->find_cart_item($model,$item['serialise_lot_no'])){
                //display_error(_("For Part :") . $item['item_code'] . " " . "This item is already on this document. You can change the quantity on the existing line if necessary.");
            }else{
                $supplier_id = get_item_supplier_id($model);
                //$standard_cost = Get_Policy_Cost($order->from_loc,$item['category_id'],$model, $supplier_id['supplier_id']);
                
                $standard_cost=Get_System_Cost($model, $type_out, $transno_out);
                $line_item = count($order->line_items);
                $order->add_to_cart($line_item, $model, $qty, $standard_cost, $item['sdescription'], $rr_date, '0000-00-00', $item['serialise_lot_no'], $item['serialise_chasis_no'], $item['idescription'], $item['item_code'], $item['mt_details_id'], $type_out, $transno_out,'', $item_type, $line_item_header);
                $order->add_gl_item($item['inventory_account'], '', '', -($standard_cost * $qty), $item['sdescription'].' '.$item['idescription'], '', '', $trans_date, null, null, 0,null,null, 99, $line_item_header);
            }
                
        }
    }else{
        $kit = get_item_kit($model);

        foreach($kit as $item) {
            if ($order->find_cart_itemcode($model, $type_out, $transno_out)){
            }else{
                $supplier_id = get_item_supplier_id($model);
                //$standard_cost = Get_Policy_Cost($order->from_loc,$order->category_id,$model, $supplier_id['supplier_id']);
                $standard_cost=Get_System_Cost($model, $type_out, $transno_out);
                $demand_qty = get_demand_qty($model, $order->from_loc);
                $demand_qty += get_demand_asm_qty($model, $order->from_loc);

                $qoaqty=get_qoh_on_date_new($type_out,$transno_out,$model,$order->from_loc);
                $qoaqty-=$demand_qty;
                $line_item = count($order->line_items);
                
                $order->add_to_cart($line_item, $model, $qty, $standard_cost, $item['sdescription'], $rr_date, '0000-00-00',null,null,$item['idescription'],$item['item_code'],0, $type_out, $transno_out, '',$item_type, $line_item_header);
                $order->add_gl_item($item['inventory_account'],'','', -($standard_cost * $qty), $item['sdescription'].' '.$item['idescription'], '', '', $trans_date, null, null, 0, 99, $line_item_header);
            }
        }
    }
    
    //$jsonresult = json_encode($group_array);
    //echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
}
//Added by Herald for MT order 11/27/2021 
function add_to_merchandise_transfer_order(&$order, $model, $new_item, $serialised, $type_out=0, $transno_out=0, $item_type='new',$qty=1,$rr_date=null, $trans_date=null)
{
    //set_global_connection();
    $line_item_header = rand();
    if($serialised){
        $kit = get_MTitem_serial_kit($new_item);
        
        foreach($kit as $item) {
            if ($order->find_cart_item($model,$item['serialise_lot_no'])){
                //display_error(_("For Part :") . $item['item_code'] . " " . "This item is already on this document. You can change the quantity on the existing line if necessary.");
            }else{
                $supplier_id = get_item_supplier_id($model);
                //$standard_cost = Get_Policy_Cost($order->from_loc,$item['category_id'],$model, $supplier_id['supplier_id']);
                
                $standard_cost=Get_System_Cost($model, $type_out, $transno_out);
                $line_item = count($order->line_items);
                $order->add_to_cart($line_item, $model, $qty, $standard_cost, $item['sdescription'], $rr_date, '0000-00-00', $item['serialise_lot_no'], $item['serialise_chasis_no'], $item['idescription'], $item['item_code'], $item['mt_details_id'], $type_out, $transno_out,'', $item_type, $line_item_header);
                //$order->add_gl_item($item['inventory_account'], '', '', -($standard_cost * $qty), $item['sdescription'].' '.$item['idescription'], '', '', $trans_date, null, null, 0,null,null, 99, $line_item_header);
            }
            
        }
    }else{
        $kit = get_item_kit($model);
        
        foreach($kit as $item) {
            if ($order->find_cart_itemcode($model, $type_out, $transno_out)){
            }else{
                $supplier_id = get_item_supplier_id($model);
                //$standard_cost = Get_Policy_Cost($order->from_loc,$order->category_id,$model, $supplier_id['supplier_id']);
                $standard_cost=Get_System_Cost($model, $type_out, $transno_out);
                $demand_qty = get_demand_qty($model, $order->from_loc);
                $demand_qty += get_demand_asm_qty($model, $order->from_loc);
                
                $qoaqty=get_qoh_on_date_new($type_out,$transno_out,$model,$order->from_loc);
                $qoaqty-=$demand_qty;
                $line_item = count($order->line_items);
                
                $order->add_to_cart($line_item, $model, $qty, $standard_cost, $item['sdescription'], $rr_date, '0000-00-00',null,null,$item['idescription'],$item['item_code'],0, $type_out, $transno_out, '',$item_type, $line_item_header);
                //$order->add_gl_item($item['inventory_account'],'','', -($standard_cost * $qty), $item['sdescription'].' '.$item['idescription'], '', '', $trans_date, null, null, 0, 99, $line_item_header);
            }
        }
    }
    
    //$jsonresult = json_encode($group_array);
    //echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
}
//--------------------------------------------------------------------------------
function add_to_rrbranch_order(&$order, $model, $item_code, $line_item_id)
{
    //set_global_connection();
    $kit = get_item_serial_mt($line_item_id);
    
    foreach($kit as $item) {
        if ($order->find_cart_item_new($item_code,$item['mt_details_serial_no'])){
            //display_error(_("For Part :") . $item['item_code'] . " " . "This item is already on this document. You can change the quantity on the existing line if necessary.");
        }else{
            $supplier_id = get_item_supplier_id($model);
            
            $standard_cost = Get_Policy_Cost($order->from_loc,$item['category_id'],$model, $supplier_id['supplier_id']);
            
            $order->add_to_cart(count($order->line_items), $model, $item['mt_details_total_qty'],$standard_cost,$item['sdescription'],'0000-00-00','0000-00-00',$item['mt_details_serial_no'],$item['mt_details_chasis_no'],$item['idescription'],$item['item_code']);
        }
            //$group_array[] = array('serialise_id'=>$item['item_code'],
            //    'color' => $item['serialise_lot_no']
            //);
    }
    
    //$jsonresult = json_encode($group_array);
    //echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
}
function display_order_header(&$order)
{
	global $Refs;

	start_outer_table(TABLESTYLE, "width='70%'");

	table_section(1);
	
	locations_list_row(_("From Location:"), 'FromStockLocation', null, false, false, $order->fixed_asset);
	locations_list_row(_("To Location:"), 'ToStockLocation', null,false, false, $order->fixed_asset);

	table_section(2, "50%");

    date_row(_("Date:"), 'AdjDate', '', true);

	ref_row(_("Reference:"), 'ref', '', $Refs->get_next(ST_LOCTRANSFER, null, array('date'=>get_post('AdjDate'), 'location'=> get_post('FromStockLocation'))), false, ST_LOCTRANSFER);

	end_outer_table(1); // outer table
}

//---------------------------------------------------------------------------------

function display_transfer_items($title, &$order)
{
	global $path_to_root;

	display_heading($title);
    div_start('items_table');
	start_table(TABLESTYLE, "width='80%'");
	$th = array(_("Item Code"), _("Item Description"), _("Quantity"), _("Unit"), '');
	if (!$order->fixed_asset && count($order->line_items)) $th[] = '';
	table_header($th);
	$k = 0;  //row colour counter

	$low_stock = $order->check_qoh($_POST['FromStockLocation'], $_POST['AdjDate'], true);
	$id = find_submit('Edit');
	foreach ($order->line_items as $line_no=>$stock_item)
	{

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
    		qty_cell($stock_item->quantity, false, get_qty_dec($stock_item->stock_id));
    		label_cell($stock_item->units);

	     	if (!$order->fixed_asset)
				edit_button_cell("Edit$line_no", _("Edit"),	_('Edit document line'));
			delete_button_cell("Delete$line_no", _("Delete"), _('Remove line from document'));
    		end_row();
		}
		else
			transfer_edit_item_controls($order, $line_no);
	}

	if ($id == -1)
		transfer_edit_item_controls($order);

    end_table();
	if ($low_stock)
		display_note(_("Marked items have insufficient quantities in stock as on day of transfer."), 0, 1, "class='stockmankofg'");
	div_end();
}

//---------------------------------------------------------------------------------
//Added by Herald [12-18-2020] for new MT Module
function display_transfer_items_serial_repo(&$order, $FromStockLocation, $AdjDate, $serialise_id='', $repo_id='')
{
   // global $path_to_root;

   // $low_stock = $order->check_qoh($FromStockLocation, $AdjDate, true);
    foreach ($order->line_items as $line_no=>$stock_item)
    {
        $kit = get_item_kit($stock_item->item_code);
        $myrow = db_fetch($kit);
        if($myrow["serialised"]){
           $qty=$stock_item->quantity;
        }else{
            $demand_qty = get_demand_qty($stock_item->stock_id, $FromStockLocation);
            $demand_qty += get_demand_asm_qty($stock_item->stock_id, $FromStockLocation);
            $qty=get_qoh_on_date_new($stock_item->type_out,$stock_item->transno_out,$stock_item->stock_id,$FromStockLocation);
            $qty-=$demand_qty;
        }
        if ($stock_item->lot_no == '') {
           $stock_quantity = $stock_item->quantity;
        } else {
            $stock_quantity = $stock_item->quantity;
        }
        $subtotal_cost = ($stock_item->standard_cost * $stock_quantity);
        if($stock_item->stock_id != ''){
            $group_array[] = array('id'=>$line_no,
                'stock_id'=>$stock_item->stock_id,
                'item_code'=>$stock_item->item_code,
                'color' => $stock_item->color,
                'stock_description' => $stock_item->item_description,
                'standard_cost' => $stock_item->standard_cost,
                'currentqty' => /*$qty  Modified by Me*/ $stock_item->quantity, 
                'qty' => $stock_quantity,
                'lot_no' => is_null($stock_item->lot_no)?'':$stock_item->lot_no,
                'chasis_no' => is_null($stock_item->chasis_no)?'':$stock_item->chasis_no,
                'mt_lines' => $stock_item->mt_lines,
                'remarks' => $stock_item->remarks,
                'serialise_loc_code'=>$FromStockLocation,
                'serialise_id'=>$serialise_id,
                'type_out' => $stock_item->type_out,
                'transno_out' => $stock_item->transno_out,
                'serialised' => $myrow["serialised"],
                'brand_name' => $myrow["brand_name"],
                'item_type' => $stock_item->item_type,
                'line_item' => $stock_item->line_item_row,
                'rr_date' => $stock_item->manufacture_date,
                'repo_id'=>$repo_id,
                'subtotal_cost'=>$subtotal_cost
            );
        }
    }
    $jsonresult = json_encode($group_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.',"AdjDate":"'.$AdjDate.'","branchcode":"'.$FromStockLocation.'"})';
}

function display_transfer_items_serial(&$order, $FromStockLocation, $AdjDate, $serialise_id='', $type_out=0, $transno_out=0)
{
   // global $path_to_root;

   // $low_stock = $order->check_qoh($FromStockLocation, $AdjDate, true);
    foreach ($order->line_items as $line_no=>$stock_item)
    {
        $kit = get_item_kit($stock_item->item_code);
        $myrow = db_fetch($kit);
        if($myrow["serialised"]){
           $qty=$stock_item->quantity;
        }else{
            $demand_qty = get_demand_qty($stock_item->stock_id, $FromStockLocation);
            $demand_qty += get_demand_asm_qty($stock_item->stock_id, $FromStockLocation);
            $qty=get_qoh_on_date_new($stock_item->type_out,$stock_item->transno_out,$stock_item->stock_id,$FromStockLocation);
            $qty-=$demand_qty;
        }
        if ($stock_item->lot_no == '') {
           $stock_quantity =  $stock_item->quantity;
        } else {
            $stock_quantity = $stock_item->quantity;
        }
        $subtotal_cost = ($stock_item->standard_cost * $stock_quantity);
        if($stock_item->stock_id != ''){
            $group_array[] = array('id'=>$line_no,
                'stock_id'=>$stock_item->stock_id,
                'item_code'=>$stock_item->item_code,
                'color' => $stock_item->color,
                'stock_description' => $stock_item->item_description,
                'standard_cost' => $stock_item->standard_cost,
                'currentqty' => $stock_item->currqty, 
                'qty' => $stock_quantity,
                'lot_no' => is_null($stock_item->lot_no)?'':$stock_item->lot_no,
                'chasis_no' => is_null($stock_item->chasis_no)?'':$stock_item->chasis_no,
                'mt_lines' => $stock_item->mt_lines,
                'remarks' => $stock_item->remarks,
                'serialise_loc_code'=>$FromStockLocation,
                'serialise_id'=>$serialise_id,
                'type_out' => $stock_item->type_out,
                'transno_out' => $stock_item->transno_out,
                'serialised' => $myrow["serialised"],
                'brand_name' => $myrow["brand_name"],
                'item_type' => $stock_item->item_type,
                'line_item' => $stock_item->line_item_row,
                'rr_date' => $stock_item->manufacture_date,
                'subtotal_cost'=>$subtotal_cost
            );
        }
    }
    $jsonresult = json_encode($group_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.',"AdjDate":"'.$AdjDate.'","branchcode":"'.$FromStockLocation.'"})';
}

//Added by Robert [08/31/2022] for new COMPLI Module
function display_transfer_items_serial_compli(&$order, $FromStockLocation, $AdjDate, $serialise_id='', $type_out=0, $transno_out=0)
{
    foreach ($order->line_items as $line_no=>$stock_item)
    {
        $kit = get_item_kit($stock_item->item_code);
        $myrow = db_fetch($kit);
        if($myrow["serialised"]){
           $qty=$stock_item->quantity;
        }else{
            $demand_qty = get_demand_qty($stock_item->stock_id, $FromStockLocation);
            $demand_qty += get_demand_asm_qty($stock_item->stock_id, $FromStockLocation);
            $qty=get_qoh_on_date_new($stock_item->type_out,$stock_item->transno_out,$stock_item->stock_id,$FromStockLocation);
            $qty-=$demand_qty;
        }
        if ($stock_item->lot_no == '') {
           $stock_quantity = 0;
        } else {
            $stock_quantity = $stock_item->quantity;
        }
        if($stock_item->stock_id != ''){
            $group_array[] = array('id'=>$line_no,
                'stock_id'=>$stock_item->stock_id,
                'item_code'=>$stock_item->item_code,
                'color' => $stock_item->color,
                'stock_description' => $stock_item->item_description,
                'standard_cost' => $stock_item->standard_cost,
                'currentqty' => /*$qty  Modified by Me*/ $stock_item->quantity, 
                'qty' => $stock_quantity,
                'lot_no' => is_null($stock_item->lot_no)?'':$stock_item->lot_no,
                'chasis_no' => is_null($stock_item->chasis_no)?'':$stock_item->chasis_no,
                'mt_lines' => $stock_item->mt_lines,
                'remarks' => $stock_item->remarks,
                'serialise_loc_code'=>$FromStockLocation,
                'serialise_id'=>$serialise_id,
                'type_out' => $stock_item->type_out,
                'transno_out' => $stock_item->transno_out,
                'serialised' => $myrow["serialised"],
                'brand_name' => $myrow["brand_name"],
                'item_type' => $stock_item->item_type,
                'line_item' => $stock_item->line_item_row,
                'rr_date' => $stock_item->manufacture_date
            );
        }
    }
    $jsonresult = json_encode($group_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.',"AdjDate":"'.$AdjDate.'","branchcode":"'.$FromStockLocation.'"})';
}

//Added by Robert [09/29/2022] for new COMPLI Module
function display_transfer_items_serial_compli_repo(&$order, $FromStockLocation, $AdjDate, $serialise_id='', $type_out=0, $transno_out=0)
{
    foreach ($order->line_items as $line_no=>$stock_item)
    {
        $kit = get_item_kit($stock_item->item_code);
        $myrow = db_fetch($kit);
        if($myrow["serialised"]){
           $qty=$stock_item->quantity;
        }else{
            $demand_qty = get_demand_qty($stock_item->stock_id, $FromStockLocation);
            $demand_qty += get_demand_asm_qty($stock_item->stock_id, $FromStockLocation);
            $qty=get_qoh_on_date_new($stock_item->type_out,$stock_item->transno_out,$stock_item->stock_id,$FromStockLocation);
            $qty-=$demand_qty;
        }
        if ($stock_item->lot_no == '') {
           $stock_quantity = 0;
        } else {
            $stock_quantity = $stock_item->quantity;
        }
        if($stock_item->stock_id != ''){
            $group_array[] = array('id'=>$line_no,
                'stock_id'=>$stock_item->stock_id,
                'item_code'=>$stock_item->item_code,
                'color' => $stock_item->color,
                'stock_description' => $stock_item->item_description,
                'standard_cost' => $stock_item->standard_cost,
                'currentqty' => /*$qty  Modified by Me*/ $stock_item->quantity, 
                'qty' => $stock_quantity,
                'lot_no' => is_null($stock_item->lot_no)?'':$stock_item->lot_no,
                'chasis_no' => is_null($stock_item->chasis_no)?'':$stock_item->chasis_no,
                'mt_lines' => $stock_item->mt_lines,
                'remarks' => $stock_item->remarks,
                'serialise_loc_code'=>$FromStockLocation,
                'serialise_id'=>$serialise_id,
                'type_out' => $stock_item->type_out,
                'transno_out' => $stock_item->transno_out,
                'serialised' => $myrow["serialised"],
                'brand_name' => $myrow["brand_name"],
                'item_type' => $stock_item->item_type,
                'line_item' => $stock_item->line_item_row,
                'rr_date' => $stock_item->manufacture_date
            );
        }
    }
    $jsonresult = json_encode($group_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.',"AdjDate":"'.$AdjDate.'","branchcode":"'.$FromStockLocation.'"})';
}

//Added by Robert [08/24/2022] for new MT Module
function display_transfer_items_serial_for_rr(&$order, $FromStockLocation, $AdjDate, $serialise_id='', $type_out=0, $transno_out=0)
{
    foreach ($order->line_items as $line_no=>$stock_item)
    {
        $kit = get_item_kit($stock_item->item_code);
        $myrow = db_fetch($kit);
        if($myrow["serialised"]){
           $qty=$stock_item->quantity;
        }else{
            $demand_qty = get_demand_qty($stock_item->stock_id, $FromStockLocation);
            $demand_qty += get_demand_asm_qty($stock_item->stock_id, $FromStockLocation);
            $qty=get_qoh_on_date_new($stock_item->type_out,$stock_item->transno_out,$stock_item->stock_id,$FromStockLocation);
            $qty-=$demand_qty;
        }
        if ($myrow["serialised"]) {
            $stock_quantity = $stock_item->quantity;
        } else {            
            $stock_quantity = $stock_item->quantity;
        }
        $subtotal_cost = ($stock_item->standard_cost * $stock_quantity);
        if($stock_item->stock_id != ''){
            $group_array[] = array('id'=>$line_no,
                'stock_id'=>$stock_item->stock_id,
                'item_code'=>$stock_item->item_code,
                'color' => $stock_item->color,
                'stock_description' => $stock_item->item_description,
                'standard_cost' => $stock_item->standard_cost,
                'currentqty' => /*$qty  Modified by Me*/ $stock_item->quantity, 
                'qty' => $stock_quantity,
                'lot_no' => is_null($stock_item->lot_no)?'':$stock_item->lot_no,
                'chasis_no' => is_null($stock_item->chasis_no)?'':$stock_item->chasis_no,
                'mt_lines' => $stock_item->mt_lines,
                'remarks' => $stock_item->remarks,
                'serialise_loc_code'=>$FromStockLocation,
                'serialise_id'=>$serialise_id,
                'type_out' => $stock_item->type_out,
                'transno_out' => $stock_item->transno_out,
                'serialised' => $myrow["serialised"],
                'brand_name' => $myrow["brand_name"],
                'item_type' => $stock_item->item_type,
                'line_item' => $stock_item->line_item_row,
                'rr_date' => $stock_item->manufacture_date,
                'subtotal_cost'=>$subtotal_cost
            );
        }
    }
    $jsonresult = json_encode($group_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.',"AdjDate":"'.$AdjDate.'","branchcode":"'.$FromStockLocation.'"})';
}

function transfer_edit_item_controls(&$order, $line_no=-1)
{
	global $Ajax;
	start_row();

	$id = find_submit('Edit');
	if ($line_no != -1 && $line_no == $id)
	{
		$_POST['stock_id'] = $order->line_items[$id]->stock_id;
		$_POST['qty'] = qty_format($order->line_items[$id]->quantity, $order->line_items[$id]->stock_id, $dec);
		$_POST['units'] = $order->line_items[$id]->units;

		hidden('stock_id', $_POST['stock_id']);
		label_cell($_POST['stock_id']);
		label_cell($order->line_items[$id]->item_description);
	    $Ajax->activate('items_table');
	}
	else
	{
		if ($order->fixed_asset)
			stock_disposable_fa_list_cells(null, 'stock_id', null, false, true, $order->line_items);
		else
			sales_items_list_cells(null,'stock_id', null, false, true, true);

		if (list_updated('stock_id')) {
			$Ajax->activate('units');
			$Ajax->activate('qty');
		}

    	$item_info = get_item_edit_info($_POST['stock_id']);

		$dec = $item_info['decimals'];
   		$_POST['qty'] = number_format2(0, $dec);
		$_POST['units'] = $item_info["units"];
	}

	if ($order->fixed_asset) {
		hidden('qty', 1);
		qty_cell(1, false, 0);
	} else
		small_qty_cells(null, 'qty', $_POST['qty'], null, null, $dec);

	label_cell($_POST['units'], '', 'units');

	if ($id != -1)
	{
		button_cell('UpdateItem', _("Update"), _('Confirm changes'), ICON_UPDATE);
		button_cell('CancelItemChanges', _("Cancel"), _('Cancel changes'), ICON_CANCEL);
		hidden('LineNo', $line_no);
 		set_focus('qty');
	}
	else
		submit_cells('AddItem', _("Add Item"), "colspan=2", _('Add new item to document'), true);

	end_row();
}


//---------------------------------------------------------------------------------

function transfer_options_controls()
{
    echo "<br>";
    start_table();

	textarea_row(_("Memo"), 'memo_', null, 50, 3);

	end_table(1);
}


//---------------------------------------------------------------------------------
