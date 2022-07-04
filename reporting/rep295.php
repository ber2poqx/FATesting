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

function get_transactions($company_id, $cleared_stat = "ALL") {

    set_global_connection($company_id);
    $branch_code = get_company_value($company_id, 'branch_code');

    $sql = "SELECT '$branch_code' AS branch, $branch_code.* 
    FROM ".TB_PREF."item_serialise AS $branch_code";

    $sql .= " WHERE IFNULL(serialise_item_code, '') <> ''";

    if ($cleared_stat != 'ALL') {
		$sql .= " AND cleared = " . db_escape($cleared_stat);
	}

    $result = db_query($sql, _("get_all_serial()"));

    set_global_connection();

	return $result;
}

//----------------------------------------------------------------------------------------------------

function print_transaction() {

    global $path_to_root, $SysPrefs, $def_coy;

    $branch_id = $_POST['PARAM_0'];
    $cleared_stat = $_POST['PARAM_1'];
    $comments = $_POST['PARAM_2'];
	$destination = $_POST['PARAM_3'];

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
        _("Category"),
        _("Item Code"),
        _("Color | Description"),
        _("Serial/Engine Number"),
        _("Chassis Number"),
        _("PNP Cleared"),
        _("Note")
    );

    $cols = array(0, 70, 150, 220, 430, 540, 600, 650, 0);

    $aligns = array('left', 'center', 'left', 'left', 
        'left', 'left', 'center', 'left'
    );

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

    $res = get_serial_list('', $branch_id, null, $cleared_stat);
   
    foreach ($res as $value => $trans) {

        $stock_row = db_fetch_assoc(get_stock_by_itemCode($trans['serialise_item_code']));
        $is_cleared = $trans['cleared'] == 1 ? _("Yes") : _("No");

        $rep->fontSize -= 1;

        $rep->TextCol(0, 1, get_company_value(get_comp_id($trans['branch']), 'name'));
        $rep->TextCol(1, 2, get_category_name($stock_row['category_id']));
        $rep->TextCol(2, 3, $stock_row['stock_id']);
        $rep->TextCol(3, 4, $stock_row['color'] != '' ? $stock_row['color'] . " | " . 
            get_color_description($trans['serialise_item_code'], $stock_row['stock_id']) : 
            get_color_description($trans['serialise_item_code'], $stock_row['stock_id'])
        );
        $rep->TextCol(4, 5, $trans['serialise_lot_no']);
        $rep->TextCol(5, 6, $trans['serialise_chasis_no']);
        $rep->TextCol(6, 7, $is_cleared);
        $rep->TextCol(7, 8, $trans['pnp_note']);

        $rep->fontSize += 1;
        $rep->NewLine();
    }

    $rep->End();
}
