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
// ------------------------------------------------------------------------------
function add_pr(&$pr_obj)
{
	global $Refs;

	begin_transaction();
	hook_db_prewrite($pr_obj, ST_PURCHREQUEST);

     /*Insert to purchase request header record */
     $sql = "INSERT INTO purch_request (supplier_id, reference ,comments, pr_date , required_date) VALUES(";
	 $sql .= db_escape($pr_obj->supplier_id) . "," .
	 	 db_escape($pr_obj->reference) . ", " .
		 db_escape($pr_obj->Comments) . ",'" .
		 date2sql($pr_obj->orig_order_date) . "', '" .
	     date2sql($pr_obj->due_date). "')";

	db_query($sql, "The purchase request header record could not be inserted");

     /*Get the auto increment value of the request number created from the sql above */
	 $pr_obj->pr_no = db_insert_id();
	 

     /*Insert the purchase request detail records */
     foreach ($pr_obj->line_items as $line_no => $pr_line)
     {
		$sql = "INSERT INTO purch_request_details (pr_no, item_code, description, qty) VALUES (";
		$sql .= $pr_obj->pr_no . ", " . db_escape($pr_line->stock_id). "," .
		db_escape($pr_line->item_description). "," .
		db_escape($pr_line->quantity). ")";
		db_query($sql, "One of the purchase request detail records could not be inserted");
		$pr_obj->line_items[$line_no]->pr_detail_rec = db_insert_id();
     }

	$Refs->save(ST_PURCHREQUEST, $pr_obj->pr_no, $pr_obj->reference);

	add_audit_trail(ST_PURCHREQUEST, $pr_obj->pr_no, $pr_obj->orig_order_date);
	hook_db_postwrite($pr_obj, ST_PURCHREQUEST);
	commit_transaction();

	return $pr_obj->reference;
}

function get_sql_for_pr_search(
	// $from, $to, $supplier_id=ALL_TEXT, $location=ALL_TEXT, $request_number='', $stock_id=''
	)
{
	$sql = "SELECT
				a.reference,
				-- a.pr_no,
				b.supp_name,
				a.pr_date,
				a.required_date,
				a.comments
			FROM purch_request a 
			LEFT JOIN suppliers b
				ON b.supplier_id = a.supplier_id
			WHERE a.pr_no < 500
			ORDER BY a.pr_no DESC";

	return $sql;
}

//----------------------------------------------------------------------------------------

function read_pr_header($pr_no, &$request)
{
    	$sql = "SELECT
			a.*,
			b.supp_name,
			b.supplier_id,
			b.curr_code,
			b.tax_group_id,
			b.tax_included
			FROM purch_request a LEFT JOIN
			suppliers b ON b.supplier_id = a.supplier_id
			WHERE a.reference = ".db_escape($pr_no);

   	$result = db_query($sql, "The request cannot be retrieved");

	if (db_num_rows($result) == 1)
	{

      	$myrow = db_fetch($result);

      	$request->trans_type = ST_PURCHREQUEST;
		$request->pr_no = $myrow["pr_no"];
		  
		$request->set_supplier($myrow["supplier_id"], $myrow["supp_name"], $myrow["curr_code"],
			$myrow['tax_group_id'], $myrow["tax_included"]);

      	$request->Comments = $myrow["comments"];
		$request->reference = $myrow["reference"];
		$request->orig_order_date = $myrow["pr_date"];
		$request->due_date = $myrow["required_date"];
      	//$request->delivery_address = $myrow["delivery_address"];
      	//$request->alloc = $myrow["alloc"];
      	//$request->prep_amount = $myrow["prep_amount"];
      	//$request->prepayments = get_payments_for($request_no, ST_PURCHREQUEST, $myrow["supplier_id"]);

      	return true;
	}

	display_db_error("FATAL : duplicate purchase request found", "", true);
	return false;
}

//----------------------------------------------------------------------------------------

function read_pr_items($pr_no, &$request, $open_items_only=false)
{
	/*now populate the line po array with the purchase request details records */

	$sql = "SELECT 
			pr_detail_item, 
			pr_no, 
			item_code, 
			description, 
			qty 
			FROM purch_request_details 
			WHERE pr_no = ".db_escape($pr_no);

	$result = db_query($sql, "The lines on the purchase request cannot be retrieved");

    if (db_num_rows($result) > 0)
    {
		while ($myrow = db_fetch($result))
        {

			if ($request->pr_add_to_order($request->lines_on_order, $myrow["item_code"],
				$myrow["qty"],$myrow["description"],
            	null,null, null,
            	null, $myrow["qty"])) {
				$request->line_items[$request->lines_on_order-1]->pr_detail_rec = $myrow["pr_detail_item"];
			}
        } /* line po from purchase request details */
    } //end of checks on returned data set
}

//----------------------------------------------------------------------------------------

function read_pr($request_no, &$request, $open_items_only=false)
{
	$result = read_pr_header($request_no, $request);

	if ($result)
		read_pr_items($request->pr_no, $request, $open_items_only);
}

//----------------------------------------------------------------------------------------