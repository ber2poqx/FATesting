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
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/ui/items_cart.inc");

//--------------------------------------------------------------------------------

function display_order_header(&$Order)
{
	global $Ajax, $Refs;

	$qes = has_quick_entries(QE_JOURNAL);
	$new = $Order->order_id==0;

	div_start('pmt_header');
	start_outer_table(TABLESTYLE2, "width='90%'");
	table_section(1);

    date_row(_("Journal Date:"), 'date_', '', $new, 0,0,0,null, true);
    if (input_changed('date_')) {
    	unset($_POST['ref']);
    	$Ajax->activate('ref');
    }

	check_row(_('A/R Installment Adjustment: &nbsp;'), 'ar_alloc', null, true);

	if (check_value('ar_alloc') == 1) {
		
		value_type_list(_("Adjustment Type: "), 'ar_type', 
    		array(
        		1 => 'Adjustment (+)', 
        		2 => 'Adjustment (-)' 
    		), 'label', null, true, '', true
		);

		customer_list_row(_("Customer:"), 'person_id', null, false, true, false, true);

		if (get_post('person_id')) {
			sql_type_list(_("Invoice No:"), 'ar_inv', 
				get_customer_invoices(get_post('person_id')), 'ar_ref', 'ar_ref', 
				'label', null, true, _("Select Invoice")
			);
		}
	
		if (get_post('ar_inv')) {

			$inv_row = db_fetch_assoc(get_customer_invoices(get_post('person_id')));
			$current_bal = current_balance_display(
				$inv_row['trans_no'],
				$inv_row['type'],
				$inv_row['cust_id'],
				date2sql(Today())
			);
			
			label_row("Invoice Type: ", $inv_row['inv_type']);
			label_row("Category: ", $inv_row['stock_name']);
			label_row("Model / Description: ", $inv_row['model'] . " / " . $inv_row['model_desc']);
			label_row("Color Description: ", $inv_row['color_code']);

			label_row("Current Invoice Balance: ", 
				price_format($current_bal)
			);

			hidden('debtor_ref', $inv_row['debtor_ref']);
			hidden('ar_trans_no', $inv_row['trans_no']);
			hidden('ar_trans_type', $inv_row['type']);
			hidden('current_bal', $current_bal);
			hidden('profit_margin', $inv_row['profit_margin']);
			hidden('si_gl', default_SI_gl($inv_row['trans_no'], $inv_row['type']));
		}
	}

	// currencies_list_row(_('Currency:'), 'currency', null, true);
 	// if (list_updated('currency')) {
	// 	unset($_POST['_ex_rate']);
	// }	
	// exchange_rate_display(get_company_pref('curr_default'), get_post('currency'), get_post('date_'), true);

	// if (list_updated('currency')) {
	// 	$Order->clear_items();
	// }

	table_section(2);

    //date_row(_("Document Date: &nbsp;"), 'doc_date', '', $new);
    //date_row(_("Event Date:"), 'event_date', '', $new);
	hidden('doc_date', Today());
	hidden('event_date', Today());
	label_row('Reference: ', $Refs->get_next(ST_JOURNAL, null, null));
	hidden('ref', $Refs->get_next(ST_JOURNAL, null, null));
	//ref_row(_("Reference:"), 'ref', null, get_post('ref'), false, ST_JOURNAL, get_post('date_'));
	//hidden('ref_original');

	if (check_value('ar_alloc') == 1 && get_post('ar_inv')) {
		label_row(_("Source Reference: &nbsp;"), get_post('ar_inv'));
	}
	else {
		text_row(_("Source Reference: &nbsp;"), 'source_ref2', null, 30, 60);
	}

 	if ($new) {
		table_section(3);
	}

	if ($qes !== false) {

		table_section(3);
		quick_entries_list_row(_("Quick Entry").":", 'quick', null, QE_JOURNAL, true);

		start_row();
		$qid = get_quick_entry(get_post('quick'));

		if (list_updated('quick')) {
			unset($_POST['totamount']); // enable default
			$Ajax->activate('totamount');
		}
		end_row();

		if ($qid['bal_type'] == 1) {
			$accname = get_gl_account_name($qid['base_desc']);

			label_row(($qid['base_amount'] == 0 ? _("Yearly") : _("Monthly")) . " ". _("balance from account")." ".
				$qid['base_desc']." ".$accname."&nbsp;&nbsp;".submit('go', _("Go"), false, false, true),'', "colspan=2"
			);
		}
		else {
			text_row(_('Additional info:'), 'aux_info', null, 35, 255);
			amount_row($qid['base_desc'].":", 'totamount', price_format($qid['base_amount']),
		 		null, "&nbsp;&nbsp;".submit('go', _("Go"), false, false, true)
			);
		}

	}

	check_row(_('Include in tax register:'), 'taxable_trans', null, true);
        // Query the user to retain the reconciled status
    if (!$new) {
        $result = get_bank_trans(ST_JOURNAL, $Order->order_id);
        $row = db_fetch($result);

        if ($row && $row['reconciled']) {
            check_row(_('Reconciled:'), 'reconciled', 1, true);
            hidden('reconciled_date', $row['reconciled']);
        }
    }
	end_outer_table(1);
	div_end();
}

//---------------------------------------------------------------------------------

function display_gl_items($title, &$order)
{
	global $path_to_root;

	$coy = user_company(); //Added by spyrax10

	display_heading($title);

	$dim = get_company_pref('use_dimension');

	$id = find_submit('Edit');
	//$sub_type = is_subledger_account(get_post('code_id'));
	//$has_subaccounts = $order->has_sub_accounts();

    div_start('items_table');
	start_table(TABLESTYLE2, "width='100%'");

	if ($dim == 2) {
		$th = array(_("Charge To"),
			_("Account Code"), _("Account Description"), _("Dimension")." 1",
			_("Dimension")." 2", _("Debit"), _("Credit"), //_("Memo"), 
			_('Suggested Entry'), //Added by spyrax10 
			_("")
		);
	}	
	elseif ($dim == 1) {
		$th = array(_("Charge To"),
			_("Account Code"), _("Account Description"), _("Dimension"),
			_("Debit"), _("Credit"), //_("Memo"),
			_('Suggested Entry'), //Added by spyrax10 
			_("")
		);
	}	
	else {
		$th = array(_("Charge To"),
			_("Account Code"), _("Account Description"),
		    _("MCODE"), _("Mastefile"), _("HOC/BC"), _("Debit"), _("Credit"), //_("Memo"), 
			_('Suggested Entry'), //Added by spyrax10 
			_("")
		);
	}

	if (count($order->gl_items)) $th[] = '';
	
	//if ($sub_type || $has_subaccounts)
	//	array_insert($th, 2, _("Counterparty"));

	table_header($th);

	$k = 0;

	if ($id == -1) {
		$id = get_post('Index', -1);
	} 		

	foreach ($order->gl_items as $line => $item) {
		if ($id != $line) {
    		alt_table_row_color($k);

			//Added by spyrax10
			label_cell(get_company_value($item->comp_id, 'name'));
			//

			//$acc = $item->code_id . ($item->person_id ? sprintf(' %05d', $item->person_id) : '');
    		//label_cells($acc, $item->description);
    		label_cell($item->code_id);
    		label_cell($item->description);
		    //if ($sub_type || $has_subaccounts)
			//     label_cell($item->person_name);

    		if ($dim >= 1) {
				label_cell(get_dimension_string($item->dimension_id, true));
			}
   				
    		if ($dim > 1) {
				label_cell(get_dimension_string($item->dimension2_id, true));
			}
   					
   			label_cell($item->mcode);
   			label_cell($item->master_file);

			//Modified by spyrax10
			if ($item->comp_id != $coy) {
				label_cell(get_hocbc_type($item->hocbc)["hocbc"], "align='center'");
			}
			else {
				label_cell('N/A', "align='center'");
			}
			//
    		if ($item->amount > 0) {
    			amount_cell(abs($item->amount));
    			label_cell("");
    		}
    		else {
    			label_cell("");
    			amount_cell(abs($item->amount));
    		}	

			if ($item->comp_id != $coy) {
				if ($item->sug_mcode != '') {
					label_cell($item->sug_mcode . " " . get_gl_account_name($item->sug_mcode), "nowrap align = 'center'");
				}
				else {
					label_cell('', "align='center'");	
				}
			}
			else {
				label_cell('', "align='center'");
			}

			//label_cell($item->reference);

			edit_button_cell("Edit$line", _("Edit"),
				_('Edit journal line')
			);
			delete_button_cell("Delete$line", _("Delete"),
				_('Remove line from journal')
			);

    		end_row();
		} 
		else {
			gl_edit_item_controls($order, $dim, 0, $line);
		}
	}

	if ($id == -1) {
		gl_edit_item_controls($order, $dim, 0, -1);
	}
	    
	if ($order->count_gl_items()) {
		$colspan = 6; //($dim == 2 ? "4" : ($dim == 1 ? "3" : "5")) + ($sub_type || $has_subaccounts ? 1 : 0);
		
		start_row();
		label_cell(_("Total"), "align=right colspan=" . $colspan);
		amount_cell($order->gl_items_total_debit());
		amount_cell(abs($order->gl_items_total_credit()));
		label_cell('', "colspan=3");
		end_row();
	}

    end_table();
	div_end();
}

//---------------------------------------------------------------------------------

function gl_edit_item_controls(&$order, $dim, $sub_accounts, $Index=null)
{
	global $Ajax;
	start_row();
	$coy = user_company();

	$id = find_submit('Edit');
	if ($id == -1) {
		$continue = $id = get_post('Index', -1);
	}
	
	if ($Index != -1 && $Index == $id) {
	    // Modifying an existing row

		$item = $order->gl_items[$Index];

		if (!isset($continue)) {
			$_POST['comp_id'] = $item->comp_id; //Added by spyrax10
			$_POST['code_id'] = $item->code_id;
			$_POST['dimension_id'] = $item->dimension_id;
			$_POST['dimension2_id'] = $item->dimension2_id;
			$_POST['person_id'] = $item->person_id;
			$_POST['mcode'] = gl_comp_name($item->mcode, true, true) != '' ? gl_comp_name($item->mcode, true, true) : $item->mcode;
			$_POST['hocbc_id'] = $item->hocbc;
			$_POST['sug_mcode'] = $item->sug_mcode; //Added by spyrax10

			if ($item->amount > 0) {
				$_POST['AmountDebit'] = price_format($item->amount);
				$_POST['AmountCredit'] = "";
			}
			else {
				$_POST['AmountDebit'] = "";
				$_POST['AmountCredit'] = price_format(abs($item->amount));
			}

			$_POST['description'] = $item->description;
			$_POST['LineMemo'] = $item->reference;
		}
		
		hidden('Index', $id);

		label_cell(get_company_value($item->comp_id, 'name'));
		hidden('comp_id', $item->comp_id); 
		label_cell($item->code_id);
    	label_cell($item->description);
		hidden('code_id', $item->code_id); 
		
		// $skip_bank = !$_SESSION["wa_current_user"]->can_access('SA_BANKJOURNAL');
		// echo gl_all_accounts_list('code_id', null, $skip_bank, true, _('[Select account]'), true, false, false);

		// if ($dim >= 1) {
		// 	dimensions_list_cells(null, 'dimension_id', null, true, " ", false, 1);
		// }
			
		// if ($dim > 1) {
		// 	dimensions_list_cells(null, 'dimension2_id', null, true, " ", false, 2);
		// }
				
	    $Ajax->activate('items_table');
	}
	else {

		if (list_updated('comp_id')) {
			$_POST['comp_id'] = get_post('comp_id');
			$_POST['code_id'] = get_company_value($_POST['comp_id'], 'gl_account');
			//$_POST['sug_mcode'] = get_company_value($_POST['comp_id'], 'gl_account');
			$_POST['mcode'] = get_company_value($_POST['comp_id'], 'gl_account');

			//$Ajax->activate('mcode');
			$Ajax->activate('code_id');
			$Ajax->activate('sug_mcode');
			$Ajax->activate('items_table');
		}
		if (check_value('ar_alloc') == 1 && get_post('ar_inv') ) {
			$_POST['mcode'] = get_post('debtor_ref');
			$Ajax->activate('mcode');
		}

		//Added by spyrax10
		company_list_row(null, 'comp_id', $id != -1 ? false : true);
		//

		$skip_bank = !$_SESSION["wa_current_user"]->can_access('SA_BANKJOURNAL');
		echo gl_all_accounts_list('code_id', null, $skip_bank, true, _("Select Account Code"), true, false, false);
		
		if ($dim >= 1) {
			dimensions_list_cells(null, 'dimension_id', null, true, " ", false, 1);
		}	
		if ($dim > 1) {
			dimensions_list_cells(null, 'dimension2_id', null, true, " ", false, 2);
		}
		//$_POST['LineMemo'] = "";			
	}

	/*if (get_post('_code_id_update'))
	{
	    $Ajax->activate('code_id');
		set_focus($sub_accounts ? 'person_id' : 'AmountDebit');
	}*/
	if ($dim < 1) {
		hidden('dimension_id', 0);
	}
		
	if ($dim < 2) {
		hidden('dimension2_id', 0);
	}

	sl_list_gl_cells(null, 'mcode', null, _("Select MCode"), false);

	if (!isset($_POST['comp_id'])) {
		$comp_id = $coy;
	}
	else {
		$comp_id = $_POST['comp_id'];
	}
	
	if ($comp_id != $coy) {
		sql_type_list(null, 'hocbc_id', 
			hocbc_type_list(), 'id', 'hocbc',
			'', null, true, _("Select HOC/BC")
		);
	}
	else {
		label_cell('N/A', "align='center'");
	}
	
	small_amount_cells(null, 'AmountDebit');
	small_amount_cells(null, 'AmountCredit');

	// if (get_post('debit_stat') == 0 || !get_post('debit_stat')) {
	// 	small_amount_cells(null, 'AmountDebit');
	// 	amount_cell(0);
	// 	set_focus('AmountDebit');
	// 	hidden('AmountCredit', 0);
	// }
	// else if (get_post('debit_stat') == 1) {
	// 	amount_cell(0);
	// 	small_amount_cells(null, 'AmountCredit');
	// 	set_focus('AmountCredit');
	// 	hidden('AmountDebit', 0);
	// }
	// else {
	// 	amount_cell(0);
	// 	small_amount_cells(null, 'AmountCredit');
	// 	set_focus('AmountCredit');
	// 	hidden('AmountDebit', 0);
	// }
	// hidden('debit_stat');

	//text_cells_ex(null, 'LineMemo', 35, 255);

	//Added by spyrax10
	if ($_POST['comp_id'] != $coy) {
		gl_all_accounts_list_cells(null, 'sug_mcode', null, false, false, _("Select Entry"), false);
	}
	else {
		label_cell('N/A', "align='center'");
	}

	if ($id != -1) {
		button_cell('UpdateItem', _("Update"),
			_('Confirm changes'), ICON_UPDATE
		);
		button_cell('CancelItemChanges', _("Cancel"),
				_('Cancel changes'), ICON_CANCEL
		);
 		set_focus('amount');
	} 
	else {
		submit_cells('AddItem', _("Add Item"), "colspan=2",
		    _('Add new line to journal'), true
		);

		// button_cell(
		// 	'toggleDebit',
		// 	_("Toggle"),
		// 	_('Switch Between Debit / Credit Entry'),
		// 	ICON_TOGGLE
		// );
	} 

	end_row();
}

//---------------------------------------------------------------------------------

function gl_options_controls()
{
	div_start('footer');
	echo "<br><table align='center'>";

	textarea_row(_("Memo"), 'memo_', null, 50, 3);

	echo "</table>";
	div_end();
}
