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
$page_security = 'SA_PLCYBUILDER';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/admin/db/builder_plcy_db.inc");

//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root ."/js/policy_builder.js");


//----------------------------------------------: for store js :---------------------------------------

if(isset($_GET['get_cboSupplier'])){
    $result = get_cboSupplier($_GET['get_cboSupplier']);
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow['supplier_id'],
                    'supp_ref'=>$myrow['supp_ref'],
                    'supp_name'=>$myrow['supp_name']);
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_category'])){
    $result = get_itemcategory($_GET['get_category']);
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        if(isset($_GET['filterview'])){
            $status_array[] = array('id'=>0,'name'=>'All');
        }
        $status_array[] = array('id'=>$myrow['category_id'],
                    'name'=>$myrow['description']);
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_cbotypecode'])){
    if($_GET['ModuleType'] == "CSHPRCPLCY"){
        $result = get_cashpricecode($_GET['get_cbotypecode']);
    }elseif($_GET['ModuleType'] == "PRCPLCY"){
        $result = get_pricecode($_GET['get_cbotypecode']);
    }elseif($_GET['ModuleType'] == "CSTPLCY"){
        $result = get_costcode($_GET['get_cbotypecode']);
    }elseif($_GET['ModuleType'] == "SRPPLCY"){
        $result = get_srpcode($_GET['get_cbotypecode']);
    }else{
        return;
    }
    
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow['id'],
                    'name'=>$myrow['type']);
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['getpricecode'])){
    $result = get_pricecode($_GET['getpricecode']);
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow['id'],
                    'name'=>$myrow['sales_type']);
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['getcostcode'])){
    $result = get_costcode($_GET['getcostcode']);
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow['id'],
                    'name'=>$myrow['cost_type']);
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['getsrpcode'])){
    $result = get_srpcode($_GET['getsrpcode']);
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow['id'],
                    'name'=>$myrow['srp_type']);
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['getinstlplcy'])){
    $result = get_instlplcy($_GET['getinstlplcy'],$_GET['category']);
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow['id'],
                    'name'=>$myrow['plcy_code'],
                    'catid'=>$myrow['category_id']);
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['get_plcybranch'])){
    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);

    $result = get_plcybranch($_GET['get_plcybranch'], $start, $limit);
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('code'=>$myrow['branch_code'],
                    'name'=>$myrow['branch_name']);
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
//----------------------------------------------: for grid js :---------------------------------------
if(isset($_GET['get_buildermode'])){

    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
    
    $result = get_buildermode($_GET['get_buildermode'], $_GET['supplier_id'], $start, $limit, $_GET['category_id'], filter_var($_GET['showall'], FILTER_VALIDATE_BOOLEAN));
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>ucfirst(trim($myrow["id"])),
                               'cstprctype_id'=>$myrow["cstprctype_id"],
                               'cstprctype_name'=>$myrow["cstprctype_name"],
                               'category_id'=>$myrow["category_id"],
                               'category_name'=>$myrow["description"],
                               'supplier_id'=>$myrow["supplier_id"],
                               'supplier_ref'=>$myrow["supp_ref"],
                               'supplier_name'=>$myrow["supp_name"],
                               'remarks'=>$myrow["remarks"],
                               'module_type'=>$myrow["module_type"],
                               'inactive'=>$myrow["inactive"] == 0 ? 'Yes' : 'No');
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return; 
}
if(isset($_GET['get_buildsupplier'])){
    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);

    $result = get_buildsupplier($_GET['supplier_id'], $start, $limit);
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow['supplier_id'],
                    'supp_ref'=>$myrow['supp_ref'],
                    'supp_name'=>$myrow['supp_name']);
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
//----------------------------------------------------: insert, update, delete :-------------------------------------------
if(isset($_GET['submit'])){
    //0 is by default no errors
    $InputError = 0;

    if (empty($_POST['moduletype'])) {
        $InputError = 1;
        $dsplymsg = _('Some fields are empty. Please reload the page and fill up the required field.');
    }
    if (empty($_POST['supplier'])){
        $InputError = 1;
        $dsplymsg = _('Supplier must not be empty');
    }
    if (empty($_POST['category'])){
        $InputError = 1;
        $dsplymsg = _('Category must not be empty');
    }
    if (empty($_POST['costpricetype'])){
        $InputError = 1;
        $dsplymsg = _('Type must not be empty');
    }

	if (!empty($_GET['syspk']) || !empty($_POST['syspk'])) {
		if (isset($_GET['syspk'])){
			$syspk = $_GET['syspk'];
		} else if (isset($_POST['syspk'])){
			$syspk = $_POST['syspk'];
		}
    }

    //check if already exist in db
    $result = check_data_exist($_POST['supplier'], $_POST['category'], $_POST['costpricetype'],  $_POST['moduletype']);
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
        update_policy_builder($syspk, $_POST['supplier'], $_POST['category'], $_POST['costpricetype'], $_POST['remarks'], $_POST['moduletype'], $_POST['inactive']);
        $dsplymsg = _('Policy builder has been updated');

        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;

    }elseif ($InputError != 1) {
        //so this is new sale installment policy.
        add_policy_builder($_POST['supplier'], $_POST['category'], $_POST['costpricetype'], $_POST['remarks'], $_POST['moduletype'], $_POST['inactive']);
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

    unset($_POST['plcydcode']);
    unset($_POST['tax_included']);
    unset($_POST['remarks']);
    unset($_POST['term']);
    unset($_POST['frate']);
    unset($_POST['rebate']);
    unset($_POST['category']);
    unset($_POST['policytype']);
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
        //$sql = "SELECT installid FROM group_installment_fr_rebate WHERE installid=".$SelectedInstall;
        //$result = DB_query($sql);

        /*if (DB_num_rows($result)>0) {
            $DeleteError = 1;
            //$dsplymsg = ('Cannot delete this policy because it is used by other modules like policy builder');
        }else{*/
            delete_policyBuilder($syspk, $_POST['moduletype']);
            $dsplymsg = ('Policy builder has been deleted');
            unset($_POST['syspk']);
            unset($_GET['syspk']);
            unset($_POST['moduletype']);
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

    // PREVENT DELETES IF DEPENDENT RECORDS exists
    //$sql = "SELECT installid FROM group_installment_fr_rebate WHERE installid=".$SelectedInstall;
    //$result = DB_query($sql);

    /*if (DB_num_rows($result)>0) {
        $DeleteError = 1;
        
    }else{*/
    //    $sql="DELETE FROM policyinstallment WHERE installid = ".$SelectedInstall;
    //    $result = DB_query($sql);
        //$errMsg .= prnMsg_Ext(_('The installment code ') . ' ' .$_POST['installname']. ' ' . _('has been deleted') . '!','success');
    //}
    

}

//------------------------------------------------------------------------------------------------------
//simple_page_mode(true);
page(_($help_context = "Policy Builder"), false, false, "", null);

start_table(TABLESTYLE, "width='100%'");
   echo "<div id='builderplcy'></div>";
end_table();
end_form();
end_page();