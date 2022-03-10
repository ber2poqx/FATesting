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

simple_page_mode(true);

//-----------------------------------------------------------------------------------------------
function can_import() {
	
	if (isset($_FILES['impCSVS']) && $_FILES['impCSVS']['name'] == '') {
        display_error(_("Please select a file to import."));
        return false;
    }
	
	return true;
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
	$err_cnt = 0;

	while ($data = fgetcsv($fp, 4096, $sep)) {

		if ($lines++ == 0) continue;

		list($stock_id, $color_code, $color, $color_desc, $pnp_color) = $data;

		if ($stock_id == "") {
			$err_cnt++;
			$err_arr[$err_cnt] = _("Stock ID is empty!"); 
		}
		else if ($color_code == "") {
			$err_cnt++;
			$err_arr[$err_cnt] = _("Item Color Code is empty!"); 
		}
		else if ($color == "") {
			$err_cnt++;
			$err_arr[$err_cnt] = _("Color is empty!"); 
		}
		else if ($pnp_color == "") {
			$err_cnt++;
			$err_arr[$err_cnt] = _("PNP Color is empty!"); 
		}
		else if (!check_stock_id_exist($stock_id)) {
			$err_cnt++;
			$err_arr[$err_cnt] = _("Stock ID does not exist!");
		}
		else if (item_color_code_exist($stock_id)) {
			$err_cnt++;
			$err_arr[$err_cnt] = _("Item Color Code Already Exists for this item!");
		}
		else {
			add_item_code($color_code, $color, $stock_id, $color_desc, 
				$pnp_color, get_stock_catID($stock_id), 1, 1
			);
		}

		$CI++;

	} //end of while loop

	@fclose($fp);

	if (count($err_arr) > 0) {
		display_error(_(count($err_arr) . " item/s unsuccessfully uploaded!"));

		foreach ($err_arr as $key => $val) {
			display_error("Line " . $key . ": " . $val);
		}
	}

	if ($CI == 0) {
		display_error(_("No Item Color Code has been imported!"));
	}
	else {
		display_notification(_("$CI Item Color Code(s) Imported Successfully!"));
	}

	unset($_POST['import_btn']);
	unset($_POST['impCSVS']);
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

    display_heading(_("Import CSV File Here"));
    br();

    start_table(TABLESTYLE2, "width=45%");

    if (!isset($_POST['sep'])) {
	    $_POST['sep'] = ",";
    }

    text_row("Field separator:", 'sep', $_POST['sep'], 2, 1);
    label_row("CSV Import File:", "<input type='file' id='impCSVS' name='impCSVS'>");

    end_table(1);

    end_outer_table(1, false);

    submit_center('import_btn', _("Import Item Color Code"));
    end_form();
	end_page();
}