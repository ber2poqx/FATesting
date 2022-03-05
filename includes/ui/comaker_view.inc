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
include_once($path_to_root. '/includes/ui/simple_crud_class.inc');
include_once($path_to_root . "/includes/db/crm_contacts_db.inc");

/*
	View/Edit class for contacts
*/
class comaker extends simple_crud {
	var $id;
	var $comaker_id;
	var $sub_class;
	var $class;
	
	function __construct($name, $id, $class, $subclass=null) {
		$fields = array('firstname','middlename','lastname', 'phone', 'address', 'facebook');

		parent::__construct($name, $fields);
		$this->class = $class;
		$this->subclass = $subclass;
		$this->comaker_id = $id;
	}

	function list_view() {
		br();

			$comaker = get_comaker_customers($this->class, $this->subclass);
				//$result = get_sales_areas(check_value('show_inactive'));
			start_table(TABLESTYLE, "width='70%'");

			$th = array(_("Full Name"), _("Phone"), _("Address"), _("Facebook"), _("Status"), '');
		
			table_header($th);
			$k = 0; 

			while ($myrow = db_fetch($comaker)) 
			{	
				alt_table_row_color($k);
				label_cell($myrow["firstname"]. ' ' . $myrow["middlename"] .' '.$myrow["lastname"]);
				label_cell($myrow["phone"]);
				label_cell($myrow["address"]);
				label_cell($myrow["facebook"]);
				label_cell($myrow["Status"]);
			 	edit_button_cell("{$this->name}Edit[{$myrow['comaker_id']}]", _("Edit"));

				// delete button removes only category unless this is the last contact for this person
				//delete_button_cell("{$this->name}Delete[{$myrow['comaker_id']}]", _("Delete"));
				end_row();
			}
			end_table();
	}
	
	function editor_view()
	{
		br();

		start_outer_table(TABLESTYLE);

		table_section(1);
		table_section_title(_("Co-Maker data"));

		text_row(_("First Name:"), 'firstname', @$this->data['firstname'], 35, 40);
		text_row(_("Middle Name:"), 'middlename', @$this->data['middlename'], 35, 40);
		text_row(_("Last Name:"), 'lastname', @$this->data['lastname'], 35, 40);
		text_row(_("Phone:"), 'phone', @$this->data['phone'], 35, 40);
		text_row(_("Facebook:"), 'facebook', @$this->data['facebook'], 35, 40);

		if ($this->selected_id == $myrow['comaker_id']) {

		} else {
			active_inactive_list_row(_("Status:"), 'inactive', @$this->data['inactive']); 
		}
		table_section(2);
		table_section_title("&nbsp;");
		textarea_row(_("Address:"), 'address', @$this->data['address'], 35, 4);
		end_outer_table(1);
	}

	function db_insert() {
			$dat = $this->data;
			$selected_id = $_POST['customer_id'];
			return add_comaker_person($selected_id, $dat['firstname'], $dat['middlename'], $dat['lastname'],
				$dat['phone'], $dat['address'], $dat['facebook']);
	}

	function db_update() {
			//$selected_id = $_POST['customer_id'];
			//return update_comaker_customer_before_insert($selected_id);
			return update_comaker_customer($this->selected_id, $_POST['firstname'], $_POST['middlename'], $_POST['lastname'], 
				$_POST['phone'], $_POST['address'], $_POST['facebook'], $_POST['inactive'], $this->class);			
	}

	function insert_check() {
		if (strlen($_POST['firstname']) == 0) {
			display_error(_("The first name cannot be empty."));
			set_focus('firstname');
			return false;
		}
		if (strlen($_POST['middlename']) == 0) {
			display_error(_("The middle name cannot be empty."));
			set_focus('middlename');
			return false;
		}
		if (strlen($_POST['lastname']) == 0) {
			display_error(_("The last name cannot be empty."));
			set_focus('lastname');
			return false;
		}
		if (strlen($_POST['phone']) == 0) {
			display_error(_("Phone number cannot be empty."));
			set_focus('phone');
			return false;
		}
		if (strlen($_POST['address']) == 0) {
			display_error(_("The address cannot be empty."));
			set_focus('address');
			return false;
		}		
		return true; 
	}

	function db_read() {
		return get_comaker_customer($this->selected_id);
	}

	function delete_check() {
		return true;
	}
	//
	//	Delete all contacts for person in current class/entity
	//
	//function db_delete() {
		//return delete_comaker_person($this->selected_id, $this->class);

	//}
}

