<?php

/*Added by Albert 12/22/2022*/

$page_security = 'SA_ACCOUNTS_RECON';
$path_to_root = "..";
include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/includes/banking.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(800, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

add_js_file('reconcile.js');

page(_($help_context = "Control Accounts Reconcilation"), false, false, "", $js);

check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));

function rec_checkbox($row)
{
	$name = "rec_" .$row['counter'];
	$hidden = 'last['.$row['counter'].']';
	$value = $row['ischecked'] != 0;

// save also in hidden field for testing during 'Reconcile'
	return checkbox(null, $name, $value, true, _('Reconcile this transaction'))
 		. hidden($hidden, $value, false);
}

function systype_name($dummy, $type)
{
	global $systypes_array;
	
	return $systypes_array[$type];
}
function update_gl_trans($id, $check_value){
	$sql = "UPDATE " . TB_PREF . "gl_trans SET ischecked = ".db_escape($check_value)." where counter =".db_escape($id);
	db_query($sql, 'Concurrent editing conflict while gl_trans update check box');
	
}

function change_tpl_flag($reconcile_id)
{
	global	$Ajax;

	$check_value = 0;

	if(get_gl_check($reconcile_id) == 1){
			
		update_gl_trans($reconcile_id, $check_value);

	}else{
		$check_value = 1;

		update_gl_trans($reconcile_id, $check_value);
	}
		
	return true;
}

function set_tpl_flag($reconcile_id)
{
	global	$Ajax;

	$check_value = 1;
	
	update_gl_trans($reconcile_id, $check_value);
}
Function get_gl_check($id){
	$sql = "SELECT 
				ischecked 
			FROM ".TB_PREF."gl_trans
			where counter =".db_escape($id);

	$result = db_query($sql, "Cant get check box value");
	$row = db_fetch_row( $result);

	return $row[0];

}
Function get_gl_check_list(){
	$sql = "SELECT 
			counter
			FROM ".TB_PREF."gl_trans
			where ischecked = 1";

	$result = db_query($sql, "Cant get check box value");

	return $result;

}



$id = find_submit('_rec_');
if ($id != -1) {
	change_tpl_flag($id);
}

if (isset($_POST['ReconcileAll'])) {

	foreach($_POST['last'] as $id => $value)
		if(get_gl_check($id)){
			// change_tpl_flag($id);

			$credit_amount = 0;
			$data = db_fetch(get_gl_trans('', '', '', $id));

			//this is to get the credit amount
			if($data['amount']< 0){
				$credit_id = $id;
				$credit_amount = $data['amount'];

				foreach($_POST['last'] as $trans_id => $value){
					if(get_gl_check($trans_id)){
						$data = db_fetch(get_gl_trans('', '', '', $trans_id));
						//get the debit amount
						if($data['amount'] > 0){
			
							display_warning("game".$credit_amount);

						}
					}
				}

			}

		}

    $Ajax->activate('_page_body');
}
//------------------------------------------------------------------------------------------------
start_form();
start_table(TABLESTYLE_NOBORDER);
start_row();
gl_all_accounts_list_cells(_("Account:"), 'bank_account', null, false, false, _("All Accounts"), true);
ref_cells(_("Masterfile:"), 'masterfile', '',null, _('Enter Masterfile fragment or leave empty'));

date_cells(_("From:"), 'TransFromDate', '', null, -user_transaction_days());
date_cells(_("To:"), 'TransToDate');

end_row();
end_table();

start_table(TABLESTYLE);

start_row();
ref_cells(_("Memo:"), 'Memo', '',null, _('Enter memo fragment or leave empty'));
small_amount_cells(_("Amount min:"), 'amount_min', null, " ");
small_amount_cells(_("Amount max:"), 'amount_max', null, " ");
submit_cells('Show',_("Show"),'','', 'default');

end_row();
end_table();
echo "<hr>";
//------------------------------------------------------------------------------------------------

if (!isset($_POST['bank_account']))
    $_POST['bank_account'] = "";

$sql = get_gl_transactions_list(
	get_post('TransFromDate'), 
	get_post('TransToDate'), -1,
	get_post("bank_account"), 
	0, 
	0, null,
	input_num('amount_min'), 
	input_num('amount_max'), null, null,
	get_post('Memo'),
	get_post('masterfile')
);

	$cols =
	array(
		_("Type") => array('fun'=>'systype_name', 'ord'=>''),
		_("#"),
		_("Masterfile"),
		_("Reference"),
		_("Amount"),
		_("Balance Due"),

		"X"=>array('insert'=>true, 'fun'=>'rec_checkbox')
	   );
	$table =& new_db_pager('trans_tbl', $sql, $cols);

	$table->width = "80%";
	display_db_pager($table);

br(1);
echo '<center>';
//submit_center_first('Reconcile', _("Reconcile"), true, '', null);
submit_center_first('ReconcileAll', _("Reconcile All"), '', 'default');
echo '</center>';
end_form();

//------------------------------------------------------------------------------------------------

end_page();
