<?php


class SalesReturn
{
    var $trans_type; // invoice, order, quotation, delivery note ...
    var $trans_no = array(); // array (num1=>ver1,..) or 0 for new
    var $so_type = 0;        // for sales order: simple=0 template=1
    var $cart_id;        // used to detect multi-tab edition conflits
    var $line_items;  //array of objects of class line_details

    var $src_docs = array();    // array of arrays(num1=>ver1,...) or 0 for no src
    var $src_date;            // src document date (for info only)

    var $document_date;
    var $due_date;
    var $sales_type; // set to the customer's sales type
    var $sales_type_name; // set to customer's sales type name
    var $tax_included;

    var $customer_currency; // set to the customer's currency
    var $default_discount; // set to the customer's discount %
    var $customer_name;
    var $customer_id;
    var $Branch;
    var $email;

    var $deliver_to;
    var $delivery_address;
    var $phone;

    var $cust_ref;
    var $reference;
    var $Comments;
    var $Location;
    var $location_name;
    var $order_no;         // the original order number

    //new added by progjr on 2-20-2021
    var $document_ref;
    var $warranty_code;
    var $fsc_series;
    var $salesman_id;
    var $salesman;
    var $category_id;
    var $category;

    var $ship_via;
    var $freight_cost = 0;

    var $tax_group_id;
    var $tax_group_name;
    var $tax_group_array = null; // saves db queries
    var $price_factor;     // ditto for price calculations

    var    $pos;            // user assigned POS
    var $cash_account;
    var $account_name;
    var $cash_discount;    // not used as of FA 2.1
    var $dimension_id;
    var $dimension2_id;
    var $payment;
    var $payment_terms; // cached payment terms
    var $credit;
    // prepayment mode:
    var $prepaid = false;    // true for documents issued in prepayment mode
    var $prep_amount = 0;    // prepayment required for SO, invoiced amount for prepaiament invoice
    var $sum_paid;         // sum of all allocated prepayments both to order and related invoices
    var $alloc;         // sum of payments allocated to this document
    var $prepayments = array(); // allocation records for this document
    var $ex_rate;

    var $fixed_asset = false;

    var $dp_amount = 0;    // Added by Ronelle 3/8/2021
    var $amortization = 0;    // Added by Ronelle 3/8/2021
    var $ar_amount = 0;    // Added by Ronelle 3/8/2021
    var $first_due_date;    // Added by Ronelle 3/14/2021
    var $maturity_date;    // Added by Ronelle 3/14/2021
    var $approval_remarks;    // Added by Ronelle 3/25/2021
    var $status;    //	Added by Ronelle 3/25/2021
    var $sum_lcp_amount = 0;    // Added by Ronelle 3/25/2021

    var $months_term = 0;    // Added by Ronelle 3/25/2021
    var $rebate = 0;    // Added by Ronelle 3/25/2021
    var $financing_rate = 0;    // Added by Ronelle 3/25/2021
    var $payment_policy = 0;
    var $months_term_paid;    // Added by Ronelle 4/30/2021
    var $invoice_type = "new";    // Added by Ronelle 5/5/2021
    var $outstanding_ar_amount = 0;
    var $lcp_amount = 0;
    var $payment_location = "Branch"; // Added by Ronelle 5/17/2021

    function __construct()
    {
        /*Constructor function initialises a new purchase order object */
        $this->line_items = array();
        // $this->lines_on_order = $this->pr_no = $this->supplier_id = 0;
    }

    function set_customer($customer_id, $customer_name, $currency, $discount, $payment, $cdiscount=0)
	{
		$this->customer_name = $customer_name;
		$this->customer_id = $customer_id;
		$this->default_discount = $discount;
		$this->customer_currency = $currency;
		$this->payment = $payment;
		$this->payment_terms = get_payment_terms($payment);
		$this->cash_discount = $cdiscount;

		if ($this->payment_terms['cash_sale']) {
			$this->Location = $this->pos['pos_location'];
			$this->location_name = $this->pos['location_name'];
		}
		$this->credit = get_current_cust_credit($customer_id);
	}
}
