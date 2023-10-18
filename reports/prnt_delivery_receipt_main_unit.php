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
<title>To Print Receipt, DELIVERY RECEIPT</title>
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
	  height: 0.43cm;
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
	//$trans_no = "1";
	$trans_no = $_REQUEST['trans_no'];

	function get_detailed_unit($itemcode)
	{
		set_global_connection();

		$sql = "SELECT CONCAT(IFNULL(sc.description,'no category'), ' - ', IFNULL(ib.name,'no brand')) AS `brand`, CONCAT(ic.item_code,' - ',ic.description) AS `model`, IFNULL(dtd.color_code,ic.description) AS `color`, sc.description as `cat_code`
				FROM " . TB_PREF . "`item_codes` ic
					LEFT JOIN " . TB_PREF . "`item_brand` ib ON ic.brand = ib.id
					LEFT JOIN " . TB_PREF . "`stock_category` sc ON ic.category_id = sc.category_id
					LEFT JOIN " . TB_PREF . "`debtor_trans_details` dtd ON ic.stock_id = dtd.stock_id
				WHERE ic.item_code = '$itemcode'"; 	

		return db_query($sql, "No transactions were returned");
	}
	


	

	$trans_result = get_salesinvoice_trans_serialized($trans_no,$trans_type = ST_CUSTDELIVERY);
	
	$myrow=db_fetch($trans_result);

	$itemcode = $myrow["stock_id"];
	$color_code = $myrow["color_code"];
	
	//$si_result = get_salesinvoice_trans_serialized($trans_no,$trans_type = ST_SALESINVOICE);	
	//$myrow=db_fetch($si_result);

	$ic_result = get_detailed_unit($itemcode);
	$ic_row = db_fetch($ic_result); //returns: brand, model & color

	$color_result = get_detailed_unit($color_code);
	$color_row = db_fetch($color_result); //color

	$qty = $myrow["Qty"];
	$brand = $ic_row["brand"];
	$model = $ic_row["model"];
	$serial = $myrow["serial"];
	$chassis = $myrow["chassis"];
	$color = $color_row["color"];
	$cat_code = $ic_row["cat_code"];
	$sold_to = $myrow["Soldto"];
	$address = $myrow["Address"];
	$date = date('m/d/Y', strtotime($myrow["Date"]));
	$terms = $myrow["Terms"];

		
?>
<!-- 
prnt_cash_SalesInvoice
 -->

<body>
	

	<div class="main printable">

		
		<div style="height: 4.4cm"></div> <!-- header height -->


		<div class="row">
			<div class="line1" style="width: 4cm;"></div> <!-- Sold_to Left indent -->

			<div class="line1" style="width: 7.1cm; text-transform: uppercase;"><?php echo $sold_to?></div> <!-- Sold_to Name position -->

			<div class="line1" style="width: 4.05cm;"></div> <!-- Charge_to and Date spacing -->

			<div class="line1" style="width: 3.25cm; text-align: center;"><?php echo $date?></div> <!-- Date Position -->
		</div>


		<div style="height: 0.3cm"> </div> <!-- Vertical height -->


		<div>
			<div class="line1" style="width: 16.03cm;"></div>

			<div class="line1" style="width: 3.1cm; text-align: center;"> </div>
		</div>


		<div style="height: 0.0cm"> </div> <!-- Vertical height -->


		<div>
			<div class="line1" style="width: 4cm; padding-top: 0px;padding-bottom: 0px"></div> <!-- Address Left indent -->

			<div class="line1" style="width: 7.1cm; padding-top: 0px;padding-bottom: 0px; text-transform: uppercase;"><?php echo $address?></div> <!-- address position -->
			<div class="line1" style="width: 4.05cm;"></div> <!-- Address and Terms spacing -->

			<div class="line1" style="width: 3.25cm; text-align: center;"><?php echo $terms?></div> <!-- Terms Position -->
		</div>


		<div style="height: 1.1cm"></div> <!-- table top spacing -->


		<div style="border: 0px solid;height: 5.2cm">

			<div class="line1" style="width: 0.6cm;"></div> <!-- table left spacing -->

			<table class="line1">
				<?php						  
					/*
				  	$result = get_salesinvoice_trans($trans_no,$trans_type = ST_CUSTDELIVERY);				
					// if (db_num_rows($result) > 0 && db_num_rows($result) <= 5)
					if (db_num_rows($result) <= 9)
					{
						$total = 0;
						$subtotal = 0;

						while ($myrow2=db_fetch($result))
						{								
							echo '<tr >';
							echo '<td align=center style="border: 0px solid; padding-left: 5px; width: 3.2cm; text-align: center; padding-bottom: 0.2cm;">'.($myrow2["Qty"]).'</td>';
							echo '<td align=center style="border: 0px solid; padding-left: 5px;width: 2.5cm; text-align: center; padding-bottom: 0.2cm;">'.($myrow2["Unit"]).'</td>';
							echo '<td align=center style="border: 0px solid; padding-left: 5px;width: 12cm; text-align: left; padding-bottom: 0.2cm;">'.($myrow2["Articles"]).'</td>';							
							echo '</tr>';

						} //end while there are line items to print out
					}
					else if (db_num_rows($result) > 9) {
						display_note(_("Number of items exceeded receipt lines."), 1, 2);
					}
					else
					display_note(_("There are no line items on this dispatch."), 1, 2);		
					*/

					echo '<tr >'; // 1st ROW
					echo '<td align=left style="padding-left: 30px; width: 12.1cm; text-align: left; padding-bottom: 0.10cm;">'.$qty.'  Brand : '.($brand).'</td>'; // QTY & BRAND
					echo '</tr>';		
					
					echo '<tr >'; // 2nd ROW
					echo '<td align=left style="padding-left: 45px; width: 12.1cm; text-align: left; padding-bottom: 0.10cm;">Model : '.($model).'</td>'; // MODEL
					echo '</tr>';

					echo '<tr >'; // 3rd ROW
					if($cat_code == "MOTORCYCLE")
					{
						echo '<td align=left style="padding-left: 45px; width: 12.1cm; text-align: left; padding-bottom: 0.10cm;">Engine # : '.($serial).'</td>'; // MC ENGINE #
					}
					else 
					{
						echo '<td align=left style="padding-left: 45px; width: 12.1cm; text-align: left; padding-bottom: 0.10cm;">Serial # : '.($serial).'</td>'; // SERIAL #
					}					
					echo '</tr>';

					echo '<tr >'; // 4th ROW
					echo '<td align=left style="padding-left: 45px; width: 12.1cm; text-align: left; padding-bottom: 0.10cm;">Chassis # : '.($chassis).'</td>'; // MC CHASSIS #	
					echo '</tr>';

					echo '<tr >'; // 5th ROW
					echo '<td align=left style="padding-left: 45px; width: 12.1cm; text-align: left; padding-bottom: 0.10cm;">Color : '.($color).'</td>'; // MC COLOR	
					echo '</tr>';

				?>
			</table>	

		</div>
		<div id="Total"> </div> <!-- TOTAL AMOUNT POSITION -->

		<div>
			<div class="line1" style="border: 0px solid; width: 10.55cm;"></div> <!-- LEFT SPACING OF CASHIER NAME -->

			<div class="line1" style="border: 0px solid; width: 7.6cm; text-align: center; text-transform: uppercase;"><?php echo $_SESSION["wa_current_user"]->name ?></div> <!-- CASHIER NAME POSITION -->
		</div>		

	<script type="text/javascript">
		window.print();
	</script>
</body>
</html>