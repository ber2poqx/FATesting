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
$page_security = 'SA_CHGPASSWD';
$path_to_root="..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Change password"));

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/admin/db/users_db.inc");

function can_process()
{
	$Auth_Result = hook_authenticate($_SESSION["wa_current_user"]->username, $_POST['cur_password']);

	$uppercase = preg_match('`[A-Z]`', $_POST['password']);
	$lowercase = preg_match('`[a-z]`', $_POST['password']);
	$number = preg_match('`[0-9]`', $_POST['password']);


	if (!isset($Auth_Result))	// if not used external login: standard method
		$Auth_Result = get_user_auth($_SESSION["wa_current_user"]->username, md5($_POST['cur_password']));

	if (!$Auth_Result)
   	{
  		display_error( _("Invalid current password entered."));
		set_focus('cur_password');
   		return false;
   	}

   	if (strlen($_POST['password']) == 0) 
	{
		display_error(_("The new password cannot be empty."));
		set_focus('password');
		return false;
	} 

	if (strlen($_POST['passwordConfirm']) == 0) 
	{
		display_error(_("The Repeat password cannot be empty."));
		set_focus('passwordConfirm');
		return false;
	} 
	
   	if (strlen($_POST['password']) < 8)
   	{
  		display_error( _("The new password entered must be at least 8 characters long."));
		set_focus('password');
   		return false;
   	}

   	if (strstr($_POST['password'], $_SESSION["wa_current_user"]->username) != false)
   	{
   		display_error( _("The new password cannot contain the user login."));
		set_focus('password');
   		return false;
   	}

   	if ($_POST['password'] != $_POST['passwordConfirm'])
   	{
   		display_error( _("The new password and repeat new password entered are not the same."));
		set_focus('password');
   		return false;
   	}

   	//--Added By Robert--//11/22/2022
   	if ($_POST['password'] == $_POST['cur_password'])
   	{
   		display_error( _("The new password must not be the same in current password."));
		set_focus('password');
   		return false;
   	}

   	if (strstr($_POST['password'], "!") || strstr($_POST['password'], "@") || strstr($_POST['password'], "#") || strstr($_POST['password'], "$") 
   		|| strstr($_POST['password'], "%") || strstr($_POST['password'], "^") || strstr($_POST['password'], "&") || strstr($_POST['password'], "*")
   		|| strstr($_POST['password'], "(") || strstr($_POST['password'], ")") || strstr($_POST['password'], "-") || strstr($_POST['password'], "_")
   		|| strstr($_POST['password'], "+") || strstr($_POST['password'], "=") || strstr($_POST['password'], "[") || strstr($_POST['password'], "]")
   		|| strstr($_POST['password'], "{") || strstr($_POST['password'], "}") || strstr($_POST['password'], ";") || strstr($_POST['password'], ":")
   		||strstr($_POST['password'], "'")  || strstr($_POST['password'], "\\") || strstr($_POST['password'], "/") || strstr($_POST['password'], "<")
   		|| strstr($_POST['password'], ">") || strstr($_POST['password'], "?") || strstr($_POST['password'], ",") || strstr($_POST['password'], ".")
   		|| strstr($_POST['password'], "~") || strstr($_POST['password'], "|"))
   	{
   		display_error( _("The new password cannot contain any special characters"));
		set_focus('password');
   		return false;
   	}

   	if (!$uppercase || !$lowercase || !$number)
   	{
   		display_error( _("New password should include at least one upper case letter, one lower case letter, one number"));
		set_focus('password');
   		return false;
   	}
   	//-----//

	return true;
}

if (isset($_POST['UPDATE_ITEM']) && check_csrf_token())
{
	$datenow =Today();
	$passupdate = date('Y-m-d', strtotime($datenow));
	if (can_process())
	{
		if ($SysPrefs->allow_demo_mode) {
		    display_warning(_("Password cannot be changed in demo mode."));
		} else {
			update_user_password($_SESSION["wa_current_user"]->user, 
				$_SESSION["wa_current_user"]->username,
				md5($_POST['password']), $passupdate);
		    display_notification(_("Your password has been updated."));
		}
		$Ajax->activate('_page_body');
	}
}

start_form();

start_table(TABLESTYLE);

$myrow = get_user($_SESSION["wa_current_user"]->user);

label_row(_("User login:"), $myrow['user_id']);

$_POST['cur_password'] = "";
$_POST['password'] = "";
$_POST['passwordConfirm'] = "";

password_row(_("Current Password:"), 'cur_password', $_POST['cur_password']);
password_row(_("New Password:"), 'password', $_POST['password']);
password_row(_("Repeat New Password:"), 'passwordConfirm', $_POST['passwordConfirm']);

table_section_title(_("Enter your new password in the fields."));

end_table(1);

submit_center( 'UPDATE_ITEM', _('Change password'), true, '',  'default');
end_form();
end_page();
