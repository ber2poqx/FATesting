<?php

//-------------------------------------------------------------------------------------------------------------

function add_grn_new(&$po, $is_consign = false)
{
	global $Refs;

	$date_ = $po->orig_order_date;

	begin_transaction();
	hook_db_prewrite($po, ST_SUPPRECEIVE);

	if (!is_company_currency($po->curr_code)) {
		if (!$po->ex_rate)
			$po->ex_rate = get_date_exchange_rate($po->curr_code, $date_);
		else
			add_new_exchange_rate($po->curr_code, $date_, $po->ex_rate);
	} else
		$po->ex_rate = 1;

	$grn = add_grn_batch(
		$po->order_no,
		$po->supplier_id,
		$po->reference,
		$po->Location,
		$date_,
		$po->ex_rate,
		$po->suppl_ref_no,
		$po->suppl_ref_date,
		$po->suppl_served_by,
		$po->Comments,
		$po->category_id
	);

	$clearing_act = get_company_pref('grn_clearing_act');
	if ($clearing_act) {	// otherwise GRN clearing account is not used
		$total = 0;
	}
	foreach ($po->line_items as $line_no => $order_line) {
		if ($order_line->receive_qty != 0 && $order_line->receive_qty != "" && isset($order_line->receive_qty)) {
			$stock_gl_code = get_stock_gl_code($order_line->stock_id);

			/*Update sales_order_details for the new quantity received and the standard cost used for postings to GL 
			  and recorded in the stock movements for FIFO/LIFO stocks valuations*/
			//------------------- update average material cost and clearing account --------------------------------
			if (is_inventory_item($order_line->stock_id)) {
				if ($clearing_act)
					$total += add_gl_trans_supplier(
						ST_SUPPRECEIVE,
						$grn,
						$date_,
						$stock_gl_code["inventory_account"],
						$stock_gl_code['dimension_id'],
						$stock_gl_code['dimension2_id'],
						$order_line->taxfree_charge_value($po),
						$po->supplier_id,
						'',
						0,
						$order_line->stock_id
					);
				update_average_material_cost(
					$po->supplier_id,
					$order_line->stock_id,
					$order_line->price,
					$order_line->receive_qty,
					$date_
				);
			}
			//------------------------------------------------------------------------------------------------------
			//if ($order_line->qty_received == 0)//Remove by Herald 03/15/2021
			//{
			/*This must be the first receipt of goods against this line */
			/*Need to get the standard cost as it is now so we can process GL jorunals later*/
			//			   	$order_line->standard_cost = get_unit_cost($order_line->stock_id);
			$order_line->standard_cost = Get_Policy_Cost($po->Location, $po->category_id, $order_line->stock_id, $po->supplier_id); //Updated by Herald 03/02/2021
			//}
			// Update the purchase data table
			add_or_update_purchase_data(
				$po->supplier_id,
				$order_line->stock_id,
				$order_line->price,
				$order_line->item_description
			);

			/*Need to insert a grn item */ // also need to check for over-receive.(within allowance)
			if ($order_line->receive_qty + $order_line->qty_received > $order_line->quantity)
				$order_line->quantity = $order_line->receive_qty + $order_line->qty_received;
			$grn_item = add_grn_detail_item(
				$grn,
				$order_line->po_detail_rec,
				$order_line->stock_id,
				$order_line->item_description,
				$order_line->standard_cost,
				$order_line->receive_qty,
				$order_line->price,
				$order_line->quantity,
				$order_line->color_code,
				$order_line->remarks
			);

			if (!$order_line->serialised) {
				$po->line_items[$line_no]->grn_item_id = $grn_item;
				add_stock_move(
					ST_SUPPRECEIVE,
					$order_line->stock_id,
					$grn,
					$po->Location,
					$date_,
					$po->reference,
					$order_line->receive_qty,
					$order_line->standard_cost,
					$order_line->taxfree_charge_value($po) / $order_line->receive_qty,
					'',
					'',
					$po->category_id,
					$order_line->stock_id
				);
			} else {
				$ctr = 0;
				while ($ctr < count($order_line->list_serial)) {
					add_stock_move(
						ST_SUPPRECEIVE,
						$order_line->stock_id,
						$grn,
						$po->Location,
						$date_,
						$po->reference,
						1,
						$order_line->standard_cost,
						$order_line->taxfree_charge_value($po) / 1,
						$order_line->list_serial[$ctr]->serial_no,
						$order_line->list_serial[$ctr]->chassis_no,
						$po->category_id,
						$order_line->stock_id
					);
					add_serialitems_detail(
						$grn,
						$grn_item,
						$order_line->list_serial[$ctr]->serial_no,
						1,
						"",
						"",
						$order_line->stock_id,
						$po->Location,
						$order_line->list_serial[$ctr]->chassis_no,
						ST_SUPPRECEIVE
					);
					$ctr++;
				}
			}
		} /*quantity received is != 0 */
	} /*end of order_line loop */

	if ($po->order_no == 0) {
		$clearing_act = get_company_pref('isa_employee');
	}

	if ($clearing_act && $total != 0.0) {
		$accs = get_supplier_accounts($po->supplier_id);
		$total += add_gl_trans_supplier(
			ST_SUPPRECEIVE,
			$grn,
			$date_,
			$clearing_act,
			$accs['dimension_id'],
			$accs['dimension2_id'],
			-$total,
			null
		);
	}
	$Refs->save(ST_SUPPRECEIVE, $grn, $po->reference);

	add_audit_trail(ST_SUPPRECEIVE, $grn, $date_);

	$po->trans_no = $grn;
	hook_db_postwrite($po, ST_SUPPRECEIVE);
	commit_transaction();

	return $grn;
}
