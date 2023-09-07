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
$page_security = 'SA_SRREPO_INQ'; //Modified by spyrax10 13 Jul 2022
include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

$js = '';
if ($SysPrefs->use_popup_windows)
    $js .= get_js_open_window(900, 500);
if (user_use_date_picker())
    $js .= get_js_date_picker();
$_SESSION['page_title'] = _( "Sales Return Replacement Repossessed");

page($_SESSION['page_title'], false, false, "", $js);

//-----------------------------------------------------------------------
start_form();
start_table(TABLESTYLE_NOBORDER);
start_row();
// ahref(_("New Sales Return Replacement"), "sales_return_replacement_entry.php?NewSalesReturn=Yes");
ref_cells(_("SR #:"), 'sr_number', '', null, '', true);
date_cells(_("From:"), 'fromDate', '', null, -user_transaction_days());
date_cells(_("To:"), 'toDate', '', null, 1);
customer_list_cells(_("Select Customer: "), 'customer_id', null, true, true);
// value_type_list(_("Type: "), 'item_type', 
//     array(
//         1 => 'repo', 
//         0 => 'new' 
//     ), '', null, true, _('All Types'), true
// );
submit_cells('SearchSalesReturn', _("Search"), '', _('Select documents'), 'default');
end_row();
end_table();

function trans_view($trans)
{
    return get_trans_view_str(ST_SALESRETURN, $trans["trans_no"], $trans["reference"]);
}

function trans_view_ref($trans)
{
    return get_trans_view_str($trans["trans_type_ref"], $trans["trans_no_ref"], $trans["trans_ref"]);
}

function total_payable_amount($row)
{
    return price_format($row["total_payable"]);
}

function total_receivable_amount($row)
{
    return price_format($row["total_receivable"]);
}

function total_prev_lcp_amount($row)
{
    return price_format($row["total_prev_lcp"]);
}

function total_new_lcp_amount($row)
{
    return price_format($row["total_new_lcp"]);
}

function total_prev_cost_amount($row)
{
    return price_format($row["total_prev_cost"]);
}

function total_new_cost_amount($row)
{
    return price_format($row["total_new_cost"]);
}

//Added by spyrax10
function ar_balance($row)
{
    return price_format($row["ar_balance"]);
}
//

function customer_name($row)
{
    return get_customer_name($row["debtor_no"]);
}

function gl_view($row)
{
    return get_gl_view_str(ST_SALESRETURN, $row["trans_no"]);
}

// Added by Prog6 7/08/2021
function print_DR_replacement($row)
{
    $print_lnk = '';
    if ($_SESSION["wa_current_user"]->can_access_page('SA_PRINT_SITERM')) {
        $print_lnk = pager_link(_("Print DR Replacement"), "/reports/Sales_replacement_delivery.php?trans_no=" . $row["trans_no"], ICON_PRINT);
    }

    return $print_lnk;
}

function sales_return_replacement($row)
{
    return done_check_qty_return_invoice($row["reference"]) ? '' : pager_link(
        _("Sales Return"),
        "/sales/sales_return_replacement_entry.php?NewSalesReturn=" . $row["trans_no"] . "&& Filter_type=" . ST_SALESRETURN,
        ICON_CREDIT
    );
}
function return_defective($row)
{
    if($row['complimentary_status'] == 0){
    
        return $row['remarks'] <> 'defective unit' ? '' : pager_link(
            _("Complimentary"),
            "/inventory/complimentary_items.php?NewSalesReturn=" . $row["trans_no"] . "&&Filter_type=" . ST_SALESRETURN."&&Category=".$row["category_id"],
            ICON_DOC
        );
    }else{
        return null;
    }
}

// Retrieve Sales Return Replacement
$sql = get_sales_return_replacement( get_post('sr_number'), 1, get_post('customer_id'), date2sql(get_post('fromDate')),date2sql(get_post('toDate')));
$cols = array(
    _("Trans #") => array('align' => 'right'),
    // _("Status"), //added by spyrax10
    _("Sales Return #") => array('fun' => 'trans_view', 'ord' => '',),
    _("Customer") => array('fun' => 'customer_name'),
    _("Date Returned") => array('name' => 'tran_date', 'type' => 'date', 'ord' => 'desc'),
    _("Trans Ref #") => array('fun' => 'trans_view_ref', 'ord' => '',),
    // _("DR Ref #"),
    _("A/P Amount") => array('align' => 'right', 'fun' => 'total_payable_amount'),
    _("A/R Amount") => array('align' => 'right', 'fun' => 'total_receivable_amount'),
    // _("Total Prev LCP") => array('align' => 'right', 'fun' => 'total_prev_lcp_amount'),
    // _("Total New LCP") => array('align' => 'right', 'fun' => 'total_new_lcp_amount'),
    // _("Total Prev Cost") => array('align' => 'right', 'fun' => 'total_prev_cost_amount'),
    // _("Total New Cost") => array('align' => 'right', 'fun' => 'total_new_cost_amount'),
    _("Total Balance") => array('align' => 'right', 'fun' => 'ar_balance'),
    array('insert' => true, 'fun' => 'gl_view'),
    array('insert' => true, 'fun' => 'print_DR_replacement'), // Added by Prog6 7/08/2021
    array('insert' => true, 'fun' => 'sales_return_replacement'),
    array('insert' => true, 'fun' => 'return_defective'),
);
$table = &new_db_pager('sales_return_tbl', $sql, $cols, null, null);
$table->width = "90%";

display_db_pager($table);

end_form();
end_page();
