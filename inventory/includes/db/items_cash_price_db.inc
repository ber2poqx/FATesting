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
function add_item_scashprice($stock_id, $scash_type_id, $curr_abrev, $price)
{
	$sql = "INSERT INTO ".TB_PREF."cash_prices (stock_id, scash_type_id, curr_abrev, price) 
		VALUES (".db_escape($stock_id).", ".db_escape($scash_type_id)
		.", ".db_escape($curr_abrev).", ".db_escape($price).")";

	db_query($sql,"an item cash price could not be added");
}

function update_item_scashprice($price_id, $scash_type_id, $curr_abrev, $price)
{
	$sql = "UPDATE ".TB_PREF."cash_prices SET scash_type_id=".db_escape($scash_type_id).", 
					curr_abrev=".db_escape($curr_abrev).", 
					price=".db_escape($price)." 
			WHERE id=".db_escape($price_id);

	db_query($sql,"an item cash price could not be updated");
}

function delete_item_scashprice($price_id)
{
	$sql="DELETE FROM ".TB_PREF."cash_prices WHERE id= ".db_escape($price_id);
	db_query($sql,"an item cash price could not be deleted");
}

function get_scashprice($stock_id)
{
	$sql = "SELECT pricelist.scash_type, price.* 
		FROM ".TB_PREF."cash_prices price, "
			.TB_PREF."sales_cash_type pricelist
		WHERE price.scash_type_id = pricelist.id
		AND stock_id=".db_escape($stock_id)
		." ORDER BY curr_abrev, scash_type_id";

	return db_query($sql,"cash price could not be retreived");
}

function get_stock_scashprice($price_id)
{
	$sql = "SELECT * FROM ".TB_PREF."cash_prices WHERE id=".db_escape($price_id);

	$result = db_query($sql,"cash price could not be retreived");

	return db_fetch($result);
}

function get_stock_scashprice_type_currency($stock_id, $type, $currency)
{
	$sql = "SELECT * FROM ".TB_PREF."cash_prices WHERE stock_id=".db_escape($stock_id)."
		AND scash_type_id=".db_escape($type)."
		AND curr_abrev=".db_escape($currency);

	$result = db_query($sql,"cash price could not be retreived");

	return db_fetch($result);
}

function get_itemInfo($stock_id){
	$sql = "SELECT category_id, manufacturer 
			FROM ".TB_PREF."stock_master 
			WHERE stock_id=".db_escape($stock_id);

	$result = db_query($sql,"stock information could not be retreived");

	return db_fetch($result);
}

//-----------ROBERT Added------------------//
function get_cash_pricing_type($id)
{
	$sql = "SELECT * FROM ".TB_PREF."sales_cash_type WHERE id=".db_escape($id);
	
	$result = db_query($sql, "could not get cash sales type");
	
	return db_fetch($result);
}
