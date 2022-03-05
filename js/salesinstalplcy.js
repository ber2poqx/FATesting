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
	'Ext.ux.form.SearchField'
]);

Ext.onReady(function(){
	Ext.QuickTips.init();
	var itemsPerPage = 18;   // set the number of items you want per page on grid.
	var showall = false;

	////define model for policy installment
    Ext.define('plcydtlmodel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'plcyd_id', mapping:'plcyd_id'},
			{name:'plcyd_code', mapping:'plcyd_code'},
			{name:'description', mapping:'description'},
			{name:'tax_incl', mapping:'tax_incl'},
			{name:'remarks', mapping:'remarks'},
			{name:'factor', mapping:'factor'},
			{name:'term', mapping:'term'},
			{name:'frate', mapping:'frate'},
			{name:'rebate', mapping:'rebate'},
			{name:'penalty', mapping:'penalty'},
			{name:'moduletype', mapping:'moduletype'},
			{name:'categoryid', mapping: 'categoryid'},
			{name:'category', mapping: 'category'},
			{name:'plcyh_id', mapping:'plcyh_id'},
			{name:'plcyh_code', mapping:'plcyh_code'},
			{name:'status', mapping:'status'},
			{name:'dateadded', mapping:'dateadded'}
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
	var plcytermstore = Ext.create('Ext.data.Store', {
		name: 'plcytermstore',
		//model: 'comboModel',
		fields:['number'],
		autoLoad : true,
        proxy: {
			url: '?getplcyterm=00',
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
	var policyInstallstore = Ext.create('Ext.data.Store', {
		model: 'plcydtlmodel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?vwplcydata=00',
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
	var plcytypestore = Ext.create('Ext.data.Store', {
		name: 'plcytypestore',
		//model: 'comboModel',
		fields:['id','name','catid'],
		autoLoad : true,
		proxy: {
			url: '?getplcytype=00',
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
	var plcyfratestore = Ext.create('Ext.data.Store', {
		name: 'plcyfratestore',
        fields:['number'],
		autoLoad : true,
        proxy: {
			url: '?getplcyfrate=00',
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
	var plcyrebatestore = Ext.create('Ext.data.Store', {
		name: 'plcyrebatestore',
        fields:['number'],
		autoLoad : true,
        proxy: {
			url: '?getplcyrebate=00',
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
	//---------------------------------------------------------------------------------------

	//for policy setup column model
	var ColumnModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Id</b>',dataIndex:'plcyd_id',hidden: true},
		{header:'<b>Code</b>', dataIndex:'plcyd_code', sortable:true, width:142},
		{header:'<b>Category</b>', dataIndex:'category', sortable:true, width:180},
		{header:'<b>Financing Rate</b>', dataIndex:'frate', sortable:true, width:122,
			renderer: Ext.util.Format.Currency = function(value){
				 if (value==0){
					   value = '0.00%';
					   return (value);
				 }else{
					   return (value + '%')
				 }
			}
		},
		{header:'<b>Rebate</b>', dataIndex:'rebate', sortable:true, width:82,
			renderer: Ext.util.Format.Currency = function(value){
				 if (value==0){
					   value = 'PhP 0.00';
					   return (value);
				 }else{
					   return ('PhP' + ' ' + value)
				 }
			}
		},
		//{header:'<b>Active</b>', dataIndex:'status', sortable:true, width:50},
		{header:'<b>Date Added</b>', dataIndex:'dateadded', sortable:true, width:110, renderer: Ext.util.Format.dateRenderer('m-d-Y')},
		{header:'<b>Active</b>', dataIndex:'status', sortable:true, width:80,
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
					var records = policyInstallstore.getAt(rowIndex);
					submit_form.getForm().reset();
					Ext.getCmp('syspk').setValue(records.get('plcyd_id'));
					Ext.getCmp('moduletype').setValue(records.get('moduletype'));
					Ext.getCmp('policytype').setValue(records.get('plcyh_id'));
					Ext.getCmp('category').setValue(records.get('categoryid'));
					Ext.getCmp('term').setValue(records.get('term'));
					Ext.getCmp('frate').setValue(records.get('frate'));
					Ext.getCmp('rebate').setValue(records.get('rebate'));
					Ext.getCmp('plcydcode').setValue(records.get('plcyd_code'));
					submit_form.down('radiogroup').setValue({tax_included: records.get('tax_incl')})
					Ext.getCmp('remarks').setValue(records.get('remarks'));
					Ext.getCmp('inactive').show();
					if (records.get('status') === 'Yes'){
						Ext.getCmp('inactive').setValue(0);
					}else{
						Ext.getCmp('inactive').setValue(1);
					}
					submit_window.setTitle('Sales Installment Policy Maintenance - Edit');
					submit_window.show();
					submit_window.setPosition(500,110);
				}
			},'-',{
				icon   : '../../js/ext4/examples/shared/icons/fam/delete.png',
				tooltip : 'Delete',
				handler : function(grid, rowIndex, colIndex){
					var records = policyInstallstore.getAt(rowIndex);
					var MsgConfirm = Ext.MessageBox.confirm('Confirm', 'Installment policy code: <b>' + records.get('plcyd_code') + '</b><br\> Are you sure you want to delete this record? ', function (btn, text) {
						if (btn == 'yes') {
							Ext.Ajax.request({
								method: 'POST',
								url: '?delete=info',
								waitMsg:'Deleting Record...please wait.',
								params: {
									syspk: records.get('plcyd_id'),
									plcycode: records.get('plcyd_code')
								},
								success: function (response){
									var data = Ext.decode(response.responseText);
									var gridplcy = Ext.getCmp('instllplcygrid');
									if (data.success == 'true') {
										Ext.Msg.alert('Success', data.message);
										policyInstallstore.load();
										if (gridplcy.getCount() = 0){
											plcytermstore.load();
										}
									}else{
										Ext.Msg.alert('Error', '<font color="red">' + data.message + '</font>');
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
	];
	var termcolModel = [
		//new Ext.grid.RowNumberer(),
		{header:'<b>Months/Term</b>', dataIndex:'number', sortable:true, width:115, tdCls: 'custom-column'}
	];
	var tbar = [{
		xtype: 'searchfield',
		id:'search',
		name:'search',
		fieldLabel: '<b>Search</b>',
		labelWidth: 50,
		width: 290,
		emptyText: "Search by policy code...",
		scale: 'small',
		store: policyInstallstore,
		listeners: {
			change: function(field) {
				var termgrid = Ext.getCmp('termgrid');

				var selected = termgrid.getSelectionModel().getSelection();
				var termselected;
				Ext.each(selected, function (item) {
					termselected = item.data.number;
				});

				if(field.getValue() != ""){
					policyInstallstore.proxy.extraParams = {plcyterm: termselected, showall: showall, query: field.getValue()};
					policyInstallstore.load();
				}else{
					policyInstallstore.proxy.extraParams = {plcyterm: termselected, showall: showall};
					policyInstallstore.load();
				}
			}
		}
	}, '-', {
		text:'<b>Add</b>',
		tooltip: 'Add new installment policy.',
		icon: '../../js/ext4/examples/shared/icons/add.png',
		scale: 'small',
		handler: function(){
			//Ext.Msg.alert('Error', newURL);
			plcytermstore.load();
			plcyfratestore.load();
			plcyrebatestore.load();
			Ext.getCmp('inactive').hide();
			submit_form.getForm().reset();
			submit_form.down('radiogroup').setValue({tax_included: 1})
			submit_window.show();
			Ext.getCmp('moduletype').setValue("INSTLPLCY");
			submit_window.setTitle('Sales Installment Policy Maintenance - Add');
			submit_window.setPosition(500,110);
		}
	}, '->' ,{
		xtype:'splitbutton',
		text: '<b>Maintenance</b>',
		tooltip: 'Select policy maintenance.',
		icon: '../../js/ext4/examples/shared/icons/cog_edit.png',
		scale: 'small',
		menu:[{
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
		}]
	}];

	var submit_form = Ext.create('Ext.form.Panel', {
		id: 'form_submit',
		model: 'plcydtlmodel',
		frame: true,
		defaultType: 'field',
		defaults: {msgTarget: 'under', labelWidth: 125, anchor: '-5'}, //msgTarget: 'side', labelAlign: 'top'
			items: [{
				xtype: 'textfield',
				id: 'syspk',
				name: 'syspk',
				fieldLabel: 'syspk',
				//allowBlank: false,
				hidden: true
			},{
				xtype: 'textfield',
				id: 'moduletype',
				name: 'moduletype',
				fieldLabel: 'moduletype',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'combobox',
				id: 'policytype',
				name: 'policytype',
				fieldLabel: '<b>Policy Type </b>',
				store: plcytypestore,
				displayField: 'name',
				valueField: 'id',
				queryMode: 'local',
				emptyText:'Select policy type',
				padding: '2 0 0 0',
				allowBlank: false,
				forceSelection: true,
				selectOnFocus:true,
				flex: 1,
				listeners: {
					afterrender: function(field) {
						field.focus();
					},
					select: function(combo, record, index) {
						//reload financing store base on category id
						Ext.getCmp('category').clearValue();
						Ext.getCmp('category').setValue(record.get('catid'));

						if (Ext.getCmp('term').getRawValue() == ""){
							Ext.getCmp('plcydcode').setValue(record.get('name'));
						}else{
							Ext.getCmp('plcydcode').setValue(Ext.getCmp('term').getValue() + '-' + record.get('name'));
						}
					}
				}
			},{
				xtype: 'combobox',
				id: 'category',
				name: 'category',
				fieldLabel: '<b>Category </b>',
				store: categorystore,
				displayField: 'name',
				valueField: 'id',
				queryMode: 'local',
				emptyText:'Select category',
				allowBlank: false,
				forceSelection: true,
				selectOnFocus:true,
				hidden: true,
				flex: 1
			},{
				xtype: 'combobox',
				id: 'term',
				name: 'term',
				fieldLabel: '<b>Term </b>',
				store: plcytermstore,
				displayField: 'number',
				valueField: 'number',
				queryMode: 'local',
				emptyText:'select or enter a number',
				allowBlank: false,
				forceSelection: false,
				selectOnFocus:true,
				maskRe: /^([0-9 .`]+)$/,
				flex: 1,
				listeners: {
					blur: function(combo, value) {
						if (Ext.getCmp('policytype').getRawValue() == ""){
							Ext.getCmp('plcydcode').setValue(combo.getValue());
						}else{
							Ext.getCmp('plcydcode').setValue(combo.getValue() + '-' + Ext.getCmp('policytype').getRawValue());
						}
					}
				}
			},{
				xtype: 'combobox',
				id: 'frate',
				name: 'frate',
				fieldLabel: '<b>Financing rate </b>',
				store: plcyfratestore,
				displayField: 'number',
				valueField: 'number',
				queryMode: 'local',
				emptyText:'select or enter a number',
				allowBlank: false,
				forceSelection: false,
				selectOnFocus:true,
				maskRe: /^([0-9 .`]+)$/,
				flex: 1
			},{
				xtype: 'combobox',
				id: 'rebate',
				name: 'rebate',
				fieldLabel: '<b>Rebate </b>',
				store: plcyrebatestore,
				displayField: 'number',
				valueField: 'number',
				queryMode: 'local',
				emptyText:'select or enter a number',
				allowBlank: false,
				forceSelection: false,
				selectOnFocus:true,
				maskRe: /^([0-9 .`]+)$/,
				flex: 1
			},{
				xtype: 'textfield',
				id: 'plcydcode',
				name: 'plcydcode',
				fieldLabel: '<b>Code </b>',
				maxLength: 150,
				allowBlank: false,
				readOnly: true,
				maskRe: /^([a-zA-Z0-9 _.,-`]+)$/,
				fieldStyle : 'text-transform: capitalize; background-color: #ddd; background-image: none; color:green;'
			},{
				xtype: 'container',
				layout: 'hbox',
				defaults: {msgTarget: 'under', anchor: '-5', fieldCls:'DisabledAmountCls', labelWidth: 110},
				margin: '2 0 2 0',
				items:[{
					xtype: 'radiogroup',
					flex:1,
					fieldLabel: '<b>Tax included </b>',
					layout: {type: 'hbox'},
					items: [
						{boxLabel: '<b>Yes</b>', name: 'tax_included',id:'yes', inputValue:1, margin : '0 3 0 0'},
						{boxLabel: '<b>No</b>', name: 'tax_included', id:'no', inputValue:0}
					]
				},{
					xtype: 'checkbox',
					id: 'inactive',
					name: 'inactive',
					boxLabel: '<b>inactive</b>',
					inputValue: 1
				}]
			},{
				xtype: 	'textareafield',
				fieldLabel: '<b>Remarks </b>',
				id:	'remarks',
				name: 'remarks',
				labelAlign:	'top',
				allowBlank: true,
				maxLength: 254,
				padding: '0 0 0 4',
				hidden: false
			}]
	});
	var submit_window = Ext.create('Ext.Window',{
		width 	: 400,
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
			icon: '../../js/ext4/examples/shared/icons/add.png',
			single : true,				
			handler:function(){
				var form_submit = Ext.getCmp('form_submit').getForm();
				if(form_submit.isValid()) {
					form_submit.submit({
						url: '?submit=info',
						waitMsg: 'Saving sales installment policy ' + Ext.getCmp('plcydcode').getValue() + '. please wait...',
						method:'POST',
						success: function(form_submit, action) {
							//show and load new added
							var termgrid = Ext.getCmp('termgrid');
							var term = Ext.getCmp('term');
							//auto select term added.
							plcytermstore.load();
							plcytermstore.on('load', function(){
								plcytermstore.each( function (model, dataindex) {
									var data = model.get('number');
									if (data == term.getRawValue()){
										termgrid.getSelectionModel().select(dataindex);
										 return false;
									}
								});
							});
							policyInstallstore.load({
								params: { plcyterm: term.getRawValue() }
								});
							//console.log(action.response.responseText);
							Ext.MessageBox.confirm('Success!', action.result.message + '<br>Would you like to add more?', function (btn, text) {
								if (btn == 'yes') {
									Ext.getCmp('inactive').hide();
									form_submit.reset();
									plcyfratestore.load();
									plcyrebatestore.load();
									Ext.getCmp('moduletype').setValue("INSTLPLCY");
									submit_window.setTitle('Installment Policy Maintenance - Add');
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
			tooltip: 'Cancel adding installment',
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

	var plcy_panel =  Ext.create('Ext.panel.Panel', { 
        renderTo: 'salesinstalplcy',
		id: 'plcy_panel',
        frame: false,
		width: 1095,
        //labelAlign: 'left',
		layout: 'column',
		tbar: tbar,
		items: [{
			xtype: 'grid',
			id: 'termgrid',
			name: 'termgrid',
			columnWidth: 0.14,
			//width: 250,
			loadMask: true,
			frame: true,
			store:	plcytermstore,
			columns: termcolModel,
			columnLines: true,
			selModel: selModel,
			selType : 'checkboxmodel',
			bbar : {
				xtype : 'pagingtoolbar',
				hidden:true,
				store : plcytermstore,
				pageSize : itemsPerPage,
				displayInfo : false,
				emptyMsg: "No records to display",
				doRefresh : function(){
					plcytermstore.load();
				}
			},
			listeners: {
				cellclick: function(view, td, cellIndex, record, tr, rowIndex, e, eOpts) {
					var termgrid = Ext.getCmp('termgrid');
					var search = Ext.getCmp('search');

					termgrid.getSelectionModel().select(rowIndex);
					if(search.getValue() != ""){
						policyInstallstore.proxy.extraParams = {plcyterm: record.get('number'), showall: showall, query: search.getValue() };
					}else{
						policyInstallstore.proxy.extraParams = {plcyterm: record.get('number'), showall: showall };
					}
					policyInstallstore.load();
				},
				afterrender: function(grid) {
					var termgrid = Ext.getCmp('termgrid');
					plcytermstore.on('load', function(){
						plcytermstore.each( function (model, dataindex) {
							termgrid.getSelectionModel().select(0);
						});
					});
				}
			}
		},{
			xtype: 'splitter',
			width: 2,
			//style: 'border: 1px solid green'
		},{
			xtype: 'grid',
			id: 'instllplcygrid',
			name: 'instllplcygrid',
			columnWidth: 0.78, //0.8
			store:	policyInstallstore,
			columns: ColumnModel,
			columnLines: true,
			autoScroll:true,
			layout:'fit',
			frame: true,
			bbar : {
				xtype : 'pagingtoolbar',
				store : policyInstallstore,
				hidden:false,
				//pageSize : itemsPerPage,
				displayInfo : true,
				emptyMsg: "No records to display",
				doRefresh : function(){
					policyInstallstore.load();
				},
				items:[{
					xtype: 'checkbox',
					id: 'fstatus',
					name: 'fstatus',
					boxLabel: 'Show also Inactive',
					listeners: {
						change: function(column, rowIdx, checked, eOpts){
							var termgrid = Ext.getCmp('termgrid');
							var search = Ext.getCmp('search');
							var selected = termgrid.getSelectionModel().getSelection();
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
								policyInstallstore.proxy.extraParams = {plcyterm: termselected, showall: showall, query: search.getValue()};
							}else{
								policyInstallstore.proxy.extraParams = {plcyterm: termselected, showall: showall};
							}
							policyInstallstore.load();
						}
					}
				}]
			}
		}]
	});
});
