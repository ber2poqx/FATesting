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
function add_serialitems_detail($grnid, $grnlineid, $lot_no, $order_quantity, $manufacture_date, $expire_date,$item_code,$loc_code, $chasis_no='', $trans_type = 25, $lcp_promotional=0)
{
	if(is_date($manufacture_date)){
	
		$manufacture_date = date2sql($manufacture_date);
	}else{
		//$manufacture_date='';
	}
	if(is_date($expire_date)){
	
		$expire_date = date2sql($expire_date);
	}else{
		//$expire_date='NULL';
	}
	if($lcp_promotional==''){
	    $lcp_promotional=0;
	}
	set_global_connection();
	//$manufacture_date = date2sql($manufacture_date);
	//$expire_date = date2sql($expire_date);
	if(!get_serialise_id($item_code,$lot_no)){
	    
	
	   $sql = "INSERT INTO ".TB_PREF."item_serialise (serialise_grn_id, serialise_grn_items_id, serialise_lot_no, serialise_qty, serialise_manufacture_date, serialise_expire_date, serialise_item_code, serialise_loc_code, serialise_chasis_no, serialise_trans_type, serialise_lcp_promotional) VALUES (".
	   	   db_escape($grnid).", ".db_escape($grnlineid).", ".db_escape($lot_no).", ".db_escape($order_quantity).", ".db_escape($manufacture_date).", ".db_escape($expire_date).", ".db_escape($item_code).", ".db_escape($loc_code).", ".db_escape($chasis_no).", ".db_escape($trans_type).", ".db_escape($lcp_promotional).")";

	   db_query($sql, "could not add serial items details");
	}
}



function update_serialitems_detail($selected_id, $lot_no, $manufacture_date, $order_quantity, $expire_date, $chasis_no='', $trans_type = 25, $lcp_promotional=0)
{
	if(is_date($manufacture_date)){
	
		$manufacture_date = date2sql($manufacture_date);
	}else{
		//$manufacture_date='1976-01-01';
	}
	if(is_date($expire_date)){
	
		$expire_date = date2sql($expire_date);
	}else{
		//$expire_date='1976-01-01';
	}
	$sql = "UPDATE ".TB_PREF."item_serialise SET serialise_lot_no=".db_escape($lot_no)
			.", serialise_qty=".db_escape($order_quantity)
			.", serialise_manufacture_date=".db_escape($manufacture_date)
			.", serialise_expire_date=".db_escape($expire_date)
			.", serialise_chasis_no=".db_escape($chasis_no)
			.", serialise_lcp_promotional=".db_escape($lcp_promotional)
			.", serialise_trans_type=".db_escape($trans_type).""
			."WHERE serialise_id=".db_escape($selected_id);

	db_query($sql, "could not update serial items detail");
}

//Modified by spyrax10
function get_all_serialitems_details($grnid,$grnlineid)
{
	$sql = "SELECT * , SMO.trans_id
	FROM ".TB_PREF."item_serialise 
	INNER JOIN ".TB_PREF."item_codes ON item_serialise.serialise_item_code=item_codes.item_code 
	LEFT JOIN ".TB_PREF."stock_moves SMO ON item_serialise.serialise_grn_id = SMO.trans_no 
        AND item_serialise.serialise_trans_type = SMO.type 
		AND item_serialise.serialise_lot_no = SMO.lot_no 
		AND item_serialise.serialise_chasis_no = SMO.chassis_no 
	WHERE (serialise_grn_id = ".db_escape($grnid)." 
	AND serialise_grn_items_id=".db_escape($grnlineid).") 
	AND (serialise_trans_type=25 || serialise_trans_type=0)";
	//echo $sql;
	//die();
	return db_query($sql, "could not get all Serial Items");
}


//Modified by spyrax10
function get_serialitems_detail($selected_id)
{
	$sql = "SELECT *, SMO.trans_id 
	FROM ".TB_PREF."item_serialise serial 
	LEFT JOIN ".TB_PREF."stock_moves SMO on serial.serialise_grn_id = SMO.trans_no AND serial.serialise_trans_type = SMO.type
		AND serial.serialise_lot_no = SMO.lot_no
	INNER JOIN item_codes icode ON serial.serialise_item_code=icode.item_code 
	WHERE serial.serialise_id=".db_escape($selected_id);

	$result = db_query($sql, "could not get serial Items detail");

	return db_fetch($result);
}

function delete_serialitem_detail($selected_id)
{
    $sql = "SELECT * FROM ".TB_PREF."item_serialise WHERE serialise_id=".db_escape($selected_id);
    $result = db_query($sql, "could not get serial Items detail");
    $myrow = db_fetch($result);
    $lot_no = $myrow["serialise_lot_no"];
    $chasis_no = $myrow["serialise_chasis_no"];
    
    $sql="DELETE FROM ".TB_PREF."stock_moves WHERE lot_no=".db_escape($lot_no);
    if(!is_null($chasis_no) || !empty($chasis_no)){
        $sql.=" AND chassis_no=".db_escape($chasis_no);
    }
    db_query($sql, "could not delete Stock Moves");
    
	$sql="DELETE FROM ".TB_PREF."item_serialise WHERE serialise_id=".db_escape($selected_id);

	db_query($sql, "could not delete requisition detail");
}

function search_serialitem($serialitem,$chasis=null){
	$sql="SELECT COUNT(serialise_lot_no) FROM ".TB_PREF."item_serialise WHERE serialise_lot_no=".db_escape($serialitem);
	if($chasis!=null || $chasis!='' || !empty($chasis)){
	    $sql.=" AND serialise_chasis_no=".db_escape($chasis);
	}
	$result = db_query($sql, "could not query Serialise Items");
	$myrow = db_fetch($result);
	return $myrow[0];
}
function search_serialno($engine){
    $sql="SELECT COUNT(serialise_lot_no) FROM ".TB_PREF."item_serialise WHERE serialise_lot_no=".db_escape($engine);
    $result = db_query($sql, "could not query engine no");
    $myrow = db_fetch($result);
    return $myrow[0];
}
function search_chasisno($chasis){
    $sql="SELECT COUNT(serialise_chasis_no) FROM ".TB_PREF."item_serialise WHERE serialise_chasis_no=".db_escape($chasis);
    $result = db_query($sql, "could not query chasis no");
    $myrow = db_fetch($result);
    return $myrow[0];
}
//Added by Herald 10/13/2021 for new QOH per serial
function get_qoh_on_date_per_serial($serial=null, $chasis=null, $type=0, $transno=0, $stock_id, $location = null, $date_ = null)
{
    if ($date_ == null)
        $date_ = Today();
        
        $date = date2sql($date_);
        $sql = "SELECT SUM(qty)
     	FROM " . TB_PREF . "stock_moves st
   		LEFT JOIN " . TB_PREF . "voided v ON st.type=v.type AND st.trans_no=v.id
          WHERE ISNULL(v.id)
          AND stock_id=" . db_escape($stock_id) . "
          AND type_out=" . db_escape($type) . "
          AND transno_out=" . db_escape($transno) . "
          AND tran_date <= '$date'";
        if($serial!=null || $serial!='' || !empty($serial)){
            $sql.=" AND lot_no=".db_escape($serial); 
        }
        if($chasis!=null || $chasis!='' || !empty($chasis)){
            $sql.=" AND chassis_no=".db_escape($chasis);
        }
        if ($location != null)
            $sql .= " AND loc_code = " . db_escape($location);
            $sql .= " GROUP BY type_out, transno_out";
            
            $result = db_query($sql, "QOH calculation failed");
            
            $myrow = db_fetch_row($result);
            
            $qoh =  $myrow[0];
            return $qoh ? $qoh : 0;
}