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
$page_security = 'SA_RRTRANSWORLDVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

include_once($path_to_root . "/includes/ui.inc");

function get_transworld_movements($rrbrand_type)
{
	$sql = "SELECT * FROM " . TB_PREF . "rr_transword";

	return db_query($sql, "could not query stock moves");
}


$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(800, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

$_SESSION['page_title'] = _($help_context = "INVENTORY RR TRANSWORLD MONITORING");

page($_SESSION['page_title'], isset($_GET['stock_id']), false, "", $js);
//------------------------------------------------------------------------------------------------
$result = get_transworld_movements($_POST['rrbrand_type']);

div_start('doc_tbl');
start_table(TABLESTYLE);
$th = array(_("TYPE"), _("SI NUMBER"), _("BRANCH"), _("MODEL"), _("SERIAL/ENGINE"), _("CHASIS"), _("PO NUMBER"), _("INT. DATE"), _("PULL-OUT DATE"), _("SI DATE"), _("DUE DATE"), _("LOCATION"));
table_header($th);
while ($myrow = db_fetch($result))
{
	alt_table_row_color($k);

	$invt_date = sql2date($myrow["invt_date"]);
	$pull_date = sql2date($myrow["pull_date"]);
	$si_date = sql2date($myrow["si_date"]);
	$due_date = sql2date($myrow["due_date"]);

	label_cell($myrow['rrbrand_type']);
	label_cell($myrow['si_no']);
	label_cell($myrow['outlatename']);
	label_cell($myrow['salesname']);
	label_cell($myrow['eng_no']);
	label_cell($myrow['frame_no']);
	label_cell($myrow['po_no']);
	label_cell($invt_date);
	label_cell($invt_date);
	label_cell($si_date);
	label_cell($due_date);
	label_cell($myrow['loc_code']);
	end_row();
}

end_table(1);
div_end();
end_page();

