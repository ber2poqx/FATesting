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
	'Ext.selection.CellModel',
	'Ext.form.field.File',
	'Ext.ux.form.SearchField',
	'Ext.ux.form.NumericField'
]);

Ext.onReady(function(){
	Ext.QuickTips.init();
	var itemsPerPage = 5;   // set the number of items you want per page on grid.
	var showall = false;

	//------THIS IS FROM ar_installment TABLE-----//
	Ext.define('InvoiceModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'name', mapping:'name'},
			{name:'type', mapping:'type'},
			{name:'tran_date', mapping:'tran_date'},
			{name:'status', mapping:'status'},
			{name:'trmd_inv_no', mapping:'trmd_inv_no'},
			{name:'trmd_inv_type', mapping:'trmd_inv_type'}
		]
    });
	Ext.define('item_delailsModel',{
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
    Ext.define('temp_repo_model',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'trans_no', mapping:'trans_no'},
			{name:'trans_type', mapping:'trans_type'},
			{name:'reference', mapping:'reference'},
			{name:'debtor_no', mapping:'debtor_no'},
			{name:'debtor_ref', mapping:'debtor_ref'},
			{name:'debtor_name', mapping:'debtor_name'},
			{name:'category_id', mapping:'category_id'},
			{name:'category_desc', mapping:'category_desc'},
			{name:'repo_date', mapping:'repo_date'},
			{name:'module_type', mapping:'module_type'},
			{name:'days', mapping:'days'},
			{name:'model_desc', mapping:'model_desc'},
			{name:'trmd_inv_no', mapping:'trmd_inv_no'},
			{name:'trmd_inv_type', mapping:'trmd_inv_type'}
		]
    });
    Ext.define('CustomersModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'debtor_no', mapping:'debtor_no'},
			{name:'debtor_ref', mapping:'debtor_ref'},
			{name:'name', mapping:'name'}
		]
    });
	var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
        clicksToEdit: 1
    });
	
    //----------FOR INVOICE NUMBER----------//
	var ARInvoiceStore = Ext.create('Ext.data.Store', {
		model: 'InvoiceModel',
		//autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_InvoiceNo=xx',
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
	var CustomerStore = Ext.create('Ext.data.Store', {
		model: 'CustomersModel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_Customer=xx',
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
	var SIitemStore = Ext.create('Ext.data.Store', {
		model: 'item_delailsModel',
		name : 'SIitemStore',
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
    var temp_repo_store = Ext.create('Ext.data.Store', {
        model: 'temp_repo_model',
		autoLoad : true,
		//pageSize: itemsPerPage, // items per page
        proxy: {
            url: '?Get_termrepo=00',
            type: 'ajax',
            reader: {
                type: 'json',
                root: 'result',
                totalProperty  : 'total'
            }
		},
		simpleSortMode : true,
		sorters : [{
			property : 'reference',
			direction : 'ASC'
		}]
    });
    var rdy_repo_store = Ext.create('Ext.data.Store', {
        model: 'temp_repo_model',
		autoLoad : true,
		//pageSize: itemsPerPage, // items per page
        proxy: {
            url: '?Get_termrepo=00&tag=zHun',
            type: 'ajax',
            reader: {
                type: 'json',
                root: 'result',
                totalProperty  : 'total'
            }
		},
		simpleSortMode : true,
		sorters : [{
			property : 'reference',
			direction : 'ASC'
		}]
    });

	//------COLUMN FOR FROM GRID-------//
	var Temp_ColumnModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Day/s</b>', dataIndex:'days', sortable:true, width:70,
			renderer:function(value,metaData){
				var tip;
				if(value == 1){
					tip = " day";
				}else{
					tip = " days";
				}
				metaData.tdAttr = 'data-qtip="' + value + tip + '"';
				metaData.style="color: #2980b9";
				return "<b>" + value + "</b>";
			}
		},
		{header:'<b>Customer Name</b>', dataIndex:'debtor_name', sortable:true, width:150},
		{header:'<b>Invoice No.</b>', dataIndex:'reference', sortable:true, width:120},
		{header:'<b>Category</b>', dataIndex:'category_desc', sortable:true, width:110},
		{header:'<b>Item Desc.</b>', dataIndex:'model_desc', sortable:true, width:130},
		{header:'<b>Repo Date</b>', dataIndex:'repo_date', sortable:true, width:95, renderer: Ext.util.Format.dateRenderer('m/d/Y')},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:95, locked: true,
			items:[{
				icon: '../../js/ext4/examples/shared/icons/layout_content.png',
				tooltip: 'view details',
				handler: function(grid, rowIndex, colIndex) {
					var records = temp_repo_store.getAt(rowIndex);

					submit_form.getForm().reset();
					
					if(records.get('trans_type') == '56'){
						Ext.getCmp('showtrmd').setValue(true);
						Ext.getCmp('showreplce').setValue(false);
						ARInvoiceStore.proxy.extraParams = {debtor_id: records.get('debtor_no'), isTrmode: true, view: true};
						SIitemStore.proxy.extraParams = {transNo: records.get('trmd_inv_no'), transtype: records.get('trmd_inv_type')};
					}else{
						if(records.get('trmd_inv_type') == '55'){
							Ext.getCmp('showreplce').setValue(true);
							Ext.getCmp('showtrmd').setValue(false);
							ARInvoiceStore.proxy.extraParams = {debtor_id: records.get('debtor_no'), view: true, isreplce: Ext.getCmp('showreplce').getValue()};
							SIitemStore.proxy.extraParams = {transNo: records.get('trans_no'), transtype: records.get('trans_type'), isreplce: Ext.getCmp('showreplce').getValue()};
						}else{
							ARInvoiceStore.proxy.extraParams = {debtor_id: records.get('debtor_no'), view: true};
							SIitemStore.proxy.extraParams = {transNo: records.get('trans_no'), transtype: records.get('trans_type')};
						}
					}
					ARInvoiceStore.load();
					SIitemStore.load();

					Ext.getCmp('moduletype').setValue(records.get('module_type'));
					Ext.getCmp('transtype').setValue(records.get('trans_type'));
					Ext.getCmp('customercode').setValue(records.get('debtor_ref'));
					Ext.getCmp('customername').setValue(records.get('debtor_no'));
					Ext.getCmp('trans_date').setValue(records.get('repo_date'));
					Ext.getCmp('InvoiceNo').setValue(records.get('trans_no'));

					submit_window.setTitle('Repo Inquiry Maintenance - Details');
					submit_window.show();
					submit_window.setPosition(320,55);
				}
			},'-',{
				icon   : '../../js/ext4/examples/shared/icons/delete.png',
				tooltip : 'Remove from temporary repossess',
				handler : function(grid, rowIndex, colIndex){
					var records = temp_repo_store.getAt(rowIndex);
					var MsgConfirm = Ext.MessageBox.confirm('Confirm', 'Reference : <b>' + records.get('reference') + '</b><br\>Category: <b>' + records.get('category_desc') + '</b><br\>Item: <b>' + records.get('model_desc') + '</b><br\><b>Are you sure you want to remove this record? </b>', function (btn, text) {
						if (btn == 'yes') {
							Ext.Ajax.request({
								method: 'POST',
								url: '?delete=info',
								waitMsg:'Deleting Record...please wait.',
								params: {
									syspk: records.get('trans_no'),
									reference: records.get('reference'),
									type: records.get('trans_type')
								},
								success: function (response){
									var data = Ext.decode(response.responseText);
									if (data.success == 'true') {
										Ext.Msg.alert('Success', data.message);
										temp_repo_store.load();
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
	var ready_ColumnModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Customer Name</b>', dataIndex:'debtor_name', sortable:true, width:150},
		{header:'<b>Invoice No.</b>', dataIndex:'reference', sortable:true, width:120},
		{header:'<b>Category</b>', dataIndex:'category_desc', sortable:true, width:110},
		{header:'<b>Item Desc.</b>', dataIndex:'model_desc', sortable:true, width:130},
		{header:'<b>Repo Date</b>', dataIndex:'repo_date', sortable:true, width:95, renderer: Ext.util.Format.dateRenderer('m/d/Y')},
		{header:'<b>Day/s</b>', dataIndex:'days', sortable:true, width:70,
			renderer:function(value,metaData){
				metaData.tdAttr = 'data-qtip="' + value + "days" + '"';
				metaData.style="color: #2980b9";
				return "<b>" + value + "</b>";
			}
		},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:95, locked: true,
			items:[{
				icon: '../../js/ext4/examples/shared/icons/layout_content.png',
				tooltip: 'view details',
				handler: function(grid, rowIndex, colIndex) {
					var records = rdy_repo_store.getAt(rowIndex);

					submit_form.getForm().reset();

					if(records.get('trans_type') == '56'){
						Ext.getCmp('showtrmd').setValue(true);
						Ext.getCmp('showreplce').setValue(false);
						ARInvoiceStore.proxy.extraParams = {debtor_id: records.get('debtor_no'), isTrmode: true, view: true};
						SIitemStore.proxy.extraParams = {transNo: records.get('trmd_inv_no'), transtype: records.get('trmd_inv_type')};
					}else{
						if(records.get('trmd_inv_type') == '55'){
							Ext.getCmp('showreplce').setValue(true);
							Ext.getCmp('showtrmd').setValue(false);
							ARInvoiceStore.proxy.extraParams = {debtor_id: records.get('debtor_no'), view: true, isreplce: Ext.getCmp('showreplce').getValue()};
							SIitemStore.proxy.extraParams = {transNo: records.get('trans_no'), transtype: records.get('trans_type'), isreplce: Ext.getCmp('showreplce').getValue()};
						}else{
							ARInvoiceStore.proxy.extraParams = {debtor_id: records.get('debtor_no'), view: true};
							SIitemStore.proxy.extraParams = {transNo: records.get('trans_no'), transtype: records.get('trans_type')};
						}
					}
					ARInvoiceStore.load();
					SIitemStore.load();


					Ext.getCmp('moduletype').setValue(records.get('module_type'));
					Ext.getCmp('transtype').setValue(records.get('trans_type'));
					Ext.getCmp('customercode').setValue(records.get('debtor_ref'));
					Ext.getCmp('customername').setValue(records.get('debtor_no'));
					Ext.getCmp('trans_date').setValue(records.get('repo_date'));
					Ext.getCmp('InvoiceNo').setValue(records.get('trans_no'));

					submit_window.setTitle('Repo Inquiry Maintenance - Details');
					submit_window.show();
					submit_window.setPosition(320,55);
				}
			},'-',{
				icon   : '../../js/ext4/examples/shared/icons/delete.png',
				tooltip : 'Remove from temporary repossess',
				handler : function(grid, rowIndex, colIndex){
					var records = temp_repo_store.getAt(rowIndex);
					var MsgConfirm = Ext.MessageBox.confirm('Confirm', 'Reference : <b>' + records.get('reference') + '</b><br\>Category: <b>' + records.get('category_desc') + '</b><br\>Item: <b>' + records.get('model_desc') + '</b><br\><b>Are you sure you want to remove this record? </b>', function (btn, text) {
						if (btn == 'yes') {
							Ext.Ajax.request({
								method: 'POST',
								url: '?delete=info',
								waitMsg:'Deleting Record...please wait.',
								params: {
									syspk: records.get('trans_no'),
									reference: records.get('reference'),
									type: records.get('trans_type')
								},
								success: function (response){
									var data = Ext.decode(response.responseText);
									if (data.success == 'true') {
										Ext.Msg.alert('Success', data.message);
										temp_repo_store.load();
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
	var Item_view = [
		{header:'<b>Item Code</b>', dataIndex:'stock_id', width:120, editor: 'textfield'},
		{header:'<b>Description</b>', dataIndex:'description', width:148, editor: 'textfield', 
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

	var tbar = [{
		xtype: 'searchfield',
		id:'search',
		name:'search',
		fieldLabel: '<b>Search</b>',
		labelWidth: 50,
		width: 300,
		emptyText: "Search Temporary Repo...",
		scale: 'small',
		store: temp_repo_store,
		listeners: {
			change: function(field) {
				var repodueksamgrid = Ext.getCmp('repodueksamgrid');

				var selected = repodueksamgrid.getSelectionModel().getSelection();
				var areaselected;
				Ext.each(selected, function (item) {
					areaselected = item.data.id;
				});

				if(field.getValue() != ""){
					temp_repo_store.proxy.extraParams = {REFRENCE: areaselected, showall: showall, query: field.getValue()};
					temp_repo_store.load();
				}else{
					temp_repo_store.proxy.extraParams = {REFRENCE: areaselected, showall: showall};
					temp_repo_store.load();
				}
			}
		}
	}, '-', {
		text:'<b>Add</b>',
		tooltip: 'Add temporary repossess',
		icon: '../../js/ext4/examples/shared/icons/add.png',
		scale: 'small',
		handler: function(){
			//Ext.Msg.alert('Error', newURL);
			submit_form.getForm().reset();

			Ext.getCmp('moduletype').setValue('TEMP-REPO');

			submit_window.show();
			submit_window.setTitle('Repo Inquiry Maintenance - Add');
			submit_window.setPosition(320,55);
		}
	}, '->', {
		xtype: 'searchfield',
		id:'searchs',
		name:'searchs',
		fieldLabel: '<b>Search</b>',
		labelWidth: 50,
		width: 300,
		emptyText: "Search Ready to Repo...",
		scale: 'small',
		store: rdy_repo_store,
		listeners: {
			change: function(field) {
				var repodueksamgridfinal = Ext.getCmp('repodueksamgridfinal');

				var selected = repodueksamgridfinal.getSelectionModel().getSelection();
				var areaselected;
				Ext.each(selected, function (item) {
					areaselected = item.data.id;
				});

				if(field.getValue() != ""){
					rdy_repo_store.proxy.extraParams = {REFRENCE: areaselected, showall: showall, query: field.getValue()};
					rdy_repo_store.load();
				}else{
					rdy_repo_store.proxy.extraParams = {REFRENCE: areaselected, showall: showall};
					rdy_repo_store.load();
				}
			}
		}
	},'-',{
		xtype:'splitbutton',
		tooltip: 'list of reports',
		icon: '../../js/ext4/examples/shared/icons/cog_edit.png',
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
	//--------FOR WINDOW FORM--------//
	var submit_form = Ext.create('Ext.form.Panel', {
		id: 'form_submit',
		model: 'item_delailsModel',
		frame: false,
		border: true,
		defaults: {msgTarget: 'side', labelWidth: 95, anchor: '-10'}, 
		items: [{
			xtype: 'textfield',
			id: 'moduletype',
			name: 'moduletype',
			fieldLabel: 'moduletype',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'transtype',
			name: 'transtype',
			fieldLabel: 'transtype',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'invoice_date',
			name: 'invoice_date',
			fieldLabel: 'invoice date',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 2',
			items:[{
				xtype: 'checkbox',
				id: 'showtrmd',
				name: 'showtrmd',
				boxLabel: 'Show term mode account',
				margin: '0 0 0 110',
				listeners: {
					change: function(checkbox, newValue, oldValue, eOpts) {
						ARInvoiceStore.proxy.extraParams = {debtor_id: 0};
						ARInvoiceStore.load();
						SIitemStore.proxy.extraParams = {transNo: 0};
						SIitemStore.load();
						Ext.getCmp('customercode').setValue();
						Ext.getCmp('customername').setValue();
						Ext.getCmp('InvoiceNo').setValue();
						Ext.getCmp('showreplce').setValue();
					}
				}
			},{
				xtype: 'checkbox',
				id: 'showreplce',
				name: 'showreplce',
				boxLabel: 'Show replace item',
				margin: '0 0 0 110',
				listeners: {
					change: function(checkbox, newValue, oldValue, eOpts) {
						ARInvoiceStore.proxy.extraParams = {debtor_id: 0};
						ARInvoiceStore.load();
						SIitemStore.proxy.extraParams = {transNo: 0};
						SIitemStore.load();
						Ext.getCmp('customercode').setValue();
						Ext.getCmp('customername').setValue();
						Ext.getCmp('InvoiceNo').setValue();
						Ext.getCmp('showtrmd').setValue();
					}
				}
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'textfield',
				fieldLabel: 'Customer ',
				id: 'customercode',
				name: 'customercode',
				allowBlank: false,
				labelWidth: 100,
				width: 250,
				readOnly: true,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'combobox',
				id: 'customername',
				name: 'customername',
				allowBlank: false,
				store : CustomerStore,
				displayField: 'name',
				valueField: 'debtor_no',
				queryMode: 'local',
				width: 342,
				forceSelection: true,
				selectOnFocus:true,
				fieldStyle: 'font-weight: bold; color: #210a04;',
				listeners: {
					select: function(combo, record, index) {
						Ext.getCmp('customercode').setValue(record.get('debtor_ref'));
						ARInvoiceStore.proxy.extraParams = {debtor_id: record.get('debtor_no'), isTrmode: Ext.getCmp('showtrmd').getValue(), isreplce: Ext.getCmp('showreplce').getValue()};
						ARInvoiceStore.load();
						SIitemStore.proxy.extraParams = {transNo: 0};
						SIitemStore.load();
					}
				}
			},{
				xtype : 'datefield',
				id	  : 'trans_date',
				name  : 'trans_date',
				fieldLabel : 'Date ',
				allowBlank: false,
				labelWidth: 50,
				//width: 255,
				format : 'm/d/Y',
				fieldStyle: 'font-weight: bold; color: #210a04;',
				value: Ext.Date.format(new Date(), 'Y-m-d'),
				listeners: {
					select: function(combo, record, index) {
						Ext.getCmp('InvoiceNo').setValue();
						ARInvoiceStore.proxy.extraParams = {debtor_id: Ext.getCmp('customername').getValue()};
						ARInvoiceStore.load();
						SIitemStore.proxy.extraParams = {transNo: 0};
						SIitemStore.load();
					}
				}
			}]
		},{	
			xtype: 'combobox',
			id: 'InvoiceNo',
			name: 'InvoiceNo',
			allowBlank: false,
			store : ARInvoiceStore,
			displayField: 'name',
			valueField: 'id',
			queryMode: 'local',
			fieldLabel : 'Invoice No. ',
			labelWidth: 100,
			margin: '2 0 2 5',
			//width: 560,
			forceSelection: true,
			selectOnFocus:true,
			fieldStyle: 'font-weight: bold; color: #210a04;',
			listeners: {
				select: function(combo, record, index) {
					Ext.getCmp('transtype').setValue(record.get('type'));
					Ext.getCmp('invoice_date').setValue(record.get('tran_date'));
					
					if(Ext.getCmp('showtrmd').getValue() == true){
						SIitemStore.proxy.extraParams = {transNo: record.get('trmd_inv_no'), transtype: record.get('trmd_inv_type')};
					}else{
						SIitemStore.proxy.extraParams = {transNo: record.get('id'), transtype: record.get('type'), transtype: record.get('type'), isreplce: Ext.getCmp('showreplce').getValue()};
					}
					
					SIitemStore.load();
				}
			}
		},{
			xtype:'gridpanel',
			id: 'ItemGrid',
			anchor:'100%',
			layout:'fit',
			title: 'Item Details',
			icon: '../../js/ext4/examples/shared/icons/lorry_flatbed.png',
			loadMask: true,
			store:	SIitemStore,
			columns: Item_view,
			plugins: [cellEditing],
			columnLines: true
		}]
	});
	var submit_window = Ext.create('Ext.Window',{
		id: 'submit_window',
		width 	: 840,
		modal	: true,
		plain 	: true,
		border 	: true,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form],
		buttons:[{
			text: 'Save',
			tooltip: 'Save Repo Transaction',
			icon: '../../js/ext4/examples/shared/icons/add.png',
			single : true,
			handler:function(){
				var form_submit = Ext.getCmp('form_submit').getForm();
				if(form_submit.isValid()) {
					form_submit.submit({
						url: '?update=info',
						waitMsg: 'Processing transaction. please wait...',
						method:'POST',
						submitEmptyText: false,
						success: function(form_submit, action) {
							temp_repo_store.load()
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
			tooltip: 'Cancel adding Repo',
			icon: '../../js/ext4/examples/shared/icons/cancel.png',
			handler:function(){
				Ext.MessageBox.confirm('Confirm:', 'Are you sure you wish to close this window?', function (btn, text) {
					if (btn == 'yes') {
						submit_window.close();					
					}
				});
			}
		}]
	});
/*
	var rrREPO_form = Ext.create('Ext.form.Panel', {
		id: 'rrREPO_form',
		model: 'item_delailsModel',
		frame: false,
		border: true,
		defaults: {msgTarget: 'side', labelWidth: 95, anchor: '-10'}, 
		items: [{
			xtype: 'textfield',
			id: 'moduletype',
			name: 'moduletype',
			fieldLabel: 'moduletype',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'transtype',
			name: 'transtype',
			fieldLabel: 'transtype',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'invoice_date',
			name: 'invoice_date',
			fieldLabel: 'invoice date',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'textfield',
				fieldLabel: 'Customer ',
				id: 'customercode',
				name: 'customercode',
				allowBlank: false,
				labelWidth: 100,
				width: 250,
				readOnly: true,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'combobox',
				id: 'customername',
				name: 'customername',
				allowBlank: false,
				store : CustomerStore,
				displayField: 'name',
				valueField: 'debtor_no',
				queryMode: 'local',
				width: 342,
				forceSelection: true,
				selectOnFocus:true,
				fieldStyle: 'font-weight: bold; color: #210a04;',
				listeners: {
					select: function(combo, record, index) {
						Ext.getCmp('customercode').setValue(record.get('debtor_ref'));
						ARInvoiceStore.proxy.extraParams = {debtor_id: record.get('debtor_no')};
						ARInvoiceStore.load();
						SIitemStore.proxy.extraParams = {transNo: 0};
						SIitemStore.load();
					}
				}
			},{
				xtype : 'datefield',
				id	  : 'trans_date',
				name  : 'trans_date',
				fieldLabel : 'Date ',
				allowBlank: false,
				labelWidth: 50,
				//width: 255,
				format : 'm/d/Y',
				fieldStyle: 'font-weight: bold; color: #210a04;',
				value: Ext.Date.format(new Date(), 'Y-m-d'),
				listeners: {
					select: function(combo, record, index) {
						Ext.getCmp('InvoiceNo').setValue();
						ARInvoiceStore.proxy.extraParams = {debtor_id: Ext.getCmp('customername').getValue()};
						ARInvoiceStore.load();
						SIitemStore.proxy.extraParams = {transNo: 0};
						SIitemStore.load();
					}
				}
			}]
		},{	
			xtype: 'combobox',
			id: 'InvoiceNo',
			name: 'InvoiceNo',
			allowBlank: false,
			store : ARInvoiceStore,
			displayField: 'name',
			valueField: 'id',
			queryMode: 'local',
			fieldLabel : 'Invoice No. ',
			labelWidth: 100,
			margin: '2 0 2 5',
			//width: 560,
			forceSelection: true,
			selectOnFocus:true,
			fieldStyle: 'font-weight: bold; color: #210a04;',
			listeners: {
				select: function(combo, record, index) {
					Ext.getCmp('transtype').setValue(record.get('type'));
					Ext.getCmp('invoice_date').setValue(record.get('tran_date'));
					SIitemStore.proxy.extraParams = {transNo: record.get('id'), transtype: record.get('type')};
					SIitemStore.load();
				}
			}
		},{
			xtype:'gridpanel',
			id: 'ItemGrid',
			anchor:'100%',
			layout:'fit',
			title: 'Item Details',
			icon: '../../js/ext4/examples/shared/icons/lorry_flatbed.png',
			loadMask: true,
			store:	SIitemStore,
			columns: Item_view,
			columnLines: true
		}]
	});
	var rrREPO_window = Ext.create('Ext.Window',{
		id: 'rrREPO_window',
		width 	: 840,
		modal	: true,
		plain 	: true,
		border 	: true,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form],
		buttons:[{
			text: 'Save',
			tooltip: 'Save Repo Transaction',
			icon: '../../js/ext4/examples/shared/icons/add.png',
			single : true,
			handler:function(){
				var rrREPO_form = Ext.getCmp('rrREPO_form').getForm();
				if(rrREPO_form.isValid()) {
					rrREPO_form.submit({
						url: '?update=info',
						waitMsg: 'Processing transaction. please wait...',
						method:'POST',
						submitEmptyText: false,
						success: function(rrREPO_form, action) {
							temp_repo_store.load()
							Ext.Msg.alert('Success!', '<font color="green">' + action.result.message + '</font>');
							submit_window.close();
						},
						failure: function(rrREPO_form, action) {
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
			tooltip: 'Cancel adding Repo',
			icon: '../../js/ext4/examples/shared/icons/cancel.png',
			handler:function(){
				Ext.MessageBox.confirm('Confirm:', 'Are you sure you wish to close this window?', function (btn, text) {
					if (btn == 'yes') {
						submit_window.close();					
					}
				});
			}
		}]
	});
*/
	var plcy_panel =  Ext.create('Ext.panel.Panel', {
		renderTo: 'ext-form',
		width: 1300,
		//height: 400,
		tbar: tbar,
		layout: 'column',
		items: [{
			xtype: 'panel',
			title: 'for redemption accounts/items:',
			height: 300,
			columnWidth: 0.5,
			items: [{
				xtype: 'grid',
				id: 'repodueksamgrid',
				name: 'repodueksamgrid',
				store:	temp_repo_store,
				columns: Temp_ColumnModel,
				columnLines: true,
				autoScroll:true,
				layout:'fit',
				frame: true,
				bbar : {
					xtype : 'pagingtoolbar',
					store : temp_repo_store,
					hidden:false,
					//pageSize : itemsPerPage,
					//displayInfo : true,
					emptyMsg: "No records to display",
					doRefresh : function(){
						temp_repo_store.load();
					}
				}
			}]
		},{
			xtype: 'splitter',
			width: 2
		},{
			xtype: 'panel',
			title: 'Ready to repo / 31 days +',
			height: 300,
			columnWidth: 0.5,
			items: [{
				xtype: 'grid',
				id: 'repodueksamgridfinal',
				name: 'repodueksamgridfinal',
				store:	rdy_repo_store,
				columns: ready_ColumnModel,
				columnLines: true,
				autoScroll:true,
				layout:'fit',
				frame: true,
				bbar : {
					xtype : 'pagingtoolbar',
					store : rdy_repo_store,
					hidden:false,
					//pageSize : itemsPerPage,
					//displayInfo : true,
					emptyMsg: "No records to display",
					doRefresh : function(){
						rdy_repo_store.load();
					}
				}
			}]
		}]
	});
});
