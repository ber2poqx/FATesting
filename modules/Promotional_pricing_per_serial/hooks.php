<?php
define ('SS_PROMOTIONAL_PRICING', 101<<8);
class hooks_Promotional_pricing_per_serial extends hooks {
	var $module_name = 'Promotional Pricing - Per Serial'; 

	/*
		Install additonal menu options provided by module
	*/
	function install_options($app) {
		global $path_to_root;

		switch($app->id) {
			case 'stock':
				$app->add_rapp_function(3, _('Promotional Pricing - Per Serial'), 
					$path_to_root.'/modules/Promotional_pricing_per_serial/promotional_pricing_per_serial.php', 'SA_PROMOTIONAL_PRICING', MENU_MAINTENANCE);
		}
	}

	function install_access()
	{
		$security_sections[SS_PROMOTIONAL_PRICING] =	_("Promotional Pricing - Per Serial");

		$security_areas['SA_PROMOTIONAL_PRICING'] = array(SS_PROMOTIONAL_PRICING|101, _("Promotional Pricing - Per Serial"));

		return array($security_areas, $security_sections);
	}
}
