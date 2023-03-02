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
$page_security = 'SA_SALES_SUM_REP'; //Modified by spyrax10 25 Jun 2022
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	RobertGwapo
// date:	2021-04-29
// Title:	Sales Summary By Amount
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
	
	$sql ="SELECT A.tran_date, B.months_term, B.category_id,
			CASE WHEN B.months_term = 0 THEN 'CASH'
			WHEN B.months_term = 1 THEN 'REGULAR INSTALLMENT' 
			WHEN B.months_term = 2 THEN 'REGULAR INSTALLMENT' 
			WHEN B.months_term = 3 THEN 'REGULAR INSTALLMENT' 
			ELSE 'INSTALLMENT' END AS Ptype,
			(SELECT SUM(X.ar_amount) AS AMOUNT FROM debtor_loans X LEFT JOIN debtor_trans F ON F.reference = X.reference
			WHERE X.category_id = 14 AND X.months_term = B.months_term AND F.type = A.type AND F.tran_date>='$from' AND F.tran_date<='$to')MOTOR,

			(SELECT SUM(X.ar_amount) AS AMOUNT FROM debtor_loans X LEFT JOIN debtor_trans F ON F.reference = X.reference
			WHERE X.category_id = 15 AND X.months_term = B.months_term AND F.type = A.type AND F.tran_date>='$from' AND F.tran_date<='$to')APPLIANCE,

			(SELECT SUM(X.ar_amount) AS AMOUNT FROM debtor_loans X LEFT JOIN debtor_trans F ON F.reference = X.reference
			WHERE X.category_id = 16 AND X.months_term = B.months_term AND F.type = A.type AND F.tran_date>='$from' AND F.tran_date<='$to')COMPUTERS,

			(SELECT SUM(X.ar_amount) AS AMOUNT FROM debtor_loans X LEFT JOIN debtor_trans F ON F.reference = X.reference
			WHERE X.category_id = 17 AND X.months_term = B.months_term AND F.type = A.type AND F.tran_date>='$from' AND F.tran_date<='$to')PROMOITEM,

			(SELECT SUM(X.ar_amount) AS AMOUNT FROM debtor_loans X LEFT JOIN debtor_trans F ON F.reference = X.reference
			WHERE X.category_id = 18 AND X.months_term = B.months_term AND F.type = A.type AND F.tran_date>='$from' AND F.tran_date<='$to')OTHERS,

			(SELECT SUM(X.ar_amount) AS AMOUNT FROM debtor_loans X LEFT JOIN debtor_trans F ON F.reference = X.reference
			WHERE X.category_id = 19 AND X.months_term = B.months_term AND F.type = A.type AND F.tran_date>='$from' AND F.tran_date<='$to')FURNITURES,

			(SELECT SUM(X.ar_amount) AS AMOUNT FROM debtor_loans X LEFT JOIN debtor_trans F ON F.reference = X.reference
			WHERE X.category_id = 21 AND X.months_term = B.months_term AND F.type = A.type AND F.tran_date>='$from' AND F.tran_date<='$to')POWERPRODUCT,

			(SELECT SUM(X.ar_amount) AS AMOUNT FROM debtor_loans X LEFT JOIN debtor_trans F ON F.reference = X.reference
			WHERE X.category_id = 23 AND X.months_term = B.months_term AND F.type = A.type AND F.tran_date>='$from' AND F.tran_date<='$to')SPGEN

			FROM debtor_trans A
			LEFT JOIN debtor_loans B ON B.reference = A.reference
			WHERE A.type = 10
			AND A.tran_date>='$from'
			AND A.tran_date<='$to'
			GROUP BY B.months_term ORDER BY B.months_term";

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
	
    $dec = user_price_dec();

	$date = explode('/', $from);
	$year1 = $date[2];
	$year2 = $year1 - 1;
	$year3 = $year2 - 1;
	$year4 = $year3 - 1;

	$params = array(0 => $comments,
		1 => array('text' => _('Period'),'from' => $from, 'to' => $to)
	);

	$cols = array(0, 47, 110, 170, 240, 300, 360, 430, 490, 
	0);

	$headers = array(
		_('Term'), 
		_('Motorcyle'),
		_('Appliance'), 
		_('Power Product'),
		_('Furniture'),
		_('Computer'),
		_('Spare Parts'),
		_('Others'),
		_('Promo Item'),
		_('Total'));

	$aligns = array('left', 'left', 'left', 'left', 'left', 'left', 
	'left', 'left', 'left', 'right');

    $rep = new FrontReport(_('Sales Summary By Amount'), "SalesSummaryByAmount", user_pagesize(), 11, $orientation);
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

	$Motorsubtotal = $Applsubtotal = $Poweproductsubtotal = $Furnituresubtotal = 
	$Computersubtotal = $Spgensubtotal = $Othersubtotal = $Promosubtotal = $Alltotal = 0.0;

	$Motorgrandtotal = $Applgrandtotal = $Poweproductgrandtotal = 
	$Furnituregrandtotal = $Computergrandtotal = $Spgengrandtotal = 
	$Othergrandtotal = $Promograndtotal = $Allgrandtotal = $Allgrandtotals = $grandcr = 0.0;

	$res = getTransactions($from, $to);
	$catt = '';

	while ($SSBA = db_fetch($res))
	{

		if ($catt != $SSBA['Ptype'])
		{		
			if ($catt != '')
			{
				$rep->NewLine(2);
				$rep->Font('bold');
				$rep->Line($rep->row  - 4);
				$rep->TextCol(0, 2, _('Sub Total'));
				$rep->AmountCol(1, 2, $Motorsubtotal);
				$rep->AmountCol(2, 3, $Applsubtotal);
				$rep->AmountCol(3, 4, $Poweproductsubtotal);
				$rep->AmountCol(4, 5, $Furnituresubtotal);
				$rep->AmountCol(5, 6, $Computersubtotal);
				$rep->AmountCol(6, 7, $Spgensubtotal);
				$rep->AmountCol(7, 8, $Othersubtotal);
				$rep->AmountCol(8, 9, $Promosubtotal);
				$rep->AmountCol(9, 10, $Allgrandtotal);
				$rep->Font();
				$rep->NewLine(2);
				$Motorsubtotal = 0.0;
				$Applsubtotal = 0.0;
				$Poweproductsubtotal = 0.0;
				$Furnituresubtotal = 0.0;
				$Computersubtotal = 0.0;
				$Spgensubtotal = 0.0;
				$Othersubtotal = 0.0;
				$Promosubtotal = 0.0;
				$Allgrandtotal = 0.0;
			}

			$rep->Font('bold');
            $rep->SetTextColor(0, 0, 255);
			$rep->TextCol(0, 3, $SSBA['Ptype']);
			$catt = $SSBA['Ptype'];
			$rep->Font();
            $rep->SetTextColor(0, 0, 0);
            $rep->NewLine();
		}

		$Dmonth = $SSBA['months_term'];
	    if ($Dmonth == 0){
			$month = 'Month';
	    }
		else {
			$month = 'Months';
		} 

		$Alltotal = $SSBA['MOTOR'] + $SSBA['APPLIANCE'] + 
		$SSBA['POWERPRODUCT'] + $SSBA['FURNITURES'] + 
		$SSBA['COMPUTERS'] + $SSBA['SPGEN'] + 
		$SSBA['OTHERS'] + $SSBA['PROMOITEM'];


		
		$rep->fontSize -= 1;
		$rep->NewLine(1.3);
		$rep->TextCol(0, 1, $SSBA['months_term'] . '  ' . $month);
		$rep->AmountCol(1, 2, $SSBA['MOTOR']);
		$rep->AmountCol(2, 3, $SSBA['APPLIANCE']);
		$rep->AmountCol(3, 4, $SSBA['POWERPRODUCT']);
		$rep->AmountCol(4, 5, $SSBA['FURNITURES']);
		$rep->AmountCol(5, 6, $SSBA['COMPUTERS']);
		$rep->AmountCol(6, 7, $SSBA['SPGEN']);
		$rep->AmountCol(7, 8, $SSBA['OTHERS']);
		$rep->AmountCol(8, 9, $SSBA['PROMOITEM']);
		$rep->AmountCol(9, 10, $Alltotal);
		
		$rep->fontSize += 1;

		$Motorsubtotal += $SSBA['MOTOR'];
		$Motorgrandtotal += $SSBA['MOTOR'];

		$Applsubtotal += $SSBA['APPLIANCE'];
		$Applgrandtotal += $SSBA['APPLIANCE'];

		$Poweproductsubtotal += $SSBA['POWERPRODUCT'];
		$Poweproductgrandtotal += $SSBA['POWERPRODUCT'];

		$Furnituresubtotal += $SSBA['FURNITURES'];
		$Furnituregrandtotal += $SSBA['FURNITURES'];

		$Computersubtotal += $SSBA['COMPUTERS'];
		$Computergrandtotal += $SSBA['COMPUTERS'];

		$Spgensubtotal += $SSBA['SPGEN'];
		$Spgengrandtotal += $SSBA['SPGEN'];

		$Othersubtotal += $SSBA['OTHERS'];
		$Othergrandtotal += $SSBA['OTHERS'];

		$Promosubtotal += $SSBA['PROMOITEM'];
		$Promograndtotal += $SSBA['PROMOITEM'];

		$Allgrandtotal += $Alltotal;
		$Allgrandtotals += $Alltotal;

	}


	$rep->NewLine(0);

	if ($catt != '')
	{
		$rep->NewLine(2);
		$rep->Font('bold');
		$rep->Line($rep->row  - 4);
		$rep->TextCol(0, 2, _('Sub Total'));
		$rep->AmountCol(1, 2, $Motorsubtotal);
		$rep->AmountCol(2, 3, $Applsubtotal);
		$rep->AmountCol(3, 4, $Poweproductsubtotal);
		$rep->AmountCol(4, 5, $Furnituresubtotal);
		$rep->AmountCol(5, 6, $Computersubtotal);
		$rep->AmountCol(6, 7, $Spgensubtotal);
		$rep->AmountCol(7, 8, $Othersubtotal);
		$rep->AmountCol(8, 9, $Promosubtotal);
		$rep->AmountCol(9, 10, $Allgrandtotal);
		$rep->Font();
		$rep->NewLine(2);
	}

	
	$rep->NewLine(2);
	$rep->Line($rep->row - 2);
	$rep->Font('bold');
	$rep->fontSize += 0;	
	$rep->TextCol(0, 7, _('Grand Total'));
	$rep->AmountCol(1, 2, $Motorgrandtotal);
	$rep->AmountCol(2, 3, $Applgrandtotal);
	$rep->AmountCol(3, 4, $Poweproductgrandtotal);
	$rep->AmountCol(4, 5, $Furnituregrandtotal);
	$rep->AmountCol(5, 6, $Computergrandtotal);
	$rep->AmountCol(6, 7, $Spgengrandtotal);
	$rep->AmountCol(7, 8, $Othergrandtotal);
	$rep->AmountCol(8, 9, $Promograndtotal);
	$rep->AmountCol(9, 10, $Allgrandtotals);
	//$rep->SetFooterType('compFooter');
    $rep->End();
}
