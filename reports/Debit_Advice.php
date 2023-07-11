
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
<title>RR Branch</title>
<title>DEBIT ADVICE</title>
<style type="text/css">
	.main{
		width: 8.3in;
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
		font-size: 14px;
	}
	
	*{
		font-family: Calibri;
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
		font-size: 14px;
		/*font-weight: bold;*/
	}
	.ReportTitle{
		font-size: 20px;
	}
	.text-center{
		text-align: center;
	}
	.top_bordered td{
		border-top: 1px dashed #000;
		font-size: 14px;
	}
	.bot_bordered td{
		border-bottom: 1px dashed #000;
	}



	@page  {
		margin: 0;
		padding: 0px 25px;
	}
	@media  print {
	  html, body {

		width: 8.5in;
		height: 11.5in;
		padding-left: 5px;
		padding-right: 5px;
		  }
	  /* ... the rest of the rules ... */
	}
	.underline_input{
	    border: 0px;
	    text-decoration: underline;
	    text-align: left;
	    width:170px;
	    font-size: 14px;
	}
	
	.footer_names{
	    border: 0px solid black;
	    text-align: center;
		padding-left: 10px;
		padding-right: 10px;
	    font-size: 14px;
		font-family: Calibri;		
	}
	
	.underline_input_long{
	    border: 0px solid;
	    text-decoration: underline;
	    text-align: left;
	    font-size: 14px;
	}
	.underlined{
	    border: 0px;
	    border-bottom: 1px solid;
	    text-align: left;
	    width:570px;
	    font-size: 14px;
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
	  border-top: 0px solid black;
    }

	td {
	  /*text-align: center;*/
	  padding: 2px;
	  padding-left: 15px;
	  font-size: 14px;

	}
	
	th {
	  /*text-align: center;*/
	  padding: 1px;
	}

	.text-left {
		font-size: 65%;
		font-weight: bold;
	}
	
	.table1-headers {
		font-size: 14px;
		font-weight: bold;
		border-top:1px solid;
	}

	.text-design {
		font-size: 68%;
	}
	
	.foot2 {
		text-align: center;		
		font-family: Calibri;
	}

	.text-sample {
		font-size: 90%;
		font-weight: bold;
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
		font-size: 14px;
	}

	.datatable-entry {
		font-size: 14px;
	}

	.foot {
		text-align: center;
		margin-right: 70px;
		font-family: monospace;
	}
	#header{
	   font-size: 14px; width: 100%; float: left; border: 0px solid black;
	}
	
	#header td{
	   padding: 2px;
	}
	
	#footer{
		font-size: 14px;			
		width: 100%;
		border: 0px solid;
	}
	/*
	#footer td{
		font-size: 11px;
		padding-top: 40px;
		padding-left: 40px;
		font-family: Calibri;
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
	function Comment($type, $trans_id)
	{
		$sql = "
			SELECT *
			FROM `comments` 
			WHERE `type` = '$type' AND `id` = '$trans_id'";

		return db_query($sql, "DV_headers query could not be retrieved");
	}
/*
function get_comments($transno, $type)
{
	set_global_connection();

	$sql = "SELECT *  FROM ".TB_PREF."comments WHERE type = $type AND id = $transno";

	return db_query($sql, "The get_comments query could not be retrieved");
}*/

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

function get_journal_trans($type,$trans_id)
{
	$sql = "SELECT * FROM `journal` WHERE type = '$type' AND trans_no = '$trans_id'";

	return db_query($sql, "get_journal_trans query could not be retrieved");
}



function searchForId($brcode, $db_connections) {
   foreach ($db_connections as $key => $val) {
       if ($val['branch_code'] === $brcode) {
           return $key;
       }
   }
   return null;
}

function get_comp_id1($branch_code) {

	global $db_connections;
	$company_id = '';

	for ($i = 0; $i < count($db_connections); $i++) {
		if ($db_connections[$i]['branch_code'] == $branch_code) {
			$company_id = $i;
			break;
		}
	}
	return $company_id;
}

function get_branch_gl_trans($trans_type,$trans_num,$limit = '')
{
	$sql =" SELECT gl.account, cm.account_name, gl.amount, usr.real_name
			FROM ".TB_PREF."gl_trans gl 
			LEFT JOIN ".TB_PREF."chart_master cm ON gl.account = cm.account_code
			LEFT JOIN ".TB_PREF."audit_trail audit ON gl.type = audit.type AND gl.type_no = audit.trans_no AND NOT ISNULL(gl_seq)
			LEFT JOIN ".TB_PREF."users usr ON audit.user = usr.id
			WHERE gl.type = ".db_escape($trans_type)." AND gl.type_no = '$trans_num' ";
	if($limit != '')
	{
		$sql .= " LIMIT '$limit' ";
	}

	return db_query($sql, "get_branch_gl_trans query could not be retrieved");
}

function get_receiving_branch($transnum, $type)
{
	$sql =" SELECT *  FROM ".TB_PREF."gl_trans WHERE amount>0 AND type = '$type' AND type_no = '$transnum' ";

	return db_query($sql, "get_receiving_branch query could not be retrieved");
}

function get_to_branch_gl_trans($refnum,$comp_id)
{
	set_global_connection($comp_id);

	$sql = "    SELECT bi.sug_mcode AS account, cm.account_name, bi.amount
				FROM ".TB_PREF."bank_interbranch_trans bi
				LEFT JOIN ".TB_PREF."chart_master cm ON bi.sug_mcode = cm.account_code
				WHERE ref_no LIKE '$refnum' ";

	return db_query($sql, "get_to_branch_gl_trans query could not be retrieved");
}

?> 

<?php
	// $rr_num = "AGOR-RRBR000012021";
	$trans_num = $_REQUEST['trans_num'];
	$code = $_REQUEST['doc'];
	$IB = $_REQUEST['interb'];
	
	if($code == "DE"){
		$trans_type = ST_BANKPAYMENT;
	}
	else if ($code == "JV"){
		$trans_type = ST_JOURNAL;
	}
	
	$res = get_gl_trans($trans_type,$trans_num);
	$myrow3=db_fetch($res);

	$com = Comment($trans_type, $trans_num);
	$commentrow = db_fetch($com);
	
	$amount = abs($myrow3["DA_amount"]);
	$to_branch = "Head Office";
	$Date = sql2date($myrow3["tran_date"]);
	//$Amount_in_words = convert_number($Amount_in_digit);
	$refnum = $myrow3["reference"];
	$JVnum = $myrow3["receipt_no"];

	if($code == "JV")
	{
		$journ_res = get_journal_trans($trans_type,$trans_num);
		$journalrow = db_fetch($journ_res);

		$result1 = get_branch_gl_trans($trans_type,$trans_num);
		$myrow4 = db_fetch($result1);

		$amount = abs($journalrow["amount"]);
		$Date = sql2date($journalrow["doc_date"]);
		$refnum = $journalrow["reference"];
		$JVnum = $journalrow["trans_no"];
		$prepared_by = $myrow4["real_name"];
		$reviewed_by = "";
		$approved_by = "";
	}
	else if($code == "DE")
	{ 
		$res = get_gl_trans($trans_type,$trans_num);
		$myrow3=db_fetch($res);

		$amount = abs($myrow3["DA_amount"]);
		$Date = sql2date($myrow3["tran_date"]);
		$refnum = $myrow3["reference"];
		$JVnum = $myrow3["receipt_no"];
		$prepared_by = $myrow3["prepared_by"];
		$reviewed_by = $myrow3["reviewed_by"];
		$approved_by = $myrow3["approved_by"];
	}

	$suggested_entry_code = $IB;

	if($IB == 1){
		$entry_title = "ACCOUNTING ENTRY - Originating Branch";

			
	}
	else{
		$entry_title = "ACCOUNTING ENTRY";
	}

	$to_branch_result = get_receiving_branch($trans_num,$trans_type);
	$to_branch_row = db_fetch($to_branch_result);

	$brCode = $to_branch_row["mcode"];

	$comp_id = get_comp_id1($brCode);
	
	$to_branch = $brCode;	
	//$Amount_in_words = convert_number($Amount_in_digit);	
	$payee = $brCode;
	$particulars = $commentrow["memo_"];


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
		$Amount_in_words = strtoupper(convert_number($amount)) . " PESOS";
	}
	else if ($decimal != 0 && convert_number($amount) != "Zero")
	{
		$Amount_in_words = strtoupper(convert_number($amount)). " PESOS AND " . strtoupper(convert_number($decimal)) . " CENTS";
	}
	else if ( convert_number($amount) == "Zero" )
	{
		$Amount_in_words = strtoupper(convert_number($amount));
	}

?>

<?php
	$brchcode = $db_connections[user_company()]["branch_code"];
	$brchcode = $db_connections[user_company()]["host"];
	//echo '({"branchcode":"'.$brcode.'"})';
			
	$compcode = $db_connections[user_company()]["name"];
	//$brscode = get_company_pref("name")

	$comadd =  $db_connections[user_company()]["postal_address"];
	//$comadd = get_company_pref("postal_address")
	$comadd = get_company_pref("postal_address");

	$testing = get_company_pref("name");
?>

<body>
	<div class="main printable">
		<div>
			<table style="border: 0px; font-family: monospace; font-size: 9px; width: 100%;">
			<td align=right>Print date: <?php echo Date("m/d/Y")?> | <?php echo Date("h:iA")?></td>
			</table>
		</div>
		
		<div style="width: 100%; text-align: center; padding-top: 0.2in; float: left; border-top: 0.1px solid black">
			<h3 style="margin: 0px">
				<div class="companyDes">
					<p><b>Du Ek Sam, Inc.</b></p>
				</div>
			</h3>
			
			<div class="BranchName">
				<p><b><?php  echo $compcode .' - '.$brchcode?></b></p>
			</div>			
				
			<div class="CompanyAdd">
				<p><?php echo $comadd?></p>
			</div>
			
			
			<h3>
				<div class="ReportTitle">
					<label ">DEBIT ADVICE</label>
				</div>			
			</h4>
			</h3>
		</div>
		<table id="header" cellspacing="0" cellpadding="0">
			<tr><td>&nbsp;</td></tr>
			<tr>
				<td align=left class="text-params" style="">To:</td>
				<th align=left><input style="width:500px; padding-left:40px;" type="text" value="<?php echo strtoupper($to_branch)?>" class="underline_input_long" readonly></th>
				<td class="text-params">Date:</td>
				<th align=left><input type="text" value="<?php echo $Date?>" class="underline_input" readonly></th>
			</tr>
		</table>
		<table id="header" cellspacing="0" cellpadding="0">
			<tr>
				<td align=left class="text-params" style="width: 50px;">Amount:</td>
				<td align=left style="white-space: normal"><u><?php echo $Amount_in_words.' - (Php '. price_format($amount) .' )'?></u></td>							
			</tr>
			<tr>
				<td align=left class="text-params"></td>
			</tr>
		</table>		
		<div style="height: 18px"></div>				
		<div class="row">
			<div class="left" style="width: 100%; padding: 0px; float: left;">			
				<table style="width: 100%; float: left;" cellspacing="0" cellpadding="0">
					<tbody>						
                        <tr >
							<th colspan= "2" align=left style="font-weight: bold; padding-bottom: 7px; font-size: 14px; border-top: 1px solid black">We have debited today Home Office Current Account for the following:</th>
						</tr>
						<tr >							
							<td style="width: 50px;"align=left class="text-params">JV#:</td>							
							<td align=left><?php echo '<u>'.$JVnum.'</u>'; ?></td>
						</tr>
						<tr >							
							<td style="width: 50px;"align=left class="text-params">Ref#:</td>							
							<td align=left><?php echo '<u>'.$refnum.'</u>'; ?></td>
						</tr>
						<tr >								
							<td style="width: 50px;"align=left class="text-params">Payee:</td>
							<td align=left><?php echo '<u>'.strtoupper($payee).'</u>'; ?></td
						</tr>
						<tr >								
							<td style="width: 50px;"align=left class="text-params"></td>
							<td align=left></td
						</tr>
						<tr >	    
						<tr >
							<td style="width: 50px;"align=left class="text-params">Particulars:</td>
							<td align=left style="white-space: normal"><?php echo '<u>'.$particulars.'</u>'; ?></td>							
						</tr>										
				    </tbody>					
				</table>				
			</div>		
			
			<div class="left" style="width: 100%; padding: 0px; float: left;font-size: 100%; margin-top: 10px;">			
				<table style="width: 100%; float: left;" cellspacing="0" cellpadding="0">
					<tbody>
						<tr class="table1-headers">				
							<th align=left style="padding-bottom: 7px;width: 60%; padding-left: 5px;"></th>
							<th align=center style="padding-bottom: 7px;border-left: 1px dotted gray; width: 20%; padding-right: 5px;">DEBIT</th>
							<th align=center style="padding-bottom: 7px;border-left: 1px dotted gray; width: 20%; padding-right: 5px;">CREDIT</th>					
						</tr>
						
						<?php				  
							$type = $trans_type;
							$trans_num_result = get_gl_trans($type,$trans_num);
							
							if($code == "JV")
							{
								$trans_num_result = get_branch_gl_trans($type,$trans_num);
								
							}
							else
							{
								$trans_num_result = get_gl_trans($type,$trans_num);
																
							}							
							
							if($suggested_entry_code == "1")
							{
								$sugg_entry_result = get_to_branch_gl_trans($refnum,$comp_id);
							}

							//$gl_data_res = get_branch_gl_trans($trans_type,$trans_num);
							//$datarow = db_fetch($gl_data_res);
							
							$acct_code = '';
							$acct_name = '';
							$debit = '';
							$credit = '';
								
							if($suggested_entry_code == "1")
							{
								echo '<tr class="datatable-entry">';					      
								echo '<td align=left style="width: 60%; padding-left: 5px;font-weight: bold;border-top: 1px solid gray">SUGGESTED ENTRY - Receiving Branch</td>';
								echo '<td align=right style="border-left: 1px dotted gray; width: 20%; padding-right: 5px;border-top: 1px solid gray"></td>';							      
								echo '<td align=right style="border-left: 1px dotted gray; width: 20%; padding-right: 5px;border-top: 1px solid gray"></td>';
								echo '</tr>';
																										
								$rowcount = 0;
								while($transrow1=db_fetch($sugg_entry_result))
								{
									
									$acct_code = $transrow1["account"];
									$acct_name = $transrow1["account_name"];

									if($transrow1["amount"]==0)
									{
										$debit = 0;
										$credit = 0;
									}
									else if($transrow1["amount"]>0)
									{
										$debit = price_format($transrow1["amount"]);
										$credit = '-';
									}
									else if($transrow1["amount"]<0)
									{
										$debit = '-';
										$credit = price_format(-$transrow1["amount"]);
									}
									$rowcount = $rowcount+1;
									echo '<tr class="datatable">';							      
									echo '<td align=left style="width: 60%; padding-left: 15px;">'.$acct_code.' - '.$acct_name.'</td>';
									echo '<td align=right style="border-left: 1px dotted gray; width: 20%; padding-right: 5px;">'.$debit.'</td>';							      
									echo '<td align=right style="border-left: 1px dotted gray; width: 20%; padding-right: 5px;">'.$credit.'</td>';
									echo '</tr>';
								}
									
								if($rowcount==0)
								{
									echo '<tr class="datatable">';							      
									echo '<td align=left style="width: 60%; padding-left: 15px;">000000 - NO SUGGESTED ACCOUNT</td>';
									echo '<td align=right style="border-left: 1px dotted gray; width: 20%; padding-right: 5px;">0.00</td>';							      
									echo '<td align=right style="border-left: 1px dotted gray; width: 20%; padding-right: 5px;">0.00</td>';
									echo '</tr>';
								}	
											/*
									echo '<tr class="datatable">';							      
									echo '<td align=left style="width: 60%; padding-left: 15px;">000000 - NO ACCOUNT and rowcount = '.$rowcount.'</td>';
									echo '<td align=right style="border-left: 1px dotted gray; width: 20%; padding-right: 5px;">0.00</td>';							      
									echo '<td align=right style="border-left: 1px dotted gray; width: 20%; padding-right: 5px;">0.00</td>';
									echo '</tr>';*/
							}

							echo '<tr class="datatable-entry">';							      
							echo '<td align=left style="width: 60%; padding-left: 5px;font-weight: bold; bold;border-top: 1px solid gray; padding-top: 15px;">'.$entry_title.'</td>';
							echo '<td align=right style="border-left: 1px dotted gray; width: 20%; padding-right: 5px;border-top: 1px solid gray; padding-top: 15px;"></td>';							      
							echo '<td align=right style="border-left: 1px dotted gray; width: 20%; padding-right: 5px;border-top: 1px solid gray; padding-top: 15px;"></td>';
							echo '</tr>';

							while($transrow=db_fetch($trans_num_result))
							{
								$acct_code = $transrow["account"];
								$acct_name = $transrow["account_name"];

								if($transrow["amount"]==0)
								{
									$debit = 0;
									$credit = 0;
								}
								else if($transrow["amount"]>0)
								{
									$debit = price_format($transrow["amount"]);
									$credit = '-';
								}
								else if($transrow["amount"]<0)
								{
									$debit = '-';
									$credit = price_format(-$transrow["amount"]);
								}

								echo '<tr class="datatable">';							      
								echo '<td align=left style="width: 60%; padding-left: 15px;">'.$acct_code.' - '.$acct_name.'</td>';
								echo '<td align=right style="border-left: 1px dotted gray; width: 20%; padding-right: 5px;">'.$debit.'</td>';							      
								echo '<td align=right style="border-left: 1px dotted gray; width: 20%; padding-right: 5px;">'.$credit.'</td>';
								echo '</tr>';
							}
							/*
							while ($transrow=db_fetch($trans_num_result))
							{
								$type_no = $transrow["trans_no"];

								$result = get_rr_supplier_gl($type_no,$type);				
															
								while ($myrow2=db_fetch($result))
								{	
									if (db_num_rows($result) > 0)
									{
										//for mcode and masterfile
    									$reference1 = $_GET['reference'];
    									$rrbranch_header = get_mt_rrbranch_header($reference1);
										$mccode = $rrbranch_header["mt_header_fromlocation"];
										$masterfile = get_db_location_name($mccode);
										//end//

										$counter = $counter + 1;
										$credit = $debit = 0;
										if ($myrow2['amount'] > 0 ) 
    										$debit += $myrow2['amount'];
    									else 
    										$credit += $myrow2['amount'];

										echo '<tr class="datatable">';	
										echo '<td align=center style="border-right:0.5px solid; padding-left: 5px;">'.($counter).'</td>';
										echo '<td align=left style="border-right:0.5px solid; padding-left: 5px;">'.($myrow2["account"]).'</td>';
										echo '<td align=left style="border-right:0.5px solid; padding-left: 5px;">'.($myrow2["account_name"]).'</td>';
										echo '<td align=left style= "border-right: 0.5px solid; padding-left: 5px;">'.($myrow2["mcode"]).'</td>';							      
										echo '<td align=left style= "border-right: 0.5px solid; padding-left: 5px;">'.($myrow2["master_file"]).'</td>';
										echo '<td align=right style="border-right:0.5px solid; padding-right: 5px;">'.($debit<=0?"-":price_format($debit)).'</td>';							      
										echo '<td align=right style="padding-right: 5px;">'.($credit==0?"-":price_format(-$credit)).'</td>';
										echo '</tr>';
										//end_row();

										$totaldeb += $debit;
										$totalcrid += -$credit;
									}
									else
									{
										display_note(_("There are no line items on this dispatch."), 1, 2);
									}									
								} 								
							}	
							*/
							
							//end while there are line items to print out
								
																					
						?>
					</tbody>
				</table>
				<table style="width: 100%;font-size: 7px; border-bottom: 1px solid black">
					<td></td>	
				</table>				
			</div>				
				
				<!--div class="footnote2">
					<?php 
						
				    ?>
				</div-->
		</div>																
	</tbody>
</table>
	<div>		
		<br/><br/>
		<table id="footer">
			<tr>
				<th></th>
				<th align=left style="text-align: left; padding-left: 15px;">Prepared By:</th>
				<th></th>
				<th style="text-align: left; padding-left: 15px;">Reviewed By:</th>
				<th></th>
				<th style="text-align: left; padding-left: 15px;">Approved By:</th>
				<th></th>
			</tr>
			<tr><td>.</td></tr>
			<!--tr><td>.</td></tr-->
			<tr>			
				<td class="footer_names"></td>	
				<td align=left class="footer_names"><?php echo $prepared_by?></td>
				<td class="footer_names"></td>	
				<td class="footer_names"><?php echo $reviewed_by?></td>
				<td class="footer_names"></td>	
				<td class="footer_names"><?php echo $approved_by?></td>		
				<td class="footer_names"></td>				
			</tr>
			<tr style="">
				<td align=center style="padding-left: 10px; border: 0px solid; width: 5%"></td>
				<td align=center style="padding-left: 10px; border-top: 1px solid; width: 25%"></td>
				<td align=center style="padding-left: 10px; border: 0px solid; width: 5%"></td>
				<td align=center style="padding-left: 10px; border-top: 1px solid; width: 25%"></td>
				<td align=center style="padding-left: 10px; border: 0px solid; width: 5%"></td>
				<td align=center style="padding-left: 10px; border-top: 1px solid; width: 25%"></td>
			</tr>
			
			
		</table>		
	</div>
	
	<script type="text/javascript">
		window.print();
	</script>
</body>
</html>
