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

page(_($help_context = "Internal Reconcilation"), false, false, "", $js);

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
			sum(amount)
			FROM ".TB_PREF."gl_trans
			where ischecked = 1";

	$result = db_query($sql, "Cant get check box value");

	$row = db_fetch_row( $result);

	return $row[0];
}
function update_balance_due($balance_due, $id){
	$sql = "UPDATE " . TB_PREF . "gl_trans SET balance_due = ".db_escape($balance_due)." where counter =".db_escape($id);
	db_query($sql, 'Concurrent editing conflict while gl_trans update check box');
	
}

function add_reconcile_accounts(
	$recon_no, $type, 
	$type_no, 
	$recon_date, 
	$account, 
	$masterfile, 
	$amount, 
	$balance_due,
	$credit_ref_no,
	$credit_ref_type){

	$sql = "INSERT INTO " .TB_PREF. "reconcile_accounts (
	reconcile_no, 
	type, 
	type_no, 
	reconcile_date, 
	account, 
	master_file, 
	amount, 
	balance_due,
	credit_ref_no,
	credit_ref_type)
	VALUES(
		".db_escape($recon_no).",
		".db_escape($type).", 
		".db_escape($type_no).",
		".db_escape($recon_date).", 
		".db_escape($account).",
		".db_escape($masterfile).", 
		".db_escape($amount).",
		".db_escape($balance_due).",
		".db_escape($credit_ref_no).",
		".db_escape($credit_ref_type).")";
	
	db_query($sql,"reconcile_accounts could not be added.");
}
function get_max_recon_no(){
	$sql = "SELECT 
			max(reconcile_no)
			FROM ".TB_PREF."reconcile_accounts ";

	$result = db_query($sql, "Cant get check box value");

	$row = db_fetch_row( $result);

	return $row[0];

}



$id = find_submit('_rec_');
if ($id != -1) {
	change_tpl_flag($id);
}

if (isset($_POST['ReconcileAll'])) {

	$recon_no = get_max_recon_no() == '' ? 1 : get_max_recon_no()+1;

	foreach($_POST['last'] as $id => $value)
		if(get_gl_check($id)){		

			$credit_amount = 0;
			$data = db_fetch(get_gl_trans('', '', '', $id));

			//this is to get the credit amount
			if($data['amount']< 0){
				$credit_data = db_fetch(get_gl_trans_amount($id));
				$credit_id = $id;
				//get the total amount credit
				// foreach($amount_ as $balance_due){
				// 	$credit_amount += $balance_due;
				// }
				$credit_amount = $credit_data['amount'];
				$credit_ref_no = $data['type_no'];
				$credit_ref_type = $data['type'];


			
				foreach($_POST['last'] as $trans_id => $value){
					if(get_gl_check($trans_id)){
						$row = db_fetch(get_gl_trans('', '', '', $trans_id));
						$ref_no = $row['type_no'];
						//get the debit amount
						if($row['balance_due'] > 0){
							if($credit_amount < 0){
								$credit_amount = $credit_amount + $row['balance_due'];
							}else{
								$credit_amount = $row['balance_due'];

							}
							if(get_gl_check_list() >= 0){
								$datenow = Today();
								if($credit_amount <= 0){
									update_balance_due(0, $trans_id);
									
									add_reconcile_accounts(
										$recon_no, 
										$row['type'], 
										$row['type_no'],
										date('Y-m-d', strtotime($datenow)),
										$row['account'],
										$row['master_file'],
										$row['amount'],
										0,
										$credit_ref_no,
										$credit_ref_type
									);	

									change_tpl_flag($trans_id);

								}else{
									if($credit_amount != $row['amount']){

										update_balance_due($credit_amount, $trans_id);

										add_reconcile_accounts(
											$recon_no, 
											$row['type'], 
											$row['type_no'],
											date('Y-m-d', strtotime($datenow)),
											$row['account'],
											$row['master_file'],
											$row['amount'],
											$credit_amount,
											$credit_ref_no,
											$credit_ref_type
										);
									change_tpl_flag($trans_id);

									}else{
										display_warning("Transaction # $trans_id Gl is not reconcile");
									}
									
								}
							// display_warning("game".$balance_due);
							// display_warning("ga".$data['amount']);
							
							
							}else{
								
								display_warning("The Amount to be reconcile is not valid!!! Please allocate the payment!!!");
							}
							

						}	
						display_warning("Transaction #: $ref_no Gl is successfuly reconciled");
						change_tpl_flag($id);
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
reconciled_type(_("Recon Type:"), 'recon_type', null, true);
submit_cells('Show',_("Show"),'','', 'default');

end_row();
end_table();
echo "<hr>";
//------------------------------------------------------------------------------------------------

if (!isset($_POST['bank_account']))
    $_POST['bank_account'] = "";

	$_POST['recon_type_id'] = get_post('recon_type');
$sql = get_gl_transactions_list(
	get_post('TransFromDate'), 
	get_post('TransToDate'), -1,
	get_post("bank_account"), 
	0, 
	0, null,
	input_num('amount_min'), 
	input_num('amount_max'), null, null,
	get_post('Memo'),
	get_post('masterfile'),
	get_post('recon_type_id')
);

	$cols =
	array(
		_("Type") => array('fun'=>'systype_name', 'ord'=>''),
		_("#"),
		_("Transaction Date"),
		_("Reconcilation Date"),
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
