<?php
/**
 * Added by: spyrax10
 * Date Added: 27 Sep 2022
*/

$page_security = 'SA_SO_DISCOUNT';
$path_to_root = "../..";

include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/sales/includes/cart_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

$js = '';

if ($SysPrefs->use_popup_windows) {
    $js .= get_js_open_window(1100, 600);
}
if (user_use_date_picker()) {
    $js .= get_js_date_picker();
}

page(_($help_context = "Pending Sales Order Discount"), false, false, '', $js);
//----------------------------------------------------------------------
if (get_post('trans')) {
    $count = 0;

    foreach(get_post('trans') as $key => $val) {
        if ($val != "Select Action") {

            foreach(get_post('dis') as $key2 => $val2) {
                if ($key == $key2) {
                    if ($val == "Approved") {
                        $discount_row = db_fetch(get_temp_discount($key));

                        if ($val2 > 0) {
                            if ($val2 > get_setup_discount($key)) {
                                display_error(_("Given discount cannot be more than Setup discount..."));
                            }
                            else {
                                $id = update_given_discount($key, 'Approved', remove_comma($val2), 
                                    $_SESSION["wa_current_user"]->user,
                                    date2sql(Today())
                                );
                               
                                if ($id) {
                                    display_notification_centered(_("Discount Request Approved..."));
                                }
                            }
                        }
                        else {
                            display_error(_("Since you are aprroving this, given discount cannot be null or zero! Dumbass!!!"));
                        }
                    }
                    else if ($val == "Disapproved") {
                        $id = update_given_discount($key, 'Disapproved', 0, 
                            $_SESSION["wa_current_user"]->user,
                            date2sql(Today())
                        );

                        if ($id) {
                            display_notification_centered(_("Discount Request Disapproved..."));
                        }
                    }
                }
            }
        }
    }

    $Ajax->activate('del_tbl');
}
//----------------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);
start_row();

sql_type_list(_("Select Category: "), 'category', 
	get_category_list(), 'category_id', 'description', 
	'', null, true, _("All Categories"), false, true
);

end_row();
end_table(); 

start_table(TABLESTYLE_NOBORDER);
start_row();

$Ajax->activate('discount_tbl');

end_row();
end_table(); 

$result = get_temp_discount('', get_post('category'));

div_start('del_tbl');
start_table(TABLESTYLE, "width='98%'");

$th = array(
    _("#"),
    _("SO Reference"),
    _("Customer"),
    _("Payment Type"),
    _("Date Created"),
    _("Prepared By"),
    _("Category"),
    _("Item Code"),
    _("Document Total"),
    _("Status"),
    _("Setup Discount"),
    _("Approved By"),
    _("Date Approved"),
    _("Given Discount"),
    _(""), _("")
);

table_header($th);

$k = 0;

while ($data = db_fetch_assoc($result)) {
    alt_table_row_color($k);
    
    label_cell($data['id']);
    label_cell($data['so_ref']);
    label_cell(get_customer_name($data['debtor_no']));
    label_cell($data['pay_type'], "nowrap align='center'");
    label_cell(phil_short_date($data['date_created']), "nowrap align='center'; style='color: blue'");
    label_cell(get_user_name($data['user_id']), "nowrap align='center'");
    label_cell(get_category_name($data['category']), "nowrap align='center'");
    label_cell($data['item_code'], "nowrap align='left'");
    label_cell(price_format($data['doc_total']), "align='right'");
    label_cell($data['status'], "nowrap align='center'");
    label_cell(price_format($data['setup_discount']), "align='right'");
    label_cell(get_user_name($data['aprroved_by']), "nowrap align='center'");
    label_cell(phil_short_date($data['date_approved']), "nowrap align='center'; style='color: blue'");
    label_cell(price_format($data['given_discount']), "align='right'");

    if ($data['status'] == "Pending") {
        text_cells(null, "dis[" . $data['id'] . "]", null, 5, 5);
        label_cell(
            value_type_list(null, "trans[" . $data['id'] . "]", 
                array(
                    "DEFAULT" => "Select Action",
                    1 => "Approved",
                    2 => "Disapproved",
                ), '', null, true
            )
        );
    }
    // else if ($data['status'] == "Approved") {
    //     text_cells(null, "upd_dis[" . $data['id'] . "]", null, 5, 5);
    //     label_cell(
    //         value_type_list(null, "upd_trans[" . $data['id'] . "]", 
    //             array(
    //                 "DEFAULT" => "Select Action",
    //                 1 => "Update Discount",
    //                 2 => "Reset Discount"
    //             ), '', null, true
    //         )
    //     );
    // }
    else {
        label_cell("");
        label_cell("");
    }
}

end_table();
div_end();

//----------------------------------------------------------------------
end_form();
end_page();
