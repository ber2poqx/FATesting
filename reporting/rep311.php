<?php
$page_security = 'SA_INVTY_REP';

// ----------------------------------------------------------------
// $ Revision:	3.0 $
// Creator:		spyrax10
// date_:		2021-07-03
// Title:		Aging Inventory Report - Detailed
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
    $location = $_POST['PARAM_2'];
    $comments = $_POST['PARAM_3'];
	$destination = $_POST['PARAM_4'];

	if ($destination) {
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	}
	else {
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	}
	
    $dec = user_price_dec();

	$orientation = 'L'; // Lock print orientation

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

	$cols = array(
		0, 45, 105, 210, 280, 345,
		390, 480, 
		570, 600, 650, 710, 0
	);

	$aligns = array(
		'left', 'left', 'left', 'left', 'left', 'center', 
		'left', 'left',
		'center', 'center', 'center', 'center', 'right'
	);

	$headers = array(
		_('Brand'), 
        _('Product Code'), 
		_('Color Description'),
        _('Sub_Category'), 
        _('Reference'), 
        _('QoH'),   
        _('Serial Number'), 
        _('Chassis Number'),
        _('Date'),
        _('Invty_Age'),
        _('Avg Cost'),
        _('Location')
	);

    $params = array( 	
		0 => $comments,
    	1 => array('text' => _('End Date'), 'from' => $date, 'to' => ''),
    	2 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
    	3 => array('text' => _('Location'), 'from' => $loc, 'to' => '')
	);

    $rep = new FrontReport(_('Aging Inventory Report (Detailed)'), "InventoryValReport", 'LEGAL', 9, $orientation);
    
	if ($orientation == 'L') {
		recalculate_cols($cols);
	}

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
	$rep->SetHeaderType('PO_Header');
    $rep->NewPage();

	$res = get_inventory_movement($category, 'ALL', $location, $date);
	$total = $grandtotal = $itm_tot = $unt_cst = 0.0;
    $qtyTot = $demand_qty = $qoh = $qty = 0;
	$catt = $code = $loc = $samp = $cor = $reference = $trim_ref = $loc_code = $ob_ref = '';

	while ($trans = db_fetch($res)) {

		$loc_code = $trans['loc_code'];
		$reference = $trans['reference'];
		
		if ($trans['type'] == ST_INVADJUST && is_invty_open_bal('', $trans['reference'])) {
			$ob_ref = str_replace($loc_code . "-", "", $reference);
			$trim_ref = $ob_ref . " (OB)";
		}
		else {
			$trim_ref = $reference;
		}

		if ($catt != $trans['cat_description']) {		
			
			if ($catt != '') {
				$rep->NewLine();
				$rep->Font('bold');
				
				$rep->TextCol(0, 1, _('Sub_Total'));
				$rep->AmountCol(10, 11, $total, $dec);
				$rep->Line($rep->row  - 4);
				$rep->Font();
				$rep->NewLine(3);
				$total = 0.0;
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
			$color_desc = get_color_description($trans['color_code'], $trans['Code'], true);

			$rep->fontSize -= 1;
			$rep->TextCol(0, 1, $trans['Brand']); 
        	$rep->TextCol(1, 2, $trans['Code']);
			$rep->TextCol(2, 3, $destination ? $color_desc : substr($color_desc, 0, 25));
        	$rep->TextCol(3, 4, $trans['Sub_Cat']);
        	$rep->TextCol(4, 5, $trim_ref);
        	$rep->TextCol(5, 6, $trans['QoH']);
        
        	$rep->TextCol(6, 7, $trans['Serial#']);
        	$rep->TextCol(7, 8, $trans['Chassis#']);
			$rep->SetTextColor(0, 0, 255);
        	$rep->TextCol(8, 9, sql2date($trans['Date']));
			$rep->SetTextColor(0, 0, 0);
        	$rep->TextCol(9, 10, $trans['Age']);
        
        	$dec2 = 0;
			$rep->AmountCol2(10, 11, $trans['UnitCost'], $dec2);

			$rep->SetTextColor(0, 0, 255);
        	$rep->TextCol(11, 12, $loc_code);  
			$rep->SetTextColor(0, 0, 0);    
			$rep->fontSize += 1;
        	$rep->NewLine();

			$total += $trans['UnitCost'];
			$grandtotal += $trans['UnitCost'];
		}

	} //END while

	if ($catt != '') {
		$rep->NewLine();
		$rep->Font('bold');		
		$rep->TextCol(0, 1, _('Sub_Total'));
		$rep->AmountCol(10, 11, $total, $dec);
		$rep->Line($rep->row  - 4);
		$rep->Font();
		$rep->NewLine(3);
	}

    $rep->Font('bold');
    $rep->fontSize += 2;
	$rep->TextCol(4, 5, _('GRAND TOTAL:'));
    $rep->AmountCol(10, 11, $grandtotal, $dec);
    $rep->Line($rep->row  - 4);
	$rep->NewLine();
    $rep->End();
}

