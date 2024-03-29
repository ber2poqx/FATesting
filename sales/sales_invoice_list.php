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
$page_security = 'SA_SALES_INVOICE_LIST';
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

$_SESSION['page_title'] = _($help_context = "List of Sales Invoice Cash/Installment");

page($_SESSION['page_title'], false, false, "", $js);

//-----------------------------------------------------------------------
start_form();

start_table(TABLESTYLE_NOBORDER);
start_row();
// ahref_cell(_("New Sales Invoice Installment"), "sales_order_entry.php?NewInvoice=0");
// ahref_cell(_("New Sales Invoice Cash"), "sales_invoice_cash.php?NewInvoice=0");
ref_cells(_("#:"), 'search_val', '', null, '', true);
date_cells(_("From:"), 'fromDate', '', null, -user_transaction_days());
date_cells(_("To:"), 'toDate', '', null, 1);
end_row();
end_table();


start_table(TABLESTYLE_NOBORDER);
start_row();
if (!$page_nested) {
	customer_list_cells(_("Select Customer: "), 'customer_id', null, true, true);
	stock_categories_list_cells(_("Category:"), "category_id", null, _("All Categories"), true);//Added by Albert
	payment_terms_type(_("Payment Type:"), "payment_terms", null, _("All Payment Type"), true);

	value_type_list(_("Sales Invoice Status:"), 'si_stat', 
    	array(
        	'Open' => 'Open',
			'Pending' => 'Pending',
        	'part-paid' => 'Part-Paid',
        	'fully-paid' => 'Fully-Paid'
    	), '', null, true, _('All Status Types')
	);
}

submit_cells('SearchRequest', _("Search"), '', _('Select documents'), 'default');
end_row();
end_table();

//---------------------------------------------------------------------------------------------
global $Ajax;

if (get_post('category_id') !='') {
	div_start('items_table');
}

if (get_post('payment_terms') !='') { 
	div_start('items_table');
}

function trans_view($trans)
{
	return get_trans_view_str(ST_SALESINVOICE, $trans["trans_no"]);
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
	return get_gl_view_str(ST_SALESINVOICE, $row["trans_no"]);
}

function fmt_amount($row)
{
	return price_format($row["ar_amount"]);
}

//Modified by spyrax10
function ar_balance($row)
{
	$ar_balance = 0;
	if ($row["status"] == "Pending") {
		$ar_balance = $row["ar_amount"];
	} else {
		if (($row["status"] != "Closed" || $row["status"] != "Close") && $row["payment_type"] == "INSTALLMENT") {
			$ar_balance = $row["ar_amount"] - $row["alloc"];
		} else if ($row["payment_type"] == "CASH" && $row["status"] == "part-paid") {
			$ar_balance = $row['lcp_2'] - $row['alloc'];
		} else if ($row["payment_type"] == "CASH" && $row["status"] == "Open") {
			$ar_balance = $row['lcp_2'];
		} else {
			$ar_balance = 0;
		}
	}

	return price_format($ar_balance);
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

function sales_return_replacement($row) {
	$link = '';
	$void_entry = get_voided_entry($row['type'], $row['trans_no']);

	if ($void_entry['void_status'] == "Voided") {
		$link = '';
	}
	else {
		if ($_SESSION["wa_current_user"]->can_access_page('SA_SALES_RETURN_REPLACEMENT')) {
			$link = done_check_qty_return_invoice($row["reference"]) || ($row["status"] == "Closed" || $row["status"] == "Close") || ($row["return_status"] == 0 || $row["return_status"] == 2)? '' : pager_link(
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
	$link = '';
	$void_entry = get_voided_entry($row['type'], $row['trans_no']);

	if ($void_entry['void_status'] == "Voided") {
		$link = '';
	}
	else {
		if ($_SESSION["wa_current_user"]->can_access_page('SA_SR_APPROVAL') && $row["status"] != "Open") {
			$link = done_check_qty_return_invoice($row["reference"]) || ($row["status"] == "Close"|| $row["status"] == "Closed" || $row["status"] == "Closed" ) || ($row["return_status"] == 1 || $row["return_status"] == 2)  ? '' :  pager_link(
				'SR Approval',
				"/sales/sales_return_approval.php?SONumber=" . $row["order_"],
				ICON_DOC
			);
		}
		else{
			$link = ''; 
		}
	}

	return $link;
}
//Added by spyrax10
function get_1stpay_stat($row)
{

	$sql = "SELECT X.status FROM " . TB_PREF . "debtor_loan_schedule X 
		WHERE X.trans_no =" . $row["trans_no"] . " 
			AND X.month_no = 1 AND X.debtor_no=" . $row["debtor_no"];

	$result = db_query($sql, "Can't get 1st payment status! (spyrax10)");
	$row = db_fetch_row($result);
	return $row[0];
}

//Added by Prog6 6/15/2021
function print_sales_invoice_receipt($row) {
	$link = '';
	$void_entry = get_voided_entry($row['type'], $row['trans_no']);

	if ($void_entry['void_status'] == "Voided") {
		$link = '';
	}
	else {
		if ($_SESSION["wa_current_user"]->can_access_page('SA_PRINT_SI')) {
			if ($row['payment_type'] == "CASH") {
				//modified by spyrax10 21 Mar 2022 Mantis Issue #815
				if($row['category'] == "MOTORCYCLE" || $row['category'] == "APPLIANCE" || $row['category'] == "POWERPRDUCT") // SERIALIZED 
				{ //Modified by Prog6 06/21/2023
					$link = printable_receipts_and_vouchers(Cash_SI_serialized, $row["trans_no"], _("Print to receipt: Cash SI Serialized"), ICON_PRINT);
				}
				else // NON-SERIALIZED
				{ // Modified by Prog6 for serialized & non-serialized items
					//Modified by Prog6 06/21/2023
					$link = printable_receipts_and_vouchers(Cash_SI, $row["trans_no"], _("Print to receipt: Cash Sales Invoice"), ICON_PRINT);
				}
			} else if ($row['payment_type'] == "INSTALLMENT") {
				if ($row['status'] == "Open" || $row['status'] == "Approved") {
					/*
					$link = pager_link(
						_("Print to receipt: Charge Sales Invoice"),
						"/reports/prnt_charge_SI_serialized.php?SI_num=" . $row["trans_no"],
						ICON_PRINT
					);
					*/

					//Modified by Prog6 06/21/2023
					$link = printable_receipts_and_vouchers(Charge_SI_serialized, $row["trans_no"], _("Print to receipt: Charge SI Serialized"), ICON_PRINT);
				}
			}
		
			if ($row['payment_type'] == "INSTALLMENT" && $row['downpayment_amount'] == 0) {
				if (get_1stpay_stat($row) == 'paid') {
					/*
					$link = pager_link(
						_("Print to receipt: Charge Sales Invoice"),
						"/reports/prnt_charge_SI_serialized.php?SI_num=" . $row["trans_no"],
						ICON_PRINT
					);*/
					//Modified by Prog6 06/21/2023
					$link = printable_receipts_and_vouchers(Charge_SI_serialized, $row["trans_no"], _("Print to receipt: Charge SI Serialized"), ICON_PRINT);
				}
			}
		}
		else {
			$link = '';
		}
	}

	return $link;
}

function change_term_link($row) {
	$link = '';
	$void_entry = get_voided_entry($row['type'], $row['trans_no']);

	if ($void_entry['void_status'] == "Voided") {
		$link = '';
	}
	else {
		if ($_SESSION["wa_current_user"]->can_access_page('SA_SITERMMOD')) {
			$link = (($row['payment_type'] == "INSTALLMENT" && ($row["status"] == "Closed" || $row["status"] == "Close" || $row["status"] == "fully-paid")) || $row['payment_type'] == "CASH") || $row['term_mode_fullpayment'] == 1 ? '' : pager_link(
				_("Change Term"),
				"/sales/sales_order_entry.php?NewChangeTerm=" . $row["trans_no"],
				ICON_RECEIVE
			);
		}
		else{
			$link = '';
		}
	}
	
	return $link;
}
//Added by Albert
function restructured_link($row) {
	$link = '';
	$void_entry = get_voided_entry($row['type'], $row['trans_no']);

	if ($void_entry['void_status'] == "Voided") {
		$link = '';
	}
	else {
		if ($_SESSION["wa_current_user"]->can_access_page('SA_RESTRUCTURED')) {

			$link = ($row['payment_type'] == "INSTALLMENT" && ($row["status"] == "Closed" || $row["status"] == "Close" || $row["status"] == "fully-paid")) || $row['payment_type'] == "CASH" || ($row["restructured_status"] == 0 || $row["restructured_status"] == 2) ? '' : pager_link(
				_("Restructured"),
				"/sales/sales_order_entry.php?NewRestructured=" . $row["trans_no"],
				ICON_RECEIVE
			);
		}
		else {
			$link = '';
		}
	}
	
	return $link;
}
function sales_restructured_approval($row) {
	$link = '';
	$void_entry = get_voided_entry($row['type'], $row['trans_no']);

	if ($void_entry['void_status'] == "Voided") {
		$link = '';
	}
	else {
		if ($_SESSION["wa_current_user"]->can_access_page('SA_SALES_RESTRUCTURED_APPROVAL')) {

			$link = done_check_qty_return_invoice($row["reference"]) || 
				($row["status"] == "Close" || $row["status"] == "Closed" || $row["status"] == "fully-paid") || 
				($row["restructured_status"] == 1 || $row["restructured_status"] == 2) ||
				$row['payment_type'] == "CASH" ? '' :  
	
			pager_link(
				'Restructured Approval',
				"/sales/sales_invoice_restructured_approval.php?SONumber=" . $row["order_"],
				ICON_DOC
			);
		}
		else {
			$link = null;
		}
	}

	
	return $link;
}

/*Added by Albert 11/07/2022*/
function payment_allocate_link($row)
{
	if ($row['term_mode_fullpayment'] == 1 && $row['amount_to_be_paid_status'] != 'paid') {
		return  ($row["status"] == "Closed" || $row["status"] == "Close" || $row["status"] == "fully-paid") ? '' : pager_link(
			_("Payment Allocate"),
			"/lending/allocation_payment.php?trans_no=" . $row["trans_no"]."&type=" . $row["type"] . "&customer=" . $row["debtor_no"] ,
			ICON_ALLOC
		);
	}else{
		return null;
	}
}
/**/


function cancel_link($row) {
	$cancel_link = '';
	$void_entry = get_voided_entry($row['type'], $row['trans_no']);

	if ($void_entry['void_status'] == "Voided") {
		$cancel_link = '';
	}
	else {
		$cancel_link =  $row["status"] == "Closed" || $row["status"] == "Close" || $row["status"] == "fully-paid" ? '' : pager_link(
			_("Cancel AR"),
			"/sales/sales_order_entry.php?CancelInvoice=" . $row["trans_no"],
			ICON_RECEIVE
		);
	
	}
	return $cancel_link;
}

//Added by spyrax10
function edit_link($row) {
	global $page_nested;

	//modified by Albert 07/13/2022
	if ($_SESSION["wa_current_user"]->can_access_page('SA_SI_UPDATE')) {

		return $page_nested || $row['status'] != "Pending" ? '' :
			trans_editor_link(ST_SALESINVOICE, $row['trans_no']);

	}else{

	    return null;
	}
}

function invoice_status($row) {
	$void_entry = get_voided_entry($row['type'], $row['trans_no']);
	return $void_entry['void_status'] == 'Voided' ? ucwords($row['status'], '-') . " (Voided)" : ucwords($row['status'], '-');
}

function check_void($row) {
    $void_entry = get_voided_entry($row['type'], $row['trans_no']);

    return $void_entry['void_status'] == 'Voided' ? true : false;
}

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
$sql = get_sales_invoices(
	$_POST['search_val'], 
	$_POST['customer_id'], 
	$_POST['category_id'], 
	$_POST['payment_terms'],
	0,
	$_POST['si_stat'],
	false,
	date2sql(get_post('fromDate')),
	date2sql(get_post('toDate'))
);

/*show a table of the Request returned by the sql */
$cols = array(
	_("Trans #") => array('fun' => 'trans_view', 'ord' => '', 'align' => 'right'),
	_("Status") => array('fun' => 'invoice_status', 'type' => 'nowrap', 'align' => 'left'),
	_("Sales Invoice #"),
	_("Customer"),
	_("Payment Type"),
	_("Invoice Type"),
	_("Category"),
	_("Invoice Date") => array('name' => 'tran_date', 'type' => 'date', 'ord' => 'desc'),
	_("Payment Location"),
	_("Months Term"),
	// _("Due Date") => array('name' => 'due_date', 'type' => 'date', 'ord' => 'desc'),
	// _("DR Trans #") => array('fun'=>'dr_trans_view', 'ord'=>'', 'align'=>'right'),
	_("SO Trans #") => array('fun' => 'so_trans_view', 'ord' => '', 'align' => 'right'),
	_("Gross Amount") => array('align' => 'right', 'fun' => 'fmt_amount'),
	_("LCP") => array('align' => 'right', 'fun' => 'lcp_amount'),
	_("DP") => array('align' => 'right', 'fun' => 'dp_amount'),
	_("Amortiztion") => array('align' => 'right', 'fun' => 'amortization_amount'),
	_("A/R Balance") => array('align' => 'right', 'fun' => 'ar_balance'),
	array('insert' => true, 'fun' => 'edit_link'), //Added by spyrax10
	array('insert' => true, 'fun' => 'gl_view'),
	array('insert' => true, 'fun' => 'sales_return_approval'),
	array('insert' => true, 'fun' => 'sales_restructured_approval'),	
	array('insert' => true, 'fun' => 'sales_return_replacement'),
	array('insert' => true, 'fun' => 'print_sales_invoice_receipt'), //Added by Prog6
	array('insert' => true, 'fun' => 'change_term_link'),
	array('insert' => true, 'fun' => 'restructured_link'), //Added by Albert
	array('insert' => true, 'fun' => 'payment_allocate_link'),
	array('insert' => true, 'fun' => 'cancel_row', 'align' => 'center')	
);

$table = &new_db_pager('invoice_tbl', $sql, $cols, null, null, 25);
$table->set_marker('check_pending');
$table->set_marker('check_void');
$table->width = "95%";

display_db_pager($table);

end_form();
end_page();
