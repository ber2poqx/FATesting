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
	'Ext.ux.form.SearchField'
]);

Ext.onReady(function(){
	Ext.QuickTips.init();
	var itemsPerPage = 18;   // set the number of items you want per page on grid.
	var showall = false;
	var maxfields = 10; //change this number if you want to increase/decrease adding fields.
	var caption;

	////define model for policy installment
    Ext.define('brnchplcymodel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'cstprctype_id', mapping:'cstprctype_id'},
			{name:'cstprctype_name', mapping:'cstprctype_name'},
			{name:'category_id', mapping:'category_id'},
			{name:'category_name', mapping:'category_name'},
			{name:'supplier_id', mapping:'supplier_id'},
			{name:'supplier_ref', mapping:'supplier_ref'},
			{name:'supplier_name', mapping:'supplier_name'},
			{name:'remarks', mapping:'remarks'},
			{name:'module_type', mapping:'module_type'},
			{name:'inactive', mapping:'inactive'}
		]
	});
	Ext.define('SupplierModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'debtor_no'},
			{name:'supp_ref', mapping:'supp_ref'},
			{name:'supp_name', mapping:'supp_name'}
		]
    });
	////define model for combobox
    Ext.define('comboModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'name', mapping:'name'}
		]
    });
	var selModel = Ext.create('Ext.selection.CheckboxModel', {
		checkOnly: true,
		mode: 'Single'
	});
	//------------------------------------: stores :----------------------------------------
	var filterCategory = Ext.create('Ext.data.Store', {
		name: 'storecategory',
        model: 'comboModel',
		autoLoad : true,
        proxy: {
			url: '?get_category=zHun&filterview=true',
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
	var get_cboSupplier = Ext.create('Ext.data.Store', {
		model: 'SupplierModel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_cboSupplier=zHun',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'supp_ref',
			direction : 'ASC'
		}]
	});
	var get_cboCategory = Ext.create('Ext.data.Store', {
		name: 'storecategory',
        model: 'comboModel',
		autoLoad : true,
        proxy: {
			url: '?get_category=zHun',
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
	var get_cbotypecode = Ext.create('Ext.data.Store', {
		name: 'storecashpricecode',
        model: 'comboModel',
		autoLoad : true,
        proxy: {
			url: '?get_cbotypecode=zHun',
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
	var builder_supplier = Ext.create('Ext.data.Store', {
		model: 'SupplierModel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_buildsupplier=zHun',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'dateadded',
			direction : 'DESC'
		}]
	});
	var builder_cashprice = Ext.create('Ext.data.Store', {
		model: 'brnchplcymodel',
		//autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_buildermode=CSHPRCPLCY',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'supplier_name',
			direction : 'ASC'
		}]
	});
	var builder_saleprice = Ext.create('Ext.data.Store', {
		model: 'brnchplcymodel',
		//autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_buildermode=PRCPLCY',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'supplier_name',
			direction : 'ASC'
		}]
	});
	var builder_systemcost = Ext.create('Ext.data.Store', {
		model: 'brnchplcymodel',
		//autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_buildermode=CSTPLCY',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'supplier_name',
			direction : 'ASC'
		}]
	});
	var builder_SRP = Ext.create('Ext.data.Store', {
		model: 'brnchplcymodel',
		//autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_buildermode=SRPPLCY',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'supplier_name',
			direction : 'ASC'
		}]
	});
	
	var submit_form = Ext.create('Ext.form.Panel', {
		id: 'form_submit',
		model: 'policytypeModel',
		frame: true,
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
				xtype: 'textfield',
				id: 'moduletype',
				name: 'moduletype',
				fieldLabel: 'moduletype',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'textfield',
				id: 'supp_ref',
				name: 'supp_ref',
				fieldLabel: 'supp ref',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'combobox',
				id: 'supplier',
				name: 'supplier',
				fieldLabel: '<b>Supplier </b>',
				store: get_cboSupplier,
				displayField: 'supp_name',
				valueField: 'id',
				queryMode: 'local',
				emptyText:'Select supplier',
				allowBlank: false,
				forceSelection: true,
				selectOnFocus:true,
				flex: 1,
				margin: '5 0 5 0',
				listeners: {
					select: function(combo, record, index) {
						Ext.getCmp('remarks').setValue(record.get('supp_ref') + ' - ' + Ext.getCmp('category').getRawValue() + ' - ' + Ext.getCmp('costpricetype').getRawValue());
						Ext.getCmp('supp_ref').setValue(record.get('supp_ref'));
					}
				}
			},{
				xtype: 'combobox',
				id: 'category',
				name: 'category',
				fieldLabel: '<b>Category </b>',
				allowBlank: false,
				store: get_cboCategory,
				displayField: 'name',
				valueField: 'id',
				queryMode: 'local',
				emptyText:'Select category',
				forceSelection: true,
				selectOnFocus:true,
				margin: '5 0 5 0',
				listeners: {
					select: function(combo, record, index) {
						Ext.getCmp('remarks').setValue(Ext.getCmp('supp_ref').getValue() + ' - ' + record.get('name') + ' - ' + Ext.getCmp('costpricetype').getRawValue());
					}
				}
			},{
				xtype: 'combobox',
				id: 'costpricetype',
				name: 'costpricetype',
				fieldLabel: '<b>Type </b>',
				displayField: 'name',
				valueField: 'id',
				allowBlank: false,
				store: get_cbotypecode,
				queryMode: 'local',
				emptyText:'Select type',
				forceSelection: true,
				selectOnFocus:true,
				editable: false,
				margin: '5 0 5 0',
				listeners: {
					select: function(combo, record, index) {
						Ext.getCmp('remarks').setValue(Ext.getCmp('supp_ref').getValue() + ' - ' + Ext.getCmp('category').getRawValue() + ' - ' + record.get('name'));
					}
				}
			},{			
				xtype: 'container',
				layout: 'hbox',
				defaults: {msgTarget: 'under', anchor: '-5', fieldCls:'DisabledAmountCls'},
				items:[{
					xtype: 'radiogroup',
					fieldLabel: '<b>? Active </b>',
					margin: '5 0 5 0',
					layout: {type: 'hbox'},
					items: [
						{boxLabel: '<b>Yes</b>', name: 'inactive',id:'yes', inputValue:0, margin : '0 3 0 0'},
						{boxLabel: '<b>No</b>', name: 'inactive', id:'no', inputValue:1}
					]
				}]
			},{
				xtype: 'textfield',
				id: 'remarks',
				name: 'remarks',
				fieldLabel: '<b>remarks </b>',
				maxLength: 150,
				allowBlank: false,
				readOnly: true,
				maskRe: /^([a-zA-Z0-9 _.,-`]+)$/,
				margin: '5 0 5 0',
				fieldStyle : 'text-transform: capitalize; background-color: #F2F3F4; background-image: none; color:green;'
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
			tooltip: 'Save policy installment',
			icon: '../js/ext4/examples/shared/icons/add.png',
			single : true,				
			handler:function(){
				var form_submit = Ext.getCmp('form_submit').getForm();
				if(form_submit.isValid()) {
					form_submit.submit({
						url: '?submit=info',
						waitMsg: 'Saving builders' + Ext.getCmp('remarks').getValue() + '. please wait...',
						method:'POST',
						success: function(form_submit, action) {
							//show and load new added
							var category = Ext.getCmp('filterCat').getValue();

							builder_supplier.load();
							builder_supplier.on('load', function(){
								builder_supplier.each( function (model, dataindex) {
									if (model.get('id') == Ext.getCmp('supplier').getValue()){
										Ext.getCmp('supplier_grid').getSelectionModel().select(dataindex);
									}
								});
							});
	
							builder_cashprice.proxy.extraParams = {supplier_id: Ext.getCmp('supplier').getValue(), category_id: category, showall: showall };
							builder_cashprice.load();
							builder_saleprice.proxy.extraParams = {supplier_id: Ext.getCmp('supplier').getValue(), category_id: category, showall: showall };
							builder_saleprice.load();
							builder_systemcost.proxy.extraParams = {supplier_id: Ext.getCmp('supplier').getValue(), category_id: category, showall: showall };
							builder_systemcost.load();
							builder_SRP.proxy.extraParams = {supplier_id: Ext.getCmp('supplier').getValue(), category_id: category, showall: showall };
							builder_SRP.load();

							Ext.MessageBox.confirm('Success!', action.result.message + '<br>Would you like to add more?', function (btn, text) {
								if (btn == 'yes') {
									form_submit.reset();
									Ext.getCmp('moduletype').setValue(caption);
									Ext.getCmp('yes').setValue(0)
									submit_window.setTitle('Policy builder maintenance - Add');
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
			tooltip: 'Cancel adding installment',
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
	var suppcolModel = [
		//new Ext.grid.RowNumberer(),
		{header:'<b>id</b>',dataIndex:'id',hidden: true},
		{header:'<b>Reference</b>', dataIndex:'supp_ref', sortable:true, width:98},
		{header:'<b>Supplier</b>', dataIndex:'supp_name', sortable:true, width:225, tdCls: 'custom-column',
			renderer : function renderTip(value, meta, rec, rowIndex, colIndex, store) {
				meta.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		}
	];
	var CASHPRCPLCYModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Id</b>',dataIndex:'id',hidden: true},
		{header:'<b>Category</b>', dataIndex:'category_name', sortable:true, width:206},
		{header:'<b>Cash Price Code</b>', dataIndex:'cstprctype_name', sortable:true, width:220},
		{header:'<b>Module Type</b>', dataIndex:'module_type', sortable:true, width:195},
		{header:'<b>Active</b>', dataIndex:'inactive', sortable:true, width:80,
			renderer:function(value,metaData){
				if (value === 'Yes'){
					metaData.style="color:#229954";
				}else{
					metaData.style="color:#900C3F";
				}
				return "<b>" + value + "</b>";
			}
		},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:100,
			items:[{
				icon: '../js/ext4/examples/shared/icons/application_form_edit.png',
				tooltip: 'Edit',
				handler: function(grid, rowIndex, colIndex) {
					var records = builder_cashprice.getAt(rowIndex);
					
					Ext.getCmp('CshPricelist').getSelectionModel().select(rowIndex);
					submit_form.getForm().reset();
					
					caption = records.get('module_type');
					get_cbotypecode.proxy.extraParams = {ModuleType: records.get('module_type')};
					get_cbotypecode.load();
					Ext.getCmp('syspk').setValue(records.get('id'));
					Ext.getCmp('moduletype').setValue(records.get('module_type'));
					Ext.getCmp('supp_ref').setValue(records.get('supplier_ref'));
					Ext.getCmp('supplier').setValue(records.get('supplier_id'));
					Ext.getCmp('category').setValue(records.get('category_id'));
					Ext.getCmp('costpricetype').setValue(records.get('cstprctype_id'));
					//submit_form.down('radiogroup').setValue({inactive: records.get('inactive')})
					Ext.getCmp('remarks').setValue(records.get('remarks'));
					if (records.get('inactive') === 'Yes'){
						Ext.getCmp('yes').setValue(0);
					}else{
						Ext.getCmp('no').setValue(1);
					}
					submit_window.setTitle('Policy builder maintenance  - Edit');
					submit_window.show();
					submit_window.setPosition(500,110);
				}
			},'-',{
				icon   : '../js/ext4/examples/shared/icons/fam/delete.png',
				tooltip : 'Delete',
				handler : function(grid, rowIndex, colIndex){
					var records = builder_cashprice.getAt(rowIndex);
					var MsgConfirm = Ext.MessageBox.confirm('Confirm', 'Supplier: <b>' + records.get('supplier_name') + '</b><br\> Category: <b>' + records.get('category_name') + '</b><br\> Cash Price Code: <b>' + records.get('cstprctype_name') + '</b><br\>  Are you sure you want to delete this record? ', function (btn, text) {
						if (btn == 'yes') {
							Ext.Ajax.request({
								method: 'POST',
								url: '?delete=info',
								waitMsg:'Deleting Record...please wait.',
								params: {
									syspk: records.get('id'),
									moduletype: records.get('module_type')
								},
								success: function (response){
									var data = Ext.decode(response.responseText);
									if (data.success == 'true') {
										builder_cashprice.load();
										Ext.Msg.alert('Success', data.message);
										
										if (builder_cashprice.getCount() == 0){
											builder_supplier.load();
										}
									}else{
										Ext.Msg.alert('Error', data.message);
									}
								}
							});
						}
					});
					MsgConfirm.defaultButton = 2;
				}
			}],
			renderer:function(value,metaData){
				metaData.style="background-color:#D3D3D3";
				return value;
			}
		}
	];
	var SALEPRCPLCYModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Id</b>',dataIndex:'id',hidden: true},
		{header:'<b>Category</b>', dataIndex:'category_name', sortable:true, width:206},
		{header:'<b>Price Code</b>', dataIndex:'cstprctype_name', sortable:true, width:220},
		{header:'<b>Module Type</b>', dataIndex:'module_type', sortable:true, width:195},
		{header:'<b>Active</b>', dataIndex:'inactive', sortable:true, width:80,
			renderer:function(value,metaData){
				if (value === 'Yes'){
					metaData.style="color:#229954";
				}else{
					metaData.style="color:#900C3F";
				}
				return "<b>" + value + "</b>";
			}
		},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:100,
			items:[{
				icon: '../js/ext4/examples/shared/icons/application_form_edit.png',
				tooltip: 'Edit',
				handler: function(grid, rowIndex, colIndex) {
					var records = builder_saleprice.getAt(rowIndex);
					
					Ext.getCmp('SalePricelist').getSelectionModel().select(rowIndex);
					submit_form.getForm().reset();
					
					caption = records.get('module_type');
					get_cbotypecode.proxy.extraParams = {ModuleType: records.get('module_type')};
					get_cbotypecode.load();
					Ext.getCmp('syspk').setValue(records.get('id'));
					Ext.getCmp('moduletype').setValue(records.get('module_type'));
					Ext.getCmp('supp_ref').setValue(records.get('supplier_ref'));
					Ext.getCmp('supplier').setValue(records.get('supplier_id'));
					Ext.getCmp('category').setValue(records.get('category_id'));
					Ext.getCmp('costpricetype').setValue(records.get('cstprctype_id'));
					//submit_form.down('radiogroup').setValue({inactive: records.get('inactive')})
					Ext.getCmp('remarks').setValue(records.get('remarks'));
					if (records.get('inactive') === 'Yes'){
						Ext.getCmp('yes').setValue(0);
					}else{
						Ext.getCmp('no').setValue(1);
					}
					submit_window.setTitle('Policy builder maintenance  - Edit');
					submit_window.show();
					submit_window.setPosition(500,110);
				}
			},'-',{
				icon   : '../js/ext4/examples/shared/icons/fam/delete.png',
				tooltip : 'Delete',
				handler : function(grid, rowIndex, colIndex){
					var records = builder_saleprice.getAt(rowIndex);
					var MsgConfirm = Ext.MessageBox.confirm('Confirm', 'Supplier: <b>' + records.get('supplier_name') + '</b><br\> Category: <b>' + records.get('category_name') + '</b><br\> Cash Price Code: <b>' + records.get('cstprctype_name') + '</b><br\>  Are you sure you want to delete this record? ', function (btn, text) {
						if (btn == 'yes') {
							Ext.Ajax.request({
								method: 'POST',
								url: '?delete=info',
								waitMsg:'Deleting Record...please wait.',
								params: {
									syspk: records.get('id'),
									moduletype: records.get('module_type')
								},
								success: function (response){
									var data = Ext.decode(response.responseText);
									if (data.success == 'true') {
										builder_saleprice.load();
										Ext.Msg.alert('Success', data.message);
										
										if (builder_saleprice.getCount() == 0){
											builder_supplier.load();
										}
									}else{
										Ext.Msg.alert('Error', data.message);
									}
								}
							});
						}
					});
					MsgConfirm.defaultButton = 2;
				}
			}],
			renderer:function(value,metaData){
				metaData.style="background-color:#D3D3D3";
				return value;
			}
		}
	];
	var COSTPLCYModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Id</b>',dataIndex:'id',hidden: true},
		{header:'<b>Category</b>', dataIndex:'category_name', sortable:true, width:206},
		{header:'<b>Cost Code</b>', dataIndex:'cstprctype_name', sortable:true, width:220},
		{header:'<b>Module Type</b>', dataIndex:'module_type', sortable:true, width:195},
		{header:'<b>Active</b>', dataIndex:'inactive', sortable:true, width:80,
			renderer:function(value,metaData){
				if (value === 'Yes'){
					metaData.style="color:#229954";
				}else{
					metaData.style="color:#900C3F";
				}
				return "<b>" + value + "</b>";
			}
		},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:100,
			items:[{
				icon: '../js/ext4/examples/shared/icons/application_form_edit.png',
				tooltip: 'Edit',
				handler: function(grid, rowIndex, colIndex) {
					//update_widow(builder_cashprice, rowIndex, storecashpricecode);
					var records = builder_systemcost.getAt(rowIndex);
					
					Ext.getCmp('SystemCostlist').getSelectionModel().select(rowIndex);
					submit_form.getForm().reset();

					caption = records.get('module_type');
					get_cbotypecode.proxy.extraParams = {ModuleType: records.get('module_type')};
					get_cbotypecode.load();
					Ext.getCmp('syspk').setValue(records.get('id'));
					Ext.getCmp('moduletype').setValue(records.get('module_type'));
					Ext.getCmp('supp_ref').setValue(records.get('supplier_ref'));
					Ext.getCmp('supplier').setValue(records.get('supplier_id'));
					Ext.getCmp('category').setValue(records.get('category_id'));
					Ext.getCmp('costpricetype').setValue(records.get('cstprctype_id'));
					//submit_form.down('radiogroup').setValue({inactive: records.get('inactive')})
					Ext.getCmp('remarks').setValue(records.get('remarks'));
					if (records.get('inactive') === 'Yes'){
						Ext.getCmp('yes').setValue(0);
					}else{
						Ext.getCmp('no').setValue(1);
					}
					submit_window.setTitle('Policy builder maintenance  - Edit');
					submit_window.show();
					submit_window.setPosition(500,110);
				}
			},'-',{
				icon   : '../js/ext4/examples/shared/icons/fam/delete.png',
				tooltip : 'Delete',
				handler : function(grid, rowIndex, colIndex){
					var records = builder_systemcost.getAt(rowIndex);
					var MsgConfirm = Ext.MessageBox.confirm('Confirm', 'Supplier: <b>' + records.get('supplier_name') + '</b><br\> Category: <b>' + records.get('category_name') + '</b><br\> Cash Price Code: <b>' + records.get('cstprctype_name') + '</b><br\>  Are you sure you want to delete this record? ', function (btn, text) {
						if (btn == 'yes') {
							Ext.Ajax.request({
								method: 'POST',
								url: '?delete=info',
								waitMsg:'Deleting Record...please wait.',
								params: {
									syspk: records.get('id'),
									moduletype: records.get('module_type')
								},
								success: function (response){
									var data = Ext.decode(response.responseText);
									if (data.success == 'true') {
										builder_systemcost.load();
										Ext.Msg.alert('Success', data.message);

										if (builder_systemcost.getCount() == 0){
											builder_supplier.load();
										}
									}else{
										Ext.Msg.alert('Error', data.message);
									}
								}
							});
						}
					});
					MsgConfirm.defaultButton = 2;
				}
			}],
			renderer:function(value,metaData){
				metaData.style="background-color:#D3D3D3";
				return value;
			}
		}
	];
	var SRPPLCYModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Id</b>',dataIndex:'id',hidden: true},
		{header:'<b>Category</b>', dataIndex:'category_name', sortable:true, width:206},
		{header:'<b>SRP Code</b>', dataIndex:'cstprctype_name', sortable:true, width:220},
		{header:'<b>Module Type</b>', dataIndex:'module_type', sortable:true, width:195},
		{header:'<b>Active</b>', dataIndex:'inactive', sortable:true, width:80,
			renderer:function(value,metaData){
				if (value === 'Yes'){
					metaData.style="color:#229954";
				}else{
					metaData.style="color:#900C3F";
				}
				return "<b>" + value + "</b>";
			}
		},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:100,
			items:[{
				icon: '../js/ext4/examples/shared/icons/application_form_edit.png',
				tooltip: 'Edit',
				handler: function(grid, rowIndex, colIndex) {
					//update_widow(builder_cashprice, rowIndex, storecashpricecode);
					var records = builder_SRP.getAt(rowIndex);
					
					Ext.getCmp('SRPlist').getSelectionModel().select(rowIndex);
					submit_form.getForm().reset();

					caption = records.get('module_type');
					get_cbotypecode.proxy.extraParams = {ModuleType: records.get('module_type')};
					get_cbotypecode.load();
					Ext.getCmp('syspk').setValue(records.get('id'));
					Ext.getCmp('moduletype').setValue(records.get('module_type'));
					Ext.getCmp('supp_ref').setValue(records.get('supplier_ref'));
					Ext.getCmp('supplier').setValue(records.get('supplier_id'));
					Ext.getCmp('category').setValue(records.get('category_id'));
					Ext.getCmp('costpricetype').setValue(records.get('cstprctype_id'));
					//submit_form.down('radiogroup').setValue({inactive: records.get('inactive')})
					Ext.getCmp('remarks').setValue(records.get('remarks'));
					if (records.get('inactive') === 'Yes'){
						Ext.getCmp('yes').setValue(0);
					}else{
						Ext.getCmp('no').setValue(1);
					}
					submit_window.setTitle('Policy builder maintenance  - Edit');
					submit_window.show();
					submit_window.setPosition(500,110);
				}
			},'-',{
				icon   : '../js/ext4/examples/shared/icons/fam/delete.png',
				tooltip : 'Delete',
				handler : function(grid, rowIndex, colIndex){
					var records = builder_SRP.getAt(rowIndex);
					var MsgConfirm = Ext.MessageBox.confirm('Confirm', 'Supplier: <b>' + records.get('supplier_name') + '</b><br\> Category: <b>' + records.get('category_name') + '</b><br\> Cash Price Code: <b>' + records.get('cstprctype_name') + '</b><br\>  Are you sure you want to delete this record? ', function (btn, text) {
						if (btn == 'yes') {
							Ext.Ajax.request({
								method: 'POST',
								url: '?delete=info',
								waitMsg:'Deleting Record...please wait.',
								params: {
									syspk: records.get('id'),
									moduletype: records.get('module_type')
								},
								success: function (response){
									var data = Ext.decode(response.responseText);
									if (data.success == 'true') {
										builder_SRP.load();
										Ext.Msg.alert('Success', data.message);
										
										if (builder_SRP.getCount() == 0){
											builder_supplier.load();
										}
									}else{
										Ext.Msg.alert('Error', data.message);
									}
								}
							});
						}
					});
					MsgConfirm.defaultButton = 2;
				}
			}],
			renderer:function(value,metaData){
				metaData.style="background-color:#D3D3D3";
				return value;
			}
		}
	];

	var plcy_panel =  Ext.create('Ext.panel.Panel', { 
        renderTo: 'builderplcy',
		id: 'plcy_panel',
        frame: false,
		width: 1200,
        //labelAlign: 'left',
		layout: 'column',
		items: [{
			xtype: 'grid',
			id: 'supplier_grid',
			name: 'supplier_grid',
			columnWidth: 0.30,
			//width: 250,
			loadMask: true,
			frame: true,
			store:	builder_supplier,
			columns: suppcolModel,
			columnLines: true,
			selModel: selModel,
			selType : 'checkboxmodel',
			bbar : {
				xtype : 'pagingtoolbar',
				hidden:true,
				store : builder_supplier,
				pageSize : itemsPerPage,
				displayInfo : false,
				emptyMsg: "No records to display",
				doRefresh : function(){
					builder_supplier.load();
				}
			},
			listeners: {
				cellclick: function(view, td, cellIndex, record, tr, rowIndex, e, eOpts) {
					//var search = Ext.getCmp('search');
					Ext.getCmp('supplier_grid').getSelectionModel().select(rowIndex);
					var category = Ext.getCmp('filterCat').getValue();
					
					builder_cashprice.proxy.extraParams = {supplier_id: record.get('id'), category_id: category, showall: showall };
					builder_cashprice.load();
					builder_saleprice.proxy.extraParams = {supplier_id: record.get('id'), category_id: category, showall: showall };
					builder_saleprice.load();
					builder_systemcost.proxy.extraParams = {supplier_id: record.get('id'), category_id: category, showall: showall };
					builder_systemcost.load();
					builder_SRP.proxy.extraParams = {supplier_id: record.get('id'), category_id: category, showall: showall };
					builder_SRP.load();
				},
				afterrender: function(grid) {
					var supplier = Ext.getCmp('supplier_grid');
					builder_supplier.on('load', function(){
						builder_supplier.each( function (model, dataindex) {
							supplier.getSelectionModel().select(0);
							
							if(dataindex == 0 && Ext.getCmp('supplier').getValue() == null){
								builder_cashprice.proxy.extraParams = {supplier_id: model.get('id'), showall: showall };
								builder_cashprice.load();
								builder_saleprice.proxy.extraParams = {supplier_id: model.get('id'), showall: showall };
								builder_saleprice.load();
								builder_systemcost.proxy.extraParams = {supplier_id: model.get('id'), showall: showall };
								builder_systemcost.load();
								builder_SRP.proxy.extraParams = {supplier_id: model.get('id'), showall: showall };
								builder_SRP.load();

							}
						});
					});
				}
			}
		},{
			xtype: 'splitter',
			width: 2
		},{
			xtype: 'tabpanel',
			columnWidth: 0.70, //0.8
			scale: 'small',
			items:[{
				xtype:'gridpanel',
				id: 'CshPricelist',
				//anchor:'100%',
				//title: '<div style="color: red;">Sales Pricing</div>',
				title: 'Cash Price',
				autoScroll: true,
				loadMask: true,
				store:	builder_cashprice,
				columns: CASHPRCPLCYModel,
				columnLines: true,
				frame: true,
				layout:'fit',
				bbar : {
					xtype : 'pagingtoolbar',
					hidden: false,
					store : builder_cashprice,
					pageSize : itemsPerPage,
					displayInfo : false,
					emptyMsg: "No records to display",
					doRefresh : function(){
						builder_cashprice.load();
					}
				},
				tbar : [{
					xtype: 'button',
					text : 'Add',
					tooltip: 'Click to add builders',
					icon: '../js/ext4/examples/shared/icons/add.png',
					handler : function() {
						submit_form.getForm().reset();
						Ext.getCmp('moduletype').setValue("CSHPRCPLCY");
						caption = 'CSHPRCPLCY';
						get_cbotypecode.proxy.extraParams = {ModuleType: caption};
						get_cbotypecode.load();
						Ext.getCmp('yes').setValue(0)
						submit_window.show();
						submit_window.setTitle('Policy builder maintenance - Add');
						submit_window.setPosition(330,50);
					}
				}]
			},{
				xtype:'gridpanel',
				id: 'SalePricelist',
				//anchor:'100%',
				//title: '<div style="color: red;">Sales Pricing</div>',
				title: 'Sale Price',
				autoScroll: true,
				loadMask: true,
				store:	builder_saleprice,
				columns: SALEPRCPLCYModel,
				columnLines: true,
				frame: true,
				layout:'fit',
				bbar : {
					xtype : 'pagingtoolbar',
					hidden: false,
					store : builder_cashprice,
					pageSize : itemsPerPage,
					displayInfo : false,
					emptyMsg: "No records to display",
					doRefresh : function(){
						builder_cashprice.load();
					}
				},
				tbar : [{
					xtype: 'button',
					text : 'Add',
					tooltip: 'Click to add builders',
					icon: '../js/ext4/examples/shared/icons/add.png',
					handler : function() {
						submit_form.getForm().reset();
						Ext.getCmp('moduletype').setValue("PRCPLCY");
						caption = 'PRCPLCY';
						get_cbotypecode.proxy.extraParams = {ModuleType: caption};
						get_cbotypecode.load();
						Ext.getCmp('yes').setValue(0)
						submit_window.show();
						submit_window.setTitle('Policy builder maintenance - Add');
						submit_window.setPosition(330,50);
					}
				}]
			},{
				xtype:'gridpanel',
				id: 'SystemCostlist',
				//anchor:'100%',
				//title: '<div style="color: red;">Sales Pricing</div>',
				title: 'System Cost',
				autoScroll: true,
				loadMask: true,
				store:	builder_systemcost,
				columns: COSTPLCYModel,
				columnLines: true,
				frame: true,
				layout:'fit',
				bbar : {
					xtype : 'pagingtoolbar',
					hidden: false,
					store : builder_systemcost,
					pageSize : itemsPerPage,
					displayInfo : false,
					emptyMsg: "No records to display",
					doRefresh : function(){
						builder_systemcost.load();
					}
				},
				tbar : [{
					xtype: 'button',
					text : 'Add',
					tooltip: 'Click to add builders',
					icon: '../js/ext4/examples/shared/icons/add.png',
					handler : function() {
						submit_form.getForm().reset();
						Ext.getCmp('moduletype').setValue("CSTPLCY");
						caption = 'CSTPLCY';
						get_cbotypecode.proxy.extraParams = {ModuleType: caption};
						get_cbotypecode.load();
						Ext.getCmp('yes').setValue(0)
						submit_window.show();
						submit_window.setTitle('Policy builder maintenance - Add');
						submit_window.setPosition(330,50);
					}
				}]
			},{
				xtype:'gridpanel',
				id: 'SRPlist',
				//anchor:'100%',
				//title: '<div style="color: red;">Sales Pricing</div>',
				title: 'SRP Pricing',
				autoScroll: true,
				loadMask: true,
				store:	builder_SRP,
				columns: SRPPLCYModel,
				columnLines: true,
				frame: true,
				layout:'fit',
				bbar : {
					xtype : 'pagingtoolbar',
					hidden: false,
					store : builder_SRP,
					pageSize : itemsPerPage,
					displayInfo : false,
					emptyMsg: "No records to display",
					doRefresh : function(){
						builder_SRP.load();
					}
				},
				tbar : [{
					xtype: 'button',
					text : 'Add',
					tooltip: 'Click to add builders',
					icon: '../js/ext4/examples/shared/icons/add.png',
					handler : function() {
						submit_form.getForm().reset();
						Ext.getCmp('moduletype').setValue("SRPPLCY");
						caption = 'SRPPLCY';
						get_cbotypecode.proxy.extraParams = {ModuleType: caption};
						get_cbotypecode.load();
						Ext.getCmp('yes').setValue(0)
						submit_window.show();
						submit_window.setTitle('Policy builder maintenance - Add');
						submit_window.setPosition(330,50);
					}
				}]
			}],
			tabBar: {
				items: [{
					xtype: 'tbfill'
				},{
					xtype: 'combobox',
					id: 'filterCat',
					name: 'filterCat',
					fieldLabel: '<span style="color: yellow;font-weight:bold">Filter by category</span>',
					store: filterCategory,
					displayField: 'name',
					valueField: 'id',
					queryMode: 'local',
					emptyText:'view by type',
					labelWidth: 130,
					forceSelection: true,
					selectOnFocus:true,
					editable: false,
					listeners: {
						select: function(combo, record, index) {
							var SelectedSpplr

							Ext.each(Ext.getCmp('supplier_grid').getSelectionModel().getSelection(), function (item) {
								SelectedSpplr = item.data.id
							});

							builder_cashprice.proxy.extraParams = {supplier_id: SelectedSpplr, category_id: record.get('id'), showall: showall };
							builder_cashprice.load();
							builder_saleprice.proxy.extraParams = {supplier_id: SelectedSpplr, category_id: record.get('id'), showall: showall };
							builder_saleprice.load();
							builder_systemcost.proxy.extraParams = {supplier_id: SelectedSpplr, category_id: record.get('id'), showall: showall };
							builder_systemcost.load();
							builder_SRP.proxy.extraParams = {supplier_id: SelectedSpplr, category_id: record.get('id'), showall: showall };
							builder_SRP.load();
						},
						afterrender: function() {
							Ext.getCmp("filterCat").setValue(0);
						}
					}
				},{
					xtype:'splitbutton',
					//text: '<b>Policy Maintenance</b>',
					tooltip: 'Select policy maintenance.',
					icon: '../js/ext4/examples/shared/icons/cog_edit.png',
					scale: 'small',
					menu:[{
						text: '<b>Cash Price Type</b>',
						icon: '../js/ext4/examples/shared/icons/table_gear.png',
						href: '../sales/manage/sales_cash_types.php?',
						hrefTarget : '_blank'
					},{
						text: '<b>Sales Price Type</b>',
						icon: '../js/ext4/examples/shared/icons/table_gear.png',
						href: '../sales/manage/sales_types.php?',
						hrefTarget : '_blank'
					},{
						text: '<b>System Cost Types</b>',
						icon: '../js/ext4/examples/shared/icons/table_gear.png',
						href: '../purchasing/manage/supplier_costyps.php?',
						hrefTarget : '_blank'
					},{
						text: '<b>SRP Types</b>',
						icon: '../js/ext4/examples/shared/icons/table_gear.png',
						href: '../inventory/manage/item_srp_area_type.php?',
						hrefTarget : '_blank'
					}, '-',{
						text: '<b>Items</b>',
						icon: '../js/ext4/examples/shared/icons/cart.png',
						href: '../inventory/manage/items.php',
						hrefTarget : '_blank'
					},{
						text: '<b>Item Categories</b>',
						icon: '../js/ext4/examples/shared/icons/chart_line.png',
						href: '../inventory/manage/item_categories.php',
						hrefTarget : '_blank'
					}]
				}]
			}
		}]
	});
});
