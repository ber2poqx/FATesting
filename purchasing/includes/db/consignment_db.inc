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
// ------------------------------------------------------------------------------
function add_consignment(&$consignment_obj)
{
	global $Refs;

	begin_transaction();
	hook_db_prewrite($consignment_obj, ST_RECEIVECONSIGN);

	/*Insert to receive consignment header record */
	$sql = "INSERT INTO " . TB_PREF . "consignment (supplier_id, reference ,comments, consign_date , category_id, suppl_ref_no) VALUES(";
	$sql .= db_escape($consignment_obj->supplier_id) . "," .
		db_escape($consignment_obj->reference) . ", " .
		db_escape($consignment_obj->Comments) . ",'" .
		date2sql($consignment_obj->orig_order_date) . "', " .
		db_escape($consignment_obj->category_id) . "," .
		db_escape($consignment_obj->suppl_ref_no) . ")";

	db_query($sql, "The receive consignment header record could not be inserted");

	/*Get the auto increment value of the request number created from the sql above */
	$consignment_obj->consign_no = db_insert_id();


	/*Insert the receive consignment detail records */
	foreach ($consignment_obj->line_items as $line_no => $consignment_line) {
		$sql = "INSERT INTO " . TB_PREF . "consignment_details (consign_no, item_code, description, qty, color_code) VALUES (";
		$sql .= $consignment_obj->consign_no . ", " . db_escape($consignment_line->stock_id) . "," .
			db_escape($consignment_line->item_description) . "," .
			db_escape($consignment_line->quantity) . "," .
			db_escape($consignment_line->color_code) . ")";
		db_query($sql, "One of the receive consignment detail records could not be inserted");
		$consignment_obj->line_items[$line_no]->consign_detail_rec = db_insert_id();
	}

	$Refs->save(ST_RECEIVECONSIGN, $consignment_obj->consign_no, $consignment_obj->reference);

	add_audit_trail(ST_RECEIVECONSIGN, $consignment_obj->consign_no, $consignment_obj->orig_order_date);
	hook_db_postwrite($consignment_obj, ST_RECEIVECONSIGN);
	commit_transaction();

	return $consignment_obj->consign_no;
}

/*  Update receive consignment  */
function update_consignment(&$consignment_obj)
{
	begin_transaction();
	hook_db_prewrite($consignment_obj, ST_RECEIVECONSIGN);

	/*Update the receive consignment header with any changes */
	$sql = "UPDATE " . TB_PREF . "consignment SET comments=" . db_escape($consignment_obj->Comments) . ",
		consign_date='" . date2sql($consignment_obj->orig_order_date) . "', suppl_ref_no=" . db_escape($consignment_obj->suppl_ref_no) . " ";
	$sql .= " WHERE consign_no = " . $consignment_obj->consign_no;
	db_query($sql, "The receive consignment could not be updated");

	$sql = "DELETE FROM " . TB_PREF . "consignment_details WHERE consign_no="
		. db_escape($consignment_obj->consign_no);
	db_query($sql, "could not delete old purch request details");

	/*Now Update the receive consignment detail records */
	foreach ($consignment_obj->line_items as $line_no => $consignment_line) {
		$sql = "INSERT INTO " . TB_PREF . "consignment_details (consign_no, item_code, description, qty, color_code) VALUES (";
		$sql .= $consignment_obj->consign_no . ", " . db_escape($consignment_line->stock_id) . "," .
			db_escape($consignment_line->item_description) . "," .
			db_escape($consignment_line->quantity) . "," .
			db_escape($consignment_line->color_code) . ")";
		db_query($sql, "One of the receive consignment detail records could not be inserted");
	}

	add_audit_trail($consignment_obj->trans_type, $consignment_obj->consign_no, Today(), _("Updated."));
	hook_db_postwrite($consignment_obj, ST_RECEIVECONSIGN);
	commit_transaction();

	return $consignment_obj->reference;
}
/* */

function cancel_consignment($consignment_obj)
{
	begin_transaction();
	hook_db_prewrite($consignment_obj, ST_RECEIVECONSIGN);

	/*Update the receive consignment header with any changes */
	$sql = "UPDATE " . TB_PREF . "consignment SET canceled= 'Y' WHERE consign_no = " . $consignment_obj->consign_no . " ";
	db_query($sql, "The receive consignment could not be cancel");

	add_audit_trail($consignment_obj->trans_type, $consignment_obj->consign_no, Today(), _("Canceled."));
	hook_db_postwrite($consignment_obj, ST_RECEIVECONSIGN);
	commit_transaction();
}

function get_sql_for_consignment_search($consignment_number = '')
{
	$sql = "SELECT
				a.consign_no,
				a.reference,
				a.suppl_ref_no,
				b.supp_name,
				a.consign_date,
				a.comments,
				(SELECT 
					CASE
						WHEN COUNT(t1.consign_id) = 0
							THEN 'Open'
						ElSE 'Closed'
					END
				FROM " . TB_PREF . "purch_orders t1 
				WHERE t1.consign_id = a.consign_no) as Status
			FROM " . TB_PREF . "consignment a 
			LEFT JOIN " . TB_PREF . "suppliers b
				ON b.supplier_id = a.supplier_id
			INNER JOIN " . TB_PREF . "consignment_details c
				ON c.consign_no = a.consign_no 
			WHERE (SELECT COUNT(t1.consign_id) FROM " . TB_PREF . "purch_orders t1 WHERE t1.consign_id = a.consign_no) = 0 ";
	if ($consignment_number != "") {
		$sql .= " AND a.consign_no LIKE " . db_escape('%' . $consignment_number . '%') . " OR a.reference LIKE " . db_escape('%' . $consignment_number . '%') . " ";
	}

	$sql .= " GROUP BY 
				a.consign_no,
				a.reference,
				a.suppl_ref_no,
				b.supp_name,
				a.consign_date,
				a.comments 
			ORDER BY a.consign_no DESC";
	return $sql;
}

//----------------------------------------------------------------------------------------

function read_consignment_header($consign_no, &$consignment)
{
	$sql = "SELECT
			a.*,
			b.supp_name,
			b.supplier_id,
			b.curr_code,
			b.tax_group_id,
			b.tax_included
			FROM " . TB_PREF . "consignment a 
			LEFT JOIN " . TB_PREF . "suppliers b 
				ON b.supplier_id = a.supplier_id

			WHERE a.reference = " . db_escape($consign_no);

	$result = db_query($sql, "The request cannot be retrieved");

	if (db_num_rows($result) == 1) {

		$myrow = db_fetch($result);

		$consignment->trans_type = ST_RECEIVECONSIGN;
		$consignment->consign_no = $myrow["consign_no"];

		$consignment->set_supplier(
			$myrow["supplier_id"],
			$myrow["supp_name"],
			$myrow["curr_code"],
			$myrow['tax_group_id'],
			$myrow["tax_included"]
		);

		$consignment->Comments = $myrow["comments"];
		$consignment->reference = $myrow["reference"];
		$consignment->orig_order_date = sql2date($myrow["consign_date"]);
		$consignment->category_id = $myrow["category_id"];
		$consignment->suppl_ref_no = $myrow["suppl_ref_no"];
		return true;
	}

	display_db_error("FATAL : duplicate receive consignment found", "", true);
	return false;
}

//----------------------------------------------------------------------------------------

function read_consignment_serial($serial_id, &$consignment)
{
	/*now populate the line po array with the receive consignment details records */

	$sql = "SELECT 
				b.consign_detail_item,
				b.consign_no,
				b.item_code,
				b.description,
				b.color_code,
				a.serialise_lot_no,
				a.serialise_chasis_no
			FROM " . TB_PREF . "item_serialise a 
			INNER JOIN " . TB_PREF . "consignment_details b 
				ON b.consign_detail_item = a.serialise_grn_items_id 
			WHERE a.serialise_trans_type = 51
			AND a.serialise_id = " . db_escape($serial_id);

	$result = db_query($sql, "The lines on the receive consignment serial cannot be retrieved");

	if (db_num_rows($result) > 0) {
		while ($myrow = db_fetch($result)) {

			if ($consignment->add_to_consignment(
				$consignment->lines_on_order,
				$myrow["item_code"],
				1,
				$myrow["description"],
				0,
				null,
				null,
				null,
				0,
				$myrow["color_code"],
				$myrow["serialise_lot_no"],
				$myrow["serialise_chasis_no"]
			)) {
				$consignment->line_items[$consignment->lines_on_order - 1]->consign_detail_rec = $myrow["consign_detail_item"];
			}
		} /* line po from receive consignment details */
	} //end of checks on returned data set
}

//----------------------------------------------------------------------------------------

function read_consignment_items($consign_no, &$consignment, $open_items_only = false)
{
	/*now populate the line po array with the receive consignment details records */

	$sql = "SELECT 
			consign_detail_item, 
			consign_no, 
			item_code, 
			description, 
			qty,
			color_code
			FROM " . TB_PREF . "consignment_details 
			WHERE consign_no = " . db_escape($consign_no);

	$result = db_query($sql, "The lines on the receive consignment cannot be retrieved");

	if (db_num_rows($result) > 0) {
		while ($myrow = db_fetch($result)) {

			if ($consignment->add_to_consignment(
				$consignment->lines_on_order,
				$myrow["item_code"],
				$myrow["qty"],
				$myrow["description"],
				0,
				null,
				null,
				null,
				0,
				$myrow["color_code"]
			)) {
				$consignment->line_items[$consignment->lines_on_order - 1]->consign_detail_rec = $myrow["consign_detail_item"];
			}
		} /* line po from receive consignment details */
	} //end of checks on returned data set
}

//----------------------------------------------------------------------------------------

function read_consignment($consignment_no, &$consignment, $serial_id = 0, $open_items_only = false)
{
	$result = read_consignment_header($consignment_no, $consignment);

	if ($result) {
		if ($serial_id == 0) {
			read_consignment_items($consignment->consign_no, $consignment, $open_items_only);
		} else {
			read_consignment_serial($serial_id, $consignment);
		}
	}
}

//----------------------------------------------------------------------------------------

function get_consignment_items($consign_no)
{
	$sql = "SELECT item_code, qty, quantity_ordered, color_code
		FROM " . TB_PREF . "consignment_details
		WHERE consign_no=" . db_escape($consign_no)
		. " ORDER BY pr_detail_item";

	$result = db_query($sql, "could not query purch request details");
	check_db_error("Could not check that the details of the receive consignment had not been changed by another user ", $sql);
	return $result;
}
//----------------------------------------------------------------------------------------

function close_consignment(&$consignment_obj)
{
	begin_transaction();
	hook_db_prewrite($consignment_obj, ST_RECEIVECONSIGN);

	/*Update the receive consignment header with any changes */
	$sql = "UPDATE " . TB_PREF . "consignment SET status='C'";
	$sql .= " WHERE consign_no = " . $consignment_obj->consign_no;
	db_query($sql, "The receive consignment could not be closed");

	add_audit_trail($consignment_obj->trans_type, $consignment_obj->consign_no, Today(), _("receive consignment Closed"));
	hook_db_postwrite($consignment_obj, ST_RECEIVECONSIGN);
	commit_transaction();

	return $consignment_obj->consign_no;
}
//----------------------------------------------------------------------------------------

function get_sql_for_rcon_po_search($serial_no = '', $consignment_no = '')
{
	$sql = "SELECT 
				b.item_code,
				b.description,
				-- b.color_code,
				a.serialise_lot_no,
				c.consign_no,
				c.reference,
				c.suppl_ref_no,
				d.supp_name,
				c.consign_date,
				c.comments,
				(SELECT 
					CASE
						WHEN COUNT(po.consign_id) = 0
							THEN 'Open'
						ElSE 'Closed'
					END
				FROM " . TB_PREF . "purch_orders po 
				WHERE po.consign_id = c.consign_no) as Status,
				a.serialise_id
			FROM " . TB_PREF . "consignment_details b 
			LEFT JOIN " . TB_PREF . "item_serialise a 
				ON b.consign_detail_item = a.serialise_grn_items_id AND a.serialise_trans_type = 51
			INNER JOIN " . TB_PREF . "consignment c 
				ON c.consign_no = b.consign_no 
			INNER JOIN " . TB_PREF . "suppliers d 
				ON d.supplier_id = c.supplier_id
			WHERE (SELECT COUNT(t1.consign_id) FROM " . TB_PREF . "purch_orders t1 WHERE t1.consign_id = c.consign_no) = 0 ";
	if ($serial_no != "") {
		$sql .= " AND a.serialise_lot_no LIKE " . db_escape('%' . $serial_no . '%');
	}
	if ($consignment_no != "") {
		$sql .= " AND c.reference=" . db_escape($consignment_no) . "";
	}
	$sql .= " ORDER BY b.consign_no DESC";
	return $sql;
}
//----------------------------------------------------------------------------------------

function get_sql_for_rcon_po_with_serial($serial_id)
{
	$sql = "SELECT 
				b.item_code,
				b.description,
				b.color_code,
				a.serialise_lot_no,
				a.serialise_chasis_no,
				c.consign_no,
				c.reference,
				c.suppl_ref_no,
				d.supp_name,
				d.supplier_id,
				c.consign_date,
				c.category_id,
				c.comments
			FROM " . TB_PREF . "item_serialise a 
			INNER JOIN " . TB_PREF . "consignment_details b 
				ON b.consign_detail_item = a.serialise_grn_items_id 
			INNER JOIN " . TB_PREF . "consignment c 
				ON c.consign_no = b.consign_no 
			INNER JOIN " . TB_PREF . "suppliers d 
				ON d.supplier_id = c.supplier_id
			WHERE a.serialise_trans_type = 51
			AND a.serialise_id = " . db_escape($serial_id) . "";

	$result = db_query($sql, "The serial cannot be retrieved");

	return $result;
}
