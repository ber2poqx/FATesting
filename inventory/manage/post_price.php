<?php
/**
 * Added by: Albert
 * Date Added: 15 Sep 2022
*/
$page_security = 'SA_POSTPRICE';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/modules/Price_import/price_import.inc");

include_once($path_to_root . "/inventory/includes/inventory_db.inc");
include_once($path_to_root . "/includes/db/manufacturing_db.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_incentive_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
    $js .= get_js_open_window(1000, 600);
if (user_use_date_picker())
    $js .= get_js_date_picker();

$page_title ="Price Approval";
page(_($help_context = $page_title), false, false, "", $js);

if (isset($_GET['AddedID'])) {

    $reference = $_GET['AddedID'];
    // $price_code = $_GET['PriceCode'];
	$status = $_GET['Status'];
    if ($status == 'Add')
        display_notification_centered(_("Price is successfuly added"). " #$reference");
    else
        display_notification_centered(_("Price is successfuly updated") . " #$reference");
	
	hyperlink_params("$path_to_root/inventory/manage/price_history_list.php", _("Back to List of Uploaded Price"), "", true);
    display_footer_exit();
}

$reference = $_GET['reference'];


//-----------------------------------------------------------------------------
function post_price_data($id, $price_code, $type, $stock_id,
$amount, $supplier_id, $date_epic, $supp_name, $prcecost_id)
{

	$cash_types = get_cash_price_types_id($price_code);
	$lcp_types = get_lcp_price_types_id($price_code);
	$cost_types = get_system_cost_types_id($price_code);
	$srp_types = get_srp_types_id($price_code);
	$incentives_types = get_incentive_types_id($price_code);

	if($type == 'Update'){
		

		if(get_cash_types($price_code) == $price_code){
			
			update_item_scashprice(
				$prcecost_id,
				$cash_types, 
				'PHP', 
				$amount,
				$date_epic);
			update_pricehistory(
				$stock_id, 
				0, 
				$cash_types, 
				0, 
				0, 
				0, 
				0, 
				'CSHPRCPLCY');  //update inactive price	
			add_pricehistory($stock_id, $amount, $prcecost_id , 0, $cash_types, 0, 0, 0, 0, 'CSHPRCPLCY', date("Y-m-d H:i:s"),$date_epic);
		}
		else if(get_lcp_price_types($price_code) == $price_code){

			update_item_price(
				$prcecost_id, 
				$lcp_types, 
				'PHP',
				$amount, 
				$date_epic);

			update_pricehistory(
				$stock_id, 
				0, 
				0, 
				$lcp_types, 
				0, 
				0, 
				0, 
				'PRCPLCY');  //update inactive price
			add_pricehistory($stock_id, $amount, $prcecost_id , 0, 0, $lcp_types, 0, 0, 0, 'PRCPLCY', date("Y-m-d H:i:s"),$date_epic);


		}
		else if(get_system_cost_types($price_code) == $price_code && $supplier_id <> null){
				
			update_item_supplrcost(
				$prcecost_id,
				$stock_id, 
				$amount,
				'', 
				1,
				normalize_chars($supp_name),
				$cost_types,
				$date_epic);
			update_pricehistory($stock_id, $supplier_id, 0, 0, $cost_types, 0, 0, 'CSTPLCY');  //update inactive price
			add_pricehistory($stock_id, $amount, $prcecost_id , $supplier_id, 0, 0, $cost_types, 0, 0, 'CSTPLCY', date("Y-m-d H:i:s"),$date_epic);
		}
		else if( get_srp_types($price_code)== $price_code && $supplier_id <> null)
		{	
			update_item_stdcost(
				$prcecost_id, 
				$srp_types, 
				'PHP', 
				$amount, 
				$supplier_id, 
				$date_epic);
			update_pricehistory($stock_id, $supplier_id, 0, 0, 0, $srp_types, 0, 'SRPPLCY');
			add_pricehistory($stock_id, $amount, $prcecost_id , $supplier_id, 0, 0, 0, $srp_types, 0, 'SRPPLCY', date("Y-m-d H:i:s"),$date_epic);
		
		}else{
			if(get_incentive_types($price_code) == $price_code){

				update_item_incentiveprice(
					$prcecost_id, 
					$incentives_types, 
					'PHP',
					$amount);

				update_pricehistory($stock_id, $supplier_id, 0, 0, 0, $incentives_types, 0, 'SMIPLCY');
				add_pricehistory($stock_id, $amount, $prcecost_id , 0, 0, 0, 0, 0, $incentives_types, 'SMIPLCY', date("Y-m-d H:i:s"),$date_epic);

			}


		}


	}else{
		if($type == 'Add'){
			$Selected_id = get_price_id($price_code) + 1;
			if( get_cash_types($price_code)==$price_code){
			
				add_item_scashprice(
					$stock_id, 
					$cash_types, 
					'PHP', 
					$amount, 
					$date_epic);
			add_pricehistory($stock_id, $amount, $Selected_id , 0, $cash_types, 0, 0, 0, 0, 'CSHPRCPLCY', date("Y-m-d H:i:s"),$date_epic);
			}
			else if(get_lcp_price_types($price_code) == $price_code)
			{

				add_item_price(
					$stock_id,  
					$lcp_types, 
					'PHP', 
					$amount, 
					$date_epic);
				
				add_pricehistory($stock_id, $amount, $Selected_id , 0, 0, $lcp_types, 0, 0, 0, 'PRCPLCY', date("Y-m-d H:i:s"),$date_epic);

			}
			else if(get_system_cost_types($price_code) == $price_code && $supplier_id <> null)
			{

				add_item_supplrcost(
					$supplier_id, 
					$stock_id, 
					$amount, 
					'', 
					1, 
					$supp_name, 
					$cost_types,  
					$date_epic);

				add_pricehistory($stock_id, $amount, $Selected_id , $supplier_id, 0, 0, $cost_types, 0, 0, 'CSTPLCY', date("Y-m-d H:i:s"),$date_epic);

			}
			else if( get_srp_types($price_code)==$price_code && $supplier_id <> null){
				
				add_item_stdcost(
					$stock_id, 
					$srp_types, 
					'PHP', 
					$amount, 
					$supplier_id, 
					$date_epic);

				add_pricehistory($stock_id, $amount, $Selected_id , $supplier_id, 0, 0, 0, $srp_types, 0, 'SRPPLCY', date("Y-m-d H:i:s"),$date_epic);
			}
			else{
				if(get_incentive_types($price_code) == $price_code){
	
					add_item_incentiveprice(
						$stock_id, 
						$incentives_types, 
						'PHP', 
						$amount);

						add_pricehistory($stock_id, $amount, $Selected_id , 0, 0, 0, 0, 0, $incentives_types, 'SMIPLCY', date("Y-m-d H:i:s"),$date_epic);	
				}
	
	
			}
		}
	}
}


//-----------------------------------------------------------------------------

if (isset($_POST['PostPrice']) ) { 
	$status = 3; // close

	$price_data = get_list_price_upload($reference);
    while ($data = db_fetch($price_data))
    {

		if (check_price_already_exist( $data['price_code'], $data['stock_id'], normalize_chars($data['supp_name']))){	
			$type = 'Update';//updated
		}else{
			$type = 'Add';//Added
		}

				post_price_data($data['id'], $data['price_code'], $type, $data['stock_id'],
				$data['amount'], $data['supplier_id'], $data['date_epic'], $data['supp_name'],
				$data['prcecost_id']);

		
    }

	price_status_update($status, $reference);
	meta_forward($_SERVER['PHP_SELF'],"AddedID=$reference&Status=$type");	
    
}

//-----------------------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

$loc_details = get_list_price_upload($reference);

start_table(TABLESTYLE);

$th = array(_("Item_code"), _("Price code"), _("Amount"), _("Created Date"), _("Effectivity date"));

table_header($th);
$j = 1;
$k = 0; //row colour counter

while ($myrow = db_fetch($loc_details))
{

	alt_table_row_color($k);

		label_cell($myrow["stock_id"]);
		label_cell($myrow["price_code"]);
		label_cell($myrow["amount"]);
		label_cell($myrow["date_defined"]);
		label_cell($myrow["date_epic"]);

		
        end_row();

	$j++;

}
// 

start_table(TABLESTYLE2);
echo "<br> <br>";

// textarea_row(_("Remarks:"), 'Comments', null, 70, 4);

end_table(1);

    submit_center_first('PostPrice', _("Post"), '', 'default');

br(2);

end_form();
end_page();