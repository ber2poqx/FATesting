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
$page_security = 'SA_RRBRANCH';
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
$action = (isset($_GET['action']) ? $_GET['action'] : $_POST['action']);

if(!is_null($action) || !empty($action)){
    switch ($action) {
        case 'NewTransfer';
            //$AdjDate = $_POST['AdjDate'];
            $brcode = $db_connections[user_company()]["branch_code"];
            handle_new_order();
            //display_transfer_items_serial($_SESSION['transfer_items'],$brcode,$AdjDate);
            echo '({"AdjDate":"'.$_POST['AdjDate'].'","branchcode":"'.$brcode.'"})';
            exit;
            break;
        case 'AddItem';
            $serialise_id = $_REQUEST['serialise_id'];
            $AdjDate = $_POST['AdjDate'];
            $model = $_REQUEST['model'];
            $brcode = $db_connections[user_company()]["branch_code"];
            $_SESSION['transfer_items']->from_loc=$brcode;
            //echo "ID:".$serialise_id;
            add_to_order_new($_SESSION['transfer_items'], $model, $serialise_id);
            display_transfer_items_serial($_SESSION['transfer_items'],$brcode,$AdjDate,$serialise_id);
            //echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
            exit;
            break;
        case 'SaveTransfer';
            //$serialise_id = $_REQUEST['serialise_id'];
            set_global_connection();
            $AdjDate = sql2date($_POST['AdjDate']);
            //$model = $_REQUEST['model'];
            //$brcode = $db_connections[user_company()]["branch_code"];
            //echo "ID:".$serialise_id;
            //add_to_order_new($_SESSION['transfer_items'], $model, $serialise_id);
            //display_transfer_items_serial($_SESSION['transfer_items'],$brcode,$AdjDate,$serialise_id);
            $catcode = $_POST['catcode'];
            $_POST['ref']=$Refs->get_next(ST_MERCHANDISETRANSFER, null, array('date'=>get_post('AdjDate'), 'location'=> get_post('FromStockLocation')));
            $trans_no = add_stock_merchandise_transfer($_SESSION['transfer_items']->line_items, $_POST['FromStockLocation'], $_POST['ToStockLocation'], $AdjDate, $_POST['ref'], $_POST['memo_'],$catcode);
            new_doc_date($AdjDate);
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
            echo '({"branchcode":"'.$brcode.'"})';
            exit;
            break;
        
        case 'serial_items':
            
            $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
            $end = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
            $catcode = (integer) (isset($_POST['catcode']) ? $_POST['catcode'] : $_GET['catcode']);
            $branchcode = (isset($_POST['branchcode']) ? $_POST['branchcode'] : $_GET['branchcode']);
            $querystr = (isset($_POST['query']) ? $_POST['query'] : $_GET['query']);
            
            if($start < 1)	$start = 0;	if($end < 1) $end = 25;
            
            //$brcode = $db_connections[user_company()]["branch_code"];
            $result = get_all_stockmoves($start,$end,$querystr,$catcode,$branchcode);
            $total_result = get_all_stockmoves($start,$end,$querystr,$catcode,$branchcode,true);
            $total = db_num_rows($total_result);
            while ($myrow = db_fetch($result))
            {
                if($myrow["serialise_qty"]>0){
                    $serialise_id = get_serialise_id($myrow["serialise_item_code"],$myrow["serialise_lot_no"]);
                    //$tandard_cost = Get_StandardCost_Plcy($branchcode,$catcode,$myrow["model"]);
                    $group_array[] = array('serialise_id'=>$serialise_id,
                        'color' => $myrow["serialise_item_code"],
                        'stock_description' => $myrow["stock_description"],
                        'item_description' => $myrow["item_description"],
                        'model' => $myrow["model"],
                        'standard_cost' => number_format2($myrow["standard_cost"],2),
                        'qty' => $myrow["serialise_qty"],
                        'category_id'=>$catcode,
                        'lot_no' => $myrow["serialise_lot_no"],
                        'chasis_no' => $myrow["serialise_chasis_no"],
                        'serialise_loc_code'=>$myrow["serialise_loc_code"]
                    );
                }
                
                
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
            //$branchcode = (isset($_POST['branchcode']) ? $_POST['branchcode'] : $_GET['branchcode']);
            $reference = (isset($_POST['reference']) ? $_POST['reference'] : $_GET['reference']);
            $querystr = (isset($_POST['query']) ? $_POST['query'] : $_GET['query']);
            $trans_id = (integer) (isset($_POST['trans_id']) ? $_POST['trans_id'] : $_GET['trans_id']);
            $brcode = $db_connections[user_company()]["branch_code"];
            
            if($start < 1)	$start = 0;	if($end < 1) $end = 25;
            
            //$brcode = $db_connections[user_company()]["branch_code"];
            //$result = get_all_stockmoves($start,$end,$querystr,$catcode,$branchcode,false,$reference);
            //$total_result = get_all_stockmoves($start,$end,$querystr,$catcode,$branchcode,true,$reference);
            
            
            $sql = "SELECT *, sc.description as category, md.mt_details_total_qty as totalqty, ic.description as item_description, sm.description as stock_description FROM ".TB_PREF."mt_header mh LEFT JOIN ".TB_PREF."mt_details md ON mh.mt_header_id=md.mt_details_header_id LEFT JOIN ".TB_PREF."stock_category sc ON mh.mt_header_category_id=sc.category_id LEFT JOIN item_codes ic ON md.mt_details_item_code=ic.item_code LEFT JOIN stock_master sm ON md.mt_details_stock_id=sm.stock_id WHERE mh.mt_header_id=$trans_id";

            
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
            //echo $sql;
            //die();
            $result=db_query($sql, "could not get all Serial Items");
            
            $total = db_num_rows($result);
            
            
            while ($myrow = db_fetch($result))
            {
                //if($myrow["serialise_qty"]>0){
                    //$serialise_id = get_serialise_id($myrow["serialise_item_code"],$myrow["serialise_lot_no"]);
                    //$tandard_cost = Get_StandardCost_Plcy($branchcode,$catcode,$myrow["model"]);
                    
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
                        'serialise_loc_code'=>$myrow["serialise_loc_code"]
                    );
                //}
                
                
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
                if($myrow["mt_header_status"]==0){
                    $status_msg='In-transit';
                }else{
                    $status_msg='';
                }
                
                $group_array[] = array('trans_id'=>$myrow["mt_header_id"],
                    'model' => $myrow["mt_details_stock_id"],
                    'trans_date' => sql2date($myrow["mt_header_date"]),
                    'category' => $myrow["category"],
                    'qty' => number_format($myrow["totalqty"],2),
                    'category_id'=>$catcode,
                    'lot_no' => $myrow["mt_details_serial_no"],
                    'chasis_no' => $myrow["mt_details_chasis_no"],
                    'stock_description' => $myrow["stock_description"],
                    'item_description' => $myrow["item_description"],
                    'status_msg'=>$status_msg,
                    'status'=>$myrow["mt_header_status"]
                );
                
            }
            
            $jsonresult = json_encode($group_array);
            echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
            exit;
            break;
            
        case 'view':
            global $def_coy;
            set_global_connection($def_coy);
            
            $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
            $end = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
            $catcode = (integer) (isset($_POST['catcode']) ? $_POST['catcode'] : $_GET['catcode']);
            $branchcode = (isset($_POST['branchcode']) ? $_POST['branchcode'] : $_GET['branchcode']);
            $querystr = (isset($_POST['query']) ? $_POST['query'] : $_GET['query']);
            
            if($start < 1)	$start = 0;	if($end < 1) $end = 25;
            
            //$brcode = $db_connections[user_company()]["branch_code"];
            //$sql = "SELECT *,count(sm.qty) as totalqty, sc.description as category FROM ".TB_PREF."stock_moves sm LEFT JOIN stock_category sc ON sm.category_id=sc.category_id WHERE sm.loc_code<>'$branchcode' and sm.type='".ST_MERCHANDISETRANSFER."' GROUP BY sm.reference order by trans_id desc,tran_date desc";
                       
            $sql = "SELECT *, sc.description as category, sum(md.mt_details_total_qty) as totalqty, loc.location_name FROM ".TB_PREF."mt_header mh LEFT JOIN ".TB_PREF."mt_details md ON mh.mt_header_id=md.mt_details_header_id LEFT JOIN ".TB_PREF."stock_category sc ON mh.mt_header_category_id=sc.category_id LEFT JOIN ".TB_PREF."locations loc ON mh.mt_header_tolocation=loc.loc_code WHERE mh.mt_header_fromlocation='$branchcode' GROUP BY mh.mt_header_id desc";
            
            $result = db_query($sql, "could not get all Serial Items");
            //$total_result = get_all_serial($start,$end,$querystr,$catcode,$branchcode,true);
            //$total = db_num_rows($total_result);
            while ($myrow = db_fetch($result))
            {
                $group_array[] = array('trans_id'=>$myrow["mt_header_id"],
                    'reference' => $myrow["mt_header_reference"],
                    'tran_date' => sql2date($myrow["mt_header_date"]),
                    'loc_code' => $myrow["mt_header_tolocation"],
                    'loc_name' => $myrow["location_name"],
                    'remarks' => $myrow["mt_header_comments"],
                    'category' => $myrow["category"],
                    'qty' => number_format($myrow["totalqty"],2),
                    'category_id'=>$myrow["mt_header_category_id"],
                    'lot_no' => $myrow["serialise_lot_no"],
                    'chasis_no' => $myrow["serialise_chasis_no"],
                    'serialise_loc_code'=>$myrow["serialise_loc_code"],
                    'status'=>'In-transit'
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
            $branchcode = (isset($_POST['branchcode']) ? $_POST['branchcode'] : $_GET['branchcode']);
            $querystr = (isset($_POST['query']) ? $_POST['query'] : $_GET['query']);
            
            if($start < 1)	$start = 0;	if($end < 1) $end = 25;
            
            //$brcode = $db_connections[user_company()]["branch_code"];
            $sql = "SELECT *, sc.description as category, sum(md.mt_details_total_qty) as totalqty FROM ".TB_PREF."mt_header mh LEFT JOIN ".TB_PREF."mt_details md ON mh.mt_header_id=md.mt_details_header_id LEFT JOIN ".TB_PREF."stock_category sc ON mh.mt_header_category_id=sc.category_id where mh.mt_header_tolocation='$branchcode' GROUP BY mh.mt_header_reference";
            
            
            $result = db_query($sql, "could not get all Serial Items");
            //$total_result = get_all_serial($start,$end,$querystr,$catcode,$branchcode,true);
            //$total = db_num_rows($total_result);
            while ($myrow = db_fetch($result))
            {
                if($myrow["mt_header_status"]==0){
                   $status_msg='In-transit'; 
                }else{
                    $status_msg='';
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

function handle_new_order()
{
	if (isset($_SESSION['transfer_items']))
	{
		$_SESSION['transfer_items']->clear_items();
		unset ($_SESSION['transfer_items']);
	}

	$_SESSION['transfer_items'] = new items_cart(ST_MERCHANDISETRANSFER);
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

