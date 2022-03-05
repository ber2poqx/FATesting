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
$page_security = 'SA_TAXREP';
// ----------------------------------------------------------------
// $ Revision:	7.0 $
// Creator:	Prog6
// date_:	2021-07-24
// Title:	Sales Summary (Insurance) Report
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

print_sales_summary_report();

function getTransactions($from, $to, $cat_id)
{
	$from = date2sql($from);
	$to = date2sql($to);
	$sql = "
		SELECT ib6.name as Brand
			,dtd4.color_code
            ,CASE 
            	WHEN dtd4.color_code IS NULL THEN '-'
                WHEN sc10.description <> 'MOTORCYCLE' THEN '-'
                ELSE dtd4.color_code END as `color`
			,dt1.type
		    ,sc10.description as Category
		    ,ii7.name as Subcategory
		    ,dt1.tran_date as `Date`
		    ,dt1.reference as `Invoice`
		    ,dm3.name as Name
		    ,dtd4.stock_id as Model
		    ,dtd4.lot_no as `Serial`
		    ,dtd4.chassis_no as `Chassis`
		    ,dl2.ar_amount as `grossAmnt`
		    ,dl2.discount_downpayment as `discountdp`
		    ,dl2.months_term as Term
		    ,dtd4.quantity as Qty
		    ,dl2.lcp_amount as LCP
		    ,dtd4.standard_cost as `UnitCost`
            ,sn9.salesman_name
		    ,CASE
		    	WHEN so8.salesman_id = 0 THEN 'Office Sales'
		        WHEN so8.salesman_id = 1 THEN 'Office Sales'
		        ELSE 'with Agent' END AS `Salestype`		    
		FROM ".TB_PREF."debtor_trans_details dtd4
		    INNER JOIN ".TB_PREF."debtor_trans dt1 on dt1.trans_no = dtd4.debtor_trans_no and dt1.type = dtd4.debtor_trans_type
			LEFT JOIN ".TB_PREF."debtor_loans dl2 on dtd4.debtor_trans_no = dl2.trans_no
		    LEFT JOIN ".TB_PREF."debtors_master dm3 on dt1.debtor_no = dm3.debtor_no
		    LEFT JOIN ".TB_PREF."stock_master sm5 on sm5.stock_id = dtd4.stock_id
		    LEFT JOIN ".TB_PREF."item_brand ib6 on sm5.brand = ib6.id
		    LEFT JOIN ".TB_PREF."item_importer ii7 on sm5.importer = ii7.id
		    LEFT JOIN ".TB_PREF."sales_orders so8 on dt1.trans_no = so8.order_no
		    LEFT JOIN ".TB_PREF."salesman sn9 on so8.salesman_id = sn9.salesman_code
		    LEFT JOIN ".TB_PREF."stock_category sc10 on dl2.category_id = sc10.category_id
		WHERE dt1.type = ".ST_SALESINVOICE."
			AND dtd4.standard_cost <> 0
			AND dt1.tran_date <= '$to'
			AND	dt1.tran_date >='$from'";

	if ($cat_id != ALL_TEXT)
	 	$sql .= " AND so8.category_id =".db_escape($cat_id);

		$sql .= " ORDER BY sc10.description, dt1.reference";

	return db_query($sql,"No transactions were returned");
}

function print_sales_summary_report()
{
	global $path_to_root;
	
	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$cat_id = $_POST['PARAM_2'];
	$comments = $_POST['PARAM_3'];
	$destination = $_POST['PARAM_4'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
				
	//$orientation = ($orientation ? 'L' : 'P');

	$orientation = 'L'; // Landscape only
	
    $dec = user_price_dec();
	
	if ($cat_id < 0)
		$cat_id = 0;
	if ($cat_id == 0)
		$cat = _('ALL');
	else
		$cat = get_category_name($cat_id);

	
	$params = array(
		0 => $comments,
		1 => array('text' => _('Period'),'from' => $from, 'to' => $to),
		2 => array('text' => _('Category'), 'from' => $cat, 'to' => ''));

	//              1    2    3    4   model chassis serial color  LCP  term  type     ===> allign LEFT
	$cols = array(0,  50,  95,  150,   215,  285,    335,   390,   470, 510,  540,   0);

	$headers = array(
		_(''),
		_('Buy Date'),
		_('SI #'), 
		_('Customers Name'),
		_('Model'),
		_('Chassis #'),
		_('Serial/Engine #'),
		_('Color'),
		_('LCP Amount'),
		_('Term'),
		_('Sales Type')
		);
    //                               model                           serial #
	$aligns = array('right', 'center', 'left', 'left', 'left', 'left', 'left', 'left', 'right', 
	'center', 'center');

	$rep = new FrontReport(_('Sales Summary (Insurance) Report'), "SSIReport", "letter", 9, $orientation);

    if ($orientation == 'L')
    	recalculate_cols($cols);
	
	$rep->fontSize -= 2;
    $rep->Info($params, $cols, $headers, $aligns, 
		null, null, null, true, true, true);
    $rep->SetHeaderType('PO_Header');
	$rep->NewPage();


	$count = 1;
	$Tot_lcp = 0;
	$Tot_all = 0;
	$Category = '';

	$res = getTransactions($from, $to, $cat_id);
	
	While ($myRow121 = db_fetch($res))
	{		
		$dec2 = get_qty_dec($myRow121['Model']);

		if ($Category != $myRow121['Category'])
		{
			if ($Category != '')
			{
				$rep->NewLine(1.5);
				$rep->Font('bold');
				$rep->Line($rep->row + 8);
				$rep->fontSize += 0.7;
				$rep->TextCol(7, 8, _('Category Total'));
				$rep->AmountCol(8, 9, $Tot_lcp, $dec);
				$rep->fontSize -= 0.7;
				$rep->Font();

				//$rep->Line($rep->row - 2);
				$rep->NewLine(2);
				$Tot_all = $Tot_all + $Tot_lcp;
				$count = 1;
				$Tot_lcp = 0;
			}
			$rep->fontSize += 0.7;
			$rep->Font('bold');
			$rep->TextCol(0, 1, $myRow121['Category']);
			$rep->fontSize -= 0.7;
			$rep->Font();
			$rep->Line($rep->row - 1);
			$Category = $myRow121['Category'];
		}
		
		$rep->NewLine();
		$rep->TextCol(0, 1, $count);
		$rep->DateCol(1, 2, $myRow121['Date']);		
		$rep->TextCol(2, 3, $myRow121['Invoice']);
		$rep->TextCol(3, 4, $myRow121['Name']);
		$rep->TextCol(4, 5, $myRow121['Model']);		
		$rep->TextCol(5, 6, $myRow121['Chassis']);
		$rep->TextCol(6, 7, $myRow121['Serial']);
		$rep->TextCol(7,8, $myRow121['color']);		
		$rep->AmountCol(8,9, $myRow121['LCP'], $dec2);
		$rep->TextCol(9,10, $myRow121['Term']);
		$rep->TextCol(10, 11, $myRow121['Salestype']);

		$count = $count + 1;
		$Tot_lcp += $myRow121['LCP'];

		$rep->NewLine(0, 1);		
	}
	if ($Category != '')
		{
			$rep->NewLine(1.5);
			$rep->Line($rep->row + 8);
			$rep->fontSize += 0.7;
			$rep->Font('bold');
			$rep->TextCol(7, 8, _('Category Total'));
			$rep->AmountCol(8, 9, $Tot_lcp, $dec);
			$rep->fontSize -= 0.7;
			$rep->Font();

			$Tot_all = $Tot_all + $Tot_lcp;

			if($cat_id == ALL_TEXT)
			{
				$rep->NewLine(2);
				$rep->fontSize += 2.5;
				$rep->Font('bold');
				$rep->TextCol(7, 8, _('Grand Total'));
				$rep->AmountCol(8, 9, $Tot_all, $dec);
				$rep->fontSize -= 2.5;
				$rep->Font();
			}		

			$rep->Line($rep->row - 12);
			$rep->Line($rep->row - 12);
			$rep->Line($rep->row - 13);
			
			$count = 1;
			$Tot_lcp = 0;
		}
	
	// $rep->NewLine();
	// $rep->Line($rep->row - 2);

	// $rep->NewLine();
	// $rep->Font('bold');
	// $rep->fontSize -= 1;	
	// $rep->TextCol(8, 11, _('TOTAL (OFFICE SALES) :'));
	// $rep->AmountCol(11, 12, $Tot_qty);
	// $rep->AmountCol(12, 13, $Tot_lcp, $dec);
	// $rep->AmountCol(13, 14, $Tot_ucost, $dec);
	// $rep->Line($rep->row - 2);
	// $rep->SetFooterType('compFooter');
	// $rep->fontSize -= 2;

    $rep->End();
}
