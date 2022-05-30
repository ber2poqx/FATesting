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
$page_security = 'SA_ARINVCINSTL';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/lending/includes/db/ar_installment_db.inc");

//----------------------------------------------------------------------------------------------------

add_js_ufile($path_to_root ."/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root ."/js/ar_installment_incoming.js");

//----------------------------------------------: for store js :---------------------------------------
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
if(isset($_GET['get_arinstallment'])){

    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
    
    $result = get_ar_installment_lending($_GET['InvType'], $start, $limit, $_GET['query'], filter_var($_GET['showall'], FILTER_VALIDATE_BOOLEAN));
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('ar_id'=>$myrow["id"],
                               'invoice_date'=>date('m/d/Y',strtotime($myrow["invoice_date"])),
                               'invoice_no'=>$myrow["invoice_no"],
                               'customer_code'=>$myrow["customer_code"],
                               'customer_name'=>$myrow["customer_name"],
                               'branch_code'=>$myrow["branch_code"],
                               'delivery_no'=>$myrow["delivery_no"],
                               'reference_no'=>$myrow["reference_no"],
                               'invoice_type'=>$myrow["invoice_type"],
                               'installplcy_id'=>$myrow["installmentplcy_id"],
                               'months_term'=>$myrow["months_term"],
                               'rebate'=>$myrow["rebate"],
                               'fin_rate'=>$myrow["financing_rate"],
                               'firstdue_date'=>date('m/d/Y',strtotime($myrow["firstdue_date"])),
                               'maturity_date'=>date('m/d/Y',strtotime($myrow["maturity_date"])),
                               'outs_ar_amount'=>$myrow["outstanding_ar_amount"],
                               'ar_amount'=>$myrow["ar_amount"],
                               'lcp_amount'=>$myrow["lcp_amount"],
                               'dp_amount'=>$myrow["downpayment_amount"],
                               'amortn_amount'=>$myrow["amortization_amount"],
                               'total_amount'=>$myrow["total_amount"],
                               'category_id'=>$myrow["category_id"],
                               'category_desc'=>$myrow["description"],
                               'comments'=>$myrow["comments"],
                               'stock_id'=>$myrow["stock_id"],
                               'qty'=>$myrow["qty"],
                               'unit_price'=>$myrow["unit_price"],
                               'serial_no'=>$myrow["serial_no"],
                               'chasis_no'=>$myrow["chasis_no"],
                               'prepared_by'=>$myrow["prepared_by"],
                               'approved_by'=>$myrow["approved_by"],
                               'approved_date'=>date('m/d/Y',strtotime($myrow["approved_date"])),
                               'status'=>$myrow["status"] == 0 ? 'Draft' : 'Approved',
                               'moduletype'=>$myrow["module_type"],
                               'inactive'=>$myrow["inactive"] == 0 ? 'Yes' : 'No'
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return; 
}
if(isset($_GET['get_aritem'])){

    $start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
    $limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
    
    $result = get_ar_installment_lending($_GET['InvType'], $start, $limit, $_GET['query'], filter_var($_GET['showall'], FILTER_VALIDATE_BOOLEAN));
    $total = DB_num_rows($result);
    while ($myrow = db_fetch($result)) {
        $status_array[] = array('id'=>$myrow["id"],
                               'stock_id'=>$myrow["stock_id"],
                               'qty'=>$myrow["qty"],
                               'unit_price'=>$myrow["unit_price"],
                               'serial_no'=>$myrow["serial_no"],
                               'chasis_no'=>$myrow["chasis_no"]
                            );
    }
    $jsonresult = json_encode($status_array);
    echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
    return; 
}
//------------------------------------------------------------------------------------------------------
//simple_page_mode(true);
page(_($help_context = "A/R Incoming Inquiry"), false, false, "", null);

start_table(TABLESTYLE, "width='100%'");
   echo "<div id='ARINQRY'></div>";
end_table();
display_note(_(""), 0, 0, "class='overduefg'");
end_form();
end_page();