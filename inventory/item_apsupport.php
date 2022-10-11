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
$page_security = 'SA_ITMAPSUPPORT';
$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/inventory/includes/db/items_apsupport_db.inc");

add_access_extensions();

//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
if($_GET['mngtype']=='type'){
    add_js_ufile($path_to_root ."/js/apsupport_type.js");
}elseif($_GET['mngtype']=='price'){
    add_js_ufile($path_to_root ."/js/apsupport_price.js");
}
//----------------------------------------------: for store js :---------------------------------------

if(isset($_GET['getcategory'])){
    $result = get_itemcategory($_GET['getcategory']);
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow['category_id'],
                    'name'=>$myrow['description']);
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['getItem'])){
    if(isset($_GET['stock_id'])){
        $result = get_stockitem_discount($_GET['stock_id']);
    }else{
        $result = get_stockitem($_GET['category'], $_GET['brand']);
    }
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow['stock_id'],
                    'name'=>$myrow['description']);
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_brand'])){
    //$result = get_item_brand($_GET['category']);
    $result = get_supplierAP();
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow['supplier_id'],
                    'name'=>$myrow['supp_name']);
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_itemapsupptype'])){
    $result = get_itemapsupp_type(0,0,'', true);
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow['id'],
                    'name'=>$myrow['ap_support_type']);
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}

if(isset($_GET['get_itemapsupp_type']))
{
    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
    
    $result = get_itemapsupp_type($start, $limit, $_GET['query']);
    $total_result = get_itemapsupp_type($start, $limit, $_GET['query'], true);

    $total = DB_num_rows($total_result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow["id"],
                               'apsupp_type'=>$myrow["ap_support_type"],
                               'distribution'=>$myrow["distribution"],
                               'status'=>$myrow["inactive"] == 0 ? 'Yes' : 'No'
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}

if(isset($_GET['get_itemapsupp_price']))
{
    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
    
    $result = get_itemapsupp_price($_GET['category'], $start, $limit, $_GET['query']);
    $total_result = get_itemapsupp_price($_GET['category'], $start, $limit, $_GET['query'], true);

    $total = DB_num_rows($total_result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow["id"],
                               'stock_id'=>$myrow["supplier_id"],
                               'stock_name'=>$myrow["supp_name"],
                               'apsupp_type_id'=>$myrow["apsupport_type_id"],
                               'apsupp_type_name'=>$myrow["ap_support_type"],
                               'category_id'=>$myrow["category_id"],
                               'distribution'=>$myrow["distribution"],
                               'brand_id'=>$myrow["brand_id"],
                               'brand_name'=>$myrow["brand_name"],
                               'price'=>$myrow["price"]
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}

//----------------------------------------------------: insert, update, delete :-------------------------------------------
if(isset($_GET['submitype'])){
    //0 is by default no errors
    $InputError = 0;

	if (!empty($_GET['syspk']) || !empty($_POST['syspk'])) {
		if (isset($_GET['syspk'])){
			$syspk = $_GET['syspk'];
		} else if (isset($_POST['syspk'])){
			$syspk = $_POST['syspk'];
		}
    }

    if (empty($_POST['apsupp_type'])) {
        $InputError = 1;
        $dsplymsg = _('AP support type must not be empty.');
    }

    //check stock id if exist
    if (empty($_GET['syspk']) && empty($_POST['syspk'])) {
        if(check_apsupp_type($_POST['apsupp_type'])){
            $InputError = 1;
            $dsplymsg = _("AP support type already exists.");
        }
    }

    if (isset($syspk) AND $InputError !=1) {
        //update info
        update_item_apsupport_type($syspk, $_POST['apsupp_type'], $_POST['distribution'], $_POST['inactive']);
        
        $dsplymsg = _('Item AP support type has been updated');

        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;

    }elseif ($InputError != 1) {
        //add info
        add_item_apsupport_type($_POST['apsupp_type'], $_POST['distribution']);

        $dsplymsg = _('Item AP support type has been added.');

        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;

    }else {
        echo '({"failure":"true","message":"'.$dsplymsg.'"})';
        return;
    }
}elseif(isset($_GET['deletetype'])){
	if (!empty($_GET['syspk']) || !empty($_POST['syspk'])) {

		if(isset($_GET['syspk'])){
			$syspk = $_GET['syspk'];
		}elseif (isset($_POST['syspk'])){
			$syspk = $_POST['syspk'];
        }

        delete_item_apsupport_type($syspk);

        $dsplymsg = ('Item AP support type has been deleted');

        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;
    }
}elseif(isset($_GET['submitprice'])){
    //0 is by default no errors
    $InputError = 0;

	if (!empty($_GET['syspk']) || !empty($_POST['syspk'])) {
		if (isset($_GET['syspk'])){
			$syspk = $_GET['syspk'];
		} else if (isset($_POST['syspk'])){
			$syspk = $_POST['syspk'];
		}
    }

    if (empty($_POST['category'])) {
        $InputError = 1;
        $dsplymsg = _('Category must not be empty.');
    }
    if (empty($_POST['brand'])) {
        $InputError = 1;
        $dsplymsg = _('Item must not be empty.');
    }
    if (empty($_POST['apsupp_type'])) {
        $InputError = 1;
        $dsplymsg = _('AP support type must not be empty.');
    }
    if (empty($_POST['amount'])) {
        $InputError = 1;
        $dsplymsg = _('Amount must not be empty.');
    }

    //check stock id if exist
    if (empty($_GET['syspk']) && empty($_POST['syspk'])) {
        if(check_apsupp_price($_POST['brand'], $_POST['apsupp_type'], $_POST['category'])){
            $InputError = 1;
            $dsplymsg = _("AP support price already exists.");
        }
    }

    if (isset($syspk) AND $InputError !=1) {
        //update info
        update_item_apsupport_price($syspk, $_POST['brand'], $_POST['apsupp_type'], $_POST['amount'], $_POST['category']);
        
        $dsplymsg = _('Item AP support price has been updated');

        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;

    }elseif ($InputError != 1) {
        //add info
        add_item_apsupport_price($_POST['category'], $_POST['brand'], $_POST['apsupp_type'], $_POST['amount']);

        $dsplymsg = _('Item AP support price has been added.');

        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;

    }else {
        echo '({"failure":"true","message":"'.$dsplymsg.'"})';
        return;
    }

}elseif(isset($_GET['deleteprice'])){
	if (!empty($_GET['syspk']) || !empty($_POST['syspk'])) {

		if(isset($_GET['syspk'])){
			$syspk = $_GET['syspk'];
		}elseif (isset($_POST['syspk'])){
			$syspk = $_POST['syspk'];
        }

        delete_item_apsupport_price($syspk);

        $dsplymsg = ('Item AP support price has been deleted');

        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;
    }

}

if($_GET['mngtype']=='type'){
    page(_($help_context = "Item AP Support Type"));
}elseif($_GET['mngtype']=='price'){
    page(_($help_context = "Item AP Support Price"));
}
start_table(TABLESTYLE, "width='100%'");
   echo "<div id='ext-form'></div>";
   echo "<style type='text/css' media='screen'>
            .x-form-text-default.x-form-textarea {
                line-height: 19px;
                min-height: 25px;
            }
        </style>";
end_table();

end_form();
end_page();

