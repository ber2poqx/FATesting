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
$page_security = 'SA_SL_REP'; //Modified by spyrax10 20 Jun 2022
// ----------------------------------------------------------------
// $ Revision:	7.0 $
// Creator:	Prog6
// date_:	2021-08-09
// Title:	SL Summary per Customer
// Mantis Issue #: 366
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

print_SL_summary_per_customer();

function getTransactions($from, $to, $gl_account)
{
	if($from != 0)
		$from = date2sql($from);
	
	$to = date2sql($to);

	
	$sql = "		
		SELECT `name`, `debtor_ref`, SUM(Debit) AS `Debit`, SUM(Credit) AS `Credit`, SUM(amount) AS `Balance`
		FROM
		(SELECT dt.debtor_no, dm.name, dm.debtor_ref
			, CASE WHEN gl.amount >= 0 THEN gl.amount ELSE 0 END AS `Debit`
		    , CASE WHEN gl.amount < 0 THEN -gl.amount ELSE 0 END AS `Credit`
		    , gl.*
		FROM ".TB_PREF."`gl_trans` gl
			LEFT JOIN ".TB_PREF."`debtor_trans` dt ON gl.type = dt.type AND gl.type_no = dt.trans_no
		    LEFT JOIN ".TB_PREF."`debtors_master` dm ON dt.debtor_no = dm.debtor_no
		WHERE gl.account = '$gl_account' ";

	if ($from == 0)	
		$sql .= " AND gl.tran_date <= '$to' ) A";	
	else
		$sql .= " AND gl.tran_date BETWEEN '$from' AND '$to' ) A";
	
		
	$sql .= " GROUP BY `name`, `debtor_ref` ORDER BY name	";
		
	
	return db_query($sql,"No transactions were returned");
}

function getTotal_debit_Credit($to, $gl_account)
{
	$to = date2sql($to);

	$sql = "
		SELECT
			SUM(Debit1) AS `Debit`, SUM(Credit1) AS `Credit`
		FROM
		(SELECT 
			CASE WHEN amount >= 0 THEN amount ELSE 0 END AS `Debit1`
			, CASE WHEN amount < 0 THEN -amount ELSE 0 END AS `Credit1`
		FROM ".TB_PREF."gl_trans 
		WHERE tran_date <= '$to'
			AND account = '$gl_account') A
		";
	return db_query($sql,"No transactions were returned");
}

function get_GL_Title($gl_account)
{	
	$sql = " SELECT * FROM `chart_master` WHERE account_code = '$gl_account' ";

	return db_query($sql,"No transactions were returned");
}

function print_SL_summary_per_customer()
{
	global $path_to_root;
	
	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$gl_account = $_POST['PARAM_2'];
	$comments = $_POST['PARAM_3'];
	$destination = $_POST['PARAM_4'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
				
	//$orientation = ($orientation ? 'L' : 'P');

	$orientation = 'P';
    $dec = user_price_dec();

    $account = get_GL_Title($gl_account);
    $GL_title = db_fetch($account);
    $account_name = $GL_title['account_name'];
	
	if($from == '')
		$from = 0;


	if($from == 0)
	{
		$params = array(0 => $comments,
			1 => array('text' => _('Date'),'from' => _('As of'), 'to' => $to),
			2 => array('text' => _('GL Title'), 'from' => $gl_account, 'to' => $account_name));
	}
	else
	{
		$params = array(0 => $comments,
			1 => array('text' => _('Period Date'),'from' => $from, 'to' => $to),
			2 => array('text' => _('GL Title'), 'from' => $gl_account, 'to' => $account_name));
	}
	
	$cols = array(20,      170,     270,  	     370,      470, 	565);

	$headers = array(
		_('Name / Entries'), 
		_('MC Code'),
		_('Debits'), 
		_('Credits'),
		_('Balance')
		);

	$aligns = array('left', 'left', 'right', 'right', 'right');

	$rep = new FrontReport(_('SL Summary per Customer'), "SalesSummaryReport", "letter", 9, $orientation);

    //if ($orientation == 'L')
    //	recalculate_cols($cols);
	
	$rep->fontSize -= 1;
    $rep->Info($params, $cols, $headers, $aligns, 
		null, null, null, true, true, true);
    $rep->SetHeaderType('SL_Summary_Header');
	$rep->NewPage();
	
	$res = getTransactions($from, $to, $gl_account);
	$Tot_bal = 0;

	While ($SLsum = db_fetch($res))
	{
		
		//$dec2 = get_qty_dec($SLsum['debtor_ref']);

		
		$rep->TextCol(0, 1, $SLsum['name']);
		$rep->fontSize -= 1;
		$rep->TextCol(1, 2, $SLsum['debtor_ref']);
		$rep->AmountCol2(2, 3, $SLsum['Debit'], $dec);
		$rep->AmountCol2(3, 4, $SLsum['Credit'], $dec);	
		$rep->Font('bold');	
		//$rep->AmountCol2(4, 5, abs($SLsum['Balance']), $dec);	
		// if ($SLsum['Balance'] >= 0)
			$rep->AmountCol2(4, 5, abs($SLsum['Balance']), $dec);	
		// else			
		// 	$rep->AmountCol2(4, 5, $SLsum['Balance'], $dec);		
		$rep->Font();
		$rep->fontSize += 1;

		
		$Tot_bal = $SLsum['Balance'] + $Tot_bal;

		// $rep->TextCol(5, 6, $GRNs['Name']);
		// $rep->TextCol(6, 7, $GRNs['Model']);
		// $rep->TextCol(7, 8, $GRNs['Serial']);
		// $rep->TextCol(8, 9, $GRNs['Chassis']);
		// $rep->TextCol(9, 10, $GRNs['Type']);
		// $rep->TextCol(10, 11, $GRNs['Term']);
		// $rep->TextCol(11, 12, $GRNs['Qty']);
		// $rep->AmountCol2(12, 13, $GRNs['LCP']);
		// $rep->AmountCol2(13, 14, $GRNs['UnitCost']);
		// $rep->AmountCol2(14, 15, $GRNs['grossAmnt']);
		// $rep->AmountCol2(15, 16, $GRNs['discountdp']);
		// $rep->TextCol(16, 17, $GRNs['SalesAgent']);

		$rep->NewLine();
		//$rep->NewLine();
	}

	$rep->NewLine();
	$rep->Line($rep->row - 2);

	
	

	$rep->NewLine();
	
	if($Tot_bal == 0)
	{
		$rep->Font('bold');	
		$rep->TextCol(1, 4, _('- - - - - - Nothing to Display Transaction in the given Parameter. - - - - - -'));
		$rep->Font();
	}
	else
	{
		$rep->fontSize += 1;	
		$rep->Font('bold');
		$rep->TextCol(1, 2, _('TOTAL'));

		$Total1 = getTotal_debit_Credit($to, $gl_account);
		While ($Total_amount = db_fetch($Total1))
		{
			$rep->AmountCol(2, 3, $Total_amount['Debit'], $dec);
			$rep->AmountCol(3, 4, $Total_amount['Credit'], $dec);
		}
		$rep->AmountCol(4, 5, abs($Tot_bal), $dec);
	}

	$rep->Font();
	$rep->Line($rep->row - 2);
	//$rep->SetFooterType('');
	$rep->fontSize -= 1;
    $rep->End();
}
