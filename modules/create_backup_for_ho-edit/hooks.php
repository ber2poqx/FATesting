<?php
define ('SS_BACKUP_FOR_HO', 101<<8);
class hooks_create_backup_for_ho extends hooks {
	var $module_name = 'Back-Up Database For Selected Table - HO'; 

	/*
		Install additonal menu options provided by module
	*/
	function install_options($app) {
		global $path_to_root;

		switch($app->id) {
			case 'system':
				$app->add_rapp_function(2, _('Back-Up Database For Selected Table - HO'), 
					$path_to_root.'/modules/create_backup_for_ho/create_backup_for_ho.php', 'SA_BACKUP_FOR_HO', MENU_SYSTEM);
		}
	}

	function install_access()
	{
		$security_sections[SS_BACKUP_FOR_HO] =	_("Back-Up Database For Selected Table - HO");

		$security_areas['SA_BACKUP_FOR_HO'] = array(SS_BACKUP_FOR_HO|101, _("Back-Up Database For Selected Table - HO"));

		return array($security_areas, $security_sections);
	}
}
