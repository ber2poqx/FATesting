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
class lending_app extends application
{
	function __construct()
	{
		parent::__construct("lending", _($this->help_context = "&Lending"));

		$this->add_module(_("Transactions"));
		//$this->add_lapp_function(0, _("A/R Incoming"),
		//	"lending/ar_installment_incoming.php?", 'SA_ARINVCINSTL', MENU_TRANSACTION);
		$this->add_lapp_function(0, _("A/R Installment Incoming"),
			"lending/inquiry/ar_invoice_inquiry.php?", 'SA_INVCINQ', MENU_INQUIRY);

		$this->add_rapp_function(0, _("Office &Collection Receipt"),
			"lending/customer_amort_receipt.php?", 'SA_LCUSTAMORT', MENU_TRANSACTION);
		//$this->add_rapp_function(0, _("Cash &Invoice Receipt"),
		//	"lending/customer_amort_receipt.php?type=cash", 'SA_LCUSTAMORT', MENU_TRANSACTION);
		$this->add_rapp_function(0, _("Payment Allocation"),
			"lending/allocation_payment.php?", 'SA_ALLOCPYMNT', MENU_TRANSACTION);

		$this->add_rapp_function(0, _("Inter-branch (From Not FA)"),
		"lending/inquiry/interbranch_payments_inquiry.php?", 'SA_INTRBPAYINQ', MENU_TRANSACTION);

		$this->add_module(_("Inquiries and Reports"));
		//$this->add_lapp_function(1, _("Payment Allocation"),
		//	"lending/allocation_payment.php?", 'SA_ALLOCPYMNT', MENU_INQUIRY);
		$this->add_lapp_function(1, _("Incoming Inter-branch Payments Inquiry"),
			"lending/inquiry/interbranch_payments_inquiry.php?", 'SA_INTRBPAYINQ', MENU_INQUIRY);

		$this->add_lapp_function(1, "","");

		$this->add_lapp_function(1, _("A/R Installment Inquiry"),
			"lending/inquiry/ar_installment_inquiry.php?", 'SA_ARINVCINQ', MENU_INQUIRY);
		$this->add_lapp_function(1, _("Receiving Report &Repo"),
			"lending/manage/repo_accounts.php?", 'SA_GRNREPO', MENU_INQUIRY);
		
		$this->add_rapp_function(0, "","");
		$this->add_lapp_function(1, _("Temporary Repo Accounts &Inquiry"),
			"lending/manage/temporary_repo_accounts.php?", 'SA_TEMPREPOINQRY', MENU_INQUIRY);
		$this->add_lapp_function(1, _("A/R Termmode &Inquiry"),
			"lending/search_work_orders.php?", 'SA_ARTERMODNQ', MENU_INQUIRY);
			
		//-----------------------------------------------------------------------
		
		$this->add_rapp_function(1, _("Customer Transaction &Inquiry"),
			"sales/inquiry/customer_inquiry.php?", 'SA_SALESTRANSVIEW', MENU_INQUIRY);
		$this->add_rapp_function(1, _("Customer Allocation &Inquiry"),
			"sales/inquiry/customer_allocation_inquiry.php?", 'SA_SALESALLOC', MENU_INQUIRY);
		
		$this->add_rapp_function(1, "","");

		$this->add_rapp_function(1, _("Lending &Reports"),
			"reporting/reports_main.php?Class=3", 'SA_ARINVCINQ', MENU_REPORT);

		$this->add_module(_("Maintenance"));
		$this->add_lapp_function(2, _("Add and Manage &Customers"),
			"sales/manage/customers.php?", 'SA_CUSTOMER', MENU_ENTRY);
		$this->add_lapp_function(2, _("Customer &Branches"),
			"sales/manage/customer_branches.php?", 'SA_CUSTOMER', MENU_ENTRY);

		$this->add_rapp_function(2, _("Add Inter-Branch Customers"),
			"lending/manage/auto_add_interb_customers.php?", 'SA_ADDCUSTINTERB', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("Inventory &Locations"),
			"inventory/manage/locations.php?", 'SA_INVENTORYLOCATION', MENU_MAINTENANCE);
			
		$this->add_extensions();
	}
}


