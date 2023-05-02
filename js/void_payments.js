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
			{name:'debtor_ref', mapping:'debtor_ref'},
			{name:'debtor_name', mapping:'debtorname'},
			{name:'status', mapping:'status'},
			{name:'gender', mapping:'gender'},
			{name:'age', mapping:'age'},
			{name:'name_father', mapping:'name_father'},
			{name:'name_mother', mapping:'name_mother'},
			{name:'address', mapping:'address'},
			{name:'collector', mapping:'collector'},
			{name:'phone', mapping:'phone'},
			{name:'email', mapping:'email'},
			{name:'branch_no', mapping:'branch_no'},
			{name:'tran_date', mapping:'tran_date'},
			{name:'reference', mapping:'reference'},
			{name:'order', mapping:'order'},
			{name:'ov_amount', mapping:'ov_amount'},
			{name:'trans_status', mapping:'trans_status'},
			{name:'debtor_loan_id', mapping:'debtor_loan_id'},
			{name:'invoice_ref_no', mapping:'invoice_ref_no'},
			{name:'delivery_ref_no', mapping:'delivery_ref_no'},
			{name:'invoice_date', mapping:'invoice_date'},
			{name:'orig_branch_code', mapping:'orig_branch_code'},
			{name:'invoice_type', mapping:'invoice_type'},
			{name:'installplcy_id', mapping:'installplcy_id'},
			{name:'months_term', mapping:'months_term'},
			{name:'rebate', mapping:'rebate'},
			{name:'fin_rate', mapping:'fin_rate'},
			{name:'firstdue_date', mapping:'firstdue_date'},
			{name:'maturity_date', mapping:'maturity_date'},
			{name:'outs_ar_amount', mapping:'outs_ar_amount'},
			{name:'ar_amount', mapping:'ar_amount'},
			{name:'ar_balance', mapping:'ar_balance'},
			{name:'lcp_amount', mapping:'lcp_amount'},
			{name:'dp_amount', mapping:'dp_amount'},
			{name:'amortn_amount', mapping:'amortn_amount'},
			{name:'total_amount', mapping:'total_amount'},
			{name:'category_id', mapping:'category_id'},
			{name:'category_desc', mapping:'category_desc'},
			{name:'comments', mapping:'comments'},
			{name:'pay_status', mapping:'pay_status'},
			{name:'module_type', mapping:'module_type'},
			{name:'unrecovered', mapping:'unrecovered'},
			{name:'addon_amount', mapping:'addon_amount'},
			{name:'total_unrecovrd', mapping:'total_unrecovrd'},
			{name:'repo_date', mapping:'repo_date'},
			{name:'past_due', mapping:'past_due'},
			{name:'over_due', mapping:'over_due'},
			{name:'repo_remark', mapping:'repo_remark'},
			{name:'type_module', mapping:'type_module'},
			{name:'term_mod_date', mapping:'term_mod_date'},
			{name:'amort_diff', mapping:'amort_diff'},
			{name:'months_paid', mapping:'months_paid'},
			{name:'amort_delay', mapping:'amort_delay'},
			{name:'adj_rate', mapping:'adj_rate'},
			{name:'oppnity_cost', mapping:'oppnity_cost'},
			{name:'amount_to_be_paid', mapping:'amount_to_be_paid'},
			{name:'Termremarks', mapping:'Termremarks'},
			{name:'profit_margin', mapping:'profit_margin'},
			{name:'payment_loc', mapping:'payment_loc'},
			{name:'restructured_status', mapping:'restructured_status'}
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
			url: '?get_custPayment=zHun&module_type=ALCN&transno= '+url_transno,
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
		}],
		callback: function(records, operation, success) {
			console.log(records.get('trans_no'));
			alert(records.get('trans_no'));
		}
	});
	
	var Entriestore = Ext.create('Ext.data.Store', {
		model: 'Entriesmodel',
		proxy: {
			url: '?get_AREntry=zHun',
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
				margin: '0 0 10 300',
				items:[{
					xtype: 	'textareafield',
					id:	'remarks',
					name: 'remarks',
					fieldLabel: '<b>Approval Remarks </b>',
					labelWidth: 130,
					width: 560,
					allowBlank: false
				},{
					xtype: 'button',
					text: 'Approved',
					padding: '8px',
					margin: '2 2 2 440',
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
					fieldLabel: '<b>Customer </b>',
					id: 'customercode',
					name: 'customercode',
					allowBlank: false,
					labelWidth: 105,
					width: 250,
					readOnly: true,
					fieldStyle: 'font-weight: bold; color: #210a04;'
				},{
					xtype : 'datefield',
					id	  : 'trans_date',
					name  : 'trans_date',
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
					xtype: 'textfield',
					fieldLabel: '<b>Customer </b>',
					id: 'InvoiceNo',
					name: 'InvoiceNo',
					allowBlank: false,
					labelWidth: 105,
					width: 250,
					readOnly: true,
					fieldStyle: 'font-weight: bold; color: #210a04;'
				},{
					xtype: 'textfield',
					fieldLabel: '<b>CR No.</b>',
					id: 'receipt_no',
					name: 'receipt_no',
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
					xtype:'gridpanel',
					id: 'EntriesGrid',
					title: 'Entries',
					anchor:'100%',
					height: 440,
					autoScroll: true,
					loadMask: true,
					store: Entriestore,
					columns: EntriescolModel,
					columnLines: true
				},{
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
					columnLines: true
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
alert(sParameterName);
			if (sParameterName[0] === sParam) {
				return sParameterName[1] === undefined ? true : sParameterName[1];
			}
		}
	};
});
