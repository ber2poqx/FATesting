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
function write_distributor($selected, $description)
{
    if($selected!='' || $selected!=0)
		$sql = "UPDATE ".TB_PREF."item_distributor SET
	 	name = ".db_escape($description)."
        	WHERE id = ".db_escape($selected);
    else
		$sql = "INSERT INTO ".TB_PREF."item_distributor (name) VALUES(".db_escape($description).")";

	db_query($sql,"a distributor could not be updated");
}

function delete_distributor($id)
{
	$sql="DELETE FROM ".TB_PREF."item_distributor WHERE id=".db_escape($id);

	db_query($sql,"this distributor could not be deleted");
}

function get_distributor_rec($brandcode)
{
	$sql="SELECT * FROM ".TB_PREF."item_distributor WHERE id='".db_escape($brandcode)."'";

	$result = db_query($sql,"the distributor could not be retrieved");

	return db_fetch($result);
}
function get_distributor($brandcode)
{
	$sql="SELECT * FROM ".TB_PREF."item_distributor where id='$brandcode'";

	$result = db_query($sql,"the distributor could not be retrieved");

	return db_fetch($result);
}

function get_distributor_descr($id)
{
	$sql = "SELECT description FROM ".TB_PREF."item_distributor WHERE id=".db_escape($id);

	$result = db_query($sql, "could not distributor name");

	$row = db_fetch_row($result);
	return $row[0];
}

function distributor_used($id) {
	$sql= "SELECT COUNT(*) FROM ".TB_PREF."stock_master WHERE distributor=".$id;
	$result = db_query($sql, "could not query stock master");
	$myrow = db_fetch_row($result);
	return ($myrow[0] > 0);
}


function get_all_distributor($all=false) {
    $sql = "SELECT * FROM ".TB_PREF."item_distributor";
	if (!$all) $sql .= " WHERE !inactive";
	$sql .= " ORDER BY name";
    return  db_query($sql, "could not get distributor");
}