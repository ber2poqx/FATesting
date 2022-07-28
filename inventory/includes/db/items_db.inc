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
function update_item(
	$stock_id,
	$description,
	$long_description,
	$category_id,
	$tax_type_id,
	$units = '',
	$brand = '',
	$manufacturer = '',
	$distributor = '',
	$importer = '',
	$old_code,
	$sap_code,
	$serialised,
	$itemstatus,
	$mb_flag = '',
	$sales_account,
	$installment_sales_account,
	$regular_sales_account,
	$inventory_account,
	$cogs_account,
	$adjustment_account,
	$wip_account,
	$dimension_id,
	$dimension2_id,
	$no_sale,
	$editable,
	$no_purchase,
	$depreciation_method = 'D',
	$depreciation_rate = 100,
	$depreciation_factor = 1,
	$depreciation_start = null,
	$fa_class_id = null,
	$size = null,
	$capacity = null,
	$alllow_zero_cost,
	$currentdate
) {
	$sql = "UPDATE " . TB_PREF . "stock_master SET long_description=" . db_escape($long_description) . ",
		description=" . db_escape($description) . ",
		category_id=" . db_escape($category_id) . ",
		sales_account=" . db_escape($sales_account) . ",
		installment_sales_account=" . db_escape($installment_sales_account) . ",
		regular_sales_account=" . db_escape($regular_sales_account) . ",
		inventory_account=" . db_escape($inventory_account) . ",
		cogs_account=" . db_escape($cogs_account) . ",
		adjustment_account=" . db_escape($adjustment_account) . ",
		wip_account=" . db_escape($wip_account) . ",
		dimension_id=" . db_escape($dimension_id) . ",
		dimension2_id=" . db_escape($dimension2_id) . ",
		tax_type_id=" . db_escape($tax_type_id) . ",
		old_code=" . db_escape($old_code) . ",
		sap_code=" . db_escape($sap_code) . ",
		serialised=" . db_escape($serialised) . ",
		no_sale=" . db_escape($no_sale) . ",
		no_purchase=" . db_escape($no_purchase) . ",
		editable=" . db_escape($editable) . ",
		depreciation_method=" . db_escape($depreciation_method) . ",
		depreciation_rate=" . db_escape($depreciation_rate) . ",
		depreciation_factor=" . db_escape($depreciation_factor) . ",
		fa_class_id=" . db_escape($fa_class_id) . ",
		size=" . db_escape($size) . ",
		capacity=" . db_escape($capacity) . ", 
		allow_zero_cost=" . db_escape($alllow_zero_cost) . ",
		date_modified=" . db_escape($currentdate);

	if ($units != '')
		$sql .= ", units=" . db_escape($units);
	if ($brand != '')
		$sql .= ", brand=" . db_escape($brand);
	if ($manufacturer != '')
		$sql .= ", manufacturer=" . db_escape($manufacturer);
	if ($distributor != '')
		$sql .= ", distributor=" . db_escape($distributor);
	if ($importer != '')
		$sql .= ", importer=" . db_escape($importer);
	if ($mb_flag != '')
		$sql .= ", mb_flag=" . db_escape($mb_flag);

	if (isset($depreciation_start)) {
		$sql .= ", depreciation_start='" . date2sql($depreciation_start) . "'"
			. ", depreciation_date='" . date2sql($depreciation_start) . "'";
	}

	$sql .= " WHERE stock_id=" . db_escape($stock_id);

	db_query($sql, "The item could not be updated");

	update_item_code(-1, $stock_id, $stock_id, $description, $category_id, 1, 0, $brand, $manufacturer, $distributor, $importer, $itemstatus);
}

function add_item($stock_id, $description, $long_description, $category_id, $tax_type_id, $units, $brand, $manufacturer, $distributor, 
	$importer, $old_code, $sap_code, $serialised, $itemstatus, $mb_flag, $sales_account, $installment_sales_account, 
	$regular_sales_account, $inventory_account, $cogs_account, $adjustment_account, $wip_account, $dimension_id, $dimension2_id,
	$no_sale, $editable, $no_purchase, $depreciation_method = 'D', $depreciation_rate = 100, $depreciation_factor = 1, 
	$depreciation_start = null, $fa_class_id = null, $size = null, $capacity = null, $alllow_zero_cost, $currentdate)
{
	$sql = "INSERT INTO " . TB_PREF . "stock_master (stock_id, description, long_description, category_id, tax_type_id, units, 
		mb_flag, sales_account, installment_sales_account, regular_sales_account, inventory_account, cogs_account, adjustment_account, 
		wip_account, dimension_id, dimension2_id, no_sale, no_purchase, editable, depreciation_method, depreciation_rate, 
		depreciation_factor" . (isset($depreciation_start) ? ", depreciation_start, depreciation_date, fa_class_id" : "") 
		. ",brand, manufacturer, distributor, importer, old_code, sap_code, serialised, size, capacity, allow_zero_cost, date_modified)
		VALUES (
		" . db_escape($stock_id) . ",
		" . db_escape($description) . ", 
		" . db_escape($long_description) . ",
		" . db_escape($category_id) . ", 
		" . db_escape($tax_type_id) . ", 
		" . db_escape($units) . ",
		" . db_escape($mb_flag) . ", 
		" . db_escape($sales_account) . ",
		" . db_escape($installment_sales_account) . ", 
		" . db_escape($regular_sales_account) . ",
		" . db_escape($inventory_account) . ", 
		" . db_escape($cogs_account) . ",
		" . db_escape($adjustment_account) . ", 
		" . db_escape($wip_account) . ", 
		" . db_escape($dimension_id) . ", 
		" . db_escape($dimension2_id) . ",
		" . db_escape($no_sale) . ", 
		" . db_escape($no_purchase) . ",
		" . db_escape($editable) . ",
		" . db_escape($depreciation_method) . ",
		" . db_escape($depreciation_rate) . ",
		" . db_escape($depreciation_factor) . (isset($depreciation_start) ? ",
		'" . date2sql($depreciation_start) . "',
		'" . date2sql($depreciation_start) . "',
		" . db_escape($fa_class_id) : "") . ",
		" . db_escape($brand) . ",
		" . db_escape($manufacturer) . ",
		" . db_escape($distributor) . ",
		" . db_escape($importer) . ",
		" . db_escape($old_code) . ",
		" . db_escape($sap_code) . ",
		" . db_escape($serialised) . ",
		" . db_escape($size) . ",
		" . db_escape($capacity) . ",
		" . db_escape($alllow_zero_cost) . ",
		" . db_escape($currentdate) . ")"; 

	db_query($sql, "The item could not be added");

	$sql = "INSERT INTO " . TB_PREF . "loc_stock (loc_code, stock_id)
		SELECT loc_code, " . db_escape($stock_id)
		. " FROM " . TB_PREF . "locations";

	db_query($sql, "The item locstock could not be added");

	//add_item_code($stock_id, $stock_id, $stock_id, $description, $category_id, 1, 0);
	add_item_code($stock_id, '', $stock_id, $description, '', $category_id, 1, 0);
}

function delete_item($stock_id)
{
	$sql = "DELETE FROM " . TB_PREF . "stock_master WHERE stock_id=" . db_escape($stock_id);
	db_query($sql, "could not delete stock item");

	/*and cascade deletes in loc_stock */
	$sql = "DELETE FROM " . TB_PREF . "loc_stock WHERE stock_id=" . db_escape($stock_id);
	db_query($sql, "could not delete stock item loc stock");

	/*and cascade deletes in purch_data */
	$sql = "DELETE FROM " . TB_PREF . "purch_data WHERE stock_id=" . db_escape($stock_id);
	db_query($sql, "could not delete stock item purch data");

	/*and cascade deletes in prices */
	$sql = "DELETE FROM " . TB_PREF . "prices WHERE stock_id=" . db_escape($stock_id);
	db_query($sql, "could not delete stock item prices");

	/*and cascade delete the bill of material if any */
	$sql = "DELETE FROM " . TB_PREF . "bom WHERE parent=" . db_escape($stock_id);
	db_query($sql, "could not delete stock item bom");

	delete_item_kit($stock_id);
}

//Modified by spyrax10 6 May 2022
function get_item($stock_id) {
	
	set_global_connection(0);
	/* $sql = "SELECT item.*, taxtype.name AS tax_type_name
		FROM ".TB_PREF."stock_master item,"
			.TB_PREF."item_tax_types taxtype
		WHERE taxtype.id=item.tax_type_id
		AND stock_id=".db_escape($stock_id); */
	//Change by Herald -12/19/2020		
	$sql = 
		"SELECT item.*, icode.description AS item_description, 
			icode.product_status AS itemstatus, taxtype.name AS tax_type_name, 
			brand.name AS brand_name, manufacturer.name AS manufacturer_name, 
			distributor.name AS distributor_name 

		FROM " . TB_PREF . "stock_master item 
			INNER JOIN " . TB_PREF . "item_tax_types taxtype ON taxtype.id = item.tax_type_id 
			LEFT JOIN " . TB_PREF . "item_brand brand ON item.brand = brand.id 
			LEFT JOIN  " . TB_PREF . "item_manufacturer manufacturer ON item.manufacturer = manufacturer.id 
			LEFT JOIN " . TB_PREF . "item_distributor distributor ON item.distributor = distributor.id 
			INNER JOIN " . TB_PREF . "item_codes icode ON item.stock_id = icode.stock_id 

		WHERE icode.item_code=" . db_escape($stock_id);

	$result = db_query($sql, "an item could not be retreived");

	return db_fetch_assoc($result);
}

function get_items($fixed_asset = 0)
{
	$sql = "SELECT * FROM " . TB_PREF . "stock_master WHERE fixed_asset=" . db_escape($fixed_asset);
	return db_query($sql, "items could not be retreived");
}

function item_in_foreign_codes($stock_id)
{
	$sqls =  array(
		"SELECT COUNT(*) FROM "
			. TB_PREF . "stock_moves WHERE stock_id=" . db_escape($stock_id) =>
		_('Cannot delete this item because there are stock movements that refer to this item.'),
		"SELECT COUNT(*) FROM "
			. TB_PREF . "bom WHERE component=" . db_escape($stock_id) =>
		_('Cannot delete this item record because there are bills of material that require this part as a component.'),
		"SELECT COUNT(*) FROM "
			. TB_PREF . "sales_order_details WHERE stk_code=" . db_escape($stock_id) =>
		_('Cannot delete this item because there are existing sales order items for it.'),
		"SELECT COUNT(*) FROM "
			. TB_PREF . "purch_order_details WHERE item_code=" . db_escape($stock_id) =>
		_('Cannot delete this item because there are existing purchase order items for it.')
	);

	$msg = '';

	foreach ($sqls as $sql => $err) {
		$result = db_query($sql, "could not query stock usage");
		$myrow = db_fetch_row($result);
		if ($myrow[0] > 0) {
			$msg = $err;
			break;
		}
	}
	if ($msg == '') {

		$kits = get_where_used($stock_id);
		$num_kits = db_num_rows($kits);
		if ($num_kits) {
			$msg = _("This item cannot be deleted because some code aliases 
				or foreign codes was entered for it, or there are kits defined 
				using this item as component")
				. ':<br>';

			while ($num_kits--) {
				$kit = db_fetch($kits);
				$msg .= "'" . $kit[0] . "'";
				if ($num_kits) $msg .= ',';
			}
		}
	}
	return $msg;
}

//Modified by spyrax10 5 May 2022
function get_items_search($description, $type, $category = -1, $supplier_id = 0) {
	
	global $SysPrefs, $def_coy;
	set_global_connection($def_coy); 
	$stmt_category = "";
	

	/* $sql = "SELECT COUNT(i.item_code) AS kit, i.item_code, i.description, c.description category
		FROM ".TB_PREF."stock_master s, ".TB_PREF."item_codes i
			LEFT JOIN ".TB_PREF."stock_category c ON i.category_id=c.category_id
		WHERE i.stock_id=s.stock_id
			AND !i.inactive AND !s.inactive
			AND (  i.item_code LIKE " . db_escape("%" . $description. "%") . " OR 
				i.description LIKE " . db_escape("%" . get_post("description"). "%") . " OR 
				c.description LIKE " . db_escape("%" . get_post("description"). "%") . ") "; */

	if ($category != null) {
		if ($category != -1) {
			$stmt_category = " AND c.category_id = " . db_escape($category);
		}	
	} else {
		$stmt_category = "";
	}
	//modify by jr. added supplier table for made in field not manufacturer. 
	//LEFT JOIN ".TB_PREF."item_manufacturer m ON s.manufacturer = m.id
	$sql = 
	"SELECT 
		COUNT(s.stock_id) AS kit, 
		s.stock_id AS item_code, s.description, 
		c.description AS category, 
		b.name AS brand_name,
		m.supp_name AS manufacturer_name, s.units, s.stock_id, 
		d.name AS distributor_name, 
		ii.name AS importer_name
	FROM ".TB_PREF."stock_master s 
		LEFT JOIN ".TB_PREF."stock_category c ON s.category_id = c.category_id	
		LEFT JOIN ".TB_PREF."item_brand b ON s.brand = b.id 
		LEFT JOIN ".TB_PREF."suppliers m ON s.manufacturer = m.supplier_id
		LEFT JOIN ".TB_PREF."item_distributor d ON s.distributor = d.id 
		LEFT JOIN ".TB_PREF."item_importer ii ON s.importer = ii.id	
			
	WHERE s.inactive = 0";

	if ($description != "") {
		$sql .= 
		" AND (s.stock_id LIKE " . db_escape("%" . $description . "%") . " OR
			s.description LIKE " . db_escape("%" . $description . "%") . " OR
			b.name LIKE " . db_escape("%" . $description . "%") . "  OR
			m.supp_name LIKE " . db_escape("%" . $description . "%") . "
		)";
	}

	if ($type == "pr") {
		$sql = 
		"SELECT COUNT(s.stock_id) AS kit, s.stock_id AS item_code, s.description, c.description as category, 
			b.name as brand_name, pd.supp_name AS manufacturer_name, s.units, s.stock_id, d.name as distributor_name, ii.name as importer_name
		FROM ".TB_PREF."stock_master s 
			INNER JOIN ".TB_PREF."suppliers pd on pd.supplier_id = s.manufacturer 
			LEFT JOIN ".TB_PREF."stock_category c ON s.category_id = c.category_id 
			LEFT JOIN ".TB_PREF."item_brand b ON s.brand = b.id 
			LEFT JOIN ".TB_PREF."item_distributor d ON s.distributor = d.id 
			LEFT JOIN ".TB_PREF."item_importer ii ON s.importer = ii.id	
					
		WHERE !s.inactive AND pd.supplier_id = " . db_escape($supplier_id);
		
		if ($description != "") {
			$sql .= 
			" AND (s.stock_id LIKE " . db_escape("%" . $description . "%") . " OR
				s.description LIKE " . db_escape("%" . get_post("description") . "%") . " OR
				b.name LIKE " . db_escape("%" . get_post("description") . "%") . "  OR
				pd.supp_name LIKE " . db_escape("%" . get_post("description") . "%") . "
			)";
		}
	}
	
	$sql .= $stmt_category;

	switch ($type) {
		case "sales":
			$sql .= " AND !s.no_sale AND mb_flag <> 'F'";
			break;
		case "manufactured":
			$sql .= " AND mb_flag = 'M'";
			break;
		case "purchasable":
			$sql .= " AND NOT no_purchase AND mb_flag <> 'F' ";
			break;
		case "costable":
			$sql .= " AND mb_flag <> 'D' AND mb_flag <> 'F' ";
			break;
		case "component":
			$parent = $_GET['parent'];
			$sql .= " AND i.stock_id <> '$parent' AND mb_flag <> 'F' ";
			break;
		case "kits":
			$sql .= " AND !i.is_foreign AND mb_flag <> 'F'";
			break;
		case "all":
			$sql .= " AND mb_flag <> 'F' ";
			break;
	}
	
	/*
	if (isset($SysPrefs->max_rows_in_search))
		$limit = $SysPrefs->max_rows_in_search;
	}
	else {
		$limit = 10;
	*/

	//$sql .= " GROUP BY i.item_code ORDER BY i.description LIMIT 0," . (int)($limit);
	$sql .= " GROUP BY s.stock_id ORDER BY s.description ";
	//$result = db_query($sql, "Failed in retreiving item list.");
	//set_global_connection();


	return $sql;
}

/* Added by Ronelle 7/17/2021 Check Item if Allow Zero Cost */
function check_allow_zero_cost_item($item_code)
{
	global $SysPrefs;
	global $def_coy;
	set_global_connection($def_coy);
	$sql = "SELECT allow_zero_cost FROM " . TB_PREF . "stock_master WHERE stock_id = " . db_escape($item_code);
	$result = db_query($sql, "The Item " . $item_code . " could not be retrieved");
	set_global_connection();
	if (db_num_rows($result) == 1) {
		$row = db_fetch_row($result);
		if ($row[0] == 0)
			return false;
		else
			return true;
	}
}

/* */

/* Added by Ronelle */
function check_item_serialized($stock_id)
{
	$sql = "SELECT serialised FROM stock_master WHERE stock_id = " . db_escape($stock_id) . "";
	$query = db_query($sql, "error");
	$row = db_fetch_row($query);
	return $row[0];
}
/* */

//Added by spyrax10 4 May 2022
function get_item_description($stock_id = "", $company_id = 0) {

	set_global_connection($company_id);

	$sql = "SELECT description 
		FROM ".TB_PREF."stock_master 
		WHERE stock_id = ".db_escape($stock_id);

	$result = db_query($sql, "Item Description could not retrive!");
	$row = db_fetch_row($result);

	return $row[0];
}