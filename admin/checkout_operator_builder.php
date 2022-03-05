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
$page_security = 'SA_CHECKOPBLDR';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/admin/db/builder_plcy_db.inc");

//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root ."/js/checkout_operator_builder.js");


//----------------------------------------------: for store js :---------------------------------------

if(isset($_GET['get_cbocashier'])){
    $result = get_selectedUser($_GET['get_cbocashier'], "equal");
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('user_id'=>$myrow['user_id'],
                    'user_name'=>htmlentities($myrow['real_name']));
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_tabanguser'])){
    $result = get_selectedUser($_GET['get_tabanguser'], "notEq");
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('user_id'=>$myrow['user_id'],
                    'user_name'=>htmlentities($myrow['real_name']));
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_CHKOPuser'])){
    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);

    $result = get_checkout_operator($_GET['get_CHKOPuser'], $start, $limit, $_GET['query']);
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow['id'],
                    'cashier_id'=>$myrow['cashier_id'],
                    'cashier_name'=>htmlentities($myrow['cashier_name']),
                    'tabang_id'=>$myrow['preparer_id'],
                    'tabang_name'=>htmlentities($myrow['preparer_name']),
                    'inactive'=>$myrow["inactive"] == 0 ? 'Yes' : 'No');
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
//----------------------------------------------------: insert, update, delete :-------------------------------------------
if(isset($_GET['submit'])){
    //0 is by default no errors
    $InputError = 0;

    if (empty($_POST['cashier'])) {
        $InputError = 1;
        $dsplymsg = _('Cashier must not be empty');
    }
    if (empty($_POST['tabang_user'])){
        $InputError = 1;
        $dsplymsg = _('Preparer must not be empty');
    }

	if (!empty($_GET['syspk']) || !empty($_POST['syspk'])) {
		if (isset($_GET['syspk'])){
			$syspk = $_GET['syspk'];
		} else if (isset($_POST['syspk'])){
			$syspk = $_POST['syspk'];
		}
    }

    //check if already exist in db
    $result = check_data_exist_OP($_POST['cashier'], $_POST['tabang_user']);
    if (DB_num_rows($result)==1){
        if (isset($syspk)){
            //$dsplymsg = _('No data changed.');
        }else{
            $InputError = 1;
            $dsplymsg = _('The entered information is a duplicate.').'. '. '<br />'. _('Please enter different values.');
        }
    }
    
    if (isset($syspk) AND $InputError !=1) {
        //update info
        update_operator_builder($syspk, $_POST['cashier'], $_POST['tabang_user'], $_POST['inactive']);
        $dsplymsg = _('Checkout operator builder has been updated');

        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;

    }elseif ($InputError != 1) {
        //so this is new sale installment policy.
        add_operator_builder($_POST['cashier'], $_POST['tabang_user'], $_POST['inactive']);
        //display_notification(_('New sales installment policy has been added'));
        $dsplymsg = ('New checkout operator builder has been added.');
        
        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;

    }else{
        //$dsplymsg = ('Could not insert the new record. Please check the data and try again...');
        //$dsplymsg = ('The entered information is a duplicate. Please go back and enter different values.');

        echo '({"failure":"true","message":"'.$dsplymsg.'"})';
        return;
    }

    unset($_POST['cashier']);
    unset($_POST['tabang_user']);
    unset($_POST['inactive']);
    $Mode = 'RESET';

}elseif(isset($_GET['delete'])){
    $DeleteError = 0;

	if (!empty($_GET['syspk']) || !empty($_POST['syspk'])) {
		if (isset($_GET['syspk'])){
			$syspk = $_GET['syspk'];
		} else if (isset($_POST['syspk'])){
			$syspk = $_POST['syspk'];
        }
        
        // PREVENT DELETES IF DEPENDENT RECORDS exists
        //$sql = "SELECT installid FROM group_installment_fr_rebate WHERE installid=".$SelectedInstall;
        //$result = DB_query($sql);

        /*if (DB_num_rows($result)>0) {
            $DeleteError = 1;
            //$dsplymsg = ('Cannot delete this policy because it is used by other modules like policy builder');
        }else{*/
            delete_operator_Builder($syspk);
            $dsplymsg = ('Checkout operator builder has been deleted');
            unset($_POST['syspk']);
            unset($_GET['syspk']);
       // }

        //show response message to client side
        if($DeleteError != 1){
            echo '({"success":"true","message":"'.$dsplymsg.'"})';
            exit();
        }else{
            echo '({"failure":"true","message":"'.$dsplymsg.'"})';
            exit();
        }

        $Mode = 'RESET';
    }
}

//------------------------------------------------------------------------------------------------------
//simple_page_mode(true);
page(_($help_context = "Checkout Operator Builder"), false, false, "", null);

start_table(TABLESTYLE, "width='80%'");
   echo "<div id='ext-form'></div>";
end_table();
end_form();
end_page();