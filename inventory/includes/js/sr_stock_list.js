function setComboItem(
    doc,
    client_id,
    value,
    text,
    serialeng_no,
    chassis_no,
    color,
    item_type,
    unit_price,
    standard_cost,
    trans_no,
    trans_type,
    qoh
) {
    var element = doc.getElementById(client_id);
    if (typeof(element) != 'undefined' && element != null && element.tagName === 'SELECT') {
        var options = element.options;
        options.length = 0;
        var option = doc.createElement('option');
        option.value = value;
        option.text = text;
        element.add(option, 0);
        element.selectedIndex = 0;
        element.onchange();
    } else {
        var stock_element = doc.getElementsByName('_stock_id_edit');
        stock_element[0].value = value;
        var desc_element = doc.getElementsByName('description');
        desc_element[0].value = text;
        var serial_element = doc.getElementsByName('serialeng_no');
        serial_element[0].value = serialeng_no;
        var chassis_element = doc.getElementsByName('chassis_no');
        chassis_element[0].value = chassis_no;
        var color_element = doc.getElementsByName('color_desc');
        color_element[0].value = color;
        var item_type_element = doc.getElementsByName('item_type');
        item_type_element[0].value = item_type;
        var unit_price_element = doc.getElementsByName('price');
        unit_price_element[0].value = unit_price;
        var cost_element = doc.getElementsByName('standard_cost');
        cost_element[0].value = standard_cost;

        var trans_no_element = doc.getElementsByName('trans_no');
        trans_no_element[0].value = trans_no;
        var trans_type_element = doc.getElementsByName('trans_type');
        trans_type_element[0].value = trans_type;
        var qoh_element = doc.getElementsByName('qoh');
        qoh_element[0].value = qoh;

        var stock_id = doc.getElementById('_stock_id_edit');
        stock_id.onblur();
    }
    window.close();
}