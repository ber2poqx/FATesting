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
	var sbranch_code;
	var sbranch_name;
	var sbranch_gl;

    Ext.define('interb_paymnt_model',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'trans_no', mapping:'trans_no'},
			{name:'type', mapping:'type'},
			{name:'tran_date', mapping:'tran_date'},
			{name:'reference', mapping:'reference'},
			{name:'orref_no', mapping:'orref_no'},
			{name:'debtor_id', mapping:'debtor_id'},
			{name:'debtor_ref', mapping:'debtor_ref'},
			{name:'debtor_name', mapping:'debtor_name'},
			{name:'payment_type_v', mapping:'payment_type_v'},
			{name:'payment_type', mapping:'payment_type'},
			{name:'total_amount', mapping:'total_amount'},
			//{name:'cashier_name', mapping:'cashier_name'},
			//{name:'cashier_id', mapping:'cashier_id'},
			{name:'remarks', mapping:'remarks'},
			{name:'branch_gl', mapping:'branch_gl'},
			{name:'branch_code', mapping:'branch_code'},
			{name:'branch_name', mapping:'branch_name'},
			{name:'preparedby', mapping:'preparedby'}
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
	Ext.define('interBModel',{
		extend : 'Ext.data.Model',
		fields  : [
			//{name:'id',mapping:'id'},
			{name:'trans_date',mapping:'trans_date'},
			{name:'gl_code',mapping:'gl_code'},
			{name:'gl_name',mapping:'gl_name'},
			{name:'sl_code',mapping:'sl_code'},
			{name:'sl_name',mapping:'sl_name', type: 'string'},
			{name:'debtor_id',mapping:'debtor_id'},
			{name:'debit_amount',mapping:'debit_amount',type:'float'},
			{name:'credit_amount',mapping:'credit_amount',type:'float'},
			{name:'tag',mapping:'tag'}
		]
	});
	var smCheckAmortGrid = Ext.create('Ext.selection.CheckboxModel',{
		mode: 'SINGLE'
	});
	//------------------------------------: stores :----------------------------------------
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
	var FromBranchstore = Ext.create('Ext.data.Store', {
		name: 'Branchstore',
		fields:['id','name','area','gl_account'],
		autoLoad : true,
        proxy: {
			url: '?getFrbranch=00',
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
			url: '?get_interb_flending=xx',
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
			url: '?get_aloneCustomer=xx',
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
		autoLoad : true,
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

	//---------------------------------------------------------------------------------------
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

	var ColumnModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>trans_no</b>',dataIndex:'trans_no',hidden: true},
		{header:'<b>Date</b>', dataIndex:'tran_date', sortable:true, width:80, renderer: Ext.util.Format.dateRenderer('m-d-Y')},
		{header:'<b>Reference No.</b>', dataIndex:'reference', sortable:true, width:170},
		{header:'<b>OR Ref. No.</b>', dataIndex:'orref_no', sortable:true, width:100},
		{header:'<b>Customer Name</b>', dataIndex:'debtor_name', sortable:true, width:220,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Payment Type</b>', dataIndex:'payment_type_v', sortable:true, width:118},
		{header:'<b>Amount</b>', dataIndex:'total_amount', sortable:true, width:90,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
			}
		},
		/*{header:'<b>Cashier</b>', dataIndex:'cashier_name', sortable:true, width:150,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},*/
		{header:'<b>Remarks</b>', dataIndex:'remarks', sortable:true, width:180,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:105,
			items:[{
				icon: '../../js/ext4/examples/shared/icons/layout_content.png',
				tooltip: 'view details',
				handler : function(grid, rowIndex, colIndex){
					var records = qqinterb_store.getAt(rowIndex);
					submit_form.getForm().reset();

					CustomerStore.proxy.extraParams = {debtor_id: records.get('debtor_id'), name: records.get('debtor_name')};
					CustomerStore.load();
					PaymentTypeStore.proxy.extraParams = {type: "interb"};
					PaymentTypeStore.load();

					Ext.getCmp('moduletype').setValue('LNTB');
					Ext.getCmp('syspk').setValue(records.get('trans_no'));
					Ext.getCmp('debit_acct').setValue(records.get('branch_gl'));
					Ext.getCmp('ref_no').setValue(records.get('reference'));
					Ext.getCmp('custname').setValue(records.get('debtor_name'));
					Ext.getCmp('frombranch').setValue(records.get('branch_code'));
					Ext.getCmp('customercode').setValue(records.get('debtor_ref'));
					Ext.getCmp('customername').setValue(records.get('debtor_id'));
					Ext.getCmp('receipt_no').setValue(records.get('orref_no'));
					//Ext.getCmp('cashier').setValue(records.get('cashier_id'))
					Ext.getCmp('tenderd_amount').setValue(records.get('amount'));
					Ext.getCmp('trans_date').setValue(records.get('tran_date'));
					Ext.getCmp('preparedby').setValue(records.get('preparedby'));
					Ext.getCmp('total_amount').setValue(records.get('total_amount'));
					Ext.getCmp('remarks').setValue(records.get('remarks'));
					Ext.getCmp('paymentType').setValue(records.get('payment_type'));
					Ext.getCmp('tenderd_amount').setValue(records.get('total_amount'));

					sbranch_code = records.get('branch_code');
					sbranch_name = records.get('branch_name');
					sbranch_gl = records.get('branch_gl');

					loadInterBranch();
					//GetCashierPrep();
					//Ext.getCmp('btnsave').setDisabled(true);

					submit_window.setTitle('Inter-Branch Payment Details');
					submit_window.show();
					submit_window.setPosition(320,50);
				}
			},'-',{
				icon   : '../../js/ext4/examples/shared/icons/chart_line.png',
				tooltip : 'Entries',
				handler : function(grid, rowIndex, colIndex){
					var records = qqinterb_store.getAt(rowIndex);
					window.open('../../gl/view/gl_trans_view.php?type_id='+ records.get('type') + '&trans_no='+ records.get('trans_no'));
				}
			}]
		}
	];

	var submit_form = Ext.create('Ext.form.Panel', {
		id: 'submit_form',
		model: 'AllocationModel',
		frame: true,
		height: 400,
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
			id: 'custname',
			name: 'custname',
			fieldLabel: 'custname',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'combobox',
				id: 'frombranch',
				name: 'frombranch',
				fieldLabel: 'From Branch ',
				allowBlank: false,
				store : FromBranchstore,
				displayField: 'name',
				valueField: 'id',
				queryMode: 'local',
				labelWidth: 105,
				width: 560,
				anyMatch: true,
				forceSelection: true,
				selectOnFocus:true,
				fieldStyle: 'font-weight: bold; color: #210a04;',
				listeners: {
					select: function(combo, record, index) {
						Ext.getCmp('debit_acct').setValue(record.get('gl_account'));
						sbranch_code = record.get('id');
						sbranch_name = record.get('name');
						sbranch_gl = record.get('gl_account');

						loadInterBranch();
					}
				}
			},{
				xtype : 'datefield',
				id	  : 'trans_date',
				name  : 'trans_date',
				fieldLabel : 'Date ',
				allowBlank: false,
				labelWidth: 100,
				width: 260,
				format : 'm/d/Y',
				fieldStyle: 'font-weight: bold; color: #210a04;',
				value: Ext.Date.format(new Date(), 'Y-m-d'),
				listeners: {
					select: function(combo, record, index) {
						Ext.getCmp('frombranch').setValue();
						Ext.getCmp('total_amount').setValue();
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
				anyMatch: true,
				forceSelection: true,
				selectOnFocus:true,
				fieldStyle: 'font-weight: bold; color: #210a04;',
				listeners: {
					select: function(combo, record, index) {
						Ext.getCmp('customercode').setValue(record.get('debtor_ref'));
						Ext.getCmp('custname').setValue(combo.getRawValue());

						Getreference();
						loadInterBranch();
					}
				}
			},{
				xtype: 'textfield',
				fieldLabel: 'OR Ref. #',
				id: 'receipt_no',
				name: 'receipt_no',
				margin: '2 0 0 0',
				allowBlank: false,
				enforceMaxLength: true,
				labelWidth: 100,
				width: 260,
				maxLength : 7,
				maskRe: /^([a-zA-Z0-9 _.,-`]+)$/,
				fieldStyle: 'font-weight: bold; color: #210a04;'
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
				fieldStyle: 'font-weight: bold; color: #210a04;'*/
			},{
				xtype: 'textfield',
				fieldLabel: 'Prepared By ',
				id: 'preparedby',
				name: 'preparedby',
				allowBlank: false,
				readOnly: true,
				labelWidth: 105,
				width: 280,
				padding: '0 280 0 0',
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
				width: 260,
				margin: '0 0 2 0',
				allowBlank: false,
				forceSelection: true,
				selectOnFocus:true,
				editable: false,
				fieldStyle: 'font-weight: bold; color: #210a04;',
				listeners: {

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
				width: 560,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'fieldcontainer',
				layout: 'vbox',
				margin: '2 0 2 5',
				items:[{
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
				},{
					xtype: 'numericfield',
					id: 'tenderd_amount',
					name: 'tenderd_amount',
					fieldLabel: 'Tenderd Amount ',
					allowBlank:false,
					useThousandSeparator: true,
					//readOnly: true,
					labelWidth: 115,
					width: 255,
					margin: '0 0 2 0',
					thousandSeparator: ',',
					minValue: 0,
					fieldStyle: 'font-weight: bold;color: red; text-align: right; background-color: #F2F4F4;',
					listeners: {
						afterrender: function(field) {
							field.focus(true);
						},
						change: function(object, value) {
							Ext.getCmp('total_amount').setValue(value);

							loadInterBranch();
						}
					}
				}]
			}]
		},{
			xtype: 'tabpanel',
			id: 'alloctabpanel',
			activeTab: 0,
			//width: 860,
			//height: 165,
			scale: 'small',
			items:[{
				xtype:'gridpanel',
				id: 'InterBGrid',
				anchor:'100%',
				layout:'fit',
				title: 'Inter-Branch Entry',
				icon: '../js/ext4/examples/shared/icons/vcard.png',
				loadMask: true,
				store:	InterBStore,
				columns: InterBGLHeader,
				features: [{ftype: 'summary'}],
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
			text: '<b>Save</b>',
			tooltip: 'Save customer payment',
			icon: '../../js/ext4/examples/shared/icons/add.png',
			single : true,
			handler:function(){
				var form_submit_InterB = Ext.getCmp('submit_form').getForm();
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
							credit_amount: item.get('credit_amount')
						};
						girdInterBData.push(ObjInterB);
					});
					form_submit_InterB.submit({
						url: '?submitInterB_lending=payment',
						params: {
							InterBDataOnGrid: Ext.encode(girdInterBData),
						},
						waitMsg: 'Saving payment. please wait...',
						method:'POST',
						submitEmptyText: false,
						success: function(form_submit_InterB, action) {
							qqinterb_store.load();
							Ext.Msg.alert('Success!', '<font color="green">' + action.result.message + '</font>');
							submit_window.close();
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
		xtype: 'searchfield',
		id:'search',
		name:'search',
		fieldLabel: '<b>Search</b>',
		labelWidth: 50,
		width: 400,
		emptyText: "Search...",
		scale: 'small',
		store: qqinterb_store,
		listeners: {
			change: function(field) {
				qqinterb_store.proxy.extraParams = {query: field.getValue()};
				qqinterb_store.load();
			}
		}
	}, '-', {
		text:'<b>Add</b>',
		tooltip: 'Add new inter-branch',
		icon: '../../js/ext4/examples/shared/icons/report_add.png',
		scale: 'small',
		handler: function(){
			submit_form.getForm().reset();
			
			PaymentTypeStore.proxy.extraParams = {type: "interb"};
			PaymentTypeStore.load();

			Ext.getCmp('moduletype').setValue('LNTB');
			Ext.getCmp('paymentType').setValue('other');
			GetCashierPrep();

			submit_window.show();
			submit_window.setTitle('Inter-Branch To Lending Entry - Add');
			submit_window.setPosition(320,23);
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
		width: 1250,
		tbar: tbar,
		items: [{
			xtype: 'grid',
			id: 'interbPayment_grid',
			name: 'interbPayment_grid',
			store:	qqinterb_store,
			columns: ColumnModel,
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
				}
			}
		}]
	});

	function Getreference(){
		Ext.Ajax.request({
			url : '?getReference=inblending',
			async:false,
			success: function (response){
				var result = Ext.JSON.decode(response.responseText);
				Ext.getCmp('ref_no').setValue(result.reference);
				submit_window.setTitle('Inter-Branch Entry - Reference No. : '+ result.reference + ' *new');
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
	function loadInterBranch(){
		InterBStore.proxy.extraParams = {
			debtor_id: Ext.getCmp('customername').getValue(),
			branch_code: sbranch_code,
			branch_name: sbranch_name,
			gl_account: sbranch_gl,
			date_issue: Ext.getCmp('trans_date').getValue(),
			debitTo: Ext.getCmp('debit_acct').getValue(),
			amounttotal: Ext.getCmp('total_amount').getValue(),
			amounttenderd: Ext.getCmp('tenderd_amount').getValue()
		};
		InterBStore.load();
	};
});
