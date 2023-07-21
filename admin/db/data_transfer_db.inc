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
//items
function add_to_datalogs($date, $remarks, $prepared)
{
	set_global_connection();
	$sql = "INSERT INTO ".TB_PREF."data_logs (
		date_upload 
		,remarks 				
		,prepared							
	) VALUES (
		".db_escape($date)."
		,".db_escape($remarks)."
		,".db_escape($prepared)."
	)";
	$MessageError = _('Could not insert data logs');
	db_query($sql, $MessageError);
}

function get_data_logs($start, $limit, $showall=false)
{
	set_global_connection();
	$sql = "SELECT A.*, B.real_name
		FROM ".TB_PREF."data_logs A
		INNER JOIN  ".TB_PREF."users B ON A.prepared = B.user_id
		ORDER BY A.id DESC";

	$sqllimit = $sql ." limit $start, $limit";

	if($showall){
		$result = db_query($sql, "could not get data logs");
	}else{
		$result = db_query($sqllimit, "could not get data logs");
	}
	return $result;
}

function get_items_to_transfer($date_from, $date_to)
{
	set_global_connection();
	$sql = "SELECT A.*
		FROM ".TB_PREF."stock_master A 
		WHERE A.date_modified BETWEEN ".db_escape($date_from). " AND ".db_escape($date_to);

	$result = db_query($sql, "could not get item");

    return $result;
}

function check_item($stock_id, $target_branch)
{
	$db_coy = Get_db_coy($target_branch);
	set_global_connection($db_coy);

	$sql = "SELECT A.stock_id
			FROM ".TB_PREF."stock_master A 
			WHERE A.stock_id = ".db_escape($stock_id);

	$result = db_query($sql, "could not get stock item ");

	set_global_connection();
	return $result;
}

function add_to_stockmaster($stockid, $category, $tax_id, $descrip, $descrip_long, $units, $mbflag, $sales_accnt, $install_accnt, $regular_accnt, 
	$cogs_accnt, $invent_accnt, $adjust_accnt, $wip_accnt, $dimension1, $dimension2, $brand, $manufacturer, $distributor, $importer, $old_code, $sap_code, 
	$serialised, $date_modified, $targetbranch) 
{
	$db_coy1 = Get_db_coy($targetbranch);
	set_global_connection($db_coy1);

	$sql = "INSERT INTO ".TB_PREF."stock_master (
					stock_id 
					,category_id
					,tax_type_id
					,description
					,long_description 
					,units
					,mb_flag
					,sales_account
					,installment_sales_account
					,regular_sales_account
					,cogs_account
					,inventory_account
					,adjustment_account
					,wip_account
					,dimension_id
					,dimension2_id
					,brand
					,manufacturer
					,distributor
					,importer
					,old_code
					,sap_code
					,serialised
					,date_modified
			) VALUES (
					".db_escape($stockid)."
					,".db_escape($category)."
					,".db_escape($tax_id)."
					,".db_escape($descrip)."
					,".db_escape($descrip_long)."
					,".db_escape($units)."
					,".db_escape($mbflag)."
					,".db_escape($sales_accnt)."
					,".db_escape($install_accnt)."
					,".db_escape($regular_accnt)."
					,".db_escape($cogs_accnt)."
					,".db_escape($invent_accnt)."
					,".db_escape($adjust_accnt)."
					,".db_escape($wip_accnt)."
					,".db_escape($dimension1)."
					,".db_escape($dimension2)."
					,".db_escape($brand)."
					,".db_escape($manufacturer)."
					,".db_escape($distributor)."
					,".db_escape($importer)."
					,".db_escape($old_code)."
					,".db_escape($sap_code)."
					,".db_escape($serialised)."
					,".db_escape($date_modified)."
			)";
	$MessageError = _('Could not insert items into stock master');
	db_query($sql, $MessageError);
}

function update_to_stockmaster($stockid, $category, $tax_id, $descrip, $descrip_long, $units, $mbflag, $sales_accnt, $install_accnt, $regular_accnt, 
	$cogs_accnt, $invent_accnt, $adjust_accnt, $wip_accnt, $dimension1, $dimension2, $brand, $manufacturer, $distributor, $importer, $old_code, $sap_code, 
	$serialised, $date_modified, $targetbranch1)
{
	$db_coy2 = Get_db_coy($targetbranch1);
	set_global_connection($db_coy2);

	$sql = "UPDATE ".TB_PREF."stock_master 
			SET category_id = ".db_escape($category)."
				,tax_type_id = ".db_escape($tax_id)."
				,description = ".db_escape($descrip)."
				,long_description = ".db_escape($descrip_long)."
				,units = ".db_escape($units)."
				,mb_flag = ".db_escape($mbflag)."
				,sales_account = ".db_escape($sales_accnt)."
				,installment_sales_account = ".db_escape($install_accnt)."
				,regular_sales_account = ".db_escape($regular_accnt)."
				,cogs_account = ".db_escape($cogs_accnt)."
				,inventory_account = ".db_escape($invent_accnt)."
				,adjustment_account = ".db_escape($adjust_accnt)."
				,wip_account = ".db_escape($wip_accnt)."
				,dimension_id = ".db_escape($dimension1)."
				,dimension2_id = ".db_escape($dimension2)."
				,brand = ".db_escape($brand)."
				,manufacturer = ".db_escape($manufacturer)."
				,distributor = ".db_escape($distributor)."
				,importer = ".db_escape($importer)."
				,old_code = ".db_escape($old_code)."
				,sap_code = ".db_escape($sap_code)."
				,serialised = ".db_escape($serialised)."
				,date_modified = ".db_escape($date_modified)."
			WHERE stock_id = " .db_escape($stockid);

	db_query($sql, "could not update stock master ");	
}
//

//Item Codes
function get_itemcodes_to_transfer($date_from, $date_to)
{
	set_global_connection();
	$sql = "SELECT A.*
		FROM ".TB_PREF."item_codes A 
		WHERE A.date_modified BETWEEN ".db_escape($date_from). " AND ".db_escape($date_to);

	$result = db_query($sql, "could not get item");

    return $result;
}

function check_item_codes($item_code, $target_branch)
{
	$db_coy = Get_db_coy($target_branch);
	set_global_connection($db_coy);

	$sql = "SELECT A.item_code
			FROM ".TB_PREF."item_codes A 
			WHERE A.item_code = ".db_escape($item_code);

	$result = db_query($sql, "could not get item codes ");

	set_global_connection();
	return $result;
}

function add_to_itemcodes($itemcode, $stockid, $descrip, $category, $qty, $foreign, $inactive, $brand, $manufacturer, $distributor, 
	$importer, $status, $pnp_color, $color, $old_code, $date_modified, $targetbranch) 
{
	$db_coy1 = Get_db_coy($targetbranch);
	set_global_connection($db_coy1);

	$sql = "INSERT INTO ".TB_PREF."item_codes (
					item_code 
					,stock_id
					,description
					,category_id
					,quantity 
					,is_foreign
					,inactive
					,brand
					,manufacturer
					,distributor
					,importer
					,product_status
					,pnp_color
					,color
					,old_code
					,date_modified					
			) VALUES (
					".db_escape($itemcode)."
					,".db_escape($stockid)."
					,".db_escape($descrip)."
					,".db_escape($category)."
					,".db_escape($qty)."
					,".db_escape($foreign)."
					,".db_escape($inactive)."
					,".db_escape($brand)."
					,".db_escape($manufacturer)."
					,".db_escape($distributor)."
					,".db_escape($importer)."
					,".db_escape($status)."
					,".db_escape($pnp_color)."
					,".db_escape($color)."
					,".db_escape($old_code)."
					,".db_escape($date_modified)."
			)";
	$MessageError = _('Could not insert product codes into item codes');
	db_query($sql, $MessageError);
}

function update_to_itemcodes($itemcode, $stockid, $descrip, $category, $qty, $foreign, $inactive, $brand, $manufacturer, $distributor, 
	$importer, $status, $pnp_color, $color, $old_code, $date_modified, $targetbranch1)
{
	$db_coy2 = Get_db_coy($targetbranch1);
	set_global_connection($db_coy2);

	$sql = "UPDATE ".TB_PREF."item_codes 
			SET stock_id = ".db_escape($stockid)."
				,description = ".db_escape($descrip)."
				,category_id = ".db_escape($category)."
				,quantity = ".db_escape($qty)."
				,is_foreign = ".db_escape($foreign)."
				,inactive = ".db_escape($inactive)."
				,brand = ".db_escape($brand)."
				,manufacturer = ".db_escape($manufacturer)."
				,distributor = ".db_escape($distributor)."
				,importer = ".db_escape($importer)."
				,product_status = ".db_escape($status)."
				,pnp_color = ".db_escape($pnp_color)."
				,color = ".db_escape($color)."
				,old_code = ".db_escape($old_code)."
				,date_modified = ".db_escape($date_modified)."
			WHERE item_code = " .db_escape($itemcode);

	db_query($sql, "could not update product codes ");	
}
//

//Policy
function get_policy_to_transfer($date_from, $date_to)
{
	set_global_connection();
	$sql = "SELECT A.*
		FROM ".TB_PREF."policy_header A 
		WHERE A.date_defined BETWEEN ".db_escape($date_from). " AND ".db_escape($date_to);

	$result = db_query($sql, "could not get policy");

    return $result;
}

function check_plcy_code($plcycode, $target_branch)
{
	$db_coy = Get_db_coy($target_branch);
	set_global_connection($db_coy);

	$sql = "SELECT A.plcy_code
			FROM ".TB_PREF."policy_header A 
			WHERE A.plcy_code = ".db_escape($plcycode);

	$result = db_query($sql, "could not get policy codes ");

	set_global_connection();
	return $result;
}

function add_to_policy($plcycode, $name, $tax, $factor, $date_defined, $remarks, $module, $inactive, $category, $branch_id, $targetbranch1) 
{
	$db_coy1 = Get_db_coy($targetbranch1);
	set_global_connection($db_coy1);

	$sql = "INSERT INTO ".TB_PREF."policy_header (
					plcy_code 
					,name
					,tax_included
					,factor
					,date_defined 
					,remarks
					,module_type
					,inactive
					,category_id
					,branch_area_id				
			) VALUES (
					".db_escape($plcycode)."
					,".db_escape($name)."
					,".db_escape($tax)."
					,".db_escape($factor)."
					,".db_escape($date_defined)."
					,".db_escape($remarks)."
					,".db_escape($module)."
					,".db_escape($inactive)."
					,".db_escape($category)."
					,".db_escape($branch_id)."
			)";
	$MessageError = _('Could not insert policy code into policy header');
	db_query($sql, $MessageError);
}

function get_policy_details_to_transfer($date_from, $date_to)
{
	set_global_connection();
	$sql = "SELECT A.*
		FROM ".TB_PREF."policy_details A 
		WHERE A.date_defined BETWEEN ".db_escape($date_from). " AND ".db_escape($date_to);

	$result1 = db_query($sql, "could not get policy details");

    return $result1;
}

function check_plcydetails_code($plcycode, $target_branch2)
{
	$db_coy2 = Get_db_coy($target_branch2);
	set_global_connection($db_coy2);

	$sql = "SELECT A.plcydtl_code
			FROM ".TB_PREF."policy_details A 
			WHERE A.plcydtl_code = ".db_escape($plcycode);

	$result = db_query($sql, "could not get policy codes ");

	set_global_connection();
	return $result;
}

function add_to_policy_details($plcycode, $descrip, $tax, $factor, $date_defined, $remarks, $module, $inactive, $term, $financing, $rebate, $penalty, $category, $policy_id, $targetbranch3) 
{
	$db_coy3 = Get_db_coy($targetbranch3);
	set_global_connection($db_coy3);

	$sql = "INSERT INTO ".TB_PREF."policy_details (
					plcydtl_code 
					,description
					,tax_included
					,factor
					,date_defined 
					,remarks
					,module_type
					,inactive
					,term
					,financing_rate				
					,rebate				
					,penalty				
					,category_id				
					,policyhdr_id				
			) VALUES (
					".db_escape($plcycode)."
					,".db_escape($descrip)."
					,".db_escape($tax)."
					,".db_escape($factor)."
					,".db_escape($date_defined)."
					,".db_escape($remarks)."
					,".db_escape($module)."
					,".db_escape($inactive)."
					,".db_escape($term)."
					,".db_escape($financing)."
					,".db_escape($rebate)."
					,".db_escape($penalty)."
					,".db_escape($category)."
					,".db_escape($policy_id)."
			)";
	$MessageError = _('Could not insert policy code into policy details');
	db_query($sql, $MessageError);
}
//

//Supplier
function get_supplier_to_transfer()
{
	set_global_connection();
	$sql = "SELECT A.*
		FROM ".TB_PREF."suppliers A";

	$result = db_query($sql, "could not get supplier");

    return $result;
}

function check_supplier($id, $target_branch)
{
	$db_coy = Get_db_coy($target_branch);
	set_global_connection($db_coy);

	$sql = "SELECT A.supplier_id
			FROM ".TB_PREF."suppliers A 
			WHERE A.supplier_id = ".db_escape($id);

	$result = db_query($sql, "could not get supplier ");

	set_global_connection();
	return $result;;
}

function add_to_supplier($supplname, $suppref, $address, $suppaddress, $gstno, $contact, $suppaccount, $website, $bankaccount, $currcode, $payment, $tax_inc, $dimension1, 
	$dimension2, $tax_group, $credit, $purchase_accnt, $payable_accnt, $paymnet_disct, $notes, $inactive, $supplier_grp, $sapcode, $supplier_type, $targetbranch1) 
{
	$db_coy1 = Get_db_coy($targetbranch1);
	set_global_connection($db_coy1);

	$sql = "INSERT INTO ".TB_PREF."suppliers (
					supp_name 
					,supp_ref
					,address
					,supp_address
					,gst_no 
					,contact
					,supp_account_no
					,website
					,bank_account
					,curr_code
					,payment_terms				
					,tax_included				
					,dimension_id				
					,dimension2_id				
					,tax_group_id				
					,credit_limit				
					,purchase_account				
					,payable_account				
					,payment_discount_account				
					,notes				
					,inactive				
					,supplier_group				
					,SAPcode				
					,supplier_type				
			) VALUES (
					".db_escape($supplname)."
					,".db_escape($suppref)."
					,".db_escape($address)."
					,".db_escape($suppaddress)."
					,".db_escape($gstno)."
					,".db_escape($contact)."
					,".db_escape($suppaccount)."
					,".db_escape($website)."
					,".db_escape($bankaccount)."
					,".db_escape($currcode)."
					,".db_escape($payment)."
					,".db_escape($tax_inc)."
					,".db_escape($dimension1)."
					,".db_escape($dimension2)."
					,".db_escape($tax_group)."
					,".db_escape($credit)."
					,".db_escape($purchase_accnt)."
					,".db_escape($payable_accnt)."
					,".db_escape($paymnet_disct)."
					,".db_escape($notes)."
					,".db_escape($inactive)."
					,".db_escape($supplier_grp)."
					,".db_escape($sapcode)."
					,".db_escape($supplier_type)."
			)";
	$MessageError = _('Could not insert supplier');
	db_query($sql, $MessageError);
}

function update_to_supplier($id, $supplname, $suppref, $address, $suppaddress, $gstno, $contact, $suppaccount, $website, $bankaccount, $currcode, $payment, $tax_inc, $dimension1, 
	$dimension2, $tax_group, $credit, $purchase_accnt, $payable_accnt, $paymnet_disct, $notes, $inactive, $supplier_grp, $sapcode, $supplier_type, $targetbranch2)
{
	$db_coy2 = Get_db_coy($targetbranch2);
	set_global_connection($db_coy2);

	$sql = "UPDATE ".TB_PREF."suppliers 
			SET supp_name = ".db_escape($supplname)."
				,supp_ref = ".db_escape($suppref)."
				,address = ".db_escape($address)."
				,supp_address = ".db_escape($suppaddress)."
				,gst_no = ".db_escape($gstno)."
				,contact = ".db_escape($contact)."
				,supp_account_no = ".db_escape($suppaccount)."
				,website = ".db_escape($website)."
				,bank_account = ".db_escape($bankaccount)."
				,curr_code = ".db_escape($currcode)."
				,payment_terms = ".db_escape($payment)."
				,tax_included = ".db_escape($tax_inc)."
				,dimension_id = ".db_escape($dimension1)."
				,dimension2_id = ".db_escape($dimension2)."
				,tax_group_id = ".db_escape($tax_group)."
				,credit_limit = ".db_escape($credit)."
				,purchase_account = ".db_escape($purchase_accnt)."
				,payable_account = ".db_escape($payable_accnt)."
				,payment_discount_account = ".db_escape($paymnet_disct)."
				,notes = ".db_escape($notes)."
				,inactive = ".db_escape($inactive)."
				,supplier_group = ".db_escape($supplier_grp)."
				,SAPcode = ".db_escape($sapcode)."
				,supplier_type = ".db_escape($supplier_type)."
			WHERE supplier_id = " .db_escape($id);

	db_query($sql, "could not update supplier ");	
}
//

//Brand
function get_brand_to_transfer()
{
	set_global_connection();
	$sql = "SELECT A.*
		FROM ".TB_PREF."item_brand A";

	$result = db_query($sql, "could not get brand");

    return $result;
}

function check_brand($id, $target_branch)
{
	$db_coy = Get_db_coy($target_branch);
	set_global_connection($db_coy);

	$sql = "SELECT A.id
			FROM ".TB_PREF."item_brand A 
			WHERE A.id = ".db_escape($id);

	$result = db_query($sql, "could not get supplier ");

	set_global_connection();
	return $result;
}

function add_to_brand($code, $name, $category, $inactive, $targetbranch1)
{
	$db_coy1 = Get_db_coy($targetbranch1);
	set_global_connection($db_coy1);

	$sql = "INSERT INTO ".TB_PREF."item_brand (
		code 
		,name				
		,cat_id				
		,inactive				
	) VALUES (
		".db_escape($code)."
		,".db_escape($name)."
		,".db_escape($category)."
		,".db_escape($inactive)."
	)";
	$MessageError = _('Could not insert brand');
	db_query($sql, $MessageError);
}

function update_to_brand($id, $code, $name, $category, $inactive, $targetbranch2)
{
	$db_coy2 = Get_db_coy($targetbranch2);
	set_global_connection($db_coy2);

	$sql = "UPDATE ".TB_PREF."item_brand
			SET code = ".db_escape($code)."
				,name = ".db_escape($name)."
				,cat_id = ".db_escape($category)."
				,inactive = ".db_escape($inactive)."
			WHERE id = ".db_escape($id);

	db_query($sql, "could not update brand");
}
//

//Classification
function get_classification_to_transfer()
{
	set_global_connection();
	$sql = "SELECT A.*
		FROM ".TB_PREF."item_importer A";

	$result = db_query($sql, "could not get classification");

    return $result;
}

function check_class($id, $target_branch)
{
	$db_coy = Get_db_coy($target_branch);
	set_global_connection($db_coy);

	$sql = "SELECT A.id
			FROM ".TB_PREF."item_importer A 
			WHERE A.id = ".db_escape($id);

	$result = db_query($sql, "could not get classification ");

	set_global_connection();
	return $result;
}

function add_to_classification($code, $name, $inactive, $targetbranch1)
{
	$db_coy1 = Get_db_coy($targetbranch1);
	set_global_connection($db_coy1);

	$sql = "INSERT INTO ".TB_PREF."item_importer (
		code 
		,name						
		,inactive				
	) VALUES (
		".db_escape($code)."
		,".db_escape($name)."
		,".db_escape($inactive)."
	)";
	$MessageError = _('Could not insert classification');
	db_query($sql, $MessageError);
}

function update_to_classification($id, $code, $name, $inactive, $targetbranch2)
{
	$db_coy2 = Get_db_coy($targetbranch2);
	set_global_connection($db_coy2);

	$sql = "UPDATE ".TB_PREF."item_importer
			SET code = ".db_escape($code)."
				,name = ".db_escape($name)."
				,inactive = ".db_escape($inactive)."
			WHERE id = ".db_escape($id);

	db_query($sql, "could not update classification");
}
//

//Sub Category
function get_subcategory_to_transfer()
{
	set_global_connection();
	$sql = "SELECT A.*
		FROM ".TB_PREF."item_distributor A";

	$result = db_query($sql, "could not get sub-category");

    return $result;
}

function check_sub_categ($id, $target_branch)
{
	$db_coy = Get_db_coy($target_branch);
	set_global_connection($db_coy);

	$sql = "SELECT A.name
			FROM ".TB_PREF."item_distributor A 
			WHERE A.id = ".db_escape($id);

	$result = db_query($sql, "could not get sub-category ");

	set_global_connection();
	return $result;
}

function add_to_subcategory($code, $name, $inactive, $targetbranch1)
{
	$db_coy1 = Get_db_coy($targetbranch1);
	set_global_connection($db_coy1);

	$sql = "INSERT INTO ".TB_PREF."item_distributor (
		code 
		,name						
		,inactive				
	) VALUES (
		".db_escape($code)."
		,".db_escape($name)."
		,".db_escape($inactive)."
	)";
	$MessageError = _('Could not insert sub-category');
	db_query($sql, $MessageError);
}

function update_to_subcategory($id, $code, $name, $inactive, $targetbranch2)
{
	$db_coy2 = Get_db_coy($targetbranch2);
	set_global_connection($db_coy2);

	$sql = "UPDATE ".TB_PREF."item_distributor
			SET code = ".db_escape($code)."
				,name = ".db_escape($name)."
				,inactive = ".db_escape($inactive)."
			WHERE id = ".db_escape($id);

	db_query($sql, "could not update sub-category");
}
//

//GL
function get_glaccount_to_transfer()
{
	set_global_connection();
	$sql = "SELECT A.*
		FROM ".TB_PREF."chart_master A";

	$result = db_query($sql, "could not get gl account");

    return $result;
}

function check_glaccount($glcode, $target_branch)
{
	$db_coy = Get_db_coy($target_branch);
	set_global_connection($db_coy);

	$sql = "SELECT A.account_code
			FROM ".TB_PREF."chart_master A 
			WHERE A.account_code = ".db_escape($glcode);

	$result = db_query($sql, "could not get gl account ");

	set_global_connection();
	return $result;
}

function add_to_glaccount($code, $code2, $name, $type, $inactive, $control, $balance, $targetbranch1)
{
	$db_coy1 = Get_db_coy($targetbranch1);
	set_global_connection($db_coy1);

	$sql = "INSERT INTO ".TB_PREF."chart_master (
		account_code 
		,account_code2						
		,account_name				
		,account_type				
		,inactive				
		,control				
		,normal_balance				
	) VALUES (
		".db_escape($code)."
		,".db_escape($code2)."
		,".db_escape($name)."
		,".db_escape($type)."
		,".db_escape($inactive)."
		,".db_escape($control)."
		,".db_escape($balance)."
	)";
	$MessageError = _('Could not insert gl account');
	db_query($sql, $MessageError);
}

function update_to_glaccount($code, $code2, $name, $type, $inactive, $control, $balance, $targetbranch2)
{
	$db_coy2 = Get_db_coy($targetbranch2);
	set_global_connection($db_coy2);

	$sql = "UPDATE ".TB_PREF."chart_master
			SET account_code2 = ".db_escape($code2)."
				,account_name = ".db_escape($name)."
				,account_type = ".db_escape($type)."
				,inactive = ".db_escape($inactive)."
				,control = ".db_escape($control)."
				,normal_balance = ".db_escape($balance)."
			WHERE account_code = ".db_escape($code);

	db_query($sql, "could not update gl account");
}
//

//Bank
function get_bankaccount_to_transfer()
{
	set_global_connection();
	$sql = "SELECT A.*
		FROM ".TB_PREF."bank_accounts A";

	$result = db_query($sql, "could not get bank account");

    return $result;
}

function check_bankaccount($id, $target_branch)
{
	$db_coy = Get_db_coy($target_branch);
	set_global_connection($db_coy);

	$sql = "SELECT A.id
			FROM ".TB_PREF."bank_accounts A 
			WHERE A.id = ".db_escape($id);

	$result = db_query($sql, "could not get bank account ");

	set_global_connection();
	return $result;
}

function add_to_bankaccount($code, $type, $name, $number, $name2, $address, $curr, $curract, $charge, $reconciled, $balance, $inactive, $targetbranch1)
{
	$db_coy1 = Get_db_coy($targetbranch1);
	set_global_connection($db_coy1);

	$sql = "INSERT INTO ".TB_PREF."bank_accounts (
		account_code 
		,account_type						
		,bank_account_name				
		,bank_account_number				
		,bank_name				
		,bank_address				
		,bank_curr_code				
		,dflt_curr_act				
		,bank_charge_act				
		,last_reconciled_date				
		,ending_reconcile_balance				
		,inactive				
	) VALUES (
		".db_escape($code)."
		,".db_escape($type)."
		,".db_escape($name)."
		,".db_escape($number)."
		,".db_escape($name2)."
		,".db_escape($address)."
		,".db_escape($curr)."
		,".db_escape($curract)."
		,".db_escape($charge)."
		,".db_escape($reconciled)."
		,".db_escape($balance)."
		,".db_escape($inactive)."
	)";
	$MessageError = _('Could not insert bank account');
	db_query($sql, $MessageError);
}

function update_to_bankaccount($code, $type, $name, $number, $name2, $address, $curr, $curract, $id, $charge, $reconciled, $balance, $inactive, $targetbranch2)
{
	$db_coy2 = Get_db_coy($targetbranch2);
	set_global_connection($db_coy2);

	$sql = "UPDATE ".TB_PREF."bank_accounts
			SET account_code = ".db_escape($code)."
				,account_type = ".db_escape($type)."
				,bank_account_name = ".db_escape($name)."
				,bank_account_number = ".db_escape($number)."
				,bank_name = ".db_escape($name2)."
				,bank_address = ".db_escape($address)."
				,bank_curr_code = ".db_escape($curr)."
				,dflt_curr_act = ".db_escape($curract)."
				,bank_charge_act = ".db_escape($charge)."
				,last_reconciled_date = ".db_escape($reconciled)."
				,ending_reconcile_balance = ".db_escape($balance)."
				,inactive = ".db_escape($inactive)."
			WHERE id = ".db_escape($id);

	db_query($sql, "could not update bank account");
}
//

//GL Group/Classes
function get_glaccountgroup_to_transfer()
{
	set_global_connection();
	$sql = "SELECT A.*
		FROM ".TB_PREF."chart_types A";

	$result = db_query($sql, "could not get gl account group");

    return $result;
}

function check_groupaccount($id, $target_branch)
{
	$db_coy = Get_db_coy($target_branch);
	set_global_connection($db_coy);

	$sql = "SELECT A.id
			FROM ".TB_PREF."chart_types A 
			WHERE A.id = ".db_escape($id);

	$result = db_query($sql, "could not get group account ");

	set_global_connection();
	return $result;
}

function add_to_accountgrp($id, $name, $class, $parent, $inactive, $targetbranch1)
{
	$db_coy1 = Get_db_coy($targetbranch1);
	set_global_connection($db_coy1);

	$sql = "INSERT INTO ".TB_PREF."chart_types (
		id 
		,name						
		,class_id				
		,parent				
		,inactive						
	) VALUES (
		".db_escape($id)."
		,".db_escape($name)."
		,".db_escape($class)."
		,".db_escape($parent)."
		,".db_escape($inactive)."
	)";
	$MessageError = _('Could not insert group account');
	db_query($sql, $MessageError);
}

function update_to_acountgrp($id, $name, $class, $parent, $inactive, $targetbranch2)
{
	$db_coy2 = Get_db_coy($targetbranch2);
	set_global_connection($db_coy2);

	$sql = "UPDATE ".TB_PREF."chart_types
			SET name = ".db_escape($name)."
				,class_id = ".db_escape($class)."
				,parent = ".db_escape($parent)."
				,inactive = ".db_escape($inactive)."
			WHERE id = ".db_escape($id);

	db_query($sql, "could not update group account");
}

function get_glaccountclass_to_transfer()
{
	set_global_connection();
	$sql = "SELECT A.*
		FROM ".TB_PREF."chart_class A";

	$result1 = db_query($sql, "could not get gl account class");

    return $result1;
}

function check_classaccount($id, $target_branch3)
{
	$db_coy3 = Get_db_coy($target_branch3);
	set_global_connection($db_coy3);

	$sql = "SELECT A.cid
			FROM ".TB_PREF."chart_class A 
			WHERE A.cid = ".db_escape($id);

	$result = db_query($sql, "could not get class account ");

	set_global_connection();
	return $result;
}

function add_to_classaccount($id, $name, $type, $inactive, $targetbranch4)
{
	$db_coy4 = Get_db_coy($targetbranch4);
	set_global_connection($db_coy4);

	$sql = "INSERT INTO ".TB_PREF."chart_class (
		cid 
		,class_name						
		,ctype				
		,inactive									
	) VALUES (
		".db_escape($id)."
		,".db_escape($name)."
		,".db_escape($type)."
		,".db_escape($inactive)."
	)";
	$MessageError = _('Could not insert class account');
	db_query($sql, $MessageError);
}

function update_to_classaccount($id, $name, $type, $inactive, $targetbranch5)
{
	$db_coy5 = Get_db_coy($targetbranch5);
	set_global_connection($db_coy5);

	$sql = "UPDATE ".TB_PREF."chart_class
			SET class_name = ".db_escape($name)."
				,ctype = ".db_escape($type)."
				,inactive = ".db_escape($inactive)."
			WHERE cid = ".db_escape($id);

	db_query($sql, "could not update class account");
}
//

//System Cost
function get_systemcost_to_transfer()
{
	set_global_connection();
	$sql = "SELECT A.*
		FROM ".TB_PREF."supp_cost_types A";

	$result = db_query($sql, "could not get system cost");

    return $result;
}

function check_systemcost($id, $target_branch)
{
	$db_coy = Get_db_coy($target_branch);
	set_global_connection($db_coy);

	$sql = "SELECT A.id
			FROM ".TB_PREF."supp_cost_types A 
			WHERE A.id = ".db_escape($id);

	$result = db_query($sql, "could not get group account ");

	set_global_connection();
	return $result;
}

function add_to_systemcost($type, $tax, $factor, $inactive, $targetbranch1)
{
	$db_coy1 = Get_db_coy($targetbranch1);
	set_global_connection($db_coy1);

	$sql = "INSERT INTO ".TB_PREF."supp_cost_types (
		cost_type 			
		,tax_included				
		,factor				
		,inactive						
	) VALUES (
		".db_escape($type)."
		,".db_escape($tax)."
		,".db_escape($factor)."
		,".db_escape($inactive)."
	)";
	$MessageError = _('Could not insert system cost');
	db_query($sql, $MessageError);
}

function update_to_systemcost($id, $type, $tax, $factor, $inactive, $targetbranch2)
{
	$db_coy2 = Get_db_coy($targetbranch2);
	set_global_connection($db_coy2);

	$sql = "UPDATE ".TB_PREF."supp_cost_types
			SET cost_type = ".db_escape($type)."
				,tax_included = ".db_escape($tax)."
				,factor = ".db_escape($factor)."
				,inactive = ".db_escape($inactive)."
			WHERE id = ".db_escape($id);

	db_query($sql, "could not update system cost");
}
//

//User
function get_user_to_transfer()
{
	set_global_connection();
	$sql = "SELECT A.*
		FROM ".TB_PREF."users A";

	$result = db_query($sql, "could not get users");

    return $result;
}

function check_datauser($userid, $target_branch)
{
	$db_coy = Get_db_coy($target_branch);
	set_global_connection($db_coy);

	$sql = "SELECT A.user_id
			FROM ".TB_PREF."users A 
			WHERE A.user_id = ".db_escape($userid);

	$result = db_query($sql, "could not get group account ");

	set_global_connection();
	return $result;
}

function add_to_user($userid, $pass, $name, $role, $phone, $email, $language, $d_format, $date_sep, $tho_sep, $dec_sep, $theme, $page,
	$price, $qty, $rates, $percent, $show_g, $show_c, $show_h, $query, $graphics, $pos, $print, $rep, $sticky, $startup, $transaction, 
	$save_report, $date_picker, $destination, $orientation, $inactive, $passdate, $targetbranch1)
{
	$db_coy1 = Get_db_coy($targetbranch1);
	set_global_connection($db_coy1);

	$sql = "INSERT INTO ".TB_PREF."users (
		user_id 			
		,password				
		,real_name				
		,role_id						
		,phone						
		,email						
		,language						
		,date_format						
		,date_sep						
		,tho_sep						
		,dec_sep						
		,theme						
		,page_size						
		,prices_dec						
		,qty_dec						
		,rates_dec						
		,percent_dec						
		,show_gl						
		,show_codes						
		,show_hints									
		,query_size						
		,graphic_links						
		,pos						
		,print_profile						
		,rep_popup						
		,sticky_doc_date						
		,startup_tab						
		,transaction_days						
		,save_report_selections						
		,use_date_picker						
		,def_print_destination						
		,def_print_orientation						
		,inactive						
		,passupdate						
	) VALUES (
		".db_escape($userid)."
		,".db_escape($pass)."
		,".db_escape($name)."
		,".db_escape($role)."
		,".db_escape($phone)."
		,".db_escape($email)."
		,".db_escape($language)."
		,".db_escape($d_format)."
		,".db_escape($date_sep)."
		,".db_escape($tho_sep)."
		,".db_escape($dec_sep)."
		,".db_escape($theme)."
		,".db_escape($page)."
		,".db_escape($price)."
		,".db_escape($qty)."
		,".db_escape($rates)."
		,".db_escape($percent)."
		,".db_escape($show_g)."
		,".db_escape($show_c)."
		,".db_escape($show_h)."
		,".db_escape($query)."
		,".db_escape($graphics)."
		,".db_escape($pos)."
		,".db_escape($print)."
		,".db_escape($rep)."
		,".db_escape($sticky)."
		,".db_escape($startup)."
		,".db_escape($transaction)."
		,".db_escape($save_report)."
		,".db_escape($date_picker)."
		,".db_escape($destination)."
		,".db_escape($orientation)."
		,".db_escape($inactive)."
		,".db_escape($passdate)."
	)";
	$MessageError = _('Could not insert users');
	db_query($sql, $MessageError);
}

function update_to_user($userid, $name, $role, $phone, $email, $language, $d_format, $date_sep, $tho_sep, $dec_sep, $theme, $page,
$price, $qty, $rates, $percent, $show_g, $show_c, $show_h, $query, $graphics, $pos, $print, $rep, $sticky, $startup, $transaction, 
$save_report, $date_picker, $destination, $orientation, $inactive, $passdate, $targetbranch2)
{
	$db_coy2 = Get_db_coy($targetbranch2);
	set_global_connection($db_coy2);

	$sql = "UPDATE ".TB_PREF."users
			SET real_name = ".db_escape($name)."
				,role_id = ".db_escape($role)."
				,phone = ".db_escape($phone)."
				,email = ".db_escape($email)."
				,language = ".db_escape($language)."
				,date_format = ".db_escape($d_format)."
				,date_sep = ".db_escape($date_sep)."
				,tho_sep = ".db_escape($tho_sep)."
				,dec_sep = ".db_escape($dec_sep)."
				,theme = ".db_escape($theme)."
				,page_size = ".db_escape($page)."
				,prices_dec = ".db_escape($price)."
				,qty_dec = ".db_escape($qty)."
				,rates_dec = ".db_escape($rates)."
				,percent_dec = ".db_escape($percent)."
				,show_gl = ".db_escape($show_g)."
				,show_codes = ".db_escape($show_c)."
				,show_hints = ".db_escape($show_h)."
				,query_size = ".db_escape($query)."
				,graphic_links = ".db_escape($graphics)."
				,pos = ".db_escape($pos)."
				,print_profile = ".db_escape($print)."
				,rep_popup = ".db_escape($rep)."
				,sticky_doc_date = ".db_escape($sticky)."
				,startup_tab = ".db_escape($startup)."
				,transaction_days = ".db_escape($transaction)."
				,save_report_selections = ".db_escape($save_report)."
				,use_date_picker = ".db_escape($date_picker)."
				,def_print_destination = ".db_escape($destination)."
				,def_print_orientation = ".db_escape($orientation)."
				,inactive = ".db_escape($inactive)."
				,passupdate = ".db_escape($passdate)."
			WHERE user_id = ".db_escape($userid);

	db_query($sql, "could not update user");
}
//

//Sales Price/Cash Price
function get_salesprice_to_transfer()
{
	set_global_connection();
	$sql = "SELECT A.*
		FROM ".TB_PREF."sales_types A";

	$result = db_query($sql, "could not get sales price");

    return $result;
}

function check_salesprice($id, $target_branch)
{
	$db_coy = Get_db_coy($target_branch);
	set_global_connection($db_coy);

	$sql = "SELECT A.id
			FROM ".TB_PREF."sales_types A 
			WHERE A.id = ".db_escape($id);

	$result = db_query($sql, "could not get sales price ");

	set_global_connection();
	return $result;
}

function add_to_salesprice($type, $remarks, $tax, $factor, $inactive, $targetbranch1)
{
	$db_coy1 = Get_db_coy($targetbranch1);
	set_global_connection($db_coy1);

	$sql = "INSERT INTO ".TB_PREF."sales_types (
		sales_type 			
		,remarks				
		,tax_included				
		,factor				
		,inactive						
	) VALUES (
		".db_escape($type)."
		,".db_escape($remarks)."
		,".db_escape($tax)."
		,".db_escape($factor)."
		,".db_escape($inactive)."
	)";
	$MessageError = _('Could not insert sales price');
	db_query($sql, $MessageError);
}

function update_to_salesprice($id, $type, $remarks, $tax, $factor, $inactive, $targetbranch2)
{
	$db_coy2 = Get_db_coy($targetbranch2);
	set_global_connection($db_coy2);

	$sql = "UPDATE ".TB_PREF."sales_types
			SET sales_type = ".db_escape($type)."
				,remarks = ".db_escape($remarks)."
				,tax_included = ".db_escape($tax)."
				,factor = ".db_escape($factor)."
				,inactive = ".db_escape($inactive)."
			WHERE id = ".db_escape($id);

	db_query($sql, "could not update sales price");
}

function get_cashprice_to_transfer()
{
	set_global_connection();
	$sql = "SELECT A.*
		FROM ".TB_PREF."sales_cash_type A";

	$result1 = db_query($sql, "could not get cash price");

    return $result1;
}

function check_cashprice($id, $target_branch3)
{
	$db_coy3 = Get_db_coy($target_branch3);
	set_global_connection($db_coy3);

	$sql = "SELECT A.id
			FROM ".TB_PREF."sales_cash_type A 
			WHERE A.id = ".db_escape($id);

	$result = db_query($sql, "could not get cash price ");

	set_global_connection();
	return $result;
}

function add_to_cashprice($type, $tax, $factor, $inactive, $targetbranch4)
{
	$db_coy4 = Get_db_coy($targetbranch4);
	set_global_connection($db_coy4);

	$sql = "INSERT INTO ".TB_PREF."sales_cash_type (
		scash_type 
		,tax_included						
		,factor				
		,inactive									
	) VALUES (
		".db_escape($type)."
		,".db_escape($tax)."
		,".db_escape($factor)."
		,".db_escape($inactive)."
	)";
	$MessageError = _('Could not insert cash price');
	db_query($sql, $MessageError);
}

function update_to_cashprice($id, $type, $tax, $factor, $inactive, $targetbranch5)
{
	$db_coy5 = Get_db_coy($targetbranch5);
	set_global_connection($db_coy5);

	$sql = "UPDATE ".TB_PREF."sales_cash_type
			SET scash_type = ".db_escape($type)."
				,tax_included = ".db_escape($tax)."
				,factor = ".db_escape($factor)."
				,inactive = ".db_escape($inactive)."
			WHERE id = ".db_escape($id);

	db_query($sql, "could not update cash price");
}
//

//Sales Type/Reason & Collection Type
function get_salestype_to_transfer()
{
	set_global_connection();
	$sql = "SELECT A.*
		FROM ".TB_PREF."sales_type A";

	$result = db_query($sql, "could not get sales type");

    return $result;
}

function check_salestype($id, $target_branch)
{
	$db_coy = Get_db_coy($target_branch);
	set_global_connection($db_coy);

	$sql = "SELECT A.id
			FROM ".TB_PREF."sales_type A 
			WHERE A.id = ".db_escape($id);

	$result = db_query($sql, "could not get sales type ");

	set_global_connection();
	return $result;
}

function add_to_salestype($code, $name, $inactive, $targetbranch1)
{
	$db_coy1 = Get_db_coy($targetbranch1);
	set_global_connection($db_coy1);

	$sql = "INSERT INTO ".TB_PREF."sales_type (
		code 			
		,name							
		,inactive						
	) VALUES (
		".db_escape($code)."
		,".db_escape($name)."
		,".db_escape($inactive)."
	)";
	$MessageError = _('Could not insert sales type');
	db_query($sql, $MessageError);
}

function update_to_salestype($id, $code, $name, $inactive, $targetbranch2)
{
	$db_coy2 = Get_db_coy($targetbranch2);
	set_global_connection($db_coy2);

	$sql = "UPDATE ".TB_PREF."sales_type
			SET code = ".db_escape($code)."
				,name = ".db_escape($name)."			
				,inactive = ".db_escape($inactive)."
			WHERE id = ".db_escape($id);

	db_query($sql, "could not update sales type");
}

function get_salesreason_to_transfer()
{
	set_global_connection();
	$sql = "SELECT A.*
		FROM ".TB_PREF."sales_reason A";

	$result1 = db_query($sql, "could not get sales reason");

    return $result1;
}

function check_salesreason($id, $target_branch3)
{
	$db_coy3 = Get_db_coy($target_branch3);
	set_global_connection($db_coy3);

	$sql = "SELECT A.id
			FROM ".TB_PREF."sales_reason A 
			WHERE A.id = ".db_escape($id);

	$result = db_query($sql, "could not get sales reason ");

	set_global_connection();
	return $result;
}

function add_to_salesreason($reason, $inactive, $targetbranch4)
{
	$db_coy4 = Get_db_coy($targetbranch4);
	set_global_connection($db_coy4);

	$sql = "INSERT INTO ".TB_PREF."sales_reason (
		reason 			
		,inActive									
	) VALUES (
		".db_escape($reason)."
		,".db_escape($inactive)."
	)";
	$MessageError = _('Could not insert sales reason');
	db_query($sql, $MessageError);
}

function update_to_salesreason($id, $reason, $inactive, $targetbranch5)
{
	$db_coy5 = Get_db_coy($targetbranch5);
	set_global_connection($db_coy5);

	$sql = "UPDATE ".TB_PREF."sales_reason
			SET reason = ".db_escape($reason)."
				,inActive = ".db_escape($inactive)."
			WHERE id = ".db_escape($id);

	db_query($sql, "could not update sales reason");
}

function get_collectiontype_to_transfer()
{
	set_global_connection();
	$sql = "SELECT A.*
		FROM ".TB_PREF."collection_types A";

	$result2 = db_query($sql, "could not get collection type");

    return $result2;
}

function check_collectiontype($id, $target_branch6)
{
	$db_coy6 = Get_db_coy($target_branch6);
	set_global_connection($db_coy6);

	$sql = "SELECT A.collect_id
			FROM ".TB_PREF."collection_types A 
			WHERE A.collect_id = ".db_escape($id);

	$result = db_query($sql, "could not get collection type ");

	set_global_connection();
	return $result;
}

function add_to_collectiontype($collection, $inactive, $targetbranch7)
{
	$db_coy7 = Get_db_coy($targetbranch7);
	set_global_connection($db_coy7);

	$sql = "INSERT INTO ".TB_PREF."collection_types (
		collection 			
		,inactive									
	) VALUES (
		".db_escape($collection)."
		,".db_escape($inactive)."
	)";
	$MessageError = _('Could not insert collection type');
	db_query($sql, $MessageError);
}

function update_to_collectiontype($id, $collection, $inactive, $targetbranch8)
{
	$db_coy8 = Get_db_coy($targetbranch8);
	set_global_connection($db_coy8);

	$sql = "UPDATE ".TB_PREF."collection_types
			SET collection = ".db_escape($collection)."
				,inactive = ".db_escape($inactive)."
			WHERE collect_id = ".db_escape($id);

	db_query($sql, "could not update collection type");
}
//

//Credit and Sales Incentive Setup
function get_credit_to_transfer()
{
	set_global_connection();
	$sql = "SELECT A.*
		FROM ".TB_PREF."credit_status A";

	$result = db_query($sql, "could not get credit status");

    return $result;
}

function check_credit($id, $target_branch)
{
	$db_coy = Get_db_coy($target_branch);
	set_global_connection($db_coy);

	$sql = "SELECT A.id
			FROM ".TB_PREF."credit_status A 
			WHERE A.id = ".db_escape($id);

	$result = db_query($sql, "could not get credit status ");

	set_global_connection();
	return $result;
}

function add_to_credit($reason, $dissallow, $inactive, $targetbranch1)
{
	$db_coy1 = Get_db_coy($targetbranch1);
	set_global_connection($db_coy1);

	$sql = "INSERT INTO ".TB_PREF."credit_status (
		reason_description 			
		,dissallow_invoices									
		,inactive									
	) VALUES (
		".db_escape($reason)."
		,".db_escape($dissallow)."
		,".db_escape($inactive)."
	)";
	$MessageError = _('Could not insert credit status');
	db_query($sql, $MessageError);
}

function update_to_credit($id, $reason, $dissallow, $inactive, $targetbranch2)
{
	$db_coy2 = Get_db_coy($targetbranch2);
	set_global_connection($db_coy2);

	$sql = "UPDATE ".TB_PREF."credit_status
			SET reason_description = ".db_escape($reason)."
				,dissallow_invoices = ".db_escape($dissallow)."
				,inactive = ".db_escape($inactive)."
			WHERE id = ".db_escape($id);

	db_query($sql, "could not update credit status");
}

function get_salesincentive_to_transfer()
{
	set_global_connection();
	$sql = "SELECT A.*
		FROM ".TB_PREF."sales_incentive_type A";

	$result1 = db_query($sql, "could not get sales incentive setup");

    return $result1;
}

function check_incentive($id, $target_branch3)
{
	$db_coy3 = Get_db_coy($target_branch3);
	set_global_connection($db_coy3);

	$sql = "SELECT A.id
			FROM ".TB_PREF."sales_incentive_type A 
			WHERE A.id = ".db_escape($id);

	$result = db_query($sql, "could not get sales incentive ");

	set_global_connection();
	return $result;
}

function add_to_incentive($descrip, $module, $inactive, $targetbranch4)
{
	$db_coy4 = Get_db_coy($targetbranch4);
	set_global_connection($db_coy4);

	$sql = "INSERT INTO ".TB_PREF."sales_incentive_type (
		description 			
		,module_type									
		,inactive									
	) VALUES (
		".db_escape($descrip)."
		,".db_escape($module)."
		,".db_escape($inactive)."
	)";
	$MessageError = _('Could not insert sales incentive');
	db_query($sql, $MessageError);
}

function update_to_incentive($id, $descrip, $module, $inactive, $targetbranch5)
{
	$db_coy5 = Get_db_coy($targetbranch5);
	set_global_connection($db_coy5);

	$sql = "UPDATE ".TB_PREF."sales_incentive_type
			SET description = ".db_escape($descrip)."
				,module_type = ".db_escape($module)."
				,inactive = ".db_escape($inactive)."
			WHERE id = ".db_escape($id);

	db_query($sql, "could not update sales incentive");
}
//

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
