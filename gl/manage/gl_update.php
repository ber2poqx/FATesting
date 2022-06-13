<?php

/**
 * Journal Entry Update
 * Added by: spyrax10
 * Date Added: 11 Jun 2022
*/

$page_security = 'SA_JE_UPDATE';
$path_to_root = "../..";

include_once($path_to_root . "/includes/ui/items_cart.inc");

include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/gl/includes/ui/gl_journal_ui.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/includes/aging.inc");

$js = '';
if ($SysPrefs->use_popup_windows) {
	$js .= get_js_open_window(800, 500);
}
if (user_use_date_picker()) {
	$js .= get_js_date_picker();
}

if (isset($_GET['trans_no'])) { 
	$_SESSION['page_title'] = sprintf(_("Modifying Journal Transaction # %d"), $_GET['trans_no']);
	$help_context = "Modifying Journal Entry";
}

page($_SESSION['page_title'], false, false, '', $js);
//---------------------------------------------------------------------------------------------

if (isset($_GET['trans_no']) && JE_exists($_GET['trans_no'])) {
    check_is_editable(ST_JOURNAL, $_GET['trans_no']);
    new_cart($_GET['trans_no']);
}
else {
    display_error(_("Cannot find this Journal Entry Transaction!"));
	hyperlink_params("$path_to_root/gl/gl_journal.php", _("Enter &New Journal Entry"), "NewJournal=Yes");
	display_footer_exit();
}

function new_cart($trans_no) {

    $mcode = $masterfile = '';
    $id = -1;

    $head_row = get_JE_transactions($trans_no, true);
    $details = get_JE_transactions($trans_no);

    if (isset($_SESSION['journal_items'])) {
        unset ($_SESSION['journal_items']);
    }

    $cart = new items_cart(ST_JOURNAL);

    $cart->order_id = $trans_no;
	$cart->reference = $head_row['reference'];
	$cart->tran_date = $head_row['tran_date'];
	$cart->doc_date = $head_row['doc_date'];
	$cart->event_date = $head_row['event_date'];
	$cart->source_ref = $head_row['source_ref'];
	$cart->trans_db = user_company();
	$cart->currency = 'PHP';
    $cart->rate = $head_row['rate'];

    $cart->memo_ = get_comments_string(ST_JOURNAL, $trans_no);

	if ($cart->currency != get_company_pref('curr_default')) {
		$cart->rate = input_num('_ex_rate');
	}

	$cart->tax_info = false;

    while ($row = db_fetch($details)) {
        $id++;
        hidden('Index', $id);

        $_POST['memo_'] = $row['memo_'];

        $cust_row = get_customer($row['mcode']);
        $comp_id = $row['interbranch'] == 0 ? user_company() : get_comp_id($row['mcode']);

        if ($row['loan_trans_no'] > 0 && $row['interbranch'] == 0) {
            $mcode = $cust_row['debtor_ref'];
        }
        else {
            $mcode = $row['mcode'];
        }
        
        $cart->add_gl_item(
            $row['account'], 0, 0, 
	        $row['line_amount'], 
	        $row['memo_'], 
	        null, 
	        null,
	        null,
		    $mcode,
		    $row['master_file'],
		    $row['hocbc_id'], 
		    $comp_id, ''
        );
    }

    $_SESSION['journal_items'] = &$cart;
}

function display_JE_header($trans_no) {

    $row = get_JE_transactions($trans_no, true);
    $debtor_row = get_SI_by_reference($row['source_ref']);
    $inv_row = db_fetch_assoc(get_customer_invoices($debtor_row['debtor_no'], $row['source_ref']));
    $cust_row = get_customer($inv_row['cust_id']);

    $current_bal = current_balance_display(
        $inv_row['trans_no'],
        $inv_row['type'],
        $inv_row['cust_id'],
        date2sql(Today())
    );

    div_start('item_head');
	start_outer_table(TABLESTYLE2, "width='70%'");
    
    table_section(1);
    label_row('Journal Date: &nbsp;', phil_short_date($row['tran_date']));
	hidden('date_', sql2date($row['tran_date']));
    date_row(_("Set New Journal Date: &nbsp;"), 'new_je_date', '', true);

    table_section(2);
    label_row('JE Reference: &nbsp;', $row['reference']);
    if ($debtor_row["trans_no"] != null) {
        label_row(_('Source Reference: &nbsp;'), get_trans_view_str(ST_SALESINVOICE, $debtor_row["trans_no"], $row['source_ref']));
        hidden('source_ref', $row['source_ref']);
        
        table_section(3);
        label_row("Customer: ", $cust_row['debtor_ref'] . " - " . $cust_row['name']);
        label_row("Invoice Type: ", $inv_row['inv_type']);
		label_row("Category: ", $inv_row['stock_name']);
		label_row("Model / Description: ", $inv_row['model'] . " / " . $inv_row['model_desc']);
		label_row("Color Description: ", $inv_row['color_code']);

		label_row("Current Invoice Balance: &nbsp;", 
			price_format($current_bal)
		);

        hidden('profit_margin', $inv_row['profit_margin']);
        hidden('source_ref2', $row['source_ref']);
    }
    else {
        text_row('Source Reference: &nbsp;', 'source_ref', $row['source_ref'], 30, 30);
    }

	hidden('ref', $row['reference']);
	hidden('old_doc_date', sql2date($row['doc_date']));
	hidden('old_event_date', sql2date($row['event_date']));

    end_outer_table(1);
	div_end();
}

//---------------------------------------------------------------------------------------------
function handle_update_item() {

    $coy = user_company();
	$line_item = $_SESSION['journal_items']->gl_items[$_POST['Index']];
    $branch_code = $branch_name = '';

    if ($_POST['UpdateItem'] != '' && check_item_data()) {

        if (input_num('AmountDebit') > 0) {
			$amount = input_num('AmountDebit');
		}
    	else {
			$amount = -input_num('AmountCredit');
		}
      
        if (gl_comp_name($_POST['mcode'], true) != '') {
			$branch_code = gl_comp_name($_POST['mcode'], true);
		}
		else {
			$branch_code = $_POST['mcode'];
		}
	
		if (gl_comp_name($_POST['mcode']) != '') {
			$branch_name = gl_comp_name($_POST['mcode']);
		}
		else {
			$branch_name =  get_slname_by_ref($_POST['mcode']);
		}
    		
    	$_SESSION['journal_items']->update_gl_item(
    	    $_POST['Index'], 
			$line_item->code_id, 
    	    0, 
    	    0, 
    	    $amount, 
    	    '',
    	    '', 
    	    null,
			$branch_code,
			$branch_name,
    	    isset($_POST['hocbc_id']) ? $_POST['hocbc_id'] : 0, 
			$line_item->comp_id,
			$line_item->sug_mcode
    	);
    }
    line_start_focus();
}
//---------------------------------------------------------------------------------------------
function check_item_data() {

    if (!(input_num('AmountDebit') != 0 ^ input_num('AmountCredit') != 0)) {
		display_error(_("You must enter either a debit amount or a credit amount."));
		set_focus('AmountDebit');
    	return false;
  	}

	if (strlen($_POST['AmountDebit']) && !check_num('AmountDebit', 0)) {
    	display_error(_("The debit amount entered is not a valid number or is less than zero."));
		set_focus('AmountDebit');
    	return false;
  	} 
    elseif (strlen($_POST['AmountCredit']) && !check_num('AmountCredit', 0)) {
    	display_error(_("The credit amount entered is not a valid number or is less than zero."));
		set_focus('AmountCredit');
    	return false;
  	}

    return true;
}
//---------------------------------------------------------------------------------------------
function line_start_focus() {
    global $Ajax;
  
    unset($_POST['Index']);
    unset($_POST['sug_mcode']);
    unset($_POST['mcode']);
    unset($_POST['amount']);
    unset($_POST['AmountDebit']);
    unset($_POST['AmountCredit']);
    $Ajax->activate('items_table');
}

if (isset($_POST['UpdateItem'])) {
	handle_update_item();
}

if (isset($_POST['CancelItemChanges'])) {
	global $Ajax;
    unset($_POST['Index']);
    $Ajax->activate('items_table');
}

if (isset($_POST['Process'])) {
    display_error(_("Under Construction! Please Wait..."));
}

//---------------------------------------------------------------------------------------------
start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

display_JE_header($_GET['trans_no']);
display_gl_items(null, $_SESSION['journal_items'], false);
gl_options_controls();

br(2);
submit_center('Process', _("Update Journal Entry"), true , null, 'default');
br();

end_form();
end_page();

