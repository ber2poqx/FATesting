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

function edit_prtpo_summary(&$pr)
{
	global $Ajax, $Refs;
    global $Refs, $db_connections;
    $branchcode = $db_connections[user_company()]["branch_code"];

	start_outer_table(TABLESTYLE2, "width='80%'");

	table_section(1);
	label_row(_("For Purchase Request"), get_trans_view_str(ST_PURCHREQUEST, $pr->reference));
	// label_row(_("For Purchase Request"),$pr->reference);
	
	if (!isset($_POST['po_reference']))
	    $_POST['po_reference'] = $Refs->get_next(ST_PURCHORDER, null, array('supplier' => $pr->supplier_id, 'date' => Today(), 'branchcode'=>$branchcode));
	ref_row(_("PO #"), 'po_reference', '', null);
	hidden('supplier_id');
	label_row(_("Supplier"), $pr->supplier_name);
	label_row(_("Category"), get_category_name($pr->category_id));
	purch_types_list_row(
		_("Purchase Type:"),
		'purch_type_id',
		null
	);

	table_section(2);

	label_row(_("Requested On"), $pr->orig_order_date);

	date_row(_("Order Date"), 'DefaultReceivedDate', '', true, 0, 0, 0, '', true);

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
	$Ajax->activate('Location');
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
	label_row(_("Served Status:"), $served_status);
	hidden('served_status', $pr->served_status);

	table_section(3);

	$loc_row = get_item_location(get_post('Location'));
	if ($loc_row) {
		$_POST['DeliveryAddress'] = $loc_row["delivery_address"];
		$Ajax->activate('DeliveryAddress');
		$_SESSION['PR']->DeliveryAddress = $_POST['DeliveryAddress'];
	} else { /*The default location of the user is crook */
		display_error(_("The default stock location set up for this user is not a currently defined stock location. Your system administrator needs to amend your user record."));
	}


	textarea_row(_("Deliver to:"), 'DeliveryAddress', null, 35, 4);

	if ($pr->Comments != "")
		label_row(_("Order Comments"), $pr->Comments, "class='tableheader2'", "colspan=9");

	end_outer_table(1);
}
