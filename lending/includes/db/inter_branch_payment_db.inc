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
function interbranch_send_payment_add($target_branch, $debtor_ref, $name, $trans_date, $ref_no, $amount, $remarks, $prepared_by, $branch_code,
		$trans_no, $tran_type, $branch_to, $type=0)
{
	$db_coy = Get_db_coy($target_branch);
    set_global_connection($db_coy);

	$sql = "INSERT INTO ".TB_PREF."interbranch_trans (
					branch_code_from, debtor_ref, debtor_name, trans_date, ref_no, amount, remarks ,prepared_by,
					transno_from_branch, trantype_from_branch, branch_code_to, type
			) VALUES (
			        ".db_escape($branch_code).", ".db_escape($debtor_ref).", ".db_escape($name).", ".db_escape($trans_date)."
					,".db_escape($ref_no).", ".db_escape($amount)." ,".db_escape($remarks)." ,".db_escape($prepared_by)."
					,".db_escape($trans_no).", ".db_escape($tran_type).", ".db_escape($branch_to).", ".db_escape($type)."
			)";
	
	$ErrMsg = _('could not send data to other branch because ');
	db_query($sql, $ErrMsg);

	set_global_connection();

}

function update_status_interbranch_trans($id, $user, $status, $trans_no, $type_to_branch)
{
	$sql = "UPDATE ".TB_PREF."interbranch_trans 
			SET status = ".db_escape($status)."
				,approved_by = ".db_escape($user)."
				,transno_to_branch = ".db_escape($trans_no)."
				,trantype_to_branch = ".db_escape($type_to_branch)."
		WHERE id = ".db_escape($id);

	db_query($sql, "could not update table inter-branch because ");
}

function update_status_interbranch_trans_HO($ref_no, $user, $status, $trans_no, $type, $transNo_from, $transType_from)
{
	//update field to HO database for reference
	$db_coy = Get_db_coy("HO");
    set_global_connection($db_coy);

	$sql = "UPDATE ".TB_PREF."interbranch_trans 
			SET status = ".db_escape($status)."
				,approved_by = ".db_escape($user)."
				,transno_to_branch = ".db_escape($trans_no)."
				,trantype_to_branch = ".db_escape($type)."
		WHERE ref_no = ".db_escape($ref_no)."
			AND transno_from_branch = ".db_escape($transNo_from)."
			AND trantype_from_branch = ".db_escape($transType_from);

	db_query($sql, "could not update table inter-branch because ");
	set_global_connection();
}

function get_incoming_interb($status, $start, $limit, $branch=null, $query=null, $type=0)
{
	$sWhere = "";
	if(!isset($status)){
		$status = "draft";
	}
	if(!empty($branch) && $branch != "zHun"){
		$sWhere .= " AND A.branch_code_from = ".db_escape($branch);
	}
	if(!empty($query)){
		$sWhere .= " AND (A.branch_code_from LIKE '%".$query."%' 
							OR A.debtor_name LIKE '%".$query."%' 
							OR A.ref_no LIKE '%".$query."%')";
	}

	$sql = "SELECT A.*, B.debtor_no, B.debtor_ref, B.name
		FROM ".TB_PREF."interbranch_trans A 
				LEFT JOIN ".TB_PREF."debtors_master B 
					ON B.debtor_ref = A.debtor_ref
		WHERE A.status = ".db_escape($status). "
			AND type = ".$type.$sWhere;

	return db_query($sql, "could not get inter-branch payment");
}

function get_interB_view($type, $trans_no)
{
		$sql = "SELECT A.type, A.trans_no, A.person_id, A.bank_act, A.ref, A.trans_date, A.masterfile, A.amount,
					B.type_no, B.account, B.mcode, C.account_code, C.account_name
			FROM ".TB_PREF."bank_trans A 
					INNER JOIN ".TB_PREF."gl_trans B 
						ON B.type = A.type AND B.type_no = A.trans_no AND B.mcode = A.masterfile
					INNER JOIN chart_master C ON C.account_code = B.account
			WHERE A.trans_no = ".db_escape($trans_no)."
				AND A.type = ".db_escape($type);

	return db_query($sql, "could not get inter-branch item");
}

//allocate payment

function get_done_allocate($type, $module_type, $start, $limit)
{
	$sql = "SELECT A.*, B.debtor_ref, B.name, C.*, D.memo_
			FROM ".TB_PREF."debtor_trans A 
				INNER JOIN ".TB_PREF."debtors_master B ON B.debtor_no = A.debtor_no
				INNER JOIN ".TB_PREF."bank_trans C ON C.trans_no = A.trans_no AND C.type = A.type
				LEFT JOIN ".TB_PREF."comments D ON D.type = A.type AND D.id = A.trans_no
				LEFT JOIN ".TB_PREF."collection_types F ON F.collect_id = A.collect_id
			WHERE A.type = ".db_escape($type). "
				AND A.module_type LIKE '".$module_type."%'
			ORDER BY A.tran_date DESC, A.reference DESC";

	return db_query($sql, "could not get allocation payment");
}

function get_interB_customer()
{
	$sql = "SELECT A.debtor_no, A.name, A.debtor_ref, A.address, A.tax_id, B.branch_code_from, B.amount, B.id, B.trans_date
		FROM ".TB_PREF."debtors_master A
			LEFT JOIN ".TB_PREF."interbranch_trans B ON B.debtor_ref = A.debtor_ref
		WHERE B.status = 'draft'
		ORDER BY A.name ";

	return db_query($sql, "could not get Inter-branch customers");
}

function check_done_alloc_trans($trans_no, $module)
{
	$sql = "SELECT COUNT(*) FROM ".TB_PREF."debtor_trans A
			WHERE A.status = 'Close'
				AND A.trans_no = ".db_escape($trans_no)."
				AND A.module_type = ".db_escape($trans_no);
				
	$result = db_query($sql, "could not get debtor trans status");
	$count =  db_fetch($result);

	return $count[0];
}

function check_done_alloc_inq($trans_no)
{
	$sql = "SELECT COUNT(*) FROM ".TB_PREF."interbranch_trans A
			WHERE A.status = 'approved'
				AND A.id  = ".db_escape($trans_no);
				
	$result = db_query($sql, "could not get debtor trans status");
	$count =  db_fetch($result);

	return $count[0];
}

function get_interB_transNo_from($id)
{
	$sql = "SELECT A.ref_no, A.transno_from_branch, A.trantype_from_branch
	FROM ".TB_PREF."interbranch_trans A
	WHERE A.id =".db_escape($id);

	$result = db_query($sql, "could not get Inter-branch transNo from branch");
	return db_fetch($result);
}

function get_notfa_interb($branch=null, $query=null)
{
	$sWhere = "";
	if(!empty($branch) && $branch != "zHun"){
		$sWhere .= " AND A.branch_code_from = ".db_escape($branch);
	}
	if(!empty($query)){
		$sWhere .= " AND (A.branch_code_from LIKE '%".$query."%' 
							OR A.debtor_name LIKE '%".$query."%' 
							OR A.ref_no LIKE '%".$query."%')";
	}

	$sql = "SELECT A.*, B.debtor_no, B.debtor_ref, B.name, C.receipt_no
		FROM ".TB_PREF."interbranch_trans A 
				LEFT JOIN ".TB_PREF."debtors_master B ON B.debtor_ref = A.debtor_ref
				INNER JOIN ".TB_PREF."bank_trans C ON C.trans_no = A.transno_to_branch
		WHERE A.type = 3 ".$sWhere;

	return db_query($sql, "could not get inter-branch payment");
}

function interbranch_notfa_add($branch_code_from, $branch_code_to, $debtor_ref, $name, $trans_date, $ref_no, $amount, $remarks, $prepared_by,
		$status, $approved, $transno_to, $transno_type, $type=0)
{
	//set_global_connection();

	$sql = "INSERT INTO ".TB_PREF."interbranch_trans (
					branch_code_from, branch_code_to, debtor_ref, debtor_name, trans_date, ref_no, amount, remarks, prepared_by,
					status, approved_by, transno_to_branch, trantype_to_branch, type
			) VALUES (
			        ".db_escape($branch_code_from).", ".db_escape($branch_code_to).", ".db_escape($debtor_ref).", ".db_escape($name)."
					,".db_escape($trans_date).", ".db_escape($ref_no)." , ".db_escape($amount)." ,".db_escape($remarks)."
					,".db_escape($prepared_by).", ".db_escape($status).", ".db_escape($approved).", ".db_escape($transno_to)."
					, ".db_escape($transno_type).", ".db_escape($type)."
			)";
	
	$ErrMsg = _('Could not insert data because  ');
	db_query($sql, $ErrMsg);
}