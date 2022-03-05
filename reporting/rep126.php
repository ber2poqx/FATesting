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
$page_security = 'SA_SALESANALYTIC';
// ----------------------------------------------------------------
// $ Revision:  2.0 $
// Creator: RobertGwapo
// date:    2021-04-29
// Title:   Aging Collectors Report
// ----------------------------------------------------------------

/**
 * Note: Update the variable $group
 * Reason: Function group_list() deleted
 * Updated By: spyrax10
 * Updated Date: 21 Mar 2022 
 */

$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/taxes/tax_calc.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/inventory/includes/db/items_category_db.inc");

//----------------------------------------------------------------------------------------------------

print_PO_Report();

function getTransactions($to, $cust_name = "", $group = 0)
{
    $to = date2sql($to);
    $advanceDate = endCycle($to, 1);
    $backoneDate = endCycle($to, -1);
    $backtwoDate = endCycle($to, -2);
    $backthreeDate = endCycle($to, -3);



    $sql ="SELECT B.name, B.address, B.area, C.reference, C.module_type, A.trans_no, A.debtor_no, A.invoice_date, A.months_term, 
            A.firstdue_date, A.maturity_date AS Maturity, A.rebate AS Rebate, A.ar_amount AS amount, D.stock_id, E.principal_due,
            F.id, F.loansched_id, F.date_paid, G.real_name AS Collector_Name, H.debtor_no, 
            I.account_code, I.account_name AS Coa_name, J.collectors_id, K.description AS AREA,
            
            EEE.principal_due - IFNULL(DUE_NXT_MNTH.adv_payment, 0) AS due_nxt_month_payment,

            EEETH.principal_due - IFNULL(DUE_THIS_MNTH.adv_payment_this_month, 0) - IFNULL(DUE_THIS_MNTH_V2.adv_payment_this_month, 0)
            AS due_this_month_payment,

            EEEE.principal_due - IFNULL(DUE_ONE_MNTH.adv_payment_due_1, 0) - IFNULL(DUE_ONE_MNTH_V2.adv_payment_due_1, 0) -
            IFNULL(DUE_ONE_MNTH_V1.adv_payment_due_1, 0) AS ovrdue_1month_payment,

            EEEEE.principal_due - IFNULL(DUE_TWO_MNTH.adv_payment_due_2, 0) - IFNULL(DUE_TWO_MNTH_V1.adv_payment_due_2, 0) - 
            IFNULL(DUE_TWO_MNTH_V2.adv_payment_due_2, 0) - IFNULL(DUE_TWO_MNTH_V3.adv_payment_due_2, 0) AS ovrdue_2month_payment,

            /*
            IFNULL(FINAL_MINUS_MNTH.final_minus_month, 0) AS final_partial_deduction,
            */
            IFNULL(DUE_THREE_MNTH.adv_payment_due_3, 0) AS ovrdue_3month_payment,
            IFNULL(PASTDUE.past_due_month, 0) AS past_due_payment,
            IFNULL(-REMAINBAL.REMAIN, A.ar_amount) AS BALANCE,

            (SELECT SUM(X.payment_applied) FROM debtor_loan_ledger X
            LEFT JOIN debtor_loan_schedule XR ON XR.debtor_no = X.debtor_no AND XR.trans_no = X.trans_no AND XR.id = X.loansched_id
            WHERE X.debtor_no = A.debtor_no AND X.trans_no = A.trans_no)
            total_partial0,

            (SELECT SUM(X.payment_applied) - Y.ar_amount FROM debtor_loan_ledger X 
            LEFT JOIN debtor_loans Y ON Y.debtor_no = X.debtor_no AND Y.trans_no = X.trans_no WHERE X.debtor_no = A.debtor_no 
            AND X.trans_no = A.trans_no)
            Balance,

            (SELECT XY.date_due FROM debtor_loan_schedule XY WHERE XY.debtor_no = A.debtor_no AND XY.trans_no = A.trans_no 
            AND XY.status != 'unpaid' ORDER BY XY.date_due DESC LIMIT 1)
            last_month_applied,

            (SELECT XJ.date_paid FROM debtor_loan_ledger XJ LEFT JOIN debtor_loan_schedule XB ON XB.debtor_no = XJ.debtor_no 
             AND XB.trans_no = XJ.trans_no AND XB.id = XJ.loansched_id
             WHERE XB.debtor_no = A.debtor_no AND XB.trans_no = A.trans_no 
            AND XB.status != 'unpaid' ORDER BY XJ.date_paid DESC LIMIT 1)
            last_payment

            FROM debtor_loans A
            LEFT JOIN debtors_master B ON B.debtor_no = A.debtor_no
            LEFT JOIN debtor_trans C ON C.trans_no = A.trans_no AND C.debtor_no = A.debtor_no
            LEFT JOIN debtor_trans_details D ON D.debtor_trans_no = C.trans_no
            LEFT JOIN debtor_loan_schedule E ON E.trans_no = A.trans_no AND E.debtor_no = A.debtor_no
            LEFT JOIN debtor_loan_ledger F ON F.trans_no = E.trans_no AND F.debtor_no = E.debtor_no 
            AND F.debtor_no = A.debtor_no AND F.trans_no = A.trans_no
            LEFT JOIN areas J ON J.area_code = B.area
            LEFT JOIN users G ON G.user_id = J.collectors_id
            LEFT JOIN cust_branch H ON H.debtor_no = A.debtor_no
            LEFT JOIN chart_master I ON I.account_code = H.receivables_account
            LEFT JOIN areas K ON K.area_code = B.area


            LEFT JOIN (
                SELECT FF.id, FF.trans_no, FF.debtor_no, FF.loansched_id, FF.date_paid, EE.date_due, 
                    SUM(FF.payment_applied) as adv_payment
                    FROM debtor_loan_ledger FF
                        INNER JOIN debtor_loan_schedule EE ON FF.loansched_id = EE.id AND FF.trans_no = EE.trans_no 
                            AND FF.debtor_no = EE.debtor_no AND EE.month_no <> 0
                    WHERE DATE_FORMAT(EE.date_due, '%Y-%m') = DATE_FORMAT('$advanceDate', '%Y-%m')
                    GROUP BY DATE_FORMAT(FF.date_paid, '%Y-%m'), FF.trans_no, FF.debtor_no
            ) DUE_NXT_MNTH ON C.trans_no = DUE_NXT_MNTH.trans_no AND C.debtor_no = DUE_NXT_MNTH.debtor_no 
                AND DATE_FORMAT(DUE_NXT_MNTH.date_paid, '%Y-%m') = DATE_FORMAT(DATE_ADD('$to', INTERVAL -1 MONTH), '%Y-%m')

            LEFT JOIN (
            SELECT FF.id, FF.trans_no, FF.debtor_no, FF.loansched_id, FF.date_paid, EE.date_due, 
                SUM(FF.payment_applied) as adv_payment_this_month
                FROM debtor_loan_ledger FF
                    INNER JOIN debtor_loan_schedule EE ON FF.loansched_id = EE.id AND FF.trans_no = EE.trans_no 
                        AND FF.debtor_no = EE.debtor_no AND EE.month_no <> 0
                WHERE DATE_FORMAT(EE.date_due, '%Y-%m') = DATE_FORMAT('$to', '%Y-%m')
                GROUP BY DATE_FORMAT(FF.date_paid, '%Y-%m'), FF.trans_no, FF.debtor_no
            ) DUE_THIS_MNTH ON C.trans_no = DUE_THIS_MNTH.trans_no AND C.debtor_no = DUE_THIS_MNTH.debtor_no 
            AND DATE_FORMAT(DUE_THIS_MNTH.date_paid, '%Y-%m') = DATE_FORMAT(DATE_ADD('$to', INTERVAL -1 MONTH), '%Y-%m')

            LEFT JOIN (
            SELECT FF.id, FF.trans_no, FF.debtor_no, FF.loansched_id, FF.date_paid, EE.date_due, 
                SUM(FF.payment_applied) as adv_payment_this_month
                FROM debtor_loan_ledger FF
                    INNER JOIN debtor_loan_schedule EE ON FF.loansched_id = EE.id AND FF.trans_no = EE.trans_no 
                        AND FF.debtor_no = EE.debtor_no AND EE.month_no <> 0
                WHERE DATE_FORMAT(EE.date_due, '%Y-%m') = DATE_FORMAT('$to', '%Y-%m')
                GROUP BY DATE_FORMAT(FF.date_paid, '%Y-%m'), FF.trans_no, FF.debtor_no
            ) DUE_THIS_MNTH_V2 ON C.trans_no = DUE_THIS_MNTH_V2.trans_no AND C.debtor_no = DUE_THIS_MNTH_V2.debtor_no 
            AND DATE_FORMAT(DUE_THIS_MNTH_V2.date_paid, '%Y-%m') = DATE_FORMAT(DATE_ADD('$to', INTERVAL -2 MONTH), '%Y-%m')

            LEFT JOIN (
            SELECT FF.id, FF.trans_no, FF.debtor_no, FF.loansched_id, FF.date_paid, EE.date_due, 
                SUM(FF.payment_applied) as adv_payment_due_1
                FROM debtor_loan_ledger FF
                    INNER JOIN debtor_loan_schedule EE ON FF.loansched_id = EE.id AND FF.trans_no = EE.trans_no 
                        AND FF.debtor_no = EE.debtor_no AND EE.month_no <> 0
                WHERE DATE_FORMAT(EE.date_due, '%Y-%m') = DATE_FORMAT(DATE_ADD('$to', INTERVAL -1 MONTH), '%Y-%m')
                GROUP BY DATE_FORMAT(FF.date_paid, '%Y-%m'), FF.trans_no, FF.debtor_no
            ) DUE_ONE_MNTH ON C.trans_no = DUE_ONE_MNTH.trans_no AND C.debtor_no = DUE_ONE_MNTH.debtor_no 
            AND DATE_FORMAT(DUE_ONE_MNTH.date_paid, '%Y-%m') = DATE_FORMAT(DATE_ADD('$to', INTERVAL -2 MONTH), '%Y-%m')

            LEFT JOIN (
            SELECT FF.id, FF.trans_no, FF.debtor_no, FF.loansched_id, FF.date_paid, EE.date_due, 
               SUM(FF.payment_applied) as adv_payment_due_1
                FROM debtor_loan_ledger FF
                    INNER JOIN debtor_loan_schedule EE ON FF.loansched_id = EE.id AND FF.trans_no = EE.trans_no 
                        AND FF.debtor_no = EE.debtor_no AND EE.month_no <> 0
                WHERE DATE_FORMAT(EE.date_due, '%Y-%m') = DATE_FORMAT(DATE_ADD('$to', INTERVAL -1 MONTH), '%Y-%m')
                GROUP BY DATE_FORMAT(FF.date_paid, '%Y-%m'), FF.trans_no, FF.debtor_no
            ) DUE_ONE_MNTH_V1 ON C.trans_no = DUE_ONE_MNTH_V1.trans_no AND C.debtor_no = DUE_ONE_MNTH_V1.debtor_no 
            AND DATE_FORMAT(DUE_ONE_MNTH_V1.date_paid, '%Y-%m') = DATE_FORMAT(DATE_ADD('$to', INTERVAL -1 MONTH), '%Y-%m')

            LEFT JOIN (
            SELECT FF.id, FF.trans_no, FF.debtor_no, FF.loansched_id, FF.date_paid, EE.date_due, 
                SUM(FF.payment_applied) as adv_payment_due_1
                FROM debtor_loan_ledger FF
                    INNER JOIN debtor_loan_schedule EE ON FF.loansched_id = EE.id AND FF.trans_no = EE.trans_no 
                        AND FF.debtor_no = EE.debtor_no AND EE.month_no <> 0
                WHERE DATE_FORMAT(EE.date_due, '%Y-%m') = DATE_FORMAT(DATE_ADD('$to', INTERVAL -1 MONTH), '%Y-%m')
                GROUP BY DATE_FORMAT(FF.date_paid, '%Y-%m'), FF.trans_no, FF.debtor_no
            ) DUE_ONE_MNTH_V2 ON C.trans_no = DUE_ONE_MNTH_V2.trans_no AND C.debtor_no = DUE_ONE_MNTH_V2.debtor_no 
            AND DATE_FORMAT(DUE_ONE_MNTH_V2.date_paid, '%Y-%m') = DATE_FORMAT(DATE_ADD('$to', INTERVAL -3 MONTH), '%Y-%m')

            LEFT JOIN (
            SELECT FF.id, FF.trans_no, FF.debtor_no, FF.loansched_id, FF.date_paid, EE.date_due, 
                SUM(FF.payment_applied) as adv_payment_due_2
                FROM debtor_loan_ledger FF
                    INNER JOIN debtor_loan_schedule EE ON FF.loansched_id = EE.id AND FF.trans_no = EE.trans_no 
                        AND FF.debtor_no = EE.debtor_no AND EE.month_no <> 0
                WHERE DATE_FORMAT(EE.date_due, '%Y-%m') = DATE_FORMAT(DATE_ADD('$to', INTERVAL -2 MONTH), '%Y-%m')
                GROUP BY DATE_FORMAT(FF.date_paid, '%Y-%m'), FF.trans_no, FF.debtor_no
            ) DUE_TWO_MNTH ON C.trans_no = DUE_TWO_MNTH.trans_no AND C.debtor_no = DUE_TWO_MNTH.debtor_no 
            AND DATE_FORMAT(DUE_TWO_MNTH.date_paid, '%Y-%m') = DATE_FORMAT(DATE_ADD('$to', INTERVAL -4 MONTH), '%Y-%m')

            LEFT JOIN (
            SELECT FF.id, FF.trans_no, FF.debtor_no, FF.loansched_id, FF.date_paid, EE.date_due, 
                SUM(FF.payment_applied) as adv_payment_due_2
                FROM debtor_loan_ledger FF
                    INNER JOIN debtor_loan_schedule EE ON FF.loansched_id = EE.id AND FF.trans_no = EE.trans_no 
                        AND FF.debtor_no = EE.debtor_no AND EE.month_no <> 0
                WHERE DATE_FORMAT(EE.date_due, '%Y-%m') = DATE_FORMAT(DATE_ADD('$to', INTERVAL -2 MONTH), '%Y-%m')
                GROUP BY DATE_FORMAT(FF.date_paid, '%Y-%m'), FF.trans_no, FF.debtor_no
            ) DUE_TWO_MNTH_V1 ON C.trans_no = DUE_TWO_MNTH_V1.trans_no AND C.debtor_no = DUE_TWO_MNTH_V1.debtor_no 
            AND DATE_FORMAT(DUE_TWO_MNTH_V1.date_paid, '%Y-%m') = DATE_FORMAT(DATE_ADD('$to', INTERVAL -3 MONTH), '%Y-%m')

            LEFT JOIN (
            SELECT FF.id, FF.trans_no, FF.debtor_no, FF.loansched_id, FF.date_paid, EE.date_due,    
                SUM(FF.payment_applied) as adv_payment_due_2
                FROM debtor_loan_ledger FF
                    INNER JOIN debtor_loan_schedule EE ON FF.loansched_id = EE.id AND FF.trans_no = EE.trans_no 
                        AND FF.debtor_no = EE.debtor_no AND EE.month_no <> 0
                WHERE DATE_FORMAT(EE.date_due, '%Y-%m') = DATE_FORMAT(DATE_ADD('$to', INTERVAL -2 MONTH), '%Y-%m')
                GROUP BY DATE_FORMAT(FF.date_paid, '%Y-%m'), FF.trans_no, FF.debtor_no
            ) DUE_TWO_MNTH_V2 ON C.trans_no = DUE_TWO_MNTH_V2.trans_no AND C.debtor_no = DUE_TWO_MNTH_V2.debtor_no 
            AND DATE_FORMAT(DUE_TWO_MNTH_V2.date_paid, '%Y-%m') = DATE_FORMAT(DATE_ADD('$to', INTERVAL -2 MONTH), '%Y-%m')

            LEFT JOIN (
            SELECT FF.id, FF.trans_no, FF.debtor_no, FF.loansched_id, FF.date_paid, EE.date_due,              
                SUM(FF.payment_applied) as adv_payment_due_2
                FROM debtor_loan_ledger FF
                    INNER JOIN debtor_loan_schedule EE ON FF.loansched_id = EE.id AND FF.trans_no = EE.trans_no 
                        AND FF.debtor_no = EE.debtor_no AND EE.month_no <> 0
                WHERE DATE_FORMAT(EE.date_due, '%Y-%m') = DATE_FORMAT(DATE_ADD('$to', INTERVAL -2 MONTH), '%Y-%m')
                GROUP BY DATE_FORMAT(FF.date_paid, '%Y-%m'), FF.trans_no, FF.debtor_no
            ) DUE_TWO_MNTH_V3 ON C.trans_no = DUE_TWO_MNTH_V3.trans_no AND C.debtor_no = DUE_TWO_MNTH_V3.debtor_no 
            AND DATE_FORMAT(DUE_TWO_MNTH_V3.date_paid, '%Y-%m') = DATE_FORMAT(DATE_ADD('$to', INTERVAL -1 MONTH), '%Y-%m')


            LEFT JOIN (
            SELECT FF.id, FF.trans_no, FF.debtor_no, FF.loansched_id, FF.date_paid, EE.date_due,              
                SUM(FF.payment_applied) as adv_payment_due_3
                FROM debtor_loan_ledger FF
                    INNER JOIN debtor_loan_schedule EE ON FF.loansched_id = EE.id AND FF.trans_no = EE.trans_no 
                        AND FF.debtor_no = EE.debtor_no AND EE.month_no <> 0
                WHERE DATE_FORMAT(EE.date_due, '%Y-%m') < DATE_FORMAT(DATE_ADD('$to', INTERVAL -2 MONTH), '%Y-%m')
                GROUP BY DATE_FORMAT(FF.date_paid, '%Y-%m'), FF.trans_no, FF.debtor_no
            ) DUE_THREE_MNTH ON C.trans_no = DUE_THREE_MNTH.trans_no AND C.debtor_no = DUE_THREE_MNTH.debtor_no 
            AND DATE_FORMAT(DUE_THREE_MNTH.date_paid, '%Y-%m') = DATE_FORMAT('$to', '%Y-%m')

            LEFT JOIN (
            SELECT FF.id, FF.trans_no, FF.debtor_no, FF.loansched_id, FF.date_paid, EE.date_due,              
                SUM(FF.payment_applied) as adv_payment_due_3
                FROM debtor_loan_ledger FF
                    INNER JOIN debtor_loan_schedule EE ON FF.loansched_id = EE.id AND FF.trans_no = EE.trans_no 
                        AND FF.debtor_no = EE.debtor_no AND EE.month_no <> 0
                WHERE DATE_FORMAT(EE.date_due, '%Y-%m') < DATE_FORMAT(DATE_ADD('$to', INTERVAL -2 MONTH), '%Y-%m')
                GROUP BY DATE_FORMAT(FF.date_paid, '%Y-%m'), FF.trans_no, FF.debtor_no
            ) DUE_THREE_MNTH_v1 ON C.trans_no = DUE_THREE_MNTH_v1.trans_no AND C.debtor_no = DUE_THREE_MNTH_v1.debtor_no 
            AND DATE_FORMAT(DUE_THREE_MNTH_v1.date_paid, '%Y-%m') = DATE_FORMAT(DATE_ADD('$to', INTERVAL -2 MONTH), '%Y-%m')

            LEFT JOIN (
            SELECT FF.id, FF.trans_no, FF.debtor_no, FF.loansched_id, FF.date_paid, EE.date_due,              
                SUM(FF.payment_applied) as adv_payment_due_3
                FROM debtor_loan_ledger FF
                    INNER JOIN debtor_loan_schedule EE ON FF.loansched_id = EE.id AND FF.trans_no = EE.trans_no 
                        AND FF.debtor_no = EE.debtor_no AND EE.month_no <> 0
                WHERE DATE_FORMAT(EE.date_due, '%Y-%m') < DATE_FORMAT(DATE_ADD('$to', INTERVAL -2 MONTH), '%Y-%m')
                GROUP BY DATE_FORMAT(FF.date_paid, '%Y-%m'), FF.trans_no, FF.debtor_no
            ) DUE_THREE_MNTH_v2 ON C.trans_no = DUE_THREE_MNTH_v2.trans_no AND C.debtor_no = DUE_THREE_MNTH_v2.debtor_no 
            AND DATE_FORMAT(DUE_THREE_MNTH_v2.date_paid, '%Y-%m') = DATE_FORMAT(DATE_ADD('$to', INTERVAL -3 MONTH), '%Y-%m')

            LEFT JOIN (
            SELECT FF.id, FF.trans_no, FF.debtor_no, FF.loansched_id, FF.date_paid, EE.date_due,              
                SUM(FF.payment_applied) as adv_payment_due_3
                FROM debtor_loan_ledger FF
                    INNER JOIN debtor_loan_schedule EE ON FF.loansched_id = EE.id AND FF.trans_no = EE.trans_no 
                        AND FF.debtor_no = EE.debtor_no AND EE.month_no <> 0
                WHERE DATE_FORMAT(EE.date_due, '%Y-%m') < DATE_FORMAT(DATE_ADD('$to', INTERVAL -2 MONTH), '%Y-%m')
                GROUP BY DATE_FORMAT(FF.date_paid, '%Y-%m'), FF.trans_no, FF.debtor_no
            ) DUE_THREE_MNTH_v3 ON C.trans_no = DUE_THREE_MNTH_v3.trans_no AND C.debtor_no = DUE_THREE_MNTH_v3.debtor_no 
            AND DATE_FORMAT(DUE_THREE_MNTH_v3.date_paid, '%Y-%m') = DATE_FORMAT(DATE_ADD('$to', INTERVAL -4 MONTH), '%Y-%m')

            LEFT JOIN (
            SELECT FF.id, FF.trans_no, FF.debtor_no, FF.loansched_id, FF.date_paid, EE.date_due,              
                SUM(FF.payment_applied) as adv_payment_due_3
                FROM debtor_loan_ledger FF
                    INNER JOIN debtor_loan_schedule EE ON FF.loansched_id = EE.id AND FF.trans_no = EE.trans_no 
                        AND FF.debtor_no = EE.debtor_no AND EE.month_no <> 0
                WHERE DATE_FORMAT(EE.date_due, '%Y-%m') < DATE_FORMAT(DATE_ADD('$to', INTERVAL -2 MONTH), '%Y-%m')
                GROUP BY DATE_FORMAT(FF.date_paid, '%Y-%m'), FF.trans_no, FF.debtor_no
            ) DUE_THREE_MNTH_v4 ON C.trans_no = DUE_THREE_MNTH_v4.trans_no AND C.debtor_no = DUE_THREE_MNTH_v4.debtor_no 
            AND DATE_FORMAT(DUE_THREE_MNTH_v4.date_paid, '%Y-%m') = DATE_FORMAT(DATE_ADD('$to', INTERVAL -5 MONTH), '%Y-%m')

            /*
            LEFT JOIN (
            SELECT FF.id, FF.trans_no, FF.debtor_no, FF.loansched_id, FF.date_paid, 
            SUM(FF.payment_applied) AS due_three_payment_minus_three, 
            EE.date_due
            FROM debtor_loan_ledger FF
            LEFT JOIN debtor_loan_schedule EE ON FF.loansched_id = EE.id AND FF.trans_no = EE.trans_no 
            AND FF.debtor_no = EE.debtor_no
            WHERE DATE_FORMAT(EE.date_due, '%Y-%m') < DATE_FORMAT('$backthreeDate', '%Y-%m') 
            AND EE.month_no != 0 AND EE.status = 'partial'
            GROUP BY DATE_FORMAT(EE.date_due, '%Y-%m') DESC LIMIT 1
            ) DUE_THREE_MINUS_MNTH ON C.trans_no = DUE_THREE_MINUS_MNTH.trans_no AND C.debtor_no = DUE_THREE_MINUS_MNTH.debtor_no

            LEFT JOIN (
            SELECT FF.id, FF.trans_no, FF.debtor_no, FF.loansched_id, FF.date_paid, 
            SUM(FF.payment_applied) AS final_minus_month, 
            EE.date_due
            FROM debtor_loan_ledger FF
            LEFT JOIN debtor_loan_schedule EE ON FF.loansched_id = EE.id AND FF.trans_no = EE.trans_no 
            AND FF.debtor_no = EE.debtor_no
            WHERE EE.month_no != 0 AND EE.status = 'partial'
            GROUP BY DATE_FORMAT(EE.date_due, '%Y-%m') DESC LIMIT 1
            ) FINAL_MINUS_MNTH ON C.trans_no = FINAL_MINUS_MNTH.trans_no AND C.debtor_no = FINAL_MINUS_MNTH.debtor_no
            */


            LEFT JOIN (
            SELECT EET.id, EET.trans_no, EET.debtor_no,  
                SUM(EET.principal_due) AS due_3_month
            FROM debtor_loan_schedule EET 
            WHERE DATE_FORMAT(EET.date_due, '%Y-%m') < DATE_FORMAT(DATE_ADD('$to', INTERVAL -2 MONTH), '%Y-%m')
            AND EET.month_no != 0 GROUP BY EET.debtor_no, EET.trans_no) OVRDUE3M ON C.trans_no = OVRDUE3M.trans_no AND C.debtor_no = OVRDUE3M.debtor_no  

            LEFT JOIN (
            SELECT EEY.id, EEY.trans_no, EEY.debtor_no, AAY.maturity_date, 
            SUM(EEY.principal_due) AS past_due_month
            FROM debtor_loan_schedule EEY 
            INNER JOIN debtor_loans AAY ON EEY.trans_no = AAY.trans_no AND EEY.debtor_no AND AAY.debtor_no
            WHERE DATE_FORMAT(AAY.maturity_date, '%Y-%m') <  DATE_FORMAT('$to', '%Y-%m') 
            GROUP BY EEY.debtor_no, EEY.trans_no) PASTDUE ON C.trans_no = PASTDUE.trans_no AND C.debtor_no = PASTDUE.debtor_no

            LEFT JOIN (
            SELECT JR.id, JR.trans_no, JR.debtor_no,
            SUM(JR.payment_applied) - RD.ar_amount AS REMAIN
            FROM debtor_loan_ledger JR
            LEFT JOIN debtor_loans RD ON RD.debtor_no = JR.debtor_no AND RD.trans_no = JR.trans_no
            WHERE JR.debtor_no = RD.debtor_no AND JR.trans_no = RD.trans_no
            GROUP BY RD.debtor_no, RD.trans_no
            ) REMAINBAL ON A.trans_no = REMAINBAL.trans_no AND A.debtor_no = REMAINBAL.debtor_no
               
            LEFT JOIN debtor_loan_schedule EEE ON C.trans_no = EEE.trans_no AND C.debtor_no = EEE.debtor_no 
            AND EEE.month_no != 0 AND DATE_FORMAT(EEE.date_due, '%Y-%m') = DATE_FORMAT('$advanceDate', '%Y-%m')

            LEFT JOIN debtor_loan_schedule EEEE ON C.trans_no = EEEE.trans_no AND C.debtor_no = EEEE.debtor_no 
            AND EEEE.month_no != 0 AND DATE_FORMAT(EEEE.date_due, '%Y-%m') = DATE_FORMAT('$backoneDate', '%Y-%m')

            LEFT JOIN debtor_loan_schedule EEEEE ON C.trans_no = EEEEE.trans_no AND C.debtor_no = EEEEE.debtor_no 
            AND EEEEE.month_no != 0 AND DATE_FORMAT(EEEEE.date_due, '%Y-%m') = DATE_FORMAT('$backtwoDate', '%Y-%m')

            LEFT JOIN debtor_loan_schedule EEETH ON C.trans_no = EEETH.trans_no AND C.debtor_no = EEETH.debtor_no 
            AND EEETH.month_no != 0 AND DATE_FORMAT(EEETH.date_due, '%Y-%m') = DATE_FORMAT('$to', '%Y-%m')

            LEFT JOIN debtor_loan_schedule RMD ON C.trans_no = RMD.trans_no AND C.debtor_no = RMD.debtor_no 
            AND RMD.month_no != 0 AND DATE_FORMAT(RMD.date_due, '%Y-%m') <= DATE_FORMAT('$backtwoDate', '%Y-%m')

            WHERE A.months_term != 0 
            AND C.type = 10
            AND C.repo_date = '0000-00-00'
            AND E.month_no != 0
            AND IFNULL(REMAINBAL.REMAIN, A.ar_amount) <> 0
            AND C.tran_date <= '$to'";

            if ($cust_name != 'ALL') {
                $sql .= " AND B.name =".db_escape($cust_name);              
            }
            
            if ($group == 1) {
                $sql .= "GROUP BY C.reference, E.trans_no, E.debtor_no";                
                $sql .= " ORDER BY I.account_code, E.date_due, F.date_paid DESC";

            }
            else if ($group == 2) {
                $sql .= "GROUP BY C.reference, E.trans_no, E.debtor_no";                
                $sql .= " ORDER BY B.collectors_name, E.date_due, F.date_paid DESC";
            } else {
                $sql .= "GROUP BY C.reference, E.trans_no, E.debtor_no";                
                $sql .= " ORDER BY B.collectors_name, E.date_due, F.date_paid DESC";
            }

    return db_query($sql, "No transactions were returned");
}

function print_PO_Report()
{
    global $path_to_root, $SysPrefs;

    $to         = $_POST['PARAM_0'];
    $customer = $_POST['PARAM_1'];
    $group = $_POST['PARAM_2'];
    $orientation= $_POST['PARAM_3'];
    $destination= $_POST['PARAM_4'];

    if ($destination)
        include_once($path_to_root . "/reporting/includes/excel_report.inc");
    else
        include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    if ($customer == ALL_TEXT)
        $cust = _('ALL');
    else
        $cust = get_customer_name($customer);
        $dec = user_price_dec();

    if ($group == 1) {
        $grp = _('CHART OF ACCOUNTS');
    }
    else if ($group == 2) {
        $grp = _('COLLECTOR');
    } else {
        $grp = _('AREA');
    }
        
        
    $orientation = 'L';
    
    $dec = user_price_dec();

    $params = array(0 => $comments,
        1 => array('text' => _('As Of Date'),'from' => $to, 'to' => ''),
        2 => array('text' => _('Customer'), 'from' => $cust, 'to' => ''),
        3 => array('text' => _('Group By'), 'from' => $grp, 'to' => ''));   


    $cols = array(0, 117, 152, 169, 204, 265, 269, 292, 332, 
    373, 419, 465, 515, 572, 613, 654, 680, 715);

    $headers = array(
        _('Customer Name'), 
        _('Buy Date'), 
        _('Term'),
        _('First Due'),
        _('Model'),
        _(''),
        _('Rebate'),
        _('Balance'),
        _('Due Nxt Mon'),
        _('Due This Mon'),
        _('Ovr Due 1 Mon'),
        _('Ovr Due 2 Mon'),
        _('Ovr Due 3 Mon <<'),
        _('Past Due'),
        _('Total Collect'),
        _('Penalty'),
        _('Last Paymt.'),
        _('Last Applied.'));

    $aligns = array('left', 'left', 'left', 'left', 'left', 'left', 'left', 
    'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 
    'left', 'left');

     $rep = new FrontReport(_('Aging Collectors Report'), "AgingCollectorsReport", "Legal", 9, $orientation);
    if ($orientation == 'L')
        recalculate_cols($cols);

    
    $rep->fontSize -= 1;
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    //$rep->SetHeaderType('COLLECTION_Header');
    if ($destination) {
        $rep->SetHeaderType('COLLECTION_Header');
    }
    else {
        $rep->SetHeaderType('COLLECTION_Header');     
    }
    $rep->NewPage();

    $Rebatesubtotal = $Balancesubtotal = $Duenxtmonthsubtotal = $Duethismonthsubtotal = 
    $Overdue1monthsubtotal = $Overdue2monthssubtotal = $Overdue3monthssubtotal = 
    $Pastduemonthssubtotal = $Totalcollectibles = $Totalcollectiblessubtotal = $Penaltysubtotal = 0.0;

    $Rebategrandtotal = $Balancegrandtotal = $Duenxtmonthgrandtotal = 
    $Duethismonthgrandtotal = $Overdue1monthgrandtotal = $Overdue2monthsgrandtotal = 
    $Overdue3monthsgrandtotal = $Pastduemonthsgrandtotal = $Totalcollectiblesgrandtotals =
    $Penaltygrandtotal = 0.0;

    $res = getTransactions($to, $cust, $group);
    $Collector_Name = $Coa_name = '';

    while ($ACRBCR = db_fetch($res))
    {

        if ($group == 1) {
            if ($Coa_name != $ACRBCR['Coa_name']) {

                if ($Coa_name != '') {
                    $rep->NewLine(2);
                    $rep->Font('bold');
                    $rep->TextCol(0, 5, _('Sub Total'));
                    $rep->AmountCol(6, 7, $Rebatesubtotal);
                    $rep->AmountCol(7, 8, $Balancesubtotal);
                    $rep->AmountCol(8, 9, $Duenxtmonthsubtotal);
                    $rep->AmountCol(9, 10, $Duethismonthsubtotal);
                    $rep->AmountCol(10, 11, $Overdue1monthsubtotal);
                    $rep->AmountCol(11, 12, $Overdue2monthssubtotal);
                    $rep->AmountCol(12, 13, $Overdue3monthssubtotal);
                    $rep->AmountCol(13, 14, $Pastduemonthssubtotal);
                    $rep->AmountCol(14, 15, $Totalcollectiblessubtotal);
                    $rep->AmountCol(15, 16, $Penaltysubtotal);
                    $rep->Line($rep->row  - 4);
                    $rep->NewLine(2);
                    $rep->Font();

                    $Rebatesubtotal = $Balancesubtotal = $Duenxtmonthsubtotal = $Duethismonthsubtotal = $Overdue1monthsubtotal = 
                    $Overdue2monthssubtotal = $Overdue3monthssubtotal = $Pastduemonthssubtotal = $Totalcollectiblessubtotal = 
                    $Penaltysubtotal = 0.0;
                }

                $rep->Font('bold');
                $rep->SetTextColor(0, 0, 255);
                $rep->TextCol(0, 5, $ACRBCR['Coa_name']);
                $Coa_name = $ACRBCR['Coa_name'];
                $rep->Font();
                $rep->SetTextColor(0, 0, 0);
                $rep->NewLine();    
            }
        } else if ($group == 2){
            if ($Collector_Name != $ACRBCR['Collector_Name']) {

                if ($Collector_Name != '') {
                    $rep->NewLine(2);
                    $rep->Font('bold');
                    $rep->TextCol(0, 5, _('Sub Total'));
                    $rep->AmountCol(6, 7, $Rebatesubtotal);
                    $rep->AmountCol(7, 8, $Balancesubtotal);
                    $rep->AmountCol(8, 9, $Duenxtmonthsubtotal);
                    $rep->AmountCol(9, 10, $Duethismonthsubtotal);
                    $rep->AmountCol(10, 11, $Overdue1monthsubtotal);
                    $rep->AmountCol(11, 12, $Overdue2monthssubtotal);
                    $rep->AmountCol(12, 13, $Overdue3monthssubtotal);
                    $rep->AmountCol(13, 14, $Pastduemonthssubtotal);
                    $rep->AmountCol(14, 15, $Totalcollectiblessubtotal);
                    $rep->AmountCol(15, 16, $Penaltysubtotal);
                    $rep->Line($rep->row  - 4);
                    $rep->NewLine(2);
                    $rep->Font();

                    $Rebatesubtotal = $Balancesubtotal = $Duenxtmonthsubtotal = $Duethismonthsubtotal = $Overdue1monthsubtotal = 
                    $Overdue2monthssubtotal = $Overdue3monthssubtotal = $Pastduemonthssubtotal = $Totalcollectiblessubtotal =
                    $Penaltysubtotal = 0.0;
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
            if ($AREA != $ACRBCR['AREA']) {

                if ($AREA != '') {
                    $rep->NewLine(2);
                    $rep->Font('bold');
                    $rep->TextCol(0, 5, _('Sub Total'));
                    $rep->AmountCol(6, 7, $Rebatesubtotal);
                    $rep->AmountCol(7, 8, $Balancesubtotal);
                    $rep->AmountCol(8, 9, $Duenxtmonthsubtotal);
                    $rep->AmountCol(9, 10, $Duethismonthsubtotal);
                    $rep->AmountCol(10, 11, $Overdue1monthsubtotal);
                    $rep->AmountCol(11, 12, $Overdue2monthssubtotal);
                    $rep->AmountCol(12, 13, $Overdue3monthssubtotal);
                    $rep->AmountCol(13, 14, $Pastduemonthssubtotal);
                    $rep->AmountCol(14, 15, $Totalcollectiblessubtotal);
                    $rep->AmountCol(15, 16, $Penaltysubtotal);
                    $rep->Line($rep->row  - 4);
                    $rep->NewLine(2);
                    $rep->Font();

                    $Rebatesubtotal = $Balancesubtotal = $Duenxtmonthsubtotal = $Duethismonthsubtotal = $Overdue1monthsubtotal = 
                    $Overdue2monthssubtotal = $Overdue3monthssubtotal = $Pastduemonthssubtotal = $Totalcollectiblessubtotal =
                    $Penaltysubtotal = 0.0;
                }
    
                $rep->Font('bold');
                $rep->SetTextColor(0, 0, 255);
                $rep->TextCol(0, 5, $ACRBCR['AREA']);
                $AREA = $ACRBCR['AREA'];
                $rep->Font();
                $rep->SetTextColor(0, 0, 0);
                $rep->NewLine();    
            }
        }

        //---FOR BALANCE ---//
        $remaining_balance = $ACRBCR['BALANCE'];

        //---DUE NEXT MONTH---//
        $due_nxt_month_payment = $ACRBCR['due_nxt_month_payment'];
        
        //---DUE THIS MONTH---//
        $due_this_month_payment = $ACRBCR['due_this_month_payment'];
          
        //----FOR OVERDUE 1 MONTH----//
        $Partial_due_1_month = $ACRBCR['ovrdue_1month_payment'];
        
        //---FOR OVER DUE 2 MONTH---/
        $Partial_due_2_month = $ACRBCR['ovrdue_2month_payment'];
       
        //---FOR OVER DUE 3 MONTH---/
        $overdue_3months_below = $ACRBCR['ovrdue_3month_payment'];

        //----FOR PAST DUE-----//
        $maturity_date = $ACRBCR['Maturity'];
        $to = date2sql($to);
        if ($maturity_date < $to) {
            $past_due_payment_final =  $ACRBCR['BALANCE'];
            $Partial_due_2_month = 0;
            $Partial_due_1_month = 0;
            $overdue_3months_below = 0;
            $Partial_due_this_month = 0;
            $due_nxt_month_payment = 0;
            $due_this_month_payment = 0;
        } else {
            $past_due_payment_final = 0;
        }
        
        //-----FOR PENALTY-----//
        $company_prefs = get_company_prefs();
        $Penalty = $company_prefs["penalty_rate"];
        $For_penalties = $ACRBCR['past_due_payment'];
        $Penalties = $Partial_due_1_month + $Partial_due_2_month + $overdue_3months_below;
        if ($For_penalties == 0) {
            $Penalties_final = $Penalties * $Penalty; 
        } else {
            $Penalties_final = $past_due_payment_final * $Penalty;
        }


        //-----FOR TOTAL COLLECTIBLES-----//
        $Totalcollectibles = $due_this_month_payment + $Partial_due_1_month + $Partial_due_2_month
        + $overdue_3months_below + $past_due_payment_final;
            

        $rep->NewLine(0.5);
        $rep->TextCol(0, 1, $ACRBCR['name']);
        $rep->NewLine(0.8);
        $rep->SetTextColor(0, 102, 0);
        $rep->TextCol(0, 1, $ACRBCR['address']);
        $rep->SetTextColor(0, 0, 0);
        $rep->TextCol(1, 2, sql2date($ACRBCR['invoice_date']));
        $rep->TextCol(2, 3, $ACRBCR['months_term']);
        $rep->TextCol(3, 4, sql2date($ACRBCR['firstdue_date']));
        $rep->TextCol(4, 5, $ACRBCR['stock_id']);
        $rep->TextCol(5, 6, $ACRBCR['']);
        $rep->AmountCol(6, 7, $ACRBCR['Rebate']);
        $rep->AmountCol(7, 8, $remaining_balance);
        $rep->AmountCol(8, 9, $due_nxt_month_payment);
        $rep->AmountCol(9, 10, $due_this_month_payment);
        $rep->SetTextColor(255, 0, 0);
        $rep->AmountCol(10, 11, $Partial_due_1_month);
        $rep->AmountCol(11, 12, $Partial_due_2_month);
        $rep->AmountCol(12, 13, $overdue_3months_below);
        $rep->AmountCol(13, 14, $past_due_payment_final);
        $rep->SetTextColor(0, 0, 0);
        $rep->AmountCol(14, 15, $Totalcollectibles);
        $rep->SetTextColor(255, 0, 0);
        $rep->AmountCol(15, 16, $Penalties_final);
        $rep->SetTextColor(0, 0, 0);
        $rep->TextCol(16, 17, sql2date($ACRBCR['last_payment']));
        $rep->TextCol(17, 18, sql2date($ACRBCR['last_month_applied']));
        $rep->NewLine(0.8);


        $Rebatesubtotal += $ACRBCR['Rebate'];
        $Rebategrandtotal += $ACRBCR['Rebate'];

        $Balancesubtotal += $remaining_balance;
        $Balancegrandtotal += $remaining_balance;

        $Duenxtmonthsubtotal += $due_nxt_month_payment;
        $Duenxtmonthgrandtotal += $due_nxt_month_payment;

        $Duethismonthsubtotal += $due_this_month_payment;
        $Duethismonthgrandtotal += $due_this_month_payment;

        $Overdue1monthsubtotal += $Partial_due_1_month;
        $Overdue1monthgrandtotal += $Partial_due_1_month;

        $Overdue2monthssubtotal += $Partial_due_2_month;
        $Overdue2monthsgrandtotal += $Partial_due_2_month;

        $Overdue3monthssubtotal += $overdue_3months_below;
        $Overdue3monthsgrandtotal += $overdue_3months_below;

        $Pastduemonthssubtotal += $past_due_payment_final;
        $Pastduemonthsgrandtotal += $past_due_payment_final;

        $Totalcollectiblessubtotal += $Totalcollectibles;
        $Totalcollectiblesgrandtotals += $Totalcollectibles;

        $Penaltysubtotal += $Penalties_final;
        $Penaltygrandtotal += $Penalties_final;

    }


    $rep->NewLine(0);

    if ($group == 1) {
        if ($Coa_name != $ACRBCR['Coa_name']) {

            if ($Coa_name != '') {
                $rep->NewLine(2);
                $rep->Font('bold');
                $rep->TextCol(0, 5, _('Sub Total'));
                $rep->AmountCol(6, 7, $Rebatesubtotal);
                $rep->AmountCol(7, 8, $Balancesubtotal);
                $rep->AmountCol(8, 9, $Duenxtmonthsubtotal);
                $rep->AmountCol(9, 10, $Duethismonthsubtotal);
                $rep->AmountCol(10, 11, $Overdue1monthsubtotal);
                $rep->AmountCol(11, 12, $Overdue2monthssubtotal);
                $rep->AmountCol(12, 13, $Overdue3monthssubtotal);
                $rep->AmountCol(13, 14, $Pastduemonthssubtotal);
                $rep->AmountCol(14, 15, $Totalcollectiblessubtotal);
                $rep->AmountCol(15, 16, $Penaltysubtotal);
                $rep->Line($rep->row  - 4);
                $rep->NewLine(2);
                $rep->Font();

                $Rebatesubtotal = $Balancesubtotal = $Duenxtmonthsubtotal = $Duethismonthsubtotal = $Overdue1monthsubtotal = 
                $Overdue2monthssubtotal = $Overdue3monthssubtotal = $Pastduemonthssubtotal = $Totalcollectiblessubtotal = 
                $Penaltysubtotal = 0.0;
            }

            $rep->Font('bold');
            $rep->SetTextColor(0, 0, 255);
            $rep->TextCol(0, 5, $ACRBCR['Coa_name']);
            $Coa_name = $ACRBCR['Coa_name'];
            $rep->Font();
            $rep->SetTextColor(0, 0, 0);
            $rep->NewLine();    
        }
    } else if ($group == 2){
        if ($Collector_Name != $ACRBCR['Collector_Name']) {

            if ($Collector_Name != '') {
                $rep->NewLine(2);
                $rep->Font('bold');
                $rep->TextCol(0, 5, _('Sub Total'));
                $rep->AmountCol(6, 7, $Rebatesubtotal);
                $rep->AmountCol(7, 8, $Balancesubtotal);
                $rep->AmountCol(8, 9, $Duenxtmonthsubtotal);
                $rep->AmountCol(9, 10, $Duethismonthsubtotal);
                $rep->AmountCol(10, 11, $Overdue1monthsubtotal);
                $rep->AmountCol(11, 12, $Overdue2monthssubtotal);
                $rep->AmountCol(12, 13, $Overdue3monthssubtotal);
                $rep->AmountCol(13, 14, $Pastduemonthssubtotal);
                $rep->AmountCol(14, 15, $Totalcollectiblessubtotal);
                $rep->AmountCol(15, 16, $Penaltysubtotal);
                $rep->Line($rep->row  - 4);
                $rep->NewLine(2);
                $rep->Font();

                $Rebatesubtotal = $Balancesubtotal = $Duenxtmonthsubtotal = $Duethismonthsubtotal = $Overdue1monthsubtotal = 
                $Overdue2monthssubtotal = $Overdue3monthssubtotal = $Pastduemonthssubtotal = $Totalcollectiblessubtotal =
                $Penaltysubtotal = 0.0;
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
        if ($AREA != $ACRBCR['AREA']) {

            if ($AREA != '') {
                $rep->NewLine(2);
                $rep->Font('bold');
                $rep->TextCol(0, 5, _('Sub Total'));
                $rep->AmountCol(6, 7, $Rebatesubtotal);
                $rep->AmountCol(7, 8, $Balancesubtotal);
                $rep->AmountCol(8, 9, $Duenxtmonthsubtotal);
                $rep->AmountCol(9, 10, $Duethismonthsubtotal);
                $rep->AmountCol(10, 11, $Overdue1monthsubtotal);
                $rep->AmountCol(11, 12, $Overdue2monthssubtotal);
                $rep->AmountCol(12, 13, $Overdue3monthssubtotal);
                $rep->AmountCol(13, 14, $Pastduemonthssubtotal);
                $rep->AmountCol(14, 15, $Totalcollectiblessubtotal);
                $rep->AmountCol(15, 16, $Penaltysubtotal);
                $rep->Line($rep->row  - 4);
                $rep->NewLine(2);
                $rep->Font();

                $Rebatesubtotal = $Balancesubtotal = $Duenxtmonthsubtotal = $Duethismonthsubtotal = $Overdue1monthsubtotal = 
                $Overdue2monthssubtotal = $Overdue3monthssubtotal = $Pastduemonthssubtotal = $Totalcollectiblessubtotal =
                $Penaltysubtotal = 0.0;
            }

            $rep->Font('bold');
            $rep->SetTextColor(0, 0, 255);
            $rep->TextCol(0, 5, $ACRBCR['AREA']);
            $AREA = $ACRBCR['AREA'];
            $rep->Font();
            $rep->SetTextColor(0, 0, 0);
            $rep->NewLine();    
        }
    }

    
    $rep->NewLine(0);
    $rep->Line($rep->row - 2);
    $rep->Font('bold');
    $rep->fontSize += 0;    
    $rep->TextCol(0, 5, _('Grand Total'));
    $rep->AmountCol(6, 7, $Rebategrandtotal);
    $rep->AmountCol(7, 8, $Balancegrandtotal);
    $rep->AmountCol(8, 9, $Duenxtmonthgrandtotal);
    $rep->AmountCol(9, 10, $Duethismonthgrandtotal);
    $rep->AmountCol(10, 11, $Overdue1monthgrandtotal);
    $rep->AmountCol(11, 12, $Overdue2monthsgrandtotal);
    $rep->AmountCol(12, 13, $Overdue3monthsgrandtotal);
    $rep->AmountCol(13, 14, $Pastduemonthsgrandtotal);
    $rep->AmountCol(14, 15, $Totalcollectiblesgrandtotals);
    $rep->AmountCol(15, 16, $Penaltygrandtotal);
    //$rep->SetFooterType('compFooter');
    $rep->End();
}