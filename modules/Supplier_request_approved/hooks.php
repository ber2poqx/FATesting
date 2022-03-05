<?php
define ('SS_SUPPLIER_APPROVAL', 101<<8);
class hooks_Supplier_request_approved extends hooks {
	var $module_name = 'Suppliers Request Approval'; 

	/*
		Install additonal menu options provided by module
	*/
	function install_options($app) {
		global $path_to_root;

		switch($app->id) {
			case 'AP':
				$app->add_lapp_function(2, _('Suppliers Request Approval'), 
					$path_to_root.'/modules/Supplier_request_approved/supplier_approved_request.php', 'SA_SUPPLIER_APPROVED', MENU_MAINTENANCE);
		}
	}

	function install_access()
	{
		$security_sections[SS_SUPPLIER_APPROVAL] =	_("Suppliers Request Approval");

		$security_areas['SA_SUPPLIER_APPROVED'] = array(SS_SUPPLIER_APPROVAL|101, _("Suppliers Request Approval"));

		return array($security_areas, $security_sections);
	}
}
