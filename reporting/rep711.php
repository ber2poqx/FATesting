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
$page_security = 'SA_GLANALYTIC';
// ----------------------------------------------------------------
// $ Revision:  2.0 $
// Creator: RobertGwapo
// date:    2022-02-12
// Title:   Expense Summary Report
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

//----------------------------------------------------------------------------------------------------

print_PO_Report();

function getTransactions($from, $to)
{
    $from = date2sql($from);
    $to = date2sql($to);
    
    $sql ="SELECT A.tran_date, A.account, B.account_name, E.name AS ChartType,

        (SELECT SUM(D.amount) AS AMOUNT FROM gl_trans D 
        WHERE D.account = A.account AND D.tran_date>='$from'
        AND D.tran_date<='$to' AND DATE_FORMAT(D.tran_date, '%m') = '01' GROUP BY D.account)January,

        (SELECT SUM(D.amount) AS AMOUNT FROM gl_trans D 
        WHERE D.account = A.account AND D.tran_date>='$from'
        AND D.tran_date<='$to' AND DATE_FORMAT(D.tran_date, '%m') = '02' GROUP BY D.account)February,

        (SELECT SUM(D.amount) AS AMOUNT FROM gl_trans D 
        WHERE D.account = A.account AND D.tran_date>='$from'
        AND D.tran_date<='$to' AND DATE_FORMAT(D.tran_date, '%m') = '03' GROUP BY D.account)March,

        (SELECT SUM(D.amount) AS AMOUNT FROM gl_trans D 
        WHERE D.account = A.account AND D.tran_date>='$from'
        AND D.tran_date<='$to' AND DATE_FORMAT(D.tran_date, '%m') = '04' GROUP BY D.account)April,

        (SELECT SUM(D.amount) AS AMOUNT FROM gl_trans D 
        WHERE D.account = A.account AND D.tran_date>='$from'
        AND D.tran_date<='$to' AND DATE_FORMAT(D.tran_date, '%m') = '05' GROUP BY D.account)May,

        (SELECT SUM(D.amount) AS AMOUNT FROM gl_trans D 
        WHERE D.account = A.account AND D.tran_date>='$from'
        AND D.tran_date<='$to' AND DATE_FORMAT(D.tran_date, '%m') = '06' GROUP BY D.account)June,

        (SELECT SUM(D.amount) AS AMOUNT FROM gl_trans D 
        WHERE D.account = A.account AND D.tran_date>='$from'
        AND D.tran_date<='$to' AND DATE_FORMAT(D.tran_date, '%m') = '07' GROUP BY D.account)July,

        (SELECT SUM(D.amount) AS AMOUNT FROM gl_trans D 
        WHERE D.account = A.account AND D.tran_date>='$from'
        AND D.tran_date<='$to' AND DATE_FORMAT(D.tran_date, '%m') = '08' GROUP BY D.account)August,

        (SELECT SUM(D.amount) AS AMOUNT FROM gl_trans D 
        WHERE D.account = A.account AND D.tran_date>='$from'
        AND D.tran_date<='$to' AND DATE_FORMAT(D.tran_date, '%m') = '09' GROUP BY D.account)September,

        (SELECT SUM(D.amount) AS AMOUNT FROM gl_trans D 
        WHERE D.account = A.account AND D.tran_date>='$from'
        AND D.tran_date<='$to' AND DATE_FORMAT(D.tran_date, '%m') = '10' GROUP BY D.account)October,

        (SELECT SUM(D.amount) AS AMOUNT FROM gl_trans D 
        WHERE D.account = A.account AND D.tran_date>='$from'
        AND D.tran_date<='$to' AND DATE_FORMAT(D.tran_date, '%m') = '11' GROUP BY D.account)November,

        (SELECT SUM(D.amount) AS AMOUNT FROM gl_trans D 
        WHERE D.account = A.account AND D.tran_date>='$from'
        AND D.tran_date<='$to' AND DATE_FORMAT(D.tran_date, '%m') = '12' GROUP BY D.account)December

        FROM gl_trans A
        LEFT JOIN chart_master B ON A.account = B.account_code
        LEFT JOIN chart_types E ON B.account_type = E.id
        WHERE A.tran_date>='$from'
        AND A.tran_date<='$to'
        AND B.account_type = '11' OR B.account_type = '12'
        GROUP BY A.account";
    return db_query($sql, "No transactions were returned");
}

function print_PO_Report()
{
    global $path_to_root;
    
    $from       = $_POST['PARAM_0'];
    $to         = $_POST['PARAM_1'];
    $orientation= $_POST['PARAM_2'];
    $destination= $_POST['PARAM_3'];

    if ($destination)
        include_once($path_to_root . "/reporting/includes/excel_report.inc");
    else
        include_once($path_to_root . "/reporting/includes/pdf_report.inc");
        
        
    $orientation = 'L';
    
    $dec = user_price_dec();

    $params = array(0 => $comments,
        1 => array('text' => _('Period'),'from' => $from, 'to' => $to)
    );

    $cols = array(0, 120, 125, 170, 220, 270, 320, 368, 415, 460, 505, 555, 600, 650, 700);

    $headers = array(
        _('Expense Account'), 
        _(''), 
        _('January'),
        _('February'),
        _('March'),
        _('April'),
        _('May'),
        _('June'),
        _('July'),
        _('August'),
        _('September'),
        _('October'),
        _('November'),
        _('December'),
        _('Total Amount'));

    $aligns = array('left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left');

    $rep = new FrontReport(_('Expense Summary Report'), "ExpenseSummaryReport", "Legal", 9, $orientation);
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

    $totalamount = 0.0;

    $jantotal = $febtotal = $martotal = $aprtotal = $maytotal = $juntotal = $jultotal = $augtotal = 
    $septotal = $octtotal = $novtotal = $dectotal = 0.0;

    $res = getTransactions($from, $to);
    $catt = '';

    while ($DSOC = db_fetch($res))
    {
        if ($catt != $DSOC['ChartType'])
        {
            $rep->Font('bold');
            $rep->SetTextColor(0, 0, 255);     
            $rep->TextCol(0, 5, $DSOC['ChartType']);
            $catt = $DSOC['ChartType'];
            $rep->Font();
            $rep->SetTextColor(0, 0, 0);
            $rep->NewLine();    
        }

        $totalamount = $DSOC['January'] + $DSOC['February'] + $DSOC['March'] + $DSOC['April'] +
        $DSOC['May'] + $DSOC['June'] + $DSOC['July'] + $DSOC['August'] + $DSOC['September'] +
        $DSOC['October'] + $DSOC['November'] + $DSOC['December'];
        
        $rep->NewLine();
        $rep->TextCol(0, 1, $DSOC['account_name']);
        $rep->TextCol(1, 2, $DSOC['']);
        $rep->AmountCol(2, 3, $DSOC['January']);
        $rep->AmountCol(3, 4, $DSOC['February']);
        $rep->AmountCol(4, 5, $DSOC['March']);
        $rep->AmountCol(5, 6, $DSOC['April']);
        $rep->AmountCol(6, 7, $DSOC['May']);
        $rep->AmountCol(7, 8, $DSOC['June']);
        $rep->AmountCol(8, 9, $DSOC['July']);
        $rep->AmountCol(9, 10, $DSOC['August']);
        $rep->AmountCol(10, 11, $DSOC['September']);
        $rep->AmountCol(11, 12, $DSOC['October']);
        $rep->AmountCol(12, 13, $DSOC['November']);
        $rep->AmountCol(13, 14, $DSOC['December']);
        $rep->AmountCol(14, 15, $totalamount);
        $rep->NewLine(0.5);

        $jantotal +=  $DSOC['January'];
        $febtotal +=  $DSOC['February'];
        $martotal +=  $DSOC['March'];
        $aprtotal +=  $DSOC['April'];
        $maytotal +=  $DSOC['May'];
        $juntotal +=  $DSOC['June'];
        $jultotal +=  $DSOC['July'];
        $augtotal +=  $DSOC['August'];
        $septotal +=  $DSOC['September'];
        $octtotal +=  $DSOC['October'];
        $novtotal +=  $DSOC['November'];
        $dectotal +=  $DSOC['December'];

        $alltotal +=  $totalamount;
    }
    $rep->Line($rep->row - 2);

    if ($catt != $DSOC['ChartType'])
    {
        $rep->Font('bold');
        $rep->SetTextColor(0, 0, 255);     
        $rep->TextCol(0, 5, $DSOC['ChartType']);
        $catt = $DSOC['ChartType'];
        $rep->Font();
        $rep->SetTextColor(0, 0, 0);
        $rep->NewLine();    
    }

    
    $rep->NewLine(2.5);
    $rep->Font('bold');
    $rep->Line($rep->row - 2);
    $rep->fontSize += 1;    
    $rep->TextCol(0, 2, _('TOTAL:'));
    $rep->AmountCol(2, 3, $jantotal);
    $rep->AmountCol(3, 4, $febtotal);
    $rep->AmountCol(4, 5, $martotal);
    $rep->AmountCol(5, 6, $aprtotal);
    $rep->AmountCol(6, 7, $maytotal);
    $rep->AmountCol(7, 8, $juntotal);
    $rep->AmountCol(8, 9, $jultotal);
    $rep->AmountCol(9, 10, $augtotal);
    $rep->AmountCol(10, 11, $septotal);
    $rep->AmountCol(11, 12, $octtotal);
    $rep->AmountCol(12, 13, $novtotal);
    $rep->AmountCol(13, 14, $dectotal);
    $rep->AmountCol(14, 15, $alltotal);
    //$rep->SetFooterType('compFooter');
    $rep->End();
}
