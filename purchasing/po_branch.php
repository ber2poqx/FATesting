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
$page_security = 'SA_PO_BRANCH';
$path_to_root = "..";
include_once($path_to_root . "/purchasing/includes/po_class.inc");
include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
    $js .= get_js_open_window(900, 500);
if (user_use_date_picker())
    $js .= get_js_date_picker();
page(_($help_context = "Search Purchase Orders"), false, false, "", $js);

//---------------------------------------------------------------------------------------------

function trans_view($trans)
{
    global $path_to_root;
    $param1 = $trans["reference"];
    $param2 = $_POST['selected_po_branch'];
    return viewer_link($trans["reference"], "purchasing/view/view_po.php?trans_no=$param1&branch_coy=$param2");
}

function trans_ref_view($trans)
{
    global $path_to_root;
    $param1 = $trans["trans_ref"];
    $param2 = $_POST['selected_po_branch'];
    if ($trans["is_consign"] == "Non-Consignment")
        return viewer_link($trans["trans_ref"], "purchasing/view/view_pr.php?trans_no=$param1&branch_coy=$param2");
    else
        return viewer_link($trans["trans_ref"], "purchasing/view/view_consignment.php?trans_no=$param1&branch_coy=$param2");
}

function consign_view($trans)
{
    return get_trans_view_str(ST_RECEIVECONSIGN, $trans["consign_no"]);
}

function edit_link($row)
{
    global $page_nested;

    return $page_nested ? '' : ($row['Status'] == "Draft" || $row['Status'] == "Disapproved" ? trans_editor_link(ST_PURCHORDER, $row["reference"]) : '');
}

function prt_link($row)
{
    //Modified by spyrax10 13 Jul 2022
    if ($_SESSION["wa_current_user"]->can_access_page('SA_PO_PRINT')) {
        return $row['Status'] == "Draft" || $row['Status'] == "Disapproved" || $row['Status'] == "Approved" ? '' :
            print_document_link($row['order_no'], _("Print"), true, ST_PURCHORDER, ICON_PRINT
        );
    }
    else {
        return null;
    }
    
}

function post_po($row)
{
    global $page_nested;

    //Modified by spyrax10 13 Jul 2022
    if ($_SESSION["wa_current_user"]->can_access_page('SA_PURCHASEORDER')) {
        return $page_nested ? '' : ($row['Status'] == "Approved" ? pager_link(
            _("Draft to PO"),
            "/purchasing/draft_to_po.php?PONumber=" . $row["reference"]
                . "&branch_coy=" . $_POST['selected_po_branch'],
            ICON_RECEIVE
        ) : '');
    }
    else {
        return null;
    }
    //
}
//Added by Albert 07/02/2022
function close_po($row)
{
    global $page_nested;

    if ($_SESSION["wa_current_user"]->can_access_page('SA_POCLOSESTATUS')) {
        return $page_nested ? '' : ($row['Status'] == "Open" || $row['Status'] == "Partially Received" ? pager_link(
            _("Close PO"),
            "/purchasing/po_close_status.php?PONumber=" . $row["reference"]
                . "&branch_coy=" . $_POST['selected_po_branch'],
            ICON_CANCEL
        ) : '');
    }
    else {
        return null;
    }
}

function update_status_link($row)
{
    global $page_nested;

    //Modified by spyrax10 13 Jul 2022
    if ($_SESSION["wa_current_user"]->can_access_page('SA_DRAFTTOPO')) {
        return $page_nested ||
        $row['Status'] == "Open" ||
        $row['Status'] == "Approved" ||
        $row['Status'] == "Partially Received" ||
        $row['Status'] == "Disapproved" || //Added by spyrax10
        $row['Status'] == "Closed" ? $row["Status"] :
        pager_link(
            $row['Status'],
            "/purchasing/po_update_status.php?PONumber=" . $row["reference"]
                . "&branch_coy=" . $_POST['selected_po_branch'],
            false
        );
    }
    else {
        return $row['Status'];
    }
    //
   
}

function change_status($row)
{
    $_POST['draft_status'] = $row['Status'];
    return draft_status_list("draft_status", null, true);
}

if (isset($_GET['order_number'])) {
    $_POST['order_number'] = $_GET['order_number'];
}

if (isset($_GET['FilterBranch']))
    $_POST['selected_po_branch'] = $_GET['FilterBranch'];

//-----------------------------------------------------------------------------------
// Ajax updates
//
if (get_post('SearchOrders')) {
    $Ajax->activate('orders_tbl');
} elseif (get_post('_order_number_changed')) {
    $disable = get_post('order_number') !== '';

    $Ajax->addDisable(true, 'OrdersAfterDate', $disable);
    $Ajax->addDisable(true, 'OrdersToDate', $disable);
    $Ajax->addDisable(true, 'StockLocation', $disable);
    $Ajax->addDisable(true, '_SelectStockFromList_edit', $disable);
    $Ajax->addDisable(true, 'SelectStockFromList', $disable);

    if ($disable) {
        $Ajax->addFocus(true, 'order_number');
    } else
        $Ajax->addFocus(true, 'OrdersAfterDate');

    $Ajax->activate('orders_tbl');
}
//---------------------------------------------------------------------------------------------

start_form();

start_table(TABLESTYLE_NOBORDER);

start_row();
branch_company_list_row(_('Branch: '), 'selected_po_branch', true, false, false);
ref_cells(_("PO #: "), 'order_number', '', null, '', true);
ref_cells(_("PR #: "), 'pr_no', '', null, '', true);

date_cells(_("Date From: "), 'OrdersAfterDate', '', null, -user_transaction_days());
date_cells(_("Date To: "), 'OrdersToDate');

// locations_list_cells(_("Location: "), 'StockLocation', null, true);
end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

stock_items_list_cells(_("Item: "), 'SelectStockFromList', null, true);
stock_categories_list_cells(_("Category:"), 'category_id', null, ("All Categories"), true, false, false);

//Modified by spyrax10
if (!$page_nested)
    supplier_list_cells(_("Supplier: "), 'supplier_id', null, true, true, true, false, false, null);

value_type_list(
    _("Status: "),
    'stat_type',
    array(
        'Draft',
        'Approved',
        'Disapproved',
        'Open',
        'Partially Received',
        'Closed'
    ),
    '',
    null,
    true,
    _('All Status Types')
);

check_cells(_('Also closed:'), 'also_closed', check_value('also_closed'));

submit_cells('SearchOrders', _("Search"), '', _('Select documents'), 'default');
end_row();
end_table(1);

//---------------------------------------------------------------------------------------------
set_global_connection($_POST['selected_po_branch']);
$sql = get_sql_for_po_search_completed(
    get_post('OrdersAfterDate'),
    get_post('OrdersToDate'),
    get_post('supplier_id'),
    get_post('StockLocation'),
    get_post('order_number'),
    get_post('SelectStockFromList'),
    get_post('also_closed'),
    get_post('pr_no'),
    //Added by spyrax10
    get_post('category_id'),
    get_post('stat_type')
);

$cols = array(
    _("Trans #"),
    _("Category"),
    _("PO #") => array(
        'fun' => 'trans_view'
    ),
    _("Status") => array('insert' => true, 'fun' => 'update_status_link'),
    'dummy' => 'skip',
    _("Supplier"),
    _("Purchase Type"),
    _("Is Consignment"),
    _("Served Status"),
    _("Location"),
    _("Trans Reference #") => array('fun' => 'trans_ref_view', 'ord' => '', 'align' => 'right'),
    _("Order Date") => array('name' => 'ord_date', 'type' => 'date', 'ord' => 'desc'),
    _("Currency") => array('align' => 'center'),
    _("Order Total") => 'amount',
    // array('insert' => true, 'fun' => 'edit_link'),
    array('insert' => true, 'fun' => 'prt_link'),
    array('insert' => true, 'fun' => 'post_po'),
    array('insert' => true, 'fun' => 'close_po')

);

if (get_post('StockLocation') != ALL_TEXT) {
    $cols[_("Location")] = 'skip';
}

//---------------------------------------------------------------------------------------------------

$table = &new_db_pager('orders_tbl', $sql, $cols, null, null, 25);

$table->width = "98%";

display_db_pager($table);

unset($_POST['draft_status']);

end_form();
end_page();
