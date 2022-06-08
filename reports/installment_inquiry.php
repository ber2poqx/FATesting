<?php
$path_to_root = '..';
if (!isset($path_to_root) || isset($_GET['path_to_root']) || isset($_POST['path_to_root']))
		die(_("Restricted access"));
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/lending/includes/db/ar_installment_db.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/lending/includes/db/customers_payment_db.inc");

include_once($path_to_root . "/includes/page/header.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

function get_ar_installmentss($trans_no, $type, $branch_code)
{
	//modify by jr for other/inter branch customer on 09-03-2021
	$db_coy = Get_db_coy($branch_code);
    set_global_connection($db_coy);

    if ($type == ST_SALESINVOICE) {
    	
    	$sql = "SELECT A.*, A.status AS STATUS, B.invoice_date AS invoice_date, B.invoice_type AS invoice_type, B.rebate AS rebate, 
			B.financing_rate AS financing_rate, B.firstdue_date AS firstdue_date, B.maturity_date AS maturity_date, 
			B.outstanding_ar_amount AS outstanding_ar_amount, B.ar_amount AS ar_amount, B.lcp_amount AS lcp_amount, 
			B.downpayment_amount AS downpayment_amount, B.amortization_amount AS amortization_amount, 
			B.total_amount AS total_amount, B.category_id AS category_id, B.payment_status AS payment_status, 
			B.warranty_code AS warranty_code, B.months_term AS monthterms, C.memo_,  D.*, E.description, 
			F.stock_id, F.description AS ITEMDES, F.lot_no, F.chassis_no, F.quantity, 
			F.unit_price, F.standard_cost, F.discount1 AS Discount,
		    F.discount2 AS OtherDiscount,
		    F.unit_price * F.quantity AS linetotal, G.description AS COLOR, H.salesman_id, I.salesman_name,
			CASE WHEN B.months_term = 0 THEN 'CASH'
			ELSE 'INSTALLMENT' END AS Payment_type
			FROM ".TB_PREF."debtor_trans A 
			INNER JOIN ".TB_PREF."debtor_loans B ON B.trans_no = A.trans_no AND B.debtor_no = A.debtor_no
			LEFT JOIN ".TB_PREF."comments C ON C.id = A.trans_no AND C.type = A.type
			LEFT JOIN ".TB_PREF."debtors_master D ON D.debtor_no = A.debtor_no
			LEFT JOIN ".TB_PREF."stock_category E ON E.category_id = B.category_id
			LEFT JOIN ".TB_PREF."debtor_trans_details F ON F.debtor_trans_no = A.trans_no AND F.debtor_trans_type = A.type
			LEFT JOIN ".TB_PREF."item_codes G ON G.item_code = F.color_code
			LEFT JOIN ".TB_PREF."sales_orders H ON A.order_ = H.order_no
			LEFT JOIN ".TB_PREF."salesman I ON H.salesman_id = I.salesman_code
			WHERE (A.type = ".ST_SALESINVOICE.") 
			AND A.trans_no = '" . $trans_no . "'
			GROUP BY A.reference, A.trans_no, F.stock_id
			ORDER BY A.tran_date";
		return db_query($sql, "No transactions were returned");
    }

    if ($type == ST_ARINVCINSTLITM) {
    	
    	$sql = "SELECT A.*, A.status AS STATUS, B.invoice_date AS invoice_date, B.invoice_type AS invoice_type, B.rebate AS rebate, 
			B.financing_rate AS financing_rate, B.firstdue_date AS firstdue_date, B.maturity_date AS maturity_date, 
			B.outstanding_ar_amount AS outstanding_ar_amount, B.ar_amount AS ar_amount, B.lcp_amount AS lcp_amount, 
			B.downpayment_amount AS downpayment_amount, B.amortization_amount AS amortization_amount, 
			B.total_amount AS total_amount, B.category_id AS category_id, B.payment_status AS payment_status, 
			B.warranty_code AS warranty_code, B.months_term AS monthterms, C.memo_,  D.*, E.description, 
			F.stock_id, F.description AS ITEMDES, F.lot_no, F.chassis_no, F.quantity, 
			F.unit_price, F.standard_cost, F.discount1 AS Discount,
		    F.discount2 AS OtherDiscount,
		    F.unit_price * F.quantity AS linetotal, G.description AS COLOR, H.salesman_id, I.salesman_name,
			CASE WHEN B.months_term = 0 THEN 'CASH'
			ELSE 'INSTALLMENT' END AS Payment_type
			FROM ".TB_PREF."debtor_trans A 
			INNER JOIN ".TB_PREF."debtor_loans B ON B.trans_no = A.trans_no AND B.debtor_no = A.debtor_no
			LEFT JOIN ".TB_PREF."comments C ON C.id = A.trans_no AND C.type = A.type
			LEFT JOIN ".TB_PREF."debtors_master D ON D.debtor_no = A.debtor_no
			LEFT JOIN ".TB_PREF."stock_category E ON E.category_id = B.category_id
			LEFT JOIN ".TB_PREF."debtor_trans_details F ON F.debtor_trans_no = A.trans_no AND F.debtor_trans_type = A.type
			LEFT JOIN ".TB_PREF."item_codes G ON G.item_code = F.color_code
			LEFT JOIN ".TB_PREF."sales_orders H ON A.order_ = H.order_no
			LEFT JOIN ".TB_PREF."salesman I ON H.salesman_id = I.salesman_code
			WHERE (A.type = ".ST_ARINVCINSTLITM.") 
			AND A.trans_no = '" . $trans_no . "'
			GROUP BY A.reference, A.trans_no, F.stock_id
			ORDER BY A.tran_date";
		return db_query($sql, "No transactions were returned");
    }

    if ($type == ST_SALESINVOICEREPO) {
    	
    	$sql = "SELECT A.*, A.status AS STATUS, B.invoice_date AS invoice_date, B.invoice_type AS invoice_type, B.rebate AS rebate, 
			B.financing_rate AS financing_rate, B.firstdue_date AS firstdue_date, B.maturity_date AS maturity_date, 
			B.outstanding_ar_amount AS outstanding_ar_amount, B.ar_amount AS ar_amount, B.lcp_amount AS lcp_amount, 
			B.downpayment_amount AS downpayment_amount, B.amortization_amount AS amortization_amount, 
			B.total_amount AS total_amount, B.category_id AS category_id, B.payment_status AS payment_status, 
			B.warranty_code AS warranty_code, B.months_term AS monthterms, C.memo_,  D.*, E.description, 
			F.stock_id, F.description AS ITEMDES, F.lot_no, F.chassis_no, F.quantity, 
			F.unit_price, F.standard_cost, F.discount1 AS Discount,
		    F.discount2 AS OtherDiscount,
		    F.unit_price * F.quantity AS linetotal, G.description AS COLOR, H.salesman_id, I.salesman_name,
			CASE WHEN B.months_term = 0 THEN 'CASH'
			ELSE 'INSTALLMENT' END AS Payment_type
			FROM ".TB_PREF."debtor_trans A 
			INNER JOIN ".TB_PREF."debtor_loans B ON B.trans_no = A.trans_no AND B.debtor_no = A.debtor_no
			LEFT JOIN ".TB_PREF."comments C ON C.id = A.trans_no AND C.type = A.type
			LEFT JOIN ".TB_PREF."debtors_master D ON D.debtor_no = A.debtor_no
			LEFT JOIN ".TB_PREF."stock_category E ON E.category_id = B.category_id
			LEFT JOIN ".TB_PREF."debtor_trans_details F ON F.debtor_trans_no = A.trans_no AND F.debtor_trans_type = A.type
			LEFT JOIN ".TB_PREF."item_codes G ON G.item_code = F.color_code
			LEFT JOIN ".TB_PREF."sales_orders H ON A.order_ = H.order_no
			LEFT JOIN ".TB_PREF."salesman I ON H.salesman_id = I.salesman_code
			WHERE (A.type = ".ST_SALESINVOICEREPO.") 
			AND A.trans_no = '" . $trans_no . "'
			GROUP BY A.reference, A.trans_no, F.stock_id
			ORDER BY A.tran_date";
		return db_query($sql, "No transactions were returned");
    }

    if ($type == ST_SITERMMOD) {

    	$sql = "SELECT A.*, A.status AS STATUS, B.warranty_code AS warranty_code, TM.term_mod_date AS invoice_date, TM.rebate AS rebate, TM.financing_rate AS financing_rate, TM.firstdue_date AS firstdue_date, TM.maturity_date AS maturity_date, TM.outstanding_ar_amount AS outstanding_ar_amount, TM.ar_amount AS ar_amount, TM.lcp_amount AS lcp_amount, TM.downpayment_amount AS downpayment_amount, TM.amortization_amount AS amortization_amount, TM.category_id AS category_id, TM.term_mode_type AS invoice_type, TM.months_term AS monthterms, C.memo_,  D.*, E.description, 
			F.stock_id, F.description AS ITEMDES, F.lot_no, F.chassis_no, F.quantity, 
			F.unit_price, F.standard_cost, F.discount1 AS Discount,
		    F.discount2 AS OtherDiscount,
		    F.unit_price * F.quantity AS linetotal, G.description AS COLOR, H.salesman_id, I.salesman_name,
			CASE WHEN TM.months_term = 0 THEN 'CASH'
			ELSE 'INSTALLMENT' END AS Payment_type
			FROM ".TB_PREF."debtor_trans A 
			INNER JOIN debtor_term_modification TM ON TM.trans_no = A.trans_no AND TM.debtor_no = A.debtor_no 
			INNER JOIN debtor_loans B ON B.debtor_no = TM.debtor_no AND B.invoice_ref_no = TM.invoice_ref_no
			LEFT JOIN ".TB_PREF."comments C ON C.id = A.trans_no AND C.type = A.type
			LEFT JOIN ".TB_PREF."debtors_master D ON D.debtor_no = A.debtor_no
			LEFT JOIN ".TB_PREF."stock_category E ON E.category_id = TM.category_id
			LEFT JOIN ".TB_PREF."debtor_trans_details F ON F.debtor_trans_no = B.trans_no
			LEFT JOIN ".TB_PREF."item_codes G ON G.item_code = F.color_code
			LEFT JOIN ".TB_PREF."sales_orders H ON A.order_ = H.order_no
			LEFT JOIN ".TB_PREF."salesman I ON H.salesman_id = I.salesman_code
			WHERE (A.type =".ST_SITERMMOD.") 
			AND A.trans_no = '" . $trans_no . "'
			GROUP BY A.reference, A.trans_no
			ORDER BY A.tran_date";
		return db_query($sql, "No transactions were returned");
    }

	set_global_connection();
	return $result;
}

function get_loan_amortization_ledgerss($type, $trans_no, $branch_code)
{
	$db_coy = Get_db_coy($branch_code);
    set_global_connection($db_coy);

	$sql = "SELECT A.id, A.debtor_no, A.trans_no, A.trans_type, A.month_no, A.date_due, A.principal_due, C.reference, B.date_paid,
					B.payment_applied, B.rebate, B.penalty, B.id ledger_id
			FROM ".TB_PREF."debtor_loan_schedule A
				LEFT JOIN ".TB_PREF."debtor_loan_ledger B ON B.loansched_id = A.id
				LEFT JOIN ".TB_PREF."debtor_trans C ON C.trans_no = B.payment_trans_no AND C.type = B.trans_type_from
			WHERE A.trans_no = ".db_escape($trans_no)."
				AND A.trans_type = ".db_escape($type)."
			ORDER BY A.month_no ASC, B.loansched_id ASC";

	$result =  db_query($sql, "could not get amortization ledger because ");

	set_global_connection();
	return $result;
}

function get_ar_balances($trans_no, $trans_type, $branch_code)
{

	$db_coy = Get_db_coy($branch_code);
    set_global_connection($db_coy);

	$sql = "SELECT (A.ov_amount - A.alloc) amount 
			FROM ".TB_PREF."debtor_trans A 
			WHERE A.trans_no = ".db_escape($trans_no)." 
				AND A.type = ".db_escape($trans_type);

	$result = db_query($sql, "could not check ar balance");
	$row = db_fetch($result);

	set_global_connection();
	return $row[0];
}

?>

<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<title>Installment Inquiry</title>
<style type="text/css">
	.main{
		width: 9in;
		height: 11in;
		background: #fff;
		margin: 0 auto;
	}
	body{
		width: 100%;
		padding: 20px 0px;
		height: 100%;
		background: #eee;
		margin: 0px;
		font-size: 19px;
	}
	
	*{
		font-family: century gothic;
		line-height: 1em;
	}
	.right{
		float: right;
	}
	
	.w-50{
		width: 50%;
	}
	.w-70{
		width: 70%;
	}
	.w-30{
		width: 30%;
	}
	p{
		margin: 0px;
		padding: 0px;
		margin-bottom: 5px;
	}
	.text-right{
		text-align: right;
	}
	.text-center{
		text-align: center;
	}
	.top_bordered td{
		border-top: 2px dashed #000;
		font-size: 12px;
	}
	.bot_bordered td{
		border-bottom: 2px dashed #000;
	}

	.Prepared {
		margin-top: 10px;
	}

	.Merchandise {
		text-align: center;
		margin-top: 20px;
		font-size: 15px;
	}

	.items {
		margin-top: 10px;
		text-align: center;
		font-size: 15px;
	}


	@page  {
		margin: 0;
		padding: 0px 25px;
	}
	@media  print {
	  html, body {

		width: 9in;
		height: 11in;
		padding-left: 7px;
		padding-right: 6px;
	
		}
		.noprint {
          visibility: hidden;
       }
       .noprintth {
          display:none;
       }
	  /* ... the rest of the rules ... */
	}
	.underline_input{
	    border: 0px;
	    border-bottom: 1px solid;
	    text-align: left;
	    width:100px;
	    font-size: 10px;
	    font-family: century gothic;
		font-weight: bold;

	}
	/*
		CSS FOR TABLES
	*/
	.left{
		float: center;
		width: 10%;
  		padding: 0px;
	}
	/* Clearfix (clear floats) */
	.row::after {
	  content: "";
	  clear: both;
	  display: table;
	}
	table {
	  border-collapse: collapse;
	  border-spacing: 0;
	  width: 50%;
	  border: 2px solid black;
	}
	td {
	  /*text-align: center;*/
	  padding: 5px;
	}
	.text-left {
		font-size: 12px;
		font-weight: bold;
	}
	.text-design {
		font-size: 68%;
	}
	.text-sample {
		font-size: 90%;
		font-weight: bold;
	}
	.companyDes {
		font-size: 25px;
	}

	.Branchcompany {
		font-size: 15px;	
	}
	.CompanyAdd {
		font-size: 75%;
	}
	.datatable {
		font-size: 11px;
		font-weight: bold;
	}
	.foot {
		text-align: center;
		margin-right: 70px;
		font-family: monospace;
	}
	.footer_names{
	    border: 0px solid black;
	    text-align: left;
		padding-left: 10px;
		padding-right: 10px;
	    font-size: 11px;
		font-family: century gothic;		
	}
	#header{
	   font-size: 15px; width: 100%; float: left; border: 0px solid black;
	}
	
	#header td{
	   padding:2px;
	}
	#footer{
		font-size: 10px;			
		width: 100%;
		border: 0px solid;
	}
	.noprint{
    	font-family: Arial;
    	background-color:#0a0a23;
    	color: #fff;
	    border-radius:10px;
	    min-height:30px; 
    	min-width: 120px;
	}
	.noprint:hover {
      background-color:red;
      transition: 0.7s;
  	}
  	button:after {
        content:"Export To Excel";
    	background-color:#0a0a23;
    	color: #fff;
	    border-radius:10px;
	    min-height:30px; 
    	min-width: 120px;
    }
</style>
</head>

<?php
	
	$trans_no = $_GET['trans_no'];
	$type = $_GET['trans_type'];
	
	if(isset($_GET['branch_code'])){
		$branch_code = $_GET['branch_code'];	
	}else{
		$branch_code = $db_connections[user_company()]["branch_code"];
	}

	$result = get_ar_installmentss($trans_no, $type, $branch_code);
				
	if (db_num_rows($result) > 0)
	{
		$myrow=db_fetch($result);

		$Dmonth = $myrow['monthterms'];
	    if ($Dmonth == 0){
			$month = 'Month';
	    }
		else if ($Dmonth == 1) {
			$month = 'Month';
		} else {
			$month = 'Months';
		} 

		$reference = $myrow["reference"];
		$name = $myrow["name"];
		$status = $myrow["STATUS"];
		$invoice_date = date('m/d/Y', strtotime($myrow["invoice_date"]));
		$maturity_date = date('m/d/Y', strtotime($myrow["maturity_date"]));
		$firstdue_date = date('m/d/Y', strtotime($myrow["firstdue_date"]));
		$invoice_type = $myrow["invoice_type"];
		$Payment_type = $myrow["Payment_type"];
		$trans_no = $myrow["trans_no"];
		$warranty_code = $myrow["warranty_code"];
		$debtor_no = $myrow["debtor_no"];
		$type = $myrow["type"];
		$salesman_name = $myrow["salesman_name"];

		$months_term = $myrow["monthterms"];
		$rebate = price_format($myrow["rebate"]);
		$downpayment_amount = price_format($myrow["downpayment_amount"]);
		$total_amount = price_format($myrow["total_amount"]);
		$ar_amount = price_format($myrow["ar_amount"]);
		$discount_downpayment = price_format($myrow["discount_downpayment"]);
		$amortization_amount = price_format($myrow["amortization_amount"]);
		$lcp_amount = price_format($myrow["lcp_amount"]);

		$balance_amount = price_format(get_ar_balances($myrow["trans_no"], $myrow["type"], $branch_code));
	}
?>

<?php
	$brchcode = $db_connections[user_company()]["branch_code"];
	//echo '({"branchcode":"'.$brcode.'"})';
			
	$compcode = $db_connections[user_company()]["name"];
	//$brscode = get_company_pref("name")

	$comadd =  $db_connections[user_company()]["postal_address"];
	$comadd = get_company_pref("postal_address")
?>
<body>
	<div class="main printable" style="">

		<div style="width: 100%; text-align: center;padding-top: 0.45in;float: left;">
			<h4 style="margin: 0px">
				<div class="companyDes">
					<p><b>Du Ek Sam, Inc.</b></p>
				</div>

				<div class="Branchcompany">
					<p><b><?php  echo $compcode?> - <?php  echo $brchcode?></b></p>
				</div>

				<div class="CompanyAdd">
					<p><b><?php echo $comadd?></b></p>
				</div>

				<div class="Merchandise">
					<label>Installment Inquiry Form - Amortization Ledger - <?php  echo $brchcode?></label>
				</div>
			</h4>
		</div>
		
		
		<div class="row">
			<div class="left" style="width: 100%; padding: 0px; float: left; margin-top: 20px;">			
				<table style="width: 100%; float: left;" cellspacing="0" cellpadding="0">
					<tbody>		
						<tr class="text-left">
							<td style= "border: 1px solid; width: 15%;">Sales Invoice</td><td style= "border: 1px solid; width: 20%; color: black;"><?php echo $reference?></td>
							<td style= "border: 1px solid; width: 15%;">Status</td><td style= "border: 1px solid; width: 20%; 
							color: black;"><?php echo $status?></td>
							<td style= "border: 1px solid; width: 15%;">Invoice Date</td><td style= "border: 1px solid; width: 20%; 
							color: black;"><?php echo $invoice_date?></td>
						</tr>
						<tr class="text-left">
							<td style= "border: 1px solid;">Customer Name</td><td style= "border: 1px solid; color: black;"><?php echo $name?></td>
							<td style= "border: 1px solid;">Payment Type</td><td style= "border: 1px solid; color: black;"><?php echo $Payment_type?></td>
							<td style= "border: 1px solid;">First Due Date</td><td style= "border: 1px solid; color: black;"><?php echo $firstdue_date?></td>
						</tr>
						<tr class="text-left">
							<td style= "border: 1px solid;">WRC/EW Code</td><td style= "border: 1px solid; color: black;"><?php echo $warranty_code?></td>
							<td style= "border: 1px solid;">Invoice Type</td><td style= "border: 1px solid; color: black;"><?php echo $invoice_type?></td>
							<td style= "border: 1px solid;">Maturity Date</td><td style= "border: 1px solid; color: black;"><?php echo $maturity_date?></td>
						</tr>
				    </tbody>					
				</table>
			</div>

			<div class="left" style="width: 100%; padding: 0px; float: left; margin-top: 10px;">			
				<table style="width: 100%; float: left;" cellspacing="0" cellpadding="0">
					<tbody>		
						<tr class="text-left">
							<td style= "border: 1px solid; width: 15%;">Rebate</td><td style= "border: 1px solid; width: 20%; 
							color: black;"><?php echo $rebate?></td>
							<td style= "border: 1px solid;">Dp Amount</td><td style= "border: 1px solid; color: black;"><?php echo $downpayment_amount?></td>
							<td style= "border: 1px solid;">LCP</td><td style= "border: 1px solid; color: black;"><?php echo $lcp_amount?></td>		
						</tr>
						<tr class="text-left">
							<td style= "border: 1px solid; width: 15%;">Months Term</td><td style= "border: 1px solid; width: 20%; 
							color: black;"><?php echo $months_term . ' '. $month?></td>
							<td style= "border: 1px solid;">Discount Dp Amount</td><td style= "border: 1px solid; color: black;"><?php echo $discount_downpayment?></td>
							<td style= "border: 1px solid; width: 15%;">Monthly Amortization</td><td style= "border: 1px solid; width: 20%; color: black;"><?php echo $amortization_amount?></td>		
							
						</tr>
						<tr class="text-left">
							<td style= "border: 1px solid;">Sales Person</td><td style= "border: 1px solid; color: black;"><?php echo $salesman_name?></td>
							<td style= "border: 1px solid;">Gross</td><td style= "border: 1px solid; color: black;"><?php echo $ar_amount?></td>	
							<td style= "border: 1px solid;">Balance</td><td style= "border: 1px solid; color: black;"><?php echo $balance_amount?></td>
							<!--#0c5eec-->

						</tr>
				    </tbody>					
				</table>
			</div>

			
			<div class="left" style="width: 100%; padding: 0px; float: left; margin-top: 3px;">			
				<table style="width: 100%; float: left;" cellspacing="0" cellpadding="0">
					<tbody>
						<div class="items">
							<label>Items</label>
						</div>
							<tr class="text-left">							
							<th style= "border: 1px solid;">Item Code</th>
							<th style= "border: 1px solid;">Item Description</th>
							<th style= "border: 1px solid;">Color</th>
							<th style= "border: 1px solid;">Serial/Engine</th>
							<th style= "border: 1px solid;">Chasis</th>					
							<th style= "border: 1px solid;">Quantity</th>
							<th style= "border: 1px solid;">Unit Price</th>	
							<th style= "border: 1px solid;">Discount</th>					
							<th style= "border: 1px solid;">Other</th>					
							<th style= "border: 1px solid;">Line Total</th>					
						</tr>

						<?php
						  $result2 = get_ar_installmentss($trans_no, $type, $branch_code);				
							if (db_num_rows($result) > 0)
							{							
								$total = 0;
								while ($myrow=db_fetch($result2))
								{															
									echo '<tr class="datatable">';
									echo '<td style= "border: 1px solid; color: black;">'.($myrow["stock_id"]).'</td>';
							        echo '<td style= "border: 1px solid; color: black;">'.($myrow["ITEMDES"]).'</td>';
							        echo '<td style= "border: 1px solid; color: black;">'.($myrow["COLOR"]).'</td>';	
							        echo '<td style= "border: 1px solid; color: black;">'.($myrow["lot_no"]).'</td>';
							        echo '<td style= "border: 1px solid; color: black;">'.($myrow["chassis_no"]).'</td>';
							        echo '<td style= "border: 1px solid; color: black;">'.price_format($myrow["quantity"]).'</td>';
							        echo '<td style= "border: 1px solid; color: black;">'.price_format($myrow["unit_price"]).'</td>';
							        echo '<td style= "border: 1px solid; color: black;">'.price_format($myrow["Discount"]).'</td>';
							        echo '<td style= "border: 1px solid; color: black;">'.price_format($myrow["OtherDiscount"]).'</td>';
							        echo '<td style= "border: 1px solid; color: black;">'.price_format($myrow["linetotal"]).'</td>';
									echo '</tr>';
									//#0c5eec
								}								
							}
							else
							display_note(_("There are no line items on this dispatch."), 1, 2);					
						?>
						
					</tbody>
				</table>
			</div>


			<div class="left" style="width: 100%; padding: 0px; float: left; margin-top: 3px;">			
				<table style="width: 100%; float: left;" cellspacing="0" cellpadding="0">
					<tbody>
						<div class="items">
							<label>Amortization Ledger</label>
						</div>
							<tr class="text-left">							
							<th style= "border: 1px solid;">No.</th>
							<th style= "border: 1px solid;">Due Date</th>
							<th style= "border: 1px solid;">Trans No.</th>							
							<th style= "border: 1px solid;">Principal Amortization</th>
							<th style= "border: 1px solid;">Date Paid</th>					
							<th style= "border: 1px solid;">Payment Applied</th>
							<th style= "border: 1px solid;">Rebate</th>
							<th style= "border: 1px solid;">Date Paid</th>					
							<th style= "border: 1px solid;">Penalty</th>					
						</tr>

						<?php
						  $type = $_GET['trans_type'];
						  $trans_no = $_GET['trans_no'];
						 
						  $result3 = get_loan_amortization_ledgerss($type, $trans_no, $branch_code);				
							if (db_num_rows($result) > 0)
							{		

								$totalpayment = 0.0;
								$totalrebate = 0.0;					
								$totalpenalty = 0.0;					
								
								while ($myrow=db_fetch($result3))
								{		
									$Drebate = $myrow["rebate"];
								    if ($Drebate == ''){
										$rebates = '';
								    }
									else {
										$rebates = price_format($myrow["rebate"]);
									} 

									$Dpenalty = $myrow["penalty"];
									if ($Dpenalty == ''){
										$penaltys = '';
								    }
									else {
										$penaltys = price_format($myrow["penalty"]);
									} 

									$Dpayment = $myrow["payment_applied"];
									if ($Dpayment == ''){
										$paymentsss = '';
								    }
									else {
										$paymentsss = price_format($myrow["payment_applied"]);
									}


									$payment_date = $myrow["payment_applied"];
									if ($payment_date == 0) {
										$pay_due_date = '';
									} else {
										$pay_due_date = $myrow["date_paid"];
									}

									$penalty_date = $myrow["penalty"];
									if ($penalty_date == 0) {
										$penalty_due_date = '';
									} else {
										$penalty_due_date = $myrow["date_paid"];
									}


									echo '<tr class="datatable">';
									echo '<td align=center style= "border: 1px solid;">'.($myrow["month_no"]).'</td>';
							        echo '<td align=center style= "border: 1px solid;">'.($myrow["date_due"]).'</td>';
							        echo '<td align=center style= "border: 1px solid;">'.($myrow["reference"]).'</td>';
							        echo '<td align=center style= "border: 1px solid;">'.price_format($myrow["principal_due"]).'</td>';
							        echo '<td align=center style= "border: 1px solid;">'.$pay_due_date.'</td>';			     	      							        	
							        echo '<td align=center style= "border: 1px solid;">'.$paymentsss.'</td>';
							        echo '<td align=center style= "border: 1px solid;">'.$rebates.'</td>';
							        echo '<td align=center style= "border: 1px solid;">'.$penalty_due_date.'</td>';	 
							        echo '<td align=center style= "border: 1px solid; color: black;">'.$penaltys.'</td>';
							        echo '</tr>';
							        //#e22d06

							        $totalpayment += $myrow["payment_applied"];
									$totalrebate += $myrow["rebate"];					
									$totalpenalty += $myrow["penalty"];
								}
								$display_total_payment = price_format($totalpayment);
								$display_total_rebate = price_format($totalrebate);
								$display_total_penalty = price_format($totalpenalty);
								echo '<tr class="top_bordered">
										<td colspan="5" style="padding-top: 7px; border: 1px solid;" align=right>Total</td>
										<td style="text-align: center; border: 1px solid;"><b>'.$display_total_payment.'</b></td>
										<td style="text-align: center; border: 1px solid;"><b>'.$display_total_rebate.'</b></td>
										<td style="text-align: center; border: 1px solid;"><b></b></td>
										<td style="text-align: center; border: 1px solid;"><b>'.$display_total_penalty.'</b></td>
									</tr>';									
							}
							else
							display_note(_("There are no line items on this dispatch."), 1, 2);					
						?>
					</tbody>
				</table>
				<table id="forexport" style="width: 100%; float: left;" cellspacing="0" cellpadding="0">
					<div class="container">
						<div class="center">	
							<form action="" method="post">					
								<th class="noprintth"><button type="submit" id="dataExport" name="dataExport" value="Export to excel" class="noprint"></button></th>
							</form>
						</div>
					</div>	
				</table>
			</div>
		</div>	
			<?php
				if(isset($_POST["dataExport"])) {	
					$fileName = "installment_inquiry".date('Ymd') . ".xls";			
					header("Content-Type: application/vnd.ms-excel");
					header("Content-Disposition: attachment; filename=\"$fileName\"");	
					$showColoumn = false;
					if(!empty($myrow)) {
					  foreach($myrow as $myrow) {
						if(!$showColoumn) {		 
						  echo implode("\t", array_keys($myrow)) . "\n";
						  $showColoumn = true;
						}
						echo implode("\t", array_values($myrow)) . "\n";
					  }
					}
					exit;  
				}
			?>																
	</tbody>
	</table>
	<script type="text/javascript">
		window.print();
	</script>
</body></html>
