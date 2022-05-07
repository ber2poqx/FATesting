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
class inventory_app extends application
{
	function __construct()
	{
		parent::__construct("stock", _($this->help_context = "&Items and Inventory"));

		$this->add_module(_("Transactions"));
		$this->add_lapp_function(0, _("Inventory Location &Transfers"),
		    "inventory/location_transfers.php", 'SA_LOCATIONTRANSFER', MENU_TRANSACTION);
		$this->add_lapp_function(0, _("Merchandise &Transfers"),
		    "inventory/merchandise_transfers.php?transtype=out", 'SA_MERCHANDISETRANSFER', MENU_TRANSACTION);
		$this->add_lapp_function(0, _("Merchandise Transfers - Repo"),
		    "inventory/merchandise_transfers_repo.php", 'SA_MERCHANDISETRANSFERREPO', MENU_TRANSACTION);
		$this->add_lapp_function(0, _("Receiving Report - Branch"),
		    "inventory/merchandise_transfers.php?transtype=in", 'SA_MERCHANDISETRANSFER', MENU_TRANSACTION);
		
		$this->add_rapp_function(0, _("Inventory &Adjustments"),
			"inventory/inquiry/adjustment_view.php?", 'SA_INVENTORYADJUSTMENT', MENU_TRANSACTION); //Modified by spyrax10
		$this->add_rapp_function(0, _("Inventory &Adjustments - Repo"),
			"inventory/inquiry/adjustment_repo_view.php?", 'SA_INVENTORYADJUSTMENT', MENU_TRANSACTION); //Added by spyrax10
		$this->add_rapp_function(0, _("&Complimentary Items"),
		    "inventory/complimentary_items.php", 'SA_COMPLIMENTARYITEM', MENU_TRANSACTION);
		
		$this->add_module(_("Inquiries and Reports"));
		$this->add_lapp_function(1, _("Inventory Item &Movements"),
			"inventory/inquiry/stock_movements.php?", 'SA_ITEMSTRANSVIEW', MENU_INQUIRY);
		$this->add_lapp_function(1, _("Inventory Item &Status"),
			"inventory/inquiry/stock_status.php?", 'SA_ITEMSSTATVIEW', MENU_INQUIRY);

		//---Robert Added---//
		$this->add_lapp_function(1, _("Inventory Transworld &Monitoring"),
			"inventory/inquiry/inventory_transworld_movements.php?", 'SA_RRTRANSWORLDVIEW', MENU_INQUIRY);

		$this->add_rapp_function(1, _("Inventory &Reports"),
			"reporting/reports_main.php?Class=2", 'SA_ITEMSTRANSVIEW', MENU_REPORT);

		$this->add_module(_("Maintenance"));
		$this->add_lapp_function(2, _("&Items"),
			"inventory/manage/items.php?", 'SA_ITEM', MENU_ENTRY);
		$this->add_lapp_function(2, _("&Item Color Code"),
			"inventory/manage/item_codes.php?", 'SA_FORITEMCODE', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("Sales &Kits"),
			"inventory/manage/sales_kits.php?", 'SA_SALESKIT', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("Item &Categories"),
			"inventory/manage/item_categories.php?", 'SA_ITEMCATEGORY', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("Inventory &Locations"),
			"inventory/manage/locations.php?", 'SA_INVENTORYLOCATION', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("&Units of Measure"),
			"inventory/manage/item_units.php?", 'SA_UOM', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("&Brand"),
			"inventory/manage/item_brand.php?", 'SA_BRAND', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("&Reorder Levels"),
			"inventory/reorder_level.php?", 'SA_REORDER', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("Sub-Category Setup"),
			"inventory/manage/item_distributor.php?", 'SA_DISTRIBUTOR', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("Classification Setup"),
			"inventory/manage/item_importer.php?", 'SA_IMPORTER', MENU_MAINTENANCE);

		$this->add_lapp_function(2, _("Inventory Adjustment Setup"),
			"inventory/manage/inventory_setup.php?", 'SA_INVENTORY_TYPE', MENU_MAINTENANCE);

		$this->add_rapp_function(2, _("&Made-in Setup"),
			"inventory/manage/item_manufacturer.php?", 'SA_MANUFACTURER', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("SRP Types Setup"),
			"inventory/manage/item_srp_area_type.php?", 'SA_SRPAREATYPE', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("Item AP Support Type"),
			"inventory/item_apsupport.php?mngtype=type", 'SA_ITMAPSUPPORT', MENU_MAINTENANCE);

		//Added by spyrax10 10 Mar 2022
		// $this->add_lapp_function(2, _("Import Item Master (New)"),
		// 	"inventory/manage/item_upload.php", 'SA_ITEM_UPLOAD', MENU_MAINTENANCE
		// );
		if ($_SESSION["wa_current_user"]->company == 0) {
			$this->add_rapp_function(2, _("Import Item Color Code"),
				"inventory/manage/color_upload.php", 'SA_FORITEMCODE', MENU_MAINTENANCE
			);
		}
		//

		$this->add_module(_("Pricing and Costs"));
		$this->add_lapp_function(3, _("Cash &Pricing"),
		"inventory/cash_price.php?", 'SA_SCASHPRICE', MENU_MAINTENANCE);
		$this->add_lapp_function(3, _("LCP &Pricing"),
			"inventory/prices.php?", 'SA_SALESPRICE', MENU_MAINTENANCE);
		$this->add_lapp_function(3, _("System &Cost"),
			//"inventory/purchasing_data.php?", 'SA_PURCHASEPRICING', MENU_MAINTENANCE);
			"inventory/supplier_cost.php?", 'SA_PURCHASEPRICING', MENU_MAINTENANCE);
		$this->add_rapp_function(3, _("SRP Pricing"),
			//"inventory/cost_update.php?", 'SA_STANDARDCOST', MENU_MAINTENANCE);
			"inventory/standard_cost.php?", 'SA_STANDARDCOST', MENU_MAINTENANCE);
		$this->add_rapp_function(3, _("Incentive Pricing"),
			"inventory/incentive_price.php?", 'SA_SINCNTVPRICE', MENU_MAINTENANCE);
		$this->add_rapp_function(3, _("Item AP Support Price"),
			"inventory/item_apsupport.php?mngtype=price", 'SA_ITMAPSUPPORT', MENU_MAINTENANCE);
		$this->add_rapp_function(3, _("Repossess Item Pricing"),
			"repossess/repo_repricing.php?", 'SA_REPOREPRICE', MENU_MAINTENANCE);
		$this->add_lapp_function(3, _("Item Discount"),
			"inventory/item_discount.php?", 'SA_ITEMDSCNT', MENU_MAINTENANCE);
		$this->add_extensions();
	}
}

