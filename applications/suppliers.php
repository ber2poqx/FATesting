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
class suppliers_app extends application 
{
	function __construct() 
	{
		parent::__construct("AP", _($this->help_context = "&Purchases"));

		$this->add_module(_("Transactions"));
		if (user_company() == 0) {
			$this->add_lapp_function(0, _("Branch Purchase Request"),
			"purchasing/pr_branch.php?", 'SA_PR_BRANCH', MENU_TRANSACTION);
			$this->add_lapp_function(0, _("Branch Purchase Orders"),
				"purchasing/po_branch.php?", 'SA_PO_BRANCH', MENU_TRANSACTION);
		}
		$this->add_lapp_function(0, _("Purchase Request"),
			"purchasing/purchase_request.php?", 'SA_PURCHASEREQUEST', MENU_TRANSACTION);
		$this->add_lapp_function(0, _("Purchase &Order Entry"),
			"purchasing/po_entry_items.php?NewOrder=Yes", 'SA_PURCHASEORDER', MENU_TRANSACTION);
		
		//Modified by spyrax10 18 Jun 2022
		$this->add_lapp_function(0, _("&Outstanding Purchase Orders Maintenance"),
			"purchasing/inquiry/po_search.php?", 'SA_RR_LIST', MENU_TRANSACTION
		);
		//
		
		$this->add_lapp_function(0, _("Receiving &Report Entry"),
			"purchasing/po_entry_items.php?NewGRN=Yes", 'SA_GRN', MENU_TRANSACTION);
		$this->add_lapp_function(0, _("Direct Supplier &Invoice"),
			"purchasing/po_entry_items.php?NewInvoice=Yes", 'SA_SUPPLIERINVOICE', MENU_TRANSACTION);
		
		//Modified by spyrax10 27 Jun 2022
		$this->add_lapp_function(0, _("Receive Consignment Item"),
			"purchasing/rcon_po.php?", 'SA_RECEIVECONSIGN', MENU_TRANSACTION
		);
		//

		$this->add_rapp_function(0, _("&Payments to Suppliers"),
			"purchasing/supplier_payment.php?", 'SA_SUPPLIERPAYMNT', MENU_TRANSACTION);
		$this->add_rapp_function(0, "","");
		$this->add_rapp_function(0, _("Supplier &Invoices"),
			"purchasing/supplier_invoice.php?New=1", 'SA_SUPPLIERINVOICE', MENU_TRANSACTION);
		$this->add_rapp_function(0, _("Supplier &Credit Notes"),
			"purchasing/supplier_credit.php?New=1", 'SA_SUPPLIERCREDIT', MENU_TRANSACTION);
		$this->add_rapp_function(0, _("&Allocate Supplier Payments or Credit Notes"),
			"purchasing/allocations/supplier_allocation_main.php?", 'SA_SUPPLIERALLOC', MENU_TRANSACTION);

		$this->add_module(_("Inquiries and Reports"));
		
		$this->add_lapp_function(1, _("Purchase Orders &Inquiry"),
			"purchasing/inquiry/po_search_completed.php?", 'SA_SUPPTRANSVIEW', MENU_INQUIRY);
		$this->add_lapp_function(1, _("Supplier Transaction &Inquiry"),
			"purchasing/inquiry/supplier_inquiry.php?", 'SA_SUPPTRANSVIEW', MENU_INQUIRY);
		$this->add_lapp_function(1, _("Supplier Allocation &Inquiry"),
			"purchasing/inquiry/supplier_allocation_inquiry.php?", 'SA_SUPPLIERALLOC', MENU_INQUIRY);

		//Modified by spyrax10 18 Jun 2022
		$this->add_rapp_function(1, _("Supplier and Purchasing &Reports"),
			"reporting/reports_main.php?Class=1", 'SA_PURCH_REPORT', MENU_REPORT
		);

		$this->add_module(_("Maintenance"));
		$this->add_lapp_function(2, _("&Supplier Set-Up"),
			"purchasing/manage/suppliers.php?", 'SA_SUPPLIER', MENU_ENTRY);
		$this->add_rapp_function(2, _("System &Cost Setup"),
			"purchasing/manage/supplier_costyps.php?", 'SA_SUPPLIERCOSTYP', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("Suppliers &Group"),
		    "purchasing/manage/suppliers_group.php?", 'SA_SUPPLIER_GROUP', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("Suppliers &Request Set-Up"),
		    "purchasing/manage/suppliers_request.php?", 'SA_SUPPLIER_GROUP', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("Suppliers Re-order &Level Entry"),
		    "purchasing/supplier_reorderlevel.php?", 'SA_SUPPLIERREORDERLEVEL', MENU_MAINTENANCE);
		
		$this->add_extensions();
	}
}


