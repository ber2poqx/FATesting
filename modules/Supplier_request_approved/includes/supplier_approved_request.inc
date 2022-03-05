<?php

function get_all_supplier_request_to_HO($start, $limit, $status, $STATUSSERACH, $showall=false)
{
	$db_coy = 0;
    set_global_connection($db_coy);
	$sql = "SELECT id, supplier_ref, supp_name, status AS STATUS, inactive
	FROM ".TB_PREF."supplier_request 
	WHERE !inactive AND STATUS=" . db_escape($STATUSSERACH) . " AND supp_name LIKE ".db_escape("%" . $status. "%");

	$sqllimit = $sql ." limit $start, $limit";

	if($showall){
		$result = db_query($sql, "could not get supplier request");
	}else{
		$result = db_query($sqllimit, "could not get supplier request");
	}

	return $result;
}

function update_get_all_supplier_request_to_HO($id, $supplier_ref, $supp_name, $status, $inactive)
{
	$db_coy = 0;
    set_global_connection($db_coy);

	$sql = "UPDATE ".TB_PREF."supplier_request
	SET supplier_ref=" . db_escape($supplier_ref) . ", 
		supp_name=" . db_escape($supp_name) . ",
		status=" . db_escape($status) . ",
		inactive=" . db_escape($inactive) . "
		WHERE id = ".db_escape($id);

	db_query($sql,"The customer could not be updated");
}

/*
function get_supplier_request_to_HO()
{
	$db_coy = 0;
    set_global_connection($db_coy);

	$sql="SELECT DISTINCT status AS STATUSSERACH FROM ".TB_PREF."supplier_request";

	return db_query($sql,"the supplier request could not be retrieved");
}
*/

function supplier_request_used($id)
{
	$db_coy = 0;
    set_global_connection($db_coy);
	$sql= "SELECT * FROM ".TB_PREF."supplier_request WHERE status = 1 AND id=".$id;
	$result = db_query($sql, "could not query Suppliers");
	$myrow = db_fetch_row($result);
	return ($myrow[0] > 0);
}

function supplier_request_already_exist($description)
{
	$db_coy = 0;
    set_global_connection($db_coy);
	$sql = "SELECT COUNT(*) FROM ".TB_PREF."supplier_request
			WHERE supp_name = ".db_escape($description);
	$result = db_query($sql, "check request failed");
	$count =  db_fetch($result);

	return $count[0];
}