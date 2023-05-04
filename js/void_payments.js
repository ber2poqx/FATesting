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
	var GridItemOnTab = 7;
	var showall = false;
	var showlend = false;
	var url_transno = getUrlParameter('trans_no');
	var url_typeno = getUrlParameter('type');

	////define model for policy installment
    Ext.define('voidmodel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'trans_no', mapping:'trans_no'},
			{name:'type', mapping:'type'},
			{name:'debtor_no', mapping:'debtor_no'},
			{name:'customer_code', mapping:'customer_code'},
			{name:'customer_name', mapping:'customer_name'},
			{name:'tran_date', mapping:'tran_date'},
			{name:'reference', mapping:'reference'},
			{name:'recpt_no', mapping:'recpt_no'},
			{name:'total', mapping:'total'},
			{name:'payment_type', mapping:'payment_type'},
			{name:'collect_type', mapping:'collect_type'},
			{name:'module_type', mapping:'module_type'},
			{name:'Bank_account', mapping:'Bank_account'},
			{name:'remarks', mapping:'remarks'},
			{name:'prepared_by', mapping:'prepared_by'},
			{name:'check_by', mapping:'check_by'},
			{name:'approved_by', mapping:'approved_by'},
			{name:'cashier', mapping:'cashier'},
			{name:'cashier_name', mapping:'cashier_name'},
		]
	});
    Ext.define('Entriesmodel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'trans_no', mapping:'trans_no'},
			{name:'type', mapping:'type'},
			{name:'entry_date', mapping:'entry_date'},
			{name:'acct_code', mapping:'acct_code'},
			{name:'descrption', mapping:'descrption'},
			{name:'debit_amount', mapping:'debit_amount', type: 'float'},
			{name:'credit_amount', mapping:'credit_amount', type: 'float'}
		]
	});
    Ext.define('AmortLedgrmodel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'loansched_id', mapping:'loansched_id'},
			{name:'ledger_id', mapping:'ledger_id'},
			{name:'trans_no', mapping:'trans_no'},
			{name:'debtor_no', mapping:'debtor_no'},
			{name:'month_no', mapping:'month_no'},
			{name:'due_date', mapping:'due_date'},
			{name:'date_paid', mapping:'date_paid'},
			{name:'pay_ref_no', mapping:'pay_ref_no'},
			{name:'amortization', mapping:'amortization', type: 'float'},
			{name:'payment_appld', mapping:'payment_appld', type: 'float'},
			{name:'rebate', mapping:'rebate', type: 'float'},
			{name:'penalty', mapping:'penalty', type: 'float'}
		]
	});

	var ARInstallQstore = Ext.create('Ext.data.Store', {
		model: 'voidmodel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_voidPayment=zHun&trans_type='+url_typeno+'&trans_no= '+url_transno,
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true
	});
	
	var Entriestore = Ext.create('Ext.data.Store', {
		model: 'Entriesmodel',
		autoLoad : true,
		proxy: {
			url: '?get_AREntry=zHun&trans_type='+url_typeno+'&trans_no= '+url_transno,
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		groupField: 'trans_no',
		sorters : [{
			property : 'acct_code',
			direction : 'ASC'
		}]
	});
	var AmortLedgertore = Ext.create('Ext.data.Store', {
		model: 'AmortLedgrmodel',
		proxy: {
			url: '?get_AmortLedger=zHun',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true
	});

	var EntriescolModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Id</b>',dataIndex:'id',hidden: true},
		{header:'<b>Date</b>', dataIndex:'entry_date', sortable:true, width:130,
			summaryRenderer: function(value, summaryData, dataIndex) {
				return '<span style="color:blue;font-weight:bold"> Grand Total: </span>';
			}
		},
		{header:'<b>Account Code</b>', dataIndex:'acct_code', sortable:true, width:150},
		{header:'<b>Description</b>', dataIndex:'descrption', sortable:true, width:275},
		{header:'<b>Debit</b>', dataIndex:'debit_amount', sortable:true, width:140,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold">' + Ext.util.Format.number(value, '0,000.00') + '</span>';
			},
			summaryType: 'sum',
            summaryRenderer: function(value, summaryData, record, dataIndex) {
				return '<span style="color:blue;font-weight:bold">' + Ext.util.Format.number(value, '0,000.00') + '</span>';
			}
		},
		{header:'<b>Credit</b>', dataIndex:'credit_amount', sortable:true, width:140,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold">' + Ext.util.Format.number(value, '0,000.00') + '</span>';
			},
			summaryType: 'sum',
            summaryRenderer: function(value, summaryData, record, dataIndex) {
				return '<span style="color:blue;font-weight:bold">' + Ext.util.Format.number(value, '0,000.00') + '</span>';
			}
		}
	];
	var AmortLedgerHeader = [
		{header:'<b>Amort</br>No.</b>', dataIndex:'month_no',align:'center', sortable:false, width:80},
		{header:'<b>Date Due</b>', dataIndex:'due_date', sortable:true, width:90},
		{header:'<b>Trans No</b>', dataIndex:'pay_ref_no', sortable:false, width:100},
		{header:'<b>Date Paid</b>', dataIndex:'date_paid', sortable:false, width:90},
		{header:'<b>Principal</br>Amortization</b>', dataIndex:'amortization', sortable:false, width:120,
			renderer: function(value, metaData, record, rowIndex, colIndex, store) {
				metaData.tdAttr = 'data-qtip=' + Ext.util.Format.number(value, '0,000.00');
				return '<span style="color:#793F3F;font-weight:bold">' + Ext.util.Format.number(value, '0,000.00') + '</span>';
			}
		},
		{header:'<b>Payment</br>Applied</b>', dataIndex:'payment_appld', sortable:false, width:120, align: 'center',
			renderer: function(value, metaData, record, rowIndex, colIndex, store) {
				metaData.tdAttr = 'data-qtip=' + Ext.util.Format.number(value, '0,000.00');
				return '<span style="color:#793F3F;font-weight:bold">' + Ext.util.Format.number(value, '0,000.00') + '</span>';
			},
			summaryType: 'sum',
            summaryRenderer: function(value, summaryData, record, dataIndex) {
				return '<span style="color:blue;font-weight:bold">' + Ext.util.Format.number(value, '0,000.00') + '</span>';
			}
		},
		{header:'<b>Rebate</b>', dataIndex:'rebate',sortable:false, width:110, align: 'center',
			renderer: function(value, metaData, record, rowIndex, colIndex, store) {
				metaData.tdAttr = 'data-qtip=' + Ext.util.Format.number(value, '0,000.00');
				return '<span style="color:#793F3F;font-weight:bold">' + Ext.util.Format.number(value, '0,000.00') + '</span>';
			},
			summaryType: 'sum',
            summaryRenderer: function(value, summaryData, record, dataIndex) {
				return '<span style="color:blue;font-weight:bold">' + Ext.util.Format.number(value, '0,000.00') + '</span>';
			}
		},
		{header:'<b>Penalty</b>', dataIndex:'penalty', sortable:false, width:110, align: 'center',
			renderer: function(value, metaData, record, rowIndex, colIndex, store) {
				metaData.tdAttr = 'data-qtip=' + Ext.util.Format.number(value, '0,000.00');
				return '<span style="color:#793F3F;font-weight:bold">' + Ext.util.Format.number(value, '0,000.00') + '</span>';
			},
			summaryType: 'sum',
            summaryRenderer: function(value, summaryData, record, dataIndex) {
				return '<span style="color:blue;font-weight:bold">' + Ext.util.Format.number(value, '0,000.00') + '</span>';
			}
		}
	];

	var submit_form = Ext.create('Ext.form.Panel', {
		renderTo: 'voidform',
		id: 'voidform',
		model: 'voidmodel',
		defaultType: 'field',
		defaults: {msgTarget: 'under', labelWidth: 125, anchor: '-5'}, //msgTarget: 'side', labelAlign: 'top'
			items: [{
				xtype: 'fieldcontainer',
				layout: 'vbox',
				margin: '2 2 2 0',
				style : {
					'background-color': '#edeeef',
					'border-radius':'10px'
				},
				items:[{
					xtype: 	'textareafield',
					id:	'remarks',
					name: 'remarks',
					fieldLabel: '<b>Approval Remarks </b>',
					labelWidth: 130,
					width: 820,
					allowBlank: false
				},{
					xtype: 'button',
					text: 'Approved',
					padding: '8px',
					margin: '2 2 2 710',
					icon: '../../js/ext4/examples/shared/icons/ipod_cast_delete.png',
					tooltip: 'Void Transaction',
					style : {
						'color': 'black',
						'font-size': '30px',
						'font-weight': 'bold',
						'background-color': '#0766f9 ',
						'position': 'absolute',
						'box-shadow': '0px 0px 2px 2px rgb(0,0,0)',
						//'border': 'none',
						'border-radius':'3px'
					},
				}]
			},{
			xtype: 'panel',
			id: 'mainpanel',
			frame: true,
			//width: 1060,
			items: [{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					xtype: 'textfield',
					fieldLabel: '<b>Reference No. </b>',
					id: 'Ref_no',
					name: 'Ref_no',
					allowBlank: false,
					labelWidth: 105,
					width: 560,
					margin: '0 0 0 0',
					readOnly: true,
					fieldStyle : 'text-transform: capitalize; background-color: #ddd; background-image: none; color:green; font-weight: bold;'
				},{
					xtype : 'datefield',
					id	  : 'trans_date',
					name  : 'trans_date',
					fieldLabel : '<b>Date </b>',
					allowBlank: false,
					labelWidth: 80,
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
					xtype: 'textfield',
					fieldLabel: '<b>Customer </b>',
					id: 'cust_code',
					name: 'cust_code',
					allowBlank: false,
					labelWidth: 105,
					width: 250,
					readOnly: true,
					fieldStyle: 'font-weight: bold; color: #210a04;'
				},{
					xtype: 'textfield',
					id: 'cust_name',
					name: 'cust_name',
					allowBlank: false,
					readOnly: true,
					width: 309,
					margin: '0 0 0 1',
					maskRe: /^([a-zA-Z0-9 _.,-`]+)$/,
					fieldStyle: 'font-weight: bold; color: #210a04;',
				},{
					xtype: 'textfield',
					fieldLabel: '<b>Receipts No. </b>',
					id: 'recpt_no',
					name: 'recpt_no',
					allowBlank: false,
					labelWidth: 100,
					width: 255,
					readOnly: true,
					fieldStyle: 'font-weight: bold; color: #210a04;'
				}]
			},{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					xtype: 'textfield',
					id: 'intobankacct',
					name: 'intobankacct',
					allowBlank: false,
					readOnly: true,
					fieldLabel : '<b>Into Bank Account </b>',
					labelWidth: 125,
					width: 560,
					fieldStyle: 'font-weight: bold; color: #210a04;'
				},{
					xtype: 'textfield',
					id: 'paytype',
					name: 'paytype',
					fieldLabel: 'Payment type ',
					width: 255,
					allowBlank: false,
					readOnly: true,
					fieldStyle: 'font-weight: bold; color: #210a04;'
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
					xtype: 'textfield',
					id: 'collectType',
					name: 'collectType',
					fieldLabel: 'Collction type ',
					margin: '0 0 2 0',
					width: 255,
					allowBlank: false,
					readOnly: true,
					fieldStyle: 'font-weight: bold; color: #210a04;'
				}]
			},{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					xtype: 	'textareafield',
					fieldLabel: '<b>Remarks </b>',
					id:	'vremarks',
					name: 'vremarks',
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
						id: 'total_amount',
						name: 'total_amount',
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
				xtype: 'tabpanel',
				id: 'tbpanel',
				activeTab: 0,
				width: 1060,
				height: 1240,
				scale: 'small',
				items:[{
					xtype:'grid',
					id: 'EntriesGrid',
					title: 'Entries',
					anchor:'100%',
					width: 1060,
					height: 440,
					autoScroll: true,
					loadMask: true,
					store: Entriestore,
					columns: EntriescolModel,
					columnLines: true,
					bbar : {
						xtype : 'pagingtoolbar',
						hidden: false,
						store : Entriestore,
						displayInfo : false,
						emptyMsg: "No records to display",
						doRefresh : function(){
							Entriestore.load();
							
						}
					}
				/*},{
					xtype:'gridpanel',
					id: 'AmortLedgerGrid',
					title: 'Amortization Ledger',
					anchor:'100%',
					autoScroll: true,
					loadMask: true,
					store: AmortLedgertore,
					columns: AmortLedgerHeader,
					height: 440,
					width: 560,
					columnLines: true*/
				}]
			}]
		}]
	});
	var submit_window = Ext.create('Ext.Window',{
		
		items:[submit_form],
		buttons:[{
			text: '<b>Save</b>',
			tooltip: 'Save customer payment',
			icon: '../js/ext4/examples/shared/icons/add.png',
			single : true,				
			handler:function(){
				//console.log(Ext.decode(gridData));
				form_submit.submit({
					url: '?submit=payment',
					params: {
						DataOnGrid: Ext.encode(gridData),
						DataOEGrid: Ext.encode(OEData)
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

	ARInstallQstore.load({
		callback: function(records) {                 
			//alert(records[i].get('customer_name'));
			Ext.getCmp('Ref_no').setValue(records[i].get('reference'));
			Ext.getCmp('recpt_no').setValue(records[i].get('recpt_no'));
			Ext.getCmp('trans_date').setValue(records[i].get('tran_date'));
			Ext.getCmp('cust_code').setValue(records[i].get('customer_code'));
			Ext.getCmp('cust_name').setValue(records[i].get('customer_name'));
			Ext.getCmp('intobankacct').setValue(records[i].get('Bank_account'));
			Ext.getCmp('paytype').setValue(records[i].get('payment_type'));
			Ext.getCmp('v_preparedby').setValue(records[i].get('recpt_no'));
			Ext.getCmp('collectType').setValue(records[i].get('collect_type'));
			Ext.getCmp('vremarks').setValue(records[i].get('remarks'));
			Ext.getCmp('total_amount').setValue(records[i].get('total'));
			Ext.getCmp('v_cashier').setValue(records[i].get('cashier_name'));
			Ext.getCmp('v_preparedby').setValue(records[i].get('prepared_by'));
		}
	});
	/*Entriestore.load({
		callback: function(records) {
			alert(records[i].get('acct_code'));
		}
	});*/
});
