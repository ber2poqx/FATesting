<?php
	/**********************************************
	Author: Albert
	Name: Import of Import Price List
	***********************************************/
	$page_security = 'SA_IMPORTCSVPRICE';
	$path_to_root="../..";

	include($path_to_root . "/includes/session.inc");
	add_access_extensions();

	include_once($path_to_root . "/includes/ui.inc");
	include_once($path_to_root . "/includes/data_checks.inc");
	include_once($path_to_root . "/admin/db/company_db.inc");
	include_once($path_to_root . "/includes/cost_and_pricing.inc");

    include_once($path_to_root . "/modules/Price_import/price_import.inc");
	include_once($path_to_root . "/inventory/includes/inventory_db.inc"); //Added by spyrax10
	
	if (isset($_POST['download'])) {
		$row = get_attachment_by_type(30);
		$dir = company_path()."/attachments";
	
		if ($row['filename'] == "") {
			display_error(_("No Template File Uploaded for Import Price list!"));
		}
		else if (!file_exists($dir."/".$row['unique_name'])) {
			display_error(_("Template File does not exists in current company's folder!"));
		}
		else {
			$type = ($row['filetype']) ? $row['filetype'] : 'application/octet-stream';	
			header("Content-type: ".$type);
			header('Content-Length: '.$row['filesize']);
			header('Content-Disposition: attachment; filename="'.$row['filename'].'"');
			echo file_get_contents(company_path()."/attachments/".$row['unique_name']);
			@fclose();
			exit();
		}
	
		unset($_POST['download']);
	}

	$action = 'import';
	if (isset($_GET['action'])) $action = $_GET['action'];
	if (isset($_POST['action'])) $action = $_POST['action'];

	page("Import Price List");

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
				    list($supplier, $types, $stock_id, $price,$date_epic) = $data;

                    $stock_id = strtoupper($stock_id);

					$types = normalize_chars($types);
					$supplier = normalize_chars($supplier);

					if( empty($date_epic)){ 
						$date_epic = "0000-00-00";

					} else {
						$date_epic = date("Y-m-d", strtotime($date_epic));
					}
					if( empty($supplier )){ 
						$supplier = null;
					}
					if( empty( $types )){ 
						display_error("Line:$lines Stock ID: $stock_id Types empty!!!");
					}
					if( empty( $stock_id )){ 
						display_error("Line:$lines Stock ID: $stock_id Stock id empty !!!");
					}
					if( empty( $price )){ 
						$price=0;
					}
					if (check_price_already_exist( $types, $stock_id, $supplier)){

						display_error("Price Already Exist!!! Line:$lines Stock ID: $stock_id is not added ");
					}

					if (!check_stock_id_exist($stock_id)){

						display_error("Line $lines: Stock Id: $stock_id is Not Exist");

					}else{
						$Selected_id = get_price_id($types) + 1;

						if( get_cash_types($types)==$types){

						add_cash_price( 
						$types = get_cash_price_types_id($types), 
						$stock_id, 
						$price,
						$date_epic);

						add_pricehistory($stock_id, $price, $Selected_id, 0, $types, 0, 0, 0, 0, 'CSHPRCPLCY', date("Y-m-d H:i:s"),$date_epic);


						}
						else if(get_lcp_price_types($types)==$types){
						add_lcp_pricing(
						$types = get_lcp_price_types_id($types),
						$stock_id,
						$price,
						$date_epic);

						add_pricehistory($stock_id, $price, $Selected_id, 0, 0, $types, 0, 0, 0, 'PRCPLCY', date("Y-m-d H:i:s"),$date_epic);
						
						}
						elseif( get_system_cost_types($types)==$types && $supplier <> null){

						$supplierdesc=get_supplier_desc($supplier);

						add_System_cost_pricing(
						$supplier = pr_get_supplier_id($supplier), 
						$types = get_system_cost_types_id($types), 
						$stock_id, 
						$supplierdesc,
						$price,
						$date_epic);

						add_pricehistory($stock_id, $price, $Selected_id, $supplier, 0, 0, $types, 0, 0, 'CSTPLCY', date("Y-m-d H:i:s"),$date_epic);


						}
						elseif( get_srp_types($types)==$types && $supplier <> null){

						add_srp_pricing(
						$supplier = pr_get_supplier_id($supplier), 
						$types = get_srp_types_id($types), 
						$stock_id, 
						$price,
						$date_epic);

						add_pricehistory($stock_id, $price, $Selected_id, $supplier, 0, 0, 0, $types, 0, 'SRPPLCY', date("Y-m-d H:i:s"),$date_epic);


												
						}elseif( get_incentive_types($types)){
							
							add_incentives_pricing(
							$types = get_incentive_types_id($types), 
							$stock_id, 
							$price);
						
						add_pricehistory($stock_id, $price, $Selected_id, 0, 0, 0, 0, 0, $types, 'SMIPLCY', date("Y-m-d H:i:s"),$date_epic);



						}else {
							if(( get_system_cost_types($types)==$types && $supplier == null) || ( get_srp_types($types)==$types && $supplier == null)){

								display_error("Line:$lines Stock ID: $stock_id Supplier is Empty");
							
							}else{
								display_error("Line:$lines Stock ID: $stock_id Import Price List is Failed");
							}
						}
					}
                    $CI++;	
                    display_notification("Line  $lines: Stock ID: $stock_id successfully imported");

                  
                    



				}			
			@fclose($fp);
			if ($CI > 0) display_notification("$CI :Price List Successfully is Added.");
		} else display_error("No CSV file selected");
	}

	if ($action == 'import') echo 'Import Price List';
	else hyperlink_params($_SERVER['PHP_SELF'], _("Import"), "action=import", false);
	echo "<br><br>";

	if ($action == 'import') {
		start_form(true);
		start_outer_table(TABLESTYLE, "width='95%'", 10);

		submit_center('download', _("Download CSV Template File for Price List"));

		start_table(TABLESTYLE2, "width=45%");

		if (!isset($_POST['sep']))
		$_POST['sep'] = ",";

		table_section_title("Import Price List");
		text_row("Field separator:", 'sep', $_POST['sep'], 2, 1);
		label_row("CSV Import File:", "<input type='file' id='impCSVS' name='impCSVS'>");

		end_table(1);
		submit_center('import', "Import CSV File");
		end_form();
		end_page();
	}
?>