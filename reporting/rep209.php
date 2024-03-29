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

$page_security = "SA_SUPP_PRINT";
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Purchase Orders
// ----------------------------------------------------------------
//Modified by soyrax10 27 Jul 2022
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/includes/db/crm_contacts_db.inc");
include_once($path_to_root . "/taxes/tax_calc.inc");

//----------------------------------------------------------------------------------------------------
print_po();
//----------------------------------------------------------------------------------------------------
function get_po($order_no, $coy = -1) {

	set_global_connection($coy);
   	$sql = "SELECT po.*, supplier.supp_name, supplier.supp_account_no,supplier.tax_included,
   		supplier.gst_no AS tax_id,
   		supplier.curr_code, supplier.payment_terms, loc.location_name,
   		supplier.address, supplier.contact, supplier.tax_group_id
		FROM ".TB_PREF."purch_orders po,"
			.TB_PREF."suppliers supplier,"
			.TB_PREF."locations loc
		WHERE po.supplier_id = supplier.supplier_id
			AND loc.loc_code = into_stock_location
			AND po.order_no = ".db_escape($order_no);

   	$result = db_query($sql, "The order cannot be retrieved");
	set_global_connection();
    return db_fetch($result);
}

function get_po_details($order_no, $coy = -1) {

	set_global_connection($coy);
	$sql = "SELECT poline.*, units
		FROM ".TB_PREF."purch_order_details poline
			LEFT JOIN ".TB_PREF."stock_master item ON poline.item_code = item.stock_id
		WHERE order_no = ".db_escape($order_no);
	$sql .= " ORDER BY po_detail_item";

	$result = db_query($sql, "Retreive order Line Items");
	set_global_connection();
	return $result;
}

function print_po() {

	global $path_to_root, $SysPrefs;
	
	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$currency = $_POST['PARAM_2'];
	$email = $_POST['PARAM_3'];
	$comments = $_POST['PARAM_4'];
	$orientation = $_POST['PARAM_5'];
	$coy = $_POST['PARAM_6'];

	for ($i = $from; $i <= $to; $i++) {
		$results = get_po_details($i, $coy);
		while ($myrow3 = db_fetch($results)) {
			$_SESSION["test"] = sql2date($myrow3['delivery_date']);
			//unset($_SESSION["test"]);	
		}
	}

	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	if (!$from || !$to) return;

	$orientation = ($orientation ? 'L' : 'P');
	$dec = user_price_dec();
//						 color,qty, unit, srp, total
	$cols = array(10, 90, 255, 355, 385, 415, 465, 515);

	// $headers in doctext.inc
	$aligns = array('left',	'left', 'left', 'center', 'center', 'right', 'right');

	$params = array('comments' => $comments);

	$cur = get_company_Pref('curr_default');

	if ($email == 0) {
		$rep = new FrontReport(_('PURCHASE ORDER'), "PurchaseOrderBulk", user_pagesize(), 9, $orientation);
	}

    if ($orientation == 'L') {
		recalculate_cols($cols);
	}
    	
	for ($i = $from; $i <= $to; $i++) {

		$myrow = get_po($i, $coy);
		if ($currency != ALL_TEXT && $myrow['curr_code'] != $currency) {
			continue;
		}
		$baccount = get_default_bank_account($myrow['curr_code'], $coy);
		$params['bankaccount'] = $baccount['id'];

		if ($email == 1) {
			$rep = new FrontReport("", "", user_pagesize(), 9, $orientation);
			$rep->title = _('PURCHASE ORDER');
			$rep->filename = "PurchaseOrder" . $i . ".pdf";
		}	
		$rep->currency = $cur;
		$rep->Font();
		$rep->Info($params, $cols, null, $aligns);

		$contacts = get_supplier_contacts($myrow['supplier_id'], 'order', $coy);
		$rep->SetCommonData($myrow, null, $myrow, $baccount, ST_PURCHORDER, $contacts);
		$rep->SetHeaderType('Header2');
		$rep->NewPage();

		$result2 = get_po_details($i, $coy);
		$SubTotal = 0;
		$items = $prices = array();
		
		while ($myrow2 = db_fetch($result2)) {

			$data = get_purchase_data($myrow['supplier_id'], $myrow2['item_code'], $coy);
			
			if ($data !== false) {
				if ($data['supplier_description'] != "") {
					$myrow2['description'] = $data['supplier_description'];
				}	
				if ($data['suppliers_uom'] != "") {
					$myrow2['units'] = $data['suppliers_uom'];
				}
				if ($data['conversion_factor'] != 1) {
					$myrow2['unit_price'] = round2($myrow2['unit_price'] * $data['conversion_factor'], user_price_dec());
					$myrow2['quantity_ordered'] = round2($myrow2['quantity_ordered'] / $data['conversion_factor'], user_qty_dec());
				}
			}

			$Net = round2(($myrow2["unit_price"] * $myrow2["quantity_ordered"]), user_price_dec());
			$prices[] = $Net;
			$items[] = $myrow2['item_code'];
			$SubTotal += $Net;
			$dec2 = 0;

			$DisplayPrice = price_decimal_format($myrow2["unit_price"], $dec2);
			$DisplayQty = number_format2($myrow2["quantity_ordered"]);
			$DisplayNet = number_format2($Net, $dec);
			
			if ($SysPrefs->show_po_item_codes()) {
				$rep->TextCol(0, 1,	$myrow2['item_code'], -2);
				$rep->TextCol(1, 2,	$myrow2['description'], -2); //*Empty slot
				$rep->TextCol(2, 3,	$myrow2['color_code'], -2);
				$rep->TextCol(3, 4,	$DisplayQty, -2);
				$rep->TextCol(4, 5,	$myrow2['units'], -2);
				$rep->TextCol(5, 6,	$DisplayPrice, -2);
				$rep->TextCol(6, 7,	$DisplayNet, -2);
				$rep->NewLine(1);
			} 
			else {
				$rep->TextCol(0, 1,	$myrow2['item_code'], -2);	
				//$rep->TextCol(2, 3,	sql2date($myrow2['delivery_date']), -2);		
				$rep->TextCol(1, 2,	$myrow2['color_code']);	//* Slot used to display data for COLOR			
				$rep->TextCol(2, 3,	$DisplayQty, -2);
				$rep->TextCol(3, 4,	$myrow2['units'], -2);
				$rep->TextCol(4, 5,	$DisplayPrice, -2);
				$rep->TextCol(5, 6,	$DisplayNet, -2);
				$rep->NewLine(1);
			}

			if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight)) {
				$rep->NewPage();
			}
		}
		/*if ($myrow['comments'] != "")
		{
			$rep->NewLine(2);
			$rep->TextColLines(1, 4, $myrow['comments'], -2);
		}*/
		$DisplaySubTot = number_format2($SubTotal,$dec);

		$rep->row = $rep->bottomMargin + (15 * $rep->lineHeight);
		$doctype = ST_PURCHORDER;

		$rep->TextCol(3, 6, _("Sub-total"), -2);
		$rep->TextCol(6, 7,	$DisplaySubTot, -2);
		$rep->NewLine();

		$tax_items = get_tax_for_items($items, $prices, 0,
		  $myrow['tax_group_id'], $myrow['tax_included'],  null, TCA_LINES
		);

		$first = true;

		foreach($tax_items as $tax_item) {
			if ($tax_item['Value'] == 0) {
				continue;
			}
			$DisplayTax = number_format2($tax_item['Value'], $dec);

			$tax_type_name = $tax_item['tax_type_name'];
		}

		$rep->NewLine();
		$DisplayTotal = number_format2($SubTotal, $dec);
		$rep->Font('bold');
		$rep->TextCol(3, 6, _("GRAND TOTAL"), - 2);
		$rep->TextCol(6, 7,	$DisplayTotal, -2);
		$words = price_in_words($SubTotal, ST_PURCHORDER);
		if ($words != "") {
			$rep->NewLine(1);
			$rep->TextCol(1, 7, $myrow['curr_code'] . ": " . $words, - 2);
		}
		$rep->Font();
		if ($email == 1) {
			$myrow['DebtorName'] = $myrow['supp_name'];

			if ($myrow['reference'] == "") {
				$myrow['reference'] = $myrow['order_no'];
			}	
			$rep->End($email);
		}
	}
	if ($email == 0) {
		$rep->End();
	}
}

