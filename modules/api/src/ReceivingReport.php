<?php

namespace FAAPI;

$path_to_root = "../..";


class ReceivingReport
{
    // Get Items
    public function get($rest)
    {
        $req = $rest->request();

        $page = $req->get("page");

        if ($page == null) {
            $this->receiving_report_all(null);
        } else {
            // If page = 1 the value will be 0, if page = 2 the value will be 1, ...
            $from = --$page * RESULTS_PER_PAGE;
            $this->receiving_report_all($from);
        }
    }

    public function getBySupplierId($rest, $supplier_id, $search = "")
    {
        $searchParam = $search;
        $sql = "SELECT
                    a.id as trans_id,
                    c.supp_name as supplier_name,
                    c.supplier_id,
                    a.purch_order_no,
                    a.reference,
                    a.delivery_date,
                    a.suppl_ref_no,
                    a.suppl_ref_date,
                    a.suppl_served_by,
                    a.grn_remarks,
                    a.category_id,
                    a.apinvoice_no_and_poack_no,
                    CASE
                    	WHEN 
                        	SUM(b.qty_recd) > SUM(d.qty_invoiced)
                        THEN
                        	'Open'
                        ELSE
                        	'Closed'
                    END as status
                FROM
                    grn_batch a
                INNER JOIN
                    grn_items b
                ON
                    b.grn_batch_id = a.id
                LEFT JOIN
                    suppliers c
                ON
                    c.supplier_id = a.supplier_id
                LEFT JOIN
                	purch_order_details d
                ON
                	d.po_detail_item = b.po_detail_item
                WHERE
                    a.supplier_id = " . $supplier_id . "
                AND
                    (a.reference LIKE '%" . $searchParam . "%')
                GROUP BY
                    a.id";
        $query = db_query($sql, "error");

        $info = array();

        while ($data = db_fetch($query, "error")) {
            $info[] = array(
                'trans_id' => $data['trans_id'],
                'supplier_name' => $data['supplier_name'],
                'supplier_sap_code' => get_supplier_sap_code($data['supplier_id']),
                'purch_order_no' => $data['purch_order_no'],
                'reference' => $data['reference'],
                'delivery_date' => $data['delivery_date'],
                'suppl_ref_no' => $data['suppl_ref_no'],
                'suppl_ref_date' => $data['suppl_ref_date'],
                'suppl_served_by' => $data['suppl_served_by'],
                'grn_remarks' => $data['grn_remarks'],
                'category' => get_category_name($data['category_id']),
                'status' => $data['status'],
                'ap_ref_no' => $data['apinvoice_no_and_poack_no']
            );
        }

        api_success_response(json_encode($info));
    }

    private function receiving_report_all($from = null)
    {
        $sql = "SELECT
                    a.id as trans_id,
                    c.supp_name as supplier_name,
                    c.SAPcode as supplier_sap_code,
                    a.purch_order_no,
                    a.reference,
                    a.delivery_date,
                    a.suppl_ref_no,
                    a.suppl_ref_date,
                    a.suppl_served_by,
                    a.grn_remarks,
                    a.category_id,
                    CASE
                    	WHEN 
                        	SUM(b.qty_recd) > SUM(d.qty_invoiced)
                        THEN
                        	'Open'
                        ELSE
                        	'Closed'
                    END as status
                FROM
                    grn_batch a
                INNER JOIN
                    grn_items b
                ON
                    b.grn_batch_id = a.id
                LEFT JOIN
                    suppliers c
                ON
                    c.supplier_id = a.supplier_id
                GROUP BY
                    a.id";
        $query = db_query($sql, "error");

        $info = array();

        while ($data = db_fetch($query, "error")) {
            $info[] = array(
                'trans_id' => $data['trans_id'],
                'supplier_name' => $data['supplier_name'],
                'supplier_sap_code' => $data['supplier_sap_code'],
                'purch_order_no' => $data['purch_order_no'],
                'reference' => $data['reference'],
                'delivery_date' => $data['delivery_date'],
                'suppl_ref_no' => $data['suppl_ref_no'],
                'suppl_ref_date' => $data['suppl_ref_date'],
                'suppl_served_by' => $data['suppl_served_by'],
                'grn_remarks' => $data['grn_remarks'],
                'category' => get_category_name($data['category_id']),
                'status' => $data['status']
            );
        }

        api_success_response(json_encode($info));
    }

    public function getReceivingReportDetails($rest, $id)
    {
        $sql = "SELECT
                    a.id,
                    a.grn_batch_id,
                    a.item_code,
                    a.description,
                    a.color_code,
                    (a.qty_recd - b.qty_invoiced) as quantity,
                    b.std_cost_unit,
                    c.category_id,
                    (SELECT 
                        t1.serialised 
                    FROM 
                        stock_master t1
                    WHERE t1.stock_id = a.item_code
                    ) as serialized,
                    a.po_detail_item
                FROM
                    grn_items a
                LEFT JOIN
                    purch_order_details b
                ON
                    b.po_detail_item = a.po_detail_item
                INNER JOIN
                    grn_batch c
                ON
                    c.id = a.grn_batch_id
                WHERE
                    a.grn_batch_id = $id
                AND
                    (a.qty_recd - b.qty_invoiced) > 0";

        $query = db_query($sql, "error");

        $info = array();

        while ($data = db_fetch($query, "error")) {

            $serials = array();
            if ($data['serialized']) {

                $sql2 = "SELECT
                            serialise_id,
                            serialise_lot_no,
                            serialise_chasis_no
                        FROM
                            item_serialise
                        WHERE
                            serialise_grn_items_id = " . $data['id'] . "
                        AND
                            invoice = 0";
                $query2 = db_query($sql2, "error");

                while ($serial = db_fetch_assoc($query2, "error")) {
                    $serials[] = $serial;
                }
            }

            $info[] = array(
                'grn_items_id' => $data['id'],
                'po_detail_id' => $data['po_detail_item'],
                'model' => $data['item_code'],
                'description' => $data['description'],
                'category' => get_category_name($data['category_id']),
                'color_code' => $data['color_code'],
                'quantity' => $data['quantity'],
                'std_cost_unit' => $data['std_cost_unit'],
                'serialized' => $data['serialized'],
                'serials' => $serials
            );
        }

        api_success_response(json_encode($info));
    }

    public function put($rest)
    {
        begin_transaction();
        $req = $rest->request();
        $info = $req->post();

        $po_details = $info['po_details'];
        $serials = $info['serials'];

         $doc_no = (string) $info['doc_entry'];
         $rr_ref_no = $info['rr_ref_no'];
         $poack_no = (string) $info['sup_inv'];

        $result = 0;    
        foreach ($po_details as $detail) {
            $invoice_qty = $detail['selected_quantity'];
            $item_code = $detail['item_code'];
            $po_detail_item = $detail['po_detail_item'];
            $sql1 = "UPDATE 
                    purch_order_details 
                SET 
                    qty_invoiced = (qty_invoiced + $invoice_qty) 
                WHERE 
                    po_detail_item = $po_detail_item
                AND
                    item_code = " . db_escape($item_code) . "";
            $result +=db_query($sql1, "The purch order details could not be updated");
        }

        foreach ($serials as $serial_id) {
            $sql2 = "UPDATE
                        item_serialise
                    SET
                        invoice = 1
                    WHERE
                        serialise_id = $serial_id";
            $result +=db_query($sql2, "The item serialize could not be updated");
        }
        //Added by Albert 10/22/2022
            $sql3 = "UPDATE
                    grn_batch a
                SET
                    a.apinvoice_no_and_poack_no = CONCAT($doc_no, ' || ', $poack_no)
                WHERE
                    a.reference = ".db_escape($rr_ref_no);
            $result =db_query($sql3, "The grn_batch could not be updated");
        /**/ 
        $response = "Failed";
        if($result == (count($po_details) + count($serials))+ 1){ //Added 1 for updating grn_batch
            commit_transaction();
            $response = "Success";
        }
        
        api_success_response($response); 
    }
     //Added by Albert  apsupport 7/29/2022 
    public function get_apsupport($rest, $supplier_id, $category_name){

        $sql = "SELECT Distinct 
                    a.ap_support_type,
                    a.distribution,
                    'APSUPPORT' as ItemCode,
                    b.price
                FROM
                    item_apsupport_type a
                Inner Join 
                    item_apsupport_price b on  a.id = b.apsupport_type_id
                INNER JOIN
                    stock_category c on c.category_id = b.category_id
                WHERE  a.inactive = '0' And b.supplier_id = " . db_escape($supplier_id) . "
                AND c.description = ".db_escape($category_name)."
                order by 2";
        $query = db_query($sql, "error");
        $info = array();

        while ($data = db_fetch($query, "error")) {
            $info[] = array(
                'ap_support_fa' => $data['ap_support_type'],
                'aps_distribution' => $data['distribution'],
                'aps_ItemCode_fa' => $data['ItemCode'],
                'aps_price_fa' => $data['price']
               );
        }

        api_success_response(json_encode($info));
        
    }
}
