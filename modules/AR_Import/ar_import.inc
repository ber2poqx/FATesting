<?php

/**
 * Created By: Albert
 */


function add_debtor_trans(
						$type,
						$trans_no, 
						$debtor_no, 
						$tran_date=0000-00-00,
						$reference, 
						$ov_amount=0,
						$installmentplcy_id=0,
						$total_amount_paid,
						$cust_branch)
{
	global $Refs;
	if ($total_amount_paid > 0)
	{
		$status = 'part-paid';
	}else
	{
		$status = 'Open';
	}
	set_global_connection();

   $sql = "INSERT INTO ".TB_PREF."debtor_trans (
							trans_no, 
							type, 
							version, 
							debtor_no, 
							branch_code, 
							tran_date, 
							due_date, 
							reference, 
							tpe, 
							order_, 
							ov_amount, 
							ov_gst, 
							ov_freight, 
							ov_freight_tax, 
							ov_discount, 
							alloc, 
							prep_amount, 
							rate, 
							ship_via, 
							dimension_id, 
							dimension2_id, 
							payment_terms, 
							tax_included, 
							status, 
							opening_balances, 
							payment_type, 
							collect_id, 
							module_type,
							repo_date)
					VALUES (
							'$trans_no', 
							'$type', 
							'0', 
							'$debtor_no',
							'$cust_branch', 
							'$tran_date', 
							'$tran_date', 
							'$reference', 
							'2', 
							'0', 
							'$ov_amount', 
							'0', 
							'0', 
							'0', 
							'0', 
							'$total_amount_paid', 
							'0', 
							'1', 
							'1', 
							'0', 
							'0', 
							'$installmentplcy_id',
							'0',
							'$status', 
							'1', 
							'', 
							'0',
							'',
							'0000-00-00')";

  			db_query($sql,"The Ar Installment Opening Balances could not be added");
}
function add_debtor_trans_det(
							$type,
							$trans_no, 
							$debtor_no,
							$stock_id, 
							$description, 
							$quantity=0, 
							$unit_price=0, 
							$tran_date=0000-00-00, 
							$standard_cost=0, 
							$lot_no=null, 
							$chassis_no=null, 
							$color_code=null)
{
	set_global_connection();

  	$sql = "INSERT INTO ".TB_PREF."debtor_trans_details ( 
		  					debtor_trans_no, 
							debtor_trans_type, 
							stock_id, 
							description, 
							quantity, 
							unit_price, 
							unit_tax,
							discount_percent, 
							standard_cost, 
							src_id, 
							lot_no, 
							chassis_no, 
							color_code, 
							item_type, 
							discount1, 
							discount2) 
						VALUES(
							'$trans_no', 
							'$type', 
							'$stock_id', 
							'$description',
							'$quantity',
							'$unit_price',
							'0',
							'0',
							'$standard_cost',
							'0',
							'$lot_no',
							'$chassis_no', 
							'$color_code',
							'Regular',
							'0',
							'0')";

   db_query($sql,"The Ar Installment Opening Balances could not be added");
}
function add_debtor_loan(
						$trans_no,
						$debtor_no, 
						$reference, 
						$tran_date=0000-00-00,
						$orig_branch_code=null,
						$installmentplcy_id=0,
						$months_term=0, 
						$rebate=0, 
						$f_rate=0,  
						$ov_amount=0, 
						$first_due_date=0000-00-00, 
						$maturity_date=0000-00-00,
 						$unit_price=0, 
						$d_amount=0, 
						$amortization_amount=0, 
						$standard_cost=0, 
						$category_id, 
						$warranty_code=null,
						$deferred_gross_profit=0, 
						$profit_margin=0,
						$old_trans_no,
						$ref_no, 
						$status,
						$invoice_type)
{
	set_global_connection();
	// $first_due_date = date("Y-m-d", strtotime("+1 month", strtotime( $tran_date)));
		$outstanding_ar_amount = $ov_amount-$d_amount;

		$sql = "INSERT INTO ".TB_PREF."debtor_loans ( 
						trans_no, 
						debtor_no, 
						invoice_ref_no, 
						delivery_ref_no, 
						reference,
		   				invoice_date, 
						orig_branch_code, 
						invoice_type, 
						installmentplcy_id, 
						months_term, rebate, 
						financing_rate, 
						firstdue_date, 
						maturity_date, 
						outstanding_ar_amount, 
						ar_amount, 
						lcp_amount, 
						downpayment_amount, 
						amortization_amount, 
						total_amount, 
						category_id, 
						payment_status, 
						warranty_code, 
						fsc_series, 
						payment_location, 
						co_maker, 
						discount_downpayment,
						deferred_gross_profit,
						profit_margin,
						ref_no,
						old_trans_no) 
				VALUES (
						'$trans_no',
						'$debtor_no',
						'$reference',
						'',
						'$reference',
						'$tran_date',
						'$orig_branch_code',
						'$invoice_type',
						'$installmentplcy_id',
						'$months_term', 
						'$rebate', 
						'$f_rate',
						'$first_due_date',
						'$maturity_date', 
						'$outstanding_ar_amount', 
						'$ov_amount',
						'$unit_price',
						'$d_amount', 
						'$amortization_amount', 
						'$standard_cost', 
						'$category_id', 
						'$status', 
						'$warranty_code', 
						'',
						'Branch', 
						'', 
						'0', 
						'$deferred_gross_profit', 
						'$profit_margin', 
						'$ref_no',
						'$old_trans_no')";

    db_query($sql,"The Ar Installment Opening Balances could not be added");

}
function add_loan_schedule( 
						$type,
						$trans_no, 
						$debtor_no, 
						$tran_date=0000-00-00,  
						$months_term=0, 
						$d_amount=0,  
						$ov_amount=0)
{
	set_global_connection();
	$weekday=date('D', strtotime($tran_date));//get the weekday

   	$sql = "INSERT INTO ".TB_PREF."debtor_loan_schedule (
						debtor_no, 
						trans_no, 
						trans_type, 
						month_no, 
						date_due, 
						weekday, 
						principal_due, 
						principal_runbal, 
						total_principaldue,
						total_runbal, 
						interest_due, 
						interest_runbal, 
						status )
				VALUES (
						'$debtor_no', 
						'$trans_no', 
						'$type', 
						'$months_term',
						'$tran_date', 
						'$weekday',
						'$d_amount',
						'$ov_amount', 
						'$d_amount',
						'$ov_amount',
						'0',
						'0',
						'unpaid')";


	db_query($sql,"The Ar Installment Opening Balances could not be added");

}


// not allowed document already exist
function check_transaction_already_exist($old_trans_no, $invoice_type, $term)
{
	set_global_connection();
	if($term == 3){
		$sql = " SELECT 
   				COUNT(*) 
   			FROM ".TB_PREF."debtor_loans
		    WHERE months_term = 3 and old_trans_no = ".db_escape($old_trans_no)." And invoice_type = ".db_escape($invoice_type);
	}else{
		$sql = " SELECT 
					COUNT(*) 
				FROM ".TB_PREF."debtor_loans
				WHERE months_term <> 3 and old_trans_no = ".db_escape($old_trans_no)." And invoice_type = ".db_escape($invoice_type);
	}
   $result = db_query($sql, "check old_trans_no Code failed");
   $count =  db_fetch($result);

   return $count[0];
}
// not allowed customer not exist
function check_customer_code_exist($debtor_no)
{
	set_global_connection();
   $sql = " SELECT 
   				COUNT(*) 
   			FROM ".TB_PREF."debtors_master
		    WHERE name = ".db_escape($debtor_no);
   $result = db_query($sql, "check Customer Code failed");
   $count =  db_fetch($result);

   return $count[0];

}

function get_customer_code($debtor_no)
{
	set_global_connection();
   $sql = " SELECT 
   				debtor_no 
			FROM ".TB_PREF."debtors_master
		    WHERE name = ".db_escape($debtor_no);
   $result = db_query($sql, "check Customer Code failed");
   $row =  db_fetch($result);

   return $row[0];

}

function get_category_id($stock_id)
{
	set_global_connection();
	$sql = "SELECT 
				category_id 
			FROM ".TB_PREF."stock_master
			WHERE  stock_id = ".db_escape($stock_id);

	$result = db_query($sql, "check category name failed");
	$row = db_fetch_row( $result);

	return $row[0];
}

function get_installment_policy($plcy_code)
{
	set_global_connection();
	$sql = "SELECT 
				id 
			FROM ".TB_PREF."policy_details
			where plcydtl_code =".db_escape($plcy_code);

	$result = db_query($sql, "check Policy Code failed");
	$row = db_fetch_row( $result);
 
	return $row[0];

}
function get_cust_branch_data($debtor_no)
{
	$sql = "SELECT 
				*
			FROM ".TB_PREF."cust_branch
			where debtor_no =".db_escape($debtor_no);

	$result = db_query($sql, "Can get cust branch data!!!");
	set_global_connection();
	return db_fetch($result);

}

function get_item_description_ar_import($stock_id)
{
	set_global_connection();
	$sql = "SELECT 
				description 
			FROM ".TB_PREF."stock_master
			where stock_id =".db_escape($stock_id);

	$result = db_query($sql, "check sales_account Code failed");
	$row = db_fetch_row( $result);

	return $row[0];

}
?>