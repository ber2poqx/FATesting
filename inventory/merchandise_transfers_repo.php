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
$page_security = 'SA_MERCHANDISETRANSFERREPO';

$path_to_root = "..";
include_once($path_to_root . "/includes/ui/items_cart.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/inventory/includes/stock_transfers_ui.inc");
include_once($path_to_root . "/modules/serial_items/includes/modules_db.inc");
include_once($path_to_root . "/includes/cost_and_pricing.inc");
include_once($path_to_root . "/repossess/includes/repossessed.inc");

add_js_ufile($path_to_root."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_css_file($path_to_root."/css/extjs-default.css");
add_js_ufile($path_to_root.'/js/merchandise_transfers_repo.js');

//--------------------
if(isset($_GET['action']) || isset($_POST['action'])){
    $action = (isset($_GET['action']) ? $_GET['action'] : $_POST['action']);
}else{
    $action=null;
}

if(!is_null($action) || !empty($action)){
    switch ($action) {
        case 'getQOA':
            $model=$_POST['stock_id'];
            $brcode=$_POST['brcode'];
            $demand_qty = get_demand_qty($model, $brcode);
	        $demand_qty += get_demand_asm_qty($model, $brcode);
            $qoaqty=get_qoh_on_date($model,$brcode);
            $qoaqty-=$demand_qty;
            echo '({"qoa":'.$qoaqty.'})';
            exit;
            break;
        case 'NewTransfer';
            $brcode = $db_connections[user_company()]["branch_code"];
            handle_new_order(ST_MERCHANDISETRANSFERREPO);
            echo json_encode(array('AdjDate'=>$_POST['AdjDate'],'branchcode'=>$brcode));
            exit;
            break;
        case 'NewTransferManual';
            $brcode = $db_connections[user_company()]["branch_code"];
            handle_new_order_manual(ST_MERCHANDISETRANSFERREPO);
            echo json_encode(array('AdjDate'=>$_POST['AdjDate'],'branchcode'=>$brcode));
            exit;
            break;
        case 'AddItem';
            $DataOnGrid = stripslashes(html_entity_decode($_REQUEST['DataOnGrid']));
            $objDataGrid = json_decode($DataOnGrid, true);
            //var_dump($objDataGrid);

            foreach($objDataGrid as $value=>$data) 
            {
                //echo $data;
                $serialise_id = $data['serialise_id'];
                $category_id = $data['category'];
                $AdjDate = $data['AdjDate'];
                $model = $data['model'];
                $lot_no = $data['lot_no'];
                $type_out = $data['type_out'];
                $transno_out = $data['transno_out'];
                $serialised = $data['serialised'];
                $rr_date = $data['rr_date'];
                $qty = $data['qty'];
                $repo_id = $data['repo_id'];
                $brcode = $db_connections[user_company()]["branch_code"];
                $_SESSION['transfer_items']->from_loc=$brcode;

                add_to_merchandise_transfer_order($_SESSION['transfer_items'], $model, $serialise_id, $serialised, $type_out, 
                    $transno_out,'repo',$qty, $rr_date);   
            }
            display_transfer_items_serial($_SESSION['transfer_items'], $brcode, $AdjDate, $serialise_id, $repo_id);
            exit;
            break;
        case 'ManualAddItem';
            $category_id = $_REQUEST['category'];
            $AdjDate = $_POST['AdjDate'];
            $model = $_REQUEST['model'];
            $item_code = $_REQUEST['item_code'];
            $serialised = $_REQUEST['serialised'];
            $stock_description = $_REQUEST['stock_description'];
            $item_description = $_REQUEST['item_description'];
            $brcode = $db_connections[user_company()]["branch_code"];
            $_SESSION['transfer_items']->from_loc=$brcode;
            if(!isset($_REQUEST['view'])){
                $line_item_header = rand();
                $line_item = count($_SESSION['transfer_items']->line_items);
                $_SESSION['transfer_items']->add_to_cart($line_item, $model, 1, 0, $stock_description, '0000-00-00','0000-00-00', '', '', $item_description, $item_code, 0, 0, 0,'', 'new', $line_item_header);
            }
            display_transfer_items_serial($_SESSION['transfer_items'], $brcode, $AdjDate, $serialise_id);
            exit;
            break;
        case 'SaveTransfer';
            global $Refs;
            set_global_connection();

            $qty = stripslashes(html_entity_decode($_POST['qty']));
            $objDataGrid = json_decode($qty, true);

            $message="";
            $AdjDate = sql2date($_POST['AdjDate']);

            $counteritem=$_SESSION['transfer_items']->count_items();
            $total_rrdate = $_SESSION['transfer_items']->check_qty_avail_by_rrdate($_POST['AdjDate']);

            $isError = 0;
            foreach($objDataGrid as $value=>$data) {
                $stock_qty = $data['qty'];
                $currentqty = $data['currentqty'];
                $repo_id = $data['repo_id'];

                if($total_rrdate>0){
                    $isError = 1;
                    $message="This document cannot be processed because there is insufficient quantity for items marked.";
                    break;
                }elseif($counteritem<=0){
                    $isError = 1;
                    //echo '({"success":"false","message":"No Item Selected"})';
                    $message="No Item Selected";                     
                    break;
                }elseif($stock_qty == 0) {
                    $isError = 1;
                    $message="Quantity must not be zero.";
                    break;
                }elseif($stock_qty > $currentqty) {
                    $isError = 1;
                    $message = "Sorry, Quantity you entered '".$stock_qty."' is Greater than Available Quantity On Hand: '".$currentqty."'";
                    break;
                }
            }

            if($isError != 1){              
                $AdjDate = sql2date($_POST['AdjDate']);
                $catcode = $_POST['catcode'];
                $_POST['ref']=$Refs->get_next(ST_MERCHANDISETRANSFERREPO, null, array('date'=>$AdjDate, 'location'=> get_post('FromStockLocation')));
                $trans_no = add_stock_merchandise_transfer_repo($_SESSION['transfer_items']->line_items, $_POST['FromStockLocation'], $_POST['ToStockLocation'], $AdjDate, $_POST['ref'], $_POST['memo_'],$catcode, $_POST['rsdno'],$_POST['servedby'], 
                    $repo_id);
            }
            echo '({"success":"true","reference":"'.$_POST['ref'].'","message":"'.$message.'"})';
            exit;
            break;
        case 'SaveManualTransfer';
            global $def_coy;
            set_global_connection($def_coy);
            $message="";
            $AdjDate = sql2date($_POST['AdjDate']);
            $counteritem=$_SESSION['transfer_items']->count_items();
            if($counteritem<=0){
                $message="No Item Selected";
            }else{
                $AdjDate = sql2date($_POST['AdjDate']);
                $catcode = $_POST['catcode'];
                $reference = $_POST['reference'];
                $trans_no = add_stock_merchandise_transfer_manual($_SESSION['transfer_items']->line_items, $_POST['FromStockLocation'], $_POST['ToStockLocation'], $AdjDate, $reference, $_POST['memo_'], $catcode, $_POST['rsdno'],$_POST['servedby']);
            }
            echo '({"success":"true","reference":"'.$_POST['ref'].'","message":"'.$message.'"})';
            exit;
            break;
        case 'receive_header';
            $brcode = $db_connections[user_company()]["branch_code"];
            $from_loc = $_POST['from_loc'];
            $from_loc_code = $_POST['from_loc_code'];
            $MTReference = $_POST['MTreference'];
            $trans_id = $_POST['trans_id'];
            handle_new_order(ST_RRBRANCH);
            $AdjDate = sql2date($_POST['AdjDate']);
            $_POST['ref']=$Refs->get_next(ST_RRBRANCH, null, array('date'=>$_POST['AdjDate'], 'location'=> $brcode));

            global $def_coy;
            set_global_connection($def_coy);
            $sql = "SELECT *, sc.description as category, md.mt_details_total_qty as totalqty, ic.description as item_description, sm.description as stock_description, md.mt_details_st_cost FROM ".TB_PREF."mt_header mh LEFT JOIN ".TB_PREF."mt_details md ON mh.mt_header_id=md.mt_details_header_id LEFT JOIN ".TB_PREF."stock_category sc ON mh.mt_header_category_id=sc.category_id LEFT JOIN item_codes ic ON md.mt_details_item_code=ic.item_code LEFT JOIN stock_master sm ON md.mt_details_stock_id=sm.stock_id WHERE mh.mt_header_id=$trans_id";
            
            $result = db_query($sql, "could not get all Serial Items");
            $total = db_num_rows($result);
            while ($myrow = db_fetch($result))
            {
                if($myrow["mt_details_status"]==0){
                    $status_msg='In-transit';
                }elseif($myrow["mt_details_status"]==1){
                    $status_msg='Partial';
                }elseif($myrow["mt_details_status"]==2){
                    $status_msg='Received';
                }
                $countrec=count($_SESSION['transfer_items']->line_items);
                $_SESSION['transfer_items']->add_to_cart($countrec, $myrow["mt_details_stock_id"], $myrow["totalqty"], $myrow["mt_details_st_cost"], $myrow['stock_description'],'0000-00-00','0000-00-00', $myrow["mt_details_serial_no"], $myrow["mt_details_chasis_no"], $myrow['item_description'], $myrow["mt_details_item_code"], $myrow["mt_details_id"]);
            }
            
            echo '({"AdjDate":"'.$_POST['AdjDate'].'","branchcode":"'.$brcode.'","from_loc":"'.$from_loc.'","RRBRReference":"'.$_POST['ref'].'","MTreference":"'.$MTReference.'","Record Count":"'.count($_SESSION['transfer_items']->line_items).'","total":"'.$total.'","from_loc_code":"'.$from_loc_code.'"})';
            exit;
            break;
        case 'receive';
            global $def_coy;
            set_global_connection($def_coy);
            
            $trans_id = $_POST['trans_id'];
            $line_item = $_POST['line_item'];
            $reference = $_POST['reference'];
            $qty = $_POST['qty'];
            $brcode = $db_connections[user_company()]["branch_code"];
            $_POST['ref'] = $_POST['RRBRReference'];
            $model = $_POST['model'];
            $item_code = $_POST['item_code'];
            $serialise_id = $_POST['engine_no'];
            $engine_no = $_POST['engine_no'];
            $chasis_no = $_POST['chasis_no'];
            $recline = $_POST['reclines'];
            $standard_cost = str_replace(',','',$_POST['standard_cost']);
            $sql="SELECT icode.*, iserial.*, item.units, item.description as sdescription, icode.description as idescription FROM ".TB_PREF."item_codes icode LEFT JOIN ".TB_PREF."stock_master item ON item.stock_id=icode.stock_id LEFT JOIN ".TB_PREF."mt_details iserial ON icode.item_code=iserial.mt_details_item_code WHERE iserial.mt_details_id=".$line_item." LIMIT 1";
                        
            $result = db_query($sql,"MT item could not be retrieved");
            $myrow = db_fetch($result);
            
            $countrec=count($_SESSION['transfer_items']->line_items);
            $_SESSION['transfer_items']->add_to_cart($recline, $model, $qty,$standard_cost,$myrow['sdescription'],'0000-00-00','0000-00-00', $engine_no, $chasis_no, $myrow['idescription'], $item_code, $line_item);
            echo '({"countrecord":"'.$recline.'","lineitem":"'.$line_item.'"})';
            exit;
            break;
        case 'receive_item';
            global $def_coy;
            set_global_connection($def_coy);
            
            $trans_id = $_POST['trans_id'];
            $line_item = $_POST['line_item'];
            $reference = $_POST['reference'];
            $qty = $_POST['qty'];
            $brcode = $db_connections[user_company()]["branch_code"];
            $_POST['ref'] = $_POST['RRBRReference'];
            $model = $_POST['model'];
            $item_code = $_POST['item_code'];
            $serialise_id = $_POST['engine_no'];
            $engine_no = $_POST['engine_no'];
            $chasis_no = $_POST['chasis_no'];
            $recline = $_POST['reclines'];
            $standard_cost = str_replace(',','',$_POST['standard_cost']);
            $sql="SELECT icode.*, iserial.*, item.units, item.description as sdescription, icode.description as idescription FROM ".TB_PREF."item_codes icode LEFT JOIN ".TB_PREF."stock_master item ON item.stock_id=icode.stock_id LEFT JOIN ".TB_PREF."mt_details iserial ON icode.item_code=iserial.mt_details_item_code WHERE iserial.mt_details_id=".$line_item." LIMIT 1";
            
            $result = db_query($sql,"MT item could not be retrieved");
            $myrow = db_fetch($result);
            
            //$standard_cost = $myrow['mt_details_st_cost'];
            $countrec=count($_SESSION['transfer_items']->line_items);
            $_SESSION['transfer_items']->add_to_cart($countrec, $model, $qty,$standard_cost,$myrow['sdescription'],'0000-00-00','0000-00-00', $engine_no, $chasis_no, $myrow['idescription'], $item_code, $line_item);
            
            echo '({"countrecord":"'.$recline.'","lineitem":"'.$line_item.'"})';
            exit;
            break;
        case 'readData';
            echo '({"success":true,"Read":"'.$brcode.'","branch_name":"'.$brname.'"})';
            exit;
            break;
        case 'updateData';
            $arrayremove = array("[","]");
            $onlyconsonants = str_replace($arrayremove, "", html_entity_decode($_REQUEST['dataUpdate']));
            $filter = json_decode($onlyconsonants, true);
            $_SESSION['transfer_items']->update_cart_item($filter['id'], $filter['qty'],$filter['standard_cost'],'0000-00-00','0000-00-00',$filter['lot_no'],$filter['chasis_no']);


            echo '({"success":true,"Update":"","id":"'.$filter['id'].'","qty":"'.$filter['qty'].'"})';
            exit;
            break;
        case 'ManualupdateData';
            $arrayremove = array("[","]");
            $onlyconsonants = str_replace($arrayremove, "", html_entity_decode($_REQUEST['dataManualUpdate']));
            $filter = json_decode($onlyconsonants, true);
            $_SESSION['transfer_items']->update_cart_item($filter['id'], $filter['qty'],$filter['standard_cost'],'0000-00-00','0000-00-00',$filter['lot_no'], $filter['chasis_no'], $filter['color']);
        
        
            echo '({"success":true,"Update":"","id":"'.$filter['id'].'","qty":"'.$filter['qty'].'"})';
            exit;
        break;
        case 'save_rrbr';
            $brcode = $db_connections[user_company()]["branch_code"];
            
            set_global_connection();
            $AdjDate = sql2date($_POST['trans_date']);
            //$_POST['ref']=$_POST['RRBRReference'];
            $MTreference=$_POST['MTreference'];
            $from_loc_code=$_POST['from_loc_code'];
            

            $catcode = $_POST['catcode'];

            $_POST['ref']=$Refs->get_next(ST_RRBRANCH, null, array('date'=>$AdjDate, 'location'=> $brcode));
            $totalline=count($_SESSION['transfer_items']->line_items);
            $trans_no = add_stock_rrBranch($_SESSION['transfer_items']->line_items,$from_loc_code, $_POST['ToStockLocation'], $AdjDate, $_POST['ref'], '', $catcode, $MTreference);
            //new_doc_date($AdjDate);
            $_SESSION['transfer_items']->clear_items();
            unset($_SESSION['transfer_items']);
            echo '({"total":"'.$totalline.'","reference":"'.$_POST['ref'].'"})';
            exit;
            break;

        case 'RemoveItem';
            $id = $_REQUEST['id'];
            $serialise_id = $_REQUEST['serialise_id'];
            $AdjDate = $_POST['AdjDate'];
            $model = $_REQUEST['model'];
            $brcode = $db_connections[user_company()]["branch_code"];
            $_SESSION['transfer_items']->remove_from_cart($id);
            display_transfer_items_serial($_SESSION['transfer_items'],$brcode,$AdjDate,$serialise_id);
            exit;
            break;

        case 'getConfig':
            $brcode = $db_connections[user_company()]["branch_code"];
            $brname = $db_connections[user_company()]["name"];
            echo '({"branchcode":"'.$brcode.'","branch_name":"'.$brname.'"})';
            exit;
            break;
        
        case 'getCountItem':
            $counteritem=$_SESSION['transfer_items']->count_items();
            echo '({"success":"true","countitem":"'.$counteritem.'"})';
            exit;
            break;
            
        case 'serial_items':
            $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
            $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
            $catcode = (integer) (isset($_POST['catcode']) ? $_POST['catcode'] : $_GET['catcode']);
            $branchcode = (isset($_POST['branchcode']) ? $_POST['branchcode'] : $_GET['branchcode']);
            $trans_date = (isset($_POST['trans_date']) ? $_POST['trans_date'] : $_GET['trans_date']);
            $querystr = (isset($_POST['query']) ? $_POST['query'] : $_GET['query']);
            $querystrserial = (isset($_POST['serialquery']) ? $_POST['serialquery'] : $_GET['serialquery']);
            
            //if($start < 1)	$start = 0;	if($end < 1) $end = 25;

            $result = get_all_stockmoves_repo($start,$limit,$querystr,$catcode,$branchcode,false,'',$trans_date, $querystrserial);
            $total_result = get_all_stockmoves_repo($start,$limit,$querystr,$catcode,$branchcode,true,'',$trans_date, $querystrserial);
            $total = db_num_rows($total_result);
            while ($myrow = db_fetch($result))
            {
                if($myrow["serialised"]){
                   $qty=$myrow["serialise_qty"];
                }else{
                   $demand_qty = get_demand_qty($myrow["model"], $branchcode);
	               $demand_qty += get_demand_asm_qty($myrow["model"], $branchcode);
	               $qty=get_qoh_on_date_new($myrow["type_out"], $myrow["transno_out"], $myrow["model"], $branchcode, sql2date($trans_date));
                   $qty-=$demand_qty;
                }
                if($qty>0){
                    $serialise_id = get_serialise_id($myrow["serialise_item_code"],$myrow["serialise_lot_no"]);
                    $counteritem=$_SESSION['transfer_items']->find_cart_item_new($myrow["serialise_item_code"],$myrow["serialise_lot_no"]);
                    if(!$counteritem){
                        $group_array[] = array('serialise_id'=>$serialise_id,
                            'type_out' => $myrow["type_out"],
                            'transno_out' => $myrow["transno_out"],
                            'reference' => $myrow["reference"],
                            'tran_date' => $myrow["tran_date"],
                            'color' => $myrow["serialise_item_code"],
                            'stock_description' => $myrow["stock_description"],
                            'item_description' => $myrow["item_description"],
                            'model' => $myrow["model"],
                            'standard_cost' => number_format2($myrow["standard_cost"],2),
                            'qty' => $qty,
                            'category_id'=>$catcode,
                            'serialised'=>$myrow["serialised"],
                            'lot_no' => $myrow["serialise_lot_no"]==null?'':$myrow["serialise_lot_no"],
                            'chasis_no' => $myrow["serialise_chasis_no"]==null?'':$myrow["serialise_chasis_no"],
                            'serialise_loc_code'=>$myrow["serialise_loc_code"],
                            'repo_id'=>$myrow["repo_id"]                           
                        );
                    }
                }
            }
            
            $jsonresult = json_encode($group_array);
            echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
            exit;
            break;
        case 'items_listing':
            set_global_connection();
            
            $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
            $end = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
            $catcode = (integer) (isset($_POST['catcode']) ? $_POST['catcode'] : $_GET['catcode']);
            $brand = (integer) (isset($_POST['brand']) ? $_POST['brand'] : $_GET['brand']);
            $branchcode = (isset($_POST['branchcode']) ? $_POST['branchcode'] : $_GET['branchcode']);
            $trans_date = (isset($_POST['trans_date']) ? $_POST['trans_date'] : $_GET['trans_date']);
            $querystr = (isset($_POST['query']) ? $_POST['query'] : $_GET['query']);
            $querystrserial = (isset($_POST['serialquery']) ? $_POST['serialquery'] : $_GET['serialquery']);
            if($start < 1)	$start = 0;	if($end < 1) $end = 25;
            
            $result = get_all_items_listing($start,$end,$querystr,$catcode,$branchcode,false,'',$trans_date, $querystrserial, $brand);
            $total_result = get_all_items_listing($start,$end,$querystr,$catcode,$branchcode,true,'',$trans_date, $querystrserial, $brand);
            $total = db_num_rows($total_result);
            while ($myrow = db_fetch($result))
            {
                
             
                    $group_array[] = array(
                        'item_code' => $myrow["item_code"],
                        'stock_description' => $myrow["stock_description"],
                        'item_description' => $myrow["item_description"],
                        'color' => $myrow["color"],
                        'model' => $myrow["model"],
                        'category_id'=>$catcode,
                        'serialised'=>$myrow["serialised"],
                        'brand_name' => $myrow["brand_name"],
                        'brand_id' => $myrow["brand_id"]
                    );
                
                
                
            }
            
            $jsonresult = json_encode($group_array);
            echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
            exit;
            break;
        case 'MTserialitems':
            global $def_coy;
            set_global_connection($def_coy);
            
            $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
            $end = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
            $catcode = (integer) (isset($_POST['catcode']) ? $_POST['catcode'] : $_GET['catcode']);
            $reference = (isset($_POST['reference']) ? $_POST['reference'] : $_GET['reference']);
            $querystr = (isset($_POST['query']) ? $_POST['query'] : $_GET['query']);
            $trans_id = (integer) (isset($_POST['trans_id']) ? $_POST['trans_id'] : $_GET['trans_id']);
            $brcode = $db_connections[user_company()]["branch_code"];

            if($start < 1)	$start = 0;	if($end < 1) $end = 25;

            
            $sql = "SELECT *, sc.description as category, md.mt_details_status, md.mt_details_total_qty as totalqty, ic.description as item_description, sm.description as stock_description FROM ".TB_PREF."mt_header mh LEFT JOIN ".TB_PREF."mt_details md ON mh.mt_header_id=md.mt_details_header_id LEFT JOIN ".TB_PREF."stock_category sc ON mh.mt_header_category_id=sc.category_id LEFT JOIN item_codes ic ON md.mt_details_item_code=ic.item_code LEFT JOIN stock_master sm ON md.mt_details_stock_id=sm.stock_id WHERE mh.mt_header_id=$trans_id";

            
            if($catcode!=0){
                $sql.=" AND mh.mt_header_category_id=$catcode";
            }
            if($reference!=''){
                $sql.=" AND mh.mt_header_reference='".$reference."'";
                
            }
            if($brcode!=''){
                $sql.=" AND mh.mt_header_fromlocation='".$brcode."'";
                
            }
            
            $result=db_query($sql, "could not get all Serial Items");
            
            $total = db_num_rows($result);
            
            
            while ($myrow = db_fetch($result))
            {
                if($myrow["mt_details_status"]==0){
                    $status_msg='In-transit';
                }elseif($myrow["mt_details_status"]==1){
                    $status_msg='Partial';
                }elseif($myrow["mt_details_status"]==2){
                    $status_msg='Received';
                }

                $group_array[] = array('serialise_id'=>$serialise_id,
                    'color' => $myrow["serialise_item_code"],
                    'stock_description' => $myrow["stock_description"],
                    'item_description' => $myrow["item_description"],
                    'model' => $myrow["mt_details_stock_id"],
                    'standard_cost' => number_format($myrow["standard_cost"],2),
                    'qty' => number_format($myrow["mt_details_total_qty"],2),
                    'category_id'=>$catcode,
                    'lot_no' => $myrow["mt_details_serial_no"],
                    'chasis_no' => $myrow["mt_details_chasis_no"],
                    'serialise_loc_code'=>$myrow["serialise_loc_code"],
                    'status_msg'=>$status_msg

                );
                
            }
            
            $jsonresult = json_encode($group_array);
            echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
            exit;
            break;
        case 'BRMTserialitems':
            global $def_coy;
            set_global_connection($def_coy);
            
            $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
            $end = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
            $catcode = (integer) (isset($_POST['catcode']) ? $_POST['catcode'] : $_GET['catcode']);
            $trans_id = (integer) (isset($_POST['trans_id']) ? $_POST['trans_id'] : $_GET['trans_id']);
            $reference = (integer) (isset($_POST['reference']) ? $_POST['reference'] : $_GET['reference']);
            $branchcode = (isset($_POST['branchcode']) ? $_POST['branchcode'] : $_GET['branchcode']);
            $querystr = (isset($_POST['query']) ? $_POST['query'] : $_GET['query']);
            
            if($start < 1)	$start = 0;	if($end < 1) $end = 25;
            
            $sql = "SELECT *, sc.description as category, md.mt_details_total_qty as totalqty, ic.description as item_description, sm.description as stock_description, md.mt_details_st_cost FROM ".TB_PREF."mt_header mh LEFT JOIN ".TB_PREF."mt_details md ON mh.mt_header_id=md.mt_details_header_id LEFT JOIN ".TB_PREF."stock_category sc ON mh.mt_header_category_id=sc.category_id LEFT JOIN item_codes ic ON md.mt_details_item_code=ic.item_code LEFT JOIN stock_master sm ON md.mt_details_stock_id=sm.stock_id WHERE mh.mt_header_id=$trans_id";
            
            
            $result = db_query($sql, "could not get all Serial Items");
            while ($myrow = db_fetch($result))
            {
                if($myrow["mt_details_status"]==0){
                    $status_msg='In-transit';
                }elseif($myrow["mt_details_status"]==1){
                    $status_msg='Partial';
                }elseif($myrow["mt_details_status"]==2){
                    $status_msg='Received';
                }
                $group_array[] = array('trans_id'=>$myrow["mt_header_id"],
                    'line_item' => $myrow["mt_details_id"],
                    'reference' => $myrow["mt_header_reference"],
                    'rrbrreference' => $myrow["mt_header_rrbranch_reference"],
                    'model' => $myrow["mt_details_stock_id"],
                    'item_code' => $myrow["mt_details_item_code"],
                    'trans_date' => sql2date($myrow["mt_header_date"]),
                    'category' => $myrow["category"],
                    'qty' => number_format($myrow["totalqty"],2),
                    'category_id'=>$catcode,
                    'lot_no' => $myrow["mt_details_serial_no"],
                    'chasis_no' => $myrow["mt_details_chasis_no"],
                    'standard_cost' => number_format2($myrow["mt_details_st_cost"],2),
                    'stock_description' => $myrow["stock_description"],
                    'item_description' => $myrow["item_description"],
                    'status_msg'=>$status_msg,
                    'status'=>$myrow["mt_details_status"]
                );
                
            }
            
            $jsonresult = json_encode($group_array);
            echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
            exit;
            break;
            
        case 'view':
            //global $def_coy;
            //set_global_connection($def_coy);
            
            $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
            $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
            $catcode = (integer) (isset($_POST['catcode']) ? $_POST['catcode'] : $_GET['catcode']);
            $branchcode = (isset($_POST['branchcode']) ? $_POST['branchcode'] : $_GET['branchcode']);
            $querystr = (isset($_POST['query']) ? $_POST['query'] : $_GET['query']);
            
            //if($start < 1)	$start = 0;	if($end < 1) $end = 25;
            
                       
            /*$sql = "SELECT *, sc.description as category, sum(md.mt_details_total_qty) as totalqty,sum(md.mt_details_total_qty)-sum(md.mt_details_recvd_qty) as balance_total FROM ".TB_PREF."mt_header mh LEFT JOIN ".TB_PREF."mt_details md ON mh.mt_header_id=md.mt_details_header_id LEFT JOIN ".TB_PREF."stock_category sc ON mh.mt_header_category_id=sc.category_id WHERE mh.mt_header_fromlocation='$branchcode' AND mh.mt_header_item_type='repo' GROUP BY mh.mt_header_id ORDER BY mh.mt_header_id DESC";*/

            $result = get_all_merchandise_repo_transfer($start,$limit,$querystr,$branchcode,false,'');
            $total_result = get_all_merchandise_repo_transfer($start,$limit,$querystr,$branchcode,true,'');

            $total = DB_num_rows($result);

            while ($myrow = db_fetch($result))
            {
                if($myrow["balance_total"]==0){
                    $status='Received';
                }else{
                    $status='In-Transit';
                }
                $group_array[] = array('trans_id'=>$myrow["mt_header_id"],
                    'reference' => $myrow["mt_header_reference"],
                    'tran_date' => sql2date($myrow["mt_header_date"]),
                    'loc_code' => $myrow["mt_header_tolocation"],
                    'loc_name' => get_db_location_name($myrow["mt_header_tolocation"]),
                    'remarks' => $myrow["mt_header_comments"],
                    'category' => $myrow["category"],
                    'qty' => number_format($myrow["totalqty"],2),
                    'category_id'=>$myrow["mt_header_category_id"],
                    'lot_no' => $myrow["serialise_lot_no"],
                    'chasis_no' => $myrow["serialise_chasis_no"],
                    'serialise_loc_code'=>$myrow["serialise_loc_code"],
                    'status'=>$status
                );
                
            }
            
            $jsonresult = json_encode($group_array);
            echo '({"total":"'.DB_num_rows($total_result).'","result":'.$jsonresult.'})';
            exit;
            break;
        case 'viewin':
            global $def_coy;
            set_global_connection($def_coy);
            
            $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
            $end = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
            $catcode = (integer) (isset($_POST['catcode']) ? $_POST['catcode'] : $_GET['catcode']);
            //$branchcode = (isset($_POST['branchcode']) ? $_POST['branchcode'] : $_GET['branchcode']);
            $fromlocation = (isset($_POST['fromlocation']) ? $_POST['fromlocation'] : $_GET['fromlocation']);
            $querystr = (isset($_POST['query']) ? $_POST['query'] : $_GET['query']);
            $branchcode = $db_connections[user_company()]["branch_code"];
            
            if($start < 1)	$start = 0;	if($end < 1) $end = 25;
            
            //$brcode = $db_connections[user_company()]["branch_code"];
            if($fromlocation!=null || isset($fromlocation)){
                $str_fromlocation=" AND  mh.mt_header_fromlocation='".$fromlocation."'";
            }else{
                $str_fromlocation="";
            }
            $sql = "SELECT *, sc.description as category, sum(md.mt_details_total_qty) as totalqty,sum(md.mt_details_total_qty)-sum(md.mt_details_recvd_qty) as balance_qty FROM ".TB_PREF."mt_header mh LEFT JOIN ".TB_PREF."mt_details md ON mh.mt_header_id=md.mt_details_header_id LEFT JOIN ".TB_PREF."stock_category sc ON mh.mt_header_category_id=sc.category_id where mh.mt_header_tolocation='$branchcode' $str_fromlocation GROUP BY mh.mt_header_reference ORDER BY mh.mt_header_date, mh.mt_header_id DESC";
            
            
            $result = db_query($sql, "could not get all Serial Items");
            //$total_result = get_all_serial($start,$end,$querystr,$catcode,$branchcode,true);
            //$total = db_num_rows($total_result);
            while ($myrow = db_fetch($result))
            {
                if($myrow["balance_qty"]==0){
                   $status_msg='Received'; 
                }else{
                    $status_msg='In-transit';
                }
                $group_array[] = array('trans_id'=>$myrow["mt_header_id"],
                    'reference' => $myrow["mt_header_reference"],
                    'rrbrreference' => $myrow["mt_header_rrbranch_reference"],
                    'trans_date' => sql2date($myrow["mt_header_date"]),
                    'fromlocation' => get_db_location_name($myrow["mt_header_fromlocation"]),
                    'from_loc' => $myrow["mt_header_fromlocation"],
                    'tolocation' => $myrow["mt_header_tolocation"],
                    'category' => $myrow["category"],
                    'qty' => number_format($myrow["totalqty"],2),
                    'category_id'=>$myrow["mt_header_category_id"],
                    'lot_no' => $myrow["serialise_lot_no"],
                    'chasis_no' => $myrow["serialise_chasis_no"],
                    'serialise_loc_code'=>$myrow["serialise_loc_code"],
                    'remarks' => $myrow["mt_header_comments"],
                    'status_msg'=>$status_msg,
                    'status'=>$myrow["mt_header_status"]
                );
                
            }
            
            $jsonresult = json_encode($group_array);
            echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
            exit;
            break;
            
        case 'category':
            $result = get_item_categories(true, false);
            $total = db_num_rows($result);
            while ($myrow = db_fetch($result))
            {
                
                $group_array[] = array('category_id'=>$myrow["category_id"],
                    'description' => $myrow["description"]
                );
            }
            
            $jsonresult = json_encode($group_array);
            echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
            
            exit;
            break;
        case 'brand':
            $category_id = $_REQUEST['category_id'];
            $sql = "SELECT ib.id as brand_id,ib.name as brand_name FROM ".TB_PREF."stock_master sm LEFT JOIN ".TB_PREF."item_brand ib ON sm.brand=ib.id WHERE NOT sm.inactive AND sm.category_id=".db_escape($category_id)." GROUP BY sm.brand ORDER BY ib.name";
            
            
            $result = db_query($sql, "could not get all Brand Items");
            $total = db_num_rows($result);
            while ($myrow = db_fetch($result))
            {
                
                $group_array[] = array('brand_id'=>$myrow["brand_id"],
                    'brand_name' => $myrow["brand_name"]
                );
            }
            
            $jsonresult = json_encode($group_array);
            echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
            
            exit;
            break;
        case 'location':
            $brcode = $db_connections[user_company()]["branch_code"];
            $result = get_item_locations(check_value('show_inactive'), get_post('fixed_asset', 0));
            $total = db_num_rows($result);
            while ($myrow = db_fetch($result))
            {
                if($myrow["loc_code"]!=$brcode){
                    $group_array[] = array('loc_code'=>$myrow["loc_code"],
                        'location_name' => $myrow["location_name"],
                        'delivery_address' => $myrow["delivery_address"],
                        'phone' => $myrow["phone"],
                        'phone2'=>$myrow["phone2"]
                    );
                }
                
            }
            
            $jsonresult = json_encode($group_array);
            echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
            
            exit;
            break;
        case 'branch_location':
            $coy = user_company();
            
            for ($i = 0; $i < count($db_connections); $i++)
            {
                if($i!=$coy){
                    $group_array[] = array('loc_code'=>$db_connections[$i]["branch_code"],
                        'location_name' => $db_connections[$i]["name"],
                        'branch_id' => $i
                    );
                }
                
                
            }
            
            $jsonresult = json_encode($group_array);
            echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
            
            exit;
            break;
    }
}

/*----Added by Robert 02/26/2022*/
if(isset($_GET['get_userLogin']))
{
    $user_role = check_user_role($_SESSION["wa_current_user"]->username);

    $servedby = $_SESSION["wa_current_user"]->name;

    echo '({"success":"true","servedby":"'.$servedby.'"})';
    return;
}
/*----------End Here--------*/

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(800, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

if (isset($_GET['NewTransfer'])) {
	if (isset($_GET['FixedAsset'])) {
		$page_security = 'SA_ASSETTRANSFER';
		$_SESSION['page_title'] = _($help_context = "Fixed Assets Location Transfers");
	}
	else {
		$_SESSION['page_title'] = _($help_context = "Merchandise Transfers Listing");
	}
}else{
    $_SESSION['page_title']="";
}
//page($_SESSION['page_title'], false, false, "", "",false);



page($_SESSION['page_title']);

//-----------------------------------------------------------------------------------------------

check_db_has_costable_items(_("There are no inventory items defined in the system (Purchased or manufactured items)."));

//-----------------------------------------------------------------------------------------------

if (isset($_GET['AddedID'])) 
{
	$trans_no = $_GET['AddedID'];
	$trans_type = ST_MERCHANDISETRANSFER;

	display_notification_centered(_("Inventory transfer has been processed"));
	display_note(get_trans_view_str($trans_type, $trans_no, _("&View this transfer")));

  $itm = db_fetch(get_stock_transfer_items($_GET['AddedID']));

  if (is_fixed_asset($itm['mb_flag']))
	  hyperlink_params($_SERVER['PHP_SELF'], _("Enter &Another Fixed Assets Transfer"), "NewTransfer=1&FixedAsset=1");
  else
	  hyperlink_params($_SERVER['PHP_SELF'], _("Enter &Another Inventory Transfer"), "NewTransfer=1");

	display_footer_exit();
}
//--------------------------------------------------------------------------------------------------

function line_start_focus() {
  global 	$Ajax;

  $Ajax->activate('items_table');
  set_focus('_stock_id_edit');
}
//-----------------------------------------------------------------------------------------------

function handle_new_order($transtype=ST_MERCHANDISETRANSFER)
{
	if (isset($_SESSION['transfer_items']))
	{
		unset ($_SESSION['transfer_items']);
	}

	$_SESSION['transfer_items'] = new items_cart($transtype);
	$_SESSION['transfer_items']->clear_items();
	//$_SESSION['transfer_items']->fixed_asset = isset($_GET['FixedAsset']);
	$_POST['AdjDate'] = new_doc_date();
	if (!is_date_in_fiscalyear($_POST['AdjDate']))
		$_POST['AdjDate'] = end_fiscalyear();
	$_SESSION['transfer_items']->tran_date = $_POST['AdjDate'];
	
}
function handle_new_order_manual($transtype=ST_MERCHANDISETRANSFER)
{
    
    if (isset($_SESSION['transfer_items']))
    {
        unset ($_SESSION['transfer_items']);
    }
    
    $_SESSION['transfer_items'] = new items_cart($transtype);
    $_SESSION['transfer_items']->clear_items();
    //$_SESSION['transfer_items']->fixed_asset = isset($_GET['FixedAsset']);
    $_POST['AdjDate'] = new_doc_date();
    if (!is_date_in_fiscalyear($_POST['AdjDate']))
        $_POST['AdjDate'] = end_fiscalyear();
    $_SESSION['transfer_items']->tran_date = $_POST['AdjDate'];
        
}

//-----------------------------------------------------------------------------------------------

if (isset($_POST['Process']))
{

	$tr = &$_SESSION['transfer_items'];
	$input_error = 0;

	if (count($tr->line_items) == 0)	{
		display_error(_("You must enter at least one non empty item line."));
		set_focus('stock_id');
		$input_error = 1;
	}
	if (!check_reference($_POST['ref'], ST_MERCHANDISETRANSFER))
	{
		set_focus('ref');
		$input_error = 1;
	} 
	elseif (!is_date($_POST['AdjDate'])) 
	{
		display_error(_("The entered transfer date is invalid."));
		set_focus('AdjDate');
		$input_error = 1;
	} 
	elseif (!is_date_in_fiscalyear($_POST['AdjDate'])) 
	{
		display_error(_("The entered date is out of fiscal year or is closed for further data entry."));
		set_focus('AdjDate');
		$input_error = 1;
	} 
	elseif ($_POST['FromStockLocation'] == $_POST['ToStockLocation'])
	{
		display_error(_("The locations to transfer from and to must be different."));
		set_focus('FromStockLocation');
		$input_error = 1;
	}
	elseif (!$SysPrefs->allow_negative_stock())
	{
		$low_stock = $tr->check_qoh($_POST['FromStockLocation'], $_POST['AdjDate'], true);

		if ($low_stock)
		{
    		display_error(_("The transfer cannot be processed because it would cause negative inventory balance in source location for marked items as of document date or later."));
			$input_error = 1;
		}
	}

	if ($input_error == 1)
		unset($_POST['Process']);
}

//-------------------------------------------------------------------------------

if (isset($_POST['Process']))
{

	$trans_no = add_stock_transfer($_SESSION['transfer_items']->line_items,
		$_POST['FromStockLocation'], $_POST['ToStockLocation'],
		$_POST['AdjDate'], $_POST['ref'], $_POST['memo_']);
	new_doc_date($_POST['AdjDate']);
	$_SESSION['transfer_items']->clear_items();
	unset($_SESSION['transfer_items']);

   	meta_forward($_SERVER['PHP_SELF'], "AddedID=$trans_no");
} /*end of process credit note */

//-----------------------------------------------------------------------------------------------

function check_item_data()
{
	if (!check_num('qty', 0) || input_num('qty') == 0)
	{
		display_error(_("The quantity entered must be a positive number."));
		set_focus('qty');
		return false;
	}
   	return true;
}

//-----------------------------------------------------------------------------------------------

function handle_update_item()
{
	$id = $_POST['LineNo'];
   	if (!isset($_POST['std_cost']))
   		$_POST['std_cost'] = $_SESSION['transfer_items']->line_items[$id]->standard_cost;
   	$_SESSION['transfer_items']->update_cart_item($id, input_num('qty'), $_POST['std_cost']);
	line_start_focus();
}

//-----------------------------------------------------------------------------------------------

function handle_delete_item($id)
{
	$_SESSION['transfer_items']->remove_from_cart($id);
	line_start_focus();
}

//-----------------------------------------------------------------------------------------------

function handle_new_item()
{
	if (!isset($_POST['std_cost']))
   		$_POST['std_cost'] = 0;
	add_to_order($_SESSION['transfer_items'], $_POST['stock_id'], input_num('qty'), $_POST['std_cost']);
	line_start_focus();
}

//-----------------------------------------------------------------------------------------------
$id = find_submit('Delete');
if ($id != -1)
	handle_delete_item($id);
	
if (isset($_POST['AddItem']) && check_item_data())
	handle_new_item();

if (isset($_POST['UpdateItem']) && check_item_data())
	handle_update_item();

if (isset($_POST['CancelItemChanges'])) {
	line_start_focus();
}
//-----------------------------------------------------------------------------------------------

if (isset($_GET['NewTransfer']) || !isset($_SESSION['transfer_items']))
{
	if (isset($_GET['fixed_asset']))
		check_db_has_disposable_fixed_assets(_("There are no fixed assets defined in the system."));
	else
		check_db_has_costable_items(_("There are no inventory items defined in the system (Purchased or manufactured items)."));

	//handle_new_order();
}


//-----------------------------------------------------------------------------------------------


echo "<div id='merchandisetransfer-grid' style='padding:15px'></div>";

/*start_form();

display_order_header($_SESSION['transfer_items']);

start_table(TABLESTYLE, "width='70%'", 10);
start_row();
echo "<td>";
display_transfer_items(_("Items"), $_SESSION['transfer_items']);
transfer_options_controls();
echo "</td>";
end_row();
end_table(1);

submit_center_first('Update', _("Update"), '', null);
submit_center_last('Process', _("Process Transfer"), '',  'default');

end_form();*/
end_page(false,true);

