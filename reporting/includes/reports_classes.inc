<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/

// Standard report classess
define('RC_CUSTOMER', 0);
define('RC_SUPPLIER', 1);
define('RC_INVENTORY', 2);
define('RC_MANUFACTURE', 3);
define('RC_DIMENSIONS', 4);
define('RC_BANKING', 5);
define('RC_GL', 6);
define('RC_FIXEDASSETS', 7);

class BoxReports
{
	var $ar_classes; // report class names
	var $ar_reports;
	var $ctrl_handlers = array();


	function __construct()
	{
		$this->ar_classes = array();
	}

	function addReportClass($class_name, $class_id=null)
	{
		if (!$class_id)
			$class_id = count_array($this->ar_classes);
		$this->ar_classes[$class_id] = $class_name;

		return $class_id;
	}

	function addReport($class, $id, $rep_name, $params=null)
	{
		unset($this->ar_reports[$class][$id]); // unset std report if any
		$this->ar_reports[$class][$id] = new Report($id, $rep_name, $params);
	}

	function getDisplay($class=null)
	{
		global $path_to_root, $SysPrefs, $Ajax;

		if (find_submit('Rep') != -1) {
			include($path_to_root.'/reporting/prn_redirect.php');
			return;
		}

		$temp = array_values($this->ar_classes);
		$display_class = $class==null ? $temp[0] : $this->ar_classes[$class];
		$st_reports = "";
		$st_params = "";
		$st_classes = "<b>" . _("Report Classes:") . "</b><br>";

		if (isset($_GET['Class']))
			set_focus('class'.$_GET['Class']);

		$cur_class = $_REQUEST['Class'];

		foreach($this->ar_classes as $class_id => $name)
		{
			if (!isset($this->ar_reports[$class_id]))
				continue; // e.g. no dimensions

			$acc = access_string($name);
			$st_classes .= "<a href='"
				.$_SERVER['PHP_SELF']."?Class=$class_id'"
				." style='font-weight:". ($cur_class == $class_id ? 'bold' : 'normal')."'"
				." class='repclass_link' id='".'class'.$class_id."'"
				."$acc[1]>$acc[0]</a> <br>";

			$style = $class_id==$cur_class ? '' : "style='display:none'";
			$st_reports .= "<table class='repclass' id='TAB_" . $class_id ."' $style cellpadding='0' cellspacing='0' border='0' width='100%'><tr><td><b>" . _("Reports For Class: ") . "&nbsp;$name</b></td></tr>\n";
			foreach($this->ar_reports[$class_id] as $rep_id => $report)
			{
				$acc = access_string($report->name);
				$st_reports .= "<tr><td><a class='repopts_link'"
					." href='".$_SERVER['PHP_SELF']."?Class=$class_id&REP_ID=$report->id'"
					." style='font-weight:". (@$_REQUEST['REP_ID'] == $report->id ? 'bold' : 'normal')."'"
					." id='".$id = default_focus()."'"
					."$acc[1]>$acc[0]</a><tr><td>\n";

				if (@$_REQUEST['REP_ID'] == $report->id) {
					$Ajax->activate($id);
				}

				if (isset($_REQUEST['REP_ID']) && $_REQUEST['REP_ID']==$report->id) {
					// parameters form
					$action = $_SERVER['PHP_SELF'];
					$st_params = "<table><tr><td>\n"
						. "<form method='POST' action='$action' target='_blank'>\n";
					$st_params .= submit('Rep'.$report->id,  
						_("Display: ") . access_string($report->name, true),
						false, '', $SysPrefs->pdf_debug ? false : 'default process') . hidden('REP_ID', $report->id, false).'<br><br>';
					$st_params .= $this->getOptions($report->get_controls(), $report->id);
					$st_params .= "\n<input type=hidden name='Class' value=".$cur_class.">"
						."\n</form></td></tr></table>\n";
					set_focus('Rep'.$report->id);
					$Ajax->addUpdate(true, 'rep_form', $st_params);
				}
			}
			$st_reports .= "</table>";
		}

		$st_params = "<div id='rep_form'>".
			"$st_params</div>";
		
		$st = "<table align='center' width='80%' style='border:1px solid #cccccc;'><tr valign='top'>";
		$st .= "<td width='30%'>$st_classes</td>";
		$st .= "<td width='35%' style='border-left:1px solid #cccccc;border-right:1px solid #cccccc;padding-left:3px;'>$st_reports</td>";
		$st .= "<td width='35%'>$st_params</td>";
		$st .= "</tr></table><br>";

		return $st;
	}

	function getOptions($controls, $id = 0)
	{
		$st = '';
		if ($controls == null)
			return "";

		$cnt = 0;
		foreach($controls as $title => $type)
		{
			$ctrl = '';
			foreach($this->ctrl_handlers as $fun) { // first check for non-standard controls
				$ctrl = $fun('PARAM_'.$cnt, $type);
				if ($ctrl) break;
			}
			if ($ctrl == '') {
				if (($id == 102 || $id == 202) && $cnt == 3) // set default Show Also Allocated to Yes in aged reports 2014-09-19 Joe Hunt
					$_POST['PARAM_'.$cnt] = 1;
				
				if (isset($_COOKIE['select'][$id][$cnt])) // saved parameters 2010-10-06 Joe Hunt
					$_POST['PARAM_'.$cnt] = $_COOKIE['select'][$id][$cnt];
				$ctrl = $this->get_ctrl('PARAM_'.$cnt, $type);
			}
			if ($ctrl != '')  {
				$st .= $title . ':<br>';
				$st .= $ctrl;
				$st .= "<br><br>";
			} else {
				display_error(_('Unknown report parameter type:').$type);
			}
			$cnt++;
		}
		return $st;
	}

	//
	//	Register additional control handler
	// $handle - name of global function f($name, $type) returning html code for control
	function register_controls($handler) {
		$this->ctrl_handlers[] = $handler;
	}
	//
	//	Returns html code for input control $name of type $type 
	//
	function get_ctrl($name, $type)
	{
		global $path_to_root, $SysPrefs,
			$type_shortcuts;
		
		$st = '';
			switch ($type)
			{
				case 'CURRENCY':
					$sql = "SELECT curr_abrev, concat(curr_abrev,' - ', currency) AS name FROM ".TB_PREF."currencies";
					return combo_input($name, '', $sql, 'curr_abrev', 'name',array('spec_option'=>_("No Currency Filter"),'spec_id'=>ALL_TEXT,'order'=>false));

				case 'DATE':
				case 'DATEBEGIN':
				case 'DATEEND':
				case 'DATEBEGINM':
				case 'DATEENDM':
				case 'DATEBEGINTAX':
				case 'DATEENDTAX':
					if (!isset($_POST[$name]))
					{
						if ($type == 'DATEBEGIN')
							$date = begin_fiscalyear();
						elseif ($type == 'DATEEND')
							$date = end_fiscalyear();
						else
							$date = Today();
						if ($type == 'DATEBEGINM')
							$date = begin_month($date);
						elseif ($type == 'DATEENDM')
							$date = end_month($date);
						elseif ($type == 'DATEBEGINTAX' || $type == 'DATEENDTAX')
						{
							$row = get_company_prefs();
							$edate = add_months($date, -$row['tax_last']);
							$edate = end_month($edate);
							if ($type == 'DATEENDTAX')
								$date = $edate;
							else
							{
								$bdate = begin_month($edate);
								$bdate = add_months($bdate, -$row['tax_prd'] + 1);
								$date = $bdate;
							}
						}
					}
					else // saved parameters
						$date = $_POST[$name];
					$st = "<input type='text' name='$name' value='$date'>";
					if (user_use_date_picker())
					{
						$calc_image = (file_exists("$path_to_root/themes/".user_theme()."/images/cal.gif")) ? 
							"$path_to_root/themes/".user_theme()."/images/cal.gif" : "$path_to_root/themes/default/images/cal.gif";
						$st .= "<a href=\"javascript:date_picker(document.forms[0].$name);\">"
						. "	<img src='$calc_image' style='vertical-align:middle;padding-bottom:4px;width:16px;height:16px;border:0;' alt='"._('Click Here to Pick up the date')."'></a>\n";
					}	
					return $st;
					break;

				case 'YES_NO':
					return yesno_list($name);
				//Added by spyrax10
				case 'BRANCH':
					return company_list_row(null, $name, true, true, false, false, false, true);
					
				case 'PNP_STAT':
					return value_type_list(null, $name, 
						array(
							'ALL' => 'All Clearance Status',
							1 => 'Cleared',
							0 => 'Not Cleared'
						), '', null, true, '', true, false, true
					);

				case 'COA_COL':
					return numeric_type_list(null, $name, 
						array(
							_('COA'),
							_('COLLECTOR'),
							_('AREA')
						), null, true, '', '', false, true
					);

				case 'AGING_FILTER':
					if (get_post('PARAM_2') == 2) {
						return collector_area($name, null, _("All Collector"),  false, true);
					}
					else if (get_post('PARAM_2') == 3) {
						return sales_areas_list($name, null, _("No Areas Filter"));
					}
					else {
						return gl_account_list($name, null, _("No Account Filter"), true, false);
					}
				//
				case 'PAYMENT_LINK':
					return payment_services($name, null);

				case 'DESTINATION':
					$sel = array(_("PDF/Printer"), "Excel");
					$def = 0;
					if (user_def_print_destination() == 1)
						$def = 1;
					return array_selector($name, $def, $sel);

				//Added by Prog6 =================================
				case 'PDFDESTINATION':
					$sel = array(_("PDF/Printer"), "Excel");
					$def = 0;
					//if (user_def_print_destination() == 1)
					//	$def = 1;
					return array_selector($name, $def, $sel);
				//================================================

				case 'ORIENTATION':
					$sel = array(_("Portrait"), _("Landscape"));
					$def = 0;
					if (user_def_print_orientation() == 1)
						$def = 1;
					return array_selector($name, $def, $sel);

				//Added by Prog6 =================================
				case 'LANDSCAPE':
					$sel = array(_("Portrait"), _("Landscape"));
					$def = 1;
					//if (user_def_print_orientation() == 1)
					//	$def = 1;
					return array_selector($name, $def, $sel);
				//================================================

				case 'COMPARE':
					$sel = array(_("Accumulated"), _("Period Y-1"), _("Budget"));
					return array_selector($name, null, $sel);

				case 'GRAPHIC':
					$sel = array(_("No Graphics"), _("Vertical bars"), _("Horizontal bars"), 
								_("Dots"), _("Lines"), _("Pie"), _("Donut"));
					return array_selector($name, null, $sel);

				case 'SYS_TYPES':
					return gl_systypes_list($name, null, _("No Type Filter"));

				case 'SYS_TYPES_ALL':
					return systypes_list($name, null, _("No Type Filter"));

				case 'TEXT':
					return "<input type='text' name='$name'>";

				case 'TEXTBOX':
					$value = (isset($_POST[$name]) ? $_POST[$name] : "");
					return "<textarea rows=4 cols=30 maxlength=130 name='$name'>$value</textarea>";

				case 'ACCOUNTS': // not used
					return gl_account_types_list($name, null, _("No Account Group Filter"), true);

				case 'ACCOUNTS_NO_FILTER': // not used
					return gl_account_types_list($name);

				case 'GL_ACCOUNTS_NO_FILTER':	
				case 'GL_ACCOUNTS':
					if ($type == 'GL_ACCOUNTS_NO_FILTER')
						return gl_all_accounts_list($name, null, false, _("All"));
					else
						return gl_all_accounts_list($name);

				case 'BANK_ACCOUNTS_NO_FILTER':
				case 'BANK_ACCOUNTS':
					if ($type == 'BANK_ACCOUNTS_NO_FILTER')
						return bank_accounts_list($name, null, false, _("All"));
					else
						return bank_accounts_list($name);

				case 'DIMENSION':
					return dimensions_list($name, null, false, ' ', false, true, 0);
					
				case 'DIMENSIONS':
					return dimensions_list($name, null, true, _("No Dimension Filter"), false, true, 0);

				case 'DIMENSION1':
					return dimensions_list($name, null, false, ' ', false, true, 1);

				case 'DIMENSIONS1':
					return dimensions_list($name, null, true, _("No Dimension Filter"), false, true, 1);

				case 'DIMENSION2':
					return dimensions_list($name, null, false, ' ', false, true, 2);

				case 'DIMENSIONS2':
					return dimensions_list($name, null, true, _("No Dimension Filter"), false, true, 2);


				case 'CUSTOMERS_LIST':
				case 'CUSTOMERS_NO_FILTER':
				case 'CUSTOMERS':
					if ($type == 'CUSTOMERS_NO_FILTER')
						return customer_list($name, null, _("No Customer Filter"));
					else if ($type == 'CUSTOMERS_LIST')
						return customer_list($name, null, _("All Customers"));
					else
						return customer_list($name);

				case 'CUSTOMER_SL':
					return sl_customer_list($name,null);

				case 'SUPPLIERS_NO_FILTER':
				case 'SUPPLIERS':

					if ($type == 'SUPPLIERS_NO_FILTER')
						return supplier_list($name, null, _("No Supplier Filter"), false, false, false, true, 
							get_post('PARAM_1') == -1 ? null : get_post('PARAM_1')); //modified by spyrax10
					else
						return supplier_list($name);

				case 'INVOICE':
					$IV = $type_shortcuts[ST_SALESINVOICE];
					$ref = ($SysPrefs->print_invoice_no() == 1 ? "trans_no" : "reference");
					$sql = "SELECT concat(debtor_trans.trans_no, '-', debtor_trans.type) AS TNO,
								concat('$IV ', debtor_trans.$ref,' ', debtor.name) as IName
						FROM ".TB_PREF."debtors_master debtor,"
							.TB_PREF."debtor_trans debtor_trans LEFT JOIN ".TB_PREF."voided AS vd ON debtor_trans.type=vd.type AND
							debtor_trans.trans_no=vd.id
						WHERE debtor_trans.type=".ST_SALESINVOICE." AND debtor.debtor_no=debtor_trans.debtor_no AND ISNULL(vd.id)
						ORDER BY debtor_trans.trans_no DESC";
					return combo_input($name, '', $sql, 'TNO', 'IName',array('order'=>false));

				case 'CREDIT':
					$CN = $type_shortcuts[ST_CUSTCREDIT];
					$ref = ($SysPrefs->print_invoice_no() == 1 ? "trans_no" : "reference");
					$sql = "SELECT concat(debtor_trans.trans_no, '-', debtor_trans.type) AS TNO,
								concat('$CN ', debtor_trans.$ref,' ', debtor.name) as IName
						FROM ".TB_PREF."debtors_master debtor,"
							.TB_PREF."debtor_trans debtor_trans LEFT JOIN ".TB_PREF."voided AS vd ON debtor_trans.type=vd.type AND
							debtor_trans.trans_no=vd.id
						WHERE debtor_trans.type=".ST_CUSTCREDIT." AND debtor.debtor_no=debtor_trans.debtor_no AND ISNULL(vd.id)
						ORDER BY debtor_trans.trans_no DESC";
					return combo_input($name, '', $sql, 'TNO', 'IName',array('order'=>false));

				case 'DELIVERY':
					$DN = $type_shortcuts[ST_CUSTDELIVERY];
					$sql = "SELECT concat(debtor_trans.trans_no, '-', debtor_trans.type) AS TNO,
								concat(debtor_trans.trans_no, ' $DN ', debtor.name) as IName
						FROM ".TB_PREF."debtors_master debtor,"
							.TB_PREF."debtor_trans debtor_trans LEFT JOIN ".TB_PREF."voided AS vd ON debtor_trans.type=vd.type AND
							debtor_trans.trans_no=vd.id
						WHERE debtor_trans.type=".ST_CUSTDELIVERY." AND debtor.debtor_no=debtor_trans.debtor_no AND ISNULL(vd.id)
						ORDER BY debtor_trans.trans_no DESC";
					return combo_input($name, '', $sql, 'TNO', 'IName',array('order'=>false));

				case 'ORDERS':
					$ref = ($SysPrefs->print_invoice_no() == 1 ? "order_no" : "reference");
					$sql = "SELECT sorder.order_no, concat(sorder.$ref, '-', debtor.name) as IName
						FROM ".TB_PREF."debtors_master debtor,"
							.TB_PREF."sales_orders sorder
						WHERE debtor.debtor_no=sorder.debtor_no	AND sorder.trans_type=".ST_SALESORDER
						." ORDER BY sorder.order_no DESC";
					return combo_input($name, '', $sql, 'order_no', 'IName',array('order'=>false));

				case 'QUOTATIONS':
					$ref = ($SysPrefs->print_invoice_no() == 1 ? "order_no" : "reference");
					$sql = "SELECT sorder.order_no, concat(sorder.$ref, '-', debtor.name) as IName
						FROM ".TB_PREF."debtors_master debtor,"
							.TB_PREF."sales_orders sorder
						WHERE debtor.debtor_no=sorder.debtor_no	AND sorder.trans_type=".ST_SALESQUOTE
						." ORDER BY sorder.order_no DESC";
					return combo_input($name, '', $sql, 'order_no', 'IName',array('order'=>false));

				case 'PO':
					$ref = ($SysPrefs->print_invoice_no() == 1 ? "order_no" : "reference");
					$sql = "SELECT po.order_no, concat(po.$ref, '-', supplier.supp_name) as IName
						FROM ".TB_PREF."suppliers supplier,"
							.TB_PREF."purch_orders po
						WHERE supplier.supplier_id=po.supplier_id
						ORDER BY po.order_no DESC";
					return combo_input($name, '', $sql, 'order_no', 'IName',array('order'=>false));

				case 'DEBTOR':
					$ref = ($SysPrefs->print_invoice_no() == 1 ? "debtor_no" : "debtor_ref");
					$sql = "SELECT debtor_no, name as IName
						FROM ".TB_PREF."debtors_master
						ORDER BY name";
					return combo_input($name, '', $sql, 'debtor_no', 'IName',array('order'=>false));

				case 'REMITTANCE':
					$BP = $type_shortcuts[ST_BANKPAYMENT];
					$SP = $type_shortcuts[ST_SUPPAYMENT];
					$CN = $type_shortcuts[ST_SUPPCREDIT];
					$ref = ($SysPrefs->print_invoice_no() == 1 ? "trans_no" : "reference");
					$sql = "SELECT concat(trans.trans_no, '-',trans.type) AS TNO,
								concat(trans.$ref, IF(trans.type=".ST_BANKPAYMENT.", ' $BP ', IF(trans.type=".ST_SUPPAYMENT.", ' $SP ', 
								' $CN ')),
								supplier.supp_name) as IName
						FROM ".TB_PREF."suppliers supplier, "
							.TB_PREF."supp_trans trans  LEFT JOIN ".TB_PREF."voided AS vd ON trans.type=vd.type AND trans.trans_no=vd.id
						WHERE trans.type IN(".ST_BANKPAYMENT.",".ST_SUPPAYMENT.",".ST_SUPPCREDIT.") AND
							supplier.supplier_id=trans.supplier_id AND ISNULL(vd.id)
						ORDER BY trans.trans_no DESC";
					return combo_input($name, '', $sql, 'TNO', 'IName',array('order'=>false));

				case 'RECEIPT':
					$BD = $type_shortcuts[ST_BANKDEPOSIT];
					$CP = $type_shortcuts[ST_CUSTPAYMENT];
					$ref = ($SysPrefs->print_invoice_no() == 1 ? "trans_no" : "reference");
					$sql = "SELECT concat(trans.trans_no, '-', trans.type) AS TNO,
								concat(trans.$ref, IF(trans.type=".ST_BANKDEPOSIT.", ' $BD ', ' $CP '), debtor.name) as IName
						FROM ".TB_PREF."debtors_master debtor,"
							.TB_PREF."debtor_trans trans LEFT JOIN ".TB_PREF."voided AS vd ON trans.type=vd.type AND trans.trans_no=vd.id
						WHERE trans.type IN(".ST_BANKDEPOSIT.",".ST_CUSTPAYMENT.",".ST_CUSTCREDIT.") AND
							debtor.debtor_no=trans.debtor_no AND ISNULL(vd.id)
						ORDER BY trans.trans_no DESC";
					return combo_input($name, '', $sql, 'TNO', 'IName',array('order'=>false));

				case 'ITEMS':
					return stock_manufactured_items_list($name);

				//Modified by spyrax10 9 Mar 2022
				case 'ITEMS_ALL':
					return stock_items_list($name, null, true, false);
					//stock_manufactured_items_list($name, null, true, false);
				//
				
				case 'ITEMS_P':
					return stock_purchasable_items_list($name, null, true);
				
				case 'WORKORDER':
					$sql = "SELECT wo.id, concat(wo.id, '-', item.description) as IName
						FROM ".TB_PREF."stock_master item,"
							.TB_PREF."workorders wo 
							LEFT JOIN ".TB_PREF."voided v ON wo.id=v.id and v.type=".ST_WORKORDER."
						WHERE ISNULL(v.id)
						AND item.stock_id=wo.stock_id
						ORDER BY wo.id DESC";
					return combo_input($name, '', $sql, 'id', 'IName',array('order'=>false));

				case 'LOCATIONS':
					return  locations_list($name, null, _("No Location Filter"));
				case 'FLOCATIONS':
					return  locations_list($name, null, _("No Location Filter"), false, true);

				case 'CATEGORIES':
					return stock_categories_list($name, null, _("--Select--"), true); //Modified by spyrax10
				case 'FCATEGORIES':
					return stock_categories_list($name, null, _("No Category Filter"), false, true);

				//Added by Prog6=====================================================
				case 'CATEGORY_LIST':
					return category_list($name, null, _("All Categories"));
				case 'CATEGORY_LIST_FILTERED':
					return category_list($name, null);
				//===================================================================
				
				case 'BRAND':
					return main_brand($name, null, _("--Select--"));
				case 'FBRAND':
					return main_brand($name, null, _("No Brand Filter"), false, true);

				//Added by Prog6=====================================================
				case 'BRAND_LIST':
					return main_brand($name, null, _("All Brands"));
				//===================================================================

				//Added by Prog6=====================================================
				case 'MONTHS_TERM':
					return installment_policy_list($name, null, _("All Terms"), false, true);
				//===================================================================

				//Added by Robert=====================================================
				case 'TRANST_TYPE':
					return main_transaction_type($name, null, _("ALL TYPE"));

				case 'STATUS_ORDER':
					return main_status_order($name, null, _("ALL STATUS"));

				case 'CASHIER_DCPR':
				return sql_type_list(null, $name, 
					get_dcpr_users(), 
					'cashier_user_id', 'real_name', 'label', null, true, 
					_("All Cashiers"), true
				);

				case 'COLLECTOR_COLLECTION':
					return collector_area($name, null, _("All Collector"),  false, true);

				case 'CASHIER_COLLECTION':
					return list_cashier_name($name, null, _("All Cashier"),  false, true);

				case 'STOCK_LIST_REPORT':
				return stock_items_list($name, null);

				case 'INVTY_TYPE_REPORT':
				return inventory_type_list_report($name, null);

				case 'CASHTYPES':
					return scash_types_list($name);

				case 'SYSTEMTYPES':
					return supp_cost_types_list($name);

				case 'SRPTYPES':
					return srp_types_list($name);

				case 'SUPPLIERS_LIST_FOR_REPORT':
				case 'SUPPLIERS_NO_FILTER_FOR_REPORT':
				case 'SUPPLIERS_FOR_REPORT':
					if ($type == 'SUPPLIERS_NO_FILTER_FOR_REPORT')
						return supplier_list($name, null, _("No Supplier Filter"));
					else if ($type == 'SUPPLIERS_LIST_FOR_REPORT')
						return supplier_list($name, null, _("All Supplier"));
					else
						return supplier_list($name);

				case 'CATEG_COLL':
					return group_list_repo($name);

				case 'MOVEMENT_TYPE':
					return qty_list_movement($name);
				//===================================================================

				case 'MODEL_LIST':
					return main_model($name, null, _("All Models"), false, true);
				
				case 'SUBCATEGORY':
					return main_sub_category($name, null, _("--Select--"));
				case 'FSUBCATEGORY':
					return main_sub_category($name, null, _("No Sub-Category Filter"), false, true);
					
				case 'CLASSIFICATION':
					return main_classification($name, null, _("--Select--"));
				case 'FCLASSIFICATION':
					return main_classification($name, null, _("No classification Filter"), false, true);

				case 'MODEL':
					return main_model($name, null, _("No Model Filter"));
				case 'FMODEL':
					return main_model($name, null, _("No Model Filter"), false, true);

				case 'STATUS':
					return main_status($name, null, _(""));

				case 'FCLASS':
					return fixed_asset_classes_list($name, null, _("No Class Filter"), false);
				case 'SALESTYPES':
					return sales_types_list($name);

				case 'AREAS':
					return sales_areas_list($name, null, _("No Areas Filter"));

				case 'SALESMEN':
					return sales_persons_list($name, null, _("No Sales Folk Filter"));
					
				case 'TRANS_YEARS':
					return fiscalyears_list($name);

				case 'USERS':
					$sql = "SELECT id, user_id FROM ".TB_PREF."users";
					return combo_input($name, '', $sql, 'id', 'user_id',array('spec_option'=>_("No Users Filter"),'spec_id'=>ALL_NUMERIC));

				case 'ACCOUNTTAGS':
				case 'DIMENSIONTAGS':
					if ($type == 'ACCOUNTTAGS')
						$tag_type = TAG_ACCOUNT;
					else
						$tag_type = TAG_DIMENSION;
					return tag_list($name, 5, $tag_type, true);

			}
		return '';
	}
}

class Report
{
	var $id;
	var $name;
	var $ar_params;
	var $controls;
	
	function __construct($id, $name, $ar_params = null)
	{
		$this->id = $id;
		$this->name = $name;
		if ($ar_params) $this->set_controls($ar_params);
	}
	
	function set_controls($ar_params) {
		$this->controls = $ar_params;
	}
	
	function get_controls() {
		return $this->controls;
	}
	
}

//------------------------------------------------------------------------------------------------
function gl_systypes_list($name, $value=null, $spec_opt=false)
{
	global $systypes_array;
	
	$types = $systypes_array;
	
	foreach(array(ST_LOCTRANSFER, ST_PURCHORDER, ST_SUPPRECEIVE, ST_MANUISSUE,
				ST_MANURECEIVE, ST_SALESORDER, ST_SALESQUOTE, ST_DIMENSION) as $type)
		unset($types[$type]);

	return array_selector($name, $value, $types, 
		array( 
			'spec_option'=> $spec_opt,
			'spec_id' => ALL_NUMERIC,
			'async' => false,
			)
	);
}
/*
	Add extension/custom reports to report manager.
*/
function add_custom_reports(&$reports)
{
	global $installed_extensions, $path_to_root;
	
	// include reports installed inside extension modules
	if (count($installed_extensions) > 0)
	{
		$extensions = $installed_extensions;
		foreach ($extensions as $ext)
			if (($ext['active'] && $ext['type'] == 'extension')) {
				$file = $path_to_root.'/'.$ext['path']."/reporting/reports_custom.php";
				if (file_exists($file)) {
					set_ext_domain($ext['path']);
					include_once($file);
					set_ext_domain();
				}
			}
	}
	$file = company_path()."/reporting/reports_custom.php";
	if (file_exists($file))
		include_once($file);
}

