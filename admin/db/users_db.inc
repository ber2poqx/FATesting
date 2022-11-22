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

function add_user($user_id, $real_name, $password, $phone, $email, $role_id, 
	$language, $profile, $rep_popup, $pos, $passupdate='')
{
	$sql = "INSERT INTO ".TB_PREF."users (user_id, real_name, password"
		.", phone, email, role_id, language, pos, print_profile, rep_popup, passupdate)
		VALUES (".db_escape($user_id).", 
		".db_escape($real_name).", ".db_escape($password) .",".db_escape($phone)
		.",".db_escape($email).", ".db_escape($role_id).", ".db_escape($language)
		.", ".db_escape($pos).",".db_escape($profile).",".db_escape($rep_popup)
		.", ".db_escape($passupdate).")";

	return db_query($sql, "could not add user for $user_id");
}

//-----------------------------------------------------------------------------------------------

function update_user_password($id, $user_id, $password, $passupdate='')
{
	$sql = "UPDATE ".TB_PREF."users SET password=".db_escape($password) . ",
		user_id = ".db_escape($user_id). ", passupdate = ".db_escape($passupdate). " WHERE id=".db_escape($id);

	return db_query($sql, "could not update user password for $user_id");
}

//-----------------------------------------------------------------------------------------------

function update_user($id, $user_id, $real_name, $phone, $email, $role_id, 
	$language, $profile, $rep_popup, $pos)
{
	$sql = "UPDATE ".TB_PREF."users SET real_name=".db_escape($real_name).
	", phone=".db_escape($phone).",
		email=".db_escape($email).",
		role_id=".db_escape($role_id).",
		language=".db_escape($language).",
		print_profile=".db_escape($profile).",
		rep_popup=".db_escape($rep_popup).",
		pos=".db_escape($pos).",
		user_id = " . db_escape($user_id)
		. " WHERE id=" . db_escape($id);
	return db_query($sql, "could not update user for $user_id");
}

//-----------------------------------------------------------------------------------------------

function update_user_prefs($id, $prefs)
{
	$sql = "UPDATE ".TB_PREF."users SET ";
	foreach($prefs as $name => $value) {
		$prefs[$name] = $name.'='. db_escape($value);
	}
	$sql .= implode(',', $prefs) . " WHERE id=".db_escape($id);

	return db_query($sql, "could not update user display prefs for $id");
}

//-----------------------------------------------------------------------------------------------


function get_users($all = false, $value = '')
{

/**
 * Modified by: spyrax10
 * Date Modified: 1 Feb 2022
 */

	$sql = "SELECT u.id, '', u.user_id, u.real_name, u.phone, u.email, u.last_visit_date, r.role, u.inactive, u.password
		FROM ".TB_PREF."users u, ".TB_PREF."security_roles r
		WHERE u.role_id = r.id";

	if ($value != '') {
		$sql .= " AND (
			u.user_id LIKE " . db_escape('%' . trim($value) . '%') . " OR 
			u.real_name LIKE " . db_escape('%' . trim($value) . '%') . " OR  
			r.role LIKE " . db_escape('%' . trim($value) . '%') . ")";
	}

	if (!$all) {
		$sql .= " AND !u.inactive";
	} 


	$sql .= " ORDER BY u.id";
	return $sql; //db_query($sql, "could not get users");
}

//-----------------------------------------------------------------------------------------------

function get_user($id)
{
	$sql = "SELECT * FROM ".TB_PREF."users WHERE id=".db_escape($id);

	$result = db_query($sql, "could not get user $id");

	return db_fetch($result);
}

//-----------------------------------------------------------------------------------------------

function get_user_by_login($user_id)
{
	$sql = "SELECT * FROM ".TB_PREF."users WHERE user_id=".db_escape($user_id);

	$result = db_query($sql, "could not get user $user_id");

	return db_fetch($result);
}

//-----------------------------------------------------------------------------------------------

function get_user_by_email($email)
{
	$sql = "SELECT * FROM ".TB_PREF."users WHERE email=".db_escape($email);

	$result = db_query($sql, "could not get user for email $email");

	if (db_num_rows($result) != 1)
		return false;

	return db_fetch($result);
}

//-----------------------------------------------------------------------------------------------

function delete_user($id)
{
	$sql="DELETE FROM ".TB_PREF."users WHERE id=".db_escape($id);

	db_query($sql, "could not delete user $id");
}

//-----------------------------------------------------------------------------------------------

function get_user_auth($user_id, $password)
{

	$sql = "SELECT * FROM ".TB_PREF."users WHERE user_id = ".db_escape($user_id)." AND"
		." password=".db_escape($password);

	return db_num_rows(db_query($sql, "could not get validate user login for $user_id")) != 0;
}

//-----------------------------------------------------------------------------------------------

function update_user_visitdate($user_id)
{
	$sql = "UPDATE ".TB_PREF."users SET last_visit_date='". date("Y-m-d H:i:s") ."'
		WHERE user_id=".db_escape($user_id);

	db_query($sql, "could not update last visit date for user $user_id");
}

//-----------------------------------------------------------------------------------------------
function check_user_activity($id) 
{
	$sql = "SELECT COUNT(*) FROM ".TB_PREF."audit_trail WHERE audit_trail.user="
		. db_escape($id);
	$result = db_query($sql,"Cant check user activity");
	$ret = db_fetch($result);

	return $ret[0];
}

//-----------------------------------------------------------------------------------------------
function show_users_online()
{
	global $db, $GetText, $SysPrefs;

	if (!isset($SysPrefs->show_users_online) || $SysPrefs->show_users_online == 0 || !defined('TB_PREF') || 
		!isset($GetText) || !isset($db))
		return "";
	$result = db_query("SHOW TABLES LIKE '".TB_PREF."useronline'");	
	if (db_num_rows($result) == 1)
	{
		$timeoutseconds = 120;

		$timestamp=time();
		$timeout=$timestamp-$timeoutseconds;
		/*
		This will find out if user is from behind proxy server. 
		In that case, the script would count them all as 1 user.
		This function tryes to get real IP address.
		*/
		if (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		}
		elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED'];
		}
		elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_FORWARDED_FOR'];
		}
		elseif (isset($_SERVER['HTTP_FORWARDED'])) {
			$ip = $_SERVER['HTTP_FORWARDED'];
		}
		else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		// Add user to database
		db_query("INSERT INTO ".TB_PREF."useronline (timestamp, ip, file) VALUES ('". $timestamp ."',". db_escape($ip) .",". db_escape($_SERVER['PHP_SELF']) .")");
		//Remove users that were not online within $timeoutseconds.
		db_query("DELETE FROM ".TB_PREF."useronline WHERE timestamp<". $timeout);

		// Select online users
		$result = db_query("SELECT DISTINCT ip FROM ".TB_PREF."useronline");
		$users = db_num_rows($result);
	}
	else
		$users = 1;
	return "$users ".($users == 1 ? _("user online") : _("users online"));

}

//added by jr
function check_user_role($user_login){
	$sql = "SELECT A.role_id 
			FROM ".TB_PREF."users A 
			WHERE A.user_id = ".db_escape($user_login);
	$result = db_query($sql, "could not get user ");
	$ret = db_fetch($result);

	return $ret[0];
}

function get_user_id($user_name){
	$sql = "SELECT A.user_id 
			FROM ".TB_PREF."users A 
			WHERE A.real_name = ".db_escape($user_name);
	$result = db_query($sql, "could not get user ");
	$ret = db_fetch($result);

	return $ret[0];
}

//Modified by spyrax10 9 Feb 2022
function get_user_name($id, $by_user_id = false)
{
	set_global_connection();

    $sql = "SELECT real_name as name 
		FROM ".TB_PREF."users ";
	
	if ($by_user_id) {
		$sql .= "WHERE user_id = ".db_escape($id);
	}
	else {
		$sql .= "WHERE id = ".db_escape($id);
	}

    $result = db_query($sql, "could not get User Name");
    
    $row = db_fetch_row($result);
    
    return $row[0];
}
//

//Added by spyrax10

function get_user_role($id) {

	set_global_connection();

	$sql = "SELECT B.role 
		FROM ".TB_PREF."users A 
			INNER JOIN ".TB_PREF." security_roles B ON A.role_id = B.id
		WHERE A.id = ".db_escape($id);
		
	$result = db_query($sql, "Cant get user role!");
	$ret = db_fetch($result);

	return $ret[0];
}

function get_current_user_fullname() {
	return normalize_chars(get_user_name($_SESSION["wa_current_user"]->user));
}

function get_cashier_preparer($cashier_id = '') {

	set_global_connection();

	$sql = "SELECT B.real_name
		FROM ".TB_PREF."operator_builder A 
			INNER JOIN ".TB_PREF."users B ON A.preparer_id = B.user_id
		WHERE A.cashier_id = ".db_escape($cashier_id);
		
	$result = db_query($sql, "Cant get user cashier preparer!");
	$ret = db_fetch($result);

	return $ret[0];
}

function allowed_dcpr_users() {

	set_global_connection();

	$sql = "SELECT A.*
		FROM ".TB_PREF."users A 
			INNER JOIN ".TB_PREF." security_roles B ON A.role_id = B.id 
		WHERE B.id IN (11,15,17,18,19) 
		ORDER BY A.real_name";

	return db_query($sql, _("Cant get DCPR users!"));
}

//