<?php


function write_restructured(&$invoice, $opening_balances=0)
{
    global $Refs;

    $trans_no = $invoice->trans_no;
    if (is_array($trans_no))
        $trans_no = key($trans_no);

    $date_ = $invoice->document_date;
    // $charge_shipping = $invoice->freight_cost;

    begin_transaction();

    hook_db_prewrite($invoice, ST_RESTRUCTURED );
    $company_data = get_company_prefs();

    $branch_data = get_branch_accounts($invoice->Branch);

    $customer = get_customer($invoice->customer_id);
    
    // if (!$invoice->is_prepaid())
    //     update_customer_trans_version(get_parent_type(ST_RESTRUCTURED ), $invoice->src_docs);
    // elseif (count($invoice->prepayments)) {    // partial invoice
    //     $last_payment = end($invoice->prepayments);
    //     $gl_date = sql2date($last_payment['tran_date']);
    // } else {    // final invoice
    //     $gl_date = $invoice->document_date;
    // }

    $cash_discount = 0;
    $ov_gst = 0;
    $dec = user_price_dec();

    $items_added_tax = 0;
    $freight_added_tax = 0;

    /*Added by Albert*/
    $row = get_SI_by_reference($invoice->document_ref);
    $loans = get_ar_amount($invoice->document_ref);
    
    if($row['opening_balances']==1){
        $previous_ar_amount = $loans['ar_amount'];
        $previous_ar_balance = $loans['ar_amount'] - $row['alloc'];
        $ov_amount = get_ob_standard_cost($row['trans_no']);
    }else{
        $previous_ar_amount = $invoice->prev_ar_amount;
        $previous_ar_balance = $invoice->prev_ar_balance;
        $ov_amount = get_cost_of_sales_for_si($invoice->dr_ref);
    }
    if($invoice->calculation_id == 1){
        $allocation = 0;
    }else{
        $allocation = $row['alloc'];
    }

    set_global_connection();

    $restructured_no = write_customer_trans(
        ST_RESTRUCTURED,
        $trans_no,
        $invoice->customer_id,
        $invoice->Branch,
        $date_,
        $invoice->reference,
        $invoice->ar_amount,
        0,
        $items_added_tax,
        $invoice->freight_cost,
        $freight_added_tax,
        $invoice->sales_type,
        0,
        $invoice->ship_via,
        $invoice->due_date,
        $allocation,
        0,
        $invoice->dimension_id,
        $invoice->dimension2_id,
        $invoice->payment,
        $invoice->tax_included,
        $invoice->prep_amount,
        $opening_balances
    );
    /*Added by Albert*/
    foreach ($invoice->line_items as $line_no => $invoice_line) {
        $term_mode_type = get_termode_serial_no($invoice_line->lot_no) ? 'repo' : 'new';
    }
    add_debtor_term_modification(
        $restructured_no,
        $invoice->customer_id,
        $invoice->document_ref,
        $invoice->dr_ref,
        $date_,
        $invoice->payment_policy,
        $invoice->months_term,
        $invoice->rebate,
        $invoice->financing_rate,
        $invoice->first_due_date,
        $invoice->maturity_date,
        $invoice->outstanding_ar_amount,
        $invoice->ar_amount,
        $invoice->lcp_amount,
        $invoice->dp_amount,
        $invoice->amortization,
        $invoice->amort_diff,
        $invoice->months_paid,
        $invoice->amort_delay,
        $invoice->adj_rate,
        $invoice->opportunity_cost,
        $invoice->amount_to_be_paid,
        $invoice->Comments,
        $invoice->category_id, //Added by Albert
        $invoice->new_profit_margin,
        $term_mode_type,
        ST_RESTRUCTURED,
        0,
        0,
        0,
        'unpaid',
        $row['trans_no']

    );
    // Down-payment 
    add_loan_schedule_ob(
        ST_RESTRUCTURED,
        $restructured_no,
        $invoice->customer_id,
        0,
        $date_,
        date('D', strtotime($date_)),
        $invoice->dp_amount,
        ($invoice->ar_amount - $invoice->dp_amount),
        $invoice->dp_amount,
        ($invoice->ar_amount - $invoice->dp_amount),
        0,
        0
    );

    // Monthly Payment
    $sched_due_date = $invoice->first_due_date;
    $principal_run_bal = $invoice->ar_amount - $invoice->dp_amount;
    for ($i = 1; $i <= $invoice->months_term; $i++) {
        $principal_run_bal = $principal_run_bal - $invoice->amortization;
        add_loan_schedule_ob(
            ST_RESTRUCTURED,
            $restructured_no,
            $invoice->customer_id,
            $i,
            $sched_due_date,
            date('D', strtotime($sched_due_date)),
            $invoice->amortization,
            $principal_run_bal,
            $invoice->amortization,
            $principal_run_bal,
            0,
            0
        );
        $sched_due_date = date("m/d/Y", strtotime("+1 month", strtotime($sched_due_date)));
    }

    // GL Entries
    $total = 0;
    /* ------------- */
    $ar_account = $branch_data["receivables_account"];
    if ($invoice->months_term >= 1 && $invoice->months_term <= 3 && !check_employee_customer($invoice->customer_id))
        $ar_account = $company_data["ar_reg_current_account"];
    $total += add_gl_trans_customer(
        ST_RESTRUCTURED,
        $restructured_no,
        $date_,
        $ar_account,
        0,
        0,
        $invoice->outstanding_ar_amount,
        $invoice->customer_id,
        "The total debtor GL posting could not be inserted"
    );
    /* */

    /* ------------ */
    $category_gl_code = get_gl_code_from_category($invoice->category_id);
    if ($invoice->months_term >= 1 && $invoice->months_term <= 3 && !check_employee_customer($invoice->customer_id))
        $sales_account = $category_gl_code['dflt_regular_sales_act'];
    if ($invoice->months_term >= 4 && !check_employee_customer($invoice->customer_id))
        $sales_account = $category_gl_code['dflt_installment_sales_act'];
    if (date("Y", strtotime($date_)) > date("Y", strtotime($row['tran_date']))){
        $sales_account = $company_data['default_sales_act'];
    }
    $total += add_gl_trans_customer(
        ST_RESTRUCTURED,
        $restructured_no,
        $date_,
        $sales_account,
        0,
        0,
        -1 * abs($invoice->outstanding_ar_amount),
        $invoice->customer_id,
        "The sales price GL posting could not be inserted"
    );
    /* */

    /* ----------- */
    if (!check_employee_customer($invoice->customer_id)) {
        $deferred_gross_profit = $invoice->ar_amount - $ov_amount;
        $new_total_dgp =  $row['alloc'] * $invoice->new_profit_margin;
        $new_dgp_balance = $deferred_gross_profit - $new_total_dgp;
    if (date("Y", strtotime($date_)) > date("Y", strtotime($row['tran_date']))){
        $company_data_account = $company_data['default_sales_act'];
    }else{
        $company_data_account = $company_data["isd_account"];
    }
        $total += add_gl_trans_customer(
            ST_RESTRUCTURED,
            $restructured_no,
            $date_,
            $company_data_account,//modified by Albert
            0,
            0,
            $new_dgp_balance,
            $invoice->customer_id,
            "The total debtor GL posting could not be inserted"
        );

        $total += add_gl_trans_customer(
            ST_RESTRUCTURED,
            $restructured_no,
            $date_,
            $company_data["dgp_account"],//modified by Albert
            0,
            0,
            -1 * abs($new_dgp_balance),
            $invoice->customer_id,
            "The total debtor GL posting could not be inserted"
        );
    }
    /* */

    /* ------------- */


    if ($invoice->prev_months_term >= 1 && $invoice->prev_months_term <= 3 && !check_employee_customer($invoice->customer_id))
        $sales_account = $category_gl_code['dflt_regular_sales_act'];
    if ($invoice->prev_months_term >= 4 && !check_employee_customer($invoice->customer_id))
        $sales_account = $category_gl_code['dflt_installment_sales_act'];
    if (date("Y", strtotime($date_)) > date("Y", strtotime($row['tran_date'])))//Added by Albert
        $sales_account = $sales_account = $company_data['default_sales_act'];

    $total += add_gl_trans_customer(
        ST_RESTRUCTURED,
        $restructured_no,
        $date_,
        $sales_account,
        0,
        0,
        $previous_ar_balance,
        $invoice->customer_id,
        "The sales price GL posting could not be inserted",
        0,
        '',
        '',
        0,
        $row['trans_no']
    );


    $ar_account = $branch_data["receivables_account"];
    if ($invoice->prev_months_term >= 1 && $invoice->prev_months_term <= 3 && !check_employee_customer($invoice->customer_id))
        $ar_account = $company_data["ar_reg_current_account"];
    $total += add_gl_trans_customer(
        ST_RESTRUCTURED,
        $restructured_no,
        $date_,
        $ar_account,
        0,
        0,
        -1 * abs($previous_ar_balance),
        $invoice->customer_id,
        "The total debtor GL posting could not be inserted",
        0,
        '',
        '',
        0,
        $row['trans_no']
    );
    /* */

    /* ----------- */
    if (!check_employee_customer($invoice->customer_id)) {
        $deferred_gross_profit = $previous_ar_amount - $ov_amount;
        $total_dgp =  $row['alloc'] * $loans['profit_margin'];
        $old_dgp_balance = $deferred_gross_profit - $total_dgp;
        
        $total += add_gl_trans_customer(
            ST_RESTRUCTURED,
            $restructured_no,
            $date_,
            $company_data["dgp_account"],
            0,
            0,
            $old_dgp_balance,
            $invoice->customer_id,
            "The total debtor GL posting could not be inserted",
            0,
            '',
            '',
            0,
            $row['trans_no']
        );

        if (date("Y", strtotime($date_)) > date("Y", strtotime($row['tran_date'])))
            $company_data_account = $company_data['default_sales_act'];
        else{
            $company_data_account = $company_data["isd_account"];
        }
        $total += add_gl_trans_customer(
            ST_RESTRUCTURED,
            $restructured_no,
            $date_,
            $company_data_account,//modified by Albert
            0,
            0,
            -1 * abs($old_dgp_balance),
            $invoice->customer_id,
            "The total debtor GL posting could not be inserted",
            0,
            '',
            '',
            0,
            $row['trans_no']
        );
    }
    /* */


    $invoice->status = "Closed";  
    //modified by Albert
    if(get_termode_serial_no($invoice->line_items[0]->lot_no)){
        closed_sales_invoice_status_repo($invoice, $restructured_no, $invoice->document_ref);
    }else{
    // Added by spyrax10
        closed_sales_invoice_status($invoice, $restructured_no, $invoice->document_ref, $invoice->payment_location);
    }
    $row = get_SI_by_reference($invoice->document_ref);
    $si_trans_no = $row['trans_no'];
    $loans =  get_ar_amount($invoice->document_ref);
    $allocatedAmount = $loans['ar_amount']- $row['alloc'];
    
    $Refs->save(ST_RESTRUCTURED, $restructured_no, $invoice->reference);
    if($invoice->calculation_id == 1){
        //close invoice alloc
        add_cust_allocation($allocatedAmount, ST_RESTRUCTURED , $restructured_no, $row['type'], $si_trans_no, $invoice->customer_id, $date_);
        update_debtor_trans_allocation($row['type'], $si_trans_no, $invoice->customer_id);
        /*----end Albert-----*/ 

        add_gl_balance($row['type'], $restructured_no, $date_, -$total, PT_CUSTOMER, $invoice->customer_id);

    }else{
        //Add termode alloc
        add_cust_allocation($row['alloc'], ST_RESTRUCTURED, $restructured_no, ST_RESTRUCTURED, $restructured_no, $invoice->customer_id, $date_);
        //close invoice alloc
        add_cust_allocation($allocatedAmount, ST_RESTRUCTURED , $restructured_no, $row['type'], $si_trans_no, $invoice->customer_id, $date_);
        update_debtor_trans_allocation($row['type'], $si_trans_no, $invoice->customer_id);
        /*----end Albert-----*/ 

        add_gl_balance($row['type'], $restructured_no, $date_, -$total, PT_CUSTOMER, $invoice->customer_id);

        $amortization_schedule = get_deptor_loan_schedule_ob($restructured_no, $invoice->customer_id, ST_RESTRUCTURED );
        $total_exist_payment = floatval($invoice->alloc);
        while ($amort_sched = db_fetch($amortization_schedule)) {
            if ($total_exist_payment == 0)
                break;

            $amount = 0;
            $status = "paid";
            if ($total_exist_payment >= $invoice->amortization) {
                $amount = $amort_sched["total_principaldue"];
            } else {
                $amount = $total_exist_payment;
                $status = "partial";
            }
            add_loan_ledger_ct(
                $restructured_no,
                $invoice->customer_id,
                $amort_sched["id"],
                ST_RESTRUCTURED,
                ST_RESTRUCTURED,
                $amount,
                0,
                0,
                0,
                date2sql($date_),
                $restructured_no,
                1
            );

            $total_exist_payment -= $amount;
            $loansched_id = $amort_sched["id"];
            $sql = "UPDATE " . TB_PREF . "debtor_loan_schedule SET
			        status=" . db_escape($status) . ",penalty_status=" . db_escape($status) . "
			        WHERE id=$loansched_id";

            $ErrMsg = _('Could not update loan schedule because ');

            db_query($sql, $ErrMsg);
        }

    }

    hook_db_postwrite($invoice, ST_RESTRUCTURED );

    commit_transaction();
    return $restructured_no;
}

/*  get sales invoice change term */
function get_sales_invoice_restructured_header($invoice_no)
{
    //modified by albert 05/10/2022
	set_global_connection();
    
    $sql = "SELECT
				c.name,
				c.debtor_no,
				a.reference as si_ct_no,
				a.status,
				a.tran_date as invoice_ct_date,

				b.months_term as new_months_term,
				b.rebate as new_rebate,
				b.financing_rate as new_financing_rate,
				b.amortization_amount as new_amort,
				b.ar_amount as new_ar_amount,
				b.firstdue_date as new_firstdue,
				b.maturity_date as new_maturity,

                d.months_term as old_months_term,
                d.rebate as old_rebate,
                d.financing_rate as old_financing_rate,
                d.amortization_amount as old_amort,
                d.ar_amount as old_ar_amount,
                d.firstdue_date as old_firstdue,
                d.maturity_date as old_maturity,

                b.lcp_amount,
                b.downpayment_amount,
				b.outstanding_ar_amount,
				b.delivery_ref_no,
                b.invoice_ref_no,
                b.amort_diff,
                b.months_paid,
                b.amort_delay,
                b.adj_rate,
                b.opportunity_cost,
                b.amount_to_be_paid,
                b.amount_to_be_paid_status,
                b.remarks,
                d.co_maker,
                d.invoice_type,
                d.warranty_code,
                d.fsc_series,
                d.reference as si_ref,
                d.discount_downpayment,
                (SELECT 
                        t1.trans_no 
                    FROM debtor_trans t1 
                    WHERE t1.reference = b.invoice_ref_no AND
                    (t1.type = " . ST_SALESINVOICE . " OR t1.type = " . ST_SALESINVOICEREPO . ")
                LIMIT 1) as si_trans_no,
                d.outstanding_ar_amount as old_ar_balance,
                (SELECT 
                        sum(t1.payment_applied) alloc
                    FROM debtor_loan_ledger t1 
                    WHERE t1.trans_no = 
                    (SELECT 
                        t1.trans_no 
                    FROM debtor_trans t1 
                    WHERE t1.reference = b.invoice_ref_no AND
                    (t1.type = " . ST_SALESINVOICE . " OR t1.type = " . ST_SALESINVOICEREPO . ")
                    LIMIT 1) AND
                    (t1.trans_type_to = " . ST_SALESINVOICE . " OR t1.trans_type_to = " . ST_SALESINVOICEREPO . ")
                LIMIT 1) as total_payment,
                d.category_id,
                d.discount_downpayment2,
                d.payment_location,
                (SELECT 
                    t1.plcydtl_code
                    FROM policy_details t1 
                WHERE t1.id = a.payment_terms) as new_payment_terms,
                (SELECT 
                    t1.plcydtl_code
                    FROM policy_details t1 
                WHERE t1.id = d.installmentplcy_id) as old_payment_terms,
                d.invoice_type,
                d.invoice_date,
                (SELECT 
                a.description
                FROM stock_category a
                WHERE a.category_id = d.category_id) as category,
                a.opening_balances,
                (SELECT 
                c.standard_cost
                FROM debtor_trans_details c
                WHERE c.debtor_trans_no = 
                (SELECT 
                t1.trans_no 
                FROM debtor_trans t1 
                WHERE t1.reference = b.invoice_ref_no AND
                (t1.type = " . ST_SALESINVOICE . " OR t1.type = " . ST_SALESINVOICEREPO . ")
                LIMIT 1) AND
                (c.debtor_trans_type = " . ST_SALESINVOICE . " OR c.debtor_trans_type = " . ST_SALESINVOICEREPO . ")) as standard_cost,
                d.profit_margin


			FROM
				debtor_trans a
            INNER JOIN
				debtor_term_modification b
			ON 
				b.trans_no = a.trans_no and a.type = b.type
			INNER JOIN
				debtors_master c
			ON
				c.debtor_no = a.debtor_no
            INNER JOIN
                debtor_loans d
            ON
                d.invoice_ref_no = b.invoice_ref_no
			WHERE
                a.trans_no =" . db_escape($invoice_no) . " AND a.type =" . ST_RESTRUCTURED ;

    $result = db_query($sql, "sales invoice retreival");

    $num = db_num_rows($result);
    if ($num > 1) {
        display_warning("You have duplicate document in database: (type:Sales Invoice CT, number:$invoice_no).");
    } else if ($num == 1) {
        return db_fetch($result);
    } else
        display_warning("You have missing or invalid sales document in database (type:Sales Invoice CT, number:$invoice_no).");
}

/* Added by Ronelle 8/20/2021 Retrieving Sales Invoices CT */
function get_sales_invoices_restructured($trans_no = 0)
{
    $sql = "SELECT 
                a.trans_no,
                d.name,
                b.status,
                c.category_id,
                b.reference,
                a.invoice_ref_no,
                a.term_mod_date,
                a.months_term,
                a.ar_amount,
                a.lcp_amount,
                a.downpayment_amount,
                a.amortization_amount,
                a.outstanding_ar_amount
            FROM
                debtor_term_modification a
            INNER JOIN
                debtor_trans b
            ON
                b.trans_no = a.trans_no and a.type = b.type
            LEFT JOIN
                debtor_loans c
            ON
                c.invoice_ref_no = a.invoice_ref_no
            LEFT JOIN
                debtors_master d
            ON
                d.debtor_no = b.debtor_no
            WHERE
                b.type = " . ST_RESTRUCTURED  . "
            ORDER BY
                a.trans_no DESC";
    return $sql;
}
/* */

