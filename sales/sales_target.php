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
$page_security = 'SA_SALES_TARGET';
include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

include_once($path_to_root . "/sales/includes/db/sales_target_db.inc");

$js = '';
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

$_SESSION['page_title'] = _($help_context = "Sales Target Setup");

page($_SESSION['page_title'], false, false, "", $js);

//-----------------------------------------------------------------------
start_form();

start_table(TABLESTYLE_NOBORDER);
start_row();
// ahref_cell(_("New Sales Invoice Installment"), "sales_order_entry.php?NewInvoice=0");
// ahref_cell(_("New Sales Invoice Cash"), "sales_invoice_cash.php?NewInvoice=0");
//ref_cells(_("#:"), 'search_val', '', null, '', true);



if (!$page_nested)
	//target_year_list_cells(_("Search Year:"), 'target_year_id', null, _("All Year"), true);
	category_list_cells(_("Category:"), "category_id", null, _("All Categories"),true);
	target_list_cells(_("Target:"), "type_id", null, _("All Types"),true);
	ref_cells(_("Year: "), 'target_year_id', '', null, '', true);
	//payment_terms_type(_("Payment Type:"), "payment_terms", null, _("All Payment Type"),true);
submit_cells('SearchRequest', _("Search"), '', _('Select documents'), 'default');
end_row();
end_table();

if($row["ID"] == "")
{
	$row["ID"] = 0;
}

start_table(TABLESTYLE_NOBORDER);
	start_row();
		echo '<br>';
		ahref_cell(_("New Target Sales Amount/Quantity"), "manage/sales_target_quantity.php?Year_id=".$row["ID"]);
	end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
	start_row();
		echo '<br>';
	end_row();
end_table();

//---------------------------------------------------------------------------------------------
global $Ajax;
 if(get_post('target_year_id') !=''){ div_start('items_table'); }
 if(get_post('category_id') !=''){ div_start('items_table'); }
 if(get_post('type_id') !=''){ div_start('items_table'); }

/*
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

function sales_return_replacement($row)
{	
	return done_check_qty_return_invoice($row["reference"]) || ($row["status"] == "Closed" || $row["status"] == "Close") || ($row["return_status"] == 0 || $row["return_status"] == 2)? '' : pager_link(
			_("Sales Return"),
			"/sales/sales_return_replacement_entry.php?NewSalesReturn=" . $row["trans_no"] . "&& Filter_type=" . $row["type"],
			ICON_CREDIT
		);
}
function sales_return_approval($row)
{
	global $page_nested;
	return done_check_qty_return_invoice($row["reference"]) || ($row["status"] == "Close"|| $row["status"] == "Closed") || ($row["return_status"] == 1 || $row["return_status"] == 2)  ? '' :  pager_link(
		'SR Approval',
		"/sales/sales_return_approval.php?SONumber=" . $row["order_"],
		ICON_DOC
	);
}


//Added by Prog6 6/15/2021
function print_sales_invoice_receipt($row)
{
	if ($row['payment_type'] == "CASH") {
		//modified by spyrax10
		if ($row['status'] == "fully-paid") {
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

	if ($row['payment_type'] == "INSTALLMENT" && $row['downpayment_amount'] == 0) {
		if (get_1stpay_stat($row) == 'paid') {
			return pager_link(
				_("Print to receipt: Charge Sales Invoice"),
				"/reports/prnt_charge_SalesInvoice.php?SI_num=" . $row["trans_no"],
				ICON_PRINT
			);
		}
	}
}

function change_term_link($row)
{
	
	// if ($row['payment_type'] == "INSTALLMENT" && $row["status"] != "Closed") {
	return ($row['payment_type'] == "INSTALLMENT" && ($row["status"] == "Closed" || $row["status"] == "Close")) || $row['payment_type'] == "CASH" ? '' : pager_link(
		_("Change Term"),
		"/sales/sales_order_entry.php?NewChangeTerm=" . $row["trans_no"],
		ICON_RECEIVE
	);
}
// }

function cancel_link($row)
{
	return $row["status"] == "Closed" || $row["status"] == "Close" ? '' : pager_link(
		_("Cancel AR"),
		"/sales/sales_order_entry.php?CancelInvoice=" . $row["trans_no"],
		ICON_RECEIVE
	);
}

*/


function edit_year($row)
{
	$target_id = $row["id"];	

	return pager_link(
		_("View this selection"),
		"/sales/manage/sales_target_quantity.php?Year_id=" . $target_id,
		ICON_VIEW
	);
}

$cat_id = $_POST['category_id'];
$typ_id = $_POST['type_id'];

//figure out the sql required from the inputs available
$sql = get_all_sale_target($_POST['target_year_id'],$_POST['category_id'],$_POST['type_id']);

/*show a table of the Request returned by the sql */
/*
$cols = array(
	_("Trans #") => array('fun' => 'trans_view', 'ord' => '', 'align' => 'right'),
	_("Status"),
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
	array('insert' => true, 'fun' => 'sales_return_replacement'),
	array('insert' => true, 'fun' => 'print_sales_invoice_receipt'), //Added by Prog6
	array('insert' => true, 'fun' => 'change_term_link')
);*/


$cols = array(
	_("ID"),
	_("Year"),
	_("January") => array('align' => 'right', 'type' => 'amount'),
	_("February") => array('align' => 'right', 'type' => 'amount'),
	_("March") => array('align' => 'right', 'type' => 'amount'),
	_("April") => array('align' => 'right', 'type' => 'amount'),
	_("May") => array('align' => 'right', 'type' => 'amount'),
	_("June") => array('align' => 'right', 'type' => 'amount'),
	_("July") => array('align' => 'right', 'type' => 'amount'),
	_("August") => array('align' => 'right', 'type' => 'amount'),	
	_("September") => array('align' => 'right', 'type' => 'amount'),
	_("October") => array('align' => 'right', 'type' => 'amount'),
	_("November") => array('align' => 'right', 'type' => 'amount'),
	_("December") => array('align' => 'right', 'type' => 'amount'),
	_("Target") => array('align' => 'center'),	
	_("Category") => array('align' => 'center'),
	 array('insert' => true, 'fun' => 'edit_year')
);

$table = &new_db_pager('', $sql, $cols, null, null, 15);
$table->width = "99%";

display_db_pager($table);






// INPUT FEILDS TEXTBOX
//start_table(TABLESTYLE2);

//label_row(_("Category Value:"),$_POST['category_id']);
//label_row(_("Target:"),$_POST['type_id']);
/*
if($_POST['category_id'] != "-1" && $_POST['type_id'] != "-1")
{
	start_row();

	category_list_cells(_("Category:"), "category_id", 14, null,false);
	target_list_cells(_("Target:"), "type_id", 'amount', null,false);
	text_row_ar(_("ID #:"), 'target_id', '', null, '', true);
	text_row_ar(_("Year:"), 'target_year', '', null, '', true);
	text_row_ar(_("January:"), 't_jan', '', null, '', true);
	text_row_ar(_("February:"), 't_feb', '', null, '', true);
	text_row_ar(_("March:"), 't_mar', '', null, '', true);
	text_row_ar(_("April:"), 't_apr', '', null, '', true);
	text_row_ar(_("May:"), 't_may', '', null, '', true);
	text_row_ar(_("June:"), 't_jun', '', null, '', true);
	text_row_ar(_("July:"), 't_jul', '', null, '', true);
	text_row_ar(_("August:"), 't_aug', '', null, '', true);
	text_row_ar(_("September:"), 't_sep', '', null, '', true);
	text_row_ar(_("October:"), 't_oct', '', null, '', true);
	text_row_ar(_("November:"), 't_nov', '', null, '', true);
	text_row_ar(_("December:"), 't_dece', '', null, '', true);

	end_row();
}*/


//end_table(1);
//---------------------------------------------------------------------------------

end_form();
end_page();
