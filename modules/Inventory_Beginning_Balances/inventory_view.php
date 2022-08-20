<?php
/**
 * Author: @spyrax10
 * Name: Inventory_Opening_Balances_viewer
 */

$page_security = 'SA_INVTYOPEN_LIST';
$path_to_root = "../..";

include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

add_access_extensions();

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/fixed_assets/includes/fixed_assets_db.inc");
include_once($path_to_root . "/modules/Inventory_Beginning_Balances/includes/item_adjustments_ui.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");
include_once($path_to_root . "/includes/sweetalert.inc");

$js = '';

if ($SysPrefs->use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}

if (user_use_date_picker()) {
	$js .= get_js_date_picker();
}

$_SESSION['page_title'] = _($help_context = "Inventory Opening Balances List");
page($_SESSION['page_title'], false, false, "", $js);

//-----------------------------------------------------------------------------------
global $Ajax;

if (get_post('SearchOrders')) {
	$Ajax->activate('inty_tbl');
}

if (get_post('stock_loc')) {
	$Ajax->activate('inty_tbl');
}

//---------------------------------------------------------------------------------------------

function gl_view($row) {

	if ($_SESSION["wa_current_user"]->can_access_page('SA_GLTRANSVIEW')) {
		$gl_link = $row['Total'] > 0 ? get_gl_view_str(ST_INVADJUST, $row["trans_no"]) : null;
	}
	else {
		$gl_link = '';
	}

	return $gl_link;
}

function trans_num($row) {
	return get_trans_view_str(ST_INVADJUST, $row["trans_no"]);
}

function trans_ref($row) {
	return get_trans_view_str(ST_INVADJUST, $row["trans_no"], $row["reference"]);
}

function category($row) {
	return $row["description"];
}

function loc_($row) {
	return get_location_name($row['loc_code']);
}

function doc_total($row) {
	return $row["Total"];
}

function trans_date($row) {
	return phil_short_date($row['tran_date']);
}

//---------------------------------------------------------------------------------------------
function get_stock_moves_list($reference, $loc_code, $from_date, $to_date, $category = '') {
	
	set_global_connection();
	
	$sql = "SELECT A.trans_no, A.type, A.reference, A.tran_date, SC.description, 
				A.loc_code, SUM(A.standard_cost * abs(A.qty)) AS Total 
			FROM stock_moves A 
                LEFT JOIN stock_category SC ON A.category_id = SC.category_id
				LEFT JOIN stock_adjustment ADJ ON A.trans_no = ADJ.trans_no
            WHERE A.type = 17 AND IFNULL(ADJ.trans_no, '') = '' ";
	
	if ($reference != '') {
		$sql .= " AND A.reference = ".db_escape($reference);
	}

	if ($loc_code != ALL_TEXT) {
		$sql .= " AND A.loc_code = ".db_escape($loc_code);
	}

	if ($category != '') {
		$sql .= " AND A.category_id = ".db_escape($category);
	}

	$sql .= " AND A.tran_date >= '" . date2sql($from_date) . "' 
		AND A.tran_date <= '" . date2sql($to_date) . "'";
	
	$sql .= " GROUP BY A.trans_no, A.tran_date ";
    $sql .= " ORDER BY A.tran_date DESC";

	return $sql;
}
//---------------------------------------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);
start_row();

ref_cells(_("Reference #:"), 'reference', '', null, '', true);
sql_type_list(_("Location: "), 'stock_loc', 
	get_location_list(), 'loc_code', 'location_name', 
	'', null, true, _("All Locations")
);

end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

sql_type_list(_("Category: "), 'category', 
	get_category_list(), 'category_id', 'description', 
	'', null, true, _("All Category")
);

date_cells(_("From:"), 'from_date', '', null, -user_transaction_days());
date_cells(_("To:"), 'to_date');

submit_cells('SearchOrders', _("Search"),'',_('Select documents'), 'default');

end_row();
end_table();

if ($_SESSION["wa_current_user"]->can_access_page('SA_INVTYOPEN_ENTRY')) {
	start_table(TABLESTYLE_NOBORDER);
	start_row();
	ahref_cell(_("New Inventory Opening"), "../../modules/Inventory_Beginning_Balances/inventory.php?");
	end_row();
	end_table();
}

start_table(TABLESTYLE_NOBORDER);
start_row();

global $Ajax;
$Ajax->activate('inty_tbl');

end_row();
end_table(); 

$sql = 
get_stock_moves_list(
	$_POST['reference'], 
	$_POST['stock_loc'], 
	$_POST['from_date'], 
	$_POST['to_date'],
	$_POST['category']
);

$cols = array(
	_("Trans #") => array('align' => 'left', 'fun' => 'trans_num', 'ord' => ''),
	_("Reference") => array('align' => 'center', 'fun' => 'trans_ref'),
	_("Category") => array('align' => 'center', 'fun' => 'category'),
	_("Location") => array('align' => 'center', 'fun' => 'loc_'),
	_("Transaction Date") => array('align' => 'center', 'fun' => 'trans_date'),
	_("Document Total") => array('align' => 'right', 'type' => 'amount', 'fun' => 'doc_total'),
	array('insert' => true, 'fun' => 'gl_view', 'align' => 'center')
);


$table = &new_db_pager('inty_tbl', $sql, $cols, null, null, 25);

$table->width = "75%";

display_db_pager($table);

end_form();
end_page();