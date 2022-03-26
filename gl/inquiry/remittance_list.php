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

function trans_num($row) {
    return $row['remit_num'];
}

function remit_ref($row) {
    return get_trans_view_str(ST_REMITTANCE, $row["remit_num"], $row['remit_ref']);
}

function ref_date($row) {
    return sql2date($row['remit_date']);
}

function cashier_name($row) {
    return get_user_name($row['remit_from']);
}

function amount_total($row) {
    return $row['tot_amount'];
} 

//---------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);


start_table(TABLESTYLE_NOBORDER);
start_row();

ref_cells(_("Reference #:"), 'reference', '', null, '', true);

sql_type_list(_("Remittance From:"), 'cashier_', 
	allowed_dcpr_users(), 'id', 'real_name', 
	'', null, true, 'Select Cashier'
);

end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

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

start_table(TABLESTYLE_NOBORDER);
start_row();

global $Ajax;
$Ajax->activate('remit_items');

end_row();
end_table(); 

$sql = get_remit_transactions(
    get_post('reference'),
    get_post('cashier_'),
    get_post('from_date'), 
    get_post('to_date')
);

$cols = array(
    _('Trans #') => array('align' => 'left', 'fun' => 'trans_num'),
    _('Reference') => array('align' => 'center', 'fun' => 'remit_ref'),
    _('Remittance Date') => array('align' => 'center', 'fun' => 'ref_date'),
    _('Remittance From') => array('align' => 'center', 'fun' => 'cashier_name'),
    _('Document Total') => array('align' => 'right', 'type' => 'amount', 'fun' => 'amount_total')
);

$table = &new_db_pager('remit_items', $sql, $cols, null, null, 25);

$table->width = "60%";

display_db_pager($table);


end_form();
end_page();