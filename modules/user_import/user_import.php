<?php
	/**********************************************
	Author: Robert Dusal
	Author: Robert Dusal - added Export of many types and import of the same
	Name: Import of CSV formatted items
	Free software under GNU GPL
	***********************************************/
	$page_security = 'SA_USER_IMPORTS';
	$path_to_root = "../..";

	include($path_to_root . "/includes/session.inc");
	include_once($path_to_root . "/includes/ui.inc");
	include_once($path_to_root . "/includes/data_checks.inc");
	include_once($path_to_root . "/includes/session.inc");
	include_once($path_to_root . "/admin/db/company_db.inc");
	include_once($path_to_root . "/includes/date_functions.inc");

	add_access_extensions();

	$_SESSION['language']->encoding = "UTF-8";

	function add_user_import($user_id, $password, $real_name, $role_id, $phone, $email, 
		$language, $pos, $profile, $rep_popup, $startup_tab)
	{
		$sql = "INSERT INTO ".TB_PREF."users (user_id, password".", real_name,
		role_id, phone, email, language, pos, print_profile, rep_popup, startup_tab)
		VALUES (".db_escape($user_id).", ".db_escape($password) .", ".db_escape($real_name).",
		".db_escape($role_id).", ".db_escape($phone).", ".db_escape($email).", 'C', '1', '', '1', 'orders')";

		return db_query($sql, "could not add user for $user_id");
	}

	function update_user_import($user_id, $password, $real_name, $role_id, $phone, $email)
	{
		$sql = "UPDATE ".TB_PREF."users SET 
		password=".db_escape($password) . ",
		real_name=".db_escape($real_name) . ",
		role_id=".db_escape($role_id) . ",
		phone=".db_escape($phone) . ",
		email = ".db_escape($email). " 
		WHERE user_id=".db_escape($user_id);

		return db_query($sql, "could not update user password for $user_id");
	}

	$action = 'import';
	if (isset($_GET['action'])) $action = $_GET['action'];
	if (isset($_POST['action'])) $action = $_POST['action'];

	page("Import of CSV Users");

	if (isset($_POST['import'])) {
		if (isset($_FILES['impCSVS']) && $_FILES['impCSVS']['name'] != '') {
			$filename = $_FILES['impCSVS']['tmp_name'];
			$sep = $_POST['sep'];

			$fp = @fopen($filename, "r");
			if (!$fp)
				die("can not open file $filename");

				$lines = $UI = $UJ = 0;
	
				while ($data = fgetcsv($fp, 4096, $sep)) {
					if ($lines++ == 0) continue;

				    list($type, $user_id, $password, $real_name, $role_id, $phone, $email) = $data;

				    $password = md5($password);
				   
				    if ($type == 'CSVUSERSET') {
				    	$sql1 = "SELECT id FROM ".TB_PREF."security_roles 
						WHERE role=".db_escape(strtoupper($role_id));
						$result1 = db_query($sql1, "could not get user id");
						$row1 = db_fetch_row($result1);
					    $role_id = $row1[0];

					    $sql = "SELECT user_id FROM ".TB_PREF."users WHERE user_id='$user_id'";
					    $result = db_query($sql,"user id not be retreived");
					    $row = db_fetch_row($result);	

					    if (!$row) {
					    	add_user_import($user_id, $password, $real_name, $role_id, $phone, $email, 
					    	$language, $pos, $profile, $rep_popup, $startup_tab);

						    db_query($sql, "The user could not be added");
					   	    display_notification("Line $lines: The User is Added User-id: $user_id  Name: $real_name");
							$UI++;	
					    }else{
					    	update_user_import($user_id, $password, $real_name, $role_id, $phone, $email);
					    	db_query($sql, "The user could not be added");
					   	    display_notification("Line $lines: The User Update User-id: $user_id  Name: $real_name");
							$UJ++;	
					    }
					}else{
				   		display_error("ERROR: Please check the Import user template CSV file if correct..");
					 	break;
					}											
				}			
			@fclose($fp);
			if ($UI+$UJ > 0) display_notification("$UI User Added, $UJ User Updated.");
			if ($UI+$UJ > 0) display_notification("Import Successful.");
		} else display_error("No CSV file selected");
	}

	if ($action == 'import') echo 'IMPORT USERS';
	else hyperlink_params($_SERVER['PHP_SELF'], _("Import"), "action=import", false);
	echo "<br><br>";

	if ($action == 'import') {
		start_form(true);

		start_table(TABLESTYLE2, "width=45%");

		if (!isset($_POST['sep']))
		$_POST['sep'] = ",";

		table_section_title("Import Users");
		text_row("Field separator:", 'sep', $_POST['sep'], 2, 1);
		label_row("CSV Import File:", "<input type='file' id='impCSVS' name='impCSVS'>");

		end_table(1);
		submit_center('import', "Import CSV File");
		end_form();
		end_page();
	}
?>

