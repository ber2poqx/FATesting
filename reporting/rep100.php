<?php
$page_security = 'SA_CUSTPAYMREP';

// ----------------------------------------------------------------
// $ Revision:	1.0 $
// Creator:	spyrax10
// date_:	2021-08-10
// Title:	Aging Summary Report
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

function get_transactions($endDate, $cust_id = '', $group = 0, $filter = '') {
    
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

            CN.user_id, CN.real_name AS col_name, DM.debtor_ref, DM.name AS cust_name, 
            DL.delivery_ref_no, '0' AS adj, '0' AS restruct, DL.downpayment_amount AS down_pay,

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
                ELSE DL.ar_amount END) - IFNULL(CPAY.sum_pay, 0) <> 0 ";
      
    $sql .= " AND (CASE 
	                WHEN DATE_FORMAT('$date', '%Y-%m') < DATE_FORMAT(REPO.repo_date, '%Y-%m') THEN 'new'
                    WHEN IFNULL(REPO.repo_date, '') = ''  THEN 'new'
                ELSE 'repo' END) = 'new' ";

    if ($cust_id != '') {
        $sql .= " AND (CASE 
                        WHEN DATE_FORMAT('$date', '%Y-%m') >= DATE_FORMAT(CT.term_mod_date, '%Y-%m') THEN CT.debtor_no
                    ELSE DL.debtor_no END) = ".db_escape($cust_id);
    }

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

    return db_query($sql,"No transactions were returned");
}

function print_transaction() {
    global $path_to_root, $SysPrefs;

    
    $date = $_POST['PARAM_0'];
    $customer = $_POST['PARAM_1'];
    $group = $_POST['PARAM_2'];
    $filter = $_POST['PARAM_3'];
    $comments = $_POST['PARAM_4'];
	$destination = $_POST['PARAM_5'];

    if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	
    if ($customer == ALL_TEXT) {
        $cust = _('ALL');
    }
	else
		$cust = get_customer_name($customer);
    	$dec = user_price_dec();

    if ($group == 1) {
        $grp = _('CHART OF ACCOUNTS');
    }
    else if ($group == 2) {
        $grp = _('COLLECTOR');
    }
    else if ($group == 3) {
        $grp = _('AREA');
    }

    $orientation = 'L'; // Lock print orientation

    $cols = array(0, 70, 105, 130, 185, 225, 265, 305, 
        350, 395, 440, 485, 530, 575, 620, 665, 710, 0
    );

    $headers = array(
        _('Account Name'), 
        _('Buy Date'), 
        _('Term'),
        _('Gross'),
        _('Down_Paymnt'),
        _('Adjstmnt'), 
        _('Rest_Adjmt'), 
        _('Payments'), 
        _('Adv_Paymnt'), 
        _('Balance'), 
        _('Not Yet Due'), 
        _('Due Nxt Mon'), 
        _('Due This Mon'), 
        _('Ovr Due 1Mon'), 
        _('Ovr Due 2Mon<<'),
        _('Past Due'),
        _('Collectibles')
    );

    $aligns = array('left', 'left', 'center', 'center', 'center', 'center', 
        'center', 'center', 'center', 'center', 'center', 'center', 'center', 
        'center', 'center', 'center', 'right'
    );
    
    $params = array(0 => $comments,
        1 => array('text' => _('As of Date'), 'from' => $date, 'to' => ''),
        2 => array('text' => _('Customer'), 'from' => $cust, 'to' => ''),
        3 => array('text' => _('Group By'), 'from' => $grp, 'to' => ''),
        //4 => array('text' => _('Sales Areas'), 'from' => $sarea, 'to' => ''),
    );
    
    $rep = new FrontReport(_('Aging Summary Report'), "AgingSumReport", 'LEGAL', 9, $orientation);
    
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


    $res = get_Transactions($date, $customer, $group, $filter);
    $col_name = $gl_name =  $area_name = '';
  
    //Parent
    $total_payment_this_month = $advance_payment = $current_balance = 
    $not_yet_due = $due_nxt_month = $due_this_month = $overdue_1month = 
    $overdue_2months = $past_due = $total_collectibles = $total_adjusment = 0;

    //Sub - Total
    $tot_gross = $tot_down = $tot_adj = $tot_rest = $tot_pay = $tot_adv = 
    $tot_bal = $tot_notDue = $tot_dueNxt = $tot_dueThis = $tot_ovr1 = 
    $tot_ovr2 = $tot_past = $tot_grand = $nyd = 0.0;
    
    //Grand Total
    $tot1_gross = $tot1_down = $tot1_adj = $tot1_rest = $tot1_pay = $tot1_adv = 
    $tot1_bal = $tot1_notDue = $tot1_dueNxt = $tot1_dueThis = $tot1_ovr1 = 
    $tot1_ovr2 = $tot1_past = $tot1_grand = 0.0;

    while ($trans = db_fetch($res)) {

        //display_error($trans['trans_no'] . " || " . $trans['trans_type'] . " || " . $trans['debtor_no']);
        //Parent
        $total_adjusment = total_adjusment($trans['trans_no'], $trans['trans_type'], $trans['debtor_no'], $trans['cur_date']);
        $total_payment_this_month = payment_this_month($trans['trans_no'], $trans['trans_type'], $trans['debtor_no'], $trans['cur_date']);
        $advance_payment = advance_payment($trans['trans_no'], $trans['trans_type'], $trans['debtor_no'], $trans['cur_date']);
        $current_balance = current_balance_display($trans['trans_no'], $trans['trans_type'], $trans['debtor_no'], $trans['cur_date']);
        $not_yet_due = not_yet_due($trans['trans_no'], $trans['trans_type'], $trans['debtor_no'], $trans['cur_date']);
        $due_nxt_month = due_nxt_month($trans['trans_no'], $trans['trans_type'], $trans['debtor_no'], $trans['cur_date']);
        $due_this_month = due_this_month($trans['trans_no'], $trans['trans_type'], $trans['debtor_no'], $trans['cur_date']);
        $overdue_1month = overdue_1month($trans['trans_no'], $trans['trans_type'], $trans['debtor_no'], $trans['cur_date']);
        $overdue_2months = overdue_2months($trans['trans_no'], $trans['trans_type'], $trans['debtor_no'], $trans['cur_date']);
        $past_due = past_due($trans['trans_no'], $trans['trans_type'], $trans['debtor_no'], $trans['cur_date']);
        $total_collectibles = total_collectibles($trans['trans_no'], $trans['trans_type'], $trans['debtor_no'], $trans['cur_date']);


        if ($group == 1) {
            if ($gl_name != $trans['gl_name']) {

                if ($gl_name != '') {
                    $rep->NewLine(2);
                    $rep->Font('bold');
				    $rep->TextCol(0, 1, _('Sub_Total'));
                    $rep->AmountCol(3, 4, $tot_gross, $dec);
                    $rep->AmountCol(4, 5, $tot_down, $dec);
                    $rep->AmountCol(5, 6, $tot_adj, $dec);
                    $rep->AmountCol(6, 7, $tot_rest, $dec);
                    $rep->AmountCol(7, 8, $tot_pay, $dec);
                    $rep->AmountCol(8, 9, $tot_adv, $dec);
                    $rep->AmountCol(9, 10, $tot_bal, $dec);
                    $rep->AmountCol(10, 11, $tot_notDue, $dec);
                    $rep->AmountCol(11, 12, $tot_dueNxt, $dec);
                    $rep->AmountCol(12, 13, $tot_dueThis, $dec);
                    $rep->SetTextColor(255, 0, 0);
                    $rep->AmountCol(13, 14, $tot_ovr1, $dec);
                    $rep->AmountCol(14, 15, $tot_ovr2, $dec);
                    $rep->SetTextColor(0, 0, 0);
                    $rep->AmountCol(15, 16, $tot_past, $dec);
                    $rep->AmountCol(16, 17, $tot_grand, $dec);
                    $rep->Line($rep->row  - 4);
                    $rep->NewLine(2);
                    $rep->Font();

                    $tot_gross = $tot_down = $tot_adj = $tot_rest = $tot_pay = $tot_adv = 
                    $tot_bal = $tot_notDue = $tot_dueNxt = $tot_dueThis = $tot_ovr1 = 
                    $tot_ovr2 = $tot_past = $tot_grand = 0.0;
                }
    
                $rep->NewLine();
                $rep->fontSize += 1;
                $rep->Font('bold');
                $rep->SetTextColor(0, 0, 255);
                $rep->TextCol(0, 10, $trans['gl_name']);
                $gl_name = $trans['gl_name'];
                $rep->Font();
                $rep->fontSize -= 1;
                $rep->SetTextColor(0, 0, 0);
                $rep->NewLine();	
            }
        }
        else if ($group == 2) {
            if ($col_name != $trans['col_name']) {

                if ($col_name != '') {
                    $rep->NewLine(2);
                    $rep->Font('bold');
				    $rep->TextCol(0, 1, _('Sub_Total'));
                    $rep->AmountCol(3, 4, $tot_gross, $dec);
                    $rep->AmountCol(4, 5, $tot_down, $dec);
                    $rep->AmountCol(5, 6, $tot_adj, $dec);
                    $rep->AmountCol(6, 7, $tot_rest, $dec);
                    $rep->AmountCol(7, 8, $tot_pay, $dec);
                    $rep->AmountCol(8, 9, $tot_adv, $dec);
                    $rep->AmountCol(9, 10, $tot_bal, $dec);
                    $rep->AmountCol(10, 11, $tot_notDue, $dec);
                    $rep->AmountCol(11, 12, $tot_dueNxt, $dec);
                    $rep->AmountCol(12, 13, $tot_dueThis, $dec);
                    $rep->SetTextColor(255, 0, 0);
                    $rep->AmountCol(13, 14, $tot_ovr1, $dec);
                    $rep->AmountCol(14, 15, $tot_ovr2, $dec);
                    $rep->SetTextColor(0, 0, 0);
                    $rep->AmountCol(15, 16, $tot_past, $dec);
                    $rep->AmountCol(16, 17, $tot_grand, $dec);
                    $rep->Line($rep->row  - 4);
                    $rep->NewLine(2);
                    $rep->Font();

                    $tot_gross = $tot_down = $tot_adj = $tot_rest = $tot_pay = $tot_adv = 
                    $tot_bal = $tot_notDue = $tot_dueNxt = $tot_dueThis = $tot_ovr1 = 
                    $tot_ovr2 = $tot_past = $tot_grand = 0.0;
                }
    
                $rep->NewLine();
                $rep->fontSize += 1;
                $rep->Font('bold');
                $rep->SetTextColor(0, 0, 255);
                $rep->TextCol(0, 10, $trans['user_id']. ' - ' . $trans['col_name']);
                $col_name = $trans['col_name'];
                $rep->Font();
                $rep->fontSize -= 1;
                $rep->SetTextColor(0, 0, 0);
                $rep->NewLine();	
            }
        }
        else {
            if ($area_name != $trans['area_name']) {

                if ($area_name != '') {
                    $rep->NewLine(2);
                    $rep->Font('bold');
				    $rep->TextCol(0, 1, _('Sub_Total'));
                    $rep->AmountCol(3, 4, $tot_gross, $dec);
                    $rep->AmountCol(4, 5, $tot_down, $dec);
                    $rep->AmountCol(5, 6, $tot_adj, $dec);
                    $rep->AmountCol(6, 7, $tot_rest, $dec);
                    $rep->AmountCol(7, 8, $tot_pay, $dec);
                    $rep->AmountCol(8, 9, $tot_adv, $dec);
                    $rep->AmountCol(9, 10, $tot_bal, $dec);
                    $rep->AmountCol(10, 11, $tot_notDue, $dec);
                    $rep->AmountCol(11, 12, $tot_dueNxt, $dec);
                    $rep->AmountCol(12, 13, $tot_dueThis, $dec);
                    $rep->SetTextColor(255, 0, 0);
                    $rep->AmountCol(13, 14, $tot_ovr1, $dec);
                    $rep->AmountCol(14, 15, $tot_ovr2, $dec);
                    $rep->SetTextColor(0, 0, 0);
                    $rep->AmountCol(15, 16, $tot_past, $dec);
                    $rep->AmountCol(16, 17, $tot_grand, $dec);
                    $rep->Line($rep->row  - 4);
                    $rep->NewLine(2);
                    $rep->Font();

                    $tot_gross = $tot_down = $tot_adj = $tot_rest = $tot_pay = $tot_adv = 
                    $tot_bal = $tot_notDue = $tot_dueNxt = $tot_dueThis = $tot_ovr1 = 
                    $tot_ovr2 = $tot_past = $tot_grand = 0.0;
                }
    
                $rep->NewLine();
                $rep->fontSize += 1;
                $rep->Font('bold');
                $rep->SetTextColor(0, 0, 255);
                $rep->TextCol(0, 10, $trans['area_name']);
                $area_name = $trans['area_name'];
                $rep->Font();
                $rep->fontSize -= 1;
                $rep->SetTextColor(0, 0, 0);
                $rep->NewLine();	
            }
        }

        $rep->NewLine();
        $rep->fontSize -= .5;

        $rep->TextCol(0, 1, $trans['cust_name']);
        $rep->SetTextColor(255, 0, 0);
        $rep->TextCol(1, 2, $trans['buy_date']);
        $rep->SetTextColor(0, 0, 0);
        $rep->TextCol(2, 3, $trans['Term']);
        $rep->AmountCol(3, 4, $trans['gross'], $dec);
        $rep->AmountCol(4, 5, $trans['down_pay'], $dec);
        $rep->AmountCol(5, 6, $total_adjusment, $dec);
        $rep->AmountCol(6, 7, $trans['restruct'], $dec);
        $rep->AmountCol(7, 8, $total_payment_this_month, $dec);
        $rep->AmountCol(8, 9, $advance_payment, $dec);
        $rep->AmountCol(9, 10, $current_balance, $dec);
        $rep->AmountCol(10, 11, $not_yet_due, $dec);
        $rep->AmountCol(11, 12, $due_nxt_month, $dec);
        $rep->AmountCol(12, 13, $due_this_month, $dec);
        $rep->SetTextColor(255, 0, 0);
        $rep->AmountCol(13, 14, $overdue_1month, $dec);
        $rep->AmountCol(14, 15, $overdue_2months, $dec);
        $rep->SetTextColor(0, 0, 0);
        $rep->AmountCol(15, 16, $past_due, $dec);
        $rep->AmountCol(16, 17, $total_collectibles, $dec);
        $rep->fontSize += .5;
        $rep->NewLine(.5);

        //Sub - Total                               //Grand Total
        $tot_gross += $trans['gross'];              $tot1_gross += $trans['gross'];
        $tot_down += $trans['down_pay'];            $tot1_down += $trans['down_pay'];           
        $tot_adj += $total_adjusment;               $tot1_adj += $total_adjusment;
        $tot_rest += $trans['restruct'];            $tot1_rest += $trans['restruct'];
        $tot_pay += $total_payment_this_month;      $tot1_pay += $total_payment_this_month;
        $tot_adv += $advance_payment;               $tot1_adv += $advance_payment;
        $tot_bal += $current_balance;               $tot1_bal += $current_balance;
        $tot_notDue += $not_yet_due;                $tot1_notDue += $not_yet_due;
        $tot_dueNxt += $due_nxt_month;              $tot1_dueNxt += $due_nxt_month;
        $tot_dueThis += $due_this_month;            $tot1_dueThis += $due_this_month;
        $tot_ovr1 += $overdue_1month;               $tot1_ovr1 += $overdue_1month;
        $tot_ovr2 += $overdue_2months;              $tot1_ovr2 += $overdue_2months;
        $tot_past += $past_due;                     $tot1_past += $past_due;
        $tot_grand += $total_collectibles;          $tot1_grand += $total_collectibles;
      
    } //END WHILE

    if ($group == 1) {
        if ($gl_name != '') {
            $rep->NewLine(2);
            $rep->Font('bold');
            $rep->TextCol(0, 1, _('Sub_Total'));
            $rep->AmountCol(3, 4, $tot_gross, $dec);
            $rep->AmountCol(4, 5, $tot_down, $dec);
            $rep->AmountCol(5, 6, $tot_adj, $dec);
            $rep->AmountCol(6, 7, $tot_rest, $dec);
            $rep->AmountCol(7, 8, $tot_pay, $dec);
            $rep->AmountCol(8, 9, $tot_adv, $dec);
            $rep->AmountCol(9, 10, $tot_bal, $dec);
            $rep->AmountCol(10, 11, $tot_notDue, $dec);
            $rep->AmountCol(11, 12, $tot_dueNxt, $dec);
            $rep->AmountCol(12, 13, $tot_dueThis, $dec);
            $rep->SetTextColor(255, 0, 0);
            $rep->AmountCol(13, 14, $tot_ovr1, $dec);
            $rep->AmountCol(14, 15, $tot_ovr2, $dec);
            $rep->SetTextColor(0, 0, 0);
            $rep->AmountCol(15, 16, $tot_past, $dec);
            $rep->AmountCol(16, 17, $tot_grand, $dec);
            $rep->Line($rep->row  - 4);
            $rep->Font();
        }
    }
    else {
        if ($col_name != '') {
            $rep->NewLine(2);
            $rep->Font('bold');
            $rep->TextCol(0, 1, _('Sub_Total'));
            $rep->AmountCol(3, 4, $tot_gross, $dec);
            $rep->AmountCol(4, 5, $tot_down, $dec);
            $rep->AmountCol(5, 6, $tot_adj, $dec);
            $rep->AmountCol(6, 7, $tot_rest, $dec);
            $rep->AmountCol(7, 8, $tot_pay, $dec);
            $rep->AmountCol(8, 9, $tot_adv, $dec);
            $rep->AmountCol(9, 10, $tot_bal, $dec);
            $rep->AmountCol(10, 11, $tot_notDue, $dec);
            $rep->AmountCol(11, 12, $tot_dueNxt, $dec);
            $rep->AmountCol(12, 13, $tot_dueThis, $dec);
            $rep->SetTextColor(255, 0, 0);
            $rep->AmountCol(13, 14, $tot_ovr1, $dec);
            $rep->AmountCol(14, 15, $tot_ovr2, $dec);
            $rep->SetTextColor(0, 0, 0);
            $rep->AmountCol(15, 16, $tot_past, $dec);
            $rep->AmountCol(16, 17, $tot_grand, $dec);
            $rep->Line($rep->row  - 4);
            $rep->Font();
        }
    }

    $rep->NewLine(2.5);
    $rep->Font('bold');
    $rep->fontSize += 2;
	$rep->TextCol(0, 1, _('GRAND TOTAL:'));
    $rep->fontSize -= 2;

    $rep->AmountCol(3, 4, $tot1_gross, $dec);
    $rep->AmountCol(4, 5, $tot1_down, $dec);
    $rep->AmountCol(5, 6, $tot1_adj, $dec);
    $rep->AmountCol(6, 7, $tot1_rest, $dec);
    $rep->AmountCol(7, 8, $tot1_pay, $dec);
    $rep->AmountCol(8, 9, $tot1_adv, $dec);
    $rep->AmountCol(9, 10, $tot1_bal, $dec);
    $rep->AmountCol(10, 11, $tot1_notDue, $dec);
    $rep->AmountCol(11, 12, $tot1_dueNxt, $dec);
    $rep->AmountCol(12, 13, $tot1_dueThis, $dec);
    $rep->SetTextColor(255, 0, 0);
    $rep->AmountCol(13, 14, $tot1_ovr1, $dec);
    $rep->AmountCol(14, 15, $tot1_ovr2, $dec);
    $rep->SetTextColor(0, 0, 0);
    $rep->AmountCol(15, 16, $tot1_past, $dec);
    $rep->AmountCol(16, 17, $tot1_grand, $dec);
    
	$rep->Line($rep->row  - 4);
    
	$rep->NewLine();
    $rep->End();
    
}