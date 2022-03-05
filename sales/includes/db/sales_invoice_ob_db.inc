<?php
/* Added by Ronelle 5/11/2021 */
function add_loan_schedule_ob(
	$trans_type,
	$trans_no,
	$debtor_no,
	$month_no,
	$date_due,
	$weekday,
	$principal_due,
	$principal_runbal,
	$total_principaldue,
	$total_runbal,
	$interest_due,
	$interest_runbal
) {
	$sql = "INSERT INTO " . TB_PREF . "debtor_loan_schedule (
					debtor_no, trans_no, trans_type, month_no, date_due, weekday, principal_due, principal_runbal, total_principaldue,
					total_runbal, interest_due, interest_runbal
			) VALUES (" . db_escape($debtor_no) . ", " . db_escape($trans_no) . ", " . db_escape($trans_type) . ", " . db_escape($month_no) . ",'" . date2sql($date_due) . "', 
				" . db_escape($weekday) . ", " . db_escape($principal_due) . ", " . db_escape($principal_runbal) . ", " . db_escape($total_principaldue) . ", 
				" . db_escape($total_runbal) . ", " . db_escape($interest_due) . ", " . db_escape($interest_runbal) . ")";

	$ErrMsg = _('The debtor loan schedule could not be written because ');
	db_query($sql, $ErrMsg);
}

function get_deptor_loan_schedule_ob($trans_no, $deptor_no, $type){
	
	$sql = "SELECT A.*, B.debtor_ref, B.name, C.reference
		FROM ".TB_PREF."debtor_loan_schedule A
        	INNER JOIN ".TB_PREF."debtors_master B ON B.debtor_no = A.debtor_no
            INNER JOIN ".TB_PREF."debtor_trans C ON C.trans_no = A.trans_no
		WHERE A.trans_no = ".db_escape($trans_no) ."
			AND A.debtor_no = ".db_escape($deptor_no) ."
			AND A.trans_type = ". $type ."
		GROUP BY id
		ORDER BY month_no ASC";

	return db_query($sql, "could not get loan schedule");
}
/* */

/* */