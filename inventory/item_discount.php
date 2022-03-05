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
$page_security = 'SA_ITEMDSCNT';
$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/inventory/includes/db/items_discount_db.inc");

add_access_extensions();

//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root ."/js/item_discount.js");

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

if(isset($_GET['get_brand'])){
    $result = get_item_brand($_GET['category']);
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow['id'],
                    'name'=>$myrow['name']);
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

if(isset($_GET['get_itemdiscount']))
{
    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
    
    $result = get_item_discount($_GET['category'], $start, $limit, $_GET['query']);
    $total_result = get_item_discount($_GET['category'], $start, $limit, $_GET['query'], true);

    $total = DB_num_rows($total_result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow["id"],
                               'stock_id'=>$myrow["stock_id"],
                               'item_description'=>$myrow["description"],
                               'discount_type_id'=>$myrow["discount_type_id"],
                               'dpdiscount1'=>$myrow["dp_discount1"],
                               'dpdiscount2'=>$myrow["dp_discount2"],
                               'salediscount1'=>$myrow["sales_discount1"],
                               'salediscount2'=>$myrow["sales_discount2"],
                               'category'=>$myrow["category_id"],
                               'brand'=>$myrow["brand"]
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}

//----------------------------------------------------: insert, update, delete :-------------------------------------------
if(isset($_GET['submit'])){
    //0 is by default no errors
    $InputError = 0;

    if (empty($_POST['item'])) {
        $InputError = 1;
        $dsplymsg = _('Item must not be empty.');
    }
    /*if (empty($_POST['salediscount1'])) {
        $InputError = 1;
        $dsplymsg = _('Sale discount 1 must not be empty.');
    }
    if (empty($_POST['salediscount2'])) {
        $InputError = 1;
        $dsplymsg = _('Sale discount 2 must not be empty.');
    }
    if (empty($_POST['dpdiscount1'])) {
        $InputError = 1;
        $dsplymsg = _('DP discount 1 must not be empty.');
    }
    if (empty($_POST['dpdiscount2'])) {
        $InputError = 1;
        $dsplymsg = _('DP discount 2 must not be empty.');
    }*/
    if (empty($_POST['category'])) {
        $InputError = 1;
        $dsplymsg = _('Category must not be empty.');
    }

	if (!empty($_GET['syspk']) || !empty($_POST['syspk'])) {
		if (isset($_GET['syspk'])){
			$syspk = $_GET['syspk'];
		} else if (isset($_POST['syspk'])){
			$syspk = $_POST['syspk'];
		}
    }else{
        //check stock id if exist
        if(check_stock_id($_POST['item'])){
            $InputError = 1;
            $dsplymsg = _("Item with discount already exists.");
        }
    }
    
    if (isset($syspk) AND $InputError !=1) {
        //update info
        update_item_discount($syspk, $_POST['item'], $_POST['dpdiscount1'], $_POST['dpdiscount2'], $_POST['salediscount1'], $_POST['salediscount2']);
        
        $dsplymsg = _('Item discount has been updated');

        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;

    }elseif ($InputError != 1) {
        //add info
        add_item_discount($_POST['item'], $_POST['dpdiscount1'], $_POST['dpdiscount2'], $_POST['salediscount1'], $_POST['salediscount2']);

        $dsplymsg = _('Item discount has been added.');

        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;

    }else {
        echo '({"failure":"true","message":"'.$dsplymsg.'"})';
        return;
    }
}elseif(isset($_GET['delete'])){
	if (!empty($_GET['syspk']) || !empty($_POST['syspk'])) {

		if(isset($_GET['syspk'])){
			$syspk = $_GET['syspk'];
		}elseif (isset($_POST['syspk'])){
			$syspk = $_POST['syspk'];
        }

        delete_item_discount($syspk);

        $dsplymsg = ('Item discount has been deleted');

        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;
    }
}

page(_($help_context = "Item Discount"));

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

