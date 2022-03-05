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

function get_supplier_details_to_order(&$order, $supplier_id)
{
	$sql = "SELECT curr_code, supp_name, tax_group_id, supp.tax_included, dimension_id, dimension2_id,
			supp.credit_limit - Sum((ov_amount + ov_gst + ov_discount)) as cur_credit,
				terms.terms, terms.days_before_due, terms.day_in_following_month
		FROM " . TB_PREF . "suppliers supp
			 LEFT JOIN " . TB_PREF . "supp_trans trans ON supp.supplier_id = trans.supplier_id
			 LEFT JOIN " . TB_PREF . "payment_terms terms ON supp.payment_terms=terms.terms_indicator
		WHERE supp.supplier_id = " . db_escape($supplier_id) . "
		GROUP BY
			  supp.supp_name";

	$result = db_query($sql, "The supplier details could not be retreived");
	$myrow = db_fetch($result);

	$order->credit = $myrow["cur_credit"];
	$order->terms = array(
		'description' => $myrow['terms'],
		'days_before_due' => $myrow['days_before_due'],
		'day_in_following_month' => $myrow['day_in_following_month']
	);

	$_POST['supplier_id'] = $supplier_id;
	$_POST['supplier_name'] = $myrow["supp_name"];
	$_POST['curr_code'] = $myrow["curr_code"];
	$_POST['dimension'] = $myrow["dimension_id"];
	$_POST['dimension2'] = $myrow["dimension2_id"];

	$order->set_supplier(
		$supplier_id,
		$myrow["supp_name"],
		$myrow["curr_code"],
		$myrow["tax_group_id"],
		$myrow["tax_included"]
	);
}

//----------------------------------------------------------------------------------------

function delete_po($po)
{
	global $Refs;

	begin_transaction();
	hook_db_prevoid($po, ST_PURCHORDER);
	$sql = "DELETE FROM " . TB_PREF . "purch_orders WHERE order_no=" . db_escape($po);
	db_query($sql, "The order header could not be deleted");

	$sql = "DELETE FROM " . TB_PREF . "purch_order_details WHERE order_no =" . db_escape($po);
	db_query($sql, "The order detail lines could not be deleted");

	$Refs->restore_last(ST_PURCHORDER, $po);
	commit_transaction();
}

//----------------------------------------------------------------------------------------

function copy_pr_to_po(&$po_obj)
{
	global $Refs;
	global $def_coy;

	begin_transaction();
	hook_db_prewrite($po_obj, ST_PURCHORDER);

	/*Insert to purchase order header record */
	$sql = "INSERT INTO " . TB_PREF . "purch_orders (supplier_id, Comments, ord_date, reference, 
     	requisition_no, into_stock_location, delivery_address, total, tax_included, item_group_id, category_id, prep_amount, purch_type_id, served_status) VALUES(";
	$sql .= db_escape($po_obj->supplier_id) . "," .
		db_escape($po_obj->Comments) . ",'" .
		date2sql($po_obj->orig_order_date) . "', " .
		db_escape($po_obj->po_reference) . ", " .
		db_escape($po_obj->reference) . ", " .
		db_escape($po_obj->Location) . ", " .
		db_escape($po_obj->delivery_address) . ", " .
		db_escape($po_obj->get_trans_total()) . ", " .
		db_escape($po_obj->tax_included) . ", " .
		db_escape($po_obj->category_id) . ", " .
		db_escape($po_obj->category_id) . ", " .
		db_escape($po_obj->prep_amount) . ", " .
		db_escape($po_obj->purch_type_id) . ", " .
		db_escape($po_obj->served_status) . ")";

	db_query($sql, "The purchase order header record could not be inserted");

	/*Get the auto increment value of the order number created from the sql above */
	$po_obj->order_no = db_insert_id();

	/*Insert the purchase order detail records */
	foreach ($po_obj->line_items as $line_no => $po_line) {

		// Update PR Details

		$sql = "UPDATE " . TB_PREF . "purch_request_details
			SET quantity_ordered = quantity_ordered + " . db_escape($po_line->receive_qty) . " 
			WHERE pr_detail_item = " . db_escape($po_line->pr_detail_rec);
		db_query($sql, "a purchase request details record could not be updated. This receipt of goods has not been processed ");

		$sql = "INSERT INTO " . TB_PREF . "purch_order_details (order_no, item_code, color_code, description,unit_price, quantity_ordered) VALUES (";
		$sql .= $po_obj->order_no . ", " . db_escape($po_line->stock_id) . "," .
			db_escape($po_line->color_code) . "," .
			db_escape($po_line->item_description) . "," .
			db_escape($po_line->price) . ", " .
			db_escape($po_line->receive_qty) . ")";
		db_query($sql, "One of the purchase order detail records could not be inserted");
		$po_obj->line_items[$line_no]->po_detail_rec = db_insert_id();
	}

	$sql = "SELECT a.pr_no FROM " . TB_PREF . "purch_request_details a
			WHERE a.pr_no = " . db_escape($po_obj->supp_ref) . "
			HAVING sum(a.qty) <= sum(a.quantity_ordered)";

	$result = db_query($sql, "could not query purch request details");
	if (db_num_rows($result) > 0) {
		$sql = "UPDATE " . TB_PREF . "purch_request SET status = 'C' WHERE pr_no = " . db_escape($po_obj->supp_ref) . " ";
		db_query($sql, "a purchase request record could not be updated.");
	}

	$Refs->save(ST_PURCHORDER, $po_obj->order_no, $po_obj->reference);

	add_audit_trail(ST_PURCHORDER, $po_obj->order_no, $po_obj->orig_order_date);
	hook_db_postwrite($po_obj, ST_PURCHORDER);
	commit_transaction();

	return $po_obj->po_reference;
}

//----------------------------------------------------------------------------------------

function copy_rcon_to_po(&$po_obj)
{
	global $Refs;

	begin_transaction();
	hook_db_prewrite($po_obj, ST_PURCHORDER);

	/*Insert to purchase order header record */
	$sql = "INSERT INTO " . TB_PREF . "purch_orders (supplier_id, Comments, ord_date, reference, 
		into_stock_location, delivery_address, total, tax_included, item_group_id, category_id, prep_amount, purch_type_id, consign_id, serialise_id) VALUES(";
	$sql .= db_escape($po_obj->supplier_id) . "," .
		db_escape($po_obj->Comments) . ",'" .
		date2sql($po_obj->orig_order_date) . "', " .
		db_escape($po_obj->po_reference) . ", " .
		db_escape($po_obj->Location) . ", " .
		db_escape($po_obj->delivery_address) . ", " .
		db_escape($po_obj->get_trans_total()) . ", " .
		db_escape($po_obj->tax_included) . ", " .
		db_escape($po_obj->category_id) . ", " .
		db_escape($po_obj->category_id) . ", " .
		db_escape($po_obj->prep_amount) . ", " .
		db_escape($po_obj->purch_type_id) . ", " .
		db_escape($po_obj->consign_no) . ", " .
		db_escape($po_obj->serialise_id) . ")";

	db_query($sql, "The purchase order header record could not be inserted");

	/*Get the auto increment value of the order number created from the sql above */
	$po_obj->order_no = db_insert_id();

	/*Insert the purchase order detail records */
	foreach ($po_obj->line_items as $line_no => $po_line) {

		// Update PR Details

		$sql = "UPDATE " . TB_PREF . "purch_request_details
			SET quantity_ordered = quantity_ordered + " . db_escape($po_line->receive_qty) . " 
			WHERE pr_detail_item = " . db_escape($po_line->pr_detail_rec);
		db_query($sql, "a purchase request details record could not be updated. This receipt of goods has not been processed ");

		$sql = "INSERT INTO " . TB_PREF . "purch_order_details (order_no, item_code, color_code, description,unit_price, quantity_ordered) VALUES (";
		$sql .= $po_obj->order_no . ", " . db_escape($po_line->stock_id) . "," .
			db_escape($po_line->color_code) . "," .
			db_escape($po_line->item_description) . "," .
			db_escape($po_line->price) . ", " .
			db_escape($po_line->receive_qty) . ")";
		db_query($sql, "One of the purchase order detail records could not be inserted");
		$po_obj->line_items[$line_no]->po_detail_rec = db_insert_id();
	}

	$sql = "SELECT a.pr_no FROM " . TB_PREF . "purch_request_details a
			WHERE a.pr_no = " . db_escape($po_obj->supp_ref) . "
			HAVING sum(a.qty) <= sum(a.quantity_ordered)";

	$result = db_query($sql, "could not query purch request details");
	if (db_num_rows($result) > 0) {
		$sql = "UPDATE " . TB_PREF . "purch_request SET status = 'C' WHERE pr_no = " . db_escape($po_obj->supp_ref) . " ";
		db_query($sql, "a purchase request record could not be updated.");
	}

	$Refs->save(ST_PURCHORDER, $po_obj->order_no, $po_obj->reference);

	add_audit_trail(ST_PURCHORDER, $po_obj->order_no, $po_obj->orig_order_date);
	hook_db_postwrite($po_obj, ST_PURCHORDER);
	commit_transaction();

	return $po_obj->po_reference;
}

//----------------------------------------------------------------------------------------

function add_po(&$po_obj)
{
	global $Refs;

	begin_transaction();
	hook_db_prewrite($po_obj, ST_PURCHORDER);

	/*Insert to purchase order header record */
	$sql = "INSERT INTO " . TB_PREF . "purch_orders(supplier_id, Comments, ord_date, reference, 
     	requisition_no, into_stock_location, delivery_address, total, tax_included, prep_amount, served_status, item_group_id, category_id, purch_type_id) VALUES(";
	$sql .= db_escape($po_obj->supplier_id) . "," .
		db_escape($po_obj->Comments) . ",'" .
		date2sql($po_obj->orig_order_date) . "', " .
		db_escape($po_obj->reference) . ", " .
		db_escape($po_obj->supp_ref) . ", " .
		db_escape($po_obj->Location) . ", " .
		db_escape($po_obj->delivery_address) . ", " .
		db_escape($po_obj->get_trans_total()) . ", " .
		db_escape($po_obj->tax_included) . ", " .
		db_escape($po_obj->prep_amount) . ", " .
		db_escape($po_obj->served_status) . ", " .
		db_escape($po_obj->category_id) . ", " .
		db_escape($po_obj->category_id) . ", " .
		db_escape($po_obj->purch_type_id) . ")";

	db_query($sql, "The purchase order header record could not be inserted");

	/*Get the auto increment value of the order number created from the sql above */
	$po_obj->order_no = db_insert_id();

	/*Insert the purchase order detail records */
	foreach ($po_obj->line_items as $line_no => $po_line) {
		$sql = "INSERT INTO " . TB_PREF . "purch_order_details (order_no, item_code, description, delivery_date,	unit_price,	quantity_ordered, color_code) VALUES (";
		$sql .= $po_obj->order_no . ", " . db_escape($po_line->stock_id) . "," .
			db_escape($po_line->item_description) . ",'" .
			date2sql($po_line->req_del_date) . "'," .
			db_escape($po_line->price) . ", " .
			db_escape($po_line->quantity) . ", " .
			db_escape($po_line->color_code) . ")";
		db_query($sql, "One of the purchase order detail records could not be inserted");
		$po_obj->line_items[$line_no]->po_detail_rec = db_insert_id();
	}

	$Refs->save(ST_PURCHORDER, $po_obj->order_no, $po_obj->reference);

	add_audit_trail(ST_PURCHORDER, $po_obj->order_no, $po_obj->orig_order_date);
	hook_db_postwrite($po_obj, ST_PURCHORDER);
	commit_transaction();

	return $po_obj->reference;
}

//----------------------------------------------------------------------------------------

function update_po(&$po_obj)
{
	begin_transaction();
	hook_db_prewrite($po_obj, ST_PURCHORDER);

	/*Update the purchase order header with any changes */
	$sql = "UPDATE " . TB_PREF . "purch_orders SET Comments=" . db_escape($po_obj->Comments) . ",
		requisition_no= " . db_escape($po_obj->supp_ref) . ",
		into_stock_location=" . db_escape($po_obj->Location) . ",
		ord_date='" . date2sql($po_obj->orig_order_date) . "',
		delivery_address=" . db_escape($po_obj->delivery_address) . ",
		total=" . db_escape($po_obj->get_trans_total()) . ",
		prep_amount=" . db_escape($po_obj->prep_amount) . ",
		tax_included=" . db_escape($po_obj->tax_included) . ",
		served_status=" . db_escape($po_obj->served_status) . ",
		purch_type_id=" . db_escape($po_obj->purch_type_id);
	$sql .= " WHERE order_no = " . $po_obj->order_no;
	db_query($sql, "The purchase order could not be updated");

	$sql = "DELETE FROM " . TB_PREF . "purch_order_details WHERE order_no="
		. db_escape($po_obj->order_no);
	db_query($sql, "could not delete old purch order details");

	/*Now Update the purchase order detail records */
	foreach ($po_obj->line_items as $po_line) {
		$sql = "INSERT INTO " . TB_PREF . "purch_order_details (po_detail_item, order_no, item_code, 
    		description, delivery_date, unit_price, quantity_ordered, quantity_received, color_code) VALUES ("
			. db_escape($po_line->po_detail_rec ? $po_line->po_detail_rec : 0) . ","
			. $po_obj->order_no . ","
			. db_escape($po_line->stock_id) . ","
			. db_escape($po_line->item_description) . ",'"
			. date2sql($po_line->req_del_date) . "',"
			. db_escape($po_line->price) . ", "
			. db_escape($po_line->quantity) . ", "
			. db_escape($po_line->qty_received) . " ,"
			. db_escape($po_line->color_code) . ")";
		db_query($sql, "One of the purchase order detail records could not be updated");
	}

	reallocate_payments($po_obj->order_no, ST_PURCHORDER, $po_obj->orig_order_date, $po_obj->get_trans_total(), $po_obj->prepayments, $po_obj->supplier_id);

	add_audit_trail($po_obj->trans_type, $po_obj->order_no, Today(), _("Updated."));
	hook_db_postwrite($po_obj, ST_PURCHORDER);
	commit_transaction();

	return $po_obj->reference;
}

//----------------------------------------------------------------------------------------

function read_po_header($order_no, &$order, $rr_read = true)
{
	$sql = "SELECT po.*, supplier.*, loc.location_name,
		CASE
			WHEN po.draft_status = 0 AND po.posted = 'N'
				THEN 'Draft'
			WHEN po.draft_status = 1 AND po.posted = 'N'
				THEN 'Approved'
			WHEN po.draft_status = 2 AND po.posted = 'N'
				THEN 'Disapproved'
			WHEN po.draft_status = 1 AND po.posted = 'Y' AND chk.isopen
				THEN 'Open'
			ELSE 'Closed'
		END as po_status,
		CASE
			WHEN po.consign_id <> 0
				THEN (SELECT t1.reference FROM " . TB_PREF ."consignment t1 WHERE t1.consign_no = po.consign_id)
			ELSE po.requisition_no
		END as trans_ref,
		CASE
			WHEN po.consign_id <> 0
				THEN 'Consignment'
			ELSE 'Non-Consignment'
		END as is_consign,
		(SELECT t1.suppl_ref_no FROM " . TB_PREF . "consignment t1 WHERE t1.consign_no = po.consign_id) as suppl_ref_no
		FROM " . TB_PREF . "purch_orders po
			LEFT JOIN (
				SELECT order_no, SUM(quantity_ordered-quantity_received + quantity_ordered-qty_invoiced) isopen
				FROM " . TB_PREF . "purch_order_details
				GROUP BY order_no
			) chk ON chk.order_no=po.order_no,"
		. TB_PREF . "suppliers supplier,"
		. TB_PREF . "locations loc
		WHERE po.supplier_id = supplier.supplier_id
		AND loc.loc_code = into_stock_location";

	if ($rr_read) {
		$sql .= " AND po.order_no = " . db_escape($order_no);
	} else {
		$sql .= " AND po.reference = " . db_escape($order_no);
	}

	$result = db_query($sql, "The order cannot be retrieved");

	if (db_num_rows($result) == 1) {

		$myrow = db_fetch($result);

		$order->trans_type = ST_PURCHORDER;
		$order->order_no = $myrow["order_no"];

		$order->set_supplier(
			$myrow["supplier_id"],
			$myrow["supp_name"],
			$myrow["curr_code"],
			$myrow['tax_group_id'],
			$myrow["tax_included"]
		);

		$order->credit = get_current_supp_credit($order->supplier_id);

		$order->orig_order_date = sql2date($myrow["ord_date"]);
		$order->Comments = $myrow["comments"];
		$order->Location = $myrow["into_stock_location"];
		$order->supp_ref = $myrow["trans_ref"];
		$order->reference = $myrow["reference"];
		$order->delivery_address = $myrow["delivery_address"];
		$order->alloc = $myrow["alloc"];
		$order->prep_amount = $myrow["prep_amount"];
		$order->prepayments = get_payments_for($order_no, ST_PURCHORDER, $myrow["supplier_id"]);

		$order->served_status = $myrow["served_status"];
		$order->category_id = $myrow["category_id"];
		$order->purch_type_id = $myrow["purch_type_id"];
		$order->po_status = $myrow["po_status"];
		$order->po_no = $myrow["reference"];
		$order->is_consign = $myrow["is_consign"];
		$order->suppl_ref_no = $myrow["suppl_ref_no"];
		return true;
	}

	display_db_error("FATAL : duplicate purchase order found", "", true);
	return false;
}

//----------------------------------------------------------------------------------------

function read_po_items($order_no, &$order, $open_items_only = false)
{
	/*now populate the line po array with the purchase order details records */

	$sql = "SELECT poline.*, units, CONCAT_WS(' **',brand.name,man.name) as description,
		itemserial.serialise_lot_no,
		itemserial.serialise_chasis_no
		FROM " . TB_PREF . "purch_order_details poline
			LEFT JOIN " . TB_PREF . "stock_master item	ON poline.item_code=item.stock_id
			LEFT JOIN " . TB_PREF . "item_brand brand ON item.brand=brand.id
			LEFT JOIN " . TB_PREF . "item_manufacturer man ON item.manufacturer=man.id
			INNER JOIN " . TB_PREF . "purch_orders porder ON porder.order_no = poline.order_no
			LEFT JOIN " . TB_PREF . "item_serialise itemserial ON itemserial.serialise_id = porder.serialise_id
		WHERE poline.order_no =" . db_escape($order_no);

	if ($open_items_only)
		$sql .= " AND (poline.quantity_ordered > poline.quantity_received) ";

	$sql .= " ORDER BY po_detail_item";

	$result = db_query($sql, "The lines on the purchase order cannot be retrieved");

	if (db_num_rows($result) > 0) {
		while ($myrow = db_fetch($result)) {
			$data = get_purchase_data($order->supplier_id, $myrow['item_code']);
			if ($data !== false) {
				if ($data['supplier_description'] != "")
					$myrow['description'] = $data['supplier_description'];
			}
			if (is_null($myrow["units"])) {
				$units = "";
			} else {
				$units = $myrow["units"];
			}

			if ($order->add_to_order(
				$order->lines_on_order,
				$myrow["item_code"],
				$myrow["quantity_ordered"],
				$myrow["description"],
				$myrow["unit_price"],
				$units,
				sql2date($myrow["delivery_date"]),
				$myrow["qty_invoiced"],
				$myrow["quantity_received"],
				0,
				$myrow["serialise_lot_no"],
				$myrow["color_code"],
				$myrow["serialise_chasis_no"]
			)) {
				$order->line_items[$order->lines_on_order - 1]->po_detail_rec = $myrow["po_detail_item"];
			}
		} /* line po from purchase order details */
	} //end of checks on returned data set
}

//----------------------------------------------------------------------------------------

function read_po($order_no, &$order, $open_items_only = false, $rr_read = true)
{
	$result = read_po_header($order_no, $order, $rr_read);

	if ($result)
		read_po_items($order->order_no, $order, $open_items_only);
}

//----------------------------------------------------------------------------------------

function get_po_items($order_no)
{
	$sql = "SELECT item_code, quantity_ordered, quantity_received, qty_invoiced, color_code
		FROM " . TB_PREF . "purch_order_details
		WHERE order_no=" . db_escape($order_no)
		. " ORDER BY po_detail_item";

	$result = db_query($sql, "could not query purch order details");
	check_db_error("Could not check that the details of the purchase order had not been changed by another user ", $sql);
	return $result;
}
//----------------------------------------------------------------------------------------

function get_short_info($stock_id)
{
	global $def_coy;
	set_global_connection($def_coy);
	$sql = "SELECT description, units, mb_flag
		FROM " . TB_PREF . "stock_master WHERE stock_id = " . db_escape($stock_id);

	$result = db_query($sql, "The stock details for " . $stock_id . " could not be retrieved");
	set_global_connection();
	return $result;
}

//modified by spyrax10
function get_sql_for_po_search_completed(
	$from,
	$to,
	$supplier_id = ALL_TEXT,
	$location = ALL_TEXT,
	$order_number = '',
	$stock_id = '',
	$also_closed = false,
	$pr_no = '', $cat_id, $stat_type = ''
) {
	
	$sql = "SELECT porder.order_no, SC.description, porder.reference, X.Status, supplier.supp_name, 
		CASE WHEN porder.purch_type_id = 1 THEN 'HO' ELSE 'Local' END as purch_type,
		CASE WHEN porder.consign_id <> 0 THEN 'Consignment' ELSE 'Non-Consignment' END as is_consign,
		CASE WHEN porder.served_status = 0 THEN 'Normal Served' 
			WHEN porder.served_status = 1 THEN 'Overserved' ELSE 'Wrong Served' END as served_status,
		
		location.location_name,
		
		CASE WHEN porder.consign_id <> 0 THEN (SELECT t1.reference FROM consignment t1 WHERE t1.consign_no = porder.consign_id)
		ELSE porder.requisition_no END as trans_ref,

		porder.ord_date, supplier.curr_code, 
		Sum(line.unit_price*line.quantity_ordered) AS OrderValue,
		Sum(line.delivery_date < '" . date2sql(Today()) . "' AND (line.quantity_ordered > line.quantity_received)) As OverDue,
		
		porder.into_stock_location,
		
		CASE WHEN porder.draft_status = 0 AND porder.posted = 'N' THEN '1'
			WHEN porder.draft_status = 1 AND porder.posted = 'N' THEN '2'
			WHEN porder.draft_status = 2 AND porder.posted = 'N' THEN '3'
			WHEN porder.draft_status = 1 AND porder.posted = 'Y' 
				THEN CASE
					WHEN (SELECT SUM(t1.quantity_received) FROM " . TB_PREF . "purch_order_details t1 WHERE t1.order_no = porder.order_no GROUP BY t1.order_no) = 0 
					THEN '4'
					WHEN (SELECT SUM(t1.quantity_received) FROM " . TB_PREF . "purch_order_details t1 WHERE t1.order_no = porder.order_no GROUP BY t1.order_no) > 0
							AND (SELECT SUM(t1.quantity_ordered) FROM " . TB_PREF . "purch_order_details t1 WHERE t1.order_no = porder.order_no GROUP BY t1.order_no) 
							<> (SELECT SUM(t1.quantity_received) FROM " . TB_PREF . "purch_order_details t1 WHERE t1.order_no = porder.order_no GROUP BY t1.order_no) 
					THEN '5'
					WHEN (SELECT SUM(t1.quantity_ordered) FROM " . TB_PREF . "purch_order_details t1 WHERE t1.order_no = porder.order_no GROUP BY t1.order_no) 
						= (SELECT SUM(t1.quantity_received) FROM " . TB_PREF . "purch_order_details t1 WHERE t1.order_no = porder.order_no GROUP BY t1.order_no) 
					THEN '6' END
		END as Status_no

		FROM " . TB_PREF . "purch_orders as porder
			LEFT JOIN (SELECT order_no, SUM(quantity_ordered-quantity_received + quantity_ordered-qty_invoiced) isopen
					FROM " . TB_PREF . "purch_order_details
					GROUP BY order_no
			) chk ON chk.order_no=porder.order_no 

			LEFT JOIN " . TB_PREF . "stock_category SC ON porder.category_id = SC.category_id

			LEFT JOIN (
			SELECT A.order_no,
				CASE WHEN A.draft_status = 0 AND A.posted = 'N' THEN 'Draft'
					WHEN A.draft_status = 1 AND A.posted = 'N' THEN 'Approved'
					WHEN A.draft_status = 2 AND A.posted = 'N' THEN 'Disapproved'
					WHEN A.draft_status = 1 AND A.posted = 'Y' 
				THEN
					CASE WHEN (SELECT SUM(t1.quantity_received) FROM " . TB_PREF . "purch_order_details t1 WHERE t1.order_no = A.order_no GROUP BY t1.order_no) = 0
					THEN 'Open'
					
					WHEN (SELECT SUM(t1.quantity_received) FROM " . TB_PREF . "purch_order_details t1 WHERE t1.order_no = A.order_no GROUP BY t1.order_no) > 0
						AND (SELECT SUM(t1.quantity_ordered) FROM " . TB_PREF . "purch_order_details t1 WHERE t1.order_no = A.order_no GROUP BY t1.order_no) 
						<> (SELECT SUM(t1.quantity_received) FROM " . TB_PREF . "purch_order_details t1 WHERE t1.order_no = A.order_no GROUP BY t1.order_no) 
					THEN 'Partially Received'
						
					WHEN (SELECT SUM(t1.quantity_ordered) FROM " . TB_PREF . "purch_order_details t1 WHERE t1.order_no = A.order_no GROUP BY t1.order_no) 
						= (SELECT SUM(t1.quantity_received) FROM " . TB_PREF . "purch_order_details t1 WHERE t1.order_no = A.order_no GROUP BY t1.order_no) 
					THEN 'Closed' END
				END as Status 

			FROM " . TB_PREF . "purch_orders A
			) X ON porder.order_no = X.order_no,

			" . TB_PREF . "purch_order_details as line, 
			" . TB_PREF . "suppliers as supplier, 
			" . TB_PREF . "locations as location

		WHERE porder.order_no = line.order_no
		AND porder.supplier_id = supplier.supplier_id
		AND location.loc_code = porder.into_stock_location ";

	if ($stat_type != '')
		$sql .= " AND X.Status = " .db_escape($stat_type);
	if ($cat_id != -1)
		$sql .= " AND porder.category_id = " .db_escape($cat_id);
	if ($supplier_id != ALL_TEXT)
		$sql .= "AND supplier.supplier_id=" . $supplier_id . " ";
	if ($order_number != "") {
		$sql .= "AND porder.reference LIKE " . db_escape('%' . $order_number . '%');
	} else if ($pr_no != "") {
		$sql .= "AND porder.requisition_no LIKE " . db_escape('%' . $pr_no . '%');
	} else {

		$data_after = date2sql($from);
		$date_before = date2sql($to);

		$sql .= " AND porder.ord_date >= '$data_after'";
		$sql .= " AND porder.ord_date <= '$date_before'";

		if ($location != ALL_TEXT) {
			$sql .= " AND porder.into_stock_location = " . db_escape($location);
		}
		if ($stock_id !== '') {
			$sql .= " AND line.item_code=" . db_escape($stock_id);
		}
		if ($supplier_id != ALL_TEXT)
			$sql .= " AND supplier.supplier_id=" . db_escape($supplier_id);
	}

	if (!$also_closed)
		$sql .= " AND isopen";
		
	//Modified by spyrax10
	$sql .= " GROUP BY porder.ord_date, porder.order_no, Status_no "; 
	$sql .= " ORDER BY porder.ord_date DESC, porder.order_no desc, Status_no asc ";
	//
	
	return $sql;
}

function get_sql_for_po_search($from, $to, $supplier_id = ALL_TEXT, $location = ALL_TEXT, $order_number = '', $stock_id = '')
{
	//Modified by spyrax10 19/Oct/2021
	$sql = "SELECT 
		porder.order_no, 
		porder.reference,
		supplier.supp_name,
		SC.description, 
		location.location_name,
		CASE
			WHEN porder.consign_id <> 0
				THEN 'Consignment'
			ELSE 'Non-Consignment'
		END as is_consign,
		CASE
			WHEN porder.consign_id <> 0
			THEN (SELECT t1.reference FROM " . TB_PREF ."consignment t1 WHERE t1.consign_no = porder.consign_id)
			ELSE porder.requisition_no
		END trans_ref, 
		porder.ord_date,
        supplier.curr_code,
		Sum(line.unit_price*line.quantity_ordered) AS OrderValue,
		Sum(line.delivery_date < '" . date2sql(Today()) . "'
		AND (line.quantity_ordered > line.quantity_received)) As OverDue,
        porder.draft_status as draft_status,
		porder.category_id

		FROM " . TB_PREF . "purch_orders as porder,"
		. TB_PREF . "purch_order_details as line, "
		. TB_PREF . "suppliers as supplier, "
		. TB_PREF . "locations as location, "
		. TB_PREF . "stock_category as SC

		WHERE porder.order_no = line.order_no
		AND porder.supplier_id = supplier.supplier_id
		AND location.loc_code = porder.into_stock_location
		AND SC.category_id = porder.category_id
		AND (line.quantity_ordered > line.quantity_received) AND porder.posted='Y'";

	if ($order_number != "") {
		$sql .= "AND porder.reference LIKE " . db_escape('%' . $order_number . '%');
	} else {
		$data_after = date2sql($from);
		$data_before = date2sql($to);

		$sql .= "  AND porder.ord_date >= '$data_after'";
		$sql .= "  AND porder.ord_date <= '$data_before'";

		if ($location != ALL_TEXT) {
			$sql .= " AND porder.into_stock_location = " . db_escape($location);
		}

		if ($stock_id != '') {
			$sql .= " AND line.item_code=" . db_escape($stock_id);
		}
		if ($supplier_id != ALL_TEXT)
			$sql .= " AND supplier.supplier_id=" . db_escape($supplier_id);
	} //end not order number selected

	//Modified by spyrax10
	$sql .= " GROUP BY porder.order_no ";
	$sql .= " ORDER BY porder.order_no desc";
	//
	
	return $sql;
}

/* Added by Ronelle 11/5/2020 */
function update_po_draft_status(&$po_obj)
{
	begin_transaction();
	hook_db_prewrite($po_obj, ST_PURCHORDER);

	/*Update the purchase order draft status */
	$sql = "UPDATE " . TB_PREF . "purch_orders SET draft_status=" . db_escape($po_obj->draft_status);
	$sql .= " WHERE order_no = " . $po_obj->order_no;
	db_query($sql, "The purchase order could not be updated");

	add_audit_trail($po_obj->trans_type, $po_obj->order_no, Today(), _("Update Draft Status."));
	hook_db_postwrite($po_obj, ST_PURCHORDER);
	commit_transaction();

	return $po_obj->reference;
}
/* */

/* Added by Ronelle 11/19/2020 */
function draft_to_po(&$po_obj)
{
	begin_transaction();
	hook_db_prewrite($po_obj, ST_PURCHORDER);
	$date = date('Y,m,d', strtotime($po_obj->orig_order_date)); //Added by Albert
	/*Update the purchase order draft status */
	$sql = "UPDATE " . TB_PREF . "purch_orders SET posted='Y',ord_date=" . db_escape($date).", into_stock_location=".db_escape($po_obj->Location);
	$sql .= " WHERE order_no = " . $po_obj->order_no;
	db_query($sql, "The purchase order could not be updated");

	add_audit_trail($po_obj->trans_type, $po_obj->order_no, Today(), _("Approved Draft PO Posted."));
	hook_db_postwrite($po_obj, ST_PURCHORDER);
	commit_transaction();

	return $po_obj->order_no;
}
/* */

/* Added by Ronelle 11/21/2020 */
function check_status_po($po_no)
{
	$sql = "SELECT 
				CASE
				WHEN porder.draft_status = 0 AND porder.posted = 'N'
					THEN 'Draft'
				WHEN porder.draft_status = 1 AND porder.posted = 'N'
					THEN 'Approved'
				WHEN porder.draft_status = 2 AND porder.posted = 'N'
					THEN 'Disapproved'
				WHEN porder.draft_status = 1 AND porder.posted = 'Y' AND chk.isopen
					THEN 'Open'
				ELSE 'Closed'
			END as Status
			FROM " . TB_PREF . "purch_orders porder LEFT JOIN (
					SELECT order_no, SUM(quantity_ordered-quantity_received + quantity_ordered-qty_invoiced) isopen
					FROM " . TB_PREF . "purch_order_details
					GROUP BY order_no
				) chk ON chk.order_no=porder.order_no
			WHERE porder.reference = " . db_escape($po_no);

	$result = db_query($sql, "The status for purchase order " . $po_no . " could not be retrieved");
	if (db_num_rows($result) == 1) {
		$row = db_fetch_row($result);
		return $row[0];
	}
}
/* */

//Added by spyrax10
function get_pr_value($reference) {
	
	$sql = "SELECT * FROM " . TB_PREF . "purch_request 
		WHERE reference = ".db_escape($reference);

	$result = db_query($sql, "Can't get PR values! (spyrax10)");
	return db_fetch($result);
}