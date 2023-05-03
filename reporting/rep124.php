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
$page_security = 'SA_TERMOD_REP'; // Modified by spyrax10 24 Jun 2022
// ----------------------------------------------------------------
// $ Revision:	7.0 $
// Creator:	Prog6
// date_:	2022-03-01
// Title:	Sales Report - Accounts with Term Modification for the Month of
// Mantis Issue #: 679
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/taxes/tax_calc.inc");
include_once($path_to_root . "/sales/includes/db/sales_target_db.inc");

print_Accounts_with_Term_Modification();

//GET TERM MODIFICATION TRANSACTIONS
function getTransactions($period_from,$period_to)
{	
	$sql = "SELECT 
				d.name
				,c.invoice_date AS `date_sold`
				,a.term_mod_date AS `date_modified`
				,c.category_id AS `classification`
				,c.months_term AS `orig_term`
				,a.months_term AS `new_term`
				,c.ar_amount AS `Existing_AR`
				,a.ar_amount AS `New_AR`
				,(c.ar_amount-a.ar_amount) AS `AR Reduction`
			FROM debtor_term_modification a
			INNER JOIN debtor_trans b ON b.trans_no = a.trans_no and a.type = b.type
			LEFT JOIN debtor_loans c ON c.invoice_ref_no = a.invoice_ref_no
			LEFT JOIN debtors_master d ON d.debtor_no = b.debtor_no
			WHERE b.type = '" . ST_SITERMMOD . "' 
			AND a.term_mod_date BETWEEN '$period_from' AND '$period_to' ORDER BY a.term_mod_date ";
	
	return db_query($sql,"No transactions were returned");
}

function getCategory($category)
{
	$sql = "SELECT `description` FROM `stock_category` WHERE `category_id` = $category ";

	return db_query($sql,"No transactions were returned");
}

function print_Accounts_with_Term_Modification()
{
	global $path_to_root;
	
	$date_param = $_POST['PARAM_0'];
	$date_end = $_POST['PARAM_1'];
	$comments = $_POST['PARAM_2'];
	$destination = $_POST['PARAM_3'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
				
	//$orientation = ($orientation ? 'L' : 'P');

	$orientation = 'L';
    $dec = user_price_dec();

	$year_param = date("Y",strtotime($date_param));
	$month_param = date("m",strtotime($date_param));
	$last_year = $year_param - 1;
	$last2_years = $year_param - 2;
	$last3_years_and_below = $year_param - 3;

	$period_from = date("Y-m-d",strtotime($date_param));
	$period_to = date("Y-m-d",strtotime($date_end));

       
    $params = array(0 => $comments,	
		 1 => array('text' => _('Period from'), 'from' => $date_param, 'to' => $date_end));

	$cols = array(5, 95, 145, 205, 275, 305, 330, 390, 450, 510, 570, 630, 690, 750, 810);

	$headers = array(/*
		_('Customer'),
		_('Date Sold'),
		_('Date Modified'),
		_('Category'),
		_('Orig. Term'),
		_('New Term'),
		_('Exist. A/R Bal'),
		_('New A/R Bal'),
		_('Saler/AR Reduction'),
		_('Sales AJE '.$year_param),
		_('Retained Earnings AJE '.$last_year),
		_('Retained Earnings AJE '.$last2_years),
		_('Retained Earnings AJE '.$last3_years_and_below),
		_('Retained Earnings AJE Total')*/
		);

	$aligns = array('center', 'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center');

	$rep = new FrontReport(_('Accounts with Term Modification'), "AccountsWithTermMod", "long", 9, $orientation);

    //if ($orientation == 'L')
    //	recalculate_cols($cols);
	
	$rep->fontSize -= 1;
    $rep->Info($params, $cols, $headers, $aligns, 
		null, null, null, true, true, true);
    $rep->SetHeaderType('SL_Summary_Header');
	$rep->NewPage();
	
	
	
	$rep->NewLine();
	$rep->Font('bold');
	$rep->TextCol(0, 4, '',null,null,1);
	$rep->TextCol(4, 6, 'Term',null,null,1);
	$rep->TextCol(6, 8, 'A/R Balance',null,null,1);
	$rep->TextCol(8, 9, 'Sales A/R',null,null,1);
	$rep->TextCol(9, 10, 'Sales AJE ',null,null,1);
	$rep->TextCol(10, 14, 'Retained Earnings AJE',null,null,1);
	$rep->NewLine();	
	$rep->TextCol(0, 1, 'Customer',null,null,1);
	$rep->TextCol(1, 2, 'Date Sold',null,null,1);
	$rep->TextCol(2, 3, 'Date Modified',null,null,1);
	$rep->TextCol(3, 4, 'Category',null,null,1);
	$rep->TextCol(4, 5, 'Orig',null,null,1);
	$rep->TextCol(5, 6, 'New',null,null,1);
	$rep->TextCol(6, 7, 'Old GSP',null,null,1);
	$rep->TextCol(7, 8, 'New GSP',null,null,1);
	$rep->TextCol(8, 9, 'Reduction',null,null,1);
	$rep->TextCol(9, 10, $year_param,null,null,1);
	$rep->TextCol(10, 11, $last_year,null,null,1);
	$rep->TextCol(11, 12, $last2_years,null,null,1);
	$rep->TextCol(12, 13, $last3_years_and_below.' & below',null,null,1);
	$rep->TextCol(13, 14, 'Total',null,null,1);
	$rep->Font();	
	$rep->NewLine(1.5);
	//$rep->AmountCol2(1, 2, $actual_amount, $dec);
	//$rep->AmountCol2(2, 3, $target_amount, $dec);

	//Get data from Target DB
	$res = getTransactions($period_from,$period_to);
	
	//$rep->NewLine();
	//$rep->Font('italic');
	//$rep->Font('bold');
	//$rep->SetTextColor(128, 0, 0);
	//$rep->TextCol(0, 4,$category_name. ' - Sales Target Amount in '.$year_param);
	//$rep->Font();
	//$rep->SetTextColor(0, 0, 0);
	//$rep->NewLine();
	//$rep->Line($rep->row + 9);
	$row_count = 0;
	$total_Exist_AR = 0;
	$total_New_AR = 0;
	$total_AR_reduction = 0;
	$total_sales_mod_year = 0;
	$Total_sales_mod_year1 = 0;
	$total_sales_mod_year2 = 0;
	$total_sales_mod_year3 = 0;
	$total_sales = 0;


	While($myRows = db_fetch($res))
	{
		$cat_result = getCategory($myRows['classification']);
		$catRows = db_fetch($cat_result);
		$catName = $catRows['description'];
		$Sale_AR_Reduction = $myRows['AR Reduction'];
		$Sales_invoice_year = date("Y",strtotime($myRows['date_sold'])); //date("Y",strtotime($date_param));

		$rep->TextCol(0, 1, $myRows['name']);
		$rep->TextCol(1, 2, $myRows['date_sold']);
		$rep->TextCol(2, 3, $myRows['date_modified']);
		$rep->TextCol(3, 4, $catName);
		$rep->TextCol(4, 5, $myRows['orig_term']);
		$rep->TextCol(5, 6, $myRows['new_term']);
		$rep->AmountCol2(6, 7, $myRows['Existing_AR'], $dec);
		$rep->AmountCol2(7, 8, $myRows['New_AR'], $dec);
		$rep->AmountCol2(8, 9, $Sale_AR_Reduction, $dec);

		if($Sales_invoice_year == $year_param)
		{
			$rep->AmountCol2(9, 10, $Sale_AR_Reduction, $dec);
			$rep->TextCol(10, 11, "-");
			$rep->TextCol(11, 12, "-");
			$rep->TextCol(12, 13, "-");
			$total_sales_mod_year = $total_sales_mod_year + $Sale_AR_Reduction;
		}
		if($Sales_invoice_year == $last_year)
		{
			
			$rep->TextCol(9, 10, "-");
			$rep->AmountCol2(10, 11, $Sale_AR_Reduction, $dec);
			$rep->TextCol(11, 12, "-");
			$rep->TextCol(12, 13, "-");
			$Total_sales_mod_year1 = $Total_sales_mod_year1 + $Sale_AR_Reduction;
		}
		if($Sales_invoice_year == $last2_years)
		{
			$rep->TextCol(9, 10, "-");
			$rep->TextCol(10, 11, "-");
			$rep->AmountCol2(11, 12, $Sale_AR_Reduction, $dec);
			$rep->TextCol(12, 13, "-");
			$total_sales_mod_year2 = $total_sales_mod_year2 + $Sale_AR_Reduction;
		}
		if($Sales_invoice_year == $last3_years_and_below)
		{
			$rep->TextCol(9, 10, "-");
			$rep->TextCol(10, 11, "-");
			$rep->TextCol(11, 12, "-");
			$rep->AmountCol2(12, 13, $Sale_AR_Reduction, $dec);
			$total_sales_mod_year3 = $total_sales_mod_year3 + $Sale_AR_Reduction;
		}
		$rep->AmountCol2(13, 14, $Sale_AR_Reduction, $dec);


		$total_Exist_AR += $myRows['Existing_AR'];
		$total_New_AR += $myRows['New_AR'];
		$total_AR_reduction += $Sale_AR_Reduction;
		$total_sales += $Sale_AR_Reduction;


		$row_count = $row_count + 1;
		$Sale_AR_Reduction = 0;
		$rep->NewLine();
	}
	
	$rep->NewLine();
	$rep->Font('bold');
	$rep->TextCol(4, 6, "TOTAL");	
	$rep->AmountCol2(6, 7, $total_Exist_AR, $dec);	
	$rep->AmountCol2(7, 8, $total_New_AR, $dec);	
	$rep->AmountCol2(8, 9, $total_AR_reduction, $dec);
	$rep->AmountCol2(9, 10, $total_sales_mod_year, $dec);
	$rep->AmountCol2(10, 11, $Total_sales_mod_year1, $dec);
	$rep->AmountCol2(11, 12, $total_sales_mod_year2, $dec);
	$rep->AmountCol2(12, 13, $total_sales_mod_year3, $dec);
	$rep->AmountCol2(13, 14, $total_sales, $dec);
	//$rep->Line($rep->row - 2);	
	//$rep->NewLine(3);
	//$rep->Font();
	//$rep->Line($rep->row - 2);
	//$rep->SetFooterType('');
	//$rep->fontSize -= 1;
    $rep->End();
}
