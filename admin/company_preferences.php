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
$page_security = 'SA_SETUPCOMPANY';
$path_to_root = "..";
include($path_to_root . "/includes/session.inc");

page(_($help_context = "Company Setup"));

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/admin/db/company_db.inc");
//-------------------------------------------------------------------------------------------------

if (isset($_POST['update']) && $_POST['update'] != "")
{
	$input_error = 0;
	/*  Added by Ronelle 12/16/2020 */
	if (!check_num('pr_expired', 1))
	{
		display_error(_("PR Expiration must be positive number."));
		set_focus('pr_expired');
		$input_error = 1;
	}
	/* */
	if (!check_num('login_tout', 10))
	{
		display_error(_("Login timeout must be positive number not less than 10."));
		set_focus('login_tout');
		$input_error = 1;
	}
	if (strlen($_POST['coy_name'])==0)
	{
		$input_error = 1;
		display_error(_("The company name must be entered."));
		set_focus('coy_name');
	}
	if (!check_num('tax_prd', 1))
	{
		display_error(_("Tax Periods must be positive number."));
		set_focus('tax_prd');
		$input_error = 1;
	}
	if (!check_num('tax_last', 1))
	{
		display_error(_("Tax Last Periods must be positive number."));
		set_focus('tax_last');
		$input_error = 1;
	}
	if (!check_num('round_to', 1))
	{
		display_error(_("Round Calculated field must be a positive number."));
		set_focus('round_to');
		$input_error = 1;
	}
	if ($_POST['add_pct'] != "" && !is_numeric($_POST['add_pct']))
	{
		display_error(_("Add Price from Std Cost field must be number."));
		set_focus('add_pct');
		$input_error = 1;
	}	
	if (isset($_FILES['pic']) && $_FILES['pic']['name'] != '')
	{
    if ($_FILES['pic']['error'] == UPLOAD_ERR_INI_SIZE) {
			display_error(_('The file size is over the maximum allowed.'));
			$input_error = 1;
    }
    elseif ($_FILES['pic']['error'] > 0) {
			display_error(_('Error uploading logo file.'));
			$input_error = 1;
    }
		$result = $_FILES['pic']['error'];
		$filename = company_path()."/images";
		if (!file_exists($filename))
		{
			mkdir($filename);
		}
		$filename .= "/".clean_file_name($_FILES['pic']['name']);

		 //But check for the worst
		if (!in_array( substr($filename,-4), array('.jpg','.JPG','.png','.PNG')))
		{
			display_error(_('Only jpg and png files are supported - a file extension of .jpg or .png is expected'));
			$input_error = 1;
		}
		elseif ( $_FILES['pic']['size'] > ($SysPrefs->max_image_size * 1024))
		{ //File Size Check
			display_error(_('The file size is over the maximum allowed. The maximum size allowed in KB is') . ' ' . $SysPrefs->max_image_size);
			$input_error = 1;
		}
		elseif ( $_FILES['pic']['type'] == "text/plain" )
		{  //File type Check
			display_error( _('Only graphics files can be uploaded'));
			$input_error = 1;
		}
		elseif (file_exists($filename))
		{
			$result = unlink($filename);
			if (!$result)
			{
				display_error(_('The existing image could not be removed'));
				$input_error = 1;
			}
		}

		if ($input_error != 1)
		{
			$result  =  move_uploaded_file($_FILES['pic']['tmp_name'], $filename);
			$_POST['coy_logo'] = clean_file_name($_FILES['pic']['name']);
			if(!$result) 
				display_error(_('Error uploading logo file'));
		}
	}
	if (check_value('del_coy_logo'))
	{
		$filename = company_path()."/images/".clean_file_name($_POST['coy_logo']);
		if (file_exists($filename))
		{
			$result = unlink($filename);
			if (!$result)
			{
				display_error(_('The existing image could not be removed'));
				$input_error = 1;
			}
		}
		$_POST['coy_logo'] = "";
	}
	if (strlen($_POST['penalty_rate'])==0)
	{
		$input_error = 1;
		display_error(_("The company name must be entered."));
		set_focus('penalty_rate');
	}
	if ($_POST['add_pct'] == "")
		$_POST['add_pct'] = -1;
	if ($_POST['round_to'] <= 0)
		$_POST['round_to'] = 1;
	if ($input_error != 1)
	{
		update_company_prefs(			
			get_post( array('coy_name','branch_code', 'coy_no','gst_no','tin','tax_prd','tax_last',
				'postal_address','phone', 'fax', 'email', 'coy_logo', 'domicile',
				'use_dimension', 'curr_default', 'f_year', 'shortname_name_in_list',
				'no_item_list' => 0, 'no_customer_list' => 0, 
				'no_supplier_list' =>0, 'base_sales', 'ref_no_auto_increase' => 0,
				'time_zone' => 0, 'company_logo_report' => 0, 'barcodes_on_stock' => 0, 'print_dialog_direct' => 0, 
				'add_pct', 'round_to', 'login_tout', 'auto_curr_reval', 'bcc_email', 'alternative_tax_include_on_docs', 
				'suppress_tax_rates', 'use_manufacturing', 'use_fixed_assets', 'pr_expired', 'penalty_rate',
				'default_rebate_valid_month', 'deployment_status'))
		);

		$_SESSION['wa_current_user']->timeout = $_POST['login_tout'];
		display_notification_centered(_("Company setup has been updated."));
		set_focus('coy_name');
		$Ajax->activate('_page_body');
	}
} /* end of if submit */

start_form(true);

$myrow = get_company_prefs();

//Added by spyrax10 8 Apr 2022
$_POST['deployment_status'] = $myrow["deployment_status"];
//
$_POST['coy_name'] = $myrow["coy_name"];
$_POST['gst_no'] = $myrow["gst_no"];
$_POST['tin'] = $myrow["tin"];
$_POST['tax_prd'] = $myrow["tax_prd"];
$_POST['tax_last'] = $myrow["tax_last"];
$_POST['coy_no']  = $myrow["coy_no"];
$_POST['postal_address']  = $myrow["postal_address"];
$_POST['phone']  = $myrow["phone"];
$_POST['fax']  = $myrow["fax"];
$_POST['email']  = $myrow["email"];
$_POST['coy_logo']  = $myrow["coy_logo"];
$_POST['domicile']  = $myrow["domicile"];
$_POST['use_dimension']  = $myrow["use_dimension"];
$_POST['base_sales']  = $myrow["base_sales"];
if (!isset($myrow["shortname_name_in_list"]))
{
	set_company_pref("shortname_name_in_list", "setup.company", "tinyint", 1, '0');
	$myrow["shortname_name_in_list"] = get_company_pref("shortname_name_in_list");
}
if (!isset($myrow["branch_code"]))
{
    set_company_pref("branch_code", "setup.company", "varchar", 100, '');
    $myrow["branch_code"] = get_company_pref("branch_code");
}
$_POST['branch_code']  = $myrow["branch_code"];
$_POST['shortname_name_in_list']  = $myrow["shortname_name_in_list"];
$_POST['no_item_list']  = $myrow["no_item_list"];
$_POST['no_customer_list']  = $myrow["no_customer_list"];
$_POST['no_supplier_list']  = $myrow["no_supplier_list"];
$_POST['curr_default']  = $myrow["curr_default"];
$_POST['f_year']  = $myrow["f_year"];
$_POST['time_zone']  = $myrow["time_zone"];
if (!isset($myrow["company_logo_report"]))
{
	set_company_pref("company_logo_report", "setup.company", "tinyint", 1, '0');
	$myrow["company_logo_report"] = get_company_pref("company_logo_report");
}
$_POST['company_logo_report']  = $myrow["company_logo_report"];
if (!isset($myrow["ref_no_auto_increase"]))
{
	set_company_pref("ref_no_auto_increase", "setup.company", "tinyint", 1, '0');
	$myrow["ref_no_auto_increase"] = get_company_pref("ref_no_auto_increase");
}
$_POST['ref_no_auto_increase']  = $myrow["ref_no_auto_increase"];
if (!isset($myrow["barcodes_on_stock"]))
{
	set_company_pref("barcodes_on_stock", "setup.company", "tinyint", 1, '0');
	$myrow["barcodes_on_stock"] = get_company_pref("barcodes_on_stock");
}
$_POST['barcodes_on_stock']  = $myrow["barcodes_on_stock"];
if (!isset($myrow["print_dialog_direct"]))
{
	set_company_pref("print_dialog_direct", "setup.company", "tinyint", 1, '0');
	$myrow["print_dialog_direct"] = get_company_pref("print_dialog_direct");
}
if (!isset($myrow["penalty_rate"]))
{
	set_company_pref("penalty_rate", "setup.company", "varchar", 5, '0.04');
	$myrow["penalty_rate"] = get_company_pref("penalty_rate");
}
if (!isset($myrow["default_rebate_valid_month"]))
{
	set_company_pref("default_rebate_valid_month", "setup.company", "varchar", 5, '0.04');
	$myrow["default_rebate_valid_month"] = get_company_pref("default_rebate_valid_month");
}
$_POST['print_dialog_direct']  = $myrow["print_dialog_direct"];
$_POST['version_id']  = $myrow["version_id"];
$_POST['add_pct'] = $myrow['add_pct'];
$_POST['login_tout'] = $myrow['login_tout'];
if ($_POST['add_pct'] == -1)
	$_POST['add_pct'] = "";
$_POST['round_to'] = $myrow['round_to'];	
$_POST['auto_curr_reval'] = $myrow['auto_curr_reval'];	
$_POST['del_coy_logo']  = 0;
$_POST['bcc_email']  = $myrow["bcc_email"];
$_POST['alternative_tax_include_on_docs']  = $myrow["alternative_tax_include_on_docs"];
$_POST['suppress_tax_rates']  = $myrow["suppress_tax_rates"];
$_POST['use_manufacturing']  = $myrow["use_manufacturing"];
$_POST['use_fixed_assets']  = $myrow["use_fixed_assets"];

/* Added by Ronelle 12/16/2020 */
$_POST['pr_expired'] = $myrow["pr_expired"];
/* */

//added by JR on 06-11-21
$_POST['penalty_rate'] = $myrow["penalty_rate"];
$_POST['default_rebate_valid_month'] = $myrow["default_rebate_valid_month"];

start_outer_table(TABLESTYLE2);

table_section(1);
table_section_title(_("General settings"));

//Added by spyrax10 8 Apr 2022
check_row(_("Deployment Status:"), 'deployment_status', $_POST['deployment_status']);
//
text_row_ex(_("Name (to appear on reports):"), 'coy_name', 50, 50);
text_row_ex(_("Branch Code:"), 'branch_code', 20, 50);
textarea_row(_("Address:"), 'postal_address', $_POST['postal_address'], 34, 5);
text_row_ex(_("Domicile:"), 'domicile', 25, 55);

text_row_ex(_("Phone Number:"), 'phone', 25, 55);
text_row_ex(_("Fax Number:"), 'fax', 25);
email_row_ex(_("Email Address:"), 'email', 50, 55);

email_row_ex(_("BCC Address for all outgoing mails:"), 'bcc_email', 50, 55);

text_row_ex(_("Official Company Number:"), 'coy_no', 25);
text_row_ex(_("GSTNo:"), 'gst_no', 25);
text_row_ex(_("TIN:"), 'tin', 20, 50);
currencies_list_row(_("Home Currency:"), 'curr_default', $_POST['curr_default']);

label_row(_("Company Logo:"), $_POST['coy_logo']);
file_row(_("New Company Logo (.jpg)") . ":", 'pic', 'pic');
check_row(_("Delete Company Logo:"), 'del_coy_logo', $_POST['del_coy_logo']);

check_row(_("Automatic Revaluation Currency Accounts"), 'auto_curr_reval', $_POST['auto_curr_reval']);
check_row(_("Time Zone on Reports"), 'time_zone', $_POST['time_zone']);
check_row(_("Company Logo on Reports"), 'company_logo_report', $_POST['company_logo_report']);
check_row(_("Use Barcodes on Stocks"), 'barcodes_on_stock', $_POST['barcodes_on_stock']);
check_row(_("Auto Increase of Document References"), 'ref_no_auto_increase', $_POST['ref_no_auto_increase']);
/* Added by Ronelle 12/16/2020 */
text_row_ex(_("PR Expiration:"), 'pr_expired', 10, 10, '', null, null, _('days'));
/* */
label_row(_("Database Scheme Version"), $_POST['version_id']);

table_section(2);

table_section_title(_("General Ledger Settings"));
fiscalyears_list_row(_("Fiscal Year:"), 'f_year', $_POST['f_year']);
text_row_ex(_("Tax Periods:"), 'tax_prd', 10, 10, '', null, null, _('Months.'));
text_row_ex(_("Tax Last Period:"), 'tax_last', 10, 10, '', null, null, _('Months back.'));
check_row(_("Put alternative Tax Include on Docs"), 'alternative_tax_include_on_docs', null);
check_row(_("Suppress Tax Rates on Docs"), 'suppress_tax_rates', null);

table_section_title(_("Sales Pricing"));
sales_types_list_row(_("Base for auto price calculations:"), 'base_sales', $_POST['base_sales'], false,
    _('No base price list') );

text_row_ex(_("Add Price from Std Cost:"), 'add_pct', 10, 10, '', null, null, "%");
$curr = get_currency($_POST['curr_default']);
text_row_ex(_("Round calculated prices to nearest:"), 'round_to', 10, 10, '', null, null, $curr['hundreds_name']);
label_row("", "&nbsp;");


table_section_title(_("Optional Modules"));
check_row(_("Manufacturing"), 'use_manufacturing', null);
check_row(_("Fixed Assets"), 'use_fixed_assets', null);
number_list_row(_("Use Dimensions:"), 'use_dimension', null, 0, 2);

table_section_title(_("User Interface Options"));

check_row(_("Short Name and Name in List"), 'shortname_name_in_list', $_POST['shortname_name_in_list']);
check_row(_("Open Print Dialog Direct on Reports"), 'print_dialog_direct', null);
check_row(_("Search Item List"), 'no_item_list', null);
check_row(_("Search Customer List"), 'no_customer_list', null);
check_row(_("Search Supplier List"), 'no_supplier_list', null);
text_row_ex(_("Login Timeout:"), 'login_tout', 10, 10, '', null, null, _('seconds'));

table_section_title(_("Other Settings"));
text_row_ex(_("Penalty rate:"), 'penalty_rate', 10, 10, '', null, null, "");
rebate_month_row(_("Rebate Valid Month:"), 'default_rebate_valid_month', $_POST['default_rebate_valid_month'], false);

end_outer_table(1);

hidden('coy_logo', $_POST['coy_logo']);
submit_center('update', _("Update"), true, '',  'default');

end_form(2);
//-------------------------------------------------------------------------------------------------

end_page();

