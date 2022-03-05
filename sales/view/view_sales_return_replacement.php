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
include_once($path_to_root . "/sales/includes/cart_class.inc");

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");

include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
    $js .= get_js_open_window(900, 600);

page(_($help_context = "View Sales Return"), true, false, "", $js);

if (isset($_GET["trans_no"])) {
    $trans_id = $_GET["trans_no"];
} elseif (isset($_POST["trans_no"])) {
    $trans_id = $_POST["trans_no"];
}

$header = get_sales_return_replacement($trans_id);
$debt_loans = get_debtor_loans($trans_id); //Added by spyrax10

display_heading(sprintf(_("Sales Return Trans #%d"), $_GET['trans_no']));

echo "<br>";

//Modified by spyrax10
start_outer_table(TABLESTYLE2, "width='95%'");

table_section(1);
table_section_title(_("Customer Information"));
label_row(null, ''); 

label_row(_("Customer's Name: "), $header["name"], "class='tableheader2'");
label_row(_("Cust Branch: "), $header["branch_ref"], "class='tableheader2'");
label_row(null, ''); label_row(null, ''); 
label_row(_("Reason: "), strtoupper($header["remarks"]), "class='tableheader2'");


table_section(2);
table_section_title(_("Transaction Reference"));
label_row(null, ''); 

label_row(_("From Reference #: "), get_trans_view_str($header["trans_type_ref"], $header["trans_no_ref"], $header["trans_ref"]), "class='tableheader2'");
label_row(_("DR No. : "), $debt_loans['delivery_ref_no'], "class='tableheader2'");
label_row(_("Payment Type: "), strtoupper($header["payment_type"]), "class='tableheader2'");


table_section(3);
table_section_title(_("LCP & Cost"));
label_row(null, ''); 

label_row(_("Return LCP Amount: "), price_format($header['total_prev_lcp']), "class='tableheader2'");
label_row(_("Return Unit Cost: "), price_format($header['total_prev_cost']), "class='tableheader2'");
label_row(null, ''); label_row(null, ''); 
label_row(_("Replace LCP Amount: "), price_format($header['total_new_lcp']), "class='tableheader2'");
label_row(_("Replace Unit Cost: "), price_format($header['total_new_cost']), "class='tableheader2'");


table_section(4);
table_section_title(_("Sales Return Information"));
label_row(null, ''); 

label_row(_("Category: "), get_category_name($header["category_id"]), "class='tableheader2'");
label_row(_("SR Type: "), strtoupper($header["sr_item_type"]), "class='tableheader2'");
label_row(_("SR Type: "), sql2date($header["tran_date"]), "class='tableheader2'");
label_row(_("Sales Return #: "), $header["reference"], "class='tableheader2'");
label_row(null, ''); label_row(null, ''); 
label_row(_("Total Payable: "), price_format($header['total_payable']), "class='tableheader2'", $header["total_payable"] > 0 ?
    "style='background-color: #ef3c3c; color: #fdf6f6; font-weight: bold;'" : "");
label_row(_("Total Receivable: "), price_format($header['total_receivable']), "class='tableheader2'", $header["total_receivable"] > 0 ?
    "style='background-color: #ef3c3c; color: #fdf6f6; font-weight: bold;'" : "");

end_outer_table(1);
//
echo "</br>";

$returned_items = get_sr_return_items($trans_id);

display_heading(_("Returned Items"));
echo "</br>";
start_table(TABLESTYLE, "width='95%'");
$th = array(
    _("Item Code"),
    _("Description"),
    _("Color"),
    _("Qty"),
    _("Unit Price"),
    _("Cost"),
    _("Serial No."),
    _("Chassis No.")
);
table_header($th);
$k = 0;    //row colour counter
if (db_num_rows($returned_items) > 0) {
    while ($myrow = db_fetch($returned_items)) {
        alt_table_row_color($k);
        label_cell($myrow["stock_id"]);
        label_cell($myrow["description"]);
        label_cell(get_color_description($myrow["color_code"], $myrow["stock_id"]));
        label_cell(number_format($myrow["quantity"], 2));
        label_cell(number_format($myrow["unit_price"], 2));
        label_cell(number_format($myrow["standard_cost"], 2));
        label_cell($myrow["lot_no"]);
        label_cell($myrow["chassis_no"]);
    }
}
end_table(1);

$replaced_items = get_sr_replace_items($trans_id);

display_heading(_("Replaced Items"));
echo "</br>";
start_table(TABLESTYLE, "width='95%'");
table_header($th);
if (db_num_rows($replaced_items) > 0) {
    while ($myrow = db_fetch($replaced_items)) {
        alt_table_row_color($k);
        label_cell($myrow["stock_id"]);
        label_cell($myrow["description"]);
        label_cell(get_color_description($myrow["color_code"], $myrow["stock_id"]));
        label_cell(number_format($myrow["quantity"], 2));
        label_cell(number_format($myrow["unit_price"], 2));
        label_cell(number_format($myrow["standard_cost"], 2));
        label_cell($myrow["lot_no"]);
        label_cell($myrow["chassis_no"]);
    }
}
end_table(1);

//Added by spyrax10
$voided = is_voided_display(ST_SALESRETURN, $trans_id, _("This invoice has been voided."));

if (!$voided) {
    display_allocations_to(PT_CUSTOMER, $header["debtor_no"], ST_SALESRETURN, $trans_id, $header["total_receivable"]);
}
//

end_page(true, false, false, $_GET['trans_type'], $_GET['trans_no']);
