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


	function get_name_approver_mt($reference){
		$sql = "SELECT B.real_name 
				FROM ".TB_PREF."mt_header A 
				LEFT JOIN users B ON B.id=A.approved_by
				WHERE A.mt_header_reference = '$reference'";
		$result = db_query($sql, "could not get users name ");
		$ret = db_fetch($result);
	
		return $ret[0];
	}

	function get_name_reviewer_mt($reference){
		$sql = "SELECT B.real_name 
				FROM ".TB_PREF."mt_header A 
				LEFT JOIN users B ON B.id=A.reviewed_by
				WHERE A.mt_header_reference = '$reference'";
		$result = db_query($sql, "could not get users name ");
		$ret = db_fetch($result);
	
		return $ret[0];
	}
?>

<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<title>Merchandise Transfer</title>
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
		margin-top: 50px;
		text-align: center;
	}


	@page  {
		margin: 0;
		padding: 0px 25px;
	}
	@media  print {
	  html, body {

		width: 9in;
		height: 11in;
		padding-left: 10px;
		padding-right: 5px;
	
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
	  padding: 7.50px;
	}

	.text-left {
		font-size: 12px;
		font-weight: bold;
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
		padding-left: 10px;
		padding-right: 10px;
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
	}
</style>
</head>

<?php
    $reference = $_GET['reference'];
    $sm_result = get_stock_moves_typetrans($reference);
    $myrow_sm=db_fetch($sm_result);
    $stock_reference=$myrow_sm['reference'];
    
    $result = getmtreceipt($reference);				
	if (db_num_rows($result) > 0)
	{
		$myrow=db_fetch($result);

		$mt_header_tolocation = get_db_location_name($myrow["mt_header_tolocation"]);
		$mt_header_rsd = $myrow["mt_header_rsd"];
		$mt_header_servedby = $myrow["mt_header_servedby"];
		$mt_header_reference = $stock_reference;
		$mt_header_comments = $myrow["mt_header_comments"];
		$mt_header_category_id = $myrow["mt_header_category_id"];
		$mt_header_date = date('m/d/Y', strtotime($myrow["mt_header_date"]));

		$delivery_address = get_db_location_address($myrow["mt_header_tolocation"]);

		if ($myrow["mt_header_item_type"] == 'repo') {
			$headertype_new_repo = 'Repo';
		} else {
			$headertype_new_repo = '';
		}
	}

	$name_approver = get_name_approver_mt($reference);	
	$name_reviewer = get_name_reviewer_mt($reference);
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
	<div class="main printable" style="">

		<div style="width: 100%; text-align: center;padding-top: 0.25in;float: left;">
			<h4 style="margin: 0px">
				<div class="companyDes">
					<p><b>Du Ek Sam, Inc.</b></p>
				</div>

				<div class="Branchcompany">
					<p><b><?php  echo $compcode?> - <?php echo $brchcode?></b></p>
				</div>

				<div class="CompanyAdd">
					<p><b><?php echo $comadd?></b></p>
				</div>

				<div class="Merchandise">
					<label>Merchandise Transfer Form <?php echo $headertype_new_repo?></label>
				</div>
			</h4>
		</div>
		<table id="header" cellspacing="0" cellpadding="0">
			<tr><td>&nbsp;</td></tr>
			<tr><td>&nbsp;</td></tr>
			<tr>
				<td align=left>Target&nbsp;Branch:</td>
				<th style="width: 60%;" align=left><?php echo $mt_header_tolocation;?></th>
				<td>MT Num:</td>
				<th style="width: 19%;" align="left"><input style="width: 90%;" type="text" value="<?php echo $mt_header_reference.$type.$type_no;?>" class="underline_input" readonly></th>
			</tr>
			<tr>
				<td align=left>Address:</td>
				<th style="width: 60%;" align=left><?php echo $delivery_address;?></th>
			</tr>
			<tr>
				<td align=left>Particulars:</td>
				<th style="width: 60%;" align=left><?php echo $mt_header_comments;?></th>
				<td>Issue Date:</td>
				<th style="width: 19%;" align="left"><input style="width: 90%;" type="text" value="<?php echo $mt_header_date.$type.$type_no;?>" class="underline_input" readonly></th>
			</tr>
			<tr>
				<td align=left>MT Served by:</td>
				<th style="width: 60%;" align=left><?php echo $mt_header_servedby;?></th>
				<td>RSD #:</td>
				<th style="width: 19%;" align="left"><input style="width: 90%;" type="text" value="<?php echo $mt_header_rsd;?>" class="underline_input" readonly></th>
			</tr>
			<tr><td>&nbsp;</td></tr>
		</table>
		
		<div class="row">
			<div class="left" style="width: 100%; padding: 0px; float: left;">			
				<table style="width: 100%; float: left;" cellspacing="0" cellpadding="0">
					<tbody>		
						<tr class="text-left">
							<th style= "border: 1px solid;">Qty</th>
							<th style= "border: 1px solid;">Unit</th>
							<th style= "border: 1px solid;">Prod Code</th>
							<th style= "border: 1px solid;">Description</th>
							<?php 
							if($mt_header_category_id==14){
							    echo '<th style= "border: 1px solid;">Color</th>';
								echo '<th style= "border: 1px solid; width: 20%;">Serial No.</th>';
								echo '<th style= "border: 1px solid; width: 20%;">Chasis No.</th>';
							}else{
							    echo '<th style= "border: 1px solid; width: 20%;">Serial No.</th>';
							}
							?>
							
							<th align=right style= "border: 1px solid;">Unit Cost</th>
							<th align=right style= "border: 1px solid;">Sub Total</th>
						</tr>
						<?php
						  $result = getmtreceipt($stock_reference);				
							if (db_num_rows($result) > 0)
							{
								
								$total = 0;
								while ($myrow=db_fetch($result))
								{	
									//$total = 0.0;									
									echo '<tr class="datatable">';
									echo '<td style= "border-right: 1px solid;">'.price_format($myrow["mt_details_total_qty"]).'</td>';
							        echo '<td style= "border-right: 1px solid;">'.($myrow["units"]).'</td>';
							        echo '<td style= "border-right: 1px solid;">'.($myrow["mt_details_stock_id"]).'</td>';
							        echo '<td style= "border-right: 1px solid;">'.($myrow["description"]).'</td>';
							        if($mt_header_category_id==14){
								        echo '<td style= "border-right: 1px solid;">'.($myrow["dcolor"]).'</td>';
								        echo '<td style= "border-right: 1px solid;">'.($myrow["mt_details_serial_no"]).'</td>';        
								        echo '<td style= "border-right: 1px solid;">'.($myrow["mt_details_chasis_no"]).'</td>';	
								    } else {
								        echo '<td style= "border-right: 1px solid;">'.($myrow["mt_details_serial_no"]).'</td>';        
								    }						      
							        echo '<td align=right style= "border-right: 1px solid;">'.price_format($myrow["COST"]).'</td>';							      
							        echo '<td align=right style= "border-right: 1px solid;">'.price_format($myrow["SUBTOTAL"]).'</td>';
									echo '</tr>';
								//end_row();
								$total += $myrow['SUBTOTAL'];

								} //end while there are line items to print out
								$display_sub_tot = price_format($total);
								/*
								label_row(_("Total"), $display_sub_tot, "colspan=7 align=right", "colspan=8 align=right",
									"nowrap align=right width='15%'");
								*/
								if($mt_header_category_id==14){
									echo '<tr class="top_bordered">
											<td colspan="8" style="padding-top: 7px;" align=right>Total</td>
											<td style="text-align: right;"><b>'.$display_sub_tot.'</b></td>
										</tr>';	
								} else {
									echo '<tr class="top_bordered">
											<td colspan="6" style="padding-top: 7px;" align=right>Total</td>
											<td style="text-align: right;"><b>'.$display_sub_tot.'</b></td>
										</tr>';	
								}
								/*
								echo '<tr class="top_bordered">
										<td colspan="8" colspan="8" style="padding-top: 7px;">Total</td>
										<td style="text-align: left;"><b>'.$display_sub_tot.'</b></td>
									</tr>';
								*/
										
							}
							else
							display_note(_("There are no line items on this dispatch."), 1, 2);										
						?>
				    </tbody>					
				</table>
			</div>

			
			<div class="left" style="width: 100%; padding: 0px; float: left;font-size: 100%; margin-top: 10px;">			
				<table style="width: 100%; float: left;" cellspacing="0" cellpadding="0">
					<tbody>
						<tr class="text-left">							
							<th style= "border: 1px solid;">Acct Code</th>
							<th style= "border: 1px solid;">Account Description</th>
							<th style= "border: 1px solid;">MCode</th>
							<th style= "border: 1px solid;">Masterfile</th>
							<th align=right style= "border: 1px solid;">Debit</th>
							<th align=right style= "border: 1px solid;">Credit</th>					
						</tr>

						<?php
						  $type=$myrow_sm['type'];
						  $type_no=$myrow_sm['trans_no'];
						
						  $result3 = getmtgl($type,$type_no);				
							if (db_num_rows($result) > 0)
							{
								$totaldeb = 0;
								$totalcrid = 0;
								
								while ($myrow2=db_fetch($result3))
								{	
									$credit = $debit = 0;
									if ($myrow2['amount'] > 0 ) 
    									$debit += $myrow2['amount'];
    									else 
    									$credit += $myrow2['amount'];

    								//for mcode and masterfile
    								$reference = $_GET['reference'];
    								$mt_header = get_mt_header($reference);
									$mccode = $mt_header["mt_header_tolocation"];
									$masterfile = get_db_location_name($mt_header["mt_header_tolocation"]);
									//end//

									echo '<tr class="datatable">';					
							        echo '<td style= "border-right: 1px solid;">'.($myrow2["account"]).'</td>';
							        echo '<td style= "border-right: 1px solid;">'.($myrow2["account_name"]).'</td>';							      
							        echo '<td style= "border-right: 1px solid;">'.$mccode.'</td>';							      
							        echo '<td style= "border-right: 1px solid;">'.$masterfile.'</td>';							      
							        echo '<td align=right style= "border-right: 1px solid;">'.($debit<=0?"":price_format($debit)).'</td>';							      
							        echo '<td align=right style= "border-right: 1px solid;">'.($credit==0?"":price_format(-$credit)).'</td>';
									echo '</tr>';
								    //end_row();

								    $totaldeb += $debit;
								    $totalcrid += -$credit;

								} //end while there are line items to print out
								$display_sub_tot = price_format($totaldeb);
								$display_sub_tots = price_format($totalcrid);
								echo '<tr class="top_bordered">
										<td colspan="4" style="padding-top: 7px;" align=right>Total</td>
										<td style="text-align: right;"><b>'.$display_sub_tot.'</b></td>
										<td style="text-align: right;"><b>'.$display_sub_tots.'</b></td>
									</tr>';		
							}
							else
							display_note(_("There are no line items on this dispatch."), 1, 2);										
						?>
					</tbody>
				</table>
			</div>
		</div>																
	</tbody>
	</table>
	<div>
		<div class="left" style="width: 90%; padding:50px ;font-size: 60%;">
			<p class="foot">-DELIVERED THE ABOVE MENTIONED ITEMS IN GOOD ORDER AND CONDITION-</p>_______________________________________________________________________________________________________________________________________		
		</div>	
		<div>		
		<br/><br/><br/><br/>
		<table id="footer">
			<tr>
				<th style="text-align: left; padding-left: 15px;">Prepared By:</th>
				<th style="text-align: left; padding-left: 15px;">Approved By:</th>
				<th style="text-align: left; padding-left: 15px;">Released By:</th>
				<th style="text-align: left; padding-left: 15px;">Received By:</th>
			</tr>
			<tr><td></td></tr>
			<tr><td></td></tr>
			<tr style="height: 1px;">
				<td align=left>__________________________</td>
				<td align=left>__________________________</td>
				<td align=left>__________________________</td>
				<td align=left>__________________________</td>
			</tr>
			<tr>
				<td class="footer_names"><?php echo $_SESSION["wa_current_user"]->name ?></td>
				<td class="footer_names"><?php echo $name_approver ?></td>
				<td class="footer_names"><?php echo $mt_header_servedby ?></td>
				<td class="footer_names"><?php echo $mt_header_tolocation ?></td>
			</tr>
			
		</table>		
	</div>			
	</div>
	<script type="text/javascript">
		window.print();
	</script>
</body></html>
