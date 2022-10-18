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
$page_security = 'SA_COMPLIMENTARYITEM';
$path_to_root = "..";
include_once($path_to_root . "/includes/ui/items_cart.inc");

include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/inventory/includes/stock_transfers_ui.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");
include_once($path_to_root . "/modules/serial_items/includes/modules_db.inc");
include_once($path_to_root . "/includes/cost_and_pricing.inc");
include_once($path_to_root . "/gl/includes/db/gl_db_accounts.inc");

add_js_ufile($path_to_root."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_css_file($path_to_root."/css/extjs-default.css");
add_js_ufile($path_to_root.'/js/complimentary_items.js');


//--------------------
if(isset($_GET['action']) || isset($_POST['action'])){
    $action = (isset($_GET['action']) ? $_GET['action'] : $_POST['action']);
}else{
    $action=null;
}


if(!is_null($action) || !empty($action)){
    switch ($action) {
        case 'NewTransfer';
            $brcode = $db_connections[user_company()]["branch_code"];
            
            handle_new_order(ST_COMPLIMENTARYITEM);
            $AdjDate = $_POST['AdjDate'];
            $_POST['ref']=$Refs->get_next(ST_COMPLIMENTARYITEM, null, array('date'=>$AdjDate, 'location'=> $brcode));
            
            echo '({"AdjDate":"'.$_POST['AdjDate'].'","branchcode":"'.$brcode.'","reference":"'.$_POST['ref'].'"})';
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
                //$AdjDate = $data['AdjDate'];
                $AdjDate = date("m/d/Y",strtotime($data['AdjDate']));
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
                $inventory_account = $data['inventory_account'];
                $brcode = $db_connections[user_company()]["branch_code"];
                $_SESSION['transfer_items']->from_loc=$brcode;

                if(!isset($_REQUEST['view'])){
                    //add_to_mt_order($_SESSION['transfer_items'], $model, $serialise_id, $serialised, $type_out, $transno_out,'new',$qty, 
                        //$rr_date, $AdjDate);
                    $line_item_header = rand();

                    if($serialised) {
                       $standard_cost=Get_System_Cost($model, $type_out, $transno_out);
                       $line_item = count($_SESSION['transfer_items']->line_items);

                       $_SESSION['transfer_items']->add_to_cart($line_item, $model, $qty, $standard_cost, $sdescription, $rr_date, 
                        '0000-00-00', $lot_no, $chasis_no, $color, $item_code, null, $type_out, $transno_out,'', 'new', '', '', '',
                        $line_item_header);

                       $_SESSION['transfer_items']->add_gl_item($inventory_account, '', '', -($standard_cost * $qty), 
                       $sdescription.' '.$color, '', '', $AdjDate, null, null, 0, null, null, 99, $line_item_header);
                    }else{
                        $standard_cost=Get_System_Cost($model, $type_out, $transno_out);
                        $line_item = count($_SESSION['transfer_items']->line_items);

                        $_SESSION['transfer_items']->add_to_cart($line_item, $model, $qty, $standard_cost, $sdescription, $rr_date, 
                        '0000-00-00', null, null, $color, $item_code, null, $type_out, $transno_out, '', 'new', '', '', '',
                        $line_item_header);

                        $_SESSION['transfer_items']->add_gl_item($inventory_account, '', '', -($standard_cost * $qty), 
                        $sdescription.' '.$color, '', '', $AdjDate, null, null, 0, 99, $line_item_header);
                    }
                } 
            }
            display_transfer_items_serial_compli($_SESSION['transfer_items'],$brcode,$AdjDate,$serialise_id);
            exit;
            break;
        case 'updateData';
            $arrayremove = array("[","]");
            $onlyconsonants = str_replace($arrayremove, "", html_entity_decode($_REQUEST['dataUpdate']));
            $filter = json_decode($onlyconsonants, true);

            $_SESSION['transfer_items']->update_cart_item($filter['id'], $filter['qty'],$filter['standard_cost'],'0000-00-00','0000-00-00',$filter['lot_no'],$filter['chasis_no']);

            $amount = $filter['qty'] * $filter['standard_cost'];
            $amount=-$amount;
            
            $_SESSION['transfer_items']->update_gl_amount($filter['id'] ,$amount);

            echo '({"success":true,"Update":"","id":"'.$filter['id'].'","qty":"'.$filter['qty'].'", "amount":"'.$amount.'"})';
            exit;
            break;
        case 'updateGLData';
            $arrayremove = array("[","]");
            $onlyconsonants = str_replace($arrayremove, "", html_entity_decode($_REQUEST['dataUpdate']));
            $filter = json_decode($onlyconsonants, true);
            //$amount = str_replace(',','',$filter['debit']);
            $db_amount = str_replace(',','',$filter['debit']);
            $cr_amount = str_replace(',','',$filter['credit']);
            if($db_amount>0){
                $amount=$db_amount;
            }else $amount=-$cr_amount;
            $_SESSION['transfer_items']->update_gl_item($filter['line'], $filter['code_id'],'','',$amount, $filter['memo'], null, $filter['person_id'], $filter['mcode'], $filter['master_file'],0, null,null, $filter['master_file_type']);
        
        
            echo '({"success":true,"Update":"","id":"'.$filter['id'].'","qty":"'.$amount.'"})';
            exit;
            break;
        
        case 'AddGLItem';
            $account_code=$_REQUEST['account_code'];
            $trans_date=sql2date($_REQUEST['AdjDate']);
            $_SESSION['transfer_items']->add_gl_item($account_code,'','', '0', '', '', '', $trans_date,null,null,0);
            display_gl_complimentaryitems($_SESSION['transfer_items']);
            exit;
            break;
        case 'display_gl_item';
            display_gl_complimentaryitems($_SESSION['transfer_items']);
            exit;
            break;
        case 'delete_gl_entry';
            $id=$_REQUEST['line_id'];
            $line_item=$_REQUEST['line_item'];
            $_SESSION['transfer_items']->remove_gl_item($id);
            $_SESSION['transfer_items']->remove_from_cart_line($line_item);
            //$array_rec = removeElementWithValue($_SESSION['transfer_items']->line_item, "line_item", $line_item);
            display_gl_complimentaryitems($_SESSION['transfer_items']);
            exit;
            break;
        case 'getTotalBalance';
            $totalDebit=$_SESSION['transfer_items']->gl_items_total_debit();
            $totalCredit=abs($_SESSION['transfer_items']->gl_items_total_credit());
            echo '({"TotalDebit":"'.number_format2($totalDebit,2).'","TotalCredit":"'.number_format2($totalCredit,2).'"})';
            exit;
            break;
        case 'masterfile';
            $masterfile_type=$_REQUEST['masterfile_type'];
            $person_id=$_REQUEST['person_id'];
            $account_code=$_REQUEST['account_code'];
            
            $type = is_subledger_account($account_code);
            //if ($type){
              
                
            if($masterfile_type==2)
                    $sql = "SELECT DISTINCT d.debtor_no as id, d.debtor_ref as ref, d.name AS name, 'Customer' as mastertype FROM "
                        .TB_PREF."debtors_master d,"
                            .TB_PREF."cust_branch c	WHERE d.debtor_no=c.debtor_no AND NOT d.inactive ORDER BY name asc";
            elseif($masterfile_type==3) 
                    $sql = "SELECT supplier_id as id, supp_ref as ref, supp_name as name, 'Supplier' as mastertype FROM "
                                    .TB_PREF."suppliers s
		              WHERE NOT s.inactive ORDER BY name asc";
            elseif($masterfile_type==6){
                $sql = "SELECT s.id, s.user_id as ref, s.real_name as name, 'Employee' as mastertype FROM "
                    .TB_PREF."users s
		              WHERE NOT s.inactive ORDER BY name asc";
            }
                                    
            $result = db_query($sql, "could not get all Serial Items");
            $total = db_num_rows($result);
            while ($myrow = db_fetch($result))
            {
                
                $group_array[] = array('id'=>$myrow["id"],
                    'namecaption' => htmlentities($myrow["name"]),
                    'ref' => $myrow["ref"],
                    'mastertype' => $myrow["mastertype"]
                );
                
            }
                                    
            $jsonresult = json_encode($group_array);
                                    //echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
            echo '({"person_id":"'.$person_id.'","masterfile_type":"'.$masterfile_type.'","account_code":"'.$account_code.'","total":"'.$total.'","result":'.$jsonresult.'})';
            exit;
            break;
        case 'updateje';
            $_POST['Index']=$_REQUEST['line_no'];
            $_POST['code_id']=$_REQUEST['account_code'];
            $_POST['AmountDebit']=$_REQUEST['debit_amount'];
            $_POST['LineMemo']=$_REQUEST['linememo'];
            $_POST['person_id']=$_REQUEST['masterfile'];
            $_POST['person_type_id']=$_REQUEST['masterfile_type'];
            $amount = input_num('AmountDebit');
            if ($_POST['person_type_id']==2)
            {
                $sql1 = "SELECT d.debtor_ref as ref, branch.branch_code as id, d.name FROM ".TB_PREF."cust_branch branch
			     LEFT JOIN ".TB_PREF."debtors_master d ON branch.debtor_no = d.debtor_no
		          WHERE d.debtor_no=".db_escape($_POST['person_id']);
            }elseif($_POST['person_type_id']==3){
                $sql1 = "SELECT supp_ref as ref, supp_name as name, '' as id
			     FROM ".TB_PREF."suppliers supp
			     WHERE supplier_id=".db_escape($_POST['person_id']);
            }elseif($_POST['person_type_id']==6){
                $sql1 = "SELECT u.user_id as ref, u.real_name as name, u.id
			     FROM ".TB_PREF."users u
			     WHERE u.id=".db_escape($_POST['person_id']);
            }
		    $result1 = db_query($sql1, 'cannot retrieve counterparty name');
		    $rowresult = db_fetch($result1);
		    $_SESSION['transfer_items']->update_gl_item($_POST['Index'], $_POST['code_id'],'','', $amount, $_POST['LineMemo'], null, $_POST['person_id'],$rowresult['ref'],$rowresult['name']);
		    display_gl_complimentaryitems($_SESSION['transfer_items'], $_POST['person_type_id']);
                
            exit;
            break;
        case 'SaveTransfer';
            set_global_connection();
            $AdjDate = sql2date($_POST['AdjDate']);
            $catcode = $_POST['catcode'];
            $totaldebit = $_POST['totaldebit'];
            $totalcredit = $_POST['totalcredit'];
            $person_type = $_POST['person_type'];
            $person_id_header = $_POST['person_id'];
            $masterfile = $_POST['masterfile'];

            $item_models = $_POST['item_models'];

            $total_rrdate = $_SESSION['transfer_items']->check_qty_avail_by_rrdate($_POST['AdjDate']);
            $brcode = $db_connections[user_company()]["branch_code"];

            $Dataongrid = stripslashes(html_entity_decode($_POST['Dataongrid']));
            $objDataGrid = json_decode($Dataongrid, true);

            $errmsg="";

            $isError = 0;
            if (count($objDataGrid) == 0){
               $errmsg = "Please Select Item";
            } else {
                foreach($objDataGrid as $value=>$data) 
                {
                    $stock_qty = $data['qty'];
                    $currentqty = $data['currentqty'];
                    $stock_id = $data['stock_id'];

                    if($stock_qty == 0) {
                        $isError = 1;
                        $errmsg="Quantity must not be zero.";
                        break;
                    }elseif($stock_qty > $currentqty) {
                        $isError = 1;
                        $errmsg = "Sorry, Quantity you entered '".$stock_qty."' is Greater than Available Quantity On Hand: '".$currentqty."'";
                        break;
                    }
                }

                if($person_type==2)
                    $sql = "SELECT d.debtor_no as id, d.debtor_ref as ref_gl, d.name AS name, 'Customer' as mastertype 
                    FROM ".TB_PREF."debtors_master d,".TB_PREF."cust_branch c 
                    WHERE d.debtor_no='$person_id_header'
                    AND NOT d.inactive ORDER BY name asc";
                elseif($person_type==3) 
                    $sql = "SELECT supplier_id as id, supp_ref as ref_gl, supp_name as name, 'Supplier' as mastertype 
                    FROM ".TB_PREF."suppliers s
                    WHERE supplier_id='$person_id_header' AND NOT s.inactive ORDER BY name asc";
                elseif($person_type==6){
                    $sql = "SELECT s.id, s.user_id as ref_gl, s.real_name as name, 'Employee' as mastertype 
                    FROM ".TB_PREF."users s
                    WHERE NOT s.inactive ORDER BY name asc";
                }

                $result = db_query($sql, 'cannot retrieve counterparty name');
                $rowresult = db_fetch($result);

                if(empty($_POST['FromStockLocation']) || $_POST['FromStockLocation']==''){
                    $errmsg="Select Location";
                    //echo '({"success":false,"errmsg":"'.$errmg.'"})';
                }elseif(empty($catcode) || $catcode==''){
                    $errmsg="Select Category";
                    //echo '({"success":false,"errmsg":"'.$errmg.'"})';
                }elseif($total_rrdate>0){
                    $errmsg="This document cannot be processed because there is insufficient quantity for items marked.";
                    //echo '({"success":false,"errmsg":"'.$errmg.'"})';
                    
                }elseif($totaldebit==$totalcredit && ($totaldebit!=0 || $totalcredit!=0) && $isError != 1){
                    //$_POST['ref']=$Refs->get_next(ST_COMPLIMENTARYITEM, null, array('date'=>$AdjDate, 'location'=> get_post('FromStockLocation')));
                    $trans_no = add_stock_Complimentary_Items($_SESSION['transfer_items']->line_items, $_POST['FromStockLocation'], $AdjDate, $_POST['ref'], $_POST['memo_'],$catcode, $person_type, $person_id_header, $masterfile);
                    

                    $totalline=count($objDataGrid);
                    //$total_gl = 0;
                    foreach($_SESSION['transfer_items']->gl_items as $gl)
                    {
                        if (!isset($gl->date))
                            $gl->date = $_SESSION['transfer_items']->tran_date;
                            
                        /*$total += add_gl_trans($_SESSION['transfer_items']->trans_type, $trans_no, $gl->date, $gl->code_id,'','', $gl->reference, $gl->amount,null,$gl->person_type_id, $gl->person_id,'',0,$rowresult['id'],$masterfile);*/

                        $total += add_adj_gl_complimentary($_SESSION['transfer_items']->trans_type, $trans_no, $gl->code_id, $gl->reference, $gl->amount,
                            $rowresult['id'], $masterfile, $_POST['ref']);
                            
                    }            
                    new_doc_date($AdjDate);
                    $_SESSION['transfer_items']->clear_items();
                    unset($_SESSION['transfer_items']);
                }
            }
            echo '({"success":true,"total":"'.$totalline.'","reference":"'.$_POST['ref'].'","trans_no":"'.$trans_no.'",
                "errmsg":"'.$errmsg.'"})';
            exit;
            break;
        case 'receive_header';
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
                    $standard_cost = $item['mt_details_st_cost'];
                    
                    $_SESSION['transfer_items']->add_to_cart(count($_SESSION['transfer_items']->line_items), $model, $item['mt_details_total_qty'],$standard_cost,$item['sdescription'],'0000-00-00','0000-00-00',$item['mt_details_serial_no'],$item['mt_details_chasis_no'],$item['idescription'],$item['item_code'],$line_item);
                //}
            }
            
            
            display_transfer_items_serial_compli($_SESSION['transfer_items'],$_POST['FromStockLocation'],$AdjDate);
            
            //echo '({"trans_date":"'.sql2date($trans_date).'","reference":"'.$reference.'","trans_id":"'.$trans_id.'","line_item":"'.$line_item.'","RRReference":"'.$_POST['ref'].'"})';
            exit;
            break;
        case 'save_rrbr';
            $brcode = $db_connections[user_company()]["branch_code"];
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
            
            //display_transfer_items_serial($_SESSION['transfer_items'],$_POST['FromStockLocation'],$AdjDate);
            //$_SESSION['transfer_items']->clear_items();
            //unset($_SESSION['transfer_items']);
            //exit;
            //break;
            $_POST['ref']=$Refs->get_next(ST_RRBRANCH, null, array('date'=>$AdjDate, 'location'=> $brcode));
            $trans_no = add_stock_rrBranch($_SESSION['transfer_items']->line_items, $_POST['FromStockLocation'], $_POST['ToStockLocation'], $AdjDate, $_POST['ref'], '',$catcode,$MTreference);
            //new_doc_date($AdjDate);
            $_SESSION['transfer_items']->clear_items();
            unset($_SESSION['transfer_items']);
            echo '({"total":"'.$AdjDate.'","reference":"'.$_POST['ref'].'"})';
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
            $_SESSION['transfer_items']->remove_from_cart($line_item);
            $_SESSION['transfer_items']->remove_gl_line_item($line_item);
            $_SESSION['transfer_items']->remove_from_cart_line($line_item);
            
            display_transfer_items_serial_compli($_SESSION['transfer_items'],$brcode,$AdjDate,$serialise_id);
            //echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
            exit;
            break;
        case 'getConfig':
            $brcode = $db_connections[user_company()]["branch_code"];
            $brname = $db_connections[user_company()]["name"];
            echo '({"branchcode":"'.$brcode.'","branch_name":"'.$brname.'"})';
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
            
            //$brcode = $db_connections[user_company()]["branch_code"];
            $result = get_all_stockmoves($start,$limit,$querystr,$catcode,$branchcode,false,'',$trans_date, $querystrserial);
            $total_result = get_all_stockmoves($start,$limit,$querystr,$catcode,$branchcode,true,'',$trans_date, $querystrserial);

            $total = DB_num_rows($result);

            while ($myrow = db_fetch($result))
            {
                
                if($myrow["serialised"]){
                    $qty=$myrow["qty_serialise"];
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
                            'inventory_account'=>$myrow["dflt_inventory_act"]
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
            $branchcode = (isset($_POST['branchcode']) ? $_POST['branchcode'] : $_GET['branchcode']);
            $reference = (isset($_POST['reference']) ? $_POST['reference'] : $_GET['reference']);
            $querystr = (isset($_POST['query']) ? $_POST['query'] : $_GET['query']);
            $trans_id = (integer) (isset($_POST['trans_no']) ? $_POST['trans_no'] : $_GET['trans_no']);
            //$brcode = $db_connections[user_company()]["branch_code"];
            
            if($start < 1)	$start = 0;	if($end < 1) $end = 25;
            
            //$brcode = $db_connections[user_company()]["branch_code"];
            //$result = get_all_stockmoves($start,$end,$querystr,$catcode,$branchcode,false,$reference);
            //$total_result = get_all_stockmoves($start,$end,$querystr,$catcode,$branchcode,true,$reference);
            
            $sql = "SELECT move.trans_no, move.stock_id, move.reference, move.lot_no, move.chassis_no, move.qty, 
            move.category_id, move.standard_cost, code.description, items.id, master.description AS description_item
            FROM ".TB_PREF."stock_adjustment move 
            LEFT JOIN ".TB_PREF."item_codes code ON code.item_code = move.color_code
            LEFT JOIN ".TB_PREF."complimentary_items items ON items.reference = move.reference
            LEFT JOIN ".TB_PREF."stock_master master ON master.stock_id = move.stock_id
            WHERE move.reference = '$reference'";

            if($catcode!=0){
                $sql.=" AND move.category_id = $catcode";
            }
            if($reference!=''){
                $sql.=" AND move.reference = '".$reference."'";
                
            }
            if($all){
                
            }else{
                //$sql.=" LIMIT $start,$end";
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
                    'subtotal_cost' => $myrow["standard_cost"] * -$myrow["qty"]

                );
            }
            
            $jsonresult = json_encode($group_array);
            echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
            exit;
            break;
        case 'GLEntryItems':
            
            $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
            $end = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
            $catcode = (integer) (isset($_POST['catcode']) ? $_POST['catcode'] : $_GET['catcode']);
            $branchcode = (isset($_POST['branchcode']) ? $_POST['branchcode'] : $_GET['branchcode']);
            $reference = (isset($_POST['reference']) ? $_POST['reference'] : $_GET['reference']);
            $querystr = (isset($_POST['query']) ? $_POST['query'] : $_GET['query']);
            $trans_id = (integer) (isset($_POST['trans_no']) ? $_POST['trans_no'] : $_GET['trans_no']);

            if($start < 1)  $start = 0; if($end < 1) $end = 25;
            
            $sql = "SELECT gl.sa_trans_no, gl.sa_adj_type, gl.sa_reference, gl.account, gl.amount, 
            gl.mcode, gl.master_file, gl.memo_, chart.account_name
            FROM ".TB_PREF."stock_adjustment_gl gl
            LEFT JOIN ".TB_PREF."chart_master chart ON gl.account = chart.account_code
            WHERE gl.sa_reference = '$reference'";

            if($reference!=''){
                $sql.=" AND gl.sa_reference = '".$reference."'";
                
            }
            if($all){
                
            }else{
                //$sql.=" LIMIT $start,$end";
            }
    
            $result=db_query($sql, "could not get all Serial Items");
            
            $total = db_num_rows($result);
            
            while ($myrow = db_fetch($result))
            {  
                if($myrow["amount"] > 0) {
                    $debit = $myrow['amount'];
                    $credit = "";
                }else{
                    $credit = $myrow['amount'];
                }      
                $group_array[] = array('trans_no' => $myrow["sa_trans_no"],
                    'account_code_gl' => $myrow["account"],
                    'account_name_gl' => $myrow["account_name"],
                    'mcode_gl' => $myrow["mcode"],
                    'masterfile_gl' => $myrow["master_file"],
                    'debit_gl' => $debit<=0?"":$debit,
                    'credit_gl' => $credit==0?"":-$credit,
                    'memo_gl' => $myrow["memo_"]
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
                    'rrbrreference' => $myrow["mt_header_rrbranch_reference"],
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
            
            $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
            $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
            $catcode = (integer) (isset($_POST['catcode']) ? $_POST['catcode'] : $_GET['catcode']);
            //$branchcode = (isset($_POST['branchcode']) ? $_POST['branchcode'] : $_GET['branchcode']);
            $branchcode = $db_connections[user_company()]["branch_code"];
            $querystr = (isset($_POST['query']) ? $_POST['query'] : $_GET['query']);

            $comp_stat = (isset($_POST['comp_stat']) ? $_POST['comp_stat'] : $_GET['comp_stat']);
            $search_ref = (isset($_POST['search_ref']) ? $_POST['search_ref'] : $_GET['search_ref']);
            
            $result = get_all_complimentary_item($start,$limit,$querystr,$branchcode,$comp_stat,$search_ref,false,'');
            $total_result = get_all_complimentary_item($start,$limit,$querystr,$branchcode,$comp_stat,$search_ref,true,'');
            //$total_result = get_all_serial($start,$end,$querystr,$catcode,$branchcode,true);
            $total = DB_num_rows($result);
            while ($myrow = db_fetch($result))
            {
                /*if($myrow["compli_status"]== "Draft"){
                    $status_msg='For Approval';
                }elseif($myrow["compli_status"]== "Approved") {
                    $status_msg='Approved';
                }else{
                    $status_msg='Closed';
                }*/

                if($myrow["date_approved"] == '0000-00-00') {
                    $trandate = sql2date($myrow["tran_date"]);
                }else{
                    $trandate = sql2date($myrow["date_approved"]);
                }
                $group_array[] = array('trans_no'=>$myrow["trans_no"],
                    'reference' => $myrow["reference"],
                    'tran_date' => $trandate,
                    'remarks' => $myrow["remarks"],
                    'loc_code' => $myrow["loc_code"],
                    'loc_name' => $myrow["loc_name"],
                    'category_id' => $myrow["category_id"],
                    'category_name' => $myrow["category_name"],
                    'qty' => number_format(abs($myrow["total_item"]),2),
                    'status' => $myrow["compli_status"]
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
                    'rrbrreference' => $myrow["mt_header_rrbranch_reference"],
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
        case 'coa':
            $brcode = $db_connections[user_company()]["branch_code"];
            $brname = $db_connections[user_company()]["name"];
            $_POST['class_type']=$_REQUEST['class_type'];
            $_POST['description']=isset($_REQUEST['query'])?$_REQUEST['query']:$_REQUEST['description'];
            $result = get_chart_accounts_search_new(get_post("class_type"),get_post("description"), true);
            $total = db_num_rows($result);
            while ($myrow = db_fetch($result))
            {
                $group_array[] = array('account_code'=>$myrow["account_code"],
                    'account_name' => $myrow["account_name"],
                        'name' => $myrow["name"],
                        'class_id' => $myrow["class_id"],
                    'class_name' => $myrow["class_name"]
                    );
                
            }
            
            $jsonresult = json_encode($group_array);
            echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
            
            exit;
            break;
        case 'coa_classtype';
            $result = get_chart_accounts_class_type();
            $total = db_num_rows($result);
            $group_array[]=array('id'=>'0','name'=>'All','class_id'=>'0');
            while ($myrow = db_fetch($result))
            {
                $group_array[] = array('id'=>$myrow["id"],
                    'name' => $myrow["name"],
                    'class_id' => $myrow["class_id"]
                );
                
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
            $approval_value = (isset($_POST['value']) ? $_POST['value'] : $_GET['value']);
            $total=0;
            if($approval_value=='yes'){
                $sql = "SELECT trans_no, type, reference FROM ".TB_PREF."stock_adjustment
                WHERE type='".ST_COMPLIMENTARYITEM."' AND reference=".db_escape($reference);
                 
                $result=db_query($sql, "could not get all Items");
                
                $total = db_num_rows($result);

                while ($myrow = db_fetch($result))
                {
                    update_stock_adjustment_status($myrow["reference"], $myrow["type"]);
                    update_compli_item_status($myrow["reference"]);
                }
            }
            echo '({"total":"'.$total.'","ApprovalStatus":"'.$approval_value.'"})';
            exit;
            break;
        case 'disapproval':
            $reference = (isset($_POST['reference']) ? $_POST['reference'] : $_GET['reference']);
            $approval_value = (isset($_POST['value']) ? $_POST['value'] : $_GET['value']);
            $total=0;
            if($approval_value=='yes'){
                $sql = "SELECT trans_no, type, reference FROM ".TB_PREF."stock_adjustment
                WHERE type='".ST_COMPLIMENTARYITEM."' AND reference=".db_escape($reference);
                 
                $result=db_query($sql, "could not get all Items");
                
                $total = db_num_rows($result);

                while ($myrow = db_fetch($result))
                {
                    update_stock_adjustment_status_disaaproved($myrow["reference"], $myrow["type"]);
                    update_compli_item_status_disaaproved($myrow["reference"]);
                }
            }
            echo '({"total":"'.$total.'","ApprovalStatus":"'.$approval_value.'"})';
            exit;
            break;
        case 'posting_transaction':
            
            $reference = (isset($_POST['reference']) ? $_POST['reference'] : $_GET['reference']);
            $trans_no = (integer) (isset($_POST['trans_no']) ? $_POST['trans_no'] : $_GET['trans_no']);
            $approval_value = (isset($_POST['value']) ? $_POST['value'] : $_GET['value']);

            if($approval_value=='yes'){
                $PostDate = sql2date($_POST['PostDate']);
                $PostDated = date('Y-m-d', strtotime($_POST['PostDate']));
                
                $result = get_transaction_from_stock_adjusment(ST_COMPLIMENTARYITEM, $trans_no, $reference);
                $result2 = get_transaction_from_stock_adjusment_check(ST_COMPLIMENTARYITEM, $trans_no, $reference);

                $errmsg="";
                $isError = 0;
                while ($myrow01 = db_fetch($result2))
                {
                    $stock_id = $myrow01['stock_id'];
                    $lot_no = $myrow01['lot_no'];

                    $qoh = get_qoh_on_date_new($myrow01['trans_type_out'], $myrow01['trans_no_out'], $myrow01['stock_id'], $myrow01['loc_code'], $PostDate, 
                        $myrow01['lot_no']);

                    if($qoh == 0) {
                        $isError = 1;
                        $errmsg="Sorry, Can't Proceed! There is not enough quantity in stock for Stock ID: ".$stock_id."  and Serial #: ".$lot_no."";
                        break;
                    }
                }

                if($isError != 1){
                    $totalitem = db_num_rows($result);
                    while ($myrow = db_fetch($result))
                    {
                        //$date = sql2date($myrow["tran_date"]);
                        add_stock_move(ST_COMPLIMENTARYITEM, $myrow["stock_id"], $myrow["trans_no"], $myrow["loc_code"], $PostDate, $myrow["reference"], $myrow["qty"],
                            $myrow["standard_cost"], 0, $myrow["lot_no"], $myrow["chassis_no"], $myrow["category_id"], $myrow["color_code"], $myrow["trans_type_out"],
                            $myrow["trans_no_out"]);

                        update_stock_adjustment_status_post($myrow["reference"], ST_COMPLIMENTARYITEM, $PostDated);
                        update_compli_item_status_post($myrow["reference"]);
                    }

                    $result1 = get_transaction_from_stock_adjusment_gl(ST_COMPLIMENTARYITEM, $trans_no, $reference);

                    $totalgl = db_num_rows($result1);
                    while ($myrow1 = db_fetch($result1))
                    {
                        //$date = sql2date($myrow1["tran_date"]);
                        add_gl_trans(ST_COMPLIMENTARYITEM, $myrow1["sa_trans_no"], $PostDate, $myrow1["account"], '', '', $myrow1["memo_"], $myrow1["amount"], null, null, 
                            null, '', 0, $myrow1["mcode"], $myrow1["master_file"]);
                    }
                }
            }
            echo '({"success":true,"totalItem":"'.$totalitem.'","totalGL":"'.$totalgl.'","reference":"'.$reference.'","trans_no":"'.$trans_no.'",
                "errmsg":"'.$errmsg.'"})';
        exit;
        break;
    }
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

function handle_new_order($transtype=ST_COMPLIMENTARYITEM)
{
	if (isset($_SESSION['transfer_items']))
	{
		$_SESSION['transfer_items']->clear_items();
		unset ($_SESSION['transfer_items']);
	}

	$_SESSION['transfer_items'] = new items_cart($transtype);
    $_SESSION['transfer_items']->fixed_asset = isset($_GET['FixedAsset']);
    $_POST['AdjDate'] =  new_doc_date();
	if (!is_date_in_fiscalyear($_POST['AdjDate']))
		$_POST['AdjDate'] = end_fiscalyear();
		$_SESSION['transfer_items']->tran_date = $_SESSION['transfer_items']->doc_date = $_SESSION['transfer_items']->event_date = $_POST['AdjDate'];	
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
	if (!check_reference($_POST['ref'], ST_COMPLIMENTARYITEM))
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

//---------------------------------------------------------------------------------
//Added by Herald [12-18-2020] for new MT Module
function display_gl_complimentaryitems(&$order)
{
    global $path_to_root;
    
    foreach ($order->gl_items as $line => $item)
    {
        $debit=0;
        $credit=0;
        if ($item->amount > 0)
        {
            $debit=abs($item->amount);
        }
        else
        {
            $credit=abs($item->amount);
        }
        if($item->master_file_type==2){
           $mastertype='Customer'; 
        }elseif($item->master_file_type==3){
            $mastertype='Supplier';
        }elseif($item->master_file_type==6){
            $mastertype='Employee';
        }
        $group_array[] = array('code_id'=>$item->code_id,
            'description'=>$item->description,
            'line'=>$line,
            'class_id'=>'',
            'branch_id'=>is_null($item->branch_id)?'':$item->branch_id,
            'person_name'=>is_null($item->master_file)?'':$item->master_file,
            'person_id'=>is_null($item->mcode)?'':$item->mcode,
            'person_type_id'=>is_null($item->person_type_id)?'':$item->person_type_id,
            'actualprice'=>$item->amount,
            'debit' => $debit,
            'credit' => $credit,
            'mcode'=>is_null($item->mcode)?'':$item->mcode,
            'master_file'=>is_null($item->master_file)?'':$item->master_file,
            'memo'=> $item->reference,
            'mastertype' => is_null($mastertype)?'':$mastertype,
            'master_file_type'=>$item->master_file_type,
            'line_item' => $item->line_item,
            'trans_date' => $item->date
        );
        
        
    }
    
    $jsonresult = json_encode($group_array);
    echo '({"success":"true","result":'.$jsonresult.'})';
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

