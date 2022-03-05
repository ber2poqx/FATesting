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
/**********************************************************************
  Page for searching item list and select it to item selection
  in pages that have the item dropdown lists.
  Author: bogeyman2007 from Discussion Forum. Modified by Joe Hunt
***********************************************************************/
$page_security = "SA_ITEM";
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/inventory/includes/db/items_db.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");

$mode = get_company_pref('no_item_list');
if ($mode != 0)
	$js = get_js_set_combo_item();
else
	$js = get_js_select_combo_item();


add_js_ufile($path_to_root . "/js/ext620/build/examples/classic/shared/include-ext.js?theme=triton");
add_js_ufile($path_to_root .'/js/stock_list.js');


if(isset($_GET['view'])){
	mysql_set_charset('utf8');
	$start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_GET['start']);
	$end = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_GET['limit']);
	
	$name = $_POST["client_id"];
	if(!isset($_REQUEST['query'])){
		$_POST['description']="";
	}else{
		$_POST['description']=$_REQUEST['query'];
	}
	if(!isset($_REQUEST['category'])){
		$_POST['category']="-1";
	}else{
		$_POST['category']=$_REQUEST['category'];
	}
	
	$result = get_items_search_listing($start, $end, $_POST['description'], @$_POST['type'],$_POST['category']);
	$totalresult = get_total_items_search_listing($_POST['description'], @$_POST['type'],$_POST['category']);
	
    $total = mysqli_num_rows($totalresult);
	while ($myrow = db_fetch_assoc($result))
	{
		$value = $myrow['item_code'];
		 //Added Herald - 09-01-2020 for available qty 
		$loc_details = get_loc_details($myrow['stock_id']);
		$myrow1 = db_fetch($loc_details);

		$demand_qty = get_demand_qty($myrow['stock_id'], 0);
		//$demand_qty += get_demand_asm_qty($myrow['stock_id'], $myrow1["loc_code"]);
		$demand_qty += get_demand_asm_qty($myrow['stock_id'],0);
		$qoh = get_qoh_on_date($myrow['stock_id'], 0);

		if ($mode != 0) {
			$text = $myrow['description'];
			$select_view = herfel_ahref(_("Select"), 'javascript:void(0)', '', 'setComboItem(window.opener.document, "'.$name.'",  "'.$value.'", "'.$text.'")');
		}
		else {
			$select_view = herfel_ahref(_("Select"), 'javascript:void(0)', '', 'selectComboItem(window.opener.document, "'.$name.'", "'.$value.'")');
		}
		$dec = get_qty_dec($myrow["item_code"]);
		
		//qty_cell($qoh - $demand_qty, false, $dec);
		
		$group_array[] = array('select_view'=>$select_view,
						'item_code'=>$myrow["item_code"],						
						'description'=>$myrow["description"],						
						'units'=>$myrow["units"],						
						'avail_qty'=>number_format($qoh - $demand_qty,$dec),						
						'category'=>$myrow["category"],
						'brand_name'=>$myrow["brand_name"],
						'manufacturer_name'=>$myrow["manufacturer_name"],
						'distributor_name'=>$myrow["distributor_name"],
						'importer_name'=>$myrow["importer_name"]=='NULL'?'':$myrow["importer_name"]
					);
	}
	$jsonresult = json_encode($group_array);
	echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
	exit;
	break;
}

if(isset($_GET['category_list'])){
	$sql = "SELECT * FROM ".TB_PREF."stock_category ORDER by description asc";
	$result = db_query($sql, "Failed in retreiving item list.");
	$total = mysqli_num_rows($result);
	while ($myrow = db_fetch($result)) 
	{
		$group_array[] = array('category_id'=>$myrow["category_id"],
			'description'=>$myrow["description"]
		);
	}
	
	$jsonresult = json_encode($group_array);
	echo '({"total":"'.$total.'","result":'.$jsonresult.'})';
	exit;
	break;

}

function herfel_ahref($label, $href, $target="", $onclick="") {
  return "<a href='$href' target='$target' onclick='$onclick'>$label</a>";
}

function get_items_search_listing($start, $end, $description, $type, $category=-1)
{
	global $SysPrefs;

	if($category!=-1){
        $stmt_category=" AND c.category_id = " . db_escape(get_post("category"));
    }else{
        $stmt_category="";
    }
	$sql = "SELECT COUNT(i.item_code) AS kit, i.item_code, i.description,  c.description as category, b.name as brand_name, m.name as manufacturer_name, s.units, s.stock_id, d.name as distributor_name, ii.name as importer_name
		FROM ".TB_PREF."stock_master s INNER JOIN ".TB_PREF."item_codes i ON i.stock_id=s.stock_id
			LEFT JOIN ".TB_PREF."stock_category c ON i.category_id=c.category_id	
			LEFT JOIN ".TB_PREF."item_brand b ON s.brand=b.id LEFT JOIN ".TB_PREF."item_manufacturer m ON s.manufacturer=m.id
			LEFT JOIN ".TB_PREF."item_distributor d ON s.distributor=d.id LEFT JOIN ".TB_PREF."item_importer ii ON s.importer=ii.id	
		WHERE !i.inactive AND !s.inactive
			AND (i.item_code LIKE " . db_escape("%" . $description. "%") . " OR
				i.description LIKE " . db_escape("%" . get_post("description"). "%") . " OR
				b.name LIKE " . db_escape("%" . get_post("description"). "%") . "  OR
				m.name LIKE " . db_escape("%" . get_post("description"). "%") . ")".$stmt_category;
				
	switch ($type) {
		case "sales":
			$sql .= " AND !s.no_sale AND mb_flag <> 'F'";
			break;
		case "manufactured":
			$sql .= " AND mb_flag = 'M'";
			break;
    	case "purchasable":
    		$sql .= " AND NOT no_purchase AND mb_flag <> 'F' AND i.item_code=i.stock_id";
    		break;
		case "costable":
			$sql .= " AND mb_flag <> 'D' AND mb_flag <> 'F' AND  i.item_code=i.stock_id";
			break;
		case "component":
			$parent = $_GET['parent'];
			$sql .= " AND  i.item_code=i.stock_id AND i.stock_id <> '$parent' AND mb_flag <> 'F' ";
			break;
		case "kits":
			$sql .= " AND !i.is_foreign AND i.item_code!=i.stock_id AND mb_flag <> 'F'";
			break;
		case "all":
			$sql .= " AND mb_flag <> 'F' AND i.item_code=i.stock_id";
			break;
	}

	if (isset($SysPrefs->max_rows_in_search))
		$limit = $SysPrefs->max_rows_in_search;
	else
		$limit = 10;

	$sql .= " GROUP BY i.item_code ORDER BY i.description LIMIT $start,$end";

	return db_query($sql, "Failed in retreiving item list.");
}

function get_total_items_search_listing($description, $type, $category=-1)
{
	global $SysPrefs;

	if($category!=-1){
        $stmt_category=" AND c.category_id = " . db_escape(get_post("category"));
    }else{
        $stmt_category="";
    }
	$sql = "SELECT COUNT(i.item_code) AS kit, i.item_code, i.description,  c.description as category, b.name as brand_name, m.name as manufacturer_name, s.units, s.stock_id, d.name as distributor_name, ii.name as importer_name
		FROM ".TB_PREF."stock_master s INNER JOIN ".TB_PREF."item_codes i ON i.stock_id=s.stock_id
			LEFT JOIN ".TB_PREF."stock_category c ON i.category_id=c.category_id	
			LEFT JOIN ".TB_PREF."item_brand b ON s.brand=b.id LEFT JOIN ".TB_PREF."item_manufacturer m ON s.manufacturer=m.id
			LEFT JOIN ".TB_PREF."item_distributor d ON s.distributor=d.id LEFT JOIN ".TB_PREF."item_importer ii ON s.importer=ii.id	
		WHERE !i.inactive AND !s.inactive
			AND (i.item_code LIKE " . db_escape("%" . $description. "%") . " OR
				i.description LIKE " . db_escape("%" . get_post("description"). "%") . " OR
				b.name LIKE " . db_escape("%" . get_post("description"). "%") . "  OR
				m.name LIKE " . db_escape("%" . get_post("description"). "%") . ")".$stmt_category;
				
	switch ($type) {
		case "sales":
			$sql .= " AND !s.no_sale AND mb_flag <> 'F'";
			break;
		case "manufactured":
			$sql .= " AND mb_flag = 'M'";
			break;
    	case "purchasable":
    		$sql .= " AND NOT no_purchase AND mb_flag <> 'F' AND i.item_code=i.stock_id";
    		break;
		case "costable":
			$sql .= " AND mb_flag <> 'D' AND mb_flag <> 'F' AND  i.item_code=i.stock_id";
			break;
		case "component":
			$parent = $_GET['parent'];
			$sql .= " AND  i.item_code=i.stock_id AND i.stock_id <> '$parent' AND mb_flag <> 'F' ";
			break;
		case "kits":
			$sql .= " AND !i.is_foreign AND i.item_code!=i.stock_id AND mb_flag <> 'F'";
			break;
		case "all":
			$sql .= " AND mb_flag <> 'F' AND i.item_code=i.stock_id";
			break;
	}

	$sql .= " GROUP BY i.item_code";

	return db_query($sql, "Failed in retreiving item list.");
}

<<<<<<< HEAD
end_table(1); */
=======
page(_($help_context = "Items"), true, false, "", $js);

//if(get_post("search")) {
  //$Ajax->activate("item_tbl");
//}

//start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

//start_table(TABLESTYLE_NOBORDER);

//start_row();

//text_cells(_("Description"), "description");
//stock_categories_list_cells(_("Category"), "category",null,_("All Categories"));
//echo '<td><input type="text" name="brand"/></td>';
//stock_brand_list_cells(_("Brand"),_("brand"),null,true);
//submit_cells("search", _("Search"), "", _("Search items"), "default");

//end_row();

//end_table();

//end_form();
>>>>>>> parent of 437770e... Rollback changing


//start_table(TABLESTYLE);

$th = array("", _("Item Code"), _("Description"), _("Avail Qty"), _("Units"), _("Category"), _("Brand"), _("Manufacturer"), _("Distributor"), _("Importer"));
//table_header($th);



//end_table(1);
start_table(TABLESTYLE, "width='100%'");
start_row();
echo '<td id="item_tbl"></td>';
div_end();
<<<<<<< HEAD
//end_page(true);
=======
end_row();
end_table(1);
>>>>>>> parent of 437770e... Rollback changing
end_page(true,false,false);
