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
$page_security = 'SA_LOCATIONTRANSFER';
$path_to_root = "..";
include_once($path_to_root . "/includes/ui/items_cart.inc");

include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/inventory/includes/stock_transfers_ui.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");
include_once($path_to_root . "/modules/serial_items/includes/modules_db.inc");
include_once($path_to_root . "/includes/cost_and_pricing.inc");

add_js_ufile($path_to_root."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_css_file($path_to_root."/css/extjs-default.css");
add_js_ufile($path_to_root.'/js/location_transfers.js');


//--------------------
if(isset($_GET['action']) || isset($_POST['action'])){
    $action = (isset($_GET['action']) ? $_GET['action'] : $_POST['action']);
}else{
    $action=null;
}


if(!is_null($action) || !empty($action)){
    switch ($action) {
        case 'NewTransfer';
            //$AdjDate = $_POST['AdjDate'];
            $brcode = $db_connections[user_company()]["branch_code"];
            handle_new_order(ST_LOCTRANSFER);
            //display_transfer_items_serial($_SESSION['transfer_items'],$brcode,$AdjDate);
            echo '({"AdjDate":"'.$_POST['AdjDate'].'","branchcode":"'.$brcode.'"})';
            exit;
            break;
        case 'AddItem';
            $DataOnGrid = stripslashes(html_entity_decode($_REQUEST['DataOnGrid']));
            $objDataGrid = json_decode($DataOnGrid, true);
            foreach($objDataGrid as $value=>$data) 
            {
                $serialise_id = $data['serialise_id'];
                $category_id = $data['category'];
                $AdjDate = $data['AdjDate'];
                $model = $data['model'];
                $item_code = $data['item_code'];
                $sdescription = $data['sdescription'];
                $color = $data['color'];
                $lot_no = $data['lot_no'];
                $chasis_no = $data['chasis_no'];
                $type_out = $data['type_out'];
                $transno_out = $data['transno_out'];
                $serialised = $data['serialised'];
                $rr_date = $data['rr_date'];
                $qty = $data['qty'];
                $currentqty = $data['qty'];
                $brcode = $db_connections[user_company()]["branch_code"];
                $_SESSION['transfer_items']->from_loc=$brcode;


                if($lot_no == ''){
                    $qty = 0;
                }

                if(!isset($_REQUEST['view'])){
                    $line_item_header = rand();
                    if($serialised) {
                        $standard_cost=Get_System_Cost($model, $type_out, $transno_out);
                        $line_item = count($_SESSION['transfer_items']->line_items);
                        $_SESSION['transfer_items']->add_to_cart($line_item, $model, $qty, $standard_cost, $sdescription, $rr_date, 
                            '0000-00-00', $lot_no, $chasis_no, $color, $item_code, null, $type_out, $transno_out, '', 'new', '', '', '',
                            $line_item_header, null, $currentqty);
                    }else{
                        $standard_cost=Get_System_Cost($model, $type_out, $transno_out);
                        $line_item = count($_SESSION['transfer_items']->line_items);
                        $_SESSION['transfer_items']->add_to_cart($line_item, $model, $qty, $standard_cost, $sdescription, $rr_date, '0000-00-00',null, null, $color, 
                            $item_code, null, $type_out, $transno_out, '', 'new', '', '', '', $line_item_header, null, $currentqty);
                    } 
                }
                /*if(!isset($_REQUEST['view'])){
                    add_to_mt_order($_SESSION['transfer_items'], $model, $serialise_id, $serialised, $type_out, $transno_out, $item_type, $qty, $rr_date, $AdjDate);
                }*/
            }
            display_transfer_items_serial($_SESSION['transfer_items'], $brcode, $AdjDate, $serialise_id);
            exit;
            break;
        case 'SaveTransfer';
            set_global_connection();

            $DataOnGrid = stripslashes(html_entity_decode($_POST['DataOnGrid']));
            $objDataGrid = json_decode($DataOnGrid, true);

            $message="";
            $isError = 0;
            foreach($objDataGrid as $value=>$data) 
            {
                $stock_qty = $data['qty'];
                $currentqty = $data['currentqty'];

                if($stock_qty == 0) {
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
                $_POST['ref']=$Refs->get_next(ST_LOCTRANSFER, null, array('date'=>$AdjDate, 'location'=> get_post('FromStockLocation')));
                $trans_no = add_item_location_transfer($_SESSION['transfer_items']->line_items, $_POST['FromStockLocation'], $_POST['ToStockLocation'], $AdjDate, 
                    $_POST['ref'], $_POST['memo_'], $catcode);
                //new_doc_date($AdjDate);
                //$_SESSION['transfer_items']->clear_items();
                //unset($_SESSION['transfer_items']);
            }
            echo '({"success":"true","reference":"'.$_POST['ref'].'","message":"'.$message.'"})';
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
            $_SESSION['transfer_items']->update_cart_item($filter['id'], $filter['qty'],$filter['standard_cost'],'0000-00-00','0000-00-00',$filter['lot_no'],$filter['chasis_no'], $filter['remarks']);
            
        
            echo '({"success":true,"Update":"","id":"'.$filter['id'].'","qty":"'.$filter['qty'].'"})';
            exit;
            break;
        
        case 'receive_header';
        //$AdjDate = $_POST['AdjDate'];
            $brcode = $db_connections[user_company()]["branch_code"];
            $from_loc = $_POST['from_loc'];
            $MTReference = $_POST['MTreference'];
            handle_new_order(ST_RRBRANCH);
            $AdjDate = sql2date($_POST['AdjDate']);
            $_POST['ref']=$Refs->get_next(ST_RRBRANCH, null, array('date'=>$_POST['AdjDate'], 'location'=> $brcode));
            
            echo '({"AdjDate":"'.$_POST['AdjDate'].'","branchcode":"'.$brcode.'","from_loc":"'.$from_loc.'","RRBRReference":"'.$_POST['ref'].'","MTreference":"'.$MTReference.'"})';
            exit;
            break;
        case 'receive';
            set_global_connection();
            
            $trans_id = $_POST['trans_id'];
            $line_item = $_POST['line_item'];
            $reference = $_POST['reference'];
            //$category_id = $_POST['category_id'];
            //$qty = $_POST['qty'];
            $brcode = $db_connections[user_company()]["branch_code"];
            $_POST['ref'] = $_POST['RRBRReference'];
            $model = $_POST['model'];
            $item_code = $_POST['item_code'];
            $serialise_id = $_POST['engine_no'];
            
            //add_to_rrbranch_order($_SESSION['transfer_items'], $model, $line_item);
            
            //set_global_connection();
            $kit = get_item_serial_mt($line_item);
            
            foreach($kit as $item) {
                //if ($_SESSION['transfer_items']->find_cart_item_new($item_code,$item['mt_details_serial_no'])){
                    //display_error(_("For Part :") . $item['item_code'] . " " . "This item is already on this document. You can change the quantity on the existing line if necessary.");
                //}else{
                    $tandard_cost = $item['mt_details_st_cost'];
                    
                    $_SESSION['transfer_items']->add_to_cart(count($_SESSION['transfer_items']->line_items), $model, $item['mt_details_total_qty'],$tandard_cost,$item['sdescription'],'0000-00-00','0000-00-00',$item['mt_details_serial_no'],$item['mt_details_chasis_no'],$item['idescription'],$item['item_code'],$line_item);
                //}
            }
            
            
            display_transfer_items_serial($_SESSION['transfer_items'],$_POST['FromStockLocation'],$AdjDate);
            
            //echo '({"trans_date":"'.sql2date($trans_date).'","reference":"'.$reference.'","trans_id":"'.$trans_id.'","line_item":"'.$line_item.'","RRReference":"'.$_POST['ref'].'"})';
            exit;
            break;
        case 'save_rrbr';
            //$brcode = $db_connections[user_company()]["branch_code"];
            //display_transfer_items_serial($_SESSION['transfer_items'],$brcode);
            
            
            //$serialise_id = $_REQUEST['serialise_id'];
            set_global_connection();
            $AdjDate = sql2date($_POST['trans_date']);
            $_POST['ref']=$_POST['RRBRReference'];
            $MTreference=$_POST['MTreference'];
            //$model = $_REQUEST['model'];
            //$brcode = $db_connections[user_company()]["branch_code"];
            //echo "ID:".$serialise_id;
            //add_to_order_new($_SESSION['transfer_items'], $model, $serialise_id);
            
            $catcode = $_POST['catcode'];
            
            display_transfer_items_serial($_SESSION['transfer_items'],$_POST['FromStockLocation'],$AdjDate);
            //$_SESSION['transfer_items']->clear_items();
            //unset($_SESSION['transfer_items']);
            exit;
            break;
            
            $trans_no = add_stock_rrBranch($_SESSION['transfer_items']->line_items, $_POST['FromStockLocation'], $_POST['ToStockLocation'], $AdjDate, $_POST['ref'], '',$catcode,$MTreference);
            //new_doc_date($AdjDate);
            $_SESSION['transfer_items']->clear_items();
            unset($_SESSION['transfer_items']);
            echo '({"total":"'.$AdjDate.'","reference":"'.$_POST['ref'].'"})';
            exit;
            break;
            
            
        case 'RemoveItem';
            $id = $_REQUEST['id'];
            $serialise_id = $_REQUEST['serialise_id'];
            $AdjDate = $_POST['AdjDate'];
            $model = $_REQUEST['model'];
            $brcode = $db_connections[user_company()]["branch_code"];
            //echo "ID:".$serialise_id;
            //add_to_order_new($_SESSION['transfer_items'], $model, $serialise_id);
            $_SESSION['transfer_items']->remove_from_cart($id);
            display_transfer_items_serial($_SESSION['transfer_items'],$brcode,$AdjDate,$serialise_id);
            //echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
            exit;
            break;
        case 'getConfig':
            $brcode = $db_connections[user_company()]["branch_code"];
            $brname = $db_connections[user_company()]["name"];
            echo '({"branchcode":"'.$brcode.'","branch_name":"'.$brname.'"})';
            exit;
            break;
        case 'getCountItem':
            $brcode = $db_connections[user_company()]["branch_code"];
            $brname = $db_connections[user_company()]["name"];
            //if(isset($_SESSION['transfer_items']) && $_SESSION['transfer_items']->count_items()>0){
                $counteritem=$_SESSION['transfer_items']->count_items();
            //}else{
            //    $counteritem
           // }
                echo '({"success":true,"countitem":'.$counteritem.'})';
            exit;
            break;
            
        case 'serial_items':
            
            $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
            $end = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
            $catcode = (integer) (isset($_POST['catcode']) ? $_POST['catcode'] : $_GET['catcode']);
            $branchcode = (isset($_POST['branchcode']) ? $_POST['branchcode'] : $_GET['branchcode']);
            $querystr = (isset($_POST['query']) ? $_POST['query'] : $_GET['query']);
            $trans_date = (isset($_POST['trans_date']) ? $_POST['trans_date'] : $_GET['trans_date']);
            $querystrserial = (isset($_POST['serialquery']) ? $_POST['serialquery'] : $_GET['serialquery']);
            
            if($start < 1)	$start = 0;	if($end < 1) $end = 25;
            
            //$brcode = $db_connections[user_company()]["branch_code"];
            $result = get_all_stockmoves($start,$end,$querystr,$catcode,$branchcode,false,'',$trans_date, $querystrserial);
            $total_result = get_all_stockmoves($start,$end,$querystr,$catcode,$branchcode,true,'',$trans_date, $querystrserial);
            $total = DB_num_rows($total_result);
            while ($myrow = db_fetch($result))
            {            
                if($myrow["serialised"]){
                    $qty=$myrow["qty_serialise"];
                }else{
                    $demand_qty = get_demand_qty($myrow["model"], $branchcode);
                    $demand_qty += get_demand_asm_qty($myrow["model"], $branchcode);
                    $qty=get_qoh_on_date_new($myrow["type_out"],$myrow["transno_out"],$myrow["model"],$branchcode, sql2date($trans_date));
                    $qty-=$demand_qty;
                }
                if($qty>0){
                    $serialise_id = get_serialise_id($myrow["serialise_item_code"],$myrow["serialise_lot_no"]);
                    $counteritem=$_SESSION['transfer_items']->find_cart_item_new_for_mtandcml($myrow["serialise_item_code"],$myrow["serialise_lot_no"], $myrow["type_out"], $myrow["transno_out"]);
                    if(!$counteritem){
                        $group_array[] = array('serialise_id'=>$serialise_id,
                            'type_out' => $myrow["type_out"],
                            'transno_out' => $myrow["transno_out"],
                            'reference' => $myrow["reference"],
                            'tran_date' => $myrow["tran_date"],
                            'item_code' => $myrow["serialise_item_code"],
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
                            'item_type'=>$myrow["item_type"]
                        );
                    }
                }                
            }          
            $jsonresult = json_encode($group_array);
            echo '({"total":"'.DB_num_rows($total_result).'","result":'.$jsonresult.'})';
            exit;
            break;
        case 'MTserialitems':
            //global $def_coy;
            //set_global_connection($def_coy);
            
            $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
            $end = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
            $catcode = (integer) (isset($_POST['catcode']) ? $_POST['catcode'] : $_GET['catcode']);
            //$branchcode = (isset($_POST['branchcode']) ? $_POST['branchcode'] : $_GET['branchcode']);
            $reference = (isset($_POST['reference']) ? $_POST['reference'] : $_GET['reference']);
            $querystr = (isset($_POST['query']) ? $_POST['query'] : $_GET['query']);
            $trans_id = (integer) (isset($_POST['trans_id']) ? $_POST['trans_id'] : $_GET['trans_id']);
            $brcode = $db_connections[user_company()]["branch_code"];
            
            if($start < 1)	$start = 0;	if($end < 1) $end = 25;
            
            $sql = "SELECT move.trans_no, move.stock_id, move.reference, move.lot_no, move.chassis_no, move.qty, 
            move.category_id, move.standard_cost, code.description, master.description AS description_item, loc.location_name AS loc_name
            FROM ".TB_PREF."stock_adjustment move 
            LEFT JOIN ".TB_PREF."item_codes code ON code.item_code = move.color_code
            LEFT JOIN ".TB_PREF."stock_master master ON master.stock_id = move.stock_id
            INNER JOIN locations loc ON move.loc_code = loc.loc_code
            WHERE (move.qty>0) AND move.reference = ".db_escape($reference);
            
           if($catcode!=0){
                $sql.=" AND move.category_id = $catcode";
            }
            if($reference!=''){
                $sql.=" AND move.reference = '".$reference."'";
                
            }
            
            $result=db_query($sql, "could not get all Serial Items");
            $total = db_num_rows($result);
            
            while ($myrow = db_fetch($result))
            {
                $group_array[] = array('trans_no' => $myrow["trans_no"],
                    'model' => $myrow["stock_id"],
                    'color' => $myrow["description"],
                    'item_description' => $myrow["description_item"],
                    'qty' => price_format(abs($myrow["qty"])),
                    'category_id'=>$catcode,
                    'lot_no' => $myrow["lot_no"],
                    'chasis_no' => $myrow["chassis_no"],
                    'standard_cost' => $myrow["standard_cost"],
                    'tolocation'=> $myrow["loc_name"]
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
            
            //$brcode = $db_connections[user_company()]["branch_code"];
            $sql = "SELECT *, sc.description as category, md.mt_details_total_qty as totalqty, ic.description as item_description, sm.description as stock_description FROM ".TB_PREF."mt_header mh LEFT JOIN ".TB_PREF."mt_details md ON mh.mt_header_id=md.mt_details_header_id LEFT JOIN ".TB_PREF."stock_category sc ON mh.mt_header_category_id=sc.category_id LEFT JOIN item_codes ic ON md.mt_details_item_code=ic.item_code LEFT JOIN stock_master sm ON md.mt_details_stock_id=sm.stock_id WHERE mh.mt_header_id=$trans_id";
            
            
            $result = db_query($sql, "could not get all Serial Items");
            //$total_result = get_all_serial($start,$end,$querystr,$catcode,$branchcode,true);
            //$total = db_num_rows($total_result);
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
                    'model' => $myrow["mt_details_stock_id"],
                    'item_code' => $myrow["mt_details_item_code"],
                    'trans_date' => sql2date($myrow["mt_header_date"]),
                    'category' => $myrow["category"],
                    'qty' => number_format($myrow["totalqty"],2),
                    'category_id'=>$catcode,
                    'lot_no' => $myrow["mt_details_serial_no"],
                    'chasis_no' => $myrow["mt_details_chasis_no"],
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

            $loc_stat = (isset($_POST['loc_stat']) ? $_POST['loc_stat'] : $_GET['loc_stat']);
            $search_ref = (isset($_POST['search_ref']) ? $_POST['search_ref'] : $_GET['search_ref']);
            
            $result = get_all_location_transfer_item($start,$limit,$querystr,$loc_stat,$search_ref,false,'');
            $total_result = get_all_location_transfer_item($start,$limit,$querystr,$loc_stat,$search_ref,true,'');
            $total = DB_num_rows($result);
            while ($myrow = db_fetch($result))
            {
                if($myrow["date_approved"] == '0000-00-00') {
                    $postdate = '';
                }else{
                    $postdate = sql2date($myrow["date_approved"]);
                }
                $group_array[] = array('trans_no'=>$myrow["trans_no"],
                    'reference' => $myrow["reference"],
                    'tran_date' => sql2date($myrow["tran_date"]),
                    'loc_code' => $myrow["loc_code"],
                    'loc_name' => $myrow["loc_name"],
                    'from_name' => $myrow["from_name"],
                    'remarks' => $myrow["memo"],
                    'category_id' => $myrow["category_id"],
                    'category_name' => $myrow["category_name"],
                    'qty' => number_format(abs($myrow["total_item"]),2),
                    'status' => $myrow["status"],
                    'postdate' => $postdate
                );
            }
            
            $jsonresult = json_encode($group_array);
            echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
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
            $sql = "SELECT *, sc.description as category, sum(md.mt_details_total_qty) as totalqty,sum(md.mt_details_total_qty)-sum(md.mt_details_recvd_qty) as balance_qty FROM ".TB_PREF."mt_header mh LEFT JOIN ".TB_PREF."mt_details md ON mh.mt_header_id=md.mt_details_header_id LEFT JOIN ".TB_PREF."stock_category sc ON mh.mt_header_category_id=sc.category_id where mh.mt_header_tolocation='$branchcode' $str_fromlocation GROUP BY mh.mt_header_reference";
            
            
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
                    'trans_date' => sql2date($myrow["mt_header_date"]),
                    'fromlocation' => $myrow["mt_header_fromlocation"],
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
        case 'location':
            $brcode = $db_connections[user_company()]["branch_code"];
            $brname = $db_connections[user_company()]["name"];
            $fromlocation = $_GET['brcode'];
            $result = get_item_locations(check_value('show_inactive'), get_post('fixed_asset', 0));
            $total = db_num_rows($result);
            /*if($brcode!=$fromlocation){
                $group_array[] = array('loc_code'=>$brcode,'location_name' => $brname,'delivery_address' => '','phone' => '',
                    'phone2'=>'');
            }*/
            
            
            while ($myrow = db_fetch($result))
            {
                if($myrow["loc_code"]!=$fromlocation){
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
        case 'fromlocation':
            $brcode = $db_connections[user_company()]["branch_code"];
            $brname = $db_connections[user_company()]["name"];
            $result = get_item_locations(check_value('show_inactive'), get_post('fixed_asset', 0));
            $total = db_num_rows($result);
            $group_array[] = array('loc_code'=>$brcode,
                'location_name' => $brname,
                'delivery_address' => '',
                'phone' => '',
                'phone2'=>''
            );
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
        case 'UserRoleId':
            
            $user_role = check_user_role($_SESSION["wa_current_user"]->username);
            $user_name = $_SESSION["wa_current_user"]->name;

            echo '({"user_name":"'.$user_name.'","user_role":"'.$user_role.'"})';
            exit;
            break;
        case 'UserRoleId_apprv':
            
            $user_role = check_user_role($_SESSION["wa_current_user"]->username);
            $user_name = $_SESSION["wa_current_user"]->username;

            $sql = "SELECT role_id FROM ".TB_PREF."users WHERE user_id = '$user_name'";
            
            $result = db_query($sql, "could not get all Serial Items");

            while ($myrow = db_fetch($result))
            {
                $group_array[] = array('role_id'=>$myrow["role_id"]); 
            }

            $jsonresult = json_encode($group_array);
            echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
            exit;
            break;
        case 'approval':
            $reference = (isset($_POST['reference']) ? $_POST['reference'] : $_GET['reference']);
            $total=0;
            $sql = "SELECT trans_no, type, reference FROM ".TB_PREF."stock_adjustment
            WHERE type='".ST_LOCTRANSFER."' AND reference=".db_escape($reference);
             
            $result=db_query($sql, "could not get all Items");
            
            $total = db_num_rows($result);

            while ($myrow = db_fetch($result))
            {
                update_stock_adjustment_status($myrow["reference"], $myrow["type"]);
            }
            echo '({"total":"'.$total.'","success":true})';
            exit;
            break;
        case 'disapproval':
            $reference = (isset($_POST['reference']) ? $_POST['reference'] : $_GET['reference']);
            $total=0;
            $sql = "SELECT trans_no, type, reference FROM ".TB_PREF."stock_adjustment
            WHERE type='".ST_LOCTRANSFER."' AND reference=".db_escape($reference);
             
            $result=db_query($sql, "could not get all Items");
            
            $total = db_num_rows($result);

            while ($myrow = db_fetch($result))
            {
                update_stock_adjustment_status_disaaproved($myrow["reference"], $myrow["type"]);
            }            
            echo '({"total":"'.$total.'","success":true})';
            exit;
            break;
         case 'posting_transaction':
            
            $reference = (isset($_POST['reference']) ? $_POST['reference'] : $_GET['reference']);
            $trans_no = (integer) (isset($_POST['trans_no']) ? $_POST['trans_no'] : $_GET['trans_no']);
           
            $PostDate = sql2date($_POST['PostDate']);
            $PostDated = date('Y-m-d', strtotime($_POST['PostDate']));
            
            $result = get_transaction_from_stock_adjusment(ST_LOCTRANSFER, $trans_no, $reference);
            $result2 = get_transaction_from_stock_adjusment_transfer_check(ST_LOCTRANSFER, $trans_no, $reference);

            $errmsg="";
            $isError = 0;
            while ($myrow01 = db_fetch($result2))
            {
                $qoh = get_qoh_on_date_new($myrow01['trans_type_out'], $myrow01['trans_no_out'], $myrow01['stock_id'], $myrow01['loc_code'], null, 
                    $myrow01['lot_no']);

                if($qoh == 0) {
                    $isError = 1;
                    $errmsg="Sorry, Can't Proceed! There is not enough quantity in stock for Stock ID: ".$myrow01['stock_id']."  and Serial #: ".$myrow01['lot_no']." and - Remaining QOH: ".$qoh."";
                    break;
                }elseif($qoh < -$myrow01['qty']) {
                    $isError = 1;
                    $errmsg="Sorry, Can't Proceed! There is not enough quantity in stock for Stock ID: ".$myrow01['stock_id']."  and Serial #: ".$myrow01['lot_no']." and - Remaining QOH: ".$qoh."";
                    break;
                }
            }

            if($isError != 1){
                $totalitem = db_num_rows($result);
                while ($myrow = db_fetch($result))
                {
                    add_stock_move(ST_LOCTRANSFER, $myrow["stock_id"], $myrow["trans_no"], $myrow["loc_code"], $PostDate, $myrow["reference"], $myrow["qty"],
                        $myrow["standard_cost"], 0, $myrow["lot_no"], $myrow["chassis_no"], $myrow["category_id"], $myrow["color_code"], $myrow["trans_type_out"],
                        $myrow["trans_no_out"]);

                    update_stock_adjustment_status_post($myrow["reference"], ST_LOCTRANSFER, $PostDated);
                }
            }
            echo '({"success":true,"totalItem":"'.$totalitem.'","reference":"'.$reference.'","trans_no":"'.$trans_no.'",
                "errmsg":"'.$errmsg.'"})';
        exit;
        break;
    }
}

function getLocation($location){
    
}
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
		unset($_SESSION['transfer_items']);
	}

	$_SESSION['transfer_items'] = new items_cart($transtype);
	$_SESSION['transfer_items']->clear_items();
	$_SESSION['transfer_items']->fixed_asset = isset($_GET['FixedAsset']);
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

	handle_new_order();
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
/*start_table(TABLESTYLE, "width='100%'");
   echo "<div id='merchandisetransfer-grid' style='padding:15px'></div>";
   echo "<style type='text/css' media='screen'>
            .x-form-text-default.x-form-textarea {
                line-height: 20px;
                min-height: 30px;
            }
        </style>";
end_table();

//end_form();
end_page();*/

