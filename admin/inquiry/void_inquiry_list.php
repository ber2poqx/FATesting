<?php

/**
 * Created by: spyrax10
 * Date Created: 22 Jun 2022     
*/

$page_security = 'SA_VOID_INQ';
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
	
page(_($help_context = "Void Transactions List"), false, false, "", $js);
//---------------------------------------------------------------------------------------------

function systype_name($row) {

	global $systypes_array;
	
	return $systypes_array[$row['type']];
}

function void_status($row) {
    $void_text = $row['cancel'] == 1 && $row["void_status"] == "Voided" ? "Cancelled" : $row["void_status"];
    if ($_SESSION["wa_current_user"]->can_access_page('SA_VOID_APPROVED')) {
        if($row['type'] == 12){
            $status_link = $row["void_status"] == "Draft" ? pager_link(
                $row['void_status'],
                "/admin/manage/void_payments.php?trans_no=" . $row['id'] . "&type=" . $row['type'] ."&status=1&cancel=" . $row['cancel'] 
                . "&void_id=" . $row['void_id'],
                false
            ) : $void_text;
        }else{
            $status_link = $row["void_status"] == "Draft" ? pager_link(
                $row['void_status'],
                "/admin/manage/void_draft.php?trans_no=" . $row['id'] . "&type=" . $row['type'] ."&status=1&cancel=" . $row['cancel'] 
                . "&void_id=" . $row['void_id'],
                false
            ) : $void_text;
        }
	}
	else {
		$status_link = $row['void_status'];
	}
	
	return $status_link;
}

function reference_row($row) {
    return get_trans_view_str($row["type"], $row["id"], $row['reference_from']);
}

function reference_to($row) {

    $banking = $row["type"] == ST_BANKPAYMENT || $row["type"] == ST_BANKDEPOSIT;
    $journal = $row["type"] == ST_JOURNAL;

    if ($row['reference_to'] != '') {
        
        if ($banking) {
            $sql_row = db_fetch_assoc(db_query(get_banking_transactions($row["type"], $row['reference_to'], '', null, null, '', '', '')));
            $trans_no = $sql_row['trans_no'];
        }
        else if ($journal) {
            $sql_row = db_fetch_assoc(db_query(get_journal_transactions($row['reference_to'], '', null, null, '')));
            $trans_no = $sql_row['trans_no'];
        }
        else {
            $trans_no = $row["id"];
        }

        return get_trans_view_str($row["type"], $trans_no, $row['reference_to']);
    }
    else {
        return null;
    }
}

function approved_by($row) {
    return get_user_name($row['approved_by']);
}

function voided_by($row) {
    return get_user_name($row['voided_by']);
}

function get_note($row) {
    return $row['status_note'];
}

function get_memo($row) {
    return $row['memo_'];
}

function date_transact($row, $type) {
    return $row['date_'] != '0000-00-00' ? phil_short_date($row['date_']) : '0000-00-00';
}

function date_approved($row, $type) {
    return $row['date_approved'] != '0000-00-00' ? phil_short_date($row['date_approved']) : '0000-00-00';
}

function date_voided($row, $type) {
    return $row['date_voided'] != '0000-00-00' ? phil_short_date($row['date_voided']) : '0000-00-00';
}

function trans_no($row) {
    return $row['id'];
}

#Added by Prog6 (07/21/2023)
function print_voucher($row)
{
    if($row["void_status"] == "Voided")
    {
        return printable_receipts_and_vouchers($row["type"],  $row["id"], _("Print voided trans"), ICON_PRINT);	
    }    
}

function post_void($row) {
    $post_link = '';
    
    if ($_SESSION["wa_current_user"]->can_access_page('SA_VOIDTRANSACTION')) {

        if ($row['type'] == ST_BANKPAYMENT || $row['type'] == ST_BANKDEPOSIT) {

            $link = $row['type'] == ST_BANKDEPOSIT ? "NewDeposit=Yes" : "NewPayment=Yes";
            
            $post_link = $row['void_status'] == "Approved" ? pager_link( _("Void This Transaction"),
                "/gl/gl_bank.php?$link&void_id=" . $row['void_id'], ICON_DOC) 
		    : null;   
        }
        else if ($row['type'] == ST_JOURNAL)                                                                                                                 {
            $post_link = $row['void_status'] == "Approved" ? pager_link( _("Void This Transaction"),
                "/gl/gl_journal.php?NewJournal=Yes&void_id=" . $row['void_id'], ICON_DOC) 
		    : null;
        }
        else if ($row['type'] == ST_SALESINVOICE) {
            $si_row = get_SI_by_reference($row['reference_from']);

            if ($si_row['opening_balances'] == 1) {
                $post_link = $row['void_status'] == "Approved" ? pager_link( _("Void This Transaction"),
                    "/sales/sales_invoice_opening_balances.php?NewInvoice=0&void_id=" . $row['void_id'], ICON_DOC) 
		        : null;
            }
            else {
                if (isset($si_row['months_term']) && $si_row['months_term'] > 0) {
                    $post_link = $row['void_status'] == "Approved" ? pager_link( _("Void This Transaction"),
                        "/sales/sales_order_entry.php?NewOrder=0&void_id=" . $row['void_id'], ICON_DOC) 
		            : null;
                }
                else {
                    $post_link = $row['void_status'] == "Approved" ? pager_link( _("Void This Transaction"),
                        "/sales/sales_invoice_cash.php?NewOrder=0&void_id=" . $row['void_id'], ICON_DOC) 
                    : null;
                }
            }
        }
	}
	else {
		$post_link = '';
	}

	return $post_link;
}

function gl_view($row) {
	
	if ($_SESSION["wa_current_user"]->can_access_page('SA_GLTRANSVIEW')) {
        if ($row['void_status'] != 'Voided') {
            $gl_link = get_gl_view_str($row['type'], $row["id"]);
        }
        else {
            $gl_link = '';
        }
	}
	else {
		$gl_link = '';
	}

	return $gl_link;
}

//--------------------------------------------------------------------------------------------
-
start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);
start_row();

journal_types_list_cells(_("Transaction Type:"), "filterType", null, true);
ref_cells(_("Reference #:"), 'reference', '', null, '', true);

end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

value_type_list(_("Status:"), 'void_stat', 
    array(
        'ALL' => 'All Void Status',
        'Draft' => 'Draft',
        'Approved' => 'Approved',
		'Disapproved' => 'Disapproved',
        'Voided' => 'Voided',
        1 => 'Cancelled'
    ), '', null, true, '', true
);

date_cells(_("From:"), 'FromDate', '', null, -user_transaction_days());
date_cells(_("&nbsp; To:"), 'ToDate');

submit_cells('Search', _("Search"), '', '', 'default');

end_row();
end_table();


start_table(TABLESTYLE_NOBORDER);
start_row();

global $Ajax;
$Ajax->activate('void_items');

end_row();
end_table(); 

$sql = get_voided_entry(
    get_post('filterType'),
    null,
    get_post('reference'),
    true,
    get_post('void_stat'),
    get_post('FromDate'),
    get_post('ToDate')
);

$cols = array(
    _('ID') => array('align' => 'left', 'name' => 'void_id'),
    _('Transaction Type') => array('align' => 'left', 'fun' => 'systype_name'),
    _('Transaction #') => array('align' => 'left', 'fun' => 'trans_no'),
    _('Voided Reference') => array('align' => 'center', 'fun' => 'reference_row'),
    _('Created Reference') => array('align' => 'center', 'fun' => 'reference_to'),
    _('Transaction Date') => array('align' => 'center', 'fun' => 'date_transact'),
    _('Status') => array('align' => 'center', 'fun' => 'void_status'),
    _('Date Approved') => array('align' => 'center', 'fun' => 'date_approved'),
    _('Approved By') => array('align' => 'center', 'fun' => 'approved_by'),
    _('Note') => array('align' => 'center', 'left', 'fun' => 'get_note'),
    _('Date Voided') => array('align' => 'center', 'fun' => 'date_voided'),
    _('Voided By') => array('align' => 'center', 'fun' => 'voided_by'),
    _('Memo') => array('align' => 'left', 'fun' => 'get_memo'),
    array('insert' => true, 'fun' => 'gl_view', 'align' => 'center'),
    array('insert' => true, 'fun' => 'post_void', 'align' => 'center'),
    array('insert' => true, 'fun' => 'print_voucher', 'align' => 'center')
);

$table = &new_db_pager('void_items', $sql, $cols, null, null, 25);

$table->width = "90%";

display_db_pager($table);


end_form();
//---------------------------------------------------------------------------------------------
end_page();