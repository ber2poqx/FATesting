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
$page_security = 'SA_TAX_REP'; //Modified by spyrax10 21 Jun 2022
// ----------------------------------------------------------------
// $ Revision:	7.0 $
// Creator:	Prog6
// date_:	2021-07-24
// Title:	Salesman Incentives Report
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

print_SMI_report();

function getTransactions($from, $to, $cat_id, $brand_code)
{
	$from = date2sql($from);
	$to = date2sql($to);
	
	// $sql = "
	// 	SELECT ib6.name as Brand
	// 		,dt1.type
	// 	    ,sc10.description as Category
	// 	    ,ii7.name as Subcategory
	// 	    ,dt1.tran_date as `Date`
	// 	    ,dt1.reference as `Invoice`
	// 	    ,dm3.name as Name
	// 	    ,dtd4.stock_id as Model
	// 	    ,dtd4.lot_no as `Serial`
	// 	    ,dtd4.chassis_no as `Chassis`
	// 	    ,CASE
	// 	    	WHEN dl2.installmentplcy_id = 0 THEN 'CASH'
	// 	        ELSE 'INSTALLMENT' END AS `Type`
	// 	    ,dl2.months_term as Term
	// 	    ,dtd4.quantity as Qty
	// 	    ,dl2.lcp_amount as LCP
	// 	    ,dtd4.standard_cost as `UnitCost`
	// 	    ,CASE
	// 	    	WHEN so8.salesman_id IS NULL OR so8.salesman_id = 0 THEN 'Office Sales'
	// 	        WHEN so8.salesman_id = 0 THEN 'Office Sales'
	// 	        ELSE sn9.salesman_name END AS `SalesAgent`		    
	// 	FROM ".TB_PREF."debtor_trans_details dtd4
	// 	    INNER JOIN ".TB_PREF."debtor_trans dt1 on dt1.trans_no = dtd4.debtor_trans_no and dt1.type = dtd4.debtor_trans_type
	// 		LEFT JOIN ".TB_PREF."debtor_loans dl2 on dtd4.debtor_trans_no = dl2.trans_no
	// 	    LEFT JOIN ".TB_PREF."debtors_master dm3 on dt1.debtor_no = dm3.debtor_no
	// 	    LEFT JOIN ".TB_PREF."stock_master sm5 on sm5.stock_id = dtd4.stock_id
	// 	    LEFT JOIN ".TB_PREF."item_brand ib6 on sm5.brand = ib6.id
	// 	    LEFT JOIN ".TB_PREF."item_importer ii7 on sm5.importer = ii7.id
	// 	    LEFT JOIN ".TB_PREF."sales_orders so8 on dt1.trans_no = so8.order_no
	// 	    LEFT JOIN ".TB_PREF."salesman sn9 on so8.salesman_id = sn9.salesman_code
	// 	    LEFT JOIN ".TB_PREF."stock_category sc10 on dl2.category_id = sc10.category_id
	// 	WHERE dt1.type = ".ST_SALESINVOICE."
	// 		AND dtd4.standard_cost <> 0
	// 		AND dt1.tran_date <= '$to'
	// 		AND	dt1.tran_date >='$from'";

	// if ($cat_id != ALL_TEXT)
	// 	$sql .= " AND so8.category_id =".db_escape($cat_id);
	// if ($brand_code != ALL_TEXT)
	// 	$sql .= " AND sm5.brand =".db_escape($brand_code);
	// if ($cust_id != ALL_TEXT)
	// 	$sql .= " AND dm3.debtor_no=".db_escape($cust_id);
	// if ($item_model != ALL_TEXT)
	// 	$sql .= " AND dtd4.stock_id =".db_escape($item_model);
	// if ($sales_type != ALL_TEXT)
	// {
	// 	if ($sales_type == 'CASH')
	// 	{
	// 		$sql .= " AND dl2.installmentplcy_id = '0'";
	// 	}
	// 	else
	// 	{
	// 		$sql .= " AND dl2.installmentplcy_id != '0'";
	// 	}
	// }
	// if($terms != 'ALL')
	// {
	// 	//$sql .= " AND dl2.months_term = ".db_escape($terms);
	// }

	$sql = "

		SELECT 
	 	    sc10.description as `Category`
	 	    ,ib6.name as `Brand`
	 	    ,dtd4.stock_id as `Model`
	 	    ,dt1.tran_date as `Date`
	 	    ,dtd4.lot_no as `Serial`
	 	    ,dtd4.chassis_no as `Chassis`
	 	    ,dt1.reference as `Invoice`
	 	    ,dtd4.quantity as `Qty`
	 	    ,dl2.lcp_amount as `Totalcost`
	 	    ,dm3.name as `customers_name`
	 	    -- , For Agent Amount
	 	    ,so8.salesman_id
	 	    ,sn9.salesman_name as `Agent_name`
	 	    ,CASE
	 	    	WHEN so8.salesman_id <> 1 THEN 'WITH AGENT SALES'
	 	        ELSE 'OFFICE SALES' END AS `SalesAgent`
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
	if ($brand_code != ALL_TEXT)
		$sql .= " AND sm5.brand =".db_escape($brand_code);

		$sql .= " ORDER BY so8.salesman_id";

	return db_query($sql,"No transactions were returned");
}

function print_SMI_report()
{
	global $path_to_root;
	
	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$cat_id = $_POST['PARAM_2'];
	$brand_code = $_POST['PARAM_3'];
	$comments = $_POST['PARAM_4'];
	$destination = $_POST['PARAM_5'];

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

	if ($brand_code < 0)
		$brand_code = 0;
	if ($brand_code == 0)
		$brd = _('ALL');
	else
		$brd = get_brand_descr($brand_code);

	
	$params = array(
		0 => $comments,
		1 => array('text' => _('Period'),'from' => $from, 'to' => $to),
		2 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
		3 => array('text' => _('Brand'), 'from' => $brd, 'to' => ''));

	//              1    2    3    4     5     6     7     8     9     10      11     12    
	$cols = array(0,  50,  100,  165,  225,  280,  340,  355,  390,  420,   450,    510,   0);

	$headers = array(
		_('Category'),
		_('Brand'), 
		_('Model'),
		_('Date'),
		_('Serial #'),
		_('Invoice #'),
		_('Qty'),
		_('Total Cost'),
		_('For Agent'),
		_('For AP'),
		_('Account Name'),
		_('Customer')
		);
    //                               model                             qty
	$aligns = array('left', 'left', 'left', 'center', 'left', 'left', 'center', 'right', 'right', 
	'right', 'center', 'center');

	$rep = new FrontReport(_('Salesman Incentives Report'), "SMIReport", "letter", 9, $orientation);

    if ($orientation == 'L')
    	recalculate_cols($cols);
	
	$rep->fontSize -= 2;
    $rep->Info($params, $cols, $headers, $aligns, 
		null, null, null, true, '', true);
    $rep->SetHeaderType('PO_Header');
	$rep->NewPage();


	$Tot_qty=0;
	$Tot_cost = 0;
	$Tot_forAgent = 0;
	$salestype = '';
	$res = getTransactions($from, $to, $cat_id, $brand_code);
	
	While ($GRNs = db_fetch($res))
	{		
		$dec2 = get_qty_dec($GRNs['Model']);

		if ($salestype != $GRNs['SalesAgent'])
		{
			if ($salestype != '')
			{
				$rep->NewLine(1.5);
				$rep->Line($rep->row + 8);
				$rep->fontSize += 0.7;
				$rep->Font('bold');
				$rep->TextCol(5, 6, _('Total'));
				$rep->TextCol(6, 7, $Tot_qty);
				$rep->AmountCol(7, 8, $Tot_cost, $dec);
				$rep->AmountCol(8, 9, $Tot_forAgent, $dec);
				$rep->fontSize -= 0.7;
				$rep->Font();
				//$rep->Line($rep->row - 2);
				$rep->NewLine(2);
				$Tot_qty = 0;
				$Tot_cost = 0;
				$Tot_forAgent = 0;
			}
			$rep->fontSize += 0.7;
			$rep->Font('bold');
			$rep->TextCol(0, 6, $GRNs['SalesAgent']);	
			$rep->fontSize -= 0.7;
			$rep->Font();
			$rep->Line($rep->row - 1);
			$salestype = $GRNs['SalesAgent'];
		}
		
		$rep->NewLine();
		$rep->TextCol(0, 1, $GRNs['Category']);
		$rep->TextCol(1, 2, $GRNs['Brand']);
		$rep->TextCol(2, 3, $GRNs['Model']);
		$rep->DateCol(3, 4, $GRNs['Date']);
		$rep->TextCol(4, 5, $GRNs['Serial']);
		$rep->TextCol(5, 6, $GRNs['Invoice']);
		$rep->TextCol(6, 7, $GRNs['Qty']);
		$rep->AmountCol(7,8, $GRNs['Totalcost'], $dec2);
		$rep->AmountCol(8,9, 0, $dec2);
		$rep->AmountCol(9,10, 0, $dec2);
		$rep->TextCol(10, 11, $GRNs['Agent_name']);
		$rep->TextCol(11, 12, $GRNs['customers_name']);

		$Tot_qty += $GRNs['Qty'];
		$Tot_cost += $GRNs['Totalcost'];
		$Tot_forAgent += 0;

		$rep->NewLine(0, 1);		
	}
	if ($salestype != '')
		{
			$rep->NewLine(1.5);
			$rep->Line($rep->row + 8);
			$rep->fontSize += 0.7;
			$rep->Font('bold');
			$rep->TextCol(5, 6, _('Total'));
			$rep->TextCol(6, 7, $Tot_qty);
			$rep->AmountCol(7, 8, $Tot_cost, $dec);
			$rep->AmountCol(8, 9, $Tot_forAgent, $dec);
			$rep->fontSize -= 0.7;
			$rep->Font();
			$rep->Line($rep->row - 14);
			$rep->Line($rep->row - 14);
			$rep->Line($rep->row - 15);
			
			$Tot_qty = 0;
			$Tot_cost = 0;
			$Tot_forAgent = 0;
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
