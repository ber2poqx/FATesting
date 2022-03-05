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
$page_security = 'SA_SCASHPRCTYPES';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Cash Price Setup"));

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/sales/includes/db/sales_cash_types_db.inc");

simple_page_mode(true);
//----------------------------------------------------------------------------------------------------

function can_process()
{
	if (strlen($_POST['scash_type']) == 0)
	{
		display_error(_("The cash price type description cannot be empty."));
		set_focus('scash_type');
		return false;
	}

	if (!check_num('factor', 0))
	{
		display_error(_("Calculation factor must be valid positive number."));
		set_focus('factor');
		return false;
	}
	return true;
}

//----------------------------------------------------------------------------------------------------

if ($Mode=='ADD_ITEM' && can_process())
{
	add_scash_type($_POST['scash_type'], check_value('tax_included'),
	    input_num('factor'));
	display_notification(_('New cash price type has been added'));
	$Mode = 'RESET';
}

//----------------------------------------------------------------------------------------------------

if ($Mode=='UPDATE_ITEM' && can_process())
{

	update_scash_type($selected_id, $_POST['scash_type'], check_value('tax_included'),
	     input_num('factor'));
	display_notification(_('Selected cash price type has been updated'));
	$Mode = 'RESET';
}

//----------------------------------------------------------------------------------------------------

if ($Mode == 'Delete')
{
	// PREVENT DELETES IF DEPENDENT RECORDS exists
	if (key_in_foreign_table($selected_id, 'cash_prices', 'scash_type_id'))
	{
		display_error(_('Cannot delete this policy because it is used by some related records in other tables.'));
	}else{
		delete_scash_price_type($selected_id);
		display_notification(_('Selected sales cash type has been deleted'));
	}
	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	$selected_id = -1;
	$sav = get_post('show_inactive');
	unset($_POST);
	$_POST['show_inactive'] = $sav;
}
//----------------------------------------------------------------------------------------------------

$result = get_all_scash_price_types(check_value('show_inactive'));

start_form();
start_table(TABLESTYLE, "width='30%'");

$th = array (_('Type Name'), _('Factor'), _('Tax Incl'), '','');
inactive_control_column($th);
table_header($th);
$k = 0;

while ($myrow = db_fetch($result))
{
	    alt_table_row_color($k);
		label_cell($myrow["scash_type"]);
		label_cell(number_format2($myrow["factor"],4));
		label_cell($myrow["tax_included"] ? _('Yes'):_('No'), 'align=center');
		inactive_control_cell($myrow["id"], $myrow["inactive"], 'sales_cash_type', 'id');
 	edit_button_cell("Edit".$myrow['id'], _("Edit"));
 	delete_button_cell("Delete".$myrow['id'], _("Delete"));
	end_row();
}
inactive_control_row($th);
end_table();

display_note(_("Marked cash price type is the company base pricelist for prices calculations."), 0, 0, "class='overduefg'");

//----------------------------------------------------------------------------------------------------

 if (!isset($_POST['tax_included']))
	$_POST['tax_included'] = 0;
 if (!isset($_POST['base']))
	$_POST['base'] = 0;

start_table(TABLESTYLE2);

if ($selected_id != -1)
{

 	if ($Mode == 'Edit') {
		$myrow = get_scash_price_type($selected_id);

		$_POST['scash_type']  = $myrow["scash_type"];
		$_POST['tax_included']  = $myrow["tax_included"];
		$_POST['factor']  = number_format2($myrow["factor"],4);
	}
	hidden('selected_id', $selected_id);
} else {
		$_POST['factor']  = number_format2(1,4);
}

text_row_ex(_("Cash Price Name").':', 'scash_type', 20);
amount_row(_("Calculation factor").':', 'factor', null, null, null, 4);
check_row(_("Tax included").':', 'tax_included', $_POST['tax_included']);

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();

end_page();

