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

$price_id = $_GET['price_id'];
$price_code = $_GET['price_code'];
$stock_id = $_GET['stock_id'];

//-----------------------------------------------------------------------------
function get_price_history($price_id, $price_code, $stock_id){
	
	$sql = "SELECT 
				case when a.plcycashprice_id = b.id then b.scash_type
					when a.plcyprice_id = c.id then c.sales_type
					when a.plcycost_id = d.id then d.cost_type
					when a.plcysrp_id = e.id then e.srp_type
					else ''end as price_code,
				a.*

			FROM ".TB_PREF."price_cost_archive a
			Left JOIN ".TB_PREF."sales_cash_type b on a.plcycashprice_id = b.id
			Left JOIN ".TB_PREF."sales_types c on a.plcyprice_id = c.id
			Left JOIN ".TB_PREF."supp_cost_types d on a.plcycost_id = d.id
			Left JOIN ".TB_PREF."item_srp_area_types e on a.plcysrp_id = e.id
			where a.is_upload = 1 and a.stock_id =".db_escape($stock_id)." And a.id =".db_escape($price_id);

	$sql.= " AND (b.scash_type like ".db_escape($price_code)."
				OR c.sales_type like ".db_escape($price_code)."
				OR d.cost_type like ".db_escape($price_code)." 
				OR e.srp_type like ".db_escape($price_code).")";

	$sql.= " order by a.date_defined desc, a.id desc";

	return db_query($sql,"The Price History could not be retreived");

}


//-----------------------------------------------------------------------------

if (isset($_POST['Approved']) ) { 

	$status = 1;

	price_status_update($status, $price_id);
	meta_forward($path_to_root . "/inventory/manage/price_history_list.php?");
	
    
}


if (isset($_POST['Disapproved']) ) {

	$status = 2;
	price_status_update($status, $price_id);
	meta_forward($path_to_root . "/inventory/manage/price_history_list.php?");
    
}

//-----------------------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

$loc_details = get_price_history($price_id, $price_code ,$stock_id);

start_table(TABLESTYLE);

$th = array(_("Item_code"), _("Price code"), _("Amount"), _("Created Date"), _("Effectivity date"));

table_header($th);
$j = 1;
$k = 0; //row colour counter

while ($myrow = db_fetch($loc_details))
{

	alt_table_row_color($k);

		label_cell($myrow["stock_id"]);
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

// textarea_row(_("Remarks:"), 'Comments', null, 70, 4);

end_table(1);

    submit_center_first('Approved', _("Approved"), '', 'default');
    submit_center_last('Disapproved', _("Disapproved"), '', 'default', ICON_DELETE);

br(2);

end_form();
end_page();