<?php

/**
 * Added by: spyrax10
 * Date Added: 16 Sep 2022
*/

$page_security = "SA_SEARCHTRANS";
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/gl/includes/db/gl_db_banking.inc");
include_once($path_to_root . "/admin/db/transactions_db.inc");
include_once($path_to_root . "/admin/db/voiding_db.inc");


$mode = get_company_pref('no_sl_list');
if ($mode != 0) {
    $js = get_js_set_combo_item();
}
else {
    $js = get_js_select_combo_item();
}

page(_($help_context = "List of Transaction References"), true, false, "", $js);
#--------------------------------------------------------------------------------
function get_all_transactions() {

    $sql = "";

    return db_query($sql, "get_all_transactions()");
}
#--------------------------------------------------------------------------------
start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);



end_form();
end_page(true);
