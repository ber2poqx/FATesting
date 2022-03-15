<?php

/**
 * Created By: spyrax10
 * Date Created: 12 Mar 2022
 * Title: Item Upload
 */

$page_security = 'SA_ITEM_UPLOAD';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/admin/db/attachments_db.inc");

include_once($path_to_root . "/inventory/includes/inventory_db.inc");

//add_access_extensions();

if (isset($_POST['download'])) {
	$row = get_attachment_by_type(32);
	$dir = company_path()."/attachments";

	if ($row['filename'] == "") {
		display_error(_("No Template File Uploaded for Item Master List!"));
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
		exit();
	}

	unset($_POST['download']);
}

$action = 'import';
if (isset($_GET['action'])) {
    $action = $_GET['action'];
}

if (isset($_POST['action'])) {
    $action = $_POST['action'];
}

page(_("Import Item Master"));

//simple_page_mode(true);

//-----------------------------------------------------------------------------------------------

function can_import() {

    if (isset($_FILES['impCSVS']) && $_FILES['impCSVS']['name'] == '') {
        display_error(_("Please select a file to import."));
        return false;
    }

    if (get_post('category_id') == '') {
        display_error(_("Please select a category!"));
        set_focus('category_id');
        return false;
    }

    return true;
}

//-----------------------------------------------------------------------------------------------

if (isset($_POST['import_btn']) && can_import()) {

    if (isset($_SESSION['import_btn'])) {
        unset($_SESSION['import_btn']);
    }

    $filename = $_FILES['impCSVS']['tmp_name'];
	$sep = $_POST['sep'];

	$fp = @fopen($filename, "r"); 
	
	if (!$fp) {
		die(_("Unable to open file $filename"));
	}

	$err_arr = array();
	$line_cnt = 0;

    $lines = $CI = 0;

    while ($data = fgetcsv($fp, 4096, $sep)) {

        if ($lines++ == 0) continue;

        list($stock_id, $description, $serialized, $brand, $made_in, $zero_cost) = $data;

        if ($stock_id == ''){
            $line_cnt++;
			$err_arr[$line_cnt] = _("Item Code is empty!"); 
        }
        else if ($description == '') {
            $line_cnt++;
			$err_arr[$line_cnt] = _("Item Description is empty!"); 
        }
        else if ($brand == '') {
            $line_cnt++;
			$err_arr[$line_cnt] = _("Item Brand is empty!"); 
        }
        else if ($made_in == '') {
            $line_cnt++;
			$err_arr[$line_cnt] = _("Made-In is empty!"); 
        }
        else if (check_stock_id_exist($stock_id)) {
			$line_cnt++;
			$err_arr[$line_cnt] = _("Item Code Already exist! " . "(".$stock_id.")");
		}
        else if (!check_item_brand($brand)) {
            $line_cnt++;
			$err_arr[$line_cnt] = _("Item Brand does not exist!");
        }
        else if (!sup_code_exist($made_in)) {
            $line_cnt++;
			$err_arr[$line_cnt] = _("Supplier Code does not exist!");
        }
        else {

            $row_brand = get_brand_list($brand, false);

            $serialized = $serialized == "YES" ? 1 : 0; 
            $zero_cost = $zero_cost == "YES" ? 1 : 0;
            $brand_id = $row_brand['id'];

            add_item(
                $stock_id, 
                $description,
				'', //Long Description 
                $_POST['category_id'], 
                $_POST['tax_type_id'],
				get_post('units'),
                $brand_id, 
                get_supplier_id_code($made_in), //Supplier
                0, //Sub-Category
                0, //Classification
                $serialized, 
                1, //col: inactive 
                get_post('mb_flag'), 
                $_POST['sales_account'],
				$_POST['installment_sales_account'], 
                $_POST['regular_sales_account'],
				$_POST['inventory_account'], 
                $_POST['cogs_account'],
				$_POST['adjustment_account'], 
                $_POST['wip_account'], 
				0, 0, //dimension_id
				0, //Exclude from Sales
                1, 
                0, //Exclude from purchases
				'', //depreciation_method 
                0, //depreciation_rate 
                0, //depreciation_factor 
                null, //depreciation_start
			    '', //fa_class_id 
                null, //size 
                null, //capacity 
				$zero_cost
            );

            $CI++; $line_cnt++;
        }
        
    } //End of while loop
    

    if (count($err_arr) > 0) {
		display_error(_(count($err_arr) . " item/s unsuccessfully uploaded!"));

		foreach ($err_arr as $key => $val) {
			display_error("Line " . $key . ": " . $val);
		}
	}

    if ($CI > 0) {
        display_error(_($CI . " Item/s Imported Successfully!"));
    }
    else {
		display_error(_("No Item has been imported!"));
	}

    @fclose($fp);

    unset($_POST['import_btn']);
	unset($_POST['impCSVS']);
}

//-----------------------------------------------------------------------------------------------
if (get_post('category_id')) {
    $Ajax->activate('_page_body');
}
else {
    $Ajax->activate('_page_body');
}

//-----------------------------------------------------------------------------------------------

if ($action == 'import') {

    if (isset($_POST['impCSVS'])) {
		unset($_POST['impCSVS']);
	}

    start_form(true);

    start_outer_table(TABLESTYLE, "width='95%'", 10);

	submit_center('download', _("Download CSV Template File for Item Master List"));
	br();

    start_table(TABLESTYLE2, "width=45%");

    table_section_title(_("Guide List"));
    sql_type_list(_("Guide for Item Brand's List:"), 'brand_id', 
        get_brand_list(), 'id', 'name', 
		'label', null, false, _("Item Brand List"), false, true
	);
    supplier_list_row(_("Guide for Supplier's List:"), 'supplier_id', null, true, true, false, true);

    end_table();

    start_table(TABLESTYLE2, "width=45%");

    table_section_title(_("Select Category"));
    sql_type_list(_("Category:"), 'category_id', 
        get_category_list(), 'category_id', 'description', 
		'label', null, true, _('Select Category'), false, true
	);

    end_table();

    start_table(TABLESTYLE2, "width=45%");

    $category_record = get_item_category($_POST['category_id']);

	//$_POST['inventory_account'] = $category_record["dflt_inventory_act"];
    hidden('inventory_account', $category_record["dflt_inventory_act"]);

	//$_POST['cogs_account'] = $category_record["dflt_cogs_act"];
    hidden('cogs_account', $category_record["dflt_cogs_act"]);

	//$_POST['sales_account'] = $category_record["dflt_sales_act"];
    hidden('sales_account', $category_record["dflt_sales_act"]);

	//$_POST['installment_sales_account'] = $category_record["dflt_installment_sales_act"];
    hidden('installment_sales_account', $category_record["dflt_installment_sales_act"]);

	//$_POST['regular_sales_account'] = $category_record["dflt_regular_sales_act"];
    hidden('regular_sales_account', $category_record["dflt_regular_sales_act"]);
	
    //$_POST['adjustment_account'] = $category_record["dflt_adjustment_act"];
    hidden('adjustment_account', $category_record["dflt_adjustment_act"]);
	
    //$_POST['wip_account'] = $category_record["dflt_wip_act"];
    hidden('wip_account', $category_record["dflt_wip_act"]);

	//$_POST['dimension_id'] = $category_record["dflt_dim1"];
    hidden('dimension_id', $category_record["dflt_dim1"]);

	//$_POST['dimension2_id'] = $category_record["dflt_dim2"];
    hidden('dimension2_id', $category_record["dflt_dim2"]);

	//$_POST['no_sale'] = $category_record["dflt_no_sale"];
    hidden('no_sale', $category_record["dflt_no_sale"]);

	//$_POST['no_purchase'] = $category_record["dflt_no_purchase"];
    hidden('no_purchase', $category_record["dflt_no_purchase"]);
    
    //$_POST['tax_type_id'] = $category_record["dflt_tax_type"];
    hidden('tax_type_id', $category_record["dflt_tax_type"]);

	//$_POST['units'] = $category_record["dflt_units"];
    hidden('units', $category_record["dflt_units"]);

	//$_POST['mb_flag'] = $category_record["dflt_mb_flag"];
    hidden('mb_flag', $category_record["dflt_mb_flag"]);
	
    // table_section_title(_("GL Account Setup"));
    // gl_all_accounts_list_row(_("Cash Sales Account:"), 'sales_account', $_POST['sales_account']);
	// gl_all_accounts_list_row(_("Installment Sales Account:"), 'installment_sales_account', $_POST['installment_sales_account']);
	// gl_all_accounts_list_row(_("Regular Credit Sales Account:"), 'regular_sales_account', $_POST['regular_sales_account']);

	// if (get_post('fixed_asset')) {
	// 	gl_all_accounts_list_row(_("Asset account:"), 'inventory_account', $_POST['inventory_account']);
	// 	gl_all_accounts_list_row(_("Depreciation cost account:"), 'cogs_account', $_POST['cogs_account']);
	// 	gl_all_accounts_list_row(_("Depreciation/Disposal account:"), 'adjustment_account', $_POST['adjustment_account']);
	// }
	// else if (!is_service(get_post('mb_flag'))) {
	// 	gl_all_accounts_list_row(_("Inventory Account:"), 'inventory_account', $_POST['inventory_account']);
	// 	gl_all_accounts_list_row(_("C.O.G.S. Account:"), 'cogs_account', $_POST['cogs_account']);
	// 	gl_all_accounts_list_row(_("Inventory Adjustments Account:"), 'adjustment_account', $_POST['adjustment_account']);
	// }
	// else {
	// 	gl_all_accounts_list_row(_("C.O.G.S. Account:"), 'cogs_account', $_POST['cogs_account']);
	// 	hidden('inventory_account', $_POST['inventory_account']);
	// 	hidden('adjustment_account', $_POST['adjustment_account']);
	// }

	// if (is_manufactured(get_post('mb_flag'))) {
    //     gl_all_accounts_list_row(_("WIP Account:"), 'wip_account', $_POST['wip_account']);
    // }
		
	// else {
    //     hidden('wip_account', $_POST['wip_account']);
    // }
		
    // end_table();

    start_table(TABLESTYLE2, "width=45%");

    if (!isset($_POST['sep'])) {
	    $_POST['sep'] = ",";
    }

    table_section_title(_("Import CSV File Here"));
    text_row("Field separator:", 'sep', $_POST['sep'], 2, 1);
    label_row("CSV Import File:", "<input type='file' id='impCSVS' name='impCSVS'>");

    end_table();

    end_outer_table(1, false);

    submit_center('import_btn', _("Import Item Master List"));
    end_form();
	end_page();
}