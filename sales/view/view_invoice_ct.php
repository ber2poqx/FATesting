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
page(_($help_context = "View Sales Invoice CT"), true, false, "", $js);


if (isset($_GET["trans_no"])) {
	$trans_id = $_GET["trans_no"];
} elseif (isset($_POST["trans_no"])) {
	$trans_id = $_POST["trans_no"];
}

// 3 different queries to get the information - what a JOKE !!!!

$header = get_sales_invoice_ct_header($trans_id);
global $db_connections;
$coy = user_company();
$db_branch_type = $db_connections[$coy]['type'];
		
if($db_branch_type == 'LENDING'){
	$type = ST_ARINVCINSTLITM;	
}else{
	if ($header["invoice_type"] == 'repo'){
		$type = ST_SALESINVOICEREPO;
	} else {
		$type = ST_SALESINVOICE;
	}
}
$myrow = get_customer_trans($header["si_trans_no"], $type);

$branch = get_branch($myrow["branch_code"]);
$sales_order = get_sales_order_header($myrow["order_"], ST_SALESORDER);
$amortization_schedule = get_deptor_loan_schedule_ob($trans_id, $header["debtor_no"], ST_SITERMMOD);

display_heading(sprintf(_("SALES INVOICE CT #%d"), $trans_id));

echo "<br>";
start_table(TABLESTYLE2, "width='95%'");
echo "<tr valign=top><td>"; // outer table

start_table(TABLESTYLE, "width='100%'");

start_row();

label_cells(_("Sales Invoice CT #"), $header["si_ref"] . " -> " . $header["si_ct_no"], "class='tableheader2'");
label_cells(_("Category"), $header["category"], "class='tableheader2'");
label_cells(_("Invoice Date"), sql2date($header["invoice_date"]), "class='tableheader2'", "nowrap"); //Added by spyrax10

end_row();
start_row();

label_cells(_("Customer Name"), $myrow["DebtorName"], "class='tableheader2'");
label_cells(_("Status"), $header["status"], "class='tableheader2'");
label_cells(_("Invoice CT Date"), sql2date($header["invoice_ct_date"]), "class='tableheader2'", "nowrap");
end_row();

start_row();

label_cells(
	_("SO Trans #"),
	get_customer_trans_view_str(ST_SALESORDER, $sales_order["order_no"]),
	"class='tableheader2'"
);
label_cells(_("Payment Type"), "INSTALLMENT", "class='tableheader2'");
label_cells(_("Invoice Type"), $header["invoice_type"], "class='tableheader2'");

end_row();

start_row();
/*-----Modified by Albert*/
label_cells(_("DR #"), $header["delivery_ref_no"], "class='tableheader2'");
if ($myrow['prep_amount'] == 0)
	label_cells(_("Deliveries"), get_customer_trans_view_str(
		ST_CUSTDELIVERY,
		get_sales_parent_numbers($type, $header["si_trans_no"])
	), "class='tableheader2'");
label_cells(_("WRC/EW Code"), $header["warranty_code"], "class='tableheader2'");
end_row();

start_row();

label_cells(_("Sales Person"), $sales_order["salesman_name"], "class='tableheader2'");
label_cells(_("Sales Type"), $sales_order["sales_type_name"], "class='tableheader2'");
label_cells(_("FSC Series"), $header["fsc_series"], "class='tableheader2'");
end_row();

start_row();

label_cells(_("Co-maker"), $header["co_maker"], "class='tableheader2'", "colspan='3'");
end_row();

comments_display_row(ST_SITERMMOD, $trans_id);
end_table();

echo "<br>";
start_table(TABLESTYLE, "width='100%'");

start_row();

$total_discount_amount = $header["discount_downpayment"] + $header["discount_downpayment2"];
label_cells(_("DP Amount"), price_format($header["downpayment_amount"]), "class='tableheader2'");
label_cells(_("Total Discount DP Amount"), price_format($total_discount_amount), "class='tableheader2'");
label_cells(_("LCP"), price_format($header["lcp_amount"]), "class='tableheader2'");
end_row();

start_row();
/*Added by Albert*/
if($header['opening_balances']==1){
	$total_unit_cost = $header['standard_cost'];
}else{
$total_unit_cost = get_cost_of_sales_for_si($header['delivery_ref_no']);
}

$half = 0.5 / pow(10, 2); 
$deferred_gross_profit = $header["new_ar_amount"] - $total_unit_cost;
$profit_margin =  round(number_format($deferred_gross_profit / $header["new_ar_amount"], 5) - $half,2,PHP_ROUND_HALF_DOWN);
$total_dgp = $header["total_payment"] * $profit_margin;
$dgp_bal = $deferred_gross_profit - $total_dgp;

$old_deferred_gross_profit = $header["old_ar_amount"] - $total_unit_cost;
$old_profit_margin =  $header["profit_margin"];
$old_total_dgp = $header["total_payment"] * $old_profit_margin;
$old_dgp_bal = $old_deferred_gross_profit - $old_total_dgp;

label_cells(_("Category ID"), $header["category_id"], "class='tableheader2'");
label_cells(_("Payment Location"), $header["payment_location"], "class='tableheader2'");
label_cells(_("Total Unit Cost"), price_format($total_unit_cost), "class='tableheader2'");
end_row();
/*-----end by Albert */
end_table();

echo "<br>";
start_table(TABLESTYLE, "width='100%'");

start_row();
label_cells(_("New First Due Date"), date('m/d/Y', strtotime($header["new_firstdue"])), "class='tableheader2'");
label_cells(_("Old First Due Date"), date('m/d/Y', strtotime($header["old_firstdue"])), "class='tableheader2'");
end_row();

start_row();
label_cells(_("New Maturity Date"), date('m/d/Y', strtotime($header["new_maturity"])), "class='tableheader2'");
label_cells(_("Old Maturity Date"), date('m/d/Y', strtotime($header["old_maturity"])), "class='tableheader2'");
end_row();
/*-----Added by Albert*/
start_row();

label_cells(_("New Payment Terms"), $header["new_payment_terms"], "class='tableheader2'");
label_cells(_("Old Payment Terms"), $header["old_payment_terms"], "class='tableheader2'");

end_row();
/*-----end by Albert */
start_row();
label_cells(_("New Months Term"), $header["new_months_term"], "class='tableheader2'");
label_cells(_("Old Months Term"), $header["old_months_term"], "class='tableheader2'");
end_row();

start_row();
label_cells(_("New Rebate"), price_format($header["new_rebate"]), "class='tableheader2'");
label_cells(_("Old Rebate"), price_format($header["old_rebate"]), "class='tableheader2'");
end_row();

start_row();
label_cells(_("New Financing Rate"), price_format($header["new_financing_rate"]) . "%", "class='tableheader2'");
label_cells(_("Old Financing Rate"), price_format($header["old_financing_rate"]) . "%", "class='tableheader2'");
end_row();

start_row();
label_cells(_("New Amortization"), price_format($header["new_amort"]), "class='tableheader2'");
label_cells(_("Old Amortization"), price_format($header["old_amort"]), "class='tableheader2'");
end_row();

start_row();
label_cells(
	_("New Gross"),
	price_format($header["new_ar_amount"]),
	"class='tableheader2'",
	"style='font-weight: bold;'"
);
label_cells(_("Old Gross"), price_format($header["old_ar_amount"]), "class='tableheader2'");
end_row();
/*Modified by Albert*/
start_row();
$old_ar_balace = price_format($header["old_ar_amount"] - $header["total_payment"]);
label_cells(_("A/R Balance"), price_format($header["outstanding_ar_amount"]), "class='tableheader2'");
label_cells(_("OLD A/R Balance"), $old_ar_balace, "class='tableheader2'");

end_row();

start_row();

label_cells(_("Total Payment"), price_format($header["total_payment"]), "class='tableheader2'");
label_cells(_("Old Total Payment"), price_format($header["total_payment"]), "class='tableheader2'");

end_row();

start_row();

label_cells(_("Profit Margin"), price_format($profit_margin), "class='tableheader2'");
label_cells(_("Old Profit Margin"), price_format($old_profit_margin), "class='tableheader2'");

end_row();

start_row();

label_cells(_("New DGP"), price_format($deferred_gross_profit), "class='tableheader2'");
label_cells(_("Old DGP"), price_format($old_deferred_gross_profit), "class='tableheader2'");

end_row();

start_row();

label_cells(_("New Total Generated DGP from Payment"), price_format($total_dgp), "class='tableheader2'");
label_cells(_("Old Total Generated DGP from Payment"), price_format($old_total_dgp), "class='tableheader2'");

end_row();

start_row();

label_cells(_("New DGP Balance"), price_format($dgp_bal), "class='tableheader2'");
label_cells(_("Old DGP Balance"), price_format($old_dgp_bal), "class='tableheader2'");
/*-----end by Albert*/
end_row();

end_table();
echo "<br>";
start_table(TABLESTYLE, "width='100%'");

start_row();
label_cells(_("Amortization Diff"), price_format($header["amort_diff"]), "class='tableheader2'");
label_cells(_("No. of Months Paid"), $header["months_paid"], "class='tableheader2'");
label_cells(_("Amortization Delay"), price_format($header["amort_delay"]), "class='tableheader2'");
end_row();

start_row();
label_cells(_("Adjustment Rate"), $header["adj_rate"] . "%", "class='tableheader2'");
label_cells(_("Opportunity Cost"), price_format($header["opportunity_cost"]), "class='tableheader2'");
label_cells(
	_("Amortization Delay Status"),
	$header["amount_to_be_paid_status"],
	"class='tableheader2'",
	$header["amount_to_be_paid_status"] == "unpaid"
		? "style='font-weight: bold; background-color: red;'"
		: "style='font-weight: bold; background-color: green;'"
);
end_row();

start_row();
label_cells(
	_("Amount to be Paid"),
	price_format($header["amount_to_be_paid"]),
	"class='tableheader2'",
	$header["amount_to_be_paid_status"] == "unpaid"
		? "style='font-weight: bold; background-color: red;'"
		: "style='font-weight: bold; background-color: green;'"
);
end_row();

end_table();

echo "</td></tr>";
end_table(1); // outer table


$result = get_customer_trans_details($type, $header["si_trans_no"]);

display_heading(_("Items"));
start_table(TABLESTYLE, "width='95%'");

if (db_num_rows($result) > 0) {
	$th = array(
		_("Item Code"), _("Item Description"), _("Serial/Eng Num"), _("Chassis Num"), _("Color"), _("Quantity"),
		_("Unit"), _("Unit Price"), _("Unit Cost"), _("Discount"), _("Other Discount"), _("Line Total")
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

// $tax_items = get_trans_tax_details(ST_SITERMMOD, $trans_id);
// display_customer_trans_tax_details($tax_items, 6);

// $display_total = price_format($myrow["ov_freight"]+$myrow["ov_gst"]+$myrow["ov_amount"]+$myrow["ov_freight_tax"]);

// label_row(_("TOTAL INVOICE"), $display_total, "colspan=6 align=right",
// 	"nowrap align=right");
// if ($myrow['prep_amount'])
// 	label_row(_("PREPAYMENT AMOUNT INVOICED"), '<b>'.price_format($myrow['prep_amount']).'</b>', "colspan=6 align=right",
// 		"nowrap align=right");
end_table(1);

$voided = is_voided_display(ST_SITERMMOD, $trans_id, _("This invoice has been voided."));

if (!$voided) {
	display_allocations_to(PT_CUSTOMER, $myrow['debtor_no'], ST_SITERMMOD, $trans_id, $myrow['Total']);
}

display_heading(_("Amortization Schedule"));
start_table(TABLESTYLE, "width='95%'");
$th = array(
	_("No."), ("Status"), _("Due Date"), _("Week Day"), _("Principal Due"), _("Principal Run Bal."), _("Total Principal Due"), _("Total Principal Run Bal.")
);
table_header($th);
while ($amort_sched = db_fetch($amortization_schedule)) {
	label_cell($amort_sched["month_no"]);
	label_cell($amort_sched["status"]);
	label_cell($amort_sched["date_due"]);
	label_cell($amort_sched["weekday"]);
	label_cell(price_format($amort_sched["principal_due"]));
	label_cell(price_format($amort_sched["principal_runbal"]));
	label_cell(price_format($amort_sched["total_principaldue"]));
	label_cell(price_format($amort_sched["total_runbal"]));
	end_row();
}
end_table(1);


end_page(true, false, false, ST_SITERMMOD, $trans_id);
