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
$page_security = 'SA_INSTLPLCYTYPS';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/sales/includes/db/sales_installment_policy_type_db.inc");

simple_page_mode(true);
//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root ."/js/instlpolicyType.js");

//----------------------------------------------: for store js :---------------------------------------
if(isset($_GET['getbranchArea'])){
    $result = get_all_BranchArea($_GET['getbranchArea']);
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow['id'],
                    'code'=>$myrow['code'],
                    'name'=>$myrow['areaname']);
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['vwbranchdata'])){
    $result = get_all_policyArea(false);
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow['branch_area_id'],
                    'name'=>$myrow['areaname']);
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
//----------------------------------------------: for grid js :---------------------------------------
if(isset($_GET['vwpolicytyp'])){
    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
    
    $result = get_all_policy_type($_GET['brancharea'], $start, $limit, $_GET['query'], filter_var($_GET['showall'], FILTER_VALIDATE_BOOLEAN));
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>ucfirst(trim($myrow["id"])),
                               'plcy_code'=>$myrow["plcy_code"],
                               'name'=>$myrow["name"],
                               'tax_included'=>$myrow["tax_included"],
                               'factor'=>$myrow["factor"],
                               'remarks'=>$myrow["remarks"],
                               'module_type'=>$myrow["module_type"],
                               'category_id'=>$myrow["category_id"],
                               'category'=>$myrow["categoryname"],
							   'brancharea_id'=>$myrow["ba_id"],
							   'brancharea_code'=>$myrow["code"],
                               'brancharea'=>$myrow["areaname"],
                               'status'=>$myrow["inactive"] == 0 ? 'Yes' : 'No',
                               'dateadded'=>date('m/d/Y',strtotime($myrow["date_defined"])));
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
//----------------------------------------------------: insert, update, delete :-------------------------------------------
if(isset($_GET['submit'])){
    //0 is by default no errors
    $InputError = 0;

    if (empty($_POST['plcycode'])) {
        $InputError = 1;
        $dsplymsg = _('Sales installment policy type maintenance code must not be empty');
    }
    if (empty($_POST['category'])){
        $InputError = 1;
        $dsplymsg = _('Category must not be empty');
    }
    if (empty($_POST['brancharea'])){
        $InputError = 1;
        $dsplymsg = _('Area must not be empty');
    }

	if (!empty($_GET['syspk']) || !empty($_POST['syspk'])) {
		if (isset($_GET['syspk'])){
			$syspk = $_GET['syspk'];
		} else if (isset($_POST['syspk'])){
			$syspk = $_POST['syspk'];
		}
    }

    //check if already exist in db
    $result = check_data_exist($_POST['brancharea'], $_POST['category'], $_POST['tax_included'],  $_POST['remarks'], $_POST['inactive']);
    if (DB_num_rows($result)==1){
        $InputError = 1;
        if (isset($syspk)){
            $dsplymsg = _('No data changed.');
        }else{
            $dsplymsg = _('The entered information is a duplicate.').'. '. '<br />'. _('Please enter different values.');
        }
    }

    if (isset($syspk) AND $InputError !=1) {
        //update info
        if (!empty($_POST['inactive'])){
            $inactive = 1;
        }else{
            $inactive = 0;
        }
        update_policy_type($syspk, $_POST['plcycode'], $_POST['category'], $_POST['tax_included'], $_POST['remarks'], $_POST['brancharea'], $inactive);
        $dsplymsg = _('Sales installment policy has been updated');

        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;

    }elseif ($InputError != 1) {
        //so this is new sale installment policy.
        $moduletype = "INSTLPLCYTYPS";
        $dateadded = date("Y-m-d H:i:s");

        add_policy_type($_POST['plcycode'], $_POST['category'], $_POST['tax_included'], $dateadded, $_POST['remarks'], $moduletype, $_POST['brancharea']);
        //display_notification(_('New sales installment policy has been added'));
        $dsplymsg = ('New sales installment policy type has been added.');
        
        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;

    }else{
        //$dsplymsg = ('Could not insert the new record. Please check the data and try again...');
        //$dsplymsg = ('The entered information is a duplicate. Please go back and enter different values.');

        echo '({"failure":"true","message":"'.$dsplymsg.'"})';
        return;
    }

    unset($_POST['inactive']);
    unset($_POST['tax_included']);
    unset($_POST['remarks']);
    unset($_POST['plcycode']);
    unset($_POST['brancharea']);
    unset($_POST['category']);
    unset($_POST['moduletype']);
    unset($_POST['syspk']);
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
        $result = check_dependent_records('policy_details','policyhdr_id', $syspk);
        if (DB_num_rows($result)==1){
            $DeleteError = 1;
            $dsplymsg = ('Cannot delete this policy because it is used by some related records in other tables.');
        }else{
            delete_policy_type($syspk);
            $dsplymsg = ('Sales installment code ') . ' <b>' .$_POST['plcycode']. '</b> ' . _('has been deleted');
        }
            unset($_POST['syspk']);
            unset($_POST['plcycode']);

        //show response message to client side
        if($DeleteError != 1){
            echo '({"success":"true","message":"'.$dsplymsg.'"})';
            exit();
        }else{
            echo '({"failure":"true","message":"'.$dsplymsg.'"})';
            exit();
        }
    }
    $Mode = 'RESET';
}
page(_($help_context = "Installment Policy Setup"));

start_table(TABLESTYLE, "width='100%'");
   echo "<div id='ext-form'></div>";
end_table();

end_form();
end_page();

