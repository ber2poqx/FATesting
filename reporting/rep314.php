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
$page_security = 'SA_PROD_REP'; //Modified by spyrax10 18 Jun 2022
// ----------------------------------------------------------------
// $ Revision:  2.0 $
// Creator: RobertGwapo
// date:    2021-04-29
// Title:   Repo Register Report
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/taxes/tax_calc.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/inventory/includes/db/items_category_db.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/sysnames.inc");
include_once($path_to_root . "/includes/types.inc");
//----------------------------------------------------------------------------------------------------

print_PO_Report();

function get_repo_register($to)
{
    $to = date2sql($to);
    $sql = "SELECT A.*, F.tran_date AS orig_date, B.stock_id, B.serial_no, B.chassis_no, 
        C.brand, D.name AS Brand, E.name AS Customer_name,

        IFNULL(QTYOUT.QTY, NULL) AS Status

        FROM repo_accounts A
        LEFT JOIN repo_item_details B ON B.ar_trans_no = A.ar_trans_no 
        AND B.repo_id = A.id
        LEFT JOIN stock_master C ON C.stock_id = B.stock_id
        LEFT JOIN item_brand D ON D.id = C.brand
        LEFT JOIN debtors_master E ON E.debtor_no = A.debtor_no
        LEFT JOIN debtor_trans F ON F.trans_no = A.ar_trans_no
        LEFT JOIN stock_moves G ON G.lot_no = B.serial_no AND G.stock_id = B.stock_id

        LEFT JOIN (
        SELECT L.stock_id, L.lot_no, 
        SUM(L.qty) AS QTY, BB.serial_no
        FROM stock_moves L
        LEFT JOIN repo_item_details BB ON L.stock_id = BB.stock_id AND L.lot_no = BB.serial_no
        LEFT JOIN repo_accounts CC ON CC.ar_trans_no = BB.ar_trans_no AND CC.id = BB.repo_id
        WHERE L.item_type = 'repo' AND DATE_FORMAT(L.tran_date, '%Y-%m-%d') <= DATE_FORMAT('$to', '%Y-%m-%d')
        GROUP BY L.stock_id, L.lot_no
        ) QTYOUT ON B.stock_id = QTYOUT.stock_id AND B.serial_no = QTYOUT.lot_no

        WHERE A.trans_date  <= '$to'
        GROUP BY A.reference_no
        ORDER BY A.reference_no DESC";

    return db_query($sql, "could not query stock moves");
}

function get_repo_register_serial($serial_no)
{
    $sql = "SELECT type FROM stock_moves
        WHERE lot_no = ".db_escape($serial_no)."
        AND item_type = 'repo' AND qty = '-1'
        ORDER BY tran_date DESC LIMIT 1";
    $result = db_query($sql, "could not query stock moves");
    $row = db_fetch_row($result);
    return $row[0];
}

function get_repo_register_customer_details($serial_no, $stock_type)
{
    if ($stock_type == 55) {
        
        $sql = "SELECT trans.tran_date AS Newdate, trans.reference AS Reference, 
        master.name AS NewCustomer
        FROM stock_moves stock
        LEFT JOIN debtor_trans trans ON trans.trans_no = stock.trans_no
        LEFT JOIN debtors_master master ON master.debtor_no = trans.debtor_no
        WHERE stock.lot_no = ".db_escape($serial_no)." AND trans.type = ".db_escape($stock_type)."
        GROUP BY stock.trans_no
        ORDER BY stock.tran_date DESC";

        return db_query($sql, "could not query stock moves");
    }

    if ($stock_type == 59) {
        
        $sql = "SELECT stock.tran_date AS Newdate, stock.reference AS Reference, 
        stock.loc_code AS NewCustomer
        FROM stock_moves stock
        WHERE stock.lot_no = ".db_escape($serial_no)." AND stock.type = ".db_escape($stock_type)."
        GROUP BY stock.trans_no
        ORDER BY stock.tran_date DESC";

        return db_query($sql, "could not query stock moves");
    }

    if ($stock_type == 13) {
        
        $sql = "SELECT trans.tran_date AS Newdate, master.name AS NewCustomer,
        loans.reference AS Reference
        FROM stock_moves stock
        LEFT JOIN debtor_trans trans ON trans.reference = stock.reference
        LEFT JOIN debtors_master master ON master.debtor_no = trans.debtor_no
        LEFT JOIN debtor_loans loans ON loans.delivery_ref_no = trans.reference
        WHERE stock.lot_no = ".db_escape($serial_no)." AND trans.type = ".db_escape($stock_type)."
        GROUP BY stock.trans_no
        ORDER BY stock.tran_date DESC";

        return db_query($sql, "could not query stock moves");
    }

    if ($stock_type == 17) {
        
        $sql = "SELECT stock.tran_date AS Newdate, stock.reference AS Reference, 
        stock.loc_code AS NewCustomer
        FROM stock_moves stock
        WHERE stock.lot_no = ".db_escape($serial_no)." AND stock.type = ".db_escape($stock_type)."
        GROUP BY stock.trans_no
        ORDER BY stock.tran_date DESC";

        return db_query($sql, "could not query stock moves");
    }
}

function print_PO_Report()
{
    global $path_to_root, $SysPrefs;

    $to         = $_POST['PARAM_0'];
    $orientation= $_POST['PARAM_1'];
    $destination= $_POST['PARAM_2'];

    if ($destination)
        include_once($path_to_root . "/reporting/includes/excel_report.inc");
    else
        include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $orientation = 'L';

    $brchcode = $db_connections[user_company()]["branch_code"];
    
    $dec = user_price_dec();

    $params = array(0 => $comments,
        1 => array('text' => _('As Of Date'),'from' => $to, 'to' => ''));   

    $cols = array(0, 37, 75, 135, 140, 198, 240, 245, 305, 310, 
    385, 390, 465, 470, 505, 537, 568, 617, 632, 665, 726, 728);

    $headers = array(
        _('Repo Date'), 
        _('Orig Date'),
        _('Customer Name'),
        _(''),
        _('RR Num'),
        _('Brand'),
        _(''),
        _('Model'),
        _(''),
        _('Serial/Engine'),
        _(''),
        _('Chasis Number'),
        _(''),
        _('Mont.Amort'),
        _('Balance'),
        _('Unre.cost'),
        _('Sold to'),
        _(''),
        _('Date Sold'),
        _('Trans Num'),
        _(''),
        _('Status'));

    $aligns = array('left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left',
    'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left');

     $rep = new FrontReport(_('Repo Register Report'), "RepoRegisterReport", "Legal", 9, $orientation);
    if ($orientation == 'L')
        recalculate_cols($cols);

    
    $rep->fontSize -= 1;
    $rep->Info($params, $cols, $headers, $aligns, 
        null, null, null, true, true, true);
    //$rep->SetHeaderType('COLLECTION_Header');
    if ($destination) {
        $rep->SetHeaderType('PO_Header');
    }
    else {
        $rep->SetHeaderType('COLLECTION_Header');     
    }
    $rep->NewPage();

    $res = get_repo_register($to);

    while ($ACRBCR = db_fetch($res))
    {

        if ($ACRBCR['Status'] == 0) {
           $status_new = 'Sold';
        } else {
           $status_new = 'Available';
        }

        $rep->NewLine(1);
        $rep->TextCol(0, 1, sql2date($ACRBCR['repo_date']));
        $rep->TextCol(1, 2, sql2date($ACRBCR['orig_date']));
        $rep->TextCol(2, 3, $ACRBCR["Customer_name"]);
        $rep->TextCol(3, 4, $ACRBCR['']);
        $rep->TextCol(4, 5, $ACRBCR["reference_no"]);
        $rep->TextCol(5, 6, $ACRBCR["Brand"]);
        $rep->TextCol(6, 7, $ACRBCR['']);
        $rep->TextCol(7, 8, $ACRBCR['stock_id']);
        $rep->TextCol(8, 9, $ACRBCR['']);
        $rep->TextCol(9, 10, $ACRBCR['serial_no']);
        $rep->TextCol(10, 11, $ACRBCR['']);
        $rep->TextCol(11, 12, $ACRBCR['chassis_no']);
        $rep->TextCol(12, 13, $ACRBCR['']); 
        $rep->AmountCol(13, 14, $ACRBCR['monthly_amount']); 
        $rep->AmountCol(14, 15, $ACRBCR['balance']); 
        $rep->AmountCol(15, 16, $ACRBCR['unrecovered_cost']);

        $stock_type = get_repo_register_serial($ACRBCR['serial_no']);
        $customer = get_repo_register_customer_details($ACRBCR['serial_no'], $stock_type);
        
        $myrow = db_fetch($customer);
        if ($ACRBCR['Status'] == 0) {
            $Customer = $myrow["NewCustomer"];
            $newdate = $myrow["Newdate"];
            $reference = $myrow["Reference"];
        } else {
            $Customer = $myrow[""];
            $newdate = $myrow[""];
            $reference = $myrow[""];
        }
        
        $rep->TextCol(16, 17, $Customer);
        $rep->TextCol(17, 18, $ACRBCR['']);
        $rep->TextCol(18, 19, sql2date($newdate));
        $rep->TextCol(19, 20, $reference);
        $rep->TextCol(20, 21, $ACRBCR[""]);
       
        $rep->TextCol(21, 22, $status_new);
        $rep->NewLine(1);
    }
    $rep->End();
}