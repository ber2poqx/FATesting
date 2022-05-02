<?php

/**
 * Added By: spyrax10
 * Date Added: 23 Apr 2022
 */

function get_stock_top($today, $limit = 10, $type = 0, $category_id = null, $invty_grp = 0) {

    $begin = begin_fiscalyear();
	$begin1 = date2sql($begin);
	$today1 = date2sql($today);
 
	if ($type == 0) {
		$sql = 
        "SELECT SUM((trans.unit_price * trans.quantity) * d.rate) AS total, 
            s.stock_id, s.description, 
			SUM(trans.quantity) AS qty, 
            SUM((s.material_cost + s.overhead_cost + s.labour_cost) * trans.quantity) AS costs,
			IB.name AS brand_name, s.category_id
        
        FROM ".TB_PREF."debtor_trans_details AS trans 
            INNER JOIN ".TB_PREF."stock_master s ON trans.stock_id = s.stock_id 
            INNER JOIN ".TB_PREF."debtor_trans d ON trans.debtor_trans_no = d.trans_no
                AND trans.debtor_trans_type = d.type  
			INNER JOIN ".TB_PREF."item_brand IB ON s.brand = IB.id
            
		WHERE (d.type = ".ST_SALESINVOICE." OR d.type = ".ST_CUSTCREDIT.") ";

		if ($category_id != null && $invty_grp != 3) {
			$sql .= " AND s.category_id = " . db_escape($category_id);
		}
	}
	else {
		$sql = 
        "SELECT SUM(m.qty * (s.material_cost + s.labour_cost + s.overhead_cost)) AS total, 
            s.stock_id, s.description, 
			SUM(qty) AS qty 
        
        FROM ".TB_PREF."stock_master AS s, 
            ".TB_PREF."stock_moves AS m 
		WHERE s.stock_id = m.stock_id ";
		
        if ($type == 1) {
            $sql .= "AND s.mb_flag = 'M' AND m.type <> ".ST_CUSTDELIVERY." AND m.type <> ".ST_CUSTCREDIT." ";
        }	
		elseif ($type == 2) {
            $sql .= "AND s.mb_flag = 'F' ";
        }	
	}

	if ($type != 2) {
		$sql .= "AND tran_date >= '$begin1' ";
	}

	$sql .= "AND tran_date <= '$today1'";

	if ($invty_grp == 2) {
		$sql .= " GROUP BY trans.stock_id ";
	}
	else if ($invty_grp == 3) {
		$sql .= " GROUP BY s.category_id ";
	}
	else {
		$sql .= " GROUP BY s.brand ";
	}

    $sql .= " ORDER BY total DESC, s.stock_id 
		LIMIT $limit";

    return db_query($sql);
}

function get_customer_top($today, $limit = 10, $sales_grp = 0) {

	$begin = begin_fiscalyear();
	$begin1 = date2sql($begin);
	$today1 = date2sql($today);

	$sql = 
	"SELECT SUM((ov_amount + ov_discount) * rate * IF(trans.type = ".ST_CUSTCREDIT.", -1, 1)) AS total, 
		d.debtor_no, d.name, d.debtor_ref, AR.description AS area_name, CN.real_name AS collector_name 
	
	FROM ".TB_PREF."debtor_trans AS trans 
		INNER JOIN ".TB_PREF."debtors_master AS d ON trans.debtor_no = d.debtor_no
		INNER JOIN ".TB_PREF."areas AS AR ON d.area = AR.area_code 
		LEFT JOIN " . TB_PREF . "users CN ON AR.collectors_id = CN.user_id
		
	WHERE (trans.type = ".ST_SALESINVOICE." OR trans.type = ".ST_CUSTCREDIT.")
		AND tran_date >= '$begin1' AND tran_date <= '$today1'";

	if ($sales_grp == 2) {
		$sql .= " GROUP BY d.area";
	}
	else if ($sales_grp == 3) {
		$sql .= " GROUP BY AR.collectors_id";
	}
	else {
		$sql .= " GROUP BY d.debtor_no";
	}
	
	$sql .= " ORDER BY total DESC, d.debtor_no 
	LIMIT $limit";

	return db_query($sql);
}

function _customer_trans($today) {
	$today = date2sql($today);

	$sql = 
	"SELECT trans.trans_no, trans.reference, trans.tran_date, trans.due_date, debtor.debtor_no, 
		debtor.name, branch.br_name, debtor.curr_code, debtor.debtor_ref, 
		(trans.ov_amount + trans.ov_gst + trans.ov_freight 
			+ trans.ov_freight_tax + trans.ov_discount)	AS total,  
		(trans.ov_amount + trans.ov_gst + trans.ov_freight 
			+ trans.ov_freight_tax + trans.ov_discount - trans.alloc) AS remainder,
		DATEDIFF('$today', trans.due_date) AS days 

	FROM ".TB_PREF."debtor_trans AS trans  
		INNER JOIN ".TB_PREF."debtors_master AS debtor ON debtor.debtor_no = trans.debtor_no
		INNER JOIN ".TB_PREF."cust_branch AS branch ON trans.branch_code = branch.branch_code

	WHERE trans.type = ".ST_SALESINVOICE." 
		AND (trans.ov_amount + trans.ov_gst + trans.ov_freight 
			+ trans.ov_freight_tax + trans.ov_discount - trans.alloc) > ".FLOAT_COMP_DELTA." 
		AND DATEDIFF('$today', trans.due_date) > 0 ORDER BY days DESC";
	
	return db_query($sql);
}

function _source_graphic($today, $title, $x_axis, $pg, $graphic1, $graphic2 = null, $type = 2) {
	
	if (count($pg->y) == 0 || (count($pg->y) == 1 && $pg->y[0] == 0)) {
		return;
	}

	display_title("$title ($today)");	
	//$pg->title     = $title . " - " . $today;
	$pg->axis_x    = $x_axis;
	$pg->axis_y    = _("Amount");
	$pg->graphic_1 = $graphic1;
	
	if ($graphic2 != null) {
		$pg->graphic_2 = $graphic2;
	}
	
	$pg->type      = $type;
	$pg->skin      = 1;
	$pg->built_in  = false;

	$filename = company_path(). "/pdf_files/". random_id().".png";
	$pg->display($filename, true);

	start_table(TABLESTYLE);
	start_row();
	echo "<td>";
	echo "<img src='$filename' border='0' alt='$title'>";
	echo "</td>";
	end_row();
	end_table(1);
}
