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

$page_security = 'SA_REQUESTSTOCKDELIVERY';

$path_to_root = "..";
include_once($path_to_root . "/includes/ui/items_cart.inc");

include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/inventory/includes/stock_transfers_ui.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");
include_once($path_to_root . "/modules/serial_items/includes/modules_db.inc");
include_once($path_to_root . "/includes/cost_and_pricing.inc");

add_access_extensions();
simple_page_mode(true);

add_js_ufile($path_to_root."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root.'/js/request_stock_delivery.js');


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
            handle_new_order(ST_REQUESTSTOCKDELIVER);
            echo json_encode(array('AdjDate'=>$_POST['AdjDate'],'branchcode'=>$brcode));
            exit;
            break;
        case 'AddItem';
            $DataOnGrid = stripslashes(html_entity_decode($_REQUEST['DataOnGrid']));
            $objDataGrid = json_decode($DataOnGrid, true);
            //var_dump($objDataGrid);
            foreach($objDataGrid as $value=>$data) 
            {
                $category_id = $data['category'];
                $AdjDate = $data['AdjDate'];
                $model = $data['model'];
                $item_code = $data['item_code'];
                $sdescription = $data['sdescription'];
                $color = $data['color'];
                $serialised = $data['serialised'];
                $qty = $data['qty'];
                $brand_id = $data['brand_id'];
                $brcode = $db_connections[user_company()]["branch_code"];
                $_SESSION['transfer_items']->from_loc=$brcode;

                $qty = 0;
                
                if(!isset($_REQUEST['view'])){
                    $line_item_header = rand();
                    if($serialised) {           
                        $line_item = count($_SESSION['transfer_items']->line_items);
                        $_SESSION['transfer_items']->add_to_cart($line_item, $model, $qty, 0, $sdescription, '0000-00-00', 
                            '0000-00-00', '', '', $color, $item_code, null, 0, 0, '', 'new', '', '', '',
                        $line_item_header, null, 0, $brand_id);
                    }else{                       
                        $line_item = count($_SESSION['transfer_items']->line_items);
                        $_SESSION['transfer_items']->add_to_cart($line_item, $model, $qty, 0, $sdescription, '0000-00-00', 
                            '0000-00-00', '', '', $color, $item_code, null, 0, 0, '', 'new', '', '', '', 
                        $line_item_header, null, 0, $brand_id);
                    } 
                }
            }
            display_request_stock($_SESSION['transfer_items'], $brcode, $AdjDate);
            exit;
            break;
        case 'SaveTransfer';
            set_global_connection();

            $requestdata = stripslashes(html_entity_decode($_POST['requestdata']));
            $objDataGrid = json_decode($requestdata, true);
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

                    if($counteritem<=0){
                        $isError = 1;                   
                        $message="No Item Selected";                     
                        break;
                    }elseif($stock_qty == 0) {
                        $isError = 1;
                        $message="Quantity must not be zero.";
                        break;
                    }
                }

                foreach ($_SESSION['transfer_items']->line_items AS $item)
                {         
                    if($item->quantity == 0){
                        $isError = 1;
                        $message="Quantity must not be zero.";                     
                        break;
                    }
                }

                if($isError != 1){
                    $brcode = $db_connections[user_company()]["branch_code"];
                    $AdjDate = sql2date($_POST['AdjDate']); 
                    $AdjDated = date('Y-m-d', strtotime($_POST['AdjDate']));             
                    $_POST['ref'] = $Refs->get_next(ST_REQUESTSTOCKDELIVER, null, array('date'=>$AdjDate, 'location'=> get_post('FromStockLocation')));

                    $rsd_id = add_rsd_header($AdjDated, ST_REQUESTSTOCKDELIVER, $_POST['ref'], $brcode, $_POST['ToStockLocation'], $_POST['catcode'], $_POST['memo_'], $_POST['servedby']);

                    foreach ($_SESSION['transfer_items']->line_items AS $items)
                    { 
                        add_rsd_details($rsd_id, $items->item_code, $items->item_description, $items->quantity, $items->brand_id);
                    }                  
                }
            }
            echo '({"success":"true","reference":"'.$_POST['ref'].'","message":"'.$message.'"})';
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
        case 'view':
                
                $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
                $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
                $catcode = (integer) (isset($_POST['catcode']) ? $_POST['catcode'] : $_GET['catcode']);
                $branchcode = $db_connections[user_company()]["branch_code"];
                $querystr = (isset($_POST['query']) ? $_POST['query'] : $_GET['query']);
                $search_ref = (isset($_POST['search_ref']) ? $_POST['search_ref'] : $_GET['search_ref']);
            
                $result = get_all_request_stock($start, $limit, $branchcode, $search_ref, false);
                $total_result = get_all_request_stock($start, $limit, $branchcode, $search_ref, true);
                $total = DB_num_rows($result);
    
                while ($myrow = db_fetch($result))
                {
                    $group_array[] = array('trans_id'=>$myrow["rsd_header_id"],
                        'reference' => $myrow["rsd_ref"],
                        'trans_date' => sql2date($myrow["rsd_date"]),
                        'loc_name' => get_db_location_name($myrow["rsd_to_loaction"]),
                        'category' => $myrow["categorys"],
                        'qty' => number_format($myrow["total_qty"],2),
                        'remarks' => $myrow["particulars"],
                        'category_id'=>$myrow["category"]
                    );               
                }
                
                $jsonresult = json_encode($group_array);
                echo '({"total":"'.DB_num_rows($total_result).'","result":'.$jsonresult.'})';
                exit;
                break;
        case 'RemoveItem';
            $line_item = $_REQUEST['line_item'];
            $serialise_id = $_REQUEST['serialise_id'];
            $AdjDate = $_POST['AdjDate'];
            $brcode = $db_connections[user_company()]["branch_code"];
            $_SESSION['transfer_items']->remove_from_cart_line($line_item);
            display_request_stock($_SESSION['transfer_items'],$brcode,$AdjDate);
            exit;
            break;
        case 'updateData';
            $arrayremove = array("[","]");
            $onlyconsonants = str_replace($arrayremove, "", html_entity_decode($_REQUEST['dataUpdate']));
            $filter = json_decode($onlyconsonants, true);
            $_SESSION['transfer_items']->update_cart_quantity($filter['id'], $filter['qty']);

            echo '({"success":true,"Update":"","id":"'.$filter['id'].'","qty":"'.$filter['qty'].'"})';
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
            $catcode = (integer) (isset($_POST['catcode']) ? $_POST['catcode'] : $_GET['catcode']);

            $sql = "SELECT ib.id as brand_id,ib.name as brand_name 
            FROM ".TB_PREF."stock_master sm 
            LEFT JOIN ".TB_PREF."item_brand ib ON sm.brand=ib.id 
            WHERE NOT sm.inactive AND sm.category_id=".db_escape($catcode)." 
            GROUP BY sm.brand ORDER BY ib.name";
            
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

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(800, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
/*----------End Here--------*/
page(_($help_context = "Request Stock Delivery"));

function handle_new_order($transtype=ST_REQUESTSTOCKDELIVER)
{
	if (isset($_SESSION['transfer_items']))
	{
		unset ($_SESSION['transfer_items']);
	}
    $_SESSION['transfer_items'] = new items_cart($transtype);
	$_SESSION['transfer_items']->clear_items();
	$_POST['AdjDate'] = new_doc_date();
	if (!is_date_in_fiscalyear($_POST['AdjDate']))
		$_POST['AdjDate'] = end_fiscalyear();
	$_SESSION['transfer_items']->tran_date = $_POST['AdjDate'];
}
//-----------------------------------------------------------------------------------------------
start_table(TABLESTYLE, "width='100%'");
   echo "<div id='requeststock-grid'></div>";
   echo "<style type='text/css' media='screen'>
            .x-form-text-default.x-form-textarea {
                line-height: 20px;
                min-height: 30px;
            }
        </style>";
end_table();

end_form();
end_page();