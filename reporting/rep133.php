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
$page_security = 'SA_WARRANTY_MONITORING'; 
// ----------------------------------------------------------------
// $ Revision:	7.0 $
// Creator:	Prog6
// date_:	2023-10-18
// Title:	Warranty Monitoring Report
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

print_warranty_monitoring_report();

function getTransactions()
{
	$from = date2sql($from);
	$to = date2sql($to);
	
	$sql = "";


	return db_query($sql,"No transactions were returned");
}

function print_warranty_monitoring_report()
{
	global $path_to_root;
	
	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$supplier = $_POST['PARAM_2'];
	$comments = $_POST['PARAM_3'];
	$orientation = $_POST['PARAM_4'];
	$destination = $_POST['PARAM_5'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
				
	$orientation = ($orientation ? 'L' : 'P');
	
    $dec = user_price_dec();
	
	$params = array(0 => $comments,
		1 => array('text' => _('Period'),'from' => $from, 'to' => $to),
		2 => array('text' => _('Supplier'), 'from' => $cat, 'to' => ''));

	//              brand    cat      sub-cat   date       SIno.
	$cols = array(0,      30,     80,  	     105,      140, 

	//       name     model     serial     chassis    type       term      qty
		195,      255,       315,       375,       425,     445,     455,   

	//      LCP     Cost     gross		DP		lending_sale	 discount1   discount2	 Net_Sales		 Agent
		465,    495,     530,     560,	590,	       625,       650,         670,         705,      0);

	$headers = array(
		_('#'), 
		_('Frame No.'),
		_('Engine No.'), 
		_('Invoice #'),
		_('Sold Date'),
		_('Sold To'),
		_('Address'),
		_('Contact No.'),
		_('Birthdate'),
		_('Gender'),
		_('Age'),
		_('Contact Person'),
		_('Qty'),
		_('Model'),
		_('WRC/EW Code'),
		_('FSC Series'),
		_('Branch'),
		_('Sales Type'),
		);

	$aligns = array('left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 
	'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left');

	$rep = new FrontReport(_('Warranty Monitoring')., "WarrantyMonitoringReport", "legal", 9, $orientation);

    if ($orientation == 'L')
    	recalculate_cols($cols);
	
	$rep->fontSize -= 2;
    $rep->Info($params, $cols, $headers, $aligns, 
		null, null, null, true, '' , true);
    //$rep->SetHeaderType('SL_Summary_Header');
    $rep->SetHeaderType('PO_Header');
	$rep->NewPage();

	$Tot_qty=0;
	$Tot_lcp=0;
	$Tot_ucost = 0;
	$Tot_gross = 0;
	$Tot_dp = 0;
	$Tot_lend = 0;
	$row_gross = 0;
	$row_dp = 0;
	$row_unitcost = 0;
	$Tot_discount1 = 0;	
	$Tot_discount2 = 0;
	$Tot_netsales = 0;
	$row_total_lcp = 0;
	$row_total_discount1 = 0;
	$row_total_discount2 = 0;
	$lending_sales = 0;
	$res = getTransactions($from, $to, $cat_id, $brand_code, $cust_id, $sales_type, $item_model);

	While ($GRNs = db_fetch($res))
	{
		if($GRNs['Type']=='CASH')
		{
			$row_gross = $GRNs['Qty']*$GRNs['Unit_price'];
		}
		else 
		{			
			$row_gross = $GRNs['Qty']*$GRNs['grossAmnt'];
		}
				
		$row_unitcost = $GRNs['UnitCost']*$GRNs['Qty'];
		$row_total_lcp = $GRNs['LCP'] * $GRNs['Qty'];
		$row_total_discount1 = $GRNs['discount1'] * $GRNs['Qty'];
		$row_total_discount2 = $GRNs['discount2'] * $GRNs['Qty'];
		$row_dp = $GRNs['dp_amount'];

		if($GRNs['category_id'] == '18'/*others*/ || $GRNs['category_id'] == '22'/*sp-appl*/ || $GRNs['category_id'] == '23'/*sp-gen*/ || $GRNs['category_id'] == '24'/*sp-rep*/ )
		{
			$lending_sales = $row_gross;
		}
		else if($GRNs['category_id'] == '17'/*promo item*/ && $GRNs['Serial'] == '')
		{
			$lending_sales = $row_gross;
		}
		else
		{
			$lending_sales = $GRNs['ar_amount'];
		}
		
		$row_netsales = $row_gross - $row_total_discount1 - $row_total_discount2;

		$dec2 = get_qty_dec($GRNs['Model']);

		$rep->NewLine();
		$rep->TextCol(0, 1, $GRNs['Brand']);
		$rep->TextCol(1, 2, $GRNs['Category']);
		$rep->TextCol(2, 3, $GRNs['Subcategory']);
		$rep->TextCol(3, 4, sql2date($GRNs['Date']));
		$rep->TextCol(4, 5, $GRNs['Invoice']);
		$rep->TextCol(5, 6, $GRNs['Name']);
		$rep->TextCol(6, 7, $GRNs['Model']);
		$rep->TextCol(7, 8, $GRNs['Serial']);
		$rep->TextCol(8, 9, $GRNs['Chassis']);
		$rep->TextCol(9, 10, $GRNs['Type']);
		$rep->TextCol(10, 11, $GRNs['Term']);
		$rep->TextCol(11, 12, $GRNs['Qty']);
		$rep->AmountCol2(12, 13, $row_unitcost);
		$rep->AmountCol2(13, 14, $row_total_lcp);				
		$rep->AmountCol2(14, 15, $row_gross);				
		$rep->AmountCol2(15, 16, $row_dp);
		$rep->AmountCol2(16, 17, $lending_sales);
		$rep->AmountCol2(17, 18, $row_total_discount1);
		$rep->AmountCol2(18, 19, $row_total_discount2);
		$rep->AmountCol2(19, 20, $row_netsales);
		$rep->TextCol(20, 21, $GRNs['SalesAgent']);

		$qty = $GRNs['Qty'];
		$Tot_qty += $qty;

		$lcp = $row_total_lcp;
		$Tot_lcp += $lcp;

		$ucost = $row_unitcost;
		$Tot_ucost += $ucost;

		$grossAmnt = $row_gross;
		$Tot_gross += $grossAmnt;

		$Tot_dp += $row_dp;

		$lendSale = $lending_sales;
		$Tot_lend += $lendSale;

		$discount1 = $row_total_discount1;
		$Tot_discount1 += $discount1;
		$discount2 = $row_total_discount2;
		$Tot_discount2 += $discount2;

		$netSales = $row_netsales;
		$Tot_netsales += $netSales;

		$rep->NewLine(0, 1);
	}

	$rep->NewLine();
	$rep->Line($rep->row - 2);

	$rep->NewLine();
	$rep->Font('bold');
	$rep->fontSize -= 1;	
	$rep->TextCol(0, 11, _('Total'));
	$rep->AmountCol(11, 12, $Tot_qty);
	$rep->AmountCol(12, 13, $Tot_ucost, $dec);
	$rep->AmountCol(13, 14, $Tot_lcp, $dec);
	$rep->AmountCol(14, 15, $Tot_gross, $dec);
	$rep->AmountCol(15, 16, $Tot_dp, $dec);
	$rep->AmountCol(16, 17, $Tot_lend, $dec);
	$rep->AmountCol(17, 18, $Tot_discount1, $dec);
	$rep->AmountCol(18, 19, $Tot_discount2, $dec);
	$rep->AmountCol(19, 20, $Tot_netsales, $dec);

	$rep->Line($rep->row - 2);
	//$rep->SetFooterType('compFooter');
	$rep->fontSize -= 2;
    $rep->End();
}
