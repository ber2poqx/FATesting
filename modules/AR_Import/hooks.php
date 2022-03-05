<?php
define ('SS_IMPORTCSVAR', 101<<8);
class hooks_AR_Import extends hooks {
	var $module_name = 'Import AR Opening Balances'; 

	/*
		Install additonal menu options provided by module
	*/
	function install_options($app) {
		global $path_to_root;

		switch($app->id) {
			case 'orders':
				$app->add_rapp_function(2, _('Import AR Opening Balances'), 
					$path_to_root.'/modules/AR_Import/ar_import.php', 'SA_CSVARIMPORT');
		}
	}

	function install_access()
	{
		$security_sections[SS_IMPORTCSVAR] =	_("Import AR Opening Balances");

		$security_areas['SA_CSVARIMPORT'] = array(SS_IMPORTCSVAR|101, _("Import AR Opening Balances"));

		return array($security_areas, $security_sections);
	}
}
