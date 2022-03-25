<?php

/**
 * Created by: spyrax10
 * Date Created: 25 Mar 2022
 */

$page_security = 'SA_REMIT_INQ';
$path_to_root = "../..";


include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/admin/db/fiscalyears_db.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");

$js = '';

if ($SysPrefs->use_popup_windows) {
    $js .= get_js_open_window(800, 500);
}
if (user_use_date_picker()) {
    $js .= get_js_date_picker();
}

page(_($help_context = "Remittance Entry Inquiry List"), false, false, '', $js);

//---------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);


start_table(TABLESTYLE_NOBORDER);
start_row();

ref_cells(_("Reference #:"), 'reference', '', null, '', true);

date_cells(_("From:"), 'from_date', '', null, -user_transaction_days());
date_cells(_("To:"), 'to_date');

submit_cells('btn_search', _("Search"),'',_('Search documents'), 'default');

end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();
ahref_cell(_("Enter New Remittance Entry"), "../remit_entry.php", "SA_REMIT");
end_row();
end_table();



end_form();
end_page();