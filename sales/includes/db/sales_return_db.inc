<?php

function add_sales_return(
    $trans_no,
    $trans_ref,
    $trans_no_ref,
    $trans_type_ref,
    $sr_item_type,
    $category_id,
    $payment_type,
    $total_payable,
    $total_receivable,
    $total_prev_lcp,
    $total_new_lcp,
    $total_prev_cost,
    $total_new_cost,
    $Comments = ""
) {
    $sql = "INSERT INTO " . TB_PREF . "sales_return_replacement (
		trans_no,
		trans_ref,
        trans_no_ref,
        trans_type_ref,
        sr_item_type,
        category_id,
        payment_type,
        total_payable,
        total_receivable,
        total_prev_lcp,
        total_new_lcp,
        total_prev_cost,
        total_new_cost,
        remarks
		) VALUES (
		$trans_no,
        " . db_escape($trans_ref) . ",
        $trans_no_ref,
        $trans_type_ref,
        " . db_escape($sr_item_type) . ",
        $category_id,
        " . db_escape($payment_type) . ",
        $total_payable,
        $total_receivable,
        $total_prev_lcp,
        $total_new_lcp,
        $total_prev_cost,
        $total_new_cost,
        " . db_escape($Comments) . ")";
    db_query($sql, "The sales_return_replacement record could not be inserted");
}

function add_sales_return_details(
    $trans_details_id,
    $sales_return_replacement_no,
    $returned_stock_id,
    $returned_qty,
    $returned_price,
    $returned_cost,
    $returned_color_code = "",
    $returned_serial_no = "",
    $returned_chassis_no = "",
    $replace_stock_id,
    $replace_qty,
    $replace_price,
    $replace_cost,
    $replace_color_code = "",
    $replace_serial_no = "",
    $replace_chassis_no = ""
) {
    $sql = "INSERT INTO " . TB_PREF . "sales_return_replacement_details (
        trans_details_id,
        sales_return_replacement_no,
        returned_stock_id,
        returned_qty,
        returned_price,
        returned_cost,
        returned_color_code,
        returned_serial_no,
        returned_chassis_no,
        replace_stock_id,
        replace_qty,
        replace_price,
        replace_cost,
        replace_color_code,
        replace_serial_no,
        replace_chassis_no
        ) VALUES (
        $trans_details_id,
        $sales_return_replacement_no,
        " . db_escape($returned_stock_id) . ",
        $returned_qty,
        $returned_price,
        $returned_cost,
        " . db_escape($returned_color_code) . ",
        " . db_escape($returned_serial_no) . ",
        " . db_escape($returned_chassis_no) . ",
        " . db_escape($replace_stock_id) . ",
        $replace_qty,
        $replace_price,
        $replace_cost,
        " . db_escape($replace_color_code) . ",
        " . db_escape($replace_serial_no) . ",
        " . db_escape($replace_chassis_no) . ")";

    $result = db_query($sql, "The sales_return_replacement_details record could not be inserted");

    if ($result) {
        $sql = "UPDATE " . TB_PREF . "debtor_trans_details SET qty_replace = (qty_replace+$replace_qty) WHERE id=$trans_details_id";
        db_query($sql, "The debtors_trans_details record could not be updated the replace qty");
    }
}

function get_sales_return_details($trans_no)
{
    $sql = "SELECT 
        a.*
        FROM " . TB_PREF . "sales_return_replacement_details a
        WHERE a.sales_return_replacement_no=$trans_no";
    return db_query($sql, 'cannot retrieve sales return details');
}

// Added by Prog6 7/07/2021
function get_sales_replacement_details($trans_num)
{
    $sql = "
            SELECT srd.*, sm.reference AS SRnum, sm.tran_date AS Returndate, sr.si_no_ref_ AS Invoice, sr.dr_no_ref_ AS DRnum, sr.total_prev_lcp AS PrevLCP, sr.total_new_lcp AS NewLCP, sr.total_prev_cost AS PrevCost, sr.total_new_cost AS NewCost, sr.total_payable AS Payable, sr.total_receivable AS Receivable , sm.category_id AS category, stock.description AS description
            FROM " . TB_PREF . "sales_return_replacement_details srd
                LEFT JOIN " . TB_PREF . "sales_return_replacement sr ON srd.sales_return_replacement_no = sr.trans_no
                INNER JOIN " . TB_PREF . "stock_moves sm ON srd.sales_return_replacement_no = sm.trans_no AND sm.type = " . ST_SALESRETURN . " AND sm.qty > 0
                LEFT JOIN " . TB_PREF . "stock_master stock ON sm.stock_id = stock.stock_id
               WHERE srd.sales_return_replacement_no = $trans_num ";

    return db_query($sql, 'cannot retrieve sales return details');
}

function write_sales_return_replacement(&$sales_return, $replace_items)
{
    global $Refs;

    $trans_no = $sales_return->trans_no;
    if (is_array($trans_no))
        $trans_no = key($trans_no);

    $ov_amount_ = 0;
    if ($sales_return->total_payable > 0)
        $ov_amount_ = $sales_return->total_payable;

    if ($sales_return->total_receivable > 0)
        $ov_amount_ = $sales_return->total_receivable;

    $date_ = $sales_return->document_date;
    $charge_shipping = $sales_return->freight_cost;

    $company_data = get_company_prefs();

    $branch_data = get_branch_accounts($sales_return->Branch);

    $customer = get_customer($sales_return->customer_id);
    add_new_exchange_rate($customer['curr_code'], $date_, $sales_return->ex_rate);

    begin_transaction();

    hook_db_prewrite($sales_return, ST_SALESRETURN);

    set_global_connection();
    $sales_return_no = write_customer_trans(
        ST_SALESRETURN,
        $trans_no,
        $sales_return->customer_id,
        $sales_return->Branch,
        $date_,
        $sales_return->reference,
        $ov_amount_,
        0,
        0,
        $sales_return->freight_cost,
        0,
        0,
        0,
        1,
        $sales_return->due_date,
        0,
        0,
        $sales_return->dimension_id,
        $sales_return->dimension2_id,
        $sales_return->payment,
        0,
        $sales_return->prep_amount,
        0
    );

    add_sales_return(
        $sales_return_no,
        $sales_return->si_no_ref, // SI Reference
        $sales_return->trans_no_ref,
        $sales_return->trans_type_ref,
        $sales_return->repo_type,
        $sales_return->category_id,
        $sales_return->payment_policy != 0 ? "installment" : "cash",
        $sales_return->total_payable,
        $sales_return->total_receivable,
        $sales_return->total_prev_lcp,
        $sales_return->total_new_lcp,
        $sales_return->total_prev_cost,
        $sales_return->total_new_cost,
        $sales_return->Comments
    );
    $total = 0;
    //   Added by Albert 03/26/2022
         $si_trans_no = get_SI_by_reference($sales_return->si_no_ref);
            
    foreach ($sales_return->line_items as $line_no => $sales_return_line) {
        if ($sales_return_line->qty_dispatched > 0) {
            write_customer_trans_detail_item(
                ST_SALESRETURN,
                $sales_return_no,
                $sales_return_line->stock_id,
                $sales_return_line->item_description,
                $sales_return_line->qty_dispatched,
                $sales_return_line->price,
                0,
                0,
                $sales_return_line->standard_cost,
                $sales_return_line->src_id,
                $trans_no ? $sales_return_line->id : 0,
                $sales_return_line->lot_no,
                $sales_return_line->chasis_no,
                $sales_return_line->color_desc,
                "Regular",
                $sales_return_line->discount1,
                $sales_return_line->discount2,
                0,
                0
            );

            add_return_units(
                $sales_return_no,
                ST_SALESRETURN,
                db_insert_id(),
                $sales_return->trans_type_ref,
                $sales_return_line->id,
                $sales_return_line->stock_id,
                $sales_return_line->qty_dispatched,
                $si_trans_no['trans_no']

            );

            add_stock_move(
                ST_SALESRETURN,
                $sales_return_line->stock_id,
                $sales_return_no,
                $sales_return->Location,
                $date_,
                $sales_return->reference,
                $sales_return_line->qty_dispatched,
                $sales_return_line->standard_cost,
                $sales_return_line->price,
                $sales_return_line->lot_no,
                $sales_return_line->chasis_no,
                $sales_return->category_id,
                $sales_return_line->color_desc,
                ST_SALESRETURN,
                $sales_return_no,
                $sales_return->repo_type
            );
        }
    }
    $total_cost = 0;
    foreach ($replace_items->line_items as $line_no => $replace_items_line) {
        if ($replace_items_line->qty_dispatched > 0) {
            write_customer_trans_detail_item(
                ST_SALESRETURN,
                $sales_return_no,
                $replace_items_line->stock_id,
                $replace_items_line->item_description,
                $replace_items_line->qty_dispatched,
                $replace_items_line->price,
                0,
                0,
                $replace_items_line->standard_cost,
                $replace_items_line->src_id,
                $trans_no ? $replace_items_line->id : 0,
                $replace_items_line->lot_no,
                $replace_items_line->chasis_no,
                $replace_items_line->color_desc,
                "Regular",
                $replace_items_line->discount1,
                $replace_items_line->discount2,
                0,
                0
            );

            add_stock_move(
                ST_SALESRETURN,
                $replace_items_line->stock_id,
                $sales_return_no,
                $sales_return->Location,
                $date_,
                $sales_return->reference,
                (-1 * abs($replace_items_line->qty_dispatched)),
                $replace_items_line->standard_cost,
                $replace_items_line->price,
                $replace_items_line->lot_no,
                $replace_items_line->chasis_no,
                $sales_return->category_id,
                $replace_items_line->color_desc,
                //Modified by spyrax10 17 Feb 2022
                $replace_items_line->transtype_out,
                $replace_items_line->transno_out,
                //
                $sales_return->repo_type
            );
        }
    }

    $category_gl_code = get_gl_code_from_category($sales_return->category_id);
    $sales_account = $category_gl_code['dflt_sales_act'];
    if ($sales_return->payment_policy != 0) {
        if ($sales_return->months_term >= 1 && $sales_return->months_term <= 3 && !check_employee_customer($sales_return->customer_id)) {
            $sales_account = $category_gl_code['dflt_regular_sales_act'];
        }
        if ($sales_return->months_term >= 4 && !check_employee_customer($sales_return->customer_id)) {
            $sales_account = $category_gl_code['dflt_installment_sales_act'];
        }   
        if (check_employee_customer($sales_return->customer_id)) {
            $sales_account = $company_data['isa_employee'];
        }
        //Added by spyrax10 28 Feb 2022
        if ($sales_return->repo_type == 'repo') {
            $sales_account = $company_data['isa_employee'];
        }
        //    
    }
    //Modified by spyrax10
    $row = get_SI_by_reference($sales_return->si_no_ref);
    $si_year = get_year($row['tran_date']);
    $sr_year = get_year($date_);
    $cur_year = get_year(Today());
    
    //date("Y", strtotime($date_)) < date("Y")

    if ($si_year < $cur_year && $sr_year == $cur_year) {
        $sales_account = $sales_account = $company_data['isa_employee'];
    }
    //

    if ($ov_amount_ > 0) {
        if ($sales_return->total_payable > 0) {

            if ($sales_return->line_items[0]->stock_id == $replace_items->line_items[0]->stock_id) {
                // Debit AP Customer
                $total += add_gl_trans_customer(
                    ST_SALESRETURN,
                    $sales_return_no,
                    $date_,
                    $sales_account,
                    0,
                    0,
                    $sales_return->total_payable,
                    $sales_return->customer_id,
                    "The total debtor GL posting could not be inserted"
                );
                // Credit Misc. Income
                $total += add_gl_trans_customer(
                    ST_SALESRETURN,
                    $sales_return_no,
                    $date_,
                    $company_data["misc_income_act"],
                    0,
                    0,
                    (-1 * abs($sales_return->total_payable)),
                    $sales_return->customer_id,
                    "The total debtor GL posting could not be inserted"
                );
            } else {
                // Debit Cash Sales
                $total += add_gl_trans_customer(
                    ST_SALESRETURN,
                    $sales_return_no,
                    $date_,
                    $sales_account,
                    0,
                    0,
                    $sales_return->total_payable,
                    $sales_return->customer_id,
                    "The total debtor GL posting could not be inserted"
                );
                // Credit AP Customer
                $total += add_gl_trans_customer(
                    ST_SALESRETURN,
                    $sales_return_no,
                    $date_,
                    $company_data["ap_customer_account"],
                    0,
                    0,
                    (-1 * abs($sales_return->total_payable)),
                    $sales_return->customer_id,
                    "The total debtor GL posting could not be inserted"
                );
            }
        }

        if ($sales_return->total_receivable > 0) {
            if ($sales_return->line_items[0]->stock_id == $replace_items->line_items[0]->stock_id) {
                // Debit Misc Expense
                $total += add_gl_trans_customer(
                    ST_SALESRETURN,
                    $sales_return_no,
                    $date_,
                    $company_data["misc_expense_act"],
                    0,
                    0,
                    $sales_return->total_receivable,
                    $sales_return->customer_id,
                    "The total debtor GL posting could not be inserted"
                );
                // Credit AR Customer
                $total += add_gl_trans_customer(
                    ST_SALESRETURN,
                    $sales_return_no,
                    $date_,
                    $sales_account,
                    0,
                    0,
                    (-1 * abs($sales_return->total_receivable)),
                    $sales_return->customer_id,
                    "The total debtor GL posting could not be inserted"
                );
            } else {
                // Debit AR Customer
                $total += add_gl_trans_customer(
                    ST_SALESRETURN,
                    $sales_return_no,
                    $date_,
                    $company_data["ar_customer_account"],
                    0,
                    0,
                    $sales_return->total_receivable,
                    $sales_return->customer_id,
                    "The total debtor GL posting could not be inserted"
                );
                // Credit Cash Sales
                $total += add_gl_trans_customer(
                    ST_SALESRETURN,
                    $sales_return_no,
                    $date_,
                    $sales_account,
                    0,
                    0,
                    (-1 * abs($sales_return->total_receivable)),
                    $sales_return->customer_id,
                    "The total debtor GL posting could not be inserted"
                );
            }
        }
    }
    /* Returned Unit */
    foreach ($sales_return->line_items as $line_no => $sales_return_line) {
        if ($replace_items_line->qty_dispatched > 0) {
            $stock_gl_code = get_stock_gl_code($sales_return_line->stock_id);
            $dim = ($sales_return->dimension_id != $customer['dimension_id'] ? $sales_return->dimension_id : ($customer['dimension_id'] != 0 ? $customer["dimension_id"] : $stock_gl_code["dimension_id"]));
            $dim2 = ($sales_return->dimension2_id != $customer['dimension2_id'] ? $sales_return->dimension2_id : ($customer['dimension2_id'] != 0 ? $customer["dimension2_id"] : $stock_gl_code["dimension2_id"]));

            if (is_inventory_item($sales_return_line->stock_id)) {

                /*now the stock entry*/
                add_gl_trans_std_cost(
                    ST_SALESRETURN,
                    $sales_return_no,
                    $date_,
                    $stock_gl_code["inventory_account"],
                    0,
                    0,
                    $sales_return_line->stock_id,
                    $sales_return_line->standard_cost * $sales_return_line->qty_dispatched,
                    PT_CUSTOMER,
                    $sales_return->customer_id,
                    "The stock side of the cost of sales GL posting could not be inserted"
                );

                /*first the cost of sales entry*/
                add_gl_trans_std_cost(
                    ST_SALESRETURN,
                    $sales_return_no,
                    $date_,
                    $stock_gl_code["cogs_account"],
                    $dim,
                    $dim2,
                    $sales_return_line->stock_id,
                    (-$sales_return_line->standard_cost * $sales_return_line->qty_dispatched),
                    PT_CUSTOMER,
                    $sales_return->customer_id,
                    "The cost of sales GL posting could not be inserted"
                );
            }
        }
    }
    /* */
    /* Replace Unit */
    foreach ($replace_items->line_items as $line_no => $replace_items_line) {
        if ($replace_items_line->qty_dispatched > 0) {
            $stock_gl_code = get_stock_gl_code($replace_items_line->stock_id);
            $dim = ($sales_return->dimension_id != $customer['dimension_id'] ?
                $sales_return->dimension_id : ($customer['dimension_id'] != 0 ? $customer["dimension_id"] : $stock_gl_code["dimension_id"]));
            $dim2 = ($sales_return->dimension2_id != $customer['dimension2_id'] ?
                $sales_return->dimension2_id : ($customer['dimension2_id'] != 0 ? $customer["dimension2_id"] : $stock_gl_code["dimension2_id"]));

            if (is_inventory_item($replace_items_line->stock_id)) {
                /*first the cost of sales entry*/
                add_gl_trans_std_cost(
                    ST_SALESRETURN,
                    $sales_return_no,
                    $date_,
                    $stock_gl_code["cogs_account"],
                    $dim,
                    $dim2,
                    $replace_items_line->stock_id,
                    $replace_items_line->standard_cost * $replace_items_line->qty_dispatched,
                    PT_CUSTOMER,
                    $sales_return->customer_id,
                    "The cost of sales GL posting could not be inserted"
                );
                /*now the stock entry*/
                add_gl_trans_std_cost(
                    ST_SALESRETURN,
                    $sales_return_no,
                    $date_,
                    $stock_gl_code["inventory_account"],
                    0,
                    0,
                    $replace_items_line->stock_id,
                    (-$replace_items_line->standard_cost * $replace_items_line->qty_dispatched),
                    PT_CUSTOMER,
                    $sales_return->customer_id,
                    "The stock side of the cost of sales GL posting could not be inserted"
                );
            }
        }
    }
    /* */
    add_gl_balance(ST_SALESINVOICE, $sales_return_no, $date_, -$total, PT_CUSTOMER, $sales_return->customer_id);

    $Refs->save(ST_SALESRETURN, $sales_return_no, $sales_return->reference);

    add_audit_trail(ST_SALESRETURN, $sales_return_no, $date_);
    hook_db_postwrite($sales_return, ST_SALESRETURN);
    commit_transaction();

    return $sales_return_no;
}

//--------------------------------------------------------------------------------------------------

function void_sales_return($type, $type_no)
{
    begin_transaction();

    hook_db_prevoid($type, $type_no);
    void_gl_trans($type, $type_no, true);

    // clear details after they've been reversed in the sales order
    void_customer_trans_details($type, $type_no);

    void_stock_move($type, $type_no); // in case of credit note with return

    void_trans_tax_details($type, $type_no);

    void_cust_allocations($type, $type_no);

    // do this last because other voidings can depend on it - especially voiding
    // DO NOT MOVE THIS ABOVE VOIDING or we can end up with trans with alloc < 0
    void_customer_trans($type, $type_no);

    commit_transaction();
}

/* Added by Ronelle 11/11/2021 */
function add_return_units(
    $debtor_trans_no,
    $debtor_trans_type,
    $debtor_trans_details_id,
    $from_debtor_trans_type,
    $from_debtor_trans_details_id,
    $stock_id,
    $quantity,
    $si_trans_no
) {
    $sql = "INSERT INTO " . TB_PREF . "returned_units (
        debtor_trans_no,
        debtor_trans_type,
        debtor_trans_details_id,
        from_debtor_trans_type,
        from_debtor_trans_details_id,
        stock_id,
        quantity,
        si_trans_no
        ) VALUES (
        " . db_escape($debtor_trans_no) . ",
        " . db_escape($debtor_trans_type) . ",
        " . db_escape($debtor_trans_details_id) . ",
        " . db_escape($from_debtor_trans_type) . ",
        " . db_escape($from_debtor_trans_details_id) . ",
        " . db_escape($stock_id) . ",
        " . db_escape($quantity) . ",
        " . db_escape($si_trans_no) . ")";

    db_query($sql, "The debtor transaction detail could not be written");
}

function get_total_return_unit($id, $trans_type)
{
    $sql = "SELECT SUM(quantity) FROM returned_units WHERE 
        from_debtor_trans_details_id = $id
        and from_debtor_trans_type = $trans_type";
    $result = db_query($sql, "retrieve return units failed");

    $myrow = db_fetch_row($result);

    $return_qty =  $myrow[0];
    return $return_qty ? $return_qty : 0;
}

function get_sr_return_items($trans_no)
{
    $sql = "SELECT
                b.id,
                b.stock_id,
                b.description,
                b.color_code,
                b.quantity,
                b.unit_price,
                b.standard_cost,
                b.lot_no,
                b.chassis_no
            FROM
                returned_units a
            INNER JOIN
                debtor_trans_details b
            ON
                b.id = a.debtor_trans_details_id
            WHERE
                a.debtor_trans_no =" . db_escape($trans_no);
    return db_query($sql, 'cannot retrieve sr returned details');
}

function get_sr_replace_items($trans_no)
{
    $sql = "SELECT
                a.id,
                a.stock_id,
                a.description,
                a.color_code,
                a.quantity,
                a.unit_price,
                a.standard_cost,
                a.lot_no,
                a.chassis_no
            FROM
                debtor_trans_details a
            LEFT JOIN
                returned_units b
            ON
                b.debtor_trans_details_id = a.id
            WHERE
                a.debtor_trans_no = $trans_no
            AND
                a.debtor_trans_type = 55
            AND
                b.debtor_trans_details_id is null";
    return db_query($sql, 'cannot retrieve sr returned details');
}
/* */
/* Added by albert */

function get_returned_repo_installment_price($stockID, $lot_no)
{

    $sql = "SELECT MAX(A.price)  FROM " . TB_PREF . " stock_moves A 
			WHERE type=80 and A.stock_id = " . db_escape($stockID) . "AND A.lot_no = " . db_escape($lot_no);

    $result = db_query($sql, "The LCP for the selected branch and item could not be retrieved");
    $row = db_fetch_row($result);
    $price = $row[0];
    return $price;
}
function get_serial_repo_no($lot_no)
{

    $sql = "SELECT A.lot_no FROM " . TB_PREF . "stock_moves A WHERE A.type = 80 AND A.lot_no =" . db_escape($lot_no);

    $sql .= " AND A.lot_no =" . db_escape($lot_no);
    $sql .= " GROUP BY A.lot_no, trans_id";
    $sql .= " ORDER BY trans_id DESC LIMIT 1";

    $result = db_query($sql, "Can not find delivery notes");
    $row = db_fetch_row($result);

    return $row[0];
}

/*---end by Albert */


//Added by spyrax10
function get_repo_by_reference($reference = '') {

	$sql = "SELECT A.*
		FROM ".TB_PREF."repo_accounts A
		WHERE A.reference_no = " .db_escape($reference);

	$result = db_query($sql, "Cant get repo account details!");
    return db_fetch($result);
}

function get_DT_details_by_serial($trans_no, $trans_type, $stock_id = '', $lot_no = '', $chassis_no = '') {
    
    $sql = "SELECT A.*, SUM(A.quantity) AS qoh
        FROM ".TB_PREF."debtor_trans_details A
        WHERE A.debtor_trans_no = " .db_escape($trans_no) . "
            AND A.debtor_trans_type = " .db_escape($trans_type) . "
            AND A.stock_id = " .db_escape($stock_id);
    
    if ($lot_no != '') {
        $sql .= " AND A.lot_no = " .db_escape($lot_no);
    }

    if ($chassis_no != '') {
        $sql .= " AND A.chassis_no = " .db_escape($chassis_no);
    }

    $result = db_query($sql, "Cant get debtor trans details!");
    return db_fetch($result);
}
//
