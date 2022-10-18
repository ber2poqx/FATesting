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

function add_stock_adjustment($id = 0, $type = 1, //added by spyrax10 for Inventory_Beginning_Balances 
	$items, $location, $date_ = null, $reference, $memo_, 
	$color = '', $person_id = '', $account_id = '') //added by spyrax10 for Inventory_Beginning_Balances 
{
	global $SysPrefs, $path_to_root, $Refs;

	begin_transaction();
	$args = func_get_args();
	$args = (object)array_combine(array('items', 'location', 'date_', 'reference', 'memo_'), $args);
	$args->trans_no = 0;
	hook_db_prewrite($args, ST_INVADJUST);

	$adj_id = get_next_adjID();

	if ($SysPrefs->loc_notification() == 1)
	{
		include_once($path_to_root . "/inventory/includes/inventory_db.inc");
		$st_ids = array();
		$st_names = array();
		$st_num = array();
		$st_reorder = array();
	}
	foreach ($items as $line_item)
	{

		if ($SysPrefs->loc_notification() == 1 && $line_item->quantity < 0)
		{
			$chg = $line_item; $chg->quantity= -$chg->quantity;	// calculate_reorder_level expect positive qty
			$loc = calculate_reorder_level($location, $chg, $st_ids, $st_names, $st_num, $st_reorder); 
		}

		//Modified by spyrax10
		$stock_id = $line_item->stock_id;
		$mcode = get_mcode($person_id);
		$masterfile = get_masterfile($person_id);

		add_stock_adjustment_item($id, $type, 
			$adj_id, 
			$line_item->stock_id, 
			$location, 
			$line_item->ob_date, 
			$reference, 
			$line_item->quantity, 
			$line_item->standard_cost, 
			$memo_, 
			$line_item->manufacture_date, 
			$line_item->expire_date, 
			$line_item->lot_no, 
			$line_item->chasis_no, 
			get_stock_catID($line_item->stock_id), 
			$line_item->color, 
			$line_item->mcode, 
			$line_item->master_file, 
			$account_id, 'new'
		);

	}

	add_comments(ST_INVADJUST, $adj_id, Today(), $memo_);

	$Refs->save(ST_INVADJUST, $adj_id, $reference);
	add_audit_trail(ST_INVADJUST, $adj_id, Today(), "Posted Inventory Opening Balance");

	$args->trans_no = $adj_id;
	hook_db_postwrite($args, ST_INVADJUST);
	commit_transaction();
	if ($SysPrefs->loc_notification() == 1 && count($st_ids) > 0)
		send_reorder_email($loc, $st_ids, $st_names, $st_num, $st_reorder);

	return $adj_id;
}

//-------------------------------------------------------------------------------------------------------------

function void_stock_adjustment($type_no)
{
	hook_db_prevoid(ST_INVADJUST, $type_no);
	void_gl_trans(ST_INVADJUST, $type_no);
	void_stock_move(ST_INVADJUST, $type_no);
}

//-------------------------------------------------------------------------------------------------------------

//Modified by spyrax10
function get_stock_adjustment_items($trans_no) {
	$result = db_num_rows(get_stock_moves(ST_INVADJUST, $trans_no)) == 0 ? null : get_stock_moves(ST_INVADJUST, $trans_no);
	$res = db_num_rows(get_stock_adjust(ST_INVADJUST, $trans_no)) == 0 ? null : get_stock_adjust(ST_INVADJUST, $trans_no);

	return $result == null ? $res : $result;
}

//--------------------------------------------------------------------------------------------------
//Modified by spyrax10
function add_stock_adjustment_item($id, $type = 1, 
	$adj_id, $stock_id, $location, $date_, $reference,
	$quantity, $standard_cost, $memo_, $manufacture_date, $expire_date, $lot_no, $chasis_no, $category_id, 
	$color, $person_id = '', $masterfile = '', $account_id = '', $line_no = null, $item_type = 'new', 
	$trans_no_out = 0, $trans_type_out = 0)
{
	$mb_flag = get_mb_flag($stock_id);

    if (is_service($mb_flag))
    {
    	display_db_error("Cannot do inventory adjustment for Service item : $stock_id", "");
    }

	update_average_material_cost(null, $stock_id, $standard_cost, $quantity, $date_);

	if (is_fixed_asset($mb_flag)) {
		$sql = "UPDATE ".TB_PREF."stock_master SET inactive=1
			WHERE stock_id=".db_escape($stock_id);
		db_query($sql,"The inactive flag for the fixed asset could not be updated");
	}

	//Modified by spyrax10
	if ($type == 2) {
		$adj_item_id = add_stock_move(ST_INVADJUST, $stock_id, $adj_id, $location,
	    $date_, $reference, $quantity, $standard_cost, 0, $lot_no, $chasis_no, $category_id, 
		$color, $trans_type_out, $trans_no_out, $item_type);  
	}
	else {
		$adj_item_id = add_stock_move(ST_INVADJUST, $stock_id, $adj_id, $location,
	    $date_, $reference, $quantity, $standard_cost, 0, $lot_no, $chasis_no, $category_id, 
		is_Serialized($stock_id) == 1 ? $color : $stock_id, $item_type == 'new' ? ST_INVADJUST : ST_RRREPO, $adj_id, $item_type);  
	}
		
	add_stock_serialitems(ST_INVADJUST, $color, $adj_id, $location,
        $date_, $reference, $quantity, $standard_cost, 0, $lot_no, $adj_item_id, $chasis_no
	); 
		
	$inv_value = $standard_cost * $quantity;
	$adj_value = $standard_cost * -($quantity);

	if (is_fixed_asset($mb_flag)) {
		// get the initial value of the fixed assset.
		$row = get_fixed_asset_move($stock_id, ST_SUPPRECEIVE);
		$inv_value = $row['price'] * $quantity;
		$adj_value = (-($row['price']) + $standard_cost) * $quantity;
	}

	if ($standard_cost > 0 || is_fixed_asset($mb_flag))
	{

		$stock_gl_codes = get_stock_gl_code($stock_id);

		$hoc_code = get_company_value(0, 'branch_code');
		$hoc_masterfile = get_company_value(0, 'name');

		//Modified by spyrax10
		if ($id == 1) { 
			add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_,
				$account_id, $stock_gl_codes['dimension_id'], $stock_gl_codes['dimension2_id'], $memo_, $adj_value );
			add_gl_masterfile(ST_INVADJUST, $adj_id, $account_id, $adj_value, $hoc_code, $hoc_masterfile, false);
			add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_, $stock_gl_codes['inventory_account'], 0, 0, $memo_, $inv_value);
			add_gl_masterfile(ST_INVADJUST, $adj_id, $stock_gl_codes['inventory_account'], $inv_value, $person_id, $masterfile, false);
		}
		//Inventory Adjustment!
		else {

			$resGL = child_adjGL_details($adj_id, $line_no);
			$gl_id = 0;
			$stock_gl_codes = get_stock_gl_code($stock_id);

			while ($row2 = db_fetch($resGL)) {
				$gl_id2 = $row2['id'];
				$amount2 = $row2['amount'];
				$account2 = $row2['account'];
				$mcode2 = $row2['mcode'];
				$masterfile2 = $row2['master_file'];
					
				//Child GL Entry
						
				add_gl_trans_std_cost(ST_INVADJUST, $row2['sa_trans_no'], $date_, $account2, 
					$stock_gl_codes['dimension_id'], $stock_gl_codes['dimension2_id'], $memo_, 
					$amount2
				);

				add_gl_masterfile(ST_INVADJUST, $row2['sa_trans_no'], $account2, $amount2, 
					$mcode2, $masterfile2, false
				);

			} //End of while loop

			//Default GL Entry
			add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_, 
				$item_type == 'repo' ? $stock_gl_codes['wip_account'] : get_default_adjGL_account($adj_id, $line_no), 
				$stock_gl_codes['dimension_id'], $stock_gl_codes['dimension2_id'], $memo_, 
				$type == 1 ? get_adjGL_total($adj_id, $line_no) : 
				-get_adjGL_total($adj_id, $line_no)
			);

			add_gl_masterfile(ST_INVADJUST, $adj_id, 
				$item_type == 'repo' ? $stock_gl_codes['wip_account'] : get_default_adjGL_account($adj_id, $line_no), 
				$type == 1 ? get_adjGL_total($adj_id, $line_no) : 
				-get_adjGL_total($adj_id, $line_no), 
				get_default_adjGL_mcode($adj_id, $line_no), 
				get_default_adjGL_masterfile($adj_id, $line_no), false
			);
		}
	}

	if (is_fixed_asset($mb_flag)) {
		// Additional gl entry for fixed asset.
		$grn_act = get_company_pref('default_loss_on_asset_disposal_act');
		add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_, $grn_act, 0, 0, $memo_, ($standard_cost * -($quantity)));
	}

}

//added by spyrax10

function is_invty_open_bal($trans_no = '', $reference = '') {
	
	set_global_connection();
	
	$sql = "SELECT COUNT(*) FROM ".TB_PREF."stock_adjustment A 
		WHERE A.trans_no = " .db_escape($trans_no) . 
		" OR A.reference = " .db_escape($reference);

	$sql .= " GROUP BY A.trans_no, A.reference";
	
	$result = db_query($sql, "Can't identified if the item is opening balance! (spyrax10)");
	
	if (db_num_rows($result) > 0) {
		return false;
	}
	else {
		return true;
	}
}

function get_stock_adjust_details($item_type = 'new') {

	set_global_connection();

	$sql = "SELECT * FROM ".TB_PREF."stock_adjustment A 
		WHERE A.item_type = " .db_escape($item_type);

	$sql .= "GROUP BY A.trans_no";

	$result = db_query($sql, "No Items return for stock_adjustments!");
	
	return $result;
}

function get_smo_details($reference = '', $stock_id, $lot_no = '') {

	set_global_connection();

	$sql = "SELECT * FROM ".TB_PREF."stock_moves A 
		WHERE A.reference = " .db_escape($reference) . 
		" AND A.stock_id = " .db_escape($stock_id);
	
	if ($lot_no != '') { 
		$sql .= " AND A.lot_no = " .db_escape($lot_no);
	}

	$sql .= " GROUP BY A.reference, A.stock_id";

	if ($lot_no != '') { 
		$sql .= ", A.lot_no";
	}

	$result = db_query($sql, "No Items return for stock_moves!");
	
	return db_fetch($result);
}

function check_null_stock_adjustment($item_type = 'new') {

	$result = get_stock_adjust_details($item_type);

	while ($row = db_fetch_assoc($result)) {

		if ($row['status'] == 'Closed' && !smo_exists($row['trans_no'], ST_INVADJUST)) {
			revert_stock_adjustment($row['trans_no']);
		}
	}
}

function revert_stock_adjustment($trans_no) {

	$sql = "UPDATE ".TB_PREF."stock_adjustment 
        SET status = 'Draft', date_approved = '0000-00-00', 
            approver = '' 
		WHERE trans_no = " .db_escape($trans_no);

	set_global_connection();
    db_query($sql, "Cannot revert stock_adjustment status! (spyrax10)");

	delete_stock_adjust_gl($trans_no);
	delete_stock_adjust_gl_trans($trans_no);
	delete_stock_adjust_audit($trans_no);
	delete_stock_adjust_refs($trans_no);
	delete_stock_adjust_comments($trans_no);

	add_audit_trail(ST_INVADJUST, $trans_no, Today(), 
		_("Status reverted back to Draft status because transaction is missing in stock moves!")
	);
}

function delete_stock_adjust_gl($trans_no) {

	$sql = "DELETE FROM ".TB_PREF."stock_adjustment_gl 
        WHERE sa_trans_no = " .db_escape($trans_no);

	set_global_connection();
    db_query($sql, "Cannot delete stock_adjustment transaction from gl! (spyrax10)");
}

function delete_stock_adjust_gl_trans($trans_no) {

	$type = ST_INVADJUST;

	$sql = "DELETE FROM ".TB_PREF."gl_trans 
        WHERE type = $type AND type_no = " .db_escape($trans_no);

	set_global_connection();
    db_query($sql, "Cannot delete stock_adjustment transaction from gl_trans! (spyrax10)");
}

function delete_stock_adjust_audit($trans_no) {

	$type = ST_INVADJUST;

	$sql = "DELETE FROM ".TB_PREF."audit_trail 
        WHERE type = $type AND trans_no = " .db_escape($trans_no);

	set_global_connection();
    db_query($sql, "Cannot delete stock_adjustment transaction from audit! (spyrax10)");
}

function delete_stock_adjust_refs($trans_no) {

	$type = ST_INVADJUST;

	$sql = "DELETE FROM ".TB_PREF."refs 
        WHERE type = $type AND id = " .db_escape($trans_no);

	set_global_connection();
    db_query($sql, "Cannot delete stock_adjustment transaction from refs! (spyrax10)");
}

function delete_stock_adjust_comments($trans_no) {
	
	$type = ST_INVADJUST;

	$sql = "DELETE FROM ".TB_PREF."comments 
        WHERE type = $type AND id = " .db_escape($trans_no);

	set_global_connection();
    db_query($sql, "Cannot delete stock_adjustment transaction from comments! (spyrax10)");
}

function is_adj_repo($trans_no) {
   
	set_global_connection();

    $sql = "SELECT A.item_type FROM ".TB_PREF."stock_adjustment A 
		WHERE A.trans_no=" . db_escape($trans_no);

	$result = db_query($sql, "Cant get adjustment item type! (spyrax10)");
	$row = db_fetch_row($result);

    if ($row[0] == 'new') {
        return false;
    }
    else if ($row[0] == 'repo') {
        return true;
    }
}

function get_default_adjGL_account($trans_no, $line_no) {
	set_global_connection();

	$sql = "SELECT A.account 
		FROM ".TB_PREF."stock_adjustment_gl A 
		WHERE A.gl_type = 'DEFAULT' AND 
		A.sa_trans_no = " .db_escape($trans_no) . " AND A.sa_line_id = " .db_escape($line_no);

	$result = db_query($sql, "Can't get default account! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_default_adjGL_mcode($trans_no, $line_no) {

	set_global_connection();

	$sql = "SELECT A.mcode 
		FROM ".TB_PREF."stock_adjustment_gl A 
		WHERE A.sa_trans_no = " .db_escape($trans_no) . " AND A.sa_line_id = " .db_escape($line_no);

	$result = db_query($sql, "Can't get default mcode! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_default_adjGL_masterfile($trans_no, $line_no) {

	set_global_connection();

	$sql = "SELECT A.master_file 
		FROM ".TB_PREF."stock_adjustment_gl A 
		WHERE A.sa_trans_no = " .db_escape($trans_no) . " AND A.sa_line_id = " .db_escape($line_no);

	$result = db_query($sql, "Can't get default mcode! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_adjGL_total($trans_no = 0, $line_no = 0, $gl_type = "DEFAULT") {

	set_global_connection();

	$sql = "SELECT SUM(abs(A.amount)) AS amount 
		FROM ".TB_PREF."stock_adjustment_gl A 
	WHERE A.gl_type = " .db_escape($gl_type);

	if ($trans_no != 0) {
		$sql .= " AND A.sa_trans_no = " .db_escape($trans_no);
	}

	if ($line_no != 0) {
		$sql .= " A.sa_line_id = ".db_escape($line_no);
	}

	$sql .= " GROUP BY A.sa_trans_no";

	$result = db_query($sql, "get_adjGL_total()");
	$row = db_fetch_row($result);
	return $row[0];
}

function child_adjGL_details($trans_no, $line_no) {
	
	set_global_connection();

	$sql = "SELECT A.*
        FROM ".TB_PREF."stock_adjustment_gl A 
        WHERE A.gl_type = 'CHILD' AND A.sa_trans_no = " .db_escape($trans_no). " 
		AND A.sa_line_id = " .db_escape($line_no);
    
    $sql .= " ORDER BY sa_line_id ASC, gl_type ASC";

	$result = db_query($sql, "No Items return for stock_adjustments GL Items! (spyrax10)");
	return $result;
}

function update_adjGL_entry($trans_no, $line_no, $stock_id, $color, $lot_no, $chasis_no, $amount, $mcode,
	$masterfile) {
	
	set_global_connection();
	
	$sql = "UPDATE ".TB_PREF."stock_adjustment_gl SET stock_id = '$stock_id', color_code = '$color', 
		lot_no = '$lot_no', chassis_no = '$chasis_no', amount = '$amount', mcode = '$mcode', master_file = '$masterfile' 
		WHERE gl_type = 'DEFAULT' AND sa_trans_no = '$trans_no' AND sa_line_id = '$line_no'";

	db_query($sql, "No Items return for stock_adjustments GL Items! (spyrax10)");
}

function get_adjGL($trans_no) {
	
	set_global_connection();

	$sql = "SELECT A.*
        FROM ".TB_PREF."stock_adjustment_gl A 
        WHERE A.sa_trans_no = " .db_escape($trans_no);

    $sql .= " ORDER BY sa_line_id ASC, gl_type ASC";

	$result = db_query($sql, "No Items return for stock_adjustments GL Items! (spyrax10)");
	return db_fetch($result);
}

function get_adjGL_id($trans_no, $stock_id, $lot_no = '') {

	set_global_connection();

	$sql = "SELECT A.id FROM ".TB_PREF."stock_adjustment_gl A 
		WHERE A.sa_trans_no =" . db_escape($trans_no) . 
		" AND A.stock_id = " .db_escape($stock_id);
	
	if ($lot_no != '') {
		$sql .= " AND A.lot_no = " .db_escape($lot_no);
	}

	$result = db_query($sql, "Cant check if stock_id is serialized or not! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}


function delete_adjGL($trans_no, $line_id = '') {

	set_global_connection();

	$sql = "DELETE FROM ".TB_PREF."stock_adjustment_gl 
		WHERE sa_trans_no = ".db_escape($trans_no);

	if ($line_id != '') {
		$sql .= " AND sa_line_id = " .db_escape($line_id);
	}

	db_query($sql, "Can't delete adjustment GL! (spyrax10)");
}

function adjGL_line_exists($trans_no, $line_id = 0) {

	set_global_connection();

	$sql = "SELECT COUNT(*) 
		FROM ".TB_PREF."stock_adjustment_gl 
		WHERE gl_type = 'DEFAULT' AND sa_trans_no = ".db_escape($trans_no);
	
	if ($line_id != 0) {
		$sql .= " AND sa_line_id = ".db_escape($line_id);
	}
	
	$result = db_query($sql, "Adjustment GL default account can't be retrived! (spyrax10)");

	$row = db_fetch_row($result);
	return $row[0];
}

function update_adjGL_amount($trans_no, $total_amount = 0) {
	set_global_connection();

	$sql = "UPDATE ".TB_PREF."stock_adjustment_gl SET amount = " .db_escape($total_amount);

	$sql .= " WHERE gl_type = 'DEFAULT' AND sa_trans_no = " .db_escape($trans_no);

	db_query($sql, 'update_adjGL_amount()');
}

function get_adjGL_details($trans_no, $line_no = 0, $gl_type = "") {
	
    set_global_connection();

	$sql = "SELECT A.*
        FROM " . TB_PREF . "stock_adjustment_gl A 
        WHERE A.sa_trans_no = " .db_escape($trans_no);
    
    if ($line_no > 0) {
        $sql .= " AND A.sa_line_id = " .db_escape($line_no);
    }

	if ($gl_type != "") {
		$sql .= " AND A.gl_type = ".db_escape($gl_type);
	}

    $sql .= " ORDER BY A.id DESC";

    return db_query($sql, "get_adjGL_details()");
}

function get_adjGL_max_line($trans_no) {

	set_global_connection();

	$sql = "SELECT MAX(A.sa_line_id) 
		FROM ".TB_PREF."stock_adjustment_gl A 
		WHERE A.sa_trans_no = " .db_escape($trans_no);

	$result = db_query($sql, "Can't get max SMO trans_no! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}


function delete_adjGL_trans($trans_id) {

	set_global_connection();

	$sql = "DELETE FROM ".TB_PREF."stock_adjustment_gl 
		WHERE id = ".db_escape($trans_id);

	db_query($sql, "Can't delete adjustment GL! (spyrax10)");
}

function count_adjGL_items($trans_no) {

	set_global_connection();

    $sql = "SELECT COUNT(*) FROM ".TB_PREF."stock_adjustment_gl A 
    WHERE A.sa_trans_no=" . db_escape($trans_no);

    $result = db_query($sql, "Cant get stock_adjustment trans_no count! (spyrax10)");
    
	$row = db_fetch_row($result);
	return $row[0];
}

function add_gl_masterfile($type, $type_no, $account, $amount, $person_id, $masterfile, $add_prefix = true) {
	
	set_global_connection();

	$person_id = $add_prefix ? sprintf("%05s", $person_id) : $person_id;
	
	$sql = "UPDATE ".TB_PREF."gl_trans 
		SET mcode = '$person_id', master_file = '$masterfile'
		WHERE type = '$type' AND type_no = '$type_no' AND account = '$account' AND amount = '$amount' ";
	
    db_query($sql, "Cannot update GL masterfile! (spyrax10)");
}

function serial_exist_adj($lot_no = '', $chasis_no = '') {
	set_global_connection();

	$sql = "SELECT COUNT(*) FROM ".TB_PREF."stock_adjustment A 
	WHERE A.status = 'Draft' AND (A.lot_no = " . db_escape(trim($lot_no)) . 
		" OR A.chassis_no= " . db_escape(trim($chasis_no)) . ")";

	$result = db_query($sql, "Can't get serial count in stock adjustment! (spyrax10)");
	$count =  db_fetch($result);

   	return $count[0];
}

function is_Serialized($stock_id) {
	global $def_coy;
	set_global_connection($def_coy);

	$sql = "SELECT A.serialised FROM ".TB_PREF."stock_master A 
		WHERE A.stock_id = " . db_escape(trim($stock_id));

	$result = db_query($sql, "Cant check if stock_id is serialized or not! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_next_adjID() {

	$max_num = max(get_max_adjID(), get_max_smo());
	return $max_num + 1;
}

function get_max_smo() {

	set_global_connection();

	$type = ST_INVADJUST;

	$sql = "SELECT MAX(A.trans_no) 
		FROM ".TB_PREF."stock_moves A 
		WHERE A.type = $type GROUP BY A.type";

	$result = db_query($sql, "Can't get max SMO trans_no! (spyrax10)");
	$row = db_fetch_row($result);

	return $row[0] != null ? $row[0] : 0;
}

function get_max_adjID() {

	set_global_connection();

	$sql = "SELECT MAX(A.trans_no) 
		FROM ".TB_PREF."stock_adjustment A ";
	
	$result = db_query($sql, "Can't get max adjustment trans_no! (spyrax10)");
	$row = db_fetch_row($result);
	
	return $row[0] != null ? $row[0] : 0;
}

function get_max_adjGLID() {
	set_global_connection();

	$sql = "SELECT MAX(A.sa_trans_no) 
		FROM ".TB_PREF."stock_adjustment_gl A ";
	
	$result = db_query($sql, "Can't get max SA_GL trans_no! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_mcode($mcode) {

    set_global_connection();

    $sql = "SELECT debtor_no as id_no FROM ".TB_PREF."debtors_master WHERE debtor_ref = " . db_escape($mcode) . "
		UNION
		SELECT id as id_no FROM ".TB_PREF."item_brand WHERE name = " . db_escape($mcode) . "
        UNION
        SELECT supplier_id as id_no FROM ".TB_PREF."suppliers WHERE supplier_id = " . db_escape($mcode) . "
		UNION
		SELECT id as id_no FROM ".TB_PREF."users WHERE role_id NOT IN(2,10) AND user_id = " .db_escape($mcode) . "
		UNION
		SELECT account_code as id_no FROM chart_master WHERE account_name LIKE '%Branch Current%' AND account_code = " .db_escape($mcode);

    $result = db_query($sql, "Can't get mcode! (spyrax10)");
    $row = db_fetch_row($result);
    //return sprintf('%05d', $row[0]);
	return $row[0];
}

function get_masterfile($mcode) {
    
    set_global_connection();

    $sql = "SELECT name as id_no FROM ".TB_PREF."debtors_master WHERE debtor_ref = " . db_escape($mcode) . "
        UNION
        SELECT supp_name as id_no FROM ".TB_PREF."suppliers WHERE supplier_id = " . db_escape($mcode) . "
		UNION
		SELECT real_name as id_no FROM ".TB_PREF."users WHERE role_id NOT IN(2,10) AND user_id = " .db_escape($mcode) ."
		UNION
		SELECT account_name as id_no FROM chart_master WHERE account_name LIKE '%Branch Current%' AND account_code = " .db_escape($mcode);

    $result = db_query($sql, "Can't get masterfile! (spyrax10)");
    $row = db_fetch_row($result);

    return $row[0] != null ? $row[0] : '';
}

function get_orig_mcode($masterfile) {

	set_global_connection();

    $sql = "SELECT debtor_ref as ref FROM ".TB_PREF."debtors_master WHERE name = " . db_escape($masterfile) . "
        UNION
        SELECT supp_ref as ref FROM ".TB_PREF."suppliers WHERE supp_name = " . db_escape($masterfile) . "
		UNION
		SELECT user_id as ref FROM ".TB_PREF."users WHERE role_id NOT IN(2,10) AND real_name = " .db_escape($masterfile) . "
		UNION
		SELECT account_code as ref FROM chart_master WHERE account_name LIKE '%Branch Current%' AND account_name = " .db_escape($masterfile);

    $result = db_query($sql, "Can't get original mcode! (spyrax10)");
    $row = db_fetch_row($result);

    return $row[0];
}

function is_Serialized_cat($category_id) {

	global $def_coy;
	set_global_connection($def_coy);
	
	$sql = "SELECT A.serialised FROM ".TB_PREF."stock_master A 
		WHERE A.category_id=" . db_escape($category_id);

	$result = db_query($sql, "Can't get category_id's serialized status!");
	$row = db_fetch_row($result);

	set_global_connection();
	return $row[0];
}

function add_stock_serialitems($type, $stock_id, $trans_no, $location,
    $date_, $reference, $quantity, $std_cost, $price = 0, $lot_no = "", 
	$adj_item_id, $chasis_no = "", $remarks = "", $pnp_note = ""
){
	//$date = date2sql($date_);
	//$man_date = date2sql($manufacture_date);
	//$exp_date = date2sql($expire_date);

	set_global_connection();

	$sql = "INSERT INTO ".TB_PREF."item_serialise 
    (
        serialise_item_code, serialise_reference, serialise_chasis_no, serialise_lot_no, 
		serialise_qty, serialise_grn_items_id, serialise_grn_id, serialise_trans_type,
		serialise_loc_code, remarks, pnp_note
    ) VALUES (
        " . db_escape($stock_id) . ",
		" . db_escape($reference) . ", 
		" . db_escape($chasis_no) . ", 
		" . db_escape($lot_no) . ", 
		" . db_escape($quantity) . ", 
		" . db_escape($adj_item_id) . ", 
		" . db_escape($trans_no) . ", 
		" . db_escape($type) . ", 
		" . db_escape($location) . ", 
		" . db_escape($remarks) . " , 
		" . db_escape($pnp_note) . "
    )";
	
	//added by spyrax10
	if ($lot_no != '' && !serial_exist($lot_no, $chasis_no)) {
		db_query($sql, "The stock serial item movement record cannot be inserted");
	}

	return db_insert_id();	
}
