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
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");
add_access_extensions();

$js = "";
if (user_use_date_picker())
	$js .= get_js_date_picker();
page(_($help_context = "Serial Items Entries"), false, false, "", $js);

//page(_($help_context = "Serial Items Entries"));

include_once($path_to_root . "/modules/serial_items/includes/modules_db.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/ui/ui_lists.inc");

simple_page_mode(true);
//-----------------------------------------------------------------------------------
if(isset($_GET['serialid'])) {
	$req = get_requisition($_GET['serialid']);
	if (!$req || $req['completed'])
	{
		display_error(sprintf(_("Requisition #%d is already completed or does not exists."), $_GET['serialid']));
		submenu_option(_( "Entry &New Requisition."), '/modules/requisitions/requisitions.php');
		display_footer_exit();
	}
	if(isset($_GET['complete']) && $_GET['complete'] == 'yes') {
			complete_requisition($_GET['serialid']);
			display_notification(sprintf(_("Requisition #%d has been completed."), $_GET['serialid']));
			submenu_option(_( "Entry &New Requisition."), '/modules/requisitions/requisitions.php');
			display_footer_exit();
	}
	else
	$_POST['serialid'] = $_GET['serialid'];
}
if(isset($_GET['itemserialid'])){
	$_POST['itemserialid'] = $_GET['itemserialid'];
}
if(isset($_GET['grnitemsid'])){
	$_POST['grnitemsid'] = $_GET['grnitemsid'];
}
if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{

	//initialise no input errors assumed initially before we test
	$input_error = 0;

	if (strlen($_POST['serialid']) == 0) 
	{
		$input_error = 1;
		display_error(_("The GRN No of use cannot be empty.".$selected_id));
		set_focus('name');
	}
	if (strlen($_POST['order_quantity']) == 0) 
	{
		$input_error = 1;
		display_error(_("The Quantity be empty.".$selected_id));
		set_focus('rate');
	}

	if ($input_error != 1) 
	{
    	if ($selected_id != -1) 
    	{
    		update_requisition_detail($selected_id, $_POST['item_code'], $_POST['purpose'], $_POST['order_quantity'], input_num('estimate_price'));
			display_notification(_('Selected requisition details has been updated.'));
    	} 
    	else 
    	{
    		add_serialitems_detail($_POST['serialid'], $_POST['itemserialid'], $_POST['lot_no'], $_POST['order_quantity'],$_POST['chasis_no']);
			display_notification(_('New Serial Items has been added'));
    	}
    	
		$Mode = 'RESET';
	}
} 

//-----------------------------------------------------------------------------------

if ($Mode == 'Delete')
{
	delete_requisition_detail($selected_id);
	display_notification(_('Selected GRN detail has been deleted'));

	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	$selected_id = -1;
	$sav = get_post('show_inactive');
	$serialid = $_POST['serialid'];
	unset($_POST);
	$_POST['serialid'] = $serialid;
}
//-----------------------------------------------------------------------------------

$result = get_one_requisition(get_post('serialid'));

start_table(TABLESTYLE, "width=70%");

$th = array(_("GRN#"),_("Item Code"), _("Description"), _("Ordered"), _("Unit"), _("Price"), _("Delivery Date"), _("Line Total"),_("Quantity Received"),"");
//inactive_control_column($th);
table_header($th);
$k = 0;
while ($myrow = db_fetch($result)) 
{
	alt_table_row_color($k);	
	label_cell($myrow["grnitemsid"]);
	//label_cell($myrow["grnbatchid"]);
	
	label_cell($myrow["item_code"]);
	label_cell($myrow["description"]);
	$dec = get_qty_dec($myrow["item_code"]);
	qty_cell($myrow["quantity_ordered"], false, $dec);
	label_cell($myrow["units"]);
	amount_decimal_cell($myrow["unit_price"]);
	$line_total = $myrow["quantity_ordered"] * $myrow["unit_price"];
	label_cell(sql2date($myrow["delivery_date"]));
	amount_cell($line_total);
	qty_cell($myrow["qty_recd"], false, $dec);
	
	echo "<td><a href='serial_details.php?serialitemdetails=yes&itemserialid=".$myrow['grnitemsid']."&serialid=".$myrow['grnbatchid']."'>"._("Details")."</a></td>\n";

	end_row();
}

end_table(1);

//-----------------------------------------------------------------------------------
echo "<hr/>\n";
if(get_post('itemserialid')){
	//$_POST['itemserialid'] = $_GET['itemserialid'];

$result = get_all_serialitems_details(get_post('serialid'),get_post('itemserialid'));

start_form();
start_table(TABLESTYLE, "width=50%");

$th = array(_("Item Code"), _("Item Name"), _("Quantity"), _("Serial/Engine #"),_("Chasis #"), "", "");

table_header($th);
$k = 0;
while ($myrow = db_fetch($result)) 
{
	alt_table_row_color($k);	

	label_cell($myrow["serialise_item_code"]);
	label_cell($myrow["serialise_reference"]);
	label_cell($myrow["serialise_qty"]);
	//label_cell(sql2date($myrow["serialise_manufacture_date"]));
	label_cell($myrow["serialise_lot_no"]);
	label_cell($myrow["serialise_chassis_no"]);
	//amount_cell($myrow["estimate_price"]);

 	edit_button_cell("Edit".$myrow['serialise_id'], _("Edit"));
 	delete_button_cell("Delete".$myrow['serialise_id'], _("Delete"));

	end_row();
}
end_table(1);


//-----------------------------------------------------------------------------------
start_table(TABLESTYLE2);
//echo $selected_id;
if ($selected_id != -1) 
{
 	if ($Mode == 'Edit') {
		//editing an existing status code

		$myrow = get_serialitems_detail($selected_id);
		
		//$_POST['item_code']  = $myrow["item_code"];
		//$_POST['purpose']  = $myrow["purpose"];
		$_POST['lot_no']  = $myrow["serialise_lot_no"];
		$_POST['chasis_no']  = $myrow["serialise_chassis_no"];
		$_POST['order_quantity']  = $myrow["serialise_qty"];
		$_POST['serialid']  = $myrow["serialise_grn_id"];
		$_POST['itemserialid']  = $myrow["serialise_grn_items_id"];
		$_POST['estimate_price']  = $myrow["estimate_price"];
	}
	hidden('selected_id', $selected_id);
	
	//echo $selected_id;
	//die();
} 
//echo $selected_id;
//	die();
//sales_local_items_list_row(_("Item :"), 'item_code', null, false, false);
text_row(_("Serial/Engine No:"), 'lot_no', null, 50, 50);
text_row(_("Chasis No:"), 'chasis_no', null, 50, 50);

	$res = get_item_edit_info(get_post('item_code'));
	$dec =  $res["decimals"] == '' ? 0 : $res["decimals"];
	$units = $res["units"] == '' ? _('kits') : $res["units"];
//text_row(_("GRN No:"),'serialid',null);
//text_row(_("GRN Line No:"),'itemserialid',null);
qty_row(_("Quantity:"), 'order_quantity', number_format2(1, $dec), '', $units, $dec);
//date_row(_("Manufacture Date :"), 'manufacture_date', '', null, 0, 0, 1001);
//date_row(_("Expire Date :"), 'expire_date', '', null, 0, 0, 1001);
//amount_row(_("Estimate Price :"), 'estimate_price', null, null, null, 2);

hidden('serialid');
hidden('itemserialid');
end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');
//submit_center_last('CancelEntry', 'Close');
}

end_form();

//------------------------------------------------------------------------------------

end_page();

