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
    $js .= get_js_open_window(1100, 500);
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
    if ($_SESSION["wa_current_user"]->can_access_page('SA_REMIT_VIEW')) {
        return get_trans_view_str(ST_REMITTANCE, $row["remit_num"], $row['remit_ref'], false, '', '', false, $row['remit_ref']);
    }
    else {
        return null;
    }
}

function ref_date($row) {
    return phil_short_date($row['remit_date']);
}

function cashier_name($row) {
    return get_user_name($row['remit_from']);
}

function remit_to($row) {
    return get_user_name($row['remit_to']);
}

function remit_stat($row) {
    if ($_SESSION["wa_current_user"]->can_access_page('SA_REMIT')) {
        return $row['remit_stat'] == "Draft"
            && $row['remit_to'] == $_SESSION["wa_current_user"]->user ? pager_link($row['remit_stat'],
            "/gl/manage/remit_draft.php?trans_no=" . $row['remit_num'] . 
            "&reference=" . $row['remit_ref'] .
            "&status=0", false
        ) : $row['remit_stat'];
    }
    else {
        return $row['remit_stat'];
    }
}

function amount_total($row) {
    return $row['tot_amount'];
} 

function gl_view($row) {
    if ($_SESSION["wa_current_user"]->can_access_page('SA_GLTRANSVIEW')) {
        return $row['remit_stat'] == 'Approved' ? 
            get_gl_view_str(ST_REMITTANCE, $row["remit_num"], '', false, '', '', 1) 
        : null;
    }
    else {
        return null;
    }
	
}

//---------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);


start_table(TABLESTYLE_NOBORDER);
start_row();

ref_cells(_("Reference #: "), 'reference', '', null, '', true);

sql_type_list(_("&nbsp; Remittance From:"), 'cashier_', 
	allowed_dcpr_users(), 'id', 'real_name', 
	'', null, true, 'Select Cashier'
);

end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

date_cells(_("From:"), 'from_date', '', null, -user_transaction_days());
date_cells(_("&nbsp; To:"), 'to_date');

submit_cells('btn_search', _("Search"),'',_('Search documents'), 'default');

end_row();
end_table();

if ($_SESSION["wa_current_user"]->can_access_page('SA_REMIT')) {
    start_table(TABLESTYLE_NOBORDER);
    start_row();
    ahref_cell(_("Enter New Remittance Entry"), "../remit_entry.php", "SA_REMIT");
    end_row();
    end_table();
}

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
    _('Status') => array('align' => 'center', 'fun' => 'remit_stat'),
    _('Remittance Date') => array('align' => 'center', 'fun' => 'ref_date'),
    _('Remittance From') => array('align' => 'center', 'fun' => 'cashier_name'),
    _('Remitted To') => array('align' => 'center', 'fun' => 'remit_to'),
    _('Document Total') => array('align' => 'right', 'type' => 'amount', 'fun' => 'amount_total'),
    array('insert' => true, 'fun' => 'gl_view', 'align' => 'center')
);

$table = &new_db_pager('remit_items', $sql, $cols, null, null, 25);

$table->width = "60%";

display_db_pager($table);


end_form();
end_page();