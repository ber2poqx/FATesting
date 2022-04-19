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
function add_item_stdcost($stock_id, $srptype_id, $curr_abrev, $cost, $supplier_id, $date_epic)
{
	$sql = "INSERT INTO ".TB_PREF."item_srp (stock_id, srptype_id, curr_abrev, standard_cost, supplier_id, date_epic) 
		VALUES (".db_escape($stock_id)
				.", ".db_escape($srptype_id)
				.", ".db_escape($curr_abrev)
				.", ".db_escape($cost)
				.", ".db_escape($supplier_id)
				.", ".db_escape($date_epic).")";

	db_query($sql,"an item standard retail price could not be added");
}

function update_item_stdcost($stdcost_id, $srptype_id, $curr_abrev, $cost, $supplier_id, $date_epic)
{
	$sql = "UPDATE ".TB_PREF."item_srp 
				SET srptype_id=".db_escape($srptype_id).", 
					curr_abrev=".db_escape($curr_abrev).", 
					standard_cost=".db_escape($cost).",
					supplier_id =".db_escape($supplier_id).",
					date_epic = ".db_escape($date_epic)."
		 WHERE id=".db_escape($stdcost_id);

	db_query($sql,"an item standard retail price could not be updated");
}

function delete_item_stdcost($stdcost_id)
{
	$sql="DELETE FROM ".TB_PREF."item_srp WHERE id= ".db_escape($stdcost_id);
	db_query($sql,"an item standard retail price could not be deleted");
}

function get_stdcost($stock_id)
{
	$sql = "SELECT srptype.srp_type, srp.*, supplier.supp_name
		FROM ".TB_PREF."item_srp srp, "
			.TB_PREF."item_srp_area_types srptype, "
			.TB_PREF."suppliers supplier
		WHERE srp.srptype_id = srptype.id
		AND supplier.supplier_id = srp.supplier_id 
		AND stock_id=".db_escape($stock_id)
		." ORDER BY curr_abrev, srptype_id";

	return db_query($sql,"item standard retail price could not be retreived");
}

function get_stock_stdcost($stdcost_id)
{
	$sql = "SELECT * FROM ".TB_PREF."item_srp WHERE id=".db_escape($stdcost_id);

	$result = db_query($sql,"standard retail price could not be retreived");

	return db_fetch($result);
}

function get_stock_stdcost_type_currency($stock_id, $type, $currency, $supplier_id)
{
	$sql = "SELECT * FROM ".TB_PREF."item_srp WHERE stock_id=".db_escape($stock_id)."
		AND srptype_id=".db_escape($type)."
		AND curr_abrev=".db_escape($currency)."
		AND supplier_id=".db_escape($supplier_id);

	$result = db_query($sql,"standard retail price could not be retreived");

	return db_fetch($result);
}

//---------------------------------------------------------------------------------------------------
//for srp setup.
//added by jr for type on 10-19-2020

function add_srp_type($name)
{
	$sql = "INSERT INTO ".TB_PREF."item_srp_area_types (srp_type, inactive) VALUES (".db_escape($name).",0)";
	db_query($sql, "could not add srp type");
}

function update_srp_type($id, $name)
{

	$sql = "UPDATE ".TB_PREF."item_srp_area_types SET srp_type = ".db_escape($name)."
	WHERE id = ".db_escape($id);
	
	db_query($sql, "could not update srp type");			
}

function delete_srp_type($id)
{
	$sql="DELETE FROM ".TB_PREF."item_srp_area_types WHERE id=".db_escape($id);
	db_query($sql,"The srp type record could not be deleted");
}

function get_all_srp_types($all=false)
{
	$sql = "SELECT * FROM ".TB_PREF."item_srp_area_types";
	if (!$all)
		$sql .= " WHERE !inactive";
	
	return db_query($sql, "could not get all srp types");
}

function get_srp_type($id)
{
	$sql = "SELECT * FROM ".TB_PREF."item_srp_area_types WHERE id=".db_escape($id);
	
	$result = db_query($sql, "could not get srp type");
	
	return db_fetch($result);
}