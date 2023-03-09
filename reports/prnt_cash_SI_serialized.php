<?php
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

	include_once($path_to_root . "/inventory/includes/stock_transfers_ui.inc");
	include_once($path_to_root . "/inventory/includes/inventory_db.inc");
	include_once($path_to_root . "/modules/serial_items/includes/modules_db.inc");
	include_once($path_to_root . "/includes/cost_and_pricing.inc");
	
?>

<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title>To Print Receipt, CASH SALES INVOICE SERIALIZED</title>
<style type="text/css">
	.main{
		width: 8.3in;
		height: 7in;
		background: #fff;
		margin: 0 auto;
	}
	body{
		width: 100%;
		padding: 20px 0px;
		height: 100%;
		background: #eee;
		margin: 0px;
		font-size: 12px;
		font-family: monospace;
		/*font-weight: bold;*/
		color: blue;
	}	

	@page  {
		margin: 0;
		padding: 0px 25px;
	}
	@media  print {
	  html, body {

		width: 8.5in;
		height: 7in;
		padding-left: 15px;
		padding-right: 5px;
		  }
	  /* ... the rest of the rules ... */
	}
	/*
		CSS FOR TABLES
	*/
	*{
	    margin:0;
	    padding:0;
	}
	.line1{
		/*border: 1px solid; */
		height: 11px; 
		font-size: 12px;
		display: inline-block;
		vertical-align: top; 
		font-family: monospace;
	}

	td{
		/*border: 1px solid;*/
		vertical-align: top;
	}

	tr{
		padding-bottom: 0.10cm;
	}


	#Total{
	  /*border: 1px solid;*/
	  height: 1.40cm;
	  text-align: right;
	  padding-right: 1.7cm
	}

	#Sign{
	  /*border: 1px solid;*/
	  text-transform: uppercase;
	  height: 1.75cm;
	  text-align: right;
	  padding-right: 1.5cm
	}


</style>
</head>

<?php
function get_detailed_unit($itemcode)
{
	set_global_connection();

	$sql = "SELECT CONCAT(IFNULL(sc.description,'no category'), ' - ', IFNULL(ib.name,'no brand')) AS `brand`, `item_code` AS `model`, ic.color, sc.description as `cat_code`
			FROM " . TB_PREF . "`item_codes` ic
				LEFT JOIN " . TB_PREF . "`item_brand` ib ON ic.brand = ib.id
				LEFT JOIN " . TB_PREF . "`stock_category` sc ON ic.category_id = sc.category_id
			WHERE item_code = '$itemcode'"; 	

	return db_query($sql, "No transactions were returned");
}

function get_SI_trans_no_from_amort_payments($trans_ref)
{
	set_global_connection();

	$sql = "SELECT dt.trans_no AS `SI_transno`, dt.reference, bt.ref
			FROM " . TB_PREF . "`debtor_trans` dt
				LEFT JOIN " . TB_PREF . "`bank_trans` bt ON dt.debtor_no = bt.person_id
			WHERE bt.`receipt_no` = '".$trans_ref."' and dt.`type` = ".ST_SALESINVOICE."";

			/*WHERE bt.type = ".ST_CUSTPAYMENT." AND dt.type = ".ST_SALESINVOICE." AND bt.ref = '".$trans_ref."'";*/
	return db_query($sql, "No transactions were returned");
}
?>

<?php
	 //$si_num = "10";
	if($_REQUEST['SI_req']=="YES")
	{
		$trans_ref = $_REQUEST['SI_num'];

		$trans_ref_result = get_SI_trans_no_from_amort_payments($trans_ref);

		$myrow2=db_fetch($trans_ref_result);

		$si_num = $myrow2["SI_transno"];
	}
	else
	{
		$si_num = $_REQUEST['SI_num'];
	}
	 
	$si_result = get_salesinvoice_trans_serialized($si_num,$trans_type = ST_SALESINVOICE);
	
	$myrow=db_fetch($si_result);

	$sold_to = $myrow["Soldto"];
	$address = $myrow["Address"];
	$date = date('m/d/Y', strtotime($myrow["Date"]));
	$terms = $myrow["Terms"];
	
	$itemcode = $myrow["stock_id"];
	
	$ic_result = get_detailed_unit($itemcode);
	$ic_row = db_fetch($ic_result); //returns: brand, model & color

	$qty = $myrow["Qty"];
	$cat_code = $ic_row["cat_code"];
	$brand = $ic_row["brand"];
	$model = $ic_row["model"];
	$serial = $myrow["serial"];
	$chassis = $myrow["chassis"];
	$color = $ic_row["color"];

	$first_due_date = $myrow["firstdue_date"];
	$downpayment = $myrow["downpayment"];
	$monthly_payment = $myrow["amort"];
	$rebate = $myrow["rebate"];
	$unit_price = $myrow["UnitCost"];
	$unit_total = $qty * $unit_price;
	$discount = $myrow["discount"];
	$total_sales_VAT_incl = $unit_price - $discount;
	$less_VAT = $total_sales_VAT_incl * 0.10714; // adjust tax rate percentage here 10.7%
	$amount_Net_of_VAT = $total_sales_VAT_incl - $less_VAT;
	$amount_due = $amount_Net_of_VAT;
	$add_VAT = $less_VAT;
	$TOTAL_amount_due = $amount_due + $add_VAT;
	$cashier = $_SESSION["wa_current_user"]->name;
	$sales_agent = $myrow["salesman"];
?>


<body>

	<div class="main printable">

		<div style="height: 3.5cm"></div> <!-- header height -->

		<div class="row">
			<div class="line1" style="width: 4.4cm;"></div> <!-- Sold_to Left indent -->

			<div class="line1" style="width: 7.1cm; text-transform: uppercase;"><?php echo $sold_to?></div> <!-- Sold_to Name position -->

			<div class="line1" style="width: 4.05cm;"></div> <!-- Charge_to and Date spacing -->

			<div class="line1" style="width: 3.1cm; text-align: center;"><?php echo $date?></div> <!-- Date Position -->
		</div>


		<div style="height: 0.1cm"> </div> <!-- Vertical height -->


		<div>
			<div class="line1" style="width: 16.03cm;"></div>

			<div class="line1" style="width: 3.1cm; text-align: center;"><?php echo $terms?> Months</div> <!-- Month Terms -->
		</div>


		<div style="height: 0.1cm"> </div> <!-- Vertical height -->


		<div>
			<div class="line1" style="width: 4.4cm; padding-top: 0px;padding-bottom: 0px"></div> <!-- Address Left indent -->

			<div class="line1" style="width: 7.2cm; padding-top: 0px;padding-bottom: 0px; text-transform: uppercase;"><?php echo $address?></div> <!-- address position -->
		</div>


		<div style="height: 1.8cm"></div> <!-- table top spacing -->


		<div style="border: 0px solid;height: 5.2cm">

			<div class="line1" style="width: 1cm;"></div> <!-- table left spacing -->

			<table class="line1">
				<?php
				  	echo '<tr >'; // 1st ROW
					echo '<td align=left style="padding-left: 0px; width: 12.1cm; text-align: left; padding-bottom: 0.10cm;">'.$qty.'  Brand : '.($brand).'</td>'; // QTY & BRAND
					echo '<td align=left style="width: 2.9cm; text-align: right; padding-bottom: 0.10cm;">'.price_format($unit_price,2).'</td>'; // UNIT COST
					echo '<td align=left style="width: 2.5cm; text-align: right; padding-bottom: 0.10cm;">'.price_format($unit_total,2).'</td>'; // SUBTOTAL
					echo '</tr>';		
					
					echo '<tr >'; // 2nd ROW
					echo '<td align=left style="padding-left: 15px; width: 12.1cm; text-align: left; padding-bottom: 0.10cm;">Model : '.($model).'</td>'; // MODEL
					echo '<td align=left style="width: 2.9cm; text-align: right; padding-bottom: 0.10cm;">Discount: </td>'; // DISCOUNT LABEL
					echo '<td align=left style="width: 2.5cm; text-align: right; padding-bottom: 0.10cm;">'.price_format($discount,2).'</td>'; // DISCOUNT AMOUNT
					echo '</tr>';

					echo '<tr >'; // 3rd ROW
					if($cat_code == "MOTORCYCLE")
					{
						echo '<td align=left style="padding-left: 15px; width: 12.1cm; text-align: left; padding-bottom: 0.10cm;">Engine # : '.($serial).'</td>'; // MC ENGINE #
					}
					else 
					{
						echo '<td align=left style="padding-left: 15px; width: 12.1cm; text-align: left; padding-bottom: 0.10cm;">Serial # : '.($serial).'</td>'; // SERIAL #
					}					
					echo '</tr>';

					echo '<tr >'; // 4th ROW
					if($cat_code == "MOTORCYCLE")
					{
						echo '<td align=left style="padding-left: 15px; width: 12.1cm; text-align: left; padding-bottom: 0.10cm;">Chassis # : '.($chassis).'</td>'; // MC CHASSIS #
					}
					else 
					{
						echo '<td align=left style="padding-left: 15px; width: 12.1cm; text-align: left; padding-bottom: 0.10cm;">.</td>'; // 1st due date
					}					
					echo '</tr>';

					echo '<tr >'; // 5th ROW
					if($cat_code == "MOTORCYCLE")
					{
						echo '<td align=left style="padding-left: 15px; width: 12.1cm; text-align: left; padding-bottom: 0.10cm;">Color : '.($color).'</td>'; // MC COLOR
					}
					else 
					{
						echo '<td align=left style="padding-left: 15px; width: 12.1cm; text-align: left; padding-bottom: 0.10cm;">.</td>'; // downpayment
					}					
					echo '</tr>';

					echo '<tr >'; // 6th ROW
					if($cat_code == "MOTORCYCLE")
					{
						echo '<td align=left style="padding-left: 15px; width: 12.1cm; text-align: left; padding-bottom: 0.01cm;">.</td>'; // 1st due date
						echo '<td align=left style="width: 2.9cm; text-align: right; padding-bottom: 0.01cm;"></td>'; // blank
						echo '<td align=left style="width: 2.5cm; text-align: right; padding-bottom: 0.01cm;">'.price_format($total_sales_VAT_incl,2).'</td>'; // total sales includes VAT
					}
					else 
					{
						echo '<td align=left style="padding-left: 15px; width: 12.1cm; text-align: left; padding-bottom: 0.01cm;">.</td>'; // Monthly payment
						echo '<td align=left style="width: 2.9cm; text-align: right; padding-bottom: 0.01cm;"></td>'; // blank
						echo '<td align=left style="width: 2.5cm; text-align: right; padding-bottom: 0.01cm;">'.price_format($total_sales_VAT_incl,2).'</td>'; // total sales includes VAT
					}					
					echo '</tr>';

					echo '<tr >'; // 7th ROW
					if($cat_code == "MOTORCYCLE")
					{
						echo '<td align=left style="padding-left: 15px; width: 12.1cm; text-align: left; padding-bottom: 0.01cm;">.</td>'; // downpayment
						echo '<td align=left style="width: 2.9cm; text-align: right; padding-bottom: 0.01cm;"></td>'; // blank
						echo '<td align=left style="width: 2.5cm; text-align: right; padding-bottom: 0.01cm;">'.price_format($less_VAT,2).'</td>'; // less VAT
					}
					else 
					{
						echo '<td align=left style="padding-left: 15px; width: 12.1cm; text-align: left; padding-bottom: 0.01cm;">.</td>'; // rebate
						echo '<td align=left style="width: 2.9cm; text-align: right; padding-bottom: 0.01cm;"></td>'; // blank
						echo '<td align=left style="width: 2.5cm; text-align: right; padding-bottom: 0.01cm;">'.price_format($less_VAT,2).'</td>'; // less VAT
					}					
					echo '</tr>';

					echo '<tr >'; // 8th ROW
					if($cat_code == "MOTORCYCLE")
					{
						echo '<td align=left style="padding-left: 15px; width: 12.1cm; text-align: left; padding-bottom: 0.01cm;">.</td>'; // Monthly payment
						echo '<td align=left style="width: 2.9cm; text-align: right; padding-bottom: 0.01cm;"></td>'; // blank
						echo '<td align=left style="width: 2.5cm; text-align: right; padding-bottom: 0.01cm;">'.price_format($amount_Net_of_VAT,2).'</td>'; // amount_Net_of_VAT
					}
					else 
					{
						echo '<td align=left style="padding-left: 15px; width: 12.1cm; text-align: left; padding-bottom: 0.01cm;"></td>'; // blank
						echo '<td align=left style="width: 2.9cm; text-align: right; padding-bottom: 0.01cm;"></td>'; // blank
						echo '<td align=left style="width: 2.5cm; text-align: right; padding-bottom: 0.01cm;">'.price_format($amount_Net_of_VAT,2).'</td>'; // amount_Net_of_VAT
					}					
					echo '</tr>';

					echo '<tr >'; // 9th ROW
					if($cat_code == "MOTORCYCLE")
					{
						echo '<td align=left style="padding-left: 15px; width: 12.1cm; text-align: left; padding-bottom: 0.01cm;">.</td>'; // rebate
						echo '<td align=left style="width: 2.9cm; text-align: right; padding-bottom: 0.01cm;"></td>'; // blank
						echo '<td align=left style="width: 2.5cm; text-align: right; padding-bottom: 0.01cm;">.</td>'; // SC/PWD discount
					}
					else 
					{
						echo '<td align=left style="padding-left: 15px; width: 12.1cm; text-align: left; padding-bottom: 0.01cm;"></td>'; // blank
						echo '<td align=left style="width: 2.9cm; text-align: right; padding-bottom: 0.01cm;"></td>'; // blank
						echo '<td align=left style="width: 2.5cm; text-align: right; padding-bottom: 0.01cm;">.</td>'; // SC/PWD discount
					}								
					echo '</tr>';

					echo '<tr >'; // 10th ROW
					echo '<td align=left style="padding-left: 15px; width: 12.1cm; text-align: left; padding-bottom: 0.01cm;"></td>'; // blank
					echo '<td align=left style="width: 2.9cm; text-align: right; padding-bottom: 0.01cm;"></td>'; // blank
					echo '<td align=left style="width: 2.5cm; text-align: right; padding-bottom: 0.01cm;">'.price_format($amount_due,2).'</td>'; // Amount Due					
					echo '</tr>';

					echo '<tr >'; // 11th ROW
					echo '<td align=left style="padding-left: 15px; width: 12.1cm; text-align: left; padding-bottom: 0.01cm;"></td>'; // blank
					echo '<td align=left style="width: 2.9cm; text-align: right; padding-bottom: 0.01cm;"></td>'; // blank
					echo '<td align=left style="width: 2.5cm; text-align: right; padding-bottom: 0.01cm;">'.price_format($add_VAT,2).'</td>'; // Add: VAT				
					echo '</tr>';

					echo '<tr >'; // 12th ROW
					echo '<td align=left style="padding-left: 15px; width: 12.1cm; text-align: left; padding-bottom: 0.01cm;"></td>'; // blank
					echo '<td align=left style="width: 2.9cm; text-align: right; padding-bottom: 0.01cm;"></td>'; // blank
					echo '<td align=left style="width: 2.5cm; text-align: right; padding-bottom: 0.01cm;">'.price_format($TOTAL_amount_due,2).'</td>'; // Total Amount Due		
					echo '</tr>';
				?>
			</table>	

		</div>
		<div id="Total"><?php echo $display_total ?></div> <!-- TOTAL AMOUNT POSITION -->

		<div>
			<div class="line1" style="border: 0px solid; width: 5.5cm; text-align: center;"></div> <!-- LEFT SPACING OF sales agent -->
			<div class="line1" style="border: 0px solid; width: 6cm; text-align: center;"><?php echo $sales_agent ?></div> <!-- SALES AGENT PLACEMENT -->
			<div class="line1" style="border: 0px solid; width: 0.5cm; text-align: center;"></div> <!-- LEFT SPACING OF CASHIER NAME -->

			<div class="line1" style="border-bottom: 0px solid; width: 5.6cm; text-align: center; text-transform: uppercase; padding-bottom: 0.02cm;"><?php echo $_SESSION["wa_current_user"]->name ?></div> <!-- CASHIER NAME POSITION -->
		</div>	
		<div class="line1" style="border: 0px solid; width: 5.5cm; text-align: center;"></div> <!-- LEFT SPACING OF sales agent -->
		<div class="line1" style="border-top: 1px solid; width: 6cm; text-align: center;">Sales Agent</div> <!--  -->
		<div class="line1" style="border: 0px solid; width: 1.5cm; text-align: center;"></div> <!-- LEFT SPACING OF CASHIER NAME -->

	<script type="text/javascript">
		window.print();
	</script>
</body>
</html>