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

	Ext.define('item_delailsModel',{
		extend : 'Ext.data.Model',
		fields  : [
			{name:'repo_id',mapping:'repo_id'},
			{name:'trans_id',mapping:'trans_id'},
			{name:'trans_no',mapping:'trans_no'},
			{name:'type',mapping:'type'},
			{name:'stock_id',mapping:'stock_id'},
			{name:'description',mapping:'description'},
			{name:'unrecovered_cost',mapping:'unrecovered_cost',type:'float'},
			{name:'price',mapping:'price',type:'float'},
			{name:'repo_date',mapping:'repo_date'},
			{name:'reference_no',mapping:'reference_no'},
			{name:'debtor_no',mapping:'debtor_no'},
			{name:'debtor_name',mapping:'debtor_name'},
			{name:'category',mapping:'category'},
			{name:'comments',mapping:'comments'},
			{name:'branch',mapping:'branch'}
		]
	});
	Ext.define('repo_item',{
		extend : 'Ext.data.Model',
		fields  : [
			{name:'stock_id',mapping:'stock_id'},
			{name:'description',mapping:'description'},
			{name:'qty',mapping:'qty'},
			{name:'unit_price',mapping:'unit_price',type:'float'},
			{name:'serial',mapping:'serial'},
			{name:'chasis',mapping:'chasis'}
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
	var Branchstore = Ext.create('Ext.data.Store', {
		name: 'Branchstore',
		fields:['id','name','area'],
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
			property : 'id',
			direction : 'ASC'
		}]
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
	var itemStore = Ext.create('Ext.data.Store', {
		model: 'repo_item',
		name : 'itemStore',
		method : 'POST',
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_Item_details=xx',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		}
	});
	var RepoStockItem = Ext.create('Ext.data.Store', {
		model: 'item_delailsModel',
		//autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_repoStock=xx',
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
			
	var Item_view = [
		{header:'<b>Item Code</b>', dataIndex:'stock_id', width:120},
		{header:'<b>Description</b>', dataIndex:'description', width:148,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Qty</b>', dataIndex:'qty', width:60},
		{header:'<b>Unit Price</b>', dataIndex:'unit_price', width:100,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>Serial No.</b>', dataIndex:'serial', width:200,
			renderer : function(value, metaData, summaryData, dataIndex){
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Chasis No.</b>', dataIndex:'chasis', width:200,
			renderer : function(value, metaData, summaryData, dataIndex){
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		}
	];
	var submit_form = Ext.create('Ext.form.Panel', {
		id: 'form_submit',
		model: 'item_delailsModel',
		frame: true,
		height: 488,
		defaultType: 'field',
		defaults: {msgTarget: 'under', anchor: '-5'}, //msgTarget: 'side', labelAlign: 'top'
		items: [{
			xtype: 'textfield',
			id: 'stockmove_id',
			name: 'stockmove_id',
			fieldLabel: 'stockmove_id',
			//allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'trans_no',
			name: 'trans_no',
			fieldLabel: 'trans_no',
			//allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'type',
			name: 'type',
			fieldLabel: 'type',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'branch',
			name: 'branch',
			fieldLabel: 'branch',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'textfield',
				fieldLabel: 'Reference no ',
				id: 'reference_no',
				name: 'reference_no',
				allowBlank: false,
				labelWidth: 115,
				//width: 250,
				//margin: '0 70 0 0',
				readOnly: true,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype : 'datefield',
				id	  : 'repo_date',
				name  : 'repo_date',
				fieldLabel : 'Date ',
				allowBlank: false,
				labelWidth: 70,
				//width: 200,
				format : 'm/d/Y',
				fieldStyle: 'font-weight: bold; color: #210a04;',
				value: Ext.Date.format(new Date(), 'Y-m-d'),
				listeners: {

				}
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'textfield',
				fieldLabel: 'Customer name ',
				id: 'customer_name',
				name: 'customer_name',
				allowBlank: false,
				labelWidth: 115,
				width: 535,
				readOnly: true,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'textfield',
				fieldLabel: 'stock id ',
				id: 'stock_id',
				name: 'stock_id',
				allowBlank: false,
				readOnly: true,
				labelWidth: 115,
				//width: 280,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'textfield',
				fieldLabel: 'Category ',
				id: 'category',
				name: 'category',
				allowBlank: false,
				readOnly: true,
				labelWidth: 70,
				//width: 250,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'textfield',
				fieldLabel: 'Item description ',
				id: 'description',
				name: 'description',
				allowBlank: false,
				labelWidth: 115,
				width: 535,
				readOnly: true,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 	'textareafield',
				fieldLabel: 'Comments ',
				id:	'remarks',
				name: 'remarks',
				allowBlank: false,
				readOnly: true,
				labelWidth: 115,
				width: 535
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'numericfield',
				id: 'unrecovered',
				name: 'unrecovered',
				fieldLabel: 'Unrecovered Cost ',
				allowBlank:false,
				useThousandSeparator: true,
				readOnly: true,
				labelWidth: 120,
				//width: 255,
				//margin: '0 0 2 0',
				thousandSeparator: ',',
				minValue: 0,
				fieldStyle: 'font-weight: bold;color: red; text-align: right;'
			},{
				xtype: 'numericfield',
				fieldLabel: 'Price ',
				id: 'price',
				name: 'price',
				allowBlank:false,
				useThousandSeparator: true,
				thousandSeparator: ',',
				labelWidth: 65,
				//width: 250,
				minValue: 0,
				fieldStyle: 'font-weight: bold;color: #008000; text-align: right; background-color: #F2F3F4;',
				listeners: {
					afterrender: function(field) {
						field.focus(true);
					}
				}
			}]
		},{
			xtype:'gridpanel',
			id: 'ItemGrid',
			anchor:'100%',
			layout:'fit',
			title: 'Item Details',
			icon: '../js/ext4/examples/shared/icons/lorry_flatbed.png',
			loadMask: true,
			height: 168,
			store:	itemStore,
			columns: Item_view,
			columnLines: true
		}]
	});

	var submit_window = Ext.create('Ext.Window',{
		width 	: 555,
		height: 500,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form],
		buttons:[{
			text: 'Save',
			tooltip: 'Save repo repricing',
			icon: '../js/ext4/examples/shared/icons/add.png',
			single : true,
			handler:function(){
				var form_submit = Ext.getCmp('form_submit').getForm();
				if(form_submit.isValid()) {
					form_submit.submit({
						url: '?submit=info',
						waitMsg: 'Saving repo re-pricing. please wait...',
						method:'POST',
						success: function(form_submit, action) {
							RepoStockItem.proxy.extraParams = {branch: Ext.getCmp('branchcode').getValue(), category: Ext.getCmp('fcategory').getValue(), query: Ext.getCmp('search').getValue()};
							RepoStockItem.load();

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
		{header:'<b>Date</b>', dataIndex:'repo_date', width:90, renderer: Ext.util.Format.dateRenderer('m-d-Y')},
		{header:'<b>Reference No.</b>', dataIndex:'reference_no', width:120},
		{header:'<b>Customer Name</b>', dataIndex:'debtor_name', width:180,
			renderer : function(value, metaData, summaryData, dataIndex){
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Stock ID</b>', dataIndex:'stock_id', width:148,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Item Description</b>', dataIndex:'description', width:200,
			renderer : function(value, metaData, summaryData, dataIndex){
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Category</b>', dataIndex:'category', width:140,
		renderer : function(value, metaData, summaryData, dataIndex){
			metaData.tdAttr = 'data-qtip="' + value + '"';
			return value;
			}
		},
		{header:'<b>Unrecovered cost</b>', dataIndex:'unrecovered_cost', width:140,
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
		{header:'<b>Price</b>', dataIndex:'price', width:120,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				if(value == 0){
					value = '0.00';
					return '<span style="color:red;font-weight:bold;">' + (value) + '</span>';
				}else{
					return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			},
			editor: {
				xtype: 'textfield',
				allowBlank: false
			}
		},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:95,
			items:[{
				icon: '../js/ext4/examples/shared/icons/layout_content.png',
				tooltip: 'view details',
				handler: function(grid, rowIndex, colIndex) {
					var records = RepoStockItem.getAt(rowIndex);
					
					itemStore.proxy.extraParams = {repo_id: records.get('repo_id'), branch: Ext.getCmp('branchcode').getValue()};
					itemStore.load();

					submit_form.getForm().reset();

					Ext.getCmp('stockmove_id').setValue(records.get('trans_id'));
					Ext.getCmp('trans_no').setValue(records.get('trans_no'));
					Ext.getCmp('type').setValue(records.get('type'));
					Ext.getCmp('reference_no').setValue(records.get('reference_no'));
					Ext.getCmp('repo_date').setValue(records.get('repo_date'));
					Ext.getCmp('customer_name').setValue(records.get('debtor_name'));
					Ext.getCmp('stock_id').setValue(records.get('stock_id'));
					Ext.getCmp('category').setValue(records.get('category'));
					Ext.getCmp('description').setValue(records.get('description'));
					Ext.getCmp('remarks').setValue(records.get('comments'));
					Ext.getCmp('unrecovered').setValue(records.get('unrecovered_cost'));
					Ext.getCmp('branch').setValue(records.get('branch'));
					Ext.getCmp('price').setValue(records.get('price'));

					submit_window.setTitle('Repo Re-pricing Items - ' + records.get('stock_id') );
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
		fieldLabel: '<b>Branch </b>',
		store: Branchstore,
		displayField: 'name',
		valueField: 'id',
		queryMode: 'local',
		emptyText:'Select Branch',
		labelWidth: 60,
		width: 300,
		forceSelection: true,
		selectOnFocus:true,
		fieldStyle : 'text-transform: capitalize; background-color: #F2F3F4; color:green; ',
		listeners: {
			select: function(combo, record, index) {
				RepoStockItem.proxy.extraParams = {branch: combo.getValue(), category: Ext.getCmp('fcategory').getValue(), query: Ext.getCmp('search').getValue()};
				RepoStockItem.load();
			},
			afterrender: function() {
				if(Getdefloc() == "DEF"){
					Ext.getCmp("branchcode").setValue("HO");
				}else{
					Ext.getCmp("branchcode").setValue(Getdefloc());	
				}
			}
		}
	}, '-',{
		xtype: 'combobox',
		id: 'fcategory',
		name: 'fcategory',
		fieldLabel: '<b>Category </b>',
		store: storecategory,
		displayField: 'name',
		valueField: 'id',
		queryMode: 'local',
		emptyText:'Select category',
		labelWidth: 80,
		width: 280,
		forceSelection: true,
		selectOnFocus:true,
		fieldStyle : 'text-transform: capitalize; background-color: #F2F3F4; color:green; ',
		listeners: {
			select: function(combo, record, index) {
				RepoStockItem.proxy.extraParams = {branch: Ext.getCmp('branchcode').getValue(), category: combo.getValue(), query: Ext.getCmp('search').getValue()};
				RepoStockItem.load();
			},
			afterrender: function() {
				Ext.getCmp("fcategory").setValue('14');
				RepoStockItem.proxy.extraParams = {branch: Ext.getCmp('branchcode').getValue(), category: "14", query: Ext.getCmp('search').getValue()};
				RepoStockItem.load();
			}
		}
	}, '-',{
		xtype: 'searchfield',
		id:'search',
		name:'search',
		fieldLabel: '<b>Search</b>',
		labelWidth: 60,
		width: 300,
		emptyText: "Search Repo Items",
		scale: 'small',
		store: RepoStockItem,
		listeners: {
			change: function(field) {
				RepoStockItem.proxy.extraParams = {branch: Ext.getCmp('branchcode').getValue(), category: Ext.getCmp('fcategory').getValue(), query: field.getValue()};
				RepoStockItem.load();
			}
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
		width: 1238,
		tbar: tbar,
		items: [{
			xtype: 'grid',
			id: 'gridItem',
			name: 'gridItem',
			store:	RepoStockItem,
			columns: main_view,
			columnLines: true,
			autoScroll:true,
			layout:'fit',
			plugins: [cellEditing],
			frame: true,
			bbar : {
				xtype : 'pagingtoolbar',
				hidden: false,
				store : RepoStockItem,
				pageSize : itemsPerPage,
				displayInfo : false,
				emptyMsg: "No records to display",
				doRefresh : function(){
					RepoStockItem.load();
				}
			}
		}]
	});

	function Getdefloc(){
		var branch_code;
		Ext.Ajax.request({
			url : '?Getdefloc=zHun',
			async:false,
			success: function (response){
				var result = Ext.JSON.decode(response.responseText);
				branch_code = result.deflocation;
			}
		});
		return branch_code;
	};
});
