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
function add_item_apsupport_type($ap_support_type, $distribution)
{
	$sql = "INSERT INTO ".TB_PREF."item_apsupport_type (ap_support_type, distribution) 
					VALUES (".db_escape($ap_support_type).", ".db_escape($distribution).")";

	db_query($sql,"an item ap support type could not be added");
}

function update_item_apsupport_type($sys_id, $ap_support_type, $distribution, $inactive)
{
	$sql = "UPDATE ".TB_PREF."item_apsupport_type 
				SET ap_support_type=".db_escape($ap_support_type).", 
					distribution =".db_escape($distribution).",
					inactive =".db_escape($inactive)."
			WHERE id = ".db_escape($sys_id);

	db_query($sql,"an item support type could not be updated");
}

function delete_item_apsupport_type($sys_id)
{
	$sql="DELETE FROM ".TB_PREF."item_apsupport_type
			WHERE id = ".db_escape($sys_id);

	db_query($sql,"an item support type could not be deleted");
}

function get_itemapsupp_type($start, $limit, $search, $showall=false)
{
	if($search != ''){
		$sWhere .= " WHERE (A.ap_support_type LIKE '%".$search."%' OR A.distribution LIKE '%".$search."%') ";
	}
	$sql = "SELECT A.*
			FROM ".TB_PREF."item_apsupport_type A
			".$sWhere."
			ORDER BY A.ap_support_type ASC";
	
	$sqllimit = $sql ." limit $start, $limit";

	if($showall){
		$result = db_query($sql, "item AP support type could not be retreived");
	}else{
		$result = db_query($sqllimit, "item AP support type could not be retreived");
	}
	return $result;
}

//---------------------------------------------------------------------------------------------------

function add_item_apsupport_price($category_id, $stock_id, $apsupport_type_id, $price=0)
{
	$sql = "INSERT INTO ".TB_PREF."item_apsupport_price (category_id, supplier_id, apsupport_type_id, price) 
					VALUES (".db_escape($category_id).",".db_escape($stock_id).", ".db_escape($apsupport_type_id).", ".db_escape($price).")";

	db_query($sql,"an item ap support price could not be added");
}

function update_item_apsupport_price($sys_id, $stock_id, $apsupport_type_id, $price=0, $category_id)
{
	$sql = "UPDATE ".TB_PREF."item_apsupport_price 
				SET supplier_id=".db_escape($stock_id).", 
					apsupport_type_id =".db_escape($apsupport_type_id).",
					price =".db_escape($price).",
					category_id =".db_escape($category_id)."
			WHERE id = ".db_escape($sys_id);

	db_query($sql,"an item discount could not be updated");
}

function delete_item_apsupport_price($sys_id)
{
	$sql="DELETE FROM ".TB_PREF."item_apsupport_price
			WHERE id = ".db_escape($sys_id);

	db_query($sql,"an item support type could not be deleted");
}

function get_itemapsupp_price($category, $start, $limit, $search, $showall=false)
{
	/*if($search != ''){
		$sWhere .= " AND (B.ap_support_type LIKE '%".$search."%' OR C.description LIKE '%".$search."%' OR D.name LIKE '%".$search."%') ";
	}
	$sql = "SELECT A.id, A.stock_id, A.apsupport_type_id, A.price, B.ap_support_type, B.distribution, C.description, C.category_id, D.id brand_id, D.name brand_name
			FROM ".TB_PREF."item_apsupport_price A
				INNER JOIN ".TB_PREF."item_apsupport_type B ON B.id = A.apsupport_type_id
				INNER JOIN ".TB_PREF."stock_master C ON trim(C.stock_id) = trim(A.stock_id)
				INNER JOIN ".TB_PREF."item_brand D ON D.id = C.brand
			WHERE C.category_id = ".db_escape($category)."
			".$sWhere."
			ORDER BY A.stock_id ASC";*/

	if($search != ''){
		$sWhere .= " AND (B.ap_support_type LIKE '%".$search."%' OR D.supp_name LIKE '%".$search."%') ";
	}
	$sql = "SELECT A.id, A.price, B.id apsupport_type_id, B.ap_support_type, B.distribution, C.category_id, C.description, D.supplier_id, D.supp_name 
			FROM ".TB_PREF."item_apsupport_price A 
				INNER JOIN ".TB_PREF."item_apsupport_type B ON B.id = A.apsupport_type_id 
				INNER JOIN ".TB_PREF."stock_category C ON C.category_id = A.category_id 
				INNER JOIN ".TB_PREF."suppliers D ON D.supplier_id = A.supplier_id 
			WHERE A.category_id = ".db_escape($category)."
				".$sWhere."
			ORDER BY A.id ASC ";
	$sqllimit = $sql ." limit $start, $limit";

	if($showall){
		$result = db_query($sql, "item AP support price could not be retreived");
	}else{
		$result = db_query($sqllimit, "item AP support price could not be retreived");
	}
	return $result;
}

function get_itemcategory($id)
{
	if ($id != 00) {
		$sWhere = " AND A.category_id = ".$id. " ";
	}
	$sql = "SELECT A.category_id, A.description 
			FROM ".TB_PREF."stock_category A 
			WHERE !A.inactive AND A.dflt_mb_flag = 'B' ".$sWhere."
			ORDER BY A.category_id ASC ";
	return db_query($sql, "could not get stock category");	
}

function get_stockitem($category_id, $brand_id)
{
	$sql = "SELECT trim(A.stock_id) stock_id, CONCAT(trim(A.stock_id), ' - ', A.description) description
			FROM ".TB_PREF."stock_master A 
			WHERE A.category_id = ".db_escape($category_id)."
				AND A.brand = ".db_escape($brand_id)."
			ORDER BY A.stock_id ASC ";

	return db_query($sql, "could not get stock items");
}

function get_stockitem_discount($stock_id)
{
	$sql = "SELECT trim(A.stock_id) stock_id, CONCAT(trim(A.stock_id), ' - ', A.description) description
			FROM ".TB_PREF."stock_master A 
			WHERE trim(A.stock_id) = ".db_escape($stock_id)."
			ORDER BY A.stock_id ASC";

	return db_query($sql, "could not get stock items");
}

function check_apsupp_type($apsupp_type)
{
	$sql = "SELECT COUNT(*) 
			FROM ".TB_PREF."item_apsupport_type A
			WHERE A.ap_support_type = ".db_escape($apsupp_type);

	$result = db_query($sql, "check ap support type failed.");
	$count =  db_fetch($result);

	return $count[0];
}

function check_apsupp_price($apsupp_item, $apsupp_type, $category_id)
{
	$sql = "SELECT COUNT(*) 
			FROM ".TB_PREF."item_apsupport_price A
			WHERE A.supplier_id = ".db_escape($apsupp_item)."
				AND A.apsupport_type_id = ".db_escape($apsupp_type)."
				AND A.category_id = ".db_escape($category_id);

	$result = db_query($sql, "check ap support price failed.");
	$count =  db_fetch($result);

	return $count[0];
}

function get_item_brand($category_id){
	$sql = "SELECT DISTINCT A.id, A.name
			FROM ".TB_PREF."item_brand A 
				INNER JOIN ".TB_PREF."stock_master B ON B.brand = A.id
			WHERE B.category_id = ".db_escape($category_id)."
			ORDER BY A.name ASC ";

	return db_query($sql, "could not get stock items");
}

function get_supplierAP()
{
	$sql = "SELECT A.supplier_id, A.supp_name FROM ".TB_PREF."suppliers A ORDER BY A.supp_name ASC ";

	return db_query($sql, "could not get suppliers");
}