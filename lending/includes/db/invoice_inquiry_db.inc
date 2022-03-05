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
function add_ar_installment($trans_no, $debtor_no, $invoice_no, $reference, $invoice_date, $branch_code, $invoice_type, $installmentplcy_id,
							$months_term, $rebate, $f_rate, $firstdue_date, $maturity_date, $o_ar_amount, $ar_amount, $lcp_amount, $dp_amount, 
							$amort_amount, $total_amount, $category_id, $delivery_no, $payment_status, $payloc="Lending")
{
	$sql = "INSERT INTO ".TB_PREF."debtor_loans (
					trans_no ,debtor_no ,invoice_ref_no, delivery_ref_no ,reference ,invoice_date ,orig_branch_code ,invoice_type
					,installmentplcy_id ,months_term ,rebate ,financing_rate ,firstdue_date ,maturity_date
					,outstanding_ar_amount ,ar_amount ,lcp_amount ,downpayment_amount ,amortization_amount
					,total_amount ,category_id ,payment_status, payment_location
			) VALUES (
			        ".db_escape($trans_no)." ,".db_escape($debtor_no)." ,".db_escape($invoice_no)." ,".db_escape($delivery_no)." 
				   	,".db_escape($reference)." ,".db_escape($invoice_date)." ,".db_escape($branch_code)." ,".db_escape($invoice_type)."
					,".db_escape($installmentplcy_id)." ,".db_escape($months_term)." ,".db_escape($rebate)." ,".db_escape($f_rate)."
					,".db_escape($firstdue_date)." ,".db_escape($maturity_date)." ,".db_escape($o_ar_amount)." ,".db_escape($ar_amount)."
					,".db_escape($lcp_amount)." ,".db_escape($dp_amount)." ,".db_escape($amort_amount)." ,".db_escape($total_amount)."
					,".db_escape($category_id)." ,".db_escape($payment_status).", ".db_escape($payloc)."
			)";
	
	$ErrMsg = _('Could not insert the new a/r installment because ');
	db_query($sql, $ErrMsg);		
}

function Update_debtor_trans_status($type, $trans_no, $status, $branch_code = null){
	if($branch_code != null){
		$db_coy = Get_db_coy($branch_code);
		set_global_connection($db_coy);
	}

	$sql = "UPDATE ".TB_PREF."debtor_trans 
			SET status = ".db_escape($status)."
			WHERE trans_no  = ".db_escape($trans_no)."
				AND type = ".db_escape($type);
	
	db_query($sql, "could not update table debtor_trans");
	set_global_connection();
}

function add_ar_item_details($debtor_trans_type, $debtor_trans_no, $stock_id, $description, $quantity, $unit_price, $unit_tax, $discount_percent, $std_cost,
							$src_id, $serial, $chasis, $color_code, $item_type, $discount1, $discount2, $qty_replace, $stock_trans_no,$stock_trans_type)
{
	$sql = "INSERT INTO ".TB_PREF."debtor_trans_details (
					debtor_trans_no, debtor_trans_type, stock_id, description, quantity, unit_price, unit_tax,
					discount_percent, standard_cost, src_id, lot_no, chassis_no, color_code, item_type, discount1, discount2, qty_replace, stock_trans_no, stock_trans_type
			) VALUES (".db_escape($debtor_trans_no).", ".db_escape($debtor_trans_type).", ".db_escape($stock_id).", ".db_escape($description).",
				".db_escape($quantity).", ".db_escape($unit_price).", ".db_escape($unit_tax).", ".db_escape($discount_percent).", 
				".db_escape($std_cost).", ".db_escape($src_id).", ".db_escape($serial).", ".db_escape($chasis).", ".db_escape($color_code).", "
				.db_escape($item_type).", ".db_escape($discount1).", ".db_escape($discount2).", ".db_escape( $qty_replace).", ".db_escape( $stock_trans_no).", ".db_escape( $stock_trans_type)." )";
	
	$ErrMsg = _('The debtor transaction detail could not be written because ');
	db_query($sql, $ErrMsg);		
}

function add_loan_schedule($trans_type, $trans_no, $debtor_no, $month_no, $date_due, $weekday, $principal_due, $principal_runbal, $total_principaldue,
							$total_runbal, $interest_due, $interest_runbal, $status='unpaid', $remarks='')
{
	$sql = "INSERT INTO ".TB_PREF."debtor_loan_schedule (
					debtor_no, trans_no, trans_type, month_no, date_due, weekday, principal_due, principal_runbal, total_principaldue,
					total_runbal, interest_due, interest_runbal, status
			) VALUES (".db_escape($debtor_no).", ".db_escape($trans_no).", ".db_escape($trans_type).", ".db_escape($month_no).", ".db_escape($date_due).", 
				".db_escape($weekday).", ".db_escape($principal_due).", ".db_escape($principal_runbal).", ".db_escape($total_principaldue).", 
				".db_escape($total_runbal).", ".db_escape($interest_due).", ".db_escape($interest_runbal).", ".db_escape($status).",
				".db_escape($remarks).")";
	
	$ErrMsg = _('The debtor loan schedule could not be written because ');
	db_query($sql, $ErrMsg);
}

function incoming_invoice_logs($trans_no, $branch_code, $approved_date, $approved_by, $status){
	$sql = "INSERT INTO ".TB_PREF."incoming_invoice_logs (
					date, trans_no, branch_code, comments, prepared_by, approved_by, 
			) VALUES (
			        ".db_escape($approved_date)." ,".db_escape($trans_no)."
			)";
	
	$ErrMsg = _('Could not insert the new a/r installment because ');
	db_query($sql, $ErrMsg);	
}

function get_invoice_incoming($InvType, $status, $start, $limit, $search, $showall=false)
{
	$db_coy = Get_db_coy();
	set_global_connection($db_coy);

	if(isset($status) AND $status != 'Open'){
		$sWhere = " AND A.status = '".$status."' ";
	}else{
		$sWhere = " AND A.status = 'Open' ";
	}
	if(isset($InvType) && $InvType != 'x'){
		$sWhere .= " AND invoice_type = '".$InvType."' ";
	}
	if(isset($search)){
		$sWhere .= " AND (F.name LIKE '%".$search."%' OR A.reference LIKE '%".$search."%' )";
	}

	$sql = "SELECT A.*, B.*, E.description, F.debtor_ref, F.name, C.memo_
			FROM ".TB_PREF."debtor_trans A 
				INNER JOIN ".TB_PREF."debtor_loans B ON B.trans_no = A.trans_no
				INNER JOIN ".TB_PREF."stock_category E ON E.category_id = B.category_id
				INNER JOIN ".TB_PREF."debtors_master F ON F.debtor_no = A.debtor_no
				LEFT JOIN ".TB_PREF."comments C ON C.id = A.trans_no AND C.type = A.type
			WHERE A.type = 10 AND A.payment_terms != 0 AND B.payment_location = 'Lending'
			".$sWhere."
			ORDER BY A.tran_date ASC limit $start, $limit";

	$result = db_query($sql, "could not get all incoming invoice");
	
	set_global_connection();
	return $result;
}

function get_debtor_items($invoice_no)
{
	$db_coy = Get_db_coy($branch_code);
    set_global_connection($db_coy);

	$sql = "SELECT A.*
			FROM ".TB_PREF."debtor_trans_details A 
			WHERE A.debtor_trans_no = '".$invoice_no."'
				AND A.debtor_trans_type = '10'";
	
	$result = db_query($sql, "could not debtor trans item details ");
	set_global_connection();
	return $result;
}

function get_debtor_per_transNo($Trans_no){
	$db_coy = Get_db_coy();
    set_global_connection($db_coy);

	$sql = "SELECT A.*, B.*, F.debtor_ref, F.name
			FROM ".TB_PREF."debtor_trans A 
				INNER JOIN ".TB_PREF."debtor_loans B ON B.trans_no = A.trans_no
				INNER JOIN ".TB_PREF."debtors_master F ON F.debtor_no = A.debtor_no
			WHERE A.type = 10 AND A.payment_terms != 0 AND B.payment_location = 'Lending'
				AND A.trans_no = ".$Trans_no;

	$result = db_query($sql, "could not get all debtor invoice per trans_no");
	
	set_global_connection();
	return $result;
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

function get_debtor_trans_info($field, $order_no)
{
	$sql = "SELECT A.".$field." 
		FROM ".TB_PREF."debtor_trans A 
		WHERE A.order_ = ".db_escape($order_no);

	$result = db_query($sql, "The deptor trans info could not be retrieved");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_Approve_deptor_trans($order_no, $type)
{
	$sql = "SELECT A.*, B.*, C.*, X.memo_
		FROM ".TB_PREF."debtor_trans A
			INNER JOIN ".TB_PREF."debtors_master B ON A.debtor_no = B.debtor_no 
			INNER JOIN ".TB_PREF."debtor_loans C ON C.trans_no = A.trans_no
			LEFT JOIN ".TB_PREF."comments X ON X.id = A.trans_no AND X.type = A.type
		WHERE C.payment_location = 'Lending'
			AND A.type = ".db_escape($type)."
			AND A.order_ = ".db_escape($order_no);

	$result = db_query($sql, "could not get approve invoice info");
	return db_fetch($result);
}
//---------------------------------------------------
function check_invoice_exist($invoice_no, $incoming_no){
	$sql = "SELECT COUNT(*) FROM ".TB_PREF."debtor_loans A 
			WHERE A.invoice_no = '".db_escape($invoice_no)."' 
				AND A.incoming_no = ".db_escape($incoming_no);
	$result = db_query($sql, "check invoice failed");
	$count =  db_fetch($result);

	return $count[0];
}

function get_newcust_branch($deptor_no, $branch_ref){
	$sql = "SELECT A.*
		FROM ".TB_PREF."cust_branch A 
		WHERE A.debtor_no = '".$deptor_no."'
			AND A.branch_ref = '".$branch_ref."'";
		
	$result = db_query($sql, "could not get customer branch");
	return db_fetch($result);
}

function get_deptor_loan_schedule($trans_no, $deptor_no, $type, $allstat=true){
	if(!$allstat){
		$sWhere = "AND A.status !='paid' AND A.month_no != 0 ";
	}
	$sql = "SELECT A.id loansched_id, A.*, D.*, B.debtor_ref, B.name, C.reference
		FROM ".TB_PREF."debtor_loan_schedule A
        	INNER JOIN ".TB_PREF."debtors_master B ON B.debtor_no = A.debtor_no
            INNER JOIN ".TB_PREF."debtor_trans C ON C.trans_no = A.trans_no AND C.type = A.trans_type ";
		if($type == ST_SITERMMOD){
			$sql .= "INNER JOIN ".TB_PREF."debtor_term_modification D ON D.trans_no = A.trans_no AND D.debtor_no = A.debtor_no ";
		}else{
			$sql .= "INNER JOIN ".TB_PREF."debtor_loans D ON D.trans_no = A.trans_no AND D.debtor_no = A.debtor_no ";
		}
			
	$sql .= "WHERE A.trans_no = ".db_escape($trans_no) ."
			AND A.debtor_no = ".db_escape($deptor_no) ."
			AND A.trans_type = ".db_escape($type). " ".$sWhere."
		ORDER BY month_no ASC";

	return db_query($sql, "could not get loan schedule");
}

function get_debtor_schedule_maxinfo($trans_no, $deptor_no, $type)
{
	$sql = "SELECT A.id, A.month_no, A.date_due, A.principal_due
		FROM ".TB_PREF."debtor_loan_schedule A 
		WHERE A.trans_no = ".db_escape($trans_no) ."
			AND A.debtor_no = ".db_escape($deptor_no) ."
			AND A.trans_type = ".db_escape($type) ."
			AND A.month_no = (SELECT MAX(A.month_no) 
								FROM ".TB_PREF."debtor_loan_schedule X 
								WHERE A.trans_no = ".db_escape($trans_no) ."
									AND A.debtor_no = ".db_escape($deptor_no) ." 
									AND A.trans_type = ".db_escape($type) ." )";

	$result = db_query($sql, "could not get loan schedule month term");
	return db_fetch($result);
}

function get_debtor_schedule_last_inserted_id_pd($trans_no, $deptor_no, $type, $month_no)
{
	$sql = "SELECT A.id
		FROM ".TB_PREF."debtor_loan_schedule A 
		WHERE A.trans_no = ".db_escape($trans_no) ."
			AND A.debtor_no = ".db_escape($deptor_no) ."
			AND A.trans_type = ".db_escape($type) ."
			AND A.month_no = ".db_escape($month_no);

	$result = db_query($sql, "could not get last inserted id on table loan schedule pastdue");
	$row = db_fetch_row($result);
	return $row[0];
}
