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
// Creator:	RobertGwapo
// date_:	2021-05-11
// Title:	Sales Order Report
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

print_sales_order_report();

function getTransactions($from, $to, $Type, $status_order)
{
	$from = date2sql($from);
	$to = date2sql($to);

	$sql = "SELECT 
			sales_orders.ord_date
		    ,sales_orders.reference
		    ,debtors_master.name
		    ,sales_orders.status
		    ,sales_orders.approval_remarks
		    ,stock_category.description	
		    ,CASE WHEN sales_orders.months_term = 0 THEN 'CASH'
		     ELSE 'INSTALLMENT' END AS `Type`
		    ,CASE WHEN sales_orders.status = 'Approved' THEN 'APPROVED' 
		     WHEN sales_orders.status = 'Disapproved' THEN 'DISAPPROVED' 
		     ELSE '' END AS ORDER_STATUS  
		FROM ".TB_PREF." sales_orders
		    LEFT JOIN ".TB_PREF." debtors_master on debtors_master.debtor_no = sales_orders.debtor_no
		    LEFT JOIN ".TB_PREF." stock_category on stock_category.category_id = sales_orders.category_id
		WHERE sales_orders.ord_date>='$from' 
		AND sales_orders.ord_date<='$to'";

		if ($Type == 'CASH')
		$sql .= " AND sales_orders.months_term ='0'";

		if ($Type == 'INSTALLMENT')
		$sql .= " AND sales_orders.months_term !='0'";

		if ($status_order == 'APPROVED')
		$sql .= " AND sales_orders.status ='Approved'";

		if ($status_order == 'DISAPPROVED')
			$sql .= " AND sales_orders.status ='Disapproved'";

		$sql .= "ORDER BY sales_orders.reference DESC";

	return db_query($sql,"No transactions were returned");	
}


function print_sales_order_report()
{
	global $path_to_root;
	
	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$Type = $_POST['PARAM_2'];
	$status_order = $_POST['PARAM_3'];
	$orientation = $_POST['PARAM_4'];
	$destination = $_POST['PARAM_5'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
				
	$orientation = ($orientation ? 'L' : 'P');
	
    $dec = user_price_dec();

    if ($Type == ALL_TEXT)
	$Type = _('ALL');
	$mot = $Type;

	if ($status_order == ALL_TEXT)
	$status_order = _('ALL');
	$mot_status = $status_order;
	
	$params = array(0 => $comments,
					1 => array('text' => _('Period'),'from' => $from, 'to' => $to),
					2 => array('text' => _('Transaction Type'), 'from' => $mot, 'to' => ''),
					3 => array('text' => _('Status'), 'from' => $mot_status, 'to' => '')
					);

	$cols = array(0, 50, 138, 230, 270, 450, 0);

	$headers = array(
		_('Date'), 
		_('Reference number'),
		_('Name of Customer'), 
		_('Status'),
		_('Remarks'),
		_('Type'),
		_('Category'),
		);

	$aligns = array('left', 'left', 'left', 'left', 'left', 'left', 'right');

	$rep = new FrontReport(_('Sales Order Report'), "SalesOrderReport", user_pagesize(), 9, $orientation);

    if ($orientation == 'L')
    	recalculate_cols($cols);
	
	$rep->fontSize -= 2;
    $rep->Info($params, $cols, $headers, $aligns, 
		null, null, null, true, true, true);
    $rep->SetHeaderType('COLLECTION_Header');
	$rep->NewPage();

	$res = getTransactions($from, $to, $Type, $status_order);

	while ($TYPES = db_fetch($res))
	{
		
		$rep->NewLine();
		$rep->TextCol(0, 1, sql2date($TYPES['ord_date']));
		$rep->TextCol(1, 2, $TYPES['reference']);
		$rep->TextCol(2, 3, $TYPES['name']);
		$rep->TextCol(3, 4, $TYPES['status']);
		$rep->TextCol(4, 5, $TYPES['approval_remarks']);
		$rep->TextCol(5, 6, $TYPES['Type']);
		$rep->TextCol(6, 7, $TYPES['description']);

		$rep->NewLine(0, 1);
	}

	$rep->NewLine();
	$rep->Line($rep->row - 2);
	$rep->SetFooterType('compFooter');
	$rep->fontSize -= 2;
    $rep->End();
}
