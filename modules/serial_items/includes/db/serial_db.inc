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


function get_all_serialitems($start,$end,$supplier_id=0,$all=false)
{
	
	$sql = "SELECT *,grnheader.reference as reference FROM ".TB_PREF."grn_batch grnheader INNER JOIN ".TB_PREF."purch_orders poorder ON grnheader.purch_order_no=poorder.order_no INNER JOIN ".TB_PREF."suppliers suppl ON grnheader.supplier_id=suppl.supplier_id INNER JOIN ".TB_PREF."locations loc ON grnheader.loc_code=loc.loc_code";
    if($supplier_id){
		$sql.=" WHERE suppl.supplier_id=".db_escape($supplier_id);
	}
	$sql.=" order by poorder.ord_date desc LIMIT $start,$end";

	return db_query($sql, "could not get all GRN");
}
function get_all_serialitems_count($supplier_id=0)
{
	/*if($supplier_id){
		$sql.=" WHERE suppl.supplier_id=".db_escape($supplier_id);
	}*/
	$sql = "SELECT COUNT(*) FROM ".TB_PREF."grn_batch grnheader INNER JOIN ".TB_PREF."purch_orders poorder ON grnheader.purch_order_no=poorder.order_no INNER JOIN ".TB_PREF."suppliers suppl ON grnheader.supplier_id=suppl.supplier_id INNER JOIN ".TB_PREF."locations loc ON grnheader.loc_code=loc.loc_code";
	if($supplier_id){
		$sql.=" WHERE suppl.supplier_id=".db_escape($supplier_id);
	}
	$result = db_query($sql, "could not get all GRN");
	$myrow = db_fetch($result);
	return $myrow[0];
}
function get_one_serialitems($selected_id)
{
	$sql = "SELECT *,grn_batch.id as grnbatchid, grn_items.id as grnitemsid, grn_batch.loc_code,icode.description as color_description, grn_items.item_code as item_code, grn_items.color_code as color_code, podetails.description, grn_batch.delivery_date FROM ".TB_PREF."grn_batch LEFT JOIN ".TB_PREF."grn_items ON ".TB_PREF."grn_batch.id=".TB_PREF."grn_items.grn_batch_id INNER JOIN ".TB_PREF."purch_order_details podetails ON ".TB_PREF."grn_items.po_detail_item=podetails.po_detail_item LEFT JOIN ".TB_PREF."item_codes icode ON ".TB_PREF."grn_items.color_code=icode.item_code WHERE grn_batch.id=".db_escape($selected_id);	
	return db_query($sql, "could not get GRN");
}
function get_category_id($selected_id, $consignment = false){
	$sql="SELECT category_id FROM ".TB_PREF."grn_batch WHERE grn_batch.id=".db_escape($selected_id);
	if($consignment)
	{
		$sql="SELECT category_id FROM ".TB_PREF."consignment WHERE consign_no=".db_escape($selected_id);
	}
    
    $result = db_query($sql, "could not query GRN Batch");
    $myrow = db_fetch($result);
    return $myrow[0];
    
}
function get_total_grn_qty($selected_id,$grn_items=0){
	if($grn_items>0){
		$grn_item_stmt = " AND grn_items.id=".db_escape($grn_items);
	}else{
		$grn_item_stmt="";
	}
	$sql="SELECT SUM(grn_items.qty_recd) FROM ".TB_PREF."grn_batch LEFT JOIN ".TB_PREF."grn_items ON grn_batch.id=grn_items.grn_batch_id INNER JOIN ".TB_PREF."purch_order_details podetails ON grn_items.po_detail_item=podetails.po_detail_item WHERE grn_batch.id=".db_escape($selected_id).$grn_item_stmt;

	$result = db_query($sql, "could not query Serialise Items");
	$myrow = db_fetch($result);
	return $myrow[0];
}
function get_total_serialised_qty($grn_id){
	$sql="SELECT SUM(serialise_qty) FROM ".TB_PREF."item_serialise WHERE serialise_grn_id=".db_escape($grn_id)." AND (serialise_trans_type=25 OR serialise_trans_type=0)";

	$result = db_query($sql, "could not query Serialise Items");
	$myrow = db_fetch($result);
	return $myrow[0];
}

/* function complete_requisition($selected_id)
{
	$sql = "UPDATE ".TB_PREF."requisitions SET completed =  1 WHERE requisition_id=".db_escape($selected_id);

	db_query($sql, "could not complete Requisition");
} */



function count_serialise_item($grn_id, $grn_item_id){
	$sql="SELECT SUM(serialise_qty) FROM ".TB_PREF."item_serialise WHERE serialise_grn_id=".db_escape($grn_id)." AND serialise_grn_items_id=".db_escape($grn_item_id)." AND (serialise_trans_type=25 OR serialise_trans_type=51)";

	$result = db_query($sql, "could not query Serialise Items");
	$myrow = db_fetch($result);
	return $myrow[0];
}

function count_serialise_id($selected_id){
	$sql="SELECT SUM(serialise_qty) FROM ".TB_PREF."item_serialise WHERE serialise_id=".db_escape($selected_id)." AND (serialise_trans_type=25 OR serialise_trans_type=0)";

	$result = db_query($sql, "could not query Serialise Items");
	$myrow = db_fetch($result);
	return $myrow[0];
}

function get_serialise_id($itemcode, $serial=''){
    $sql="SELECT serialise_id FROM ".TB_PREF."item_serialise WHERE serialise_item_code=".db_escape($itemcode)." AND (serialise_lot_no=".db_escape($serial).")";
    
    $result = db_query($sql, "could not query Serialise Items");
    $myrow = db_fetch($result);
    return $myrow[0];
}
//Added by Herald 03/18/2021 - for non serialised Item
function get_nonserialise_id($itemcode){
    $sql="SELECT serialise_id FROM ".TB_PREF."item_serialise WHERE serialise_item_code=".db_escape($itemcode);
    
    $result = db_query($sql, "could not query Non Serialise Items");
    $myrow = db_fetch($result);
    return $myrow[0];
}

function get_all_serial($start,$end,$querystr='',$category=0,$branchcode='',$all=false){
    $sql = "SELECT serial.*,icode.stock_id as model, smaster.description as stock_description, icode.description as item_description FROM ".TB_PREF."item_serialise serial INNER JOIN ".TB_PREF."item_codes icode ON serial.serialise_item_code=icode.item_code INNER JOIN ".TB_PREF."stock_master smaster ON icode.stock_id=smaster.stock_id WHERE (serial.serialise_trans_type=25 || serial.serialise_trans_type=0)";
    if($category!=0){
        $sql.=" AND icode.category_id=$category";
    }
    if($branchcode!=''){
        $sql.=" AND serial.serialise_loc_code='".$branchcode."'";
        
    }
    if($querystr!=''){
        $sql.=" AND (serial.serialise_item_code LIKE '%".$querystr."%' OR smaster.description LIKE '%".$querystr."%' OR icode.description LIKE '%".$querystr."%')";
    }
    if($all){
        
    }else{
        $sql.=" LIMIT $start,$end";
    }
    //echo $sql;
    //die();
    return db_query($sql, "could not get all Serial Items");
    
}

function get_all_stockmoves($start,$limit,$querystr='',$category=0,$branchcode='',$all=false,$reference='', $trans_date=null,$serialquery=null){


    $sql = "SELECT SUM(serial.qty) as serialise_qty, serial.stock_id as model, serial.color_code as serialise_item_code, 
    serial.lot_no as serialise_lot_no, serial.chassis_no as serialise_chasis_no, serial.loc_code as serialise_loc_code, 
    smaster.description as stock_description, icode.description as item_description, smaster.serialised, smaster.inventory_account, 
    serial.type_out, serial.transno_out,serial.tran_date, serial.reference, serial.standard_cost, serial.item_type,
    categstock.dflt_inventory_act, IFNULL(QTYOUT.QTY, NULL) AS qty_serialise, /*Added by Robert */
    IFNULL(SPGEN.QTY, NULL) /*Added by Robert */
    FROM ".TB_PREF."stock_moves serial 
    LEFT JOIN ".TB_PREF."voided v ON serial.type=v.type AND serial.trans_no=v.id 
    INNER JOIN ".TB_PREF."item_codes icode ON serial.color_code=icode.item_code
    INNER JOIN ".TB_PREF."stock_master smaster ON icode.stock_id=smaster.stock_id
    INNER JOIN ".TB_PREF."stock_category categstock ON serial.category_id=categstock.category_id  

    LEFT JOIN (
    SELECT robert.type_out, robert.transno_out, robert.stock_id, robert.lot_no, SUM(robert.qty) AS QTY
    FROM stock_moves robert
    GROUP BY robert.type_out, robert.transno_out, robert.stock_id, robert.lot_no
    )QTYOUT ON QTYOUT.type_out = serial.type_out AND QTYOUT.transno_out =  QTYOUT.transno_out 
     AND QTYOUT.stock_id = serial.stock_id AND QTYOUT.lot_no = serial.lot_no

    LEFT JOIN ( 
    SELECT jayhil.type_out, jayhil.transno_out, jayhil.stock_id, jayhil.lot_no, SUM(jayhil.qty) AS QTY 
    FROM stock_moves jayhil GROUP BY jayhil.type_out, jayhil.transno_out, jayhil.stock_id
    )SPGEN ON SPGEN.type_out = serial.type_out AND SPGEN.transno_out = SPGEN.transno_out AND SPGEN.stock_id = serial.stock_id 
 
    WHERE ISNULL(v.id) AND serial.item_type = 'new' AND IFNULL(QTYOUT.QTY, 1) > 0 AND IFNULL(SPGEN.QTY, 1) > 0"; /*Added by Robert */

    if($category!=0){
        $sql.=" AND smaster.category_id=$category";
    }
    if($reference!=''){
        $sql.=" AND serial.reference='".$reference."'";
        
    }
    if($branchcode!=''){
        $sql.=" AND serial.loc_code='".$branchcode."'";
        
    }
    if($trans_date!=null){
        //$trans_date = date2sql($trans_date);
        $sql.=" AND serial.tran_date<=".db_escape($trans_date);
        
    }
    if($serialquery!=null){
        $sql.=" AND (serial.lot_no LIKE '%".$serialquery."%')";
    }
    if($querystr!=''){
        $sql.=" AND (serial.color_code LIKE '%".$querystr."%' OR smaster.description LIKE '%".$querystr."%' OR icode.description LIKE '%".$querystr."%')";
    }
    $sql.=" GROUP BY serial.type_out, serial.transno_out, serial.stock_id, serial.lot_no ORDER BY serial.stock_id,serial.tran_date, serial.standard_cost";
    
    /*if($all){
        
    }else{
        $sql.=" limit $start,$limit";
    }
    echo $sql;
    //die();
    return db_query($sql, "could not get all Serial Items");*/

    /*Added by Robert */
    $sqllimit = $sql ." limit $start, $limit";

    if ($all) {
        $result = db_query($sql, "could not get all Serial Items");
    }else{
        $result = db_query($sqllimit, "could not get all Serial Items");
    }
    //echo $sql;

    return $result;
}

//Added by Robert 11/07/2022//
function get_all_stockmoves_compli($start,$limit,$querystr='',$category=0,$branchcode='',$all=false,$reference='', $trans_date=null,$sales_return='',
    $filter_type='',$serialquery=null){


    $sql = "SELECT SUM(serial.qty) as serialise_qty, serial.stock_id as model, serial.color_code as serialise_item_code, 
    serial.lot_no as serialise_lot_no, serial.chassis_no as serialise_chasis_no, serial.loc_code as serialise_loc_code, 
    smaster.description as stock_description, icode.description as item_description, smaster.serialised, smaster.inventory_account, 
    serial.type_out, serial.transno_out,serial.tran_date, serial.reference, serial.standard_cost, serial.item_type,
    categstock.dflt_inventory_act, IFNULL(QTYOUT.QTY, NULL) AS qty_serialise, /*Added by Robert */
    IFNULL(SPGEN.QTY, NULL) /*Added by Robert */
    FROM ".TB_PREF."stock_moves serial 
    LEFT JOIN ".TB_PREF."voided v ON serial.type=v.type AND serial.trans_no=v.id 
    INNER JOIN ".TB_PREF."item_codes icode ON serial.color_code=icode.item_code
    INNER JOIN ".TB_PREF."stock_master smaster ON icode.stock_id=smaster.stock_id
    INNER JOIN ".TB_PREF."stock_category categstock ON serial.category_id=categstock.category_id  

    LEFT JOIN (
    SELECT robert.type_out, robert.transno_out, robert.stock_id, robert.lot_no, SUM(robert.qty) AS QTY
    FROM stock_moves robert
    GROUP BY robert.type_out, robert.transno_out, robert.stock_id, robert.lot_no
    )QTYOUT ON QTYOUT.type_out = serial.type_out AND QTYOUT.transno_out =  QTYOUT.transno_out 
     AND QTYOUT.stock_id = serial.stock_id AND QTYOUT.lot_no = serial.lot_no

    LEFT JOIN ( 
    SELECT jayhil.type_out, jayhil.transno_out, jayhil.stock_id, jayhil.lot_no, SUM(jayhil.qty) AS QTY 
    FROM stock_moves jayhil GROUP BY jayhil.type_out, jayhil.transno_out, jayhil.stock_id
    )SPGEN ON SPGEN.type_out = serial.type_out AND SPGEN.transno_out = SPGEN.transno_out AND SPGEN.stock_id = serial.stock_id 
 
    WHERE ISNULL(v.id) AND serial.item_type = 'new' AND IFNULL(QTYOUT.QTY, 1) > 0 AND IFNULL(SPGEN.QTY, 1) > 0"; /*Added by Robert */

    if($category!=0){
        $sql.=" AND smaster.category_id=$category";
    }
    if($reference!=''){
        $sql.=" AND serial.reference='".$reference."'";
        
    }
    if($branchcode!=''){
        $sql.=" AND serial.loc_code='".$branchcode."'";
        
    }
    if($trans_date!=null){
        //$trans_date = date2sql($trans_date);
        $sql.=" AND serial.tran_date<=".db_escape($trans_date);
        
    }
    if($sales_return!=''){
        $sql.=" AND serial.trans_no='".$sales_return."'";
        
    }
    if($filter_type!=''){
        $sql.=" AND serial.type='".$filter_type."'";
        
    }
    if($serialquery!=null){
        $sql.=" AND (serial.lot_no LIKE '%".$serialquery."%')";
    }
    if($querystr!=''){
        $sql.=" AND (serial.color_code LIKE '%".$querystr."%' OR smaster.description LIKE '%".$querystr."%' OR icode.description LIKE '%".$querystr."%')";
    }
    $sql.=" GROUP BY serial.type_out, serial.transno_out, serial.stock_id, serial.lot_no ORDER BY serial.stock_id,serial.tran_date, serial.standard_cost";
    
    /*if($all){
        
    }else{
        $sql.=" limit $start,$limit";
    }
    echo $sql;
    //die();
    return db_query($sql, "could not get all Serial Items");*/

    /*Added by Robert */
    $sqllimit = $sql ." limit $start, $limit";

    if ($all) {
        $result = db_query($sql, "could not get all Serial Items");
    }else{
        $result = db_query($sqllimit, "could not get all Serial Items");
    }
    //echo $sql;

    return $result;
}

/*Added by robert 04/19/2022*/
function get_all_merchandise_transfer($start,$limit,$querystr='',$branchcode='',$search_ref='',$all=false){
    //global $def_coy;
    //set_global_connection($def_coy);

    $sql = "SELECT *, sc.description as category, sum(md.mt_details_total_qty) as totalqty,
    sum(md.mt_details_recvd_qty) as totalreceived, sum(md.mt_details_total_qty)-sum(md.mt_details_recvd_qty) as balance_total 
    FROM ".TB_PREF."mt_header mh 
    LEFT JOIN ".TB_PREF."mt_details md ON mh.mt_header_id=md.mt_details_header_id 
    LEFT JOIN ".TB_PREF."stock_category sc ON mh.mt_header_category_id=sc.category_id 
    WHERE mh.mt_header_fromlocation='$branchcode' AND mh.mt_header_item_type='new'
    AND mh.mt_header_reference LIKE '%".$search_ref."%' 
    GROUP BY mh.mt_header_id ORDER BY mh.mt_header_id DESC";

    /*Added by Robert */
    $sqllimit = $sql ." limit $start, $limit";

    if ($all) {
        $result = db_query($sql, "could not get all merchandise transfer");
    }else{
        $result = db_query($sqllimit, "could not get all merchandise transfer");
    }
    //echo $sql;

    return $result;
}

/*Added by robert 04/19/2022*/
function get_all_complimentary_item($start,$limit,$querystr='',$comp_stat='',$search_ref='', $all=false)
{

    if($comp_stat!=''){
        $str_status=" AND compl.status='".$comp_stat."'";
    }else{
        $str_status="";
    }

    $sql = "SELECT smoves.*,scat.description AS category_name, SUM(smoves.qty) as total_item, 
    loc.location_name AS loc_name, compl.memo_ AS remarks, compl.status AS compli_status
    FROM ".TB_PREF."stock_adjustment smoves 
    INNER JOIN ".TB_PREF."stock_category scat ON smoves.category_id=scat.category_id 
    INNER JOIN ".TB_PREF."locations loc ON smoves.loc_code=loc.loc_code 
    LEFT JOIN ".TB_PREF."complimentary_items compl ON smoves.reference=compl.reference 
    WHERE smoves.type='".ST_COMPLIMENTARYITEM."' AND smoves.item_type = 'new' $str_status
    AND compl.reference LIKE '%".$search_ref."%'
    GROUP BY smoves.trans_no ORDER BY smoves.tran_date DESC, smoves.trans_id DESC";

    /*Added by Robert */
    $sqllimit = $sql ." limit $start, $limit";

    if ($all) {
        $result = db_query($sql, "could not get all complimentary item");
    }else{
        $result = db_query($sqllimit, "could not get all complimentary item");
    }
    //echo $sql;

    return $result;
}

/*Added by robert 04/28/2022*/
function get_all_receiving_item_branch($start,$limit,$branchcode='',$fromlocation='',$catcode='',$search_ref='',$all=false){

    if($fromlocation!=''){
        $str_fromlocation=" AND mh.mt_header_fromlocation='".$fromlocation."'";
    }else{
        $str_fromlocation="";
    }

    if($catcode != 0){
        $str_category=" AND mh.mt_header_category_id='".$catcode."'";
    }else{
        $str_category="";
    }

    $sql = "SELECT *, sc.description as category, sum(md.mt_details_total_qty) as totalqty, 
    sum(md.mt_details_recvd_qty) as totalreceived, sum(md.mt_details_total_qty)-sum(md.mt_details_recvd_qty) as balance_qty 
    FROM ".TB_PREF."mt_header mh 
    LEFT JOIN ".TB_PREF."mt_details md ON mh.mt_header_id=md.mt_details_header_id 
    LEFT JOIN ".TB_PREF."stock_category sc ON mh.mt_header_category_id=sc.category_id 
    WHERE mh.mt_header_tolocation='$branchcode' $str_fromlocation $str_category AND mh.mt_header_item_type = 'new'
    AND (mh.mt_header_reference LIKE '%".$search_ref."%' OR mh.mt_header_rrbranch_reference LIKE '%".$search_ref."%')
    GROUP BY mh.mt_header_reference ORDER BY mh.mt_header_date DESC, mh.mt_header_id DESC";

    /*Added by Robert */
    $sqllimit = $sql ." limit $start, $limit";

    if ($all) {
        $result = db_query($sql, "could not get all receiving item");
    }else{
        $result = db_query($sqllimit, "could not get all receiving item");
    }
    //echo $sql;

    return $result;
}

/*Added by robert 10/08/2022*/
function get_transaction_from_stock_adjusment_check($type, $trans_no, $reference){

    $sql = "SELECT * FROM ".TB_PREF."stock_adjustment 
    WHERE type='".$type."' AND trans_no='".$trans_no."' AND reference='".$reference."'";

    $result2 = db_query($sql, "could not get all complimentary item");

    return $result2;
}

/*Added by robert 10/20/2022*/
function get_transaction_from_stock_adjusment_transfer_check($type, $trans_no, $reference){

    $sql = "SELECT * FROM ".TB_PREF."stock_adjustment 
    WHERE type='".$type."' AND trans_no='".$trans_no."' AND reference='".$reference."'
    AND (qty<0)";

    $result2 = db_query($sql, "could not get all complimentary item");

    return $result2;
}

/*Added by robert 10/06/2022*/
function get_transaction_from_stock_adjusment($type, $trans_no, $reference){

    $sql = "SELECT * FROM ".TB_PREF."stock_adjustment 
    WHERE type='".$type."' AND trans_no='".$trans_no."' AND reference='".$reference."'";

    $result = db_query($sql, "could not get all complimentary item");

    return $result;
}

/*Added by robert 10/06/2022*/
function get_transaction_from_stock_adjusment_gl($type, $trans_no, $reference){

    $sql = "SELECT gl.*, stock.tran_date FROM ".TB_PREF."stock_adjustment_gl gl
    INNER JOIN stock_adjustment stock ON stock.type = gl.sa_adj_type AND stock.trans_no = gl.sa_trans_no
    WHERE sa_adj_type='".$type."' AND sa_trans_no='".$trans_no."' AND sa_reference='".$reference."'
    GROUP BY gl.id";

    $result1 = db_query($sql, "could not get all complimentary item");

    return $result1;
}

/*Added by robert 10/19/2022*/
function get_all_location_transfer_item($start,$limit,$querystr='',$loc_stat='',$search_ref='',$all=false)
{
    if($loc_stat!=''){
        $str_status=" AND smoves.status='".$loc_stat."'";
    }else{
        $str_status="";
    }

    $sql = "SELECT smoves.*,scat.description AS category_name, SUM(smoves.qty) as total_item, 
    loc.location_name AS loc_name,
    (SELECT adjloc.location_name FROM ".TB_PREF."stock_adjustment adj 
    LEFT JOIN ".TB_PREF."locations adjloc ON adj.loc_code = adjloc.loc_code
    WHERE adj.type='".ST_LOCTRANSFER."' AND adj.reference=smoves.reference 
    AND (adj.qty<0) GROUP BY adj.reference) AS from_name
    FROM ".TB_PREF."stock_adjustment smoves 
    INNER JOIN ".TB_PREF."stock_category scat ON smoves.category_id=scat.category_id 
    INNER JOIN ".TB_PREF."locations loc ON smoves.loc_code=loc.loc_code 
    WHERE smoves.type='".ST_LOCTRANSFER."' AND smoves.item_type = 'new' AND (smoves.qty>0) $str_status
    AND smoves.reference LIKE '%".$search_ref."%'
    GROUP BY smoves.trans_no ORDER BY smoves.tran_date DESC, smoves.trans_id DESC";

    /*Added by Robert */
    $sqllimit = $sql ." limit $start, $limit";

    if ($all) {
        $result = db_query($sql, "could not get all location transfer item");
    }else{
        $result = db_query($sqllimit, "could not get all location transfer item");
    }
    //echo $sql;

    return $result;
}

//added by Herald 09/22/2021 for Manual Entry MT
function get_all_items_listing($start,$end,$brand=0,$querystr='',$category=0,$all=false){
    $sql = "SELECT smaster.stock_id as model, icode.item_code as item_code, smaster.description as stock_description, icode.description as item_description, smaster.serialised, icode.color as color, icode.category_id, item_brand.name as brand_name, smaster.brand as brand_id FROM ".TB_PREF."item_codes icode INNER JOIN ".TB_PREF."stock_master smaster ON icode.stock_id=smaster.stock_id 
LEFT JOIN item_brand ON smaster.brand=item_brand.id
WHERE smaster.inactive=0";
    
    if($category!=0){
        if($category==14){
            $sql.=" AND icode.category_id=$category AND icode.is_foreign=1";
        }else{
            $sql.=" AND icode.category_id=$category AND icode.is_foreign=0";
        }
    }
    if($brand!=0){
        $sql.=" AND smaster.brand=$brand";
    }
    
    if($querystr!=''){
        $sql.=" AND (icode.item_code LIKE '%".$querystr."%' OR smaster.description LIKE '%".$querystr."%' OR icode.description LIKE '%".$querystr."%')";
    }
    $sql.=" ORDER BY item_brand.name, smaster.stock_id,icode.item_code";
    
    if($all){
        
    }else{
        $sql.=" LIMIT $start,$end";
    }
    //echo $sql;
    //die();
    return db_query($sql, "could not get all Items");
}
/*  Added by Ronelle 12/16/2020 */
function get_consign_serial($consign_no)
{
	$sql = "SELECT
				b.consign_detail_item,
				a.consign_no,
				a.reference,
				a.supplier_id,
				b.item_code,
				b.description,
				b.color_code,
				b.qty
			FROM ".TB_PREF."consignment a INNER JOIN
			".TB_PREF."consignment_details b on b.consign_no = a.consign_no INNER JOIN
			".TB_PREF."stock_master c on c.stock_id = b.item_code AND c.serialised = 1
			WHERE a.consign_no =" .db_escape($consign_no). "
			GROUP BY b.consign_detail_item";
	return db_query($sql, "could not get Consignment");
}

function get_serial_by_consign_no($consign_no, $items_id)
{
	$sql = "SELECT
				a.*,
				b.item_code
			FROM ".TB_PREF."item_serialise a INNER JOIN
			".TB_PREF."consignment_details b on b.consign_detail_item = a.serialise_grn_items_id INNER JOIN
			".TB_PREF."consignment c on c.consign_no = b.consign_no
			WHERE a.serialise_grn_items_id = ".db_escape($items_id). " AND a.serialise_trans_type = 51 AND c.consign_no = ".db_escape($consign_no)."";
	return db_query($sql, "could not get Consignment Serial");
}

//--Added by robert--09/05/2022--//
function get_category_receiving_branch()
{   
    $sql = "SELECT category_id, description
            FROM ".TB_PREF."stock_category 
            WHERE dflt_mb_flag <> 'D'";

    $result = db_query($sql, "The promotional price from other database could not be retrieved");
    
    set_global_connection();        
    return $result;
}
/* */
