<?php
define ('SS_INVTYOPENBAL', 105<<8);
class hooks_Inventory_Beginning_Balances extends hooks {
	var $module_name = 'Inventory Beginning Balances'; 

	/*
		Install additonal menu options provided by module
	*/
	function install_options($app) {
		global $path_to_root;

		switch($app->id) {
			case 'stock':
				$app->add_rapp_function(0, _('Inventory Opening Balances'), 
					$path_to_root.'/modules/Inventory_Beginning_Balances/inventory_view.php', 'SA_INVTYOPEN_LIST'
				);
		}
	}

	function install_access() {
		$security_sections[SS_INVTYOPENBAL] = _("Inventory Opening Balances");

		$security_areas['SA_INVTYOPEN_ENTRY'] = array(SS_INVTYOPENBAL|105, _("Inventory Opening Balances Entry"));
		$security_areas['SA_INVTYOPEN_LIST'] = array(SS_INVTYOPENBAL|106, _("Inventory Opening Balances Inquiry List"));

		return array($security_areas, $security_sections);
	}
}
