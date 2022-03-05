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
$page_security = 'SA_ITEMSTRANSVIEW';
$path_to_root = "../..";

include($path_to_root . "/includes/session.inc");

page(_($help_context = "View Inventory Adjustment"), true);

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");

if (isset($_GET["trans_no"]))
{
	$trans_no = $_GET["trans_no"];
}

//Modified by spyrax10
$title = smo_exists($trans_no, ST_INVADJUST) ? "" : "Pending ";
$sub_title = is_invty_open_bal($trans_no, '') ? "Inventory Opening" :
	 $systypes_array[ST_INVADJUST];
$ext = is_smo_repo($trans_no, ST_INVADJUST) ? " (Repo) " . " #$trans_no" : " #$trans_no";

display_heading($title . $sub_title . $ext);

br(1);
$adjustment_items = get_stock_adjustment_items($trans_no);
$k = $total = 0;
$header_shown = false;
while ($adjustment = db_fetch($adjustment_items))
{
	$sub_total = abs($adjustment['qty']) * $adjustment['standard_cost'];
	$total += $sub_total;

	if (!$header_shown)
	{

		start_table(TABLESTYLE2, "width='90%'");
		start_row();
		label_cells(_("At Location"), $adjustment['location_name'], "class='tableheader2'");
    	label_cells(_("Reference"), $adjustment['reference'], "class='tableheader2'", "colspan=6");
		label_cells(_("Date"), sql2date($adjustment['tran_date']), "class='tableheader2'");
		label_cells(_("Item Type: "), strtoupper($adjustment['item_type']), "class='tableheader2'"); //Added by spyrax10
		end_row();

		//Added by spyrax10
		if (!smo_exists($trans_no, ST_INVADJUST)) {
			if ($adjustment['status'] == 'Disapproved') {
				start_row();
				label_cells(_("Remarks: "), $adjustment['comments'], "class='tableheader2'", "colspan=6");
				end_row();
			}	
		}

		comments_display_row(ST_INVADJUST, $trans_no);

		end_table();
		$header_shown = true;

		echo "<br>";
		start_table(TABLESTYLE, "width='100%'");

    	$th = array(_("Item Code"), _("Description"), _("Color"), _("Qty"),
    		_("Units"), _("Serial #"), _("Chassis #"), _("Unit Cost"), _("Sub Total"));
    	table_header($th);
	}

    alt_table_row_color($k);

    label_cell($adjustment['stock_id']);
    label_cell($adjustment['description']);
	label_cell($adjustment['color_desc']);
    label_cell($adjustment['qty'], "align='center'");
    label_cell($adjustment['units']);
	label_cell($adjustment['lot_no']);
	label_cell($adjustment['chassis_no']);
    amount_decimal_cell($adjustment['standard_cost']);
	amount_decimal_cell($sub_total);
    end_row();
}

label_row(_("Document Total: "), number_format2($total,user_price_dec()), 
			"align=right colspan=8; style='font-weight:bold';", "style='font-weight:bold'; align=right", 0);
end_table(1);

is_voided_display(ST_INVADJUST, $trans_no, _("This adjustment has been voided."));

end_page(true, false, false, ST_INVADJUST, $trans_no);
