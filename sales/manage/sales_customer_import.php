<?php
	/**********************************************
	Author: Robert Dusal
	Author: Robert Dusal - added Export of many types and import of the same
	Name: Import of CSV formatted items
	Free software under GNU GPL
	***********************************************/
	$page_security = 'SA_CUSTOMERSIMPORTS';
	$path_to_root = "../..";
	include($path_to_root . "/includes/session.inc");

	include_once($path_to_root . "/includes/ui.inc");
	include_once($path_to_root . "/includes/data_checks.inc");

	include_once($path_to_root . "/inventory/includes/inventory_db.inc");
	include_once($path_to_root . "/inventory/includes/db/items_codes_db.inc");
	include_once($path_to_root . "/dimensions/includes/dimensions_db.inc");

	include_once($path_to_root . "/includes/db_pager.inc");
	include_once($path_to_root . "/includes/session.inc");
	include_once($path_to_root . "/admin/db/company_db.inc");

	include_once($path_to_root . "/includes/date_functions.inc");
	include_once($path_to_root . "/includes/banking.inc");
	include_once($path_to_root . "/includes/ui/contacts_view.inc");
	include_once($path_to_root . "/includes/ui/attachment.inc");

	$_SESSION['language']->encoding = "UTF-8";

	function add_customer_import($name, $debtor_ref, $address, $barangay, $municipality, $province, $zip_code, $tax_id, $age, $gender,
		$status, $spouse = null, $name_father, $name_mother, $collectors_name, $curr_code, $area, $sales_type, $dimension_id, $dimension2_id,
	    $credit_status, $payment_terms, $discount, $pymt_discount, $credit_limit, $notes, $inactive, $employee_id)
	{
		$sql = "INSERT INTO ".TB_PREF."debtors_master (name, debtor_ref, address, barangay, municipality, province, zip_code, tax_id, age, gender, status, spouse, name_father, name_mother, collectors_name, curr_code, area, sales_type, dimension_id, dimension2_id, credit_status, payment_terms, discount, pymt_discount, credit_limit, notes, inactive, employee_id) VALUES ('$name', '$debtor_ref', '$address', '$barangay', '$municipality', '$province', '', '$tax_id', '$age', '$gender', '$status', 
			'$spouse', '$name_father', '$name_mother', '', 'PHP', '$area', '3', '0', '0', '1', '4', '0', '0', '$credit_limit', '', '0', '')";

		db_query($sql,"The customer could not be added");
	}

	function add_branch_import($customer_id, $name, $debtor_ref, $address, $area, $salesman,
		$default_location, $tax_group_id, $sales_account, $sales_discount_account, $receivables_account, 
		$payment_discount_account, $br_post_address, $group_no, $default_ship_via, $notes, $bank_account, $inactive)
	{
		$sql = "INSERT INTO ".TB_PREF."cust_branch (debtor_no, br_name, branch_ref, br_address, area, salesman, default_location, tax_group_id, sales_account, sales_discount_account, receivables_account, payment_discount_account, default_ship_via, br_post_address, group_no, notes, bank_account, inactive) VALUES ('$customer_id', '$name', '$debtor_ref', '$address', '$area', '1', '$default_location', '1', '', '$sales_discount_account', '$receivables_account', '$payment_discount_account', '1', '$address', '0', '', '', '0')";

		db_query($sql,"The branch record could not be added");
	}


	function add_crm_person_import($debtor_ref, $name, $name2, $address, $phone, $phone2, $fax, $email, $facebook = null, $lang, $notes, $inactive)
	{
		$sql = "INSERT INTO ".TB_PREF."crm_persons (ref, name, name2, address, phone, phone2, fax, email, facebook, lang, notes, inactive) 
				VALUES ('$debtor_ref', '$name', '', '$address', '$phone', '', '', '$email', '$facebook', '', '', '0')";

		db_query($sql,"The branch record could not be added");
	}

	function add_crm_contact_import($type, $action, $entity_id, $person_id)
	{
		$sql = "INSERT INTO ".TB_PREF."crm_contacts (person_id, type, action, entity_id)
	    VALUES ('$person_id', '$type', '$action', '$entity_id')";

		db_query($sql,"The branch record could not be added");
	}

	function check_customer_code_already_exist($debtor_ref)
	{
		$sql = "SELECT COUNT(*) FROM ".TB_PREF."debtors_master
				WHERE debtor_ref = ".db_escape($debtor_ref);
		$result = db_query($sql, "check Customer Code failed");
		$count =  db_fetch($result);

		return $count[0];
	}

	function check_customer_name_already_exist($name)
	{
		$sql = "SELECT COUNT(*) FROM ".TB_PREF."debtors_master
				WHERE name = ".db_escape($name);
		$result = db_query($sql, "check Customer name failed");
		$count =  db_fetch($result);

		return $count[0];
	}

	$action = 'import';
	if (isset($_GET['action'])) $action = $_GET['action'];
	if (isset($_POST['action'])) $action = $_POST['action'];

	page("Import of CSV Customers");

	if (isset($_POST['import'])) {
		if (isset($_FILES['impCSVS']) && $_FILES['impCSVS']['name'] != '') {
			$filename = $_FILES['impCSVS']['tmp_name'];
			$sep = $_POST['sep'];

			$fp = @fopen($filename, "r");
			if (!$fp)
				die("can not open file $filename");

				$lines = $CI = 0;
	
				while ($data = fgetcsv($fp, 4096, $sep)) {
					if ($lines++ == 0) continue;

				    list($type, $name, $address, $barangay, $municipality, $province, $tax_id, $age, $gender, $status, $spouse,
				    $name_mother, $name_father, $area, $credit_limit, $phone, $email, $facebook) = $data;

					//$debtor_ref = strtoupper($debtor_ref);

					$debtor_ref = get_Customer_AutoGenerated_Code();
					$name = utf8_encode($name);
					$address = utf8_encode($address);
					$barangay = utf8_encode($barangay);
					$province = utf8_encode($province);
					$spouse = utf8_encode($spouse);
					$name_mother = utf8_encode($name_mother);
					$name_father = utf8_encode($name_father);
					$email = utf8_encode($email);
					$facebook = utf8_encode($facebook);

					$sales_discount_account = get_company_pref('default_sales_discount_act');
					$receivables_account = get_company_pref('debtors_act');
					$payment_discount_account = get_company_pref('default_prompt_payment_act');
					$brchcode = $db_connections[user_company()]["branch_code"];

					$age = date('Y-m-d', strtotime($age));

					if ($credit_limit == '') {					
						display_error("Line $lines: The credit limit must not be empty");
						$CI++;
           				break;
					} else if ($area == '') {
						display_error("Line $lines: The area must not be empty");
           				break;
					} else if (check_customer_code_already_exist($debtor_ref)) {
				        $sql = "SELECT debtor_ref FROM ".TB_PREF."debtors_master WHERE debtor_ref = ".db_escape($debtor_ref);
				        $result = db_query($sql, "Could not search Customer Code");
				        $row = db_fetch_row($result);
				        $CI++;	
				        display_error("Line $lines: The Customer Could Not Be Added Customer Code: $debtor_ref is Already Exist");	
           				break;
			    	} else if (check_customer_name_already_exist($name)) {
			    		$sql = "SELECT name FROM ".TB_PREF."debtors_master WHERE name = ".db_escape($name);
				        $result = db_query($sql, "Could not search Customer Name");
				        $row = db_fetch_row($result);
				        $CI++;	
				        display_error("Line $lines: The Customer Could Not Be Added Customer Name: $name is Already Exist");	
           				break;
			    	} elseif ($type == 'CSVCUSTOM') {	
			    		
						add_customer_import($name, $debtor_ref, $address, $barangay, $municipality, $province, $zip_code, $tax_id, $age,
						$gender, $status, $spouse, $name_father, $name_mother, $collectors_name, $curr_code, $area, $sales_type, 
						$dimension_id, $dimension2_id, $credit_status, $payment_terms, $discount, $pymt_discount, $credit_limit, 
						$notes, $inactive, $employee_id);

						$selectedlast_id = db_insert_id();
						db_query($sql, "The item could not be added");
						$CI++;	
				   	    display_notification("Line $lines: The Customer is Added Customer Name: $name AND Customer Code: $debtor_ref");

				   	    if (isset($SysPrefs->auto_create_branch) && $SysPrefs->auto_create_branch == 1)
						{
							add_branch_import($selectedlast_id, $name, $debtor_ref, $address, $area, $salesman,
							$brchcode, $tax_group_id, $sales_account, get_company_pref('default_sales_discount_act'), 
							get_company_pref('debtors_act'), get_company_pref('default_prompt_payment_act'), $default_ship_via,
						    $address, $group_no, $default_ship_via, $notes, $bank_account, $inactive);

        					$selectedlast_branch_id = db_insert_id();
							db_query($sql, "The item could not be added");
					   	    display_notification("Line $lines: The Default Branch Customer is Added Customer Name: $name AND Customer Code: $debtor_ref");

					   	    add_crm_person_import($debtor_ref, $name, $name2, $address, $phone, $phone2, $fax, $email, $facebook, 
					   	    $lang, $notes, $inactive);

							$selectedlast_pers_id = db_insert_id();				   
							db_query($sql, "The item could not be added");
					   	    display_notification("Line $lines: The Default Customer Contacts is Added Customer Name: $name AND Customer Code: $debtor_ref");

					   	    add_crm_contact_import('cust_branch', 'general', $selectedlast_branch_id, $selectedlast_pers_id);

							add_crm_contact_import('customer', 'general', $selectedlast_branch_id, $selectedlast_pers_id);
					   	}
				   	} else {
				   		display_error("ERROR: Please check the Import customer template CSV file if correct..");
           				break;
				   	}						
				}			
			@fclose($fp);
			if ($CI > 0) display_notification("$CI : Customer is Added.");
		} else display_error("No CSV file selected");
	}

	if ($action == 'import') echo 'IMPORT CUSTOMERS';
	else hyperlink_params($_SERVER['PHP_SELF'], _("Import"), "action=import", false);
	echo "<br><br>";

	if ($action == 'import') {
		start_form(true);

		start_table(TABLESTYLE2, "width=45%");

		if (!isset($_POST['sep']))
		$_POST['sep'] = ",";

		table_section_title("Import Customers");
		text_row("Field separator:", 'sep', $_POST['sep'], 2, 1);
		label_row("CSV Import File:", "<input type='file' id='impCSVS' name='impCSVS'>");

		end_table(1);
		submit_center('import', "Import CSV File");
		end_form();
		end_page();
	}
?>

