<?php

/**
 * Added by: spyrax10
 * Date Added: 29 Jun 2022 
*/

$page_security = 'SA_FISCAL_MONTH';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/admin/db/company_db.inc");
include_once($path_to_root . "/admin/db/fiscalyears_db.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/sales/includes/db/cust_trans_db.inc");
include_once($path_to_root . "/admin/db/maintenance_db.inc");

$js = "";

if (user_use_date_picker()) {
    $js .= get_js_date_picker();
}

page(_($help_context = "Posting Period Maintenance"), false, false, "", $js);

simple_page_mode(true);

//---------------------------------------------------------------------------------------------
if (!posting_period_exists(get_year(Today()))) {
    $new = generate_posting_period();
    display_notification(_("New Posting Period Sucessfully Generated!"));
}

if (get_post('posting')) {
    $count = 0;
    foreach(get_post('posting') as $key => $val) {
        if ($val != "Select Action") {
            $status = $val == "Locked" ? 1 : 0;
            update_posting_period($key, $status);
            $count++;
        }
    }
    if ($count > 0) {
        display_notification(_("Posting Period Sucessfully Updated!"));
        $Ajax->activate('posting_div');
    }
}

if (get_post('year_list')) {
    if (!posting_period_exists(get_post('year_list'))) {
        $new = generate_posting_period(get_post('year_list'));
        display_notification(_("New Posting Period Sucessfully Generated!"));
    }
    $Ajax->activate('posting_div');
}

if (get_post('year_upd')) {
   
    if (get_post('year_upd') != 'Select Action') {
        $status = get_post('year_upd') == "Locked" ? 1 : 0;
        $id = update_posting_period(get_post('year_list'), $status, true);
        if ($id) {
            display_notification(_("Posting Period Sucessfully Updated! (YEAR: " . get_post('year_list') . ")"));
            $Ajax->activate('posting_div');
            unset($_POST['year_upd']);
        }
    }
}

//---------------------------------------------------------------------------------------------

start_form();

div_start("posting_div");

start_outer_table(TABLESTYLE2, "width='70%'");

table_section(1, "24%");

$default_year = isset($_POST['year_list']) ? $_POST['year_list'] : get_year(Today());

range_type_list(_("Select Year: "), "year_list", get_max_fiscalyear(), get_max_fiscalyear(true), 
    "DESC", "&nbsp;&nbsp;", '', $default_year, true
);

label_row(null, ''); label_row(null, '');

value_type_list(_("Update Whole Year: &nbsp;"), "year_upd", 
    array(
        "DEFAULT" => "Select Action",
        1 => "Locked",
        0 => "UnLocked"
    ), '', null, true, '', false, true
);

table_section(2, "76%");

start_table(TABLESTYLE2, "width='100%'");

$sql = get_posting_period(get_post('year_list'));

$th = array(
    _("ID"),
    _("Begin Date"),
    _("End Date"),
    _("Status"),
    _("Last Update"),
    _("Updated By"),
    _("")
);

table_header($th);

$k = 0;

while ($row = db_fetch_assoc($sql)) {

    if ($row['locked'] == 1) {
        start_row("class='overduebg'");
    }
    else {
        alt_table_row_color($k);
    }

    $status = $row['locked'] == 0 ? "Unlocked" : "Locked";

    label_cell($row['id']);
    label_cell(phil_short_date($row['begin_date']), "nowrap align='center'");
    label_cell(phil_short_date($row['end_date']), "nowrap align='center'");
    label_cell($status, "nowrap align='center'");
    label_cell(phil_short_date($row['last_update']), "nowrap align='center'");
    label_cell(get_user_name($row['updated_by']), "nowrap align='center'");
    label_cell(
        value_type_list(null, "posting[" . $row['id'] . "]", 
            array(
                "DEFAULT" => "Select Action",
                1 => "Locked",
                0 => "UnLocked"
            ), '', null, true
        )
    );
}

end_table();
display_note("Mark Rows are LOCKED for futher data entry...", 0, 0, "class='overduefg'");

end_outer_table(1);

div_end();

br(2);

end_form();
//---------------------------------------------------------------------------------------------
end_page();