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

function add_item_supplrcost($supplier_id, $stock_id, $price, $suppliers_uom, 
						$conversion_factor, $supplier_description, $cost_type_id)
{
	$sql = "INSERT INTO ".TB_PREF."purch_data (supplier_id, stock_id, price, suppliers_uom, 
				conversion_factor, supplier_description, cost_type_id) VALUES (";
	$sql .= db_escape($supplier_id).", ".db_escape($stock_id). ", "
		.$price . ", ".db_escape($suppliers_uom ). ", "
		.$conversion_factor . ", "
		.db_escape($supplier_description) . ", "
		.db_escape($cost_type_id). ")";

	db_query($sql,"The supplier purchasing details could not be added");
}

function update_item_supplrcost($selected_id, $stock_id, $price, $suppliers_uom, $conversion_factor, $supplier_description, $cost_type_id)
{
	/*supplier_id=".db_escape($selected_id);*/

	$sql = "UPDATE ".TB_PREF."purch_data SET price=" . $price . ",
		suppliers_uom=".db_escape($suppliers_uom) . ",
		conversion_factor=" . $conversion_factor . ",
		supplier_description=" . db_escape($supplier_description) . ",
		cost_type_id =" . db_escape($cost_type_id) . "
		WHERE stock_id=".db_escape($stock_id) . " AND
		id = ".db_escape($selected_id);

	db_query($sql,"The supplier purchasing details could not be updated");
}

function delete_item_supplrcost($supplier_id, $stock_id)
{
	$sql = "DELETE FROM ".TB_PREF."purch_data WHERE supplier_id=".db_escape($supplier_id)."
		AND stock_id=".db_escape($stock_id);
	db_query($sql,"could not delete purchasing data");
}

function delete_id_supplrcost($selected_id)
{
	$sql = "DELETE FROM ".TB_PREF."purch_data WHERE id=".db_escape($selected_id);
	db_query($sql,"could not delete purchasing data");
}

function get_items_supplrcost($stock_id)
{
    $sql = "SELECT pdata.*, supplier.supp_name,	supplier.curr_code, srptype.cost_type
		FROM ".TB_PREF."purch_data pdata
			INNER JOIN ".TB_PREF."suppliers supplier ON pdata.supplier_id=supplier.supplier_id
			LEFT JOIN ".TB_PREF."supp_cost_types srptype ON pdata.cost_type_id = srptype.id 
		WHERE stock_id = ".db_escape($stock_id);

    return db_query($sql, "The supplier purchasing details for the selected part could not be retrieved");
}

function get_item_supplrcost($supplier_id, $stock_id, $cost_type_id)
{
	$sql = "SELECT pdata.*, supplier.supp_name
		FROM ".TB_PREF."purch_data pdata
			INNER JOIN ".TB_PREF."suppliers supplier ON pdata.supplier_id=supplier.supplier_id
		WHERE pdata.supplier_id=".db_escape($supplier_id)."
			AND pdata.stock_id=".db_escape($stock_id)."
			AND pdata.cost_type_id=".db_escape($cost_type_id);

	$result = db_query($sql, "The supplier purchasing details for the selected supplier and item could not be retrieved");

	return db_fetch($result);
}

function get_id_supplrcost($selected_id)
{
	$sql = "SELECT pdata.*, supplier.supp_name
		FROM ".TB_PREF."purch_data pdata
			INNER JOIN ".TB_PREF."suppliers supplier ON pdata.supplier_id=supplier.supplier_id
		WHERE pdata.id=".db_escape($selected_id);

	$result = db_query($sql, "The supplier purchasing details for the selected supplier and item could not be retrieved");

	return db_fetch($result);
}

//-----------ROBERT Added------------------//
function get_sytem_cost_pricing_type($id)
{
	$sql = "SELECT * FROM ".TB_PREF."supp_cost_types WHERE id=".db_escape($id);
	
	$result = db_query($sql, "could not get system sales type");
	
	return db_fetch($result);
}