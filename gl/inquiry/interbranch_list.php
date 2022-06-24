<?php

/**
 * Added by spyrax10
 */

$page_security = 'SA_INTERB_LIST';
$path_to_root="../..";

include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(800, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

page(_($help_context = "Banking Interbranch Inquiry"), false, false, "", $js);

//-----------------------------------------------------------------------------------
function get_interbranch_list($branch_code = '', $reference = null, $trans_type = null, $status = null,
    $from_date = null, $to_date = null) {

    $sql = "SELECT * 
        FROM " . TB_PREF . "bank_interbranch_trans 
        WHERE IFNULL(sug_mcode, '') <> '' ";
   
    if ($branch_code != '') {
        $sql .= " AND branch_code_from =" .db_escape($branch_code);
    }

    if ($reference != null) {
        $sql .= " AND ref_no =" .db_escape($reference);
    }

    if ($trans_type != '') {
        $sql .= " AND trantype_from_branch = " .db_escape($trans_type);
    }

    if ($status != null) {
        
        $status = $status == "Reviewed" ? "Approved" : $status;
        $sql .= " AND status = " .db_escape($status);
    }
    
    $sql .= " AND branch_code_to = " .db_escape(getCompDet('branch_code'));

    $sql .= " AND trans_date >= '" . date2sql($from_date) . "' 
		AND trans_date <= '" . date2sql($to_date) . "'";

    $sql .= " GROUP BY transno_from_branch, trantype_from_branch, trans_date";
    $sql .= " ORDER BY trans_date DESC, id DESC";

	set_global_connection();
	return $sql;
}

function _interbranch_total($row) {

    $sql = '';
    $coy = user_company();

    if ($row['trantype_from_branch'] == ST_JOURNAL) {
        $sql = "SELECT SUM(amount) FROM " . TB_PREF . "journal 
        WHERE reference = " .db_escape($row['ref_no']);

        $db_coy = Get_db_coy($row['branch_code_from']);
    }
    else {

        $sql = "SELECT SUM(amount) 
        FROM " . TB_PREF . "bank_interbranch_trans 
        WHERE amount > 0 AND transno_from_branch = ".db_escape($row['transno_from_branch']) . " 
            AND trantype_from_branch = " .db_escape($row['trantype_from_branch']);

        $sql .= "GROUP BY transno_from_branch, trantype_from_branch, trans_date";

        $db_coy = Get_db_coy(get_company_value($coy, 'branch_code'));
    }

    set_global_connection($db_coy);
    $result = db_query($sql, "Cant get gl Total! (spyrax10)");
	$row = db_fetch_row($result);
	
    return $row[0] != null ? $row[0] : 0;
}

//-----------------------------------------------------------------------------------
global $Ajax;

if (get_post('comp_id')) {
    $Ajax->activate('bank_items');
}

if (get_post('Search')) {
    $Ajax->activate('bank_items');
}

if (get_post('status')) {
    $Ajax->activate('bank_items');
}

if (get_post('filterType')) {
    $Ajax->activate('bank_items');
}
//-----------------------------------------------------------------------------------

function status_link($row)
{
	global $page_nested;

    $status_text = $row["status"] == "Approved" ? "Reviewed" : $row["status"];

	return $row["status"] == "Draft" ? pager_link(
		$row['status'],
		"/gl/manage/gl_draft.php?trans_no=" . $row['transno_from_branch'] . 
            "&type=" . $row['trantype_from_branch'] .
            "&status=0",
		false
	) : $status_text;
}

function systype_name($row)
{
	global $systypes_array;
	
	return $systypes_array[$row['trantype_from_branch']];
}

function gl_view($row)
{
	return $row['status'] == "Closed" ? get_gl_view_str($row['trantype_to_branch'], $row["transno_to_branch"], '', false, '', '', 1) : null;
}

function post_gl($row) {
	return $row['status'] == "Approved" ? pager_link( _("Post Transaction Ref: " . $row['ref_no']),
	"/gl/manage/gl_draft.php?trans_no=" . $row["transno_from_branch"] . 
        "&type=" . $row['trantype_from_branch'] .
        "&status=1", ICON_DOC) : null;
}

function trans_num($row) {
    return $row['transno_from_branch'];
}

function trans_date($row) {
    return phil_short_date($row['trans_date']);
}

function origin_branch($row) {
    return get_db_location_name($row['branch_code_from']);
}

function reference_row($row) {
    
    return $row['ref_no'];
}

function preparer_row($row) {
    return $row['prepared_by'];
}

function approved_date($row) {
    return phil_short_date($row['approved_date']);
}

function approver($row) {
    return $row['approved_by'];
}

function post_date($row) {
    return phil_short_date($row['post_date']);
}

//-----------------------------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);
start_row();

company_list_row(_('Originating Branch: '), 'comp_id', true, true, false);
ref_cells(_("Reference:"), 'Ref', '',null, _('Enter reference fragment or leave empty'));
value_type_list('Status: ', 'status', array('Reviewed', 'Disaaproved', 'Draft', 'Closed'), '', null, true, 
    _('All Status Types')
);

end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

value_type_list(_("Originating Type:"), 'filterType', 
    array(
        0 => 'Journal Entry', 
        1 => 'Disbursement Entry', 
        2 => 'Receipt Entry'
    ), '', null, true, 
    _('All Originating Types'), true
);
date_cells(_("From:"), 'FromDate', '', null, -user_transaction_days());
date_cells(_("To:"), 'ToDate');
submit_cells('Search', _("Search"), '', '', 'default');

end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

global $Ajax;
$Ajax->activate('bank_items');

end_row();
end_table(); 

$sql = 
get_interbranch_list( 
    get_post('comp_id') != null ? get_company_value(get_post('comp_id'), 'branch_code') : '',
    get_post('Ref'),
    get_post('filterType'),
    get_post('status'),
    get_post('FromDate'),
    get_post('ToDate')
);

$cols = array(
    _('Originating Type') => array('align' => 'left', 'fun' => 'systype_name'),
    _('Trans #') => array('align' => 'left', 'fun' => 'trans_num'),
    _('Status') => array('fun' => 'status_link'),
    _('Date') => array('align' => 'center', 'fun' => 'trans_date'),
    _('Origin Branch') => array('align' => 'left', 'fun' => 'origin_branch', "align" => 'center'),
    _('Reference') => array('align' => 'center', 'fun' => 'reference_row'),
    _('Prepared By') => array('align' => 'left', 'fun' => 'preparer_row'),
    _('Reviewed Date') => array('align' => 'center', 'fun' => 'approved_date'),
    _('Reviewed By') => array('align' => 'left', 'fun' => 'approver'),
    _('Posting Date') => array('align' => 'center', 'fun' => 'post_date'),
    _('Document Amount') => array('align' => 'right', 'type' => 'amount', 'fun' => '_interbranch_total'),
    array('insert' => true, 'fun' => 'post_gl', 'align' => 'center'),
    array('insert' => true, 'fun' => 'gl_view', 'align' => 'center')
);

$table = &new_db_pager('bank_items', $sql, $cols, null, null, 25);

$table->width = "98%";

display_db_pager($table);

end_form();
end_page();
