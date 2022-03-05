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
$page_security = 'SA_SERIALITEMS';
$path_to_root = "..";
include($path_to_root . "/includes/session.inc");
add_access_extensions();

$js = "";;
//add_js_ufile("../../js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
//add_js_ufile('../../js/serial_items.js');

if (user_use_date_picker())
	$js .= get_js_date_picker();

page(_($help_context = "Serial Items Entries"), false, false, "", $js);
//page(_($help_context = "Serial Items Entries"));

include_once($path_to_root . "/modules/serial_items/includes/modules_db.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/ui/ui_lists.inc");

simple_page_mode(true);
//-----------------------------------------------------------------------------------
if (isset($_GET['serialid'])) {
	$_POST['serialid'] = $_GET['serialid'];
}
if (isset($_GET['itemserialid'])) {
	$_POST['itemserialid'] = $_GET['itemserialid'];
}
if (isset($_GET['grnitemsid'])) {
	$_POST['grnitemsid'] = $_GET['grnitemsid'];
}
if (isset($_GET['item_code'])) {
	$_POST['item_code'] = $_GET['item_code'];
}
if (isset($_GET['loc_code'])) {
	$_POST['loc_code'] = $_GET['loc_code'];
}
if (isset($_GET['complete'])) {
	$_POST['complete'] = $_GET['complete'];
}
if (isset($_GET['line_qty'])) {
	$_POST['line_qty'] = $_GET['line_qty'];
}

if ($Mode == 'ADD_ITEM' || $Mode == 'UPDATE_ITEM') {

	//initialise no input errors assumed initially before we test
	$input_error = 0;

	if (strlen($_POST['serialid']) == 0) {
		$input_error = 1;
		display_error(_("The GRN No of use cannot be empty." . $selected_id));
		set_focus('name');
	}
	if (search_serialitem($_POST['lot_no']) > 0) {
		$input_error = 1;
		display_error(_("The Serial/Engine No. is already exist. " . $_POST['lot_no']));
		set_focus('lot_no');
	}
	if (strlen($_POST['order_quantity']) == 0 || $_POST['order_quantity'] <= 0) {
		$input_error = 1;
		display_error(_("The Quantity should not be empty or less than 0." . $selected_id));
		set_focus('order_quantity');
	}

	$total_grn_item_qty = $_POST['line_qty'];
	$serialise_count = count_serialise_item($_POST['serialid'], $_POST['itemserialid']);
	if ($Mode == 'UPDATE_ITEM') {
		$serialise_count_item = count_serialise_id($selected_id);
		$serialise_count -= $serialise_count_item;
		//$serialise_count= $_POST['order_quantity'];
	}
	$serialise_count += $_POST['order_quantity'];

	if ($serialise_count > $total_grn_item_qty) {
		$input_error = 1;
		display_error(_("The Quantity should not be greater then Total Quantity to receive." . $selected_id));
		set_focus('order_quantity');
	}

	if ($input_error != 1) {
		if ($selected_id != -1) {
			update_serialitems_detail($selected_id, $_POST['lot_no'], $_POST['manufacture_date'], $_POST['order_quantity'], $_POST['expire_date'], $_POST['chasis_no'], 51);
			display_notification(_('Selected item serial details has been updated.'));
		} else {
			add_serialitems_detail($_POST['serialid'], $_POST['itemserialid'], $_POST['lot_no'], $_POST['order_quantity'], $_POST['manufacture_date'], $_POST['expire_date'], $_POST['item_code'], $_POST['loc_code'], $_POST['chasis_no'], 51);
			display_notification(_('New Serial Items has been added'));
		}
		if ($serialise_count == $total_grn_item_qty) {
			$_POST['complete'] = 1;
		}
		$Mode = 'RESET';
	}
}

//-----------------------------------------------------------------------------------

if ($Mode == 'Delete') {
	delete_serialitem_detail($selected_id);
	display_notification(_('Selected serial item detail has been deleted'));

	$Mode = 'RESET';
}

if ($Mode == 'RESET') {
	$selected_id = -1;
	//$sav = get_post('show_inactive');
	$serialid = $_POST['serialid'];
	$itemserialid = $_POST['itemserialid'];
	$item_code = $_POST['item_code'];
	$loc_code = $_POST['loc_code'];
	// $complete = $_POST['complete'];
	$line_qty = $_POST['line_qty'];
	unset($_POST);
	$_POST['serialid'] = $serialid;
	$_POST['itemserialid'] = $itemserialid;
	$_POST['item_code'] = $item_code;
	$_POST['loc_code'] = $loc_code;
	// $_POST['complete'] = $complete;
	$_POST['line_qty'] = $line_qty;
}
//-----------------------------------------------------------------------------------

$result = get_consign_serial(get_post('serialid'));


start_table(TABLESTYLE, "width=80%");
$cat_result = get_category_id(get_post('serialid'), true);
if ($cat_result == 14) {
	$th = array(_("#"), _("Consignment #"), _("Model Code"), _("Description"), _("Color Code"), _("Quantity Received"), _("Serialise Qty"), "");
} else {
	$th = array(_("#"), _("Consignment #"), _("Model Code"), _("Description"), _("Quantity Received"), _("Serialise Qty"), "");
}
//inactive_control_column($th);
table_header($th);
$k = 0;
while ($myrow = db_fetch($result)) {
	if (isset($_POST['itemserialid'])) {
		if ($myrow['consign_detail_item'] == $_POST['itemserialid']) {
			start_row("class='overduebg'");
			//$overdue_items = true;
		} else {
			alt_table_row_color($k);
		}
	}

	label_cell($myrow["consign_detail_item"]);
	label_cell($myrow["reference"]);

	label_cell($myrow["item_code"]);

	label_cell($myrow["description"], "nowrap align=left");
	if ($cat_result == 14) {
		label_cell($myrow["color_code"], "nowrap align=left");
		$itemselect = $myrow["color_code"];
	} else {
		$itemselect = $myrow["item_code"];
	}
	$dec = get_qty_dec($myrow["item_code"]);
	qty_cell($myrow["qty"], false, $dec);
	$serialise_count = count_serialise_item($myrow['consign_no'], $myrow['consign_detail_item']);
	qty_cell($serialise_count, false, $dec);
	$loc_code = "DEF";
	if ($serialise_count < $myrow["qty"]) {
		echo "<td align=center colspan=2><a href='consignment_serial_details.php?serialitemdetails=yes&itemserialid=" . $myrow['consign_detail_item'] . "&serialid=" . $myrow['consign_no'] . "&item_code=" . $itemselect . "&loc_code=" . $loc_code . "&complete=0&line_qty=" . $myrow['qty'] . "'>" . _("Details") . "</a></td>\n";
	} else {
		echo "<td align=center><a href='consignment_serial_details.php?serialitemdetails=yes&itemserialid=" . $myrow['consign_detail_item'] . "&serialid=" . $myrow['consign_no'] . "&item_code=" . $itemselect . "&loc_code=" . $loc_code . "&complete=1&line_qty=" . $myrow['qty'] . "'>" . _("Complete") . "</a></td><td>
		<a href='consignment_serial_details.php?serialitemdetails=yes&itemserialid=" . $myrow['consign_detail_item'] . "&serialid=" . $myrow['consign_no'] . "&item_code=" . $itemselect . "&loc_code=" . $loc_code . "&complete=0&line_qty=" . $myrow['qty'] . "'>Edit</a>
		</td>\n";
	}


	end_row();
}

end_table(1);

//-----------------------------------------------------------------------------------
echo "<hr/>\n";
if (get_post('itemserialid')) {
	//$_POST['itemserialid'] = $_GET['itemserialid'];

	$result = get_serial_by_consign_no(get_post('serialid'), get_post('itemserialid'));

	start_form();
	start_table(TABLESTYLE, "width=50%");
	if ($cat_result == 14) {
		$th = array(_("Location"), _("Quantity"), _("Engine No."), _("Chasis No."), "", "");
	} else {
		$th = array(_("Location"), _("Quantity"), _("Serial No."), _(""));
	}
	table_header($th);
	$k = 0;
	while ($myrow = db_fetch($result)) {
		alt_table_row_color($k);

		label_cell($myrow["serialise_loc_code"]);
		label_cell($myrow["serialise_qty"]);

		label_cell($myrow["serialise_lot_no"]);
		if ($cat_result == 14) {
			label_cell($myrow["serialise_chasis_no"]);
		}

		if (get_post('complete') == 0) {
			edit_button_cell("Edit" . $myrow['serialise_id'], _("Edit"));
			// delete_button_cell("Delete" . $myrow['serialise_id'], _("Delete"));
		} else {
			echo "<td></td><td></td>";
		}

		end_row();
	}
	end_table(1);


	//-----------------------------------------------------------------------------------
	start_table(TABLESTYLE2);
	//echo $selected_id;
	if (get_post('complete') == 0) {
		if ($selected_id != -1) {
			if ($Mode == 'Edit') {
				//editing an existing status code

				$myrow = get_serialitems_detail($selected_id);
				//echo $selected_id;
				//die();
				$_POST['item_code']  = $myrow["serialise_item_code"];
				//$_POST['purpose']  = $myrow["purpose"];
				//$_POST['color_code']  = $myrow["color_code"];
				$_POST['order_quantity']  = $myrow["serialise_qty"];
				$_POST['serialid']  = $myrow["serialise_grn_id"];
				$_POST['lot_no']  = $myrow["serialise_lot_no"];
				$_POST['chasis_no'] = $myrow["serialise_chasis_no"];
				$_POST['manufacture_date']  = sql2date($myrow["serialise_manufacture_date"]);
				$_POST['expire_date']  = sql2date($myrow["serialise_expire_date"]);
				$_POST['itemserialid']  = $myrow["serialise_grn_items_id"];
				//$_POST['estimate_price']  = $myrow["estimate_price"];
			}
			hidden('selected_id', $selected_id);
			//hidden('serialid');
			//hidden('itemserialid');
		} else {
			$_POST['order_quantity'] = 0;
		}
		//sales_local_items_list_row(_("Item :"), 'item_code', null, false, false);

		$res = get_item_edit_info(get_post('item_code'));
		$dec =  $res["decimals"] == '' ? 0 : $res["decimals"];
		$units = $res["units"] == '' ? _('kits') : $res["units"];
		//text_row(_("GRN No:"),'serialid',null);
		//text_row(_("GRN Line No:"),'itemserialid',null);
		//text_row(_("Location Code:"),'loc_code',null);
		//text_row(_("Item Code:"),'item_code',null);
		qty_row(_("Quantity:"), 'order_quantity', number_format2(1, $dec), '', $units, $dec);
		if ($cat_result == 14) {
			text_row(_("Engine No.:"), 'lot_no', null, 50, 50);
			text_row(_("Chasis No.:"), 'chasis_no', null, 50, 50);
		} else {
			text_row(_("Serial No.:"), 'lot_no', null, 50, 50);
			hidden('chasis_no');
		}
		//date_row(_("Manufacture Date :"), 'manufacture_date', '', null, 0, 0, 1001);
		//date_row(_("Expire Date :"), 'expire_date', '', null, 0, 0, 1001);
		//amount_row(_("Estimate Price :"), 'estimate_price', null, null, null, 2);
		set_focus('order_quantity');
		hidden('loc_code');
		hidden('item_code');
		hidden('serialid');
		hidden('manufacture_date');
		hidden('expire_date');
		hidden('itemserialid');
		hidden('consign_no');
		hidden('line_qty');
		hidden('complete');
		end_table(1);

		submit_add_or_update_center($selected_id == -1, '', 'both');
	}
	//submit_center_last('CancelEntry', 'Close');
}

end_form();

//------------------------------------------------------------------------------------

end_page();
