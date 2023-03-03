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
// $ Revision:	7.0 $
// Creator:	Prog6
// date_:	2021-11-03
// Title:	RGP Report Summarized per year
// Mantis Issue #: 511
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

print_RGP_summarized();

function getTransactions($month, $account)
{	
	$sql = "		
			SELECT YEAR(gl.tran_date) AS year , SUM(ABS(gl.amount)) AS amount 
			FROM `gl_trans` gl 				
			WHERE gl.`account` = '$account' 
				AND MONTH(gl.tran_date) = '$month' 
			GROUP BY YEAR(gl.tran_date)";

	return db_query($sql,"No transactions were returned");
}

function print_RGP_summarized()
{
	global $path_to_root;
	
	$month_param = $_POST['PARAM_0'];
	// $month = '4';
	$comments = $_POST['PARAM_1'];
	$destination = $_POST['PARAM_2'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
				
	//$orientation = ($orientation ? 'L' : 'P');

	$orientation = 'P';
    $dec = user_price_dec();

    $month = date("m",strtotime($month_param));
    $month_name = '';
    $curr_date = date("Y-m-d");
    $curr_year = date("Y");
    $prev1_year = date("Y",strtotime("-1 year"));
    $prev2_year = date("Y",strtotime("-2 year"));    
    $prev3_year = date("Y",strtotime("-3 year"));

    if ($month == '1')
    	$month_name = 'JANUARY';
    if ($month == '2')
    	$month_name = 'FEBRUARY';
    if ($month == '3')
		$month_name = 'MARCH';
    if ($month == '4')
    	$month_name = 'APRIL';
    if ($month == '5')
    	$month_name = 'MAY';
    if ($month == '6')
    	$month_name = 'JUNE';
    if ($month == '7')
    	$month_name = 'JULY';
    if ($month == '8')
    	$month_name = 'AUGUST';
    if ($month == '9')
    	$month_name = 'SEPTEMBER';
    if ($month == '10')
    	$month_name = 'OCTOBER';
    if ($month == '11')
    	$month_name = 'NOVEMBER';
    if ($month == '12')
    	$month_name = 'DECEMBER';
   
    $params = array(0 => $comments);	
		// 1 => array('text' => _('RGP IN THE MONTH OF '), 'from' => $month_name, 'to' => ''));

	$cols = array(5, 130, 230, 330, 430, 0);

	$headers = array(
		_('Month'),
		_($curr_year),
		_($prev1_year),
		_($prev2_year),
		_($prev3_year.' and below')
		);

	$aligns = array('center', 'right', 'right', 'right', 'right');

	$rep = new FrontReport(_('RGP Report - Summarized per Year'), "RGPSummarizedReport", "letter", 9, $orientation);

    //if ($orientation == 'L')
    //	recalculate_cols($cols);
	
	$rep->fontSize -= 1;
    $rep->Info($params, $cols, $headers, $aligns, 
		null, null, null, true, true, true);
    $rep->SetHeaderType('SL_Summary_Header');
	$rep->NewPage();
	
	$res = getTransactions($month, $account = '402001'); //Old code = 4465

	$RGP1 = 0;
	$RGP2 = 0;
	$RGP3 = 0;
	$RGP4 = 0;

	While ($RGPsum = db_fetch($res))
	{
		if($RGPsum['year'] == $curr_year)
			$RGP1 = $RGPsum['amount'];
		if($RGPsum['year'] == $prev1_year)
			$RGP2 = $RGPsum['amount'];
		if($RGPsum['year'] == $prev2_year)
			$RGP3 = $RGPsum['amount'];
		if($RGPsum['year'] <= $prev3_year)
			$RGP4 = $RGP4 + $RGPsum['amount'];

	}


	// $rep->NewLine(0.5);

	if ($month_name == '') 
	{
		$rep->TextCol(0, 5, _('- - - - - - Nothing to Display Transaction in the given Parameter. Please Select Month- - - - - -'));
	}
	else 
	{
		$rep->TextCol(0, 1, $month_name);
		$rep->AmountCol2(1, 2, $RGP1, $dec);
		$rep->AmountCol2(2, 3, $RGP2, $dec);
		$rep->AmountCol2(3, 4, $RGP3, $dec);
		$rep->AmountCol2(4, 5, $RGP4, $dec);
	}
	$rep->NewLine(0.5);

	$rep->Font();
	$rep->Line($rep->row - 2);
	//$rep->SetFooterType('');
	$rep->fontSize -= 1;
    $rep->End();
}
