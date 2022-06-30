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
$page_security = 'SA_PO_REP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Modified by:	Chitto Cancio
// date:	2021-02-26
// Title:	Purchase Order Summary Report
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

function getTransactions($category, $fromsupp, $item, $from, $to, $subcat)
{
    //echo $subcat;
    //exit;

	$from = date2sql($from);
	$to = date2sql($to);
	
	$sql = "SELECT
		poheader.order_no as po_number,
		poheader.supplier_id,
		supplier.supp_name,
		poline.item_code,
		quantity_ordered,
		std_cost_unit,
		act_price,
		unit_price,
		ord_date,
		smaster.importer,
		classification.name as classification_name,
		subcat.name as subcat_name
	FROM
		".TB_PREF."purch_order_details poline 
		LEFT JOIN
		".TB_PREF."stock_master smaster 
		ON smaster.stock_id = poline.item_code 
		LEFT JOIN
		".TB_PREF."item_importer classification 
		ON smaster.importer = classification.id 
		LEFT JOIN
		".TB_PREF."item_distributor subcat 
		ON smaster.distributor = subcat.id,
		".TB_PREF."purch_orders poheader,
		".TB_PREF."suppliers supplier 
	WHERE
		poheader.supplier_id = supplier.supplier_id 
		AND poheader.ord_date >= '$from' 
		AND poheader.ord_date <= '$to' 
		AND poheader.order_no = poline.order_no";

	if ($fromsupp != ALL_TEXT)
		$sql .= " AND poheader.supplier_id =".db_escape($fromsupp);
	if ($category != ALL_TEXT)
	    $sql .= " AND poheader.category_id =".db_escape($category);
	if ($subcat != -1)  
	    $sql .= " AND subcat.id =".db_escape($subcat);
	if ($item != ALL_TEXT)  
	    $sql .= " AND poline.item_code =".db_escape($item);
	if ($status != ALL_TEXT)  
	    $sql .= " AND poline.item_code =".db_escape($status);
	        
	$sql .= " ORDER BY poheader.supplier_id, poheader.order_no";

    return db_query($sql, "No transactions were returned");
}

function print_PO_Report()
{
    global $path_to_root;

    $from 		= $_POST['PARAM_0'];
	$to 		= $_POST['PARAM_1'];
	$category 	= $_POST['PARAM_2'];
	$fromsupp 	= $_POST['PARAM_3'];
	$subcat 	= $_POST['PARAM_4'];
	$item 		= $_POST['PARAM_5'];
	$status 	= $_POST['PARAM_6'];
	$comments	= $_POST['PARAM_7'];
	$orientation= $_POST['PARAM_8'];
	$destination= $_POST['PARAM_9'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'P');
	
    $dec = user_price_dec();

	if ($category == ALL_NUMERIC)
		$category = 0;
	if ($category == 0)
		$cat = _('All');
	else
		$cat = get_category_name($category);

	if ($fromsupp == '')
		$froms = _('All');
	else
		$froms = get_supplier_name($fromsupp);

	if ($item == '')
		$itm = _('All');
	else
		$itm = $item;

	$params = array(0 => $comments,
		1 => array('text' => _('Period'),'from' => $from, 'to' => $to),
		2 => array('text' => _('Supplier'), 'from' => $froms, 'to' => ''),
		3 => array('text' => _('Category'), 'from' => $cat, 'to' => ''));

	$cols = array(0, 50, 75, 150, 300, 390, 460, 490, 540);

	$headers = array(
		_('P.O date'), 
		_('P.O no.'),
		_('Supplier'), 
		_('Model'),
		// _('Item description'),  
		_('Sub-category'), 
		_('Classification'), 
		_('Qty'), 
		_('Amount'));

	$aligns = array('left', 'left',	'center', 'center', 'right', 'right', 'right', 'right');

    $rep = new FrontReport(_('Purchase Order Summary Report'), "POSummary", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);

	$rep->fontSize -= 1;
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

	$Tot_Val=0;
	$Supplier = '';
	$SuppTot_Val=0;
	$res = getTransactions($category, $fromsupp, $item, $from, $to, $subcat);

	While ($GRNs = db_fetch($res))
	{
		$dec2 = get_qty_dec($GRNs['item_code']);
		if ($Supplier != $GRNs['supplier_id'])
		{
			if ($Supplier != '')
			{
				$rep->NewLine(2);
				$rep->TextCol(0, 7, _('Total'));
				$rep->AmountCol(7, 8, $SuppTot_Val, $dec);
				$rep->Line($rep->row - 2);
				$rep->NewLine(3);
				$SuppTot_Val = 0;
			}
			$rep->TextCol(0, 5, $GRNs['supp_name']);
			$Supplier = $GRNs['supplier_id'];
			$rep->NewLine();
		}
		$rep->NewLine();
		$rep->TextCol(0, 1, sql2date($GRNs['ord_date']));
		$rep->TextCol(1, 2, $GRNs['po_number']);
		$rep->TextCol(2, 3, $GRNs['supp_name']);
		$rep->TextCol(3, 4, $GRNs['item_code']);
		//$rep->TextCol(3, 4, $GRNs['description']);
		$rep->TextCol(4, 5, $GRNs['subcat_name']);
		$Value = $GRNs['quantity_ordered'] * $GRNs['unit_price'];
		//$rep->AmountCol(4, 5, $GRNs['act_price']);
		$rep->TextCol(5, 6, $GRNs['classification_name']);
		$rep->AmountCol(6, 7, $GRNs['quantity_ordered'], $dec);
		$rep->AmountCol(7, 8, $Value, $dec);
		$Tot_Val += $Value;
		$SuppTot_Val += $Value;

		$rep->NewLine(0, 1);
	}
	if ($Supplier != '')
	{
		$rep->NewLine();
		$rep->NewLine();
		$rep->TextCol(0, 7, _('Total'));
		$rep->AmountCol(7, 8, $SuppTot_Val, $dec);
		$rep->Line($rep->row - 2);
		$rep->NewLine(3);
		$SuppTot_Val = 0;
	}
	$rep->NewLine(2);
	$rep->TextCol(0, 7, _('Grand Total'));
	$rep->AmountCol(7, 8, $Tot_Val, $dec);
	$rep->Line($rep->row - 2);
	$rep->NewLine();
    $rep->End();
}

