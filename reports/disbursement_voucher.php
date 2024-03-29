<?php
$path_to_root = '..';
if (!isset($path_to_root) || isset($_GET['path_to_root']) || isset($_POST['path_to_root']))
		die(_("Restricted access"));
	include_once($path_to_root . "/includes/ui.inc");
	include_once($path_to_root . "/includes/page/header.inc");
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
<title>DV Form</title>
<style type="text/css">
	.main{
		width: 8.5in;
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
		font-size: 14;
	}

	@page  {
		margin: 10px;
		padding: 0px 25px;
	}
	@media  print {
	  html, body {

		width: 8.5in;
		height: 11in;
		padding-left: 10px;
		/*padding-right: 15px;*/
		  }
	  /* ... the rest of the rules ... */
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
	.text-params{
		text-align: left;
		font-size: 11px;
		/*font-weight: bold;*/
	}
	.text-center{
		text-align: center;
	}
	.top_bordered td{
		border-top: 1px dashed #000;
		font-size: 11px;
	}
	.bot_bordered td{
		border-bottom: 1px dashed #000;
	}

	.Prepared {
		margin-top: 10px;
	}

	.Merchandise {
		margin-top: 20px;
		text-align: center;
		font-family: century gothic;
	}
	
	.underline_input{
	    border: 0px;
	    text-decoration: underline;
	    text-align: left;
	    font-size: 11px;
	}
	
	.footer_names{
	    border: 0px solid black;
	    text-align: center;
		padding-left: 10px;
		padding-right: 10px;
	    font-size: 11px;
		font-family: century gothic;		
	}
	
	.underline_input_long{
	    border: 0px;
	    text-decoration: underline;
	    text-align: left;
	    width:480px;
	    font-size: 11px;
	}
	.underlined{
	    border: 0px;
	    border-bottom: 1px solid;
	    text-align: left;
	    width:570px;
	    font-size: 11px;
	}
	
	/*
		CSS FOR TABLES
	*/
	.left{
			float: center;
			width: 10%;
	  		padding: 3px;
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
	  padding: 1px;
	}
	
	th {
	  /*text-align: center;*/
	  padding: 1px;
	}

	.text-left {
		font-size: 65%;
		/*font-weight: bold;*/
	}
	
	.table1-headers {
		font-size: 11px;
		/*font-weight: bold;*/
	}

	.text-design {
		font-size: 68%;
	}
	
	.foot2 {
		text-align: center;		
		font-family: century gothic;
	}

	.text-sample {
		font-size: 90%;
		/*font-weight: bold;*/
	}

	.companyDes {
		font-size: 100%;
	}
	
	.BranchName {
		font-size: 75%;
	}

	.CompanyAdd {
		font-size: 65%;
	}

	.datatable {
		font-size: 11px;
	}

	.foot {
		text-align: center;
		margin-right: 70px;
		font-family: Century Gothic;
	}
	#header{
	   font-size: 11px; width: 100%; float: left; border: 0px solid black;
	}
	
	#header td{
	   padding: 2px;
	}
	
	#footer{
		font-size: 10px;			
		width: 100%;
		border: 0px solid;
	}
	/*
	#footer td{
		font-size: 11px;
		padding-top: 40px;
		padding-left: 40px;
		font-family: century gothic;
	}*/
	.footnotes{
		border: 1px solid black;
		width: 90%; 
		font-size: 70%; 
		text-align: left; 
		padding-top: 100px; 
		padding-left: 50px; 
		font-style: italic; 
		/*font-weight: bold; */
	}
	.footnote2{
		width: 90%; 
		font-size: 70%; 
		text-align: left; 
		padding-top: 3px;
		padding-left: 50px; 
		font-style: italic; 
	}
	
</style>
</head>



<?php
	#Query
	function DV_headers($trans_no)
	{
		$sql = "
			SELECT bank.`masterfile`, IFNULL(gl.master_file, dm.name) AS name, bank.`receipt_no`, bank.`trans_date`, bank.`amount`, c.memo_
			FROM ".TB_PREF."`bank_trans` bank 
				LEFT JOIN ".TB_PREF."`gl_trans` gl ON bank.type = gl.type AND bank.trans_no=gl.type_no
				LEFT JOIN ".TB_PREF."`comments` c ON bank.type = c.type AND bank.trans_no = c.id 
				LEFT JOIN ".TB_PREF."`debtors_master` dm ON bank.masterfile = dm.debtor_no
			WHERE bank.`type` = '".ST_BANKPAYMENT."' AND `trans_no` = '$trans_no'";

		return db_query($sql, "DV_headers query could not be retrieved");
	}	

	function get_gl_trans_dis($type, $trans_id)
	{
		set_global_connection();
		$sql = "SELECT gl.*, cm.account_name, IFNULL(refs.reference, '') AS reference, user.real_name, 
				COALESCE(st.tran_date, dt.tran_date, bt.trans_date, grn.delivery_date, gl.tran_date) as doc_date,
				IF(ISNULL(st.supp_reference), '', st.supp_reference) AS supp_reference
		FROM ".TB_PREF."gl_trans as gl
			LEFT JOIN ".TB_PREF."chart_master as cm ON gl.account = cm.account_code
			LEFT JOIN ".TB_PREF."refs as refs ON (gl.type=refs.type AND gl.type_no=refs.id)
			LEFT JOIN ".TB_PREF."audit_trail as audit ON (gl.type=audit.type AND gl.type_no=audit.trans_no AND NOT ISNULL(gl_seq))
			LEFT JOIN ".TB_PREF."users as user ON (audit.user=user.id)
				# all this below just to retrieve doc_date :>
			LEFT JOIN ".TB_PREF."supp_trans st ON gl.type_no=st.trans_no AND st.type=gl.type AND (gl.type!=".ST_JOURNAL." OR gl.person_id=st.supplier_id)
			LEFT JOIN ".TB_PREF."grn_batch grn ON grn.id=gl.type_no AND gl.type=".ST_SUPPRECEIVE." AND gl.person_id=grn.supplier_id
			LEFT JOIN ".TB_PREF."debtor_trans dt ON gl.type_no=dt.trans_no AND dt.type=gl.type AND (gl.type!=".ST_JOURNAL." OR gl.person_id=dt.debtor_no)
			LEFT JOIN ".TB_PREF."bank_trans bt ON bt.type=gl.type AND bt.trans_no=gl.type_no AND bt.amount!=0
				 AND bt.person_type_id=gl.person_type_id AND bt.person_id=gl.person_id
			LEFT JOIN ".TB_PREF."journal j ON j.type=gl.type AND j.trans_no=gl.type_no"

			." WHERE gl.type= ".db_escape($type) 
			." AND gl.type_no = ".db_escape($trans_id)
			." AND gl.amount <> 0"
			." ORDER BY tran_date, counter";

		return db_query($sql, "The gl transactions could not be retrieved");
	}
	
	function Comment($type, $trans_id)
	{
		$sql = "
			SELECT *
			FROM `comments` 
			WHERE `type` = '$type' AND `id` = '$trans_id'";

		return db_query($sql, "DV_headers query could not be retrieved");
	}
?>
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
	 //$trans_no = "1";
	$trans_no = $_REQUEST['trans_num'];
	$type = ST_BANKPAYMENT;
	$trans_id = $trans_no;
	
	$trans_data = get_gl_trans_dis($type, $trans_id);
	$get_data = db_fetch($trans_data);

	$headers_result = DV_headers($trans_no);
	$headers_row = db_fetch($headers_result);

	$name = $headers_row["name"];
	$voucher_num = $headers_row["receipt_no"];
	$amount = abs($get_data["amount"]);
	$date = $headers_row["trans_date"];
	$particular = $headers_row["memo_"];
		
	$null1 = "";

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
		$amnt_in_words = strtoupper(convert_number($amount)). " AND " . strtoupper(convert_number($decimal)) . " CENTS";
	}
	if ( convert_number($amount) == "Zero" )
	{
		$amnt_in_words = strtoupper(convert_number($amount));
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
	<div class="main printable">
		<div>
			<table style="border: 0px; font-family: Calibri; font-size: 9px; width: 97%;">
			<td align=right>Print date: <?php echo Date("m/d/Y")?> | <?php echo Date("h:iA")?></td>
			</table>
		</div>
		
		<div style="width: 100%; text-align: center; padding-top: 0.2in; float: left; border-top: 0.1px solid black">
			<h3 style="margin: 0px">
				<div class="companyDes">
					<p><b>Du Ek Sam, Inc. (<?php echo $compcode?>)</b></p>
				</div>
			</h3>
				
			<div class="CompanyAdd">
				<p><?php echo $comadd?></p>
			</div>
			
			<h4>
				<div class="Merchandise">
					<label>DISBURSEMENT VOUCHER - <?php echo $brchcode?></label>
				</div>			
			</h4>
		</div>
		<table id="header" cellspacing="0" cellpadding="0">
			<tr><td>&nbsp;</td></tr>
			<tr>
				<td align=left class="text-params">Name:</td>
				<th style="width: 50%;" align=left><input type="text" value="<?php echo "$name"?>" class="underline_input_long" readonly></th>
				<td class="text-params">Voucher_#:</td>
				<th align=left><input type="text" value="<?php echo $voucher_num?>" class="underline_input" readonly></th>
			</tr>
			<tr>
				<td align=left class="text-params"></td>
				<th style="width: 50%;" align=left><input type="text" value="<?php echo $null1 ?>" class="underline_input_long" readonly></th>
				<td class="text-params">Date:</td>
				<th align=left><input type="text" value="<?php echo sql2date($date) ?>" class="underline_input" readonly></th>
			</tr>
			<tr>
				<td style="width: 83px;" align=left class="text-params">Amount:</td>
				<th style="width: 87.5%; border: 0px solid; colspan: 2;" align=left><input  type="text" value="<?php echo $amnt_in_words.' - ('. price_format($amount) .')'?>" class="underline_input_long"></th>
			</tr>
			<tr>
				<td style="width: 83px;" align=left class="text-params">Particulars:</td>
				<th style="width: 87.5%; border: 0px solid; colspan: 2;" align=left><input  type="text" value="<?php echo $particular?>" class="underline_input_long"></th>
			</tr>
			<tr><td>&nbsp;</td></tr>
		</table>

						
		<div class="row">
			<div class="left" style="width: 95%; padding: 0px; float: left;">			
				<table style="width: 100%; float: left;" cellspacing="0" cellpadding="0">
					<tbody>						
                        <tr class="table1-headers">
							<th align=left style="border-bottom:0.5px solid;">Code</th>
							<th align=left style="border-bottom:0.5px solid;">Account Name</th>
							<th align=left style="border-bottom:0.5px solid;">MCode</th>
							<th align=left style="border-bottom:0.5px solid;">Masterfile</th>			
							<th align=right style="border-bottom:0.5px solid;">Debit</th>
							<th align=right style="border-bottom:0.5px solid;">Credit</th>	
							
						</tr>
						<?php 	
							
							$result = get_gl_trans_dis($type, $trans_id);								
							if (db_num_rows($result) > 0)
							{
								echo '<tr class="datatable"><td colspan="4" style="padding-top: 5px;" align=right><b> </b></td></tr>';
								$total_deb = $total_cred = 0;
								while($myrow=db_fetch($result))
								{	
									$memo_result = Comment($type, $trans_id);
									$myrow2 = db_fetch($memo_result);

									echo '<tr class="datatable">';
									echo '<td align=left style="border-right:0px solid;">'.($myrow["account"]).'</td>';	
									echo '<td align=left style="border-right:0px solid;">'.($myrow["account_name"]).'</td>';
									echo '<td align=left style="border-right:0px solid;">'.($myrow["mcode"]).'</td>';	
									echo '<td align=left style="border-right:0px solid;">'.($myrow["master_file"]).'</td>';	
									//echo '<td align=left style="border-right:0.5px solid; padding-left: 2px;">'.($myrow2["memo_"]).'</td>';
									if($myrow["amount"] > 0)
									{
										echo '<td align=right style="border-right:0px solid; padding-right: 2px;">'.price_format($myrow["amount"]).'</td>';
										echo '<td align=right style="border-right:0px solid; padding-right: 2px;"> - </td></tr>';
										$total_deb = $total_deb + $myrow["amount"];
									}
									if($myrow["amount"] < 0)
									{
										$this_credit = -$myrow["amount"];
										echo '<td align=right style="border-right:0px solid; padding-right: 2px;"> - </td>';
										echo '<td align=right style="border-right:0px solid; padding-right: 2px;">'.price_format($this_credit).'</td></tr>';
										$total_cred = $total_cred + $this_credit;
									}
								}

								echo '<tr class="top_bordered">
										<td colspan="4" style="padding-top: 5px;" align=right><b>Total </b></td>										
										<td style="text-align: right;"><b>'.price_format($total_deb).'</b></td>										
										<td style="text-align: right;"><b>'.price_format($total_cred).'</b></td>
									</tr>';
							}							
						?>												
				    </tbody>					
				</table>				
			</div>
		</div>																
	</tbody>
</table>
	<div>		
		<br/><br/><br/><br/>
		<table id="footer">
			<tr>
				<th style="text-align: left; padding-left: 15px;">Prepared By:</th>
				<th style="text-align: left; padding-left: 15px;">Checked By:</th>
				<th style="text-align: left; padding-left: 15px;">Approved By:</th>
				<th style="text-align: left; padding-left: 15px;">Received By:</th>
			</tr>
			<tr><td>.</td></tr>
			<tr><td>.</td></tr>
			<tr style="height: 1px;">
				<td align=center>_________________________________</td>
				<td align=center>_________________________________</td>
				<td align=center>_________________________________</td>
				<td align=center>_________________________________</td>
			</tr>
			<tr>
				<td class="footer_names"><?php echo $_SESSION["wa_current_user"]->name?></td>
				<td class="footer_names"><input type="text" style="border: 0px; text-align: center; font-size: 11px; font-family: century gothic; width: 90%;"></td>
				<td class="footer_names"><input type="text" style="border: 0px; text-align: center; font-size: 11px; font-family: century gothic; width: 90%;"></td>	
				<td class="footer_names"><input type="text" style="border: 0px; text-align: center; font-size: 11px; font-family: century gothic; width: 90%;"></td>						
			</tr>
			
		</table>		
	</div>
	
	<script type="text/javascript">
		window.print();
	</script>
</body>
</html>