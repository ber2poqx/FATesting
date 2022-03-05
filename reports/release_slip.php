<?php
$path_to_root = '..';
if (!isset($path_to_root) || isset($_GET['path_to_root']) || isset($_POST['path_to_root']))
		die(_("Restricted access"));
	include_once($path_to_root . "/includes/ui.inc");
	include_once($path_to_root . "/includes/page/header.inc");
	include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/sales/includes/sales_ui.inc");

include_once($path_to_root . "/sales/includes/sales_db.inc");
if (isset($_GET["trans_no"]))
{
	$trans_id = $_GET["trans_no"];
}
elseif (isset($_POST["trans_no"]))
{
	$trans_id = $_POST["trans_no"];
}
$myrow = get_customer_trans($trans_id, ST_CUSTDELIVERY);

//$branch = get_branch($myrow["branch_code"]);

//$sales_order = get_sales_order_header($myrow["order_"], ST_SALESORDER);

?>	
<!DOCTYPE html>
<html><head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<title>Delivery Slip</title>
<style type="text/css">
	.main{
		width: 8in;
		height: 6.25in;
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
		font-family: monospace;
		line-height: 1em;
	}
	.right{
		float: right;
	}
	.left{
		float: left;
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
		border-top: 1px dashed #000;
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
	height: 6.5in;
	padding-left: 15px;
	padding-right: 15px;
	  }
  /* ... the rest of the rules ... */
}
.underline_input{
    border: 0px;
    border-bottom: 1px solid;
}

</style></head>

<body>
	<div class="main printable" style="">
		<div style="width: 100%; text-align: center;padding-top: 0.25in;float: left;">
			<h4 style="margin: 0px">
				<?php
				$company = get_company_prefs();
				echo $company['coy_name'];
				?>
			</h4>
			<small>
			<?php echo $company['postal_address'];?>
			</small>
		</div>

		<div style="width: 100%;padding-top: 0.35in;float: left;">
			<div style="font-size: 13px; width: 100%;float: left;">
				<p>Ctrl No: <b style="padding-left: 10px;"><?php echo $myrow["reference"];?></b></p>
			</div>
			<div style="font-size: 13px; width: 50%;float: left;">
				<p>Sold To: <b style="padding-left: 10px;"><?php echo $myrow["DebtorName"];?></b></p>
			</div>
			<div style="font-size: 13px; width: 50%;float: left;">
				<p class="text-right"> Time: <b><?php echo date('h:i:s A', strtotime(now()));?></b></p>
			</div>
			<div style="font-size: 13px; width: 70%;float: left;">
				<p>Address: <b style="padding-left: 10px;"><?php echo nl2br($branch["br_address"]);?></b></p>
			</div>
			<div style="font-size: 13px; width: 30%;float: left;">
				<p class="text-right"> Date: <b><?php echo date('d M Y', strtotime(now()));?></b></p>
			</div>
		</div>

		<div class="left" style="width: 100%; padding: 0px; float: left;font-size: 100%;">
			
			<table style="width: 100%; float: left;" cellspacing="0" cellpadding="0">
				<tbody>
				<tr class="top_bordered bot_bordered text-left">
					<td style="padding: 5px 0px;">Qty</td>
					<td>Unit</td>
					<td style="text-align: left;">Description</td>
					<td>Engine No.</td>
					<td>Chasis No.</td>
					<td>U-Price</td>
					<td align='center'>T-Amount</td>
				</tr>
				<?php
					$result = get_customer_trans_details(ST_CUSTDELIVERY, $trans_id);
					
if (db_num_rows($result) > 0)
{
	//$th = array(_("Item Code"), _("Item Description"), _("Lot No."), _("Expire Date"), _("Quantity"),
	//	_("Unit"), _("Price"), _("Discount %"), _("Total"));
	//table_header($th);

	$k = 0;	//row colour counter
	$sub_total = 0;
	while ($myrow2 = db_fetch($result))
	{
		echo '<tr class=" text-left">';
		if($myrow2['sods_qty_sold']==0) continue;
		
		$value = round2(((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["sods_qty_sold"]),
		   user_price_dec());
		$sub_total += $value;

	    if ($myrow2["discount_percent"] == 0)
	    {
		  	$display_discount = "";
	    }
	    else
	    {
		  	$display_discount = percent_format($myrow2["discount_percent"]*100) . "%";
	    }

		//label_cell($myrow2["stock_id"]);
		echo '<td style="padding: 3px 0px;">'.$myrow2["sods_qty_sold"].'</td>';
        echo '<td>'.$myrow2["units"].'</td>';
        echo '<td style="text-align: left;">'.$myrow2["StockDescription"].'</td>';
        echo '<td style="padding-top: 3px; ">'.$myrow2["serialise_lot_no"].'</td>';
        echo '<td style="padding-top: 3px;">'.date('m/y', strtotime($myrow2["serialise_expire_date"])).'</td>';
        echo '<td>'.price_format($myrow2["unit_price"]).'</td>';
        //label_cell($display_discount, "nowrap align=right");
        echo '<td style="text-align: right; padding-right: 20px;"><b>'.price_format($value).'</b></td>';
		echo '</tr>';
	//end_row();
	} //end while there are line items to print out
	$display_sub_tot = price_format($sub_total);
	//label_row(_("Sub-total"), $display_sub_tot, "colspan=8 align=right",
	//	"nowrap align=right width='15%'");
	echo '<tr class="top_bordered">
					<td colspan="6" style="padding-top: 7px;">&nbsp;</td>
					<td style="text-align: right; padding-right: 20px;"><b>'.$display_sub_tot.'</b></td>
				</tr>';
}
else
	display_note(_("There are no line items on this dispatch."), 1, 2);					
?>				
														
				
			</tbody></table>

		</div>



		


	</div>
	<script type="text/javascript">
		window.print();
	</script>

</body></html>