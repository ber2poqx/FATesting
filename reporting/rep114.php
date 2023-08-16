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
// date_:	2021-05-11
// Title:	Sales Summary Report
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

function getTransactions($from, $to, $cat_id, $brand_code, $cust_id, $sales_type, $item_model)
{
	$from = date2sql($from);
	$to = date2sql($to);

	// $sql = "SELECT
	// 	IB.name as Brand
	// 	,SC.description as Category
	// 	,IR.name as Subcategory
	// 	,SO.from_stk_loc as Branch
	// 	,SO.ord_date as Date
	// 	,SO.doc_ref_no as Invoice
	// 	,DM.name as Name
	// 	,SOD.stk_code as Model
	// 	,SOD.lot_no as Serial
	// 	,SOD.chassis_no as Chassis
	// 	,CASE 
	// 		WHEN SO.payment_terms = 0 THEN 'Cash'    	
	// 		ELSE 'Installment' END AS Type
	// 	,SO.payment_terms as Term
	// 	,SOD.quantity as Qty
	// 	,SO.lcp_amount as LCP
	// 	,SOD.unit_price as UnitCost
	// 	,CASE
	// 		WHEN SA.first_name IS NULL THEN 'Office Sales'
	// 	    ELSE CONCAT(SA.first_name,' ',SA.last_name,' ',SA.suffix_name) END as SalesAgent
	// 	FROM ".TB_PREF."sales_orders SO 
	// 		INNER JOIN ".TB_PREF."sales_order_details SOD on SO.order_no = SOD.order_no
	// 	    LEFT JOIN ".TB_PREF."stock_master SM on SOD.stk_code = SM.stock_id
	// 	    LEFT JOIN ".TB_PREF."debtors_master DM on SO.debtor_no = DM.debtor_no
	// 	    LEFT JOIN ".TB_PREF."item_importer IR on SM.importer = IR.id
	// 	    LEFT JOIN ".TB_PREF."stock_category SC on SO.category_id = SC.category_id
	// 	    LEFT JOIN ".TB_PREF."item_brand IB on SM.brand = IB.id
	// 	    LEFT JOIN ".TB_PREF."sales_agent SA on SO.salesman_id = SA.id
	// 	WHERE 
	// 		SO.order_no = SOD.order_no
	// 		AND SO.ord_date <= '$to'
	// 		AND	SO.ord_date >='$from'";
	$sql = "
		SELECT ib6.name as Brand
			,dt1.type
		    ,sc10.description as Category
		    ,ii7.name as Subcategory
		    ,dt1.tran_date as `Date`
		    ,dt1.reference as `Invoice`
		    ,dm3.name as Name
		    ,dtd4.stock_id as Model
		    ,dtd4.lot_no as `Serial`
		    ,dtd4.chassis_no as `Chassis`
		    ,dtd4.unit_price as `Unit_price`
			,dl2.financing_gross as 'grossAmnt'
		    ,dl2.discount_downpayment as `discountdp`
		    ,CASE
		    	WHEN dl2.months_term = 0 THEN 'CASH'
		        WHEN dl2.months_term > 3 THEN 'INSTALLMENT'
				ELSE 'REGULAR' END AS `Type`
		    ,dl2.months_term as Term
		    ,dtd4.quantity as Qty
		    ,dl2.lcp_amount as LCP_perinvoice
			,dtd4.lcp_price as LCP
		    ,dtd4.standard_cost as `UnitCost`
		    ,CASE
		    	WHEN so8.salesman_id IS NULL OR so8.salesman_id = 0 THEN 'Office Sales'
		        WHEN so8.salesman_id = 0 THEN 'Office Sales'
		        ELSE sn9.salesman_name END AS `SalesAgent`	
			,dtd4.discount1
			,dtd4.discount2
			,dl2.ar_amount
			,sc10.category_id
			,dl2.downpayment_amount AS dp_amount
		FROM ".TB_PREF."debtor_trans_details dtd4
		    LEFT JOIN ".TB_PREF."debtor_trans dt1 on dt1.trans_no = dtd4.debtor_trans_no and dt1.type = dtd4.debtor_trans_type
			LEFT JOIN ".TB_PREF."debtor_loans dl2 on dtd4.debtor_trans_no = dl2.trans_no
		    LEFT JOIN ".TB_PREF."debtors_master dm3 on dt1.debtor_no = dm3.debtor_no
		    LEFT JOIN ".TB_PREF."stock_master sm5 on sm5.stock_id = dtd4.stock_id
		    LEFT JOIN ".TB_PREF."item_brand ib6 on sm5.brand = ib6.id
		    LEFT JOIN ".TB_PREF."item_importer ii7 on sm5.importer = ii7.id
		    LEFT JOIN ".TB_PREF."sales_orders so8 on dt1.order_ = so8.order_no AND dt1.type = ".ST_SALESINVOICE."
		    LEFT JOIN ".TB_PREF."salesman sn9 on so8.salesman_id = sn9.salesman_code
		    LEFT JOIN ".TB_PREF."stock_category sc10 on dl2.category_id = sc10.category_id
            LEFT JOIN ".TB_PREF."voided void ON dtd4.debtor_trans_type=void.type AND dtd4.debtor_trans_no = void.id
		WHERE dt1.type = ".ST_SALESINVOICE."
			AND dtd4.standard_cost <> 0
			AND dt1.tran_date <= '$to'
			AND	dt1.tran_date >='$from'";

	if ($cat_id != ALL_TEXT)
		$sql .= " AND so8.category_id =".db_escape($cat_id);
	if ($brand_code != ALL_TEXT)
		$sql .= " AND sm5.brand =".db_escape($brand_code);
	if ($cust_id != ALL_TEXT)
		$sql .= " AND dm3.debtor_no=".db_escape($cust_id);
	if ($item_model != ALL_TEXT)
		$sql .= " AND dtd4.stock_id =".db_escape($item_model);
	if ($sales_type != ALL_TEXT)
	{
		/*
		if ($sales_type == 'CASH')
		{
			$sql .= " AND dl2.installmentplcy_id = '0'";
		}
		else
		{
			$sql .= " AND dl2.installmentplcy_id != '0'";
		}*/

		if ($sales_type == 'CASH')
		{
			$sql .= " AND dl2.months_term = '0'";
		}
		else if($sales_type == 'INSTALLMENT')
		{
			$sql .= " AND dl2.months_term > '3'";
		}
		else
		{
			$sql .= " AND dl2.months_term BETWEEN '1' AND '3' ";
		}
	}
		
		$sql .= " AND void.void_status IS NULL ORDER BY dt1.tran_date, dt1.reference "; 

	// if($terms != 'ALL')
	// {
	// 	//$sql .= " AND dl2.months_term = ".db_escape($terms);
	// }

	return db_query($sql,"No transactions were returned");
}

function print_sales_summary_report()
{
	global $path_to_root;
	
	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$cat_id = $_POST['PARAM_2'];
	$brand_code = $_POST['PARAM_3'];
	$cust_id = $_POST['PARAM_4'];
	$sales_type = $_POST['PARAM_5'];
	$item_model = $_POST['PARAM_6'];
	//$months_term = $_POST['PARAM_7'];
	$comments = $_POST['PARAM_7'];
	$orientation = $_POST['PARAM_8'];
	$destination = $_POST['PARAM_9'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
				
	$orientation = ($orientation ? 'L' : 'P');
	
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

	if ($cust_id == ALL_TEXT)
		$cust = _('ALL');
	else
		$cust = get_customer_name($cust_id);

	if($sales_type == ALL_TEXT)
	{
		$type = _('ALL');
	}

	if($item_model == ALL_TEXT)
	{
		$model = _('ALL');
	}

	// if($months_term == 0)
	// {
	// 	$terms = _('ALL');
	// }
	// else 
	// 	$terms = $months_term;
	
	$params = array(0 => $comments,
		1 => array('text' => _('Period'),'from' => $from, 'to' => $to),
		2 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
		3 => array('text' => _('Brand'), 'from' => $brd, 'to' => ''),
		4 => array('text' => _('Customer'), 'from' => $cust, 'to' => ''),
		5 => array('text' => _('Sales type'), 'from' => $type, 'to' => ''),
		6 => array('text' => _('Item model'), 'from' => $model, 'to' => ''));
		// 7 => array('text' => _('Months Term'), 'from' => $terms, 'to' => ''));

	//              brand    cat      sub-cat   date       SIno.
	$cols = array(0,      30,     80,  	     105,      140, 

	//       name     model     serial     chassis    type       term      qty
		195,      255,       315,       375,       425,     445,     455,   

	//      LCP     Cost     gross		DP		lending_sale	 discount1   discount2	 Net_Sales		 Agent
		465,    495,     530,     560,	590,	       625,       650,         670,         705,      0);

	$headers = array(
		_('Brand'), 
		_('Category'),
		_('Sub-category'), 
		_('Date'),
		_('Invoice #'),
		_('Customer'),
		_('Model'),
		_('Serial #'),
		_('Chassis #'),
		_('Type'),
		_('Term'),
		_('Qty'),
		_('Unit Cost'),
		_('LCP'),
		_('Gross Amnt'),
		_('DP Amnt'),
		_('Lend Sales'),
		_('Dscount1'),
		_('Dscount2'),
		_('Net Sales'),
		_('Sales Agent'),
		);

	$aligns = array('left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 
	'left', 'right', 'right', 'right', 'right', 'right', 'right', 'right', 'right', 'right', 'right', 'right', 'right');

	$rep = new FrontReport(_('Sales Summary Report'), "SalesSummaryReport", "legal", 9, $orientation);

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
