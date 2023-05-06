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
	var itemsPerPage = 18;   // set the number of items you want per page on grid.
	var showall = false;
	var maxfields = 10; //change this number if you want to increase/decrease adding fields.
	var selectedbranch = [];

	////define model for policy installment
    Ext.define('data_description',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'remarks', mapping:'remarks'}
		]
	});
	Ext.define('data_logs',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'date_transfer', mapping:'date_transfer'},
			{name:'details', mapping:'details'}
		]
    });
	Ext.define('branch_model',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'branch_no', mapping:'branch_no'},
			{name:'branch_code', mapping:'branch_code'},
			{name:'branch_name', mapping:'branch_name'}
		]
    });
	var selModel = Ext.create('Ext.selection.CheckboxModel', {
		checkOnly: true,
		mode: 'Single'
	});
	//------------------------------------: stores :----------------------------------------
	var datadesc_store = Ext.create('Ext.data.Store', {
		model: 'data_description',
		autoLoad : true,
		proxy: {
			url: '?get_datadesc=00',
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
	var branch_store = Ext.create('Ext.data.Store', {
        model: 'branch_model',
		autoLoad : true,
        proxy: {
			url: '?get_branch=00',
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
	var datalogs_store = Ext.create('Ext.data.Store', {
        model: 'data_logs',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
        proxy: {
			url: '?get_datalogs=00',
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
		model: 'xxx',
		frame: true,
		defaultType: 'field',
		defaults: {msgTarget: 'under', anchor: '-5'}, //msgTarget: 'side', labelAlign: 'top'
		items: [{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 2 2 5',
			items:[{
				xtype : 'datefield',
				id	  : 'date_from',
				name  : 'date_from',
				fieldLabel : '<b>Date from </b>',
				allowBlank: false,
				labelWidth: 100,
				//width: 100,
				margin: '0 20 0 0',
				format : 'm/d/Y',
				fieldStyle: 'font-weight: bold; color: #210a04;',
				value: Ext.Date.format(new Date(), 'Y-m-d')
			},{
				xtype : 'datefield',
				id	  : 'date_to',
				name  : 'date_to',
				fieldLabel : '<b>Date to </b>',
				allowBlank: false,
				labelWidth: 100,
				//width: 100,
				format : 'm/d/Y',
				fieldStyle: 'font-weight: bold; color: #210a04;',
				value: Ext.Date.format(new Date(), 'Y-m-d')
			}]
		},{
			xtype: 'combobox',
			id: 'datadesc',
			name: 'datadesc',
			fieldLabel: '<b>Description </b>',
			store: datadesc_store,
			displayField: 'description',
			valueField: 'id',
			queryMode: 'local',
			emptyText:'Select description',
			allowBlank: false,
			forceSelection: true,
			selectOnFocus:true,
			labelWidth: 105,
			margin: '5 0 5 0'
		},{
			xtype: 'combobox',
			id: 'branch',
			name: 'branch',
			fieldLabel: '<b>Send to? </b>',
			allowBlank: false,
			store: branch_store,
			displayField: 'branch_name',
			valueField: 'branch_code',
			queryMode: 'local',
			emptyText:'Select branch',
			forceSelection: true,
			selectOnFocus:true,
			multiSelect: true,
			labelWidth: 105,
			margin: '5 0 5 0',
			listeners: {
				select: function(combo, record, index) {
					
				}
			}
		}]
	});
	var submit_window = Ext.create('Ext.Window',{
		width 	: 590,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form],
		buttons:[{
			text: 'Process',
			tooltip: 'Process',
			icon: '../js/ext4/examples/shared/icons/table_row_insert.png',
			single : true,				
			handler:function(){
				var form_submit = Ext.getCmp('form_submit').getForm();
				if(form_submit.isValid()) {
					
					form_submit.submit({
						url: '?submit=info',
						waitMsg: 'Proccessing data transfer. Please wait...',
						method:'POST',
						success: function(form_submit, action) {
							//show and load new added
							datadesc_store.load();
							Ext.Msg.alert('Success!', JSON.stringify(action.result.message));
							datalogs_store.load();
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
	//---------------------------------------------------------------------------------------
	//for policy setup column model
	var CheckOperatorModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>id</b>',dataIndex:'id',hidden: true},
		{header:'<b>Date Upload</b>', dataIndex:'date_upload', sortable:true, width:120,
			renderer: function(value, metaData, record, rowIndex, colIndex, store) {
				return '<span style="color:black; font-weight:bold;">' + value + '</span>';
		    }	
		},
		{header:'<b>Remarks</b>', dataIndex:'remarks', sortable:true, width:442,
			renderer: function(value, metaData, record, rowIndex, colIndex, store) {
				return '<span style="color:green; font-weight:bold;">' + value + '</span>';
			}
		},
		{header:'<b>Preparer Name</b>', dataIndex:'prepared', sortable:true, width:180,
			renderer: function(value, metaData, record, rowIndex, colIndex, store) {
				return '<span style="color:black; font-weight:bold;">' + value + '</span>';
			}	
		},
		{header:'<b>Active</b>', dataIndex:'inactive', sortable:true, width:68, hidden: true,
			renderer:function(value,metaData){
				if (value === 'Yes'){
					metaData.style="color:#229954";
				}else{
					metaData.style="color:#900C3F";
				}
				return "<b>" + value + "</b>";
			}
		},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:95, hidden: true,
			items:[{
				icon: '../js/ext4/examples/shared/icons/layout_content.png',
				tooltip: 'view details',
				handler: function(grid, rowIndex, colIndex) {
					var records = datadesc_store.getAt(rowIndex);
					
					submit_form.getForm().reset();

					Ext.getCmp('syspk').setValue(records.get('id'));
					Ext.getCmp('cashier').setValue(records.get('cashier_id'));
					Ext.getCmp('tabang_user').setValue(records.get('tabang_id'));
					if (records.get('inactive') === 'Yes'){
						Ext.getCmp('yes').setValue(0);
					}else{
						Ext.getCmp('no').setValue(1);
					}
					submit_window.setTitle('Checkout operator builder maintenance  - Edit');
					submit_window.show();
					submit_window.setPosition(330,90);
				}
			},'-',{
				icon: '../js/ext4/examples/shared/icons/fam/delete.png',
				tooltip: 'Delete builder',
				handler: function(grid, rowIndex, colIndex) {
					var records = datadesc_store.getAt(rowIndex);
					var MsgConfirm = Ext.MessageBox.confirm('Confirm?', 'Cashier: <b>' + records.get('cashier_name') + '</b><br\> Preparer: <b>' + records.get('tabang_name') + '</b><br\> Are you sure you want to delete this record? ', function (btn, text) {
						if (btn == 'yes') {
							Ext.Ajax.request({
								method: 'POST',
								url: '?delete=info',
								waitMsg:'Deleting Record...please wait.',
								params: {
									syspk: records.get('id'),
								},
								success: function (response){
									var data = Ext.decode(response.responseText);
									if (data.success == 'true') {
										Ext.Msg.alert('Success', data.message);
										datadesc_store.load();
									}else{
										Ext.Msg.alert('Error', data.message);
									}
								}
							});
						}
					});
					MsgConfirm.defaultButton = 2;
				}
			}]
		}
	];
	var tbar = [/*{
		xtype: 'searchfield',
		id:'search',
		name:'search',
		fieldLabel: '<b>Search</b>',
		labelWidth: 50,
		width: 290,
		emptyText: "Search by name...",
		scale: 'small',
		store: datadesc_store,
		listeners: {
			change: function(field) {
				datadesc_store.proxy.extraParams = {query: field.getValue()};
				datadesc_store.load();
			}
		}
	}, '-',*/{
		text:'<b>Transfer Data</b>',
		tooltip: 'Click transfer data to branches',
		icon: '../js/ext4/examples/shared/icons/table_relationship.png',
		scale: 'small',
		handler: function(){
			submit_form.getForm().reset();
			submit_window.show();
			submit_window.setTitle('Data Synchronization');
			submit_window.setPosition(330,90);
		}
	} /*, '->' ,{
		xtype:'splitbutton',
		//text: '<b>Maintenance</b>',
		tooltip: 'Select...',
		icon: '../js/ext4/examples/shared/icons/cog_edit.png',
		scale: 'small',
		menu:[{
			text: '<b>A/R Installment Inquiry</b>',
			icon: '../js/ext4/examples/shared/icons/map_magnify.png',
			href: '../lending/inquiry/ar_installment_inquiry.php?',
			hrefTarget : '_blank'
		}]
	}*/];

	var builder_panel =  Ext.create('Ext.panel.Panel', { 
        renderTo: 'ext-form',
		id: 'builder_panel',
		title	: 'Data Synchronization Logs',
        frame: false,
		width: 780,
		tbar: tbar,
		items: [{
			xtype: 'grid',
			id: 'datagrid_logs',
			name: 'datagrid_logs',
			store:	datalogs_store,
			columns: CheckOperatorModel,
			columnLines: true,
			autoScroll:true,
			layout:'fit',
			frame: true,
			bbar : {
				xtype : 'pagingtoolbar',
				hidden: false,
				store : datalogs_store,
				pageSize : itemsPerPage,
				displayInfo : true,
				emptyMsg: "No records to display",
				doRefresh : function(){
					datalogs_store.load();
					
				}
			}
		}]
	});
});
