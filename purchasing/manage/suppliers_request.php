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
$page_security = 'SA_SUPPLIER_GROUP';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");

page(_($help_context = "Supplier's Request Set-up"));

include_once($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/purchasing/includes/db/supplier_group_db.inc");

simple_page_mode(false);
//----------------------------------------------------------------------------------

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{

	//initialise no input errors assumed initially before we test
	$input_error = 0;

	if (strlen($_POST['description']) == 0)
	{
		$input_error = 1;
		display_error(_("The supplier's name cannot be empty."));
		set_focus('description');
	}

	
	if(supplier_request_already_exist($_POST['description'], $_POST['location'], $_POST['id']))
	{
        $input_error = 1;
        display_error(_("Supplier name already exists."));	
    }

	if ($input_error !=1) {
		$supplier_ref = $db_connections[user_company()]["branch_code"] . '' .get_Supplier_AutoGenerated_Code();
		$location = $db_connections[user_company()]["branch_code"];
    	add_supplier_request($selected_id,  $supplier_ref, $_POST['description'], $location);
		if($selected_id != '')
			display_notification(_('Selected supplier name has been updated'));
		else
			display_notification(_('New supplier name has been added'));
		$Mode = 'RESET';
	}
}

//----------------------------------------------------------------------------------

if ($Mode == 'Delete')
{

	if (supplier_request_used($selected_id))
	{
		display_error(_("Cannot delete this request because supplier name have been closed and already added in suppliers list."));
	}
	else
	{
		delete_supplier_request($selected_id);
		display_notification(_('Selected Supplier name has been deleted'));
	}
	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	$selected_id = '';
	$sav = get_post('show_inactive');
	unset($_POST);
	$_POST['show_inactive'] = $sav;
}

//----------------------------------------------------------------------------------

$result = get_all_supplier_request(check_value('show_inactive'), $location);

start_form();
start_table(TABLESTYLE, "width='40%'");
$th = array( _('ID'), _('Supplier Code'), _('Supplier Name'), _('Branch Code'), _('Status'), "Edit");
//inactive_control_column($th);

table_header($th);
$k = 0; //row colour counter

while ($myrow = db_fetch($result))
{

	alt_table_row_color($k);

	label_cell($myrow["id"]);
	label_cell($myrow["supplier_ref"]);
	label_cell($myrow["supp_name"]);
	label_cell($myrow["location"]);
	label_cell($myrow["STATUS"]);
	$code = html_specials_encode($myrow["id"]);
	inactive_control_cell($code, $myrow["inactive"], 'supplier_request', 'id');
 	edit_button_cell("Edit".$code, _("Edit"));
 	//delete_button_cell("Delete".$code, _("Delete"));
	end_row();
}

inactive_control_row($th);
end_table(1);

//----------------------------------------------------------------------------------

start_table(TABLESTYLE2);

if ($selected_id != '') 
{
	
 	if ($Mode == 'Edit') {
		$myrow1 = get_supplier_request($selected_id);

		$_POST['id'] = $myrow1["id"];
		$_POST['supplier_ref'] = $myrow1["supplier_ref"];
		$_POST['description']  = $myrow1["supp_name"];
		$_POST['location']  = $myrow1["location"];
	}
	hidden("selected_id", $myrow1["id"]);
}

if ($selected_id != ''){
    label_row(_("ID:"), $_POST['id']);
    hidden('id', $_POST['id']);
    label_row(_("Location:"), $_POST['location']);
    hidden('location', $_POST['location']);
    label_row(_("Supplier Code:"), $_POST['supplier_ref']);
    hidden('supplier_ref', $_POST['supplier_ref']);
}
text_row(_("Supplier Name:"), 'description', null, 40, 40);

end_table(1);

submit_add_or_update_center($selected_id == '', '', 'both');

end_form();

end_page();