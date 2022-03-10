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
// date_:	2021-10-16
// Title:	SL Realized Gross Profit (RGP - detailed)
// Mantis Issue #: 510
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

print_SL_RGP();

function getTransactions($from, $to, $gl_account,$masterfile)
{	
	if ($from != 0)
		$from = date2sql($from);
		$to = date2sql($to);
		/*SELECT gl.tran_date
			, YEAR(dt.tran_date) AS year
			, ref.reference
            , dt.trans_no
			, gl.loan_trans_no AS gl_trans_no
            , dt.debtor_no
			, dm.name
			, gl.memo_
			, cm.account_name
			, gl.amount
			, CASE WHEN gl.amount >= 0 THEN amount ELSE 0 END AS `Debit`
		    , CASE WHEN gl.amount <  0 THEN -amount ELSE 0 END AS `Credit`
		FROM `gl_trans` gl
			LEFT JOIN `refs` ref ON gl.type = ref.type AND gl.loan_trans_no = ref.id
		    LEFT JOIN `debtor_trans` dt ON gl.type = dt.type AND gl.loan_trans_no = dt.trans_no
		    LEFT JOIN `debtors_master` dm ON dt.debtor_no = dm.debtor_no
		    LEFT JOIN `comments` com ON gl.type = com.type AND gl.loan_trans_no = com.id 
		    LEFT JOIN `chart_master` cm ON gl.account = cm.account_code
		WHERE gl.`account` = '$gl_account'";*/
		$sql = "
		SELECT gl.tran_date
			, YEAR(dt.tran_date) AS year
			, ref.reference
            , dt.trans_no
			, gl.loan_trans_no
            , dt.debtor_no
			, dm.name
			, IF(ISNULL(com.memo_), gl.memo_, CONCAT(gl.memo_,' ',com.memo_)) AS memo_
			, cm.account_name
			, gl.amount
			, CASE WHEN gl.amount >= 0 THEN amount ELSE 0 END AS `Debit`
		    , CASE WHEN gl.amount <  0 THEN -amount ELSE 0 END AS `Credit`
		FROM `gl_trans` gl
			LEFT JOIN `refs` ref ON gl.type = ref.type AND gl.type_no = ref.id
		    LEFT JOIN `debtor_trans` dt ON gl.type = dt.type AND gl.type_no = dt.trans_no AND (gl.type!=".ST_JOURNAL." OR gl.person_id=dt.debtor_no)
		    LEFT JOIN `debtors_master` dm ON dt.debtor_no = dm.debtor_no
		    LEFT JOIN `comments` com ON gl.type = com.type AND gl.type_no = com.id 
		    LEFT JOIN `chart_master` cm ON gl.account = cm.account_code
		WHERE gl.`account` = '$gl_account'";



	if($masterfile != ALL_TEXT)
	{
		$sql .= " AND dt.debtor_no = '$masterfile'";
	}

	if ($from == 0)
	{
		$sql .= " AND gl.tran_date <= '$to'";
	}
	if ($from != 0)
	{
		$sql .= " AND gl.tran_date BETWEEN '$from' AND '$to'";
	}
		//$sql .= " ORDER BY ref.reference";
		$sql .= " ORDER BY gl.`tran_date`, gl.`counter`";
		
		
	
	return db_query($sql,"No transactions were returned");
}

function Invoice_year($loan_trans_no)
{
	$sql = "SELECT `trans_no`, YEAR(`tran_date`) AS `invoice_year` FROM `debtor_trans` WHERE `trans_no` = '$loan_trans_no' AND type = '".ST_SALESINVOICE."' ORDER BY `trans_no`";

	return db_query($sql,"No invoice year");
}

function getEnding_bal($to, $gl_account, $masterfile)
{
	$to = date2sql($to);

	$sql = "
		SELECT
			SUM(Debit1) AS `Debit`, SUM(Credit1) AS `Credit`
		FROM
		(SELECT 
			CASE WHEN gl.amount >= 0 THEN amount ELSE 0 END AS `Debit1`
			, CASE WHEN gl.amount < 0 THEN -amount ELSE 0 END AS `Credit1`
		FROM ".TB_PREF."gl_trans gl
			INNER JOIN ".TB_PREF."debtor_trans dt ON gl.type = dt.type AND gl.type_no = dt.trans_no
		WHERE gl.account = '$gl_account' AND gl.tran_date <= '$to'";

	if($masterfile != ALL_TEXT){
		$sql .=	"AND dt.debtor_no = '$masterfile'";
	}

	$sql .=	") A	";

	return db_query($sql,"No transactions were returned");
} 

function getBalance_forwarded($from, $gl_account, $masterfile)
{
	$from = date2sql($from);

	$sql = "
		SELECT `Debit2` AS `Debit`
			, `Credit2` AS `Credit`
			, CASE
				WHEN `Debit2` >= `Credit2` THEN `Debit2` - `Credit2`
				ELSE `Credit2` - `Debit2` END AS `Forwarded_Bal`
		FROM
		(
			SELECT
				SUM(Debit1) AS `Debit2`, SUM(Credit1) AS `Credit2`
			FROM
			(
				SELECT 
					CASE WHEN gl.amount >= 0 THEN amount ELSE 0 END AS `Debit1`
					, CASE WHEN gl.amount < 0 THEN -amount ELSE 0 END AS `Credit1`
				FROM ".TB_PREF."gl_trans gl 
					INNER JOIN ".TB_PREF."debtor_trans dt ON gl.type = dt.type AND gl.type_no = dt.trans_no
				WHERE gl.account = '$gl_account' AND gl.tran_date < '$from'";

	if($masterfile != ALL_TEXT){
		$sql .=	"AND dt.debtor_no = '$masterfile'";
	}

	$sql .= "	) A ) B ";
	return db_query($sql,"No transactions were returned");
}


function get_GL_Title($gl_account)
{	
	$sql = " SELECT * FROM `chart_master` WHERE account_code = '$gl_account' ";

	return db_query($sql,"No transactions were returned");
}

function get_Masterfile_Name($masterfile)
{	
	$sql = " SELECT * FROM `debtors_master` WHERE debtor_no = '$masterfile' ";

	return db_query($sql,"No transactions were returned");
}

function print_SL_RGP()
{
	global $path_to_root;
	
	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$masterfile = $_POST['PARAM_2'];
	$comments = $_POST['PARAM_3'];
	$destination = $_POST['PARAM_4'];
	$gl_account = 4465;

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
	
    if($masterfile == ALL_TEXT)
    {
    	$Masterfile_name = _('ALL');
    }else{
		$person_id = get_Masterfile_Name($masterfile);
    	$Cust_name = db_fetch($person_id);
    	$Masterfile_name = $Cust_name['name'];
    }

	if($from == '')
		$from = 0;
	
	if($from == 0)
	{
		$params = array(0 => $comments,		
		1 => array('text' => _('Date'),'from' => _('As of'), 'to' => $to),		
		2 => array('text' => _('GL Title'), 'from' => $gl_account . _(' - ') . $account_name, 'to' => ''),
		3 => array('text' => _('Masterfile Name'), 'from' => $Masterfile_name, 'to' => ''));
	}
	if($from != 0)
	{
	$params = array(0 => $comments,		
		1 => array('text' => _('Period'),'from' => $from, 'to' => $to),
		2 => array('text' => _('GL Title'), 'from' => $gl_account . _(' - ') . $account_name, 'to' => ''),
		3 => array('text' => _('Masterfile Name'), 'from' => $Masterfile_name, 'to' => ''));
	}

	$cols = array(5, 55, 105,  150, 180, 290,  360, 435,   500,	0);

	$headers = array(
		_('Date'), 
		_('Reference'),
		_('Trans #'),
		_('Year'),
		_('Name'), 
		_('Particulars'),
		_('Debits'),
		_('Credits'), 
		_('Balance')
		);

	$aligns = array('left', 'left', 'center', 'left', 'left', 'left', 'right', 'right', 'right');

	$rep = new FrontReport(_('SL RGP Report - Realized Gross Profit (per transaction)'), "SalesSummaryReport", "letter", 9, $orientation);

    //if ($orientation == 'L')
    //	recalculate_cols($cols);
	
	$rep->fontSize -= 1;
    $rep->Info($params, $cols, $headers, $aligns, 
		null, null, null, true, true, true);
    $rep->SetHeaderType('SL_Summary_Header');
	$rep->NewPage();
	
	$res = getTransactions($from, $to, $gl_account, $masterfile);

	if ($from != 0)
		$Forwarded_bal = getBalance_forwarded($from, $gl_account, $masterfile);
	
	$Tot_bal = 0;
	$Tot_deb = 0;
	$Tot_cred = 0;
	$running_bal = 0;
	$amount_val = 0;
	$Forwarded_deb = 0;
	$Forwarded_cred = 0;
	$trans_number = 0;

	While ($SLsum = db_fetch($res))
	{
		$trans_number = $SLsum['loan_trans_no'];

		$result_trans_no = Invoice_year($trans_number);
		$rowYear = db_fetch($result_trans_no);
		
		if($running_bal == 0)
		{	
			$rep->NewLine(0.5);
			$rep->Font('bold');		
			$rep->TextCol(0, 5, $gl_account . _(' - ') . $account_name);
			if($from == 0)
			{
				$rep->TextCol(6, 7, _('As of - ') . $to);
			}
			$rep->Line($rep->row - 2);			
			$rep->Font();

			if ($from != 0)
			{
				While ($F_bal = db_fetch($Forwarded_bal))
				{	
					$rep->Font('bold');	
					$rep->TextCol(5, 6, _('Balance Forwarded'));
					$rep->AmountCol2(6, 7, $F_bal['Debit'], $dec);
					$rep->AmountCol2(7, 8, $F_bal['Credit'], $dec);
					$rep->AmountCol2(8, 9, $F_bal['Forwarded_Bal'], $dec);
					$rep->Font();	
					$running_bal = $F_bal['Forwarded_Bal'];
					$Forwarded_deb = $F_bal['Debit'];
					$Forwarded_cred = $F_bal['Credit'];			
				}
					
			}										
		}

		$amount_val = $SLsum['amount'];

		//$running_bal = $running_bal + $amount_val;
		$Tot_bal = $Tot_bal + $amount_val;

		if($amount_val >= 0)
		{
			$running_bal = $running_bal - $amount_val;
		}
		if ($amount_val < 0)
		{
			$amount_val = -$SLsum['amount'];
			$running_bal = $running_bal + $amount_val;
		}

		$dec2 = get_qty_dec($SLsum['reference']);

		$rep->NewLine();
		$rep->TextCol(0, 1, $SLsum['tran_date']);
		$rep->TextCol(1, 2, $SLsum['reference']);
		$rep->TextCol(2, 3, $trans_number);
		$rep->TextCol(3, 4, $rowYear['invoice_year']);
		$rep->TextCol(4, 5, $SLsum['name']);
		$rep->TextCol(5, 6, $SLsum['memo_']);
		$rep->AmountCol2(6, 7, $SLsum['Debit'], $dec);
		$rep->AmountCol2(7, 8, $SLsum['Credit'], $dec);
		if ($running_bal < 0)
			$rep->AmountCol2(8, 9, -$running_bal, $dec);
		else
			$rep->AmountCol2(8, 9, $running_bal, $dec);

		$Tot_deb = $SLsum['Debit'] + $Tot_deb;
		$Tot_cred = $SLsum['Credit'] + $Tot_cred;


		

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
		
		$rep->NewLine(0, 1);
	}

	$rep->NewLine(1);

	$rep->Line($rep->row + 10);

		
	if($Tot_bal == 0)
	{
		$rep->Font('bold');	
		$rep->NewLine(1);
		$rep->TextCol(1, 7, _('- - - - - - Nothing to Display Transaction in the given Parameter. - - - - - -'));
		$rep->Font();
		$rep->NewLine(1);
	}
	else
	{

		$rep->Font('bold');
		$rep->TextCol(5, 6, _('Subtotal'));
		$rep->Font('italic');
		$rep->AmountCol(6, 7, $Tot_deb, $dec);
		$rep->AmountCol(7, 8, $Tot_cred, $dec);
		$rep->Font();
		$rep->Line($rep->row - 25);

		$rep->NewLine(3);

		
		$rep->Font('bold');		
		$rep->TextCol(0, 5, $gl_account . _(' - ') . $account_name);	
		$rep->TextCol(5, 6, _('Ending Balance'));
		$rep->Font();		
		$rep->Font('bold');
		$Total1 = getEnding_bal($to, $gl_account, $masterfile);
		While ($Total_amount = db_fetch($Total1))
		{
			$rep->AmountCol(6, 7, $Total_amount['Debit'], $dec);
			$rep->AmountCol(7, 8, $Total_amount['Credit'], $dec);
		}
		if ($running_bal < 0)
			$rep->AmountCol(8, 9, -$running_bal, $dec);
		else		
			$rep->AmountCol(8, 9, $running_bal, $dec);
		$rep->Line($rep->row - 2);
		
		$rep->NewLine(2);

		$rep->fontSize += 1.5;	
		$rep->Font('bold');
		$rep->TextCol(5, 7, _('Grand Total'));
		$rep->AmountCol(7, 8, $Tot_deb + $Forwarded_deb, $dec);
		$rep->AmountCol(8, 9, $Tot_cred + $Forwarded_cred, $dec);
		$rep->fontSize -= 1.5;	
		$rep->Font();

	}

	$rep->Font();
	$rep->Line($rep->row - 2);
	//$rep->SetFooterType('');
	$rep->fontSize -= 1;
    $rep->End();
}
