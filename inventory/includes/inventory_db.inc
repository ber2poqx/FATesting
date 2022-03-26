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
	WHERE mt_header.mt_header_reference = mt_header_reference AND mt_header_reference='$mt_header_reference'
	GROUP BY mt_header.mt_header_id";

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
		
		$sql = "SELECT dt.`reference`, dt.`trans_no`, dt.`tran_date`, dt.`ov_amount`, dm.`name`, dm.`tax_id`, dm.`address`, com.`memo_`, bt.`bank_branch`, bt.`check_no`, bt.`check_date`, bt.`cashier_user_id`, bt.`pay_type`
				FROM `debtor_trans` dt
					LEFT JOIN " . TB_PREF . "debtors_master dm ON dt.debtor_no = dm.debtor_no
				    LEFT JOIN " . TB_PREF . "comments com ON dt.type = com.type AND dt.trans_no = com.id
				    LEFT JOIN " . TB_PREF . "bank_trans bt ON dt.type = bt.type AND dt.trans_no = bt.trans_no
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
			AND trans_no = '" . $trans_num . "'
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
	$sql = "SELECT trans_no
			FROM " . TB_PREF . "stock_moves 
			WHERE reference = '" . $rr_num . "' AND type = '" . $trans_type . "'";

	return db_query($sql, "No transactions were returned");
}

//ADDED BY: Prog6 05/18/2021
function get_salesinvoice_trans($trans_no, $trans_type)
{
	set_global_connection();
	$sql = "SELECT
			dm.name as Soldto
		    ,dm.address as Address
		    ,dt.tran_date as Date
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