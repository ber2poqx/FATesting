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
function add_requisition($point_of_use, $narrative, $details)
{
	$sql = "INSERT INTO ".TB_PREF."requisitions (point_of_use, narrative, details) VALUES (".
		db_escape($point_of_use).",".db_escape($narrative).",".db_escape($details).")";

	db_query($sql, "could not add requisitions");
}

function update_requisition($selected_id, $point_of_use, $narrative, $details)
{
	$sql = "UPDATE ".TB_PREF."requisitions SET point_of_use=".db_escape($point_of_use)
			.", narrative=".db_escape($narrative)
			.", details=".db_escape($details)
			."WHERE requisition_id=".db_escape($selected_id);

	db_query($sql, "could not update Requisition");
}

function get_all_serialitems($all=false)
{
	//$sql = "SELECT * FROM ".TB_PREF."grn_batch  WHERE (completed = 0)";
	$sql = "SELECT *,grnheader.reference as reference FROM ".TB_PREF."grn_batch grnheader INNER JOIN ".TB_PREF."purch_orders poorder ON grnheader.purch_order_no=poorder.order_no INNER JOIN ".TB_PREF."suppliers suppl ON grnheader.supplier_id=suppl.supplier_id INNER JOIN ".TB_PREF."locations loc ON grnheader.loc_code=loc.loc_code";
	//if (!$all) $sql .= " AND !inactive";

	return db_query($sql, "could not get all GRN");
}

function get_one_requisition($selected_id)
{
	//$sql = "SELECT * FROM ".TB_PREF."requisitions WHERE requisition_id=".db_escape($selected_id);
	//$sql = "SELECT *,grnheader.reference as reference FROM ".TB_PREF."grn_batch grnheader LEFT JOIN ".TB_PREF."purch_orders poorder ON grnheader.purch_order_no=poorder.order_no  INNER JOIN ".TB_PREF."purch_order_details podetails ON poorder.order_no=podetails.order_no INNER JOIN ".TB_PREF."suppliers suppl ON grnheader.supplier_id=suppl.supplier_id INNER JOIN ".TB_PREF."locations loc ON grnheader.loc_code=loc.loc_code INNER JOIN grn_items grndetails ON grnheader.id=grndetails.grn_batch_id WHERE grnheader.id=".db_escape($selected_id);
	
	/*$sql = "SELECT poline.*, units
		FROM ".TB_PREF."purch_order_details poline
			LEFT JOIN ".TB_PREF."stock_master item	ON poline.item_code=item.stock_id
		WHERE  =".db_escape($selected_id);*/
	$sql = "SELECT *,grn_batch.id as grnbatchid, grn_items.id as grnitemsid FROM ".TB_PREF."grn_batch LEFT JOIN ".TB_PREF."grn_items ON grn_batch.id=grn_items.grn_batch_id INNER JOIN ".TB_PREF."purch_order_details podetails ON grn_items.po_detail_item=podetails.po_detail_item WHERE grn_batch.id=".db_escape($selected_id);	
	return db_query($sql, "could not get GRN");
}

function get_requisition($selected_id)
{
	$sql = "SELECT *, grnheader.id as id FROM ".TB_PREF."grn_batch grnheader INNER JOIN ".TB_PREF."grn_items items ON grnheader.id=items.grn_batch_id WHERE grnheader.id=".db_escape($selected_id);

	$result = db_query($sql, "could not get GRN No");

	return db_fetch($result);
}

function delete_requisition($selected_id)
{
	$sql="DELETE FROM ".TB_PREF."requisitions WHERE requisition_id=".db_escape($selected_id);

	db_query($sql, "could not delete Requisition");
}

function complete_requisition($selected_id)
{
	$sql = "UPDATE ".TB_PREF."requisitions SET completed =  1 WHERE requisition_id=".db_escape($selected_id);

	db_query($sql, "could not complete Requisition");
}

function generate_po()
{
	$sql = "SELECT ".TB_PREF."generate_po()";

	$result = db_query($sql, "could not process Requisition to Purchase Order");
	$row = db_fetch($result);
	return $row[0] == 'Done';
}

function requisitions_in_details($selected_id)
{
	$sql="SELECT COUNT(*) FROM ".TB_PREF."requisition_details WHERE requisition_id=".db_escape($selected_id);

	$result = db_query($sql, "could not query assets");
	$myrow = db_fetch_row($result);
	return ($myrow[0] > 0); 
}

