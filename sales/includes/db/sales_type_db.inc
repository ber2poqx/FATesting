<?php

/******************
Added by Prog6
11/03/2020
*******************/

function write_sale_type($selected, $description)
{
    if($selected!='' || $selected!=0)
		$sql = "UPDATE ".TB_PREF."sales_type SET
	 	name = ".db_escape($description)."
        	WHERE id = ".db_escape($selected);
    else
		$sql = "INSERT INTO ".TB_PREF."sales_type (name) VALUES(".db_escape($description).")";

	db_query($sql,"Sales type could not be updated");
}

function add_sale_type($description){
	$sql = "INSERT INTO ".TB_PREF."sales_type (name) VALUES('".$description."')";

	db_query($sql,"Sales type could not be added.");
}

function delete_sale_type($id)
{
	$sql="DELETE FROM ".TB_PREF."sales_type WHERE id=".db_escape($id);

	db_query($sql,"this sales type could not be deleted");
}

function get_sale_type_rec($brandcode)
{
	$sql="SELECT * FROM ".TB_PREF."sales_type WHERE id='".db_escape($brandcode)."'";

	$result = db_query($sql,"the sales type could not be retrieved");

	return db_fetch($result);
}
function get_sale_type($brandcode)
{
	$sql="SELECT * FROM ".TB_PREF."sales_type where id='$brandcode'";

	$result = db_query($sql,"the sales type could not be retrieved");

	return db_fetch($result);
}

function get_sale_type_descr($id)
{
	$sql = "SELECT description FROM ".TB_PREF."sales_type WHERE id=".db_escape($id);

	$result = db_query($sql, "could not get sales type description");

	$row = db_fetch_row($result);
	return $row[0];
}


function sale_type_used($id) {
	$sql= "SELECT COUNT(*) FROM ".TB_PREF."customers WHERE sales_type=".$id;
	$result = db_query($sql, "could not query Customers");
	$myrow = db_fetch_row($result);
	return ($myrow[0] > 0);
}


function get_all_sale_type($all=false) {
    $sql = "SELECT * FROM ".TB_PREF."sales_type";
	if (!$all) $sql .= " WHERE !inactive";
	$sql .= " ORDER BY name";
    return  db_query($sql, "could not get sales type");
}

//----------start(AlbertP)--------------------------------
function get_all_sales_reason($all=false) {
    $sql = "SELECT * FROM ".TB_PREF."sales_reason";
	if (!$all) $sql .= " WHERE !inActive";
	$sql .= " ORDER BY reason";
    return  db_query($sql, "could not get sales reason");
}
function delete_sale_reason($id)
{
	$sql="DELETE FROM ".TB_PREF."sales_reason WHERE id=".db_escape($id);

	db_query($sql,"this sales reason could not be deleted");
}
function add_sale_reason($description){
	$sql = "INSERT INTO ".TB_PREF."sales_reason (reason) VALUES('".$description."')";

	db_query($sql,"Sales reason could not be added.");
}
function get_sale_reason($brandcode)
{
	$sql="SELECT * FROM ".TB_PREF."sales_reason where id='$brandcode'";

	$result = db_query($sql,"the sales reason could not be retrieved");

	return db_fetch($result);
}

function write_sale_reason($selected, $description)
{
    if($selected!='' || $selected!=0)
		$sql = "UPDATE ".TB_PREF."sales_reason SET
	 	reason = ".db_escape($description)."
        	WHERE id = ".db_escape($selected);
    else
		$sql = "INSERT INTO ".TB_PREF."sales_reason (reason) VALUES(".db_escape($description).")";

	db_query($sql,"Sales reason could not be updated");
}
//---------------end(albertP)------------------------