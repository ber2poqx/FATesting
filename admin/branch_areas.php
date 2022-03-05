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
$page_security = 'SA_BRANCHAREA';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Branch &Area Setup"));

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/admin/db/branch_areas_db.inc");

simple_page_mode(true);
//----------------------------------------------------------------------------------------------------

function can_process()
{
	if (strlen($_POST['code']) == 0)
	{
		display_error(_("The branch area code cannot be empty."));
		set_focus('code');
		return false;
	}
	if (strlen($_POST['description']) == 0)
	{
		display_error(_("The branch area description cannot be empty."));
		set_focus('code');
		return false;
	}
	return true;
}

//----------------------------------------------------------------------------------------------------

if ($Mode=='ADD_ITEM' && can_process())
{
	add_branch_area($_POST['code'], $_POST['description']);
	display_notification(_('New branch area has been added'));
	$Mode = 'RESET';
}

//----------------------------------------------------------------------------------------------------

if ($Mode=='UPDATE_ITEM' && can_process())
{
	update_branch_area($selected_id, $_POST['code'], $_POST['description']);
	display_notification(_('Selected branch area has been updated'));
	$Mode = 'RESET';
}

//----------------------------------------------------------------------------------------------------

if ($Mode == 'Delete')
{
	// PREVENT DELETES IF DEPENDENT RECORDS IN 'item_srp'
	if (key_in_foreign_table($selected_id, 'item_srp', 'srptype_id'))
	{
		display_error(_("Cannot delete this branch area because some items are currently set up to use this srp type."));
	}
	else
	{
		delete_branch_area($selected_id);
		display_notification(_('Selected branch area has been deleted'));
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

$result = get_all_branch_area(check_value('show_inactive'));

start_form();
start_table(TABLESTYLE, "width='30%'");

$th = array (_('Code'), _('Description'), 'E','D');
inactive_control_column($th);
table_header($th);
$k = 0;
$base_sales = get_base_sales_type();

while ($myrow = db_fetch($result))
{
	//if ($myrow["id"] == $base_sales)
	//    start_row("class='overduebg'");
	//else
	    alt_table_row_color($k);
		label_cell($myrow["code"]);
		label_cell($myrow["description"]);

	inactive_control_cell($myrow["id"], $myrow["inactive"], 'branch_area', 'id');
 	edit_button_cell("Edit".$myrow['id'], _("Edit"));
 	delete_button_cell("Delete".$myrow['id'], _("Delete"));
	end_row();
}
inactive_control_row($th);
end_table();

//display_note(_("Marked SRP type is the company base pricelist for standard retail price calculations."), 0, 0, "class='overduefg'");

//----------------------------------------------------------------------------------------------------

start_table(TABLESTYLE2);

if ($selected_id != -1)
{

 	if ($Mode == 'Edit') {
		$myrow = get_branch_area($selected_id);

		$_POST['code']  = $myrow["code"];
		$_POST['description']  = $myrow["description"];
	}
	hidden('selected_id', $selected_id);
} else { }

text_row_ex(_("Code").':', 'code', 20);
text_row_ex(_("Description").':', 'description', 20);

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();

end_page();

