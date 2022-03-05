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
$page_security = 'SA_PROOF_CASH';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");

add_access_extensions();

include_once($path_to_root . "/sales/includes/db/proof_cash_transaction_db.inc");
//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root ."/js/proof_of_cash.js");

//----------------------------------------------: for store js :---------------------------------------//`  
if(isset($_GET['get_proofcash']))
{
    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);

    $result = get_proof_cash($start, $limit, $_GET['query']);
    $total_result = get_proof_cash($start, $limit, $_GET['query'], true);

    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow["id"],
                               'trans_no'=>$myrow["trans_no"],
                               'tran_date'=>date('m/d/Y', strtotime($myrow["tran_date"])),
                               'one_thousand'=>$myrow["one_thousand"],
                               'one_thousand_qty'=>$myrow["one_thousand_qty"],
                               'five_hundred'=>$myrow["five_hundred"],
                               'five_hundred_qty'=>$myrow["five_hundred_qty"],
                               'two_hundred'=>$myrow["two_hundred"],
                               'two_hundred_qty'=>$myrow["two_hundred_qty"],
                               'one_hundred'=>$myrow["one_hundred"],
                               'one_hundred_qty'=>$myrow["one_hundred_qty"],
                               'fifty'=>$myrow["fifty"],
                               'fifty_qty'=>$myrow["fifty_qty"],
                               'twenty'=>$myrow["twenty"],
                               'twenty_qty'=>$myrow["twenty_qty"],
                               'ten'=>$myrow["ten"],
                               'ten_qty'=>$myrow["ten_qty"],
                               'five'=>$myrow["five"],
                               'five_qty'=>$myrow["five_qty"],
                               'one'=>$myrow["one"],
                               'one_qty'=>$myrow["one_qty"],
                               'twenty_five_cent'=>$myrow["twenty_five_cent"],
                               'twenty_five_cent_qty'=>$myrow["twenty_five_cent_qty"],
                               'ten_cent'=>$myrow["ten_cent"],
                               'ten_cent_qty'=>$myrow["ten_cent_qty"],
                               'five_cent'=>$myrow["five_cent"],
                               'five_cent_qty'=>$myrow["five_cent_qty"],
                               'comments'=>$myrow["comments"]
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
    
    $tran_date = date('Y-m-d', strtotime($_GET['tran_date']));
    if(check_cash_already_exist($tran_date)){
      $InputError = 1;
      $dsplymsg = _("Transaction Date Already Exist");    
        
    }
    if (empty($_GET['one_thousand_qty'])) {
      $InputError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }
    if (empty($_GET['five_hundred_qty'])) {
      $InputError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }
    if (empty($_GET['two_hundred_qty'])) {
      $InputError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }
    if (empty($_GET['one_hundred_qty'])) {
      $InputError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }
    if (empty($_GET['fifty_qty'])) {
      $InputError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }
    if (empty($_GET['twenty_qty'])) {
      $InputError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }
    if (empty($_GET['ten_qty'])) {
      $InputError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }
    if (empty($_GET['five_qty'])) {
      $InputError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }
    if (empty($_GET['one_qty'])) {
      $InputError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }
    if (empty($_GET['twenty_five_cent_qty'])) {
      $InputError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }
    if (empty($_GET['ten_cent_qty'])) {
      $InputError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }
    if (empty($_GET['five_cent_qty'])) {
      $InputError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }

    if ($InputError != 1){

        $tran_date = date('Y-m-d', strtotime($_GET['tran_date']));
       
        add_proof_of_cash($tran_date, $_GET['one_thousand'], $_GET['one_thousand_qty'], $_GET['five_hundred'], 
            $_GET['five_hundred_qty'], $_GET['two_hundred'], $_GET['two_hundred_qty'], $_GET['one_hundred'], 
            $_GET['one_hundred_qty'], $_GET['fifty'], $_GET['fifty_qty'], $_GET['twenty'], $_GET['twenty_qty'], 
            $_GET['ten'], $_GET['ten_qty'], $_GET['five'], $_GET['five_qty'], $_GET['one'], $_GET['one_qty'], 
            $_GET['twenty_five_cent'], $_GET['twenty_five_cent_qty'], $_GET['ten_cent'], $_GET['ten_cent_qty'], 
            $_GET['five_cent'], $_GET['five_cent_qty'], $_GET['comments']);

        $dsplymsg = _("Proof of Cash transaction has been successfully entered...");

        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;

    }else{
        echo '({"failure":"false","message":"'.$dsplymsg.'"})';
        return;
    }

}elseif(isset($_GET['update'])){
    $UpdateError = 0;
    
    if (empty($_GET['one_thousand_qty'])) {
      $UpdateError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }
    if (empty($_GET['five_hundred_qty'])) {
      $UpdateError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }
    if (empty($_GET['two_hundred_qty'])) {
      $UpdateError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }
    if (empty($_GET['one_hundred_qty'])) {
      $UpdateError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }
    if (empty($_GET['fifty_qty'])) {
      $UpdateError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }
    if (empty($_GET['twenty_qty'])) {
      $UpdateError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }
    if (empty($_GET['ten_qty'])) {
      $UpdateError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }
    if (empty($_GET['five_qty'])) {
      $UpdateError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }
    if (empty($_GET['one_qty'])) {
      $UpdateError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }
    if (empty($_GET['twenty_five_cent_qty'])) {
      $UpdateError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }
    if (empty($_GET['ten_cent_qty'])) {
      $UpdateError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }
    if (empty($_GET['five_cent_qty'])) {
      $UpdateError = 1;
      $dsplymsg = _('Quantity must not be empty.');
    }

   if ($UpdateError != 1) {
        $tran_date = date('Y-m-d', strtotime($_GET['tran_date']));
        update_proof_of_cash($tran_date, $_GET['one_thousand'], $_GET['one_thousand_qty'], $_GET['five_hundred'], 
            $_GET['five_hundred_qty'], $_GET['two_hundred'], $_GET['two_hundred_qty'], $_GET['one_hundred'], 
            $_GET['one_hundred_qty'], $_GET['fifty'], $_GET['fifty_qty'], $_GET['twenty'], $_GET['twenty_qty'], 
            $_GET['ten'], $_GET['ten_qty'], $_GET['five'], $_GET['five_qty'], $_GET['one'], $_GET['one_qty'], 
            $_GET['twenty_five_cent'], $_GET['twenty_five_cent_qty'], $_GET['ten_cent'], $_GET['ten_cent_qty'], 
            $_GET['five_cent'], $_GET['five_cent_qty'], $_GET['comments']);

        $dsplymsg = _('Proof of cash has been successfully updated...');
        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;
    }else{
        echo '({"failure":"true","message":"'.$dsplymsg.'"})';
        return;
    }
}elseif(isset($_GET['delete'])){
    $DeleteError = 0;

    if (!empty($_GET['syspk']) || !empty($_GET['syspk'])) {
        if (isset($_GET['syspk'])){
            $syspk = $_GET['syspk'];
        } else if (isset($_GET['syspk'])){
            $syspk = $_GET['syspk'];
        }
            delete_proof_of_cash($syspk, $_GET['trans_no']);
            $dsplymsg = ('Selected trans no. ans trans date has been successfully removed from proof of cash table.');

            unset($_GET['syspk']);
            unset($_GET['trans_no']);

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

page(_($help_context = "Proof Of Cash And Reports"));

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

