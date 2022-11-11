var newURL = window.location.protocol + "//" + window.location.host + window.location.pathname;
Ext.Loader.setConfig({enabled: true});
Ext.Loader.setPath('Ext.ux', '../js/ext4/examples/ux/');
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
	var url_transno = getUrlParameter('transno');

    Ext.define('CustomerPay_model',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'tran_date', mapping:'tran_date'},
			{name:'trans_no', mapping:'trans_no'},
			{name:'invoice_no', mapping:'invoice_no'},
			{name:'trans_typeFr', mapping:'trans_typeFr'},
			{name:'trans_typeTo', mapping:'trans_typeTo'},
			{name:'debtor_no', mapping:'debtor_no'},
			{name:'debtor_ref', mapping:'debtor_ref'},
			{name:'customer_name', mapping:'customer_name'},
			{name:'reference', mapping:'reference'},
			{name:'receipt_no', mapping:'receipt_no'},
			{name:'total_amount', mapping:'total_amount'},
			{name:'discount', mapping:'discount'},
			{name:'Bank_account_id', mapping:'Bank_account_id'},
			{name:'Bank_account', mapping:'Bank_account'},
			{name:'pay_type', mapping:'pay_type'},
			{name:'pay_amount', mapping:'pay_amount'},
			{name:'check_date', mapping:'check_date'},
			{name:'check_no', mapping:'check_no'},
			{name:'Bank_branch', mapping:'Bank_branch'},
			{name:'remarks', mapping:'remarks'},
			{name:'module_type', mapping:'module_type'},
			{name:'prepared_by', mapping:'prepared_by'},
			{name:'check_by', mapping:'check_by'},
			{name:'approved_by', mapping:'approved_by'},
			{name:'payment_type', mapping:'payment_type'},
			{name:'payment_type_v', mapping:'payment_type_v'},
			{name:'collect_type', mapping:'collect_type'},
			{name:'cashier', mapping:'cashier'},
			{name:'cashier_name', mapping:'cashier_name'}
		]
	});
	Ext.define('view_ledger',{
		extend : 'Ext.data.Model',
		fields  : [
			{name:'loansched_id',mapping:'loansched_id'},
			{name:'debtor_id',mapping:'debtor_id'},
			{name:'trans_no',mapping:'trans_no'},
			{name:'date_due',mapping:'date_due'},
			{name:'mosterm',mapping:'mosterm'},
			{name:'amortization',mapping:'amortization'},
			{name:'rebate',mapping:'rebate',type:'float'},
			{name:'penalty',mapping:'penalty',type:'float'},
			{name:'alloc_amount',mapping:'alloc_amount',type:'float'},
			{name:'total_alloc',mapping:'total_alloc',type:'float'},
			{name:'balance',mapping:'balance',type:'float'}
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
			{name:'name', mapping:'name'},
			{name:'amount', mapping:'amount'},
			{name:'trans_no', mapping:'trans_no'},
			{name:'brcode', mapping:'brcode'},
			{name:'brdate', mapping:'brdate'},
			{name:'pay_type', mapping:'pay_type'}
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
	Ext.define('InvoiceModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'name', mapping:'name'},
			{name:'type', mapping:'type'},
			{name:'status', mapping:'status'},
			{name:'pay_location', mapping:'pay_location'}
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
			{name:'grossPM',mapping:'grossPM', type:'float'},
			{name:'downpayment',mapping:'downpayment', type:'float'},
			{name:'dp_discount',mapping:'dp_discount', type:'float'},
			{name:'balance',mapping:'balance', type:'float'}
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
	Ext.define('interCOAModel',{
		extend : 'Ext.data.Model',
		fields  : [
			{name:'id',mapping:'id'},
			{name:'trans_date',mapping:'trans_date'},
			{name:'gl_code',mapping:'gl_code'},
			{name:'gl_name',mapping:'gl_name'},
			{name:'sl_code',mapping:'sl_code'},
			{name:'sl_name',mapping:'sl_name'},
			{name:'debtor_id',mapping:'debtor_id'},
			{name:'debit_amount',mapping:'debit_amount',type:'float'},
			{name:'credit_amount',mapping:'credit_amount',type:'float'}
		]
	});

	var smCheckDPGrid = Ext.create('Ext.selection.CheckboxModel',{
		mode: 'SINGLE'
	});
	var smCheckIBGrid = Ext.create('Ext.selection.CheckboxModel',{
		mode: 'SINGLE'
	});
	//------------------------------------: stores :----------------------------------------
	var PaymentTypeStore = Ext.create('Ext.data.Store',{
		fields: ['id','name'],
		autoLoad: true,
		data : 	[
			{"id":"other","name":"Other Payment"}
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
	var RecptViewStore = Ext.create('Ext.data.Store', {
		model: 'view_ledger',
		//autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_loan_ledger=xx',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true
	});
	var done_allocate_store = Ext.create('Ext.data.Store', {
		model: 'CustomerPay_model',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_custPayment=zHun&module_type=ALCN&transno= '+url_transno,
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
			url: '?get_Customer=zhun',
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
	var GLStore = Ext.create('Ext.data.Store', {
		name: 'GLStore',
		fields:['code','name','group'],
		autoLoad : true,
        proxy: {
			url: '?getCOA=00',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'code',
			direction : 'ASC'
		}]
	});
	var InterCOAStore = Ext.create('Ext.data.Store', {
		model: 'interCOAModel',
		name : 'InterCOAStore',
		method : 'POST',
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_interCOAPaymnt=xx',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		}
	});

	//---------------------------------------------------------------------------------------
	var AllocDPHeader = [
		{header:'<b>Id</b>',dataIndex:'loansched_id',hidden: true},
		{header:'<b>Trans No.</b>', dataIndex:'trans_no', width:70, hidden: true}, //align:'center',
		{header:'<b>No.</b>', dataIndex:'mosterm', width:50, locked: true},
		{header:'<b>Due Date</b>', dataIndex:'date_due', width:90, locked: true},
		{header:'<b>Down-Payment</b>', dataIndex:'downpayment', align:'right', width:145,
			renderer : function(value, metaData, summaryData, dataIndex){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>DP Discount</b>', dataIndex:'dp_discount', align:'right', width:130, 
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
		{header:'<b>Total Payment</b>', dataIndex:'totalpayment', width:140, align:'right',  locked: true, summaryType: 'sum',
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
		{header:'<b>Credit Amount</b>', dataIndex:'alloc_amount', width:150, align:'right', summaryType: 'sum', locked: true,
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
		},
		{header:'<b>GrossPM</b>', dataIndex:'grossPM', width:125, align:'right',
			renderer : function(value, metaData, summaryData, dataIndex){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>AR Balance</b>', dataIndex:'balance', width:125, align:'right',
			renderer : function(value, metaData, summaryData, dataIndex){
				return Ext.util.Format.number(value, '0,000.00');
			}
		}
	];
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
		},
		{header:'<b>AR Balance</b>', dataIndex:'balance', width:125, align:'right',
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
	var columnAmort_view = 	[
		{header:'<b>Id</b>',dataIndex:'loansched_id',hidden: true},
		{header:'<b>Trans No.</b>', dataIndex:'trans_no', width:70, hidden: true}, //align:'center',
		{header:'<b>No.</b>', dataIndex:'mosterm', width:50},
		{header:'<b>Due Date</b>', dataIndex:'date_due', width:90},
		{header:'<b>Monthly</b>', dataIndex:'amortization', align:'right', width:100,
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
		{header:'<b>Allocate amount</b>', dataIndex:'alloc_amount', width:155, align:'right',
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:blue;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			},	
		},
		{header:'<b>Total Allocate</b>', dataIndex:'total_alloc', width:135, align:'right',
			renderer : function(value, metaData, summaryData, dataIndex){
				return Ext.util.Format.number(value, '0,000.00');
			}	
		},
		{header:'<b>Balance</b>', dataIndex:'balance', width:122, align:'right',
			renderer : function(value, metaData, summaryData, dataIndex){
				return Ext.util.Format.number(value, '0,000.00');
			}	
		}
	];
	var column_COA = [
		{header:'<b>Code</b>', dataIndex:'code', width:120},
		{header:'<b>Description</b>', dataIndex:'name', width:148, flex: 1},
		{header:'<b>Group</b>', dataIndex:'group', width:120},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:110,
			items:[{
				icon: '../js/ext4/examples/shared/icons/add.png', //tick
				tooltip: 'Select',
				handler: function(grid, rowIndex, colIndex) {
					var records = GLStore.getAt(rowIndex);
					loadCOA('add', records.get('code'));

					Ext.toast({
						icon: '../js/ext4/examples/shared/icons/accept.png',
						html: 'Code: <b>' + records.get('code') + ' </b><br/> ' + 'Description: <b>' + records.get('name') + '<b/>',
						title: 'Successfully added...',
						width: 250,
						bodyPadding: 10,
						align: 'tl',
						bodyStyle: {
							color: ' #273746 ',
							background:'#e8ecf0',
							border: '2px solid red'
						}
					});	
				}
			}]
		}
	];
	//for policy setup column model
	var InterB_Payment_Header = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Date</b>', dataIndex:'tran_date', sortable:true, width:100, renderer: Ext.util.Format.dateRenderer('m-d-Y')},
		{header:'<b>Reference No.</b>', dataIndex:'reference', sortable:true, width:130},
		{header:'<b>Customer Name</b>', dataIndex:'customer_name', sortable:true, width:210},
		{header:'<b>Amount</b>', dataIndex:'total_amount', sortable:true, width:130,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
			}
		},
		{header:'<b>Remarks</b>', dataIndex:'remarks', sortable:true, width:272,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Prepared by</b>', dataIndex:'prepared_by', sortable:true, width:210,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:110,
			items:[{
				icon: '../js/ext4/examples/shared/icons/layout_content.png',
				tooltip: 'view details',
				handler: function(grid, rowIndex, colIndex) {
					var records = done_allocate_store.getAt(rowIndex);

					submit_form_ADP.getForm().reset();
					submit_form_AIB.getForm().reset();
					submit_form_view.getForm().reset();

					ARInvoiceStore.proxy.extraParams = {debtor_id: records.get('debtor_no'), tag: "inst"};
					ARInvoiceStore.load();
					RecptViewStore.proxy.extraParams = {trans_no: records.get('trans_no'), typeTo: records.get('trans_typeTo')};
					RecptViewStore.load();

					Ext.getCmp('v_syspk').setValue(records.get('trans_no'));
					Ext.getCmp('v_moduletype').setValue(records.get('module_type'));
					Ext.getCmp('v_transtypeFr').setValue(records.get('trans_typeFr'));
					Ext.getCmp('v_transtypeTo').setValue(records.get('trans_typeTo'));
					Ext.getCmp('v_customercode').setValue(records.get('debtor_ref'));
					Ext.getCmp('v_customername').setValue(records.get('customer_name'));
					Ext.getCmp('v_trans_date').setValue(records.get('tran_date'));
					Ext.getCmp('v_InvoiceNo').setValue(records.get('invoice_no'));
					Ext.getCmp('v_total_amount').setValue(records.get('total_amount'));
					Ext.getCmp('v_cashier').setValue(records.get('cashier_name'));
					Ext.getCmp('v_preparedby').setValue(records.get('prepared_by'));
					Ext.getCmp('v_remarks').setValue(records.get('remarks'));

					submit_window_view.setTitle('Allocate Inter-Branch Payment details - Reference No. : '+ records.get('reference'));
					submit_window_view.show();
					submit_window_view.setPosition(320,55);

				}
			},'-',{
				icon   : '../js/ext4/examples/shared/icons/chart_line.png',
				tooltip : 'Entries',
				handler : function(grid, rowIndex, colIndex){
					var records = done_allocate_store.getAt(rowIndex);
					window.open('../gl/view/gl_trans_view.php?type_id=12&trans_no='+ records.get('trans_no'));
				}
			}]
		}
	];
	var WaivedGLHeader = [
		//{header:'<b>Id</b>',dataIndex:'loansched_id',hidden: true},
		{header:'<b>Date</b>', dataIndex:'trans_date', width:80, hidden: true},
		{header:'<b>GL Code</b>', dataIndex:'gl_code', width:80},
		{header:'<b>Description</b>', dataIndex:'gl_name', width:230},
		{header:'<b>SL Code</b>', dataIndex:'sl_code', width:80},
		{header:'<b>SL Name</b>', dataIndex:'sl_name', width:155},
		{header:'<b>Debit</b>', dataIndex:'debit_amount', width:100, align:'right', summaryType: 'sum',
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:green;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			},
			summaryRenderer: function(value, summaryData, dataIndex){
				Ext.getCmp('total_debt_wv').setValue(value);
				return '<span style="color:blue;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
			},
			editor: new Ext.form.TextField({
				xtype:'textfield',
				id: 'debit_amount',
				name: 'debit_amount',
				allowBlank: false,
				listeners : {

				}
			})
		},
		{header:'<b>Credit</b>', dataIndex:'credit_amount', width:100, align:'right', summaryType: 'sum',
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:green;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			},
			summaryRenderer: function(value, summaryData, dataIndex){
				Ext.getCmp('total_cred_wv').setValue(value);
				return '<span style="color:blue;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
			},
			editor: new Ext.form.TextField({
				xtype:'textfield',
				id: 'credit_amount',
				name: 'credit_amount',
				allowBlank: false,
				listeners : {

				}
			})
		},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:70,
			items:[{
				icon: '../js/ext4/examples/shared/icons/delete.png',
				tooltip: 'remove',
				handler: function(grid, rowIndex, colIndex) {
					var records = InterCOAStore.getAt(rowIndex);
					loadCOA('delete', records.get('gl_code'));
				}
			}]
		}
	];

	var GLTitle_w = new Ext.create('Ext.Window',{
		id: 'GLTitle_w',
		width: 840,
		height: 400,
		scale: 'small',
		resizable: false,
		closeAction:'hide',
		//closable:true,
		modal: true,
		layout:'fit',
		plain 	: true,
		title: 'List Of Chart Of Accounts',
		items: [{
			xtype: 'gridpanel',
			store: GLStore,
			anchor:'100%',
			layout:'fit',
			frame: false,
			loadMask: true,
			columns: column_COA,
			features: [{ftype: 'summary'}],
			columnLines: true,
			bbar : {
				xtype : 'pagingtoolbar',
				hidden: false,
				store : GLStore,
				pageSize : itemsPerPage,
				displayInfo : false,
				emptyMsg: "No records to display",
				doRefresh : function(){
					GLStore.load();
				},
				items:[{
					xtype: 'searchfield',
					id:'searchCOA',
					name:'searchCOA',
					fieldLabel: '<b>Search</b>',
					labelWidth: 50,
					width: 305,
					emptyText: "Search",
					scale: 'small',
					store: GLStore,
					listeners: {
						change: function(field) {
							GLStore.proxy.extraParams = {query: field.getValue()};
							GLStore.load();
						}
					}
				/*},'->',{
					xtype: 'button',
					tooltip: 'Close window',
					margin: '0 12 0 0',
					text:'<b>Close</b>',
					style:'background-color: white; color: red; font-weight: bold;',
					icon: '../js/ext4/examples/shared/icons/cancel.png',
					handler : function() {
						GLTitle_w.close();
					}*/
				}]
			}
		}],
		/*listeners:{
			close: function(thiswindow) {
				thiswindow.close();
		   }
        }
		buttons:[{
			text:'<b>Close</b>',
			tooltip: 'Close window',
			icon: '../js/ext4/examples/shared/icons/cancel.png',
			handler:function(){
				GLTitle_w.close();
			}
		}]*/
	});

	var submit_form_ADP = Ext.create('Ext.form.Panel', {
		id: 'submit_form_ADP',
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
			id: 'pay_transtype',
			name: 'pay_transtype',
			fieldLabel: 'pay transtype',
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
			id: 'paylocation',
			name: 'paylocation',
			fieldLabel: 'pay location',
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
						Ext.getCmp('syspk').setValue(record.get('trans_no'));
						Ext.getCmp('tenderd_amount').setValue(record.get('amount'));
						Ext.getCmp('pay_transtype').setValue(record.get('pay_type'));
						
						ARInvoiceStore.proxy.extraParams = {debtor_id: record.get('debtor_no'), tag: "inst"};
						ARInvoiceStore.load();
						AllocationStore.proxy.extraParams = {transNo: 0, debtor_no: 0, transtype: 0 };
						AllocationStore.load();

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
						Getreference("DP");
						Ext.getCmp('transtype').setValue(record.get('type'));
						Ext.getCmp('paylocation').setValue(record.get('pay_location'));

						AllocationStore.proxy.extraParams = {transNo: record.get('id'), debtor_no: Ext.getCmp('customername').getValue(), transtype: record.get('type'), transdate: Ext.getCmp('trans_date').getValue(), pay_type: "down", payloc: record.get('pay_location') };
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
				xtype: 'combobox',
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
			},{
				xtype: 'textfield',
				fieldLabel: 'Prepared By ',
				id: 'preparedby',
				name: 'preparedby',
				allowBlank: false,
				readOnly: true,
				labelWidth: 105,
				width: 280,
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
				title: 'Allocate Entry DP',
				icon: '../js/ext4/examples/shared/icons/page_attach.png',
				loadMask: true,
				store: AllocationStore,
				columns: AllocDPHeader,
				selModel: smCheckDPGrid,
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
				icon: '../js/ext4/examples/shared/icons/lorry_flatbed.png',
				loadMask: true,
				store:	SIitemStore,
				columns: Item_view,
				columnLines: true
			}]
		}]
	});
	var submit_window_ADP = Ext.create('Ext.Window',{
		width 	: 842,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form_ADP],
		buttons:[{
			text: '<b>Process</b>',
			tooltip: 'Save customer payment',
			icon: '../js/ext4/examples/shared/icons/add.png',
			single : true,				
			handler:function(){
				var submit_form_ADP = Ext.getCmp('submit_form_ADP').getForm();
				if(submit_form_ADP.isValid()) {
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
					submit_form_ADP.submit({
						url: '?submitAllocDP=payment',
						params: {
							DataOnGrid: Ext.encode(gridData)
						},
						waitMsg: 'Allocate payment for Invoice No.' + Ext.getCmp('InvoiceNo').getRawValue() + '. please wait...',
						method:'POST',
						submitEmptyText: false,
						success: function(submit_form_ADP, action) {
							done_allocate_store.load()
							Ext.Msg.alert('Success!', '<font color="green">' + action.result.message + '</font>');
							submit_window_ADP.close();
						},
						failure: function(submit_form_ADP, action) {
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
			tooltip: 'Cancel customer allocate payment',
			icon: '../js/ext4/examples/shared/icons/cancel.png',
			handler:function(){
				Ext.MessageBox.confirm('Confirm:', 'Are you sure you wish to close this window?', function (btn, text) {
					if (btn == 'yes') {
						//Ext.Msg.alert('Close','close.');
						submit_form_ADP.getForm().reset();
						submit_window_ADP.close();
					}
				});
			}
		}]
	});

	var submit_form_AIB = Ext.create('Ext.form.Panel', {
		id: 'submit_form_AIB',
		model: 'AllocationModel',
		frame: true,
		height: 365,
		defaultType: 'field',
		defaults: {msgTarget: 'under', labelWidth: 125, anchor: '-5'}, //msgTarget: 'side', labelAlign: 'top'
		items: [{
			xtype: 'textfield',
			id: 'syspk_aib',
			name: 'syspk_aib',
			fieldLabel: 'syspk',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'moduletype_aib',
			name: 'moduletype_aib',
			fieldLabel: 'moduletype',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'transtype_aib',
			name: 'transtype_aib',
			fieldLabel: 'transtype',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'debit_acct_aib',
			name: 'debit_acct_aib',
			fieldLabel: 'debit_acct',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'ref_no_aib',
			name: 'ref_no_aib',
			fieldLabel: 'ref_no',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'collectType_aib',
			name: 'collectType_aib',
			fieldLabel: 'collectType',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'paymentType_aib',
			name: 'paymentType_aib',
			fieldLabel: 'paymentType',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'textfield',
				fieldLabel: 'Customer ',
				id: 'customercode_aib',
				name: 'customercode_aib',
				allowBlank: false,
				labelWidth: 105,
				width: 250,
				readOnly: true,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'combobox',
				id: 'customername_aib',
				name: 'customername_aib',
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
						Ext.getCmp('customercode_aib').setValue(record.get('debtor_ref'));
						Ext.getCmp('syspk_aib').setValue(record.get('trans_no'));
						Ext.getCmp('tenderd_amount_aib').setValue(record.get('amount'));
						Ext.getCmp('debit_acct_aib').setValue(record.get('brcode'));
						Ext.getCmp('trans_date_aib').setValue(record.get('brdate'));
						
						ARInvoiceStore.proxy.extraParams = {debtor_id: record.get('debtor_no'), tag: "inst"};
						ARInvoiceStore.load();

					}
				}
			},{
				xtype : 'datefield',
				id	  : 'trans_date_aib',
				name  : 'trans_date_aib',
				fieldLabel : 'Date ',
				allowBlank: false,
				labelWidth: 100,
				width: 255,
				format : 'm/d/Y',
				fieldStyle: 'font-weight: bold; color: #210a04;',
				value: Ext.Date.format(new Date(), 'Y-m-d'),
				listeners: {
					select: function(combo, record, index) {
						Ext.getCmp('InvoiceNo_aib').setValue();
						Ext.getCmp('total_amount_aib').setValue();
						ARInvoiceStore.proxy.extraParams = {debtor_id: Ext.getCmp('customername_aib').getValue()};
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
				id: 'InvoiceNo_aib',
				name: 'InvoiceNo_aib',
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
						Getreference("INTERB");
						Ext.getCmp('transtype_aib').setValue(record.get('type'));

						AllocationStore.proxy.extraParams = {transNo: record.get('id'), debtor_no: Ext.getCmp('customername_aib').getValue(), transtype: record.get('type'), transdate: Ext.getCmp('trans_date_aib').getValue(), pay_type: "interb" };
						AllocationStore.load();
						SIitemStore.proxy.extraParams = {transNo: record.get('id'), transtype: record.get('type')};
						SIitemStore.load();
					}
				}
			},{
				xtype: 'numericfield',
				id: 'total_amount_aib',
				name: 'total_amount_aib',
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
				id: 'cashier_aib',
				name: 'cashier_aib',
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
				id: 'preparedby_aib',
				name: 'preparedby_aib',
				allowBlank: false,
				readOnly: true,
				labelWidth: 105,
				margin: '0 280 0 0',
				width: 280,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'numericfield',
				id: 'tenderd_amount_aib',
				name: 'tenderd_amount_aib',
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
				id:	'remarks_aib',
				name: 'remarks_aib',
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
			id: 'alloctabpanel_aib',
			activeTab: 0,
			width: 860,
			height: 165,
			scale: 'small',
			items:[{
				xtype:'gridpanel',
				id: 'AllocTabGrid_aib',
				anchor:'100%',
				layout:'fit',
				title: 'Allocate Entry',
				icon: '../js/ext4/examples/shared/icons/page_attach.png',
				loadMask: true,
				store:	AllocationStore,
				columns: AllocationHeader,
				selModel: smCheckIBGrid,
				features: [{ftype: 'summary'}],
				columnLines: true,
				viewConfig : {
					listeners : {
						cellclick : function(view, cell, cellIndex, record, row, rowIndex, e) {
							Ext.getCmp("total_amount_aib").setValue(record.get("totalpayment"));
						},
						rowclick: function(sm, rowIdx, r) {
							var GridRecords = Ext.getCmp('AllocTabGrid_aib').getSelectionModel().getLastSelected();
							GridRecords.set('alloc_amount', Ext.getCmp("tenderd_amount_aib").getValue());
						}
					}
				}
			},{
				xtype:'gridpanel',
				id: 'ItemGrid_aib',
				anchor:'100%',
				layout:'fit',
				title: 'Item Details',
				icon: '../js/ext4/examples/shared/icons/lorry_flatbed.png',
				loadMask: true,
				store:	SIitemStore,
				columns: Item_view,
				columnLines: true
			}]
		}]
	});
	var submit_window_AIB = Ext.create('Ext.Window',{
		width 	: 842,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form_AIB],
		buttons:[{
			text: '<b>Process</b>',
			tooltip: 'Save customer payment',
			icon: '../js/ext4/examples/shared/icons/add.png',
			single : true,				
			handler:function(){
				var submit_form_AIB = Ext.getCmp('submit_form_AIB').getForm();
				if(submit_form_AIB.isValid()) {
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
					submit_form_AIB.submit({
						url: '?submitAllocInterB=payment',
						params: {
							DataOnGrid: Ext.encode(gridData)
						},
						waitMsg: 'Allocate payment for Invoice No.' + Ext.getCmp('InvoiceNo').getRawValue() + '. please wait...',
						method:'POST',
						submitEmptyText: false,
						success: function(submit_form_AIB, action) {
							done_allocate_store.load()
							Ext.Msg.alert('Success!', '<font color="green">' + action.result.message + '</font>');
							submit_window_AIB.close();
						},
						failure: function(submit_form_AIB, action) {
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
			tooltip: 'Cancel customer allocate payment',
			icon: '../js/ext4/examples/shared/icons/cancel.png',
			handler:function(){
				Ext.MessageBox.confirm('Confirm:', 'Are you sure you wish to close this window?', function (btn, text) {
					if (btn == 'yes') {
						//Ext.Msg.alert('Close','close.');
						submit_window_AIB.getForm().reset();
						submit_window_AIB.close();
					}
				});
			}
		}]
	});
	var submit_form_view = Ext.create('Ext.form.Panel', {
		id: 'submit_form_view',
		model: 'CustomerPay_model',
		frame: true,
		defaultType: 'field',
		items: [{
			xtype: 'textfield',
			id: 'v_syspk',
			name: 'v_syspk',
			fieldLabel: 'syspk',
			//allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'v_moduletype',
			name: 'v_moduletype',
			fieldLabel: 'moduletype',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'v_transtypeFr',
			name: 'v_transtypeFr',
			fieldLabel: 'transtypeF',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'v_transtypeTo',
			name: 'v_transtypeTo',
			fieldLabel: 'transtypeT',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'textfield',
				fieldLabel: '<b>Customer </b>',
				id: 'v_customercode',
				name: 'v_customercode',
				allowBlank: false,
				labelWidth: 105,
				width: 250,
				readOnly: true,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'textfield',
				id: 'v_customername',
				name: 'v_customername',
				allowBlank: false,
				width: 310,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype : 'datefield',
				id	  : 'v_trans_date',
				name  : 'v_trans_date',
				fieldLabel : '<b>Date </b>',
				allowBlank: false,
				labelWidth: 100,
				width: 255,
				format : 'm/d/Y',
				fieldStyle: 'font-weight: bold; color: #210a04;',
				value: Ext.Date.format(new Date(), 'Y-m-d')
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'combobox',
				id: 'v_InvoiceNo',
				name: 'v_InvoiceNo',
				store : ARInvoiceStore,
				displayField: 'name',
				valueField: 'id',
				queryMode: 'local',
				fieldLabel : '<b>Invoice No. </b>',
				labelWidth: 105,
				width: 560,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'numericfield',
				id: 'v_total_amount',
				name: 'v_total_amount',
				fieldLabel: 'Total Amount ',
				allowBlank:false,
				useThousandSeparator: true,
				readOnly: true,
				labelWidth: 100,
				width: 255,
				thousandSeparator: ',',
				minValue: 0,
				margin: '0 0 2 0',
				fieldStyle: 'font-weight: bold;color: red; text-align: right;'
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'textfield',
				fieldLabel: 'Cashier/Teller ',
				id: 'v_cashier',
				name: 'v_cashier',
				allowBlank: false,
				readOnly: true,
				labelWidth: 105,
				width: 280,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'textfield',
				fieldLabel: 'Prepared By ',
				id: 'v_preparedby',
				name: 'v_preparedby',
				allowBlank: false,
				readOnly: true,
				labelWidth: 105,
				width: 280,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 	'textareafield',
				fieldLabel: '<b>Remarks </b>',
				id:	'v_remarks',
				name: 'v_remarks',
				//labelAlign:	'top',
				allowBlank: true,
				maxLength: 254,
				labelWidth: 105,
				width: 560,
				hidden: false
			}]
		},{
			xtype: 'tabpanel',
			activeTab: 0,
			width: 860,
			scale: 'small',
			items:[{
				xtype:'gridpanel',
				anchor:'100%',
				layout:'fit',
				title: 'Allocate Receipts',
				icon: '../js/ext4/examples/shared/icons/page_attach.png',
				loadMask: true,
				height: 130,
				store:	RecptViewStore,
				columns: columnAmort_view,
				columnLines: true,
				features: [{ftype: 'summary'}]
			}]
		}]
	});
	var submit_window_view = Ext.create('Ext.Window',{
		width 	: 842,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form_view],
		buttons:[{
			text:'<b>Close</b>',
			tooltip: 'close window',
			icon: '../js/ext4/examples/shared/icons/cancel.png',
			handler:function(){
				submit_form_view.getForm().reset();
				submit_window_view.close();
			}
		}]
	});
	var submit_form_waivpnlty = Ext.create('Ext.form.Panel', {
		id: 'submit_form_waivpnlty',
		model: 'AllocationModel',
		frame: true,
		defaultType: 'field',
		defaults: {msgTarget: 'under', labelWidth: 125, anchor: '-5'}, //msgTarget: 'side', labelAlign: 'top'
			items: [{
				xtype: 'textfield',
				id: 'name_wv',
				name: 'name_wv',
				fieldLabel: 'customer name',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'textfield',
				id: 'transtype_wv',
				name: 'transtype_wv',
				fieldLabel: 'transtype',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'textfield',
				id: 'ref_no_wv',
				name: 'ref_no_wv',
				fieldLabel: 'ref_no',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'textfield',
				id: 'total_debt_wv',
				name: 'total_debt_wv',
				fieldLabel: 'total_debt_wv',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					xtype: 'textfield',
					fieldLabel: 'Customer ',
					id: 'customercode_wv',
					name: 'customercode_wv',
					allowBlank: false,
					labelWidth: 105,
					width: 250,
					readOnly: true,
					fieldStyle: 'font-weight: bold; color: #210a04;'
				},{
					xtype: 'combobox',
					id: 'customername_wv',
					name: 'customername_wv',
					allowBlank: false,
					store : CustomerStore,
					displayField: 'name',
					valueField: 'debtor_no',
					queryMode: 'local',
					width: 290,
					forceSelection: true,
					selectOnFocus:true,
					fieldStyle: 'font-weight: bold; color: #210a04;',
					listeners: {
						select: function(combo, record, index) {
							Ext.getCmp('customercode_wv').setValue(record.get('debtor_ref'));
							Ext.getCmp('name_wv').setValue(record.get('name'));

							ARInvoiceStore.proxy.extraParams = {debtor_id: record.get('debtor_no'), tag: "inst"};
							ARInvoiceStore.load();

							loadCOA('load');
						}
					}
				},{
					xtype : 'datefield',
					id	  : 'trans_date_wv',
					name  : 'trans_date_wv',
					fieldLabel : 'Date ',
					allowBlank: false,
					labelWidth: 105,
					width: 280,
					format : 'm/d/Y',
					fieldStyle: 'font-weight: bold; color: #210a04;',
					value: Ext.Date.format(new Date(), 'Y-m-d')
				}]
			},{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					xtype: 'combobox',
					id: 'InvoiceNo_wv',
					name: 'InvoiceNo_wv',
					allowBlank: false,
					store : ARInvoiceStore,
					displayField: 'name',
					valueField: 'id',
					queryMode: 'local',
					fieldLabel : 'Invoice No. ',
					labelWidth: 105,
					width: 540,
					forceSelection: true,
					selectOnFocus:true,
					fieldStyle: 'font-weight: bold; color: #210a04;',
					listeners: {
						select: function(combo, record, index) {
							Getreference("waived");
							Ext.getCmp('transtype_wv').setValue(record.get('type'));
	
							SIitemStore.proxy.extraParams = {transNo: record.get('id'), transtype: record.get('type')};
							SIitemStore.load();

							loadCOA('add','',record.get('id'),record.get('type'));
						}
					}
				},{
					xtype: 'textfield',
					fieldLabel: 'Prepared By ',
					id: 'preparedby_wv',
					name: 'preparedby_wv',
					allowBlank: false,
					readOnly: true,
					labelWidth: 105,
					width: 280,
					fieldStyle: 'font-weight: bold; color: #210a04;'
				}]
			},{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					xtype: 	'textareafield',
					fieldLabel: 'Remarks ',
					id:	'remarks_wv',
					name: 'remarks_wv',
					//labelAlign:	'top',
					allowBlank: false,
					maxLength: 254,
					labelWidth: 105,
					width: 540,
					hidden: false
				},{
					xtype: 'numericfield',
					id: 'total_cred_wv',
					name: 'total_cred_wv',
					fieldLabel: 'Total Amount ',
					allowBlank:false,
					useThousandSeparator: true,
					readOnly: true,
					labelWidth: 105,
					width: 280,
					thousandSeparator: ',',
					minValue: 0,
					margin: '0 0 2 0',
					fieldStyle: 'font-weight: bold;color: red; text-align: right;'
				}]
			},{
				xtype: 'tabpanel',
				id: 'wvPanel',
				activeTab: 0,
				width: 860,
				height: 240,
				scale: 'small',
				items:[{
					xtype:'gridpanel',
					id: 'gridCOA_wv',
					anchor:'100%',
					layout:'fit',
					title: 'Entry',
					icon: '../js/ext4/examples/shared/icons/vcard.png',
					loadMask: true,
					store:	InterCOAStore,
					columns: WaivedGLHeader,
					columnLines: true,
					selModel: 'cellmodel',
					plugins: {
						ptype: 'cellediting',
						clicksToEdit: 1
					},
					features: [{ftype: 'summary'}]
				},{
					xtype:'gridpanel',
					id: 'ItemGrid_wv',
					anchor:'100%',
					layout:'fit',
					title: 'Item Details',
					icon: '../js/ext4/examples/shared/icons/lorry_flatbed.png',
					loadMask: true,
					store:	SIitemStore,
					columns: Item_view,
					columnLines: true
				}],
				tabBar: {
					items: [{
						xtype: 'tbfill'
					},{
						xtype: 'button',
						text: 'Add GL Account',
						padding: '3px',
						margin: '2px 2px 6px 2px',
						icon: '../js/ext4/examples/shared/icons/chart_line_add.png',
						tooltip: 'Click to Add GL Entry',
						style : {
							'color': 'blue',
							'font-size': '30px',
							'font-weight': 'bold',
							'background-color': '#0a0a23',
							'position': 'absolute',
							'box-shadow': '0px 0px 2px 2px rgb(0,0,0)',
							'border': 'none',
							//'border-radius':'10px'
						},
						handler: function(){
							Ext.getCmp('searchCOA').focus(false, 200);
							GLTitle_w.show();
							GLTitle_w.setPosition(320,60);
						}
					}]
				}
			}]
	});
	var submit_window_waivpnlty = Ext.create('Ext.Window',{
		width 	: 842,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form_waivpnlty],
		buttons:[{
			text: '<b>Save</b>',
			tooltip: 'Save customer payment',
			icon: '../js/ext4/examples/shared/icons/add.png',
			single : true,
			handler:function(){
				var form_submit_Adj = Ext.getCmp('submit_form_waivpnlty').getForm();
				if(form_submit_Adj.isValid()) {
					var gridData = InterCOAStore.getRange();
					var gridAdjData = [];
					count = 0;
					Ext.each(gridData, function(item) {
						var ObjInterB = {
							gl_code: item.get('gl_code'),  
							gl_name: item.get('gl_name'),
							sl_code: item.get('sl_code'),
							sl_name: item.get('sl_name'),
							debtor_id: item.get('debtor_id'),
							debit_amount: item.get('debit_amount'),
							credit_amount: item.get('credit_amount'),
						};
						gridAdjData.push(ObjInterB);
					});
					//console.log(Ext.decode(gridData));
					form_submit_Adj.submit({
						url: '?submitAdj=adjustment',
						params: {
							DataOnGrid: Ext.encode(gridAdjData),
						},
						waitMsg: 'Saving adjustment. please wait...',
						method:'POST',
						submitEmptyText: false,
						success: function(form_submit_Adj, action) {
							PaymentStore.load()
							Ext.Msg.alert('Success!', '<font color="green">' + action.result.message + '</font>');
							submit_window_waivpnlty.close();
						},
						failure: function(form_submit_Adj, action) {
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
			tooltip: 'Cancel adjustment',
			icon: '../js/ext4/examples/shared/icons/cancel.png',
			handler:function(){
				Ext.MessageBox.confirm('Confirm:', 'Are you sure you wish to close this window?', function (btn, text) {
					if (btn == 'yes') {
						//Ext.Msg.alert('Close','close.');
						submit_form_waivpnlty.getForm().reset();
						submit_window_waivpnlty.close();
					}
				});
			}
		}]
	});
	var tbar = [{
		xtype: 'searchfield',
		id:'search',
		name:'search',
		fieldLabel: '<b>Search</b>',
		labelWidth: 50,
		width: 305,
		emptyText: "Search by name...",
		scale: 'small',
		store: done_allocate_store,
		listeners: {
			change: function(field) {
				done_allocate_store.proxy.extraParams = {query: field.getValue()};
				done_allocate_store.load();
			}
		}
	}, '-', {
		text:'<b>Allocate Down-Payment</b>',
		tooltip: 'Allocate Down-payment',
		icon: '../js/ext4/examples/shared/icons/money_add.png',
		scale: 'small',
		handler: function(){
			submit_form_ADP.getForm().reset();
			
			CustomerStore.proxy.extraParams = {module: "DP"};
			CustomerStore.load();
			AllocationStore.proxy.extraParams = {transNo: 0 };
			AllocationStore.load();

			cashierStore.load({
				callback: function(records) {                 
        			Ext.getCmp('cashier').setValue(records[i].get('id'));
				}
			});

			Ext.getCmp('moduletype').setValue('ALCN-DP');
			Ext.getCmp('collectType').setValue('0');
			Ext.getCmp('paymentType').setValue('alloc');
			GetCashierPrep("DP");

			submit_window_ADP.show();
			submit_window_ADP.setTitle('Allocate Down-Payment Entry - Add');
			submit_window_ADP.setPosition(320,23);
		}
	}, '-', {
		text:'<b>Allocate Inter-branch Payment</b>',
		tooltip: 'Allocate Inter-branch Payment',
		icon: '../js/ext4/examples/shared/icons/table_relationship.png',
		scale: 'small',
		handler: function(){
			submit_form_AIB.getForm().reset();
			
			CustomerStore.proxy.extraParams = {module: "INTERB"};
			CustomerStore.load();
			AllocationStore.proxy.extraParams = {transNo: 0 };
			AllocationStore.load();
			
			/*cashierStore.load({
				callback: function(records) {                 
        			Ext.getCmp('cashier_aib').setValue(records[i].get('id'));
				}
			});*/

			Ext.getCmp('moduletype_aib').setValue('ALCN-INTERB');
			Ext.getCmp('collectType_aib').setValue('0');
			Ext.getCmp('paymentType_aib').setValue('alloc');
			GetCashierPrep("interb");

			submit_window_AIB.show();
			submit_window_AIB.setTitle('Allocate Inter-Branch Payment Entry - Add');
			submit_window_AIB.setPosition(320,23);
		}
	}, '-', {
		text:'<b>Allocate Other Adjustment</b>',
		tooltip: 'Waived Penalty Adjustment',
		icon: '../js/ext4/examples/shared/icons/coins-in-hand-icon.png',
		scale: 'small',
		handler: function(){
			submit_form_waivpnlty.getForm().reset();
			GetCashierPrep("waived");
			CustomerStore.proxy.extraParams = {module: "waived"};
			CustomerStore.load();

			submit_window_waivpnlty.show();
			submit_window_waivpnlty.setTitle('Waived Penalty Adjustment - Add');
			submit_window_waivpnlty.setPosition(320,23);
		}
	}, '->' ,{
		xtype:'splitbutton',
		//text: '<b>Maintenance</b>',
		tooltip: 'Select...',
		icon: '../js/ext4/examples/shared/icons/cog_edit.png',
		scale: 'small',
		menu:[{
			text: '<b>Customer List</b>',
			icon: '../js/ext4/examples/shared/icons/map_magnify.png',
			href: '../sales/inquiry/customers_list.php?popup=1&client_id=customer_id',
			hrefTarget : '_blank'
		}]
	}];
	var tbarCOA = [{
		xtype: 'searchfield',
		id:'searchCOA',
		name:'searchCOA',
		fieldLabel: '<b>Search</b>',
		labelWidth: 50,
		width: 305,
		emptyText: "Search",
		scale: 'small',
		store: GLStore,
		listeners: {
			change: function(field) {
				GLStore.proxy.extraParams = {query: field.getValue()};
				GLStore.load();
			}
		}
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
			store:	done_allocate_store,
			columns: InterB_Payment_Header,
			columnLines: true,
			autoScroll:true,
			layout:'fit',
			frame: true,
			bbar : {
				xtype : 'pagingtoolbar',
				hidden: false,
				store : done_allocate_store,
				pageSize : itemsPerPage,
				displayInfo : false,
				emptyMsg: "No records to display",
				doRefresh : function(){
					done_allocate_store.load();
					
				}
			}
		}]
	});

	function Getreference($tag){
		Ext.Ajax.request({
			url : '?getReference=zHun',
			async:false,
			success: function (response){
				var result = Ext.JSON.decode(response.responseText);
				if($tag == "DP"){
					Ext.getCmp('ref_no').setValue(result.reference);
					submit_window_ADP.setTitle('Allocate Down-Payment Entry - Reference No. : '+ result.reference + ' *new');
				}else if($tag == "waived"){
					Ext.getCmp('ref_no_wv').setValue(result.reference);
					submit_window_waivpnlty.setTitle('Allocate Adjustment Entry - Reference No. : '+ result.reference + ' *new');
				}else{
					Ext.getCmp('ref_no_aib').setValue(result.reference);
					submit_window_AIB.setTitle('Allocate Inter-Branch Payment Entry - Reference No. : '+ result.reference + ' *new');
				}
				
			}
		});
	};
	function GetCashierPrep($tag){
		Ext.Ajax.request({
			url : '?get_cashierPrep=zHun',
			async:false,
			success: function (response){
				var result = Ext.JSON.decode(response.responseText);
				if($tag == "DP"){
					//Ext.getCmp('cashier').setValue(result.cashier);
					Ext.getCmp('preparedby').setValue(result.prepare);
				}else if($tag == "interb"){
					//Ext.getCmp('cashier_aib').setValue(result.cashier);
					Ext.getCmp('preparedby_aib').setValue(result.prepare);
				}else if($tag == "waived"){
					//Ext.getCmp('cashier_aib').setValue(result.cashier);
					Ext.getCmp('preparedby_wv').setValue(result.prepare);
				}
			}
		});
	};
	function getUrlParameter(sParam) {
		var sPageURL = decodeURIComponent(window.location.search.substring(1)),
			sURLVariables = sPageURL.split('&'),
			sParameterName,
			i;

		for (i = 0; i < sURLVariables.length; i++) {
			sParameterName = sURLVariables[i].split('=');

			if (sParameterName[0] === sParam) {
				return sParameterName[1] === undefined ? true : sParameterName[1];
			}
		}
	};
	function loadCOA($tag, $gl_code, $id=0,$type=0){
		var gridData = InterCOAStore.getRange();
		var OEData = [];

		Ext.each(gridData, function(item) {
			var ObjItem = {
				id: item.get('id'),  
				gl_code: item.get('gl_code'),
				gl_name: item.get('gl_name'),
				sl_code: item.get('sl_code'),
				sl_name: item.get('sl_name'),
				debtor_id: item.get('debtor_id'),
				debit_amount: item.get('debit_amount'),
				credit_amount: item.get('credit_amount')
			};
			OEData.push(ObjItem);
		});

		InterCOAStore.proxy.extraParams = {
			DataOEGrid: Ext.encode(OEData),
			debtor_id: Ext.getCmp('customername_wv').getValue(),
			date_issue: Ext.getCmp('trans_date_wv').getValue(),
			gl_account: $gl_code,
			tag: $tag,
			transNo: $id,
			transtype: $type,
			amounttotal: Ext.getCmp('total_debt_wv').getValue(),
			amounttenderd: Ext.getCmp('total_cred_wv').getValue()
		};
		InterCOAStore.load();
	};
});
