<?php
function get_all_stockmoves_repo($start,$limit,$querystr='',$category=0,$branchcode='',$all=false,$reference='', $trans_date=null,$serialquery=null){
    
    $sql = "SELECT SUM(serial.qty) as serialise_qty, serial.stock_id as model, serial.color_code as serialise_item_code,
    serial.lot_no as serialise_lot_no, serial.chassis_no as serialise_chasis_no, serial.loc_code as serialise_loc_code, 
    smaster.description as stock_description, icode.description as item_description, smaster.serialised, serial.type_out,
    serial.transno_out,serial.tran_date, serial.reference, serial.standard_cost, serial.item_type,repo.repo_id, 
    categstock.dflt_repo_invty_act, IFNULL(QTYOUT.QTY, NULL) AS qty_serialise /*Added by Robert */
    FROM ".TB_PREF."stock_moves serial  
    INNER JOIN ".TB_PREF."item_codes icode ON serial.color_code=icode.item_code 
    INNER JOIN ".TB_PREF."stock_master smaster ON icode.stock_id=smaster.stock_id 
    INNER JOIN ".TB_PREF."stock_category categstock ON serial.category_id=categstock.category_id 

    LEFT JOIN (
    SELECT robert.type_out, robert.transno_out, robert.stock_id, robert.lot_no, SUM(robert.qty) AS QTY
    FROM stock_moves robert
    GROUP BY robert.type_out, robert.transno_out, robert.stock_id, robert.lot_no
    )QTYOUT ON QTYOUT.type_out = serial.type_out AND QTYOUT.transno_out =  QTYOUT.transno_out 
    AND QTYOUT.stock_id = serial.stock_id AND QTYOUT.lot_no = serial.lot_no

    LEFT JOIN repo_item_details repo ON serial.stock_id=repo.stock_id AND serial.lot_no=repo.serial_no
    WHERE serial.item_type = 'repo' AND IFNULL(QTYOUT.QTY, 1) > 0"; /*Added by Robert */
    
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
    
    /*if(!$all){
        $sql.=" limit $start,$limit";
    }
    return db_query($sql, "could not get all Serial Items");*/
    $sqllimit = $sql ." limit $start, $limit";

    if ($all) {
        $result = db_query($sql, "could not get all Serial Items");
    }else{
        $result = db_query($sqllimit, "could not get all Serial Items");
    }
    //echo $sql;

    return $result;
}

function add_stock_merchandise_transfer_repo($Items, $location_from, $location_to, $date_, $reference, $memo_,$catcode=0, $rsdno=null, $servedby=null, $repo_id)
{
    global $SysPrefs, $path_to_root, $Refs, $db_connections;
    $company_prefs = get_company_prefs();
    
    begin_transaction();
    $args = func_get_args();
    $args = (object)array_combine(array('Items', 'location_from', 'location_to','date_', 'reference', 'memo_','catcode','rsdno','servedby','repo_id'), $args);
    $args->trans_no = 0;
    
    hook_db_prewrite($args, ST_MERCHANDISETRANSFERREPO);
    
    $transfer_id = get_next_trans_no(ST_MERCHANDISETRANSFERREPO);

    $mcode = $_POST['ToStockLocation'];
    $master_file = get_db_location_name($_POST['ToStockLocation']);
    $coy_target_branches_status_repo = get_coy_target_branch_repo($location_to);
    $lending_type = $db_connections[Get_db_coy($location_from)]["type"];
    
    if ($SysPrefs->loc_notification() == 1)
    {
        include_once($path_to_root . "/inventory/includes/inventory_db.inc");
        $st_ids = array();
        $st_names = array();
        $st_num = array();
        $st_reorder = array();
    }
    $total = 0;

    $mt_header_id_local = add_mt_header_repo($date_,$reference,$location_from,$location_to,$catcode,$memo_,$rsdno,$servedby,$repo_id,
        $location_from);
    if(!empty($company_prefs["deployment_status"])){
        $mt_header_id_target = add_mt_header_repo($date_,$reference,$location_from,$location_to,$catcode,$memo_,$rsdno,$servedby,$repo_id,
        $location_to);
    }elseif($company_prefs["deployment_status"] == '') {
        if($coy_target_branches_status_repo == '') {
            $mt_header_id_target = add_mt_header_repo($date_,$reference,$location_from,$location_to,$catcode,$memo_,$rsdno,$servedby,$repo_id, 
            $location_to);
        }
    }
    
    foreach ($Items as $line_item)
    {
            set_global_connection();
            $stock_gl_mt_code = get_stock_repo_gl_code($line_item->category_id);
            
            $total += add_gl_trans_mtbranch(ST_MERCHANDISETRANSFERREPO, $transfer_id, $date_, $stock_gl_mt_code["dflt_repo_invty_act"], 0, 0,-(str_replace(",","",$line_item->standard_cost)*$line_item->quantity), '', 0, $line_item->stock_id, $mcode, 
                $master_file);
            
            add_stock_merchandise_transfer_item_repo($transfer_id, $line_item->stock_id, $location_from, $location_to, $date_, $reference, $line_item->quantity, $line_item->lot_no, $line_item->chasis_no, $line_item->category_id, $line_item->item_code, $line_item->standard_cost, $line_item->type_out, $line_item->transno_out);
            
            $originating = 0;           
            if(!empty($company_prefs["deployment_status"])){
                if($coy_target_branches_status_repo == '') {        
                    $originating = add_stock_mt_repo_details($mt_header_id_local,$line_item->stock_id,$line_item->quantity,$line_item->quantity,str_replace(",","",$line_item->standard_cost),0,$line_item->lot_no,$line_item->chasis_no,$line_item->item_code, 2, $location_from,$originating);
                }else{
                    $originating = add_stock_mt_repo_details($mt_header_id_local,$line_item->stock_id,$line_item->quantity, 0, str_replace(",","",$line_item->standard_cost),0,$line_item->lot_no,$line_item->chasis_no,$line_item->item_code, 0, $location_from,$originating);
                }
            }elseif($company_prefs["deployment_status"] == '') {
                if($coy_target_branches_status_repo == '') {                  
                    $originating = add_stock_mt_repo_details($mt_header_id_local,$line_item->stock_id,$line_item->quantity, 0, str_replace(",","",$line_item->standard_cost),0,$line_item->lot_no,$line_item->chasis_no,$line_item->item_code, 0, $location_from,$originating);
                }
            }

            if(!empty($company_prefs["deployment_status"])){
                add_stock_mt_repo_details($mt_header_id_target,$line_item->stock_id,$line_item->quantity, 0, str_replace(",","",$line_item->standard_cost),0,$line_item->lot_no,$line_item->chasis_no,$line_item->item_code, 0, $location_to,$originating);
            }elseif($company_prefs["deployment_status"] == '') {
                if($coy_target_branches_status_repo == '') {
                    add_stock_mt_repo_details($mt_header_id_target,$line_item->stock_id,$line_item->quantity, 0, str_replace(",","",$line_item->standard_cost),0,$line_item->lot_no,$line_item->chasis_no,$line_item->item_code, 0, $location_to,$originating);
                }
            }
            //Updating Repo Item Status
            update_repo_status($line_item->transno_out);
    }
    
    add_comments(ST_MERCHANDISETRANSFERREPO, $transfer_id, $date_, $memo_);
    if($lending_type == 'LENDING') {
        $accs = $db_connections[Get_db_coy($location_from)]["gl_account"];
    }else{
        $accs = $db_connections[Get_db_coy($location_to)]["gl_account"];
    }
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
function add_mt_header_repo($transdate, $reference, $fromlocation, $tolocation, $cat_id=0, $comments, $rsdno, $servedby, $repo_id, $targetdb){
    $db_coy = Get_db_coy($targetdb);
    set_global_connection($db_coy);
    
    $trans_date = date2sql($transdate);
    
    $sql = "INSERT INTO ".TB_PREF."mt_header (mt_header_date, mt_header_reference, mt_header_fromlocation, mt_header_tolocation, mt_header_category_id, mt_header_comments, mt_header_rsd, mt_header_servedby, mt_header_item_type, mt_header_repo_account_id) VALUES ('$trans_date', ".db_escape($reference).", ".db_escape($fromlocation).", ".db_escape($tolocation).", '".$cat_id."', ".db_escape($comments).", ".db_escape($rsdno).", ".db_escape($servedby).", 'repo', ".db_escape($repo_id).")";
    
    db_query($sql, "The MT Header Repo record cannot be inserted");
    
    $dbid = db_insert_id();
    
    set_global_connection();
    return $dbid;
}

function get_stock_repo_gl_code($cat_id)
{
    //global $def_coy;
    set_global_connection();
    $sql = "SELECT dflt_repo_invty_act FROM " . TB_PREF . "stock_category WHERE category_id = " . db_escape($cat_id);
    $get = db_query($sql, "retreive stock repo gl code");
    set_global_connection();
    return db_fetch($get);
}

function add_stock_merchandise_transfer_item_repo($transfer_id, $stock_id, $location_from, $location_to, $date_, $reference, $quantity,$lot_no,$chasis_no, $category_id, $color_code='',$standard_cost=0, $type_out=0, $transno_out=0)
{
    add_stock_move(ST_MERCHANDISETRANSFERREPO, $stock_id, $transfer_id, $location_from,	$date_, $reference, -$quantity,str_replace(",","",$standard_cost),0,$lot_no,$chasis_no, $category_id, $color_code, $type_out, $transno_out, 'repo');
    
}

function add_stock_mt_repo_details($header_id, $stock_id, $quantity=0, $received_qty=0, $std_cost, $price=0, $lot_no='',$chasis_no='', $color_code='', 
    $status=0, $targetdb, $originating)
{
    $db_coy = Get_db_coy($targetdb);
    set_global_connection($db_coy);
    
    $sql = "INSERT INTO ".TB_PREF."mt_details (
        mt_details_header_id, 
        mt_details_stock_id, 
        mt_details_item_code, 
        mt_details_serial_no, 
        mt_details_chasis_no, 
        mt_details_total_qty, 
        mt_details_st_cost, 
        mt_details_price,
        mt_details_recvd_qty, 
        mt_details_status,
        originating_id
        ) VALUES ("
        .db_escape($header_id).","
        .db_escape($stock_id).", "
        .db_escape($color_code).", "
        .db_escape($lot_no).", "
        .db_escape($chasis_no).", "
        .db_escape($quantity).", "
        .db_escape($std_cost).", "
        .db_escape($price).", "
        .db_escape($received_qty).", "
        .db_escape($status).", "
        .db_escape($originating).")";
                        
    db_query($sql, "The MT details record cannot be inserted");
                        
    $dbid = db_insert_id();
                        
    set_global_connection();
    return $dbid;
}

/*Added by robert 04/26/2022*/
function get_all_merchandise_repo_transfer($start,$limit,$querystr='',$branchcode='',$search_ref='',$all=false){
    //global $def_coy;
    set_global_connection();

    $sql = "SELECT *, sc.description as category, sum(md.mt_details_total_qty) as totalqty, 
    sum(md.mt_details_total_qty)-sum(md.mt_details_recvd_qty) as balance_total 
    FROM ".TB_PREF."mt_header mh 
    LEFT JOIN ".TB_PREF."mt_details md ON mh.mt_header_id=md.mt_details_header_id 
    LEFT JOIN ".TB_PREF."stock_category sc ON mh.mt_header_category_id=sc.category_id 
    WHERE mh.mt_header_fromlocation='$branchcode' AND mh.mt_header_item_type='repo'
    AND mh.mt_header_reference LIKE '%".$search_ref."%' 
    GROUP BY mh.mt_header_id ORDER BY mh.mt_header_id DESC";

    /*Added by Robert */
    $sqllimit = $sql ." limit $start, $limit";

    if ($all) {
        $result = db_query($sql, "could not get all merchandise repo transfer");
    }else{
        $result = db_query($sqllimit, "could not get all merchandise repo transfer");
    }
    //echo $sql;

    return $result;
}

/*Added by robert 05/27/2022*/
function get_all_complimentary_item_repo($start,$limit,$querystr='',$comp_stat='',$search_ref='',$all=false)
{
    if($comp_stat!=''){
        $str_status=" AND compl.status='".$comp_stat."'";
    }else{
        $str_status="";
    }

    $sql = "SELECT smoves.*,scat.description AS category_name, SUM(smoves.qty) as total_item, 
    loc.location_name AS loc_name, compl.memo_ AS remarks, compl.status AS compli_status
    FROM ".TB_PREF."stock_adjustment smoves 
    INNER JOIN ".TB_PREF."stock_category scat ON smoves.category_id=scat.category_id 
    INNER JOIN ".TB_PREF."locations loc ON smoves.loc_code=loc.loc_code 
    LEFT JOIN ".TB_PREF."complimentary_items compl ON smoves.reference=compl.reference 
    WHERE smoves.type='".ST_COMPLIMENTARYITEMREPO."' AND smoves.item_type = 'repo' $str_status
    AND compl.reference LIKE '%".$search_ref."%'
    GROUP BY smoves.trans_no ORDER BY smoves.tran_date DESC, smoves.trans_id DESC";

    /*Added by Robert */
    $sqllimit = $sql ." limit $start, $limit";

    if ($all) {
        $result = db_query($sql, "could not get all complimentary item repo");
    }else{
        $result = db_query($sqllimit, "could not get all complimentary item repo");
    }
    //echo $sql;

    return $result;
}

//Added by Robert - 05/28/2022
function add_stock_Complimentary_Items_repo($Items, $location_from, $date_, $reference, $memo_,$catcode=0, $person_type=99, $person_id_header='', $masterfile='')
{
    global $SysPrefs, $path_to_root, $Refs, $db_connections;
    
    begin_transaction();
    $args = func_get_args();
    $args = (object)array_combine(array('Items', 'location_from','date_', 'reference', 'memo_','catcode','person_type','person_id_header','masterfile'), $args);
    $args->trans_no = 0;
    hook_db_prewrite($args,ST_COMPLIMENTARYITEMREPO);
    
    $transfer_id = get_next_trans_no(ST_COMPLIMENTARYITEMREPO);
    
    if ($SysPrefs->loc_notification() == 1)
    {
        include_once($path_to_root . "/inventory/includes/inventory_db.inc");
        $st_ids = array();
        $st_names = array();
        $st_num = array();
        $st_reorder = array();
    }
    $total = 0;
    
    $complintary_header_id = add_complimentary_header_repo($date_,$reference,$location_from,$catcode,$person_type, $person_id_header, $masterfile, $memo_);
    
    foreach ($Items as $line_item)
    {
        /*$refline = add_stock_complimentary_item_repo($transfer_id, $line_item->stock_id, $location_from, $date_, $reference, 
        $line_item->quantity, $line_item->lot_no, $line_item->chasis_no, $line_item->category_id, $line_item->item_code, 
        $line_item->standard_cost, $line_item->type_out, $line_item->transno_out);*/

        $refline = add_stock_complimentary_item_repo($transfer_id, $line_item->stock_id, $location_from, $date_, $reference, $line_item->quantity, 
            $line_item->lot_no, $line_item->chasis_no, $line_item->category_id, $line_item->item_code, $line_item->standard_cost, $line_item->type_out, 
            $line_item->transno_out, $memo_);
    }

    add_comments(ST_COMPLIMENTARYITEMREPO, $transfer_id, $date_, $memo_);
    
    $Refs->save(ST_COMPLIMENTARYITEMREPO, $transfer_id, $reference);
    //add_audit_trail(ST_COMPLIMENTARYITEMREPO, $transfer_id, $date_,'');
    
    $args->trans_no = $transfer_id;
    hook_db_postwrite($args, ST_COMPLIMENTARYITEMREPO);
    
    commit_transaction();                    
    return $transfer_id;
}

//Added by Robert - 05/28/2022
function add_complimentary_header_repo($transdate, $reference, $fromlocation, $cat_id=0, $person_type=99, $mcode='', $masterfile='', $memo=''){
    set_global_connection();
    
    $trans_date = date2sql($transdate);
    
    $sql = "INSERT INTO ".TB_PREF."complimentary_items (trans_date, reference, location, category, person_type, mcode, masterfile, item_type, memo_) 
    VALUES ('$trans_date', ".db_escape($reference).", ".db_escape($fromlocation).", '".$cat_id."', ".db_escape($person_type).", ".db_escape($mcode).", 
    ".db_escape($masterfile).", 'repo', ".db_escape($memo).")";
    
    db_query($sql, "The complimentary Header record cannot be inserted");
    
    $dbid = db_insert_id();
    
    set_global_connection();
    return $dbid;
}

//Added by Robert - 05/28/2022 - 10/10/2022
function add_stock_complimentary_item_repo($transfer_id, $stock_id, $location_from,  $date_, $reference, $quantity,$lot_no,$chasis_no, $category_id, $color_code='',
    $standard_cost=0, $type_out, $transno_out, $remarks)
{
    //return add_stock_move(ST_COMPLIMENTARYITEMREPO, $stock_id, $transfer_id, $location_from, $date_, $reference, -$quantity,$standard_cost,0,$lot_no,$chasis_no, $category_id, $color_code, $type_out, $transno_out, 'repo'); 
    return add_stock_adjustment_complimentary(ST_COMPLIMENTARYITEMREPO, $stock_id, $transfer_id, $location_from, $date_, $reference, -$quantity, $standard_cost, 0, 
        $lot_no, $chasis_no, $category_id, $color_code, $type_out, $transno_out, $remarks, 'repo');
}

//Added by Robert - 09/29/2022
function get_coy_target_branch_repo($targetdb){

    $db_coy = Get_db_coy($targetdb);
    set_global_connection($db_coy);
    
    $sql = "SELECT value FROM ".TB_PREF."sys_prefs WHERE name='deployment_status'";
                
    $result = db_query($sql, "The deployment status could not be retrieved");
    $row =  db_fetch($result);

    set_global_connection();        
    return $row[0];
}