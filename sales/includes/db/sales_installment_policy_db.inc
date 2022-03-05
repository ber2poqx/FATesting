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
function add_instllpolicy($plcy_code, $tax_included, $dateadded, $remark, $moduletype, $term, $frate, $rebate, $categoryid, $plcyh_id)
{
	$sql = "INSERT INTO ".TB_PREF."policy_details (
					plcydtl_code 
					,tax_included
					,date_defined
					,remarks
					,module_type
					,term
					,financing_rate
					,rebate
					,category_id
					,policyhdr_id
			) VALUES (
			        ".db_escape($plcy_code)."
					,".db_escape($tax_included)."
				   	,".db_escape($dateadded)."
				   	,".db_escape($remark)."
				   	,".db_escape($moduletype)."
				   	,".db_escape($term)."
				   	,".db_escape($frate)."
				   	,".db_escape($rebate)."
					,".db_escape($categoryid)."
					,".db_escape($plcyh_id)."
			)";
	
	$ErrMsg = _('Could not insert the new installment policy because ');
	db_query($sql, $ErrMsg);		
}

function update_policy_type($syspk, $plcy_code, $tax_included, $remark, $term, $frate, $rebate, $categoryid, $plcyh_id, $inactive)
{
	$sql = "UPDATE ".TB_PREF."policy_details 
			SET plcydtl_code = ".db_escape($plcy_code)."
				,tax_included = ".db_escape($tax_included)."
				,remarks = ".db_escape($remark)."
				,term = ".db_escape($term)."
				,financing_rate = ".db_escape($frate)."
				,rebate =".db_escape($rebate)."
				,category_id = ".db_escape($categoryid)."
				,policyhdr_id = ".db_escape($plcyh_id)."
				,inactive = ".db_escape($inactive)."
			WHERE id = ".db_escape($syspk);
	
	db_query($sql, "could not update policy sales installment ");	
}

function get_all_instlpolicy($term, $start, $limit, $search, $showall=false)
{
	if (!empty($search)) {
		if (!empty($term)){
			$sWhere = " AND A.term ='".$term."' AND A.plcydtl_code LIKE '%".$search."%'";
		}else{
			$sWhere = " AND A.plcydtl_code LIKE '%".$search."%'";
		}
	}elseif (!empty($term)){
		$sWhere = " AND A.term ='".$term."'";
	}else{
		$term = db_fetch(get_policyterm(true));
		$sWhere = " AND A.term ='".$term['term']."'";
	}
	if (!$showall) $sWhere .= " AND !A.inactive";

	$sqllimit = "SELECT A.*, B.plcy_code, C.description catgryname
				FROM ".TB_PREF."policy_details A
				 	INNER JOIN ".TB_PREF."policy_header B ON B.id = A.policyhdr_id
					INNER JOIN ".TB_PREF."stock_category C ON C.category_id = A.category_id 
				WHERE A.module_type = 'INSTLPLCY' ".$sWhere."
				ORDER BY date_defined DESC limit $start, $limit";

	return db_query($sqllimit, "could not get all installment policy");
}

function get_policytype($id)
{
	$sql = "SELECT A.id, A.plcy_code, A.category_id FROM ".TB_PREF."policy_header A WHERE !A.inactive ORDER BY A.id ASC ";
	return db_query($sql, "could not get policy type");	
}

function get_itemcategory($id)
{
	if ($id == "00") {
		$sWhere = "";
	}else{
		$sWhere = " WHERE A.category_id = ".$id. " ";
	}
	$sql = "SELECT A.category_id, A.description 
			FROM ".TB_PREF."stock_category A 
			".$sWhere."
			ORDER BY A.category_id ASC ";
	return db_query($sql, "could not get stock category");	
}

function get_policyterm($limit=false)
{
	$sql = "SELECT DISTINCT A.term FROM ".TB_PREF."policy_details A ORDER BY A.term ASC ";
	//if ($limit) $sql .= " LIMIT 1";
	return db_query($sql, "could not get policy term/s");	
}

function get_policyfrate()
{
	$sql = "SELECT DISTINCT A.financing_rate FROM ".TB_PREF."policy_details A ORDER BY A.financing_rate ASC ";
	return db_query($sql, "could not get policy financing rate");	
}

function get_policyrebate()
{
	$sql = "SELECT DISTINCT A.rebate FROM ".TB_PREF."policy_details A ORDER BY A.rebate ASC ";
	return db_query($sql, "could not get policy financing rate");	
}

function delete_si_plcy($id)
{
	$sql="DELETE FROM ".TB_PREF."policy_details WHERE id=".db_escape($id);
	db_query($sql,"The Sales type record could not be deleted");
}

function check_data_exist($plcy_code, $term, $frate, $rebate, $categoryid, $plcyh_id, $taxIncl, $remark, $inactive)
{
	$sql = "SELECT A.*
			FROM ".TB_PREF."policy_details A 
			WHERE A.module_type = 'INSTLPLCY'
				AND A.plcydtl_code = ".db_escape($plcy_code)."
				AND A.term = ".db_escape($term)."
				AND A.financing_rate = ".db_escape($frate)."
				AND A.rebate = ".db_escape($rebate)."
				AND A.category_id = ".db_escape($categoryid)."
				AND A.policyhdr_id = ".db_escape($plcyh_id)."
				AND A.tax_included = ".db_escape($taxIncl)."
				AND A.remarks = ".db_escape($remark)."
				AND A.inactive = ".db_escape($inactive);
	return db_query($sql, "could not get sales installment policy data ");

}

function check_dependent_records($table, $field, $syspk){
	$sql = "SELECT A.*
			FROM ".TB_PREF.$table." A 
			WHERE A.".$field." = ".db_escape($syspk);

	return db_query($sql, "could not get dependent records ");
}

/* Added by Ronelle 2/25/2021 for filtering using category id */
function get_instlpolicy_by_category_id($category_id, $searchval="")
{
	$sql = "SELECT
				a.*
			FROM
				".TB_PREF."policy_details a
			WHERE
				a.category_id = ".db_escape($category_id)." AND
				a.module_type = 'INSTLPLCY' AND
				a.inactive = 0 AND 
				a.plcydtl_code LIKE " . db_escape("%" . $searchval . "%") . " ";

	return db_query($sql, "could not get installment policy");
}

function get_instlpolicy_by_id($id)
{
	$sql = "SELECT * FROM ".TB_PREF."policy_details WHERE id = " . db_escape($id) ."";

	return db_query($sql, "could not get installment policy");
}
/* */