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
function add_scash_type($name, $tax_included, $factor)
{
	$sql = "INSERT INTO ".TB_PREF."sales_cash_type (scash_type ,tax_included,factor) 
			VALUES (".db_escape($name).",".db_escape($tax_included).",".db_escape($factor).")";

	db_query($sql, "could not add cash price type");		
}

function update_scash_type($id, $name, $tax_included, $factor)
{
	$sql = "UPDATE ".TB_PREF."sales_cash_type SET scash_type  = ".db_escape($name).",
				tax_included =".db_escape($tax_included).", factor=".db_escape($factor)." 
			WHERE id = ".db_escape($id);
	
	db_query($sql, "could not update cash price type");			
}

function get_all_scash_price_types($all=false)
{
	$sql = "SELECT * FROM ".TB_PREF."sales_cash_type";
	if (!$all)
		$sql .= " WHERE !inactive";
	
	return db_query($sql, "could not get all sales types");
} 

function get_scash_price_type($id)
{
	$sql = "SELECT * FROM ".TB_PREF."sales_cash_type WHERE id=".db_escape($id);
	
	$result = db_query($sql, "could not get cash price type");
	return db_fetch($result);
}

function delete_scash_price_type($id)
{
	$sql="DELETE FROM ".TB_PREF."sales_cash_type WHERE id=".db_escape($id);
	db_query($sql,"The cash price type record could not be deleted");
}