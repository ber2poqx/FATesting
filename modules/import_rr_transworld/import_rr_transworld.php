<?php
	/**********************************************
	Author: Joe Hunt
	Author: Tom Moulton - added Export of many types and import of the same
	Name: Import of CSV formatted items
	Free software under GNU GPL
	***********************************************/
	$page_security = 'SA_CSVRRIMPORT';
	$path_to_root="../..";

	include($path_to_root . "/includes/session.inc");
	add_access_extensions();

	include_once($path_to_root . "/includes/ui.inc");
	include_once($path_to_root . "/includes/data_checks.inc");

	include_once($path_to_root . "/inventory/includes/inventory_db.inc");
	include_once($path_to_root . "/inventory/includes/db/items_codes_db.inc");
	include_once($path_to_root . "/dimensions/includes/dimensions_db.inc");


	function item_brand_forimport_row($label, $name, $TYPE, $selected_id=null, $submit_on_change=false, $spec_opt=true)
	{
	    $where = false;
		$sql = "SELECT DISTINCT stock_master.brand AS BRAND, item_brand.name AS TYPE, item_brand.inactive AS INACTIVE FROM ".TB_PREF."stock_master
				INNER JOIN ".TB_PREF."item_brand ON item_brand.id = stock_master.brand 
				WHERE stock_master.category_id='14'";

	    if ($label != null)
	        echo "<td>$label</td>\n";
	    echo "<td>";

	    echo combo_input($name, $selected_id, $sql, 'TYPE', 'INACTIVE',
	        array(
	            'spec_option' => $spec_opt===true ?_("--Select--") : $spec_opt,
	            'spec_id' => -1,
	            'order' => 'BRAND',
	            'select_submit'=> $submit_on_change,
	            'async' => false
	        ) );
	    echo "</td>";

	}

	function check_serial_already_exist($eng_no)
	{
		$sql = "SELECT COUNT(*) FROM ".TB_PREF."rr_transword
				WHERE eng_no = ".db_escape($eng_no);
		$result = db_query($sql, "check serial failed");
		$count =  db_fetch($result);

		return $count[0];
	}

	function check_chasis_already_exist($frame_no)
	{
		$sql = "SELECT COUNT(*) FROM ".TB_PREF."rr_transword
				WHERE frame_no = ".db_escape($frame_no);
		$result = db_query($sql, "check chasis failed");
		$count =  db_fetch($result);

		return $count[0];
	}

	$action = 'import';
	if (isset($_GET['action'])) $action = $_GET['action'];
	if (isset($_POST['action'])) $action = $_POST['action'];

	page("Import of CSV RR Transworld formatted Items");

	if (isset($_POST['import'])) {
		if (isset($_FILES['impCSV']) && $_FILES['impCSV']['name'] != '') {
			$filename = $_FILES['impCSV']['tmp_name'];
			$sep = $_POST['sep'];
		
			$TYPE =  $_POST['TYPE'];
			$type = $TYPE;

			$fp = @fopen($filename, "r");
			if (!$fp)
				die("can not open file $filename");

				$lines = $rr = 0;
				while ($data = fgetcsv($fp, 4096, $sep)) {
					if ($lines++ == 0) continue;
				    list($type, $si_no, $code, $outlatename, $salesname, $eng_no, $frame_no, $po_no, $invt_date, $pull_date, $si_date,
				    $due_date) = $data;
					$type = strtoupper($type);
					$po_no = strtoupper($po_no);

					//$si_no = !empty($si_no) ? "$si_no" : "";
					if (empty($invt_date)){ $invt_date = "0000-00-00"; } else { $invt_date = date('Y-m-d', strtotime($invt_date)); }
					if (empty($pull_date)){ $pull_date = "0000-00-00"; } else { $pull_date = date('Y-m-d', strtotime($pull_date)); }
					if (empty($si_date)){ $si_date = "0000-00-00"; } else { $si_date = date('Y-m-d', strtotime($si_date)); }
					if (empty($due_date)){ $due_date = "0000-00-00"; } else { $due_date = date('Y-m-d', strtotime( $due_date)); }

					if ($type == 'YAMAHA' || $type == 'HONDA' || $type == 'KAWASAKI' || $type == 'SUZUKI') {
						if (check_serial_already_exist($eng_no)) {
					        $sql = "SELECT eng_no FROM ".TB_PREF."rr_transword WHERE eng_no = ".db_escape($eng_no);
					        $result = db_query($sql, "Could not search BOM");
					        $row = db_fetch_row($result);
					        $rr++;	
					        display_error("Line $lines: The Item Could Not Be Added Serial Number: $eng_no is Already Exist");	

				    	} else if (check_chasis_already_exist($frame_no)) {
				    		$sql = "SELECT frame_no FROM ".TB_PREF."rr_transword WHERE frame_no = ".db_escape($frame_no);
					        $result = db_query($sql, "Could not search BOM");
					        $row = db_fetch_row($result);
					        $rr++;	
					        display_error("Line $lines: The Item Could Not Be Added Chasis Number: $frame_no is Already Exist");	

				    	} else	{		
							$sql = "INSERT INTO ".TB_PREF."rr_transword (rrbrand_type, si_no, code, outlatename, salesname, eng_no, frame_no, po_no, invt_date, pull_date, si_date, due_date, loc_code) VALUES ('$type', '$si_no', '$code', '$outlatename', '$salesname', '$eng_no', '$frame_no', '$po_no', '$invt_date', '$pull_date', '$si_date', '$due_date', '{$_POST['location']}')";

							db_query($sql, "The item could not be added");
							$rr++;	
					   	    display_notification("Line $lines: The Item is Added Serial Number: $eng_no AND Chasis Number: $frame_no");
					   	}
					}		
				}			
			@fclose($fp);
			if ($rr > 0) display_notification("$rr : Item $type is Added.");
		} else display_error("No CSV file selected");
	}

	if ($action == 'import') echo 'IMPORT';
	else hyperlink_params($_SERVER['PHP_SELF'], _("Import"), "action=import", false);
	echo "<br><br>";

	if ($action == 'import') {
		start_form(true);

		start_table(TABLESTYLE2, "width=45%");

		if (!isset($_POST['sep']))
		$_POST['sep'] = ",";

		table_section_title("Import to Transworld");
		text_row("Field separator:", 'sep', $_POST['sep'], 2, 1);
		locations_list_row("To Location:", 'location', null);
		//item_brand_forimport_row("Brand Type:", 'TYPE', null);
		label_row("CSV Import File:", "<input type='file' id='impCSV' name='impCSV'>");

		end_table(1);
		submit_center('import', "Import CSV File");
		end_form();
		end_page();
	}
?>

