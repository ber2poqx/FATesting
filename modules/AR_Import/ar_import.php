<?php
	/**********************************************
	Author: Albert
	Name: Import of AR Opening Balances
	***********************************************/
	$page_security = 'SA_CSVARIMPORT';
	$path_to_root="../..";

	include($path_to_root . "/includes/session.inc");
	add_access_extensions();

	include_once($path_to_root . "/includes/ui.inc");
	include_once($path_to_root . "/includes/data_checks.inc");

	include_once($path_to_root . "/modules/AR_Import/ar_import.inc");
	include_once($path_to_root . "/sales/includes/db/custalloc_db.inc");
	
	include_once($path_to_root . "/inventory/includes/inventory_db.inc");
	include_once($path_to_root . "/inventory/includes/db/items_codes_db.inc");
	include_once($path_to_root . "/dimensions/includes/dimensions_db.inc");

	include_once($path_to_root . "/includes/db_pager.inc");
	include_once($path_to_root . "/includes/session.inc");
	include_once($path_to_root . "/admin/db/company_db.inc");

	include_once($path_to_root . "/includes/date_functions.inc");
	include_once($path_to_root . "/includes/ui/attachment.inc");
	include_once($path_to_root . "/sales/includes/sales_db.inc");
	include_once($path_to_root . "/lending/includes/db/customers_payment_db.inc");
	include_once($path_to_root . "/inventory/includes/inventory_db.inc"); //Added by spyrax10



	$action = 'import';
	if (isset($_GET['action'])) $action = $_GET['action'];
	if (isset($_POST['action'])) $action = $_POST['action'];

	page("Import of AR Opening Balances");

	if (isset($_POST['import'])) {
		if (isset($_FILES['impCSVS']) && $_FILES['impCSVS']['name'] != '') {
			$filename = $_FILES['impCSVS']['tmp_name'];
			$sep = $_POST['sep'];

			$fp = @fopen($filename, "r"); 
			if (!$fp)
				die("can not open file $filename");

				$lines = $CI = 0;
	
				while ($data = fgetcsv($fp, 4096, $sep)) {
					if ($lines++ == 0) continue;
				    list(
						$old_trans_no, 
						$debtor_no, 
						$orig_branch_code, 
						$tran_date,
						$ref_no,
						$first_due_date,
						$maturity_date,
						$plcy_code,
						$months_term, 
						$rebate, 
						$f_rate, 
						$d_amount, 
						$ov_amount, 
						$quantity, 
						$stock_id,
					 	$color_code, 
						$lot_no, 
						$chassis_no, 
						$amortization_amount, 
						$total_amount_paid,
						$standard_cost,
						$unit_price,
						$deferred_gross_profit, 
						$profit_margin, 
						$warranty_code) = $data;

				amort_calculation($d_amount, $unit_price, $f_rate, $months_term, $rebate, $ov_amount, $amortization_amount);
				  
					if (check_transaction_already_exist($old_trans_no)) 
					{
				        $sql = "SELECT old_trans_no FROM ".TB_PREF."debtor_loans WHERE  old_trans_no = ".db_escape($old_trans_no);
				       
						$result = db_query($sql, "Could not search old transaction no");
				        $row = db_fetch_row($result);
				        $CI++;	
				        display_error("Line $lines: The old Document no: $old_trans_no is Already Exist");
					
					}else if (!check_stock_id_exist($stock_id)){
					
						display_error("Line $lines: Stock Id: $stock_id is Not Exist");

					}else if(!check_color_exist($stock_id, $color_code, true) ){

						display_error("Line $lines: Color : $color_code is Not Exist");
					
					}else if ($old_trans_no == "") { // Old Transaction # can't be empty!
						
						display_error("Line $lines: Old Transaction # is empty!");
					
					}else if ($debtor_no == "") { // Customer # can't be empty!
						
						display_error("Line $lines: Customer # is empty!");
					
					}else if ($orig_branch_code == "") { // Customer # can't be empty!
						
						display_error("Line $lines: Customer # is empty!");

					}else if ($ref_no == "") { // Reference # can't be empty!
						
						display_error("Line $lines: Ref_no is empty!");

					}else if ( $stock_id == "") { //Itemcode can't be empty!
						
						display_error("Line $lines: Itemcode is empty!");
					
					}else if ( $tran_date== "") { // Invoice Date can't be empty!
						
						display_error("Line $lines: Invoice Date is Empty empty!");

					}else if ( $first_due_date== "") { // First Duedate can't be empty!
						
						display_error("Line $lines: First Duedate is Empty empty!");

					}else if ( $maturity_date== "") { // Maturity Date can't be empty!
						
						display_error("Line $lines: Maturity Date is Empty empty!");
					
					}else if ( $months_term== "") { // Months Term can't be empty!
						
						display_error("Line $lines:  Months Term is Empty empty!");

					// }else if ( $d_amount== "") { // Down Payment can't be empty!
					// 		display_error("Line $lines:  Down Payment is Empty empty!");
							
					}else if ( $ov_amount== "") { // Ar Amount can't be empty!
						
						display_error("Line $lines: Ar Amount is Empty empty!");
					
					}else if ( $quantity== "") { // Quantity can't be empty!
						
						display_error("Line $lines:  Quantity is Empty empty!");
					
					}else if ( $lot_no== "") { // Serial No can't be empty!
						
						display_error("Line $lines: Serial No is Empty empty!");
					
					}else if ( $amortization_amount== "") { // Amortization can't be empty!
						
						display_error("Line $lines: Amortization is Empty empty!");

					// }else if ( $total_amount_== "") { // Total Amount can't be empty!
					// 	display_error("Line $lines: Total Amount is Empty empty!");
					
					}else if ($standard_cost== "") { // Category ID can't be empty!
						
						display_error("Line $lines: Invoice Date is Empty empty!");
					
					}else if ( $unit_price== "") { // Unit price/Lcp can't be empty!
						
						display_error("Line $lines: Unit price/Lcp is Empty empty!");
					
					} else {

						if (check_customer_code_already_exist($debtor_no))
						{
							global $Refs;
							$ref_num = '';
							$account_no = '';
							
							$date_ = $tran_date;
							$debtor_no = get_customer_code(normalize_chars($debtor_no));
							$category_id = get_category_id($stock_id);
							$description = get_item_description($stock_id);
							$ref_num = $Refs->get_next(ST_SALESINVOICE, null, @$tran_date);
							$installmentplcy_id = get_installment_policy($plcy_code);
							$cust_branch = get_cust_branch_data($debtor_no);
							if ($total_amount_paid > 0)
							{
								$loans_status = 'part-paid';
							}else
							{
								$loans_status = 'unpaid';
							}
							//Modified by spyrax10
							$max_num = max(get_max_trans_no(ST_SALESINVOICE), get_max_trans_no(ST_SALESINVOICEREPO));
							$trans_no = $max_num + 1;
							//
							$principal_run_bal = $ov_amount - $d_amount;

						 	//Added by soyrax10
							if (empty($tran_date)) {
								$tran_date = Today();
							}
							else {
								$tran_date = date2sql($tran_date);
							}
							//
							if( empty($first_due_date )){ 
								$tran_date = "0000-00-00";
							 } else {
								$first_due_date = date("Y-m-d", strtotime($first_due_date));
							}
							if( empty($maturity_date )){ 
								$maturity_date = "0000-00-00";
							 } else {
								
								$maturity_date = date("Y-m-d", strtotime($maturity_date));
							}
							if ( empty($rebate )){ 
								$rebate=0;
							}
							if ( empty($f_rate )){ 
								$f_rate=0;
							}
							if ( empty($plcy_code )){ 
								$plcy_code=0;
							}

							add_loan_schedule(
							$trans_no,
							$debtor_no, 
							$tran_date,   
							0, 
							$d_amount, 
							$principal_run_bal
							);
							
							$sched_due_date = $first_due_date;
							for ($i = 1; $i <= $months_term; $i++) 
							{
							
								$principal_run_bal = $principal_run_bal -  $amortization_amount;
								
								add_loan_schedule(
									$trans_no,
									$debtor_no,
									$sched_due_date,  
									$i, 
									$amortization_amount, 
									$principal_run_bal, 
									date('D', strtotime($sched_due_date)));
								

								$sched_due_date = date("Y-m-d", strtotime("+1 month", strtotime($sched_due_date)));
										
							}

								add_debtor_trans(
								$trans_no,
								$debtor_no, 
								$tran_date, 
								$ref_num,  
								$ov_amount,
								$installmentplcy_id,
								$total_amount_paid,
							    $cust_branch['branch_code']);	

								
								add_debtor_loan( 
								$trans_no,
								$debtor_no, 
								$ref_num, 
								$tran_date, 
								$orig_branch_code,
								$installmentplcy_id,
								$months_term, 
								$rebate, 
								$f_rate,  
								$ov_amount, 
								$first_due_date,
								$maturity_date, 
								$unit_price, 
								$d_amount, 
								$amortization_amount, 
								$standard_cost,  
								$category_id, 
								$warranty_code,
								$deferred_gross_profit,
								$profit_margin,
								$old_trans_no,
								$ref_no,
								$loans_status);
								
								$item_color_code = check_color_exist($stock_id, $color_code);
								add_debtor_trans_det(
								$trans_no,	
								$debtor_no,
								$stock_id, 
								$description, 
								$quantity, 
								$unit_price, 
								$tran_date,
								$standard_cost, 
								$lot_no, 
								$chassis_no, 
								$item_color_code["item_code"]);

								$oustanding_balance = $ov_amount - $total_amount_paid;

								$hoc_code = get_company_value(0, 'branch_code');
								$hoc_masterfile = get_company_value(0, 'name');

								add_gl_trans_customer(
									ST_SALESINVOICE,
									$trans_no,
									$date_,
									$account_no =get_customer_receivables_account($debtor_no),
									0,
									0,
									$ov_amount,
									$debtor_no,
									"The sales price GL posting could not be inserted"
								);

								add_gl_trans_customer(
									ST_SALESINVOICE,
									$trans_no,
									$date_,
									$account_no =get_customer_receivables_account($debtor_no),
									0,
									0,
									-1 * $total_amount_paid,
									$debtor_no,
									"The sales price GL posting could not be inserted"
								);	
								
								add_gl_trans_customer(
									ST_SALESINVOICE,
									$trans_no,
									$date_,
									$account_no =get_customer_sales_account(),
									0,
									0,
									-1 * abs($oustanding_balance),
									$debtor_no,
									"The sales price GL posting could not be inserted",
									0,
									$hoc_code,
									$hoc_masterfile
								);
								add_gl_trans_customer(
									ST_SALESINVOICE,
									$trans_no,
									$date_,
									$account_no =get_account_code(),
									0,
									0,
									(-$deferred_gross_profit) *1,
									$debtor_no,
									"The total debtor GL posting could not be inserted"
								);
						
								add_gl_trans_customer(
									ST_SALESINVOICE,
									$trans_no,
									$date_,
									$account_no =get_customer_sales_account(),
									0,
									0,
									($deferred_gross_profit) *1,
									$debtor_no,
									"The total debtor GL posting could not be inserted",
									0,
									$hoc_code,
									$hoc_masterfile

								);

								/*alloc*/
								$amortization_schedule = get_deptor_loan_schedule_ob($trans_no, $debtor_no, ST_SALESINVOICE);
								$total_exist_payment = floatval($total_amount_paid);
								while ($amort_sched = db_fetch($amortization_schedule)) {
									if ($total_exist_payment == 0)
										break;

									$amount = 0;
									$status = "paid";
									if ($total_exist_payment >= $amortization_amount) {
										$amount = $amort_sched["total_principaldue"];
									} else {
										$amount = $total_exist_payment;
										$status = "partial";
									}
									add_loan_ledger(
										$trans_no,
										$debtor_no,
										$amort_sched["id"],
										ST_SALESINVOICE,
										ST_CUSTPAYMENT,
										$amount,
										0,
										0,
										0,
										$tran_date,
										0
									);

									$total_exist_payment -= $amount;
									$loansched_id = $amort_sched["id"];
									$sql = "UPDATE " . TB_PREF . "debtor_loan_schedule SET
										status=" . db_escape($status) . ",penalty_status=" . db_escape($status) . "
										WHERE id=$loansched_id";

									$ErrMsg = _('Could not update loan schedule because ');

									db_query($sql, $ErrMsg);
								}

								add_cust_allocation(floatval(
									$total_amount_paid), 
									ST_CUSTPAYMENT, 
									$trans_no, 
									ST_SALESINVOICE, 
									$trans_no, 
									$debtor_no,  
									$date_);
    							update_debtor_trans_allocation( 
									ST_SALESINVOICE, 
									$trans_no, 
									$debtor_no);

								/**/

								
							
								$CI++;	
									display_notification("Line  $lines: The Old Transaction No: $old_trans_no is successfully Added Ar Installment Opening Balances.  Customer No : $debtor_no");
									
						}else{
							display_error("Line $lines: Customer name  is not Exist!");
								
						}


				    }	
				}			
			@fclose($fp);
			if ($CI > 0) display_notification("$CI :Ar Installment Opening Balances is Added.");
		} else display_error("No CSV file selected");
	}

	if ($action == 'import') echo 'Import Openning Balances';
	else hyperlink_params($_SERVER['PHP_SELF'], _("Import"), "action=import", false);
	echo "<br><br>";

	if ($action == 'import') {
		start_form(true);

		start_table(TABLESTYLE2, "width=45%");

		if (!isset($_POST['sep']))
		$_POST['sep'] = ",";

		table_section_title("Import A/R Openning Balances");
		text_row("Field separator:", 'sep', $_POST['sep'], 2, 1);
		label_row("CSV Import File:", "<input type='file' id='impCSVS' name='impCSVS'>");

		end_table(1);
		submit_center('import', "Import CSV File");
		end_form();
		end_page();
	}
function amort_calculation($d_amount, $unit_price, $f_rate, $months_term, $rebate,$ov_amount, $amortization_amount){

	//amort calculation

	$quotient_financing_rate = $f_rate / 100;
	$diff_lcp_downpayment = $unit_price - $d_amount;

	$amount_to_be_finance = $unit_price - $d_amount;
	$interest_charge = $quotient_financing_rate * $amount_to_be_finance;

	$sum_of_interest_charge_and_atbf = $interest_charge + $amount_to_be_finance;

	$amort_wo_rebate = $sum_of_interest_charge_and_atbf / $months_term;

	$amort = round($amort_wo_rebate + $rebate);

	$total_amount_cal = $amort * $months_term + $d_amount;
	//

	if($amort != $amortization_amount){
		display_error("The amortation amount: $amortization_amount not match with the system calculation amount: $amort !!!");
	}
	if($total_amount_cal != $ov_amount){
		display_error("The Gross amount: $ov_amount not match with the system calculation Gross amount: $total_amount_cal !!!");
	}
}
?>