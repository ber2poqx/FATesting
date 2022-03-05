<?php

function get_promotional_pricing_AllDB($branch_code, $Itemcateg_search, $Itembrand_search, $start, $limit, $search, $showall=false)
{
	$db_coy = Get_db_coy($branch_code);
    set_global_connection($db_coy);
	
	$sql = "SELECT A.serialise_item_code, A.serialise_lot_no, IFNULL(A.serialise_chasis_no, '') AS serialise_chasis_no,
			A.serialise_loc_code, A.serialise_lcp_promotional_price, B.stock_id, D.category_id, D.brand, C.description, E.name
			FROM ".TB_PREF."item_serialise A
			LEFT JOIN item_codes B ON B.item_code = A.serialise_item_code
			LEFT JOIN stock_master D ON D.stock_id = B.stock_id
			LEFT JOIN stock_category C ON D.category_id = C.category_id
			LEFT JOIN item_brand E ON E.id = D.brand
			WHERE A.serialise_lcp_promotional = 1 AND A.serialise_lot_no <> '' 
			AND D.category_id = ".db_escape($Itemcateg_search)." 
			AND D.brand = ".db_escape($Itembrand_search)." 
			AND B.stock_id LIKE ".db_escape("%" . $search. "%")."
			GROUP BY A.serialise_lot_no ORDER BY A.serialise_item_code ASC";

	$sqllimit = $sql ." limit $start, $limit";

	if($showall){
		$result = db_query($sql, "The promotional price from other database could not be retrieved");
	}else{
		$result = db_query($sqllimit, "The promotional price from other database could not be retrieved");
	}

	set_global_connection();
	return $result;
}

function get_promotional_pricing_category($branch_code)
{	
	$db_coy = Get_db_coy($branch_code);
    set_global_connection($db_coy);

	$sql = "SELECT category_id, description
			FROM ".TB_PREF."stock_category 
			WHERE dflt_mb_flag <> 'D'";

	$result = db_query($sql, "The promotional price from other database could not be retrieved");
	
	set_global_connection();		
	return $result;
}

function get_promotional_pricing_brand($branch_code, $Itemcateg_search)
{
	$db_coy = Get_db_coy($branch_code);
    set_global_connection($db_coy);

	$sql = "SELECT A.id, A.name, B.category_id
			FROM ".TB_PREF."item_brand A
			LEFT JOIN item_codes B ON B.brand = A.id
			WHERE B.category_id = ".db_escape($Itemcateg_search)."
			GROUP BY A.name";

	$result = db_query($sql, "The promotional price from other database could not be retrieved");
	
	set_global_connection();	
	return $result;
}

function get_promotional_pricing_itemcode($branch_code, $Itemcateg_search, $Itembrand_search)
{
	$db_coy = Get_db_coy($branch_code);
    set_global_connection($db_coy);
	
	$sql = "SELECT B.stock_id, A.serialise_lot_no, C.category_id, C.brand
			FROM ".TB_PREF."item_serialise A
			LEFT JOIN item_codes B ON B.item_code = A.serialise_item_code
			LEFT JOIN stock_master C ON C.stock_id = B.stock_id
			WHERE A.serialise_lcp_promotional = 1 AND A.serialise_lot_no <> '' 
			AND C.category_id = ".db_escape($Itemcateg_search)." 
			AND C.brand = ".db_escape($Itembrand_search)." 
			GROUP BY B.stock_id ORDER BY B.stock_id ASC";

	$result = db_query($sql, "The promotional price from other database could not be retrieved");

	set_global_connection();
	return $result;
}

function update_promotional_price_per_serial($serialise_item_code, $serialise_lot_no, $serialise_chasis_no, $serialise_loc_code, 
	$serialise_lcp_promotional_price)
{
	$db_coy = Get_db_coy($serialise_loc_code);
    set_global_connection($db_coy);

	$sql = "UPDATE ".TB_PREF."item_serialise
	SET serialise_item_code=" . db_escape($serialise_item_code) . ", 
		serialise_lot_no=" . db_escape($serialise_lot_no) . ",
		serialise_chasis_no=" . db_escape($serialise_chasis_no) . ",
		serialise_loc_code=" . db_escape($serialise_loc_code) . ",
		serialise_lcp_promotional_price=" . db_escape($serialise_lcp_promotional_price) . "
		WHERE serialise_item_code = ".db_escape($serialise_item_code)." AND serialise_lot_no = ".db_escape($serialise_lot_no);

	$result = db_query($sql, "The promotional price from other database could not be retrieved");

	set_global_connection();
	return $result;
}