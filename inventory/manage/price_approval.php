<?php
/**
 * Added by: Albert
 * Date Added: 15 Sep 2022
*/
$page_security = 'SA_PRICE_UPDATE_STATUS';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/inventory/includes/inventory_db.inc");
include_once($path_to_root . "/includes/db/manufacturing_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
    $js .= get_js_open_window(1000, 600);
if (user_use_date_picker())
    $js .= get_js_date_picker();

$page_title ="Price Approval";
page(_($help_context = $page_title), false, false, "", $js);

$reference = $_GET['Reference'];
//-----------------------------------------------------------------------------

if (isset($_POST['Approved']) ) { 

	$status = 1;
	$date = date("Y-m-d H:i:s");
	$user = $_SESSION["wa_current_user"]->user;
	price_status_update($status, $reference, get_post('Comments'), $user, $date);
	meta_forward($path_to_root . "/inventory/manage/price_history_list_upload.php?");
	
}


if (isset($_POST['Disapproved']) ) {

	$status = 2;
	price_status_update($status, $reference);
	meta_forward($path_to_root . "/inventory/manage/price_history_list_upload.php?");
    
}

//-----------------------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

$loc_details = get_list_price_upload($reference);

start_table(TABLESTYLE);

$th = array(_("Item_code"), _("Supplier"), _("Price code"), _("Amount"), _("Created Date"), _("Effectivity date"));

table_header($th);
$j = 1;
$k = 0; //row colour counter

while ($myrow = db_fetch($loc_details))
{

	alt_table_row_color($k);

		label_cell($myrow["stock_id"]);
		label_cell($myrow["supp_name"] != '' ? $myrow["supp_name"] : '' );
		label_cell($myrow["price_code"]);
		label_cell($myrow["amount"]);
		label_cell($myrow["date_defined"]);
		label_cell($myrow["date_epic"]);

		
        end_row();

	$j++;

}
// 

start_table(TABLESTYLE2);
echo "<br> <br>";

textarea_row(_("Remarks:"), 'Comments', null, 70, 4);

end_table(1);

    submit_center_first('Approved', _("Approved"), '', 'default');
    submit_center_last('Disapproved', _("Disapproved"), '', 'default', ICON_DELETE);

br(2);

end_form();
end_page();