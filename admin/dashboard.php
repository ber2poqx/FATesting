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
$page_security = 'SA_SETUPDISPLAY'; // A very low access level. The real access level is inside the routines.
$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/reporting/includes/class.graphic.inc");
include_once($path_to_root . "/includes/dashboard.inc"); // here are all the dashboard routines.
include_once($path_to_root . "/includes/db/dashboard_db.inc"); 

$js = "";
if ($SysPrefs->use_popup_windows) {
    $js .= get_js_open_window(800, 500);
}

page(_($help_context = "Dashboard"), false, false, "", $js);

start_form();

if (get_post('trans_type')) {
    $_GET['sel_app'] = get_post('trans_type');
    $Ajax->activate('_page_body');
}

start_table(TABLESTYLE_NOBORDER);
start_row();

value_type_list(_("Application Type: "), 'trans_type', 
    array(
        'ALL' => 'All Transaction Summary',
        'orders' => 'Sales',
        'AP' => 'Purchases',
		'stock' => 'Items and Inventory',
        'GL' => 'Banking and General Ledger'
    ), '', null, true, '', true
);

end_row();
end_table(); 

if (isset($_GET['sel_app'])) {
	dashboard($_GET['sel_app'], get_post('trans_type'));
    end_form();
	end_page();
	exit;
}
