<?php
define ('SS_IMPORTCSVRR', 105<<8);
class hooks_import_rr_transworld extends hooks {
	var $module_name = 'Import CSV RR Transworld'; 

	/*
		Install additonal menu options provided by module
	*/
	function install_options($app) {
		global $path_to_root;

		switch($app->id) {
			case 'stock':
				$app->add_rapp_function(2, _('Import CSV RR Transworld'), 
					$path_to_root.'/modules/import_rr_transworld/import_rr_transworld.php', 'SA_CSVRRIMPORT');
		}
	}

	function install_access()
	{
		$security_sections[SS_IMPORTCSVRR] =	_("Import CSV RR Transworld");

		$security_areas['SA_CSVRRIMPORT'] = array(SS_IMPORTCSVRR|105, _("Import CSV RR Transworld"));

		return array($security_areas, $security_sections);
	}
}
