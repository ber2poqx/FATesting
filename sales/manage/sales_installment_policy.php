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
$page_security = 'SA_INSTLPLCY';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/sales/includes/db/sales_installment_policy_db.inc");


//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root ."/js/salesinstalplcy.js");
//add_js_ufile($path_to_root . "/css/sales_installment_policy.css");

//----------------------------------------------: for store js :---------------------------------------
if(isset($_GET['getplcytype'])){
    $result = get_policytype($_GET['getplcytype']);
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
if(isset($_GET['getplcyterm'])){
    $result = get_policyterm(false);
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('number'=>$myrow['term']);
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['getplcyfrate'])){
    $result = get_policyfrate();
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('number'=>$myrow['financing_rate']);
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
if(isset($_GET['getplcyrebate'])){
    $result = get_policyrebate();
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('number'=>$myrow['rebate']);
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return;
}
//----------------------------------------------: for grid js :---------------------------------------
if(isset($_GET['vwplcydata'])){
    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
    
    $result = get_all_instlpolicy($_GET['plcyterm'], $start, $limit, $_GET['query'], filter_var($_GET['showall'], FILTER_VALIDATE_BOOLEAN));

    $total_result = get_all_instlpolicy($_GET['plcyterm'], $start, $limit, $_GET['query'], filter_var($_GET['showall'], FILTER_VALIDATE_BOOLEAN));
    $total = DB_num_rows($total_result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('plcyd_id'=>ucfirst(trim($myrow["id"])),
                               'plcyd_code'=>$myrow["plcydtl_code"],
                               'description'=>$myrow["description"],
                               'tax_incl'=>$myrow["tax_included"],
                               'remarks'=>$myrow["remarks"],
                               'factor'=>$myrow["factor"],
                               'term'=>$myrow["term"],
                               'frate'=>$myrow["financing_rate"],
                               'rebate'=>$myrow["rebate"],
                               'penalty'=>$myrow["penalty"],
                               'moduletype'=>$myrow["module_type"],
                               'categoryid'=>$myrow["category_id"],
                               'category'=>$myrow["catgryname"],
                               'plcyh_id'=>$myrow["policyhdr_id"],
                               'plcyh_code'=>$myrow["plcy_code"],
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

    if (empty($_POST['plcydcode'])) {
        $InputError = 1;
        $dsplymsg = _('Sales installment policy maintenance code must not be empty');
    }
    if (empty($_POST['policytype'])){
        $InputError = 1;
        $dsplymsg = _('Sales installment policy type must not be empty');
    }

	if (!empty($_GET['syspk']) || !empty($_POST['syspk'])) {
		if (isset($_GET['syspk'])){
			$syspk = $_GET['syspk'];
		} else if (isset($_POST['syspk'])){
			$syspk = $_POST['syspk'];
		}
    }

    //check if already exist in db
    $result = check_data_exist($_POST['plcydcode'], $_POST['term'], $_POST['frate'], $_POST['rebate'], $_POST['category'], $_POST['policytype'], $_POST['tax_included'], $_POST['remarks'], $_POST['inactive']);
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
        update_policy_type($syspk, $_POST['plcydcode'], $_POST['tax_included'], $_POST['remarks'], $_POST['term'], $_POST['frate'], $_POST['rebate'], $_POST['category'], $_POST['policytype'], $inactive);
        $dsplymsg = _('Sales installment policy has been updated');

        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;

    }elseif ($InputError != 1) {
        //so this is new sale installment policy.
        $moduletype = "INSTLPLCY";
        $dateadded = date("Y-m-d H:i:s");

        add_instllpolicy($_POST['plcydcode'], $_POST['tax_included'], $dateadded, $_POST['remarks'], $moduletype, $_POST['term'], $_POST['frate'], $_POST['rebate'], $_POST['category'], $_POST['policytype']);
        //display_notification(_('New sales installment policy has been added'));
        $dsplymsg = ('New sales installment policy has been added.');
        
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
        $result = check_dependent_records('sales_orders','payment_terms', $syspk);
        if (DB_num_rows($result)>0) {
            $DeleteError = 1;
            $dsplymsg = ('<b>Cannot delete this policy because it is used by some related records in other tables like sales order</b>');
        }else{
            delete_si_plcy($syspk);
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

        $Mode = 'RESET';
    }

    // PREVENT DELETES IF DEPENDENT RECORDS exists
    //$sql = "SELECT installid FROM group_installment_fr_rebate WHERE installid=".$SelectedInstall;
    //$result = DB_query($sql);

    /*if (DB_num_rows($result)>0) {
        $DeleteError = 1;
        
    }else{*/
        $sql="DELETE FROM policyinstallment WHERE installid = ".$SelectedInstall;
        $result = DB_query($sql);
        //$errMsg .= prnMsg_Ext(_('The installment code ') . ' ' .$_POST['installname']. ' ' . _('has been deleted') . '!','success');
    //}
    

}
//------------------------------------------------------------------------------------------------------
//simple_page_mode(true);
page(_($help_context = "Financing Rate Setup"), false, false, "", $js);

start_table(TABLESTYLE, "width='100%'");
   echo "<div id='salesinstalplcy'></div>";
end_table();
end_form();
end_page();