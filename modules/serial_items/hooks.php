<?php

define('SS_SERIALITEMS', 101<<8); 

class hooks_serial_items extends hooks {
	var $module_name = 'serial_items'; 

	/*
		Install additonal menu options provided by module
	*/
	function install_options($app) {
		global $path_to_root;

		switch($app->id) {
			case 'AP':
				$app->add_rapp_function(0, _('Serial Items Entries'), $path_to_root.'/modules/serial_items/serial_items.php',
					'SA_SERIALITEMS',	MENU_TRANSACTION);
				break;
		}
	}

	function install_access()
	{

		$security_sections[SS_SERIALITEMS] = _("Serial Items");

		$security_areas['SA_SERIALITEMS'] = array(SS_SERIALITEMS|1, _("Serial Items Entries"));
		

		return array($security_areas, $security_sections);
	}

	/* This method is called on extension activation for company. 	*/
	function activate_extension($company, $check_only=true)
	{
		global $db_connections;

		$updates = array(
			'update.sql' => array('serial_items')
		);

		return $this->update_databases($company, $updates, $check_only);
	}
}
