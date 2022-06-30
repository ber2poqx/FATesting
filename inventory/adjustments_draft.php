<?php
/**
 * Added by: spyrax10
 */

$path_to_root = "..";

$page_security = $_GET['status'] == 1 ? 'SA_INVTY_POST' : 'SA_INVTY_DRAFT';

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/ui/items_cart.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/sweetalert.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
    $js .= get_js_open_window(1000, 600);
if (user_use_date_picker())
    $js .= get_js_date_picker();

$page_title = is_adj_repo($_GET['trans_no']) ? "Inventory Adjustment - Repo #" . $_GET['trans_no'] :
    "Inventory Adjustment #" . $_GET['trans_no'];
page(_($help_context = $page_title), false, false, "", $js);

$trans_no = $_GET['trans_no'];
$status = $_GET['status'];

//-----------------------------------------------------------------------------

if (smo_exists($trans_no, ST_INVADJUST)) 
{
	$trans_type = ST_INVADJUST;
    $sub_title = is_adj_repo($_GET['trans_no']) ? "Adjustment - Repo" : "Adjustment";

    update_adjustment($trans_no, '', 0, 1);
    $result = get_stock_adjustment_items($trans_no);
    $row = db_fetch($result);

    if (is_fixed_asset($row['mb_flag'])) {
        display_notification_centered(_("Fixed Assets disposal has been processed!"));
        display_note(get_trans_view_str($trans_type, $trans_no, _("&View this disposal")));

        display_note(get_gl_view_str($trans_type, $trans_no, _("View the GL &Postings for this Disposal")), 1, 0);
	    hyperlink_params($_SERVER['PHP_SELF'], _("Enter &Another Disposal"), "NewAdjustment=1&FixedAsset=1");
    }
    else {
        display_notification_centered(_("Inventory ". $sub_title . " has been processed!"));
        display_note(get_trans_view_str($trans_type, $trans_no, _("&View this " . $sub_title)));

        display_note(get_gl_view_str($trans_type, $trans_no, _("View the GL &Postings for this " . $sub_title)), 1, 0);

	    hyperlink_params("$path_to_root/inventory/adjustments.php", _("Enter &Another " . $sub_title), 
            is_adj_repo($_GET['trans_no']) ? "RepoAdjustment=1" : "NewAdjustment=1");
    }

    if (is_adj_repo($_GET['trans_no'])) {
        hyperlink_params("$path_to_root/inventory/inquiry/adjustment_repo_view.php?", _("Back to Inventory Adjustment - Repo List"), "");
    }
    else if (is_adj_repo($_GET['trans_no']) == false) {
        hyperlink_params("$path_to_root/inventory/inquiry/adjustment_view.php?", _("Back to Inventory Adjustment List"), "");
    }

	hyperlink_params("$path_to_root/admin/attachments.php", _("Add an Attachment"), "filterType=$trans_type&trans_no=$trans_no");
	display_footer_exit();
}

//-----------------------------------------------------------------------------

global $Ajax;
if (isset($_POST['AdjDate'])) {
    $_POST['AdjDate'] = get_post('AdjDate');
    $Ajax->activate('AdjDate');
}

//-----------------------------------------------------------------------------
function get_adjust_head($trans_no) {
	
	$sql = "SELECT A.*, B.description 
            FROM " . TB_PREF . "stock_adjustment A 
                INNER JOIN " . TB_PREF . "stock_category B ON A.category_id = B.category_id
            WHERE A.trans_no=" .db_escape($trans_no);
    
    $sql .= " GROUP BY A.trans_no";

	$result = db_query($sql, "No Items return for stock_adjustments! (spyrax10)");
	set_global_connection();
	return $result;
}

function get_adjust_items($trans_no, $include_child = false) {
	
	$sql = "SELECT A.trans_id, A.line_id, A.trans_no, A.item_type, A.stock_id, A.color_code, A.loc_code, 
        A.tran_date, A.reference, abs(A.qty) AS qty, A.standard_cost, A.lot_no, A.chassis_no,
        A.category_id, A.adjustment_type, A.status, SM.description, SM.units, A.trans_no_out, A.trans_type_out,
        C.mcode, C.master_file

        FROM " . TB_PREF . "stock_adjustment A 
            LEFT JOIN " . TB_PREF . "stock_master SM ON A.stock_id = SM.stock_id
            LEFT JOIN " . TB_PREF . "stock_adjustment_gl C ON A.trans_id = C.sa_trans_no 
                AND A.line_id = C.sa_line_id";
    
    if (!$include_child) {
        $sql .= " AND C.gl_type = 'DEFAULT'";
    }

    $sql .= " WHERE A.trans_no=" .db_escape($trans_no);
	$result = db_query($sql, "No Items return for stock_adjustments! (spyrax10)");
	set_global_connection();
	return $result;
}

function del_item_serial($stock_id, $lot_no, $chassis_no = null) {

    $sql = "DELETE FROM " . TB_PREF . "item_serialise 
        WHERE serialise_trans_type = 17 AND serialise_item_code = " .db_escape($stock_id) . 
        " AND serialise_lot_no = ".db_escape($lot_no);
    
    if ($chassis_no != null) {
        $sql .= " AND serialise_chasis_no = ".db_escape($chassis_no);
    }

    $result = db_query($sql, "Can't delete serial #! (spyrax10)");
	set_global_connection();
	return $result;
}

function update_adj_ref($old_ref = '', $new_ref = '', $trans_no) {

    $sql = "UPDATE ".TB_PREF."stock_adjustment SET reference = '$new_ref' 
        WHERE trans_no = " .db_escape($trans_no);
    
    set_global_connection();
    db_query($sql, "Cannot update stock_adjustment reference! (spyrax10)");

    if (is_date_in_fiscalyear(Today())) {
        add_audit_trail(ST_INVADJUST, $trans_no, Today(), "Update Ref From: " . $old_ref . " To: " .$new_ref);
    }
}

function update_adjustment($trans_no, $remarks = '', $approve_stat = 1, $stat = 0, $stock_id = '') {

    $status = '';

    if ($approve_stat == 2) {
        $status = 'Disapproved';
    }
    else {
        if ($stat == 1) {
            $status = 'Closed';
        }
        else {
            $status = 'Approved';
        }
    }

    $date = date('Y-m-d', strtotime(Today()));
    $approver = get_current_user_fullname();

    $sql = "UPDATE ".TB_PREF."stock_adjustment 
        SET status = '$status', date_approved = '$date', 
            approver = '$approver'";

    if ($remarks != '') {
        $sql .= ", comments = '$remarks'";
    }

    $sql .= " WHERE trans_no=".db_escape($trans_no);
        
    set_global_connection();
    db_query($sql, "Cannot update stock_adjustment! (spyrax10)");
    
    if ($status != 'Closed') {
        add_audit_trail(ST_INVADJUST, $trans_no, Today(), $status . " Inventory Adjustment. Stock ID: " .$stock_id);
    }
}

function add_smo($trans_no, $remarks = '', $approve_stat = 1, $status = 0) {

    global $Refs;

    $resHead = get_adjust_head($trans_no);
    $resItems = get_adjust_items($trans_no);

    $adj_type = 1; $cat_id = $qty = $std_cost = $trans_id = $person_id = $line_id = $amount =
    $diff_total = 0;
    $loc_code = $reference = $memo = $stock_id = $stock_des = $stock_color = $lot_no = $chassis_no = $masterfile =
    $mcode = $masterfile2 = $account = $item_type = ""; 
    $trans_date = $manu_date = $expire_date = '0000-00-00';

    while ($row = db_fetch($resHead)) {
        $loc_code = $row['loc_code'];
        $adj_type = $row['adjustment_type'] == "IN" ? 1 : 2;
        $reference = $row['reference'];
        $cat_id = $row['category_id'];
        $memo = $row['memo'];
        $trans_date = $_POST['AdjDate'];
    }

    while ($row = db_fetch($resItems)) {
    
        if ($trans_id != $row['trans_id']) {

            $line_id = $row['line_id'];
            $trans_id = $row['trans_id'];
            $item_type = $row['item_type'];
            $stock_id = $row['stock_id'];
            $stock_des = $row['description'];
            $stock_color = $row['color_code'];
            $qty = $adj_type == 2 ? -$row['qty'] : $row['qty'];
            $lot_no = $row['lot_no'];
            $chassis_no = $row['chassis_no'] != null ? $row['chassis_no'] : null;
            $std_cost = $row['standard_cost'];
            $trans_no_out = $row['trans_no_out'];
            $trans_type_out = $row['trans_type_out'];

            if ($status == 1) {

                add_stock_adjustment_item(0, $adj_type, 
                    $trans_no, $stock_id, $loc_code, $trans_date, 
                    $reference, $qty, $std_cost, $memo, $manu_date, $expire_date, 
                    $lot_no, $chassis_no, $cat_id, $stock_color, '', '', '',
                    $line_id, $item_type, $trans_no_out, $trans_type_out
                );

                if ($adj_type == 2) {
                    if (serial_exist($lot_no, $chassis_no)) {
                        del_item_serial($stock_id, $lot_no, $chassis_no);
                    }
                }

                update_adjustment($trans_no, $remarks, $approve_stat, 1, $stock_id);
            }
            else {
                update_adjustment($trans_no, $remarks, $approve_stat, 0, $stock_id );
            }
           
        }
    }

    if (smo_exists($trans_no, ST_INVADJUST)) {
        if ($memo != '') {
            add_comments(ST_INVADJUST, $trans_no, $trans_date, $memo);
        }
        $Refs->save(ST_INVADJUST, $trans_no, $reference);
        add_audit_trail(ST_INVADJUST, $trans_no, $trans_date, "Posted Inventory Adjustment");
    }
}

function check_status_adj($trans_no) {
    $sql = "SELECT A.status FROM " . TB_PREF . "stock_adjustment A 
		WHERE A.trans_no=" . db_escape($trans_no);

	$result = db_query($sql, "Cant get adjustment status! (spyrax10)");
    set_global_connection();
	$row = db_fetch_row($result);
	return $row[0];
}

//-----------------------------------------------------------------------------

function display_adjustment_header($trans_no)
{
    global $Refs;
    $result = get_adjust_head($trans_no);
    $reference = '';

    start_outer_table(TABLESTYLE2, "width='80%'");

    while ($row = db_fetch_assoc($result)) {
       
        $date = $row['tran_date'];
        $_POST['category_id'] = $row['category_id'];

        if (!check_reference($row['reference'], ST_INVADJUST, 0, null, null, false)) {
            $new_ref = $Refs->get_next(ST_INVADJUST);
            update_adj_ref($row['reference'], $new_ref, $trans_no);
            $reference = $row['reference'] . " -> " . $new_ref;
            display_warning(_("Reference updated to: " . $new_ref . " from: " . $row['reference']));
        }
        else {
            $reference = $row['reference'];
        }

        table_section(1);
        label_row(_("Location: "), _branch_name($row['loc_code']));
        
        if ($_GET['status'] == 1) {
            label_row(_("Transaction Date: "), phil_short_date($row['tran_date']), "", "", 0, 'orig_date');
            if (!isset($_POST['AdjDate'])) {
                $_POST['AdjDate'] = sql2date($date);
            }
            date_row(_("Posting Date: "), 'AdjDate', '', null, 0, 0, 0, null, true);
        }
        else {
            label_row(_("Transaction Date: "), phil_short_date($row['tran_date']));
        }

        label_row(_("Adjustment Type: "), $row['adjustment_type']);
    
        table_section(2);
        label_row(_("Reference: "), $reference);
        label_row(_("Category: "), $row['description'], "", "", 0, 'category_id');
        label_row(_("Memo: "), $row['memo']);
    }
    end_outer_table(1);
}

function display_adjustment_items($trans_no)
{
	display_heading("Adjustment Items");
	div_start('adj_items');
    start_table(TABLESTYLE, "colspan=7 width='90%'");
    
    $result = get_adjust_items($trans_no);

    $th = array(
        _('ID'),
        $_GET['status'] == 1 ? _("GL") : '',
        _("Item Code"), 
        _("Item Description"), 
        _("Color"), 
        _("Quantity"), 
        _("Unit"),  
        _("Serial/Engine Num"), 
        _("Chassis Num"),
        _("Unit Cost"), 
        _("Sub Total")
    );

    table_header($th);

	$total = 0;
    $k = 0;

	while ($row = db_fetch_assoc($result)) {
        alt_table_row_color($k);

        $sub_total = $row['qty'] * $row['standard_cost'];
        $total += $sub_total;
        $adj_type = $row['adjustment_type'] == "IN" ? 1 : 2;

        label_cell($row['trans_id'], "align='center'");
        
        if ($sub_total > 0 && $_GET['status'] == 1) {
            view_JE_adj_cell($trans_no, $row['line_id'], $_GET['status']);
        }
        else {
            label_cell('');
        }
        
        view_stock_status_cell($row['stock_id']);
        label_cell($row['description']);
        label_cell($row['color_code']);
        label_cell($row['qty'], "nowrap align='center'");
        label_cell($row['units'], "nowrap align='center'");
        label_cell($row['lot_no']);
        label_cell($row['chassis_no']);
        amount_cell($row['standard_cost']);
        amount_cell($sub_total);

        if ($sub_total > 0) {
            $person_id = get_sup_id_by_stock($row['stock_id']);
		    $masterfile = get_sup_name_by_sup(get_sup_id_by_stock($row['stock_id']));

		    $stock_gl_codes = get_stock_gl_code($row['stock_id']);
		    $amount = $adj_type == 1 ? $row['qty'] * $row['standard_cost'] :
			    -$row['qty'] * $row['standard_cost'];

            if (adjGL_line_exists($trans_no, $row['line_id']) == 0) {
                add_adj_gl (
                    $trans_no, 
                    $row['line_id'], 
                    $adj_type, 
                    $row['reference'], 
                    $row['stock_id'], 
                    $row['color_code'], 
                    $row['lot_no'], 
                    $row['chassis_no'], 
                    $amount, 
                    $person_id, 
                    $masterfile,
                    $row['item_type'] == 'repo' ? $stock_gl_codes['wip_account'] : 
                        $stock_gl_codes['inventory_account'], 
                    'DEFAULT', $row['item_type']
                );
            }
            
        }
    }

    label_row(_("Document Total: "), number_format2($total, user_price_dec()), 
        "align=right colspan=10; style='font-weight:bold';", "style='font-weight:bold'; align=right", 0
    );

    end_table();
    div_end();
}

//-----------------------------------------------------------------------------
function can_proceed($approve_stat = 0) {

    if (!is_date_in_fiscalyear(Today())) {
        display_error(_("The Entered Date is OUT of FISCAL YEAR or is CLOSED for further data entry!"));
		return false;
    }
    else if (!allowed_posting_date(Today())) {
		display_error(_("The Entered Date is currently LOCKED for further data entry!"));
		return false;
	}
    
    if (get_post('Comments') == '' && $approve_stat == 2) {
        display_error(_('Remarks needed for disapproval!'));
        return false;
    }
    
    return true;
}

function can_post() {
    $result = get_adjust_items($_GET['trans_no'], true);

    $trans_date = $_POST['AdjDate'];

    if (!is_date_in_fiscalyear($trans_date)) {
        display_error(_("The Entered Date is OUT of FISCAL YEAR or is CLOSED for further data entry!"));
		return false;
    }
    else if (!allowed_posting_date(Today())) {
		display_error(_("The Entered Date is currently LOCKED for further data entry!"));
		return false;
	}
    else {
        while ($row = db_fetch_assoc($result)) {

            $line_id = $row['line_id'];
            $trans_id = $row['trans_id'];
            $stock_id = $row['stock_id'];
            $qoh = get_qoh_on_date_new($row['trans_type_out'], $row['trans_no_out'], $stock_id, $row['loc_code'], $trans_date, $row['lot_no']);
    
            if (default_adjGL_total($_GET['trans_no'], $line_id) != child_adjGL_total($_GET['trans_no'], $line_id) 
                && $row['standard_cost'] > 0) {
                display_error(_("Can't Proceed! GL Account in some entries ARE NOT BALANCE!"));
                return false;
            }
            else if (!check_reference($row['reference'], ST_INVADJUST)) {
                return false;
            }
            else if ($row['adjustment_type'] == "OUT" && $qoh < abs($row['qty'])) {
                display_error(_("Can't Proceed! There is NOT enough quantity in stock for Stock ID: " .$stock_id));
                return false;
            }
            // else if ($row['mcode'] == '' || $row['master_file'] == '') {
            //     display_error(_("Cant' Proceed! There are missing masterfile in some entries!"));
            //     return false;
            // }
        }
    }
    return true;
}

//-----------------------------------------------------------------------------

if (isset($_POST['Approved']) && can_proceed(1)) { 
    add_smo($trans_no, $_POST['Comments'], 1, 0);

    if (is_adj_repo($_GET['trans_no'])) {
        meta_forward("../inventory/inquiry/adjustment_repo_view.php?");
    }
    else if (is_adj_repo($_GET['trans_no']) == false) {
        meta_forward("../inventory/inquiry/adjustment_view.php?");
    }
}

if (isset($_POST['POST_SMO']) && can_post()) { 
    add_smo($trans_no, '', 1, 1);
    meta_forward($_SERVER['PHP_SELF'], "trans_no=$trans_no" ."&status=0");
}


if (isset($_POST['Disapproved']) && can_proceed(2)) {
    add_smo($trans_no, $_POST['Comments'], 2, 0);

    if (is_adj_repo($_GET['trans_no'])) {
        meta_forward("../inventory/inquiry/adjustment_repo_view.php?");
    }
    else if (is_adj_repo($_GET['trans_no']) == false) {
        meta_forward("../inventory/inquiry/adjustment_view.php?");
    }
}

//-----------------------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

display_adjustment_header($trans_no);
display_adjustment_items($trans_no);

start_table(TABLESTYLE2);
echo "<br> <br>";

if ($status != 1) {
    textarea_row(_("Remarks:"), 'Comments', null, 70, 4);
}

end_table(1);

if (check_status_adj($trans_no) == "Draft") {
    submit_center_first('Approved', _("Approved"), '', 'default');
    submit_center_last('Disapproved', _("Disapproved"), '', 'default', ICON_DELETE);
}
else if (check_status_adj($trans_no) == "Approved") {
    if ($status == 1) {
        submit_center_first('POST_SMO', _("Post This Transaction"), '', 'default');
    }
}

end_form();
end_page();