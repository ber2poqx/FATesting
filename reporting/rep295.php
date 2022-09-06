<?php
$page_security = 'SA_SERIAL_LIST';

/**
 * Created by : spyrax10
 * Date Created: 16 Jun 2022
 * Title: PNP Clearance Monitoring Report
*/

$path_to_root="..";

include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/inventory/includes/db/items_db.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");

//----------------------------------------------------------------------------------------------------
print_transaction();
//----------------------------------------------------------------------------------------------------

function print_transaction() {

    global $path_to_root, $SysPrefs, $def_coy;

    $branch_id = $_POST['PARAM_0'];
    $cleared_stat = $_POST['PARAM_1'];
    $show_all = $_POST['PARAM_2'];
    $serial_no = $_POST['PARAM_3'];
    $comments = $_POST['PARAM_4'];
	$destination = $_POST['PARAM_5'];

    if ($destination) {
        include_once($path_to_root . "/reporting/includes/excel_report.inc");
    }	
	else {
        include_once($path_to_root . "/reporting/includes/pdf_report.inc");
    }

    if ($branch_id != null) {
        $branch_name = get_company_value($branch_id, 'name');
    }
    else {
        $branch_name = _("All Branches");
    }

    if ($cleared_stat == 'ALL') {
        $stat = "All Clearance Status";
    }
    else {
        $stat = $cleared_stat == 1 ? _("Cleared") : _("Not Cleared");
    }

	$orientation = 'L';

    $headers = array(
        _("Origin Branch"),
        _("Reference"),
        _("Category"),
        _("Date"),
        _("Item Code | Color | Color Description"),
        _("Serial/Engine Number"),
        _("Chassis Number"),
        _("PNP Cleared"),
        _("Note")
    );

    if ($show_all == 1) {
        array_splice($headers, 7, 0, 'QoH');
    }

    if ($show_all == 1) {
        $cols = array(0, 90, 140, 190, 230, 410, 480, 540, 560, 610, 0);
    }
    else {
        $cols = array(0, 90, 140, 190, 230, 410, 480, 530, 620, 0);
    }

    $aligns = array('left', 'left', 'center', 'center',
        'left', 'left', 'left', 'center', 'left'
    );

    if ($show_all == 1) {
        array_splice($aligns, 7, 0, 'center');
    }

    $params = array( 
		0 => $comments,
        1 => array('text' => _('Origin Branch'), 'from' => $branch_name, 'to' => ''),
        2 => array('text' => _('Clearance Status'), 'from' => $stat, 'to' => ''),
	);

    $rep = new FrontReport(_('PNP Clearance Monitoring Report'), "PNP_CLEARANCE_REPORT", 'LEGAL', 9, $orientation);

    if ($orientation == 'L') {
        recalculate_cols($cols);
    }

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
	$rep->SetHeaderType('PO_Header');
    $rep->NewPage();

    $res = get_serial_list('', $branch_id, null, $cleared_stat, $show_all, $serial_no);
   
    foreach ($res as $value => $trans) {

        $stock_row = db_fetch_assoc(get_stock_by_itemCode($trans['serialise_item_code']));
        $is_cleared = $trans['cleared'] == 1 ? _("Yes") : _("No");
        $reference = str_replace($trans['loc_code'] . "-", "", $trans['reference']);
        $loc_name = str_replace($trans['loc_code'] . " - ", "", get_company_value(get_comp_id($trans['branch']), 'name'));

        $rep->fontSize -= 1;

        $rep->TextCol(0, 1, $loc_name);
        $rep->SetTextColor(0, 0, 255);
        $rep->TextCol(1, 2, $reference);
        $rep->SetTextColor(0, 0, 0);
        $rep->TextCol(2, 3, get_category_name($stock_row['category_id']));
        $rep->SetTextColor(0, 0, 255);	
        $rep->TextCol(3, 4, $trans['trans_date']);
        $rep->SetTextColor(0, 0, 0);
        $rep->TextCol(4, 5, $stock_row['color'] != '' ? 
            $trans['stock_id'] . " | ".  $stock_row['color'] . " | " 
            . substr(get_color_description($trans['serialise_item_code'], $trans['stock_id']), 0 , 25) 
            : $trans['stock_id']
        );
        $rep->TextCol(5, 6, $trans['serialise_lot_no']);
        $rep->TextCol(6, 7, $trans['serialise_chasis_no']);

        if ($show_all == 1) {
            $rep->TextCol(7, 8, $trans['qoh']);
            $rep->TextCol(8, 9, $is_cleared);
            $rep->TextCol(9, 10, $trans['pnp_note']);
        }
        else {
            $rep->TextCol(7, 8, $is_cleared);
            $rep->TextCol(8, 9, $trans['pnp_note']);
        }

        $rep->fontSize += 1;
        $rep->NewLine();
    }

    $rep->End();
}
