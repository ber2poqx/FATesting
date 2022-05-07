<?php

/**
 * Created by: spyrax10
 */

$page_security = 'SA_INVTY_REP';
$path_to_root = "../..";

include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/ui/items_cart.inc");
include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");

$js = "";
if ($SysPrefs->use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}
if (user_use_date_picker()) {
	$js .= get_js_date_picker();
}

page(_($help_context = "Inventory Adjustment - Repo List"), false, false, "", $js);

//-----------------------------------------------------------------------------------

global $Ajax;

if (get_post('SearchOrders')) {
	$Ajax->activate('inty_tbl');
}
else {

	if (get_post('adj_type')) {
		$Ajax->activate('inty_tbl');
	}

	if (get_post('stat_type')) {
		$Ajax->activate('inty_tbl');
	}
	$Ajax->activate('inty_tbl');
} 

if (get_post('stock_loc')) {
	$Ajax->activate('inty_tbl');
}

//---------------------------------------------------------------------------------------------

function get_stock_adjust_list($reference, $loc_code, $adj_type, $from_date, $to_date, $status = '', 
	$category = '') {
	
	$type = ST_INVADJUST;
	
	$sql = "SELECT A.trans_no, A.type, A.status, A.reference, A.stock_id, A.adjustment_type, A.tran_date, B.tran_date AS post_date, 
				A.comments, A.date_approved, A.approver, A.loc_code, SUM(A.standard_cost * abs(A.qty)) AS Total 
			FROM " . TB_PREF . "stock_adjustment A 
			LEFT JOIN  " . TB_PREF . "stock_moves B ON A.trans_no = B.trans_no AND A.type = B.type
			WHERE A.type = $type AND A.item_type = 'repo'";
	
	if ($status != '') {
		$sql .= " AND A.status = ".db_escape($status);
	}	
	
	if ($reference != '') {
		$sql .= " AND A.reference = ".db_escape($reference);
	}

	if ($loc_code != ALL_TEXT) {
		$sql .= " AND A.loc_code = ".db_escape($loc_code);
	}

	if ($adj_type != ALL_TEXT) {
		$sql .= " AND A.adjustment_type = ".db_escape($adj_type == 2 ? "OUT" : "IN");
	}

	if ($category != '') {
		$sql .= " AND A.category_id = ".db_escape($category);
	}

	$sql .= " AND A.tran_date >= '" . date2sql($from_date) . "' 
		AND A.tran_date <= '" . date2sql($to_date) . "'";
	
	$sql .= " GROUP BY A.trans_no";
	$sql .= " ORDER BY A.trans_no DESC";

	// $result = db_query($sql, "No Items return for stock_adjustments!");
	// set_global_connection();
	return $sql;
}

//---------------------------------------------------------------------------------------------
if (is_date_in_fiscalyear(Today())) {
	check_null_stock_adjustment('repo');
}
//---------------------------------------------------------------------------------------------

function trans_num($row) {
	return get_trans_view_str(ST_INVADJUST, $row["trans_no"]);
}

function status_link($row)
{
	global $page_nested;

	return $row["status"] == "Draft" ? pager_link(
		$row['status'],
		"/inventory/adjustments_draft.php?trans_no=" . $row["trans_no"] ."&status=0",
		false
	) : $row["status"];
}

function approver_row($row) {
	return $row['approver'];
}

function date_approved($row) {
	return phil_short_date($row['date_approved']);
}

function type_row($row) {
	return $row['adjustment_type'];
}

function reference_row($row) {
	return get_trans_view_str(ST_INVADJUST, $row["trans_no"], $row["reference"]);
}

function get_category($row) {
	return get_category_name(get_stock_catID($row['stock_id']));
}

function trans_date($row) {
	return phil_short_date($row['tran_date']);
}

function post_date($row) {
	return phil_short_date($row['post_date']);
}

function location($row) {
	return get_location_name($row["loc_code"]);
}

function document_total($row) {

	set_global_connection();
	$total = 0;

	$sql = "SELECT SUM(A.standard_cost * abs(A.qty)) AS Total
		FROM stock_adjustment A
		WHERE A.trans_no = " .db_escape($row['trans_no']);
	
	$sql .= " GROUP BY A.trans_no";

	$result = db_query($sql, "Cant calculate stock adjustment document total! (spyrax10)");
	$row = db_fetch_row($result);
	$total = $row[0];
	
	return $total;
}

function update_link($row)
{
	global $page_nested;

	return $row["status"] == "Draft" ? trans_editor_link(ST_INVADJUST, $row["trans_no"]) : null;
}

function gl_view($row)
{
	return $row['status'] == "Closed" && $row['Total'] > 0 
		? get_gl_view_str(ST_INVADJUST, $row["trans_no"]) : null;
}

function post_smo($row) {
	return $row['status'] == "Approved" ? pager_link( _("Post"),
	"/inventory/adjustments_draft.php?trans_no=" . $row["trans_no"] ."&status=1", ICON_DOC) : null;
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

numeric_type_list('Adjustment Type:', 'adj_type', 
	array(
		_('Inventory In'),
		_('Inventory Out')
	), null, true, _("All Adjustment Types")
);

end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

sql_type_list(_("Category: "), 'category', 
	get_category_list(), 'category_id', 'description', 
	'', null, true, _("All Category")
);

value_type_list(_("Status: "), 'stat_type', 
	array(
		'Draft', 
		'Approved', 
		'Disapproved', 
		'Closed' 
	), '', null, true, _('All Status Types')
);
date_cells(_("From:"), 'from_date', '', null, -user_transaction_days());
date_cells(_("To:"), 'to_date');

submit_cells('SearchOrders', _("Search"),'',_('Select documents'), 'default');

end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();
ahref_cell(_("New Inventory Adjustment - Repo"), "../adjustments.php?RepoAdjustment=1");
end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

global $Ajax;
$Ajax->activate('inty_tbl');

end_row();
end_table(); 

$sql = 
get_stock_adjust_list($_POST['reference'], $_POST['stock_loc'], $_POST['adj_type'],
	$_POST['from_date'], $_POST['to_date'], $_POST['stat_type'], $_POST['category']
);

$cols = array(
	_("Trans #") => array('fun' => 'trans_num'),
	_("Status") => array('fun' => 'status_link'),
	_("Approver") => array('fun' => 'approver_row'),
	_("Date Approved") => array('align' => 'center', 'fun' => 'date_approved'),
	_("Type") => array('align' => 'center', 'fun' => 'type_row'),
	_("Reference") => array('align' => 'center', 'fun' => 'reference_row'),
	_("Category") => array('align' => 'center', 'fun' => 'get_category'),
	_("Transaction Date") => array('align' => 'center', 'fun' => 'trans_date'),
	_("Posting Date") => array('align' => 'center', 'fun' => 'post_date'),
	_("Location") => array('align' => 'center', 'fun' => 'location'),
	_("Document Total") => array('align' => 'right', 'type' => 'amount', 'fun' => 'document_total'),
	array('insert' => true, 'fun' => 'update_link', 'align' => 'center'),
	array('insert' => true, 'fun' => 'post_smo', 'align' => 'center'),
	array('insert' => true, 'fun' => 'gl_view', 'align' => 'center'),
);

$table = &new_db_pager('inty_tbl', $sql, $cols, null, null, 25);

$table->width = "98%";

display_db_pager($table);

end_form();
end_page(true);
