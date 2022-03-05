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
$page_security = 'SA_COPYFROM_RCON_TO_PO';
$path_to_root = "..";
include_once($path_to_root . "/purchasing/includes/consign_class.inc");

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
page(_($help_context = "Order RR Consignment Items"), false, false, "", $js);

//---------------------------------------------------------------------------------------------------------------

if (isset($_GET['AddedID'])) {
	$rconpo = $_GET['AddedID'];
	$trans_type = ST_PURCHORDER;

	display_notification_centered(_("Draft Purchase Order has been processed"));

	display_note(get_trans_view_str($trans_type, $rconpo, _("&View this Draft Purchase Order")));

	display_footer_exit();
}

//--------------------------------------------------------------------------------------------------

if ((!isset($_GET['ConsignmentNumber']) || empty($_GET['ConsignmentNumber'])) && !isset($_SESSION['RCON'])) {
	die(_("This page can only be opened if a rr consignment has been selected. Please select a rr consignment first."));
}

function display_rcon_order_items()
{
	div_start('order_items');
	start_table(TABLESTYLE, "colspan=7 width='90%'");
	$th = array(
		_("Item Code"), _("Description"), _("Serial No."), _("Chassis No."), _("Color Description - (Code)"), _("Received"), _("QOH"), _("This Order"), _("SRP"), _("Total")
	);

	if ($_SESSION['RCON']->category_id != 14) {
		array_remove($th, 3);
		array_remove($th, 3);
	}
	table_header($th);

	/*show the line items on the order with the quantity being received for modification */

	$total = 0;
	$k = 0; //row colour counter

	if (count($_SESSION['RCON']->line_items) > 0) {
		foreach ($_SESSION['RCON']->line_items as $ln_itm) {
			$price = Get_Policy_SRP('GALY', $_SESSION['RCON']->category_id, $ln_itm->stock_id,$_SESSION['RCON']->supplier_id);
			alt_table_row_color($k);

			$qty_outstanding = $ln_itm->quantity - $ln_itm->qty_ordered;
			$ln_itm->price = $price;

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

			label_cell($ln_itm->serial_no);
			if ($_SESSION['RCON']->category_id == 14)
				label_cell($ln_itm->chassis_no);
			if ($_SESSION['RCON']->category_id == 14)
				color_list_cells($ln_itm->stock_id, null, $ln_itm->line_no . 'color_code', $ln_itm->color_code);
			$dec = get_qty_dec($ln_itm->stock_id);
			$qoh = get_qoh_on_date($ln_itm->stock_id, 0);
			qty_cell($ln_itm->quantity, false, $dec);
			qty_cell($qoh, false, 0);

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
	$taxes = $_SESSION['RCON']->get_taxes(input_num('freight_cost'), true);

	$tax_total = display_edit_tax_items($taxes, $colspan, $_SESSION['RCON']->tax_included);

	$display_total = price_format(($total + input_num('freight_cost') + $tax_total));

	start_row();
	label_cells(_("Amount Total"), $display_total, "colspan=$colspan align='right'", "align='right'");
	end_row();
	end_table();
	div_end();
}

function check_pr_changed()
{
	/*Now need to check that the order details are the same as they were when they were read
	into the Items array. If they've changed then someone else must have altered them */
	// Compare against COMPLETED items only !!
	// Otherwise if you try to fullfill item quantities separately will give error.
	$result = get_pr_items($_SESSION['RCON']->pr_no);

	$line_no = 0;
	while ($myrow = db_fetch($result)) {
		$ln_item = $_SESSION['RCON']->line_items[$line_no];
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

	if (count($_SESSION['RCON']->line_items) <= 0) {
		display_error(_("There is nothing to process. Please enter valid quantities greater than zero."));
		return false;
	}

	if (!is_date($_POST['DefaultReceivedDate'])) {
		display_error(_("The entered date is invalid."));
		set_focus('DefaultReceivedDate');
		return false;
	}
	// if (!is_date_in_fiscalyear($_POST['DefaultReceivedDate'])) {
	// 	display_error(_("The entered date is out of fiscal year or is closed for further data entry."));
	// 	set_focus('DefaultReceivedDate');
	// 	return false;
	// }

	if (!check_reference($_POST['po_reference'], ST_PURCHORDER)) {
		set_focus('po_reference');
		return false;
	}

	$something_received = 0;
	foreach ($_SESSION['RCON']->line_items as $request_line) {
		if ($request_line->quantity > 0) {
			$something_received = 1;
			break;
		} else if (get_purchase_price($_SESSION['RCON']->supplier_id, $request_line->stock_id) == 0) {
		}
	}

	/* Added By Ronelle 10/16/2020  */
	// Check SRP is not 0
	// $no_srp = false;
	// foreach ($_SESSION['RCON']->line_items as $request_line) {
	// 	if (get_purchase_price($_SESSION['RCON']->supplier_id, $request_line->stock_id) == 0) {
	// 		$no_srp = true;
	// 		break;
	// 	}
	// }
	/* */

	// Check whether trying to deliver more items than are recorded on the actual purchase order (+ overreceive allowance)
	// $delivery_qty_too_large = 0;
	// foreach ($_SESSION['RCON']->line_items as $request_line) {
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
	} else if ($no_srp) {
		display_error(_("There is nothing to process. Please enter valid srp greater than zero."));
		return false;
	}

	return true;
}

function process_request_po()
{
	global $path_to_root, $Ajax;

	if (!can_process())
		return;

	// if (check_pr_changed()) {
	// 	display_error(_("This order has been changed or invoiced since this delivery was started to be actioned. Processing halted. To enter a delivery against this purchase order, it must be re-selected and re-read again to update the changes made by the other user."));

	// 	hyperlink_no_params(
	// 		"$path_to_root/purchasing/inquiry/purchase_request.php",
	// 		_("Select a different rr consignment for purchase order against")
	// 	);

	// 	hyperlink_params(
	// 		"$path_to_root/purchasing/pr_order_items.php",
	// 		_("Re-Read the updated rr consignment for purchase order against"),
	// 		"ConsignmentNumber=" . $_SESSION['RCON']->po_reference
	// 	);

	// 	unset($_SESSION['RCON']->line_items);
	// 	unset($_SESSION['RCON']);
	// 	unset($_POST['ProcessPO']);
	// 	$Ajax->activate('_page_body');
	// 	display_footer_exit();
	// }

	$rconpo = &$_SESSION['RCON'];
	$rconpo->orig_order_date = $_POST['DefaultReceivedDate'];
	$rconpo->po_reference = $_POST['po_reference'];
	$rconpo->Location = $_POST['Location'];
	// $rconpo->supp_ref = $_SESSION['RCON']->pr_no;
	$rconpo->delivery_address = $_POST['DeliveryAddress'];
	$rconpo->ex_rate = input_num('_ex_rate', null);
	$rconpo->purch_type_id = $_POST['purch_type_id'];
	$rconpo_no = copy_rcon_to_po($rconpo);

	new_doc_date($_POST['DefaultReceivedDate']);
	unset($_SESSION['RCON']->line_items);
	unset($_SESSION['RCON']);

	meta_forward($_SERVER['PHP_SELF'], "AddedID=$rconpo_no");
}


//--------------------------------------------------------------------------------------------------

if (isset($_GET['ConsignmentNumber']) && !empty($_GET['ConsignmentNumber']) && !isset($_POST['Update'])) {
	create_new_consign(ST_RECEIVECONSIGN, $_GET['ConsignmentNumber'], $_GET['Serial']);
	$_SESSION['RCON']->trans_type = ST_PURCHORDER;
	$_SESSION['RCON']->serialise_id = $_GET['Serial'];
	consign_copy_from_cart();
}

//--------------------------------------------------------------------------------------------------

if (isset($_POST['Update']) || isset($_POST['ProcessPO'])) {
	/* if update quantities button is hit page has been called and ${$line->line_no} would have be
 	set from the post to the quantity to be received in this receival*/
	foreach ($_SESSION['RCON']->line_items as $line) {
		if (($line->quantity - $line->qty_ordered) > 0) {
			$_POST[$line->line_no] = max($_POST[$line->line_no], 0);
			if (!check_num($line->line_no))
				$_POST[$line->line_no] = number_format2(0, get_qty_dec($line->stock_id));

			if (!isset($_POST['DefaultReceivedDate']) || $_POST['DefaultReceivedDate'] == "")
				$_POST['DefaultReceivedDate'] = new_doc_date();

			$_SESSION['RCON']->line_items[$line->line_no]->receive_qty = input_num($line->line_no);
			if ($_SESSION['RCON']->category_id == 14)
				$_SESSION['RCON']->line_items[$line->line_no]->color_code = $_POST[$line->line_no . "color_code"];
			if (isset($_POST[$line->stock_id . "Desc"]) && strlen($_POST[$line->stock_id . "Desc"]) > 0) {
				$_SESSION['RCON']->line_items[$line->line_no]->item_description = $_POST[$line->stock_id . "Desc"];
			}
		}
	}
	$Ajax->activate('order_items');
}

//--------------------------------------------------------------------------------------------------

if (isset($_POST['ProcessPO'])) {
	process_request_po();
}

//--------------------------------------------------------------------------------------------------

start_form();

edit_copy_from_rcon_to_po_summary($_SESSION['RCON'], true);
display_heading(_("Items to Order"));
display_rcon_order_items();

echo '<br>';
submit_center_first('ProcessPO', _("Process Order Items"), _("Clear all GL entry fields"), 'default');

end_form();

//--------------------------------------------------------------------------------------------------
end_page();
