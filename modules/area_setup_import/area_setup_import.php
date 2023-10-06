<?php
	/**********************************************
	Author: Robert Dusal
	Author: Robert Dusal - added Export of many types and import of the same
	Name: Import of CSV formatted items
	Free software under GNU GPL
	***********************************************/
	$page_security = 'SA_AREA_IMPORTS';
	$path_to_root = "../..";

	include($path_to_root . "/includes/session.inc");
	include_once($path_to_root . "/includes/ui.inc");
	include_once($path_to_root . "/includes/data_checks.inc");
	include_once($path_to_root . "/includes/session.inc");
	include_once($path_to_root . "/admin/db/company_db.inc");
	include_once($path_to_root . "/includes/date_functions.inc");

	add_access_extensions();

	$_SESSION['language']->encoding = "UTF-8";

	function add_area_import($description, $collectors_id, $description_area)
	{
		$sql = "INSERT INTO ".TB_PREF."areas (description, collectors_id, collector_description)
		VALUES (".db_escape($description).", ".db_escape($collectors_id) .", ".db_escape($description_area).")";

		return db_query($sql, "could not add area for $description");
	}

	function update_area_import($description, $collectors_id, $description_area)
	{
		$sql = "UPDATE ".TB_PREF."areas SET 
		collectors_id=".db_escape($collectors_id) . ",
		collector_description = ".db_escape($description_area). " 
		WHERE description=".db_escape($description);

		return db_query($sql, "could not update area for $description");
	}

	$action = 'import';
	if (isset($_GET['action'])) $action = $_GET['action'];
	if (isset($_POST['action'])) $action = $_POST['action'];

	page("Import of CSV Collector Area Setup");

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

				    list($type, $description, $collectors, $description_area) = $data;
				   
				    if ($type == 'CSVAREASET') {							
						if(strlen($collectors) == 1)
						{
							$collectors_id = "00000"."".$collectors;
						}elseif(strlen($collectors) == 2)
						{
							$collectors_id = "0000"."".$collectors;
						}elseif(strlen($collectors) == 3)
						{
							$collectors_id = "000"."".$collectors;
						}elseif(strlen($collectors) == 4)
						{
							$collectors_id = "00"."".$collectors;
						}elseif(strlen($collectors) == 5)
						{
							$collectors_id = "0"."".$collectors;
						}elseif(strlen($collectors) == 6)
						{
							$collectors_id = $collectors;
						}
						
					    $sql = "SELECT description FROM ".TB_PREF."areas WHERE description='$description'";
					    $result = db_query($sql,"Area name not be retreived");
					    $row = db_fetch_row($result);	

					    if (!$row) {
					    	add_area_import($description, $collectors_id, $description_area);

						    db_query($sql, "The area could not be added");
					   	    display_notification("Line $lines: The area is added Area name: $description  Collectors id: $collectors_id");
							$UI++;	
					    }else{
					    	update_area_import($description, $collectors_id, $description_area);
					    	db_query($sql, "The user could not be added");
					   	    display_notification("Line $lines: The area is update Area name: $description");
							$UJ++;	
					    }
					}else{
				   		display_error("ERROR: Please check the Import area template CSV file if correct..");
					 	break;
					}											
				}			
			@fclose($fp);
			if ($UI+$UJ > 0) display_notification("$UI Area Added, $UJ Area Updated.");
			if ($UI+$UJ > 0) display_notification("Import Successful.");
		} else display_error("No CSV file selected");
	}

	if ($action == 'import');
	else hyperlink_params($_SERVER['PHP_SELF'], _("Import"), "action=import", false);
	echo "<br><br>";

	if ($action == 'import') {
		start_form(true);

		start_table(TABLESTYLE2, "width=45%");

		if (!isset($_POST['sep']))
		$_POST['sep'] = ",";

		table_section_title("Impor Collector Area Setup");
		text_row("Field separator:", 'sep', $_POST['sep'], 2, 1);
		label_row("CSV Import File:", "<input type='file' id='impCSVS' name='impCSVS'>");

		end_table(1);
		submit_center('import', "Import CSV File");
		end_form();
		end_page();
	}
?>

