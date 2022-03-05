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
//----------------------------------------------------------------------------------------

function get_customer_trans_details($debtor_trans_type, $debtor_trans_no, $view_trans = 0)
{
	if (!is_array($debtor_trans_no))
		$debtor_trans_no = array(0 => $debtor_trans_no);

	/* $sql = "SELECT line.*,
		line.unit_price+line.unit_tax AS FullUnitPrice,
        line.unit_price AS UnitPriceExTax,  
		line.description As StockDescription,
		item.units, item.mb_flag
		FROM "
			.TB_PREF."debtor_trans_details line,"
			.TB_PREF."stock_master item INNER JOIN"
			.TB_PREF."debtor_trans dt INNER JOIN ON dt.trans_no = line.debtor_trans_no LEFT JOIN"
			.TB_PREF."sales_order_details sod ON sod.order_no = dt.order_
		WHERE (";

	$tr=array();
	foreach ($debtor_trans_no as $trans_no)
		$tr[] = 'debtor_trans_no='.db_escape($trans_no);

	$sql .= implode(' OR ', $tr);


	$sql.=	") AND debtor_trans_type=".db_escape($debtor_trans_type)."
		AND item.stock_id=line.stock_id
		ORDER BY id"; */


	$sql = "SELECT line.*, line.unit_price+line.unit_tax AS FullUnitPrice,
        line.unit_price AS UnitPriceExTax,  
		line.standard_cost,
		line.description As StockDescription,
		item.units, item.mb_flag,
		line.lot_no,
		line.chassis_no,
		line.color_code,
		line.item_type,
		line.discount1,
		line.discount2,
		line.color_code AS color,
		icode.category_id AS catt,
		icode.color AS pnpcolor

		FROM 
		" . TB_PREF . "debtor_trans_details line INNER JOIN 
		" . TB_PREF . "stock_master item ON item.stock_id=line.stock_id INNER JOIN 
		" . TB_PREF . "debtor_trans dt ON dt.trans_no = line.debtor_trans_no AND dt.type =" . db_escape($debtor_trans_type) . " INNER JOIN 
		" . TB_PREF . "item_codes icode ON line.stock_id = icode.stock_id
        WHERE (";
	// line.debtor_trans_no=5 and line.debtor_trans_type=13 order by id";

	$tr = array();
	foreach ($debtor_trans_no as $trans_no)
		$tr[] = 'debtor_trans_no=' . db_escape($trans_no);

	$sql .= implode(' OR ', $tr);


	$sql .=	") AND debtor_trans_type=" . db_escape($debtor_trans_type) . " ";

	if ($debtor_trans_type == ST_SALESRETURN) {
		$sql .= " AND (SELECT 
				t1.id 
			FROM 
				returned_units t1 
			WHERE 
				t1.debtor_trans_details_id = line.id
			) is null ";
	}

	//Modified by Prog6 (06/18/2021)
	// if ($view_trans == 0)
	// 	$sql .= "AND line.item_type = 'Regular'"; 

	/* Modified by Ronelle 11/11/2021 */
	$sql .= "GROUP BY line.id ORDER BY id";

	return db_query($sql, "The debtor transaction detail could not be queried");
}
//----------------------------------------------------------------------------------------

function void_customer_trans_details($type, $type_no)
{
	$sql = "UPDATE " . TB_PREF . "debtor_trans_details SET quantity=0, unit_price=0,
		unit_tax=0, discount_percent=0, standard_cost=0, src_id=0
		WHERE debtor_trans_no=" . db_escape($type_no) . "
		AND debtor_trans_type=" . db_escape($type);

	db_query($sql, "The debtor transaction details could not be voided");

	// clear the stock move items
	void_stock_move($type, $type_no);
}
//----------------------------------------------------------------------------------------

function write_customer_trans_detail_item(
	$debtor_trans_type,
	$debtor_trans_no,
	$stock_id,
	$description,
	$quantity,
	$unit_price,
	$unit_tax,
	$discount_percent,
	$std_cost,
	$src_id,
	$line_id = 0,
	$serial_no = "",
	$chassis_no = "",
	$color_code = "",
	$item_type = "Regular",
	$discount1 = 0,
	$discount2 = 0,
	$smi = 0,
	$incentives = 0
) {
	if ($line_id != 0)
		$sql = "UPDATE " . TB_PREF . "debtor_trans_details SET
			stock_id=" . db_escape($stock_id) . ",
			description=" . db_escape($description) . ",
			quantity=$quantity,
			unit_price=$unit_price,
			unit_tax=$unit_tax,
			discount_percent=$discount_percent,
			standard_cost=$std_cost,
			src_id=" . db_escape($src_id) . ",
			lot_no=" . db_escape($serial_no) . ",
			chassis_no=" . db_escape($chassis_no) . ",
			color_code=" . db_escape($color_code) . ",
			item_type=" . db_escape($item_type) . ",
			discount1=$discount1,
			discount2=$discount2,
			smi=$smi,
			incentives=$incentives
			WHERE id=" . db_escape($line_id);
	else
		$sql = "INSERT INTO " . TB_PREF . "debtor_trans_details (debtor_trans_no,
				debtor_trans_type, stock_id, description, quantity, unit_price,
				unit_tax, discount_percent, standard_cost, src_id, lot_no, chassis_no,
				color_code, item_type, discount1, discount2,
				smi, incentives)
			VALUES (" . db_escape($debtor_trans_no) . ", " . db_escape($debtor_trans_type) . ", " . db_escape($stock_id) .
			", " . db_escape($description) . ",
				$quantity, $unit_price, $unit_tax, 
				$discount_percent, $std_cost," . db_escape($src_id) . ",
				" . db_escape($serial_no) . ", " . db_escape($chassis_no) . ",
				" . db_escape($color_code) . ", " . db_escape($item_type) . ",
				$discount1, $discount2,
				$smi, $incentives)";

	db_query($sql, "The debtor transaction detail could not be written");
}
