<?php

/**
 * Added By: spyrax10
 * Date Added: 23 Apr 2022
 */

function get_stock_top($today, $limit = 10, $type = 0) {

    $begin = begin_fiscalyear();
	$begin1 = date2sql($begin);
	$today1 = date2sql($today);
 
	if ($type == 0) {
		$sql = 
        "SELECT SUM((trans.unit_price * trans.quantity) * d.rate) AS total, 
            s.stock_id, s.description, 
			SUM(trans.quantity) AS qty, 
            SUM((s.material_cost + s.overhead_cost + s.labour_cost) * trans.quantity) AS costs 
        
        FROM ".TB_PREF."debtor_trans_details AS trans, 
            ".TB_PREF."stock_master AS s, 
            ".TB_PREF."debtor_trans AS d 
            
		WHERE trans.stock_id = s.stock_id 
            AND trans.debtor_trans_type = d.type 
            AND trans.debtor_trans_no = d.trans_no
			AND (d.type = ".ST_SALESINVOICE." OR d.type = ".ST_CUSTCREDIT.") ";
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
        GROUP by s.stock_id 
        ORDER BY total DESC, s.stock_id 
		LIMIT $limit";

    return db_query($sql);
}