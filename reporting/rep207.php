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
$page_security = 'SA_RR_REP'; // Modified by spyrax10 24 Jun 2022
// ----------------------------------------------------------------
/* $ Revision:	2.0 $
   Modified by:	Prog6
   date_:	2021-02-18
   Title:	Receiving Report Form (Supplier)
   
   
   
   
   
   
   Notes: To be fix/implemented ✔❎☐
   
	✔ Rename report header to "RECEIVING REPORT FORM" = done.
	✔ Change Table's column header to; Qty, Unit, Item/Description, Serial/Engine, Chassis, Unit Price, SubTotal. = done.
	✔ Display data to Table filtered and group by default(supplier) = done.
	
	☐  Add in header; RR#, Issue Date, Supplier Ref.#, Ref. date, PO#, PR# and Particulars = working...
	
	❎ Add in footer; Prepared by, Checked by and Received by = pending...
	❎ Add icon button view this RR in Goods receipt note = pending...
	
	

*/
// ----------------------------------------------------------------


$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

print_outstanding_GRN();

function getTransactions($fromsupp)
{
	$sql = "SELECT grn.id,
			order_no,
			grn.supplier_id,
			supplier.supp_name,
			supplier.address,
			item.item_code,
			item.description,
			qty_recd,
			quantity_inv,
			std_cost_unit,
			act_price,
			unit_price,
			
			smoves.lot_no,
        	smoves.chassis_no
			
		FROM ".TB_PREF."grn_items item,
			".TB_PREF."grn_batch grn,
			".TB_PREF."purch_order_details poline,
			".TB_PREF."suppliers supplier,
			".TB_PREF."stock_moves smoves
		WHERE grn.supplier_id=supplier.supplier_id
		AND grn.id = item.grn_batch_id
		AND item.po_detail_item = poline.po_detail_item
		AND smoves.trans_no = poline.order_no
		AND smoves.trans_id = poline.po_detail_item
		AND qty_recd-quantity_inv!=0";

	if ($fromsupp != ALL_TEXT)
		$sql .= " AND grn.supplier_id =".db_escape($fromsupp);

	$sql .= " ORDER BY grn.supplier_id,	grn.id";

    return db_query($sql, "No transactions were returned");
}

//----------------------------------------------------------------------------------------------------

function print_outstanding_GRN()
{
    global $path_to_root;

    $fromsupp = $_POST['PARAM_0'];
    $comments = $_POST['PARAM_1'];
	$orientation = $_POST['PARAM_2'];
	$destination = $_POST['PARAM_3'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'P');
	if ($fromsupp == ALL_TEXT)
		$from = _('All');
	else
	$from = get_supplier_name($fromsupp);
    $dec = user_price_dec();
	
	//           (s) (q) (u) (des)(sl) (ch)     (srp)  (subtl)
	$cols = array(0, 15, 55, 220, 340, 430, 430, 490, 555);
	
	

	$headers = array(_('Qty'), _('Unit'), _('Item') . '/' . _('Description'), _('Serial/Engine'), _('Chassis'), _(''),
		_('Unit Price'), _('SubTotal'));
		
		
		

	$aligns = array('center',	'center',	'left',	'left', 'left', 'right', 'right', 'right');
	

    $params =   array( 	0 => $comments, 1 => array('text' => _('Supplier'), 'from' => $from, 'to' => ''));		
	
    $rep = new FrontReport(_('Receiving Report Form'), "OutstandingGRN", user_pagesize(), 9, $orientation);
	
    if ($orientation == 'L') recalculate_cols($cols);
		
		
    //$rep->Font();
    $rep->RRInfo($params, $cols, $headers, $aligns);
    $rep->NewPage();
	$Tot_Val=0;
	$Supplier = '';
	$SuppTot_Val=0;
	$res = getTransactions($fromsupp);

	While ($GRNs = db_fetch($res))
	{		
		$dec2 = get_qty_dec($GRNs['item_code']);
		/*
		if ($Supplier != $GRNs['supplier_id'])
		{
			if ($Supplier != '')
			{				
				$rep->NewLine(2);
				$rep->TextCol(0, 7, _('Total'));
				$rep->AmountCol(7, 8, $SuppTot_Val, $dec);
				$rep->Line($rep->row - 2);
				$rep->NewLine(3);
				$SuppTot_Val = 0;				
			}
			$rep->TextCol(0, 6, $GRNs['supp_name'] . ' (' . $GRNs['address'] . ')');
			$Supplier = $GRNs['supplier_id'];
		}*/
		$rep->NewLine();
		$rep->AmountCol(0, 1, $GRNs['qty_recd'], $dec2); //Qty
		$rep->TextCol(1, 2, 'unit'); // unit
		$rep->TextCol(2, 3, $GRNs['item_code'] . '-' . $GRNs['description']); // Prod Code - Description
		$rep->TextCol(3, 4, $GRNs['lot_no']); // Serial/Engine
		$rep->TextCol(4, 5, $GRNs['chassis_no']); // Chassis
		$QtyOstg = $GRNs['qty_recd'] - $GRNs['quantity_inv'];
		$Value = $GRNs['qty_recd'] * $GRNs['act_price'];
		$rep->TextCol(5, 6, '');
		$rep->AmountCol(6, 7, $GRNs['act_price'], $dec);
		$rep->AmountCol(7, 8, $Value, $dec);
		$Tot_Val += $Value;
		$SuppTot_Val += $Value;
		$rep->NewLine(0, 1);
		
		/*
		$rep->NewLine();
		$rep->TextCol(0, 1, $GRNs['id']);
		$rep->TextCol(1, 2, $GRNs['order_no']);
		$rep->TextCol(2, 3, $GRNs['item_code'] . '-' . $GRNs['description']);
		$rep->AmountCol(3, 4, $GRNs['qty_recd'], $dec2);
		$rep->AmountCol(4, 5, $GRNs['quantity_inv'], $dec2);
		$QtyOstg = $GRNs['qty_recd'] - $GRNs['quantity_inv'];
		$Value = ($GRNs['qty_recd'] - $GRNs['quantity_inv']) * $GRNs['act_price'];
		$rep->AmountCol(5, 6, $QtyOstg, $dec2);
		$rep->AmountCol(6, 7, $GRNs['act_price'], $dec);
		$rep->AmountCol(7, 8, $Value, $dec);
		$Tot_Val += $Value;
		$SuppTot_Val += $Value;

		$rep->NewLine(0, 1);
		*/
	}
	/*
	if ($Supplier == '')
	{
		$rep->NewLine(2);
		$rep->TextCol(0, 7, _('Total'));
		$rep->AmountCol(7, 8, $SuppTot_Val, $dec);
		$rep->Line($rep->row - 2);
		$rep->NewLine(2);
		$SuppTot_Val = 0;		
	}
	*/
	
	$rep->NewLine(2);
	$rep->TextCol(0, 7, _('Total Amount'));
	$rep->AmountCol(7, 8, $Tot_Val, $dec);
	$rep->Line($rep->row - 2);
	$rep->NewLine();
	$rep->End();    
}

