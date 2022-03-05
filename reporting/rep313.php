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
$page_security = 'SA_ITEMSVALREP';
// ----------------------------------------------------------------
// $ Revision:  2.0 $
// Creator: RobertGwapo
// date:    2021-04-29
// Title:   Product Inquiry Printed Report
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

function get_stock_movement_inquiry($stock_id, $Inventory_type, $to)
{
    $to = date2sql($to);
    $sql = "SELECT SUM(move.qty), move.*, item_codes.description, 
            IM.name AS manu_name,
            IF(ISNULL(supplier.supplier_id), debtor.name, supplier.supp_name) name,
            
            CASE WHEN (SELECT SUM(moveinner.qty) as qtytest 
            FROM stock_moves moveinner 
            WHERE moveinner.stock_id = move.stock_id 
            AND moveinner.lot_no = move.lot_no 
            AND moveinner.type_out = move.type_out 
            AND moveinner.transno_out = move.transno_out
            GROUP BY moveinner.lot_no, moveinner.type_out, moveinner.transno_out LIMIT 1) > 0 THEN 'Available'
            ELSE 'Unavailable' END AS `TYPE`,

            CASE WHEN (SELECT SUM(moveinner.qty) as qtytest 
            FROM stock_moves moveinner 
            WHERE moveinner.stock_id = move.stock_id 
            AND moveinner.type_out = move.type_out 
            AND moveinner.transno_out = move.transno_out
            GROUP BY moveinner.type_out, moveinner.transno_out
            ORDER BY `move`.`tran_date`  DESC LIMIT 1) > 0 THEN 'Available'
            ELSE 'Unavailable' END AS `TYPE_SPGEN`,

            CASE WHEN move.item_type = 'new' THEN 'Brand New'
            ELSE 'Repo' END AS Inventory_type,

            CASE WHEN move.type = 0 THEN 'Journal Entry'
            WHEN move.type = 1 THEN 'Disbursement Entry'
            WHEN move.type = 2 THEN 'Receipts Entry'
            WHEN move.type = 4 THEN 'Funds Transfer'
            WHEN move.type = 10 THEN 'Sales Invoice'
            WHEN move.type = 11 THEN 'Customer Credit Note'
            WHEN move.type = 12 THEN 'Customer Payment'
            WHEN move.type = 13 THEN 'Delivery Note'
            WHEN move.type = 16 THEN 'Location Transfer'
            WHEN move.type = 17 THEN 'Inventory Adjustment'
            WHEN move.type = 18 THEN 'Purchase Order'
            WHEN move.type = 20 THEN 'Supplier Invoice'
            WHEN move.type = 21 THEN 'Supplier Credit Note'
            WHEN move.type = 22 THEN 'Supplier Payment'
            WHEN move.type = 25 THEN 'Purchase Order Delivery'
            WHEN move.type = 26 THEN 'Work Order'
            WHEN move.type = 28 THEN 'Work Order Issue'
            WHEN move.type = 29 THEN 'Work Order Production'
            WHEN move.type = 30 THEN 'Sales Order'
            WHEN move.type = 32 THEN 'Sales Quotation'
            WHEN move.type = 35 THEN 'Cost Update'
            WHEN move.type = 40 THEN 'Dimension'
            WHEN move.type = 41 THEN 'Customer'
            WHEN move.type = 42 THEN 'Supplier'
            WHEN move.type = 50 THEN 'Purchase Request'
            WHEN move.type = 51 THEN 'Receive Consignment'
            WHEN move.type = 52 THEN 'Merchandise Transfer'
            WHEN move.type = 53 THEN 'Receiving Report - Branch'
            WHEN move.type = 54 THEN 'Complimentary Items'
            WHEN move.type = 55 THEN 'Sales Return'
            WHEN move.type = 56 THEN 'Sales Invoice Term Modification'
            WHEN move.type = 57 THEN 'Sales Invoice Repossessed'
            WHEN move.type = 70 THEN 'A/R Installment Items Lending'
            WHEN move.type = 80 THEN 'Receive Report Repossessed Items'
            ELSE '' END AS TYPES,
        
        IF(move.type=" . ST_SUPPRECEIVE . ", grn.reference, IF(move.type=" . ST_CUSTCREDIT . ", cust_trans.reference, move.reference)) 
        reference

        FROM stock_moves move
        LEFT JOIN supp_trans credit ON credit.trans_no=move.trans_no AND credit.type=move.type
        LEFT JOIN grn_batch grn ON grn.id=move.trans_no AND move.type=" . ST_SUPPRECEIVE . "
        LEFT JOIN suppliers supplier ON IFNULL(grn.supplier_id, credit.supplier_id)=supplier.supplier_id
        LEFT JOIN debtor_trans cust_trans ON cust_trans.trans_no=move.trans_no AND cust_trans.type=move.type
        LEFT JOIN debtors_master debtor ON cust_trans.debtor_no=debtor.debtor_no
        LEFT JOIN item_codes ON item_codes.item_code = move.color_code
        LEFT JOIN stock_master SM ON SM.stock_id = move.stock_id
        LEFT JOIN item_manufacturer IM ON SM.manufacturer = IM.id

        WHERE move.tran_date <= '$to'
        AND move.stock_id = " . db_escape($stock_id);

        if ($Inventory_type == 'Brand New')
        $sql .= " AND move.item_type ='new'";

        if ($Inventory_type == 'Repo')
        $sql .= " AND move.item_type !='new'";
   
    $sql .= "GROUP BY move.lot_no, move.type_out, move.transno_out, 
    move.reference,move.item_type 
    ORDER BY move.tran_date, move.trans_id";


    return db_query($sql, "could not query stock moves");
}

function get_stock_id_item($stock_id)
{
    $sql = "SELECT stock_id FROM ".TB_PREF."stock_moves WHERE stock_id=".db_escape($stock_id);

    $result = db_query($sql, "could not get stock_id");

    $row = db_fetch_row($result);

    return $row[0];
}

function print_PO_Report()
{
    global $path_to_root, $SysPrefs;

    $to         = $_POST['PARAM_0'];
    $stock_id = $_POST['PARAM_1'];
    $Inventory_type = $_POST['PARAM_2'];
    $orientation= $_POST['PARAM_3'];
    $destination= $_POST['PARAM_4'];

    if ($destination)
        include_once($path_to_root . "/reporting/includes/excel_report.inc");
    else
        include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    if ($stock_id == '')
        $cust = _('Select Item First');
    else
        $cust = get_stock_id_item($stock_id);

    if ($Inventory_type == '') {
        $type = _('Select Inventory Type');
    } else {
        $type = $Inventory_type;

    }
        
        
    $orientation = 'L';
    
    $dec = user_price_dec();

    $params = array(0 => $comments,
        1 => array('text' => _('As Of Date'),'from' => $to, 'to' => ''),
        2 => array('text' => _('Item'), 'from' => $cust, 'to' => ''),
        3 => array('text' => _('Inventory Type'), 'from' => $type, 'to' => ''));   


    $cols = array(0, 73, 82, 145, 170, 232, 237, 316, 320, 396, 400, 
    435, 520, 525, 549, 575, 597, 635, 678, 715);

    $headers = array(
        _('Type'), 
        _(''),
        _('Reference'),
        _('Location'),
        _('Color'),
        _(''),
        _('Serial/Engine'),
        _(''),
        _('Chasis Number'),
        _(''),
        _('Date'),
        _('Detail'),
        _(''),
        _('Qty In'),
        _('Qty Out'),
        _('QtyOH'),
        _('Unit Cost In'),
        _('Unit Cost Out'),
        _('Balance'),
        _('Status'));

    $aligns = array('left', 'left', 'left', 'left', 'left', 'left', 
    'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 
    'left', 'left', 'left');

     $rep = new FrontReport(_('Product Inquiry Report'), "ProductInquiryReport", "Legal", 9, $orientation);
    if ($orientation == 'L')
        recalculate_cols($cols);

    
    $rep->fontSize -= 1;
    $rep->Info($params, $cols, $headers, $aligns, 
        null, null, null, true, true, true);
    $rep->SetHeaderType('COLLECTION_Header');
    $rep->NewPage();

   

    $total_in = $total_out = $total_onhand = $cost_in = 
    $cost_out = $total_balance = 0.0;

    $res = get_stock_movement_inquiry($stock_id, $Inventory_type, $to);
    $Collector_Name = $Coa_name = '';
    $_POST['fixed_asset'] = 1;

    while ($ACRBCR = db_fetch($res))
    {
        if (get_post('fixed_asset') == 0 && isset($fa_systypes_array[$ACRBCR["type"]]))
            $type_name = $fa_systypes_array[$ACRBCR["type"]];
        else
            $type_name = $systypes_array[$ACRBCR["type"]];
        
        if ($ACRBCR['name'] == '') {
            $manu = $ACRBCR['manu_name'];
        } else {
            $manu = $ACRBCR['name'];
        }

        if ($ACRBCR['lot_no'] != '') {
            $status = $ACRBCR['TYPE'];
        } else {
            $status = $ACRBCR['TYPE_SPGEN'];
        }

        if ($ACRBCR["qty"] > 0)
        {
            $qtyIn = number_format2($ACRBCR["qty"], $dec);
            $qtyOut = '';
            $cost_formatted_in = number_format2($ACRBCR["standard_cost"]);
            $cost_formatted_out = '';
            $total_in += $ACRBCR["qty"];
            $cost_in += $ACRBCR["standard_cost"];
        }
        else
        {
            $qtyOut = number_format2(-$ACRBCR["qty"], $dec);
            $qtyIn = '';
            $cost_formatted_out = number_format2($ACRBCR["standard_cost"]);
            $cost_formatted_in = '';
            $total_out += -$ACRBCR["qty"];
            $cost_out += $ACRBCR["standard_cost"];
        }
        $after_qty += $ACRBCR["qty"];
        if ($ACRBCR["qty"] < 0) {
            $after_cost -= $ACRBCR["standard_cost"] * -$ACRBCR["qty"];
        } else {
            $after_cost += $ACRBCR["standard_cost"] * $ACRBCR["qty"];
        }

        $rep->NewLine(1);
        $rep->TextCol(0, 1, $ACRBCR["TYPES"]);
        $rep->TextCol(1, 2, $ACRBCR[""]);
        $rep->TextCol(2, 3, $ACRBCR["reference"]);
        $rep->TextCol(3, 4, $ACRBCR['loc_code']);
        $rep->TextCol(4, 5, $ACRBCR['description']);
        $rep->TextCol(5, 6, $ACRBCR['']);
        $rep->TextCol(6, 7, $ACRBCR['lot_no']);
        $rep->TextCol(7, 8, $ACRBCR[""]);
        $rep->TextCol(8, 9, $ACRBCR['chassis_no']);
        $rep->TextCol(9, 10, $ACRBCR[""]);
        $rep->TextCol(10, 11, sql2date($ACRBCR['tran_date']));
        $rep->TextCol(11, 12, $manu); 
        $rep->TextCol(12, 13, $ACRBCR['']);
        $rep->TextCol(13, 14,  $qtyIn);  
        $rep->TextCol(14, 15, $qtyOut);
        $rep->AmountCol(15, 16, $after_qty, $dec);
        $rep->TextCol(16, 17, $cost_formatted_in);
        $rep->TextCol(17, 18, $cost_formatted_out);
        $rep->AmountCol(18, 19, $after_cost);
        $rep->TextCol(19, 20, $status);
        $rep->NewLine(1);


        $total_onhand = $after_qty;
        $total_balance = $after_cost;
    }
    
    $rep->NewLine(2);
    $rep->Line($rep->row - 2);
    $rep->Font('bold');
    $rep->fontSize += 0;    
    $rep->TextCol(11, 13, _('Grand Total:'));
    $rep->AmountCol(13, 14, $total_in, $dec);
    $rep->AmountCol(14, 15, $total_out, $dec);
    $rep->AmountCol(15, 16, $total_onhand, $dec);
    $rep->AmountCol(16, 17, $cost_in);
    $rep->AmountCol(17, 18, $cost_out);
    $rep->AmountCol(18, 19, $total_balance);
    $rep->End();
}