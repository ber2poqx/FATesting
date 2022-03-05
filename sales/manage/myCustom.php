<?php
$path_to_root = "../..";

include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/admin/db/company_db.inc");

$type = $_POST['type'];

if($type == 'areaSelect'){
	$id = $_POST['name'];
	$sql = "SELECT area_code, description, collectors_id, inactive FROM ".TB_PREF."areas WHERE area_code=$id";
	$result = db_query($sql, "could not get group");
	$row = db_fetch($result);
	$data = array('name' =>$row[2] );
	echo json_encode($data);
}


if($type == 'collectorSelect'){
	$ids = $_POST['names'];
	$sql = "SELECT area_code, description, collectors_id FROM ".TB_PREF. "areas
    INNER JOIN ".TB_PREF."users ON ".TB_PREF."areas.collectors_id = users.user_id";
	$result = db_query($sql, "could not get group");
	$row = db_fetch($result);
	$data = array('names' =>$row[1] );
	echo json_encode($data);
}


if($type == 'munizipcodeSelect'){
	$idss = $_POST['mcode'];
	$sql = "SELECT muni_code, municipality, zipcode, inactive FROM ".TB_PREF."municipality_zipcode WHERE muni_code=$idss";
	$result = db_query($sql, "could not get group");
	$row = db_fetch($result);
	$data = array('mcode' =>$row[0] );
	echo json_encode($data);
}

?>