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
$page_security = 'SA_SUPPLIERREORDERLEVEL';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");

add_js_file('budget.js');

page(_($help_context = "Supplier's Re-order Level Entry"));

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/admin/db/fiscalyears_db.inc");
include_once($path_to_root . "/purchasing/includes/db/supp_trans_db.inc");


check_db_has_suppliers(_("There are no Supplier's defined. Please define at least one Supplier before entering accounts."));

//------------------------------------------------------------------------------------

if (isset($_GET['supplier_id']))
    $_POST['supplier_id'] = $_GET['supplier_id'];
    
if (list_updated('supplier_id'))
{
    $Ajax->activate('show_heading');
    $Ajax->activate('reorders');
}
//------------------------------------------------------------------------------------
    
$action = $_SERVER['PHP_SELF'];
//-------------------------------------------------------------------------------------

    
if ($page_nested) $action .= "?supplier_id=".get_post('supplier_id');
        
start_form(false, false, $action);
        
if (!isset($_POST['supplier_id']))
    $_POST['supplier_id'] = get_global_supplier();
    
if (!$page_nested)
{
    echo "<center>" . _("Supplier:"). "&nbsp;";
    echo supplier_list('supplier_id', $_POST['supplier_id'], false, true);
    
    echo "<hr></center>";
}
else  br(2);

div_start('show_heading');
supplier_heading($_POST['supplier_id']);
br();
div_end();
                
if (isset($_POST['add']) || isset($_POST['delete']))
{
	begin_transaction();

	for ($i = 0, $da = $_POST['begin']; date1_greater_date2($_POST['end'], $da); $i++)
	{
		if (isset($_POST['add']))
			add_update_gl_budget_trans($da, $_POST['account'], $_POST['dim1'], $_POST['dim2'], input_num('amount'.$i));
		else
			delete_gl_budget_trans($da, $_POST['account'], $_POST['dim1'], $_POST['dim2']);
		$da = add_months($da, 1);
	}
	commit_transaction();

	if (isset($_POST['add']))
		display_notification_centered(_("The Budget has been saved."));
	else
		display_notification_centered(_("The Budget has been deleted."));

	$Ajax->activate('budget_tbl');
}
if (isset($_POST['submit']) || isset($_POST['update']))
	$Ajax->activate('budget_tbl');

//-------------------------------------------------------------------------------------

start_form();

if (db_has_stock_categories())
{
	start_table(TABLESTYLE2);
	fiscalyears_list_row(_("Fiscal Year:"), 'fyear', null);
	stock_categories_list_row(_("Category:"), 'category', null);
	
    
	submit_row('submit', _("Get"), true, '', '', true);
	end_table(1);
	div_start('budget_tbl');
	
	start_table(TABLESTYLE2);
	
	$th = array(_("Period"),_("Target (Qty)"), _("Target (Amount)"), _("Actual (Qty)"), _("Actual (Amount)"));
	table_header($th);
	
	$year = $_POST['fyear'];
	
	if (get_post('update') == '') {
		$fyear = get_fiscalyear($year);
		$_POST['begin'] = sql2date($fyear['begin']);
		$_POST['end'] = sql2date($fyear['end']);
	}
	
	hidden('begin');
	hidden('end');
	
	$total = $btotal = $ltotal = $qtytotal = $amttotal= 0;
	for ($i = 0, $date_ = $_POST['begin']; date1_greater_date2($_POST['end'], $date_); $i++)
	{
		start_row();
		if (get_post('update') == ''){
		    $_POST['amount'.$i] = number_format2(get_only_supplier_reorderlevel_from_to($date_, $date_, $_POST['account']), 0);
		    $_POST['qty'.$i] = number_format2(get_only_supplier_reorderlevel_from_to($date_, $date_, $_POST['account']), 0);
		    
		}

		label_cell($date_);
		qty_cells(null, 'qty'.$i, null, 15, null, 0);
		amount_cells(null, 'amount'.$i, null, 15, null, 0);
		
		$lamount = get_gl_trans_from_to(add_years($date_, -1), add_years(end_month($date_), -1), $_POST['account'], $_POST['dim1'], $_POST['dim2']);
		$total += input_num('amount'.$i);
		$ltotal += $lamount;
		label_cell(number_format2($lamount, 0), "nowrap align=right");
		label_cell(number_format2($lamount, 0), "nowrap align=right");
		$date_ = add_months($date_, 1);
		end_row();
	}
	start_row();
	label_cell("<b>"._("Total")."</b>");
	label_cell(number_format2($total, 0), 'align=right style="font-weight:bold"', 'Total');
	
	label_cell("<b>".number_format2($ltotal, 0)."</b>", "nowrap align=right");
	label_cell("<b>".number_format2($ltotal, 0)."</b>", "nowrap align=right");
	label_cell("<b>".number_format2($ltotal, 0)."</b>", "nowrap align=right");
	end_row();
	end_table(1);
	div_end();
	submit_center_first('update', _("Update"), '', null);
	submit('add', _("Save"), true, '', 'default');
	submit_center_last('delete', _("Delete"), '', true);
}
end_form();

end_page();

