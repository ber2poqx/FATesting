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
$page_security = 'SA_SALES_TARGET';
// ----------------------------------------------------------------
// $ Revision:	7.0 $
// Creator:	Prog6
// date_:	2021-11-10
// Title:	Sales Target Report
// Mantis Issue #: 
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/taxes/tax_calc.inc");
include_once($path_to_root . "/sales/includes/db/sales_target_db.inc");

print_Sales_Target_Amount();

//GET TARGET AMOUNT
function getTransactions($year_param, $category)
{	
	$sql = "SELECT * FROM `sales_target` WHERE `type` = 'amount' AND `year` = '$year_param' AND category_id = '$category'";

	return db_query($sql,"No transactions were returned");
}

//GET ACTUAL AMOUNT
function getActual($year_param, $month, $category)
{
	/*
	$sql = "SELECT `tran_date`, SUM(`ov_amount`) AS `amount`
			FROM `debtor_trans`
			WHERE YEAR(`tran_date`) = $year_param AND MONTH(`tran_date`) = $month AND reference NOT LIKE 'CR-%' AND reference NOT LIKE '%-SR%'
			GROUP BY MONTH(`tran_date`)";
	*/

	$sql = "SELECT `invoice_date`, SUM(`lcp_amount`) AS `amount`
			FROM `debtor_loans`
			WHERE YEAR(`invoice_date`) = '$year_param' AND MONTH(`invoice_date`) = '$month' AND category_id = '$category'
			GROUP BY MONTH(`invoice_date`)";

	return db_query($sql,"No transactions were returned");
}

//GET TARGET QUANTITY
function getTargetQty($year_param, $category)
{	
	$sql = "SELECT * FROM `sales_target` WHERE `type` = 'quantity' AND `year` = '$year_param' AND category_id = '$category'";

	return db_query($sql,"No transactions were returned");
}

//GET ACTUAL QUANTITY
function getActualQty($year_param, $month, $category)
{	
	/*$sql = "SELECT YEAR(dt.`tran_date`) AS `Year`, MONTH(dt.`tran_date`) AS `Month`, SUM(dtd.`quantity`) AS `quantity`
			FROM `debtor_trans_details` dtd
				LEFT JOIN `debtor_trans` dt ON dtd.debtor_trans_no = dt.trans_no
    				AND dtd.debtor_trans_type = dt.type
				LEFT JOIN `debtor_loans` dl ON dl.trans_no = dt.trans_no
			WHERE YEAR(dt.`tran_date`) = $year_param AND MONTH(dt.`tran_date`) = $month AND dl.category_id = '$category'
			GROUP BY MONTH(dt.`tran_date`)";*/

	$sql = "SELECT YEAR(dt.`tran_date`) AS `Year`, MONTH(dt.`tran_date`) AS `Month`, SUM(dtd.`quantity`) AS `quantity`
			FROM `debtor_trans_details` dtd
				LEFT JOIN `debtor_trans` dt ON dtd.debtor_trans_no = dt.trans_no
    				AND dtd.debtor_trans_type = dt.type
				LEFT JOIN `debtor_loans` dl ON dl.trans_no = dt.trans_no
			WHERE YEAR(dt.`tran_date`) = '$year_param' AND MONTH(dt.`tran_date`) = '$month' AND dl.category_id = '$category' AND dt.type = '".ST_SALESINVOICE."'
            GROUP BY MONTH(dt.`tran_date`)";

	return db_query($sql,"No transactions were returned");
}

function getCategory($category)
{
	$sql = "SELECT `description` FROM `stock_category` WHERE `category_id` = $category ";

	return db_query($sql,"No transactions were returned");
}

function print_Sales_Target_Amount()
{
	global $path_to_root;
	
	$date_param = $_POST['PARAM_0'];
	$category = $_POST['PARAM_1'];	
	$comments = $_POST['PARAM_2'];
	$destination = $_POST['PARAM_3'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
				
	//$orientation = ($orientation ? 'L' : 'P');

	//Get category name

	
	if($category == '-1')
	{
		$category_name = "All Categories";
	}
	else
	{
		$myrow1 = getCategory($category);
		$cat_result = db_fetch($myrow1);
		$category_name = $cat_result['description'];
	}


	$orientation = 'P';
    $dec = user_price_dec();

	$year_param = date("Y",strtotime($date_param));

       
    $params = array(0 => $comments,	
		 1 => array('text' => _('Sales Target Year'), 'from' => $year_param, 'to' => ''),
		 2 => array('text' => _('Category'), 'from' => $category_name, 'to' => ''));

	$cols = array(140, 210, 300, 380, 470);

	$headers = array(
		_('Month'),
		_('Actual'),
		_('Target'),
		_('Status')
		);

	$aligns = array('left', 'right', 'right', 'center');

	$rep = new FrontReport(_('Sales Target Report'), "SalesTargetReport", "letter", 9, $orientation);

    //if ($orientation == 'L')
    //	recalculate_cols($cols);
	
	$rep->fontSize -= 1;
    $rep->Info($params, $cols, $headers, $aligns, 
		null, null, null, true, true, true);
    $rep->SetHeaderType('SL_Summary_Header');
	$rep->NewPage();
	
	//Get data from Target DB
	$res = getTransactions($year_param, $category);
	
		
	While ($targetAmnt = db_fetch($res))
	{
		$target_jan = $targetAmnt['jan'];	
		$target_feb = $targetAmnt['feb'];
		$target_mar = $targetAmnt['mar'];
		$target_apr = $targetAmnt['apr'];
		$target_may = $targetAmnt['may'];
		$target_jun = $targetAmnt['jun'];
		$target_jul = $targetAmnt['jul'];
		$target_aug = $targetAmnt['aug'];
		$target_sep = $targetAmnt['sep'];
		$target_oct = $targetAmnt['oct'];
		$target_nov = $targetAmnt['nov'];
		$target_dec = $targetAmnt['dece'];
		$target_year = $targetAmnt['year'];
	}

	$res2 = getTargetQty($year_param, $category);
		
	While ($targetQty = db_fetch($res2))
	{
		$targetq_jan = $targetQty['jan'];	
		$targetq_feb = $targetQty['feb'];
		$targetq_mar = $targetQty['mar'];
		$targetq_apr = $targetQty['apr'];
		$targetq_may = $targetQty['may'];
		$targetq_jun = $targetQty['jun'];
		$targetq_jul = $targetQty['jul'];
		$targetq_aug = $targetQty['aug'];
		$targetq_sep = $targetQty['sep'];
		$targetq_oct = $targetQty['oct'];
		$targetq_nov = $targetQty['nov'];
		$targetq_dec = $targetQty['dece'];
		$targetq_year = $targetQty['year'];
	}

	 $hit_Status = " TARGET HIT";
	 $nhit_status = "target not hit";
	 $no_status = "no target set";
	 $noAct_status = "no actual sales";
	 $noBoth_status = "NO target and actual sales";
	 $month_name = "";
	 $month_counter = 1;
	 $target_amount = 0;
	 $actual_amount = 0;
	 $target_qty = 0;
	 $actual_qty = 0;

	// $rep->NewLine(0.5);

		
	// BEGIN SALES AMOUNT TABLE ACTUAL AND TARGET

	$rep->NewLine();
	$rep->Font('italic');
	$rep->Font('bold');
	$rep->SetTextColor(128, 0, 0);
	$rep->TextCol(0, 4,$category_name. ' - Sales Target Amount in '.$year_param);
	$rep->Font();
	$rep->SetTextColor(0, 0, 0);
	$rep->NewLine();

	$rep->Line($rep->row + 9);

	While($month_counter <= 12){
			
		$actual_amount = 0;

		if($month_counter==1){$month_name = "January";$target_amount = $target_jan;}
		elseif($month_counter==2){$month_name = "February";$target_amount = $target_feb;}
		elseif($month_counter==3){$month_name = "March";$target_amount = $target_mar;}
		elseif($month_counter==4){$month_name = "April";$target_amount = $target_apr;}
		elseif($month_counter==5){$month_name = "May";$target_amount = $target_may;}
		elseif($month_counter==6){$month_name = "June";$target_amount = $target_jun;}
		elseif($month_counter==7){$month_name = "July";$target_amount = $target_jul;}
		elseif($month_counter==8){$month_name = "August";$target_amount = $target_aug;}
		elseif($month_counter==9){$month_name = "September";$target_amount = $target_sep;}
		elseif($month_counter==10){$month_name = "October";$target_amount = $target_oct;}
		elseif($month_counter==11){$month_name = "November";$target_amount = $target_nov;}
		elseif($month_counter==12){$month_name = "December";$target_amount = $target_dec;}

		//GET DATA FROM ACTUAL
		$act = getActual($year_param, $month_counter, $category);
		While ($ActualAmnt = db_fetch($act))
		{
			$actual_amount = $ActualAmnt['amount'];
		}
									
		$rep->Font('bold');
		$rep->TextCol(0, 1, $month_name);
		$rep->Font();
		$rep->AmountCol2(1, 2, $actual_amount, $dec);
		$rep->AmountCol2(2, 3, $target_amount, $dec);

		//FOR STATUS CONDITIONS....
		if($target_amount == 0 && $actual_amount == 0)
		{
			$rep->fontSize -= 2;
			$rep->TextCol(3, 4, $noBoth_status);
			$rep->fontSize += 2;
		}
		else if($target_amount == 0)
		{
			$rep->fontSize -= 2;
			$rep->TextCol(3, 4, $no_status);
			$rep->fontSize += 2;
		}
		else if($actual_amount == 0)
		{
			$rep->fontSize -= 2;
			$rep->TextCol(3, 4, $noAct_status);
			$rep->fontSize += 2;
		}
		else if($actual_amount >= $target_amount)
		{
			$rep->Font('bold');
			$rep->SetTextColor(0, 128, 0); //green
			//$rep->SetTextColor(0, 75, 255); //royal blue
			//$rep->SetTextColor(194, 24, 7); //chili red
			$rep->fontSize += 1;
			$rep->TextCol(3, 4, $hit_Status);
			$rep->fontSize -= 1;
			$rep->SetTextColor(0, 0, 0); //black
			$rep->Font();
		}
		else if($actual_amount < $target_amount)
		{				
			$rep->SetTextColor(194, 24, 7); //chili red
			$rep->TextCol(3, 4, $nhit_status);
			$rep->SetTextColor(0, 0, 0); //black
		}
		
		
		if($month_counter != 12)
			$rep->NewLine();

		$month_counter = $month_counter + 1;			
	}
	$rep->Line($rep->row - 2);
	$month_counter = 1;		
	$rep->NewLine(3);
	// END SALES AMOUNT TABLE ACTUAL AND TARGET


	// BEGIN SALES QUANTITY TABLE ACTUAL AND TARGET
		
	$rep->Font('italic');
	$rep->Font('bold');
	$rep->SetTextColor(128, 0, 0);
	$rep->TextCol(0, 4, $category_name. ' - Sales Target Quantity in '.$year_param);
	$rep->Font();
	$rep->SetTextColor(0, 0, 0);
	$rep->NewLine();

	$rep->Line($rep->row + 9);

	While($month_counter <= 12){
			
		$actual_qty = 0;

		if($month_counter==1){$month_name = "January";$target_qty = $targetq_jan;}
		elseif($month_counter==2){$month_name = "February";$target_qty = $targetq_feb;}
		elseif($month_counter==3){$month_name = "March";$target_qty = $targetq_mar;}
		elseif($month_counter==4){$month_name = "April";$target_qty = $targetq_apr;}
		elseif($month_counter==5){$month_name = "May";$target_qty = $targetq_may;}
		elseif($month_counter==6){$month_name = "June";$target_qty = $targetq_jun;}
		elseif($month_counter==7){$month_name = "July";$target_qty = $targetq_jul;}
		elseif($month_counter==8){$month_name = "August";$target_qty = $targetq_aug;}
		elseif($month_counter==9){$month_name = "September";$target_qty = $targetq_sep;}
		elseif($month_counter==10){$month_name = "October";$target_qty = $targetq_oct;}
		elseif($month_counter==11){$month_name = "November";$target_qty = $targetq_nov;}
		elseif($month_counter==12){$month_name = "December";$target_qty = $targetq_dec;}

		//GET DATA FROM ACTUAL
		$actq = getActualQty($year_param, $month_counter, $category); 
		While ($ActualQty = db_fetch($actq))
		{
			$actual_qty = $ActualQty['quantity'];
		}
									
		$rep->Font('bold');
		$rep->TextCol(0, 1, $month_name);
		$rep->Font();
		$rep->AmountCol(1, 2, $actual_qty);
		$rep->AmountCol(2, 3, $target_qty);

		//FOR STATUS CONDITIONS....
		if($target_qty == 0 && $actual_qty == 0)
		{
			$rep->fontSize -= 2;
			$rep->TextCol(3, 4, $noBoth_status);
			$rep->fontSize += 2;
		}
		else if($target_qty == 0)
		{
			$rep->fontSize -= 2;
			$rep->TextCol(3, 4, $no_status);
			$rep->fontSize += 2;
		}
		else if($actual_qty == 0)
		{
			$rep->fontSize -= 2;
			$rep->TextCol(3, 4, $noAct_status);
			$rep->fontSize += 2;
		}
		else if($actual_qty >= $target_qty)
		{
			$rep->Font('bold');
			$rep->SetTextColor(0, 128, 0); //green
			//$rep->SetTextColor(0, 75, 255); //royal blue
			//$rep->SetTextColor(194, 24, 7); //chili red
			$rep->fontSize += 1;
			$rep->TextCol(3, 4, $hit_Status);
			$rep->fontSize -= 1;
			$rep->SetTextColor(0, 0, 0); //black
			$rep->Font();
		}
		else if($actual_qty < $target_qty)
		{				
			$rep->SetTextColor(194, 24, 7); //chili red
			$rep->TextCol(3, 4, $nhit_status);
			$rep->SetTextColor(0, 0, 0); //black
		}
			
		if($month_counter != 12)
			$rep->NewLine();

		$month_counter = $month_counter + 1;
	}
	$month_counter = 1;
	
	

	$rep->Font();
	$rep->Line($rep->row - 2);
	//$rep->SetFooterType('');
	$rep->fontSize -= 1;
    $rep->End();
}
