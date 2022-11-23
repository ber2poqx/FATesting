<?php
define ('SS_AREA_IMPORTS', 101<<8);
class hooks_area_setup_import extends hooks {
	var $module_name = 'Area Setup Import'; 

	/*
		Install additonal menu options provided by module
	*/
	function install_options($app) {
		global $path_to_root;

		switch($app->id) {
			case 'orders':
				$app->add_lapp_function(2, _('Area Setup Import'), 
					$path_to_root.'/modules/area_setup_import/area_setup_import.php', 'SA_AREA_IMPORTS', MENU_MAINTENANCE);
		}
	}

	function install_access()
	{
		$security_sections[SS_AREA_IMPORTS] =	_("Area Setup Import");

		$security_areas['SA_AREA_IMPORTS'] = array(SS_AREA_IMPORTS|101, _("Area Setup Import"));

		return array($security_areas, $security_sections);
	}
}
