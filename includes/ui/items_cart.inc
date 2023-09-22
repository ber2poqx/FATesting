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
include_once($path_to_root . "/includes/prefs/sysprefs.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");
include_once($path_to_root . "/includes/cost_and_pricing.inc");

class items_cart
{
	var $trans_type;
	var $line_items;
	var $gl_items;

	var	$order_id;

	var $from_loc;
	var $to_loc;
	var $tran_date;
	var $doc_date;
	var $event_date;
	var $transfer_type;
	var $increase;
	var $memo_;
	var $branch_id;
	var $reference;
	var $original_amount;
	var $currency;
	var $rate;
	var $source_ref;
	//Added by spyrax10 10 Feb 2022
	var $source_ref2;
	var $trans_db;
	var $ar_type;
	var $ar_alloc = 0;
	var $ar_date;
	var $ar_trans_no = 0;
	var $ar_trans_type = null;
	var $ar_debtor_no;
	var $gross_profit = 0;
	var $profit_margin;
	var $void_id;
	//
	var $vat_category;
    var $category_id; //Added by Herald 06/07/2021
	var $tax_info;	// tax info for the GL transaction
    //var $item_type; //Added by Herald 08/27/2021 for type=new or repo
	var $fixed_asset;
	var $status = "";
	/*Added by Albert 09/22/2023*/
	var $approved_id;
	var $reviewed_id;

	function __construct($type, $trans_no=0)
	{
		$this->trans_type = $type;
		$this->order_id = $trans_no;
		$this->clear_items();
		if (in_array($type, array(ST_MERCHANDISETRANSFER, ST_RRBRANCH, ST_LOCTRANSFER, ST_INVADJUST, ST_COSTUPDATE, ST_MANUISSUE, ST_MANURECEIVE, ST_JOURNAL)))
			$this->currency = get_company_pref('curr_default');
		$this->rate = 1;
	}

	// --------------- line item functions

	function add_to_cart($line_no, $stock_id, $qty, $standard_cost, $description=null, $manufacture_date='0000-00-00', $expire_date='0000-00-00', 
		$lot_no='',$chasis_no='', $color='', $color_code='', $mt_lines = 0, $type_out = 0, $transno_out = 0, $remarks = '', 
		$item_type = 'new', $stock_ref = '', $mcode = '', $master_file = '', //Added by spyrax10
		$line_item='', $ob_date = null, $currqty=0)
	{

		if (isset($stock_id) && $stock_id != "" && isset($qty))
		{
		    $this->line_items[$line_no] = new line_item($stock_id, $qty, $standard_cost, $description, $manufacture_date, $expire_date, 
				$lot_no, $chasis_no, $color, $color_code, $mt_lines, 
				$type_out, $transno_out, $remarks, $item_type, $stock_ref, $mcode, $master_file,
				$line_item, $ob_date, $currqty
			);
			return true;
		}
		else
		{
			// shouldn't come here under normal circumstances
			display_error("unexpected - adding an invalid item or null quantity", "", true);
		}

		return false;
	}

	//Added by spyrax10

	function get_items_total() {
		$total = 0;

		foreach($this->line_items as $line_no => $line) {
			$total += round($line->quantity * $line->standard_cost, user_price_dec());
		}
		return $total;
	}

	function find_SI_gl($si_gl) {
		foreach ($this->gl_items as $line_no => $line) {
			if ($line->code_id == $si_gl) {
				return true;
			}
		}
		return false;
	}

	function find_cart_serial($lot_no) {

		foreach($this->line_items as $line_no => $line) {
			if ($line->lot_no == $lot_no) {
				return $this->line_items[$line_no];
			}			
		}
		return null;
	}

	function find_cart_stock($stock_id) {

		foreach($this->line_items as $line_no => $line) {
			if ($line->stock_id == $stock_id) {
				return $this->line_items[$line_no];
			}			
		}
		return null;
	}

	function find_cart_chassis($chasis_no) {

		foreach($this->line_items as $line_no => $line) {
			if ($line->chasis_no == $chasis_no) {
				return $this->line_items[$line_no];
			}			
		}
		return null;
	}
	//

	function find_cart_item($stock_id, $lot_no='')
	{
		foreach($this->line_items as $line_no=>$line) {
			if ($line->stock_id == $stock_id && $line->lot_no == $lot_no)
				return $this->line_items[$line_no];
		}
		return null;
	}
	//Added by Herald 12/19/2020
	function find_cart_item_new($item_code, $lot_no='')
	{
	    foreach($this->line_items as $line_no=>$line) {
	        if ($line->item_code == $item_code && $line->lot_no == $lot_no)
	            return $this->line_items[$line_no];
	    }
	    return null;
	}

	//Added by Robert 08/31/2023
	function find_cart_item_new_for_mtandcml($item_code, $lot_no='', $type_out = 0, $transno_out = 0)
	{
	    foreach($this->line_items as $line_no=>$line) {
	        if ($line->item_code == $item_code && $line->lot_no == $lot_no && $line->type_out == $type_out && $line->transno_out == $transno_out)
	            return $this->line_items[$line_no];
	    }
	    return null;
	}
	//Added by Herald 03/08/2021
	function find_cart_item_serialise($item_code)
	{
	    foreach($this->line_items as $line_no=>$line) {
	        if ($line->serialise_id == $item_code)
	            return $this->line_items[$line_no];
	    }
	    return null;
	}
	//added by Herald 06/07/2021
	function find_cart_itemcode($stock_id, $type_out, $transno_out)
	{
	    foreach($this->line_items as $line_no=>$line) {
	        if ($line->stock_id == $stock_id && $line->type_out == $type_out && $line->transno_out == $transno_out)
	            return $this->line_items[$line_no];
	    }
	    return null;
	}
	function update_cart_item($line_no, $qty=0, $standard_cost, $manufacture_date='0000-00-00',$expire_date='0000-00-00', $lot_no='',$chasis_no='',
		$color = '', $remarks=null)
	{
		$this->line_items[$line_no]->quantity = $qty;
		$this->line_items[$line_no]->standard_cost = $standard_cost;
		$this->line_items[$line_no]->manufacture_date = $manufacture_date;
		$this->line_items[$line_no]->expire_date = $expire_date;
		$this->line_items[$line_no]->lot_no = $lot_no;
		//Added by spyrax10
		$this->line_items[$line_no]->chasis_no = $chasis_no; 
		$this->line_items[$line_no]->color = $color; 
		//
		$this->line_items[$line_no]->remarks = $remarks;
	}

	//Added by Robert 08/27/2022
	function rr_manual_items_total_cost()
	{
		$total = 0;
		foreach ($this->line_items as $line_items)
		{
			if ($line_items->standard_cost > 0)
				$total += $line_items->standard_cost;
		}
		return $total;
	}

	function remove_from_cart($line_no)
	{
		array_splice($this->line_items, $line_no, 1);
	}
	//Added by Herald 09/01/2021
	function remove_from_cart_line($line_item)
	{
	    foreach($this->line_items as $line_no=>$line){
	        if($line->line_item_row == $line_item){
	            unset($this->line_items[$line_no]);
	        }
	    }
	}
	function count_items()
	{
		return count($this->line_items);
	}

	function check_qoh($location, $date_, $reverse=false)
	{
		global $SysPrefs;

		$low_stock = array();

    	if (!$SysPrefs->allow_negative_stock())
    	{
			foreach ($this->line_items as $line_no => $line_item)
				if (has_stock_holding($line_item->mb_flag) || is_fixed_asset($line_item->mb_flag))
				{
					$quantity = $line_item->quantity;
					if ($reverse)
						$quantity = -$line_item->quantity;

					if ($quantity >= 0)
						continue;

					if (check_negative_stock($line_item->stock_id, $quantity, $location, $date_))
						$low_stock[] = $line_item->stock_id;
    			}
		}
		return $low_stock;
	}
    //Added by Herald 09/02/2021
	function check_qty_avail_by_rrdate($trans_date){
	    $totalcheck=0;
	    foreach($this->line_items as $line_no=>$line) {
	        if ($line->manufacture_date > $trans_date)
	            $totalcheck++;
	    }
	    return $totalcheck;
	}
	// ----------- GL item functions
	/* Modified by Ronelle 2/22/2021 
	 * Medified by Herald 09/01/2021
	 * */
	
	//Modified by spyrax10 10-11-2021
	function add_gl_item($code_id, $dimension_id, $dimension2_id, $amount, $memo='', $act_descr=null, $person_id=null, $date=null, $mcode=null, $master_file=null, $hocbc = 0, 
		$comp_id = null, $sug_mcode = null, 
		$master_file_type=99, $line_item='')
	{
		if (isset($code_id) && $code_id != "" && isset($amount) && isset($dimension_id)  &&
			isset($dimension2_id))
		{
		    $this->gl_items[] = new gl_item($code_id, $dimension_id, $dimension2_id, $amount, $memo, $act_descr, $person_id, $date, $mcode, $master_file, $hocbc, 
				$comp_id, $sug_mcode, 
				$master_file_type, $line_item
			);
			return true;
		}
		else
		{
			// shouldn't come here under normal circumstances
			display_error("unexpected - invalid parameters in add_gl_item($code_id, $dimension_id, $dimension2_id, $amount,...)", "", true);
		}

		return false;
	}

	//Modified by spyrax10 10-11-2021
	function update_gl_item($index, $code_id, $dimension_id, $dimension2_id, $amount, $memo='', $act_descr=null, $person_id=null, $mcode=null, $master_file=null, $hocbc = 0, 
		$comp_id = null, $sug_mcode = null, 
		$master_file_type = 99) 
	{
		//Added by spyrax10 10-11-2021
		$this->gl_items[$index]->comp_id = $comp_id;
		$this->gl_items[$index]->sug_mcode = $sug_mcode;
		//
	    $this->gl_items[$index]->code_id = $code_id;
	    $this->gl_items[$index]->person_id = $person_id;
	    $this->gl_items[$index]->master_file_type = $master_file_type;
	    
		$gl_type = is_subledger_account($code_id, $person_id);
		if ($gl_type)
		{
			$this->gl_items[$index]->person_type_id = $gl_type > 0 ? PT_CUSTOMER : PT_SUPPLIER;
			$data = get_subaccount_data($code_id, $person_id);
			$this->gl_items[$index]->person_name = $data['name'];
			$this->gl_items[$index]->branch_id = $data['id'];
		} else
		{
			$this->gl_items[$index]->person_type_id = $this->gl_items[$index]->person_name = '';
		}
		$this->gl_items[$index]->dimension_id = $dimension_id;
		$this->gl_items[$index]->dimension2_id = $dimension2_id;
		$this->gl_items[$index]->amount = $amount;
		$this->gl_items[$index]->reference = $memo;
		if ($act_descr == null)
			$this->gl_items[$index]->description = get_gl_account_name($code_id);
		else
			$this->gl_items[$index]->description = $act_descr;
		/* Added by Ronelle 2/22/2021 */
		$this->gl_items[$index]->mcode = $mcode;
		$this->gl_items[$index]->master_file = $master_file;
		$this->gl_items[$index]->hocbc = $hocbc;
		/* */

	}

	//--Robert Added--//
	function update_gl_amount($index, $amount) 
	{
		$this->gl_items[$index]->amount = $amount;
	}

	function update_gl_masterfile($index, $mcode, $master_file) 
	{
		$this->gl_items[$index]->mcode = $mcode;
		$this->gl_items[$index]->master_file = $master_file;
	}

	function remove_gl_item($index)
	{
		array_splice($this->gl_items, $index, 1);
	}
	//Added by Herald 09/01/2021
	function remove_gl_line_item($line_item)
	{
	    foreach($this->gl_items as $line_no=>$line){
	        if($line->line_item == $line_item){
	            unset($this->gl_items[$line_no]);
	        }
	    }
	}
	function count_gl_items()
	{
		return count($this->gl_items);
	}

	function gl_items_total()
	{
		$total = 0;
		foreach ($this->gl_items as $gl_item)
			$total += $gl_item->amount;
		return $total;
	}

	function gl_items_total_debit()
	{
		$total = 0;
		foreach ($this->gl_items as $gl_item)
		{
			if ($gl_item->amount > 0)
				$total += $gl_item->amount;
		}
		return $total;
	}

	function gl_items_total_credit()
	{
		$total = 0;
		foreach ($this->gl_items as $gl_item)
		{
			if ($gl_item->amount < 0)
				$total += $gl_item->amount;
		}
		return $total;
	}

	//Added by spyrax10 13 Jun 2022
	function gl_si_total() {
		$total = 0;
		foreach ($this->gl_items as $gl_item) {
			if ($gl_item->amount > 0) {
				if ($gl_item->code_id != getCompDet('dgp_account')) {
					$total += $gl_item->amount;
				}
			}
		}
		return $total;
	}

	// ------------ common functions

	function clear_items()
	{
    	unset($this->line_items);
		$this->line_items = array();

    	unset($this->gl_items);
		$this->gl_items = array();

	}
	//
	//	Check if cart contains virtual subaccount (AP/AR) postings
	//
	function has_sub_accounts()
	{
		foreach ($this->gl_items as $gl_item)
		{
			if (is_subledger_account($gl_item->code_id))
				return true;
		}
		return false;
	}

	//
	//	Check if cart contains postings to tax accounts
	//
	function has_taxes()
	{
		foreach ($this->gl_items as $gl_item)
		{
			if (is_tax_account($gl_item->code_id))
				return true;
		}
		return false;
	}

	/*
		Collect tax info from the GL transaction lines and return as array of values:
			'tax_date' 		- tax date
			'tax_group'		- related counterparty tax group
			'tax_category'	- tax category (not set for now)
			'net_amount' - tax amounts array indexed by tax type id
			'tax_in', 'tax_out' - tax amounts array indexed by tax type id
			'tax_reg' - tax register used
	*/
	function collect_tax_info()
	{
		$tax_info = array();
		$subledger_sum = $net_sum = 0;

		$tax_info['tax_date'] = $this->tran_date;
		$vat_percent = get_company_pref('partial_vat_percent');
		$factor = $vat_percent && ($this->vat_category == VC_PARTIAL) ? $vat_percent/100: 1;

		foreach($this->gl_items as $gl)
		{
			if ($person_type = is_subledger_account($gl->code_id, $gl->person_id))
			{
				$tax_info['person_type'] = $person_type < 0 ? PT_SUPPLIER : PT_CUSTOMER;
				$tax_info['person_id'] = $gl->person_id;

				if ($tax_info['person_type'] == PT_CUSTOMER)
				{
					$branch = get_default_branch($gl->person_id);
					$tax_info['tax_group'] = $branch['tax_group_id'];
				} else {
					$supplier = get_supplier($gl->person_id);
					$tax_info['tax_group'] = $supplier['tax_group_id'];
				}
				$subledger_sum += $gl->amount;
			} elseif ($tax_id = is_tax_account($gl->code_id))
			{
				$tax_type = get_tax_type($tax_id);
				if ($gl->code_id == $tax_type['purchasing_gl_code']) {
					if (!isset($tax_info['tax_in'][$tax_id]))
						$tax_info['tax_in'][$tax_id] = 0;
					$tax_info['tax_in'][$tax_id] += $gl->amount;
					$tax_info['tax_reg'] = TR_INPUT;
				} else {
					if (!isset($tax_info['tax_out'][$tax_id]))
						$tax_info['tax_out'][$tax_id] = 0;
					$tax_info['tax_out'][$tax_id] -= $gl->amount;
					if (!isset($tax_info['tax_reg'])) // TR_INPUT has priority (EU are posted on both accounts)
						$tax_info['tax_reg'] = TR_OUTPUT;
				}
				if ($tax_type['rate'])
				{
					// assume transaction adjustment for negative tax in/out
					$sign = (@$tax_info['tax_in'][$tax_id] < 0 || @$tax_info['tax_out'][$tax_id] < 0) ? -1 : 1;
					// we can have both input and output tax postings in some cases like intra-EU trade.
					// so just calculate net_amount from the higher in/out tax
					$tax_info['net_amount'][$tax_id]
						= $sign*round2(max(abs(@$tax_info['tax_in'][$tax_id]), abs(@$tax_info['tax_out'][$tax_id]))/$tax_type['rate']*100, 2)/$factor;

				}
			} else
				$net_sum += $gl->amount;
		}
		// if no tax amount posted guess register type from person_type used (e.g. export invoice)
		if (!isset($tax_info['tax_reg']) && isset($tax_info['person_type']))
			$tax_info['tax_reg'] = $tax_info['person_type']==PT_CUSTOMER ? TR_OUTPUT : TR_INPUT;

		if (count_array(@$tax_info['net_amount']))	// guess exempt sales/purchase if any tax has been found
		{
			$ex_net = abs($net_sum) - @array_sum($tax_info['net_amount']);
			if ($ex_net != 0)
				$tax_info['net_amount_ex'] = $ex_net;
		}

		return $tax_info;
	}

	function set_currency($curr, $rate=0)
	{
		$this->currency = $curr;
		$this->rate = $rate;
	}

	/*
		Reduce number of necessary gl posting lines.
	*/
	function reduce_gl()
	{
		/* reduce additional postings */
		$codes = array();
		foreach($this->gl_items as $n => $gl)
		{
			$prev = @$codes[$gl->code_id][$gl->person_id][$gl->dimension_id][$gl->dimension2_id][$gl->reference];
			if (isset($prev)) { // add amount to previous line for the same gl_code dims and memo
				$this->gl_items[$prev]->amount += $gl->amount;
				if ($this->gl_items[$prev]->amount == 0) // discard if overall amount==0
				{
					unset($this->gl_items[$prev], $codes[$gl->code_id][$gl->person_id][$gl->dimension_id][$gl->dimension2_id][$gl->reference]);
				}
				unset($this->gl_items[$n]);
			} else
				$codes[$gl->code_id][$gl->person_id][$gl->dimension_id][$gl->dimension2_id][$gl->reference] = $n;
		}
	}
	/*
		Write transaction GL postings, creating tax records and updating AP/AR/bank ledger if needed.
	*/
	function write_gl($check_balance = true) {

		$coy = user_company();

		$delta = $this->gl_items_total();
		if ($check_balance && floatcmp($delta, 0) != 0) {
			$this->add_gl_item(get_company_pref($delta > 0 ? 'rounding_db_act' : 'rounding_cr_act'),
				0, 0, -$delta, ''
			);
			error_log(sprintf( _("Rounding error %s encountered for trans_type:%s,trans_no:%s"), $delta, $this->trans_type, $this->order_id));
		}

		$bank_trans = $supp_trans = $cust_trans = array();
		$total_gl = 0;

		$link_to_entry = false;
		foreach($this->gl_items as $gl) {
			if (!isset($gl->date)) {
				$gl->date = $this->tran_date;
			}

			$row = get_gl_account($gl->code_id);
			$link_to_entry = ($row['control'] == 1 && $gl->amount > 0) || $this->ar_alloc == 1  ? true : false;

			$total_gl += 
				add_gl_trans(
					$this->trans_type, 
					$this->order_id, 
					$gl->date, 
					$gl->code_id, 
					$gl->dimension_id, 
					$gl->dimension2_id, 
					$this->memo_, 
					$gl->amount, 
					$this->currency, 
					$gl->master_file_type, 
					$gl->person_id, "", 
					$this->rate, 
					get_person_id(PT_CUSTOMER, $gl->mcode), 
					$gl->master_file, 
					$gl->hocbc,
					$link_to_entry ? $this->ar_trans_no : 0, 
					$gl->comp_id != $coy ? 1 : 0,
					false,
					0,
					$link_to_entry ? $this->ar_trans_type : null
				);
				
			if ($this->ar_alloc == 1) {

				if (!gl_account_exists($this->trans_type, $this->order_id, getCompDet('dgp_account')) || 
					!gl_account_exists($this->trans_type, $this->order_id, getCompDet('rgp_account'))) {
					
					$account_id = $gl->amount > 0 ? getCompDet('dgp_account') : getCompDet('rgp_account');

					add_gl_trans(
						$this->trans_type, 
						$this->order_id, 
						$gl->date, 
						$account_id, 
						$gl->dimension_id, 
						$gl->dimension2_id, 
						$this->memo_, 
						$gl->amount > 0 ? $this->gross_profit : -$this->gross_profit, 
						$this->currency, 
						$gl->master_file_type, 
						$gl->person_id, "", 
						$this->rate, 
						$this->ar_debtor_no, 
						get_person_name(PT_CUSTOMER, $this->ar_debtor_no), 
						$gl->hocbc,
						$this->ar_trans_no, 
						$gl->comp_id != $coy ? 1 : 0,
						false
					);
				}

			}

			// post to first found bank account using given gl acount code.
			$is_bank_to = is_bank_account($gl->code_id);
	    	if ($is_bank_to && (get_bank_account_currency($is_bank_to) == $this->currency)) { // do not register exchange variations in bank trans
    			if (!isset($bank_trans[$is_bank_to])) {
					$bank_trans[$is_bank_to] = 0;
				}
	    		$bank_trans[$is_bank_to] += $gl->amount;

    		} 
			elseif ($gl->person_id) {
    			$home_currency = get_company_currency();
				// collect per counterparty amounts (in case more than one posting was done to the account),
				// do not post exchange variations to AR/AP (journal in not customer/supplier currency)
	    		if ($gl->person_type_id == PT_SUPPLIER 
					&& (get_supplier_currency($gl->person_id) == $this->currency || $this->currency != $home_currency)) {
					
					$supp_trans[$gl->person_id] = @$supp_trans[$gl->person_id] + $gl->amount;
				}
    			elseif ($gl->person_type_id == PT_CUSTOMER 
					&& (get_customer_currency(null, $gl->branch_id) == $this->currency || $this->currency != $home_currency)) {

					$cust_trans[$gl->branch_id] = @$cust_trans[$gl->branch_id] + $gl->amount;
				}
    		}

			if ($gl->comp_id != $coy) {

				$branch_code = get_company_value($gl->comp_id, 'branch_code');

				$hoc_code = get_company_value(user_company(), 'branch_code');
				$hoc_masterfile = get_company_value(user_company(), 'name');
				$ib_trans_no = max_interbranch_no($this->trans_type, $gl->comp_id) + 1;

				bank_interbranch_entry(
					$branch_code, 	
					$gl->sug_mcode, 
					$hoc_code, 
					$hoc_masterfile, 
					date2sql($gl->date), 	
					$this->reference, 	
					$gl->amount, 
					$this->memo_, 
					get_current_user_fullname(), //Prepared by 	
					get_company_value($coy, 'branch_code'), 
					$ib_trans_no,	
					$this->trans_type, 
					$branch_code,
					2,
					max_gl_counter() 
				);

				//Branch Current Entry
				bank_interbranch_entry(
					$branch_code, 	
					get_company_value(user_company(), 'gl_account'), 
					$hoc_code, 
					$hoc_masterfile, 
					date2sql($gl->date), 	
					$this->reference, 	
					-$gl->amount, 
					$this->memo_, 
					get_current_user_fullname(), //Prepared by 	
					get_company_value($coy, 'branch_code'), 
					$ib_trans_no, 	
					$this->trans_type, 
					$branch_code,
					2,
					max_gl_counter() 
				);
			}

		} // End of foreach loop

		// post currency roundings if any
		if ($check_balance && floatcmp($total_gl, 0)) {
			add_gl_trans(
				$this->trans_type, 
				$this->order_id, 
				$this->tran_date, 
				get_company_pref($total_gl>0 ? 'rounding_db_act' : 'rounding_cr_act'), 0, 0, 
				_('Exchange rate roundings'), 
				-$total_gl
			);
		}

		// update bank ledger if used
		foreach($bank_trans as $bank_id => $amount)
   			//Modified by spyrax10 8 Feb 2022
			add_bank_trans(
				$this->trans_type, 
				$this->order_id, 
				$bank_id, 
				$this->reference,
				$this->tran_date, 
				$amount, 
				0, 
				"", 
				0, //Cashier
				null, 
				null, 
				0, 
				null, 
				0, 
				0, 
				get_current_user_fullname(), //Prepared by 
				null, //Checked by
				null, //Approved by
				null, //Module type
				$this->currency, 
				"Cannot insert a destination bank transaction"
			);

		// add AP/AR for journal transaction
		if ($this->trans_type == ST_JOURNAL) {
			// update AR
			foreach($cust_trans as $branch_id => $amount) {
				if (floatcmp($amount, 0)) {
					write_cust_journal($this->trans_type, $this->order_id, $branch_id, $this->tran_date,
						$this->reference, $amount, $this->rate
					);
				}
			}
			// update AP
			foreach($supp_trans as $supp_id => $amount) {
				if (floatcmp($amount, 0)) {
					write_supp_journal($this->trans_type, $this->order_id, $supp_id, $this->tran_date,
						$this->reference, -$amount, $this->rate, $this->source_ref
					);
				}
			}
		}

		// generate tax records for journal transaction
		if ($this->trans_type == ST_JOURNAL && is_array($this->tax_info)) {

			foreach($this->tax_info['net_amount'] as $tax_id => $net) {
				if (!$net) {
					continue;
				}

				// in EU VAT system intra-community goods aquisition is posted to both purchasing and sales tax accounts,
				// but included only in purchase register. To avoid double registering ELSE is used below!
				if (isset($this->tax_info['tax_in'][$tax_id])) {
					$tax = $this->tax_info['tax_in'][$tax_id];
					$reg = TR_INPUT;
				}
				elseif (isset($this->tax_info['tax_out'][$tax_id])) {
					$tax = $this->tax_info['tax_out'][$tax_id];
					$reg = TR_OUTPUT;
				}
				elseif (isset($this->tax_info['tax_reg'])) { // e.g. export
					$tax = 0;
					$reg = $this->tax_info['tax_reg'];
				} 
				else {
					continue;
				}

				$tax_nominal = $this->tax_info['rate'][$tax_id]/100*$net;
				add_trans_tax_details($this->trans_type, $this->order_id,
					$tax_id, $this->tax_info['rate'][$tax_id], 0, $tax_nominal, $net, $this->rate,
					$this->tran_date,
					$this->source_ref, $reg
				);
			}
		} // End of Inserting Tax Records

		if ($this->void_id > 0) {
			void_entries($this->void_id, ST_JOURNAL, $this->reference);
		}
	}
}

//--------------------------------------------------------------------------------------------

class line_item
{
	var $stock_id;
	var $item_code;
	var $item_description;
	var $units;
	var $mb_flag;

	var $quantity;
	var $price;
	var $standard_cost;
	
	var $manufacture_date;
	var $expire_date;
	var $lot_no;
	var $chasis_no;
	var $color;
	var $category_id;
	var $mt_lines;
	var $serialised;
	var $type_out;
	var $transno_out;
	var $remarks;
	var $item_type;
	//Added by spyrax10
	var $stock_ref; 
	var $mcode;
	var $master_file;
	var $ob_date;
	var $currqty; //Added by Robert 09/07/2022
	//
	var $line_item_row; //Added by Herald 09/01/2021
	
	function __construct($stock_id, $qty, $standard_cost=null, $description=null,$manufacture_date='0000-00-00',$expire_date='0000-00-00', 
		$lot_no = '', $chasis_no = '', $color = '', $item_code, $mt_lines = 0, 
		$type_out = 0, $transno_out = 0, $remarks = '', $item_type = 'new', 
		$stock_ref = '', $mcode = '', $master_file = '', //Added by spyrax10
		$line_item = '', $ob_date = null, $currqty = 0)
	{
		$item_row = get_item($stock_id);

		if ($item_row == null)
			display_error("invalid item added to order : $stock_id", "");

		$this->mb_flag = $item_row["mb_flag"];
		$this->units = $item_row["units"];
		$this->category_id = $item_row["category_id"];
		$this->serialised = $item_row["serialised"];
		$this->color = $color;
		$this->item_code = $item_code;
		$this->manufacture_date=$manufacture_date;
		$this->expire_date=$expire_date;
		$this->lot_no=$lot_no;
		$this->chasis_no=$chasis_no;
		$this->mt_lines=$mt_lines;
		$this->type_out = $type_out;
		$this->transno_out = $transno_out;
		$this->remarks = $remarks;
		$this->item_type = $item_type;
		//Added by spyrax10
		$this->stock_ref = $stock_ref; 
		$this->mcode = $mcode;
		$this->master_file = $master_file;
		$this->ob_date = $ob_date;
		$this->currqty = $currqty;
		//
		$this->line_item_row = $line_item;
		
		/*if($item_row["brand_name"]!=""){
			$brandname=" **".$item_row["brand_name"];
		}else $brandname="";
		
		if($item_row["manufacturer_name"]!=""){
			$manufacturername=" **".$item_row["manufacturer_name"];
		}else $manufacturername="";
		*/
		if ($description == null)
			$this->item_description = $item_row["description"];
		else
			$this->item_description = $description;

		if ($standard_cost == null)
			$this->standard_cost = $item_row["purchase_cost"];
		else
			$this->standard_cost = $standard_cost;

		$this->stock_id = $stock_id;
		$this->quantity = $qty;
		//$this->price = $price;
		$this->price = 0;
	}
}

//---------------------------------------------------------------------------------------

class gl_item
{

	var $code_id;
	var $dimension_id;
	var $dimension2_id;
	var $amount;
	var $reference;
	var $description;
	var $person_id;
	var $person_type_id;
	var $person_name;
	var $branch_id;
	var $date;
	var $mcode;	// Added by Ronelle 2/19/2021
	var $master_file;	// Added by Ronelle 2/19/2021
	var $hocbc;	// Added by Ronelle/2/22/2021
	//Added by spyrax10
	var $comp_id; 
	var $sug_mcode;
	//
    var $master_file_type; //Added by Herald 08/20/2021
    var $line_item; //Added by Herald 09/01/2021 for Complimentary Item module
	
	//Modified by spyrax10 10-11-2021
	function __construct($code_id = null, $dimension_id = 0, $dimension2_id = 0, $amount = 0, $memo = '',
	    $act_descr = null, $person_id = null, $date = null, $mcode = null, $master_file = null, $hocbc = 0, 
		$comp_id = null, $sug_mcode = null, 
		$master_file_type = 99, $line_item = '')
	{
		//echo "adding $index, $code_id, $dimension_id, $amount, $reference<br>";

		if ($act_descr == null && $code_id)
			$this->description = get_gl_account_name($code_id);
		else
			$this->description = $act_descr;

		$this->code_id = $code_id;
		$this->person_id = $person_id;
		$gl_type = is_subledger_account($code_id, $person_id);
		if ($gl_type)
		{
			$this->person_type_id = $gl_type > 0 ? PT_CUSTOMER : PT_SUPPLIER;
			$data = get_subaccount_data($code_id, $person_id);
			$this->person_name = $data['name'];
			$this->branch_id = $data['id'];
		}
		$this->dimension_id = $dimension_id;
		$this->dimension2_id = $dimension2_id;
		$this->amount = round($amount, 2);
		$this->reference = $memo;
		$this->date = $date;
		$this->mcode = $mcode;	// Added by Ronelle 2/19/2021
		$this->master_file= $master_file;	// Added by Ronelle 2/19/2021
		$this->hocbc = $hocbc;	// Added by Ronelle 2/22/2021
		//Added by spyrax10 10-11-2021
		$this->comp_id = $comp_id;
		$this->sug_mcode = $sug_mcode; 
		//
		$this->master_file_type = $master_file_type; //Added by Herald 08/20/2021
	
		$this->line_item = $line_item;
	}
}
