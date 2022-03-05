<?php
/**
 * Added by: spyrax10
 */

$path_to_root = "../..";
$page_security = 'SA_SI_UPDATE';

include_once($path_to_root . "/sales/includes/cart_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/ui/sales_invoice_cash_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_types_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/includes/cost_and_pricing.inc");

include_once($path_to_root . "/sales/includes/db/sales_installment_policy_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}
if (user_use_date_picker()) {
	$js .= get_js_date_picker();
}

if (isset($_GET['ModifyInvoice'])) {
	$_SESSION['page_title'] = sprintf(_("Modifying Sales Invoice #%d") ,$_GET['ModifyInvoice']);
	$help_context = "Modifying Sales Invoice";
}

page($_SESSION['page_title'], false, false, "", $js);

$trans_no = $_GET['ModifyInvoice'];

//-----------------------------------------------------------------------------
if (get_SI_status($trans_no) != "Pending") {
	display_error("This transaction already closed!");
	die();
}

if (get_post('co_maker')) {
	$_POST['co_maker'] = get_post('co_maker');
	$Ajax->activate('_page_body');
}

//-----------------------------------------------------------------------------
function get_SI_head($trans_no) {

	$sql = "SELECT DT.*, SO.ord_date, SO.from_stk_loc AS stock_loc, SO.category_id, SO.so_type, SO.salesman_id, SO.ship_via,
				SO.delivery_address, SO.contact_phone, SO.deliver_to, SO.delivery_date, SO.co_maker, SO.deliver_to, 
				SO.customer_ref, SO.comments, 
				DL.delivery_ref_no, DL.reference, DL.installmentplcy_id, DL.months_term, DL.rebate, DL.financing_rate,
				DL.firstdue_date, DL.maturity_date, DL.ar_amount, DL.amortization_amount, DL.warranty_code, DL.fsc_series,
				DL.co_maker, DL.discount_downpayment, DL.deferred_gross_profit, DL.profit_margin, DL.ref_no, DL.invoice_date, 
				DL.downpayment_amount, DL.total_amount, DL.payment_location, 
				DM.debtor_ref, DM.name
		
		FROM " . TB_PREF ."debtor_trans DT  
			INNER JOIN " . TB_PREF ."debtor_trans_details DET ON DT.trans_no = DET.debtor_trans_no AND DET.debtor_trans_type = 10
			INNER JOIN " . TB_PREF ."debtor_loans DL ON DT.trans_no = DL.trans_no
			INNER JOIN " . TB_PREF ."sales_orders SO ON DT.order_ = SO.order_no
			LEFT JOIN " . TB_PREF ."debtors_master DM ON DT.debtor_no = DM.debtor_no

		WHERE DT.type = 10 and DT.trans_no = " .db_escape($trans_no);
	
	$sql .= " GROUP BY DT.trans_no";

	$result = db_query($sql, "No transaction return for SI header! (spyrax10)");
	set_global_connection();
	return $result;
}

function get_SI_items($trans_no) {

	$sql = "SELECT DET.*, SO.category_id, SM.units
		FROM " . TB_PREF ."debtor_trans_details DET
			LEFT JOIN " . TB_PREF ."debtor_trans DT ON DET.debtor_trans_no = DT.trans_no AND DT.type = 10 
			LEFT JOIN " . TB_PREF ."sales_orders SO ON DT.order_ = SO.order_no
			LEFT JOIN " . TB_PREF ."stock_master SM ON DET.stock_id = SM.stock_id

		WHERE DET.debtor_trans_type = 10 AND DET.debtor_trans_no = " .db_escape($trans_no);

	$result = db_query($sql, "No transaction return for SI items! (spyrax10)");
	set_global_connection();
	return $result;
}

function update_SI($order_no, $trans_no, $ref_no, $sales_person, $sales_type, $co_maker) {

	set_global_connection();

	$sql = "UPDATE ".TB_PREF."sales_orders 
		SET salesman_id = '$sales_person', co_maker = '$co_maker', so_type = '$sales_type' 
		WHERE order_no =".db_escape($order_no);

	db_query($sql, "Cannot update sales_orders!");

	update_DL($trans_no, $ref_no, $co_maker);

	display_notification("Successfully Updated!");
}

function update_DL($trans_no, $ref_no, $co_maker) {

	$sql = "UPDATE ".TB_PREF."debtor_loans
		SET ref_no = '$ref_no', co_maker = '$co_maker' 
		WHERE trans_no =".db_escape($trans_no);
	
    db_query($sql, "Cannot update debtor_loans! (spyrax10)");
}

//-----------------------------------------------------------------------------

function get_SO_order($trans_no) {

	$sql = "SELECT A.order_ FROM " . TB_PREF . "debtor_trans A 
		WHERE A.trans_no=" . db_escape($trans_no);

	$result = db_query($sql, "Cant get SO trans_no! (spyrax10)");
    set_global_connection();
	$row = db_fetch_row($result);
	return $row[0];
}

function get_SI_status($trans_no) {

	$sql = "SELECT A.status FROM " . TB_PREF . "debtor_trans A 
		WHERE A.trans_no=" . db_escape($trans_no);

	$result = db_query($sql, "Cant get debtor_trans status! (spyrax10)");
    set_global_connection();
	$row = db_fetch_row($result);
	return $row[0];
}

function get_shipper_name($shipper_id) {

	$sql = "SELECT A.shipper_name FROM ".TB_PREF."shippers A
		WHERE A.shipper_id = ".db_escape($shipper_id);

	$result = db_query($sql, "Cant get shippers name! (spyrax10)");
    set_global_connection();
	$row = db_fetch_row($result);
	return $row[0];
}

//-----------------------------------------------------------------------------
function display_SI_head($trans_no) {

	start_outer_table(TABLESTYLE2, "width='80%'");

	$result = get_SI_head($trans_no);
	$customer = '';
	

	while ($row = db_fetch_assoc($result)) {

		$customer = $row['debtor_ref'] . " - " . $row['name'];

		table_section(1);
		label_row(_("Customer: "), $customer);
		label_row(_("Invoice #: "), $row['reference']);
		label_row(_("DR #: "), $row['delivery_ref_no']);
		text_row(_("Reference #: "), 'ref_no', $row['ref_no'], 16, 20);
		label_row(_("SO Date: "), sql2date($row['ord_date']));
		label_row(_("Invoice Date: "), sql2date($row['invoice_date']));
		sales_persons_list_row(_("Sales Person: "), 'salesman_id', $row['salesman_id']);
		saleorder_types_row(_("Sale Type: "), 'stype_id', $row['so_type']);
		
		if (!isset($_POST['co_maker'])) {
			$_POST['co_maker'] = $row['co_maker'];
		}

		sql_type_list(_("Co-maker:"), 'co_maker', 
			get_comaker($row['debtor_no'], '', true), 'comaker_id', 'comaker', 
			'label', '', true
		);

		table_section(2);
		label_row(_("WRC/EW Code: "), $row['warranty_code']);
		label_row(_("FSC Series: "), $row['fsc_series']);
		label_row(_("Category: "), get_category_name($row['category_id']));
		label_row(_("Downpayment: "), $row['downpayment_amount']);
		label_row(_("Total Discount: "), $row['discount_downpayment']);
		label_row(_("First Due Date: "), sql2date($row['firstdue_date']));
		label_row(_("Maturity Date: "), sql2date($row['maturity_date']));
		label_row(_("Total Unit Cost: "), price_format($row['total_amount']));
		label_row(_("Defered Gross Profit: "), price_format($row['deferred_gross_profit']));
		label_row(_("Profit Margin: "), $row['profit_margin']);
		label_row(_("Payment Location: "), $row['payment_location']);
		
		table_section(3);
		label_row(_("Payment Term: "), get_policy_name($row['installmentplcy_id'], $row['category_id']));
		label_row(_("Months Term: "), $row['months_term']);
		label_row(_("Rebate: "), price_format($row['rebate']));
		label_row(_("Financing Rate: "), $row['financing_rate']. "%");
		label_row(_("Due/Amortization: "), price_format($row['amortization_amount']));
		label_row(_("A/R Amount"), price_format($row['ar_amount']));

	}

	end_outer_table(1);
}

function display_SI_items($trans_no) {

	display_heading("Sales Invoice Items");

	div_start('si_items');
	start_table(TABLESTYLE2, "width='98%'");
	
	$result = get_SI_items($trans_no);
	$th = array(
		_("Item Code"), 
		_("Item Description"), 
		_("Color Code"),
		_("Color"), 
		_("Item Type"), 
		_("Quantity"),  
		_("Unit"), 
		_("Unit Price"),
		_("Unit Cost"),
		_("SMI"),
		_("Incentives"), 
		_("Sub Total"),
		_("Serial/Engine Num"),
		_("Chassis Num")
	);

	table_header($th);

	$total = 0;
	$k = 0;

	while ($row = db_fetch_assoc($result)) {

		alt_table_row_color($k);
		label_cell($row['stock_id']);
		label_cell($row['description']);
		label_cell($row['category_id'] == 14 ? $row['color_code'] : '');
		label_cell($row['category_id'] == 14 ? get_color_description($row['color_code'], $row['stock_id']) : '');
		label_cell($row['item_type']);
		label_cell($row['quantity'], "nowrap align='center'");
		label_cell($row['units'], "nowrap align='center'");
		label_cell(price_format($row['unit_price']), "nowrap align='center'");
		label_cell(price_format($row['standard_cost']), "nowrap align='center'");
		label_cell(price_format($row['smi']), "nowrap align='center'");
		label_cell(price_format($row['incentives']), "nowrap align='center'");
		label_cell(price_format($row['unit_price']), "nowrap align='center'");
		label_cell($row['lot_no'], "nowrap align='center'");
		label_cell($row['chassis_no'], "nowrap align='center'");

	}

	end_table();
    div_end();
}

function display_SI_delivery($trans_no) {

	display_heading("Delivery Details");

	start_outer_table(TABLESTYLE2, "width='80%'");

	$result = get_SI_head($trans_no);

	while ($row = db_fetch_assoc($result)) {

		table_section(1);
		label_row(_("Deliver from Location: "), _branch_name($row['stock_loc']));
		label_row(_("Deliver Date: "), sql2date($row['delivery_date']));
		label_row(_("Deliver To: "), $row['deliver_to']);
		label_row(_("Address: "), $row['delivery_address']);

		table_section(2);
		label_row(_("Contact Phone Number: "), $row['contact_phone']);
		label_row(_("Contact Reference"), $row['customer_ref']);
		label_row(_("Comments: "), $row['comments']);
		label_row(_("Shipping Company: "), get_shipper_name($row['ship_via']));
	}

	end_outer_table(1);
}

//-----------------------------------------------------------------------------

function can_proceed() {

	if (reference_exist(get_post('ref_no'), $_GET['ModifyInvoice'])) {
		display_error("Reference is already exists!");
		set_focus('ref_no');
		return false;
	}

	if (get_post('ref_no') == '' || get_post('co_maker') == '') {
		display_error("Empty Fields!");
		return false;
	}

	return true;
}

//-----------------------------------------------------------------------------

if (isset($_POST['Process']) && can_proceed()) {

	update_SI(get_SO_order($_GET['ModifyInvoice']), 
		$_GET['ModifyInvoice'], 
		get_post('ref_no'), 
		get_post('salesman_id'), 
		get_post('stype_id'), 
		get_post('co_maker')
	);
}

//-----------------------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

display_SI_head($trans_no);
display_SI_items($trans_no);
echo "<br>";
display_SI_delivery($trans_no);

submit_center_first('Process', 'Update Sales Invoice', '', 'default');

end_form();
end_page();