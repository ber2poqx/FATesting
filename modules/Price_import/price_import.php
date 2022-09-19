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
	include_once($path_to_root . "/sales/includes/db/sales_incentive_db.inc");
	include_once($path_to_root . "/inventory/includes/inventory_db.inc"); //Added by spyrax10

	if (isset($_POST['download'])) {

		$dir = company_path()."/template";
		$file_type = "application/vnd.ms-excel";
		$file_name = get_template_name(30);
		$file_size = str_after_delimiter($file_name, "_");
	
		if (!file_exists($dir ."/". $file_name)) {
			display_error(_("Template File does not exists in current company's folder!"));
		}
		else {
			header("Content-type: ". $file_type);
			header('Content-Length: '. $file_size);
			header('Content-Disposition: attachment; filename="import_price.csv"');
			echo file_get_contents(company_path()."/template/". $file_name);
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

					if (check_price_already_exist( $types, $stock_id, $supplier)){	
                    	$add = 1;//updated
					}else{
						$add = 0;//Added
					}

					if( empty($date_epic)){ 
						$date_epic = "0000-00-00";

					} else {
						$date_epic = date("Y-m-d", strtotime($date_epic));
					}
					if( empty($supplier )){ 
						
						$supplier = null;
					}
					if( empty( $price )){ 
						$price=0;
					}
					
					if( empty( $types )){ 
						
						display_error("Line:$lines Stock ID: $stock_id Types empty!!!");
					
					} else if( empty( $stock_id )){ 
						
						display_error("Line:$lines Stock ID: $stock_id Stock id empty !!!");
	
					// } elseif (check_price_already_exist( $types, $stock_id, $supplier)){

					// 	display_error("Price Already Exist!!! Line:$lines Stock ID: $stock_id is not added ");
					
					}else if (!check_stock_id_exist($stock_id)){

						display_error("Line $lines: Stock Id: $stock_id is Not Exist");

					}else{
						$Selected_id = get_price_id($types) + 1;
						$supplierdesc = get_supplier_desc($stock_id);
						$supplier_id = pr_get_supplier_id($supplier);

						// Add new price
						if ($add == 0){
							if( get_cash_types($types)==$types){

								$cash_types = get_cash_price_types_id($types);

								// add_item_scashprice($stock_id, $cash_types,'PHP', $price, $date_epic);

								add_pricehistory($stock_id, $price, $Selected_id, 0,
								 $cash_types, 0, 0, 0, 0, 'CSHPRCPLCY', date("Y-m-d H:i:s"),$date_epic, 0, 1);


							}
							else if(get_lcp_price_types($types)==$types){

								$lcp_types = get_lcp_price_types_id($types);

								// add_item_price($stock_id, $lcp_types, 'PHP', $price, $date_epic);
	
								add_pricehistory($stock_id, $price, $Selected_id, 0, 0, $lcp_types, 0, 0,
								0, 'PRCPLCY', date("Y-m-d H:i:s"),$date_epic, 0, 1);
								
							}
							elseif( get_system_cost_types($types)==$types && $supplier <> null){
 
								$cost_types = get_system_cost_types_id($types);

								// add_item_supplrcost($supplier_id, $stock_id, $price, '', 1, $supplierdesc, $cost_types, $date_epic);


								add_pricehistory($stock_id, $price, $Selected_id, $supplier_id, 0, 0,
								$cost_types, 0, 0, 'CSTPLCY', date("Y-m-d H:i:s"),$date_epic, 0, 1);


							}
							elseif( get_srp_types($types)==$types && $supplier <> null){


								$srp_types = get_srp_types_id($types);

								// add_item_stdcost($stock_id, $srp_types, 'PHP', $price, $supplier_id, $date_epic);

								add_pricehistory($stock_id, $price, $Selected_id, $supplier_id, 0, 0,
								 0, $srp_types, 0, 'SRPPLCY', date("Y-m-d H:i:s"),$date_epic, 0, 1);


													
							}elseif( get_incentive_types($types)){

								$incentives_types = get_incentive_types_id($types);

								// add_item_incentiveprice($stock_id, $incentives_types, 'PHP', $price);
							
								add_pricehistory($stock_id, $price, $Selected_id, 0, 0, 
								0, 0, 0, $incentives_types, 'SMIPLCY', date("Y-m-d H:i:s"),$date_epic);

							}else {
								if(( get_system_cost_types($types)==$types && $supplier == null) || ( get_srp_types($types)==$types && $supplier == null)){

									display_error("Line:$lines Stock ID: $stock_id Supplier is Empty");
								
								}else{
									display_error("Line:$lines Stock ID: $stock_id Import Price List is Failed");
								}
							}



						}else{
							//Update Price
							if ($add == 1){
								$price_id = get_existing_price_id($types, $stock_id);
								if( get_cash_types($types)==$types){

									$cash_types = get_cash_price_types_id($types);

									// update_item_scashprice(
									// $price_id,
									// $cash_types, 
									// 'PHP', 
									// $price,
									// $date_epic);

									// update_pricehistory($stock_id, 0, $cash_types, 0, 0, 0, 0, 'CSHPRCPLCY');
									add_pricehistory($stock_id, $price, $price_id , 0, $cash_types, 0, 0, 0, 0, 'CSHPRCPLCY', date("Y-m-d H:i:s"),$date_epic, 0, 1);
									
								}else if(get_lcp_price_types($types)==$types){

									$lcp_types = get_lcp_price_types_id($types);

									// update_item_price(
									// $price_id, 
									// $lcp_types, 
									// 'PHP', 
									// $price, 
									// $date_epic);

									// update_pricehistory($stock_id, 0, 0, $lcp_types, 0, 0, 0, 'PRCPLCY');
									add_pricehistory($stock_id, $price, $price_id , 0, 0, $lcp_types, 0, 0, 0, 'PRCPLCY', date("Y-m-d H:i:s"),$date_epic, 0, 1);
									
								}
								elseif( get_system_cost_types($types)==$types && $supplier <> null){
									
									$cost_types = get_system_cost_types_id($types);

									// update_item_supplrcost(
									// $price_id,
									// $stock_id, 
									// $price,
									// '', 
									// 1,
									// $supplierdesc,
									// $cost_types,
									// $date_epic);

									// update_pricehistory($stock_id, $supplier_id  , 0, 0, $cost_types, 0, 0,  'CSTPLCY');
									add_pricehistory($stock_id, $price, $price_id , $supplier_id, 0, 0, $cost_types, 0, 0, 'CSTPLCY', date("Y-m-d H:i:s"),$date_epic, 0, 1);


								}
								elseif( get_srp_types($types)==$types && $supplier <> null){

									$srp_types = get_srp_types_id($types);

									// update_item_stdcost(
									// $price_id, 
									// $srp_types, 
									// 'PHP', 
									// $price, 
									// $supplier_id, 
									// $date_epic);

									// update_pricehistory($stock_id, $supplier_id, 0, 0, 0, $srp_types, 0, 'CSHPRCPLCY');
									add_pricehistory($stock_id, $price, $price_id , $supplier_id, 0, 0, 0, $srp_types, 0, 'SRPPLCY', date("Y-m-d H:i:s"),$date_epic, 0, 1);
													
								}elseif( get_incentive_types($types)){

									$incentives_types = get_incentive_types_id($types);

									// update_item_incentiveprice(
									// $price_id, 
									// $incentives_types, 
									// 'PHP',
									// $price);

								
									// update_pricehistory($stock_id, 0, 0, 0, 0, 0, $incentives_types, 'SMIPLCY');
									add_pricehistory($stock_id, $price, $price_id , 0, 0, 0, 0, 0, $incentives_types, 'SMIPLCY', date("Y-m-d H:i:s"),$date_epic, 0, 1);
		
								}else {
									if(( get_system_cost_types($types)==$types && $supplier == null) || ( get_srp_types($types)==$types && $supplier == null)){

										display_error("Line:$lines Stock ID: $stock_id Supplier is Empty");
									
									}else{
										display_error("Line:$lines Stock ID: $stock_id Update Price List is Failed");
									}
								}
							}else {
								display_error("Line:$lines Stock ID: $stock_id Updated Price List is Failed");
							}
						}	
					}
					
                    $CI++;
					// if($add == 1)
					// 	display_notification("Line  $lines: Stock ID: $stock_id successfully updated");
					// else
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