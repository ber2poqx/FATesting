<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/

function dashboard($sel_app, $type = "", $category_id = null, 
	$sales_grp = 0, $invty_grp = 0) {
	$selected_app = $sel_app;


	if (is_object($sel_app) && !$_SESSION["wa_current_user"]->check_application_access($selected_app))
		return;
	// first have a look through the directory, 
	// and remove old temporary pdfs and pngs
	$dir = company_path(). '/pdf_files';

	if ($d = @opendir($dir)) {
		while (($file = readdir($d)) !== false) {
			if (!is_file($dir.'/'.$file) || $file == 'index.php') continue;
		// then check to see if this one is too old
			$ftime = filemtime($dir.'/'.$file);
		 // seems 3 min is enough for any report download, isn't it?
			if (time()-$ftime > 180){
				unlink($dir.'/'.$file);
			}
		}
		closedir($d);
	}

	//check_for_overdue_recurrent_invoices();
	if ($selected_app == "orders" && $type == "orders") {
		display_customer_topten($sales_grp);
	}
	elseif ($selected_app == "AP" && $type == "AP") {
		display_supplier_topten();
	}
	elseif ($selected_app == "stock" && $type == "stock") {
		display_stock_topten(0, $category_id, $invty_grp);
	}
	elseif ($selected_app == "manuf") {
		display_stock_topten(1);
	}
	elseif ($selected_app == "assets") {
		display_stock_topten(2);
	}
	elseif ($selected_app == "proj") {
		display_dimension_topten();
	}
	elseif ($selected_app == "GL" && $type == "GL") {
		display_gl_info();
	}
	else{
		display_all();
	}
}

function display_title($title)
{
	br();
	display_heading($title);
	br();
	//echo "<tr><td colspan=$colspan class='headingtext' style='background-color:#fff;text-align:center;border:0;height:40px;'>$title</td></tr>\n";
}	

function table_one()
{
	echo "<table width='100%'>";
	echo "<tr valign=top><td style='width:50%'>\n"; // outer table
}

function table_two()
{
	echo "</td><td style='width:50%'>\n";
}

function table_end()
{
	echo "</td></tr></table>\n";
}

function display_customer_topten($sales_grp = 0) {
	$pg = new graph();

	if ($sales_grp == 2) {
		$sub_title = "Area";
	}
	else if ($sales_grp == 3) {
		$sub_title = "Collector's Name";
	}
	else {
		$sub_title = "Customer";
	}

	table_one();
	$today = Today();
	$title = customer_top($today, 10, 85, $pg, $sales_grp);
	customer_trans($today);

	table_two();
	_source_graphic(
		$today, 
		_("Graphical View of ") . $title, 
		$sub_title, 
		$pg, 
		_("Sales")
	);
	
	customer_recurrent_invoices($today);
	table_end();
}

function display_supplier_topten()
{
	$pg = new graph();

	$today = Today();
	table_one();
	$title = supplier_top($today, 10, 66, $pg);
	supplier_trans($today);
	table_two();
	_source_graphic($today, $title, _("Supplier"), $pg, _("Purchase"));
	table_end();
}

function display_stock_topten($type = 0, $category_id = null, $invty_grp = 0) {
	$pg = new graph();

	table_one();
	$today = Today();
	$title = stock_top($today, 10, 80, $type, $pg, $category_id, $invty_grp);
	table_two();

	if ($type == 2) {
		$source = _("Fixed Assets");
	}
	elseif ($type == 1) {
		$source = _("Manufacturing");
	}
	else {
		if ($invty_grp == 2) {
			$source = _("Item Description");
		}
		else if ($invty_grp == 3) {
			$source = _("Item Category");
		}
		else {
			$source = _("Brand");
		}
	}
	
	_source_graphic($today, 
		_("Graphical View of ") . $title, 
		$source, $pg, 
		($type == 0 ? _("Sales") : _("Amount")) 
		//($type == 0 ? _("Costs") : null)
	);

	table_end();
}

function display_dimension_topten()
{
	$pg = new graph();

	table_one();
	$today = Today();
	$title = dimension_top($today, 10, 66, $pg);
	table_two();
	_source_graphic($today, $title, _("Dimension"), $pg, _("Performance"), null, 5);
	table_end();
}	

function display_gl_info()
{
	$pg = new graph();

	table_one();
	$today = Today();
	$title = gl_top($today, 66, $pg);
	bank_balance($today, 66);
	table_two();
	_source_graphic($today, $title, _("Class"), $pg, _("Amount"), null, 5);
	gl_performance($today, 66, 5);
	table_end();
}	

function display_all() {
	$today = Today();

	$pg = new graph();
	table_one();
	$title = customer_top($today, 5, 85, $pg);
	_source_graphic($today, $title, _("Customer"), $pg, _("Sales"));

	$pg = new graph();
	$title = supplier_top($today, 5, 66, $pg);
	_source_graphic($today, $title, _("Supplier"), $pg, _("Purchases"));

	$pg = new graph();
	$title = stock_top($today, 5, 66, 0, $pg);
	_source_graphic($today, $title, _("Brand"), $pg, _("Sales"), _("Costs"));
	
	table_two();
	dimension_top($today, 5, 66);
	$pg = new graph();
	$title = gl_top($today, 66, $pg);
	_source_graphic($today, $title, _("Class"), $pg, _("Amount"), null, 5);
	stock_top($today, 5, 66, 2);
	stock_top($today, 5, 66, 1);
	table_end();
}

function customer_top($today, $limit = 10, $width = "33", &$pg = null, $sales_grp = 0) {
	
	$result = get_customer_top($today, $limit, $sales_grp);

	if ($sales_grp == 2) {
		$sub_title = "Areas";
	}
	if ($sales_grp == 3) {
		$sub_title = "Collectors";
	}
	else {
		$sub_title = "Customers";
	}

	$title = sprintf(_("Top %s $sub_title in Fiscal Year"), $limit);

	display_title($title);

	if ($sales_grp == 2 || $sales_grp == 3) {
		$th = array(_("Area"), _("Designated Collector"), _("Amount"));
	}
	else {
		$th = array(_("Area"), _("Designated Collector"), _("Customer"), _("Amount"));
	}

	start_table(TABLESTYLE, "width='$width%'");
	table_header($th);

	check_page_security('SA_SALESTRANSVIEW');
	
	$k = 0; //row colour counter
	$i = 0;
	$name = '';

	while ($row = db_fetch($result)) {
		alt_table_row_color($k);
		
		if ($sales_grp == 2) {
			$name = strtoupper($row['area_name']) . " ";
		}
		else if ($sales_grp == 3) {
			$name = $row['collector_name'] . " ";
		}
		else {
			$name = $row["debtor_ref"]." - ".htmlspecialchars_decode($row["name"]);
		}
		
		if ($sales_grp == 2 || $sales_grp == 3) {
			label_cell(strtoupper($row['area_name']));
			label_cell($row['collector_name']);
		}
		else {
			label_cell(strtoupper($row['area_name']));
			label_cell($row['collector_name']);
			label_cell($name);
		}

		amount_cell($row['total']);
		
		if ($pg != null) {

			$pg->x[$i] = $name . " "; 
			$pg->y[$i] = $row['total'];
		}	
		$i++;
		end_row();
	}
	end_table(2);

	return $title;
}

function supplier_top($today, $limit=10, $width="33", &$pg=null)
{
	$begin = begin_fiscalyear();
	$begin1 = date2sql($begin);
	$today1 = date2sql($today);
	$sql = "SELECT SUM((trans.ov_amount + trans.ov_discount) * rate) AS total, s.supplier_id, s.supp_name FROM
		".TB_PREF."supp_trans AS trans, ".TB_PREF."suppliers AS s WHERE trans.supplier_id=s.supplier_id
		AND (trans.type = ".ST_SUPPINVOICE." OR trans.type = ".ST_SUPPCREDIT.")
		AND tran_date >= '$begin1' AND tran_date <= '$today1' GROUP by s.supplier_id ORDER BY total DESC, s.supplier_id 
		LIMIT $limit";
	$result = db_query($sql);
	$title = sprintf(_("Top %s Suppliers in Fiscal Year"), $limit);
	display_title($title);
	$th = array(_("Supplier"), _("Amount"));
	start_table(TABLESTYLE, "width='$width%'");
	table_header($th);
	check_page_security('SA_SUPPTRANSVIEW');
	$k = 0; //row colour counter
	$i = 0;
	while ($row = db_fetch($result))
	{
		alt_table_row_color($k);
		$name = $row["supplier_id"]." ".htmlspecialchars_decode($row["supp_name"]);
		label_cell($name);
		amount_cell($row['total']);
		if ($pg != null)
		{
			$pg->x[$i] = $name; 
			$pg->y[$i] = $row['total'];
		}	
		$i++;
		end_row();
	}
	end_table(2);
	return $title;
}

function stock_top($today, $limit = 10, $width = "33", $type = 0, &$pg = null, 
	$category_id = null, $invty_grp = 0) {
	
	if ($type == 2) {
		$sec = 'SA_ASSETSANALYTIC';
	}
	elseif ($type == 1) {
		$sec = 'SA_WORKORDERANALYTIC';
	}
	else {
		$sec = 'SA_ITEMSTRANSVIEW';
	}

	if ($type == 1) {
		$title = sprintf(_("Top %s Manufactured Items in Fiscal Year"), $limit);
	}
	elseif ($type == 2) {
		$title = sprintf(_("Top %s Fixed Assets"), $limit);
	}
	else {
		$head_title = '';
		if ($invty_grp == 2) {
			$head_title == "Items";
		}
		else if ($invty_grp == 3) {
			$head_title == "Category";
		}
		else {
			$head_title == "Brands";
		}
		
		$head_title = $invty_grp == 2 ? "Items" : "Brands";
		$sub_title = $category_id != null ? "(" . get_category_name($category_id) . ")" : "";
		
		$title = 
		sprintf(
			_("Top %s Sold $head_title in Fiscal Year $sub_title"), 
			$limit
		);
	}	

	$result = get_stock_top($today, $limit, $type, $category_id, $invty_grp);

	display_title($title);	
	
	if ($type == 0) {

		if ($invty_grp == 2) {
			$th = array(_("Item Description"), _("Sales"), _("Costs"), _("Quantity Sold"));
		}
		else if ($invty_grp == 3) {
			$th = array(_("Item Category"), _("Sales"), _("Costs"), _("Quantity Sold"));
		}
		else {
			$th = array(_("Brand"), _("Sales"), _("Costs"), _("Quantity Sold"));
		}
	}	
	else {
		$th = array(_("Brand"), _("Amount"), _("Quantity Sold"));
	}	
		
	start_table(TABLESTYLE, "width='$width%'");
	table_header($th);
	check_page_security($sec);

	$k = 0; //row colour counter
	$i = 0;

	while ($row = db_fetch($result)) {

		alt_table_row_color($k);

		$name = $row["description"];
		$category_name = get_category_name($row["category_id"]);
		$brand_name = strtoupper($row["brand_name"]); 

		if ($invty_grp == 2) {
			label_cell($name);
		}
		else if ($invty_grp == 3) {
			label_cell($category_name);
		}
		else {
			label_cell($brand_name);
		}
		
		amount_cell($row['total']);
		
		if ($type == 0) {
			amount_cell($row['costs']);
		}

		label_cell($row['qty'], "align='center'");
		
		if ($pg != NULL) {

			if ($invty_grp == 2) {
				$pg->x[$i] = $name . " "; 
			}
			else if ($invty_grp == 3) {
				$pg->x[$i] = $category_name . "  "; 
			}
			else {
				$pg->x[$i] = $brand_name . " "; 
			}

			$pg->y[$i] = $row['total'];
			// if ($type == 0) {
			// 	$pg->z[$i] = $row['costs'];
			// }
		}	

		$i++;
		end_row();
	}
	end_table(2);
	return $title;
}

function dimension_top($today, $limit=10, $width="33", &$pg=null)
{

	$begin = begin_fiscalyear();
	$begin1 = date2sql($begin);
	$today1 = date2sql($today);
	$sql = "SELECT SUM(-t.amount) AS total, d.reference, d.name FROM
		".TB_PREF."gl_trans AS t,".TB_PREF."dimensions AS d WHERE
		(t.dimension_id = d.id OR t.dimension2_id = d.id) AND
		t.tran_date >= '$begin1' AND t.tran_date <= '$today1' GROUP BY d.id ORDER BY total DESC LIMIT $limit";
	$result = db_query($sql, "Transactions could not be calculated");
	$title = sprintf(_("Top %s Dimensions in fiscal year"), $limit);
	display_title($title);
	$th = array(_("Dimension"), _("Amount"));
	start_table(TABLESTYLE, "width='$width%'");
	table_header($th);
	check_page_security('SA_DIMTRANSVIEW');
	$k = 0; //row colour counter
	$i = 0;
	while ($row = db_fetch($result))
	{
		alt_table_row_color($k);
		$name = $row['reference']." ".$row["name"];
		label_cell($name);
		amount_cell($row['total']);
		if ($pg != null)
		{
			$pg->x[$i] = $name; 
			$pg->y[$i] = abs($row['total']);
		}	
		$i++;
		end_row();
	}
	end_table(2);
	return $title;
}

function gl_top($today, $width="33", &$pg=null)
{
	$begin = begin_fiscalyear();
	$begin1 = date2sql($begin);
	$today1 = date2sql($today);
	$sql = "SELECT SUM(amount) AS total, c.class_name, c.ctype FROM
		".TB_PREF."gl_trans,".TB_PREF."chart_master AS a, ".TB_PREF."chart_types AS t, 
		".TB_PREF."chart_class AS c WHERE
		account = a.account_code AND a.account_type = t.id AND t.class_id = c.cid
		AND IF(c.ctype > 3, tran_date >= '$begin1', tran_date >= '0000-00-00') 
		AND tran_date <= '$today1' GROUP BY c.cid ORDER BY c.cid"; 
	$result = db_query($sql, "Transactions could not be calculated");
	$title = _("Class Balances");
	display_title($title);
	start_table(TABLESTYLE2, "width='$width%'");
	check_page_security('SA_GLANALYTIC');
	$i = 0;
	$total = 0;
	while ($row = db_fetch($result))
	{
		if ($row['ctype'] > 3)
		{
			$total += $row['total'];
			$row['total'] = -$row['total'];
			if ($pg != null)
			{
				$pg->x[$i] = $row['class_name']; 
				$pg->y[$i] = abs($row['total']);
			}	
			$i++;
		}	
		label_row($row['class_name'], number_format2($row['total'], user_price_dec()), 
			"class='label' style='font-weight:bold;'", "style='font-weight:bold;' align=right");
	}
	$calculated = _("Calculated Return");
	label_row("&nbsp;", "");
	label_row($calculated, number_format2(-$total, user_price_dec()), 
		"class='label' style='font-weight:bold;'", "style='font-weight:bold;' align=right");
	if ($pg != null)
	{
		$pg->x[$i] = $calculated; 
		$pg->y[$i] = -$total;
	}
	end_table(2);
	return $title;
}

function gl_performance($today, $width="33", $weeks=5)
{
	global $SysPrefs;
	$pg = new graph();

	$begin = begin_fiscalyear();
	$begin1 = date2sql($begin);
	$today1 = date2sql($today);
	$sep = $SysPrefs->dateseps[user_date_sep()];
	$sql = "SELECT week_name, sales, costs 
		FROM(SELECT DATE_FORMAT(tran_date, '%Y{$sep}%u') AS week_name, 
			SUM(IF(c.ctype = 4, amount * -1, 0)) AS sales, 
			SUM(IF(c.ctype = 6, amount, 0)) AS costs FROM 
			".TB_PREF."gl_trans, ".TB_PREF."chart_master AS a, ".TB_PREF."chart_types AS t, 
			".TB_PREF."chart_class AS c WHERE(c.ctype = 4 OR c.ctype = 6) 
			AND account = a.account_code AND a.account_type = t.id AND t.class_id = c.cid 
			AND tran_date >= '$begin1' AND tran_date <= '$today1' 
			GROUP BY week_name ORDER BY week_name DESC LIMIT 0, $weeks) b 
		GROUP BY week_name ORDER BY week_name ASC";
	$result = db_query($sql, "Transactions could not be calculated");
	$title = _("Last $weeks weeks Performance");
	check_page_security('SA_GLANALYTIC');
	$i = 0;
	while ($row = db_fetch($result))
	{
		$pg->x[$i] = $row['week_name']; 
		$pg->y[$i] = $row['sales'];
		$pg->z[$i] = $row['costs'];
		$i++;
	}	
	
	_source_graphic($today, $title, _("Week"), $pg, _("Sales"), _("Costs"), 1);
}

function customer_trans($today) {

	$result = _customer_trans($today);

	$title = db_num_rows($result) . _(" Overdue Sales Invoices");
  	display_title($title);

	$th = array(
		_("Reference"), 
		_(""), _(""),
		_("Invoice Date"), 
		_(""), _(""),
		_("Customer"), 
		_("Total"), 
		_("Remainder"),	
		_(""), _(""),
		_("Total Days")
	);

	start_table(TABLESTYLE,"width='95%'");
	table_header($th);
  	$k = 0; //row colour counter

	while ($row = db_fetch($result)) {
		
		alt_table_row_color($k);

		label_cell(get_trans_view_str(ST_SALESINVOICE, $row["trans_no"], $row['reference']), "nowrap align='left'");
		label_cell(''); label_cell('');
		label_cell(sql2date($row['tran_date']), "style='color: blue'");
		label_cell(''); label_cell('');

		$name = $row["debtor_ref"] . " - ". $row["name"];
		label_cell($name, "nowrap align='left'");

		amount_cell($row['total']);
		amount_cell($row['remainder'], "bold");
		label_cell(''); label_cell('');
		label_cell($row['days'], "nowrap align='right'");

		end_row();
	}
	end_table(2);
}

function calculate_next_invoice($row)
{
	if ($row["last_sent"] == '0000-00-00')
		$next = sql2date($row["begin"]);
	else
		$next = sql2date($row["last_sent"]);
	$next = add_months($next, $row['monthly']);
	$next = add_days($next, $row['days']);
	return add_days($next,-1);
}

function customer_recurrent_invoices($today)
{
	$result = get_recurrent_invoices($today);
	$title = _("Overdue Recurrent Invoices");
  	display_title($title);
	$th = array(_("Description"), _("Template No"),_("Customer"),_("Branch")."/"._("Group"),_("Next invoice"));
	start_table(TABLESTYLE, "width=70%");
  	table_header($th);
	$k = 0;
	while ($row = db_fetch($result)) 
	{
		if (!$row['overdue'])
			continue;
		alt_table_row_color($k);

		label_cell($row["description"]);
		label_cell(get_customer_trans_view_str(ST_SALESORDER, $row["order_no"]));
		if ($row["debtor_no"] == 0)
		{
			label_cell("");

			label_cell(get_sales_group_name($row["group_no"]));
		}
		else
		{
			label_cell(get_customer_name($row["debtor_no"]));
			label_cell(get_branch_name($row['group_no']));
		}
		label_cell(calculate_next_invoice($row),  "align='center'");
		end_row();
	}
	end_table(2);
}

function supplier_trans($today)
{
	$today = date2sql($today);
	$sql = "SELECT trans.trans_no, trans.reference, trans.tran_date, trans.due_date, s.supplier_id, 
		s.supp_name, s.curr_code,
		(trans.ov_amount + trans.ov_gst + trans.ov_discount) AS total,  
		(trans.ov_amount + trans.ov_gst + trans.ov_discount - trans.alloc) AS remainder,
		DATEDIFF('$today', trans.due_date) AS days 	
		FROM ".TB_PREF."supp_trans as trans, ".TB_PREF."suppliers as s 
		WHERE s.supplier_id = trans.supplier_id
			AND trans.type = ".ST_SUPPINVOICE." AND (ABS(trans.ov_amount + trans.ov_gst + 
				trans.ov_discount) - trans.alloc) > ".FLOAT_COMP_DELTA."
			AND DATEDIFF('$today', trans.due_date) > 0 ORDER BY days DESC";
	$result = db_query($sql);
	$title = db_num_rows($result) . _(" overdue Purchase Invoices");
	display_title($title);
	$th = array("#", _("Ref."), _("Date"), _("Due Date"), _("Supplier"), _("Currency"), _("Total"), 
		_("Remainder"),	_("Days")
	);

	start_table(TABLESTYLE, "width=100%");
	table_header($th);
	$k = 0; //row colour counter
	while ($row = db_fetch($result))
	{
		alt_table_row_color($k);
		label_cell(get_trans_view_str(ST_SUPPINVOICE, $row["trans_no"]));
		label_cell($row['reference']);
		label_cell(sql2date($row['tran_date']));
		label_cell(sql2date($row['due_date']));
		$name = $row["supplier_id"]." ".$row["supp_name"];
		label_cell($name);
		label_cell($row['curr_code']);
		amount_cell($row['total']);
		amount_cell($row['remainder']);
		label_cell($row['days'], "align='right'");
		end_row();
	}
	end_table(2);
}

function bank_balance($today, $width)
{
	$today = date2sql($today);
	$sql = "SELECT bank_act, bank_account_name, bank_curr_code, SUM(amount) balance FROM ".TB_PREF."bank_trans bt 
	            INNER JOIN ".TB_PREF."bank_accounts ba ON bt.bank_act = ba.id
	            WHERE trans_date <= '$today'
	            AND inactive <> 1
	            GROUP BY bank_act, bank_account_name
				ORDER BY bank_account_name";
	$result = db_query($sql);
	$title = _("Bank Account Balances");
	display_title($title);
	$th = array(_("Account"), _("Currency"), _("Balance"));
	start_table(TABLESTYLE, "width='$width%'");
	table_header($th);
	$k = 0; //row colour counter
	while ($row = db_fetch($result))
	{
		alt_table_row_color($k);
		label_cell(viewer_link($row["bank_account_name"], 'gl/inquiry/bank_inquiry.php?bank_account='.$row["bank_act"]));
		label_cell($row["bank_curr_code"]);
		amount_cell($row['balance']);
		end_row();
	}
	end_table(1);
}