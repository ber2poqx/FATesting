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
$page_security = 'SA_SUPPLIERANALYTIC';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	RobertGwapo
// date:	2021-08-30
// Title:	Collection &Report - Actual vs Target
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

function getCollectionActual_Target($from, $to)
{
	$from = date2sql($from);
	$to = date2sql($to);
	
	$sql ="SELECT DATE_FORMAT(A.collect_date, '%Y') AS Transaction_year, DATE_FORMAT(A.collect_date, '%m') AS Transaction_date, A.percentage AS Target_percentage, B.amount AS target_amount,

		IFNULL(ACTUALTAMT.Amount, 0) AS Actual_amount, 

		IFNULL(ACTUALTAMT.Amount, 0) / IFNULL(B.amount, 0) * 100 AS Actual_percentage

		FROM collection_target_percentage A
		LEFT JOIN collection_target_amount B ON DATE_FORMAT(B.collect_date, '%Y-%m') = DATE_FORMAT(A.collect_date, '%Y-%m')

		LEFT JOIN (
		SELECT C.tran_date, C.module_type, C.type, SUM(C.ov_amount) AS Amount
		FROM debtor_trans C
		WHERE C.type = 12 AND C.module_type != 'CLTN-DPWOSI' 
		AND C.module_type != 'CLTN-INTERB'
		GROUP BY DATE_FORMAT(C.tran_date, '%Y-%m')
		) ACTUALTAMT ON DATE_FORMAT(A.collect_date, '%Y-%m') = DATE_FORMAT(ACTUALTAMT.tran_date, '%Y-%m')

		WHERE A.collect_date>= '$from'
		AND A.collect_date <= '$to'
		GROUP BY DATE_FORMAT(A.collect_date, '%Y-%m'), DATE_FORMAT(B.collect_date, '%Y-%m')";
    return db_query($sql, "No transactions were returned");
}

function print_PO_Report()
{
    global $path_to_root;
	
    $from 		= $_POST['PARAM_0'];
	$to 		= $_POST['PARAM_1'];
	$orientation= $_POST['PARAM_2'];
	$destination= $_POST['PARAM_3'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
		
		
	$orientation = 'L';


	$date = explode('/', $to);
	$year = $date[2];
	
    $dec = user_price_dec();

	$params = array(0 => $comments,
		1 => array('text' => _('Period'),'from' => $from, 'to' => $to)
		//2 => array('text' => _('Year'),'from' => $year, 'to' => '')
	);

	$cols = array(120, 190, 260, 330, 400);

	$headers = array(
		_('Month'), 
		_('Target Percentage'),
		_('Target Amount'),
		_('Actual Amount'),
		_('Actual Percentage'));

	$aligns = array('left', 'left', 'left', 'left', 'left');

    $rep = new FrontReport(_('Collection Report - Actual vs Target'), "CollectionReportActualvsTarget", user_pagesize(), 11, $orientation);
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

	$Total = 0.0;

	$res = getCollectionActual_Target($from, $to);
	$Transaction_year = '';

	while ($DSOC = db_fetch($res))
	{	
		$percent = '%';
		$Target_percentage = $DSOC['Target_percentage'] . '' . $percent;
		$Actual_percentage = ROUND($DSOC['Actual_percentage'], 2);

		$actual_percent = $Actual_percentage . '' . $percent;

		if ($DSOC['Transaction_date'] == '01') {
			$month = 'January';
		} elseif ($DSOC['Transaction_date'] == '02') {
			$month = 'February';
		} elseif ($DSOC['Transaction_date'] == '03') {
			$month = 'March';
		} elseif ($DSOC['Transaction_date'] == '04') {
			$month = 'April';
		} elseif ($DSOC['Transaction_date'] == '05') {
			$month = 'May';
		} elseif ($DSOC['Transaction_date'] == '06') {
			$month = 'June';
		} elseif ($DSOC['Transaction_date'] == '07') {
			$month = 'July';
		} elseif ($DSOC['Transaction_date'] == '08') {
			$month = 'August';
		} elseif ($DSOC['Transaction_date'] == '09') {
			$month = 'September';
		} elseif ($DSOC['Transaction_date'] == '10') {
			$month = 'October';
		} elseif ($DSOC['Transaction_date'] == '11') {
			$month = 'November';
		} elseif ($DSOC['Transaction_date'] == '12') {
			$month = 'December';
		} 

		if ($Transaction_year != $DSOC['Transaction_year']) {
			if ($Transaction_year != '')
			{
				$rep->NewLine(6);
				$rep->Font('bold');
				//$rep->Line($rep->row  - 4);
				$rep->Font();
				$rep->NewLine(2);
			}

            $rep->Font('bold');      
            $rep->SetTextColor(0, 0, 255);
            $rep->TextCol(0, 5, $DSOC['Transaction_year']);
            $Transaction_year = $DSOC['Transaction_year'];
            $rep->Font();
            $rep->SetTextColor(0, 0, 0);
            $rep->NewLine(1);    
        }

		$rep->NewLine(1);
		$rep->TextCol(0, 1, $month);
		$rep->TextCol(1, 2, $Target_percentage);
		$rep->AmountCol(2, 3, $DSOC['target_amount']);
		$rep->AmountCol(3, 4, $DSOC['Actual_amount']);
		$rep->TextCol(4, 5, $actual_percent);
        $rep->NewLine(1);
	}
    $rep->NewLine(1);
	$rep->Line($rep->row - 2);
	$rep->Line($rep->row - 2);
	$rep->Line($rep->row - 2);
    $rep->NewLine(0.5);
	$rep->Line($rep->row - 2);
	$rep->Line($rep->row - 2);
	$rep->Line($rep->row - 2);
    $rep->End();
}
