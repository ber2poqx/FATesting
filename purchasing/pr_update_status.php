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
$page_security = 'SA_DRAFTPRUPDATESTATUS';
$path_to_root = "..";
include_once($path_to_root . "/purchasing/includes/pr_class.inc");

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
    $js .= get_js_open_window(900, 500);
if (user_use_date_picker())
    $js .= get_js_date_picker();
page(_($help_context = "Draft Purchase Request # " . $_GET['PRNumber']), false, false, "", $js);
//--------------------------------------------------------------------------------------------------
if ((!isset($_GET['PRNumber']) || empty($_GET['PRNumber'])) && !isset($_SESSION['PR'])) {
    die(_("This page can only be opened if a purchase request has been selected. Please select a purchase request first."));
}

if (isset($_GET['PRNumber']) && !empty($_GET['PRNumber'])) {
    create_new_pr(ST_PURCHREQUEST, $_GET['PRNumber'], false);
    $_SESSION['PR']->trans_type = ST_PURCHREQUEST;
    pr_copy_from_cart();
}

function update_status_items()
{
    div_start('pr_items');
    start_table(TABLESTYLE, "colspan=7 width='90%'");
    if ($_SESSION['PR']->category_id == 14) {
        $th = array(
            _("Item Code"), _("Description"), _("Color Description - (Code)"), _("QoH"), _("Requested"), _("Undelivered PO"), _("Ordered")
        );
    } else {
        $th = array(
            _("Item Code"), _("Description"), _("QoH"), _("Requested"), _("Undelivered PO"), _("Ordered")
        );
    }
    table_header($th);

    /*show the line items on the order with the quantity being received for modification */

    $total = 0;
    $k = 0; //row colour counter

    if (count($_SESSION['PR']->line_items) > 0) {
        foreach ($_SESSION['PR']->line_items as $ln_itm) {

            alt_table_row_color($k);
            label_cell($ln_itm->stock_id);
            label_cell($ln_itm->item_description);
            if ($_SESSION['PR']->category_id == 14)
                label_cell(get_color_description($ln_itm->color_code, $ln_itm->stock_id));
            $dec = get_qty_dec($ln_itm->stock_id);
            set_global_connection();
            $qoh = get_qoh_on_date($ln_itm->stock_id, 0);
            qty_cell($qoh, false, 0);
            qty_cell($ln_itm->quantity, false, $dec); // Requested

            //Added by spyrax10
            $qoo = get_on_porder_qty($ln_itm->stock_id, $_SESSION['PR']->Location);
            
            qty_cell($qoo, false, $dec); //Undelivered
            qty_cell($ln_itm->qty_ordered, false, $dec); //Ordered
            //
            end_row();
        }
    }
    end_table();
    div_end();
}

function update_status_header(&$pr)
{
    global $Refs;

    start_outer_table(TABLESTYLE2, "width='80%'");

    table_section(1);
    label_row(_("PR #"), $pr->reference);
    label_row(_("Supplier"), $pr->supplier_name);
    label_row(_("Requested On"), $pr->orig_order_date);
    table_section(2);

    if (!isset($_POST['Location']))
        $_POST['Location'] = $pr->Location;

    // locations_list_row(_("Deliver Into Location"), "Location", $_POST['Location']);

    if (!isset($_POST['DefaultReceivedDate']))
        $_POST['DefaultReceivedDate'] = new_doc_date();

    // date_row(_("Date Items Received"), 'DefaultReceivedDate', '', true, 0, 0, 0, '', true);
    label_row(_("Category"), get_category_name($pr->category_id));
    label_row(_("Purchase Type"), $pr->purch_type_id == 1 ? "HO" : "LOCAL");
    table_section(3);
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
    label_row(_("Served Status"), $served_status);
    label_row(_("Delivery Address"), $pr->delivery_address);

    if ($pr->Comments != "")
        label_row(_("Order Comments"), $pr->Comments, "class='tableheader2'", "colspan=9");

    if (!is_company_currency($pr->curr_code))
        exchange_rate_display(get_company_currency(), $pr->curr_code, get_post('DefaultReceivedDate'));
    end_outer_table(1);
}


if (isset($_POST['Approved'])) {
    $probj = new purch_request;
    $probj->pr_no = $_SESSION['PR']->pr_no;
    $probj->trans_type = ST_PURCHREQUEST;
    $probj->draft_status = 1;
    $probj->reference = $_SESSION['PR']->reference;

    $pr_no = update_pr_draft_status($probj);
    unset($_SESSION['PR']->line_items);
    unset($_SESSION['PR']);

    meta_forward($path_to_root . "/purchasing/purchase_request.php?");
}

if (isset($_POST['Disapproved'])) {
    $probj = new purch_request;
    $probj->pr_no = $_SESSION['PR']->pr_no;
    $probj->trans_type = ST_PURCHREQUEST;
    $probj->draft_status = 2;
    $probj->reference = $_SESSION['PR']->reference;

    $pr_no = update_pr_draft_status($probj);
    unset($_SESSION['PR']->line_items);
    unset($_SESSION['PR']);

    meta_forward($path_to_root . "/purchasing/purchase_request.php?");
}

start_form();

update_status_header($_SESSION['PR'], true);
display_heading(_("Purchase Request Items"));
update_status_items();

echo '<br>';
start_table(TABLESTYLE2);

// textarea_row(_("Remarks:"), 'Comments', null, 70, 4);

end_table(1);

if (check_status_pr($_GET['PRNumber']) == "Draft" || check_status_pr($_GET['PRNumber']) == "Disapproved") {
    submit_center_first('Approved', _("Approved"), '', 'default');
    submit_center_last('Disapproved', _("Disapproved"), '', 'default', ICON_DELETE);
}

end_form();

//--------------------------------------------------------------------------------------------------
end_page();
