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

function display_grn_summary(&$po)
{

	start_table(TABLESTYLE2, "width='90%'");

	start_row();
	label_cells(_("Supplier"), $po->supplier_name, "class='tableheader2'");

	if (!is_company_currency($po->curr_code))
		label_cells(_("Order Currency"), $po->curr_code, "class='tableheader2'");

	label_cells(
		_("For Transaction Reference"),
		get_trans_view_str($po->is_consign == 'Non-Consignment' ? ST_PURCHREQUEST : ST_RECEIVECONSIGN, $po->supp_ref),
		"class='tableheader2'"
	);

	label_cells(_("Delivery Date"), $po->orig_order_date, "class='tableheader2'"); // FIXME: delivery date
	end_row();

	start_row();
	label_cells(_("RR #"), $po->reference, "class='tableheader2'");
	label_cells(_("Deliver Into Location"), get_location_name($po->Location), "class='tableheader2'");
	label_cells(_("Status"), $po->po_status, "class='tableheader2'");

	end_row();

	start_row();
	label_cells(_("Supplier's Ref #"), $po->suppl_ref_no, "class='tableheader2'");
	label_cells(_("Supplier's Ref Date"), sql2date($po->suppl_ref_date), "class='tableheader2'");
	label_cells(_("Invoiced Served By"), $po->suppl_served_by, "class='tableheader2'");

	end_row();

	label_row(_("Delivery Address"), $po->delivery_address, "class='tableheader2'", "colspan=9");

	if ($po->Comments != "")
		label_row(_("Remarks"), $po->grnComments, "class='tableheader2'", "colspan=9");
	end_table(1);
}

function edit_grn_summary(&$po)
{
	global $Refs;

	start_outer_table(TABLESTYLE2, "width='80%'");

	table_section(1);
	label_row(_("Supplier"), $po->supplier_name);

	if (!is_company_currency($po->curr_code))
		label_row(_("Order Currency"), $po->curr_code);
	label_row(_("For Purchase Order"), get_post('po_number'));

	label_row(_("Ordered On"), $po->orig_order_date);
	ref_row(_("Supplier's Ref. #"), 'suppl_ref_no', '', null);
	date_row(_("Supplier's Ref Date"), 'suppl_ref_date', '', true, 0, 0, 0, '', true);
	ref_row(_("Invoiced served by"), 'suppl_served_by', '', null);

	table_section(2);
	if (!isset($_POST['ref']))
		$_POST['ref'] = $Refs->get_next(ST_SUPPRECEIVE, null, array('supplier' => $po->supplier_id, 'date' => Today()));
	ref_row(_("RR Document #"), 'ref', '', null);

	if (!isset($_POST['Location']))
		$_POST['Location'] = $po->Location;

	if ($_POST['Location'] != "HO") {
		label_row(_("Deliver Into Location"), get_location_name($_POST['Location']));
		hidden('Location');
	} else {
		locations_list_row(_("Deliver Into Location"), "Location", $_POST['Location']);
	}

	if (!isset($_POST['DefaultReceivedDate']))
		$_POST['DefaultReceivedDate'] = new_doc_date();

	date_row(_("Date Items Received"), 'DefaultReceivedDate', '', true, 0, 0, 0, '', true);
	label_row(_("Category"), get_category_name($po->category_id));
	label_row(_("Is Consignment"), $po->is_consign);

	table_section(3);
	label_row(_("Trans Reference #"), get_trans_view_str($po->is_consign == "Consignment" ? ST_RECEIVECONSIGN : ST_PURCHREQUEST, $po->supp_ref));

	label_row(_("Delivery Address"), $po->delivery_address);

	if ($po->Comments != "")
		label_row(_("Order Comments"), $po->Comments, "class='tableheader2'", "colspan=9");

	if (!is_company_currency($po->curr_code))
		exchange_rate_display(get_company_currency(), $po->curr_code, get_post('DefaultReceivedDate'));
	end_outer_table(1);
}
