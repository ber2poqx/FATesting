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


	function get_nameuser(){
		$sql = "SELECT A.real_name 
				FROM ".TB_PREF."users A 
				WHERE A.role_id = '19'";
		$result = db_query($sql, "could not get user ");
		$ret = db_fetch($result);
	
		return $ret[0];
	}

	function get_nameuseraccounting(){
		$sql = "SELECT A.real_name 
				FROM ".TB_PREF."users A 
				WHERE A.role_id = '12'";
		$result = db_query($sql, "could not get user ");
		$ret = db_fetch($result);
	
		return $ret[0];
	}

	function get_name_masterfile($reference){
		$sql = "SELECT A.masterfile 
				FROM ".TB_PREF."complimentary_items A 
				WHERE A.reference = '$reference'";
		$result = db_query($sql, "could not get masterfile ");
		$ret = db_fetch($result);
	
		return $ret[0];
	}
?>

<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<title>Complimentary Item Slip</title>
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
	    width:120px;
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
	/*.footnote2{
		width: 90%; 
		font-size: 70%; 
		text-align: left; 
		padding-top: 3px;
		padding-left: 50px; 
		font-style: italic; 
	}*/
	#accountingname{
		display: inline-block;
		border: none;
		background: white;
	}
	#managername{
		display: inline-block;
		border: none;
		background: white;
	}
	#crrntname{
		display: inline-block;
		border: none;
		background: white;
	}
</style>
</head>

<?php
function getreceipt($reference)
{
    
    $sql = "SELECT smoves.*, loc.location_name, comments.memo_,smaster.units, smaster.description as sdescription, 
		item_codes.description as dcolor, smaster.category_id
	FROM ".TB_PREF."stock_moves smoves 
	LEFT JOIN ".TB_PREF."locations loc ON smoves.loc_code=loc.loc_code
    LEFT JOIN ".TB_PREF."comments ON smoves.type=comments.type AND smoves.trans_no=comments.id
    INNER JOIN ".TB_PREF."stock_master smaster ON smaster.stock_id = smoves.stock_id
	LEFT JOIN ".TB_PREF."item_codes ON item_codes.item_code = smoves.color_code
	
	WHERE smoves.reference='$reference'";
    
    return db_query($sql,"No transactions were returned");
}

    $reference = $_GET['reference'];
    $sm_result = get_stock_moves_typetrans($reference);
    $myrow_sm=db_fetch($sm_result);
    $stock_reference=$myrow_sm['reference'];
    $type=$myrow_sm['type'];
    $trans_no=$myrow_sm['trans_no'];
    
    $result = getreceipt($reference);				
    if (db_num_rows($result) > 0)
	{
	    $myrow=db_fetch($result);
	    $mt_header_reference = $stock_reference;
		$mt_header_tolocation = $myrow["location_name"];
		$mt_header_comments = $myrow["memo_"];
		$mt_header_category_id = $myrow["category_id"];
		$mt_header_date = date('m/d/Y', strtotime($myrow["tran_date"]));
		$trans_category = $myrow["category_id"];

		//$delivery_address = get_db_location_address($myrow["mt_header_tolocation"]);

		if ($myrow["item_type"] == 'repo') {
			$headertype_new_repo = 'Repo';
		} else {
			$headertype_new_repo = '';
		}
	}	
	
	$name_masterfile = get_name_masterfile($reference);
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
	<div class="main printable" style="">

		<div style="width: 100%; text-align: center;padding-top: 0.25in;float: left;">
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
					<label>Complimentary Items Form <?php echo $headertype_new_repo?></label>
				</div>
			</h4>
		</div>
		<table id="header" cellspacing="0" cellpadding="0" border=0>
			<tr><td>&nbsp;</td></tr>
			<tr><td>&nbsp;</td></tr>
			<tr>
				<td align=left>Name:</td>
				<th style="width: 40%;" align=left><?php echo $name_masterfile;?></th>
				<td>Reference no.:</td>
				<th style="width: 20%;" align="left"><input style="width: 95%;" type="text" value="<?php echo $reference;?>" class="underline_input" readonly></th>
			</tr>
			<tr>
				<td align=left>Inventory Location:</td>
				<th style="width: 40%;" align=left><?php echo $mt_header_tolocation;?></th>
				<td>Issue Date:</td>
				<th style="width: 20%;" align="left"><input style="width: 95%;" type="text" value="<?php echo $mt_header_date;?>" class="underline_input" readonly></th>
			</tr>
			
			<tr>
				<td align=left>Particulars:</td>
				<th style="width: 50%;" align=left><?php echo $mt_header_comments;?></th>				
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
								echo '<th style= "border: 1px solid;">Engine No.</th>';
								echo '<th style= "border: 1px solid;">Chasis No.</th>';
							}else{
							    echo '<th style= "border: 1px solid;">Serial No.</th>';
							}
							?>
							
							<th align=right style= "border: 1px solid; padding-right: 10px;">Unit Cost</th>
							<th align=right style= "border: 1px solid; padding-right: 10px;">Sub Total</th>
						</tr>
						<?php
						  $result = getreceipt($stock_reference);				
							if (db_num_rows($result) > 0)
							{
								
								$total = 0;

								while ($myrow=db_fetch($result))
								{	
									$subtotal = 0;									
									echo '<tr class="datatable">';
									echo '<td style= "border-right: 1px solid;">'.price_format(abs($myrow["qty"])).'</td>';
							        echo '<td style= "border-right: 1px solid;">'.($myrow["units"]).'</td>';
							        echo '<td style= "border-right: 1px solid;">'.($myrow["stock_id"]).'</td>';
							        echo '<td style= "border-right: 1px solid;">'.($myrow["sdescription"]).'</td>';

							        if($mt_header_category_id==14){
								        echo '<td style= "border-right: 1px solid;">'.($myrow["dcolor"]).'</td>';
								        echo '<td style= "border-right: 1px solid;">'.($myrow["lot_no"]).'</td>';        
								        echo '<td style= "border-right: 1px solid;">'.($myrow["chassis_no"]).'</td>';
								    }else{
								    	echo '<td style= "border-right: 1px solid;">'.($myrow["lot_no"]).'</td>';
								    }

							        echo '<td align=right style= "border-right: 1px solid;">'.price_format($myrow["standard_cost"]).'</td>';

							        $subtotal = abs($myrow["qty"]) * $myrow["standard_cost"];

							        echo '<td align=right style= "border-right: 1px solid;">'.price_format($subtotal).'</td>';
									echo '</tr>';
									//end_row();
									$total += $subtotal;

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
								}else{
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
							<th style= "border: 1px solid;">Mcode</th>
							<th style= "border: 1px solid;">Masterfile</th>
							<th align=right style= "border: 1px solid; padding-right: 10px;">Debit</th>
							<th align=right style= "border: 1px solid; padding-right: 10px;">Credit</th>					
						</tr>

						<?php
						  $type=$myrow_sm['type'];
						  $type_no=$myrow_sm['trans_no'];
						
						  $result3 = getmtgl_mcode($type,$type_no);				
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

									echo '<tr class="datatable">';					
							        echo '<td style= "border-right: 1px solid;">'.($myrow2["account"]).'</td>';
							        echo '<td style= "border-right: 1px solid;">'.($myrow2["account_name"]).'</td>';
							        echo '<td style= "border-right: 1px solid;">'.($myrow2["mcode"]).'</td>';
							        echo '<td style= "border-right: 1px solid;">'.($myrow2["master_file"]).'</td>';							      
							        echo '<td align=right style= "border-right: 1px solid;">'.(price_format($debit)).'</td>';							      
							        echo '<td align=right style= "border-right: 1px solid;">'.(price_format(-$credit)).'</td>';
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
		<br/><br/><br/><br/>
		<table id="footer">
			<tr>
				<th style="text-align: left; padding-left: 15px;">Prepared By:</th>
				<th style="text-align: left; padding-left: 15px;">Reviewed By:</th>
				<th style="text-align: left; padding-left: 15px;">Approved By:</th>
				<th style="text-align: left; padding-left: 15px;">Received By:</th>
			</tr>
			<tr><td></td></tr>
			<tr><td></td></tr>
			<tr style="height: 1px;">
				<td align=left>__________________________________</td>
				<td align=left>__________________________________</td>
				<td align=left>__________________________________</td>
				<td align=left>__________________________________</td>
			</tr>
			<tr>
				<td class="footer_names"><select name="crrntname" id="crrntname"><option value="<?php echo $_SESSION["wa_current_user"]->name?>" > <?php echo $_SESSION["wa_current_user"]->name?></option></select></td>
				<td class="footer_names"><select name="accountingname" id="accountingname"><option value="<?php echo get_nameuseraccounting() ?>" > <?php echo get_nameuseraccounting()?></option></select></td>
				<td class="footer_names"><select name="managername" id="managername"><option value="<?php echo get_nameuser() ?>" > <?php echo get_nameuser()?></option></select></td>
				<td class="footer_names"><select name="crrntname" id="crrntname"><option value="<?php echo $name_masterfile ?>" > <?php echo $name_masterfile ?></option></select></td>							
			</tr>
			
		</table>		
	</div>			
	</div>
	<script type="text/javascript">
		window.print();
	</script>
</body></html>
