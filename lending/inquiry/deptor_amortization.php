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
/**********************************************************************
  Page for searching customer list and select it to customer selection
  in pages that have the supplier dropdown lists.
  Author: bogeyman2007 from Discussion Forum. Modified by Joe Hunt
***********************************************************************/
$page_security = "SA_SALESORDER";
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/lending/includes/db/invoice_inquiry_db.inc");
include_once($path_to_root . "/lending/includes/lending_cfunction.inc");

page(_($help_context = "Customer Amortization"), true, null);
start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE, "width='50%'");

table_section_title(_("Customer Amortization Schedule"));
$datenow = date("Y-m-d");

if(isset($_GET['Type']) AND isset($_GET['debtor_no']) AND isset($_GET['invoice_no'])){

	$result = get_deptor_loan_schedule($_GET['invoice_no'], $_GET['debtor_no'], $_GET['Type']);
	$loanrow = db_fetch($result);
	
	//echo "<center><font color=red size=4><b>" . _("CUSTOMER ACCOUNT IS ON HOLD") . "</font></b></center>";
	
	label_row(_("Invoice Number:"), $loanrow["reference"]); 
	label_row(_("Customer Name:"), $loanrow["debtor_ref"] .'-'. $loanrow["name"]);

}else{

	$result = get_debtor_per_transNo($_GET['invoice_no']);
	$invoicerow = db_fetch($result);

	label_row(_("Invoice Number:"), $invoicerow["invoice_ref_no"]); 
	label_row(_("Customer Name:"), $invoicerow["debtor_ref"] .' - '. $invoicerow["name"]);
}

end_table();

end_form();

div_start("customer_tbl");

start_table(TABLESTYLE, "width='50%'");

$th = array(_("No."), _("Due Date"), _("Week Day"), _("Amortization"), _("Run Balance"), _("Total Amort."), 
 _("Run Total Amort."));

table_header($th);

$k = 0;

if(isset($_GET['Type']) AND isset($_GET['debtor_no']) AND isset($_GET['invoice_no'])){
	//while ($myrow = db_fetch($result)) {
	foreach($result as $myrow) {
		alt_table_row_color($k);

		label_cell($myrow["month_no"]);
		label_cell($myrow["date_due"]);
		label_cell($myrow["weekday"]);
		label_cell(number_format2($myrow["principal_due"]));
		label_cell(number_format2($myrow["principal_runbal"]));
		label_cell(number_format2($myrow["total_principaldue"]));
		label_cell(number_format2($myrow["total_runbal"]));
		end_row();
	}

}else{
    $arry_result = array_Amortization($invoicerow["months_term"], $invoicerow["amortization_amount"], $invoicerow["outstanding_ar_amount"],
                        $invoicerow["firstdue_date"], $datenow, $invoicerow["ar_amount"], $invoicerow["downpayment_amount"]);
    
	foreach($arry_result as $item) {
		alt_table_row_color($k);

		label_cell($item["no"]);
		label_cell($item["datedue"]);
		label_cell($item["weekday"]);
		label_cell(number_format2($item["amortization"]));
		label_cell(number_format2($item["runbalance"]));
		label_cell(number_format2($item["totalamort"]));
		label_cell(number_format2($item["runtotalamort"]));
		end_row();
	}
}

end_table(1);

div_end();

end_page(true);
