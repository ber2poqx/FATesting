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
	var sumdebit = 0;
	var sumcredit = 0;

	////define model for policy installment
    Ext.define('ARInstlqmodel',{
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
    Ext.define('AmortSchedmodel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'debtor_no', mapping:'debtor_no'},
			{name:'trans_no', mapping:'trans_no'},
			{name:'trans_type', mapping:'trans_type'},
			{name:'month_no', mapping:'month_no', type: 'float'},
			{name:'date_due', mapping:'date_due'},
			{name:'weekday', mapping:'weekday'},
			{name:'principal_due', mapping:'principal_due'},
			{name:'principal_runbal', mapping:'principal_runbal'},
			{name:'total_principaldue', mapping:'total_principaldue'},
			{name:'total_runbal', mapping:'total_runbal'},
			{name:'interest_due', mapping:'interest_due'},
			{name:'interest_runbal', mapping:'interest_runbal'},
			{name:'status', mapping:'status'}
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
    Ext.define('EntryLedgrmodel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'transno', mapping:'transno'},
			{name:'docno', mapping:'docno'},
			{name:'type', mapping:'type'},
			{name:'tran_date', mapping:'tran_date'},
			{name:'debtor_no', mapping:'debtor_no'},
			{name:'name', mapping:'name'},
			{name:'account', mapping:'account'},
			{name:'account_name', mapping:'account_name'},
			{name:'debit', mapping:'debit'},
			{name:'credit', mapping:'credit'},
			{name:'balance', mapping:'balance'},
			{name:'reference', mapping:'reference'}
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
			{name:'chasis',mapping:'chasis'},
			{name:'type',mapping:'type'}
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
	var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
        clicksToEdit: 2
    });
	
	//------------------------------------: stores :----------------------------------------
	var ARInstallQstore = Ext.create('Ext.data.Store', {
		model: 'ARInstlqmodel',
		//autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_arinstallment=zHun',
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
	var AmortSchedstore = Ext.create('Ext.data.Store', {
		model: 'AmortSchedmodel',
		//pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_AmortSched=zHun',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'month_no',
			direction : 'ASC'
		}]
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
	var EntryLedgertore = Ext.create('Ext.data.Store', {
		model: 'EntryLedgrmodel',
		proxy: {
			url: '?get_LedgerEntry=zHun',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true
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
	var InvTypeStore = Ext.create('Ext.data.Store',{
		fields: ['id','name'],
		autoLoad: true,
		data : 	[
            {"id":"new","name":"Brand new"},
            {'id':'repo','name':'Repo'},
			{"id":"x","name":"All"}
        ]
	});
	var deferdtore = Ext.create('Ext.data.Store', {
		model: 'EntryLedgrmodel',
		proxy: {
			url: '?get_deferedLed=zHun',
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
	//---------------------------------------------------------------------------------------
	var AmortSchedcolModel = [
		//new Ext.grid.RowNumberer(),
		{header:'<b>Id</b>',dataIndex:'id',hidden: true},
		{header:'<b>No.</b>', dataIndex:'month_no', sortable:true, width:70},
		{header:'<b>Due Date</b>', dataIndex:'date_due', sortable:true, width:90},
		{header:'<b>Week Day</b>', dataIndex:'weekday', sortable:true, width:90},
		{header:'<b>Principal Due</b>', dataIndex:'principal_due', sortable:true, width:120,
			renderer: Ext.util.Format.Currency = function(value){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>Principal Run Bal.</b>', dataIndex:'principal_runbal', sortable:true, width:150,
			renderer: Ext.util.Format.Currency = function(value){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>Total Principal Due</b>', dataIndex:'total_principaldue', sortable:true, width:150,
			renderer: Ext.util.Format.Currency = function(value){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>Total Principal Run Bal.</b>', dataIndex:'total_runbal', sortable:true, width:180,
			renderer: Ext.util.Format.Currency = function(value){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>Interest Due</b>', dataIndex:'interest_due', sortable:true, hidden: true},
		{header:'<b>Interest RunBal.</b>', dataIndex:'interest_runbal', sortable:true, hidden: true},
		{header:'<b>Status</b>', dataIndex:'status',
			renderer: function(value, metaData, record, rowIndex, colIndex, store) {
				if (value == "paid"){
					return '<span style="color:green;">' + value + '</span>';
				}else if(value == "partial"){
					return '<span style="color:brown;">' + value + '</span>';
				}else{
					return '<span style="color:blue;">' + value + '</span>';
				}
			}
		}
	];
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
	var EntryLedgerHeader = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Date</b>', dataIndex:'tran_date', sortable:true, width:90},
		{header:'<b>Reference No.</b>', dataIndex:'reference', sortable:true, width:118},
		{header:'<b>GL Account</b>', dataIndex:'account_name', sortable:false, width:300},
		{header:'<b>Debit</b>', dataIndex:'debit', sortable:false, width:110,
			renderer: function(value, metaData, record, rowIndex, colIndex, store) {
				sumdebit = sumdebit + value;
				metaData.tdAttr = 'data-qtip=' + Ext.util.Format.number(value, '0,000.00');
				return '<span style="color:green;font-weight:bold">' + Ext.util.Format.number(value, '0,000.00') + '</span>';
			},
			summaryType: 'sum',
            summaryRenderer: function(value, summaryData, record, dataIndex) {
				return '<span style="color:blue;font-weight:bold">' + Ext.util.Format.number(value, '0,000.00') + '</span>';
			}
		},
		{header:'<b>Credit</b>', dataIndex:'credit', sortable:false, width:110, align: 'center',
			renderer: function(value, metaData, record, rowIndex, colIndex, store) {
				sumcredit = sumcredit + value;
				metaData.tdAttr = 'data-qtip=' + Ext.util.Format.number(value, '0,000.00');
				return '<span style="color:green;font-weight:bold">' + Ext.util.Format.number(value, '0,000.00') + '</span>';
			},
			summaryType: 'sum',
            summaryRenderer: function(value, summaryData, record, dataIndex) {
				return '<span style="color:blue;font-weight:bold">' + Ext.util.Format.number(value, '0,000.00') + '</span>';
			}
		},
		{header:'<b>Balance</b>', dataIndex:'balance',sortable:false, width:110, align: 'center',
			renderer: function(value, metaData, record, rowIndex, colIndex, store) {
				metaData.tdAttr = 'data-qtip=' + Ext.util.Format.number(value, '0,000.00');
				return '<span style="color:green;font-weight:bold">' + Ext.util.Format.number(value, '0,000.00') + '</span>';
			},
            summaryType: 'min',
            summaryRenderer: function(value, summaryData, record, dataIndex) {
				//added sumdebit & sumcredit kay dili sakto ang display kung naay gi void nga ledger
				value = sumdebit - sumcredit;
				sumdebit = sumcredit = 0;
				return '<span style="color:blue;font-weight:bold">' + Ext.util.Format.number(value, '0,000.00') + '</span>';
				//return '<span style="color:blue;font-weight:bold">' + Ext.util.Format.number(Ext.getCmp('balance_amount').getValue(), '0,000.00') + '</span>';
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
	var Item_view = [
		{header:'<b>Item Code</b>', dataIndex:'stock_id', width:120, editor: 'textfield'},
		{header:'<b>Description</b>', dataIndex:'description', width:148, editor: 'textfield',
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
		{header:'<b>Serial No.</b>', dataIndex:'serial', width:200, editor: 'textfield',
			renderer : function(value, metaData, summaryData, dataIndex){
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Chasis No.</b>', dataIndex:'chasis', width:200, editor: 'textfield',
			renderer : function(value, metaData, summaryData, dataIndex){
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		}
	];
	var link_form = Ext.create('Ext.form.Panel', {
		id: 'link_form',
		model: 'AllocationModel',
		//frame: true,
		margin: '2 2 2 2',
		items: [{
			xtype: 'textfield',
			id: 'rpt_transno',
			name: 'rpt_transno',
			fieldLabel: 'rpt_transno',
			//allowBlank: false,
			hidden: true
		}, {
			xtype: 'textfield',
			id: 'rpt_transtype',
			name: 'rpt_transtype',
			fieldLabel: 'rpt_transtype',
			//allowBlank: false,
			hidden: true
		}, {
			xtype: 'textfield',
			id: 'fldstatus',
			name: 'fldstatus',
			fieldLabel: 'fldstatus',
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
				text:'<b>Change Term</b>',
				icon: '../../js/ext4/examples/shared/icons/cash-register-icon.png',
				margin: '2 2 2 2',
				handler : function() {
					window.open('../../sales/sales_order_entry.php?NewChangeTerm=' + Ext.getCmp('rpt_transno').getValue() + '&opening_balance=1&paytype=' + Ext.getCmp('rpt_transtype').getValue());
					submit_window_InterB.close();
				}
			},{
                xtype: 'splitter'
			},{
				xtype: 'button',
				cls: 'rptbtn',
				width: 200,
				text:'<b>Restructured Account</b>',
				icon: '../../js/ext4/examples/shared/icons/script.png',
				margin: '2 2 2 2',
				handler : function() {
					if(Ext.getCmp('fldstatus').getValue() == 0){
						window.open('../../sales/sales_invoice_restructured_approval.php?SONumber=' + Ext.getCmp('rpt_transno').getValue()+ '&paytype=' + Ext.getCmp('rpt_transtype').getValue());
					}else{
						window.open('../../sales/sales_order_entry.php?NewRestructured=' + Ext.getCmp('rpt_transno').getValue()+ '&opening_balance=1&paytype=' + Ext.getCmp('rpt_transtype').getValue());
					}
					
					submit_window_InterB.close();
				}
			}]
		},{
			/*xtype: 'fieldcontainer',
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
					window.open('../reports/prnt_cash_SI_serialized.php?SI_req=YES&SI_num=' + Ext.getCmp('rpt_transno').getValue());
					submit_window_InterB.close();
				}
			}]*/
		}]
	});
	var link_window = Ext.create('Ext.Window',{
		width 	: 430,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[link_form]
	});

	var AR_window = new Ext.create('Ext.Window',{
		width 	: 900,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		autoScroll:true,
		layout: 'fit',
		items:[{
			xtype: 'panel',
			id: 'mainpanel',
			items: [{
				xtype: 'textfield',
				id: 'trans_type',
				name: 'trans_type',
				fieldLabel: 'trans_type',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'panel',
				id: 'upanel',
				items: [{
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '0 0 0 0',
					items:[{
						xtype: 'textfield',
						fieldLabel: '<b>Customer Name </b>',
						id: 'custname',
						name: 'custname',
						margin: '2 0 0 2',
						width: 655,
						labelWidth: 117,
						readOnly: true,
						fieldStyle: 'font-weight: bold; color: #210a04;'
					},{
						xtype: 'numericfield',
						id: 'balance_amount',
					  	name: 'balance_amount',
						fieldLabel: '<b>Balance</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						labelWidth: 60,
						thousandSeparator: ',',
						margin: '2 0 0 0',
						minValue: 0,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{
					xtype: 'panel',
					id: 'mpanel',
					//width: 500,
					height: 613,
					layout: 'border',
					items: [{
						xtype: 'panel',
						id: 'west-region-container',
						title: 'Customer Information',
						region:'west',
						margin: '4 0 0 4',
						//width: 200,
						collapsible: true,   // make collapsible
						collapsed: true,
						border: true,
						items: [{
							xtype: 'fieldcontainer',
							layout: 'hbox',
							margin: '2 0 2 0',
							items:[{
								xtype: 'textfield',
								fieldLabel: '<b>Status </b>',
								id: 'Status',
								name: 'Status',
								labelWidth: 60,
								margin: '0 5 0 0',
								width: 190,
								//readOnly: true,
								fieldStyle: 'font-weight: bold; color: #210a04;'
							},{
								xtype: 'textfield',
								fieldLabel: '<b>Gender </b>',
								id: 'Gender',
								name: 'Gender',
								labelWidth: 60,
								margin: '0 5 0 0',
								width: 190,
								//readOnly: true,
								fieldStyle: 'font-weight: bold; color: #210a04;'
							}]
						},{
							xtype: 'fieldcontainer',
							layout: 'hbox',
							margin: '2 0 2 0',
							items:[{
								xtype: 'textfield',
								fieldLabel: '<b>Phone </b>',
								id: 'Phone',
								name: 'Phone',
								labelWidth: 60,
								margin: '0 5 0 0',
								width: 190,
								//readOnly: true,
								fieldStyle: 'font-weight: bold; color: #210a04;'
							},{
								xtype: 'textfield',
								fieldLabel: '<b>Birth Date </b>',
								id: 'age',
								name: 'age',
								labelWidth: 80,
								width: 190,
								//readOnly: true,
								fieldStyle: 'font-weight: bold; color: #210a04;'
							}]
						},{
							xtype: 'textfield',
							fieldLabel: '<b>E-mail </b>',
							id: 'email',
							name: 'email',
							labelWidth: 60,
							width: 385,
							//readOnly: true,
							margin: '2 0 2 0',
							fieldStyle: 'font-weight: bold; color: #210a04;'
						},{
							xtype: 'textfield',
							fieldLabel: '<b>Name of Father </b>',
							id: 'Fathername',
							name: 'Fathername',
							labelWidth: 115,
							margin: '2 0 2 0',
							width: 385,
							//readOnly: true,
							fieldStyle: 'font-weight: bold; color: #210a04;'
						},{
							xtype: 'textfield',
							fieldLabel: '<b>Name of Mother </b>',
							id: 'Fathermother',
							name: 'Fathermother',
							labelWidth: 115,
							margin: '2 0 2 0',
							width: 385,
							//readOnly: true,
							fieldStyle: 'font-weight: bold; color: #210a04;'							
						},{
							/*xtype: 'textareafield',
							fieldLabel: '<b>Complete Address </b>',
							id: 'completeaddress',
							name: 'completeaddress',
							labelAlign: 'top',
							//labelWidth: 130,
							margin: '5 5 2 4',
							width: 440,
							//readOnly: true,
							anchor    : '100%',
							grow: false,
							height: 5,
							fieldStyle: 'font-weight: bold; color: #210a04;'*/
							xtype: 'textfield',
							fieldLabel: '<b>Complete Address </b>',
							id: 'completeaddress',
							name: 'completeaddress',
							labelWidth: 130,
							margin: '2 0 2 0',
							width: 385,
							//readOnly: true,
							fieldStyle: 'font-weight: bold; color: #210a04;'
						},{
							xtype: 'textfield',
							fieldLabel: '<b>Collector </b>',
							id: 'collector',
							name: 'collector',
							labelWidth: 115,
							margin: '2 5 2 0',
							width: 385,
							//readOnly: true,
							fieldStyle: 'font-weight: bold; color: #210a04;'
						}]
					},{
						xtype: 'panel',
						id: 'cpanel',
						region: 'center',     // center region is required, no width/height specified
						margin: '4 4 0 0',
						items: [{
							xtype: 'fieldcontainer',
							layout: 'hbox',
							items:[{
								xtype: 'fieldcontainer',
								items:[{
									xtype: 'textfield',
									id: 'trans_no',
									name: 'trans_no',
									fieldLabel: 'FormID',
									allowBlank: false,
									readOnly: true,
									hidden: true
								},{
									xtype: 'textfield',
									id: 'debtor_no',
									name: 'debtor_no',
									fieldLabel: 'Customer ID',
									allowBlank: false,
									readOnly: true,
									hidden: true
								},{
									xtype: 'fieldcontainer',
									layout: 'hbox',
									margin: '2 0 2 0',
									items:[{
										xtype: 'textfield',
										fieldLabel: '<b>Invoice No. </b>',
										id: 'invoice_no',
										name: 'invoice_no',
										labelWidth: 110,
										margin: '0 5 0 0',
										//readOnly: true,
										fieldStyle: 'font-weight: bold; color: #210a04;'
									},{
										xtype : 'datefield',
										id	  : 'invoice_date',
										name  : 'invoice_date',
										fieldLabel : '<b>Invoice Date </b>',
										allowBlank: false,
										labelWidth: 110,
										format : 'm/d/Y',
										fieldStyle: 'font-weight: bold; color: #210a04;'
									}]
								},{
									xtype: 'fieldcontainer',
									layout: 'hbox',
									margin: '2 0 2 0',
									items:[{
										xtype: 'textfield',
										fieldLabel: '<b>Delivery No. </b>',
										id: 'delivery_no',
										name: 'delivery_no',
										labelWidth: 110,
										margin: '0 5 0 0',
										//readOnly: true,
										fieldStyle: 'font-weight: bold; color: #210a04;'
									},{
										xtype: 'combobox',
										fieldLabel: '<b>Invoice Type </b>',
										id: 'invoice_type',
										name: 'invoice_type',
										labelWidth: 110,
										//readOnly: true,
										fieldStyle: 'font-weight: bold; color: #210a04;'
									}]
								},{
									xtype: 'fieldcontainer',
									layout: 'hbox',
									margin: '2 0 2 0',
									items:[{
										xtype: 'textfield',
										fieldLabel: '<b>Category </b>',
										id: 'category',
										name: 'category',
										labelWidth: 110,
										margin: '0 5 0 0',
										//readOnly: true,
										fieldStyle: 'font-weight: bold; color: #210a04;'
									},{
										xtype: 'numberfield',
										fieldLabel: '<b>Term </b>',
										id: 'months_term',
										name: 'months_term',
										fieldStyle: 'text-align: right;',
										labelWidth: 110,
										maxLength: 5,
										minValue: 0,
										value: 0,
										fieldStyle: 'font-weight: bold; color: #210a04;'
									}]
								},{
									xtype: 'fieldcontainer',
									layout: 'hbox',
									margin: '2 0 2 0',
									items:[{
										xtype: 'numericfield',
										id: 'ar_amount',
										name: 'ar_amount',
										fieldLabel: '<b>AR Amount</b>',
										useThousandSeparator: true,
										decimalPrecision: 2,
										alwaysDisplayDecimals: true,
										allowNegative: false,
										labelWidth: 110,
										//width: 250,
										margin: '0 5 0 0',
										//readOnly: true,
										thousandSeparator: ',',
										minValue: 0,
										fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
									},{
										xtype: 'numericfield',
										id: 'rebate',
										name: 'rebate',
										fieldLabel: '<b>Rebate </b>',
										useThousandSeparator: true,
										decimalPrecision: 2,
										alwaysDisplayDecimals: true,
										allowNegative: false,
										labelWidth: 110,
										//width: 250,
										//readOnly: true,
										thousandSeparator: ',',
										minValue: 0,
										fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
									}]
								},{
									xtype: 'fieldcontainer',
									layout: 'hbox',
									margin: '2 0 2 0',
									items:[{
										xtype: 'numericfield',
										id: 'outs_ar_amount',
										name: 'outs_ar_amount',
										fieldLabel: '<b>Balance aftr DP </b>',
										useThousandSeparator: true,
										decimalPrecision: 2,
										alwaysDisplayDecimals: true,
										allowNegative: false,
										labelWidth: 110,
										//width: 250,
										margin: '0 5 0 0',
										//readOnly: true,
										thousandSeparator: ',',
										minValue: 0,
										fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
									},{
										xtype: 'numericfield',
										id: 'financing_rate',
										name: 'financing_rate',
										fieldLabel: '<b>Financing rate </b>',
										useThousandSeparator: false,
										decimalPrecision: 2,
										alwaysDisplayDecimals: true,
										allowNegative: false,
										//currencySymbol: '₱',
										labelWidth: 110,
										//width: 250,
										readOnly: true,
										thousandSeparator: ',',
										minValue: 0,
										fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
									}]
								},{
									xtype: 'fieldcontainer',
									layout: 'hbox',
									margin: '2 0 2 0',
									items:[{
										xtype: 'numericfield',
										id: 'lcp_amount',
										name: 'lcp_amount',
										fieldLabel: '<b>LCP Amount </b>',
										useThousandSeparator: true,
										decimalPrecision: 2,
										alwaysDisplayDecimals: true,
										allowNegative: false,
										labelWidth: 110,
										//width: 240,
										margin: '0 5 0 0',
										//readOnly: true,
										thousandSeparator: ',',
										minValue: 0,
										fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
									},{
										xtype: 'numericfield',
										id: 'dp_amount',
										name: 'dp_amount',
										fieldLabel: '<b>Down Payment </b>',
										useThousandSeparator: true,
										decimalPrecision: 2,
										alwaysDisplayDecimals: true,
										allowNegative: false,
										labelWidth: 110,
										//width: 250,
										//readOnly: true,
										thousandSeparator: ',',
										minValue: 0,
										fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
									}]
								},{
									xtype: 'fieldcontainer',
									layout: 'hbox',
									margin: '2 0 2 0',
									items:[{
										xtype: 'numericfield',
										id: 'total_amount',
										name: 'total_amount',
										fieldLabel: '<b>Unit Cost </b>',
										useThousandSeparator: true,
										decimalPrecision: 2,
										alwaysDisplayDecimals: true,
										allowNegative: false,
										labelWidth: 110,
										//width: 280,
										margin: '0 5 0 0',
										//readOnly: true,
										thousandSeparator: ',',
										minValue: 0,
										fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
									},{
										xtype: 'numericfield',
										id: 'amort_amount',
										name: 'amort_amount',
										fieldLabel: '<b>Amortization </b>',
										useThousandSeparator: true,
										decimalPrecision: 2,
										alwaysDisplayDecimals: true,
										allowNegative: false,
										labelWidth: 110,
										//width: 280,
										//readOnly: true,
										thousandSeparator: ',',
										minValue: 0,
										fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
									}]
								},{
									xtype: 'fieldcontainer',
									layout: 'hbox',
									margin: '2 0 2 0',
									items:[{
										xtype : 'datefield',
										id	  : 'firstdue_date',
										name  : 'firstdue_date',
										fieldLabel : '<b>First Due Date </b>',
										margin: '0 5 0 0',
										allowBlank: false,
										labelWidth: 110,
										format : 'm/d/Y',
										fieldStyle: 'font-weight: bold; color: #210a04;'
									},{
										xtype : 'datefield',
										id	  : 'maturity_date',
										name  : 'maturity_date',
										fieldLabel : '<b>Maturity Date </b>',
										labelWidth: 110,
										format : 'm/d/Y',
										fieldStyle: 'font-weight: bold; color: #210a04;'
									}]
								}]
							},{
								xtype: 'fieldcontainer',
								id: 'frepo',
								margin: '0 0 0 3',
								//bodyStyle: 'background-color: #fca678;',
								defaults: {labelAlign: 'top', margin: '0 0 0 0'},
								items:[{
									xtype: 'fieldcontainer',
									layout: 'hbox',
									defaults: {labelAlign: 'top', margin: '0 2 0 0', width: 130},
									items:[{
										xtype : 'datefield',
										id	  : 'repo_date',
										name  : 'repo_date',
										fieldLabel : '<b>Repo Date </b>',
										allowBlank: false,
										format : 'm/d/Y',
										fieldStyle: 'font-weight: bold; color: #c24f37;'
									},{
										xtype: 'numericfield',
										id: 'over_due',
										name: 'over_due',
										fieldLabel: '<b>Over Due</b>',
										useThousandSeparator: true,
										decimalPrecision: 2,
										alwaysDisplayDecimals: true,
										allowNegative: false,
										thousandSeparator: ',',
										minValue: 0,
										fieldStyle: 'font-weight: bold;color: #c24f37; text-align: right;'
									}]
								},{
									xtype: 'fieldcontainer',
									layout: 'hbox',
									defaults: {labelAlign: 'top', margin: '0 2 0 0', width: 130},
									items:[{
										xtype: 'numericfield',
										id: 'unrecovrd_cost',
										name: 'unrecovrd_cost',
										fieldLabel: '<b>Unrecovered cost </b>',
										useThousandSeparator: true,
										decimalPrecision: 2,
										alwaysDisplayDecimals: true,
										allowNegative: false,
										thousandSeparator: ',',
										minValue: 0,
										fieldStyle: 'font-weight: bold;color: #c24f37; text-align: right;'
									},{
										xtype: 'numericfield',
										id: 'past_due',
										name: 'past_due',
										fieldLabel: '<b>Past Due </b>',
										useThousandSeparator: true,
										decimalPrecision: 2,
										alwaysDisplayDecimals: true,
										allowNegative: false,
										thousandSeparator: ',',
										minValue: 0,
										fieldStyle: 'font-weight: bold;color: #c24f37; text-align: right;'
									}]
								},{
									xtype: 'fieldcontainer',
									layout: 'hbox',
									defaults: {labelAlign: 'top', margin: '0 2 0 0', width: 130},
									items:[{
										xtype: 'numericfield',
										id: 'total_unrecovrd',
										name: 'total_unrecovrd',
										fieldLabel: '<b>Total unrecovered </b>',
										useThousandSeparator: true,
										decimalPrecision: 2,
										alwaysDisplayDecimals: true,
										allowNegative: false,
										thousandSeparator: ',',
										minValue: 0,
										fieldStyle: 'font-weight: bold;color: #c24f37; text-align: right;'
									},{
										xtype: 'numericfield',
										id: 'addon_cost',
										name: 'addon_cost',
										fieldLabel: '<b>Add On </b>',
										useThousandSeparator: true,
										decimalPrecision: 2,
										alwaysDisplayDecimals: true,
										allowNegative: false,
										thousandSeparator: ',',
										minValue: 0,
										fieldStyle: 'font-weight: bold;color: #c24f37; text-align: right;'
									}]
								},{
									xtype: 	'textareafield',
									fieldLabel: 'Remarks ',
									id:	'remarks',
									name: 'remarks',
									labelAlign:	'top',
									width: 260,
									fieldStyle: 'font-weight: bold; color: #c24f37;'
								}]
							}]
						}]
					},{
						xtype: 'panel',
						//id: 'dpanel',
						region: 'south',     // position for region
						//split: true,         // enable resizing
						margin: '0 4 4 4',
						items: [{
							xtype: 'tabpanel',
							columnWidth: 0.78, //0.8
							scale: 'small',
							items:[{
								xtype:'gridpanel',
								id: 'LedgerGrid',
								title: 'Ledger Entries',
								anchor:'100%',
								autoScroll: true,
								loadMask: true,
								store: EntryLedgertore,
								columns: EntryLedgerHeader,
								height: 275,
								columnLines: true,
								features: [{
									ftype: 'summary'
								}],
								bbar : {
									xtype : 'pagingtoolbar',
									id: 'ledger_entries',
									hidden: false,
									store : EntryLedgertore,
									displayInfo : false,
									emptyMsg: "No records to display",
									doRefresh : function(){
										EntryLedgertore.load();
									}
								}
							},{
								xtype:'gridpanel',
								id: 'AmortLedgerGrid',
								title: 'Amortization Ledger',
								anchor:'100%',
								autoScroll: true,
								loadMask: true,
								store: AmortLedgertore,
								columns: AmortLedgerHeader,
								height: 275,
								columnLines: true,
								features: [{
									ftype: 'summary'
								}],
								bbar : {
									xtype : 'pagingtoolbar',
									id: 'amort_ledger',
									hidden: false,
									store : AmortLedgertore,
									displayInfo : false,
									emptyMsg: "No records to display",
									doRefresh : function(){
										AmortLedgertore.load();
									},
									items:[{
										xtype: 'button',
										text : '<b>Open in new tab</b>',
										tooltip: 'Click to view amortization report',
										margin: '0 0 0 0',
										icon: '../../js/ext4/examples/shared/icons/application_side_expand.png',
										handler : function() {
											window.open('../../reports/installment_inquiry.php?&trans_no=' + Ext.getCmp('trans_no').getValue() + '&trans_type='+Ext.getCmp('trans_type').getValue());
										}
									}]
								}
							},{
								xtype:'gridpanel',
								id: 'AmortSchedGrid',
								title: 'Amortization Schedule',
								anchor:'100%',
								autoScroll: true,
								loadMask: true,
								store: AmortSchedstore,
								columns: AmortSchedcolModel,
								height: 275,
								columnLines: true,
								bbar : {
									xtype : 'pagingtoolbar',
									id: 'AmortSched',
									hidden: false,
									store : AmortSchedstore,
									displayInfo : false,
									emptyMsg: "No records to display",
									doRefresh : function(){
										AmortSchedstore.load();
									},
									items:[{
										xtype: 'button',
										text : '<b>Open in new tab</b>',
										//tooltip: 'Click to search customer information',
										margin: '0 0 0 0',
										icon: '../../js/ext4/examples/shared/icons/application_side_expand.png',
										handler : function() {
											window.open('../../lending/inquiry/deptor_amortization.php?invoice_no='+Ext.getCmp('trans_no').getValue() +'&debtor_no='+Ext.getCmp('debtor_no').getValue() +'&Type='+ Ext.getCmp('trans_type').getValue());
										}
									}]
								}
							},{
								xtype:'gridpanel',
								id: 'EntriesGrid',
								title: 'A/R Entries',
								anchor:'100%',
								autoScroll: true,
								loadMask: true,
								store: Entriestore,
								columns: EntriescolModel,
								height: 275,
								columnLines: true,
								features: [{
									ftype: 'summary'
								}],
								bbar : {
									xtype : 'pagingtoolbar',
									id: 'AREntries',
									hidden: false,
									store : Entriestore,
									displayInfo : false,
									emptyMsg: "No records to display",
									doRefresh : function(){
										Entriestore.load();
									},
									items:[{
										xtype: 'button',
										text : '<b>Open in new tab</b>',
										//tooltip: 'Click to search customer information',
										margin: '0 0 0 0',
										icon: '../../js/ext4/examples/shared/icons/application_side_expand.png',
										handler : function() {
											window.open('../../gl/view/gl_trans_view.php?type_id='+ Ext.getCmp('trans_type').getValue() +'&trans_no='+ Ext.getCmp('trans_no').getValue());
										}
									}]
								}
							},{
								xtype:'gridpanel',
								id: 'DeferredGrid',
								title: 'Deferred Ledger',
								anchor:'100%',
								autoScroll: true,
								loadMask: true,
								store: deferdtore,
								columns: EntryLedgerHeader,
								height: 275,
								columnLines: true,
								features: [{
									ftype: 'summary'
								}],
								bbar : {
									xtype : 'pagingtoolbar',
									id: 'Deferred',
									hidden: false,
									store : deferdtore,
									displayInfo : false,
									emptyMsg: "No records to display",
									doRefresh : function(){
										deferdtore.load();
									}
								}
							},{
								xtype:'gridpanel',
								id: 'ItemGrid',
								anchor:'100%',
								autoScroll: true,
								title: 'Item Details',
								icon: '../../js/ext4/examples/shared/icons/lorry_flatbed.png',
								loadMask: true,
								store:	SIitemStore,
								columns: Item_view,
								plugins: [cellEditing],
								height: 275,
								columnLines: true,
								bbar : {
									xtype : 'pagingtoolbar',
									id: 'ARItems',
									hidden: false,
									store : SIitemStore,
									displayInfo : false,
									emptyMsg: "No records to display",
									doRefresh : function(){
										SIitemStore.load();
									}
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
													if(record.get('type') != "XX"){
														Ext.fly(cells[j]).setStyle('background-color', "#fca678");
													}
												}
											}
										}
									}
								}
							},{
								xtype: 'panel',
								title: 'More Info...',
								iconCls: 'x-fa fa-html5',
								height: 275,
								width: 400,
								bodyPadding: 12,
								items: [{
									xtype: 'fieldcontainer',
									layout: 'hbox',
									margin: '2 0 2 0',
									items:[{
										xtype: 'textfield',
										fieldLabel: '<b>Module type </b>',
										id: 'type_module',
										name: 'type_module',
										labelWidth: 120,
										margin: '2 5 2 0',
										width: 385,
										fieldStyle: 'font-weight: bold; color: #210a04;'
									},{
										xtype: 'textfield',
										fieldLabel: '<b>Term mode date </b>',
										id: 'term_mod_date',
										name: 'term_mod_date',
										labelWidth: 120,
										margin: '2 5 2 0',
										width: 385,
										fieldStyle: 'font-weight: bold; color: #210a04;'
									}]
								}, {
									xtype: 'fieldcontainer',
									layout: 'hbox',
									margin: '2 0 2 0',
									items:[{
										xtype: 'textfield',
										fieldLabel: '<b>Amort diff. </b>',
										id: 'amort_diff',
										name: 'amort_diff',
										labelWidth: 120,
										margin: '2 5 2 0',
										width: 385,
										fieldStyle: 'font-weight: bold; color: #210a04;'
									},{
										xtype: 'textfield',
										fieldLabel: '<b>Months paid </b>',
										id: 'months_paid',
										name: 'months_paid',
										labelWidth: 120,
										margin: '2 5 2 0',
										width: 385,
										fieldStyle: 'font-weight: bold; color: #210a04;'
									}]
								},{
									xtype: 'fieldcontainer',
									layout: 'hbox',
									margin: '2 0 2 0',
									items:[{
										xtype: 'textfield',
										fieldLabel: '<b>Amort delay </b>',
										id: 'amort_delay',
										name: 'amort_delay',
										labelWidth: 120,
										margin: '2 5 2 0',
										width: 385,
										fieldStyle: 'font-weight: bold; color: #210a04;'
									},{
										xtype: 'textfield',
										fieldLabel: '<b>Adjustment rate </b>',
										id: 'adj_rate',
										name: 'adj_rate',
										labelWidth: 120,
										margin: '2 5 2 0',
										width: 385,
										fieldStyle: 'font-weight: bold; color: #210a04;'
									}]
								},{
									xtype: 'fieldcontainer',
									layout: 'hbox',
									margin: '2 0 2 0',
									items:[{
										xtype: 'textfield',
										fieldLabel: '<b>opportunity cost </b>',
										id: 'oppnity_cost',
										name: 'oppnity_cost',
										labelWidth: 120,
										margin: '2 5 2 0',
										width: 385,
										fieldStyle: 'font-weight: bold; color: #210a04;'
									},{
										xtype: 'textfield',
										fieldLabel: '<b>Amount to paid </b>',
										id: 'amount_to_paid',
										name: 'amount_to_paid',
										labelWidth: 120,
										margin: '2 5 2 0',
										width: 385,
										fieldStyle: 'font-weight: bold; color: #210a04;'
									}]
								},{
									xtype: 'textfield',
									fieldLabel: '<b>Profit margin </b>',
									id: 'profit_margin',
									name: 'profit_margin',
									labelWidth: 120,
									margin: '2 5 2 0',
									width: 385,
									fieldStyle: 'font-weight: bold; color: #210a04;'
								},{
									xtype: 'textfield',
									fieldLabel: '<b>Remarks</b>',
									id: 'Termremarks',
									name: 'Termremarks',
									labelWidth: 120,
									margin: '2 5 2 0',
									width: 775,
									fieldStyle: 'font-weight: bold; color: #210a04;'
								}]
							}]
						}]
					}]
				}]
			}]
		}]
	});
	//for ar installment column model
	var ColumnModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Id</b>',dataIndex:'plcyd_id',hidden: true},
		{header:'<b>Date</b>', dataIndex:'tran_date', sortable:true, width:85, renderer: Ext.util.Format.dateRenderer('m-d-Y')},
		{header:'<b>Reference No.</b>', dataIndex:'reference', sortable:true, width:170},
		{header:'<b>Module Type</b>', dataIndex:'type_module', sortable:true, width:130,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';

				if (value === 'Sales Invoice Opening Balances'){
					metaData.style="font-weight: bold; color: #117a65";
				}else if(value === 'Sales Invoice Installment'){
					metaData.style="font-weight: bold; color: #229954";
				}else if(value === 'Sales Invoice Term Modification'){
					metaData.style="font-weight: bold; color:#d35400";
				}else if(value === 'Sales Invoice Repossessed'){
					metaData.style="font-weight: bold; color:#c0392b";
				}else if(value === 'A/R Installment Lending'){
					metaData.style="font-weight: bold; color: #f5a104";
				}else{
					metaData.style="color: #5c09ec";
				}
				return "<b>" + value + "</b>";
			}
		},
		{header:'<b>Customer Name</b>', dataIndex:'debtor_name', sortable:true, width:180,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				metaData.style="font-weight: bold;";
				return value;
			}
		},
		{header:'<b>Category</b>', dataIndex:'category_desc', sortable:true, width:110},
		{header:'<b>Term</b>', dataIndex:'months_term', sortable:true, width:59},
		{header:'<b>Maturity</b>', dataIndex:'maturity_date', sortable:true, width:90, renderer: Ext.util.Format.dateRenderer('m-d-Y')},
		{header:'<b>AR Amount</b>', dataIndex:'ar_amount', sortable:true, width:100,
			renderer: Ext.util.Format.Currency = function(value){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>Amortization</b>', dataIndex:'amortn_amount', sortable:true, width:115,
			renderer: Ext.util.Format.Currency = function(value){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>Balance</b>', dataIndex:'ar_balance', sortable:true, width:100,
			renderer: function(value, metaData){
				if (value == 0){
					metaData.style="font-weight: bold; color: #2980b9";
				}else{
					metaData.style="font-weight: bold; color:#229954";
				}
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>Status</b>', dataIndex:'pay_status', sortable:true, width:70,
			renderer:function(value,metaData){
				if (value === 'unpaid'){
					metaData.style="color: #2980b9";
				}else if(value === 'part paid'){
					metaData.style="color:#1e8449 ";
				}else{
					metaData.style="color:#229954";
				}
				return "<b>" + value + "</b>";
			}
		},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:107,
			items:[{
				icon: '../../js/ext4/examples/shared/icons/table_go.png',
				tooltip: 'View details',
				handler: function(grid, rowIndex, colIndex) {
					var records = ARInstallQstore.getAt(rowIndex);
					
					sumdebit = 0;
					sumcredit = 0;

					Ext.getCmp('trans_no').setValue(records.get('trans_no'));
					Ext.getCmp('trans_type').setValue(records.get('type'));
					Ext.getCmp('invoice_no').setValue(records.get('invoice_ref_no'));
					Ext.getCmp('invoice_date').setValue(records.get('invoice_date'));
					Ext.getCmp('delivery_no').setValue(records.get('delivery_ref_no'));
					Ext.getCmp('invoice_type').setValue(records.get('invoice_type'));
					Ext.getCmp('category').setValue(records.get('category_desc'));
					Ext.getCmp('months_term').setValue(records.get('months_term'));
					Ext.getCmp('rebate').setValue(records.get('rebate'));
					Ext.getCmp('financing_rate').setValue(records.get('fin_rate'));
					Ext.getCmp('dp_amount').setValue(records.get('dp_amount'));
					Ext.getCmp('firstdue_date').setValue(records.get('firstdue_date'));
					Ext.getCmp('maturity_date').setValue(records.get('maturity_date'));
					Ext.getCmp('lcp_amount').setValue(records.get('lcp_amount'));
					Ext.getCmp('ar_amount').setValue(records.get('ar_amount'));
					Ext.getCmp('outs_ar_amount').setValue(records.get('outs_ar_amount'));
					Ext.getCmp('total_amount').setValue(records.get('total_amount'));
					Ext.getCmp('amort_amount').setValue(records.get('amortn_amount'));
					Ext.getCmp('custname').setValue(records.get('debtor_ref') + ' - ' + records.get('debtor_name'))
					Ext.getCmp('debtor_no').setValue(records.get('debtor_no'));
					Ext.getCmp('Status').setValue(records.get('Status'));
					Ext.getCmp('Gender').setValue(records.get('gender'));
					Ext.getCmp('age').setValue(records.get('age'));
					Ext.getCmp('Phone').setValue(records.get('phone'));
					Ext.getCmp('email').setValue(records.get('email'));
					Ext.getCmp('Fathername').setValue(records.get('name_father'));
					Ext.getCmp('Fathermother').setValue(records.get('name_mother'));
					Ext.getCmp('completeaddress').setValue(records.get('address'));
					Ext.getCmp('collector').setValue(records.get('collector'));
					Ext.getCmp('balance_amount').setValue(records.get('ar_balance'));
					Ext.getCmp('ARINQRYGRID').getSelectionModel().select(rowIndex);

					Ext.getCmp('type_module').setValue(records.get('type_module'));
					Ext.getCmp('term_mod_date').setValue(records.get('term_mod_date'));
					Ext.getCmp('amort_diff').setValue(records.get('amort_diff'));
					Ext.getCmp('months_paid').setValue(records.get('months_paid'));
					Ext.getCmp('amort_delay').setValue(records.get('amort_delay'));
					Ext.getCmp('adj_rate').setValue(records.get('adj_rate'));
					Ext.getCmp('oppnity_cost').setValue(records.get('oppnity_cost'));
					Ext.getCmp('amount_to_paid').setValue(records.get('amount_to_be_paid'));
					Ext.getCmp('Termremarks').setValue(records.get('Termremarks'));
					Ext.getCmp('profit_margin').setValue(records.get('profit_margin'));
					
					EntryLedgertore.proxy.extraParams = {trans_no: records.get('trans_no'), type: records.get('type')};
					EntryLedgertore.load();
					
					AmortLedgertore.proxy.extraParams = {trans_no: records.get('trans_no'), type: records.get('type')};
					AmortLedgertore.load();

					AmortSchedstore.proxy.extraParams = {trans_no: records.get('trans_no'), trans_type: records.get('type'), debtor_no: records.get('debtor_no')};
					AmortSchedstore.load();

					Entriestore.proxy.extraParams = {trans_no: records.get('trans_no'), trans_type: records.get('type')};
					Entriestore.load();

					SIitemStore.proxy.extraParams = {transNo: records.get('trans_no'), transtype: records.get('type'), invoice_ref: records.get('invoice_ref_no'), };
					SIitemStore.load();

					deferdtore.proxy.extraParams = {trans_no: records.get('trans_no'), type: records.get('type')};
					deferdtore.load();

					if(records.get('module_type') == "REPO" || records.get('module_type') == "TEMP-REPO"){
						Ext.getCmp('frepo').setVisible(true);
						Ext.getCmp('repo_date').setValue(records.get('repo_date'));
						Ext.getCmp('over_due').setValue(records.get('over_due'));
						Ext.getCmp('unrecovrd_cost').setValue(records.get('unrecovered'));
						Ext.getCmp('past_due').setValue(records.get('past_due'));
						Ext.getCmp('total_unrecovrd').setValue(records.get('total_unrecovrd'));
						Ext.getCmp('addon_cost').setValue(records.get('addon_amount'));
						Ext.getCmp('remarks').setValue(records.get('repo_remark'));

						AR_window.setTitle('A/R Installment Inquiry - Transaction No. '+ records.get('trans_no') + ' | Reference No. : ' + records.get('reference') + ' >> ' + records.get('module_type'));
					}else{
						Ext.getCmp('frepo').setVisible(false);

						AR_window.setTitle('A/R Installment Inquiry - Transaction No. '+ records.get('trans_no') + ' | Reference No. : ' + records.get('reference'));
					}
					
					AR_window.show();
					AR_window.setPosition(200,23);
				}
			},'-',{
				icon: '../../js/ext4/examples/shared/icons/print-preview-icon.png',
				tooltip: 'Installment ledger report',
				handler: function(grid, rowIndex, colIndex) {
					var records = ARInstallQstore.getAt(rowIndex);
					//window.open('../../reports/installment_inquiry.php?&trans_no=' + records.get('trans_no') + '&trans_type='+records.get('type'));

					//Rober -Add
					var win = new Ext.Window({
						autoLoad:{
							url:'../../reports/installment_inquiry.php?&trans_no=' + records.get('trans_no') + '&trans_type='+records.get('type'),
							discardUrl: true,
							nocache: true,
							text:"Loading...",
							timeout:60,
							scripts: false
						},
						width:'80%',
						height:'95%',
						title:'Preview Print',
						modal: true
					})
					
					var iframeid = win.getId() + '_iframe';


			        var iframe = {
			            id:iframeid,
			            tag:'iframe',
			            src:'../../reports/installment_inquiry.php?&trans_no=' + records.get('trans_no') + '&trans_type='+records.get('type'),
			            width:'100%',
			            height:'100%',
			            frameborder:0
			        }
					win.show();
					Ext.DomHelper.insertFirst(win.body, iframe)
					//---//
				}
			},'-',{
				icon: '../../js/ext4/examples/shared/icons/book_next.png',
				id: 'changeterm',
				//hidden: true,
				tooltip: 'Change term',
				handler: function(grid, rowIndex, colIndex) {
					var records = ARInstallQstore.getAt(rowIndex);
					//window.open('../../sales/sales_order_entry.php?NewChangeTerm=' + records.get('trans_no') + '&opening_balance=1&paytype=' + records.get('payment_loc'));
					
					Ext.getCmp('rpt_transno').setValue(records.get('trans_no'));
					Ext.getCmp('rpt_transtype').setValue(records.get('payment_loc'));
					Ext.getCmp('fldstatus').setValue(records.get('restructured_status'));

					link_window.setTitle('Selection List');
					link_window.show();
					link_window.setPosition(500,150);
				},
				isDisabled: function(view, rowIndex, colIndex, item, record) {
				//hidden : function(view, record) {
					// Returns true if 'editable' is false (, null, or undefined)
					if(record.get('payment_loc') == 'Lending'){
						return false;
					}else{
						return true;
					}
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
		editable: false,
		listeners: {
			select: function(combo, record, index) {
				/*ARInstallQstore.load({
					params: { InvType: combo.getValue(),  status: Ext.getCmp('fstatus').getValue()}
				});*/
				ARInstallQstore.proxy.extraParams = {InvType: combo.getValue(), showZeroB: showall, query: Ext.getCmp('search').getValue(), showlend: showlend};
				ARInstallQstore.load();
			},
			afterrender: function(combo) {
				Ext.getCmp("invtype").setValue("new");
				ARInstallQstore.proxy.extraParams = {InvType: combo.getValue(), showZeroB: showall, query: Ext.getCmp('search').getValue(), showlend: showlend};
				ARInstallQstore.load();
			}
		}
	}, {
		xtype: 'searchfield',
		id:'search',
		name:'search',
		fieldLabel: '<b>Search</b>',
		labelWidth: 50,
		width: 350,
		emptyText: "Search by customer name or invoice no",
		scale: 'small',
		store: ARInstallQstore,
		listeners: {
			change: function(field) {
				if(field.getValue() != ""){
					ARInstallQstore.proxy.extraParams = {InvType: Ext.getCmp('invtype').getValue(), showZeroB: showall, query: field.getValue(), showlend: showlend};
					ARInstallQstore.load();
				}else{
					ARInstallQstore.proxy.extraParams = {InvType: Ext.getCmp('invtype').getValue(), showZeroB: showall, query: field.getValue(), showlend: showlend};
					ARInstallQstore.load();
				}
			}
		}
	}, '->' ,{
		xtype:'splitbutton',
		text: '<b>Reports</b>',
		tooltip: 'list of reports',
		icon: '../../js/ext4/examples/shared/icons/cog_edit.png',
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
				},
				items:[{
					xtype: 'checkbox',
					id: 'instlstatus',
					name: 'instlstatus',
					boxLabel: 'Show 0 Balance Accounts',
					listeners: {
						change: function(column, rowIdx, checked, eOpts){
							var search = Ext.getCmp('search');

							if(checked){
								showall = false;
							}else{
								showall = true;
							}

							if(search.getValue() != ""){
								ARInstallQstore.proxy.extraParams = {InvType: Ext.getCmp('invtype').getValue(), showZeroB: showall, query: search.getValue(), showlend: showlend};
							}else{
								ARInstallQstore.proxy.extraParams = {InvType: Ext.getCmp('invtype').getValue(), showZeroB: showall, showlend: showlend};
							}
							ARInstallQstore.load();
						}
					}
				},{
					xtype: 'checkbox',
					id: 'showlending',
					name: 'showlending',
					boxLabel: 'Show lending accounts',
					listeners: {
						change: function(field, rowIdx, checked, eOpts) {
							var search = Ext.getCmp('search');

							if(checked){
								showlend = false;
							}else{
								showlend = true;
							}

							if(search.getValue() != ""){
								ARInstallQstore.proxy.extraParams = {InvType: Ext.getCmp('invtype').getValue(), showZeroB: showall, query: search.getValue(), showlend: showlend};
							}else{
								ARInstallQstore.proxy.extraParams = {InvType: Ext.getCmp('invtype').getValue(), showZeroB: showall, showlend: showlend};
							}
							ARInstallQstore.load();
						}
					}
				}]
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
								if(record.get('module_type') == "REPO"){
									Ext.fly(cells[j]).setStyle('background-color', "#fca678");
								}else if(record.get('module_type') == "TEMP-REPO"){
									Ext.fly(cells[j]).setStyle('background-color', "#f7b86d");
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
