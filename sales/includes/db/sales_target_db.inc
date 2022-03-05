<?php

/******************
Added by Prog6
11/03/2020
*******************/


// ================================== AMOUNT QUERY ========================================================================



function get_all_sale_target_amount($show_inactive, $category, $type) {
    $sql = "SELECT * FROM ".TB_PREF."sales_target WHERE type = '$type'";	

	if($category != 0 || $category != null)
	{
		$sql .= " AND category_id = '$category'";
	}
	$sql .= " ORDER BY year DESC";
    return  db_query($sql, "could not get sales target amount");
}



function add_sale_target($year, $jan, $feb, $mar, $apr, $may, $jun, $jul, $aug, $sep, $oct, $nov, $dec){
	$sql = "INSERT INTO ".TB_PREF."sales_target (year, jan, feb, mar, apr, may, jun, jul, aug, sep, oct, nov, dece, type) 
	VALUES('".db_escape($year)."','".db_escape($jan)."','".db_escape($feb)."','".db_escape($mar)."','".db_escape($apr)."','".db_escape($may)."','".db_escape($jun)."','".db_escape($jul)."','".db_escape($aug)."','".db_escape($sep)."','".db_escape($oct)."','".db_escape($nov)."','".db_escape($dec)."', 'amount')";

	db_query($sql,"Sales type could not be added.");
}

function get_sale_target_amount($selected_id)
{
	$sql="SELECT * FROM ".TB_PREF."sales_target WHERE type = 'amount' AND id = $selected_id";

	$result = db_query($sql,"the sales type could not be retrieved");

	return db_fetch($result);
}

function delete_sale_target($selected_id)
{
	$sql="DELETE FROM ".TB_PREF."sales_target WHERE id=".db_escape($selected_id);

	db_query($sql,"this sales target year could not be deleted");
}

function update_target_amount($selected_id, $year, $jan, $feb, $mar, $apr, $may, $jun, $jul, $aug, $sep, $oct, $nov, $dec)
{
	$sql = "UPDATE ".TB_PREF."sales_target SET 
	 	
	 jan=".db_escape($jan) ."
	 , feb=".db_escape($feb) ."
	 , mar=".db_escape($mar) ."
	 , apr=".db_escape($apr) ."
	 , may=".db_escape($may) ."
	 , jun=".db_escape($jun) ."
	 , jul=".db_escape($jul) ."
	 , aug=".db_escape($aug) ."
	 , sep=".db_escape($sep) ."
	 , oct=".db_escape($oct) ."
	 , nov=".db_escape($nov) ."
	 , dece=".db_escape($dec) ."
	 WHERE id = $selected_id";
   	db_query($sql,"The target year could not be updated");	
}

function add_target_amount($year, $jan, $feb, $mar, $apr, $may, $jun, $jul, $aug, $sep, $oct, $nov, $dec)
{
	$sql = "INSERT INTO ".TB_PREF."sales_target 
		   (year, jan, feb, mar, apr, may, jun, jul, aug, sep, oct, nov, dece, type)
    VALUES (".db_escape($year). " ,".db_escape($jan). ", ".db_escape($feb). ", ".db_escape($mar). ", ".db_escape($apr). ", ".db_escape($may). ", ".db_escape($jun). ", ".db_escape($jul). ", ".db_escape($aug). ", ".db_escape($sep). ", ".db_escape($oct). ", ".db_escape($nov). ", ".db_escape($dec). ", 'amount')";
   	db_query($sql,"The target year could not be added");
}

//=====================================================================================================================================




// ================================== QUANTITY QUERY ========================================================================

function check_existing_target_year_quantity($check_year){
	$sql="SELECT COUNT(*) FROM ".TB_PREF."sales_target WHERE type = 'quantity' AND year = ".db_escape($check_year)."";

	$result = db_query($sql,"the sales type could not be retrieved");

	$count =  db_fetch($result);

	return $count[0];
}

function get_all_sale_target_quantity($year_id) {
    $sql = "SELECT * FROM ".TB_PREF."sales_target";
	
	$sql .= " WHERE id =".db_escape($year_id);
    return  db_query($sql, "could not get sales target quantity");
}

function delete_sale_target_quantity($selected_id)
{
	$sql="DELETE FROM ".TB_PREF."sales_target WHERE id=".db_escape($selected_id);

	db_query($sql,"this sales target year could not be deleted");
}

function update_target_quantity($selected_id, $year, $jan, $feb, $mar, $apr, $may, $jun, $jul, $aug, $sep, $oct, $nov, $dec)
{
	$sql = "UPDATE ".TB_PREF."sales_target SET 
	 	
	 jan=".db_escape($jan) ."
	 , feb=".db_escape($feb) ."
	 , mar=".db_escape($mar) ."
	 , apr=".db_escape($apr) ."
	 , may=".db_escape($may) ."
	 , jun=".db_escape($jun) ."
	 , jul=".db_escape($jul) ."
	 , aug=".db_escape($aug) ."
	 , sep=".db_escape($sep) ."
	 , oct=".db_escape($oct) ."
	 , nov=".db_escape($nov) ."
	 , dece=".db_escape($dec) ."
	 WHERE id = $selected_id";
   	db_query($sql,"The target year could not be updated");	
}

function add_target_quantity($year, $jan, $feb, $mar, $apr, $may, $jun, $jul, $aug, $sep, $oct, $nov, $dec)
{
	$sql = "INSERT INTO ".TB_PREF."sales_target 
		   (year, jan, feb, mar, apr, may, jun, jul, aug, sep, oct, nov, dece, type)
    VALUES (".db_escape($year). " ,".db_escape($jan). ", ".db_escape($feb). ", ".db_escape($mar). ", ".db_escape($apr). ", ".db_escape($may). ", ".db_escape($jun). ", ".db_escape($jul). ", ".db_escape($aug). ", ".db_escape($sep). ", ".db_escape($oct). ", ".db_escape($nov). ", ".db_escape($dec). ", 'quantity')";
   	db_query($sql,"The target year could not be added");
}
//=====================================================================================================================================



// ==================== ================  ========= SALES TARGET MAIN QUERIES ============= ============= ============

function add_sales_target($year, $jan, $feb, $mar, $apr, $may, $jun, $jul, $aug, $sep, $oct, $nov, $dece, $type, $cat, $type_id){
	
	if($type == 1)
	{
		$type1 = 'amount';
	}
	else if($type == 2)
	{
		$type1 = 'quantity';
	}
	else
	{
		$type1 = 'error';
	}

	$sql = "INSERT INTO ".TB_PREF."sales_target (year, jan, feb, mar, apr, may, jun, jul, aug, sep, oct, nov, dece, type, category_id, type_id) 
	VALUES(".db_escape($year).",".db_escape($jan).",".db_escape($feb).",".db_escape($mar).",".db_escape($apr).",".db_escape($may).",".db_escape($jun).",".db_escape($jul).",".db_escape($aug).",".db_escape($sep).",".db_escape($oct).",".db_escape($nov).",".db_escape($dece).",".db_escape($type1).",".db_escape($cat).",".db_escape($type_id).")";

	db_query($sql,"Sales type could not be added.");
}

function update_target($selected_id, $year, $jan, $feb, $mar, $apr, $may, $jun, $jul, $aug, $sep, $oct, $nov, $dece, $type, $cat, $type_id)
{
	if($type == 1)
	{
		$type1 = 'amount';
	}
	else if($type == 2)
	{
		$type1 = 'quantity';
	}
	else
	{
		$type1 = 'error';
	}

	$sql = "UPDATE ".TB_PREF."sales_target SET 
	 	
	 jan=".db_escape($jan) ."
	 , feb=".db_escape($feb) ."
	 , mar=".db_escape($mar) ."
	 , apr=".db_escape($apr) ."
	 , may=".db_escape($may) ."
	 , jun=".db_escape($jun) ."
	 , jul=".db_escape($jul) ."
	 , aug=".db_escape($aug) ."
	 , sep=".db_escape($sep) ."
	 , oct=".db_escape($oct) ."
	 , nov=".db_escape($nov) ."
	 , dece=".db_escape($dece) ."
	 , oct=".db_escape($type1) ."
	 , nov=".db_escape($cat) ."
	 , dece=".db_escape($type_id) ."
	 WHERE type = 'quantity' AND id = $selected_id";
   	db_query($sql,"The target year could not be updated");	
}

function check_existing_target_year($check_year, $type, $category){
	

	$sql="SELECT COUNT(*) FROM ".TB_PREF."sales_target WHERE type_id = ".db_escape($type)." AND year = ".db_escape($check_year)." AND category_id = ".db_escape($category)."";

	$result = db_query($sql,"check_existing_target_year function in sales_target_db encountered problem.");

	$count =  db_fetch($result);

	return $count[0];
}


function get_sale_target_quantity($selected_id)
{
	$sql="SELECT * FROM ".TB_PREF."sales_target WHERE id =".db_escape($selected_id);

	$result = db_query($sql,"the sales type could not be retrieved");

	return db_fetch($result);
}