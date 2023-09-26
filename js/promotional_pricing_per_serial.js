var newURL = window.location.protocol + "//" + window.location.host + window.location.pathname;
Ext.Loader.setConfig({enabled: true});
Ext.Loader.setPath('Ext.ux', '../../js/ext4/examples/ux/');
Ext.require([
    'Ext.grid.*',
    'Ext.data.*',
    'Ext.panel.*',
    'Ext.form.*',
	'Ext.window.*',
    'Ext.tab.*',
	'Ext.selection.CheckboxModel',
	'Ext.selection.CheckboxModel',
	'Ext.selection.CellModel',
	'Ext.form.field.File',
	'Ext.ux.form.SearchField',
	'Ext.ux.form.NumericField'

]);

Ext.onReady(function(){
	Ext.QuickTips.init();
	var itemsPerPage = 20;   // set the number of items you want per page on grid.
	var showall = false;
	var maxfields = 10; //change this number if you want to increase/decrease adding fields.

	////define model for policy installment
    Ext.define('Prom_Price_model',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'branchcode', mapping:'branchcode'},
			{name:'stock_id_item', mapping:'serialise_item_code'},
			{name:'serial', mapping:'serialise_lot_no'},
			{name:'chasis', mapping:'serialise_chasis_no'},
			{name:'location_code', mapping:'serialise_loc_code'},
			{name:'price', mapping:'serialise_lcp_promotional_price'},
			{name:'STATUS', mapping:'STATUS'},
			{name:'STATUSSERACH', mapping:'STATUSSERACH'}, 
			{name:'item_code_stock', mapping:'item_code_stock'},
			{name:'categdescription', mapping:'description'},
			{name:'code_brand', mapping:'brand'},
			{name:'code_brand_name', mapping:'name'},
			{name:'itemstock_id', mapping:'stock_id'}																																																																					
		]
	});

	Ext.define('Prom_Categ_model',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'branchcode', mapping:'branchcode'},
			{name:'category_id', mapping:'category_id'},
			{name:'description', mapping:'description'}																	
		]
	});

	Ext.define('Prom_Brand_model',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'brand_id', mapping:'id'},
			{name:'branchcode', mapping:'branchcode'},
			{name:'brand_name', mapping:'name'},																
			{name:'brand_categ', mapping:'category_id'}																
		]
	});

	Ext.define('Prom_item_code_model',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'branchcode', mapping:'branchcode'},
			{name:'stock_item_code', mapping:'stock_id'},																
			{name:'categ_item_id', mapping:'category_id'},																
			{name:'code_item_brand', mapping:'brand'}																																	
		]
	});
	//------------------------------------: stores :----------------------------------------
	var Branchstore = Ext.create('Ext.data.Store', {
		name: 'Branchstore',
		fields:['id', 'name', 'area'],
		autoLoad : true,
        proxy: {
			url: '?getbranch=00',
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

	var Promotional_pricing_store = Ext.create('Ext.data.Store', {
        model: 'Prom_Price_model',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
        proxy: {
			url: '?get_promotional_allDB=Dusal',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'stock_id_item',
			direction : 'ASC'
		}]
	});

	var Promotional_pricing_category_store = Ext.create('Ext.data.Store', {
        model: 'Prom_Categ_model',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
        proxy: {
			url: '?get_promotional_category_allDB=robert',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'item_code_stock',
			direction : 'ASC'
		}]
	});

	var Promotional_pricing_brand_store = Ext.create('Ext.data.Store', {
        model: 'Prom_Brand_model',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
        proxy: {
			url: '?get_promotional_brand_allDB=tah0',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'brand_name',
			direction : 'ASC'
		}]
	});

	var Promotional_pricing_item_store = Ext.create('Ext.data.Store', {
        model: 'Prom_item_code_model',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
        proxy: {
			url: '?get_promotional_itemcode=gwapo',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'stock_item_code',
			direction : 'ASC'
		}]
	});

	var submit_form = Ext.create('Ext.form.Panel', {
		id: 'form_submit',
		model: 'Prom_Price_model',
		frame: true,
		defaultType: 'field',
		defaults: {margin: '2 0 2 5', msgTarget: 'under', anchor: '-5'}, //msgTarget: 'side', labelAlign: 'top'
		items: [{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'textfield',
				fieldLabel: '<b>Category</b>',
				id: 'id_category',
				name: 'id_category',
				labelWidth: 120,
				width: 325,
				readOnly: true,
				allowBlank: false,
				fieldStyle: 'font-weight: bold; color: #003566;'
			},{
				xtype: 'textfield',
				fieldLabel: '<b>Brand</b>',
				id: 'name_brand',
				name: 'name_brand',
				labelWidth: 50,
				width: 261,
				readOnly: true,
				allowBlank: false,
				fieldStyle: 'font-weight: bold; color: #003566;'
			}]
		},{

			xtype: 'textfield',
			fieldLabel: '<b>Stock ID</b>',
			id: 'codestock',
			name: 'codestock',
			labelWidth: 120,
			width: 250,
			readOnly: true,
			allowBlank: false,
			fieldStyle: 'font-weight: bold; color: #003566;'	
		},{
			xtype: 'textfield',
			fieldLabel: '<b>Item Code</b>',
			id: 'CodesItem',
			name: 'CodesItem',
			labelWidth: 120,
			width: 250,
			readOnly: true,
			allowBlank: false,
			fieldStyle: 'font-weight: bold; color: #003566;'
		},{
			xtype: 'textfield',
			fieldLabel: '<b>Serial number</b>',
			id: 'Lot_no',
			name: 'Lot_no',
			labelWidth: 120,
			width: 250,
			readOnly: true,
			allowBlank: false,
			fieldStyle: 'font-weight: bold; color: #210a04;'
		},{
			xtype: 'textfield',
			id: 'Chasis_no',
			name: 'Chasis_no',
			fieldLabel: '<b>Chasis number</b>',
			allowBlank: true,
			readOnly: true,
			labelWidth: 120,
			fieldStyle: 'font-weight: bold; color: #210a04;'
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'textfield',
				id: 'loca_code',
				name: 'loca_code',
				fieldLabel: '<b>Location</b>',
				allowBlank: false,
				readOnly: true,
				width: 325,	
				labelWidth: 120,
				fieldStyle: 'font-weight: bold; color: #003566;'
			},{
				xtype: 'numericfield',
				id: 'price_serial',
				name: 'price_serial',
				fieldLabel: '<b>Price</b>',
				allowBlank: true,
				readOnly: false,				
				width: 261,	
				labelWidth: 50,							
				fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
			}]
		}]		
	});
	var submit_window = Ext.create('Ext.Window',{
		width 	: 600,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form],
		buttons:[{
			text: 'Save',
			tooltip: 'Update Price',
			icon: '../../js/ext4/examples/shared/icons/add.png',
			single : true,				
			handler:function(){
				var form_submit = Ext.getCmp('form_submit').getForm();
				if(form_submit.isValid()) {
					form_submit.submit({
						url: '?update=info',
						waitMsg: 'Save Price. please wait...',
						method:'GET',
						success: function(form_submit, action) {
							//show and load new added
							Promotional_pricing_store.load()
							Ext.Msg.alert('Success!', '<font color="green">' + action.result.message + '</font>');
							submit_window.close();
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
			tooltip: 'Cancel builder',
			icon: '../../js/ext4/examples/shared/icons/cancel.png',
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
	//---------------------------------------------------------------------------------------
	//for policy setup column model
	var Promotional_price_Header = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Stock ID</b>', dataIndex:'itemstock_id', sortable:true, width:150,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:black;font-weight:bold;">' + Ext.util.Format.number(value) +'</span>';
			}
		},
		{header:'<b>Item Code</b>', dataIndex:'stock_id_item', sortable:true, width:180,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:black;font-weight:bold;">' + Ext.util.Format.number(value) +'</span>';
			}
		},
		{header:'<b>Serial Number</b>', dataIndex:'serial', sortable:true, width:183,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:black; font-weight:bold;">' + Ext.util.Format.number(value) +'</span>';
			}
		},
		{header:'<b>Chasis Number</b>', dataIndex:'chasis', sortable:true, width:183,			
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:black; font-weight:bold;">' + Ext.util.Format.number(value) +'</span>';
			}			
		},
		{header:'<b>Category</b>', dataIndex:'categdescription', sortable:true, width:125,			
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:#003566; font-weight:bold;">' + Ext.util.Format.number(value) +'</span>';
			}			
		},
		{header:'<b>Brand</b>', dataIndex:'code_brand_name', sortable:true, width:125,			
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:#003566; font-weight:bold;">' + Ext.util.Format.number(value) +'</span>';
			}			
		},
		{header:'<b>Location</b>', dataIndex:'location_code', sortable:true, width:90,			
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:#003566; font-weight:bold;">' + Ext.util.Format.number(value) +'</span>';
			}			
		},
		{header:'<b>Price</b>', dataIndex:'price', sortable:true, width:100,			
			renderer: Ext.util.Format.Currency = function(value){

				return '<span style="color:green; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';	
			}			
		},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:80,
			items:[{
				icon: '../../js/ext4/examples/shared/icons/layout_content.png',
				tooltip: 'Edit Price',
				handler: function(grid, rowIndex, colIndex) {
					submit_form.getForm().reset();

					var records = Promotional_pricing_store.getAt(rowIndex);
					
					Ext.getCmp('id_category').setValue(records.get('categdescription'));
					Ext.getCmp('name_brand').setValue(records.get('code_brand_name'));
					Ext.getCmp('codestock').setValue(records.get('itemstock_id'));
					Ext.getCmp('CodesItem').setValue(records.get('stock_id_item'));
					Ext.getCmp('Lot_no').setValue(records.get('serial'));
					Ext.getCmp('Chasis_no').setValue(records.get('chasis'));
					Ext.getCmp('loca_code').setValue(records.get('location_code'));
					Ext.getCmp('price_serial').setValue(records.get('price'));

					submit_window.setTitle('Promotional Price - Per Serial - ' + Ext.getCmp('branchcode').getRawValue());
					submit_window.show();
					submit_window.setPosition(330,90);
				}
			}]
		}
	];
	
	var tbar = [{
		xtype: 'combobox',
		id: 'branchcode',
		name: 'branchcode',
		fieldLabel: '<b>Branch</b>',
		store: Branchstore,
		displayField: 'name',
		valueField: 'id',
		queryMode: 'local',
		emptyText:'Select Branch',
		labelWidth: 50,
		width: 400,
		forceSelection: true,
		selectOnFocus:true,
		anyMatch: true,
		fieldStyle : 'background-color: #F2F3F4; color:green; font-weight:bold;',
		listeners: {
			select: function(combo, record, index) {
				var search = Ext.getCmp("search").getValue();

				Promotional_pricing_store.proxy.extraParams = {branch: Ext.getCmp('branchcode').getValue(), category: Ext.getCmp('Itemcateg_search').getValue(), brand: Ext.getCmp('Itembrand_search').getValue(), query: Ext.getCmp('search').getValue()};
				Promotional_pricing_store.load();

				Promotional_pricing_category_store.proxy.extraParams = {branch: Ext.getCmp('branchcode').getValue(), category: Ext.getCmp('Itemcateg_search').getValue(), brand: Ext.getCmp('Itembrand_search').getValue()};
				Promotional_pricing_category_store.load();	

				Promotional_pricing_item_store.proxy.extraParams = {branch: Ext.getCmp('branchcode').getValue(), category: Ext.getCmp('Itemcateg_search').getValue(), brand: Ext.getCmp('Itembrand_search').getValue()};
				Promotional_pricing_item_store.load();	
			}
		}
	}, '-',{	
		xtype: 'combobox',
		id: 'Itemcateg_search',
		name: 'Itemcateg_search',
		fieldLabel: '<b>Category</b>',
		store: Promotional_pricing_category_store,
		displayField: 'description',
		valueField: 'category_id',
		queryMode: 'local',
		emptyText:'Select Category',
		labelWidth: 60,
		width: 230,
		forceSelection: true,
		selectOnFocus:true,
		fieldStyle : 'background-color: #F2F3F4; color:green; font-weight:bold;',
		listeners: {
			select: function(combo, record, index) {
				var search = Ext.getCmp("search").getValue();

				Promotional_pricing_store.proxy.extraParams = {branch: Ext.getCmp('branchcode').getValue(), category: Ext.getCmp('Itemcateg_search').getValue(), brand: Ext.getCmp('Itembrand_search').getValue(), query: Ext.getCmp('search').getValue()};
				Promotional_pricing_store.load();

				Promotional_pricing_brand_store.proxy.extraParams = {branch: Ext.getCmp('branchcode').getValue(), category: Ext.getCmp('Itemcateg_search').getValue(), brand: Ext.getCmp('Itembrand_search').getValue()};
				Promotional_pricing_brand_store.load();	

				Promotional_pricing_item_store.proxy.extraParams = {branch: Ext.getCmp('branchcode').getValue(), category: Ext.getCmp('Itemcateg_search').getValue(), brand: Ext.getCmp('Itembrand_search').getValue()};
				Promotional_pricing_item_store.load();	
			}
		}			
	}, '-',{
		xtype: 'combobox',
		id: 'Itembrand_search',
		name: 'Itembrand_search',
		fieldLabel: '<b>Brand</b>',
		store: Promotional_pricing_brand_store,
		displayField: 'brand_name',
		valueField: 'brand_id',
		queryMode: 'local',
		emptyText:'Select Brand',
		labelWidth: 50,
		width: 200,
		forceSelection: true,
		selectOnFocus:true,
		fieldStyle : 'background-color: #F2F3F4; color:green; font-weight:bold;',
		listeners: {
			select: function(combo, record, index) {
				var search = Ext.getCmp("search").getValue();

				Promotional_pricing_store.proxy.extraParams = {branch: Ext.getCmp('branchcode').getValue(), category: Ext.getCmp('Itemcateg_search').getValue(), brand: Ext.getCmp('Itembrand_search').getValue(), query: Ext.getCmp('search').getValue()};
				Promotional_pricing_store.load();

				Promotional_pricing_item_store.proxy.extraParams = {branch: Ext.getCmp('branchcode').getValue(), category: Ext.getCmp('Itemcateg_search').getValue(), brand: Ext.getCmp('Itembrand_search').getValue()};
				Promotional_pricing_item_store.load();	
			}
		}	
	}, '-',{

		xtype: 'combobox',
		id: 'search',
		name: 'search',
		fieldLabel: '<b>Search</b>',
		store: Promotional_pricing_item_store,
		displayField: 'stock_item_code',
		valueField: 'stock_item_code',
		queryMode: 'local',
		emptyText:'Select Item',
		labelWidth: 50,
		width: 270,
		forceSelection: true,
		selectOnFocus:true,
		fieldStyle : 'background-color: #F2F3F4; color:green; font-weight:bold;',
		listeners: {
			change: function(field) {

				Promotional_pricing_store.proxy.extraParams = {branch: Ext.getCmp('branchcode').getValue(), category: Ext.getCmp('Itemcateg_search').getValue(), brand: Ext.getCmp('Itembrand_search').getValue(), query: Ext.getCmp('search').getValue()};
				Promotional_pricing_store.load();
			}
		}
		/*
		xtype: 'searchfield',
		id:'search',
		name:'search',
		fieldLabel: '<b>Search</b>',
		labelWidth: 50,
		width: 250,
		emptyText: "Search by Serial..",
		scale: 'small',
		store: Promotional_pricing_store,
		fieldStyle : 'background-color: #F2F3F4; color:green; font-weight:bold;',
		listeners: {
			change: function(field) {

				Promotional_pricing_store.proxy.extraParams = {Promotional: Ext.getCmp('branchcode').getValue(), query: field.getValue()};
				Promotional_pricing_store.load();
			}
		}
		*/
	}];

	var builder_panel =  Ext.create('Ext.panel.Panel', { 
        renderTo: 'ext-form',
		id: 'builder_panel',
        frame: false,
		width: 1250,
		tbar: tbar,
		items: [{
			xtype: 'grid',
			title: 'Promotional Pricing - Per Serial - Details',
			id: 'Approval_grid',
			name: 'Approval_grid',
			store:	Promotional_pricing_store,
			columns: Promotional_price_Header,
			columnLines: true,
			autoScroll:true,
			layout:'fit',
			frame: true,
			bbar : {
				xtype : 'pagingtoolbar',
				hidden: false,
				store : Promotional_pricing_store,
				pageSize : itemsPerPage,
				displayInfo : false,
				emptyMsg: "No records to display",
				doRefresh : function(){
					Promotional_pricing_store.load();
					
				}
			}
		}]
	});
});
