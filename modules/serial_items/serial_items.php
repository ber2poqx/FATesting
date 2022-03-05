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




include_once($path_to_root . "/modules/serial_items/includes/modules_db.inc");
include_once($path_to_root . "/includes/ui.inc");

add_js_ufile("../../js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile('../../js/serial_items.js');

//simple_page_mode(true);
//-----------------------------------------------------------------------------------

if(isset($_GET['view'])){
	//mysql_set_charset('utf8');
	$start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
	$limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
	if(isset($_GET['supplier_id'])){
		$supplier_id=$_GET['supplier_id'];
	}else $supplier_id=0;
	$total=get_all_serialitems_count($supplier_id);
	$result = get_all_serialitems($start,$limit,$supplier_id,check_value('show_inactive'));
    while ($myrow = db_fetch($result)) {
	  $total_qty = get_total_grn_qty($myrow["id"]);
	  $serialise_total_qty = get_total_serialised_qty($myrow["id"]);
      $group_array[] = array('id'=>$myrow["id"],
						'reference'=>$myrow["reference"],						
						'purch_order_no'=>$myrow["purch_order_no"],
						'supp_name'=>$myrow["supp_name"],
						'location_name'=>$myrow["location_name"],
						'ord_date'=>sql2date($myrow["ord_date"]),
						'delivery_date'=>sql2date($myrow["delivery_date"]),
						'total_qty'=>$total_qty,
						'serialise_total_qty'=>$serialise_total_qty
						);
      
	}
	
	$jsonresult = json_encode($group_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';

   exit();
}

if(isset($_GET['suppliers_list'])){
	$sql = "SELECT * FROM ".TB_PREF."suppliers ORDER BY supp_name asc";
	$total=0;
	$result = db_query($sql, "could not get all Suppliers");
	$group_array[] = array('supplier_id'=>'0','supp_name'=>'All');
    while ($myrow = db_fetch($result)) {
		$group_array[] = array('supplier_id'=>$myrow["supplier_id"],
						'supp_name'=>$myrow["supp_name"]
		);
      
	}
	
	$jsonresult = json_encode($group_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';

   exit();
}
page(_($help_context = "Goods Receipt Listing"));

if (isset($Mode) && ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM')) 
{

	//initialise no input errors assumed initially before we test
	$input_error = 0;

	if (strlen($_POST['point_of_use']) == 0) 
	{
		$input_error = 1;
		display_error(_("The point of use cannot be empty."));
		set_focus('name');
	}
	if (strlen($_POST['narrative']) == 0) 
	{
		$input_error = 1;
		display_error(_("The narrative be empty."));
		set_focus('rate');
	}

	if ($input_error != 1) 
	{
    	if ($selected_id != -1) 
    	{
    		update_requisition($selected_id, $_POST['point_of_use'], $_POST['narrative'], $_POST['details']);
			display_notification(_('Selected requisition has been updated.'));


    	} 
    	else 
    	{
    		add_requisition( $_POST['point_of_use'], $_POST['narrative'], $_POST['details']);
			display_notification(_('New requisition has been added'));
    	}
    	
		$Mode = 'RESET';
	}
} 

//-----------------------------------------------------------------------------------

function can_delete($selected_id)
{
	if (requisitions_in_details($selected_id))
	{
		display_error(_("Cannot delete this requisition because details transactions have been created referring to it."));
		return false;
	}
	
	return true;
}


//-----------------------------------------------------------------------------------

if (isset($Mode) && $Mode == 'Delete')
{
	if (can_delete($selected_id))
	{
		delete_requisition($selected_id);
		display_notification(_('Selected requisition has been deleted'));
	}
	$Mode = 'RESET';
}

if (isset($Mode) && $Mode == 'RESET')
{
	$selected_id = -1;
	$sav = get_post('show_inactive');
	unset($_POST);
	$_POST['show_inactive'] = $sav;
}
//-----------------------------------------------------------------------------------
//start_table(TABLESTYLE2, "width=90%");
//echo "<tr><td align=center>";
//div_start('serialitems-grid');
//div_end();
echo "<div id='serialitems-grid' style='padding:15px'></div>";
//echo "</td></tr>";
//end_table();
/* 
$result = get_all_serialitems(check_value('show_inactive'));


start_form();
start_table(TABLESTYLE, "width=50%");

$th = array(_("#"), _("Reference"), _("PO #"), _("Supplier"), _("Location"), _("Order Date"),_("Delivery Date"), _("Details"));
//inactive_control_column($th);
table_header($th);
$k = 0;
while ($myrow = db_fetch($result)) 
{
	alt_table_row_color($k);	

	label_cell($myrow["id"]);
	label_cell($myrow["reference"]);
	label_cell($myrow["purch_order_no"]);
	label_cell($myrow["supp_name"]);
	label_cell($myrow["location_name"]);
	label_cell(sql2date($myrow["ord_date"]));
	label_cell(sql2date($myrow["delivery_date"]));

 	//edit_button_cell("Edit".$myrow['requisition_id'], _("Edit"));
	//inactive_control_cell($myrow["requisition_id"], $myrow["inactive"], 'requisitions', 'requisition_id');
 	//delete_button_cell("Delete".$myrow['requisition_id'], _("Delete"));

	echo "<td><a href='serial_details.php?serialid=".$myrow['id']."'>"._("Details")."</a></td>\n";

	end_row();
}
//inactive_control_row($th);
end_table(); */

//-----------------------------------------------------------------------------------

/* start_table(TABLESTYLE2);

if ($selected_id != -1) 
{
 	if ($Mode == 'Edit') {
		//editing an existing status code

		$myrow = get_requisition($selected_id);

		$_POST['point_of_use']  = $myrow["point_of_use"];
		$_POST['narrative']  = $myrow["narrative"];
		$_POST['details']  = $myrow["details"];
	}
	hidden('selected_id', $selected_id);
} 

text_row(_("Point of use :"), 'point_of_use', null, 50, 50);
text_row(_("Narrative :"), 'narrative', null, 50, 50);
textarea_row(_("Details :"), 'details', null, 50, 5);

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form(); */

//------------------------------------------------------------------------------------

end_page();

