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
			{name:'status', mapping:'status'}
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
	var smCheckAmortGrid = Ext.create('Ext.selection.CheckboxModel',{
		mode: 'SINGLE'
	});
	var smCheckCashGrid = Ext.create('Ext.selection.CheckboxModel',{
		mode: 'SINGLE'
	});
	var cellEditing = Ext.create('Ext.grid.plugin.CellEditing',{
        clicksToEdit: 2
    });
	var Branchstore = Ext.create('Ext.data.Store', {
		name: 'Branchstore',
		fields:['id','name','area','gl_account'],
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
	var lastpayStore = Ext.create('Ext.data.Store',{
		fields: ['id','name'],
		autoLoad: true,
		data : 	[
			{"id":"yes","name":"Yes"},
            {"id":"no","name":"No"}
        ]
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
			url: '?get_custPayment=zHun&module_type=CLTN',
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
	var scheduleStore = Ext.create('Ext.data.Store', {
		model: 'schedModel',
		//autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_schedule=zHun',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		//sorters : [{
			//property : 'mosterm',
			//direction : 'ASC'
		//}]
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
	var InterBStore = Ext.create('Ext.data.Store', {
		model: 'interBModel',
		name : 'InterBStore',
		method : 'POST',
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_interBPaymnt=xx',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		}
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

	var ColumnModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>trans_no</b>',dataIndex:'trans_no',hidden: true},
		{header:'<b>Date</b>', dataIndex:'tran_date', sortable:true, width:80, renderer: Ext.util.Format.dateRenderer('m-d-Y')},
		{header:'<b>Reference No.</b>', dataIndex:'reference', sortable:true, width:120},
		{header:'<b>CR No.</b>', dataIndex:'receipt_no', sortable:true, width:100},
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
		{header:'<b>Particulars</b>', dataIndex:'remarks', sortable:true, width:170,
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

					submit_form_InterB_view.getForm().reset();
					submit_form_view.getForm().reset();
					submit_form_DP.getForm().reset();
					submit_form_cashview.getForm().reset();

					if(records.get('module_type') == 'CLTN-INTERB'){

						RecptViewStore_inb.proxy.extraParams = {trans_no: records.get('trans_no')};
						RecptViewStore_inb.load();
						
						PaymentTypeStore.proxy.extraParams = {type: "interb"};
						PaymentTypeStore.load();
						CollectionTypeStore.proxy.extraParams = {type: "interb"};
						CollectionTypeStore.load();

						Ext.getCmp('v_syspk_inb').setValue(records.get('trans_no'));
						Ext.getCmp('v_transtypeFr_inb').setValue(records.get('trans_typeFr'));
						Ext.getCmp('v_transtypeTo_inb').setValue(records.get('trans_typeTo'));
						Ext.getCmp('v_paymentType_inb').setValue(records.get('payment_type'));
						Ext.getCmp('v_collectType_inb').setValue(records.get('collect_type'));
						Ext.getCmp('v_customercode_inb').setValue(records.get('debtor_ref'));
						Ext.getCmp('v_customername_inb').setValue(records.get('debtor_no'));
						Ext.getCmp('v_trans_date_inb').setValue(records.get('tran_date'));
						Ext.getCmp('v_branch_inb').setValue(records.get('invoice_no'));
						Ext.getCmp('v_receipt_no_inb').setValue(records.get('receipt_no'));
						Ext.getCmp('v_intobankacct_inb').setValue(records.get('Bank_account_id'));
						Ext.getCmp('v_total_amount_inb').setValue(records.get('total_amount'));
						Ext.getCmp('v_remarks_inb').setValue(records.get('remarks'));
						Ext.getCmp('v_check_date_inb').setValue(records.get('check_date'));
						Ext.getCmp('v_check_no_inb').setValue(records.get('check_no'));
						Ext.getCmp('v_Bank_branch_inb').setValue(records.get('Bank_branch'));
						Ext.getCmp('v_cashier_inb').setValue(records.get('cashier_name'));
						Ext.getCmp('v_preparedby_inb').setValue(records.get('prepared_by'));

						submit_window_InterB_view.setTitle('Inter-branch Collection Receipt Details - Reference No. : '+ records.get('reference'));
						submit_window_InterB_view.show();
						submit_window_InterB_view.setPosition(320,55);
						
					}else if(records.get('module_type') == 'CLTN-DPWOSI'){

						PaymentTypeStore.proxy.extraParams = {type: "downp"};
						PaymentTypeStore.load();
						CollectionTypeStore.proxy.extraParams = {type: "amort"};
						CollectionTypeStore.load();

						Ext.getCmp('syspk_dp').setValue(records.get('trans_no'));
						Ext.getCmp('moduletype_dp').setValue(records.get('module_type'));
						Ext.getCmp('pay_type_dp').setValue(records.get('pay_type'));
						Ext.getCmp('paymentType_dp').setValue(records.get('payment_type'));
						Ext.getCmp('collectType_dp').setValue(records.get('collect_type'));
						Ext.getCmp('customercode_dp').setValue(records.get('debtor_ref'));
						Ext.getCmp('customername_dp').setValue(records.get('debtor_no'));
						Ext.getCmp('name_dp').setValue(records.get('customer_name'));
						Ext.getCmp('trans_date_dp').setValue(records.get('tran_date'));
						Ext.getCmp('ref_no_dp').setValue(records.get('reference'));
						Ext.getCmp('receipt_no_dp').setValue(records.get('receipt_no'));
						Ext.getCmp('intobankacct_dp').setValue(records.get('Bank_account_id'));
						Ext.getCmp('total_amount_dp').setValue(records.get('total_amount'));
						Ext.getCmp('tenderd_amount_dp').setValue(records.get('total_amount'));
						Ext.getCmp('remarks_dp').setValue(records.get('remarks'));
						Ext.getCmp('check_date_dp').setValue(records.get('check_date'));
						Ext.getCmp('check_no_dp').setValue(records.get('check_no'));
						Ext.getCmp('bank_branch_dp').setValue(records.get('Bank_branch'));
						Ext.getCmp('preparedby_dp').setValue(records.get('prepared_by'));
						Ext.getCmp('cashier_dp').setValue(records.get('cashier_name'));

						Ext.getCmp('tenderd_amount_dp').setReadOnly(true);
						Ext.getCmp('customername_dp').setReadOnly(true);
						Ext.getCmp('intobankacct_dp').setReadOnly(true);
						//RecptViewStore_inb.proxy.extraParams = {trans_no: records.get('trans_no')};
						//RecptViewStore_inb.load();

						loadGLDP("view");
						Ext.getCmp('btnDPcancel').setText('Close');
						Ext.getCmp('btnDPsave').setVisible(false);

						submit_window_DP.setTitle('Down-payment Entry Details - Reference No. : '+ records.get('reference'));
						submit_window_DP.show();
						submit_window_DP.setPosition(320,55);
					
					}else if(records.get('module_type') == 'CLTN-CASH'){
						
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
					}else{
						
						ARInvoiceStore.proxy.extraParams = {debtor_id: records.get('debtor_no'), tag: "inst"};
						ARInvoiceStore.load();
						RecptViewStore.proxy.extraParams = {trans_no: records.get('trans_no'), typeTo: records.get('trans_typeTo')};
						RecptViewStore.load();
						
						PaymentTypeStore.proxy.extraParams = {type: "amort"};
						PaymentTypeStore.load();
						CollectionTypeStore.proxy.extraParams = {type: "amort"};
						CollectionTypeStore.load();

						Ext.getCmp('v_syspk').setValue(records.get('trans_no'));
						Ext.getCmp('v_transtypeFr').setValue(records.get('trans_typeFr'));
						Ext.getCmp('v_transtypeTo').setValue(records.get('trans_typeTo'));
						Ext.getCmp('v_paymentType').setValue(records.get('payment_type'));
						Ext.getCmp('v_collectType').setValue(records.get('collect_type'));
						Ext.getCmp('v_customercode').setValue(records.get('debtor_ref'));
						Ext.getCmp('v_customername').setValue(records.get('debtor_no'));
						Ext.getCmp('v_trans_date').setValue(records.get('tran_date'));
						Ext.getCmp('v_InvoiceNo').setValue(records.get('invoice_no'));
						Ext.getCmp('v_receipt_no').setValue(records.get('receipt_no'));
						Ext.getCmp('v_intobankacct').setValue(records.get('Bank_account_id'));
						Ext.getCmp('v_total_amount').setValue(records.get('total_amount'));
						Ext.getCmp('v_remarks').setValue(records.get('remarks'));
						Ext.getCmp('v_check_date').setValue(records.get('check_date'));
						Ext.getCmp('v_check_no').setValue(records.get('check_no'));
						Ext.getCmp('v_Bank_branch').setValue(records.get('Bank_branch'));
						Ext.getCmp('v_cashier').setValue(records.get('cashier_name'));
						Ext.getCmp('v_preparedby').setValue(records.get('prepared_by'));
						
						submit_window_view.setTitle('Customer Amortization Receipt Details - Reference No. :'+ records.get('reference'));
						submit_window_view.show();
						submit_window_view.setPosition(320,55);
					}
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

					report_window.setTitle('List Of Reports');
					report_window.show();
					report_window.setPosition(500,150);
				}
			}]
		}
	];
	var AllocationHeader = [
		{header:'<b>Id</b>',dataIndex:'loansched_id',hidden: true},
		{header:'<b>Trans No.</b>', dataIndex:'trans_no', width:70, hidden: true}, //align:'center',
		{header:'<b>No.</b>', dataIndex:'mosterm', width:50, locked: true},
		{header:'<b>Due Date</b>', dataIndex:'date_due', width:90, locked: true},
		{header:'<b>Monthly</b>', dataIndex:'amortization', align:'right', width:130,
			renderer : function(value, metaData, summaryData, dataIndex){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
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
			 editor: new Ext.form.TextField({
				xtype:'textfield',
				id: 'alloc_amount',
				name: 'alloc_amount',
				allowBlank: false,
				listeners : {
					change: function(editor, e){
						Ext.getCmp('tenderd_amount').setValue(e);
						var GridSModel = Ext.getCmp('AllocTabGrid').getSelectionModel();
						var GridRecords = GridSModel.getLastSelected();																																		 
						var newGPM = (e * GridRecords.get('grossPM'));
						GridRecords.set("comptdGPM",Math.round(newGPM));						  
					}
				},
			}), 
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
		},
		{header:'<b>GrossPM</b>', dataIndex:'grossPM', width:125, align:'right',
			renderer : function(value, metaData, summaryData, dataIndex){
				return Ext.util.Format.number(value, '0,000.00');
			}
		}
	];
	var AmortSchedHeader = [
		{header:'<b>Id</b>',dataIndex:'loansched_id',hidden: true},
		{header:'<b>Trans No.</b>', dataIndex:'trans_no', width:70, hidden: true}, //align:'center',
		{header:'<b>No.</b>', dataIndex:'mosterm', width:50},
		{header:'<b>Due Date</b>', dataIndex:'date_due', width:90},
		{header:'<b>Monthly</b>', dataIndex:'amortization', width:90,
			renderer : function(value, metaData, summaryData, dataIndex){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>Penalty</b>', dataIndex:'penalty', width:85,
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:red;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			}
		},
		{header:'<b>Rebate</b>', dataIndex:'rebate', width:80,
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:green;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			}
		},
		{header:'<b>Partial Paymnt</b>', dataIndex:'partialpayment', width:105, /*align:'right',*/
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:blue;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			}
		},
		{header:'<b>Penalty Bal.</b>', dataIndex:'penaltyBal', width:105, /*align:'right',*/
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color: #69250e;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			}
		},
		{header:'<b>Total Payment</b>', dataIndex:'totalpayment', width:125,
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:red;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}	
			}	
		},
		{header:'<b>Run Balance</b>', dataIndex:'runningbalance', width:115,
			renderer : function(value, metaData, summaryData, dataIndex){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>Maturity Date</b>', dataIndex:'maturity_date', width:120},
		{header:'<b>Paymnt Status</b>', dataIndex:'status', width:130}
	];
	var InterBGLHeader = [
		//{header:'<b>Id</b>',dataIndex:'loansched_id',hidden: true},
		{header:'<b>Date</b>', dataIndex:'trans_date', width:80},
		{header:'<b>GL Code</b>', dataIndex:'gl_code', width:80},
		{header:'<b>Description</b>', dataIndex:'gl_name', width:230},
		{header:'<b>SL Code</b>', dataIndex:'sl_code', width:80},
		{header:'<b>SL Name</b>', dataIndex:'sl_name', width:158},
		{header:'<b>Debit</b>', dataIndex:'debit_amount', width:100, align:'right', summaryType: 'sum',
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:green;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			},
			summaryRenderer: function(value, summaryData, dataIndex){
				return '<span style="color:blue;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';									
			}
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
				return '<span style="color:blue;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';									
			}
		}
	];
	var DPGLHeader = [
		//{header:'<b>Id</b>',dataIndex:'loansched_id',hidden: true},
		{header:'<b>Date</b>', dataIndex:'trans_date', width:80},
		{header:'<b>GL Code</b>', dataIndex:'gl_code', width:80},
		{header:'<b>Description</b>', dataIndex:'gl_name', width:230},
		{header:'<b>SL Code</b>', dataIndex:'sl_code', width:80},
		{header:'<b>SL Name</b>', dataIndex:'sl_name', width:158},
		{header:'<b>Debit</b>', dataIndex:'debit_amount', width:100, align:'right', summaryType: 'sum',
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:green;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			},
			summaryRenderer: function(value, summaryData, dataIndex){
				return '<span style="color:blue;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';									
			}
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
				return '<span style="color:blue;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';									
			}
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
	var columnInterB_view = [
		//{header:'<b>Id</b>',dataIndex:'loansched_id',hidden: true},
		{header:'<b>Date</b>', dataIndex:'trans_date', width:80},
		{header:'<b>GL Code</b>', dataIndex:'gl_code', width:95},
		{header:'<b>Description</b>', dataIndex:'gl_name', width:245,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>SL Code</b>', dataIndex:'sl_code', width:95},
		{header:'<b>SL Name</b>', dataIndex:'sl_name', width:215,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
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
				return '<span style="color:blue;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';									
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
				PaymentStore.proxy.extraParams = {query: field.getValue()};
				PaymentStore.load();
			}
		}
	}, '-', {
		text:'<b>Amortization Payment</b>',
		tooltip: 'Add new amortization payment.',
		icon: '../js/ext4/examples/shared/icons/cash-register-icon.png',
		scale: 'small',
		handler: function(){
			submit_form.getForm().reset();
			submit_form_InterB.getForm().reset();
			submit_form_DP.getForm().reset();
			submit_form_cash.getForm().reset();
			
			Ext.getCmp('check').setVisible(false);
			CustomerStore.load();
			PaymentTypeStore.proxy.extraParams = {type: "amort"};
			PaymentTypeStore.load();
			
			/*cashierStore.load({
				callback: function(records) {                 
        			Ext.getCmp('cashier').setValue(records[i].get('id'));
				}
			});*/

			//CollectionTypeStore.proxy.extraParams = {type: "amort"};
			//CollectionTypeStore.load();

			Ext.getCmp('intobankacct').setValue(3);
			Ext.getCmp('debit_acct').setValue("1050");
			Ext.getCmp('paymentType').setValue('amort');
			//Ext.getCmp('collectType').setValue(1);//'office'
			Ext.getCmp('moduletype').setValue('CLTN-AMORT');
			//Ext.getCmp('lastpayment').setValue('no');
			Ext.getCmp('AllocTabGrid').columns[7].setVisible(false);
			Ext.getCmp('AllocTabGrid').columns[8].setVisible(false);
			GetCashierPrep("amort");

			ARInvoiceStore.proxy.extraParams = {debtor_id: 0};
			ARInvoiceStore.load();
			scheduleStore.proxy.extraParams = {transNo: 0, debtor_no: 0, transtype: 0, transdate: null };
			scheduleStore.load();
			AllocationStore.proxy.extraParams = {transNo: 0, debtor_no: 0, transtype: 0, transdate: null };
			AllocationStore.load();

			var allocgrid = Ext.getCmp('AllocTabGrid');

			allocgrid.columns[6].setVisible(true);
			allocgrid.columns[7].setVisible(false);
			allocgrid.columns[8].setVisible(false);
			allocgrid.columns[9].setVisible(true);
			allocgrid.columns[10].setVisible(true);

			submit_window.show();
			submit_window.setTitle('Customer Amortization Receipt Entry - Add');
			submit_window.setPosition(320,23);
		}
	}, '-', {
		text:'<b>Cash/Adjustment Payment</b>',
		tooltip: 'Add new cash payment for sales invoice / Adjustment payment for replacement items',
		icon: '../js/ext4/examples/shared/icons/coins-in-hand-icon.png',
		scale: 'small',
		handler: function(){
			submit_form.getForm().reset();
			submit_form_InterB.getForm().reset();
			submit_form_DP.getForm().reset();
			submit_form_cash.getForm().reset();
			
			Ext.getCmp('check_cash').setVisible(false);
			
			CustomerStore.load();
			PaymentTypeStore.proxy.extraParams = {type: "cash"};
			PaymentTypeStore.load();
			ARInvoiceStore.proxy.extraParams = {debtor_id: 0};
			ARInvoiceStore.load();
			/*cashierStore.load({
				callback: function(records) {                 
        			Ext.getCmp('cashier_cash').setValue(records[i].get('id'));
				}
			});*/

			Ext.getCmp('intobankacct_cash').setValue(3);
			Ext.getCmp('debit_acct_cash').setValue("1050");
			Ext.getCmp('paymentType_cash').setValue('other');
			Ext.getCmp('collectType_cash').setValue(1);//'office'
			Ext.getCmp('moduletype_cash').setValue('CLTN-CASH');
			GetCashierPrep("sicash");

			submit_window_cash.show();
			submit_window_cash.setTitle('Cash Payment Receipt Entry - Add');
			submit_window_cash.setPosition(320,23);
		}
	}, '-', {
		text:'<b>Inter-branch Payment</b>',
		tooltip: 'Add new inter-branch payment.',
		icon: '../js/ext4/examples/shared/icons/table_relationship.png',
		scale: 'small',
		handler: function(){
			submit_form.getForm().reset();
			submit_form_InterB.getForm().reset();
			submit_form_DP.getForm().reset();
			submit_form_cash.getForm().reset();
			
			Ext.getCmp('check_inb').setVisible(false);
			CustomerStore.load();
			PaymentTypeStore.proxy.extraParams = {type: "interb"};
			PaymentTypeStore.load();
			/*cashierStore.load({
				callback: function(records) {                 
        			Ext.getCmp('cashier_inb').setValue(records[i].get('id'));
				}
			});*/
			//CollectionTypeStore.proxy.extraParams = {type: "interb"};
			//CollectionTypeStore.load();

			Ext.getCmp('intobankacct_inb').setValue(3);
			Ext.getCmp('debit_acct_inb').setValue("1050");
			Ext.getCmp('paymentType_inb').setValue('other');
			Ext.getCmp('collectType_inb').setValue(3);//'interb'
			Ext.getCmp('moduletype_inb').setValue('CLTN-INTERB');
			GetCashierPrep("interb");
			//loadInterBranch();

			submit_window_InterB.show();
			submit_window_InterB.setTitle('Customer Inter-Branch Receipt Entry - Add');
			submit_window_InterB.setPosition(320,23);
		}
	}, '-', {
		text:'<b>DP W/O Sales Invoice</b>',
		tooltip: 'Add new down payment without sales Invoice.',
		icon: '../js/ext4/examples/shared/icons/money_add.png',
		scale: 'small',
		handler: function(){
			submit_form.getForm().reset();
			submit_form_InterB.getForm().reset();
			submit_form_DP.getForm().reset();
			submit_form_cash.getForm().reset();

			Ext.getCmp('check_dp').setVisible(false);
			CustomerStore.load();
			PaymentTypeStore.proxy.extraParams = {type: "downp"};
			PaymentTypeStore.load();
			/*cashierStore.load({
				callback: function(records) {                 
        			Ext.getCmp('cashier_dp').setValue(records[i].get('id'));
				}
			});*/

			Ext.getCmp('intobankacct_dp').setValue(3);
			Ext.getCmp('debit_acct_dp').setValue("1050");
			Ext.getCmp('paymentType_dp').setValue('down');
			Ext.getCmp('collectType_dp').setValue(1);//'interb'
			Ext.getCmp('moduletype_dp').setValue('CLTN-DPWOSI');
			GetCashierPrep("downp");

			Ext.getCmp('btnDPcancel').setText('Cancel');
			Ext.getCmp('btnDPsave').setVisible(true);

			Ext.getCmp('tenderd_amount_dp').setReadOnly(false);
			Ext.getCmp('customername_dp').setReadOnly(false);
			Ext.getCmp('intobankacct_dp').setReadOnly(false);

			submit_window_DP.show();
			submit_window_DP.setTitle('Down-payment Entry - Add');
			submit_window_DP.setPosition(320,23);
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

	var submit_form = Ext.create('Ext.form.Panel', {
		id: 'form_submit',
		model: 'AllocationModel',
		frame: true,
		height: 488,
		defaultType: 'field',
		defaults: {msgTarget: 'under', labelWidth: 125, anchor: '-5'}, //msgTarget: 'side', labelAlign: 'top'
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
				id: 'totalrebate',
				name: 'totalrebate',
				fieldLabel: 'total rebate',
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
					width: 260,
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
					width: 320,
					forceSelection: true,
					selectOnFocus:true,
					fieldStyle: 'font-weight: bold; color: #210a04;',
					listeners: {
						select: function(combo, record, index) {
							Ext.getCmp('customercode').setValue(record.get('debtor_ref'));
							Ext.getCmp('tenderd_amount').setValue();
							Ext.getCmp('tenderd_amount').focus(false, 200);
							
							ARInvoiceStore.proxy.extraParams = {debtor_id: record.get('debtor_no'), tag: "inst"};
							ARInvoiceStore.load();

							Ext.Ajax.request({
								url : '?getReference=zHun',
								params: {
									debtor_id: record.get('debtor_no'),
									date: Ext.getCmp('trans_date').getValue()
								},
								async:false,
								success: function (response){
									var result = Ext.JSON.decode(response.responseText);
									Ext.getCmp('ref_no').setValue(result.reference);
									submit_window.setTitle('Customer Amortization Receipt Entry - Reference No. : '+ result.reference + ' *new');
								}
							});
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
						change: function(combo, record, index) {
							Ext.getCmp('InvoiceNo').setValue();
							Ext.getCmp('total_amount').setValue();
							Ext.getCmp('tenderd_amount').setValue();
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
					width: 580,
					forceSelection: true,
					selectOnFocus:true,
					fieldStyle: 'font-weight: bold; color: #210a04;',
					listeners: {
						select: function(combo, record, index) {
							var allocgrid = Ext.getCmp('AllocTabGrid');

							Ext.getCmp('transtype').setValue(record.get('type'));
							Ext.getCmp('tenderd_amount').setValue();
							Ext.getCmp('tenderd_amount').focus(false, 200);
							
							scheduleStore.proxy.extraParams = {transNo: record.get('id'), debtor_no: Ext.getCmp('customername').getValue(), transtype: record.get('type'), transdate: Ext.getCmp('trans_date').getValue(), colltype: Ext.getCmp('collectType').getValue()};
							scheduleStore.load({
								callback: function(records) {
									//alert("da");
									for (var i = 0; i < records.length; i++) {
										Ext.getCmp('totalrebate').setValue(records[i].get('totalrebate'));
									}
								}
							});
							SIitemStore.proxy.extraParams = {transNo: record.get('id'), transtype: record.get('type')};
							SIitemStore.load();
							//alert(record.get('type'));
							if(record.get('status') == "Pending" && record.get('type') != 56){

								Ext.getCmp('paymentType').setValue('down');
								allocgrid.columns[6].setVisible(false);
								allocgrid.columns[7].setVisible(true);
								allocgrid.columns[8].setVisible(true);
								allocgrid.columns[9].setVisible(false);
								allocgrid.columns[10].setVisible(false);

								AllocationStore.proxy.extraParams = {transNo: record.get('id'), debtor_no: Ext.getCmp('customername').getValue(), transtype: record.get('type'), transdate: Ext.getCmp('trans_date').getValue(), pay_type: Ext.getCmp('paymentType').getValue(), colltype: Ext.getCmp('collectType').getValue() };
								AllocationStore.load();

							}else{
								
								Ext.getCmp('paymentType').setValue('amort');
								allocgrid.columns[6].setVisible(true);
								allocgrid.columns[7].setVisible(false);
								allocgrid.columns[8].setVisible(false);
								allocgrid.columns[9].setVisible(true);
								allocgrid.columns[10].setVisible(true);

								AllocationStore.proxy.extraParams = {transNo: record.get('id'), debtor_no: Ext.getCmp('customername').getValue(), transtype: record.get('type'), transdate: Ext.getCmp('trans_date').getValue(), pay_type: Ext.getCmp('paymentType').getValue(), colltype: Ext.getCmp('collectType').getValue() };
								AllocationStore.load();
							}
						}
					}
				},{
					xtype: 'textfield',
					fieldLabel: 'CR Number',
					id: 'receipt_no',
					name: 'receipt_no',
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
					id: 'cashier',
					name: 'cashier',
					store: cashierStore,
					displayField: 'name',
					valueField: 'id',
					queryMode: 'local',
					labelWidth: 105,
					width: 290,
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
					width: 290,
					fieldStyle: 'font-weight: bold; color: #210a04;'
				},{
					xtype: 'combobox',
					id: 'paymentType',
					name: 'paymentType',
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
					fieldStyle: 'font-weight: bold; color: #210a04;',
					listeners: {
						select: function(combo, record, index) {
							Ext.getCmp('tenderd_amount').setValue();
							Ext.getCmp('tenderd_amount').focus(false, 200);

							AllocationStore.proxy.extraParams = {transNo: Ext.getCmp('InvoiceNo').getValue(), debtor_no: Ext.getCmp('customername').getValue(), transtype: Ext.getCmp('transtype').getValue(), transdate: Ext.getCmp('trans_date').getValue(), pay_type: record.get('id'), colltype: Ext.getCmp('collectType').getValue() };
							AllocationStore.load();

							var allocgrid = Ext.getCmp('AllocTabGrid');
							if(record.get('id') == "down"){
								allocgrid.columns[6].setVisible(false);
								allocgrid.columns[7].setVisible(true);
								allocgrid.columns[8].setVisible(true);
								allocgrid.columns[9].setVisible(false);
								allocgrid.columns[10].setVisible(false);
							}else{
								allocgrid.columns[6].setVisible(true);
								allocgrid.columns[7].setVisible(false);
								allocgrid.columns[8].setVisible(false);
								allocgrid.columns[9].setVisible(true);
								allocgrid.columns[10].setVisible(true);
							}
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
					id:	'remarks',
					name: 'remarks',
					//labelAlign:	'top',
					allowBlank: false,
					maxLength: 254,
					labelWidth: 105,
					width: 580,
					hidden: false
				},{
					xtype: 'fieldcontainer',
					layout: 'vbox',
					margin: '0 0 0 0',
					items:[{
						xtype: 'combobox',
						id: 'collectType',
						name: 'collectType',
						fieldLabel: 'Collection type',
						store: CollectionTypeStore,
						displayField: 'name',
						valueField: 'id',
						queryMode: 'local',
						width: 255,
						margin: '0 0 2 0',
						allowBlank: false,
						forceSelection: true,
						selectOnFocus:true,
						editable: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						listeners: {
							select: function(combo, record, index) {
								if(record.get('id') == 5){
									Ext.getCmp('InvoiceNo').setValue();
									Ext.getCmp('total_amount').setValue();
									Ext.getCmp('tenderd_amount').setValue();
									ARInvoiceStore.proxy.extraParams = {debtor_id: Ext.getCmp('customername').getValue()};
									ARInvoiceStore.load();
									AllocationStore.proxy.extraParams = {transNo: 0};
									AllocationStore.load();
								}else{
									scheduleStore.proxy.extraParams = {transNo: Ext.getCmp('InvoiceNo').getValue(), debtor_no: Ext.getCmp('customername').getValue(), transtype: Ext.getCmp('transtype').getValue(), transdate: Ext.getCmp('trans_date').getValue(), colltype: record.id };
									scheduleStore.load({
										callback: function(records) {
											//alert("sa");
											for (var i = 0; i < records.length; i++) {
												Ext.getCmp('totalrebate').setValue(records[i].get('totalrebate'));
											}
										}
									});
									AllocationStore.proxy.extraParams = {transNo: Ext.getCmp('InvoiceNo').getValue(), debtor_no: Ext.getCmp('customername').getValue(), transtype: Ext.getCmp('transtype').getValue(), transdate: Ext.getCmp('trans_date').getValue(), pay_type: Ext.getCmp('paymentType').getValue(), colltype: record.id };
									AllocationStore.load();
								}

								Ext.getCmp('tenderd_amount').setValue();
								Ext.getCmp('tenderd_amount').focus(false, 200);
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
				}]
			},{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					xtype: 'combobox',
					id: 'intobankacct',
					name: 'intobankacct',
					allowBlank: false,
					store : IntoBankAcctStore,
					displayField: 'name',
					valueField: 'id',
					queryMode: 'local',
					fieldLabel : 'Into Bank Account ',
					labelWidth: 123,
					width: 560,
					forceSelection: true,
					selectOnFocus:true,
					fieldStyle: 'font-weight: bold; color: #210a04;',
					listeners: {
						select: function(combo, record, index) {
							Ext.getCmp('debit_acct').setValue(record.get("type"));
							Ext.getCmp('tenderd_amount').setValue();
							Ext.getCmp('tenderd_amount').focus(false, 200);
						},
						change: function(object, value) {
							//console.log(value);
							if(value == 1 || value == 2 || value == 3){ //object.getRawValue()
								Ext.getCmp('check').setVisible(false);
								Ext.getCmp('pay_type').setValue('Cash');
							}else{
								Ext.getCmp('check').setVisible(true);
								Ext.getCmp('pay_type').setValue('Check');
							}
						}
					}
				/*},{
					xtype: 'combobox',
					id: 'lastpayment',
					name: 'lastpayment',
					fieldLabel: 'Last Payment <b>?</b> ',
					store: lastpayStore,
					displayField: 'name',
					valueField: 'id',
					queryMode: 'local',
					width: 190,
					labelWidth: 105,
					margin: '0 0 2 0',
					allowBlank: false,
					forceSelection: true,
					selectOnFocus:true,
					editable: false,
					fieldStyle: 'font-weight: bold; color: #210a04;'*/
				},{
					xtype: 'numericfield',
					id: 'tenderd_amount',
					name: 'tenderd_amount',
					fieldLabel: 'Tendered Amount ',
					allowBlank:false,
					useThousandSeparator: true,
					labelWidth: 123,
					width: 275,
					thousandSeparator: ',',
					minValue: 0,
					fieldStyle: 'font-weight: bold;color: #008000; text-align: right; background-color: #F2F3F4;',
					listeners: {
						afterrender: function(field) {
							field.focus(true);
						},
						change: function(object, value) {

							if(Ext.getCmp('InvoiceNo').getValue() != null){
								Ext.getCmp('alloc_amount').setValue(value);
								var ItemModel = Ext.getCmp('AllocTabGrid').getSelectionModel();
								var GridRecords = ItemModel.getLastSelected(); //getLastSelected();

								GridRecords.set("alloc_amount",value);
							}
						}
					}
				}]
			},{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				id: 'check',
				items:[{
					xtype : 'datefield',
					id	  : 'check_date',
					name  : 'check_date',
					fieldLabel : 'Check Date ',
					allowBlank: true,
					labelWidth: 105,
					width: 230,
					format : 'm/d/Y'
				},{
					xtype: 'textfield',
					fieldLabel: 'Check No. ',
					id: 'check_no',
					name: 'check_no',
					allowBlank: true,
					labelWidth: 78,
					width: 230,
					fieldStyle: 'font-weight: bold; color: #210a04;'
				},{
					xtype: 'combobox',
					id: 'bank_branch',
					name: 'bank_branch',
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
				id: 'alloctabpanel',
				activeTab: 0,
				width: 860,
				height: 270,
				scale: 'small',
				items:[{
					xtype:'gridpanel',
					id: 'AllocTabGrid',
					anchor:'100%',
					layout:'fit',
					title: 'Allocate Receipts',
					icon: '../js/ext4/examples/shared/icons/page_attach.png',
					loadMask: true,
					//height: 130,
					store:	AllocationStore,
					columns: AllocationHeader,
					selModel: smCheckAmortGrid,
					columnLines: true,
					features: [{ftype: 'summary'}],
					plugins: [cellEditing],
					/*bbar : {
						xtype : 'pagingtoolbar',
						store:	AllocationStore,
						items:[{
							xtype: 'checkbox',
							id: 'checklastpay',
							name: 'checklastpay',
							boxLabel: 'Please check if last payment'
						}]
					},*/
					viewConfig : {
						listeners : {
							cellclick : function(view, cell, cellIndex, record, row, rowIndex, e) {
								//alert( record.get("totalpayment") + ' ' + (rowIndex+1));
								Ext.getCmp("total_amount").setValue(record.get("totalpayment"));
								Ext.getCmp('tenderd_amount').setValue();
								Ext.getCmp('tenderd_amount').focus(false, 200);
							}
						}
					}
				},{
					xtype:'gridpanel',
					id: 'AmortSchedGrid',
					anchor:'100%',
					layout:'fit',
					title: 'Customer Amort Schedule',
					icon: '../js/ext4/examples/shared/icons/vcard.png',
					loadMask: true,
					//height: 250,
					store:	scheduleStore,
					columns: AmortSchedHeader,
					features: [{ftype: 'summary'}],
					columnLines: true,
					viewConfig : {
						listeners : {
							cellclick : function(view, cell, cellIndex, record, row, rowIndex, e) {
								//alert( record.get("penalty") + ' ' + (rowIndex+1));
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
					//height: 250,
					store:	SIitemStore,
					columns: Item_view,
					columnLines: true
				}]
			}]
	});
	var submit_window = Ext.create('Ext.Window',{
		width 	: 856,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form],
		buttons:[{
			text: '<b>Save</b>',
			tooltip: 'Save customer payment',
			icon: '../js/ext4/examples/shared/icons/add.png',
			single : true,				
			handler:function(){
				var HaveErrors = 0;
				var $message;
				var form_submit = Ext.getCmp('form_submit').getForm();
				if(Ext.getCmp('alloc_amount').getValue() != Ext.getCmp('tenderd_amount').getValue()){
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
							//params[field.name+'['+ i +']'] = records[i].get(field.name);
							count++;
							if(count == TotalFld){
								gridData.push(params);
								params = {};
								count = 0;
							}
						});
					}
					//console.log(Ext.decode(gridData));
					form_submit.submit({
						url: '?submit=payment',
						params: {
							DataOnGrid: Ext.encode(gridData)
						},
						waitMsg: 'Saving payment for Invoice No.' + Ext.getCmp('InvoiceNo').getRawValue() + '. please wait...',
						method:'POST',
						submitEmptyText: false,
						success: function(form_submit, action) {
							PaymentStore.load()
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
			tooltip: 'Cancel customer payment',
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
	var submit_form_InterB = Ext.create('Ext.form.Panel', {
		id: 'submit_form_InterB',
		model: 'AllocationModel',
		frame: true,
		defaultType: 'field',
		defaults: {msgTarget: 'under', labelWidth: 125, anchor: '-5'}, //msgTarget: 'side', labelAlign: 'top'
			items: [{
				xtype: 'textfield',
				id: 'syspk_inb',
				name: 'syspk_inb',
				fieldLabel: 'syspk',
				//allowBlank: false,
				hidden: true
			},{
				xtype: 'textfield',
				id: 'moduletype_inb',
				name: 'moduletype_inb',
				fieldLabel: 'syspk',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'textfield',
				id: 'pay_type_inb',
				name: 'pay_type_inb',
				fieldLabel: 'Pay type',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'textfield',
				id: 'debit_acct_inb',
				name: 'debit_acct_inb',
				fieldLabel: 'debit_acct',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'textfield',
				id: 'name_inb',
				name: 'name_inb',
				fieldLabel: 'customer name',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'textfield',
				id: 'ref_no_inb',
				name: 'ref_no_inb',
				fieldLabel: 'ref No',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					xtype: 'textfield',
					fieldLabel: 'Customer ',
					id: 'customercode_inb',
					name: 'customercode_inb',
					allowBlank: false,
					labelWidth: 105,
					width: 250,
					readOnly: true,
					fieldStyle: 'font-weight: bold; color: #210a04;'
				},{
					xtype: 'combobox',
					id: 'customername_inb',
					name: 'customername_inb',
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
							Ext.getCmp('customercode_inb').setValue(record.get('debtor_ref'));
							Ext.getCmp('tenderd_amount_inb').setValue();
							Ext.getCmp('tenderd_amount_inb').focus(false, 200);
							Ext.getCmp('name_inb').setValue(Ext.getCmp('customername_inb').getRawValue());

							Ext.Ajax.request({
								url : '?getReference=zHun',
								params: {
									debtor_id: record.get('debtor_no'),
									date: Ext.getCmp('trans_date_inb').getValue()
								},
								async:false,
								success: function (response){
									var result = Ext.JSON.decode(response.responseText);
									Ext.getCmp('ref_no_inb').setValue(result.reference);
									submit_window_InterB.setTitle('Customer Inter-Branch Receipt Entry - Reference No. : '+ result.reference + ' *new');
								}
							});

							loadInterBranch();
						}
					}
				},{
					xtype: 'button',
					tooltip: 'Click to open inter-branch customers maintenance page',
					margin: '0 12 0 0',
					icon: '../js/ext4/examples/shared/icons/door_in.png',
					handler : function() {
						window.open('../lending/manage/auto_add_interb_customers.php?');
						submit_window_InterB.close();
					}
				},{
					xtype : 'datefield',
					id	  : 'trans_date_inb',
					name  : 'trans_date_inb',
					fieldLabel : 'Date ',
					allowBlank: false,
					labelWidth: 48,
					width: 203,
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
					id: 'branch_inb',
					name: 'branch_inb',
					fieldLabel: 'Branch ',
					allowBlank: false,
					store : Branchstore,
					displayField: 'name',
					valueField: 'id',
					queryMode: 'local',
					labelWidth: 105,
					width: 560,
					forceSelection: true,
					selectOnFocus:true,
					fieldStyle: 'font-weight: bold; color: #210a04;',
					listeners: {
						select: function(combo, record, index) {
							sbranch_code = record.get('id');
							sbranch_name = record.get('name');
							sbranch_gl = record.get('gl_account');

							loadInterBranch();
						}
					}
				},{
					xtype: 'textfield',
					fieldLabel: 'CR Number',
					id: 'receipt_no_inb',
					name: 'receipt_no_inb',
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
					id: 'cashier_inb',
					name: 'cashier_inb',
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
					id: 'preparedby_inb',
					name: 'preparedby_inb',
					allowBlank: false,
					readOnly: true,
					labelWidth: 105,
					width: 280,
					fieldStyle: 'font-weight: bold; color: #210a04;'
				},{
					xtype: 'combobox',
					id: 'paymentType_inb',
					name: 'paymentType_inb',
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
					xtype: 	'textareafield',
					fieldLabel: 'Remarks ',
					id:	'remarks_inb',
					name: 'remarks_inb',
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
						id: 'collectType_inb',
						name: 'collectType_inb',
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
					},{
						xtype: 'numericfield',
						id: 'total_amount_inb',
						name: 'total_amount_inb',
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
				margin: '2 0 2 5',
				items:[{
					xtype: 'combobox',
					id: 'intobankacct_inb',
					name: 'intobankacct_inb',
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
							Ext.getCmp('debit_acct_inb').setValue(record.get("type"));
							Ext.getCmp('tenderd_amount_inb').setValue();
							Ext.getCmp('tenderd_amount_inb').focus(false, 200);

							loadInterBranch();
						},
						change: function(object, value) {
							//console.log(value);
							if(value == 1 || value == 2 || value == 3){ //object.getRawValue()
								Ext.getCmp('check_inb').setVisible(false);
								Ext.getCmp('pay_type_inb').setValue('Cash');
							}else{
								Ext.getCmp('check_inb').setVisible(true);
								Ext.getCmp('pay_type_inb').setValue('Check');
							}
						}
					}
				},{
					xtype: 'numericfield',
					id: 'tenderd_amount_inb',
					name: 'tenderd_amount_inb',
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
							Ext.getCmp('total_amount_inb').setValue(value);

							loadInterBranch();
						}
					}
				}]
			},{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				id: 'check_inb',
				items:[{
					xtype : 'datefield',
					id	  : 'check_date_inb',
					name  : 'check_date_inb',
					fieldLabel : 'Check Date ',
					allowBlank: true,
					labelWidth: 105,
					width: 230,
					format : 'm/d/Y'
				},{
					xtype: 'textfield',
					fieldLabel: 'Check No. ',
					id: 'check_no_inb',
					name: 'check_no_inb',
					allowBlank: true,
					labelWidth: 78,
					width: 230,
					fieldStyle: 'font-weight: bold; color: #210a04;'
				},{
					xtype: 'combobox',
					id: 'bank_branch_inb',
					name: 'bank_branch_inb',
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
				id: 'InterBPanel',
				activeTab: 0,
				width: 860,
				height: 200,
				scale: 'small',
				items:[{
					xtype:'gridpanel',
					id: 'InterBGrid',
					anchor:'100%',
					layout:'fit',
					title: 'Inter-Branch Collection Entry',
					icon: '../js/ext4/examples/shared/icons/vcard.png',
					loadMask: true,
					store:	InterBStore,
					columns: InterBGLHeader,
					features: [{ftype: 'summary'}],
					columnLines: true
				}]
			}]
	});
	var submit_window_InterB = Ext.create('Ext.Window',{
		width 	: 842,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form_InterB],
		buttons:[{
			text: '<b>Save</b>',
			tooltip: 'Save customer payment',
			icon: '../js/ext4/examples/shared/icons/add.png',
			single : true,				
			handler:function(){
				var form_submit_InterB = Ext.getCmp('submit_form_InterB').getForm();
				if(form_submit_InterB.isValid()) {
					var gridData = InterBStore.getRange();
					var girdInterBData = [];
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
						girdInterBData.push(ObjInterB);
					});
					//console.log(Ext.decode(gridData));
					form_submit_InterB.submit({
						url: '?submitInterB=payment',
						params: {
							InterBDataOnGrid: Ext.encode(girdInterBData)
						},
						waitMsg: 'Saving payment. please wait...',
						method:'POST',
						submitEmptyText: false,
						success: function(form_submit_InterB, action) {
							PaymentStore.load()
							Ext.Msg.alert('Success!', '<font color="green">' + action.result.message + '</font>');
							submit_window_InterB.close();
						},
						failure: function(form_submit_InterB, action) {
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
						submit_form_InterB.getForm().reset();
						submit_window_InterB.close();
					}
				});
			}
		}]
	});
	var submit_form_DP = Ext.create('Ext.form.Panel', {
		id: 'submit_form_DP',
		model: 'AllocationModel',
		frame: true,
		defaultType: 'field',
		defaults: {msgTarget: 'under', labelWidth: 125, anchor: '-5'}, //msgTarget: 'side', labelAlign: 'top'
			items: [{
				xtype: 'textfield',
				id: 'syspk_dp',
				name: 'syspk_inb',
				fieldLabel: 'syspk',
				//allowBlank: false,
				hidden: true
			},{
				xtype: 'textfield',
				id: 'moduletype_dp',
				name: 'moduletype_dp',
				fieldLabel: 'syspk',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'textfield',
				id: 'pay_type_dp',
				name: 'pay_type_dp',
				fieldLabel: 'Pay type',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'textfield',
				id: 'debit_acct_dp',
				name: 'debit_acct_dp',
				fieldLabel: 'debit_acct',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'textfield',
				id: 'name_dp',
				name: 'name_dp',
				fieldLabel: 'customer name',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'textfield',
				id: 'ref_no_dp',
				name: 'ref_no_dp',
				fieldLabel: 'ref No',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					xtype: 'textfield',
					fieldLabel: 'Customer code ',
					id: 'customercode_dp',
					name: 'customercode_dp',
					allowBlank: false,
					labelWidth: 105,
					width: 280,
					margin: '0 282 0 0',
					readOnly: true,
					fieldStyle: 'font-weight: bold; color: #210a04;'
				},{
					xtype : 'datefield',
					id	  : 'trans_date_dp',
					name  : 'trans_date_dp',
					fieldLabel : 'Date ',
					allowBlank: false,
					labelWidth: 98,
					width: 252,
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
					id: 'customername_dp',
					name: 'customername_dp',
					fieldLabel: 'Customer name',
					allowBlank: false,
					store : CustomerStore,
					displayField: 'name',
					valueField: 'debtor_no',
					queryMode: 'local',
					width: 560,
					labelWidth: 106,
					//margin: '0 5 0 0',
					forceSelection: true,
					selectOnFocus:true,
					fieldStyle: 'font-weight: bold; color: #210a04;',
					listeners: {
						select: function(combo, record, index) {
							Ext.getCmp('customercode_dp').setValue(record.get('debtor_ref'));
							Ext.getCmp('tenderd_amount_dp').focus(false, 200);
							Ext.getCmp('name_dp').setValue(Ext.getCmp('customername_dp').getRawValue());

							Ext.Ajax.request({
								url : '?getReference=zHun',
								params: {
									debtor_id: record.get('debtor_no'),
									date: Ext.getCmp('trans_date_dp').getValue()
								},
								async:false,
								success: function (response){
									var result = Ext.JSON.decode(response.responseText);
									Ext.getCmp('ref_no_dp').setValue(result.reference);
									submit_window_DP.setTitle('Down-payment Receipt Entry - Reference No. : '+ result.reference + ' *new');
								}
							});

							loadGLDP();
						}
					}
				},{
					xtype: 'textfield',
					fieldLabel: 'CR Number',
					id: 'receipt_no_dp',
					name: 'receipt_no_dp',
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
					id: 'cashier_dp',
					name: 'cashier_dp',
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
					id: 'preparedby_dp',
					name: 'preparedby_dp',
					allowBlank: false,
					readOnly: true,
					labelWidth: 105,
					width: 280,
					fieldStyle: 'font-weight: bold; color: #210a04;'
				},{
					xtype: 'combobox',
					id: 'paymentType_dp',
					name: 'paymentType_dp',
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
					xtype: 	'textareafield',
					fieldLabel: 'Remarks ',
					id:	'remarks_dp',
					name: 'remarks_dp',
					//labelAlign:	'top',
					allowBlank: false ,
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
						id: 'collectType_dp',
						name: 'collectType_dp',
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
					},{
						xtype: 'numericfield',
						id: 'total_amount_dp',
						name: 'total_amount_dp',
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
				margin: '2 0 2 5',
				items:[{
					xtype: 'combobox',
					id: 'intobankacct_dp',
					name: 'intobankacct_dp',
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
							Ext.getCmp('debit_acct_dp').setValue(record.get("type"));
							Ext.getCmp('tenderd_amount_dp').focus(false, 200);

							loadGLDP();
						},
						change: function(object, value) {
							//console.log(value);
							if(value == 1 || value == 2 || value == 3){ //object.getRawValue()
								Ext.getCmp('check_dp').setVisible(false);
								Ext.getCmp('pay_type_dp').setValue('Cash');
							}else{
								Ext.getCmp('check_dp').setVisible(true);
								Ext.getCmp('pay_type_dp').setValue('Check');
							}
						}
					}
				},{
					xtype: 'numericfield',
					id: 'tenderd_amount_dp',
					name: 'tenderd_amount_dp',
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
							Ext.getCmp('total_amount_dp').setValue(value);

							loadGLDP();
						}
					}
				}]
			},{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				id: 'check_dp',
				items:[{
					xtype : 'datefield',
					id	  : 'check_date_dp',
					name  : 'check_date_dp',
					fieldLabel : 'Check Date ',
					allowBlank: true,
					labelWidth: 105,
					width: 230,
					format : 'm/d/Y'
				},{
					xtype: 'textfield',
					fieldLabel: 'Check No. ',
					id: 'check_no_dp',
					name: 'check_no_dp',
					allowBlank: true,
					labelWidth: 78,
					width: 230,
					fieldStyle: 'font-weight: bold; color: #210a04;'
				},{
					xtype: 'combobox',
					id: 'bank_branch_dp',
					name: 'bank_branch_dp',
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
				id: 'DPPanel',
				activeTab: 0,
				width: 860,
				height: 200,
				scale: 'small',
				items:[{
					xtype:'gridpanel',
					id: 'DPGrid',
					anchor:'100%',
					layout:'fit',
					title: 'Down-payment Entry',
					icon: '../js/ext4/examples/shared/icons/vcard.png',
					loadMask: true,
					store:	DPitemStore,
					columns: DPGLHeader,
					features: [{ftype: 'summary'}],
					columnLines: true
				}]
			}]
	});
	var submit_window_DP = Ext.create('Ext.Window',{
		width 	: 842,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form_DP],
		buttons:[{
			text: '<b>Save</b>',
			id: 'btnDPsave',
			tooltip: 'Save customer payment',
			icon: '../js/ext4/examples/shared/icons/add.png',
			single : true,				
			handler:function(){
				var form_submit_DP = Ext.getCmp('submit_form_DP').getForm();
				if(form_submit_DP.isValid()) {
					var gridData = DPitemStore.getRange();
					var girdDPData = [];
					count = 0;
					Ext.each(gridData, function(item) {
						var ObjDP = {
							gl_code: item.get('gl_code'),  
							gl_name: item.get('gl_name'),
							sl_code: item.get('sl_code'),
							sl_name: item.get('sl_name'),
							debtor_id: item.get('debtor_id'),
							debit_amount: item.get('debit_amount'),
							credit_amount: item.get('credit_amount'),
						};
						girdDPData.push(ObjDP);
					});
					//console.log(Ext.decode(gridData));
					form_submit_DP.submit({
						url: '?submitDPnoAmort=payment',
						params: {
							DPDataOnGrid: Ext.encode(girdDPData)
						},
						waitMsg: 'Saving downpayment. please wait...',
						method:'POST',
						submitEmptyText: false,
						success: function(form_submit_DP, action) {
							PaymentStore.load()
							Ext.Msg.alert('Success!', '<font color="green">' + action.result.message + '</font>');
							submit_window_DP.close();
						},
						failure: function(form_submit_DP, action) {
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
			id: 'btnDPcancel',
			tooltip: 'Cancel customer payment',
			icon: '../js/ext4/examples/shared/icons/cancel.png',
			handler:function(btnc){
				if(btnc.text == "Cancel"){
					Ext.MessageBox.confirm('Confirm:', 'Are you sure you wish to close this window?', function (btn, text) {
						if (btn == 'yes') {
							//Ext.Msg.alert('Close','close.');
							submit_form_DP.getForm().reset();
							submit_window_DP.close();
						}
					});
				}else{
					submit_window_DP.close();
				}
			}
		}]
	});
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
								url : '?getReference=zHun',
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
							if(value == 1 || value == 2 || value == 3){ //object.getRawValue()
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
							Ext.getCmp('alloc_amount_cash').setValue(value);
							
							var ItemModel = Ext.getCmp('allocgrid_cash').getSelectionModel();
							var GridRecords = ItemModel.getLastSelected(); //getLastSelected();

							GridRecords.set("alloc_amount_cash",value);
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
								Ext.getCmp('tenderd_amount_cash').setValue();
								Ext.getCmp('tenderd_amount_cash').focus(false, 200);
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
					//console.log(Ext.decode(gridData));
					submit_form_cash.submit({
						url: '?submitSICash=payment',
						params: {
							DataOnGrid: Ext.encode(gridData)
						},
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
		width: 1210,
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
					
				}
			}
		}]
	});

	var submit_form_view = Ext.create('Ext.form.Panel', {
		id: 'submit_form_view',
		model: 'AllocationModel',
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
				xtype: 'combobox',
				id: 'v_customername',
				name: 'v_customername',
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
				xtype: 'textfield',
				fieldLabel: '<b>CR No.</b>',
				id: 'v_receipt_no',
				name: 'v_receipt_no',
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
				id: 'v_intobankacct',
				name: 'v_intobankacct',
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
						if(value == 1 || value == 2 || value == 3){ //object.getRawValue()
							Ext.getCmp('v_check').setVisible(false);
						}else{
							Ext.getCmp('v_check').setVisible(true);
						}
					}
				}
			},{
				xtype: 'combobox',
				id: 'v_paymentType',
				name: 'v_paymentType',
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
			},{
				xtype: 'combobox',
				id: 'v_collectType',
				name: 'v_collectType',
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
				id:	'v_remarks',
				name: 'v_remarks',
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
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 5 5',
			id: 'v_check',
			items:[{
				xtype : 'datefield',
				id	  : 'v_check_date',
				name  : 'v_check_date',
				fieldLabel : '<b>Check Date </b>',
				allowBlank: true,
				labelWidth: 105,
				width: 230,
				format : 'm/d/Y'
			},{
				xtype: 'textfield',
				fieldLabel: '<b>Check No. </b>',
				id: 'v_check_no',
				name: 'v_check_no',
				allowBlank: true,
				labelWidth: 78,
				width: 230,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'combobox',
				id: 'v_Bank_branch',
				name: 'v_Bank_branch',
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
			tooltip: 'Close window',
			icon: '../js/ext4/examples/shared/icons/cancel.png',
			handler:function(){
				submit_form_view.getForm().reset();
				submit_window_view.close();
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
				fieldLabel: '<b>CR No.</b>',
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
						if(value == 1 || value == 2 || value == 3){ //object.getRawValue()
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
			}]
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
				submit_form_view.getForm().reset();
				submit_window_view.close();
			}
		}]
	});
	var submit_form_InterB_view = Ext.create('Ext.form.Panel', {
		id: 'submit_form_InterB_view',
		model: 'AllocationModel',
		frame: true,
		defaultType: 'field',
		items: [{
			xtype: 'textfield',
			id: 'v_syspk_inb',
			name: 'v_syspk_inb',
			fieldLabel: 'syspk',
			//allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'v_transtypeFr_inb',
			name: 'v_transtypeFr_inb',
			fieldLabel: 'transtypeF',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'v_transtypeTo_inb',
			name: 'v_transtypeTo_inb',
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
				id: 'v_customercode_inb',
				name: 'v_customercode_inb',
				allowBlank: false,
				labelWidth: 105,
				width: 250,
				readOnly: true,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'combobox',
				id: 'v_customername_inb',
				name: 'v_customername_inb',
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
				id	  : 'v_trans_date_inb',
				name  : 'v_trans_date_inb',
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
				id: 'v_branch_inb',
				name: 'v_branch_inb',
				fieldLabel: 'Branch ',
				allowBlank: false,
				store : Branchstore,
				displayField: 'name',
				valueField: 'id',
				queryMode: 'local',
				labelWidth: 105,
				width: 560,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'textfield',
				fieldLabel: '<b>Reference No.</b>',
				id: 'v_receipt_no_inb',
				name: 'v_receipt_no_inb',
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
				id: 'v_intobankacct_inb',
				name: 'v_intobankacct_inb',
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
						if(value == 1 || value == 2 || value == 3){ //object.getRawValue()
							Ext.getCmp('v_check_inb').setVisible(false);
						}else{
							Ext.getCmp('v_check_inb').setVisible(true);
						}
					}
				}
			},{
				xtype: 'combobox',
				id: 'v_paymentType_inb',
				name: 'v_paymentType_inb',
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
				id: 'v_cashier_inb',
				name: 'v_cashier_inb',
				allowBlank: false,
				readOnly: true,
				labelWidth: 105,
				width: 280,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'textfield',
				fieldLabel: 'Prepared By ',
				id: 'v_preparedby_inb',
				name: 'v_preparedby_inb',
				allowBlank: false,
				readOnly: true,
				labelWidth: 105,
				width: 280,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'combobox',
				id: 'v_collectType_inb',
				name: 'v_collectType_inb',
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
				id:	'v_remarks_inb',
				name: 'v_remarks_inb',
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
					id: 'v_total_amount_inb',
					name: 'v_total_amount_inb',
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
			id: 'v_check_inb',
			items:[{
				xtype : 'datefield',
				id	  : 'v_check_date_inb',
				name  : 'v_check_date_inb',
				fieldLabel : '<b>Check Date </b>',
				allowBlank: true,
				labelWidth: 105,
				width: 230,
				format : 'm/d/Y'
			},{
				xtype: 'textfield',
				fieldLabel: '<b>Check No. </b>',
				id: 'v_check_no_inb',
				name: 'v_check_no_inb',
				allowBlank: true,
				labelWidth: 78,
				width: 230,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'combobox',
				id: 'v_Bank_branch_inb',
				name: 'v_Bank_branch_inb',
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
				title: 'Inter-branch Collection Receipts',
				icon: '../js/ext4/examples/shared/icons/page_attach.png',
				loadMask: true,
				height: 130,
				store:	RecptViewStore_inb,
				columns: columnInterB_view,
				columnLines: true,
				features: [{ftype: 'summary'}]
			}]
		}]
	});
	var submit_window_InterB_view = Ext.create('Ext.Window',{
		width 	: 842,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form_InterB_view],
		buttons:[{
			text:'<b>Close</b>',
			tooltip: 'Close window',
			icon: '../js/ext4/examples/shared/icons/cancel.png',
			handler:function(){
				submit_form_InterB_view.getForm().reset();
				submit_window_InterB_view.close();
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
			id: 'rpt_syspk',
			name: 'rpt_syspk',
			fieldLabel: 'rpt_syspk',
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
					window.open('../reports/prnt_cash_SI_serialized.php?SI_req=YES&SI_num=' + Ext.getCmp('rpt_syspk').getValue());
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
			url : '?getReference=zHun',
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
	function loadInterBranch(){
		InterBStore.proxy.extraParams = {
			debtor_id: Ext.getCmp('customername_inb').getValue(),
			branch_code: sbranch_code,
			branch_name: sbranch_name,
			gl_account: sbranch_gl,
			date_issue: Ext.getCmp('trans_date_inb').getValue(),
			debitTo: Ext.getCmp('debit_acct_inb').getValue(),
			amount: Ext.getCmp('tenderd_amount_inb').getValue()
		};
		InterBStore.load();
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
				amount: Ext.getCmp('tenderd_amount_dp').getValue(),
				branch_code: Ext.getCmp('customercode_dp').getValue(),
				branch_name: Ext.getCmp('customername_dp').getRawValue()
			};
		}
		DPitemStore.load();
	}
});
