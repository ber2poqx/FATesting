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
function add_proof_of_cash($tran_date, $one_thousand, $one_thousand_qty, $five_hundred, $five_hundred_qty, $two_hundred, $two_hundred_qty, $one_hundred, $one_hundred_qty, $fifty, $fifty_qty, $twenty, $twenty_qty, $ten, $ten_qty, $five, $five_qty, $one, $one_qty, 
	 $twenty_five_cent, $twenty_five_cent_qty, $ten_cent, $ten_cent_qty, $five_cent, $five_cent_qty, $comments)
{
	$sql = "INSERT INTO ".TB_PREF."proof_of_cash (tran_date, one_thousand, one_thousand_qty, five_hundred, five_hundred_qty, two_hundred, two_hundred_qty, one_hundred, one_hundred_qty, fifty, fifty_qty, twenty, twenty_qty, ten, ten_qty, five, five_qty, one, one_qty, twenty_five_cent, twenty_five_cent_qty, ten_cent, ten_cent_qty, five_cent, five_cent_qty, comments) 
		VALUES (".db_escape($tran_date).", 
				".db_escape($one_thousand).", 
				".db_escape($one_thousand_qty).", 
				".db_escape($five_hundred).", 
				".db_escape($five_hundred_qty).",
				".db_escape($two_hundred).", 
				".db_escape($two_hundred_qty).", 
				".db_escape($one_hundred).", 
				".db_escape($one_hundred_qty).",
				".db_escape($fifty).",
				".db_escape($fifty_qty).",
				".db_escape($twenty).",
				".db_escape($twenty_qty).",
				".db_escape($ten).",
				".db_escape($ten_qty).",
				".db_escape($five).",
				".db_escape($five_qty).",
				".db_escape($one).",
				".db_escape($one_qty).",
				".db_escape($twenty_five_cent).",
				".db_escape($twenty_five_cent_qty).",
				".db_escape($ten_cent).",
				".db_escape($ten_cent_qty).",
				".db_escape($five_cent).",
				".db_escape($five_cent_qty).",
				".db_escape($comments, true)."
			)";

	db_query($sql, "The proof of cash item record cannot be inserted");
}

function get_proof_cash($start, $limit, $search, $showall=false)
{
	$sql = "SELECT * FROM ".TB_PREF."proof_of_cash WHERE DATE_FORMAT(tran_date, '%m/%d/%Y') LIKE '%".$search."%' 
	ORDER BY tran_date DESC";

	$sqllimit = $sql ." limit $start, $limit";

	if ($showall) {
		$result = db_query($sql, "could not get all proof of cash");
	}else{
		$result = db_query($sqllimit, "could not get all proof of cash");	
	}

	return $result;
}

function delete_proof_of_cash($trans_no)
{
	$sql = "DELETE FROM ".TB_PREF."proof_of_cash 
			WHERE trans_no = ".db_escape($trans_no);

	db_query($sql, "could not remove to proof of cash");	
}

function check_cash_already_exist($tran_date)
{
	$sql = "SELECT COUNT(*) FROM ".TB_PREF."proof_of_cash WHERE tran_date = ".db_escape($tran_date);
	$result = db_query($sql, "check customer failed");
	$count =  db_fetch($result);

	return $count[0];
}

function update_proof_of_cash($tran_date, $one_thousand, $one_thousand_qty, $five_hundred, $five_hundred_qty, $two_hundred, 
	$two_hundred_qty, $one_hundred, $one_hundred_qty, $fifty, $fifty_qty, $twenty, $twenty_qty, $ten, $ten_qty, $five, $five_qty, 
	$one, $one_qty, $twenty_five_cent, $twenty_five_cent_qty, $ten_cent, $ten_cent_qty, $five_cent, $five_cent_qty, $comments)
{
	$sql = "UPDATE ".TB_PREF."proof_of_cash
	SET tran_date=" . db_escape($tran_date) . ", 
		one_thousand=" . db_escape($one_thousand) . ",
		one_thousand_qty=" . db_escape($one_thousand_qty) . ",
		five_hundred=" . db_escape($five_hundred) . ",
		five_hundred_qty=" . db_escape($five_hundred_qty) . ",
		two_hundred=" . db_escape($two_hundred) . ",
		two_hundred_qty=" . db_escape($two_hundred_qty) . ",
		one_hundred=" . db_escape($one_hundred) . ",
		one_hundred_qty=" . db_escape($one_hundred_qty) . ",
		fifty=" . db_escape($fifty) . ",
		fifty_qty=" . db_escape($fifty_qty) . ",
		twenty=" . db_escape($twenty) . ",
		twenty_qty=" . db_escape($twenty_qty) . ",
		ten=" . db_escape($ten) . ",
		ten_qty=" . db_escape($ten_qty) . ",
		five=" . db_escape($five) . ",
		five_qty=" . db_escape($five_qty) . ",
		one=" . db_escape($one) . ",
		one_qty=" . db_escape($one_qty) . ",
		twenty_five_cent=" . db_escape($twenty_five_cent) . ",
		twenty_five_cent_qty=" . db_escape($twenty_five_cent_qty) . ",
		ten_cent=" . db_escape($ten_cent) . ",
		ten_cent_qty=" . db_escape($ten_cent_qty) . ",
		five_cent=" . db_escape($five_cent) . ",
		five_cent_qty=" . db_escape($five_cent_qty) . ",
		comments=".db_escape($comments) ."
		WHERE tran_date = ".db_escape($tran_date);

	db_query($sql,"The customer could not be updated");
}

function add_collection_target($collect_date, $month, $percentage)
{
	
	$sql = "INSERT INTO ".TB_PREF."collection_target_percentage (collect_date, month, percentage) 
		VALUES (".db_escape($collect_date).", 
				".db_escape($month).", 
				".db_escape($percentage)."
			)";
			
	db_query($sql, "The collection target percentage cannot be inserted");
	
}

function check_target_date_already_exist($collect_date)
{
	$sql = "SELECT COUNT(YEAR(collect_date)) FROM ".TB_PREF."collection_target_percentage WHERE YEAR(collect_date) = ".db_escape($collect_date);
	$result = db_query($sql, "check target failed");
	$count =  db_fetch($result);

	return $count[0];
}

function get_collection_target_percent($start, $limit, $search, $showall=false)
{
	$sql = "SELECT YEAR(A.collect_date) AS YEAR,

			(SELECT B.percentage FROM collection_target_percentage B
			WHERE DATE_FORMAT(B.collect_date, '%Y') = DATE_FORMAT(A.collect_date, '%Y') AND B.month = '01-01' GROUP BY YEAR(B.collect_date))JANUARY,

			(SELECT C.percentage FROM collection_target_percentage C
			WHERE DATE_FORMAT(C.collect_date, '%Y') = DATE_FORMAT(A.collect_date, '%Y')AND C.month = '02-01')FEBRUARY,

			(SELECT C.percentage FROM collection_target_percentage C
			WHERE DATE_FORMAT(C.collect_date, '%Y') = DATE_FORMAT(A.collect_date, '%Y')AND C.month = '03-01')MARCH,

			(SELECT D.percentage FROM collection_target_percentage D
			WHERE DATE_FORMAT(D.collect_date, '%Y') = DATE_FORMAT(A.collect_date, '%Y')AND D.month = '04-01')APRIL,

			(SELECT D.percentage FROM collection_target_percentage D
			WHERE DATE_FORMAT(D.collect_date, '%Y') = DATE_FORMAT(A.collect_date, '%Y')AND D.month = '05-01')MAY,

			(SELECT D.percentage FROM collection_target_percentage D
			WHERE DATE_FORMAT(D.collect_date, '%Y') = DATE_FORMAT(A.collect_date, '%Y')AND D.month = '06-01')JUNE,

			(SELECT D.percentage FROM collection_target_percentage D
			WHERE DATE_FORMAT(D.collect_date, '%Y') = DATE_FORMAT(A.collect_date, '%Y')AND D.month = '07-01')JULY,

			(SELECT D.percentage FROM collection_target_percentage D
			WHERE DATE_FORMAT(D.collect_date, '%Y') = DATE_FORMAT(A.collect_date, '%Y')AND D.month = '08-01')AUGUST, 

			(SELECT D.percentage FROM collection_target_percentage D
			WHERE DATE_FORMAT(D.collect_date, '%Y') = DATE_FORMAT(A.collect_date, '%Y')AND D.month = '09-01')SEPTEMBER,

			(SELECT D.percentage FROM collection_target_percentage D
			WHERE DATE_FORMAT(D.collect_date, '%Y') = DATE_FORMAT(A.collect_date, '%Y')AND D.month = '10-01')OCTOBER,

			(SELECT D.percentage FROM collection_target_percentage D
			WHERE DATE_FORMAT(D.collect_date, '%Y') = DATE_FORMAT(A.collect_date, '%Y')AND D.month = '11-01')NOVEMBER, 

			(SELECT D.percentage FROM collection_target_percentage D
			WHERE DATE_FORMAT(D.collect_date, '%Y') = DATE_FORMAT(A.collect_date, '%Y')AND D.month = '12-01')DECEMBER

			FROM collection_target_percentage A
			WHERE YEAR(A.collect_date) LIKE '%".$search."%'
			GROUP BY YEAR(A.collect_date)
			ORDER BY YEAR DESC";

	$sqllimit = $sql ." limit $start, $limit";

	if ($showall) {
		$result = db_query($sql, "could not get all collection percentage");
	}else{
		$result = db_query($sqllimit, "could not get all collection percentage");
	}
	return $result;
}

function update_collection_percentage($collect_date, $month, $percentage)
{
	$sql = "UPDATE ".TB_PREF."collection_target_percentage
	SET collect_date=" . db_escape($collect_date) . ", 
		month=" . db_escape($month) . ",
		percentage=".db_escape($percentage) ."
		WHERE collect_date = ".db_escape($collect_date);

	db_query($sql,"The customer could not be updated");
}

function delete_collection_percent_target($collect_date)
{
	$sql = "DELETE FROM ".TB_PREF."collection_target_percentage 
			WHERE YEAR(collect_date) = ".db_escape($collect_date);

	db_query($sql, "could not remove to collection amount");	
}

function add_collection_amount($collect_date, $amount)
{
	$sql = "INSERT ".TB_PREF."INTO collection_target_amount (collect_date, amount)
			VALUES (".db_escape($collect_date).", 
					".db_escape($amount)."
			)";
	db_query($sql, "The collection target amount cannot be inserted");
}

function get_collection_target_amount($start, $limit, $search, $showall=false)
{
	$sql = "SELECT *, DATE_FORMAT(collect_date, '%Y-%m-%d') AS YEAR_DATE FROM collection_target_amount 
			WHERE DATE_FORMAT(collect_date, '%m/%d/%Y') LIKE '%".$search."%'
			GROUP BY collect_date ORDER BY collect_date DESC";

	$sqllimit = $sql ." limit $start, $limit";

	if ($showall) {
		$result = db_query($sql, "could not get all collection amount");
	}else{
		$result = db_query($sqllimit, "could not get all collection amount");
	}

	return $result;
}

function delete_collection_percent_amount($collect_date)
{
	$sql = "DELETE FROM ".TB_PREF."collection_target_amount 
			WHERE collect_date = ".db_escape($collect_date);

	db_query($sql, "could not remove to collection amount");	
}

function update_collection_amount($id, $collect_date, $amount)
{
	$sql = "UPDATE ".TB_PREF."collection_target_amount
	SET collect_date=" . db_escape($collect_date) . ", 
		amount=" . db_escape($amount) . "
		WHERE id = ".db_escape($id);

	db_query($sql,"The customer could not be updated");
}

function check_target_date_already_exist_v2($collect_date)
{
	$sql = "SELECT COUNT(DATE_FORMAT(collect_date, '%Y-&m')) FROM ".TB_PREF."collection_target_amount WHERE DATE_FORMAT(collect_date, '%Y-%m') = ".db_escape($collect_date);
	$result = db_query($sql, "check target failed");
	$count =  db_fetch($result);

	return $count[0];
}

