<?php

/**
 * Author: spyrax10
 * Name: Item Color Code Upload
 * Date Created: 10 Mar 2022
 */

$page_security = 'SA_FORITEMCODE';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include($path_to_root . "/includes/db_pager.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

add_access_extensions();

if (isset($_POST['download'])) {
	$row = get_attachment_by_type(33);
	$dir = company_path()."/attachments";

	if ($row['filename'] == "") {
		display_error(_("No Template File Uploaded for Item Color Code!"));
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

$js = '';

if (user_use_date_picker())  {
	$js .= get_js_date_picker();
}

$action = 'import';
if (isset($_GET['action'])) {
    $action = $_GET['action'];
}

if (isset($_POST['action'])) {
    $action = $_POST['action'];
}

page(_("Import Item Color Code"), false, false, "", $js);

//simple_page_mode(true);

if (isset($_GET['Rows_Uploaded'])) {

	$total_rows = $_GET['Rows_Uploaded'];
	display_notitfication(_("Rows Uploaded: ") . $total_rows);
}

//-----------------------------------------------------------------------------------------------
function can_import() {
	
	if (isset($_FILES['impCSVS']) && $_FILES['impCSVS']['name'] == '') {
        display_error(_("Please select a file to import."));
        return false;
    }
	else if (!strpos($_FILES['impCSVS']['name'], ".csv") !== false) {
        display_error(_("Only CSV files can be used to upload."));
        unset($_POST['impCSVS']);
        return false;
    }
	
	return true;
}
//-----------------------------------------------------------------------------------------------
function clear_session() {
	global $Ajax;

	$_FILES['impCSVS']['name'] = "";
	
	if (isset($_POST['impCSVS'])) {
		unset($_POST['impCSVS']);
	}

	if (isset($_POST['import_btn'])) {
		unset($_POST['import_btn']);
	}

	unset($_FILES['impCSVS']['name']);
}
//-----------------------------------------------------------------------------------------------

if (isset($_POST['import_btn']) && can_import()) {

	$filename = $_FILES['impCSVS']['tmp_name'];
	$sep = $_POST['sep'];

	$fp = @fopen($filename, "r"); 
	
	if (!$fp) {
		die(_("Unable to open file $filename"));
	}

	$lines = $CI = 0;
	$err_arr = array();
	$line_cnt = $status_id = $manu_id = $brand_id = 0;

	while ($data = fgetcsv($fp, 4096, $sep)) {

		if ($lines++ == 0) continue;

		list($stock_id, $color, $pnp_color, $old_code, $color_desc, $brand_name, $manufacturer, $status) = $data;

		if ($stock_id == "") {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Stock ID is empty!"); 
		}
		else if ($color == "") {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Color is empty!"); 
		}
		else if ($pnp_color == "") {
			$line_cnt++;
			$err_arr[$line_cnt] = _("PNP Color is empty!"); 
		}
		else if ($color_desc == "") {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Color Description is empty!"); 
		}
		else if (!check_stock_id_exist(trim($stock_id), user_company())) {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Stock ID does not exist! " . "(" . $stock_id . ")");
		}
		else if (check_color_exist(trim($stock_id), trim($stock_id) . "-" . $color, true, true, user_company())) {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Item Color Code Already Exists for this Item! " . "(" . $stock_id . "-" . $color . ")");
		}
		//For parent Item Code
		// else if (!check_color_exist(trim($stock_id), trim($stock_id), true, true, user_company())) {
		// 	$line_cnt++;
		// 	$err_arr[$line_cnt] = _("Parent Item Code Already Existed for this Item! ". "(" . $stock_id .")");
		// }
		else {
			
			$item_code = trim($stock_id) . "-" . $color;
			$manu_id = !manufacturer_exists($manufacturer) || $manufacturer == "" ? 0 : manufacturer_exists($manufacturer, true);
			$brand_id = !brand_exists($brand_name) || $brand_name == "" ? 0 : brand_exists($brand_name, true);
			$importer_id = get_importer_id(trim($stock_id), user_company());

			if ($status == "") {
				$status_id = 0;
			}
			else {
				if ($status == "PHASE-OUT") {
					$status_id = 1;
				}
				else {
					$status_id = 0;
				}
			}

			if (item_code_has_parent(trim($stock_id), user_company())) {
				add_item_code(
					$item_code, $color, trim($stock_id), $color_desc, $pnp_color, 
					get_stock_catID(trim($stock_id), user_company()), 
					1, 1,
					$brand_id,
					$manu_id, 
					0, $importer_id, $status_id,
					$old_code,
					user_company()
				);
			}
			else {
				add_item_code(
					trim($stock_id), null, trim($stock_id), 
					get_item_description(trim($stock_id), user_company()), null, 
					get_stock_catID(trim($stock_id), user_company()), 
					1, 0,
					$brand_id,
					$manu_id, 
					0, $importer_id, $status_id, null,
					user_company()
				);

				add_item_code(
					$item_code, $color, trim($stock_id), $color_desc, $pnp_color, 
					get_stock_catID(trim($stock_id), user_company()), 
					1, 1,
					$brand_id,
					$manu_id, 
					0, $importer_id, $status_id,
					$old_code,
					user_company()
				);
			}

			$CI++; $line_cnt++;
		}

	} //end of while loop

	if (count($err_arr) > 0) {
		display_error(_(count($err_arr) . " item/s unsuccessfully uploaded!"));

		foreach ($err_arr as $key => $val) {
			display_error("Line " . $key . ": " . $val);
		}
	}

	if ($CI > 0) {
		if (count($err_arr) == 0) {
			display_notification(_("$CI Item Color Code(s) Imported Successfully!"));
		}
		else {
			display_error(_("$CI Item Color Code(s) Imported Successfully!"));
		}
	}
	else {
		display_error(_("No Item has been imported!"));
	}

	@fclose($fp);
	clear_session();
}

//-----------------------------------------------------------------------------------------------

if ($action == 'import') {

	if (isset($_POST['impCSVS'])) {
		unset($_POST['impCSVS']);
	}

	start_form(true);

    start_outer_table(TABLESTYLE, "width='95%'", 10);

	submit_center('download', _("Download CSV Template File for Item Color Code"));
	br();

    start_table(TABLESTYLE2, "width=45%");

    if (!isset($_POST['sep'])) {
	    $_POST['sep'] = ",";
    }

	table_section_title(_("Import CSV File Here"));
    text_row("Field separator:", 'sep', $_POST['sep'], 2, 1);
    label_row("CSV Import File:", "<input type='file' id='impCSVS' name='impCSVS'>");

    end_table(1);

    end_outer_table(1, false);

    submit_center('import_btn', _("Import Item Color Code"));
    end_form();
	end_page();
}