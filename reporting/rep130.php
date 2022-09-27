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
// Title:	Item SRP List
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

function fetch_items($category=0, $supplier)
{
	global $def_coy;
	//$def_coy = 0;
	set_global_connection($def_coy);

	$sql = "SELECT item.stock_id, item.description AS name,
			item.material_cost AS Standardcost,
			item.category_id, item.units, item.manufacturer,
			category.description,
			srp.standard_cost AS SRP,
			srp.supplier_id
		FROM ".TB_PREF."stock_master item,
			".TB_PREF."stock_category category,
			".TB_PREF."item_srp srp
		WHERE item.category_id = category.category_id";
	if ($category != 0)
		$sql .= " AND category.category_id = ".db_escape($category);

	if ($supplier != '')
		$sql .= " AND item.manufacturer = ".db_escape($supplier);

	$sql .= " AND item.mb_flag<> 'F' GROUP BY item.stock_id ORDER BY item.category_id,
			item.stock_id";

    return db_query($sql,"No transactions were returned");
}

function get_srp_sales_type_name($id)
{
	$sql = "SELECT srp_type FROM ".TB_PREF."item_srp_area_types WHERE id=".db_escape($id);
	
	$result = db_query($sql, "could not get sales type");
	
	$row = db_fetch_row($result);
	return $row[0];
}

function fetch_array_header_srp($category=0, $supplier)
{
	global $def_coy;
	//$def_coy = 0;
	set_global_connection($def_coy);

	$sql = "SELECT B.srp_type FROM  ".TB_PREF."policy_builder A
				INNER  ".TB_PREF."JOIN item_srp_area_types B ON A.cstprctype_id = B.id
			WHERE module_type = 'SRPPLCY'";
	if ($category != 0)
		$sql .= " AND A.category_id = ".db_escape($category);

	if ($supplier != '')
		$sql .= " AND A.supplier_id = ".db_escape($supplier);

	$sql .= "GROUP BY A.cstprctype_id ORDER BY A.cstprctype_id ASC";

    return db_query($sql,"No transactions were returned");
}

function get_sales_type_id_details_srp($category, $supplier)
{
	global $def_coy;
	//$def_coy = 0;
	set_global_connection($def_coy);

    $sql = "SELECT A.cstprctype_id FROM  ".TB_PREF."policy_builder A
				INNER  ".TB_PREF."JOIN item_srp_area_types B ON A.cstprctype_id = B.id
			WHERE module_type = 'SRPPLCY'";
	if ($category != 0)
		$sql .= " AND A.category_id = ".db_escape($category);

	if ($supplier != '')
		$sql .= " AND A.supplier_id = ".db_escape($supplier);

	$sql .= "GROUP BY A.cstprctype_id ORDER BY A.cstprctype_id ASC";

    return db_query($sql,"No transactions were returned");
}

function get_srp_price_item($stock_id, $currency, $srptype_id, $factor = null, $date = null)
{
	global $def_coy;
	//$def_coy = 0;
	set_global_connection($def_coy);

	if ($date == null)
		$date = new_doc_date();

	if ($factor === null) {
		$myrow = get_srp_type($srptype_id);
		$factor = $myrow['factor'];
	}

	$add_pct = get_company_pref('add_pct');
	$base_id = get_base_sales_type();
	$home_curr = get_company_currency();
	$sql = "SELECT standard_cost, curr_abrev, srptype_id
		FROM " . TB_PREF . "item_srp
		WHERE stock_id = " . db_escape($stock_id) . "
			AND srptype_id = " . db_escape($srptype_id);

	$result = db_query($sql, "There was a problem retrieving the pricing information for the part $stock_id for customer");
	$num_rows = db_num_rows($result);
	$rate = round2(
		get_exchange_rate_from_home_currency($currency, $date),
		user_exrate_dec()
	);
	$round_to = get_company_pref('round_to');
	$prices = array();
	while ($myrow = db_fetch($result)) {
		$prices[$myrow['srptype_id']][$myrow['curr_abrev']] = $myrow['standard_cost'];
	}
	$price = false;
	if (isset($prices[$srptype_id][$currency])) {
		$price = $prices[$srptype_id][$currency];
	} elseif (isset($prices[$base_id][$currency])) {
		$price = $prices[$base_id][$currency] * $factor;
	} elseif (isset($prices[$srptype_id][$home_curr])) {
		$price = $prices[$srptype_id][$home_curr] / $rate;
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
    $supplier = $_POST['PARAM_1'];
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
	if ($supplier == ALL_NUMERIC)
		$supplier = 0;
	if ($salestype == ALL_NUMERIC)
		$salestype = 0;
	if ($category == 0)
		$cat = _('All');
	else
		$cat = get_category_name($category);
	if ($salestype == 0)
		$stype = _('All');
	else
		$stype = get_srp_sales_type_name($salestype);

	if ($supplier == 0)
		$suppl = _('All');
	else
		$suppl = get_supplier_name($supplier);

	$result1 = fetch_array_header_srp($category, $supplier);
	$columns = array('Category/Items', 'Description', 'UOM');
	//$i=1;
	while ($myrow=db_fetch($result1))
	{
		$columns[] = $myrow['srp_type'];
        //$i++;
	}
	//var_dump($columns);

	$column = $columns;

	$cols = array(0, 170, 450, 480, 580, 680, 780, 880, 980, 1080, 1180, 1280, 1380, 1480, 1580, 1680, 1780, 1880, 1980, 2080, 2180, 2280, 2380, 2480, 2580, 2680, 2780, 2880, 2980, 3080, 
				3180, 3280, 3380, 3480, 3580, 3680, 3780, 3880, 3980, 4080, 4180, 4280, 4380, 4480, 4580, 4680, 4780, 4880, 4980, 5080, 5180, 5280, 5380, 5480, 5580, 5680, 5780, 5880, 5980,
				6080, 6180, 6280, 6380, 6480);

	//$headers = array(_('Category/Items'), _(''), _('Description'), _(''), _('UOM'), _($stype));
	$headers = $column;

	$aligns = array('left', 'left', 'left',	'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left',
					'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left',
					'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left',
					'left', 'left', 'left', 'left', 'left', 'left', 'left');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
    				    2 => array('text' => _('Supplier'), 'from' => $suppl, 'to' => ''));

	if ($pictures)
		$user_comp = user_company();
	else
		$user_comp = "";

    $rep = new FrontReport(_('Item SRP List'), "ItemSRPList", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    //$rep->SetHeaderType('COLLECTION_Header');
    if ($destination) {
        $rep->SetHeaderType('PO_Header');
    }
    else {
        $rep->SetHeaderType('COLLECTION_Header');     
    }
    $rep->NewPage();

    $id_sales_type = array();
    $salestype_id = get_sales_type_id_details_srp($category, $supplier);
    $i = 0; $j = 1;  
    while ($myrow1 = db_fetch($salestype_id)) {

    	$id_sales_type[] = $myrow1['cstprctype_id'];
    	$sales_id[$j] = $id_sales_type[$i];

    	$i++;
    	$j++;
    }
    $count = count($sales_id);

	$result = fetch_items($category, $supplier);

	$catgor = '';
	$_POST['sales_type_id'] = $salestype;
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
			$rep->NewLine(2);
		}
		$rep->TextCol(0, 1,	$myrow['stock_id']);
		$rep->TextCol(1, 2, $myrow['name']);
		$rep->TextCol(2, 3, $myrow['units']);
		
		$x=3; $y=4; $z=1;
		for($i = 0; $i < $count; $i++) {
			$price = get_srp_price_item($myrow['stock_id'], 'PHP', $sales_id[$z]);
			
			$rep->AmountCol($x, $y, $price, $dec);
			
			$x++;
			$y++;
			$z++;
		}	
		$rep->NewLine();
	}
	$rep->Line($rep->row  - 4);
	$rep->NewLine();
    $rep->End();
}

