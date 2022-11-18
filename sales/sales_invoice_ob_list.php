<?php

/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
 ***********************************************************************/
$path_to_root = "..";
$page_security = 'SA_SALES_INVOICE_OB';
include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

$js = '';
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

$_SESSION['page_title'] = _($help_context = "List of Sales Invoice for Opening Balances");

page($_SESSION['page_title'], false, false, "", $js);

//-----------------------------------------------------------------------
start_form();

start_table(TABLESTYLE_NOBORDER);
start_row();
// ahref(_("New Sales Invoice for Opening Balances"), "sales_invoice_opening_balances.php?NewInvoice=0");  //comment by Albert request by maam helen 05/25/2022
ref_cells(_("#:"), 'search_val', '', null, '', true);
if (!$page_nested)
	customer_list_cells(_("Select a customer: "), 'customer_id', null, true, true);

submit_cells('SearchRequest', _("Search"), '', _('Select documents'), 'default');
end_row();
end_table();

//---------------------------------------------------------------------------------------------

function trans_view($trans)
{
	if ($trans['invoice_type'] == 'new'){
		$type = ST_SALESINVOICE;
	}else{
		$type = ST_SALESINVOICEREPO;
	}
	return get_trans_view_str($type, $trans["trans_no"]);
}

function dr_trans_view($trans)
{
	return get_trans_view_str(ST_CUSTDELIVERY, $trans["dr_no"]);
}

function so_trans_view($trans)
{
	return get_trans_view_str(ST_SALESORDER, $trans["so_no"]);
}

function gl_view($row)
{
	if ($row['invoice_type'] == 'new'){
		$type = ST_SALESINVOICE;
	}else{
		$type = ST_SALESINVOICEREPO;
	}
	return get_gl_view_str($type, $row["trans_no"]);
}

function fmt_amount($row)
{
    return price_format($row["ar_amount"]);
}

function lcp_amount($row)
{
    return price_format($row["lcp_amount"]);
}

function dp_amount($row)
{
    return price_format($row["downpayment_amount"]);
}

function amortization_amount($row)
{
    return price_format($row["amortization_amount"]);
}

function check_pending($row)
{
	return $row['status'] == "Pending";
}

//Added by spyrax10
function sales_return_replacement($row) {

	$void_entry = get_voided_entry($row['type'], $row['trans_no']);
	$link = '';

	if ($void_entry['void_status'] == "Voided") {
		$link = '';
	}
	else {
		if ($_SESSION["wa_current_user"]->can_access_page('SA_SALES_RETURN_REPLACEMENT')) {

			$link = done_check_qty_return_invoice($row["reference"]) || ($row["status"] == "Closed" || $row["status"] == "Close") || ($row["return_status"] == 0 || $row["return_status"] == 2)? '' : 
			pager_link(
					_("Sales Return"),
					"/sales/sales_return_replacement_entry.php?NewSalesReturn=" . $row["trans_no"] . "&& Filter_type=" . $row["type"],
					ICON_CREDIT
			);
		}
		else {
			$link = '';
		}
	}
	return $link;
}
function sales_return_approval($row) {
	global $page_nested;

	$void_entry = get_voided_entry($row['type'], $row['trans_no']);
	$link = '';

	if ($void_entry['void_status'] == "Voided") {
		$link = '';
	}
	else {
		if ($_SESSION["wa_current_user"]->can_access_page('SA_SR_APPROVAL')) {
			$link = done_check_qty_return_invoice($row["reference"]) || ($row["status"] == "Close"|| $row["status"] == "Closed") || ($row["return_status"] == 1 || $row["return_status"] == 2)  ? '' :  
			pager_link(
				'SR Approval',
				"/sales/manage/approval_SI_OB.php?trans_no=" . $row["trans_no"],
				ICON_DOC
			);
		}
		else {
			$link = '';
		}
	}
	
	return $link;
}

function change_term_link($row) {

	$void_entry = get_voided_entry($row['type'], $row['trans_no']);
	$link = '';

	if ($void_entry['void_status'] == "Voided") {
		$link = '';
	}
	else {
		//modified by Albert 07/13/2022
		if ($_SESSION["wa_current_user"]->can_access_page('SA_SITERMMOD')) {
			if ($row['invoice_type'] == 'new'){
				return (($row['payment_type'] == "INSTALLMENT" && ($row["status"] == "Closed" || $row["status"] == "Close")) 
					|| $row['payment_type'] == "CASH") || $row['term_mode_fullpayment'] == 1  ? '' : pager_link(
					_("Change Term"),
					"/sales/sales_order_entry.php?NewChangeTerm=" . $row["trans_no"] . "&opening_balance=1",
					ICON_RECEIVE
				);
			}
			else {
				return ($row['payment_type'] == "INSTALLMENT" && ($row["status"] == "Closed" || $row["status"] == "Close")) 
					|| $row['payment_type'] == "CASH"  ? '' : pager_link(
					_("Change Term"),
					"/sales/si_repo_install.php?NewChangeTerm=" . $row["trans_no"] . "&opening_balance=1",
					ICON_RECEIVE
				);
			}
		}
		else {
			$link = '';
		}
	}
	
	return $link;
}
//Added by Albert
function sales_restructured_approval($row) {
	global $page_nested;
	
	$void_entry = get_voided_entry($row['type'], $row['trans_no']);
	$link = '';

	if ($void_entry['void_status'] == "Voided") {
		$link = '';
	}
	else {
		if ($_SESSION["wa_current_user"]->can_access_page('SA_SALES_RESTRUCTURED_APPROVAL')) {
			$link = done_check_qty_return_invoice($row["reference"]) || ($row["status"] == "Close"|| $row["status"] == "Closed") || ($row["restructured_status"] == 1 || $row["restructured_status"] == 2)  ? '' :  pager_link(
				'Restructured Approval',
				"/sales/manage/approval_restructured_ob.php?trans_no=" . $row["trans_no"],
				ICON_DOC
			);
		}
		else {
			$link = '';
		}
	
	}

	return $link;
}
function restructured_link($row) {

	$void_entry = get_voided_entry($row['type'], $row['trans_no']);
	$link = '';

	if ($void_entry['void_status'] == "Voided") {
		$link = '';
	}
	else {
		if ($_SESSION["wa_current_user"]->can_access_page('SA_RESTRUCTURED')) {

			if ($row['invoice_type'] == 'new'){
				return ($row['payment_type'] == "INSTALLMENT" && ($row["status"] == "Closed" || $row["status"] == "Close")) || $row['payment_type'] == "CASH" || ($row["restructured_status"] == 0 || $row["restructured_status"] == 2) ? '' : pager_link(
					_("Restructured"),
					"/sales/sales_order_entry.php?NewRestructured=" . $row["trans_no"]. "&opening_balance=1",
					ICON_RECEIVE
				);
			}
			else {
				return ($row['payment_type'] == "INSTALLMENT" && ($row["status"] == "Closed" || $row["status"] == "Close")) || $row['payment_type'] == "CASH" || ($row["restructured_status"] == 0 || $row["restructured_status"] == 2) ? '' : pager_link(
					_("Restructured"),
					"/sales/si_repo_install.php?NewRestructured=" . $row["trans_no"] . "&opening_balance=1",
					ICON_RECEIVE
				);
	
			}
		}
		else{
			return null;
		}
	}

	return $link;
}
/*Added by Albert 11/07/2022*/
function payment_allocate_link($row)
{
	if ($row['term_mode_fullpayment'] == 1) {
		return ($row["status"] == "Closed" || $row["status"] == "Close") ? '' : pager_link(
			_("Payment Allocate"),
			"/lending/allocation_payment.php?trans_no=" . $row["trans_no"]."&type=" . $row["type"] . "&customer=" . $row["debtor_no"] ,
			ICON_ALLOC
		);
	}
}
/**/

function status_row($row) {
	$void_entry = get_voided_entry($row['type'], $row['trans_no']);
	return $void_entry['void_status'] == 'Voided' ? ucwords($row['status'], '-') . " (Voided)" : ucwords($row['status'], '-');
}

function check_void($row) {
    $void_entry = get_voided_entry($row['type'], $row['trans_no']);

    return $void_entry['void_status'] == 'Voided' ? true : false;
}

//Added by spyrax10 12 Sep 2022
function cancel_row($row) {
    $cancel_link = '';

    if ($_SESSION["wa_current_user"]->can_access_page('SA_VOIDTRANSACTION')) {
        $void_entry = get_voided_entry($row['type'], $row['trans_no']);

        if ($void_entry == null) {
            $cancel_link = pager_link( _("Request to Cancel"),
                "/admin/manage/void_draft.php?trans_no=" . $row['trans_no'] . "&type=" . $row['type'] ."&status=0&cancel=1", ICON_CANCEL
            );
        }
        else if ($void_entry['void_status'] == 'Disapproved') {

            $cancel_link = pager_link( _("Request to Cancel"),
                "/admin/manage/void_draft.php?trans_no=" . $row['trans_no'] . "&type=" . $row['type'] ."&status=0&cancel=1", ICON_CANCEL
            );
        }
    }
    else {
		$cancel_link = '';
	}

    return $cancel_link;
}
//

//figure out the sql required from the inputs available
$sql = get_sales_invoices_aropening($_POST['search_val'], $_POST['customer_id'], 1);

/*show a table of the Request returned by the sql */
$cols = array(
	_("Trans #") => array('fun'=>'trans_view', 'ord'=>'', 'align'=>'right'),
	_("Status") => array('fun' => 'status_row', 'type' => 'nowrap', 'align' => 'left'),
	_("Sales Invoice #"),
	_("Customer"),
	_("Payment Type"),
	_("Invoice Type"),
	_("Invoice Date") => array('name' => 'tran_date', 'type' => 'date', 'ord' => 'desc'),
	_("Months Term"),
    // _("Due Date") => array('name' => 'due_date', 'type' => 'date', 'ord' => 'desc'),
	// _("DR Trans #") => array('fun'=>'dr_trans_view', 'ord'=>'', 'align'=>'right'),
	// _("SO Trans #") => array('fun'=>'so_trans_view', 'ord'=>'', 'align'=>'right'),
    _("A/R Amount") => array('align'=>'right', 'fun'=>'fmt_amount'), 
	_("LCP") => array('align'=>'right', 'fun'=>'lcp_amount'), 
	_("DP") => array('align'=>'right', 'fun'=>'dp_amount'), 
	_("Amortiztion") => array('align'=>'right', 'fun'=>'amortization_amount'), 
    array('insert'=>true, 'fun'=>'gl_view'),
	//Added by spyrax10
	array('insert' => true, 'fun' => 'sales_return_approval'),
	array('insert' => true, 'fun' => 'sales_restructured_approval'),
	array('insert' => true, 'fun' => 'sales_return_replacement'),
	array('insert' => true, 'fun' => 'change_term_link'),
	array('insert' => true, 'fun' => 'restructured_link'), //Added by Albert
	array('insert' => true, 'fun' => 'payment_allocate_link'),
	array('insert' => true, 'fun' => 'cancel_row', 'align' => 'center')
	//
);

$table = &new_db_pager('invoice_tbl', $sql, $cols, null, null, 25);
$table->set_marker('check_pending');
$table->set_marker('check_void');

$table->width = "95%";

display_db_pager($table);

end_form();
end_page();