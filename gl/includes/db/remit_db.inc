<?php

/**
 * Created by: spyrax10
 * Date Created: 25 Mar 2022
 */

function get_bank_transactions($from_date = null, $cashier = '', $report = true) {

	if ($report) {
		$from_date = date2sql($from_date);
	}

	$sql = "SELECT A.ref, A.trans_no, A.receipt_no, A.type, A.trans_date, A.pay_type, A.prepared_by, 
            abs(A.amount) AS amt, A.person_id, A.cashier_user_id, B.name, 
			C.memo_, D.real_name, D.user_id, A.person_type_id, A.masterfile,
			CASE 
				WHEN A.type = " . ST_CUSTPAYMENT . " THEN 'Office Collection Receipt'
    			WHEN A.type = " . ST_BANKDEPOSIT . " THEN 'Receipt Entries'
                WHEN A.type = " . ST_REMITTANCE . " THEN 'Remittance Entries'
			END AS 'receipt_type'

			FROM ".TB_PREF."bank_trans A
				LEFT JOIN ".TB_PREF."debtors_master B ON B.debtor_no = A.person_id
				LEFT JOIN ".TB_PREF."comments C ON C.id = A.trans_no AND C.type = A.type
				LEFT JOIN ".TB_PREF."users D ON D.real_name = A.cashier_user_id
			WHERE opening_balance = 0";

    if ($report) {
        $sql .= " AND A.type IN (" . ST_BANKDEPOSIT . ", " . ST_CUSTPAYMENT . ", " . ST_REMITTANCE . ")";
    }
    
	if ($cashier != '') {
		$sql .= " AND A.cashier_user_id = ".db_escape($cashier);
	}

	if ($report) {
		$sql .= " AND A.trans_date = '$from_date'";
	}
			
	$sql .= " GROUP BY A.ref, A.type ORDER BY A.trans_date DESC, A.type DESC";

    if ($report) {
        return db_query($sql,"No transactions were returned from bank transaction!");
    }
    else {
        return $sql;
    }
}

function write_remit_transactions($trans_type, $reference = '', $date_, $total_amount, $user_id = 0, $remit_to = 0, $memo_ = ''
) {

    global $Refs;

    $trans_no = get_next_trans_no($trans_type);

    add_bank_trans($trans_type, $trans_no,
		3, //COH
		$ref, $date_, $total_amount,
		PT_EMPLOYEE, $user_id,
		$remit_to, //Cashier
		"Cash",
        //For Cheque
        '0000-00-00', 0, null,
        //
        $remit_to, //masterfile
		0, //receeipt no
		get_current_user_fullname(), //Prepared by 
		null, //Checked by
		null, //Approved by
		null, //Module type
		'', // 19
		"Cannot insert a source bank transaction (remittance) ",// 20
		0 //Opening Balance
	);

    remit_bank_trans(1, $user_id, $remit_to);

    add_gl_trans(
        $trans_type, 
        $trans_no, 
        $date_, 
        1060, //Temporary Account
        0, 0, 
        $memo_, 
        $total_amount, 
        null, PT_EMPLOYEE, $user_id, "", 0, 
        '', '' //Masterfile
    );

    add_gl_trans(
        $trans_type, 
        $trans_no, 
        $date_, 
        1060, //Temporary Account
        0, 0, 
        $memo_, 
        -$total_amount, 
        null, PT_EMPLOYEE, $user_id, "", 0, 
        '', '' //Masterfile
    );

    add_comments($trans_type, $trans_no, $date_, $memo_);
	$Refs->save($trans_type, $trans_no, $reference);
	add_audit_trail($trans_type, $trans_no, $date_);

    return $trans_no;
}

function remit_bank_trans($remit_stat = 0, $user_id = 0, $remit_to = 0) {

    $sql = "UPDATE ".TB_PREF."bank_trans BT
        SET BT.cashier_user_id = $remit_to, BT.remit_stat = $remit_stat, BT.remit_from = $user_id";
    
    $sql .= " WHERE BT.cashier_user_id = " .db_escape($user_id);
    
    db_query($sql, "Cannot update remit transaction!");
}