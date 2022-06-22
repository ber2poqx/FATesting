<?php

/**
 * Created by: spyrax10
 * Date Created: 22 Jun 2022     
*/

$page_security = 'SA_VOID_APPROVED';
$path_to_root = "../..";

include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/admin/db/transactions_db.inc");

include_once($path_to_root . "/admin/db/voiding_db.inc");

$js = "";

if (user_use_date_picker()) {
    $js .= get_js_date_picker();
}
if ($SysPrefs->use_popup_windows) {
    $js .= get_js_open_window(800, 500);
}

if ($_GET['status'] == 1) {
    $header_text = _("Pending");
} 
else if ($_GET['status'] == 2) {
    $header_text = _("Proceed To");
}
else {
    $header_text = _("Create");
}

page(_($help_context = $header_text ." Void Transaction # " . $_GET['trans_no']), false, false, "", $js);
//---------------------------------------------------------------------------------------------


	
//---------------------------------------------------------------------------------------------
end_page();