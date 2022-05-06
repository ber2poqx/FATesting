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
$page_security = 'SA_DRAFTPOUPDATESTATUS';
$path_to_root = "..";
include_once($path_to_root . "/purchasing/includes/po_class.inc");

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
    $js .= get_js_open_window(900, 500);
if (user_use_date_picker())
    $js .= get_js_date_picker();
page(_($help_context = "Draft Purchase Order # " . $_GET['PONumber']), false, false, "", $js);
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
    $_SESSION['PO']->trans_type = ST_PURCHORDER;
    copy_from_cart();
    if (isset($_GET['branch_coy'])) {
        global $def_coy;
        $_SESSION["wa_current_user"]->company = $def_coy;
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
            _("Item Code"), _("Description"), _("Color Description - (Code)"), _("Requested"), _("QoH"), _("Undelivered PO"), _("Ordered"), _("Price"), _("Total")
        );
    } else {
        $th = array(
            _("Item Code"), _("Description"), _("Requested"), _("QoH"), _("Undelivered PO"), _("Ordered"), _("Price"), _("Total")
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

            //Modified by spyrax10 12 Apr 2022
            $qoh = get_qoh_on_date($ln_itm->stock_id, 0, null, 'new', $_GET['branch_coy']);
            $qoo = get_on_porder_qty($ln_itm->stock_id, $_SESSION['PO']->Location, $_GET['branch_coy']);

            qty_cell($ln_itm->quantity, false, $dec);
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

    label_row(_("Ordered On"), $po->orig_order_date);
    if (isset($_GET['branch_coy']))
        label_row(_("PR Reference #"), viewer_link($po->supp_ref, "purchasing/view/view_pr.php?trans_no=$po->supp_ref&branch_coy=". $_GET['branch_coy']));
    else
        label_row(_("PR Reference #"), get_trans_view_str(ST_PURCHREQUEST, $po->supp_ref));
    table_section(2);
    table_section(2);

    if (!isset($_POST['Location']))
        $_POST['Location'] = $po->Location;

    // locations_list_row(_("Deliver Into Location"), "Location", $_POST['Location']);
    if (isset($_GET['branch_coy'])) {
        set_global_connection($_GET['branch_coy']);
    }
    label_row(_("Deliver Into Location"), get_location_name($po->Location, $_GET['branch_coy']));
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
        label_row(_("Order Comments"), $po->Comments, "class='tableheader2'", "colspan=9");

    if (!is_company_currency($po->curr_code))
        exchange_rate_display(get_company_currency(), $po->curr_code, get_post('DefaultReceivedDate'));
    end_outer_table(1);
}

//-----------------------------------------------------------------------------
function can_proceed()
{

    if (!is_date_in_fiscalyear(Today())) {
        display_error(_("The Entered Date is OUT of FISCAL YEAR or is CLOSED for further data entry!"));
        return false;
    }

    return true;
}
//-----------------------------------------------------------------------------

if (isset($_POST['Approved']) && can_proceed()) {
    $poobj = new purch_order;
    $poobj->order_no = $_SESSION['PO']->order_no;
    $poobj->trans_type = ST_PURCHORDER;
    $poobj->draft_status = 1;
    $poobj->reference = $_SESSION['PO']->reference;
    if (isset($_POST['branch_coy'])) {
        set_global_connection($_POST['branch_coy']);
    }
    $order_no = update_po_draft_status($poobj);
    unset($_SESSION['PO']->line_items);
    unset($_SESSION['PO']);

    meta_forward($path_to_root . "/purchasing/po_branch.php?FilterBranch=" . $_POST['branch_coy']);
}

if (isset($_POST['Disapproved']) && can_proceed()) {
    $poobj = new purch_order;
    $poobj->order_no = $_SESSION['PO']->order_no;
    $poobj->trans_type = ST_PURCHORDER;
    $poobj->draft_status = 2;
    $poobj->reference = $_SESSION['PO']->reference;
    if (isset($_POST['branch_coy'])) {
        set_global_connection($_POST['branch_coy']);
    }
    $order_no = update_po_draft_status($poobj);
    unset($_SESSION['PO']->line_items);
    unset($_SESSION['PO']);

    meta_forward($path_to_root . "/purchasing/inquiry/po_search_completed.php?");
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
if (isset($_GET['branch_coy'])) {
    set_global_connection($_GET['branch_coy']);
}
if (check_status_po($_GET['PONumber']) == "Draft" || check_status_po($_GET['PONumber']) == "Disapproved") {
    submit_center_first('Approved', _("Approved"), '', 'default');
    submit_center_last('Disapproved', _("Disapproved"), '', 'default', ICON_DELETE);
}

end_form();

//--------------------------------------------------------------------------------------------------
end_page();
