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
	    width:400px;
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
	//$trans_num = "2";
	$trans_num = $_REQUEST['trans_no'];
	$rr_num_result = get_rrsupp_ref($trans_num);
	$rr_num_row=db_fetch($rr_num_result);
	$trans_type =  ST_SUPPRECEIVE ;
	$rr_num = $rr_num_row["reference"];
    
    $result = get_rr_details($rr_num,$trans_num,$trans_type);
	
	if (db_num_rows($result) > 0)
	{
		$myrow=db_fetch($result);		
		//$rrsupp_header_reference = $reference;		
		$supp_name = $myrow["supp_name"];
		$supp_address = $myrow["address"];
		$issue_date = date('m/d/Y', strtotime($myrow["tran_date"]));
		$supp_ref = $myrow["suppl_ref_no"];	
		$rrsupp_date = date('m/d/Y', strtotime($myrow["suppl_ref_date"]));
		$po_num = $myrow["purch_order_no"];
		$po_ref = $myrow["poNumber"];
		$trans_category = $myrow["category_id"];
		$reference = $myrow["reference"];
		$rr_remarks = $myrow["grn_remarks"];
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
					<label>Receiving Report Form - SUPPLIER</label>
				</div>			
			</h4>
		</div>
		<table id="header" cellspacing="0" cellpadding="0">
			<tr><td>&nbsp;</td></tr>
			<tr>
				<td align=left class="text-params">Supplier&nbsp;Name :</td>
				<th style="width: 60%;" align=left><input type="text" value="<?php echo $supp_name?>" class="underline_input_long" readonly></th>
				<td class="text-params">RR # :</td>
				<th align=left><input type="text" value="<?php echo $reference?>" class="underline_input" readonly></th>
			</tr>
			<tr>
				<td align=left class="text-params">Address :</td>
				<th style="width: 60%;" align=left><input type="text" value="<?php echo $supp_address?>" class="underline_input_long" readonly></th>
				<td class="text-params">Issue Date :</td>
				<th align=left><input type="text" value="<?php echo $issue_date?>" class="underline_input" readonly></th>
			</tr>
			<tr>
				<td align=left class="text-params">Supplier&nbsp;Ref.# :</td>
				<th style="width: 60%;" align=left><input type="text" value="<?php echo $supp_ref?>" class="underline_input_long" readonly></th>
				< <td class="text-params">PO # :</td>
				<th align=left><input type="text" value="<?php echo $po_ref?>" class="underline_input" readonly></th> >
			</tr>
			<tr>
				<td align=left class="text-params">Reference&nbsp;date :</td>
				<th style="width: 60%;" align=left><input type="text" value="<?php echo $rrsupp_date?>" class="underline_input_long" readonly></th>
				<td class="text-params">&nbsp;</td>
				<th align=left><input type="text" value="" class="underline_input" readonly></th>
			</tr>
			<tr><td>&nbsp;</td></tr>
		</table>
		<table style="width: 100%; border: 0px">
			<tr>
				<td style="width: 13%;" align=left class="text-params">Remarks :</td>
				<th align=left><input style="width: 100%; colspan: 2;" type="text" value="<?php echo $rr_remarks?>" class="underline_input"></th>
			</tr>
		</table>
		<div style="height: 18px"></div>				
		<div class="row">
			<div class="left" style="width: 100%; padding: 0px; float: left;">			
				<table style="width: 100%; float: left;" cellspacing="0" cellpadding="0">
					<tbody>						
                        <tr class="table1-headers">
							<th style="border:0.5px solid;">Qty</th>
							<th style="border:0.5px solid;">Unit</th>
							<th style="border:0.5px solid;">Prod Code</th>
							<th style="border:0.5px solid;">Description</th>
							
							<?php
								if($trans_category == 14)
								{
									echo '<th style="border:0.5px solid;">Color</th>';
									echo '<th style="border:0.5px solid;">Engine#</th>';
									echo '<th style="border:0.5px solid;">Chassis#</th>';
								}
								else
								{
									echo '<th style="border:0.5px solid;">Serial#</th>';
								}
							?>						
							<th align=right style="border:0.5px solid;">Unit Cost</th>
							<th align=right style="border:0.5px solid;">Subtotal</th>	
							
						</tr>
						<?php 			
							$result = get_rr_details($rr_num,$trans_num,$trans_type);								
							if (db_num_rows($result) > 0)
							{
								$total = 0;
								while($myrow=db_fetch($result))
								{
									$subtotal = $qty = $amnt = 0;									
									$qty = $myrow['qty'];
									$amnt = $myrow['standard_cost'];
									$subtotal = $qty * $amnt;
									
									echo '<tr class="datatable">';
									echo '<td align=center style="border-right:0.5px solid;">'.($myrow["qty"]).'</td>';	
									echo '<td align=center style="border-right:0.5px solid;">'.($myrow["units"]).'</td>';	
									echo '<td align=left style="border-right:0.5px solid; padding-left: 2px;">'.($myrow["stock_id"]).'</td>';
									echo '<td align=left style="border-right:0.5px solid; padding-left: 2px;">'.($myrow["description"]).'</td>';
									if($trans_category == 14)
									{
										echo '<td align=left style="border-right:0.5px solid;">'.($myrow["pnp_color"]).'</td>';
										echo '<td align=left style="border-right:0.5px solid; padding-left: 2px;">'.($myrow["lot_no"]).'</td>';		
										echo '<td align=left style="border-right:0.5px solid; padding-left: 2px;">'.($myrow["chassis_no"]).'</td>';

										echo '<td align=right style="border-right:0.5px solid; padding-right: 2px;">'.($amnt<=0?"":price_format($amnt)).'</td>';
										echo '<td align=right style="border-right:0.5px solid; padding-right: 2px;">'.($subtotal<=0?"":price_format($subtotal)).'</td>';
									}
									else
									{
										echo '<td align=left style="border-right:0.5px solid; padding-left: 2px;">'.($myrow["lot_no"]).'</td>';
										echo '<td align=right style="border-right:0.5px solid; padding-right: 2px;">'.($amnt<=0?"":price_format($amnt)).'</td>';
										echo '<td align=right style="border-right:0.5px solid; padding-right: 2px;">'.($subtotal<=0?"":price_format($subtotal)).'</td>';
									}			
											
									
									$total += $subtotal;
								}
								$display_total_amount = price_format($total);
								if($trans_category == 14)
								{
									echo '<tr class="top_bordered">
										<td colspan="7" style="padding-top: 5px;" align=right><b>Total Price</b></td>										
										<td colspan="2" style="text-align: right;"><b>'.$display_total_amount.'</b></td>
									</tr>';	
								}
								else
								{
									echo '<tr class="top_bordered">
										<td colspan="6" style="padding-top: 5px;" align=right><b>Total Price</b></td>										
										<td colspan="2" style="text-align: right;"><b>'.$display_total_amount.'</b></td>
									</tr>';
								}
							}							
						?>												
				    </tbody>					
				</table>				
			</div>		
			
			<div class="left" style="width: 100%; padding: 0px; float: left;font-size: 100%; margin-top: 10px;">			
				<table style="width: 100%; float: left;" cellspacing="0" cellpadding="0">
					<tbody>
						<tr class="table1-headers">	
							<th style="border:0.5px solid;">Rec#</th>
							<th style="border:0.5px solid; padding-left: 5px;">Acct Code</th>
							<th style="border:0.5px solid; padding-left: 5px;">Account Description</th>
							<th align=right style="border:0.5px solid; padding-right: 5px;">Debit</th>
							<th align=right style="border:0.5px solid; padding-right: 5px;">Credit</th>					
						</tr>
						
						<?php						  
						  $type_no=$trans_num;
						  $type = $trans_type;
						
						  $result = get_rr_supplier_gl($type_no,$type);				
							if (db_num_rows($result) > 0)
							{
								$totaldeb = 0;
								$totalcrid = 0;
								$counter = 0;
								while ($myrow2=db_fetch($result))
								{	
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
							        echo '<td align=right style="border-right:0.5px solid; padding-right: 5px;">'.($debit<=0?"-":price_format($debit)).'</td>';							      
							        echo '<td align=right style="padding-right: 5px;">'.($credit==0?"-":price_format(-$credit)).'</td>';
									echo '</tr>';
								    //end_row();

								    $totaldeb += $debit;
								    $totalcrid += -$credit;

								} //end while there are line items to print out
								$display_sub_tot = price_format($totaldeb);
								$display_sub_tots = price_format($totalcrid);
								echo '<tr class="top_bordered">
										<td colspan="3" style="padding-top: 5px;" align=right><b>Total</b></td>
										<td style="text-align: right; padding-right: 5px;"><b>'.$display_sub_tot.'</b></td>
										<td style="text-align: right; padding-right: 5px;"><b>'.$display_sub_tots.'</b></td>
									</tr>';		
							}
							else
							display_note(_("There are no line items on this dispatch."), 1, 2);										
						?>
					</tbody>
				</table>
				<table style="font-size: 7px; border: 0px">
					<td>.</td>	
				</table>				
			</div>				
				<div class="footnote2">
					* Received the above mentioned items in good order and condition.
				</div>
				<div class="footnote2">
					<?php 
						if($trans_category == 14)
							echo '* Motorcycle is complete with keys, tools, manual, coupon, battery and side mirrors.';
						else
							echo '';
					 ?>
				</div>
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