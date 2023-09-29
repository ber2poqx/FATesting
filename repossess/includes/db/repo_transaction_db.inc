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
function add_repo_accounts($trans_type, $trans_no, $ar_trans_type, $trans_date_, $repo_date_, $repo_type, $reference_no, $debtor_no, $lcp, $downpayment, $out_ar, 
							$monthly, $term, $release_date_, $firstdue_date_, $maturity_date_, $balance, $spot_cash, $total_amount, $unrecovered,
							$addon, $total_unrecovered, $over_due=0, $past_due=0, $category_id, $branch_code, $comments, $gpm, $transfer_id=0, $accu_amount=0)
{
	global $Refs;
	
	$trans_date = date2sql($trans_date_);
	$repo_date = date2sql($repo_date_);
	$release_date = date2sql($release_date_);
	$firstdue_date = date2sql($firstdue_date_);
	$maturity_date = date2sql($maturity_date_);

	$sql = "INSERT INTO ".TB_PREF."repo_accounts (
					type, ar_trans_no, ar_trans_type, trans_date, repo_date, repo_type, reference_no, debtor_no, lcp_amount, downpayment, outstanding_ar,
					monthly_amount, term, release_date, firstdue_date, maturity_date, balance, spot_cash_amount, total_amount, unrecovered_cost,
					addon_amount, total_unrecovered, over_due, past_due, category_id, branch_code, comments, gpm, transfer_id, accu_amount
			) VALUES (
					".db_escape($trans_type).", ".db_escape($trans_no).", ".db_escape($ar_trans_type).", ".db_escape($trans_date).", ".db_escape($repo_date).", ".db_escape($repo_type).",
					".db_escape($reference_no).", ".db_escape($debtor_no).", ".db_escape($lcp).", ".db_escape($downpayment).", ".db_escape($out_ar).",
					".db_escape($monthly).", ".db_escape($term).", ".db_escape($release_date).", ".db_escape($firstdue_date).", ".db_escape($maturity_date).",
					".db_escape($balance).", ".db_escape($spot_cash).", ".db_escape($total_amount).", ".db_escape($unrecovered).", ".db_escape($addon).",
					".db_escape($total_unrecovered).", ".db_escape($over_due).", ".db_escape($past_due).", ".db_escape($category_id).", ".db_escape($branch_code).",
					".db_escape($comments).", ".db_escape($gpm).", ".db_escape($transfer_id).", ".db_escape($accu_amount)."
			)";

	db_query($sql, "The rr repo account record cannot be inserted");
	$repo_id = db_insert_id();

	add_audit_trail(ST_RRREPO, $repo_id, $trans_date_);
	$Refs->save(ST_RRREPO, $repo_id, $reference_no);

	return $repo_id;
}

function add_repo_item($trans_no, $repo_id, $stock_id, $description, $qty, $unit_price, $unrecovrd_cost, $serial_no, $chassis_no, $color_code)
{
	$sql = "INSERT INTO ".TB_PREF."repo_item_details (
					repo_id, ar_trans_no, stock_id, description, qty, unit_price, unrecovrd_cost, serial_no, chassis_no, color_code
			) VALUES (
					".db_escape($repo_id).", ".db_escape($trans_no).", ".db_escape($stock_id).", ".db_escape($description).", ".db_escape($qty).",
					".db_escape($unit_price).", ".db_escape($unrecovrd_cost).", ".db_escape($serial_no).", ".db_escape($chassis_no).", ".db_escape($color_code)."
			)";

	db_query($sql, "The rr repo account item record cannot be inserted");
	$repo_item_id = db_insert_id();

	return $repo_item_id;
}

function update_repo_reprice_stock_move($amount, $trans_id, $trans_no, $stock_id, $def_loc)
{
	$db_coy = Get_db_coy($def_loc);
    set_global_connection($db_coy);

	$sql = "UPDATE ".TB_PREF."stock_moves 
			SET price = ".db_escape($amount)."
		WHERE type = 80
			AND trans_id = ".db_escape($trans_id)."
			AND trans_no = ".db_escape($trans_no)."
			AND stock_id = ".db_escape($stock_id);
	
	db_query($sql, "could not update stock move price because ");
	set_global_connection();
}

function get_repo_accounts($start, $limit, $search, $showall=false)
{
	if($search != ''){
		$sWhere .= " WHERE (C.name LIKE '%".$search."%' OR A.reference_no LIKE '%".$search."%' OR A.repo_date LIKE '%".$search."%' OR B.description LIKE '%".$search."%') ";
	}
	$sql = "SELECT A.*, B.description cat_desc, C.name, C.debtor_ref
		FROM ".TB_PREF."repo_accounts A
			INNER JOIN ".TB_PREF."stock_category B ON B.category_id  = A.category_id
			LEFT JOIN ".TB_PREF."debtors_master C ON C.debtor_no = A.debtor_no 
		".$sWhere."
		ORDER BY A.trans_date DESC";

		$sqllimit = $sql ." limit $start, $limit";

		if($showall){
			$result = db_query($sql, "could not get all customer repo accounts");
		}else{
			$result = db_query($sqllimit, "could not get all customer repo accounts");
		}

	return $result;
}

function get_repo_accounts_item_details($repo_id)
{
	$sql = "SELECT A.* 
			FROM ".TB_PREF."repo_item_details A 
			WHERE A.id = ".db_escape($repo_id);

	return db_query($sql, "could not get all repo item details");
}

//to temporary repo accounts.

function update_debtor_trans_to_repo($module_type, $repo_date, $trans_no, $trans_type)
{
	$date = date2sql($repo_date);

	$sql = "UPDATE ".TB_PREF."debtor_trans 
			SET module_type = ".db_escape($module_type)."
				,repo_date = ".db_escape($date);

			if($module_type == "REPO"){
				$sql .= " ,status = 'Close' ";
			}
			
			$sql .= " WHERE trans_no = ".db_escape($trans_no)."
							AND type =".db_escape($trans_type);
	
	db_query($sql, "could not add a/r account to repo");			
}

function update_un_temprepo($trans_no, $trans_type, $reference)
{
	$sql = "UPDATE ".TB_PREF."debtor_trans 
			SET module_type = 'NULL', repo_date = '0000-00-00'
			WHERE trans_no = ".db_escape($trans_no)."
				AND type = ".db_escape($trans_type)."
				AND reference = ".db_escape($reference);
	
	db_query($sql, "could not remove to a/r account temp repo");	
}

function get_customer_account_repo($type)
{
	if($type == 'new' || $type == 'openar'){
		$criteria = ST_SALESINVOICE; //10
	}else if($type == 'repo'){
		$criteria = ST_SALESINVOICEREPO; //57
	}else if($type == 'replcmnt'){
		$criteria = ST_SALESRETURN; //55
	}else if($type == 'mt'){
		$criteria = ST_MERCHANDISETRANSFERREPO; //59
	}else if($type == 'trmode'){
		$criteria = ST_SITERMMOD; //56
	}else if($type == 'arlend'){
		$criteria = ST_ARINVCINSTLITM; //70
	}
	$swhere = " AND COALESCE(A.module_type, 0) = 'TEMP-REPO' ";

	if($type == 'openar'){
		$swhere = " AND A.opening_balances = 1 ";
	}

	$sql = "SELECT B.debtor_no, B.debtor_ref, B.name
			FROM ".TB_PREF."debtor_trans A
				INNER JOIN ".TB_PREF."debtors_master B ON B.debtor_no = A.debtor_no
			WHERE A.payment_terms != 0
				AND (A.type = ".$criteria.") "
				.$swhere ;
	
	return db_query($sql, "could not get customer ");
}

function get_customer_from_replacement()
{
	$sql = "SELECT B.debtor_no, C.debtor_ref, C.name
			FROM ".TB_PREF."returned_units A
				INNER JOIN ".TB_PREF."debtor_trans B ON B.trans_no = A.si_trans_no
				INNER JOIN ".TB_PREF."debtors_master C ON C.debtor_no = B.debtor_no
			WHERE B.module_type = 'TEMP-REPO' ";
	
	return db_query($sql, "could not get customer ");
}

function get_invoice_to_repo($debtor_no, $view=false)
{
	if($view == false ){
		$sWhere = " AND COALESCE(A.module_type, 0) != 'TEMP-REPO' ";
	}
	$sql = "SELECT A.trans_no, A.reference, A.type, A.tran_date, A.status, B.stock_id, B.description itemdesc, D.description category
			FROM ".TB_PREF."debtor_trans A
            	INNER JOIN ".TB_PREF."debtor_trans_details B ON B.debtor_trans_no = A.trans_no AND B.debtor_trans_type = A.type
                INNER JOIN ".TB_PREF."debtor_loans C ON C.trans_no = A.trans_no AND C.debtor_no = A.debtor_no
                INNER JOIN ".TB_PREF."stock_category D ON D.category_id = C.category_id
			WHERE (A.type = ".ST_SALESINVOICE." OR A.type =".ST_ARINVCINSTLITM." OR A.type =".ST_SALESINVOICEREPO.")
				AND (A.status != 'fully-paid' AND A.status != 'closed')
				". $sWhere ."
				AND A.debtor_no = ".db_escape($debtor_no)."
				AND C.months_term != '0'
			ORDER BY A.tran_date ASC";

	return db_query($sql, "could not get all customer invoice");
}

function get_invtermode_to_repo($debtor_no, $view=false)
{
	if($view == false ){
		$sWhere = " AND COALESCE(A.module_type, 0) != 'TEMP-REPO' ";
	}
	$sql = "SELECT A.trans_no, A.reference, A.type, A.tran_date, A.status, stock_id, itemdesc, B.*, category, ITEM.trans_no invoice_no, ITEM.debtor_trans_type
			FROM ".TB_PREF."debtor_trans A 
				INNER JOIN ".TB_PREF."debtor_term_modification B ON B.trans_no = A.trans_no AND B.debtor_no = A.debtor_no
				LEFT JOIN ( 
					SELECT Z.debtor_trans_no, Z.debtor_trans_type, Y.trans_no, Y.debtor_no, X.stock_id, X.description itemdesc, W.category_id, W.description category, Y.invoice_ref_no 
               		FROM ".TB_PREF."debtor_trans_details Z
               			INNER JOIN ".TB_PREF."debtor_loans Y ON Y.trans_no = Z.debtor_trans_no 
               			INNER JOIN ".TB_PREF."stock_master X ON X.stock_id = Z.stock_id 
                		INNER JOIN ".TB_PREF."stock_category W ON W.category_id = X.category_id
               		WHERE (Z.debtor_trans_type = ".ST_SALESINVOICE." OR Z.debtor_trans_type =".ST_ARINVCINSTLITM.")
      			) ITEM ON ITEM.invoice_ref_no = B.invoice_ref_no AND ITEM.debtor_no = B.debtor_no
			WHERE A.type = ".ST_SITERMMOD." 
				AND (A.status != 'fully-paid' AND A.status != 'closed')
				". $sWhere ."
				AND A.debtor_no = ".db_escape($debtor_no)."
				AND B.months_term != '0'
			ORDER BY A.tran_date ASC";
	//echo $sql;
	return db_query($sql, "could not get all customer invoice");
}

function get_item_detials_to_repo($trans_no, $trans_type)
{
	$sql = "SELECT A.*
		FROM ".TB_PREF."debtor_trans_details A 
		WHERE A.debtor_trans_no = ".db_escape($trans_no)."
			AND A.debtor_trans_type = ".db_escape($trans_type);

	return db_query($sql, "could not get sales invoice item details");	
}

function get_ar_account_temprepo($start, $limit, $search)
{
	if($search != ''){
		$sWhere .= " AND (B.name LIKE '%".$search."%' OR A.reference LIKE '%".$search."%') ";
	}

	$sql = "SELECT DATEDIFF(CURRENT_DATE(), A.repo_date) AS COUNTDOWN , A.trans_no, A.reference, A.module_type, A.repo_date, A.type,
				B.debtor_no, B.name, B.debtor_ref, D.category_id, D.description Catgry_desc, E.stock_id, E.description item_desc
			FROM ".TB_PREF."debtor_trans A
				LEFT JOIN ".TB_PREF."debtors_master B ON B.debtor_no = A.debtor_no
				LEFT JOIN ".TB_PREF."debtor_loans C ON C.trans_no = A.trans_no AND C.debtor_no = A.debtor_no
				LEFT JOIN ".TB_PREF."stock_category D ON D.category_id = C.category_id
				LEFT JOIN ".TB_PREF."debtor_trans_details E ON E.debtor_trans_no = A.trans_no  AND E.debtor_trans_type = A.type
			WHERE A.module_type = 'TEMP-REPO'
				AND (A.type = ".ST_SALESINVOICE." OR A.type =".ST_ARINVCINSTLITM." OR A.type = ".ST_SITERMMOD." OR A.type = ".ST_SALESRETURN." OR A.type =".ST_SALESINVOICEREPO.") "
			.$sWhere."
			ORDER BY A.repo_date DESC";
	
	return db_query($sql, "could not get temporary repo accounts");	 
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

function get_invoice_per_customer_repo($debtor_no, $rtype)
{
	$swhere = " AND COALESCE(A.module_type, 0) = 'TEMP-REPO' ";
	
	if($rtype == 'openar'){
		$swhere = " AND A.opening_balances = 1 ";
	}

	$sql = "SELECT A.*, C.*, B.stock_id, B.description itemdesc, D.description category
			FROM ".TB_PREF."debtor_trans A
            	INNER JOIN ".TB_PREF."debtor_trans_details B ON B.debtor_trans_no = A.trans_no AND B.debtor_trans_type = A.type
                INNER JOIN ".TB_PREF."debtor_loans C ON C.trans_no = A.trans_no AND C.debtor_no = A.debtor_no
                INNER JOIN ".TB_PREF."stock_category D ON D.category_id = C.category_id
			WHERE (A.type = ".ST_SALESINVOICE." OR A.type =".ST_ARINVCINSTLITM." OR A.type =".ST_SALESINVOICEREPO.")
				AND (A.status != 'fully-paid' AND A.status != 'close')
				AND A.payment_terms != 0
				AND A.debtor_no = ".db_escape($debtor_no)
				. $swhere .
			" ORDER BY A.tran_date ASC";

	return db_query($sql, "could not get all customer invoice");
}

function get_payment_applied($type, $trans_no)
{
	$sql = "SELECT SUM(A.payment_applied) payApplied
		FROM ".TB_PREF."debtor_loan_ledger A 
		WHERE A.trans_type_to = ".db_escape($type). "
			AND A.trans_no = ".db_escape($trans_no);
			
	$result = db_query($sql, "could not get payment applied");
	$row = db_fetch($result);
	return $row[0];
}

function get_cost_Sales($type, $trans_no, $debtor_no)
{
	$company_record = get_company_prefs();
	//'501001' cost of sales
	//$typechild = get_sales_child_documents(ST_SALESORDER, $trans_no);
	//$type_row = db_fetch($typechild);
	//$delivery_no = get_trans_delivery($trans_no, ST_CUSTDELIVERY);
	
	/*$sql = "SELECT A.amount
		FROM ".TB_PREF."gl_trans A
			INNER JOIN ".TB_PREF."debtor_trans B on B.trans_no = A.type_no
		WHERE A.account = ".db_escape($company_record["default_cogs_act"]). "
			AND B.type = ".db_escape($type). "
			AND B.trans_no = ".db_escape($trans_no);
		*/

		$sql = "SELECT A.amount
		FROM ".TB_PREF."gl_trans A
		WHERE A.account = ".db_escape($company_record["default_cogs_act"]). "
			AND A.type = ".db_escape(ST_CUSTDELIVERY). "
			AND A.type_no = ".db_escape(get_trans_delivery($trans_no, $type, $debtor_no));
			//echo $sql . ' - '. $trans_no;
	$result = db_query($sql, "could not get cost of sales amount");
	$row = db_fetch($result);
	return $row[0];
}

function get_default_location()
{
	$sql = "SELECT pos_location FROM ".TB_PREF."sales_pos WHERE id=1";
	
	$result = db_query($sql, "could not get POS location");
	
	$row = db_fetch_row($result);
	return $row[0];
}

function get_repo_invty_act($category_id)
{
	$sql="SELECT dflt_repo_invty_act FROM ".TB_PREF."stock_category WHERE category_id=".db_escape($category_id);

	$result = db_query($sql,"an item category could not be retrieved");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_repo_stocks($category_id, $def_loc, $start, $limit, $search, $showall=false)
{
	$db_coy = Get_db_coy($def_loc);
    set_global_connection($db_coy);

	if($search != ''){
		$sWhere .= " AND (A.stock_id LIKE '%".$search."%' OR B.description LIKE '%".$search."%' OR C.reference_no LIKE '%".$search."%' OR D.name LIKE '%".$search."%') ";
	}
	$sql = "SELECT A.trans_id, A.trans_no, A.type, A.stock_id, A.price, B.description, C.id, C.unrecovered_cost, C.repo_date, C.reference_no, C.comments, D.debtor_no, D.name, E.description category
		FROM ".TB_PREF."stock_moves A
			INNER JOIN ".TB_PREF."stock_master B ON B.stock_id = A.stock_id
			INNER JOIN ".TB_PREF."repo_accounts C ON C.id = A.trans_no
			INNER JOIN ".TB_PREF."debtors_master D ON D.debtor_no = C.debtor_no
			INNER JOIN ".TB_PREF."stock_category E ON E.category_id = C.category_id
		WHERE A.type = 80
			AND E.category_id = ".db_escape($category_id). "
			".$sWhere."
		ORDER BY A.tran_date ASC ";
		
	$sqllimit = $sql ." limit $start, $limit";

	if($showall){
		$result = db_query($sql, "could not get repo item");
	}else{
		$result = db_query($sqllimit, "could not get repo item");
	}

	set_global_connection();
	return $result;
}

function get_repo_item_detials($trans_no, $def_loc)
{
	$db_coy = Get_db_coy($def_loc);
    set_global_connection($db_coy);
	
	$sql = "SELECT A.*
		FROM ".TB_PREF."repo_item_details A 
		WHERE A.repo_id = ".db_escape($trans_no);
	
	$result = db_query($sql, "could not get repo item details");
	set_global_connection();
	return $result;
}

function get_total_payment_applied($type, $trans_no)
{
	$sql = "SELECT SUM(A.payment_applied) payApplied
		FROM ".TB_PREF."debtor_loan_ledger A 
		WHERE A.trans_type_to = ".db_escape($type). "
			AND A.trans_no = ".db_escape($trans_no);

	$result = db_query($sql, "could not get total payment applied");
	$row = db_fetch($result);
	return $row[0];
}

function get_sched_loans_due($type, $trans_no, $repo_date)
{
	$sql = "SELECT SUM(A.principal_due) due_amount
		FROM ".TB_PREF."debtor_loan_schedule A 
		WHERE A.trans_type = ".db_escape($type). "
			AND A.trans_no = ".db_escape($trans_no). "
			AND A.date_due <= '". $repo_date ."'";

	$result = db_query($sql, "could not get total payment due");
	$row = db_fetch($result);
	return $row[0];
}

function get_termode_details($debtor_no, $trans_no)
{
	$sql = "SELECT  A.*
				FROM ".TB_PREF."debtor_term_modification A
				WHERE A.debtor_no = ".db_escape($debtor_no). "
					AND A.trans_no = ".db_escape($trans_no);
				
	$result = db_query($sql, "could not get term mode details");
	return db_fetch($result);
}

function get_item_termmode($debtor_no, $trans_no, $type)
{
	$sql = "SELECT Z.debtor_trans_type, Y.trans_no, Y.debtor_no, X.stock_id, X.description itemdesc, W.category_id, W.description category, Y.invoice_ref_no 
               		FROM ".TB_PREF."debtor_trans_details Z
               			INNER JOIN ".TB_PREF."debtor_loans Y ON Y.trans_no = Z.debtor_trans_no 
               			INNER JOIN ".TB_PREF."stock_master X ON X.stock_id = Z.stock_id 
                		INNER JOIN ".TB_PREF."stock_category W ON W.category_id = X.category_id
               		WHERE (Z.debtor_trans_type = ".ST_SALESINVOICE." OR Z.debtor_trans_type =".ST_ARINVCINSTLITM.")
						AND Y.debtor_no = ".db_escape($debtor_no). "
						AND Y.trans_no = ".db_escape($trans_no);
					
	$result = db_query($sql, "could not get total payment due");
	return db_fetch($result);
}

function get_unitcost($inv_reference)
{
	$sql = "SELECT A.total_amount
				FROM ".TB_PREF."debtor_loans A 
				WHERE A.invoice_ref_no = ".db_escape($inv_reference);

	$result = db_query($sql, "could not get unit cost");
	$row = db_fetch($result);
	return $row[0];
}

function get_replace_item_loans($debtor_no)
{
	$sql = "SELECT B.*, D.stock_id, D.description itemdesc, E.description category, A.from_debtor_trans_type type, B.invoice_date tran_date
			FROM ".TB_PREF."returned_units A
				INNER JOIN ".TB_PREF."debtor_loans B ON B.trans_no = A.si_trans_no
				INNER JOIN ".TB_PREF."debtors_master C ON C.debtor_no = B.debtor_no
				INNER JOIN ".TB_PREF."debtor_trans_details D ON D.debtor_trans_no = A.debtor_trans_no AND D.debtor_trans_type = A.debtor_trans_type
				INNER JOIN ".TB_PREF."stock_category E ON E.category_id = B.category_id
			WHERE A.debtor_trans_details_id != D.id
				AND B.debtor_no = ".db_escape($debtor_no);
			
	return db_query($sql, "could not get debtor loans info in replace item because ");
}

function get_replace_item_to_repo($trans_no)
{
	$sql = "SELECT B.id, B.stock_id, B.description, B.color_code, B.quantity, B.unit_price, B.standard_cost, B.lot_no, B.chassis_no
			FROM ".TB_PREF."returned_units A 
				INNER JOIN ".TB_PREF."debtor_trans_details B ON B.debtor_trans_no = A.debtor_trans_no AND B.debtor_trans_type = A.debtor_trans_type
			WHERE A.si_trans_no = ".db_escape($trans_no)."
				AND A.debtor_trans_details_id != B.id
			ORDER BY B.id DESC";

	return db_query($sql, "could not get a/r ledger entries because ");
}

function get_mt_per_branch($home_branch, $from_branch)
{
    $db_coy = Get_db_coy($from_branch);
    set_global_connection($db_coy);
	
	$sql = "SELECT A.mt_header_id, A.mt_header_reference, B.mt_details_stock_id, C.description, D.description category, A.mt_header_repo_account_id
			FROM ".TB_PREF."mt_header A 
				INNER JOIN ".TB_PREF."mt_details B ON B.mt_details_header_id = A.mt_header_id
    			INNER JOIN ".TB_PREF."stock_master C ON C.stock_id = B.mt_details_stock_id
				INNER JOIN ".TB_PREF."stock_category D ON D.category_id = A.mt_header_category_id
			WHERE A.mt_header_tolocation = ".db_escape($home_branch)."
				AND A.mt_header_fromlocation = ".db_escape($from_branch)."
				AND A.mt_header_item_type = 'repo'
				AND B.mt_details_status = 0
			ORDER BY A.mt_header_reference ASC";

	$result = db_query($sql, "could not get repo merchandise transfer item because");

	set_global_connection();
	
	return $result;
}

function get_mt_customer($repo_id, $from_branch)
{
	$db_coy = Get_db_coy($from_branch);
    set_global_connection($db_coy);

	$sql = "SELECT B.debtor_no, B.name
			FROM ".TB_PREF."repo_accounts A
				INNER JOIN ".TB_PREF."debtors_master B ON B.debtor_no = A.debtor_no
			WHERE A.id = ".db_escape($repo_id);

	$result = db_query($sql, "could not get customer info from ".$from_branch. " because");
	$row = db_fetch($result);
		
	set_global_connection();

	$sql = "SELECT B.*
			FROM ".TB_PREF."debtors_master B 
			WHERE B.name LIKE ".db_escape('%'. $row[1] .'%');

	$result = db_query($sql, "could not get customer because");
	return $result;
}

function get_mt_repo_account($repo_id, $from_branch)
{
	$db_coy = Get_db_coy($from_branch);
    set_global_connection($db_coy);

	$sql = "SELECT A.*, B.*, C.description category
			FROM ".TB_PREF."repo_accounts A
				INNER JOIN ".TB_PREF."repo_item_details B ON B.repo_id = A.id
				INNER JOIN ".TB_PREF."stock_category C ON C.category_id = A.category_id
			WHERE A.id = ".db_escape($repo_id);

	$result = db_query($sql, "could not get repo accounts info from ".$from_branch. " because");

	set_global_connection();
	
	return $result;
}

function get_new_added_customer($interb_ref, $custname)
{
	$sql = "SELECT A.*
			FROM ".TB_PREF."debtors_master A
			WHERE A.interb_ref = ".db_escape($interb_ref)."
				AND A.name = ".db_escape($custname);

	$result = db_query($sql, "could not get new added customer accounts because");
	return db_fetch($result);
}

function check_existing_customer($custname, $bdate)
{
	$sql = "SELECT A.*
			FROM ".TB_PREF."debtors_master A
			WHERE A.name = ".db_escape($custname)."
				AND A.age = ".db_escape($bdate);

	$result = db_query($sql, "could not get customer accounts because");
	return db_fetch($result);
}

function get_mt_details($id, $stock_id, $from_branch)
{
	$db_coy = Get_db_coy($from_branch);
    set_global_connection($db_coy);
   
	$sql = "SELECT A.* 
			FROM ".TB_PREF."mt_details A 
			WHERE  A.mt_details_stock_id = ".db_escape($stock_id)."
				AND A.mt_details_header_id = ".db_escape($id);

	$result = db_query($sql, "could not get mt details info because");

	set_global_connection();

	return db_fetch($result);
}

function repo_update_mt_details($id, $from_branch)
{
	$db_coy = Get_db_coy($from_branch);
    set_global_connection($db_coy);

	$sql = "UPDATE ".TB_PREF."mt_details 
			SET mt_details_status = 2 
		WHERE mt_details_id = ".db_escape($id);
	
	db_query($sql, "could not update  mt details because ");
	set_global_connection();
}

function repo_update_mt_header($id, $from_branch)
{
	$db_coy = Get_db_coy($from_branch);
    set_global_connection($db_coy);

	$sql = "UPDATE ".TB_PREF."mt_header 
			SET mt_header_status = 2 
			WHERE mt_header_id = ".db_escape($id);
	
	db_query($sql, "could not update  mt header because ");
	set_global_connection();
}

function get_trans_delivery($trans_no, $trans_type, $debtor_no)
{
	$sql = "SELECT A.debtor_trans_no
			FROM ".TB_PREF."debtor_trans_details A 
			INNER JOIN ".TB_PREF."sales_orders B ON B.order_no = A.src_id
			INNER JOIN ".TB_PREF."sales_order_details C ON C.order_no = B.order_no AND C.stk_code = A.stock_id
			INNER JOIN ".TB_PREF."debtor_trans_details D ON D.stock_id = A.stock_id AND D.stock_id = C.stk_code
			WHERE  D.debtor_trans_no = ".db_escape($trans_no)."
				AND D.debtor_trans_type = ".db_escape($trans_type)."
				AND B.debtor_no = ".db_escape($debtor_no);

	$result = db_query($sql, "could not get delivery info because");
	//echo $sql;
	$row = db_fetch($result);
	return $row[0];
}

function add_item_serialise($type, $stock_id, $trans_no, $location, $reference, $quantity, $lot_no = "", $adj_item_id, $chasis_no = "", $remarks = "", $pnp_note = "")
{
	$sql = "INSERT INTO ".TB_PREF."item_serialise 
    (
        serialise_item_code, serialise_reference, serialise_chasis_no, serialise_lot_no, 
		serialise_qty, serialise_grn_items_id, serialise_grn_id, serialise_trans_type,
		serialise_loc_code, remarks, pnp_note
    ) VALUES (
        " . db_escape($stock_id) . ",
		" . db_escape($reference) . ", 
		" . db_escape($chasis_no) . ", 
		" . db_escape($lot_no) . ", 
		" . db_escape($quantity) . ", 
		" . db_escape($adj_item_id) . ", 
		" . db_escape($trans_no) . ", 
		" . db_escape($type) . ", 
		" . db_escape($location) . ", 
		" . db_escape($remarks) . " , 
		" . db_escape($pnp_note) . "
    )";
	
	db_query($sql, "The stock serial item movement record cannot be inserted");
	$itemserial_id = db_insert_id();

	return $itemserial_id;
}

function get_stock_items($category, $stock_id= '', $item_code='')
{
	//echo $stock_id;
	if($stock_id != ''){
		$sWhere = " AND a.stock_id = ".db_escape($stock_id)." AND b.item_code = ".db_escape($item_code);
	}
	$sql = "SELECT a.stock_id, b.item_code, b.description
			FROM ".TB_PREF."stock_master a 
				INNER JOIN ".TB_PREF."item_codes b ON b.stock_id = a.stock_id
			WHERE a.category_id = ".db_escape($category)."
			".$sWhere."
			ORDER BY a.stock_id ASC";
 //echo $sql;
	return db_query($sql, "could not get stock items because ");
}

function get_deferred_balance($trans_no, $account)
{
	$sql = "SELECT abs(SUM(amount))
			FROM ".TB_PREF."gl_trans A 
			WHERE A.loan_trans_no = ".db_escape($trans_no) ."
				AND A.account = ".db_escape($account);

	$result = db_query($sql, "could not get account deferred balance ");
	$row = db_fetch($result);
	return $row[0];
}