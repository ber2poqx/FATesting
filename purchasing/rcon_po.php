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
$page_security = 'SA_RCONPO';
include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/purchasing/includes/db/consignment_db.inc");
include_once($path_to_root . "/purchasing/includes/db/suppliers_db.inc");


$js = '';
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

$_SESSION['page_title'] = _($help_context = "Receive Consignment Items");


page($_SESSION['page_title'], false, false, "", $js);

if (isset($_GET['ConsignmentNumber'])) {
	$_POST['consign_no'] = $_GET['ConsignmentNumber'];
}

if (get_post('SearchConsignment')) {
	$Ajax->activate('rcon_po_tbl');
}

start_form();

start_table(TABLESTYLE_NOBORDER);
start_row();
ahref(_("New Consignment"), "consignment_entry.php?NewConsign=Yes");
ref_cells(_("Serial #:"), 'serial_no', '', null, '', true);
submit_cells('SearchConsignment', _("Search"), '', _('Select documents'), 'default');
end_row();
end_table();

function trans_view($trans)
{
	return get_trans_view_str(ST_RECEIVECONSIGN, $trans["reference"]);
}

function copy_from_rcon_to_po_link($row)
{
	return ($row["Status"] == "Open" && $row["serialise_lot_no"] != "") ? pager_link(
		_("Copy to PO"),
		"/purchasing/copy_from_rcon_to_po.php?ConsignmentNumber=" . $row["reference"] . "&Serial=" . $row["serialise_id"],
		ICON_RECEIVE
	) : "";
}

function view_serial($row)
{
	return viewer_link(
		_("Serial"),
		"purchasing/consignment_serial_details.php?serialid=" . $row["consign_no"]
	);
}

$sql = get_sql_for_rcon_po_search($_POST['serial_no'], isset($_POST['consign_no']) ? $_POST['consign_no'] : "");

$cols = array(
	_("Item Code"),
	_("Item Description"),
	// _("Color Code"),
	_("Serial No."),	
	_("Trans #") => array('name' => 'trans_no'),
	_("Consignment #") => array(
		'fun' => 'trans_view'
	),
	_("Supplier Ref #"),
	_("Supplier"),
	_("Consignment Date") => array('name' => 'consign_date', 'type' => 'date', 'ord' => 'desc'),
	_("Remarks"),
	_("Status"),
	array('insert' => true, 'fun' => 'view_serial', 'align' => 'center'),
	array('insert' => true, 'fun' => 'copy_from_rcon_to_po_link')
);

$table = &new_db_pager('rcon_po_tbl', $sql, $cols, null, null, 25);

$table->width = "98%";

display_db_pager($table);


end_form();
end_page();
