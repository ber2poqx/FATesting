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
	//Modfied by spyrax10 22 Aug 2022
	start_row();
	label_cells(_("Supplier's Ref #"), $po->suppl_ref_no, "class='tableheader2'");
	label_cells(_("Supplier's PO #:"), $po->sup_po, "class='tableheader2'");
	label_cells(_("Supplier's DR #:"), $po->sup_dr, "class='tableheader2'");

	end_row();

	start_row();
	label_cells(_("Supplier's Ref Date"), sql2date($po->suppl_ref_date), "class='tableheader2'");
	label_cells(_("Invoiced Served By"), $po->suppl_served_by, "class='tableheader2'");
	end_row();
	//

	label_row(_("Delivery Address"), $po->delivery_address, "class='tableheader2'", "colspan=9");

	if ($po->Comments != "")
		label_row(_("Remarks"), $po->grnComments, "class='tableheader2'", "colspan=9");
	end_table(1);
}

function edit_grn_summary(&$po) {
	global $Refs;

	start_outer_table(TABLESTYLE2, "width='80%'");
	//Modified by spyrax10 22 Aug 2022
	table_section(1);
	label_row(_("Supplier: &nbsp;"), $po->supplier_name);

	if (!is_company_currency($po->curr_code))
		label_row(_("Order Currency"), $po->curr_code);
	label_row(_("For Purchase Order: &nbsp;"), get_post('po_number'));

	label_row(_("Ordered On: &nbsp;"), $po->orig_order_date);
	ref_row(_("Supplier's Ref #: &nbsp;"), 'suppl_ref_no', '', null);
	ref_row(_("Supplier's PO #: &nbsp;"), 'sup_po', '', null);
	ref_row(_("Supplier's DR #: &nbsp;"), 'sup_dr', '', null);
	date_row(_("Supplier's Ref Date: &nbsp;"), 'suppl_ref_date', '', true, 0, 0, 0, '', true);
	ref_row(_("Invoiced served by: &nbsp;"), 'suppl_served_by', '', null);

	table_section(2);
	if (!isset($_POST['ref']))
		$_POST['ref'] = $Refs->get_next(ST_SUPPRECEIVE, null, array('supplier' => $po->supplier_id, 'date' => Today()));
	ref_row(_("RR Document #: &nbsp;"), 'ref', '', null);

	if (!isset($_POST['Location']))
		$_POST['Location'] = $po->Location;

	if ($_POST['Location'] != "HO") {
		label_row(_("Deliver Into Location: &nbsp;"), get_location_name($_POST['Location']));
		hidden('Location');
	} else {
		locations_list_row(_("Deliver Into Location: &nbsp;"), "Location", $_POST['Location']);
	}

	if (!isset($_POST['DefaultReceivedDate']))
		$_POST['DefaultReceivedDate'] = new_doc_date();

	date_row(_("Date Items Received: &nbsp;"), 'DefaultReceivedDate', '', true, 0, 0, 0, '', true);
	label_row(_("Category: &nbsp;"), get_category_name($po->category_id));
	label_row(_("Is Consignment: &nbsp;"), $po->is_consign);

	table_section(3);
	label_row(_("Trans Reference #: &nbsp;"), get_trans_view_str($po->is_consign == "Consignment" ? ST_RECEIVECONSIGN : ST_PURCHREQUEST, $po->supp_ref));

	label_row(_("Delivery Address: &nbsp;"), $po->delivery_address);

	if ($po->Comments != "")
		label_row(_("Order Comments: &nbsp;"), $po->Comments);

	if (!is_company_currency($po->curr_code))
		exchange_rate_display(get_company_currency(), $po->curr_code, get_post('DefaultReceivedDate'));
	end_outer_table(1);
}
