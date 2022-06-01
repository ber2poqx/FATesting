<?php
$page_security = 'SA_INVTY_REP';

// ----------------------------------------------------------------
// $ Revision:	3.0 $
// Creator:		spyrax10
// date_:		2021-07-05
// Title:		Aging Inventory Report - By Year
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

	$cols = array(0, 45, 120, 205, 
		283, 375, 
		465, 500, 555, 605, 655, 695, 
		720, 0
	);

    $maxYear = (int)get_smo_max_year();
	$maxYear_1 = (int)get_smo_max_year() - 1;
	$maxYear_2 = (int)get_smo_max_year() - 2;
	$maxYear_3 = (int)get_smo_max_year() - 3;

	$headers = array(
		_('Brand'), 
        _('Product Code'), 
        _('Sub_Category'),
        _('Reference'), 
        _('Serial Number'),
        _('Chassis Number'), 
        _('QoH'),
        _($maxYear),
        _($maxYear_1),
        _($maxYear_2),
        _((string)$maxYear_3.'<<'),
        _('Date'),
        _('Age')
    );

	$aligns = array(
		'left', 'left', 'left', 'left', 'left', 'left', 'center', 
		'center', 'center', 'center', 'center',
		'left', 'right'	
	);

    $params = array( 	
		0 => $comments,
    	1 => array('text' => _('End Date'), 'from' => $date, 'to' => ''),
    	2 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
    	3 => array('text' => _('Location'), 'from' => $loc, 'to' => '')
	);

    $rep = new FrontReport(_('Aging Inventory Report (By Year)'), "InventoryValReport", 'LEGAL', 9, $orientation);
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
	$catt = $code = $loc = $samp = $reference = '';
	$m0 = $m1 = $m2 = $m3 = 0.0;
	$am0 = $am1 = $am2 = $am3 = 0.0;

	while ($trans = db_fetch($res)) {

		if ($trans['type'] == ST_INVADJUST && is_invty_open_bal('', $trans['reference'])) {
			$reference = $trans['reference'] . " (OB)";
		}
		else {
			$reference = $trans['reference'];
		}
		
		if ($catt != $trans['cat_description']) {	

			if ($catt != '') {
				$rep->NewLine();
				$rep->Font('bold');	
				
				$rep->TextCol(0, 1, _('Sub_Total'));
                $rep->AmountCol(6, 7, $qty);
				$rep->AmountCol2(7, 8, $m0);
				$rep->AmountCol2(8, 9, $m1);
				$rep->AmountCol2(9, 10, $m2);
				$rep->AmountCol2(10, 11, $m3);
				$rep->Line($rep->row  - 4);
				$rep->Font();
				$rep->NewLine(3);
				$qty = 0;
				$m0 = $m1 = $m2 = $m3 = 0.0;
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
			$rep->fontSize -= 1.5;
			$rep->TextCol(0, 1, $trans['Brand']);
        	$rep->TextCol(1, 2, $trans['Code']);
        	$rep->TextCol(2, 3, $trans['Sub_Cat']);
        	$rep->TextCol(3, 4, $reference);
        	$rep->TextCol(4, 5, $trans['Serial#']);
        	$rep->TextCol(5, 6, $trans['Chassis#']);
        	$rep->TextCol(6, 7, $trans['QoH']);
        	$rep->AmountCol2(7, 8, $trans['maxYear'] * $trans['QoH']);
        	$rep->AmountCol2(8, 9, $trans['maxYear_1'] * $trans['QoH']);
        	$rep->AmountCol2(9, 10, $trans['maxYear_2'] * $trans['QoH']);
        	$rep->AmountCol2(10, 11, $trans['maxYear_3'] * $trans['QoH']);
        	$rep->TextCol(11, 12, $trans['Date']);
        	$rep->TextCol(12, 13, $trans['Age']);
        
        	$dec2 = 0;
				  
			$rep->fontSize += 1.5;
        	$rep->NewLine();
		
			$m0 += $trans['maxYear'] * $trans['QoH'];
			$m1 += $trans['maxYear_1'] * $trans['QoH'];
			$m2 += $trans['maxYear_2'] * $trans['QoH'];
			$m3 += $trans['maxYear_3'] * $trans['QoH'];
			$qtyTot += $trans['QoH'];
			$qty += $trans['QoH'];	
			$am0 += $trans['maxYear'] * $trans['QoH'];
			$am1 += $trans['maxYear_1'] * $trans['QoH'];
			$am2 += $trans['maxYear_2'] * $trans['QoH'];
			$am3 += $trans['maxYear_3'] * $trans['QoH'];
		}		
	} //END while

	if ($catt != '') {
		$rep->NewLine();
		$rep->Font('bold');				
		$rep->TextCol(0, 1, _('Sub_Total'));
        $rep->AmountCol(6, 7, $qty);
		$rep->AmountCol2(7, 8, $m0);
		$rep->AmountCol2(8, 9, $m1);
		$rep->AmountCol2(9, 10, $m2);
		$rep->AmountCol2(10, 11, $m3);
		$rep->Line($rep->row  - 4);
		$rep->Font();
		$rep->NewLine(3);
	}

    $rep->Font('bold');
    $rep->fontSize += 2;
	$rep->TextCol(4, 5, _('GRAND TOTAL:'));
    
    $rep->TextCol(6, 7, $qtyTot);
	$rep->AmountCol2(7, 8, $am0);
	$rep->AmountCol2(8, 9, $am1);
	$rep->AmountCol2(9, 10, $am2);
	$rep->AmountCol2(10, 11, $am3);
    $rep->Line($rep->row  - 4);
	$rep->NewLine();
    $rep->End();
}

