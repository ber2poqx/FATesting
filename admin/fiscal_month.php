<?php

/**
 * Added by: spyrax10
 * Date Added: 29 Jun 2022 
*/

$page_security = 'SA_FISCALYEARS';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/admin/db/company_db.inc");
include_once($path_to_root . "/admin/db/fiscalyears_db.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/sales/includes/db/cust_trans_db.inc");
include_once($path_to_root . "/admin/db/maintenance_db.inc");

$js = "";

if (user_use_date_picker()) {
    $js .= get_js_date_picker();
}

page(_($help_context = "Monthly Posting Period"), false, false, "", $js);

simple_page_mode(true);

//---------------------------------------------------------------------------------------------