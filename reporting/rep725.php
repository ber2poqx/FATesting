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
// date_:	2021-08-11
// Title:	SL Summary per Account
// Mantis Issue #: 367
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

function getTransactions($from, $to, $masterfile)
{
	if($from != 0)
		$from = date2sql($from);
	
	$to = date2sql($to);

	
	$sql = "		
		SELECT `gl_entry`
			, SUM(Debit) AS `Debits`
		    , SUM(Credit) AS `Credits`
		FROM(
			SELECT `gl_entry`
				, CASE WHEN amount >= 0 THEN amount ELSE 0  END AS `Debit`
			    , CASE WHEN amount <  0	THEN -amount ELSE 0 END AS `Credit`
			FROM(
				SELECT cm.account_name AS `gl_entry`, gl.* 
				FROM `gl_trans` gl
					LEFT JOIN `debtor_trans` dt ON gl.type = dt.type AND gl.type_no = dt.trans_no
				    LEFT JOIN `debtors_master` dm ON dt.debtor_no = dm.debtor_no
				    LEFT JOIN `chart_master` cm ON gl.account = cm.account_code
				WHERE dt.debtor_no = '$masterfile'";

	if ($from == 0)	
		$sql .= " AND gl.tran_date <= '$to' ";	
	else
		$sql .= " AND gl.tran_date BETWEEN '$from' AND '$to' ";


	$sql .= " 
				ORDER BY `cm`.`account_name` ASC
			    )A
			ORDER BY `gl_entry`
		    )B
		GROUP BY `gl_entry`
		ORDER BY `gl_entry` ";

	
	return db_query($sql,"No transactions were returned");
}

function getTotal_debit_Credit($to, $masterfile)
{
	$to = date2sql($to);

	$sql = "
		SELECT
			SUM(Debit1) AS `Debit`, SUM(Credit1) AS `Credit`
		FROM
		(SELECT 
			CASE WHEN gl.amount >= 0 THEN gl.amount ELSE 0 END AS `Debit1`
			, CASE WHEN gl.amount < 0 THEN -gl.amount ELSE 0 END AS `Credit1`
		FROM ".TB_PREF."gl_trans gl
			LEFT JOIN ".TB_PREF."debtor_trans dt ON gl.type = dt.type AND gl.type_no = dt.trans_no
			LEFT JOIN ".TB_PREF."debtors_master dm ON dt.debtor_no = dm.debtor_no
		WHERE gl.tran_date <= '$to'
			AND dt.debtor_no = '$masterfile') A
		";
	return db_query($sql,"No transactions were returned");
}

function get_Masterfile_Name($masterfile)
{	
	$sql = " SELECT * FROM `debtors_master` WHERE debtor_no = '$masterfile' ";

	return db_query($sql,"No transactions were returned");
}

function print_SL_summary_per_customer()
{
	global $path_to_root;
	
	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$masterfile = $_POST['PARAM_2'];
	$comments = $_POST['PARAM_3'];
	$destination = $_POST['PARAM_4'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
				
	//$orientation = ($orientation ? 'L' : 'P');

	$orientation = 'P';
    $dec = user_price_dec();

    $person_id = get_Masterfile_Name($masterfile);
    $Cust_name = db_fetch($person_id);
    $Masterfile_name = $Cust_name['name'];
	
	if($from == '')
		$from = 0;


	if($from == 0)
	{
		$params = array(0 => $comments,
			1 => array('text' => _('Date'),'from' => _('As of'), 'to' => $to),
			2 => array('text' => _('Masterfile Name'), 'from' => $Masterfile_name, 'to' => ''));
	}
	else
	{
		$params = array(0 => $comments,
			1 => array('text' => _('Period Date'),'from' => $from, 'to' => $to),
			2 => array('text' => _('Masterfile Name'), 'from' => $Masterfile_name, 'to' => ''));
	}
	
	$cols = array(20,      170,      370,      470, 	565);

	$headers = array(
		_('Account Title'),
		_('Debits'), 
		_('Credits'),
		_('Balance')
		);

	$aligns = array('left', 'right', 'right', 'right');

	$rep = new FrontReport(_('SL Summary per Account'), "SalesSummaryReport", "letter", 9, $orientation);

    //if ($orientation == 'L')
    //	recalculate_cols($cols);
	
	// $rep->fontSize -= 1;
    $rep->Info($params, $cols, $headers, $aligns, 
		null, null, null, true, true, true);
    $rep->SetHeaderType('SL_Summary_Header');
	$rep->NewPage();
	
	$res = getTransactions($from, $to, $masterfile);
	$row_bal = 0;
	$Deb_bal = 0;
	$Cred_bal = 0;
	$total_sub = 0;
	$row_count = 0;

	While ($SLsum = db_fetch($res))
	{
		if($row_count == 0)
		{
			if($from == 0)
			{
				$rep->NewLine(0.5);
				$rep->Font('bold');	
				$rep->TextCol(0, 1, $Masterfile_name );
				$rep->fontSize -= 1;
				$rep->Font();						
				$rep->TextCol(0, 3, _('                                              ( As of - ') . $to . _(' )'));
				$rep->fontSize += 1;
				$rep->Line($rep->row - 2);
				$rep->NewLine(0.5);
			}
			else
			{
				$rep->NewLine(0.5);
				$rep->Font('bold');	
				$rep->TextCol(0, 1, $Masterfile_name );	
				$rep->fontSize -= 1;				
				$rep->Font();
				$rep->TextCol(0, 3, _('                                              ( Period from ') . $from . _(' to ') . $to . _(' )'));			
				$rep->fontSize += 1;
				$rep->Line($rep->row - 2);
				$rep->NewLine(0.5);
			}			
		}

		$row_count += 1;
		$row_bal = 0;
		$dec2 = get_qty_dec($SLsum['gl_entry']);
		
		$rep->NewLine();
		$rep->NewLine();
		$rep->TextCol(0, 1, $SLsum['gl_entry']);
		$rep->AmountCol(1, 2, $SLsum['Debits'], $dec);
		$rep->AmountCol(2, 3, $SLsum['Credits'], $dec);	
		$row_bal = abs($SLsum['Credits']) - abs($SLsum['Debits']);
		$rep->AmountCol(3, 4, abs($row_bal), $dec);

		$total_sub += $row_bal;
		$Cred_bal += $SLsum['Credits'];
		$Deb_bal += $SLsum['Debits'];

		// if($SLsum['Debits'] == 0)
		// {
		// 	$rep->AmountCol(3, 4, $SLsum['Credits'], $dec);
		// 	$row_bal = abs($SLsum['Credits']) - abs($SLsum['Debits']);
		// 	$Cred_bal += $SLsum['Credits'];
		// }
		// else
		// {
		// 	$rep->AmountCol(3, 4, $SLsum['Debits'], $dec);
		// 	$row_bal += $SLsum['Debits'];
		// 	$Deb_bal += $SLsum['Debits'];
		// }
		
		//$rep->NewLine();
	}

	$rep->Line($rep->row - 7);
	$rep->NewLine();
	//$rep->Line($rep->row - 2);

	
	if($row_bal == 0)
	{
		$rep->Font('bold');	
		$rep->TextCol(0, 4, _('-      -      -      -      -      -      -      -      -      Nothing to Display Transaction in the given Parameter.      -      -      -      -      -      -      -      -      -'));
		$rep->Font();
	}
	else
	{
		$rep->NewLine(2);
		$rep->fontSize += 1;	
		$rep->Font('bold');
		$rep->TextCol(0, 1, _('GRAND TOTAL'));

		$Total1 = getTotal_debit_Credit($to, $masterfile);
		While ($Total_amount = db_fetch($Total1))
		{
			$rep->AmountCol(1, 2, $Deb_bal, $dec);
			$rep->AmountCol(2, 3, $Cred_bal, $dec);
			$rep->AmountCol(3, 4, $total_sub, $dec);
		}
		//$rep->AmountCol(4, 5, $Tot_bal, $dec);
	}

	$rep->Font();
	$rep->Line($rep->row - 2);
	//$rep->SetFooterType('');
	$rep->fontSize -= 1;
    $rep->End();
}
