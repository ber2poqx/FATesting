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

	////define model for policy installment
    Ext.define('ARInstlmntmodel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'ar_id', mapping:'ar_id'},
			{name:'invoice_date', mapping:'invoice_date'},
			{name:'invoice_no', mapping:'invoice_no'},
			{name:'customer_code', mapping:'customer_code'},
			{name:'customer_name', mapping:'customer_name'},
			{name:'branch_code', mapping:'branch_code'},
			{name:'delivery_no', mapping:'delivery_no'},
			{name:'invoice_type', mapping:'invoice_type'},
			{name:'installplcy_id', mapping:'installplcy_id'},
			{name:'months_term', mapping:'months_term'},
			{name:'rebate', mapping:'rebate'},
			{name:'fin_rate', mapping:'fin_rate'},
			{name:'firstdue_date', mapping:'firstdue_date'},
			{name:'maturity_date', mapping:'maturity_date'},
			{name:'outs_ar_amount', mapping:'outs_ar_amount'},
			{name:'ar_amount', mapping:'ar_amount'},
			{name:'lcp_amount', mapping:'lcp_amount'},
			{name:'dp_amount', mapping:'dp_amount'},
			{name:'amortn_amount', mapping:'amortn_amount'},
			{name:'total_amount', mapping:'total_amount'},
			{name:'category_id', mapping:'category_id'},
			{name:'category_desc', mapping:'category_desc'},
			{name:'comments', mapping:'comments'},
			//{name:'prepared_by', mapping:'prepared_by'},
			{name:'approved_by', mapping:'approved_by'},
			{name:'approved_date', mapping:'approved_date'},
			{name:'status', mapping:'status'},
			{name:'trans_no', mapping:'trans_no'},
			{name:'debtor_no', mapping:'debtor_no'},
			{name:'debtor_ref', mapping:'debtor_ref'},
			{name:'reference', mapping:'reference'},
			{name:'gpm', mapping:'gpm'}
		]
	});
    Ext.define('Itemmodel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'invoice_no', mapping:'invoice_no'},
			{name:'stock_id', mapping:'stock_id'},
			{name:'qty', mapping:'qty', type: 'float'},
			{name:'unit_price', mapping:'unit_price'},
			{name:'total_price', mapping:'total_price'},
			{name:'serial_no', mapping:'serial_no'},
			{name:'chasis_no', mapping:'chasis_no'}
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
    Ext.define('EntriesModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'entryid', mapping:'entryid'},
			{name:'InvoiceNo', mapping:'InvoiceNo'},
			{name:'gltag', mapping:'gltag'},
			{name:'GlCaption', mapping:'GlCaption'},
			{name:'TransType', mapping:'TransType'},
			{name:'TransDate', mapping:'TransDate'},
			{name:'PeriodNo', mapping:'PeriodNo'},
			{name:'Period', mapping:'Period'},
			{name:'AccountNo', mapping:'AccountNo'},
			{name:'AccountName', mapping:'AccountName'},
			{name:'Narrative', mapping:'Narrative'},
			{name:'Description', mapping:'Description'},
			{name:'Debit', mapping:'Debit', type: 'float'},
			{name:'Credit', mapping:'Credit', type: 'float'},
			{name:'Posted', mapping:'Posted'},
			{name:'LastdateInPeriod', mapping:'LastdateInPeriod'}
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
	//------------------------------------: stores :----------------------------------------
	var ARInstallQstore = Ext.create('Ext.data.Store', {
		model: 'ARInstlmntmodel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_invcincome=00',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'invoice_date',
			direction : 'DESC'
		}]
	});
	var Itemstore = Ext.create('Ext.data.Store', {
		model: 'Itemmodel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_aritem=00',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		groupField: 'invoice_no',
		sorters : [{
			property : 'id',
			direction : 'ASC'
		}]
	});
	var Customerstore = Ext.create('Ext.data.Store', {
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
	var InvTypeStore = Ext.create('Ext.data.Store',{
		fields: ['id','name'],
		autoLoad: true,
		data : 	[
			{"id":"x","name":"All"},
            {"id":"new","name":"Brand new"},
            {'id':'repo','name':'Repo'}
        ]
	});
	var fstatusStore = Ext.create('Ext.data.Store',{
		fields: ['id','name'],
		autoLoad: true,
		data : 	[
            {"id":"Open","name":"Open"},
            {'id':'Approved','name':'Approved'},
			{'id':'Disapproved','name':'Disapproved'}
        ]
	});
    var EntriesStore = Ext.create('Ext.data.Store', {
        model: 'EntriesModel',
		//pageSize: GridItemOnTab, // items per page
        proxy: {
            url: '?getSIEntries=1',
            type: 'ajax',
            reader: {
                type: 'json',
                root: 'result',
                totalProperty  : 'total'
            }
		},
		simpleSortMode : true,
		groupField: 'gltag',
		sorters : [{
			property : 'GLID',
			direction : 'ASC'
		}]
    });

	var ItemcolModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Id</b>',dataIndex:'plcyd_id',hidden: true},
		{header:'<b>Stock id</b>', dataIndex:'stock_id', sortable:true, width:130,
			summaryRenderer: function(value, summaryData, dataIndex) {
				return '<span style="color:blue;font-weight:bold"> Grand Total: </span>';
			}
		},
		{header:'<b>Qty</b>', dataIndex:'qty', sortable:true, width:50,
			summaryType: 'sum',
			summaryRenderer: function(value, summaryData, record, dataIndex) {
				return '<span style="color:green;font-weight:bold">' + Ext.util.Format.currency(value,' ', 0) + '</span>';
			}
		},
		{header:'<b>Unit Price</b>', dataIndex:'unit_price', sortable:true, width:100,
			renderer: Ext.util.Format.Currency = function(value){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>Sub Total</b>', dataIndex:'total_price', sortable:true, width:100,
			renderer: Ext.util.Format.Currency = function(value){
				return Ext.util.Format.number(value, '0,000.00');
			},
			summaryType: 'sum',
            summaryRenderer: function(value, summaryData, record, dataIndex) {
				return '<span style="color:green;font-weight:bold">' + Ext.util.Format.number(value, '0,000.00') + '</span>';
			}
		},
		{header:'<b>Serial/Engine No.</b>', dataIndex:'serial_no', sortable:true, width:180},
		{header:'<b>Chasis No.</b>', dataIndex:'chasis_no', sortable:true, width:180}
	];
	var EntriescolModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Id</b>',dataIndex:'plcyd_id',hidden: true},
		{header:'<b>Stock id</b>', dataIndex:'stock_id', sortable:true, width:130,
			summaryRenderer: function(value, summaryData, dataIndex) {
				return '<span style="color:blue;font-weight:bold"> Grand Total: </span>';
			}
		},
		{header:'<b>Qty</b>', dataIndex:'qty', sortable:true, width:50,
			summaryType: 'sum',
			summaryRenderer: function(value, summaryData, record, dataIndex) {
				return '<span style="color:green;font-weight:bold">' + Ext.util.Format.currency(value,' ', 0) + '</span>';
			}
		},
		{header:'<b>Unit Price</b>', dataIndex:'unit_price', sortable:true, width:100,
			renderer: Ext.util.Format.Currency = function(value){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>Sub Total</b>', dataIndex:'total_price', sortable:true, width:100,
			renderer: Ext.util.Format.Currency = function(value){
				return Ext.util.Format.number(value, '0,000.00');
			},
			summaryType: 'sum',
            summaryRenderer: function(value, summaryData, record, dataIndex) {
				return '<span style="color:green;font-weight:bold">' + Ext.util.Format.number(value, '0,000.00') + '</span>';
			}
		},
		{header:'<b>Serial/Engine No.</b>', dataIndex:'serial_no', sortable:true, width:180},
		{header:'<b>Chasis No.</b>', dataIndex:'chasis_no', sortable:true, width:180}
	];
	//---------------------------------------------------------------------------------------
	//view detailed records form
	var submit_form = Ext.create('Ext.form.Panel', {
		id: 'submit_form',
		model: 'ARInstlmntmodel',
		frame: false,
		defaultType: 'field',
		//layout: 'column',
		items: [{
			xtype: 'textfield',
			id: 'id',
			name: 'id',
			fieldLabel: 'FormID',
			margin: '2 0 2 5',
			allowBlank: false,
			readOnly: true,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'policy_id',
			name: 'policy_id',
			fieldLabel: 'policy id',
			margin: '2 0 2 5',
			allowBlank: false,
			readOnly: true,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'category_id',
			name: 'category_id',
			fieldLabel: 'category id',
			margin: '2 0 2 5',
			allowBlank: false,
			readOnly: true,
			hidden: true
		},{
			xtype: 'combobox',
			fieldLabel: '<b>Invoice Type </b>',
			id: 'invoice_type',
			name: 'invoice_type',
			labelWidth: 115,
			width: 355,
			readOnly: true,
			hidden: true,
			fieldStyle: 'font-weight: bold; color: #210a04;'
		},{
			xtype: 'textfield',
			fieldLabel: '<b>Reference No. </b>',
			id: 'ref_no',
			name: 'ref_no',
			labelWidth: 113,
			width: 320,
			margin: '0 42 0 0',
			allowBlank: false,
			readOnly: true,
			hidden: true,
			fieldStyle: 'font-weight: bold; color: #210a04;'
		},{
			xtype: 'textfield',
			fieldLabel: '<b>Name </b>',
			id: 'name',
			name: 'name',
			labelWidth: 113,
			width: 320,
			margin: '0 42 0 0',
			allowBlank: false,
			readOnly: true,
			hidden: true,
			fieldStyle: 'font-weight: bold; color: #210a04;'
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'textfield',
				fieldLabel: '<b>DES Customer </b>',
				id: 'descustcode',
				name: 'descustcode',
				labelWidth: 110,
				width: 245,
				margin: '0 0 0 0',
				allowBlank: false,
				readOnly: true,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype : 'textfield',
				id	  : 'descustname',
				name  : 'descustname',
				allowBlank: false,
				width: 297,
				readOnly: true,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'button',
				text : 'Search',
				tooltip: 'Click to search customer information',
				margin: '0 0 0 0',
				icon: '../../js/ext4/examples/shared/icons/vcard.png',
				handler : function() {
					window.open('../../sales/inquiry/customers_list.php?popup=1&client_id=customer_id');
				}
			},{
				xtype: 'button',
				text : 'Add Customer',
				id	 : 'AutoAddCustomer',
				tooltip: 'Click to add this new customer',
				margin: '0 0 0 1',
				icon: '../../js/ext4/examples/shared/icons/user_add.png',
				handler : function() {
					Ext.MessageBox.confirm('Confirmation:', 'Are you sure you wish to add this customer?', function (btn, text) {
						if (btn == 'yes') {
							Ext.Ajax.request({
								url : '?getCustomerAutoSetup='+Ext.getCmp('descustcode').getValue()+'&branchcode='+Ext.getCmp('branch_code').getValue()+'&aliename='+Ext.getCmp('descustname').getValue(),
								async:false,
								success: function (response) {
									var result = Ext.JSON.decode(response.responseText);
									Customerstore.load();
									if (result.success == "true") {
										Ext.Msg.show({
											title: 'AutoCreatedCustomer: Success!',
											msg: '<font color="green">' + result.message + '</font>',
											buttons: Ext.Msg.OK,
											icon: Ext.MessageBox.INFORMATION
										});
									}
									else {
										Ext.Msg.show({
											title: 'AutoCreatedCustomer: Failed!',
											msg: result.message,
											buttons: Ext.Msg.OK,
											icon: Ext.MessageBox.ERROR
										});
									}
								},
								failure: function () {
									Ext.Msg.show({
										title: 'AutoCreatedCustomer: Failed!',
										msg: result.message,
										buttons: Ext.Msg.OK,
										icon: Ext.MessageBox.ERROR
									});
								}
							});
						}
					});
				}
			}]
		},{
			xtype: 'panel',
			id: 'mpanel',
			//width: 500,
			height: 400,
			layout: 'border',
			items: [{
				xtype: 'panel',
				title: 'DES Computation',
				region:'west',
				margin: '4 0 0 4',
				//width: 200,
				collapsible: true,   // make collapsible
				id: 'west-region-container',
				border: true,
				//layout: 'fit',
				items: [{
					xtype: 'numericfield',
					id: 'deslcp_amount',
					name: 'deslcp_amount',
					fieldLabel: '<b>LCP Amount </b>',
					allowBlank:false,
					useThousandSeparator: true,
					decimalPrecision: 2,
					alwaysDisplayDecimals: true,
					allowNegative: false,
					//currencySymbol: '₱',
					labelWidth: 117,
					width: 240,
					margin: '2 5 2 0',
					readOnly: true,
					thousandSeparator: ',',
					fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
				},{
					xtype: 'numericfield',
					id: 'destotal_amount',
					name: 'destotal_amount',
					fieldLabel: '<b>Total Amount </b>',
					allowBlank:false,
					useThousandSeparator: true,
					decimalPrecision: 2,
					alwaysDisplayDecimals: true,
					allowNegative: false,
					//currencySymbol: '₱',
					labelWidth: 117,
					width: 240,
					margin: '2 5 2 0',
					readOnly: true,
					thousandSeparator: ',',
					minValue: 0,
					fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
				},{
					xtype: 'numericfield',
					id: 'desar_amount',
					name: 'desar_amount',
					fieldLabel: '<b>AR Amount</b>',
					allowBlank:false,
					useThousandSeparator: true,
					decimalPrecision: 2,
					alwaysDisplayDecimals: true,
					allowNegative: false,
					//currencySymbol: '₱',
					labelWidth: 117,
					width: 240,
					margin: '2 5 2 0',
					readOnly: true,
					thousandSeparator: ',',
					fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
				},{
					xtype: 'numericfield',
					id: 'desouts_ar_amount',
					name: 'desouts_ar_amount',
					fieldLabel: '<b>Outstanding AR </b>',
					allowBlank:false,
					useThousandSeparator: true,
					decimalPrecision: 2,
					alwaysDisplayDecimals: true,
					allowNegative: false,
					//currencySymbol: '₱',
					labelWidth: 117,
					width: 240,
					margin: '2 5 2 0',
					readOnly: true,
					thousandSeparator: ',',
					fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
				},{
					xtype: 'numericfield',
					id: 'desamort_amount',
					name: 'desamort_amount',
					fieldLabel: '<b>Amortization </b>',
					allowBlank:false,
					useThousandSeparator: true,
					decimalPrecision: 2,
					alwaysDisplayDecimals: true,
					allowNegative: false,
					//currencySymbol: '₱',
					labelWidth: 117,
					width: 240,
					margin: '2 5 2 0',
					readOnly: true,
					thousandSeparator: ',',
					fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
				}]
			},{
				//title: 'Center Region',
				region: 'center',     // center region is required, no width/height specified
				xtype: 'panel',
				//layout: 'fit',
				margin: '4 4 0 0',
				items: [{
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 5',
					items:[{
						xtype : 'datefield',
						id	  : 'firstdue_date',
						name  : 'firstdue_date',
						fieldLabel : '<b>First Due Date </b>',
						allowBlank: false,
						labelWidth: 117,
						width: 250,
						margin: '0 5 0 0',
						format : 'm/d/Y',
						readOnly: true,
						fieldStyle: 'font-weight: bold; color: #210a04;'
					},{
						xtype : 'datefield',
						id	  : 'maturity_date',
						name  : 'maturity_date',
						fieldLabel : '<b>Maturity Date </b>',
						labelWidth: 117,
						width: 250,
						format : 'm/d/Y',
						readOnly: true,
						fieldStyle: 'font-weight: bold; color: #210a04;'
					}]
				},{
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 5',
					items:[{
						xtype: 'numericfield',
						id: 'lcp_amount',
						name: 'lcp_amount',
						fieldLabel: '<b>LCP Amount </b>',
						allowBlank:false,
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						//currencySymbol: '₱',
						labelWidth: 117,
						width: 250,
						margin: '0 5 0 0',
						readOnly: true,
						thousandSeparator: ',',
						minValue: 0,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					},{
						xtype: 'textfield',
						fieldLabel: '<b>Category </b>',
						id: 'category',
						name: 'category',
						labelWidth: 117,
						width: 250,
						readOnly: true,
						fieldStyle: 'font-weight: bold; color: #210a04;'
					}]
				},{
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 5',
					items:[{
						xtype: 'numericfield',
						id: 'total_amount',
						name: 'total_amount',
						fieldLabel: '<b>Total Amount </b>',
						allowBlank:false,
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						//currencySymbol: '₱',
						labelWidth: 117,
						width: 250,
						margin: '0 5 0 0',
						readOnly: true,
						thousandSeparator: ',',
						minValue: 0,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					},{
						xtype: 'numberfield',
						fieldLabel: '<b>Term </b>',
						id: 'months_term',
						name: 'months_term',
						labelWidth: 117,
						width: 250,
						minValue: 0,
						value: 0,
						readOnly: true,
						fieldStyle: 'font-weight: bold; color: #210a04;'
					}]
				},{
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 5',
					items:[{
						xtype: 'numericfield',
						id: 'ar_amount',
						name: 'ar_amount',
						fieldLabel: '<b>AR Amount</b>',
						allowBlank:false,
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						//currencySymbol: '₱',
						labelWidth: 117,
						width: 250,
						margin: '0 5 0 0',
						readOnly: true,
						thousandSeparator: ',',
						minValue: 0,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'financing_rate',
						name: 'financing_rate',
						fieldLabel: '<b>Financing rate </b>',
						allowBlank:false,
						useThousandSeparator: false,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						//currencySymbol: '₱',
						labelWidth: 117,
						width: 250,
						readOnly: true,
						thousandSeparator: ',',
						minValue: 0,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 5',
					items:[{
						xtype: 'numericfield',
						id: 'outs_ar_amount',
						name: 'outs_ar_amount',
						fieldLabel: '<b>Outstanding AR </b>',
						allowBlank:false,
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						//currencySymbol: '₱',
						labelWidth: 117,
						width: 250,
						margin: '0 5 0 0',
						readOnly: true,
						thousandSeparator: ',',
						minValue: 0,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'rebate',
						name: 'rebate',
						fieldLabel: '<b>Rebate </b>',
						allowBlank:false,
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						//currencySymbol: '₱',
						labelWidth: 117,
						width: 250,
						readOnly: true,
						thousandSeparator: ',',
						minValue: 0,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 5',
					items:[{
						xtype: 'numericfield',
						id: 'amort_amount',
						name: 'amort_amount',
						fieldLabel: '<b>Amortization </b>',
						allowBlank:false,
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						//currencySymbol: '₱',
						labelWidth: 117,
						width: 210,
						margin: '0 0 0 0',
						readOnly: true,
						thousandSeparator: ',',
						minValue: 0,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					},{
						xtype: 'button',
						//text : ''
						tooltip: 'Click to compute amortization',
						margin: '0 5 0 0',
						icon: '../../js/ext4/examples/shared/icons/calculator.png',
						handler : function() {
							CalculateInstallment();
							Ext.Msg.alert('Information:', '<font color="green"><b> finished... </b></font>');
						}
					},{
						xtype: 'numericfield',
						id: 'dp_amount',
						name: 'dp_amount',
						fieldLabel: '<b>Down Payment </b>',
						allowBlank:false,
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						//currencySymbol: '₱',
						labelWidth: 117,
						width: 250,
						readOnly: true,
						thousandSeparator: ',',
						minValue: 0,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 5',
					items:[{
						xtype: 'numericfield',
						id: 'profitmargin',
						name: 'profitmargin',
						fieldLabel: '<b>Old GP Margin </b>',
						allowBlank:false,
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						//currencySymbol: '₱',
						labelWidth: 117,
						width: 250,
						margin: '0 5 0 0',
						readOnly: true,
						thousandSeparator: ',',
						minValue: 0,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'newprofitmargin',
						name: 'newprofitmargin',
						fieldLabel: '<b>New GP Margin </b>',
						allowBlank:false,
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						//currencySymbol: '₱',
						labelWidth: 117,
						width: 250,
						//margin: '0 0 0 255',
						readOnly: true,
						thousandSeparator: ',',
						minValue: 0,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				}]
			},{
				//title: 'South Region is resizable',
				region: 'south',     // position for region
				xtype: 'panel',
				split: true,         // enable resizing
				margin: '0 4 4 4',
				items: [{
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 5',
					items:[{
						xtype: 'textfield',
						fieldLabel: '<b>Customer </b>',
						id: 'customercode',
						name: 'customercode',
						labelWidth: 113,
						width: 245,
						margin: '2 0 0 0',
						allowBlank: false,
						readOnly: true,
						fieldStyle: 'font-weight: bold; color: #210a04;'
					},{
						xtype: 'combobox',
						id: 'customername',
						name: 'customername',
						allowBlank: false,
						store : Customerstore,
						displayField: 'name',
						valueField: 'debtor_no',
						queryMode: 'local',
						margin: '2 0 0 0',
						width: 507,
						anyMatch: true,
						forceSelection: true,
						selectOnFocus:true,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						listeners: {
							select: function(combo, record, index) {
								Ext.getCmp('customercode').setValue(record.get('debtor_ref'));
								Ext.getCmp('name').setValue(record.get('name'));

								Ext.Ajax.request({
									url : '?getReference=INV',
									async:false,
									success: function (response){
										var result = Ext.JSON.decode(response.responseText);
										Ext.getCmp('ref_no').setValue(result.reference);
										submit_window.setTitle('Customer Amortization Receipt Entry - Reference No. : '+ result.reference + ' *new');
									}
								});
							}
						}
					}]
				},{
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 5',
					items:[{
						xtype: 'textfield',
						fieldLabel: '<b>Invoice No. </b>',
						id: 'invoice_no',
						name: 'invoice_no',
						labelWidth: 113,
						width: 355,
						margin: '0 42 0 0',
						readOnly: true,
						fieldStyle: 'font-weight: bold; color: #210a04;'
					},{
						xtype : 'datefield',
						id	  : 'invoice_date',
						name  : 'invoice_date',
						fieldLabel : '<b>Invoice Date </b>',
						allowBlank: false,
						labelWidth: 115,
						width: 355,
						format : 'm/d/Y',
						readOnly: true,
						fieldStyle: 'font-weight: bold; color: #210a04;'
					}]
				},{
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 5',
					items:[{
						xtype: 'textfield',
						fieldLabel: '<b>Delivery No. </b>',
						id: 'delivery_no',
						name: 'delivery_no',
						labelWidth: 113,
						width: 355,
						margin: '0 42 0 0',
						readOnly: true,
						fieldStyle: 'font-weight: bold; color: #210a04;'
					},{
						xtype: 'textfield',
						fieldLabel: '<b>Branch code </b>',
						id: 'branch_code',
						name: 'branch_code',
						labelWidth: 115,
						width: 355,
						readOnly: true,
						fieldStyle: 'font-weight: bold; color: #210a04;'
					}]
				},{
					xtype: 'textfield',
					fieldLabel: '<b>Description </b>',
					id: 'comments',
					name: 'comments',
					margin: '0 0 5 5',
					labelWidth: 113,
					width: 752,
					//readOnly: true,
					fieldStyle: 'font-weight: bold; color: #210a04;'
				}]
			}]
		},{
			xtype: 'grid',
			id: 'itemgrid',
			name: 'itemgrid',
			//title: 'Items',
			loadMask: true,
			frame: true,
			store:	Itemstore,
			columns: ItemcolModel,
			columnLines: true,
			autoScroll:true,
			layout:'fit',
			features: [{
				ftype: 'summary'
			}]
		}]
	});
	var submit_window = Ext.create('Ext.Window',{
		id: 'submit_window',
		width 	: 780,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		//style: 'margin:0 auto;margin-top:0px;',
		icon: '../../js/ext4/examples/shared/icons/information.png',
		items:[submit_form],
		buttons:[{
			text:'<b>Amortization</b>',
			tooltip: 'Preview Amortization',
			icon: '../../js/ext4/examples/shared/icons/application_view_list.png',
			single : true,
			handler:function(){
				window.open('../../lending/inquiry/deptor_amortization.php?invoice_no='+Ext.getCmp('id').getValue());
			}
		},'		','-','		',{
			text:'<b>Approved</b>',
			id:'btn_apprvd',
			tooltip: 'Approved A/R Installment',
			icon: '../../js/ext4/examples/shared/icons/accept.png',
			single : true,
			handler:function(){
				var HaveErrors = 0;
				var $message;
				var form_submit = Ext.getCmp('submit_form').getForm();
				
				/*if(Ext.getCmp('desouts_ar_amount').getValue() != Ext.getCmp('outs_ar_amount').getValue()){
					$message = "Outstanding AR amount are not equal. Please review all data before saving.";
					HaveErrors = 1;
				}
				if(Ext.getCmp('desar_amount').getValue() != Ext.getCmp('ar_amount').getValue()){
					$message = "AR amount are not equal. Please review all data before saving.";
					HaveErrors = 1;
				}
				if(Ext.getCmp('desamort_amount').getValue() != Ext.getCmp('amort_amount').getValue()){
					$message = "Amortization amount are not equal. Please review all data before saving.";
					HaveErrors = 1;
				}
				if(Ext.getCmp('descustname').getValue() != Ext.getCmp('customername').getRawValue()){
					$message = "Customer are not equal. Please review all data before saving.";
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
				}*/
				Ext.MessageBox.confirm('Confirmation:', 'Have you reviewed all the data before saving?', function (btn, text) {
					if (btn == 'yes') {
						form_submit.submit({
							url : newURL+'?submit=Approved-'+ Ext.getCmp('invoice_no').getValue(),
							submitEmptyText: false,
							waitMsg: 'Please wait while we are processing your request...',
							method:'POST',
							success: function(form_submit, action) {
								ARInstallQstore.load();
								Ext.Msg.alert('Success!', '<font color="green">' + action.result.message + '</font>');
								submit_window.close();
							},
							failure: function(form_submit, action) {
								Ext.Msg.alert('Failed!', '<font color="red">' + action.result.message + '</font>');
							}
						});
					}
				});
			}
		},{
			text: '<b>Disapproved</b>',
			id:'btn_disapprvd',
			tooltip: 'Disapproved A/R Installment',
			icon: '../../js/ext4/examples/shared/icons/cancel.png',
			single : true,
			handler:function(){
				var form_submit = Ext.getCmp('submit_form').getForm();
				var form_value = form_submit.getValues();
				
				Ext.MessageBox.confirm('Confirmation:', 'Are you sure you want to reject this invoice?', function (btn, text) {
					if (btn == 'yes') {
						Ext.Ajax.request({
							url : '?DisApproved=YES'+'&id='+form_value['id']+'&branch_code='+form_value['branch_code'],
							waitMsg: 'Please wait while we are processing your request...',
							async:false,
							success: function (response) {
								var result = Ext.JSON.decode(response.responseText);
								if (result.success == "true") {
									Ext.Msg.show({
										title: 'Success!',
										msg: '<font color="green">' + result.message + '</font>',
										buttons: Ext.Msg.OK,
										icon: Ext.MessageBox.INFORMATION
									});
									ARInstallQstore.load();
								}
								else {
									Ext.Msg.show({
										title: 'Failed!',
										msg: result.message,
										buttons: Ext.Msg.OK,
										icon: Ext.MessageBox.ERROR
									});
								}
							},
							failure: function () {
								Ext.Msg.show({
									title: 'Failed!',
									msg: result.message,
									buttons: Ext.Msg.OK,
									icon: Ext.MessageBox.ERROR
								});
							}
						});
					}
				});
				
				submit_form.getForm().reset();
				submit_window.close();
			}
		}]
	});

	var entry_window = new Ext.create('Ext.Window',{
		width 	: 860,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		autoScroll:true,
		layout: 'fit',
		items:[{
			xtype:'gridpanel',
			id: 'gridEntry',
			anchor:'100%',
			autoScroll: true,
			loadMask: true,
			store: EntriesStore,
			columns: EntriescolModel,
			columnLines: true,
			features: [{
				ftype: 'summary'
			}]
		}]
	});

	//---------------------------------------------------------------------------------------
	//for ar installment column model
	var ColumnModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Id</b>',dataIndex:'plcyd_id',hidden: true},
		{header:'<b>Inv.Date</b>', dataIndex:'invoice_date', sortable:true, width:100, renderer: Ext.util.Format.dateRenderer('m-d-Y')},
		{header:'<b>Invoice No.</b>', dataIndex:'invoice_no', sortable:true, width:120},
		{header:'<b>Customer Name</b>', dataIndex:'customer_name', sortable:true, width:180,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Type</b>', dataIndex:'invoice_type', sortable:true, width:60,
			renderer:function(value,metaData){
				if (value === 'repo'){
					metaData.style="color: #4927e1";
				}else{
					metaData.style="color:#229954";
				}
				return "<b>" + value + "</b>";
			}
		},
		{header:'<b>Category</b>', dataIndex:'category_desc', sortable:true, width:100},
		{header:'<b>Term</b>', dataIndex:'months_term', sortable:true, width:59},
		{header:'<b>Financing</b>', dataIndex:'fin_rate', sortable:true, width:88,
			renderer: Ext.util.Format.Currency = function(value){
				 if (value==0){
					   value = '0.00%';
					   return (value);
				 }else{
					   return (value + '%')
				 }
			}
		},
		{header:'<b>Downpayment</b>', dataIndex:'dp_amount', sortable:true, width:82,
			renderer: Ext.util.Format.Currency = function(value){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>Due Date</b>', dataIndex:'firstdue_date', sortable:true, width:88, renderer: Ext.util.Format.dateRenderer('m-d-Y')},
		{header:'<b>Maturity Date</b>', dataIndex:'maturity_date', sortable:true, width:95, renderer: Ext.util.Format.dateRenderer('m-d-Y')},
		{header:'<b>AR Amount</b>', dataIndex:'ar_amount', sortable:true, width:100,
			renderer: Ext.util.Format.Currency = function(value){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>Amort Amount</b>', dataIndex:'amortn_amount', sortable:true, width:80,
			renderer: Ext.util.Format.Currency = function(value){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>Status</b>', dataIndex:'status', sortable:true, width:88,
			renderer:function(value,metaData){
				metaData.tdAttr = 'data-qtip="' + value + '"';
				if (value === 'Draft'){
					metaData.style="color: #2980b9";
				}else if(value === 'Approved'){
					metaData.style="color:#1e8449 ";
					//'<a href="../lending/ar_installment.php?">'+value+'</a>';
				}else {
					metaData.style="color:#f00a2a ";
				}
				return "<b>" + value + "</b>";
			}
		},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:73,
			items:[{
				icon: '../../js/ext4/examples/shared/icons/layout_content.png',
				tooltip: 'view details',
				handler: function(grid, rowIndex, colIndex) {
					var records = ARInstallQstore.getAt(rowIndex);
					var formtitle = 'Incoming Invoice';

					submit_form.getForm().reset();
					Itemstore.proxy.extraParams = {SIitem: records.get('ar_id')};
					Itemstore.load();
					Ext.getCmp('id').setValue(records.get('ar_id'));
					Ext.getCmp('invoice_no').setValue(records.get('invoice_no'));
					Ext.getCmp('invoice_date').setValue(records.get('invoice_date'));
					Ext.getCmp('delivery_no').setValue(records.get('delivery_no'));
					Ext.getCmp('invoice_type').setValue(records.get('invoice_type'));
					Ext.getCmp('descustcode').setValue(records.get('customer_code'));
					Ext.getCmp('descustname').setValue(records.get('customer_name'));
					//Ext.getCmp('descustomer').setText(records.get('customer_code') + ' - '+ records.get('customer_name'));
					Ext.getCmp('category_id').setValue(records.get('category_id'));
					Ext.getCmp('category').setValue(records.get('category_desc'));
					Ext.getCmp('months_term').setValue(records.get('months_term'));
					Ext.getCmp('rebate').setValue(records.get('rebate'));
					Ext.getCmp('financing_rate').setValue(records.get('fin_rate'));
					Ext.getCmp('dp_amount').setValue(records.get('dp_amount'));
					Ext.getCmp('firstdue_date').setValue(records.get('firstdue_date'));
					Ext.getCmp('maturity_date').setValue(records.get('maturity_date'));
					//Ext.getCmp('prepared_by').setValue(records.get('prepared_by'));
					Ext.getCmp('branch_code').setValue(records.get('branch_code'));
					Ext.getCmp('comments').setValue(records.get('comments'));
					Ext.getCmp('lcp_amount').setValue(records.get('lcp_amount'));
					Ext.getCmp('deslcp_amount').setValue(records.get('lcp_amount'));
					Ext.getCmp('desar_amount').setValue(records.get('ar_amount'));
					Ext.getCmp('desouts_ar_amount').setValue(records.get('outs_ar_amount'));
					Ext.getCmp('destotal_amount').setValue(records.get('total_amount'));
					Ext.getCmp('desamort_amount').setValue(records.get('amortn_amount'));
					Ext.getCmp('policy_id').setValue(records.get('installplcy_id'));
					Ext.getCmp('profitmargin').setValue(records.get('gpm'));
					Ext.getCmp('ARINQRYGRID').getSelectionModel().select(rowIndex);
					if(records.get('invoice_type') == "repo"){
						$type = '<span style="color: blue;font-weight:bold"> Repo </span>';
					}else{
						$type = '<span style="color: yellow;font-weight:bold"> Brandnew </span>';
					}

					formtitle = formtitle + ' '+ $type + ' ';

					if(records.get('status') == "Open"){
						Ext.getCmp('btn_apprvd').setDisabled(false);
						Ext.getCmp('btn_disapprvd').setDisabled(false);
						formtitle = formtitle + records.get('invoice_no') + ' - ' + records.get('status');
					}else{
						if(records.get('status') == "Approved"){
							formtitle = formtitle + ' - ' + records.get('reference') + ' - ' + records.get('status');
						}
						//console.log(records.get('status'));
						Ext.getCmp('customername').setValue(records.get('debtor_no'));
						Ext.getCmp('customercode').setValue(records.get('debtor_ref'));
						Ext.getCmp('btn_apprvd').setDisabled(true);
						Ext.getCmp('btn_disapprvd').setDisabled(true);
						Ext.getCmp('AutoAddCustomer').setDisabled(true);
					}

					CalculateInstallment();
					submit_window.setTitle(formtitle);
					submit_window.show();
					submit_window.setPosition(320,23);
				}
			},'-',{
				icon   : '../../js/ext4/examples/shared/icons/chart_line.png',
				tooltip : 'Entries',
				handler : function(grid, rowIndex, colIndex){
					var records = ARInstallQstore.getAt(rowIndex);
					Ext.getCmp('ARINQRYGRID').getSelectionModel().select(rowIndex);
					if(records.get('status') == "Approved"){
						/*entry_window.setTitle('A/R Installment Entries - ' + records.get('invoice_no') + ' - ' + records.get('status'));
						entry_window.show();
						entry_window.setPosition(320,23);*/
						/*var trans_no = 0;
						Ext.Ajax.request({
							url : '?getARGL='+records.get('ar_id'),
							async:false,
							success: function (response){
								var result = Ext.JSON.decode(response.responseText);
								trans_no = result.trans_no;
							}
						});*/
						window.open('../../gl/view/gl_trans_view.php?type_id=70&trans_no='+ records.get('trans_no'));
	
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
				var search = Ext.getCmp("search").getValue();

				ARInstallQstore.proxy.extraParams = {InvType: combo.getValue(),  status: Ext.getCmp('fstatus').getValue(), query: search};
				ARInstallQstore.load();
			},
			afterrender: function() {
				Ext.getCmp("invtype").setValue("x");
			}
		}
	}, {
		xtype: 'combobox',
		id: 'fstatus',
		name: 'fstatus',
		fieldLabel: '<b>Status</b>',
		store: fstatusStore,
		displayField: 'name',
		valueField: 'id',
		queryMode: 'local',
		emptyText:'view by status',
		labelWidth: 50,
		width: 190,
		forceSelection: true,
		selectOnFocus:true,
		editable: false,
		listeners: {
			select: function(combo, record, index) {
				/*ARInstallQstore.load({
					params: { InvType: Ext.getCmp('invtype').getValue(),  status: combo.getValue()}
				});*/
				
				var search = Ext.getCmp("search").getValue();
				
				ARInstallQstore.proxy.extraParams = {InvType: Ext.getCmp('invtype').getValue(),  status: combo.getValue(), query: search};
				ARInstallQstore.load();
			},
			afterrender: function() {
				Ext.getCmp("fstatus").setValue("Open");
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
					ARInstallQstore.proxy.extraParams = {InvType: Ext.getCmp('invtype').getValue(),  status: Ext.getCmp('fstatus').getValue(), query: field.getValue()};
					ARInstallQstore.load();
				}else{
					ARInstallQstore.proxy.extraParams = {InvType: Ext.getCmp('invtype').getValue(),  status: Ext.getCmp('fstatus').getValue()};
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
					//ARInstallQstore.proxy.extraParams = {identifier: Identifier, loccode: Ext.getCmp('Location').getValue(), category: ValCategory};
					ARInstallQstore.load();
					
				}/*,
				items:[{
					xtype: 'checkbox',
					id: 'cststatus',
					name: 'cststatus',
					boxLabel: 'Show also Inactive',
					listeners: {
						change: function(column, rowIdx, checked, eOpts){
							var search = Ext.getCmp('search');
							
							if(checked){
								showall = false;
							}else{
								showall = true;
							}
							if(search.getValue() != ""){
								ARInstallQstore.proxy.extraParams = {branch: branchselected, showall: showall, query: search.getValue()};
							}else{
								ARInstallQstore.proxy.extraParams = {branch: branchselected, showall: showall};
							}
							ARInstallQstore.load();
						}
					}
				}]*/
			}
		}]
	});

	function CalculateInstallment(){
		/*	ComputedRebate = Term * Rebate
			MinusLCP = LCP - DP
			FR_LCP = MinusLCP * FR(Financing Rate)
			- 1 to 3 months 
				~^ Amort = Roundoff ( MinusLCP / Term )
			- 6 to up
				~^ Amort = MinusLCP + FR_LCP + ComputedRebate + DP
				~^ Amort = Roundoff ( Amort / Term )
			RateOfMonths = 
		
			Term: 0.5/15 days
				Unit Price = LCP(vise versa, pwd e change ang unit price)
				-^ if category is motorcycle, LCP must not change even if user wants to change the value of unit price.
				DisplaySubTotalOut = Unit Price * Qty Out
				MonthlyAmount = LCP - DP(Downpayment)
				
				-----------------------------
				ARAmount = MonthlyAmount + DP
				Outstanding = ARAmount - DP
				GrossProfitAmount = ARAmount - (Net Cost * Qty Out)
				DeferredAmount = GrossProfitAmount
				TotalAmount = ARAmount
			Term: 3 to up
		*/
		var FR = Ext.getCmp('financing_rate').getValue();
		var Term = Ext.getCmp('months_term').getValue();
		var Rebate = Ext.getCmp('rebate').getValue();
		var LCPAmount = Ext.getCmp('lcp_amount').getValue();
		var DPAmount = Ext.getCmp('dp_amount').getValue();
		var unearned_amount = 0;
		var newgpm = 0;

		Factor = (FR / 100); //parseInt(FR)
		Amortization = GetMonthlyAmort(LCPAmount,DPAmount,Factor,Rebate,Term);
		ARamount = Amortization * Term + DPAmount;
		OutstandingAR = Amortization * Term;
		
		Ext.getCmp('ar_amount').setValue(ARamount);
		Ext.getCmp('outs_ar_amount').setValue(OutstandingAR);
		Ext.getCmp('total_amount').setValue(ARamount);
		Ext.getCmp('amort_amount').setValue(Amortization);

		unearned_amount = (ARamount - LCPAmount);
		newgpm = (unearned_amount / OutstandingAR);
		Ext.getCmp('newprofitmargin').setValue(newgpm);
	};
	function GetMonthlyAmort(LCP, DP, Factor, Rebate, Term){
		/*get the amortization amount*/
		MinusLCP = LCP - DP;
		
		if(Factor != 0){
			Factor = Factor + 1;
			MinusLCP = MinusLCP * Factor;
		}
		if(Term != 0){
			MinusLCP = MinusLCP / Term;
		}
		if(Rebate != 0){
			MinusLCP = MinusLCP + Math.round(Rebate);
		}
		return Math.round(MinusLCP);
	}
});
