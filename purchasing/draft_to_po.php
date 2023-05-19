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
$page_security = 'SA_DRAFTTOPO';
$path_to_root = "..";
include_once($path_to_root . "/purchasing/includes/po_class.inc");

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
    $js .= get_js_open_window(900, 500);
if (user_use_date_picker())
    $js .= get_js_date_picker();
$po_no = isset($_GET['PONumber']) ? $_GET['PONumber'] : '';
page(_($help_context = "Approved Draft Purchase Order # " . $po_no), false, false, "", $js);

//---------------------------------------------------------------------------------------------------------------
if (isset($_GET['AddedID']) && isset($_GET['PONumber'])) {
    $order_no = $_GET['AddedID'];
    $po_no = $_GET['PONumber'];
    $trans_type = ST_PURCHORDER;
    if (!isset($_GET['Updated']))
        display_notification_centered(_("Purchase Order has been entered"));
    else
        display_notification_centered(_("Purchase Order has been updated") . " #$order_no");
    if ($_GET['branch_coy']) {
        display_note(
            viewer_link(
                _("&View this order"),
                "purchasing/view/view_po.php?trans_no=$po_no&branch_coy=" . $_GET['branch_coy']
            ),
            0,
            1
        );
    } else {
        display_note(get_trans_view_str($trans_type, $po_no, _("&View this order")), 0, 1);
    }

    display_note(print_document_link($order_no, _("&Print This Order"), true, $trans_type), 0, 1);

    display_note(print_document_link($order_no, _("&Email This Order"), true, $trans_type, false, "printlink", "", 1));

    hyperlink_params("$path_to_root/purchasing/po_branch.php", _("Back to Purchase Order List"), "", true);

    // hyperlink_params($path_to_root . "/purchasing/po_receive_items.php", _("&Receive Items on this Purchase Order"), "PONumber=$order_no");

    // TODO, for fixed asset
    // hyperlink_params($_SERVER['PHP_SELF'], _("Enter &Another Purchase Order"), "NewOrder=yes");

    // hyperlink_no_params($path_to_root . "/purchasing/inquiry/po_search.php", _("Select An &Outstanding Purchase Order"));

    display_footer_exit();
}
//--------------------------------------------------------------------------------------------------

if ((!isset($_GET['PONumber']) || empty($_GET['PONumber'])) && !isset($_SESSION['PO'])) {
    die(_("This page can only be opened if a purchase order has been selected. Please select a purchase order first."));
}

if (isset($_GET['PONumber']) && !empty($_GET['PONumber'])) {
    if (isset($_GET['branch_coy'])) {
        $_POST['branch_coy'] = $_GET['branch_coy'];
        set_global_connection($_GET['branch_coy']);
        $_SESSION["wa_current_user"]->company = $_GET['branch_coy'];
    }

    create_new_po(ST_PURCHORDER, $_GET['PONumber'], false);
    $_SESSION['PO']->trans_type = ST_SUPPRECEIVE;
    // $_SESSION['PO']->reference = $Refs->get_next(
    //     ST_SUPPRECEIVE,
    //     array('date' => Today(), 'supplier' => $_SESSION['PO']->supplier_id)
    // );
    $_SESSION['PO']->reference = $_GET['PONumber'];
    copy_from_cart();
    if (isset($_GET['branch_coy'])) {
        global $def_coy;
        //modified by Albert 03/29/2023
        $coy = user_company();
        $db_branch_type = $db_connections[$coy]['type'];
        if($db_branch_type == 'DESM'){
            $db_coy = 1;
        }
        if($db_branch_type == 'DESM'){
            $_SESSION["wa_current_user"]->company = $db_coy;
        }else{
            $_SESSION["wa_current_user"]->company = $def_coy;
        }
        /**/
    }
}


//--------------------------------------------------------------------------------------------------

function update_status_items()
{
    div_start('po_items');
    start_table(TABLESTYLE, "colspan=7 width='90%'");
    //echo $_SESSION['PO']->category_id;
    if ($_SESSION['PO']->category_id == 14) {
        $th = array(
            _("Item Code"), _("Description"), _("Color Description - (Code)"), ("QoH"), _("Undelivered PO"), _("Ordered"), _("Price"), _("Total")
        );
    } else {
        $th = array(
            _("Item Code"), _("Description"), ("QoH"), _("Undelivered PO"), _("Ordered"), _("Price"), _("Total")
        );
    }
    table_header($th);

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

            $line_total = ($ln_itm->receive_qty * $ln_itm->price);
            $total += $line_total;

            label_cell($ln_itm->stock_id);
            label_cell($ln_itm->item_description);
            if ($_SESSION['PO']->category_id == 14)
                label_cell(get_color_description($ln_itm->color_code, $ln_itm->stock_id));
            $dec = get_qty_dec($ln_itm->stock_id);
            $qoh = get_qoh_on_date($ln_itm->stock_id, 0, null, 'new', $_GET['branch_coy']);//modified by Albert 05/13/2022
            set_global_connection();
            $qoo = get_on_porder_qty($ln_itm->stock_id, $_POST['Location']);
            qty_cell($qoh, false, 0);
            qty_cell($qoo, false, 0);
            qty_cell($ln_itm->quantity, false, $dec);

            amount_decimal_cell($ln_itm->price);
            amount_cell($line_total);
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

function update_status_header(&$po)
{
    global $Refs;

    start_outer_table(TABLESTYLE2, "width='80%'");

    table_section(1);
    label_row(_("Supplier"), $po->supplier_name);
    /*modified by Albert*/
    // label_row(_("Ordered On"), $po->orig_order_date);

    date_row(
        _("Ordered On"),
        'OrderDate',
        _('Date of order receive'),
        $po->order_no == 0,
        0,
        0,
        0,
        null,
        true
    );
    $po->orig_order_date = get_post('OrderDate');
    /*End by Albert */
    if (isset($_GET['branch_coy']))
        label_row(_("PR Reference #"), viewer_link($po->supp_ref, "purchasing/view/view_pr.php?trans_no=$po->supp_ref&branch_coy=". $_GET['branch_coy']));
    else
        label_row(_("PR Reference #"), get_trans_view_str(ST_PURCHREQUEST, $po->supp_ref));
    table_section(2);

    if (!isset($_POST['Location']))
        $_POST['Location'] = $po->Location;

    $company = get_company_prefs();

    $branch_code = $company["branch_code"];
    if (!isset($_POST['Location'])) {
        $_POST['Location'] = $branch_code;
    }

    if ($_POST['Location'] != "HO") {
        label_row(_("Deliver Into Location"), get_location_name($_POST['Location'], $_GET['branch_coy']));
        hidden('Location');
    } else {
        locations_list_row(_("Deliver Into Location"), "Location", $_POST['Location']);
    }

    if (!isset($_POST['DefaultReceivedDate']))
        $_POST['DefaultReceivedDate'] = new_doc_date();

    // date_row(_("Date Items Received"), 'DefaultReceivedDate', '', true, 0, 0, 0, '', true);
    label_row(_("Category"), get_category_name($po->category_id));
    label_row(_("Purchase Type"), $po->purch_type_id == 1 ? "HO" : "LOCAL");
    table_section(3);
    $served_status = "";
    switch ($po->served_status) {
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
    label_row(_("Served Status"), $served_status);
    label_row(_("Delivery Address"), $po->delivery_address);

    if ($po->Comments != "")
        label_row(_("Order Comments: "), $po->Comments);

    if (!is_company_currency($po->curr_code))
        exchange_rate_display(get_company_currency(), $po->curr_code, get_post('DefaultReceivedDate'));
    end_outer_table(1);
}


if (isset($_POST['DraftToPO'])) {
    if (isset($_POST['branch_coy'])) {
        set_global_connection($_POST['branch_coy']);
    }
    $_SESSION['PO']->Location = $_POST['Location'];
    $order_no = draft_to_po($_SESSION['PO']);
    $po_no = $_SESSION['PO']->reference;
    unset($_SESSION['PO']->line_items);
    unset($_SESSION['PO']);
    if (isset($_POST['branch_coy']))
        meta_forward($_SERVER['PHP_SELF'], "AddedID=$order_no&PONumber=$po_no&branch_coy=" . $_POST['branch_coy']);
    else
        meta_forward($_SERVER['PHP_SELF'], "AddedID=$order_no&PONumber=$po_no");
}

start_form();
hidden('branch_coy');
update_status_header($_SESSION['PO'], true);
display_heading(_("Purchase Order Items"));
update_status_items();

echo '<br>';
start_table(TABLESTYLE2);

// textarea_row(_("Remarks:"), 'Comments', null, 70, 4);

end_table(1);

submit_center_first('DraftToPO', _("Post"), '', 'default');

end_form();

//--------------------------------------------------------------------------------------------------
end_page();
