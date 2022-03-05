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
$page_security = 'SA_COLLECTION_PERCENTAGE';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/db/proof_cash_transaction_db.inc");

add_access_extensions();
//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root ."/js/collection_target.js");

//----------------------------------------------: for store js :---------------------------------------//`  
if(isset($_GET['get_percenatge_target']))
{
    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);

    $result = get_collection_target_percent($start, $limit, $_GET['query']);
    $total_result = get_collection_target_percent($start, $limit, $_GET['query'], true);

    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow["id"],
                               'collect_date'=>$myrow["YEAR"],
                               //'collect_date0'=>$myrow["YEARS"],
                               'percentage0'=>$myrow["JANUARY"],
                               'percentage1'=>$myrow["FEBRUARY"],
                               'percentage2'=>$myrow["MARCH"],
                               'percentage3'=>$myrow["APRIL"],
                               'percentage4'=>$myrow["MAY"],
                               'percentage5'=>$myrow["JUNE"],
                               'percentage6'=>$myrow["JULY"],
                               'percentage7'=>$myrow["AUGUST"],
                               'percentage8'=>$myrow["SEPTEMBER"],
                               'percentage9'=>$myrow["OCTOBER"],
                               'percentage10'=>$myrow["NOVEMBER"],
                               'percentage11'=>$myrow["DECEMBER"]
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

    $collect_date = $_POST['collect_date0'];
    if(check_target_date_already_exist($collect_date)){
      $InputError = 1;
      $dsplymsg = _("Collection Year Already Exist");    
        
    }

    $collect_date = $_POST['collect_date1'];
    if(check_target_date_already_exist($collect_date)){
      $InputError = 1;
      $dsplymsg = _("Collection Year Already Exist");    
        
    }

    $collect_date = $_POST['collect_date2'];
    if(check_target_date_already_exist($collect_date)){
      $InputError = 1;
      $dsplymsg = _("Collection Year Already Exist");    
        
    }

    $collect_date = $_POST['collect_date3'];
    if(check_target_date_already_exist($collect_date)){
      $InputError = 1;
      $dsplymsg = _("Collection Year Already Exist");    
        
    }

    $collect_date = $_POST['collect_date4'];
    if(check_target_date_already_exist($collect_date)){
      $InputError = 1;
      $dsplymsg = _("Collection Year Already Exist");    
        
    }

    $collect_date = $_POST['collect_date5'];
    if(check_target_date_already_exist($collect_date)){
      $InputError = 1;
      $dsplymsg = _("Collection Year Already Exist");    
        
    }

    $collect_date = $_POST['collect_date6'];
    if(check_target_date_already_exist($collect_date)){
      $InputError = 1;
      $dsplymsg = _("Collection Year Already Exist");    
        
    }

    $collect_date = $_POST['collect_date7'];
    if(check_target_date_already_exist($collect_date)){
      $InputError = 1;
      $dsplymsg = _("Collection Year Already Exist");    
        
    }

    $collect_date = $_POST['collect_date8'];
    if(check_target_date_already_exist($collect_date)){
      $InputError = 1;
      $dsplymsg = _("Collection Year Already Exist");    
        
    }

    $collect_date = $_POST['collect_date9'];
    if(check_target_date_already_exist($collect_date)){
      $InputError = 1;
      $dsplymsg = _("Collection Year Already Exist");    
        
    }

    $collect_date = $_POST['collect_date10'];
    if(check_target_date_already_exist($collect_date)){
      $InputError = 1;
      $dsplymsg = _("Collection Year Already Exist");    
        
    }

    $collect_date = $_POST['collect_date11'];
    if(check_target_date_already_exist($collect_date)){
      $InputError = 1;
      $dsplymsg = _("Collection Year Already Exist");    
        
    }

    if (empty($_POST['collect_date0'])) {
      $InputError = 1;
      $dsplymsg = _('Collection Year must not be empty.');
    }

    if (empty($_POST['collect_date1'])) {
      $InputError = 1;
      $dsplymsg = _('Collection Year must not be empty.');
    }

    if (empty($_POST['collect_date2'])) {
      $InputError = 1;
      $dsplymsg = _('Collection Year must not be empty.');
    }

    if (empty($_POST['collect_date3'])) {
      $InputError = 1;
      $dsplymsg = _('Collection Year must not be empty.');
    }

    if (empty($_POST['collect_date4'])) {
      $InputError = 1;
      $dsplymsg = _('Collection Year must not be empty.');
    }

    if (empty($_POST['collect_date5'])) {
      $InputError = 1;
      $dsplymsg = _('Collection Year must not be empty.');
    }

    if (empty($_POST['collect_date6'])) {
      $InputError = 1;
      $dsplymsg = _('Collection Year must not be empty.');
    }

    if (empty($_POST['collect_date7'])) {
      $InputError = 1;
      $dsplymsg = _('Collection Year must not be empty.');
    }

    if (empty($_POST['collect_date8'])) {
      $InputError = 1;
      $dsplymsg = _('Collection Year must not be empty.');
    }

    if (empty($_POST['collect_date9'])) {
      $InputError = 1;
      $dsplymsg = _('Collection Year must not be empty.');
    }

    if (empty($_POST['collect_date10'])) {
      $InputError = 1;
      $dsplymsg = _('Collection Year must not be empty.');
    }

    if (empty($_POST['collect_date11'])) {
      $InputError = 1;
      $dsplymsg = _('Collection Year must not be empty.');
    }

    if (empty($_POST['percentage0'])) {
      $InputError = 1;
      $dsplymsg = _('Percentage must not be empty.');
    }

    if (empty($_POST['percentage1'])) {
      $InputError = 1;
      $dsplymsg = _('Percentage must not be empty.');
    }

    if (empty($_POST['percentage2'])) {
      $InputError = 1;
      $dsplymsg = _('Percentage must not be empty.');
    }

    if (empty($_POST['percentage3'])) {
      $InputError = 1;
      $dsplymsg = _('Percentage must not be empty.');
    }

    if (empty($_POST['percentage4'])) {
      $InputError = 1;
      $dsplymsg = _('Percentage must not be empty.');
    }

    if (empty($_POST['percentage5'])) {
      $InputError = 1;
      $dsplymsg = _('Percentage must not be empty.');
    }

    if (empty($_POST['percentage6'])) {
      $InputError = 1;
      $dsplymsg = _('Percentage must not be empty.');
    }

    if (empty($_POST['percentage7'])) {
      $InputError = 1;
      $dsplymsg = _('Percentage must not be empty.');
    }

    if (empty($_POST['percentage8'])) {
      $InputError = 1;
      $dsplymsg = _('Percentage must not be empty.');
    }

    if (empty($_POST['percentage9'])) {
      $InputError = 1;
      $dsplymsg = _('Percentage must not be empty.');
    }

    if (empty($_POST['percentage10'])) {
      $InputError = 1;
      $dsplymsg = _('Percentage must not be empty.');
    }

    if (empty($_POST['percentage11'])) {
      $InputError = 1;
      $dsplymsg = _('Percentage must not be empty.');
    }

    
    if ($InputError != 1){
        $CI = 0;
        while ($CI <= 11) {
            $collect_date = $_POST['collect_date0'] . '-' . $_POST['month'. '' .$CI];
            $month = $_POST['month' . '' . $CI];
            $percentage = $_POST['percentage' . '' . $CI];
            add_collection_target($collect_date, $month, $percentage);
            $dsplymsg = _("Collection target - Percentage has been successfully entered...");
            $CI++;
        }
        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;
    }else{
        echo '({"failure":"false","message":"'.$dsplymsg.'"})';
        return;
    }     
}elseif(isset($_GET['update'])){
    
    $UpdateError = 0;

    //show response message to client side
    if($UpdateError != 1){
        $DI = 0;
        while ($DI <= 11) {
            $collect_date =  $_POST['collect_date0'] . '-' . $_POST['month'. '' .$DI];
            $month = $_POST['month' . '' . $DI];
            $percentage = $_POST['percentage' . '' . $DI];

            update_collection_percentage($collect_date, $month, $percentage);
            $dsplymsg = _('Collection Percentage Target has been successfully updated...');
            $DI++;
        }
        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;
    }else{
        $dsplymsg = _('Collection Target cannot be update...');
        echo '({"failure":"true","message":"'.$dsplymsg.'"})';
        return;
    }
}elseif (isset($_GET['delete'])) {
    $DeleteError = 0;

    delete_collection_percent_target($_POST['collect_date']);
    $dsplymsg = ('Selected Year. has been successfully removed from collection percentage target table.');

    //unset($_POST['syspk']);
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

page(_($help_context = "Collection Percentage Target Setup"));

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

