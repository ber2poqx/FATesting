<?php

function search_item(
	$path_to_root,
	$id,
	$category,
	$loc_code,
	$repo = "new",
	$serialized = 0
) {
	$link = $path_to_root . "/inventory/inquiry/sr_stock_list.php?popup=1&type=all&client_id="
		. $id . "&category=" . $category . "&location=" . $loc_code . "&serialized=" . $serialized
		. "&repo=" . $repo;

	$img_title = _("Search items");

	$theme = user_theme();
	$img = 'Search Item <img src="' . $path_to_root . '/themes/' . $theme . '/images/' . ICON_VIEW .
		'" style="vertical-align:middle;width:12px;height:12px;border:0;" onclick="javascript:lookupWindow(&quot;' .
		$link . '&quot;, &quot;&quot;);" title="' . $img_title . '" style="cursor:pointer;" />';
	echo $img;
}

function sr_get_js_set_combo_item()
{
	$js = "function setComboItem(doc, client_id, value, text, serialeng_no, chassis_no, color, item_type){
		var serial_element = doc.getElementsByName('serialeng_no');
		serial_element[0].value = serialeng_no;
		var chassis_element = doc.getElementsByName('chassis_no');
		chassis_element[0].value = chassis_no;
		var color_element = doc.getElementsByName('color_desc');
		color_element[0].value = color;	
		var item_type_element = doc.getElementsByName('item_type');
		item_type_element[0].value = item_type;

    	var element = doc.getElementById(client_id);
  		if(typeof(element) != 'undefined' && element != null && element.tagName === 'SELECT') {
	 		var options = element.options;
	 		options.length = 0;
	 		var option = doc.createElement('option');
			option.value = value;
			option.text = text;
	   		element.add(option, 0);
			element.selectedIndex = 0;
	        element.onchange();
	    } else {
			var stock_element = doc.getElementsByName('stock_id');
	    	if( stock_element.length > 0) {
				stock_element[0].value = value;	
				var stock_id = doc.getElementById('_stock_id_edit'); 
				stock_id.value=value;
				stock_id.onblur();					
			}
 		}
		window.close();
	}";
	return $js;
}

function hidden2($name, $id, $value = null, $echo = true)
{
	global $Ajax, $hidden_fields;

	if ($value === null)
		$value = get_post($name);
	$by_id = true;
	$ret = "<input type='hidden' name='$id' id='$id' 
			class='combo' rel='$id'"
		. (!fallback_mode() && !$by_id ? " style=display:none;" : '')
		. ">\n";
	$ret .= sprintf(
		SEARCH_BUTTON,
		'',
		user_theme(),
		(fallback_mode() ? '' : 'display:none;'),
		"_{$name}_button"
	) . "\n";
	$Ajax->addUpdate($name, $name, $value);
	if ($echo)
		$hidden_fields[] = $ret;
	else
		return $ret;
}

function custom_qty_cells($label, $name, $init = null, $params = null, $post_label = null, $dec = null)
{
	if (!isset($dec))
		$dec = user_qty_dec();

	custom_amount_cells_ex($label, $name, 15, 15, $init, $params, $post_label, $dec);
}

function custom_amount_cells_ex($label, $name, $size, $max = null, $init = null, $params = null, $post_label = null, $dec = null)
{
	global $Ajax;

	if (!isset($dec))
		$dec = user_price_dec();
	if (!isset($_POST[$name]) || $_POST[$name] == "") {
		if ($init !== null)
			$_POST[$name] = $init;
		else
			$_POST[$name] = '';
	}
	if ($label != null) {
		if ($params == null)
			$params = "class='label'";
		label_cell($label, $params);
	}
	if (!isset($max))
		$max = $size;

	if ($label != null)
		echo "<td>";
	else
		echo "<td align='right'>";

	$submit_on_change = "JsHttpRequest.request($name, this.form)";
	echo "<input class='amount' type=\"text\" name=\"$name\" size=\"$size\" maxlength=\"$max\" dec=\"$dec\" value=\"" . $_POST[$name] . "\" 
		onchange=\"$submit_on_change\" >";

	if ($post_label) {
		echo "<span id='_{$name}_label'> $post_label</span>";
		$Ajax->addUpdate($name, '_' . $name . '_label', $post_label);
	}
	echo "</td>\n";
	$Ajax->addUpdate($name, $name, $_POST[$name]);
	$Ajax->addAssign($name, $name, 'dec', $dec);
}

function copy_pr_navi_button($label, $name, $value, $enabled=true, $icon = false) {
	global $path_to_root;
  	return "<button ". ($enabled ? '':'disabled')
  		." class=\"navibutton\" type=\"submit\""
	    ." name=\"$name\"  id=\"$name\" value=\"$value\">"
	  	.($icon ? "<img src='$path_to_root/themes/".user_theme()."/images/".$icon."'>":'')
		."<span>$label</span></button>\n";
}

function copy_pr_button($name, $value, $title=false, $icon=false,  $aspect='')
{
	// php silently changes dots,spaces,'[' and characters 128-159
	// to underscore in POST names, to maintain compatibility with register_globals
	$rel = '';
	if ($aspect == 'selector') {
		$rel = " rel='$value'";
		$value = _("Select");
	}
	if (user_graphic_links() && $icon)
	{
		if ($value == _("Delete")) // Helper during implementation
			$icon = ICON_DELETE;
		return "<button type='submit' class='editbutton' name='"
			.html_specials_encode(strtr($name, array('.'=>'=2E', '='=>'=3D',// ' '=>'=20','['=>'=5B'
			)))
			."' value='$value'" . ($title ? " title='$title'":" title='$value'")
			. ($aspect ? " aspect='$aspect'" : '')
			. $rel
			." >".set_icon($icon)."</button>\n";
	}
	else
		return "<input type='submit' class='editbutton' name='"
			.htmlentities(strtr($name, array('.'=>'=2E', '='=>'=3D',// ' '=>'=20','['=>'=5B'
			)))
			."' value='$value'"
			.($title ? " title='$title'":'')
			. ($aspect ? " aspect='$aspect'" : '')
			. $rel
			." >\n";
}

function copy_pr_button_cell($name, $value, $title=false, $icon=false, $aspect='')
{
	echo "<td align='center'>";
	echo copy_pr_button($name, $value, $title, $icon, $aspect);
	echo "</td>";
}
