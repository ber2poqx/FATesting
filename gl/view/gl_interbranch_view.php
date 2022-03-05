<?php

/**
 * Created by: spyrax10
 * Date Created: 18 Feb 2022
 */

$page_security = 'SA_BANKTRANSVIEW';
$path_to_root = "../..";

include($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(1200, 900);
if (user_use_date_picker())
	$js .= get_js_date_picker();
    
page(_($help_context = "View Interbranch Transaction"), true, false, "", $js);

//-----------------------------------------------------------------------------------

display_gl_draft($_GET["trans_no"], $_GET["type"], _("Pending Interbranch Transaction"), $_GET['ref']);

end_page(true, false, false, $_GET["type"], $_GET["trans_no"]);


