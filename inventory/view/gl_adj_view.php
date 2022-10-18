<?php
/**
 * Added by: spyrax10 
 */

$page_security = 'SA_INVTY_ADJ_ENTRY';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/inventory/includes/inventory_db.inc");
include_once($path_to_root . "/includes/db/manufacturing_db.inc");

simple_page_mode(true);

$js = "";
if ($SysPrefs->use_popup_windows)
    $js .= get_js_open_window(900, 1200);
if (user_use_date_picker())
    $js .= get_js_date_picker();

$page_tile = is_adj_repo($_GET['trans_no']) ? "Adjustment - Repo " : "Adjustment";
page(_($help_context = "GL Entry for " . $page_tile . " Line #:" . $_GET['line_no']), true, false, "", $js);


//----------------------------------------------------------------------------------------------------

function display_menu() {
    
    echo "<hr></center>";

    display_adjGL_head($_GET['trans_no'], $_GET['line_no']);
    echo "<br>";
    display_adjGL_details($_GET['trans_no'], $_GET['line_no']);

    echo "<br>";
    //echo "<hr></center>";
}

//----------------------------------------------------------------------------------------------------

function get_adjGL_head($trans_no, $line_no) {

    set_global_connection();

    $sql = "SELECT A.*
        FROM " . TB_PREF . "stock_adjustment_gl A 
        WHERE A.gl_type = 'DEFAULT' AND A.sa_trans_no = " .db_escape($trans_no). " AND A.sa_line_id = " .db_escape($line_no);
    
    $sql .= " GROUP BY A.sa_line_id";

    return db_query($sql, "No Items return for stock_adjustments GL Head! (spyrax10)");
}

function _adjGL_head_item($trans_no, $line_no) {

    set_global_connection();

    $sql = "SELECT A.*
        FROM " . TB_PREF . "stock_adjustment_gl A 
        WHERE A.gl_type = 'DEFAULT' AND A.sa_trans_no = " .db_escape($trans_no). " AND A.sa_line_id = " .db_escape($line_no);
    
    $sql .= " GROUP BY A.sa_line_id";

    $result = db_query($sql, "No Items return for stock_adjustments GL Head! (spyrax10)");
    return db_fetch($result);
}

function get_adjGL_val($trans_id = 0) {
    set_global_connection();

	$sql = "SELECT A.*
        FROM " . TB_PREF . "stock_adjustment_gl A 
        WHERE A.id = " .db_escape($trans_id);

    $result = db_query($sql, "No Items return for stock_adjustments GL Items! (spyrax10)");
    return db_fetch($result);
}

function delete_adjustmentGL($trans_id) {
    global $Ajax;
    set_global_connection();

    $sql = "DELETE FROM ".TB_PREF."stock_adjustment_gl 
        WHERE id = '$trans_id'";

    db_query($sql, "Cannot delete stock_adjustment GL! (spyrax10)");
    $Ajax->activate('_page_body');
    display_notification(_("Transaction ID #" . $trans_id . " sucessfully deleted!"));
}

function update_adjustmetGL($trans_id, $account, $amount, $mcode, $masterfile) {
    global $Ajax;
    set_global_connection();

    $sql = "UPDATE ".TB_PREF."stock_adjustment_gl 
            SET account = '$account', amount = '$amount', mcode = '$mcode', master_file = '$masterfile' 
        WHERE id = ".db_escape($trans_id);

    db_query($sql, "Cannot update stock_adjustment GL! (spyrax10)");

    $Ajax->activate('_page_body');
    display_notification(_("Transaction ID #" . $trans_id . " sucessfully updated!"));
}

//----------------------------------------------------------------------------------------------------

function display_adjGL_head($trans_no, $line_no) {

    $page_tile = is_adj_repo($trans_no) ? "Adjustment - Repo Details" : "Adjustment Details";

    display_heading($page_tile);
    div_start('gl_head');
    start_table(TABLESTYLE, "width='95%'");

    $result = get_adjGL_head($trans_no, $line_no);

    $th = array(
        _("Reference"),
        _("Item Code"),
        _("Description"),
        _("Color Code"),
        _("Serial / Engine #"),
        _("Chassis #")
    );

    table_header($th);

    $total = 0;
    $k = 0;

    while ($row = db_fetch($result)) {
        alt_table_row_color($k);

        label_cell($row['sa_reference']);
        label_cell($row['stock_id']);
        label_cell(get_stock_description($row['stock_id']));
        label_cell($row['color_code']);
        label_cell($row['lot_no']);
        label_cell($row['chassis_no']);
        
        start_row();
        label_cells(_('Adjustment Type: '), $row['sa_adj_type'] == 2 ? "Inventory Out" : "Inventory In", 
        "class='tableheader2'", "colspan='3'");
        label_cells(_('Item Type: '), strtoupper($row['sa_adj_item']), "class='tableheader2'");
        end_row();

    }

    end_table();
    div_end();
}

function display_adjGL_details($trans_no, $line_no) {

    display_heading("General Ledger Details");
    div_start("gl_details");
    echo "<br>";

    start_table(TABLESTYLE, "width='95%'");

    $result = get_adjGL_details($trans_no, $line_no);

    $th = array(
            _("ID"),
            _("Account Code"),
            _("Account Name"),
            _("MCODE"),
            _("Masterfile"),
            _("Debit"),
            _("Credit")
    );

    table_header($th);

    $total = 0;
    $k = $debit_tot = $credit_tot = 0;

    while ($row = db_fetch($result)) {
        alt_table_row_color($k);

        $debit_row = $row['amount'] > 0 ? $row['amount'] : 0;
        $credit_row = $row['amount'] < 0 ? $row['amount'] : 0;
        $debit_tot += $debit_row;
        $credit_tot += $credit_row;

        label_cell($row['id'], "", 'gl_Id');
        label_cell($row['account'], "", 'gl_account');
        label_cell(get_gl_account_name($row['account']));
        label_cell($row['mcode'], "align='center'");
        label_cell($row['master_file']);
        label_cell(price_format(abs($debit_row)), "align='right'");
        label_cell(price_format(abs($credit_row)), "align='right'");

        if ($_GET['editable'] == 1) {
            if ($row['gl_type'] == "DEFAULT") {
                submit_cells("AddGL".$row['id'], _("Add Entry"), "colspan=2", _('Add New GL Entry'), true);
                edit_button_cell("EditGL".$row['id'], _("Edit"), _('Edit GL line')); 
            }
            else {
                edit_button_cell("EditGL".$row['id'], _("Edit"), _('Edit GL line'));
                delete_button_cell("DeleteGL".$row['id'], _("Delete"), _('Remove line from document'));
            }
        }
    }

    start_row("class='inquirybg' style='font-weight:bold'");
    label_cell(_("Total"), "colspan=5");
    label_cell(price_format(abs($debit_tot)), "align='right'", 'debit_tot');
    label_cell(price_format(abs($credit_tot)), "align='right'", 'credit_tot');
    end_row();

    $_POST['debit_tot'] = abs($debit_tot);
    $_POST['credit_tot'] = abs($credit_tot);
    $_POST['gl_Id'] = $row['id'];
    
    end_table();
    div_end();
}

function display_add_gl($trans_id) {

    display_heading(_('Adding GL Entry for Line ID #' . $trans_id));
    echo "<br>";
    div_start("add_gl");
    start_table(TABLESTYLE2);

    $row = get_adjGL_val($trans_id);

    gl_all_accounts_list_row(_("Account:"), 'account_id', null, false, false, _("Default Account"), true);

    sl_list_gl_row(_("Send to: "), 'mcode', null, _("Select Masterfile"), true);

    amount_row($row['sa_adj_type'] == 2 ? _("Debit Amount: ") : _("Credit Amount: "), 'amount_row', price_format(0));

    end_table(1);
    div_end();
    submit_add_or_update_center(true, '', 'both');
    echo "<br> <br>";
}

function display_del_gl($trans_id) {

    start_table(TABLESTYLE2);

    $row = get_adjGL_val($trans_id);
    $_POST['trans_id'] = $row['id'];
    hidden('trans_id');

    display_heading(_("Are you sure to DELETE Transaction #" . $trans_id . "?"));
    end_table(1);
    submit_add_or_update_center(false, '', 'both', false, true);
}

function display_update_gl($trans_id) {

    display_heading(_('Updating GL Entry for Line ID #' . $trans_id));
    echo "<br>";
    div_start("update_gl");
    start_table(TABLESTYLE2);

    $row = get_adjGL_val($trans_id);

    $_POST['trans_id'] = $row['id'];
    hidden('trans_id');

    if ($row['gl_type'] == 'CHILD') {
        gl_all_accounts_list_row(_("Account:"), 'account_id_upd', $row['account'], false, false, _("Default Account"));
    }
    else {
        label_row(_("Account: "), $row['account'] . " - ". get_gl_account_name($row['account']));
    }

    sl_list_gl_row(_("Send to: "), 'mcode_upd', get_orig_mcode($row['master_file']), _("Select Masterfile"), true);

    if ($row['gl_type'] == 'CHILD') {
        
        text_row($row['amount'] > 0 ? _("Debit Amount: ") : _("Credit Amount: "), 
            'amount_row_upd', price_format(abs($row['amount'])), 15, 15, '', "nowrap align='right'");
    }
    else {
        label_row($row['amount'] > 0 ? _("Debit Amount: ") : _("Credit Amount: "), 
            price_format(abs($row['amount'])), "");
    }

    end_table(1);
    div_end();
    submit_add_or_update_center(false, '', 'both', false, false);
}

//----------------------------------------------------------------------------------------------------

function can_proceed($add = true) {

    $row = _adjGL_head_item($_GET['trans_no'], $_GET['line_no']);
    $val = get_adjGL_val(get_post('trans_id'));

    if ($add) {
        if ((child_adjGL_total($_GET['trans_no'], $_GET['line_no']) + input_num('amount_row')) > 
        abs($row['amount'])) {

        display_error(_("Can't proceed! Entered amount is greater than default amount!"));
        return false;
        }

        if (!input_num('amount_row')) {
            display_error(_("Invalid Amount!"));
            return false;
        }
    }
    else {

        if (!input_num('amount_row_upd') && $val['gl_type'] != 'DEFAULT') {
            display_error(_("Invalid Amount!"));
            return false;
        }

        if ((child_adjGL_total($_GET['trans_no'], $_GET['line_no']) - abs($val['amount']) + input_num('amount_row_upd')) 
            > abs($row['amount'])) {
            display_error(_("Can't proceed! Entered amount is greater than default amount! "));
            return false;
        }

    }

    return true;
}

//----------------------------------------------------------------------------------------------------
if (isset($_POST['ADD_ITEM']) && can_proceed(true)) {

    $row = _adjGL_head_item($_GET['trans_no'], $_GET['line_no']);

    add_adj_gl (
        $_GET['trans_no'], 
        $_GET['line_no'], 
        $row['sa_adj_type'], 
        $row['sa_reference'], 
        $row['stock_id'], 
        $row['color_code'], 
        $row['lot_no'], 
        $row['chassis_no'], 
        $row['amount'] > 0 ? -input_num('amount_row') : input_num('amount_row'), 
        get_mcode(get_post('mcode')) != null ? get_mcode(get_post('mcode')) : 
            get_default_adjGL_mcode($_GET['trans_no'], $_GET['line_no']), 
        get_masterfile(get_post('mcode')) != null ? get_masterfile(get_post('mcode')) : 
            get_default_adjGL_masterfile($_GET['trans_no'], $_GET['line_no']),
        get_post('account_id') != null ? get_post('account_id') : 5040,
        'CHILD', $row['sa_adj_item']
    );

    $Ajax->activate('_page_body');
    display_notification(_("Successfully Added!"));
}

if (isset($_POST['DELETE_ITEM'])) {
    delete_adjustmentGL(get_post('trans_id'));
}

if (isset($_POST['UPDATE_ITEM']) && can_proceed(false)) {

    $row = get_adjGL_val(get_post('trans_id'));
    $account = $mcode = $masterfile = '';
    //$amount = 0;

    if ($row['gl_type'] == 'DEFAULT') {
        $account = $row['account']; 
        $amount = $row['amount'];
    }
    else {
        $account = get_post('account_id_upd') != null ? get_post('account_id_upd') : $row['account'];
        $amount =  $row['amount'] > 0 ? input_num('amount_row_upd') : -input_num('amount_row_upd');
    }

    $mcode = get_post('mcode_upd') != null ? get_mcode(get_post('mcode_upd')) : $row['mcode'];
    $masterfile = get_post('mcode_upd') != null ? get_masterfile(get_post('mcode_upd')) : $row['master_file'];

    update_adjustmetGL(get_post('trans_id'), $account, $amount, $mcode, $masterfile);
}

//----------------------------------------------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

display_menu();

//----------------------------------------------------------------------------------------------------
global $Ajax;
$add_id = find_submit('AddGL');
$edit_id = find_submit('EditGL');
$delete_id = find_submit('DeleteGL');

if ($add_id != -1) {

    $row = _adjGL_head_item($_GET['trans_no'], $_GET['line_no']);

    if (child_adjGL_total($_GET['trans_no'], $_GET['line_no']) == abs($row['amount'])) {
        display_error(_("Cannot Add GL Entry! Account Already Balance!"));
    }
    else {
        $id = get_post('selected_id', find_submit('AddGL'));
        display_add_gl($id);
        $Ajax->activate('_page_body');
    }
}
else if ($edit_id != -1) {
    $id = get_post('selected_id', find_submit('EditGL'));
    display_update_gl($id);
}
else if ($delete_id != -1) {
    $id = get_post('selected_id', find_submit('DeleteGL'));
    display_del_gl($id);
}
hidden('class_name');

//----------------------------------------------------------------------------------------------------

end_form();
end_page(true);

