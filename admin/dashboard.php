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

$begin_fiscal = phil_short_date(begin_fiscalyear());
$end_fiscal = phil_short_date(end_fiscalyear());

page(_($help_context = "Dashboard"), false, false, "", $js);

start_form();

if (get_post('trans_type')) {
    $_GET['sel_app'] = get_post('trans_type');
    $Ajax->activate('_page_body');
}
else {
    if (get_post('category_id') || 
        get_post('sales_grp') || 
        get_post('invty_grp')
    ) {
        $Ajax->activate('_page_body');
    }
}

br();

if ($_GET['sel_app'] == "ALL" || get_post('trans_type') == null) {

    if (user_company() != 0) {
        start_table(TABLESTYLE_NOBORDER, "width = 23%");

        value_type_list(_("Application Type: "), 'trans_type', 
            array(
                'ALL' => 'All Transaction Summary',
                'orders' => 'Sales',
                'AP' => 'Purchases',
                'stock' => 'Items and Inventory',
                'GL' => 'Banking and General Ledger'
            ), '', null, true, '', true
        );
    
        end_table();
    }
}
else {

    start_outer_table(TABLESTYLE2, "width = '45%'", 10);

    table_section(1);

    value_type_list(_("Application Type:"), 'trans_type', 
        array(
            'ALL' => 'All Transaction Summary',
            'orders' => 'Sales',
            'AP' => 'Purchases',
		    'stock' => 'Items and Inventory',
            'GL' => 'Banking and General Ledger'
        ), '', null, true, '', true
    );

    if ($_GET['sel_app'] == 'stock') {
    
        table_section(2);

        value_type_list(_("Group By: "), 'invty_grp', 
            array(
               1 => 'Brand',
               2 => 'Item',
               3 => 'Category'
            ), '', null, true, '', true, true
        );

        if (get_post('invty_grp') != 3) {
            sql_type_list(_("Select Category: "), 'category_id', 
                get_category_list(), 'category_id', 'description', 
                '', null, true, _("All Category")
            );
        }
    }
    else if ($_GET['sel_app'] == 'orders') {

        table_section(2);

        value_type_list(_("Group By: "), 'sales_grp', 
            array(
               1 => 'Customer',
               2 => 'Area',
               3 => 'Collector'
            ), '', null, true, '', true
        );
    }

    end_outer_table(1);
}

if (isset($_GET['sel_app'])) {
	
    dashboard(
        user_company() == 0 ? "AP" : $_GET['sel_app'], 
        user_company() == 0 ? "AP" : get_post('trans_type'), 
        get_post('category_id'), 
        get_post('sales_grp'),
        get_post('invty_grp')
    );
    
    display_note(_("Current Fiscal Year: ") . $begin_fiscal . " - " . $end_fiscal, 1, 0, "class='currentfg'");

    end_form();
	end_page();
	exit;
}
