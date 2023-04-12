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
function add_policy_type($plcy_code, $category_id, $tax_included, $dateadded, $remark, $module_type, $area)
{
	$sql = "INSERT INTO ".TB_PREF."policy_header (
					plcy_code
					,category_id
					,tax_included
					,date_defined
					,remarks
					,module_type
					,branch_area_id
			) VALUES (
			        ".db_escape($plcy_code)."
				   ,".db_escape($category_id)."
				   ,".db_escape($tax_included)."
				   ,".db_escape($dateadded)."
				   ,".db_escape($remark)."
				   ,".db_escape($module_type)."
				   ,".db_escape($area)."
			)";
	db_query($sql, "could not add policy type");		
}

function update_policy_type($id, $plcy_code, $category, $tax_included, $remark, $area, $inactive)
{
	$sql = "UPDATE ".TB_PREF."policy_header 
			SET plcy_code = ".db_escape($plcy_code)."
				,category_id = ".db_escape($category)."
				,tax_included = ".db_escape($tax_included)."
				,inactive = ".db_escape($inactive)."
				,remarks = ".db_escape($remark)."
				,branch_area_id = ".db_escape($area)."
			WHERE id = ".db_escape($id);
	
	db_query($sql, "could not update policy type");			
}

function get_all_policy_type($area, $start, $limit, $search, $showall=false)
{
	if (!empty($search)) {
		if (!empty($area)){
			$sWhere = " AND A.branch_area_id ='".$area."' AND A.plcy_code LIKE '%".$search."%'";
		}else{
			$sWhere = " AND A.plcy_code LIKE '%".$search."%'";
		}
	}elseif (!empty($area)){
		$sWhere = " AND A.branch_area_id ='".$area."'";
	}else{
		$area = db_fetch(get_all_policyArea(true));
		$sWhere = " AND A.branch_area_id ='".$area['branch_area_id']."'";
	}
	if (!$showall) $sWhere .= " AND !A.inactive";

	$sql = "SELECT A.*, B.description categoryname, C.id ba_id, C.code, C.description areaname
			FROM ".TB_PREF."policy_header A
			LEFT JOIN ".TB_PREF."stock_category B ON B.category_id = A.category_id 
			INNER JOIN ".TB_PREF."branch_area C ON C.id = A.branch_area_id 
			WHERE A.module_type = 'INSTLPLCYTYPS' ".$sWhere."
			ORDER BY date_defined DESC ";

	$sqllimit = $sql ." limit $start, $limit";
	if($limit != 0){
		$result = db_query($sqllimit, "could not get all installment policy");
	}else{
		$result = db_query($sql, "could not get all installment policy");
	}
	return $result;
}

function get_all_policyArea($limit=false)
{
	$sql = "SELECT DISTINCT A.branch_area_id, B.description areaname
			FROM ".TB_PREF."policy_header A
			INNER JOIN ".TB_PREF."branch_area B ON B.id = A.branch_area_id 
			ORDER BY B.description ASC ";
	return db_query($sql, "could not get area/s");	
}

function get_all_BranchArea($id)
{
	$sql = "SELECT A.id, A.code, A.description areaname
			FROM ".TB_PREF."branch_area A
			ORDER BY A.description ASC ";
	return db_query($sql, "could not get area/s");	
}

function get_policy_type_name($id)
{
	$sql = "SELECT plcy_code FROM ".TB_PREF."policy_header WHERE id=".db_escape($id);
	
	$result = db_query($sql, "could not get installment policy type");
	
	$row = db_fetch_row($result);
	return $row[0];
}

function get_itemcategory($id)
{
	if ($id != 00) {
		$sWhere = " AND A.category_id = ".$id. " ";
	}
	$sql = "SELECT A.category_id, A.description 
			FROM ".TB_PREF."stock_category A 
			WHERE !A.inactive AND A.dflt_mb_flag = 'B' ".$sWhere."
			ORDER BY A.category_id ASC ";
	return db_query($sql, "could not get stock category");	
}

function delete_policy_type($id)
{
	$sql="DELETE FROM ".TB_PREF."policy_header WHERE id=".db_escape($id);
	db_query($sql,"The installment policy type record could not be deleted");

}

function check_data_exist($areaid, $categoryid, $tax_incl, $remark, $inactive)
{
	$sql = "SELECT A.*
			FROM ".TB_PREF."policy_header A 
			WHERE A.module_type = 'INSTLPLCYTYPS'
				AND A.category_id = ".db_escape($categoryid)."
				AND A.branch_area_id = ".db_escape($areaid)."
				AND A.tax_included = ".db_escape($tax_incl)."
				AND A.remarks = ".db_escape($remark)."
				AND A.inactive = ".db_escape($inactive);

	return db_query($sql, "could not get sales installment policy type data ");
}

function check_dependent_records($table, $field, $syspk){
	$sql = "SELECT A.*
			FROM ".TB_PREF.$table." A 
			WHERE A.".$field." = ".db_escape($syspk);

	return db_query($sql, "could not get dependent records ");
}