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
$page_security = 'SA_TEMPREPOINQRY';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/repossess/includes/repossessed.inc");
include_once($path_to_root . "/lending/includes/db/ar_installment_db.inc");

add_access_extensions();

//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root ."/js/temporary_repo_inquiry.js");

//----------------------------------------------: for store js :---------------------------------------
if(isset($_GET['get_Customer']))
{
    $counter=0;
    //$result = get_customer_account();
    $result = get_all_customer();
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('debtor_no'=>$myrow["debtor_no"],
                               'debtor_ref'=>$myrow["debtor_ref"],
                               'name'=>htmlentities($myrow["name"])
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_InvoiceNo']))
{
    if($_GET['isTrmode'] == 'true') {
        $result = get_invtermode_to_repo($_GET['debtor_id'], $_GET['view']);
    }else{
        $result = get_invoice_to_repo($_GET['debtor_id'], $_GET['view']);
    }

    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        
        if($_GET['isreplce'] == 'true'){
            if($counter == 0){
                $replace_result = get_replace_item($myrow['trans_no']);
                $replace_itm = db_fetch($replace_result);
                $counter = 1;
            }

            $status_array[] = array('id'=>$myrow["trans_no"],
                                'name'=>($myrow["reference"].' > '.$myrow["category"].' > '.$replace_itm["stock_id"].' > '.$replace_itm["description"]),
                                'type'=>$myrow["type"],
                                'tran_date'=>$myrow["tran_date"],
                                'status'=>$myrow["status"],
                                'trmd_inv_no'=>$invoice_no,
                                'trmd_inv_type'=>$invoice_type
                                );
        }else{
            if($_GET['isTrmode'] == 'true') {
                $invoice_no = $myrow["invoice_no"];
                $invoice_type = $myrow["debtor_trans_type"];
            }else{
                $invoice_no = $invoice_type = 0;
            }

            $status_array[] = array('id'=>$myrow["trans_no"],
                                'name'=>($myrow["reference"].' > '.$myrow["category"].' > '.$myrow["stock_id"].' > '.$myrow["itemdesc"]),
                                'type'=>$myrow["type"],
                                'tran_date'=>$myrow["tran_date"],
                                'status'=>$myrow["status"],
                                'trmd_inv_no'=>$invoice_no,
                                'trmd_inv_type'=>$invoice_type
                                );
        }
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_Item_details']))
{
    if($_GET['isreplce'] == 'true'){
        $result = get_replace_item($_GET['transNo']);
        $total = DB_num_rows($result);
        while ($myrow = db_fetch($result)) {
            $status_array[] = array('stock_id'=>$myrow["stock_id"],
                                   'description'=>$myrow["description"],
                                   'qty'=>$myrow["quantity"],
                                   'unit_price'=>$myrow["unit_price"],
                                   'serial'=>$myrow["lot_no"],
                                   'chasis'=>$myrow["chassis_no"]
                                );
        }
    }else{
        $result = get_item_detials_to_repo($_GET['transNo'], $_GET['transtype']);
        $total = DB_num_rows($result);
        while ($myrow = db_fetch($result)) {
            $status_array[] = array('stock_id'=>$myrow["stock_id"],
                                   'description'=>$myrow["description"],
                                   'qty'=>$myrow["quantity"],
                                   'unit_price'=>$myrow["unit_price"],
                                   'serial'=>$myrow["lot_no"],
                                   'chasis'=>$myrow["chassis_no"]
                                );
        }
    }

    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
//----------------------------------------------: for grid js :---------------------------------------
if(isset($_GET['Get_termrepo'])){
    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);

    $result = get_ar_account_temprepo($start, $limit, $_GET['query']);
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {

        if($myrow['type'] == ST_SITERMMOD){

            $trmd_result = get_invtermode_to_repo($myrow['debtor_no'], true);
            $tmd_item = db_fetch($trmd_result);

            $category_id = $tmd_item['category_id'];
            $category_desc = $tmd_item['category'];
            $model_desc = $tmd_item['itemdesc'];
            $trmd_trans_no = $tmd_item['invoice_no'];
            $trmd_trans_type = $tmd_item['debtor_trans_type'];

        }else{

                $rplc_result = get_replace_item($myrow['trans_no']);
                
                if (db_num_rows($rplc_result) != 0){
                    
                    $rplc_item = db_fetch($rplc_result);
                    $model_desc = $rplc_item['description'];
                    $trmd_trans_no = 0;
                    $trmd_trans_type = ST_SALESRETURN;

                }else{
                    $model_desc = $myrow['item_desc'];
                    $trmd_trans_no = 0;
                    $trmd_trans_type = 0;
                }

                $category_id = $myrow['category_id'];
                $category_desc = $myrow['Catgry_desc'];
        }

        if($_GET['tag'] == 'zHun'){
            if($myrow['COUNTDOWN'] > 30){
                $status_array[] = array('trans_no'=>$myrow['trans_no'],
                    'trans_type'=>$myrow['type'],
                    'reference'=>$myrow['reference'],
                    'debtor_no'=>$myrow['debtor_no'],
                    'debtor_ref'=>$myrow['debtor_ref'],
                    'debtor_name'=>htmlentities($myrow['name']),
                    'category_id'=>$category_id,
                    'category_desc'=>$category_desc,
                    'module_type'=>$myrow['module_type'],
                    'repo_date'=>$myrow['repo_date'],
                    'days'=>$myrow['COUNTDOWN'],
                    'model_desc'=>$model_desc,
                    'trmd_inv_no'=>$trmd_trans_no,
                    'trmd_inv_type'=>$trmd_trans_type
                );
            }
        }else{
            if($myrow['COUNTDOWN'] <= 30){
                $status_array[] = array('trans_no'=>$myrow['trans_no'],
                    'trans_type'=>$myrow['type'],
                    'reference'=>$myrow['reference'],
                    'debtor_no'=>$myrow['debtor_no'],
                    'debtor_ref'=>$myrow['debtor_ref'],
                    'debtor_name'=>htmlentities($myrow['name']),
                    'category_id'=>$category_id,
                    'category_desc'=>$category_desc,
                    'module_type'=>$myrow['module_type'],
                    'repo_date'=>$myrow['repo_date'],
                    'days'=>$myrow['COUNTDOWN'],
                    'model_desc'=>$model_desc,
                    'trmd_inv_no'=>$trmd_trans_no,
                    'trmd_inv_type'=>$trmd_trans_type
                );
            }
        }
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
//----------------------------------------------------: insert, update, delete :-------------------------------------------
if(isset($_GET['update'])){
    //0 is by default no errors
    $InputError = 0;

    if (empty($_POST['moduletype']) || empty($_POST['transtype']) || empty($_POST['invoice_date'])){
        $InputError = 1;
        $dsplymsg = _('Some fields are empty or contain an improper value. Please reload the page and fill up the required field.');
    }
    if (empty($_POST['customercode'])) {
        $InputError = 1;
        $dsplymsg = _('Customer code must not be empty.');
    }
    if (empty($_POST['customername'])) {
        $InputError = 1;
        $dsplymsg = _('Customer name must not be empty.');
    }
    if (empty($_POST['trans_date'])) {
        $InputError = 1;
        $dsplymsg = _('Transaction date must not be empty.');
    }else{
        $trans_date = date('Y-m-d',strtotime($_POST['trans_date']));
    }
    if (empty($_POST['InvoiceNo'])) {
        $InputError = 1;
        $dsplymsg = _('Invoice number must not be empty.');
    }
    if(check_two_dates($_POST['invoice_date'], $_POST['trans_date']) < 1){
        $InputError = 1;
        $dsplymsg = _('Repo date must be greater than invoice date.');
    }

    if ($InputError != 1){
        //update info
        update_debtor_trans_to_repo($_POST['moduletype'], $_POST['trans_date'], $_POST['InvoiceNo'], $_POST['transtype']);

        $dsplymsg = _('AR account for repossesse has been added');

        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;

    }else {
        echo '({"failure":"true","message":"'.$dsplymsg.'"})';
        return;
    }

}elseif(isset($_GET['delete'])){
    $DeleteError = 0;

    if (!empty($_GET['syspk']) || !empty($_POST['syspk'])) {
        if (isset($_GET['syspk'])){
            $syspk = $_GET['syspk'];
        } else if (isset($_POST['syspk'])){
            $syspk = $_POST['syspk'];
        }
            update_un_temprepo($syspk, $_POST['type'], $_POST['reference']);
            $dsplymsg = ('Selected invoice has been successfully removed from temporary repossess table.');

            unset($_POST['syspk']);
            unset($_POST['type']);
            unset($_POST['reference']);

        //show response message to client side
        if($DeleteError != 1){
            echo '({"success":"true","message":"'.$dsplymsg.'"})';
            exit();
        }else{
            echo '({"failure":"true","message":"'.$dsplymsg.'"})';
            exit();
        }
    }
}
page(_($help_context = "Temporary Repossess"));

start_table(TABLESTYLE, "width='100%'");
   echo "<div id='ext-form'></div>";
end_table();

end_form();
end_page();

