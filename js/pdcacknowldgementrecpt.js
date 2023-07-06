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
	Ext.define('comboModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'name', mapping:'name'},
			{name:'type', mapping:'type'}
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
	var cashierStore = Ext.create('Ext.data.Store', {
		model: 'comboModel',
		autoLoad : true,
		//pageSize: itemsPerPage, // items per page
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
	var CustomerStore = Ext.create('Ext.data.Store', {
		model: 'CustomersModel',
		autoLoad : true,
		//pageSize: itemsPerPage, // items per page
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
		model: 'InvoiceModel',
		//autoLoad : true,
		//pageSize: itemsPerPage, // items per page
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
	var IntoBankAcctStore = Ext.create('Ext.data.Store', {
		model: 'comboModel',
		autoLoad : true,
		//pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_IntoBank=PDC',
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
				/*	var records = PaymentStore.getAt(rowIndex);

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
						submit_window_cashview.setPosition(320,55);*/
						
				}
			},'-',{
				icon   : '../js/ext4/examples/shared/icons/chart_line.png',
				tooltip : 'Entries',
				handler : function(grid, rowIndex, colIndex){
					//var records = PaymentStore.getAt(rowIndex);
					//window.open('../gl/view/gl_trans_view.php?type_id=12&trans_no='+ records.get('trans_no'));
				}
			},'-',{
				icon: '../js/ext4/examples/shared/icons/print-preview-icon.png',
				tooltip: 'view reports',
				handler: function(grid, rowIndex, colIndex) {
					//var records = PaymentStore.getAt(rowIndex);

					//Ext.getCmp('rpt_syspk').setValue(records.get('reference'));
					//Ext.getCmp('rpt_transnum').setValue(records.get('trans_no'));
					//Ext.getCmp('rpt_receipt').setValue(records.get('receipt_no'));

					//report_window.setTitle('List Of Reports');
					//report_window.show();
					//report_window.setPosition(500,150);
				}
			}]
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
		text:'<b>Add</b>',
		tooltip: 'Add new PDC acknowldgement receipt payment',
		icon: '../js/ext4/examples/shared/icons/coins-in-hand-icon.png',
		scale: 'small',
		handler: function(){
			submit_form_cash.getForm().reset();
			
			Ext.getCmp('check_cash').setVisible(false);
			
			CustomerStore.load();
			PaymentTypeStore.proxy.extraParams = {type: "cash"};
			PaymentTypeStore.load();
			ARInvoiceStore.proxy.extraParams = {debtor_id: 0};
			ARInvoiceStore.load();

			Ext.getCmp('debit_acct_cash').setValue("1050");
			Ext.getCmp('paymentType_cash').setValue('other');
			Ext.getCmp('collectType_cash').setValue(1);//'office'
			Ext.getCmp('moduletype_cash').setValue('CI-CASH');

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
							
							//allocCash_store.proxy.extraParams = {transNo: record.get('id'), debtor_no: Ext.getCmp('customername_cash').getValue(), transtype: record.get('type')};
							//allocCash_store.load();
							//SIitemStore.proxy.extraParams = {transNo: record.get('id'), transtype: record.get('type')};
							//SIitemStore.load();
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
						url: '?submitSICash=payment',
						params: {
							DataOnGrid: Ext.encode(gridData),
							DataOEGrid: Ext.encode(OEData)
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
				})  ;
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
});
