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

	////define model for policy installment
    Ext.define('User_CHECKOPmodel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'cashier_id', mapping:'cashier_id'},
			{name:'cashier_name', mapping:'cashier_name'},
			{name:'tabang_id', mapping:'tabang_id'},
			{name:'tabang_name', mapping:'tabang_name'},
			{name:'inactive', mapping:'inactive'}
		]
	});
	Ext.define('cashierModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'user_id', mapping:'user_id'},
			{name:'user_name', mapping:'user_name'}
		]
    });
	Ext.define('tabangModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'user_id', mapping:'user_id'},
			{name:'user_name', mapping:'user_name'}
		]
    });
	var selModel = Ext.create('Ext.selection.CheckboxModel', {
		checkOnly: true,
		mode: 'Single'
	});
	//------------------------------------: stores :----------------------------------------
	var get_cbocashier = Ext.create('Ext.data.Store', {
		model: 'cashierModel',
		autoLoad : true,
		proxy: {
			url: '?get_cbocashier=15',
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
	var get_cboTabang = Ext.create('Ext.data.Store', {
        model: 'tabangModel',
		autoLoad : true,
        proxy: {
			url: '?get_tabanguser=15',
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
	var User_CHKOPStore = Ext.create('Ext.data.Store', {
        model: 'User_CHECKOPmodel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
        proxy: {
			url: '?get_CHKOPuser=zHun',
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
		model: 'policytypeModel',
		frame: true,
		defaultType: 'field',
		defaults: {msgTarget: 'under', anchor: '-5'}, //msgTarget: 'side', labelAlign: 'top'
			items: [{
				xtype: 'textfield',
				id: 'syspk',
				name: 'syspk',
				fieldLabel: 'syspk',
				//allowBlank: false,
				hidden: true
			},{
				xtype: 'combobox',
				id: 'cashier',
				name: 'cashier',
				fieldLabel: '<b>Main Cashier </b>',
				store: get_cbocashier,
				displayField: 'user_name',
				valueField: 'user_id',
				queryMode: 'local',
				emptyText:'Select main cashier',
				allowBlank: false,
				forceSelection: true,
				selectOnFocus:true,
				labelWidth: 105,
				margin: '5 0 5 0'
			},{
				xtype: 'combobox',
				id: 'tabang_user',
				name: 'tabang_user',
				fieldLabel: '<b>Prepared User </b>',
				allowBlank: false,
				store: get_cboTabang,
				displayField: 'user_name',
				valueField: 'user_id',
				queryMode: 'local',
				emptyText:'Select Preparer',
				forceSelection: true,
				selectOnFocus:true,
				labelWidth: 105,
				margin: '5 0 5 0',
				//listConfig: {
					//getInnerTpl: function(displayField) {
						//return '{[Ext.String.htmlEncode(values.' + displayField + ')]}';
					//}
				//}
			},{			
				xtype: 'container',
				layout: 'hbox',
				defaults: {msgTarget: 'under', anchor: '-5', fieldCls:'DisabledAmountCls'},
				items:[{
					xtype: 'radiogroup',
					fieldLabel: '<b>? Active </b>',
					margin: '5 0 5 0',
					layout: {type: 'hbox'},
					items: [
						{boxLabel: '<b>Yes</b>', name: 'inactive',id:'yes', inputValue:0, margin : '0 3 0 0'},
						{boxLabel: '<b>No</b>', name: 'inactive', id:'no', inputValue:1}
					]
				}]
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
			text: 'Save',
			tooltip: 'Save policy installment',
			icon: '../js/ext4/examples/shared/icons/add.png',
			single : true,				
			handler:function(){
				var form_submit = Ext.getCmp('form_submit').getForm();
				if(form_submit.isValid()) {
					form_submit.submit({
						url: '?submit=info',
						waitMsg: 'Saving builders. please wait...',
						method:'POST',
						success: function(form_submit, action) {
							//show and load new added
							User_CHKOPStore.load();
							Ext.MessageBox.confirm('Success!', action.result.message + '<br>Would you like to add more?', function (btn, text) {
								if (btn == 'yes') {
									form_submit.reset();
									Ext.getCmp('yes').setValue(0)
									submit_window.setTitle('Checkout operator builder maintenance - Add');
								}else{
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
	var CheckOperatorModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>id</b>',dataIndex:'id',hidden: true},
		{header:'<b>Cashier Id</b>', dataIndex:'cashier_id', sortable:true, width:110},
		{header:'<b>Main Cashier</b>', dataIndex:'cashier_name', sortable:true, width:225},
		{header:'<b>Preparer Id</b>', dataIndex:'tabang_id', sortable:true, width:110},
		{header:'<b>Preparer Name</b>', dataIndex:'tabang_name', sortable:true, width:225},
		{header:'<b>Active</b>', dataIndex:'inactive', sortable:true, width:68,
			renderer:function(value,metaData){
				if (value === 'Yes'){
					metaData.style="color:#229954";
				}else{
					metaData.style="color:#900C3F";
				}
				return "<b>" + value + "</b>";
			}
		},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:95,
			items:[{
				icon: '../js/ext4/examples/shared/icons/layout_content.png',
				tooltip: 'view details',
				handler: function(grid, rowIndex, colIndex) {
					var records = User_CHKOPStore.getAt(rowIndex);
					
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
					var records = User_CHKOPStore.getAt(rowIndex);
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
										User_CHKOPStore.load();
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
	var tbar = [{
		xtype: 'searchfield',
		id:'search',
		name:'search',
		fieldLabel: '<b>Search</b>',
		labelWidth: 50,
		width: 290,
		emptyText: "Search by name...",
		scale: 'small',
		store: User_CHKOPStore,
		listeners: {
			change: function(field) {
				User_CHKOPStore.proxy.extraParams = {query: field.getValue()};
				User_CHKOPStore.load();
			}
		}
	}, '-', {
		text:'<b>Add</b>',
		tooltip: 'Add new checkout Operator builder.',
		icon: '../js/ext4/examples/shared/icons/add.png',
		scale: 'small',
		handler: function(){
			submit_form.getForm().reset();
			Ext.getCmp('yes').setValue(0)
			submit_window.show();
			submit_window.setTitle('Checkout operator builder maintenance - Add');
			submit_window.setPosition(330,90);
		}
	}, '->' ,{
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
	}];

	var builder_panel =  Ext.create('Ext.panel.Panel', { 
        renderTo: 'ext-form',
		id: 'builder_panel',
        frame: false,
		width: 870,
		tbar: tbar,
		items: [{
			xtype: 'grid',
			id: 'CheckOpUser_grid',
			name: 'CheckOpUser_grid',
			store:	User_CHKOPStore,
			columns: CheckOperatorModel,
			columnLines: true,
			autoScroll:true,
			layout:'fit',
			frame: true,
			bbar : {
				xtype : 'pagingtoolbar',
				hidden: false,
				store : User_CHKOPStore,
				pageSize : itemsPerPage,
				displayInfo : false,
				emptyMsg: "No records to display",
				doRefresh : function(){
					User_CHKOPStore.load();
					
				}
			}
		}]
	});
});
