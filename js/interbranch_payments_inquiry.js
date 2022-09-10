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
	'Ext.ux.form.NumericField'
	/*'Ext.ux.grid.gridsummary',*/
]);

Ext.onReady(function(){
	Ext.QuickTips.init();
	var itemsPerPage = 18;   // set the number of items you want per page on grid.
	var showall = false;
	var maxfields = 10; //change this number if you want to increase/decrease adding fields.

    Ext.define('interb_paymnt_model',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'branch_code_from', mapping:'branch_code_from'},
			{name:'branch_name', mapping:'branch_name'},
			{name:'branch_gl_code', mapping:'branch_gl_code'},
			{name:'debtor_id', mapping:'debtor_id'},
			{name:'debtor_ref', mapping:'debtor_ref'},
			{name:'debtor_name', mapping:'debtor_name'},
			{name:'trans_date', mapping:'trans_date'},
			{name:'ref_no', mapping:'ref_no'},
			{name:'amount', mapping:'amount'},
			{name:'remarks', mapping:'remarks'},
			{name:'prepared_by', mapping:'prepared_by'},
			{name:'status', mapping:'status'},
			{name:'approved_by', mapping:'approved_by'},
			{name:'type', mapping:'type'}
		]
	});
	var fstatusStore = Ext.create('Ext.data.Store',{
		fields: ['id','name'],
		autoLoad: true,
		data : 	[
            {"id":"draft","name":"Draft"},
            {'id':'approved','name':'Approved'}
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
	Ext.define('comboModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'name', mapping:'name'},
			{name:'type', mapping:'type'}
		]
    });
	Ext.define('AllocationModel',{
		extend : 'Ext.data.Model',
		fields  : [
			{name:'loansched_id',mapping:'loansched_id'},
			{name:'debtor_id',mapping:'debtor_id'},
			{name:'trans_no',mapping:'trans_no'},
			{name:'date_due',mapping:'date_due'},
			{name:'maturity_date',mapping:'maturity_date'},
			{name:'mosterm',mapping:'mosterm'},
			{name:'amortization',mapping:'amortization'},
			{name:'ar_due',mapping:'ar_due',type:'float'},
			{name:'rebate',mapping:'rebate',type:'float'},
			{name:'penalty',mapping:'penalty',type:'float'},
			{name:'penaltyBal',mapping:'penaltyBal',type:'float'},
			{name:'partialpayment',mapping:'partialpayment',type: 'float'},
			{name:'totalpayment',mapping:'totalpayment',type: 'float'},
			{name:'alloc_amount',mapping:'alloc_amount',type:'float'},
			{name:'grossPM',mapping:'grossPM', type:'float'}
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
	var smCheckAmortGrid = Ext.create('Ext.selection.CheckboxModel',{
		mode: 'SINGLE'
	});
	//------------------------------------: stores :----------------------------------------
	var FromStore = Ext.create('Ext.data.Store',{
		fields: ['id','name'],
		autoLoad: true,
		data : 	[
			{"id":"0","name":"From Branch"},
			{"id":"1","name":"From lending"}
        ]
	});
	var CollectionTypeStore = Ext.create('Ext.data.Store', {
		fields: ['id','name'],
		autoLoad : true,
		proxy: {
			url: '?get_CollectionType=00',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true
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
	var qqinterb_store = Ext.create('Ext.data.Store', {
		model: 'interb_paymnt_model',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_incoming_interb=xx',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true
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
	var ARInvoiceStore = Ext.create('Ext.data.Store', {
		model: 'comboModel',
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
	var AllocationStore = Ext.create('Ext.data.Store', {
		model: 'AllocationModel',
		name : 'AllocationStore',
		method : 'POST',
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_aloc=xx',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		}
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
	var cashierStore = Ext.create('Ext.data.Store', {
		model: 'comboModel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_CashierTellerCol=xx',
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

	//---------------------------------------------------------------------------------------
	var AllocationHeader = [
		{header:'<b>Id</b>',dataIndex:'loansched_id',hidden: true},
		{header:'<b>Trans No.</b>', dataIndex:'trans_no', width:70, hidden: true}, //align:'center',
		{header:'<b>No.</b>', dataIndex:'mosterm', width:50},
		{header:'<b>Due Date</b>', dataIndex:'date_due', width:90, locked: true},
		{header:'<b>Monthly</b>', dataIndex:'amortization', align:'right', width:130,
			renderer : function(value, metaData, summaryData, dataIndex){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>Penalty</b>', dataIndex:'penalty', width:95, align:'right',
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:red;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			}
		},
		{header:'<b>Rebate</b>', dataIndex:'rebate', width:90, align:'right',
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:green;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			}
		},
		{header:'<b>Partial</b>', dataIndex:'partialpayment', width:80, /* align:'right'*/
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:blue;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			},
		},
		{header:'<b>Total Payment</b>', dataIndex:'totalpayment', width:140, align:'right', locked: true, summaryType: 'sum',
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:red;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			},
			summaryRenderer: function(value, summaryData, dataIndex){
				return '<span style="color:blue;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';									
			}
		},
		{header:'<b>Credit Amount</b>', dataIndex:'alloc_amount', width:138, align:'right', locked: true, summaryType: 'sum', 
			renderer: Ext.util.Format.Currency = function(value, metaData, record, rowIdx, colIdx, store, view){
				metaData.tdAttr = 'data-qtip="<b> Click to Enter Payment Here! </b>"';
				if(value == 0){
					value = '0.00';
					return '<span style="color:red;">' + (value) + '</span>';
				}else{
					return '<span style="color:green;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			},
			summaryRenderer: function(value, summaryData, dataIndex){
				return '<span style="color:blue;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';									
			}
		},
		{header:'<b>Total AR Due</b>', dataIndex:'ar_due', width:115, /*align:'right',*/
			renderer : function(value, metaData, summaryData, dataIndex){
				return Ext.util.Format.number(value, '0,000.00');
			}
		}
	];
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
	//for policy setup column model
	var InterB_Payment_Header = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Date</b>', dataIndex:'trans_date', sortable:true, width:80, renderer: Ext.util.Format.dateRenderer('m-d-Y')},
		{header:'<b>From Branch</b>', dataIndex:'branch_name', sortable:true, width:182,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Customer Name</b>', dataIndex:'debtor_name', sortable:true, width:210},
		{header:'<b>Reference No.</b>', dataIndex:'ref_no', sortable:true, width:120},
		{header:'<b>Amount</b>', dataIndex:'amount', sortable:true, width:87,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
			}
		},
		{header:'<b>Remarks</b>', dataIndex:'remarks', sortable:true, width:170,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Prepared by</b>', dataIndex:'prepared_by', sortable:true, width:130,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Status</b>', dataIndex:'status', sortable:true, width:88,
			renderer:function(value,metaData){
				metaData.tdAttr = 'data-qtip="' + value + '"';
				if (value === 'draft'){
					metaData.style="color: #2980b9";
				}else if(value === 'Approved'){
					metaData.style="color:#409a2e ";
					//'<a href="../lending/ar_installment.php?">'+value+'</a>';
				}else {
					metaData.style="color:#f00a2a ";
				}
				return "<b>" + value + "</b>";
			}
		},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:95,
			items:[{
				icon: '../../js/ext4/examples/shared/icons/layout_content.png',
				tooltip: 'view details',
				handler: function(grid, rowIndex, colIndex) {
					var records = qqinterb_store.getAt(rowIndex);

					CustomerStore.proxy.extraParams = {debtor_id: records.get('debtor_id'), name: records.get('debtor_name')};
					CustomerStore.load();
					ARInvoiceStore.proxy.extraParams = {debtor_id: records.get('debtor_id')};
					ARInvoiceStore.load();

					AllocationStore.proxy.extraParams = {transNo: 0};
					AllocationStore.load();

					submit_form.getForm().reset();
					
					cashierStore.load({
						callback: function(records) {                 
							Ext.getCmp('cashier').setValue(records[i].get('id'));
						}
					});
					
					Ext.getCmp('syspk').setValue(records.get('id'));
					Ext.getCmp('moduletype').setValue('ALCN-INTERB');
					Ext.getCmp('pay_type').setValue('alloc');
					Ext.getCmp('customercode').setValue(records.get('debtor_ref'));
					Ext.getCmp('customername').setValue(records.get('debtor_id'));
					Ext.getCmp('debit_acct').setValue(records.get('branch_gl_code'));
					Ext.getCmp('paymentType').setValue('alloc')
					Ext.getCmp('collectType').setValue(0)
					Ext.getCmp('tenderd_amount').setValue(records.get('amount'));
					Ext.getCmp('trans_date').setValue(records.get('trans_date'));
					Ext.getCmp('islending').setValue(records.get('type'));
					Ext.getCmp('remarks').setValue("Inter branch payment from " + records.get('branch_name') + " with reference number " + records.get('ref_no'));
					
					GetCashierPrep();

					submit_window.setTitle('Inter-Branch Payment Details');
					submit_window.show();
					submit_window.setPosition(320,50);
				}
			}]
		}
	];

	var submit_form = Ext.create('Ext.form.Panel', {
		id: 'form_submit',
		model: 'AllocationModel',
		frame: true,
		height: 365,
		defaultType: 'field',
		defaults: {msgTarget: 'under', labelWidth: 125, anchor: '-5'}, //msgTarget: 'side', labelAlign: 'top'
		items: [{
			xtype: 'textfield',
			id: 'syspk',
			name: 'syspk',
			fieldLabel: 'syspk',
			allowBlank: false,
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
			id: 'transtype',
			name: 'transtype',
			fieldLabel: 'transtype',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'pay_type',
			name: 'pay_type',
			fieldLabel: 'Pay type',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'debit_acct',
			name: 'debit_acct',
			fieldLabel: 'debit_acct',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'ref_no',
			name: 'ref_no',
			fieldLabel: 'ref_no',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'collectType',
			name: 'collectType',
			fieldLabel: 'collectType',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'paymentType',
			name: 'paymentType',
			fieldLabel: 'paymentType',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'islending',
			name: 'islending',
			fieldLabel: 'islending',
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
				labelWidth: 105,
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
				width: 310,
				forceSelection: true,
				selectOnFocus:true,
				fieldStyle: 'font-weight: bold; color: #210a04;',
				listeners: {
					select: function(combo, record, index) {
						Ext.getCmp('customercode').setValue(record.get('debtor_ref'));
						
						ARInvoiceStore.proxy.extraParams = {debtor_id: record.get('debtor_no')};
						ARInvoiceStore.load();

					}
				}
			},{
				xtype : 'datefield',
				id	  : 'trans_date',
				name  : 'trans_date',
				fieldLabel : 'Date ',
				allowBlank: false,
				labelWidth: 100,
				width: 255,
				format : 'm/d/Y',
				fieldStyle: 'font-weight: bold; color: #210a04;',
				value: Ext.Date.format(new Date(), 'Y-m-d'),
				listeners: {
					select: function(combo, record, index) {
						Ext.getCmp('InvoiceNo').setValue();
						Ext.getCmp('total_amount').setValue();
						ARInvoiceStore.proxy.extraParams = {debtor_id: Ext.getCmp('customername').getValue()};
						ARInvoiceStore.load();
						AllocationStore.proxy.extraParams = {transNo: 0};
						AllocationStore.load();
					}
				}
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'combobox',
				id: 'InvoiceNo',
				name: 'InvoiceNo',
				allowBlank: false,
				store : ARInvoiceStore,
				displayField: 'name',
				valueField: 'id',
				queryMode: 'local',
				fieldLabel : 'Invoice No. ',
				labelWidth: 105,
				width: 560,
				forceSelection: true,
				selectOnFocus:true,
				fieldStyle: 'font-weight: bold; color: #210a04;',
				listeners: {
					select: function(combo, record, index) {
						Getreference();
						Ext.getCmp('transtype').setValue(record.get('type'));

						AllocationStore.proxy.extraParams = {transNo: record.get('id'), debtor_no: Ext.getCmp('customername').getValue(), transtype: record.get('type'), transdate: Ext.getCmp('trans_date').getValue(), pay_type: Ext.getCmp('paymentType').getValue(), islending: Ext.getCmp('islending').getValue()}; //, alloc_amount: Ext.getCmp('alloc_amount').getValue()
						AllocationStore.load();
						SIitemStore.proxy.extraParams = {transNo: record.get('id'), transtype: record.get('type')};
						SIitemStore.load();
					}
				}
			},{
				xtype: 'numericfield',
				id: 'total_amount',
				name: 'total_amount',
				fieldLabel: 'Total Amount ',
				allowBlank:false,
				useThousandSeparator: true,
				readOnly: true,
				labelWidth: 100,
				width: 255,
				margin: '0 0 2 0',
				thousandSeparator: ',',
				minValue: 0,
				fieldStyle: 'font-weight: bold;color: red; text-align: right;'
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				/*xtype: 'combobox',
				fieldLabel: 'Cashier/Teller ',
				id: 'cashier',
				name: 'cashier',
				store: cashierStore,
				displayField: 'name',
				valueField: 'id',
				queryMode: 'local',
				labelWidth: 105,
				width: 280,
				forceSelection: true,
				selectOnFocus:true,
				allowBlank: false,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{*/
				xtype: 'textfield',
				fieldLabel: 'Prepared By ',
				id: 'preparedby',
				name: 'preparedby',
				allowBlank: false,
				readOnly: true,
				labelWidth: 105,
				width: 280,
				margin: '0 280 0 0',
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'numericfield',
				id: 'tenderd_amount',
				name: 'tenderd_amount',
				fieldLabel: 'Allocate Amount ',
				allowBlank:false,
				useThousandSeparator: true,
				readOnly: true,
				labelWidth: 115,
				width: 255,
				margin: '0 0 2 0',
				thousandSeparator: ',',
				minValue: 0,
				fieldStyle: 'font-weight: bold;color: red; text-align: right; background-color: #F2F4F4;'
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 	'textareafield',
				fieldLabel: 'Remarks ',
				id:	'remarks',
				name: 'remarks',
				//labelAlign:	'top',
				allowBlank: false,
				maxLength: 254,
				labelWidth: 105,
				width: 560,
				hidden: false,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			}]
		},{
			xtype: 'tabpanel',
			id: 'alloctabpanel',
			activeTab: 0,
			width: 860,
			height: 165,
			scale: 'small',
			items:[{
				xtype:'gridpanel',
				id: 'AllocTabGrid',
				anchor:'100%',
				layout:'fit',
				title: 'Allocate Entry',
				icon: '../../js/ext4/examples/shared/icons/page_attach.png',
				loadMask: true,
				store:	AllocationStore,
				columns: AllocationHeader,
				selModel: smCheckAmortGrid,
				features: [{ftype: 'summary'}],
				columnLines: true,
				viewConfig : {
					listeners : {
						cellclick : function(view, cell, cellIndex, record, row, rowIndex, e) {
							Ext.getCmp("total_amount").setValue(record.get("totalpayment"));
						},
						rowclick: function(sm, rowIdx, r) {
							var GridRecords = Ext.getCmp('AllocTabGrid').getSelectionModel().getLastSelected();
							GridRecords.set('alloc_amount', Ext.getCmp("tenderd_amount").getValue());
						}
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
		}]
	});
	var submit_window = Ext.create('Ext.Window',{
		width 	: 842,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form],
		buttons:[{
			text: '<b>Process</b>',
			tooltip: 'Allocate customer payment',
			icon: '../../js/ext4/examples/shared/icons/add.png',
			single : true,				
			handler:function(){
				var form_submit = Ext.getCmp('form_submit').getForm();
				if(form_submit.isValid()) {
					var AllocationModel = AllocationStore.model;
					var TotalFld = AllocationModel.getFields().length -1;
					var records = AllocationStore.getModifiedRecords();
					var gridData = [];
					var params = {};
					var count = 0;
					for(var i = 0; i < records.length; i++){
						Ext.each(AllocationModel.getFields(), function(field){
							params[field.name] = records[i].get(field.name);
							count++;
							if(count == TotalFld){
								gridData.push(params);
								params = {};
								count = 0;
							}
						});
					}
					form_submit.submit({
						url: '?submit=payment',
						params: {
							DataOnGrid: Ext.encode(gridData)
						},
						waitMsg: 'Allocate payment for Invoice No.' + Ext.getCmp('InvoiceNo').getRawValue() + '. please wait...',
						method:'POST',
						submitEmptyText: false,
						success: function(form_submit, action) {
							qqinterb_store.load()
							Ext.Msg.alert('Success!', '<font color="green">' + action.result.message + '</font>');
							submit_window.close();
							window.open('../allocation_payment.php?transno='+ action.result.payno);
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
			tooltip: 'Cancel customer payment',
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

	var tbar = [{
		xtype: 'combobox',
		id: 'fstatus',
		name: 'fstatus',
		fieldLabel: '<b>Status</b>',
		store: fstatusStore,
		displayField: 'name',
		valueField: 'id',
		queryMode: 'local',
		emptyText:'select...',
		labelWidth: 50,
		width: 180,
		forceSelection: true,
		selectOnFocus:true,
		editable: false,
		listeners: {
			select: function(combo, record, index) {
				qqinterb_store.proxy.extraParams = {status: combo.getValue(), branch: Ext.getCmp('branchcode').getValue(), query: Ext.getCmp('search').getValue(), islending: Ext.getCmp("fromlending").getValue()};
				qqinterb_store.load();
			},
			afterrender: function() {
				Ext.getCmp("fstatus").setValue("draft");
			}
		}
	},{
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
				qqinterb_store.proxy.extraParams = {status: Ext.getCmp('fstatus').getValue(), branch: combo.getValue(), query: Ext.getCmp('search').getValue(), islending: Ext.getCmp("fromlending").getValue()};
				qqinterb_store.load();
			},
			afterrender: function() {
				Ext.getCmp("branchcode").setValue("zHun");
			}
		}
	}, '-', {
		xtype: 'searchfield',
		id:'search',
		name:'search',
		fieldLabel: '<b>Search</b>',
		labelWidth: 50,
		width: 305,
		emptyText: "Search by name...",
		scale: 'small',
		store: qqinterb_store,
		listeners: {
			change: function(field) {
				qqinterb_store.proxy.extraParams = {status: Ext.getCmp('fstatus').getValue(), branch: Ext.getCmp('branchcode').getValue(), query: field.getValue(), islending: Ext.getCmp("fromlending").getValue()};
				qqinterb_store.load();
			}
		}
	}, '->' ,{
		xtype:'splitbutton',
		//text: '<b>Maintenance</b>',
		tooltip: 'Select...',
		icon: '../../js/ext4/examples/shared/icons/cog_edit.png',
		scale: 'small',
		menu:[{
			text: '<b>Customer List</b>',
			icon: '../../js/ext4/examples/shared/icons/map_magnify.png',
			href: '../../sales/inquiry/customers_list.php?popup=1&client_id=customer_id',
			hrefTarget : '_blank'
		}]
	}];

	var builder_panel =  Ext.create('Ext.panel.Panel', { 
        renderTo: 'ext-form',
		id: 'builder_panel',
        frame: false,
		width: 1200,
		tbar: tbar,
		items: [{
			xtype: 'grid',
			id: 'interbPayment_grid',
			name: 'interbPayment_grid',
			store:	qqinterb_store,
			columns: InterB_Payment_Header,
			columnLines: true,
			autoScroll:true,
			layout:'fit',
			frame: true,
			bbar : {
				xtype : 'pagingtoolbar',
				hidden: false,
				store : qqinterb_store,
				pageSize : itemsPerPage,
				displayInfo : false,
				emptyMsg: "No records to display",
				doRefresh : function(){
					qqinterb_store.load();
				},
				items:[{
					xtype: 'checkbox',
					id: 'fromlending',
					name: 'fromlending',
					boxLabel: 'Show from lending',
					listeners: {
						change: function(field) {
							//alert(field.getValue());
							if(field.getValue()){
								Ext.getCmp('branchcode').setDisabled(true);
							}else{
								Ext.getCmp('branchcode').setDisabled(false);
							}
							
							qqinterb_store.proxy.extraParams = {status: Ext.getCmp('fstatus').getValue(), branch: Ext.getCmp('branchcode').getValue(), query: Ext.getCmp('search').getValue(), islending: field.getValue()};
							qqinterb_store.load();
						}
					}
					/*xtype: 'combobox',
					id: 'showl',
					name: 'showl',
					store : FromStore,
					displayField: 'name',
					valueField: 'id',
					queryMode: 'local'*/
				}]
			}
		}]
	});

	function Getreference(){
		Ext.Ajax.request({
			url : '?getReference=zHun',
			async:false,
			success: function (response){
				var result = Ext.JSON.decode(response.responseText);
				Ext.getCmp('ref_no').setValue(result.reference);
				submit_window.setTitle('Inter-Branch Payment Entry - Reference No. : '+ result.reference + ' *new');
			}
		});
	};
	function GetCashierPrep(){
		Ext.Ajax.request({
			url : '?get_cashierPrep=zHun',
			async:false,
			success: function (response){
				var result = Ext.JSON.decode(response.responseText);
				//Ext.getCmp('cashier').setValue(result.cashier);
				Ext.getCmp('preparedby').setValue(result.prepare);
			}
		});
	};
});
