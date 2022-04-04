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
$page_security = 'SA_SUPPLIERANALYTIC';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	RobertGwapo
// date:	2021-08-30
// Title:	Check Register Other Cash Item
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
	
	$sql ="SELECT A.bank_branch, B.name AS Name, A.trans_date, A.check_date, A.receipt_no, A.amount, C.memo_
		   FROM bank_trans A
		   LEFT JOIN debtors_master B ON B.debtor_no = A.person_id
		   LEFT JOIN comments C ON C.id = A.id AND C.type = A.type
		   WHERE  A.trans_date>='$from'
		   AND A.trans_date<='$to' 
		   AND A.pay_type = 'check'";
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

	$params = array(0 => $comments,
		1 => array('text' => _('Period'),'from' => $from, 'to' => $to)
	);

	$cols = array(0, 110, 210, 285, 350, 420, 460);

	$headers = array(
		_('Bank Code / Branch'), 
		_('Customer Name'),
		_('Check Received'),
		_('Check Date'),
		_('Check Number'),
		_('Amount'),
		_('Remarks'));

	$aligns = array('left', 'left', 'left', 'left', 'left', 'left', 'left');

    $rep = new FrontReport(_('Check Register & Other Cash Item'), "CheckRegisterOtherCashItem", user_pagesize(), 10, $orientation);
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

	$Total = 0.0;

	$res = getTransactions($from, $to);
	$catt = '';

	while ($DSOC = db_fetch($res))
	{
		
		$rep->NewLine();
		$rep->TextCol(0, 1, $DSOC['bank_branch']);
		$rep->TextCol(1, 2, $DSOC['Name']);
		$rep->TextCol(2, 3, sql2date($DSOC['trans_date']));
		$rep->TextCol(3, 4, sql2date($DSOC['check_date']));
		$rep->TextCol(4, 5, $DSOC['receipt_no']);
		$rep->AmountCol(5, 6, $DSOC['amount']);
		$rep->TextCol(6, 7, $DSOC['memo_']);
        $rep->NewLine(0.5);

		$Total +=  $DSOC['amount'];
	}
	$rep->Line($rep->row - 2);

	
	$rep->NewLine(2.5);
	$rep->Font('bold');
	$rep->Line($rep->row - 2);
	$rep->fontSize += 1;	
	$rep->TextCol(4, 5, _('TOTAL'));
	$rep->AmountCol(5, 6, $Total);
	//$rep->SetFooterType('compFooter');
    $rep->End();
}
