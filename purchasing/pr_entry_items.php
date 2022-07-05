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
$page_security = 'SA_PURCHASEREQUEST';
include_once($path_to_root . "/purchasing/includes/pr_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/purchasing/includes/db/pr_db.inc");
include_once($path_to_root . "/purchasing/includes/db/suppliers_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

set_page_security(
	@$_SESSION['PR']->trans_type,
	array(
		ST_PURCHREQUEST => 'SA_PURCHASEREQUEST'
	),
	array(
		'NewRequest' => 'SA_PURCHASEREQUEST',
		'ModifyRequestNumber' => 'SA_PURCHASEREQUEST',
		'AddedID' => 'SA_PURCHASEREQUEST'
	)
);

$js = '';
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

if (isset($_GET['ModifyRequestNumber']) && is_string($_GET['ModifyRequestNumber'])) {

	$_SESSION['page_title'] = _($help_context = "Modify Purchase Request #") . $_GET['ModifyRequestNumber'];
	create_new_pr(ST_PURCHREQUEST, $_GET['ModifyRequestNumber']);
	pr_copy_from_cart();
} elseif (isset($_GET['NewRequest'])) {
	$_SESSION['page_title'] = _($help_context = "Purchase Request");
	create_new_pr(ST_PURCHREQUEST, (string) NULL);
	pr_copy_from_cart();
}

page($_SESSION['page_title'], false, false, "", $js);

if (isset($_GET['AddedID'])) {
	$pr_no = $_GET['AddedID'];
	$trans_type = ST_PURCHREQUEST;

	if (!isset($_GET['Updated']))
		display_notification_centered(_("Purchase Request has been entered"));
	else
		display_notification_centered(_("Purchase Request has been updated") . " #$pr_no");
	display_note(get_trans_view_str($trans_type, $pr_no, _("&View this request")), 0, 1);

	hyperlink_params("$path_to_root/purchasing/purchase_request.php", _("Back to Purchase Request List"), "", true);

	// display_note(print_document_link($pr_no, _("&Print This Request"), true, $trans_type), 0, 1);

	// display_note(print_document_link($pr_no, _("&Email This Order"), true, $trans_type, false, "printlink", "", 1));

	// TODO, for fixed asset
	hyperlink_params($_SERVER['PHP_SELF'], _("Enter &Another Purchase Request"), "NewRequest=yes");

	// hyperlink_no_params($path_to_root."/purchasing/inquiry/po_search.php", _("Select An &Outstanding Purchase Request"));

	display_footer_exit();
}

//---------------------------------------------------------------------------------------------------
//
// Add PR
//
function add_pr_trans($cart)
{
	global $Refs, $type_shortcuts;

	$pr_no = add_pr($cart);
	$cart->pr_no = $pr_no;

	if ($cart->trans_type == ST_PURCHREQUEST)
		return $pr_no;

	commit_transaction();
}


function pr_line_start_focus()
{
	global 	$Ajax;
	$Ajax->activate('items_table');
	set_focus('_stock_id_edit');
}

function line_start_focus()
{
	global 	$Ajax;

	$Ajax->activate('items_table');
	set_focus('_stock_id_edit');
}
//--------------------------------------------------------------------------------------------------

function unset_form_variables()
{
	unset($_POST['stock_id']);
	unset($_POST['qty']);
	unset($_POST['price']);
	unset($_POST['req_del_date']);
	unset($_POST['color_code']);
}

//---------------------------------------------------------------------------------------------------

function handle_delete_item($line_no)
{
	if ($_SESSION['PR']->some_already_received($line_no) == 0) {
		$_SESSION['PR']->remove_from_order($line_no);
		unset_form_variables();
	} else {
		display_error(_("This item cannot be deleted because some of it has already been received."));
	}
	line_start_focus();
}

//---------------------------------------------------------------------------------------------------

function handle_cancel_pr()
{
	global $path_to_root;

	//need to check that not already dispatched or invoiced by the supplier
	if (($_SESSION['PR']->pr_no != 0) &&
		$_SESSION['PR']->any_already_received() == 1
	) {
		display_error(_("This order cannot be cancelled because some of it has already been received.")
			. "<br>" . _("The line item quantities may be modified to quantities more than already received. prices cannot be altered for lines that have already been received and quantities cannot be reduced below the quantity already received."));
		return;
	}

	$fixed_asset = $_SESSION['PR']->fixed_asset;

	if ($_SESSION['PR']->pr_no != 0)
		delete_po($_SESSION['PR']->pr_no);
	else {
		unset($_SESSION['PR']);

		if ($fixed_asset)
			meta_forward($path_to_root . '/index.php', 'application=assets');
		else
			meta_forward($path_to_root . '/index.php', 'application=AP');
	}

	$_SESSION['PR']->clear_items();
	$_SESSION['PR'] = new purch_request;

	display_notification(_("This purchase Request has been cancelled."));

	hyperlink_params($path_to_root . "/purchasing/po_entry_items.php", _("Enter a new purchase Request"), "NewOrder=Yes");
	echo "<br>";

	end_page();
	exit;
}

//---------------------------------------------------------------------------------------------------

function check_data()
{
	if (!get_post('stock_id_text', true)) {
		display_error(_("Item description cannot be empty."));
		set_focus('stock_id_edit');
		return false;
	}

	// $dec = get_qty_dec($_POST['stock_id']);
	// $min = 1 / pow(10, $dec);
	// if (!check_num('qty', $min)) {
	// 	$min = number_format2($min, $dec);
	// 	display_error(_("The quantity of the order item must be numeric and not less than ") . $min);
	// 	set_focus('qty');
	// 	return false;
	// }

	//   if (!check_num('price', 0))
	//   {
	// 		 display_error(_("The price entered must be numeric and not less than zero."));
	// 	  set_focus('price');
	// 		 return false;	   
	//   }
	//   if ($_SESSION['PR']->trans_type == ST_PURCHREQUEST && !is_date($_POST['req_del_date'])){
	// 		  display_error(_("The date entered is in an invalid format."));
	// 	  set_focus('req_del_date');
	// 		 return false;    	 
	//   }

	return true;
}

//---------------------------------------------------------------------------------------------------

function handle_update_item()
{
	$allow_update = check_data();

	if ($allow_update) {
		if (
			$_SESSION['PR']->line_items[$_POST['line_no']]->qty_inv > input_num('qty') ||
			$_SESSION['PR']->line_items[$_POST['line_no']]->qty_ordered > input_num('qty')
		) {
			display_error(_("You are attempting to make the quantity ordered a quantity less than has already been invoiced or received.  This is prohibited.") .
				"<br>" . _("The quantity received can only be modified by entering a negative receipt and the quantity invoiced can only be reduced by entering a credit note against this item."));
			set_focus('qty');
			return;
		}

		$_SESSION['PR']->update_order_item(
			$_POST['line_no'],
			input_num('qty'),
			input_num('price'),
			@$_POST['req_del_date'],
			$_POST['item_description'],
			($_POST['category'] == 14) ? $_POST['color_code'] : ''
		);
		unset_form_variables();
	}
	line_start_focus();
}

//Modified by spyrax10
function handle_pr_add_new_item()
{
	$allow_update = check_data();

	if ($allow_update == true) {
		if (count($_SESSION['PR']->line_items) > 0) {
			foreach ($_SESSION['PR']->line_items as $order_item) {
				/* do a loop round the items on the order to see that the item
				  is not already on this order */
				if (($order_item->stock_id == $_POST['stock_id'])) {
					display_warning(_("The selected item is already on this order."));
				}
			} /* end of the foreach loop to look for pre-existing items of the same code */
		}

		if ($allow_update == true) {
			$result = get_short_info($_POST['stock_id']);

			if (db_num_rows($result) == 0) {
				$allow_update = false;
			}

			if ($allow_update) {
				$_SESSION['PR']->pr_add_to_order(
					count($_SESSION['PR']->line_items),
					$_POST['stock_id'],
					input_num('qty'),
					get_post('stock_id_text'), //$myrow["description"], 
					0,
					'', // $myrow["units"], (retrived in cart)
					$_SESSION['PR']->trans_type == ST_PURCHREQUEST ? '' : '',
					0,
					0,
					(get_post("category") == 14) ? $_POST['color_code'] : ''
				);

			} else {
				display_error(_("The selected item does not exist or it is a kit part and therefore cannot be purchased."));
			}
		} /* end of if not already on the order and allow input was true*/
	}
	
	unset($_POST['_stock_id_edit'], $_POST['stock_id'], $_POST['qty']);
	page_modified();
	line_start_focus();
}

//---------------------------------------------------------------------------------------------------

function can_commit()
{
	if (!get_post('supplier_id')) {
		display_error(_("There is no supplier selected."));
		set_focus('supplier_id');
		return false;
	}

	if (!is_date($_POST['PRDate'])) {
		display_error(_("The entered order date is invalid."));
		set_focus('PRDate');
		return false;
	}

	//Added by spyrax10 5 Jul 2022
	if (!allowed_posting_date($_POST['PRDate'])) {
		display_error(_("The Entered Order Date is currently LOCKED for further data entry."));
		set_focus('PRDate');
		return false;
	}
	//

	if (!$_SESSION['PR']->pr_no) {
		if (!check_reference(get_post('reference'), $_SESSION['PR']->trans_type)) {
			set_focus('reference');
			return false;
		}
	}

	return true;
}

function handle_commit_request()
{
	$cart = &$_SESSION['PR'];
	pr_copy_to_cart();
	new_doc_date($cart->orig_order_date);

	if (empty($cart->pr_no)) {
		$pr_no = add_pr_trans($cart);
		unset($_SESSION['PR']);
		if ($cart->trans_type == ST_PURCHREQUEST)
		meta_forward($_SERVER['PHP_SELF'], "AddedID=$pr_no");
	} else {
		$pr_no = update_pr($cart);
		unset($_SESSION['PR']);
		meta_forward($_SERVER['PHP_SELF'], "AddedID=$pr_no&Updated=1");
	}
}
//---------------------------------------------------------------------------------------------------

function handle_cancel_request()
{
	$cart = &$_SESSION['PR'];
	pr_copy_to_cart();

	$pr_no = cancel_pr($cart);
	unset($_SESSION['PR']);
	meta_forward($_SERVER['PHP_SELF'], "AddedID=$pr_no&Canceled=1");
}

if (get_post("category")) {
	$Ajax->activate("items_table");
}

if (isset($_POST['update'])) {
	pr_copy_to_cart();
	$Ajax->activate('items_table');
}

$id = find_submit('Delete');
if ($id != -1)
	handle_delete_item($id);

if (isset($_POST['Commit'])) {
	handle_commit_request();
}
// if (isset($_POST['CancelDoc'])) {
// 	handle_cancel_request();
// }
if (isset($_POST['UpdateLine']))
	handle_update_item();

if (isset($_POST['EnterLine'])) {
	handle_pr_add_new_item();
}

if (isset($_POST['CancelRequest']))
	handle_cancel_pr();

if (isset($_POST['CancelUpdate']))
	unset_form_variables();

if (isset($_POST['CancelUpdate']) || isset($_POST['UpdateLine'])) {
	line_start_focus();
}

//---------------------------------------------------------------------------------------------------

start_form();



display_pr_header($_SESSION['PR']);
echo "<br>";

// display_pr_tables();
// echo "<br>";

display_pr_items($_SESSION['PR']);

start_table(TABLESTYLE2);

textarea_row(_("Remarks:"), 'Comments', null, 70, 4);

end_table(1);
//---------------------------------------------------------------------------------------------------

div_start('controls', 'items_table');
$process_txt = _("Place Request");
$update_txt = _("Update Request");
$cancel_txt = _("Exit");
// $canceled_txt = _("Cancel");

if ($_SESSION['PR']->order_has_items()) {
	if ($_SESSION['PR']->pr_no) {
		submit_center_first('Commit', $update_txt, '', 'default');
		// submit_center_first('CancelDoc', $canceled_txt, '', 'default');
	} else {
		submit_center_first('Commit', $process_txt, '', 'default');
	}
	submit_center_last('CancelRequest', $cancel_txt);
} else
	submit_center('CancelRequest', $cancel_txt, true, false, 'cancel');
div_end();

end_form();
end_page();
