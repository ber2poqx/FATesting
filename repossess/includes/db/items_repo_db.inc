<?php
function get_all_stockmoves_repo($start,$end,$querystr='',$category=0,$branchcode='',$all=false,$reference='', $trans_date=null,$serialquery=null){
    
    $sql = "SELECT SUM(serial.qty) as serialise_qty, serial.stock_id as model, serial.color_code as serialise_item_code,
     serial.lot_no as serialise_lot_no, serial.chassis_no as serialise_chasis_no, serial.loc_code as serialise_loc_code, 
     smaster.description as stock_description, icode.description as item_description, smaster.serialised, serial.type_out,
     serial.transno_out,serial.tran_date, serial.reference, serial.standard_cost, serial.item_type 
     FROM ".TB_PREF."stock_moves serial 
     LEFT JOIN ".TB_PREF."voided v ON serial.type=v.type AND serial.trans_no=v.id 
     INNER JOIN ".TB_PREF."item_codes icode ON serial.color_code=icode.item_code 
     INNER JOIN ".TB_PREF."stock_master smaster ON icode.stock_id=smaster.stock_id 
     WHERE ISNULL(v.id) AND serial.item_type = 'repo'";
    
    if($category!=0){
        $sql.=" AND smaster.category_id=$category";
    }
    if($reference!=''){
        $sql.=" AND serial.reference='".$reference."'";
    }
    if($branchcode!=''){
        $sql.=" AND serial.loc_code='".$branchcode."'";
    }
    if($trans_date!=null){
        $sql.=" AND serial.tran_date<=".db_escape($trans_date);
    }
    if($serialquery!=null){
        $sql.=" AND (serial.lot_no LIKE '%".$serialquery."%')";
    }
    if($querystr!=''){
        $sql.=" AND (serial.color_code LIKE '%".$querystr."%' OR smaster.description LIKE '%".$querystr."%' OR icode.description LIKE '%".$querystr."%')";
    }
    $sql.=" group by serial.type_out, serial.transno_out, serial.stock_id, serial.lot_no ORDER BY serial.stock_id,serial.tran_date, serial.standard_cost";
    
    if(!$all){
        $sql.=" LIMIT $start,$end";
    }
    return db_query($sql, "could not get all Serial Items");
}

function add_stock_merchandise_transfer_repo($Items, $location_from, $location_to, $date_, $reference, $memo_,$catcode=0, $rsdno=null, $servedby=null)
{
    global $SysPrefs, $path_to_root, $Refs, $db_connections;
    
    begin_transaction();
    $args = func_get_args();
    $args = (object)array_combine(array('Items', 'location_from', 'location_to','date_', 'reference', 'memo_','catcode','rsdno','servedby'), $args);
    $args->trans_no = 0;
    
    hook_db_prewrite($args, ST_MERCHANDISETRANSFERREPO);
    
    $transfer_id = get_next_trans_no(ST_MERCHANDISETRANSFERREPO);

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
    $mt_header_id = add_mt_header_repo($date_,$reference,$location_from,$location_to,$catcode,$memo_,$rsdno,$servedby);
    
    foreach ($Items as $line_item)
    {
            set_global_connection();
            $stock_gl_mt_code = get_stock_repo_gl_code($line_item->category_id);
            
            $total += add_gl_trans_mtbranch(ST_MERCHANDISETRANSFERREPO, $transfer_id, $date_, $stock_gl_mt_code["dflt_repo_invty_act"], 0, 0,-(str_replace(",","",$line_item->standard_cost)*$line_item->quantity), '', 0, $line_item->stock_id, $mcode, 
                $master_file);
            
            add_stock_merchandise_transfer_item_repo($transfer_id, $line_item->stock_id, $location_from, $location_to, $date_, $reference, $line_item->quantity, $line_item->lot_no, $line_item->chasis_no, $line_item->category_id, $line_item->item_code, $line_item->standard_cost, $line_item->type_out, $line_item->transno_out);
            
            add_stock_mt_repo_details($mt_header_id,$line_item->stock_id,$line_item->quantity,str_replace(",","",$line_item->standard_cost),0,$line_item->lot_no,$line_item->chasis_no,$line_item->item_code);
            //Updating Repo Item Status
            update_repo_status($line_item->transno_out);
    }
    
    add_comments(ST_MERCHANDISETRANSFERREPO, $transfer_id, $date_, $memo_);
    $accs = $db_connections[Get_db_coy($location_to)]["gl_account"];
    //$accs = get_supplier_accounts($po->supplier_id);
    $total += add_gl_trans_mtbranch(ST_MERCHANDISETRANSFERREPO, $transfer_id, $date_, $accs, '', '', ($total)*-1, '', 0, null, $mcode, 
        $master_file);
    $Refs->save(ST_MERCHANDISETRANSFERREPO, $transfer_id, $reference);
    add_audit_trail(ST_MERCHANDISETRANSFERREPO, $transfer_id, $date_,'Merchandise Transfer Repo to '.$location_to);
    
    $args->trans_no = $transfer_id;
    hook_db_postwrite($args, ST_MERCHANDISETRANSFERREPO);
    
    commit_transaction();
    
    if ($SysPrefs->loc_notification() == 1 && count($st_ids) > 0)
        send_reorder_email($loc, $st_ids, $st_names, $st_num, $st_reorder);
        
        return $transfer_id;
}
//added 01/08/2022 by Herald Felisilda
function update_repo_status($trans_no){
    set_global_connection();
    
    $sql = "UPDATE ".TB_PREF."repo_item_details SET status='1'";
    
    $sql .= " WHERE repo_id=".db_escape($trans_no);
    db_query($sql, "The Repo Items Details record cannot be updated");
    return true;
}

//Added by Herald - 02/03/2021
function add_mt_header_repo($transdate, $reference, $fromlocation, $tolocation, $cat_id=0, $comments, $rsdno, $servedby){
    global $def_coy;
    set_global_connection($def_coy);
    
    $trans_date = date2sql($transdate);
    
    $sql = "INSERT INTO ".TB_PREF."mt_header (mt_header_date, mt_header_reference, mt_header_fromlocation, mt_header_tolocation, mt_header_category_id, mt_header_comments, mt_header_rsd, mt_header_servedby, mt_header_item_type) VALUES ('$trans_date', ".db_escape($reference).", ".db_escape($fromlocation).", ".db_escape($tolocation).", '".$cat_id."', ".db_escape($comments).", ".db_escape($rsdno).", ".db_escape($servedby).", 'repo')";
    
    db_query($sql, "The MT Header Repo record cannot be inserted");
    
    $dbid = db_insert_id();
    
    set_global_connection();
    return $dbid;
}

function get_stock_repo_gl_code($cat_id)
{
    global $def_coy;
    set_global_connection($def_coy);
    $sql = "SELECT dflt_repo_invty_act FROM " . TB_PREF . "stock_category WHERE category_id = " . db_escape($cat_id);
    $get = db_query($sql, "retreive stock repo gl code");
    set_global_connection();
    return db_fetch($get);
}

function add_stock_merchandise_transfer_item_repo($transfer_id, $stock_id, $location_from, $location_to, $date_, $reference, $quantity,$lot_no,$chasis_no, $category_id, $color_code='',$standard_cost=0, $type_out=0, $transno_out=0)
{
    add_stock_move(ST_MERCHANDISETRANSFERREPO, $stock_id, $transfer_id, $location_from,	$date_, $reference, -$quantity,str_replace(",","",$standard_cost),0,$lot_no,$chasis_no, $category_id, $color_code, $type_out, $transno_out, 'repo');
    
}

function add_stock_mt_repo_details($header_id, $stock_id, $quantity=0, $std_cost, $price=0, $lot_no='',$chasis_no='', $color_code='')
{
    global $def_coy;
    set_global_connection($def_coy);
    
    $sql = "INSERT INTO ".TB_PREF."mt_details (
        mt_details_header_id, 
        mt_details_stock_id, 
        mt_details_item_code, 
        mt_details_serial_no, 
        mt_details_chasis_no, 
        mt_details_total_qty, 
        mt_details_st_cost, 
        mt_details_price
        ) VALUES ("
        .db_escape($header_id).","
        .db_escape($stock_id).", "
        .db_escape($color_code).", "
        .db_escape($lot_no).", "
        .db_escape($chasis_no).", "
        .db_escape($quantity).", "
        .db_escape($std_cost).", "
        .db_escape($price).")";
                        
    db_query($sql, "The MT details record cannot be inserted");
                        
    $dbid = db_insert_id();
                        
    set_global_connection();
    return $dbid;
}