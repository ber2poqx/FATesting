<?php

/**
 * Journal Entry Update
 * Added by: spyrax10
 * Date Added: 11 Jun 2022
*/

$page_security = 'SA_JE_UPDATE';
$path_to_root = "../..";

include_once($path_to_root . "/includes/ui/items_cart.inc");

include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/gl/includes/ui/gl_journal_ui.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/includes/aging.inc");

$js = '';
if ($SysPrefs->use_popup_windows) {
	$js .= get_js_open_window(800, 500);
}
if (user_use_date_picker()) {
	$js .= get_js_date_picker();
}

if (isset($_GET['trans_no'])) { 
	$_SESSION['page_title'] = sprintf(_("Modifying Journal Transaction # %d"), $_GET['trans_no']);
	$help_context = "Modifying Journal Entry";
}

page($_SESSION['page_title'], false, false,'', $js);
//---------------------------------------------------------------------------------------------

function get_JE_transactions($trans_no, $header = false) {
    
    set_global_connection();

    $sql = $header ? "SELECT JE.*" : 
        "SELECT JE.*, GL.*, GL.amount AS line_amount";
    
    $sql .= " FROM " . TB_PREF . "journal JE";

    if (!$header) {
        $sql .= " INNER JOIN " . TB_PREF . "gl_trans GL ON JE.trans_no = GL.type_no 
            AND GL.type = " .db_escape(ST_JOURNAL);
    }

    $sql .= " WHERE JE.trans_no = " .db_escape($trans_no);

    $result = db_query($sql, "get_JE_header()");

    if ($header) {
        return db_fetch($result);
    }
    else {
        return $result;
    }
}

function new_cart($trans_no) {

    $mcode = $masterfile = '';
    $id = -1;

    $head_row = get_JE_transactions($trans_no, true);
    $details = get_JE_transactions($trans_no);

    if (isset($_SESSION['journal_items'])) {
        unset ($_SESSION['journal_items']);
    }

    $cart = new items_cart(ST_JOURNAL);
    $_SESSION['journal_items'] = &$cart;

    while ($row = db_fetch($details)) {
        $id++;
        hidden('Index', $id);
        $comp_id = $row['interbranch'] == 0 ? user_company() : get_comp_id($row['mcode']);
        $mcode = $row['mcode'];

        if ($row['interbranch'] == 1) {
            $masterfile = get_company_value($comp_id, 'name');
        }
        else {
            $masterfile = get_slname_by_ref($row['mcode']);
        }

        $_SESSION['journal_items']->add_gl_item(
            $row['account'], 0, 0, 
	        $row['line_amount'], 
	        $row['memo_'], 
	        null, 
	        null,
	        null,
		    $mcode,
		    $masterfile,
		    $row['hocbc_id'], 
		    $comp_id, ''
        );
    }

}

function display_JE_header($trans_no) {

    $row = get_JE_transactions($trans_no, true);

    div_start('item_head');
	start_outer_table(TABLESTYLE2, "width='50%'");
    
    table_section(1);
    label_row('Journal Date: &nbsp;', phil_short_date($row['tran_date']));
    date_row(_("Set New Journal Date: &nbsp;"), 'new_je_date', '', true);

    table_section(2);
    label_row('JE Reference: &nbsp;', $row['reference']);
    text_row('Source Reference: &nbsp;', 'source_ref', $row['source_ref'], 30, 30);

    end_outer_table(1);
	div_end();
}

//---------------------------------------------------------------------------------------------

if (isset($_POST['CancelItemChanges'])) {
	global $Ajax;
    unset($_POST['Index']);
    $Ajax->activate('items_table');
}

if (isset($_POST['Process'])) {
    display_error(_("Under Construction! Please Wait..."));
}

//---------------------------------------------------------------------------------------------
start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

if (isset($_GET['trans_no']) && JE_exists($_GET['trans_no'])) {
    display_JE_header($_GET['trans_no']);
    new_cart($_GET['trans_no']);
    display_gl_items(_("Journal Entry Rows:"), $_SESSION['journal_items'], false);

    br(2);
    submit_center('Process', _("Update Journal Entry"), true , null, 'default');
    br();
}
else {
    display_error(_("Cannot find this Journal Entry Transaction!"));
    display_footer_exit();
}

end_form();
end_page();

