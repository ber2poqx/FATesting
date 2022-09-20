<?php

/**
 * Added by: Albert
 * Date Added: 15 Sep 2022
*/

$page_security = 'SA_PRICE_HISTORY_LIST';
$path_to_root = "../..";


include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

add_access_extensions();

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/includes/ui.inc");


$js = '';

if ($SysPrefs->use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}

if (user_use_date_picker()) {
	$js .= get_js_date_picker();
}

$_SESSION['page_title'] = _($help_context = "List of Price Upload");
page($_SESSION['page_title'], false, false, "", $js);

//-----------------------------------------------------------------------------------
global $Ajax;

if (get_post('SearchOrders')) {
	$Ajax->activate('price_hstry_tbl');
}

if (get_post('stock_loc')) {
	$Ajax->activate('price_hstry_tbl');
}

//---------------------------------------------------------------------------------------------

function get_price_history_list($search_val= null){

	$sql = "SELECT a.prcecost_id,
	case when a.status = 0 then 'Draft'
		when a.status = 1 then 'Approved'
		when a.status = 1 then 'Disapproved' else 'Closed' end as status,
	a.stock_id, supp.supp_name, a.date_defined, 
    b.scash_type, c.sales_type, d.cost_type, e.srp_type, a.amount, a.date_epic, a.id

			FROM ".TB_PREF."price_cost_archive a
			Left JOIN ".TB_PREF."sales_cash_type b on a.plcycashprice_id = b.id
			Left JOIN ".TB_PREF."sales_types c on a.plcyprice_id = c.id
			Left JOIN ".TB_PREF."supp_cost_types d on a.plcycost_id = d.id
			Left JOIN ".TB_PREF."item_srp_area_types e on a.plcysrp_id = e.id
			Left Join ".TB_PREF." suppliers supp on a.supplier_id = supp.supplier_id
			where a.is_upload=1";

	if($search_val <> null){
		$sql.=" And stock_id like".db_escape($search_val);
	}
	$sql.= " order by a.date_defined desc";


return $sql;

}

function update_price_status_link($row) {
	global $page_nested;

	if ($_SESSION["wa_current_user"]->can_access_page('SA_PRICE_UPDATE_STATUS')) {
		
		if($row["scash_type"] <> ''){
			$price_code = $row["scash_type"];
		}else if($row["sales_type"] <> ''){
			$price_code = $row["sales_type"];
		}else if($row["cost_type"] <> ''){
			$price_code = $row["cost_type"];
		}else if($row["srp_type"] <> ''){
			$price_code = $row["srp_type"];
		}else{
			// $price_code = $row["incentive_type"];
			$price_code='';
		}

		$status_link = 
		$row["status"] == "Draft" ? pager_link(
			$row['status'],
			"/inventory/manage/price_approval.php?price_id=" . $row["id"]."&&price_code=" .$price_code."&&stock_id=".$row["stock_id"],
			false
		) : $row["status"];
	}
	else {
		$status_link = $row["status"];
	}

	return $status_link;
}
//Added by Albert 09/15/2022
function post_price($row) {
	global $page_nested;

	if($row["scash_type"] <> ''){
		$price_code = $row["scash_type"];
	}else if($row["sales_type"] <> ''){
		$price_code = $row["sales_type"];
	}else if($row["cost_type"] <> ''){
		$price_code = $row["cost_type"];
	}else if($row["srp_type"] <> ''){
		$price_code = $row["srp_type"];
	}else{
		// $price_code = $row["incentive_type"];
		$price_code='';
	}

	if ($_SESSION["wa_current_user"]->can_access_page('SA_POSTPRICE')) {
		$price_link = $row["status"] == "Approved" ? pager_link(
			'post',
			"/inventory/manage/post_price.php?price_id=" . $row["id"]."&&price_code=" . $price_code."&&stock_id=".$row["stock_id"]."&&prcecost_id=".$row["prcecost_id"],
			ICON_DOC
		) : '';
	}
	else {
		$price_link = '';
	}

	return $price_link;
}

//---------------------------------------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);
start_row();

ref_cells(_("Stock_id:"), 'search_val', '', null, '', true);

// date_cells(_("From:"), 'from_date', '', null, -user_transaction_days());
// date_cells(_("To:"), 'to_date');

submit_cells('SearchOrders', _("Search"),'',_('Select documents'), 'default');

end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

global $Ajax;
$Ajax->activate('price_hstry_tbl');

end_row();
end_table(); 

$sql = get_price_history_list(get_post('search_val'));

$cols = array(
	_("Price Id #"),
	_("Status") => array('insert' => true, 'fun' => 'update_price_status_link'),'dummy' => 'skip',
	_("Stock Id"),
	_("Supplier"),
	_("Create Date"),
	_("LCP"),
	_("Cash"),
	_("System Cost"),
	_("SRP"),
	_("Price"), 
	_("Date Effect"),
	array('insert'=>true, 'fun'=>'post_price'), 
);


$table = &new_db_pager('price_hstry_tbl', $sql, $cols, null, null, 25);

$table->width = "75%";

display_db_pager($table);

end_form();
end_page();


