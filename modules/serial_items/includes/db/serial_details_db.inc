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
function add_serialitems_detail($grnid, $grnlineid, $lot_no='', $order_quantity, $chasis_no='', $manufacture_date='0000-00-00', $expire_date='0000-00-00')
{
	$manufacture_date = date2sql($manufacture_date);
	$expire_date = date2sql($expire_date);
	$sql = "INSERT INTO ".TB_PREF."item_serialise (serialise_grn_id, serialise_grn_items_id, serialise_lot_no, serialise_qty, serialise_manufacture_date, serialise_expire_date, serialise_item_code,serialise_chassis_no) VALUES (".
	db_escape($grnid).",".db_escape($grnlineid).",".db_escape($lot_no).",".db_escape($order_quantity).",".db_escape($manufacture_date).",".db_escape($expire_date).",'',".db_escape($chasis_no).")";

	db_query($sql, "could not add serial items details");
}

function update_serialitems_detail($selected_id, $item_code, $purpose, $order_quantity, $estimate_price)
{
	$sql = "UPDATE ".TB_PREF."item_serialise SET item_code=".db_escape($item_code)
			.", purpose=".db_escape($purpose)
			.", order_quantity=".db_escape($order_quantity)
			.", estimate_price=".db_escape($estimate_price)
			.", quantity=".db_escape($order_quantity)
			.", price=".db_escape($estimate_price)
			."WHERE requisition_detail_id=".db_escape($selected_id);

	db_query($sql, "could not update requisition detail");
}

function update_requisition_lpo($selected_id, $supplier_id, $quantity, $price)
{
	$sql = "UPDATE ".TB_PREF."requisition_details SET supplier_id=".db_escape($supplier_id)
			.", quantity=".db_escape($quantity)
			.", price=".db_escape($price)
			."WHERE requisition_detail_id=".db_escape($selected_id);

	db_query($sql, "could not update requisition detail");
}

function get_all_serialitems_details($grnid,$grnlineid)
{
	$sql = "SELECT * FROM ".TB_PREF."item_serialise WHERE (serialise_grn_id = ".db_escape($grnid)." AND serialise_grn_items_id=".db_escape($grnlineid).")";
	//echo $sql;
	//die();
	return db_query($sql, "could not get all Serial Items");
}

function get_open_requisition_details()
{
	$sql = "SELECT * FROM ".TB_PREF."item_codes ic INNER JOIN ".TB_PREF."requisition_details rd ON ic.item_code = rd.item_code
	INNER JOIN ".TB_PREF."requisitions r ON rd.requisition_id = r.requisition_id
	LEFT JOIN ".TB_PREF."suppliers s ON rd.supplier_id = s.supplier_id
	WHERE (r.completed = 1) AND (rd.lpo_id = 0)
	ORDER BY r.requisition_id";

	return db_query($sql, "could not get all requisition_details");
}

function get_serialitems_detail($selected_id)
{
	$sql = "SELECT * FROM ".TB_PREF."item_serialise WHERE serialise_id=".db_escape($selected_id);

	$result = db_query($sql, "could not get serial Items detail");

	return db_fetch($result);
}

function delete_requisition_detail($selected_id)
{
	$sql="DELETE FROM ".TB_PREF."requisition_details WHERE requisition_detail_id=".db_escape($selected_id);

	db_query($sql, "could not delete requisition detail");
}


