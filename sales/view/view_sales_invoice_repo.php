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
$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/sales/includes/sales_ui.inc");

include_once($path_to_root . "/sales/includes/sales_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 600);
page(_($help_context = "View Sales Invoice"), true, false, "", $js);


if (isset($_GET["trans_no"])) {
	$trans_id = $_GET["trans_no"];
} elseif (isset($_POST["trans_no"])) {
	$trans_id = $_POST["trans_no"];
}

// 3 different queries to get the information - what a JOKE !!!!

$header =get_sales_invoice_header_repo($trans_id);

$myrow = get_customer_trans($trans_id, ST_SALESINVOICEREPO);
$paym = get_payment_terms($myrow['payment_terms']);
$branch = get_branch($myrow["branch_code"]);

//Added by spyrax10
$debt_loans = get_debtor_loans($trans_id, 'repo');
$dr_ref = $debt_loans['dr_ref_no'] != ''? $debt_loans['dr_ref_no'] : $debt_loans['delivery_ref_no'];
$total_unit_cost = $header["opening_balances"] == 0 ? get_cost_of_sales_for_si($header['delivery_ref_no']) : 
	get_ob_standard_cost($trans_id);
$payment_location_id = get_company_pref('payment_location');
//

$sales_order = null;
$amortization_schedule = null;
if ($header["opening_balances"] == 0) {
	$sales_order = get_sales_order_header($myrow["order_"], ST_SALESORDER);
}

if ($header["payment_type"] == "INSTALLMENT")
	$amortization_schedule = get_deptor_loan_schedule_ob($trans_id, $header["debtor_no"], ST_SALESINVOICEREPO);

display_heading(sprintf($myrow['prep_amount'] > 0 ? ($paym['days_before_due'] >= 0 ? _("FINAL INVOICE #%d") : _("PREPAYMENT INVOICE #%d")) : _("SALES INVOICE TRANS #%d"), $trans_id));

echo "<br>";

//Modified by spyrax10
start_outer_table(TABLESTYLE2, "width='95%'");

table_section(1);

label_row(_("Customer's Name: "), $myrow["DebtorName"], "class='tableheader2'");
label_row(_("Cust Branch: "), $header["branch_ref"], "class='tableheader2'");
label_row(null, ''); label_row(null, ''); label_row(null, '');
//label_row(_("SO Trans #: "), get_customer_trans_view_str(ST_SALESORDER, $sales_order["order_no"]), "class='tableheader2'");
label_row(_("Reference No. #:"), $header["si_no"], "class='tableheader2'");
label_row(_("DR No. : "), $header["category_id"] == 14 ? $debt_loans['delivery_ref_no'] : '', "class='tableheader2'");
label_row(_("Sales Invoice #: "), $debt_loans['ref_no'], "class='tableheader2'");
label_row(_("Delivery Ref. : "), $dr_ref, "class='tableheader2'");
label_row(null, ''); label_row(null, '');
label_row("SO Date: ", sql2date($myrow['tran_date']), "class='tableheader2'");
label_row("Invoice Date: ", sql2date($header["invoice_date"]), "class='tableheader2'");
label_row(null, ''); label_row(null, '');
label_row(_("Sales Person: "), $header['sales_type'], "class='tableheader2'");
label_row(_("Sale Type: "), $header['salesman_name'], "class='tableheader2'");
label_row(_("Co-maker: "), $header['co_maker_name'], "class='tableheader2'");

label_row(_("Previous Owner: "), get_customer_name($sales_order['previous_owner']), "class='tableheader2'");
table_section(2);
// label_row(_("WRC/EW Code: "), $header["warranty_code"], "class='tableheader2'");
label_row(_("FSC Series: "), $header["fsc_series"], "class='tableheader2'");
label_row(_("Category: "), get_category_name($header["category_id"]), "class='tableheader2'");
label_row(null, ''); label_row(null, '');
label_row(_("Downpayment: "), price_format($debt_loans['downpayment_amount']), "class='tableheader2'");
label_row(_("Discount DP: "), price_format($debt_loans['discount_downpayment']), "class='tableheader2'");
label_row(_("Discount DP2: "), price_format($debt_loans['discount_downpayment2']), "class='tableheader2'");
label_row(null, ''); label_row(null, '');
label_row("First Due Date: ", sql2date($debt_loans['firstdue_date']), "class='tableheader2'");
label_row("Maturity Date: ", sql2date($debt_loans['maturity_date']), "class='tableheader2'");
label_row(null, ''); label_row(null, '');
label_row(_("Total Unit Cost: "), price_format($total_unit_cost), "class='tableheader2'");
label_row(_("Deferred Gross Profit: "), price_format($header["deferred_gross_profit"]), "class='tableheader2'");
label_row(_("Profit Margin: "), price_format($header["profit_margin"]), "class='tableheader2'");
label_row(_("Payment Location: "), $payment_location_id ? "Lending" : "Branch", "class='tableheader2'");

table_section(3);
label_row(_("Payment Term: "), get_policy_name($debt_loans['installmentplcy_id'], $header["category_id"]), "class='tableheader2'");
label_row(_("Months Term: "), $debt_loans['months_term'], "class='tableheader2'");
label_row(_("Rebate: "), price_format($debt_loans['rebate']), "class='tableheader2'");
label_row(_("Financing Rate: "), $debt_loans['financing_rate']. "%", "class='tableheader2'");
label_row(_("Due/Amortization: "), price_format($debt_loans['amortization_amount']), "class='tableheader2'");
label_row(null, ''); label_row(null, '');
//label_row(_("LCP Amount: "), price_format($debt_loans['lcp_amount']), "class='tableheader2'");
label_row(_("A/R Amount: "), price_format($debt_loans['ar_amount']), "class='tableheader2'");

end_outer_table(1);
//

$result = get_customer_trans_details(ST_SALESINVOICEREPO, $trans_id);

display_heading(_("Items"));
start_table(TABLESTYLE, "width='95%'");

if (db_num_rows($result) > 0) {
	$th = array(
		_("Item Code"), _("Item Description"), _("Serial/Eng Num"), _("Chassis Num"), _("Color"), _("Quantity"),
		_("Unit"), _("Unit Price"), _("Unit Cost"), _("SMI"), _("Incentives"), _("Discount"), _("Other Discount"), _("Line Total")
	);
	table_header($th);

	$k = 0;	//row colour counter
	$sub_total = 0;
	while ($myrow2 = db_fetch($result)) {
		if ($myrow2["quantity"] == 0) continue;
		alt_table_row_color($k);

		$value = round2(((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]),
			user_price_dec()
		);
		$sub_total += $value;

		if ($myrow2["discount_percent"] == 0) {
			$display_discount = "";
		} else {
			$display_discount = percent_format($myrow2["discount_percent"] * 100) . "%";
		}

		$value -= $myrow2["discount1"] + $myrow2["discount2"];

		label_cell($myrow2["stock_id"]);
		label_cell($myrow2["StockDescription"]);
		label_cell($myrow2["lot_no"]);
		label_cell($myrow2["chassis_no"]);
		label_cell(get_color_description($myrow2["color_code"], $myrow2["stock_id"]));
		qty_cell($myrow2["quantity"], false, get_qty_dec($myrow2["stock_id"]));
		label_cell($myrow2["units"], "align=right");
		amount_cell($myrow2["unit_price"]);
		amount_cell($myrow2["standard_cost"]);
		amount_cell($myrow2["smi"]);
		amount_cell($myrow2["incentives"]);
		amount_cell($myrow2["discount1"]);
		amount_cell($myrow2["discount2"]);
		amount_cell($value);
		end_row();
	} //end while there are line items to print out

	// $display_sub_tot = price_format($sub_total);
	// label_row(_("Sub-total"), $display_sub_tot, "colspan=6 align=right",
	// 	"nowrap align=right width='15%'");
} else
	display_note(_("There are no line items on this invoice."), 1, 2);

/*Print out the invoice text entered */
// if ($myrow['ov_freight'] != 0.0)
// {
// 	$display_freight = price_format($myrow["ov_freight"]);
// 	label_row(_("Shipping"), $display_freight, "colspan=6 align=right", "nowrap align=right");
// }

// $tax_items = get_trans_tax_details(ST_SALESINVOICEREPO, $trans_id);
// display_customer_trans_tax_details($tax_items, 6);

// $display_total = price_format($myrow["ov_freight"]+$myrow["ov_gst"]+$myrow["ov_amount"]+$myrow["ov_freight_tax"]);

// label_row(_("TOTAL INVOICE"), $display_total, "colspan=6 align=right",
// 	"nowrap align=right");
// if ($myrow['prep_amount'])
// 	label_row(_("PREPAYMENT AMOUNT INVOICED"), '<b>'.price_format($myrow['prep_amount']).'</b>', "colspan=6 align=right",
// 		"nowrap align=right");
end_table(1);

$voided = is_voided_display(ST_SALESINVOICEREPO, $trans_id, _("This invoice has been voided."));

if (!$voided) {
	display_allocations_to(PT_CUSTOMER, $myrow['debtor_no'], ST_SALESINVOICEREPO, $trans_id, $myrow['Total']);
}


if ($header["payment_type"] == "INSTALLMENT") {
	display_heading(_("Amortization Schedule"));
	start_table(TABLESTYLE, "width='95%'");
	$th = array(
		_("No."), _("Due Date"), _("Week Day"), _("Principal Due"), _("Principal Run Bal."), _("Total Principal Due"), _("Total Principal Run Bal.")
	);
	table_header($th);
	while ($amort_sched = db_fetch($amortization_schedule)) {

		label_cell($amort_sched["month_no"]);
		label_cell($amort_sched["date_due"]);
		label_cell($amort_sched["weekday"]);
		label_cell(price_format($amort_sched["principal_due"]));
		label_cell(price_format($amort_sched["principal_runbal"]));
		label_cell(price_format($amort_sched["total_principaldue"]));
		label_cell(price_format($amort_sched["total_runbal"]));
		end_row();
	}
	end_table(1);
}


end_page(true, false, false, ST_SALESINVOICEREPO, $trans_id);
