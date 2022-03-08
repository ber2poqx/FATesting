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

page(_($help_context = "Upload CSV File"), false, false, "", $js);

//----------------------------------------------------------------------------------------
function can_import() {

    if (isset($_FILES['impCSVS']) && $_FILES['impCSVS']['name'] == '') {
        display_error(_("Please select a file to import."));
        unset($_POST['impCSVS']);
        return false;
    }

    if ($_FILES['impCSVS']['type'] != "application/vnd.ms-excel") {
        display_error(_("Only CSV files can be imported."));
        unset($_POST['impCSVS']);
        return false;
    }

    if ($_FILES['impCSVS']['size'] > 80) {
        display_error(_("The file size is too large. Please select a smaller file."));
        unset($_POST['impCSVS']);
        return false;
    }

    return true;
}

function delete_attach() {
    
    if (import_file_exists($_POST['trans_type'])) {

        $row = get_attachment_by_type($_POST['trans_type']);
        $dir = company_path()."/attachments";
	    
        if (file_exists($dir."/".$row['unique_name'])) {
            unlink($dir."/".$row['unique_name']);
        }
	    delete_attachment($row['id']);	
    }
}
//----------------------------------------------------------------------------------------

if (isset($_POST['import']) && can_import()) {

    $tmpname = $_FILES['impCSVS']['tmp_name'];

    $fp = @fopen($filename, "r"); 

    $dir =  company_path()."/attachments";

    delete_attach();

    if (!file_exists($dir)) {
        mkdir ($dir,0777);
		$index_file = "<?php\nheader(\"Location: ../index.php\");\n";
		$fp = fopen($dir."/index.php", "w");
		fwrite($fp, $index_file);
		fclose($fp);
    }

    $filename = basename($_FILES['impCSVS']['name']);
	$filesize = $_FILES['impCSVS']['size'];
	$filetype = $_FILES['impCSVS']['type'];

    $unique_name = random_id();

    move_uploaded_file($tmpname, $dir."/".$unique_name);

    add_attachment($_POST['trans_type'], max_attach_no() + 1, _("For Importing"),
		$filename, $unique_name, $filesize, $filetype
    );

    @fclose($fp);
    unset($_POST['impCSVS']);
    
    display_notification_centered(_("CSV File Successfully Uploaded!")); 
}

//----------------------------------------------------------------------------------------

if ($action == 'import') {
    start_form(true);

    start_outer_table(TABLESTYLE, "width='95%'", 10);

    display_heading(_("Import CSV File Here"));
    br();

    start_table(TABLESTYLE2, "width=45%");

    value_type_list(_("CSV Import File For:"), 'trans_type', 
        array(
            ST_INVADJUST => 'Inventory Opening', 
            ST_SALESINVOICE => 'Sales Invoice Opening',
            30 => 'Price List'
        ), 'label', null, true, '', true
    );

    label_row("Select CSV File:", "<input type='file' id='impCSVS' name='impCSVS'>");

    end_table(1);

    submit_center('import', _("Add New File"));

    end_outer_table(1, false);

    end_form();
    end_page();
}
