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
$page_security = 'SA_BRANCHPLCY';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/admin/db/branch_policy_db.inc");

//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root ."/js/branch_policy.js");


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
if(isset($_GET['getbranch'])){
    global $db_connections;
    $conn = $db_connections;
    $total = count($conn);

	for ($i = 0; $i < $total; $i++)
	{
        $status_array[] = array('id'=>$conn[$i]['branch_code'],
                                'name'=>$conn[$i]['name'],
                                'area'=>$conn[$i]['branch_area']);
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
if(isset($_GET['getcashpricecode'])){
    $result = get_cashpricecode($_GET['getcashpricecode']);
    $total = DB_num_rows($result);

    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow['id'],
                    'name'=>$myrow['scash_type']);
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
if(isset($_GET['vwplcymode'])){

    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
    
    $result = get_all_Branchpolicy($_GET['vwplcymode'], $_GET['branch'], $start, $limit, $_GET['query'], filter_var($_GET['showall'], FILTER_VALIDATE_BOOLEAN));
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>ucfirst(trim($myrow["id"])),
                               'branch_code'=>$myrow["branch_code"],
                               'branch_name'=>$myrow["branch_name"],
                               'category_id'=>$myrow["category_id"],
                               'category_name'=>$myrow["catgryname"],
                               'plcyinstl_id'=>$myrow["instl_id"],
                               'plcyinstl_code'=>$myrow["plcy_code"],
                               'plcyprice_id'=>$myrow["price_id"],
                               'plcyprice_code'=>$myrow["sales_type"],
                               'plcysplrcost_id'=>$myrow["cost_id"],
                               'plcysplrcost_code'=>$myrow["cost_type"],
                               'plcysrp_id'=>$myrow["srp_id"],
                               'plcysrp_code'=>$myrow["srp_type"],
                               'plcycashprice_id'=>$myrow["cshprice_id"],
                               'plcycashprice_code'=>$myrow["scash_type"],
                               'module_type'=>$myrow["module_type"],
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

    if (empty($_POST['branchcode'])) {
        $InputError = 1;
        $dsplymsg = _('Branch must not be empty');
    }
    if (empty($_POST['branchname'])){
        $InputError = 1;
        $dsplymsg = _('Some fields are empty. Please reload the page and fill up the required field.');
    }
    if (empty($_POST['totalfields'])){
        $InputError = 1;
        $dsplymsg = _('Some fields are empty. Please reload the page and fill up the required field.');
    }
    if (empty($_POST['moduletype'])){
        $InputError = 1;
        $dsplymsg = _('Some fields are empty. Please reload the page and fill up the required field.');
    }

	if (!empty($_GET['syspk']) || !empty($_POST['syspk'])) {
		if (isset($_GET['syspk'])){
			$syspk = $_GET['syspk'];
		} else if (isset($_POST['syspk'])){
			$syspk = $_POST['syspk'];
		}
    }

    //check if already exist in db
    if ($InputError !=1 AND is_numeric($_POST['totalfields']) > 0) {
        $counter = 1;
        while($counter <= $_POST['totalfields']) {
            if (mb_strlen(isset($syspk)) != 0) {
                $brpolicy = $_POST["branchpolicy"];
                $brcategory = $_POST["category"];
            }else{
                $brpolicy = $_POST["branchpolicy$counter"];
                $brcategory = $_POST["category$counter"];
            }

            if (!empty($_POST['status'])){
                $inactive = 1;
            }else{
                $inactive = 0;
            }
            $affectedfld = 'NULL';
            if ($_POST['moduletype'] == "CSHPRCPLCY") {
                $affectedfld = "plcycashprice_id";
            }elseif($_POST['moduletype'] == "PRCPLCY") {
                $affectedfld = "plcyprice_id";
            }elseif($_POST['moduletype'] == "CSTPLCY") {
                $affectedfld = "plcysplrcost_id";
            }elseif($_POST['moduletype'] == "SRPPLCY") {
                $affectedfld = "plcysrp_id";
            }elseif($_POST['moduletype'] == "INSTLPLCY") {
                $affectedfld = "plcyinstl_id";
            }elseif($_POST['moduletype'] == "CSHPRCPLCY") {
                $affectedfld = "plcycashprice_id";
            }

            $result = check_data_exist($_POST['branchcode'], $_POST['branchname'], $brpolicy, $brcategory, $_POST['moduletype'], $inactive, $affectedfld);
            if (DB_num_rows($result)==1){
                $InputError = 1;
                if (isset($syspk)){
                    $dsplymsg .= _('No data changed for '.$_POST['branchcode'].'|'.$brpolicy.'|'.$brcategory.'|'.$_POST['moduletype']);
                }else{
                    $dsplymsg .= _('The entered information is a duplicate.'). $_POST['branchcode'].'|'.$brpolicy.'|'.$brcategory.'|'.$_POST['moduletype'].'<br />'. _('Please enter different values.');
                }
            }else{
                if (isset($syspk) AND $InputError !=1) {

                    update_branchplcy($syspk, $inactive, $brcategory, $_POST['moduletype'], $brpolicy, $affectedfld);
                    $dsplymsg .= _('Record has been successfully updated...');
                    $zhuntip = "upd";

                } elseif ($InputError != 1) {
                    $dateadded = date("Y-m-d H:i:s");

                    add_branchplcy($dateadded, $_POST['branchcode'], $_POST['branchname'], $brpolicy, $brcategory, $_POST['moduletype'], $affectedfld);
                    $dsplymsg .= _('New branch policy has been added. '.$_POST['branchcode'].'|'.$brpolicy.'|'.$brcategory.'<br />');
                    $zhuntip = "add";

                }
            }

			//incase of infinite loop
			if ($counter == $_POST['totalfields']){
                break;
            }
            $counter++;
        }
    }

    if ($InputError != 1) {
        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;
    }else{
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
            delete_branchplcy($syspk, $_POST['moduletype']);
            $dsplymsg = ('Branch policy has been deleted');
            unset($_POST['syspk']);
            unset($_POST['plcycode']);
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
        $sql="DELETE FROM policyinstallment WHERE installid = ".$SelectedInstall;
        $result = DB_query($sql);
        //$errMsg .= prnMsg_Ext(_('The installment code ') . ' ' .$_POST['installname']. ' ' . _('has been deleted') . '!','success');
    //}
    

}

//------------------------------------------------------------------------------------------------------
//simple_page_mode(true);
page(_($help_context = "Branch Policy Setup"), false, false, "", null);

start_table(TABLESTYLE, "width='100%'");
   echo "<div id='salesinstalplcy'></div>";
end_table();
end_form();
end_page();