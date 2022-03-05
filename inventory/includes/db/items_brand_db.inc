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

function write_brand($selected, $description, $category=0)
{
    if($selected!='' || $selected!=0)
		$sql = "UPDATE ".TB_PREF."item_brand SET
	 	name = ".db_escape($description).",
	 	cat_id = ".db_escape($category)."
        	WHERE id = ".db_escape($selected);
    else
        $sql = "INSERT INTO ".TB_PREF."item_brand (name,cat_id) VALUES(".db_escape($description).",".db_escape($category).")";

	db_query($sql,"an brand could not be updated");
}

function add_item_brand($description){
	$sql = "INSERT INTO ".TB_PREF."item_brand (name) VALUES('".$description."')";

	db_query($sql,"an brand could not be updated");
}

function delete_brand($id)
{
	$sql="DELETE FROM ".TB_PREF."item_brand WHERE id=".db_escape($id);

	db_query($sql,"this brand could not be deleted");
}

function get_brand_rec($brandcode)
{
	$sql="SELECT * FROM ".TB_PREF."item_brand WHERE id='".db_escape($brandcode)."'";

	$result = db_query($sql,"the brand could not be retrieved");

	return db_fetch($result);
}
function get_brand($brandcode)
{
	$sql="SELECT * FROM ".TB_PREF."item_brand where id='$brandcode'";

	$result = db_query($sql,"the brand could not be retrieved");

	return db_fetch($result);
}

function get_brand_descr($id)
{
	$sql = "SELECT name FROM ".TB_PREF."item_brand WHERE id=".db_escape($id);

	$result = db_query($sql, "could not brand description");

	$row = db_fetch_row($result);
	return $row[0];
}

function brand_used($id) {
	$sql= "SELECT COUNT(*) FROM ".TB_PREF."stock_master WHERE brand=".$id;
	$result = db_query($sql, "could not query stock master");
	$myrow = db_fetch_row($result);
	return ($myrow[0] > 0);
}


function get_all_brand($all=false) {
    $sql = "SELECT ib.*, sc.description as category_name FROM ".TB_PREF."item_brand ib LEFT JOIN stock_category sc ON ib.cat_id=sc.category_id";
	if (!$all) $sql .= " WHERE !ib.inactive";
	$sql .= " ORDER BY ib.name";
    return  db_query($sql, "could not get brand");
}