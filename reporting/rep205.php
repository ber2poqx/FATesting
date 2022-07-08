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
$page_security = 'SA_SUPP_REP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Customer Details Listing
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/includes/db/crm_contacts_db.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

print_supplier_details_listing();

function get_supplier_details_for_report()
{
	$sql = "SELECT a.*,b.name as supplier_group_name
			FROM ".TB_PREF."suppliers a
			INNER JOIN supplier_group b ON a.supplier_group = b.id
			WHERE a.inactive = 0
	 		ORDER BY a.supp_name asc";

    return db_query($sql,"No transactions were returned");
}


function getTransactions($supplier_id, $date)
{
	$date = date2sql($date);

	$sql = "SELECT SUM((ov_amount+ov_discount)*rate) AS Turnover
		FROM ".TB_PREF."supp_trans
		WHERE supplier_id=".db_escape($supplier_id)."
		AND (type=".ST_SUPPINVOICE." OR type=".ST_SUPPCREDIT.")
		AND tran_date >='$date'";

    $result = db_query($sql,"No transactions were returned");

	$row = db_fetch_row($result);
	return $row[0];
}

//----------------------------------------------------------------------------------------------------

function print_supplier_details_listing()
{
    global $path_to_root;

    $from = $_POST['PARAM_0'];
    $more = $_POST['PARAM_1'];
    $less = $_POST['PARAM_2'];
    $comments = $_POST['PARAM_3'];
	$orientation = $_POST['PARAM_4'];
	$destination = $_POST['PARAM_5'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'P');
    $dec = 0;

	if ($more != '')
		$morestr = _('Greater than ') . number_format2($more, $dec);
	else
		$morestr = '';
	if ($less != '')
		$lessstr = _('Less than ') . number_format2($less, $dec);
	else
		$lessstr = '';

	$more = (double)$more;
	$less = (double)$less;

	$cols = array(0, 150, 210, 250, 290, 350, 400, 450, 490, 535, 585, 640, 700, 730);

	$headers = array(	_('Supplier Name:'), 
					 	_('Supplier Code'),	
						_('Supplier Group'),
						_('SAPcode'), 
						_('TIN No:'), 
						_('Website:'), 
						_('Payment Terms:'), 
						_('tax included:'),
    					_('Supplier Type:'), 
						_('Payable Account'), 
						_('Purchase Account:'), 
						_('Discount Account'),
						_('Mailing Address'));

	$aligns = array(	'left',
						'left',	
						'left',	
						'left',	
						'left', 
						'left',	
						'center', 
						'left', 
						'center', 
						'center', 
						'center', 
						'center', 
						'left');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Activity Since'), 	'from' => $from, 		'to' => ''),
    				    2 => array('text' => _('Activity'), 		'from' => $morestr, 	'to' => $lessstr . " " . get_company_pref("curr_default")));

    $rep = new FrontReport(_('Supplier Details Listing'), "SupplierDetailsListing", "legal", 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->fontSize -= 2;
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

	$result = get_supplier_details_for_report();

	while ($myrow=db_fetch($result))
	{
		$printsupplier = true;
		if ($more != '' || $less != '')
		{
			$turnover = getTransactions($myrow['supplier_id'], $from);
			if ($more != 0.0 && $turnover <= (double)$more)
				$printsupplier = false;
			if ($less != 0.0 && $turnover >= (double)$less)
				$printsupplier = false;
		}
		if ($printsupplier)
		{
			$newrow = 0;
			$rep->NewLine();
			// Here starts the new report lines
			$contacts = get_supplier_contacts($myrow['supplier_id']);
			$rep->TextCol(0, 1,	$myrow['supp_name']);
			$rep->TextCol(1, 2,	$myrow['supp_ref']);
			$rep->TextCol(2, 3,	$myrow['supplier_group_name']);
            $rep->TextCol(3, 4,	$myrow['SAPcode']);
			$rep->TextCol(4, 5,	$myrow['gst_no']);
			$rep->TextCol(5, 6,	$myrow['website']);
            $rep->TextCol(6, 7,	$myrow['payment_terms']);
			$rep->TextCol(7, 8,	$myrow['tax_included']);
			$rep->TextCol(8, 9,	$myrow['supplier_type']);
            $rep->TextCol(9, 10,	$myrow['payable_account']);
			$rep->TextCol(10, 11,	$myrow['purchase_account']);
			$rep->TextCol(11, 12,	$myrow['payment_discount_account']);
			$rep->TextCol(12, 13,	$myrow['address']);

			$rep->NewLine();
			$rep->Line($rep->row + 8);
			$rep->NewLine(0, 2);
		}
	}
    $rep->End();
}

