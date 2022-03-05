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
function add_item_discount($stock_id, $dp_discount1=0, $dp_discount2=0, $sales_discount1=0, $sales_discount2=0)
{
	$sql = "INSERT INTO ".TB_PREF."item_discount (stock_id, dp_discount1, dp_discount2, sales_discount1, sales_discount2) 
					VALUES (".db_escape($stock_id).", ".db_escape($dp_discount1).", ".db_escape($dp_discount2).",
							 ".db_escape($sales_discount1).", ".db_escape($sales_discount2).")";

	db_query($sql,"an item discount could not be added");
}

function update_item_discount($sys_id, $stock_id, $dp_discount1=0, $dp_discount2=0, $sales_discount1=0, $sales_discount2=0)
{
	$sql = "UPDATE ".TB_PREF."item_discount 
				SET stock_id=".db_escape($stock_id).", 
					dp_discount1 =".db_escape($dp_discount1).",
					dp_discount2 =".db_escape($dp_discount2).",
					sales_discount1 =".db_escape($sales_discount1).",
					sales_discount2 =".db_escape($sales_discount2)."
			WHERE id = ".db_escape($sys_id);

	db_query($sql,"an item discount could not be updated");
}

function delete_item_discount($sys_id)
{
	$sql="DELETE FROM ".TB_PREF."item_discount
			WHERE id = ".db_escape($sys_id);

	db_query($sql,"an item discount could not be deleted");
}

function get_item_discount($category_id, $start, $limit, $search, $showall=false)
{
	if($search != ''){
		$sWhere .= " AND (A.stock_id LIKE '%".$search."%' OR B.description LIKE '%".$search."%') ";
	}
	$sql = "SELECT A.*, B.description, B.category_id, B.brand
			FROM ".TB_PREF."item_discount A
				INNER JOIN ".TB_PREF."stock_master B ON trim(B.stock_id) = trim(A.stock_id)
			WHERE B.category_id = ".db_escape($category_id)."
			".$sWhere."
			ORDER BY A.stock_id ASC";
	
	$sqllimit = $sql ." limit $start, $limit";

	if($showall){
		$result = db_query($sql, "item discount could not be retreived");
	}else{
		$result = db_query($sqllimit, "item discount could not be retreived");
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

function check_stock_id($stock_id)
{
	$sql = "SELECT COUNT(*) 
			FROM ".TB_PREF."item_discount A
			WHERE trim(A.stock_id) = ".db_escape($stock_id);

	$result = db_query($sql, "check stock id failed.");
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