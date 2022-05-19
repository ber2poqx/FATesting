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
/**********************************************************************
  Page for searching customer list and select it to customer selection
  in pages that have the supplier dropdown lists.
  Author: bogeyman2007 from Discussion Forum. Modified by Joe Hunt
***********************************************************************/
$page_security = "SA_SEARCHSL";
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/gl/includes/db/gl_db_banking.inc");

$mode = get_company_pref('no_sl_list');
if ($mode != 0)
	$js = get_js_set_combo_item();
else
	$js = get_js_select_combo_item();

page(_($help_context = "Masterfile List"), true, false, "", $js);

$send_type = $_GET["send_type"];

if(get_post("search")) {
  $Ajax->activate("sl_tbl");
}

//Added by spyrax10
if (get_post('_slval_changed')) {
	$Ajax->activate("sl_tbl");
}

if (get_post("_send")) {
	$Ajax->activate("sl_tbl");
}
//

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);

start_row();

//Added by spyrax10
if ($_GET['send_type'] == '') {
	numeric_type_list(_("Select Filter: "), '_send', 
		array(
			_('Supplier'),
			_('Customer'),
			_('List of Branch')
		), null, true, _('No Filter')
	);
}
//Modified by spyrax10 19 May 2022
ref_cells(_("Mcode / Masterfile : &nbsp;"), 'slval', '', null, '', true);
//
submit_cells("search", _("Search"), "", _("Search SL"), "default");

end_row();

end_table();

end_form();

div_start("sl_tbl");

start_table(TABLESTYLE);

$th = array("", _("MCode"), _("Masterfile"), _("Class"));

table_header($th);

$k = 0;
$name = $_GET["client_id"];

$result = get_list_of_sl(get_post("slval"), $send_type, get_post("_send")); //Added by spyrax10
while ($myrow = db_fetch_assoc($result)) {
	alt_table_row_color($k);
	$value = $myrow['ref'];
	if ($mode != 0) {
		$text = $myrow['name'];
  		ahref_cell(_("Select"), 'javascript:void(0)', '', 'setComboItem(window.opener.document, "'.$name.'",  "'.$value.'", "'.$text.'")');
	}
	else {
  		ahref_cell(_("Select"), 'javascript:void(0)', '', 'selectComboItem(window.opener.document, "'.$name.'", "'.$value.'")');
	}
  	label_cell(sprintf("%05s", $myrow["ref"]));
  	label_cell($myrow["name"]);
  	label_cell($myrow["class"]);
	end_row();
}

end_table(1);

div_end();

end_page(true);
