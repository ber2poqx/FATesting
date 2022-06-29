<?php
define ('SS_IMPORTCSVITEMS_SAMPLE', 105<<8);
class hooks_import_items extends hooks {
	var $module_name = 'Import CSV Items'; 

	/*
		Install additonal menu options provided by module
	*/
	function install_options($app) {
		global $path_to_root;

		switch($app->id) {
			case 'stock':
				$app->add_rapp_function(2, _('Import CSV Items'), 
					$path_to_root.'/modules/testing_1/import_items.php', 'SA_CSVIMPORT_SAMPLE');
		}
	}

	function install_access()
	{
		$security_sections[SS_IMPORTCSVITEMS_SAMPLE] =	_("Import CSV Items");

		$security_areas['SA_CSVIMPORT_SAMPLE'] = array(SS_IMPORTCSVITEMS_SAMPLE|105, _("Import CSV Items"));

		return array($security_areas, $security_sections);
	}
}
