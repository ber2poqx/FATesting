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
// Title:	Item CASH Price List
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
				cash.price AS CASH_PRICE
			FROM ".TB_PREF."stock_master item,
				".TB_PREF."stock_category category,
				".TB_PREF."cash_prices cash
			WHERE item.category_id = category.category_id AND item.stock_id = cash.stock_id";
		if ($category != 0)
			$sql .= " AND category.category_id = ".db_escape($category);
	
		$sql .= " AND item.mb_flag<> 'F' GROUP BY item.stock_id ORDER BY item.category_id,
				item.stock_id";

    return db_query($sql,"No transactions were returned");
}

function get_cash_sales_type_name($id)
{
	$sql = "SELECT scash_type FROM ".TB_PREF."sales_cash_type WHERE id=".db_escape($id);
	
	$result = db_query($sql, "could not get sales type");
	
	$row = db_fetch_row($result);
	return $row[0];
}
//----------------------------------------------------------------------------------------------------

function print_price_listing()
{
    global $path_to_root, $SysPrefs;

    $category = $_POST['PARAM_0'];
    $salestype = $_POST['PARAM_1'];
    $comments = $_POST['PARAM_2'];
	$orientation = $_POST['PARAM_3'];
	$destination = $_POST['PARAM_4'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'P');
    $dec = user_price_dec();

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
		$stype = get_cash_sales_type_name($salestype);

	$cols = array(0, 175, 180, 445, 450, 485);

	$headers = array(_('Category/Items'), _(''), _('Description'), _(''), _('UOM'), _($stype));

	$aligns = array('left', 'left',	'left', 'left',	'left', 'left');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
    				    //2 => array('text' => _('Supplier'), 'from' => $froms, 'to' => ''),
    				    2 => array('text' => _('Sales Type'), 'from' => $stype, 'to' => ''));

	if ($pictures)
		$user_comp = user_company();
	else
		$user_comp = "";

    $rep = new FrontReport(_('Item CASH Price List'), "ItemCASHPriceList", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->SetHeaderType('COLLECTION_Header');
    $rep->NewPage();

	$result = fetch_items($category);

	$catgor = '';
	$_POST['scash_type_id'] = $salestype;
	while ($myrow=db_fetch($result))
	{
		if ($catgor != $myrow['description'])
		{
			$rep->Line($rep->row  - $rep->lineHeight);
			$rep->NewLine(2);
			$rep->fontSize += 2;
			$rep->TextCol(0, 3, $myrow['category_id'] . " - " . $myrow['description']);
			$catgor = $myrow['description'];
			$rep->fontSize -= 2;
			$rep->NewLine();
		}
		$rep->NewLine(1.3);
		$rep->TextCol(0, 1,	$myrow['stock_id']);
		$rep->TextCol(1, 2, $myrow['']);
		$rep->TextCol(2, 3, $myrow['name']);
		$rep->TextCol(3, 4, $myrow['']);
		$rep->TextCol(4, 5, $myrow['units']);
		$price = get_cash_price($myrow['stock_id'], $currency, $salestype);
		$rep->AmountCol(5, 6, $price, $dec);
		$rep->NewLine(0, 1);
	}
	$rep->Line($rep->row  - 4);
	$rep->Line($rep->row  - 4);
	$rep->NewLine();
    $rep->End();
}

