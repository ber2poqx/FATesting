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
//----------------------------------------------------------------------------------------
function add_sales_order(&$order)
{
	global $SysPrefs, $path_to_root, $Refs;
	set_global_connection();
	begin_transaction();
	hook_db_prewrite($order, $order->trans_type);
	$order_no = get_next_trans_no($order->trans_type);
	$del_date = date2sql($order->due_date);
	$order_type = 0; // this is default on new order
	$total = $order->get_trans_total();
	$first_due_date = "0000-00-00";
	$maturity_date = "0000-00-00";

	if ($order->payment_policy != 0) {
		$first_due_date = date2sql($order->first_due_date);
		$maturity_date = date2sql($order->maturity_date);
	}

	$so_status = "Draft";
	if ($order->payment == 0) {
		$so_status = "Approved";
	}

	//Modified by spyrax10 5 Feb 2022
	$payterm_name = $comaker_name = '';

	if ($order->co_maker != '') {
		$comaker_name = $order->co_maker;
	}

	if ($order->payment != '') {
		$plcy_row = db_fetch(get_instlpolicy_by_id($order->payment));
		$payterm_name = $plcy_row['plcydtl_code'];
	}
	//

	$temp_sales_tax_type = 2;
	$sql = "INSERT INTO " . TB_PREF . "sales_orders (order_no, type, debtor_no, trans_type, branch_code, customer_ref, reference, comments, ord_date,
				order_type, ship_via, deliver_to, delivery_address, contact_phone, freight_cost, from_stk_loc, delivery_date, payment_terms, total, 
				prep_amount, doc_ref_no, salesman_id, category_id, dp_amount, lcp_amount, amortization, waranty_code, fsc_series, ar_amount, first_due_date, maturity_date,
				months_term, rebate, financing_rate, status, co_maker, discount_downpayment, so_type,discount_downpayment2, account_specialist_remarks, so_item_type, previous_owner, 
				payment_terms_name, pdc_no, pdc_discount)

		VALUES (" . db_escape($order_no) . "," .
		db_escape($order_type) . "," .
		db_escape($order->customer_id) . ", " .
		db_escape($order->trans_type) . "," .
		db_escape($order->Branch) . ", " .
		db_escape($order->cust_ref) . "," .
		db_escape($order->reference) . "," .
		db_escape($order->Comments) . ",'" .
		date2sql($order->document_date) . "', " .
		db_escape($temp_sales_tax_type) . ", " .
		db_escape($order->ship_via) . "," .
		db_escape($order->deliver_to) . "," .
		db_escape($order->delivery_address) . ", " .
		db_escape($order->phone) . ", " .
		db_escape($order->freight_cost) . ", " .
		db_escape($order->Location) . ", " .
		db_escape($del_date) . "," .
		db_escape($order->payment) . "," .
		db_escape($order->ar_amount) . "," .
		db_escape($order->prep_amount) . "," .
		db_escape($order->document_ref) . "," .
		db_escape($order->salesman_id) . "," .
		db_escape($order->category_id) . "," .
		db_escape($order->dp_amount) . ",
			$order->lcp_amount,
			" . db_escape($order->amortization) . "," .
		db_escape($order->warranty_code) . "," .
		db_escape($order->fsc_series) . "," .
		db_escape($order->ar_amount) . ",
			'$first_due_date',
			'$maturity_date',
			$order->months_term,
			$order->rebate,
			$order->financing_rate,
			" . db_escape($so_status) . ",
			'$comaker_name',
			$order->discount_dp_amount,
			" .  db_escape($order->stype_id). ",
			".db_escape($order->discount_dp_amount2) . ",
			".db_escape($order->account_specialist_remarks) . ",
		    ".db_escape($order->so_item_type) . ",
			".db_escape($order->previous_owner) . //modified by Albert 11/03/2021
			", '$payterm_name', 
			".db_escape($order->pdc_no) .",
			".db_escape($order->pdc_discount != '' ? $order->pdc_discount : 0) ."
		)"; 

	db_query($sql, "order Cannot be Added");

	$order->trans_no = array($order_no => 0);

	if ($SysPrefs->loc_notification() == 1) {
		include_once($path_to_root . "/inventory/includes/inventory_db.inc");
		$st_ids = array();
		$st_names = array();
		$st_num = array();
		$st_reorder = array();
	}
	foreach ($order->line_items as $line) {
		if ($SysPrefs->loc_notification() == 1 && is_inventory_item($line->stock_id))
			$loc = calculate_reorder_level($order->Location, $line, $st_ids, $st_names, $st_num, $st_reorder);

		$sql = "INSERT INTO " . TB_PREF . "sales_order_details (order_no, trans_type, stk_code, description, unit_price, quantity, discount_percent, lot_no, chassis_no, color_code, item_type, discount1, discount2
		) VALUES (";
		$sql .= $order_no . "," . $order->trans_type .
			"," . db_escape($line->stock_id) . ", "
			. db_escape($line->item_description) . ", $line->price,
				$line->quantity,
				$line->discount_percent,
				" . db_escape($line->lot_no) . ",
				" . db_escape($line->chasis_no) . ",
				" . db_escape($line->color_desc) . ",
				" . db_escape($line->item_type) . ",
				" . $line->discount1 . ",
				" . $line->discount2 . ")";

		db_query($sql, "order Details Cannot be Added");

		// Now mark quotation line as processed
		if ($order->trans_type == ST_SALESORDER && $line->src_id)
			update_parent_line(ST_SALESORDER, $line->src_id, $line->qty_dispatched); // clear all the quote despite all or the part was ordered
	} /* inserted line items into sales order details */

	add_audit_trail($order->trans_type, $order_no, $order->document_date);
	$Refs->save($order->trans_type, $order_no, $order->reference, null, $order->fixed_asset);

	hook_db_postwrite($order, $order->trans_type);
	commit_transaction();

	if ($SysPrefs->loc_notification() == 1 && count($st_ids) > 0)
		send_reorder_email($loc, $st_ids, $st_names, $st_num, $st_reorder);
	return $order_no;
}

//----------------------------------------------------------------------------------------

function delete_sales_order($order_no, $trans_type)
{
	begin_transaction();
	hook_db_prevoid($trans_type, $order_no);

	$sql = "DELETE FROM " . TB_PREF . "sales_orders WHERE order_no=" . db_escape($order_no)
		. " AND trans_type=" . db_escape($trans_type);

	db_query($sql, "order Header Delete");

	$sql = "DELETE FROM " . TB_PREF . "sales_order_details WHERE order_no ="
		. db_escape($order_no) . " AND trans_type=" . db_escape($trans_type);
	db_query($sql, "order Detail Delete");

	add_audit_trail($trans_type, $order_no, Today(), _("Deleted."));
	commit_transaction();
}

//----------------------------------------------------------------------------------------
// Mark changes in sales_order_details
//
function update_sales_order_version($order)
{
	foreach ($order as $so_num => $so_ver) {
		$sql = 'UPDATE ' . TB_PREF . 'sales_orders SET version=version+1 WHERE order_no=' . db_escape($so_num) .
			' AND version=' . $so_ver . " AND trans_type=" . ST_SALESORDER;
		db_query($sql, 'Concurrent editing conflict while sales order update');
	}
}

//----------------------------------------------------------------------------------------
//Added by spyrax10
function get_so_status($order_no)
{
	$sql = "SELECT A.status 
		FROM " . TB_PREF . "sales_orders A 
		WHERE A.order_no =" . db_escape($order_no);

	$res = db_query($sql, 'Invalid Sales Order Number!');
	$row = db_fetch_row($res);
	return $row[0];
}

function update_sales_order($order)
{
	global $SysPrefs, $path_to_root, $Refs;

	$del_date = date2sql($order->due_date);
	$ord_date = date2sql($order->document_date);
	$order_no =  key($order->trans_no);
	$version = current($order->trans_no);
	$total = $order->get_trans_total();
	$status = (string)get_so_status($order_no); //Added by spyrax10

	$first_due_date = "0000-00-00";
	$maturity_date = "0000-00-00";
	$comaker_name = get_comaker($order->customer_id,$order->co_maker);//Added by albert 11/15/2021

	if ($order->payment_policy != 0) {
		$first_due_date = date2sql($order->first_due_date);
		$maturity_date = date2sql($order->maturity_date);
	}

	set_global_connection();
	begin_transaction();
	hook_db_prewrite($order, $order->trans_type);

	if ($order->trans_type == ST_SALESORDER)
		$allocs = get_payments_for($order_no, $order->trans_type, $order->customer_id);

	$sql = "UPDATE " . TB_PREF . "sales_orders SET type =" . db_escape($order->so_type) . " ,
		debtor_no = " . db_escape($order->customer_id) . ",
		branch_code = " . db_escape($order->Branch) . ",
		customer_ref = " . db_escape($order->cust_ref) . ",
		reference = " . db_escape($order->reference) . ",
		comments = " . db_escape($order->Comments) . ",
		ord_date = " . db_escape($ord_date) . ",
		ship_via = " . db_escape($order->ship_via) . ",
		deliver_to = " . db_escape($order->deliver_to) . ",
		delivery_address = " . db_escape($order->delivery_address) . ",
		contact_phone = " . db_escape($order->phone) . ",
		freight_cost = " . db_escape($order->freight_cost) . ",
		from_stk_loc = " . db_escape($order->Location) . ",
		delivery_date = " . db_escape($del_date) . ",
		version = " . ($version + 1) . ",
		payment_terms = " . db_escape($order->payment) . ",
		total = " . db_escape($order->ar_amount) . ",
		prep_amount = " . db_escape($order->prep_amount) . ",
		months_term = $order->months_term,
		rebate = $order->rebate,
		financing_rate = $order->financing_rate,
		category_id = $order->category_id,
		dp_amount = $order->dp_amount,
		lcp_amount = $order->lcp_amount,
		amortization = $order->amortization,
		ar_amount = $order->ar_amount,
		first_due_date = '$first_due_date',
		maturity_date = '$maturity_date',
		co_maker = " . db_escape($order->co_maker) . ",
		discount_downpayment = $order->discount_dp_amount,
		so_type = " . db_escape($order->sales_type) . ",
		pdc_no = " . db_escape($order->pdc_no) . "

	 WHERE order_no=" . db_escape($order_no) . "
	 AND trans_type=" . $order->trans_type . " AND version=" . $version;
	db_query($sql, "order Cannot be Updated, this can be concurrent edition conflict");

	$id_tokeep = array();
	foreach ($order->line_items as $line) {
		array_push($id_tokeep, $line->id);
	}
	$id_list = implode(', ', $id_tokeep);

	$sql = "DELETE FROM " . TB_PREF . "sales_order_details WHERE order_no =" . db_escape($order_no) . " AND trans_type=" . $order->trans_type;
	$sql .= " AND id NOT IN ($id_list)";

	db_query($sql, "Old order Cannot be Deleted");

	if ($SysPrefs->loc_notification() == 1) {
		include_once($path_to_root . "/inventory/includes/inventory_db.inc");
		$st_ids = array();
		$st_names = array();
		$st_num = array();
		$st_reorder = array();
	}
	foreach ($order->line_items as $line) {
		if ($SysPrefs->loc_notification() == 1 && is_inventory_item($line->stock_id))
			$loc = calculate_reorder_level($order->Location, $line, $st_ids, $st_names, $st_num, $st_reorder);

		if (!$line->id) //new line 
		{
			$sql = "INSERT INTO " . TB_PREF . "sales_order_details (order_no, trans_type, stk_code, description, unit_price, quantity, discount_percent, lot_no, chassis_no, color_code, item_type, discount1, discount2) VALUES (";
			$sql .= $order_no . "," . $order->trans_type .
				"," . db_escape($line->stock_id) . ", "
				. db_escape($line->item_description) . ", $line->price,
					$line->quantity,
					$line->discount_percent,
					" . db_escape($line->lot_no) . ",
					" . db_escape($line->chasis_no) . ",
					" . db_escape($line->color_desc) . ",
					" . db_escape($line->item_type) . ",
					" . $line->discount1 . ",
					" . $line->discount2 . ")";
		} else {
			$sql = "UPDATE " . TB_PREF . "sales_order_details
			SET id=" . db_escape($line->id) . ",
				order_no=$order_no,
				trans_type=" . $order->trans_type . ",
				stk_code=" . db_escape($line->stock_id) . ",
				description=" . db_escape($line->item_description) . ",
				unit_price=" . db_escape($line->price) . ",
				quantity=" . db_escape($line->quantity) . ",
		    	discount_percent=" . db_escape($line->discount_percent) . ",
			    qty_sent=" . db_escape($line->qty_done) . ",
				lot_no=" . db_escape($line->lot_no) . ",
				chassis_no=" . db_escape($line->chasis_no) . ",
				color_code=" . db_escape($line->color_desc) . ",
				item_type=" . db_escape($line->item_type) . ",
				discount1=$line->discount1,
				discount2=$line->discount2
			 WHERE id = " . db_escape($line->id);
		}
		db_query($sql, "Old order Cannot be updated");
	} /* inserted line items into sales order details */

	if ($order->trans_type == ST_SALESORDER)
		reallocate_payments($order_no, ST_SALESORDER, $ord_date, $total, $allocs, $order->customer_id);
	add_audit_trail($order->trans_type, $order_no, $order->document_date, _("Updated."));
	$Refs->save($order->trans_type, $order_no, $order->reference, null, $order->fixed_asset);

	hook_db_postwrite($order, $order->trans_type);
	commit_transaction();
	if ($SysPrefs->loc_notification() == 1 && count($st_ids) > 0)
		send_reorder_email($loc, $st_ids, $st_names, $st_num, $st_reorder);
}

//----------------------------------------------------------------------------------------

function get_sales_order_header($order_no, $trans_type)
{

	//modified by albert 05/10/2022
	set_global_connection();
	
	$sql = "SELECT sorder.*,
	  cust.name,
	  cust.curr_code,
	  cust.address,
	  loc.location_name,
	  cust.discount,
	  stype.sales_type,
	  stype.id AS sales_type_id,
	  stype.tax_included,
	  stype.factor,
 	  ship.shipper_name,
	  tax_group.name AS tax_group_name,
	  tax_group.id AS tax_group_id,
	  cust.tax_id,
	  sorder.alloc,
	  IFNULL(allocs.ord_allocs, 0)+IFNULL(inv.inv_allocs ,0) AS sum_paid,
	  sorder.prep_amount>0 as prepaid,
	  sales_type.name as sales_type_name,
	  sales_type.id as sales_type_id,
	  sales_man.salesman_name as salesman_name
	FROM " . TB_PREF . "sales_orders sorder
			LEFT JOIN (SELECT trans_no_to, sum(amt) ord_allocs FROM " . TB_PREF . "cust_allocations
				WHERE trans_type_to=" . ST_SALESORDER . " AND trans_no_to=" . db_escape($order_no) . " GROUP BY trans_no_to)
				 allocs ON sorder.trans_type=" . ST_SALESORDER . " AND allocs.trans_no_to=sorder.order_no
			LEFT JOIN (SELECT order_, sum(alloc) inv_allocs FROM " . TB_PREF . "debtor_trans 
				WHERE type=" . ST_SALESINVOICE . " AND order_=" . db_escape($order_no) . "  GROUP BY order_)
				 inv ON sorder.trans_type=" . ST_SALESORDER . " AND inv.order_=sorder.order_no
			LEFT JOIN " . TB_PREF . "shippers ship ON  ship.shipper_id = sorder.ship_via,"
		. TB_PREF . "debtors_master cust,"
		. TB_PREF . "sales_types stype, "
		. TB_PREF . "tax_groups tax_group, "
		. TB_PREF . "cust_branch branch,"
		. TB_PREF . "locations loc,"
		. TB_PREF . "sales_type sales_type,"
		. TB_PREF . "salesman sales_man
	WHERE sorder.order_type=stype.id
		AND sorder.so_type=sales_type.id
		AND branch.branch_code = sorder.branch_code
		AND branch.tax_group_id = tax_group.id
		AND sorder.debtor_no = cust.debtor_no
		AND loc.loc_code = sorder.from_stk_loc
		AND sorder.salesman_id = sales_man.salesman_code
		AND sorder.trans_type = " . db_escape($trans_type) . "
		AND sorder.order_no = " . db_escape($order_no);

	$result = db_query($sql, "order Retreival");

	$num = db_num_rows($result);
	if ($num > 1) {
		display_warning("You have duplicate document in database: (type:$trans_type, number:$order_no).");
	} else if ($num == 1) {
		return db_fetch($result);
	} else
		display_warning("You have missing or invalid sales document in database (type:$trans_type, number:$order_no).");
}

//----------------------------------------------------------------------------------------

function get_sales_order_details($order_no, $trans_type)
{
	$sql = "SELECT id, stk_code, unit_price,
				line.description,
				line.quantity,
				discount_percent,
				qty_sent as qty_done,
				item.units,
				item.mb_flag,
				item.material_cost,
				lot_no,
				chassis_no,
				color_code,
				item_type,
				discount1,
				discount2
			FROM " . TB_PREF . "sales_order_details line,"
		. TB_PREF . "stock_master item
			WHERE line.stk_code = item.stock_id
				AND order_no =" . db_escape($order_no)
		. " AND trans_type = " . db_escape($trans_type) . " ORDER BY id";

	return db_query($sql, "Retreive order Line Items");
}
//----------------------------------------------------------------------------------------

function read_sales_order($order_no, &$order, $trans_type, $copy_so = false)
{
	//modified by albert 05/10/2022
	set_global_connection();

	$myrow = get_sales_order_header($order_no, $trans_type);

	$order->trans_type = $myrow['trans_type'];
	$order->so_type =  $myrow["type"];
	$order->trans_no = array($order_no => $myrow["version"]);
	$order->order_no = $myrow["order_no"];
	$order->set_customer(
		$myrow["debtor_no"],
		$myrow["name"],
		$myrow["curr_code"],
		$myrow["discount"],
		$myrow["payment_terms"]
	);

	$order->set_branch(
		$myrow["branch_code"],
		$myrow["tax_group_id"],
		$myrow["tax_group_name"],
		$myrow["contact_phone"]
	);

	$order->set_sales_type(
		$myrow["sales_type_id"],
		$myrow["sales_type"],
		$myrow["tax_included"],
		$myrow["factor"]
	); // no default price calculations on edit

	$order->set_location($myrow["from_stk_loc"], $myrow["location_name"]);

	$order->set_delivery(
		$myrow["ship_via"],
		$myrow["deliver_to"],
		$myrow["delivery_address"],
		$myrow["freight_cost"]
	);

	$order->set_category($myrow["category_id"], get_category_name($myrow["category_id"]));

	$order->cust_ref = $myrow["customer_ref"];
	$order->sales_type = $myrow["order_type"];
	$order->reference = $myrow["reference"];
	$order->Comments = $myrow["comments"];
	$order->due_date = sql2date($myrow["delivery_date"]);
	$order->document_date = sql2date($myrow["ord_date"]);

	$order->prepaid = $myrow["prepaid"];
	$order->alloc = $myrow['alloc'];
	$order->sum_paid = $myrow["sum_paid"]; // sum of all prepayments to so (also invoiced)
	$order->prep_amount = $myrow["prep_amount"];
	$order->prepayments = get_payments_for($order_no, $myrow['trans_type'], $myrow['debtor_no']);

	$order->document_ref = $myrow["doc_ref_no"];
	$order->warranty_code = $myrow["waranty_code"];
	$order->fsc_series = $myrow["fsc_series"];
	$order->salesman_id = $myrow["salesman_id"];
	$order->category_id = $myrow["category_id"];

	$order->lcp_amount = $myrow["lcp_amount"];
	$order->dp_amount = $myrow["dp_amount"]; 
	$order->discount_dp_amount2 = $myrow["discount_downpayment2"]; //Added by Albert
	$order->amortization = $myrow["amortization"];
	$order->ar_amount = $myrow["ar_amount"];
	$order->first_due_date = date('m/d/Y', strtotime($myrow["first_due_date"]));
	$order->maturity_date = date('m/d/Y', strtotime($myrow["maturity_date"]));

	$order->payment_policy = $myrow["payment_terms"];
	$order->months_term = $myrow["months_term"];
	$order->rebate = $myrow["rebate"];
	$order->financing_rate = $myrow["financing_rate"];

	$order->co_maker = $myrow["co_maker"]; 
	$order->discount_dp_amount = $myrow["discount_downpayment"];
	$order->stype_id = $myrow["sales_type_id"];
	$order->stype_name = $myrow["sales_type_name"];
	$order->previous_owner = $myrow["previous_owner"];

	$result = get_sales_order_details($order_no, $order->trans_type);
	$lcp_amount = 0;
	if (db_num_rows($result) > 0) {
		$line_no = 0;
		while ($myrow = db_fetch($result)) {
			if ($myrow["item_type"] == "Regular" && $copy_so) {
				$order->add_to_cart(
					$line_no,
					$myrow["stk_code"],
					$myrow["quantity"],
					$myrow["unit_price"],
					$myrow["discount_percent"],
					$myrow["qty_done"],
					$myrow["material_cost"],
					$myrow["description"],
					$myrow["id"],
					0,
					0,
					$myrow["lot_no"],
					$myrow["chassis_no"],
					$myrow["color_code"],
					$myrow["item_type"],
					$myrow["discount1"],
					$myrow["discount2"],
					0,
					Get_Item_Incentive_Price($order->category_id, $myrow["stk_code"], "SMI"),
					Get_Item_Incentive_Price($order->category_id, $myrow["stk_code"], "BII")
				);
				$lcp_amount += $myrow["unit_price"];
				$line_no++;
			}

			if (!$copy_so) {
				$order->add_to_cart(
					$line_no,
					$myrow["stk_code"],
					$myrow["quantity"],
					$myrow["unit_price"],
					$myrow["discount_percent"],
					$myrow["qty_done"],
					$myrow["material_cost"],
					$myrow["description"],
					$myrow["id"],
					0,
					0,
					$myrow["lot_no"],
					$myrow["chassis_no"],
					$myrow["color_code"],
					$myrow["item_type"],
					$myrow["discount1"],
					$myrow["discount2"],
					0
				);
				$lcp_amount += $myrow["unit_price"];
				$line_no++;
			}
		}
	}
	$order->sum_lcp_amount = $lcp_amount;

	return true;
}

//----------------------------------------------------------------------------------------

function sales_order_has_deliveries($order_no)
{
	$sql = "SELECT SUM(qty_sent) FROM " . TB_PREF .
		"sales_order_details WHERE order_no=" . db_escape($order_no)
		. " AND trans_type=" . ST_SALESORDER;

	$result = db_query($sql, "could not query for sales order usage");

	$row = db_fetch_row($result);

	if ($row[0] > 0)
		return true;  // 2010-04-21 added check for eventually voided deliveries, Joe Hunt
	$sql = "SELECT order_ FROM " . TB_PREF . "debtor_trans WHERE type=" . ST_CUSTDELIVERY . " AND order_=" . db_escape($order_no);
	$result = db_query($sql, "The related delivery notes could not be retreived");
	return (db_num_rows($result) > 0);
}

//----------------------------------------------------------------------------------------

function close_sales_order($order_no)
{
	// set the quantity of each item to the already sent quantity. this will mark item as closed.
	$sql = "UPDATE " . TB_PREF . "sales_order_details
		SET quantity = qty_sent WHERE order_no = " . db_escape($order_no)
		. " AND trans_type=" . ST_SALESORDER;

	db_query($sql, "The sales order detail record could not be updated");
}

//---------------------------------------------------------------------------------------------------------------

function get_invoice_duedate($terms, $invdate)
{
	if (!is_date($invdate)) {
		return new_doc_date();
	}

	$myrow = get_payment_terms($terms);

	if (!$myrow)
		return $invdate;

	if ($myrow['day_in_following_month'] > 0)
		$duedate = add_days(end_month($invdate), $myrow['day_in_following_month']);
	elseif ($myrow['days_before_due'] > 0)
		$duedate = add_days($invdate, $myrow['days_before_due']);
	else
		$duedate = $invdate;
	return $duedate;
}

function get_customer_to_order($customer_id)
{
	set_global_connection();

	// Now check to ensure this account is not on hold */
	$sql = "SELECT cust.name, 
		  cust.address,
		  credit_status.dissallow_invoices, 
		  cust.sales_type AS salestype,
		  cust.dimension_id,
		  cust.dimension2_id,
		  stype.sales_type,
		  stype.tax_included,
		  stype.factor,
		  cust.curr_code,
		  cust.discount,
		  cust.payment_terms,
		  cust.pymt_discount,
		  cust.credit_limit - Sum(IFNULL(IF(trans.type IN(" . implode(',', array(ST_CUSTCREDIT, ST_CUSTPAYMENT, ST_BANKDEPOSIT)) . "),
			-1, 1) * (ov_amount + ov_gst + ov_freight + ov_freight_tax + ov_discount),0)) as cur_credit
		FROM " . TB_PREF . "debtors_master cust
		  LEFT JOIN " . TB_PREF . "debtor_trans trans ON trans.type!=" . ST_CUSTDELIVERY . " AND trans.debtor_no = cust.debtor_no,"
		. TB_PREF . "credit_status credit_status,"
		. TB_PREF . "sales_types stype
		WHERE cust.sales_type=stype.id
			AND cust.credit_status=credit_status.id
			AND cust.debtor_no=" . db_escape($customer_id)
		. " GROUP by cust.debtor_no";

	$result = db_query($sql, "Customer Record Retreive");
	return 	db_fetch($result);
}

function get_branch_to_order($customer_id, $branch_id)
{

	// the branch was also selected from the customer selection so default the delivery details from the customer branches table cust_branch. The order process will ask for branch details later anyway
	$sql = "SELECT branch.br_name,
					branch.br_address,
					branch.br_post_address,
					default_location, location_name, default_ship_via,
					tax_group.name AS tax_group_name,
					tax_group.id AS tax_group_id
				FROM " . TB_PREF . "cust_branch branch,"
		. TB_PREF . "tax_groups tax_group,"
		. TB_PREF . "locations location
			WHERE branch.tax_group_id = tax_group.id
				AND location.loc_code=default_location
				AND branch.branch_code=" . db_escape($branch_id) . "
				AND branch.debtor_no=" . db_escape($customer_id);

	return db_query($sql, "Customer Branch Record Retreive");
}

/*
	Supported filters:

	InvoiceTemplates
	DeliveryTemplates
	OutstandingOnly
	PrepaidOrders
*/
function get_sql_for_sales_orders_view(
	$trans_type,
	$trans_no,
	$filter,
	$stock_item = '',
	$from = '',
	$to = '',
	$ref = '',
	$location = ALL_TEXT,
	$customer_id = ALL_TEXT,
	//Added by spyrax10 23 Aug 2022
	$category_id = '',
	$status = '' 
) {

	$sql = "SELECT 
			sorder.order_no,
			sorder.reference,
			sorder.status,
			sorder.so_item_type as invoice_type,
			debtor.name,
			CASE
                WHEN sorder.payment_terms = 0
                    THEN 'CASH'
                ELSE
                    'INSTALLMENT'
            END as payment_type,
			(SELECT sq1.description FROM ".TB_PREF."stock_category sq1 WHERE sq1.category_id = sorder.category_id) as category,
			sorder.approval_remarks,"
		. ($filter == 'InvoiceTemplates'
			|| $filter == 'DeliveryTemplates' ?
			"sorder.comments, " : "sorder.customer_ref, ")
		. "sorder.ord_date,
			sorder.delivery_date,
			sorder.deliver_to,
			sorder.ar_amount AS OrderValue,
			sorder.type,
			debtor.curr_code,
			Sum(line.qty_sent) AS TotDelivered,
			Sum(line.quantity) AS TotQuantity,
			Sum(line.invoiced) AS TotInvoiced,
			alloc,
			prep_amount,
			allocs.ord_payments,
			inv.inv_payments,
			sorder.total,
			sorder.trans_type
		FROM " . TB_PREF . "sales_orders as sorder
		LEFT JOIN (SELECT trans_no_to, sum(amt) ord_payments FROM " . TB_PREF . "cust_allocations WHERE trans_type_to=" . ST_SALESORDER . " GROUP BY trans_no_to)
			 allocs ON sorder.trans_type=" . ST_SALESORDER . " AND allocs.trans_no_to=sorder.order_no
		LEFT JOIN (SELECT order_, sum(prep_amount) inv_payments	FROM " . TB_PREF . "debtor_trans WHERE type=" . ST_SALESINVOICE . " GROUP BY order_)
				 inv ON sorder.trans_type=" . ST_SALESORDER . " AND inv.order_=sorder.order_no,"
		. TB_PREF . "sales_order_details as line , "
		. TB_PREF . "debtors_master as debtor, "
		. TB_PREF . "cust_branch as branch
			WHERE sorder.order_no = line.order_no
			AND sorder.trans_type = line.trans_type
			AND sorder.trans_type = " . db_escape($trans_type) . "
			AND sorder.debtor_no = debtor.debtor_no
			AND sorder.branch_code = branch.branch_code
			AND debtor.debtor_no = branch.debtor_no
			AND sorder.so_item_type = 'new'";

	if (isset($trans_no) && $trans_no != "") {
		// search orders with number like 
		$number_like = "%" . $trans_no;
		$sql .= " AND sorder.order_no LIKE " . db_escape($number_like);
		//				." GROUP BY sorder.order_no";
	} elseif ($ref != "") {
		// search orders with reference like 
		$sql .= " AND sorder.reference LIKE " . db_escape('%' . $ref . '%');
		//				." GROUP BY sorder.order_no";
	} else	// ... or select inquiry constraints
	{
		if ($filter != 'DeliveryTemplates' && $filter != 'InvoiceTemplates' && $filter != 'OutstandingOnly') {
			$date_after = date2sql($from);
			$date_before = date2sql($to);

			$sql .=  " AND sorder.ord_date >= '$date_after'"
				. " AND sorder.ord_date <= '$date_before'";
		}
	}
	if ($trans_type == ST_SALESQUOTE && !check_value('show_all'))
		$sql .= " AND sorder.delivery_date >= '" . date2sql(Today()) . "' AND line.qty_sent=0"; // show only outstanding, not realized quotes

	//if ($selected_customer != -1)
	//	$sql .= " AND sorder.debtor_no=".db_escape($selected_customer);

	if ($stock_item != ALL_TEXT)
		$sql .= " AND line.stk_code=" . db_escape($stock_item);

	if ($location != ALL_TEXT)
		$sql .= " AND sorder.from_stk_loc = " . db_escape($location);

	if ($filter == 'OutstandingOnly')
		$sql .= " AND line.qty_sent < line.quantity";

	if ($filter == 'PrepaidOrders')
		$sql .= " AND prep_amount>0";

	elseif ($filter == 'InvoiceTemplates' || $filter == 'DeliveryTemplates')
		$sql .= " AND sorder.type=1";

	//Chaiatanya : New Filter
	if ($customer_id != ALL_TEXT) {
		$sql .= " AND sorder.debtor_no = " . db_escape($customer_id);
	}

	//Added by spyrax10 23 Aug 2022
	if ($category_id != '') {
		$sql .= " AND sorder.category_id = " .db_escape($category_id);
	}

	if ($status != '') {
		$sql .= " AND sorder.status = " .db_escape($status);
	}
	//

	$sql .= " GROUP BY sorder.order_no,
					sorder.debtor_no,
					sorder.branch_code,
					sorder.customer_ref,
					sorder.ord_date,
					sorder.deliver_to
				ORDER BY sorder.order_no DESC";
	return $sql;
}
//Added by Albert 10/13/2021
function get_sql_for_sales_orders_repo_view(
	$trans_type,
	$trans_no,
	$filter,
	$stock_item = '',
	$from = '',
	$to = '',
	$ref = '',
	$location = ALL_TEXT,
	$customer_id = ALL_TEXT
) {

	$sql = "SELECT 
			sorder.order_no,
			sorder.reference,
			sorder.status,
			sorder.so_item_type as invoice_type,
			debtor.name,
			CASE
                WHEN sorder.payment_terms = 0
                    THEN 'CASH'
                ELSE
                    'INSTALLMENT'
            END as payment_type,
			(SELECT sq1.description FROM ".TB_PREF."stock_category sq1 WHERE sq1.category_id = sorder.category_id) as category,
			sorder.approval_remarks,"
		. ($filter == 'InvoiceTemplates'
			|| $filter == 'DeliveryTemplates' ?
			"sorder.comments, " : "sorder.customer_ref, ")
		. "sorder.ord_date,
			sorder.delivery_date,
			sorder.deliver_to,
			sorder.ar_amount AS OrderValue,
			sorder.type,
			debtor.curr_code,
			Sum(line.qty_sent) AS TotDelivered,
			Sum(line.quantity) AS TotQuantity,
			Sum(line.invoiced) AS TotInvoiced,
			alloc,
			prep_amount,
			allocs.ord_payments,
			inv.inv_payments,
			sorder.total,
			sorder.trans_type
		FROM " . TB_PREF . "sales_orders as sorder
		LEFT JOIN (SELECT trans_no_to, sum(amt) ord_payments FROM " . TB_PREF . "cust_allocations WHERE trans_type_to=" . ST_SALESORDER . " GROUP BY trans_no_to)
			 allocs ON sorder.trans_type=" . ST_SALESORDER . " AND allocs.trans_no_to=sorder.order_no
		LEFT JOIN (SELECT order_, sum(prep_amount) inv_payments, trans_no	FROM " . TB_PREF . "debtor_trans WHERE type=" . ST_SALESINVOICE . " GROUP BY order_)
				 inv ON sorder.trans_type=" . ST_SALESORDER . " AND inv.order_=sorder.order_no,"
		. TB_PREF . "sales_order_details as line , "
		. TB_PREF . "debtors_master as debtor, "
		. TB_PREF . "cust_branch as branch
			WHERE sorder.order_no = line.order_no
			AND sorder.trans_type = line.trans_type
			AND sorder.trans_type = " . db_escape($trans_type) . "
			AND sorder.debtor_no = debtor.debtor_no
			AND sorder.branch_code = branch.branch_code
			AND debtor.debtor_no = branch.debtor_no
			AND sorder.so_item_type = 'repo'";


	if (isset($trans_no) && $trans_no != "") {
		// search orders with number like 
		$number_like = "%" . $trans_no;
		$sql .= " AND sorder.order_no LIKE " . db_escape($number_like);
		//				." GROUP BY sorder.order_no";
	} elseif ($ref != "") {
		// search orders with reference like 
		$sql .= " AND sorder.reference LIKE " . db_escape('%' . $ref . '%');
		//				." GROUP BY sorder.order_no";
	} else	// ... or select inquiry constraints
	{
		if ($filter != 'DeliveryTemplates' && $filter != 'InvoiceTemplates' && $filter != 'OutstandingOnly') {
			$date_after = date2sql($from);
			$date_before = date2sql($to);

			$sql .=  " AND sorder.ord_date >= '$date_after'"
				. " AND sorder.ord_date <= '$date_before'";
		}
	}
	if ($trans_type == ST_SALESQUOTE && !check_value('show_all'))
		$sql .= " AND sorder.delivery_date >= '" . date2sql(Today()) . "' AND line.qty_sent=0"; // show only outstanding, not realized quotes

	//if ($selected_customer != -1)
	//	$sql .= " AND sorder.debtor_no=".db_escape($selected_customer);

	if ($stock_item != ALL_TEXT)
		$sql .= " AND line.stk_code=" . db_escape($stock_item);

	if ($location != ALL_TEXT)
		$sql .= " AND sorder.from_stk_loc = " . db_escape($location);

	if ($filter == 'OutstandingOnly')
		$sql .= " AND line.qty_sent < line.quantity";

	if ($filter == 'PrepaidOrders')
		$sql .= " AND prep_amount>0";

	elseif ($filter == 'InvoiceTemplates' || $filter == 'DeliveryTemplates')
		$sql .= " AND sorder.type=1";

	//Chaiatanya : New Filter
	if ($customer_id != ALL_TEXT)
		$sql .= " AND sorder.debtor_no = " . db_escape($customer_id);

	$sql .= " GROUP BY sorder.order_no,
					sorder.debtor_no,
					sorder.branch_code,
					sorder.customer_ref,
					sorder.ord_date,
					sorder.deliver_to
				ORDER BY sorder.order_no DESC";
	return $sql;
}

//--------------------------------------------------------------------------------------------------
function update_prepaid_so_line($line_id, $qty_invoiced)
{
	$sql = "UPDATE " . TB_PREF . "sales_order_details
		SET invoiced = invoiced + " . (float)$qty_invoiced . "
		WHERE id=" . db_escape($line_id);

	db_query($sql, "The document detail record could not be updated with invoiced qty");
	return true;
}
/*
	Returns array of all issued invoices to sales order $order_no, optinally up to trans_no==$up_to
*/
function get_sales_order_invoices($order_no)
{
	$sql = "SELECT trans_no, dt.type as type, tran_date, reference, prep_amount
	    FROM " . TB_PREF . "debtor_trans dt
		LEFT JOIN " . TB_PREF . "voided v ON v.type=dt.type AND v.id=dt.trans_no
		WHERE ISNULL(v.id) AND dt.type=" . ST_SALESINVOICE . " AND dt.order_=" . db_escape($order_no)
		. " ORDER BY dt.tran_date, dt.reference, dt.trans_no";

	return db_query($sql, "cannot retrieve sales invoices for sales order");
}

function is_sales_order_started($order_no)
{
	$sql = "SELECT count(*) FROM " . TB_PREF . "sales_order_details WHERE order_no=" . db_escape($order_no) . " AND trans_type=" . ST_SALESORDER
		. " AND (invoiced!=0 OR qty_sent!=0)";

	$result = db_fetch(db_query($sql, "cannot retrieve sales invoices for sales order"));
	return $result[0];
}

//---------------------------------------------------------------------------------------------
//
// Mark/unmark sales order as template.
//
function sales_order_set_template($id, $status)
{
	$sql = "UPDATE " . TB_PREF . "sales_orders SET type = " . db_escape($status) . " WHERE order_no=" . db_escape($id);
	db_query($sql, "Can't change sales order type");
}

/*
	Check whether sales order is issued in prepaid mode and already opened
*/

function is_prepaid_order_open($order_no)
{
	$sql = "SELECT count(*)
		FROM " . TB_PREF . "sales_orders o,
		((SELECT trans_no_to FROM " . TB_PREF . "cust_allocations
				WHERE trans_type_to=" . ST_SALESORDER . " AND trans_no_to=" . db_escape($order_no) . ")
		UNION
		  (SELECT order_ FROM " . TB_PREF . "debtor_trans 
			WHERE type=" . ST_SALESINVOICE . " AND order_=" . db_escape($order_no) . ")) related
	WHERE
		o.prep_amount>0
		AND o.trans_type = " . ST_SALESORDER . "
		AND o.order_no = " . db_escape($order_no);

	$result = db_fetch(db_query($sql, "cannot check prepaid order open"));

	return $result[0];
}

function last_sales_order_detail($order, $field)
{
	$sql = "SELECT $field
            FROM " . TB_PREF . "sales_order_details d
            WHERE order_no =
            (SELECT order_no FROM " . TB_PREF . "sales_orders o
            WHERE debtor_no=" . db_escape($order->customer_id) . "
            ORDER BY order_no DESC LIMIT 1)
            ORDER BY d.id DESC LIMIT 1";

	$last_query = db_query($sql, "Could not retrieve last order detail");
	$row = db_fetch_row($last_query);
	return $row[0];
}

/* Added by Ronelle 3/25/2021 */
function update_so_draft_status(&$so_obj)
{
	begin_transaction();
	hook_db_prewrite($so_obj, ST_SALESORDER);

	/*Update the sales order draft status */
	$sql = "UPDATE " . TB_PREF . "sales_orders SET status=" . db_escape($so_obj->status) . ",approval_remarks=" . db_escape($so_obj->approval_remarks);
	$sql .= " WHERE order_no = " . $so_obj->order_no;
	db_query($sql, "The sales order could not be updated");

	add_audit_trail($so_obj->trans_type, $so_obj->order_no, Today(), _("Update Status."));
	hook_db_postwrite($so_obj, ST_SALESORDER);
	commit_transaction();

	return "Successfully Approved!";
}
/* Added by Albert 10/25/2021 */
function update_so_approval_account_specialist(&$so_obj)
{
	begin_transaction();
	hook_db_prewrite($so_obj, ST_SALESORDER);

	/*Update the sales order draft status */
	$sql = "UPDATE " . TB_PREF . "sales_orders SET account_specialist_remarks=" . db_escape($so_obj->account_specialist_remarks);
	$sql .= " WHERE order_no = " . $so_obj->order_no;
	db_query($sql, "The sales order could not be updated");

	add_audit_trail($so_obj->trans_type, $so_obj->order_no, Today(), _("Update Status."));
	hook_db_postwrite($so_obj, ST_SALESORDER);
	commit_transaction();

	return "Successfully Approved!";
}


/* Added by Ronelle 3/25/2021 */
function check_status_so($order_no) {

	//Modify spyrax10 10 May 2022
	set_global_connection();

	$sql = "SELECT status FROM " . TB_PREF . "sales_orders 
		WHERE order_no = " . db_escape($order_no);

	$result = db_query($sql, "The status for sales order " . $order_no . " could not be retrieved");
	if (db_num_rows($result) == 1) {
		$row = db_fetch_row($result);
		return $row[0];
	}
}
/* */

/* Added by Ronelle 4/1/2021 */
function closed_sales_order_status($so_obj)
{
	begin_transaction();
	hook_db_prewrite($so_obj, ST_SALESORDER);

	/*Update the sales order draft status */
	$sql = "UPDATE " . TB_PREF . "sales_orders SET status=" . db_escape($so_obj->status);
	$sql .= " WHERE order_no = " . $so_obj->order_no;
	db_query($sql, "The sales order could not be updated");

	add_audit_trail($so_obj->trans_type, $so_obj->order_no, Today(), _("Update Status."));
	hook_db_postwrite($so_obj, ST_SALESORDER);
	commit_transaction();
}
/* */

/*Added by Albert 11/06/2021*/ 
function get_comaker($customer_id, $co_maker = '', $all = false){
	set_global_connection();
	//Modified by spyrax10
	$sql = "SELECT CONCAT(A.lastname, ' ', A.firstname) AS comaker, A.comaker_id
		FROM " . TB_PREF . "co_makers A 
		WHERE A.debtor_no =" . db_escape($customer_id);
	
	if ($co_maker != '') {
		$sql .= " AND A.comaker_id =".db_escape($co_maker);
		$sql .= " GROUP BY A.comaker_id";
	}

	$result = db_query($sql, "could'nt get data!");
	
	if ($all) {
		return $result;
	}
	else {
		$row = db_fetch_row($result);
		return $row[0];
	}
	//
}

function Get_Repo_Installment_Price($stockID, $lot_no)
{

	$sql = "SELECT MAX(A.price)  FROM " . TB_PREF . " stock_moves A 
			WHERE type=80 and A.stock_id = " . db_escape($stockID) . "AND A.lot_no = " . db_escape($lot_no);

	$result = db_query($sql, "The LCP for the selected branch and item could not be retrieved");
	$row = db_fetch_row($result);
	$price = $row[0];
	return $price;
}
function Get_Repo($lot_no, $stockID)
{

	$sql = "SELECT A.lot_no  FROM " . TB_PREF . " stock_moves A 
			WHERE type=80 and A.stock_id = " . db_escape($stockID) . "AND A.lot_no = " . db_escape($lot_no);

	$result = db_query($sql, "The LCP for the selected branch and item could not be retrieved");
	$row = db_fetch_row($result);
	$price = $row[0];
	return $price;
}

function update_return_status(&$so_obj)
{
	begin_transaction();
	hook_db_prewrite($so_obj, ST_SALESINVOICE);

	/*Update the sales order draft status */
	$sql = "UPDATE " . TB_PREF . "debtor_trans SET return_status =" . db_escape($so_obj->approval_remarks);
	$sql .= " WHERE type != 13  AND  order_ = " . $so_obj->order_no;
	db_query($sql, "The debtor_trans return_status could not be updated");

	add_audit_trail($so_obj->trans_type, $so_obj->order_no, Today(), _("Update Return Status."));
	hook_db_postwrite($so_obj, ST_SALESINVOICE);
	commit_transaction();

	return "Successfully Approved!";
}

function update_restructured_status(&$so_obj)
{
	begin_transaction();
	hook_db_prewrite($so_obj, ST_SALESINVOICE);

	/*Update the sales order draft status */
	$sql = "UPDATE " . TB_PREF . "debtor_trans SET restructured_status =" .db_escape($so_obj->status).",approval_remarks=" . db_escape($so_obj->approval_remarks);
	$sql .= " WHERE type != 13  AND  order_ = " . $so_obj->order_no;
	db_query($sql, "The debtor_trans restructured_status could not be updated");

	add_audit_trail($so_obj->trans_type, $so_obj->order_no, Today(), _("Update Restructured Status."));
	hook_db_postwrite($so_obj, ST_SALESINVOICE);
	commit_transaction();

	return "Successfully Approved!";
}
function get_so_repo_status($lot_no)
{
	set_global_connection();
	$sql = "SELECT
				IFNULL(sorder.status, '') as status
			FROM " . TB_PREF . "sales_orders as sorder,"
			. TB_PREF . "sales_order_details as line 
				WHERE sorder.order_no = line.order_no
				AND sorder.trans_type = line.trans_type
				AND sorder.so_item_type = 'repo'";
			$sql .= " AND line.lot_no =" . db_escape($lot_no);
			/*modified by Albert*/
			$sql .= " GROUP BY line.lot_no, sorder.order_no";
			$sql .= " ORDER BY sorder.order_no DESC LIMIT 1";
			//
			$result = db_query($sql, "Can't Get Status!!!");
			$row = db_fetch_row($result);
	display_error($sql);
	return $row[0];
}
function get_so_item_type($order_no, $type)
{

	$sql = "SELECT 
				so_item_type
			FROM " . TB_PREF . "sales_orders as sorder
				WHERE sorder.order_no = " . db_escape($order_no)."
				AND sorder.trans_type = " . db_escape($type);
			$result = db_query($sql, "Can't Get item type!!!");
			$row = db_fetch_row($result);

	return $row[0];
}
function get_so_items($reference)
{

	$sql = "SELECT 
				sorder.account_specialist_remarks
			FROM " . TB_PREF . "sales_orders as sorder
				WHERE sorder.reference = " . db_escape($reference);
			$result = db_query($sql, "Can't Get item type!!!");

			$row = db_fetch_row($result);

			return $row[0];
}
function Get_Policy_Installment_Effectivity_Date($branchCode, $categoryID, $stockID)
{
	global $def_coy;
    set_global_connection($def_coy);
	
	$sql = "SELECT A.date_epic
			FROM ".TB_PREF."prices A 
				INNER JOIN ".TB_PREF."branch_policy B ON B.plcyprice_id = A.sales_type_id
    			INNER JOIN ".TB_PREF."stock_master C ON C.stock_id = A.stock_id AND C.category_id = B.category_id
			WHERE B.module_type = 'PRCPLCY'
				AND B.branch_code = ".db_escape($branchCode)."
				AND C.category_id = ".db_escape($categoryID)."
    			AND A.stock_id = ".db_escape($stockID);

	$result = db_query($sql, "The LCP for the selected branch and item could not be retrieved");
	$row = db_fetch_row($result);
	set_global_connection();

	return $row[0];
}
function Get_Policy_CashPrice_Effectivity_Date($branchCode, $categoryID, $stockID)
{
    global $def_coy;
    set_global_connection($def_coy);
	
	$sql = "SELECT A.date_epic
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
function Get_Previous_Policy_CashPrice_last_date_updated($branchCode, $categoryID, $stockID)
{
    global $def_coy;
    set_global_connection($def_coy);

	
	$sql = "SELECT  max(D.date_defined)
			FROM ".TB_PREF."cash_prices A 
				INNER JOIN ".TB_PREF."branch_policy B ON B.plcycashprice_id = A.scash_type_id
    			INNER JOIN ".TB_PREF."stock_master C ON C.stock_id = A.stock_id AND C.category_id = B.category_id
				INNER JOIN ".TB_PREF."price_cost_archive D ON D.plcycashprice_id = A.scash_type_id and D.stock_id = A.stock_id
			WHERE B.module_type = 'CSHPRCPLCY'
				AND D.inactive = 1
				AND B.branch_code = ".db_escape($branchCode)."
				AND C.category_id = ".db_escape($categoryID)."
    			AND A.stock_id = ".db_escape($stockID);

	$result = db_query($sql, "The cash price for the selected branch and item could not be retrieved");

	$row = db_fetch_row($result);
	$date_defined = $row[0];

	set_global_connection();
	return $date_defined;
}

function Get_Previous_Policy_Installment_Price_last_date_updated($branchCode, $categoryID, $stockID)
{
    global $def_coy;
    set_global_connection($def_coy);
	
	$sql = "SELECT max(D.date_defined)
			FROM ".TB_PREF."prices A 
				INNER JOIN ".TB_PREF."branch_policy B ON B.plcyprice_id = A.sales_type_id
    			INNER JOIN ".TB_PREF."stock_master C ON C.stock_id = A.stock_id AND C.category_id = B.category_id
				INNER JOIN ".TB_PREF."price_cost_archive D ON D.plcyprice_id = A.sales_type_id and D.stock_id = A.stock_id
			WHERE B.module_type = 'PRCPLCY'
				AND D.inactive = 1
				AND B.branch_code = ".db_escape($branchCode)."
				AND C.category_id = ".db_escape($categoryID)."
    			AND A.stock_id = ".db_escape($stockID);

	$result = db_query($sql, "The LCP for the selected branch and item could not be retrieved");
	$row = db_fetch_row($result);
	$date_defined = $row[0];

	set_global_connection();
	return $date_defined;
}
/*---END by Albert*/

//Added by spyrax10 27 Sep 2022 

function get_temp_discount($id = '', $category = '', $user_id = '', $reference = '') {

    set_global_connection();

    $sql = "SELECT * FROM " . TB_PREF . "temp_discount";

    if ($id != '') {
        $sql .= " WHERE id = " .db_escape($id);
    }
	else {
		$sql .= " WHERE id > 0";
	}

    if ($category != '') {
        $sql .= " AND category = " .db_escape($category);
    }

	if ($user_id != '') {
		$sql .= " AND user_id = " .db_escape($user_id);
	}

	if ($reference = '') {
		$sql .= " AND so_ref = " .db_escape($reference);
	}

    return db_query($sql, "get_temp_discount()");
}

function update_so_discount($order_no = 0, $stock_id = '', $discount = 0) {
	set_global_connection();

	$sql = "UPDATE sales_order_details SET discount1 = " .db_escape(remove_comma($discount));

	$sql .= " WHERE order_no = " .db_escape($order_no) 
	. " AND stk_code = " .db_escape($stock_id);

	db_query($sql, "update_so_discount()");

	return $order_no;
}

function get_setup_discount($id = 0) {
	set_global_connection();

	$sql = "SELECT IFNULL(setup_discount, 0) FROM " . TB_PREF . "temp_discount ";
	$sql .= " WHERE id = " .db_escape($id);

	$result = db_query($sql, "get_setup_discount()");

	$row = db_fetch_row($result);
	return $row[0];
}

function update_temp_discount_total($user_id = null, $so_ref = null, $date_created = null, $doc_total = 0, 
	$stock_id = null) {
	
	set_global_connection();

	$sql = "UPDATE temp_discount SET item_code = " .db_escape($stock_id) .", 
		doc_total = " .db_escape(remove_comma($doc_total));

	$sql .= "WHERE user_id = " .db_escape($user_id) . 
	" AND so_ref = " .db_escape($so_ref) . 
	" AND date_created = " .db_escape($date_created);

	db_query($sql, "update_temp_discount_total()");

	return $user_id;
}

function update_given_discount($id = '', $status = '', $given_discount = 0, $user_id = 0, $date_approved = null) {
	
	set_global_connection();

	$sql = "UPDATE temp_discount SET given_discount = " .db_escape($given_discount) . 
		", status = " .db_escape($status) . ", aprroved_by = " .db_escape($user_id) . 
		", date_approved = " .db_escape($date_approved);

	$sql .= "WHERE id = " .db_escape($id);

	db_query($sql, "update_temp_discount_status()");

	return $id;
}

function temp_discount_exist($user_id = null, $so_ref = null, $date_created = null) {
	
	set_global_connection();

	$sql = "SELECT COUNT(*) FROM ".TB_PREF."temp_discount A 
		WHERE A.user_id = " .db_escape($user_id) . 
            " AND A.so_ref = " .db_escape($so_ref) . 
			" AND date_created = " .db_escape($date_created);

	$result = db_query($sql, "temp_discount_exist()");
	$row = db_fetch_row($result);
	return $row[0];
}

function reset_temp_discount($user_id = 0, $so_ref = null, $date_created = null) {
	
	set_global_connection();

	$sql = "UPDATE temp_discount SET doc_total = 0";

	$sql .= "WHERE A.user_id = " .db_escape($user_id) . 
	" AND A.so_ref = " .db_escape($so_ref) . 
	" AND date_created = " .db_escape(sql2date($date_created));

	db_query($sql, "update_temp_discount_total()");

}

function add_temp_dicount($user_id = 0, $so_ref = null, $debtor_no = 0, $pay_type = null, $category = null, $item_code = '', $doc_total = 0,
	$status = null, $given_dis = 0) {
	
	set_global_connection();

	$setup_discount = Get_Sales_Discount1($item_code) + Get_Sales_Discount2($item_code);

	$sql = "INSERT INTO ".TB_PREF."temp_discount (so_ref, debtor_no, pay_type, date_created, user_id, category, item_code, 
	doc_total, status, setup_discount, given_discount, aprroved_by, date_approved)";

	$sql .= "VALUES (
		". db_escape($so_ref) .",
		". db_escape($debtor_no) .", 
		". db_escape($pay_type) .", 
		". db_escape(date2sql(Today())) .", 
		". db_escape($user_id) .", 
		". db_escape($category) .", 
		". db_escape($item_code) .", 
		". db_escape($doc_total) .", 
		". db_escape($status) .", 
		". db_escape(remove_comma($setup_discount)) .", 
		". db_escape(remove_comma($given_dis)) .", 
		0, null
	)";

	if ($setup_discount > 0) {
		db_query($sql, "add_temp_dicount()");
	}
	else {
		display_error(_("Cannot Proceed! There is no discount setup for this Item: $item_code"));
	}

	return db_insert_id();
}
