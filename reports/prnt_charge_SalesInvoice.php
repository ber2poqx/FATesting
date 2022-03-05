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
<title>To Print Receipt, CHARGE SALES INVOICE</title>
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
		font-weight: bold;
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
		padding-left: 5px;
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
		padding-bottom: 0.15cm;
	}


	#Total{
	  /*border: 1px solid;*/
	  height: 0.80cm;
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
	 // $si_num = "AGOR-SI00222021";
	$si_num = $_REQUEST['SI_num']; 

	$si_result = get_salesinvoice_trans($si_num,$trans_type = ST_SALESINVOICE);
	
	$myrow=db_fetch($si_result);

	$sold_to = $myrow["Soldto"];
	$address = $myrow["Address"];
	$date = date('m/d/Y', strtotime($myrow["Date"]));
	$terms = $myrow["Terms"];
	$qty = $myrow["Qty"];
	$unit = $myrow["Unit"];
	$article = $myrow["Article"];
	$unit_price = $myrow["UnitCost"];
	$unit_total = 0;
	$grandTotal = 0;
		
?>
<!-- 
prnt_cash_SalesInvoice
 -->

<body>
	

	<div class="main printable">

		
		<div style="height: 3.7cm"></div> <!-- header height -->


		<div class="row">
			<div class="line1" style="width: 4.4cm;"></div> <!-- Sold_to Left indent -->

			<div class="line1" style="width: 7.1cm; text-transform: uppercase;"><?php echo $sold_to?></div> <!-- Sold_to Name position -->

			<div class="line1" style="width: 4.05cm;"></div> <!-- Charge_to and Date spacing -->

			<div class="line1" style="width: 3.1cm; text-align: center;"><?php echo $date?></div> <!-- Date Position -->
		</div>


		<div style="height: 0.2cm"> </div> <!-- Vertical height -->


		<div>
			<div class="line1" style="width: 16.03cm;"></div>

			<div class="line1" style="width: 3.1cm; text-align: center;"><?php echo $terms?></div>
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
					  
				  	$result = get_salesinvoice_trans($si_num,$trans_type = ST_SALESINVOICE);				
					// if (db_num_rows($result) > 0 && db_num_rows($result) <= 5)
					if (db_num_rows($result) <= 5)
					{
						$total = 0;
						$subtotal = 0;

						while ($myrow2=db_fetch($result))
						{	
							$subtotal = $myrow2["Qty"]*$myrow2["UnitCost"];
							

							echo '<tr >';
							echo '<td align=center style="padding-left: 5px; width: 3.6cm; text-align: center; padding-bottom: 0.15cm;">'.($myrow2["Qty"]).'</td>';
							echo '<td align=center style="padding-left: 5px;width: 2.1cm; text-align: center; padding-bottom: 0.15cm;">'.($myrow2["Unit"]).'</td>';
							echo '<td align=center style="padding-left: 5px;width: 6.4cm; text-align: left; padding-bottom: 0.15cm;">'.($myrow2["Article"]).'</td>';
							echo '<td align=center style="width: 2.9cm; text-align: right; padding-bottom: 0.15cm;">'.price_format($myrow2["UnitCost"],2).'</td>';
							echo '<td align=center style="width: 2.5cm; text-align: right; padding-bottom: 0.15cm;">'.price_format($subtotal,2).'</td>';
							echo '</tr>';
						    //end_row();

						    $total += $subtotal;

						} //end while there are line items to print out
						$display_total = price_format($total);
					}
					else if (db_num_rows($result) > 5) {
						display_note(_("Number of items exceeded receipt lines."), 1, 2);
					}
					else
					display_note(_("There are no line items on this dispatch."), 1, 2);										
				?>
			</table>	

		</div>
		<div id="Total"><?php echo $display_total ?></div> <!-- TOTAL AMOUNT POSITION -->

		<div>
			<div class="line1" style="border: 0px solid; width: 13cm;"></div> <!-- LEFT SPACING OF CASHIER NAME -->

			<div class="line1" style="border: 0px solid; width: 5.6cm; text-align: center; text-transform: uppercase;"><?php echo $_SESSION["wa_current_user"]->name ?></div> <!-- CASHIER NAME POSITION -->
		</div>		

	<script type="text/javascript">
		window.print();
	</script>
</body>
</html>