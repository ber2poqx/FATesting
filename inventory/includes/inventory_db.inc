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
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/inventory.inc");

include_once($path_to_root . "/inventory/includes/db/items_category_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_trans_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_prices_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_purchases_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_codes_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_locations_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_adjust_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_transfer_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_units_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_brand_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_manufacturer_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_distributor_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_importer_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_standardcost_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_suppliercost_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_cash_price_db.inc");
include_once($path_to_root . "/includes/cost_and_pricing.inc");

function item_img_name($stock_id)
{
	$stock_id = strtr($stock_id, "><\\/:|*?", '________');
	return clean_file_name($stock_id);
}

//ROBERT ADDED
function getmtreceipt($mt_header_reference)
{
	$def_coy = 0;
	set_global_connection($def_coy);

	$sql = "SELECT mt_header.mt_header_date, mt_header.mt_header_category_id, mt_header.mt_header_reference,
    mt_header.mt_header_tolocation, mt_header.mt_header_comments, mt_header.mt_header_rsd, mt_header.mt_header_servedby,
    mt_header.mt_header_item_type, mt_details.mt_details_stock_id, mt_details.mt_details_total_qty, 
    mt_details.mt_details_st_cost AS COST, mt_details.mt_details_total_qty * mt_details.mt_details_st_cost AS SUBTOTAL,
	mt_details.mt_details_item_code, mt_details.mt_details_serial_no, mt_details.mt_details_chasis_no,
	item_codes.description AS dcolor, stock_master.description, stock_master.units
	FROM " . TB_PREF . "mt_header 
	LEFT JOIN " . TB_PREF . "mt_details ON mt_details.mt_details_header_id = mt_header.mt_header_id 
	LEFT JOIN " . TB_PREF . "item_codes ON item_codes.item_code = mt_details.mt_details_item_code
	LEFT JOIN " . TB_PREF . "stock_master ON stock_master.stock_id = mt_details.mt_details_stock_id
	AND stock_master.stock_id = item_codes.stock_id
	WHERE mt_header.mt_header_reference = mt_header_reference AND mt_header_reference='$mt_header_reference'";

	return db_query($sql, "No transactions were returned");
}
//

//ROBERT ADDED
function getmtgl($type, $type_no)
{
	set_global_connection();
	$sql = "SELECT gl_trans.account, gl_trans.amount, chart_master.account_name FROM " . TB_PREF . "gl_trans LEFT JOIN " . TB_PREF . "chart_master ON chart_master.account_code = gl_trans.account WHERE gl_trans.type=$type and gl_trans.type_no=$type_no ORDER BY chart_master.account_name ASC";

	return db_query($sql, "No transactions were returned");
}
//

// Added by Prog6 (8/27/2021)
function getmtgl_mcode($type, $type_no)
{
	set_global_connection();
	$sql = "SELECT gl_trans.account, gl_trans.amount, chart_master.account_name, mcode, master_file FROM " . TB_PREF . "gl_trans LEFT JOIN " . TB_PREF . "chart_master ON chart_master.account_code = gl_trans.account WHERE gl_trans.type=$type and gl_trans.type_no=$type_no ORDER BY chart_master.account_name ASC";

	return db_query($sql, "No transactions were returned");
}
//
//Added by Herald
function get_stock_moves_typetrans($mt_header_reference)
{
	set_global_connection();
	$sql = "SELECT type, trans_no, reference  FROM " . TB_PREF . "stock_moves WHERE reference='" . $mt_header_reference . "' LIMIT 1";

	return db_query($sql, "No transactions were returned");
}

// for Collection Receipt query; Added by AJE 10/04/2021
function get_cr_trans($cr_ref, $trans_type)
{
		set_global_connection();
		
		$sql = "SELECT dt.`reference`, dt.`trans_no`, dt.`tran_date`, dt.`ov_amount`, dm.`name`, dm.`tax_id`, dm.`address`, com.`memo_`, bt.`bank_branch`, bt.`check_no`, bt.`check_date`, bt.`cashier_user_id`, bt.`pay_type`, user.`real_name`
				FROM `debtor_trans` dt
					LEFT JOIN " . TB_PREF . "debtors_master dm ON dt.debtor_no = dm.debtor_no
				    LEFT JOIN " . TB_PREF . "comments com ON dt.type = com.type AND dt.trans_no = com.id
				    LEFT JOIN " . TB_PREF . "bank_trans bt ON dt.type = bt.type AND dt.trans_no = bt.trans_no
				    LEFT JOIN " . TB_PREF . "users user ON bt.cashier_user_id = user.id
				WHERE dt.`type` = '" . $trans_type . "' AND dt.`reference` = '" . $cr_ref . "'";	
		
		return db_query($sql, "No transactions were returned");
}

// for Official Receipt query; Added by AJE 10/06/2021
function get_or_trans($cr_ref, $trans_type)
{
		set_global_connection();
		
		$sql = "";	
		
		return db_query($sql, "No transactions were returned");
}

//Added by AJE 03/12/2021
function get_rr_details($rr_num, $trans_num, $trans_type)
{
	set_global_connection();

	if ($trans_type == ST_SUPPRECEIVE) {
		$sql = "SELECT supp.supp_name
			,supp.address
			,sm.tran_date
			,grn.suppl_ref_no
			,grn.suppl_ref_date
			,grn.purch_order_no
			,grn.reference
			,sm.qty
			,stk.units
			,sm.stock_id
			,stk.description
			,sm.lot_no
			,sm.chassis_no
			,sm.standard_cost
			,sm.category_id
			,ic.pnp_color
			,CONCAT(stk.description,' - ',ic.description) AS descript
			,grn.grn_remarks
        FROM " . TB_PREF . "grn_batch grn
			INNER JOIN " . TB_PREF . "stock_moves sm ON grn.id = sm.trans_no
			INNER JOIN " . TB_PREF . "suppliers supp ON grn.supplier_id = supp.supplier_id
			INNER JOIN " . TB_PREF . "stock_master stk ON sm.stock_id = stk.stock_id			
			INNER JOIN " . TB_PREF . "item_codes ic ON ic.item_code = sm.color_code
		WHERE sm.type = '" . ST_SUPPRECEIVE . "' 
			AND trans_no = '" . $trans_num . "'";
		return db_query($sql, "No transactions were returned");
	}

	if ($trans_type == ST_RRBRANCH) {
		$sql = "SELECT sm.tran_date	
			,sm.qty
			,stk.units
			,sm.stock_id
			,stk.description
			,sm.lot_no
			,sm.chassis_no
			,sm.standard_cost
			,sm.category_id
			,ic.pnp_color
			,CONCAT(stk.description,' - ',ic.description) AS descript
		    ,sm.reference
        FROM " . TB_PREF . "stock_moves sm
			INNER JOIN " . TB_PREF . "stock_master stk ON sm.stock_id = stk.stock_id
			INNER JOIN " . TB_PREF . "item_codes ic ON ic.item_code = sm.color_code
		WHERE sm.type = '" . $trans_type . "'	
			AND sm.item_type = 'new'		
			AND sm.reference = '" . $rr_num . "'";
		return db_query($sql, "No transactions were returned");
	}

	if ($trans_type == ST_RRREPO) {
		$sql = "SELECT sm.tran_date	
			,sm.qty
			,stk.units
			,sm.stock_id
			,stk.description
			,sm.lot_no
			,sm.chassis_no
			,sm.standard_cost
			,sm.category_id
			,ic.pnp_color
			,CONCAT(stk.description,' - ',ic.description) AS descript
		    ,sm.reference
        FROM " . TB_PREF . "stock_moves sm
			INNER JOIN " . TB_PREF . "stock_master stk ON sm.stock_id = stk.stock_id
			INNER JOIN " . TB_PREF . "item_codes ic ON ic.item_code = sm.color_code
		WHERE sm.type = '" . $trans_type . "'	
			AND sm.item_type != 'new'		
			AND trans_no = '" . $trans_num . "'
			AND sm.reference = '" . $rr_num . "'";
		return db_query($sql, "No transactions were returned");
	}
}

//Added by Aje 03/11/2021
function get_rrsupp_trans($trans_num)
{
	set_global_connection();
	$sql = "SELECT trans_no, type
			FROM " . TB_PREF . "stock_moves 
			WHERE trans_no = '" . $trans_num . "' AND type = '" . ST_SUPPRECEIVE . "'";

	return db_query($sql, "No transactions were returned");
}

//Added by Aje 06/11/2021
function get_rrsupp_ref($trans_num)
{
	set_global_connection();
	$sql = "SELECT reference
			FROM " . TB_PREF . "grn_batch 
			WHERE id = '" . $trans_num . "'";

	return db_query($sql, "No transactions were returned");
}

//Added by Aje 07/01/2021
function get_rrbranch_transno($rr_num,$trans_type)
{
	set_global_connection();

	if ($trans_type == ST_RRBRANCH) {

		$sql = "SELECT Distinct trans_no
				FROM " . TB_PREF . "stock_moves 
				WHERE reference = '" . $rr_num . "' AND type = '" . $trans_type . "'";
		return db_query($sql, "No transactions were returned");
	}
	else
	{
		$sql = "SELECT trans_no
				FROM " . TB_PREF . "stock_moves 
				WHERE reference = '" . $rr_num . "' AND type = '" . $trans_type . "'";
		return db_query($sql, "No transactions were returned");
	}
}

//ADDED BY: Prog6 05/18/2021
function get_salesinvoice_trans($trans_no, $trans_type)
{
	set_global_connection();
	$sql = "SELECT
			dm.name as Soldto
		    ,dm.address as Address
		    ,dt.tran_date as `Date`
		    ,dl.months_term as Terms
		    ,dtd.quantity as Qty
		    ,dtd.description as Article
		    ,CASE
		    	WHEN dtd.quantity <= 0 THEN ''
		        WHEN dtd.quantity = 1 THEN 'unit'
		        ELSE 'units' END as Unit
		    ,CONCAT(dtd.stock_id,'-', dtd.description) as Articles
		    ,dt.ov_amount as UnitCost       
		FROM " . TB_PREF . "debtor_trans_details dtd
			INNER JOIN " . TB_PREF . "debtor_trans dt on dtd.debtor_trans_no = dt.trans_no AND dtd.debtor_trans_type = dt.type
			LEFT JOIN " . TB_PREF . "debtors_master dm on dt.debtor_no = dm.debtor_no
			LEFT JOIN " . TB_PREF . "debtor_loans dl on dt.debtor_no = dl.debtor_no AND dt.trans_no = dl.trans_no
		WHERE 
			dtd.debtor_trans_no = '" . $trans_no . "' AND dtd.quantity > 0 AND dt.type = '" . $trans_type . "'"; 	

	// if ($trans_type != 1) {
	// 	$sql .= " AND dtd.debtor_trans_type = dt.type";
	// }
	return db_query($sql, "No transactions were returned");
}

//ADDED BY: Prog6 04/05/2022
function get_salesinvoice_trans_serialized($trans_no, $trans_type)
{
	set_global_connection();
	$sql = "SELECT
			dm.name as Soldto
		    ,dm.address as Address
		    ,dt.tran_date as `Date`
		    ,dl.months_term as Terms
		    ,dtd.quantity as Qty
		    ,dtd.description as Article
		    ,CASE
		    	WHEN dtd.quantity <= 0 THEN ''
		        WHEN dtd.quantity = 1 THEN 'unit'
		        ELSE 'units' END as Unit
		    ,CONCAT(dtd.stock_id,'-', dtd.description) as Articles
		    ,dt.ov_amount as UnitCost 
			,dtd.stock_id
			,dtd.lot_no as `serial`
			,dtd.chassis_no as `chassis`
			,dl.firstdue_date
			,dl.downpayment_amount as `downpayment`
			,dl.amortization_amount as `amort`
			,dl.rebate
			,(dl.discount_downpayment + dl.discount_downpayment2) as `discount`
			,SMAN.salesman_name AS `salesman`
		FROM " . TB_PREF . "debtor_trans_details dtd
			INNER JOIN " . TB_PREF . "debtor_trans dt on dtd.debtor_trans_no = dt.trans_no AND dtd.debtor_trans_type = dt.type
			LEFT JOIN " . TB_PREF . "debtors_master dm on dt.debtor_no = dm.debtor_no
			LEFT JOIN " . TB_PREF . "debtor_loans dl on dt.debtor_no = dl.debtor_no AND dt.trans_no = dl.trans_no
			LEFT JOIN " . TB_PREF . "sales_orders SO ON dt.order_ = SO.order_no
			LEFT JOIN " . TB_PREF . "salesman SMAN ON SO.salesman_id = SMAN.salesman_code
		WHERE 
			dtd.debtor_trans_no = '" . $trans_no . "' AND dtd.quantity > 0 AND dt.type = '" . $trans_type . "'
		LIMIT 1"; 	

	// if ($trans_type != 1) {
	// 	$sql .= " AND dtd.debtor_trans_type = dt.type";
	// }
	return db_query($sql, "No transactions were returned");
}

//Added by AJE 03/10/2021
function get_rr_supplier_gl($type_no, $type)
{
	set_global_connection();

	if ($type == ST_SUPPRECEIVE) {

		$sql = "SELECT gl.account
				,cm.account_name
				,gl.amount 
				,gl.mcode
				,gl.master_file
			FROM " . TB_PREF . "gl_trans gl 
				LEFT JOIN " . TB_PREF . "chart_master cm ON cm.account_code = gl.account 
			WHERE gl.type=" . ST_SUPPRECEIVE . "
				AND gl.type_no='" . $type_no . "'
			ORDER BY cm.account_name DESC";

		return db_query($sql, "No transactions were returned");
	}

	if ($type == ST_RRBRANCH) {

		$sql = "SELECT gl.account
				,cm.account_name
				,gl.amount
				,gl.mcode
				,gl.master_file
			FROM " . TB_PREF . "gl_trans gl 
				LEFT JOIN " . TB_PREF . "chart_master cm ON cm.account_code = gl.account 
			WHERE gl.type=" . ST_RRBRANCH . "
				AND gl.type_no='" . $type_no . "'
			ORDER BY cm.account_name DESC";

		return db_query($sql, "No transactions were returned");
	}

	if ($type == ST_RRREPO) {

		$sql = "SELECT gl.account
				,cm.account_name
				,gl.amount
				,gl.mcode
				,gl.master_file
			FROM " . TB_PREF . "gl_trans gl 
				LEFT JOIN " . TB_PREF . "chart_master cm ON cm.account_code = gl.account 
			WHERE gl.type=" . ST_RRREPO . "
				AND gl.type_no='" . $type_no . "'
			ORDER BY cm.account_name DESC";

		return db_query($sql, "No transactions were returned");
	}

	else 
	{
		$sql = "SELECT gl.account
				,cm.account_name
				,gl.amount
				,gl.mcode
				,gl.master_file
			FROM " . TB_PREF . "gl_trans gl 
				LEFT JOIN " . TB_PREF . "chart_master cm ON cm.account_code = gl.account 
			WHERE gl.type='" . $type . "'
				AND gl.type_no='" . $type_no . "'
			ORDER BY cm.account_name DESC";

		return db_query($sql, "No transactions were returned");
	}
}
#Added by AJE 03/19/2021
function lookup_to_HO_mt_header($rr_num)
{
	$def_coy = 0;
	set_global_connection($def_coy);

	$sql = "SELECT 
				mt_header.mt_header_date AS MTdate
    			,mt_header.mt_header_reference AS MTnum
    			,mt_header.mt_header_fromlocation AS deliveredFrom    			
			FROM " . TB_PREF . "mt_header
			WHERE mt_header.mt_header_rrbranch_reference = '" . $rr_num . "'";

	return db_query($sql, "No transactions were returned");
}

function get_stock_movements($stock_id, $Inventory_type, $StockLocation, $BeforeDate, $AfterDate, $lot_no)
{
	$before_date = date2sql($BeforeDate);
	$after_date = date2sql($AfterDate);
	// PO Delivery and Customer Credit Notes references should be saved in stock moves reference in 2.5
	$sql = "SELECT SUM(move.qty), move.*, item_codes.description, 
			IM.name AS manu_name,
			IF(ISNULL(supplier.supplier_id), debtor.name, supplier.supp_name) name,
			
			CASE WHEN (SELECT SUM(moveinner.qty) as qtytest 
			FROM stock_moves moveinner 
			WHERE moveinner.stock_id = move.stock_id 
			AND moveinner.lot_no = move.lot_no 
			AND moveinner.type_out = move.type_out 
			AND moveinner.transno_out = move.transno_out
			GROUP BY moveinner.lot_no, moveinner.type_out, moveinner.transno_out LIMIT 1) > 0 THEN 'Available'
			ELSE 'Unavailable' END AS `TYPE`,

			CASE WHEN (SELECT SUM(moveinner.qty) as qtytest 
			FROM stock_moves moveinner 
			WHERE moveinner.stock_id = move.stock_id 
			AND moveinner.type_out = move.type_out 
			AND moveinner.transno_out = move.transno_out
			GROUP BY moveinner.type_out, moveinner.transno_out
			ORDER BY `move`.`tran_date`  DESC LIMIT 1) > 0 THEN 'Available'
			ELSE 'Unavailable' END AS `TYPE_SPGEN`,

			CASE WHEN move.item_type = 'new' THEN 'Brand New'
			ELSE 'Repo' END AS Inventory_type,

		IF(move.type=" . ST_SUPPRECEIVE . ", grn.reference, IF(move.type=" . ST_CUSTCREDIT . ", cust_trans.reference, move.reference)) reference";

	if (!$StockLocation) {
		$sql .= ", move.loc_code";
	}

	$sql .=    " FROM " . TB_PREF . "stock_moves move
				LEFT JOIN " . TB_PREF . "supp_trans credit ON credit.trans_no=move.trans_no AND credit.type=move.type
				LEFT JOIN " . TB_PREF . "grn_batch grn ON grn.id=move.trans_no AND move.type=" . ST_SUPPRECEIVE . "
				LEFT JOIN " . TB_PREF . "suppliers supplier ON IFNULL(grn.supplier_id, credit.supplier_id)=supplier.supplier_id
				LEFT JOIN " . TB_PREF . "debtor_trans cust_trans ON cust_trans.trans_no=move.trans_no AND cust_trans.type=move.type
				LEFT JOIN " . TB_PREF . "debtors_master debtor ON cust_trans.debtor_no=debtor.debtor_no
				LEFT JOIN " . TB_PREF . "item_codes ON item_codes.item_code = move.color_code
				LEFT JOIN " . TB_PREF . "stock_master SM ON SM.stock_id = move.stock_id
				LEFT JOIN " . TB_PREF . "item_manufacturer IM ON SM.manufacturer = IM.id

		WHERE";

	if ($Inventory_type){
		$sql .= " move.item_type = ". db_escape($Inventory_type) . " AND";
	}

	if ($StockLocation) {
		$sql .= " move.loc_code=" . db_escape($StockLocation) . " AND";
	}

	if ($lot_no != ''){
		$sql .= " move.lot_no = ". db_escape($lot_no) . " AND";
	}

	$sql .= " move.tran_date >= '" . $after_date . "'
		AND move.tran_date <= '" . $before_date . "'
		AND move.stock_id = " . db_escape($stock_id) . " GROUP BY move.lot_no, move.type_out, move.transno_out, move.reference, 
		move.item_type
		ORDER BY move.tran_date, move.trans_id";


	return db_query($sql, "could not query stock moves");
}

function calculate_reorder_level($location, $line, &$st_ids, &$st_names, &$st_num, &$st_reorder)
{
	set_global_connection();

	$sql = "SELECT stock.*, loc.location_name, loc.email
		FROM " . TB_PREF . "loc_stock stock,"
		. TB_PREF . "locations loc
		WHERE stock.loc_code=loc.loc_code
		AND stock.stock_id = '" . $line->stock_id . "'
		AND stock.loc_code = '" . $location . "'";
	$res = db_query($sql, "a location could not be retreived");
	$loc = db_fetch($res);
	if ($loc['email'] != "") {
		$qoh = get_qoh_on_date($line->stock_id, $location);
		$qoh -= get_demand_qty($line->stock_id, $location);
		$qoh -= get_demand_asm_qty($line->stock_id, $location);
		$qoh -= $line->quantity;
		if ($qoh < $loc['reorder_level']) {
			$st_ids[] = $line->stock_id;
			$st_names[] = $line->item_description;
			$st_num[] = $qoh - $loc['reorder_level'];
			$st_reorder[] = $loc['reorder_level'];
		}
	}
	return $loc;
}

function send_reorder_email($loc, $st_ids, $st_names, $st_num, $st_reorder)
{
	global $path_to_root;

	require_once($path_to_root . "/reporting/includes/class.mail.inc");
	$company = get_company_prefs();
	$mail = new email($company['coy_name'], $company['email']);
	$to = $loc['location_name'] . " <" . $loc['email'] . ">";
	$subject = _("Stocks below Re-Order Level at " . $loc['location_name']);
	$msg = "\n";
	for ($i = 0; $i < count($st_ids); $i++)
		$msg .= $st_ids[$i] . " " . $st_names[$i] . ", " . _("Re-Order Level") . ": " . $st_reorder[$i] . ", " . _("Below") . ": " . $st_num[$i] . "\n";
	$msg .= "\n" . _("Please reorder") . "\n\n";
	$msg .= $company['coy_name'];
	$mail->to($to);
	$mail->subject($subject);
	$mail->text($msg);
	return $mail->send();
}

/* Added by Ronelle 3/10/2021 */
function sold_item_serialize($stock_id, $lot_no, $chassis_no)
{
	$sql = "UPDATE " . TB_PREF . "item_serialise SET serialise_sold_qty = 1
			 WHERE serialise_lot_no = " . db_escape($lot_no) . " AND serialise_chasis_no = " . db_escape($chassis_no);

	db_query($sql, "could not update serialise sold qty");
}
/* */

/* Added by Ronelle 6/7/2021 */
function get_unit_cost_average($stock_id, $location, $serial_no = "", $chassis_no = "")
{
	global $SysPrefs;

	$sql = "SELECT serialised FROM " . TB_PREF . "stock_master WHERE stock_id=" . db_escape($stock_id);
	$result = db_query($sql, "could not retreive the item data for $stock_id");
	if (db_num_rows($result) == 0) {
		// can't return nothing
		if ($SysPrefs->go_debug)
			display_backtrace();
		display_db_error("no item data found for given params", $sql, true);
		exit;
	}
	$row = db_fetch_row($result);

	if ($row[0] == 0) {
		$sql = "SELECT 
			(SUM(a.standard_cost * a.qty) / SUM(a.qty)) as unit_cost 
			FROM ".TB_PREF."stock_moves a 
			WHERE a.stock_id = ". db_escape($stock_id) ." 
			AND a.loc_code =".db_escape($location);
	} else {
		$sql = "SELECT 
			a.standard_cost as unit_cost 
			FROM ".TB_PREF."stock_moves a 
			WHERE a.stock_id = ". db_escape($stock_id) ." 
			AND a.loc_code =". db_escape($location) ."
			AND a.lot_no =". db_escape($serial_no);

			//Added by spyrax10
			if ($chassis_no != '') {
				$sql .= "AND a.chassis_no =". db_escape($chassis_no);
			}	
			//
	}

	$result = db_query($sql, "could not retreive the unit cost for $stock_id");

	if (db_num_rows($result) == 0) {
		// can't return nothing
		if ($SysPrefs->go_debug)
			display_backtrace();
		display_db_error("no item data found for given params", $sql, true);
		exit;
	}
	$row = db_fetch_row($result);
	return $row[0];

}

/* Added by Albert */

function get_repo_cost_average($stock_id, $location, $serial_no = "", $chassis_no = ""){
	
	global $SysPrefs;

	$sql = "SELECT serialised FROM " . TB_PREF . "stock_master WHERE stock_id=" . db_escape($stock_id);
	$result = db_query($sql, "could not retreive the item data for $stock_id");
	if (db_num_rows($result) == 0) {
		// can't return nothing
		if ($SysPrefs->go_debug)
			display_backtrace();
		display_db_error("no item data found for given params", $sql, true);
		exit;
	}
	$row = db_fetch_row($result);

	if ($row[0] == 0) {
		$sql = "SELECT 
			(SUM(a.standard_cost * a.qty) / SUM(a.qty)) as unit_cost 
			FROM ".TB_PREF."stock_moves a 
			WHERE a.stock_id = ". db_escape($stock_id) ." 
			AND a.loc_code =".db_escape($location);
	} else {
		
			$sql = "SELECT 
			a.standard_cost as unit_cost 
			FROM ".TB_PREF."stock_moves a 
			WHERE a.type=80
			AND a.stock_id = ". db_escape($stock_id) ."
			AND a.loc_code =". db_escape($location) ." 
			AND a.lot_no =". db_escape($serial_no) ."
			AND a.chassis_no =". db_escape($chassis_no);
	
	}
	$result = db_query($sql, "could not retreive the unit cost for $stock_id");

	if (db_num_rows($result) == 0) {
		// can't return nothing
		// if ($SysPrefs->go_debug)
		// 	display_backtrace();
		// display_db_error("no item data found for given params", $sql, true);
		// exit;
	}
	$row = db_fetch_row($result);
	return $row[0];
}

//Added by spyrax10
function smo_exists($trans_no, $trans_type) {

	set_global_connection();

    $sql = "SELECT COUNT(*) FROM stock_moves A 
		WHERE A.trans_no=" . db_escape($trans_no) . 
            " AND A.type=" .db_escape($trans_type);

	$result = db_query($sql, "Cant get SMO count!");
	$row = db_fetch_row($result);
	return $row[0];
}

function is_smo_repo($trans_no, $trans_type) {
	set_global_connection();

	$sql = "SELECT COUNT(*) FROM stock_moves A 
	WHERE A.item_type = 'repo' AND A.trans_no=" . db_escape($trans_no) . 
		" AND A.type=" .db_escape($trans_type);
	
	$sql .= " GROUP BY A.type, A.trans_no";
	
	$result = db_query($sql, "Cant identify if item is repo or not!");

	if (db_num_rows($result) > 0) {
		return true;
	}
	else {
		return false;
	}
}

function _branch_name($branch_code) {
	
	set_global_connection();

	$sql = "SELECT location_name FROM locations A 
	WHERE A.loc_code =" . db_escape($branch_code); 

	$result = db_query($sql, "Cant get branch_name");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_stock_name($stock_id = "") {

	set_global_connection();

	$sql = "SELECT description FROM stock_master A 
	WHERE A.stock_id =" . db_escape($stock_id); 

	$result = db_query($sql, "Cant get stock_name");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_stock_catID($stock_id) {
	
	set_global_connection();

	$sql = "SELECT A.category_id 
	FROM ".TB_PREF."stock_master A
	WHERE A.stock_id = " . db_escape(trim($stock_id));

	$result = db_query($sql, "Cant get stock category id! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_smo_max_year() {
    
    $sql = "SELECT MAX(YEAR(A.tran_date)) AS maxYear
        FROM ".TB_PREF."stock_moves A ";

    $res = db_query($sql, 'Cant get max year!');
    $row = db_fetch_row($res);
	return $row[0];
}

function get_stock_by_itemCode($itemCode = '') {
	
	global $def_coy;
    set_global_connection($def_coy);

	$sql = "SELECT A.* FROM ".TB_PREF."item_codes A 
	WHERE A.item_code = " . db_escape(trim($itemCode));
	$sql .= " GROUP BY A.stock_id";
	$result = db_query($sql, 'get_stock_by_itemCode()');

	set_global_connection();
	return $result;
}

function serial_exist($lot_no = '', $chasis_no = '', $company_id = -1) {

	set_global_connection($company_id);

	$sql = "SELECT COUNT(*) FROM item_serialise A 
	WHERE A.serialise_lot_no = " . db_escape(trim($lot_no));
		
	if ($chasis_no != '') {
		$sql .= " OR A.serialise_chasis_no = " . db_escape(trim($chasis_no));
	} 
	$result = db_query($sql, "Can't get serial count in items_adjust! (spyrax10)");
	$count =  db_fetch($result);

	set_global_connection();
   	return $count[0];
}

function get_serial_details($company_id = -1, $trans_no) {

	if ($company_id == -1) {
		$company_id = $_SESSION["wa_current_user"]->company;
	}

	set_global_connection($company_id);

	$sql = "SELECT A.* FROM ".TB_PREF."item_serialise A 
	WHERE A.serialise_id = " . db_escape($trans_no);
	
	return db_query($sql, 'get_serial_details()');
}

function clean_spaces($table_name = '', $column = '', $company_id = -1) {
	
	set_global_connection($company_id);

	$sql = "UPDATE $table_name 
	SET $column = REPLACE
	(REPLACE(REPLACE($column, ' ', ''), '\t', ''), '\n', '');";

	db_query($sql, "Cannot clean spaces!");
	
}

//------------------------------------------------------------------------//
# Inventory Report Query:
//------------------------------------------------------------------------//

function get_inventory_movement($category = 0, $supplier = "ALL", $location = "ALL", $date, 
	$repo = false, $summarized = false) {
	
	$date = date2sql($date);

	$maxYear = (int)get_smo_max_year();
	$maxYear_1 = (int)get_smo_max_year() - 1;
	$maxYear_2 = (int)get_smo_max_year() - 2;
	$maxYear_3 = (int)get_smo_max_year() - 3;

	$sql = "
		SELECT DATEDIFF(DATE('$date'), SMO.tran_date) AS Age, ID.name AS Sub_Cat,	
			CASE 
				WHEN YEAR(SMO.tran_date) = '$maxYear' THEN SMO.standard_cost ELSE 0
			END AS 'maxYear',
			CASE 
				WHEN YEAR(SMO.tran_date) = '$maxYear_1' THEN SMO.standard_cost ELSE 0
			END AS 'maxYear_1',
			CASE 
				WHEN YEAR(SMO.tran_date) = '$maxYear_2' THEN SMO.standard_cost ELSE 0
			END AS 'maxYear_2',
			CASE 
				WHEN YEAR(SMO.tran_date) <= '$maxYear_3' THEN SMO.standard_cost ELSE 0
			END AS 'maxYear_3',

			SUM(SMO.qty) as QoH, 
			SMO.standard_cost AS UnitCost,
			SMO.category_id, SC.description AS cat_description,
			SMO.tran_date, SM.units,
			SMO.trans_id, SMO.trans_no, SMO.reference, SMO.type, 
			SM.stock_id AS 'Code', IB.name AS Brand, SMO.color_code,
			SMO.tran_date AS 'Date',  
			SMO.lot_no AS 'Serial#', 
			SMO.chassis_no AS 'Chassis#', 
			SMO.loc_code,
			SM.description AS prod_desc
	
		FROM ".TB_PREF."stock_moves SMO
			INNER JOIN ".TB_PREF."stock_master SM ON SMO.stock_id = SM.stock_id
			INNER JOIN ".TB_PREF."stock_category SC ON SM.category_id = SC.category_id
			LEFT JOIN ".TB_PREF."item_brand IB ON SM.brand = IB.id
			LEFT JOIN ".TB_PREF."suppliers SUP ON IB.name = SUP.supp_ref 
			LEFT JOIN ".TB_PREF."item_distributor ID ON SM.distributor = ID.id

		WHERE SMO.tran_date <= '$date' AND SM.mb_flag <> 'D' AND SM.mb_flag <> 'F'";

	if ($repo) {
		$sql .= " AND SMO.item_type = 'repo'";
	}
	else {
		$sql .= " AND SMO.item_type = 'new'";
	}

	if ($category != 0) {
		$sql .= " AND SMO.category_id = ".db_escape($category);
	}
		
	if ($supplier != 'ALL') {
		$sql .= " AND SUP.supp_name =".db_escape($supplier);
	}

	if ($location != 'ALL') {
		$sql .= " AND SMO.loc_code = ".db_escape($location);
	}

	if ($summarized) {
		$sql .= " GROUP BY SMO.stock_id, SMO.loc_code";
		$sql .= " ORDER BY SMO.category_id, SMO.stock_id, SMO.tran_date";
	}
	else {
		$sql .= " GROUP BY SMO.transno_out, SMO.type_out, SMO.stock_id, SMO.loc_code, SMO.lot_no, SMO.chassis_no";
		$sql .= " ORDER BY SMO.category_id, SMO.stock_id, SMO.tran_date, SMO.lot_no";
	}

	return db_query($sql, "No transactions were returned! ");
}

function get_serial_list($search = '', $branch_id = null, $trans_no = null, $cleared_stat = 'ALL') {

    global $db_connections, $def_coy;
    $sql = "";
    $involve_db = $return_arr = array();
    $default_table_count = count_columns(0, 'item_serialise');
    $company_check = $branch_id != null 
		&& $default_table_count == count_columns($branch_id, 'item_serialise');

    if ($company_check) {

		set_global_connection($branch_id);
        $branch_code = get_company_value($branch_id, 'branch_code');

        $involve_db[$branch_id] = $branch_id;

        $sql .= "SELECT '$branch_code' AS branch, $branch_code.* 
        FROM ".TB_PREF."item_serialise AS $branch_code";

        $sql .= " WHERE IFNULL(serialise_item_code, '') <> ''";

        if ($search != '') {
            $sql .= " AND serialise_item_code LIKE " . db_escape('%' . trim($search) . '%') . " OR 
            serialise_lot_no LIKE " . db_escape('%' . trim($search) . '%') . " OR 
            serialise_chasis_no LIKE " . db_escape('%' . trim($search) . '%');
        }

		if ($cleared_stat != 'ALL') {
			$sql .= " AND cleared = " . db_escape($cleared_stat);
		}

        if ($trans_no != null) {
            $sql .= " AND serialise_id = " .db_escape($trans_no);
        }

		$result = db_query($sql, _("get_all_serial()"));
		set_global_connection();

		while ($row = db_fetch_assoc($result)) {
			$return_arr[] = array(
				'serialise_id' => $row['serialise_id'],
				'branch' => $row['branch'],
				'serialise_item_code' => $row['serialise_item_code'],
				'serialise_lot_no' => $row['serialise_lot_no'],
				'serialise_chasis_no' => $row['serialise_chasis_no'],
				'cleared' => $row['cleared'],
				'pnp_note' => $row['pnp_note']
			);
		}
		//return $result;
    }
    else {
		display_error("MAIN: " . $db_connections[0]['dbuser']);

        for ($i = 0; $i < count($db_connections); $i++) {

			if (check_db_exists($i)) {

				if ($default_table_count == count_columns($i, 'item_serialise')) {
    
					set_global_connection($i);

					$branch_code = $db_connections[$i]['branch_code'];

					display_error($branch_code . ": " . $db_connections[$i]['dbuser']);
					
					$involve_db[$i] = $i;
	
					$sql .= "SELECT '$branch_code' AS branch, $branch_code.* 
					FROM " . TB_PREF . "item_serialise AS $branch_code";
	
					$sql .= " WHERE IFNULL(serialise_item_code, '') <> '' ";
		
					if ($search != '') {
						$sql .= " AND serialise_item_code LIKE " . db_escape('%' . trim($search) . '%') . " OR 
						serialise_lot_no LIKE " . db_escape('%' . trim($search) . '%') . " OR 
						serialise_chasis_no LIKE " . db_escape('%' . trim($search) . '%');
					}
	
					if ($cleared_stat != 'ALL') {
						$sql .= " AND cleared = " . db_escape($cleared_stat);
					}
	
					if ($trans_no != null) {
						$sql .= " AND serialise_id = " .db_escape($trans_no);
					}

					$result = db_query($sql, _("get_all_serial(2)"));

					if (db_num_rows($result) > 0) {
						while ($row = db_fetch_assoc($result)) {
							$return_arr[] = array(
								'serialise_id' => $row['serialise_id'],
								'branch' => $row['branch'],
								'serialise_item_code' => $row['serialise_item_code'],
								'serialise_lot_no' => $row['serialise_lot_no'],
								'serialise_chasis_no' => $row['serialise_chasis_no'],
								'cleared' => $row['cleared'],
								'pnp_note' => $row['pnp_note']
							);
						}
					}
					
					set_global_connection();
				}
			}
			$sql = "";
        } // End of forloop
    }

	// foreach($involve_db as $db_index) {
	// 	display_error($db_index);
	// }    

	return $return_arr;
}
