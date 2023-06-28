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

Ext.onReady(function() {
	Ext.QuickTips.init();
	var itemsPerPage = 18;   // set the number of items you want per page on grid.
	var GridItemOnTab = 7;
	var showall = false;
	var sbranch_code;
	var sbranch_name;
	var sbranch_gl;
	var colheadtag;

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
	Ext.define('InvoiceModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'name', mapping:'name'},
			{name:'type', mapping:'type'},
			{name:'status', mapping:'status'},
			{name:'paylocation', mapping:'paylocation'}
		]
    });
	Ext.define('schedModel',{
		extend : 'Ext.data.Model',
		fields  : [
			{name:'loansched_id',mapping:'loansched_id'},
			{name:'debtor_id',mapping:'debtor_id'},
			{name:'trans_no',mapping:'trans_no'},
			{name:'date_due',mapping:'date_due'},
			{name:'maturity_date',mapping:'maturity_date'},
			{name:'mosterm',mapping:'mosterm'},
			{name:'amortization',mapping:'amortization'},
			{name:'rebate',mapping:'rebate',type:'float'},
			{name:'totalrebate',mapping:'totalrebate',type:'float'},
			{name:'penalty',mapping:'penalty',type:'float'},
			{name:'penaltyBal',mapping:'penaltyBal',type: 'float'},
			{name:'partialpayment',mapping:'partialpayment',type: 'float'},
			{name:'totalpayment',mapping:'totalpayment',type: 'float'},
			{name:'runningbalance',mapping:'runningbalance'},
			{name:'status',mapping:'status'}
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
			{name:'comptdGPM',mapping:'comptdGPM', type:'float'},
			{name:'downpayment',mapping:'downpayment', type:'float'},
			{name:'dp_discount',mapping:'dp_discount', type:'float'},
			{name:'balance',mapping:'balance', type:'float'}
		]
	});
	Ext.define('AllocCashModel',{
		extend : 'Ext.data.Model',
		fields  : [
			{name:'debtor_id',mapping:'debtor_id'},
			{name:'trans_no',mapping:'trans_no'},
			{name:'due_date',mapping:'due_date'},
			{name:'ar_due',mapping:'ar_due',type:'float'},
			{name:'partialpayment',mapping:'partialpayment',type: 'float'},
			{name:'totalpayment',mapping:'totalpayment',type: 'float'},
			{name:'alloc_amount_cash',mapping:'alloc_amount_cash',type:'float'},
			{name:'balance',mapping:'balance',type: 'float'},
			{name:'cash_discount',mapping:'cash_discount',type: 'float'}
		]
	});
	Ext.define('OtherEntryModel',{
		extend : 'Ext.data.Model',
		fields  : [
			{name:'id',mapping:'id'},
			{name:'gl_code',mapping:'gl_code'},
			{name:'gl_name',mapping:'gl_name'},
			{name:'sl_code',mapping:'sl_code'},
			{name:'sl_name',mapping:'sl_name'},
			{name:'debtor_id',mapping:'debtor_id'},
			{name:'debit_amount',mapping:'debit_amount',type:'float'},
			{name:'bankaccount',mapping:'bankaccount'},
			{name:'otref_no',mapping:'otref_no'}			
		]
	});
	Ext.define('interBModel',{
		extend : 'Ext.data.Model',
		fields  : [
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
	var smCheckCashGrid = Ext.create('Ext.selection.CheckboxModel',{
		mode: 'SINGLE'
	});
	var cellEditing = Ext.create('Ext.grid.plugin.CellEditing',{
        clicksToEdit: 2,
        listeners: {
            edit: function(grid){
                // refresh summaries
                grid.getView().refresh();
            }
        }
    });
	var PaymentTypeStore = Ext.create('Ext.data.Store', {
		fields: ['id','name'],
		proxy: {
			url: '?get_PaymentType=00',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true
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
	var BankBrStore = Ext.create('Ext.data.Store', {
		fields: ['value'],
		autoLoad : true,
		simpleSortMode : true,
		proxy: {
			url: '?get_BankB=00',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		}
	});
	var PaymentStore = Ext.create('Ext.data.Store', {
		model: 'CustomerPay_model',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_custPayment=zHun&module_type=PDC',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'tran_date',
			direction : 'DESC'
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
	var IntoBankAcctStore = Ext.create('Ext.data.Store', {
		model: 'comboModel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_IntoBank=xx',
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
	var DPitemStore = Ext.create('Ext.data.Store', {
		model: 'interBModel',
		name : 'DPitemStore',
		method : 'POST',
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_DownPaymnt=xx',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		}
	});
	var RecptViewStore_inb = Ext.create('Ext.data.Store', {
		model: 'interBModel',
		//autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_interb_view=interb',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true
	});
	var allocCash_store = Ext.create('Ext.data.Store', {
		model: 'AllocCashModel',
		method : 'POST',
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_alocCash=xx',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		}
	});
	var allocCash_store_view = Ext.create('Ext.data.Store', {
		model: 'AllocCashModel',
		method : 'POST',
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_alocCash_view=xx',
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
	var OtherEntryStore = Ext.create('Ext.data.Store', {
		model: 'OtherEntryModel',
		name : 'OtherEntryStore',
		method : 'POST',
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_OtherEntryPay=xx',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		}
	});

	var ColumnModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>trans_no</b>',dataIndex:'trans_no',hidden: true},
		{header:'<b>Date</b>', dataIndex:'tran_date', sortable:true, width:80, renderer: Ext.util.Format.dateRenderer('m-d-Y')},
		{header:'<b>Reference No.</b>', dataIndex:'reference', sortable:true, width:150},
		{header:'<b>Receipt No.</b>', dataIndex:'receipt_no', sortable:true, width:100},
		{header:'<b>Customer Name</b>', dataIndex:'customer_name', sortable:true, width:180,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Type</b>', dataIndex:'pay_type', sortable:true, width:60},
		{header:'<b>Payment Type</b>', dataIndex:'payment_type_v', sortable:true, width:118},
		{header:'<b>Amount</b>', dataIndex:'total_amount', sortable:true, width:90,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
			}
		},
		{header:'<b>Cashier</b>', dataIndex:'cashier_name', sortable:true, width:150,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Particulars</b>', dataIndex:'remarks', sortable:true, width:180,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:105,
			items:[{
				icon: '../js/ext4/examples/shared/icons/layout_content.png',
				tooltip: 'view details',
				handler: function(grid, rowIndex, colIndex) {
					var records = PaymentStore.getAt(rowIndex);

						ARInvoiceStore.proxy.extraParams = {debtor_id: records.get('debtor_no'), tag: "cash"};
						ARInvoiceStore.load();
						PaymentTypeStore.proxy.extraParams = {type: "cash"};
						PaymentTypeStore.load();
						CollectionTypeStore.proxy.extraParams = {type: "amort"};
						CollectionTypeStore.load();
						
						allocCash_store_view.proxy.extraParams = {transNo: records.get('invoice_no'), debtor_no: records.get('debtor_no'), transtype: records.get('trans_typeTo')};
						allocCash_store_view.load();
						
						Ext.getCmp('v_syspk_cash').setValue(records.get('trans_no'));
						Ext.getCmp('v_transtypeFr_cash').setValue(records.get('trans_typeFr'));
						Ext.getCmp('v_transtypeTo_cash').setValue(records.get('trans_typeTo'));
						Ext.getCmp('v_paymentType_cash').setValue(records.get('payment_type'));
						Ext.getCmp('v_collectType_cash').setValue(records.get('collect_type'));
						Ext.getCmp('v_customercode_cash').setValue(records.get('debtor_ref'));
						Ext.getCmp('v_customername_cash').setValue(records.get('debtor_no'));
						Ext.getCmp('v_trans_date_cash').setValue(records.get('tran_date'));
						Ext.getCmp('v_InvoiceNo_cash').setValue(records.get('invoice_no'));
						Ext.getCmp('v_receipt_no_cash').setValue(records.get('receipt_no'));
						Ext.getCmp('v_intobankacct_cash').setValue(records.get('Bank_account_id'));
						Ext.getCmp('v_total_amount_cash').setValue(records.get('total_amount'));
						Ext.getCmp('v_remarks_cash').setValue(records.get('remarks'));
						Ext.getCmp('v_check_date_cash').setValue(records.get('check_date'));
						Ext.getCmp('v_check_no_cash').setValue(records.get('check_no'));
						Ext.getCmp('v_Bank_branch_cash').setValue(records.get('Bank_branch'));
						Ext.getCmp('v_cashier_cash').setValue(records.get('cashier_name'));
						Ext.getCmp('v_preparedby_cash').setValue(records.get('prepared_by'));

						submit_window_cashview.setTitle('Cash Payment Receipt Details - Reference No. :'+ records.get('reference'));
						submit_window_cashview.show();
						submit_window_cashview.setPosition(320,55);
						
				}
			},'-',{
				icon   : '../js/ext4/examples/shared/icons/chart_line.png',
				tooltip : 'Entries',
				handler : function(grid, rowIndex, colIndex){
					var records = PaymentStore.getAt(rowIndex);
					window.open('../gl/view/gl_trans_view.php?type_id=12&trans_no='+ records.get('trans_no'));
				}
			},'-',{
				icon: '../js/ext4/examples/shared/icons/print-preview-icon.png',
				tooltip: 'view reports',
				handler: function(grid, rowIndex, colIndex) {
					var records = PaymentStore.getAt(rowIndex);

					Ext.getCmp('rpt_syspk').setValue(records.get('reference'));
					Ext.getCmp('rpt_transnum').setValue(records.get('trans_no'));
					Ext.getCmp('rpt_receipt').setValue(records.get('receipt_no'));

					report_window.setTitle('List Of Reports');
					report_window.show();
					report_window.setPosition(500,150);
				}
			}]
		}
	];
	var AllocCash_Header = [
		{header:'<b>Trans No.</b>', dataIndex:'trans_no', width:90},
		{header:'<b>Due Date</b>', dataIndex:'due_date', width:95},
		{header:'<b>Total AR Due</b>', dataIndex:'ar_due', align:'right', width:130, 
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:green;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			}
		},
		{header:'<b>Discount</b>', dataIndex:'cash_discount', align:'right', width:120, 
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:green;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			}
		},
		{header:'<b>Partial</b>', dataIndex:'partialpayment', width:100, /* align:'right'*/
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:blue;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			},
		},
		{header:'<b>Total Payment</b>', dataIndex:'totalpayment', width:120, align:'right', summaryType: 'sum',
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
		{header:'<b>Credit Amount</b>', dataIndex:'alloc_amount_cash', width:140, align:'right', summaryType: 'sum',
			renderer: Ext.util.Format.Currency = function(value, metaData, record, rowIdx, colIdx, store, view){
				metaData.tdAttr = 'data-qtip="<b> Click to Enter Payment Here! </b>"';
				if(value == 0){
					value = '0.00';
					return '<span style="color:red;">' + (value) + '</span>';
				}else{
					return '<span style="color:green;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			},
			 editor: new Ext.form.TextField({
				xtype:'textfield',
				id: 'alloc_amount_cash',
				name: 'alloc_amount_cash',
				allowBlank: false,
				listeners : {
					change: function(editor, e){
						Ext.getCmp('tenderd_amount_cash').setValue(e);					  
					}
				},
			}), 
			summaryRenderer: function(value, summaryData, dataIndex){
				return '<span style="color:blue;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';									
			}
		}
	];
	var AllocCash_Header_view = [
		{header:'<b>Trans No.</b>', dataIndex:'trans_no', width:100},
		{header:'<b>Due Date</b>', dataIndex:'due_date', width:110},
		{header:'<b>Total AR Due</b>', dataIndex:'ar_due', align:'right', width:140, 
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:green;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			}
		},
		{header:'<b>Allocate Amount</b>', dataIndex:'alloc_amount_cash', width:200, align:'right', summaryType: 'sum',
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
		{header:'<b>Balance</b>', dataIndex:'balance', width:140, align:'right', summaryType: 'sum',
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
		}
	];

	var cashpayOtherEntryHeader = [
		{header:'<b>GL Code</b>', dataIndex:'gl_code', width:80},
		{header:'<b>Description</b>', dataIndex:'gl_name', width:225},
		{header:'<b>SL Code</b>', dataIndex:'sl_code', width:90},
		{header:'<b>SL Name</b>', dataIndex:'sl_name', width:172},
		{header:'<b>Ref. No.</b>', dataIndex:'otref_no', width:90,
			editor: new Ext.form.TextField({
				xtype:'textfield',
				id: 'otref_no_cashpay',
				name: 'otref_no_cashpay',
				allowBlank: false,
				listeners : {

				}
			})
		},
		{header:'<b>Amount</b>', dataIndex:'debit_amount', width:100, align:'right', summaryType: 'sum',
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:green;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			},
			summaryRenderer: function(value, summaryData, dataIndex){
				Ext.getCmp('total_otheramount_cashpay').setValue(value);
				return '<span style="color:blue;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
			},
			editor: new Ext.form.TextField({
				xtype:'textfield',
				id: 'otheramnt_cashpay',
				name: 'otheramnt_cashpay',
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
					var records = OtherEntryStore.getAt(rowIndex);
					loadOtherEntry('delete',records.get("id"), 'amort');
				}
			}]
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

	var tbar = [{
		xtype: 'searchfield',
		id:'search',
		name:'search',
		fieldLabel: '<b>Search</b>',
		labelWidth: 50,
		width: 290,
		emptyText: "Search by reference/customer name...",
		scale: 'small',
		store: PaymentStore,
		listeners: {
			change: function(field) {
				PaymentStore.proxy.extraParams = {query: field.getValue(), cashier: Ext.getCmp('fltr_cashier').getValue()};
				PaymentStore.load();
			}
		}
	}, '-', {
		text:'<b>Add PDC</b>',
		tooltip: 'Add new PDC Acknowldgement Receipts',
		icon: '../js/ext4/examples/shared/icons/money_add.png',
		scale: 'small',
		handler: function(){
			submit_form_cash.getForm().reset();
			
			Ext.getCmp('check_cash').setVisible(false);
			
			CustomerStore.load();
			PaymentTypeStore.proxy.extraParams = {type: "cash"};
			PaymentTypeStore.load();
			ARInvoiceStore.proxy.extraParams = {debtor_id: 0};
			ARInvoiceStore.load();
			OtherEntryStore.proxy.extraParams = {transNo: null};
			OtherEntryStore.load();
			/*cashierStore.load({
				callback: function(records) {                 
        			Ext.getCmp('cashier_cash').setValue(records[i].get('id'));
				}
			});*/

			//Ext.getCmp('intobankacct_cash').setValue(3);
			Ext.getCmp('total_otheramount_cashpay').setValue(0);
			Ext.getCmp('debit_acct_cash').setValue("1050");
			Ext.getCmp('paymentType_cash').setValue('other');
			Ext.getCmp('collectType_cash').setValue(1);//'office'
			Ext.getCmp('moduletype_cash').setValue('CI-CASH');
			GetCashierPrep("sicash");

			submit_window_cash.show();
			submit_window_cash.setTitle('Cash Payment Receipt Entry - Add');
			submit_window_cash.setPosition(320,23);
		}
	}, '->' ,{
		xtype:'splitbutton',
		//text: '<b>Maintenance</b>',
		tooltip: 'Select...',
		icon: '../js/ext4/examples/shared/icons/cog_edit.png',
		scale: 'small',
		menu:[{
			text: '<b>A/R Installment Inquiry</b>',
			icon: '../js/ext4/examples/shared/icons/map_magnify.png',
			href: '../lending/inquiry/ar_installment_inquiry.php?',
			hrefTarget : '_blank'
		},'-',{
			text: '<b>Add Inter-Branch Customers</b>',
			icon: '../js/ext4/examples/shared/icons/door_in.png',
			href: '../lending/manage/auto_add_interb_customers.php?',
			hrefTarget : '_blank'
		}]
	}];

	var cashpaytbar = [{
		xtype: 'textfield',
		id: 'othrdebit_acct_cashpay',
		name: 'othrdebit_acct_cashpay',
		fieldLabel: 'othrdebit_acct_cashpay',
		hidden: true
	},{
		xtype: 'combobox',
		id: 'otherintobankacct_cashpay',
		name: 'otherintobankacct_cashpay',
		//allowBlank: false,
		store : IntoBankAcctStore,
		displayField: 'name',
		valueField: 'id',
		queryMode: 'local',
		fieldLabel : 'Debit to ',
		labelWidth: 100,
		width: 300,
		forceSelection: true,
		selectOnFocus:true,
		fieldStyle: 'font-weight: bold; color: #210a04;',
		listeners: {
			select: function(combo, record, index) {
				Ext.getCmp('othrdebit_acct_cashpay').setValue(record.get("type"));
				loadOtherEntry('add',0, 'cashpay', record.get("id"));
			}
		}
	},{
		xtype: 'textfield',
		id: 'total_otheramount_cashpay',
		name: 'total_otheramount_cashpay',
		fieldLabel: 'Other Entry Total ',
		//allowBlank: false,
		//hidden: true,
		labelWidth: 123,
		readOnly: true,
		fieldStyle: 'font-weight: bold;color: #008000; text-align: right; background-color: #F2F3F4;',
		listeners: {
			change: function(object, value) {
				/*var otheramnt = Math.floor(Ext.getCmp('tenderd_amount_cash').getValue());
				var totalamnt = (parseFloat(otheramnt) + parseFloat(value));

				Ext.getCmp('total_amount_cash').setValue(totalamnt);
				loadInterBranch();*/
			}
		}
	}];

	var submit_form_cash = Ext.create('Ext.form.Panel', {
		id: 'submit_form_cash',
		model: 'AllocationModel',
		frame: true,
		defaultType: 'field',
		defaults: {msgTarget: 'under', labelWidth: 125, anchor: '-5'}, //msgTarget: 'side', labelAlign: 'top'
			items: [{
				xtype: 'textfield',
				id: 'syspk_cash',
				name: 'syspk_cash',
				fieldLabel: 'syspk',
				//allowBlank: false,
				hidden: true
			},{
				xtype: 'textfield',
				id: 'moduletype_cash',
				name: 'moduletype_cash',
				fieldLabel: 'moduletype',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'textfield',
				id: 'transtype_cash',
				name: 'transtype_cash',
				fieldLabel: 'transtype',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'textfield',
				id: 'pay_type_cash',
				name: 'pay_type_cash',
				fieldLabel: 'Pay type',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'textfield',
				id: 'debit_acct_cash',
				name: 'debit_acct_cash',
				fieldLabel: 'debit_acct',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'textfield',
				id: 'ref_no_cash',
				name: 'ref_no_cash',
				fieldLabel: 'ref_no',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					xtype: 'textfield',
					fieldLabel: 'Customer ',
					id: 'customercode_cash',
					name: 'customercode_cash',
					allowBlank: false,
					labelWidth: 105,
					width: 250,
					readOnly: true,
					fieldStyle: 'font-weight: bold; color: #210a04;'
				},{
					xtype: 'combobox',
					id: 'customername_cash',
					name: 'customername_cash',
					allowBlank: false,
					store : CustomerStore,
					displayField: 'name',
					valueField: 'debtor_no',
					queryMode: 'local',
					width: 310,
					anyMatch: true,
					forceSelection: true,
					selectOnFocus:true,
					fieldStyle: 'font-weight: bold; color: #210a04;',
					listeners: {
						select: function(combo, record, index) {
							Ext.getCmp('customercode_cash').setValue(record.get('debtor_ref'));
							Ext.getCmp('tenderd_amount_cash').setValue();
							Ext.getCmp('tenderd_amount_cash').focus(false, 200);

							//alert(Ext.getCmp('paymentType_cash').getValue());
							if(Ext.getCmp('paymentType_cash').getValue() == "adjmt"){
								$tag = "adjustment";
							}else{
								$tag = "cash";
							}
							ARInvoiceStore.proxy.extraParams = {debtor_id: record.get('debtor_no'), tag: $tag};
							ARInvoiceStore.load();

							Ext.Ajax.request({
								url : '?getReference=CI',
								params: {
									debtor_id: record.get('debtor_no'),
									date: Ext.getCmp('trans_date_cash').getValue()
								},
								async:false,
								success: function (response){
									var result = Ext.JSON.decode(response.responseText);
									Ext.getCmp('ref_no_cash').setValue(result.reference);
									submit_window_cash.setTitle('Cash Payment Receipt Entry - Reference No. : '+ result.reference + ' *new');
								}
							});
						}
					}
				},{
					xtype : 'datefield',
					id	  : 'trans_date_cash',
					name  : 'trans_date_cash',
					fieldLabel : 'Date ',
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
					id: 'InvoiceNo_cash',
					name: 'InvoiceNo_cash',
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
							Ext.getCmp('transtype_cash').setValue(record.get('type'));
							Ext.getCmp('tenderd_amount_cash').setValue();
							Ext.getCmp('tenderd_amount_cash').focus(false, 200);
							
							allocCash_store.proxy.extraParams = {transNo: record.get('id'), debtor_no: Ext.getCmp('customername_cash').getValue(), transtype: record.get('type')};
							allocCash_store.load();
							SIitemStore.proxy.extraParams = {transNo: record.get('id'), transtype: record.get('type')};
							SIitemStore.load();
						}
					}
				},{
					xtype: 'textfield',
					fieldLabel: 'Receipt No. ',
					id: 'receipt_no_cash',
					name: 'receipt_no_cash',
					margin: '2 0 0 0',
					allowBlank: false,
					enforceMaxLength: true,
					labelWidth: 100,
					width: 255,
					maxLength : 7,
					maskRe: /^([a-zA-Z0-9 _.,-`]+)$/,
					fieldStyle: 'font-weight: bold; color: #210a04;',
				}]
			},{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					xtype: 'combobox',
					fieldLabel: 'Cashier/Teller ',
					id: 'cashier_cash',
					name: 'cashier_cash',
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
					id: 'preparedby_cash',
					name: 'preparedby_cash',
					allowBlank: false,
					readOnly: true,
					labelWidth: 105,
					width: 280,
					fieldStyle: 'font-weight: bold; color: #210a04;'
				},{
					xtype: 'combobox',
					id: 'paymentType_cash',
					name: 'paymentType_cash',
					fieldLabel: 'Payment type ',
					store: PaymentTypeStore,
					displayField: 'name',
					valueField: 'id',
					queryMode: 'local',
					width: 255,
					margin: '0 0 2 0',
					allowBlank: false,
					forceSelection: true,
					selectOnFocus:true,
					editable: false,
					listeners: {
						select: function(combo, record, index) {
							Ext.getCmp('tenderd_amount_cash').setValue();
							Ext.getCmp('tenderd_amount_cash').focus(false, 200);

							ARInvoiceStore.proxy.extraParams = {debtor_id: Ext.getCmp('customername_cash').getValue(), tag: "adjustment"};
							ARInvoiceStore.load();
						}
					}
				}]
			},{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					xtype: 	'textareafield',
					fieldLabel: 'Remarks ',
					id:	'remarks_cash',
					name: 'remarks_cash',
					//labelAlign:	'top',
					allowBlank: false,
					maxLength: 254,
					labelWidth: 105,
					width: 560,
					hidden: false
				},{
					xtype: 'fieldcontainer',
					layout: 'vbox',
					margin: '0 0 0 0',
					items:[{
						xtype: 'combobox',
						id: 'collectType_cash',
						name: 'collectType_cash',
						fieldLabel: 'Collction type ',
						store: CollectionTypeStore,
						displayField: 'name',
						valueField: 'id',
						queryMode: 'local',
						width: 255,
						margin: '0 0 2 0',
						allowBlank: false,
						forceSelection: true,
						selectOnFocus:true,
						editable: false
					},{
						xtype: 'numericfield',
						id: 'total_amount_cash',
						name: 'total_amount_cash',
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
				}]
			},{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					xtype: 'combobox',
					id: 'intobankacct_cash',
					name: 'intobankacct_cash',
					allowBlank: false,
					store : IntoBankAcctStore,
					displayField: 'name',
					valueField: 'id',
					queryMode: 'local',
					fieldLabel : 'Into Bank Account ',
					labelWidth: 125,
					width: 537,
					forceSelection: true,
					selectOnFocus:true,
					fieldStyle: 'font-weight: bold; color: #210a04;',
					listeners: {
						select: function(combo, record, index) {
							Ext.getCmp('debit_acct_cash').setValue(record.get("type"));
							Ext.getCmp('tenderd_amount_cash').setValue();
							Ext.getCmp('tenderd_amount_cash').focus(false, 200);
						},
						change: function(object, value) {
							//console.log(value);
							if(value == 1){ //object.getRawValue()
								Ext.getCmp('check_cash').setVisible(false);
								Ext.getCmp('pay_type_cash').setValue('Cash');
							}else{
								Ext.getCmp('check_cash').setVisible(true);
								Ext.getCmp('pay_type_cash').setValue('Check');
							}
						}
					}
				},{
					xtype: 'numericfield',
					id: 'tenderd_amount_cash',
					name: 'tenderd_amount_cash',
					fieldLabel: 'Tendered Amount ',
					allowBlank:false,
					useThousandSeparator: true,
					labelWidth: 123,
					width: 278,
					thousandSeparator: ',',
					minValue: 0,
					fieldStyle: 'font-weight: bold;color: #008000; text-align: right; background-color: #F2F3F4;',
					listeners: {
						afterrender: function(field) {
							field.focus(true);
						},
						change: function(object, value) {

							if(Ext.getCmp('InvoiceNo_cash').getValue() != null){
								Ext.getCmp('alloc_amount_cash').setValue(value);
								
								var totalamnt = (parseFloat(value) + parseFloat(Math.floor(Ext.getCmp('total_otheramount_cashpay').getValue())));

								var ItemModel = Ext.getCmp('allocgrid_cash').getSelectionModel();
								var GridRecords = ItemModel.getLastSelected(); //getLastSelected();

								GridRecords.set("alloc_amount_cash",totalamnt);
							}
						}
					}
				}]
			},{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				id: 'check_cash',
				items:[{
					xtype : 'datefield',
					id	  : 'check_date_cash',
					name  : 'check_date_cash',
					fieldLabel : 'Check Date ',
					allowBlank: true,
					labelWidth: 105,
					width: 230,
					format : 'm/d/Y'
				},{
					xtype: 'textfield',
					fieldLabel: 'Check No. ',
					id: 'check_no_cash',
					name: 'check_no_cash',
					allowBlank: true,
					labelWidth: 78,
					width: 230,
					fieldStyle: 'font-weight: bold; color: #210a04;'
				},{
					xtype: 'combobox',
					id: 'bank_branch_cash',
					name: 'bank_branch_cash',
					fieldLabel: 'Bank Branch ',
					allowBlank: true,
					store : BankBrStore,
					displayField: 'value',
					valueField: 'value',
					queryMode: 'local',
					labelWidth: 95,
					width: 355,
					//forceSelection: true,
					selectOnFocus:true,
					fieldStyle: 'font-weight: bold; color: #210a04;'
				}]
			},{
				xtype: 'tabpanel',
				id: 'alloctabpanel_cash',
				activeTab: 0,
				width: 860,
				scale: 'small',
				items:[{
					xtype:'gridpanel',
					id: 'allocgrid_cash',
					anchor:'100%',
					layout:'fit',
					title: 'Allocate Receipt',
					icon: '../js/ext4/examples/shared/icons/page_attach.png',
					loadMask: true,
					height: 130,
					store:	allocCash_store,
					columns: AllocCash_Header,
					selModel: smCheckCashGrid,
					columnLines: true,
					features: [{ftype: 'summary'}],
					plugins: [cellEditing],
					viewConfig : {
						listeners : {
							cellclick : function(view, cell, cellIndex, record, row, rowIndex, e) {
								//alert( record.get("totalpayment") + ' ' + (rowIndex+1));
								Ext.getCmp("total_amount_cash").setValue(record.get("totalpayment"));
								//Ext.getCmp('tenderd_amount_cash').setValue();
								Ext.getCmp('tenderd_amount_cash').focus(false, 200);

								if(Ext.getCmp('total_otheramount_cashpay').getValue() != 0){
									var ItemModel = Ext.getCmp('allocgrid_cash').getSelectionModel();
									var GridRecords = ItemModel.getLastSelected();

									var otheramnt = Math.floor(Ext.getCmp('tenderd_amount_cash').getValue());
									var totalamnt = (parseFloat(otheramnt) + parseFloat(Ext.getCmp('total_otheramount_cashpay').getValue()));

									GridRecords.set("alloc_amount_cash",totalamnt);
								}
							}
						}
					}
				},{
					xtype:'gridpanel',
					id: 'OtherEntriesGrid_cashpay',
					anchor:'100%',
					layout:'fit',
					tbar: cashpaytbar,
					selModel: 'cellmodel',
					plugins: {
						ptype: 'cellediting',
						clicksToEdit: 1
					},
					title: 'Other Entry',
					icon: '../js/ext4/examples/shared/icons/page_add.png',
					loadMask: true,
					store:	OtherEntryStore,
					columns: cashpayOtherEntryHeader,
					features: [{ftype: 'summary'}],
					columnLines: true
				},{
					xtype:'gridpanel',
					id: 'ItemGrid',
					anchor:'100%',
					layout:'fit',
					title: 'Item Details',
					icon: '../js/ext4/examples/shared/icons/lorry_flatbed.png',
					loadMask: true,
					height: 250,
					store:	SIitemStore,
					columns: Item_view,
					columnLines: true
				}]
			}]
	});
	var submit_window_cash = Ext.create('Ext.Window',{
		width 	: 842,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form_cash],
		buttons:[{
			text: '<b>Save</b>',
			tooltip: 'Save customer payment',
			icon: '../js/ext4/examples/shared/icons/add.png',
			single : true,
			handler:function(){
				var HaveErrors = 0;
				var $message;
				var submit_form_cash = Ext.getCmp('submit_form_cash').getForm();
				if(Ext.getCmp('alloc_amount_cash').getValue() != Ext.getCmp('tenderd_amount_cash').getValue()){
					$message = "Tendered amount and credit amount are not equal. Please review all data before saving.";
					HaveErrors = 1;
				}
				if (HaveErrors == 1){
					Ext.Msg.show({
						title: 'Error!',
						msg: '<font color="red">' + $message + '</font>',
						buttons: Ext.Msg.OK,
						icon: Ext.MessageBox.ERROR
					});
					return false;
				}
				if(submit_form_cash.isValid()) {
					var AllocationModel = allocCash_store.model;
					var TotalFld = AllocationModel.getFields().length -1;
					var records = allocCash_store.getModifiedRecords();
					var gridData = [];
					var params = {};
					var count = 0;
					for(var i = 0; i < records.length; i++){
						Ext.each(AllocationModel.getFields(), function(field){
							params[field.name] = records[i].get(field.name);
							//params[field.name+'['+ i +']'] = records[i].get(field.name);
							count++;
							if(count == TotalFld){
								gridData.push(params);
								params = {};
								count = 0;
							}
						});
					}
					//other entries
					if(Ext.getCmp('total_otheramount_cashpay').getValue() != 0){
						var gridOEData = OtherEntryStore.getRange();
						var OEData = [];
						
						Ext.each(gridOEData, function(item) {
							var ObjItem = {
								id: item.get('id'),  
								gl_code: item.get('gl_code'),
								gl_name: item.get('gl_name'),
								sl_code: item.get('sl_code'),
								sl_name: item.get('sl_name'),
								debtor_id: item.get('debtor_id'),
								debit_amount: item.get('debit_amount'),
								bankaccount: item.get('bankaccount'),
								otref_no: item.get('otref_no')
							};
							OEData.push(ObjItem);
						});
					}
					//console.log(Ext.decode(gridData));
					submit_form_cash.submit({
						url: '?submitPDCAck=payment',
						waitMsg: 'Saving payment for Invoice No.' + Ext.getCmp('InvoiceNo_cash').getRawValue() + '. please wait...',
						method:'POST',
						submitEmptyText: false,
						success: function(submit_form_cash, action) {
							PaymentStore.load()
							Ext.Msg.alert('Success!', '<font color="green">' + action.result.message + '</font>');
							submit_window_cash.close();
						},
						failure: function(submit_form_cash, action) {
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
			icon: '../js/ext4/examples/shared/icons/cancel.png',
			handler:function(){
				Ext.MessageBox.confirm('Confirm:', 'Are you sure you wish to close this window?', function (btn, text) {
					if (btn == 'yes') {
						//Ext.Msg.alert('Close','close.');
						submit_form_cash.getForm().reset();
						submit_window_cash.close();
					}
				});
			}
		}]
	});

	var Customer_Payment =  Ext.create('Ext.panel.Panel', { 
        renderTo: 'ext-form',
		id: 'cust_pay',
        frame: false,
		width: 1250,
		tbar: tbar,
		items: [{
			xtype: 'grid',
			id: 'GridCustomerPayment',
			name: 'GridCustomerPayment',
			store:	PaymentStore,
			columns: ColumnModel,
			columnLines: true,
			autoScroll:true,
			layout:'fit',
			frame: true,
			bbar : {
				xtype : 'pagingtoolbar',
				hidden: false,
				store : PaymentStore,
				pageSize : itemsPerPage,
				displayInfo : false,
				emptyMsg: "No records to display",
				doRefresh : function(){
					PaymentStore.load();
				},
				items:[ '->',{
						xtype: 'combobox',
						id: 'fltr_cashier',
						name: 'fltr_cashier',
						fieldLabel: 'View by cashier/Teller ',
						store: cashierStore,
						displayField: 'name',
						valueField: 'id',
						queryMode: 'local',
						labelWidth: 145,
						width: 350,
						forceSelection: true,
						selectOnFocus:true,
						enableKeyEvents: true,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						listeners: {
							select: function(combo, record, index) {
								PaymentStore.proxy.extraParams = {query: Ext.getCmp('search').getValue(), cashier: record.get('id')};
								PaymentStore.load();
							},
							keydown: function(obj, e) {
								if (e.getCharCode() == e.BACKSPACE) {
									PaymentStore.proxy.extraParams = {query: Ext.getCmp('search').getValue(), cashier: null};
									PaymentStore.load();
								}
							}
						}
					}
				]
			},
			viewConfig: {
				listeners: {
					refresh: function(view) {
						// get all grid view nodes
						var nodes = view.getNodes();
						
						for (var i = 0; i < nodes.length; i++) {
							var node = nodes[i];
							var record = view.getRecord(node);
							// get all td elements
							var cells = Ext.get(node).query('td');  
							// set bacground color to all row td elements
							for(var j = 0; j < cells.length; j++) {
								//console.log(cells[j]);
								if(record.get('status') == "Draft"){
									Ext.fly(cells[j]).setStyle('background-color', "#f8cbcb");
								}else if(record.get('status') == "Voided"){
									Ext.fly(cells[j]).setStyle('background-color', "#716e6e");
								}
							}
							//Ext.getCmp('changeterm');
							//Ext.getCmp('changeterm').isDisabled(true);
							if(record.get('payment_loc') == 'Lending'){
								//Ext.getCmp('changeterm').iconCls= 'btnchangetrm';
								//Ext.getCmp('showlending').setVisible(false);
							}else{
								//Ext.getCmp('showlending').setVisible(true);
							}
						}
					}
				}
			}
		}]
	});

	var submit_form_cashview = Ext.create('Ext.form.Panel', {
		id: 'submit_form_cashview',
		model: 'AllocationModel',
		frame: true,
		defaultType: 'field',
		items: [{
			xtype: 'textfield',
			id: 'v_syspk_cash',
			name: 'v_syspk_cash',
			fieldLabel: 'syspk',
			//allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'v_transtypeFr_cash',
			name: 'v_transtypeFr_cash',
			fieldLabel: 'transtypeF',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'v_transtypeTo_cash',
			name: 'v_transtypeTo_cash',
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
				id: 'v_customercode_cash',
				name: 'v_customercode_cash',
				allowBlank: false,
				labelWidth: 105,
				width: 250,
				readOnly: true,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'combobox',
				id: 'v_customername_cash',
				name: 'v_customername_cash',
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
					
				}
			},{
				xtype : 'datefield',
				id	  : 'v_trans_date_cash',
				name  : 'v_trans_date_cash',
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
				id: 'v_InvoiceNo_cash',
				name: 'v_InvoiceNo_cash',
				store : ARInvoiceStore,
				displayField: 'name',
				valueField: 'id',
				queryMode: 'local',
				fieldLabel : '<b>Invoice No. </b>',
				labelWidth: 105,
				width: 560,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'textfield',
				fieldLabel: '<b>Receipt No.</b>',
				id: 'v_receipt_no_cash',
				name: 'v_receipt_no_cash',
				margin: '2 0 0 0',
				allowBlank: false,
				readOnly: true,
				labelWidth: 100,
				width: 255,
				maskRe: /^([a-zA-Z0-9 _.,-`]+)$/,
				fieldStyle: 'font-weight: bold; color: #210a04;',
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'combobox',
				id: 'v_intobankacct_cash',
				name: 'v_intobankacct_cash',
				allowBlank: false,
				store : IntoBankAcctStore,
				displayField: 'name',
				valueField: 'id',
				queryMode: 'local',
				fieldLabel : '<b>Into Bank Account </b>',
				labelWidth: 125,
				width: 560,
				forceSelection: true,
				selectOnFocus:true,
				fieldStyle: 'font-weight: bold; color: #210a04;',
				listeners: {
					change: function(object, value) {
						if(value == 1){ //object.getRawValue()
							Ext.getCmp('v_check_cash').setVisible(false);
						}else{
							Ext.getCmp('v_check_cash').setVisible(true);
						}
					}
				}
			},{
				xtype: 'combobox',
				id: 'v_paymentType_cash',
				name: 'v_paymentType_cash',
				fieldLabel: 'Payment type ',
				store: PaymentTypeStore,
				displayField: 'name',
				valueField: 'id',
				queryMode: 'local',
				width: 255,
				forceSelection: true,
				selectOnFocus:true,
				editable: false
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'textfield',
				fieldLabel: 'Cashier/Teller ',
				id: 'v_cashier_cash',
				name: 'v_cashier_cash',
				allowBlank: false,
				readOnly: true,
				labelWidth: 105,
				width: 280,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'textfield',
				fieldLabel: 'Prepared By ',
				id: 'v_preparedby_cash',
				name: 'v_preparedby_cash',
				allowBlank: false,
				readOnly: true,
				labelWidth: 105,
				width: 280,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'combobox',
				id: 'v_collectType_cash',
				name: 'v_collectType_cash',
				fieldLabel: 'Collction type ',
				store: CollectionTypeStore,
				displayField: 'name',
				valueField: 'id',
				queryMode: 'local',
				margin: '0 0 2 0',
				width: 255,
				allowBlank: false,
				forceSelection: true,
				selectOnFocus:true,
				editable: false
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 	'textareafield',
				fieldLabel: '<b>Remarks </b>',
				id:	'v_remarks_cash',
				name: 'v_remarks_cash',
				//labelAlign:	'top',
				allowBlank: true,
				maxLength: 254,
				labelWidth: 105,
				width: 560,
				hidden: false
			},{
				xtype: 'fieldcontainer',
				layout: 'vbox',
				margin: '0 0 0 0',
				items:[{
					xtype: 'numericfield',
					id: 'v_total_amount_cash',
					name: 'v_total_amount_cash',
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
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 5 5',
			id: 'v_check_cash',
			items:[{
				xtype : 'datefield',
				id	  : 'v_check_date_cash',
				name  : 'v_check_date_cash',
				fieldLabel : '<b>Check Date </b>',
				allowBlank: true,
				labelWidth: 105,
				width: 230,
				format : 'm/d/Y'
			},{
				xtype: 'textfield',
				fieldLabel: '<b>Check No. </b>',
				id: 'v_check_no_cash',
				name: 'v_check_no_cash',
				allowBlank: true,
				labelWidth: 78,
				width: 230,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'combobox',
				id: 'v_Bank_branch_cash',
				name: 'v_Bank_branch_cash',
				fieldLabel: '<b>Bank Branch </b>',
				allowBlank: true,
				store : BankBrStore,
				displayField: 'value',
				valueField: 'value',
				queryMode: 'local',
				labelWidth: 95,
				width: 355,
				//forceSelection: true,
				selectOnFocus:true,
				fieldStyle: 'font-weight: bold; color: #210a04;'
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
				store:	allocCash_store_view,
				columns: AllocCash_Header_view,
				columnLines: true,
				features: [{ftype: 'summary'}]
			}],
			tabBar: {
				items: [{
					xtype: 'button',
					id: 'btnVoidSR',
					text: 'Void',
					padding: '3px',
					margin: '2px 2px 6px 580px',
					icon: '../js/ext4/examples/shared/icons/ipod_cast_delete.png',
					tooltip: 'Void Transaction',
					style : {
						'color': 'black',
						'font-size': '30px',
						'font-weight': 'bold',
						'background-color': '#f71d04',
						'position': 'absolute',
						'box-shadow': '0px 0px 2px 2px rgb(0,0,0)',
						//'border': 'none',
						'border-radius':'3px'
					},
					handler: function(){
						Ext.MessageBox.confirm('Confirmation:', 'Are you sure you wish to void this transaction?', function (btn, text) {
							if (btn == 'yes') {
								Ext.MessageBox.prompt('Void Receipt', 'Reason for Void:', function(btn, text){
									if (btn == 'ok'){
										Ext.Ajax.request({
											url : '?submitVoid=payment&syspk='+Ext.getCmp('v_syspk_cash').getValue()+'&systype='+Ext.getCmp('v_transtypeFr_cash').getValue()+'&reason='+text,
											//waitMsg: 'Saving downpayment. please wait...',
											method:'POST',
											//async:false,
											success: function (response) {
												var result = Ext.JSON.decode(response.responseText);
												PaymentStore.load();
												if (result.success == "true") {
													Ext.Msg.show({
														title: 'Void Transaction: Success!',
														msg: '<font color="green">' + result.message + '</font>',
														buttons: Ext.Msg.OK,
														icon: Ext.MessageBox.INFORMATION
													});
												}
												else {
													Ext.Msg.show({
														title: 'Void Transaction: Failed!',
														msg: result.message,
														buttons: Ext.Msg.OK,
														icon: Ext.MessageBox.ERROR
													});
												}
												submit_window_view.close();
											},
											failure: function () {
												Ext.Msg.show({
													title: 'Void Transaction: Failed!',
													msg: result.message,
													buttons: Ext.Msg.OK,
													icon: Ext.MessageBox.ERROR
												});
											}
										});
									}
								});
							}
						});
					}
				}]
			}
		}]
	});
	var submit_window_cashview = Ext.create('Ext.Window',{
		width 	: 842,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form_cashview],
		buttons:[{
			text:'<b>Close</b>',
			tooltip: 'Close window',
			icon: '../js/ext4/examples/shared/icons/cancel.png',
			handler:function(){
				submit_form_cashview.getForm().reset();
				submit_window_cashview.close();
			}
		}]
	});

	var report_form = Ext.create('Ext.form.Panel', {
		id: 'report_form',
		model: 'AllocationModel',
		//frame: true,
		margin: '2 2 2 2',
		items: [{
			xtype: 'textfield',
			id: 'rpt_syspk',
			name: 'rpt_syspk',
			fieldLabel: 'rpt_syspk',
			//allowBlank: false,
			hidden: true
		}, {
			xtype: 'textfield',
			id: 'rpt_transnum',
			name: 'rpt_transnum',
			fieldLabel: 'rpt_transnum',
			//allowBlank: false,
			hidden: true
		}, {
			xtype: 'textfield',
			id: 'rpt_receipt',
			name: 'rpt_receipt',
			fieldLabel: 'rpt_receipt',
			//allowBlank: false,
			hidden: true
		}, {
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 2',
			items:[{
				xtype: 'button',
				cls: 'rptbtn',
				width: 200,
				text:'<b>Collection Receipt</b>',
				icon: '../js/ext4/examples/shared/icons/cash-register-icon.png',
				margin: '2 2 2 2',
				handler : function() {
					window.open('../reports/prnt_collection_receipt.php?reference='+ Ext.getCmp('rpt_syspk').getValue());
					submit_window_InterB.close();
				}
			},{
                xtype: 'splitter'
			},{
				xtype: 'button',
				cls: 'rptbtn',
				width: 200,
				text:'<b>Official Receipt</b>',
				icon: '../js/ext4/examples/shared/icons/script.png',
				margin: '2 2 2 2',
				handler : function() {
					window.open('../reports/prnt_official_receipt.php?reference='+ Ext.getCmp('rpt_syspk').getValue());
					submit_window_InterB.close();
				}
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 2',
			items:[{
				xtype: 'button',
				cls: 'rptbtn',
				width: 200,
				text: '<b>Cash Sales Invoice</b>',
				icon: '../js/ext4/examples/shared/icons/script.png',
				margin: '2 2 2 2',
				handler: function () {
					window.open('../reports/prnt_cash_SI_serialized.php?SI_req=YES&SI_num=' + Ext.getCmp('rpt_receipt').getValue());
					submit_window_InterB.close();
				}
			}]
		}]
	});
	var report_window = Ext.create('Ext.Window',{
		width 	: 430,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[report_form]
	});

	function Getreference(){
		var reference;
		Ext.Ajax.request({
			url : '?getReference=CI',
			async:false,
			success: function (response){
				var result = Ext.JSON.decode(response.responseText);
				reference = result.reference;
			}
		});
		return reference;
	};
	function GetCashierPrep($tag){
		Ext.Ajax.request({
			url : '?get_cashierPrep=zHun',
			async:false,
			success: function (response){
				var result = Ext.JSON.decode(response.responseText);
				if($tag == "amort"){
					//Ext.getCmp('cashier').setValue(result.cashier);
					Ext.getCmp('preparedby').setValue(result.prepare);
				}else if($tag == "interb"){
					//Ext.getCmp('cashier_inb').setValue(result.cashier);
					Ext.getCmp('preparedby_inb').setValue(result.prepare);
				}else if($tag == "downp"){
					//Ext.getCmp('cashier_dp').setValue(result.cashier);
					Ext.getCmp('preparedby_dp').setValue(result.prepare);
				}else if($tag == "sicash"){
					//Ext.getCmp('cashier_cash').setValue(result.cashier);
					Ext.getCmp('preparedby_cash').setValue(result.prepare);
				}
			}
		});
	};
	function loadGLDP($tag=""){
		if($tag == "view"){
			DPitemStore.proxy.extraParams = {
				isview: "zHun",
				trans_no: Ext.getCmp('syspk_dp').getValue()
			};
		}else{
			DPitemStore.proxy.extraParams = {
				debtor_id: Ext.getCmp('customername_dp').getValue(),
				date_issue: Ext.getCmp('trans_date_dp').getValue(),
				debitTo: Ext.getCmp('debit_acct_dp').getValue(),
				//amount: Ext.getCmp('tenderd_amount_dp').getValue(),
				amounttotal: Ext.getCmp('total_amount_dp').getValue(),
				amounttenderd: Ext.getCmp('tenderd_amount_dp').getValue(),
				branch_code: Ext.getCmp('customercode_dp').getValue(),
				branch_name: Ext.getCmp('customername_dp').getRawValue(),
				paytype: Ext.getCmp('paymentType_dp').getValue()
			};
		}
		DPitemStore.load();
	}
	function loadOtherEntry($tag, $id=0, $mode, $bankaccount){
		var gridData = OtherEntryStore.getRange();
		var OEData = [];
		count = 0;

		$debitTo = Ext.getCmp('othrdebit_acct_cashpay').getValue();
		$customername = Ext.getCmp('customername_cash').getValue();
		
		Ext.each(gridData, function(item) {
			var ObjItem = {
				id: item.get('id'),  
				gl_code: item.get('gl_code'),
				gl_name: item.get('gl_name'),
				sl_code: item.get('sl_code'),
				sl_name: item.get('sl_name'),
				debtor_id: item.get('debtor_id'),
				debit_amount: item.get('debit_amount'),
				bankaccount: item.get('bankaccount'),
				otref_no: item.get('otref_no')
			};
			OEData.push(ObjItem);
		});
		if($tag == 'delete'){
			OtherEntryStore.proxy.extraParams = {
				DataOEGrid: Ext.encode(OEData),
				delete_id: $id,
			};
		}else{
			OtherEntryStore.proxy.extraParams = {
				DataOEGrid: Ext.encode(OEData),
				debtor_id: $customername,
				debitTo: $debitTo,
				bankaccount: $bankaccount
			};
		}
		OtherEntryStore.load();
	};
});
