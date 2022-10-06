<?php

/**
 * Name: Trial Balance (New)
 * Added by: spyrax10
 * Date Added: 6 Oct 2022
*/
$path_to_root = "..";
$page_security = 'SA_GLANALYTIC';

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/admin/db/fiscalyears_db.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------
print_trial_balance();
//----------------------------------------------------------------------------------------------------
function get_trial_balace($from = '', $to = '', $account_code = '', $col = "") {

    $fdate = date2sql($from);
    $tdate = date2sql($to);
    
    set_global_connection();

    $sql = "SELECT CM.account_code, CM.account_name,
    (SELECT SUM(amount) FROM gl_trans 
        WHERE account = CM.account_code AND amount > 0
	        AND tran_date >= '$fdate' AND tran_date <= '$tdate'
    ) period_debit,
    (SELECT SUM(amount) FROM gl_trans 
        WHERE account = CM.account_code AND amount < 0
	        AND tran_date >= '$fdate' AND tran_date <= '$tdate'
    ) period_credit, 
    (SELECT SUM(amount) FROM gl_trans 
        WHERE account = CM.account_code AND amount > 0
	        AND tran_date < '$fdate'
    ) forward_debit,
    (SELECT SUM(amount) FROM gl_trans 
        WHERE account = CM.account_code AND amount < 0
	        AND tran_date < '$fdate' 
    ) forward_credit

    FROM chart_master CM 
    WHERE CM.account_code = " .db_escape($account_code);

    $result = db_query($sql, _("get_trial_balace()"));
    $row = db_fetch_row($result);

    if ($col == "period_debit") {
        $ret_row = $row[2];
    }
    else if ($col == "period_credit") {
        $ret_row = $row[3];
    }
    else if ($col == "forward_debit") {
        $ret_row = $row[4];
    }
    else if ($col == "forward_credit") {
        $ret_row = $row[5];
    }
    
    return $ret_row;
} 

//----------------------------------------------------------------------------------------------------

function print_trial_balance() {

    global $path_to_root, $SysPrefs;
    
	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
    $comments = $_POST['PARAM_2'];
    $destination = $_POST['PARAM_3'];

    
	if ($destination) {
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	}
	else {
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	}

    $orientation = 'L';
	$dec = user_price_dec();

	$cols2 = array(0, 20, 250, 370, 490, 600);
	//-------------0--1---2----3----4----5--

	$headers2 = array('', '', _('Brought Forward'),	_('This Period'), _('Balance'));

	$aligns2 = array('left', 'left', 'left', 'left', 'left');

	$cols = array(0, 60, 210, 270, 330,	390, 450, 510, 570,	630);
	//------------0--1---2----3----4----5----6----7----8--

	$headers = array(_('Account'), _('Account Name'), _('Debit'), _('Credit'), _('Debit'),
		_('Credit'), _('Debit'), _('Credit')
	);

	$aligns = array('left',	'left',	'right', 'right', 'right', 'right',	'right', 'right');

    $params = array( 	
        0 => $comments,
        1 => array('text' => _('Period'),'from' => $from, 'to' => $to)
    );

    $rep = new FrontReport(_('Trial Balance'), "TrialBalance", 'LETTER', 9, $orientation);

    if ($orientation == 'L') {
    	recalculate_cols($cols);
    	recalculate_cols($cols2);
	}

	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns, $cols2, $headers2, $aligns2);
	$rep->SetHeaderType('PO_Header');
	$rep->NewPage();

    $accounts = get_gl_accounts(null, null, null);
    
    $for_debit_tot = $for_credit_tot = 0;
    $per_debit_tot = $per_credit_tot = 0;
    $end_debit_tot = $end_credit_tot = 0;

    while ($data = db_fetch($accounts)) {

        $forward_debit = get_trial_balace($from, $to, $data['account_code'], 'forward_debit');
        $forward_credit = get_trial_balace($from, $to, $data['account_code'], 'forward_credit');
        $period_debit = get_trial_balace($from, $to, $data['account_code'], 'period_debit');
        $period_credit = get_trial_balace($from, $to, $data['account_code'], 'period_credit');
        
        $total_forward = $forward_debit + $forward_credit;
        $total_period =  $period_debit + $period_credit;

        if ($total_forward + $total_period > 0) {
            $bal_debit = $total_forward + $total_period;
            $bal_credit =  0;
        }
        else {
            $bal_credit = $total_forward + $total_period;
            $bal_debit = 0;
        }
    
        $rep->TextCol(0, 1, $data['account_code']);
		$rep->TextCol(1, 2,	$data['account_name']);

        $rep->AmountCol(2, 3, $forward_debit, $dec);
        $rep->AmountCol(3, 4, ABS($forward_credit), $dec);
        $rep->AmountCol(4, 5, $period_debit, $dec);
        $rep->AmountCol(5, 6, ABS($period_credit), $dec);
        $rep->AmountCol(6, 7, $bal_debit, $dec);
        $rep->AmountCol(7, 8, ABS($bal_credit), $dec);
        $rep->NewLine(1);

        $for_debit_tot += $forward_debit;
        $for_credit_tot += ABS($forward_credit);
        $per_debit_tot += $period_debit;
        $per_credit_tot += ABS($period_credit);
        $end_debit_tot += $bal_debit;
        $end_credit_tot += ABS($bal_credit);
    }

    $rep->Line($rep->row  - 4);
	$rep->Font();
	$rep->NewLine(3);
    $rep->Font('bold');
    $rep->fontSize += 2;
	$rep->TextCol(0, 2, _('Grand Total Balance:'));
    $rep->Line($rep->row  - 4);
	$rep->fontSize -= 2;
    $rep->AmountCol(2, 3, $for_debit_tot, $dec);
    $rep->AmountCol(3, 4, $for_credit_tot, $dec);
    $rep->AmountCol(4, 5, $per_debit_tot, $dec);
    $rep->AmountCol(5, 6, $per_credit_tot, $dec);
    $rep->AmountCol(6, 7, $end_debit_tot, $dec);
    $rep->AmountCol(7, 8, $end_credit_tot, $dec);
    $rep->Font();

    $rep->End();
}
