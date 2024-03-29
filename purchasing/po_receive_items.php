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
$page_security = 'SA_GRN';
$path_to_root = "..";
include_once($path_to_root . "/purchasing/includes/po_class.inc");

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/modules/serial_items/includes/modules_db.inc");

// add_js_ufile($path_to_root . "/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
// add_css_file($path_to_root . "/css/extjs-default.css");
// add_js_ufile($path_to_root . '/js/rr_serial_items_entry.js');

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
page(_($help_context = "Receive Purchase Order Items"), false, false, "", $js);

//---------------------------------------------------------------------------------------------------------------

if (isset($_GET['AddedID'])) {
	$grn = $_GET['AddedID'];
	$trans_type = ST_SUPPRECEIVE;

	display_notification_centered(_("Purchase Order Delivery has been processed"));
	if (isset($_GET['category']) && $_GET['category'] == 23) {
	} else
		display_note(viewer_link("Serial Item Entry", "modules/serial_items/serial_details.php?serialid=" . $grn), 0, 1);

	//hyperlink_no_params("$path_to_root/modules/serial_items/serial_details.php?serialid=".$grn, _("Serial Items Entry"));
	//echo "<br/>";
	display_note(get_trans_view_str($trans_type, $grn, _("&View this Delivery")));

	$clearing_act = get_company_pref('grn_clearing_act');
	if ($clearing_act)
		display_note(get_gl_view_str($trans_type, $grn, _("View the GL Journal Entries for this Delivery")), 1);

	hyperlink_params("$path_to_root/purchasing/supplier_invoice.php", _("Entry purchase &invoice for this receival"), "New=1");

	hyperlink_no_params("$path_to_root/purchasing/inquiry/po_search.php", _("Select a different &purchase order for receiving items against"));

	display_footer_exit();
}

//--------------------------------------------------------------------------------------------------

if ((!isset($_GET['PONumber']) || $_GET['PONumber'] == 0) && !isset($_SESSION['PO'])) {
	die(_("This page can only be opened if a purchase order has been selected. Please select a purchase order first."));
}

//--------------------------------------------------------------------------------------------------

function display_po_receive_items()
{
	div_start('grn_items');
	start_table(TABLESTYLE, "colspan=7 width='90%'");
	//echo $_SESSION['PO']->category_id;
	if ($_SESSION['PO']->category_id == 14) {

		if ($_SESSION['PO']->is_consign == "Consignment") {
			$th = array(
				_("Model Code"), _("Product Description"), _("Serial No."), _("Chassis No."), _("Color Description - (Code)"), _("Ordered"), _("Units"), _("Received"),
				_("Outstanding"), _("This Delivery"), _("Unit Cost"), _("Total")
			);
		} else {
			$th = array(
				_("Model Code"), _("Product Description"), _("Color Description - (Code)"), _("Ordered"), _("Units"), _("Received"),
				_("Outstanding"), _("This Delivery"), _("Unit Cost"), _("Total")
			);
		}
	} else {
		if ($_SESSION['PO']->is_consign == "Consignment") {
			$th = array(
				_("Item Code"), _("Description"), _("Serial No."), _("Ordered"), _("Units"), _("Received"),
				_("Outstanding"), _("This Delivery"), _("Unit Cost"), _("Total")
			);
		} else {
			$th = array(
				_("Item Code"), _("Description"), _("Ordered"), _("Units"), _("Received"),
				_("Outstanding"), _("This Delivery"), _("Unit Cost"), _("Total")
			);
		}
	}
	table_header($th);

	$company = get_company_prefs();

	$branch_code = $company["branch_code"];

	/*show the line items on the order with the quantity being received for modification */

	$total = 0;
	$k = 0; //row colour counter

	if (count($_SESSION['PO']->line_items) > 0) {
		foreach ($_SESSION['PO']->line_items as $ln_itm) {

			alt_table_row_color($k);

			$qty_outstanding = $ln_itm->quantity - $ln_itm->qty_received;

			if (!isset($_POST['Update']) && !isset($_POST['ProcessGoodsReceived']) && $ln_itm->receive_qty == 0) {   //If no quantites yet input default the balance to be received
				$ln_itm->receive_qty = $qty_outstanding;
			}
			$price = Get_Policy_Cost($branch_code, $_SESSION['PO']->category_id, $ln_itm->stock_id, $_SESSION['PO']->supplier_id);
			if ($price == "")
				$price = 0;
			$ln_itm->price = $price;
			$ln_itm->standard_cost = $price;
			$line_total = ($ln_itm->receive_qty * $ln_itm->price);
			$total += $line_total;

			label_cell($ln_itm->stock_id);
			if ($qty_outstanding > 0)
				if ($_SESSION['PO']->is_consign == "Consignment")
					label_cell($ln_itm->item_description);
				else
					text_cells(null, $ln_itm->stock_id . "Desc", $ln_itm->item_description, 30, 50);
			else
				label_cell($ln_itm->item_description);
			if ($_SESSION['PO']->is_consign == "Consignment") {
				label_cell($ln_itm->serial);
				if ($_SESSION['PO']->category_id == 14)
					label_cell($ln_itm->chasis_no);
			}
			if ($_SESSION['PO']->category_id == 14)
				color_list_cells($ln_itm->stock_id, null, $ln_itm->line_no . 'color_code', $ln_itm->color_code);
			//label_cell($ln_itm->color_code);
			$dec = get_qty_dec($ln_itm->stock_id);
			qty_cell($ln_itm->quantity, false, $dec);
			label_cell($ln_itm->units);
			qty_cell($ln_itm->qty_received, false, $dec);
			qty_cell($qty_outstanding, false, $dec);

			if ($qty_outstanding > 0)
				qty_cells(null, $ln_itm->line_no, number_format2($ln_itm->receive_qty, $dec), "align=right", null, $dec);
			else
				label_cell(number_format2($ln_itm->receive_qty, $dec), "align=right");

			amount_decimal_cell($ln_itm->price);
			amount_cell($line_total);

			//echo "<td><center><a href='#' onclick='window_show(".$ln_itm->line_no.");return false;'>Serial Entry</a></center></td>";

			//button_cell('UpdateLine', _("Update"),_('Confirm changes'), ICON_EDIT);
			//hidden('serialize_id', $ln_itm->line_no);
			//set_focus('qty');

			//echo "<td><a href='item_serial_details.php?GRNSerialise=1&grnid=".$ln_itm->line_no."'>"._("Details")."</a></td>";
			end_row();
		}
	}

	$colspan = count($th) - 1;

	$display_sub_total = price_format($total/* + input_num('freight_cost')*/);

	label_row(_("Sub-total"), $display_sub_total, "colspan=$colspan align=right", "align=right");
	$taxes = $_SESSION['PO']->get_taxes(input_num('freight_cost'), true);

	$tax_total = display_edit_tax_items($taxes, $colspan, $_SESSION['PO']->tax_included);

	$display_total = price_format(($total + input_num('freight_cost') + $tax_total));

	start_row();
	label_cells(_("Amount Total"), $display_total, "colspan=$colspan align='right'", "align='right'");
	end_row();
	end_table();
	div_end();
}

//--------------------------------------------------------------------------------------------------

function check_po_changed()
{
	/*Now need to check that the order details are the same as they were when they were read
	into the Items array. If they've changed then someone else must have altered them */
	// Compare against COMPLETED items only !!
	// Otherwise if you try to fullfill item quantities separately will give error.
	$result = get_po_items($_SESSION['PO']->order_no);

	$line_no = 0;
	while ($myrow = db_fetch($result)) {
		$ln_item = $_SESSION['PO']->line_items[$line_no];
		// only compare against items that are outstanding
		$qty_outstanding = $ln_item->quantity - $ln_item->qty_received;
		if ($qty_outstanding > 0) {
			//$ln_item->stock_id != $myrow["item_code"] ||
			if (
				$ln_item->qty_inv != $myrow["qty_invoiced"]	||
				$ln_item->quantity != $myrow["quantity_ordered"] ||
				$ln_item->qty_received != $myrow["quantity_received"]
			) {
				return true;
			}
		}
		$line_no++;
	} /*loop through all line items of the order to ensure none have been invoiced */

	return false;
}

//--------------------------------------------------------------------------------------------------

function can_process()
{
	global $SysPrefs;

	if (count($_SESSION['PO']->line_items) <= 0) {
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

	if (!check_reference($_POST['ref'], ST_SUPPRECEIVE)) {
		set_focus('ref');
		return false;
	}
	if (is_null($_POST['suppl_ref_no']) || $_POST['suppl_ref_no'] == '') {
		display_error(_("The entered Supplier's Reference is empty."));
		set_focus('suppl_ref_no');
		return false;
	}
	if (!is_date($_POST['suppl_ref_date'])) {
		display_error(_("The entered Supplier's Reference Date is invalid."));
		set_focus('suppl_ref_date');
		return false;
	}
	if (is_null($_POST['suppl_served_by']) || $_POST['suppl_served_by'] == '') {
		display_error(_("The entered Invoiced served by is empty."));
		set_focus('suppl_served_by');
		return false;
	}

	//Added by spyrax10
	if ($_POST['DefaultReceivedDate'] < $_SESSION['PO']->orig_order_date) {
		display_error(_("RR Date should not less than PO Date!"));
		return false;
	}

	$something_received = 0;
	foreach ($_SESSION['PO']->line_items as $order_line) {
		if ($order_line->receive_qty > 0) {
			$something_received = 1;
			break;
		}
	}

	// Check whether trying to deliver more items than are recorded on the actual purchase order (+ overreceive allowance)
	$delivery_qty_too_large = 0;
	foreach ($_SESSION['PO']->line_items as $order_line) {
		if (
			$order_line->receive_qty + $order_line->qty_received >
			$order_line->quantity * (1 + ($SysPrefs->over_receive_allowance() / 100))
		) {
			$delivery_qty_too_large = 1;
			break;
		}
	}

	if ($something_received == 0) { 	/*Then dont bother proceeding cos nothing to do ! */
		display_error(_("There is nothing to process. Please enter valid quantities greater than zero."));
		return false;
	} elseif ($delivery_qty_too_large == 1) {
		display_error(_("Entered quantities cannot be greater than the quantity entered on the purchase order including the allowed over-receive percentage") . " (" . $SysPrefs->over_receive_allowance() . "%)."
			. "<br>" .
			_("Modify the ordered items on the purchase order if you wish to increase the quantities."));
		return false;
	}

	/* Added by Ronelle 7/17/2021 Check if Allow Zero Cost */
	if ($_SESSION['PO']->trans_type == ST_SUPPRECEIVE) {
		foreach ($_SESSION['PO']->line_items as $order_line) {
			if (!check_allow_zero_cost_item($order_line->stock_id) && $order_line->price == 0) {
				display_error(_("Items contains 0 cost"));
				return false;
			}
		}
	}
	/* */

	return true;
}

//--------------------------------------------------------------------------------------------------

function process_receive_po()
{
	global $path_to_root, $Ajax;

	if (!can_process())
		return;

	if (check_po_changed()) {
		display_error(_("This order has been changed or invoiced since this delivery was started to be actioned. Processing halted. To enter a delivery against this purchase order, it must be re-selected and re-read again to update the changes made by the other user."));

		hyperlink_no_params(
			"$path_to_root/purchasing/inquiry/po_search.php",
			_("Select a different purchase order for receiving goods against")
		);

		hyperlink_params(
			"$path_to_root/purchasing/po_receive_items.php",
			_("Re-Read the updated purchase order for receiving goods against"),
			"PONumber=" . $_SESSION['PO']->order_no
		);

		unset($_SESSION['PO']->line_items);
		unset($_SESSION['PO']);
		unset($_POST['ProcessGoodsReceived']);
		$Ajax->activate('_page_body');
		display_footer_exit();
	}

	$grn = &$_SESSION['PO'];
	$grn->orig_order_date = $_POST['DefaultReceivedDate'];
	$grn->reference = $_POST['ref'];
	$grn->Location = $_POST['Location'];
	$grn->ex_rate = input_num('_ex_rate', null);
	$grn->suppl_ref_no = $_POST['suppl_ref_no'];
	$grn->suppl_ref_date = $_POST['suppl_ref_date'];
	$grn->suppl_served_by = $_POST['suppl_served_by'];
	$grn->Comments = $_POST['GRNComments'];
	$category_id = $grn->category_id;
	$grn_no = ($_SESSION['PO']->is_consign == "Consignment") ? add_grn($grn, true) : add_grn($grn);

	new_doc_date($_POST['DefaultReceivedDate']);
	unset($_SESSION['PO']->line_items);
	unset($_SESSION['PO']);

	meta_forward($_SERVER['PHP_SELF'], "AddedID=$grn_no&category=$category_id");
}

//--------------------------------------------------------------------------------------------------

if (isset($_GET['PONumber']) && $_GET['PONumber'] > 0 && !isset($_POST['Update'])) {
	create_new_po(ST_PURCHORDER, $_GET['PONumber']);
	$_SESSION['PO']->trans_type = ST_SUPPRECEIVE;
	$_POST['po_number'] = $_SESSION['PO']->reference;
	$_SESSION['PO']->reference = $Refs->get_next(
		ST_SUPPRECEIVE,
		array('date' => Today(), 'supplier' => $_SESSION['PO']->supplier_id)
	);
	copy_from_cart();
}

//--------------------------------------------------------------------------------------------------

if (isset($_POST['Update']) || isset($_POST['ProcessGoodsReceived'])) {

	/* if update quantities button is hit page has been called and ${$line->line_no} would have be
 	set from the post to the quantity to be received in this receival*/
	foreach ($_SESSION['PO']->line_items as $line) {
		if (($line->quantity - $line->qty_received) > 0) {
			$_POST[$line->line_no] = max($_POST[$line->line_no], 0);
			if (!check_num($line->line_no))
				$_POST[$line->line_no] = number_format2(0, get_qty_dec($line->stock_id));

			if (!isset($_POST['DefaultReceivedDate']) || $_POST['DefaultReceivedDate'] == "")
				$_POST['DefaultReceivedDate'] = new_doc_date();

			$_SESSION['PO']->line_items[$line->line_no]->receive_qty = input_num($line->line_no);

			//if(isset($_POST[$line->stock_id . "color_code"]) && strlen($_POST[$line->line_no . "color_code"]) > 0){
			$_SESSION['PO']->line_items[$line->line_no]->color_code = $_POST[$line->line_no . "color_code"];
			//}
			// echo $_SESSION['PO']->line_items[$line->line_no]->color_code;

			if (isset($_POST[$line->stock_id . "Desc"]) && strlen($_POST[$line->stock_id . "Desc"]) > 0) {
				$_SESSION['PO']->line_items[$line->line_no]->item_description = $_POST[$line->stock_id . "Desc"];
			}
		}
	}
	$Ajax->activate('grn_items');
}

//--------------------------------------------------------------------------------------------------

if (isset($_POST['ProcessGoodsReceived'])) {
	process_receive_po();
}

//--------------------------------------------------------------------------------------------------

start_form();

edit_grn_summary($_SESSION['PO'], true);
display_heading(_("Items to Receive"));
display_po_receive_items();

echo '<br>';
start_table(TABLESTYLE2);


if ($_SESSION['PO']->trans_type == ST_SUPPINVOICE) {
	cash_accounts_list_row(_("Payment:"), 'cash_account', null, false, _('Delayed'));
}

textarea_row(_("Remarks:"), 'GRNComments', null, 70, 4);

end_table(1);

submit_center_first('Update', _("Update"), '', true);
submit_center_last('ProcessGoodsReceived', _("Process Receive Items"), _("Clear all GL entry fields"), 'default');

end_form();

//--------------------------------------------------------------------------------------------------
end_page();
