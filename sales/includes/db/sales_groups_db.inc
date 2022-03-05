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

function add_sales_group($description)
{
	$sql = "INSERT INTO ".TB_PREF."groups (description) VALUES (".db_escape($description) . ")";
   	db_query($sql,"The sales group could not be added");
}

function update_sales_group($selected_id, $description)
{
	$sql = "UPDATE ".TB_PREF."groups SET description=".db_escape($description)." WHERE id = ".db_escape($selected_id);
   	db_query($sql,"The sales group could not be updated");
}

function delete_sales_group($selected_id)
{
	$sql="DELETE FROM ".TB_PREF."groups WHERE id=".db_escape($selected_id);
	db_query($sql,"could not delete sales group");
}

function get_sales_groups($show_inactive)
{
	$sql = "SELECT * FROM ".TB_PREF."groups";
	if (!$show_inactive) $sql .= " WHERE !inactive";
	$sql .= " ORDER BY description";
	return db_query($sql,"could not get groups");
}

function get_sales_group($selected_id)
{
	$sql = "SELECT * FROM ".TB_PREF."groups WHERE id=".db_escape($selected_id);

	$result = db_query($sql,"could not get group");
	return db_fetch($result);
}

function get_sales_group_name($group_no)
{
	$sql = "SELECT description FROM ".TB_PREF."groups WHERE id = ".db_escape($group_no);
	$result = db_query($sql, "could not get group");
	$row = db_fetch($result);
	return $row[0];
}

//modified br robert
function add_sales_area($description, $collectors_id, $collector_description)
{
	$sql = "INSERT INTO ".TB_PREF."areas (description, collectors_id, collector_description)
    VALUES (".db_escape($description) .", 
    		".db_escape($collectors_id) .",
     		".db_escape($collector_description) .")";
   	db_query($sql,"The sales area could not be added");
}

//added by robert
function add_muni_zipcode($municipality, $zipcode)
{
	$sql = "INSERT INTO ".TB_PREF."municipality_zipcode (municipality, zipcode)
    VALUES (".db_escape($municipality) .", 
     		".db_escape($zipcode) .")";
   	db_query($sql,"The municipality code could not be added");
}

//added by robert
function update_muni_zipcode($selected_id, $municipality, $zipcode)
{
	$sql = "UPDATE ".TB_PREF."municipality_zipcode SET 
	 municipality=".db_escape($municipality). ",
	 zipcode=".db_escape($zipcode) ."
	 WHERE muni_code = ".db_escape($selected_id);
   	db_query($sql,"The municipality code could not be updated");
}

//added by robert
function update_sales_area($selected_id, $description, $collectors_id, $collector_description)
{
	$sql = "UPDATE ".TB_PREF."areas SET 
	 description=".db_escape($description). ",
	 collectors_id=".db_escape($collectors_id). ",
	 collector_description=".db_escape($collector_description) ."
	 WHERE area_code = ".db_escape($selected_id);
   	db_query($sql,"The sales area could not be updated");
}

function delete_sales_area($selected_id)
{
	$sql="DELETE FROM ".TB_PREF."areas WHERE area_code=".db_escape($selected_id);
	db_query($sql,"could not delete sales area");
}

//----ADDED BY ROBERT----//
function get_sales_areas($show_inactive)
{
	$sql = "SELECT A.*, B.real_name FROM ".TB_PREF."areas A
				INNER JOIN ".TB_PREF."users B ON B.user_id = A.collectors_id  ";
	if (!$show_inactive) $sql .= " WHERE !A.inactive AND B.role_id = 11";

	$sql .= " ORDER BY A.description";

	return db_query($sql,"could not get areas");
}

function get_sales_area($selected_id)
{
	$sql = "SELECT * FROM ".TB_PREF."areas WHERE area_code=".db_escape($selected_id);
	$result = db_query($sql,"could not get area");
	return db_fetch($result);
}

//----ADDED BY ROBERT----//
function get_munizip_code($show_inactive)
{
	$sql = "SELECT * FROM ".TB_PREF."municipality_zipcode";
	if (!$show_inactive) $sql .= " WHERE !inactive";
	return db_query($sql,"could not get code");
}

//----ADDED BY ROBERT----//
function get_munizip_edit_code($selected_id)
{
	$sql = "SELECT * FROM ".TB_PREF."municipality_zipcode WHERE muni_code=".db_escape($selected_id);

	$result = db_query($sql,"could not get code");
	return db_fetch($result);
}

//----ADDED BY ROBERT----//
function delete_munizip_code($selected_id)
{
	$sql="DELETE FROM ".TB_PREF."municipality_zipcode WHERE muni_code=".db_escape($selected_id);
	db_query($sql,"could not delete code");
}

//added by robert
function municipality_already_exist($municipality)
{
	$sql = "SELECT COUNT(*) FROM ".TB_PREF."municipality_zipcode
			WHERE municipality = ".db_escape($municipality);
	$result = db_query($sql, "check request failed");
	$count =  db_fetch($result);

	return $count[0];
}

//added by robert
function zipcode_already_exist($zipcode)
{
	$sql = "SELECT COUNT(*) FROM ".TB_PREF."municipality_zipcode
			WHERE zipcode = ".db_escape($zipcode);
	$result = db_query($sql, "check request failed");
	$count =  db_fetch($result);

	return $count[0];
}

//----ADDED BY ROBERT----//
function add_collection_type($collection)
{
	$sql = "INSERT INTO ".TB_PREF."collection_types (collection)
    VALUES (".db_escape($collection).")";
   	db_query($sql,"The collection type could not be added");
}

function update_collection_type($selected_id, $collection)
{
	$sql = "UPDATE ".TB_PREF."collection_types SET 
	 collection=".db_escape($collection)."
	 WHERE collect_id = ".db_escape($selected_id);
   	db_query($sql,"The collection type could not be updated");
}

function delete_collection_type($selected_id)
{
	$sql="DELETE FROM ".TB_PREF."collection_types WHERE collect_id=".db_escape($selected_id);
	db_query($sql,"could not delete collection type");
}

function get_collection_type($selected_id)
{
	$sql = "SELECT * FROM ".TB_PREF."collection_types WHERE collect_id=".db_escape($selected_id);

	$result = db_query($sql,"could not get collection type");
	return db_fetch($result);
}

function get_collection_types($show_inactive)
{
	$sql = "SELECT * FROM ".TB_PREF."collection_types";
	if (!$show_inactive) $sql .= " WHERE !inactive";
	return db_query($sql,"could not get areas");
}
//----------------------//

function get_area_name($id)
{
	$sql = "SELECT description FROM ".TB_PREF."areas WHERE area_code=".db_escape($id);

	$result = db_query($sql, "could not get sales type");

	$row = db_fetch_row($result);
	return $row[0];
}

function add_salesman($salesman_name, $salesman_phone, $salesman_fax,
	$salesman_email, $provision, $break_pt, $provision2)
{
	$sql = "INSERT INTO ".TB_PREF."salesman (salesman_name, salesman_phone, salesman_fax, salesman_email,
		provision, break_pt, provision2)
		VALUES (".db_escape($salesman_name) . ", "
		  .db_escape($salesman_phone) . ", "
		  .db_escape($salesman_fax) . ", "
		  .db_escape($salesman_email) . ", ".
		$provision.", ".$break_pt.", "
		.$provision2.")";
   	db_query($sql,"The insert of the sales person failed");
}

function update_salesman($selected_id, $salesman_name, $salesman_phone, $salesman_fax,
	$salesman_email, $provision, $break_pt, $provision2)
{
	$sql = "UPDATE ".TB_PREF."salesman SET salesman_name=".db_escape($salesman_name) . ",
		salesman_phone=".db_escape($salesman_phone) . ",
		salesman_fax=".db_escape($salesman_fax) . ",
		salesman_email=".db_escape($salesman_email) . ",
		provision=".$provision.",
		break_pt=".$break_pt.",
		provision2=".$provision2."
		WHERE salesman_code = ".db_escape($selected_id);
   	db_query($sql,"The update of the sales person failed");
}

function delete_salesman($selected_id)
{
	$sql="DELETE FROM ".TB_PREF."salesman WHERE salesman_code=".db_escape($selected_id);
	db_query($sql,"The sales-person could not be deleted");
}

function get_salesmen($show_inactive)
{
	$sql = "SELECT * FROM ".TB_PREF."salesman";
	if (!$show_inactive) $sql .= " WHERE !inactive";
	return db_query($sql,"could not get sales persons");
}

function get_salesman($selected_id)
{
	$sql = "SELECT *  FROM ".TB_PREF."salesman WHERE salesman_code=".db_escape($selected_id);

	$result = db_query($sql,"could not get sales person");
	return db_fetch($result);
}

function get_salesman_name($id)
{
	$sql = "SELECT salesman_name FROM ".TB_PREF."salesman WHERE salesman_code=".db_escape($id);

	$result = db_query($sql, "could not get sales type");

	$row = db_fetch_row($result);
	return $row[0];
}

/* Added by Ronelle 2/22/2021 for HOC/BC Type */
function add_hocbc_type($hocbc)
{
	$sql = "INSERT INTO ".TB_PREF."hocbc_types (hocbc)
    VALUES (".db_escape($hocbc).")";
   	db_query($sql,"The hocbc type could not be added");
}

function update_hocbc_type($selected_id, $hocbc)
{
	$sql = "UPDATE ".TB_PREF."hocbc_types SET 
	 hocbc=".db_escape($hocbc)."
	 WHERE id = ".db_escape($selected_id);
   	db_query($sql,"The hocbc type could not be updated");
}

function delete_hocbc_type($selected_id)
{
	$sql="DELETE FROM ".TB_PREF."hocbc_types WHERE id=".db_escape($selected_id);
	db_query($sql,"could not delete hocbc type");
}

function get_hocbc_type($selected_id)
{
	$sql = "SELECT * FROM ".TB_PREF."hocbc_types WHERE id=".db_escape($selected_id);

	$result = db_query($sql,"could not get hocbc type");
	return db_fetch($result);
}

function get_hocbc_types($show_inactive)
{
	$sql = "SELECT * FROM ".TB_PREF."hocbc_types";
	if (!$show_inactive) $sql .= " WHERE !inactive";
	return db_query($sql,"could not get areas");
}
/* */



