<?php

/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
 ***********************************************************************/
$path_to_root = "..";
$page_security = 'SA_PR_INQ'; //Modified by spyrax10 13 Jul 2022
include_once($path_to_root . "/purchasing/includes/pr_class.inc");
include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/purchasing/includes/db/pr_db.inc");
include_once($path_to_root . "/purchasing/includes/db/suppliers_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

set_page_security(
	@$_SESSION['PR']->trans_type,
	array(ST_PURCHREQUEST => 'SA_PURCHASEREQUEST'),
	array(
		'' => 'SA_PURCHASEREQUEST'
	)
);

$js = '';
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

$_SESSION['page_title'] = _($help_context = "Purchase Request");

if (isset($_GET['pr_number'])) {
	$_POST['pr_number'] = $_GET['pr_number'];
}

if (isset($_GET['delete_pr'])) {
	$_POST['delete_pr'] = $_GET['delete_pr'];
}

page($_SESSION['page_title'], false, false, "", $js);

//-----------------------------------------------------------------------------------
// Ajax updates
//
if (get_post('SearchRequest')) {
	$Ajax->activate('pr_tbl');
}

if (get_post('delete_pr')) {
	$pr_obj = new purch_request;
	$pr_obj->trans_type = ST_PURCHREQUEST;
	$pr_obj->pr_no = get_post('delete_pr');
	$pr_no = close_pr($pr_obj);
	if ($pr_no) {
		$Ajax->activate('pr_tbl');
	}
}

start_form();

start_table(TABLESTYLE_NOBORDER);
start_row();

if ($_SESSION["wa_current_user"]->can_access_page('SA_PURCHASEREQUEST')) {
	ahref(_("New Purchase Request"), "pr_entry_items.php?NewRequest=Yes");
}

ref_cells(_("PR#:"), 'pr_number', '', null, '', true);
submit_cells('SearchRequest', _("Search"), '', _('Select documents'), 'default');
end_row();
end_table();

//---------------------------------------------------------------------------------------------
function trans_view($trans)
{
	return get_trans_view_str(ST_PURCHREQUEST, $trans["reference"]);
}

function edit_link($row)
{
	return ($row['Status'] == "Draft" ||
		$row['Status'] == "Disapproved") ? trans_editor_link(ST_PURCHREQUEST, $row["reference"]) : '';
}

function prt_link($row)
{
	return print_document_link($row['pr_no'], _("Print"), true, ST_PURCHREQUEST, ICON_PRINT);
}

function update_status_link($row)
{
	global $page_nested;

	//Modified by spyrax10 13 Jul 2022
	if ($_SESSION["wa_current_user"]->can_access_page('SA_DRAFTPRUPDATESTATUS')) {
		return $page_nested ||
		$row['Status'] == "Open" ||
		$row['Status'] == "Closed" ||
		$row['Status'] == "Partially Ordered" ||
		$row['Status'] == "Expired" ? $row["Status"] :
		pager_link(
			$row['Status'],
			"/purchasing/pr_update_status.php?PRNumber=" . $row["reference"],
			false
		);
	}
	else {
		return $row['Status'];
	}
	//
	
}

function order_link($row)
{
	return ($row['Status'] == "Expired" ||
		$row['Status'] == "Closed" ||
		$row['Status'] == "Canceled" ||
		$row['Status'] == "Draft" ||
		$row['Status'] == "Disapproved") ? '' : pager_link(
		_("Copy to PO"),
		"/purchasing/pr_order_items.php?PRNumber=" . $row["reference"],
		ICON_RECEIVE
	);
}

function close_link($row)
{
	//Modified by spyrax10 13 Jul 2022
	if ($_SESSION["wa_current_user"]->can_access_page('SA_PR_CLOSE')) {
		return ($row['Status'] == "Open" || $row['Status'] == "Partially Ordered") ?  pager_link(
			_("Close PR"),
			"/purchasing/purchase_request.php?delete_pr=" . $row["pr_no"],
			ICON_DELETE
		) : '';
	}
	else {
		return null;
	}
	//
}

function check_overdue($row)
{
	return $row['OverDue'] == 1;
}

function check_expired($row)
{
	return $row['Status'] == "Expired";
}

if (get_post("ClosePR")) {
	$trans_no = get_post("ClosePR");
	meta_forward($_SERVER['PHP_SELF'], "PRNumber=$trans_no");
}

//---------------------------------------------------------------------------------------------

//figure out the sql required from the inputs available
$sql = get_sql_for_pr_search($_POST['pr_number']);

//$result = db_query($sql,"No Request were returned");

/*show a table of the Request returned by the sql */
$cols = array(
	_("Trans #") => array('name' => 'trans_no'),
	_("PR#") => array(
		'fun' => 'trans_view'
	),
	_("Status") => array('insert' => true, 'fun' => 'update_status_link'),
	'dummy' => 'skip',
	_("Category"),
	_("Supplier"),
	_("Purchase Type"),
	_("Served Status"),
	_("PR Date") => array('name' => 'pr_date', 'type' => 'date', 'ord' => 'desc'),
	// _("Required Date") => array('name' => 'req_date', 'type' => 'date'),
	array('insert' => true, 'fun' => 'edit_link'),
	// array('insert' => true, 'fun' => 'order_link'),
	array('insert' => true, 'fun' => 'close_link')
);

$table = &new_db_pager('pr_tbl', $sql, $cols, null, null, 25);
$table->set_marker('check_expired', _("Marked Request have expired."));

$table->width = "80%";

display_db_pager($table);

end_form();
end_page();
