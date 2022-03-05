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
$page_security = 'SA_CREATECOMPANY';
$path_to_root="..";
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/admin/db/company_db.inc");
include_once($path_to_root . "/admin/db/maintenance_db.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/admin/db/branch_areas_db.inc");

//include_once($path_to_root . "/modules/create_backup_for_ho/includes/maintenance_backup_to_ho_db.inc");

page(_($help_context = "Create/Update Branch"));

$comp_subdirs = array('images', 'pdf_files', 'backup','js_cache', 'reporting', 'attachments');

simple_page_mode(true);
/*
	FIXME: tb_pref_counter should track prefix per database.
*/
//---------------------------------------------------------------------------------------------
function check_data($selected_id)
{
	global $db_connections, $tb_pref_counter;

	if ($selected_id != -1) {
		if ($_POST['name'] == "")
		{
			display_error(_("Database settings are not specified."));
	 		return false;
		}
	} else {
	    if (!get_post('name') || !get_post('branch_code') || !get_post('partner_code') || !get_post('type') || !get_post('host') || !get_post('dbuser') || !get_post('dbname'))
		{
			display_error(_("Database settings are not specified."));
	 		return false;
		}
		if ($_POST['port'] != '' && !is_numeric($_POST['port'])) 
		{
			display_error(_('Database port has to be numeric or empty.'));
			return false;
		}
	
		foreach($db_connections as $id=>$con)
		{
		 if($id != $selected_id && $_POST['host'] == $con['host'] 
		 	&& $_POST['dbname'] == $con['dbname'])
	  		{
				if ($_POST['tbpref'] == $con['tbpref'])
				{
					display_error(_("This database settings are already used by another company."));
					return false;
				}
				if (($_POST['tbpref'] == 0) ^ ($con['tbpref'] == ''))
				{
					display_error(_("You cannot have table set without prefix together with prefixed sets in the same database."));
					return false;
				}
		  	}
		}
	}
	return true;
}

//---------------------------------------------------------------------------------------------

function remove_connection($id) {
	global $db_connections;

	$err = db_drop_db($db_connections[$id]);

	unset($db_connections[$id]);
	$conn = array_values($db_connections);
	$db_connections = $conn;
    return $err;
}

//Added by Robert
function db_import_ho($filename, $connection, $branch_code, $force=true, $init=true, $protect=false, $return_errors=false)
{
	global $db, $SysPrefs;

	$branch_id = Get_db_coy($branch_code);
	set_global_connection($branch_id);

	$trail = $SysPrefs->sql_trail;
	$SysPrefs->sql_trail = false;

	$allowed_commands = array(
		"create"  => 'table_queries', 
		"delimiter" => 'table_queries',
		"alter table" => 'table_queries', 
		"insert" => 'data_queries', 
		"update" => 'data_queries', 
		"set names" => 'set_names',
		"drop table if exists" => 'drop_queries',
		"drop function if exists" => 'drop_queries',
		"drop trigger if exists" => 'drop_queries',
		"select" => 'data_queries', 
		"delete" => 'data_queries',
		"drop view if exists" => 'drop_queries',
		"create view as" => 'data_queries'		//we should be able to create views after all tables have been created 
		);

	$protected = array(
		'security_roles',
		'users'
	);

	$ignored_mysql_errors = array( //errors ignored in normal (non forced) mode
		'1022',	// duplicate key
		'1050', // Table %s already exists
		'1060', // duplicate column name
		'1061', // duplicate key name
		'1062', // duplicate key entry
		'1091'  // can't drop key/column check if exists
	);

	$set_names = array();
	$data_queries = array();
	$drop_queries = array();
	$table_queries = array();
	$sql_errors = array();

	$old_encoding = db_get_charset($db);

	ini_set("max_execution_time", max("180", ini_get("max_execution_time")));
	db_query("SET foreign_key_checks=0");
	db_query("SET sql_mode=''");

	if (isset($connection['collation']))
		db_set_collation($db, $connection['collation']);

	$check_line_len = false;

	// uncompress gziped backup files
	if (strpos($filename, ".gz") || strpos($filename, ".GZ"))
	{	$lines = db_ungzip("lines", $filename);
		$check_line_len = true;
	} elseif (strpos($filename, ".zip") || strpos($filename, ".ZIP"))
		$lines = db_unzip("lines", $filename);
	else
		$lines = file("". $filename);

	// parse input file
	$query_table = '';
	$delimiter = ';';

	foreach($lines as $line_no => $line)
	{
		$gzfile_bug = $check_line_len && (strlen($line) == 8190); // there is a bug in php (at least 4.1.1-5.5.9) gzfile which limits line length to 8190 bytes!

		$line = trim($line);
		if ($init)
			$line = str_replace("0_", $connection["tbpref"], $line);

		if ($query_table == '') 
		{	// check if line begins with one of allowed queries
		 	foreach($allowed_commands as $cmd => $table) 
			{
				if (strtolower(substr($line, 0, strlen($cmd))) == $cmd) 
				{
					if ($cmd == 'delimiter') {
						$delimiter = trim(substr($line, 10));
						continue 2;
					}
					$query_table = $table;
					$skip = false;
					if ($protect)
					{
						foreach($protected as $protbl)
							if (strpos($line, $connection["tbpref"].$protbl) !== false)
							{
								$skip = true; break;
							}
					}
					if (!$skip)
						${$query_table}[] = array('', $line_no+1);
					break;
				}
		 	}
		 }
		 if($query_table != '')  // inside allowed query
		 {
		 	$table = $query_table;
			if (!$gzfile_bug && substr($line, -strlen($delimiter)) == $delimiter) // end of query found 
			{
				$line = substr($line, 0, strlen($line) - strlen($delimiter)); // strip delimiter
				$query_table = '';
			}
			if (!$skip)
				${$table}[count(${$table}) - 1][0] .= $line . "\n";
		}

	}

	//
	// 'set names' or equivalents should be used only on post 2.3 FA versions
	// otherwise text encoding can be broken during import
	//
	$encoding = null; // UI encoding for default site language is the default
	$new_db = $init || db_fixed();
	$new_file = count($set_names);
	if ($new_db)
	{
		if ($new_file)
		{
			if (count($set_names)) // standard db restore
			{
				if (preg_match('/set\s*names\s*[\']?(\w*)[\']?/i', $set_names[0][0], $match))
					$encoding = $match[1];
			}
			// otherwise use default site ui encoding
		}
	}
	else
	{
		if ($new_file) // import on old db is forbidden: this would destroy db content unless latin1 was used before in UI
		{
			$msg = _("This is new format backup file which cannot be restored on database not migrated to utf8.");
			if ($return_errors)
				return $msg;
			else
				display_error($msg);
			return false;
		}
		 else	// backup restore during upgrade failure
			$encoding = 'latin1'; // standard encoding on mysql client
	}

	db_set_charset($db, $encoding);

/*/	{ 	// for debugging purposes
	global $path_to_root;
	$f = fopen($path_to_root.'/tmp/dbimport.txt', 'w+');
	fwrite($f, print_r($set_names,true) ."\n");
	fwrite($f, print_r($drop_queries,true) ."\n");
	fwrite($f, print_r($table_queries,true) ."\n");
	fwrite($f, print_r($data_queries,true));
	fclose($f);
	}
/*/
	if ($return_errors)
	{	// prevent errors display
		$save_debug = $SysPrefs->go_debug;
		$SysPrefs->go_debug = 0;
	}
	// execute drop tables if exists queries
	if (is_array($drop_queries))
	{
		foreach($drop_queries as $drop_query)
		{
			if (!db_query($drop_query[0]))
			{
				if (!in_array(db_error_no(), $ignored_mysql_errors) || !$force)
					$sql_errors[] = array(db_error_msg($db), $drop_query[1]);
			}
		}
	}

	// execute create tables queries
	if (is_array($table_queries))
	{
		foreach($table_queries as $table_query)
		{
			if (!db_query($table_query[0]))
			{	
				if (!in_array(db_error_no(), $ignored_mysql_errors) || !$force) {
					$sql_errors[] = array(db_error_msg($db), $table_query[1]);
				}
			}
		}
	}

	// execute insert data queries
	if (is_array($data_queries))
	{
		foreach($data_queries as $data_query)
		{
			if (!db_query($data_query[0]))
			{
				if (!in_array(db_error_no(),$ignored_mysql_errors) || !$force)
					$sql_errors[] = array(db_error_msg($db), $data_query[1]);
			}
		}
	}

	if ($return_errors)
		$SysPrefs->go_debug = $save_debug;

	$SysPrefs->sql_trail = $trail;

	db_query("SET foreign_key_checks=1");
	if ($delimiter != ';') db_query("delimiter ;"); // just for any case

	db_set_charset($db, $old_encoding); // restore connection encoding

	if (count($sql_errors)) {
		if ($return_errors)
			return $sql_errors;

		// display first failure message; the rest are probably derivative 
		$err = $sql_errors[0];
		display_error(sprintf(_("SQL script execution failed in line %d: %s"),
			$err[1], $err[0]));
		return false;
	} else
		return true;
}
//---------------------------------------------------------------------------------------------
function handle_submit($selected_id)
{
	global $db_connections, $def_coy, $tb_pref_counter, $db,
	    $comp_subdirs, $path_to_root, $Mode;

	$error = false;

	if ($selected_id==-1)
		$selected_id = count($db_connections);

	$new = !isset($db_connections[$selected_id]);

	if (check_value('def'))
		$def_coy = $selected_id;

	$db_connections[$selected_id]['name'] = $_POST['name'];
	$db_connections[$selected_id]['address'] = $_POST['address'];
	$db_connections[$selected_id]['branch_area'] = $_POST['branch_area'];
	$db_connections[$selected_id]['branch_code'] = $_POST['branch_code'];
	$db_connections[$selected_id]['partner_code'] = $_POST['partner_code'];
	$db_connections[$selected_id]['type'] = $_POST['type'];
	$db_connections[$selected_id]['gl_account'] = $_POST['gl_account'];
	$db_connections[$selected_id]['ap_account'] = $_POST['ap_account'];
	if ($new) {
		$db_connections[$selected_id]['host'] = $_POST['host'];
		$db_connections[$selected_id]['port'] = $_POST['port'];
		$db_connections[$selected_id]['dbuser'] = $_POST['dbuser'];
		$db_connections[$selected_id]['dbpassword'] = html_entity_decode($_POST['dbpassword'], ENT_QUOTES, 
			$_SESSION['language']->encoding=='iso-8859-2' ? 'ISO-8859-1' : $_SESSION['language']->encoding);
		$db_connections[$selected_id]['dbname'] = $_POST['dbname'];
		$db_connections[$selected_id]['collation'] = $_POST['collation'];
		if (is_numeric($_POST['tbpref']))
		{
			$db_connections[$selected_id]['tbpref'] = $_POST['tbpref'] == 1 ?
			  $tb_pref_counter."_" : '';
		}
		else if ($_POST['tbpref'] != "")
			$db_connections[$selected_id]['tbpref'] = $_POST['tbpref'];
		else
			$db_connections[$selected_id]['tbpref'] = "";

		$conn = $db_connections[$selected_id];
		if (($db = db_create_db($conn)) === false)
		{
			display_error(_("Error creating Database: ") . $conn['dbname'] . _(", Please create it manually"));
			$error = true;
		} else {
			if (strncmp(db_get_version(), "5.6", 3) >= 0) 
				db_query("SET sql_mode = ''");
			if (!db_import($path_to_root.'/sql/'.get_post('coa'), $conn, $selected_id)) {
				display_error(_('Cannot create new company due to bugs in sql file.'));
				$error = true;
			} 
			else
			{
				if (!isset($_POST['admpassword']) || $_POST['admpassword'] == "")
					$_POST['admpassword'] = "password";
				update_admin_password($conn, md5($_POST['admpassword']));
			}
		}
		if ($error) {
			remove_connection($selected_id);
			return false;
		}
	}
	$error = write_config_db($new);

	if ($error == -1)
		display_error(_("Cannot open the configuration file - ") . $path_to_root . "/config_db.php");
	else if ($error == -2)
		display_error(_("Cannot write to the configuration file - ") . $path_to_root . "/config_db.php");
	else if ($error == -3)
		display_error(_("The configuration file ") . $path_to_root . "/config_db.php" . _(" is not writable. Change its permissions so it is, then re-run the operation."));
	if ($error != 0)
	{
		return false;
	}

	if ($new)
	{
		create_comp_dirs(company_path($selected_id), $comp_subdirs);
		$exts = get_company_extensions();
		write_extensions($exts, $selected_id);
	}
	display_notification($new ? _('New company has been created.') : _('Company has been updated.'));

	$Mode = 'RESET';
	return true;
}

//---------------------------------------------------------------------------------------------

function handle_delete($id)
{
	global $Ajax, $def_coy, $db_connections, $comp_subdirs, $path_to_root, $Mode;

	// First make sure all company directories from the one under removal are writable. 
	// Without this after operation we end up with changed per-company owners!
	for($i = $id; $i < count($db_connections); $i++) {
			$comp_path = company_path($i);
		if (!is_dir($comp_path) || !is_writable($comp_path)) {
			display_error(_('Broken company subdirectories system. You have to remove this company manually.'));
			return;
		}
	}
	// make sure config file is writable
	if (!is_writeable($path_to_root . "/config_db.php"))
	{
		display_error(_("The configuration file ") . $path_to_root . "/config_db.php" . _(" is not writable. Change its permissions so it is, then re-run the operation."));
		return;
	}
	// rename directory to temporary name to ensure all
	// other subdirectories will have right owners even after
	// unsuccessfull removal.
	$cdir = company_path($id);
	$tmpname  = company_path('/old_'.$id);
	if (!@rename($cdir, $tmpname)) {
		display_error(_('Cannot rename subdirectory to temporary name.'));
		return;
	}
	// 'shift' company directories names
	for ($i = $id+1; $i < count($db_connections); $i++) {
		if (!rename(company_path($i), company_path($i-1))) {
			display_error(_("Cannot rename company subdirectory"));
			return;
		}
	}
	$err = remove_connection($id);
	if ($err == 0)
		display_error(_("Error removing Database: ") . $id . _(", please remove it manually"));

	if ($def_coy == $id)
		$def_coy = 0;

	$error = write_config_db();
	if ($error == -1)
		display_error(_("Cannot open the configuration file - ") . $path_to_root . "/config_db.php");
	else if ($error == -2)
		display_error(_("Cannot write to the configuration file - ") . $path_to_root . "/config_db.php");
	else if ($error == -3)
		display_error(_("The configuration file ") . $path_to_root . "/config_db.php" . _(" is not writable. Change its permissions so it is, then re-run the operation."));
	if ($error != 0) {
		@rename($tmpname, $cdir);
		return;
	}
	// finally remove renamed company directory
	@flush_dir($tmpname, true);
	if (!@rmdir($tmpname))
	{
		display_error(_("Cannot remove temporary renamed company data directory ") . $tmpname);
		return;
	}
	display_notification(_("Selected company has been deleted"));
	$Ajax->activate('_page_body');
	$Mode = 'RESET';
}

function get_backup_file_combo_ho()
{
	global $path_to_root, $Ajax, $SysPrefs;
	
	$ar_files = array();
    default_focus('backups');
    $dh = opendir($SysPrefs->backup_dir_ho());
	while (($file = readdir($dh)) !== false)
		$ar_files[] = $file;
	closedir($dh);

    rsort($ar_files);
	$opt_files = "";
    foreach ($ar_files as $file)
		if (preg_match("/.sql(.zip|.gz)?$/", $file))
    		$opt_files .= "<option value='$file'>$file</option>";

	$selector = "<select name='backups' size=2 style='height:80px;min-width:240px'>$opt_files</select>";

	$Ajax->addUpdate('backups', "_backups_sel", $selector);
	$selector = "<span id='_backups_sel'>".$selector."</span>\n";

	return $selector;
}
//---------------------------------------------------------------------------------------------

function display_companies()
{
	global $def_coy, $db_connections, $supported_collations, $SysPrefs;

	$coyno = user_company();

	start_table(TABLESTYLE);

	$th = array(_("Branch Name"), _("Branch Area"),_("Branch Code"), _("Partner Code"), _("Type"), _("A/R Account"), _("A/P Account"), _("Database Host"), _("Database Port"), _("Database User"),
		_("Database Name"), _("Table Pref"), _("Charset"), _("Default"), "", "", "Backup");
	table_header($th);

	//Added by Robert
	$backup_name = clean_file_name(get_post('backups'));
	$backup_path_ho= $SysPrefs->backup_dir_ho() . $backup_name;
	$conns = $db_connections[$selected_id];


	$k=0;
	$conn = $db_connections;
	$n = count($conn);
	for ($i = 0; $i < $n; $i++)
	{
		
		if ($i == $coyno)
    		start_row("class='stockmankobg'");
    	else
    		alt_table_row_color($k);

		label_cell($conn[$i]['name']);
		//label_cell($conn[$i]['address']);
		label_cell($conn[$i]['branch_area']);
		label_cell($conn[$i]['branch_code']);
		label_cell($conn[$i]['partner_code']);
		label_cell($conn[$i]['type']);
		label_cell($conn[$i]['gl_account']);
		label_cell($conn[$i]['ap_account']);
		label_cell($conn[$i]['host']);
		label_cell(isset($conn[$i]['port']) ? $conn[$i]['port'] : '');
		label_cell($conn[$i]['dbuser']);
		label_cell($conn[$i]['dbname']);
		label_cell($conn[$i]['tbpref']);
		label_cell(isset($conn[$i]['collation']) ? $supported_collations[$conn[$i]['collation']] : '');
		label_cell($i == $def_coy ? _("Yes") : _("No"));
	 	edit_button_cell("Edit".$i, _("Edit"));
		if ($i != $coyno)
		{
	 		delete_button_cell("Delete".$i, _("Delete"));
			submit_js_confirm("Delete".$i, 
				sprintf(_("You are about to remove company \'%s\'.\nDo you want to continue ?"), 
					$conn[$i]['name']));
	 	} else
	 		label_cell('');

	 	//Added by Robert
	 	if (get_post('restore_ho'.$i)) {
			if ($backup_name) {
				if (db_import_ho($backup_path_ho, $conn, $conn[$i]['branch_code'], true, false, check_value('protected')))
					display_notification(_("Restore backup completed - ")." ". $conn[$i]['name']);

				$SysPrefs->refresh(); // re-read system setup
			} else
				display_error(_("Select backup file first."));
		}

	 	//Added by Robert
	 	if ($i != $coyno)
		{	
			submit_row_backup('restore_ho'.$i, _("Restore Backup"), false, '','', 'process');
			submit_js_confirm('restore_ho'.$i,
				sprintf( _("You are about to restore database from backup file \'%s\'.\nDo you want to continue?"), 
				$conn[$i]['name']));
	 	} else
	 		label_cell(get_backup_file_combo_ho());

		end_row();
	}

	end_table();
    display_note(_("The marked company is the current company which cannot be deleted."), 0, 0, "class='currentfg'");
    display_note(_("If no Admin Password is entered, the new Admin Password will be '<b>password</b>' by default "));
    display_note(_("Set Only Port value if you cannot use the default port 3306."));
}

//---------------------------------------------------------------------------------------------

function display_company_edit($selected_id)
{
	global $def_coy, $db_connections, $tb_pref_counter;

	start_table(TABLESTYLE2);

	if ($selected_id != -1)
	{
		$conn = $db_connections[$selected_id];
		$_POST['name'] = $conn['name'];
		$_POST['address'] = $conn['address'];
		$_POST['branch_area'] = $conn['branch_area'];
		$_POST['branch_code'] = $conn['branch_code'];
		$_POST['partner_code'] = $conn['partner_code'];
		$_POST['type'] = $conn['type'];
		$_POST['gl_account'] = $conn['gl_account'];
		$_POST['ap_account'] = $conn['ap_account'];
		$_POST['host']  = $conn['host'];
		$_POST['port'] = isset($conn['port']) ? $conn['port'] : '';
		$_POST['dbuser']  = $conn['dbuser'];
		$_POST['dbpassword']  = $conn['dbpassword'];
		$_POST['dbname']  = $conn['dbname'];
		$_POST['tbpref']  = $conn['tbpref'];
		$_POST['def'] = $selected_id == $def_coy;
		$_POST['dbcreate']  = false;
		$_POST['collation']  = isset($conn['collation']) ? $conn['collation'] : '';
		hidden('tbpref', $_POST['tbpref']);
		hidden('dbpassword', $_POST['dbpassword']);
	}
	else
	{
		$_POST['tbpref'] = $tb_pref_counter."_";

		// Use current settings as default
		$conn = $db_connections[user_company()];
		$_POST['name'] = '';
		$_POST['address'] = $conn['address'];
		$_POST['branch_area'] = $conn['branch_area'];
		$_POST['branch_code'] = $conn['branch_code'];
		$_POST['partner_code'] = $conn['partner_code'];
		$_POST['type'] = $conn['type'];
		$_POST['gl_account'] = $conn['gl_account'];
		$_POST['ap_account'] = $conn['ap_account'];
		$_POST['host']  = $conn['host'];
		$_POST['port']  = isset($conn['port']) ? $conn['port'] : '';
		$_POST['dbuser']  = $conn['dbuser'];
		$_POST['dbpassword']  = $conn['dbpassword'];
		$_POST['dbname']  = $conn['dbname'];
		$_POST['collation']  = isset($conn['collation']) ? $conn['collation'] : '';
		unset($_POST['def']);
	}

	text_row_ex(_("Branch Name:"), 'name', 50);
	text_row_ex(_("Address:"), 'address', 50);
	branch_area_list_row(_('Branch Area:'), 'branch_area', null,_('No Branch Area'));
	text_row_ex(_("Branch Code:"), 'branch_code', 20);
	text_row_ex(_("Partner Code:"), 'partner_code', 20);
	company_type_row(_("Type:"), 'type', $_POST['type']);
	gl_all_accounts_list_row(_("A/R Account:"), 'gl_account', $_POST['gl_account']);
	gl_all_accounts_list_row(_("A/P Account:"), 'ap_account', $_POST['ap_account']);

	if ($selected_id == -1)
	{
		text_row_ex(_("Host"), 'host', 30, 60);
		text_row_ex(_("Port"), 'port', 30, 60);
		text_row_ex(_("Database User"), 'dbuser', 30);
		text_row_ex(_("Database Password"), 'dbpassword', 30);
		text_row_ex(_("Database Name"), 'dbname', 30);
		collations_list_row(_("Database Collation:"), 'collation');
		yesno_list_row(_("Table Pref"), 'tbpref', 1, $_POST['tbpref'], _("None"), false);
		check_row(_("Default Branch"), 'def');
		coa_list_row(_("Database Script"), 'coa');
		text_row_ex(_("New script Admin Password"), 'admpassword', 20);
	} else {
		label_row(_("Host"), $_POST['host']);
		label_row(_("Port"), $_POST['port']);
		label_row(_("Database User"), $_POST['dbuser']);
		label_row(_("Database Name"), $_POST['dbname']);
		collations_list_row(_("Database Collation:"), 'collation');
		label_row(_("Table Pref"), $_POST['tbpref']);
		if (!get_post('def'))
			check_row(_("Default Branch"), 'def');
		else
			label_row(_("Default Branch"), _("Yes"));
	}

	end_table(1);
	hidden('selected_id', $selected_id);
}

//---------------------------------------------------------------------------------------------

if (($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') && check_data($selected_id))
	handle_submit($selected_id);

if ($Mode == 'Delete')
	handle_delete($selected_id);

if ($Mode == 'RESET')
{
	$selected_id = -1;
	unset($_POST);
}
//---------------------------------------------------------------------------------------------

start_form();

	display_companies();
	display_company_edit($selected_id);
	submit_add_or_update_center($selected_id == -1, '', 'upgrade');

end_form();

end_page();

