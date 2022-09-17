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
function add_pricehistory($stock_id, $amount, $prcecost_id, $supplier_id, $plcycashprc_id, $plcyprice_id, $plcycost_id, $plcysrp_id, $incentive_id, $module, $dateadded,$date_epic = '0000-00-00', $status=0, $price_upload=0)  //date epic Added by Albert
{
	// for price import Add by Albert 09/16/2022
	if($price_upload ==1){
		$inactive = 1;
	}else{
		$inactive = 0;
	}
	//
	$sql = "INSERT INTO ".TB_PREF."price_cost_archive (
					stock_id 
					,amount
					,prcecost_id
					,supplier_id
					,plcycashprice_id
					,plcyprice_id
					,plcycost_id
					,plcysrp_id
					,incentive_id
					,module_type
					,date_defined
					,inactive
					,date_epic
					,status
					,price_upload
			) VALUES (
			        ".db_escape($stock_id)."
					,".db_escape($amount)."
				   	,".db_escape($prcecost_id)."
				   	,".db_escape($supplier_id)."
					,".db_escape($plcycashprc_id)." 
				   	,".db_escape($plcyprice_id)."
				   	,".db_escape($plcycost_id)."
				   	,".db_escape($plcysrp_id)."
					,".db_escape($incentive_id)."
				   	,".db_escape($module)."
					,".db_escape($dateadded)."
					,".db_escape($inactive)."
					,".db_escape($date_epic)."
					,".db_escape($status)."
					,".db_escape($price_upload)."
			)";
	
	$ErrMsg = _('Could not insert the new price/cost history because ');
	db_query($sql, $ErrMsg);		
}

function update_pricehistory($stock_id, $supplier_id, $plcycashprc_id, $plcyprice_id, $plcycost_id, $plcysrp_id, $incentive_id, $module)
{
	$sql = "UPDATE ".TB_PREF."price_cost_archive SET inactive = 1
		WHERE stock_id = ".db_escape($stock_id). " 
			AND supplier_id = ".db_escape($supplier_id)."
			AND plcycashprice_id = ".db_escape($plcycashprc_id)."
			AND plcyprice_id = ".db_escape($plcyprice_id)."
			AND plcycost_id = ".db_escape($plcycost_id)."
			AND plcysrp_id = ".db_escape($plcysrp_id)."
			AND incentive_id = ".db_escape($incentive_id)."
			AND module_type = ".db_escape($module);
			
	db_query($sql,"The price/cost history could not be updated");
}

function remove_id_pricehistory($selected_id, $module)
{
	$sql = "UPDATE ".TB_PREF."price_cost_archive SET inactive = 1 
			WHERE prcecost_id = ".db_escape($selected_id)."
				AND module_type = ".db_escape($module);
	
	db_query($sql,"The price/cost history could not be updated");
}

/*****************************************************************************************/

function Get_Policy_Cost($branchCode, $categoryID, $stockID, $supplierID)
{
    global $def_coy;
    set_global_connection($def_coy);
	
	$sql = "SELECT A.price 
			FROM ".TB_PREF."purch_data A 
				INNER JOIN ".TB_PREF."branch_policy B ON B.plcysplrcost_id = A.cost_type_id
    			INNER JOIN ".TB_PREF."stock_master C ON C.stock_id = A.stock_id AND C.category_id = B.category_id
			WHERE B.module_type = 'CSTPLCY'
				AND B.branch_code = ".db_escape($branchCode)."
				AND C.category_id = ".db_escape($categoryID)."
    			AND A.stock_id = ".db_escape($stockID)."
				AND A.supplier_id = ".db_escape($supplierID);

	$result = db_query($sql, "The supplier cost for the selected branch and item could not be retrieved");
	$row = db_fetch_row($result);
	$price = $row[0];

	set_global_connection();
	return $price;
}

function Get_Policy_SRP($branchCode, $categoryID, $stockID, $supplierID)
{
    global $def_coy;
    set_global_connection($def_coy);

	$sql = "SELECT A.standard_cost 
			FROM ".TB_PREF."item_srp A 
				INNER JOIN ".TB_PREF."branch_policy B ON B.plcysrp_id = A.srptype_id
    			INNER JOIN ".TB_PREF."stock_master C ON C.stock_id = A.stock_id AND C.category_id = B.category_id
			WHERE B.module_type = 'SRPPLCY'
				AND B.branch_code = ".db_escape($branchCode)."
				AND C.category_id = ".db_escape($categoryID)."
    			AND A.stock_id = ".db_escape($stockID)."
				AND A.supplier_id = ".db_escape($supplierID);

	$result = db_query($sql, "The standard cost for the selected branch and item could not be retrieved");
	$row = db_fetch_row($result);
	$price = $row[0];

	set_global_connection();
	return $price;
}

function Get_Policy_CashPrice($branchCode, $categoryID, $stockID)
{
    global $def_coy;
    set_global_connection($def_coy);
	
	$sql = "SELECT A.price 
			FROM ".TB_PREF."cash_prices A 
				INNER JOIN ".TB_PREF."branch_policy B ON B.plcycashprice_id = A.scash_type_id
    			INNER JOIN ".TB_PREF."stock_master C ON C.stock_id = A.stock_id AND C.category_id = B.category_id
			WHERE B.module_type = 'CSHPRCPLCY'
				AND B.branch_code = ".db_escape($branchCode)."
				AND C.category_id = ".db_escape($categoryID)."
    			AND A.stock_id = ".db_escape($stockID);

	$result = db_query($sql, "The cash price for the selected branch and item could not be retrieved");
	$row = db_fetch_row($result);
	$price = $row[0];

	set_global_connection();
	return $price;
}

function Get_Policy_Installment_Price($branchCode, $categoryID, $stockID)
{
    global $def_coy;
    set_global_connection($def_coy);
	
	$sql = "SELECT A.price 
			FROM ".TB_PREF."prices A 
				INNER JOIN ".TB_PREF."branch_policy B ON B.plcyprice_id = A.sales_type_id
    			INNER JOIN ".TB_PREF."stock_master C ON C.stock_id = A.stock_id AND C.category_id = B.category_id
			WHERE B.module_type = 'PRCPLCY'
				AND B.branch_code = ".db_escape($branchCode)."
				AND C.category_id = ".db_escape($categoryID)."
    			AND A.stock_id = ".db_escape($stockID);

	$result = db_query($sql, "The LCP for the selected branch and item could not be retrieved");
	$row = db_fetch_row($result);
	$price = $row[0];

	set_global_connection();
	return $price;
}

function Get_Item_Incentive_Price($categoryID, $stockID, $moduleType)
{
    global $def_coy;
    set_global_connection($def_coy);
	
	/** for reference, just set $moduleType to 
			"BII" - for brand new items
			"BII" - for repo items
			"SMI" - for monthly incentive
	**/

	$sql = "SELECT A.price 
			FROM ".TB_PREF."incentive_prices A 
    			INNER JOIN ".TB_PREF."stock_master B ON B.stock_id = A.stock_id 
                INNER JOIN ".TB_PREF."sales_incentive_type C ON C.id = A.incentive_type_id
			WHERE C.module_type = ".db_escape($moduleType)."
				AND B.category_id = ".db_escape($categoryID)."
    			AND A.stock_id = ".db_escape($stockID);

	$result = db_query($sql, "The incentive price for selected item could not be retrieved");
	$row = db_fetch_row($result);
	$price = $row[0];

	set_global_connection();
	return $price ? $price : 0;
}

function Get_System_Cost($stockID,$type_out=0,$transno_out=0){
    set_global_connection();
    $sql = "SELECT A.standard_cost 
			FROM ".TB_PREF."stock_moves A WHERE A.type_out = ".db_escape($type_out)."
    			AND A.stock_id = ".db_escape($stockID)."
				AND A.transno_out = ".db_escape($transno_out);
    
    $result = db_query($sql, "The standard cost for the selected item could not be retrieved");
    $row = db_fetch_row($result);
    $cost = $row[0];
    
    set_global_connection();
    return $cost;
}

function Get_Sales_Discount1($stockID){
    /* Modified by Ronelle */
	global $def_coy;
    set_global_connection($def_coy);
	/* */
    $sql = "SELECT A.sales_discount1 
			FROM ".TB_PREF."item_discount A 
			WHERE A.stock_id = ".db_escape($stockID);
    
    $result = db_query($sql, "The sales discount1 for the selected item could not be retrieved");
    $row = db_fetch_row($result);
    $discount = $row[0];
    
    set_global_connection();
    return $discount;
}

function Get_Sales_Discount2($stockID){
    /* Modified by Ronelle */
	global $def_coy;
    set_global_connection($def_coy);
	/* */
    $sql = "SELECT A.sales_discount2 
			FROM ".TB_PREF."item_discount A 
			WHERE A.stock_id = ".db_escape($stockID);
    
    $result = db_query($sql, "The sales discount2 for the selected item could not be retrieved");
    $row = db_fetch_row($result);
    $discount = $row[0];
    
    set_global_connection();
    return $discount;
}

function Get_DP_Discount1($stockID){
    /* Modified by Ronelle */
	global $def_coy;
    set_global_connection($def_coy);
	/* */
    $sql = "SELECT A.dp_discount1 
			FROM ".TB_PREF."item_discount A 
			WHERE A.stock_id = ".db_escape($stockID);
    
    $result = db_query($sql, "The dp discount1 for the selected item could not be retrieved");
    $row = db_fetch_row($result);
    $discount = $row[0];
    
    set_global_connection();
    return $discount;
}

function Get_DP_Discount2($stockID){
    /* Modified by Ronelle */
	global $def_coy;
    set_global_connection($def_coy);
	/* */
    $sql = "SELECT A.dp_discount2 
			FROM ".TB_PREF."item_discount A 
			WHERE A.stock_id = ".db_escape($stockID);
    
    $result = db_query($sql, "The dp discount2 for the selected item could not be retrieved");
    $row = db_fetch_row($result);
    $discount = $row[0];
    
    set_global_connection();
    return $discount;
}

function Get_AP_Support_Price($stockID, $apsupptype_id){
    set_global_connection();
    $sql = "SELECT A.price 
			FROM ".TB_PREF."item_apsupport_price A 
			WHERE A.stock_id = ".db_escape($stockID)."
				AND A.apsupport_type_id = ".db_escape($apsupptype_id);
    
    $result = db_query($sql, "The ap support price for the selected item could not be retrieved");
    $row = db_fetch_row($result);
    $ap_price = $row[0];
    
    set_global_connection();
    return $ap_price;
}
/*Added by Albert 04-23-2022*/
function Get_Previous_Policy_Installment_Price($branchCode, $categoryID, $stockID, $last_date_updated)
{
    global $def_coy;
    set_global_connection($def_coy);
	
	$sql = "SELECT D.Amount
			FROM ".TB_PREF."prices A 
				INNER JOIN ".TB_PREF."branch_policy B ON B.plcyprice_id = A.sales_type_id
    			INNER JOIN ".TB_PREF."stock_master C ON C.stock_id = A.stock_id AND C.category_id = B.category_id
				INNER JOIN ".TB_PREF."price_cost_archive D ON D.plcyprice_id = A.sales_type_id and D.stock_id = A.stock_id
			WHERE B.module_type = 'PRCPLCY'
				AND D.inactive = 1
				AND B.branch_code = ".db_escape($branchCode)."
				AND C.category_id = ".db_escape($categoryID)."
    			AND A.stock_id = ".db_escape($stockID)."
				AND D.date_defined = ".db_escape($last_date_updated);
	$sql.= "order by D.id desc";
	$result = db_query($sql, "The LCP for the selected branch and item could not be retrieved");
	$row = db_fetch_row($result);
	$price = $row[0];

	set_global_connection();
	return $price;
}
function Get_Previous_Policy_CashPrice($branchCode, $categoryID, $stockID, $last_date_updated)
{
    global $def_coy;
    set_global_connection($def_coy);

	
	$sql = "SELECT D.amount
			FROM ".TB_PREF."cash_prices A 
				INNER JOIN ".TB_PREF."branch_policy B ON B.plcycashprice_id = A.scash_type_id
    			INNER JOIN ".TB_PREF."stock_master C ON C.stock_id = A.stock_id AND C.category_id = B.category_id
				INNER JOIN ".TB_PREF."price_cost_archive D ON D.plcycashprice_id = A.scash_type_id and D.stock_id = A.stock_id
			WHERE B.module_type = 'CSHPRCPLCY'
				AND D.inactive = 1
				AND B.branch_code = ".db_escape($branchCode)."
				AND C.category_id = ".db_escape($categoryID)."
    			AND A.stock_id = ".db_escape($stockID)."
				AND D.date_defined = ".db_escape($last_date_updated);
	$sql.= "order by D.id desc";
	$result = db_query($sql, "The cash price for the selected branch and item could not be retrieved");

	$row = db_fetch_row($result);
	$price = $row[0];

	set_global_connection();
	return $price;;
}

function Get_Previous_Policy_SRP($branchCode, $categoryID, $stockID, $supplierID, $last_date_updated)
{
    global $def_coy;
    set_global_connection($def_coy);

	$sql = "SELECT D.amount
			FROM ".TB_PREF."item_srp A 
				INNER JOIN ".TB_PREF."branch_policy B ON B.plcysrp_id = A.srptype_id
    			INNER JOIN ".TB_PREF."stock_master C ON C.stock_id = A.stock_id AND C.category_id = B.category_id
				INNER JOIN ".TB_PREF."price_cost_archive D ON D.plcysrp_id =  A.srptype_id and D.stock_id = A.stock_id
			WHERE B.module_type = 'SRPPLCY'
				AND D.inactive = 1
				AND B.branch_code = ".db_escape($branchCode)."
				AND C.category_id = ".db_escape($categoryID)."
    			AND A.stock_id = ".db_escape($stockID)."
				AND A.supplier_id = ".db_escape($supplierID)."
				AND D.date_defined = ".db_escape($last_date_updated);
	$sql.= "order by D.id desc";
	$result = db_query($sql, "The standard cost for the selected branch and item could not be retrieved");
	$row = db_fetch_row($result);
	$price = $row[0];

	set_global_connection();
	return $price;
}
function Get_Previous_Policy_Cost($branchCode, $categoryID, $stockID, $supplierID, $date_defined)
{
    global $def_coy;
    set_global_connection($def_coy);
	
	$sql = "SELECT D.amount
			FROM ".TB_PREF."purch_data A 
				INNER JOIN ".TB_PREF."branch_policy B ON B.plcysplrcost_id = A.cost_type_id
    			INNER JOIN ".TB_PREF."stock_master C ON C.stock_id = A.stock_id AND C.category_id = B.category_id
				INNER JOIN ".TB_PREF."price_cost_archive D ON D.plcycost_id = A.cost_type_id and D.stock_id = A.stock_id
			WHERE B.module_type = 'CSTPLCY'
				AND D.inactive = 1
				AND B.branch_code = ".db_escape($branchCode)."
				AND C.category_id = ".db_escape($categoryID)."
    			AND A.stock_id = ".db_escape($stockID)."
				AND A.supplier_id = ".db_escape($supplierID)."
				And D.date_defined = ".db_escape($date_defined);
	$sql.= "order by D.id desc";
	
	$result = db_query($sql, "The supplier cost for the selected branch and item could not be retrieved");
	$row = db_fetch_row($result);
	$price = $row[0];

	set_global_connection();
	return $price;
}
/*----Added by Albert 09/16/2022 */
function price_status_update($status,$price_id)
{
	$sql = "UPDATE ".TB_PREF."price_cost_archive SET status = ".db_escape($status)."
			WHERE id=".db_escape($price_id);

	$result = db_query($sql,"an item cash price status could not be updated");
	
	return $result;
}

/*END by Albert*/
