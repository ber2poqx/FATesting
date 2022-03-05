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
function add_incentive_type($description, $module_type)
{
	$sql = "INSERT INTO ".TB_PREF."sales_incentive_type (description ,module_type) 
			VALUES (".db_escape($description).",".db_escape($module_type).")";

	db_query($sql, "could not add incentive type");		
}

function update_incentive_type($id, $description, $module_type)
{
	$sql = "UPDATE ".TB_PREF."sales_incentive_type SET description  = ".db_escape($description).",
				module_type =".db_escape($module_type)." 
			WHERE id = ".db_escape($id);
	
	db_query($sql, "could not update incentive type");			
}

function get_all_incentive_type($all=false)
{
	$sql = "SELECT * FROM ".TB_PREF."sales_incentive_type";
	if (!$all)
		$sql .= " WHERE !inactive";
	
	return db_query($sql, "could not get all incentive types");
} 

function get_incentive_type($id)
{
	$sql = "SELECT * FROM ".TB_PREF."sales_incentive_type WHERE id=".db_escape($id);
	
	$result = db_query($sql, "could not get incentive type");
	return db_fetch($result);
}

function delete_incentive_type($id)
{
	$sql="DELETE FROM ".TB_PREF."sales_incentive_type WHERE id=".db_escape($id);
	db_query($sql,"The incentive type record could not be deleted");
}

//--------------- incentive type
/*******************************************************************************************/
//--------------- incentive Pricing

function add_item_incentiveprice($stock_id, $type_id, $curr_abrev, $price)
{
	$sql = "INSERT INTO ".TB_PREF."incentive_prices (stock_id, incentive_type_id, curr_abrev, price) 
		VALUES (".db_escape($stock_id).", ".db_escape($type_id).", ".db_escape($curr_abrev).", ".db_escape($price).")";

	db_query($sql,"an item cash price could not be added");
}

function update_item_incentiveprice($price_id, $type_id, $curr_abrev, $price)
{
	$sql = "UPDATE ".TB_PREF."incentive_prices SET incentive_type_id=".db_escape($type_id).", 
					curr_abrev=".db_escape($curr_abrev).", 
					price=".db_escape($price)." 
			WHERE id=".db_escape($price_id);

	db_query($sql,"an item cash price could not be updated");
}

function delete_item_incentiveprice($price_id)
{
	$sql="DELETE FROM ".TB_PREF."incentive_prices WHERE id= ".db_escape($price_id);
	db_query($sql,"an item incentive price could not be deleted");
}

function get_stock_incentive_currency($stock_id, $type, $currency)
{
	$sql = "SELECT * FROM ".TB_PREF."incentive_prices WHERE stock_id=".db_escape($stock_id)."
		AND incentive_type_id=".db_escape($type)."
		AND curr_abrev=".db_escape($currency);

	$result = db_query($sql,"cash price could not be retreived");

	return db_fetch($result);
}

function get_incentiveprice($stock_id)
{
	$sql = "SELECT inctype.description, incprice.* 
		FROM ".TB_PREF."incentive_prices incprice LEFT JOIN "
			.TB_PREF."sales_incentive_type inctype ON incprice.incentive_type_id = inctype.id
		WHERE stock_id=".db_escape($stock_id)
		." ORDER BY incprice.curr_abrev, incprice.incentive_type_id";

	return db_query($sql,"incentive price could not be retreived");
}

function get_stock_incentiveprice($price_id)
{
	$sql = "SELECT * FROM ".TB_PREF."incentive_prices WHERE id=".db_escape($price_id);

	$result = db_query($sql,"incentive price could not be retreived");

	return db_fetch($result);
}