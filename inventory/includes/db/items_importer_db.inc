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
function write_importer($selected, $description)
{
    if($selected!='' || $selected!=0)
		$sql = "UPDATE ".TB_PREF."item_importer SET
	 	name = ".db_escape($description)."
        	WHERE id = ".db_escape($selected);
    else
		$sql = "INSERT INTO ".TB_PREF."item_importer (name) VALUES(".db_escape($description).")";

	db_query($sql,"an importer could not be updated");
}

function delete_importer($id)
{
	$sql="DELETE FROM ".TB_PREF."item_importer WHERE id=".db_escape($id);

	db_query($sql,"this importer could not be deleted");
}

function get_importer_rec($brandcode)
{
	$sql="SELECT * FROM ".TB_PREF."item_importer WHERE id='".db_escape($brandcode)."'";

	$result = db_query($sql,"the importer could not be retrieved");

	return db_fetch($result);
}
function get_importer($brandcode)
{
	$sql="SELECT * FROM ".TB_PREF."item_importer where id='$brandcode'";

	$result = db_query($sql,"the importer could not be retrieved");

	return db_fetch($result);
}
//Added by Prog6 04/19/2021
function get_importer_name($name)
{
	$sql="SELECT COUNT(*) AS num, name FROM ".TB_PREF."item_importer where name='".$name."'";

	return  db_query($sql, "could not get data from item_importer.");
}

function get_importer_descr($id)
{
	$sql = "SELECT description FROM ".TB_PREF."item_importer WHERE id=".db_escape($id);

	$result = db_query($sql, "could not importer name");

	$row = db_fetch_row($result);
	return $row[0];
}

function importer_used($id) {
	$sql= "SELECT COUNT(*) FROM ".TB_PREF."stock_master WHERE importer=".$id;
	$result = db_query($sql, "could not query stock master");
	$myrow = db_fetch_row($result);
	return ($myrow[0] > 0);
}


function get_all_importer($all=false) {
    $sql = "SELECT * FROM ".TB_PREF."item_importer";
	if (!$all) $sql .= " WHERE !inactive";
	$sql .= " ORDER BY name";
    return  db_query($sql, "could not get distributor");
}

//Added by spyrax10 5 May 2022

function get_importer_id($stock_id) {

	set_global_connection(0);

	$sql = "SELECT importer 
		FROM ".TB_PREF."stock_master 
		WHERE stock_id=".db_escape($stock_id);

	$result = db_query($sql, "Item Importer could not retrive!");
	$row = db_fetch_row($result);

	return $row[0] != null? $row[0]: 0;
}