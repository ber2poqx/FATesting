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
$page_security = 'SA_PROMOTIONAL_PRICING';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/lending/includes/lending_cfunction.inc");
include_once($path_to_root . "/modules/Promotional_pricing_per_serial/includes/promotional_pricing_per_serial.inc");

//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root ."/js/promotional_pricing_per_serial.js");

//----------------------------------------------: for store js :---------------------------------------
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

if(isset($_GET['get_promotional_allDB']) AND $_GET['branch'] != ""){

    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);

    $result = get_promotional_pricing_AllDB($_GET['branch'], $_GET['category'], $_GET['brand'], $start, $limit, $_GET['query']);

    $total_result = get_promotional_pricing_AllDB($_GET['branch'], $_GET['category'], $_GET['brand'], $start, $limit, $_GET['query'], true);

    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('stock_id_item'=>$myrow['serialise_item_code'],
                                'serial'=>$myrow['serialise_lot_no'], 
                                'chasis'=>$myrow['serialise_chasis_no'],                                        
                                'location_code'=>$myrow['serialise_loc_code'],                                        
                                'price'=>$myrow['serialise_lcp_promotional_price'],                                        
                                'categid'=>$myrow['category_id'],                                        
                                'categdescription'=>$myrow['description'],                                        
                                'code_brand'=>$myrow['brand'],                                        
                                'code_brand_name'=>$myrow['name'],                                       
                                'itemstock_id'=>$myrow['stock_id'],                                       
                                'branch_code'=>$_GET['branch']                          
                            );
     }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.DB_num_rows($total_result).'","result":'.$jsonresult.'})';

    set_global_connection();
    return;
}

if(isset($_GET['get_promotional_category_allDB']) AND $_GET['branch'] != ""){

    $result = get_promotional_pricing_category($_GET['branch'], $_GET['category']);
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('category_id'=>$myrow['category_id'],
                                'description'=>$myrow['description'],
                                'branch_code'=>$_GET['branch']                                                                   
                            );
     }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';

    set_global_connection();
    return;
}

if(isset($_GET['get_promotional_brand_allDB']) AND $_GET['branch'] != ""){

    $result = get_promotional_pricing_brand($_GET['branch'], $_GET['category']);
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('brand_id'=>$myrow['id'],
                                'brand_name'=>$myrow['name'],                               
                                'brand_categ'=>$myrow['category_id'], 
                                'branch_code'=>$_GET['branch']  
                            );
     }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';

    set_global_connection();
    return;
}

if(isset($_GET['get_promotional_itemcode']) AND $_GET['branch'] != ""){

    $result = get_promotional_pricing_itemcode($_GET['branch'], $_GET['category'], $_GET['brand']);
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('stock_item_code'=>$myrow['stock_id'],                                    
                                'categ_item_id'=>$myrow['category_id'],
                                'code_item_brand'=>$myrow['brand'],                                                            
                                'branch_code'=>$_GET['branch']                          
                            );
     }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';

    set_global_connection();
    return;
}

if(isset($_GET['update'])){
    //0 is by default no errors
    $UpdateError = 0;

    //show response message to client side
    if($UpdateError != 1){
        
        //$branch_code = 'branch_code'=>$_GET['Promotional'];
        $serialise_item_code = $_GET['CodesItem'];
        $serialise_lot_no = $_GET['Lot_no'];
        $serialise_chasis_no = $_GET['Chasis_no'];
        $serialise_loc_code = $_GET['loca_code'];
        $serialise_lcp_promotional_price = $_GET['price_serial'];
        update_promotional_price_per_serial($serialise_item_code, $serialise_lot_no, $serialise_chasis_no, 
            $serialise_loc_code, $serialise_lcp_promotional_price);
        $dsplymsg = _("Selected Item: "  .$serialise_item_code.  " with serial number : " .$serialise_lot_no. " has been updated.");
    
        echo '({"success":"true","message":"'.$dsplymsg.'"})';
        return;
    }else{
        $dsplymsg = ('Could not update the record. Please check the data and try again...');
        echo '({"failure":"true","message":"'.$dsplymsg.'"})';
        return;
    }
}
simple_page_mode(true);

page(_($help_context = "Promotional Pricing"));

start_table(TABLESTYLE, "width='100%'");
   echo "<div id='ext-form'></div>";
end_table();

end_form();
end_page();

