<?php
/**
 * Added by spyrax10
 */

$path_to_root = "../..";
$page_security = 'SA_SALES_RETURN_UPDATE_STATUS';

include_once($path_to_root . "/sales/includes/cart_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/ui/sales_invoice_opening_balances_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_types_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/includes/cost_and_pricing.inc");

include_once($path_to_root . "/sales/includes/db/sales_installment_policy_db.inc");	// Added by Ronelle 2/25/2021

$js = '';
if ($SysPrefs->use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}

if (user_use_date_picker()) {
	$js .= get_js_date_picker();
}

page(_("Draft for SI Opening Balances #" . $_GET['trans_no']), false, false, "", $js);

//-----------------------------------------------------------------------------
function get_SIOB_header($trans_no) {

    $sql = "SELECT A.*, E.*, B.name, C.stock_id, 
        CASE WHEN A.payment_terms > 0 THEN 'INSTALLMENT' ELSE 'CASH' END AS pay_terms,
        CONCAT(D.lastname, ', ', D.firstname, ' ', D.middlename) AS comaker

        FROM " . TB_PREF . "debtor_trans A 
        LEFT JOIN " . TB_PREF . "debtors_master B ON A.debtor_no = B.debtor_no
        LEFT JOIN " . TB_PREF . "debtor_trans_details C ON A.trans_no = C.debtor_trans_no
        LEFT JOIN " . TB_PREF . "co_makers D ON A.debtor_no = D.debtor_no
        LEFT JOIN " . TB_PREF . "debtor_loans E ON A.trans_no = E.trans_no
        WHERE A.type = 10 AND A.opening_balances = 1 
        AND A.trans_no = ".db_escape($trans_no);
    
    $sql .= " GROUP BY A.trans_no";

    $result = db_query($sql, "No Items return for SI Opening Balances! (spyrax10)");
	set_global_connection();
	return $result;
}

function get_SIOB_items($trans_no) {

    $sql = "SELECT A.*, B.units 
        FROM " . TB_PREF . "debtor_trans_details A
        LEFT JOIN " . TB_PREF . "stock_master B ON A.stock_id = B.stock_id
        WHERE A.debtor_trans_type = 10 AND A.debtor_trans_no = " .db_escape($trans_no);

    $result = db_query($sql, "No Items return for SI Opening Balances! (spyrax10)");
	set_global_connection();
	return $result;
}

function get_SIOB_reference($trans_no) {

    $sql = "SELECT A.reference 
        FROM " . TB_PREF . "debtor_trans A 
        WHERE A.type = 10 AND A.opening_balances = 1 
        AND A.trans_no = ".db_escape($trans_no);

    $result = db_query($sql, "Cant get SI Opening Balances reference! (spyrax10)");
    set_global_connection();
	$row = db_fetch_row($result);
	return $row[0];
}

function update_SIOB_status($trans_no, $status) {

    global $Refs;

    $sql = "UPDATE ".TB_PREF."debtor_trans 
        SET return_status = '$status' 
        WHERE type != 13 AND opening_balances = 1 
            AND trans_no = " .db_escape($trans_no);

    set_global_connection();
    db_query($sql, "Cannot update SI Opening Balances status! (spyrax10)");

    $Refs->save(ST_SALESINVOICE, $trans_no, get_SIOB_reference($trans_no));
    add_audit_trail(ST_SALESINVOICE, $trans_no, Today(), "Update SI Opening Balances Status to " 
        . $status == 1 ? 'Approved' : 'Disapproved');
}

//-----------------------------------------------------------------------------
function display_SIOB_heading($trans_no) {
    
    $result = get_SIOB_header($trans_no);

    start_outer_table(TABLESTYLE2, "width='80%'");

    while ($row = db_fetch_assoc($result)) {

        table_section(1);
        label_row(_("Customer: "), $row['name']);
        label_row(_("Invoice #: "), $row['reference']);
        label_row(_("Reference #: "), $row['ref_no']);
        label_row(_("Invoice Date: "), sql2date($row['tran_date']));
        label_row(null, ''); label_row(null, ''); label_row(null, '');
        label_row(_("WRC/EW Code: "), $row['warranty_code']);
        label_row(_("FSC Series: "), $row['fsc_series']);

        table_section(2);
        //label_row(_("Category: "), get_category_name(get_stock_catID($row['stock_id'])));
        label_row(_("Payment Type: "), $row['pay_terms']);
        label_row(_("Co-Maker: "), $row['comaker']);
        label_row(_("1st Downpayment: "), number_format($row['downpayment_amount'], 2));
        label_row(_("Discount Downpayment: "), number_format($row['discount_downpayment'], 2));
        label_row(_("First Due Date: "), sql2date($row['firstdue_date']));
        label_row(_("Maturity Date: "), sql2date($row['maturity_date']));

        table_section(3);
        label_row(_("Months Term: "), $row['months_term']);
        label_row(_("Rebate: "), number_format($row['rebate'], 2));
        label_row(_("Financing Rate: "), $row['financing_rate']. "%");
        label_row(_("LCP Amount: "), number_format($row['lcp_amount'], 2));
        label_row(_("Due/Amortization: "), number_format($row['amortization_amount'], 2));
        label_row(_("A/R Amount: "), number_format($row['ar_amount'], 2));
        
    }

    end_outer_table(1);
}

function display_SIOB_items($trans_no) {
    
    display_heading("SI Opening Balances");
    div_start('ob_items');
    start_table(TABLESTYLE, "colspan=7 width='90%'");

    $result = get_SIOB_items($trans_no);

    $th = array(
        _("Item Code"), 
        _("Item Description"), 
        _("Color"), 
        _("Quantity"), 
        _("Unit"),  
        _('Unit Price'),
        _('Discount'),
        _('Other Discount'),
        _("Sub Total"),
        _("Serial/Engine Num"), 
        _("Chassis Num")
    );

    table_header($th);

	$total = 0;
    $k = $total = 0;

    while ($row = db_fetch_assoc($result)) {
        alt_table_row_color($k);

        $sub_total = $row['quantity'] * $row['unit_price'];
        $total += $sub_total;

        label_cell($row['stock_id']);
        label_cell($row['description']);
        label_cell($row['color_code']);
        label_cell($row['quantity'], "align='center'");
        label_cell($row['units'], "align='center'");
        label_cell(number_format($row['unit_price'], 2), "align='center'");
        label_cell(number_format($row['discount1'], 2), "align='center'");
        label_cell(number_format($row['discount2'], 2), "align='center'");
        label_cell(number_format($sub_total, 2), "align='right'");
        label_cell($row['lot_no'], "align='center'");
        label_cell($row['chassis_no'], "align='center'");
    }
    label_row(_("Document Total: "), number_format2($total, user_price_dec()), 
        "align=right colspan=8; style='font-weight:bold';", "style='font-weight:bold'; align=right", 0);

    end_table();
    div_end();
}
//-----------------------------------------------------------------------------
function can_proceed() {

    if (!is_date_in_fiscalyear(Today())) {
        display_error(_("The Entered Date is OUT of FISCAL YEAR or is CLOSED for further data entry!"));
		return false;
    }

    return true;
}
//-----------------------------------------------------------------------------

if (isset($_POST['Approved']) && can_proceed()) {
    update_SIOB_status($_GET['trans_no'], 1);
    meta_forward("../sales_invoice_ob_list.php?");
}

if (isset($_POST['Disapproved']) && can_proceed()) {
    update_SIOB_status($_GET['trans_no'], 2);
    meta_forward("../sales_invoice_ob_list.php?");
}

//-----------------------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

display_SIOB_heading($_GET['trans_no']);
display_SIOB_items($_GET['trans_no']);

echo "<br>";
submit_center_first('Approved', _("Approved"), '', 'default');
submit_center_last('Disapproved', _("Disapproved"), '', 'default', ICON_DELETE);

end_form();
end_page();