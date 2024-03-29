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
$page_security = 'SA_FORITEMCODE';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");

$js = "";
if ($SysPrefs->use_popup_windows && $SysPrefs->use_popup_search)
	$js .= get_js_open_window(900, 500);

page(_($help_context = "Item Color Code"), false, false, "", $js);

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

//check_db_has_purchasable_items(_("There are no inventory items defined in the system."));

simple_page_mode(true);
//--------------------------------------------------------------------------------------------------

if ($Mode == 'ADD_ITEM' || $Mode == 'UPDATE_ITEM') {

	$input_error = 0;
	$item_code = $_POST['item_code'] . "-" . $_POST['color'];
	if ($_POST['stock_id'] == "" || !isset($_POST['stock_id'])) {
		$input_error = 1;
		display_error(_("There is no item selected."));
		set_focus('stock_id');
	} elseif (!input_num('quantity')) {
		$input_error = 1;
		display_error(_("The quantity entered was not positive number."));
		set_focus('quantity');
	} elseif ($_POST['description'] == '') {
		$input_error = 1;
		display_error(_("Item code description cannot be empty."));
		set_focus('description');
	} elseif ($selected_id == -1) {
		$kit = get_item_kit($item_code);
		if (db_num_rows($kit)) {
			$input_error = 1;
			display_error(_("This item code is already assigned to stock item or sale kit."));
			set_focus('item_code');
		}
	}

	if ($input_error == 0) {
		if ($Mode == 'ADD_ITEM') {
			$currentdate = date('Y-m-d');
			add_item_code(
				$item_code,
				$_POST['color'],
				$_POST['stock_id'],
				$_POST['description'],
				$_POST['pnp_color'],
				$_POST['category_id'],
				$_POST['quantity'],
				1, 0, 0, 0, 0, 0, $_POST['old_code'], $currentdate
			);

			display_notification(_("New item code has been added."));
		} else {
			$currentdate = date('Y-m-d');
			update_item_code(
				$selected_id,
				$item_code,
				$_POST['color'],
				$_POST['stock_id'],
				$_POST['description'],
				$_POST['pnp_color'],
				$_POST['category_id'],
				$_POST['quantity'],
				1, 0, 0, 0, 0, 0, $_POST['old_code'], $currentdate
			);

			display_notification(_("Item code has been updated."));
		}
		$Mode = 'RESET';
	}
}

//--------------------------------------------------------------------------------------------------

if ($Mode == 'Delete') {
	delete_item_code($selected_id);

	display_notification(_("Item code has been sucessfully deleted."));
	$Mode = 'RESET';
}

if ($Mode == 'RESET') {
	$selected_id = -1;
	unset($_POST);
}

if (list_updated('stock_id'))
	$Ajax->activate('_page_body');

//--------------------------------------------------------------------------------------------------

start_form();

if (!isset($_POST['stock_id']))
	$_POST['stock_id'] = get_global_stock_item();

echo "<center>" . _("Item:") . "&nbsp;";
//Manufcatured item visible
echo stock_items_list('stock_id', $_POST['stock_id'], false, true);

echo "<hr></center>";

set_global_stock_item($_POST['stock_id']);

$result = get_item_code_dflts($_POST['stock_id']);
$dec = $result['decimals'];
$units = $result['units'];
$dflt_desc = $result['description'];
$dflt_cat = $result['category_id'];

$result = get_all_item_codes($_POST['stock_id'], 1);
div_start('code_table');
start_table(TABLESTYLE, "width='90%'");

$th = array(_("Old System Code"),
	_("FA Item Code"), _("Color Code"), _("Quantity"), _("Units"),
	_("Color Description"), _("PNP Color"), _("Category"), "", ""
);

table_header($th);

$k = $j = 0; //row colour counter

while ($myrow = db_fetch($result)) {
	alt_table_row_color($k);

	label_cell($myrow["old_code"]);
	label_cell($myrow["item_code"]);
	label_cell($myrow["color"]);
	qty_cell($myrow["quantity"], $dec);
	label_cell($units);
	label_cell($myrow["color_desc"]);
	label_cell($myrow["pnp_color"]);
	label_cell($myrow["cat_name"]);
	edit_button_cell("Edit" . $myrow['id'], _("Edit"));
	edit_button_cell("Delete" . $myrow['id'], _("Delete"));
	end_row();

	$j++;
	if ($j == 12) {
		$j = 1;
		table_header($th);
	} //end of page full new headings
} //end of while loop

end_table();
div_end();

//-----------------------------------------------------------------------------------------------

if ($selected_id != '') {
	if ($Mode == 'Edit') {
		$myrow = get_item_code($selected_id);
		$_POST['item_code'] = $myrow["item_code"];
		$_POST['color'] = $myrow["color"];
		$_POST['quantity'] = $myrow["quantity"];
		$_POST['description'] = $myrow["description"];
		$_POST['pnp_color'] = $myrow["pnp_color"];
		$_POST['category_id'] = $myrow["category_id"];
		$_POST['old_code'] = $myrow["old_code"];
	}
	hidden('selected_id', $selected_id);
} else {
	$_POST['quantity'] = 1;
	$_POST['description'] = $dflt_desc;
	$_POST['category_id'] = $dflt_cat;
}

echo "<br>";
start_table(TABLESTYLE2);

hidden('code_id', $selected_id);
text_row(_("Color:"), 'color', null, 20, 21);
hidden('item_code', $_POST['stock_id']);
qty_row(_("Quantity:"), 'quantity', null, '', $units, $dec);
text_row(_("Color Description:"), 'description', null, 50, 200);
text_row(_("PNP Color:"), 'pnp_color', null, 20, 21);
text_row(_("Old System Code:"), 'old_code', null, 50, 50);
stock_categories_list_row(_("Category:"), 'category_id', null);

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();
end_page();
