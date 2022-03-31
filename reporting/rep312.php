<?php
$page_security = 'SA_INVTY_REP';

// ----------------------------------------------------------------
// $ Revision:	3.0 $
// Creator:		spyrax10
// date_:		2021-07-05
// Title:		Aging Inventory Report - By Year
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

function get_smo_max_year() {
    
    $sql = "SELECT MAX(YEAR(A.tran_date)) AS maxYear
        FROM ".TB_PREF."stock_moves A ";

    $res = db_query($sql, 'Invalid!');
    $row = db_fetch_row($res);
	return $row[0];
}

function getTransactions($category, $location, $date)
{
	$date = date2sql($date);

	$maxYear = (int)get_smo_max_year();
	$maxYear_1 = (int)get_smo_max_year() - 1;
	$maxYear_2 = (int)get_smo_max_year() - 2;
	$maxYear_3 = (int)get_smo_max_year() - 3;

	$sql = "SELECT DATEDIFF(DATE('$date'), SMO.tran_date) AS Age, ID.name AS Sub_Cat,
			
			CASE 
		        WHEN YEAR(SMO.tran_date) = '$maxYear' THEN SMO.standard_cost ELSE 0
	        END AS 'maxYear',
            CASE 
		        WHEN YEAR(SMO.tran_date) = '$maxYear_1' THEN SMO.standard_cost ELSE 0
	        END AS 'maxYear_1',
            CASE 
		        WHEN YEAR(SMO.tran_date) = '$maxYear_2' THEN SMO.standard_cost ELSE 0
	        END AS 'maxYear_2',
            CASE 
		        WHEN YEAR(SMO.tran_date) <= '$maxYear_3' THEN SMO.standard_cost ELSE 0
	        END AS 'maxYear_3',

			SUM(SMO.qty) as QoH, 
			SMO.standard_cost AS UnitCost,
			SMO.category_id, SC.description AS cat_description,
			SMO.tran_date, SM.units,
			SMO.trans_id, SMO.trans_no, SMO.reference, SMO.type, 
			SM.stock_id AS 'Code', IB.name AS Brand, IC.description AS Color,
			SMO.tran_date AS 'Date',  
			SMO.lot_no AS 'Serial#', 
			SMO.chassis_no AS 'Chassis#', 
			SMO.loc_code  
	
			FROM ".TB_PREF."stock_moves SMO
				INNER JOIN ".TB_PREF."stock_master SM ON SMO.stock_id = SM.stock_id
				INNER JOIN ".TB_PREF."stock_category SC ON SM.category_id = SC.category_id
				LEFT JOIN ".TB_PREF."item_brand IB ON SM.brand = IB.id
				LEFT JOIN ".TB_PREF."suppliers SUP ON IB.name = SUP.supp_ref 
				LEFT JOIN ".TB_PREF."item_distributor ID ON SM.distributor = ID.id
				LEFT JOIN item_codes IC ON SM.stock_id = IC.stock_id AND SMO.color_code = IC.item_code
					AND IC.category_id = 14
	
			WHERE SMO.tran_date <= '$date' AND SM.mb_flag <> 'D' 
				AND SM.mb_flag <> 'F' AND SMO.item_type <> 'repo' ";
	
	if ($category != 0) {
		$sql .= " AND SMO.category_id = ".db_escape($category);
	}
			
	if ($location != 'all') {
		$sql .= " AND SMO.loc_code = ".db_escape($location);
	}
	
	$sql .= " GROUP BY SMO.transno_out, SMO.type_out, SMO.stock_id, SMO.loc_code, SMO.lot_no, SMO.chassis_no";
	$sql .= " ORDER BY SMO.category_id, SMO.stock_id, SMO.tran_date, SMO.lot_no ";

    return db_query($sql,"No transactions were returned");
}

//----------------------------------------------------------------------------------------------------

function print_transaction()
{
    global $path_to_root, $SysPrefs;

	$date = $_POST['PARAM_0'];
    $category = $_POST['PARAM_1'];
    $location = $_POST['PARAM_2'];
    $comments = $_POST['PARAM_3'];
	$destination = $_POST['PARAM_4'];

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
    if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
	$rep->SetHeaderType('PO_Header');
    $rep->NewPage();

	$res = getTransactions($category, $location, $date);
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
		
			$loc = $trans['loc_code'];
		
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

	if ($catt != '')
	{
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

