<?php
$page_security = 'SA_INVTY_REP';

// ----------------------------------------------------------------
// $ Revision:	3.0 $
// Creator:		spyrax10
// date_:		2021-06-14
// Title:		Inventory On Hand Report (Summarized)
// ----------------------------------------------------------------
$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_category_db.inc");

include_once($path_to_root . "/inventory/includes/inventory_db.inc");
include_once($path_to_root . "/includes/db/manufacturing_db.inc");

//----------------------------------------------------------------------------------------------------
print_transaction();
//----------------------------------------------------------------------------------------------------

function print_transaction() {
    global $path_to_root, $SysPrefs;

	$date = $_POST['PARAM_0'];
    $category = $_POST['PARAM_1'];
	$supp = $_POST['PARAM_2'];
    $location = $_POST['PARAM_3'];
    $comments = $_POST['PARAM_4'];
	$destination = $_POST['PARAM_5'];

	if ($destination) {
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	}
	else {
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	}
		
    $dec = user_price_dec();

	$orientation = 'P'; // Lock print orientation

	if ($category == ALL_NUMERIC) {
		$category = 0;
	}
	if ($category == 0) {
		$cat = _('ALL');
	}
	else {
		$cat = get_category_name($category);
	}

	if ($location == ALL_TEXT) {
		$location = 'ALL';
	}
	if ($location == 'ALL') {
		$loc = _('ALL');
	}
	else {
		$loc = get_location_name($location);
	}

	if ($supp == '') {
		$supplier = _('ALL');
	}
	else {
		$supplier = get_supplier_name($supp);
	}

	$cols = array(0, 70, 250, 400, 450, 0);

	$headers = array(	
        _('Brand'), 
		_('Product Code'),
		_('Product Description'),
        _('QoH'),
        _('Total Cost'),
	);

	$aligns = array('left', 'left', 'left', 'center', 'right');

    $params = array( 
		0 => $comments,
    	1 => array('text' => _('End Date'), 'from' => $date, 'to' => ''),
    	2 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
		3 => array('text' => _('Supplier'), 'from' => $supplier, 'to' => ''),
    	4 => array('text' => _('Location'), 'from' => $loc, 'to' => '')
	);

    $rep = new FrontReport(_('Inventory On Hand Report (Summarized)'), "InventoryValReport", 'LETTER', 9, $orientation);
    if ($orientation == 'L') {
		recalculate_cols($cols);
	}

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
	$rep->SetHeaderType('PO_Header');
    $rep->NewPage();

	$res = get_inventory_movement($category, $supplier, $location, $date, false, true);
	$total = $grandtotal = $itm_tot = $unt_cst = 0.0;
    $qtyTot = $demand_qty = $qoh = $qty = $qoh = $qty_ = 0;
	$catt = $code = $loc = $samp = $cor = $poc = '';

	while ($trans = db_fetch($res)) {

		if ($catt != $trans['cat_description']) {		

			if ($catt != '') {

				$rep->NewLine();
				$rep->Font('bold');
				$rep->TextCol(0, 1, _('Sub_Total'));
				
				$rep->AmountCol(3, 4, $qty);
    			$rep->AmountCol(4, 5, $itm_tot, $dec);
				
				$rep->Line($rep->row  - 4);
				$rep->Font();
				$rep->NewLine(3);
				$qty = 0;
				$itm_tot = $unt_cst = $item_tot = 0.0;
			}

			$rep->Font('bold');	
			$rep->SetTextColor(0, 0, 255);	
			$rep->TextCol(0, 5, $trans['cat_description']);
			$catt = $trans['cat_description'];
			$rep->Font();
			$rep->SetTextColor(0, 0, 0);
			$rep->NewLine(2);		
		}

		if ($trans['QoH'] > 0) {

			$qoh = $trans['QoH'];

			$rep->fontSize -= 1;
			$rep->TextCol(0, 1, $trans['Brand']);
			$rep->TextCol(1, 2, $trans['Code']);
			$rep->TextCol(2, 3, $trans['prod_desc']);
			$rep->TextCol(3, 4, $qoh);

        	$dec2 = 0; 
			$rep->AmountCol2(4, 5, $trans['UnitCost'] * $qoh, $dec);
			$rep->fontSize += 1;
        	$rep->NewLine();
		
			$qty += $qoh;
			$itm_tot += $trans['UnitCost'] * $qoh;
		
			$qtyTot += $qoh;
			$grandtotal += $trans['UnitCost'] * $qoh;
		}
	} //END while

	if ($catt != '') {
		$rep->NewLine();
		$rep->Font('bold');	
		$rep->TextCol(0, 1, _('Sub_Total'));
		
		$rep->AmountCol(3, 4, $qty);
    	$rep->AmountCol(4, 5, $itm_tot, $dec);
		
		$rep->Line($rep->row  - 4);
		$rep->Font();
		$rep->NewLine(3);
	}

    $rep->Font('bold');
    $rep->fontSize += 2;
	$rep->TextCol(0, 1, _('GRAND TOTAL:'));

	$rep->TextCol(3, 4, $qtyTot);
    $rep->AmountCol(4, 5, $grandtotal, $dec);
	$rep->Line($rep->row  - 4);
	$rep->NewLine();
    $rep->End();
}

