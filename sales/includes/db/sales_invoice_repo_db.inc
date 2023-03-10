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
//-----------------------------------------------------------------------------
//	Add or update Sales Invoice
//
function write_sales_invoice_repo(&$invoice, $opening_balances = 0, $warranty_code = "", $fsc_series = "")
{
	global $Refs;

	//Modified by spyrax10
	$trans_no = $invoice->trans_no;
	//

	if (is_array($trans_no))
		$trans_no = key($trans_no);

	$date_ = $invoice->document_date;
	$charge_shipping = $invoice->freight_cost;

	begin_transaction();

	hook_db_prewrite($invoice, ST_SALESINVOICEREPO);
	$company_data = get_company_prefs();

	$branch_data = get_branch_accounts($invoice->Branch);

	$customer = get_customer($invoice->customer_id);

	add_new_exchange_rate($customer['curr_code'], $date_, $invoice->ex_rate);

	// offer price values without freight costs
	$items_total = $invoice->get_items_total_dispatch();
	$freight_tax = $invoice->get_shipping_tax();

	if (!$invoice->is_prepaid())
		update_customer_trans_version(get_parent_type(ST_SALESINVOICEREPO), $invoice->src_docs);
	elseif (count($invoice->prepayments)) {	// partial invoice
		$last_payment = end($invoice->prepayments);
		$gl_date = sql2date($last_payment['tran_date']);
	} else {	// final invoice
		$gl_date = $invoice->document_date;
	}

	$cash_discount = 0;
	$c_discount1 = 0;
	$c_discount2 = 0;
	$mcode = $masterfile = ''; //Added by spyrax10

	$ov_gst = 0;
	$taxes = $invoice->get_taxes(); // all taxes with freight_tax
	$dec = user_price_dec();
	foreach ($taxes as $taxitem) {
		$taxitem['Value'] =  round2($taxitem['Value'], $dec);
		$ov_gst +=  $taxitem['Value'];
	}

	if ($invoice->tax_included == 0) {
		$items_added_tax = $ov_gst - $freight_tax;
		$freight_added_tax = $freight_tax;
	} else {
		$items_added_tax = 0;
		$freight_added_tax = 0;
	}

	/* Insert/update the debtor_trans */
	$sales_order = $invoice->order_no;
	if ($opening_balances == 1)
		$sales_order = 0;
	if (is_array($sales_order))
		$sales_order = $sales_order[0]; // assume all crucial SO data are same for every delivery

	if ($trans_no) {
		$allocs = get_payments_for($trans_no, ST_SALESINVOICEREPO, $invoice->customer_id);
		delete_comments(ST_SALESINVOICEREPO, $trans_no);
		void_gl_trans(ST_SALESINVOICEREPO, $trans_no, true);
		void_trans_tax_details(ST_SALESINVOICEREPO, $trans_no);
	} else
		$allocs = get_payments_for($invoice->order_no, ST_SALESORDER, $invoice->customer_id);

	if ($invoice->is_prepaid()) // selected prepayment is already in cart
	{
		$allocs = $invoice->prepayments;
		// values posted are reduced by prepaid_factor
		$prepaid_factor = $invoice->prep_amount / $invoice->get_trans_total();
	} else {
		$prepaid_factor = 1;
	}
	set_global_connection();
	// write_customer_trans have to be called after optional void_cust_allocations above
	$entry_amount = $invoice->payment_location == "Lending" ? $invoice->lcp_amount : $invoice->ar_amount;
	$outstanding_ar_amount = $entry_amount - $invoice->dp_amount;
	$invoice_no = write_customer_trans(
		ST_SALESINVOICEREPO,
		$trans_no,
		$invoice->customer_id,
		$invoice->Branch,
		$date_,
		$invoice->reference,
		$entry_amount,
		0,
		$items_added_tax,
		$invoice->freight_cost,
		$freight_added_tax,
		$invoice->sales_type,
		$sales_order,
		$invoice->ship_via,
		$invoice->due_date,
		0,
		0,
		$invoice->dimension_id,
		$invoice->dimension2_id,
		$invoice->payment,
		$invoice->tax_included,
		$invoice->prep_amount,
		$opening_balances
	);

	add_debtor_loans(
		$invoice_no,
		$invoice->customer_id,
		$invoice->reference,
		$invoice->dr_ref,
		$invoice->reference,
		$date_,
		$company_data["branch_code"],
		$invoice->invoice_type='repo',
		$invoice->payment_policy,
		$invoice->months_term,
		$invoice->rebate,
		$invoice->financing_rate,
		$invoice->first_due_date,
		$invoice->maturity_date,
		$outstanding_ar_amount,
		$entry_amount,
		$invoice->lcp_amount,
		$invoice->dp_amount,
		$invoice->amortization,
		$items_total,
		$invoice->category_id,
		$warranty_code,
		$fsc_series,
		$invoice->payment_location,
		$invoice->co_maker,
		$invoice->discount_dp_amount,
		$invoice->deferred_gross_profit,
		$invoice->profit_margin,
		$invoice->document_ref,//Added by spyrax10
		0,
		$invoice->discount_dp_amount2,
		$invoice->ar_amount //Added by Albert 06/30/2022
	);

	if ($invoice->payment_policy != 0 && ST_SALESINVOICEREPO && 
	$invoice->payment_location == "Branch") { //Modified by Albert 18 Aug 2022

		// Down-payment 
		add_loan_schedule_ob(
			ST_SALESINVOICEREPO,
			$invoice_no,
			$invoice->customer_id,
			0,
			$date_,
			date('D', strtotime($date_)),
			$invoice->dp_amount,
			($entry_amount - $invoice->dp_amount),
			$invoice->dp_amount,
			($entry_amount - $invoice->dp_amount),
			0,
			0
		);

		// Monthly Payment
		$sched_due_date = $invoice->first_due_date;
		$principal_run_bal = $entry_amount - $invoice->dp_amount;
		for ($i = 1; $i <= $invoice->months_term; $i++) {
			$principal_run_bal = $principal_run_bal - $invoice->amortization;
			add_loan_schedule_ob(
				ST_SALESINVOICEREPO,
				$invoice_no,
				$invoice->customer_id,
				$i,
				$sched_due_date,
				date('D', strtotime($sched_due_date)),
				$invoice->amortization,
				$principal_run_bal,
				$invoice->amortization,
				$principal_run_bal,
				0,
				0
			);
			$sched_due_date = date("m/d/Y", strtotime("+1 month", strtotime($sched_due_date)));
		}
	}

	$invoice->status = 'Closed';
	if ($opening_balances == 0)
		closed_sales_order_status($invoice);

	if ($trans_no == 0) {
		$invoice->trans_no = array($invoice_no => 0);
	} else
		move_trans_attachments(ST_SALESINVOICEREPO, $trans_no, $invoice_no);

	$total = 0;
	// for prepayments use deferred income account if set
	$sales_account = $invoice->is_prepaid() ? get_company_pref('deferred_income_act') : 0;

	foreach ($invoice->line_items as $line_no => $invoice_line) {

		$qty = $invoice_line->qty_dispatched;
		$line_taxfree_price = get_tax_free_price_for_item(
			$invoice_line->stock_id,
			$invoice_line->price * $qty,
			0,
			$invoice->tax_included,
			$invoice->tax_group_array
		);

		$line_tax = get_full_price_for_item(
			$invoice_line->stock_id,
			$invoice_line->price * $qty,
			0,
			$invoice->tax_included,
			$invoice->tax_group_array
		) - $line_taxfree_price;
		
		write_customer_trans_detail_item(
			ST_SALESINVOICEREPO,
			$invoice_no,
			$invoice_line->stock_id,
			$invoice_line->item_description,
			$invoice_line->qty_dispatched,
			$invoice_line->line_price(),
			$qty ? $line_tax / $qty : 0,
			$invoice_line->discount_percent,
			$invoice_line->standard_cost,
			$invoice_line->src_id,
			$trans_no ? $invoice_line->id : 0,
			$invoice_line->lot_no,
			$invoice_line->chasis_no,
			$invoice_line->color_desc,
			$invoice_line->item_type,
			$invoice_line->discount1,
			$invoice_line->discount2,
			0,
			Get_Item_Incentive_Price($invoice->category_id, $invoice_line->stock_id, "BII"),
			Get_Policy_Installment_Price(getCompDet('branch_code'), $invoice->category_id, $invoice_line->stock_id)		//Added by Albert 03/09/2023


		);
		/* Update status in repo items_details */
		update_repo_items_status($invoice_no,$invoice_line->lot_no);

		// Update delivery items for the quantity invoiced
		if ($invoice_line->qty_old != $invoice_line->qty_dispatched) {
			if ($invoice->is_prepaid())
				update_prepaid_so_line($invoice_line->src_id, $invoice_line->qty_dispatched - $invoice_line->qty_old);
			else
				update_parent_line(ST_SALESINVOICEREPO, $invoice_line->src_id, ($invoice_line->qty_dispatched - $invoice_line->qty_old));
		}
		if ($invoice_line->qty_dispatched != 0) {
			$stock_gl_code = get_stock_gl_code($invoice_line->stock_id);
			$category_gl_code = get_gl_code_from_category($invoice->category_id);
			if ($invoice_line->line_price() != 0) {
				//Post sales transaction to GL credit sales

				// If there is a Branch Sales Account, then override with this,
				// else take the Item Sales Account
				// if (!$invoice->is_prepaid()) {
				// 	if ($invoice->amortization > 0) {
				// 		if ($invoice->months_term >= 1 && $invoice->months_term <= 3 && !check_employee_customer($invoice->customer_id))
				// 			$sales_account = $category_gl_code['dflt_regular_sales_act'];
				// 		if ($invoice->months_term >= 4 && !check_employee_customer($invoice->customer_id))
				// 			$sales_account = $category_gl_code['dflt_installment_sales_act'];
				// 		if (check_employee_customer($invoice->customer_id))
				// 			$sales_account = $company_data['isa_employee'];
				// 	} else {
				// 		$sales_account = $category_gl_code['dflt_sales_act'];
				// 	}
				// 	// $sales_account = ($branch_data['sales_account'] != "" ? $branch_data['sales_account'] : $stock_gl_code['sales_account']);	
				// }

				// If there is a Customer Dimension, then override with this,
				// else take the Item Dimension (if any)
				$dim = ($invoice->dimension_id != $customer['dimension_id'] ? $invoice->dimension_id : ($customer['dimension_id'] != 0 ? $customer["dimension_id"] : $stock_gl_code["dimension_id"]));
				$dim2 = ($invoice->dimension2_id != $customer['dimension2_id'] ? $invoice->dimension2_id : ($customer['dimension2_id'] != 0 ? $customer["dimension2_id"] : $stock_gl_code["dimension2_id"]));

				$cash_discount += $invoice_line->discount1 + $invoice_line->discount2;
				$c_discount1 += $invoice_line->discount1;
				$c_discount2 += $invoice_line->discount2;
				
				//Added by spyrax10
				$row = get_supplier_mcode($invoice_line->stock_id);
				$mcode = strtoupper($row['name']);
				$masterfile = strtoupper($row['supp_name']);
				//

				// if ($invoice_line->discount_percent != 0) {

				// 	$total += add_gl_trans_customer(
				// 		ST_SALESINVOICEREPO,
				// 		$invoice_no,
				// 		$date_,
				// 		$branch_data["sales_discount_account"],
				// 		$dim,
				// 		$dim2,
				// 		($line_taxfree_price * $invoice_line->discount_percent) * $prepaid_factor,
				// 		$invoice->customer_id,
				// 		"The sales discount GL posting could not be inserted"
				// 	);
				// }


				/*end of if discount !=0 */
			}
		} /*quantity dispatched is more than 0 */
	} /*end of delivery_line loop */

	if ($invoice->amortization > 0 ) {
		if ($invoice->months_term >= 1 && $invoice->months_term <= 3 && !check_employee_customer($invoice->customer_id))
			$sales_account = $category_gl_code['dflt_regular_sales_act'];
		if ($invoice->months_term >= 4 && !check_employee_customer($invoice->customer_id))
			$sales_account = $category_gl_code['dflt_installment_sales_act'];
		if (check_employee_customer($invoice->customer_id))
			$sales_account = $company_data['isa_employee'];
		/*Added by Albert */
		if ($invoice->payment_location == "Lending"){
			$sales_account = $category_gl_code['dflt_sales_act'];
		}
		/**/
	} else {
		$sales_account = $category_gl_code['dflt_sales_act'];
	}
	if ($opening_balances <> 1) { // albert 09/14/2021
		$total += add_gl_trans_customer(
			ST_SALESINVOICEREPO,
			$invoice_no,
			$date_,
			$sales_account,
			0,
			0,
			(-$entry_amount * 1), //Modified by spyrax10
			$invoice->customer_id,
			"The sales price GL posting could not be inserted"
		);
	}
	if ($c_discount1 > 0) {
		$total += add_gl_trans_customer(
			ST_SALESINVOICEREPO,
			$invoice_no,
			$date_,
			$branch_data["sales_discount_account"],
			$dim,
			$dim2,
			$c_discount1,
			$invoice->customer_id,
			"The sales discount GL posting could not be inserted"
		);
	}

	if ($c_discount2 > 0) {
		$total += add_gl_trans_customer(
			ST_SALESINVOICEREPO,
			$invoice_no,
			$date_,
			$company_data["ar_supp_discount_act"],
			$dim,
			$dim2,
			$c_discount2,
			$invoice->customer_id,
			"The sales discount GL posting could not be inserted",
			//Added by spyrax10
			0,
			$mcode, $masterfile
			//
		);
	}
	if ($opening_balances == 1) {
		add_gl_trans_customer(
			ST_SALESINVOICEREPO,
			$invoice_no,
			$date_,
			$company_data["default_sales_act"],
			$dim,
			$dim2,
			-1 * abs($entry_amount),
			$invoice->customer_id,
			"The sales price GL posting could not be inserted"
		);
		add_gl_trans_customer(
			ST_SALESINVOICEREPO,
			$invoice_no,
			$date_,
			$branch_data["receivables_account"],
			0,
			0,
			$entry_amount,
			$invoice->customer_id,
			"The total debtor GL posting could not be inserted"
		);
	}
	// 	//---start Modified by Albert P 09/09/2021---

	// 	add_gl_trans_customer(
	// 		ST_SALESINVOICEREPO,
	// 		$invoice_no,
	// 		$date_,
	// 		$company_data["dgp_account"],
	// 		$dim,
	// 		$dim2,
	// 		-1 * abs($invoice->deferred_gross_profit),
	// 		$invoice->customer_id,
	// 		"The total debtor GL posting could not be inserted"
	// 	);
	// 	add_gl_trans_customer(
	// 		ST_SALESINVOICEREPO,
	// 		$invoice_no,
	// 		$date_,
	// 		$company_data["default_sales_act"],
	// 		0,
	// 		0,
	// 		$invoice->deferred_gross_profit,
	// 		$invoice->customer_id,
	// 		"The total debtor GL posting could not be inserted"
	// 	);
	// }
	// 	// --end Albert
	if ($invoice->amortization > 0) {
		if (($entry_amount + $charge_shipping) != 0) {
			$ar_account = $branch_data["receivables_account"];
			if ($invoice->months_term >= 1 && $invoice->months_term <= 3 && !check_employee_customer($invoice->customer_id))
				$ar_account = $company_data["ar_reg_current_account"];
				
				/*Added by Albert*/
				if ($invoice->payment_location == "Lending"){
					$ar_account = $company_data["ar_cash_sales_account"];
					$amount = $entry_amount;
				}else{
					$amount = (($entry_amount - $cash_discount) + $charge_shipping) * $prepaid_factor;
				}
				/**/
			$total += add_gl_trans_customer(
				ST_SALESINVOICEREPO,
				$invoice_no,
				$date_,
				$ar_account,
				0,
				0,
				$amount,
				// (($entry_amount - $cash_discount) + $charge_shipping) * $prepaid_factor, //Modified by spyrax10
				// ($entry_amount + $charge_shipping + $items_added_tax + $freight_added_tax) * $prepaid_factor,
				$invoice->customer_id,
				"The total debtor GL posting could not be inserted"
			);
		}
	} else {
		if (($entry_amount + $charge_shipping) != 0) {
			$total += add_gl_trans_customer(
				ST_SALESINVOICEREPO,
				$invoice_no,
				$date_,
				$company_data["ar_cash_sales_account"],
				0,
				0,
				(($entry_amount - $cash_discount) + $charge_shipping) * $prepaid_factor, //Modified by spyrax10
				// ($entry_amount + $charge_shipping + $items_added_tax + $freight_added_tax) * $prepaid_factor,
				$invoice->customer_id,
				"The total debtor GL posting could not be inserted"
			);
		}
	}
	//Modified by spyrax10
	$to_allocate = (($entry_amount - $cash_discount) + $charge_shipping + $items_added_tax + $freight_added_tax);

	if ($charge_shipping != 0) {
		$total += add_gl_trans_customer(
			ST_SALESINVOICEREPO,
			$invoice_no,
			$date_,
			$company_data["freight_act"],
			0,
			0,
			-$invoice->get_tax_free_shipping() * $prepaid_factor,
			$invoice->customer_id,
			"The freight GL posting could not be inserted"
		);
	}

	if (!check_employee_customer($invoice->customer_id) && $invoice->amortization > 0 && $invoice->payment_location <> "Lending") {//modified by Albert
		$deferred_gross_profit = $entry_amount - get_cost_of_sales_for_si($invoice->dr_ref);
		//-------------09/13/2021 albert
		if ($opening_balances == 1) {
			$deferred_gross_profit = $invoice->deferred_gross_profit;		
		}

		$total += add_gl_trans_customer(
			ST_SALESINVOICEREPO,
			$invoice_no,
			$date_,
			$company_data["dgp_account"],
			$dim,
			$dim2,
			(-$deferred_gross_profit) ,
			$invoice->customer_id,
			"The total debtor GL posting could not be inserted"
		);
		//-------------09/13/2021 albert
		if ($opening_balances == 1) {
			$company_data = $company_data["default_sales_act"];	
		}else{
			$company_data = $company_data["isd_account"];
		}

		$total += add_gl_trans_customer(
			ST_SALESINVOICEREPO,
			$invoice_no,
			$date_,
			$company_data,
			0,
			0,
			($deferred_gross_profit),
			$invoice->customer_id,
			"The total debtor GL posting could not be inserted"
		);
	}


	// post all taxes
	// foreach ($taxes as $taxitem) {
	// 	if ($taxitem['Net'] != 0) {
	// 		$ex_rate = get_exchange_rate_from_home_currency(get_customer_currency($invoice->customer_id), $date_);
	// 		add_trans_tax_details(ST_SALESINVOICEREPO, $invoice_no, $taxitem['tax_type_id'],
	// 			$taxitem['rate'], $invoice->tax_included, $prepaid_factor*$taxitem['Value'],
	// 			 $taxitem['Net'], $ex_rate, $date_, $invoice->reference, TR_OUTPUT);
	// 		if (isset($taxitem['sales_gl_code']) && !empty($taxitem['sales_gl_code']) && $taxitem['Value'] != 0)
	// 			$total += add_gl_trans_customer(ST_SALESINVOICEREPO, $invoice_no, $date_, $taxitem['sales_gl_code'], 0, 0,
	// 				(-$taxitem['Value'])*$prepaid_factor, $invoice->customer_id,
	// 				"A tax GL posting could not be inserted");
	// 	}
	// }

	/*Post a balance post if $total != 0 */
	add_gl_balance(ST_SALESINVOICEREPO, $invoice_no, $date_, -$total, PT_CUSTOMER, $invoice->customer_id);

	add_comments(ST_SALESINVOICEREPO, $invoice_no, $date_, $invoice->Comments);

	if ($trans_no == 0) {
		$Refs->save(ST_SALESINVOICEREPO, $invoice_no, $invoice->reference, null, $invoice->fixed_asset);
		if ($invoice->payment_terms['cash_sale'] && $invoice->pos['pos_account']) {
			$amount = $items_total + $items_added_tax + $invoice->freight_cost
				+ $freight_added_tax;
			if ($amount != 0) {
				// to use debtors.pmt_discount on cash sale:
				// extend invoice entry page with final amount after discount 
				// and change line below.
				$discount = 0; // $invoice->cash_discount*$amount;
				$pmtno = write_customer_payment(
					0,
					$invoice->customer_id,
					$invoice->Branch,
					$invoice->pos['pos_account'],
					$date_,
					$Refs->get_next(ST_CUSTPAYMENT, null, array(
						'customer' => $invoice->customer_id,
						'branch' => $invoice->Branch, 'date' => $date_
					)),
					$amount - $discount,
					$discount,
					$invoice->pos['pos_name'] . ' #' . $invoice_no
				);
				add_cust_allocation($amount, ST_CUSTPAYMENT, $pmtno, ST_SALESINVOICEREPO, $invoice_no, $invoice->customer_id, $date_);

				update_debtor_trans_allocation(ST_SALESINVOICEREPO, $invoice_no, $invoice->customer_id);
				update_debtor_trans_allocation(ST_CUSTPAYMENT, $pmtno, $invoice->customer_id);
			}
		}
	}
	reallocate_payments($invoice_no, ST_SALESINVOICEREPO, $date_, $to_allocate, $allocs, $invoice->customer_id);
	hook_db_postwrite($invoice, ST_SALESINVOICEREPO);

	commit_transaction();

	return $invoice_no;
}

//--------------------------------------------------------------------------------------------------

function void_sales_invoice_repo($type, $type_no)
{
	begin_transaction();

	hook_db_prevoid($type, $type_no);
	void_bank_trans($type, $type_no, true);
	void_gl_trans($type, $type_no, true);

	// reverse all the changes in parent document(s)
	$factor = get_cust_prepayment_invoice_factor($type_no);
	if ($factor != 0) {
		$lines = get_customer_trans_details($type, $type_no);
		while ($line = db_fetch($lines)) {
			update_prepaid_so_line($line['src_id'], -$factor * $line['quantity']);
		}
	} else {
		$deliveries = get_sales_parent_numbers($type, $type_no);

		if ($deliveries !== 0) {
			if ($type == ST_SALESINVOICEREPO && count($deliveries) == 1 && get_reference(ST_CUSTDELIVERY, $deliveries[0]) == "auto") {
				void_sales_delivery(ST_CUSTDELIVERY, $deliveries[0], false);
				$date_ = Today();
				add_audit_trail(ST_CUSTDELIVERY, $deliveries[0], $date_, _("Voided."));
				add_voided_entry(ST_CUSTDELIVERY, $deliveries[0], $date_, "");
			} else {
				$srcdetails = get_sales_parent_lines($type, $type_no);
				while ($row = db_fetch($srcdetails)) {
					update_parent_line($type, $row['id'], -$row['quantity']);
				}
			}
		}
	}
	// clear details after they've been reversed in the sales order
	void_customer_trans_details($type, $type_no);

	void_stock_move($type, $type_no); // in case of credit note with return

	void_trans_tax_details($type, $type_no);

	void_cust_allocations($type, $type_no);

	// do this last because other voidings can depend on it - especially voiding
	// DO NOT MOVE THIS ABOVE VOIDING or we can end up with trans with alloc < 0
	void_customer_trans($type, $type_no);

	commit_transaction();
}

/* Added by Ronelle 5/6/2021 */
//Modified by spyrax10 23-Oct-2021
function get_sales_invoice_header_repo($invoice_no)
{
	//modified by albert 05/10/2022
	set_global_connection();
	
	$sql = "SELECT
				c.name,
				c.debtor_no,
				a.reference as si_no,
				a.status,
				a.tran_date as invoice_date,
				CASE
					WHEN b.installmentplcy_id = 0
						THEN 'CASH'
					ELSE
						'INSTALLMENT'
				END as payment_type,
				b.invoice_type,
				b.months_term,
				b.rebate,
				b.financing_rate,
				b.downpayment_amount,
				b.amortization_amount,
				b.lcp_amount + DIS.discount AS lcp_amount,
				b.ar_amount,
				b.warranty_code,
				b.fsc_series,
				b.firstdue_date,
				b.maturity_date,
				b.outstanding_ar_amount,
				a.opening_balances,
				b.co_maker,
				b.discount_downpayment,
				b.delivery_ref_no,
				b.deferred_gross_profit,
				b.profit_margin,
				b.ref_no,
				b.discount_downpayment2,
				b.category_id,
				ST.name AS 'sales_type',
				SMAN.salesman_name,
				CONCAT(CM.lastname, ' ', CM.firstname) AS co_maker_name,
				BR.br_name, BR.branch_ref,
				a.alloc


				FROM debtor_trans a
				INNER JOIN debtor_loans b ON b.trans_no = a.trans_no and a.reference = b.reference
				INNER JOIN debtors_master c ON c.debtor_no = a.debtor_no 
				LEFT JOIN sales_orders SO ON a.order_ = SO.order_no
				LEFT JOIN co_makers CM ON b.co_maker = CM.comaker_id
				LEFT JOIN sales_type ST ON c.sales_type = ST.id
				LEFT JOIN salesman SMAN ON SO.salesman_id = SMAN.salesman_code
				LEFT JOIN cust_branch BR ON c.debtor_no = BR.debtor_no

			LEFT JOIN (
					SELECT A.order_no, SUM(B.discount1 + B.discount2) AS discount
					FROM sales_orders A 
					INNER JOIN sales_order_details B ON A.order_no = B.order_no 
					GROUP BY B.order_no
				) DIS ON a.order_ = DIS.order_no

			WHERE
				a.trans_no =" . db_escape($invoice_no) . " AND a.type =". ST_SALESINVOICEREPO;

	$result = db_query($sql, "sales invoice retreival");

	$num = db_num_rows($result);
	if ($num > 1) {
		display_warning("You have duplicate document in database: (type:Sales Invoice, number:$invoice_no).");
	} else if ($num == 1) {
		return db_fetch($result);
	} else
		display_warning("You have missing or invalid sales document in database (type:Sales Invoice, number:$invoice_no).");
}

/* Added by Ronelle 8/7/2021 */

function done_check_qty_replace_invoice_repo($debtor_trans_ref)
{
	$sql = "SELECT SUM(a.qty_replace) as sum_qty_replace, SUM(a.quantity) as sum_quantity 
		FROM " . TB_PREF . "debtor_trans_details a INNER JOIN
		" . TB_PREF . "debtor_trans b ON b.trans_no = a.debtor_trans_no 
		WHERE a.debtor_trans_type = " . ST_SALESINVOICEREPO . "
		AND b.reference =" . db_escape($debtor_trans_ref);

	$result = db_query($sql, "sales invoice retreival replace qty and invoice qty");
	$row = db_fetch($result);
	if ($row[0] >= $row[1] && !is_null($row[0]) && !is_null($row[1]))
		return true;
	else
		return false;
}

/* */

/* Count months paid amortization schedule - Added by Ronelle 8/14/2021 */
function count_months_paid_repo($debtor_trans_ref)
{
	$sql = "SELECT COUNT(a.status) FROM " . TB_PREF . "debtor_loan_schedule a 
		INNER JOIN debtor_trans b ON b.trans_no = a.trans_no
		WHERE a.trans_type=" . ST_SALESINVOICEREPO . " 
		AND b.reference=" . db_escape($debtor_trans_ref) . " AND a.status = 'paid' AND a.month_no > 0";
	$result = db_query($sql, "not read sales invoice count months paid");
	$row = db_fetch($result);
	return $row[0];
}
/* */

/* Added by Ronelle 8/20/2021 */
function closed_sales_invoice_status_repo($invoice, $trans_no, $ref)
{
	//Added by spyrax10
	$row = get_SI_by_reference($ref);
	$si_trans_no = $row['trans_no'];
	//

	begin_transaction();
	hook_db_prewrite($invoice, ST_SALESINVOICEREPO);

	/*Update the sales order draft status */
	$sql = "UPDATE " . TB_PREF . "debtor_trans SET status='Closed' ";
	$sql .= " WHERE trans_no = $si_trans_no AND type = " . ST_SALESINVOICEREPO;
	db_query($sql, "The sales order could not be updated");

	add_audit_trail(ST_SALESINVOICEREPO, $si_trans_no, Today(), _("Update Status."));
	hook_db_postwrite($invoice, ST_SALESINVOICEREPO);
	commit_transaction();
}
/* */

//Added by spyrax10

function get_supplier_mcode_repo($stock_id) {
	
	$sql = "SELECT IB.name, IFNULL(SUP.supp_name, IB.name) AS supp_name 
		FROM stock_master SM
			INNER JOIN item_brand IB ON SM.brand = IB.id
			LEFT JOIN suppliers SUP ON IB.name = SUP.supp_ref 
		WHERE SM.stock_id = " .db_escape($stock_id);

	$result = db_query($sql, "No Supplier details return! (spyrax10)");
	set_global_connection();
	return db_fetch($result);
}
/* Added by Albert */
function update_repo_items_status($trans_no,$lot_no){

	$sql = "UPDATE ".TB_PREF."repo_item_details
		SET status = 1
		WHERE status = 0 And serial_no = ".db_escape($lot_no);
		
		db_query($sql, "Cannot update repo_item_details!");
	
		add_audit_trail(ST_SALESINVOICEREPO, $trans_no, Today(), _("Update Status."));
		
}

function get_previous_owner($lot_no) {

	set_global_connection();
	$sql = "SELECT b.name, b.debtor_no
			FROM ".TB_PREF."debtor_trans a  
			Inner join ".TB_PREF."debtor_trans_details c ON c.debtor_trans_no = a.trans_no
			Inner Join ".TB_PREF."debtors_master b ON a.debtor_no = b.debtor_no
			WHERE c.lot_no =" . db_escape($lot_no);

	$sql.= " ORDER BY a.trans_no DESC LIMIT 1";

	$result = db_query($sql, "Can't get previous name!!!");
	
	return db_fetch($result);
}