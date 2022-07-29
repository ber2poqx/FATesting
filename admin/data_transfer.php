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
$page_security = 'SA_CDATATRANS';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/admin/db/data_transfer_db.inc");

//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root ."/js/data_transfer.js");


//----------------------------------------------: for store js :---------------------------------------

if(isset($_GET['get_branch'])){
    global $db_connections;
    $conn = $db_connections;
    $total = count($conn);

    $status_array[] = array('branch_no'=>"all",
        'branch_name'=>"All"
    );
	for ($i = 0; $i < $total; $i++)
	{
        $status_array[] = array('branch_no'=>Get_db_coy($conn[$i]['branch_code']),
                                'branch_name'=>$conn[$i]['branch_code']. '-'.$conn[$i]['name']);
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}

if(isset($_GET['get_datadesc'])){

    $status_array[] = array('id'=>"item",
        'description'=>"Products"
    );
    $status_array[] = array('id'=>"suplr",
        'description'=>"Suppliers"
    );
    $status_array[] = array('id'=>"Subcatgry",
        'description'=>"Sub Category"
    );
    $status_array[] = array('id'=>"clasftn",
        'description'=>"Brand"
    );
    $status_array[] = array('id'=>"brand",
        'description'=>"Classification"
    );

    $total = count($status_array);
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}

//----------------------------------------------------: insert, update, delete :-------------------------------------------
if(isset($_GET['submit'])){
    //0 is by default no errors
    $InputError = 0;

    if (empty($_POST['datadesc'])) {
        $InputError = 1;
        $dsplymsg = _('Description must not be empty');
    }
    if (empty($_POST['branch'])){
        $InputError = 1;
        $dsplymsg = _('Branch must not be empty');
    }
    if (empty($_POST['date_from'])){
        $InputError = 1;
        $dsplymsg = _('Date from must not be empty');
    }
    if (empty($_POST['date_to'])){
        $InputError = 1;
        $dsplymsg = _('Date to must not be empty');
    }

    if($InputError !=1) {
        if($_POST['datadesc'] == "item"){

            $items = get_items_to_transfer($_POST['date_from'], $_POST['date_to']);
            while ($itemrow = db_fetch($items)) {

                $checkres = check_item($myrow["stock_id"], );

                if (DB_num_rows($result)==1){
                    if (isset($syspk)){
                        //$dsplymsg = _('No data changed.');
                    }else{
                        $InputError = 1;
                        $dsplymsg = _('The entered information is a duplicate.').'. '. '<br />'. _('Please enter different values.');
                    }
                }
            }
        }
    }else{
        echo '({"failure":"true","message":"'.$dsplymsg.'"})';
        return;
    }
    $Mode = 'RESET';
}

//------------------------------------------------------------------------------------------------------
//simple_page_mode(true);
page(_($help_context = "Data Synchronization - Data Transfer From HO to Branch"), false, false, "", null);

start_table(TABLESTYLE, "width='80%'");
   echo "<div id='ext-form'></div>";
end_table();
end_form();
end_page();