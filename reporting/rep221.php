<?php
$page_security = 'SA_SUPP_REP';

// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Modified by:	spyrax10
// date:	2021-04-29
// Title:	Purchase Order Summary Report
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

function getTransactions($category, $fromsupp, $item, $from, $to, $subcat, $status)
{
  
	$from = date2sql($from);
	$to = date2sql($to);
	
	$sql = "SELECT PO.order_no AS Order_Num,
	SM.brand AS Supp_Id, SS.supp_name AS Supplier, CHK.StatID,
	CASE 
		WHEN PO.draft_status = 0 AND PO.posted = 'N' THEN 'DRAFT'
    	WHEN PO.draft_status = 1 AND PO.posted = 'N' THEN 'APPROVED'
    	WHEN PO.draft_status = 2 AND PO.posted = 'N' THEN 'DISAPPROVED'
    	WHEN POD.quantity_ordered - POD.quantity_received = 0 
			AND PO.draft_status != 2 AND PO.draft_status != 0 THEN 'CLOSED'
	ELSE 'OPEN' END AS Status,

	PO.ord_Date AS PO_Date, PO.reference AS PO_Num,
	SC.description AS Item_Group, POD.item_code AS Model,
	ID.name AS Category, IB.name AS Brand, II.name AS Sub_Category,
	
	POD.quantity_ordered AS Quantity,
	POD.quantity_received AS Delivered,
	POD.quantity_ordered - POD.quantity_received AS UnDelivered,
	TRUNCATE(POD.unit_price * POD.quantity_ordered, 2) AS Total
	
	FROM ".TB_PREF."purch_orders PO
		INNER JOIN ".TB_PREF."purch_order_details POD ON PO.order_no = POD.order_no
		LEFT JOIN ".TB_PREF."stock_master SM ON POD.item_code = SM.stock_id
		LEFT JOIN ".TB_PREF."stock_category SC ON PO.category_id = SC.category_id
		LEFT JOIN ".TB_PREF."item_distributor ID ON SM.distributor = ID.id
		LEFT JOIN ".TB_PREF."item_importer II ON SM.importer = II.id
		LEFT JOIN ".TB_PREF."item_brand IB ON SM.brand = IB.id
		LEFT JOIN ".TB_PREF."suppliers SS ON PO.supplier_id = SS.supplier_id
		LEFT JOIN (
			SELECT A.order_no,
				CASE 
					WHEN A.draft_status = 0 AND A.posted = 'N' THEN 6
					WHEN A.draft_status = 1 AND A.posted = 'N' THEN 5
    				WHEN A.draft_status = 2 AND A.posted = 'N' THEN 4		
    				WHEN B.quantity_ordered - B.quantity_received = 0 
						AND A.draft_status != 2 AND A.draft_status != 0 THEN 2
				ELSE 1 END AS StatID 
			FROM ".TB_PREF."purch_orders A
				INNER JOIN ".TB_PREF."purch_order_details B ON A.order_no = B.order_no
		) CHK ON PO.order_no = CHK.order_no

	WHERE
		PO.supplier_id = SS.supplier_id 
		AND PO.ord_date >= '$from' 
		AND PO.ord_date <= '$to' ";
		

	if ($fromsupp != ALL_TEXT) {
		$sql .= " AND PO.supplier_id =".db_escape($fromsupp);
	}
	if ($category != ALL_TEXT) {
		$sql .= " AND PO.category_id =".db_escape($category);
	}
	if ($subcat != -1) {
		$sql .= " AND II.id =".db_escape($subcat);
	} 
	if ($item != ALL_TEXT) {
		$sql .= " AND POD.item_code =".db_escape($item);
	} 

	if ($status == 3) {
		$sql .= " AND CHK.StatID = 1 AND POD.quantity_ordered != POD.quantity_received 
			AND (POD.quantity_ordered - POD.quantity_received) != POD.quantity_ordered";
	}
	else {
		if ($status != 0)  
	    $sql .= " AND CHK.StatID =".db_escape($status);
	}

	$sql .= " GROUP BY PO.order_no DESC, SC.description ";
	$sql .= " ORDER BY SM.category_id";
	
    return db_query($sql, "No transactions were returned");
}

function print_PO_Report() {

    global $path_to_root;
	
    $from 		= $_POST['PARAM_0'];
	$to 		= $_POST['PARAM_1'];
	$category 	= $_POST['PARAM_2'];
	$fromsupp 	= $_POST['PARAM_3'];
	$subcat 	= $_POST['PARAM_4'];
	$item 		= $_POST['PARAM_5'];
	$status 	= $_POST['PARAM_6'];
	$comments	= $_POST['PARAM_7'];
	$destination= $_POST['PARAM_8'];

	if ($destination) {
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	}
	else {
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	}
		
	$orientation = 'L'; // Lock print orientation
	
    $dec = user_price_dec();

	if ($status == 0) {
		$stat = _('ALL');
	}
	else if ($status == 2) {
		$stat = _('CLOSED');
	}
	else if ($status == 1) {
		$stat = _('OPEN');
	}
	else if ($status == 3) {
		$stat = _('PARTIALLY DELIVERED');
	}

	if ($category == ALL_NUMERIC) {
		$category = 0;
	}
	if ($category == 0) {
		$cat = _('ALL');
	}
	else {
		$cat = get_category_name($category);
	}

	if ($fromsupp == '') {
		$froms = _('ALL');
	}
	else {
		$froms = get_supplier_name($fromsupp);
	}

	if ($item == '') {
		$itm = _('ALL');
	}
	else {
		$itm = $item;
	}

	$params = array(0 => $comments,
		1 => array('text' => _('Period'),'from' => $from, 'to' => $to),
		2 => array('text' => _('Supplier'), 'from' => $froms, 'to' => ''),
		3 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
		4 => array('text' => _('Model'), 'from' => $itm, 'to' => ''),
		5 => array('text' => _('Status'), 'from' => $stat, 'to' => '')
	);

	$cols = array(0, 38, 103, 145, 210, 290, 350, 
		420, 440, 480, 513, 0
	);

	$headers = array(
		_('P.O Date'), 
		_('P.O No.'),
		_('Status'), 
		_('Model'),
		_('Category'),
		_('Sub_Category'),
		_('Brand Name'),
		_('Qty'),
		_('Delivered'),
		_('UnDelivered'),
		_('Total')
	);

	$aligns = array('left', 'left', 'left', 'left', 'left', 'left', 'left', 
		'center', 'center', 'center', 'right'
	);

    $rep = new FrontReport(_('Purchase Order Summary Report v2'), "POSummaryRep", 'LETTER', 9, $orientation);
    if ($orientation == 'L') {
		recalculate_cols($cols);
	}
	
    $rep->Info($params, $cols, $headers, $aligns, 
		null, null, null, true, true, true
	);
    $rep->SetHeaderType('PO_Header');
	$rep->NewPage();

	$Tot_Val = $qtyTot = $undTot = $delTot = 0;
	$qty = $del = $und = $tot = 0;
	$Supplier = '';
	$SuppTot_Val = 0;
	$res = getTransactions($category, $fromsupp, $item, $from, $to, $subcat, $status);
	$catt = '';

	while ($row = db_fetch($res)) {

		if ($catt != $row['Item_Group']) {		
			
			if ($catt != '') {
				$rep->NewLine(2);
				$rep->Font('bold');
				$rep->Line($rep->row  - 4);
				$rep->TextCol(0, 1, _('Sub_Total'));
				$rep->AmountCol(7, 8, $qty);
				$rep->AmountCol(8, 9, $del);
				$rep->AmountCol(9, 10, $und);
				$rep->AmountCol(10, 11, $tot, $dec);
				$rep->Line($rep->row  - 4);
				$rep->NewLine(2);
				$rep->Font();

				$qty = $del = $und = $tot = 0;
			}
			$rep->NewLine();
			$rep->fontSize += 1;
			$rep->Font('bold');		
			$rep->SetTextColor(0, 0, 255);	
			$rep->TextCol(0, 5, $row['Item_Group']);
			$catt = $row['Item_Group'];		
			$rep->Font();
			$rep->fontSize -= 1;
			$rep->SetTextColor(0, 0, 0);
			$rep->NewLine();
		}
		
		$rep->fontSize -= 1;
		$dec2 = get_qty_dec($row['Model']);
		$rep->NewLine();
		$rep->TextCol(0, 1, sql2date($row['PO_Date']));
		$rep->TextCol(1, 2, $row['PO_Num']);
		$rep->TextCol(2, 3, $row['Status']);
		$rep->TextCol(3, 4, substr($row['Model'], 0, 16));
		$rep->TextCol(4, 5, substr($row['Category'], 0, 15));
		$rep->TextCol(5, 6, $row['Sub_Category']);
		$rep->TextCol(6, 7, $row['Brand']);
		$rep->TextCol(7, 8, $row['Quantity']);
		$rep->TextCol(8, 9, $row['Delivered']);
		$rep->TextCol(9, 10, $row['UnDelivered']);
		$rep->AmountCol2(10, 11, $row['Total']);
		$rep->fontSize += 1;
		$rep->NewLine(.5);

		$qtyTot += $row['Quantity'];		$qty += $row['Quantity'];
		$delTot += $row['Delivered'];		$del += $row['Delivered'];
		$undTot += $row['UnDelivered'];	$und += $row['UnDelivered'];
		$Tot_Val += $row['Total'];			$tot += $row['Total'];

	}

	if ($catt != '') {
		$rep->NewLine(2);
		$rep->Font('bold');
		$rep->Line($rep->row  - 4);
		$rep->TextCol(0, 1, _('Sub_Total'));
		$rep->AmountCol(7, 8, $qty);
		$rep->AmountCol(8, 9, $del);
		$rep->AmountCol(9, 10, $und);
		$rep->AmountCol(10, 11, $tot, $dec);
		$rep->Line($rep->row  - 4);
		$rep->Font();		
	}

	$rep->NewLine(3);
	$rep->Font('bold');
	$rep->fontSize += 2;	
	$rep->TextCol(0, 10, _('Grand Total'));
	$rep->AmountCol(7, 8, $qtyTot);
	$rep->AmountCol(8, 9, $delTot);
	$rep->AmountCol(9, 10, $undTot);
	$rep->AmountCol(10, 11, $Tot_Val, $dec);
	$rep->Line($rep->row  - 4);
	$rep->fontSize -= 2;
	$rep->Font();
	$rep->NewLine();
    $rep->End();
}
