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
// Mark changes in debtor_trans_details
//
function update_customer_trans_version($type, $versions)
{

	$sql = 'UPDATE ' . TB_PREF . 'debtor_trans SET version=version+1
			WHERE type=' . db_escape($type) . ' AND (';

	foreach ($versions as $trans_no => $version)
		$where[] = 	'(trans_no=' . db_escape($trans_no) . ' AND version=' . $version . ')';

	$sql .= implode(' OR ', $where) . ')';

	return  db_query($sql, 'Concurrent editing conflict');
}

//----------------------------------------------------------------------------------------
// Gets document header versions for transaction set of type $type
// $trans_no = array(num1, num2,...);
// returns array(num1=>ver1, num2=>ver2...)
//
function get_customer_trans_version($type, $trans_no)
{
	if (!is_array($trans_no))
		$trans_no = array($trans_no);

	$sql = 'SELECT trans_no, version FROM ' . TB_PREF . 'debtor_trans
			WHERE type=' . db_escape($type) . ' AND (';

	foreach ($trans_no as $key => $trans)
		$trans_no[$key] = 	'trans_no=' . db_escape($trans_no[$key]);

	$sql .= implode(' OR ', $trans_no) . ')';

	$res = db_query($sql, 'document version retreival');

	$vers = array();
	while ($mysql = db_fetch($res)) {
		$vers[$mysql['trans_no']] = $mysql['version'];
	}
	return $vers;
}
//----------------------------------------------------------------------------------------
// $Total, $Tax, $Freight, $discount all in customer's currency
// date_ is display date (non-sql)
// Update Code Format by spyrax10 12 Apr 2022
function write_customer_trans(
	$trans_type, $trans_no, $debtor_no, $BranchNo, $date_, $reference,
	$Total = 0, 
	$discount = 0, $Tax = 0, $Freight = 0, $FreightTax = 0, $sales_type = 0,
	$order_no = 0,
	$ship_via = 0, $due_date = "",
	$AllocAmt = 0,
	$rate = 0, $dimension_id = 0, $dimension2_id = 0,
	$payment_terms = null, $tax_included = 0, $prep_amount = 0,
	$opening_balances = 0, $payment_type = null,
	$collection_type = 0, $module_type = null) {

	$new = $trans_no == 0;
	$curr = get_customer_currency($debtor_no);
	if ($rate == 0) {
		$rate = get_exchange_rate_from_home_currency($curr, $date_);
	}

	$SQLDate = date2sql($date_);
	if ($due_date == "") {
		$SQLDueDate = "0000-00-00";
	}
	else {
		$SQLDueDate = date2sql($due_date);
	}

	if ($trans_type == ST_BANKPAYMENT) {
		$Total = -$Total;
	}

	if ($new || !exists_customer_trans($trans_type, $trans_no)) {
		if ($new) { //Modified by spyrax10 7 Apr 2022
			if ($trans_type == ST_SALESINVOICEREPO || $trans_type == ST_SALESINVOICE 
				|| $trans_type == ST_SITERMMOD || $trans_type == ST_RESTRUCTURED) {
				$max_num = max(
					get_max_trans_no(ST_SALESINVOICE), get_max_trans_no(ST_SALESINVOICEREPO),
					get_max_trans_no(ST_SITERMMOD), get_max_trans_no(ST_RESTRUCTURED)
				);
				$trans_no =  $max_num + 1;
			}
			else if ($trans_type == ST_JOURNAL) {
				$trans_no = get_max_trans_no(ST_JOURNAL);
			}
			else {
				$trans_no = get_next_trans_no($trans_type);
			}			
		}
		$status = "Pending";
		if ($payment_terms == 0 || $payment_terms == null)
			$status = "Open";

		if ($trans_type == ST_CUSTPAYMENT) {
			if ($module_type == "CLTN-DPWOSI") {
				$status = "Open";
			} else {
				$status = "Closed";
			}
		}

		$sql = 
		"INSERT INTO " . TB_PREF . "debtor_trans (
			trans_no, type, debtor_no, branch_code, tran_date, due_date,
			reference, tpe, order_, ov_amount, ov_discount, ov_gst, ov_freight, ov_freight_tax,
			rate, ship_via, alloc,
			dimension_id, dimension2_id, payment_terms, tax_included, prep_amount, opening_balances, 
			status, payment_type, collect_id, module_type
		) 
		VALUES (
			" . db_escape($trans_no) . ", 
			" . db_escape($trans_type) . ",
			" . db_escape($debtor_no) . ", 
			" . db_escape($BranchNo) . ",
			" . db_escape($SQLDate) . ",
			" . db_escape($SQLDueDate) . ",
			" . db_escape($reference) . ",
			" . db_escape($sales_type) . ", 
			" . db_escape($order_no) . ", 
			" . db_escape($Total) . ", 
			" . db_escape($discount) . ", 
			" . db_escape($Tax) . ", 
			" . db_escape($Freight) . ",
			" . db_escape($FreightTax) . ",
			" . db_escape($rate) . ",
			" . db_escape($ship_via) . ", 
			" . db_escape($AllocAmt) . ", 
			" . db_escape($dimension_id) . ", 
			" . db_escape($dimension2_id) . ", 
			" . db_escape($payment_terms, true) . ", 
			" . db_escape($tax_included) . ", 
			" . db_escape($prep_amount) . ", 
			" . db_escape($opening_balances) . ", 
			" . db_escape($status) . ", 
			" . db_escape($payment_type) . ", 
			" . db_escape($collection_type) . ", 
			" . db_escape($module_type) . "
		)";

		//echo $sql;
	} else {	// may be optional argument should stay unchanged ?

		$sql = 
		"UPDATE " . TB_PREF . "debtor_trans SET
			debtor_no = " . db_escape($debtor_no) . ", 
			branch_code = " . db_escape($BranchNo) . ",
			tran_date = " . db_escape($SQLDate) . ", 
			due_date = " . db_escape($SQLDueDate) . ",
			reference = " . db_escape($reference) . ", 
			tpe = " . db_escape($sales_type) . ", 
			order_=" . db_escape($order_no) . ",
			ov_amount = " . db_escape($Total) . ", 
			ov_discount = " . db_escape($discount) . ", 
			ov_gst = " . db_escape($Tax) . ",
			ov_freight = " . db_escape($Freight) . ", 
			ov_freight_tax = " . db_escape($FreightTax) . ", 
			rate = " . db_escape($rate) . ", 
			ship_via = " . db_escape($ship_via) . ", 
			alloc = " . db_escape($AllocAmt) . ",
			dimension_id = " . db_escape($dimension_id) . ", dimension2_id=" . db_escape($dimension2_id) . ",
			payment_terms=" . db_escape($payment_terms, true) . ",
			tax_included=" . db_escape($tax_included) . ",
			prep_amount =" . db_escape($prep_amount) . ",
			payment_type = " . db_escape($payment_type) . ",
			collect_id = " . db_escape($collection_type) . "

		WHERE trans_no=" . db_escape($trans_no) . " AND type=" . db_escape($trans_type);
	}
	db_query($sql, "The debtor transaction record could not be inserted");
	// skip for journal entries
	if ($trans_type != ST_JOURNAL) {
		add_audit_trail($trans_type, $trans_no, $date_, $new ? '' : _("Updated."));
	} 
	//echo $sql;
	return $trans_no;
}
//----------------------------------------------------------------------------------------

function get_customer_trans($trans_id, $trans_type, $customer_id = null)
{
	global $SysPrefs;

	$sql = "SELECT trans.*,"
		. "ov_amount+ov_gst+ov_freight+ov_freight_tax+ov_discount AS Total,"
		. "cust.name AS DebtorName, cust.address, "
		. "cust.curr_code, "
		. "cust.tax_id,
		trans.prep_amount>0 as prepaid,"
		. "com.memo_";

	if ($trans_type == ST_CUSTPAYMENT || $trans_type == ST_BANKDEPOSIT) {
		// it's a payment so also get the bank account
		$sql .= ",bank_act," . TB_PREF . "bank_accounts.bank_name, " . TB_PREF . "bank_accounts.bank_account_name,
			" . TB_PREF . "bank_accounts.account_type AS BankTransType,
			" . TB_PREF . "bank_accounts.bank_curr_code,
			" . TB_PREF . "bank_trans.amount as bank_amount";
	}

	if ($trans_type == ST_SALESINVOICE || $trans_type == ST_SALESINVOICEREPO || $trans_type == ST_CUSTCREDIT || $trans_type == ST_CUSTDELIVERY) {
		// it's an invoice so also get the shipper and salestype
		$sql .= ", " . TB_PREF . "shippers.shipper_name, "
			. TB_PREF . "sales_types.sales_type, "
			. TB_PREF . "sales_types.tax_included, "
			. "branch.*, "
			. "cust.discount, "
			. TB_PREF . "tax_groups.name AS tax_group_name, "
			. TB_PREF . "tax_groups.id AS tax_group_id ";
	}

	if ($trans_type == ST_JOURNAL) {
		$sql .= ", branch.*";
	}

	$sql .= " FROM " . TB_PREF . "debtor_trans trans
					LEFT JOIN " . TB_PREF . "comments com ON trans.type=com.type AND trans.trans_no=com.id
					LEFT JOIN " . TB_PREF . "shippers ON " . TB_PREF . "shippers.shipper_id=trans.ship_via, 
					" . TB_PREF . "debtors_master cust";

	if ($trans_type == ST_CUSTPAYMENT || $trans_type == ST_BANKDEPOSIT) {
		// it's a payment so also get the bank account
		$sql .= ", " . TB_PREF . "bank_trans, " . TB_PREF . "bank_accounts";
	}

	if ($trans_type == ST_SALESINVOICE || $trans_type == ST_SALESINVOICEREPO || $trans_type == ST_CUSTCREDIT || $trans_type == ST_CUSTDELIVERY) {
		// it's an invoice so also get the shipper, salestypes
		$sql .= ", " . TB_PREF . "sales_types, "
			. TB_PREF . "cust_branch branch, "
			. TB_PREF . "tax_groups ";
	}

	if ($trans_type == ST_JOURNAL) {
		$sql .= ", " . TB_PREF . "cust_branch branch ";
	}

	$sql .= " WHERE trans.trans_no=" . db_escape($trans_id) . "
		AND trans.type=" . db_escape($trans_type) . "
		AND trans.debtor_no=cust.debtor_no";

	if (isset($customer_id))
		$sql .= " AND trans.debtor_no=" . db_escape($customer_id);

	if ($trans_type == ST_CUSTPAYMENT || $trans_type == ST_BANKDEPOSIT) {
		// it's a payment so also get the bank account
		$sql .= " AND " . TB_PREF . "bank_trans.trans_no =" . db_escape($trans_id) . "
			AND " . TB_PREF . "bank_trans.type=$trans_type
			AND " . TB_PREF . "bank_trans.amount != 0
			AND " . TB_PREF . "bank_accounts.id=" . TB_PREF . "bank_trans.bank_act ";
	}
	if ($trans_type == ST_SALESINVOICE || $trans_type == ST_SALESINVOICEREPO || $trans_type == ST_CUSTCREDIT || $trans_type == ST_CUSTDELIVERY) {
		// it's an invoice so also get the shipper
		$sql .= " AND " . TB_PREF . "sales_types.id = trans.tpe
			AND branch.branch_code = trans.branch_code
			AND branch.tax_group_id = " . TB_PREF . "tax_groups.id ";
	}
	if ($trans_type == ST_JOURNAL) {
		$sql .= " AND branch.branch_code = trans.branch_code ";
	}

	$sql .= " GROUP BY trans.trans_no";

	$result = db_query($sql, "Cannot retreive a debtor transaction");

	if (db_num_rows($result) == 0) {
		// can't return nothing
		if ($SysPrefs->go_debug)
			display_backtrace();
		display_db_error("no debtor trans found for given params", $sql, true);
		exit;
	}
	if (db_num_rows($result) > 1) {
		// can't return multiple
		if ($SysPrefs->go_debug)
			display_backtrace();
		display_db_error("duplicate debtor transactions found for given params", $sql, true);
		exit;
	}

	$row = db_fetch($result);
	return $row;
}

//----------------------------------------------------------------------------------------

function exists_customer_trans($type, $type_no)
{
	$sql = "SELECT trans_no FROM " . TB_PREF . "debtor_trans WHERE type=" . db_escape($type) . "
		AND trans_no=" . db_escape($type_no);

	$result = db_query($sql, "Cannot retreive a debtor transaction");

	return (db_num_rows($result) > 0);
}

//----------------------------------------------------------------------------------------

// retreives the related sales order for a given trans

function get_customer_trans_order($type, $type_no)
{
	$sql = "SELECT order_ FROM " . TB_PREF . "debtor_trans WHERE type=" . db_escape($type) . " AND trans_no=" . db_escape($type_no);

	$result = db_query($sql, "The debtor transaction could not be queried");

	$row = db_fetch_row($result);

	return $row[0];
}

//----------------------------------------------------------------------------------------

function get_customer_details_from_trans($type, $type_no)
{
	$sql = "SELECT debtor.name, debtor.curr_code, branch.br_name
		FROM "
		. TB_PREF . "debtors_master debtor,"
		. TB_PREF . "cust_branch branch,"
		. TB_PREF . "debtor_trans trans
		WHERE trans.type=" . db_escape($type) . " AND trans.trans_no=" . db_escape($type_no) . "
		AND debtor.debtor_no = trans.debtor_no
		AND	branch.branch_code = trans.branch_code";

	$result = db_query($sql, "could not get customer details from trans");
	return db_fetch($result);
}

//----------------------------------------------------------------------------------------

function void_customer_trans($type, $type_no)
{
	// clear all values and mark as void
	$sql = "UPDATE " . TB_PREF . "debtor_trans SET ov_amount=0, ov_discount=0, ov_gst=0, ov_freight=0,
		ov_freight_tax=0, alloc=0, prep_amount=0, version=version+1 WHERE type=" . db_escape($type) . " AND trans_no=" . db_escape($type_no);

	db_query($sql, "could not void debtor transactions for type=$type and trans_no=$type_no");
}

//----------------------------------------------------------------------------------------

function clear_customer_trans($type, $type_no)
{
	// Delete
	$sql = "DELETE FROM " . TB_PREF . "debtor_trans WHERE type=" . db_escape($type) . " AND trans_no=" . db_escape($type_no);

	db_query($sql, "could not clear debtor transactions for type=$type and trans_no=$type_no");
}
//----------------------------------------------------------------------------------------

function post_void_customer_trans($type, $type_no)
{
	switch ($type) {
		case ST_SALESINVOICE:
		case ST_CUSTCREDIT:
			void_sales_invoice($type, $type_no);
			break;
		case ST_CUSTDELIVERY:
			void_sales_delivery($type, $type_no);
			break;
		case ST_CUSTPAYMENT:
			void_customer_payment($type, $type_no);
			break;
	}
}

//----------------------------------------------------------------------------------------
function get_sql_for_customer_inquiry(
	$from,
	$to,
	$cust_id = ALL_TEXT,
	$filter = ALL_TEXT,
	// Added by spyrax10
	$del_type = ALL_TEXT,
	$trans_no = ''
	//, $show_voided = 0 //Commented by spyrax10
) {
	$date_after = date2sql($from);
	$date_to = date2sql($to);

	$sql = "SELECT

		trans.type,	
		trans.trans_no, 
		trans.order_, 
		trans.reference,
		trans.tran_date, 
		trans.due_date, 
		debtor.name, 
		branch.br_name,
		debtor.curr_code,
		IF(prep_amount, prep_amount, trans.ov_amount + trans.ov_gst + trans.ov_freight 
			+ trans.ov_freight_tax + trans.ov_discount)	AS TotalAmount, 
		
		X.item_type AS Note, " //Modified by spyrax10
		//   	if ($filter != ALL_TEXT)
		//  		$sql .= "@bal := @bal+IF(prep_amount, prep_amount, trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount), ";

		//	else
		//		$sql .= "IF(trans.type=".ST_CUSTDELIVERY.",'', IF(trans.type=".ST_SALESINVOICE." OR trans.type=".ST_BANKPAYMENT.",@bal := @bal+
		//			(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount), @bal := @bal-
		//			(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount))) , ";
		. "IF(trans.type IN(" . implode(',',  array(ST_CUSTCREDIT, ST_CUSTPAYMENT, ST_BANKDEPOSIT)) . "), -1, 1)
				*(IF(prep_amount, prep_amount, trans.ov_amount + trans.ov_gst + trans.ov_freight 
			+ trans.ov_freight_tax + trans.ov_discount)-trans.alloc) Balance, 
		debtor.debtor_no,";

	$sql .= "trans.alloc AS Allocated,
		((trans.type = " . ST_SALESINVOICE . " || trans.type = " . ST_JOURNAL . ")
			AND trans.due_date < '" . date2sql(Today()) . "') AS OverDue ,
		Sum(line.quantity-line.qty_done) AS Outstanding,
		Sum(line.qty_done) AS HasChild,
		prep_amount

		FROM " . TB_PREF . "debtor_trans as trans
			LEFT JOIN " . TB_PREF . "debtor_trans_details as line
				ON trans.trans_no=line.debtor_trans_no AND trans.type=line.debtor_trans_type
            LEFT JOIN " . TB_PREF . "audit_trail as audit ON (trans.type=audit.type AND trans.trans_no=audit.trans_no)
            LEFT JOIN " . TB_PREF . "users as user ON (audit.user=user.id)
			LEFT JOIN " . TB_PREF . "cust_branch as branch ON trans.branch_code=branch.branch_code 
			LEFT JOIN (SELECT DTT.debtor_trans_no, DTT.debtor_trans_type, 
				CASE WHEN DTT.item_type = 'Promo' THEN 'FREE ITEM'
				ELSE 'MAIN UNIT' END AS item_type 
				FROM " . TB_PREF . "debtor_trans_details DTT
				GROUP BY DTT.debtor_trans_no, DTT.debtor_trans_type) X ON trans.trans_no = X.debtor_trans_no 
				AND X.debtor_trans_type = trans.type, " //Added by spyrax10
		. TB_PREF . "debtors_master as debtor

		WHERE debtor.debtor_no = trans.debtor_no"; // exclude voided transactions and self-balancing (journal) transactions:

	//Commented by spyrax10
	//if (!$show_voided) 	
	//$sql .= " AND ISNULL(v.date_) AND (trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount) != 0";

	//Added by spyrax10
	if ($trans_no != '') {
		$sql .= " AND trans.trans_no = " .db_escape($trans_no) . " OR trans.order_ = " .db_escape($trans_no);
	}

	if ($del_type != ALL_TEXT) {
		if ($del_type == '1') { //Main Unit
			$sql .= " AND X.item_type = 'MAIN UNIT' ";
		} else if ($del_type == '2') { //Free Item
			$sql .= " AND X.item_type = 'FREE ITEM' ";
		}
	}

	if ($filter == '2')
		$sql .= " AND ABS(IF(prep_amount, prep_amount, trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount)-trans.alloc)>"
			. FLOAT_COMP_DELTA;
	else {
		$sql .= " AND trans.tran_date >= '$date_after'
			AND trans.tran_date <= '$date_to'";
	}

	if ($cust_id != ALL_TEXT)
		$sql .= " AND trans.debtor_no = " . db_escape($cust_id);

	if ($filter != ALL_TEXT) {
		if ($filter == '1') {
			$sql .= " AND (trans.type = " . ST_SALESINVOICE . ") ";
		} elseif ($filter == '2') {
			$sql .= " AND (trans.type <> " . ST_CUSTDELIVERY . ") ";
		} elseif ($filter == '3') {
			$sql .= " AND (trans.type = " . ST_CUSTPAYMENT
				. " OR trans.type = " . ST_BANKDEPOSIT . " OR trans.type = " . ST_BANKPAYMENT . ") ";
		} elseif ($filter == '4') {
			$sql .= " AND trans.type = " . ST_CUSTCREDIT . " ";
		} elseif ($filter == '5') {
			$sql .= " AND trans.type = " . ST_CUSTDELIVERY . " ";
		}

		if ($filter == '2') {
			$today =  date2sql(Today());
			$sql .= " AND trans.due_date < '$today'
				AND (trans.ov_amount + trans.ov_gst + trans.ov_freight_tax + 
				trans.ov_freight + trans.ov_discount - trans.alloc > 0) ";
		}
	}

	$sql .= " GROUP BY trans.trans_no, trans.type";

	return $sql;
}

function get_sql_for_sales_deliveries_view($from, $to, $customer_id, $stock_item, $location, $delivery, $outstanding = false)
{
	$sql = "SELECT trans.trans_no,
			debtor.name,
			branch.branch_code,
			branch.br_name,
			sorder.deliver_to,
			trans.reference,
			sorder.customer_ref,
			trans.tran_date,
			trans.due_date,
			(ov_amount+ov_gst+ov_freight+ov_freight_tax) AS DeliveryValue,
			debtor.curr_code,
			Sum(line.quantity-line.qty_done) AND sorder.prep_amount=0 AS Outstanding,
			Sum(line.qty_done) AS Done
		FROM "
		. TB_PREF . "sales_orders as sorder, "
		. TB_PREF . "debtor_trans as trans, "
		. TB_PREF . "debtor_trans_details as line, "
		. TB_PREF . "debtors_master as debtor, "
		. TB_PREF . "cust_branch as branch
			WHERE
			sorder.order_no = trans.order_ AND
			trans.debtor_no = debtor.debtor_no
				AND trans.type = " . ST_CUSTDELIVERY . "
				AND line.debtor_trans_no = trans.trans_no
				AND line.debtor_trans_type = trans.type
				AND trans.branch_code = branch.branch_code
				AND trans.debtor_no = branch.debtor_no ";

	if ($outstanding == true) {
		$sql .= " AND line.qty_done < line.quantity ";
	}

	//figure out the sql required from the inputs available
	if ($delivery) {
		$sql .= " AND trans.trans_no LIKE " . db_escape('%' . $delivery . '%');
		$sql .= " GROUP BY trans.trans_no";
	} else {
		$sql .= " AND trans.tran_date >= '" . date2sql($from) . "'";
		$sql .= " AND trans.tran_date <= '" . date2sql($to) . "'";

		if ($stock_item != ALL_TEXT)
			$sql .= " AND line.stock_id=" . db_escape($stock_item) . " ";

		if ($location != ALL_TEXT)
			$sql .= " AND sorder.from_stk_loc = " . db_escape($location) . " ";

		if ($customer_id != ALL_TEXT)
			$sql .= " AND trans.debtor_no = " . db_escape($customer_id);

		$sql .= " GROUP BY trans.trans_no ";
	} //end no delivery number selected
	return $sql;
}

/* Added by Ronelle 5/5/2021 */
function add_debtor_loans(
	$trans_no,
	$debtor_no,
	$invoice_ref_no,
	$delivery_ref_no,
	$reference,
	$invoice_date,
	$orig_branch_code,
	$invoice_type,
	$installmentplcy_id,
	$months_term,
	$rebate,
	$financing_rate,
	$firstdue_date,
	$maturity_date,
	$outstanding_ar_amount,
	$ar_amount,
	$lcp_amount,
	$downpayment_amount,
	$amortization_amount,
	$total_amount,
	$category_id,
	$warranty_code,
	$fsc_series,
	$payment_location,
	$co_maker = "",
	$discount_dp_amount = 0,
	$deferred_gross_profit = 0,
	$profit_margin = 0,
	$document_ref = "",//Added by spyrax10
	$old_trans_no = 0,
	$discount_dp_amount2, //Added by Albert
	$financing_gross = 0	//Added by Albert
) {
	// $outstanding_ar_amount = $ar_amount - $downpayment_amount; //comment by albert
	if ($installmentplcy_id == 0) {
		$firstdue_date = "0000-00-00";
		$maturity_date = "0000-00-00";
	} else {
		$firstdue_date = date2sql($firstdue_date);
		$maturity_date = date2sql($maturity_date);
	}

	$document_ref = get_post('document_ref'); //Added by spyrax10

	$sql = "INSERT INTO " . TB_PREF . "debtor_loans (
		trans_no,
		debtor_no,
		invoice_ref_no,
		delivery_ref_no,
		reference,
		invoice_date,
		orig_branch_code,
		invoice_type,
		installmentplcy_id,
		months_term,
		rebate,
		financing_rate,
		firstdue_date,
		maturity_date,
		outstanding_ar_amount,
		ar_amount,
		lcp_amount,
		downpayment_amount,
		amortization_amount,
		total_amount,
		category_id,
		warranty_code,
		fsc_series,
		payment_location,
		co_maker,
		discount_downpayment,
		deferred_gross_profit,
		profit_margin,
		ref_no,
		old_trans_no,
		discount_downpayment2,
		financing_gross
		) VALUES (
		" . db_escape($trans_no) . ",
		" . db_escape($debtor_no) . ",
		" . db_escape($invoice_ref_no) . ",
		" . db_escape($delivery_ref_no) . ",
		" . db_escape($reference) . ",
		'" . date2sql($invoice_date) . "',
		" . db_escape($orig_branch_code) . ",
		" . db_escape($invoice_type) . ",
		$installmentplcy_id,
		$months_term,
		$rebate,
		$financing_rate,
		'$firstdue_date',
		'$maturity_date',
		$outstanding_ar_amount,
		$ar_amount,
		$lcp_amount,
		$downpayment_amount,
		$amortization_amount,
		$total_amount,
		$category_id,
		" . db_escape($warranty_code) . ",
		" . db_escape($fsc_series) . ",
		" . db_escape($payment_location) . ",
		" . db_escape(get_post('co_maker')) . ",
		$discount_dp_amount,
		$deferred_gross_profit,
		$profit_margin, 
		" . db_escape($document_ref) . ",
		".db_escape($old_trans_no).",
		$discount_dp_amount2,
		$financing_gross)"; //modified by Albert 06/30/2022

	db_query($sql, "The debtor loans record could not be inserted");
}
/* */

/* Update Sales Invoice Status Added by Ronelle 5/12/2021 */
function update_dp_status($trans_no, $trans_type)
{
	$sql = "SELECT a.alloc,b.downpayment_amount,b.ar_amount,a.status FROM " . TB_PREF . "debtor_trans a
		INNER JOIN debtor_loans b ON b.trans_no = a.trans_no 
		WHERE a.trans_no =" . db_escape($trans_no) . " AND a.type =" . db_escape($trans_type);
	$result = db_query($sql, "no allocation found");
	if (db_num_rows($result) == 1) {
		$row = db_fetch_row($result);
		if ($row[0] >= $row[1]) {
			$status = $row[0] == $row[2] ? "Closed" : "Open";
			$sql = "UPDATE " . TB_PREF . "debtor_trans SET status =" . db_escape($status) . "
					WHERE trans_no=" . db_escape($trans_no) . " AND type=" . db_escape($trans_type);
			db_query($sql, "The sales invoice status cannot be updated");
		}
	}
}
/* */

/* Read debtor_loans table by Ronelle 5/272/2021 */
function get_debtor_loans($trans_no, $inv_type = "new")
{
	$sql = "SELECT * FROM " . TB_PREF . "debtor_loans WHERE trans_no=$trans_no 
		AND invoice_type=" . db_escape($inv_type);

	$result = db_query($sql, "Cannot retreive a debtor transaction");
	$row = db_fetch($result);
	return $row;
}
/* *

/* Check if the customer is employee Added by Ronelle 7/23/2021 */
function check_employee_customer($customer_id)
{
	global $SysPrefs;

	$sql = "SELECT employee FROM " . TB_PREF . "debtors_master WHERE debtor_no=$customer_id";
	$result = db_query($sql, "no customer found");
	if (db_num_rows($result) == 1) {
		$row = db_fetch_row($result);
		return $row[0];
	}

	if (db_num_rows($result) > 1) {
		// can't return multiple
		if ($SysPrefs->go_debug)
			display_backtrace();
		display_db_error("duplicate debtor id found for given params", $sql, true);
		exit;
	}
}
/* */

/* Get Cost of Sales of SI by Delivery Ref No Added by Ronelle 7/23/2021 */
function get_cost_of_sales_for_si($deliver_ref_no)
{
	global $SysPrefs;

	$sql = "SELECT ov_amount FROM " . TB_PREF . "debtor_trans WHERE type = 13 AND reference=" . db_escape($deliver_ref_no);
	$result = db_query($sql, "no delivery found");
	if (db_num_rows($result) == 1) {
		$row = db_fetch_row($result);
		return $row[0];
	}

	if (db_num_rows($result) > 1) {
		// can't return multiple
		if ($SysPrefs->go_debug)
			display_backtrace();
		display_db_error("duplicate deliveries id found for given params", $sql, true);
		exit;
	}
}
/* */

/* Added by Ronelle 8/19/2021 */
function add_debtor_term_modification(
	$trans_no,
	$debtor_no,
	$invoice_ref_no,
	$delivery_ref_no,
	$term_mod_date,
	$installmentplcy_id,
	$months_term,
	$rebate,
	$financing_rate,
	$firstdue_date,
	$maturity_date,
	$outstanding_ar_amount,
	$ar_amount,
	$lcp_amount,
	$downpayment_amount,
	$amortization_amount,
	$amort_diff,
	$months_paid,
	$amort_delay,
	$adj_rate,
	$opportunity_cost,
	$amount_to_be_paid,
	$remarks,
	$category_id, //Added by Albert
	$profit_margin,
	$term_mode_type,
	$type,
	$rebate_if_adv_pay = 0,
	$sales_adjustment = 0 //Added by Albert 09/27/2022

) {
	if ($installmentplcy_id == 0) {
		$firstdue_date = "0000-00-00";
		$maturity_date = "0000-00-00";
	} else {
		$firstdue_date = date2sql($firstdue_date);
		$maturity_date = date2sql($maturity_date);
	}
	$sql = "INSERT INTO " . TB_PREF . "debtor_term_modification (
		trans_no,
		debtor_no,
		invoice_ref_no,
		delivery_ref_no,
		term_mod_date,
		installmentplcy_id,
		months_term,
		rebate,
		financing_rate,
		firstdue_date,
		maturity_date,
		outstanding_ar_amount,
		ar_amount,
		lcp_amount,
		downpayment_amount,
		amortization_amount,
		amort_diff,
		months_paid,
		amort_delay,
		adj_rate,
		opportunity_cost,
		amount_to_be_paid,
		remarks,
		category_id,
		profit_margin,
		term_mode_type,
		type,
		adv_payment_rebate,
		sales_adjustment
		) VALUES (
		" . db_escape($trans_no) . ",
		" . db_escape($debtor_no) . ",
		" . db_escape($invoice_ref_no) . ",
		" . db_escape($delivery_ref_no) . ",
		'" . date2sql($term_mod_date) . "',
		$installmentplcy_id,
		$months_term,
		$rebate,
		$financing_rate,
		'$firstdue_date',
		'$maturity_date',
		$outstanding_ar_amount,
		$ar_amount,
		$lcp_amount,
		$downpayment_amount,
		$amortization_amount,
		$amort_diff,
		$months_paid,
		$amort_delay,
		$adj_rate,
		$opportunity_cost,
		$amount_to_be_paid,
		" . db_escape($remarks) . ",
		$category_id,
		$profit_margin,
		" . db_escape($term_mode_type).",
		$type,
		" .db_escape($rebate_if_adv_pay). ",
		" .db_escape($sales_adjustment). ")";
	db_query($sql, "The debtor loans record could not be inserted");
}
/* */

