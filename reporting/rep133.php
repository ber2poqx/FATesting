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
$page_security = 'SA_WARRANTY_MONITORING'; 
// ----------------------------------------------------------------
// $ Revision:	7.0 $
// Creator:	Prog6
// date_:	2023-10-18
// Title:	Warranty Monitoring Report
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

print_warranty_monitoring_report();

function Headers_data()
{
	
}

function getTransactions($from, $to, $supplier, $type)
{
	$from = date2sql($from);
	$to = date2sql($to);
	
	$sql = "SELECT DISTINCT dtd.lot_no as `serial`
				, sup.supplier_id as supp_id
				, sup.supp_name
				, ib.name as supp_ref
				, dtd.debtor_trans_no
				, dtd.debtor_trans_type
				, dtd.stock_id
				, dtd.description as stock_desc
				, dtd.src_id
				, dtd.chassis_no as chassis
				, dtd.color_code as model
				, dt.trans_no
				, dt.type
				, dt.debtor_no
				, dt.branch_code
				, dt.tran_date
				, dt.order_ as SO_num1
				, dt.reference
				, so.order_no as SO_num2
				, so.waranty_code
				, so.fsc_series
				, stype.name as SalesType
				, dm.name
				, dm.address
				, dm.barangay
				, dm.province
				, munzip.municipality
				, munzip.zipcode
				, crmp.phone
				, dm.gender
				, dm.status
				, dm.age as `birthdate`
				, TIMESTAMPDIFF(year,dm.age, now()) as `age`
			FROM ".TB_PREF."`debtor_trans` dt
				LEFT JOIN ".TB_PREF."`sales_orders` so on dt.order_ = so.order_no
				LEFT JOIN ".TB_PREF."`debtor_trans_details` dtd ON dt.trans_no = dtd.debtor_trans_no AND dt.type = dtd.debtor_trans_type
				LEFT JOIN ".TB_PREF."`item_codes` ic ON dtd.stock_id = ic.stock_id
				LEFT JOIN ".TB_PREF."`item_brand` ib ON ic.brand = ib.id
				LEFT JOIN ".TB_PREF."`debtors_master` dm ON dt.debtor_no = dm.debtor_no
				LEFT JOIN ".TB_PREF."`municipality_zipcode` munzip ON dm.municipality = munzip.muni_code
				LEFT JOIN ".TB_PREF."`crm_persons` crmp ON dm.debtor_ref = crmp.ref
				LEFT JOIN ".TB_PREF."`suppliers` sup ON ib.name = sup.supp_ref
				LEFT JOIN ".TB_PREF."`sales_type` stype ON so.so_type = stype.id
			WHERE dt.type = '$type' AND so.category_id = '14' AND dt.tran_date BETWEEN '$from' AND '$to' AND sup.supplier_id = '$supplier'";


	return db_query($sql,"No transactions were returned");
}

function get_supplier_ref($supplier)
{
	$sql = "SELECT * FROM ".TB_PREF."`suppliers` WHERE supplier_id = '$supplier'";

	return db_query($sql,"No transactions were returned");
}



function print_warranty_monitoring_report()
{
	global $path_to_root;
	
	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$supplier = $_POST['PARAM_2'];
	$comments = $_POST['PARAM_3'];
	$orientation = $_POST['PARAM_4'];
	$destination = $_POST['PARAM_5'];

	$myrow_1 = get_company_prefs();
	$Company = $myrow_1['coy_name'];

	/*
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	*/

	include_once($path_to_root . "/reporting/includes/excel_report.inc");
				
	//$orientation = ($orientation ? 'L' : 'P');
	$orientation =  'L';
	
    $dec = user_price_dec();

	$sup_res = get_supplier_ref($supplier);
	$sup_details = db_fetch($sup_res);
	$sup_name_ref = $sup_details["supp_ref"];
	$sup_name_ful = $sup_details["supp_name"];
	//$Branch_current = $_SESSION["wa_current_user"]->company;	
	$Branch_current = $Company;
	$Branch = $db_connections[user_company()]["name"];
	
	$params = array(0 => $comments,
		1 => array('text' => _('Period'),'from' => $from, 'to' => $to),
		2 => array('text' => _('Supplier'), 'from' => $sup_name_ful, 'to' => ''));
			
		
	//Headers_data();
	if(strtoupper($sup_name_ref)=="SUZUKI")
	{
		########################################################################################	
			
		$cols = array(0,   100,			 200,  	     300,           400, 		
			500,          600,         700,              800,            900,         1000,      1100,   		
			1200,              1300);

		$headers = array(
			_('#'), 
			_('Dealer Code'),
			_('Name'), 
			_('Address'),
			_('Contact No.'),
			_('Invoice'),
			_('Invoice Date'),
			_('Model Name'),
			_('Engine #'),
			_('Frame #'),
			_('Branch'),
			_('EW Code'),
			_('Sales Type')
			);

		$aligns = array('left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 
		'left', 'left', 'left', 'left');
		#################################################################################################
	}

	$rep = new FrontReport(_('Warranty Monitoring - ').$sup_name_ref, _('Warranty Monitoring - ').$sup_name_ref, "legal", 9, $orientation);

    if ($orientation == 'L')
    	recalculate_cols($cols);
	
	$rep->fontSize -= 2;
    $rep->Info($params, $cols, $headers, $aligns, 
		null, null, null, true, '' , true);
    //$rep->SetHeaderType('SL_Summary_Header');
    $rep->SetHeaderType('PO_Header');
	$rep->NewPage();

	$counter = 0;
	$res = getTransactions($from, $to, $supplier, $type = ST_SALESINVOICE);

	if(strtoupper($sup_name_ref)=="SUZUKI")
	{
		While ($GRNs = db_fetch($res))
		{
			$counter = $counter + 1;
			//$dec2 = get_qty_dec($GRNs['Model']);

			$rep->NewLine();
			$rep->TextCol(0, 1, $counter);
			$rep->TextCol(1, 2, _('-'));
			$rep->TextCol(2, 3, $GRNs['name']);
			$rep->TextCol(3, 4, $GRNs['address']);
			$rep->TextCol(4, 5, $GRNs['phone']);
			$rep->TextCol(5, 6, $GRNs['reference']);
			$rep->TextCol(6, 7, $GRNs['tran_date']);
			$rep->TextCol(7, 8, $GRNs['model']);
			$rep->TextCol(8, 9, $GRNs['serial']);
			$rep->TextCol(9, 10, $GRNs['chassis']);
			$rep->TextCol(10, 11, $Branch_current);
			$rep->TextCol(11, 12, $GRNs['waranty_code']);
			$rep->TextCol(12, 13, $GRNs['SalesType']);

			$rep->NewLine(0, 1);
		}
	}
	

	//$rep->SetFooterType('compFooter');
	$rep->fontSize -= 2;
    $rep->End();
}
