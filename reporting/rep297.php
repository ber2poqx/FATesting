<?php
$page_security = 'SA_ITEMSVALREP';

// ----------------------------------------------------------------
// $ Revision:	    1.0 $
// Creator:		    spyrax10
// Date Created:    9 Mar 2022
// Title:		    Color Code List Report
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_category_db.inc");

include_once($path_to_root . "/inventory/includes/inventory_db.inc");
include_once($path_to_root . "/includes/db/manufacturing_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_codes_db.inc");

// ----------------------------------------------------------------

print_transaction();

function getTransactions($stock_id = "", $category) {

	$sql = "SELECT IC.*, SC.description AS cat_name, IC.description AS color_desc
        FROM ".TB_PREF."stock_master SM
            LEFT JOIN ".TB_PREF."item_codes IC ON SM.stock_id = IC.stock_id 
            LEFT JOIN ".TB_PREF."stock_category as SC ON SM.category_id = SC.category_id 
        
        WHERE SM.mb_flag <> 'F'";
    
    if ($category != 0) {
        $sql .= " AND SM.category_id = ".db_escape($category);
    }

    if ($stock_id != "") {
        $sql .= " AND SM.stock_id = ".db_escape($stock_id);
    }

    if ($stock_id != "") {
        $sql .= " GROUP BY IC.item_code";
    }

    $sql .= " ORDER BY SM.category_id DESC, SM.stock_id";

    return db_query($sql, "No transactions were returned");
}

//----------------------------------------------------------------------------------------------------

function print_transaction() {

    global $path_to_root, $SysPrefs;

    $category = $_POST['PARAM_0'];
	$stock_id = $_POST['PARAM_1'];
    $yes_no = $_POST['PARAM_2'];
    $comments = $_POST['PARAM_3'];
	$destination = $_POST['PARAM_4'];

    if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
		
    $dec = user_price_dec();
    $cat_name = "";

	$orientation = 'P'; // Lock print orientation

    if ($category == ALL_NUMERIC) {
        $category = 0;
    }
	else if ($category == 0) {
        $cat_name = _('ALL');
    }
    else {
        $cat_name = get_category_name($category);
    }
		
    if ($stock_id == ALL_TEXT) {
        $stock_name = "ALL STOCK ID";
    }
    else {
        $stock_name = get_stock_name($stock_id);
    }

    $cols = array(0, 120, 300, 415, 550, 600);

    $headers = array (
        _("Stock ID"),
        _("Item Color Code"),
        _("Color"),
        $yes_no == 1 ? _("Item Description") : _("Color Description")
    );

    $aligns = array('left', 'left', 'left', 'left');

    $params = array( 
		0 => $comments,
        1 => array('text' => _('Category'), 'from' => $cat_name, 'to' => ''),
    	2 => array('text' => _('Product Name'), 'from' => $stock_name, 'to' => ''),
        3 => array('text' => _('Has Color Code'), 'from' => $yes_no == 1 ? "NO" : "YES", 'to' => ''),
	);

    $rep = new FrontReport(_('Color Code List Report'), "Color_List_Report", 'LETTER', 9, $orientation);
    
    if ($orientation == 'L') {
        recalculate_cols($cols);
    }

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
	$rep->SetHeaderType('PO_Header');
    $rep->NewPage();

    $res = getTransactions($stock_id, $category);
    $cat = '';

    while ($trans = db_fetch($res)) {

        if ($cat != $trans['cat_name']) {
            $rep->NewLine();
            $rep->Font('bold');	
			$rep->SetTextColor(0, 0, 255);	
			$rep->TextCol(0, 5, $trans['cat_name']);
			$cat = $trans['cat_name'];
			$rep->Font();
			$rep->SetTextColor(0, 0, 0);
			$rep->NewLine(2);		
        }

        if ($yes_no == 1) {
            if (!item_has_color($trans['stock_id'])) {
                $rep->fontSize -= 1;
                $rep->TextCol(0, 1, $trans['stock_id']);
                $rep->TextCol(1, 2, '');
                $rep->TextCol(2, 3, $trans['color']);
                $rep->TextCol(3, 4, $trans['color_desc']);
                $rep->fontSize += 1;
                $rep->NewLine();
            }
        }
        else {
            if ($trans['is_foreign'] == 1) {
                $rep->fontSize -= 1;
                $rep->TextCol(0, 1, $trans['stock_id']);
                $rep->TextCol(1, 2, $trans['item_code']);
                $rep->TextCol(2, 3, $trans['color']);
                $rep->TextCol(3, 4, $trans['color_desc']);
                $rep->fontSize += 1;
                $rep->NewLine();
            }
        }
    }

    $rep->End();

}