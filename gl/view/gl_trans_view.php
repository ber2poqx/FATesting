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
/**
 * Modified by: spyrax10
 * Date Modified: 01-12-2022
 */

$page_security = 'SA_GLTRANSVIEW';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

simple_page_mode(true);

$js = "";
if ($SysPrefs->use_popup_windows)
    $js .= get_js_open_window(900, 1200);
if (user_use_date_picker())
    $js .= get_js_date_picker();

page(_($help_context = "General Ledger Transaction Details"), true, false, "", $js);


if (!isset($_GET['type_id']) || !isset($_GET['trans_no'])) 
{ /*Script was not passed the correct parameters */

	display_note(_("The script must be called with a valid transaction type and transaction number to review the general ledger postings for."));
	end_page();
}

function display_gl_heading($myrow)
{
	global $systypes_array;

	//Modified by spyrax10 26 Feb 2022
	if ($_GET['type_id'] == ST_INVADJUST) {
		if (is_invty_open_bal($_GET['trans_no'],'')) {
			$trans_name = "Inventory Opening";
		}
		else {
			if (is_smo_repo($_GET['trans_no'], ST_INVADJUST)) {
				$trans_name = "Inventory Adjustment (Repo)";
			}
			else {
				$trans_name = "Inventory Adjustment (Brand New)";
			}
		}
	}
	else if ($_GET['type_id'] == ST_BANKDEPOSIT && is_RE_opening($_GET['trans_no'])) {
		$trans_name = "Receipts Entry (Opening Balance)";
	}
	else {
		$trans_name = $systypes_array[$_GET['type_id']];
	}
	//
	
	$journal = $_GET['type_id'] == ST_JOURNAL;
	$merchandisetransfer = $_GET['type_id'] == ST_MERCHANDISETRANSFER;
	//Added by Robert//
	$merchandisetransferrepo = $_GET['type_id'] == ST_MERCHANDISETRANSFERREPO;
	$complimentary = $_GET['type_id'] == ST_COMPLIMENTARYITEM;
	//
	$rrbranch = $_GET['type_id'] == ST_RRBRANCH;
	
    start_table(TABLESTYLE, "width='95%'");

    $th = array(_("General Ledger Transaction Details"), _("Reference"),
    	_("Transaction Date"), _("GL #"));

	if ($_GET['type_id'] == ST_JOURNAL) {
		array_insert($th, 3, array(_("Document Date"), _("Event Date")));
	}
	else {
		array_insert($th, 3, array(_("Counterparty")));	
	}
	
	if($myrow['supp_reference']) {
		array_insert($th, 2, array(_("Supplier Reference")));
	}

    table_header($th);	
    start_row();	
    label_cell("$trans_name #" . $_GET['trans_no']);
    label_cell($myrow["reference"], "align='center'");

	if ($myrow['supp_reference']){
		label_cell($myrow["supp_reference"], "align='center'");
	}

	if ($_GET['type_id'] == ST_INVADJUST && is_invty_open_bal('', $myrow["reference"])) {
		label_cell(sql2date($myrow["ob_date"]), "align='center'");
	}
	else {
		label_cell(sql2date($myrow["doc_date"]), "align='center'");
	}

	if ($journal)
	{
		$header = get_journal($myrow['type'], $_GET['trans_no']);
		label_cell($header["doc_date"] == '0000-00-00' ? '-' : sql2date($header["doc_date"]), "align='center'");
		label_cell($header["event_date"] == '0000-00-00' ? '-' : sql2date($header["event_date"]), "align='center'");
	}elseif($merchandisetransfer){
	    $mt_header = get_mt_header($myrow["reference"]);
	    label_cell(get_db_location_name($mt_header["mt_header_tolocation"]));
	}elseif($rrbranch){
	    $mt_rrbranch_header = get_mt_rrbranch_header($myrow["reference"]);
	    label_cell(get_db_location_name($mt_rrbranch_header["mt_header_fromlocation"]));   
	}elseif($merchandisetransferrepo) {
		$mt_header = get_mt_header($myrow["reference"]);
	    label_cell(get_db_location_name($mt_header["mt_header_tolocation"]));
	} else
		label_cell(get_counterparty_name($_GET['type_id'],$_GET['trans_no']));
	label_cell( get_journal_number($myrow['type'], $_GET['trans_no']), "align='center'");
	end_row();

	start_row();
	label_cells(_('Entered By'), $myrow["real_name"], "class='tableheader2'", "colspan=" .
		 ($journal ? ($header['rate']==1 ? '3':'1'):'6'));
	if ($journal)
	{
		if ($header['rate'] != 1)
			label_cells(_('Exchange rate'), $header["rate"].' ', "class='tableheader2'");
		label_cells(_('Source document'), $header["source_ref"], "class='tableheader2'");
	}
	end_row();
	comments_display_row($_GET['type_id'], $_GET['trans_no']);
    end_table(1);
}


function _gl_details() {

	/*show a table of the transactions returned by the sql */
	/* Added by Ronelle 2/22/2021 */
	$receipts = $_GET['type_id'] == ST_BANKDEPOSIT;
	$disbursement = $_GET['type_id'] == ST_BANKPAYMENT;
	/* */

	$journal = $_GET['type_id'] == ST_JOURNAL;
	$merchandisetransfer = $_GET['type_id'] == ST_MERCHANDISETRANSFER;
	//Added by Robert//
	$merchandisetransferrepo = $_GET['type_id'] == ST_MERCHANDISETRANSFERREPO;
	$complimentary = $_GET['type_id'] == ST_COMPLIMENTARYITEM;
	//
	$rrbranch = $_GET['type_id'] == ST_RRBRANCH;
	//Added by spyrax10
	$adjustment = $_GET['type_id'] == ST_INVADJUST;
	$invoice = $_GET['type_id'] == ST_SALESINVOICE;
	$sales_return = $_GET['type_id'] == ST_SALESRETURN;
	$sup_recv = $_GET['type_id'] == ST_SUPPRECEIVE;
	$remittance = $_GET['type_id'] == ST_REMITTANCE;
	//
	//Added by albert 
	$termmode = $_GET['type_id'] == ST_SITERMMOD;
	$restructured = $_GET['type_id'] == ST_RESTRUCTURED;
	//
	//jr
	$payments = $_GET['type_id'] == ST_CUSTPAYMENT;
	$rrepo = $_GET['type_id'] == ST_RRREPO;
	$arlending = $_GET['type_id'] == ST_ARINVCINSTLITM;

	$dim = get_company_pref('use_dimension');

	if ($dim == 2) {
		$th = array(_("ID"), //Added by spyrax10
			_("Journal Date"), _("Account Code"), _("Account Name"), _("Dimension")." 1", _("Dimension")." 2",
			_("Debit"), _("Credit"), _("Memo"), 
			_("") //Added by spyrax10
		);
	}	
	elseif ($dim == 1) {
		$th = array(_("ID"), //Added by spyrax10
			_("Journal Date"), _("Account Code"), _("Account Name"), _("Dimension"),
			_("Debit"), _("Credit"), _("Memo"), 
			_("") //Added by spyrax10
		);
	}
	else {
		$th = array(_("ID"), //Added by spyrax10
			_("Journal Date"), _("Account Code"), _("Account Name"), _("MCode"), _("Masterfile"),
			_("Debit"), _("Credit"), _("Memo"), 
			_("") //Added by spyrax10
		);
	}		
		

	$k = 0; //row colour counter
	$heading_shown = false;
	$result = get_gl_trans($_GET['type_id'], $_GET['trans_no']);
	$credit = $debit = 0;
	while ($myrow = db_fetch($result)) 
	{
		if ($myrow['amount'] == 0) continue;
		if (!$heading_shown) {
			display_gl_heading($myrow);
			start_table(TABLESTYLE, "width='95%'");
			table_header($th);
			$heading_shown = true;
		}

		//Modified by spyrax10 24 Jun 2022
		if ($myrow['void_entry'] == 1) {
			start_row("class='overduebg'");
		}
		else {
			alt_table_row_color($k);
		}
		//

		//Added by spyrax10
		label_cell($myrow['counter']);
		//

		$counterpartyname = get_subaccount_name($myrow["account"], $myrow["person_id"]);
		$counterparty_id = $counterpartyname ? sprintf(' %05d', $myrow["person_id"]) : '';

    	label_cell(sql2date($myrow['tran_date']));
    	label_cell($myrow['account']);
		label_cell($myrow['account_name'] . ($counterpartyname ? ': '.$counterpartyname : ''));
		
		if ($dim >= 1) {
			label_cell(get_dimension_string($myrow['dimension_id'], true));
		}
			
		if ($dim > 1) {
			label_cell(get_dimension_string($myrow['dimension2_id'], true));
		}
		
		/* Modified by Ronelle 2/22/2021 */
		//Modifed by Herald 12/02/2021
		if ($receipts || $disbursement || $journal || $adjustment) {
			label_cell($myrow['mcode']);
			label_cell($myrow['master_file']);
		}
		else if($merchandisetransfer) {//Added by Herald 12/02/2021 for Merchandise transfer header
	    	$mt_header = get_mt_header($myrow["reference"]);
	    	label_cell($mt_header["mt_header_tolocation"]);
	    	label_cell(get_db_location_name($mt_header["mt_header_tolocation"]));
	    
		}
		elseif($merchandisetransferrepo) {//Added by Robert 02/26/2022 for Merchandise transfer Repo
			$mt_header = get_mt_header($myrow["reference"]);
	    	label_cell($mt_header["mt_header_tolocation"]);
	    	label_cell(get_db_location_name($mt_header["mt_header_tolocation"]));
		}
		elseif($complimentary) {//Added by Robert 03/02/2022 for Complimentary
			label_cell($myrow['mcode']);
			label_cell($myrow['master_file']);
		}
		//Modified by spyrax10 26 Aug 2022
		// else if($rrbranch){//Added by Herald 12/02/2021 for RR Branch
	    // 	$rrbranch_header = get_mt_rrbranch_header($myrow["reference"]);
	    // 	label_cell($rrbranch_header["mt_header_fromlocation"]);
	    // 	label_cell(get_db_location_name($rrbranch_header["mt_header_fromlocation"]));
		// }	
		//Added by spyrax10
		else if ($invoice || $sales_return || $sup_recv || $remittance || $rrbranch) {
			label_cell($myrow['mcode'] != null ? $myrow['mcode'] : 
				get_subaccount_code($_GET['type_id'], $_GET['trans_no'])
			);
			label_cell($myrow['master_file'] != null ? $myrow['master_file'] : 
				get_subaccount_fullname($_GET['type_id'], $_GET['trans_no'])
			);
		}
		//Added by Albert
		//modify jr 03/21/22
		else if ($termmode|| $restructured || $payments || $rrepo || $arlending) {
			label_cell($myrow['mcode'] != null ? $myrow['mcode'] : 
			get_subaccount_code($_GET['type_id'],$_GET['trans_no']));
			label_cell($myrow['master_file'] != null ? $myrow['master_file'] : 
			get_subaccount_fullname($_GET['type_id'],$_GET['trans_no']));
		}
		//
		else {
			label_cell(get_subaccount_code($_GET['type_id'],$_GET['trans_no']));
			label_cell(get_subaccount_fullname($_GET['type_id'],$_GET['trans_no']));
		}
		/* */
		display_debit_or_credit_cells($myrow['amount']);
		label_cell($myrow['memo_']);

		$void_entry = get_voided_entry($_GET['type_id'], $_GET['trans_no']);
		if ($_GET['edit_line'] == 1 && $void_entry['void_status'] != 'Voided') {
			edit_button_cell("EditGL".$myrow['counter'], _("Edit"), _('Edit Line Memo'));
		}
	
		end_row();
    	if ($myrow['amount'] > 0 ) {
			$debit += $myrow['amount'];
		} 
    	
    	else {
			$credit += $myrow['amount'];
		}
    	
	} // end of while loop

	if ($heading_shown) {
    	start_row("class='inquirybg' style='font-weight:bold'");
    	label_cell(_("Total"), "colspan=6");
    
		if ($dim >= 1) {
			label_cell('');
		}
        
    	if ($dim > 1) {
			label_cell('');
		}
        
    	amount_cell($debit);
    	amount_cell(-$credit);
    	label_cell('');
    	end_row();
		end_table(1);
	}

	
	if (db_num_rows($result) == 0)
	{
    	echo "<p><center>" . _("No general ledger transactions have been created for") . " " .$systypes_array[$_GET['type_id']]." " . _("number") . " " . $_GET['trans_no'] . "</center></p><br><br>";
		end_page(true);
		exit;
	}

} //end of display_gl_details

//Added by spyrax10
//-----------------------------------------
function get_GLmemo_by_counter($id) {
	
	set_global_connection();

	$sql = "SELECT memo_ FROM ".TB_PREF."gl_trans 
		WHERE counter = " .db_escape($id);
	
	$res = db_query($sql, 'Cant get gl_trans line memo!');
	$row = db_fetch_row($res);
	return $row[0];
}

function can_proceed() {

    if (!is_date_in_fiscalyear(Today())) {
        display_error(_("The Entered Date is OUT of FISCAL YEAR or is CLOSED for further data entry!"));
		return false;
    }

    return true;
}

function update_gl_memo($id, $memo = '') {

	global $Ajax;
    set_global_connection();

	$sql = "UPDATE ".TB_PREF."gl_trans SET memo_ = '$memo' 
		WHERE counter = " .db_escape($id);

	db_query($sql, "Cannot update gl_trans GL! (spyrax10)");

	add_audit_trail($_GET['type_id'], $_GET['trans_no'], Today(), "Update memo of gl_trans. counter #: " .$id);

	$Ajax->activate('_page_body');
	display_notification(_("Counter ID #" . $id . " sucessfully updated!"));
	
}

function display_edit_line($id) {

	hidden('counter_id', $id);

	display_heading(_('Updating Line Memo for ID #' . $id));
	
	echo "<br>";
    div_start("update_gl");

	echo "<center>";
	textarea_row(null, 'memo_', null, 50, 3);
	echo "/<center";

	echo "<br>";
	end_table(1);
    div_end();
    submit_add_or_update_center(false, '', 'both', false, false);
}

//-----------------------------------------------------------------------------
if (isset($_POST['UPDATE_ITEM']) && can_proceed()) {
	update_gl_memo(get_post('counter_id'), 
	get_post('memo_') != '' ? get_post('memo_') : get_GLmemo_by_counter(get_post('counter_id')) );
}

//-----------------------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

_gl_details();

//-----------------------------------------------------------------------------
$edit_id = find_submit('EditGL');

if ($edit_id != -1) {
	$id = get_post('selected_id', find_submit('EditGL'));
	display_edit_line($id);
}

//-----------------------------------------------------------------------------

is_voided_display($_GET['type_id'], $_GET['trans_no'], _("This transaction has been voided."));

end_page(true, false, false, $_GET['type_id'], $_GET['trans_no']);
