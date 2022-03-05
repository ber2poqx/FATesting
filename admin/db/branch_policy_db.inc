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
function add_branchplcy($dateadded, $branchcode, $branchname, $brpolicyid, $brcategoryid, $moduletype, $affectedfld)
{
	$sql = "INSERT INTO ".TB_PREF."branch_policy (
					branch_code 
					,branch_name
					,date_defined
					,category_id 
					,".$affectedfld."
					,module_type
			) VALUES (
			        ".db_escape($branchcode)."
					,".db_escape($branchname)."
				   	,".db_escape($dateadded)."
				   	,".db_escape($brcategoryid)."
				   	,".db_escape($brpolicyid)."
				   	,".db_escape($moduletype)."
			)";
	
	$ErrMsg = _('Could not insert the new installment policy because ');
	db_query($sql, $ErrMsg);		
}

function update_branchplcy($syspk, $inactive, $brcategoryid, $moduletype, $brpolicyid, $affectedfld)
{
	$sql = "UPDATE ".TB_PREF."branch_policy 
			SET category_id = ".db_escape($brcategoryid)."
				,".$affectedfld." = ".db_escape($brpolicyid)."
				,inactive = ".db_escape($inactive)."
			WHERE id = " .db_escape($syspk). " AND module_type = ".db_escape($moduletype);
	
	db_query($sql, "could not update branch policy ");	
}

function get_plcybranch($id, $start, $limit)
{
	$sql = "SELECT DISTINCT A.branch_code, A.branch_name 
			FROM ".TB_PREF."branch_policy A 
			ORDER BY A.branch_code limit $start, $limit";
	return db_query($sql, "could not get branch policy ");	
}

function get_all_Branchpolicy($module, $branch, $start, $limit, $search, $showall=false)
{
	if (!empty($search)) {
		if (!empty($branch)){
			$sWhere = " AND A.branch_code ='".$branch."' AND 
			(C.sales_type LIKE '%".$search."%'
				OR D.cost_type LIKE '%".$search."%'
				OR E.srp_type LIKE '%".$search."%'
				OR F.plcy_code LIKE '%".$search."%'
				OR B.description LIKE '%".$search."%'
				OR CZ.scash_type LIKE '%".$search."%'
			)";
		}else{
			$sWhere = " AND (C.sales_type LIKE '%".$search."%'
				OR D.cost_type LIKE '%".$search."%'
				OR E.srp_type LIKE '%".$search."%'
				OR F.plcy_code LIKE '%".$search."%'
				OR B.description LIKE '%".$search."%'
				OR CZ.scash_type LIKE '%".$search."%'
			)";
		}
	}elseif (!empty($branch)){
		$sWhere = " AND A.branch_code ='".$branch."'";
	}else{
		$branch = db_fetch(get_plcybranch(00,0,1));
		$sWhere = " AND A.branch_code ='".$branch['branch_code']."'";
	}
	if (!$showall) $sWhere .= " AND !A.inactive";

	$sql = "SELECT A.*, B.category_id, B.description catgryname
					, C.id price_id, C.sales_type
					, D.id cost_id, D.cost_type
					, E.id srp_id, E.srp_type
					, F.id instl_id, F.plcy_code
					, CZ.id cshprice_id, CZ.scash_type
				FROM ".TB_PREF."branch_policy A
				 	INNER JOIN stock_category B ON B.category_id = A.category_id
					LEFT JOIN sales_cash_type CZ ON CZ.id = A.plcycashprice_id
					LEFT JOIN sales_types C ON C.id = A.plcyprice_id
					LEFT JOIN supp_cost_types D ON D.id = A.plcysplrcost_id
					LEFT JOIN item_srp_area_types E ON E.id = A.plcysrp_id
					LEFT JOIN policy_header F ON F.id = A.plcyinstl_id
				WHERE A.module_type = '".$module."' ".$sWhere."
				ORDER BY A.date_defined DESC limit $start, $limit";

	return db_query($sql, "could not get all branch policy");
}

function get_cashpricecode($id)
{
	if (!empty($id) && $id != 00 ){
		$sWhere = " AND A.id = ".$id;
	}
	$sql = "SELECT A.id, A.scash_type
			FROM ".TB_PREF."sales_cash_type A 
			WHERE !A.inactive ".$sWhere."
			ORDER BY A.scash_type ASC ";
	return db_query($sql, "could not get cash price type");	
}

function get_pricecode($id)
{
	if (!empty($id) && $id != 00 ){
		$sWhere = " AND A.id = ".$id;
	}
	$sql = "SELECT A.id, A.sales_type 
			FROM ".TB_PREF."sales_types A 
			WHERE !A.inactive ".$sWhere."
			ORDER BY A.sales_type ASC ";
	return db_query($sql, "could not get sales type");	
}

function get_costcode($id)
{
	if (!empty($id) && $id != 00 ){
		$sWhere = " AND A.id = ".$id;
	}
	$sql = "SELECT A.id, A.cost_type 
			FROM ".TB_PREF."supp_cost_types A 
			WHERE !A.inactive ".$sWhere."
			ORDER BY A.cost_type ASC ";
	return db_query($sql, "could not get supplier cost type");	
}

function get_itemcategory($id)
{
	if (!empty($id) && $id != 00 ){
		$sWhere = " AND A.category_id = ".$id. " ";
	}
	$sql = "SELECT A.category_id, A.description 
			FROM ".TB_PREF."stock_category A 
			WHERE !A.inactive AND A.dflt_mb_flag = 'B' ".$sWhere."
			ORDER BY A.category_id ASC ";
	return db_query($sql, "could not get stock category");	
}

function get_srpcode($id)
{
	if (!empty($id) && $id != 00 ){
		$sWhere = " AND A.id = ".$id;
	}
	$sql = "SELECT A.id, A.srp_type 
			FROM ".TB_PREF."item_srp_area_types A 
			WHERE !A.inactive ".$sWhere."
			ORDER BY A.srp_type ASC ";
	return db_query($sql, "could not get item srp type");	
}

function get_instlplcy($id, $category)
{
	if (!empty($id) && $id != 00 ){
		$sWhere = " AND A.id = ".$id;
	}
	if (!empty($category)){
		$sWhere = " AND A.category_id = ".$category;
	}	
	$sql = "SELECT A.id, A.plcy_code, A.category_id
			FROM ".TB_PREF."policy_header A 
			WHERE !A.inactive ".$sWhere."
			ORDER BY A.plcy_code ASC ";
	return db_query($sql, "could not get sales installment policy type");	
}

function delete_branchplcy($id, $moduletype)
{
	$sql="DELETE FROM ".TB_PREF."branch_policy WHERE id=".db_escape($id)." AND module_type = ".db_escape($moduletype);
	db_query($sql,"The branch policy record could not be deleted");
}

function check_data_exist($branchcode, $branchname, $brpolicyid, $brcategoryid, $moduletype, $inactive, $affectedfld)
{
	$sql = "SELECT A.*
			FROM ".TB_PREF."branch_policy A 
			WHERE A.branch_code = ".db_escape($branchcode)."
				AND A.branch_name = ".db_escape($branchname)."
				AND A.category_id = ".db_escape($brcategoryid)."
				AND A.inactive = ".db_escape($inactive)."
				AND A.module_type = ".db_escape($moduletype)."
				AND A.".$affectedfld." = ".db_escape($brpolicyid);
				
	return db_query($sql, "could not get branch policy ");
}
