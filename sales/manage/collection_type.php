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
$page_security = 'SA_COLLECTYPE';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");

page(_($help_context = "Collection Type"));

include($path_to_root . "/includes/ui.inc");

simple_page_mode(true);

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{

	$input_error = 0;

	if (strlen($_POST['collection']) == 0) 
	{
		$input_error = 1;
		display_error(_("The Collection Type cannot be empty."));
		set_focus('collection');
	}


	if ($input_error != 1)
	{
    	if ($selected_id != -1) 
    	{
    		update_collection_type($selected_id, $_POST['collection']);
			$note = _('Selected collection type has been updated');
    	} 
    	else 
    	{
    		add_collection_type($_POST['collection']);
			$note = _('New collection type has been added');
    	}
    
		display_notification($note);    	
		$Mode = 'RESET';
	}
} 

if ($Mode == 'Delete')
{

	$cancel_delete = 0;

	// PREVENT DELETES IF DEPENDENT RECORDS IN 'debtors_master'

	if (key_in_foreign_table($selected_id, 'cust_branch', 'area'))
	{
		$cancel_delete = 1;
		display_error(_("Cannot delete this collection type because customer branches have been created using this collection."));
	} 
	if ($cancel_delete == 0) 
	{
		delete_collection_type($selected_id);

		display_notification(_('Selected collection type has been deleted'));
	} //end if Delete area
	$Mode = 'RESET';
} 

if ($Mode == 'RESET')
{
	$selected_id = -1;
	$sav = get_post('show_inactive');
	unset($_POST);
	$_POST['show_inactive'] = $sav;
}

//-------------------------------------------------------------------------------------------------

$result = get_collection_types(check_value('show_inactive'));

start_form();
start_table(TABLESTYLE, "width='50%'");

$th = array(_("Collection Type"), "", "");
inactive_control_column($th);

table_header($th);
$k = 0; 

while ($myrow = db_fetch($result)) 
{
	
	alt_table_row_color($k);
		
	//label_cell($myrow["collect_id"]);
	label_cell($myrow["collection"]);
	
	inactive_control_cell($myrow["collect_id"], $myrow["inactive"], 'collection_types', 'collect_id');

 	edit_button_cell("Edit".$myrow["collect_id"], _("Edit"));
 	delete_button_cell("Delete".$myrow["collect_id"], _("Delete"));
	end_row();
}
	
inactive_control_row($th);
end_table();
echo '<br>';

//-------------------------------------------------------------------------------------------------

start_table(TABLESTYLE2);

//modified br robert
if ($selected_id != -1) 
{
 	if ($Mode == 'Edit') {
		//editing an existing collection type
		$myrow = get_collection_type($selected_id);

		$_POST['collection']  = $myrow["collection"];
	}
	hidden("selected_id", $selected_id);
} 

text_row_ex(_("Collection Type:"), 'collection', 30); 

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();

end_page();
