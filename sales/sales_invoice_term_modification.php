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
$page_security = 'SA_SITERM_INQ'; //Modified by spyrax10 13 Jul 2022
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

$_SESSION['page_title'] = _($help_context = "List of Sales Invoice Term Modification");

page($_SESSION['page_title'], false, false, "", $js);
start_form();

start_table(TABLESTYLE_NOBORDER);
start_row();

ref_cells(_("#:"), 'search_val', '', null, '', true);
full_payment_type(_("Pay Type:"), 'pay_type_id', null	, true, 'label');

submit_cells('Search', _("Search"), '', _('Select documents'), 'default');

end_row();
end_table();

//---------------------------------------------------------------------------------------------

function trans_view($trans)
{
	return get_trans_view_str(ST_SITERMMOD, $trans["trans_no"]);
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
	return get_gl_view_str(ST_SITERMMOD, $row["trans_no"]);
}

function fmt_amount($row)
{
	return price_format($row["ar_amount"]);
}

function ar_balance($row)
{
	return price_format($row["outstanding_ar_amount"]);
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

function sales_return_replacement($row)
{
	return done_check_qty_replace_invoice($row["reference"]) ? '' : pager_link(
		_("Sales Return"),
		"/sales/sales_return_replacement_entry.php?NewSalesReturn=" . $row["trans_no"],
		ICON_CREDIT
	);
	// return pager_link(
	// 	_("Sales Return"),
	// 	"/sales/sales_return_replacement_entry.php?NewSalesReturn=" . $row["trans_no"],
	// 	ICON_CREDIT
	// );
}

/*Added by Albert */
function print_termode_receipt($row)
{
	if ($row['term_mode_fullpayment'] == 1 && $row['payment_status'] != "fully-paid") {
			return pager_link(
				_("Print to receipt: Termode Full Payment"),
				"/reports/prnt_termode_fullpayment.php?trans_no=" . $row["trans_no"],
				ICON_PRINT
			);
	}
}

//Added by Prog6 6/15/2021
function print_sales_invoice_receipt($row)
{
	if ($row['payment_type'] == "CASH") {
		if ($row['status'] == "Closed") {
			return pager_link(
				_("Print to receipt: Cash Sales Invoice"),
				"/reports/prnt_cash_SalesInvoice.php?SI_num=" . $row["trans_no"],
				ICON_PRINT
			);
		}
	} else if ($row['payment_type'] == "INSTALLMENT") {
		if ($row['status'] == "Open" || $row['status'] == "Approved") {
			return pager_link(
				_("Print to receipt: Charge Sales Invoice"),
				"/reports/prnt_charge_SalesInvoice.php?SI_num=" . $row["trans_no"],
				ICON_PRINT
			);
		}
	}
}
function sales_ct_approval($row) {
	$link = '';
	$void_entry = get_voided_entry($row['type'], $row['trans_no']);

	if ($void_entry['void_status'] == "Voided") {
		$link = '';
	}
	else {
		if ($_SESSION["wa_current_user"]->can_access_page('SA_SALES_CT_APPROVAL')) {

			$link = ($row["status"] == "Close" || $row["status"] == "Closed" || $row["status"] == "fully-paid")  ? '' :  
	
			pager_link(
				'Change Term Approval',
				"/sales/sales_invoice_ct_approval.php?CTNumber=" . $row["trans_no"],
				ICON_DOC
			);
		}
		else {
			$link = null;
		}
	}

	
	return $link;
}
function cancel_link($row)
{
	return pager_link(
		_("Cancel AR"),
		"/sales/sales_order_entry.php?CancelInvoice=" . $row["trans_no"],
		ICON_RECEIVE
	);
}

function category_name($row)
{
	return get_category_name($row["category_id"]);
}

//figure out the sql required from the inputs available
$sql = get_sales_invoices_ct($_POST['search_val'], $_POST['pay_type_id']);

/*show a table of the Request returned by the sql */
$cols = array(
	_("Trans #") => array('fun' => 'trans_view', 'ord' => '', 'align' => 'right'),
	_("Customer"),
	_("Status") => array('insert' => true, 'fun' => 'sales_ct_approval'),
	_("Category") => array('fun' => 'category_name'),
	_("Sales Invoice CT #"),
	_("SI Ref #"),
	_("Invoice CT Date") => array('name' => 'tran_date', 'type' => 'date', 'ord' => 'desc'),
	_("Months Term"),
	_("Gross Amount") => array('align' => 'right', 'fun' => 'fmt_amount'),
	_("LCP") => array('align' => 'right', 'fun' => 'lcp_amount'),
	_("DP") => array('align' => 'right', 'fun' => 'dp_amount'),
	_("Amortiztion") => array('align' => 'right', 'fun' => 'amortization_amount'),
	_("A/R Balance") => array('align' => 'right', 'fun' => 'ar_balance'),
	array('insert' => true, 'fun' => 'gl_view'),
	array('insert' => true, 'fun' => 'print_termode_receipt') 
	// array('insert' => true, 'fun' => 'print_sales_invoice_receipt') //Added by Prog6
);

$table = &new_db_pager('invoice_ct_tbl', $sql, $cols, null, null, 25);
$table->set_marker('check_pending');
$table->width = "90%";

display_db_pager($table);

end_form();
end_page();
