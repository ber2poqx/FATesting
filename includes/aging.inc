<?php

/**
 * Created by: spyrax10
 * Date Created: Dec 11, 2021
 * Functions needed for aging report
 */

function ar_table_name($trans_type) {
    return $trans_type == ST_SITERMMOD || $trans_type == ST_RESTRUCTURED 
        ? "debtor_term_modification" : "debtor_loans";
}

function ar_invoice_date($trans_type) {
    return $trans_type == ST_SITERMMOD || $trans_type == ST_RESTRUCTURED  
        ? "term_mod_date" : "invoice_date";
}

function get_DL_details($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no) {

    $table_name = ar_table_name($trans_type);

    $sql = "SELECT * FROM ".TB_PREF."$table_name A 
        WHERE A.trans_no =".db_escape($trans_no) . 
        " AND A.debtor_no =".db_escape($debtor_no);

    $res = db_query($sql, "No details return for debtor loans");
    return db_fetch($res);
}

function get_max_year($trans_type) {

    $table_name = ar_table_name($trans_type);
    $inv_date = ar_invoice_date($trans_type);
    
    $sql = "SELECT MAX(YEAR(A.$inv_date)) AS maxYear
        FROM ".TB_PREF."$table_name A ";
    
    $res = db_query($sql, "Can't get max year DL invoice_date!");
    $row = db_fetch_row($res);
	return $row[0];
}

function get_DL_by_reference($reference, $trans_type = ST_SALESINVOICE) {

    $table_name = ar_table_name($trans_type);

    $sql = "SELECT * FROM ".TB_PREF."$table_name A 
        WHERE A.invoice_ref_no =".db_escape($reference);

    $res = db_query($sql, "No details return for debtor loans (reference)");
    return db_fetch($res);
}

function debtor_remaining_month($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no) {

    $sql = "SELECT COUNT(*) 
        FROM ".TB_PREF."debtor_loan_schedule A 
        WHERE A.month_no <> 0 AND status <> 'paid'
            AND A.debtor_no = ".db_escape($debtor_no) . 
            " AND A.trans_no = ".db_escape($trans_no) . 
            " AND A.trans_type = ".db_escape($trans_type);

    $res = db_query($sql, 'Cant get remaining month payment! ');
    $row = db_fetch_row($res);
    return $row[0];
}

function get_customer_loansched($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no) {

	set_global_connection();

	$sql = "SELECT DLS.*, DATE_FORMAT(DLS.date_due, '%M %Y') AS disp_date
		FROM " . TB_PREF . "debtor_loan_schedule DLS 
		WHERE DLS.month_no <> 0 AND DLS.trans_no = " .db_escape($trans_no) 
			. " AND DLS.debtor_no = " .db_escape($debtor_no) 
			. " AND DLS.trans_type = " .db_escape($trans_type);

	return db_query($sql, _("Cant get Customer's Loan Schedule!"));
}

function invoice_type_status($trans_no, $debtor_no, $end_date) {

    $sql = "SELECT 
                CASE 
                    WHEN DATE_FORMAT('$end_date', '%Y-%m') = DATE_FORMAT(REPO.repo_date, '%Y-%m') THEN 'repo'
                    WHEN DATE_FORMAT('$end_date', '%Y-%m') < DATE_FORMAT(REPO.repo_date, '%Y-%m') THEN 'new'
                    WHEN IFNULL(REPO.repo_date, '') = ''  THEN 'new'
                ELSE 'repo' END

            FROM ".TB_PREF."debtor_loans DL 
                LEFT JOIN (
                    SELECT A.ar_trans_no, A.debtor_no, A.repo_date
                    FROM ".TB_PREF."repo_accounts A
                ) REPO ON DL.trans_no = REPO.ar_trans_no AND DL.debtor_no = REPO.debtor_no

            WHERE DL.trans_no = ".db_escape($trans_no) . 
                " AND DL.debtor_no = ".db_escape($debtor_no);

    $res = db_query($sql, 'Cant get invoice_type_status!');
    $row = db_fetch_row($res);

    return $row[0] != null ? $row[0] : 'new';
}

function add_to_DLL($trans_no, $debtor_no, $type_from, $type_to, $date_paid, $invoice_no, $is_alloc = 0, 
	$loan_shed, $total = 0) {

	set_global_connection();

	$sql = "INSERT INTO ".TB_PREF."debtor_loan_ledger 
		(trans_no, debtor_no, trans_type_from, trans_type_to, payment_applied, date_paid, payment_trans_no, alloc, loansched_id)";
	
	$sql .= " VALUES (
		" . db_escape($trans_no) . ",
		" . db_escape($debtor_no) . ",
		" . db_escape($type_from) . ",
		" . db_escape($type_to) . ", 
		" . db_escape($total) . ", 
		" . db_escape($date_paid) . ",
		" . db_escape($invoice_no) . ", 
		" . db_escape($is_alloc) . ",
		" . db_escape($loan_shed) . "
	)";

	db_query($sql, _("Cant insert Customer's Loan Allocation!"));
}

function get_payment_for_loanID($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $loan_schedID) {

    $total = 0;
    $table_name = ar_table_name($trans_type);
    $inv_date = ar_invoice_date($trans_type);

    $sql = "SELECT SUM(A.payment_applied) 
        FROM ".TB_PREF."debtor_loan_ledger A ";

    $sql .= " WHERE A.debtor_no = ".db_escape($debtor_no) . 
        " AND A.trans_no = ".db_escape($trans_no) . 
        " AND A.trans_type_to = ".db_escape($trans_type) . 
        " AND A.loansched_id = ".db_escape($loan_schedID);

    $res = db_query($sql, 'Cant get total payment for given loan_ID! < ');
    $row = db_fetch_row($res);
    $total = $row[0] != null ? $row[0] : 0;

    return $total;
}

function update_DLS_status($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no,  
    $loan_schedID, $status = '') {

    $sql = "UPDATE ".TB_PREF."debtor_loan_schedule SET status = '$status'";

    $sql .= " WHERE debtor_no = ".db_escape($debtor_no) . 
        " AND trans_no = ".db_escape($trans_no) . 
        " AND trans_type = ".db_escape($trans_type) . 
        " AND id = ".db_escape($loan_schedID);

    db_query($sql, _("Can't update loan status!"));
}

function update_AR_amount($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $addted_ar = 0) {

    $table_name = ar_table_name($trans_type);
    $resRow = get_DL_details($trans_no, $trans_type, $debtor_no);
    $current_ar = $resRow['ar_amount'];
    $new_ar = $current_ar + $addted_ar;

    $sql = "UPDATE ".TB_PREF."$table_name SET ar_amount = " .db_escape($new_ar);

    $sql .= " WHERE debtor_no = ".db_escape($debtor_no) . 
        " AND trans_no = ".db_escape($trans_no);

    db_query($sql, _("Can't update DL AR Amount!"));

    $sql2 = "UPDATE ".TB_PREF."debtor_trans SET ov_amount = " .db_escape($new_ar);

    $sql2 .= " WHERE debtor_no = ".db_escape($debtor_no) . 
        " AND trans_no = ".db_escape($trans_no) . 
        " AND type = ".db_escape($trans_type);
    
    db_query($sql2, _("Can't update DT OV Amount!"));
}

function total_payment_for_month($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date, 
    $summarized = false, $trans_year = '', $last_year = false) {

    $total_payment = 0;
    $table_name = ar_table_name($trans_type);
    $inv_date = ar_invoice_date($trans_type);

    $sql = "SELECT SUM(A.payment_applied) 
        FROM ".TB_PREF."debtor_loan_ledger A 
            INNER JOIN ".TB_PREF."debtor_loan_schedule B ON A.loansched_id = B.id AND A.trans_no = B.trans_no 
                AND A.debtor_no = B.debtor_no AND A.trans_type_to = B.trans_type ";

    if ($summarized) {
        $sql .= " LEFT JOIN ".TB_PREF."$table_name X ON A.trans_no = X.trans_no AND A.debtor_no = X.debtor_no ";
    }

    $sql .= " WHERE B.month_no <> 0 AND A.debtor_no = ".db_escape($debtor_no) . 
        " AND A.trans_no = ".db_escape($trans_no) . 
        " AND A.trans_type_to = ".db_escape($trans_type);

    if ($summarized) {
        if ($last_year) {
            $sql .= " AND YEAR(X.$inv_date) <= $trans_year";
        }
        else {
            $sql .= " AND YEAR(X.$inv_date) = $trans_year";
        }
    }

    $sql .= " AND DATE_FORMAT(A.date_paid, '%Y-%m') = DATE_FORMAT('$end_date', '%Y-%m')";

    $res = db_query($sql, 'Cant get total payment for this month! < ');
    $row = db_fetch_row($res);
    $total_payment = $row[0];
    return $total_payment; 
}

function total_payment_this_month($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date, $include_cur_month = false, 
    $summarized = false, $trans_year = '', $last_year = false) {
    
    $total = 0;
    $table_name = ar_table_name($trans_type);
    $inv_date = ar_invoice_date($trans_type);

    $sql = "SELECT SUM(A.payment_applied) AS payment
        FROM ".TB_PREF."debtor_loan_ledger A
            INNER JOIN ".TB_PREF."debtor_loan_schedule B ON A.loansched_id = B.id AND A.trans_no = B.trans_no 
	            AND A.debtor_no = B.debtor_no AND A.trans_type_to = B.trans_type
                AND DATE_FORMAT(B.date_due, '%Y-%m') = DATE_FORMAT('$end_date', '%Y-%m') ";
        
    if ($summarized) {
        $sql .= " LEFT JOIN ".TB_PREF."$table_name X ON A.trans_no = X.trans_no AND A.debtor_no = X.debtor_no ";
    }

    $sql .=  "WHERE B.month_no <> 0 AND A.debtor_no = ".db_escape($debtor_no) . 
        " AND A.trans_no = ".db_escape($trans_no) . 
        " AND A.trans_type_to = ".db_escape($trans_type);
    
    if ($summarized) {
        if ($last_year) {
            $sql .= " AND YEAR(X.$inv_date) <= $trans_year";
        }
        else {
            $sql .= " AND YEAR(X.$inv_date) = $trans_year";
        }
    }
    
    if ($include_cur_month) {
        $sql .= " AND DATE_FORMAT(A.date_paid, '%Y-%m') <= DATE_FORMAT('$end_date', '%Y-%m') ";
    }
    else {
        $sql .= " AND DATE_FORMAT(A.date_paid, '%Y-%m') < DATE_FORMAT('$end_date', '%Y-%m') ";
    }

    $res = db_query($sql, 'Cant get total payment for nxt month!');
    $row = db_fetch_row($res);
	$total = $row[0];
    return $total != null ? $total : 0;
}

function payment_this_month($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date, 
    $summarized = false, $trans_year = '', $last_year = false) {
    
    $total = 0;
    $resRow = get_DL_details($trans_no, $trans_type, $debtor_no);
    $table_name = ar_table_name($trans_type);
    $inv_date = ar_invoice_date($trans_type);

    $new_end_date = new DateTime(sql2date($end_date));
    $new_maturity_date = new DateTime(sql2date($resRow['maturity_date']));

    $sql = "SELECT SUM(A.payment_applied) AS payment
        FROM ".TB_PREF."debtor_loan_ledger A
            INNER JOIN ".TB_PREF."debtor_loan_schedule B ON A.loansched_id = B.id AND A.trans_no = B.trans_no 
	            AND A.debtor_no = B.debtor_no AND A.trans_type_to = B.trans_type
                AND DATE_FORMAT(B.date_due, '%Y-%m') <= DATE_FORMAT('$end_date', '%Y-%m') ";
    
    if ($summarized) {
        $sql .= " LEFT JOIN ".TB_PREF."$table_name X ON A.trans_no = X.trans_no AND A.debtor_no = X.debtor_no ";
    } 
    
    $sql .= " WHERE B.month_no <> 0 AND A.debtor_no = ".db_escape($debtor_no) . 
        " AND A.trans_no = ".db_escape($trans_no) . 
        " AND A.trans_type_to = ".db_escape($trans_type);

    if ($summarized) {
        if ($last_year) {
            $sql .= " AND YEAR(X.$inv_date) <= $trans_year";
        }
        else {
            $sql .= " AND YEAR(X.$inv_date) = $trans_year";
        }
    }
    
    $sql .= " AND DATE_FORMAT(A.date_paid, '%Y-%m') = DATE_FORMAT('$end_date', '%Y-%m') ";

    $res = db_query($sql, 'Cant get total payment for nxt month!');
    $row = db_fetch_row($res);
	
    if (get_month_year($end_date) == get_month_year($resRow['maturity_date'])) {
        $total = $row[0];
    }
    else if ($new_maturity_date < $new_end_date) {
        $total = total_payment_for_month($trans_no, $trans_type, $debtor_no, $end_date, $summarized, $trans_year, $last_year);
    }   
    else {

        $total = $row[0];
    }
    return $total - total_adjusment($trans_no, $trans_type, $debtor_no, $end_date);
}

function advance_payment($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date, 
    $summarized = false, $trans_year = '', $last_year = false) {

    $total = 0;
    $table_name = ar_table_name($trans_type);
    $inv_date = ar_invoice_date($trans_type);

    $sql = "SELECT SUM(A.payment_applied) AS payment
        FROM ".TB_PREF."debtor_loan_ledger A
            INNER JOIN ".TB_PREF."debtor_loan_schedule B ON A.loansched_id = B.id AND A.trans_no = B.trans_no 
	            AND A.debtor_no = B.debtor_no AND A.trans_type_to = B.trans_type
                AND DATE_FORMAT(B.date_due, '%Y-%m') >= DATE_FORMAT(DATE_ADD('$end_date', INTERVAL +1 MONTH), '%Y-%m') ";
    
    if ($summarized) {
        $sql .= " LEFT JOIN ".TB_PREF."$table_name X ON A.trans_no = X.trans_no AND A.debtor_no = X.debtor_no ";
    }

    $sql .= " WHERE B.month_no <> 0 AND A.debtor_no = ".db_escape($debtor_no) . 
        " AND A.trans_no = ".db_escape($trans_no) .
        " AND A.trans_type_to = ".db_escape($trans_type);

    if ($summarized) {
        if ($last_year) {
            $sql .= " AND YEAR(X.$inv_date) <= $trans_year";
        }
        else {
            $sql .= " AND YEAR(X.$inv_date) = $trans_year";
        }
    }

    $sql .= " AND DATE_FORMAT(A.date_paid, '%Y-%m') = DATE_FORMAT('$end_date', '%Y-%m')";
    
    $res = db_query($sql, 'Cant get advance payment!');
    $row = db_fetch_row($res);
	$total = $row[0];

    return $total != null ? $total : 0;
}

function total_payment_for_nxt_month($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date, 
    $summarized = false, $trans_year = '', $last_year = false) {

    $total = 0;
    $table_name = ar_table_name($trans_type);
    $inv_date = ar_invoice_date($trans_type);

    $sql = "SELECT SUM(A.payment_applied) AS payment
        FROM ".TB_PREF."debtor_loan_ledger A
            INNER JOIN ".TB_PREF."debtor_loan_schedule B ON A.loansched_id = B.id AND A.trans_no = B.trans_no 
	            AND A.debtor_no = B.debtor_no AND A.trans_type_to = B.trans_type
                AND DATE_FORMAT(B.date_due, '%Y-%m') = DATE_FORMAT(DATE_ADD('$end_date', INTERVAL +1 MONTH), '%Y-%m') ";
        
    if ($summarized) {
        $sql .= " LEFT JOIN ".TB_PREF."$table_name X ON A.trans_no = X.trans_no AND A.debtor_no = X.debtor_no ";
    }

    $sql .= " WHERE B.month_no <> 0 AND A.debtor_no = ".db_escape($debtor_no) . 
        " AND A.trans_no = ".db_escape($trans_no) .
        " AND A.trans_type_to = ".db_escape($trans_type);

    if ($summarized) {
        if ($last_year) {
            $sql .= " AND YEAR(X.$inv_date) <= $trans_year";
        }
        else {
            $sql .= " AND YEAR(X.$inv_date) = $trans_year";
        }
    }
    
    $sql .= " AND DATE_FORMAT(A.date_paid, '%Y-%m') < DATE_FORMAT('$end_date', '%Y-%m')";

    $res = db_query($sql, 'Cant get total payment for nxt month!');
    $row = db_fetch_row($res);
	$total = $row[0];

    return $total != null ? $total : 0;
}

function last_month_payment($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date, $include_cur_month = false, 
    $summarized = false, $trans_year = '', $last_year = false) {

    $total = 0;
    $table_name = ar_table_name($trans_type);
    $inv_date = ar_invoice_date($trans_type);

    $sql = "SELECT SUM(A.payment_applied) AS payment
        FROM ".TB_PREF."debtor_loan_ledger A
            INNER JOIN ".TB_PREF."debtor_loan_schedule B ON A.loansched_id = B.id AND A.trans_no = B.trans_no 
	            AND A.debtor_no = B.debtor_no AND A.trans_type_to = B.trans_type
                AND DATE_FORMAT(B.date_due, '%Y-%m') = DATE_FORMAT(DATE_ADD('$end_date', INTERVAL -1 MONTH), '%Y-%m') ";

    if ($summarized) {
        $sql .= " LEFT JOIN ".TB_PREF."$table_name X ON A.trans_no = X.trans_no AND A.debtor_no = X.debtor_no ";
    }

    $sql .= " WHERE B.month_no <> 0 AND A.debtor_no = ".db_escape($debtor_no) . 
        " AND A.trans_no = ".db_escape($trans_no) .
        " AND A.trans_type_to = ".db_escape($trans_type);
    
    if ($summarized) {
        if ($last_year) {
            $sql .= " AND YEAR(X.$inv_date) <= $trans_year";
        }
        else {
            $sql .= " AND YEAR(X.$inv_date) = $trans_year";
        }
    }

    if ($include_cur_month) {
        $sql .= " AND DATE_FORMAT(A.date_paid, '%Y-%m') <= DATE_FORMAT('$end_date', '%Y-%m')";
    }
    else {
        $sql .= " AND DATE_FORMAT(A.date_paid, '%Y-%m') <= DATE_FORMAT(DATE_ADD('$end_date', INTERVAL -1 MONTH), '%Y-%m')";
    }

    $res = db_query($sql, 'Cant get last month payment!');
    $row = db_fetch_row($res);
	$total = $row[0];

    return $total != null ? $total : 0;
}

function last_2months_payment($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date, 
    $summarized = false, $trans_year = '', $last_year = false) {
    
    $total = 0;
    $table_name = ar_table_name($trans_type);
    $inv_date = ar_invoice_date($trans_type);

    $sql = "SELECT SUM(A.payment_applied) AS payment
        FROM ".TB_PREF."debtor_loan_ledger A
            INNER JOIN ".TB_PREF."debtor_loan_schedule B ON A.loansched_id = B.id AND A.trans_no = B.trans_no 
	            AND A.debtor_no = B.debtor_no AND A.trans_type_to = B.trans_type
                AND DATE_FORMAT(B.date_due, '%Y-%m') <= DATE_FORMAT(DATE_ADD('$end_date', INTERVAL -2 MONTH), '%Y-%m') ";

    if ($summarized) {
        $sql .= " LEFT JOIN ".TB_PREF."$table_name X ON A.trans_no = X.trans_no AND A.debtor_no = X.debtor_no ";
    }

    $sql .= " WHERE B.month_no <> 0 AND A.debtor_no = ".db_escape($debtor_no) . 
        " AND A.trans_no = ".db_escape($trans_no) .
        " AND A.trans_type_to = ".db_escape($trans_type);
    
    if ($summarized) {
        if ($last_year) {
            $sql .= " AND YEAR(X.$inv_date) <= $trans_year";
        }
        else {
            $sql .= " AND YEAR(X.$inv_date) = $trans_year";
        }
    }

    $sql .= " AND DATE_FORMAT(A.date_paid, '%Y-%m') <= DATE_FORMAT(DATE_ADD('$end_date', INTERVAL -1 MONTH), '%Y-%m') ";

    $res = db_query($sql, 'Cant get last 2 months payment!');
    $row = db_fetch_row($res);
	$total = $row[0] + total_adjusment($trans_no, $trans_type, $debtor_no, $end_date);
    return $total != null ? $total : 0;
}

function total_current_payment($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date, 
    $summarized = false, $trans_year = '', $last_year = false) {
    
    $total = 0;
    $table_name = ar_table_name($trans_type);
    $inv_date = ar_invoice_date($trans_type);

    $sql = "SELECT SUM(DLL.payment_applied) AS sum_pay 
        FROM ".TB_PREF."debtor_loan_ledger DLL ";

    if ($summarized) {
        $sql .= " LEFT JOIN ".TB_PREF."$table_name A ON DLL.trans_no = A.trans_no AND DLL.debtor_no = A.debtor_no ";
    }

    $sql .= " WHERE DLL.debtor_no = ".db_escape($debtor_no) . 
        " AND DLL.trans_no = ".db_escape($trans_no) . 
        " AND DLL.trans_type_to = ".db_escape($trans_type);
     
    if ($summarized) {
        if ($last_year) {
            $sql .= " AND YEAR(A.$inv_date) <= $trans_year";
        }
        else {
            $sql .= " AND YEAR(A.$inv_date) = $trans_year";
        }
    }

    $sql .= " AND DATE_FORMAT(DLL.date_paid, '%Y-%m') <= DATE_FORMAT('$end_date', '%Y-%m')";

    $res = db_query($sql, 'Cant get total_current_payment!');
    $row = db_fetch_row($res);
    $total = $row[0];

	return $total != null ? $total : 0;
}

function total_payment($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date, 
    $summarized = false, $trans_year = '', $last_year = false) {
   
    $total = 0;
    $table_name = ar_table_name($trans_type);
    $inv_date = ar_invoice_date($trans_type);

    $sql = "SELECT SUM(DLL.payment_applied) AS sum_pay 
        FROM ".TB_PREF."debtor_loan_ledger DLL ";
        
    if ($summarized) {
        $sql .= " LEFT JOIN ".TB_PREF."$table_name A ON DLL.trans_no = A.trans_no AND DLL.debtor_no = A.debtor_no ";
    }

    $sql .= " WHERE DLL.debtor_no = ".db_escape($debtor_no) . 
        " AND DLL.trans_no = ".db_escape($trans_no) . 
        " AND DLL.trans_type_to = ".db_escape($trans_type);
    
    if ($summarized) {
        if ($last_year) {
            $sql .= " AND YEAR(A.$inv_date) <= $trans_year";
        }
        else {
            $sql .= " AND YEAR(A.$inv_date) = $trans_year";
        }
    }
    
    $sql .= " AND DATE_FORMAT(DLL.date_paid, '%Y-%m') < DATE_FORMAT('$end_date', '%Y-%m') ";
    
    $res = db_query($sql, 'Cant get total payment!');
    $row = db_fetch_row($res);
    $total = $row[0];
	return $total;
}

function current_balance($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date, 
    $summarized = false, $trans_year = '', $last_year = false) {

    $total = 0;

    $row = get_DL_details($trans_no, $trans_type, $debtor_no);

    $total = $row['ar_amount'] 
    - total_current_payment($trans_no, $trans_type, $debtor_no, $end_date, $summarized, $trans_year, $last_year);

    return $total;
}

function current_balance_display($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date) {

    $total = 0;

    $row = get_DL_details($trans_no, $trans_type, $debtor_no);

    if (invoice_type_status($trans_no, $debtor_no, $end_date) == 'new' ) {
        $total = $row['ar_amount'] - total_current_payment($trans_no, $trans_type, $debtor_no, $end_date);
    }
    else {
        $total = 0;
    }

    return $total;
}

function amort_this_month($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date, 
    $summarized = false, $trans_year = '', $last_year = false) {
    
    $total = 0;
    $table_name = ar_table_name($trans_type);
    $inv_date = ar_invoice_date($trans_type);

    $sql = "SELECT A.principal_due 
		FROM ".TB_PREF."debtor_loan_schedule A ";

    if ($summarized) {
        $sql .= " LEFT JOIN ".TB_PREF."$table_name X ON A.trans_no = X.trans_no AND A.debtor_no = X.debtor_no ";
    }
    
	$sql .= " WHERE A.month_no <> 0 AND A.debtor_no =".db_escape($debtor_no) . 
        " AND A.trans_no =".db_escape($trans_no) .
        " AND A.trans_type =".db_escape($trans_type);

    if ($summarized) {
        if ($last_year) {
            $sql .= " AND YEAR(X.$inv_date) <= $trans_year";
        }
        else {
            $sql .= " AND YEAR(X.$inv_date) = $trans_year";
        }
    }
    
    $sql .= " AND DATE_FORMAT(A.date_due, '%Y-%m') = DATE_FORMAT('$end_date', '%Y-%m') ";

    $res = db_query($sql, 'Cant get amort_this_month!');
    $row = db_fetch_row($res);
    $total =  $row[0];

    return $total != null ? $total : 0;
}

function amort_nxt_month($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date, 
    $summarized = false, $trans_year = '', $last_year = false) {

    $total = 0;
    $table_name = ar_table_name($trans_type);
    $inv_date = ar_invoice_date($trans_type);

    $sql = "SELECT A.principal_due 
		FROM ".TB_PREF."debtor_loan_schedule A ";

    if ($summarized) {
        $sql .= " LEFT JOIN ".TB_PREF."$table_name X ON A.trans_no = X.trans_no AND A.debtor_no = X.debtor_no ";
    }

    $sql .= " WHERE A.month_no <> 0 AND A.debtor_no =".db_escape($debtor_no) . 
        " AND A.trans_no =".db_escape($trans_no) .
        " AND A.trans_type =".db_escape($trans_type);

    if ($summarized) {
        if ($last_year) {
            $sql .= " AND YEAR(X.$inv_date) <= $trans_year";
        }
        else {
            $sql .= " AND YEAR(X.$inv_date) = $trans_year";
        }
    }

    $sql .= " AND DATE_FORMAT(A.date_due, '%Y-%m') = DATE_FORMAT(DATE_ADD('$end_date', INTERVAL +1 MONTH), '%Y-%m') ";

    $res = db_query($sql, 'Cant get amort_nxt_month!');
    $row = db_fetch_row($res);
    $total =  $row[0];

    return $total != null ? $total : 0;
}

function due_this_month($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date, 
    $summarized = false, $trans_year = '', $last_year = false) {
    
    $total = 0;
    $resRow = get_DL_details($trans_no, $trans_type, $debtor_no);
    
    if ($trans_type == ST_SITERMMOD) {
        $ct_date = $resRow['term_mod_date'];

        if (get_month_year($end_date) == get_month_year($ct_date)) {
            $include_cur_month = true;
        }
        else {
            $include_cur_month = false;
        }
    }
    else {
        $include_cur_month = false;
    }
   
    $new_end_date = new DateTime(sql2date($end_date));
    $new_maturity_date = new DateTime(sql2date($resRow['maturity_date']));

    if (get_month_year($end_date) == get_month_year($resRow['maturity_date'])) {
        $total = amort_this_month($trans_no, $trans_type, $debtor_no, $end_date, $summarized, $trans_year, $last_year) 
        - total_payment_this_month($trans_no, $trans_type, $debtor_no, $end_date, $include_cur_month, $summarized, $trans_year, $last_year);
    }
    else if ($new_maturity_date > $new_end_date) {
        $total = amort_this_month($trans_no, $trans_type, $debtor_no, $end_date, $summarized, $trans_year, $last_year) 
        - total_payment_this_month($trans_no, $trans_type, $debtor_no, $end_date, $include_cur_month, $summarized, $trans_year, $last_year);
    }
    else {
        $total = 0;
    }

	return $total < 0 ? 0 : $total;
}

function due_nxt_month($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date, 
    $summarized = false, $trans_year = '', $last_year = false) {

    $total = 0;

    $total = amort_nxt_month($trans_no, $trans_type, $debtor_no, $end_date, $summarized, $trans_year, $last_year) 
    - total_payment_for_nxt_month($trans_no, $trans_type, $debtor_no, $end_date, $summarized, $trans_year, $last_year);
	
    return $total < 0 ? 0 : $total;
}

function due_last_month($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date, 
    $summarized = false, $trans_year = '', $last_year = false) {

    $total = 0;
    $table_name = ar_table_name($trans_type);
    $inv_date = ar_invoice_date($trans_type);

    $sql = "SELECT A.principal_due 
		FROM ".TB_PREF."debtor_loan_schedule A ";
		
    if ($summarized) {
        $sql .= " LEFT JOIN ".TB_PREF."$table_name X ON A.trans_no = X.trans_no AND A.debtor_no = X.debtor_no ";
    }

    $sql .= " WHERE A.month_no <> 0 AND A.debtor_no =".db_escape($debtor_no) . 
        " AND A.trans_no =".db_escape($trans_no) . 
        " AND A.trans_type =".db_escape($trans_type);

    if ($summarized) {
        if ($last_year) {
            $sql .= " AND YEAR(X.$inv_date) <= $trans_year";
        }
        else {
            $sql .= " AND YEAR(X.$inv_date) = $trans_year";
        }
    }

    $sql .= " AND DATE_FORMAT(A.date_due, '%Y-%m') = DATE_FORMAT(DATE_ADD('$end_date', INTERVAL -1 MONTH), '%Y-%m') ";

    $res = db_query($sql, 'Cant get due last month!');
    $row = db_fetch_row($res);
    $total = $row[0];
	return $total != null ? $total : 0;
}

function due_last_2months($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date, 
    $summarized = false, $trans_year = '', $last_year = false) {
    
    $total = 0;
    $table_name = ar_table_name($trans_type);
    $inv_date = ar_invoice_date($trans_type);

    $sql = "SELECT SUM(A.principal_due) AS total 
		FROM ".TB_PREF."debtor_loan_schedule A ";
	
    if ($summarized) {
        $sql .= " LEFT JOIN ".TB_PREF."$table_name X ON A.trans_no = X.trans_no AND A.debtor_no = X.debtor_no ";
    }

    $sql .= " WHERE A.month_no <> 0 AND A.debtor_no =".db_escape($debtor_no) . 
        " AND A.trans_no =".db_escape($trans_no) . 
        " AND A.trans_type =".db_escape($trans_type);

    if ($summarized) {
        if ($last_year) {
            $sql .= " AND YEAR(X.$inv_date) <= $trans_year";
        }
        else {
            $sql .= " AND YEAR(X.$inv_date) = $trans_year";
        }
    }

    if ($trans_type == ST_SITERMMOD) {

        $resRow = get_DL_details($trans_no, $trans_type, $debtor_no);
        $ct_firstdue = $resRow['firstdue_date'];
        $new_end_date = endCycle($end_date, -2);
        
        if (get_month_year($ct_firstdue) >= get_month_year($new_end_date)) {
            $sql .= " AND DATE_FORMAT(A.date_due, '%Y-%m') <= DATE_FORMAT(DATE_ADD('$end_date', INTERVAL -2 MONTH), '%Y-%m') ";
        }
        else {
            $sql .= " AND DATE_FORMAT(A.date_due, '%Y-%m') < DATE_FORMAT('$new_end_date', '%Y-%m') ";
        }
    }
    else {
        $sql .= " AND DATE_FORMAT(A.date_due, '%Y-%m') <= DATE_FORMAT(DATE_ADD('$end_date', INTERVAL -2 MONTH), '%Y-%m') ";
    }

    $res = db_query($sql, 'Cant get due last 2 months!');
    $row = db_fetch_row($res);
    $total = $row[0];
	return $total;
}

function debtor_last_month_balance($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date, $include_cur_month = false, 
    $summarized = false, $trans_year = '', $last_year = false) {
    
    $total = 0;

    $total = due_last_month($trans_no, $trans_type, $debtor_no, $end_date, $summarized, $trans_year, $last_year) 
    - last_month_payment($trans_no, $trans_type, $debtor_no, $end_date, $trans_type == ST_SITERMMOD ? true : $include_cur_month, 
        $summarized, $trans_year, $last_year
    );

    return $total > 0 ? $total : 0;
}

function overdue_1month($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date, 
    $summarized = false, $trans_year = '', $last_year = false) {

    $total = 0;
    $resRow = get_DL_details($trans_no, $trans_type, $debtor_no);

    $new_end_date = new DateTime(sql2date($end_date));
    $new_maturity_date = new DateTime(sql2date($resRow['maturity_date']));

    if (get_month_year($end_date) == get_month_year($resRow['maturity_date'])) {
        $total = debtor_last_month_balance($trans_no, $trans_type, $debtor_no, $end_date, false, $summarized, $trans_year, $last_year);
    }
    else if ($new_maturity_date > $new_end_date) {
        $total = debtor_last_month_balance($trans_no, $trans_type, $debtor_no, $end_date, false, $summarized, $trans_year, $last_year);
    }
    else {
        $total = 0;
    }
   
    return $total;
}

function overdue_2months($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date, 
    $summarized = false, $trans_year = '', $last_year = false) {

    $total = 0;
    $resRow = get_DL_details($trans_no, $trans_type, $debtor_no);

    $new_end_date = new DateTime(sql2date($end_date));
    $new_maturity_date = new DateTime(sql2date($resRow['maturity_date']));

    if (get_month_year($end_date) == get_month_year($resRow['maturity_date'])) {
        $total = due_last_2months($trans_no, $trans_type, $debtor_no, $end_date, $summarized, $trans_year, $last_year) 
        - last_2months_payment($trans_no, $trans_type, $debtor_no, $end_date, $summarized, $trans_year, $last_year);
    }
    else if ($new_maturity_date > $new_end_date) {
        $total = due_last_2months($trans_no, $trans_type, $debtor_no, $end_date, $summarized, $trans_year, $last_year) 
        - last_2months_payment($trans_no, $trans_type, $debtor_no, $end_date, $summarized, $trans_year, $last_year);
    }
    else {
        $total = 0;
    }
    return $total > 0 ? $total : 0;
}

function not_yet_due($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date,
    $summarized = false, $trans_year = '', $last_year = false) {
    
    $total = 0;
    $resRow = get_DL_details($trans_no, $trans_type, $debtor_no);

    $new_end_date = new DateTime(sql2date($end_date));
    $new_maturity_date = new DateTime(sql2date($resRow['maturity_date']));
    
    if ($new_maturity_date < $new_end_date) {
        $total = 0;
    }
    else {
        $total = (current_balance($trans_no, $trans_type, $debtor_no, $end_date, $summarized, $trans_year, $last_year) +
            payment_this_month($trans_no, $trans_type, $debtor_no, $end_date, $summarized, $trans_year, $last_year) + 
            advance_payment($trans_no, $trans_type, $debtor_no, $end_date, $summarized, $trans_year, $last_year)) -
            due_nxt_month($trans_no, $trans_type, $debtor_no, $end_date, $summarized, $trans_year, $last_year) - 
            due_this_month($trans_no, $trans_type, $debtor_no, $end_date, $summarized, $trans_year, $last_year) - 
            overdue_1month($trans_no, $trans_type, $debtor_no, $end_date, $summarized, $trans_year, $last_year) - 
            overdue_2months($trans_no, $trans_type, $debtor_no, $end_date, $summarized, $trans_year, $last_year);
    }

    return $total;
}

function past_due($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date, 
    $summarized = false, $trans_year = '', $last_year = false) {

    $total = 0;
    $resRow = get_DL_details($trans_no, $trans_type, $debtor_no);

    $new_end_date = new DateTime(sql2date($end_date));
    $new_maturity_date = new DateTime(sql2date($resRow['maturity_date']));

    if (get_month_year($end_date) == get_month_year($resRow['maturity_date'])) {
        $total = 0;
    }
    else if ($new_maturity_date < $new_end_date) {
        $total = $resRow['ar_amount'] - total_payment($trans_no, $trans_type, $debtor_no, $end_date, $summarized, $trans_year, $last_year);
    }
    else {
        $total = 0;
    }

    return $total;
}

function total_penalty($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date) {

    $total = 0;
    $resRow = get_DL_details($trans_no, $trans_type, $debtor_no);

    $new_end_date = new DateTime(sql2date($end_date));
    $new_maturity_date = new DateTime(sql2date($resRow['maturity_date']));

    $overdue_1month = overdue_1month($trans_no, $trans_type, $debtor_no, $end_date);
    $overdue_2month = overdue_2months($trans_no, $trans_type, $debtor_no, $end_date);
    $past_due = past_due($trans_no, $trans_type, $debtor_no, $end_date);

    $penalty_rate = getCompDet('penalty_rate', user_company());

    if (get_month_year($end_date) == get_month_year($resRow['maturity_date'])) {
        $total = 0;
    }
    else if ($new_maturity_date < $new_end_date) {
        $total = $past_due * $penalty_rate;
    }
    else {
        $total = ($overdue_1month * $penalty_rate) + ($overdue_2month * $penalty_rate);
    }

    return $total;
}

function total_collectibles($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date) {

    $total = 0;

    $total = due_this_month($trans_no, $trans_type, $debtor_no, $end_date) + 
        overdue_1month($trans_no, $trans_type, $debtor_no, $end_date) + 
        overdue_2months($trans_no, $trans_type, $debtor_no, $end_date) + 
        past_due($trans_no, $trans_type, $debtor_no, $end_date);

    return $total;
}

function total_adjusment($trans_no, $trans_type = ST_SALESINVOICE, $debtor_no, $end_date) {
    
    $total = 0;

    if ($trans_type == ST_SITERMMOD) {

        $resRow = get_DL_details($trans_no, $trans_type, $debtor_no);

        $ct_date = $resRow['term_mod_date'];
        $new_end_date = new DateTime(sql2date($end_date));

        if (get_month_year($end_date) == get_month_year($ct_date)) {

            $sql = "SELECT SUM(A.payment_applied) AS total 
                FROM ".TB_PREF."debtor_loan_ledger A 
                    INNER JOIN ".TB_PREF."debtor_loan_schedule B ON A.loansched_id = B.id 
                        AND A.trans_no = B.trans_no 
                        AND A.debtor_no = B.debtor_no 
                        AND A.trans_type_to = B.trans_type";

            $sql .= " WHERE B.month_no <> 0 AND A.alloc = 1 AND A.debtor_no = ".db_escape($debtor_no) . 
                " AND A.trans_no = ".db_escape($trans_no) . 
                " AND A.trans_type_to = ".db_escape($trans_type);

            $sql .= " AND DATE_FORMAT(A.date_paid, '%Y-%m') = DATE_FORMAT('$end_date', '%Y-%m') ";

            $res = db_query($sql, 'Cant get total_allocation!');
            $row = db_fetch_row($res);
            $total = $row[0];
        }
        else {
            $total = 0;
        }

    }
    else {
        $total = 0;
    }

    return $total;
}

//------------------------------------------------------------------------//
#SUMMARIZED
//------------------------------------------------------------------------//

function sum_not_yet_due($trans_no_arr, $trans_type_arr, $debtor_no_arr, $trans_year, $end_date, $last_year = false) {
    
    $total = 0;

    foreach ($trans_no_arr as $key => $val) {
        $trans_no = $val;
        $trans_type = $trans_type_arr[$trans_no];
        $debtor_no = $debtor_no_arr[$trans_no];
        
        $total += not_yet_due($trans_no, $trans_type, $debtor_no, $end_date, true, $trans_year, $last_year);
    }
    
    return $total;
}

function sum_due_this_month($trans_no_arr, $trans_type_arr, $debtor_no_arr, $trans_year, $end_date, $last_year = false) {
    
    $total = 0;

    foreach ($trans_no_arr as $key => $val) {
        $trans_no = $val;
        $trans_type = $trans_type_arr[$trans_no];
        $debtor_no = $debtor_no_arr[$trans_no];

        $total += due_this_month($trans_no, $trans_type, $debtor_no, $end_date, true, $trans_year, $last_year);
    }

    return $total;
}

function total_overdue($trans_no_arr, $trans_type_arr, $debtor_no_arr, $trans_year, $end_date, $last_year = false) {
    
    $total = $overdue_1month = $overdue_2month = 0;

    foreach ($trans_no_arr as $key => $val) {
        $trans_no = $val;
        $trans_type = $trans_type_arr[$trans_no];
        $debtor_no = $debtor_no_arr[$trans_no];

        $overdue_1month += overdue_1month($trans_no, $trans_type, $debtor_no, $end_date, true, $trans_year, $last_year);
        $overdue_2month += overdue_2months($trans_no, $trans_type, $debtor_no, $end_date, true, $trans_year, $last_year);
    }

    $total = $overdue_1month + $overdue_2month;

    return $total;
}

function sum_pastdue($trans_no_arr, $trans_type_arr, $debtor_no_arr, $trans_year, $end_date, $last_year = false) {

    $total = 0;

    foreach ($trans_no_arr as $key => $val) {
        $trans_no = $val;
        $trans_type = $trans_type_arr[$trans_no];
        $debtor_no = $debtor_no_arr[$trans_no];

        $total += past_due($trans_no, $trans_type, $debtor_no, $end_date, true, $trans_year, $last_year);
    }

    return $total;
}

//------------------------------------------------------------------------//
# Transaction Query:
//------------------------------------------------------------------------//

function get_AR_transactions($endDate, $cust_id = '', $group = 0, $filter = '', $summarized = false) {

    $date = date2sql($endDate);
    $sales_invoice = ST_SALESINVOICE;
    $sales_invoice_repo = ST_SALESINVOICEREPO;
    $change_term = ST_SITERMMOD;
    $restruct = ST_RESTRUCTURED;

     $sql = "SELECT '$date' AS cur_date, GL.gl_code, DM.area, A.description AS area_name, DT.stock_id, DM.address, 
            CASE 
	            WHEN GL.gl_code = 1200 THEN 'A/R - Regular Current'
	            WHEN GL.gl_code = 1201 THEN 'A/R - Installment Current'
	            WHEN GL.gl_code = 1202 THEN 'A/R - Regular Past Due'
	            WHEN GL.gl_code = 1204 THEN 'A/R - Installment Past Due'
            ELSE GL.gl_name END AS gl_name, 

            CN.user_id, CN.real_name AS col_name, DM.debtor_ref, DM.name AS cust_name, 
            DL.delivery_ref_no, '0' AS adj, '0' AS restruct, DL.downpayment_amount AS down_pay,

            CASE 
	            WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(CT.term_mod_date, '%Y-%m') THEN CT.invoice_ref_no
                WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(RT.term_mod_date, '%Y-%m') THEN RT.invoice_ref_no
            ELSE DL.invoice_ref_no END AS invoice_ref_no,

            CASE 
	            WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(CT.term_mod_date, '%Y-%m') THEN YEAR(CT.term_mod_date)
                WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(RT.term_mod_date, '%Y-%m') THEN YEAR(RT.term_mod_date)
            ELSE YEAR(DL.invoice_date) END AS inv_year,

            CASE 
	            WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(CT.term_mod_date, '%Y-%m') THEN CT.debtor_no
                WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(RT.term_mod_date, '%Y-%m') THEN RT.debtor_no
            ELSE DL.debtor_no END AS debtor_no,

            CASE 
	            WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(CT.term_mod_date, '%Y-%m') THEN CT.term_mod_date
                WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(RT.term_mod_date, '%Y-%m') THEN RT.term_mod_date
            ELSE DL.invoice_date END AS buy_date,

            CASE 
	            WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(CT.term_mod_date, '%Y-%m') THEN CT.ar_amount
                WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(RT.term_mod_date, '%Y-%m') THEN RT.ar_amount
            ELSE DL.ar_amount END AS gross,
            
            CASE 
	            WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(CT.term_mod_date, '%Y-%m') THEN CT.months_term
                WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(RT.term_mod_date, '%Y-%m') THEN RT.months_term
            ELSE DL.months_term END AS Term,

            CASE 
	            WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(CT.term_mod_date, '%Y-%m') THEN CT.trans_no
                WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(RT.term_mod_date, '%Y-%m') THEN RT.trans_no
            ELSE DL.trans_no END AS trans_no,

            CASE 
	            WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(CT.term_mod_date, '%Y-%m') THEN $change_term
                WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(RT.term_mod_date, '%Y-%m') THEN $restruct
            ELSE IF(DL.invoice_type = 'new', $sales_invoice, $sales_invoice_repo) END AS trans_type

            FROM " . TB_PREF . "debtor_loans DL
                LEFT JOIN " . TB_PREF . "debtors_master DM ON DL.debtor_no = DM.debtor_no
                LEFT JOIN " . TB_PREF . "areas A ON DM.area = A.area_code
                LEFT JOIN " . TB_PREF . "users CN ON A.collectors_id = CN.user_id
                LEFT JOIN " . TB_PREF . "debtor_trans_details DT ON DL.trans_no = DT.debtor_trans_no AND DT.debtor_trans_type = 10
                LEFT JOIN (
	                SELECT debtor_no, Y.account_code AS gl_code, Y.account_name AS gl_name 
                    FROM " . TB_PREF . " cust_branch X
		                INNER JOIN " . TB_PREF . "chart_master Y ON X.receivables_account = Y.account_code
                ) GL ON DM.debtor_no = GL.debtor_no
            
                LEFT JOIN ".TB_PREF."debtor_term_modification CT ON DL.invoice_ref_no = CT.invoice_ref_no 
                    AND DL.debtor_no = CT.debtor_no AND CT.type = $change_term
            
                LEFT JOIN ".TB_PREF."debtor_term_modification RT ON DL.invoice_ref_no = RT.invoice_ref_no 
                    AND DL.debtor_no = RT.debtor_no AND RT.type = $restruct

                LEFT JOIN (
                    SELECT DLL.trans_no, DLL.debtor_no, DLL.trans_type_to, 
                        SUM(DLL.payment_applied) AS sum_pay 
                    FROM ".TB_PREF."debtor_loan_ledger DLL 
                    WHERE DATE_FORMAT(DLL.date_paid, '%Y-%m') < DATE_FORMAT('$date', '%Y-%m')
                    GROUP BY DLL.trans_no, DLL.debtor_no
                ) CPAY ON (CASE
                        WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(CT.term_mod_date, '%Y-%m') THEN CT.trans_no
                        WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(RT.term_mod_date, '%Y-%m') THEN RT.trans_no
                    ELSE DL.trans_no END) = CPAY.trans_no 
                
                    AND (CASE
                        WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(CT.term_mod_date, '%Y-%m') THEN CT.debtor_no
                        WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(RT.term_mod_date, '%Y-%m') THEN RT.debtor_no
                    ELSE DL.debtor_no END) = CPAY.debtor_no 
                    
                    AND (CASE 
	                    WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(CT.term_mod_date, '%Y-%m') THEN $change_term
                        WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(RT.term_mod_date, '%Y-%m') THEN $restruct
                    ELSE IF(DL.invoice_type = 'new', $sales_invoice, $sales_invoice_repo) END) = CPAY.trans_type_to

                LEFT JOIN (
                    SELECT A.ar_trans_no, A.debtor_no, A.repo_date FROM ".TB_PREF."repo_accounts A
                ) REPO ON (CASE
                        WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(CT.term_mod_date, '%Y-%m') THEN CT.trans_no
                        WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(RT.term_mod_date, '%Y-%m') THEN RT.trans_no
                    ELSE DL.trans_no END) = REPO.ar_trans_no 
                    
                    AND (CASE
                        WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(CT.term_mod_date, '%Y-%m') THEN CT.debtor_no
                        WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(RT.term_mod_date, '%Y-%m') THEN RT.debtor_no
                    ELSE DL.debtor_no END) = REPO.debtor_no
           
            WHERE DL.months_term <> 0 AND DL.invoice_date <= '$date' 
                AND (CASE 
                    WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(CT.term_mod_date, '%Y-%m') THEN CT.ar_amount
                    WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(RT.term_mod_date, '%Y-%m') THEN RT.ar_amount
                ELSE DL.ar_amount END) - IFNULL(CPAY.sum_pay, 0) <> 0 ";
      
    $sql .= 
    " AND (CASE 
	        WHEN DATE_FORMAT('$date', '%Y-%m') < DATE_FORMAT(REPO.repo_date, '%Y-%m') THEN 'new'
            WHEN IFNULL(REPO.repo_date, '') = ''  THEN 'new'
        ELSE 'repo' END
    ) = 'new' ";

    if ($cust_id != '') {
        $sql .= 
        " AND (CASE 
            WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(CT.term_mod_date, '%Y-%m') THEN CT.debtor_no
            WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(RT.term_mod_date, '%Y-%m') THEN RT.debtor_no
        ELSE DL.debtor_no END) = ".db_escape($cust_id);
    }

    if ($summarized) {
        $sql .= " GROUP BY inv_year, invoice_ref_no, trans_type, debtor_no";
        $sql .= " ORDER BY inv_year ASC";
    }
    else {
        
        if ($group == 1 && $filter != '') {
            $sql .= " AND GL.gl_code = ".db_escape($filter);
        }
        else if ($group == 2 && $filter != '') {
            $sql .= " AND CN.user_id = ".db_escape($filter);
        }
        else if ($group == 3 && $filter != '') {
            $sql .= " AND DM.area = ".db_escape($filter);
        }

        if ($group == 1) {
            $sql .= " ORDER BY GL.gl_code ASC, DM.name ASC";
        }
        else if ($group == 2) {
            $sql .= " ORDER BY CN.user_id ASC, DM.name ASC";
        }
        else if ($group == 3) {
            $sql .= " ORDER BY DM.area, A.description, DM.name ASC";
        }     
    }
    
    return db_query($sql,"No transactions were returned");
}