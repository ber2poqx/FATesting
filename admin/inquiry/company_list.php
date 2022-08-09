<?php
/**
 * Added by: spyrax10
 * Date Added: 9 Aug 2022 
*/

$page_security = "SA_ITEMPOPUPVIEW";
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");

$js = get_js_select_combo_item();

page(_($help_context = "List of Branches"), true, false, "", $js);

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);

start_row();

ref_cells(_("Search for Branch: &nbsp;"), 'search', '', null, '', true);

end_row();

end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

global $Ajax;
$Ajax->activate('comp_tbl');

end_row();
end_table(); 


div_start("comp_tbl");

start_table(TABLESTYLE, "width='80%'");

$th = array(_("ID"), _("Branch Code"), _("Branch Name"), _(""));

table_header($th);

$k = 0;
$name = $_GET["client_id"];

$sql = db_query(company_list_row(
    null, 'comp_sql', false, false, true, false, 
    false, false, true, true, false, get_post('search')
));

while ($row = db_fetch_assoc($sql)) {
    alt_table_row_color($k);
    $coy = $row['coy'];
    $branch_name = $row['branch_name'];

    label_cell($coy);
    label_cell($row['branch_code']);
    label_cell($branch_name);
    label_cell(ahref_cell(
        _("Select"), 'javascript:void(0)', '', 
        'selectComboItem(window.opener.document, "'.$name.'",  "'.$coy.'", "'.$branch_name.'")')
    );
}

end_table(1);

div_end();

end_page(true);
