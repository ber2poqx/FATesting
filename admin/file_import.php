<?php

/**
 * Added by: spyrax10
 * Date Added: 4 Mar 2022
 */

$page_security = 'SA_CSV';
$path_to_root = "..";

include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/admin/db/attachments_db.inc");
include_once($path_to_root . "/admin/db/transactions_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(800, 500);

$action = 'import';
if (isset($_GET['action'])) {
    $action = $_GET['action'];
}

if (isset($_POST['action'])) {
    $action = $_POST['action'];
}

page(_($help_context = "Upload CSV Template File"), false, false, "", $js);

//----------------------------------------------------------------------------------------
function can_import() {

    if (!get_post('trans_type')) {
        display_error(_("Please Select Transaction Type."));
        return false;
    }

    if (isset($_FILES['impCSVS']) && $_FILES['impCSVS']['name'] == '') {
        display_error(_("Please Select a file to import."));
        unset($_POST['impCSVS']);
        return false;
    }

    if (!valid_file($_FILES['impCSVS']['name'], '.csv')) {
        display_error(_("Only CSV Files can be Imported."));
        unset($_POST['impCSVS']);
        return false;
    }

    if ($_FILES['impCSVS']['size'] > 400) {
        display_error(_("The file size is too large. Please select a smaller file. || ") . $_FILES['impCSVS']['size']);
        unset($_POST['impCSVS']);
        return false;
    }

    return true;
}

function delete_attach() {

    $dir = company_path()."/template"; 
    if (get_template_name(get_post('trans_type')) != '') {
        unlink(realpath($dir ."/". get_template_name(get_post('trans_type'))));
    }
}

//----------------------------------------------------------------------------------------

if (isset($_POST['import_btn']) && can_import()) {

    $tmpname = $_FILES['impCSVS']['tmp_name'];

    $fp = @fopen($filename, "r"); 

    $dir =  company_path()."/template";

    if (!file_exists($dir)) {
        mkdir ($dir,0777);
		$index_file = "<?php\nheader(\"Location: ../index.php\");\n";
		$fp = fopen($dir."/index.php", "w");
		fwrite($fp, $index_file);
		fclose($fp);
    }

    delete_attach();

    $filename = basename($_FILES['impCSVS']['name']);
	$filesize = $_FILES['impCSVS']['size'];
	$filetype = $_FILES['impCSVS']['type'];

    $unique_name = get_post('trans_type') . "_" . $_FILES['impCSVS']['size'];

    move_uploaded_file($tmpname, $dir ."/". $unique_name);

    @fclose($fp);
    unset($_POST['impCSVS']);
    unset($_POST['trans_type']);
    unset($_POST['import_btn']);
    
    display_notification_centered(_("CSV File Successfully Uploaded!")); 
}

//----------------------------------------------------------------------------------------

if ($action == 'import') {
    start_form(true);

    start_outer_table(TABLESTYLE, "width='95%'", 10);

    display_heading(_("Upload CSV Template File Here"));
    br();

    start_table(TABLESTYLE2, "width=45%");

    value_type_list(_("CSV Template File For:"), 'trans_type', 
        array(
            ST_INVADJUST => 'Inventory Opening', 
            ST_SALESINVOICE => 'Sales Invoice Opening',
            30 => 'Price List',
            31 => 'Customer List',
            32 => 'Item List',
            33 => 'Item Color Code'
        ), 'label', null, true, 'Select Transaction', true
    );

    label_row("Select CSV File:", "<input type='file' id='impCSVS' name='impCSVS'>");

    end_table(1);

    submit_center('import_btn', _("Add New Template File"));

    end_outer_table(1, false);

    end_form();
    end_page();
}
