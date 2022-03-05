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
//add_js_ufile("../../js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
//add_js_ufile('../../js/serial_items.js');

if (user_use_date_picker())
	$js .= get_js_date_picker();

page(_($help_context = "Serial Items Entries"), false, false, "", $js);
//page(_($help_context = "Serial Items Entries"));

include_once($path_to_root . "/purchasing/includes/db/grn_db.inc");
include_once($path_to_root . "/modules/serial_items/includes/modules_db.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/ui/ui_lists.inc");

simple_page_mode(true);
//-----------------------------------------------------------------------------------
if(isset($_GET['serialid'])) {
	/* $req = get_requisition($_GET['serialid']);
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
	else */
	$_POST['serialid'] = $_GET['serialid'];
}
if(isset($_GET['itemserialid'])){
	$_POST['itemserialid'] = $_GET['itemserialid'];
}
if(isset($_GET['grnitemsid'])){
	$_POST['grnitemsid'] = $_GET['grnitemsid'];
}
if(isset($_GET['item_code'])){
	$_POST['item_code'] = $_GET['item_code'];
}
if(isset($_GET['loc_code'])){
	$_POST['loc_code'] = $_GET['loc_code'];
}
if(isset($_GET['complete'])){
	$_POST['complete'] = $_GET['complete'];
}
if(isset($_GET['model'])){
    $_POST['model'] = $_GET['model'];
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
	
	
	if(is_null($_POST['lot_no']) || empty($_POST['lot_no'])){
		$input_error = 1;
		display_error(_("The Serial/Engine No. should not be empty."));
		set_focus('lot_no');
	}
	if($_POST['category_id']==14 && (search_chasisno($_POST['chasis_no'])>0 && $Mode=='ADD_ITEM')){
	    $input_error = 1;
	    display_error(_("The Chassis No. is already exist. ".$_POST['chasis_no']));
	    set_focus('chasis_no');
	}
	
	if($_POST['category_id']==14 && (search_serialno($_POST['lot_no'])>0 && $Mode=='ADD_ITEM')){
	    $input_error = 1;
	    display_error(_("The Serial/Engine No. is already exist. ".$_POST['lot_no']));
	    set_focus('chasis_no');
	}
	
	if(search_serialitem($_POST['lot_no'], $_POST['chasis_no'])>0 && $Mode=='ADD_ITEM'){
	    $input_error = 1;
	    display_error(_("The Serial/Engine No. is already exist. ".$_POST['lot_no']));
	    set_focus('lot_no');
	}
	if($_POST['category_id']==14 && ($_POST['chasis_no']==null || $_POST['chasis_no']=='' || empty($_POST['chasis_no']))){
	    $input_error = 1;
	    display_error(_("The Chassis No. should not be empty. ".$_POST['lot_no']));
	    set_focus('chasis_no');
	}
	if (strlen($_POST['order_quantity']) == 0 || $_POST['order_quantity']<=0) 
	{
		$input_error = 1;
		display_error(_("The Quantity should not be empty or less than 0.".$selected_id));
		set_focus('order_quantity');
	}
	
	$total_grn_item_qty = get_total_grn_qty($_POST['serialid'],$_POST['itemserialid']);
	$serialise_count = count_serialise_item($_POST['serialid'],$_POST['itemserialid']);
	if($Mode=='UPDATE_ITEM'){
		$serialise_count_item = count_serialise_id($selected_id);
		$serialise_count -= $serialise_count_item;
		//$serialise_count= $_POST['order_quantity'];
	}
	$serialise_count += $_POST['order_quantity'];
	
	if($serialise_count>$total_grn_item_qty){
		$input_error = 1;
		display_error(_("The Quantity should not be greater then Total Quantity to receive.".$selected_id));
		set_focus('order_quantity');
	}
	
	if ($input_error != 1) 
	{
    	if ($selected_id != -1) 
    	{
    	    update_serialitems_detail($selected_id, $_POST['lot_no'], $_POST['manufacture_date'], $_POST['order_quantity'], $_POST['expire_date'], $_POST['chasis_no'],ST_SUPPRECEIVE, check_value('lcp_promotional'));
    	    update_smo_serial($_POST['trans_id'], $_POST['serialid'], ST_SUPPRECEIVE, $_POST['lot_no'], $_POST['chasis_no']); //added by spyrax10, updated by Herald 11/20/2021
			display_notification(_('Selected item serial details has been updated.'));
    	} 
    	else 
    	{
    	    $grnrow = get_grn_batch($_POST['serialid']);
    	    $del_date = sql2date($grnrow['delivery_date']);
    	    $cat_result = $grnrow['category_id'];
    	    $supplier_id = $grnrow['supplier_id'];
    	    $standard_cost = Get_Policy_Cost($_POST['loc_code'],$cat_result,$_POST['model'], $supplier_id);//Updated by Herald 03/11/2021
    	    
    	    add_stock_move(ST_SUPPRECEIVE, $_POST['model'], $_POST['serialid'],$_POST['loc_code'], $del_date, $grnrow['reference'], $_POST['order_quantity'], $standard_cost, 0,  $_POST['lot_no'], $_POST['chasis_no'], $grnrow['category_id'], $_POST['item_code']);
    	    
    	    
    	    add_serialitems_detail($_POST['serialid'], $_POST['itemserialid'], $_POST['lot_no'], $_POST['order_quantity'], $_POST['manufacture_date'],$_POST['expire_date'],$_POST['item_code'],$_POST['loc_code'],$_POST['chasis_no'], ST_SUPPRECEIVE, check_value('lcp_promotional') );
    		//Added by Herald
    		
			display_notification(_('New Serial Items has been added'));
    	}
    	if($serialise_count==$total_grn_item_qty){
			$_POST['complete']=1;
		}
		$Mode = 'RESET';
	}
} 

//-----------------------------------------------------------------------------------

if ($Mode == 'Delete')
{
	delete_serialitem_detail($selected_id);
	display_notification(_('Selected serial item detail has been deleted'));

	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	$selected_id = -1;
	//$sav = get_post('show_inactive');
	$serialid = $_POST['serialid'];
	$itemserialid = $_POST['itemserialid'];
	$item_code = $_POST['item_code'];
	$model = $_POST['model'];
	$loc_code = $_POST['loc_code'];
	$complete = $_POST['complete'];
	$lcp_promotional = $_POST['lcp_promotional'];
	unset($_POST);
	$_POST['serialid'] = $serialid;
	$_POST['itemserialid'] = $itemserialid;
	$_POST['item_code'] = $item_code;
	$_POST['model'] = $model;
	$_POST['loc_code']=$loc_code;
	$_POST['complete'] = $complete;	
	$_POST['lcp_promotional'] = $lcp_promotional;
}
//-----------------------------------------------------------------------------------

$result = get_one_serialitems(get_post('serialid'));


start_table(TABLESTYLE, "width=80%");
$cat_result = get_category_id(get_post('serialid'));
if($cat_result==14){
    $th = array(_("GRN#"),_("Model Code"), _("Description"), _("Color Code"),_("Color Description"), _("Ordered"), _("Unit"), _("Delivery Date"),_("Quantity Received"),_("Serialise Qty"),"");
    
}else{
    $th = array(_("GRN#"),_("Model Code"), _("Description"), _("Ordered"), _("Unit"), _("Delivery Date"),_("Quantity Received"),_("Serialise Qty"),"");
    
}
//inactive_control_column($th);
table_header($th);
$k = 0;
while ($myrow = db_fetch($result)) 
{
	if ($myrow['grnitemsid']==$_POST['itemserialid'])
	{
    	start_row("class='overduebg'");
    	//$overdue_items = true;
	}
	else
	{
		alt_table_row_color($k);
	}
	
	//alt_table_row_color($k);	
	label_cell($myrow["grnitemsid"]);
	//label_cell($myrow["grnbatchid"]);
	
	label_cell($myrow["item_code"]);
	$res = get_item_edit_info($myrow["item_code"]);
	$units = $res["units"] == '' ? _('kits') : $res["units"];
	
	label_cell($myrow["description"], "nowrap align=left");
	if($cat_result==14){
	    label_cell($myrow["color_code"], "nowrap align=left");
	    label_cell($myrow["color_description"], "nowrap align=left");
	    $itemselect = $myrow["color_code"];
	}else{
	    $itemselect = $myrow["item_code"];
	    
	}
	$modelcode = $myrow["item_code"];
	$dec = get_qty_dec($myrow["item_code"]);
	qty_cell($myrow["quantity_ordered"], false, $dec);
	//label_cell($myrow["units"]);
	label_cell($units," align='center'");
	//amount_decimal_cell($myrow["unit_price"]);
	$line_total = $myrow["quantity_ordered"] * $myrow["unit_price"];
	label_cell(sql2date($myrow["delivery_date"])," align='center'");
	//amount_cell($line_total);
	qty_cell($myrow["qty_recd"], false, $dec);
	$serialise_count = count_serialise_item($myrow['grnbatchid'],$myrow['grnitemsid']);
	qty_cell($serialise_count, false, $dec);
	if($serialise_count<$myrow["qty_recd"]){
	    echo "<td align=center colspan=2><a href='serial_details.php?serialitemdetails=yes&itemserialid=".$myrow['grnitemsid']."&serialid=".$myrow['grnbatchid']."&model=".$myrow["item_code"]."&item_code=".$itemselect."&loc_code=".$myrow["loc_code"]."&complete=0'>"._("Details")."</a></td>\n";
			
	}else{
	    echo "<td align=center><a href='serial_details.php?serialitemdetails=yes&itemserialid=".$myrow['grnitemsid']."&serialid=".$myrow['grnbatchid']."&model=".$myrow["item_code"]."&item_code=".$itemselect."&loc_code=".$myrow["loc_code"]."&complete=1'>"._("Complete")."</a></td><td>
		<a href='serial_details.php?serialitemdetails=yes&itemserialid=".$myrow['grnitemsid']."&serialid=".$myrow['grnbatchid']."&model=".$myrow["item_code"]."&item_code=".$itemselect."&loc_code=".$myrow["loc_code"]."&complete=0'>Edit</a>
		</td>\n";
	}


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
	//Modified by spyrax10
	if($cat_result==14){
	    $th = array(_("ID"), _("Location"), _("Quantity"),_("Engine No."),_("Chasis No."), _("Promotional"), "", "");
	}else{
	    $th = array(_("ID"), _("Location"), _("Quantity"),_("Serial No."), _("Promotional"),_(""),_(""));
	    
	}
	table_header($th);
	$k = 0;
	while ($myrow = db_fetch($result)) 
	{
		alt_table_row_color($k);	

		label_cell($myrow["trans_id"]); //Added by spyrax10
		label_cell($myrow["serialise_loc_code"]);
		//label_cell($myrow["color_code"]);
		label_cell($myrow["serialise_qty"]);
		//label_cell(sql2date($myrow["serialise_manufacture_date"]));
		//label_cell(sql2date($myrow["serialise_expire_date"]));
		
		label_cell($myrow["serialise_lot_no"]);
		if($cat_result==14){
		  label_cell($myrow["serialise_chasis_no"]);
		}
		//amount_cell($myrow["estimate_price"]);
		label_cell($myrow["serialise_lcp_promotional"]);
		
		$demand_qty = get_demand_qty($myrow["stock_id"], $myrow["serialise_loc_code"]);
		$demand_qty += get_demand_asm_qty($myrow["stock_id"], $myrow["serialise_loc_code"]);
		$qty=get_qoh_on_date_per_serial($myrow["serialise_lot_no"], $myrow["serialise_chasis_no"], $myrow["serialise_trans_type"], $myrow["serialise_grn_id"], $myrow["stock_id"], $myrow["serialise_loc_code"]);
		$qty-=$demand_qty;
		
		if(get_post('complete')==0 && $qty>0){
			edit_button_cell("Edit".$myrow['serialise_id'], _("Edit"));
			delete_button_cell("Delete".$myrow['serialise_id'], _("Delete"));
		}else{
			echo "<td></td><td></td>";
		}

		end_row();
	}
	end_table(1);
	

//-----------------------------------------------------------------------------------
	start_table(TABLESTYLE2);
	//echo $selected_id;
	if(get_post('complete')==0){	
	if ($selected_id != -1) 
	{
		if ($Mode == 'Edit') {
		//editing an existing status code

				$myrow = get_serialitems_detail($selected_id);
			//echo $selected_id;
			//die();
			$_POST['trans_id'] = $myrow["trans_id"]; //Added by spyrax10
			$_POST['item_code']  = $myrow["serialise_item_code"];
			//$_POST['purpose']  = $myrow["purpose"];
			//$_POST['color_code']  = $myrow["color_code"];
			$_POST['order_quantity']  = $myrow["serialise_qty"];
			$_POST['serialid']  = $myrow["serialise_grn_id"];
			$_POST['lot_no']  = $myrow["serialise_lot_no"];
			$_POST['chasis_no'] = $myrow["serialise_chasis_no"];
			$_POST['manufacture_date']  = sql2date($myrow["serialise_manufacture_date"]);
			$_POST['expire_date']  = sql2date($myrow["serialise_expire_date"]);
			$_POST['itemserialid']  = $myrow["serialise_grn_items_id"];
			$_POST['lcp_promotional']  = $myrow["serialise_lcp_promotional"];
			$_POST['old_lot_no']  = $myrow["serialise_lot_no"];
			$_POST['old_chasis_no'] = $myrow["serialise_chasis_no"];
			//$_POST['estimate_price']  = $myrow["estimate_price"];
		}
		hidden('selected_id', $selected_id);
		//hidden('serialid');
		//hidden('itemserialid');
	}else{
	    $_POST['order_quantity']=0;
	    $_POST['lcp_promotional']=0;
	    $_POST['old_lot_no']  = '';
	    $_POST['old_chasis_no'] = '';
	}		
	//sales_local_items_list_row(_("Item :"), 'item_code', null, false, false);

	$res = get_item_edit_info(get_post('model'));
	$dec =  $res["decimals"] == '' ? 0 : $res["decimals"];
	$units = $res["units"] == '' ? _('kits') : $res["units"];
	$_POST['category_id']  = $res["category_id"];
	//text_row(_("GRN No:"),'serialid',null);
	//text_row(_("GRN Line No:"),'itemserialid',null);
	//text_row(_("Location Code:"),'loc_code',null);
	//text_row(_("Item Code:"),'item_code',null);
	//text_row(_("ID: "), 'trans_id', $myrow["trans_id"], 10, 10);
	//text_row(_("Category ID:"), 'category_id');
	if($res["serialised"]){
	   hidden('order_quantity',1);
	   set_focus('lot_no');
	   
	}else{
	    qty_row(_("Quantity:"), 'order_quantity', number_format2(1, $dec), '', $units, $dec);
	    set_focus('order_quantity');
	}
	if($cat_result==14){
	   text_row(_("Engine No.:"), 'lot_no', null, 50, 50);
	   text_row(_("Chasis No.:"), 'chasis_no', null, 50, 50);
	}else{
	    text_row(_("Serial No.:"), 'lot_no', null, 50, 50);
	    hidden('chasis_no');
	}
	check_row(_("LCP/Promotional:"), 'lcp_promotional');
	
	//date_row(_("Manufacture Date :"), 'manufacture_date', '', null, 0, 0, 1001);
	//date_row(_("Expire Date :"), 'expire_date', '', null, 0, 0, 1001);
	//amount_row(_("Estimate Price :"), 'estimate_price', null, null, null, 2);
	hidden('trans_id'); //Added by spyrax10
	hidden('category_id'); //Added by Herald 10/13/2021
	hidden('loc_code');
	hidden('model');
	hidden('item_code');
	hidden('serialid');
	hidden('manufacture_date');
	hidden('expire_date');
	hidden('itemserialid');
	hidden('old_lot_no');
	hidden('old_chasis_no');
	end_table(1);

	submit_add_or_update_center($selected_id == -1, '', 'both');
	
	}
//submit_center_last('CancelEntry', 'Close');
}

end_form();

//------------------------------------------------------------------------------------

end_page();

