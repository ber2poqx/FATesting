<?php

/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
 ***********************************************************************/
$path_to_root = "..";
$page_security = 'SA_PR_BRANCH';
include_once($path_to_root . "/purchasing/includes/pr_class.inc");
include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/purchasing/includes/db/pr_db.inc");
include_once($path_to_root . "/purchasing/includes/db/suppliers_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = '';
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

$_SESSION['page_title'] = _($help_context = "Search Purchase Request");
if (isset($_GET['pr_number'])) {
	$_POST['pr_number'] = $_GET['pr_number'];
}

if (isset($_GET['delete_pr'])) {
	$_POST['delete_pr'] = $_GET['delete_pr'];
}
page($_SESSION['page_title'], false, false, "", $js);

//-----------------------------------------------------------------------------------
// Ajax updates
//

if (isset($_POST['ProcessPO'])) {
	process_request_po();
}

if (isset($_GET['AddedID'])) {
	$reference = $_GET['AddedID'];
	$trans_type = ST_PURCHORDER;

	display_notification_centered(_("Draft Purchase Order has been processed"));
	display_note(
		viewer_link(
			_("&View this Draft Purchase Order"),
			"purchasing/view/view_po.php?trans_no=$reference&branch_coy=" . $_GET['branch_coy']
		)
	);
	hyperlink_params("$path_to_root/purchasing/pr_branch.php", _("Back to Purchase Request List"), "", true);

	display_footer_exit();
}

if (isset($_SESSION['selected_pr_branch']) && isset($_GET['PRNumber'])) {
	//--------------------------------------------------------------------------------------------------
	$branch_selected = $_SESSION['selected_pr_branch'];
	unset($_SESSION['selected_pr_branch']);

	set_global_connection($branch_selected);
	create_new_pr(ST_PURCHREQUEST, $_GET['PRNumber']);
	$_SESSION['PR']->trans_type = ST_PURCHORDER;
	pr_copy_from_cart();

	start_form();
	hidden('branch_selected', $branch_selected);
	pr_branch_edit_prtpo_summary($_SESSION['PR'], $branch_selected);
	display_heading(_("Items to Order"));
	pr_branch_display_pr_order_items();
	echo '<br>';
	submit_center_first('Update', _("Update"), '', true);
	submit_center_last('ProcessPO', _("Process Order Items"), _("Clear all GL entry fields"), 'default');	
	end_form();
	display_footer_exit();
}

function check_pr_changed()
{
	/*Now need to check that the order details are the same as they were when they were read
	into the Items array. If they've changed then someone else must have altered them */
	// Compare against COMPLETED items only !!
	// Otherwise if you try to fullfill item quantities separately will give error.
	$result = get_pr_items($_SESSION['PR']->pr_no);

	$line_no = 0;
	while ($myrow = db_fetch($result)) {
		$ln_item = $_SESSION['PR']->line_items[$line_no];
		// only compare against items that are outstanding
		$qty_outstanding = $ln_item->quantity - $ln_item->qty_ordered;
		if ($qty_outstanding > 0) {
			if (
				$ln_item->stock_id != $myrow["item_code"] ||
				$ln_item->quantity != $myrow["qty"] ||
				$ln_item->qty_ordered != $myrow["quantity_ordered"]
			) {
				return true;
			}
		}
		$line_no++;
	} /*loop through all line items of the order to ensure none have been invoiced */

	return false;
}

function can_process()
{
	//global $SysPrefs;

	if (count($_SESSION['PR']->line_items) <= 0) {
		display_error(_("There is nothing to process. Please enter valid quantities greater than zero."));
		return false;
	}

	if (!is_date($_POST['DefaultReceivedDate'])) {
		display_error(_("The entered date is invalid."));
		set_focus('DefaultReceivedDate');
		return false;
	}
	if (!is_date_in_fiscalyear($_POST['DefaultReceivedDate'])) {
		display_error(_("The entered date is out of fiscal year or is closed for further data entry."));
		set_focus('DefaultReceivedDate');
		return false;
	}

	//Added by spyrax10 5 Jul 2022
	if (!allowed_posting_date($_POST['DefaultReceivedDate'])) {
		display_error(_("The Entered Date is currently LOCKED for further data entry!"));
		set_focus('DefaultReceivedDate');
		return false;
	}

	if ($_POST['DefaultReceivedDate'] < $_SESSION['PR']->orig_order_date) {
		display_error(_("PO Date should not less than PR Date!"));
		return false;
	}
	//

	//Added by Albert 05/16/2022
	foreach ($_SESSION['PR']->line_items as $request_line) {
		if ($request_line->price == 0) {
			display_error(_("Can't Proceed Price Zero!"));
			return false;
		}
	}

	if (!check_reference($_POST['po_reference'], ST_PURCHORDER)) {
		set_focus('po_reference');
		return false;
	}

	$something_received = 0;
	foreach ($_SESSION['PR']->line_items as $request_line) {
		if ($request_line->quantity > 0) {
			$something_received = 1;
			break;
		} else if (get_purchase_price($_SESSION['PR']->supplier_id, $request_line->stock_id) == 0) {
		}
	}

	/* Added By Ronelle 10/16/2020  */
	// Check SRP is not 0
	// $company = get_company_prefs();

	// $branch_code = $company["branch_code"];
	// $no_srp = false;
	// foreach ($_SESSION['PR']->line_items as $request_line) {
	// 	if (Get_Policy_SRP($branch_code, $_SESSION['PR']->category_id, $request_line->stock_id, $_POST['supplier_id']) == 0) {
	// 		$no_srp = true;
	// 		break;
	// 	}
	// }
	/* */

	// Check whether trying to deliver more items than are recorded on the actual purchase order (+ overreceive allowance)
	// $delivery_qty_too_large = 0;
	// foreach ($_SESSION['PR']->line_items as $request_line) {
	// 	if (
	// 		$request_line->quantity + $request_line->qty_ordered >
	// 		$request_line->quantity * (1 + ($SysPrefs->over_receive_allowance() / 100))
	// 	) {
	// 		$delivery_qty_too_large = 1;
	// 		break;
	// 	}
	// }

	if ($something_received == 0) { 	/*Then dont bother proceeding cos nothing to do ! */
		display_error(_("There is nothing to process. Please enter valid quantities greater than zero."));
		return false;
	}

	return true;
}

function process_request_po()
{
	global $path_to_root, $Ajax;

	if (!can_process())
		return;

	if (check_pr_changed()) {
		display_error(_("This order has been changed or invoiced since this delivery was started to be actioned. Processing halted. To enter a delivery against this purchase order, it must be re-selected and re-read again to update the changes made by the other user."));

		hyperlink_no_params(
			"$path_to_root/purchasing/inquiry/purchase_request.php",
			_("Select a different purchase request for purchase order against")
		);

		hyperlink_params(
			"$path_to_root/purchasing/pr_order_items.php",
			_("Re-Read the updated purchase request for purchase order against"),
			"PRNumber=" . $_SESSION['PR']->po_reference
		);

		unset($_SESSION['PR']->line_items);
		unset($_SESSION['PR']);
		unset($_POST['ProcessPO']);
		$Ajax->activate('_page_body');
		display_footer_exit();
	}

	$prtpo = &$_SESSION['PR'];
	$prtpo->orig_order_date = $_POST['DefaultReceivedDate'];
	$prtpo->po_reference = $_POST['po_reference'];
	$prtpo->Location = $_POST['Location'];
	$prtpo->supp_ref = $_SESSION['PR']->pr_no;
	$prtpo->delivery_address = $_POST['DeliveryAddress'];
	$prtpo->ex_rate = input_num('_ex_rate', null);
	$prtpo->purch_type_id = $_POST['purch_type_id'];

	$prtpo->served_status = $_POST['served_status'];


	set_global_connection($_POST['branch_selected']);
	$prtpo_no = copy_pr_to_po($prtpo);

	new_doc_date($_POST['DefaultReceivedDate']);
	unset($_SESSION['PR']->line_items);
	unset($_SESSION['PR']);

	meta_forward($_SERVER['PHP_SELF'], "AddedID=$prtpo_no&branch_coy=" . $_POST['branch_selected']);
}

if (get_post('SearchRequest')) {
	$Ajax->activate('pr_tbl');
}

if (get_post('delete_pr')) {
	$pr_obj = new purch_request;
	$pr_obj->trans_type = ST_PURCHREQUEST;
	$pr_obj->pr_no = get_post('delete_pr');
	$pr_no = close_pr($pr_obj);
	if ($pr_no) {
		$Ajax->activate('pr_tbl');
	}
}

$id = find_submit('CopyToPO');
if ($id != -1) {
	global $path_to_root;
	$value = $_POST["CopyToPO$id"];
	$coy = $_POST['selected_pr_branch'];
	$_SESSION["selected_pr_branch"] = $_POST['selected_pr_branch'];
	meta_forward($_SERVER['PHP_SELF'], "PRNumber=$value&coy=$coy");
}

function pr_branch_display_pr_order_items()
{
	div_start('order_items');
	start_table(TABLESTYLE, "colspan=7 width='90%'");
	$th = array(
		_("Item Code"), _("Description"), _("Color Description - (Code)"), _("Requested"), _("QoH"), _("Undelivered PO"), _("Ordered"),
		_("Outstanding"), _("This Order"), _("Price"), _("Total")
	);

	if ($_SESSION['PR']->category_id != 14)
		array_remove($th, 2);
	table_header($th);

	$company = get_company_prefs();

	$branch_code = $company["branch_code"];

	/*show the line items on the order with the quantity being received for modification */

	$total = 0;
	$k = 0; //row colour counter
	if (count($_SESSION['PR']->line_items) > 0) {
		foreach ($_SESSION['PR']->line_items as $ln_itm) {

			alt_table_row_color($k);

			$qty_outstanding = $ln_itm->quantity - $ln_itm->qty_ordered;
			// $ln_itm->price = get_purchase_price($_SESSION['PR']->supplier_id, $ln_itm->stock_id);

			/*modified by Albert base on effectivity date 05/03/2022*/
			$last_date_updated =  Get_Previous_Policy_SRP_last_date_updated($branch_code, $_SESSION['PR']->category_id, $ln_itm->stock_id, get_post('supplier_id'));
			if(date2sql($_SESSION['PR']->orig_order_date) <  Get_Policy_SRP_Effectivity_Date($branch_code, $_SESSION['PR']->category_id, $ln_itm->stock_id, get_post('supplier_id'))){
			
				$price = Get_Previous_Policy_SRP($branch_code, $_SESSION['PR']->category_id, $ln_itm->stock_id, get_post('supplier_id'),$last_date_updated );
			}else{
				
				$price =  Get_Policy_SRP($branch_code, $_SESSION['PR']->category_id, $ln_itm->stock_id, get_post('supplier_id'));

			}
			/*End by Albert*/		
			if ($price == "")
				$price = 0;
			$ln_itm->price  = $price;

			if (!isset($_POST['Update']) && !isset($_POST['ProcessPO']) && $ln_itm->receive_qty == 0) {   //If no quantites yet input default the balance to be received
				$ln_itm->receive_qty = $qty_outstanding;
			}

			$line_total = ($ln_itm->receive_qty * $ln_itm->price);
			$total += $line_total;

			label_cell($ln_itm->stock_id);
			//if ($qty_outstanding > 0)
			//	text_cells(null, $ln_itm->stock_id . "Desc", $ln_itm->item_description." Hello", 30, 50);
			//else
			label_cell($ln_itm->item_description);
			if ($_SESSION['PR']->category_id == 14)
				color_list_cells($ln_itm->stock_id, null, $ln_itm->line_no . 'color_code', $ln_itm->color_code);

			$dec = get_qty_dec($ln_itm->stock_id);

			//Modified by spyrax10 12 Apr 2022
			$qoh = get_qoh_on_date($ln_itm->stock_id, 0, null, 'new', $_GET['coy']);
			$qoo = get_on_porder_qty($ln_itm->stock_id, $_POST['Location'], $_GET['coy']);

			qty_cell($ln_itm->quantity, false, $dec);
			qty_cell($qoh, false, 0);
			qty_cell($qoo, false, 0);
			qty_cell($ln_itm->qty_ordered, false, $dec);
			qty_cell($qty_outstanding, false, $dec);

			if ($qty_outstanding > 0)
				qty_cells(null, $ln_itm->line_no, number_format2($ln_itm->receive_qty, $dec), "align=right", null, $dec);
			else
				label_cell(number_format2($ln_itm->receive_qty, $dec), "align=right");

			amount_decimal_cell($ln_itm->price);
			amount_cell($line_total);


			//button_cell('UpdateLine', _("Update"),_('Confirm changes'), ICON_EDIT);
			//hidden('serialize_id', $ln_itm->line_no);
			//set_focus('qty');

			//echo "<td><a href='item_serial_details.php?GRNSerialise=1&grnid=".$ln_itm->line_no."'>"._("Details")."</a></td>";
			end_row();
		}
	}

	$colspan = count($th) - 2;

	$display_sub_total = price_format($total/* + input_num('freight_cost')*/);

	label_row(_("Sub-total"), $display_sub_total, "colspan=$colspan align=right", "align=right");
	$taxes = $_SESSION['PR']->get_taxes(input_num('freight_cost'), true);

	$tax_total = display_edit_tax_items($taxes, $colspan, $_SESSION['PR']->tax_included);

	$display_total = price_format(($total + input_num('freight_cost') + $tax_total));

	start_row();
	label_cells(_("Amount Total"), $display_total, "colspan=$colspan align='right'", "align='right'");
	end_row();
	end_table();
	div_end();
}

function pr_branch_edit_prtpo_summary(&$pr, $branch_selected)
{
	$_SESSION["wa_current_user"]->company = $branch_selected;
	global $Ajax, $Refs;
	global $Refs, $db_connections;
	global $def_coy;
	$branchcode = $db_connections[user_company()]["branch_code"];
	start_outer_table(TABLESTYLE2, "width='80%'");

	table_section(1);
	
	//Modify by spyrax10
	$reference_link = viewer_link($pr->reference, "purchasing/view/view_pr.php?trans_no=$pr->reference&branch_coy=$branch_selected");
	label_row(_("For Purchase Request"), $reference_link);
	//

	if (!isset($_POST['po_reference']))
		$_POST['po_reference'] = $Refs->get_next(ST_PURCHORDER, null, array('supplier' => $pr->supplier_id, 'date' => Today(), 'branchcode' => $branchcode));

	ref_row(_("PO #"), 'po_reference', '', null);
	hidden('supplier_id');
	label_row(_("Supplier"), $pr->supplier_name);
	label_row(_("Category"), get_category_name($pr->category_id));
	purch_types_list_row(
		_("Purchase Type:"),
		'purch_type_id',
		null
	);

	table_section(2);

	label_row(_("Requested On"), $pr->orig_order_date);

	date_row(_("Order Date"), 'DefaultReceivedDate', '', true, 0, 0, 0, '', true);

	if (!isset($_POST['Location'])) {
		$_POST['Location'] = $branchcode;
		$_POST['StkLocation'] = $branchcode;
	}

	label_row(_("Deliver Into Location"), get_location_name($_POST['Location']));
	hidden('Location');
	$Ajax->activate('Location');
	$served_status = "";
	switch ($pr->served_status) {
		case 0:
			$served_status = "Normal Served";
			break;
		case 1:
			$served_status = "Overserved";
			break;
		case 2:
			$served_status = "Wrong Served";
			break;
		default:
			$served_status = "";
	}
	label_row(_("Served Status:"), $served_status);
	hidden('served_status', $pr->served_status);

	table_section(3);

	$loc_row = get_item_location(get_post('Location'));
	if ($loc_row) {
		$_POST['DeliveryAddress'] = $loc_row["delivery_address"];
		$Ajax->activate('DeliveryAddress');
		$_SESSION['PR']->DeliveryAddress = $_POST['DeliveryAddress'];
	} else { /*The default location of the user is crook */
		display_error(_("The default stock location set up for this user is not a currently defined stock location. Your system administrator needs to amend your user record."));
	}


	textarea_row(_("Deliver to:"), 'DeliveryAddress', null, 35, 4);

	if ($pr->Comments != "")
		label_row(_("Order Comments"), $pr->Comments, "class='tableheader2'", "colspan=9");

	end_outer_table(1);

	$_SESSION["wa_current_user"]->company = $def_coy;
}

//--------------------------------------------------------------------------------------------------

if (isset($_POST['Update']) || isset($_POST['ProcessPO'])) {
	/* if update quantities button is hit page has been called and ${$line->line_no} would have be
 	set from the post to the quantity to be received in this receival*/
	foreach ($_SESSION['PR']->line_items as $line) {
		if (($line->quantity - $line->qty_ordered) > 0) {
			$_POST[$line->line_no] = max($_POST[$line->line_no], 0);
			if (!check_num($line->line_no))
				$_POST[$line->line_no] = number_format2(0, get_qty_dec($line->stock_id));

			if (!isset($_POST['DefaultReceivedDate']) || $_POST['DefaultReceivedDate'] == "")
				$_POST['DefaultReceivedDate'] = new_doc_date();

			$_SESSION['PR']->line_items[$line->line_no]->receive_qty = input_num($line->line_no);
			if ($_SESSION['PR']->category_id == 14)
				$_SESSION['PR']->line_items[$line->line_no]->color_code = $_POST[$line->line_no . "color_code"];
			if (isset($_POST[$line->stock_id . "Desc"]) && strlen($_POST[$line->stock_id . "Desc"]) > 0) {
				$_SESSION['PR']->line_items[$line->line_no]->item_description = $_POST[$line->stock_id . "Desc"];
			}
			/*Added by Albert base on effectivity date 05/03/2022*/
			$last_date_updated =  Get_Previous_Policy_SRP_last_date_updated(get_post('Location'), $_SESSION['PR']->category_id, $line->stock_id, get_post('supplier_id'));
			
			if(date2sql($_POST['DefaultReceivedDate']) <  Get_Policy_SRP_Effectivity_Date(get_post('Location'), $_SESSION['PR']->category_id, $line->stock_id, get_post('supplier_id'))){
			
				$_SESSION['PR']->line_items[$line->line_no]->price = Get_Previous_Policy_SRP(get_post('Location'), $_SESSION['PR']->category_id, $line->stock_id, get_post('supplier_id'),$last_date_updated );
			}else{
				
				$_SESSION['PR']->line_items[$line->line_no]->price =  Get_Policy_SRP(get_post('Location'), $_SESSION['PR']->category_id, $line->stock_id, get_post('supplier_id'));

			}	
			/*End by Albert*/
		}
	}
	$Ajax->activate('order_items');
}

//--------------------------------------------------------------------------------------------------
start_form();
start_table(TABLESTYLE_NOBORDER);
start_row();
// ahref(_("New Purchase Request"), "pr_entry_items.php?NewRequest=Yes");
branch_company_list_row(_('Branch: '), 'selected_pr_branch', true, false, false);//modified by albert

ref_cells(_("PR#:"), 'pr_number', '', null, '', true);

//Added by spyrax10 22 Aug 2022
sql_type_list(_("Category: "), 'category', 
	get_category_list(), 'category_id', 'description', 
	'', null, true, _("All Category")
);
//

end_row();
end_table();

//---------------------------------------------------------------------------------------------
// function trans_view($trans)
// {
// 	return get_trans_view_str(ST_PURCHREQUEST, $trans["reference"]);
// }

function trans_view($trans)
{
	global $path_to_root;
	$param1 = $trans["reference"];
	$param2 = $_POST['selected_pr_branch'];
	return viewer_link($trans["reference"], "purchasing/view/view_pr.php?trans_no=$param1&branch_coy=$param2");
}

function edit_link($row)
{
	return ($row['Status'] == "Draft" ||
		$row['Status'] == "Disapproved") ? trans_editor_link(ST_PURCHREQUEST, $row["reference"]) : '';
}

function prt_link($row)
{
	return print_document_link($row['pr_no'], _("Print"), true, ST_PURCHREQUEST, ICON_PRINT);
}

function update_status_link($row)
{
	global $page_nested;

	return $page_nested ||
		$row['Status'] == "Open" ||
		$row['Status'] == "Closed" ||
		$row['Status'] == "Partially Ordered" ||
		$row['Status'] == "Expired" ? $row["Status"] :
		"Draft";
}

function order_link($row)
{
	return ($row['Status'] == "Expired" ||
		$row['Status'] == "Closed" ||
		$row['Status'] == "Canceled" ||
		$row['Status'] == "Draft" ||
		$row['Status'] == "Disapproved") ? '' : pager_link(
		_("Copy to PO"),
		"/purchasing/pr_order_items.php?PRNumber=" . $row["reference"],
		ICON_RECEIVE
	);
}

function copy_to_po($row)
{
	//Modified by spyrax10 13 Jul 2022
	if ($_SESSION["wa_current_user"]->can_access_page('SA_PURCHASEORDER')) {
		$id = $row["pr_no"];
		$value = $row["reference"];
	
		return ($row['Status'] == "Expired" ||
			$row['Status'] == "Closed" ||
			$row['Status'] == "Canceled" ||
			$row['Status'] == "Draft" ||
			$row['Status'] == "Disapproved") ? '' : copy_pr_button_cell(
			"CopyToPO$id",
			$value,
			"Copy to PO",
			ICON_RECEIVE, '', get_post('selected_pr_branch')
		);
	}
	else {
		return null;
	}
	//
	
}

function close_link($row)
{
	return ($row['Status'] == "Open" || $row['Status'] == "Partially Ordered") ?  pager_link(
		_("Close PR"),
		"/purchasing/purchase_request.php?delete_pr=" . $row["pr_no"],
		ICON_DELETE
	) : '';
}

function check_overdue($row)
{
	return $row['OverDue'] == 1;
}

function check_expired($row)
{
	return $row['Status'] == "Expired";
}

if (get_post("ClosePR")) {
	$trans_no = get_post("ClosePR");
	meta_forward($_SERVER['PHP_SELF'], "PRNumber=$trans_no");
}

if (get_post('category')) {
	$Ajax->activate('pr_tbl');
}

//---------------------------------------------------------------------------------------------
//figure out the sql required from the inputs available
set_global_connection(get_post('selected_pr_branch'));
$sql = get_sql_for_pr_search($_POST['pr_number'], 1, get_post('category'));// Modifed by Albert one means Ho database 06/16/2022

//$result = db_query($sql,"No Request were returned");

/*show a table of the Request returned by the sql */
$cols = array(
	_("Trans #") => array('name' => 'trans_no'),
	_("PR#") => array(
		'fun' => 'trans_view'
	),
	_("Status") => array('insert' => true, 'fun' => 'update_status_link'),
	'dummy' => 'skip',
	_("Category"),
	_("Supplier"),
	_("Purchase Type"),
	_("Served Status"),
	_("PR Date") => array('name' => 'pr_date', 'type' => 'date', 'ord' => 'desc'),
	array('insert' => true, 'fun' => 'copy_to_po')
);

$table = &new_db_pager('pr_tbl', $sql, $cols, null, null, 25);
$table->set_marker('check_expired', _("Marked Request have expired."));

$table->width = "80%";

display_db_pager($table);

end_form();
end_page();
