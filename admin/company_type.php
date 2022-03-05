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
$page_security = 'SA_COMPANYTYP';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Company Type Setup"));

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/admin/db/company_db.inc");

simple_page_mode(true);
//----------------------------------------------------------------------------------------------------

function can_process()
{
	if (strlen($_POST['type']) == 0)
	{
		display_error(_("The company type cannot be empty."));
		set_focus('type');
		return false;
	}
	return true;
}

//----------------------------------------------------------------------------------------------------

if ($Mode=='ADD_ITEM' && can_process())
{
	add_company_type($_POST['type'], $_POST['description']);
	display_notification(_('New company type has been added'));
	$Mode = 'RESET';
}

//----------------------------------------------------------------------------------------------------

if ($Mode=='UPDATE_ITEM' && can_process())
{
	update_company_type($selected_id, $_POST['description']);
	display_notification(_('Selected company type has been updated'));
	$Mode = 'RESET';
}

//----------------------------------------------------------------------------------------------------

if ($Mode == 'Delete')
{
	/*// PREVENT DELETES IF DEPENDENT RECORDS IN 'item_srp'
	if (key_in_foreign_table($selected_id, 'item_srp', 'srptype_id'))
	{
		display_error(_("Cannot delete this branch area because some items are currently set up to use this srp type."));
	}
	else
	{*/
		//delete_company_type($selected_id);
		//display_notification(_('Selected company type has been deleted'));
		display_error(_('You try to remove a record which has child records.'));
	//}
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

$result = get_all_company_type(check_value('show_inactive'));

start_form();
start_table(TABLESTYLE, "width='30%'");

$th = array (_('Code'), _('Description'), 'E','D');
inactive_control_column($th);
table_header($th);
$k = 0;

while ($myrow = db_fetch($result))
{
	    alt_table_row_color($k);
		label_cell($myrow["type"]);
		label_cell($myrow["description"]);

	inactive_control_cell($myrow["type"], $myrow["inactive"], 'company_type', 'type');
 	edit_button_cell("Edit".$myrow['type'], _("Edit"));
 	delete_button_cell("Delete".$myrow['type'], _("Delete"));
	end_row();
}
inactive_control_row($th);
end_table();

display_note(_(""), 0, 0, "class='overduefg'");

//----------------------------------------------------------------------------------------------------

start_table(TABLESTYLE2);

if ($selected_id != -1)
{

 	if ($Mode == 'Edit') {
		$myrow = get_company_type($selected_id);

		$comp_code = $myrow["type"];
		$_POST['type']  = $myrow["type"];
		$_POST['description']  = $myrow["description"];
	}
	hidden('selected_id', $selected_id);
} else {

 }

if ($Mode == 'Edit'){
	hidden('supplier_id');
	label_row(_("Code").':', $comp_code);
}else{
	text_row_ex(_("Code").':', 'type', 20);
}

textarea_row(_("Description"), 'description', $_POST['description'], 34, 2);

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();

end_page();

