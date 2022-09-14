<?php

/**
 * Created by: spyrax10
 * Date Created: 21 Mar 2022
 */

$page_security = 'SA_RECEIPT_LIST';
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

page(_($help_context = "Receipt Entry Inquiry List"), false, false, '', $js);

//---------------------------------------------------------------

function trans_num($row) {
    return $row['trans_no'];
}

function reference_row($row) {
    return get_trans_view_str($row["type"], $row["trans_no"], $row['ref']);
}

function trans_date($row) {
    return phil_short_date($row['trans_date']);
}

function cashier_name($row) {
    return get_user_name($row['cashier_user_id']);
}

function preparer_name($row) {
    return $row['prepared_by'];
}

function pay_type($row) {
    return strtoupper($row['pay_type']);
}

function pay_to($row) {
    $person_type = get_person_type($row['person_type_id'], false);
    return $person_type . get_person_name($row['person_type_id'], $row['person_id']);
}

function is_interbranch($row) {
    return has_interbranch_entry($row['trans_no'], ST_BANKDEPOSIT) ? "Interbranch Entry" : "Normal Entry";
}

function doc_ref($row) {
    return $row['receipt_no'];
}

function amount_total($row) {
    return abs($row['amount']);
}

function gl_view($row) {

    if ($_SESSION["wa_current_user"]->can_access_page('SA_GLTRANSVIEW')) {

        $gl_link = get_gl_view_str(ST_BANKDEPOSIT, $row["trans_no"], '', false, '', '', 1);
    }
    else {
        $gl_link = '';
    }

    return $gl_link;
}

function cancel_row($row) {
    $cancel_link = '';

    if ($_SESSION["wa_current_user"]->can_access_page('SA_VOIDTRANSACTION')) {
        $void_entry = get_voided_entry(ST_BANKDEPOSIT, $row['trans_no']);

        if ($void_entry == null) {
            $cancel_link = pager_link( _("Request to Cancel"),
                "/admin/manage/void_draft.php?trans_no=" . $row['trans_no'] . "&type=" . ST_BANKDEPOSIT ."&status=0&cancel=1", ICON_CANCEL
            );
        }
        else if ($void_entry['void_status'] == 'Disapproved') {

            $cancel_link = pager_link( _("Request to Cancel"),
                "/admin/manage/void_draft.php?trans_no=" . $row['trans_no'] . "&type=" . ST_BANKDEPOSIT ."&status=0&cancel=1", ICON_CANCEL
            );
        }
        else if (has_interbranch_entry($row['trans_no'], ST_BANKDEPOSIT)) {
           
            $comp_id = get_comp_id(has_interbranch_entry($row['trans_no'], ST_BANKDEPOSIT));
            $interb_status = bank_interB_stat($comp_id, $row['ref'], ST_BANKDEPOSIT);
            
            if ($interb_status == 'Draft' && $void_entry == null) {
                $cancel_link = pager_link( _("Request to Cancel"),
                    "/admin/manage/void_draft.php?trans_no=" . $row['trans_no'] . "&type=" . ST_BANKDEPOSIT ."&status=0&cancel=1", ICON_CANCEL
                );
            }
        }

    }
    else {
		$cancel_link = '';
	}

    return $cancel_link;
}


function void_row($row) {
    
    $void_link = '';

    if ($_SESSION["wa_current_user"]->can_access_page('SA_VOIDTRANSACTION')) {

        $void_entry = get_voided_entry(ST_BANKDEPOSIT, $row['trans_no']);

        if ($void_entry == null) {
            $void_link = pager_link( _("Request to Void"),
                "/admin/manage/void_draft.php?trans_no=" . $row['trans_no'] . "&type=" . ST_BANKDEPOSIT ."&status=0&cancel=0", ICON_DOC
            );
        }
        else if ($void_entry['void_status'] == 'Disapproved') {

            $void_link = pager_link( _("Request to Void"),
                "/admin/manage/void_draft.php?trans_no=" . $row['trans_no'] . "&type=" . ST_BANKDEPOSIT ."&status=0&cancel=0", ICON_DOC
            );
        }
        else if (has_interbranch_entry($row['trans_no'], ST_BANKDEPOSIT)) {
           
            $comp_id = get_comp_id(has_interbranch_entry($row['trans_no'], ST_BANKDEPOSIT));
            $interb_status = bank_interB_stat($comp_id, $row['ref'], ST_BANKDEPOSIT);
            
            if ($interb_status == 'Draft' && $void_entry == null) {
                $void_link = pager_link( _("Request to Void"),
                    "/admin/manage/void_draft.php?trans_no=" . $row['trans_no'] . "&type=" . ST_BANKDEPOSIT ."&status=0&cancel=0", ICON_DOC
                );
            }
        }
	}
	else {
		$void_link = '';
	}

	return $void_link;
}

function check_void($row) {
    $void_entry = get_voided_entry(ST_BANKDEPOSIT, $row['trans_no']);

    return $void_entry['void_status'] == 'Voided' ? true : false;
}

if (get_post('mcode')) {
    $Ajax->activate('bank_items');
}

//---------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);
start_row();

ref_cells(_("Reference #:"), 'reference', '', null, '', true);
ref_cells(_("Receipt #:"), 'doc_ref', '', null, '', true);

sql_type_list(_("Cashier / Teller: "), 'cashier', 
	get_dcpr_users(), 
	'cashier_user_id', 'real_name', '', null, true, 
	_("All Cashiers")
);

value_type_list(_("Entry Type: "), 'interbranch', 
    array(
        1 => 'Interbranch Entry', 
        0 => 'Normal Entry' 
    ), '', null, true, _('All Entry Types'), true
);

end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

sl_list_gl_cells(_("Pay To: "), 'mcode', null, _("All Recipients"), true);
hidden('class_name');

date_cells(_("From:"), 'from_date', '', null, -user_transaction_days());
date_cells(_("To:"), 'to_date');

check_cells(_('&nbsp; Show Opening Balance Entries:'), 'open_bal', null, true);

submit_cells('btn_search', _("Search"),'',_('Search documents'), 'default');

end_row();
end_table();

if ($_SESSION["wa_current_user"]->can_access_page('SA_DEPOSIT')) {
    start_table(TABLESTYLE_NOBORDER);
    start_row();
    ahref_cell(_("Enter New Receipts Entry"), "../gl_bank.php?NewDeposit=Yes", "SA_DEPOSIT");
    end_row();
    end_table();
}

start_table(TABLESTYLE_NOBORDER);
start_row();

global $Ajax;
$Ajax->activate('bank_items');

end_row();
end_table(); 

$sql = get_banking_transactions(ST_BANKDEPOSIT, 
    get_post('reference'), 
    get_post('doc_ref'),
    get_post('from_date'), 
    get_post('to_date'),
    get_post('cashier'),
    get_post('interbranch'),
    get_post('open_bal'),
    0,
    get_post('mcode'),
    get_post('class_name')
);

$cols = array(
    _('Entry Type') => array('align' => 'left', 'fun' => 'is_interbranch'),
    _('Trans #') => array('align' => 'left', 'fun' => 'trans_num'),
    _('Reference') => array('align' => 'center', 'fun' => 'reference_row'),
    _('Date') => array('align' => 'center', 'fun' => 'trans_date'),
    _("Pay To ") => array('align' => 'left', 'fun' => 'pay_to'),
    _('Receipt #') => array('align' => 'center', 'fun' => 'doc_ref'),
    _('Cashier / Teller') => array('align' => 'center', 'fun' => 'cashier_name'),
    _('Prepared By') => array('align' => 'center', 'fun' => 'preparer_name'),
    _('Payment Type') => array('align' => 'center', 'fun' => 'pay_type'),
    _('Document Total') => array('align' => 'right', 'type' => 'amount', 'fun' => 'amount_total'),
    array('insert' => true, 'fun' => 'gl_view', 'align' => 'center'),
    array('insert' => true, 'fun' => 'cancel_row', 'align' => 'center'),
    array('insert' => true, 'fun' => 'void_row', 'align' => 'center')
);

$table = &new_db_pager('bank_items', $sql, $cols, null, null, 25);
$table->set_marker('check_void', _("Marked Rows are Voided"));

$table->width = "95%";

display_db_pager($table);

end_form();
end_page();