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
include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/purchasing/includes/db/pr_db.inc");
include_once($path_to_root . "/purchasing/includes/db/suppliers_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

set_page_security( @$_SESSION['PR']->trans_type,
	array(	ST_PURCHREQUEST => 'SA_PURCHASEREQUEST'),
	array(	'NewRequest' => 'SA_PURCHASEREQUEST',
			'AddedID' => 'SA_PURCHASEREQUEST')
);

$js = '';
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

$_SESSION['page_title'] = _($help_context = "Purchase Request");

if (isset($_GET['order_number']))
{
	$_POST['order_number'] = $_GET['order_number'];
}

page($_SESSION['page_title'], false, false, "", $js);

//-----------------------------------------------------------------------------------
// Ajax updates
//
if (get_post('SearchOrders')) 
{
	$Ajax->activate('pr_tbl');
} elseif (get_post('_order_number_changed')) 
{
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

	$Ajax->activate('pr_tbl');
}

// Header Search

start_form();

start_table(TABLESTYLE_NOBORDER);
start_row();
ahref(_("New Purchase Request"), "pr_entry_items.php?NewRequest=Yes");
ref_cells(_("PR#:"), 'order_number', '',null, '', true);
submit_cells('SearchOrders', _("Search"),'',_('Select documents'), 'default');
end_row();
end_table();



//---------------------------------------------------------------------------------------------
function trans_view($trans)
{
	return get_trans_view_str(ST_PURCHREQUEST, $trans["reference"]);
}

function edit_link($row) 
{
	return trans_editor_link(ST_PURCHREQUEST, $row["pr_no"]);
}

function prt_link($row)
{
	return print_document_link($row['pr_no'], _("Print"), true, ST_PURCHREQUEST, ICON_PRINT);
}

function receive_link($row) 
{
  return pager_link( _("Copy to PO"),
	"/purchasing/po_receive_items.php?PONumber=" . $row["reference"], ICON_RECEIVE);
}

function check_overdue($row)
{
	return $row['OverDue']==1;
}


//---------------------------------------------------------------------------------------------

//figure out the sql required from the inputs available
$sql = get_sql_for_pr_search(
    // get_post('OrdersAfterDate'), 
    // get_post('OrdersToDate'), 
    // get_post('supplier_id'), 
    // get_post('StockLocation'),
    // $_POST['order_number'], 
    // get_post('SelectStockFromList')
);

//$result = db_query($sql,"No orders were returned");

/*show a table of the orders returned by the sql */
$cols = array(
		_("PR#") => array(
			'fun'=>'trans_view', 
			'ord'=>''
		),
		// _("Transaction No."), 
		_("Supplier"),
        _("PR Date"),
		_("Required Date"),
		_("Remarks"),
		array('insert'=>true, 'fun'=>'receive_link')
);

$table =& new_db_pager('pr_tbl', $sql, $cols);
// $table->set_marker('check_overdue', _("Marked orders have overdue items."));

$table->width = "80%";

display_db_pager($table);

end_form();
end_page();