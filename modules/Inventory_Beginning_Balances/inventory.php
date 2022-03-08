<?php
/**
 * Author: spyrax10
 * Name: Inventory_Opening_Balances
 */

$page_security = 'SA_INVTYOPEN';
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

simple_page_mode(true);

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
    else {
        
        if (!check_reference($_POST['ref'], ST_INVADJUST)) {
            set_focus('ref');
		    return false;
    	}

	    if (!is_date($_POST['AdjDate'])) {
		    display_error(_("The entered date for the adjustment is invalid."));
		    set_focus('AdjDate');
		    return false;
	    } 
	    elseif (!is_date_in_fiscalyear($_POST['AdjDate'])) {
            display_error(_("The Entered Date is OUT of FISCAL YEAR or is CLOSED for further data entry!"));
			set_focus('AdjDate');
		    return false;
	    }
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
	$_SESSION['adj_items']->tran_date = $_POST['AdjDate'];	
}

//-----------------------------------------------------------------------------------------------

if (isset($_POST['import']) && can_import()) {

    handle_new_order();

    $item_arr = array();

    $filename = $_FILES['impCSVS']['tmp_name'];
	$sep = $_POST['sep'];

	$fp = @fopen($filename, "r"); 
	
	if (!$fp) {
		die(_("Unable to open file $filename"));
	}

    $lines = $CI = $error = 0;
    $adj = &$_SESSION['adj_items'];

	while ($data = fgetcsv($fp, 4096, $sep)) {
		
		if ($lines++ == 0) continue;

		list($stock_id, $color, $lot_no, $chassis_no, $qty, $std_cost, $mcode) = $data;

		if ($stock_id == "") {
			display_error(_("Line $CI: Stock ID is empty!"));
		}
		else if (get_stock_catID($stock_id) != get_post('category')) {
			display_error(_("Line $CI: Invalid stock item based on given category!"));
		}
		else if (!check_stock_id_exist($stock_id)) {
			display_error(_("Line $CI: Stock ID does not exist!"));
		}
		else if ($qty == "") {
			display_error(_("Line $CI & Column $lines: Invalid Quantity!"));
		}
		else if ($std_cost == "") {
			display_error(_("Line $CI & Column $lines: Invalid Standard Cost!"));
		}
		else if ($std_cost == 0 && get_stock_catID($stock_id) != 17) {
			display_error(_("Line $CI & Column $lines: Only PROMO ITEMS are allowed to have zero cost!"));
		}
		else if (is_Serialized($stock_id) == 1 && $lot_no == "") {
			display_error(_("Line $CI & Column $lines: Serial No cannot be empty for this item!"));
		}
		else if (get_stock_catID($stock_id) == 14 && $chassis_no == "") {
			display_error(_("Line $CI & Column $lines: Chassis No cannot be empty for this item!"));
		}
		else if (is_Serialized($stock_id) == 1 && serial_exist($lot_no, $chassis_no)) {
			display_error("Line $CI & Column $lines: Serial / Chassis # Already Exists!");
		}
		else if (is_Serialized($stock_id) == 1 && serial_exist_adj($lot_no, $chassis_no)) {
			display_error("Line $CI & Column $lines: Serial / Chassis # Already Pending in Inventory Adjustment!");
		}
		else if (get_stock_catID($stock_id) == 14 && $color == "") {
			display_error(_("Line $CI & Column $lines: Color Description cannot be empty for this item!"));
		}
		else if (get_stock_catID($stock_id) == 14 && !check_color_exist($stock_id, $color, true, true)) {
			display_error(_("Line $CI & Column $lines: Color Code does not exist!"));
		}
		else if ($mcode == "") {
			display_error(_("Line $CI & Column $lines: Invalid Master Code!"));
		}
		else if (get_masterfile($mcode) == "") {
			display_error(_("Line $CI & Column $lines: Unknown Masterfile!"));
		}
		else {

			$masterfile = get_masterfile($mcode);

            add_to_order(
                $_SESSION['adj_items'], 
		        $stock_id,
		        is_Serialized($stock_id) == 1 ? 1 : $qty, 
		        $std_cost, 
		        "0000-00-00", //Manufacture Date, 
		        "0000-00-00", //Expire Date, 
		        $lot_no,
		        $chassis_no,  
		        $color,
				'', 
				$mcode, $masterfile
            );

			$CI++;	
		}
	} //end of while loop

    if ($CI > 0 && count($adj->line_items) > 0 && isset($_SESSION['adj_items'])) {

		$trans_no = add_stock_adjustment(1, 1, 
			$_SESSION['adj_items']->line_items,
			$_POST['StockLocation'], 
			get_post('AdjDate'), 
			$_POST['ref'], 
			$_POST['memo_'], 
			'', '',
			getCompDet('open_inty')
		);

		new_doc_date($_POST['AdjDate']);
		$_SESSION['adj_items']->clear_items();
		unset($_SESSION['adj_items']);

        if ($trans_no) {
            meta_forward($_SERVER['PHP_SELF'], "AddedID=$trans_no");
        }
	}
	else {
		display_error(_("No Item has been imported!"));
	}
	
	@fclose($fp);

	if ($CI > 0) {
		display_notification("$CI :Inventory Opening Added.");
	} 			
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

    start_form(true);

    display_order_header($_SESSION['adj_items'], 0);

    start_outer_table(TABLESTYLE, "width='95%'", 10);

    display_heading(_("Import CSV File Here"));
    br();

    start_table(TABLESTYLE2, "width=45%");

    if (!isset($_POST['sep'])) {
	    $_POST['sep'] = ",";
    }

	submit_center('download', _("Download Inventory Opening CSV Template File"));
	br();

	sl_list_gl_row(_("Guide for Masterfile: "), 'mcode', null, _("Masterfile List"), false);
    text_row("Field separator:", 'sep', $_POST['sep'], 2, 1);
    label_row("CSV Import File:", "<input type='file' id='impCSVS' name='impCSVS'>");

    end_table(1);

    adjustment_options_controls();

    end_outer_table(1, false);

    submit_center('import', _("Process Inventory Opening"));
    end_form();
	end_page();
}