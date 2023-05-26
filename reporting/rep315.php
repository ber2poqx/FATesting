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
$page_security = 'SA_PROD_REP'; //Modified by spyrax10 18 Jun 2022
// ----------------------------------------------------------------
// $ Revision:  2.0 $
// Creator: RobertGwapo
// date:    2021-04-29
// Title:   Schedule Of Repo Report
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/taxes/tax_calc.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/inventory/includes/db/items_category_db.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/sysnames.inc");
include_once($path_to_root . "/includes/types.inc");
//----------------------------------------------------------------------------------------------------

print_PO_Report();

function get_repo_schedule($to)
{
    $to = date2sql($to);
    $sql = "SELECT MAX(YEAR(A.repo_date)) AS BYYEAR, A.*, F.tran_date AS orig_date, B.stock_id, B.serial_no, B.chassis_no, 
            CASE WHEN B.status = 0 THEN 'Available'
            ELSE 'Unavailable' END AS Status,
            C.brand, D.name AS Brand, E.name AS Customer_name, E.area,
            G.description AS CATEGORY, H.collectors_id, I.real_name AS Collector_Name,
            J.description AS COLOR, K.trans_no, K.debtor_no,
           

            IFNULL(PASTDUE.past_due_month, 0) AS past_due_payment,
            IFNULL(OVERDUE.over_due, 0) AS ovrdue_3month_payment,
            IFNULL(TOTAL_PARTIAL.total_deduction, 0) AS payment_partial,
            IFNULL(QTYOUT.QTY, 1),

            (SELECT SUM(X.payment_applied) FROM debtor_loan_ledger X
            LEFT JOIN debtor_loan_schedule XR ON XR.debtor_no = X.debtor_no AND XR.trans_no = X.trans_no AND XR.id = X.loansched_id
            WHERE X.debtor_no = F.debtor_no AND X.trans_no = F.trans_no AND XR.month_no != 0 AND XR.status = 'partial')
            total_partial

            FROM repo_accounts A
            LEFT JOIN repo_item_details B ON B.ar_trans_no = A.ar_trans_no 
            AND B.repo_id = A.id
            LEFT JOIN stock_master C ON C.stock_id = B.stock_id
            LEFT JOIN item_brand D ON D.id = C.brand
            LEFT JOIN debtors_master E ON E.debtor_no = A.debtor_no
            LEFT JOIN debtor_trans F ON F.trans_no = A.ar_trans_no 
            AND F.type = A.ar_trans_type
            LEFT JOIN stock_category G ON G.category_id = A.category_id 
            LEFT JOIN areas H ON H.area_code = E.area 
            LEFT JOIN users I ON I.user_id = H.collectors_id 
            LEFT JOIN item_codes J ON J.item_code = B.color_code 
            LEFT JOIN debtor_loans K ON K.trans_no = A.ar_trans_no AND K.invoice_type = 'new'
            

            LEFT JOIN (
            SELECT EEY.id, EEY.trans_no, EEY.debtor_no, AAY.maturity_date, 
            SUM(EEY.principal_due) AS past_due_month
            FROM debtor_loan_schedule EEY 
            INNER JOIN debtor_loans AAY ON EEY.trans_no = AAY.trans_no AND EEY.debtor_no AND AAY.debtor_no AND AAY.invoice_type = 'new'
            WHERE AAY.maturity_date < '$to' AND 
            EEY.status != 'paid' GROUP BY EEY.debtor_no, EEY.trans_no) PASTDUE ON F.trans_no = PASTDUE.trans_no AND F.debtor_no = PASTDUE.debtor_no

            LEFT JOIN (
            SELECT EET.id, EET.trans_no, EET.debtor_no,  
                SUM(EET.principal_due) AS over_due
            FROM debtor_loan_schedule EET 
            LEFT JOIN repo_accounts GGWP ON EET.trans_no = GGWP.ar_trans_no AND EET.debtor_no = GGWP.debtor_no 
            AND EET.trans_type = GGWP.ar_trans_type
            WHERE DATE_FORMAT(EET.date_due, '%Y-%m') <= DATE_FORMAT('$to', '%Y-%m') AND 
            EET.status != 'paid' AND YEAR(GGWP.repo_date) >= YEAR(EET.date_due)
            GROUP BY EET.debtor_no, EET.trans_no) OVERDUE ON F.trans_no = OVERDUE.trans_no AND F.debtor_no = OVERDUE.debtor_no

            LEFT JOIN (
            SELECT FF.id, FF.trans_no, FF.debtor_no, FF.loansched_id, FF.date_paid, 
            SUM(FF.payment_applied) AS total_deduction, 
            EE.date_due
            FROM debtor_loan_ledger FF
            LEFT JOIN debtor_loan_schedule EE ON FF.loansched_id = EE.id AND FF.trans_no = EE.trans_no 
            AND FF.debtor_no = EE.debtor_no
            WHERE EE.month_no != 0 AND EE.status = 'partial'
            GROUP BY DATE_FORMAT(EE.date_due, '%Y-%m')
            ) TOTAL_PARTIAL ON F.trans_no = TOTAL_PARTIAL.trans_no AND F.debtor_no = TOTAL_PARTIAL.debtor_no


           	LEFT JOIN (
			SELECT L.stock_id, L.lot_no, 
			SUM(L.qty) AS QTY, BB.serial_no, CC.trans_date
			FROM stock_moves L
            LEFT JOIN repo_item_details BB ON L.stock_id = BB.stock_id AND L.lot_no = BB.serial_no
			LEFT JOIN repo_accounts CC ON CC.ar_trans_no = BB.ar_trans_no AND CC.id = BB.repo_id
			WHERE L.item_type = 'repo' AND DATE_FORMAT(L.tran_date, '%Y-%m-%d') <= DATE_FORMAT('$to', '%Y-%m-%d')
			GROUP BY L.stock_id, L.lot_no
			) QTYOUT ON B.stock_id = QTYOUT.stock_id AND B.serial_no = QTYOUT.lot_no

            WHERE IFNULL(QTYOUT.QTY, 1) <> 0
            AND A.repo_date <= '$to'";

            if ($group == 0) {
                $sql .= "GROUP BY A.reference_no, A.ar_trans_no, A.debtor_no";                
                $sql .= " ORDER BY A.repo_date DESC";

            }else if ($group == 1){
                $sql .= "GROUP BY A.reference_no, A.ar_trans_no, A.debtor_no";                 
                $sql .= " ORDER BY A.repo_date DESC";
            }else{
            	$sql .= "GROUP BY A.reference_no, A.ar_trans_no, A.debtor_no";                 
                $sql .= " ORDER BY A.repo_date DESC";
            }


    return db_query($sql, "could not query stock moves");
}

function print_PO_Report()
{
    global $path_to_root, $SysPrefs;

    $to         = $_POST['PARAM_0'];
    $group      = $_POST['PARAM_1'];
    $orientation= $_POST['PARAM_2'];
    $destination= $_POST['PARAM_3'];

    if ($destination)
        include_once($path_to_root . "/reporting/includes/excel_report.inc");
    else
        include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    if ($group == 0) {
        $grp = _('CATEGORY');
    }
    else if ($group == 1) {
        $grp = _('COLLECTOR');
    } else {
        $grp = _('YEAR');
    }

    $orientation = 'L';

    $brchcode = $db_connections[user_company()]["branch_code"];
    
    $dec = user_price_dec();

    $params = array(0 => $comments,
        1 => array('text' => _('As Of Date'),'from' => $to, 'to' => ''),
        2 => array('text' => _('Group By'), 'from' => $grp, 'to' => '')); 

    $cols = array(0, 37, 75, 135, 140, 198, 240, 245, 305, 310, 385, 390, 
    465, 470, 545, 550, 590, 630, 670, 700, 705);

    $headers = array(
        _('Repo Date'), 
        _('Orig Date'),
        _('Customer Name'),
        _(''),
        _('RR Num'),
        _('Brand'),
        _(''),
        _('Model'),
        _(''),
        _('Color'),
        _(''),
        _('Serial/Engine'),
        _(''),
        _('Chasis Number'),
        _(''),
        _('Month.Amort'),
        _('Balance'),
        _('Over Due'),
        _('Past Due'),
        _(''),
        _('Unrecovered'));

    $aligns = array('left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left',
    'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left');

     $rep = new FrontReport(_('Schedule Of Repo Report'), "ScheduleOfRepoReport", "Legal", 9, $orientation);
    if ($orientation == 'L')
        recalculate_cols($cols);

    
    $rep->fontSize -= 1;
    $rep->Info($params, $cols, $headers, $aligns, 
        null, null, null, true, true, true);
    //$rep->SetHeaderType('COLLECTION_Header');
    if ($destination) {
        $rep->SetHeaderType('PO_Header');
    }
    else {
        $rep->SetHeaderType('COLLECTION_Header');     
    }
    $rep->NewPage();

    $Balancesubtotal = $unrecoveredsubtotal = $overduesubtotal = $pastduesubtotal = 0.0;
    $Balancegrandtotal = $unrecoveredgrandtotal = $overduegrandtotal = $pastduegrandtotal = 0.0;

    $res = get_repo_schedule($to);
    $Collector_Name = $CATEGORY = $BYYEAR = '';

    while ($ACRBCR = db_fetch($res))
    {

    	if ($group == 0) {
            if ($CATEGORY != $ACRBCR['CATEGORY']) {

                if ($CATEGORY != '') {
                    $rep->NewLine(2);
                    $rep->Font('bold');
                    $rep->TextCol(13, 15, _('Sub Total'));
                    $rep->AmountCol(16, 17, $Balancesubtotal, $dec);
                    $rep->AmountCol(17, 18, $overduesubtotal, $dec);
                    $rep->AmountCol(18, 19, $pastduesubtotal, $dec);
                    $rep->AmountCol(20, 21, $unrecoveredsubtotal, $dec);
                    $rep->Line($rep->row  - 4);
                    $rep->NewLine(2);
                    $rep->Font();

                    $overduesubtotal = $Balancesubtotal = $pastduesubtotal = $unrecoveredsubtotal = 0.0;
                }

                $rep->Font('bold');
                $rep->SetTextColor(0, 0, 255);
                $rep->TextCol(0, 5, $ACRBCR['CATEGORY']);
                $CATEGORY = $ACRBCR['CATEGORY'];
                $rep->Font();
                $rep->SetTextColor(0, 0, 0);
                $rep->NewLine();    
            }
        } else if ($group == 1){
            if ($Collector_Name != $ACRBCR['Collector_Name']) {

                if ($Collector_Name != '') {
                    $rep->NewLine(2);
                    $rep->Font('bold');
                    $rep->TextCol(13, 15, _('Sub Total'));
                    $rep->AmountCol(16, 17, $Balancesubtotal, $dec);
                    $rep->AmountCol(17, 18, $overduesubtotal, $dec);
                    $rep->AmountCol(18, 19, $pastduesubtotal, $dec);
                    $rep->AmountCol(20, 21, $unrecoveredsubtotal, $dec);
                    $rep->Line($rep->row  - 4);
                    $rep->NewLine(2);
                    $rep->Font();

                    $overduesubtotal = $Balancesubtotal = $pastduesubtotal = $unrecoveredsubtotal = 0.0;                
                }
    
                $rep->Font('bold');
                $rep->SetTextColor(0, 0, 255);
                $rep->TextCol(0, 5, $ACRBCR['Collector_Name']);
                $Collector_Name = $ACRBCR['Collector_Name'];
                $rep->Font();
                $rep->SetTextColor(0, 0, 0);
                $rep->NewLine();    
            }
        } else {
        	if ($BYYEAR != $ACRBCR['BYYEAR']) {

                if ($BYYEAR != '') {
                    $rep->NewLine(2);
                    $rep->Font('bold');
                    $rep->TextCol(13, 15, _('Sub Total'));
                    $rep->AmountCol(16, 17, $Balancesubtotal, $dec);
                    $rep->AmountCol(17, 18, $overduesubtotal, $dec);
                    $rep->AmountCol(18, 19, $pastduesubtotal, $dec);
                    $rep->AmountCol(20, 21, $unrecoveredsubtotal, $dec);
                    $rep->Line($rep->row  - 4);
                    $rep->NewLine(2);
                    $rep->Font();

                    $overduesubtotal = $Balancesubtotal = $pastduesubtotal = $unrecoveredsubtotal = 0.0;           
                }
    
                $rep->Font('bold');
                $rep->SetTextColor(0, 0, 255);
                $rep->TextCol(0, 5, $ACRBCR['BYYEAR']);
                $BYYEAR = $ACRBCR['BYYEAR'];
                $rep->Font();
                $rep->SetTextColor(0, 0, 0);
                $rep->NewLine();    
            }
        }

        if ($ACRBCR['past_due_payment'] == 0) {
        	$overdue = $ACRBCR['ovrdue_3month_payment'] - $ACRBCR['total_partial'];
        } else {
        	$overdue = 0;
        }

        if ($ACRBCR['past_due_payment'] == 0) {
        	$pastdue = $ACRBCR['past_due_payment'];
        } else {
        	$pastdue = $ACRBCR['past_due_payment'] - $ACRBCR['total_partial'];
        }

        $rep->NewLine(1);
        $rep->TextCol(0, 1, sql2date($ACRBCR['repo_date']));
        $rep->TextCol(1, 2, sql2date($ACRBCR['orig_date']));
        $rep->TextCol(2, 3, $ACRBCR["Customer_name"]);
        $rep->TextCol(3, 4, $ACRBCR['']);
        $rep->TextCol(4, 5, $ACRBCR["reference_no"]);
        $rep->TextCol(5, 6, $ACRBCR["Brand"]);
        $rep->TextCol(6, 7, $ACRBCR['']);
        $rep->TextCol(7, 8, $ACRBCR['stock_id']);
        $rep->TextCol(8, 9, $ACRBCR['']);
        $rep->TextCol(9, 10, $ACRBCR['COLOR']);
        $rep->TextCol(10, 11, $ACRBCR['']);
        $rep->TextCol(11, 12, $ACRBCR['serial_no']);
        $rep->TextCol(12, 13, $ACRBCR['']);
        $rep->TextCol(13, 14, $ACRBCR['chassis_no']);
        $rep->TextCol(14, 15, $ACRBCR['']); 
        $rep->AmountCol(15, 16, $ACRBCR['monthly_amount'], $dec); 
        $rep->AmountCol(16, 17, $ACRBCR['balance'], $dec); 
        $rep->AmountCol(17, 18, $overdue, $dec);
        $rep->AmountCol(18, 19, $pastdue, $dec);
        $rep->TextCol(19, 20, $ACRBCR['']);
        $rep->AmountCol(20, 21, $ACRBCR['unrecovered_cost'], $dec);
        $rep->NewLine(1);


        $Balancesubtotal += $ACRBCR['balance'];
        $Balancegrandtotal += $ACRBCR['balance'];

        $unrecoveredsubtotal += $ACRBCR['unrecovered_cost'];
        $unrecoveredgrandtotal += $ACRBCR['unrecovered_cost'];

        $pastduesubtotal += $pastdue;
        $pastduegrandtotal += $pastdue;

        $overduesubtotal += $overdue;
        $overduegrandtotal += $overdue;
    }

    if ($group == 0) {
            if ($CATEGORY != $ACRBCR['CATEGORY']) {

                if ($CATEGORY != '') {
                    $rep->NewLine(2);
                    $rep->Font('bold');
                    $rep->TextCol(13, 15, _('Sub Total'));
                    $rep->AmountCol(16, 17, $Balancesubtotal, $dec);
                    $rep->AmountCol(17, 18, $overduesubtotal, $dec);
                    $rep->AmountCol(18, 19, $pastduesubtotal, $dec);
                    $rep->AmountCol(20, 21, $unrecoveredsubtotal, $dec);
                    $rep->Line($rep->row  - 4);
                    $rep->NewLine(2);
                    $rep->Font();

                    $overduesubtotal = $Balancesubtotal = $pastduesubtotal = $unrecoveredsubtotal = 0.0;                           
                }

                $rep->Font('bold');
                $rep->SetTextColor(0, 0, 255);
                $rep->TextCol(0, 5, $ACRBCR['CATEGORY']);
                $CATEGORY = $ACRBCR['CATEGORY'];
                $rep->Font();
                $rep->SetTextColor(0, 0, 0);
                $rep->NewLine();    
            }
        } else if ($group == 1){
            if ($Collector_Name != $ACRBCR['Collector_Name']) {

                if ($Collector_Name != '') {
                    $rep->NewLine(2);
                    $rep->Font('bold');
                    $rep->TextCol(13, 15, _('Sub Total'));
                    $rep->AmountCol(16, 17, $Balancesubtotal, $dec);
                    $rep->AmountCol(17, 18, $overduesubtotal, $dec);
                    $rep->AmountCol(18, 19, $pastduesubtotal, $dec);
                    $rep->AmountCol(20, 21, $unrecoveredsubtotal, $dec);
                    $rep->Line($rep->row  - 4);
                    $rep->NewLine(2);
                    $rep->Font();

                    $overduesubtotal = $Balancesubtotal = $pastduesubtotal = $unrecoveredsubtotal = 0.0;           
                }
    
                $rep->Font('bold');
                $rep->SetTextColor(0, 0, 255);
                $rep->TextCol(0, 5, $ACRBCR['Collector_Name']);
                $Collector_Name = $ACRBCR['Collector_Name'];
                $rep->Font();
                $rep->SetTextColor(0, 0, 0);
                $rep->NewLine();    
            }
        } else {
        	if ($BYYEAR != $ACRBCR['BYYEAR']) {

                if ($BYYEAR != '') {
                    $rep->NewLine(2);
                    $rep->Font('bold');
                    $rep->TextCol(13, 15, _('Sub Total'));
                    $rep->AmountCol(16, 17, $Balancesubtotal, $dec);
                    $rep->AmountCol(17, 18, $overduesubtotal, $dec);
                    $rep->AmountCol(18, 19, $pastduesubtotal, $dec);
                    $rep->AmountCol(20, 21, $unrecoveredsubtotal, $dec);
                    $rep->Line($rep->row  - 4);
                    $rep->NewLine(2);
                    $rep->Font();

                    $overduesubtotal = $Balancesubtotal = $pastduesubtotal = $unrecoveredsubtotal = 0.0;                
                }
    
                $rep->Font('bold');
                $rep->SetTextColor(0, 0, 255);
                $rep->TextCol(0, 5, $ACRBCR['BYYEAR']);
                $BYYEAR = $ACRBCR['BYYEAR'];
                $rep->Font();
                $rep->SetTextColor(0, 0, 0);
                $rep->NewLine();    
            }
        }

    $rep->NewLine(0);
    $rep->Line($rep->row - 2);
    $rep->Font('bold');
    $rep->fontSize += 0;    
    $rep->TextCol(13, 15, _('Grand Total'));
  	$rep->AmountCol(16, 17, $Balancegrandtotal, $dec);
    $rep->AmountCol(17, 18, $overduegrandtotal, $dec);
    $rep->AmountCol(18, 19, $pastduegrandtotal, $dec);
    $rep->AmountCol(20, 21, $unrecoveredgrandtotal, $dec);
    $rep->End();
}