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
			{name:'cashier', mapping:'cashier'}
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
			url: '?get_custPayment=zHun&module_type=BEG',
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
		{header:'<b>Old Ref. No</b>', dataIndex:'receipt_no', sortable:true, width:100},
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
		{header:'<b>Cashier</b>', dataIndex:'prepared_by', sortable:true, width:150,
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
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:95,
			items:[{
				icon: '../js/ext4/examples/shared/icons/layout_content.png',
				tooltip: 'view details',
				handler: function(grid, rowIndex, colIndex) {
					var records = PaymentStore.getAt(rowIndex);

					submit_form_DP.getForm().reset();

					PaymentTypeStore.proxy.extraParams = {type: "downp"};
					PaymentTypeStore.load();
					CollectionTypeStore.proxy.extraParams = {type: "amort"};
					CollectionTypeStore.load();

					Ext.getCmp('syspk_dp').setValue(records.get('trans_no'));
					Ext.getCmp('moduletype_dp').setValue(records.get('module_type'));
					//Ext.getCmp('pay_type_dp').setValue(records.get('pay_type'));
					Ext.getCmp('paymentType_dp').setValue(records.get('payment_type'));
					Ext.getCmp('collectType_dp').setValue(records.get('collect_type'));
					Ext.getCmp('customercode_dp').setValue(records.get('debtor_ref'));
					Ext.getCmp('customername_dp').setValue(records.get('debtor_no'));
					Ext.getCmp('name_dp').setValue(records.get('customer_name'));
					Ext.getCmp('trans_date_dp').setValue(records.get('tran_date'));
					Ext.getCmp('ref_no_dp').setValue(records.get('reference'));
					Ext.getCmp('receipt_no_dp').setValue(records.get('receipt_no'));
					Ext.getCmp('intobankacct_dp').setValue(0);
					Ext.getCmp('total_amount_dp').setValue(records.get('total_amount'));
					Ext.getCmp('tenderd_amount_dp').setValue(records.get('total_amount'));
					Ext.getCmp('remarks_dp').setValue(records.get('remarks'));
					Ext.getCmp('check_date_dp').setValue(records.get('check_date'));
					Ext.getCmp('check_no_dp').setValue(records.get('check_no'));
					Ext.getCmp('bank_branch_dp').setValue(records.get('Bank_branch'));
					Ext.getCmp('preparedby_dp').setValue(records.get('prepared_by'));
					Ext.getCmp('cashier_dp').setValue(records.get('cashier'));

					Ext.getCmp('tenderd_amount_dp').setReadOnly(true);
					Ext.getCmp('customername_dp').setReadOnly(true);
					//Ext.getCmp('intobankacct_dp').setReadOnly(true);
					//RecptViewStore_inb.proxy.extraParams = {trans_no: records.get('trans_no')};
					//RecptViewStore_inb.load();

					loadGLDP("view");
					Ext.getCmp('btnDPcancel').setText('Close');
					Ext.getCmp('btnDPsave').setVisible(false);

					submit_window_DP.setTitle('Down-payment Entry Details - Reference No. : '+ records.get('reference'));
					submit_window_DP.show();
					submit_window_DP.setPosition(320,55);
				}
			},'-',{
				icon   : '../js/ext4/examples/shared/icons/chart_line.png',
				tooltip : 'Entries',
				handler : function(grid, rowIndex, colIndex){
					var records = PaymentStore.getAt(rowIndex);
					window.open('../gl/view/gl_trans_view.php?type_id=12&trans_no='+ records.get('trans_no'));
				}
			}]
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
		text:'<b>Add</b>',
		tooltip: 'Add new down payment without sales Invoice opening.',
		icon: '../js/ext4/examples/shared/icons/money_add.png',
		scale: 'small',
		handler: function(){
			submit_form_DP.getForm().reset();

			Ext.getCmp('check_dp').setVisible(false);
			CustomerStore.load();
			PaymentTypeStore.proxy.extraParams = {type: "downp"};
			PaymentTypeStore.load();

			Ext.getCmp('intobankacct_dp').setValue(0);
			Ext.getCmp('debit_acct_dp').setValue("2151");
			Ext.getCmp('paymentType_dp').setValue('down');
			Ext.getCmp('collectType_dp').setValue(1);//'interb'
			Ext.getCmp('moduletype_dp').setValue('BEG-DP');
			GetCashierPrep("downp");

			Ext.getCmp('btnDPcancel').setText('Cancel');
			Ext.getCmp('btnDPsave').setVisible(true);
			Ext.getCmp('cashier_dp').setVisible(false);
			Ext.getCmp('preparedby_dp').setVisible(false);
			
			Ext.getCmp('tenderd_amount_dp').setReadOnly(false);
			Ext.getCmp('customername_dp').setReadOnly(false);
			//Ext.getCmp('intobankacct_dp').setReadOnly(false);

			submit_window_DP.show();
			submit_window_DP.setTitle('Opening Down-payment Entry - Add');
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
				//allowBlank: false,
				hidden: true
			},{
				xtype: 'textfield',
				id: 'intobankacct_dp',
				name: 'intobankacct_dp',
				fieldLabel: 'intobankacct_dp',
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
					anyMatch: true,
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
					fieldLabel: 'Old Ref. No.',
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
					xtype: 'textfield',
					fieldLabel: 'Cashier/Teller ',
					id: 'cashier_dp',
					name: 'cashier_dp',
					allowBlank: false,
					readOnly: true,
					labelWidth: 105,
					width: 280,
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
					labelWidth: 105,
					margin: '0 50 0 0',
					forceSelection: true,
					selectOnFocus:true,
					editable: false
				},{
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
					},{
						xtype: 'numericfield',
						id: 'tenderd_amount_dp',
						name: 'tenderd_amount_dp',
						fieldLabel: 'Tendered Amount ',
						allowBlank:false,
						useThousandSeparator: true,
						labelWidth: 123,
						width: 255,
						//margin: '0 0 0 517',
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
				}]
			},{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					/*xtype: 'combobox',
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
							//Ext.getCmp('debit_acct_dp').setValue(record.get("type"));
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
					}*/
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
					hidden: true,
					format : 'm/d/Y'
				},{
					xtype: 'textfield',
					fieldLabel: 'Check No. ',
					id: 'check_no_dp',
					name: 'check_no_dp',
					allowBlank: true,
					labelWidth: 78,
					width: 230,
					hidden: true,
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
					hidden: true,
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
						url: '?submitBEGDP=payment',
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

	var Customer_Payment =  Ext.create('Ext.panel.Panel', { 
        renderTo: 'ext-form',
		id: 'cust_pay',
        frame: false,
		width: 1200,
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
					Ext.getCmp('cashier').setValue(result.cashier);
					Ext.getCmp('preparedby').setValue(result.prepare);
				}else if($tag == "interb"){
					Ext.getCmp('cashier_inb').setValue(result.cashier);
					Ext.getCmp('preparedby_inb').setValue(result.prepare);
				}else if($tag == "downp"){
					Ext.getCmp('cashier_dp').setValue(result.cashier);
					Ext.getCmp('preparedby_dp').setValue(result.prepare);
				}else if($tag == "sicash"){
					Ext.getCmp('cashier_cash').setValue(result.cashier);
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
				trans_no: Ext.getCmp('syspk_dp').getValue(),
				branch_name: Ext.getCmp('customername_dp').getRawValue()
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
