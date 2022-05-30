var newURL = window.location.protocol + "//" + window.location.host + window.location.pathname;
Ext.Loader.setConfig({enabled: true});
Ext.Loader.setPath('Ext.ux', '../../js/ext4/examples/ux/');
Ext.require([
    'Ext.grid.*',
    'Ext.data.*',
	'Ext.dd.*',
    'Ext.panel.*',
    'Ext.form.*',
	'Ext.window.*',
    'Ext.tab.*',
	'Ext.selection.CheckboxModel',
	'Ext.selection.CellModel',
	'Ext.form.field.File',
	'Ext.ux.form.SearchField',
	/*'Ext.ux.form.NumericField'
	/*'Ext.ux.grid.gridsummary',*/
]);
Ext.onReady(function(){
	Ext.QuickTips.init();
	var itemsPerPage = 18;   // set the number of items you want per page on grid.
	var GridItemOnTab = 7;
	var showall = false;

	Ext.override(Ext.form.NumberField, {
		forcePrecision : false,
	
		valueToRaw: function(value) {
			var me = this,
				decimalSeparator = me.decimalSeparator;
			value = me.parseValue(value);
			value = me.fixPrecision(value);
			value = Ext.isNumber(value) ? value : parseFloat(String(value).replace(decimalSeparator, '.'));
			if (isNaN(value))
			{
			  value = '';
			} else {
			  value = me.forcePrecision ? value.toFixed(me.decimalPrecision) : parseFloat(value);
			  value = String(value).replace(".", decimalSeparator);
			}
			return value;
		}
	});
	////define model for policy installment
    Ext.define('ARInstlmntmodel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'ar_id', mapping:'ar_id'},
			{name:'invoice_date', mapping:'invoice_date'},
			{name:'invoice_no', mapping:'invoice_no'},
			{name:'customer_code', mapping:'customer_code'},
			{name:'customer_name', mapping:'customer_name'},
			{name:'branch_code', mapping:'branch_code'},
			{name:'delivery_no', mapping:'delivery_no'},
			{name:'reference_no', mapping:'reference_no'},
			{name:'invoice_type', mapping:'invoice_type'},
			{name:'installplcy_id', mapping:'installplcy_id'},
			{name:'months_term', mapping:'months_term'},
			{name:'rebate', mapping:'rebate'},
			{name:'fin_rate', mapping:'fin_rate'},
			{name:'firstdue_date', mapping:'firstdue_date'},
			{name:'maturity_date', mapping:'maturity_date'},
			{name:'outs_ar_amount', mapping:'outs_ar_amount'},
			{name:'ar_amount', mapping:'ar_amount'},
			{name:'lcp_amount', mapping:'lcp_amount'},
			{name:'dp_amount', mapping:'dp_amount'},
			{name:'amortn_amount', mapping:'amortn_amount'},
			{name:'total_amount', mapping:'total_amount'},
			{name:'category_id', mapping:'category_id'},
			{name:'category_desc', mapping:'category_desc'},
			{name:'comments', mapping:'comments'},
			{name:'prepared_by', mapping:'prepared_by'},
			{name:'approved_by', mapping:'approved_by'},
			{name:'approved_date', mapping:'approved_date'},
			{name:'status', mapping:'status'},
			{name:'moduletype', mapping:'moduletype'},
			{name:'inactive', mapping:'inactive'}
		]
	});
    Ext.define('Itemmodel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'stock_id', mapping:'stock_id'},
			{name:'qty', mapping:'qty'},
			{name:'unit_price', mapping:'unit_price'},
			{name:'serial_no', mapping:'serial_no'},
			{name:'chasis_no', mapping:'chasis_no'}
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
	var ARInstallQstore = Ext.create('Ext.data.Store', {
		model: 'ARInstlmntmodel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_arinstallment=00',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'invoice_date',
			direction : 'DESC'
		}]
	});
	var Itemstore = Ext.create('Ext.data.Store', {
		model: 'Itemmodel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_aritem=00',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'invoice_date',
			direction : 'DESC'
		}]
	});
	var InvTypeStore = Ext.create('Ext.data.Store',{
		fields: ['id','name'],
		autoLoad: true,
		data : 	[
			{"id":"x","name":"All"},
            {"id":"new","name":"Brand new"},
            {'id':'repo','name':'Repo'}
        ]
	});
	var fstatusStore = Ext.create('Ext.data.Store',{
		fields: ['id','name'],
		autoLoad: true,
		data : 	[
			{"id":"x","name":"All"},
            {"id":"0","name":"Draft"},
            {'id':'1','name':'Approved'},
			{'id':'2','name':'Closed'},
			{'id':'3','name':'Disapproved'}
        ]
	});
    var categorystore = Ext.create('Ext.data.Store', {
		name: 'categorystore',
        model: 'comboModel',
		autoLoad : true,
        proxy: {
			url: newURL+'?getcategory=1',
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
	var ItemcolModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Id</b>',dataIndex:'plcyd_id',hidden: true},
		{header:'<b>Stock id</b>', dataIndex:'stock_id', sortable:true, width:110},
		{header:'<b>Qty</b>', dataIndex:'qty', sortable:true, width:50},
		{header:'<b>Unit Price</b>', dataIndex:'unit_price', sortable:true, width:90,
			renderer: Ext.util.Format.Currency = function(value){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>Serial/Engine No.</b>', dataIndex:'serial_no', sortable:true, width:230},
		{header:'<b>Chasis No.</b>', dataIndex:'chasis_no', sortable:true, width:230}
	];
	//---------------------------------------------------------------------------------------
	//view detailed records form
	var submit_form = Ext.create('Ext.form.Panel', {
		id: 'form_submit',
		model: 'ARInstlmntmodel',
		frame: false,
		defaultType: 'field',
		items: [{
			xtype: 'textfield',
			id: 'id',
			name: 'id',
			fieldLabel: 'FormID',
			allowBlank: false,
			readOnly: true,
			hidden: true
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'textfield',
				fieldLabel: '<b>Invoice No. </b>',
				id: 'invoice_no',
				name: 'invoice_no',
				labelWidth: 115,
				margin: '0 20 0 0',
				readOnly: true
			},{
				xtype : 'datefield',
				id	  : 'invoice_date',
				name  : 'invoice_date',
				fieldLabel : '<b>Invoice Date </b>',
				allowBlank: false,
				labelWidth: 108,
				format : 'm/d/Y'
				//value: new Date()
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'textfield',
				fieldLabel: '<b>Delivery No. </b>',
				id: 'delivery_no',
				name: 'delivery_no',
				labelWidth: 115,
				margin: '0 20 0 0',
				readOnly: true
			},{
				xtype: 'combobox',
				fieldLabel: '<b>Invoice Type </b>',
				id: 'invoice_type',
				name: 'invoice_type',
				labelWidth: 108,
				readOnly: true
			}]
		},{
			xtype: 'textfield',
			fieldLabel: '<b>Customer </b>',
			id: 'customer',
			name: 'customer',
			margin: '0 0 2 5',
			labelWidth: 115,
			width: 595
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'textfield',
				fieldLabel: '<b>Category </b>',
				id: 'category',
				name: 'category',
				labelWidth: 115,
				margin: '0 20 0 0',
				readOnly: true
			},{
				xtype: 'numberfield',
				fieldLabel: '<b>Term </b>',
				id: 'months_term',
				name: 'months_term',
				fieldStyle: 'text-align: right;',
				labelWidth: 108,
				maxLength: 5,
				minValue: 0,
				value: 0
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'numberfield',
				fieldLabel: '<b>AR Amount </b>',
				id: 'ar_amount',
				name: 'ar_amount',
				labelWidth: 115,
				margin: '0 20 0 0',
				fieldStyle: 'text-align: right;',
				useThousandSeparator: true,
				forcePrecision: true,
				thousandSeparator: ',',
				decimalPrecision:2,
				minValue: 0,
				value: 0
			},{
				xtype: 'numberfield',
				id: 'rebate',
				name: 'rebate',
				fieldLabel: '<b>Rebate </b>',
				fieldStyle: 'text-align: right;',
				labelWidth: 108,
				enforceMaxLength: true,
				minValue: 0,
				value: 0
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'numberfield',
				fieldLabel: '<b>Outstanding AR </b>',
				id: 'outs_ar_amount',
				name: 'outs_ar_amount',
				labelWidth: 115,
				margin: '0 20 0 0',
				fieldStyle: 'text-align: right;',
				enforceMaxLength: true,
				minValue: 0,
				value: 0
			},{
				xtype: 'numberfield',
				fieldLabel: '<b>Financing rate </b>',
				id: 'financing_rate',
				name: 'financing_rate',
				fieldStyle: 'text-align: right;',
				labelWidth: 108,
				enforceMaxLength: true,
				allowDecimals: true,
				minValue: 0,
				value: 0
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'numberfield',
				fieldLabel: '<b>LCP Amount </b>',
				id: 'lcp_amount',
				name: 'lcp_amount',
				labelWidth: 115,
				margin: '0 20 0 0',
				fieldStyle: 'text-align: right;',
				enforceMaxLength: true,
				minValue: 0,
				value: 0
			},{
				xtype: 'numberfield',
				fieldLabel: '<b>Down Payment </b>',
				id: 'dp_amount',
				name: 'dp_amount',
				fieldStyle: 'text-align: right;',
				labelWidth: 108,
				enforceMaxLength: true,
				minValue: 0,
				value: 0
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'numberfield',
				fieldLabel: '<b>Total Amount </b>',
				id: 'total_amount',
				name: 'total_amount',
				labelWidth: 115,
				margin: '0 20 0 0',
				fieldStyle: 'text-align: right;',
				enforceMaxLength: true,
				minValue: 0,
				value: 0
			},{
				xtype: 'numberfield',
				fieldLabel: '<b>Amortization </b>',
				id: 'amort_amount',
				name: 'amort_amount',
				fieldStyle: 'text-align: right;',
				labelWidth: 108,
				enforceMaxLength: true,
				minValue: 0,
				value: 0
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype : 'datefield',
				id	  : 'firstdue_date',
				name  : 'firstdue_date',
				fieldLabel : '<b>First Due Date </b>',
				margin: '0 20 0 0',
				allowBlank: false,
				labelWidth: 115,
				format : 'm/d/Y'
			},{
				xtype : 'datefield',
				id	  : 'maturity_date',
				name  : 'maturity_date',
				fieldLabel : '<b>Maturity Date </b>',
				labelWidth: 108,
				format : 'm/d/Y'
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'textfield',
				fieldLabel: '<b>Prepared by </b>',
				id: 'prepared_by',
				name: 'prepared_by',
				labelWidth: 115,
				margin: '0 20 0 0',
				readOnly: true
			},{
				xtype: 'textfield',
				fieldLabel: '<b>Branch code </b>',
				id: 'branch_code',
				name: 'branch_code',
				labelWidth: 108,
				readOnly: true
			}]
		},{
			xtype: 'textfield',
			fieldLabel: '<b>Description </b>',
			id: 'comments',
			name: 'comments',
			margin: '0 0 5 5',
			labelWidth: 115,
			width: 595
		},{
			xtype: 'grid',
			id: 'itemgrid',
			name: 'itemgrid',
			loadMask: true,
			frame: true,
			store:	Itemstore,
			columns: ItemcolModel,
			columnLines: true,
			autoScroll:true,
			layout:'fit',
		}]
	});
	var submit_window = Ext.create('Ext.Window',{
		id: 'submit_window',
		width 	: 750,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		style: 'margin:0 auto;margin-top:0px;',
		icon: '../js/ext4/examples/shared/newicon/icon_component.gif',
		items:[submit_form],
		buttons:[{
			text:'<b>Close</b>',
			tooltip: 'Close Window...',
			icon: '../ext4/examples/shared/icons/cancel.png',
			handler:function(){
				submit_form.getForm().reset();
				submit_window.close();
			}
		}]
	});
	//---------------------------------------------------------------------------------------
	//for ar installment column model
	var ColumnModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Id</b>',dataIndex:'plcyd_id',hidden: true},
		{header:'<b>Inv.Date</b>', dataIndex:'invoice_date', sortable:true, width:100, renderer: Ext.util.Format.dateRenderer('m-d-Y')},
		{header:'<b>Invoice No.</b>', dataIndex:'invoice_no', sortable:true, width:120},
		{header:'<b>Customer Name</b>', dataIndex:'customer_name', sortable:true, width:190},
		{header:'<b>Type</b>', dataIndex:'invoice_type', sortable:true, width:60,
			renderer:function(value,metaData){
				if (value === 'repo'){
					metaData.style="color: #4927e1";
				}else{
					metaData.style="color:#229954";
				}
				return "<b>" + value + "</b>";
			}
		},
		{header:'<b>Category</b>', dataIndex:'category_desc', sortable:true, width:100},
		{header:'<b>Term</b>', dataIndex:'months_term', sortable:true, width:59},
		{header:'<b>Financing</b>', dataIndex:'fin_rate', sortable:true, width:88,
			renderer: Ext.util.Format.Currency = function(value){
				 if (value==0){
					   value = '0.00%';
					   return (value);
				 }else{
					   return (value + '%')
				 }
			}
		},
		{header:'<b>Downpayment</b>', dataIndex:'dp_amount', sortable:true, width:82,
			renderer: Ext.util.Format.Currency = function(value){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>Due Date</b>', dataIndex:'firstdue_date', sortable:true, width:88, renderer: Ext.util.Format.dateRenderer('m-d-Y')},
		{header:'<b>Maturity Date</b>', dataIndex:'maturity_date', sortable:true, width:95, renderer: Ext.util.Format.dateRenderer('m-d-Y')},
		{header:'<b>AR Amount</b>', dataIndex:'ar_amount', sortable:true, width:100,
			renderer: Ext.util.Format.Currency = function(value){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>Amort Amount</b>', dataIndex:'amortn_amount', sortable:true, width:78,
			renderer: Ext.util.Format.Currency = function(value){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>Status</b>', dataIndex:'status', sortable:true, width:80,
			renderer:function(value,metaData){
				if (value === 'Draft'){
					metaData.style="color: #2980b9";
				}else if(value === 'Approved'){
					metaData.style="color:#1e8449 ";
				}else{
					metaData.style="color:#229954";
				}
				return "<b>" + value + "</b>";
			}
		},
		/*{header:'<b>Active</b>', dataIndex:'inactive', sortable:true, width:68,
			renderer:function(value,metaData){
				if (value === 'Yes'){
					metaData.style="color:#229954";
				}else{
					metaData.style="color:#900C3F";
				}
				return "<b>" + value + "</b>";
			}
		},*/
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:77,
			items:[{
				icon: '../js/ext4/examples/shared/icons/table_go.png',
				tooltip: 'view details',
				handler: function(grid, rowIndex, colIndex) {
					var records = ARInstallQstore.getAt(rowIndex);
					submit_form.getForm().reset();
					Itemstore.proxy.extraParams = {sinumber: records.get('ar_id')};
					Itemstore.load();
					Ext.getCmp('id').setValue(records.get('ar_id'));
					Ext.getCmp('invoice_no').setValue(records.get('invoice_no'));
					Ext.getCmp('invoice_date').setValue(records.get('invoice_date'));
					Ext.getCmp('delivery_no').setValue(records.get('delivery_no'));
					Ext.getCmp('invoice_type').setValue(records.get('invoice_type'));
					Ext.getCmp('customer').setValue(records.get('customer_code') + ' - '+ records.get('customer_name'));
					Ext.getCmp('category').setValue(records.get('category_desc'));
					Ext.getCmp('months_term').setValue(records.get('months_term'));
					Ext.getCmp('ar_amount').setValue(records.get('ar_amount'));
					Ext.getCmp('rebate').setValue(records.get('rebate'));
					Ext.getCmp('outs_ar_amount').setValue(records.get('outs_ar_amount'));
					Ext.getCmp('financing_rate').setValue(records.get('fin_rate'));
					Ext.getCmp('lcp_amount').setValue(records.get('lcp_amount'));
					Ext.getCmp('dp_amount').setValue(records.get('dp_amount'));
					Ext.getCmp('total_amount').setValue(records.get('total_amount'));
					Ext.getCmp('amort_amount').setValue(records.get('amortn_amount'));
					Ext.getCmp('firstdue_date').setValue(records.get('firstdue_date'));
					Ext.getCmp('maturity_date').setValue(records.get('maturity_date'));
					Ext.getCmp('prepared_by').setValue(records.get('prepared_by'));
					Ext.getCmp('branch_code').setValue(records.get('branch_code'));
					Ext.getCmp('comments').setValue(records.get('comments'));
					submit_window.setTitle('A/R Installment - ' + records.get('invoice_no'));
					submit_window.show();
					submit_window.setPosition(320,23);
				}
			},'-',{
				icon   : '../js/ext4/examples/shared/icons/fam/delete.png',
				tooltip : 'Closed',
				handler : function(grid, rowIndex, colIndex){
					/*var records = policyInstallstore.getAt(rowIndex);
					var MsgConfirm = Ext.MessageBox.confirm('Confirm', 'Installment policy code: <b>' + records.get('plcyd_code') + '</b><br\> Are you sure you want to delete this record? ', function (btn, text) {
						if (btn == 'yes') {
							Ext.Ajax.request({
								method: 'POST',
								url: '?delete=info',
								waitMsg:'Deleting Record...please wait.',
								params: {
									syspk: records.get('plcyd_id'),
									plcycode: records.get('plcyd_code')
								},
								success: function (response){
									var data = Ext.decode(response.responseText);
									var gridplcy = Ext.getCmp('instllplcygrid');
									if (data.success == 'true') {
										Ext.Msg.alert('Success', data.message);
										policyInstallstore.load();
										if (gridplcy.getCount() = 0){
											plcytermstore.load();
										}
									}else{
										Ext.Msg.alert('Error', data.message);
									}
								}
							});
						//}
					});
					MsgConfirm.defaultButton = 2;*/
				}
			}],
			renderer:function(value,metaData){
				metaData.style="background-color:#D3D3D3";
				return value;
			}
		}
	];

	var tbar = [{
		xtype: 'combobox',
		id: 'invtype',
		name: 'invtype',
		fieldLabel: '<b>Type </b>',
		store: InvTypeStore,
		displayField: 'name',
		valueField: 'id',
		queryMode: 'local',
		emptyText:'view by type',
		labelWidth: 50,
		width: 180,
		forceSelection: true,
		selectOnFocus:true,
		listeners: {
			select: function(combo, record, index) {
				ARInstallQstore.load({
					params: { InvType: combo.getValue() }
				});
			},
			afterrender: function() {
				Ext.getCmp("invtype").setValue("x");
			}
		}
	}, {
		xtype: 'combobox',
		id: 'fstatus',
		name: 'fstatus',
		fieldLabel: '<b>Status</b>',
		store: fstatusStore,
		displayField: 'name',
		valueField: 'id',
		queryMode: 'local',
		emptyText:'view by status',
		labelWidth: 50,
		width: 190,
		forceSelection: true,
		selectOnFocus:true,
		listeners: {
			select: function(combo, record, index) {
				ARInstallQstore.load({
					params: { InvType: combo.getValue() }
				});
			},
			afterrender: function() {
				Ext.getCmp("fstatus").setValue("x");
			}
		}
	}, {
		xtype: 'searchfield',
		id:'search',
		name:'search',
		fieldLabel: '<b>Search</b>',
		labelWidth: 50,
		width: 300,
		emptyText: "Search by policy code...",
		scale: 'small',
		store: ARInstallQstore,
		listeners: {
			change: function(field) {
				/*if(field.getValue() != ""){
					ARInstallQstore.proxy.extraParams = {plcyterm: termselected, showall: showall, query: field.getValue()};
					ARInstallQstore.load();
				}else{
					ARInstallQstore.proxy.extraParams = {plcyterm: termselected, showall: showall};
					ARInstallQstore.load();
				}*/
			}
		}
	}, '->' ,{
		xtype:'splitbutton',
		text: '<b>Reports</b>',
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

	var ARInquiry =  Ext.create('Ext.panel.Panel', { 
        renderTo: 'ARINQRY',
		id: 'ARInquiry',
        frame: false,
		tbar: tbar,
		items: [{
			xtype: 'grid',
			id: 'ARINQRYGRID',
			name: 'ARINQRYGRID',
			store:	ARInstallQstore,
			columns: ColumnModel,
			columnLines: true,
			autoScroll:true,
			layout:'fit',
			frame: true,
			bbar : {
				xtype : 'pagingtoolbar',
				hidden: false,
				store : ARInstallQstore,
				pageSize : itemsPerPage,
				displayInfo : false,
				emptyMsg: "No records to display",
				doRefresh : function(){
					ARInstallQstore.load();
				}/*,
				items:[{
					xtype: 'checkbox',
					id: 'cststatus',
					name: 'cststatus',
					boxLabel: 'Show also Inactive',
					listeners: {
						change: function(column, rowIdx, checked, eOpts){
							var search = Ext.getCmp('search');
							
							if(checked){
								showall = false;
							}else{
								showall = true;
							}
							if(search.getValue() != ""){
								ARInstallQstore.proxy.extraParams = {branch: branchselected, showall: showall, query: search.getValue()};
							}else{
								ARInstallQstore.proxy.extraParams = {branch: branchselected, showall: showall};
							}
							ARInstallQstore.load();
						}
					}
				}]*/
			}
		}]
	});
});
