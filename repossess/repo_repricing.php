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
$page_security = 'SA_REPOREPRICE';
$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/repossess/includes/repossessed.inc");

add_access_extensions();

//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root ."/js/repo_repricing.js");

if(isset($_GET['Getdefloc'])){
    $defaultLoc = get_default_location();
    echo '({"success":"true","deflocation":"'.$defaultLoc.'"})';
    return;
}
//----------------------------------------------: for store js :---------------------------------------
if(isset($_GET['getbranch'])){
    global $db_connections;
    $conn = $db_connections;
    $total = count($conn);

    $loc_code = get_default_location();
    //echo "sad".$loc_code;
    if($loc_code != 'HO' AND $loc_code != 'DEF'){
        for ($i = 0; $i < $total; $i++)
        {
            if($loc_code == $conn[$i]['branch_code']){
                $status_array[] = array('id'=>$conn[$i]['branch_code'],
                                        'name'=>$conn[$i]['name'],
                                        'area'=>$conn[$i]['branch_area']);
            }
        }
    }else{
        for ($i = 0; $i < $total; $i++)
        {
            $status_array[] = array('id'=>$conn[$i]['branch_code'],
                                    'name'=>$conn[$i]['name'],
                                    'area'=>$conn[$i]['branch_area']);
        }
    }

    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;

}

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

if(isset($_GET['get_repoStock']))
{
    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
    
    $result = get_repo_stocks($_GET['category'], $_GET['branch'], $start, $limit, $_GET['query']);
    $total_result = get_repo_stocks($_GET['category'], $_GET['branch'], $start, $limit, $_GET['query'], true);

    $total = DB_num_rows($total_result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('repo_id'=>$myrow["id"],
                               'trans_id'=>$myrow["trans_id"],
                               'trans_no'=>$myrow["trans_no"],
                               'type'=>$myrow["type"],
                               'stock_id'=>$myrow["stock_id"],
                               'description'=>$myrow["description"],
                               'unrecovered_cost'=>$myrow["unrecovered_cost"],
                               'price'=>$myrow["price"],
                               'repo_date'=>$myrow["repo_date"],
                               'reference_no'=>$myrow["reference_no"],
                               'debtor_no'=>$myrow["debtor_no"],
                               'debtor_name'=>$myrow["name"],
                               'category'=>$myrow["category"],
                               'comments'=>$myrow["comments"],
                               'branch'=>$_GET['branch']
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_Item_details']))
{
    $result = get_repo_item_detials($_GET['repo_id'], $_GET['branch']);
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('stock_id'=>$myrow["stock_id"],
                               'description'=>$myrow["description"],
                               'qty'=>$myrow["qty"],
                               'unit_price'=>$myrow["unit_price"],
                               'serial'=>$myrow["serial_no"],
                               'chasis'=>$myrow["chassis_no"]
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

    if (empty($_POST['stockmove_id']) || empty($_POST['trans_no']) || empty($_POST['type'])){
        $InputError = 1;
        $dsplymsg = _('Some fields are empty or contain an improper value. Please reload the page and fill up the required field.');
    }
    if (empty($_POST['reference_no'])) {
        $InputError = 1;
        $dsplymsg = _('Reference no must not be empty.');
    }
    if (empty($_POST['repo_date'])) {
        $InputError = 1;
        $dsplymsg = _('Date must not be empty.');
    }else{
        $trans_date = date('Y-m-d',strtotime($_POST['repo_date']));
    }
    if (empty($_POST['customer_name'])) {
        $InputError = 1;
        $dsplymsg = _('Invoice number must not be empty.');
    }
    if (empty($_POST['stock_id'])) {
        $InputError = 1;
        $dsplymsg = _('Stock id must not be empty.');
    }
    if (empty($_POST['category'])) {
        $InputError = 1;
        $dsplymsg = _('Category must not be empty.');
    }
    if (empty($_POST['unrecovered'])) {
        $InputError = 1;
        $dsplymsg = _('Unrecovered cost must not be empty.');
    }
    if ($_POST['price'] == 0) {
        $InputError = 1;
        $dsplymsg = _('Price amount must be greater than 0.');
    }
    if (empty($_POST['price'])) {
        $InputError = 1;
        $dsplymsg = _('Price must not be empty.');
    }

    if ($InputError != 1){
        //update info
        update_repo_reprice_stock_move($_POST['price'], $_POST['stockmove_id'], $_POST['trans_no'], $_POST['stock_id'], $_POST['branch']);

        $dsplymsg = _('Repo item price has been updated.');

        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;

    }else {
        echo '({"failure":"true","message":"'.$dsplymsg.'"})';
        return;
    }
}

page(_($help_context = "Repossess Item Pricing"));

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

