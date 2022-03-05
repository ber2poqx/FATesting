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
//---------------------------------------------------------------------------------------------------

function edit_copy_from_rcon_to_po_summary(&$rcon)
{
    global $Refs, $db_connections;
    $branchcode = $db_connections[user_company()]["branch_code"];

	start_outer_table(TABLESTYLE2, "width='80%'");

	table_section(1);
	label_row(_("For Consignment"), get_trans_view_str(ST_RECEIVECONSIGN, $rcon->reference));
	// label_row(_("For Purchase Request"),$rcon->reference);
	
	if (!isset($_POST['po_reference']))
	    $_POST['po_reference'] = $Refs->get_next(ST_PURCHORDER, null, array('supplier' => $rcon->supplier_id, 'date' => Today(), 'branchcode'=>$branchcode));
	ref_row(_("PO #"), 'po_reference', '', null);
	label_row(_("Supplier"), $rcon->supplier_name);
	label_row(_("Category"), get_category_name($rcon->category_id));
	purch_types_list_row(
		_("Purchase Type:"),
		'purch_type_id',
		null
	);

	table_section(2);

	label_row(_("Received On"), $rcon->orig_order_date);

	date_row(_("Order Date"), 'DefaultReceivedDate', '', true, 0, 0, 0, '', true);

	if (!isset($_POST['Location']))
		$_POST['Location'] = $rcon->Location;

	$company = get_company_prefs();

	$branch_code = $company["branch_code"];
	if (!isset($_POST['Location'])) {
		$_POST['Location'] = $branch_code;
		$_POST['StkLocation'] = $branch_code;
	}

	if ($_POST['Location'] != "HO") {
		label_row(_("Deliver Into Location"), get_location_name($_POST['Location']));
		hidden('Location');
	} else {
		locations_list_row(_("Deliver Into Location"), "Location", $_POST['Location']);
	}
	// $served_status = "";
    // switch ($rcon->served_status) {
    //     case 0:
    //         $served_status = "Normal Served";
    //         break;
    //     case 1:
    //         $served_status = "Overserved";
    //         break;
    //     case 2:
    //         $served_status = "Wrong Served";
    //         break;
    //     default:
    //         $served_status = "";
    // }
	// label_row(_("Served Status:"), $served_status);
	// hidden('served_status', $rcon->served_status);

	table_section(3);


	textarea_row(_("Deliver to:"), 'DeliveryAddress', null, 35, 4);

	if ($rcon->Comments != "")
		label_row(_("Order Comments"), $rcon->Comments, "class='tableheader2'", "colspan=9");

	end_outer_table(1);
}
