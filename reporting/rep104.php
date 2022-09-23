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
$page_security = 'SA_PRICEREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Dev.:	Robert Dusal
// date_:	2021-10-12
// Title:	Item LCP Price List
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui/ui_input.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_types_db.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");

//----------------------------------------------------------------------------------------------------
print_price_listing();

function fetch_items($category=0)
{
		$def_coy = 0;
		set_global_connection($def_coy);

		$sql = "SELECT item.stock_id, item.description AS name,
				item.material_cost AS Standardcost,
				item.category_id,item.units,
				category.description,
				lcp.price AS LCP_PRICE
			FROM ".TB_PREF."stock_master item,
				".TB_PREF."stock_category category,
				".TB_PREF."prices lcp
			WHERE item.category_id = category.category_id AND item.stock_id = lcp.stock_id";
		if ($category != 0)
			$sql .= " AND category.category_id = ".db_escape($category);

		$sql .= " AND item.mb_flag<> 'F' GROUP BY item.stock_id ORDER BY item.category_id,
				item.stock_id";

    return db_query($sql,"No transactions were returned");
}

function get_price_lcp($stock_id, $currency, $sales_type_id, $factor = null, $date = null)
{
	if ($date == null)
		$date = new_doc_date();

	if ($factor === null) {
		$myrow = get_sales_type($sales_type_id);
		$factor = $myrow['factor'];
	}

	$add_pct = get_company_pref('add_pct');
	$base_id = get_base_sales_type();
	$home_curr = get_company_currency();
	$sql = "SELECT price, curr_abrev, sales_type_id
		FROM " . TB_PREF . "prices
		WHERE stock_id = " . db_escape($stock_id) . "
			AND sales_type_id = " . db_escape($sales_type_id);

	$result = db_query($sql, "There was a problem retrieving the pricing information for the part $stock_id for customer");
	$num_rows = db_num_rows($result);
	$rate = round2(
		get_exchange_rate_from_home_currency($home_curr, $date),
		user_exrate_dec()
	);
	$round_to = get_company_pref('round_to');
	$prices = array();
	while ($myrow = db_fetch($result)) {
		$prices[$myrow['sales_type_id']][$myrow['curr_abrev']] = $myrow['price'];
	}
	$price = false;
	if (isset($prices[$sales_type_id][$currency])) {
		$price = $prices[$sales_type_id][$currency];
	} elseif (isset($prices[$base_id][$currency])) {
		$price = $prices[$base_id][$currency] * $factor;
	} elseif (isset($prices[$sales_type_id][$home_curr])) {
		$price = $prices[$sales_type_id][$home_curr] / $rate;
	} elseif (isset($prices[$base_id][$home_curr])) {
		$price = $prices[$base_id][$home_curr] * $factor / $rate;
	} elseif ($num_rows == 0 && $add_pct != -1) {
		$price = get_calculated_price($stock_id, $add_pct);
		if ($currency != $home_curr)
			$price /= $rate;
		if ($factor != 0)
			$price *= $factor;
	}
	if ($price === false)
		return 0;
	elseif ($round_to != 1)
		return round_to_nearest($price, $round_to);
	else
		return round2($price, user_price_dec());
}
//----------------------------------------------------------------------------------------------------

function print_price_listing()
{
    global $path_to_root, $SysPrefs;

    $category = $_POST['PARAM_0'];
    //$salestype = $_POST['PARAM_1'];
    $comments = $_POST['PARAM_1'];
	$orientation = $_POST['PARAM_2'];
	$destination = $_POST['PARAM_3'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'P');
    $dec = user_price_dec();

    /*
	$home_curr = get_company_pref('curr_default');
	if ($currency == ALL_TEXT)
		$currency = $home_curr;
	$curr = get_currency($currency);
	$curr_sel = $currency . " - " . $curr['currency'];
	*/

	if ($category == ALL_NUMERIC)
		$category = 0;
	if ($salestype == ALL_NUMERIC)
		$salestype = 0;
	if ($category == 0)
		$cat = _('All');
	else
		$cat = get_category_name($category);
	if ($salestype == 0)
		$stype = _('All');
	else
		$stype = get_sales_type_name($salestype);

	$cols = array(0, 170, 450, 480, 540, 600, 660, 730, 820, 960, 1050, 1110, 1180, 1255, 1380, 1505, 1575, 1645, 1745, 1855, 1945, 2025, 2145, 2300, 2390, 2460, 2550, 2690, 2770, 2820, 
				2920, 3020, 3105, 3205, 3375);

	$headers = array(_('Category/Items'), _('Description'), _('UOM'), _('LCP-1'), _('LCP-2'), _('LCP-3'), _('LCP-BOHOL'), _('LCP-BUKIDNON'), _('LCP-CAGAYAN DE ORO'), _('LCP-CAMIGUIN'),
					_('LCP-CEBU'), _('LCP-DAVAO'), _('LCP-KALIBO'), _('LCP-KAWASAKI 3S A'), _('LCP-KAWASAKI 3S B'), _('LCP-LEYTE'), _('LCP-LUZON'), _('LCP-MINDANAO'), _('LCP-MINDANAO 3'),
					_('LCP-MINDORO'), _('LCP-NEGROS'), _('LCP-NON YAMAHA'), _('LCP-NON YAMAHA-MINDA'), _('LCP-PALAWAN'), _('LCP-PANAY'), _('LCP-ROMBLON'), _('LCP-ROMBLON OUTLET'), 
					_('LCP-SIQUIJOR'), _('LCP-SP'),  _('LCP-SUZUKI 3S-A'), _('LCP-SUZUKI 3S-B'), _('LCP-VISAYAS'), _('LCP-YAMAHA 3S'), _('LCP-YAMAHA 3S-MINDA'));

	$aligns = array('left', 'left', 'left',	'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left',
					'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Category'), 'from' => $cat, 'to' => ''));

	if ($pictures)
		$user_comp = user_company();
	else
		$user_comp = "";

    $rep = new FrontReport(_('Item LCP Price List'), "ItemLCPPriceList", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    if ($destination) {
        $rep->SetHeaderType('PO_Header');
    }
    else {
        $rep->SetHeaderType('COLLECTION_Header');     
    }
    $rep->NewPage();

	$result = fetch_items($category);

	$catgor = '';
	$_POST['sales_type_id'] = $salestype;
	while ($myrow=db_fetch($result))
	{
		if ($catgor != $myrow['description'])
		{
			$rep->Line($rep->row  - $rep->lineHeight);
			$rep->NewLine(2);
			$rep->fontSize += 2;
			$rep->Font('bold');
            $rep->SetTextColor(0, 0, 255);
			$rep->TextCol(0, 3, $myrow['category_id'] . " - " . $myrow['description']);
			$catgor = $myrow['description'];
			$rep->Font();
            $rep->SetTextColor(0, 0, 0);
			$rep->fontSize -= 2;
			$rep->NewLine(2);
		}
		$rep->TextCol(0, 1,	$myrow['stock_id']);
		$rep->TextCol(1, 2, $myrow['name']);
		$rep->TextCol(2, 3, $myrow['units']);
		$price = get_price_lcp($myrow['stock_id'], 'PHP', '34');
		$rep->AmountCol(3, 4, $price, $dec);

		$price1 = get_price_lcp($myrow['stock_id'], 'PHP', '33');
		$rep->AmountCol(4, 5, $price1, $dec);

		$price2 = get_price_lcp($myrow['stock_id'], 'PHP', '35');
		$rep->AmountCol(5, 6, $price2, $dec);

		$price3 = get_price_lcp($myrow['stock_id'], 'PHP', '2');
		$rep->AmountCol(6, 7, $price3, $dec);

		$price4 = get_price_lcp($myrow['stock_id'], 'PHP', '16');
		$rep->AmountCol(7, 8, $price4, $dec);

		$price5 = get_price_lcp($myrow['stock_id'], 'PHP', '17');
		$rep->AmountCol(8, 9, $price5, $dec);

		$price6 = get_price_lcp($myrow['stock_id'], 'PHP', '14');
		$rep->AmountCol(9, 10, $price6, $dec);

		$price7 = get_price_lcp($myrow['stock_id'], 'PHP', '13');
		$rep->AmountCol(10, 11, $price7, $dec);

		$price8 = get_price_lcp($myrow['stock_id'], 'PHP', '5');
		$rep->AmountCol(11, 12, $price8, $dec);

		$price9 = get_price_lcp($myrow['stock_id'], 'PHP', '9');
		$rep->AmountCol(12, 13, $price9, $dec);

		$price10 = get_price_lcp($myrow['stock_id'], 'PHP', '29');
		$rep->AmountCol(13, 14, $price10, $dec);

		$price11 = get_price_lcp($myrow['stock_id'], 'PHP', '30');
		$rep->AmountCol(14, 15, $price11, $dec);

		$price12 = get_price_lcp($myrow['stock_id'], 'PHP', '12');
		$rep->AmountCol(15, 16, $price12, $dec);

		$price13 = get_price_lcp($myrow['stock_id'], 'PHP', '18');
		$rep->AmountCol(16, 17, $price13, $dec);

		$price14 = get_price_lcp($myrow['stock_id'], 'PHP', '28');
		$rep->AmountCol(17, 18, $price14, $dec);

		$price15 = get_price_lcp($myrow['stock_id'], 'PHP', '3');
		$rep->AmountCol(18, 19, $price15, $dec);

		$price16 = get_price_lcp($myrow['stock_id'], 'PHP', '7');
		$rep->AmountCol(19, 20, $price16, $dec);

		$price17 = get_price_lcp($myrow['stock_id'], 'PHP', '11');
		$rep->AmountCol(20, 21, $price17, $dec);

		$price18 = get_price_lcp($myrow['stock_id'], 'PHP', '21');
		$rep->AmountCol(21, 22, $price18, $dec);

		$price19 = get_price_lcp($myrow['stock_id'], 'PHP', '22');
		$rep->AmountCol(22, 23, $price19, $dec);

		$price20 = get_price_lcp($myrow['stock_id'], 'PHP', '6');
		$rep->AmountCol(23, 24, $price20, $dec);

		$price21 = get_price_lcp($myrow['stock_id'], 'PHP', '10');
		$rep->AmountCol(24, 25, $price21, $dec);

		$price22 = get_price_lcp($myrow['stock_id'], 'PHP', '8');
		$rep->AmountCol(25, 26, $price22, $dec);

		$price23 = get_price_lcp($myrow['stock_id'], 'PHP', '4');
		$rep->AmountCol(26, 27, $price23, $dec);

		$price24 = get_price_lcp($myrow['stock_id'], 'PHP', '15');
		$rep->AmountCol(27, 28, $price24, $dec);

		$price25 = get_price_lcp($myrow['stock_id'], 'PHP', '36');
		$rep->AmountCol(28, 29, $price25, $dec);

		$price26 = get_price_lcp($myrow['stock_id'], 'PHP', '31');
		$rep->AmountCol(29, 30, $price26, $dec);

		$price27 = get_price_lcp($myrow['stock_id'], 'PHP', '32');
		$rep->AmountCol(30, 31, $price27, $dec);

		$price28 = get_price_lcp($myrow['stock_id'], 'PHP', '24');
		$rep->AmountCol(31, 32, $price28, $dec);

		$price29 = get_price_lcp($myrow['stock_id'], 'PHP', '19');
		$rep->AmountCol(32, 33, $price29, $dec);

		$price30 = get_price_lcp($myrow['stock_id'], 'PHP', '20');
		$rep->AmountCol(33, 34, $price30, $dec);
		$rep->NewLine();
	}
	$rep->Line($rep->row  - 4);
	$rep->NewLine();
    $rep->End();
}

