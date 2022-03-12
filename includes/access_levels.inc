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
/*
	Security sections groups various areas on both functionality and privilege levels.
	Often analytic inquires are available only for management, and configuration
	for administration or management staff. This is why we have those three
	section type inside near every FA module.

	Section codes 0-99 are reserved for core FA functionalities.
	Every security section can contain up to 256 different areas.
	External modules can extend security roles system by adding rows to 
	$security_sections and $security_areas using section codes >=100.
	Security areas and sections created by extension modules/plugins
	have dynamically assigned 3-byte integer codes. The highest byte is zero
	for sections/areas defined in this file, and extid+1 for those defined 
	by extensions 
*/
define('SS_SADMIN',	1<<8);	// site admin
define('SS_SETUP',	2<<8);	// company level setup
define('SS_SPEC',	3<<8);	// special administration

define('SS_SALES_C',11<<8); // configuration
define('SS_SALES',	12<<8);	// transactions
define('SS_SALES_A',13<<8); // analytic functions/reports/inquires

define('SS_PURCH_C',21<<8);
define('SS_PURCH',	22<<8);
define('SS_PURCH_A',23<<8);

define('SS_ITEMS_C',31<<8);
define('SS_ITEMS',	32<<8);
define('SS_ITEMS_A',33<<8);

define('SS_ASSETS_C',36<<8);
define('SS_ASSETS',	37<<8);
define('SS_ASSETS_A',38<<8);

define('SS_MANUF_C',41<<8);
define('SS_MANUF',	42<<8);
define('SS_MANUF_A',43<<8);

define('SS_DIM_C',	51<<8);
define('SS_DIM',	52<<8);
define('SS_DIM_A',	53<<8);

define('SS_GL_C',	61<<8);
define('SS_GL',		62<<8);
define('SS_GL_A',	63<<8);

define('SS_LEND_C',	71<<8); // configuration
define('SS_LEND',	72<<8);	// transactions
define('SS_LEND_A',	73<<8); // analytic functions/reports/inquires

define('SS_REPO_C',	91<<8); // configuration
define('SS_REPO',	92<<8);	// transactions
define('SS_REPO_A',	93<<8); // analytic functions/reports/inquires

$security_sections = array(
 	SS_SADMIN => _("System administration"),
 	SS_SETUP => _("Company setup"),
 	SS_SPEC => _("Special maintenance"),
 	SS_SALES_C => _("Sales configuration"),
 	SS_SALES => _("Sales transactions"),
 	SS_SALES_A => _("Sales related reports"),
 	SS_PURCH_C => _("Purchase configuration"),
 	SS_PURCH => _("Purchase transactions"),
 	SS_PURCH_A => _("Purchase analytics"),
 	SS_ITEMS_C => _("Inventory configuration"),
 	SS_ITEMS => _("Inventory operations"),
 	SS_ITEMS_A => _("Inventory analytics"),
	SS_ASSETS_C => _("Fixed Assets configuration"),
	SS_ASSETS => _("Fixed Assets operations"),
	SS_ASSETS_A => _("Fixed Assets analytics"),
 	SS_MANUF_C => _("Manufacturing configuration"),
 	SS_MANUF => _("Manufacturing transactions"),
 	SS_MANUF_A => _("Manufacturing analytics"),

 	SS_DIM_C => _("Dimensions configuration"),
 	SS_DIM => _("Dimensions"),
 	SS_GL_C => _("Banking & GL configuration"),
 	SS_GL => _("Banking & GL transactions"),
 	SS_GL_A => _("Banking & GL analytics"),

 	SS_LEND_C => _("Lending configuration"),
 	SS_LEND => _("Lending transactions"),
 	SS_LEND_A => _("Lending analytics"),

 	SS_REPO_C => _("Repo configuration"),
 	SS_REPO => _("Repo transactions"),
 	SS_REPO_A => _("Repo analytics")
);

/*
	This table stores security areas available in FA. 
	Key is area identifier used to check user rights, values are
	codes stored for each role in security_roles table and description used
	in roles editor.

	Set of allowed access areas codes is retrieved during user login from
	security_roles table, and cached in user profile.

	Special value 'SA_OPEN' is used for publicly available pages like login/logout.
*/
$security_areas =array(
//
//	Site administration
//
	'SA_CREATECOMPANY' =>array(SS_SADMIN|1, _("Install/update companies")),
	'SA_CREATELANGUAGE' => array(SS_SADMIN|2, _("Install/update languages")),
	'SA_CREATEMODULES' => array(SS_SADMIN|3, _("Install/upgrade modules")),
	'SA_SOFTWAREUPGRADE' => array(SS_SADMIN|4, _("Software upgrades")),
//
//	Company setup
//
	'SA_SETUPCOMPANY' => array(SS_SETUP|1, _("Company parameters")),
	'SA_SECROLES' => array(SS_SETUP|2, _("Access levels edition")),
	'SA_USERS' => array(SS_SETUP|3, _("Users setup")),
	'SA_POSSETUP' => array(SS_SETUP|4, _("Point of sales definitions")),
	'SA_PRINTERS' => array(SS_SETUP|5, _("Printers configuration")),
	'SA_PRINTPROFILE' => array(SS_SETUP|6, _("Print profiles")),
	'SA_PAYTERMS' => array(SS_SETUP|7, _("Payment terms")),
	'SA_SHIPPING' => array(SS_SETUP|8, _("Shipping ways")),
	'SA_CRSTATUS' => array(SS_SETUP|9, _("Credit status definitions changes")),
	'SA_INVENTORYLOCATION' => array(SS_SETUP|10, _("Inventory locations changes")),
	'SA_INVENTORYMOVETYPE'  => array(SS_SETUP|11, _("Inventory movement types")),
	'SA_WORKCENTRES' => array(SS_SETUP|12, _("Manufacture work centres")),
	'SA_FORMSETUP' => array(SS_SETUP|13, _("Forms setup")),
	'SA_CRMCATEGORY' => array(SS_SETUP|14, _("Contact categories")),
	'SA_PLCYBUILDER' => array(SS_SETUP|14, _("Policy Builders")),
	'SA_CHECKOPBLDR' => array(SS_SETUP|14, _("Checkout Operator Builder")),
//
// Special and common functions
//
	'SA_VOIDTRANSACTION' => array(SS_SPEC|1, _("Voiding transactions")),
	'SA_BACKUP' => array(SS_SPEC|2, _("Database backup/restore")),
	'SA_VIEWPRINTTRANSACTION' => array(SS_SPEC|3, _("Common view/print transactions interface")),
	'SA_ATTACHDOCUMENT' => array(SS_SPEC|4, _("Attaching documents")),
	'SA_SETUPDISPLAY' => array(SS_SPEC|5, _("Display preferences")),
	'SA_CHGPASSWD' => array(SS_SPEC|6, _("Password changes")),
	'SA_EDITOTHERSTRANS' => array(SS_SPEC|7, _("Edit other users transactions")),
	'SA_BRANCHAREA' => array(SS_SPEC|8, _("Branch Area Setup")),
	'SA_BRANCHPLCY' => array(SS_SPEC|9, _("Branch Policy Setup")),
	'SA_COMPANYTYP' => array(SS_SPEC|10, _("Company Type Setup")),
	//Added by spyrax10 8 Mar 2022
	'SA_CSV' => array(SS_SPEC|11, _("Upload CSV Template File")),
	//
//
// Sales related functionality
//
	'SA_SALESTYPES' => array(SS_SALES_C|1, _("Sales Price Code")),
	'SA_SALESPRICE' => array(SS_SALES_C|2, _("Sales prices edition")),
	'SA_SALESMAN' => array(SS_SALES_C|3, _("Sales staff maintenance")),
	'SA_SALESAREA' => array(SS_SALES_C|4, _("Sales areas maintenance")),
	'SA_SALESGROUP' => array(SS_SALES_C|5, _("Sales groups changes")),
	'SA_STEMPLATE' => array(SS_SALES_C|6, _("Sales templates")),
	'SA_SRECURRENT' => array(SS_SALES_C|7, _("Recurrent invoices definitions")),
	
	'SA_SALES_TYPE' => array(SS_SALES_C|8, _("Sales Type changes")), // Added by Prog6	
	'SA_COLLECTYPE' => array(SS_SALES_C|9, _("Collection Type changes")), // Added by Robert Gwapo	

	//new added by progjr for sales installment 10-23-2020
	'SA_INSTLPLCYTYPS' => array(SS_SALES_C|10, _("Sales Installment Policy Type")),
	'SA_INSTLPLCY' => array(SS_SALES_C|11, _("Sales Installment Policy")),
	'SA_SCASHPRCTYPES' => array(SS_SALES_C|12, _("Cash Price Types")),
	'SA_SCASHPRICE' => array(SS_SALES_C|13, _("Cash Prices edition")),
	
	'SA_HOCBCTYPE' => array(SS_SALES_C|14, _("HOC/BC Type changes")),	// Added by Ronelle 2/22/2021

	'SA_SLINCNTIVTYPE' => array(SS_SALES_C|15, _("Sales Incentive Type")), //progjr 3/19/2021
	'SA_SINCNTVPRICE' => array(SS_SALES_C|16, _("Sales Incentive Pricing")), //progjr 3/19/2021
	'SA_ITEMDSCNT' => array(SS_SALES_C|17, _("Item Discount")), //progjr 10/19/2021
	'SA_ITMAPSUPPORT' => array(SS_SALES_C|18, _("Item AP Support Type")), //progjr 10/26/2021
	'SA_ITMAPSUPPORT' => array(SS_SALES_C|19, _("Item AP Support Price")), //progjr 10/26/2021
	'SA_SALES_TARGET' => array(SS_SALES_C|20, _("Sales Target changes")), // Added by Prog6	(11/09/2021)

	'SA_SALESTRANSVIEW' => array(SS_SALES|1,  _("Sales transactions view")),
	'SA_CUSTOMER' => array(SS_SALES|2,  _("Sales customer and branches changes")),
	'SA_SALESQUOTE' => array(SS_SALES|10, _("Sales quotations")),
	'SA_SALESORDER' => array(SS_SALES|3, _("Sales orders edition")),
	'SA_SALESDELIVERY' => array(SS_SALES|4, _("Sales deliveries edition")),
	'SA_SALESINVOICE' => array(SS_SALES|5, _("Sales invoices edition")),
	'SA_SALESCREDITINV' => array(SS_SALES|6, _("Sales credit notes against invoice")),
	'SA_SALESCREDIT' => array(SS_SALES|7, _("Sales freehand credit notes")),
	'SA_SALESPAYMNT' => array(SS_SALES|8, _("Customer payments entry")),
	'SA_SALESALLOC' => array(SS_SALES|9, _("Customer payments allocation")),

	'SA_MUNIZICODE' => array(SS_SALES|11, _("Municipality and Zipcode")),
	'SA_INSTLPOLICYLIST' => array(SS_SALES|12, _("List of Installment Policy")),	// Added by Ronelle
	'SA_SALES_INVOICE_LIST' => array(SS_SALES|13, _("List of Sales Invoices")),	// Added by Ronelle
	'SA_SALES_ORDER_UPDATE_STATUS' => array(SS_SALES|14, _("Approval Sales Order")),	// Added by Ronelle
	'SA_SALES_INVOICE_OB' => array(SS_SALES|15, _("Sales Invoice for Opening Balances")),	// Added by Ronelle

	'SA_SALES_RETURN_REPLACEMENT' => array(SS_SALES|16, _("Sales Return Replacement")),	// Added by Ronelle
	'SA_SITERMMOD' => array(SS_SALES|17, _("Sales Invoice Term Modification")),	// Added by Ronelle
	'SA_SALESINVOICEREPO' => array(SS_SALES|18, _("Sales Invoice Repossessed")),	// Added by Ronelle
	'SA_RESTRUCTURED' => array(SS_SALES|17, _("Sales Invoice Restructured")),	// Added by Albert

	'SA_CUSTOMERSIMPORTS' => array(SS_SALES|19, _("Customer Import CSV")), // Added by Robert
	'SA_PROOF_CASH' => array(SS_SALES|20, _("Proof of Cash Report")),      // Added by Robert
	'SA_COLLECTION_PERCENTAGE' => array(SS_SALES|21, _("Collection Percentage Target")), // Added by Robert
	'SA_COLLECTION_AMOUNT' => array(SS_SALES|22, _("Collection Amount Target")), // Added by Robert

	'SA_SALESANALYTIC' => array(SS_SALES_A|1, _("Sales analytical reports")),
	'SA_SALESBULKREP' => array(SS_SALES_A|2, _("Sales document bulk reports")),
	'SA_PRICEREP' => array(SS_SALES_A|3, _("Sales prices listing")),
	'SA_SALESMANREP' => array(SS_SALES_A|4, _("Sales staff listing")),
	'SA_CUSTBULKREP' => array(SS_SALES_A|5, _("Customer bulk listing")),
	'SA_CUSTSTATREP' => array(SS_SALES_A|6, _("Customer status report")),
	'SA_CUSTPAYMREP' => array(SS_SALES_A|7, _("Customer payments report")),

	'SA_DAILYCASH' => array(SS_SALES_A|10, _("Daily Cash report")), // Added by Robert Gwapo	
	'SA_SALES_ORDER_APPROVAL'=> array(SS_SALES|14, _("Account Specialist Approval Sales Order")), //Added by Albert
	'SA_SALES_RETURN_UPDATE_STATUS' => array(SS_SALES|16, _("Sales Return Approval")),
	'SA_SALES_RESTRUCTURED_APPROVAL' => array(SS_SALES|16, _("Sales Restructured Approval")),

	//Added by spyrax10
	'SA_SI_UPDATE' => array(SS_SALES|17, _("Update Sales Invoice Transactions")),
	//

//
// Purchase related functions
//
	'SA_PURCHASEPRICING' => array(SS_PURCH_C|1, _("Purchase price changes")),
	'SA_SUPPLIER_GROUP' => array(SS_PURCH_C|2, _("Supplier's Group")),
    //Added by Herald for Suppliers Re order Level
    'SA_SUPPLIERREORDERLEVEL' => array(SS_PURCH_C|3, _("Supplier's Re-order Level")),
    //Added by herald for Popupview security - 11/28/2020
    'SA_SUPPPOPUPLIST' => array(SS_PURCH_C|4, _("Supplier's Masterlist Popup view")),
    
	'SA_SUPPTRANSVIEW' => array(SS_PURCH|1, _("Supplier transactions view")),
	'SA_SUPPLIER' => array(SS_PURCH|2, _("Suppliers changes")),
	'SA_PURCHASEORDER' => array(SS_PURCH|3, _("Purchase order entry")),
	'SA_GRN' => array(SS_PURCH|4, _("Purchase receive")),
    'SA_SUPPLIERINVOICE' => array(SS_PURCH|5, _("Supplier invoices")),
	'SA_GRNDELETE' => array(SS_PURCH|9, _("Deleting GRN items during invoice entry")),
	'SA_SUPPLIERCREDIT' => array(SS_PURCH|6, _("Supplier credit notes")),
	'SA_SUPPLIERPAYMNT' => array(SS_PURCH|7, _("Supplier payments")),
	'SA_SUPPLIERALLOC' => array(SS_PURCH|8, _("Supplier payments allocations")),
    'SA_DRAFTTOPO' => array(SS_PURCH|10, _("Draft To PO")),
    
    
	// Added by Ronelle PR MODULE
	'SA_PURCHASEREQUEST' => array(SS_PURCH|12, _("Purchase Request")),
	'SA_PRTPO' => array(SS_PURCH|13, _("Purchase Request to Order")),
	'SA_RECEIVECONSIGN' => array(SS_PURCH|14, _("Receive Consignment")),
	'SA_DRAFTPOUPDATESTATUS' => array(SS_PURCH|15, _("Approval for Draft PO Status")),
	'SA_DRAFTPRUPDATESTATUS' => array(SS_PURCH|15, _("Approval for Draft PR Status")),
	'SA_RCONPO' => array(SS_PURCH|16, _("Receive Consignment to PO")),
	'SA_COPYFROM_RCON_TO_PO' => array(SS_PURCH|17, _("Copy RR Consignment to Purchase Order")),
	'SA_PR_BRANCH' => array(SS_PURCH|18, _("List of Branch Purchase Request")),
	'SA_PO_BRANCH' => array(SS_PURCH|19, _("List of Branch Purchase Order")),

	'SA_SUPPLIERANALYTIC' => array(SS_PURCH_A|1, _("Supplier analytical reports")),
	'SA_SUPPBULKREP' => array(SS_PURCH_A|2, _("Supplier document bulk reports")),
	'SA_SUPPPAYMREP' => array(SS_PURCH_A|3, _("Supplier payments report")),

	//added by JR for supplier costing types on 10-13-2020
	'SA_SUPPLIERCOSTYP' => array(SS_PURCH|11, _("Supplier Cost Types")),
//
// Inventory 
//
	'SA_ITEM' => array(SS_ITEMS_C|1, _("Stock items add/edit")),
	'SA_SALESKIT' => array(SS_ITEMS_C|2, _("Sales kits")),
	'SA_ITEMCATEGORY' => array(SS_ITEMS_C|3, _("Item categories")),
	'SA_UOM' => array(SS_ITEMS_C|4, _("Units of measure")),
	'SA_BRAND' => array(SS_ITEMS_C|5, _("Brand")),
	'SA_MANUFACTURER' => array(SS_ITEMS_C|6, _("Manufacturer")),
	'SA_DISTRIBUTOR' => array(SS_ITEMS_C|7, _("Distributor")),
	'SA_IMPORTER' => array(SS_ITEMS_C|8, _("Item Importer")),
    'SA_ITEMPOPUPVIEW' => array(SS_ITEMS_C|7, _("Stock Item Popup View")),

	//added by jr for standart cost area type on 10-19-2020
	'SA_SRPAREATYPE' => array(SS_ITEMS_C|8, _("Standard Retail Price Types")),

	// Added by Ronelle
	'SA_SISTOCKLIST' => array(SS_ITEMS_C|9, _("Available Item for SI")),
	'SA_SRSTOCKLIST' => array(SS_ITEMS_C|10, _("Available Item for SR")),
    
	'SA_ITEMSSTATVIEW' => array(SS_ITEMS|1, _("Stock status view")),
	'SA_ITEMSTRANSVIEW' => array(SS_ITEMS|2, _("Stock transactions view")),
	'SA_FORITEMCODE' => array(SS_ITEMS|3, _("Item color codes entry")),
	'SA_LOCATIONTRANSFER' => array(SS_ITEMS|4, _("Inventory location transfers")),
	'SA_INVENTORYADJUSTMENT' => array(SS_ITEMS|5, _("Inventory adjustments")),
    'SA_MERCHANDISETRANSFER' => array(SS_ITEMS|6, _("Merchandise Transfers")),
    'SA_MERCHANDISETRANSFERREPO' => array(SS_ITEMS|12, _("Merchandise Transfers - Repo")),
    //Added by Herald for RR Branch 02/22/2021
    'SA_RRBRANCH' => array(SS_ITEMS|7, _("Receiving Report - Branch")),
    //Added by Herald - 04/12/2021 for Complimentary Items
    'SA_COMPLIMENTARYITEM' => array(SS_ITEMS|8, _("Complimentary Items")),

	//Added by spyrax10 24 Feb 2022
	'SA_INVTY_UPDATE' => array(SS_ITEMS|13, _("Update for Inventory Adjustment Transactions")),
	'SA_INVTY_DRAFT' => array(SS_ITEMS|14, _("Approval for Inventory Adjustment Transactions")),
	'SA_INVTY_POST' => array(SS_ITEMS|15, _("Posting for Inventory Adjustment Transactions")),
	'SA_ITEM_UPLOAD' => array(SS_ITEMS|16, _("Import Item Master (New)")),
	//

    //----Robert Added----//
	'SA_RRTRANSWORLDVIEW' => array(SS_ITEMS|9, _("RR Transworld view")),
	'SA_INVENTORY_TYPE' => array(SS_ITEMS|10, _("Inventory Adjustment Setup")),

	'SA_REORDER' => array(SS_ITEMS_A|1, _("Reorder levels")),
	'SA_ITEMSANALYTIC' => array(SS_ITEMS_A|2, _("Items analytical reports and inquiries")),
	'SA_ITEMSVALREP' => array(SS_ITEMS_A|3, _("Inventory valuation report")),

//
// Fixed Assets
//
	'SA_ASSET' => array(SS_ASSETS_C|1, _("Fixed Asset items add/edit")),
	'SA_ASSETCATEGORY' => array(SS_ASSETS_C|2, _("Fixed Asset categories")),
	'SA_ASSETCLASS' => array(SS_ASSETS_C|4, _("Fixed Asset classes")),

	'SA_ASSETSTRANSVIEW' => array(SS_ASSETS|1, _("Fixed Asset transactions view")),
	'SA_ASSETTRANSFER' => array(SS_ASSETS|2, _("Fixed Asset location transfers")),
	'SA_ASSETDISPOSAL' => array(SS_ASSETS|3, _("Fixed Asset disposals")),
    'SA_DEPRECIATION' => array(SS_ASSETS|4, _("Depreciation")),

	'SA_ASSETSANALYTIC' => array(SS_ASSETS_A|1, _("Fixed Asset analytical reports and inquiries")),

//
// Manufacturing module 
//
	'SA_BOM' => array(SS_MANUF_C|1, _("Bill of Materials")),

	'SA_MANUFTRANSVIEW' => array(SS_MANUF|1, _("Manufacturing operations view")),
	'SA_WORKORDERENTRY' => array(SS_MANUF|2, _("Work order entry")),
	'SA_MANUFISSUE' => array(SS_MANUF|3, _("Material issues entry")),
	'SA_MANUFRECEIVE' => array(SS_MANUF|4, _("Final product receive")),
	'SA_MANUFRELEASE' => array(SS_MANUF|5, _("Work order releases")),

	'SA_WORKORDERANALYTIC' => array(SS_MANUF_A|1, _("Work order analytical reports and inquiries")),
	'SA_WORKORDERCOST' => array(SS_MANUF_A|2, _("Manufacturing cost inquiry")),
	'SA_MANUFBULKREP' => array(SS_MANUF_A|3, _("Work order bulk reports")),
	'SA_BOMREP' => array(SS_MANUF_A|4, _("Bill of materials reports")),
//

// Dimensions
//
	'SA_DIMTAGS' => array(SS_DIM_C|1, _("Dimension tags")),

	'SA_DIMTRANSVIEW' => array(SS_DIM|1, _("Dimension view")),

	'SA_DIMENSION' => array(SS_DIM|2, _("Dimension entry")),

	'SA_DIMENSIONREP' => array(SS_DIM|3, _("Dimension reports")),
//
// Banking and General Ledger
//
	'SA_ITEMTAXTYPE' => array(SS_GL_C|1, _("Item tax type definitions")),
	'SA_GLACCOUNT' => array(SS_GL_C|2, _("GL accounts edition")),
	'SA_GLACCOUNTGROUP' => array(SS_GL_C|3, _("GL account groups")),
	'SA_GLACCOUNTCLASS' => array(SS_GL_C|4, _("GL account classes")),
	'SA_QUICKENTRY' => array(SS_GL_C|5, _("Quick GL entry definitions")),
	'SA_CURRENCY' => array(SS_GL_C|6, _("Currencies")),
	'SA_BANKACCOUNT' => array(SS_GL_C|7, _("Bank accounts")),
	'SA_TAXRATES' => array(SS_GL_C|8, _("Tax rates")),
	'SA_TAXGROUPS' => array(SS_GL_C|12, _("Tax groups")),
	'SA_FISCALYEARS' => array(SS_GL_C|9, _("Fiscal years maintenance")),
	'SA_GLSETUP' => array(SS_GL_C|10, _("Company GL setup")),
	'SA_GLACCOUNTTAGS' => array(SS_GL_C|11, _("GL Account tags")),
	'SA_GLCLOSE' => array(SS_GL_C|14, _("Closing GL transactions")),
	'SA_GLREOPEN' => array(SS_GL_C|15, _("Reopening GL transactions")), // see below
	'SA_MULTIFISCALYEARS' => array(SS_GL_C|13, _("Allow entry on non closed Fiscal years")),

	'SA_BANKTRANSVIEW' => array(SS_GL|1, _("Bank transactions view")),
	'SA_GLTRANSVIEW' => array(SS_GL|2, _("GL postings view")),
	'SA_EXCHANGERATE' => array(SS_GL|3, _("Exchange rate table changes")),
	'SA_PAYMENT' => array(SS_GL|4, _("Bank payments")),
	'SA_DEPOSIT' => array(SS_GL|5, _("Bank deposits")),
	'SA_BANKTRANSFER' => array(SS_GL|6, _("Bank account transfers")),
	'SA_RECONCILE' => array(SS_GL|7, _("Bank reconciliation")),
	'SA_JOURNALENTRY' => array(SS_GL|8, _("Manual journal entries")),
	'SA_BUDGETENTRY' => array(SS_GL|9, _("Budget edition")),
	'SA_STANDARDCOST' => array(SS_GL|10, _("Item standard cost")),
	'SA_BANKJOURNAL' => array(SS_GL|11, _("Journal entries to bank related accounts")),
	'SA_ACCRUALS' => array(SS_GL|12, _("Revenue / Cost Accruals")),

	/* Added by Ronelle 2/20/2021 for MCode Listing */
	'SA_SEARCHSL' => array(SS_GL|13, _("List of Supplier, Branch, Customer and Employee")),
	/* */
	
	//Added by spyrax10 24 Feb 2022
	'SA_INTER_BANK_DRAFT' => array(SS_GL|14, _("Review Bank Interbranch Transactions")),
	'SA_INTER_BANK_POST' => array(SS_GL|15, _("Post Bank Interbranch Transactions")),
	//

	'SA_GLANALYTIC' => array(SS_GL_A|1, _("GL analytical reports and inquiries")),
	'SA_TAXREP' => array(SS_GL_A|2, _("Tax reports and inquiries")),
	'SA_BANKREP' => array(SS_GL_A|3, _("Bank reports and inquiries")),
	'SA_GLREP' => array(SS_GL_A|4, _("GL reports and inquiries")),

//
// Lending related functions
//
	'SA_ARINVCINSTL' => array(SS_LEND|1, _("A/R Installment")),
	'SA_LCUSTAMORT'=> array(SS_LEND|2, _("Office &Collection Receipt")),
	'SA_LINTERBRANCH'=> array(SS_LEND|3, _("Interbranch &Collection Receipt")),
	'SA_APCUSTDPOPEN'=> array(SS_LEND|4, _("AP Customer Deposit Opening")),

	//'SA_LENDING_A' => array(SS_LEND_A|1, _("Lending analytics")),
	'SA_INVCINQ'=> array(SS_LEND_A|1, _("Incoming Invoice Inquiry")),
	'SA_INTRBPAYINQ'=> array(SS_LEND_A|2, _("Incoming Inter-branch Payments Inquiry")),
	'SA_ALLOCPYMNT'=> array(SS_LEND_A|3, _("Payment Allocation")),
	'SA_ARINVCINQ' => array(SS_LEND_A|4, _("A/R Installment Inquiry")),
	
	'SA_ADDCUSTINTERB'=> array(SS_LEND_C|1, _("Add Inter-Branch Customers")),

//
// Repossessed
//

	'SA_GRNREPO' => array(SS_REPO|1, _("Receiving Report Repo")),
	'SA_TEMPREPOINQRY' => array(SS_REPO|2, _("Temporary Repo Lending inquiry")),

	'SA_REPOREPRICE' => array(SS_REPO|3, _("Repossess Item Pricing")),

);

if (!@$SysPrefs->allow_gl_reopen)
	unset($security_areas['SA_GLREOPEN']);
/*
	This function should be called whenever we want to extend core access level system
	with new security areas and/or sections i.e.: 
	. on any page with non-standard security areas
	. in security roles editor
	The call should be placed between session.inc inclusion and page() call.
	Up to 155 security sections and 155 security areas for any extension can be installed.
*/
function add_access_extensions()
{
	global $security_areas, $security_sections, $installed_extensions;

	foreach($installed_extensions as $extid => $ext) {
		$accext = hook_invoke($ext['package'], 'install_access', $dummy);
		if ($accext == null) continue;

		$scode = 100;
		$acode = 100;
		$extsections = $accext[1];
		$extareas = $accext[0];
		$extcode = $extid<<16;
		
		$trans = array();
		foreach($extsections as $code =>$name) {
			$trans[$code] = $scode<<8;
			// reassign section codes
			$security_sections[$trans[$code]|$extcode] = $name;
			$scode++;
		}
		foreach($extareas as $code => $area) {
			$section = $area[0]&0xff00;
			// extension modules:
			// if area belongs to nonstandard section
			// use translated section codes and
			// preserve lower part of area code
			if (isset($trans[$section])) {
				$section = $trans[$section];
			} 
				// otherwise assign next available
				// area code >99
			$area[0] = $extcode | $section | ($acode++);
			$security_areas[$code] = $area;
		}
	}
}

function check_edit_access($name)
{
	global $input_security;

	$access = @$input_security[$name];

	if (!$access)
		$access = @$input_security['']; // default access level

	if (!$access)
		return true; // if constraint is not defined edit access is allowed

	return  user_check_access($access);
}
/*
	Returns POST value or null if edit access to $name control is forbidden.
*/
function access_post($name, $dflt=null)
{
	if (!check_edit_access($name))
		return $dflt;
	else
		return get_post($name, $dflt);
}

/*
	Returns numeric input value or null if edit access to $name control is forbidden.
*/
function access_num($name, $dflt=null)
{
	if (!check_edit_access($name))
		return $dflt;
	else
		return input_num($name, $dflt);
}
