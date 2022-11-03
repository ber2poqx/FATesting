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
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/inventory.inc");
include_once($path_to_root . "/includes/db/allocations_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_order_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_credit_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_invoice_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_invoice_repo_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_delivery_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_types_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_points_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_groups_db.inc");
include_once($path_to_root . "/sales/includes/db/recurrent_invoices_db.inc");
include_once($path_to_root . "/sales/includes/db/custalloc_db.inc");
include_once($path_to_root . "/sales/includes/db/cust_trans_db.inc");
include_once($path_to_root . "/sales/includes/db/cust_trans_details_db.inc");
include_once($path_to_root . "/sales/includes/db/payment_db.inc");
include_once($path_to_root . "/sales/includes/db/branches_db.inc");
include_once($path_to_root . "/sales/includes/db/customers_db.inc");

include_once($path_to_root . "/sales/includes/db/sales_invoice_ob_db.inc"); // Added by Ronelle 5/12/2021
include_once($path_to_root . "/sales/includes/db/sales_return_db.inc"); // Added by Ronelle 6/1/2021
include_once($path_to_root . "/sales/includes/db/sales_term_mod_db.inc"); // Added by Ronelle 8/19/2021
include_once($path_to_root . "/sales/includes/db/sales_restructured_db.inc"); // Added by Albert 12/08/2022

include_once($path_to_root . "/lending/includes/db/customers_payment_db.inc"); //Added by spyrax10 7 April 2022
//----------------------------------------------------------------------------------------
// add a debtor-related gl transaction
// $date_ is display date (non-sql)
// $amount is in CUSTOMER'S currency

function add_gl_trans_customer(
	$type,
	$type_no,
	$date_,
	$account,
	$dimension,
	$dimension2,
	$amount,
	$customer_id,
	$err_msg = "",
	$rate = 0,
	$mcode = null,
	$master_file = null,
	$hocbc = 0,
	$loan_trans_no = 0
) {
	if ($err_msg == "")
		$err_msg = "The customer GL transaction could not be inserted";
	if ($loan_trans_no == 0){//modified by Albert
		if (
			$type == ST_SALESINVOICE
			|| $type == ST_SALESINVOICEREPO
			|| $type == ST_SALESRETURN
			|| $type == ST_SITERMMOD
			|| $type == ST_RESTRUCTURED
			|| $type == ST_ARINVCINSTLITM //for ar lending
		) {
			$loan_trans_no = $type_no;
		}
	}

	//Added by spyrax10 28 Feb 2022
	if (($type == ST_SALESRETURN || $type == ST_SALESINVOICE || $type == ST_RESTRUCTURED) && $account == 2151) {
		$mcode = get_company_value(0, 'branch_code');
		$master_file = get_company_value(0, 'name');
	}
	//

	return add_gl_trans(
		$type,
		$type_no,
		$date_,
		$account,
		$dimension,
		$dimension2,
		"",
		$amount,
		get_customer_currency($customer_id),
		PT_CUSTOMER,
		$customer_id,
		$err_msg,
		$rate,
		$mcode,
		$master_file,
		$hocbc,
		$loan_trans_no
	);
}

//----------------------------------------------------------------------------------------

function get_calculated_price($stock_id, $add_pct)
{
	$avg = get_unit_cost($stock_id);
	if ($avg == 0)
		return 0;
	return round2($avg * (1 + $add_pct / 100), user_price_dec());
}

function round_to_nearest($price, $round_to)
{
	if ($price == 0)
		return 0;
	$pow = pow(10, user_price_dec());
	if ($pow >= $round_to)
		$mod = ($pow % $round_to);
	else
		$mod = ($round_to % $pow);
	if ($mod != 0)
		$price = ceil($price) - ($pow - $round_to) / $pow;
	else
		$price = ceil($price * ($pow / $round_to)) / ($pow / $round_to);
	return $price;
}

function get_price($stock_id, $currency, $sales_type_id, $factor = null, $date = null)
{
	if ($date == null)
		$date = new_doc_date();

	if ($factor === null) {
		$myrow = get_sales_type($sales_type_id);
		$factor = $myrow['factor'];
	}

	$add_pct = get_company_pref('add_pct');
	$base_id = get_base_sales_type();
	$home_curr = get_company_currency();
	//	AND (sales_type_id = $sales_type_id	OR sales_type_id = $base_id)
	$sql = "SELECT price, curr_abrev, sales_type_id
		FROM " . TB_PREF . "prices
		WHERE stock_id = " . db_escape($stock_id) . "
			AND (curr_abrev = " . db_escape($currency) . " OR curr_abrev = " . db_escape($home_curr) . ")";

	$result = db_query($sql, "There was a problem retrieving the pricing information for the part $stock_id for customer");
	$num_rows = db_num_rows($result);
	$rate = round2(
		get_exchange_rate_from_home_currency($currency, $date),
		user_exrate_dec()
	);
	$round_to = get_company_pref('round_to');
	$prices = array();
	while ($myrow = db_fetch($result)) {
		$prices[$myrow['sales_type_id']][$myrow['curr_abrev']] = $myrow['price'];
	}
	$price = false;
	if (isset($prices[$sales_type_id][$currency])) {
		$price = $prices[$sales_type_id][$currency];
	} elseif (isset($prices[$base_id][$currency])) {
		$price = $prices[$base_id][$currency] * $factor;
	} elseif (isset($prices[$sales_type_id][$home_curr])) {
		$price = $prices[$sales_type_id][$home_curr] / $rate;
	} elseif (isset($prices[$base_id][$home_curr])) {
		$price = $prices[$base_id][$home_curr] * $factor / $rate;
	} elseif ($num_rows == 0 && $add_pct != -1) {
		$price = get_calculated_price($stock_id, $add_pct);
		if ($currency != $home_curr)
			$price /= $rate;
		if ($factor != 0)
			$price *= $factor;
	}
	if ($price === false)
		return 0;
	elseif ($round_to != 1)
		return round_to_nearest($price, $round_to);
	else
		return round2($price, user_price_dec());
}
//----------------------------------------------------------------------------------------

//-----------------------Robert Added For Cash Price Report------------------------------//
function get_cash_price($stock_id, $currency, $scash_type_id, $factor = null, $date = null)
{
	if ($date == null)
		$date = new_doc_date();

	if ($factor === null) {
		$myrow = get_cash_pricing_type($scash_type_id);
		$factor = $myrow['factor'];
	}

	$add_pct = get_company_pref('add_pct');
	$base_id = get_base_sales_type();
	$home_curr = get_company_currency();
	//	AND (sales_type_id = $sales_type_id	OR sales_type_id = $base_id)
	$sql = "SELECT price, curr_abrev, scash_type_id
		FROM " . TB_PREF . "cash_prices
		WHERE stock_id = " . db_escape($stock_id) . "
			AND (curr_abrev = " . db_escape($currency) . " OR curr_abrev = " . db_escape($home_curr) . ")";

	$result = db_query($sql, "There was a problem retrieving the pricing information for the part $stock_id for customer");
	$num_rows = db_num_rows($result);
	$rate = round2(
		get_exchange_rate_from_home_currency($currency, $date),
		user_exrate_dec()
	);
	$round_to = get_company_pref('round_to');
	$prices = array();
	while ($myrow = db_fetch($result)) {
		$prices[$myrow['scash_type_id']][$myrow['curr_abrev']] = $myrow['price'];
	}
	$price = false;
	if (isset($prices[$scash_type_id][$currency])) {
		$price = $prices[$scash_type_id][$currency];
	} elseif (isset($prices[$base_id][$currency])) {
		$price = $prices[$base_id][$currency] * $factor;
	} elseif (isset($prices[$scash_type_id][$home_curr])) {
		$price = $prices[$scash_type_id][$home_curr] / $rate;
	} elseif (isset($prices[$base_id][$home_curr])) {
		$price = $prices[$base_id][$home_curr] * $factor / $rate;
	} elseif ($num_rows == 0 && $add_pct != -1) {
		$price = get_calculated_price($stock_id, $add_pct);
		if ($currency != $home_curr)
			$price /= $rate;
		if ($factor != 0)
			$price *= $factor;
	}
	if ($price === false)
		return 0;
	elseif ($round_to != 1)
		return round_to_nearest($price, $round_to);
	else
		return round2($price, user_price_dec());
}
//-------------------------------------------------------------------------//

//-----------------------Robert Added For SRP Price Report------------------------------//
function get_srp_price($stock_id, $currency, $srptype_id, $factor = null, $date = null)
{
	if ($date == null)
		$date = new_doc_date();

	if ($factor === null) {
		$myrow = get_srp_type($srptype_id);
		$factor = $myrow['factor'];
	}

	$add_pct = get_company_pref('add_pct');
	$base_id = get_base_sales_type();
	$home_curr = get_company_currency();
	//	AND (sales_type_id = $sales_type_id	OR sales_type_id = $base_id)
	$sql = "SELECT standard_cost, curr_abrev, srptype_id
		FROM " . TB_PREF . "item_srp
		WHERE stock_id = " . db_escape($stock_id) . "
			AND (curr_abrev = " . db_escape($currency) . " OR curr_abrev = " . db_escape($home_curr) . ")";

	$result = db_query($sql, "There was a problem retrieving the pricing information for the part $stock_id for customer");
	$num_rows = db_num_rows($result);
	$rate = round2(
		get_exchange_rate_from_home_currency($currency, $date),
		user_exrate_dec()
	);
	$round_to = get_company_pref('round_to');
	$prices = array();
	while ($myrow = db_fetch($result)) {
		$prices[$myrow['srptype_id']][$myrow['curr_abrev']] = $myrow['standard_cost'];
	}
	$price = false;
	if (isset($prices[$srptype_id][$currency])) {
		$price = $prices[$srptype_id][$currency];
	} elseif (isset($prices[$base_id][$currency])) {
		$price = $prices[$base_id][$currency] * $factor;
	} elseif (isset($prices[$srptype_id][$home_curr])) {
		$price = $prices[$srptype_id][$home_curr] / $rate;
	} elseif (isset($prices[$base_id][$home_curr])) {
		$price = $prices[$base_id][$home_curr] * $factor / $rate;
	} elseif ($num_rows == 0 && $add_pct != -1) {
		$price = get_calculated_price($stock_id, $add_pct);
		if ($currency != $home_curr)
			$price /= $rate;
		if ($factor != 0)
			$price *= $factor;
	}
	if ($price === false)
		return 0;
	elseif ($round_to != 1)
		return round_to_nearest($price, $round_to);
	else
		return round2($price, user_price_dec());
}
//-------------------------------------------------------------------------//

//-----------------------Robert Added For System Cost Report------------------------------//
function get_systemcost_price($stock_id, $cost_type_id, $factor = null, $date = null)
{
	if ($date == null)
		$date = new_doc_date();

	if ($factor === null) {
		$myrow = get_sytem_cost_pricing_type($cost_type_id);
		$factor = $myrow['factor'];
	}

	$add_pct = get_company_pref('add_pct');
	$base_id = get_base_sales_type();
	$home_curr = get_company_currency();
	//	AND (sales_type_id = $sales_type_id	OR sales_type_id = $base_id)
	$sql = "SELECT price, cost_type_id
		FROM " . TB_PREF . "purch_data
		WHERE stock_id = " . db_escape($stock_id);

	$result = db_query($sql, "There was a problem retrieving the pricing information for the part $stock_id for customer");
	$num_rows = db_num_rows($result);
	$round_to = get_company_pref('round_to');
	$prices = array();
	while ($myrow = db_fetch($result)) {
		$prices[$myrow['cost_type_id']] = $myrow['price'];
	}
	$price = false;
	if (isset($prices[$cost_type_id])) {
		$price = $prices[$cost_type_id];
	} elseif (isset($prices[$base_id])) {
		$price = $prices[$base_id] * $factor;
	} elseif (isset($prices[$cost_type_id][$home_curr])) {
		$price = $prices[$cost_type_id][$home_curr];
	} elseif (isset($prices[$base_id][$home_curr])) {
		$price = $prices[$base_id][$home_curr] * $factor;
	} elseif ($num_rows == 0 && $add_pct != -1) {
		$price = get_calculated_price($stock_id, $add_pct);
		if ($currency != $home_curr)
			$price /= $rate;
		if ($factor != 0)
			$price *= $factor;
	}
	if ($price === false)
		return 0;
	elseif ($round_to != 1)
		return round_to_nearest($price, $round_to);
	else
		return round2($price, user_price_dec());
}
//-------------------------------------------------------------------------//
//
//	Get price for given item or kit.
//  When $std==true price is calculated as a sum of all included stock items,
//	otherwise all prices set for kits and items are accepted.
//
function get_kit_price(
	$item_code,
	$currency,
	$sales_type_id,
	$factor = null,
	$date = null,
	$std = false
) {
	$kit_price = 0.00;
	if (!$std) {
		$kit_price = get_price(
			$item_code,
			$currency,
			$sales_type_id,
			$factor,
			$date
		);

		if ($kit_price !== 0) {
			return $kit_price;
		}
	}
	// no price for kit found, get total value of all items
	$kit = get_item_kit($item_code);

	while ($item = db_fetch($kit)) {
		if ($item['item_code'] != $item['stock_id']) {
			// foreign/kit code
			$kit_price += $item['quantity'] * get_kit_price(
				$item['stock_id'],
				$currency,
				$sales_type_id,
				$factor,
				$date,
				$std
			);
		} else {
			// stock item
			$kit_price += $item['quantity'] * get_price(
				$item['stock_id'],
				$currency,
				$sales_type_id,
				$factor,
				$date
			);
		}
	}
	return $kit_price;
}

//--------------------------------------------------------------------------------------------------
function update_parent_line($doc_type, $line_id, $qty_dispatched, $auto = false)
{
	$doc_type = get_parent_type($doc_type);

	$qty_dispatched = (float)$qty_dispatched;

	if ($doc_type == 0)
		return false;
	else {
		if ($doc_type == ST_SALESORDER || $doc_type == ST_SALESQUOTE) {
			$sql = "UPDATE " . TB_PREF . "sales_order_details
				SET qty_sent = qty_sent + $qty_dispatched";
			if ($auto)
				$sql .= ", quantity = quantity + $qty_dispatched";
			$sql .= " WHERE id=" . db_escape($line_id);
		} else
			$sql = "UPDATE " . TB_PREF . "debtor_trans_details
				SET qty_done = qty_done + $qty_dispatched
				WHERE id=" . db_escape($line_id);
	}
	db_query($sql, "The parent document detail record could not be updated");
	return true;
}

//--------------------------------------------------------------------------------------------------
// find inventory location for given transaction
//
function get_location(&$cart)
{
	$sql = "SELECT location.*
		FROM " . TB_PREF . "stock_moves move,"
		. TB_PREF . "locations location" .
		" WHERE type=" . db_escape($cart->trans_type) .
		" AND trans_no=" . key($cart->trans_no) .
		" AND qty!=0 " .
		" AND location.loc_code=move.loc_code";

	$result = db_query($sql, 'Retreiving inventory location');

	if (db_num_rows($result)) {
		return db_fetch($result);
	}
	return null;
}
//--------------------------------------------------------------------------------------------------
// Generic read debtor transaction into cart
//
//	$trans_no - array of trans nums; special case trans_no==0 - new doc
//
function read_sales_trans($doc_type, $trans_no, &$cart)
{
	if (!is_array($trans_no) && $trans_no)
		$trans_no = array($trans_no);

	$cart->trans_type = $doc_type;
	if (!$trans_no) { // new document
		$cart->trans_no = $trans_no;
	} else {
		// read header data from first document
		$myrow = get_customer_trans($trans_no[0], $doc_type);
		if (count_array($trans_no) > 1)
			$cart->trans_no = get_customer_trans_version($doc_type, $trans_no);
		else
			$cart->trans_no = array($trans_no[0] => $myrow["version"]);

		if ($doc_type != ST_SALESRETURN) {
			$cart->set_sales_type($myrow["tpe"], $myrow["sales_type"], $myrow["tax_included"], 0);
		}

		$cart->set_customer(
			$myrow["debtor_no"],
			$myrow["DebtorName"],
			$myrow["curr_code"],
			$doc_type != ST_SALESRETURN ? $myrow["discount"] : 0,
			$myrow["payment_terms"]
		);

		$cart->set_branch(
			$myrow["branch_code"],
			$doc_type != ST_SALESRETURN ? $myrow["tax_group_id"] : 0,
			$doc_type != ST_SALESRETURN ? $myrow["tax_group_name"] : 0
		);

		$cart->reference = $myrow["reference"];
		$cart->prepaid = $myrow["prepaid"];
		$cart->order_no = $myrow["order_"];
		$cart->due_date = sql2date($myrow["due_date"]);
		$cart->document_date = sql2date($myrow["tran_date"]);
		$cart->dimension_id = $myrow['dimension_id']; // added 2.1 Joe Hunt 2008-11-12
		$cart->dimension2_id = $myrow['dimension2_id'];
		$cart->Comments = '';
		$cart->alloc = $myrow["alloc"];
		if ($doc_type == ST_SALESINVOICE || $doc_type == ST_SALESINVOICEREPO  || $doc_type == 70) { // modified by albert doc_type = 70 is for Lending 
			$debtor_loans = get_debtor_loans($trans_no[0], $doc_type == ST_SALESINVOICEREPO ? "repo" : "new");
			$cart->warranty_code = $debtor_loans["warranty_code"];
			$cart->fsc_series = $debtor_loans["fsc_series"];

			$cart->lcp_amount = $debtor_loans["lcp_amount"];
			$cart->dp_amount = $debtor_loans["downpayment_amount"];
			$cart->amortization = $debtor_loans["amortization_amount"];
			$cart->ar_amount = $debtor_loans["ar_amount"];
			$cart->first_due_date = date('m/d/Y', strtotime($debtor_loans["firstdue_date"]));
			$cart->maturity_date = date('m/d/Y', strtotime($debtor_loans["maturity_date"]));

			$cart->payment_policy = $debtor_loans["installmentplcy_id"];
			$cart->months_term = $debtor_loans["months_term"];
			$cart->rebate = $debtor_loans["rebate"];
			$cart->financing_rate = $debtor_loans["financing_rate"];
			$cart->category_id = $debtor_loans["category_id"];
			$cart->Location = $debtor_loans["orig_branch_code"];
			$cart->dr_ref = $debtor_loans["delivery_ref_no"];
			$cart->document_ref = $debtor_loans["invoice_ref_no"];
			$cart->repo_type = $debtor_loans["invoice_type"];
		}

		if ($doc_type == ST_SALESRETURN) {
			$sales_return = get_sales_return_replacement($trans_no[0]);
			$cart->category_id = $sales_return["category_id"];
			$cart->repo_type = $sales_return["sr_item_type"];
		}

		foreach ($trans_no as $trans) {
			$cart->Comments .= get_comments_string($doc_type, $trans);
		}

		// FIX this should be calculated sum() for multiply parents

		if ($doc_type != ST_SALESRETURN) {
			$cart->set_delivery(
				$myrow["ship_via"],
				$myrow["br_name"],
				$myrow["br_address"],
				$myrow["ov_freight"]
			);
		}

		$location = 0;
		$myrow = get_location($cart); // find location from movement

		if ($myrow != null) {
			$cart->set_location($myrow['loc_code'], $myrow['location_name']);
		}

		$result = get_customer_trans_details($doc_type, $trans_no);
		if (db_num_rows($result) > 0) {

			for ($line_no = 0; $myrow = db_fetch($result); $line_no++) {
				if ($myrow["item_type"] == "Regular") {
					$cart->line_items[$line_no] = new line_details(
						$myrow["stock_id"],
						$myrow["quantity"],
						$myrow["unit_price"],
						$myrow["discount_percent"],
						$myrow["qty_done"],
						$myrow["standard_cost"],
						$myrow["StockDescription"],
						$myrow["id"],
						$myrow["debtor_trans_no"],
						@$myrow["src_id"],
						$myrow["lot_no"],
						$myrow["chassis_no"],
						$myrow["color_code"],
						$myrow["item_type"],
						$myrow["discount1"],
						$myrow["discount2"],
						0, 	//lcp_price
						$myrow["smi"],
						$myrow["incentives"]
					);
				}
			}
		}
		$cart->prepayments = get_payments_for($trans_no, $doc_type, $myrow["debtor_no"]);
	} // !newdoc

	return true;
}
//----------------------------------------------------------------------------------------

function get_sales_child_lines($trans_type, $trans_no, $lines = true)
{
	if (!($ctype = get_child_type($trans_type)))
		return false;
	if (!is_array($trans_no)) {
		$trans_no = array($trans_no);
	}

	$par_tbl = $trans_type == ST_SALESORDER ? "sales_order_details" : "debtor_trans_details";
	$par_no = $trans_type == ST_SALESORDER ? "trans.order_no" : "trans.debtor_trans_no";

	foreach ($trans_no as $n => $trans) {
		$trans_no[$n] = db_escape($trans);
	}
	$sql = "SELECT child.*
			FROM
				" . TB_PREF . "debtor_trans_details child
			LEFT JOIN " . TB_PREF . "$par_tbl trans 
				ON child.src_id=trans.id AND child.debtor_trans_type=$ctype
			WHERE $par_no IN(" . implode(',', array_values($trans_no)) . ")";

	if (!$lines)
		$sql .= " GROUP BY child.debtor_trans_no";

	$sql .= " ORDER BY child.debtor_trans_no";

	return db_query($sql, "can't retrieve child trans");
}

function get_sales_child_numbers($trans_type, $trans_no)
{
	$trans = array();
	$res = get_sales_child_lines($trans_type, $trans_no, false);
	while ($line = db_fetch($res)) {
		$trans[] = $line['debtor_trans_no'];
	}
	return $trans;
}

function get_sales_parent_lines($trans_type, $trans_no, $lines = true)
{
	//modified by Albert 10/16/2021
	$partype = get_parent_type($trans_type) == null ? ST_SALESINVOICEREPO : get_parent_type($trans_type);
	if (!$partype)
		return false;

	$par_tbl = $partype == ST_SALESORDER ? "sales_order_details" : "debtor_trans_details";
	$par_no = $partype == ST_SALESORDER ? "parent.order_no" : "parent.debtor_trans_no";
	$sql = "SELECT parent.*
			FROM
				" . TB_PREF . "$par_tbl parent
			LEFT JOIN " . TB_PREF . "debtor_trans_details trans 
				ON trans.src_id=parent.id
			WHERE
				trans.debtor_trans_type=" . db_escape($trans_type)
		. " AND trans.debtor_trans_no=" . db_escape($trans_no);
	if (!$lines)
		$sql .= " GROUP BY $par_no";

	$sql .= " ORDER BY $par_no";

	return db_query($sql, "can't retrieve child trans");
}

function get_sales_parent_numbers($trans_type, $trans_no)
{
	$trans = array();
	$res = get_sales_parent_lines($trans_type, $trans_no, false);
	while ($line = db_fetch($res))
		$trans[] = $line[$trans_type == ST_CUSTDELIVERY ? 'order_no' : 'debtor_trans_no'];
	return $trans;
}

//----------------------------------------------------------------------------------------

function get_sales_child_documents($trans_type, $trans_no)
{
	// FIXME -  credit notes retrieved here should be those linked to invoices containing 
	// at least one line from related invoice
	
	//Added by spyrax10 11 May 2022
	set_global_connection();

	if (!count_array($trans_no))
		return false;
	$childs = get_sales_child_numbers($trans_type, $trans_no, false);
	if (!count_array($childs))
		return false;

	$sql = "SELECT * FROM " . TB_PREF . "debtor_trans
		WHERE type=" . get_child_type($trans_type) . " AND trans_no IN(" . implode(',', array_values($childs)) . ")";

	return db_query($sql, "The related credit notes could not be retreived");
}

/* Added by Ronelle 3/14/2021 Retrieving Sales Invoices */
function get_sales_invoices($trans_no = '', $debtor_no = '', $category_id = '', $payment_terms = -1, $opening_balances = 0, 
	$status = '', $include_repo = false)
{
	//Modified by spyrax10 21-Oct-2021
	$sql = "SELECT
				a.trans_no,
				a.status,
				a.reference,
				b.name,
				CASE
                	WHEN a.payment_terms = 0
                    	THEN 'CASH'
                    ELSE
                    	'INSTALLMENT'
                END as payment_type,
				c.invoice_type,
				(SELECT sq1.description FROM " . TB_PREF . "stock_category sq1 WHERE sq1.category_id = c.category_id) as category,
				a.tran_date,
				c.payment_location,
				c.months_term,
				-- a.due_date,
				-- (SELECT t1.trans_no FROM " . TB_PREF . "debtor_trans t1 WHERE t1.type = 13 AND t1.order_ = a.order_) as dr_no,
                a.order_ as so_no,
				c.ar_amount,
				c.lcp_amount + DIS.discount AS lcp_amount,
				c.downpayment_amount,
				c.amortization_amount,
				a.debtor_no, 
				a.alloc,
				a.type, 
				cust.amt,
				c.lcp_amount as lcp_2,
				a.order_,
				a.return_status,
				a.restructured_status, a.ov_amount, c.ref_no, c.category_id, b.debtor_ref

			FROM " . TB_PREF . "debtor_trans a
				INNER JOIN " . TB_PREF . "debtors_master b ON b.debtor_no= a.debtor_no
				INNER JOIN " . TB_PREF . "debtor_loans c ON c.trans_no = a.trans_no and  c.reference = a.reference 

				LEFT JOIN (
					SELECT X.id, X.trans_no_to, X.trans_type_to, X.person_id, SUM(X.amt) AS amt
					FROM " . TB_PREF . "cust_allocations X
					GROUP BY X.trans_no_to, X.trans_type_to, X.person_id
				) cust ON a.trans_no = cust.trans_no_to AND a.type = cust.trans_type_to AND cust.person_id = a.debtor_no 

				LEFT JOIN (
					SELECT A.order_no, SUM(B.discount1 + B.discount2) AS discount
					FROM " . TB_PREF . "sales_orders A 
					INNER JOIN " . TB_PREF . "sales_order_details B ON A.order_no = B.order_no 
					GROUP BY B.order_no
				) DIS ON a.order_ = DIS.order_no
			
			WHERE
				a.type = " . ST_SALESINVOICE . " AND a.opening_balances = " . db_escape($opening_balances) . "";

	//Added by spyrax10
	if ($include_repo) {
		$sql .= " AND a.type IN (" . ST_SALESINVOICE . ", " . ST_SALESINVOICEREPO . ")";
	}
	else {
		$sql .= " AND a.type = " . ST_SALESINVOICE . "";
	}

	if ($trans_no != '') {
		$sql .= " AND a.trans_no = " . db_escape($trans_no) . " OR a.reference = " . db_escape($trans_no);
	}

	if ($debtor_no != '') {
		$sql .= " AND a.debtor_no = " . db_escape($debtor_no);
	}

	if ($status != '') {
		$sql .= " AND a.status = " .db_escape($status);
	}

	//Added by Albert
	if ($category_id != -1) {
		$sql .= " AND c.category_id = " . db_escape($category_id);
	}
	if ($payment_terms != -1) {
		if ($payment_terms == 0)
			$sql .= " AND a.payment_terms = 0 ";
		else {
			$sql .= " AND a.payment_terms > 0 ";
		}
	}

	$sql .= " ORDER BY a.tran_date DESC, a.trans_no DESC ";
	return $sql;
}
// Added by Albert
function get_sales_repo_invoices($trans_no = '', $debtor_no = '', $category_id = '', $payment_terms, $opening_balances = 0)
{
	$sql = "SELECT
				a.trans_no,
				a.status,
				a.reference,
				b.name,
				CASE
                	WHEN a.payment_terms = 0
                    	THEN 'CASH'
                    ELSE
                    	'INSTALLMENT'
                END as payment_type,
				c.invoice_type,
				(SELECT sq1.description FROM " . TB_PREF . "stock_category sq1 WHERE sq1.category_id = c.category_id) as category,
				a.tran_date,
				c.payment_location,
				c.months_term,
				-- a.due_date,
				-- (SELECT t1.trans_no FROM " . TB_PREF . "debtor_trans t1 WHERE t1.type = 13 AND t1.order_ = a.order_) as dr_no,
                a.order_ as so_no,
				c.ar_amount,
				c.lcp_amount + DIS.discount AS lcp_amount,
				c.downpayment_amount,
				c.amortization_amount,
				a.debtor_no, 
				a.alloc,
				a.type, 
				cust.amt,
				c.lcp_amount as lcp_2,
				a.order_,
				a.return_status,
				a.restructured_status

			FROM " . TB_PREF . "debtor_trans a
				INNER JOIN " . TB_PREF . "debtors_master b ON b.debtor_no= a.debtor_no
				INNER JOIN " . TB_PREF . "debtor_loans c ON c.trans_no = a.trans_no and  c.reference = a.reference 

				LEFT JOIN (
					SELECT X.id, X.trans_no_to, X.trans_type_to, X.person_id, SUM(X.amt) AS amt
					FROM " . TB_PREF . "cust_allocations X
					GROUP BY X.trans_no_to, X.trans_type_to, X.person_id
				) cust ON a.trans_no = cust.trans_no_to AND a.type = cust.trans_type_to AND cust.person_id = a.debtor_no 

				LEFT JOIN (
					SELECT A.order_no, SUM(B.discount1 + B.discount2) AS discount
					FROM " . TB_PREF . "sales_orders A 
					INNER JOIN " . TB_PREF . "sales_order_details B ON A.order_no = B.order_no 
					GROUP BY B.order_no
				) DIS ON a.order_ = DIS.order_no
			WHERE
				a.type = " . ST_SALESINVOICEREPO . " AND a.opening_balances = " . db_escape($opening_balances) . "";

	//Added by spyrax10
	if ($trans_no != '') {
		$sql .= " AND a.trans_no = " . db_escape($trans_no) . " OR a.reference = " . db_escape($trans_no);
	}
	if ($debtor_no != '') {
		$sql .= " AND a.debtor_no = " . db_escape($debtor_no);
	}
	//Added by Albert
	if ($category_id != -1) {
		$sql .= " AND c.category_id = " . db_escape($category_id);
	}
	if ($payment_terms != -1) {
		if ($payment_terms == 0)
			$sql .= " AND a.payment_terms = 0 ";
		else {
			$sql .= " AND a.payment_terms > 0 ";
		}
	}

	$sql .= " ORDER BY a.tran_date DESC, a.trans_no DESC ";
	return $sql;
}

/* */
/*albert*/
function get_sales_invoices_aropening($trans_no = 0, $debtor_no, $opening_balances = 0)
{
	$sql = "SELECT
				a.trans_no,
				a.status,
				a.reference,
				b.name,
				CASE
                	WHEN a.payment_terms = 0
                    	THEN 'CASH'
                    ELSE
                    	'INSTALLMENT'
                END as payment_type,
				c.invoice_type,
				a.tran_date,
				c.months_term,
				-- a.due_date,
				-- (SELECT t1.trans_no FROM " . TB_PREF . "debtor_trans t1 WHERE t1.type = 13 AND t1.order_ = a.order_) as dr_no,
                a.order_ as so_no,
				c.ar_amount,
				c.lcp_amount,
				c.downpayment_amount,
				c.amortization_amount,
				a.return_status, a.type,
				a.restructured_status
			FROM
				" . TB_PREF . "debtor_trans a
			INNER JOIN
				" . TB_PREF . "debtors_master b
			ON
				b.debtor_no= a.debtor_no
			INNER JOIN
				" . TB_PREF . "debtor_loans c
			ON
				c.trans_no = a.trans_no
			WHERE
				(a.type = 10 OR a.type = 57 ) AND a.opening_balances = " . db_escape($opening_balances);
			
	if ($trans_no != '') {
		$sql .= " AND a.trans_no = " . db_escape($trans_no) . " OR a.reference = " . db_escape($trans_no);
	}
	if ($debtor_no != '') {
		$sql .= " AND a.debtor_no = " . db_escape($debtor_no);
	}
	$sql .= " ORDER BY a.tran_date DESC, a.trans_no DESC ";

	return $sql;
}
/* */
/* Added by Ronelle 6/1/2021 Retrieving Sales Return Replacement */
//Modified by spyrax10 2021-12-23
function get_sales_return_replacement($trans_no = 0, $repo = "new")
{
	//modified by albert 05/10/2022
	set_global_connection();
	
	$sql = "SELECT
				a.trans_no,
				/*CASE 
					WHEN c.status = 'Close' THEN 'CLOSED'
					WHEN c.status = 'Closed' THEN 'CLOSED'
				ELSE 'OPEN' END,*/
				b.reference,
				b.debtor_no,
				b.tran_date,
				a.trans_ref,
				a.trans_no_ref,
				a.trans_type_ref,
				a.category_id,
				a.sr_item_type,
				a.payment_type,
				a.total_payable,
				a.total_receivable,
				a.total_prev_lcp,
				a.total_new_lcp,
				a.total_prev_cost,
				a.total_new_cost,
				c.ov_amount - c.alloc AS ar_balance,
				a.remarks,
				DM.name, BR.br_name, BR.branch_ref

			FROM " . TB_PREF . "sales_return_replacement a 
			INNER JOIN " . TB_PREF . "debtor_trans b ON b.trans_no = a.trans_no
			LEFT JOIN " . TB_PREF . "debtor_trans c ON b.reference = c.reference AND b.debtor_no = c.debtor_no
			LEFT JOIN " . TB_PREF . "debtors_master DM ON b.debtor_no = DM.debtor_no
			LEFT JOIN " . TB_PREF . "cust_branch BR ON DM.debtor_no = BR.debtor_no

			WHERE b.type = 55";
	if ($trans_no == 0) {
		$sql .= " AND a.sr_item_type =" . db_escape($repo);
	}
	if ($trans_no != 0) {
		$sql .= " AND a.trans_no=$trans_no";
		$result = db_query($sql, 'cannot retrieve sales return replacement');
		$num = db_num_rows($result);
		if ($num > 1) {
			display_warning("You have duplicate document in database: (type:Sales Return, number:$trans_no).");
		} else if ($num == 1) {
			return db_fetch($result);
		} else
			display_warning("You have missing or invalid sales document in database (type:Sales Return, number:$trans_no).");
	}
	$sql .= " ORDER BY a.trans_no DESC";
	return $sql;
}

//Added by spyrax10
//modified by Albert
function get_SI_date($order_no)
{
	$sql = "SELECT DATE_FORMAT(A.tran_date, '%m/%d/%Y')
		FROM " . TB_PREF . ".debtor_trans A
		WHERE (A.type = 10 or A.type = 57) AND A.order_=" . db_escape($order_no);

	$result = db_query($sql, "Can't get SI date! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}
//Added by Albert 10/25/2021
function get_account_specialist_remarks($order_no)
{
	$sql = "SELECT a.account_specialist_remarks
		FROM " . TB_PREF . "sales_orders a
		WHERE a.order_no = " . db_escape($order_no);

	$result = db_query($sql, "Can't get Account Specialist Approval! (Albert)");
	$row = db_fetch_row($result);
	return $row[0];
}

//Added by Prog6 (12/10/2021)
function get_all_sale_target($year,$category,$type) 
{
    $sql = "SELECT 
				  trgt.`id`
				, trgt.`year`
				, trgt.`jan`
				, trgt.`feb`
				, trgt.`mar`
				, trgt.`apr`
				, trgt.`may`
				, trgt.`jun`
				, trgt.`jul`
				, trgt.`aug`
				, trgt.`sep`
				, trgt.`oct`
				, trgt.`nov`
				, trgt.`dece`
				, trgt.`type`
				, cat.description
			FROM " . TB_PREF . "sales_target trgt
				LEFT JOIN " . TB_PREF . "stock_category cat ON trgt.category_id = cat.category_id";	

	if($category == -1)
	{
		$sql .= " WHERE trgt.category_id > '-1'";		
	}
	else
		$sql .= " WHERE trgt.category_id = '$category'";


	if($type == -1)
	{
		$sql .= " AND trgt.type_id > '-1'";
	}
	else		
		$sql .= " AND trgt.type_id = '$type'";


	if($year != "" || $year <> NULL)
	{
		$sql .= " AND trgt.year = '$year'";
	}		
		
	

	$sql .= " ORDER BY trgt.year DESC";
    return $sql;
	return $sql_category = $category;
	return $sql_type = $type;
	return $sql_year = $year;
}


/* Added by Ronelle retrieve payment location from stock category table */
function get_payment_location_by_category($category_id)
{
	$sql = "SELECT payment_location FROM stock_category WHERE category_id=$category_id";
	$result = db_query($sql, "no result found.");
	$row = db_fetch_row($result);
	return $row[0];
}
/* */

//Added by spyrax10 6 Apr 2022
function get_customer_invoices($customer_id = '', $si_ref = '') {

	set_global_connection();

	$sql = "SELECT DT.*, DT.reference AS ar_ref, DTT.stock_id AS model, DM.debtor_ref, DT.debtor_no AS cust_id, 
			DTT.description as model_desc, SC.description AS stock_name, DTT.color_code AS color_code,
			CASE 
				WHEN DT.opening_balances = 1 THEN 'Sales Invoice - Opening Balance'
				WHEN DT.payment_terms > 0 THEN 'Sales Invoice - Installment'
				WHEN DL.invoice_type = 'repo' THEN 'Sales Invoice - Repo'
			END AS inv_type, DL.ar_amount, DL.profit_margin

		FROM " . TB_PREF . "debtor_trans DT
			INNER JOIN " . TB_PREF . "debtor_trans_details DTT ON DT.trans_no = DTT.debtor_trans_no
			INNER JOIN " . TB_PREF . "debtor_loans DL ON DT.trans_no = DL.trans_no 
				AND DT.debtor_no = DL.debtor_no
			INNER JOIN " . TB_PREF . "stock_category SC ON DL.category_id = SC.category_id
			INNER JOIN " . TB_PREF . "debtors_master DM ON DT.debtor_no = DM.debtor_no
			LEFT JOIN " . TB_PREF . "voided V ON DT.trans_no = V.id AND DT.type = V.type 
				AND V.void_status = 'Voided'
		WHERE ISNULL(V.void_id) AND DT.type = " . ST_SALESINVOICE . " AND DT.payment_terms > 0";

	if ($customer_id != '') {
		$sql .= " AND DT.debtor_no = " .db_escape($customer_id);
	}

	if ($si_ref != '') {
		$sql .= " AND DT.reference = " .db_escape($si_ref);
	}

	return db_query($sql, _("Cant get Customer's Invoice/s!"));
}

function insert_customer_alloc($date_paid, $je_no, $trans_no, $trans_type, $debtor_no, 
	$je_reference = '', $total = 0) {

	set_global_connection();

	$date = date2sql($date_paid);
	$alloc_4_unpaid = $alloc_4_partial = 0;

	$invoice_no = write_customer_trans(
		ST_JOURNAL,
		0,
		$debtor_no,
		$debtor_no,
		$date_paid,
		$je_reference,
		$total > 0 ? abs($total) : 0,
		0, 0, 0, 0, 0, 0, 0, $date_paid, 
		$total < 0 ? abs($total) : 0
	);

	$total_alloc = abs($total);

	if ($invoice_no) {

		if ($total < 0) {

			$remain_month = debtor_remaining_month($trans_no, $trans_type, $debtor_no);
			
			if ($remain_month > 0) {

				add_cust_allocation(
					abs($total), 
					ST_JOURNAL, 
					$je_no, 
					$trans_type, 
					$trans_no, 
					$debtor_no,  
					$date_paid
				);
		
				update_debtor_trans_allocation( 
					$trans_type, 
					$trans_no, 
					$debtor_no
				);

				while ($total_alloc > 0) {

					$res = get_customer_loansched($trans_no, $trans_type, $debtor_no);

					while ($trans = db_fetch($res)) {

						if ($total_alloc == 0) {
							break;
						}

						if ($trans['status'] == 'unpaid') {

							if ($total_alloc >= $trans['principal_due']) {
								$alloc_4_unpaid = $trans['principal_due'];
							}
							else if ($total_alloc < $trans['principal_due']) {
								$alloc_4_unpaid = $total_alloc;	
							}
							
							add_to_DLL(
								$trans_no,
								$debtor_no, 
								ST_JOURNAL,
								$trans_type,
								$date,
								$invoice_no,
								1,
								$trans['id'],
								$alloc_4_unpaid
							);

							$total_pay_month = get_payment_for_loanID($trans_no, $trans_type, $debtor_no, $trans['id']);
							$month_bal = $trans['principal_due'] - $total_pay_month;

							if ($month_bal == 0) {
								update_DLS_status($trans_no, $trans_type, $debtor_no, $trans['id'], 'paid');
							}
							else {
								update_DLS_status($trans_no, $trans_type, $debtor_no, $trans['id'], 'partial');
							}

							$total_alloc -= $alloc_4_unpaid;
							
						}
						else if ($trans['status'] == 'partial') {

							$total_pay_month = get_payment_for_loanID($trans_no, $trans_type, $debtor_no, $trans['id']);
							$month_bal = $trans['principal_due'] - $total_pay_month;

							if ($total_alloc >= $month_bal) {
								$alloc_4_partial = $month_bal;
							}
							else if ($total_alloc < $month_bal) {
								$alloc_4_partial = $total_alloc;	
							}

							add_to_DLL(
								$trans_no,
								$debtor_no, 
								ST_JOURNAL,
								$trans_type,
								$date,
								$invoice_no,
								1,
								$trans['id'],
								$alloc_4_partial
							);

							$total_alloc -= $alloc_4_partial;
						}

						if ($trans['principal_due'] - get_payment_for_loanID($trans_no, $trans_type, $debtor_no, $trans['id']) == 0) {
							update_DLS_status($trans_no, $trans_type, $debtor_no, $trans['id'], 'paid');
						}
						else {
							update_DLS_status($trans_no, $trans_type, $debtor_no, $trans['id'], 'partial');
						}

					} //End of while loop

				} //End of total alloc while loop
			}

		} //Adjustment Type (-)
		else {
			update_AR_amount($trans_no, $trans_type, $debtor_no, $total_alloc);
		} //Adjustment Type (+)
	}
}

function get_AR_adjusted_amount($debtor_no, $si_ref = '', $get_total = false) {

	set_global_connection();

	if ($get_total) {
		$sql = "SELECT SUM(ov_amount) AS ar_amount";
	}
	else {
		$sql = "SELECT *";
	}
	
	$sql .= " FROM " . TB_PREF . "debtor_trans DT
			INNER JOIN " . TB_PREF . "journal JE ON DT.reference = JE.reference 
		WHERE DT.type = " . ST_JOURNAL . " AND DT.ov_amount > 0 AND DT.alloc = 0";

	$sql .= " AND DT.debtor_no = " .db_escape($debtor_no) . 
		" AND JE.source_ref = " .db_escape($si_ref);

	$res =  db_query($sql, _("get_AR_adjusted_amount"));
	
	if ($get_total) {
		$row = db_fetch_row($res);
		return $row[0];
	}
	else {
		return $res;
	}
}

function default_SI_gl($trans_no, $type) {

	set_global_connection();

	$sql = "SELECT X.account, MAX(X.amount) FROM " . TB_PREF . "gl_trans X
	WHERE X.type_no = " .db_escape($trans_no) . 
	" AND X.type = " .db_escape($type);

	$res =  db_query($sql, _("default_SI_gl"));
	
	$row = db_fetch_row($res);
	return $row[0] != null ? $row[0] : '';
}

//