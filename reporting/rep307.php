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
$page_security = 'SA_ITEMSVALREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Jujuk
// date_:	2011-05-24
// Title:	Stock Movements
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

inventory_movements();

function fetch_items($brand_code, $category=0, $type)
{
		$sql = "SELECT stock.stock_id, stock.description AS name,
				stock.category_id, stock.brand,
				units,
				cat.description,
				item.name AS BRAND,
				IFNULL(QTYMOVE.QTY, 0)

				FROM stock_master stock 
				LEFT JOIN stock_category cat ON stock.category_id=cat.category_id
				LEFT JOIN item_brand item ON stock.brand=item.id
			
				LEFT JOIN (
				SELECT move.stock_id,
				SUM(move.qty) AS QTY
				FROM stock_moves move
				LEFT JOIN stock_master BB ON move.stock_id = BB.stock_id
				GROUP BY move.stock_id
				) QTYMOVE ON stock.stock_id = QTYMOVE.stock_id

				WHERE mb_flag <> 'D' AND mb_flag <>'F'";
				if ($category != 0)
					$sql .= " AND cat.category_id = ".db_escape($category);

				if ($brand_code != ALL_TEXT)
					$sql .= " AND stock.brand =".db_escape($brand_code);

				if ($type != 0) 
					$sql .= " AND IFNULL(QTYMOVE.QTY, 0) <> '0'";

				$sql .= "GROUP BY stock.stock_id ORDER BY stock.category_id, stock_id";

    return db_query($sql,"No transactions were returned");
}

function trans_qty($stock_id, $location=null, $from_date, $to_date, $inward = true)
{
	if ($from_date == null)
		$from_date = Today();

	$from_date = date2sql($from_date);	

	if ($to_date == null)
		$to_date = Today();

	$to_date = date2sql($to_date);

	$sql = "SELECT ".($inward ? '' : '-')."SUM(qty) FROM ".TB_PREF."stock_moves
		WHERE stock_id=".db_escape($stock_id)."
		AND tran_date >= '$from_date' 
		AND tran_date <= '$to_date'";

	if ($location != '')
		$sql .= " AND loc_code = ".db_escape($location);

	if ($inward)
		$sql .= " AND qty > 0 ";
	else
		$sql .= " AND qty < 0 ";

	$result = db_query($sql, "QOH calculation failed");

	$myrow = db_fetch_row($result);	

	return $myrow[0];

}

//----------------------------------------------------------------------------------------------------

function inventory_movements()
{
    global $path_to_root;

    $from_date = $_POST['PARAM_0'];
    $to_date = $_POST['PARAM_1'];
    $category = $_POST['PARAM_2'];
    $brand_code = $_POST['PARAM_3'];
	$location = $_POST['PARAM_4'];
	$type = $_POST['PARAM_5'];
    $comments = $_POST['PARAM_6'];
	$orientation = $_POST['PARAM_7'];
	$destination = $_POST['PARAM_8'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'P');
	if ($category == ALL_NUMERIC)
		$category = 0;
	if ($category == 0)
		$cat = _('All');
	else
		$cat = get_category_name($category);

	if ($location == '')
		$loc = _('All');
	else
		$loc = get_location_name($location);

	if ($brand_code < 0)
		$brand_code = 0;
	if ($brand_code == 0)
		$brnd = _('All');
	else
		$brnd = get_brand_descr($brand_code);

	if ($type == 0) {
		$type_movement = 'All Item';
	} else {
		$type_movement = 'Item with Qty';
	}
	

	$cols = array(0, 117, 122, 350, 355, 390, 435, 475, 525);

	$headers = array(_('Category'), _(''), _('Description'), _(''), _('UOM'), _('Opening'), _('QTY In'), 
	_('QTY Out'), _('Balance'));

	$aligns = array('left', 'left',	'left', 'left',	'left', 'left', 'left', 'left','left');

    $params =   array( 	0 => $comments,
						1 => array('text' => _('Period'), 'from' => $from_date, 'to' => $to_date),
    				    2 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
    				    3 => array('text' => _('Brand'), 'from' => $brnd, 'to' => ''),
						4 => array('text' => _('Location'), 'from' => $loc, 'to' => ''),
						5 => array('text' => _('Type'), 'from' => $type_movement, 'to' => ''));

    $rep = new FrontReport(_('Inventory Movements'), "InventoryMovements", user_pagesize(), 9, $orientation);
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

	$result = fetch_items($brand_code, $category, $type);

	$catgor = '';
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
		$qoh_start= $inward = $outward = $qoh_end = 0; 
		
		$qoh_start += get_qoh_on_date($myrow['stock_id'], $location, add_days($from_date, -1));
		$qoh_end += get_qoh_on_date($myrow['stock_id'], $location, $to_date);
		
		$inward += trans_qty($myrow['stock_id'], $location, $from_date, $to_date);
		$outward += trans_qty($myrow['stock_id'], $location, $from_date, $to_date, false);

		$stock_qty_dec = get_qty_dec($myrow['stock_id']);
		$rep->AmountCol(5, 6, $qoh_start, $stock_qty_dec);
		$rep->AmountCol(6, 7, $inward, $stock_qty_dec);
		$rep->AmountCol(7, 8, $outward, $stock_qty_dec);
		$rep->AmountCol(8, 9, $qoh_end, $stock_qty_dec);
		$rep->NewLine(0, 1);
	}
	$rep->Line($rep->row  - 4);

	$rep->NewLine();
    $rep->End();
}

