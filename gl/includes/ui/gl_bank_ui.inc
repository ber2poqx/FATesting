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
function display_bank_header(&$order) {
	global $Ajax, $Refs;
	//$payment = $order->trans_type == ST_BANKPAYMENT;
	$payment = ST_BANKPAYMENT;
	
	if ($order->void_id > 0) {
		void_header($order->void_id);
	}

	$customer_error = false;
	div_start('pmt_header');

	start_outer_table(TABLESTYLE2, "width='90%'"); // outer table

	table_section(1);

	if (!isset($_POST['PayType'])) {
		if (isset($_GET['PayType']))
			$_POST['PayType'] = $_GET['PayType'];
		else
			$_POST['PayType'] = "";
	}
	if (!isset($_POST['person_id'])) {
		if (isset($_GET['PayPerson']))
			$_POST['person_id'] = $_GET['PayPerson'];
		else
			$_POST['person_id'] = "";
	}
	if (isset($_POST['_PayType_update'])) {
		$_POST['person_id'] = '';
		$Ajax->activate('pmt_header');
		$Ajax->activate('code_id');
		$Ajax->activate('pagehelp');
		$Ajax->activate('editors');
		$Ajax->activate('footer');
	}

	if ($order->trans_type == ST_BANKDEPOSIT) {
		check_row(_('Opening Balance:'), 'open_bal', null, true);
	}

	if (check_value('open_bal') == 0 || !isset($_POST['open_bal'])) {

		value_type_list(_("Pay To:"), 'PayType', 
    		array(
        		PT_CUSTOMER => 'Customer', 
        		PT_SUPPLIER => 'Suppier',
				PT_BRANCH => 'Branch'
    		), 'label', null, true, '', true
		);

		switch ($_POST['PayType']) {
		
			// case PT_MISC:
			// 	text_row_ex(
			// 		$payment ? _("To the Order of:") : _("Name:"),
			// 		'person_id',
			// 		40,
			// 		50
			// 	);
			// 	break;
	
			case PT_SUPPLIER:
				supplier_list_row(_("Supplier:"), 'person_id', null, false, true, false, true);
				break;
				
			case PT_CUSTOMER:
				customer_list_row(_("Customer:"), 'person_id', null, false, true, false, true);
	
				if (db_customer_has_branches($_POST['person_id'])) {
					customer_branches_list_row(
						_("Branch:"),
						$_POST['person_id'],
						'PersonDetailID',
						null,
						false,
						true,
						true,
						true
					);
				} else {
					$_POST['PersonDetailID'] = ANY_NUMERIC;
					hidden('PersonDetailID');
				}
				$trans = get_customer_habit($_POST['person_id']); // take care of customers on hold
				if ($trans['dissallow_invoices'] != 0) {
					if ($payment) {
						$customer_error = true;
						display_error(_("This customer account is on hold."));
					} else
						display_warning(_("This customer account is on hold."));
				}
				break;
			
			case PT_BRANCH:
				company_list_row(_('Branch: '), 'person_id', true, false, true, true, true);
				break;
	
			/*
			case PT_QUICKENTRY:
				quick_entries_list_row(_("Type") . ":", 'person_id', null, ($payment ? QE_PAYMENT : QE_DEPOSIT), true);
				$qid = get_quick_entry(get_post('person_id'));
				if (list_updated('person_id')) {
					unset($_POST['totamount']); // enable default
					$Ajax->activate('footer');
					$Ajax->activate('totamount');
				}
				amount_row(
					$qid['base_desc'] . ":",
					'totamount',
					price_format($qid['base_amount']),
					null,
					"&nbsp;&nbsp;" . submit('go', _("Go"), false, false, true)
				);
				break;
			*/
		} //end of switch
	}

	//ref_row(_("Transaction #: &nbsp;"), 'ref', '', $order->reference, false, $order->trans_type, get_post('date_'));
	$_POST['ref'] = $Refs->get_next($_SESSION['pay_items']->trans_type, null, null);
	label_row(_("Transaction #: &nbsp;"), get_post('ref'));
	hidden('ref');

	table_section(2, "33%");

	date_row(_("Date:"), 'date_', '', true, 0, 0, 0, null, true);
	//--------------ADDED BY ROBERT-------------//
	//$_POST['receipt_no'] = "";

	if ($order->trans_type == ST_BANKDEPOSIT) {
		text_row(_("Receipts #: &nbsp;"), 'receipt_no', null, 28, 28);
	}
	else {
		label_row(_("Disbursement #: &nbsp;"), auto_disburse_no());
		hidden('receipt_no', auto_disburse_no());
	}
	
	//Modified by spyrax10
	if (get_user_role($_SESSION["wa_current_user"]->user) == 'Cashier/Teller') {
		
		$_POST['cashier_teller'] = $_SESSION["wa_current_user"]->user;
		label_row(_("Cashier/Teller:"), $_POST['cashier_teller'] . " | " . get_current_user_fullname());
		hidden('cashier_teller');
	}
	else {
		sql_type_list(_("Cashier/Teller:"), 'cashier_', 
			allowed_dcpr_users(), 'id', 'real_name', 
			'label', null, true, 'Select Cashier'
		);
	}

	if ($order->trans_type == ST_BANKDEPOSIT) {
		sql_type_list(_("Collection Type: &nbsp;"), 'typecollection', 
			collection_type_list(), 'collect_id', 'collection', 
			'label', null, true, '', false, true
		);
	}
	//
		
	//-----------------------------------------//

	table_section(3, "33%");

	//Commented by spyrax10 21 Feb 2022
	// if (!$order->order_id && !get_post('bank_account')) {
	// 	if ($_POST['PayType'] == PT_CUSTOMER) {
	// 		$_POST['bank_account'] = get_default_customer_bank_account($_POST['person_id']);
	// 	}	
	// 	elseif ($_POST['PayType'] == PT_SUPPLIER) {
	// 		$_POST['bank_account'] = get_default_supplier_bank_account($_POST['person_id']);
	// 	}	
	// 	else {
	// 		unset($_POST['bank_account']);
	// 	}
	// }

	//bank_accounts_list_row(_("Debit To: "), 'bank_account', null, true, true, _("Select Bank Account"));

	sql_type_list(_("Debit To:"), 'bank_account', get_bank_lists(), 'id', 'bank_account_name', 
		'label', null, true, _("Select Bank Account")
	);

	if ($order->trans_type == ST_BANKDEPOSIT)
	{
		label_row("Total Amount: &nbsp;", price_format(abs($order->gl_items_total_credit())));
		label_row("Pay Amount: ", price_format(abs($order->gl_items_total_credit()) - $_SESSION['pay_items']->gl_items_total_debit()));
	}
	if ($order->trans_type == ST_BANKPAYMENT)
	{
		label_row("Cash Amount: ", price_format($_SESSION['pay_items']->gl_items_total_debit() - abs($order->gl_items_total_credit())));
		label_row("Total Amount: ", price_format($_SESSION['pay_items']->gl_items_total_debit()));
	}

	if (get_post('bank_account') == BT_CHECK) {
		date_row(_("Check Date:"), 'check_date', '');
		//date_row(_("Check Date:"), 'check_date', '', true, 0, 0, 0, null, true);
		text_row(_("Check #:"), 'check_no', null, 35, 35);
		text_row(_("Bank Branch:"), 'bank_branch', null, 35, 35);
	}
	
	if ($payment) {
		bank_balance_row($_POST['bank_account']); 
	}
	
	// $bank_currency = get_bank_account_currency($_POST['bank_account']);

	// exchange_rate_display(get_company_currency(), $bank_currency, $_POST['date_']);

	end_outer_table(1); // outer table

	div_end();
	if ($customer_error) {
		end_form();
		end_page();
		exit;
	}
}
//---------------------------------------------------------------------------------

function display_gl_items($title, &$order)
{
	global $path_to_root;

	$coy = user_company();

	$dim = get_company_pref('use_dimension');
	$colspan = ($dim == 2 ? 4 : ($dim == 1 ? 3 : 2));
	display_heading($title);

	div_start('items_table');
	start_table(TABLESTYLE2, "width='100%'");

	/* Modified by Ronelle */
	if ($dim == 2)
		$th = array(
			_("Charge To"), //Added by spyrax10
			_("Account Code"), _("Account Description"), _("Dimension") . " 1",
			_("Dimension") . " 2", _("Debit"), _("Credit"), 
			_("Suggested Entry"), //Added by spyrax10
			""
		);
	elseif ($dim == 1)
		$th = array(
			_("Charge To"), //Added by spyrax10
			_("Account Code"), _("Account Description"), _("Dimension"),
			_("Debit"), _("Credit"), 
			_('Suggested Entry'), //Added by spyrax10
			""
		);
	else {

		$th = array(
			_("Charge To"), //Added by spyrax10
			_("Account Code"), _("Account Description"),
			_("MCODE"), _("Mastefile"),
			_("Debit"), _("Credit"), 
			_('Suggested Entry'), //Added by spyrax10
			""
		);

		array_insert($th, 5, array(_("HOC/BC")));

	}
	if (count($order->gl_items)) $th[] = '';

	table_header($th);
	$k = 0;  //row colour counter

	$id = find_submit('Edit');
	foreach ($order->gl_items as $line => $item) {
		if ($id != $line) {
			alt_table_row_color($k);

			//Added by spyrax10
			label_cell(get_company_value($item->comp_id, 'name'));
			//

			label_cell($item->code_id);
			label_cell($item->description);
			if ($dim >= 1)
				label_cell(get_dimension_string($item->dimension_id, true));
			if ($dim > 1)
				label_cell(get_dimension_string($item->dimension2_id, true));
			
			label_cell($item->mcode);
			label_cell($item->master_file);

			//Modified by spyrax10
			if ($item->comp_id != $coy) {
				label_cell(get_hocbc_type($item->hocbc)["hocbc"]);
			}
			else {
				label_cell('N/A', "align='center'");
			}
			//
		
			if ($order->trans_type == ST_BANKDEPOSIT) {
				/* Modified by Ronelle */
				if ($item->amount > 0) {
					amount_cell(abs(-$item->amount));
					amount_cell(abs(0));
				} else {
					amount_cell(abs(0));
					amount_cell(abs(-$item->amount));
				}
				// amount_cell(-$item->amount);
				/* */
			} else {
				if ($item->amount > 0) {
					amount_cell(abs(-$item->amount));
					amount_cell(abs(0));
				} else {
					amount_cell(abs(0));
					amount_cell(abs(-$item->amount));
				}
			}
			if ($item->comp_id != $coy) {
				label_cell($item->sug_mcode . " " . get_gl_account_name($item->sug_mcode), "align = 'center'");
			}
			else {
				label_cell('', "align='center'");
			}
			
			// label_cell($item->reference);

			edit_button_cell(
				"Edit$line",
				_("Edit"),
				_('Edit document line')
			);
			delete_button_cell(
				"Delete$line",
				_("Delete"),
				_('Remove line from document')
			);
			end_row();
		} else {
			gl_edit_item_controls($order, $dim, $line);
		}
	}

	if ($id == -1)
		gl_edit_item_controls($order, $dim);

	/*  Modified by Ronelle */
	if ($order->count_gl_items()) {
		$colspan = ($dim == 2 ? "6" : ($dim == 1 ? "5" : "6")) + (/* $sub_type || $has_subaccounts */ 0 ? 1 : 0);
		start_row();
		label_cell(_("Total"), "align=right colspan=" . $colspan);
		amount_cell($order->gl_items_total_debit());
		amount_cell(abs($order->gl_items_total_credit()));
		label_cell('', "colspan=3");
		end_row();
	}
	/* */
	// if ($order->count_gl_items())
	// 	label_row(_("Total"), number_format2(abs($order->gl_items_total()), user_price_dec()), "colspan=" . $colspan . " align=right", "align=right", 3);

	end_table();
	div_end();
}

//---------------------------------------------------------------------------------

function gl_edit_item_controls(&$order, $dim, $Index = null)
{
	global $Ajax;
	$coy = user_company();
	$payment = $order->trans_type == ST_BANKPAYMENT;

	start_row();
	$id = find_submit('Edit');
	if ($Index != -1 && $Index == $id) {
		$item = $order->gl_items[$Index];

		$_POST['comp_id'] = $item->comp_id; //Added by spyrax10
		$_POST['code_id'] = $item->code_id;
		$_POST['dimension_id'] = $item->dimension_id;
		$_POST['dimension2_id'] = $item->dimension2_id;
		$_POST['mcode'] = $item->mcode; //Added by spyrax10

		/*  Modified by Ronelle */
		if ($item->amount > 0) {
			$_POST['AmountDebit'] = price_format($item->amount);
			$_POST['AmountCredit'] = "";
		} else {
			$_POST['AmountDebit'] = "";
			$_POST['AmountCredit'] = price_format(abs($item->amount));
		}

		//Added by spyrax10
		$_POST['sug_mcode'] = $item->sug_mcode;
		$_POST['hocbc'] = $item->hocbc;
		//
		// $_POST['amount'] = price_format(abs($item->amount));
		/* */
		$_POST['description'] = $item->description;
		// $_POST['LineMemo'] = $item->reference;

		hidden('Index', $id);
		$Ajax->activate('items_table');
	} else {
	
		if (list_updated('comp_id')) {
			$_POST['comp_id'] = get_post('comp_id');
			$_POST['code_id'] = get_company_value($_POST['comp_id'], 'gl_account');
			//$_POST['sug_mcode'] = get_company_value($_POST['comp_id'], 'gl_account');
			$_POST['mcode'] = get_company_value($_POST['comp_id'], 'gl_account');

			$Ajax->activate('mcode');
			$Ajax->activate('code_id');
			//$Ajax->activate('sug_mcode');
			$Ajax->activate('items_table');
		}

		if (isset($_POST['_code_id_update'])) {
			$Ajax->activate('code_id');
		}
		// if (list_updated('PayType') || list_updated('person_id')) {
		// 	if ($_POST['PayType'] == PT_CUSTOMER) {
		// 		$acc = get_branch_accounts($_POST['PersonDetailID']);
		// 		$_POST['code_id'] = $acc['receivables_account'];
		// 	} elseif ($_POST['PayType'] == PT_SUPPLIER) {
		// 		$acc = get_supplier_accounts($_POST['person_id']);
		// 		$_POST['code_id'] = $acc['payable_account'];
		// 		$_POST['dimension_id'] = $acc['dimension_id'];
		// 		$_POST['dimension2_id'] = $acc['dimension2_id'];
		// 	} else {
		// 		$_POST['code_id'] =
		// 			get_company_pref($payment ? 'default_cogs_act' : 'default_inv_sales_act');
		// 	}
		// 	$Ajax->activate('dimension_id');
		// 	$Ajax->activate('dimension2_id');
		// }
	}
	//Added by spyrax10
	company_list_row(null, 'comp_id', $id != -1 ? false : true);
	//
	echo gl_all_accounts_list('code_id', null, true, true, true, false, false, false, _("Select Account Code"));
	
	if ($dim >= 1)
		dimensions_list_cells(null, 'dimension_id', null, true, " ", false, 1);
	else
		hidden('dimension_id', 0);
	if ($dim > 1)
		dimensions_list_cells(null, 'dimension2_id', null, true, " ", false, 2);
	else
		hidden('dimension2_id', 0);

	sl_list_gl_cells(null, 'mcode', null, _("Select MCode"), false);
	// $slname = get_slname_by_ref($_POST['mcode']);
	// $_POST['master_file'] = $slname;
	// text_cells(null, 'master_file');

	if (!isset($_POST['comp_id'])) {
		$comp_id = $coy;
	}
	else {
		$comp_id = $_POST['comp_id'];
	}
	
	if ($comp_id != $coy) {
		sql_type_list(null, 'hocbc_id', 
			hocbc_type_list(), 'id', 'hocbc'
		);
	}
	else {
		label_cell('N/A', "align='center'");
	}

	/* Modified by Ronelle */
	if ((get_post('debit_stat') == 0 || !get_post('debit_stat')) 
		&& $order->trans_type == ST_BANKPAYMENT) {
		small_amount_cells(null, 'AmountDebit');
		amount_cell(0);
		hidden('AmountCredit', 0);
	} 
	else if ((get_post('debit_stat') == 0 || !get_post('debit_stat'))
		&& $order->trans_type == ST_BANKDEPOSIT) {
		amount_cell(0);
		small_amount_cells(null, 'AmountCredit');
		hidden('AmountDebit', 0);
	} 
	else if (get_post('debit_stat') == 1 && $order->trans_type == ST_BANKPAYMENT) {
		amount_cell(0);
		small_amount_cells(null, 'AmountCredit');
		hidden('AmountDebit', 0);
	}
	else if (get_post('debit_stat') == 1 && $order->trans_type == ST_BANKDEPOSIT) {
		small_amount_cells(null, 'AmountDebit');
		amount_cell(0);
		hidden('AmountCredit', 0);
	}
	hidden('debit_stat');
	//

	// amount_cells(null, 'amount');
	/* */
	// text_cells_ex(null, 'LineMemo', 35, 255);

	gl_all_accounts_list_cells(null, 'sug_mcode', $_POST['sug_mcode'], false, false, _("Select Entry"), false);
	hidden('class_name');

	if ($id != -1) {
		button_cell(
			'UpdateItem',
			_("Update"),
			_('Confirm changes'),
			ICON_UPDATE
		);
		button_cell(
			'CancelItemChanges',
			_("Cancel"),
			_('Cancel changes'),
			ICON_CANCEL
		);
		set_focus('amount');
	} else {
		submit_cells(
			'AddItem',
			_("Add Item"),
			"colspan=2",
			_('Add new item to document'),
			true
		);

		button_cell(
			'toggleDebit',
			_("Toggle"),
			_('Switch Between Debit / Credit Entry'),
			ICON_TOGGLE
		);
	}

	end_row();
}


//---------------------------------------------------------------------------------

function gl_options_controls($order = null) {
	div_start('footer');
	echo "<br><table align='center'>";

	/*
	$type = get_post('PayType');
	$bank_curr = get_bank_account_currency(get_post('bank_account'));
	$person_curr = $type == PT_CUSTOMER ? get_customer_currency(get_post('person_id'))
		: ($type == PT_SUPPLIER ? get_supplier_currency(get_post('person_id')) : $bank_curr);

	if ($person_curr != $bank_curr) {
		$_POST['settled_amount'] =
			price_format(abs($order->gl_items_total() / get_exchange_rate_from_to($bank_curr, $person_curr, get_post('date_'))));
		amount_row(
			$type == PT_CUSTOMER ? _("Settled AR Amount:") :  _("Settled AP Amount:"),
			'settled_amount',
			null,
			null,
			$person_curr,
			user_price_dec()
		);
	}*/
	textarea_row(_("Memo: "), 'memo_', null, 50, 3);

	echo "</table>";
	div_end();
}


//---------------------------------------------------------------------------------
