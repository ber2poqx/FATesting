<?php
/**
 * Author: spyrax10
 * Name: Inventory_Opening_Balances
 */

$page_security = 'SA_INVTYOPEN_ENTRY';
$path_to_root = "../..";

include_once($path_to_root . "/includes/ui/items_cart.inc");
include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/fixed_assets/includes/fixed_assets_db.inc");
include_once($path_to_root . "/modules/Inventory_Beginning_Balances/includes/item_adjustments_ui.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");
include_once($path_to_root . "/includes/sweetalert.inc");
include_once($path_to_root . "/admin/db/attachments_db.inc");

add_access_extensions();

if (isset($_POST['download'])) {
	$row = get_attachment_by_type(ST_INVADJUST);
	$dir = company_path()."/attachments";

	if ($row['filename'] == "") {
		display_error(_("No Template File Uploaded for Inventory Opening!"));
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

page(_("Inventory Opening Balances"), false, false, "", $js);

//-----------------------------------------------------------------------------------------------

if (isset($_GET['AddedID'])) {

	$trans_no = $_GET['AddedID'];
	$trans_type = ST_INVADJUST;

	$result = get_stock_adjustment_items($trans_no);
	$row = db_fetch($result);

	if (is_fixed_asset($row['mb_flag'])) {
		display_notification_centered(_("Fixed Assets disposal has been processed!"));
		display_note(get_trans_view_str($trans_type, $trans_no, _("&View this disposal")));
		display_note(get_gl_view_str($trans_type, $trans_no, _("View the GL &Postings for this Disposal")), 1, 0);
		hyperlink_params($_SERVER['PHP_SELF'], _("Enter &Another Disposal"), "NewAdjustment=1&FixedAsset=1");
	}
	else {
		display_notification_centered(_("Inventory Opening Transaction has been processed!"));
		//sweet_notification(_("Items adjustment has been processed"), false);
    	display_note(get_trans_view_str($trans_type, $trans_no, _("&View this Inventory Opening")));
    	display_note(get_gl_view_str($trans_type, $trans_no, _("View the GL &Postings for this Inventory Opening")), 1, 0);
	  	hyperlink_params($_SERVER['PHP_SELF'], _("Enter &Another Inventory Opening"), "NewAdjustment=1");
  	}

	hyperlink_params("$path_to_root/modules/Inventory_Beginning_Balances/inventory_view.php", _("Back to Inventory Opening List"), "");
	hyperlink_params("$path_to_root/admin/attachments.php", _("Add an Attachment"), "filterType=$trans_type&trans_no=$trans_no");

	display_footer_exit();
}

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
	else if (!allowed_posting_date(Today())) {
		display_error(_("The Entered Date is currently LOCKED for further data entry!"));
		set_focus('AdjDate');
		return false;
	}
	else if (!check_reference($_POST['ref'], ST_INVADJUST)) {
		set_focus('ref');
		return false;
	}

    return true;
}

function handle_new_order() {

	if (isset($_SESSION['adj_items'])) {
		$_SESSION['adj_items']->clear_items();
		unset ($_SESSION['adj_items']);
	}

    $_SESSION['adj_items'] = new items_cart(ST_INVADJUST);
    $_SESSION['adj_items']->fixed_asset = isset($_GET['FixedAsset']);
}

function clear_session() {
	global $Ajax, $Refs;

	if (isset($_POST['impCSVS'])) {
		unset($_POST['impCSVS']);
	}

	if (isset($_POST['download'])) {
		unset($_POST['download']);
	}

	if (!check_reference($_POST['ref'], ST_INVADJUST, 0, null, null, false)) {
		$_POST['ref'] = $Refs->get_next(ST_INVADJUST);
		$Ajax->activate('ref');
	}

	unset($_POST['AdjDate'], $_POST['memo_'], $_POST['StockLocation'], $_POST['category']);

	$Ajax->activate('_page_body');
}

//-----------------------------------------------------------------------------------------------

if (isset($_POST['import_btn']) && can_import()) {

    handle_new_order();

    $filename = $_FILES['impCSVS']['tmp_name'];
	$sep = $_POST['sep'];

	$fp = @fopen($filename, "r"); 
	
	if (!$fp) {
		die(_("Unable to open file $filename"));
	}

	$err_arr = array();
	$line_cnt = 0;

    $lines = $CI = 0;
    $adj = &$_SESSION['adj_items'];

	while ($data = fgetcsv($fp, 4096, $sep)) {
		
		if ($lines++ == 0) continue;

		list($ob_date, $stock_id, $color, $lot_no, $chassis_no, $qty, $std_cost, $mcode) = $data;

		$serial_count = get_qoh_on_date('', $_POST['StockLocation'], null, 'new', user_company(), $lot_no);
		$chassis_count = get_qoh_on_date('', $_POST['StockLocation'], null, 'new', user_company(), '', $chassis_no);


		if ($ob_date == "") {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Transaction Date is empty!"); 
		}
		else if (!is_date($ob_date)) {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Invalid Transaction Date Format! ($ob_date)"); 
		}
		else if ($stock_id == "") {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Stock ID is empty!"); 
		}
		else if (!check_stock_id_exist($stock_id)) {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Stock ID does not exist! (" . $stock_id . ")");
		}
		else if (get_stock_catID($stock_id) != get_post('category')) {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Invalid stock item based on given category! (" . $stock_id . ")");
		}
		else if ($qty == "") {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Invalid Quantity! (Empty Value)");
		}
		else if (!is_numeric($qty)) {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Invalid Quantity! (Invalid Format)");
		}
		else if ($std_cost == "") {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Invalid Standard Cost! (Empty Value)");
		}
		else if (contains_comma($std_cost)) {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Invalid Standard Cost! (Contains Comma)");
		}
		else if ($std_cost == 0 && get_stock_catID($stock_id) != 17) {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Only PROMO ITEMS are allowed to have zero cost! ($stock_id)");
		}
		else if (is_Serialized($stock_id) == 1 && $lot_no == "") {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Serial No cannot be empty for this item! ($stock_id)");
		}
		else if (get_stock_catID($stock_id) == 14 && $chassis_no == "") {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Chassis No cannot be empty for this item! ($stock_id)");
		}
		else if (is_Serialized($stock_id) == 1 && $serial_count > 0) {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Serial # Already Registered in the System! " . "(Serial: " . $lot_no . ")");
		}
		else if (is_Serialized($stock_id) == 1 && $chassis_count > 0) {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Chassis # Already Registered in the System! " . "(Chassis: " . $chassis_no . ")");
		}
		else if (is_Serialized($stock_id) == 1 && serial_exist_adj($lot_no, $chassis_no)) {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Serial / Chassis # Already Pending in Inventory Adjustment!" . "(Serial: " . $lot_no . " || Chassis: " . $chassis_no . ")");
		}
		else if (get_stock_catID($stock_id) == 14 && $color == "") {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Color Description cannot be empty for this item! ($stock_id)");
		}
		else if (get_stock_catID($stock_id) == 14 && !check_color_exist(trim($stock_id), trim($color), true, false)) {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Item Color Code does not exist! (" . $stock_id . " || Color: " . $color . ")");
		}
		else if ($mcode == "") {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Invalid Master Code!");
		}
		else if (get_masterfile(trim($mcode)) == "") {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Unknown Masterfile!");
		}
		else {

			$masterfile = get_masterfile(trim($mcode));
			$color_code = get_color_code(trim($stock_id), trim($color));

            add_to_order(
                $_SESSION['adj_items'], 
		        trim($stock_id),
		        is_Serialized($stock_id) == 1 ? 1 : $qty, 
		        $std_cost, 
		        "0000-00-00", //Manufacture Date, 
		        "0000-00-00", //Expire Date, 
		        trim($lot_no),
		        trim($chassis_no),  
		        is_Serialized($stock_id) == 1 ? trim($color_code) : trim($stock_id),
				'', 
				trim($mcode), $masterfile, $ob_date
            );

			$CI++; $line_cnt++;
		}
	} //end of while loop

    if ($CI > 0 && count($adj->line_items) > 0 && isset($_SESSION['adj_items'])) {

		$trans_no = add_stock_adjustment(1, 1, 
			$_SESSION['adj_items']->line_items,
			$_POST['StockLocation'], 
			null, 
			$_POST['ref'], 
			$_POST['memo_'], 
			'', '',
			getCompDet('open_inty')
		);

		//new_doc_date($_POST['AdjDate']);
		$_SESSION['adj_items']->clear_items();
		unset($_SESSION['adj_items']);

	}
	else {
		display_error(_("No Item has been imported!"));
	}
	
	if ($CI > 0 && count($err_arr) == 0) {
		meta_forward($_SERVER['PHP_SELF'], "AddedID=$trans_no");
	}
	else {

		if (count($err_arr) > 0) {
			display_error(_(count($err_arr) . " item/s unsuccessfully uploaded!"));
	
			foreach ($err_arr as $key => $val) {
				display_error("Line " . $key . ": " . $val);
			}
		}

		if ($CI > 0) {
			display_error("$CI item/s successfully uploaded!");
		}
	} 	

	@fclose($fp);
	clear_session();
}

//-----------------------------------------------------------------------------------------------

if (get_post("StockLocation")) {
	$Ajax->activate('item_head');
}

if (isset($_POST['AdjDate'])) {
	$Ajax->activate('AdjDate');
}

if (get_post('category')) {
	$Ajax->activate('item_head');
}

//-----------------------------------------------------------------------------------------------
if ($action == 'import') {

	global $Refs;

	if (isset($_POST['impCSVS'])) {
		unset($_POST['impCSVS']);
	}

    start_form(true);

	submit_center('download', _("Download CSV Template File for Inventory Opening"));

	start_table(TABLESTYLE_NOBORDER);
	start_row();
	ahref_cell(_("Back to Inventory Opening Balances Inquiry List"), "../../modules/Inventory_Beginning_Balances/inventory_view.php?");
	end_row();
	end_table();
	
    start_outer_table(TABLESTYLE, "width = '95%'", 10);

	start_table(TABLESTYLE2, "width = 30%");

	table_section_title(_("Transaction Details"));

	sql_type_list(_("Location: "), 'StockLocation', 
		get_location_list(), 'loc_code', 'location_name', 
		'label', null, true
	);

	$_POST['ref'] = $Refs->get_next(ST_INVADJUST, null, array('location'=>get_post('StockLocation'), 'date'=>get_post('AdjDate')));
	label_row(_("Reference #: &nbsp;"), get_post('ref'));
	hidden('ref');

	sql_type_list(_("Category: "), 'category', 
		get_category_list(), 'category_id', 'description', 
		'label', null, true, '', false, true
	);

	end_table(1);
	
    start_table(TABLESTYLE2, "width = 45%");

    if (!isset($_POST['sep'])) {
	    $_POST['sep'] = ",";
    }

	table_section_title(_("Import CSV File Here"));
	sl_list_gl_row(_("Guide for Masterfile: "), 'mcode', null, _("Masterfile List"), false);
    text_row("Field separator:", 'sep', $_POST['sep'], 2, 1);
    label_row("CSV Import File:", "<input type='file' id='impCSVS' name='impCSVS'>");

    end_table(1);

    adjustment_options_controls();

	submit_center('import_btn', _("Process Inventory Opening"));

    end_outer_table(1, false);

	br();

    end_form();
	end_page();
}