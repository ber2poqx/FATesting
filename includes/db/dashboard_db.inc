<?php

/**
 * Added By: spyrax10
 * Date Added: 23 Apr 2022
 */

function get_stock_top($today, $limit = 10, $type = 0, $category_id = null) {

    $begin = begin_fiscalyear();
	$begin1 = date2sql($begin);
	$today1 = date2sql($today);
 
	if ($type == 0) {
		$sql = 
        "SELECT SUM((trans.unit_price * trans.quantity) * d.rate) AS total, 
            s.stock_id, s.description, 
			SUM(trans.quantity) AS qty, 
            SUM((s.material_cost + s.overhead_cost + s.labour_cost) * trans.quantity) AS costs,
			IB.name AS brand_name
        
        FROM ".TB_PREF."debtor_trans_details AS trans 
            INNER JOIN ".TB_PREF."stock_master s ON trans.stock_id = s.stock_id 
            INNER JOIN ".TB_PREF."debtor_trans d ON trans.debtor_trans_no = d.trans_no
                AND trans.debtor_trans_type = d.type  
			INNER JOIN ".TB_PREF."item_brand IB ON s.brand = IB.id
            
		WHERE (d.type = ".ST_SALESINVOICE." OR d.type = ".ST_CUSTCREDIT.") ";

		if ($category_id != null) {
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

	$sql .= "AND tran_date <= '$today1' 
        GROUP by s.brand
        ORDER BY total DESC, s.stock_id 
		LIMIT $limit";

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
