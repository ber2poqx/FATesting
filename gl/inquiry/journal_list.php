<?php

/**
 * Created by: spyrax10
 * Date Created: 21 Mar 2022
 */

$page_security = 'SA_JE_LIST';
$path_to_root = "../..";

include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/admin/db/fiscalyears_db.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/includes/aging.inc");

$js = '';

if ($SysPrefs->use_popup_windows) {
    $js .= get_js_open_window(800, 500);
}
if (user_use_date_picker()) {
    $js .= get_js_date_picker();
}

page(_($help_context = "Journal Entry Inquiry List"), false, false, '', $js);

//---------------------------------------------------------------

function trans_num($row) {
    return $row['trans_no'];
}

function reference_row($row) {
    return $row['reference'];
}

function trans_date($row) {
    return phil_short_date($row['tran_date']);
}

function event_date($row) {
    return phil_short_date($row['event_date']);
}

function doc_date($row) {
    return phil_short_date($row['doc_date']);
}

function doc_ref($row) {

    $debtor_row = get_SI_by_reference($row['source_ref']);

    return $debtor_row["trans_no"] != null ? 
        get_trans_view_str(ST_SALESINVOICE, $debtor_row["trans_no"], $row['source_ref']) 
    : $row['source_ref'];
}

function is_interbranch($row) {
    return has_interbranch_entry($row['trans_no'], ST_JOURNAL) ? "Interbranch Entry" : "Normal Entry";
}

function amount_total($row) {
    return abs($row['amount']);
}

function gl_view($row) {
	return get_gl_view_str(ST_JOURNAL, $row["trans_no"], '', false, '', '', 1);
}

function gl_update($row) {

    $update_link = '';
    $void_entry = get_voided_entry(ST_JOURNAL, $row['trans_no']);

    if ($_SESSION["wa_current_user"]->can_access_page('SA_JOURNALENTRY')) {

        if ($void_entry['void_status'] != 'Voided') {
            $debtor_row = get_SI_by_reference($row['source_ref']);
            $update_link = $debtor_row["trans_no"] == null ? trans_editor_link(ST_JOURNAL, $row["trans_no"]) : "";
        }
    }
    return $update_link;
}

#Added by Prog6 (03/31/2022)
function print_voucher($row) {

    if ($_SESSION["wa_current_user"]->can_access_page('SA_PRINT_JE')) {
        $print_link = pager_link(_("Print: Journal Voucher"),
            "/reports/journal_voucher.php?trans_num=" . $row["trans_no"],
            ICON_PRINT
        );
    }
    else {
        $print_link = '';
    }

	return $print_link;
}

function void_row($row) {
    
    $void_link = '';

    if ($_SESSION["wa_current_user"]->can_access_page('SA_VOIDTRANSACTION')) {

        $void_entry = get_voided_entry(ST_JOURNAL, $row['trans_no']);
        $debtor_row = get_SI_by_reference($row['source_ref']);

        if ($void_entry == null) {
            $void_link = pager_link( _("Request to Void"),
                "/admin/manage/void_draft.php?trans_no=" . $row['trans_no'] . "&type=" . ST_JOURNAL ."&status=0", ICON_DOC
            );
        }
        else if ($void_entry['void_status'] == 'Disapproved') {

            $void_link = pager_link( _("Request to Void"),
                "/admin/manage/void_draft.php?trans_no=" . $row['trans_no'] . "&type=" . ST_JOURNAL ."&status=0", ICON_DOC
            );
        }
        else if (has_interbranch_entry($row['trans_no'], ST_JOURNAL)) {
           
            $comp_id = get_comp_id(has_interbranch_entry($row['trans_no'], ST_JOURNAL));
            $interb_status = bank_interB_stat($comp_id, $row['ref'], ST_JOURNAL);
            
            if ($interb_status == 'Draft' && $void_entry == null) {
                $void_link = pager_link( _("Request to Void"),
                    "/admin/manage/void_draft.php?trans_no=" . $row['trans_no'] . "&type=" . ST_JOURNAL ."&status=0", ICON_DOC
                );
            }
        }
        else if ($debtor_row["trans_no"] != null && $void_entry['void_status'] != "Voided") {
            $inv_row = db_fetch_assoc(get_customer_invoices('', $row['source_ref']));
            $current_bal = current_balance_display(
				$inv_row['trans_no'],
				$inv_row['type'],
				$inv_row['cust_id'],
				date2sql(Today())
			);

            if ($current_bal != 0) {
                $void_link = pager_link( _("Request to Void"),
                    "/admin/manage/void_draft.php?trans_no=" . $row['trans_no'] . "&type=" . ST_JOURNAL ."&status=0", ICON_DOC
                );
            }
        }
	}

	return $void_link;
}

function check_void($row) {
    $void_entry = get_voided_entry(ST_JOURNAL, $row['trans_no']);

    return $void_entry['void_status'] == 'Voided' ? true : false;
}

//---------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);
start_row();

ref_cells(_("Reference #:"), 'reference', '', null, '', true);
ref_cells(_("Source Ref:"), 'doc_ref', '', null, '', true);

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

date_cells(_("From:"), 'from_date', '', null, -user_transaction_days());
date_cells(_("To:"), 'to_date');

submit_cells('btn_search', _("Search"),'',_('Search documents'), 'default');

end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();
ahref_cell(_("Enter New Journal Entry"), "../gl_journal.php?NewJournal=Yes", "ST_JOURNAL");
end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

global $Ajax;
$Ajax->activate('bank_items');

end_row();
end_table();

$sql = get_journal_transactions(
    get_post('reference'), 
    get_post('doc_ref'),
    get_post('from_date'), 
    get_post('to_date'),
    get_post('interbranch')
);

$cols = array(
    _('Entry Type') => array('align' => 'left', 'fun' => 'is_interbranch'),
    _('Trans #') => array('align' => 'left', 'fun' => 'trans_num'),
    _('Reference') => array('align' => 'center', 'fun' => 'reference_row'),
    _('Journal Date') => array('align' => 'center', 'fun' => 'trans_date'),
    _('Source Ref') => array('align' => 'center', 'fun' => 'doc_ref'),
    _('Document Date') => array('align' => 'center', 'fun' => 'doc_date'),
    _('Event Date') => array('align' => 'center', 'fun' => 'event_date'),
    _('Document Total') => array('align' => 'right', 'type' => 'amount', 'fun' => 'amount_total'),
    array('insert' => true, 'fun' => 'gl_update', 'align' => 'center'),
    array('insert' => true, 'fun' => 'gl_view', 'align' => 'center'),
    array('insert' => true, 'fun' => 'void_row', 'align' => 'center'),
	array('insert'=>true, 'fun'=>'print_voucher') //Added by Prog6(03/31/2022)
);

$table = &new_db_pager('bank_items', $sql, $cols, null, null, 25);
$table->set_marker('check_void', _("Marked Rows are Voided"));

$table->width = "80%";

display_db_pager($table);

end_form();
end_page();
