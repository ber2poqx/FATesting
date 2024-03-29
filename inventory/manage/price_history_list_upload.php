<?php

/**
 * Added by: Albert
 * Date Added: 15 Sep 2022
*/

$page_security = 'SA_PRICE_HISTORY_LIST';
$path_to_root = "../..";


include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

add_access_extensions();

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/includes/ui.inc");


$js = '';

if ($SysPrefs->use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}

if (user_use_date_picker()) {
	$js .= get_js_date_picker();
}

$_SESSION['page_title'] = _($help_context = "List of Price Upload");
page($_SESSION['page_title'], false, false, "", $js);

//-----------------------------------------------------------------------------------
global $Ajax;

if (get_post('SearchOrders')) {
	$Ajax->activate('price_hstry_tbl');
}

if (get_post('stock_loc')) {
	$Ajax->activate('price_hstry_tbl');
}

//---------------------------------------------------------------------------------------------

function get_price_history_list_upload($search_val= null){

	$sql = "SELECT a.reference, 
			case when a.status = 0 then 'Draft'
			when a.status = 1 then 'Approved'
			when a.status = 2 then 'Disapproved' 
			else 'Closed' end as status
			,a.accounting_remarks ,a.date_defined
			,case when a.date_approved = '0000-00-00' Then '' Else a.date_approved END 
			,case when a.date_post = '0000-00-00' Then '' Else a.date_post END 
			, a.date_epic

			FROM ".TB_PREF."list_of_price_upload a 
			where a.status is not null
			group by a.reference";

	if($search_val <> null){
		$sql.="AND stock_id like".db_escape($search_val);
	}
	$sql.= " order by a.reference desc";


return $sql;

}

function update_price_status_link($row) {
	global $page_nested;

	if ($_SESSION["wa_current_user"]->can_access_page('SA_PRICE_UPDATE_STATUS') &&  $row["accounting_remarks"] != "") {
		
		$status_link = 
		$row["status"] == "Draft" ? pager_link(
			$row['status'],
			"/inventory/manage/price_approval.php?Reference=" . $row["reference"],
			false
		) : $row["status"];
	}
	else {
		$status_link = $row["status"];
	}

	return $status_link;
}
function upload_view($row) {
	global $page_nested;

	if ($_SESSION["wa_current_user"]->can_access_page('SA_PRICE_UPLOAD_VIEW')) {
		
		$status_link = 
		$row["reference"] <> "" ? pager_link(
			$row['reference'],
			"/inventory/view/price_upload_view.php?Reference=" . $row["reference"],
			false
		) : $row["reference"];
	}
	else {
		$status_link = $row["reference"];
	}

	return $status_link;
}

function accounting_approval_link($row) {
	global $page_nested;

	if ($_SESSION["wa_current_user"]->can_access_page('SA_PRICE_ACCOUNTING_APPROVAL') && $row["accounting_remarks"] == "" && $row["status"] == "Draft") {
		$price_link = $row["accounting_remarks"] == "" ? pager_link(
			'Accounting Approval',
			"/inventory/manage/price_accounting_approval.php?Reference=" . $row["reference"],
			ICON_DOC
		) : '';
	}
	else {
		$price_link = '';
	}

	return $price_link;
}
//Added by Albert 09/15/2022
function post_price($row) {
	global $page_nested;

	if ($_SESSION["wa_current_user"]->can_access_page('SA_POSTPRICE')) {
		$price_link = $row["status"] == "Approved" ? pager_link(
			'post',
			"/inventory/manage/post_price.php?reference=" . $row["reference"],
			ICON_DOC
		) : '';
	}
	else {
		$price_link = '';
	}

	return $price_link;
}

//---------------------------------------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);
start_row();

ref_cells(_("Stock_id:"), 'search_val', '', null, '', true);

// date_cells(_("From:"), 'from_date', '', null, -user_transaction_days());
// date_cells(_("To:"), 'to_date');

submit_cells('SearchOrders', _("Search"),'',_('Select documents'), 'default');

end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

global $Ajax;
$Ajax->activate('price_hstry_tbl');

end_row();
end_table(); 

$sql = get_price_history_list_upload(get_post('search_val'));

$cols = array(
	_("Reference #") => array('insert' => false, 'fun' => 'upload_view'),
	_("Status") => array('insert' => false, 'fun' => 'update_price_status_link'),
	_("Accounting Remarks"),
	_("Create Date"),
	_("Approved Date"),
	_("Posting Date"),
	_("Date Effect"),
	array('insert'=>true, 'fun'=>'accounting_approval_link'), 
	array('insert'=>true, 'fun'=>'post_price'), 
);


$table = &new_db_pager('price_hstry_tbl', $sql, $cols, null, null, 25);

$table->width = "75%";

display_db_pager($table);

end_form();
end_page();


