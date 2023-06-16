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
// insert/update sales delivery
//
function write_sales_delivery(&$delivery, $bo_policy)
{
	global $Refs;

	$trans_no = $delivery->trans_no;
	if (is_array($trans_no)) $trans_no = key($trans_no);

	begin_transaction();
	$delivery->bo_policy = $bo_policy;
	hook_db_prewrite($delivery, ST_CUSTDELIVERY);

	$customer = get_customer($delivery->customer_id);

	add_new_exchange_rate($customer['curr_code'], $delivery->document_date, $delivery->ex_rate);
	// modified by Albert 11/08/2021
	foreach ($delivery->line_items as $line_no => $delivery_line) {
		$delivery_items_total = get_serial_no($delivery_line->lot_no)
			? get_repo_cost_average($delivery_line->stock_id, $delivery->Location, $delivery_line->lot_no, $delivery_line->chasis_no)
			: $delivery->get_items_total_dispatch();
	}
	$freight_tax = $delivery->get_shipping_tax();

	// mark sales order for concurrency conflicts check
	update_sales_order_version($delivery->src_docs);

	$tax_total = 0;
	$taxes = $delivery->get_taxes(); // all taxes with freight_tax

	foreach ($taxes as $taxitem) {
		$taxitem['Value'] =  round2($taxitem['Value'], user_price_dec());
		$tax_total +=  $taxitem['Value'];
	}
	set_global_connection();
	/* Insert/update the debtor_trans */
	$delivery_no = write_customer_trans(
		ST_CUSTDELIVERY,
		$trans_no,
		$delivery->customer_id,
		$delivery->Branch,
		$delivery->document_date,
		$delivery->reference,
		$delivery_items_total,
		0,
		$delivery->tax_included ? 0 : $tax_total - $freight_tax,
		$delivery->freight_cost,
		$delivery->tax_included ? 0 : $freight_tax,
		$delivery->sales_type,
		$delivery->order_no,
		$delivery->ship_via,
		$delivery->due_date,
		0,
		0,
		$delivery->dimension_id,
		$delivery->dimension2_id,
		$delivery->payment,
		$delivery->tax_included,
		$delivery->prep_amount,
		0,
		'',
		0,
		'',
		false,
		$delivery->dr_ref_no == '' ? $delivery->reference : $delivery->dr_ref_no
	);

	if ($trans_no == 0) {
		$delivery->trans_no = array($delivery_no => 0);
	} else {
		move_trans_attachments(ST_CUSTDELIVERY, $trans_no, $delivery_no);
		void_gl_trans(ST_CUSTDELIVERY, $delivery_no, true);
		void_stock_move(ST_CUSTDELIVERY, $delivery_no);
		void_trans_tax_details(ST_CUSTDELIVERY, $delivery_no);
		delete_comments(ST_CUSTDELIVERY, $delivery_no);
	}


	// for prepaid delivery repost income from deffered account
	if ($delivery->is_prepaid()) {
		$branch_data = get_branch_accounts($delivery->Branch);
		$total = 0;
	}
	$total_cost = 0;
	foreach ($delivery->line_items as $line_no => $delivery_line) {
		$std_cost = get_serial_no($delivery_line->lot_no) ? get_repo_cost_average($delivery_line->stock_id, $delivery->Location, $delivery_line->lot_no, $delivery_line->chasis_no) :
			get_unit_cost_average($delivery_line->stock_id, $delivery->Location, $delivery_line->lot_no, $delivery_line->chasis_no);
		$smi = Get_Item_Incentive_Price(get_stock_catID($delivery_line->stock_id), $delivery_line->stock_id, "SMI") != null ?
			Get_Item_Incentive_Price(get_stock_catID($delivery_line->stock_id), $delivery_line->stock_id, "SMI") : 0;
		$incentives = Get_Item_Incentive_Price(get_stock_catID($delivery_line->stock_id), $delivery_line->stock_id, "BII") != null ?
			Get_Item_Incentive_Price(get_stock_catID($delivery_line->stock_id), $delivery_line->stock_id, "BII") : 0;
		$item_type =  get_serial_no($delivery_line->lot_no) ? 'repo' : 'new'; //Added by Albert 10/26/2021

		$qty = $delivery_line->qty_dispatched;
		$line_price = $delivery_line->line_price();
		$line_taxfree_price = get_tax_free_price_for_item(
			$delivery_line->stock_id,
			$delivery_line->price * $qty,
			0,
			$delivery->tax_included,
			$delivery->tax_group_array
		);

		$line_tax = get_full_price_for_item(
			$delivery_line->stock_id,
			$delivery_line->price * $qty,
			0,
			$delivery->tax_included,
			$delivery->tax_group_array
		) - $line_taxfree_price;
		/*Added by Albert 10/11/2021*/
		// display_warning(get_serial_no($delivery_line->lot_no));
		/*if (is_Serialized($delivery_line->stock_id) == 1 ){
			if(get_serial_no($delivery_line->lot_no))
			{
				$delivery_line->standard_cost = get_repo_cost_average($delivery_line->stock_id, $delivery->Location,  $delivery_line->lot_no, $delivery_line->chasis_no);
			}
			else {
				$delivery_line->standard_cost = get_unit_cost_average($delivery_line->stock_id, $delivery->Location, $delivery_line->lot_no, $delivery_line->chasis_no);
			}			
		}
		else{
			$delivery_line->standard_cost = get_unit_cost_average($delivery_line->stock_id, $delivery->Location, $delivery_line->lot_no, $delivery_line->chasis_no);
		} */
		//Modified by spyrax10
		if ($delivery_line->qty_dispatched != 0) {

			/* Added by Ronelle 11/11/2021 */
			$allocate_qty = 0;
			$done_allocate = false;
			$result = get_available_items(
				$delivery_line->stock_id,
				$delivery->Location,
				date("yyyy-MM-dd"),
				$delivery_line->lot_no,
				$delivery_line->chasis_no
			);
			while ($myrow = db_fetch($result)) {
				if ($done_allocate)
					break;
				$dispatch_qty = 0;
				for ($i = 0; $i < $myrow["qoh"]; $i++) {
					if ($allocate_qty < $delivery_line->qty_dispatched) {
						$allocate_qty++;
						$dispatch_qty++;
					} else {
						$done_allocate = true;
						break;
					}
				}
				//Modified by spyrax10 12 Aug 2022
				$total_cost = $myrow["standard_cost"] * $dispatch_qty;
				//
				write_customer_trans_detail_item(
					ST_CUSTDELIVERY,
					$delivery_no,
					$delivery_line->stock_id,
					$delivery_line->item_description,
					$dispatch_qty,
					$delivery_line->line_price(),
					$qty ? $line_tax / $qty : 0,
					$delivery_line->discount_percent,
					$myrow["standard_cost"],
					$delivery_line->src_id,
					$trans_no ? $delivery_line->id : 0,
					$delivery_line->lot_no,
					$delivery_line->chasis_no,
					$delivery_line->color_desc,
					$delivery_line->item_type,
					$delivery_line->discount1,
					$delivery_line->discount2,
					$smi,
					$incentives,
					$std_cost	//Added by Albert 03/09/2023
				);

				//Modified by spyrax10 13 Aug 2022
				if ($dispatch_qty != 0) {
					add_stock_move(
						ST_CUSTDELIVERY,
						$delivery_line->stock_id,
						$delivery_no,
						$delivery->Location,
						$delivery->document_date,
						$delivery->reference,
						-$dispatch_qty,
						//Modified by spyrax10
						/* Updated by Ronelle 11/11/2021 */
						$myrow["standard_cost"],
						/* */
						$line_price * (1 - $delivery_line->discount_percent),
						$delivery_line->lot_no,
						$delivery_line->chasis_no,
						$myrow['category_id'], //Modified by spyrax10 16 Aug 2022
						$delivery_line->color_desc,
						//Modified by spyrax10
						/* UPDATED BY RONELLE 11/11/2021 */
						$myrow["type"],
						$myrow["trans_no"],
						/* */
						// modified by albert
						$item_type
					);
				}
				//
			}
		}

		// Now update sales_order_details for the quantity delivered
		if ($delivery_line->qty_old != $delivery_line->qty_dispatched)
			update_parent_line(
				ST_CUSTDELIVERY,
				$delivery_line->src_id,
				$delivery_line->qty_dispatched - $delivery_line->qty_old
			);

		if ($delivery_line->qty_dispatched != 0) {

			sold_item_serialize($delivery_line->stock_id, $delivery_line->lot_no, $delivery_line->chasis_no);
			$mb_flag = get_mb_flag($delivery_line->stock_id);

			if (is_fixed_asset($mb_flag)) {
				$sql = "UPDATE " . TB_PREF . "stock_master SET inactive=1, material_cost=0
					WHERE stock_id=" . db_escape($delivery_line->stock_id);
				db_query($sql, "The cost details for the fixed asset could not be updated");
			}
			$stock_gl_code = get_stock_gl_code($delivery_line->stock_id);
			//Added by Albert
			$get_si_repo_acc = get_serial_no($delivery_line->lot_no) ? get_gl_account_per_category($delivery->category_id) : $stock_gl_code["inventory_account"]; //Added by Albert 11/05/2021
			// If there is a Customer Dimension, then override with this,
			// else take the Item Dimension (if any)
			$dim = ($delivery->dimension_id != $customer['dimension_id'] ? $delivery->dimension_id : ($customer['dimension_id'] != 0 ? $customer["dimension_id"] : $stock_gl_code["dimension_id"]));
			$dim2 = ($delivery->dimension2_id != $customer['dimension2_id'] ? $delivery->dimension2_id : ($customer['dimension2_id'] != 0 ? $customer["dimension2_id"] : $stock_gl_code["dimension2_id"]));
			if ($delivery->is_prepaid()) {
				$sales_account = ($branch_data['sales_account'] != "" ? $branch_data['sales_account'] : $stock_gl_code['sales_account']);

				$total += add_gl_trans_customer(
					ST_CUSTDELIVERY,
					$delivery_no,
					$delivery->document_date,
					$sales_account,
					$dim,
					$dim2,
					-$line_taxfree_price,
					$delivery->customer_id,
					"The sales price GL posting could not be inserted"
				);
			}
			/* insert gl_trans to credit stock and debit cost of sales at standard cost*/

			if (is_inventory_item($delivery_line->stock_id)) {
				// Fixed Assets
				if ($delivery->fixed_asset) {
					$fa_purchase_cost = get_purchase_cost($delivery_line->stock_id);
					$fa_depreciation = $fa_purchase_cost - $std_cost;
					/*first remove depreciation*/
					add_gl_trans_std_cost(
						ST_CUSTDELIVERY,
						$delivery_no,
						$delivery->document_date,
						$stock_gl_code["adjustment_account"],
						$dim,
						$dim2,
						$delivery_line->stock_id, //Added by spyrax10
						$fa_depreciation,
						PT_CUSTOMER,
						$delivery->customer_id,
						"The cost of sales GL posting could not be inserted"
					);
					/*then remove asset*/
					add_gl_trans_std_cost(
						ST_CUSTDELIVERY,
						$delivery_no,
						$delivery->document_date,
						$stock_gl_code["inventory_account"],
						0,
						0,
						$delivery_line->stock_id, //Added by spyrax10
						-$fa_purchase_cost,
						PT_CUSTOMER,
						$delivery->customer_id,
						"The stock side of the cost of sales GL posting could not be inserted"
					);
					/*finally adjust sales account with the remaining*/
					add_gl_trans_std_cost(
						ST_CUSTDELIVERY,
						$delivery_no,
						$delivery->document_date,
						//Modified by spyrax10					//Ads & Promo Entry
						get_stock_catID($delivery_line->stock_id) == 17 ? getCompDet('cos_free_item') : $stock_gl_code["cogs_account"],
						0,
						0,
						$delivery_line->stock_id, //Added by spyrax10
						($fa_purchase_cost - $fa_depreciation),
						PT_CUSTOMER,
						$delivery->customer_id,
						"The stock side of the cost of sales GL posting could not be inserted"
					);
				}
				// Invetory Items
				else if ($total_cost != 0) {
					/*first the cost of sales entry*/
					/*Added by Albert 04/10/2023 fix unbalance entry in delivery */
					$result = get_stock_moves_delivery_amount($delivery_no, $delivery_line->stock_id);
					
					while ($myrow = db_fetch($result)) {
						$total_cost = $myrow["standard_cost"];
					/**/

						add_gl_trans_std_cost(
							ST_CUSTDELIVERY,
							$delivery_no,
							$delivery->document_date,
							//Modified by spyrax10					//Ads & Promo Entry
							get_stock_catID($delivery_line->stock_id) == 17 ? getCompDet('cos_free_item') : $stock_gl_code["cogs_account"],
							$dim,
							$dim2,
							$delivery_line->stock_id, //Added by spyrax10
							// Updated by ronelle 11/11/2021
							$total_cost,
							PT_CUSTOMER,
							$delivery->customer_id,
							"The cost of sales GL posting could not be inserted"
						);

						/*now the stock entry*/
						// modified by albert 11/05/2021
						add_gl_trans_std_cost(
							ST_CUSTDELIVERY,
							$delivery_no,
							$delivery->document_date,
							$get_si_repo_acc,
							0,
							0,
							$delivery_line->stock_id, //Added by spyrax10
							// Updated by ronelle 11/11/2021
							($total_cost * -1),
							PT_CUSTOMER,
							$delivery->customer_id,
							"The stock side of the cost of sales GL posting could not be inserted"
						);
					}
					
				}
			} /* end of if GL and stock integrated and standard cost !=0 */
		} /*quantity dispatched is more than 0 */
	} /*end of order_line loop */

	if ($delivery->is_prepaid()) {
		$deferred_act = get_company_pref('deferred_income_act');
		add_gl_trans_customer(
			ST_CUSTDELIVERY,
			$delivery_no,
			$delivery->document_date,
			$deferred_act,
			0,
			0,
			-$total,
			$delivery->customer_id,
			"The deferred income GL posting could not be inserted"
		);
	}

	if ($bo_policy == 0) {
		// if cancelling any remaining quantities
		close_sales_order($delivery->order_no);
	}

	// taxes - this is for printing purposes
	foreach ($taxes as $taxitem) {
		if ($taxitem['Net'] != 0) {
			$ex_rate = get_exchange_rate_from_home_currency(get_customer_currency($delivery->customer_id), $delivery->document_date);
			add_trans_tax_details(
				ST_CUSTDELIVERY,
				$delivery_no,
				$taxitem['tax_type_id'],
				$taxitem['rate'],
				$delivery->tax_included,
				$taxitem['Value'],
				$taxitem['Net'],
				$ex_rate,
				$delivery->document_date,
				$delivery->reference,
				null
			);
		}
	}

	add_comments(ST_CUSTDELIVERY, $delivery_no, $delivery->document_date, $delivery->Comments);

	if ($trans_no == 0) {
		$Refs->save(ST_CUSTDELIVERY, $delivery_no, $delivery->reference);
	}
	hook_db_postwrite($delivery, ST_CUSTDELIVERY);
	commit_transaction();

	return $delivery_no;
}

//--------------------------------------------------------------------------------------------------
function adjust_shipping_charge(&$delivery, $trans_no)
{
	$sql = "SELECT sum(ov_freight) as freight FROM " . TB_PREF . "debtor_trans WHERE order_ = $trans_no AND type = " . ST_CUSTDELIVERY . " AND debtor_no = " . $delivery->customer_id;
	$result = db_query($sql, "Can not find delivery notes");
	$row = db_fetch_row($result);
	if (!$row[0]) $freight = 0;
	else $freight = $row[0];
	if ($freight < $delivery->freight_cost) $delivery->freight_cost = $delivery->freight_cost - $freight;
	else $delivery->freight_cost = 0;
}

//--------------------------------------------------------------------------------------------------

function void_sales_delivery($type, $type_no, $transactions = true)
{
	if ($transactions)
		begin_transaction();

	hook_db_prevoid($type, $type_no);

	void_gl_trans($type, $type_no, true);

	// reverse all the changes in the sales order
	$items_result = get_customer_trans_details($type, $type_no);

	$order = get_customer_trans_order($type, $type_no);

	if ($order) {
		$auto = (get_reference(ST_SALESORDER, $order) == "auto");
		while ($row = db_fetch($items_result)) {
			update_parent_line(ST_CUSTDELIVERY, $row['src_id'], -$row['quantity'], $auto);
		}
	}

	// clear details after they've been reversed in the sales order
	void_customer_trans_details($type, $type_no);

	void_trans_tax_details($type, $type_no);

	void_cust_allocations($type, $type_no);

	// do this last because other voidings can depend on it
	// DO NOT MOVE THIS ABOVE VOIDING or we can end up with trans with alloc < 0
	void_customer_trans($type, $type_no);

	if ($transactions)
		commit_transaction();
}
// /* Added by albert */
function get_serial_no($lot_no)
{

	$sql = "SELECT A.lot_no FROM " . TB_PREF . "stock_moves A WHERE A.type = 80 AND A.lot_no =" . db_escape($lot_no);

	$sql .= " AND A.lot_no =" . db_escape($lot_no);
	$sql .= " GROUP BY A.lot_no, trans_id";
	$sql .= " ORDER BY A.trans_id DESC LIMIT 1";

	$result = db_query($sql, "Can not find delivery notes");
	$row = db_fetch_row($result);

	return $row[0];
}

/* Added by Ronelle  */
function get_available_items($stock_id, $location, $date, $serial_no = "", $chassis_no = "") {
	
	//Modified by spyrax10 16 Aug 2022
	$sql = 
	"SELECT x1.trans_id, x1.trans_no, x1.type, x1.stock_id,
		x1.qty as InQty, x1.OutQty as OutQty,
		(x1.qty - x1.OutQty) as qoh, x1.category_id, x1.standard_cost, x1.tran_date
	FROM
		(SELECT a.trans_id, a.trans_no, a.stock_id, 
		    a.type, a.qty, a.category_id,
		    (SELECT IFNULL(SUM(t1.qty * -1), 0)
			FROM stock_moves t1
		    WHERE t1.transno_out = a.trans_no AND t1.type_out = a.type
				AND t1.stock_id = a.stock_id
				AND IFNULL(t1.lot_no, '') = IFNULL(a.lot_no, '') 
				AND IFNULL(t1.chassis_no, '') = IFNULL(a.chassis_no, '')
				AND t1.item_type = a.item_type AND t1.loc_code = a.loc_code
				AND t1.qty < 0
			) as OutQty, a.standard_cost, a.tran_date

		FROM stock_moves a 
		WHERE a.qty > 0 AND a.loc_code = " . db_escape($location) . 
			" AND a.tran_date < NOW() AND a.stock_id = " . db_escape($stock_id);
	
	if ($serial_no != "") {  
		$sql .= " AND a.lot_no = " . db_escape($serial_no);
	}

	if ($chassis_no != "") {
		$sql .= " AND a.chassis_no = " . db_escape($chassis_no);
	}
	
	$sql .= ") x1 WHERE x1.qty > x1.OutQty";

	$result = db_query($sql, "retrieve available items failed");
	return $result;
}
/*Added by Albert */
function get_gl_account_per_category($category_id)
{
	$sql = "SELECT 
				dflt_repo_invty_act 
			FROM " . TB_PREF . "stock_category 
			WHERE category_id = ". db_escape($category_id);

	$result = db_query($sql, "Can not find delivery notes");
	$row = db_fetch_row($result);

	return $row[0];
}

function get_stock_moves_delivery_amount($delivery_no, $stock_id)
{

	$sql = "SELECT DISTINCT A.standard_cost * (A.qty *-1) as standard_cost, A.trans_no, A.trans_id
	FROM " . TB_PREF . "stock_moves A WHERE A.type = 13 AND A.stock_id =" . db_escape($stock_id)." AND A.trans_no =" . db_escape($delivery_no);

	$result = db_query($sql, "Can not find delivery notes");
	return $result;
}
  