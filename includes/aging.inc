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