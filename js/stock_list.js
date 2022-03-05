var newURL = window.location.protocol + "//" + window.location.host + window.location.pathname;
var url_string = window.location.href;
var url = new URL(url_string);
var type = url.searchParams.get("type");
var itemgroup = url.searchParams.get("itemgroup");
if(itemgroup==null){
	itemgroup='-1';
}
var client_id = url.searchParams.get("client_id");
var supplier = url.searchParams.get("supplier");
if(supplier==null){
	supplier='0';
}
//alert(supplier);
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
			{name:'select_view',mapping:'select_view'},
			{name:'item_code',mapping:'item_code'},
			{name:'units',mapping:'units'},
			{name:'description',mapping:'description'},
			{name:'avail_qty',mapping:'avail_qty'},
			{name:'category',mapping:'category'},
			{name:'brand_name',mapping:'brand_name'},
			{name:'manufacturer_name',mapping:'manufacturer_name'},
			{name:'distributor_name',mapping:'distributor_name'},
			{name:'importer_name',mapping:'importer_name'}
		]
	});

	function columnWrap(val){
		return '<div style="white-space:normal !important;">'+ val +'</div>';
	}	
	var columnModel =[
		{header:'', dataIndex:'select_view', sortable:true, width:30},
		{header:'Item<br/>Code', dataIndex:'item_code', sortable:true, width:40},
		{header:'Description', dataIndex:'description', sortable:true, width:120,renderer: columnWrap},
		{header:'Avail<br/>Qty', dataIndex:'avail_qty', sortable:true, width:30, align: 'right'},
		{header:'Units', dataIndex:'units', sortable:true, width:30},
		{header:'Category', dataIndex:'category', sortable:true, width:50},
		{header:'Brand', dataIndex:'brand_name', sortable:true, width:50},
		{header:'Suppliers', dataIndex:'manufacturer_name', sortable:true, width:50, align:'left'},
		{header:'Sub-category', dataIndex:'distributor_name', sortable:true, width:50, align:'left'},
		{header:'Classification', dataIndex:'importer_name', sortable:true, width:50, align:'left'}
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
			url	: '?view=1&type='+type+'&client_id='+client_id+'&itemgroup='+itemgroup+'&supplier='+supplier,
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
    	fieldLabel:'Category',
		labelWidth: 60,
    	name:'search_category',
    	id:'search_category',
    	queryMode: 'local',
    	triggerAction : 'all',
    	displayField  : 'description',
    	valueField    : 'category_id',
    	editable      : true,
    	forceSelection: false,
    	allowBlank: true,
    	required: false,
    	hiddenName: 'suppliers_id',
    	typeAhead: true,
    	selectOnFocus:true,
    	//layout:'anchor',
    	store: Ext.create('Ext.data.Store',{
    		fields:['category_id','description'],
    		autoLoad: true,
    		proxy: {
    			type:'ajax',
    			url: '?category_list=1',
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
  				myInsurance.proxy.extraParams = { category: v }
  				myInsurance.load();
  			}
  		}

    })
	
	var grid = Ext.create('Ext.grid.Panel', {
		renderTo: 'item_tbl',
		layout: 'anchor',
		//height	: 550,
		title	: 'Items Listing',
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

	if(type=='pr'){
		//alert(type);
		Ext.getCmp('search_category').hide();
	}
});
