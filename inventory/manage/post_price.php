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

$js = "";
if ($SysPrefs->use_popup_windows)
    $js .= get_js_open_window(1000, 600);
if (user_use_date_picker())
    $js .= get_js_date_picker();

$page_title ="Price Approval";
page(_($help_context = $page_title), false, false, "", $js);

$price_id = $_GET['price_id'];
$price_code = $_GET['price_code'];
$row = get_price_history_data($price_id);

//-----------------------------------------------------------------------------
function get_price_history($price_id, $price_code, $stock_id){
	
	$sql = "SELECT 
				case when a.plcycashprice_id = b.id then b.scash_type
					when a.plcyprice_id = c.id then c.sales_type
					when a.plcycost_id = d.id then d.cost_type
					when a.plcysrp_id = e.id then e.srp_type
					else ''end as price_code,
				a.*

			FROM ".TB_PREF."price_cost_archive a
			Left JOIN ".TB_PREF."sales_cash_type b on a.plcycashprice_id = b.id
			Left JOIN ".TB_PREF."sales_types c on a.plcyprice_id = c.id
			Left JOIN ".TB_PREF."supp_cost_types d on a.plcycost_id = d.id
			Left JOIN ".TB_PREF."item_srp_area_types e on a.plcysrp_id = e.id
			where a.is_upload = 1 and a.stock_id =".db_escape($stock_id)." And a.id =".db_escape($price_id);

	$sql.= " AND (b.scash_type like ".db_escape($price_code)."
				OR c.sales_type like ".db_escape($price_code)."
				OR d.cost_type like ".db_escape($price_code)." 
				OR e.srp_type like ".db_escape($price_code).")";

	$sql.= " order by a.date_defined desc, a.id desc";

return db_query($sql,"The Price History could not be retreived");

}

function post_price_data($row, $price_code, $price_id)
{
	
	if (check_price_already_exist( $price_code, $row['stock_id'], normalize_chars($row['supp_name']))){	
		$type = 'Update';//updated
	}else{
		$type = 'Add';//Added
	}

	$cash_types = get_cash_price_types_id($price_code);
	$lcp_types = get_lcp_price_types_id($price_code);
	$cost_types = get_system_cost_types_id($price_code);
	$srp_types = get_srp_types_id($price_code);
	$incentives_types = get_incentive_types_id($price_code);

	if($type == 'Update'){

		if(get_cash_types($price_code) == $price_code){
			
			update_item_scashprice(
				$row['prcecost_id'],
				$cash_types, 
				'PHP', 
				$row['amount'],
				$row['date_epic']);
			update_pricehistory(
				$row['stock_id'], 
				0, 
				$cash_types, 
				0, 
				0, 
				0, 
				0, 
				'CSHPRCPLCY');  //update inactive price
			update_active_pricehistory($price_id, $row['prcecost_id']); 						//update active price

		}
		else if(get_lcp_price_types($price_code) == $price_code){

			update_item_price(
				$row['prcecost_id'], 
				$lcp_types, 
				'PHP',
				$row['amount'], 
				$row['date_epic']);

			update_pricehistory(
				$row['stock_id'], 
				0, 
				0, 
				$lcp_types, 
				0, 
				0, 
				0, 
				'PRCPLCY');  //update inactive price
			update_active_pricehistory($price_id, $row['prcecost_id']); 						//update active price

		}
		else if(get_system_cost_types($price_code) == $price_code && $row['supplier_id'] <> null){
				
			update_item_supplrcost(
				$row['prcecost_id'],
				$row['stock_id'], 
				$row['amount'],
				'', 
				1,
				normalize_chars($row['supp_name']),
				$cost_types,
				$row['date_epic']);
			update_pricehistory($row['stock_id'], $row['supplier_id'], 0, 0, $cost_types, 0, 0, 'CSTPLCY');  //update inactive price
			update_active_pricehistory($price_id, $row['prcecost_id']); 						//update active price
		}
		else if( get_srp_types($price_code)== $price_code && $row['supplier_id'] <> null)
		{	
			update_item_stdcost(
				$row['prcecost_id'], 
				$srp_types, 
				'PHP', 
				$row['amount'], 
				$row['supplier_id'], 
				$row['date_epic']);
			update_pricehistory($row['stock_id'], $row['supplier_id'], 0, 0, 0, $srp_types, 0, 'SRPPLCY');
			update_active_pricehistory($price_id, $row['prcecost_id']); 
		}


	}else{
		if($type == 'Add'){
			$Selected_id = get_price_id($price_code) + 1;
			if( get_cash_types($price_code)==$price_code){
			
				add_item_scashprice(
					$row['stock_id'], 
					$cash_types, 
					'PHP', 
					$row['amount'], 
					$row['date_epic']);

				$item_price_id = get_stock_scashprice_type_currency($row['stock_id'], $cash_types, 'PHP');
				display_error( get_stock_scashprice_type_currency($row['stock_id'], $cash_types, 'PHP'));
				// update_pricehistory($row['stock_id'], 0, $cash_types['id'], 0, 0, 0, 0, 'CSHPRCPLCY');  //update inactive price
				update_active_pricehistory($price_id, $item_price_id['id']); 						//update active price

			}
			else if(get_lcp_price_types($price_code) == $price_code)
			{

				add_item_price(
					$row['stock_id'],  
					$lcp_types, 
					'PHP', 
					$row['amount'], 
					$row['date_epic']);
				
				$item_price_id = get_stock_price_type_currency($row['stock_id'], $lcp_types, 'PHP');
				// update_pricehistory($row['stock_id'], 0, 0, $lcp_types,  0, 0, 0, 'PRCPLCY');  //update inactive price
				update_active_pricehistory($price_id, $item_price_id['id']); 						//update active price

			}
			else if(get_system_cost_types($price_code) == $price_code && $row['supplier_id'] <> null)
			{

				add_item_supplrcost(
					$row['supplier_id'], 
					$row['stock_id'], 
					$row['amount'], 
					'', 
					1, 
					$row['suppname'], 
					$cost_types,  
					$row['date_epic']);
				
				$item_price_id = get_item_supplrcost($row['supplier_id'], $row['stock_id'], $cost_types);
				
				// update_pricehistory($row['stock_id'], $row['supplier_id'], 0, 0, 0, $cost_types, 0, 0, 'CSTPLCY');  //update inactive price
				update_active_pricehistory(
					$price_id, 
					$item_price_id['id']); 						//update active price

			}else if( get_srp_types($price_code)==$price_code && $row['supplier_id'] <> null){
				
				add_item_stdcost(
					$row['stock_id'], 
					$srp_types, 'PHP', 
					$row['amount'], 
					$row['supplier_id'], 
					$row['date_epic']);

				$item_price_id = get_stock_stdcost_type_currency($row['stock_id'], $srp_types, 'PHP', $row['supplier_id']);
				// update_pricehistory($row['stock_id'], $row['supplier_id'], 0, 0, 0, $srp_types, 0, 'SRPPLCY');
				update_active_pricehistory(
					$price_id, 
					$item_price_id['id']); 
			}else{

			}
		}
	}
}


//-----------------------------------------------------------------------------

if (isset($_POST['PostPrice']) ) { 
	$status = 3; // close
	post_price_data($row, $price_code, $price_id);

	price_status_update($status, $price_id);
	meta_forward($path_to_root . "/inventory/manage/price_history_list.php?");	
    
}

//-----------------------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

$loc_details = get_price_history($price_id, $price_code ,$row['stock_id']);

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