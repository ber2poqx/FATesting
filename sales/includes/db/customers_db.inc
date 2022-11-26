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

function add_customer($CustName, $cust_ref, $address, $barangay, $municipality, $province, $zip_code = null,
    $tax_id, $age, $gender, $status, $spouse = null, $name_father, $name_mother, $collectors_name = null, $curr_code, $area,
	$dimension_id, $dimension2_id, $credit_status, $payment_terms, $discount, $pymt_discount, 
	$credit_limit, $sales_type, $notes, $interb_ref=0, $employee=0, $employee_id=0)
{
	$sql = "INSERT INTO ".TB_PREF."debtors_master (name, debtor_ref, address, barangay, municipality, province, zip_code, tax_id, age, gender, status, spouse, name_father, name_mother, collectors_name,
		dimension_id, dimension2_id, curr_code, area, credit_status, payment_terms, discount, 
		pymt_discount,credit_limit, sales_type, notes, interb_ref, employee, employee_id) VALUES ("
		.db_escape($CustName) .", " .db_escape($cust_ref) .", "
		.db_escape($address) . ", " .db_escape($barangay) . ", "
		.db_escape($municipality) . ", ".db_escape($province) . ", "
		.db_escape($zip_code, true) . ", " . db_escape($tax_id) . ", "
		.db_escape($age) . ", " .db_escape($gender) . ", "
		.db_escape($status) . ", " .db_escape($spouse, true) . ", "
		.db_escape($name_father) . ", " .db_escape($name_mother) . "," 
		.db_escape($collectors_name, true) . ", " .db_escape($dimension_id) . ", " 
		.db_escape($dimension2_id) . ", " .db_escape($curr_code) . ","
		.db_escape($area) . ", "  . db_escape($credit_status) . ", "
		.db_escape($payment_terms) . ", " . $discount . ", " . $pymt_discount . ", " 
		. $credit_limit .", " .db_escape($sales_type).", " .db_escape($notes).", " 
		.db_escape($interb_ref). ", " .db_escape($employee) .", " .db_escape($employee_id).")";

	db_query($sql,"The customer could not be added");
}

function update_customer($customer_id, $CustName, $cust_ref, $address, $barangay, $municipality, $province,
    $tax_id, $age, $gender, $status, $spouse, $name_father, $name_mother, $curr_code, $area,
	$dimension_id, $dimension2_id, $credit_status, $payment_terms, $discount, $pymt_discount,
	$credit_limit, $sales_type, $notes, $employee, $employee_id)
{
	$sql = "UPDATE ".TB_PREF."debtors_master SET name=" . db_escape($CustName) . ", 
		debtor_ref=" . db_escape($cust_ref) . ",
		address=".db_escape($address) . ", 
		barangay=".db_escape($barangay) . ",
		municipality=".db_escape($municipality) . ",
		province=".db_escape($province) . ",
		tax_id=".db_escape($tax_id) . ", 
		age=".db_escape($age) . ", 
		gender=".db_escape($gender) . ", 
		status=".db_escape($status) . ", 
		spouse=".db_escape($spouse) . ", 
		name_father=".db_escape($name_father) . ", 
		name_mother=".db_escape($name_mother) . ", 
		curr_code=".db_escape($curr_code) . ",
		area=".db_escape($area) . ",  
		dimension_id=".db_escape($dimension_id) . ", 
		dimension2_id=".db_escape($dimension2_id) . ", 
		credit_status=".db_escape($credit_status) . ", 
		payment_terms=".db_escape($payment_terms) . ", 
		discount=" . $discount . ", 
		pymt_discount=" . $pymt_discount . ", 
		credit_limit=" . $credit_limit . ", 
		sales_type = ".db_escape($sales_type) . ", 
		notes=".db_escape($notes) .",
		employee=".db_escape($employee) .",
		employee_id=".db_escape($employee_id) ."
		WHERE debtor_no = ".db_escape($customer_id);

	db_query($sql,"The customer could not be updated");
}

function delete_customer($customer_id)
{
	begin_transaction();
	delete_entity_contacts('customer', $customer_id);

	$sql = "DELETE FROM ".TB_PREF."debtors_master WHERE debtor_no=".db_escape($customer_id);
	db_query($sql,"cannot delete customer");
	commit_transaction();
}

/*
	This function probably should be renamed to get_customer_summary
*/
function get_customer_details($customer_id, $to=null, $all=true)
{

	if ($to == null)
		$todate = date("Y-m-d");
	else
		$todate = date2sql($to);
	$past1 = get_company_pref('past_due_days');
	$past2 = 2 * $past1;
	// removed - debtor_trans.alloc from all summations
	$sign = "IF(`type` IN(".implode(',',  array(ST_CUSTCREDIT,ST_CUSTPAYMENT,ST_BANKDEPOSIT))."), -1, 1)";
	if ($all)
    	$value = "IFNULL($sign*(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount),0)";
    else		
    	$value = "IFNULL($sign*(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount - 
    		trans.alloc),0)";
	$due = "IF (trans.type=".ST_SALESINVOICE.", trans.due_date, trans.tran_date)";
    $sql = "SELECT debtor.name, debtor.curr_code, terms.terms, debtor.credit_limit,
    			credit_status.dissallow_invoices, credit_status.reason_description,
				Sum($value) AS Balance,
				Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) > 0,$value,0)) AS Due,
				Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) > $past1,$value,0)) AS Overdue1,
				Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) > $past2,$value,0)) AS Overdue2
			FROM ".TB_PREF."debtors_master debtor
				 LEFT JOIN ".TB_PREF."debtor_trans trans ON trans.tran_date <= '$todate' AND debtor.debtor_no = trans.debtor_no AND trans.type <> ".ST_CUSTDELIVERY.","
				 .TB_PREF."payment_terms terms,"
				 .TB_PREF."credit_status credit_status
			WHERE
					debtor.payment_terms = terms.terms_indicator
	 			AND debtor.credit_status = credit_status.id";
	 if ($customer_id)
		$sql .= " AND debtor.debtor_no = ".db_escape($customer_id);

	if (!$all)
		$sql .= " AND ABS(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount - trans.alloc) > ".FLOAT_COMP_DELTA;
	$sql .= " GROUP BY
			  	debtor.name,
			  	terms.terms,
			  	terms.days_before_due,
			  	terms.day_in_following_month,
			  	debtor.credit_limit,
			  	credit_status.dissallow_invoices,
			  	credit_status.reason_description";
    $result = db_query($sql,"The customer details could not be retrieved");

    $customer_record = db_fetch($result);

    return $customer_record;

}

//Modified by spyrax10 22 Apr 2022
function get_customer($customer_id = '', $customer_ref = '') {

	set_global_connection();

	$sql = "SELECT * FROM ".TB_PREF."debtors_master WHERE inactive = 0";
	
	if ($customer_id != '') {
		$sql .=	" AND debtor_no = ".db_escape($customer_id);
	}

	if ($customer_ref != '') {
		$sql .=	" AND debtor_ref = ".db_escape($customer_ref);
	}

	$result = db_query($sql, "could not get customer");

	return db_fetch($result);
}

function get_customer_name($customer_id)
{
	$sql = "SELECT name FROM ".TB_PREF."debtors_master WHERE debtor_no=".db_escape($customer_id);

	$result = db_query($sql, "could not get customer");

	$row = db_fetch_row($result);

	return $row[0];
}

function get_customer_habit($customer_id)
{
	$sql = "SELECT debtor.pymt_discount, credit_status.dissallow_invoices
			FROM ".TB_PREF."debtors_master debtor,"
				.TB_PREF."credit_status credit_status
			WHERE debtor.credit_status = credit_status.id
			AND debtor.debtor_no = ".db_escape($customer_id);

	$result = db_query($sql, "could not query customers");

	return db_fetch($result);
}

function get_customer_contacts($customer_id, $action=null)
{
	$results = array();
	$res = get_crm_persons('customer', $action, $customer_id);
	while($contact = db_fetch($res))
	{
		if ($contact['lang'] == 'C') // Fix for improper lang in demo sql files.
			$contact['lang'] = '';
		$results[] = $contact;
	}	
	return $results;
}

function get_current_cust_credit($customer_id)
{
	$custdet = get_customer_details($customer_id);

	return $custdet['credit_limit']-$custdet['Balance'];

}

function is_new_customer($id)
{
	$tables = array('cust_branch', 'debtor_trans', 'recurrent_invoices', 'sales_orders');

	return !key_in_foreign_table($id, $tables, 'debtor_no');
}

function get_customer_by_ref($reference)
{
	$sql = "SELECT * FROM ".TB_PREF."debtors_master WHERE debtor_ref=".db_escape($reference);

	$result = db_query($sql, "could not get customer");

	return db_fetch($result);
}

//--ADDED BY ROBERT---//
function check_customer_already_exist($CustName)
{
	$sql = "SELECT COUNT(*) FROM ".TB_PREF."debtors_master A
			WHERE A.name = ".db_escape($CustName);
	$result = db_query($sql, "check customer failed");
	$count =  db_fetch($result);

	return $count[0];
}

function get_employee_detail($employee_id)
{
    $sql = "SELECT user_id, real_name, email FROM users
        WHERE user_id = ".db_escape($employee_id);
   return db_query($sql, "could not query users");
}
//----------------//

//----------------------------------------------------------------------------------

function get_customer_currency($customer_id=null, $branch_id=null)
{
    $sql = "SELECT curr_code
    	FROM ".TB_PREF."debtors_master cust
    		LEFT JOIN ".TB_PREF."cust_branch branch ON branch.debtor_no=cust.debtor_no
    	WHERE " .(isset($branch_id) ? "branch_code = ".db_escape($branch_id) : "cust.debtor_no = ".db_escape($customer_id));

	$result = db_query($sql, "Retreive currency of customer $customer_id");

	$myrow=db_fetch_row($result);
	return $myrow[0];
}

function get_customers_search($customer)
{
	global $SysPrefs;

	if (isset($SysPrefs->max_rows_in_search))
		$limit = $SysPrefs->max_rows_in_search;
	else
		$limit = 10;

	$sql = "SELECT A.debtor_no, A.name, A.debtor_ref, A.address, A.tax_id, B.tax_group_id, C.description, C.collectors_id, D.phone,
    E.real_name, F.name AS salestype, G.name AS taxgroup
	FROM ".TB_PREF."debtors_master A
	LEFT JOIN cust_branch B ON B.debtor_no=A.debtor_no
	LEFT JOIN areas C ON C.area_code=A.area
	LEFT JOIN crm_persons D ON D.ref=A.debtor_ref
	LEFT JOIN users E ON E.user_id=C.collectors_id
	LEFT JOIN sales_type F ON F.id=A.sales_type
	lEFT JOIN tax_groups G ON G.id=B.tax_group_id
	  WHERE (  A.name LIKE " . db_escape("%" . $customer. "%") . " OR 
    		 A.debtor_ref LIKE " . db_escape("%" . $customer. "%") . " OR 
	        A.address LIKE " . db_escape("%" . $customer. "%") . " OR 
    	     A.tax_id LIKE " . db_escape("%" . $customer. "%").")
	  ORDER BY A.name LIMIT 0,".(int)($limit);

	return db_query($sql, "Failed in retreiving customer list.");
}

function get_Customer_AutoGenerated_Code()
{
	//modify by zhun to make function
	//MOdified by Robert Dusal

    global $db_connections;
	$brchcode = $db_connections[user_company()]["branch_code"];

	$sql = "SELECT LPAD(COALESCE(MAX(debtor_no) + 1, 1), 6, 0) FROM debtors_master";
	$result = db_query($sql, "could not get customer code");
    $row = db_fetch_row($result);
	return $brchcode.''.$row[0];
}

//-------------:: added by jr ::---------------
function check_customer_exist($customer_name)
{
	$sql = "SELECT COUNT(*) FROM ".TB_PREF."debtors_master A 
			WHERE A.name = ".db_escape($customer_name);
	$result = db_query($sql, "check customer failed");
	$count =  db_fetch($result);

	return $count[0];
}

function Get_CustomerFromOtherDB($debtor_ref, $branch_code)
{
	$db_coy = Get_db_coy($branch_code);
    set_global_connection($db_coy);
	
	$sql = "SELECT A.*, B.salesman, B.tax_group_id, B.default_ship_via, B.bank_account, C.phone, C.phone2, C.fax, C.email, C.facebook 
			FROM ".TB_PREF."debtors_master A 
				LEFT JOIN ".TB_PREF."cust_branch B ON B.debtor_no = A.debtor_no 
				LEFT JOIN ".TB_PREF."crm_persons C ON C.ref = A.debtor_ref 
			WHERE A.debtor_ref = ".db_escape($debtor_ref);

	$result = db_query($sql, "The customer from other database could not be retrieved");

	set_global_connection();
	return $result;
}

function search_CustomerFromOtherDB($customer, $branch_code)
{
	$db_coy = Get_db_coy($branch_code);
    set_global_connection($db_coy);
	
	$sql = "SELECT A.*, B.salesman, B.tax_group_id, B.default_ship_via, B.bank_account, C.phone, C.phone2, C.fax, C.email, D.municipality munic, D.zipcode
			FROM ".TB_PREF."debtors_master A 
				LEFT JOIN ".TB_PREF."cust_branch B ON B.debtor_no = A.debtor_no 
				LEFT JOIN ".TB_PREF."crm_persons C ON C.ref = A.debtor_ref 
				LEFT JOIN ".TB_PREF."municipality_zipcode D ON D.muni_code = A.municipality 
			WHERE A.name LIKE ".db_escape("%" . $customer. "%");

	$result = db_query($sql, "The customer from other database could not be retrieved");

	set_global_connection();
	return $result;
}

function get_debtor_interb_ref($debtor_no)
{
	$sql = "SELECT A.interb_ref 
		FROM ".TB_PREF."debtors_master A 
		WHERE A.debtor_no = ".db_escape($debtor_no);

	$result = db_query($sql, "could not get debtors table records ");
	$ret = db_fetch($result);

	return $ret[0];
}

function get_areas()
{
	$sql = "SELECT area_code, description, collectors_id 
			FROM ".TB_PREF."areas A
			WHERE !A.inactive";

	return db_query($sql, "could not get areas table records");
}

function get_Collector_by_area($user_id)
{
	$sql = "SELECT A.user_id, A.real_name 
			FROM ".TB_PREF."users A
			WHERE A.role_id = '11'
				AND A.user_id =".db_escape($user_id);

	return db_query($sql, "could not get user table records");
}

function get_all_customer($debtorno=null)
{
	$sql = "SELECT A.debtor_no, A.name, A.debtor_ref, A.address, A.tax_id, B.tax_group_id, C.description, D.phone,
    			E.real_name, F.name AS salestype, G.name AS taxgroup
		FROM ".TB_PREF."debtors_master A
			LEFT JOIN ".TB_PREF."cust_branch B ON B.debtor_no=A.debtor_no
			LEFT JOIN ".TB_PREF."areas C ON C.area_code=A.area
			LEFT JOIN ".TB_PREF."crm_persons D ON D.ref=A.debtor_ref
			LEFT JOIN ".TB_PREF."users E ON E.user_id=A.collectors_name
			LEFT JOIN ".TB_PREF."sales_type F ON F.id=A.sales_type
			lEFT JOIN ".TB_PREF."tax_groups G ON G.id=B.tax_group_id ";
		if($debtorno != null || $debtorno != '' || $debtorno != 0){
			$sql .= " WHERE  A.debtor_no =".db_escape($debtorno);
		}
		$sql .= " ORDER BY A.name ";

	return db_query($sql, "could not get all customers");
}

function get_debtor_per_customer($debtor_ref, $branch_code)
{
	$db_coy = Get_db_coy($branch_code);
    set_global_connection($db_coy);

	$sql = "SELECT A.trans_no, A.type
		FROM ".TB_PREF."debtor_trans A
			INNER JOIN ".TB_PREF."debtors_master B ON B.debtor_no = A.debtor_no
		WHERE (A.type = ".ST_SALESINVOICE." OR A.type =".ST_ARINVCINSTLITM.")
			AND (A.status != 'fully-paid' AND A.status != 'close')
			AND A.payment_terms != 0
			AND B.debtor_ref =".db_escape($debtor_ref)."
		ORDER BY A.trans_no DESC ";

	$result = db_query($sql, "could not get all customer loans");
	return db_fetch($result);
}

//Added by spyrax10 5 Feb 2022

function get_customer_branch_details($customer_id = '') {

	set_global_connection();
	
	$sql = "SELECT * 
		FROM ".TB_PREF."cust_branch 
		WHERE debtor_no = ".db_escape($customer_id);

	$result = db_query($sql, "Cant get customer branch details!");

	return db_fetch($result);
}
