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
function write_manufacturer($selected, $description)
{
    if($selected!='' || $selected!=0)
		$sql = "UPDATE ".TB_PREF."item_manufacturer SET
	 	name = ".db_escape($description)."
        	WHERE id = ".db_escape($selected);
    else
		$sql = "INSERT INTO ".TB_PREF."item_manufacturer (name) VALUES(".db_escape($description).")";

	db_query($sql,"a manufacturer could not be updated");
}

function delete_manufacturer($id)
{
	$sql="DELETE FROM ".TB_PREF."item_manufacturer WHERE id=".db_escape($id);

	db_query($sql,"this manufacturer could not be deleted");
}

function get_manufacturer_rec($brandcode)
{
	$sql="SELECT * FROM ".TB_PREF."item_manufacturer WHERE id='".db_escape($brandcode)."'";

	$result = db_query($sql,"the manufacturer could not be retrieved");

	return db_fetch($result);
}
function get_manufacturer($brandcode)
{
	$sql="SELECT * FROM ".TB_PREF."item_manufacturer where id='$brandcode'";

	$result = db_query($sql,"the manufacturer could not be retrieved");

	return db_fetch($result);
}

function get_manufacturer_descr($id)
{
	$sql = "SELECT description FROM ".TB_PREF."item_manufacturer WHERE id=".db_escape($id);

	$result = db_query($sql, "could not manufacturer name");

	$row = db_fetch_row($result);
	return $row[0];
}

function manufacturer_used($id) {
	$sql= "SELECT COUNT(*) FROM ".TB_PREF."stock_master WHERE manufacturer=".$id;
	$result = db_query($sql, "could not query stock master");
	$myrow = db_fetch_row($result);
	return ($myrow[0] > 0);
}


function get_all_manufacturer($all=false) {
    $sql = "SELECT * FROM ".TB_PREF."item_manufacturer";
	if (!$all) $sql .= " WHERE !inactive";
	$sql .= " ORDER BY name";
    return  db_query($sql, "could not get manufacturer");
}

//Added by spyrax10 4 May 2022
function manufacturer_exists($manufacturer = '', $get_id = false) {
	
	$sql = "SELECT id 
		FROM ".TB_PREF."item_manufacturer 
		WHERE name = ".db_escape($manufacturer);

	$result = db_query($sql, "could not check manufacturer");
	$row = db_fetch_row($result);

	if ($get_id) {
		return $row[0];
	}
	else {
		return $row[0] != null ? true : false;
	}
}