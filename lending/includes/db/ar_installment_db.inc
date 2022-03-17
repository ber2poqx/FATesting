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
function get_ar_installment($InvType='new', $start, $limit, $search, $showall=false)
{
	if(isset($InvType) && $InvType != 'x'){
		$sWhere .= " AND B.invoice_type = '".$InvType."' ";
	}
	if($search != ''){
		$sWhere .= " AND (D.name LIKE '%".$search."%' OR A.reference LIKE '%".$search."%') ";
	}
	if (!$showall) $sWhere .= " AND (A.status != 'fully-paid' AND A.status != 'Closed')";

	/*$sql = "SELECT A.*, B.*, C.memo_,  D.*, E.description, Z.unrecovered_cost, Z.addon_amount, Z.total_unrecovered, 
				Z.repo_date, Z.past_due, Z.over_due, Z.comments
			FROM ".TB_PREF."debtor_trans A 
				INNER JOIN ".TB_PREF."debtor_loans B ON B.trans_no = A.trans_no AND B.debtor_no = A.debtor_no
				LEFT JOIN ".TB_PREF."comments C ON C.id = A.trans_no AND C.type = A.type
				INNER JOIN ".TB_PREF."debtors_master D ON D.debtor_no = A.debtor_no
				INNER JOIN ".TB_PREF."stock_category E ON E.category_id = B.category_id
				LEFT JOIN ".TB_PREF."repo_accounts Z ON Z.ar_trans_no = A.trans_no AND Z.ar_trans_type = A.type
			WHERE (A.type = ".ST_SALESINVOICE." OR A.type =".ST_ARINVCINSTLITM." OR A.type =".ST_SALESINVOICEREPO.") 
				AND A.payment_terms != 0 "
			.$sWhere."
			ORDER BY A.tran_date DESC limit $start, $limit";*/

	$sql = "SELECT A.*, D.*, C.memo_, E.description, B.id, B.invoice_ref_no, B.delivery_ref_no, B.invoice_date, B.orig_branch_code, B.invoice_type,
				B.installmentplcy_id, B.months_term, B.rebate, B.financing_rate, B.firstdue_date, B.maturity_date, B.outstanding_ar_amount, B.ar_amount,
				B.lcp_amount, B.downpayment_amount, B.amortization_amount, B.total_amount, B.category_id, B.payment_status, B.warranty_code, B.fsc_series,
				B.payment_location, B.co_maker, B.discount_downpayment, B.deferred_gross_profit, B.profit_margin, B.discount_downpayment2,
				Z.unrecovered_cost, Z.addon_amount, Z.total_unrecovered, Z.repo_date, Z.past_due, Z.over_due, Z.comments,
				NULL term_mod_date, NULL amort_diff, NULL months_paid, NULL amort_delay, NULL adj_rate, NULL opportunity_cost, NULL amount_to_be_paid, NULL termremarks
			FROM ".TB_PREF."debtor_trans A 
				INNER JOIN ".TB_PREF."debtor_loans B ON B.trans_no = A.trans_no AND B.debtor_no = A.debtor_no
				LEFT JOIN ".TB_PREF."comments C ON C.id = A.trans_no AND C.type = A.type
				INNER JOIN ".TB_PREF."debtors_master D ON D.debtor_no = A.debtor_no
				INNER JOIN ".TB_PREF."stock_category E ON E.category_id = B.category_id
				LEFT JOIN ".TB_PREF."repo_accounts Z ON Z.ar_trans_no = A.trans_no AND Z.ar_trans_type = A.type
			WHERE (A.type = ".ST_SALESINVOICE." OR A.type =".ST_ARINVCINSTLITM." OR A.type =".ST_SALESINVOICEREPO.") 
				AND A.payment_terms != 0 "
				.$sWhere."
                
			UNION ALL

			SELECT A.*, D.*, C.memo_, E.description, TM.id, TM.invoice_ref_no, TM.delivery_ref_no, TM.term_mod_date, B.orig_branch_code, B.invoice_type,
				TM.installmentplcy_id, TM.months_term, TM.rebate, TM.financing_rate, TM.firstdue_date, TM.maturity_date, TM.outstanding_ar_amount, TM.ar_amount,
				TM.lcp_amount, TM.downpayment_amount, TM.amortization_amount, B.total_amount, B.category_id, A.status, B.warranty_code, B.fsc_series,
				B.payment_location, B.co_maker, B.discount_downpayment, B.deferred_gross_profit, TM.profit_margin, B.discount_downpayment2,
				Z.unrecovered_cost, Z.addon_amount, Z.total_unrecovered, Z.repo_date, Z.past_due, Z.over_due, Z.comments,
				TM.term_mod_date, TM.amort_diff, TM.months_paid, TM.amort_delay, TM.adj_rate, TM.opportunity_cost, TM.amount_to_be_paid, TM.remarks termremarks
			FROM ".TB_PREF."debtor_trans A 
				INNER JOIN ".TB_PREF."debtor_term_modification TM ON TM.trans_no = A.trans_no AND TM.debtor_no = A.debtor_no
				INNER JOIN ".TB_PREF."debtor_loans B ON B.debtor_no = TM.debtor_no AND B.invoice_ref_no = TM.invoice_ref_no
				INNER JOIN ".TB_PREF."debtors_master D ON D.debtor_no = A.debtor_no
				LEFT JOIN ".TB_PREF."comments C ON C.id = A.trans_no AND C.type = A.type
				INNER JOIN ".TB_PREF."stock_category E ON E.category_id = TM.category_id
				LEFT JOIN ".TB_PREF."repo_accounts Z ON Z.ar_trans_no = A.trans_no AND Z.ar_trans_type = A.type
			WHERE (A.type = ".ST_SITERMMOD.")
				AND A.payment_terms != 0 "
				.$sWhere."
			ORDER BY tran_date DESC limit $start, $limit";
	
	return db_query($sql, "could not get all ar invoice");
}

function get_ar_installment_termmode($InvType='new', $start, $limit, $search, $showall=false)
{
	if(isset($InvType) && $InvType != 'x'){
		$sWhere .= " AND B.invoice_type = '".$InvType."' ";
	}
	if($search != ''){
		$sWhere .= " AND (D.name LIKE '%".$search."%' OR A.reference LIKE '%".$search."%') ";
	}
	$sql = "SELECT A.*, B.*, C.memo_,  D.*, E.description, Z.unrecovered_cost, Z.addon_amount, Z.total_unrecovered, 
				Z.repo_date, Z.past_due, Z.over_due, Z.comments
			FROM ".TB_PREF."debtor_trans A 
				INNER JOIN ".TB_PREF."debtor_loans B ON B.trans_no = A.trans_no AND B.debtor_no = A.debtor_no
				LEFT JOIN ".TB_PREF."comments C ON C.id = A.trans_no AND C.type = A.type
				INNER JOIN ".TB_PREF."debtors_master D ON D.debtor_no = A.debtor_no
				INNER JOIN ".TB_PREF."stock_category E ON E.category_id = B.category_id
				LEFT JOIN ".TB_PREF."debtor_term_modification F ON F.invoice_ref_no = A.reference AND F.debtor_no = A.debtor_no
				LEFT JOIN ".TB_PREF."repo_accounts Z ON Z.ar_trans_no = A.trans_no AND Z.ar_trans_type = A.type
			WHERE (A.type = ".ST_SALESINVOICE." OR A.type =".ST_ARINVCINSTLITM." OR A.type =".ST_SALESINVOICEREPO.") 
				AND A.payment_terms != 0 "
			.$sWhere."
			ORDER BY A.tran_date DESC limit $start, $limit";

	return db_query($sql, "could not get all ar invoice");
}

function get_AmortSched($trans_no, $trans_type, $debtor_no)
{
	$sql = "SELECT A.*
				FROM ".TB_PREF."debtor_loan_schedule A 
				WHERE A.trans_no = '".$trans_no."'
					AND A.trans_type = '".$trans_type."'
					AND A.debtor_no = '".$debtor_no."'
				ORDER BY A.month_no";

	return db_query($sql, "could not get all a/r schedule");
}

function get_loan_amortization_ledger($type, $trans_no)
{
	$sql = "SELECT A.id, A.debtor_no, A.trans_no, A.trans_type, A.month_no, A.date_due, A.principal_due,
			( 
				CASE WHEN C.reference IS NULL and B.date_paid IS NOT NULL
					THEN(SELECT X.reference 
							FROM debtor_trans X 
							WHERE X.trans_no = ".db_escape($trans_no)." 
							AND X.type = ".db_escape($type).")
    				ELSE C.reference
 				END
			)reference, 
			B.date_paid, B.payment_applied, B.rebate, B.penalty, B.id ledger_id
			FROM ".TB_PREF."debtor_loan_schedule A
				LEFT JOIN ".TB_PREF."debtor_loan_ledger B ON B.loansched_id = A.id
				LEFT JOIN ".TB_PREF."debtor_trans C ON C.trans_no = B.payment_trans_no AND C.type = B.trans_type_from
			WHERE A.trans_no = ".db_escape($trans_no)."
				AND A.trans_type = ".db_escape($type)."
			ORDER BY A.month_no ASC, B.date_paid ASC";

	return db_query($sql, "could not get amortization ledger because ");
}

function get_debtor_trans_all($type, $trans_no)
{
	$sql = "SELECT A.*
		FROM ".TB_PREF."debtor_trans A 
		WHERE A.trans_no = ".db_escape($trans_no)."
			AND A.type = ".db_escape($type);

	return db_query($sql, "The deptor trans all could not be retrieved");
}

function get_ar_ledger_entries($type, $trans_no)
{
	$sql = "SELECT A.trans_no, A.reference, B.type_no, B.type, B.tran_date, C.debtor_no, C.name, D.account_code, D.account_name, B.amount,
				CASE WHEN B.amount >= 0 THEN B.amount
					ELSE 0
				END AS debit ,
				CASE WHEN B.amount < 0 THEN B.amount
					ELSE 0
				END AS credit,
				CASE WHEN B.type = ".ST_RRREPO." THEN (SELECT Z.reference_no FROM ".TB_PREF."repo_accounts Z WHERE Z.id = B.type_no)
					ELSE (SELECT Z.reference FROM ".TB_PREF."debtor_trans Z WHERE Z.trans_no = B.type_no AND Z.type = B.type)
				END AS ref_no
			FROM ".TB_PREF."debtor_trans A
				INNER JOIN ".TB_PREF."gl_trans B ON B.person_id = A.debtor_no AND B.loan_trans_no = A.trans_no
				INNER JOIN ".TB_PREF."debtors_master C ON C.debtor_no = A.debtor_no
				INNER JOIN ".TB_PREF."chart_master D ON D.account_code = B.account
			WHERE A.trans_no = ".db_escape($trans_no)."
				AND A.type = ".db_escape($type)."
			ORDER BY B.tran_date ASC, B.counter ASC";

	return db_query($sql, "could not get a/r ledger entries because ");
}

function get_ar_item_detials($trans_no, $trans_type)
{
	$sql = "SELECT A.*
		FROM ".TB_PREF."debtor_trans_details A 
		WHERE A.debtor_trans_no = ".db_escape($trans_no)."
			AND A.debtor_trans_type = ".db_escape($trans_type);
	
	return db_query($sql, "could not get sales invoice item details");	
}