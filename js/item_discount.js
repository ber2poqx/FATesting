var newURL = window.location.protocol + "//" + window.location.host + window.location.pathname;
Ext.Loader.setConfig({enabled: true});
Ext.Loader.setPath('Ext.ux', '../js/ext4/examples/ux/');
Ext.require([
    'Ext.grid.*',
    'Ext.data.*',
    'Ext.panel.*',
    'Ext.form.*',
	'Ext.window.*',
    'Ext.tab.*',
	'Ext.selection.CheckboxModel',
	'Ext.selection.CellModel',
	'Ext.form.field.File',
	'Ext.ux.form.SearchField',
	'Ext.ux.form.NumericField'
]);

Ext.onReady(function(){
	Ext.QuickTips.init();
	var itemsPerPage = 5;   // set the number of items you want per page on grid.
	var showall = false;

	Ext.define('itmdiscount_model',{
		extend : 'Ext.data.Model',
		fields  : [
			{name:'id',mapping:'id'},
			{name:'stock_id',mapping:'stock_id'},
			{name:'item_description',mapping:'item_description'},
			{name:'discount_type_id',mapping:'discount_type_id'},
			{name:'dpdiscount1',mapping:'dpdiscount1',type:'float'},
			{name:'dpdiscount2',mapping:'dpdiscount2',type:'float'},
			{name:'salediscount1',mapping:'salediscount1',type:'float'},
			{name:'salediscount2',mapping:'salediscount2',type:'float'},
			{name:'category',mapping:'category'},
			{name:'brand',mapping:'brand'}
		]
	});
    Ext.define('comboModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'name', mapping:'name'}
		]
    });
	var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
        clicksToEdit: 2
    });
    var storecategory = Ext.create('Ext.data.Store', {
		name: 'storecategory',
        model: 'comboModel',
		autoLoad : true,
        proxy: {
			url: '?getcategory=00',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'name',
			direction : 'ASC'
		}]
	});
	var storebrand = Ext.create('Ext.data.Store', {
		name: 'storebrand',
        model: 'comboModel',
		autoLoad : true,
        proxy: {
			url: '?get_brand=00',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'name',
			direction : 'ASC'
		}]
	});
    var storeItem = Ext.create('Ext.data.Store', {
		name: 'storeItem',
        model: 'comboModel',
		autoLoad : true,
        proxy: {
			url: '?getItem=00',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'name',
			direction : 'ASC'
		}]
	});
	var Itemdiscount_store = Ext.create('Ext.data.Store', {
		model: 'itmdiscount_model',
		//autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_itemdiscount=xx',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'id',
			direction : 'ASC'
		}]
	});
			
	var submit_form = Ext.create('Ext.form.Panel', {
		id: 'form_submit',
		model: 'itmdiscount_model',
		frame: true,
		//height: 488,
		defaultType: 'field',
		defaults: {msgTarget: 'under', anchor: '-5'}, //msgTarget: 'side', labelAlign: 'top'
		items: [{
			xtype: 'textfield',
			id: 'syspk',
			name: 'syspk',
			fieldLabel: 'syspk',
			//allowBlank: false,
			hidden: true
		},{
			xtype: 'combobox',
			id: 'category',
			name: 'category',
			fieldLabel: '<b>Category </b>',
			store: storecategory,
			displayField: 'name',
			valueField: 'id',
			queryMode: 'local',
			emptyText:'Select category',
			margin: '2 0 2 0',
			labelWidth: 110,
			forceSelection: true,
			selectOnFocus:true,
			allowBlank:false,
			fieldStyle : 'font-weight: bold; color: #210a04;',
			listeners: {
				select: function(combo, record, index) {
					storeItem.proxy.extraParams = {category: 0, brand:0};
					storeItem.load();
					storebrand.proxy.extraParams = {category: combo.getValue()};
					storebrand.load();
				}
			}
		},{
			xtype: 'combobox',
			id: 'brand',
			name: 'brand',
			fieldLabel: '<b>Brand </b>',
			store: storebrand,
			displayField: 'name',
			valueField: 'id',
			queryMode: 'local',
			emptyText:'Select brand',
			margin: '2 0 2 0',
			labelWidth: 110,
			forceSelection: true,
			selectOnFocus:true,
			allowBlank:false,
			fieldStyle : 'font-weight: bold; color: #210a04;',
			listeners: {
				select: function(combo, record, index) {
					storeItem.proxy.extraParams = {brand: combo.getValue(), category: Ext.getCmp('category').getValue()};
					storeItem.load();
				}
			}
		},{
			xtype: 'combobox',
			id: 'item',
			name: 'item',
			fieldLabel: '<b>Item </b>',
			store: storeItem,
			displayField: 'name',
			valueField: 'id',
			queryMode: 'local',
			emptyText:'Select item',
			margin: '2 0 2 0',
			labelWidth: 110,
			forceSelection: true,
			selectOnFocus:true,
			allowBlank:false,
			fieldStyle : 'font-weight: bold; color: #210a04;'
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 0',
			items:[{
				xtype: 'numericfield',
				id: 'salediscount1',
				name: 'salediscount1',
				fieldLabel: 'Sale discount 1 ',
				allowBlank:false,
				useThousandSeparator: true,
				labelWidth: 110,
				width: 271,
				thousandSeparator: ',',
				minValue: 0,
				fieldStyle: 'font-weight: bold;color: #008000; text-align: right; background-color: #F2F3F4;'
			},{
				xtype: 'numericfield',
				id: 'dpdiscount1',
				name: 'dpdiscount1',
				fieldLabel: 'DP discount 1 ',
				allowBlank:false,
				useThousandSeparator: true,
				labelWidth: 100,
				width: 271,
				thousandSeparator: ',',
				minValue: 0,
				fieldStyle: 'font-weight: bold;color: #008000; text-align: right; background-color: #F2F3F4;'
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 0',
			items:[{
				xtype: 'numericfield',
				id: 'salediscount2',
				name: 'salediscount2',
				fieldLabel: 'Sale discount 2 ',
				allowBlank:false,
				useThousandSeparator: true,
				labelWidth: 110,
				width: 271,
				thousandSeparator: ',',
				minValue: 0,
				fieldStyle: 'font-weight: bold;color: #008000; text-align: right; background-color: #F2F3F4;'
			},{
				xtype: 'numericfield',
				id: 'dpdiscount2',
				name: 'dpdiscount2',
				fieldLabel: 'DP discount 2 ',
				allowBlank:false,
				useThousandSeparator: true,
				labelWidth: 100,
				width: 271,
				thousandSeparator: ',',
				minValue: 0,
				fieldStyle: 'font-weight: bold;color: #008000; text-align: right; background-color: #F2F3F4;'
			}]
		}]
	});

	var submit_window = Ext.create('Ext.Window',{
		width 	: 555,
		height: 266,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form],
		buttons:[{
			text: 'Save',
			tooltip: 'Save item discount',
			icon: '../js/ext4/examples/shared/icons/add.png',
			single : true,
			handler:function(){
				var form_submit = Ext.getCmp('form_submit').getForm();
				if(form_submit.isValid()) {
					form_submit.submit({
						url: '?submit=info',
						waitMsg: 'Saving item discount. please wait...',
						method:'POST',
						success: function(form_submit, action) {
							Ext.getCmp("fcategory").setValue(Ext.getCmp('category').getValue());
							Itemdiscount_store.proxy.extraParams = {category: Ext.getCmp('fcategory').getValue(), query: Ext.getCmp('search').getValue()};
							Itemdiscount_store.load();

							//Ext.Msg.alert('Success!', '<font color="green">' + action.result.message + '</font>');
							//submit_window.close();
							Ext.MessageBox.confirm('Success!', action.result.message + '<br>Would you like to add more?', function (btn, text) {
								if (btn == 'yes') {
									form_submit.reset();
									submit_window.setTitle('Item Discount Maintenance - Add');
								}else{
									submit_window.close();
								}
							});
						},
						failure: function(form_submit, action) {
							Ext.Msg.alert('Failed!', JSON.stringify(action.result.message));
						}
					});
					window.onerror = function(note_msg, url, linenumber) { //, column, errorObj
						//alert('An error has occurred!')
						Ext.Msg.alert('Error: ', note_msg + ' Script: ' + url + ' Line: ' + linenumber);
						return true;
					}
				}
			}
		},{
			text:'<b>Cancel</b>',
			tooltip: 'Cancel',
			icon: '../js/ext4/examples/shared/icons/cancel.png',
			handler:function(){
				Ext.MessageBox.confirm('Confirm:', 'Are you sure you wish to close this window?', function (btn, text) {
					if (btn == 'yes') {
						//Ext.Msg.alert('Close','close.');
						submit_form.getForm().reset();
						submit_window.close();
					}
				});
			}
		}]
	});

	var main_view = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Item Code</b>', dataIndex:'stock_id', width:120},
		{header:'<b>Item Description</b>', dataIndex:'item_description', width:250,
			renderer : function(value, metaData, summaryData, dataIndex){
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Sales Discount 1</b>', dataIndex:'salediscount1', width:140,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				if(value == 0){
					value = '0.00';
					return '<span style="color:red;font-weight:bold;">' + (value) + '</span>';
				}else{
					return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			}
		},
		{header:'<b>Sales Discount 2</b>', dataIndex:'salediscount2', width:140,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				if(value == 0){
					value = '0.00';
					return '<span style="color:red;font-weight:bold;">' + (value) + '</span>';
				}else{
					return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			}
		},
		{header:'<b>DP Discount 1</b>', dataIndex:'dpdiscount1', width:140,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				if(value == 0){
					value = '0.00';
					return '<span style="color:red;font-weight:bold;">' + (value) + '</span>';
				}else{
					return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			}
		},
		{header:'<b>DP Discount 2</b>', dataIndex:'dpdiscount2', width:140,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				if(value == 0){
					value = '0.00';
					return '<span style="color:red;font-weight:bold;">' + (value) + '</span>';
				}else{
					return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			}
		},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:95,
			items:[{
				icon: '../js/ext4/examples/shared/icons/layout_content.png',
				tooltip: 'view details',
				handler: function(grid, rowIndex, colIndex) {
					var records = Itemdiscount_store.getAt(rowIndex);

					storebrand.proxy.extraParams = {category: records.get('category')};
					storebrand.load();
					storeItem.proxy.extraParams = {stock_id: records.get('stock_id')};
					storeItem.load();

					submit_form.getForm().reset();

					Ext.getCmp('syspk').setValue(records.get('id'));
					Ext.getCmp('category').setValue(records.get('category'));
					Ext.getCmp('brand').setValue(records.get('brand'));
					Ext.getCmp('item').setValue(records.get('stock_id'));
					Ext.getCmp('salediscount1').setValue(records.get('salediscount1'));
					Ext.getCmp('salediscount2').setValue(records.get('salediscount2'));
					Ext.getCmp('dpdiscount1').setValue(records.get('dpdiscount1'));
					Ext.getCmp('dpdiscount2').setValue(records.get('dpdiscount2'));
					Ext.getCmp('category').setValue(records.get('category'));
					
					submit_window.setTitle('Item Discount Details - ' + records.get('item_description') );
					submit_window.show();
					submit_window.setPosition(330,90);
				}
			},'-',{
				icon   : '../js/ext4/examples/shared/icons/fam/delete.png',
				tooltip : 'Delete',
				handler : function(grid, rowIndex, colIndex){
					var records = Itemdiscount_store.getAt(rowIndex);
					var MsgConfirm = Ext.MessageBox.confirm('Confirm', 'Category: <b>' + Ext.getCmp('fcategory').getRawValue() + '</b><br\> Item: <b>' + records.get('item_description') + '</b><br\> Are you sure you want to delete this record? ', function (btn, text) {
						if (btn == 'yes') {
							Ext.Ajax.request({
								method: 'POST',
								url: '?delete=info',
								waitMsg:'Deleting Record...please wait.',
								params: {
									syspk: records.get('id')
								},
								success: function (response){
									var data = Ext.decode(response.responseText);
									if (data.success == 'true') {

										Itemdiscount_store.proxy.extraParams = {category: Ext.getCmp('fcategory').getValue(), query: Ext.getCmp('search').getValue()};
										Itemdiscount_store.load();

										Ext.Msg.alert('Success', data.message);
									}else{
										Ext.Msg.alert('Error', data.message);
									}
								}
							});
						}
					});
					MsgConfirm.defaultButton = 2;
				}
			}]
		}
	];

	var tbar = [{
		xtype: 'combobox',
		id: 'fcategory',
		name: 'fcategory',
		fieldLabel: '<b>Category </b>',
		store: storecategory,
		displayField: 'name',
		valueField: 'id',
		queryMode: 'local',
		emptyText:'Select category',
		labelWidth: 70,
		width: 240,
		forceSelection: true,
		selectOnFocus:true,
		fieldStyle : 'text-transform: capitalize; background-color: #F2F3F4; color:green; ',
		listeners: {
			select: function(combo, record, index) {
				Itemdiscount_store.proxy.extraParams = {category: combo.getValue(), query: Ext.getCmp('search').getValue()};
				Itemdiscount_store.load();
			},
			afterrender: function() {
				Ext.getCmp("fcategory").setValue('14');
				Itemdiscount_store.proxy.extraParams = {category: "14", query: Ext.getCmp('search').getValue()};
				Itemdiscount_store.load();
			}
		}
	}, '-',{
		xtype: 'searchfield',
		id:'search',
		name:'search',
		fieldLabel: '<b>Search</b>',
		labelWidth: 60,
		width: 300,
		emptyText: "Search Items",
		scale: 'small',
		store: Itemdiscount_store,
		listeners: {
			change: function(field) {
				Itemdiscount_store.proxy.extraParams = {category: Ext.getCmp('fcategory').getValue(), query: field.getValue()};
				Itemdiscount_store.load();
			}
		}
	}, '-',{
		text:'<b>Add</b>',
		tooltip: 'Add new item discount.',
		icon: '../js/ext4/examples/shared/icons/add.png',
		scale: 'small',
		handler: function(){
			submit_form.getForm().reset();

			submit_window.show();
			submit_window.setTitle('Item Discount Maintenance - Add');
			submit_window.setPosition(380,100);
		}
	}, '->',{
		xtype:'splitbutton',
		tooltip: 'list of reports',
		icon: '../js/ext4/examples/shared/icons/cog_edit.png',
		scale: 'small',
		/*menu:[{
			text: '<b>Sales Installment Policy Type</b>',
			icon: '../../js/ext4/examples/shared/icons/table_gear.png',
			href: 'sales_installment_policy_type.php?'
		},{
			text: '<b>Item Categories</b>',
			icon: '../../js/ext4/examples/shared/icons/chart_line.png',
			href: '../../inventory/manage/item_categories.php?',
		}, '-',{
			text: '<b>Items</b>',
			icon: '../../js/ext4/examples/shared/icons/cart.png',
			href: '../../inventory/manage/items.php?',
			hrefTarget : '_blank'
		}]*/
	}];

	var grid_panel =  Ext.create('Ext.panel.Panel', { 
        renderTo: 'ext-form',
		id: 'builder_panel',
        frame: false,
		width: 1065,
		tbar: tbar,
		items: [{
			xtype: 'grid',
			id: 'gridItem',
			name: 'gridItem',
			store:	Itemdiscount_store,
			columns: main_view,
			columnLines: true,
			autoScroll:true,
			layout:'fit',
			plugins: [cellEditing],
			frame: true,
			bbar : {
				xtype : 'pagingtoolbar',
				hidden: false,
				store : Itemdiscount_store,
				pageSize : itemsPerPage,
				displayInfo : false,
				emptyMsg: "No records to display",
				doRefresh : function(){
					Itemdiscount_store.load();
				}
			}
		}]
	});
});
