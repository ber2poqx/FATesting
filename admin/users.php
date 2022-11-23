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

/**
 * Modified by: spyrax10
 * Date Modified: 1 Feb 2022
 */


$page_security = 'SA_USERS';
$path_to_root = "..";

include_once($path_to_root . "/includes/db_pager.inc"); 
include_once($path_to_root . "/includes/session.inc");

add_js_ufile($path_to_root . "/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root . '/js/users.js');
add_js_ufile($path_to_root . "/js/ext620/build/examples/classic/shared/examples.js");
add_css_file($path_to_root . "/css/extjs-default.css");


include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/admin/db/users_db.inc");
include_once($path_to_root . "/admin/db/branch_areas_db.inc");

$_SESSION['language']->encoding = "UTF-8";

//---------------definition for first branchlisting records in the datagrid---------
if(isset($_GET['branchlistingleft'])){
    $admin_id = $_REQUEST['admin_id'];
    
    global $db_connections;
    $counterrec=0;
    for ($i = 0; $i < count($db_connections); $i++)
    {
        $total=0;
        $sql ="select admin_branches_id from ".TB_PREF."admin_branches_access where admin_branches_access.admin_branches_branchcode='".$db_connections[$i]["branch_code"]."' and admin_branches_access.admin_branches_admin_id=$admin_id limit 1";
        //$group_array_query1 = $db->execute($temp1);
        
        $result=db_query($sql, "could not get all Branches");
        
        $total = db_num_rows($result);
        
        
        if($total<=0){
            $group_array[] = array( 'branch_id'=>$i,
                'branch_code'=>$db_connections[$i]["branch_code"],
                'branch_name'=>$db_connections[$i]["name"],
                'branch_area'=>$db_connections[$i]["branch_area"],
                'branch_area_name'=>get_branch_area_name_by_code($db_connections[$i]["branch_area"]),
                'admin_id'=>$admin_id,
            );
            $counterrec++;
        }
        
    }
    
    
    $jsonresult = json_encode($group_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    
    exit();
}

//---------------definition for second branchlisting records in the datagrid---------
if(isset($_GET['branchlistingright'])){
    $admin_id = $_REQUEST['admin_id'];
    $sql ="select * from ".TB_PREF."admin_branches_access aba where aba.admin_branches_admin_id=$admin_id ORDER BY  aba.admin_branches_branchcode ASC";
    //$group_array_query = $db->execute($temp);
    //$total = $group_array_query->RecordCount();
    
    $result=db_query($sql);
    
    $total = db_num_rows($result);
    
    while($myrow=db_fetch($result)) {
        $group_array[] = array(
            'branch_code'=>$myrow['admin_branches_branchcode'],
            'branch_name'=>get_db_location_name($myrow['admin_branches_branchcode']),
            'branch_area'=>get_db_location_area($myrow['admin_branches_branchcode']),
            'branch_area_name'=>get_branch_area_name_by_code(get_db_location_area($myrow['admin_branches_branchcode'])),
            'admin_branches_id'=>$myrow['admin_branches_id'],
            'admin_branches_canrequest'=>$myrow['admin_branches_canrequest'],
            'admin_branches_canreview'=>$myrow['admin_branches_canreview'],
            'admin_branches_cannoted'=>$myrow['admin_branches_cannoted'],
            'admin_branches_canapprove'=>$myrow['admin_branches_canapprove'],
            'admin_id'=>$admin_id
        );
        
    }
    $jsonresult = json_encode($group_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    exit();
}

if( isset($_GET['save_branchaccess']) ) {
    $admin_id = $_REQUEST['admin_id'];
    $branch_code = $_REQUEST['branch_code'];
    /*$sql_data_array = array(
        'admin_branches_admin_id' => $admin_id,
        'admin_branches_branchcode' => $branch_code
    );*/
    
    //$columns = implode(", ",array_keys($sql_data_array));
    //$escaped_values = array_map('mysql_real_escape_string', array_values($sql_data_array));
    //$values  = implode(", ", $escaped_values);
    $sql = "INSERT INTO ".TB_PREF."admin_branches_access (admin_branches_admin_id,admin_branches_branchcode) VALUES ('$admin_id','$branch_code')";
    $result=db_query($sql);
    //zen_db_perform('admin_branches_access', $sql_data_array);
    
    echo json_encode(array('success'=>'true'));
    exit();
    
}else if(isset($_GET['add_accesscontrol'])) {
    $admin_branches_id = $_REQUEST['admin_branches_id'];
    $admin_branches_canrequest = $_REQUEST['admin_branches_canrequest'];
    $admin_branches_canreview = $_REQUEST['admin_branches_canreview'];
    $admin_branches_cannoted = $_REQUEST['admin_branches_cannoted'];
    $admin_branches_canapprove = $_REQUEST['admin_branches_canapprove'];
    /*$sql_data_array = array(
        'admin_branches_canrequest' => ($admin_branches_canrequest=='false'?0:1),
        'admin_branches_canreview' => ($admin_branches_canreview=='false'?0:1),
        'admin_branches_cannoted' => ($admin_branches_cannoted=='false'?0:1),
        'admin_branches_canapprove' => ($admin_branches_canapprove=='false'?0:1)
    );*/
    //zen_db_perform('admin_branches_access', $sql_data_array, 'update', "admin_branches_id = '" . $admin_branches_id . "'");

    $sql = "UPDATE ".TB_PREF."admin_branches_access SET admin_branches_canrequest=".db_escape($admin_branches_canrequest=='false'?0:1) . ",
		admin_branches_canreview = ".db_escape($admin_branches_canreview=='false'?0:1). ",
		admin_branches_cannoted = ".db_escape($admin_branches_cannoted=='false'?0:1).",
		admin_branches_canapprove = ".db_escape($admin_branches_canapprove=='false'?0:1)." WHERE admin_branches_id=".db_escape($admin_branches_id);
    
    return db_query($sql, "could not update Branch user for $admin_branches_id");
    
    echo json_encode(array('success'=>'true','admin_id'=>$admin_branches_id));
    exit();
}else if(isset($_GET['remove_branchaccess'])){;

    $admin_branches_id = $_REQUEST['admin_branches_id'];
    
    $sql="DELETE FROM ".TB_PREF."admin_branches_access WHERE admin_branches_id=".db_escape($admin_branches_id);
    
    db_query($sql, "could not branches user $admin_branches_id");
    
    //$sql = "delete from ".TB_PREF."admin_branches_access where admin_branches_id=".$admin_branches_id;
    //$result=db_query($sql,"Error");

    echo json_encode(array('success'=>'true','admin_banches ID'=>$admin_branches_id));
    exit();
} // end


page(_($help_context = "Users"));

simple_page_mode(true);
//-------------------------------------------------------------------------------------------------

function can_process($new) 
{
    $uppercase = preg_match('`[A-Z]`', $_POST['password']);
    $lowercase = preg_match('`[a-z]`', $_POST['password']);
    $number = preg_match('`[0-9]`', $_POST['password']);

	if (strlen($_POST['user_id']) < 4)
	{
		display_error( _("The user login entered must be at least 4 characters long."));
		set_focus('user_id');
		return false;
	}

    if (strlen($_POST['password']) == 0) 
    {
        display_error(_("The password cannot be empty."));
        set_focus('password');
        return false;
    } 

    if (strlen($_POST['password']) < 8)
    {
        display_error( _("The password entered must be at least 8 characters long."));
        set_focus('password');
        return false;
    }

    if (strstr($_POST['password'], "!") || strstr($_POST['password'], "@") || strstr($_POST['password'], "#") || strstr($_POST['password'], "$") 
        || strstr($_POST['password'], "%") || strstr($_POST['password'], "^") || strstr($_POST['password'], "&") || strstr($_POST['password'], "*")
        || strstr($_POST['password'], "(") || strstr($_POST['password'], ")") || strstr($_POST['password'], "-") || strstr($_POST['password'], "_")
        || strstr($_POST['password'], "+") || strstr($_POST['password'], "=") || strstr($_POST['password'], "[") || strstr($_POST['password'], "]")
        || strstr($_POST['password'], "{") || strstr($_POST['password'], "}") || strstr($_POST['password'], ";") || strstr($_POST['password'], ":")
        ||strstr($_POST['password'], "'") || strstr($_POST['password'], "\\") || strstr($_POST['password'], "/") || strstr($_POST['password'], "<")
        || strstr($_POST['password'], ">") || strstr($_POST['password'], "?") || strstr($_POST['password'], ",") || strstr($_POST['password'], ".")
        || strstr($_POST['password'], "~") || strstr($_POST['password'], "|"))
    {
        display_error( _("The new password cannot contain any special characters"));
        set_focus('password');
        return false;
    }

    if (!$uppercase || !$lowercase || !$number)
    {
        display_error( _("The password should include at least one upper case letter, one lower case letter, one number"));
        set_focus('password');
        return false;
    }

	if (!$new && ($_POST['password'] != ""))
	{
    	if (strlen($_POST['password']) < 8)
    	{
    		display_error( _("The password entered must be at least 8 characters long."));
			set_focus('password');
    		return false;
    	}

    	if (strstr($_POST['password'], $_POST['user_id']) != false)
    	{
    		display_error( _("The password cannot contain the user login."));
			set_focus('password');
    		return false;
    	}

        if (strstr($_POST['password'], "!") || strstr($_POST['password'], "@") || strstr($_POST['password'], "#") || strstr($_POST['password'], "$") 
            || strstr($_POST['password'], "%") || strstr($_POST['password'], "^") || strstr($_POST['password'], "&") || strstr($_POST['password'], "*")
            || strstr($_POST['password'], "(") || strstr($_POST['password'], ")") || strstr($_POST['password'], "-") || strstr($_POST['password'], "_")
            || strstr($_POST['password'], "+") || strstr($_POST['password'], "=") || strstr($_POST['password'], "[") || strstr($_POST['password'], "]")
            || strstr($_POST['password'], "{") || strstr($_POST['password'], "}") || strstr($_POST['password'], ";") || strstr($_POST['password'], ":")
            ||strstr($_POST['password'], "'") || strstr($_POST['password'], "\\") || strstr($_POST['password'], "/") || strstr($_POST['password'], "<")
            || strstr($_POST['password'], ">") || strstr($_POST['password'], "?") || strstr($_POST['password'], ",") || strstr($_POST['password'], ".")
            || strstr($_POST['password'], "~") || strstr($_POST['password'], "|"))
        {
            display_error( _("The new password cannot contain any special characters"));
            set_focus('password');
            return false;
        }

        if (!$uppercase || !$lowercase || !$number)
        {
            display_error( _("The password should include at least one upper case letter, one lower case letter, one number"));
            set_focus('password');
            return false;
        }
	}

	return true;
}

//-------------------------------------------------------------------------------------------------

if (($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') && check_csrf_token())
{
    $datenow =Today();
    $passupdate = date('Y-m-d', strtotime($datenow));
	if (can_process($Mode == 'ADD_ITEM'))
	{
    	if ($selected_id != -1) 
    	{
    		update_user_prefs($selected_id,
    			get_post(array('user_id', 
					'real_name', 
					'phone', 'email', 'role_id', 'language',
					'print_profile', 'rep_popup' => 0, 'pos', 'inactive' => 0)
				)
			);

    		if ($_POST['password'] != "")
    			update_user_password($selected_id, $_POST['user_id'], md5($_POST['password']), $passupdate);

    		display_notification_centered(_("The selected user has been updated."));
    	} 
    	else 
    	{
    		add_user(
				$_POST['user_id'], $_POST['real_name'], md5($_POST['password']),
				$_POST['phone'], $_POST['email'], $_POST['role_id'], $_POST['language'],
				$_POST['print_profile'], check_value('rep_popup'), $_POST['pos'], $passupdate
			);
			
			$id = db_insert_id();
			// use current user display preferences as start point for new user
			$prefs = $_SESSION['wa_current_user']->prefs->get_all();
			
			update_user_prefs($id, array_merge($prefs, get_post(array('print_profile',
				'rep_popup' => 0, 'language'))));

			display_notification_centered(_("A new user has been added."));
    	}
		$Mode = 'RESET';
	}
}

//-------------------------------------------------------------------------------------------------

if ($Mode == 'Delete' && check_csrf_token())
{
	$cancel_delete = 0;
    if (key_in_foreign_table($selected_id, 'audit_trail', 'user'))
    {
        $cancel_delete = 1;
        display_error(_("Cannot delete this user because entries are associated with this user."));
    }
    if ($cancel_delete == 0) 
    {
    	delete_user($selected_id);
    	display_notification_centered(_("User has been deleted."));
    } //end if Delete group
    $Mode = 'RESET';
}

if ($Mode == 'RESET')
{
 	$selected_id = -1;
	$sav = get_post('show_inactive', null);
	unset($_POST);	// clean all input fields
	$_POST['show_inactive'] = $sav;
}
//-------------------------------------------------------------------------------------------------
function user_login($row) {
	return $row['user_id'];
}

function real_name($row) {
	return $row['real_name'];
}

function phone_num($row) {
	return $row['phone'];
}

function email($row) {
	return $row['email'];
}

function last_visit($row) {

	$time_format = (user_date_format() == 0 ? "h:i a" : "H:i");

	return sql2date($row["last_visit_date"]). " " . 
		date($time_format, strtotime($row["last_visit_date"]));
}

function access_level($row) {
	return $row['role'];
}

function btn_edit($row) {
	return edit_button_cell("Edit".$row["id"], _("Edit"));
}

function btn_delete($row) {

	$not_me = strcasecmp($row["user_id"], $_SESSION["wa_current_user"]->username);

	return $not_me ? delete_button_cell("Delete".$row["id"], _("Delete")) : '';
}

function btn_branch($row) {

	$not_me = strcasecmp($row["user_id"], $_SESSION["wa_current_user"]->username);

	/*
	if ($not_me) {
		$res = button_cell("Branch".$row["id"], _("Branch"), false, ICON_DOC);
	}
	else {
		
	} */

	$res = "<a href='#' onclick='window_show(".$row["id"].")'>".set_icon(ICON_DOC)."</a>";
	return $res;
}

function chk_inactive($row) {

	$not_me = strcasecmp($row["user_id"], $_SESSION["wa_current_user"]->username);
	$res = '';

	if ($not_me) {
		$res = inactive_control_cell($row["id"], $row["inactive"], 'users', 'id');
	}

	return $res;
}

function inactive_stat($row) {
	return $row['inactive'] == 1 ? "Yes" : "No";
}

if (get_post("search")) {
	$Ajax->activate("usr_tbl");
}

//-------------------------------------------------------------------------------------------------
start_form(true, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);
start_row();

text_cells(_("User ID / Full Name / Access Level: "), "searchval");
submit_cells("search", _("Search User"), "", _("Search User"), "default");

end_row();
end_table(); 

//-------------------------------------------------------------------------------------------------

$sql = get_users(check_value('show_inactive'), get_post("searchval"));

$cols = array (
	_("User login") => array('fun' => 'user_login'),
	_("Branch") => array('fun' => 'btn_branch', 'align' => 'center'),
	_("Full Name") => array('fun' => 'real_name'),
	_("Phone") => array('fun' => 'phone_num'),
	_("E-mail") => array('fun' => 'email'),
	_("Last Visit") => array('fun' => 'last_visit'),
	_("Access Level") => array('fun' => 'access_level'),
	_("Inactive") => array('fun' => 'inactive_stat', 'align' => 'center'),
	array('insert' => true, 'fun' => 'btn_edit', 'align' => 'center'),
	array('insert' => true, 'fun' => 'btn_delete', 'align' => 'center'),
);


$table = &new_db_pager('usr_tbl', $sql, $cols, null, null, 20);

$table->width = "98%";

display_db_pager($table);
br();

//-------------------------------------------------------------------------------------------------

// $result = get_users(check_value('show_inactive'));
// start_form();
// start_table(TABLESTYLE);

// $th = array(_("User login"),"Branch", _("Full Name"), _("Phone"),
// 	_("E-mail"), _("Last Visit"), _("Access Level"), "", "");

// inactive_control_column($th);
// table_header($th);	

// $k = 0; //row colour counter

// while ($myrow = db_fetch($result)) 
// {

// 	alt_table_row_color($k);

// 	$time_format = (user_date_format() == 0 ? "h:i a" : "H:i");
// 	$last_visit_date = sql2date($myrow["last_visit_date"]). " " . 
// 		date($time_format, strtotime($myrow["last_visit_date"]));

// 	/*The security_headings array is defined in config.php */
// 	$not_me = strcasecmp($myrow["user_id"], $_SESSION["wa_current_user"]->username);

// 	label_cell($myrow["user_id"]);
// 	//if ($not_me)
// 	//    button_cell("Branch".$myrow["id"], _("Branch"), false, ICON_DOC);
// 	//    else
// 	label_cell("<a href='#' onclick='window_show(".$myrow["id"].")'>".set_icon(ICON_DOC)."</a>","align=center");
// 	label_cell($myrow["real_name"]);
// 	label_cell($myrow["phone"]);
// 	email_cell($myrow["email"]);
// 	label_cell($last_visit_date, "nowrap");
// 	label_cell($myrow["role"]);
	
//     if ($not_me)
// 		inactive_control_cell($myrow["id"], $myrow["inactive"], 'users', 'id');
// 	elseif (check_value('show_inactive'))
// 		label_cell('');

// 	edit_button_cell("Edit".$myrow["id"], _("Edit"));
	
// 	if ($not_me)
//  		delete_button_cell("Delete".$myrow["id"], _("Delete"));
// 	else
// 		label_cell('');
// 	end_row();

// } //END WHILE LIST LOOP

// inactive_control_row($th);
// end_table(1);
//-------------------------------------------------------------------------------------------------
start_table(TABLESTYLE2);

inactive_control_row($cols);

$_POST['email'] = "";
if ($selected_id != -1) 
{
  	if ($Mode == 'Edit') {
		//editing an existing User
		$myrow = get_user($selected_id);

		$_POST['id'] = $myrow["id"];
		$_POST['user_id'] = $myrow["user_id"];
		$_POST['real_name'] = $myrow["real_name"];
		$_POST['phone'] = $myrow["phone"];
		$_POST['email'] = $myrow["email"];
		$_POST['role_id'] = $myrow["role_id"];
		$_POST['language'] = $myrow["language"];
		$_POST['print_profile'] = $myrow["print_profile"];
		$_POST['rep_popup'] = $myrow["rep_popup"];
		$_POST['inactive'] = $myrow["inactive"];
		$_POST['pos'] = $myrow["pos"];
	}
	hidden('selected_id', $selected_id);
	hidden('user_id');

	start_row();
	label_row(_("User login:"), $_POST['user_id']);
} 
else 
{ //end of if $selected_id only do the else when a new record is being entered
	text_row(_("User Login:"), "user_id",  null, 22, 20);
	$_POST['language'] = user_language();
	$_POST['print_profile'] = user_print_profile();
	$_POST['rep_popup'] = user_rep_popup();
	$_POST['pos'] = user_pos();
}
$_POST['password'] = "";
password_row(_("Password:"), 'password', $_POST['password']);

if ($selected_id != -1) 
{
	table_section_title(_("Enter a new password to change, leave empty to keep current."));
}

text_row_ex(_("Full Name").":", 'real_name',  50);

text_row_ex(_("Telephone No.:"), 'phone', 30);

email_row_ex(_("Email Address:"), 'email', 50);

security_roles_list_row(_("Access Level:"), 'role_id', null); 

languages_list_row(_("Language:"), 'language', null);

pos_list_row(_("User's POS"). ':', 'pos', null);

print_profiles_list_row(_("Printing profile"). ':', 'print_profile', null,
	_('Browser printing support'));

check_row(_("Use popup window for reports:"), 'rep_popup', $_POST['rep_popup'],
	false, _('Set this option to on if your browser directly supports pdf files'));

if ($selected_id != -1)  {
	check_row(_("Inactive Status:"), 'inactive', $_POST['inactive'],
		false, _("Change User Inactive Status"));
}

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');
br();

end_form();
end_page(true);
