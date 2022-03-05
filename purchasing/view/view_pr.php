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
$page_security = 'SA_SUPPTRANSVIEW';
$path_to_root = "../..";
include($path_to_root . "/purchasing/includes/pr_class.inc");

include($path_to_root . "/includes/session.inc");
include($path_to_root . "/purchasing/includes/purchasing_ui.inc");


$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
page(_($help_context = "View Purchase Request"), true, false, "", $js);


if (!isset($_GET['trans_no'])) {
	die("<br>" . _("This page must be called with a purchase Request number to review."));
}

display_heading(_("Purchase Request") . " #" . $_GET['trans_no']);

$purchase_request = new purch_request;
if (isset($_GET['branch_coy'])) {
	set_global_connection($_GET['branch_coy']);
}
read_pr($_GET['trans_no'], $purchase_request);
echo "<br>";

display_pr_summary($purchase_request, true);

start_table(TABLESTYLE, "width='90%'", 6);
echo "<tr><td valign=top>"; // outer table

display_heading2(_("Line Details"));

start_table(TABLESTYLE, "width='100%'");

$th = array(_("Item Code"), _("Description"), _("Color Description - (Code)"), _("Quantity"), _("Quantity Ordered"));

if ($purchase_request->category_id != 14)
	array_remove($th, 2);

table_header($th);
$total = $k = 0;
$overdue_items = false;

foreach ($purchase_request->line_items as $stock_item) {

	$dec = get_qty_dec($stock_item->stock_id);
	label_cell($stock_item->stock_id);
	label_cell($stock_item->item_description);
	if ($purchase_request->category_id == 14)
		label_cell(get_color_description($stock_item->color_code, $stock_item->stock_id));
	qty_cell($stock_item->quantity, false, $dec);
	qty_cell($stock_item->qty_ordered);



	end_row();
}

end_table();

echo "</td></tr>";

end_table(1); // outer table

end_page(true, false, false, ST_PURCHREQUEST, $_GET['trans_no']);
