<?php

/**
 * Created by spyrax10
 * Date Created: 28 Mar 20222
 */

$page_security = 'SA_CUST_SALES';

$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

print_transaction();

function get_customer_list($area = 0) {

    $sql = "SELECT DM.*, AR.description AS area_name, 
        CB.br_name, CB.br_address, CB.br_post_address,

        CASE 
            WHEN IFNULL(CP.phone2, '') <> '' THEN CONCAT(CP.phone, ' / ', CP.phone2)
        ELSE CP.phone
        END AS contact_num,
        CP.email, CP.facebook, ST.sales_type, CN.real_name

        FROM ".TB_PREF."debtors_master DM 
            INNER JOIN ".TB_PREF."areas AR ON DM.area = AR.area_code
            INNER JOIN ".TB_PREF."cust_branch CB ON DM.debtor_no = CB.debtor_no
            INNER JOIN ".TB_PREF."crm_persons CP ON DM.debtor_ref = CP.ref
            INNER JOIN ".TB_PREF."sales_types ST ON DM.sales_type = ST.id
            INNER JOIN " . TB_PREF . "users CN ON AR.collectors_id = CN.user_id
        WHERE DM.inactive = 0";
    
    if ($area != 0) {
        $sql .= " AND DM.area = ".db_escape($area);
    }

    //$sql .= " GROUP BY DM.area, DM.debtor_no";
    $sql .= " ORDER BY DM.name, AR.description";

    return db_query($sql,"No transactions were returned");
}

//----------------------------------------------------------------------------------------------------

function print_transaction() {
    global $path_to_root;

    $area = $_POST['PARAM_0'];
    $comments = $_POST['PARAM_1'];
	$destination = $_POST['PARAM_2'];

	if ($destination) {
        include_once($path_to_root . "/reporting/includes/excel_report.inc");
    }
	else {
        include_once($path_to_root . "/reporting/includes/pdf_report.inc");
    }

	$orientation = 'L';    
    $dec = 0;

    if ($area == 0) {
        $sarea = _('ALL AREAS');
    }
	else {
        $sarea = get_area_name($area);
    }

    $cols = array(0, 60, 150, 300, 400, 490, 0);

    $headers = array(
        _("Customer Code"),
        _("Customer Name"),
        _("Customer Address"),
        _("Contact Number/s"),
        _("Area Name"),
        _("Price List Type")
    );

    $aligns = array('left', 'left', 'left', 'left', 'center', 'right');

    $params = array(
        0 => $comments,
        1 => array('text' => _('Area'), 'from' => $sarea, 'to' => ''),
    );

    $rep = new FrontReport(_('Customer Details Listing'), "CustomerDetailsListing", 'LETTER', 9, $orientation);
    if ($orientation == 'L') {
        recalculate_cols($cols);
    }

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    
    if ($destination) {
        $rep->SetHeaderType('PO_Header');
    }
    else {
        $rep->SetHeaderType('PO_Header');     
    }
	
    $rep->NewPage();

    $res = get_customer_list($area);
    $area_ = '';

    while ($trans = db_fetch($res)) {

        // if ($area_ != $trans['area_name']) {

        //     $rep->Font('bold');	
		// 	$rep->SetTextColor(0, 0, 255);	
		// 	$rep->TextCol(0, 3, $trans['area_name'] );
        //     $rep->TextCol(4, 6, " Collector Name: " . $trans['real_name']);
		// 	$area_ = $trans['area_name'];
		// 	$rep->Font();
		// 	$rep->SetTextColor(0, 0, 0);
		// 	$rep->NewLine(1.5);	
        // }

        $rep->NewLine(.5);
        $rep->TextCol(0, 1, $trans['debtor_ref']);
        $rep->TextCol(1, 2, $trans['name']);
        $rep->TextCol(2, 3, $trans['address']);
        $rep->TextCol(3, 4, $trans['contact_num']);
        $rep->TextCol(4, 5, $trans['area_name']);
        $rep->TextCol(5, 6, $trans['sales_type']);
        $rep->NewLine();
    }

    $rep->End();
}