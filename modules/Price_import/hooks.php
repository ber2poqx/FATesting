<?php
define ('SS_IMPORTCSVPRICE', 105<<8);
class hooks_Price_Import extends hooks {
	var $module_name = 'Import Price List'; 

	/*
		Install additonal menu options provided by module
	*/
	function install_options($app) {
		global $path_to_root;

		switch($app->id) {
			case 'stock':
				$app->add_rapp_function(2, _('Import Price List'), 
					$path_to_root.'/modules/Price_import/price_import.php', 'SA_IMPORTCSVPRICE');
		}
	}

	function install_access()
	{
		$security_sections[SS_IMPORTCSVPRICE] =	_("Import Price List");

		$security_areas['SA_IMPORTCSVPRICE'] = array(SS_IMPORTCSVPRICE|105, _("Import Price List"));

		return array($security_areas, $security_sections);
	}
}
