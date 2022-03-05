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
/*
CREATED BY: Prog6
Date: 11/10/2021
*/

$page_security = 'SA_SALES_TARGET';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");

page(_($help_context = "Sales Target AMOUNT Setup"));

include($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/sales/includes/db/sales_target_db.inc");


simple_page_mode(true);

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{

	$input_error = 0;


	
	if($Mode != 'UPDATE_ITEM')
	{
		//Check for existing target year
		if(check_existing_target_year($_POST['target_year']))
		{
			$input_error = 1;
			display_error(_("Target Year already exist."));
			set_focus('target_year');
		}	
		if (strlen($_POST['target_year']) == 0 || strlen($_POST['target_year']) == '') 
		{
			$input_error = 1;
			display_error(_("Target Year cannot be empty."));
			set_focus('target_year');
		}
	}
	
	if ($_POST['target_jan'] == '') 
	{
		$_POST['target_jan'] = 0;
	}
	if ($_POST['target_feb'] == '') 
	{
		$_POST['target_feb'] = 0;
	}
	if ($_POST['target_mar'] == '') 
	{
		$_POST['target_mar'] = 0;
	}
	if ($_POST['target_apr'] == '') 
	{
		$_POST['target_apr'] = 0;
	}
	if ($_POST['target_may'] == '') 
	{
		$_POST['target_may'] = 0;
	}
	if ($_POST['target_jun'] == '') 
	{
		$_POST['target_jun'] = 0;
	}
	if ($_POST['target_jul'] == '') 
	{
		$_POST['target_jul'] = 0;
	}
	if ($_POST['target_aug'] == '') 
	{
		$_POST['target_aug'] = 0;
	}
	if ($_POST['target_sep'] == '') 
	{
		$_POST['target_sep'] = 0;
	}
	if ($_POST['target_oct'] == '') 
	{
		$_POST['target_oct'] = 0;
	}
	if ($_POST['target_nov'] == '') 
	{
		$_POST['target_nov'] = 0;
	}
	if ($_POST['target_dec'] == '') 
	{
		$_POST['target_dec'] = 0;
	}


	if ($input_error != 1)
	{
    	if ($selected_id != -1) 
    	{
    		update_target_amount($selected_id, $_POST['target_year'], $_POST['target_jan'], $_POST['target_feb'], $_POST['target_mar'], $_POST['target_apr'], $_POST['target_may'], $_POST['target_jun'], $_POST['target_jul'], $_POST['target_aug'], $_POST['target_sep'], $_POST['target_oct'], $_POST['target_nov'], $_POST['target_dec']);
			$note = _('Selected target year  has been updated');
    	} 
    	else 
    	{
    		add_target_amount($_POST['target_year'], $_POST['target_jan'], $_POST['target_feb'], $_POST['target_mar'], $_POST['target_apr'], $_POST['target_may'], $_POST['target_jun'], $_POST['target_jul'], $_POST['target_aug'], $_POST['target_sep'], $_POST['target_oct'], $_POST['target_nov'], $_POST['target_dec']);
			$note = _('New Target Year has been added');
    	}
    
		display_notification($note);    	
		$Mode = 'RESET';
	}
} 

if ($Mode == 'Delete')
{

	$cancel_delete = 0;

	delete_sale_target($selected_id);
	display_notification(_('Selected Target Year has been deleted'));
	$Mode = 'RESET';
} 

if ($Mode == 'RESET')
{
	$selected_id = -1;
	$sav = get_post('show_inactive');
	unset($_POST);
	$_POST['show_inactive'] = $sav;

	$_POST['target_year']  = '';
	$_POST['target_jan']  = '';
	$_POST['target_feb']  = '';
	$_POST['target_mar']  = '';
	$_POST['target_apr']  = '';
	$_POST['target_may']  = '';
	$_POST['target_jun']  = '';
	$_POST['target_jul']  = '';
	$_POST['target_aug']  = '';
	$_POST['target_sep']  = '';
	$_POST['target_oct']  = '';
	$_POST['target_nov']  = '';
	$_POST['target_dec']  = '';
}

//-------------------------------------------------------------------------------------------------

$result = get_all_sale_target_amount(check_value('show_inactive'));

start_form();
start_table(TABLESTYLE, "width='100%'");

$th = array(_("ID"), _("Year"), _("January"), _("February"), _("March"), _("April"), _("May"), _("June"), _("July"), _("August"), _("September"), _("October"), _("November"), _("December"),"Edit", "Delete");
//inactive_control_column($th);

table_header($th);
$k = 0; 

while ($myrow = db_fetch($result)) 
{
	
	alt_table_row_color($k);
		
	label_cell($myrow["id"]);
	label_cell("<b>".$myrow["year"]."</b>");
	amount_cell($myrow["jan"]);
	amount_cell($myrow["feb"]);
	amount_cell($myrow["mar"]);
	amount_cell($myrow["apr"]);
	amount_cell($myrow["may"]);
	amount_cell($myrow["jun"]);
	amount_cell($myrow["jul"]);
	amount_cell($myrow["aug"]);
	amount_cell($myrow["sep"]);
	amount_cell($myrow["oct"]);
	amount_cell($myrow["nov"]);
	amount_cell($myrow["dece"]);

	
	//inactive_control_cell($myrow["id"], $myrow["inactive"], '', 'id');

 	edit_button_cell("Edit".$myrow["id"], _("Edit"));
 	delete_button_cell("Delete".$myrow["id"], _("Delete"));
	end_row();
}
	
//inactive_control_row($th);
end_table();
echo '<br>';

//-------------------------------------------------------------------------------------------------

start_table(TABLESTYLE2);

if ($selected_id != -1) 
{
 	if ($Mode == 'Edit') {
		//editing an existing area
		$myrow = get_sale_target_amount($selected_id);

		$_POST['target_year']  = $myrow["year"];
		$_POST['target_jan']  = $myrow["jan"];
		$_POST['target_feb']  = $myrow["feb"];
		$_POST['target_mar']  = $myrow["mar"];
		$_POST['target_apr']  = $myrow["apr"];
		$_POST['target_may']  = $myrow["may"];
		$_POST['target_jun']  = $myrow["jun"];
		$_POST['target_jul']  = $myrow["jul"];
		$_POST['target_aug']  = $myrow["aug"];
		$_POST['target_sep']  = $myrow["sep"];
		$_POST['target_oct']  = $myrow["oct"];
		$_POST['target_nov']  = $myrow["nov"];
		$_POST['target_dec']  = $myrow["dece"];

	}
	hidden("selected_id", $selected_id);
	//label_row(_("Year:"), $myrow["year"]);
	set_focus('target_jan');

} 

text_row_ar(_("Year:"), 'target_year', 10, $Mode);
text_row_ar(_("January:"), 'target_jan', 15); 
text_row_ar(_("February:"), 'target_feb', 15);
text_row_ar(_("March:"), 'target_mar', 15); 
text_row_ar(_("April:"), 'target_apr', 15);
text_row_ar(_("May:"), 'target_may', 15); 
text_row_ar(_("June:"), 'target_jun', 15);
text_row_ar(_("July:"), 'target_jul', 15); 
text_row_ar(_("August:"), 'target_aug', 15);
text_row_ar(_("September:"), 'target_sep', 15); 
text_row_ar(_("October:"), 'target_oct', 15);
text_row_ar(_("November:"), 'target_nov', 15); 
text_row_ar(_("December:"), 'target_dec', 15);


end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();

end_page();
