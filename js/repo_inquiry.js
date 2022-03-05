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

	//-------THIS IS FROM repo_accounts TABLE--------//
	Ext.define('repotypeModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'ar_installment_id', mapping:'ar_installment_id'},
			{name:'repo_date', mapping:'repo_date'},
			{name:'reference_no', mapping:'reference_no'},
			{name:'debtor_no', mapping:'debtor_no'},
			{name:'category_id', mapping:'category_id'},
			{name:'total_amount', mapping:'total_amount'},
			{name:'unrecovered_cost', mapping:'unrecovered_cost'}
		]
    });

	//------THIS IS FROM ar_installment TABLE-----//
    Ext.define('RepoModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'reference', mapping:'reference'},
			{name:'tran_date', mapping:'tran_date'},
			{name:'lcp_amount', mapping:'lcp_amount'},
			{name:'months_term', mapping:'months_term'},
			{name:'description', mapping:'description'},
			{name:'category_id', mapping:'category_id'},
			{name:'debtor_no', mapping:'debtor_no'},
			{name:'name', mapping:'name'},
			{name:'debtor_ref', mapping:'debtor_ref'},
			{name:'downpayment_amount', mapping:'downpayment_amount'},
			{name:'firstdue_date', mapping:'firstdue_date'},
			{name:'maturity_date', mapping:'maturity_date'},
			{name:'outstanding_ar_amount', mapping:'outstanding_ar_amount'},
			{name:'ar_amount', mapping:'ar_amount'},
			{name:'amortization_amount', mapping:'amortization_amount'},
			{name:'total_amount', mapping:'total_amount'},		
			{name:'reference_no', mapping:'reference_no'},			
			{name:'orig_branch_code', mapping:'orig_branch_code'},		
			{name:'installmentplcy_id', mapping:'installmentplcy_id'},		
			{name:'rebate', mapping:'rebate'},		
			{name:'payment_status', mapping:'payment_status'},		
			{name:'orig_branch_code', mapping:'orig_branch_code'},		
			//{name:'comments', mapping:'comments'},		
			//{name:'datetoday', mapping:'datetoday'},		
		]
    });
    //--------------------------------------------//

    Ext.define('comboModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'stock_id', mapping:'stock_id'},
			{name:'quantity', mapping:'quantity'},
			{name:'lot_no', mapping:'lot_no'},
			{name:'chassis_no', mapping:'chassis_no'},
			{name:'unit_price', mapping:'unit_price'},
		]
    });

    Ext.define('RepotodueksamModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'ar_ref_no', mapping:'ar_ref_no'},
			{name:'name', mapping:'name'},
			{name:'debtor_no', mapping:'debtor_no'},
			{name:'description', mapping:'description'},
			{name:'category_id', mapping:'category_id'},
			{name:'repo_date', mapping:'repo_date'},
			{name:'total_amount', mapping:'total_amount'},
			{name:'unrecovered_cost', mapping:'unrecovered_cost'},
		]
    });

    //----------FOR INVOICE NUMBER----------//
    var Invoicestore = Ext.create('Ext.data.Store', {
		//name: 'Invoicestore',
        model: 'RepoModel',
		autoLoad : true,
        proxy: {
			url: '?getInvoicenumber=00',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'reference',
			direction : 'ASC'
		}]
	});
	//-----------------------------------//

	var repotypestore = Ext.create('Ext.data.Store', {
        model: 'repotypeModel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
        proxy: {
            //url: '?vwpolicytyp=00',
            type: 'ajax',
            reader: {
                type: 'json',
                root: 'result',
                totalProperty  : 'total'
            }
		},
		simpleSortMode : true,
		sorters : [{
			property : 'dateadded',
			direction : 'DESC'
		}]
    });


    var forrepostore = Ext.create('Ext.data.Store', {
        model: 'comboModel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
        proxy: {
            url: '?Getinvoicerepo=00',
            type: 'ajax',
            reader: {
                type: 'json',
                root: 'result',
                totalProperty  : 'total'
            }
		},
		simpleSortMode : true,
		sorters : [{
			property : 'reference',
			direction : 'ASC'
		}]
    });


    var repotodueksamstore = Ext.create('Ext.data.Store', {
        model: 'RepotodueksamModel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
        proxy: {
            url: '?Getrepo=00',
            type: 'ajax',
            reader: {
                type: 'json',
                root: 'result',
                totalProperty  : 'total'
            }
		},
		simpleSortMode : true,
		sorters : [{
			property : 'ar_ref_no',
			direction : 'ASC'
		}]
    });

	var ColumnModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Id</b>',dataIndex:'id',hidden: true},
		//{header:'<b>Invoice number</b>', dataIndex:'ar_installment_id', sortable:true, width:165},
		{header:'<b>Repo number</b>', dataIndex:'ar_ref_no', sortable:true, width:250},
		{header:'<b>Customer Name</b>', dataIndex:'name', sortable:true, width:250},
		{header:'<b>Category</b>', dataIndex:'description', sortable:true, width:175},
		{header:'<b>Repo Date</b>', dataIndex:'repo_date', sortable:true, width:165, renderer: Ext.util.Format.dateRenderer('m-d-Y')},
		{header:'<b>Total Amount</b>', dataIndex:'total_amount', sortable:true, width:165,
			renderer: Ext.util.Format.Currency = function(value){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>Unrecover Cost</b>', dataIndex:'unrecovered_cost', sortable:true, width:165,
			renderer: Ext.util.Format.Currency = function(value){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},

		/*
		{header:'<b>Approved</b>', dataIndex:'status', sortable:true, width:100,
			renderer:function(value,metaData){
				if (value === 'Yes'){
					metaData.style="color:#229954";
				}else{
					metaData.style="color:#900C3F";
				}
				return "<b>" + value + "</b>";
			}
		},

	
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:100,
			items:[{
				icon: '../../js/ext4/examples/shared/icons/application_form_edit.png',
				tooltip: 'Edit',
				handler: function(grid, rowIndex, colIndex) {
					var records = repotypestore.getAt(rowIndex);
					submit_form.getForm().reset();
					Ext.getCmp('syspk').setValue(records.get('id'));
					Ext.getCmp('moduletype').setValue(records.get('module_type'));
					Ext.getCmp('brancharea').setValue(records.get('brancharea_id'));
					Ext.getCmp('category').setValue(records.get('category_id'));
					Ext.getCmp('plcycode').setValue(records.get('plcy_code'));
					submit_form.down('radiogroup').setValue({tax_included: records.get('tax_included')})
					Ext.getCmp('remarks').setValue(records.get('remarks'));
					Ext.getCmp('inactive').show();
					if (records.get('status') === 'Yes'){
						Ext.getCmp('inactive').setValue(0);
					}else{
						Ext.getCmp('inactive').setValue(1);
					}
					submit_window.setTitle('Installment Policy Type Maintenance  - Edit');
					submit_window.show();
					submit_window.setPosition(500,110);
				}
			},'-',{
				icon   : '../../js/ext4/examples/shared/icons/fam/delete.png',
				tooltip : 'Delete',
				handler : function(grid, rowIndex, colIndex){
					var records = repotypestore.getAt(rowIndex);
					var MsgConfirm = Ext.MessageBox.confirm('Confirm', 'Installment policy code: <b>' + records.get('plcy_code') + '</b><br\> Are you sure you want to delete this record? ', function (btn, text) {
						if (btn == 'yes') {
							Ext.Ajax.request({
								method: 'POST',
								url: '?delete=info',
								waitMsg:'Deleting Record...please wait.',
								params: {
									syspk: records.get('id'),
									plcycode: records.get('plcy_code')
								},
								success: function (response){
									var data = Ext.decode(response.responseText);
									var gridplcy = Ext.getCmp('instllplcygrid');
									if (data.success == 'true') {
										Ext.Msg.alert('Success', data.message);
										repotypestore.load();
										if (gridplcy.getCount() = 0){
											vwbranchtore.load();
										}
									}else{
										Ext.Msg.alert('Error', data.message);
									}
								}
							});
						}
					});
					MsgConfirm.defaultButton = 2;
				}
			}],
			renderer:function(value,metaData){
				metaData.style="background-color:#D3D3D3";
				return value;
			}
		}
		*/
	];

	var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
        clicksToEdit: 1
    });

    var currentDate = new Date();


	//------COLUMN FOR FROM GRID-------//
	var ForRepoModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Id</b>',dataIndex:'id',hidden: true},
		{header:'<b>Model</b>', dataIndex:'stock_id', sortable:true, width:173},
		{header:'<b>Qty</b>', dataIndex:'quantity', sortable:true, width:80,
			editor: {
                xtype: 'numberfield',
                allowBlank: false,
                minValue: 0,
                maxValue: 100000
            }
		},

		{header:'<b>Unit Price</b>', dataIndex:'unit_price', sortable:true, width:165,
			renderer: Ext.util.Format.Currency = function(value){
				return Ext.util.Format.number(value, '0,000.00');
			}
		},
		{header:'<b>Serial number</b>', dataIndex:'lot_no', sortable:true, width:170},
		{header:'<b>Chasis number</b>', dataIndex:'chassis_no', sortable:true, width:170},
		


		{header:'<b></b>',xtype:'actioncolumn', align:'center', width:30,
			items:[{
				icon   : '../../js/ext4/examples/shared/icons/fam/delete.png',
				tooltip: 'Remove',
				handler: function(grid, rowIndex, colIndex) {
				var records = forrepostore.getAt(rowIndex);
                    forrepostore.removeAt(rowIndex);
				}
			}]
		}
	];
	//--------SEARCHBOX-------//
	var tbar = [{
		xtype: 'searchfield',
		id:'search',
		name:'search',
		fieldLabel: '<b>Search</b>',
		labelWidth: 50,
		width: 300,
		emptyText: "Search by Repo Number...",
		scale: 'small',
		store: repotodueksamstore,
		listeners: {
			change: function(field) {
				var repodueksamgrid = Ext.getCmp('repodueksamgrid');

				var selected = repodueksamgrid.getSelectionModel().getSelection();
				var areaselected;
				Ext.each(selected, function (item) {
					areaselected = item.data.id;
				});

				if(field.getValue() != ""){
					repotodueksamstore.proxy.extraParams = {REFRENCE: areaselected, showall: showall, query: field.getValue()};
					repotodueksamstore.load();
				}else{
					repotodueksamstore.proxy.extraParams = {REFRENCE: areaselected, showall: showall};
					repotodueksamstore.load();
				}
			}
		}

		//-------ADD BUTTON FOR FORM-----//
		}, '-', {
		text:'<b>Add New Repo</b>',
		tooltip: 'Add New Repo.',
		icon: '../../js/ext4/examples/shared/icons/add.png',
		scale: 'small',
		handler: function(){
			//Ext.Msg.alert('Error', newURL);
			submit_form.getForm().reset();
			Invoicestore.load();
			forrepostore.proxy.extraParams = {ARREFNO: "00"};
			forrepostore.load();
			repotodueksamstore.load();
			//categorystore.load();
			//Ext.getCmp('inactive').hide();
			//submit_form.down('radiogroup').setValue({tax_included: 1})
			submit_window.show();
			//Ext.getCmp('moduletype').setValue("INSTLPLCYTYPS");
			submit_window.setTitle('Repo Inquiry Maintenance - Add');
			submit_window.setPosition(200,23);
		}
	}];


	//--------FOR WINDOW FORM--------//
	var submit_form = Ext.create('Ext.form.Panel', {
		id: 'form_submit',
		model: 'RepoModel',
		frame: false,
		border: true,
		defaults: {msgTarget: 'side', labelWidth: 95, anchor: '-10'}, 
			items: [{
				xtype: 'textfield',
				id: 'syspk',
				name: 'syspk',
				fieldLabel: 'syspk',
				//allowBlank: false,
				hidden: true
			},{	
				xtype:'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					xtype: 'combobox',
					id: 'reference',
					name: 'reference',
					fieldLabel: '<b>AR #</b>',
					store: Invoicestore,
					displayField: 'reference',
					valueField: 'reference',
					queryMode: 'local',
					emptyText:'Select AR number',
					padding: '2 5 0 0',
					maxLength: 150,
					labelWidth: 120,
					width: 400,
					height: 35,
					forceSelection: true,
					selectOnFocus:true,
					//readOnly: true,
					fieldStyle: 'font-weight: bold; font-size:15px; color: black; text-align: left;',
					flex: 1,
					listeners: {
				
						/*
						change: function(field) {
							var Areagrid = Ext.getCmp('Areagrid');

							var selected = Areagrid.getSelectionModel().getSelection();
							var areaselected;
							Ext.each(selected, function (item) {
								areaselected = item.data.id;
							});

							if(search.getValue() != ""){
								forrepostore.proxy.extraParams = {id: areaselected, showall: showall, query: search.getValue()};
								forrepostore.load();
							}
						}
						*/
						
						
						select: function(combo, record, index, rowIndex, field) {
							if (Ext.getCmp('reference').getValue() != ""){
								Ext.getCmp('id').setValue(record.get('id')+'');
								Ext.getCmp('tran_date').setValue(record.get('tran_date')+'');
								Ext.getCmp('lcp_amount').setValue(record.get('lcp_amount')+'');
								Ext.getCmp('months_term').setValue(record.get('months_term')+'');
								Ext.getCmp('description').setValue(record.get('description')+'');
								Ext.getCmp('category_id').setValue(record.get('category_id')+'');
								Ext.getCmp('debtor_no').setValue(record.get('debtor_no')+'');
								//Ext.getCmp('name').setValue(record.get('name')+'');
								Ext.getCmp('customer').setValue(record.get('debtor_ref') + ' - '+ record.get('name'));
								Ext.getCmp('downpayment_amount').setValue(record.get('downpayment_amount')+'');
								Ext.getCmp('firstdue_date').setValue(record.get('firstdue_date')+'');
								Ext.getCmp('maturity_date').setValue(record.get('maturity_date')+'');
								Ext.getCmp('outstanding_ar_amount').setValue(record.get('outstanding_ar_amount')+'');
								Ext.getCmp('ar_amount').setValue(record.get('ar_amount')+'');
								Ext.getCmp('amortization_amount').setValue(record.get('amortization_amount')+'');
								Ext.getCmp('total_amount').setValue(record.get('total_amount')+'');
								Ext.getCmp('reference_no').setValue(record.get('reference_no')+'');
								Ext.getCmp('orig_branch_code').setValue(record.get('orig_branch_code')+'');
								Ext.getCmp('installmentplcy_id').setValue(record.get('installmentplcy_id')+'');
								Ext.getCmp('rebate').setValue(record.get('rebate')+'');
								Ext.getCmp('financing_rate').setValue(record.get('financing_rate')+'');
								Ext.getCmp('payment_status').setValue(record.get('payment_status')+'');
								//Ext.getCmp('datetoday').setValue(record.get('datetoday')+'');


								
								//Ext.getCmp('unit_price').setValue(record.get('unit_price')+'');
								//Ext.getCmp('qty').setValue(record.get('qty')+'');
								//var records = ARInstallQstore.getAt(rowIndex);
								//submit_form.getForm().reset();
								forrepostore.proxy.extraParams = {ARREFNO: record.get('reference')};
								forrepostore.load();
							}
						}
						
					}
			},{	
					xtype: 'textfield',
					id: 'reference_no',
					name: 'reference_no',
					fieldLabel: '<b>Generate ID</b>',
					padding: '2 5 0 0',
					maxLength: 150,
					labelWidth: 120,
					width: 450,
					height: 35,
					allowBlank: false,
					readOnly: true,
					fieldStyle: 'font-weight: bold; font-size:15px; color: black; text-align: left;'
				}]
			},{					
				xtype: 'textfield',
				id: 'category_id',
				name: 'category_id',
				fieldLabel: 'Category Id',
				//allowBlank: false,
				hidden: true
			},{					
				xtype: 'textfield',
				id: 'orig_branch_code',
				name: 'orig_branch_code',
				fieldLabel: 'Branch Code',
				//allowBlank: false,
				hidden: true
			},{	
				xtype: 'textfield',
				id: 'ar_amount',
				name: 'ar_amount',
				fieldLabel: 'AR Amount',
				//allowBlank: false,
				hidden: true
			},{	
				xtype: 'textfield',
				id: 'installmentplcy_id',
				name: 'installmentplcy_id',
				fieldLabel: 'Installment Policy ID',
				//allowBlank: false,
				hidden: true
			},{	
				xtype: 'textfield',
				id: 'rebate',
				name: 'rebate',
				fieldLabel: 'Rebate',
				//allowBlank: false,
				hidden: true
			},{	
				xtype: 'textfield',
				id: 'financing_rate',
				name: 'financing_rate',
				fieldLabel: 'Financing Rate',
				//allowBlank: false,
				hidden: true
			},{	
				xtype: 'textfield',
				id: 'payment_status',
				name: 'payment_status',
				fieldLabel: 'Payment Status',
				//allowBlank: false,
				hidden: true
			},{	
				xtype: 'textfield',
				id: 'debtor_no',
				name: 'debtor_no',
				fieldLabel: 'Debtor No',
				//allowBlank: false,
				hidden: true
			},{	
				xtype: 'textfield',
				id: 'id',
				name: 'id',
				fieldLabel: 'ID',
				//allowBlank: false,
				hidden: true
			},{	
				xtype:'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					xtype: 'textfield',
					id: 'customer',
					name: 'customer',
					fieldLabel: '<b>Customer name</b>',
					padding: '2 5 0 0',
					maxLength: 150,
					labelWidth: 120,
					width: 806,
					height: 35,
					allowBlank: false,
					readOnly: true,
					fieldStyle: 'font-weight: bold; font-size:15px; color: black; text-align: left;'	
				}]
			},{		
				xtype:'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',			
				items:[{			
					xtype: 'textfield',
					id: 'description',
					name: 'description',
					fieldLabel: '<b>Category</b>',
					padding: '2 5 0 0',
					maxLength: 150,
					labelWidth: 120,
					width: 400,
					height: 35,
					allowBlank: false,
					readOnly: true,
					maskRe: /^([a-zA-Z0-9 _.,-`]+)$/,
					fieldStyle: 'font-weight: bold; font-size:15px; color: black; text-align: left;'	
			},{		
				
					xtype: 'numericfield',
					id: 'downpayment_amount',
					name: 'downpayment_amount',
					fieldLabel: '<b>Down payment</b>',
					padding: '2 5 0 0',
					allowBlank:false,
					useThousandSeparator: true,
					decimalPrecision: 2,
					alwaysDisplayDecimals: true,
					allowNegative: false,
					//currencySymbol: '₱',
					labelWidth:120,
					width: 400,
					height: 35,
					readOnly: true,
					thousandSeparator: ',',
					minValue: 0,					
					fieldStyle: 'font-weight: bold; font-size:15px; color: #008000; text-align: right;'	
				}]										
			},{
				xtype:'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					xtype: 'datefield',
					id: 'tran_date',
					name: 'tran_date',
					fieldLabel: '<b>Date Release</b>',
					padding: '2 5 0 0',
					maxLength: 150,
					labelWidth: 120,
					width: 400,
					height: 35,
					allowBlank: false,
					format : 'Y/m/d',
					readOnly: true,
					fieldStyle: 'font-weight: bold; font-size:15px; color: black; text-align: left;'	
			},{	
				
					xtype: 'numericfield',
					id: 'outstanding_ar_amount',
					name: 'outstanding_ar_amount',
					fieldLabel: '<b>Outstanding A/R</b>',
					padding: '2 5 0 0',
					allowBlank:false,
					useThousandSeparator: true,
					decimalPrecision: 2,
					alwaysDisplayDecimals: true,
					allowNegative: false,
					//currencySymbol: '₱',
					labelWidth:120,
					width: 400,
					height: 35,
					readOnly: true,
					thousandSeparator: ',',
					minValue: 0,
					fieldStyle: 'font-weight: bold; font-size:15px; color: #008000; text-align: right;'	
				}]					
			},{
				xtype:'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					xtype: 'datefield',
					id: 'repo_date',
					name: 'repo_date',
					fieldLabel: '<b>Date</b>',
					padding: '2 5 0 0',
					maxLength: 150,
					labelWidth: 120,
					width: 400,
					height: 35,
					allowBlank: true,
					format : 'Y/m/d',
					readOnly: true,
					value: new Date(),
					//currentDate: new Date,
					fieldStyle: 'font-weight: bold; font-size:15px; color: black; text-align: left;'	            			
			},{	
				
					xtype: 'numericfield',
					id: 'balance',
					name: 'balance',
					fieldLabel: '<b>Balance</b>',
					padding: '2 5 0 0',
					allowBlank: true,
					useThousandSeparator: true,
					decimalPrecision: 2,
					alwaysDisplayDecimals: true,
					allowNegative: false,
					//currencySymbol: '₱',
					labelWidth:120,
					width: 400,
					height: 35,
					readOnly: true,
					thousandSeparator: ',',
					minValue: 0,
					fieldStyle: 'font-weight: bold; font-size:15px; color: #008000; text-align: right;'
				}]	
			},{	
				xtype:'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					xtype: 'textfield',
                    id: 'months_term',
                    name: 'months_term',
                    fieldLabel: '<b>Term</b>',
                    padding: '2 5 0 0',
                    maxLength: 150,
                    labelWidth: 120,
                    width: 400,
                    height: 35,
                    allowBlank: false,
                    readOnly: false,
                    maskRe: /^([a-zA-Z0-9 _.,-`]+)$/,
                    fieldStyle: 'font-weight: bold; font-size:15px; color: black; text-align: left;'	
			},{	
				
					xtype: 'numericfield',
					id: 'amortization_amount',
					name: 'amortization_amount',
					fieldLabel: '<b>Monthly Amort.</b>',
					padding: '2 5 0 0',
					allowBlank:false,
					useThousandSeparator: true,
					decimalPrecision: 2,
					alwaysDisplayDecimals: true,
					allowNegative: false,
					//currencySymbol: '₱',
					labelWidth:120,
					width: 400,
					height: 35,
					readOnly: true,
					thousandSeparator: ',',
					minValue: 0,
					fieldStyle: 'font-weight: bold; font-size:15px; color: #008000; text-align: right;'
				}]						

			},{	
				xtype:'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					xtype: 'datefield',
					id: 'firstdue_date',
					name: 'firstdue_date',
					fieldLabel: '<b>First Due Date</b>',
					padding: '2 5 0 0',
					maxLength: 150,
					labelWidth: 120,
					width: 400,
					height: 35,
					allowBlank: true,
					format : 'Y/m/d',
					readOnly: true,
					maskRe: /^([a-zA-Z0-9 _.,-`]+)$/,
					fieldStyle: 'font-weight: bold; font-size:15px; color: black; text-align: left;'
			},{			
					xtype: 'numericfield',
                    id: 'lcp_amount',
                    name: 'lcp_amount',
                    fieldLabel: '<b>LCP</b>',
                    padding: '2 5 0 0',
                    maxLength: 150,
                    labelWidth: 120,
                    height: 35,
                    width: 400,
                    allowBlank: false,
                    readOnly: true,
                    maskRe: /^([a-zA-Z0-9 _.,-`]+)$/,
                    fieldStyle: 'font-weight: bold; font-size:15px; color: #008000; text-align: right;'
				}]							
			},{		
				xtype:'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					xtype: 'datefield',
					id: 'maturity_date',
					name: 'maturity_date',
					fieldLabel: '<b>Due Date</b>',
					padding: '2 5 0 0',
					maxLength: 150,
					labelWidth: 120,
					height: 35,
					width: 400,
					allowBlank: true,
					format : 'Y/m/d',
					readOnly: true,
					maskRe: /^([a-zA-Z0-9 _.,-`]+)$/,
					fieldStyle: 'font-weight: bold; font-size:15px; color: black; text-align: left;'
			},{							
					xtype: 'numericfield',
                    id: 'total_amount',
                    name: 'total_amount',
                    fieldLabel: '<b>Total Amount</b>',
                    padding: '2 5 0 0',
                    allowBlank:false,
                    useThousandSeparator: true,
                    decimalPrecision: 2,
                    alwaysDisplayDecimals: true,
                    allowNegative: false,
                    //currencySymbol: '₱',
                    labelWidth:120,
                    width: 400,
                    height: 35,
                    readOnly: true,
                    thousandSeparator: ',',
                    minValue: 0,
                    fieldStyle: 'font-weight: bold; font-size:15px; color: #008000; text-align: right;' 	
				}]				
			},{	
				xtype:'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					xtype: 'numericfield',
					id: 'spotcash',
					name: 'spotcash',
					fieldLabel: '<b>Spot Cash</b>',
					padding: '2 5 0 0',
					allowBlank: true,
					useThousandSeparator: true,
					decimalPrecision: 2,
					alwaysDisplayDecimals: true,
					allowNegative: false,
					//currencySymbol: '₱',
					labelWidth:120,
					width: 400,
					height: 35,
					readOnly: true,
					thousandSeparator: ',',
					minValue: 0,
					fieldStyle : 'text-transform: capitalize; font-size:15px; color:green; text-align: right;'
			},{	
					xtype: 'numericfield',
					id: 'unrecovered_cost',
					name: 'unrecovered_cost',
					fieldLabel: '<b>Unrecovered Cost</b>',
					padding: '2 5 0 0',
					allowBlank: true,
					useThousandSeparator: true,
					decimalPrecision: 2,
					alwaysDisplayDecimals: true,
					allowNegative: false,
					//currencySymbol: '₱',
					labelWidth:120,
					width: 400,
					height: 35,
					readOnly: true,
					thousandSeparator: ',',
					minValue: 0,
					fieldStyle : 'text-transform: capitalize; font-size:15px; color:green; text-align: right;'
					
				}]		
			},{	
				xtype:'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					xtype: 'textfield',
                    id: 'spotcashrate',
                    name: 'spotcashrate',
                    fieldLabel: '<b>SpotCash Rate</b>',
                    padding: '2 5 0 0',
                    maxLength: 150,
                    labelWidth: 120,
                    width: 400,
                    height: 35,
                    allowBlank: true,
                    readOnly: true,
                    maskRe: /^([a-zA-Z0-9 _.,-`]+)$/,
                    fieldStyle : 'text-transform: capitalize; font-size:15px; color:green; text-align: right;'
					
				}]	
			},{									
				xtype:'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 5',
				items:[{
					xtype: 'textareafield',
					id: 'comments',
					name: 'comments',
					fieldLabel: '<b>Particulars</b>',
					padding: '2 5 0 0',
					maxLength: 150,
					labelWidth: 120,
					height: 60,
					width: 806,
					allowBlank: true,
					readOnly: false,
					maskRe: /^([a-zA-Z0-9 _.,-`]+)$/,
					fieldStyle: 'font-weight: bold; font-size:12px; color: black; text-align: left;'	
				}]		
			},{		
				xtype: 'grid',
				margin: '2 0 2 5',
				layout:'fit',	
				id: 'Repogridtrans',
				name: 'Repogridtrans',
				title	: 'Repo Details - Listing',
				loadMask: true,
				frame: true,
				store:	forrepostore,
				columns: ForRepoModel,
				columnLines: true,
				autoScroll:true,
	            plugins: [cellEditing]			
			},{  		
		}]
	});

	//--------FOR WINDOW FORM----------//
	var submit_window = Ext.create('Ext.Window',{
		id: 'submit_window',		
		width 	: 830,
		modal	: true,
		plain 	: true,
		border 	: true,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form],
		buttons:[{
			text: 'Save',
			tooltip: 'Save Repo Transaction',
			icon: '../../js/ext4/examples/shared/icons/add.png',
			single : true,				
			handler:function(){
				var form_submit = Ext.getCmp('form_submit').getForm();
				if(form_submit.isValid()) {
					form_submit.submit({
						url: '?submit=info',
						waitMsg: 'Saving Repo Transaction ' + Ext.getCmp('reference').getValue() + '. please wait...',
						method:'POST',
						success: function(form_submit, action) {
							//show and load new added
							/*
							var Areagrid = Ext.getCmp('Areagrid');
							var invoice_no = Ext.getCmp('invoice_no');
							//auto select brancharea added.
							forrepostore.load();
							forrepostore.on('load', function(){
								vwbranchtore.each( function (model, dataindex) {
									var data = model.get('name');
									if (data == brancharea.getRawValue()){
										Areagrid.getSelectionModel().select(dataindex);
										 return false;
									}
								});
							});
							forrepostore.load({
								params: { invoice_no: invoice_no.getValue() }
								});
							*/
							//console.log(action.response.responseText);
							Ext.MessageBox.confirm('Success!', action.result.message + '<br>Would you like to add more?', function (btn, text) {
								if (btn == 'yes') {
									forrepostore.proxy.extraParams = {ARREFNO: "00"};					
									forrepostore.load();
									repotodueksamstore.load();
									//Ext.getCmp('inactive').hide();
									form_submit.reset();
									//Ext.getCmp('moduletype').setValue("INSTLPLCYTYPS");
									submit_window.setTitle('Repo Inquiry Maintenance - Add');
								}else{
									repotodueksamstore.load();
									submit_window.close();
								}
							});
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
			tooltip: 'Cancel adding Repo',
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

	//----------GRID PANEL-------------//
	var plcy_panel =  Ext.create('Ext.panel.Panel', { 
		title	: 'Repo Transfers Listing',
        renderTo: 'ext-form',
		id: 'plcy_panel',
        frame: true,
		width: 1200,
		height:400,
		bodyPadding: 5,
        //labelAlign: 'left',
		layout: 'fit',
		tbar: tbar,
		items: [{
			xtype: 'grid',
			id: 'repodueksamgrid',
			name: 'repodueksamgrid',
			columnWidth: 0.70, //0.8
			flex: 2,
			store:	repotodueksamstore,
			columns: ColumnModel,
			columnLines: true,
			autoScroll:true,
			layout:'fit',
			frame: true,
			bbar : {
				xtype : 'pagingtoolbar',
				store : repotodueksamstore,
				hidden:false,
				//pageSize : itemsPerPage,
				displayInfo : true,
				emptyMsg: "No records to display",
				doRefresh : function(){
					repotodueksamstore.load();
				},
				/*
				items:[{
					xtype: 'checkbox',
					id: 'fstatus',
					name: 'fstatus',
					boxLabel: 'Show also Inactive',
					listeners: {
						change: function(column, rowIdx, checked, eOpts){
							var repodueksamgrid = Ext.getCmp('repodueksamgrid');
							var search = Ext.getCmp('search');
							var selected = repodueksamgrid.getSelectionModel().getSelection();
							var termselected

							Ext.each(selected, function (item) {
								termselected = item.data.number;
							});
							if(checked){
								showall = false;
							}else{
								showall = true;
							}
							if(search.getValue() != ""){
								repotodueksamstore.proxy.extraParams = {REFRENCE: termselected, showall: showall, query: search.getValue()};
							}else{
								repotodueksamstore.proxy.extraParams = {REFRENCE: termselected, showall: showall};
							}
							repotodueksamstore.load();
						}
					}
				}]
				*/
			}
		}]
	});
});
