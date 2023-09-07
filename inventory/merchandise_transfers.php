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

//if($_GET['transtype']=='out') 
    $page_security = 'SA_MERCHANDISETRANSFER';
//else $page_security = 'SA_RRBRANCH';


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
if($_GET['transtype']=='out'){
    add_js_ufile($path_to_root.'/js/merchandise_transfers.js');
}else{
    add_js_ufile($path_to_root.'/js/merchandise_transfers_in.js');
    
}


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
            //$AdjDate = $_POST['AdjDate'];
            $brcode = $db_connections[user_company()]["branch_code"];
            handle_new_order(ST_MERCHANDISETRANSFER);
            //display_transfer_items_serial($_SESSION['transfer_items'],$brcode,$AdjDate);
            //echo '({"AdjDate":"'.$_POST['AdjDate'].'","branchcode":"'.$brcode.'"})';
            echo json_encode(array('AdjDate'=>$_POST['AdjDate'],'branchcode'=>$brcode));
            exit;
            break;
        case 'NewTransferManual';
            $brcode = $db_connections[user_company()]["branch_code"];
            handle_new_order(ST_RRBRANCH);
            $AdjDate = sql2date($_POST['AdjDate']);
            $_POST['ref']=$Refs->get_next(ST_RRBRANCH, null, array('date'=>$_POST['AdjDate'], 'location'=> $brcode));
            
            echo '({"AdjDate":"'.$_POST['AdjDate'].'","branchcode":"'.$brcode.'","reference":"'.$_POST['ref'].'"})';
            exit;
            break;
        case 'AddItem';
            $DataOnGrid = stripslashes(html_entity_decode($_REQUEST['DataOnGrid']));
            $objDataGrid = json_decode($DataOnGrid, true);
            //var_dump($objDataGrid);
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
                        $standard_cost=Get_System_Cost_serialised($model, $lot_no, $type_out, $transno_out);
                        $line_item = count($_SESSION['transfer_items']->line_items);
                        $_SESSION['transfer_items']->add_to_cart($line_item, $model, $qty, $standard_cost, $sdescription, $rr_date, 
                            '0000-00-00', $lot_no, $chasis_no, $color, $item_code, null, $type_out, $transno_out, '', 'new', '', '', '',
                            $line_item_header, null, $currentqty);
                    }else{
                        $standard_cost=Get_System_Cost($model, $type_out, $transno_out);
                        $line_item = count($_SESSION['transfer_items']->line_items);
                        $_SESSION['transfer_items']->add_to_cart($line_item, $model, $qty, $standard_cost, $sdescription, $rr_date, '0000-00-00', null, null, $color, 
                            $item_code, null, $type_out, $transno_out, '', 'new', '', '', '', $line_item_header, null, $currentqty);
                    } 
                }
            }
            display_transfer_items_serial($_SESSION['transfer_items'], $brcode, $AdjDate, $serialise_id);
            exit;
            break;
        case 'ManualAddItem';
            $DataOnGrid = stripslashes(html_entity_decode($_REQUEST['DataOnGrid']));
            $objDataGrid = json_decode(trim($DataOnGrid), true);

            foreach($objDataGrid as $value=>$data) 
            {
                $category_id = $data['category'];
                $AdjDate = $data['AdjDate'];
                $model = $data['model'];
                $item_code = $data['item_code'];                
                $serialised = $data['serialised'];
                $stock_description = $data['stock_description'];
                $item_description = $data['item_description'];
                $brcode = $db_connections[user_company()]["branch_code"];
                $_SESSION['transfer_items']->from_loc=$brcode;

                if($serialised) {
                    $qty = 1;
                }else{
                    $qty = 0;
                }

                if(!isset($_REQUEST['view'])){
                    $line_item_header = rand();
                    $line_item = count($_SESSION['transfer_items']->line_items);
                    $_SESSION['transfer_items']->add_to_cart($line_item, $model, $qty, 0, $stock_description, '0000-00-00','0000-00-00', '', '', $item_description, $item_code, 0, 0, 0,'', 'new', '', '', '', $line_item_header, null, null);
                }
            }
            display_transfer_items_serial_for_rr($_SESSION['transfer_items'], $brcode, $AdjDate);
            exit;
            break;
        case 'SaveTransfer';
            set_global_connection();

            $qty = stripslashes(html_entity_decode($_POST['qty']));
            $objDataGrid = json_decode($qty, true);
            $approval_value = (isset($_POST['value']) ? $_POST['value'] : $_GET['value']);

            if($approval_value=='yes'){
                $message="";
                $AdjDate = sql2date($_POST['AdjDate']);

                $counteritem=$_SESSION['transfer_items']->count_items();
                $total_rrdate = $_SESSION['transfer_items']->check_qty_avail_by_rrdate($_POST['AdjDate']);
                
                $isError = 0;
                foreach($objDataGrid as $value=>$data) 
                {
                    $stock_qty = $data['qty'];
                    $currentqty = $data['currentqty'];
                    $standard_cost = $data['standard_cost'];

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
                    }elseif($standard_cost == 0) {
                        $isError = 1;
                        $message="Unit Cost must not be zero.";
                        break;
                    }
                }

                foreach ($_SESSION['transfer_items']->line_items AS $item)
                {         
                    if($item->quantity == 0){
                        $isError = 1;
                        $message="Quantity must not be zero.";                     
                        break;
                    }elseif($item->quantity > $item->currqty){
                        $isError = 1;
                        $message = "Sorry, Quantity you entered '".$item->quantity."' is Greater than Available Quantity On Hand: '".$item->currqty."'";
                        break;
                    }
                }

                if($isError != 1){
                    $AdjDate = sql2date($_POST['AdjDate']);
                    $catcode = $_POST['catcode'];
                    $_POST['ref']=$Refs->get_next(ST_MERCHANDISETRANSFER, null, array('date'=>$AdjDate, 'location'=> get_post('FromStockLocation')));
                    $trans_no = add_stock_merchandise_transfer($_SESSION['transfer_items']->line_items, $_POST['FromStockLocation'], $_POST['ToStockLocation'], $AdjDate, $_POST['ref'], $_POST['memo_'],$catcode, $_POST['rsdno'],$_POST['servedby']);
                    //new_doc_date($AdjDate);
                    //$_SESSION['transfer_items']->clear_items();
                    //unset($_SESSION['transfer_items']);
                }
            }
            echo '({"success":"true","reference":"'.$_POST['ref'].'","message":"'.$message.'"})';
            exit;
            break;
        case 'SaveManualTransfer';
            set_global_connection();

            $DataOnGrid = stripslashes(html_entity_decode($_POST['DataOnGrid']));
            $objDataGrid = json_decode($DataOnGrid, true);

            $message="";
            $AdjDate = sql2date($_POST['AdjDate']);
            
            $counteritem=$_SESSION['transfer_items']->count_items();
                          
            $isError = 0;
            foreach($objDataGrid as $value=>$data) 
            {
                $stock_id = $data['stock_id'];
                $item_code = $data['item_code'];
                $stock_description = $data['stock_description'];
                $item_description = $data['item_description'];
                $quantity = $data['qty'];
                $standard_cost = $data['standard_cost'];
                $lot_no = $data['lot_no'];
                $chasis_no = $data['chasis_no'];
                $serialised = $data['serialised'];
                $catcode = $data['catcode'];

                if($counteritem<=0){
                    $isError = 1;
                    //echo '({"success":"false","message":"No Item Selected"})';
                    $message="No Item Selected";                     
                    break;
                }elseif($quantity == 0){
                    $isError = 1;
                    $message="Quantity must not be zero.";
                    break;
                }elseif($standard_cost == 0){
                    $isError = 1;
                    $message="Standard Cost must not be zero.";
                    break;
                }elseif ($catcode == '14' && $lot_no == ''){
                    $isError = 1;
                    $message="Serial No. must not be empty.";
                    break;
                }elseif($catcode == '14' && $chasis_no == ''){
                    $isError = 1;
                    $message="Chassis No. must not be empty.";
                    break;
                }elseif ($catcode == '15' && $lot_no == ''){
                    $isError = 1;                    
                    $message="Serial No. must not be empty.";
                    break;                    
                }elseif($serialised && $lot_no == '') {
                    $isError = 1;                    
                    $message="Serial No. must not be empty.";
                    break;  
                }elseif($serialised && $quantity > 1) {
                    $isError = 1;                    
                    $message="Quantity must not greater than 1";
                    break; 
                }
            }

            if($isError != 1){
                $brcode = $db_connections[user_company()]["branch_code"];
                $catcode = $_POST['catcode'];
                $FromStockLocation = $_POST['FromStockLocation'];
                $br_reference = $_POST['br_reference'];
                $mt_reference = $_POST['mt_reference'];
                $remarks = $_POST['remarks'];

                $servedby = $_SESSION["wa_current_user"]->name;
                              
                $totalline=count($objDataGrid);
            
                $trans_no = add_stock_rrBranch_manual($objDataGrid, $FromStockLocation, $brcode, $AdjDate, $br_reference, $remarks, $catcode,$mt_reference, $servedby);

                $_SESSION['transfer_items']->clear_items();
                unset($_SESSION['transfer_items']);
            }                       
            echo '({"total":"'.$totalline.'","reference":"'.$br_reference.'","message":"'.$message.'"})';
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

            //global $def_coy;
            set_global_connection();
            $sql = "SELECT *, sc.description as category, md.mt_details_total_qty as totalqty, ic.description as item_description, sm.description as stock_description, md.mt_details_st_cost FROM ".TB_PREF."mt_header mh LEFT JOIN ".TB_PREF."mt_details md ON mh.mt_header_id=md.mt_details_header_id LEFT JOIN ".TB_PREF."stock_category sc ON mh.mt_header_category_id=sc.category_id LEFT JOIN item_codes ic ON md.mt_details_item_code=ic.item_code LEFT JOIN stock_master sm ON md.mt_details_stock_id=sm.stock_id WHERE mh.mt_header_id=$trans_id";
            
            
            $result = db_query($sql, "could not get all Serial Items");
            //$total_result = get_all_serial($start,$end,$querystr,$catcode,$branchcode,true);
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
            //$category_id = $_POST['category_id'];
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
            //$kit = get_item_serial_mt($line_item);
            $myrow = db_fetch($result);
            
            //$standard_cost = $myrow['mt_details_st_cost'];
            $countrec=count($_SESSION['transfer_items']->line_items);
            $_SESSION['transfer_items']->add_to_cart($recline, $model, $qty,$standard_cost,$myrow['sdescription'],'0000-00-00','0000-00-00', $engine_no, $chasis_no, $myrow['idescription'], $item_code, $line_item);
            
            
            /*foreach($kit as $item) {
                //if ($_SESSION['transfer_items']->find_cart_item_new($item_code,$item['mt_details_serial_no'])){
                    //display_error(_("For Part :") . $item['item_code'] . " " . "This item is already on this document. You can change the quantity on the existing line if necessary.");
                //}else{
                    $standard_cost = $item['mt_details_st_cost'];

                    $_SESSION['transfer_items']->add_to_cart(count($_SESSION['transfer_items']->line_items), $model, $item['mt_details_total_qty'],$standard_cost,$item['sdescription'],'0000-00-00','0000-00-00',$item['mt_details_serial_no'],$item['mt_details_chasis_no'],$item['idescription'],$item['item_code'],$line_item);
                //}
            }*/


            //display_transfer_items_serial($_SESSION['transfer_items'],$_POST['FromStockLocation'],$AdjDate);

            //echo '({"trans_date":"'.sql2date($trans_date).'","reference":"'.$reference.'","trans_id":"'.$trans_id.'","line_item":"'.$line_item.'","RRReference":"'.$_POST['ref'].'"})';
            echo '({"countrecord":"'.$recline.'","lineitem":"'.$line_item.'"})';
            exit;
            break;
        case 'receive_item';
            global $def_coy;
            set_global_connection($def_coy);
            
            $trans_id = $_POST['trans_id'];
            $line_item = $_POST['line_item'];
            $reference = $_POST['reference'];
            //$category_id = $_POST['category_id'];
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
            //$kit = get_item_serial_mt($line_item);
            $myrow = db_fetch($result);
            
            //$standard_cost = $myrow['mt_details_st_cost'];
            $countrec=count($_SESSION['transfer_items']->line_items);
            $_SESSION['transfer_items']->add_to_cart($countrec, $model, $qty,$standard_cost,$myrow['sdescription'],'0000-00-00','0000-00-00', $engine_no, $chasis_no, $myrow['idescription'], $item_code, $line_item);
            
            
            /*foreach($kit as $item) {
             //if ($_SESSION['transfer_items']->find_cart_item_new($item_code,$item['mt_details_serial_no'])){
             //display_error(_("For Part :") . $item['item_code'] . " " . "This item is already on this document. You can change the quantity on the existing line if necessary.");
             //}else{
             $standard_cost = $item['mt_details_st_cost'];
             
             $_SESSION['transfer_items']->add_to_cart(count($_SESSION['transfer_items']->line_items), $model, $item['mt_details_total_qty'],$standard_cost,$item['sdescription'],'0000-00-00','0000-00-00',$item['mt_details_serial_no'],$item['mt_details_chasis_no'],$item['idescription'],$item['item_code'],$line_item);
             //}
             }*/
            
            
            //display_transfer_items_serial($_SESSION['transfer_items'],$_POST['FromStockLocation'],$AdjDate);
            
            //echo '({"trans_date":"'.sql2date($trans_date).'","reference":"'.$reference.'","trans_id":"'.$trans_id.'","line_item":"'.$line_item.'","RRReference":"'.$_POST['ref'].'"})';
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
            $_SESSION['transfer_items']->update_cart_item($filter['id'], $filter['qty'],$filter['standard_cost'],'0000-00-00','0000-00-00',$filter['lot_no'],$filter['chasis_no'], $filter['color']);


            echo '({"success":true,"Update":"","id":"'.$filter['id'].'","qty":"'.$filter['qty'].'"})';
            exit;
            break;
        case 'getTotalCost';
            $arrayremove = array("[","]");
            $onlyconsonants = str_replace($arrayremove, "", html_entity_decode($_REQUEST['dataUpdate']));
            $filter = json_decode($onlyconsonants, true);

            $totalcost=$_SESSION['transfer_items']->rr_manual_items_total_cost();

            echo '({"TotalCost":"'.number_format2($totalcost,2).'"})';
            exit;
        break;
        case 'getTotalBalance';
            $totalcost=$_SESSION['transfer_items']->rr_manual_items_total_cost();

            echo '({"TotalCost":"'.number_format2($totalcost,2).'"})';
            exit;
        break;
        case 'ManualupdateData';
            $arrayremove = array("[","]");
            $onlyconsonants = str_replace($arrayremove, "", html_entity_decode($_REQUEST['dataManualUpdate']));
            $filter = json_decode($onlyconsonants, true);
            $_SESSION['transfer_items']->update_cart_item($filter['id'], $filter['qty'],$filter['standard_cost'],'0000-00-00','0000-00-00',$filter['lot_no'], $filter['chasis_no'], $filter['color']);
        
            echo '({"success":true,"Update":"","id":"'.$filter['id'].'","qty":"'.$filter['qty'].'","standard_cost":"'.$filter['standard_cost'].'","lot_no":"'.$filter['lot_no'].'","chasis_no":"'.$filter['chasis_no'].'"})';
            exit;
        break;
        case 'save_rrbr';
            
            set_global_connection();

            $DataOnGrid = stripslashes(html_entity_decode($_POST['DataOnGrid']));
            $objDataGrid = json_decode($DataOnGrid, true);

            //var_dump($objDataGrid);
            $isError = 0;
            if (count($objDataGrid) == 0){
               $message = "Please Select Item";
            } else {
                foreach($objDataGrid as $value=>$data) 
                {
                    //echo $data['trans_date'];
                    $AdjDate = sql2date($data['trans_date']);
                    $MTreference = $data['MTreference'];
                    $from_loc_code = $data['from_loc_code'];

                    $line_item = $data['line_item'];
                    $model = $data['model'];
                    $quantity = $data['qty'];
                    $currentqty = $data['currentqty'];
                    $receivedqty = $data['receivedqty'];
                    $lot_no = $data['lot_no'];
                    $chasis_no = $data['chasis_no'];
                    $catcode = $data['catcode'];
                    $item_code = $data['item_code'];
                    $standard_cost = $data['standard_cost'];
                    $rrbrreference = $data['rrbrreference'];

                    if($quantity == 0) {
                        $isError = 1;
                        $message="Quantity must not be zero.";
                        break;
                    }elseif($quantity > $currentqty) {
                        $isError = 1;
                        $message = "Sorry, Quantity you entered '".$quantity."' is Greater than Available Quantity On Hand: '".$currentqty."'";
                        break;
                    }
                    //$AdjDate = sql2date($_POST['trans_dates']);
                    //$_SESSION['transfer_items']->from_loc=$brcode;
                }

                if($isError != 1){
                    $brcode = $db_connections[user_company()]["branch_code"];
                    if($rrbrreference=='') {
                        $reference = $_POST['ref']=$Refs->get_next(ST_RRBRANCH, null, array('date'=>$AdjDate, 'location'=> $brcode));
                    }else{
                        $reference = $rrbrreference;
                    }  
                                
                    $totalline=count($objDataGrid);
                    
                    //$trans_no = add_stock_rrBranch($_SESSION['transfer_items']->line_items,$from_loc_code, $_POST['ToStockLocation'], $AdjDate, $_POST['ref'], '', $catcode, $MTreference);

                    $trans_no = add_stock_rrBranch($objDataGrid, $from_loc_code, $brcode, $AdjDate, $reference, '', $catcode, 
                    $MTreference);

                    $_SESSION['transfer_items']->clear_items();
                    unset($_SESSION['transfer_items']);
                }
                //$brcode = $db_connections[user_company()]["branch_code"];           
                //$AdjDate = sql2date($_POST['trans_date']);
                //$_POST['ref']=$_POST['RRBRReference'];
                //$MTreference=$_POST['MTreference'];
                //$from_loc_code=$_POST['from_loc_code'];
                //$catcode = $_POST['catcode'];

                //display_transfer_items_serial($_SESSION['transfer_items'],$_POST['FromStockLocation'],$AdjDate);
                //$_SESSION['transfer_items']->clear_items();
                //unset($_SESSION['transfer_items']);
                //exit;
                //break;
                //$totalline=count($_SESSION['transfer_items']->line_items);
                
                //new_doc_date($AdjDate);               
            }
            echo '({"total":"'.$totalline.'","reference":"'.$reference.'","message":"'.$message.'"})';
            exit;
            break;


        case 'RemoveItem';
            $id = $_REQUEST['id'];
            $line_item = $_REQUEST['line_item'];
            $serialise_id = $_REQUEST['serialise_id'];
            $AdjDate = $_POST['AdjDate'];
            $model = $_REQUEST['model'];
            $brcode = $db_connections[user_company()]["branch_code"];
            //echo "ID:".$serialise_id;
            //add_to_order_new($_SESSION['transfer_items'], $model, $serialise_id);
            //$_SESSION['transfer_items']->remove_from_cart($line_item);
            $_SESSION['transfer_items']->remove_from_cart_line($line_item);
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
            //$brcode = $db_connections[user_company()]["branch_code"];
            //$brname = $db_connections[user_company()]["name"];
            //if(isset($_SESSION['transfer_items']) && $_SESSION['transfer_items']->count_items()>0){
            $counteritem=$_SESSION['transfer_items']->count_items();
            //}else{
            //    $counteritem
            // }
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
            //if(isset($querystrserial)) $querystr = $querystrserial;
            //if($start < 1)	$start = 0;	if($limit < 1) $limit = 25;

            //$brcode = $db_connections[user_company()]["branch_code"];
            $result = get_all_stockmoves($start,$limit,$querystr,$catcode,$branchcode,false,'',null, $querystrserial);
            $total_result = get_all_stockmoves($start,$limit,$querystr,$catcode,$branchcode,true,'',null, $querystrserial);

            $total = DB_num_rows($result);

            while ($myrow = db_fetch($result))
            {
                if($myrow["serialised"]){
                   $qty=$myrow["qty_serialise"];
                }else{
                   $demand_qty = get_demand_qty($myrow["model"], $branchcode);
	               $demand_qty += get_demand_asm_qty($myrow["model"], $branchcode);
	               $qty=get_qoh_on_date_new($myrow["type_out"], $myrow["transno_out"], $myrow["model"], $branchcode, null, $myrow["serialise_lot_no"]);
                   $qty-=$demand_qty;
                }

                //$subtotal_cost = $myrow["standard_cost"] * $myrow["qty_serialise"];
                
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
                            'serialise_loc_code'=>$myrow["serialise_loc_code"]
                            //'subtotal_cost'=>$subtotal_cost
                        );   
                    }         
                }      
            }
            
            $jsonresult = json_encode($group_array);
            echo '({"total":"'.DB_num_rows($total_result).'","result":'.$jsonresult.'})';
            exit;
            break;
        case 'items_listing':
            global $def_coy;
            set_global_connection($def_coy);
            
            $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
            $end = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
            $catcode = (integer) (isset($_POST['catcode']) ? $_POST['catcode'] : $_GET['catcode']);
            $brand = (integer) (isset($_POST['brand']) ? $_POST['brand'] : $_GET['brand']);
            $branchcode = (isset($_POST['branchcode']) ? $_POST['branchcode'] : $_GET['branchcode']);
            $trans_date = (isset($_POST['trans_date']) ? $_POST['trans_date'] : $_GET['trans_date']);
            $querystr = (isset($_POST['querystr']) ? $_POST['querystr'] : $_GET['querystr']);
            $querystrserial = (isset($_POST['serialquery']) ? $_POST['serialquery'] : $_GET['serialquery']);
            if($start < 1)	$start = 0;	if($end < 1) $end = 25;
            
            $result = get_all_items_listing($start,$end,$brand,$querystr,$catcode,false);
            $total_result = get_all_items_listing($start,$end,$brand,$querystr,$catcode,true);
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
            //global $def_coy;
            //set_global_connection($def_coy);
            
            $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
            $end = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
            $catcode = (integer) (isset($_POST['catcode']) ? $_POST['catcode'] : $_GET['catcode']);
            $reference = (isset($_POST['reference']) ? $_POST['reference'] : $_GET['reference']);
            $querystr = (isset($_POST['query']) ? $_POST['query'] : $_GET['query']);
            $trans_id = (integer) (isset($_POST['trans_id']) ? $_POST['trans_id'] : $_GET['trans_id']);
            $brcode = $db_connections[user_company()]["branch_code"];

            if($start < 1)	$start = 0;	if($end < 1) $end = 25;

            
            $sql = "SELECT *, sc.description AS category, md.mt_details_status, md.mt_details_total_qty AS totalqty, md.mt_details_st_cost AS unit_cost, ic.description AS item_description, sm.description AS stock_description FROM ".TB_PREF."mt_header mh LEFT JOIN ".TB_PREF."mt_details md ON mh.mt_header_id=md.mt_details_header_id LEFT JOIN ".TB_PREF."stock_category sc ON mh.mt_header_category_id=sc.category_id LEFT JOIN item_codes ic ON md.mt_details_item_code=ic.item_code LEFT JOIN stock_master sm ON md.mt_details_stock_id=sm.stock_id WHERE mh.mt_header_id=$trans_id";

            
            if($catcode!=0){
                $sql.=" AND mh.mt_header_category_id=$catcode";
            }
            if($reference!=''){
                $sql.=" AND mh.mt_header_reference='".$reference."'";
                
            }
            if($brcode!=''){
                $sql.=" AND mh.mt_header_fromlocation='".$brcode."'";
                
            }
            /*if($querystr!=''){
                $sql.=" AND (serial.color_code LIKE '%".$querystr."%' OR smaster.description LIKE '%".$querystr."%' OR icode.description LIKE '%".$querystr."%')";
            }*/
            //$sql.=" group by serial.stock_id, serial.color_code, serial.lot_no, serial.chassis_no";
            if($all){
                
            }else{
                //$sql.=" LIMIT $start,$end";
            }
            
            $result=db_query($sql, "could not get all Serial Items");
            
            $total = db_num_rows($result);
            
            
            while ($myrow = db_fetch($result))
            {
                //if($myrow["serialise_qty"]>0){
                    //$serialise_id = get_serialise_id($myrow["serialise_item_code"],$myrow["serialise_lot_no"]);
                    //$tandard_cost = Get_StandardCost_Plcy($branchcode,$catcode,$myrow["model"]);

                    /*if($myrow["mt_details_status"]==0){
                        $status_msg='In-transit';
                    }elseif($myrow["mt_details_status"]==1){
                        $status_msg='Partial';
                    }elseif($myrow["mt_details_status"]==2){
                        $status_msg='Received';
                    }*/
                    $total_cost = ($myrow["totalqty"] * $myrow["unit_cost"]);
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
                        'status_msg'=>$myrow["mt_details_status"],
                        'unit_cost'=>$myrow["unit_cost"],
                        'total_cost'=>$total_cost
                    );
                //}
                
                
            }
            
            $jsonresult = json_encode($group_array);
            echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
            exit;
            break;
        case 'BRMTserialitems':
            //global $def_coy;
            //et_global_connection($def_coy);
            set_global_connection();
            
            $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
            $end = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
            $catcode = (integer) (isset($_POST['catcode']) ? $_POST['catcode'] : $_GET['catcode']);
            $trans_id = (integer) (isset($_POST['trans_id']) ? $_POST['trans_id'] : $_GET['trans_id']);
            $reference = (integer) (isset($_POST['reference']) ? $_POST['reference'] : $_GET['reference']);
            $branchcode = (isset($_POST['branchcode']) ? $_POST['branchcode'] : $_GET['branchcode']);
            $querystr = (isset($_POST['query']) ? $_POST['query'] : $_GET['query']);

            if($start < 1)  $start = 0; if($end < 1) $end = 25;
            
            //$brcode = $db_connections[user_company()]["branch_code"];
            $sql = "SELECT *, sc.description AS category, md.mt_details_total_qty AS totalqty, md.mt_details_recvd_qty AS totalrcvd,
            sum(md.mt_details_total_qty)-sum(md.mt_details_recvd_qty) AS balance_total, ic.description AS item_description, 
            sm.description AS stock_description, md.mt_details_st_cost, md.originating_id 
            FROM ".TB_PREF."mt_header mh 
            LEFT JOIN ".TB_PREF."mt_details md ON mh.mt_header_id=md.mt_details_header_id 
            LEFT JOIN ".TB_PREF."stock_category sc ON mh.mt_header_category_id=sc.category_id 
            LEFT JOIN item_codes ic ON md.mt_details_item_code=ic.item_code 
            LEFT JOIN stock_master sm ON md.mt_details_stock_id=sm.stock_id 
            WHERE mh.mt_header_id=$trans_id GROUP BY md.mt_details_id";
            
            
            $result = db_query($sql, "could not get all Serial Items");
            //$total_result = get_all_serial($start,$end,$querystr,$catcode,$branchcode,true);
            //$total = db_num_rows($total_result);
            while ($myrow = db_fetch($result))
            {
                if($myrow["mt_details_status"]==2) {
                    $balance = number_format($myrow["totalqty"],2);
                }else{
                    $balance = number_format($myrow["balance_total"],2);
                }

                /*if($myrow["mt_details_status"]==0){
                    $status_msg='In-transit';
                }elseif($myrow["mt_details_status"]==1){
                    $status_msg='Partial';
                }elseif($myrow["mt_details_status"]==2){
                    $status_msg='Received';
                }*/
                $total_cost = ($balance * $myrow["mt_details_st_cost"]);
                
                $group_array[] = array('trans_id'=>$myrow["mt_header_id"],
                    'line_item' => $myrow["mt_details_id"],
                    'reference' => $myrow["mt_header_reference"],
                    'rrbrreference' => $myrow["mt_header_rrbranch_reference"],
                    'model' => $myrow["mt_details_stock_id"],
                    'item_code' => $myrow["mt_details_item_code"],
                    'trans_date' => sql2date($myrow["mt_header_date"]),
                    'category' => $myrow["category"],
                    'qty' => $balance,
                    'currentqty' => number_format($myrow["balance_total"],2),
                    'receivedqty' => number_format($myrow["totalrcvd"],2),
                    'category_id'=>$catcode,
                    'lot_no' => $myrow["mt_details_serial_no"],
                    'chasis_no' => $myrow["mt_details_chasis_no"],
                    'standard_cost' => number_format2($myrow["mt_details_st_cost"],2),
                    'stock_description' => $myrow["stock_description"],
                    'item_description' => $myrow["item_description"],
                    'status_msg'=>$myrow["mt_details_status"],
                    'status'=>$myrow["mt_details_status"],
                    'originating_id'=>$myrow["originating_id"],
                    'total_cost'=>$total_cost
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
            //$branchcode = (isset($_POST['branchcode']) ? $_POST['branchcode'] : $_GET['branchcode']);
            $branchcode = $db_connections[user_company()]["branch_code"];
            $querystr = (isset($_POST['query']) ? $_POST['query'] : $_GET['query']);

            $search_ref = (isset($_POST['search_ref']) ? $_POST['search_ref'] : $_GET['search_ref']);
            $fromdate = (isset($_POST['fromdate']) ? $_POST['fromdate'] : $_GET['fromdate']);
            $todate = (isset($_POST['todate']) ? $_POST['todate'] : $_GET['todate']);

            $fromdate_f = sql2date($fromdate);
            $todate_f = sql2date($todate);

            $result = get_all_merchandise_transfer($start,$limit,$querystr,$branchcode,$search_ref,$fromdate_f,$todate_f,false);
            $total_result = get_all_merchandise_transfer($start,$limit,$querystr,$branchcode,$search_ref,$fromdate_f,$todate_f,true);
            //$total_result = get_all_serial($start,$end,$querystr,$catcode,$branchcode,true);
            $total = DB_num_rows($result);

            while ($myrow = db_fetch($result))
            {
                if($myrow["totalqty"]==$myrow["totalreceived"]){
                    $status='Received';
                }elseif($myrow["totalreceived"]!=0) {
                    $status ='Partial';
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
            //global $def_coy;
            set_global_connection();
            
            $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
            $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
            $catcode = (integer) (isset($_POST['catcode']) ? $_POST['catcode'] : $_GET['catcode']);
            //$branchcode = (isset($_POST['branchcode']) ? $_POST['branchcode'] : $_GET['branchcode']);
            $fromlocation = (isset($_POST['fromlocation']) ? $_POST['fromlocation'] : $_GET['fromlocation']);
            $search_ref = (isset($_POST['search_ref']) ? $_POST['search_ref'] : $_GET['search_ref']);
            $branchcode = $db_connections[user_company()]["branch_code"];

            $fromdate = (isset($_POST['fromdate']) ? $_POST['fromdate'] : $_GET['fromdate']);
            $todate = (isset($_POST['todate']) ? $_POST['todate'] : $_GET['todate']);

            $fromdate_f = sql2date($fromdate);
            $todate_f = sql2date($todate);
            
            //if($start < 1)  $start = 0; if($end < 1) $end = 25;
            
            //$brcode = $db_connections[user_company()]["branch_code"];
            /*if($fromlocation!=null || isset($fromlocation)){
                $str_fromlocation=" AND  mh.mt_header_fromlocation='".$fromlocation."'";
            }else{
                $str_fromlocation="";
            }
            $sql = "SELECT *, sc.description as category, sum(md.mt_details_total_qty) as totalqty, 
            sum(md.mt_details_recvd_qty) as totalreceived, sum(md.mt_details_total_qty)-sum(md.mt_details_recvd_qty) as balance_qty 
            FROM ".TB_PREF."mt_header mh LEFT JOIN ".TB_PREF."mt_details md ON mh.mt_header_id=md.mt_details_header_id 
            LEFT JOIN ".TB_PREF."stock_category sc ON mh.mt_header_category_id=sc.category_id 
            WHERE mh.mt_header_tolocation='$branchcode' $str_fromlocation AND mh.mt_header_item_type = 'new'
            GROUP BY mh.mt_header_reference ORDER BY mh.mt_header_date DESC, mh.mt_header_id DESC";*/
            
            //$result = db_query($sql, "could not get all Serial Items");

            $result = get_all_receiving_item_branch($start,$limit,$branchcode,$fromlocation,$catcode,$search_ref,$fromdate_f,$todate_f,false);
            $total_result = get_all_receiving_item_branch($start,$limit,$branchcode,$fromlocation,$catcode,$search_ref,$fromdate_f,$todate_f,true);
            //$total_result = get_all_serial($start,$end,$querystr,$catcode,$branchcode,true);
            $total = DB_num_rows($result);

            while ($myrow = db_fetch($result))
            {
                if($myrow["totalqty"]==$myrow["totalreceived"]){
                   $status_msg='Received'; 
                }elseif ($myrow["totalreceived"] != 0) {
                    $status_msg='Partial';
                }else{
                    $status_msg='In-transit';
                }

                /*if($myrow["mt_type"] == 0) {
                    $manual_type = 'NO';
                }else{
                    $manual_type = 'YES';
                }*/
                $group_array[] = array('trans_id'=>$myrow["mt_header_id"],
                    'reference' => $myrow["mt_header_reference"],
                    'rrbrreference' => is_null($myrow["mt_header_rrbranch_reference"])?'':$myrow["mt_header_rrbranch_reference"],
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
                    'status'=>$myrow["mt_header_status"],
                    'type_rr'=>$myrow["mt_type"],
                    'post_date' => sql2date($myrow["post_date"])
                );                
            }
            
            $jsonresult = json_encode($group_array);
            echo '({"total":"'.DB_num_rows($total_result).'","result":'.$jsonresult.'})';
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

/*----Added by Robert 02/22/2022*/
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

