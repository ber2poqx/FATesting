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
$page_security = 'SA_COLLECTION_AMOUNT';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/db/proof_cash_transaction_db.inc");

add_access_extensions();
//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root ."/js/collection_target_amount.js");

//----------------------------------------------: for store js :---------------------------------------//`  
if(isset($_GET['get_percenatge_amount']))
{
    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);

    $result = get_collection_target_amount($start, $limit, $_GET['query']);
    $total_result = get_collection_target_amount($start, $limit, $_GET['query'], true);

    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow["id"],
                               'collect_date'=>date('m/d/Y', strtotime($myrow["collect_date"])),  
                               'YEAR_DATE'=>$myrow["YEAR_DATE"],         
                               'amount'=>$myrow["amount"]
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.DB_num_rows($total_result).'","result":'.$jsonresult.'})';
    return;
}

if(isset($_GET['submit']))
{
    //initialise no input errors assumed initially before we proceed
    //0 is by default no errors
    $InputError = 0;

    $collect_date = date('Y-m', strtotime($_POST['collect_date']));
    if(check_target_date_already_exist_v2($collect_date)){
      $InputError = 1;
      $dsplymsg = _("Collection month and year already exist");         
    }

    if (empty($_POST['collect_date'])) {
      $InputError = 1;
      $dsplymsg = _('Date must not be empty.');
    }

    if (empty($_POST['amount'])) {
      $InputError = 1;
      $dsplymsg = _('Amount must not be empty.');
    }

    if ($InputError != 1){

        $collect_date = date('Y-m-d', strtotime($_POST['collect_date']));
        $percentage = $_POST['percentage'];
        add_collection_amount($collect_date, $_POST['amount']);
        $dsplymsg = _("Collection target - Amount has been successfully entered...");
        
        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;
    }else{
        echo '({"failure":"false","message":"'.$dsplymsg.'"})';
        return;
    }     
}elseif(isset($_GET['update'])){
    
    $UpdateError = 0;

    if (empty($_POST['collect_date'])) {
      $UpdateError = 1;
      $dsplymsg = _('Date must not be empty.');
    }

    if (empty($_POST['amount'])) {
      $UpdateError = 1;
      $dsplymsg = _('Amount must not be empty.');
    }

    //show response message to client side
    if($UpdateError != 1){
        
        $collect_date = date('Y-m-d', strtotime($_POST['collect_date1']));      
        update_collection_amount($_POST['id'], $collect_date, $_POST['amount']);
        $dsplymsg = _('Collection Amount Target has been successfully updated...');
    
        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;
    }else{
        //$dsplymsg = _('Collection Target cannot be update...');
        echo '({"failure":"true","message":"'.$dsplymsg.'"})';
        return;
    }
}elseif (isset($_GET['delete'])) {
    $DeleteError = 0;

    $collect_date = date('Y-m-d', strtotime($_POST['collect_date']));  
    delete_collection_percent_amount($collect_date);
    $dsplymsg = ('Selected Date has been successfully removed from Collection Amount table.');

    unset($_POST['collect_date']);

    //show response message to client side
    if($DeleteError != 1){
        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        exit();
    }else{
        echo '({"failure":"true","message":"'.$dsplymsg.'"})';
        exit();
    }
    
}

page(_($help_context = "Collection Amount Target Setup"));

start_table(TABLESTYLE, "width='100%'");
   echo "<div id='ext-form'></div>";
   echo "<style type='text/css' media='screen'>
            .x-form-text-default.x-form-textarea {
                line-height: 20px;
                min-height: 30px;
            }
        </style>";
end_table();

//end_form();
end_page();

