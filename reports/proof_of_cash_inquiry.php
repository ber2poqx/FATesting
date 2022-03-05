<?php
$path_to_root = '..';
if (!isset($path_to_root) || isset($_GET['path_to_root']) || isset($_POST['path_to_root']))
		die(_("Restricted access"));
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/includes/page/header.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

function get_proof_cash_inquiry($trans_no)
{
	$sql = "SELECT * FROM proof_of_cash WHERE trans_no = '" . $trans_no . "' ORDER BY tran_date DESC";

	return db_query($sql, "could not get all proof of cash");
}

function get_branch_manager()
{
	$sql = "SELECT real_name FROM users WHERE role_id = '19'";
	return db_query($sql, "could not get branch manager");
}

?>

<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<title>Proof of Cash Inquiry</title>
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
		font-size: 12px;
		font-weight: bold;
	}
	.top_bordered td{
		border-top: 2px dashed #000;
		font-size: 15px;
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

	.transdateheader {
		text-align: center;
		margin-top: 05px;
		font-size: 13px;
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
	}

	.foot {
		text-align: center;
		margin-right: 70px;
		font-family: monospace;
	}

	.footer_names{
	    border: 0px solid black;
	    text-align: left;
		padding-left: 150px;
		padding-right: 0px;
	    font-size: 11px;
		font-family: century gothic;		
	}

	.footer_names_part2{
	    border: 0px solid black;
	    text-align: left;
		padding-left: 190px;
		padding-right: 0px;
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
		margin-top: 100px;
	}
</style>
</head>

<?php
	
	$trans_no = $_GET['trans_no'];
	$result = get_proof_cash_inquiry($trans_no);				
	if (db_num_rows($result) > 0)
	{
		while ($myrow=db_fetch($result))
		{

			$tran_date = date('m/d/Y', strtotime($myrow["tran_date"]));

			$one_thousand = price_format($myrow["one_thousand"]);
			$one_thousand_qty = price_format($myrow["one_thousand_qty"]);
			$five_hundred = price_format($myrow["five_hundred"]);
			$five_hundred_qty = price_format($myrow["five_hundred_qty"]);
			$two_hundred = price_format($myrow["two_hundred"]);
			$two_hundred_qty = price_format($myrow["two_hundred_qty"]);
			$one_hundred = price_format($myrow["one_hundred"]);
			$one_hundred_qty = price_format($myrow["one_hundred_qty"]);
			$fifty = price_format($myrow["fifty"]);
			$fifty_qty = price_format($myrow["fifty_qty"]);
			$twenty = price_format($myrow["twenty"]);
			$twenty_qty = price_format($myrow["twenty_qty"]);
			$ten = price_format($myrow["ten"]);
			$ten_qty = price_format($myrow["ten_qty"]);
			$five = price_format($myrow["five"]);
			$five_qty = price_format($myrow["five_qty"]);
			$one = price_format($myrow["one"]);
			$one_qty = price_format($myrow["one_qty"]);
			$twenty_five_cent = price_format($myrow["twenty_five_cent"]);
			$twenty_five_cent_qty = price_format($myrow["twenty_five_cent_qty"]);
			$ten_cent = price_format($myrow["twenty_five_cent"]);
			$ten_cent_qty = price_format($myrow["ten_cent_qty"]);
			$five_cent = price_format($myrow["five_cent"]);
			$five_cent_qty = price_format($myrow["five_cent_qty"]);

			$amount_of_thousand = $myrow["one_thousand"] * $myrow["one_thousand_qty"];
			$total_of_thousand = $myrow["one_thousand"] * $myrow["one_thousand_qty"];

			$amount_of_fivehundred = $myrow["five_hundred"] * $myrow["five_hundred_qty"];
			$total_of_fivehundred = $myrow["five_hundred"] * $myrow["five_hundred_qty"];

			$amount_of_twohundred = $myrow["two_hundred"] * $myrow["two_hundred_qty"];
			$total_of_twohundred = $myrow["two_hundred"] * $myrow["two_hundred_qty"];

			$amount_of_onehundred = $myrow["one_hundred"] * $myrow["one_hundred_qty"];
			$total_of_onehundred = $myrow["one_hundred"] * $myrow["one_hundred_qty"];

			$amount_of_fifty = $myrow["fifty"] * $myrow["fifty_qty"];
			$total_of_fifty = $myrow["fifty"] * $myrow["fifty_qty"];

			$amount_of_twenty = $myrow["twenty"] * $myrow["twenty_qty"];
			$total_of_twenty = $myrow["twenty"] * $myrow["twenty_qty"];

			$subtotalBills = $total_of_thousand + $total_of_fivehundred + $total_of_twohundred + $total_of_onehundred +
			$total_of_fifty + $total_of_twenty;

			$amount_of_ten = $myrow["ten"] * $myrow["ten_qty"];
			$total_of_ten = $myrow["ten"] * $myrow["ten_qty"];

			$amount_of_five = $myrow["five"] * $myrow["five_qty"];
			$total_of_five = $myrow["five"] * $myrow["five_qty"];

			$amount_of_one = $myrow["one"] * $myrow["one_qty"];
			$total_of_one = $myrow["one"] * $myrow["one_qty"];

			$amount_of_twentyfivecent = $myrow["twenty_five_cent"] * $myrow["twenty_five_cent_qty"];
			$total_of_twentyfivecent = $myrow["twenty_five_cent"] * $myrow["twenty_five_cent_qty"];

			$amount_of_tencent = $myrow["ten_cent"] * $myrow["ten_cent_qty"];
			$total_of_tencent = $myrow["ten_cent"] * $myrow["ten_cent_qty"];

			$amount_of_fivecent = $myrow["five_cent"] * $myrow["five_cent_qty"];
			$total_of_fivecent = $myrow["five_cent"] * $myrow["five_cent_qty"];

			$subtotalCoins = $total_of_ten + $total_of_five + $total_of_one + $total_of_twentyfivecent +
			$total_of_tencent + $total_of_fivecent;

			$totalall = $subtotalBills + $subtotalCoins;
		}
	}
?>

<?php
	$brchcode = $db_connections[user_company()]["branch_code"];		
	$compcode = $db_connections[user_company()]["name"];
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
					<label>Proof Of Cash - <?php  echo $brchcode?></label>
				</div>
				<div class="transdateheader">
					<label>Transaction Date : <?php  echo $tran_date?></label>
				</div>
			</h4>
		</div>
		
		
		<div class="row">
			<div class="left" style="width: 100%; padding: 0px; float: left; margin-top: 20px;">			
				<table style="width: 100%; float: left;" cellspacing="0" cellpadding="0">
					<tbody>		
						<tr class="text-center">
							<td style= "border: 1px solid; width: 15%;">Denominations:</td>
							<td style= "border: 1px solid; width: 15%;"></td>
							<td style= "border: 1px solid; width: 15%;">Quantity</td>
							<td style= "border: 1px solid; width: 15%;">Amount</td>
							<td style= "border: 1px solid; width: 15%;">Total</td>
						</tr>
						<tr class="text-center">
							<td style= "border: 1px solid; width: 20%;">Bills</td>
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"></td>
						</tr>
						<tr class="text-center">
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $one_thousand?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $one_thousand_qty?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($amount_of_thousand)?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($total_of_thousand)?></td>
						</tr>
						<tr class="text-center">
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $five_hundred?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $five_hundred_qty?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($amount_of_fivehundred)?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($total_of_fivehundred)?></td>
						</tr>
						<tr class="text-center">
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $two_hundred?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $two_hundred_qty?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($amount_of_twohundred)?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($total_of_twohundred)?></td>
						</tr>
						<tr class="text-center">
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $one_hundred?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $one_hundred_qty?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($amount_of_onehundred)?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($total_of_onehundred)?></td>
						</tr>
						<tr class="text-center">
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $fifty?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $fifty_qty?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($amount_of_fifty)?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($total_of_fifty)?></td>
						</tr>
						<tr class="text-center">
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $twenty?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $twenty_qty?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($amount_of_twenty)?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($total_of_twenty)?></td>
						</tr>
						<tr class="text-center">
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;">SUB TOTAL</td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($subtotalBills)?></td>
						</tr>
						<tr class="text-center">
							<td style= "border: 1px solid; width: 20%;">Coins</td>
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"></td>
						</tr>
						<tr class="text-center">
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $ten?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $ten_qty?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($amount_of_ten)?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($total_of_ten)?></td>
						</tr>
						<tr class="text-center">
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $five?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $five_qty?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($amount_of_five)?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($total_of_five)?></td>
						</tr>
						<tr class="text-center">
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $one?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $one_qty?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($amount_of_one)?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($total_of_one)?></td>
						</tr>
						<tr class="text-center">
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $twenty_five_cent?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $twenty_five_cent_qty?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($amount_of_twentyfivecent)?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($total_of_twentyfivecent)?></td>
						</tr>
						<tr class="text-center">
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $ten_cent?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $ten_cent_qty?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($amount_of_tencent)?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($total_of_tencent)?></td>
						</tr>
						<tr class="text-center">
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $five_cent?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo $five_cent_qty?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($amount_of_fivecent)?></td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($total_of_fivecent)?></td>
						</tr>
						<tr class="text-center">
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;">SUB TOTAL</td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($subtotalCoins)?></td>
						</tr>
						<tr class="text-center">
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;"></td>
							<td style= "border: 1px solid; width: 20%;">TOTAL CASH ON HAND</td>
							<td style= "border: 1px solid; width: 20%;"><?php echo price_format($totalall)?></td>
						</tr>
				    </tbody>					
				</table>
			</div>
		</div>																
	</tbody>
	</table>
	<div>
		<div>		
		<br/><br/><br/><br/>
		<table id="footer">
			<tr>
				<th style="text-align: left; padding-left: 160px;">Prepared By:</th>
				<th style="text-align: left; padding-left: 190px;">Noted By:</th>
			</tr>
			<tr><td>.</td></tr>
			<tr><td>.</td></tr>
			<tr style="height: 1px;">
				<td align=center>__________________________</td>
				<td align=center>__________________________</td>
			</tr>
			<?php
	
				$result = get_branch_manager();				
				if (db_num_rows($result) > 0)
				{
					$myrow=db_fetch($result);
					$real_name = $myrow["real_name"];	
				}
			?>
			<tr>
				<td class="footer_names"><?php echo $_SESSION["wa_current_user"]->name?></td>
				<td class="footer_names_part2"><?php echo $real_name?></td>						
			</tr>
			
		</table>		
	</div>
	<script type="text/javascript">
		window.print();
	</script>
</body></html>
