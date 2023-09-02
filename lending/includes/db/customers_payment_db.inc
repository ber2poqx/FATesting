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
function addmodify_customer_trans($trans_type, $trans_no, $debtor_no, $BranchNo, $date_, $reference, $Total, $discount=0, $ship_via=0, $due_date="", $AllocAmt=0, $rate=0, $cashier_name=null)
{
	$new = $trans_no==0;
	$curr = get_customer_currency($debtor_no);
	if ($rate == 0){
		$rate = get_exchange_rate_from_home_currency($curr, $date_);
	}

	$SQLDate = date2sql($date_);
	if ($due_date == ""){
		$SQLDueDate = "0000-00-00";
	}else{
		$SQLDueDate = date2sql($due_date);
	}

	if($discount != 0){
		if($Total == $AllocAmt){
			$AllocAmt = ($AllocAmt + $discount);
		}
	}

	if ($trans_type == ST_CUSTPAYMENT){
		$cashier_id = get_user_id($cashier_name);
	}

    if ($new || !exists_customer_trans($trans_type, $trans_no))
    {
        if ($new){
			$trans_no = get_next_trans_no($trans_type);
		}

		$sql = "INSERT INTO ".TB_PREF."debtor_trans (
							trans_no, type, debtor_no, branch_code, tran_date, due_date, reference, ov_amount, ov_discount, rate, ship_via, alloc, status
						) VALUES (".db_escape($trans_no).", ".db_escape($trans_type).", ".db_escape($debtor_no).", ".db_escape($BranchNo).", '$SQLDate', 
							'$SQLDueDate', ".db_escape($reference).", $Total, ".db_escape($discount).", $rate, ".db_escape($ship_via).", ".db_escape($AllocAmt).", " .db_escape($status).")";
	
	}else{
		//get and set amount total
		$cust_trans = get_debtor_alloc($trans_no, $debtor_no, $trans_type);

		if($cust_trans['ov_discount'] != 0){
			$discount += $cust_trans['ov_discount'];
		}
		if($cust_trans['ov_amount'] != 0){
			$Total += $cust_trans['ov_amount'];
		}
		if($cust_trans['alloc'] != 0){
			$AllocAmt += $cust_trans['alloc'];
		}

		$sql = "UPDATE ".TB_PREF."debtor_trans SET
						debtor_no=".db_escape($debtor_no)." , branch_code=".db_escape($BranchNo).",
						tran_date= '$SQLDate', due_date='$SQLDueDate',
						reference=".db_escape($reference).", ov_amount=$Total, ov_discount=".db_escape($discount).", rate=$rate,
						ship_via=".db_escape($ship_via).", alloc=".db_escape($AllocAmt)."
			WHERE trans_no=".db_escape($trans_no)." AND type=".db_escape($trans_type);
	}

	db_query($sql, 'Could not insert debtor trans because ');

	if ($trans_type != ST_JOURNAL) // skip for journal entries
		add_audit_trail($trans_type, $trans_no, $date_, $new ? '': _("Updated."));
	return $trans_no;
}

function add_loan_ledger($trans_no, $debtor_no, $loansched_id, $typeto, $typefrom, $payment_applied, $penalty, $rebate, $interest, $date_paid, $payment_trans_no)
{
	$ledger_id = exists_payment_loan_ledger($typeto, $loansched_id, $date_paid, $payment_trans_no);

	if($ledger_id["id"] == 0 || empty($ledger_id["id"])){

		$sql = "INSERT INTO ".TB_PREF."debtor_loan_ledger (
						trans_no ,debtor_no ,loansched_id, trans_type_to, trans_type_from, payment_applied ,penalty ,rebate ,interest ,date_paid, payment_trans_no
				) VALUES (
						".db_escape($trans_no)." ,".db_escape($debtor_no)." ,".db_escape($loansched_id)." ,".db_escape($typeto)." ,".db_escape($typefrom)."
						,".db_escape($payment_applied)." ,".db_escape($penalty)." ,".db_escape($rebate)." ,".db_escape($interest)."
						,".db_escape($date_paid)." ,".db_escape($payment_trans_no)."
				)";

		$ErrMsg = _('Could not insert loan ledger because ');

	}else{

		$sql = "UPDATE ".TB_PREF."debtor_loan_ledger SET
			payment_applied=".db_escape($payment_applied)."
			WHERE id = ".db_escape($ledger_id["id"]);

		$ErrMsg = _('Could not update loan ledger because ');
	}
	
	db_query($sql, $ErrMsg);
}

function add_pdc_trans($debtor_no, $trans_date, $masterfile, $masterfile_type, $amount, $check_date, $check_no, $bank_branch, $cashier_id, $prepared_by)
{
	$sql = "INSERT INTO ".TB_PREF." pdc_trans(
				debtor_no, trans_date, masterfile, masterfile_type, amount, check_date, check_no, bank_branch, cashier_user_id, prepared_by
			) VALUES (
					".db_escape($debtor_no)." ,".db_escape($trans_date)." ,".db_escape($masterfile)."
					,".db_escape($masterfile_type)." ,".db_escape($amount)." ,".db_escape($check_date)." ,".db_escape($check_no)."
					,".db_escape($bank_branch)." ,".db_escape($cashier_id)." ,".db_escape($prepared_by)."
			)";

	$ErrMsg = _('Could not insert pdc because ');
	
	db_query($sql, $ErrMsg);
}

function auto_allocate_payment($trans_no, $trans_type, $amount)
{
	$sql = "UPDATE ".TB_PREF."debtor_trans 
				SET alloc = ".db_escape($amount)."
			WHERE trans_no  = ".db_escape($trans_no)."
				AND type = ".db_escape($trans_type);
		
		db_query($sql, "could not auto allocate payment because ");
}

function update_alloc_rebate($trans_type, $trans_no, $amount)
{
	$sql = "UPDATE ".TB_PREF."debtor_trans 
			SET ov_discount = ".db_escape($amount)."
		WHERE trans_no  = ".db_escape($trans_no)."
			AND type = ".db_escape($trans_type);
	
	db_query($sql, "could not update discount debtor trans table because ");
}

function Update_invoice_no($trans_no, $trans_type, $debtor_no, $invoice_no)
{
	$sql = "UPDATE ".TB_PREF."debtor_trans 
			SET invoice_no = ".db_escape($invoice_no)."
		WHERE trans_no  = ".db_escape($trans_no)."
			AND type = ".db_escape($trans_type)."
			AND debtor_no  = ".db_escape($debtor_no);
	
	db_query($sql, "could not update invoice number in debtor trans table because ");
}
function update_loan_schedule($loansched_id, $debtor_no, $trans_no, $type, $status, $penaltyBal=0, $penalty_status=null)
{
	$sql = "UPDATE ".TB_PREF."debtor_loan_schedule 
				SET status = ".db_escape($status)."
					,penalty_balance = ".db_escape($penaltyBal)."
					,penalty_status = ".db_escape($penalty_status)."
			WHERE id  = ".db_escape($loansched_id)."
				AND debtor_no = ".db_escape($debtor_no)."
				AND trans_no = ".db_escape($trans_no)."
				AND trans_type = ".db_escape($type);
	
	db_query($sql, "could not update table loan schedule because ");
}

function update_status_debtor_loans($trans_no, $debtor_no, $status)
{
	$sql = "UPDATE ".TB_PREF."debtor_loans 
			SET payment_status = ".db_escape($status)."
		WHERE debtor_no = ".db_escape($debtor_no)."
			AND trans_no = ".db_escape($trans_no);

	db_query($sql, "could not update table debtor loan because ");
}

function update_status_debtor_trans($trans_no, $debtor_no, $type, $status, $module_type = null)
{
	if(isset($module_type)){
		$upmode = ", module_type = ".db_escape($module_type);
	}

	$sql = "UPDATE ".TB_PREF."debtor_trans 
			SET status = ".db_escape($status)." " .$upmode."
		WHERE debtor_no = ".db_escape($debtor_no)."
			AND trans_no = ".db_escape($trans_no)."
			AND type = ".db_escape($type);
		
	db_query($sql, "could not update table debtor trans because ");
}

function update_pdc_trans($syspk, $debtor_no, $masterfile, $trans_date, $amount, $check_date, $check_no, $bank_branch, $cashier_id)
{
	$sql = "UPDATE ".TB_PREF."pdc_trans 
			SET cashier_user_id = ".db_escape($cashier_id).",
				amount = ".db_escape($amount).",
				check_date = ".db_escape($check_date).",
				check_no = ".db_escape($check_no).",
				bank_branch = ".db_escape($bank_branch).",
				trans_date = ".db_escape($trans_date)."
		WHERE id = ".db_escape($syspk)."
			AND debtor_no = ".db_escape($debtor_no)."
			AND masterfile = ".db_escape($masterfile);
		
	db_query($sql, "could not update table pdc trans because ");
}

function get_CPbank_accounts($account_code=null)
{
	$sql = "SELECT id, bank_account_name, bank_curr_code, account_code, inactive
		FROM ".TB_PREF."bank_accounts";
	if(isset($account_code)){
		$sql .= " WHERE account_code=".db_escape($account_code);
	}

	return db_query($sql, "could not get bank accounts info");
}
function get_ARinvoice_debtor_trans($Trans_no=null, $debtor_no=null)
{
	if(isset($Trans_no)){
		$sWhere .= " AND A.trans_no = ".db_escape($Trans_no);
	}
	
	if(isset($debtor_no)){
		$sWhere .= " AND A.debtor_no =".db_escape($debtor_no);
	}
	$sql = "SELECT A.*, B.*, E.description, F.debtor_ref, F.name, C.memo_
			FROM ".TB_PREF."debtor_trans A 
				INNER JOIN ".TB_PREF."debtor_loans B ON B.trans_no = A.trans_no
				INNER JOIN ".TB_PREF."stock_category E ON E.category_id = B.category_id
				INNER JOIN ".TB_PREF."debtors_master F ON F.debtor_no = A.debtor_no
				LEFT JOIN ".TB_PREF."comments C ON C.id = A.trans_no AND C.type = A.type
			WHERE (A.type = ".ST_SALESINVOICE." OR A.type =".ST_ARINVCINSTLITM."  OR A.type =".ST_SALESINVOICEREPO.") 
			".$sWhere."
				AND A.status != 'fully-paid'
			ORDER BY A.tran_date ASC";

	return db_query($sql, "could not get all debtor trans");
}

function get_invoice_per_customer($debtor_no, $tag)
{
	//if(get_company_value(user_company(), 'type') != "LENDING"){
		if($tag == "cash"){
			$criteria = "AND A.payment_terms = 0";
		}else{
			$criteria = "AND A.payment_terms != 0";
		}
	//}

	/*$sql = "SELECT A.trans_no, A.reference, A.type, A.status, B.stock_id, B.description itemdesc, D.description category, C.profit_margin
			FROM ".TB_PREF."debtor_trans A
            	INNER JOIN ".TB_PREF."debtor_trans_details B ON B.debtor_trans_no = A.trans_no AND B.debtor_trans_type = A.type
                INNER JOIN ".TB_PREF."debtor_loans C ON C.trans_no = A.trans_no AND C.debtor_no = A.debtor_no
                INNER JOIN ".TB_PREF."stock_category D ON D.category_id = C.category_id
			WHERE (A.type = ".ST_SALESINVOICE." OR A.type =".ST_ARINVCINSTLITM." OR A.type =".ST_SALESINVOICEREPO.")
				AND (A.status != 'fully-paid' AND A.status != 'Closed')
				AND IFNULL(A.module_type,'') != 'REPO'
				AND A.debtor_no = ".db_escape($debtor_no)."
				".$criteria."
			ORDER BY A.tran_date ASC";*/
	$sql = "SELECT A.trans_no, A.reference, A.type, A.status, B.stock_id, B.description itemdesc, C.payment_location, C.profit_margin, D.description category
			FROM ".TB_PREF."debtor_trans A
		        INNER JOIN ".TB_PREF."debtor_trans_details B ON B.debtor_trans_no = A.trans_no AND B.debtor_trans_type = A.type
                INNER JOIN ".TB_PREF."debtor_loans C ON C.trans_no = A.trans_no AND C.debtor_no = A.debtor_no
                INNER JOIN ".TB_PREF."stock_category D ON D.category_id = C.category_id
			WHERE (A.type = ".ST_SALESINVOICE." OR A.type =".ST_ARINVCINSTLITM." OR A.type =".ST_SALESINVOICEREPO.")
				AND (A.status != 'fully-paid' AND A.status != 'Closed'  AND A.status != 'Cancelled')
				AND IFNULL(A.module_type,'') != 'REPO'
				AND A.debtor_no = ".db_escape($debtor_no)."
				".$criteria."

			UNION ALL

			SELECT A.trans_no, A.reference, A.type, A.status, XT.stock_id, XT.description itemdesc, null payment_location, C.profit_margin, D.description category
			FROM ".TB_PREF."debtor_trans A
                INNER JOIN ".TB_PREF."debtor_term_modification C ON C.trans_no = A.trans_no AND C.debtor_no = A.debtor_no
                INNER JOIN ".TB_PREF."stock_category D ON D.category_id = C.category_id
                INNER JOIN (
					SELECT X.stock_id, X.description, XX.reference 
					FROM ".TB_PREF."debtor_trans_details X 
						INNER JOIN ".TB_PREF."debtor_trans XX ON X.debtor_trans_no = XX.trans_no AND X.debtor_trans_type = XX.type
				) XT ON XT.reference = C.invoice_ref_no
			WHERE (A.type = ".ST_SITERMMOD.")
				AND (A.status != 'fully-paid' AND A.status != 'Closed'  AND A.status != 'Cancelled')
				AND IFNULL(A.module_type,'') != 'REPO'
				AND A.debtor_no = ".db_escape($debtor_no)."
				".$criteria;
			
	return db_query($sql, "could not get all customer invoice");
}

function get_Penalty_balance($type, $trans_no, $sched_no)
{
	$sql = "SELECT A.penalty_balance, A.penalty_status 
		FROM ".TB_PREF."debtor_loan_schedule A 
		WHERE A.trans_type = ".db_escape($type). "
			AND A.trans_no = ".db_escape($trans_no). "
			AND A.id = ".db_escape($sched_no);

	$result = db_query($sql, "could not get all penalty balance");
	return db_fetch($result);
}

function get_payment_appliedInfo($type, $trans_no, $sched_no)
{
	$sql = "SELECT A.*
		FROM ".TB_PREF."debtor_loan_ledger A 
		WHERE A.trans_type_to = ".db_escape($type). "
			AND A.trans_no = ".db_escape($trans_no). "
			AND A.loansched_id = ".db_escape($sched_no);

	$result = db_query($sql, "could not get payment applied");
	return db_fetch($result);
}

function get_payment_appliedSUM($type, $trans_no, $sched_no=0)
{
	$sWhere = "";
	if($sched_no != 0){
		$sWhere = "AND A.loansched_id = ".db_escape($sched_no);
	}
	$sql = "SELECT SUM(A.payment_applied) payApplied
		FROM ".TB_PREF."debtor_loan_ledger A 
		WHERE A.trans_type_to = ".db_escape($type). "
			AND A.trans_no = ".db_escape($trans_no)." "
			.$sWhere;

	$result = db_query($sql, "could not get payment applied");
	$row = db_fetch($result);
	return $row[0];
}

function get_total_due($type, $trans_no)
{
	$sql = "SELECT SUM(A.principal_due) ARDUE
		FROM ".TB_PREF."debtor_loan_schedule A 
		WHERE A.month_no != 0 
			AND A.status !='paid'
			AND A.trans_type = ".db_escape($type). "
			AND A.trans_no = ".db_escape($trans_no);

	$result = db_query($sql, "could not get total AR Due");
	$row = db_fetch($result);
	return $row[0];
}

function get_debtor_alloc($trans_no, $debtor_no, $type)
{
	$sql = "SELECT A.ov_amount, A.ov_discount, A.alloc
		FROM ".TB_PREF."debtor_trans A 
		WHERE A.trans_no = ".db_escape($trans_no). "
			AND A.debtor_no = ".db_escape($debtor_no). "
			AND A.type = ".db_escape($type);

	$result = db_query($sql, "could not get debtor trans alloc");
	return db_fetch($result);
}

function get_debtor_payment_info($type, $module_type, $start, $limit, $search, $tagtrans=0, $showall=false, $beg=0, $cashier='')
{
	if($search != ''){
		$sWhere .= " AND (B.name LIKE '%".$search."%' OR A.reference LIKE '%".$search."%' OR C.receipt_no LIKE '%".$search."%') ";
	}
	if($tagtrans != 0){
		$sWhere .= " AND A.trans_no = ".db_escape($tagtrans);
	}
	if($beg == 1){
		$sWhere .= " AND A.opening_balances = 1 ";
	}else{
		$sWhere .= " AND A.opening_balances != 1 ";
	}
	if($module_type == 'CR'){
		$sWhere .= " AND (A.module_type = 'CR-AMORT' OR A.module_type = 'CR-INTERB' OR A.module_type = 'CR-ADJ' OR A.module_type = 'CR-DPWOSI') ";
	}elseif($module_type == 'CI'){
		$sWhere .= " AND A.module_type = 'CI-CASH' ";
	}elseif($module_type == 'ALCN'){
		$sWhere .= " AND (A.module_type = 'ALCN-DP' OR A.module_type = 'ALCN-INTERB' OR A.module_type = 'ALCN-ADJ') ";
	}elseif($module_type == 'BEG'){
		$sWhere .= " AND A.module_type = 'BEG-DP' ";
	}elseif($module_type == 'PDC'){
		$sWhere .= " AND A.module_type = 'PDC-ACK' ";
	}

	if($cashier != ''){
		$sWhere .= " AND C.cashier_user_id = ".db_escape($cashier);
	}
	$sql = "SELECT A.*, B.debtor_ref, B.name, C.*, D.memo_, E.bank_account_name, G.real_name, K.void_status
			FROM ".TB_PREF."debtor_trans A 
				INNER JOIN ".TB_PREF."debtors_master B ON B.debtor_no = A.debtor_no
				INNER JOIN ".TB_PREF."bank_trans C ON C.trans_no = A.trans_no AND C.type = A.type AND C.isotherentry = 0
				INNER JOIN ".TB_PREF."comments D ON D.type = A.type AND D.id = A.trans_no
				LEFT JOIN ".TB_PREF."bank_accounts E ON E.id = C.bank_act
				LEFT JOIN ".TB_PREF."collection_types F ON F.collect_id = A.collect_id
				LEFT JOIN ".TB_PREF."users G ON G.id = C.cashier_user_id
				LEFT JOIN ".TB_PREF."voided K ON K.id = A.trans_no AND K.type = A.type
			WHERE A.type = ".db_escape($type).$sWhere."
			ORDER BY A.tran_date DESC, A.reference DESC";
	
	$sqllimit = $sql ." limit $start, $limit";

	if($showall){
		$result = db_query($sql, "could not get debtor trans payment");
	}else{
		$result = db_query($sqllimit, "could not get debtor trans payment");
	}
	return $result;
}

function get_debtor_payment_per_transno($trans_type, $trans_no)
{
	$sql = "SELECT A.*, B.debtor_ref, B.name, C.*, D.memo_, E.bank_account_name, G.real_name, H.collection, I.bank_account_name
			FROM ".TB_PREF."debtor_trans A 
				INNER JOIN ".TB_PREF."debtors_master B ON B.debtor_no = A.debtor_no
				INNER JOIN ".TB_PREF."bank_trans C ON C.trans_no = A.trans_no AND C.type = A.type AND C.isotherentry = 0
				LEFT JOIN ".TB_PREF."comments D ON D.type = A.type AND D.id = A.trans_no
				LEFT JOIN ".TB_PREF."bank_accounts E ON E.id = C.bank_act
				LEFT JOIN ".TB_PREF."collection_types F ON F.collect_id = A.collect_id
				LEFT JOIN ".TB_PREF."users G ON G.id = C.cashier_user_id
				LEFT JOIN ".TB_PREF."collection_types H ON H.collect_id = A.collect_id
				LEFT JOIN ".TB_PREF."bank_accounts I ON I.id = C.bank_act
			WHERE A.type = ".db_escape($trans_type)." 
				AND A.trans_no = ".db_escape($trans_no); 

	return db_query($sql, "could not get payment data to void becuase");
}

function check_schedule_status($trans_no, $trans_type, $debtor_no)
{
	$sql = "SELECT COUNT(*) 
			FROM ".TB_PREF."debtor_loan_schedule A 
			WHERE A.status != 'paid'
				AND A.trans_no = ".db_escape($trans_no). "
				AND A.trans_type = ".db_escape($trans_type). "
				AND A.debtor_no = ".db_escape($debtor_no);

	$result = db_query($sql, "could not check schedule table ");
	$ret = db_fetch($result);

	return $ret[0];
}

function get_loan_schedule($trans_no, $deptor_no, $type)
{
	$sql = "SELECT A.id loansched_id, A.*
		FROM ".TB_PREF."debtor_loan_schedule A
		WHERE A.trans_no = ".db_escape($trans_no) ."
			AND A.debtor_no = ".db_escape($deptor_no) ."
			AND A.trans_type = ".db_escape($type). "
			AND A.status !='paid' AND A.month_no != 0 
			AND (A.remarks IS NULL OR A.remarks = '')
		ORDER BY month_no ASC";

	return db_query($sql, "could not get loan schedule");
}

function get_loan_schedule_dP_lending($trans_no, $deptor_no, $type)
{
	$sql = "SELECT A.id loansched_id, A.*
		FROM ".TB_PREF."debtor_loan_schedule A
		WHERE A.trans_no = ".db_escape($trans_no) ."
			AND A.debtor_no = ".db_escape($deptor_no) ."
			AND A.trans_type = ".db_escape($type). "
			AND A.month_no = 0 
		ORDER BY month_no ASC";

	return db_query($sql, "could not get loan schedule");
}

function get_loan_schedule_penalty($trans_no, $deptor_no, $type)
{
	$sql = "SELECT A.id loansched_id, A.*
		FROM ".TB_PREF."debtor_loan_schedule A
		WHERE A.trans_no = ".db_escape($trans_no) ."
			AND A.debtor_no = ".db_escape($deptor_no) ."
			AND A.trans_type = ".db_escape($type). "
			AND COALESCE(A.penalty_status, 0) != 'paid' AND A.month_no != 0 
		ORDER BY month_no ASC";

	return db_query($sql, "could not get loan schedule");
}

function get_loan_schedule_pastdue($trans_no, $deptor_no, $type)
{
	$sql = "SELECT A.id loansched_id, A.*
		FROM ".TB_PREF."debtor_loan_schedule A
		WHERE A.trans_no = ".db_escape($trans_no) ."
			AND A.debtor_no = ".db_escape($deptor_no) ."
			AND A.trans_type = ".db_escape($type). "
			AND A.status !='paid' AND A.month_no != 0 
			AND A.remarks = 'pastdue'
		ORDER BY month_no ASC";

	return db_query($sql, "could not get loan schedule pastdue");
}

function get_loan_ledger_payment($Trans_no, $typeFrom, $typeTo)
{
	/*$sql = "SELECT A.loansched_id, A.trans_no, A.date_paid, SUM(A.penalty) penalty, SUM(A.rebate) rebate, SUM(A.payment_applied) PayAmount,
					B.principal_due, A.debtor_no, B.date_due, B.month_no, B.principal_runbal, C.ov_amount, D.ar_amount
		FROM ".TB_PREF."debtor_loan_ledger A
			INNER JOIN ".TB_PREF."debtor_loan_schedule B ON B.id = A.loansched_id
			INNER JOIN ".TB_PREF."debtor_trans C ON C.trans_no = A.payment_trans_no
			INNER JOIN ".TB_PREF."debtor_loans D ON D.trans_no = A.trans_no AND D.debtor_no = A.debtor_no
		WHERE A.payment_trans_no = ".db_escape($Trans_no) ."
			AND A.trans_type_from = ".db_escape($typeFrom) ."
			AND A.trans_type_to = ".db_escape($typeTo);*/
	$sql = "SELECT A.loansched_id, A.trans_no, A.date_paid, SUM(A.penalty) penalty, SUM(A.rebate) rebate, SUM(A.payment_applied) PayAmount,
					B.principal_due, A.debtor_no, B.date_due, B.month_no, B.principal_runbal, D.ar_amount
		FROM ".TB_PREF."debtor_loan_ledger A
			INNER JOIN ".TB_PREF."debtor_loan_schedule B ON B.id = A.loansched_id
			INNER JOIN ".TB_PREF."debtor_loans D ON D.trans_no = A.trans_no AND D.debtor_no = A.debtor_no
		WHERE A.payment_trans_no = ".db_escape($Trans_no) ."
			AND A.trans_type_from = ".db_escape($typeFrom) ."
			AND A.trans_type_to = ".db_escape($typeTo);

	return db_query($sql, "could not get loan ledger");
}

function get_Trans_Type($trans_no)
{
	$sql = "SELECT A.trans_type_to 
			FROM ".TB_PREF."cust_allocations A 
			WHERE A.trans_no_from = ".db_escape($trans_no);

	$result = db_query($sql, "could not get trans type ");
	$ret = db_fetch($result);

	return $ret[0];
}

function get_DP_NoAmort($type, $trans_no)
{
	$sql = "SELECT A.type, A.trans_no, A.person_id, A.bank_act, A.ref, A.trans_date, A.masterfile, B.amount,
				B.type_no, B.account, B.mcode, C.account_code, C.account_name
		FROM ".TB_PREF."bank_trans A 
				INNER JOIN ".TB_PREF."gl_trans B 
					ON B.type = A.type AND B.type_no = A.trans_no AND A.isotherentry = 0
				INNER JOIN ".TB_PREF."chart_master C ON C.account_code = B.account
		WHERE A.trans_no = ".db_escape($trans_no)."
			AND A.type = ".db_escape($type);

	return db_query($sql, "could not get down payment item");
}

function get_amort_downpayment($trans_no, $debtor_no, $type)
{
	$sql = "SELECT A.id loansched_id, A.*, B.downpayment_amount, B.discount_downpayment, B.discount_downpayment2, B.maturity_date, B.rebate, B.profit_margin
		FROM ".TB_PREF."debtor_loan_schedule A 
				INNER JOIN ".TB_PREF."debtor_loans B ON B.trans_no = A.trans_no AND B.debtor_no = A.debtor_no
		WHERE A.status != 'paid'
			AND A.month_no = 0 
			AND A.trans_no = ".db_escape($trans_no)."
			AND A.debtor_no = ".db_escape($debtor_no)."
			AND A.trans_type = ".db_escape($type);
		
	return db_query($sql, "could not get down-payment amount schedule");
}

function get_debtor_loans_info($trans_no=0, $debtor_no=0)
{
	$sql = "SELECT A.*
		FROM ".TB_PREF."debtor_loans A 
		WHERE  A.trans_no = ".db_escape($trans_no)."
			AND A.debtor_no = ".db_escape($debtor_no);

	$result = db_query($sql, "could not get loan info");
	return db_fetch($result);
}

function exists_payment_loan_ledger($type, $loansched_id, $date_paid, $pay_trans_no)
{
	$sql = "SELECT id 
			FROM ".TB_PREF."debtor_loan_ledger 
			WHERE payment_applied = 0 AND penalty <> 0
				AND trans_type_to = ".db_escape($type)."
				AND date_paid = ".db_escape($date_paid)."
				AND loansched_id = ".db_escape($loansched_id)."
				AND payment_trans_no = ".db_escape($pay_trans_no);

	$result = db_query($sql, "Cannot retreive a loan ledger info");
	return db_fetch($result);
}

function get_ar_cash($trans_no, $debtor_no, $trans_type)
{
	$sql = "SELECT A.trans_no, A.type, A.debtor_no, A.tran_date, A.reference, A.ov_amount, A.alloc, A.status, B.ar_amount
		FROM ".TB_PREF."debtor_trans A 
				INNER JOIN ".TB_PREF."debtor_loans B ON B.trans_no = A.trans_no AND B.debtor_no = A.debtor_no
		WHERE A.payment_terms = 0 
			AND (A.status = 'Open' OR A.status = 'part-paid')
			AND A.debtor_no = ".db_escape($debtor_no)."
			AND A.type = ".db_escape($trans_type)."
			AND A.trans_no = ".db_escape($trans_no);

	return db_query($sql, "could not get sales cash info");
}

function get_cash_discount($trans_no, $trans_type)
{
	$sql = "SELECT (SUM(A.discount1 * A.quantity) + SUM(A.discount2)) discount
		FROM ".TB_PREF."debtor_trans_details A 
		WHERE A.debtor_trans_type = ".db_escape($trans_type)."
			AND A.debtor_trans_no = ".db_escape($trans_no)."
			AND A.quantity != 0";

	$result = db_query($sql, "could not get sales cash discount info");
	$row =  db_fetch($result);

	return $row[0];
}

function get_alloc_payment($trans_no, $debtor_no, $trans_type)
{
	$sql = "SELECT sum(amt) amount
		FROM ".TB_PREF."cust_allocations A 
		WHERE A.person_id = ".db_escape($debtor_no)."
			AND A.trans_type_to = ".db_escape($trans_type)."
			AND A.trans_no_to = ".db_escape($trans_no);

	$result = db_query($sql, "could not get customer allocation");
	$row = db_fetch($result);
	return $row[0];
}

function check_ar_balance($trans_no, $trans_type)
{
	$sql = "SELECT (A.ov_amount - A.alloc) amount 
			FROM ".TB_PREF."debtor_trans A 
			WHERE A.trans_no = ".db_escape($trans_no)." 
				AND A.type = ".db_escape($trans_type);

	$result = db_query($sql, "could not check ar balance");
	$row = db_fetch($result);
	return $row[0];
}

function get_ar_cash_alloc($trans_no, $debtor_no, $trans_type)
{
	$sql = "SELECT A.trans_no, A.debtor_no, A.tran_date, A.ov_amount, A.alloc, B.amt
		FROM ".TB_PREF."debtor_trans A 
			INNER JOIN ".TB_PREF."cust_allocations B ON B.trans_no_to = A.trans_no AND B.trans_type_to = A.type
		WHERE A.debtor_no = ".db_escape($debtor_no)."
			AND A.type = ".db_escape($trans_type)."
			AND A.trans_no = ".db_escape($trans_no);
	
	return db_query($sql, "could not get sales cash info");
}

function get_item_detials($trans_no, $trans_type)
{
	$sql = "SELECT A.*
		FROM ".TB_PREF."debtor_trans_details A 
		WHERE A.debtor_trans_no = ".db_escape($trans_no)."
			AND A.debtor_trans_type = ".db_escape($trans_type);
	
	return db_query($sql, "could not get sales invoice item details");	
}

function get_AllocDP_customer()
{
	$sql = "SELECT A.debtor_no, A.name, A.debtor_ref, A.address, A.tax_id, B.ov_amount, B.trans_no, B.payment_type, B.collect_id, B.type
		FROM ".TB_PREF."debtors_master A
			LEFT JOIN ".TB_PREF."debtor_trans B ON B.debtor_no = A.debtor_no
		WHERE B.module_type = 'CR-DPWOSI' AND B.status = 'Open'
		ORDER BY A.name ";

	return db_query($sql, "could not get customers to allocate");
}

function get_invoice_per_adjustment($debtor_no)
{
	$sql = "SELECT A.trans_no, A.type, A.debtor_no, A.reference, A.status, D.total_receivable, E.payment_location, F.category_id, F.description category, B.stock_id, B.description itemdesc
			FROM ".TB_PREF."debtor_trans A 
				INNER JOIN ".TB_PREF."debtor_trans_details B ON B.debtor_trans_no = A.trans_no AND B.debtor_trans_type = A.type
				INNER JOIN ".TB_PREF."returned_units C ON C.debtor_trans_details_replace_id = B.id
				INNER JOIN ".TB_PREF."sales_return_replacement D ON D.trans_no = A.trans_no
				LEFT JOIN ".TB_PREF."debtor_loans E ON E.trans_no = D.trans_no_ref AND E.invoice_ref_no = D.trans_ref
				INNER JOIN ".TB_PREF."stock_category F ON F.category_id = D.category_id
			WHERE A.type = ".ST_SALESRETURN." 
				AND D.total_receivable != 0
				AND A.alloc < D.total_receivable
				AND (A.status != 'fully-paid' AND A.status != 'Closed' AND A.status != 'Cancelled')
				AND A.debtor_no = ".db_escape($debtor_no)."
			ORDER BY A.tran_date ASC";

	return db_query($sql, "could not get all customer invoice adjustment");
}

function get_ar_adjustment($trans_no, $debtor_no, $trans_type)
{
	$sql = "SELECT A.trans_no, A.type, A.debtor_no, A.tran_date, A.reference, A.ov_amount, A.alloc, A.status, B.total_receivable ar_amount
		FROM ".TB_PREF."debtor_trans A 
				INNER JOIN ".TB_PREF."sales_return_replacement B ON B.trans_no = A.trans_no
		WHERE B.total_receivable != 0
			AND A.alloc < B.total_receivable
			AND A.debtor_no = ".db_escape($debtor_no)."
			AND A.type = ".db_escape($trans_type)."
			AND A.trans_no = ".db_escape($trans_no);

	return db_query($sql, "could not get sales cash info");
}

function get_item_detials_adjustment($trans_no, $trans_type)
{
	$sql = "SELECT A.replace_stock_id stock_id, A.replace_qty quantity, A.replace_price unit_price, A.replace_serial_no lot_no, 
				A.replace_chassis_no chassis_no, B.description
		FROM ".TB_PREF."sales_return_replacement_details A 
			INNER JOIN ".TB_PREF."stock_master B ON B.stock_id = A.replace_stock_id
		WHERE A.sales_return_replacement_no = ".db_escape($trans_no);
	
	return db_query($sql, "could not get replacement item details");	
}

function check_cr_number($crno, $module_type)
{
	$sql = "SELECT COUNT(*) 
			FROM ".TB_PREF."bank_trans A 
				INNER JOIN ".TB_PREF."debtor_trans B ON B.trans_no = A.trans_no AND B.type = A.type AND A.isotherentry = 0
			WHERE B.module_type LIKE '".$module_type."%' 
				AND A.receipt_no = ".db_escape($crno);

	$result = db_query($sql, "check cr number failed");
	$count =  db_fetch($result);

	return $count[0];
}

function get_debtor_last_payment_date($trans_no, $trans_type, $debtor_no)
{
	$sql = "SELECT A.date_paid 
			FROM ".TB_PREF."debtor_loan_ledger A 
			WHERE A.trans_no = ".db_escape($trans_no)."
				AND A.trans_type_to = ".db_escape($trans_type)."
				AND A.debtor_no = ".db_escape($debtor_no)."
			ORDER BY A.date_paid DESC LIMIT 1 ";

	$result = db_query($sql, "could not get last payment date");
	$row =  db_fetch($result);

	return $row[0];
}

function count_paid_penalty($trans_no, $trans_type, $debtor_no)
{
	$sql = "SELECT COUNT(*) 
			FROM ".TB_PREF."debtor_loan_schedule A
			WHERE (A.status = 'unpaid' OR A.status = 'partial')
				AND  A.penalty_status = 'paid'
				AND A.trans_no = ".db_escape($trans_no)."
				AND A.trans_type = ".db_escape($trans_type)."
				AND A.debtor_no = ".db_escape($debtor_no);

	$result = db_query($sql, "count paid penalty failed");
	$count =  db_fetch($result);

	return $count[0];
}

function get_paid_penalty($trans_no, $trans_type, $debtor_no)
{
	$sql = "SELECT A.id loansched_id, A.* 
			FROM ".TB_PREF."debtor_loan_schedule A
			WHERE(A.status = 'unpaid' OR A.status = 'partial')
				AND A.penalty_status = 'paid'
				AND A.trans_no = ".db_escape($trans_no)."
				AND A.trans_type = ".db_escape($trans_type)."
				AND A.debtor_no = ".db_escape($debtor_no);

	return db_query($sql, "get paid penalty failed");

}

function get_lastpayment_mosterm($trans_no, $debtor_no)
{
	$sql = "SELECT A.month_no
		FROM ".TB_PREF."debtor_loan_schedule  A 
		WHERE A.status != 'paid'
			AND A.trans_no = ".db_escape($trans_no)."
			AND A.debtor_no = ".db_escape($debtor_no);
	
	return db_query($sql, "could not get replacement item details");
}

function get_dp_discount($trans_no, $debtor_no, $invoice_type='new')
{
	$sql = "SELECT A.discount_downpayment, A.discount_downpayment2
			FROM ".TB_PREF."debtor_loans A
			WHERE A.invoice_type = ".db_escape($invoice_type)."
				AND A.trans_no = ".db_escape($trans_no)."
				AND A.debtor_no = ".db_escape($debtor_no);

	$result = db_query($sql, "could not get dp discount");
	return db_fetch($result);
}

function get_debtor_invoice_date($trans_no, $debtor_no, $type)
{
	$sql = "SELECT A.tran_date
			FROM ".TB_PREF."debtor_trans A
			WHERE A.trans_no = ".db_escape($trans_no)."
				AND A.type = ".db_escape($type)."
				AND A.debtor_no = ".db_escape($debtor_no);

	$result = db_query($sql, "could not get invoice date");
	$row =  db_fetch($result);

	return $row[0];
}

function get_deptors_termmode($trans_no, $debtor_no, $type)
{
	//modify on 7-6-23
	// if change term - not full payment -> get new invoice 
	// if full payment -> get old invoice
	if($type == ST_SALESINVOICE || $type == ST_SALESINVOICEREPO || $type == ST_ARINVCINSTLITM ){
		$sql = "SELECT A.*
				FROM ".TB_PREF."debtor_term_modification A
				WHERE A.si_trans_no = ".db_escape($trans_no) ."
					AND A.debtor_no = ".db_escape($debtor_no) ."
					AND A.amount_to_be_paid_status !='paid'
					AND A.months_term != 0 ";
	}else{
		$sql = "SELECT A.*
				FROM ".TB_PREF."debtor_term_modification A
				WHERE A.trans_no = ".db_escape($trans_no) ."
					AND A.debtor_no = ".db_escape($debtor_no) ."
					AND type = ".db_escape($type)."
					AND A.amount_to_be_paid_status !='paid'
					AND A.months_term != 0 ";
	}	

	return db_query($sql, "could not get loan term mode");
}

function update_termmode_status($trans_no, $debtor_no, $status)
{
	$sql = "UPDATE ".TB_PREF."debtor_term_modification 
			SET amount_to_be_paid_status = ".db_escape($status)."
		WHERE si_trans_no  = ".db_escape($trans_no)."
			AND debtor_no = ".db_escape($debtor_no);
	
	db_query($sql, "could not auto allocate payment because ");
}

function get_mos_termode($trans_no, $debtor_no, $type)
{
	$sql = "SELECT A.months_term
		FROM ".TB_PREF."debtor_term_modification A 
		WHERE A.months_term != 0
			AND A.trans_no = ".db_escape($trans_no)."
			AND A.debtor_no = ".db_escape($debtor_no)."
			AND type = ".db_escape($type);

	$result = db_query($sql, "could not get term mode term");
	$row = db_fetch($result);
	return $row[0];
}

function get_AR_maturity($trans_no, $debtor_no)
{
	$sql = "SELECT A.maturity_date
		FROM ".TB_PREF."debtor_loans A 
		WHERE A.trans_no = ".db_escape($trans_no)."
			AND A.debtor_no = ".db_escape($debtor_no);

	$result = db_query($sql, "could not get maturity date");
	$row = db_fetch($result);
	return $row[0];
}


function check_operator_builder($user_login)
{
	$sql = "SELECT A.id, B.id usersid, B.user_id cashier_id, B.real_name cashier_name, B.role_id
			FROM ".TB_PREF."operator_builder A 
				INNER JOIN ".TB_PREF."users B ON B.user_id = A.cashier_id
				INNER JOIN ".TB_PREF."users C ON C.user_id = A.preparer_id
			WHERE !A.inactive 
				AND (A.cashier_id = ".db_escape($user_login)."
				 OR A.preparer_id =  ".db_escape($user_login). ")";

	return db_query($sql, "could not get loan ledger");
}

function get_casheirCol()
{
	$sql = "SELECT A.id, A.user_id, A.real_name, A.role_id 
			FROM ".TB_PREF."users A 
			WHERE (A.role_id = 15 OR A.role_id = 11) AND A.inactive = 0
			ORDER BY A.role_id DESC";

	return db_query($sql, "could not get user cashier/collector ");
}

function get_item_to_supplier($trans_no, $trans_type)
{
	$sql = "SELECT C.supplier_id, C.supp_name
			FROM ".TB_PREF."debtor_trans_details A 
				INNER JOIN ".TB_PREF."stock_master B ON B.stock_id = A.stock_id
				INNER JOIN ".TB_PREF."suppliers C ON C.supplier_id = B.manufacturer
			WHERE A.debtor_trans_no = ".db_escape($trans_no)."
				AND A.debtor_trans_type = ".db_escape($trans_type);

	return db_query($sql, "could not get item supplier");
}

function get_NoSchedDownPaymnt($trans_no, $debtor_no)
{
	$sql = "SELECT A.*
		FROM ".TB_PREF."debtor_loans A
		WHERE A.trans_no = ".db_escape($trans_no)."
			AND A.debtor_no = ".db_escape($debtor_no);
		
	return db_query($sql, "could not get down-payment amount schedule");
}

function get_DownPaymnt_paid($trans_no, $type)
{
	$sql = "SELECT SUM(A.ov_amount)
		FROM ".TB_PREF."debtor_trans A
			INNER JOIN ".TB_PREF."bank_trans B ON B.trans_no = A.trans_no AND B.type = A.type
		WHERE B.masterfile = ".db_escape($trans_no)."
			AND B.masterfile_type = ".db_escape($type);
		
	$result = db_query($sql, "could not get down-payment paid amount");
	$row = db_fetch($result);
	return $row[0];
}

function get_deferdBal($trans_no, $glcode)
{
	$sql = "SELECT abs(ROUND(SUM(X.amount),2)) bal 
			FROM ".TB_PREF."gl_trans X 
			WHERE X.loan_trans_no=".db_escape($trans_no)."
				And X.account = ".db_escape($glcode);
				
	$result = db_query($sql, "could not get defered balance");
	$row =  db_fetch($result);

	return $row[0];
}

function get_islive($branch)
{

    $db_coy = Get_db_coy($branch);
    set_global_connection($db_coy);
    
    $sql = "SELECT value FROM ".TB_PREF."sys_prefs WHERE name='deployment_status'";
                
    $result = db_query($sql, "The deployment status could not be retrieved");
    $row =  db_fetch($result);

    set_global_connection();        
    return $row[0];
}

function get_List_COA($search)
{
	if($search != ''){
		$sWhere .= " AND (chart.account_code LIKE '%".$search."%' OR chart.account_name LIKE '%".$search."%') ";
	}
	$sql = "SELECT chart.account_code, chart.account_name, type.name, chart.inactive, type.id
			FROM ".TB_PREF."chart_master AS chart 
				INNER JOIN ".TB_PREF."chart_types AS type ON chart.account_type = type.id
			WHERE chart.inactive = 0 ".$sWhere."
			ORDER BY chart.account_code";

    return db_query($sql, "Failed in retreiving GL account list.");
}

function get_InvoiceCOA($trans_no, $type)
{
	$sql = "SELECT X.account 
			FROM ".TB_PREF."gl_trans X 
			WHERE X.person_id is not null AND X.amount >0 
				AND X.type_no=".db_escape($trans_no)."
				And X.type = ".db_escape($type);
				
	$result = db_query($sql, "could not get defered balance");
	$row =  db_fetch($result);

	return $row[0];
}

function isTermFullpayment($trans_no, $type, $debtor_no)
{
	$sql = "SELECT z.term_mode_fullpayment 
			FROM ".TB_PREF."debtor_term_modification z 
			WHERE z.type = ".db_escape($type) . "
				and z.debtor_no = ".db_escape($debtor_no) . "
				and (z.trans_no = ".db_escape($trans_no)." OR z.si_trans_no = ".db_escape($trans_no) .") " ;
			
	$result = db_query($sql, "could not get defered balance");
	$row =  db_fetch($result);
	
	return $row[0];
}

function check_voided_payments_exist($type, $trans_no)
{
	$sql = "SELECT COUNT(*)
			FROM ".TB_PREF."voided X 
			WHERE X.type=".db_escape($type)."
				And X.id = ".db_escape($trans_no);
			
	$result = db_query($sql, "could not query voided transaction table");
	$row = db_fetch($result);
	return $row[0];

}

function get_debtor_trans_all($type, $trans_no)
{
	$sql = "SELECT A.*
		FROM ".TB_PREF."debtor_trans A 
		WHERE A.trans_no = ".db_escape($trans_no)."
			AND A.type = ".db_escape($type);

	$result = db_query($sql, "The deptor trans all could not be retrieved");
	return db_fetch($result);
}

function get_sl_masterfile()
{
	$sql = "SELECT *
			FROM (
				SELECT debtor_no as id, name as name, employee as type FROM ".TB_PREF."debtors_master
				/*UNION ALL
				SELECT supplier_id as id, supp_name as name, '3' as type FROM ".TB_PREF."suppliers*/
			) sl
			ORDER BY sl.name ASC, sl.type ASC;";

	return db_query($sql, "The masterfile could not be retrieved");
}

function get_customer_type($debtor_no)
{
	//customer=0; employee=1; bank=2
	$sql = "SELECT A.employee
			FROM ".TB_PREF."debtors_master A 
			WHERE A.debtor_no = ".db_escape($debtor_no);

	$result = db_query($sql, "could not get customer type");
	$row = db_fetch($result);
	return $row[0];
}

//void payments------------------->>
function get_loan_ledger_payment_per_transno($type, $trans_no)
{
	$sql = "SELECT B.id, B.month_no, C.reference, B.date_due, B.principal_due, A.date_paid, A.payment_applied, A.rebate, A.penalty, A.id ledger_id, A.trans_no, A.debtor_no, B.trans_type
	FROM ".TB_PREF."debtor_loan_ledger A
		INNER JOIN ".TB_PREF."debtor_loan_schedule B ON B.id = A.loansched_id
		INNER JOIN ".TB_PREF."debtor_trans C ON C.trans_no = A.payment_trans_no AND C.type = A.trans_type_from
	WHERE  A.trans_type_from = ".db_escape($type)."
		AND A.payment_trans_no = ".db_escape($trans_no)."
	ORDER BY B.id ASC";
		
	return db_query($sql, "could not get amortization ledger because ");
}
function get_gl_trans_void($type, $trans_no)
{
	$sql = "SELECT * FROM ".TB_PREF."gl_trans WHERE type = ".db_escape($type)." AND type_no =".db_escape($trans_no);
	return db_query($sql,"could not get gl trans because ");
}
function update_gl_trans_void($counter, $type, $trans_no, $Voidno)
{
	$sql = "UPDATE ".TB_PREF."gl_trans SET
				void_entry =".db_escape($Voidno)."
			WHERE counter = ".db_escape($counter)."
				AND type = ".db_escape($type)."
				AND type_no =".db_escape($trans_no);

	return db_query($sql,"could not update gl trans void entry because ");
}
function update_debtor_trans_void($type, $trans_no, $status)
{
	$sql = "UPDATE ".TB_PREF."debtor_trans SET
				status =".db_escape($status)."
			WHERE type = ".db_escape($type)."
				AND trans_no =".db_escape($trans_no);

	return db_query($sql,"could not update debtor trans void because ");
}
function update_loan_sched_void($loansched_id = 0, $debtor_no, $trans_no, $status)
{
	$sql = "UPDATE ".TB_PREF."debtor_loan_schedule SET
				status =".db_escape($status).",
				penalty_status = null
			WHERE debtor_no = ".db_escape($debtor_no)."
				AND trans_no =".db_escape($trans_no);
	/*modified by Albert fix AR openning void*/
	if($loansched_id != 0){
		$sql .= " AND id = ".db_escape($loansched_id);
	}
	/**/

	return db_query($sql,"could not update loan schedule status because ");		
}
function update_void($void_id, $trans_no, $status, $note)
{
	$sql = "UPDATE ".TB_PREF." voided SET
				void_status =".db_escape($status).",
				status_note = ".db_escape($note)."
			WHERE void_id = ".db_escape($void_id)."
				AND id = ".db_escape($trans_no);

	return db_query($sql,"could not update loan schedule status because ");	
}
function delete_cust_allocations_void($type, $trans_no, $invoice_no)
{
	$sql = "DELETE FROM ".TB_PREF."cust_allocations 
			WHERE trans_type_from = ".db_escape($type)."
					AND trans_no_from =".db_escape($trans_no)."
					AND trans_no_to =".db_escape($invoice_no);

	return db_query($sql,"could not delete customer allocation because ");
}
function delete_loan_ledger_void($type, $trans_no, $pay_trans_no)
{
	$sql = "DELETE FROM ".TB_PREF."debtor_loan_ledger 
			WHERE trans_type_from = ".db_escape($type)."
					AND trans_no =".db_escape($trans_no)."
					AND payment_trans_no =".db_escape($pay_trans_no);

	return db_query($sql,"could not delete loan ledger because ");
}
function disapproved_void($type, $trans_no, $void_id)
{
	$sql = "DELETE FROM ".TB_PREF."voided 
			WHERE void_id = ".db_escape($void_id)." 
				AND type = ".db_escape($type)." 
				AND id = ".db_escape($trans_no);
	
	return db_query($sql,"could not disapproved void because ");
}
function check_ledger_to_void($type, $trans_no)
{
	$sql = "SELECT B.loansched_id, B.trans_no, B.payment_trans_no 
			FROM ".TB_PREF."debtor_loan_ledger B 
			WHERE B.trans_no = ".db_escape($trans_no)." 
				AND B.trans_type_to = ".db_escape($type)." 
			ORDER BY B.loansched_id DESC";

	return db_query($sql,"could not get loan ledger because ");
}

function check_if_void_done($id)
{
	$sql = "SELECT A.*
			FROM ".TB_PREF."voided A 
			WHERE A.void_id = ".db_escape($id);

	$result = db_query($sql,"could not get voided data ");
	return db_fetch($result);
}

function get_bank_trans_perno($type, $trans_no)
{
	$sql = "SELECT A.*
		FROM ".TB_PREF."bank_trans A 
		WHERE A.trans_no = ".db_escape($trans_no)."
			AND A.type = ".db_escape($type);

	$result = db_query($sql, "The bank trans per trans number could not be retrieved");
	return db_fetch($result);
}

function exists_pdc_trans($id, $masterfile, $masterfile_type)
{
	$sql = "SELECT COUNT(A.*) pdc
			FROM ".TB_PREF." pdc_trans a
			WHERE id = ".db_escape($type)."
				AND masterfile = ".db_escape($masterfile)."
				AND masterfile_type = ".db_escape($masterfile_type);

	$result = db_query($sql, "Cannot retreive pdc info");
	$ret = db_fetch($result);

	return $ret[0];
}