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
<!-- DONE - CR CASH PAYMENT
	 PENDING - BANK PAYMENT, CHECK PAYMENT ONLINE PAYMENT -->

<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title>To Print, Collection Receipt</title>
<style type="text/css">
	.main{
		width: 8.5in;
		height: 5in;
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
		font-family: Calibri;
		/*font-weight: bold;*/
		color: black;
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
		font-family: Calibri;
	}

	td{
		/*border: 1px solid;*/
		vertical-align: top;
	}

	tr{
		padding-bottom: 0.15cm;
	}

</style>
</head>
<?php
/*
** Function: convert_number
** Arguments: int
** Returns: string
** Description:
** Converts a given integer (in range [0..1T-1], inclusive) into
** alphabetical format ("one", "two", etc.).
*/
function convert_number($number)
{
	if (($number < 0) || ($number > 999999999999))
	{
		return "$number";
	}

	$Tn = floor($number / 1000000000); /* Billions (Terra) */
	$number -= $Tn * 1000000000;
	$Gn = floor($number / 1000000); /* Millions (giga) */
	$number -= $Gn * 1000000;
	$kn = floor($number / 1000); /* Thousands (kilo) */
	$number -= $kn * 1000;
	$Hn = floor($number / 100); /* Hundreds (hecto) */
	$number -= $Hn * 100;
	$Dn = floor($number / 10); /* Tens (deca) */
	$n = $number % 10; /* Ones */

	$res = "";

	if ($Gn)
	{
		$res .= convert_number($Gn) . " Million";
	}

	if ($kn)
	{
		$res .= (empty($res) ? "" : " ") .
		convert_number($kn) . " Thousand";
	}

	if ($Hn)
	{
		$res .= (empty($res) ? "" : " ") .
		convert_number($Hn) . " Hundred";
	}

	$ones = array("", "One", "Two", "Three", "Four", "Five", "Six",
		"Seven", "Eight", "Nine", "Ten", "Eleven", "Twelve", "Thirteen",
		"Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eightteen",
		"Nineteen");
	$tens = array("", "", "Twenty", "Thirty", "Fourty", "Fifty", "Sixty",
		"Seventy", "Eigthy", "Ninety");

	if ($Dn || $n)
	{
		if (!empty($res))
		{
			$res .= " and ";
		}

		if ($Dn < 2)
		{
			$res .= $ones[$Dn * 10 + $n];
		}
		else
		{
			$res .= $tens[$Dn];

			if ($n)
			{
				$res .= "-" . $ones[$n];				
			}
		}
	}

	if (empty($res))
	{
	$res = "Zero";
	}

	return $res;
}

?> 



<?php	

	//$cr_ref = "CR-202100001";
	$cr_ref = $_REQUEST['reference'];
	$cr_result = get_cr_trans($cr_ref,$trans_type = ST_CUSTPAYMENT);
	
	$myrow=db_fetch($cr_result);

	// $trans_no = "7";
	// $date = "10/04/2021";
	// $amount = "562582";
	// $name = "Davilla, Karen";
	// $tin = "65844568";
	// $address = "Union Poblacion, Dauis, Bohol";
	// $remarks = "payment for Vivo v15";
	// $bankbranch = "BDO-Tagb.";
	// $bankname = "BDO";
	// $check_num = "16986558";
	// $check_date = "10/10/2021";
	// $cashier = "Alex Gonzaga";
	// $pay_type = "Cash";

	$trans_no = $myrow["trans_no"];
	$date = $myrow["tran_date"];
	$amount = $myrow["ov_amount"];
	$name = $myrow["name"];
	$tin = $myrow["tax_id"];
	$address = $myrow["address"];
	$remarks = strtoupper($myrow["memo_"]);
	$bankbranch = $myrow["bank_branch"];
	$bankname = "BDO";
	$check_num = $myrow["check_no"];
	$check_date = $myrow["check_date"];
	$cashier = strtoupper($myrow["real_name"]);
	$pay_type = $myrow["pay_type"];


	$whole = intval($amount); /* check for centavo amount */
	$decimal = 0;

	if ($amount <> $whole)
	{
		$decimal1 = $amount - $whole;
		$decimal2 = round($decimal1, 2);
		$decimal = substr($decimal2, 2);
	}

	if ($decimal == 0 && convert_number($amount) != "Zero")
	{
		$amnt_in_words = strtoupper(convert_number($amount)) . " PESOS ONLY";
	}
	if ($decimal != 0 && convert_number($amount) != "Zero")
	{
		$amnt_in_words = strtoupper(convert_number($amount)). " AND " . $decimal . "/100 PESOS ONLY";
	}
	if ( convert_number($amount) == "Zero" )
	{
		$amnt_in_words = strtoupper(convert_number($amount));
	}
	
?>



<body>
	

	<div class="main printable">

		
		<div style="height: 2cm"></div> <!-- header height -->


		<div class="row">
			<div class="line1" style="width: 15.9cm;"></div> <!-- date Left indent -->

			<div class="line1" style="width: 4.3cm; text-align: center;"><?php echo $date?></div> <!-- date position -->
			
		</div>


		<div style="height: 0.5cm"> </div> <!-- Vertical height -->


		<div>
			<div class="line1" style="width: 4cm;"></div> <!-- Name left indent -->

			<div class="line1" style="width: 11.5cm; text-align: left; text-transform: uppercase;"><?php echo $name?></div> <!-- name position -->

			<div class="line1" style="width: 1.5cm;"></div> <!-- tin left indent -->

			<div class="line1" style="width: 3cm; text-align: left;"><?php echo $tin?></div> <!-- tin position -->

		</div>


		<div style="height: 0.2cm"> </div> <!-- Vertical height -->


		<div>
			<div class="line1" style="width: 3.7cm; padding-top: 0px;padding-bottom: 0px"></div> <!-- Address Left indent -->

			<div class="line1" style="width: 14.5cm; padding-top: 0px;padding-bottom: 0px; text-transform: uppercase;"><?php echo $address?></div> <!-- address position -->
		</div>

		<div style="height: 0.1cm"> </div> <!-- Vertical height -->

		<div>
			<div class="line1" style="width: 4cm; padding-top: 0px;padding-bottom: 0px"></div> <!-- bussiness style Left indent -->

			<div class="line1" style="width: 14.5cm; padding-top: 0px;padding-bottom: 0px; text-transform: uppercase;"></div> <!-- bussiness style position -->
		</div>

		<div style="height: 0.1cm"> </div> <!-- Vertical height -->

		<div>
			<div class="line1" style="width: 1.5cm; padding-top: 0px;padding-bottom: 0px"></div> <!-- amount in words Left indent -->

			<div class="line1" style="width: 17.7cm; padding-top: 0px;padding-bottom: 0px;"><?php echo $amnt_in_words?></div> <!-- amount in words position -->
		</div>

		<div style="height: 0.15cm"> </div> <!-- Vertical height -->

		<div>
			<div class="line1" style="width: 1.5cm; padding-top: 0px;padding-bottom: 0px"></div> <!-- amount Left indent -->

			<div class="line1" style="width: 3cm; padding-top: 0px; text-align: right; padding-bottom: 0px;"><?php echo price_format($amount)?></div> <!-- amount position -->

			<div class="line1" style="width: 3.9cm; padding-top: 0px;padding-bottom: 0px"></div> <!-- remarks Left indent -->

			<div class="line1" style="width: 9cm; padding-top: 0px;padding-bottom: 0px;"><?php echo $remarks?></div> <!-- remarks position -->
		</div>



		<?php 

			if($pay_type == "Cash")
			{
				echo '<div style="height: 2.6cm"> </div>';
				echo '<div>';
					echo '<div class="line1" style="width: 17.2cm; padding-top: 0px;padding-bottom: 0px;"></div>'; 

					echo '<div class="line1" style="width: 2.5cm; text-align: right;padding-top: 0px;padding-bottom: 0px;">'.price_format($amount).'</div>';
				echo '</div>';

				echo '<div style="height: 0.2cm"> </div>';
				echo '<div>';
					echo '<div class="line1" style="width: 17.2cm; padding-top: 0px;padding-bottom: 0px;"></div>'; 

					echo '<div class="line1" style="width: 2.5cm; text-align: right;padding-top: 0px;padding-bottom: 0px;">'.price_format($amount).'</div>';
				echo '</div>';
			}			

		?>		
		<div style="height: 0.7cm"> </div>
		<div>
			<div class="line1" style="border: 0px solid; width: 14.9cm;"></div> <!-- LEFT SPACING OF CASHIER NAME -->

			<div class="line1" style="border: 0px solid; width: 4.85cm; text-align: center; text-transform: uppercase;"><?php echo $cashier ?></div> <!-- CASHIER NAME POSITION -->
		</div>	

	</div>
	<script type="text/javascript">
		window.print();
	</script>
</body>
</html>