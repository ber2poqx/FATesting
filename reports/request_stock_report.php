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
	<title>Request Stock Delivery</title>
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
    $trans_id = $_GET['rsd_id'];
    $result = get_request_stock_deliver($trans_id);				
	if (db_num_rows($result) > 0)
	{
		$myrow=db_fetch($result);

		$rsd_to_location = get_db_location_name($myrow["rsd_to_loaction"]);
		$rsd_created_by = $myrow["created_by"];
		$rsd_reference = $myrow["rsd_ref"];
		$rsd_comments = $myrow["particulars"];
		$mt_header_category_id = $myrow["mt_header_category_id"];
		$rsd_request_date = date('m/d/Y', strtotime($myrow["rsd_date"]));
		$delivery_address = get_db_location_address($myrow["rsd_to_loaction"]);

		if ($myrow["mt_header_item_type"] == 'repo') {
			$headertype_new_repo = 'Repo';
		} else {
			$headertype_new_repo = '';
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
					<label>Request Stock Delivery - <?php echo $brchcode?></label>
				</div>
			</h4>
		</div>
		<table id="header" cellspacing="0" cellpadding="0">
			<tr><td>&nbsp;</td></tr>
			<tr><td>&nbsp;</td></tr>
			<tr>
				<td align=left>Request&nbsp;Branch:</td>
				<th style="width: 60%;" align=left><?php echo $rsd_to_location;?></th>
				<td>RSD #:</td>
				<th style="width: 19%;" align="left"><input style="width: 90%;" type="text" value="<?php echo $rsd_reference;?>" class="underline_input" readonly></th>
			</tr>
			<tr>
				<td align=left>Branch Name:</td>
				<th style="width: 60%;" align=left><?php echo $delivery_address;?></th>
			</tr>
			<tr>
				<td align=left>Particulars:</td>
				<th style="width: 60%;" align=left><?php echo $rsd_comments;?></th>
				<td>Issue Date:</td>
				<th style="width: 19%;" align="left"><input style="width: 90%;" type="text" value="<?php echo $rsd_request_date;?>" class="underline_input" readonly></th>
			</tr>
            <tr>
				<td align=left>Request By:</td>
				<th style="width: 60%;" align=left><?php echo $rsd_created_by;?></th>
			</tr>
			<tr><td>&nbsp;</td></tr>
		</table>
		
		<div class="row">
			<div class="left" style="width: 100%; padding: 0px; float: left;">			
				<table style="width: 100%; float: left;" cellspacing="0" cellpadding="0">
					<tbody>		
						<tr class="text-left">
							<th style= "border: 1px solid;">Qty</th>
							<th style= "border: 1px solid;">Product Code</th>
							<th style= "border: 1px solid;">Brand</th>
							<th style= "border: 1px solid;">Product Name</th>							
							<th style= "border: 1px solid;">Invty On <br>Hand</th>
							<th style= "border: 1px solid;">Sales Last <br> Month</th>
							<th style= "border: 1px solid;">Last Year of <br> the Same Month</th>
						</tr>
						<?php
						  $result = get_request_stock_deliver($trans_id);				
							if (db_num_rows($result) > 0)
							{								
								$total = 0;
								while ($myrow=db_fetch($result))
								{	
                                    if($myrow["category"] == 14) {
                                        $inty_onhand = get_onhand($myrow["item_code"], $myrow["rsd_date"]);
                                        $sales_lastmonth = get_sales_lastmonth($myrow["item_code"], $myrow["rsd_date"]);
                                        $sales_lasyear = get_sales_lastyear_samemonth($myrow["item_code"], $myrow["rsd_date"]);
                                    }else{
                                        $inty_onhand = get_onhand_not_motor($myrow["item_code"], $myrow["rsd_date"]);
                                        $sales_lastmonth = get_sales_lastmonth_not_motor($myrow["item_code"], $myrow["rsd_date"]);
                                        $sales_lasyear = get_sales_lastyear_samemonth_notmotor($myrow["item_code"], $myrow["rsd_date"]);
                                    }		

									echo '<tr class="datatable">';
									echo '<td style= "border-right: 1px solid;">'.price_format($myrow["quantity"]).'</td>';
							        echo '<td style= "border-right: 1px solid;">'.($myrow["item_code"]).'</td>';
							        echo '<td style= "border-right: 1px solid;">'.($myrow["brandname"]).'</td>';
							        echo '<td style= "border-right: 1px solid;">'.($myrow["description"]).'</td>';							    	      
							        echo '<td align=right style= "text-align: center; border-right: 1px solid;">'.price_format($inty_onhand).'</td>';							      
							        echo '<td align=right style= "text-align: center; border-right: 1px solid;">'.price_format($sales_lastmonth).'</td>';
							        echo '<td align=right style= "text-align: center;border-right: 1px solid;">'.price_format($sales_lasyear).'</td>';
									echo '</tr>';	

								    $total_qty += $myrow['quantity'];
								    $total_onhand += $inty_onhand;
								    $total_last_month += $sales_lastmonth;
								    $total_last_year += $sales_lasyear;
								}
								$display_sub_qty = price_format($total_qty);							
								$display_sub_onh = price_format($total_onhand);							
								$display_sub_lm = price_format($total_last_month);							
								$display_sub_ly = price_format($total_last_year);							
                                echo '<tr class="top_bordered">
                                        <td style="text-align: left;"><b>'.$display_sub_qty.'</b></td>
                                        <td colspan="3" style="padding-top: 7px;" align=right>Total</td>
                                        <td style="text-align: center;"><b>'.$display_sub_onh.'</b></td>
                                        <td style="text-align: center;"><b>'.$display_sub_lm.'</b></td>
                                        <td style="text-align: center;"><b>'.$display_sub_ly.'</b></td>
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
		<br/><br/><br/><br/>
		<br/><br/><br/><br/>
		<br/><br/><br/><br/>
		<table id="footer">
			<tr>
				<th style="text-align: left; padding-left: 15px;">Prepared By:</th>
				<th style="text-align: left; padding-left: 15px;">Checked By:</th>
				<th style="text-align: left; padding-left: 15px;">Noted By:</th>
				<th style="text-align: left; padding-left: 15px;">Released By:</th>
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
				<td class="footer_names"><?php echo $_SESSION["wa_current_user"]->name?></td>
				<td class="footer_names"><input type="text" style="border: 0px; text-align: center; font-size: 11px; font-family: century gothic;"></td>
				<td class="footer_names"><input type="text" style="border: 0px; text-align: center; font-size: 11px; font-family: century gothic;"></td>							
			</tr>
		</table>		
	</div>			
	</div>
	<script type="text/javascript">
		window.print();
	</script>
</body></html>
