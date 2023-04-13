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
	var Area = '';
	////define model for policy
    Ext.define('policytypeModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'plcy_code', mapping:'plcy_code'},
			{name:'name', mapping:'name'},
			{name:'tax_included', mapping:'tax_included'},
			{name:'factor', mapping:'factor'},
			{name:'dateadded', mapping:'dateadded'},
			{name:'remarks', mapping:'remarks'},
			{name:'module_type', mapping:'module_type'},
			{name:'status', mapping:'status'},
			{name:'category_id ', mapping:'category_id '},
			{name:'category ', mapping:'category '},
			{name:'brancharea_id', mapping:'brancharea_id'},
			{name:'brancharea_code', mapping:'brancharea_code'},
			{name:'brancharea', mapping:'brancharea'}
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
	//create a store for policy
    var policytypestore = Ext.create('Ext.data.Store', {
        model: 'policytypeModel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
        proxy: {
            url: '?vwpolicytyp=00',
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
	var vwbranchtore = Ext.create('Ext.data.Store', {
		model: 'comboModel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?vwbranchdata=00',
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
	var Branchstore = Ext.create('Ext.data.Store', {
		name: 'Branchstore',
		fields:['id','code','name'],
		autoLoad : true,
        proxy: {
			url: '?getbranchArea=00',
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
	//---------------------------------------------------------------------------------------

	//for policy setup column model
	var ColumnModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Id</b>',dataIndex:'id',hidden: true},
		{header:'<b>Plcy Code</b>', dataIndex:'plcy_code', sortable:true, width:173},
		{header:'<b>Category</b>', dataIndex:'category', sortable:true, width:200},
		{header:'<b>Date Added</b>', dataIndex:'dateadded', sortable:true, width:120, renderer: Ext.util.Format.dateRenderer('m-d-Y')},
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
					var records = policytypestore.getAt(rowIndex);
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
					var records = policytypestore.getAt(rowIndex);
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
										policytypestore.load();
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
	];
	var AreacolModel = [
		//new Ext.grid.RowNumberer(),
		{header:'<b>Area</b>', dataIndex:'name', sortable:true, width:160, tdCls: 'custom-column'}
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
		store: policytypestore,
		listeners: {
			change: function(field) {
				var Areagrid = Ext.getCmp('Areagrid');

				var selected = Areagrid.getSelectionModel().getSelection();
				var areaselected;
				Ext.each(selected, function (item) {
					areaselected = item.data.id;
				});

				if(field.getValue() != ""){
					policytypestore.proxy.extraParams = {brancharea: areaselected, showall: showall, query: field.getValue()};
					policytypestore.load();
				}else{
					policytypestore.proxy.extraParams = {brancharea: areaselected, showall: showall};
					policytypestore.load();
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
			submit_form.getForm().reset();
			Branchstore.load();
			categorystore.load();
			Ext.getCmp('inactive').hide();
			submit_form.down('radiogroup').setValue({tax_included: 1})
			submit_window.show();
			Ext.getCmp('moduletype').setValue("INSTLPLCYTYPS");
			submit_window.setTitle('Installment Policy Type Maintenance  - Add');
			submit_window.setPosition(500,110);
		}
	}, '->' ,{
		xtype:'splitbutton',
		text: '<b>Maintenance</b>',
		tooltip: 'Select policy maintenance.',
		icon: '../../js/ext4/examples/shared/icons/cog_edit.png',
		scale: 'small',
		menu:[{
			text: '<b>Area</b>',
			icon: '../../js/ext4/examples/shared/icons/map_magnify.png',
			href: '../../admin/branch_areas.php?',
		},{
			text: '<b>Item Categories</b>',
			icon: '../../js/ext4/examples/shared/icons/chart_line.png',
			href: '../../inventory/manage/item_categories.php?',
		}, '-',{
			text: '<b>Sales Installment Policy</b>',
			icon: '../../js/ext4/examples/shared/icons/table_gear.png',
			href: 'sales_installment_policy.php?'
		},{
			text: '<b>Items</b>',
			icon: '../../js/ext4/examples/shared/icons/cart.png',
			href: '../../inventory/manage/items.php',
			hrefTarget : '_blank'
		}]
	}];

	var submit_form = Ext.create('Ext.form.Panel', {
		id: 'form_submit',
		model: 'policytypeModel',
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
				id: 'brancharea',
				name: 'brancharea',
				fieldLabel: '<b>Area </b>',
				store: Branchstore,
				displayField: 'name',
				valueField: 'id',
				queryMode: 'local',
				emptyText:'Select branch area',
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
						Area = record.get('code');
						/*if (Ext.getCmp('category').getRawValue() == ""){
							Ext.getCmp('plcycode').setValue(record.get('code')+'-');
						}else{*/
							if(record.get('code') != ""){
								Ext.getCmp('plcycode').setValue(record.get('code')+'-'+ Ext.getCmp('category').getRawValue());
							}else{
								if(Ext.getCmp('category').getRawValue() == ""){
									Ext.getCmp('plcycode').setValue('');
								}
							}
						//}
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
				flex: 1,
				listeners: {
					select: function(combo, record, index) {
						if(Area != ""){
							Ext.getCmp('plcycode').setValue(Area +'-' + record.get('name'));
						}else{
							Ext.getCmp('plcycode').setValue('');
						}
						
					}
				}
			},{
				xtype: 'textfield',
				id: 'plcycode',
				name: 'plcycode',
				fieldLabel: '<b>Code </b>',
				maxLength: 150,
				allowBlank: false,
				//readOnly: true,
				maskRe: /^([a-zA-Z0-9 _.,-`]+)$/,
				fieldStyle : 'text-transform: uppercase; background-color: #F2F3F4; background-image: none; color:green; font-weight:bold;'
			},{
				xtype: 'container',
				layout: 'hbox',
				defaults: {msgTarget: 'under', anchor: '-5', fieldCls:'DisabledAmountCls', labelWidth: 110},
				margin: '2 0 2 0',
				//hidden: true,
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
						waitMsg: 'Saving sales installment policy type ' + Ext.getCmp('plcycode').getValue() + '. please wait...',
						method:'POST',
						success: function(form_submit, action) {
							//show and load new added
							var Areagrid = Ext.getCmp('Areagrid');
							var brancharea = Ext.getCmp('brancharea');
							//auto select brancharea added.
							vwbranchtore.load();
							vwbranchtore.on('load', function(){
								vwbranchtore.each( function (model, dataindex) {
									var data = model.get('name');
									if (data == brancharea.getRawValue()){
										Areagrid.getSelectionModel().select(dataindex);
										 return false;
									}
								});
							});
							policytypestore.load({
								params: { brancharea: brancharea.getValue() }
								});
							//console.log(action.response.responseText);
							Ext.MessageBox.confirm('Success!', action.result.message + '<br>Would you like to add more?', function (btn, text) {
								if (btn == 'yes') {
									Ext.getCmp('inactive').hide();
									form_submit.reset();
									Ext.getCmp('moduletype').setValue("INSTLPLCYTYPS");
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
        renderTo: 'ext-form',
		id: 'plcy_panel',
        frame: false,
		width: 1095,
        //labelAlign: 'left',
		layout: 'column',
		tbar: tbar,
		items: [{
			xtype: 'grid',
			id: 'Areagrid',
			name: 'Areagrid',
			columnWidth: 0.18,
			loadMask: true,
			frame: true,
			store:	vwbranchtore,
			columns: AreacolModel,
			columnLines: true,
			selModel: selModel,
			selType : 'checkboxmodel',
			flex: 1,
			bbar : {
				xtype : 'pagingtoolbar',
				hidden:true,
				store : vwbranchtore,
				pageSize : itemsPerPage,
				displayInfo : false,
				emptyMsg: "No records to display",
				doRefresh : function(){
					vwbranchtore.load();
				}
			},
			listeners: {
				cellclick: function(view, td, cellIndex, record, tr, rowIndex, e, eOpts) {
					var Areagrid = Ext.getCmp('Areagrid');
					var search = Ext.getCmp('search');

					Areagrid.getSelectionModel().select(rowIndex);
					if(search.getValue() != ""){
						policytypestore.proxy.extraParams = {brancharea: record.get('id'), showall: showall, query: search.getValue(), isclick: 1 };
					}else{
						policytypestore.proxy.extraParams = {brancharea: record.get('id'), showall: showall, isclick: 1 };
					}
					policytypestore.load();
				},
				afterrender: function(grid) {
					var Areagrid = Ext.getCmp('Areagrid');
					vwbranchtore.on('load', function(){
						vwbranchtore.each( function (model, dataindex) {
							Areagrid.getSelectionModel().select(0);
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
			columnWidth: 0.65, //0.8
			flex: 2,
			store:	policytypestore,
			columns: ColumnModel,
			columnLines: true,
			autoScroll:true,
			layout:'fit',
			frame: true,
			bbar : {
				xtype : 'pagingtoolbar',
				store : policytypestore,
				hidden:false,
				//pageSize : itemsPerPage,
				displayInfo : true,
				emptyMsg: "No records to display",
				doRefresh : function(){
					policytypestore.load();
				},
				items:[{
					xtype: 'checkbox',
					id: 'fstatus',
					name: 'fstatus',
					boxLabel: 'Show also Inactive',
					listeners: {
						change: function(column, rowIdx, checked, eOpts){
							var Areagrid = Ext.getCmp('Areagrid');
							var search = Ext.getCmp('search');
							var selected = Areagrid.getSelectionModel().getSelection();
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
								policytypestore.proxy.extraParams = {brancharea: termselected, showall: showall, query: search.getValue()};
							}else{
								policytypestore.proxy.extraParams = {brancharea: termselected, showall: showall};
							}
							policytypestore.load();
						}
					}
				}]
			}
		}]
	});
});
