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
$page_security = 'SA_SLINCNTIVTYPE';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Sales Incentive Setup"));

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/sales/includes/db/sales_incentive_db.inc");

simple_page_mode(true);
//----------------------------------------------------------------------------------------------------

function can_process()
{
	if (strlen($_POST['inc_type']) == 0)
	{
		display_error(_("Description cannot be empty."));
		set_focus('inc_type');
		return false;
	}
	if (strlen($_POST['mode_type']) == 0)
	{
		display_error(_("The module type cannot be empty."));
		set_focus('mode_type');
		return false;
	}
	return true;
}

//----------------------------------------------------------------------------------------------------

if ($Mode=='ADD_ITEM' && can_process())
{
	add_incentive_type($_POST['inc_type'], $_POST['mode_type']);
	display_notification(_('New incentive type has been added'));
	$Mode = 'RESET';
}

//----------------------------------------------------------------------------------------------------

if ($Mode=='UPDATE_ITEM' && can_process())
{

	update_incentive_type($selected_id, $_POST['inc_type'], $_POST['mode_type']);
	display_notification(_('Selected incentive type has been updated'));
	$Mode = 'RESET';
}

//----------------------------------------------------------------------------------------------------

if ($Mode == 'Delete')
{
	// PREVENT DELETES IF DEPENDENT RECORDS exists
	if (key_in_foreign_table($selected_id, 'incentive_prices', 'incentive_type_id'))
	{
		display_error(_('Cannot delete this policy type because it is used by some related records in other tables.'));
	}else{
		delete_incentive_type($selected_id);
		display_notification(_('Selected sales incentive type has been deleted'));
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

$result = get_all_incentive_type(check_value('show_inactive'));

start_form();
start_table(TABLESTYLE, "width='30%'");

$th = array (_('Description'), _('Module Type'), 'E','D');
inactive_control_column($th);
table_header($th);
$k = 0;

while ($myrow = db_fetch($result))
{
	switch ($myrow["module_type"]) {
		case "BII":
			$module = "Brandnew Item Incentive";
			break;
		case "RII":
			$module = "Repossessed Item Incentive";
			break;
		case "SMI":
			$module = "Sales Monthly Incentive";
			break;
	}
	    alt_table_row_color($k);
		label_cell($myrow["description"]);
		label_cell($module);
		inactive_control_cell($myrow["id"], $myrow["inactive"], 'sales_incentive_type', 'id');
 	edit_button_cell("Edit".$myrow['id'], _("Edit"));
 	delete_button_cell("Delete".$myrow['id'], _("Delete"));
	end_row();
}
inactive_control_row($th);
end_table();

display_note(_("Marked cash price type is the company base pricelist for prices calculations."), 0, 0, "class='overduefg'");

//----------------------------------------------------------------------------------------------------

start_table(TABLESTYLE2);

if ($selected_id != -1)
{

 	if ($Mode == 'Edit') {
		$myrow = get_incentive_type($selected_id);

		$_POST['inc_type']  = $myrow["description"];
		$_POST['mode_type']  = $myrow["module_type"];
	}
	hidden('selected_id', $selected_id);
} else {
		$_POST['factor']  = number_format2(1,4);
}

text_row_ex(_("Description").':', 'inc_type', 25);
group_incentive_list_row(_("Module Type:"), 'mode_type', null, true, " ");

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();

end_page();

