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
$page_security = 'SA_RECEIVECONSIGN';
include_once($path_to_root . "/purchasing/includes/consign_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/purchasing/includes/db/consignment_db.inc");
include_once($path_to_root . "/purchasing/includes/db/suppliers_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

set_page_security(
    @$_SESSION['RCON']->trans_type,
    array(
        ST_RECEIVECONSIGN => 'SA_RECEIVECONSIGN'
    ),
    array(
        'NewConsign' => 'SA_RECEIVECONSIGN',
        'ModifyConsignNumber' => 'SA_RECEIVECONSIGN',
        'AddedID' => 'SA_RECEIVECONSIGN'
    )
);

$js = '';
if ($SysPrefs->use_popup_windows)
    $js .= get_js_open_window(900, 500);
if (user_use_date_picker())
    $js .= get_js_date_picker();

if (isset($_GET['ModifyConsignNumber']) && is_string($_GET['ModifyConsignNumber'])) {

    $_SESSION['page_title'] = _($help_context = "Modify Receive Consignment Item #") . $_GET['ModifyConsignNumber'];
    create_new_consign(ST_RECEIVECONSIGN, $_GET['ModifyConsignNumber']);
    consign_copy_from_cart();
} elseif (isset($_GET['NewConsign'])) {
    $_SESSION['page_title'] = _($help_context = "Receive Consignment Item");
    create_new_consign(ST_RECEIVECONSIGN, (string) NULL);
    consign_copy_from_cart();
}

page($_SESSION['page_title'], false, false, "", $js);

if (isset($_GET['AddedID'])) {
    $consign_no = $_GET['AddedID'];
    $trans_type = ST_RECEIVECONSIGN;

    if (!isset($_GET['Updated']))
        display_notification_centered(_("Receive Consignment Item has been entered"));
    else
        display_notification_centered(_("Receive Consignment Item has been updated") . " #$consign_no");
    display_note(get_trans_view_str($trans_type, $consign_no, _("&View this Consignment")), 0, 1);

    display_note(viewer_link("Serial Item Entry","purchasing/consignment_serial_details.php?serialid=".$consign_no),0,1);
    
    hyperlink_params($_SERVER['PHP_SELF'], _("Enter &Another Receive Consignment Item"), "NewConsign=yes");

    display_footer_exit();
}

//---------------------------------------------------------------------------------------------------
//
// Add PR
//
function add_consignment_trans($cart)
{
    global $Refs, $type_shortcuts;

    $consign_no = add_consignment($cart);
    $cart->consign_no = $consign_no;

    if ($cart->trans_type == ST_RECEIVECONSIGN)
        return $consign_no;

    commit_transaction();
}


function pr_line_start_focus()
{
    global     $Ajax;
    $Ajax->activate('items_table');
    set_focus('_stock_id_edit');
}

function line_start_focus()
{
    global     $Ajax;

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
    if ($_SESSION['RCON']->some_already_received($line_no) == 0) {
        $_SESSION['RCON']->remove_from_order($line_no);
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
    if (($_SESSION['RCON']->consign_no != 0) &&
        $_SESSION['RCON']->any_already_received() == 1
    ) {
        display_error(_("This order cannot be cancelled because some of it has already been received.")
            . "<br>" . _("The line item quantities may be modified to quantities more than already received. prices cannot be altered for lines that have already been received and quantities cannot be reduced below the quantity already received."));
        return;
    }

    $fixed_asset = $_SESSION['RCON']->fixed_asset;

    if ($_SESSION['RCON']->consign_no != 0)
        delete_po($_SESSION['RCON']->consign_no);
    else {
        unset($_SESSION['RCON']);

        if ($fixed_asset)
            meta_forward($path_to_root . '/index.php', 'application=assets');
        else
            meta_forward($path_to_root . '/index.php', 'application=AP');
    }

    $_SESSION['RCON']->clear_items();
    $_SESSION['RCON'] = new receive_consignment;

    display_notification(_("This Receive Consignment Item has been cancelled."));

    hyperlink_params($path_to_root . "/purchasing/po_entry_items.php", _("Enter a new Receive Consignment Item"), "NewOrder=Yes");
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
    //   if ($_SESSION['RCON']->trans_type == ST_RECEIVECONSIGN && !is_date($_POST['req_del_date'])){
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
            $_SESSION['RCON']->line_items[$_POST['line_no']]->qty_inv > input_num('qty') ||
            $_SESSION['RCON']->line_items[$_POST['line_no']]->qty_ordered > input_num('qty')
        ) {
            display_error(_("You are attempting to make the quantity ordered a quantity less than has already been invoiced or received.  This is prohibited.") .
                "<br>" . _("The quantity received can only be modified by entering a negative receipt and the quantity invoiced can only be reduced by entering a credit note against this item."));
            set_focus('qty');
            return;
        }

        $_SESSION['RCON']->update_consignment_item(
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

function handle_consign_add_new_item()
{
    $allow_update = check_data();

    if ($allow_update == true) {
        if (count($_SESSION['RCON']->line_items) > 0) {
            foreach ($_SESSION['RCON']->line_items as $consign_item) {
                /* do a loop round the items on the order to see that the item
				  is not already on this order */
                if (($consign_item->stock_id == $_POST['stock_id'])) {
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
                $_SESSION['RCON']->add_to_consignment(
                    count($_SESSION['RCON']->line_items),
                    $_POST['stock_id'],
                    input_num('qty'),
                    get_post('stock_id_text'), //$myrow["description"], 
                    input_num('price'),
                    '', // $myrow["units"], (retrived in cart)
                    $_SESSION['RCON']->trans_type == ST_RECEIVECONSIGN ? '' : '',
                    0,
                    0,
                    (get_post("category") == 14) ? $_POST['color_code'] : ''
                );

                unset_form_variables();
                $_POST['stock_id']    = "";
            } else {
                display_error(_("The selected item does not exist or it is a kit part and therefore cannot be purchased."));
            }
        } /* end of if not already on the order and allow input was true*/
    }
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

    if (!$_SESSION['RCON']->consign_no) {
        if (!check_reference(get_post('reference'), $_SESSION['RCON']->trans_type)) {
            set_focus('reference');
            return false;
        }
    }

    return true;
}

function handle_commit_request()
{
    $cart = &$_SESSION['RCON'];
    consign_copy_to_cart();
    new_doc_date($cart->orig_order_date);

    if (empty($cart->consign_no)) {
        $consign_no = add_consignment_trans($cart);
        unset($_SESSION['RCON']);
        if ($cart->trans_type == ST_RECEIVECONSIGN)
            meta_forward($_SERVER['PHP_SELF'], "AddedID=$consign_no");
    } else {
        $consign_no = update_consignment($cart);
        unset($_SESSION['RCON']);
        meta_forward($_SERVER['PHP_SELF'], "AddedID=$consign_no&Updated=1");
    }
}
//---------------------------------------------------------------------------------------------------

if (get_post("category")) {
    $Ajax->activate("items_table");
}

if (isset($_POST['update'])) {
    consign_copy_to_cart();
    $Ajax->activate('items_table');
}

$id = find_submit('Delete');
if ($id != -1)
    handle_delete_item($id);

if (isset($_POST['Commit'])) {
    handle_commit_request();
}
if (isset($_POST['UpdateLine']))
    handle_update_item();

if (isset($_POST['EnterLine'])) {
    handle_consign_add_new_item();
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



display_consign_header($_SESSION['RCON']);
echo "<br>";

// display_pr_tables();
// echo "<br>";

display_consign_items($_SESSION['RCON']);

start_table(TABLESTYLE2);

textarea_row(_("Remarks:"), 'Comments', null, 70, 4);

end_table(1);
//---------------------------------------------------------------------------------------------------

div_start('controls', 'items_table');
$process_txt = _("Receive");
$update_txt = _("Update");
$cancel_txt = _("Exit");
// $canceled_txt = _("Cancel");

if ($_SESSION['RCON']->order_has_items()) {
    if ($_SESSION['RCON']->consign_no) {
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
