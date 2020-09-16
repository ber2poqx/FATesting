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
$page_security = 'SA_IMPORTER';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");

page(_($help_context = "Importer Masterfile Setup"));

include_once($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/inventory/includes/db/items_importer_db.inc");

simple_page_mode(false);
//----------------------------------------------------------------------------------

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{

	//initialise no input errors assumed initially before we test
	$input_error = 0;

	
	if (strlen($_POST['description']) == 0)
	{
		$input_error = 1;
		display_error(_("The importer name cannot be empty."));
		set_focus('description');
	}

	if ($input_error !=1) {
    	write_importer($selected_id, $_POST['description']);
		if($selected_id != '')
			display_notification(_('Selected importer has been updated'));
		else
			display_notification(_('New importer has been added'));
		$Mode = 'RESET';
	}
}

//----------------------------------------------------------------------------------

if ($Mode == 'Delete')
{

	// PREVENT DELETES IF DEPENDENT RECORDS IN 'stock_master'

	if (importer_used($selected_id))
	{
		display_error(_("Cannot delete this importer because items have been created using this importer."));

	}
	else
	{
		delete_importer($selected_id);
		display_notification(_('Selected importer has been deleted'));
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

$result = get_all_importer(check_value('show_inactive'));
//$result = get_all_brand('false');

start_form();
start_table(TABLESTYLE, "width='40%'");
$th = array( _('ID'), _('Importer'), "", "");
inactive_control_column($th);

table_header($th);
$k = 0; //row colour counter

while ($myrow = db_fetch($result))
{

	alt_table_row_color($k);

	//label_cell($myrow["id"]);
	label_cell($myrow["id"]);
	label_cell($myrow["name"]);
	//label_cell(($myrow["decimals"]==-1?_("User Quantity Decimals"):$myrow["decimals"]));
	$code = html_specials_encode($myrow["id"]);
	inactive_control_cell($code, $myrow["inactive"], 'item_importer', 'id');
 	edit_button_cell("Edit".$code, _("Edit"));
 	delete_button_cell("Delete".$code, _("Delete"));
	end_row();
}

inactive_control_row($th);
end_table(1);

//----------------------------------------------------------------------------------

start_table(TABLESTYLE2);

if ($selected_id != '') 
{
	
 	if ($Mode == 'Edit') {
		//editing an existing brand
		
		//$sql="SELECT * FROM ".TB_PREF."item_brand WHERE code='".db_escape($selected_id)."' limit 1";

		//$result1 = db_query($sql,"the brand could not be retrieved");

		//$myrow1= db_fetch($result1);
		//label_row(_("Brand Code:"), $selected_id);
		$myrow1 = get_importer($selected_id);

		$_POST['id'] = $myrow1["id"];
		$_POST['description']  = $myrow1["name"];
		
		
	}
	hidden("selected_id", $myrow1["id"]);
}
//$selected_id != '' && brand_used($selected_id)
if ($selected_id != '' && importer_used($selected_id)){
    label_row(_("ID:"), $_POST['id']);
    hidden('id', $_POST['id']);
}else{
	//label_row(_("ID:"), $_POST['id']);
	//text_row(_("Brand Code:"), 'code', null, 20, 20);
 }
text_row(_("Imported By Name:"), 'description', null, 40, 40);

//number_list_row(_("Decimal Places:"), 'decimals', null, 0, 6, _("User Quantity Decimals"));

end_table(1);

submit_add_or_update_center($selected_id == '', '', 'both');

end_form();

end_page();