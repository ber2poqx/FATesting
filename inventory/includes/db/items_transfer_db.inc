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
//-------------------------------------------------------------------------------------------------------------
function add_stock_merchandise_transfer($Items, $location_from, $location_to, $date_, $reference, $memo_,$catcode=0, $rsdno=null, $servedby=null)
{
    global $SysPrefs, $path_to_root, $Refs, $db_connections;
    $company_prefs = get_company_prefs();
    
    begin_transaction();
    $args = func_get_args();
    $args = (object)array_combine(array('Items', 'location_from', 'location_to','date_', 'reference', 'memo_','catcode','rsdno','servedby'), $args);
    $args->trans_no = 0;
    hook_db_prewrite($args, ST_MERCHANDISETRANSFER);
    
    $transfer_id = get_next_trans_no(ST_MERCHANDISETRANSFER);
    $mcode = $_POST['ToStockLocation'];
    $master_file = get_db_location_name($_POST['ToStockLocation']);
    
    if ($SysPrefs->loc_notification() == 1)
    {
        include_once($path_to_root . "/inventory/includes/inventory_db.inc");
        $st_ids = array();
        $st_names = array();
        $st_num = array();
        $st_reorder = array();
    }
    $total = 0;
    
    $mt_header_id_local = add_mt_header($date_,$reference,$location_from,$location_to,$catcode,$memo_,$rsdno,$servedby, $location_from);
    if(!empty($company_prefs["deployment_status"])){
        $mt_header_id_target = add_mt_header($date_,$reference,$location_from,$location_to,$catcode,$memo_,$rsdno,$servedby, $location_to);
    }

    foreach ($Items as $line_item)
    {
        if ($SysPrefs->loc_notification() == 1)
            //$quantity=1;
            
            //$mb_flag = get_mb_flag($line_item->stock_id);
            
            //$stock_gl_mt_code = get_stock_mt_gl_code($line_item->stock_id);
            //$stock_gl_mt_code = $db_connections[user_company()]["gl_account"];
            /*Update sales_order_details for the new quantity received and the standard cost used for postings to GL
             and recorded in the stock movements for FIFO/LIFO stocks valuations*/
            //------------------- update average material cost and clearing account --------------------------------
            //$sql1 = "SELECT mb_flag, inventory_account, cogs_account, adjustment_account, sales_account, wip_account, dimension_id, dimension2_id FROM ".TB_PREF."stock_master WHERE stock_id = ".db_escape($line_item->stock_id);
            
           // $get1 = db_query($sql1,"retreive stock gl code");
           // $stock_gl_mt_code = db_fetch($get1);
            set_global_connection();
            $stock_gl_mt_code = get_stock_gl_code($line_item->stock_id);
            
            if (is_inventory_item($line_item->stock_id))
            //if (is_inventory_item($stock_gl_code['stock_id']))
            {
                //$total += add_gl_trans_mtbranch(ST_MERCHANDISETRANSFER, $transfer_id, $date_, $stock_gl_mt_code["inventory_account"], $stock_gl_mt_code['dimension_id'], $stock_gl_mt_code['dimension2_id'],-($line_item->standard_cost), '', 0, $line_item->stock_id);
                $total += add_gl_trans_mtbranch(ST_MERCHANDISETRANSFER, $transfer_id, $date_, $stock_gl_mt_code["inventory_account"], 0, 0,-(str_replace(",","",$line_item->standard_cost)*$line_item->quantity), '', 0, $line_item->stock_id, $mcode,
                 $master_file);
                    /*update_average_material_cost($po->supplier_id, $order_line->stock_id, $order_line->price,
                        $order_line->receive_qty, $date_);*/
            }
            
            $loc = calculate_reorder_level($location_from, $line_item, $st_ids, $st_names, $st_num, $st_reorder);
            
            add_stock_merchandise_transfer_item($transfer_id, $line_item->stock_id, $location_from, $location_to, $date_, $reference, $line_item->quantity, $line_item->lot_no, $line_item->chasis_no, $line_item->category_id, $line_item->item_code, $line_item->standard_cost, $line_item->type_out, $line_item->transno_out);
            
            $originating = 0;
            $originating = add_stock_mt_details($mt_header_id_local,$line_item->stock_id,$line_item->quantity,str_replace(",","",$line_item->standard_cost),0,$line_item->lot_no,$line_item->chasis_no,$line_item->item_code,$location_from,$originating);

            if(!empty($company_prefs["deployment_status"])){
                add_stock_mt_details($mt_header_id_target,$line_item->stock_id,$line_item->quantity,str_replace(",","",$line_item->standard_cost),0,$line_item->lot_no,$line_item->chasis_no,$line_item->item_code,$location_to,$originating);
            }
            
            
            //Added by Herald - 02/06/2021
            
            /*$inv_value = $line_item->standard_cost * $quantity;
             $adj_value = $line_item->standard_cost * -($quantity);
             
             if ($line_item->standard_cost > 0 || is_fixed_asset($mb_flag))
             {
             
             $stock_gl_codes = get_stock_gl_code($line_item->stock_id);
             
             add_gl_trans_std_cost(ST_LOCTRANSFER, $transfer_id, $date_,
             $stock_gl_codes['adjustment_account'], $stock_gl_codes['dimension_id'], $stock_gl_codes['dimension2_id'], $memo_, $adj_value  );
             
             add_gl_trans_std_cost(ST_LOCTRANSFER, $transfer_id, $date_, $stock_gl_codes['inventory_account'], 0, 0, $memo_, $inv_value);
             }*/
            
            
    }
    
    add_comments(ST_MERCHANDISETRANSFER, $transfer_id, $date_, $memo_);
    $accs = $db_connections[Get_db_coy($location_to)]["gl_account"];
    //$accs = get_supplier_accounts($po->supplier_id);
    $total += add_gl_trans_mtbranch(ST_MERCHANDISETRANSFER, $transfer_id, $date_, $accs, '', '', ($total)*-1, '', 0, null, $mcode, $master_file);
    $Refs->save(ST_MERCHANDISETRANSFER, $transfer_id, $reference);
    add_audit_trail(ST_MERCHANDISETRANSFER, $transfer_id, $date_,'Merchandise Transfer to '.$location_to);
    
    $args->trans_no = $transfer_id;
    hook_db_postwrite($args, ST_MERCHANDISETRANSFER);
    
    commit_transaction();
    
    if ($SysPrefs->loc_notification() == 1 && count($st_ids) > 0)
        send_reorder_email($loc, $st_ids, $st_names, $st_num, $st_reorder);
        
        return $transfer_id;
}

function add_stock_merchandise_transfer_manual($Items, $location_from, $location_to, $date_, $reference, $memo_,$catcode=0, $rsdno=null, $servedby=null)
{
    global $SysPrefs, $path_to_root, $Refs, $db_connections;
    $company_prefs = get_company_prefs();

    //begin_transaction();
    //$args = func_get_args();
    //$args = (object)array_combine(array('Items', 'location_from', 'location_to','date_', 'reference', 'memo_','catcode','rsdno','servedby'), $args);
    //$args->trans_no = 0;
    //hook_db_prewrite($args, ST_MERCHANDISETRANSFER);
    
    //$transfer_id = get_next_trans_no(ST_MERCHANDISETRANSFER);
    
    if ($SysPrefs->loc_notification() == 1)
    {
        include_once($path_to_root . "/inventory/includes/inventory_db.inc");
        //$st_ids = array();
        //$st_names = array();
        //$st_num = array();
        //$st_reorder = array();
    }
    //$total = 0;
    $mt_header_id = add_mt_header($date_,$reference,$location_from,$location_to,$catcode,$memo_,$rsdno,$servedby);
    
    foreach ($Items as $line_item)
    {
        //if ($SysPrefs->loc_notification() == 1)
            //set_global_connection();
            //$stock_gl_mt_code = get_stock_gl_code($line_item->stock_id);
            
            /*if (is_inventory_item($line_item->stock_id))
            {
                $total += add_gl_trans_mtbranch(ST_MERCHANDISETRANSFER, $transfer_id, $date_, $stock_gl_mt_code["inventory_account"], 0, 0,-(str_replace(",","",$line_item->standard_cost)*$line_item->quantity), '', 0, $line_item->stock_id);
            }*/
            
            //$loc = calculate_reorder_level($location_from, $line_item, $st_ids, $st_names, $st_num, $st_reorder);
            
            //add_stock_merchandise_transfer_item($transfer_id, $line_item->stock_id, $location_from, $location_to, $date_, $reference, $line_item->quantity, $line_item->lot_no, $line_item->chasis_no, $line_item->category_id, $line_item->item_code, $line_item->standard_cost, $line_item->type_out, $line_item->transno_out);
            
            add_stock_mt_details($mt_header_id,$line_item->stock_id,$line_item->quantity,str_replace(",","",$line_item->standard_cost),0,$line_item->lot_no,$line_item->chasis_no,$line_item->item_code);
            
            
            
            
            
    }
    
    //add_comments(ST_MERCHANDISETRANSFER, $transfer_id, $date_, $memo_);
    //$accs = $db_connections[Get_db_coy($location_to)]["gl_account"];
    //$accs = get_supplier_accounts($po->supplier_id);
    //$total += add_gl_trans_mtbranch(ST_MERCHANDISETRANSFER, $transfer_id, $date_, $accs, '', '', ($total)*-1, null);
    //$Refs->save(ST_MERCHANDISETRANSFER, $transfer_id, $reference);
    //add_audit_trail(ST_MERCHANDISETRANSFER, $transfer_id, $date_,'Merchandise Transfer to '.$location_to);
    
    //$args->trans_no = $transfer_id;
    //hook_db_postwrite($args, ST_MERCHANDISETRANSFER);
    
    //commit_transaction();
    
    //if ($SysPrefs->loc_notification() == 1 && count($st_ids) > 0) send_reorder_email($loc, $st_ids, $st_names, $st_num, $st_reorder);
        
    return $mt_header_id;
}


function add_stock_rrBranch($objDataGrid, $location_from, $branch_code, $date_, $reference, $memo_,$catcode=0,$mtreference)
{
    global $SysPrefs, $path_to_root, $Refs, $db_connections;
    
    begin_transaction();
    $args = func_get_args();
    $args = (object)array_combine(array('objDataGrid', 'location_from', 'branch_code','date_', 'reference', 'memo_','catcode','mtreference'), $args);
    $args->trans_no = 0;
    hook_db_prewrite($args,ST_RRBRANCH);
    
    $transfer_id = get_next_trans_no(ST_RRBRANCH);

    $mcode = $location_from;
    $master_file = get_db_location_name($location_from);
    
    if ($SysPrefs->loc_notification() == 1)
    {
        include_once($path_to_root . "/inventory/includes/inventory_db.inc");
        $st_ids = array();
        $st_names = array();
        $st_num = array();
        $st_reorder = array();
    }
    $total = 0;
    include_once($path_to_root . "/modules/serial_items/includes/modules_db.inc");
    add_js_ufile($path_to_root.'/js/merchandise_transfers_in.js');

    update_mt_header($reference,$mtreference,$branch_code);
    update_mt_header($reference,$mtreference,$location_from);

    $DataOnGrid = stripslashes(html_entity_decode($_REQUEST['DataOnGrid']));
    $objDataGrid = json_decode($DataOnGrid, true);
    
    foreach ($objDataGrid as $item)
    {
        //if ($SysPrefs->loc_notification() == 1)
            //$quantity=1;
            
            //set_global_connection();

            $line_item = $item['line_item'];
            $stock_id = $item['model'];
            $quantity = $item['qty'];
            $currentqty = $item['currentqty'];
            $receivedqty = $item['receivedqty'];
            $lot_no = $item['lot_no'];
            $chasis_no = $item['chasis_no'];
            $catcode = $item['catcode'];
            $item_code = $item['item_code'];
            $standard_cost = $item['standard_cost'];
            $originating_id = $item['originating_id'];


            update_stock_mt_details($line_item, $quantity, $currentqty, $receivedqty,$branch_code);
            update_stock_mt_details($originating_id, $quantity, $currentqty, $receivedqty,$location_from);
            
            $stock_gl_mt_code = get_stock_gl_code($stock_id);
            $totalCost =  str_replace(",","",$standard_cost) *  str_replace(",","",$quantity);
            /*$total += add_gl_trans_mtbranch(ST_RRBRANCH, $transfer_id, $date_, $stock_gl_mt_code["inventory_account"], 0, 0,
                $totalCost, '', 0, $line_item->stock_id);*/

            $total += add_gl_trans_mtbranch(ST_RRBRANCH, $transfer_id, $date_, $stock_gl_mt_code["inventory_account"], 0, 0,
                $totalCost, '', 0, $stock_id, $mcode, $master_file);
            //$loc = calculate_reorder_level($location_from, $line_item, $st_ids, $st_names, $st_num, $st_reorder);
            
            $refline = add_stock_rrbranch_item($transfer_id, $stock_id, $location_from, $branch_code, $date_, $reference, 
                $quantity, $lot_no, $chasis_no, $catcode,  $item_code, $standard_cost);
            //Added by Herald 03/25/2021 - insert serialise item
            //if($line_item->serialised){
                //set_global_connection();
                add_serialitems_detail($transfer_id,$refline,$lot_no,$quantity,'0000-00-00','0000-00-00',
                    $item_code,$branch_code, $chasis_no,ST_RRBRANCH);
            //}     
    }
    
    add_comments(ST_RRBRANCH, $transfer_id, $date_, $memo_);
    //$accs = $db_connections[user_company()]["gl_account"];
    //$accs = get_db_location_glaccount($location_from);
    $accs = $db_connections[Get_db_coy($location_from)]["gl_account"];
    
    //$total += add_gl_trans_mtbranch(ST_RRBRANCH, $transfer_id, $date_, $accs, '', '', -$total, null);
    $total += add_gl_trans_mtbranch(ST_RRBRANCH, $transfer_id, $date_, $accs, '', '', -$total, '', 0, null, $mcode, $master_file);
    
    $Refs->save(ST_RRBRANCH, $transfer_id, $reference);
    add_audit_trail(ST_RRBRANCH, $transfer_id, $date_,'RR Branch to '.$branch_code);
    
    $args->trans_no = $transfer_id;
    hook_db_postwrite($args, ST_RRBRANCH);
    
    commit_transaction();
    
    if ($SysPrefs->loc_notification() == 1 && count($st_ids) > 0)
        send_reorder_email($loc, $st_ids, $st_names, $st_num, $st_reorder);

        
    return $transfer_id;
}

//--Added by Robert--08/25/2022--//
function add_stock_rrBranch_manual($objDataGrid, $FromStockLocation, $branch_code, $date_, $br_reference, $memo_,$catcode=0,$mt_reference, 
    $servedby)
{
    global $SysPrefs, $path_to_root, $Refs, $db_connections;
    
    begin_transaction();
    $args = func_get_args();
    $args = (object)array_combine(array('objDataGrid', 'FromStockLocation', 'branch_code','date_', 'br_reference', 'memo_','catcode','mt_reference', 'servedby'), $args);
    $args->trans_no = 0;
    hook_db_prewrite($args,ST_RRBRANCH);
    
    $transfer_id = get_next_trans_no(ST_RRBRANCH);

    $mcode = $FromStockLocation;
    $master_file = get_db_location_name($FromStockLocation);
    
    if ($SysPrefs->loc_notification() == 1)
    {
        include_once($path_to_root . "/inventory/includes/inventory_db.inc");
        $st_ids = array();
        $st_names = array();
        $st_num = array();
        $st_reorder = array();
    }
    $total = 0;
    include_once($path_to_root . "/modules/serial_items/includes/modules_db.inc");
    add_js_ufile($path_to_root.'/js/merchandise_transfers_in.js');

    $DataOnGrid = stripslashes(html_entity_decode($_REQUEST['DataOnGrid']));
    $objDataGrid = json_decode($DataOnGrid, true);

    $mt_header_id_local_manual = add_mt_header_manual($date_, $mt_reference, $br_reference, $FromStockLocation, $branch_code, 
        $catcode, $memo_, $servedby);
    
    foreach ($objDataGrid as $item)
    {
            $stock_id = $item['stock_id'];
            $item_code = $item['item_code'];
            $stock_qty = $item['qty'];
            $standard_cost = $item['standard_cost'];
            $lot_no = $item['lot_no'];
            $chasis_no = $item['chasis_no'];
            $serialised = $item['serialised'];
            $catcode = $item['catcode'];

            add_stock_mt_details_manual($mt_header_id_local_manual, $stock_id, $stock_qty, str_replace(",","",$standard_cost), 0, $lot_no, 
                $chasis_no, $item_code);
            
            $stock_gl_mt_code = get_stock_gl_code($stock_id);
            $totalCost =  str_replace(",","",$standard_cost) *  str_replace(",","",$stock_qty);

            $total += add_gl_trans_mtbranch(ST_RRBRANCH, $transfer_id, $date_, $stock_gl_mt_code["inventory_account"], 0, 0,
                $totalCost, '', 0, $stock_id, $mcode, $master_file);
            
            $refline = add_stock_rrbranch_item($transfer_id, $stock_id, $FromStockLocation, $branch_code, $date_, $br_reference, 
                $stock_qty, $lot_no, $chasis_no, $catcode,  $item_code, $standard_cost);
           
            add_serialitems_detail($transfer_id,$refline,$lot_no,$stock_qty,'0000-00-00','0000-00-00',
                $item_code,$branch_code, $chasis_no,ST_RRBRANCH);   
    }
    
    add_comments(ST_RRBRANCH, $transfer_id, $date_, $memo_);

    $accs = $db_connections[Get_db_coy($FromStockLocation)]["gl_account"];
    
    //$total += add_gl_trans_mtbranch(ST_RRBRANCH, $transfer_id, $date_, $accs, '', '', -$total, null);
    $total += add_gl_trans_mtbranch(ST_RRBRANCH, $transfer_id, $date_, $accs, '', '', -$total, '', 0, null, $mcode, $master_file);
    
    $Refs->save(ST_RRBRANCH, $transfer_id, $br_reference);
    add_audit_trail(ST_RRBRANCH, $transfer_id, $date_,'RR Branch to '.$branch_code);
    
    $args->trans_no = $transfer_id;
    hook_db_postwrite($args, ST_RRBRANCH);
    
    commit_transaction();
    
    if ($SysPrefs->loc_notification() == 1 && count($st_ids) > 0)
        send_reorder_email($loc, $st_ids, $st_names, $st_num, $st_reorder);

        
    return $transfer_id;
}

function add_stock_Complimentary_Items($Items, $location_from, $date_, $reference, $memo_,$catcode=0, $person_type=99, $person_id_header='', $masterfile='')
{
    global $SysPrefs, $path_to_root, $Refs, $db_connections;
    
    begin_transaction();
    $args = func_get_args();
    $args = (object)array_combine(array('Items', 'location_from','date_', 'reference', 'memo_','catcode','person_type','person_id_header','masterfile'), $args);
    $args->trans_no = 0;
    hook_db_prewrite($args,ST_COMPLIMENTARYITEM);
    
    $transfer_id = get_next_trans_no(ST_COMPLIMENTARYITEM);
    
    if ($SysPrefs->loc_notification() == 1)
    {
        include_once($path_to_root . "/inventory/includes/inventory_db.inc");
        $st_ids = array();
        $st_names = array();
        $st_num = array();
        $st_reorder = array();
    }
    $total = 0;
    //include_once($path_to_root . "/modules/serial_items/includes/modules_db.inc");
    
    $complintary_header_id = add_complimentary_header($date_,$reference,$location_from,$catcode,$person_type, $person_id_header, $masterfile);
    
    foreach ($Items as $line_item)
    {
        
        //$stock_gl_mt_code = get_stock_gl_code($line_item->stock_id);
        //set_global_connection();
        
        //$total += add_gl_trans_mtbranch(ST_RRBRANCH, $transfer_id, $date_, $stock_gl_mt_code["inventory_account"], 0, 0,$line_item->standard_cost, '', 0, $line_item->stock_id);
        
        //$loc = calculate_reorder_level($location_from, $line_item, $st_ids, $st_names, $st_num, $st_reorder);
        
        $refline = add_stock_complimentary_item($transfer_id, $line_item->stock_id, $location_from, $date_, $reference, $line_item->quantity, $line_item->lot_no, $line_item->chasis_no, $line_item->category_id, $line_item->item_code, $line_item->standard_cost, $line_item->type_out, $line_item->transno_out);
    }

    add_comments(ST_COMPLIMENTARYITEM, $transfer_id, $date_, $memo_);
    //$accs = $db_connections[user_company()]["gl_account"];
    //$accs = get_supplier_accounts($po->supplier_id);
    //$total += add_gl_trans_mtbranch(ST_COMPLIMENTARYITEM, $transfer_id, $date_, $accs, '', '', -$total, null);
    
    $Refs->save(ST_COMPLIMENTARYITEM, $transfer_id, $reference);
    add_audit_trail(ST_COMPLIMENTARYITEM, $transfer_id, $date_,'');
    
    $args->trans_no = $transfer_id;
    hook_db_postwrite($args, ST_COMPLIMENTARYITEM);
    
    commit_transaction();
    
    
        
        
        return $transfer_id;
}


function add_stock_transfer($Items, $location_from, $location_to, $date_, $reference, $memo_,$catcode=0)
{
    global $SysPrefs, $path_to_root, $Refs;
	
	begin_transaction();
	$args = func_get_args();
	$args = (object)array_combine(array('Items', 'location_from', 'location_to','date_', 'reference', 'memo_','catcode'), $args);
	$args->trans_no = 0;
	hook_db_prewrite($args, ST_LOCTRANSFER);

	$transfer_id = get_next_trans_no(ST_LOCTRANSFER);

	if ($SysPrefs->loc_notification() == 1)
	{
		include_once($path_to_root . "/inventory/includes/inventory_db.inc");
		$st_ids = array();
		$st_names = array();
		$st_num = array();
		$st_reorder = array();
	}
	
	$mt_header_id = add_mt_header($date_,$reference,$location_from,$location_to,$catcode,$memo_);
	
	foreach ($Items as $line_item)
	{
		if ($SysPrefs->loc_notification() == 1)
		    //$quantity=1;
		    
		    //$mb_flag = get_mb_flag($line_item->stock_id);
		
			$loc = calculate_reorder_level($location_from, $line_item, $st_ids, $st_names, $st_num, $st_reorder); 
		
			add_stock_transfer_item($transfer_id, $line_item->stock_id, $location_from, $location_to, $date_, $reference, $line_item->quantity, $line_item->lot_no, $line_item->chasis_no, $line_item->category_id, $line_item->item_code, $line_item->standard_cost);
			
			add_stock_mt_details($mt_header_id,$line_item->stock_id,1,$line_item->standard_cost,0,$line_item->lot_no,$line_item->chasis_no,$line_item->item_code);
			
	}

	add_comments(ST_LOCTRANSFER, $transfer_id, $date_, $memo_);

	$Refs->save(ST_LOCTRANSFER, $transfer_id, $reference);
	add_audit_trail(ST_LOCTRANSFER, $transfer_id, $date_);

	$args->trans_no = $transfer_id;
	hook_db_postwrite($args, ST_LOCTRANSFER);

	commit_transaction();
	
	if ($SysPrefs->loc_notification() == 1 && count($st_ids) > 0)
		send_reorder_email($loc, $st_ids, $st_names, $st_num, $st_reorder);

	return $transfer_id;
}

//Added by Herald 03/27/2021 for New Item Transfer Location
function add_item_location_transfer($Items, $location_from, $location_to, $date_, $reference, $memo_,$catcode=0)
{
    global $SysPrefs, $path_to_root, $Refs;
    
    begin_transaction();
    $args = func_get_args();
    $args = (object)array_combine(array('Items', 'location_from', 'location_to',
        'date_', 'reference', 'memo_','$catcode'), $args);
    $args->trans_no = 0;
    hook_db_prewrite($args, ST_LOCTRANSFER);
    
    $transfer_id = get_next_trans_no(ST_LOCTRANSFER);
    
    if ($SysPrefs->loc_notification() == 1)
    {
        include_once($path_to_root . "/inventory/includes/inventory_db.inc");
        $st_ids = array();
        $st_names = array();
        $st_num = array();
        $st_reorder = array();
    }
    foreach ($Items as $line_item)
    {
        set_global_connection();
        if ($SysPrefs->loc_notification() == 1)
            $loc = calculate_reorder_level($location_from, $line_item, $st_ids, $st_names, $st_num, $st_reorder);
            add_stock_transfer_item_location($transfer_id, $line_item->stock_id, $location_from,
                $location_to, $date_, $reference, $line_item->quantity, $line_item->lot_no, $line_item->chasis_no, $line_item->category_id, $line_item->item_code, $line_item->standard_cost, $line_item->type_out, $line_item->transno_out, $line_item->item_type, $line_item->remarks);
    }
    
    add_comments(ST_LOCTRANSFER, $transfer_id, $date_, $memo_);
    
    $Refs->save(ST_LOCTRANSFER, $transfer_id, $reference);
    add_audit_trail(ST_LOCTRANSFER, $transfer_id, $date_);
    
    $args->trans_no = $transfer_id;
    hook_db_postwrite($args, ST_LOCTRANSFER);
    
    commit_transaction();
    
    if ($SysPrefs->loc_notification() == 1 && count($st_ids) > 0)
        send_reorder_email($loc, $st_ids, $st_names, $st_num, $st_reorder);
        
        return $transfer_id;
}
//Added by Herald - 02/03/2021
function add_mt_header($transdate, $reference, $fromlocation, $tolocation, $cat_id=0, $comments, $rsdno, $servedby, $targetdb){
    //global $def_coy;
    //set_global_connection($def_coy);

    $db_coy = Get_db_coy($targetdb);
    set_global_connection($db_coy);
    
    $trans_date = date2sql($transdate);
    
    $sql = "INSERT INTO ".TB_PREF."mt_header (mt_header_date, mt_header_reference, mt_header_fromlocation, mt_header_tolocation, mt_header_category_id, mt_header_comments, mt_header_rsd, mt_header_servedby) VALUES ('$trans_date', ".db_escape($reference).", ".db_escape($fromlocation).", ".db_escape($tolocation).", '".$cat_id."', ".db_escape($comments).", ".db_escape($rsdno).", ".db_escape($servedby).")";
                
    db_query($sql, "The MT Header record cannot be inserted");
                
    $dbid = db_insert_id();
    
    set_global_connection();
    return $dbid;
}

//Added by Robert - 08/25/2022
function add_mt_header_manual($transdate, $mt_reference, $br_reference, $fromlocation, $tolocation, $cat_id=0, $comments, $servedby){
    //$db_coy = Get_db_coy($targetdb);
    set_global_connection();
    
    $trans_date = date2sql($transdate);
    
    $sql = "INSERT INTO ".TB_PREF."mt_header (mt_header_date, mt_header_reference, mt_header_rrbranch_reference, mt_header_fromlocation, mt_header_tolocation, mt_header_category_id, mt_header_comments, mt_header_servedby) VALUES ('$trans_date', ".db_escape($mt_reference).", ".db_escape($br_reference).", ".db_escape($fromlocation).", ".db_escape($tolocation).", '".$cat_id."', ".db_escape($comments).", 
        ".db_escape($servedby).")";
                
    db_query($sql, "The MT Header record cannot be inserted");
                
    $dbid = db_insert_id();
    
    set_global_connection();
    return $dbid;
}

//Added by Robert - 08/25/2022
function add_stock_mt_details_manual($header_id, $stock_id, $quantity=0, $std_cost, $price=0, $lot_no='',$chasis_no='', $color_code='')
{
    //$db_coy = Get_db_coy($targetdb);
    set_global_connection();
    
    $sql = "INSERT INTO ".TB_PREF."mt_details (mt_details_header_id, mt_details_stock_id, mt_details_item_code, mt_details_serial_no, mt_details_chasis_no, mt_details_total_qty, mt_details_st_cost, mt_details_price, mt_details_recvd_qty, mt_details_status) VALUES ("
        .db_escape($header_id).","
            .db_escape($stock_id).", 
                ".db_escape($color_code).", "
                    .db_escape($lot_no).", 
                        ".db_escape($chasis_no).", 
                            ".db_escape($quantity).", "
                                .db_escape($std_cost).", "
                                     .db_escape($price).", "
                                        .db_escape($quantity).",
                                            '2')";            
    db_query($sql, "The MT details record cannot be inserted");
                
    $dbid = db_insert_id();
                
    set_global_connection();
    return $dbid;
}

//Added by Herald - 08/31/2021
function add_complimentary_header($transdate, $reference, $fromlocation, $cat_id=0, $person_type=99, $mcode='', $masterfile=''){
    set_global_connection();
    
    $trans_date = date2sql($transdate);
    
    $sql = "INSERT INTO ".TB_PREF."complimentary_items (trans_date, reference, location, category, person_type, mcode, masterfile) VALUES ('$trans_date', ".db_escape($reference).", ".db_escape($fromlocation).", '".$cat_id."', ".db_escape($person_type).", ".db_escape($mcode).", ".db_escape($masterfile).")";
    
    db_query($sql, "The complimentary Header record cannot be inserted");
    
    $dbid = db_insert_id();
    
    set_global_connection();
    return $dbid;
}
//Added by Herald - 03/18/2021 - to update the MT header for the RR Reference
function update_mt_header($reference, $mtreference, $targetdb){
    $db_coy = Get_db_coy($targetdb);
    set_global_connection($db_coy);
    
    $sql = "UPDATE ".TB_PREF."mt_header SET mt_header_rrbranch_reference=".db_escape($reference)." WHERE mt_header_reference= ".db_escape($mtreference);
    
    db_query($sql, "The MT Header record cannot be inserted");
    
    set_global_connection();
    return true;
}

function add_stock_mt_details($header_id, $stock_id, $quantity=0, $std_cost, $price=0, $lot_no='',$chasis_no='', $color_code='', $targetdb, 
    $originating)
{
    //$date = date2sql($date_);
    //$man_date = date2sql($manufacture_date);
    //$exp_date = date2sql($expire_date);
    //global $def_coy;
    //set_global_connection($def_coy);

    $db_coy = Get_db_coy($targetdb);
    set_global_connection($db_coy);
    
    $sql = "INSERT INTO ".TB_PREF."mt_details (mt_details_header_id, mt_details_stock_id, mt_details_item_code, mt_details_serial_no, mt_details_chasis_no, mt_details_total_qty, mt_details_st_cost, mt_details_price, originating_id) VALUES ("
        .db_escape($header_id).","
            .db_escape($stock_id).", ".db_escape($color_code).", "
                .db_escape($lot_no).", ".db_escape($chasis_no).", ".db_escape($quantity).", "
                    .db_escape($std_cost).", "
                        .db_escape($price).", "
                            .db_escape($originating).")";
                
    db_query($sql, "The MT details record cannot be inserted");
                
    $dbid = db_insert_id();
                
    set_global_connection();
    return $dbid;
}

function update_stock_mt_details($line_item,$quantity=0,$currentqty,$receivedqty,$targetdb)
{
    $db_coy = Get_db_coy($targetdb);
    set_global_connection($db_coy);
    if($quantity==$currentqty) {
        $status = '2';
    }else{
        $status = '1';   
    }

    $quantityreceived = $receivedqty + $quantity;

    $sql = "UPDATE ".TB_PREF."mt_details SET mt_details_recvd_qty='".$quantityreceived."', mt_details_status='".$status."'";
    
        
    $sql .= " WHERE mt_details_id=".db_escape($line_item);
    db_query($sql, "The MT details record cannot be updated");
    
    
    set_global_connection();
    return true;
}
//-------------------------------------------------------------------------------------------------------------

// add 2 stock_moves entries for a stock transfer
// $date_ is display date (not sql)
// std_cost is in HOME currency
// it seems the standard_cost field is not used at all

function add_stock_transfer_item($transfer_id, $stock_id, $location_from, $location_to,	$date_, $reference, $quantity,$lot_no,$chasis_no, $category_id, $color_code='',$standard_cost=0)
{
    add_stock_move(ST_MERCHANDISETRANSFER, $stock_id, $transfer_id, $location_from,	$date_, $reference, -$quantity,$standard_cost,0,$lot_no,$chasis_no, $category_id, $color_code);
    //Removed by Herald 02/06/2021
    //add_stock_move(ST_LOCTRANSFER, $stock_id, $transfer_id, $location_to, $date_, $reference, $quantity, 0,0,$lot_no,$chasis_no, $category_id, $color_code);

}
//Added by Herald 03/27/2021 for new Item Transfer Location    
function add_stock_transfer_item_location($transfer_id, $stock_id, $location_from, $location_to,	$date_, $reference, $quantity,$lot_no,$chasis_no, $category_id, $color_code='',$standard_cost=0, $type_out=0, $transno_out=0, $item_type='new', $remarks=null)
{
    add_stock_move(ST_LOCTRANSFER, $stock_id, $transfer_id, $location_from,	$date_, $reference, -$quantity,$standard_cost,0,$lot_no,$chasis_no, $category_id, $color_code, $type_out, $transno_out, $item_type, $remarks);
    add_stock_move_transfer_approval(ST_LOCTRANSFER, $stock_id, $transfer_id, $location_to, $date_, $reference, $quantity, $standard_cost,0,$lot_no,$chasis_no, $category_id, $color_code, 0, 0, $item_type, $remarks);
    
}
function add_stock_merchandise_transfer_item($transfer_id, $stock_id, $location_from, $location_to,	$date_, $reference, $quantity,$lot_no,$chasis_no, $category_id, $color_code='',$standard_cost=0, $type_out=0, $transno_out=0)
{
    add_stock_move(ST_MERCHANDISETRANSFER, $stock_id, $transfer_id, $location_from,	$date_, $reference, -$quantity,str_replace(",","",$standard_cost),0,$lot_no,$chasis_no, $category_id, $color_code, $type_out, $transno_out);
    //Removed by Herald 02/06/2021
    //add_stock_move(ST_LOCTRANSFER, $stock_id, $transfer_id, $location_to, $date_, $reference, $quantity, 0,0,$lot_no,$chasis_no, $category_id, $color_code);
    
}
function add_stock_rrbranch_item($transfer_id, $stock_id, $location_from, $branch_code, $date_, $reference, $quantity,$lot_no,$chasis_no, $category_id, $color_code='', $standard_cost=0)
{
    return add_stock_move(ST_RRBRANCH, $stock_id, $transfer_id, $branch_code, $date_, $reference, $quantity, 
        str_replace(",","",$standard_cost),0,$lot_no,$chasis_no, $category_id, $color_code);
    //Removed by Herald 02/06/2021
    //add_stock_move(ST_LOCTRANSFER, $stock_id, $transfer_id, $location_to, $date_, $reference, $quantity, 0,0,$lot_no,$chasis_no, $category_id, $color_code);
    
}

function add_stock_complimentary_item($transfer_id, $stock_id, $location_from,	$date_, $reference, $quantity,$lot_no,$chasis_no, $category_id, $color_code='',$standard_cost=0, $type_out, $transno_out)
{
    return add_stock_move(ST_COMPLIMENTARYITEM, $stock_id, $transfer_id, $location_from, $date_, $reference, -$quantity,$standard_cost,0,$lot_no,$chasis_no, $category_id, $color_code, $type_out, $transno_out);
    //Removed by Herald 02/06/2021
    //add_stock_move(ST_LOCTRANSFER, $stock_id, $transfer_id, $location_to, $date_, $reference, $quantity, 0,0,$lot_no,$chasis_no, $category_id, $color_code);
    
}

//-------------------------------------------------------------------------------------------------------------
//
//	Get inventory transfer common data (currently this is still stored in stock_moves table).
//
function get_stock_transfer($trans_no)
{
    set_global_connection();
	// retrieve common data from any two from/to move records
	$sql = "SELECT loc_from.*, loc_to.*
		FROM
			(SELECT trans_no, type, tran_date, reference, move.loc_code as from_loc, loc.location_name as from_name
			FROM ".TB_PREF."stock_moves move
				LEFT JOIN ".TB_PREF."locations loc ON loc.loc_code=move.loc_code
			WHERE type=".ST_MERCHANDISETRANSFER." AND trans_no=".db_escape($trans_no). " AND qty<0 LIMIT 1) loc_from,

			(SELECT move.loc_code as to_loc, loc.location_name as to_name
			FROM ".TB_PREF."stock_moves move
				LEFT JOIN ".TB_PREF."locations loc ON loc.loc_code=move.loc_code
			WHERE type=".ST_MERCHANDISETRANSFER." AND trans_no=".db_escape($trans_no). " AND qty>0 LIMIT 1) loc_to";

	$result = db_query($sql, "Could not get transfer common data");

	$data = db_fetch($result);

	return $data;
}
//Added by Herald - 06/15/2021
function get_stock_loctransfer($trans_no)
{
    set_global_connection();
    // retrieve common data from any two from/to move records
    $sql = "SELECT loc_from.*, loc_to.*
		FROM
			(SELECT trans_no, type, tran_date, reference, move.loc_code as from_loc, loc.location_name as from_name
			FROM ".TB_PREF."stock_moves move
				LEFT JOIN ".TB_PREF."locations loc ON loc.loc_code=move.loc_code
			WHERE type=".ST_LOCTRANSFER." AND trans_no=".db_escape($trans_no). " AND qty<0 LIMIT 1) loc_from,
			    
			(SELECT move.loc_code as to_loc, loc.location_name as to_name
			FROM ".TB_PREF."stock_moves move
				LEFT JOIN ".TB_PREF."locations loc ON loc.loc_code=move.loc_code
			WHERE type=".ST_LOCTRANSFER." AND trans_no=".db_escape($trans_no). " AND qty>0 LIMIT 1) loc_to";
    
    $result = db_query($sql, "Could not get transfer common data");
    
    $data = db_fetch($result);
    
    return $data;
}
function get_stock_merchandise_transfer($trans_no)
{
    set_global_connection();
    // retrieve common data from any two from/to move records
    $sql = "SELECT loc_from.*
        FROM
			(SELECT trans_no, type, tran_date, reference, move.loc_code as from_loc, loc.location_name as from_name, move.category_id, sc.description as category_name
			FROM ".TB_PREF."stock_moves move
			LEFT JOIN ".TB_PREF."locations loc ON loc.loc_code=move.loc_code
			LEFT JOIN ".TB_PREF."stock_category sc ON move.category_id=sc.category_id
			WHERE move.type=".ST_MERCHANDISETRANSFER." AND move.trans_no=".db_escape($trans_no). " AND qty<0 LIMIT 1) loc_from";
    
    $result = db_query($sql, "Could not get transfer common data");
    
    $data = db_fetch($result);
    
    return $data;
}
//Added by Herald 01/15/2022 for MT Repo
function get_stock_merchandise_transfer_repo($trans_no)
{
    set_global_connection();
    // retrieve common data from any two from/to move records
    $sql = "SELECT loc_from.*
        FROM
			(SELECT trans_no, type, tran_date, reference, move.loc_code as from_loc, loc.location_name as from_name, move.category_id, sc.description as category_name
			FROM ".TB_PREF."stock_moves move
			LEFT JOIN ".TB_PREF."locations loc ON loc.loc_code=move.loc_code
			LEFT JOIN ".TB_PREF."stock_category sc ON move.category_id=sc.category_id
			WHERE move.type=".ST_MERCHANDISETRANSFERREPO." AND move.trans_no=".db_escape($trans_no). " AND qty<0 LIMIT 1) loc_from";
    
    $result = db_query($sql, "Could not get transfer common data");
    
    $data = db_fetch($result);
    
    return $data;
}
//-------------------------------------------------------------------------------------------------------------
function get_stock_rrbranch_transfer($trans_no)
{
    set_global_connection();
    // retrieve common data from any two from/to move records
    $sql = "SELECT loc_to.*
        FROM
			(SELECT trans_no, type, tran_date, reference, move.loc_code as from_loc, loc.location_name as from_name, move.category_id, sc.description as category_name
			FROM ".TB_PREF."stock_moves move
			LEFT JOIN ".TB_PREF."locations loc ON loc.loc_code=move.loc_code
			LEFT JOIN ".TB_PREF."stock_category sc ON move.category_id=sc.category_id
			WHERE move.type=".ST_RRBRANCH." AND move.trans_no=".db_escape($trans_no). " AND qty>0 LIMIT 1) loc_to";
    
    $result = db_query($sql, "Could not get transfer common data");
    
    $data = db_fetch($result);
    
    return $data;
}

function get_stock_transfer_items($trans_no)
{
    $result = get_stock_moves(ST_MERCHANDISETRANSFER, $trans_no);

	if (db_num_rows($result) == 0)
	{
		return null;
	}

	return $result;
}
function get_stock_transfer_items_repo($trans_no)
{
    $result = get_stock_moves(ST_MERCHANDISETRANSFERREPO, $trans_no);
    
    if (db_num_rows($result) == 0)
    {
        return null;
    }
    
    return $result;
}

function get_stock_rrbanch_items($trans_no)
{
    $result = get_stock_moves(ST_RRBRANCH, $trans_no);
    
    if (db_num_rows($result) == 0)
    {
        return null;
    }
    
    return $result;
}

//-------------------------------------------------------------------------------------------------------------

function update_merchandise_transfer($trans_id){
    global $def_coy;
    set_global_connection($def_coy);
    
    $sql = "UPDATE ".TB_PREF."mt_details SET price=".db_escape($price);
    if ($uom != "")
        $sql .= ",suppliers_uom=".db_escape($uom);
        if ($description != "")
            $sql .= ",supplier_description=".db_escape($description);
            $sql .= " WHERE stock_id=".db_escape($stock_id)." AND supplier_id=".db_escape($supplier_id);
            db_query($sql,"The supplier purchasing details could not be updated");
            return true;

}

function void_stock_transfer($type_no)
{
    hook_db_prevoid(ST_MERCHANDISETRANSFER, $type_no);
    void_gl_trans(ST_MERCHANDISETRANSFER, $type_no);
    void_stock_move(ST_MERCHANDISETRANSFER, $type_no);
}

function void_stock_transfer_repo($type_no)
{
    hook_db_prevoid(ST_MERCHANDISETRANSFERREPO, $type_no);
    void_gl_trans(ST_MERCHANDISETRANSFERREPO, $type_no);
    void_stock_move(ST_MERCHANDISETRANSFERREPO, $type_no);
}

function void_rrbranch_transfer($type_no)
{
    hook_db_prevoid(ST_RRBRANCH, $type_no);
    void_gl_trans(ST_RRBRANCH, $type_no);
    void_stock_move(ST_RRBRANCH, $type_no);
}

function check_mt_status($reference)
{
    global $def_coy;
    set_global_connection($def_coy);
    
    $sql = "SELECT mt_header_rrbranch_reference
		FROM ".TB_PREF."mt_header
		WHERE mt_header_reference=".db_escape($reference);
	$result = db_query($sql, "Cannot retreive a MT Header transaction");
	$data = db_fetch($result);
	set_global_connection();
	return $data["mt_header_rrbranch_reference"];
}