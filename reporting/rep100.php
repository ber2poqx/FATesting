<?php
$page_security = 'SA_AGING_REP';

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

function print_transaction() {
    global $path_to_root, $SysPrefs;

    $date = $_POST['PARAM_0'];
    $customer = $_POST['PARAM_1'];
    $group = $_POST['PARAM_2'];
    $filter = $_POST['PARAM_3'];
    $show_add = $_POST['PARAM_4'];
    $comments = $_POST['PARAM_5'];
	$destination = $_POST['PARAM_6'];

    if ($show_add == 1) {
        include_once($path_to_root . "/reporting/includes/excel_report.inc");
    }
    else {
        if ($destination) {
            include_once($path_to_root . "/reporting/includes/excel_report.inc");
        }
        else {
            include_once($path_to_root . "/reporting/includes/pdf_report.inc");
        }
    }
	
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

    $cols = array(0, 12, 70, 115, 145, 165, 
        205, 255, 285, 325, 365, 400, 440, 495,
        530, 575, 615, 655, 700
    );

    if ($show_add == 1) {
        array_push($cols, 750, 800, 0);
    }
    else {
        array_push($cols, 0);
    }

    $aligns = array('left', 'left', 'left', 'center', 'center', 'center',
        'center', 'center', 'center', 'center', 'center', 'center', 'center',
        'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center'
    );

    if ($show_add == 1) {
        array_push($aligns, 'center', 'left');
    }

    $headers = array(
        _(""),
        _('Account Name'), 
        _('Model'), 
        _('Buy Date'), 
        _('Term'),
        _('Gross'),
        _('Down_Paymnt'),
        _('Adjstmnt'), 
        _('Rest_Adjmt'), 
        _('Payment'), 
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

    if ($show_add == 1) {
        array_push($headers, "Penalty", "Address");
    }
    
    $params = array(0 => $comments,
        1 => array('text' => _('As of Date'), 'from' => $date, 'to' => ''),
        2 => array('text' => _('Customer'), 'from' => $cust, 'to' => ''),
        3 => array('text' => _('Group By'), 'from' => $grp, 'to' => ''),
        //4 => array('text' => _('Sales Areas'), 'from' => $sarea, 'to' => ''),
    );
    
    $rep = new FrontReport(_('Aging Summary Report'), 
        "Aging_Summary_Detailed_" . "($date)", 
        'LEGAL', 9, $orientation
    );
    
    if ($orientation == 'L') {
        recalculate_cols($cols);
    }

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    
    if ($destination) {
        $rep->SetHeaderType('PO_Header');
    }
    else {
        $rep->SetHeaderType('PO_Header');     
    }
   
    $rep->NewPage();

    $res = get_AR_transactions($date, $customer, $group, $filter);
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

    $total_act = 0;

    while ($trans = db_fetch($res)) {

        $total_act++;
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
        $total_penalty = total_penalty($trans['trans_no'], $trans['trans_type'], $trans['debtor_no'], $trans['cur_date']);


        if ($group == 1) {
            if ($gl_name != $trans['gl_name']) {

                if ($gl_name != '') {
                    $rep->NewLine(2);
                    $rep->Font('bold');
				    $rep->TextCol(1, 2, _('Sub_Total'));
                    $rep->AmountCol(5, 6, $tot_gross, $dec);
                    $rep->AmountCol(6, 7, $tot_down, $dec);
                    $rep->AmountCol(7, 8, $tot_adj, $dec);
                    $rep->AmountCol(8, 9, $tot_rest, $dec);
                    $rep->AmountCol(9, 10, $tot_pay, $dec);
                    $rep->AmountCol(10, 11, $tot_adv, $dec);
                    $rep->AmountCol(11, 12, $tot_bal, $dec);
                    $rep->AmountCol(12, 13, $tot_notDue, $dec);
                    $rep->AmountCol(13, 14, $tot_dueNxt, $dec);
                    $rep->AmountCol(14, 15, $tot_dueThis, $dec);
                    $rep->SetTextColor(255, 0, 0);
                    $rep->AmountCol(15, 16, $tot_ovr1, $dec);
                    $rep->AmountCol(16, 17, $tot_ovr2, $dec);
                    $rep->SetTextColor(0, 0, 0);
                    $rep->AmountCol(17, 18, $tot_past, $dec);
                    $rep->AmountCol(18, 19, $tot_grand, $dec);
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
				    $rep->TextCol(1, 2, _('Sub_Total'));
                    $rep->AmountCol(5, 6, $tot_gross, $dec);
                    $rep->AmountCol(6, 7, $tot_down, $dec);
                    $rep->AmountCol(7, 8, $tot_adj, $dec);
                    $rep->AmountCol(8, 9, $tot_rest, $dec);
                    $rep->AmountCol(9, 10, $tot_pay, $dec);
                    $rep->AmountCol(10, 11, $tot_adv, $dec);
                    $rep->AmountCol(11, 12, $tot_bal, $dec);
                    $rep->AmountCol(12, 13, $tot_notDue, $dec);
                    $rep->AmountCol(13, 14, $tot_dueNxt, $dec);
                    $rep->AmountCol(14, 15, $tot_dueThis, $dec);
                    $rep->SetTextColor(255, 0, 0);
                    $rep->AmountCol(15, 16, $tot_ovr1, $dec);
                    $rep->AmountCol(16, 17, $tot_ovr2, $dec);
                    $rep->SetTextColor(0, 0, 0);
                    $rep->AmountCol(17, 18, $tot_past, $dec);
                    $rep->AmountCol(18, 19, $tot_grand, $dec);
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
				    $rep->TextCol(1, 2, _('Sub_Total'));
                    $rep->AmountCol(5, 6, $tot_gross, $dec);
                    $rep->AmountCol(6, 7, $tot_down, $dec);
                    $rep->AmountCol(7, 8, $tot_adj, $dec);
                    $rep->AmountCol(8, 9, $tot_rest, $dec);
                    $rep->AmountCol(9, 10, $tot_pay, $dec);
                    $rep->AmountCol(10, 11, $tot_adv, $dec);
                    $rep->AmountCol(11, 12, $tot_bal, $dec);
                    $rep->AmountCol(12, 13, $tot_notDue, $dec);
                    $rep->AmountCol(13, 14, $tot_dueNxt, $dec);
                    $rep->AmountCol(14, 15, $tot_dueThis, $dec);
                    $rep->SetTextColor(255, 0, 0);
                    $rep->AmountCol(15, 16, $tot_ovr1, $dec);
                    $rep->AmountCol(16, 17, $tot_ovr2, $dec);
                    $rep->SetTextColor(0, 0, 0);
                    $rep->AmountCol(17, 18, $tot_past, $dec);
                    $rep->AmountCol(18, 19, $tot_grand, $dec);
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

        $rep->TextCol(0, 1, $total_act . ".) ");
        $rep->TextCol(1, 2, $trans['cust_name']);
        $rep->TextCol(2, 3, debtor_stock_id($trans['trans_no'], $trans['trans_type']));
        $rep->SetTextColor(255, 0, 0);
        $rep->TextCol(3, 4, $trans['buy_date']);
        $rep->SetTextColor(0, 0, 0);
        $rep->TextCol(4, 5, $trans['Term']);
        $rep->AmountCol(5, 6, $trans['gross'], $dec);
        $rep->AmountCol(6, 7, $trans['down_pay'], $dec);
        $rep->AmountCol(7, 8, $total_adjusment, $dec);
        $rep->AmountCol(8, 9, $trans['restruct'], $dec);
        $rep->AmountCol(9, 10, $total_payment_this_month, $dec);
        $rep->AmountCol(10, 11, $advance_payment, $dec);
        $rep->AmountCol(11, 12, $current_balance, $dec);
        $rep->AmountCol(12, 13, $not_yet_due, $dec);
        $rep->AmountCol(13, 14, $due_nxt_month, $dec);
        $rep->AmountCol(14, 15, $due_this_month, $dec);
        $rep->SetTextColor(255, 0, 0);
        $rep->AmountCol(15, 16, $overdue_1month, $dec);
        $rep->AmountCol(16, 17, $overdue_2months, $dec);
        $rep->SetTextColor(0, 0, 0);
        $rep->AmountCol(17, 18, $past_due, $dec);
        $rep->AmountCol(18, 19, $total_collectibles, $dec);

        if ($show_add == 1) {
            $rep->SetTextColor(255, 0, 0);
            $rep->AmountCol(19, 20, $total_penalty, $dec);
            $rep->SetTextColor(0, 0, 0);
            $rep->TextCol(20, 21, $trans['address']);
        }

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
            $rep->TextCol(1, 2, _('Sub_Total'));
            $rep->AmountCol(5, 6, $tot_gross, $dec);
            $rep->AmountCol(6, 7, $tot_down, $dec);
            $rep->AmountCol(7, 8, $tot_adj, $dec);
            $rep->AmountCol(8, 9, $tot_rest, $dec);
            $rep->AmountCol(9, 10, $tot_pay, $dec);
            $rep->AmountCol(10, 11, $tot_adv, $dec);
            $rep->AmountCol(11, 12, $tot_bal, $dec);
            $rep->AmountCol(12, 13, $tot_notDue, $dec);
            $rep->AmountCol(13, 14, $tot_dueNxt, $dec);
            $rep->AmountCol(14, 15, $tot_dueThis, $dec);
            $rep->SetTextColor(255, 0, 0);
            $rep->AmountCol(15, 16, $tot_ovr1, $dec);
            $rep->AmountCol(16, 17, $tot_ovr2, $dec);
            $rep->SetTextColor(0, 0, 0);
            $rep->AmountCol(17, 18, $tot_past, $dec);
            $rep->AmountCol(18, 19, $tot_grand, $dec);
            $rep->Line($rep->row  - 4);
            $rep->Font();
        }
    }
    else if ($group == 2) {
        if ($col_name != '') {
            $rep->NewLine(2);
            $rep->Font('bold');
            $rep->TextCol(1, 2, _('Sub_Total'));
            $rep->AmountCol(5, 6, $tot_gross, $dec);
            $rep->AmountCol(6, 7, $tot_down, $dec);
            $rep->AmountCol(7, 8, $tot_adj, $dec);
            $rep->AmountCol(8, 9, $tot_rest, $dec);
            $rep->AmountCol(9, 10, $tot_pay, $dec);
            $rep->AmountCol(10, 11, $tot_adv, $dec);
            $rep->AmountCol(11, 12, $tot_bal, $dec);
            $rep->AmountCol(12, 13, $tot_notDue, $dec);
            $rep->AmountCol(13, 14, $tot_dueNxt, $dec);
            $rep->AmountCol(14, 15, $tot_dueThis, $dec);
            $rep->SetTextColor(255, 0, 0);
            $rep->AmountCol(15, 16, $tot_ovr1, $dec);
            $rep->AmountCol(16, 17, $tot_ovr2, $dec);
            $rep->SetTextColor(0, 0, 0);
            $rep->AmountCol(17, 18, $tot_past, $dec);
            $rep->AmountCol(18, 19, $tot_grand, $dec);
            $rep->Line($rep->row  - 4);
            $rep->Font();
        }
    }
    else {
        if ($area_name != '') {
            $rep->NewLine(2);
            $rep->Font('bold');
            $rep->TextCol(1, 2, _('Sub_Total'));
            $rep->AmountCol(5, 6, $tot_gross, $dec);
            $rep->AmountCol(6, 7, $tot_down, $dec);
            $rep->AmountCol(7, 8, $tot_adj, $dec);
            $rep->AmountCol(8, 9, $tot_rest, $dec);
            $rep->AmountCol(9, 10, $tot_pay, $dec);
            $rep->AmountCol(10, 11, $tot_adv, $dec);
            $rep->AmountCol(11, 12, $tot_bal, $dec);
            $rep->AmountCol(12, 13, $tot_notDue, $dec);
            $rep->AmountCol(13, 14, $tot_dueNxt, $dec);
            $rep->AmountCol(14, 15, $tot_dueThis, $dec);
            $rep->SetTextColor(255, 0, 0);
            $rep->AmountCol(15, 16, $tot_ovr1, $dec);
            $rep->AmountCol(16, 17, $tot_ovr2, $dec);
            $rep->SetTextColor(0, 0, 0);
            $rep->AmountCol(17, 18, $tot_past, $dec);
            $rep->AmountCol(18, 19, $tot_grand, $dec);
            $rep->Line($rep->row  - 4);
            $rep->Font();
        }
    }

    $rep->NewLine(2.5);
    $rep->Font('bold');
    $rep->fontSize += 2;
	$rep->TextCol(1, 2, _('GRAND TOTAL:'));
    $rep->fontSize -= 2;

    $rep->AmountCol(5, 6, $tot1_gross, $dec);
    $rep->AmountCol(6, 7, $tot1_down, $dec);
    $rep->AmountCol(7, 8, $tot1_adj, $dec);
    $rep->AmountCol(8, 9, $tot1_rest, $dec);
    $rep->AmountCol(9, 10, $tot1_pay, $dec);
    $rep->AmountCol(10, 11, $tot1_adv, $dec);
    $rep->AmountCol(11, 12, $tot1_bal, $dec);
    $rep->AmountCol(12, 13, $tot1_notDue, $dec);
    $rep->AmountCol(13, 14, $tot1_dueNxt, $dec);
    $rep->AmountCol(14, 15, $tot1_dueThis, $dec);
    $rep->SetTextColor(255, 0, 0);
    $rep->AmountCol(15, 16, $tot1_ovr1, $dec);
    $rep->AmountCol(16, 17, $tot1_ovr2, $dec);
    $rep->SetTextColor(0, 0, 0);
    $rep->AmountCol(17, 18, $tot1_past, $dec);
    $rep->AmountCol(18, 19, $tot1_grand, $dec);
    
	$rep->Line($rep->row  - 4);
    
	$rep->NewLine();
    $rep->End();
    
}