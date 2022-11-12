<?php
/*
	Created by: Albert 11/12/2022

*/

$path_to_root = '..';
if (!isset($path_to_root) || isset($_GET['path_to_root']) || isset($_POST['path_to_root']))
		die(_("Restricted access"));
    include_once($path_to_root . "/includes/ui.inc");
    include_once($path_to_root . "/includes/page/header.inc");
    include_once($path_to_root . "/includes/session.inc");
    include_once($path_to_root . "/includes/ui/items_cart.inc");

    include_once($path_to_root . "/includes/session.inc");

    include_once($path_to_root . "/includes/date_functions.inc");
    include_once($path_to_root . "/includes/data_checks.inc");
?>

<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<title>Print termode full payment</title>
    <style type="text/css">
	.main{
		width: 13in;
		height: 8.5in;
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
    .split {
        height: 100%;
        width: 50%;
        position: fixed;
        z-index: 1;
        top: 0;
        overflow-x: hidden;
        padding-top: 20px;
    }

    /* Control the left side */
    .left {
        left: 0;
		padding-left: 20px;
		
    }

    /* Control the right side */
    .right {
		padding-top: 5cm;
        right: 0;
		color:red;
    }

	.customers_copy {
		height: 60%;
        width: 80%;
		border: 2px solid black;
        right: 0;
		color:red;
    }
	.branch_copy {
		height: 35%;
        width: 90%;
		border: 2px solid black;
        right: 0;
		padding-top: 2px;
		padding-bottom: 2px;
		padding-left: 2px;
		padding-right: 2px
    }	
	.box {
		height: 98%;
        width: 98.5%;
		border: 2px solid black;
        right: 0;
    }	
	
	*{
		font-family: century gothic;
		line-height: 1em;
	}
	

	p{
		margin: 0px;
		padding: 0px;
		margin-bottom: 5px;
	}
	.text-right{
		text-align: right;
	}
    .text-left{
		text-align: left;
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




	@page  {
		margin: 0;
		padding: 0px 25px;
	}
	@media  print {
	  html, body {

		width: 13in;
		height: 8.5in;
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
	
	.companyDes {
		font-size: 18px;
	}

	.Branch {
		font-size: 12px;	
	}
	.Transaction {
		font-size: 15px;
	}
	.CTDate {
		font-size: 12px;
        text-align: right;
        margin-right: 190px;
	}
    .row::after {
	  content: "";
	  clear: both;
	  display: table;
	}
    .line1{

		height: 13px; 
		font-size: 12px;
        
		display: inline-block;
		vertical-align: top; 
		font-family: monospace;
	}
	.foot {
		text-align: center;
		margin-right: 70px;
		font-family: monospace;
	}
	.footer_header{
	    border: 0px solid black;
	    text-align: left;
		padding-right: 10px;
		padding-top: 70px;
		padding-bottom: 5px;
	   
		font-family: century gothic;		
	}
	.footer_names{
	    border: 0px solid black;
	    text-align: left;
		padding-right: 10px;
		padding-top: 40px;
		padding-bottom: 5px;
	   
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
$termode_data =  get_sales_invoice_ct_header($_GET['trans_no']);

	$stock_id = $termode_data['stockid'];
	$date = date("m/d/Y", strtotime($termode_data['invoice_ct_date']));
	$customer = $termode_data['name'];
	$inv_date = date("F d, Y", strtotime($termode_data['invoice_date']));
	$new_term = $termode_data['new_months_term'];
	$old_term = $termode_data['old_months_term'];
	$lcp_price = $termode_data['lcp_amount'];
	$downpayment = $termode_data['downpayment_amount'];
	$amount_to_be_finance = floatval($lcp_price) - floatval($downpayment);
	$new_financing_rate = floatval($termode_data['new_financing_rate']);
	$financing_charge = $amount_to_be_finance * (floatval($termode_data['new_financing_rate'])/100);
	$new_gross_selling_price = $lcp_price + $financing_charge;
	$new_gross_selling_price_net_dp = $new_gross_selling_price - $downpayment;
	$new_amort = $termode_data['new_amort'];
	$rebate = $termode_data['new_rebate'];
	$old_amort = $termode_data['old_amort'];
	$amort_diff = $new_amort - $old_amort;
	$adj_rate = $termode_data['adj_rate'];
	$opportunity_cost = $termode_data['opportunity_cost'];
	$total_amount_due = $new_gross_selling_price + ($rebate * $new_term) + $opportunity_cost;
	$total_payment = $termode_data['total_payment'];
	$amount_due_excluding_penalty = $total_amount_due - $total_payment;
	$adv_payment_rebate = $termode_data['adv_payment_rebate'];
	$amount_due = $amount_due_excluding_penalty + $adv_payment_rebate;
	$old_gross_selling_price = $termode_data['old_ar_amount'];
	$sales_adjustment = $termode_data['sales_adjustment'];
	$months_paid = $termode_data['months_paid'];

	/*customers copy*/

	$total = $old_gross_selling_price + $opportunity_cost;
	$new_gross_sp = $total - $sales_adjustment;
	$customer_amount_due = $new_gross_sp - $total_payment + $adv_payment_rebate;
	/**/

?>

<?php
	$brchcode = $db_connections[user_company()]["branch_code"];
	//echo '({"branchcode":"'.$brcode.'"})';
			
	$compcode = $db_connections[user_company()]["name"];
	//$brscode = get_company_pref("name")

	// $comadd =  $db_connections[user_company()]["postal_address"];
	// $comadd = get_company_pref("postal_address")
?>

<body>

<div class="main printable" style="">
    <div class="split left">   
		<div class="row">
			<div class="line1" style="width: 100%;text-align: center; font-size: 18px;"><b>Du Ek Sam, Inc.<b></div> 
		</div>

		<div class="row">
			<div class="line1" style="width: 6.0cm;"></div> 
			<div class="line1" style="width: 3cm;border-bottom: 2px solid black; font-size: 15px; text-align: center;"><b><?php echo $brchcode?></b></div> 
			<div class="line1" style="width: 0.1cm; font-size: 15px;"><b>Branch</b></div> 
		</div>

		<div class="row">
			<div class="line1" style="width: 5.4cm;"></div> 
			<div class="line1" style="width: 5.5cm;text-align: center; padding-bottom:20px; font-size: 15px;"><b>Term Modification</b></div> 
		</div>
		<div class="row">
			
			<div class="line1" style="width: 9cm;"></div> 
			<div class="line1" style="width: 0.7cm;">Date:</div> 
			<div class="line1" style="width: 2.0cm;border-bottom: 2px solid black; text-align: center;"><?php echo $date?></div> 
		</div>

		<div class="row">
			<div class="line1" style="width: 0.1cm;"></div> 
			<div class="line1" style="width: 1.6cm; ">Customer</div> 
			<div class="line1" style="width: 0.1cm;">:</div> 
			<div class="line1" style="width: 7cm; border-bottom: 2px solid black;"><?php echo $customer?></div> 
			<div class="line1" style="width: 0.4cm;"></div> 
			<div class="line1" style="width: 2.0cm; text-align: right;">Orig. Term:</div> 
			<div class="line1" style="width: 1cm;border-bottom: 2px solid black; text-align: center;"><?php echo $old_term?></div> 
			<div class="line1" style="width: 1.0cm; text-align: center;">mos.</div> 
		</div>

		<div class="row" >
			<div class="line1" style="width: 0.1cm;"></div> 
			<div class="line1" style="width: 1.6cm; ">Date Sold</div> 
			<div class="line1" style="width: 0.1cm;">:</div> 
			<div class="line1" style="width: 7cm; border-bottom: 2px solid black;"><?php echo $inv_date?></div> 
			<div class="line1" style="width: 0.4cm;"></div> 
			<div class="line1" style="width: 2.0cm; text-align: right;">New Term:</div> 
			<div class="line1" style="width: 1cm;border-bottom: 2px solid black; text-align: center;"><?php echo $new_term?></div> 
			<div class="line1" style="width: 1.0cm; text-align: center;">mos.</div> 

		</div>

		<div class="row" >
			<div class="line1" style="width: 0.1cm;"></div> 
			<div class="line1" style="width: 1.6cm; ">Unit Sold</div> 
			<div class="line1" style="width: 0.1cm;">:</div> 
			<div class="line1" style="width: 7cm; border-bottom: 2px solid black;"><?php echo $stock_id ?></div> 
			<div class="line1" style="width: 3.1cm; text-align: center;"></div> 
		</div>

		<div class = "branch_copy">
			<div class = "branch_copy box">

				<div class="row" >
					<div class="line1" style="width: 7cm;">LCP</div> 
					<div class="line1" style="width: 1cm;"></div> 
					<div class="line1" style="width: 2cm; color: red; text-align: right;"><?php echo number_format($lcp_price, 2)?></div> 
					<div class="line1" style="width: 0.6cm;"></div> 
					<div class="line1" style="width: 3cm;">to be filled up</div> 
				</div>

				<div class="row" >
					<div class="line1" style="width: 7cm;">LESS: DP</div> 
					<div class="line1" style="width: 1cm;"></div> 
					<div class="line1" style="width: 2cm; color: red; border-bottom: 2px solid black; text-align: right;"><?php echo number_format($downpayment, 2)?></div> 
					<div class="line1" style="width: 0.6cm;"></div> 
					<div class="line1" style="width: 3cm;">to be filled up</div> 
				</div>

				<div class="row" >
					<div class="line1" style="width: 7cm;">AMOUNT TO BE FINANCED</div> 
					<div class="line1" style="width: 1cm;"></div> 
					<div class="line1" style="width: 2cm; text-align: right;"><?php echo number_format($amount_to_be_finance, 2)?></div> 
					<div class="line1" style="width: 0.6cm;"></div> 
					<div class="line1" style="width: 3cm;">formula</div> 
				</div>

				<div class="row" >
					<div class="line1" style="width: 7.99cm;">MULTIPLY BY the FC of new term (old rate)</div> 
					<div class="line1" style="width: 0.01cm;"></div> 
					<div class="line1" style="width: 2cm; color: red;  border-bottom: 2px solid black; text-align: right;"><?php echo $new_financing_rate?>%</div> 
					<div class="line1" style="width: 0.6cm;"></div> 
					<div class="line1" style="width: 3cm;">formula</div> 
				</div>

				<div class="row" >
					<div class="line1" style="width: 7cm;">FINANCING CHARGE</div> 
					<div class="line1" style="width: 1cm;"></div> 
					<div class="line1" style="width: 2cm; text-align: right;"><?php echo number_format($financing_charge, 2)?></div> 
					<div class="line1" style="width: 0.6cm;"></div> 
					<div class="line1" style="width: 3cm;">to be filled up</div> 
				</div>

				<div class="row" >
					<div class="line1" style="width: 7cm;">ADD: LCP</div> 
					<div class="line1" style="width: 1cm;"></div> 
					<div class="line1" style="width: 2cm; text-align: right;"><?php echo number_format($lcp_price ,2)?></div> 
					<div class="line1" style="width: 0.6cm;"></div> 
					<div class="line1" style="width: 3cm;">to be filled up</div> 
				</div>

				<div class="row" >
					<div class="line1" style="width: 7cm;">NEW GROSS SELLING PRICE</div> 
					<div class="line1" style="width: 1cm;"></div> 
					<div class="line1" style="width: 2cm; text-align: right;"><?php echo number_format($new_gross_selling_price, 2)?></div> 
					<div class="line1" style="width: 0.6cm;"></div> 
					<div class="line1" style="width: 3cm;">formula</div> 
				</div>

				<div class="row" >
					<div class="line1" style="width: 7cm;">LESS: DP</div> 
					<div class="line1" style="width: 1cm;"></div> 
					<div class="line1" style="width: 2cm; color: red; border-bottom: 2px solid black; text-align: right;"><?php echo number_format($downpayment, 2)?></div> 
					<div class="line1" style="width: 0.6cm;"></div> 
					<div class="line1" style="width: 3cm;">to be filled up</div> 
				</div>


				<div class="row" >
					<div class="line1" style="width: 7cm;">NEW GROSS SELLING PRICE net of DP</div> 
					<div class="line1" style="width: 1cm;"></div> 
					<div class="line1" style="width: 2cm; text-align: right;"><?php echo number_format($new_gross_selling_price_net_dp, 2)?></div> 
					<div class="line1" style="width: 0.6cm;"></div> 
					<div class="line1" style="width: 3cm;">formula</div> 
				</div>

				<div class="row" >
					<div class="line1" style="width: 7cm;">DIVIDED BY NEW TERM</div> 
					<div class="line1" style="width: 1cm;"></div> 
					<div class="line1" style="width: 2cm; color: red; border-bottom: 2px solid black; text-align: right;"><?php echo $new_term?></div> 
					<div class="line1" style="width: 0.6cm;"></div> 
					<div class="line1" style="width: 3cm;">to be filled up</div> 
				</div>

				<div class="row" >
					<div class="line1" style="width: 7cm;">MONTHLY AMORTIZATION</div> 
					<div class="line1" style="width: 1cm;"></div> 
					<div class="line1" style="width: 2cm; text-align: right;"><?php echo number_format($new_amort + $rebate, 2)?></div> 
					<div class="line1" style="width: 0.6cm;"></div> 
					<div class="line1" style="width: 3cm;">formula</div> 
				</div>

				<div class="row" >
					<div class="line1" style="width: 7cm;">Add: Rebate per month</div> 
					<div class="line1" style="width: 1cm;"></div> 
					<div class="line1" style="width: 2cm; color: red; border-bottom: 2px solid black; text-align: right;"><?php echo number_format($rebate, 2)?></div> 
					<div class="line1" style="width: 0.6cm;"></div> 
					<div class="line1" style="width: 3cm;">to be filled up</div> 
				</div>

				<div class="row" >
					<div class="line1" style="width: 7cm;">NEW MONTHLY AMORTIZATION</div> 
					<div class="line1" style="width: 1cm;"></div> 
					<div class="line1" style="width: 2cm; text-align: right;"><?php echo number_format($new_amort, 2)?></div> 
					<div class="line1" style="width: 0.6cm;"></div> 
					<div class="line1" style="width: 3cm;">formula</div> 
				</div>

				<div class="row" >
					<div class="line1" style="width: 7cm;">OLD MONTHLY AMORTIZATION</div> 
					<div class="line1" style="width: 1cm;"></div> 
					<div class="line1" style="width: 2cm; color: red; border-bottom: 2px solid black; text-align: right;"><?php echo number_format($old_amort,2)?></div> 
					<div class="line1" style="width: 0.6cm;"></div> 
					<div class="line1" style="width: 3cm;">to be filled up</div> 
				</div>

				<div class="row" >
					<div class="line1" style="width: 7cm;">DIFFERENCE</div> 
					<div class="line1" style="width: 1cm;"></div> 
					<div class="line1" style="width: 2cm; text-align: right;"><?php echo number_format($amort_diff, 2)?></div> 
					<div class="line1" style="width: 0.6cm;"></div> 
					<div class="line1" style="width: 3cm;">formula</div> 
				</div>

				<div class="row" >
					<div class="line1" style="width: 7cm;">MULTIPLY BY # OF MONTHS DUE</div> 
					<div class="line1" style="width: 1cm;"></div> 
					<div class="line1" style="width: 2cm; color: red; text-align: right;"><?php echo $months_paid?></div> 
					<div class="line1" style="width: 0.6cm;"></div> 
					<div class="line1" style="width: 3cm;">to be filled up</div> 
				</div>

				<div class="row" >
					<div class="line1" style="width: 7cm;">MULTIPLY BY ADJUSTMENT RATE</div> 
					<div class="line1" style="width: 1cm;"></div> 
					<div class="line1" style="width: 2cm; text-align: right; border: 2px solid black;"><?php echo $adj_rate?></div> 

				</div>

				<div class="row" >
					<div class="line1" style="width: 7cm;">PENALTY/OPPORTUNITY COST</div> 
					<div class="line1" style="width: 1cm;"></div> 
					<div class="line1" style="width: 2cm; text-align: right;"><?php echo number_format($opportunity_cost, 2)?></div> 
					<div class="line1" style="width: 0.6cm;"></div> 
					<div class="line1" style="width: 3cm;">formula</div> 
				</div>	
		
			</div>

			<div class="row" >
					<div class="line1" style="width: 9cm; padding-top: 10px; padding-bottom: 5px;"><u>TOTAL DUE AT THE TIME OF MODIFICATION:</u></div> 
			</div>

			<div class="row" >
				<div class="line1" style="width: 0.3cm;"></div>
				<div class="line1" style="width: 6.7cm;">NEW GROSS SELLING PRICE</div> 
				<div class="line1" style="width: 1cm;"></div> 
				<div class="line1" style="width: 2cm; text-align: right;"><?php echo number_format($new_gross_selling_price, 2)?></div> 
				<div class="line1" style="width: 0.6cm;"></div> 
				<div class="line1" style="width: 3cm;">addressed</div> 
			</div>	

			<div class="row" >
				<div class="line1" style="width: 0.3cm;"></div>
				<div class="line1" style="width: 6.7cm;">ADD: REBATE</div> 
				<div class="line1" style="width: 1cm;"></div> 
				<div class="line1" style="width: 2cm; text-align: right;"><?php echo number_format($rebate, 2)?></div> 
			</div>	

			<div class="row" >
				<div class="line1" style="width: 0.3cm;"></div>
				<div class="line1" style="width: 6.7cm;">ADD: OPPORTUNITY COST</div> 
				<div class="line1" style="width: 1cm;"></div> 
				<div class="line1" style="width: 2cm; border-bottom: 2px solid black; text-align: right;"><?php echo number_format($opportunity_cost, 2)?></div> 
				<div class="line1" style="width: 0.6cm;"></div> 
				<div class="line1" style="width: 3cm;">addressed</div> 
			</div>
			
			<div class="row" >
				<div class="line1" style="width: 0.3cm;"></div>
				<div class="line1" style="width: 6.7cm;">TOTAL AMOUNT DUE</div> 
				<div class="line1" style="width: 1cm;"></div> 
				<div class="line1" style="width: 2cm; text-align: right;"><?php echo number_format($total_amount_due, 2)?></div> 
			</div>

			<div class="row" >
				<div class="line1" style="width: 0.3cm;"></div>
				<div class="line1" style="width: 6.7cm;">LESS: TOTAL PAYMENT</div> 
				<div class="line1" style="width: 1cm;"></div> 
				<div class="line1" style="width: 2cm; border-bottom: 2px solid black; text-align: right;"><?php echo $total_payment?></div> 
				<div class="line1" style="width: 0.6cm;"></div> 
				<div class="line1" style="width: 3cm;">to be filled up</div> 
			</div>

			<div class="row" >
				<div class="line1" style="width: 7.1cm;">AMOUNT DUE excluding penalty</div> 
				<div class="line1" style="width: 1cm;"></div> 
				<div class="line1" style="width: 2cm; text-align: right;"><?php echo number_format($amount_due_excluding_penalty, 2)?></div> 
			</div>

			<div class="row" >
				<div class="line1" style="width: 0.3cm;"></div>
				<div class="line1" style="width: 6.7cm;">ADD: penalty</div> 
				<div class="line1" style="width: 1cm;"></div> 
				<div class="line1" style="width: 2cm; text-align: right;"><?php echo number_format($adv_payment_rebate, 2)?></div> 
			</div>

			<div class="row" >
				<div class="line1" style="width: 7.1cm;">AMOUNT DUE</div> 
				<div class="line1" style="width: 1cm;"></div> 
				<div class="line1" style="width: 2cm; border: 2px solid black; text-align: right;"><?php echo number_format($amount_due, 2)?></div> 
			</div>

			<div class="row" >
					<div class="line1" style="width: 7cm; padding-top: 10px; padding-bottom: 5px;"><u>ADJUSTMENT ON SALES:</u></div> 
			</div>

			<div class="row" >
				<div class="line1" style="width: 0.3cm;"></div>
				<div class="line1" style="width: 6.7cm;">OLD GROSS SELLING PRICE</div> 
				<div class="line1" style="width: 1cm;"></div> 
				<div class="line1" style="width: 2cm; color: red; text-align: right;"><?php echo number_format($old_gross_selling_price, 2)?></div> 
				<div class="line1" style="width: 0.6cm;"></div> 
				<div class="line1" style="width: 3cm;">to be filled up</div>
			</div>

			<div class="row" >
				<div class="line1" style="width: 0.3cm;"></div>
				<div class="line1" style="width: 6.7cm;">NEW GROSS SELLING PRICE</div> 
				<div class="line1" style="width: 1cm;"></div> 
				<div class="line1" style="width: 2cm;  border-bottom: 2px solid black; text-align: right;"><?php echo number_format($new_gross_selling_price + $downpayment, 2)?></div>
			</div>

			<div class="row" >
				<div class="line1" style="width: 0.3cm;"></div>
				<div class="line1" style="width: 6.7cm;">SALES ADJUSTMENT</div> 
				<div class="line1" style="width: 1cm;"></div> 
				<div class="line1" style="width: 2cm; text-align: right;"><?php echo number_format($sales_adjustment, 2)?></div> 
			</div>

			<div class="footer_header " >
				<div class="row" >

					<div class="line1" style="width: 4cm;">Prepared by:</div>
					<div class="line1" style="width: 0.5cm;"></div>
					<div class="line1" style="width: 4cm;text-align: center">Reviewed by:</div> 
					<div class="line1" style="width: 2cm;"></div>
					<div class="line1" style="width: 2.5cm; text-align: left">Noted by:</div> 
				</div>

			</div>

			<div class="footer_names " >
				<div class="row" >

					<div class="line1" style="width: 4cm; border-bottom: 2px solid black;"></div>
					<div class="line1" style="width: 0.5cm;"></div>
					<div class="line1" style="width: 4cm;text-align: center; border-bottom: 2px solid black;"></div> 
					<div class="line1" style="width: 2cm;"></div>
					<div class="line1" style="width: 2.5cm; text-align: left;">Branch Manager</div> 
				</div>

			</div>

			<div class="row" >
					<div class="line1" style="width: 7cm; padding-top: 10px; padding-bottom: 5px;"><p>Branch copy</p></div> 
			</div>


		</div>

    </div>

	<div class="split right"> 
		<div class = "customers_copy">

			<div class="row">
				<div class="line1" style="width: 100%;text-align: center; font-size: 18px;"><b>Du Ek Sam, Inc.<b></div> 
			</div>

			<div class="row">
				<div class="line1" style="width: 4.0cm;"></div> 
				<div class="line1" style="width: 3cm;border-bottom: 2px solid black; font-size: 15px; text-align: center;"><b><?php echo $brchcode?></b></div> 
				<div class="line1" style="width: 0.1cm; font-size: 15px;"><b>Branch</b></div> 
			</div>

			<div class="row">
				<div class="line1" style="width: 3.7cm;"></div> 
				<div class="line1" style="width: 5.5cm;text-align: center; font-size: 15px;"><b>Term Modification</b></div> 
			</div>
			
			<div class="row" >
				<div class="line1" style="width: 0.1cm;"></div> 
				<div class="line1" style="width: 9cm; height: 20px;"></div> 
				<div class="line1" style="width: 0.7cm;">Date:</div> 
				<div class="line1" style="width: 2.0cm;border-bottom: 2px solid black; text-align: center;"><?php echo $date?></div> 
			</div>

			<div class="row" >
				<div class="line1" style="width: 0.4cm;"></div> 
				<div class="line1" style="width: 1.8cm; ">Customer</div> 
				<div class="line1" style="width: 0.1cm;">:</div> 
				<div class="line1" style="width: 7cm; border-bottom: 2px solid black;"><?php echo $customer?></div> 
				<div class="line1" style="width: 3.1cm; text-align: center;"></div> 
			</div>

			<div class="row" >
				<div class="line1" style="width: 0.4cm;"></div> 
				<div class="line1" style="width: 1.8cm; ">Date Sold</div> 
				<div class="line1" style="width: 0.1cm;">:</div> 
				<div class="line1" style="width: 7cm; border-bottom: 2px solid black;"><?php echo $inv_date?></div> 
				<div class="line1" style="width: 3.1cm; text-align: center;"></div> 
			</div>

			<div class="row" >
				<div class="line1" style="width: 0.4cm;"></div> 
				<div class="line1" style="width: 1.8cm; ">Unit Sold</div> 
				<div class="line1" style="width: 0.1cm;">:</div> 
				<div class="line1" style="width: 7cm; border-bottom: 2px solid black;"><?php echo $brchcode?></div> 
				<div class="line1" style="width: 3.1cm; text-align: center;"></div> 
			</div>
	
			<div class="row" >
				<div class="line1" style="width: 0.4cm;"></div> 
				<div class="line1" style="width: 1.8cm;">Orig. Term</div> 
				<div class="line1" style="width: 0.1cm;">:</div> 
				<div class="line1" style="width: 2cm; text-align: center;  border-bottom: 2px solid black;"><?php echo $old_term?></div> 
				<div class="line1" style="width: 3.1cm;"> months</div> 
			</div>
			<div class="row" >
				<div class="line1" style="width: 0.4cm;"></div> 
				<div class="line1" style="width: 1.8cm;">New Term</div> 
				<div class="line1" style="width: 0.1cm;">:</div> 
				<div class="line1" style="width: 2cm; text-align: center;  border-bottom: 2px solid black;"><?php echo $new_term?></div> 
				<div class="line1" style="width: 3.1cm;"> months</div> 
			</div>

			<div class="row" >
				<div class="line1" style="width: 0.4cm;"></div> 
				<div class="line1" style="width: 10cm; "><p>Amount Due:</p></div> 

			</div>

			<div class="row" >
				<div class="line1" style="width: 1.0cm;"></div> 
				<div class="line1" style="width: 9cm; "><p>OLD GROSS SELLING PRICE</p></div> 
				<div class="line1" style="width: 2cm; text-align: right;"><?php echo number_format($old_gross_selling_price, 2)?></div>
			</div>

			<div class="row" >
				<div class="line1" style="width: 1.0cm;"></div> 
				<div class="line1" style="width: 9cm; ">ADD: OPPORTUNITY COST</div> 
				<div class="line1" style="width: 2cm; border-bottom: 2px solid black; text-align: right;"><?php echo number_format($opportunity_cost, 2)?></div>
			</div>

			<div class="row" >
				<div class="line1" style="width: 1.0cm;"></div> 
				<div class="line1" style="width: 9cm; ">TOTAL:</div> 
				<div class="line1" style="width: 2cm; border-bottom: 2px solid black; text-align: right;"><?php echo number_format($old_gross_selling_price + $opportunity_cost, 2) ?></div>
			</div>

			<div class="row" >
				<div class="line1" style="width: 1.0cm;"></div> 
				<div class="line1" style="width: 9cm; ">LESS: SALES ADJUSTMENT</div> 
				<div class="line1" style="width: 2cm; border-bottom: 2px solid black; text-align: right;"><?php echo number_format($sales_adjustment, 2)?></div>
			</div>

			<div class="row" >
				<div class="line1" style="width: 1.0cm;"></div> 
				<div class="line1" style="width: 9cm; ">NEW GROSS SELLING PRICE</div> 
				<div class="line1" style="width: 2cm; border-bottom: 2px solid black; text-align: right;"><?php echo number_format($new_gross_sp, 2)?></div>
			</div>

			<div class="row" >
				<div class="line1" style="width: 1.0cm;"></div> 
				<div class="line1" style="width: 9cm; ">LESS: TOTAL PAYMENTS</div> 
				<div class="line1" style="width: 2cm; border-bottom: 2px solid black; text-align: right;"><?php echo number_format($total_payment, 2)?></div>
			</div>

			<div class="row" >
				<div class="line1" style="width: 10.4cm; "></div> 
				<div class="line1" style="width: 1.0cm; text-align: right;"><?php echo number_format($new_gross_sp - $total_payment, 2)?></div>
			</div>

			<div class="row" >
				<div class="line1" style="width: 1.0cm; padding-bottom: 20px;"></div> 
				<div class="line1" style="width: 10cm; padding-bottom: 20px; ">ADD: PENALTY</div> 
				<div class="line1" style="width: 1.0cm; padding-bottom: 20px; text-align: right;"><?php echo number_format($adv_payment_rebate, 2)?></div> 
			</div>

			<div class="row" >
				<div class="line1" style="width: 2.4cm;"></div> 
				<div class="line1" style="width: 6.58cm;border-top: 2px solid black;"></div> 
				<div class="line1" style="width: 0.88cm;"></div> 
				<div class="line1" style="width: 2.0cm;border-top: 2px solid black;"></div> 
			</div>

			<div class="row" >
				<div class="line1" style="width: 0.4cm;"></div> 
				<div class="line1" style="width: 10cm; ">AMOUNT DUE</div> 
				<div class="line1" style="width: 1.0cm; text-align: right;"><?php echo number_format($customer_amount_due, 2)?></div> 
			</div>
			
			<div style="width:100%; height: 1px; border-bottom: 2px solid black;"></div> 
			
			<div style="width:100%; height: 3px; border-bottom: 2px solid black;"></div> 
			
			<div class="row">
				<div class="line1" style="width: 0.4cm;"></div> 
				<div class="line1" style="width: 2cm; height: 30px;padding-top: 2cm; ">Noted by:</div> 
		
			</div>

			<div class="row" >
				<div class="line1" style="width: 0.4cm;"></div> 
				<div class="line1" style="width: 4cm; height: 30px; border-bottom: 2px solid black;"></div> 
			</div>

			<div class="row" >
				<div class="line1" style="width: 0.1cm;"></div> 
				<div class="line1" style="width: 10cm; height: 20px;"></div> 
				<div class="line1" style="width: 3cm;">Customer's copy</div> 
			</div>
		</div>
    </div>
</div>
<script type="text/javascript">
		window.print();
</script>


</body>
</html>