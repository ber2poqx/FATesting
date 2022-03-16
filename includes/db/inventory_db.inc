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
function get_qoh_on_date(
	$stock_id,
	$location = null,
	$date_ = null,
	$item_type = 'new'
) //Added by spyrax10
{
	if ($date_ == null)
		$date_ = Today();

	$date = date2sql($date_);
	$sql = "SELECT SUM(qty)
     	FROM " . TB_PREF . "stock_moves st
   		LEFT JOIN " . TB_PREF . "voided v ON st.type=v.type AND st.trans_no=v.id
          WHERE ISNULL(v.id)
          AND stock_id=" . db_escape($stock_id) . "
          AND item_type = '$item_type' 
          AND tran_date <= '$date'";

	if ($location != null)
		$sql .= " AND loc_code = " . db_escape($location);

	$result = db_query($sql, "QOH calculation failed");

	$myrow = db_fetch_row($result);

	$qoh =  $myrow[0];
	return $qoh ? $qoh : 0;
}

function get_qoh_on_date_repo($stock_id, $location = null, $date_ = null)
{
	if ($date_ == null)
		$date_ = Today();

	$date = date2sql($date_);
	$sql = "SELECT SUM(qty)
     	FROM " . TB_PREF . "stock_moves st
   		LEFT JOIN " . TB_PREF . "voided v ON st.type=v.type AND st.trans_no=v.id
          WHERE ISNULL(v.id)
          AND stock_id=" . db_escape($stock_id) . "
          AND item_type = 'repo'
          AND tran_date <= '$date'";

	if ($location != null)
		$sql .= " AND loc_code = " . db_escape($location);

	$result = db_query($sql, "QOH calculation failed");

	$myrow = db_fetch_row($result);

	$qoh =  $myrow[0];
	return $qoh ? $qoh : 0;
}

//Added by Robert 09/14/2021 for unit price refer to RR standard cost in inventory movement
function get_cost_on_date($stock_id, $location = null, $date_ = null)
{
	if ($date_ == null)
		$date_ = Today();

	$date = date2sql($date_);
	$sql = "SELECT SUM(qty) * standard_cost
     	FROM " . TB_PREF . "stock_moves sm
   		LEFT JOIN " . TB_PREF . "voided v ON sm.type=v.type AND sm.trans_no=v.id
          WHERE ISNULL(v.id)
          AND stock_id=" . db_escape($stock_id) . "
          AND item_type = 'new'
          AND tran_date <= '$date'";

	if ($location != null)
		$sql .= " AND loc_code = " . db_escape($location);

	$result = db_query($sql, "Cost calculation failed");

	$myrow = db_fetch_row($result);

	$coh =  $myrow[0];
	return $coh ? $coh : 0;
}

//Added by Robert 09/14/2021 for unit price refer to RR standard cost in inventory movement
function get_cost_on_date_repo($stock_id, $location = null, $date_ = null)
{
	if ($date_ == null)
		$date_ = Today();

	$date = date2sql($date_);
	$sql = "SELECT SUM(qty) * standard_cost
     	FROM " . TB_PREF . "stock_moves sm
   		LEFT JOIN " . TB_PREF . "voided v ON sm.type=v.type AND sm.trans_no=v.id
          WHERE ISNULL(v.id)
          AND stock_id=" . db_escape($stock_id) . "
          AND item_type = 'repo'
          AND tran_date <= '$date'";

	if ($location != null)
		$sql .= " AND loc_code = " . db_escape($location);

	$result = db_query($sql, "Cost calculation failed");

	$myrow = db_fetch_row($result);

	$coh =  $myrow[0];
	return $coh ? $coh : 0;
}

//Added by Herald 06/18/2021 for new QOH refer to RR standard cost
function get_qoh_on_date_new($type = 0, $transno = 0, $stock_id, $location = null, $date_ = null, 
	$lot = '') //Added by spyrax10
{
	if ($date_ == null)
		$date_ = Today();

	$date = date2sql($date_);
	$sql = "SELECT SUM(qty)
     	FROM " . TB_PREF . "stock_moves st
   		LEFT JOIN " . TB_PREF . "voided v ON st.type=v.type AND st.trans_no=v.id
          WHERE ISNULL(v.id)
          AND stock_id=" . db_escape($stock_id) . "
          AND type_out=" . db_escape($type) . "
          AND transno_out=" . db_escape($transno) . "
          AND tran_date <= '$date'";

	if ($location != null) {
		$sql .= " AND loc_code = " . db_escape($location);
	}

	//Added by spyrax10
	if ($lot != '') {
		$sql .= " AND lot_no = " . db_escape($lot);
	}
	//
		
	$sql .= " GROUP BY type_out, transno_out";

	//Added by spyrax10
	if ($lot != '') {
		$sql .= ", lot_no";
	}
	//

	$result = db_query($sql, "QOH calculation failed");

	$myrow = db_fetch_row($result);

	$qoh =  $myrow[0];
	return $qoh ? $qoh : 0;
}
/**
 *	Check whether change in stock on date would not cause negative qoh in stock history.
 *	Returns null on success or max. available quantity with respective date otherwise.
 *   Running balance is checked on daily basis only, as we do not control time of transaction.
 *
 *	$delta_qty - tested change in stock qty at $date.
 *	$date - check date; when set to null checks all the stock history.
 **/

function check_negative_stock($stock_id, $delta_qty, $location = null, $date = null, $serial = "", $chassis = "")
{

	if ($delta_qty >= 0)
		return null;	// qty increese is always safe

	if (!isset($date))
		$date = Today();

	$date = date2sql($date);

	/* Modified by Ronelle 4/6/2021 */
	$serialised = 0;
	global $def_coy;
	set_global_connection($def_coy);
	$check_serialised_sql = "SELECT serialised FROM stock_master WHERE stock_id=" . db_escape($stock_id);
	$check_serial = db_query($check_serialised_sql, "could not retreive the $stock_id");
	set_global_connection();
	if (db_num_rows($check_serial) == 1) {
		$row = db_fetch_row($check_serial);
		$serialised = $row[0];
	}
	/* */


	// check stock status on date
	$sql = "SELECT SUM(qty) qty, '$date' tran_date FROM " . TB_PREF . "stock_moves
            WHERE stock_id=" . db_escape($stock_id) . "
            AND tran_date <= '$date'";

	if ($location)
		$sql .= " AND loc_code = " . db_escape($location);

	if ($serialised == 1)
		$sql .= " AND lot_no = " . db_escape($serial) . " AND chassis_no =" . db_escape($chassis);

	$result = db_query($sql, "QOH calculation failed");
	$qos = db_fetch_assoc($result);

	// check also all stock changes after the date to avoid negative stock in future
	$sql = TB_PREF . "stock_moves WHERE stock_id=" . db_escape($stock_id) . " AND tran_date > '$date'";

	if ($location)
		$sql .= " AND loc_code=" . db_escape($location);

	//added by albert 09/27/2021	
	if ($serialised == 1)
		$sql .= " AND lot_no = " . db_escape($serial) . " AND chassis_no =" . db_escape($chassis);



	$rt = running_total_sql($sql, 'qty', 'tran_date');

	$sql = "SELECT  {$qos['qty']}+total qty, tran_date FROM ($rt) stock_status ORDER by total, tran_date";
	$history = db_query($sql, 'cannot check stock history');
	$min_qos = db_fetch($history);

	if ($min_qos && ($min_qos['qty'] < $qos['qty']))
		$qos = $min_qos;

	return  -$delta_qty > $qos['qty'] ? $qos : null;
}

//--------------------------------------------------------------------------------------

function get_item_edit_info($stock_id)
{
	$sql = "SELECT item.material_cost, item.units, unit.decimals, item.serialised, item.category_id
		FROM " . TB_PREF . "stock_master item,"
		. TB_PREF . "item_units unit
		WHERE stock_id=" . db_escape($stock_id)
		. " AND item.units=unit.abbr";
	$result = db_query($sql, "The standard cost cannot be retrieved");

	return db_fetch($result);
}

//--------------------------------------------------------------------------------------

function get_unit_cost($stock_id)
{
	$sql = "SELECT material_cost
		FROM " . TB_PREF . "stock_master
		WHERE stock_id=" . db_escape($stock_id);
	$result = db_query($sql, "The standard cost cannot be retrieved");

	$myrow = db_fetch_row($result);

	return $myrow[0];
}
//--------------------------------------------------------------------------------------

function get_purchase_cost($stock_id)
{
	$sql = "SELECT purchase_cost
		FROM " . TB_PREF . "stock_master
		WHERE stock_id=" . db_escape($stock_id);
	$result = db_query($sql, "The purchase cost cannot be retrieved");

	$myrow = db_fetch_row($result);

	return $myrow[0];
}

//--------------------------------------------------------------------------------------

function is_inventory_item($stock_id)
{
	$sql = "SELECT stock_id FROM "
		. TB_PREF . "stock_master
		WHERE stock_id=" . db_escape($stock_id) . " AND mb_flag <> 'D'";
	$result = db_query($sql, "Cannot query is inventory item or not");

	return db_num_rows($result) > 0;
}

//-------------------------------------------------------------------

function last_negative_stock_begin_date($stock_id, $to)
{
	$to = date2sql($to);
	$sql = "SET @q = 0";
	db_query($sql);
	$sql = "SET @flag = 0";
	db_query($sql);
	$sql = "SELECT SUM(qty), @q:= @q + qty, IF(@q < 0 AND @flag=0, @flag:=1,@flag:=0), IF(@q < 0 AND @flag=1, tran_date,'') AS begin_date 
		FROM " . TB_PREF . "stock_moves
		WHERE stock_id=" . db_escape($stock_id) . " AND tran_date<='$to' 
		AND qty <> 0
		GROUP BY stock_id ORDER BY tran_date";

	$result = db_query($sql, "The dstock moves could not be retrieved");
	$row = db_fetch_row($result);
	return $row[3];
}

//-------------------------------------------------------------------

function get_already_delivered($stock_id, $location, $trans_no)
{
	$sql = "SELECT qty
		FROM " . TB_PREF . "stock_moves
		WHERE stock_id = " . db_escape($stock_id) . "
		AND loc_code = " . db_escape($location) . "
		AND type=" . ST_CUSTDELIVERY . " AND trans_no=" . db_escape($trans_no);
	$result = db_query($sql, "Could not get stock moves");
	$row = db_fetch_row($result);
	return $row[0];
}
/*
	Returns start move_id in latest negative status period for $stock_id
	FIXME: $to ? 
*/
function last_negative_stock_trans_id($stock_id, $to)
{
	$sql = "SELECT * from " . TB_PREF . "stock_moves
		WHERE stock_id=" . db_escape($stock_id) . " 
		AND qty <> 0 order by trans_id asc";

	$result = db_query($sql, "The query on stock moves failed.");

	$qty = 0;
	$flag = 0;
	$negative_trans_id = -1;

	while ($myrow = db_fetch($result)) {
		$qty += $myrow['qty'];
		if ($qty < 0 && $flag == 0) {
			$flag = 1;
			$negative_trans_id = $myrow['trans_id'];
		}
		if ($qty >= 0)
			$flag = 0;
	}

	if ($flag == 1)
		return $negative_trans_id;
	else
		return false;
}

//-------------------------------------------------------------------

function get_deliveries_between($stock_id, $from, $to)
{
	$from = date2sql($from);
	$to = date2sql($to);
	$sql = "SELECT SUM(-qty), SUM(-qty*standard_cost) FROM " . TB_PREF . "stock_moves
		WHERE type=" . ST_CUSTDELIVERY . " AND stock_id=" . db_escape($stock_id) . " AND
			tran_date>='$from' AND tran_date<='$to' GROUP BY stock_id";

	$result = db_query($sql, "The deliveries could not be updated");
	return db_fetch_row($result);
}

/*
	Returns quantity and total cost of $stock_id sales, entered after record with $move_id
*/
function get_deliveries_from_trans($stock_id, $move_id)
{
	// -ve qty is delivery either by ST_CUSTDELIVERY or inventory adjustment
	//Price for GRN and SUPPCREDIT and std_cost for other trans_types
	$sql = "SELECT SUM(-qty), SUM(-qty*IF(type=" . ST_SUPPRECEIVE . " OR type=" . ST_SUPPCREDIT . ", price, standard_cost))
        FROM " . TB_PREF . "stock_moves
        WHERE stock_id=" . db_escape($stock_id) . " AND qty < 0 AND
            trans_id>='$move_id' GROUP BY stock_id";
	$result = db_query($sql, "The deliveries could not be updated");
	$row = db_fetch_row($result);

	$sql = "SELECT IF(type=" . ST_SUPPRECEIVE . " OR type=" . ST_SUPPCREDIT . ", price, standard_cost)
        FROM " . TB_PREF . "stock_moves
        WHERE stock_id=" . db_escape($stock_id)
		. " AND trans_id ='$move_id'";
	$result = db_query($sql, "The deliveries could not be updated");
	$cost = db_fetch_row($result);

	// Adjusting QOH valuation 
	$sql = "SELECT SUM(qty)
		FROM " . TB_PREF . "stock_moves
		WHERE stock_id=" . db_escape($stock_id) . " AND
			trans_id<'$move_id' GROUP BY stock_id";
	$result = db_query($sql, "The deliveries could not be updated");
	$qoh = db_fetch_row($result);

	$qty = $row[0] - $qoh[0]; //QOH prior to -ve stock is subtracted
	$final_cost = $row[1] - $qoh[0] * $cost[0];

	return array($qty, $final_cost);
}

/*
	Returns quantity and total cost of $stock_id purchases, entered after record with $move_id
*/
function get_purchases_from_trans($stock_id, $move_id)
{
	// Calculate All inward stock moves i.e. qty > 0
	$sql = "SELECT SUM(qty), SUM(qty*standard_cost)
		FROM " . TB_PREF . "stock_moves
		WHERE stock_id=" . db_escape($stock_id) . " AND qty > 0 AND 
			trans_id>'$move_id' GROUP BY stock_id";
	$result = db_query($sql, "Could not get get_purchases_from_trans");
	$row = db_fetch_row($result);

	return $row;
}

//-------------------------------------------------------------------
/*
	This routine fixes stock and COGS balances for all $stock_id sales made during negative inventory status.
	This is called when delivery is received causing inventory status to be positive again.
*/
function adjust_deliveries($stock_id, $material_cost, $to)
{
	global $Refs;

	if (!is_inventory_item($stock_id))
		return;

	$move_id = last_negative_stock_trans_id($stock_id, $to);
	if ($move_id == false || $move_id == -1)
		return;

	$row = get_deliveries_from_trans($stock_id, $move_id);

	if ($row == false)
		return;
	$old_sales_cost = $row[1];
	$new_sales_cost = $row[0] * $material_cost;
	$sales_diff = $new_sales_cost - $old_sales_cost;

	$row = get_purchases_from_trans($stock_id, $move_id);
	$purchase_diff = 0;
	$old_purchase_cost = $new_purchase_cost = 0;
	if ($row != false) {
		$old_purchase_cost = $row[1];
		$new_purchase_cost = $row[0] * $material_cost;
		$purchase_diff = $new_purchase_cost - $old_purchase_cost;
	}

	$diff =  $sales_diff - $purchase_diff;

	if ($diff != 0) {
		$stock_gl_code = get_stock_gl_code($stock_id);

		$dec = user_price_dec();
		$old_cost = -round2($old_sales_cost - $old_purchase_cost, $dec);
		$new_cost = -round2($new_sales_cost - $new_purchase_cost, $dec);

		$cart = new items_cart(ST_COSTUPDATE);
		$cart->tran_date = $cart->doc_date = $cart->event_date = $to;
		if (!is_date_in_fiscalyear($cart->tran_date))
			$cart->tran_date = end_fiscalyear();
		$cart->reference = $Refs->get_next(ST_COSTUPDATE, null, $cart->tran_date, $to);

		$cart->memo_ = _("Cost was ") . $old_cost . _(" changed to ") . $new_cost . _(" for item ") . "'$stock_id'";

		$cart->add_gl_item($stock_gl_code["cogs_account"], $stock_gl_code["dimension_id"], $stock_gl_code["dimension2_id"], $diff);
		$cart->add_gl_item($stock_gl_code["inventory_account"], 0, 0, -$diff);

		write_journal_entries($cart);
	}
}

function get_stock_gl_code($stock_id)
{
	/*Gets the GL Codes relevant to the item account  */
	//$sql = "SELECT stock_id, mb_flag, inventory_account, cogs_account, adjustment_account, sales_account, wip_account, dimension_id, dimension2_id FROM ".TB_PREF."stock_master WHERE stock_id = ".db_escape($stock_id);
	global $def_coy;
	set_global_connection($def_coy);

	$sql = "SELECT 
		stock_id, 
		mb_flag, 
		inventory_account, 
		cogs_account, 
		adjustment_account, 
		sales_account, 
		wip_account, 
		dimension_id, 
		dimension2_id, 
		installment_sales_account,
		regular_sales_account
		FROM " . TB_PREF . "stock_master WHERE stock_id = " . db_escape($stock_id);

	$get = db_query($sql, "retreive stock gl code");
	set_global_connection();
	return db_fetch($get);
}

function get_purchase_value($stock_id)
{
	$sql = "SELECT purchase_cost FROM
		" . TB_PREF . "stock_master WHERE stock_id = " . db_escape($stock_id);

	$result = db_query($sql, "retreive stock purchase price");
	$row = db_fetch_row($result);
	return $row[0];
}

function update_purchase_value($stock_id, $price)
{
	$price = round2($price, user_price_dec());
	$sql = "UPDATE " . TB_PREF . "stock_master SET purchase_cost=" . db_escape($price)
		. " WHERE stock_id=" . db_escape($stock_id);
	db_query($sql, "The stock master purchase_cost cannot be updated");
}
//-----------------------------------------------------------------------------------------

function handle_negative_inventory($stock_id, $quantity, $standard_cost, $date_)
{
	//If negative adjustment result in negative or zero inventory
	//then difference should be adjusted
	$qoh = get_qoh_on_date($stock_id);

	if ($qoh + $quantity <= 0 && $qoh > 0) //Positive inventory turning zero/negative
	{
		global $Refs;

		$id = get_next_trans_no(ST_JOURNAL);
		$ref = $Refs->get_next(ST_JOURNAL, null, $date_);
		$diff = round($qoh * get_unit_cost($stock_id) + $quantity * $standard_cost, user_price_dec());

		if ($diff != 0) {
			begin_transaction();
			add_journal(ST_JOURNAL, $id, $diff, $date_, get_company_currency(), $ref);
			$Refs->save(ST_JOURNAL, $id, $ref);

			$stock_gl_code = get_stock_gl_code($stock_id);
			$memo = _("Zero/negative inventory handling");
			//Reverse the inventory effect if $qoh <=0
			add_gl_trans_std_cost(
				ST_JOURNAL,
				$id,
				$date_,
				$stock_gl_code["inventory_account"],
				$stock_gl_code['dimension_id'],
				$stock_gl_code['dimension2_id'],
				$memo,
				-$diff
			);
			//GL Posting to inventory adjustment account
			add_gl_trans_std_cost(
				ST_JOURNAL,
				$id,
				$date_,
				$stock_gl_code["adjustment_account"],
				$stock_gl_code['dimension_id'],
				$stock_gl_code['dimension2_id'],
				$memo,
				$diff
			);

			add_audit_trail(ST_JOURNAL, $id, $date_);
			add_comments(ST_JOURNAL, $id, $date_, $memo);
			$Refs->save(ST_JOURNAL, $id, $ref);
			commit_transaction();
		}
	}
}

//--------------------------------------------------------------------------------------

// $date_ - display / non-sql date
// $std_cost - in HOME currency
// $price - in transaction currency

function add_stock_move($type, $stock_id, $trans_no, $location, $date_, $reference, $quantity, $std_cost, $price = 0, $lot_no = '', $chasis_no = '', $category = 0, $color_code = '', $type_out = 0, $transno_out = 0, $item_type = "new", $remarks = null)

{
	$date = date2sql($date_);
	//$man_date = date2sql($manufacture_date);
	//$exp_date = date2sql($expire_date);
	set_global_connection();
	if ($type_out == 0) {
		$type_out = $type;
	}
	if ($transno_out == 0) {
		$transno_out = $trans_no;
	}

	$sql = "INSERT INTO " . TB_PREF . "stock_moves (stock_id, trans_no, type, loc_code, tran_date, reference, qty, standard_cost,
				price, lot_no, chassis_no, category_id, color_code, type_out, transno_out, item_type, remarks
			) VALUES ("
		. db_escape($stock_id) . ", " . db_escape($trans_no) . ", " . db_escape($type) . ", " . db_escape($location) . ", '$date', "
		. db_escape($reference) . ", " . db_escape($quantity) . ", " . db_escape($std_cost) . "," . db_escape($price) . ", "
		. db_escape($lot_no) . ", " . db_escape($chasis_no) . ", " . db_escape($category) . ", " . db_escape($color_code) . ", "
		. db_escape($type_out) . ", " . db_escape($transno_out) . ", " . db_escape($item_type) . ", " . db_escape($remarks) . ")";

	db_query($sql, "The stock movement record cannot be inserted");

	return db_insert_id();
}
//Added by Herald 12/06/2021 - for Location Transfer approval
function add_stock_move_transfer_approval($type, $stock_id, $trans_no, $location, $date_, $reference, $quantity, $std_cost, $price = 0, $lot_no = '', $chasis_no = '', $category = 0, $color_code = '', $type_out = 0, $transno_out = 0, $item_type = "new", $remarks = null)

{
	$date = date2sql($date_);
	set_global_connection();
	if ($type_out == 0) {
		$type_out = $type;
	}
	if ($transno_out == 0) {
		$transno_out = $trans_no;
	}

	$sql = "INSERT INTO " . TB_PREF . "stock_moves (stock_id, trans_no, type, loc_code, tran_date, reference, qty, standard_cost, price, lot_no, chassis_no, category_id, color_code, type_out, transno_out, item_type, remarks, qty_approval) VALUES (" . db_escape($stock_id) . ", " . db_escape($trans_no) . ", " . db_escape($type) . ", " . db_escape($location) . ", '$date', " . db_escape($reference) . ", '0', " . db_escape($std_cost) . "," . db_escape($price) . ", " . db_escape($lot_no) . ", " . db_escape($chasis_no) . ", " . db_escape($category) . ", " . db_escape($color_code) . ", " . db_escape($type_out) . ", " . db_escape($transno_out) . ", " . db_escape($item_type) . ", " . db_escape($remarks) . ", " . db_escape($quantity) . ")";
	db_query($sql, "The stock movement record cannot be inserted");

	return db_insert_id();
}

//Added by spyrax10
function add_stock_adjust(
	$type,
	$stock_id,
	$trans_no,
	$line_id,
	$location,
	$date_,
	$reference,
	$quantity,
	$std_cost,
	$price = 0,
	$lot_no = '',
	$chasis_no = '',
	$category = 0,
	$color_code = '',
	$adj_type = "IN",
	$status = 'Draft',
	$memo = '',
	$approver = '',
	$date_approved = "0000-00-00",
	$comments = '',
	$trans_no_out = 0,
	$trans_type_out = 0,
	$item_type = "new"
) {
	$date = date2sql($date_);
	set_global_connection();

	$quantity = $adj_type == "OUT" ? -$quantity : $quantity;

	$sql = "INSERT INTO " . TB_PREF . "stock_adjustment (stock_id, trans_no, line_id, type, loc_code, tran_date, reference, qty, standard_cost,
				price, lot_no, chassis_no, category_id, color_code, adjustment_type, status, memo, approver, date_approved, comments, trans_no_out, trans_type_out,
				item_type) 
			VALUES ("
		. db_escape($stock_id) . ", " . db_escape($trans_no) . ", " . db_escape($line_id) . ", " . db_escape($type) . ", " . db_escape($location) . ", '$date', "
		. db_escape($reference) . ", " . db_escape($quantity) . ", " . db_escape($std_cost) . "," . db_escape($price) . ", "
		. db_escape($lot_no) . ", " . db_escape($chasis_no) . ", " . db_escape($category) . ", " . db_escape($color_code) . ", "
		. db_escape($adj_type) . ", " . db_escape($status) . ", " . db_escape($memo) . ", " . db_escape($approver) . ", " . db_escape($date_approved) . ", "
		. db_escape($comments) . ", " . db_escape($trans_no_out) . ", " . db_escape($trans_type_out) . ", " . db_escape($item_type) . ")";

	db_query($sql, "The stock adjustment record cannot be inserted! (spyrax10)");

	return db_insert_id();
}

function update_smo_serial($trans_id, $trans_no, $type, $lot_no, $chassis_no)
{

	$sql = " UPDATE " . TB_PREF . "stock_moves 
		SET lot_no = '$lot_no', chassis_no = '$chassis_no'
		WHERE trans_no = " . db_escape($trans_no) . " AND type = " . db_escape($type) . "AND trans_id = " . db_escape($trans_id);

	set_global_connection();
	db_query($sql, "The stock movement serial details can't be updated! (spyrax10)");
}

function add_adj_gl(
	$trans_no,
	$line_id,
	$adj_type,
	$reference,
	$stock_id,
	$color_code = '',
	$lot_no = '',
	$chasis_no = '',
	$amount,
	$mcode = '',
	$masterfile = '',
	$account = null,
	$gl_type = '',
	$item_type = "new"
) {
	set_global_connection();

	$mcode = sprintf(' %05d', $mcode);

	$sql = "INSERT INTO " . TB_PREF . "stock_adjustment_gl (sa_trans_no, sa_line_id, sa_adj_type, sa_reference, 
		stock_id, color_code, lot_no, chassis_no, gl_type, account, amount, mcode, master_file, sa_adj_item) VALUES (
			" . db_escape($trans_no) . ", " . db_escape($line_id) . ", " . db_escape($adj_type) . ", " . db_escape($reference) . ", 
			" . db_escape($stock_id) . ", " . db_escape($color_code) . ", " . db_escape($lot_no) . ", " . db_escape($chasis_no) . ", 
			" . db_escape($gl_type) . ", " . db_escape($account) . ", " . db_escape($amount) . ", " . db_escape($mcode) . ", 
			" . db_escape($masterfile) . ", " . db_escape($item_type) . "
		)";

	db_query($sql, "Cannot add adjustment gl! (spyrax10)");
}

//

function update_stock_move($type, $trans_no, $stock_id, $cost)
{
	$sql = "UPDATE " . TB_PREF . "stock_moves SET standard_cost=" . db_escape($cost)
		. " WHERE type=" . db_escape($type)
		. "	AND trans_no=" . db_escape($trans_no)
		. "	AND stock_id=" . db_escape($stock_id);
	db_query($sql, "The stock movement standard_cost cannot be updated");
}

//--------------------------------------------------------------------------------------------------

function get_stock_moves($type, $type_no)
{
	/* $sql = "SELECT move.*, CONCAT_WS(' **',item.description,item) as description, item.mb_flag, item.units, stock.location_name
		FROM ".TB_PREF."stock_moves move,"
			.TB_PREF."locations stock,"
			.TB_PREF."stock_master item
		WHERE move.stock_id = item.stock_id
		AND stock.loc_code=move.loc_code
		AND type=".db_escape($type)
		." AND trans_no=".db_escape($type_no)
		." ORDER BY trans_id"; */
	//LEFT JOIN " . TB_PREF . "item_codes icode ON move.stock_id=icode.stock_id AND move.color_code=icode.item_code

	set_global_connection();
	$sql = "SELECT move.*, item.description as description, item.mb_flag, item.units, stock.location_name,brand.name as brand_name, man.name as manufacturer_name,d.name as distributor_name, importer.name as importer_name, item.category_id, (SELECT description FROM " . TB_PREF . "item_codes icode WHERE icode.item_code=move.color_code) as color_desc FROM " . TB_PREF . "stock_moves move 
	INNER JOIN stock_master item ON move.stock_id = item.stock_id
	INNER JOIN locations stock ON stock.loc_code=move.loc_code
	LEFT JOIN item_brand brand ON item.brand=brand.id
	LEFT JOIN item_manufacturer man ON item.manufacturer=man.id
	LEFT JOIN item_distributor d ON item.distributor=d.id
	LEFT JOIN item_importer importer ON item.importer=importer.id
	WHERE type=" . db_escape($type) . " AND trans_no=" . db_escape($type_no) . " ORDER BY trans_id";

	return db_query($sql, "Could not get stock moves");
}

//Added by spyrax10
function get_stock_adjust($type, $type_no)
{
	set_global_connection();
	$sql = "SELECT move.*, item.description as description, item.mb_flag, item.units, stock.location_name,brand.name as brand_name, man.name as manufacturer_name,d.name as distributor_name, importer.name as importer_name, item.category_id, 
			(SELECT description FROM item_codes icode WHERE icode.item_code=move.color_code) as color_desc 
		FROM stock_adjustment move 

	INNER JOIN stock_master item ON move.stock_id = item.stock_id
	INNER JOIN locations stock ON stock.loc_code=move.loc_code
	LEFT JOIN item_brand brand ON item.brand=brand.id
	LEFT JOIN item_manufacturer man ON item.manufacturer=man.id
	LEFT JOIN item_distributor d ON item.distributor=d.id
	LEFT JOIN item_importer importer ON item.importer=importer.id
	WHERE type=" . db_escape($type) . " AND trans_no=" . db_escape($type_no) . " ORDER BY trans_id";

	return db_query($sql, "Could not get stock adjustments! (spyrax10)");
}

//--------------------------------------------------------------------------------------------------

function void_stock_move($type, $type_no)
{
	set_global_connection();
	$sql = "SELECT move.*, supplier.supplier_id
    		FROM " . TB_PREF . "stock_moves move
				LEFT JOIN " . TB_PREF . "supp_trans credit ON credit.trans_no=move.trans_no AND credit.type=move.type
				LEFT JOIN " . TB_PREF . "grn_batch grn ON grn.id=move.trans_no AND 25=move.type
				LEFT JOIN " . TB_PREF . "suppliers supplier ON IFNULL(grn.supplier_id, credit.supplier_id)=supplier.supplier_id
			WHERE move.type=" . db_escape($type) . " AND move.trans_no=" . db_escape($type_no);

	$result = db_query($sql, "Could not void stock moves");
	while ($row = db_fetch($result)) {
		//Skip cost averaging of service items
		if (is_inventory_item($row["stock_id"])) {
			// The cost has to be adjusted.
			// Transaction rates are stored either as price or standard_cost depending on types
			$types = array(ST_SUPPCREDIT, ST_SUPPRECEIVE);
			if (in_array($type, $types))
				$unit_cost = $row["price"];
			else
				$unit_cost = $row["standard_cost"];

			update_average_material_cost(
				$row["supplier_id"],
				$row["stock_id"],
				$unit_cost,
				-$row["qty"],
				sql2date($row["tran_date"])
			);
		}
	}

	if ($type == ST_MERCHANDISETRANSFER) {
		$row = get_stock_merchandise_transfer($type_no);
		global $def_coy;
		set_global_connection($def_coy);

		$sql = "DELETE FROM " . TB_PREF . "mt_header
    			WHERE mt_header_reference=" . db_escape($row["reference"]);
		db_query($sql, "The MT Transaction cannot be deleted");
		set_global_connection();
	}
	if ($type == ST_MERCHANDISETRANSFERREPO) {
	    $row = get_stock_merchandise_transfer_repo($type_no);
	    global $def_coy;
	    set_global_connection($def_coy);
	    
	    $sql = "DELETE FROM " . TB_PREF . "mt_header
    			WHERE mt_header_reference=" . db_escape($row["reference"]);
	    db_query($sql, "The MT Transaction cannot be deleted");
	    set_global_connection();
	}
	
	$sql = "DELETE FROM " . TB_PREF . "stock_moves
			WHERE type=" . db_escape($type)
		. "	AND trans_no=" . db_escape($type_no);
	db_query($sql, "The stock movement cannot be delated");
}

//--------------------------------------------------------------------------------------------------

function get_location_name($loc_code)
{
	$sql = "SELECT location_name FROM " . TB_PREF . "locations
		WHERE loc_code=" . db_escape($loc_code);

	$result = db_query($sql, "could not retreive the location name for $loc_code");

	if (db_num_rows($result) == 1) {
		$row = db_fetch_row($result);
		return $row[0];
	}

	display_db_error("could not retreive the location name for $loc_code", $sql, true);
}

function get_mb_flag($stock_id)
{
	$sql = "SELECT mb_flag FROM " . TB_PREF . "stock_master
		WHERE stock_id = " . db_escape($stock_id);
	$result = db_query($sql, "retreive mb_flag from item");

	if (db_num_rows($result) == 0)
		return -1;

	$myrow = db_fetch_row($result);
	return $myrow[0];
}

/* Added by Ronelle 10/15/2020 */

function get_color_description($item_code, $stock_id)
{
	global $def_coy;
	set_global_connection($def_coy);
	$sql = "SELECT description as ColorDesc FROM " . TB_PREF . "item_codes 
		WHERE item_code = " . db_escape($item_code) . " AND stock_id = " . db_escape($stock_id) . " ";

	$result = db_query($sql, "could not retreive the color description for $item_code");
	set_global_connection();
	if (db_num_rows($result) == 1) {
		$row = db_fetch_row($result);
		return $row[0];
	} else {
		return "";
	}
}
/*Added by Albert*/
function get_color_description_opening($description, $stock_id)
{
	global $def_coy;
	set_global_connection($def_coy);
	$sql = "SELECT description as ColorDesc FROM " . TB_PREF . "item_codes 
		WHERE description like " . db_escape($description) . " AND stock_id like " . db_escape("%" . $stock_id . "%") . " ";

	$result = db_query($sql, "could not retreive the color description for $stock_id");
	set_global_connection();
	//Modified by spyrax10
	if (db_num_rows($result) == 1) {
		$row = db_fetch_row($result);
		return true;
	} else {
		return false;
	}
	//
}
function add_gl_trans_mtbranch(
	$type,
	$type_no,
	$date_,
	$account,
	$dimension,
	$dimension2,
	$amount,
	$err_msg = "",
	$rate = 0,
	$memo = ""
) {
	if ($err_msg == "")
		$err_msg = "The MT GL transaction could not be inserted";

	return add_gl_trans(
		$type,
		$type_no,
		$date_,
		$account,
		$dimension,
		$dimension2,
		$memo,
		$amount,
		null,
		null,
		null,
		$err_msg,
		$rate
	);
}
/* */

//Added by spyrax10
function get_available_item_for_inty(
	$category_id,
	$serialized = 0,
	$search_value = "",
	$adj_type = 1,
	$loc_code = "",
	$supp_id,
	$item_type
) {

	$sql = "";

	if ($adj_type == 2) {

		$sql = "SELECT 
			a.reference,
			e.name as brand,
			a.stock_id, 
			b.description, 
			a.color_code, 
			a.lot_no as serialeng_no, 
			a.chassis_no as chassis_no,
			a.trans_no, a.type_out
			/*d.description as category, 
			a.standard_cost,
			a.item_type, a.trans_no, a.type, a.type_out,
			b.units, a.qty as quantity*/

			FROM " . TB_PREF . "stock_moves a 
				INNER JOIN " . TB_PREF . "stock_master b ON b.stock_id = a.stock_id 
				INNER JOIN " . TB_PREF . "stock_category d ON d.category_id = b.category_id 
				INNER JOIN " . TB_PREF . "item_brand e ON e.id = b.brand 

			WHERE 
				b.serialised = $serialized AND a.item_type = '$item_type' AND a.category_id =" . db_escape($category_id);


		if ($serialized == 1 && $category_id == 14) {
			$sql .= " AND (SELECT SUM(t1.qty) FROM " . TB_PREF . "stock_moves t1 
				WHERE t1.stock_id = a.stock_id AND t1.loc_code = '$loc_code' AND t1.item_type = '$item_type' 
					AND t1.lot_no = a.lot_no AND t1.chassis_no = a.chassis_no) > 0 ";
		} 
		else if ($serialized == 1) {
			$sql .= " AND (SELECT SUM(t1.qty) FROM " . TB_PREF . "stock_moves t1 
				WHERE t1.stock_id = a.stock_id AND t1.loc_code = '$loc_code' AND t1.item_type = '$item_type' 
					AND t1.lot_no = a.lot_no) > 0 ";
		}
		else if ($serialized == 0) {
			$sql .= " AND (SELECT SUM(t1.qty) FROM " . TB_PREF . "stock_moves t1 
				WHERE t1.stock_id = a.stock_id AND t1.loc_code = '$loc_code' AND t1.item_type = '$item_type' ) > 0 ";
		}

		if ($search_value != "") {
			$sql .= " AND (
				a.lot_no LIKE " . db_escape('%' . trim($search_value) . '%') . " OR 
				a.chassis_no LIKE " . db_escape('%' . trim($search_value) . '%') . " OR 
				a.stock_id LIKE " . db_escape('%' . trim($search_value) . '%') . " OR 
				b.description LIKE " . db_escape('%' . trim($search_value) . '%') . ")";
		}

		if ($serialized == 1) {
			$sql .= " GROUP BY a.stock_id, a.lot_no, a.chassis_no ";
		} else if ($serialized == 0) {
			$sql .= " GROUP BY a.stock_id ";
		}
		set_global_connection();

	} else {

		global $def_coy;

		/*
		$coy =  user_company();
		$ho_dbname = get_company_value(0, 'dbname');
		$branch_dbname = get_company_value($coy, 'dbname');
		*/

		$sql = "SELECT 
				e.name as brand,
				a.stock_id, 
				a.description, 
				b.item_code as color_code,
				'' as serialeng_no,
				'' as chassis_no

				FROM " . TB_PREF . "stock_master a 
					INNER JOIN " . TB_PREF . "stock_category d ON d.category_id = a.category_id
					LEFT JOIN " . TB_PREF . "item_brand e ON e.id = a.brand  
					LEFT JOIN " . TB_PREF . "item_codes b ON a.stock_id = b.stock_id 
					LEFT JOIN " . TB_PREF . "suppliers SUP ON e.name = SUP.supp_ref
				
				WHERE 
				a.serialised = $serialized AND a.category_id =" . db_escape($category_id);

		if ($search_value != "") {
			$sql .= " AND ( 
				a.stock_id LIKE " . db_escape('%' . trim($search_value) . '%') . " OR 
				b.description LIKE " . db_escape('%' . trim($search_value) . '%') . ")";
		}

		if ($supp_id != '') {
			$sql .= " AND SUP.supplier_id = " . db_escape($supp_id);
		}

		$sql .= " GROUP BY a.stock_id ";
		set_global_connection($def_coy); 
	}

	//$result = db_query($sql, "Could not retreive Inventory items! (spyrax10)");
	return $sql;
}
//

/* Added by Ronelle 2/26/2021 */
function get_available_item_for_si(
	$category_id,
	$serialized = 0,
	$promo_item = 0,
	$search_value = "",
	$loc_code = ""
) //Added by spyrax10
{
	//Modified by spyrax10
	if ($loc_code == "") {
		$loc_code = getCompDet('branch_code');
	}

	$sql = "";
	if ($serialized == 0) {
		global $def_coy;
		set_global_connection($def_coy);
		$sql = "SELECT
					a.units,
					a.stock_id,
					a.description,
					b.description as category,
					c.name as brand
				FROM " . TB_PREF . "stock_master a
				
				INNER JOIN " . TB_PREF . "stock_category b ON b.category_id = a.category_id
				LEFT JOIN " . TB_PREF . "item_brand c ON c.id = a.brand

				WHERE a.serialised = 0 AND
					a.category_id =" . db_escape($category_id);


		if ($search_value != "") {
			$sql .= " AND (
				a.stock_id LIKE " . db_escape('%' . trim($search_value) . '%') . " OR 
				a.description LIKE " . db_escape('%' . trim($search_value) . '%') . ")"; //modified by spyrax10
		}

		$sql .= " GROUP BY a.stock_id";
	} else {
		/* Modified by Ronelle 11/11/2021 */
		$sql = "SELECT
					x1.trans_id,
					x1.reference,
					x1.units,
					x1.stock_id,
					x1.description,
					x1.color_code,
					x1.lot_no as serialeng_no,
					x1.chassis_no,
					x1.category,
					x1.brand,
					x1.trans_no,
					x1.type,
					(x1.qty - x1.OutQty) as qoh,
					x1.standard_cost
				FROM
				(SELECT
					a.trans_id,
					a.reference,
					b.units,
					a.stock_id,
					b.description,
					a.color_code,
					a.lot_no,
					a.chassis_no,
					c.description as category,
					d.name as brand,
					a.trans_no,
					a.type,
					a.qty,
					(SELECT 
						ifnull(SUM(t1.qty * -1), 0)
					FROM
						stock_moves t1
					WHERE
						t1.transno_out = a.trans_no
					AND
						t1.type_out = a.type
					AND
                     	t1.stock_id = a.stock_id
                    AND
                     	t1.lot_no = a.lot_no
                    AND
                     	t1.chassis_no = a.chassis_no
                    AND
                     	t1.loc_code = a.loc_code
					AND
						t1.item_type = a.item_type
					AND
						t1.qty < 0
					) as OutQty,
					a.standard_cost,
					a.tran_date
				FROM
					stock_moves a
				INNER JOIN
					stock_master b
				ON
					b.stock_id = a.stock_id AND b.serialised = 1
				INNER JOIN
					stock_category c
				ON
					c.category_id = b.category_id
				LEFT JOIN
					item_brand d
				ON
					d.id = b.brand
				WHERE
					a.qty > 0
				AND
					a.loc_code = " . db_escape($loc_code) . "
				AND
					b.category_id = $category_id
				) x1
				WHERE
					x1.type <> 80 AND
					x1.qty > x1.OutQty > 0";

		if ($search_value != "") {
			$sql .= " AND (
						x1.lot_no LIKE " . db_escape('%' . trim($search_value) . '%') . " OR 
						x1.chassis_no LIKE " . db_escape('%' . trim($search_value) . '%') . " OR 
						x1.stock_id LIKE " . db_escape('%' . trim($search_value) . '%') . " OR 
						x1.description LIKE " . db_escape('%' . trim($search_value) . '%') . ")";
		}
		$sql .= " GROUP BY x1.trans_id";
		/* */
	}

	if ($promo_item == 1) {
		global $def_coy;
		set_global_connection($def_coy);
		$sql = "SELECT
					a.units,
					a.stock_id,
					a.description,
					b.description as category,
					c.name as brand
				FROM
					" . TB_PREF . "stock_master a
				INNER JOIN
					" . TB_PREF . "stock_category b
				ON
					b.category_id = a.category_id
				LEFT JOIN
					" . TB_PREF . "item_brand c
				ON
					c.id = a.brand
				WHERE
					a.category_id = 17";
		if ($search_value != "") {
			$sql .= " AND (
				a.stock_id LIKE " . db_escape('%' . trim($search_value) . '%') . " OR 
				a.description LIKE " . db_escape('%' . trim($search_value) . '%') . ")"; //modified by spyrax10
		}
	}
	$result = db_query($sql, "could not retreive the items");
	set_global_connection();
	return $result;
}
/* */
/* Added by Albert 10/14/2021 */
function get_available_item_for_si_repo(
	$category_id,
	$serialized = 0,
	$promo_item = 0,
	$search_value = "",
	$loc_code = ""
) //Added by spyrax10
{
	//Modified by spyrax10
	if ($loc_code == "") {
		$loc_code = getCompDet('branch_code');
	}

	$sql = "";
	if ($serialized == 0) {
		//global $def_coy;
		//set_global_connection($def_coy);
		$sql = "SELECT
					a.units,
					a.stock_id,
					a.description,
					b.description as category,
					c.name as brand
				FROM " . TB_PREF . "stock_master a
				
				INNER JOIN " . TB_PREF . "stock_category b ON b.category_id = a.category_id
				LEFT JOIN " . TB_PREF . "item_brand c ON c.id = a.brand
				LEFT JOIN " . TB_PREF . "stock_moves d ON a.stock_id = d.stock_id AND d.qty > 0

				WHERE a.serialised = 0 AND d.loc_code = '$loc_code' AND
					a.category_id =" . db_escape($category_id);


		if ($search_value != "") {
			$sql .= " AND (
				a.stock_id LIKE " . db_escape('%' . trim($search_value) . '%') . " OR 
				a.description LIKE " . db_escape('%' . trim($search_value) . '%') . ")"; //modified by spyrax10
		}
	} else {

		$sql = "SELECT 
		(SELECT z1.reference 
			 FROM stock_moves z1 
				 WHERE z1.stock_id = a.stock_id 
					 AND z1.lot_no = a.lot_no 
					 AND z1.chassis_no = a.chassis_no
		ORDER BY z1.trans_id DESC
		LIMIT 1) as reference,
			b.units, 
			a.stock_id, 
			b.description, 
			a.color_code, 
			a.lot_no as serialeng_no, 
			a.chassis_no as chassis_no, 
			d.description as category, 
			e.name as brand, 
			a.qty as quantity,
			a.trans_no as stock_trans_no,
			a.type as stock_trans_type
			FROM stock_moves a 
			INNER JOIN stock_master b 
				ON b.stock_id = a.stock_id 
			INNER JOIN stock_category d 
				ON d.category_id = b.category_id 
			INNER JOIN item_brand e 
				ON e.id = b.brand 
			WHERE 
				a.type=80 AND
				b.serialised = 1 AND 
				b.category_id = $category_id AND 
				(SELECT SUM(t1.qty) 
					 FROM stock_moves t1 
						 WHERE 
							 t1.stock_id = a.stock_id AND t1.loc_code = '$loc_code' AND 
							 t1.lot_no = a.lot_no AND t1.chassis_no = a.chassis_no) > 0 ";

		if ($search_value != "") {

			$sql .= " AND (
						a.lot_no LIKE " . db_escape('%' . trim($search_value) . '%') . " OR 
						a.chassis_no LIKE " . db_escape('%' . trim($search_value) . '%') . " OR 
						a.stock_id LIKE " . db_escape('%' . trim($search_value) . '%') . " OR 
						b.description LIKE " . db_escape('%' . trim($search_value) . '%') . ")";
		}
		//Added by spyrax10
		$sql .= " GROUP BY a.stock_id, a.chassis_no, a.lot_no";
	}

	if ($promo_item == 1) {
		//global $def_coy;
		//set_global_connection($def_coy);
		$sql = "SELECT
					a.units,
					a.stock_id,
					a.description,
					b.description as category,
					c.name as brand
				FROM
					" . TB_PREF . "stock_master a
				INNER JOIN
					" . TB_PREF . "stock_category b
				ON
					b.category_id = a.category_id
				LEFT JOIN
					" . TB_PREF . "item_brand c
				ON
					c.id = a.brand

				LEFT JOIN " . TB_PREF . "stock_moves d ON a.stock_id = d.stock_id AND d.qty > 0

				WHERE
					a.category_id = 17 AND d.loc_code = '$loc_code'";
		if ($search_value != "") {
			$sql .= " AND (
				a.stock_id LIKE " . db_escape('%' . trim($search_value) . '%') . " OR 
				a.description LIKE " . db_escape('%' . trim($search_value) . '%') . ")"; //modified by spyrax10
		}
	}
	$result = db_query($sql, "could not retreive the items");
	set_global_connection();
	return $result;
}
/* Added by Ronelle 5/31/2021  */
function get_dr_cost_from_so($so_no)
{
	$sql = "SELECT sum(ov_amount) as dr_cost
		FROM " . TB_PREF . "debtor_trans
		WHERE reference = 'auto' AND order_=" . db_escape($so_no);
	$result = db_query($sql, "The dr cost cannot be retrieved");

	$myrow = db_fetch_row($result);

	return $myrow[0];
}
/* */

/* Retrieve GL Account by Category Added by Ronelle 7/23/2021 */
function get_gl_code_from_category($category_id)
{
	$sql = "SELECT 
		dflt_sales_act,
		dflt_installment_sales_act,
		dflt_regular_sales_act
		FROM " . TB_PREF . "stock_category WHERE category_id = " . db_escape($category_id);

	$get = db_query($sql, "retreive category gl code");
	set_global_connection();
	return db_fetch($get);
}
/* */

// Added by spyrax10

function get_location_list() {
	
	set_global_connection();
	$cur_branch = getCompDet('branch_code');

	$sql = "SELECT loc_code, location_name 
		FROM " . TB_PREF . "locations 
		WHERE inactive = 0 
		ORDER BY FIELD(loc_code, '$cur_branch') DESC ";
	
	return db_query($sql, _("Cant get location list!"));
}

function get_category_list() {

	set_global_connection();

	$sql = "SELECT SC.category_id, SC.description 
		FROM ".TB_PREF."stock_category SC
		WHERE SC.inactive = 0 ";

	return db_query($sql, _("Cant get category list!"));
}

function get_brand_list($brand_name = '', $all = true) {

	set_global_connection();

	$sql = "SELECT IB.id, IB.name 
		FROM ".TB_PREF."item_brand IB
		WHERE inactive = 0 ";
	
	if ($brand_name != '') {
		$sql .= " AND IB.name = " .db_escape($brand_name);
	}

	$sql .= " ORDER BY IB.name ";

	$result = db_query($sql, _("Cant get brand list!"));

	if ($all) {
		return $result;
	}
	else {
		return db_fetch_assoc($result);
	}

}

function get_color_list($stock_id = '') {
	
	set_global_connection(0);

	$sql = "SELECT a.item_code, CONCAT(a.description, ' - ',a.color) AS ColorDesc
		FROM " . TB_PREF ."item_codes a
		WHERE a.inactive = 0 AND a.is_foreign = 1";

	if ($stock_id != '') {
		$sql .= " AND a.stock_id = " . db_escape($stock_id);
	}

	return db_query($sql, _("Cant get stock color list!"));
}

function get_stock_description($stock_id)
{
	$sql = "SELECT A.description
		FROM " . TB_PREF . "stock_master A
		WHERE A.stock_id= " . db_escape($stock_id);
	$sql .= " GROUP BY A.stock_id";

	$result = db_query($sql, "Can't get stock description! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}

function check_item_brand($brand)
{
	$sql = "SELECT name 
		FROM ".TB_PREF."item_brand
		WHERE name =".db_escape($brand);

	$result = db_query($sql, "Check Item Brand failed");

	if (db_num_rows($result) > 0) {
		return true;
	}
	else {
		return false;
	}
}

function get_brand_id($stock_id)
{
	$sql = "SELECT A.brand
		FROM " . TB_PREF . "stock_master A
		WHERE A.stock_id= " . db_escape($stock_id);
	$sql .= " GROUP BY A.stock_id";

	$result = db_query($sql, "Can't get item_brand! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_brand_name($brand_id)
{
	$sql = "SELECT B.supp_name
		FROM " . TB_PREF . "item_brand A
		LEFT JOIN " . TB_PREF . "suppliers B ON A.name = B.supp_ref
		WHERE A.id= " . db_escape($brand_id);

	$result = db_query($sql, "Can't get brand_name! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_smo_trans_no($stock_id, $serial = '')
{
	$sql = "SELECT A.trans_no as stock_trans_no 
		FROM " . TB_PREF . "stock_moves A
		WHERE A.qty > 0 AND A.stock_id=" . db_escape($stock_id);

	if ($serial != '') {
		$sql .= " AND A.lot_no =" . db_escape($serial);
		$sql .= " GROUP BY A.stock_id, A.lot_no, A.chassis_no, trans_id";
		$sql .= " ORDER BY trans_id DESC LIMIT 1";
	} else {
		$sql .= " GROUP BY A.stock_id, trans_id";
		$sql .= " ORDER BY trans_id DESC LIMIT 1";
	}

	$result = db_query($sql, "SMO trans_no cant be retrived! (spyrax10)");

	if (db_num_rows($result) == 1) {
		$row = db_fetch_row($result);
		return $row[0];
	} else {
		return 0;
	}
}

function get_smo_trans_type($stock_id, $serial = '')
{
	$sql = "SELECT A.type as stock_trans_type 
		FROM " . TB_PREF . "stock_moves A
		WHERE A.qty > 0 AND A.stock_id=" . db_escape($stock_id);

	if ($serial != '') {
		$sql .= " AND A.lot_no =" . db_escape($serial);
		$sql .= " GROUP BY A.stock_id, A.lot_no, A.chassis_no, trans_id";
		$sql .= " ORDER BY trans_id DESC LIMIT 1";
	} else {
		$sql .= " GROUP BY A.stock_id, trans_id";
		$sql .= " ORDER BY trans_id DESC LIMIT 1";
	}

	$result = db_query($sql, "SMO trans_type cant be retrived! (spyrax10)");

	if (db_num_rows($result) == 1) {
		$row = db_fetch_row($result);
		return $row[0];
	} else {
		return 0;
	}
}

function sup_code_exist($supp_code) {

	$sql = "SELECT supp_ref 
		FROM ".TB_PREF."suppliers
	WHERE supp_ref =".db_escape($supp_code);

	$result = db_query($sql, "Check Supplier Code failed");

	if (db_num_rows($result) > 0) {
		return true;
	}
	else {
		return false;
	}
}

function get_supplier_id_code($supp_code) {

	$sql = "SELECT supplier_id 
		FROM ".TB_PREF."suppliers
	WHERE supp_ref =".db_escape($supp_code);

	$result = db_query($sql, "Failed to get supplier id");
	$row = db_fetch_row($result);
	return $row[0];
}

function check_stock_id_exist($stock_id)
{
	$sql = "SELECT stock_id 
		FROM ".TB_PREF."stock_master
		WHERE stock_id =".db_escape($stock_id);

	$result = db_query($sql, "Check Stock Code failed");

	if (db_num_rows($result) > 0) {
		return true;
	}
	else {
		return false;
	}
}

function item_has_color($stock_id) {

	set_global_connection();

	$sql = "SELECT COUNT(*) 
		FROM ".TB_PREF."item_codes";

	$sql .= " WHERE stock_id = ".db_escape($stock_id);

	$result = db_query($sql, "Check Color failed");

	$row = db_fetch_row($result);

	if ($row[0] > 1) {
		return true;
	}
	else {
		return false;
	}
}

function get_color_code($stock_id, $color) {

	set_global_connection();

	$sql = "SELECT item_code 
		FROM ".TB_PREF."item_codes";

	$sql .= " WHERE stock_id = ".db_escape($stock_id);
	$sql .= " AND color = ".db_escape($color);

	$result = db_query($sql, "Cant get ");

	$row = db_fetch_row($result);

	return $row[0];
}

function check_color_exist($stock_id, $color_code, $check_only = false, $chk_code = false)
{
	set_global_connection();

	$sql = "SELECT color, item_code 
		FROM ".TB_PREF."item_codes ";

	if ($chk_code) { 
		$sql .= " WHERE item_code = ".db_escape($color_code);
	}
	else {
		$sql .= "WHERE stock_id = ".db_escape($stock_id).
		" AND color = ".db_escape($color_code);
	}
	
	$result = db_query($sql, "Check Color failed");

	if ($check_only) {
		if (db_num_rows($result) > 0) {
			return true;
		}
		else {
			return false;
		}
	}
	else {
		return db_fetch($result);
	}
}

//

/* retrieve quantity on hand by Ronelle 11/11/2021 */
function get_qoh_si($stock_id, $loc_code, $date_ = "", $serial_no = "", $chassis_no = "", $repo = "new")
{
	if ($date_ == "")
		$date_ = Today();

	$date = date2sql($date_);
	$sql = "SELECT
				ifnull(SUM((x1.qty - x1.OutQty)), 0) as qoh
			FROM
			(SELECT
				a.stock_id,
				a.qty,
				(SELECT 
					ifnull(SUM(t1.qty * -1), 0)
				FROM
					stock_moves t1
				WHERE
					t1.transno_out = a.trans_no
				AND
					t1.type_out = a.type
				AND
                    t1.stock_id = a.stock_id
                AND
                    t1.lot_no = a.lot_no
                    AND
                    t1.chassis_no = a.chassis_no
                AND
                    t1.loc_code = a.loc_code
				AND
					t1.item_type = a.item_type
				AND
					t1.qty < 0
				) as OutQty,
				a.tran_date
			FROM
				stock_moves a
			WHERE
				a.qty > 0
			AND
				a.loc_code = " . db_escape($loc_code) . "
			AND
				a.tran_date <= '$date'
			AND
				a.stock_id = " . db_escape($stock_id) . "
			) x1
			WHERE
				x1.qty > x1.OutQty";

	$result = db_query($sql, "QOH calculation failed");

	$myrow = db_fetch_row($result);

	$qoh =  $myrow[0];
	return $qoh ? $qoh : 0;
}
/* */


/* Added by Ronelle 2/26/2021 */
function get_available_item_for_sr(
	$category_id,
	$serialized = 0,
	$search_value = "",
	$loc_code = "",
	$repo = "new"
) {
	if ($loc_code == "") {
		$loc_code = getCompDet('branch_code');
	}

	$sql = "SELECT
					x1.trans_id,
					x1.reference,
					x1.units,
					x1.stock_id,
					x1.description,
					x1.color_code,
					x1.lot_no as serialeng_no,
					x1.chassis_no,
					x1.category,
					x1.brand,
					x1.trans_no,
					x1.type,
					(x1.qty - x1.OutQty) as qoh,
					x1.standard_cost,
					x1.item_type
				FROM
				(SELECT
					a.trans_id,
					a.reference,
					b.units,
					a.stock_id,
					b.description,
					a.color_code,
					a.lot_no,
					a.chassis_no,
					c.description as category,
					d.name as brand,
					a.trans_no,
					a.type,
					a.qty,
					(SELECT 
						ifnull(SUM(t1.qty * -1), 0)
					FROM
						stock_moves t1
					WHERE
						t1.transno_out = a.trans_no
					AND
						t1.type_out = a.type
					AND
                     	t1.stock_id = a.stock_id
                    AND
                     	t1.lot_no = a.lot_no
                    AND
                     	t1.chassis_no = a.chassis_no
                    AND
                     	t1.loc_code = a.loc_code
					AND
						t1.item_type = a.item_type
					AND
						t1.qty < 0
					) as OutQty,
					a.standard_cost,
					a.tran_date,
					a.item_type
				FROM
					stock_moves a
				INNER JOIN
					stock_master b
				ON
					b.stock_id = a.stock_id AND b.serialised = $serialized
				INNER JOIN
					stock_category c
				ON
					c.category_id = b.category_id
				LEFT JOIN
					item_brand d
				ON
					d.id = b.brand
				WHERE
					a.qty > 0
				AND
					a.loc_code = " . db_escape($loc_code) . "
				AND
					b.category_id = $category_id
				) x1
				WHERE
					x1.qty > x1.OutQty > 0
				AND
					x1.item_type =" . db_escape($repo);

	if ($search_value != "") {
		$sql .= " AND (
						x1.lot_no LIKE " . db_escape('%' . trim($search_value) . '%') . " OR 
						x1.chassis_no LIKE " . db_escape('%' . trim($search_value) . '%') . " OR 
						x1.stock_id LIKE " . db_escape('%' . trim($search_value) . '%') . " OR 
						x1.description LIKE " . db_escape('%' . trim($search_value) . '%') . ")";
	}
	$sql .= " GROUP BY x1.trans_id";
	$result = db_query($sql, "could not retreive the items");
	return $result;
}
