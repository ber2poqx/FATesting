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
$page_security = 'SA_SUPPLIER_APPROVED';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/lending/includes/lending_cfunction.inc");
include_once($path_to_root . "/modules/Supplier_request_approved/includes/supplier_approved_request.inc");

//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root ."/js/supplier_request.js");

//----------------------------------------------: for store js :---------------------------------------
if(isset($_GET['get_supplier_request_allDB']) AND $_GET['Status'] != ""){

	$start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);

    $result = get_all_supplier_request_to_HO($start, $limit, $_GET['query'], $_GET['Status']);
    $total_result = get_all_supplier_request_to_HO($start, $limit, $_GET['query'], $_GET['Status'], true);

    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('supplier_ref'=>$myrow['supplier_ref'],
                                'supp_name'=>htmlentities($myrow['supp_name']), 
                                'STATUS'=>$myrow['STATUS'],                                        
                                'id'=>$myrow['id'],                                        
                                'inactive'=>$myrow['inactive'],                                        
                                'STATUSSERACH'=>$_GET['Status']                              
                            );
     }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.DB_num_rows($total_result).'","result":'.$jsonresult.'})';

    set_global_connection();
    return;
}

if(isset($_GET['update'])){
    //0 is by default no errors
    $UpdateError = 0;

    //show response message to client side
    if($UpdateError != 1){
        
        $status = $_GET['Status2']; 
        update_get_all_supplier_request_to_HO($_GET['id'], $_GET['supplier_ref'], $_GET['supp_name'], $status, $_GET['inactive']);
        $dsplymsg = _("Selected Supplier has been updated.");
    
        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;
    }else{
        $dsplymsg = ('Could not update the record. Please check the data and try again...');
        echo '({"failure":"true","message":"'.$dsplymsg.'"})';
        return;
    }
}
simple_page_mode(true);

page(_($help_context = "Supplier's Request Approval"));

start_table(TABLESTYLE, "width='100%'");
   echo "<div id='ext-form'></div>";
end_table();

end_form();
end_page();

