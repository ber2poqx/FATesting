<?php
define ('SS_IMPORTCSVITEMS', 105<<8);
class hooks_import_items extends hooks {
	var $module_name = 'Import CSV Items Master'; 
	/*
		Install additonal menu options provided by module
	*/
	function install_options($app) {
		global $path_to_root;

		switch($app->id) {
			case 'stock':
				$app->add_rapp_function(2, _('Import CSV Items Master'), 
					$path_to_root.'/modules/import_items/import_items.php', 'SA_CSVIMPORT', MENU_MAINTENANCE);
		}
	}

	function install_access()
	{
		$security_sections[SS_IMPORTCSVITEMS] =	_("Import CSV Items Master");
		$security_areas['SA_CSVIMPORT'] = array(SS_IMPORTCSVITEMS|105, _("Import CSV Stock Items"));

		return array($security_areas, $security_sections);
	}
}
