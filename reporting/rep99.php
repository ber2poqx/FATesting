<?php

$page_security = 'SA_CUSTPAYMREP';

// ----------------------------------------------------------------
// $ Revision:	1.0 $
// Creator:	spyrax10
// date_:	2022-01-13
// Title:	Aging Summary Report - Summarized
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/sales/includes/db/customers_db.inc");
include_once($path_to_root . "/includes/aging.inc");

//----------------------------------------------------------------------------------------------------
print_transaction();
//----------------------------------------------------------------------------------------------------

function get_transactions($endDate, $cust_id = '') {

    $date = date2sql($endDate);
    $sales_invoice = ST_SALESINVOICE;
    $sales_invoice_repo = ST_SALESINVOICEREPO;
    $change_term = ST_SITERMMOD;
    $restruct = ST_RESTRUCTURED;

    $sql = "SELECT '$date' AS cur_date, GL.gl_code, DM.area, A.description AS area_name,  
            CASE 
	            WHEN GL.gl_code = 1200 THEN 'A/R - Regular Current'
	            WHEN GL.gl_code = 1201 THEN 'A/R - Installment Current'
	            WHEN GL.gl_code = 1202 THEN 'A/R - Regular Past Due'
	            WHEN GL.gl_code = 1204 THEN 'A/R - Installment Past Due'
                ELSE GL.gl_name END AS gl_name, 

            CN.user_id, CN.real_name AS col_name, DL.debtor_no, DM.debtor_ref, DM.name AS cust_name,
            DL.invoice_date AS buy_date, DL.delivery_ref_no, 
            '0' AS adj, '0' AS restruct, DL.downpayment_amount AS down_pay, DL.invoice_ref_no, 

            CASE 
	            WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(CT.term_mod_date, '%Y-%m') THEN CT.invoice_ref_no
                WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(RT.term_mod_date, '%Y-%m') THEN RT.invoice_ref_no
            ELSE DL.invoice_ref_no END AS invoice_ref_no,

            CASE 
	            WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(CT.term_mod_date, '%Y-%m') THEN YEAR(CT.term_mod_date)
                WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(RT.term_mod_date, '%Y-%m') THEN YEAR(RT.term_mod_date)
            ELSE YEAR(DL.invoice_date) END AS inv_year,

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
            LEFT JOIN (
	            SELECT debtor_no, Y.account_code AS gl_code, Y.account_name AS gl_name 
                FROM " . TB_PREF . " cust_branch X
		            INNER JOIN " . TB_PREF . "chart_master Y ON X.receivables_account = Y.account_code
            ) GL ON DM.debtor_no = GL.debtor_no
            
            LEFT JOIN ".TB_PREF."debtor_term_modification CT ON DL.invoice_ref_no = CT.invoice_ref_no 
                AND DL.debtor_no = CT.debtor_no

            LEFT JOIN ".TB_PREF."debtor_term_modification RT ON DL.invoice_ref_no = RT.invoice_ref_no 
                AND DL.debtor_no = RT.debtor_no
            
            LEFT JOIN (
                SELECT DLL.trans_no, DLL.debtor_no, DLL.trans_type_to, 
                    SUM(DLL.payment_applied) AS sum_pay 
                FROM ".TB_PREF."debtor_loan_ledger DLL 
                WHERE DATE_FORMAT(DLL.date_paid, '%Y-%m') < DATE_FORMAT('$date', '%Y-%m')
                GROUP BY DLL.trans_no, DLL.debtor_no
            ) CPAY ON DL.trans_no = CPAY.trans_no AND DL.debtor_no = CPAY.debtor_no AND 
                (CASE 
	                WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(CT.term_mod_date, '%Y-%m') THEN $change_term
                ELSE IF(DL.invoice_type = 'new', $sales_invoice, $sales_invoice_repo) END) = CPAY.trans_type_to

            LEFT JOIN (
                SELECT A.ar_trans_no, A.debtor_no, A.repo_date
                FROM ".TB_PREF."repo_accounts A
            ) REPO ON DL.trans_no = REPO.ar_trans_no AND DL.debtor_no = REPO.debtor_no
           
            WHERE DL.months_term <> 0 AND DL.invoice_date <= '$date' 
                AND (CASE 
                        WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(CT.term_mod_date, '%Y-%m') THEN CT.ar_amount
                ELSE DL.ar_amount END) - IFNULL(CPAY.sum_pay, 0) <> 0";
      
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
            ELSE DL.debtor_no END
        ) = ".db_escape($cust_id);
    }
    
    $sql .= " GROUP BY inv_year, invoice_ref_no, trans_type, DL.debtor_no ";
    $sql .= " ORDER BY inv_year ASC";
    
    return db_query($sql,"No transactions were returned");
}

function print_transaction() {

    global $path_to_root, $SysPrefs;

    $date = $_POST['PARAM_0'];
    $customer = $_POST['PARAM_1'];
    $comments = $_POST['PARAM_2'];
	$destination = $_POST['PARAM_3'];

    if ($customer == ALL_TEXT) {
        $cust = _('ALL');
    }
	else {
        $cust = get_customer_name($customer);
    	$dec = user_price_dec();
    }
		
    if ($destination) {
        include_once($path_to_root . "/reporting/includes/excel_report.inc");
    }
	else {
        include_once($path_to_root . "/reporting/includes/pdf_report.inc");
    }
		
    $orientation = 'P'; // Lock print orientation

    $maxYear = max((int)get_max_year(ST_SALESINVOICE), (int)get_max_year(ST_SITERMMOD));
	$maxYear1 = $maxYear - 1;
	$maxYear2 = $maxYear - 2;
	$maxYear3 = $maxYear - 3;

    $cols = array (0, 150, 210, 300, 385, 480, 0);

    $headers = array(
        _(""),
        _($maxYear),
        _($maxYear1),
        _($maxYear2),
        _((string)$maxYear3.' and Below'),
        _('Total')
    );

    $aligns = array('left', 'right', 'right', 'right', 'right', 'right');

    $params = array(
        0 => $comments,
        1 => array('text' => _('End Date'), 'from' => $date, 'to' => ''),
        2 => array('text' => _('Customer'), 'from' => $cust, 'to' => ''),
    );


    $rep = new FrontReport(_('Aging Summary Report (Summarized)'), "AgingSumReport", 'LEGAL', 10, $orientation);

    if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    
    if ($destination) {
        $rep->SetHeaderType('PO_Header');
    }
    else {
        $rep->SetHeaderType('PO_Header');     
    }
	
    $rep->NewPage();

    $cur_date = "0000-00-00";

    //Array
    $trans_no_arr1 = $trans_no_arr2 = $trans_no_arr3 = $trans_no_arr4 = array();
    $trans_type_arr1 = $trans_type_arr2 = $trans_type_arr3 = $trans_type_arr4 = array();
    $debt_no_arr1 = $debt_no_arr2 = $debt_no_arr3 = $debt_no_arr4 = array();

    //Validation
    $trans_no1 = $trans_no2 = $trans_no3 = $trans_no4 = '';

    //Parent
    $sum_not_yet_due1 = $sum_not_yet_due2 = $sum_not_yet_due3 = $sum_not_yet_due4 = $sum_not_yet_due5 = 0.0;
    $sum_due_this_month1 = $sum_due_this_month2 = $sum_due_this_month3 = $sum_due_this_month4 = $sum_due_this_month5 = 0.0;
    $total_current1 = $total_current2 = $total_current3 = $total_current4 = $total_current5 = 0.0;
    $total_overdue1 = $total_overdue2 = $total_overdue3 = $total_overdue4 = $total_overdue5 = 0.0;
    $pastdue1 = $pastdue2 = $pastdue3 = $pastdue4 = $pastdue5 = 0.0;
    $total_pastdue1 = $total_pastdue2 = $total_pastdue3 = $total_pastdue4 = $total_pastdue5 = 0.0;
    $acct_due1 = $acct_due2 = $acct_due3 = $acct_due4 = $acct_due5 = 0.0;
    $total_ar1 = $total_ar2 = $total_ar3 = $total_ar4 = $total_ar5 = 0.0;
    $total_acc1 = $total_acc2 = $total_acc3 = $total_acc4 = $total_acc5 = 0;
    

    $res = get_Transactions($date, $customer);

    while ($trans = db_fetch($res)) {

        $cur_date = $trans['cur_date'];
        
        if ($trans['inv_year'] == $maxYear) {
            if ($trans_no1 != $trans['trans_no']) {
                $trans_no_arr1[$trans['trans_no']] = $trans['trans_no']; 
                $trans_type_arr1[$trans['trans_no']] = $trans['trans_type'];
                $debt_no_arr1[$trans['trans_no']] = $trans['debtor_no'];
            }        
        }
        else if ($trans['inv_year'] == $maxYear1) {
            if ($trans_no2 != $trans['trans_no']) {
                $trans_no_arr2[$trans['trans_no']] = $trans['trans_no'];
                $trans_type_arr2[$trans['trans_no']] = $trans['trans_type']; 
                $debt_no_arr2[$trans['trans_no']] = $trans['debtor_no'];
            }
        }
        else if ($trans['inv_year'] == $maxYear2) {
            if ($trans_no3 != $trans['trans_no']) {
                $trans_no_arr3[$trans['trans_no']] = $trans['trans_no'];
                $trans_type_arr3[$trans['trans_no']] = $trans['trans_type']; 
                $debt_no_arr3[$trans['trans_no']] = $trans['debtor_no'];
            }
        }
        else if ($trans['inv_year'] <= $maxYear3) {
            if ($trans_no4 != $trans['trans_no']) {
                $trans_no_arr4[$trans['trans_no']] = $trans['trans_no']; 
                $trans_type_arr4[$trans['trans_no']] = $trans['trans_type'];
                $debt_no_arr4[$trans['trans_no']] = $trans['debtor_no'];
            }
        }
        //display_error($trans['trans_no'] . " || " . $trans['trans_type'] . " || " . $trans['debtor_no'] . " || " . $trans['inv_year']);
    }

    //Caculations
    $sum_not_yet_due1 = sum_not_yet_due($trans_no_arr1, $trans_type_arr1, $debt_no_arr1, $maxYear, $cur_date, false);
    $sum_not_yet_due2 = sum_not_yet_due($trans_no_arr2, $trans_type_arr2, $debt_no_arr2, $maxYear1, $cur_date, false);
    $sum_not_yet_due3 = sum_not_yet_due($trans_no_arr3, $trans_type_arr3, $debt_no_arr3, $maxYear2, $cur_date, false);
    $sum_not_yet_due4 = sum_not_yet_due($trans_no_arr4, $trans_type_arr4, $debt_no_arr4, $maxYear3, $cur_date, true);
    $sum_not_yet_due5 = $sum_not_yet_due1 + $sum_not_yet_due2 + $sum_not_yet_due3 + $sum_not_yet_due4;

    $sum_due_this_month1 = sum_due_this_month($trans_no_arr1, $trans_type_arr1, $debt_no_arr1, $maxYear, $cur_date, false);
    $sum_due_this_month2 = sum_due_this_month($trans_no_arr2, $trans_type_arr2, $debt_no_arr2, $maxYear1, $cur_date, false);
    $sum_due_this_month3 = sum_due_this_month($trans_no_arr3, $trans_type_arr3, $debt_no_arr3, $maxYear2, $cur_date, false);
    $sum_due_this_month4 = sum_due_this_month($trans_no_arr4, $trans_type_arr4, $debt_no_arr4, $maxYear3, $cur_date, true);
    $sum_due_this_month5 = $sum_due_this_month1 + $sum_due_this_month2 + $sum_due_this_month3 + $sum_due_this_month4;

    $total_current1 = $sum_not_yet_due1 + $sum_due_this_month1;
    $total_current2 = $sum_not_yet_due2 + $sum_due_this_month2;
    $total_current3 = $sum_not_yet_due3 + $sum_due_this_month3;
    $total_current4 = $sum_not_yet_due4 + $sum_due_this_month4;
    $total_current5 = $total_current1 + $total_current2 + $total_current3 + $total_current4;

    $total_overdue1 = total_overdue($trans_no_arr1, $trans_type_arr1, $debt_no_arr1, $maxYear, $cur_date, false);
    $total_overdue2 = total_overdue($trans_no_arr2, $trans_type_arr2, $debt_no_arr2, $maxYear1, $cur_date, false);
    $total_overdue3 = total_overdue($trans_no_arr3, $trans_type_arr3, $debt_no_arr3, $maxYear2, $cur_date, false);
    $total_overdue4 = total_overdue($trans_no_arr4, $trans_type_arr4, $debt_no_arr4, $maxYear3, $cur_date, true);
    $total_overdue5 = $total_overdue1 + $total_overdue2 + $total_overdue3 + $total_overdue4;

    $pastdue1 = sum_pastdue($trans_no_arr1, $trans_type_arr1, $debt_no_arr1, $maxYear, $cur_date, false);
    $pastdue2 = sum_pastdue($trans_no_arr2, $trans_type_arr2, $debt_no_arr2, $maxYear1, $cur_date, false);
    $pastdue3 = sum_pastdue($trans_no_arr3, $trans_type_arr3, $debt_no_arr3, $maxYear2, $cur_date, false);
    $pastdue4 = sum_pastdue($trans_no_arr4, $trans_type_arr4, $debt_no_arr4, $maxYear3, $cur_date, true);
    $pastdue5 = $pastdue1 + $pastdue2 + $pastdue3 + $pastdue4;

    $total_pastdue1 = $total_overdue1 + $pastdue1;
    $total_pastdue2 = $total_overdue2 + $pastdue2;
    $total_pastdue3 = $total_overdue3 + $pastdue3;
    $total_pastdue4 = $total_overdue4 + $pastdue4;
    $total_pastdue5 = $total_pastdue1 + $total_pastdue2 + $total_pastdue3 + $total_pastdue4;

    $acct_due1 = $sum_due_this_month1 + $total_pastdue1;
    $acct_due2 = $sum_due_this_month2 + $total_pastdue2;
    $acct_due3 = $sum_due_this_month3 + $total_pastdue3;
    $acct_due4 = $sum_due_this_month4 + $total_pastdue4;
    $acct_due5 = $acct_due1 + $acct_due2 + $acct_due3 + $acct_due4;

    $total_ar1 = $total_current1 + $total_pastdue1;
    $total_ar2 = $total_current2 + $total_pastdue2;
    $total_ar3 = $total_current3 + $total_pastdue3;
    $total_ar4 = $total_current4 + $total_pastdue4;
    $total_ar5 = $total_ar1 + $total_ar2 + $total_ar3 + $total_ar4; 

    $total_acc1 = count($trans_no_arr1);
    $total_acc2 = count($trans_no_arr2);
    $total_acc3 = count($trans_no_arr3);
    $total_acc4 = count($trans_no_arr4);
    $total_acc5 = $total_acc1 + $total_acc2 + $total_acc3 + $total_acc4;
   
    $dec = user_price_dec();

    //Display
    $rep->TextCol(0, 1, _("Not Yet Due"));

    $rep->AmountCol(1, 2, $sum_not_yet_due1, $dec);
    $rep->AmountCol(2, 3, $sum_not_yet_due2, $dec);
    $rep->AmountCol(3, 4, $sum_not_yet_due3, $dec);
    $rep->AmountCol(4, 5, $sum_not_yet_due4, $dec);
    $rep->Font('bold');
    $rep->AmountCol(5, 6, $sum_not_yet_due5, $dec);
    $rep->Font();
    
    $rep->NewLine();
    $rep->TextCol(0, 1, _("Due This Month"));

    $rep->AmountCol(1, 2, $sum_due_this_month1, $dec);
    $rep->AmountCol(2, 3, $sum_due_this_month2, $dec);
    $rep->AmountCol(3, 4, $sum_due_this_month3, $dec);
    $rep->AmountCol(4, 5, $sum_due_this_month4, $dec);
    $rep->Font('bold');
    $rep->AmountCol(5, 6, $sum_due_this_month5, $dec);
    $rep->Font();

    $rep->Line($rep->row - 4);
    $rep->NewLine(1.5);
    $rep->Font('bold');
    $rep->TextCol(0, 1, _("Total Current"));
    
    $rep->AmountCol(1, 2, $total_current1, $dec);
    $rep->AmountCol(2, 3, $total_current2, $dec);
    $rep->AmountCol(3, 4, $total_current3, $dec);
    $rep->AmountCol(4, 5, $total_current4, $dec);
    $rep->AmountCol(5, 6, $total_current5, $dec);

    $rep->Font();

    $rep->Line($rep->row - 4);
    $rep->NewLine(.2);
    $rep->Line($rep->row - 4);
    $rep->NewLine(2);
    $rep->TextCol(0, 1, _("Over Due Amortization"));

    $rep->AmountCol(1, 2, $total_overdue1, $dec);
    $rep->AmountCol(2, 3, $total_overdue2, $dec);
    $rep->AmountCol(3, 4, $total_overdue3, $dec);
    $rep->AmountCol(4, 5, $total_overdue4, $dec);
    $rep->Font('bold');
    $rep->AmountCol(5, 6, $total_overdue5, $dec);
    $rep->Font();

    $rep->NewLine(2);
    $rep->TextCol(0, 1, _("Past Due Accounts"));

    $rep->AmountCol(1, 2, $pastdue1, $dec);
    $rep->AmountCol(2, 3, $pastdue2, $dec);
    $rep->AmountCol(3, 4, $pastdue3, $dec);
    $rep->AmountCol(4, 5, $pastdue4, $dec);
    $rep->Font('bold');
    $rep->AmountCol(5, 6, $pastdue5, $dec);
    $rep->Font();

    $rep->NewLine();
    $rep->TextCol(0, 1, _("Accounts in Litigation"));

    $rep->Font('bold');
    $rep->TextCol(1, 2, "-");
    $rep->TextCol(2, 3, "-");
    $rep->TextCol(3, 4, "-");
    $rep->TextCol(4, 5, "-");
    $rep->Font();

    $rep->Line($rep->row - 4);
    $rep->NewLine(2);
    $rep->Font('bold');
    $rep->SetTextColor(255, 0, 0);
    $rep->TextCol(0, 1, _("Total Past Due"));

    $rep->AmountCol(1, 2, $total_pastdue1, $dec);
    $rep->AmountCol(2, 3, $total_pastdue2, $dec);
    $rep->AmountCol(3, 4, $total_pastdue3, $dec);
    $rep->AmountCol(4, 5, $total_pastdue4, $dec);
    $rep->AmountCol(5, 6, $total_pastdue5, $dec);

    $rep->SetTextColor(0, 0, 0);
    $rep->Font();

    $rep->Line($rep->row - 4);
    $rep->NewLine(2);
    $rep->TextCol(0, 1, _("Total Account Due"));

    $rep->AmountCol(1, 2, $acct_due1, $dec);
    $rep->AmountCol(2, 3, $acct_due2, $dec);
    $rep->AmountCol(3, 4, $acct_due3, $dec);
    $rep->AmountCol(4, 5, $acct_due4, $dec);
    $rep->Font('bold');
    $rep->AmountCol(5, 6, $acct_due5, $dec);
    $rep->Font();

    $rep->NewLine(2);
    $rep->Font('bold');
    $rep->TextCol(0, 1, _("Total Account Receivable"));

    $rep->AmountCol(1, 2, $total_ar1, $dec);
    $rep->AmountCol(2, 3, $total_ar2, $dec);
    $rep->AmountCol(3, 4, $total_ar3, $dec);
    $rep->AmountCol(4, 5, $total_ar4, $dec);
    $rep->Font('bold');
    $rep->AmountCol(5, 6, $total_ar5, $dec);
    $rep->Font();

    $rep->Font();

    $rep->Line($rep->row - 4);
    $rep->NewLine(.2);
    $rep->Line($rep->row - 4);
    $rep->NewLine(2);
    $rep->TextCol(0, 1, _("Total No Of Accounts"));

    $rep->AmountCol(1, 2, $total_acc1);
    $rep->AmountCol(2, 3, $total_acc2);
    $rep->AmountCol(3, 4, $total_acc3);
    $rep->AmountCol(4, 5, $total_acc4);
    $rep->Font('bold');
    $rep->AmountCol(5, 6, $total_acc5);
    $rep->Font();

    $rep->End();
}