<?php
define ('SS_USER_IMPORTS', 101<<8);
class hooks_user_import extends hooks {
	var $module_name = 'User Setup Import'; 

	/*
		Install additonal menu options provided by module
	*/
	function install_options($app) {
		global $path_to_root;

		switch($app->id) {
			case 'system':
				$app->add_lapp_function(2, _('User Setup Import'), 
					$path_to_root.'/modules/user_import/user_import.php', 'SA_USER_IMPORTS', MENU_SYSTEM);
		}
	}

	function install_access()
	{
		$security_sections[SS_USER_IMPORTS] =	_("User Setup Import");

		$security_areas['SA_USER_IMPORTS'] = array(SS_USER_IMPORTS|101, _("User Setup Import"));

		return array($security_areas, $security_sections);
	}
}
