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
/*
	item_codes table is used to store both multiply foreign codes (aliases) and 
	sales kits definition.
*/
function update_item_code($id, $item_code, $color, $stock_id, $description, $pnp_color, $category, $qty, 
	$foreign = 0, $brand = 0, $manufacturer = 0, $distributor = 0, $importer = 0, $itemstatus = 0,
	$old_code = "")
{
	//Added by spyrax10 7 May 2022
	//global $def_coy;
	//set_global_connection($def_coy);
	//

	$sql = "UPDATE ".TB_PREF."item_codes SET
	 	item_code = ".db_escape($item_code).",
	 	stock_id = ".db_escape($stock_id).",
	 	description = ".db_escape($description).",
	 	category_id = ".db_escape($category).",
	 	quantity = ".db_escape($qty).",
	 	is_foreign = ".db_escape($foreign).",
	 	brand = ".db_escape($brand).",
	 	manufacturer = ".db_escape($manufacturer).",
	 	distributor = ".db_escape($distributor).",
	 	importer = ".db_escape($importer).",
		product_status = ".db_escape($itemstatus).",
		pnp_color = ".db_escape($pnp_color).",
		color = ".db_escape($color).",
		old_code = ".db_escape($old_code)."
        	WHERE ";
			
	if ($id == -1) // update with unknown $id i.e. from items table editor
	 	$sql .= "item_code = ".db_escape($item_code)
		." AND stock_id = ".db_escape($stock_id);
	else
		$sql .= "id = ".db_escape($id);

	db_query($sql,"an item code could not be updated");
}

//Modify by spyrax10 4 May 2022
function add_item_code($item_code, $color = null, $stock_id, $description, $pnp_color = null, $category, $qty, 
	$foreign = 0, $brand = 0, $manufacturer = 0, $distributor = 0, $importer = 0, $itemstatus = 0, $old_code = null)
{
	//Added by spyrax10 7 May 2022
	//global $def_coy;
	//set_global_connection($def_coy);
	//

	$sql = "INSERT INTO ".TB_PREF."item_codes
			(
				item_code, stock_id, description, category_id, quantity, is_foreign, brand, manufacturer, distributor, importer, 
				product_status, pnp_color, color, old_code
			) 
			
			VALUES ( 
				".db_escape($item_code).", 
				".db_escape($stock_id).",
	  			".db_escape($description).",
            	".db_escape($category).",  
            	".db_escape($qty).", 
            	".db_escape($foreign).", 
            	".db_escape($brand).", 
            	".db_escape($manufacturer).", 
            	".db_escape($distributor).", 
            	".db_escape($importer).", 
            	".db_escape($itemstatus).", 
            	".db_escape($pnp_color).",  
            	".db_escape($color).",
				".db_escape($old_code)." 
            )";

	db_query($sql,"an item code could not be added");
}

function add_item_code_brand($description){
	$sql = "INSERT INTO ".TB_PREF."item_brand (name) VALUES(".db_escape($description).")";

	db_query($sql,"an brand could not be updated");
}

function add_item_code_manufacturer($description){
	$sql = "INSERT INTO ".TB_PREF."item_manufacturer (name) VALUES(".db_escape($description).")";

	db_query($sql,"a manufacturer could not be updated");
}

function add_item_code_distributor($description){
	$sql = "INSERT INTO ".TB_PREF."item_distributor (name) VALUES(".db_escape($description).")";

	db_query($sql,"a distributor could not be updated");
}
function add_item_code_importer($description){
	$sql = "INSERT INTO ".TB_PREF."item_importer (name) VALUES(".db_escape($description).")";

	db_query($sql,"a importer could not be updated");
}
function delete_item_code($id)
{
	$sql="DELETE FROM ".TB_PREF."item_codes WHERE id=".db_escape($id);
	db_query($sql,"an item code could not be deleted");
}

function get_item_code($id)
{
	$sql="SELECT * FROM ".TB_PREF."item_codes WHERE id=".db_escape($id);

	$result = db_query($sql,"item code could not be retrieved");

	return db_fetch($result);
}

//Added by spyrax10 4 May 2022
function item_code_has_parent($stock_id = '') {
	
	set_global_connection(0);

	$sql = "SELECT is_foreign 
		FROM ".TB_PREF."item_codes 
		WHERE is_foreign = 0 AND stock_id = ".db_escape($stock_id);
	
	$result = db_query($sql,"Item Code parent could not be retrieved");

	$row = db_fetch_row($result);

	return $row[0] != null ? true : false;
}

//Modified by spyrax10 11 Mar 2022
function get_all_item_codes($stock_id, $foreign=1) {
	//Added by spyrax10 7 May 2022
	global $def_coy;
	set_global_connection($def_coy);
	//
	$sql = "SELECT IC.*, SC.description AS cat_name, IC.description AS color_desc
	FROM ".TB_PREF."stock_master SM
		LEFT JOIN ".TB_PREF."item_codes IC ON SM.stock_id = IC.stock_id 
		LEFT JOIN ".TB_PREF."stock_category as SC ON SM.category_id = SC.category_id ";
		
	$sql .= " WHERE SM.stock_id = ".db_escape($stock_id) . "
		AND IC.is_foreign = ".db_escape($foreign);

	$result = db_query($sql,"all item codes could not be retrieved");
	return $result;
}
//

function delete_item_kit($item_code)
{
	$sql="DELETE FROM ".TB_PREF."item_codes WHERE item_code=".db_escape($item_code);
	db_query($sql,"an item kit could not be deleted");
}

function get_item_kit($item_code)
{
	$sql="SELECT DISTINCT kit.*, item.units, comp.description as comp_name, item.description as sdescription, comp.description as idescription, item.inventory_account, item.serialised, brand.name as brand_name
		FROM "
		.TB_PREF."item_codes kit,"
		.TB_PREF."item_codes comp
		LEFT JOIN "
		.TB_PREF."stock_master item
		ON 
			item.stock_id=comp.item_code
		LEFT JOIN "
		.TB_PREF."item_brand brand
		ON 
			item.brand=brand.id
		WHERE
			kit.stock_id=comp.item_code
			AND kit.item_code=".db_escape($item_code);

	$result = db_query($sql,"item kit could not be retrieved");

	return $result;
}

function get_item_serial_kit($item_code)
{
   
    
    $sql="SELECT icode.*, iserial.*, item.units, item.description as sdescription, icode.description as idescription, item.inventory_account FROM "
		.TB_PREF."item_codes icode 
LEFT JOIN "
		.TB_PREF."stock_master item ON item.stock_id=icode.stock_id
INNER JOIN "
		.TB_PREF."item_serialise iserial ON icode.item_code=iserial.serialise_item_code    
		WHERE iserial.serialise_id=".db_escape($item_code);
                
    $result = db_query($sql,"item could not be retrieved");
                
    return $result;
}

function get_MTitem_serial_kit($item_code)
{
    $sql="SELECT icode.*, iserial.*, item.units, item.description as sdescription, icode.description as idescription, item.inventory_account FROM ".TB_PREF."item_codes icode
LEFT JOIN "
            .TB_PREF."stock_master item ON item.stock_id=icode.stock_id
INNER JOIN "
                .TB_PREF."item_serialise iserial ON icode.item_code=iserial.serialise_item_code
		WHERE iserial.serialise_id=".db_escape($item_code);
                
                $result = db_query($sql,"item could not be retrieved");
                
                return $result;
}
//Added by Herald 03/25/2021
function get_item_supplier_id($stock_id){
    global $def_coy;
    set_global_connection($def_coy);
    
    $sql="SELECT supplier_id FROM "
        .TB_PREF."purch_data supplier
		WHERE supplier.stock_id=".db_escape($stock_id);
                
    $result = db_query($sql,"item supplier could not be retrieved");
    return db_fetch($result);
    //set_global_connection();
    //return $result;
                
}
//Added by Herald 03/05/2021 for RR Branch Search Serial
function get_item_serial_mt($mt_details_id)
{
    global $def_coy;
    set_global_connection($def_coy);
    
    $sql="SELECT icode.*, iserial.*, item.units, item.description as sdescription, icode.description as idescription FROM "
        .TB_PREF."item_codes icode
LEFT JOIN "
        .TB_PREF."stock_master item ON item.stock_id=icode.stock_id
INNER JOIN "
        .TB_PREF."mt_details iserial ON icode.item_code=iserial.mt_details_item_code
		WHERE iserial.mt_details_id=".$mt_details_id." LIMIT 1";
                
    $result = db_query($sql,"MT item could not be retrieved");
    set_global_connection();
    return $result;
    
}

function get_item_code_dflts($stock_id)
{
	$sql = "SELECT units, decimals, description, category_id
		FROM ".TB_PREF."stock_master,".TB_PREF."item_units
		WHERE stock_id=".db_escape($stock_id);

	$result = db_query($sql,"item code defaults could not be retrieved");
	return db_fetch($result);
}
//
//	Check if kit contains given item, optionally recursive.
//
function check_item_in_kit($old_id, $kit_code, $item_code, $recurse=false)
{
	$result = get_item_kit($kit_code);
	if ($result)
	{
		while ($myrow = db_fetch($result))
		{
			if ($myrow['id'] == $old_id) 
				continue;
				
			if ($myrow['stock_id'] == $item_code)
			{
				return 1;
			}

			if ($recurse && $myrow['item_code'] != $myrow['stock_id']
				&& check_item_in_kit($old_id, $item_code, $myrow['stock_id'], true))
			{
				return 1;
			}
		}
	}
	return 0;
}

function get_kit_props($kit_code)
{
	$sql = "SELECT description, category_id FROM ".TB_PREF."item_codes "
		. " WHERE item_code=".db_escape($kit_code);
	$res = db_query($sql, "kit name query failed");
	return db_fetch($res);
}

function update_kit_props($kit_code, $name, $category)
{
	$sql = "UPDATE ".TB_PREF."item_codes SET description="
		. db_escape($name).",category_id=".db_escape($category)
		. " WHERE item_code=".db_escape($kit_code);
	db_query($sql, "kit name update failed");
}

function get_where_used($item_code)
{
	$sql = "SELECT item_code, description FROM "
		.TB_PREF."item_codes "
		. " WHERE stock_id=".db_escape($item_code)."
			AND item_code!=".db_escape($item_code);
	return db_query($sql, "where used query failed");
}

function get_adjustment_types($show_inactive)
{
	$sql = "SELECT * FROM ".TB_PREF."inventory_adjustment";
	if (!$show_inactive) $sql .= " WHERE !inactive";
	return db_query($sql,"could not get adjustment");
}

function get_adjustment_type($selected_id)
{
	$sql = "SELECT * FROM ".TB_PREF."inventory_adjustment WHERE adjust_id=".db_escape($selected_id);

	$result = db_query($sql,"could not get adjustment type");
	return db_fetch($result);
}

function add_adjustment_type($adjustment_name)
{
	$sql = "INSERT INTO ".TB_PREF."inventory_adjustment (adjustment_name)
    VALUES (".db_escape($adjustment_name).")";
   	db_query($sql,"The adjustment type could not be added");
}

function update_adjustment_type($selected_id, $adjustment_name)
{
	$sql = "UPDATE ".TB_PREF."inventory_adjustment SET 
	 adjustment_name=".db_escape($adjustment_name)."
	 WHERE adjust_id = ".db_escape($selected_id);
   	db_query($sql,"The adjustment type could not be updated");
}

function delete_adjustment_type($selected_id)
{
	$sql="DELETE FROM ".TB_PREF."inventory_adjustment WHERE adjust_id=".db_escape($selected_id);
	db_query($sql,"could not delete adjustment type");
}