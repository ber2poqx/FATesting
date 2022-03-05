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
	include_once($path_to_root . "/sales/includes/sales_db.inc");
	
?>

<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title>RR Supplier</title>
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
		font-size: 14;
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
		font-weight: bold;
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
		font-weight: bold;
	}
	
	.table1-headers {
		font-size: 11px;
		font-weight: bold;
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
		font-size: 11px;
	}

	.foot {
		text-align: center;
		margin-right: 70px;
		font-family: monospace;
	}
	#header{
	   font-size: 12px; width: 100%; float: left; border: 0px solid black;
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
		//font-weight: bold; 
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
	// $trans_num = "2";
	$trans_num = $_REQUEST['trans_no'];
	$result = get_sales_replacement_details($trans_num);

	if (db_num_rows($result) > 0)
	{
		$myrow=db_fetch($result);

		
		$trans_category = $myrow["category"];
		$SR_num = $myrow["SRnum"];
		$SR_date = $myrow["Returndate"];
		$Invoice_num = $myrow["Invoice"];
		$DR_num = $myrow["DRnum"];
		$P_lcp = $myrow["PrevLCP"];
		$N_lcp = $myrow["NewLCP"];
		$P_cost = $myrow["PrevCost"];
		$N_cost = $myrow["NewCost"];
		$Payable = $myrow["Payable"];
		$Receivable = $myrow["Receivable"];
	}	
?>

<?php
	$brchcode = $db_connections[user_company()]["branch_code"];
	//echo '({"branchcode":"'.$brcode.'"})';
			
	$compcode = $db_connections[user_company()]["name"];
	//$brscode = get_company_pref("name")

	//$comadd =  $db_connections[user_company()]["postal_address"];
	$comadd = get_company_pref("postal_address")
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
				<p><b><?php  echo $compcode?> - <?php echo $brchcode?></b></p>
			</div>			
				
			<div class="CompanyAdd">
				<p><?php echo $comadd?></p>
			</div>
			
			<h4>
				<div class="Merchandise">
					<label>Delivery Receipt Note - Sales Item Replacement</label>
				</div>			
			</h4>
		</div>
		<table id="header" cellspacing="0" cellpadding="0">
			<tr><td>&nbsp;</td></tr>
			<tr>
				<td align=left class="text-params">Sales Return # :</td>
				<th style="width: 60%;" align=left><input type="text" value="<?php echo $SR_num?>" class="underline_input_long" readonly></th>
				<td class="text-params">Returned Date :</td>
				<th align=left><input type="text" value="<?php echo $SR_date?>" class="underline_input" readonly></th>
			</tr>
			<tr>
				<td align=left class="text-params">Invoice # :</td>
				<th style="width: 60%;" align=left><input type="text" value="<?php echo $Invoice_num?>" class="underline_input_long" readonly></th>
				<td class="text-params">DR # :</td>
				<th align=left><input type="text" value="<?php echo $DR_num?>" class="underline_input" readonly></th>
			</tr>
			<tr>
				<td align=left class="text-params">Total Prev LCP :</td>
				<th style="width: 60%;" align=left><input type="text" value="<?php echo price_format($P_lcp)?>" class="underline_input_long" readonly></th>
				<td class="text-params">Total New LCP :</td>
				<th align=left><input type="text" value="<?php echo price_format($N_lcp)?>" class="underline_input" readonly></th>
			</tr>
			<tr>
				<td align=left class="text-params">Total Prev Cost :</td>
				<th style="width: 60%;" align=left><input type="text" value="<?php echo price_format($P_cost)?>" class="underline_input_long" readonly></th>
				<td class="text-params">Total Prev Cost :</td>
				<th align=left><input type="text" value="<?php echo price_format($N_cost)?>" class="underline_input" readonly></th>
			</tr>
			<tr>
				<td align=left class="text-params">Total Payable :</td>
				<th style="width: 60%;" align=left><input type="text" value="<?php echo price_format($Payable)?>" class="underline_input_long" readonly></th>
				<td class="text-params">Total Receivable :</td>
				<th align=left><input type="text" value="<?php echo price_format($Receivable)?>" class="underline_input" readonly></th>
			</tr>
			<tr><td>&nbsp;</td></tr>
		</table>
		<div style="height: 12px"></div>
		<table style="width: 100%; border: 0px">
			<tr>
			<td style="width: 13%;" align=center class="text-params">Replaced Item Details:</td>
				<!-- <th align=left><input style="width: 100%; colspan: 2;" type="text" value="<?php  $rr_remarks?>" class="underline_input"></th> -->
			</tr>
		</table>
		<div style="height: 3px"></div>				
		<div class="row">
			<div class="left" style="width: 100%; padding: 0px; float: left;">			
				<table style="width: 100%; float: left;" cellspacing="0" cellpadding="0">
					<tbody>						
                        <tr class="table1-headers">
							<th style="border:0.5px solid;">Item Code</th>
							<th style="border:0.5px solid;">Description</th>
							<th style="border:0.5px solid;">Qty</th>
							<th style="border:0.5px solid;">Unit Price</th>
							<th style="border:0.5px solid;">Cost</th>
							
							<?php
								if($trans_category == 14)
								{
									echo '<th style="border:0.5px solid;">Color</th>';
									echo '<th style="border:0.5px solid;">Engine #</th>';
									echo '<th style="border:0.5px solid;">Chassis #</th>';
								}
								else
								{
									echo '<th style="border:0.5px solid;">Serial #</th>';
								}
							?>						
							<!-- <th align=right style="border:0.5px solid;">Unit Cost</th>
							<th align=right style="border:0.5px solid;">Subtotal</th>	 -->


							
						</tr>
						<?php 			
							$data_result = get_sales_replacement_details($trans_num);								
							if (db_num_rows($data_result) > 0)
							{								
								while($myrow2=db_fetch($data_result))
								{		
									$price = price_format($myrow2["replace_price"]);
									$cost = price_format($myrow2["replace_cost"]);

									echo '<tr class="datatable">';
									echo '<td align=center style="border-right:0.5px solid;">'.($myrow2["replace_stock_id"]).'</td>';	
									echo '<td align=left style="border-right:0.5px solid;">'.($myrow2["description"]).'</td>';	
									echo '<td align=center style="border-right:0.5px solid;">'.($myrow2["replace_qty"]).'</td>';	
									echo '<td align=right style="border-right:0.5px solid; padding-left: 2px;">'.($price).'</td>';
									echo '<td align=right style="border-right:0.5px solid; padding-left: 2px;">'.($cost).'</td>';

									if($trans_category == 14)
									{
										echo '<td align=left style="border-right:0.5px solid;">'.($myrow2["replace_color_code"]).'</td>';
										echo '<td align=left style="border-right:0.5px solid; padding-left: 2px;">'.($myrow2["replace_serial_no"]).'</td>';		
										echo '<td align=left style="border-right:0.5px solid; padding-left: 2px;">'.($myrow2["replace_chassis_no"]).'</td>';	
									}
									else
									{
										echo '<td align=center style="border-right:0.5px solid; padding-left: 2px;">'.($myrow2["replace_serial_no"]).'</td>';
									}	
								}
								// $display_total_amount = price_format($total);
								// if($trans_category == 14)
								// {
								// 	echo '<tr class="top_bordered">
								// 		<td colspan="7" style="padding-top: 5px;" align=right><b>Total Price</b></td>										
								// 		<td colspan="2" style="text-align: right;"><b>'.$display_total_amount.'</b></td>
								// 	</tr>';	
								// }
								// else
								// {
								// 	echo '<tr class="top_bordered">
								// 		<td colspan="6" style="padding-top: 5px;" align=right><b>Total Price</b></td>										
								// 		<td colspan="2" style="text-align: right;"><b>'.$display_total_amount.'</b></td>
								// 	</tr>';
								// }
							}							
						?>		

				    </tbody>					
				</table>				
			</div>		
														<!-- // $item_code = $myrow["replace_stock_id"];
														// $qty = $myrow["replace_qty"];
														// $unit_price = $myrow["replace_price"];
														// $cost = $myrow["replace_cost"];
														// $color = $myrow["replace_color_code"];
														// $serial = $myrow["replace_serial_no"];
														// $chassis = $myrow["replace_chassis_no"]; -->
			
		</div>																
	</tbody>
</table>
	<div>		
		<br/><br/><br/><br/>
		<table id="footer">
			<tr>
				<th style="text-align: left; padding-left: 15px;">Prepared By:</th>
				<th style="text-align: left; padding-left: 15px;">Approved By:</th>
				<th style="text-align: left; padding-left: 15px;">Checked By:</th>
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