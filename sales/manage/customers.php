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
$page_security = 'SA_CUSTOMER';
$path_to_root = "../..";

include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/admin/db/company_db.inc");


$js = "";
if ($SysPrefs->use_popup_windows && $SysPrefs->use_popup_search)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
	
page(_($help_context = "Customers"), @$_REQUEST['popup'], false, "", $js); 

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/ui/contacts_view.inc");
include_once($path_to_root . "/includes/ui/comaker_view.inc");
include_once($path_to_root . "/includes/ui/attachment.inc");


if (isset($_GET['debtor_no'])) 
{
	$_POST['customer_id'] = $_GET['debtor_no'];
}

$selected_id = get_post('customer_id','');
//--------------------------------------------------------------------------------------------

function can_process()
{

    $InputError = 0;

	if (strlen($_POST['CustName']) == 0) 
	{
		display_error(_("The customer name cannot be empty."));
		set_focus('CustName');
		return false;
	} 

	if (strlen($_POST['address']) == 0) 
	{
		display_error(_("The address cannot be empty."));
		set_focus('address');
		return false;
	} 

	if (strlen($_POST['barangay']) == 0) 
	{
		display_error(_("The barangay cannot be empty."));
		set_focus('barangay');
		return false;
	} 

	if (strlen($_POST['municipality']) == 0) 
	{
		display_error(_("The municipality cannot be empty."));
		set_focus('municipality');
		return false;
	} 

	if (strlen($_POST['province']) == 0) 
	{
		display_error(_("The province cannot be empty."));
		set_focus('province');
		return false;
	} 

	/*
	if (strlen($_POST['zip_code']) == 0) 
	{
		display_error(_("The zip code cannot be empty."));
		set_focus('zip_code');
		return false;
	} 
	*/

	if (strlen($_POST['age']) == 0) 
	{
		display_error(_("The age cannot be empty."));
		set_focus('age');
		return false;
	}

	if (strlen($_POST['gender']) == 0) 
	{
		display_error(_("The gender cannot be empty."));
		set_focus('gender');
		return false;
	} 

	if (strlen($_POST['status']) == 0) 
	{
		display_error(_("The status cannot be empty."));
		set_focus('status');
		return false;
	} 

	/*if (strlen($_POST['name_father']) == 0) 
	{
		display_error(_("The father name cannot be empty."));
		set_focus('name_father');
		return false;
	} 

	if (strlen($_POST['name_mother']) == 0) 
	{
		display_error(_("The mother name cannot be empty."));
		set_focus('name_mother');
		return false;
	}*/ 

	if (strstr($_POST['CustName'], "\"") || strstr($_POST['CustName'], "&") || strstr($_POST['CustName'], "?") 
		|| strstr($_POST['CustName'], "%") || strstr($_POST['CustName'], "$") || strstr($_POST['CustName'], "*")) 
	{
		$InputError = 1;
		display_error( _('The Customer name cannot contain any of the following characters %, $, *, ?, & OR quotes'));
		set_focus('CustName');
		return false;
	}

	if (strstr($_POST['address'], "\"") || strstr($_POST['address'], "&") || strstr($_POST['address'], "%")
		|| strstr($_POST['address'], "?") || strstr($_POST['address'], "$") || strstr($_POST['address'], "*")) 
	{
		$InputError = 1;
		display_error( _('The Address cannot contain any of the following characters %, $, *, ?, & OR quotes'));
		set_focus('address');
		return false;	
	}

	if (strstr($_POST['barangay'], "\"") || strstr($_POST['barangay'], "&") || strstr($_POST['barangay'], "?") 
		|| strstr($_POST['barangay'], "%") || strstr($_POST['barangay'], "$") || strstr($_POST['barangay'], "*")) 
	{
		$InputError = 1;
		display_error( _('The Barangay cannot contain any of the following characters %, $, *, ?, & OR quotes'));
		set_focus('barangay');
		return false;	
	}

	if (strstr($_POST['province'], "\"") || strstr($_POST['province'], "&") || strstr($_POST['province'], "?") 
		|| strstr($_POST['province'], "%") || strstr($_POST['province'], "$") || strstr($_POST['province'], "*")) 
	{
		$InputError = 1;
		display_error( _('The Province cannot contain any of the following characters %, $, *, ?, & OR quotes'));
		set_focus('province');
		return false;		
	}

	if (strstr($_POST['name_mother'], "\"") || strstr($_POST['name_mother'], "&") || strstr($_POST['name_mother'], "?") 
		|| strstr($_POST['name_mother'], "%") || strstr($_POST['name_mother'], "$") || strstr($_POST['name_mother'], "*")) 
	{
		$InputError = 1;
		display_error( _('The Mother name cannot contain any of the following characters  %, $, *, ?, & OR quotes'));
		set_focus('name_mother');
		return false;
	}

	if (strstr($_POST['name_father'], "\"") || strstr($_POST['name_father'], "&") || strstr($_POST['name_father'], "?") 
		|| strstr($_POST['name_father'], "%") || strstr($_POST['name_father'], "$") || strstr($_POST['name_father'], "*")) 
	{
		$InputError = 1;
		display_error( _('The Father name cannot contain any of the following characters %, $, *, ?, & OR quotes'));
		set_focus('name_father');
		return false;
	}

	/*
	if (strlen($_POST['collectors_name']) == 0) 
	{
		display_error(_("The Collector name cannot be empty."));
		set_focus('collectors_name');
		return false;
	} 
	*/

	if (strlen($_POST['area']) == 0) 
	{
		display_error(_("The Sales area cannot be empty."));
		set_focus('area');
		return false;
	}

	if (strlen($_POST['phone']) == 0) 
	{
		display_error(_("The Phone number cannot be empty."));
		set_focus('phone');
		return false;
	}

	if (strlen($_POST['email']) == 0) 
	{
		display_error(_("The Email cannot be empty."));
		set_focus('email');
		return false;
	}

	if (!check_num('pymt_discount', 0, 100)) 
	{
		display_error(_("The payment discount must be numeric and is expected to be less than 100% and greater than or equal to 0."));
		set_focus('pymt_discount');
		return false;		
	} 
	
	if (!check_num('discount', 0, 100)) 
	{
		display_error(_("The discount percentage must be numeric and is expected to be less than 100% and greater than or equal to 0."));
		set_focus('discount');
		return false;		
	} 

	if(check_customer_already_exist($_POST['CustName'], $_POST['debtor_no']))
	{
        $InputError = 1;
        display_error(_("Customer Name already exists."));
		return false;		
    }

	return true;
}

//--------------------------------------------------------------------------------------------

function handle_submit(&$selected_id)
{
	global $path_to_root, $Ajax, $SysPrefs;
	$_SESSION['language']->encoding = "UTF-8";

	if ($selected_id) 
	{

		$age = date('Y-m-d', strtotime($_POST['age']));
		$_POST['CustName'] = normalize_chars($_POST['CustName']);
		$_POST['address'] = normalize_chars($_POST['address']);
		$_POST['barangay'] = normalize_chars($_POST['barangay']);
		$_POST['province'] = normalize_chars($_POST['province']);
		$_POST['spouse'] = normalize_chars($_POST['spouse']);
		$_POST['name_father'] = normalize_chars($_POST['name_father']);
		$_POST['name_mother'] = normalize_chars($_POST['name_mother']);

		update_customer($_POST['customer_id'], $_POST['CustName'], $_POST['cust_ref'], $_POST['address'],
			$_POST['barangay'], $_POST['municipality'], $_POST['province'], $_POST['tax_id'],
		    $age, $_POST['gender'], $_POST['status'], $_POST['spouse'], $_POST['name_father'], $_POST['name_mother'], 
		    $_POST['curr_code'], $_POST['area'], $_POST['dimension_id'], $_POST['dimension2_id'],
			$_POST['credit_status'], $_POST['payment_terms'], input_num('discount') / 100, input_num('pymt_discount') / 100,
			input_num('credit_limit'), $_POST['sales_type'], $_POST['notes'], $_POST['employee'], $_POST['employee_id']);

		update_record_status($_POST['customer_id'], $_POST['inactive'],
			'debtors_master', 'debtor_no');

		$Ajax->activate('customer_id'); // in case of status change
		display_notification(_("Customer has been updated."));
	} 
	else 
	{ 	
		//--it is a new customer--/
		if (!can_process())
		return;

		begin_transaction();
		$_POST['cust_ref'] = get_Customer_AutoGenerated_Code();
		$age = date('Y-m-d', strtotime($_POST['age']));
		$_POST['CustName'] = normalize_chars($_POST['CustName']);
		$_POST['address'] = normalize_chars($_POST['address']);
		$_POST['barangay'] = normalize_chars($_POST['barangay']);
		$_POST['province'] = normalize_chars($_POST['province']);
		$_POST['spouse'] = normalize_chars($_POST['spouse']);
		$_POST['name_father'] = normalize_chars($_POST['name_father']);
		$_POST['name_mother'] = normalize_chars($_POST['name_mother']);
		
		add_customer($_POST['CustName'], $_POST['cust_ref'], $_POST['address'], $_POST['barangay'],
			$_POST['municipality'], $_POST['province'], $_POST['zip_code'], $_POST['tax_id'],
		    $age, $_POST['gender'], $_POST['status'], $_POST['spouse'], $_POST['name_father'], $_POST['name_mother'],
		    $_POST['collectors_name'], $_POST['curr_code'], $_POST['area'], $_POST['dimension_id'], $_POST['dimension2_id'],
			$_POST['credit_status'], $_POST['payment_terms'], input_num('discount') / 100, input_num('pymt_discount') / 100,
			input_num('credit_limit'), $_POST['sales_type'], $_POST['notes'], 0 , $_POST['employee'], $_POST['employee_id']);

		$selected_id = $_POST['customer_id'] = db_insert_id();
         
		if (isset($SysPrefs->auto_create_branch) && $SysPrefs->auto_create_branch == 1)
		{
        	add_branch($selected_id, $_POST['CustName'], $_POST['cust_ref'],
                $_POST['address'], $_POST['salesman'], $_POST['area'], $_POST['tax_group_id'], '',
                get_company_pref('default_sales_discount_act'), get_company_pref('debtors_act'), get_company_pref('default_prompt_payment_act'),
                $_POST['location'], $_POST['address'], 0, $_POST['ship_via'], $_POST['notes'], $_POST['bank_account']);
                
        	$selected_branch = db_insert_id();
        
			add_crm_person($_POST['cust_ref'], $_POST['CustName'], '', $_POST['address'], 
				$_POST['phone'], $_POST['phone2'], $_POST['fax'], $_POST['email'], $_POST['facebook'], '', '');

			$pers_id = db_insert_id();
			add_crm_contact('cust_branch', 'general', $selected_branch, $pers_id);

			add_crm_contact('customer', 'general', $selected_id, $pers_id);
		}
		commit_transaction();

		display_notification(_("A new customer has been added."));

		if (isset($SysPrefs->auto_create_branch) && $SysPrefs->auto_create_branch == 1)
			display_notification(_("A default Branch has been automatically created, please check default Branch values by using link below."));
		
		$Ajax->activate('_page_body');
	}


}
//--------------------------------------------------------------------------------------------

if (isset($_POST['submit'])) 
{
	handle_submit($selected_id);
}
//-------------------------------------------------------------------------------------------- 

if (isset($_POST['delete'])) 
{

	$cancel_delete = 0;

	// PREVENT DELETES IF DEPENDENT RECORDS IN 'debtor_trans'

	if (key_in_foreign_table($selected_id, 'debtor_trans', 'debtor_no'))
	{
		$cancel_delete = 1;
		display_error(_("This customer cannot be deleted because there are transactions that refer to it."));
	} 
	else 
	{
		if (key_in_foreign_table($selected_id, 'sales_orders', 'debtor_no'))
		{
			$cancel_delete = 1;
			display_error(_("Cannot delete the customer record because orders have been created against it."));
		} 
		else 
		{
			if (key_in_foreign_table($selected_id, 'cust_branch', 'debtor_no'))
			{
				$cancel_delete = 1;
				display_error(_("Cannot delete this customer because there are branch records set up against it."));
				//echo "<br> There are " . $myrow[0] . " branch records relating to this customer";
			}
		}
	}
	
	if ($cancel_delete == 0) 
	{ 	//ie not cancelled the delete as a result of above tests
	
		delete_customer($selected_id);

		display_notification(_("Selected customer has been deleted."));
		unset($_POST['customer_id']);
		$selected_id = '';
		$Ajax->activate('_page_body');
	} //end if Delete Customer
}

function customer_settings($selected_id) 
{
	global $SysPrefs, $path_to_root, $Ajax, $page_nested;
	
	if (!$selected_id) 
	{
	 	if (list_updated('customer_id') || !isset($_POST['CustName'])) {
			$_POST['CustName'] = $_POST['cust_ref'] = $_POST['address'] = $_POST['barangay']
			= $_POST['municipality'] = $_POST['province'] = $_POST['tax_id'] = $_POST['age']
			= $_POST['gender'] = $_POST['status'] = $_POST['spouse'] = $_POST['name_father'] = $_POST['name_mother'] = 
			$_POST['area'] = '';
			$_POST['dimension_id'] = 0;
			$_POST['dimension2_id'] = 0;
			$_POST['sales_type'] = -1;
			$_POST['curr_code']  = get_company_currency();
			$_POST['credit_status']  = -1;
			$_POST['payment_terms']  = $_POST['notes']  = '';
			$_POST['discount']  = $_POST['pymt_discount'] = percent_format(0);
			$_POST['credit_limit']	= price_format($SysPrefs->default_credit_limit());
			$_POST['employee']  = '';
			$_POST['employee_id']  = '';
		}
	}
	else 
	{
		$myrow = get_customer($selected_id);

		$age = date('m/d/Y', strtotime($myrow["age"]));

		$_POST['CustName'] = $myrow["name"];
		$_POST['cust_ref'] = $myrow["debtor_ref"];
		$_POST['address']  = $myrow["address"];
		$_POST['barangay']  = $myrow["barangay"];
		$_POST['municipality']  = $myrow["municipality"];
		$_POST['province']  = $myrow["province"];
		//$_POST['zip_code']  = $myrow["zip_code"];
		$_POST['tax_id']  = $myrow["tax_id"];
		$_POST['age']  = $age;
		$_POST['gender']  = $myrow["gender"];
		$_POST['status']  = $myrow["status"];
		$_POST['spouse']  = $myrow["spouse"];
		$_POST['name_father']  = $myrow["name_father"];
		$_POST['name_mother']  = $myrow["name_mother"];
		//$_POST['collectors_name']  = $myrow["collectors_name"];
		$_POST['dimension_id']  = $myrow["dimension_id"];
		$_POST['dimension2_id']  = $myrow["dimension2_id"];
		$_POST['sales_type'] = $myrow["sales_type"];
		$_POST['curr_code']  = $myrow["curr_code"];
		$_POST['area']  = $myrow["area"];
		$_POST['credit_status']  = $myrow["credit_status"];
		$_POST['payment_terms']  = $myrow["payment_terms"];
		$_POST['discount']  = percent_format($myrow["discount"] * 100);
		$_POST['pymt_discount']  = percent_format($myrow["pymt_discount"] * 100);
		$_POST['credit_limit']	= price_format($myrow["credit_limit"]);
		$_POST['notes']  = $myrow["notes"];
		$_POST['inactive'] = $myrow["inactive"];
		$_POST['employee'] = $myrow["employee"];
		$_POST['employee_id'] = $myrow["employee_id"];
		

	}

	start_outer_table(TABLESTYLE2);
	table_section(1);
	table_section_title(_("Name and Address"));

	//----Added by Robert----//
	if ($selected_id){
		employee_unemployee_list_row( _("Customer Type:"), 'employee', $_POST['employee'], false, false, false);
		if ($myrow["employee_id"] == '') {
			unemployee_v2_list_cells( _("Employee Name:"), 'employee_id', null);
		} else {
			unemployee_list_cells( _("Employee Name:"), 'employee_id', null);
		}			
	} else {
		employee_unemployee_list_row( _("Customer Type:"), 'employee', $_POST['employee'], false, false, true);
		switch ($_POST['employee']) {
			case CT_UNEMPLOYEE:
				break;
			case CT_EMPLOYEE:
				employee_customer_list_row( _("Employee Name:"), 'employee_id', null, true);
				break;
		}
	}
	//-----------------------//
	//MOdified by Robert Dusal
	if ($selected_id != '' && get_customer($selected_id)){
    label_row(_("Customer Code:"), $_POST['cust_ref']);
    hidden('cust_ref', $_POST['cust_ref']);
	}else{
	//text_row(_("Customer Code:"), 'cust_ref', null, 35, 35);
 	}
	text_row(_("Customer Name: *"), 'CustName', $_POST['CustName'], 40, 80);
	textarea_row(_("Complete Address: *"), 'address', $_POST['address'], 34, 2);
	text_row(_("Barangay: *"), 'barangay', $_POST['barangay'], 35, 35);
	text_row(_("Province: *"), 'province', $_POST['province'], 35, 35);
	sales_munizipcode_list_row( _("Municipality: *"), 'municipality', null, true);
	//text_row(_("Municipality:"), 'municipality', $_POST['municipality'], 35, 35);
	//zipcode_list_cells( _("Zip Code:"), 'zip_code', null);
	//text_row(_("Zip Code:"), 'zip_code', $_POST['zip_code'], 35, 35);

	text_row(_("TINNo:"), 'tax_id', null, 35, 35);
	date_cells(_("Birth Date: *"), 'age', $_POST['age']);
	//text_row(_("Age:"), 'age', $_POST['age'], 35, 35);
	gender_status_list_row(_("Gender: *"), 'gender', $_POST['gender']);
	if ($selected_id) {
		status_customer_list_row(_("Status: *"), 'status', $_POST['status'], false, false, false, false);
		text_row(_("Spouse Name: *"), 'spouse', $_POST['spouse'], 35, 35);
	} else {
		status_customer_list_row(_("Status: *"), 'status', $_POST['status'], false, false, false, true);
			switch ($_POST['status']) {
				case DT_SINGLE:
					break;
				case DT_MARRIED:
					text_row(_("Spouse Name: *"), 'spouse', $_POST['spouse'], 35, 35);
					break;
				case DT_WIDOWED:
					break;
			}
	}
	text_row(_("Name of Father:"), 'name_father', $_POST['name_father'], 35, 35);
	text_row(_("Name of Mother:"), 'name_mother', $_POST['name_mother'], 35, 35);

	sales_areas_list_row( _("Collector Area: *"), 'area', null, true);
	//customer_collector_list_cells( _("Collector Name:"), 'collectors_name', null);

	if (!$selected_id || is_new_customer($selected_id) || (!key_in_foreign_table($selected_id, 'debtor_trans', 'debtor_no') &&
		!key_in_foreign_table($selected_id, 'sales_orders', 'debtor_no'))) 
	{
		currencies_list_row(_("Customer's Currency:"), 'curr_code', $_POST['curr_code']);
	} 
	else 
	{
		label_row(_("Customer's Currency:"), $_POST['curr_code']);
		hidden('curr_code', $_POST['curr_code']);				
	}
	//modify by progjr on feb 23, 2021
	//sales_types_list_row(_("Sales Type/Price List:"), 'sales_type', $_POST['sales_type']);
	saleorder_types_row(_("Sales Type: *"), 'sales_type', $_POST['sales_type'], false);
	//check_row(_("Employee: "), 'employee'); // Added by Ronelle 7/23/2021
	
	if($selected_id)
		record_status_list_row(_("Customer status:"), 'inactive');
	elseif (isset($SysPrefs->auto_create_branch) && $SysPrefs->auto_create_branch == 1)
	{
		table_section_title(_("Branch"));
		text_row(_("Phone: *"), 'phone', null, 35, 35);
		text_row(_("Secondary Phone Number:"), 'phone2', null, 35, 35);
		text_row(_("Fax Number:"), 'fax', null, 35, 35);
		email_row(_("E-mail: *"), 'email', null, 35, 55);
		email_row(_("Facebook:"), 'facebook', null, 35, 55);
		text_row(_("Bank Account Number:"), 'bank_account', null, 35, 35);
		sales_persons_list_row( _("Sales Person:"), 'salesman', null);
	}
	table_section(2);

	table_section_title(_("Sales"));

	percent_row(_("Discount Percent:"), 'discount', $_POST['discount']);
	percent_row(_("Prompt Payment Discount Percent:"), 'pymt_discount', $_POST['pymt_discount']);
	amount_row(_("Credit Limit:"), 'credit_limit', $_POST['credit_limit']);

	payment_terms_list_row(_("Payment Terms:"), 'payment_terms', $_POST['payment_terms']);
	credit_status_list_row(_("Credit Status:"), 'credit_status', $_POST['credit_status']); 
	$dim = get_company_pref('use_dimension');
	if ($dim >= 1)
		dimensions_list_row(_("Dimension")." 1:", 'dimension_id', $_POST['dimension_id'], true, " ", false, 1);
	if ($dim > 1)
		dimensions_list_row(_("Dimension")." 2:", 'dimension2_id', $_POST['dimension2_id'], true, " ", false, 2);
	if ($dim < 1)
		hidden('dimension_id', 0);
	if ($dim < 2)
		hidden('dimension2_id', 0);

	if ($selected_id)  {
		start_row();
		echo '<td class="label">'._('Customer branches').':</td>';
	  	hyperlink_params_td($path_to_root . "/sales/manage/customer_branches.php",
			'<b>'. ($page_nested ?  _("Select or &Add") : _("&Add or Edit ")).'</b>', 
			"debtor_no=".$selected_id.($page_nested ? '&popup=1':''));
		end_row();
	}

	textarea_row(_("General Notes:"), 'notes', null, 35, 5);
	//------------ADDED BY ROBERT--------------//
		//table_section_title(_("Collector Area & Collector Name Setup"));
		//sales_areas_list_row( _("Collector Area:"), 'area', null, true);
		//customer_collector_list_cells( _("Collector Name:"), 'collectors_name', null);
		//customer_list_cells( _("Collector Name:"), 'collectors_name', null);
	//---------------------------------------//
		
	if (!$selected_id && isset($SysPrefs->auto_create_branch) && $SysPrefs->auto_create_branch == 1)
	{
		table_section_title(_("Branch"));

		$company = get_company_prefs();
		$branch_code = $company["branch_code"];
		if (!isset($_POST['location'])) {
			$_POST['location'] = $branch_code;
			$_POST['Stklocation'] = $branch_code;
		}
			
		if ($_POST['location'] != "HO") {
			label_row(_("Default Inventory Location:"), get_location_name($_POST['location']));
			hidden('location');
		} else {
			locations_list_row(_("Default Inventory Location:"), "location", $_POST['location']);
		}
		shippers_list_row(_("Default Shipping Company:"), 'ship_via');
		tax_groups_list_row(_("Tax Group:"), 'tax_group_id', null);
	}
	end_outer_table(1);

	

	div_start('controls');
	if (@$_REQUEST['popup']) hidden('popup', 1);
	if (!$selected_id)
	{
		submit_center('submit', _("Add New Customer"), true, '', false);
	} 
	else 
	{
		submit_center_first('submit', _("Update Customer"), 
		  _('Update customer data'), $page_nested ? true : false);
		submit_return('select', $selected_id, _("Select this customer and return to document entry."));
		submit_center_last('delete', _("Delete Customer"), 
		  _('Delete customer data if have been never used'), true);
	}
	div_end();
}

//--------------------------------------------------------------------------------------------

check_db_has_sales_types(_("There are no sales types defined. Please define at least one sales type before adding a customer."));
 
start_form(true);

if (db_has_customers()) 
{
	start_table(TABLESTYLE_NOBORDER);
	start_row();
	customer_list_cells(_("Select a customer: "), 'customer_id', null,
		_('New customer'), true, check_value('show_inactive'));
	check_cells(_("Show inactive:"), 'show_inactive', null, true);
	end_row();
	end_table();

	if (get_post('_show_inactive_update')) {
		$Ajax->activate('customer_id');
		set_focus('customer_id');
	}
} 
else 
{
	hidden('customer_id');
}

//if (!$selected_id || list_updated('customer_id'))
if (!$selected_id)
	unset($_POST['_tabs_sel']); // force settings tab for new customer

tabbed_content_start('tabs', array(
		'settings' => array(_('&General settings'), $selected_id),
		'contacts' => array(_('&Contacts'), $selected_id),
		'comaker' => array(_('&Co-maker'), $selected_id),
		'transactions' => array(_('&Transactions'), (user_check_access('SA_SALESTRANSVIEW') ? $selected_id : null)),
		'orders' => array(_('Sales &Orders'), (user_check_access('SA_SALESTRANSVIEW') ? $selected_id : null)),
		'attachments' => array(_('Attachments'), (user_check_access('SA_ATTACHDOCUMENT') ? $selected_id : null)),
	));
	
	switch (get_post('_tabs_sel')) {
		default:
		case 'settings':
			customer_settings($selected_id); 
			break;
		case 'contacts':
			$contacts = new contacts('contacts', $selected_id, 'customer');
			$contacts->show();
			break;
		case 'comaker':
			$comaker = new comaker('comaker', $selected_id, 'customer');
			$comaker->show();
			break;
		case 'transactions':
			$_GET['customer_id'] = $selected_id;
			include_once($path_to_root."/sales/inquiry/customer_inquiry.php");
			break;
		case 'orders':
			$_GET['customer_id'] = $selected_id;
			include_once($path_to_root."/sales/inquiry/sales_orders_view.php");
			break;
		case 'attachments':
			$_GET['trans_no'] = $selected_id;
			$_GET['type_no']= ST_CUSTOMER;
			$attachments = new attachments('attachment', $selected_id, 'customers');
			$attachments->show();
	};
br();
tabbed_content_end();

end_form();
end_page(@$_REQUEST['popup']);


//-----------------ADDED BY ROBERT-----------------//
//echo "<script src='../../js/jquery.js'></script>";
//echo "<script src='../../js/myJS.js'></script>";
//------------------------------------------------//

