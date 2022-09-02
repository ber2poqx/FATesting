

<?php

/**
 * Created By: Albert
 */

function get_cash_price_types_id($types){
    $sql = "SELECT id FROM ".TB_PREF."sales_cash_type
            WHERE scash_type = ".db_escape($types);
    $result = db_query($sql, "check cash price types failed");
    $row =  db_fetch($result);

return $row[0];

}

function get_lcp_price_types_id($types){
    $sql = "SELECT id FROM ".TB_PREF."sales_types
            WHERE sales_type = ".db_escape($types);
    $result = db_query($sql, "check lcp price types failed");
    $row =  db_fetch($result);

return $row[0];

}

function get_system_cost_types_id($types){
    $sql = "SELECT id FROM ".TB_PREF."supp_cost_types
            WHERE cost_type = ".db_escape($types);
    $result = db_query($sql, "check system cost types failed");
    $row =  db_fetch($result);

return $row[0];

}

function get_srp_types_id($types){
    $sql = "SELECT id FROM ".TB_PREF."item_srp_area_types
            WHERE srp_type = ".db_escape($types);
    $result = db_query($sql, "srp types failed");
    $row =  db_fetch($result);

return $row[0];

}

function get_incentive_types_id($types){
    $sql = "SELECT id FROM ".TB_PREF."sales_incentive_type
            WHERE module_type = ".db_escape($types);
    $result = db_query($sql, "check incentive types failed");
    $row =  db_fetch($result);

return $row[0];

}


function get_cash_types($types){
    $sql = "SELECT scash_type  FROM ".TB_PREF."sales_cash_type
            WHERE scash_type = ".db_escape($types);
    $result = db_query($sql, "check cash  types failed");
    $row =  db_fetch($result);

return $row[0];

}

function get_lcp_price_types($types){
    $sql = "SELECT sales_type FROM ".TB_PREF."sales_types
            WHERE sales_type = ".db_escape($types);
    $result = db_query($sql, "check lcp price types failed");
    $row =  db_fetch($result);

return $row[0];

}

function get_system_cost_types($types){
    $sql = "SELECT cost_type FROM ".TB_PREF."supp_cost_types
            WHERE cost_type = ".db_escape($types);
    $result = db_query($sql, "check system cost types failed");
    $row =  db_fetch($result);

return $row[0];

}

function get_srp_types($types){
    $sql = "SELECT srp_type FROM ".TB_PREF."item_srp_area_types
            WHERE srp_type = ".db_escape($types);
    $result = db_query($sql, "check srp types failed");
    $row =  db_fetch($result);

return $row[0];

}

function get_incentive_types($types){
    $sql = "SELECT module_type FROM ".TB_PREF."sales_incentive_type
            WHERE module_type = ".db_escape($types);
    $result = db_query($sql, "check incentive types failed");
    $row =  db_fetch($result);

return $row[0];

}

function pr_get_supplier_id($supplier){
    $sql = "SELECT supplier_id FROM ".TB_PREF."suppliers
            WHERE supp_name = ".db_escape($supplier);
    $result = db_query($sql, "check supplier_id failed");
    $row =  db_fetch($result);

return $row[0];

}

function get_supplier_desc($stock_id){
    $sql = "SELECT description FROM ".TB_PREF."stock_master
            WHERE stock_id = ".db_escape($stock_id);
    $result = db_query($sql, "check supplier description failed");
    $row =  db_fetch($result);

return $row[0];

}

function check_price_already_exist( $types, $stock_id, $supplier){

    if(get_cash_types($types)){
        $sql = "SELECT 
                a.stock_id,
                a.price,
                b.scash_type
                FROM ".TB_PREF."cash_prices a
                INNER JOIN ".TB_PREF."sales_cash_type b on a.scash_type_id = b.id
                WHERE a.stock_id = ".db_escape($stock_id)."AND b.scash_type =".db_escape($types);
   
    }else if(get_lcp_price_types($types)){

        $sql = "SELECT 
                a.stock_id,
                a.price,
                b.sales_type
                FROM ".TB_PREF."prices a
                INNER JOIN ".TB_PREF."sales_types b on a.sales_type_id = b.id
                WHERE a.stock_id = ".db_escape($stock_id)."AND b.sales_type =".db_escape($types);
    }else if(get_system_cost_types($types)){
        $sql = "SELECT 
                a.stock_id,
                a.price,
                b.cost_type,
                c.supp_name
                FROM ".TB_PREF."purch_data a
                INNER JOIN ".TB_PREF."supp_cost_types b on a.cost_type_id = b.id
                INNER JOIN ".TB_PREF."suppliers c on a.supplier_id = c.supplier_id
                WHERE a.stock_id = ".db_escape($stock_id)."AND c.supp_name =".db_escape($supplier)."AND b.cost_type =".db_escape($types);
   
    }else if(get_srp_types($types)){
        $sql = "SELECT 
                a.stock_id,
                a.standard_cost,
                b.srp_type,
                c.supp_name
                FROM ".TB_PREF."item_srp a
                INNER JOIN ".TB_PREF."item_srp_area_types b on a.srptype_id = b.id
                INNER JOIN ".TB_PREF."suppliers c on a.supplier_id = c.supplier_id
                WHERE a.stock_id = ".db_escape($stock_id)."AND c.supp_name =".db_escape($supplier)."AND b.srp_type =".db_escape($types);

    
    }else{
          if(get_incentive_types($types)){

            $sql = "SELECT 
                    a.stock_id,
                    a.price,
                    b.description
                    FROM ".TB_PREF."incentive_prices a
                    INNER JOIN ".TB_PREF."sales_incentive_type b on a.incentive_type_id = b.id
                    WHERE a.stock_id = ".db_escape($stock_id)."AND b.module_type=".db_escape($types);
          }

    }
    $result = db_query($sql, "check incentive types failed");
    $row =  db_fetch($result);

return $row[0];



}

function get_price_id($types){

    if(get_cash_types($types)){
        $sql = "SELECT
                        max(a.id) as last_id
                    FROM ".TB_PREF."cash_prices a";
   
    }else if(get_lcp_price_types($types)){

            $sql = "SELECT
                        max(a.id) as last_id
                    FROM ".TB_PREF."prices a";
    }else if(get_system_cost_types($types)){
            $sql = "SELECT
                        max(a.id) as last_id
                    FROM ".TB_PREF."purch_data a";   
    }else if(get_srp_types($types)){
            $sql = "SELECT
                        max(a.id) as last_id
                    FROM ".TB_PREF."item_srp a";
    
    }else{
          if(get_incentive_types($types)){

            $sql = "SELECT
                        max(a.id) as last_id
                    FROM ".TB_PREF."incentive_prices a";

          }

    }
        $result = db_query($sql, "check incentive types failed");
        $row =  db_fetch($result);
        
        return $row[0];
}

function get_existing_price_id($types,$stock_id){
    set_global_connection();

    if(get_cash_types($types)){
        $cash_type = get_cash_price_types_id($types);
        $sql = "SELECT
                        a.id
                    FROM ".TB_PREF."cash_prices a where a.scash_type_id =".db_escape($cash_type)."AND a.stock_id =".db_escape($stock_id);
    }else if(get_lcp_price_types($types)){
        $lcp_type = get_lcp_price_types_id($types);

            $sql = "SELECT
                        a.id
                    FROM ".TB_PREF."prices a where a.sales_type_id =".db_escape($lcp_type)."AND a.stock_id =".db_escape($stock_id);
    }else if(get_system_cost_types($types)){
        $cost_type = get_system_cost_types_id($types);
            $sql = "SELECT
                        a.id
                    FROM ".TB_PREF."purch_data a where a.cost_type_id =".db_escape($cost_type)."AND a.stock_id =".db_escape($stock_id);
    }else if(get_srp_types($types)){
        $srp_type = get_srp_types_id($types);
            $sql = "SELECT
                        a.id
                    FROM ".TB_PREF."item_srp a where a.srptype_id =".db_escape($srp_type)."AND a.stock_id =".db_escape($stock_id);
    
    }else{
          if(get_incentive_types($types)){
            $incentive_type = get_incentive_types_id($types);

            $sql = "SELECT
                        a.id
                    FROM ".TB_PREF."incentive_prices a where a.incentive_type_id =".db_escape($incentive_type)."AND a.stock_id =".db_escape($stock_id);

          }

    }
        $result = db_query($sql, "check Price types failed");
        $row =  db_fetch($result);
        
        return $row[0];
}
?>