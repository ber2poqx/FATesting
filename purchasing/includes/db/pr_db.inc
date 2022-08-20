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
function get_pr_expired()
{
	$sql = "SELECT value FROM " . TB_PREF . "sys_prefs WHERE name = 'pr_expired'";
	$result = db_query($sql, "could not get sys prefs pr expiration");

	$row = db_fetch_row($result);
	return $row[0];
}

function add_pr(&$pr_obj)
{
	global $Refs;

	begin_transaction();
	hook_db_prewrite($pr_obj, ST_PURCHREQUEST);

	/*Insert to purchase request header record */
	$sql = "INSERT INTO " . TB_PREF . "purch_request (supplier_id, reference ,comments, pr_date , required_date, purch_type_id, item_group_id, served_status, pr_expired) VALUES(";
	$sql .= db_escape($pr_obj->supplier_id) . "," .
		db_escape($pr_obj->reference) . ", " .
		db_escape($pr_obj->Comments) . ",'" .
		date2sql($pr_obj->orig_order_date) . "', '" .
		date2sql($pr_obj->orig_order_date) . "', " .
		db_escape($pr_obj->purch_type_id) . "," .
		db_escape($pr_obj->category_id) . "," .
		db_escape($pr_obj->served_status) . "," .
		db_escape(get_pr_expired()) . ")";

	db_query($sql, "The purchase request header record could not be inserted");

	/*Get the auto increment value of the request number created from the sql above */
	$pr_obj->pr_no = db_insert_id();


	/*Insert the purchase request detail records */
	foreach ($pr_obj->line_items as $line_no => $pr_line) {
		$sql = "INSERT INTO " . TB_PREF . "purch_request_details (pr_no, item_code, description, qty, color_code) VALUES (";
		$sql .= $pr_obj->pr_no . ", " . db_escape($pr_line->stock_id) . "," .
			db_escape($pr_line->item_description) . "," .
			db_escape($pr_line->quantity) . "," .
			db_escape($pr_line->color_code) . ")";
		db_query($sql, "One of the purchase request detail records could not be inserted");
		$pr_obj->line_items[$line_no]->pr_detail_rec = db_insert_id();
	}

	$Refs->save(ST_PURCHREQUEST, $pr_obj->pr_no, $pr_obj->reference);

	add_audit_trail(ST_PURCHREQUEST, $pr_obj->pr_no, $pr_obj->orig_order_date);
	hook_db_postwrite($pr_obj, ST_PURCHREQUEST);
	commit_transaction();

	return $pr_obj->reference;
}

/*  Update Purchase Request  */
function update_pr(&$pr_obj)
{
	begin_transaction();
	hook_db_prewrite($pr_obj, ST_PURCHREQUEST);

	/*Update the purchase request header with any changes */
	$sql = "UPDATE " . TB_PREF . "purch_request SET comments=" . db_escape($pr_obj->Comments) . ",
		purch_type_id= " . db_escape($pr_obj->purch_type_id) . ",
		pr_date='" . date2sql($pr_obj->orig_order_date) . "',
		required_date='" . date2sql($pr_obj->orig_order_date) . "'";
	$sql .= " WHERE pr_no = " . $pr_obj->pr_no;
	db_query($sql, "The purchase request could not be updated");

	$sql = "DELETE FROM " . TB_PREF . "purch_request_details WHERE pr_no="
		. db_escape($pr_obj->pr_no);
	db_query($sql, "could not delete old purch request details");

	/*Now Update the purchase request detail records */
	foreach ($pr_obj->line_items as $line_no => $pr_line) {
		$sql = "INSERT INTO " . TB_PREF . "purch_request_details (pr_no, item_code, description, qty, color_code) VALUES (";
		$sql .= $pr_obj->pr_no . ", " . db_escape($pr_line->stock_id) . "," .
			db_escape($pr_line->item_description) . "," .
			db_escape($pr_line->quantity) . "," .
			db_escape($pr_line->color_code) . ")";
		db_query($sql, "One of the purchase request detail records could not be inserted");
	}

	add_audit_trail($pr_obj->trans_type, $pr_obj->pr_no, Today(), _("Updated."));
	hook_db_postwrite($pr_obj, ST_PURCHREQUEST);
	commit_transaction();

	return $pr_obj->reference;
}
/* */

function cancel_pr($pr_obj)
{
	begin_transaction();
	hook_db_prewrite($pr_obj, ST_PURCHREQUEST);

	/*Update the purchase request header with any changes */
	$sql = "UPDATE " . TB_PREF . "purch_request SET canceled= 'Y' WHERE pr_no = " . $pr_obj->pr_no . " ";
	db_query($sql, "The purchase request could not be cancel");

	add_audit_trail($pr_obj->trans_type, $pr_obj->pr_no, Today(), _("Canceled."));
	hook_db_postwrite($pr_obj, ST_PURCHREQUEST);
	commit_transaction();
}
//modified by Albert  06/16/2022
function get_sql_for_pr_search($request_number = '', $category_id = '', $ho_data = '')
{
	$sql = "SELECT
				a.pr_no,
				a.reference,
				CASE 
					WHEN datediff(NOW(), a.pr_date) >=  a.pr_expired
						AND isnull((SELECT t1.requisition_no FROM " . TB_PREF . "purch_orders t1 WHERE t1.requisition_no = a.pr_no LIMIT 1)) = 1 
						AND a.status = 'O' AND a.draft_status = 0
							THEN 'Expired' 
					WHEN a.status = 'O' AND sum(c.quantity_ordered) = 0 AND a.canceled = 'N' AND a.draft_status = 1
						THEN 'Open'
					WHEN sum(c.quantity_ordered) > 0 AND sum(c.quantity_ordered) < sum(c.qty) AND a.status = 'O' AND a.draft_status = 1
						THEN 'Partially Ordered'
					WHEN a.canceled = 'Y'
						THEN 'Canceled'
					WHEN a.draft_status = 2
						THEN 'Disapproved'
					WHEN a.draft_status = 0
						THEN 'Draft'
					ELSE 
						'Closed' 
				END as Status,
				SC.description,
				b.supp_name,
				CASE
					WHEN a.purch_type_id = 1
						THEN 'HO'
					ELSE
						'Local'
				END as purch_type,
				CASE
					WHEN a.served_status = 0
						THEN 'Normal Served'
					WHEN a.served_status = 1
						THEN 'Overserved'
					ELSE
						'Wrong Served'
				END as served_status,
				a.pr_date,
				a.comments
			FROM " . TB_PREF . "purch_request a 
			LEFT JOIN " . TB_PREF . "suppliers b
				ON b.supplier_id = a.supplier_id
			INNER JOIN " . TB_PREF . "purch_request_details c
				ON c.pr_no = a.pr_no 
			
			LEFT JOIN " . TB_PREF . "stock_category SC ON a.item_group_id = SC.category_id
			
			WHERE a.pr_no < 1000";
	if ($request_number != "") {
		$sql .= " AND a.pr_no LIKE " . db_escape('%' . $request_number . '%') . " OR a.reference LIKE " . db_escape('%' . $request_number . '%') . " ";
	}
	//Added by Albert in PR Branch not display dissapproved 06/16/2022
	if ($ho_data != "") {
		$sql .= " AND  a.draft_status <> 2";
	}

	//Added by spyrax10 20 Aug 2022
	if ($category_id != '') {
		$sql .= " AND a.item_group_id = " . db_escape($category_id);
	}

	$sql .= " GROUP BY a.pr_no ORDER BY a.pr_date DESC, a.pr_no DESC"; // modified by spyrax10
	return $sql;
}

//----------------------------------------------------------------------------------------

function read_pr_header($pr_no, &$request)
{
	$sql = "SELECT
			a.*,
			CASE 
				WHEN datediff(NOW(), a.pr_date) >=  a.pr_expired
					AND isnull((SELECT t1.requisition_no FROM " . TB_PREF . "purch_orders t1 WHERE t1.requisition_no = a.pr_no LIMIT 1)) = 1 
					AND a.status = 'O'
						THEN 'Expired' 
				WHEN a.status = 'O' AND sum(d.quantity_ordered) = 0 AND a.canceled = 'N'
					THEN 'Open'
				WHEN sum(d.quantity_ordered) > 0 AND sum(d.quantity_ordered) < sum(d.qty) AND a.status = 'O'
					THEN 'Partially Ordered'
				WHEN a.canceled = 'Y'
					THEN 'Canceled'
				ELSE 
					'Closed' 
			END as pr_status,
			b.supp_name,
			b.supplier_id,
			b.curr_code,
			b.tax_group_id,
			b.tax_included,
			c.id AS purch_type_id,
			c.purch_type
			FROM " . TB_PREF . "purch_request a 
			LEFT JOIN " . TB_PREF . "suppliers b 
				ON b.supplier_id = a.supplier_id
			INNER JOIN " . TB_PREF . "purch_types c
				ON c.id = a.purch_type_id
			INNER JOIN " . TB_PREF . "purch_request_details d
				ON d.pr_no = a.pr_no

			WHERE a.reference = " . db_escape($pr_no) . "
			GROUP BY a.pr_no";

	$result = db_query($sql, "The request cannot be retrieved");

	if (db_num_rows($result) == 1) {

		$myrow = db_fetch($result);

		$request->trans_type = ST_PURCHREQUEST;
		$request->pr_no = $myrow["pr_no"];

		$request->set_supplier(
			$myrow["supplier_id"],
			$myrow["supp_name"],
			$myrow["curr_code"],
			$myrow['tax_group_id'],
			$myrow["tax_included"]
		);

		$request->Comments = $myrow["comments"];
		$request->reference = $myrow["reference"];
		$request->orig_order_date = sql2date($myrow["pr_date"]);
		$request->due_date = sql2date($myrow["required_date"]);
		$request->purch_type_id = $myrow["purch_type_id"];
		$request->purch_type = $myrow["purch_type"];
		$request->category_id = $myrow["item_group_id"];
		$request->served_status = $myrow["served_status"];
		$request->pr_status = $myrow["pr_status"];

		return true;
	}

	display_db_error("FATAL : duplicate purchase request found", "", true);
	return false;
}

//----------------------------------------------------------------------------------------

function read_pr_items($pr_no, &$request, $open_items_only = false)
{
	/*now populate the line po array with the purchase request details records */

	$sql = "SELECT 
			pr_detail_item, 
			pr_no, 
			item_code, 
			description, 
			qty,
			quantity_ordered,
			color_code
			FROM " . TB_PREF . "purch_request_details 
			WHERE pr_no = " . db_escape($pr_no);

	$result = db_query($sql, "The lines on the purchase request cannot be retrieved");

	if (db_num_rows($result) > 0) {
		while ($myrow = db_fetch($result)) {

			if ($request->pr_add_to_order(
				$request->lines_on_order,
				$myrow["item_code"],
				$myrow["qty"],
				$myrow["description"],
				0,
				null,
				null,
				null,
				$myrow["quantity_ordered"],
				$myrow["color_code"]
			)) {
				$request->line_items[$request->lines_on_order - 1]->pr_detail_rec = $myrow["pr_detail_item"];
			}
		} /* line po from purchase request details */
	} //end of checks on returned data set
}

//----------------------------------------------------------------------------------------

function read_pr($request_no, &$request, $open_items_only = false)
{
	$result = read_pr_header($request_no, $request);

	if ($result)
		read_pr_items($request->pr_no, $request, $open_items_only);
}

//----------------------------------------------------------------------------------------

function get_pr_items($pr_no)
{
	$sql = "SELECT item_code, qty, quantity_ordered, color_code
		FROM " . TB_PREF . "purch_request_details
		WHERE pr_no=" . db_escape($pr_no)
		. " ORDER BY pr_detail_item";

	$result = db_query($sql, "could not query purch request details");
	check_db_error("Could not check that the details of the purchase request had not been changed by another user ", $sql);
	return $result;
}
//----------------------------------------------------------------------------------------

function close_pr(&$pr_obj)
{
	begin_transaction();
	hook_db_prewrite($pr_obj, ST_PURCHREQUEST);

	/*Update the purchase request header with any changes */
	$sql = "UPDATE " . TB_PREF . "purch_request SET status='C'";
	$sql .= " WHERE pr_no = " . $pr_obj->pr_no;
	db_query($sql, "The purchase request could not be closed");

	add_audit_trail($pr_obj->trans_type, $pr_obj->pr_no, Today(), _("Purchase Request Closed"));
	hook_db_postwrite($pr_obj, ST_PURCHREQUEST);
	commit_transaction();

	return $pr_obj->pr_no;
}

/* Added by Ronelle 12/20/2020 */
function update_pr_draft_status(&$pr_obj)
{
	begin_transaction();
	hook_db_prewrite($pr_obj, ST_PURCHREQUEST);

	/*Update the purchase order draft status */
	$sql = "UPDATE " . TB_PREF . "purch_request SET draft_status=" . db_escape($pr_obj->draft_status);
	$sql .= " WHERE pr_no = " . $pr_obj->pr_no;
	db_query($sql, "The purchase request could not be updated");

	add_audit_trail($pr_obj->trans_type, $pr_obj->pr_no, Today(), _("Update Draft Status."));
	hook_db_postwrite($pr_obj, ST_PURCHREQUEST);
	commit_transaction();

	return $pr_obj->reference;
}
/* */

/* Added by Ronelle 12/20/2020 */
function check_status_pr($pr_no)
{
	$sql = "SELECT 
			CASE 
				WHEN datediff(NOW(), a.pr_date) >=  a.pr_expired
				AND isnull((SELECT t1.requisition_no FROM " . TB_PREF . "purch_orders t1 WHERE t1.requisition_no = a.pr_no LIMIT 1)) = 1 
				AND a.status = 'O' AND a.draft_status = 0
					THEN 'Expired' 
				WHEN a.status = 'O' AND sum(b.quantity_ordered) = 0 AND a.canceled = 'N' AND a.draft_status = 1
					THEN 'Open'
				WHEN sum(b.quantity_ordered) > 0 AND sum(b.quantity_ordered) < sum(b.qty) AND a.status = 'O' AND a.draft_status = 1
					THEN 'Partially Ordered'
				WHEN a.canceled = 'Y'
					THEN 'Canceled'
				WHEN a.draft_status = 2
					THEN 'Disapproved'
				WHEN a.draft_status = 0
					THEN 'Draft'
				ELSE 
					'Closed' 
			END as Status
			FROM " . TB_PREF . "purch_request a INNER JOIN
			" . TB_PREF . "purch_request_details b 
				ON b.pr_no = a.pr_no
			WHERE a.reference = " . db_escape($pr_no) . "";

	$result = db_query($sql, "The status for purchase request " . $pr_no . " could not be retrieved");
	if (db_num_rows($result) == 1) {
		$row = db_fetch_row($result);
		return $row[0];
	}
}
/* */

//Added by spyrax10

function get_pr_total_qty($reference) {

	set_global_connection();

	$sql = "SELECT SUM(b.qty) as total_qty
			FROM " . TB_PREF . "purch_request a 
				INNER JOIN " . TB_PREF . "purch_request_details b ON b.pr_no = a.pr_no
			WHERE a.reference = " . db_escape($reference);

	$result = db_query($sql, "The status for purchase request " . $reference . " could not be retrieved");
	if (db_num_rows($result) == 1) {
		$row = db_fetch_row($result);
		return $row[0];
	}
	else {
		return 0;
	}
}
//Added by Albert

function get_po_total_qty_recieved($reference) {

	set_global_connection();

	$sql = "SELECT SUM(b.quantity_received) as total_qty_received
			FROM " . TB_PREF . "purch_orders a 
				INNER JOIN " . TB_PREF . "purch_order_details b ON b.order_no = a.order_no
			WHERE a.reference = " . db_escape($reference);

	$result = db_query($sql, "The status for purchase order " . $reference . " could not be retrieved");

		$row = db_fetch_row($result);
		return $row[0];
}
/*Added by Albert 05/04/2022*/
function Get_Policy_SRP_Effectivity_Date($branchCode, $categoryID, $stockID, $supplierID)
{
    global $def_coy;
    set_global_connection($def_coy);

	$sql = "SELECT A.date_epic
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


function Get_Previous_Policy_SRP_last_date_updated($branchCode, $categoryID, $stockID, $supplierID)
{
    global $def_coy;
    set_global_connection($def_coy);

	$sql = "SELECT max(D.date_defined)
			FROM ".TB_PREF."item_srp A 
				INNER JOIN ".TB_PREF."branch_policy B ON B.plcysrp_id = A.srptype_id
    			INNER JOIN ".TB_PREF."stock_master C ON C.stock_id = A.stock_id AND C.category_id = B.category_id
				INNER JOIN ".TB_PREF."price_cost_archive D ON D.plcysrp_id =  A.srptype_id and D.stock_id = A.stock_id
			WHERE B.module_type = 'SRPPLCY'
				AND D.inactive = 1
				AND B.branch_code = ".db_escape($branchCode)."
				AND C.category_id = ".db_escape($categoryID)."
    			AND A.stock_id = ".db_escape($stockID)."
				AND A.supplier_id = ".db_escape($supplierID);

	$result = db_query($sql, "The standard cost for the selected branch and item could not be retrieved");
	$row = db_fetch_row($result);
	$date_defined = $row[0];

	set_global_connection();
	return $date_defined;
}

function get_pr_data($db_id = "") {

	global $db, $db_connections;

	$sql = "SELECT a.* FROM ".TB_PREF." purch_request a where a.status <> 'C' limit 1";

	$result = db_query($sql, "The Purchased Request could not be retrieved");

	$row = db_fetch_row($result);
	return $row[0];
}
/*END by Albert*/
