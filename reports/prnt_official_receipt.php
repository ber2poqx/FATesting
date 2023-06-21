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
<title>To Print Receipt, OFFICIAL RECEIPT</title>
<style type="text/css">
	/*@import url('https://fonts.googleapis.com/css?family=Courier+New&display=swap');
	@font-face {
		  font-family: 'Courier New';
		  font-style: normal;
		  font-weight: 200;
		  font-display: swap;
		  src: url(https://fonts.gstatic.com/l/font?kit=Cn-vJt-LUxZV2ICofzrQFC41xsIia3M&skey=93c2fdf69b410576&v=v10) format('woff2');
		  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
	}*/
	.main{
		width: 16.8cm;
		height: 10.3cm;
		background: #fff;
		margin: 0 auto;
	}
	body{
		width: 100%;
		padding: 20px 0px;
		height: 100%;
		background: #eee;
		margin: 0px;
		font-size: 10px;
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

		width: 16.8cm;
		height: 10.3cm;
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
		height: 10px; 
		font-size: 10px;
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

function get_or_trans($or_num, $trans_type)
{
		set_global_connection();
		
		$sql = "SELECT BT.*, DM.name AS customer, DM.tax_id, DM.address, CM.memo_ AS remarks, USR.real_name AS cashier 
				FROM " . TB_PREF . "bank_trans BT 
				LEFT JOIN " . TB_PREF . "debtors_master DM ON BT.person_id = DM.debtor_no
				LEFT JOIN " . TB_PREF . "comments CM ON BT.trans_no = CM.id AND BT.type = CM.type
                LEFT JOIN " . TB_PREF . "users USR ON BT.cashier_user_id = USR.id
				WHERE BT.ref = '" . $or_num . "' AND BT.type = '" . $trans_type . "'  ";	
		
		return db_query($sql, "No transactions were returned");
}

function convert_number($number)
{
	if (($number < 0) || ($number > 99999999))
	{
		return "$number";
	}

	// $Tn = floor($number / 1000000000); /* Billions (Terra) */
	// $number -= $Tn * 1000000000;
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
	// $or_num = "AGOR-SI00222021";
	$or_num = $_REQUEST['reference']; 
	//$dlvry_tag = 0;
	$or_result = get_or_trans($or_num,$trans_type = ST_BANKDEPOSIT);
	
	$myrow=db_fetch($or_result);

	$amount = $myrow["amount"];
	$lessVAT = "";
	$noVAT = "";
	$date = $myrow["trans_date"];
	$name = strtoupper($myrow["customer"]);
	$TIN = $myrow["tax_id"];
	$address = $myrow["address"];
	$business_style = "";
	$payment_for = $myrow["remarks"];
	$cashier = strtoupper($myrow["cashier"]);
	//$amount_in_words = "One Hundred Only";
	$taxable = "Y";


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
		$amnt_in_words = strtoupper(convert_number($amount)) . "";
	}
	else if ($decimal != 0 && convert_number($amount) != "Zero")
	{
		$amnt_in_words = strtoupper(convert_number($amount)). " Pesos AND " . $decimal . "/100 Cents";
	}
	else if ( convert_number($amount) == "Zero" && $decimal != 0)
	{
		$amnt_in_words = $decimal . "/100 Cents";
	}
	else
	{
		$amnt_in_words = "ZERO AMOUNT";
	}
		
?>

<body>
	

	<div class="main printable">

		<?php 
			if($taxable == "Y")
			{
				echo '<div class="line1" style="height: 0.85cm"></div>';  // header height
				echo '<div class="row">
							<div class="line1" style="height: 0.1cm"></div>
							<div class="line1" style="width: 2.55cm;"></div>'; // Total Sales (Vat Inclusive) left indent
				echo '		<div class="line1" style="width: 2cm; text-align: right;">'.price_format($amount).'</div>';// field width
				echo '</div>';

				echo '';  // less VAT newline
				echo '<div>
							<div class="line1" style="width: 0px; height: 0.25cm"></div>
							<div class="line1" style="width: 2.55cm;"></div>'; // less VAT left indent
				echo '		<div class="line1" style="width: 2cm; text-align: right;">'.price_format($lessVAT).'</div>';// field width
				echo '</div>';

				echo '';  // no VAT newline
				echo '<div>
							<div class="line1" style="width: 0px; height: 0.00cm"></div>
							<div class="line1" style="width: 2.55cm;"></div>'; // Total Sales (Vat Inclusive) left indent
				echo '		<div class="line1" style="width: 2cm; text-align: right;">'.price_format($noVAT).'</div>';// field width
				echo '</div>';

				echo '';  // date newline
				echo '<div>
							<div class="line1" style="width: 0px; height: 0.7cm"></div>
							<div class="line1" style="width: 12.65cm;"></div>'; // date left indent
				echo '		<div class="line1" style="width: 2.8cm; text-align: center;">'.$date.'</div>';// field width
				echo '</div>';

				echo '';  // name newline
				echo '<div>
							<div class="line1" style="width: 0px; height: 0.5cm"></div>
							<div class="line1" style="width: 7.75cm;"></div>'; // name left indent
				echo '		<div class="line1" style="width: 4.5cm; text-align: left;">'.$name.'</div>';// field width
				// echo '<div class="row">
				// 			<div class="line1" style="width: 1.3cm;"></div>'; // TIN left indent
				// echo '		<div class="line1" style="width: 1.8cm; text-align: left;">'.$TIN.'</div>';// field width
				echo '</div>';

				echo '';  // amount due and address newline
				echo '<div>
							<div class="line1" style="width: 0px; height: 0.45cm"></div>
							<div class="line1" style="width: 2.65cm;"></div>'; // amount due left indent
				echo '		<div class="line1" style="width: 2cm; text-align: right;">'.price_format($amount).'</div>';// field width
				echo '
							<div class="line1" style="width: 2.5cm;"></div>'; // address left indent
				echo '		<div class="line1" style="width: 5.9cm; text-align: left;">'.$address.'</div>';// field width
				echo '</div>';

				echo '';  // business style newline
				echo '<div>
							<div class="line1" style="width: 0px; height: 0.5cm"></div>
							<div class="line1" style="width: 7.65cm;"></div>'; // date left indent
				echo '		<div class="line1" style="width: 6.2cm; text-align: left;">'.$business_style.'</div>';// field width
				echo '</div>';

				echo '';  // amount in words newline
				echo '<div>
							<div class="line1" style="width: 0px; height: 0.5cm"></div>
							<div class="line1" style="width: 4.85cm;"></div>'; // amount in words left indent
				echo '		<div class="line1" style="width: 9.5cm; text-align: left;">'.$amnt_in_words.'</div>';// field width
				echo '</div>';

				//echo '<div class="line1" style="height: 0.2cm"></div>';  // amount_in_digit and payment_for newline
				echo '<div>
							<div class="line1" style="width: 0px; height: 0.7cm"></div>
							<div class="line1" style="width: 5.4cm;"></div>'; // amount_in_digit left indent
				echo '		<div class="line1" style="width: 2cm; text-align: right;">'.price_format($amount).'</div>';// field width				
				echo '		<div class="line1" style="width: 3.8cm;"></div>'; // payment_for left indent
				echo '		<div class="line1" style="width: 4cm; text-align: left;">'.$payment_for.'</div>';// field width
				echo '</div>';

				echo '';  // cashier in words newline
				echo '<div>
							<div class="line1" style="width: 0px; height: 0.2cm"></div>
							<div class="line1" style="width: 11.85cm;"></div>'; // cashier left indent
				echo '		<div class="line1" style="width: 3.5cm; text-align: left;">'.$cashier.'</div>';// field width
				echo '</div>';				
			}

		?>

	<script type="text/javascript">
		window.print();
	</script>
</body>
</html>