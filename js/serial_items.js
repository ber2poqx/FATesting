var newURL = window.location.protocol + "//" + window.location.host + window.location.pathname;
Ext.Loader.setConfig({enabled: true});
Ext.Loader.setPath('Ext.ux', '../../js/ext4/examples/ux/');
Ext.require(['Ext.toolbar.Paging',
    'Ext.ux.form.SearchField',
	'Ext.layout.container.Column',
    'Ext.tab.*',
	'Ext.window.MessageBox',
	'Ext.grid.*']);

Ext.onReady(function(){
	Ext.QuickTips.init();
	var global_master_id;


	var GroupTypeStore = new Ext.create('Ext.data.Store',{
		fields 	: 	['id','groupname'],
		data 	: 	[
            //{"id":"0","groupname":"BLANK"},
            {"id":"1","groupname":"DES, Inc"},
            {'id':'2','groupname':'DES Marketing'}
        ],
        autoLoad:true
	});

	var insurance_info = {
		xtype           :'fieldset',
		title           :'Branch Details',
		collapsible     :false,
		defaults        :{
			labelWidth    :100,
			anchor        : '100%',
			labelAlign:'right',
			layout        :{type :'form',defaultMargins  :{top:0,right:5,bottom:0,left:0}}
		},
		items:[{
			xtype:'textfield',
			name:'id',
			id:'id',
            hidden: true
		},{
			xtype: 	'textfield',
			fieldLabel: 'Branch SysPK',
			flex:   1,
			allowBlank: 	false,
			labelAlign:'right',
			name: 	'syspk',
			id:'syspk'
		},{
			xtype: 	'textfield',
			fieldLabel: 'GL Code',
			flex:   1,
			allowBlank: true,
			labelAlign:'right',
			name: 	'gl_code',
			id:'gl_code'
		},{
			xtype: 	'textfield',
			fieldLabel: 'Branch Code',
			flex:   1,
			allowBlank: true,
			labelAlign:'right',
			name: 	'branch_code',
			id:'branch_code'
		},{
			xtype: 	'textfield',
			fieldLabel: 'SAP Branch Code',
			flex:   1,
			allowBlank: true,
			labelAlign:'right',
			name: 	'sap_branch_code',
			id:'sap_branch_code'
		},{
			xtype 		: 	'textfield',
			labelAlign  : 'right',
			allowBlank 	: 	true,
			fieldLabel 	: 	'Branch Name',
			name		: 	'branch_name',
			id:'branch_name',
			anchor:'100%',
			flex:1,
			margins     : '0'
		},{
			xtype 		: 	'textfield',
			labelAlign  : 'right',
			allowBlank 	: 	true,
			fieldLabel 	: 	'Area Name',
			name		: 	'area',
			id:'area',
			anchor:'100%',
			flex:1,
			margins     : '0'
		},{
			xtype 		: 	'textfield',
			flex        :   1,
			fieldLabel 	: 	'Module',
			name		: 	'module',
			id:'module',
			allowBlank  :   true,
			margins     :   '0'
		},{
			xtype 		: 	'textfield',
			fieldLabel 	: 	'Server',
			labelAlign:'right',
			allowBlank 	: 	true,
			name		: 	'server',
			id:'server',
			anchor:'100%'
		},{
			xtype 		: 	'textfield',
			allowBlank 	: 	true,
			fieldLabel 	: 	'Database',
			name		: 	'dbname',
			id: 'dbname',
			flex        :1
		},{
                xtype:'combo',
                name : 'groupname',
				id: 'group_type_id',
				store: GroupTypeStore,
				fieldLabel: 'Report Type',
				queryMode: 'local',
				triggerAction : 'all',
				displayField  : 'groupname',
				valueField    : 'id',
                editable      : true,
				forceSelection: false,
				required: false,
				hiddenName: 'id',
				typeAhead: true,
				emptyText:'--Select--',
				selectOnFocus:true
        },{
			xtype 		: 	'textfield',
			allowBlank 	: 	true,
			fieldLabel 	: 	'Show on Report',
			name		: 	'showonreport',
			id: 'showonreport',
			flex        :1
		},{
			xtype 		: 	'textfield',
			allowBlank 	: 	true,
			fieldLabel 	: 	'Active',
			name		: 	'status',
			id: 'status',
			flex        :1
		}]
	};

 	Ext.define('insurance', {
		extend : 'Ext.data.Model',
		fields  : [
			{name:'id',mapping:'id'},
			{name:'reference',mapping:'reference'},
			{name:'purch_order_no',mapping:'purch_order_no'},
			{name:'supp_name',mapping:'supp_name'},
			{name:'location_name',mapping:'location_name'},
			{name:'ord_date',mapping:'ord_date'},
			{name:'total_qty',mapping:'total_qty'},
			{name:'serialise_total_qty',mapping:'serialise_total_qty'},
			{name:'delivery_date',mapping:'delivery_date'}
		]
	});

	
	var columnModel =[
		{header:'ID', dataIndex:'id', sortable:true, width:20},
		{header:'Reference', dataIndex:'reference', sortable:true, width:50},
		{header:'PO #', dataIndex:'purch_order_no', sortable:true, width:30},
		{header:'Supplier', dataIndex:'supp_name', sortable:true, width:90},
		{header:'Location', dataIndex:'location_name', sortable:true, width:90},
		{header:'Order Date', dataIndex:'ord_date', sortable:true, width:50},
		{header:'Delivery Date', dataIndex:'delivery_date', sortable:true, width:50},
		{header:'Total Qty', dataIndex:'total_qty', sortable:true, width:50, align:'center'},
		{header:'Total-Serial', dataIndex:'serialise_total_qty', sortable:true, width:50, align:'center'},
		{header	: 'Action',	xtype:'actioncolumn', align:'center', width:40,
			items:[
				{
					icon: '../../js/ext4/examples/shared/icons/application_view_columns.png',
					tooltip: 'Serial Items Detail',
					handler: function(grid, rowIndex, colIndex) {
						var record = myInsurance.getAt(rowIndex);
						id = record.get('id');
                        syspk_um = record.get('syspk_um');
                        branch_code = record.get('branch_code');
                        sap_branch_code = record.get('sap_branch_code');
                        gl_code = record.get('gl_code');
                        branch_name = record.get('branch_name');
                        module = record.get('module_type');
                        server = record.get('server');
                        dbname = record.get('database');
                        area = record.get('area');
                        bykind_val = record.get('bykind');
                        bybrand_val = record.get('bybrand');
                        sales_val = record.get('sales');
                        includeoutlet_val = record.get('includeoutlet');
                        detailed_val = record.get('detailed');
                        category_val = record.get('category');
                        model_val = record.get('model');
                        period_start = record.get('period_start');
                        period_end = record.get('period_end');
                        brand_val = record.get('brand');
                        branchcombo_val = record.get('branch');
                        group_type_val = record.get('group_id');
						showonreport = record.get('showonreport');
						status = record.get('status');
						date_generated = record.get('date_generated');
                        kind_val = '';
                        Ext.getCmp('id').setValue(id);
                        Ext.getCmp('syspk').setValue(syspk_um);
                        Ext.getCmp('branch_code').setValue(branch_code);
                        Ext.getCmp('sap_branch_code').setValue(sap_branch_code);
                        Ext.getCmp('gl_code').setValue(gl_code);
                        Ext.getCmp('branch_name').setValue(branch_name);
                        Ext.getCmp('module').setValue(module);
                        Ext.getCmp('server').setValue(server);
                        Ext.getCmp('dbname').setValue(dbname);
                        Ext.getCmp('area').setValue(area);
                        Ext.getCmp('group_type_id').setValue(group_type_val);
                        Ext.getCmp('showonreport').setValue(showonreport);
                        Ext.getCmp('status').setValue(status);
                        //edit_insurance_win.show();
						window.location.replace('serial_details.php?serialid='+id);
					}
				},{
					icon   : '../../js/ext4/examples/shared/icons/fam/delete.gif',
					tooltip : 'Delete',
					hidden: true,
					handler : function(grid, rowIndex, colIndex){
						var records = myInsurance.getAt(rowIndex);
						var deleteID = records.get('id');
						
					}
				} ,{
					icon: '../../js/ext4/examples/shared/icons/connect.png',
					hidden: true,
					tooltip: 'Check Server Connection',
					handler: function(grid,rowIndex,colIndex){
						var records = myInsurance.getAt(rowIndex);
						var ID = records.get('id');
                        branch_code = records.get('branch_code');
                        branch_name = records.get('branch_name');
                        server = records.get('server');
                        dbname = records.get('database');
                        module = records.get('module_um');

                        var preview_contract = new Ext.create('Ext.Window',{
							title: 'Check Server Connection',
							resizable: false,
							maximizable: false,
							maximized: false,
							width: 400,
							height: 200,
							closeAction:'hide',
							items:[
								{
									xtype:'container',
									html: '<iframe src="?connection=1&server='+server+'&dbname='+dbname+'&module='+module+'" width="100%" height="100%" frameborder=0></iframe>'}
							],
							buttons:[
								/*{
									text:'Print',
									handler:function(){
										//alert('Printing please wait...');
										Ext.ux.Printer.print(preview_contract);

									}
								},*/{
									text:'Close',
									handler:function(){
										preview_contract.hide();
									}
								}
							],
							layout:'fit',
							modal: true
						}).show();
					}
				}
			]
		}
	];


	var new_insurance_win =  new Ext.create('Ext.Window', {
		width 	: 400,
		title 	: 'New Branch',
		modal	: true,
		border 	: false,
		closeAction: 'hide',
		buttonAlign : 'center',
		items	:[{
			xtype		: 'form',
			model		: 'insurance',
			id			: 'add_form',
			url   		: '?save=1&new=1',
			method 		: 'POST',
			frame 		: true,
			buttonAlign : 'center',
			defaultType : 'field',
			defaults 	: {
				msgTarget 	: 'side',
				border      : false,
				anchor		: '100%'
			},
			items : [insurance_info],
			buttons : [{
					text : 'Save',
					handler : function()
					{
						//var add_form = this.up('form').getForm();
						if(this.up('form').getForm().isValid()) {
							this.up('form').getForm().submit({
								waitMsg:'Saving Data...',
								success : function (response) {
									Ext.getCmp('add_form').getForm().reset();
									new_insurance_win.hide();
									new_insurance_win.close();
									myInsurance.load();
								},
								failure : function (response) {
									Ext.Msg.alert('Failed', 'Unable to add Insurance, please try again');
									//myInsurance.load();
									//new_insurance_win.hide();
								}
							});
						}
					}
				},{
					text : 'Cancel',
					handler: function(){
						//this.up('form').getForm().reset();
						Ext.getCmp('add_form').getForm().reset();
						new_insurance_win.hide();
						new_insurance_win.close();
				}
			}]
		}]
	});

	var edit_insurance_win =  new Ext.create('Ext.Window', {
		width 	: 400,
		title 	: 'Edit Branch Information',
		modal	: true,
		border 	: false,
		closeAction: 'hide',
		buttonAlign : 'center',
		items	:[{
				xtype		: 'form',
				model		: 'insurance',
				id			: 'edit_form',
				url   		: '?save=1',
				method 		: 'POST',
				frame 		: true,
				buttonAlign : 'center',
				defaultType : 'field',
				defaults 	: {
					msgTarget 	: 'side',
					border      : false,
					anchor		: '100%'
				},
				items : [insurance_info],
				buttons : [{
						text : 'Save',
						handler : function()
						{
							var edit_form = this.up('form').getForm();
							if(this.up('form').getForm().isValid()) {
								this.up('form').getForm().submit({
									waitMsg:'Saving Data...',
									success : function (response) {
										Ext.getCmp('edit_form').getForm().reset();
										edit_insurance_win.hide();
										myInsurance.load();
									},
									failure : function (response) {
										Ext.Msg.alert('Failed', 'Unable to edit the record, please try again');
										//edit_insurance_win.hide();
									}
								});
							}
						}
					},{
						text : 'Close',
						handler: function(){
							Ext.getCmp('edit_form').getForm().reset();
							edit_insurance_win.hide();
						}
					}]
			 }]
	});

	var myInsurance = Ext.create('Ext.data.Store', {
		model : 'insurance',
		name : 'myInsurance',
		method : 'POST',
		proxy : {
			type: 'ajax',
			url	: '?view=1',
			reader:{
				type : 'json',
				root : 'result',
				totalProperty : 'total'
			}
		}/*,
		simpleSortMode : true,
		sorters : [{property : 'id',direction : 'DESC'}]*/,
		autoLoad: true
	});


	var Supplier_Filter = Ext.create('Ext.form.ComboBox', {
    	xtype:'combo',
    	hidden: false,
    	fieldLabel:'Suppliers',
		labelWidth: 60,
    	name:'search_suppliers',
    	id:'search_suppliers',
    	queryMode: 'local',
    	triggerAction : 'all',
    	displayField  : 'supp_name',
    	valueField    : 'supplier_id',
    	editable      : true,
    	forceSelection: false,
    	allowBlank: true,
    	required: false,
    	hiddenName: 'suppliers_id',
    	typeAhead: true,
    	selectOnFocus:true,
    	//layout:'anchor',
    	store: Ext.create('Ext.data.Store',{
    		fields:['supplier_id','supp_name'],
    		autoLoad: true,
    		proxy: {
    			type:'ajax',
    			url: '?suppliers_list=1',
    			reader:{
    				type : 'json',
    				root : 'result',
    				totalProperty : 'total'
    			}
    		}
    	}),
          listeners: {
  			select: function(cmb, rec, idx) {
  				var v = this.getValue();
                //var branch_combo = Ext.getCmp('branch_combo').getValue();
  				myInsurance.proxy.extraParams = { supplier_id: v }
  				myInsurance.load();
  			}
  		}

    })
	
	var grid = Ext.create('Ext.grid.Panel', {
		renderTo: 'serialitems-grid',
		layout: 'fit',
		//height	: 550,
		title	: 'Serial Items Listing',
		store	    :	myInsurance,
		id 		    : 'grid',
		columns 	: columnModel,
		forceFit 	: true,
		frame		: false,
		columnLines	: true,
		sortableColumns :true,
		dockedItems: [{
            dock	: 'top',
            xtype	: 'toolbar',
			name 	: 'search',
            items: [{
					width	: 200,
					xtype	: 'searchfield',
					store	: myInsurance,
					name 	: 'search',
					fieldLabel: 'Search',
					labelWidth: 50
				},Supplier_Filter,{
					icon   	: '../../js/ext4/examples/shared/icons/fam/add.gif',
					tooltip	: 'New Branch',
					text 	: 'New Branch',
					hidden: true,
					handler : function(){
						//Ext.getCmp('add_form').getForm().reset();
						var date = new Date();
						var currentyear = date.getFullYear();
						var currentmonth = ("0" + (date.getMonth() + 1)).slice(-2);
						var currentday = ("0" + date.getDate()).slice(-2);
						var currenthour = date.getHours();
						var currentminutes = date.getMinutes();
						var currentseconds = date.getSeconds();
						var syspk = currentyear.toString()+currentmonth.toString()+currentday.toString()+currenthour.toString()+currentminutes.toString()+currentseconds.toString();
						Ext.getCmp('syspk').setValue(syspk);
						Ext.getCmp('gl_code').setValue(syspk);
			   
						new_insurance_win.show();
						
					},
					scale	: 'small'
				},{
							icon   	: '../../js/ext4/examples/shared/icons/transmit_go.png',
							tooltip	: 'Import New Branches',
							text 	: 'Import New Branches',
							hidden: true,
							handler : function(){
									Ext.Ajax.request({
										url : '?importing=1',
        								//waitMsg:'Importing Branches...',
										method: 'POST',
										success: function (response){
    										myInsurance.load();
										},
										failure: function (response){
											Ext.Msg.alert('Error', 'Importing Branches Error');
										}
									});
							},
							scale	: 'small'
				},{
							icon   	: '../../js/ext4/examples/shared/icons/transmit_go.png',
							tooltip	: 'Import to New Table Branches',
							text 	: 'Import to New Table Branches',
							hidden: true,
							handler : function(){
									Ext.Ajax.request({
										url : '?importing_new=1',
        								//waitMsg:'Importing Branches...',
										method: 'POST',
										success: function (response){
    										myInsurance.load();
										},
										failure: function (response){
											Ext.Msg.alert('Error', 'Importing Branches Error');
										}
									});
							},
							scale	: 'small'
				}]
		}],
		bbar : {
			xtype : 'pagingtoolbar',
			store : myInsurance,
			displayInfo : true
		}

	});


});
