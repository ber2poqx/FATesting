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
function add_policy_builder($supplier_id, $category_id, $type_id, $remarks, $moduleType, $inactive)
{
	$sql = "INSERT INTO ".TB_PREF."policy_builder (
					supplier_id 
					,category_id
					,cstprctype_id
					,remarks 
					,inactive
					,module_type
			) VALUES (
			        ".db_escape($supplier_id)."
					,".db_escape($category_id)."
				   	,".db_escape($type_id)."
				   	,".db_escape($remarks)."
				   	,".db_escape($inactive)."
				   	,".db_escape($moduleType)."
			)";
	
	db_query($sql, "Could not insert the new policy builder ");		
}

function update_policy_builder($syspk, $supplier_id, $category_id, $type_id, $remarks, $moduleType, $inactive)
{
	$sql = "UPDATE ".TB_PREF."policy_builder 
			SET supplier_id = ".db_escape($supplier_id)."
				,category_id = ".db_escape($category_id)."
				,cstprctype_id = ".db_escape($type_id)."
				,remarks = ".db_escape($remarks)."
				,inactive = ".db_escape($inactive)."
			WHERE id = " .db_escape($syspk). " AND module_type = ".db_escape($moduleType);
	
	db_query($sql, "could not update policy builder ");	
}

function get_cboSupplier($id)
{
	$sql = "SELECT A.supplier_id, A.supp_ref, A.supp_name 
			FROM ".TB_PREF."suppliers A 
			ORDER BY A.supp_name ASC";

	return db_query($sql, "could not get supplier ");	
}

function get_cashpricecode($id)
{
	if (!empty($id) && $id != 00 ){
		$sWhere = " AND A.id = ".$id;
	}
	$sql = "SELECT A.id, A.scash_type type
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
	$sql = "SELECT A.id, A.sales_type type 
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
	$sql = "SELECT A.id, A.cost_type type
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
	$sql = "SELECT A.id, A.srp_type type
			FROM ".TB_PREF."item_srp_area_types A 
			WHERE !A.inactive ".$sWhere."
			ORDER BY A.srp_type ASC ";
	return db_query($sql, "could not get item srp type");	
}

function delete_policyBuilder($id, $moduletype)
{
	$sql="DELETE FROM ".TB_PREF."policy_builder WHERE id=".db_escape($id)." AND module_type = ".db_escape($moduletype);
	db_query($sql,"The policy builder record could not be deleted");
}

function check_data_exist($supplier_id, $category_id, $type, $module)
{
	$sql = "SELECT A.*
			FROM ".TB_PREF."policy_builder A 
			WHERE A.supplier_id = ".db_escape($supplier_id)."
				AND A.category_id = ".db_escape($category_id)."
				AND A.cstprctype_id = ".db_escape($type)."
				AND A.module_type = ".db_escape($module);
				
	return db_query($sql, "could not get policy builder ");
}

function get_buildsupplier($id)
{
	if (!empty($id) && $id != 00 ){
		$sWhere = " WHERE A.id = ".$id;
	}
		$sql = "SELECT A.*, B.supplier_id, B.supp_ref, B.supp_name
			FROM ".TB_PREF."policy_builder A 
				INNER JOIN ".TB_PREF."suppliers B ON B.supplier_id = A.supplier_id 
			".$sWhere."
			ORDER BY B.supp_name ASC ";
				
	return db_query($sql, "could not get policy builder ");
}

function get_buildermode($module, $supplier_id, $start, $limit, $category_id, $showall=false)
{
	$selecttype = "";
	$jointable = "";
	$sWhere = "";
	if (!empty($supplier_id) && $supplier_id != 00 ){
		$sWhere .= " AND A.supplier_id  = ".$supplier_id;
	}
	if (!empty($category_id) && $category_id != 00 ){
		$sWhere .= " AND A.category_id  = ".$category_id;
	}
	if (!empty($module) && isset($module)){
		if($module == "CSHPRCPLCY"){
			$selecttype = ", Z.scash_type cstprctype_name, Z.id cstprctype_id ";
			$jointable = "INNER JOIN ".TB_PREF."sales_cash_type Z ON Z.id = A.cstprctype_id ";
		}elseif($module == "PRCPLCY"){
			$selecttype = ", Z.sales_type cstprctype_name, Z.id cstprctype_id ";
			$jointable = "INNER JOIN ".TB_PREF."sales_types Z ON Z.id = A.cstprctype_id ";
		}elseif($module == "CSTPLCY"){
			$selecttype = ", Z.cost_type cstprctype_name, Z.id cstprctype_id ";
			$jointable = "INNER JOIN ".TB_PREF."supp_cost_types Z ON Z.id = A.cstprctype_id ";
		}elseif($module == "SRPPLCY"){
			$selecttype = ", Z.srp_type cstprctype_name, Z.id cstprctype_id ";
			$jointable = "INNER JOIN ".TB_PREF."item_srp_area_types Z ON Z.id = A.cstprctype_id ";
		}
	}

	$sql = "SELECT A.*, B.supplier_id, B.supp_ref, B.supp_name, C.category_id, C.description ".$selecttype."
		FROM ".TB_PREF."policy_builder A 
			INNER JOIN ".TB_PREF."suppliers B ON B.supplier_id = A.supplier_id 
			INNER JOIN ".TB_PREF."stock_category C ON C.category_id = A.category_id  
			".$jointable."
		WHERE A.module_type = ".db_escape($module)." ".$sWhere."
		ORDER BY B.supp_name ASC ";
		
	return db_query($sql, "could not get policy builder ");
}

//------------------------------------------// check operator

function get_selectedUser($role_id, $criteria){
	if($criteria == "equal"){
		//$sWhere = "WHERE A.role_id = ".db_escape($role_id)." AND (A.user_id != 'prog6' AND A.user_id != '008001') ";
		$sWhere = "WHERE A.role_id = ".db_escape($role_id);
	}else if($criteria == "notEq"){
		$sWhere = "WHERE A.role_id != ".db_escape($role_id);
	}

	$sql = "SELECT A.user_id, A.real_name, A.role_id FROM users A 
			".$sWhere."
			ORDER BY A.real_name ASC ";
	
	//echo $sql;
	return db_query($sql, "could not get cashier ");
}

function add_operator_builder($cashier_id, $preparer_id, $inactive)
{
	$sql = "INSERT INTO ".TB_PREF."operator_builder (
					cashier_id 
					,preparer_id
					,inactive
			) VALUES (
			        ".db_escape($cashier_id)."
					,".db_escape($preparer_id)."
				   	,".db_escape($inactive)."
			)";
	
	db_query($sql, "Could not insert the new checkout operator builder ");		
}

function update_operator_builder($syspk, $cashier_id, $preparer_id, $inactive)
{
	$sql = "UPDATE ".TB_PREF."operator_builder 
			SET cashier_id = ".db_escape($cashier_id)."
				,preparer_id = ".db_escape($preparer_id)."
				,inactive = ".db_escape($inactive)."
			WHERE id = " .db_escape($syspk);
	
	db_query($sql, "could not update checkout operator builder ");	
}

function delete_operator_Builder($id)
{
	$sql="DELETE FROM ".TB_PREF."operator_builder WHERE id=".db_escape($id);
	db_query($sql,"The checkout operator builder record could not be deleted");
}

function check_data_exist_OP($cashier_id, $preparer_id)
{
	$sql = "SELECT A.*
			FROM ".TB_PREF."operator_builder A 
			WHERE A.cashier_id = ".db_escape($cashier_id)."
				AND A.preparer_id = ".db_escape($preparer_id);
				
	return db_query($sql, "could not get checkout operator builder ");
}

function get_checkout_operator($id, $start, $limit, $query)
{
	$sWhere = ""; //Added by spyrax10 3 Feb 20222

	if($query != ""){
		$sWhere = " WHERE (B.real_name LIKE '%".$query."%' OR C.real_name LIKE '%".$query."%') ";
	}
	$sql = "SELECT A.id, B.user_id cashier_id, B.real_name cashier_name, C.user_id preparer_id, C.real_name preparer_name, A.inactive
			FROM ".TB_PREF."operator_builder A 
				INNER JOIN ".TB_PREF."users B ON B.user_id = A.cashier_id
				INNER JOIN ".TB_PREF."users C ON C.user_id = A.preparer_id 
			".$sWhere."
			ORDER BY cashier_name ASC ";
		
	return db_query($sql, "could not get checkout operator builder ");
}