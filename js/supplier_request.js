var newURL = window.location.protocol + "//" + window.location.host + window.location.pathname;
Ext.Loader.setConfig({enabled: true});
Ext.Loader.setPath('Ext.ux', '../js/ext4/examples/ux/');
Ext.require([
    'Ext.grid.*',
    'Ext.data.*',
    'Ext.panel.*',
    'Ext.form.*',
	'Ext.window.*',
    'Ext.tab.*',
	'Ext.selection.CheckboxModel',
	'Ext.ux.form.SearchField'
]);

Ext.onReady(function(){
	Ext.QuickTips.init();
	var itemsPerPage = 20;   // set the number of items you want per page on grid.
	var showall = false;
	var maxfields = 10; //change this number if you want to increase/decrease adding fields.

	////define model for policy installment
    Ext.define('Supp_Request_model',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'branchcode', mapping:'branchcode'},
			{name:'supplier_ref', mapping:'supplier_ref'},
			{name:'supp_name', mapping:'supp_name'},
			{name:'Status1', mapping:'Status1'},
			{name:'Status2', mapping:'Status2'},
			{name:'inactive', mapping:'inactive'},
			{name:'STATUS', mapping:'STATUS'},
			{name:'STATUSSERACH', mapping:'STATUSSERACH'}									
		]
	});
	//------------------------------------: stores :----------------------------------------
	var Statusstore = Ext.create('Ext.data.Store',{
		fields: ['id','name'],
		autoLoad: true,
		data : 	[
			{"value":"Added"},
            {"value":"Pending"}
        ]
	});
	/*
	var Statusstore = Ext.create('Ext.data.Store', {
		name: 'Statusstore',
		fields:['STATUSSERACH'],
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
			property : 'status',
			direction : 'ASC'
		}]
	});
	*/
	var Supp_Request_store = Ext.create('Ext.data.Store', {
        model: 'Supp_Request_model',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
        proxy: {
			url: '?get_supplier_request_allDB=zHun',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'user_name',
			direction : 'ASC'
		}]
	});

	var submit_form = Ext.create('Ext.form.Panel', {
		id: 'form_submit',
		model: 'Supp_Request_model',
		frame: true,
		defaultType: 'field',
		defaults: {margin: '2 0 2 5', msgTarget: 'under', anchor: '-5'}, //msgTarget: 'side', labelAlign: 'top'
		items: [{
			xtype: 'textfield',
			fieldLabel: '<b>Supplier Code</b>',
			id: 'supplier_ref',
			name: 'supplier_ref',
			labelWidth: 120,
			width: 250,
			readOnly: true,
			allowBlank: false,
			fieldStyle: 'font-weight: bold; color: #210a04;'
		},{
			xtype: 'textfield',
			fieldLabel: '<b>Supplier Name</b>',
			id: 'supp_name',
			name: 'supp_name',
			labelWidth: 120,
			width: 250,
			readOnly: true,
			allowBlank: false,
			fieldStyle: 'font-weight: bold; color: #210a04;'
		},{
			xtype: 'textfield',
			id: 'Status1',
			name: 'Status1',
			fieldLabel: '<b>Status</b>',
			allowBlank: false,
			readOnly: true,
			labelWidth: 120,
			value: 'Closed',
			fieldStyle: 'font-weight: bold; color: #210a04;'
		},{
			xtype: 'textfield',
			id: 'Status2',
			name: 'Status2',
			fieldLabel: '<b>Status1</b>',
			allowBlank: false,
			readOnly: true,
			labelWidth: 120,
			value: 'Added',
			hidden: true,
			fieldStyle: 'font-weight: bold; color: #210a04;'
		},{
			xtype: 'textfield',
			id: 'id',
			name: 'id',
			fieldLabel: '<b>ID</b>',
			allowBlank: false,
			readOnly: true,
			labelWidth: 120,
			fieldStyle: 'font-weight: bold; color: #210a04;',
			hidden: true
		},{
			xtype: 'textfield',
			id: 'inactive',
			name: 'inactive',
			fieldLabel: '<b>Inactive</b>',
			allowBlank: false,
			readOnly: true,
			labelWidth: 120,
			fieldStyle: 'font-weight: bold; color: #210a04;',
			hidden: true
		},{
			xtype: 'textfield',
			id: 'STATUS',
			name: 'STATUS',
			fieldLabel: '<b>STATUS</b>',
			allowBlank: true,
			readOnly: true,
			labelWidth: 120,
			fieldStyle: 'font-weight: bold; color: #210a04;',
			hidden: true
		}]
	});
	var submit_window = Ext.create('Ext.Window',{
		width 	: 600,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form],
		buttons:[{
			text: 'Update',
			tooltip: 'Closed Status',
			icon: '../../js/ext4/examples/shared/icons/add.png',
			single : true,				
			handler:function(){
				var form_submit = Ext.getCmp('form_submit').getForm();
				if(form_submit.isValid()) {
					form_submit.submit({
						url: '?update=info',
						waitMsg: 'Closed Status. please wait...',
						method:'GET',
						success: function(form_submit, action) {
							//show and load new added
							Supp_Request_store.load()
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
			tooltip: 'Cancel builder',
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
	//---------------------------------------------------------------------------------------
	//for policy setup column model
	var Supp_Request_Header = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Supplier Code</b>', dataIndex:'supplier_ref', sortable:true, width:130,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:black;font-weight:bold;">' + Ext.util.Format.number(value) +'</span>';
			}
		},
		{header:'<b>Supplier Name</b>', dataIndex:'supp_name', sortable:true, width:270,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green; font-weight:bold;">' + Ext.util.Format.number(value) +'</span>';
			}
		},
		{header:'<b>Status</b>', dataIndex:'STATUS', sortable:true, width:200,			
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:black; font-weight:bold;">' + Ext.util.Format.number(value) +'</span>';
			}			
		},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:70,
			items:[{
				icon: '../../js/ext4/examples/shared/icons/layout_content.png',
				tooltip: 'Closed Request',
				handler: function(grid, rowIndex, colIndex) {
					submit_form.getForm().reset();

					var records = Supp_Request_store.getAt(rowIndex);
					

					//Ext.getCmp('id').setValue(records.get('id'));
					Ext.getCmp('id').setValue(records.get('id'));
					Ext.getCmp('supplier_ref').setValue(records.get('supplier_ref'));
					Ext.getCmp('supp_name').setValue(records.get('supp_name'));
					Ext.getCmp('STATUS').setValue(records.get('STATUS'));
					Ext.getCmp('inactive').setValue(records.get('inactive'));

					submit_window.setTitle('Supplier Request Approval Details - ' + Ext.getCmp('supplier_ref').getRawValue());
					submit_window.show();
					submit_window.setPosition(330,90);
				}
			}]
		}
	];
	
	var tbar = [{
		xtype: 'combobox',
		id: 'STATUSSERACH1',
		name: 'STATUSSERACH1',
		fieldLabel: '<b>Status</b>',
		store: Statusstore,
		displayField: 'value',
		valueField: 'value',
		queryMode: 'local',
		emptyText:'Select Status',
		labelWidth: 60,
		width: 250,
		forceSelection: true,
		selectOnFocus:true,
		fieldStyle : 'background-color: #F2F3F4; color:green; font-weight:bold;',
		listeners: {
			select: function(combo, record, index) {
				var search = Ext.getCmp("search").getValue();

				Supp_Request_store.proxy.extraParams = {Status: combo.getValue(), query: Ext.getCmp('search').getValue()};
				Supp_Request_store.load();
			}
		}
	}, '-',{
		xtype: 'searchfield',
		id:'search',
		name:'search',
		fieldLabel: '<b>Search</b>',
		labelWidth: 50,
		width: 300,
		emptyText: "Search by supplier name...",
		scale: 'small',
		store: Supp_Request_store,
		fieldStyle : 'background-color: #F2F3F4; color:green; font-weight:bold;',
		listeners: {
			change: function(field) {

				Supp_Request_store.proxy.extraParams = {Status: Ext.getCmp('STATUSSERACH1').getValue(), query: field.getValue()};
				Supp_Request_store.load();
			}
		}
	}];

	var builder_panel =  Ext.create('Ext.panel.Panel', { 
        renderTo: 'ext-form',
		id: 'builder_panel',
        frame: false,
		width: 707,
		tbar: tbar,
		items: [{
			xtype: 'grid',
			title: 'Suppliers Request Approval',
			id: 'Approval_grid',
			name: 'Approval_grid',
			store:	Supp_Request_store,
			columns: Supp_Request_Header,
			columnLines: true,
			autoScroll:true,
			layout:'fit',
			frame: true,
			bbar : {
				xtype : 'pagingtoolbar',
				hidden: false,
				store : Supp_Request_store,
				pageSize : itemsPerPage,
				displayInfo : false,
				emptyMsg: "No records to display",
				doRefresh : function(){
					Supp_Request_store.load();
					
				}
			}
		}]
	});
});
