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
    Ext.define('RepoModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'ar_trans_no', mapping:'ar_trans_no'},
			{name:'ar_trans_type', mapping:'ar_trans_type'},
			{name:'trans_date', mapping:'trans_date'},
			{name:'type', mapping:'type'},
			{name:'repo_date', mapping:'repo_date'},
			{name:'repo_type', mapping:'repo_type'},
			{name:'reference_no', mapping:'reference_no'},
			{name:'debtor_no', mapping:'debtor_no'},
			{name:'name', mapping:'name'},
			{name:'debtor_ref', mapping:'debtor_ref'},
			{name:'lcp_amount', mapping:'lcp_amount'},
			{name:'downpayment', mapping:'downpayment'},
			{name:'outstanding_ar', mapping:'outstanding_ar'},
			{name:'amortization_amount', mapping:'amortization_amount'},
			{name:'term', mapping:'term'},
			{name:'release_date', mapping:'release_date'},
			{name:'firstdue_date', mapping:'firstdue_date'},
			{name:'maturity_date', mapping:'maturity_date'},
			{name:'balance', mapping:'balance'},
			{name:'spot_cash_amount', mapping:'spot_cash_amount'},
			{name:'total_amount', mapping:'total_amount'},
			{name:'unrecovered_cost', mapping:'unrecovered_cost'},
			{name:'addon_amount', mapping:'addon_amount'},
			{name:'total_unrecovered', mapping:'total_unrecovered'},
			{name:'over_due', mapping:'over_due'},
			{name:'past_due', mapping:'past_due'},
			{name:'category_id', mapping:'category_id'},
			{name:'category_desc', mapping:'category_desc'},
			{name:'branch_code', mapping:'branch_code'},
			{name:'comments', mapping:'comments'},
			{name:'gpm', mapping:'gpm'}
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
			{name:'tran_date', mapping:'tran_date'},
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
			{name:'CGPM', mapping:'CGPM'},
			{name:'remarks', mapping:'remarks'},
			{name:'base_transno', mapping:'base_transno'},
			{name:'base_transtype', mapping:'base_transtype'}
		]
    });
	Ext.define('item_delailsModel',{
		extend : 'Ext.data.Model',
		fields : [
			{name:'stock_id',mapping:'stock_id'},
			{name:'description',mapping:'description'},
			{name:'qty',mapping:'qty'},
			{name:'unit_price',mapping:'unit_price',type:'float'},
			{name:'serial',mapping:'serial'},
			{name:'chasis',mapping:'chasis'},
			{name:'color_code',mapping:'color_code'}
		]
	});
    Ext.define('mtModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'desc', mapping:'desc'},
			{name:'fk_id', mapping:'fk_id'}
		]
    });
	//------------------------------------: stores :----------------------------------------
	var rTypeStore = Ext.create('Ext.data.Store',{
		fields: ['id','name'],
		autoLoad: true,
		data : 	[
            {"id":"new","name":"New"},
            {'id':'repo','name':'Repo'},
			{"id":"replcmnt","name":"Replacement"},
			{"id":"mt","name":"Merchandise Transfer"},
			{"id":"trmode","name":"AR Term mode"},
			{"id":"openar","name":"AR Opening"},
			{"id":"arlend","name":"From AR lending"}
        ]
	});
	var CustomerStore = Ext.create('Ext.data.Store', {
		model: 'CustomersModel',
		//autoLoad : true,
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
	var RepoDetailsStore = Ext.create('Ext.data.Store', {
		model: 'RepoModel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_repodetails=zHun',
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
    var mtstore = Ext.create('Ext.data.Store', {
		name: 'mtstore',
        model: 'mtModel',
		//autoLoad : true,
        proxy: {
			url: '?getmtItem=00',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'desc',
			direction : 'ASC'
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
					var records = RepoDetailsStore.getAt(rowIndex);

					CustomerStore.proxy.extraParams = {debtor_ref: records.get('debtor_ref')};
					CustomerStore.load();

					SIitemStore.proxy.extraParams = {repo_id: records.get('id')};
					SIitemStore.load();

					Ext.getCmp('InvoiceNo').setVisible(false);
					Ext.getCmp('vw_InvoiceNo').setVisible(true);
					//Ext.getCmp('btncancel').setVisible(false);
					Ext.getCmp('btnsave').setVisible(false);
					Ext.getCmp('btncancel').setText('Close');

					Ext.getCmp('sysid').setValue(records.get('id'));
					Ext.getCmp('transtype').setValue(records.get('ar_trans_type'));
					Ext.getCmp('customercode').setValue(records.get('debtor_ref'));
					Ext.getCmp('customername').setValue(records.get('debtor_no'));
					Ext.getCmp('repo_type').setValue(records.get('repo_type'));
					//Ext.getCmp('InvoiceNo').setValue(records.get('ar_trans_no'));
					Ext.getCmp('vw_InvoiceNo').setValue(records.get('reference_no') + ' > ' + records.get('category_desc') + ' > ' + records.get('ar_trans_no'));
					Ext.getCmp('repo_date').setValue(records.get('repo_date'));
					Ext.getCmp('release_date').setValue(records.get('release_date'));
					Ext.getCmp('months_term').setValue(records.get('term'));
					Ext.getCmp('downpayment').setValue(records.get('downpayment'));
					Ext.getCmp('outs_ar_amount').setValue(records.get('outstanding_ar'));
					Ext.getCmp('amort_amount').setValue(records.get('amortization_amount'));
					Ext.getCmp('balance').setValue(records.get('balance'));
					Ext.getCmp('firstdue_date').setValue(records.get('firstdue_date'));
					Ext.getCmp('maturity_date').setValue(records.get('maturity_date'));
					Ext.getCmp('reference_no').setValue(records.get('reference_no'));
					Ext.getCmp('category').setValue(records.get('category_id'));
					Ext.getCmp('lcp_amount').setValue(records.get('lcp_amount'));
					Ext.getCmp('over_due').setValue(records.get('over_due'));
					Ext.getCmp('spotcash').setValue(records.get('spot_cash_amount'));
					Ext.getCmp('past_due').setValue(records.get('past_due'));
					Ext.getCmp('total_amount').setValue(records.get('total_amount'));
					Ext.getCmp('addon_cost').setValue(records.get('addon_amount'));
					Ext.getCmp('unrecovrd_cost').setValue(records.get('unrecovered_cost'));
					Ext.getCmp('gpm').setValue(records.get('gpm'));
					Ext.getCmp('total_unrecovrd').setValue(records.get('total_unrecovered'));
					Ext.getCmp('remarks').setValue(records.get('comments'));

					submit_window.setTitle('Receiving Report Repo Details - Reference No. :'+ records.get('reference_no'));
					submit_window.show();
					submit_window.setPosition(320,55);
				}
			},'-',{
				icon   : '../../js/ext4/examples/shared/icons/chart_line.png',
				tooltip : 'Entries',
				handler : function(grid, rowIndex, colIndex){
					var records = RepoDetailsStore.getAt(rowIndex);
					window.open('../../gl/view/gl_trans_view.php?type_id='+ records.get('type') +'&trans_no='+ records.get('id'));
				}
			},'-',{
				icon: '../../js/ext4/examples/shared/icons/print-preview-icon.png',
				tooltip: 'view receiving report repo',
				handler: function(grid, rowIndex, colIndex) {
					var records = RepoDetailsStore.getAt(rowIndex);
					var win = new Ext.Window({
						autoLoad:{
							url:'../../reports/rr_repo.php?&reference=' + records.get('reference_no'),
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
						src:'../../reports/rr_repo.php?&reference=' + records.get('reference_no'),
						width:'100%',
						height:'100%',
						frameborder:0
					}
					win.show();
					Ext.DomHelper.insertFirst(win.body, iframe)
					//---//
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
		{header:'<b>Serial No.</b>', dataIndex:'serial_no', width:200,
			renderer : function(value, metaData, summaryData, dataIndex){
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Chasis No.</b>', dataIndex:'chassis_no', width:200,
			renderer : function(value, metaData, summaryData, dataIndex){
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		}/*,
		{header:'<b>Color code</b>', dataIndex:'color_code', width:200,
			renderer : function(value, metaData, summaryData, dataIndex){
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		}*/
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
		store: RepoDetailsStore,
		listeners: {
			change: function(field) {
				RepoDetailsStore.proxy.extraParams = {query: field.getValue()};
				RepoDetailsStore.load();
			}
		}
	}, '-', {
		text:'<b>Add</b>',
		tooltip: 'Add new repossesse transaction.',
		icon: '../../js/ext4/examples/shared/icons/add.png',
		scale: 'small',
		handler: function(){
			submit_form.getForm().reset();
			
			CustomerStore.proxy.extraParams = {rtype: 'new', debtor_id: ''};
			CustomerStore.load();

			Ext.getCmp('repo_type').setValue("new");
			Ext.getCmp('btnsave').setVisible(true);
			//Ext.getCmp('btncancel').setVisible(true);
			Ext.getCmp('btncancel').setText('Cancel');
			Ext.getCmp('InvoiceNo').setVisible(true);
			Ext.getCmp('vw_InvoiceNo').setVisible(false);
			Ext.getCmp('cBranch').setVisible(false);
			Ext.getCmp('mt_ref').setVisible(false);
			
			submit_window.show();
			submit_window.setTitle('Receiving Report Repo - Add');
			submit_window.setPosition(320,23);
		}
	}];
	var submit_form = Ext.create('Ext.form.Panel', {
		id: 'form_submit',
		model: 'RepoModel',
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
						xtype: 'combobox',
						id: 'cBranch',
						name: 'cBranch',
						fieldLabel: 'Branch ',
						allowBlank: false,
						store : Branchstore,
						displayField: 'name',
						valueField: 'id',
						queryMode: 'local',
						emptyText: "Select From Branch",
						labelWidth: 80,
						width: 410,
						hidden: true,
						forceSelection: true,
						selectOnFocus:true,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						listeners: {
							select: function(combo, record, index) {
								mtstore.proxy.extraParams = {from_branch: record.get('id')};
								mtstore.load();
							}
						}
					},{
						xtype: 'combobox',
						id: 'mt_ref',
						name: 'mt_ref',
						fieldLabel: 'MT Reference ',
						allowBlank: false,
						store : mtstore,
						displayField: 'desc',
						valueField: 'id',
						queryMode: 'local',
						width: 410,
						hidden: true,
						forceSelection: true,
						selectOnFocus:true,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						listeners: {
							select: function(combo, record, index) {
								CustomerStore.proxy.extraParams = {repo_id: record.get('fk_id'), from_branch: Ext.getCmp('cBranch').getValue(), rtype: Ext.getCmp('repo_type').getValue()};
								CustomerStore.load();
								ARInvoiceStore.proxy.extraParams = {repo_id: record.get('fk_id'), from_branch: Ext.getCmp('cBranch').getValue(), rtype: Ext.getCmp('repo_type').getValue()};
								ARInvoiceStore.load();
								SIitemStore.proxy.extraParams = {repo_id: record.get('fk_id'), from_branch: Ext.getCmp('cBranch').getValue(), rtype: Ext.getCmp('repo_type').getValue()};
								SIitemStore.load();
							}
						}
					}]
				},{
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
						forceSelection: true,
						selectOnFocus:true,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						listeners: {
							select: function(combo, record, index) {
								Ext.getCmp('customercode').setValue(record.get('debtor_ref'));
								Ext.getCmp('custname').setValue(record.get('name'));

								if(Ext.getCmp('repo_type').getValue() != 'mt'){
									ARInvoiceStore.proxy.extraParams = {debtor_id: record.get('debtor_no'), repo_date: Ext.getCmp('repo_date').getValue(), rtype: Ext.getCmp('repo_type').getValue()};
									ARInvoiceStore.load();
									SIitemStore.proxy.extraParams = {transNo: 0};
									SIitemStore.load();
								}
								Ext.getCmp('InvoiceNo').setValue();
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
								Ext.getCmp('gpm').setValue();
								//Ext.getCmp('cgpm').setValue();
								Ext.getCmp('addon_cost').setValue();
								Ext.getCmp('total_amount').setValue();
								Ext.getCmp('unrecovrd_cost').setValue();
								Ext.getCmp('total_unrecovrd').setValue();
								Ext.getCmp('past_due').setValue();
								Ext.getCmp('over_due').setValue();
								Ext.getCmp('base_transno').setValue();
								Ext.getCmp('base_transtype').setValue();

								Ext.Ajax.request({
									url : '?getReference=zHun',
									async:false,
									success: function (response){
										var result = Ext.JSON.decode(response.responseText);
										Ext.getCmp('reference_no').setValue(result.reference);
										submit_window.setTitle('Receiving Report Repo -: '+ result.reference + ' *new');
									}
								});
							}
						}
					},{
						xtype: 'combobox',
						id: 'repo_type',
						name: 'repo_type',
						fieldLabel: '<b>Type </b>',
						store: rTypeStore,
						displayField: 'name',
						valueField: 'id',
						queryMode: 'local',
						emptyText:'type of repo',
						labelWidth: 50,
						width: 230,
						forceSelection: true,
						selectOnFocus:true,
						editable: false,
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						listeners: {
							select: function(combo, record, index) {
								CustomerStore.proxy.extraParams = {rtype: record.get('id')};
								CustomerStore.load();
								ARInvoiceStore.proxy.extraParams = {debtor_id: 0};
								ARInvoiceStore.load();
								Ext.getCmp('customercode').setValue();
								Ext.getCmp('customername').setValue();
								Ext.getCmp('InvoiceNo').setValue();
								Ext.getCmp('release_date').setValue();
								Ext.getCmp('months_term').setValue();
								Ext.getCmp('downpayment').setValue();
								Ext.getCmp('outs_ar_amount').setValue();
								Ext.getCmp('amort_amount').setValue();
								Ext.getCmp('balance').setValue();
								Ext.getCmp('firstdue_date').setValue();
								Ext.getCmp('maturity_date').setValue();
								Ext.getCmp('reference_no').setValue();
								Ext.getCmp('category').setValue();
								Ext.getCmp('lcp_amount').setValue();
								Ext.getCmp('over_due').setValue();
								Ext.getCmp('spotcash').setValue();
								Ext.getCmp('past_due').setValue();
								Ext.getCmp('total_amount').setValue();
								Ext.getCmp('addon_cost').setValue();
								Ext.getCmp('unrecovrd_cost').setValue();
								Ext.getCmp('gpm').setValue();
								Ext.getCmp('total_unrecovrd').setValue();
								Ext.getCmp('remarks').setValue();
								Ext.getCmp('base_transno').setValue();
								Ext.getCmp('base_transtype').setValue();
								Ext.getCmp('custname').setValue();
								Ext.getCmp('cBranch').setValue();
								Ext.getCmp('mt_ref').setValue();

								if(record.get('id') == 'mt'){
									Ext.getCmp('cBranch').setVisible(true);
									Ext.getCmp('mt_ref').setVisible(true);
								}else{
									Ext.getCmp('cBranch').setVisible(false);
									Ext.getCmp('mt_ref').setVisible(false);
								}
							}
						}
					}]
				},{
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
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
						labelWidth: 80,
						width: 590,
						forceSelection: true,
						selectOnFocus:true,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						listeners: {
							select: function(combo, record, index) {
								if(Ext.getCmp('repo_type').getValue() != 'mt'){
									SIitemStore.proxy.extraParams = {transNo: record.get('id'), transtype: record.get('type'), amount: record.get('unrecoverd'), rtype: Ext.getCmp('repo_type').getValue(), base_transno: record.get('base_transno'), base_transtype: record.get('base_transtype')};
									SIitemStore.load();
								}

								Ext.getCmp('transtype').setValue(record.get('type'));
								Ext.getCmp('release_date').setValue(record.get('tran_date'));
								Ext.getCmp('months_term').setValue(record.get('term'));
								Ext.getCmp('downpayment').setValue(record.get('dp_amount'));
								Ext.getCmp('outs_ar_amount').setValue(record.get('out_ar'));
								Ext.getCmp('amort_amount').setValue(record.get('monthly_amort'));
								Ext.getCmp('balance').setValue(record.get('balance'));
								Ext.getCmp('firstdue_date').setValue(record.get('first_duedate'));
								Ext.getCmp('maturity_date').setValue(record.get('maturty_date'));
								Ext.getCmp('category').setValue(record.get('category_id'));
								Ext.getCmp('lcp_amount').setValue(record.get('lcp_amount'));
								Ext.getCmp('spotcash').setValue(record.get('unit_cost'));
								Ext.getCmp('gpm').setValue(record.get('GPM'));
								//Ext.getCmp('cgpm').setValue(record.get('CGPM'));
								Ext.getCmp('addon_cost').setValue(record.get('addon_amount'));
								Ext.getCmp('total_amount').setValue(record.get('unrecoverd'));
								Ext.getCmp('unrecovrd_cost').setValue(record.get('unrecoverd'));
								Ext.getCmp('total_unrecovrd').setValue(record.get('totalunrecoverd'));
								Ext.getCmp('past_due').setValue(record.get('pastdue'));
								Ext.getCmp('over_due').setValue(record.get('overdue'));
								Ext.getCmp('remarks').setValue(record.get('remarks'));
								Ext.getCmp('base_transno').setValue(record.get('base_transno'));
								Ext.getCmp('base_transtype').setValue(record.get('base_transtype'));
							}
						}
					},{
						xtype: 'textfield',
						fieldLabel: 'Customer ',
						id: 'vw_InvoiceNo',
						name: 'vw_InvoiceNo',
						fieldLabel : 'Invoice No. ',
						//allowBlank: false,
						labelWidth: 80,
						width: 590,
						readOnly: true,
						fieldStyle: 'font-weight: bold; color: #210a04;'
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
						value: Ext.Date.format(new Date(), 'Y-m-d'),
						listeners: {
							change: function(combo, record, index) {
								ARInvoiceStore.proxy.extraParams = {debtor_id: 0};
								ARInvoiceStore.load();
								SIitemStore.proxy.extraParams = {transNo: 0};
								SIitemStore.load();

								Ext.getCmp('InvoiceNo').setValue();
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
								Ext.getCmp('gpm').setValue();
								//Ext.getCmp('cgpm').setValue();
								Ext.getCmp('addon_cost').setValue();
								Ext.getCmp('total_amount').setValue();
								Ext.getCmp('unrecovrd_cost').setValue();
								Ext.getCmp('total_unrecovrd').setValue();
								Ext.getCmp('past_due').setValue();
								Ext.getCmp('over_due').setValue();
								Ext.getCmp('base_transno').setValue();
								Ext.getCmp('base_transtype').setValue();
								Ext.getCmp('cBranch').setValue();
								Ext.getCmp('mt_ref').setValue();
							}
						}
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
							readOnly: true,
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
							readOnly: true,
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
							readOnly: true,
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
								fieldLabel: 'Reference No.',
								id: 'reference_no',
								name: 'reference_no',
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
								readOnly: true,
								margin: '2 0 2 0',
								labelWidth: 70,
								width: 225,
								//flex: 1,
								fieldStyle: 'font-weight: bold; color: #210a04;',
								listeners: {
									select: function(combo, record, index) {

									}
								}
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
								id: 'over_due',
								name: 'over_due',
								fieldLabel: '<b>Over Due</b>',
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
							},{
								xtype: 'numericfield',
								id: 'past_due',
								name: 'past_due',
								fieldLabel: '<b>Past Due</b>',
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
							/*},{
								xtype: 'textfield',
								id: 'cgpm',
								name: 'cgpm',
								readOnly: true,
								allowBlank: false,
								width: 50,
								margin: '2 0 2 0',
								fieldStyle: 'font-weight: bold;color: #4a235a; text-align: right;'*/
							},{
								xtype: 'textfield',
								id: 'gpm',
								name: 'gpm',
								readOnly: true,
								allowBlank: false,
								width: 50,
								margin: '2 0 2 0',
								fieldStyle: 'font-weight: bold;color: #4a235a; text-align: right;'
							}]
						},{
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
			xtype:'gridpanel',
			id: 'ItemGrid',
			anchor:'100%',
			layout:'fit',
			title: 'Item Details',
			icon: '../../js/ext4/examples/shared/icons/lorry_flatbed.png',
			loadMask: true,
			store:	SIitemStore,
			columns: Item_view,
			columnLines: true
		}]
	});
	var submit_window = Ext.create('Ext.Window',{
		id: 'submit_window',
		width 	: 840,
		modal	: true,
		plain 	: true,
		border 	: true,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form],
		buttons:[{
			text: 'Save',
			id: 'btnsave',
			tooltip: 'Save Repo Transaction',
			icon: '../../js/ext4/examples/shared/icons/add.png',
			single : true,
			handler:function(){
				var form_submit = Ext.getCmp('form_submit').getForm();
				if(form_submit.isValid()) {
					var gridData = SIitemStore.getRange();
					var gridRepoData = [];
					count = 0;
					Ext.each(gridData, function(item) {
						var ObjItem = {
							stock_id: item.get('stock_id'),  
							description: item.get('description'),
							qty: item.get('qty'),
							unit_price: item.get('unit_price'),
							serial: item.get('serial'),
							chasis: item.get('chasis')
						};
						gridRepoData.push(ObjItem);
					});
					form_submit.submit({
						url: '?submit=repoinfo',
						params: {
							DataOnGrid: Ext.encode(gridRepoData)
						},
						waitMsg: 'Processing transaction. please wait...',
						method:'POST',
						submitEmptyText: false,
						success: function(form_submit, action) {
							RepoDetailsStore.load()
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
			store:	RepoDetailsStore,
			columns: ColumnModel,
			columnLines: true,
			autoScroll:true,
			layout:'fit',
			frame: true,
			bbar : {
				xtype : 'pagingtoolbar',
				hidden: false,
				store : RepoDetailsStore,
				pageSize : itemsPerPage,
				displayInfo : false,
				emptyMsg: "No records to display",
				doRefresh : function(){
					RepoDetailsStore.load();
					
				}
			}
		}]
	});
});
