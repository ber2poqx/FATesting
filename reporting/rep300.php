<?php
$page_security = 'SA_INVTY_REP';

// ----------------------------------------------------------------
// $ Revision:	3.0 $
// Creator:		spyrax10
// date_:		2021-06-14
// Title:		Inventory On Hand Report
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_category_db.inc");

include_once($path_to_root . "/inventory/includes/inventory_db.inc");
include_once($path_to_root . "/includes/db/manufacturing_db.inc");

//----------------------------------------------------------------------------------------------------

print_transaction();

function getTransactions($category, $supplier, $location, $date)
{
	$date = date2sql($date);

	$sql = "
		SELECT 
			SUM(SMO.qty) as QoH, 
			SMO.standard_cost AS UnitCost,
			SMO.category_id, SC.description AS cat_description,
			SMO.tran_date, SM.units,
			SMO.trans_id, SMO.trans_no, SMO.reference, SMO.type, 
			SM.stock_id AS 'Code', IB.name AS Brand, IC.description AS Color,
			SMO.tran_date AS 'Date',  
			SMO.lot_no AS 'Serial#', 
			SMO.chassis_no AS 'Chassis#', 
			SMO.loc_code,
			SM.description AS prod_desc
	
		FROM ".TB_PREF."stock_moves SMO
			INNER JOIN ".TB_PREF."stock_master SM ON SMO.stock_id = SM.stock_id
			INNER JOIN ".TB_PREF."stock_category SC ON SM.category_id = SC.category_id
			LEFT JOIN ".TB_PREF."item_brand IB ON SM.brand = IB.id
			LEFT JOIN ".TB_PREF."suppliers SUP ON IB.name = SUP.supp_ref 
			LEFT JOIN ".TB_PREF."item_distributor ID ON SM.distributor = ID.id
			LEFT JOIN item_codes IC ON SM.stock_id = IC.stock_id AND SMO.color_code = IC.item_code
				AND IC.category_id = 14
	
		WHERE SMO.tran_date <= '$date' AND SM.mb_flag <> 'D' AND SM.mb_flag <> 'F' AND SMO.item_type <> 'repo' ";
	
		if ($category != 0)
			$sql .= " AND SMO.category_id = ".db_escape($category);
			
		if ($supplier != 'ALL')
			$sql .= " AND SUP.supp_name =".db_escape($supplier);

		if ($location != 'all')
			$sql .= " AND SMO.loc_code = ".db_escape($location);


		$sql .= " GROUP BY SMO.transno_out, SMO.type_out, SMO.stock_id, SMO.loc_code, SMO.lot_no, SMO.chassis_no";
		$sql .= " ORDER BY SMO.category_id, SMO.stock_id, SMO.tran_date, SMO.lot_no";

    return db_query($sql, "No transactions were returned! ");
}

//----------------------------------------------------------------------------------------------------

function print_transaction()
{
    global $path_to_root, $SysPrefs;

	$date = $_POST['PARAM_0'];
    $category = $_POST['PARAM_1'];
	$supp = $_POST['PARAM_2'];
    $location = $_POST['PARAM_3'];
    $comments = $_POST['PARAM_4'];
	$destination = $_POST['PARAM_5'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
		
    $dec = user_price_dec();

	$orientation = 'L'; // Lock print orientation

	if ($category == ALL_NUMERIC)
		$category = 0;
	if ($category == 0)
		$cat = _('ALL');
	else
		$cat = get_category_name($category);

	if ($location == ALL_TEXT)
		$location = 'all';
	if ($location == 'all')
		$loc = _('ALL');
	else
		$loc = get_location_name($location);

	if ($supp == '')
		$supplier = _('ALL');
	else
		$supplier = get_supplier_name($supp);

	$cols = array(0, 55, 130, 190, 230, 280, 345, 
		400, 480, 540, 590, 605, 650, 700, 0
	);

	$headers = array(
		_('Product Code'),
		_('Product Description'),
		_('Color Description'), 
        _('Brand'), 
        _('Date'), 
        _('Reference #'), 
        _('Trans #'),
        _('Serial Number'),
        _('Chassis Number'), 
        _('QoH'),
        _('UoM'),
        _('Unit Cost'),
        _('Total Cost'),
		_('Location')
	);

	$aligns = array('left', 'left', 'left', 'center', 'center', 'left', 'center',
		'left', 'left', 'center', 'center', 'right', 'right', 'right'
	);

    $params = array( 	
		0 => $comments,
    	1 => array('text' => _('End Date'), 'from' => $date, 'to' => ''),
    	2 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
		3 => array('text' => _('Supplier'), 'from' => $supplier, 'to' => ''),
    	4 => array('text' => _('Location'), 'from' => $loc, 'to' => '')
	);

    $rep = new FrontReport(_('Inventory On Hand Report (Detailed)'), "InventoryValReport", 'LEGAL', 9, $orientation);
    
	if ($orientation == 'L') {
		recalculate_cols($cols);
	}

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
	$rep->SetHeaderType('PO_Header');
    $rep->NewPage();

	$res = getTransactions($category, $supplier, $location, $date);

	$total = $grandtotal = $itm_tot = $unt_cst = 0.0;
    $qtyTot = $demand_qty = $qoh = $qty = 0;
	$catt = $code = $loc = $samp = $cor = $reference = '';

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
				$rep->fontSize -= 1;
				$rep->AmountCol(9, 10, $qty);
				$rep->AmountCol(11, 12, $unt_cst, $dec);
    			$rep->AmountCol(12, 13, $itm_tot, $dec);
				$rep->fontSize += 1;
				$rep->Line($rep->row  - 4);
				$rep->Font();
				$rep->NewLine(3);
				$qty = 0;
				$itm_tot = $unt_cst = $item_tot = 0.0;
			}

			$rep->Font('bold');	
			$rep->SetTextColor(0, 0, 255);	
			$rep->TextCol(0, 1, $trans['cat_description']);
			$catt = $trans['cat_description'];
			$rep->Font();
			$rep->SetTextColor(0, 0, 0);
			$rep->NewLine(2);		
		}

		if ($trans['QoH'] > 0) {
			$rep->fontSize -= 2;
			$rep->TextCol(0, 1, substr($trans['Code'], 0, 15));
			$rep->TextCol(1, 2, $trans['prod_desc']);
			$cor = $trans['Color'];
			$rep->TextCol(2, 3, $cor);
			$code = $trans['Code'];
			$loc = $trans['loc_code'];  
        	$rep->TextCol(3, 4, $trans['Brand']);
        	$rep->TextCol(4, 5, sql2date($trans['Date']));
        	$rep->TextCol(5, 6, $reference);
        	$rep->TextCol(6, 7, $trans['trans_no']);
        	$rep->TextCol(7, 8, $trans['Serial#']);
        	$rep->TextCol(8, 9, $trans['Chassis#']);
			$rep->fontSize += .5;
        	$rep->TextCol(9, 10, $trans['QoH']);
			$rep->fontSize -= .5;
        	$rep->TextCol(10, 11, $trans['units']);      
        	
        	$dec2 = 0; 
			$rep->AmountCol2(11, 12, $trans['UnitCost'], $dec2);
			$rep->AmountCol2(12, 13, $trans['QoH'] * $trans['UnitCost'], $dec);
			$rep->TextCol(13, 14, $trans['loc_code']);
			$rep->fontSize += 2;
        	$rep->NewLine();
		
			$qty += $trans['QoH'];
			$unt_cst += $trans['UnitCost'];
			$itm_tot += $trans['QoH'] * $trans['UnitCost'];
		
			$qtyTot += $trans['QoH'];
			$total += $trans['UnitCost'];
			$grandtotal += $trans['QoH'] * $trans['UnitCost'];
		}
	} //END while

	if ($catt != '') {
		$rep->NewLine();
		$rep->Font('bold');	
		$rep->TextCol(0, 1, _('Sub_Total'));
		$rep->fontSize -= 1;
		$rep->AmountCol(9, 10, $qty);
		$rep->AmountCol(11, 12, $unt_cst, $dec);
    	$rep->AmountCol(12, 13, $itm_tot, $dec);
		$rep->fontSize += 1;
		$rep->Line($rep->row  - 4);
		$rep->Font();
		$rep->NewLine(3);
	}

    $rep->Font('bold');
    $rep->fontSize += 2;
	$rep->TextCol(6, 7, _('GRAND TOTAL:'));
	$rep->fontSize -= 2;
	$rep->TextCol(9, 10, $qtyTot);
    $rep->AmountCol(11, 12, $total, $dec);
    $rep->AmountCol(12, 13, $grandtotal, $dec);
	$rep->Line($rep->row  - 4);
	$rep->NewLine();
    $rep->End();
}

