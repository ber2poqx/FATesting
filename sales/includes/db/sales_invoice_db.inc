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
function write_sales_invoice(&$invoice, $opening_balances = 0, $warranty_code = "", $fsc_series = "", 
	$ob_standard_cost = 0) //Added by spyrax10
{
	global $Refs;

	$trans_no = $invoice->trans_no;
	if (is_array($trans_no))
		$trans_no = key($trans_no);

	$date_ = $invoice->document_date;
	$charge_shipping = $invoice->freight_cost;

	begin_transaction();

	hook_db_prewrite($invoice, ST_SALESINVOICE);
	$company_data = get_company_prefs();

	$branch_data = get_branch_accounts($invoice->Branch);

	$customer = get_customer($invoice->customer_id);

	add_new_exchange_rate($customer['curr_code'], $date_, $invoice->ex_rate);

	// offer price values without freight costs
	$items_total = $invoice->get_items_total_dispatch();
	$freight_tax = $invoice->get_shipping_tax();

	if (!$invoice->is_prepaid())
		update_customer_trans_version(get_parent_type(ST_SALESINVOICE), $invoice->src_docs);
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
	$hoc_code = get_company_value(0, 'branch_code') ? get_company_value(0, 'branch_code'):0  ;
    $hoc_masterfile = get_company_value(0, 'name') ? get_company_value(0, 'name'): 0;

	if ($opening_balances == 1)
		$sales_order = 0;
	if (is_array($sales_order))
		$sales_order = $sales_order[0]; // assume all crucial SO data are same for every delivery

	if ($trans_no) {
		$allocs = get_payments_for($trans_no, ST_SALESINVOICE, $invoice->customer_id);
		delete_comments(ST_SALESINVOICE, $trans_no);
		void_gl_trans(ST_SALESINVOICE, $trans_no, true);
		void_trans_tax_details(ST_SALESINVOICE, $trans_no);
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

	//Modified by spyrax10 8 Aug 2022
	if ($invoice->months_term > 0) {
		$entry_amount = $invoice->payment_location == "Lending" ? $invoice->lcp_amount : $invoice->ar_amount;
	}
	else {
		$entry_amount = $invoice->ar_amount;
	}
	//
	
	//openning oustading ar balance (added by Albert)
	$ar_balance = $entry_amount -  $invoice->ob_total_amount_paid;
	//
	$outstanding_ar_amount = $entry_amount - $invoice->dp_amount; // added by albert
	
	$invoice_no = write_customer_trans(
		ST_SALESINVOICE,
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
		$opening_balances == 1 ? $invoice->ob_total_amount_paid : 0,
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
		$invoice->invoice_type,
		$invoice->payment_policy,
		$invoice->months_term,
		$invoice->rebate,
		$invoice->financing_rate,
		$invoice->first_due_date, 
		$invoice->maturity_date,
		$opening_balances == 1 ? $ar_balance : $outstanding_ar_amount,
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
		$invoice->profit_margin > 0 ? $invoice->profit_margin : 0,
		$invoice->document_ref, //Added by spyrax10
		$opening_balances == 1 ? $invoice->old_trans_no : 0,
		$invoice->discount_dp_amount2, //Added by Albert
		$invoice->ar_amount //Added by Albert 06/30/2022

	);

	//Modified by spyrax10 9 Aug 2022
	if ($invoice->payment_policy != 0 && ST_SALESINVOICE && 
		$invoice->payment_location == "Branch") {

		// Down-payment 
		add_loan_schedule_ob(
			ST_SALESINVOICE,
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

		//Added by Albert 11/05/2022 Address mantis #1024
		if($invoice->months_term < 1 && $invoice->months_term > 0){
				
			$principal_run_bal = $principal_run_bal - $invoice->amortization;
				add_loan_schedule_ob(
					ST_SALESINVOICE,
					$invoice_no,
					$invoice->customer_id,
					$invoice->months_term,
					$sched_due_date,
					date('D', strtotime($sched_due_date)),
					$invoice->amortization,
					$principal_run_bal,
					$invoice->amortization,
					$principal_run_bal,
					0,
					0
				);
		/**/	
		}else{
			for ($i = 1; $i <= $invoice->months_term; $i++) {
				$principal_run_bal = $principal_run_bal - $invoice->amortization;
				add_loan_schedule_ob(
					ST_SALESINVOICE,
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
	}

	$invoice->status = 'Closed';
	if ($opening_balances == 0)
		closed_sales_order_status($invoice);

	if ($trans_no == 0) {
		$invoice->trans_no = array($invoice_no => 0);
	} else
		move_trans_attachments(ST_SALESINVOICE, $trans_no, $invoice_no);

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
			ST_SALESINVOICE,
			$invoice_no,
			$invoice_line->stock_id,
			$invoice_line->item_description,
			$invoice_line->qty_dispatched,
			$invoice_line->line_price(),
			$qty ? $line_tax / $qty : 0,
			$invoice_line->discount_percent,
			//Modified by spyrax10
			$opening_balances == 1 ? $invoice->ob_standard_cost : $invoice_line->standard_cost,
			//
			$invoice_line->src_id,
			$trans_no ? $invoice_line->id : 0,
			$invoice_line->lot_no,
			$invoice_line->chasis_no,
			$invoice_line->color_desc,
			$invoice_line->item_type,
			$invoice_line->discount1,
			$invoice_line->discount2,
			//Modified by spyrax10 7 Feb 2022
			Get_Item_Incentive_Price($invoice->category_id, $invoice_line->stock_id, "SMI"),
			Get_Item_Incentive_Price($invoice->category_id, $invoice_line->stock_id, "BII"),
			//$invoice_line->smi,
			//$invoice_line->incentives
			Get_Policy_Installment_Price(getCompDet('branch_code'), $invoice->category_id, $invoice_line->stock_id)   //Added by Albert 03/09/2023
		);

		// Update delivery items for the quantity invoiced
		if ($invoice_line->qty_old != $invoice_line->qty_dispatched) {
			if ($invoice->is_prepaid())
				update_prepaid_so_line($invoice_line->src_id, $invoice_line->qty_dispatched - $invoice_line->qty_old);
			else
				update_parent_line(ST_SALESINVOICE, $invoice_line->src_id, ($invoice_line->qty_dispatched - $invoice_line->qty_old));
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
				/*modified by Albert Discount is based in QTY*/
				$cash_discount += ($invoice_line->discount1 * $qty) + ($invoice_line->discount2 * $qty);
				$c_discount1 += $invoice_line->discount1 * $qty;
				$c_discount2 += $invoice_line->discount2 * $qty;
				/**/
				//Added by spyrax10
				$row = get_supplier_mcode($invoice_line->stock_id);
				$mcode = strtoupper($row['name']);
				$masterfile = strtoupper($row['supp_name']);
				//

				// if ($invoice_line->discount_percent != 0) {

				// 	$total += add_gl_trans_customer(
				// 		ST_SALESINVOICE,
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

	if ($invoice->amortization > 0) {
		if ($invoice->months_term > 0 && $invoice->months_term <= 3 && !check_employee_customer($invoice->customer_id)) { //modified by Albert Address 15 days installment
			$sales_account = $category_gl_code['dflt_regular_sales_act'];
		}
		if ($invoice->months_term >= 4 && !check_employee_customer($invoice->customer_id)) {
			$sales_account = $category_gl_code['dflt_installment_sales_act'];
		}
		/*Added by Albert */
		if ($invoice->payment_location == "Lending") {
			$sales_account = $category_gl_code['dflt_sales_act'];
		}
		/**/

		// addressed to fix employee entry
		if (check_employee_customer($invoice->customer_id) == 1) {
			$sales_account = $category_gl_code['dflt_installment_sales_act'];
			$entry_amount = $invoice->ar_amount;
		}
		//
	} else {
		$sales_account = $category_gl_code['dflt_sales_act'];
	}


	// if ($invoice->payment_location)
	// 	$sales_account = $category_gl_code['dflt_sales_act'];

	if ($opening_balances <> 1) { // albert 09/14/2021

		// //Added by Albert 7/27/2022 for gl entry discount 
		// if($invoice->amortization == 0){
		// 	$entry_amount = $entry_amount + $c_discount1 + $c_discount2;
		// }
		//
		$total += add_gl_trans_customer(
			ST_SALESINVOICE,
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
			ST_SALESINVOICE,
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
			ST_SALESINVOICE,
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
		$oustanding_balance = $entry_amount - $invoice->ob_total_amount_paid;
		add_gl_trans_customer(
			ST_SALESINVOICE,
			$invoice_no,
			$date_,
			$company_data["default_sales_act"],
			$dim,
			$dim2,
			-1 * abs($oustanding_balance),
			$invoice->customer_id,
			"The sales price GL posting could not be inserted",
			0,
			$hoc_code, 
			$hoc_masterfile
		);
		add_gl_trans_customer(
			ST_SALESINVOICE,
			$invoice_no,
			$date_,
			$branch_data["receivables_account"],
			0,
			0,
			$entry_amount,
			$invoice->customer_id,
			"The total debtor GL posting could not be inserted"
		);
		if ($invoice->ob_total_amount_paid > 0){
			add_gl_trans_customer(
				ST_SALESINVOICE,
				$invoice_no,
				$date_,
				$branch_data["receivables_account"],
				0,
				0,
				-1 * $invoice->ob_total_amount_paid,
				$invoice->customer_id,
				"The total debtor GL posting could not be inserted"
			);
		}
	}
	// 	//---start Modified by Albert P 09/09/2021---

	// 	add_gl_trans_customer(
	// 		ST_SALESINVOICE,
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
	// 		ST_SALESINVOICE,
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
	if ($opening_balances <> 1){
		if ($invoice->amortization > 0 ) {
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
					
				// addressed to fix employee entry
				if (check_employee_customer($invoice->customer_id)) {
					$ar_account = $company_data['isa_employee'];
					$amount = $invoice->ar_amount;
				}
				/**/
					
				$total += add_gl_trans_customer(
					ST_SALESINVOICE,
					$invoice_no,
					$date_,
					$ar_account,
					0,
					0,
					$amount, //modfied by Albert
					//(($entry_amount - $cash_discount) + $charge_shipping) * $prepaid_factor, //Modified by spyrax10
					// ($entry_amount + $charge_shipping + $items_added_tax + $freight_added_tax) * $prepaid_factor,
					$invoice->customer_id,
					"The total debtor GL posting could not be inserted"
				);
			}
		} else {
			if (($entry_amount + $charge_shipping) != 0) {
				$total += add_gl_trans_customer(
					ST_SALESINVOICE,
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
	}
	//Modified by spyrax10
	$to_allocate = (($entry_amount - $cash_discount) + $charge_shipping + $items_added_tax + $freight_added_tax);

	if ($charge_shipping != 0) {
		$total += add_gl_trans_customer(
			ST_SALESINVOICE,
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
			ST_SALESINVOICE,
			$invoice_no,
			$date_,
			$company_data["dgp_account"],
			$dim,
			$dim2,
			(-$deferred_gross_profit),
			$invoice->customer_id,
			"The total debtor GL posting could not be inserted"
		);
		//-------------09/13/2021 albert
		if ($opening_balances == 1) {
			$company_data = $company_data["default_sales_act"];
		} else {
			$company_data = $company_data["isd_account"];
		}
		//Added by Albert 7/27/2022 for gl entry discount 
		if($invoice->amortization == 0){
			$deferred_gross_profit = $deferred_gross_profit + $c_discount1 + $c_discount2;
		}
		//

		$total += add_gl_trans_customer(
			ST_SALESINVOICE,
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
	// 		add_trans_tax_details(ST_SALESINVOICE, $invoice_no, $taxitem['tax_type_id'],
	// 			$taxitem['rate'], $invoice->tax_included, $prepaid_factor*$taxitem['Value'],
	// 			 $taxitem['Net'], $ex_rate, $date_, $invoice->reference, TR_OUTPUT);
	// 		if (isset($taxitem['sales_gl_code']) && !empty($taxitem['sales_gl_code']) && $taxitem['Value'] != 0)
	// 			$total += add_gl_trans_customer(ST_SALESINVOICE, $invoice_no, $date_, $taxitem['sales_gl_code'], 0, 0,
	// 				(-$taxitem['Value'])*$prepaid_factor, $invoice->customer_id,
	// 				"A tax GL posting could not be inserted");
	// 	}
	// }

	/*Post a balance post if $total != 0 */
	add_gl_balance(ST_SALESINVOICE, $invoice_no, $date_, -$total, PT_CUSTOMER, $invoice->customer_id);

	add_comments(ST_SALESINVOICE, $invoice_no, $date_, $invoice->Comments);

	if ($trans_no == 0) {
		$Refs->save(ST_SALESINVOICE, $invoice_no, $invoice->reference, null, $invoice->fixed_asset);
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
				add_cust_allocation($amount, ST_CUSTPAYMENT, $pmtno, ST_SALESINVOICE, $invoice_no, $invoice->customer_id, $date_);

				update_debtor_trans_allocation(ST_SALESINVOICE, $invoice_no, $invoice->customer_id);
				update_debtor_trans_allocation(ST_CUSTPAYMENT, $pmtno, $invoice->customer_id);
			}
		}
	}
	reallocate_payments($invoice_no, ST_SALESINVOICE, $date_, $to_allocate, $allocs, $invoice->customer_id);
	
	/*Added by Albert*/
	if($opening_balances == 1){
		
	alloc_in_openning($invoice_no, $invoice->customer_id, $invoice->ob_total_amount_paid, $invoice->amortization, $date_);


	add_cust_allocation($invoice->ob_total_amount_paid, ST_SALESINVOICE, $invoice_no, ST_SALESINVOICE, $invoice_no, $invoice->customer_id, $date_);
    update_debtor_trans_allocation( ST_SALESINVOICE, $invoice_no, $invoice->customer_id);


	}
	/**/
	hook_db_postwrite($invoice, ST_SALESINVOICE);

	commit_transaction();

	return $invoice_no;
}

//--------------------------------------------------------------------------------------------------

function void_sales_invoice($type, $type_no)
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
			if ($type == ST_SALESINVOICE && count($deliveries) == 1 && get_reference(ST_CUSTDELIVERY, $deliveries[0]) == "auto") {
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

function is_cust_invoice_credited($trans_no)
{
	return db_num_rows(get_sales_child_lines(ST_SALESINVOICE, $trans_no));
}

function get_cust_prepayment_invoice_factor($trans_no)
{
	$sql = "SELECT IF(dt.prep_amount>0, dt.prep_amount/so.total ,0) 
		FROM " . TB_PREF . "debtor_trans dt
		LEFT JOIN " . TB_PREF . "sales_orders so ON so.trans_type=" . ST_SALESORDER . " AND so.order_no=dt.order_
		 WHERE dt.type=" . ST_SALESINVOICE . " AND trans_no=" . db_escape($trans_no);
	$row = db_fetch(db_query($sql, 'cannot retrieve prepaid invoice factor'));
	return $row[0];
}

/*
	Return yet not invoiced amount of prepayment invoice (or 0 if normal invoice)
*/
function prepaid_invoice_remainder($order)
{
	$sql = "SELECT so.total - IFNULL(SUM(inv.prep_amount),0) FROM "
		. TB_PREF . "sales_orders so,
		" . TB_PREF . "debtor_trans inv,
		" . TB_PREF . "payment_terms pt
		WHERE  so.order_no=" . db_escape($order)
		. " AND so.trans_type=" . ST_SALESORDER
		. " AND inv.type=" . ST_SALESINVOICE
		. " AND inv.order_=so.order_no"
		. " AND so.payment_terms=pt.terms_indicator"
		. " AND inv.payment_terms=pt.terms_indicator"
		. " AND pt.days_before_due = -1";

	$result = db_fetch(db_query($sql, "cannot find final invoice value"));
	return $result[0] ? $result[0] : 0;
}


/*
	Find oldest delivery date for sales invoice
*/
function get_oldest_delivery_date($invoice_no)
{
	$sql = "SELECT MIN(trans.tran_date)
			FROM
				" . TB_PREF . "debtor_trans_details del
			LEFT JOIN " . TB_PREF . "debtor_trans_details inv
				ON inv.src_id=del.id
			LEFT JOIN " . TB_PREF . "debtor_trans trans 
				ON trans.type=" . ST_CUSTDELIVERY . " AND trans.trans_no = del.debtor_trans_no
			WHERE
				inv.debtor_trans_type=" . ST_SALESINVOICE
		. " AND inv.debtor_trans_no=" . db_escape($invoice_no);
	$res = db_query($sql, 'cannot find oldest delivery date');
	$date = db_fetch($res);
	return $date[0];
}

/*
	Find oldest payment date for sales invoice
*/
function get_oldest_payment_date($invoice_no)
{
	$sql = "SELECT MIN(payment.tran_date)
			FROM
			" . TB_PREF . "cust_allocations alloc,
			" . TB_PREF . "debtor_trans payment
			WHERE
				alloc.trans_type_to=" . ST_SALESINVOICE . " AND alloc.trans_no_to=" . db_escape($invoice_no)
		. " AND alloc.trans_type_from=payment.type AND alloc.trans_no_from=payment.trans_no";
	$res = db_query($sql, 'cannot find oldest delivery date');
	$date = db_fetch($res);
	return $date[0];
}

/* Added by Ronelle 5/6/2021 */
//Modified by spyrax10 23-Oct-2021
function get_sales_invoice_header($invoice_no)
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
				a.alloc,
				b.payment_location

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
				a.trans_no =" . db_escape($invoice_no) . " AND a.type = 10";

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

function done_check_qty_replace_invoice($debtor_trans_ref)
{
	$sql = "SELECT SUM(a.qty_replace) as sum_qty_replace, SUM(a.quantity) as sum_quantity 
		FROM " . TB_PREF . "debtor_trans_details a INNER JOIN
		" . TB_PREF . "debtor_trans b ON b.trans_no = a.debtor_trans_no 
		WHERE a.debtor_trans_type = " . ST_SALESINVOICE . "
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
function count_months_paid($debtor_trans_ref)
{
	$sql = "SELECT COUNT(a.status) FROM " . TB_PREF . "debtor_loan_schedule a 
		INNER JOIN debtor_trans b ON b.trans_no = a.trans_no
		WHERE a.trans_type=" . ST_SALESINVOICE . " 
		AND b.reference=" . db_escape($debtor_trans_ref) . " AND a.status = 'paid' AND a.month_no > 0";
	$result = db_query($sql, "not read sales invoice count months paid");
	$row = db_fetch($result);
	return $row[0];
}
/* */
/*Added by Albert 10/15/2022*/
function get_loan_schedule_months_due($debtor_trans_ref, $date)
{
	$sql = "SELECT a.date_due FROM " . TB_PREF . "debtor_loan_schedule a 
		INNER JOIN debtor_trans b ON b.trans_no = a.trans_no
		WHERE a.trans_type=" . ST_SALESINVOICE . " 
		AND b.reference=" . db_escape($debtor_trans_ref) . "AND a.date_due <=" . db_escape($date) . " AND a.month_no > 0 order by a.date_due desc limit 1";
	$result = db_query($sql, "not read sales invoice count months paid");
	$row = db_fetch($result);
	return $row[0];
}
/**/

/* Added by Ronelle 8/20/2021 */
function closed_sales_invoice_status($invoice, $trans_no, $ref, $payment_location)
{
	//Added by spyrax10
	$row = get_SI_by_reference($ref);
	$si_trans_no = $row['trans_no'];
	/*Added by Albert 11/03/2022*/
	$type =  $row['type'];

	begin_transaction();
	hook_db_prewrite($invoice, $type);

	/*Update the sales order draft status */
	$sql = "UPDATE " . TB_PREF . "debtor_trans SET status='Closed' ";
	$sql .= " WHERE trans_no = $si_trans_no AND type = " . db_escape($type);
	db_query($sql, "The sales order could not be updated");

	add_audit_trail($type, $si_trans_no, Today(), _("Update Status."));
	hook_db_postwrite($invoice, $type);
	commit_transaction();
}

/* */

//Added by spyrax10

function debtor_stock_id($trans_no = 0, $trans_type = 0) {
	
	set_global_connection();

	$sql = "SELECT stock_id FROM  ".TB_PREF."debtor_trans_details
		WHERE debtor_trans_no = " .db_escape($trans_no) 
		. " AND debtor_trans_type = " .db_escape($trans_type);

	$result = db_query($sql, "debtor_stock_id()");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_policy_name($policy_id, $category_id) {

	$sql = "SELECT A.plcydtl_code FROM ".TB_PREF."policy_details A
		WHERE A.id = ".db_escape($policy_id) . " AND A.category_id = ".db_escape($category_id);

	$result = db_query($sql, "Cant get policy name! (spyrax10)");
    set_global_connection();
	$row = db_fetch_row($result);
	return $row[0];
}

function get_supplier_mcode($stock_id) {
	
	$sql = "SELECT IB.name, IFNULL(SUP.supp_name, IB.name) AS supp_name 
		FROM stock_master SM
			INNER JOIN item_brand IB ON SM.brand = IB.id
			LEFT JOIN suppliers SUP ON IB.name = SUP.supp_ref 
		WHERE SM.stock_id = " .db_escape($stock_id);

	$result = db_query($sql, "No Supplier details return! (spyrax10)");
	set_global_connection();
	return db_fetch($result);
}

function get_SI_by_reference($reference = '', $trans_no = '', $type = '', $debtor_no = '') {
	
	set_global_connection();
	
	$months_term = $reference == '' ? ', DL.months_term, DL.delivery_ref_no' : '' ;

	$sql = "SELECT DT.*$months_term FROM " . TB_PREF . "debtor_trans DT ";

	if ($reference == '') {
		$sql .= " INNER JOIN " . TB_PREF . "debtor_loans DL ON DT.reference = DL.invoice_ref_no and DT.trans_no = DL.trans_no";
	}

	$sql .= " WHERE IFNULL(DT.trans_no, '') <> ''";

	if ($reference != '') {
		$sql .= " AND DT.reference = " .db_escape($reference);
		$sql .= " AND DT.trans_no not in (SELECT id FROM " . TB_PREF . "voided where void_status = 'Voided' AND reference_from = " .db_escape($reference).")"; //Added by Albert this is to avoid display voided data 
	}
	else {
		$sql .= " AND DT.trans_no = " .db_escape($trans_no) . 
		" AND DT.type = " .db_escape($type); 
		
		if ($debtor_no != '') {
			$sql .= " AND DT.debtor_no = " .db_escape($debtor_no);
		}
	}

	$result = db_query($sql, "get_SI_by_reference($reference || $trans_no || $type || $debtor_no)");
	return db_fetch($result);
}

function get_SI_details($trans_no) {

	set_global_connection();

	$sql = "SELECT * FROM " . TB_PREF . "debtor_trans_details 
		WHERE quantity > 0 AND debtor_trans_type = " . ST_CUSTDELIVERY . " 
			AND  debtor_trans_no = " .db_escape($trans_no);

	return db_query($sql, "get_SI_details($trans_no)");
}

function get_SO_header($order_no) {
	set_global_connection();

	$sql = "SELECT * FROM " . TB_PREF . "sales_orders";
	$sql .= " WHERE order_no = " .db_escape($order_no);

	$result = db_query($sql, "get_SO_header");
	return db_fetch($result);
}
function void_payments_thru_JE($reference = '', $trans_no = '', $is_alloc = false) {

	set_global_connection();

	$field = $is_alloc ? "alloc" : "ov_amount";

	$sql = "UPDATE " . TB_PREF . "debtor_trans SET $field = 0";
	$sql .= " WHERE type = " . ST_JOURNAL ." AND reference = " .db_escape($reference);
	db_query($sql, "void_SI_thru_JE(debtor_trans)");

	if ($is_alloc) {
		$sql2 = "UPDATE " . TB_PREF . "cust_allocations SET amt = 0";
		$sql2 .= " WHERE trans_type_from = " . ST_JOURNAL ." AND trans_no_from = " .db_escape($trans_no); 
		db_query($sql2, "void_SI_thru_JE(cust_allocations)");

		$sql3 = "UPDATE " . TB_PREF . "debtor_loan_ledger SET payment_applied = 0";
		$sql3 .= " WHERE trans_type_from = " . ST_JOURNAL ." AND payment_trans_no = " .db_escape($trans_no);
		db_query($sql3, "void_SI_thru_JE(debtor_loan_ledger)");
	}

	return $reference;
}

/*Added by Albert*/
function get_ar_amount($reference) {
	set_global_connection();
	$sql = "SELECT * FROM " . TB_PREF . "debtor_loans 
		WHERE invoice_ref_no = " .db_escape($reference);

	$result = db_query($sql, "No loans details return! (Albert)");

	return db_fetch($result);
}
function get_ob_standard_cost($invoice_no) {

	$sql = "SELECT standard_cost FROM " . TB_PREF . "debtor_trans_details 
		WHERE (debtor_trans_type = 10 OR debtor_trans_type = 57) AND  debtor_trans_no =" . db_escape($invoice_no);

	$result = db_query($sql, "No loans details return! (Albert)");
	$row = db_fetch_row($result);
	return $row[0];
}
function get_ob_old_trans_no($old_trans_no) {

	$sql = "SELECT old_trans_no FROM " . TB_PREF . "debtor_loans 
		WHERE old_trans_no = " . db_escape($old_trans_no);

	$result = db_query($sql, "can't get old trans no!!! (Albert)");
	$row = db_fetch_row($result);
	return $row[0];
}


/* --------------*/
function get_SI_id($trans_no, $stock_id, $lot_no = '', $chassis_no = '') {

	set_global_connection();

	$sql = "SELECT id FROM debtor_trans_details 
		WHERE debtor_trans_type = 10 
			AND debtor_trans_no = " .db_escape($trans_no) . "
			AND stock_id = " .db_escape($stock_id) . " 
			AND lot_no = " .db_escape($lot_no) . "
			AND chassis_no = " .db_escape($chassis_no);

	$result = db_query($sql, "No SI details return! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_so_date($order_no)
{
	set_global_connection();

	$sql = "SELECT A.ord_date FROM sales_orders A 
		WHERE A.order_no=" . db_escape($order_no);

	$result = db_query($sql, "Can't get SO Date! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_total_discount($order_no, $dis1_only = false) {
	set_global_connection();

	if ($dis1_only) {
		$sql = "SELECT  SUM(B.discount1 * B.quantity AS discount";
	}
	else {
		$sql = "SELECT SUM((B.discount1 * B.quantity) + (B.discount2 * B.quantity)) AS discount";
	}

	$sql .= " FROM sales_orders A 
			INNER JOIN sales_order_details B ON A.order_no = B.order_no
		WHERE A.order_no=" . db_escape($order_no) . " GROUP BY B.order_no";

	$result = db_query($sql, "get_total_discount()");
	$row = db_fetch_row($result);
	
	return $row[0];
}

function reference_exist($reference, $trans_no = null)
{

	set_global_connection();

	$sql = "SELECT COUNT(*) FROM debtor_loans A 
		WHERE A.ref_no=" . db_escape($reference);

	if ($trans_no != null) {
		$sql .= " AND trans_no != " . db_escape($trans_no);
	}

	$result = db_query($sql, "Can't get DL reference count! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}


function get_SO_trans_no($stock_id, $order_no)
{
	set_global_connection();

	$sql = "SELECT A.stock_trans_no
	FROM sales_order_details A
	WHERE A.stk_code =" . db_escape($stock_id) . " AND A.order_no=" . db_escape($order_no);

	$result = db_query($sql, "Can't get SO stock_trans_no! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_SO_trans_type($stock_id, $order_no)
{
	set_global_connection();

	$sql = "SELECT A.stock_trans_type
		FROM sales_order_details A
		WHERE A.stk_code =" . db_escape($stock_id) . " AND A.order_no=" . db_escape($order_no);

	$result = db_query($sql, "Can't get SO stock_trans_type! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_SO_total($order_no)
{
	set_global_connection();

	$sql = "SELECT A.total
		FROM sales_orders A
		WHERE A.order_no=" . db_escape($order_no);

	$result = db_query($sql, "Can't get SO Total! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}

//

/* Added by Ronelle 11/11/2021 */
function done_check_qty_return_invoice($si_ref)
{
	$sql = "SELECT
				(SELECT 
					SUM(t1.quantity)
				FROM 
					debtor_trans_details t1
				WHERE 
					t1.debtor_trans_no = b.trans_no 
				AND 
					t1.debtor_trans_type = b.type
				) - (SELECT
					SUM(z1.Quantity)
				FROM
					returned_units z1
				INNER JOIN
					debtor_trans_details z2
				ON
					z2.id = z1.from_debtor_trans_details_id
				WHERE
					z1.debtor_trans_no = b.trans_no
				AND
					z2.debtor_trans_type = b.type
				) as isFullySR
			FROM
				sales_return_replacement a
			INNER JOIN
				debtor_trans b
			ON
				b.reference = a.trans_ref
			WHERE
				a.trans_ref = " . db_escape($si_ref) . "
			GROUP BY b.trans_no";

	$result = db_query($sql, "sales invoice retreival retuurn qty");
	$row = db_fetch($result);
	$done = false;
	
	if (!empty($row) && $row[0] == 0){
		$done = true;
	}
	return $done;
}
function alloc_in_openning($trans_no, $debtor_no, $alloc, $amortization, $date_){
	/*alloc in openning*/
	$amortization_schedule = get_deptor_loan_schedule_ob($trans_no, $debtor_no, ST_SALESINVOICE);
    $total_exist_payment = floatval($alloc);
    while ($amort_sched = db_fetch($amortization_schedule)) {
        if ($total_exist_payment == 0)
            break;

        $amount = 0;
        $status = "paid";
        if ($total_exist_payment >= $amortization) {
            $amount = $amort_sched["total_principaldue"];
        } else {
            $amount = $total_exist_payment;
            $status = "partial";
        }
        add_loan_ledger_ct(
            $trans_no,
            $debtor_no,
            $amort_sched["id"],
            ST_SALESINVOICE,
            ST_SALESINVOICE,
            $amount,
            0,
            0,
            0,
            date2sql($date_),
            $trans_no,
			1
        );

        $total_exist_payment -= $amount;
        $loansched_id = $amort_sched["id"];
        $sql = "UPDATE " . TB_PREF . "debtor_loan_schedule SET
			status=" . db_escape($status) . ",penalty_status=" . db_escape($status) . "
			WHERE id=$loansched_id";

        $ErrMsg = _('Could not update loan schedule because ');

        db_query($sql, $ErrMsg);
    }
}