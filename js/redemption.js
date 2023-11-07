var newURL = window.location.protocol + "//" + window.location.host + window.location.pathname;
Ext.Loader.setConfig({enabled: true});
Ext.Loader.setPath('Ext.ux', '../../js/ext4/examples/ux/');
Ext.require([
    'Ext.grid.*',
    'Ext.data.*',
    'Ext.panel.*',
    'Ext.form.*',
	'Ext.window.*',
    'Ext.tab.*',
	'Ext.selection.CheckboxModel',
	'Ext.selection.CellModel',
	'Ext.form.field.File',
	'Ext.ux.form.SearchField',
	'Ext.ux.form.NumericField'

]);

Ext.onReady(function(){
	Ext.QuickTips.init();
	var itemsPerPage = 18;   // set the number of items you want per page on grid.
	var showall = false;

	var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
        clicksToEdit: 1
    });

    Ext.define('comboModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'name', mapping:'name'}
		]
    });
    Ext.define('redempModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'type', mapping:'type'},
			{name:'repo_date', mapping:'repo_date'},
			{name:'repo_ref', mapping:'repo_ref'},
			{name:'repo_id', mapping:'repo_id'},
			{name:'invoice_date', mapping:'invoice_date'},
			{name:'invoice_ref', mapping:'invoice_ref'},
			{name:'term', mapping:'term'},
			{name:'dp_amount', mapping:'dp_amount'},
			{name:'out_ar', mapping:'out_ar'},
			{name:'monthly_amort', mapping:'monthly_amort'},
			{name:'balance', mapping:'balance'},
			{name:'first_duedate', mapping:'first_duedate'},
			{name:'maturty_date', mapping:'maturty_date'},
			{name:'lcp_amount', mapping:'lcp_amount'},
			{name:'unit_cost', mapping:'unit_cost'},
			{name:'category_id', mapping:'category_id'},
			{name:'category_desc', mapping:'category_desc'},
			{name:'addon_amount', mapping:'addon_amount'},
			{name:'unrecoverd', mapping:'unrecoverd'},
			{name:'totalunrecoverd', mapping:'totalunrecoverd'},
			{name:'overdue', mapping:'overdue'},
			{name:'pastdue', mapping:'pastdue'},
			{name:'GPM', mapping:'GPM'},
			{name:'total_amount', mapping:'total_amount'},
			{name:'remarks', mapping:'remarks'},
			{name:'ar_transno', mapping:'ar_transno'},
			{name:'ar_transtype', mapping:'ar_transtype'}
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
	Ext.define('RepoModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'type', mapping:'type'},
			{name:'repo_date', mapping:'repo_date'},
			{name:'repo_ref', mapping:'repo_ref'},
			{name:'invoice_date', mapping:'invoice_date'},
			{name:'invoice_ref', mapping:'invoice_ref'},
			{name:'term', mapping:'term'},
			{name:'dp_amount', mapping:'dp_amount'},
			{name:'out_ar', mapping:'out_ar'},
			{name:'monthly_amort', mapping:'monthly_amort'},
			{name:'balance', mapping:'balance'},
			{name:'first_duedate', mapping:'first_duedate'},
			{name:'maturty_date', mapping:'maturty_date'},
			{name:'lcp_amount', mapping:'lcp_amount'},
			{name:'unit_cost', mapping:'unit_cost'},
			{name:'category_id', mapping:'category_id'},
			{name:'category_desc', mapping:'category_desc'},
			{name:'addon_amount', mapping:'addon_amount'},
			{name:'unrecoverd', mapping:'unrecoverd'},
			{name:'totalunrecoverd', mapping:'totalunrecoverd'},
			{name:'overdue', mapping:'overdue'},
			{name:'pastdue', mapping:'pastdue'},
			{name:'GPM', mapping:'GPM'},
			{name:'total_amount', mapping:'total_amount'},
			{name:'remarks', mapping:'remarks'},
			{name:'ar_transno', mapping:'ar_transno'},
			{name:'ar_transtype', mapping:'ar_transtype'}
		]
    });
	Ext.define('item_delailsModel',{
		extend : 'Ext.data.Model',
		fields : [
			{name:'stock_id',mapping:'stock_id'},
			{name:'description',mapping:'description'},
			{name:'qty',mapping:'qty'},
			{name:'unit_price',mapping:'unit_price',type:'float'},
			{name:'serial_no',mapping:'serial_no'},
			{name:'chassis_no',mapping:'chassis_no'},
			{name:'color_code',mapping:'color_code'}
		]
	});
	//------------------------------------: stores :----------------------------------------
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
			property : 'debtor_no',
			direction : 'ASC'
		}]
	});
    var categorystore = Ext.create('Ext.data.Store', {
		name: 'categorystore',
        model: 'comboModel',
		autoLoad : true,
        proxy: {
			url: '?getcategory=00',
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
	var RepoitemStore = Ext.create('Ext.data.Store', {
		model: 'item_delailsModel',
		name : 'RepoitemStore',
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
	var RepoAccntStore = Ext.create('Ext.data.Store', {
		model: 'RepoModel',
		//autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_RepoAccnt=xx',
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
	var RedempStore = Ext.create('Ext.data.Store', {
		model: 'redempModel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_Redemption=zHun',
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
	//-----------------------------------//
	var ColumnModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Tran Date</b>', dataIndex:'trans_date', sortable:true, width:93, renderer: Ext.util.Format.dateRenderer('m-d-Y')},
		{header:'<b>Repo Date</b>', dataIndex:'repo_date', sortable:true, width:95, renderer: Ext.util.Format.dateRenderer('m-d-Y')},
		{header:'<b>Reference No.</b>', dataIndex:'reference_no', sortable:true, width:120},
		{header:'<b>Customer Name</b>', dataIndex:'name', sortable:true, width:170,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Category</b>', dataIndex:'category_desc', sortable:true, width:130},
		{header:'<b>Invoice Date</b>', dataIndex:'release_date', sortable:true, width:110, renderer: Ext.util.Format.dateRenderer('m-d-Y')},
		{header:'<b>Total Amount</b>', dataIndex:'total_amount', sortable:true, width:120,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
			}
		},
		{header:'<b>Particulars</b>', dataIndex:'comments', sortable:true, width:180,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Total Unrecovrd</b>', dataIndex:'total_unrecovered', sortable:true, width:130,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
			}
		},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:110,
			items:[{
				icon: '../../js/ext4/examples/shared/icons/layout_content.png',
				tooltip: 'view details',
				handler: function(grid, rowIndex, colIndex) {
					var records = RedempStore.getAt(rowIndex);

					CustomerStore.proxy.extraParams = {debtor_no: records.get('debtor_no')};
					CustomerStore.load();
					RepoAccntStore.proxy.extraParams = {debtor_no: records.get('debtor_no')};
					RepoAccntStore.load();
					RepoitemStore.proxy.extraParams = {repo_id: records.get('id')};
					RepoitemStore.load();

					Ext.getCmp('btnsave').setVisible(false);
					Ext.getCmp('btncancel').setText('Close');
					
					Ext.getCmp('sysid').setValue(records.get('id'));
					Ext.getCmp('transtype').setValue(records.get('ar_trans_type'));
					Ext.getCmp('customercode').setValue(records.get('debtor_ref'));
					Ext.getCmp('RepoNo').setValue(records.get('repo_id'));
					Ext.getCmp('repo_date').setValue(records.get('repo_date'));
					Ext.getCmp('release_date').setValue(records.get('release_date'));
					Ext.getCmp('months_term').setValue(records.get('term'));
					Ext.getCmp('downpayment').setValue(records.get('downpayment'));
					Ext.getCmp('outs_ar_amount').setValue(records.get('outstanding_ar'));
					Ext.getCmp('amort_amount').setValue(records.get('amortization_amount'));
					Ext.getCmp('balance').setValue(records.get('balance'));
					Ext.getCmp('firstdue_date').setValue(records.get('firstdue_date'));
					Ext.getCmp('maturity_date').setValue(records.get('maturity_date'));
					Ext.getCmp('invoice_refno').setValue(records.get('reference_no'));
					Ext.getCmp('category').setValue(records.get('category_id'));
					Ext.getCmp('lcp_amount').setValue(records.get('lcp_amount'));
					//Ext.getCmp('over_due').setValue(records.get('over_due'));
					Ext.getCmp('spotcash').setValue(records.get('spot_cash_amount'));
					//Ext.getCmp('past_due').setValue(records.get('past_due'));
					Ext.getCmp('total_amount').setValue(records.get('total_amount'));
					Ext.getCmp('addon_cost').setValue(records.get('addon_amount'));
					Ext.getCmp('unrecovrd_cost').setValue(records.get('unrecovered_cost'));
					Ext.getCmp('total_unrecovrd').setValue(records.get('total_unrecovered'));
					Ext.getCmp('remarks').setValue(records.get('comments'));
					//Ext.getCmp('customername').setWidth(390);
					Ext.getCmp('customername').setValue(records.get('debtor_no'));

					submit_window.setTitle('Installment Redemption - Reference No. :'+ records.get('reference_no'));
					submit_window.show();
					submit_window.setPosition(330,140);
				}
			},'-',{
				icon   : '../../js/ext4/examples/shared/icons/chart_line.png',
				tooltip : 'Entries',
				handler : function(grid, rowIndex, colIndex){
					var records = RedempStore.getAt(rowIndex);
					window.open('../../gl/view/gl_trans_view.php?type_id='+ records.get('type') +'&trans_no='+ records.get('id'));
				}
			}]
		}
	];
	var Item_view = [
		{header:'<b>Item Code</b>', dataIndex:'stock_id', width:120},
		{header:'<b>Description</b>', dataIndex:'description', width:140,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Qty</b>', dataIndex:'qty', width:60},
		{header:'<b>Unit Price</b>', dataIndex:'unit_price', width:100,
			editor: new Ext.form.TextField({
				xtype:'textfield',
				id: 'unit_price',
				name: 'unit_price',
				allowBlank: false,
				listeners : {
					change: function(editor, e){
						Ext.getCmp('lcp_amount').setValue(e);					  
					}
				},
			}), 
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>Serial No.</b>', dataIndex:'serial_no', width:170, editor: 'textfield',
			renderer : function(value, metaData, summaryData, dataIndex){
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Chasis No.</b>', dataIndex:'chassis_no', width:170, editor: 'textfield',
			renderer : function(value, metaData, summaryData, dataIndex){
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		/*{header:'<b>Color code</b>', dataIndex:'color_code', width:200,
			renderer : function(value, metaData, summaryData, dataIndex){
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		}*/
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:70,
			items:[{
				icon: '../../js/ext4/examples/shared/icons/delete.png',
				tooltip: 'remove',
				handler: function(grid, rowIndex, colIndex) {
					var records = OtherEntryStore.getAt(rowIndex);
					//loadOtherEntry('delete',records.get("id"), 'amort');
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
		store: RedempStore,
		listeners: {
			change: function(field) {
				RedempStore.proxy.extraParams = {query: field.getValue()};
				RedempStore.load();
			}
		}
	}, '-', {
		text:'<b>Add</b>',
		tooltip: 'Add new repossesse transaction.',
		icon: '../../js/ext4/examples/shared/icons/add.png',
		scale: 'small',
		handler: function(){
			submit_form.getForm().reset();
			
			submit_window.show();
			submit_window.setTitle('Installment Redemption - Add');
			submit_window.setPosition(330,140);
		}
	}];
	var submit_form = Ext.create('Ext.form.Panel', {
		id: 'form_submit',
		model: 'redempModel',
		frame: false,
		border: true,
		defaults: {msgTarget: 'side', labelWidth: 95, anchor: '-10'}, 
		items: [{
			xtype: 'textfield',
			id: 'sysid',
			name: 'sysid',
			fieldLabel: 'sysid',
			//allowBlank: false,
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
			id: 'base_transno',
			name: 'base_transno',
			fieldLabel: 'base_transno',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'base_transtype',
			name: 'base_transtype',
			fieldLabel: 'base_transtype',
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
			xtype: 'textfield',
			id: 'reference',
			name: 'reference',
			fieldLabel: 'reference',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'panel',
			id: 'mainpanel',
			items: [{
				xtype: 'panel',
				id: 'upanel',
				items: [{
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{
						xtype: 'textfield',
						fieldLabel: 'Customer ',
						id: 'customercode',
						name: 'customercode',
						allowBlank: false,
						labelWidth: 80,
						width: 200,
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
						width: 390,
						anyMatch: true,
						forceSelection: true,
						selectOnFocus:true,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						listeners: {
							select: function(combo, record, index) {
								Ext.getCmp('customercode').setValue(record.get('debtor_ref'));
								Ext.getCmp('custname').setValue(record.get('name'));

								RepoAccntStore.proxy.extraParams = {debtor_no: record.get('debtor_no')};
								RepoAccntStore.load();
								RepoitemStore.proxy.extraParams = {transNo: 0};
								RepoitemStore.load();
								
								Ext.getCmp('RepoNo').setValue();
								Ext.getCmp('transtype').setValue(0);
								Ext.getCmp('release_date').setValue();
								Ext.getCmp('months_term').setValue();
								Ext.getCmp('downpayment').setValue();
								Ext.getCmp('outs_ar_amount').setValue();
								Ext.getCmp('amort_amount').setValue();
								Ext.getCmp('balance').setValue();
								Ext.getCmp('firstdue_date').setValue();
								Ext.getCmp('maturity_date').setValue();
								Ext.getCmp('category').setValue();
								Ext.getCmp('lcp_amount').setValue();
								Ext.getCmp('spotcash').setValue();
								Ext.getCmp('addon_cost').setValue();
								Ext.getCmp('total_amount').setValue();
								Ext.getCmp('unrecovrd_cost').setValue();
								Ext.getCmp('total_unrecovrd').setValue();
								//Ext.getCmp('past_due').setValue();
								//Ext.getCmp('over_due').setValue();

								Ext.getCmp('base_transtype').setValue(0);
								Ext.getCmp('base_transno').setValue(0);
								
								//Ext.getCmp('remarks').readOnly = false;
								Ext.getCmp('remarks').setReadOnly(false);

								Ext.Ajax.request({
									url : '?getReference=zHun',
									async:false,
									success: function (response){
										var result = Ext.JSON.decode(response.responseText);
										Ext.getCmp('reference').setValue(result.reference);
										submit_window.setTitle('Installment Redemption -: '+ result.reference + ' *new');
									}
								});
							}
						}
					},{
						xtype : 'datefield',
						id	  : 'trans_date',
						name  : 'trans_date',
						fieldLabel : 'Trans Date ',
						allowBlank: false,
						labelWidth: 80,
						width: 230,
						format : 'm/d/Y',
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y-m-d'),
						listeners: {
							change: function(combo, record, index) {
								RepoAccntStore.proxy.extraParams = {debtor_id: 0};
								RepoAccntStore.load();
								RepoitemStore.proxy.extraParams = {transNo: 0};
								RepoitemStore.load();

								Ext.getCmp('RepoNo').setValue();
								Ext.getCmp('transtype').setValue();
								Ext.getCmp('release_date').setValue();
								Ext.getCmp('months_term').setValue();
								Ext.getCmp('downpayment').setValue();
								Ext.getCmp('outs_ar_amount').setValue();
								Ext.getCmp('amort_amount').setValue();
								Ext.getCmp('balance').setValue();
								Ext.getCmp('firstdue_date').setValue();
								Ext.getCmp('maturity_date').setValue();
								Ext.getCmp('category').setValue();
								Ext.getCmp('lcp_amount').setValue();
								Ext.getCmp('spotcash').setValue();
								Ext.getCmp('addon_cost').setValue();
								Ext.getCmp('total_amount').setValue();
								Ext.getCmp('unrecovrd_cost').setValue();
								Ext.getCmp('total_unrecovrd').setValue();
								//Ext.getCmp('past_due').setValue();
								//Ext.getCmp('over_due').setValue();
								Ext.getCmp('base_transno').setValue();
								Ext.getCmp('base_transtype').setValue();

								Ext.getCmp('remarks').readOnly = false;
							}
						}
					}]
				},{
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '0 0 0 0',
					items:[{
						xtype: 'combobox',
						id: 'RepoNo',
						name: 'RepoNo',
						allowBlank: false,
						store : RepoAccntStore,
						displayField: 'repo_ref',
						valueField: 'id',
						queryMode: 'local',
						fieldLabel : 'Repo Ref. ',
						labelWidth: 80,
						width: 590,
						forceSelection: true,
						selectOnFocus:true,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						listeners: {
							select: function(combo, record, index) {
								var form = this.up('form').getForm();
								fields = form.getFields();
								Ext.each(fields.items, function (f) {
									f.inputEl.dom.readOnly = true;
								});

								RepoitemStore.proxy.extraParams = {repo_id: record.get('id')};
								RepoitemStore.load();
								
								Ext.getCmp('transtype').setValue(record.get('type'));
								Ext.getCmp('base_transno').setValue(record.get('ar_transno'));
								Ext.getCmp('base_transtype').setValue(record.get('ar_transtype'));
								Ext.getCmp('repo_date').setValue(record.get('repo_date'));
								Ext.getCmp('release_date').setValue(record.get('invoice_date'));
								Ext.getCmp('invoice_refno').setValue(record.get('invoice_ref'));
								Ext.getCmp('months_term').setValue(record.get('term'));
								Ext.getCmp('downpayment').setValue(record.get('dp_amount'));
								Ext.getCmp('outs_ar_amount').setValue(record.get('out_ar'));
								Ext.getCmp('amort_amount').setValue(record.get('monthly_amort'));
								Ext.getCmp('balance').setValue(record.get('balance'));
								Ext.getCmp('firstdue_date').setValue(record.get('first_duedate'));
								Ext.getCmp('maturity_date').setValue(record.get('maturity_date'));
								Ext.getCmp('category').setValue(record.get('category_id'));
								Ext.getCmp('lcp_amount').setValue(record.get('lcp_amount'));
								Ext.getCmp('spotcash').setValue(record.get('unit_cost'));
								Ext.getCmp('addon_cost').setValue(record.get('addon_amount'));
								Ext.getCmp('total_amount').setValue(record.get('unrecoverd'));
								Ext.getCmp('unrecovrd_cost').setValue(record.get('unrecoverd'));
								Ext.getCmp('total_unrecovrd').setValue(record.get('totalunrecoverd'));
								//Ext.getCmp('past_due').setValue(record.get('pastdue'));
								//Ext.getCmp('over_due').setValue(record.get('overdue'));
								Ext.getCmp('remarks').setValue(record.get('remarks'));


								//Ext.getCmp('remarks').readOnly = false;
								Ext.getCmp('remarks').setReadOnly(false);
							}
						}
					},{
						xtype : 'datefield',
						id	  : 'repo_date',
						name  : 'repo_date',
						fieldLabel : '<b>Repo Date </b>',
						margin: '3 0 3 0',
						labelWidth: 80,
						width: 230,
						format : 'm/d/Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						//value: Ext.Date.format(new Date(), 'Y-m-d'),
					}]
				},{
					xtype: 'panel',
					id: 'mpanel',
					//width: 500,
					height: 330,
					margin: '0 0 2 2',
					layout: 'border',
					items: [{
						xtype: 'panel',
						id: 'west-region-container',
						title: 'Sales Invoice Info',
						region:'west',
						collapsible: false,   // make collapsible
						collapsed: false,
						border: true,
						items: [{
							xtype : 'datefield',
							id	  : 'release_date',
							name  : 'release_date',
							fieldLabel : '<b>Invoice Date </b>',
							allowBlank: false,
							//readOnly: true,
							margin: '3 3 3 0',
							labelWidth: 115,
							format : 'm/d/Y',
							fieldStyle: 'font-weight: bold; color: #210a04;'
						},{
							xtype: 'numberfield',
							fieldLabel: '<b>Term </b>',
							id: 'months_term',
							name: 'months_term',
							fieldStyle: 'text-align: right;',
							allowBlank: false,
							readOnly: true,
							margin: '3 3 3 0',
							labelWidth: 115,
							maxLength: 5,
							minValue: 0,
							value: 0,
							fieldStyle: 'font-weight: bold; color: #210a04;'
						},{
							xtype: 'numericfield',
							id: 'downpayment',
							name: 'downpayment',
							fieldLabel: '<b>Downpayment </b>',
							useThousandSeparator: true,
							decimalPrecision: 2,
							alwaysDisplayDecimals: true,
							allowNegative: false,
							allowBlank: false,
							readOnly: true,
							margin: '3 3 3 0',
							labelWidth: 115,
							thousandSeparator: ',',
							minValue: 0,
							fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
						},{
							xtype: 'numericfield',
							id: 'outs_ar_amount',
							name: 'outs_ar_amount',
							fieldLabel: '<b>Outstanding AR </b>',
							useThousandSeparator: true,
							decimalPrecision: 2,
							alwaysDisplayDecimals: true,
							allowNegative: false,
							allowBlank: false,
							readOnly: true,
							margin: '3 3 3 0',
							labelWidth: 115,
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
							allowBlank: false,
							readOnly: true,
							margin: '3 3 3 0',
							labelWidth: 115,
							thousandSeparator: ',',
							minValue: 0,
							fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
						},{
							xtype: 'numericfield',
							id: 'balance',
							name: 'balance',
							fieldLabel: '<b>Balance </b>',
							useThousandSeparator: true,
							decimalPrecision: 2,
							alwaysDisplayDecimals: true,
							allowNegative: false,
							allowBlank: false,
							readOnly: true,
							margin: '3 3 3 0',
							labelWidth: 115,
							thousandSeparator: ',',
							minValue: 0,
							fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
						},{
							xtype : 'datefield',
							id	  : 'firstdue_date',
							name  : 'firstdue_date',
							fieldLabel : '<b>First Due Date </b>',
							allowBlank: false,
							//readOnly: true,
							margin: '3 3 3 0',
							labelWidth: 115,
							format : 'm/d/Y',
							fieldStyle: 'font-weight: bold; color: #210a04;'
						},{
							xtype : 'datefield',
							id	  : 'maturity_date',
							name  : 'maturity_date',
							fieldLabel : '<b>Maturity Date </b>',
							allowBlank: false,
							//readOnly: true,
							margin: '3 3 3 0',
							labelWidth: 115,
							format : 'm/d/Y',
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
							margin: '0 0 0 0',
							items:[{
								xtype: 'textfield',
								fieldLabel: 'Invoice Ref# ',
								id: 'invoice_refno',
								name: 'invoice_refno',
								margin: '2 0 2 0',
								allowBlank: false,
								readOnly: true,
								labelWidth: 117,
								fieldStyle: 'font-weight: bold; color: #210a04;'
							},{
								xtype: 'combobox',
								id: 'category',
								name: 'category',
								fieldLabel: '<b>Category </b>',
								store: categorystore,
								displayField: 'name',
								valueField: 'id',
								queryMode: 'local',
								allowBlank: false,
								forceSelection: true,
								selectOnFocus:true,
								//readOnly: true,
								margin: '2 0 2 0',
								labelWidth: 70,
								width: 225,
								//flex: 1,
								fieldStyle: 'font-weight: bold; color: #210a04;'
							}]
						},{
							xtype: 'fieldcontainer',
							layout: 'hbox',
							margin: '0 0 0 0',
							items:[{
								xtype: 'numericfield',
								id: 'lcp_amount',
								name: 'lcp_amount',
								fieldLabel: '<b>LCP Amount </b>',
								useThousandSeparator: true,
								decimalPrecision: 2,
								alwaysDisplayDecimals: true,
								allowNegative: false,
								allowBlank: false,
								readOnly: true,
								margin: '2 0 2 0',
								labelWidth: 117,
								thousandSeparator: ',',
								minValue: 0,
								fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
							},{
								xtype: 'numericfield',
								id: 'addon_cost',
								name: 'addon_cost',
								fieldLabel: '<b>Add On </b>',
								useThousandSeparator: true,
								decimalPrecision: 2,
								alwaysDisplayDecimals: true,
								allowNegative: false,
								margin: '2 0 2 0',
								labelWidth: 70,
								width: 225,
								thousandSeparator: ',',
								minValue: 0,
								fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
							}]
						},{
							xtype: 'fieldcontainer',
							layout: 'hbox',
							margin: '0 0 0 0',
							items:[{
								xtype: 'numericfield',
								id: 'spotcash',
								name: 'spotcash',
								fieldLabel: '<b>Unit Cost </b>',
								useThousandSeparator: true,
								decimalPrecision: 2,
								alwaysDisplayDecimals: true,
								allowNegative: false,
								allowBlank: false,
								margin: '2 0 2 0',
								labelWidth: 117,
								readOnly: true,
								thousandSeparator: ',',
								minValue: 0,
								fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
							}]
						},{
							xtype: 'fieldcontainer',
							layout: 'hbox',
							margin: '0 0 0 0',
							items:[{
								xtype: 'numericfield',
								id: 'total_amount',
								name: 'total_amount',
								fieldLabel: '<b>Total Amount </b>',
								useThousandSeparator: true,
								decimalPrecision: 2,
								alwaysDisplayDecimals: true,
								allowNegative: false,
								allowBlank: false,
								readOnly: true,
								margin: '2 0 2 0',
								labelWidth: 117,
								thousandSeparator: ',',
								minValue: 0,
								fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
							}]
						},{
							xtype: 'fieldcontainer',
							layout: 'hbox',
							margin: '0 0 0 0',
							items:[{
								xtype: 'numericfield',
								id: 'unrecovrd_cost',
								name: 'unrecovrd_cost',
								fieldLabel: '<b>Unrecovered cost </b>',
								useThousandSeparator: true,
								decimalPrecision: 2,
								alwaysDisplayDecimals: true,
								allowNegative: false,
								allowBlank: false,
								readOnly: true,
								margin: '2 0 2 0',
								labelWidth: 125,
								width: 292,
								thousandSeparator: ',',
								minValue: 0,
								fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
							}]
						},{
							xtype: 'fieldcontainer',
							layout: 'hbox',
							margin: '0 0 0 0',
							items:[{
								xtype: 'numericfield',
								id: 'total_unrecovrd',
								name: 'total_unrecovrd',
								fieldLabel: '<b>Total unrecovered </b>',
								useThousandSeparator: true,
								decimalPrecision: 2,
								alwaysDisplayDecimals: true,
								allowNegative: false,
								allowBlank: false,
								readOnly: true,
								labelWidth: 125,
								width: 292,
								thousandSeparator: ',',
								minValue: 0,
								fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
							}]
						},{
							xtype: 	'textareafield',
							fieldLabel: 'Remarks ',
							id:	'remarks',
							name: 'remarks',
							labelAlign:	'top',
							allowBlank: false,
							margin: '0 0 0 2',
							maxLength: 254,
							width: 515,
							hidden: false
						}]
					}]
				}]
			}]
		},{
			xtype: 'tabpanel',
			activeTab: 0,
			width: 860,
			scale: 'small',
			items:[{
				xtype:'gridpanel',
				id: 'ItemGrid',
				anchor:'100%',
				layout:'fit',
				title: 'Item Details',
				icon: '../../js/ext4/examples/shared/icons/lorry_flatbed.png',
				loadMask: true,
				store:	RepoitemStore,
				columns: Item_view,
				plugins: [cellEditing],
				columnLines: true
			}]
		}]
	});
	var submit_window = Ext.create('Ext.Window',{
		id: 'submit_window',
		width 	: 850,
		modal	: true,
		plain 	: true,
		border 	: true,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form],
		buttons:[{
			text: 'Process',
			id: 'btnsave',
			tooltip: 'Save Repo Transaction',
			icon: '../../js/ext4/examples/shared/icons/add.png',
			single : true,
			handler:function(){
				var form_submit = Ext.getCmp('form_submit').getForm();
				if(form_submit.isValid()) {
					form_submit.submit({
						url: '?submit=redemrepo',
						waitMsg: 'Processing transaction. please wait...',
						method:'POST',
						submitEmptyText: false,
						success: function(form_submit, action) {
							RedempStore.load()
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
			id: 'btncancel',
			tooltip: 'Cancel adding Repo',
			icon: '../../js/ext4/examples/shared/icons/cancel.png',
			handler:function(btnc){
				if(btnc.text == "Cancel"){
					Ext.MessageBox.confirm('Confirm:', 'Are you sure you wish to close this window?', function (btn, text) {
						if (btn == 'yes') {
							submit_window.close();
						}
					});
				}else{
					submit_window.close();
				}
			}
		}]
	});
	//------------------------------------: main grid :----------------------------------------
	var REPO_GRID =  Ext.create('Ext.panel.Panel', { 
        renderTo: 'ext-form',
		id: 'REPO_GRID',
        frame: false,
		width: 1300,
		tbar: tbar,
		items: [{
			xtype: 'grid',
			id: 'repoGrd',
			store:	RedempStore,
			columns: ColumnModel,
			columnLines: true,
			autoScroll:true,
			layout:'fit',
			frame: true,
			bbar : {
				xtype : 'pagingtoolbar',
				hidden: false,
				store : RedempStore,
				pageSize : itemsPerPage,
				displayInfo : false,
				emptyMsg: "No records to display",
				doRefresh : function(){
					RedempStore.load();
					
				}
			}
		}]
	});
});
