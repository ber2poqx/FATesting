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
// date_:	2021-08-10
// Title:	SL Summary (Particulars)
// Mantis Issue #: 365
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

print_SL_summary_particulars();

function getTransactions($from, $to, $gl_account,$masterfile)
{	
	if ($from != 0)
		$from = date2sql($from);
	$to = date2sql($to);
	
	
		$sql = "		
			SELECT bt.receipt_no AS cr_num
			, gl.tran_date
			, IFNULL(IFNULL(ref.reference, bt.ref),dl.reference) AS reference			
            , IFNULL(IFNULL(IFNULL(IFNULL(sup2.supp_name, debt.name), pdebt.name), gl.master_file ),gldebt.name) as name
			, IF(ISNULL(c.memo_), gl.memo_, CONCAT(gl.memo_,' ',c.memo_)) AS memo_			
			##, gl.memo_ AS memo_
			, gl.account AS account
			, cm.account_name AS account_name
			, gl.amount
			, CASE WHEN gl.amount >= 0 THEN gl.amount ELSE 0 END AS `Debit`
		    , CASE WHEN gl.amount <  0 THEN -gl.amount ELSE 0 END AS `Credit`
			FROM ".TB_PREF."`gl_trans` gl
				LEFT JOIN ".TB_PREF."`refs` ref ON gl.type = ref.type AND gl.type_no = ref.id
				LEFT JOIN ".TB_PREF."`debtor_trans` dt ON gl.type = dt.type AND gl.type_no = dt.trans_no			
				LEFT JOIN ".TB_PREF."`grn_batch` grn ON grn.id=gl.type_no AND gl.type=".ST_SUPPRECEIVE."
				LEFT JOIN ".TB_PREF."`debtors_master` debt ON dt.debtor_no = debt.debtor_no
				LEFT JOIN ".TB_PREF."bank_trans bt ON bt.type=gl.type AND bt.trans_no=gl.type_no AND bt.amount = gl.amount AND bt.amount!=0 AND (bt.person_id != '' AND !ISNULL(bt.person_id))
				LEFT JOIN ".TB_PREF."`suppliers` sup2 ON grn.supplier_id = sup2.supplier_id
				LEFT JOIN (SELECT `type`, `id`, `date_`, `memo_` FROM ".TB_PREF."`comments` GROUP BY `type`, `id`, `date_`, `memo_`) c ON gl.type = c.type AND gl.type_no = c.id 
				LEFT JOIN ".TB_PREF."`chart_master` cm ON gl.account = cm.account_code			
				LEFT JOIN  ".TB_PREF."`debtors_master` pdebt ON bt.person_id = pdebt.debtor_no
				LEFT JOIN  ".TB_PREF."`debtors_master` gldebt ON gl.person_id = gldebt.debtor_no
				LEFT JOIN  ".TB_PREF."`debtor_loans` dl ON gl.loan_trans_no = dl.trans_no
		";
		
		if ($from == 0)
		{
			$sql .= " WHERE gl.tran_date <= '$to' "; 
		}
		else if ($from != 0)
		{
			$sql .= " WHERE gl.tran_date BETWEEN '$from' AND '$to'"; 
		}

		if($masterfile != ALL_TEXT)
		{
			$sql .= " AND dt.debtor_no = '$masterfile' ";
		}

		if ($gl_account != ALL_TEXT)
		{
			$sql .= " AND gl.account = '$gl_account' ORDER BY gl.`tran_date`, gl.`counter` ";
		}
		else if ($gl_account == ALL_TEXT)
		{
			$sql .= " ORDER BY gl.account, gl.`tran_date`, gl.`counter` ";
		}
		
	return db_query($sql,"No transactions were returned");
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
			LEFT JOIN ".TB_PREF."debtor_trans dt ON gl.type = dt.type AND gl.type_no = dt.trans_no			
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
					LEFT JOIN ".TB_PREF."debtor_trans dt ON gl.type = dt.type AND gl.type_no = dt.trans_no
				WHERE gl.account = '$gl_account' AND gl.tran_date < '$from'";  //" AND void.cancel IS NULL ";

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

function get_GL_numbers($from, $to)
{
	$sql = " SELECT DISTINCT `account` FROM `gl_trans` WHERE `tran_date` BETWEEN '$from' AND '$to' ORDER BY `account` LIMIT 1 ";

	return db_query($sql,"No transactions were returned");
}

function print_SL_summary_particulars()
{
	global $path_to_root;
	
	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$gl_account = $_POST['PARAM_2'];
	$masterfile = $_POST['PARAM_3'];
	$comments = $_POST['PARAM_4'];
	$destination = $_POST['PARAM_5'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
				
	//$orientation = ($orientation ? 'L' : 'P');

	$orientation = 'P';
    $dec = user_price_dec();

	  
	
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
	
	if($gl_account == ALL_TEXT)
	{
		if($from == 0)
		{
			$params = array(0 => $comments,		
			1 => array('text' => _('Date'),'from' => _('As of'), 'to' => $to),		
			2 => array('text' => _('GL Title'), 'from' => $gl_account , 'to' => ''),
			3 => array('text' => _('Masterfile Name'), 'from' => $Masterfile_name, 'to' => ''));
		}
		if($from != 0)
		{
			$params = array(0 => $comments,		
			1 => array('text' => _('Period'),'from' => $from, 'to' => $to),
			2 => array('text' => _('GL Title'), 'from' => $gl_account , 'to' => ''),
			3 => array('text' => _('Masterfile Name'), 'from' => $Masterfile_name, 'to' => ''));
		}
	}else
	{
		$account = get_GL_Title($gl_account);
		$GL_title = db_fetch($account);
		$account_name = $GL_title['account_name'];
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
	}	

	$cols = array(5,   55,   90,  180,  280, 360,  	           435,   500,	0); 

	$headers = array(
		_('Date'), 
		_('Ref #'),
		_('Trans Num'),
		_('Name'), 
		_('Particulars'),
		_('Debits'),
		_('Credits'), 
		_('Balance')
		);

	$aligns = array('left', 'left', 'left', 'left', 'left', 'right', 'right', 'right');

	$rep = new FrontReport(_('SL Summary (Particulars)'), "SLparticulars", "letter", 9, $orientation);

    //if ($orientation == 'L')
    //	recalculate_cols($cols);
	
	$rep->fontSize -= 1;
    $rep->Info($params, $cols, $headers, $aligns, 
		null, null, null, true, '', true);
    $rep->SetHeaderType('SL_Summary_Header');
	$rep->NewPage();
	
	if($gl_account != ALL_TEXT)
	{
		$res = getTransactions($from, $to, $gl_account, $masterfile);
		
		if ($from != 0)
		{
			$Forwarded_bal = getBalance_forwarded($from, $gl_account, $masterfile);
		}

		$account = get_GL_Title($gl_account);
		$GL_title = db_fetch($account);
		$account_name = $GL_title['account_name'];
	
		$Tot_bal = null;
		$Tot_deb = 0;
		$Tot_cred = 0;
		$running_bal = null;
		$amount_val = 0;
		$Forwarded_deb = 0;
		$Forwarded_cred = 0;
		$Bal_forwarded_opt = 0; // 0 for debit, 1 for credit
	
		While ($SLsum = db_fetch($res))
		{
			if(!isset($running_bal))
			{	
				$rep->NewLine(0.5);
				$rep->Font('bold');
				$rep->Line($rep->row + 9, 1); //put a borderline above the balance forwarded
				$rep->TextCol(0, 4, $gl_account . _(' - ') . $account_name);
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
						if($F_bal['Credit']>$F_bal['Debit'])
						{
							$Bal_forwarded_opt = 1;
						}
						else
						{
							$Bal_forwarded_opt = 0;
						}

						$rep->Font('bold');
						$rep->TextCol(4, 5, _('Balance Forwarded'));
						$rep->AmountCol2(5, 6, $F_bal['Debit'], $dec);
						$rep->AmountCol2(6, 7, $F_bal['Credit'], $dec);
						$rep->AmountCol2(7, 8, $F_bal['Forwarded_Bal'], $dec);
						$rep->Font();
						$running_bal = $F_bal['Forwarded_Bal'];
						$Forwarded_deb = $F_bal['Debit'];
						$Forwarded_cred = $F_bal['Credit'];
					}
						
				}										
			}

			$amount_val = $SLsum['amount'];// + for debit, - for credit
				
	
			//$running_bal = $running_bal + $amount_val;
			$Tot_bal = $Tot_bal + $amount_val;
	
			if($Bal_forwarded_opt == 0)
			{
				$running_bal = $running_bal + $amount_val;
			}
			else if($Bal_forwarded_opt == 1)
			{
				$running_bal = $running_bal - $amount_val;
			}

			/*
			if($amount_val >= 0)
			{
				$running_bal = $running_bal + $amount_val;
			}
			if ($amount_val < 0)
			{
				$amount_val = -$SLsum['amount'];
				$running_bal = $running_bal - $amount_val;
			}*/
	
			$dec2 = get_qty_dec($SLsum['reference']);
	
			$rep->fontSize -= 1.5;

			$rep->NewLine();
			$rep->TextCol(0, 1, $SLsum['tran_date']);
			$rep->TextCol(1, 2, $SLsum['cr_num']);
			$rep->TextCol(2, 3, $SLsum['reference']);
			$rep->TextCol(3, 4, $SLsum['name']);
			$rep->TextCol(4, 5, $SLsum['memo_']);
			$rep->AmountCol2(5, 6, $SLsum['Debit'], $dec);
			$rep->AmountCol2(6, 7, $SLsum['Credit'], $dec);
			//$rep->AmountCol2(6, 7, -$running_bal, $dec);
			
			if ($running_bal < 0)
				$rep->AmountCol2(7, 8, -$running_bal, $dec);
			else
				$rep->AmountCol2(7, 8, $running_bal, $dec);			
	
			$Tot_deb = $SLsum['Debit'] + $Tot_deb;
			$Tot_cred = $SLsum['Credit'] + $Tot_cred;			
	
			$rep->fontSize += 1.5;

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

		if($Tot_bal != 0)
		{
			$rep->NewLine(1);
			$rep->Line($rep->row + 10);
		}
			
		if(isset($Tot_bal))
		{
			While ($F_bal = db_fetch($Forwarded_bal))
			{	
				$rep->Font('bold');
				$rep->TextCol(0, 4, $gl_account . _(' - ') . $account_name);
				$rep->TextCol(4, 5, _('Balance Forwarded'));
				$rep->AmountCol2(5, 6, $F_bal['Debit'], $dec);
				$rep->AmountCol2(6, 7, $F_bal['Credit'], $dec);
				$rep->AmountCol2(7, 8, $F_bal['Forwarded_Bal'], $dec);
				$rep->Font();
				$running_bal = $F_bal['Forwarded_Bal'];
				$Forwarded_deb = $F_bal['Debit'];
				$Forwarded_cred = $F_bal['Credit'];
			}				
		}

		if(!isset($Tot_bal))
		{
			While ($F_bal = db_fetch($Forwarded_bal))
			{	
				$rep->Font('bold');
				$rep->TextCol(0, 4, $gl_account . _(' - ') . $account_name);
				$rep->TextCol(4, 5, _('Balance Forwarded'));
				$rep->AmountCol2(5, 6, $F_bal['Debit'], $dec);
				$rep->AmountCol2(6, 7, $F_bal['Credit'], $dec);
				$rep->AmountCol2(7, 8, $F_bal['Forwarded_Bal'], $dec);
				$rep->Font();
				$running_bal = $F_bal['Forwarded_Bal'];
				$Forwarded_deb = $F_bal['Debit'];
				$Forwarded_cred = $F_bal['Credit'];
			}	
			$rep->NewLine(0.2);
			$rep->Line($rep->row);
			$rep->NewLine(2);
			$rep->TextCol(2, 7, _('-  -  -  -  -  -  -  No Transaction in the given Parameter  -  -  -  -  -  -  '));
			$rep->NewLine(1);
		}
		
		if($Tot_bal != 0)
		{
			$rep->fontSize -= 1;
			$rep->Font('bold');
			$rep->TextCol(4, 6, _('Subtotal'));
			$rep->Font('italic');
			$rep->AmountCol(5, 6, $Tot_deb, $dec);
			$rep->AmountCol(6, 7, $Tot_cred, $dec);
			$rep->Font();
			$rep->fontSize += 1;
		}

		$rep->Line($rep->row - 7);

		/*if(isset($Tot_bal))
		{
			$rep->NewLine(1.3);			
			$rep->Font('bold');		
			$rep->TextCol(0, 4, $gl_account . _(' - ') . $account_name);	
			$rep->TextCol(4, 5, _('Ending Balance'));
			$rep->Font();		
			$rep->Font('bold');
			$Total1 = getEnding_bal($to, $gl_account, $masterfile);
			While ($Total_amount = db_fetch($Total1))
			{
				$rep->AmountCol(5, 6, $Total_amount['Debit'], $dec);
				$rep->AmountCol(6, 7, $Total_amount['Credit'], $dec);
			}
			if ($running_bal < 0)
				$rep->AmountCol(7, 8, -$running_bal, $dec);
			else		
				$rep->AmountCol(7, 8, $running_bal, $dec);
			$rep->Line($rep->row - 2, 0.5);	
			$rep->Font();
		}*/

		$rep->NewLine(1.3);			
		$rep->Font('bold');		
		$rep->TextCol(0, 4, $gl_account . _(' - ') . $account_name);	
		$rep->TextCol(4, 5, _('Ending Balance'));
		$rep->Font();		
		$rep->Font('bold');
		$Total1 = getEnding_bal($to, $gl_account, $masterfile);
		While ($Total_amount = db_fetch($Total1))
		{
			$rep->AmountCol(5, 6, $Total_amount['Debit'], $dec);
			$rep->AmountCol(6, 7, $Total_amount['Credit'], $dec);
		}
		if ($running_bal < 0)
			$rep->AmountCol(7, 8, -$running_bal, $dec);
		else		
			$rep->AmountCol(7, 8, $running_bal, $dec);
		$rep->Line($rep->row - 2, 0.5);	
		$rep->Font();
		
		

		$rep->NewLine(5);
		
		if(isset($Tot_bal))
		{
			//$rep->fontSize += 1;	
			$rep->Font('bold');
			$rep->TextCol(3, 4, _('Grand Total'));
			$rep->AmountCol(5, 6, $Tot_deb + $Forwarded_deb, $dec);
			$rep->AmountCol(6, 7, $Tot_cred + $Forwarded_cred, $dec);
			$rep->fontSize -= 1;	
			$rep->Font();
			$rep->Line($rep->row - 2);
			//$rep->SetFooterType('');
			//$rep->fontSize -= 1;
		}
	}	
	else if($gl_account == ALL_TEXT)
	{
		$res = getTransactions($from, $to, $gl_account, $masterfile);

		$Tot_bal = null;
		$Tot_deb = 0;
		$Tot_cred = 0;
		$running_bal = 0;
		$amount_val = 0;
		$Forwarded_deb = 0;
		$Forwarded_cred = 0;
		$grand_deb = 0;
		$grand_cred = 0;
		$grand_forwarded_deb = 0;
		$grand_forwarded_cred = 0;
		$Bal_forwarded_opt = 0; // 0 for debit, 1 for credit
	
		While ($SLsum = db_fetch($res))
		{
			if($gl_account != $SLsum['account'])
			{	
				$account = get_GL_Title($gl_account);
				$GL_title = db_fetch($account);
				$account_name = $GL_title['account_name'];

				$Forwarded_bal = getBalance_forwarded($from, $SLsum['account'], $masterfile);
				
				if(isset($Tot_bal))
				{
					$rep->Line($rep->row - 2);

					//subtotal
					$rep->NewLine();
					$rep->fontSize -= 1;
					$rep->Font('bold');
					$rep->TextCol(4, 6, _('Subtotal'));
					$rep->Font('italic');
					$rep->AmountCol(5, 6, $Tot_deb, $dec);
					$rep->AmountCol(6, 7, $Tot_cred, $dec);
					$grand_deb = $grand_deb + $Tot_deb;
					$grand_cred = $grand_cred + $Tot_cred;
					$Tot_deb = 0;
					$Tot_cred = 0;
					$rep->fontSize += 1;

					$rep->NewLine(0.2);
					$rep->Line($rep->row - 3);

					//ending balance
					$rep->NewLine();			
					$rep->Font('bold');		
					$rep->TextCol(0, 4, $gl_account . _(' - ') . $account_name);	
					$rep->TextCol(4, 5, _('Ending Balance'));
					$rep->Font();		
					$rep->Font('bold');
					$Total1 = getEnding_bal($to, $gl_account, $masterfile);
					While ($Total_amount = db_fetch($Total1))
					{
						$rep->AmountCol(5, 6, $Total_amount['Debit'], $dec);
						$rep->AmountCol(6, 7, $Total_amount['Credit'], $dec);
					}
					if ($running_bal < 0)
						$rep->AmountCol(7, 8, -$running_bal, $dec);
					else		
						$rep->AmountCol(7, 8, $running_bal, $dec);


					$rep->Font();
					$rep->Line($rep->row - 3,1);

					$rep->NewLine(3);
				}
				
				
				$rep->Font('bold');
				$rep->Line($rep->row + 9, 1); //put a borderline above the balance forwarded
				$rep->TextCol(0, 4, $SLsum['account'] . _(' - ') . $SLsum['account_name']);
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
						if($F_bal['Credit']>$F_bal['Debit'])
						{
							$Bal_forwarded_opt = 1;
						}
						else
						{
							$Bal_forwarded_opt = 0;
						}

						$rep->Font('bold');
						$rep->TextCol(4, 5, _('Balance Forwarded'));
						$rep->AmountCol2(5, 6, $F_bal['Debit'], $dec);
						$rep->AmountCol2(6, 7, $F_bal['Credit'], $dec);
						$rep->AmountCol2(7, 8, $F_bal['Forwarded_Bal'], $dec);
						$rep->Font();
						$grand_forwarded_deb = $grand_forwarded_deb + $F_bal['Debit'];
						$grand_forwarded_cred = $grand_forwarded_cred + $F_bal['Credit'];
						$running_bal = $F_bal['Forwarded_Bal'];
						$Forwarded_deb = $F_bal['Debit'];
						$Forwarded_cred = $F_bal['Credit'];
					}						
				}										
			}
	
			$amount_val = $SLsum['amount'];// + for debit, - for credit
	
			//$running_bal = $running_bal + $amount_val;
			$Tot_bal = $Tot_bal + $amount_val;
	
			if($Bal_forwarded_opt == 0)
			{
				$running_bal = $running_bal + $amount_val;
			}
			else if($Bal_forwarded_opt == 1)
			{
				$running_bal = $running_bal - $amount_val;
			}

			/*
			if($amount_val >= 0)
			{
				$running_bal = $running_bal + $amount_val;
			}
			if ($amount_val < 0)
			{
				$amount_val = -$SLsum['amount'];
				$running_bal = $running_bal - $amount_val;
			}*/
	
			$dec2 = get_qty_dec($SLsum['reference']);
			
			#CHANGE FONTSIZE
			$rep->fontSize -= 1.5;

			$rep->NewLine();
			$rep->TextCol(0, 1, $SLsum['tran_date']);
			$rep->TextCol(1, 2, $SLsum['cr_num']);
			$rep->TextCol(2, 3, $SLsum['reference']);
			$rep->TextCol(3, 4, $SLsum['name']);
			$rep->TextCol(4, 5, $SLsum['memo_']);
			$rep->AmountCol2(5, 6, $SLsum['Debit'], $dec);
			$rep->AmountCol2(6, 7, $SLsum['Credit'], $dec);
			//$rep->AmountCol2(6, 7, -$running_bal, $dec);
			
			if ($running_bal < 0)
				$rep->AmountCol2(7, 8, -$running_bal, $dec);
			else
				$rep->AmountCol2(7, 8, $running_bal, $dec);			
	
			$Tot_deb = $SLsum['Debit'] + $Tot_deb;
			$Tot_cred = $SLsum['Credit'] + $Tot_cred;			
	
			#CHANGE FONTSIZE
			$rep->fontSize += 1.5;

			$rep->NewLine(0, 1);

			$gl_account = $SLsum['account'];
		}

		
		if(isset($Tot_bal) && $from != 0)
		{
			While ($F_bal = db_fetch($Forwarded_bal))
			{	
				$rep->Font('bold');
				$rep->TextCol(0, 4, $gl_account . _(' - ') . $account_name);
				$rep->TextCol(4, 5, _('Balance Forwarded'));
				$rep->AmountCol2(5, 6, $F_bal['Debit'], $dec);
				$rep->AmountCol2(6, 7, $F_bal['Credit'], $dec);
				$rep->AmountCol2(7, 8, $F_bal['Forwarded_Bal'], $dec);
				$rep->Font();
				$running_bal = $F_bal['Forwarded_Bal'];
				$Forwarded_deb = $F_bal['Debit'];
				$Forwarded_cred = $F_bal['Credit'];
			}				
		}

		if(!isset($Tot_bal))
		{	
			While ($F_bal = db_fetch($Forwarded_bal))
			{	
				$rep->Font('bold');
				$rep->TextCol(0, 4, $gl_account . _(' - ') . $account_name);
				$rep->TextCol(4, 5, _('Balance Forwarded'));
				$rep->AmountCol2(5, 6, $F_bal['Debit'], $dec);
				$rep->AmountCol2(6, 7, $F_bal['Credit'], $dec);
				$rep->AmountCol2(7, 8, $F_bal['Forwarded_Bal'], $dec);
				$rep->Font();
				$running_bal = $F_bal['Forwarded_Bal'];
				$Forwarded_deb = $F_bal['Debit'];
				$Forwarded_cred = $F_bal['Credit'];
			}				
			$rep->Line($rep->row);
			$rep->NewLine(2);
			$rep->TextCol(2, 7, _('-  -  -  -  -  -  -  No Transaction in the given Parameter  -  -  -  -  -  -  '));
			$rep->NewLine(1);
		}
		
		
		$rep->fontSize -= 1;
		$rep->Line($rep->row - 2);
		$rep->NewLine();

		if(isset($Tot_bal))
		{
			$rep->Font('bold');
			$rep->TextCol(4, 6, _('Subtotal'));
			$rep->Font('italic');
			$rep->AmountCol(5, 6, $Tot_deb, $dec);
			$rep->AmountCol(6, 7, $Tot_cred, $dec);
			$grand_deb = $grand_deb + $Tot_deb;
			$grand_cred = $grand_cred + $Tot_cred;
			$Tot_deb = 0;
			$Tot_cred = 0;
			$rep->fontSize += 1;
			$rep->Font();

			$rep->NewLine(0.2);
			$rep->Line($rep->row - 3);

			$account = get_GL_Title($gl_account);
			$GL_title = db_fetch($account);
			$account_name = $GL_title['account_name'];

			//ending balance
			$rep->NewLine();			
			$rep->Font('bold');		
			$rep->TextCol(0, 4, $gl_account . _(' - ') . $account_name);	
			$rep->TextCol(4, 5, _('Ending Balance'));
			$rep->Font();		
			$rep->Font('bold');
			$Total1 = getEnding_bal($to, $gl_account, $masterfile);
			While ($Total_amount = db_fetch($Total1))
			{
				$rep->AmountCol(5, 6, $Total_amount['Debit'], $dec);
				$rep->AmountCol(6, 7, $Total_amount['Credit'], $dec);
			}
			if ($running_bal < 0)
				$rep->AmountCol(7, 8, -$running_bal, $dec);
			else		
				$rep->AmountCol(7, 8, $running_bal, $dec);


			$rep->Font();
			$rep->Line($rep->row - 3,1);

			$rep->NewLine(5);

			$Grand_total_debit = $grand_deb + $grand_forwarded_deb;
			$Grand_total_credit = $grand_cred + $grand_forwarded_cred;
			$Grand_total_balance = abs($Grand_total_debit) - abs($Grand_total_credit);

			//$rep->fontSize += 1;	
			$rep->Font('bold');
			$rep->TextCol(3, 4, _('Grand Total'));
			$rep->AmountCol(5, 6, $Grand_total_debit, $dec);
			$rep->AmountCol(6, 7, $Grand_total_credit, $dec);
			$rep->AmountCol(7, 8, abs($Grand_total_balance), $dec);
			$rep->fontSize -= 1;	
			$rep->Font();
			$rep->Line($rep->row - 2);
			//$rep->SetFooterType('');
			//$rep->fontSize -= 1;
		}
	}
    $rep->End();
}
