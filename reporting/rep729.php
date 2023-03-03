<?php

/**
 * Name: Trial Balance (New)
 * Added by: spyrax10
 * Date Added: 6 Oct 2022
*/
$path_to_root = "..";
$page_security = 'SA_GLANALYTIC';

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/admin/db/fiscalyears_db.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------
print_transactions();
//----------------------------------------------------------------------------------------------------
function systype_name($type, $type_no) {
	global $systypes_array;

	if ($type == ST_INVADJUST) {
		if (is_invty_open_bal($type_no, '')) {
			return "Inventory Opening";
		}
		else {
			if (is_smo_repo($type_no, ST_INVADJUST)) {
				return "Inventory Adjustment (Repo)";
			}
			else {
				return "Inventory Adjustment (Brand New)";
			}
		}
	}
	else if ($type == ST_BANKDEPOSIT && is_RE_opening($type_no)) {
		return "Receipts Entry (Opening Balance)";
	}
	else {
		return $systypes_array[$type];
	}
	
}
function get_transactions($from) {
    
    $fdate = date2sql($from);
    set_global_connection();

    $sql = "SELECT GL.type, GL.type_no, 
    IFNULL(RF.reference, IFNULL(DT.reference, SM.reference)) AS reference,  
    GL.tran_date, GL.mcode, GL.master_file, GL.person_type_id, 
    IFNULL(TRUNCATE(DB.debit, 2), 0) AS debit, 
    IFNULL(TRUNCATE(CD.credit, 2), 0) AS credit 

    FROM gl_trans GL
    LEFT JOIN (
        SELECT ROUND(SUM(amount),2) AS debit, type, type_no FROM gl_trans WHERE amount > 0
        GROUP BY type, type_no
    ) DB ON GL.type = DB.type AND GL.type_no = DB.type_no
    LEFT JOIN (
        SELECT ROUND(SUM(amount),2) AS credit, type, type_no FROM gl_trans WHERE amount < 0
        GROUP BY type, type_no
    ) CD ON GL.type = CD.type AND GL.type_no = CD.type_no
    
    LEFT JOIN refs RF ON GL.type = RF.type AND GL.type_no = RF.id 
    LEFT JOIN debtor_trans DT ON GL.type = DT.type AND GL.type_no = DT.trans_no
    LEFT JOIN stock_moves SM ON GL.type = SM.type AND GL.type_no = SM.trans_no";

    $sql .= " WHERE GL.tran_date <= '$fdate'";
    $sql .= " GROUP BY GL.type, GL.type_no";


    return db_query($sql);
}
//----------------------------------------------------------------------------------------------------
function print_transactions() {
    global $path_to_root, $SysPrefs;

    $from = $_POST['PARAM_0'];
    $show_all = $_POST['PARAM_1'];
    $comments = $_POST['PARAM_2'];
    $destination = $_POST['PARAM_3'];

    if ($destination) {
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	}
	else {
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	}

    $dec = user_price_dec();
    $orientation = 'L';

    $headers = array(
        _("#"),
        _("Transaction Type"),
        //_("Reference"),
        _("Date"),
        _("Mcode"),
        _("Masterfile"),
        _("Debit"),
        _("Credit"),
        _("Discrepancy") 
    );

    $aligns = array('left', 'left', 'center', 'left', 'left', 'right', 'right', 'right');
    $cols = array(0, 40, 155, 210, 260, 340, 420, 500, 580, 0);


    // $aligns = array('left', 'left', 'left', 'center', 'left', 'left', 'right', 'right', 'right');
    // $cols = array(0, 40, 175, 250, 320, 370, 440, 490, 540, 590, 640);

    $params = array(0 => $comments,
        1 => array('text' => _('As of Date'), 'from' => $from, 'to' => '')
    );

    $rep = new FrontReport(_('Unbalanced Entries Report'), "UnbalancedEntries", 'LETTER', 9, $orientation);

    if ($orientation == 'L') {
        recalculate_cols($cols);
    }

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    
    if ($destination) {
        $rep->SetHeaderType('PO_Header');
    }
    else {
        $rep->SetHeaderType('PO_Header');     
    }

    $rep->NewPage();

    $result = get_transactions($from);
    $total_debit = $total_credit = $total_dis = 0;

    while ($data = db_fetch($result)) {

        $reference = str_replace(getCompDet('branch_code') . "-", "", $data['reference']);

        if ($data['type'] == ST_INVADJUST && is_invty_open_bal('', $data['reference'])) {
			$trim_ref = $reference . " (OB)";
		}
		else {
			$trim_ref = $reference;
		}

        $debit = $data['debit']; 
        $credit = $data['credit'];
        $total = $debit + $credit;

        $mcode = $data['mcode'] != null ? $data['mcode'] : get_subaccount_code($data['type'], $data['type_no']);
        $masterfile =  $data['master_file'] != null ? $data['master_file'] : get_subaccount_fullname($data['type'], $data['type_no']);

        if ($show_all) {
            $rep->fontSize -= .5;
            $rep->TextCol(0, 1, $data['type_no']);
            $rep->TextCol(1, 2, systype_name($data['type'], $data['type_no']));
            //$rep->TextCol(2, 3, $trim_ref);
            $rep->SetTextColor(0, 0, 255);
            $rep->TextCol(2, 3, sql2date($data['tran_date']));
            $rep->SetTextColor(0, 0, 0);
            $rep->TextCol(3, 4, $mcode);
            $rep->TextCol(4, 5, $masterfile);
            $rep->AmountCol(5, 6, $debit, $dec);
            $rep->AmountCol(6, 7, ABS($credit), $dec);
            $rep->AmountCol(7, 8, ABS($total), $dec);
            $rep->fontSize += .5;
            $rep->NewLine();

            $total_debit += $debit;
            $total_credit += ABS($credit);
            $total_dis += ABS($total);
        }
        else {
            if ($total != 0) {
                $rep->fontSize -= .5;
                $rep->TextCol(0, 1, $data['type_no']);
                $rep->TextCol(1, 2, systype_name($data['type'], $data['type_no']));
                //$rep->TextCol(2, 3, $trim_ref);
                $rep->SetTextColor(0, 0, 255);
                $rep->TextCol(2, 3, sql2date($data['tran_date']));
                $rep->SetTextColor(0, 0, 0);
                $rep->TextCol(3, 4, $mcode);
                $rep->TextCol(4, 5, $masterfile);
                $rep->AmountCol(5, 6, $debit, $dec);
                $rep->AmountCol(6, 7, ABS($credit), $dec);
                $rep->AmountCol(7, 8, ABS($total), $dec);
                $rep->fontSize += .5;
                $rep->NewLine();
        
                $total_debit += $debit;
                $total_credit += ABS($credit);
                $total_dis += ABS($total);                
            }
        }

    }

    $rep->Line($rep->row  - 4);
	$rep->Font();
	$rep->NewLine(3);
    $rep->Font('bold');
    $rep->fontSize += 2;
	$rep->TextCol(0, 2, _('Grand Total:'));
    $rep->Line($rep->row  - 4);
	$rep->fontSize -= 2;
    $rep->AmountCol(5, 6, $total_debit, $dec);
    $rep->AmountCol(6, 7, ABS($total_credit), $dec);
    $rep->AmountCol(7, 8, ABS($total_dis), $dec);
    $rep->Font();

    $rep->End();
}