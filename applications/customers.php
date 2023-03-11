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
class customers_app extends application 
{
	function __construct() 
	{
		parent::__construct("orders", _($this->help_context = "&Sales"));
	
		$this->add_module(_("Transactions"));
		$this->add_lapp_function(0, _("Sales &Quotation Entry"),
			"sales/sales_order_entry.php?NewQuotation=Yes", 'SA_SALESQUOTE', MENU_TRANSACTION);
		// $this->add_lapp_function(0, _("Sales &Order Entry"),
		// 	"sales/sales_order_entry.php?NewOrder=Yes", 'SA_SALESORDER', MENU_TRANSACTION);
		
		$this->add_lapp_function(0, _("Sales Order"),
			"sales/inquiry/sales_orders_view.php?type=30", 'SA_SALESTRANSVIEW', MENU_TRANSACTION
		);

		//Modified by spyrax10 15 2022
		$this->add_lapp_function(0, _("Sales Delivery Inqiury List"),
			"sales/inquiry/delivery_inquiry.php?", 'SA_SALESDELIVERY', MENU_TRANSACTION
		);
		//
		
		// $this->add_lapp_function(0, _("Sales &Invoice Installment"),
		// 	"sales/sales_order_entry.php?NewInvoice=0", 'SA_SALESINVOICE', MENU_TRANSACTION);
		// $this->add_lapp_function(0, _("Sales &Invoice Cash"),
		// 	"sales/sales_invoice_cash.php?NewInvoice=0", 'SA_SALESINVOICE', MENU_TRANSACTION);
		$this->add_lapp_function(0, _("Sales Invoice Cash/Installment"),
			"sales/sales_invoice_list.php?", 'SA_SALES_INVOICE_LIST', MENU_TRANSACTION);
		
		//Modified by spyrax10 13 Jul 2022
		$this->add_lapp_function(0, _("Sales Return Replacement"),
			"sales/sales_return_replacement.php?type=new", 'SA_SR_INQ', MENU_TRANSACTION
		);
		//
		$this->add_lapp_function(0, _("Sales Invoice Opening Balances"),
			"sales/sales_invoice_ob_list.php?", 'SA_SALES_INVOICE_OB', MENU_TRANSACTION);
		
		//Modified by spyrax10 13 Jul 2022
		$this->add_lapp_function(0, _("Sales Invoice Term Modification"),
			"sales/sales_invoice_term_modification.php?", 'SA_SITERM_INQ', MENU_TRANSACTION
		);
		$this->add_lapp_function(0, _("Sales Invoice Restructured"),//Added by Albert 12/08/2021
			"sales/sales_invoice_restructured.php?", 'SA_SIRES_INQ', MENU_TRANSACTION
		);
		//
		
		//Modified by spyrax10 18 Jun 2022
		$this->add_lapp_function(0, _("Sales Order Repossessed"),	//Added by Albert
			"sales/inquiry/sales_orders_repo_view.php?", 'SA_SO_REPO_VIEW', MENU_TRANSACTION
		);
		//

		$this->add_lapp_function(0, _("Sales Invoice Repossessed"),
			"sales/si_repo.php?", 'SA_SALESINVOICEREPO', MENU_TRANSACTION);
		$this->add_lapp_function(0, _("Sales Return Replacement Repossessed"),
			"sales/sales_return_replacement.php?type=repo", 'SA_SALES_RETURN_REPLACEMENT', MENU_TRANSACTION);
			
		$this->add_lapp_function(0, "","");
		$this->add_lapp_function(0, _("&Delivery Against Sales Orders"),
			"sales/inquiry/sales_orders_view.php?OutstandingOnly=1", 'SA_SALESDELIVERY', MENU_TRANSACTION);
		
		//Modified by spyrax10 18 Jun 2022
		$this->add_lapp_function(0, _("&Invoice Against Sales Delivery"),
			"sales/inquiry/sales_deliveries_view.php?OutstandingOnly=1", 'SA_SI_SD', MENU_TRANSACTION
		);

		$this->add_rapp_function(0, _("&Template Delivery"),
			"sales/inquiry/sales_orders_view.php?DeliveryTemplates=Yes", 'SA_SALESDELIVERY', MENU_TRANSACTION);

		//Modified by spyrax10 18 Jun 2022
		$this->add_rapp_function(0, _("&Template Invoice"),
			"sales/inquiry/sales_orders_view.php?InvoiceTemplates=Yes", 'SA_SI_TEMPLATE', MENU_TRANSACTION
		);
		$this->add_rapp_function(0, _("&Create and Print Recurrent Invoices"),
			"sales/create_recurrent_invoices.php?", 'SA_SI_REC', MENU_TRANSACTION
		);
		//

		$this->add_rapp_function(0, "","");

		$this->add_rapp_function(0, _("Office &Collection Receipt"),
			"lending/customer_amort_receipt.php?type=amort", 'SA_LCUSTAMORT', MENU_TRANSACTION);
		$this->add_rapp_function(0, _("Cash &Invoice Receipt"),
			"lending/customer_amort_receipt.php?type=cash", 'SA_LCUSTAMORT', MENU_TRANSACTION);
		$this->add_rapp_function(0, _("Payment Allocation"),
			"lending/allocation_payment.php?", 'SA_ALLOCPYMNT', MENU_TRANSACTION);
		$this->add_rapp_function(0, _("AP Customer Deposit Opening"),
			"lending/customer_ap_opening.php?", 'SA_APCUSTDPOPEN', MENU_TRANSACTION);
			$this->add_rapp_function(0, _("Inter-branch (From Not FA)"),
			"lending/inquiry/interbranch_payments_inquiry.php?type=aloneinb", 'SA_INTRBPAYINQ', MENU_TRANSACTION);

		$this->add_rapp_function(0, "","");

		$this->add_rapp_function(0, _("Customer &Payments"),
			"sales/customer_payments.php?", 'SA_SALESPAYMNT', MENU_TRANSACTION);

		//Modified by spyrax10 18 Jun 2022
		$this->add_lapp_function(0, _("Invoice &Prepaid Orders"),
			"sales/inquiry/sales_orders_view.php?PrepaidOrders=Yes", 'SA_SI_PREPAID', MENU_TRANSACTION
		);
		//
		$this->add_rapp_function(0, _("Customer &Credit Notes"),
			"sales/credit_note_entry.php?NewCredit=Yes", 'SA_SALESCREDIT', MENU_TRANSACTION);
		$this->add_rapp_function(0, _("&Allocate Customer Payments or Credit Notes"),
			"sales/allocations/customer_allocation_main.php?", 'SA_SALESALLOC', MENU_TRANSACTION);

		$this->add_rapp_function(0, "","");
		$this->add_rapp_function(0, _("Temporary Repo Accounts &Inquiry"),
			"repossess/inquiry/temporary_repo_accounts.php?", 'SA_TEMPREPOINQRY', MENU_TRANSACTION); //Robert Added
		$this->add_rapp_function(0, _("Receiving Report &Repo"),
			"repossess/manage/repo_accounts.php?rtype=REPO", 'SA_GRNREPO', MENU_TRANSACTION); //Robert Added

		$this->add_module(_("Inquiries and Reports"));
		$this->add_lapp_function(1, _("Incoming Inter-branch Payments Inquiry"),
			"lending/inquiry/interbranch_payments_inquiry.php?", 'SA_INTRBPAYINQ', MENU_INQUIRY);
		
		$this->add_lapp_function(1, "","");
		$this->add_lapp_function(1, _("A/R Installment Inquiry"),
			"lending/inquiry/ar_installment_inquiry.php?", 'SA_ARINVCINQ', MENU_INQUIRY);

		//Modified by spyrax10 18 Jun 2022
		$this->add_lapp_function(1, _("Sales Quotation I&nquiry"),
			"sales/inquiry/sales_orders_view.php?type=32", 'SA_SO_QUOTE_VIEW', MENU_INQUIRY
		);
		//

		$this->add_lapp_function(1, _("Customer Transaction &Inquiry"),
			"sales/inquiry/customer_inquiry.php?", 'SA_SALESTRANSVIEW', MENU_INQUIRY);
		$this->add_lapp_function(1, _("Customer Allocation &Inquiry"),
			"sales/inquiry/customer_allocation_inquiry.php?", 'SA_SALESALLOC', MENU_INQUIRY);

		//Modified by spyrax10 18 Jun 2022
		$this->add_rapp_function(1, _("Customer and Sales &Reports"),
			"reporting/reports_main.php?Class=0", 'SA_SALES_REPORT', MENU_REPORT
		);
		//

		$this->add_rapp_function(1, _("Proof Of Cash And &Reports"),
			"sales/manage/proof_of_cash.php?Class=0", 'SA_PROOF_CASH', MENU_REPORT); //Robert Added

		$this->add_module(_("Maintenance"));
		$this->add_lapp_function(2, _("Add and Manage &Customers"),
			"sales/manage/customers.php?", 'SA_CUSTOMER', MENU_ENTRY);
		$this->add_lapp_function(2, _("Customer &Branches"),
			"sales/manage/customer_branches.php?", 'SA_CUSTOMER', MENU_ENTRY);
		$this->add_lapp_function(2, _("Sales &Groups"),
			"sales/manage/sales_groups.php?", 'SA_SALESGROUP', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("Recurrent &Invoices"),
			"sales/manage/recurrent_invoices.php?", 'SA_SRECURRENT', MENU_MAINTENANCE);

		$this->add_rapp_function(2, _("Sales &Price Setup - LCP"),
			"sales/manage/sales_types.php?", 'SA_SALESTYPES', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("Sales &Persons"),
			"sales/manage/sales_people.php?", 'SA_SALESMAN', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("Collector &Area Setup"),
			"sales/manage/sales_areas.php?", 'SA_SALESAREA', MENU_MAINTENANCE);
			
		$this->add_rapp_function(2, _("Sales &Type"),                              // Added by:
			"sales/manage/sales_type.php?", 'SA_SALES_TYPE', MENU_MAINTENANCE);   //     Prog6
			
		$this->add_lapp_function(2, _("Sales Tar&get Setup"),                              // Added by:
			"sales/sales_target.php?", 'SA_SALES_TARGET', MENU_MAINTENANCE);   //     Prog6 (11/06/2021)		
		/*$this->add_lapp_function(2, _("Sales Target Qu&antity Setup"),                              // Added by:
			"sales/manage/sales_target_quantity.php?", 'SA_SALES_TARGET', MENU_MAINTENANCE);*/   //     Prog6 (11/10/2021)
		
		$this->add_rapp_function(2, _("Sales Reason"),                              // Added by:
			"sales/manage/sales_reason.php?", 'SA_SALES_TYPE', MENU_MAINTENANCE);   //     AlbertP



		$this->add_rapp_function(2, _("Collections &Type"),                              // Added by:
			"sales/manage/collection_type.php?", 'SA_COLLECTYPE', MENU_MAINTENANCE);   //     Robert Gwapo

		$this->add_lapp_function(2, _("Municipality &Zipcode Setup"),
			"sales/manage/sales_municode.php?", 'SA_MUNIZICODE', MENU_MAINTENANCE); //Added by: Robert

		$this->add_lapp_function(2, _("Import &Customers CSV"),
			"sales/manage/sales_customer_import.php?", 'SA_CUSTOMERSIMPORTS', MENU_MAINTENANCE); //Added by: Robert
			
		$this->add_rapp_function(2, _("Credit &Status Setup"),
			"sales/manage/credit_status.php?", 'SA_CRSTATUS', MENU_MAINTENANCE);

		// Added Comment By Ronelle

		// Add progJR
		$this->add_lapp_function(2, _("Financing &Rate Setup"),
			"sales/manage/sales_installment_policy.php?", 'SA_INSTLPLCY', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("Installment &Policy Setup"),
			"sales/manage/sales_installment_policy_type.php?", 'SA_INSTLPLCYTYPS', MENU_ENTRY);
		$this->add_rapp_function(2, _("Cash Price Setup"),
			"sales/manage/sales_cash_types.php?", 'SA_SCASHPRCTYPES', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("Sales Incentive Setup"),
			"sales/manage/sales_incentive_type.php?", 'SA_SLINCNTIVTYPE', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("Add Inter-Branch Customers"),
			"lending/manage/auto_add_interb_customers.php?", 'SA_ADDCUSTINTERB', MENU_MAINTENANCE);

		//Added by spyrax10 23 Sep 2022
		$this->add_lapp_function(2, _("Delivery Discrepancy Monitoring"),
			"sales/manage/delivery_monitoring.php?", 'SA_FIX_DEL', MENU_MAINTENANCE
		);

		$this->add_lapp_function(2, _("Pending Sales Order Discount"),
			"sales/manage/pending_discount.php?", 'SA_SALESORDER', MENU_MAINTENANCE
		);
		//

		/* Added by Ronelle 2/22/2021 */
		$this->add_rapp_function(2, _("HOC/BC Types"),
			"sales/manage/hocbc_type.php?", 'SA_HOCBCTYPE', MENU_MAINTENANCE);

		$this->add_rapp_function(2, _("Collection Percentage &Target Setup"),
			"sales/manage/collection_target.php?Class=0", 'SA_COLLECTION_PERCENTAGE', MENU_MAINTENANCE); //Robert Added

		$this->add_rapp_function(2, _("Collection Amount &Target Setup"),
			"sales/manage/collection_target_amount.php?Class=0", 'SA_COLLECTION_AMOUNT', MENU_MAINTENANCE); //Robert Added
		/* */
		$this->add_extensions();
	}
}


