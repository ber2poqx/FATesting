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
function add_branch_area($code, $name)
{
	$sql = "INSERT INTO ".TB_PREF."branch_area (code, description) VALUES (".db_escape($code).",".db_escape($name).")";
	db_query($sql, "could not add branch area");		
}

function update_branch_area($id, $code, $name)
{

	$sql = "UPDATE ".TB_PREF."branch_area SET code = ".db_escape($code).",
							description =".db_escape($name)."
			 WHERE id = ".db_escape($id);
	
	db_query($sql, "could not update branch area");			
}

function get_all_branch_area($all=false)
{
	$sql = "SELECT * FROM ".TB_PREF."branch_area ";
	if (!$all)
		$sql .= " WHERE !inactive";
	
	return db_query($sql, "could not get all branch areas");
} 

function get_branch_area($id)
{
	$sql = "SELECT * FROM ".TB_PREF."branch_area WHERE id=".db_escape($id);
	
	$result = db_query($sql, "could not get branch area");
	
	return db_fetch($result);
}

function get_branch_area_name($id)
{
	$sql = "SELECT description FROM ".TB_PREF."branch_area WHERE id=".db_escape($id);
	
	$result = db_query($sql, "could not get branch name");
	
	$row = db_fetch_row($result);
	return $row[0];
}
function get_branch_area_name_by_code($code)
{
    $sql = "SELECT description FROM ".TB_PREF."branch_area WHERE code=".db_escape($code);
    
    $result = db_query($sql, "could not get branch name");
    
    $row = db_fetch_row($result);
    return $row[0];
}
function delete_branch_area($id)
{
	$sql="DELETE FROM ".TB_PREF."branch_area WHERE id=".db_escape($id);
	db_query($sql,"The branch area record could not be deleted");
}

