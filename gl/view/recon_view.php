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
$page_security = 'SA_RECONVIEW';
$path_to_root = "../..";

include($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 600);

page(_($help_context = "Internal Reconcilation"), true, false, "", $js);

if (isset($_GET["recon_no"])) {
	$recon_no = $_GET["recon_no"];
}

$result = recon_data($recon_no);

$from_trans = db_fetch($result);
//$person_type = get_person_type($from_trans['person_type_id'], false);

display_heading(_("Internal Reconcilation Report"));

echo "<br>";
start_table(TABLESTYLE, "width='80%'");

start_row();
label_cells(_("GL Account"), $from_trans['account'], "class='tableheader2'");
end_row();
start_row();
label_cells(_("GL Account Name"), $from_trans['account_name'], "class='tableheader2'");

end_row();


end_table(1);


$items = recon_data_details($recon_no, );

echo "<br>";
start_table(TABLESTYLE, "width='80%'");

$th = array(_("Date"), _("Reference"), _("Name"), _("Particulars"),
        _("Balance"));
table_header($th);

$k = 0; //row colour counter
$total_amount = 0;

while ($item = db_fetch($items)) {
    alt_table_row_color($k);
    label_cell($item["reconcile_date"]);
    label_cell($item["reference"]);
    label_cell($item["master_file"]);
    label_cell($item["memo_"]);
    amount_cell($item["balance_due"]);

    $total_amount += $item["balance_due"];
}

label_row(_("Total"), number_format2($total_amount, user_price_dec()),"", "align=right", "align=right");

end_table(1);
end_page();
