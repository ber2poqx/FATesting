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
$page_security = 'SA_MUNIZICODE';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");

page(_($help_context = "Minicipality and Zipcode Setup"));

include($path_to_root . "/includes/ui.inc");

simple_page_mode(true);

function can_process()
{
	$input_error1 = 0;
	if(municipality_already_exist($_POST['municipality'], $_POST['muni_code']))
	{
        $input_error1 = 1;
        display_error(_("Municipality already exists."));	
        set_focus('municipality');
		return false;		
    }

    if(zipcode_already_exist($_POST['zipcode'], $_POST['muni_code']))
	{
        $input_error1 = 1;
        display_error(_("ZipCode already exists."));	
        set_focus('zipcode');
		return false;		
    }
    return true;
}

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{

	$input_error = 0;

	if (strlen($_POST['municipality']) == 0) 
	{
		$input_error = 1;
		display_error(_("The Municipality cannot be empty."));
		set_focus('municipality');
		return false;		
	}

	if (strlen($_POST['zipcode']) == 0) 
	{
		$input_error = 1;
		display_error(_("The ZipCode cannot be empty."));
		set_focus('zipcode');
		return false;		
	}

	if ($input_error != 1)
	{
    	if ($selected_id != -1) 
    	{
    		update_muni_zipcode($selected_id, $_POST['municipality'], $_POST['zipcode']);
			$note = _('Selected municipality has been updated');
    	} 
    	else 
    	{
    		if (!can_process()) 
    		{
    			return true;
    		}else{
    			//begin_transaction();

	    		add_muni_zipcode($_POST['municipality'], $_POST['zipcode']);
				$note = _('New municipality has been added');
    		}	
    	}
    
		display_notification($note);    	
		$Mode = 'RESET';
	}
} 

if ($Mode == 'Delete')
{

	$cancel_delete = 0;

	// PREVENT DELETES IF DEPENDENT RECORDS IN 'debtors_master'

	if (key_in_foreign_table($selected_id, 'debtors_master', 'municipality'))
	{
		$cancel_delete = 1;
		display_error(_("Cannot delete this municipality because customer branches have been created using this municipality."));
	} 
	if ($cancel_delete == 0) 
	{
		delete_munizip_code($selected_id);

		display_notification(_('Selected municipality has been deleted'));
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

$result = get_munizip_code(check_value('show_inactive'));

start_form();
start_table(TABLESTYLE, "width='50%'");

$th = array(_("ID"), _("Minicipality"), _("ZipCode"), "Edit", "Delete");
inactive_control_column($th);

table_header($th);
$k = 0; 

while ($myrow = db_fetch($result)) 
{
	
	alt_table_row_color($k);
		
	label_cell($myrow["muni_code"]);
	label_cell($myrow["municipality"]);
	label_cell($myrow["zipcode"]);

	
	inactive_control_cell($myrow["muni_code"], $myrow["inactive"], 'municipality_zipcode', 'muni_code');

 	edit_button_cell("Edit".$myrow["muni_code"], _("Edit"));
 	delete_button_cell("Delete".$myrow["muni_code"], _("Delete"));
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
		//editing an existing area
		$myrow = get_munizip_edit_code($selected_id);

		$_POST['municipality']  = $myrow["municipality"];
		$_POST['zipcode']  = $myrow["zipcode"];

	}
	hidden("selected_id", $selected_id);
} 

text_row_ex(_("Municipality:"), 'municipality', 30); 
text_row_ex(_("ZipCode:"), 'zipcode', 30); 


end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();

end_page();
