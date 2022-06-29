<?php

/**
 * Created by: spyrax10
 * Date Created: 25 Mar 2022
 */

function _bank_transactions($from_date = null, $cashier = '', $report = true, 
    $type = '', $trans_date = '') {

    set_global_connection();

	if ($report) {
		$from_date = date2sql($from_date);
	}

	$sql = "SELECT A.ref, A.trans_no, A.receipt_no, A.type, A.trans_date, A.pay_type, A.prepared_by, 
            A.amount AS amt, A.person_id, A.cashier_user_id, B.name, 
			C.memo_, D.real_name, D.user_id, A.person_type_id, A.masterfile,
			CASE 
				WHEN A.type = " . ST_CUSTPAYMENT . " THEN 'Office Collection Receipt:'
    			WHEN A.type = " . ST_BANKDEPOSIT . " THEN 'Receipt Entries:'
			END AS 'receipt_type'

			FROM ".TB_PREF."bank_trans A
				LEFT JOIN ".TB_PREF."debtors_master B ON B.debtor_no = A.person_id
				LEFT JOIN ".TB_PREF."comments C ON C.id = A.trans_no AND C.type = A.type
				LEFT JOIN ".TB_PREF."users D ON D.id = A.cashier_user_id
			WHERE opening_balance = 0";

    if ($report) {
        $sql .= " AND A.type IN (" . ST_BANKDEPOSIT . ", " . ST_CUSTPAYMENT . ")";
    }
    
	if ($cashier != '') {
		$sql .= " AND A.cashier_user_id = ".db_escape($cashier);
	}

	if ($report && $from_date != null) {
		$sql .= " AND A.trans_date = '$from_date'";
	}

    if ($type != '') {
        $sql .= " AND A.type = " .db_escape($type);
    }

    if ($trans_date != '') {
        $sql .= " AND A.trans_date = " .db_escape(date2sql($trans_date));
    }

    if ($report) {
        $sql .= " AND A.remit_stat <> 'Approved'";
    }
    else {
        $sql .= " AND A.remit_stat = 'Open'";
    }

	$sql .= " GROUP BY A.ref, A.type ORDER BY A.trans_date DESC, A.type";

    if ($report) {
        return db_query($sql,"No transactions were returned from bank transaction!");
    }
    else {
        return $sql;
    }
}

function get_remit_transactions($reference = '', $remit_from = '', $from_date = null, $to_date = null, $details = false) {

    set_global_connection();

    if ($details) {
        $sql = "SELECT RT.*
            FROM ".TB_PREF."remittance RT";
    }
    else {
        $sql = "SELECT RT.*, SUM(RT.amount) AS tot_amount
            FROM ".TB_PREF."remittance RT";
    }

    if ($details) {
        $sql .= " WHERE IFNULL(RT.remit_ref, '') <> ''";
    }
    else {
        if ($from_date != null || $to_date != null) {
            $sql .= " WHERE RT.remit_date >= '" . date2sql($from_date) . "' 
                AND RT.remit_date <= '" . date2sql($to_date) . "'";
        }
        else {
            $sql .= " WHERE IFNULL(RT.remit_ref, '') <> ''";
        }
    }

    if ($reference != '') {
        $sql .= " AND RT.remit_ref = ".db_escape($reference);
    }
    
    if ($remit_from != '') {
        $sql .= " AND RT.remit_from = " .db_escape($remit_from);
    }

    //$sql .= " AND RT.remit_to = " .db_escape($_SESSION['wa_current_user']->user);

    if ($details == false) {
        $sql .= " GROUP BY RT.remit_ref";
    }

    $sql .= " ORDER BY RT.type DESC";

    if ($details) {
        return db_query($sql, _("No remittance transactions were returned from the database."));
    }
    else {
        return $sql;
    }
}

function write_remit_transactions($reference = '', $date_, $user_id = 0, $remit_to = 0, $memo_ = '', 
    $type = '', $trans_date = '') {

    set_global_connection();

    global $Refs;

    $trans_no = max_remit_no() + 1;
    $result = db_query(_bank_transactions(null, $user_id, false, $type, $trans_date));
    $inserted = 0;

    while ($row = db_fetch($result)) {

        $void_entry = get_voided_entry($row['type'], $row['trans_no']);

        if ($void_entry['void_status'] != "Voided") {
            
            add_remit_entry(
                $trans_no,
                _('Draft'), 
                $user_id, 
                $remit_to, 
                date2sql($date_), 
                $reference,
                $row['ref'],
                $row['type'],
                $row['trans_date'],
                $row['amt'],
                $memo_,
                ''
            );
    
            remit_bank_trans(_('Draft'), $row['ref'], $user_id, $remit_to);
    
            $inserted++;
        }
    }

    if ($inserted > 0) {
        add_comments(ST_REMITTANCE, $trans_no, $date_, $memo_);
        $Refs->save(ST_REMITTANCE, $trans_no, $reference);
	    add_audit_trail(ST_REMITTANCE, $trans_no, $date_, _("Draft ref: " . $reference));
    }

    return $trans_no;
}

function add_remit_entry($remit_num = 0, $remit_stat = '', $remit_from = 0, $remit_to = 0, $remit_date = null,
$remit_ref = '', $from_ref = '', $from_type = '', $from_date = null, $from_amt = 0, $remit_memo = '', $stat_memo = '') {

    set_global_connection();

    $sql = "INSERT INTO ".TB_PREF."remittance 
        (remit_num, remit_stat, remit_from, remit_to, remit_date, remit_ref, from_ref, type, trans_date, amount, 
        remit_memo, status_memo)";
    
    $sql .= " VALUES(
        " . db_escape($remit_num) . ",
        " . db_escape($remit_stat) . ",
        " . db_escape($remit_from) . ",
        " . db_escape($remit_to) . ",
        " . db_escape($remit_date) . ",
        " . db_escape($remit_ref) . ",
        " . db_escape($from_ref) . ",
        " . db_escape($from_type) . ",
        " . db_escape($from_date) . ",
        " . db_escape($from_amt) . ",
        " . db_escape($remit_memo) . ",
        " . db_escape($stat_memo) . "
    )";

    db_query($sql, _("Couldn't add remittance entry!"));
}

function max_remit_no() {
    
    set_global_connection();
    
    $sql = "SELECT MAX(remit_num) AS max_no FROM ".TB_PREF."remittance";
    
    $result = db_query($sql, "Could not get max remittance number");
    $row = db_fetch($result);
    
    return $row[0];
}

function update_remit_trans($status = '', $reference = '', $remarks = '') {

    set_global_connection();

    $sql = "UPDATE ".TB_PREF."remittance RT 
        SET RT.remit_stat = '$status'";
    
    if ($remarks != '') {
        $sql .= ", status_memo = " .db_escape($remarks);
    }

    $sql .= " WHERE RT.remit_ref = " .db_escape($reference);
    
    db_query($sql, "Cannot update remit transaction!");
}

function remit_bank_trans($remit_stat = '', $reference = '', $user_id = 0, $remit_to = 0) {

    set_global_connection();

    $sql = "UPDATE ".TB_PREF."bank_trans BT 
        SET BT.remit_stat = '$remit_stat'";

    if ($remit_stat == "Approved") {
        $sql .= ", BT.cashier_user_id = $remit_to";
    }

    if ($remit_stat != '') {
        if ($remit_stat != "Approved") {
            $sql .= ", BT.remit_from = $remit_to";
        }
        else {
            $sql .= ", BT.remit_from = $user_id";
        }
    }
    
    $sql .= " WHERE BT.ref = " .db_escape($reference);
    
    db_query($sql, "Cannot update remit transaction!");
}

function remit_status($reference = '') {

    set_global_connection();

	$sql = "SELECT RT.remit_stat FROM " . TB_PREF . "remittance RT 
		WHERE RT.remit_ref =" . db_escape($reference);
	
	$sql .= " GROUP BY RT.remit_ref";

	$result = db_query($sql, "Cant get remit status! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}