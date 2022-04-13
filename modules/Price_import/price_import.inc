

<?php

/**
 * Created By: Albert
 */

//Cash Pricing
function add_cash_price($types, $stock_id, $price, $date_epic)
{
	global $Refs;
   
   $sql = "INSERT INTO ".TB_PREF."cash_prices ( stock_id, scash_type_id, curr_abrev, price, date_epic)
	VALUES ('$stock_id', '$types', 'PHP', '$price', '$date_epic')";

   db_query($sql,"The Import Price List Data could not be added");
}

//Lcp Pricing
function add_lcp_pricing($types, $stock_id, $price, $date_epic)
{
	global $Refs;
   
   $sql = "INSERT INTO ".TB_PREF."prices ( stock_id, sales_type_id, curr_abrev, price, date_epic)
	VALUES ('$stock_id', '$types', 'PHP', '$price', '$date_epic')";

    db_query($sql,"The Import Price List Data could not be added");
}

//System cost
function add_System_cost_pricing($supplier, $types, $stock_id, $supplierdesc, $price, $date_epic)
{
	global $Refs;
   
   $sql = "INSERT INTO ".TB_PREF."purch_data ( supplier_id, stock_id, price, suppliers_uom, conversion_factor,supplier_description, cost_type_id, date_epic)
	VALUES ('$supplier', '$stock_id', '$price', '', '1', '$supplierdesc', '$types', '$date_epic')";

    db_query($sql,"The Import Price List Data could not be added");
}

//SRP
function add_srp_pricing($supplier, $types, $stock_id, $price, $date_epic)
{
	global $Refs;
   
   $sql = "INSERT INTO ".TB_PREF."item_srp ( stock_id, srptype_id, supplier_id, curr_abrev, standard_cost, date_epic)
	VALUES ( '$stock_id', '$types', '$supplier', 'PHP', '$price', '$date_epic')";

    db_query($sql,"The Import Price List Data could not be added");
}

//Incentives Pricing
function add_incentives_pricing($types, $stock_id, $price)
{
	global $Refs;
   
   $sql = "INSERT INTO ".TB_PREF."incentive_prices (  stock_id, incentive_type_id, curr_abrev, price)
	VALUES ('$stock_id','$types' , 'PHP','$price')";

    db_query($sql,"The Import Price List Data could not be added");
}

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

function get_supplier_desc($supplier){
    $sql = "SELECT description FROM ".TB_PREF."stock_master
            WHERE stock_id = ".db_escape($supplier);
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

?>