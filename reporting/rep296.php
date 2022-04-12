<?php
$page_security = 'SA_ITEMSANALYTIC';

/**
 * Created by : spyrax10
 * Date Created: 11 Mar 2022
 * Title: Item List Detailed Report
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

function get_transactions($category, $brand) {
    
    set_global_connection(0);
    $sql = "SELECT SM.*, SC.description AS cat_name, SM.description AS prod_desc,
        IB.name AS item_brand, II.name AS class, ID.name AS sub_cat

        FROM ".TB_PREF."stock_master SM
            LEFT JOIN ".TB_PREF."stock_category as SC ON SM.category_id = SC.category_id 
            LEFT JOIN ".TB_PREF."item_brand IB ON SM.brand = IB.id 
            LEFT JOIN ".TB_PREF."item_importer II ON SM.importer = II.id
            LEFT JOIN ".TB_PREF."item_distributor ID ON SM.distributor = ID.id 
        WHERE mb_flag <> 'F'";

    if ($category != 0) {
        $sql .= " AND SM.category_id = ".db_escape($category);
    }

    if ($brand != 0) {
        $sql .= " AND SM.brand = ".db_escape($brand);
    }

    $sql .= "GROUP BY SM.stock_id";
    $sql .= " ORDER BY SM.category_id DESC, SM.stock_id";

    return db_query($sql, "No transactions were returned");

}

function print_transaction() {

    global $path_to_root, $SysPrefs;

    $category = $_POST['PARAM_0'];
    $brand = $_POST['PARAM_1'];
    $comments = $_POST['PARAM_2'];
	$destination = $_POST['PARAM_3'];

    if ($destination) {
        include_once($path_to_root . "/reporting/includes/excel_report.inc");
    }	
	else {
        include_once($path_to_root . "/reporting/includes/pdf_report.inc");
    }
		
    $dec = user_price_dec();

	$orientation = 'L';

    if ($category == ALL_NUMERIC) {
        $category = 0;
    }
	if ($category == 0) {
        $cat_name = _('ALL CATEGORIES');
    }
	else {
        $cat_name = get_category_name($category);
    }

    if ($brand == ALL_NUMERIC) {
        $brand = 0;
    }
	if ($brand == 0) {
        $brand_name = _('ALL BRANDS');
    }
	else {
        $brand_name = get_brand_descr($brand);
    }
		
    $cols = array(0, 100, 230, 290, 360, 470);

    $headers = array(
        _('Item Code'),
        _('Description'),
        _('Category'),
        _('Brand'),
        _('Sub-Category'),
        _('Classification')
    );

    $aligns = array('left', 'left', 'center', 'center', 'left', 'left');

    $params = array( 
		0 => $comments,
        1 => array('text' => _('Category'), 'from' => $cat_name, 'to' => ''),
        2 => array('text' => _('Brand'), 'from' => $brand_name, 'to' => '')
	);

    $rep = new FrontReport(_('Item List Detailed Report'), "Item_List_Report", 'LETTER', 9, $orientation);

    if ($orientation == 'L') {
        recalculate_cols($cols);
    }

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
	$rep->SetHeaderType('PO_Header');
    $rep->NewPage();

    $res = get_transactions($category, $brand);
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

        $rep->fontSize -= 1;
        $rep->TextCol(0, 1, $trans['stock_id']);
        $rep->TextCol(1, 2, $trans['prod_desc']);
        $rep->TextCol(2, 3, $trans['cat_name']);
        $rep->TextCol(3, 4, $trans['item_brand']);
        $rep->TextCol(4, 5, $trans['sub_cat']);
        $rep->TextCol(5, 6, $trans['class']);
        $rep->fontSize += 1;
        $rep->NewLine();
    }

    $rep->End();

}