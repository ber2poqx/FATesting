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
include($path_to_root . "/purchasing/includes/consign_class.inc");

include($path_to_root . "/includes/session.inc");
include($path_to_root . "/purchasing/includes/purchasing_ui.inc");


$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
page(_($help_context = "View Consignment"), true, false, "", $js);


if (!isset($_GET['trans_no'])) {
	die("<br>" . _("This page must be called with a Consignment number to review."));
}

display_heading(_("Consignment") . " #" . $_GET['trans_no']);

$consignment = new receive_consignment;

read_consignment($_GET['trans_no'], $consignment);
echo "<br>";

display_consign_summary($consignment, true);

start_table(TABLESTYLE, "width='90%'", 6);
echo "<tr><td valign=top>"; // outer table

display_heading2(_("Line Details"));

start_table(TABLESTYLE, "width='100%'");

$th = array(_("Quantity"), _("Item Code"), _("Item Description"), _("Color Description - (Code)"));

if ($consignment->category_id != 14)
	array_remove($th, 4);

table_header($th);
$total = $k = 0;
$overdue_items = false;

foreach ($consignment->line_items as $stock_item) {

	$dec = get_qty_dec($stock_item->stock_id);
	qty_cell($stock_item->quantity, false, $dec);
	label_cell($stock_item->stock_id);
	label_cell($stock_item->item_description);
	if ($consignment->category_id == 14)
		label_cell(get_color_description($stock_item->color_code, $stock_item->stock_id));

	end_row();
}

end_table();

echo "</td></tr>";

end_table(1); // outer table

end_page(true, false, false, ST_PURCHREQUEST, $_GET['trans_no']);
