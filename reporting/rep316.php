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
// Title:	Item codes all item list
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

function get_allitem_codes($brand_code, $category=0)
{
	$sql ="SELECT A.stock_id, A.item_code, A.color, A.quantity, A.description AS Description, A.pnp_color, 
	   A.category_id, A.brand, B.description AS Category, C.units, D.name
	   FROM item_codes A
	   LEFT JOIN stock_category B ON B.category_id = A.category_id
	   LEFT JOIN stock_master C ON C.category_id = A.category_id
	   LEFT JOIN item_brand D ON C.brand = D.id
	   WHERE A.color <> ''
	   AND A.category_id <> ''";

	   if ($category != 0)
			$sql .= " AND A.category_id = ".db_escape($category);

		if ($brand_code != ALL_TEXT)
			$sql .= " AND A.brand =".db_escape($brand_code);

	    $sql .= "GROUP BY A.item_code, A.stock_id ORDER BY A.stock_id ASC";
    return db_query($sql, "No transactions were returned");
}

function print_PO_Report()
{
    global $path_to_root;
    $category = $_POST['PARAM_0'];
    $brand_code = $_POST['PARAM_1'];
	$orientation= $_POST['PARAM_2'];
	$destination= $_POST['PARAM_3'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
		
		
	$orientation = 'L';

	if ($category == ALL_NUMERIC)
		$category = 0;
	if ($category == 0)
		$cat = _('All');
	else
		$cat = get_category_name($category);

	if ($brand_code < 0)
		$brand_code = 0;
	if ($brand_code == 0)
		$brnd = _('All');
	else
		$brnd = get_brand_descr($brand_code);
	
    $dec = user_price_dec();

    
	$params = array(0 => $comments,
		 			1 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
    				2 => array('text' => _('Brand'), 'from' => $brnd, 'to' => '')
	);
	

	$cols = array(0, 108, 115, 200, 205, 220, 250, 435, 530, 535);

	$headers = array(
		_('Item Code'), 
		_(''),
		_('Color'),
		_(''),
		_('Qty'),
		_('Units'),
		_('Color Description'),
		_('PNP Color'),
		_(''),
		_('Category'));

	$aligns = array('left', 'left', 'left', 'left', 'left', 'left', 'left');

    $rep = new FrontReport(_('Item Codes All Item List'), "ItemCodesAllItemList", user_pagesize(), 10, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
	
	$rep->fontSize -= 1;
    $rep->Info($params, $cols, $headers, $aligns, 
		null, null, null, true, true, true);
    $rep->SetHeaderType('COLLECTION_Header');
	$rep->NewPage();

	$Total = 0.0;

	$res = get_allitem_codes($brand_code, $category);
	$Category = '';

	while ($DSOC = db_fetch($res))
	{	
		if ($Category != $DSOC['Category']) {

            $rep->Font('bold');      
            $rep->SetTextColor(0, 0, 255);
            $rep->TextCol(0, 5, $DSOC['Category']);
            $Category = $DSOC['Category'];
            $rep->Font();
            $rep->SetTextColor(0, 0, 0);
            $rep->NewLine(2);    
        }
		$rep->NewLine(1);
		$rep->TextCol(0, 1, $DSOC['item_code']);
		$rep->TextCol(1, 2, $DSOC['']);
		$rep->TextCol(2, 3, $DSOC['color']);
		$rep->TextCol(3, 4, $DSOC['']);
		$rep->AmountCol(4, 5, $DSOC['quantity']);
		$rep->TextCol(5, 6, $DSOC['units']);
		$rep->TextCol(6, 7, $DSOC['Description']);
		$rep->TextCol(7, 8, $DSOC['pnp_color']);
		$rep->TextCol(8, 9, $DSOC['']);
		$rep->TextCol(9, 10, $DSOC['Category']);
        $rep->NewLine(1);
	}
    $rep->End();
}
