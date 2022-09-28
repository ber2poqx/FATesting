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
	var delfieldcount;
	var maxfields = 10; //change this number if you want to increase/decrease adding fields.
	var caption;
	var MultiStore; //used to load data from multiple stores.
	var branchselected;

	////define model for policy installment
    Ext.define('brnchplcymodel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'branch_code', mapping:'branch_code'},
			{name:'branch_name', mapping:'branch_name'},
			{name:'category_id', mapping:'category_id'},
			{name:'category_name', mapping:'category_name'},
			{name:'plcyinstl_id', mapping:'plcyinstl_id'},
			{name:'plcyinstl_code', mapping:'plcyinstl_code'},
			{name:'plcycashprice_id', mapping:'plcycashprice_id'},
			{name:'plcycashprice_code', mapping:'plcycashprice_code'},
			{name:'plcyprice_id', mapping:'plcyprice_id'},
			{name:'plcyprice_code', mapping:'plcyprice_code'},
			{name:'plcysplrcost_id', mapping:'plcysplrcost_id'},
			{name:'plcysplrcost_code', mapping: 'plcysplrcost_code'},
			{name:'plcysrp_id', mapping: 'plcysrp_id'},
			{name:'plcysrp_code', mapping:'plcysrp_code'},
			{name:'module_type', mapping:'module_type'},
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
	var storebranchplcy = Ext.create('Ext.data.Store', {
		name: 'storebranchplcy',
		fields:['code','name'],
		autoLoad : true,
		pageSize: itemsPerPage,
        proxy: {
			url: '?get_plcybranch=00',
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
	var storebplcycashprice = Ext.create('Ext.data.Store', {
		model: 'brnchplcymodel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?vwplcymode=CSHPRCPLCY',
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
	var storebplcyprice = Ext.create('Ext.data.Store', {
		model: 'brnchplcymodel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?vwplcymode=PRCPLCY',
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
	var storebplcycost = Ext.create('Ext.data.Store', {
		model: 'brnchplcymodel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?vwplcymode=CSTPLCY',
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
	var storebplcysrp = Ext.create('Ext.data.Store', {
		model: 'brnchplcymodel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?vwplcymode=SRPPLCY',
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
	var storebplcyInstl = Ext.create('Ext.data.Store', {
		model: 'brnchplcymodel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?vwplcymode=INSTLPLCY',
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
		fields:['id','name','area'],
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
	var storeInstplcy = Ext.create('Ext.data.Store', {
		name: 'storeInstplcy',
		fields:['id','name','catid'],
		autoLoad : true,
		proxy: {
			url: '?getinstlplcy=00',
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
    var storecashpricecode = Ext.create('Ext.data.Store', {
		name: 'storecashpricecode',
        model: 'comboModel',
		autoLoad : true,
        proxy: {
			url: '?getcashpricecode=00',
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
    var storepricecode = Ext.create('Ext.data.Store', {
		name: 'storepricecode',
        model: 'comboModel',
		autoLoad : true,
        proxy: {
			url: '?getpricecode=00',
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
    var storecategory = Ext.create('Ext.data.Store', {
		name: 'storecategory',
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
	var storecostcode = Ext.create('Ext.data.Store', {
		name: 'storecostcode',
        model: 'comboModel',
		autoLoad : true,
        proxy: {
			url: '?getcostcode=00',
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
	var storesrpcode = Ext.create('Ext.data.Store', {
		name: 'storesrpcode',
		model: 'comboModel',
		autoLoad : true,
        proxy: {
			url: '?getsrpcode=00',
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
	var update_widow = function(store, rowIndex, plcystore){
		var edit_window = Ext.create('Ext.Window',{
			width 	: 400,
			modal	: true,
			title: 'Branch Policy Maintenance - Edit',
			plain 	: true,
			border 	: false,
			resizable: false,
			items:[{
				xtype: 'form',
				id: 'update_form',
				model: 'brnchplcymodel',
				frame: true,
				defaultType: 'field',
				defaults: {msgTarget: 'under', labelWidth: 100, anchor: '-5'},
				items:[{
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
					xtype: 'textfield',
					id: 'totalfields',
					name: 'totalfields',
					fieldLabel: '<b>number </b>',
					allowBlank: false,
					readOnly: true,
					hidden: true
				},{
					xtype: 'textfield',
					id: 'branchname',
					name: 'branchname',
					fieldLabel: 'branchname',
					allowBlank: false,
					hidden: true
				},{
					xtype: 'combobox',
					id: 'branchcode',
					name: 'branchcode',
					fieldLabel: '<b>Branch </b>',
					store: Branchstore,
					displayField: 'name',
					valueField: 'id',
					padding: '2 0 0 0',
					allowBlank: false,
					readOnly: true,
					fieldStyle : 'text-transform: capitalize; background-color: #F2F3F4; background-image: none; color:green; ',
					flex: 1
				},{
					xtype: 'combobox',
					id: 'category',
					name: 'category',
					fieldLabel: '<b>Category </b>',
					allowBlank: false,
					store: storecategory,
					displayField: 'name',
					valueField: 'id',
					queryMode: 'local',
					emptyText:'Select Category',
					forceSelection: true,
					selectOnFocus:true
				},{
					xtype: 'combobox',
					id: 'branchpolicy',
					name: 'branchpolicy',
					fieldLabel: '<b>Policy </b>',
					allowBlank: false,
					store: plcystore,
					displayField: 'name',
					valueField: 'id',
					queryMode: 'local',
					emptyText:'Select branch',
					forceSelection: true,
					selectOnFocus:true
				},{
					xtype: 'radiogroup',
					flex:1,
					fieldLabel: '<b>Active </b>',
					layout: {type: 'hbox'},
					items: [
						{boxLabel: 'Yes', name: 'status',id:'yes',inputValue:0, margin : '0 3 0 0'},
						{boxLabel: 'No', name: 'status', id:'no',inputValue:1}
					]
				}]
			}],
			buttons:[{
				text: '<b>Update</b>',
				tooltip: 'update policy',
				icon: '../js/ext4/examples/shared/icons/page_refresh.png',
				single : true,				
				handler:function(){
					var update_info = Ext.getCmp('update_form').getForm();
					if(update_info.isValid()) {
						update_info.submit({
							url : newURL+'?submit=info',
							waitMsg: 'Updating. please wait...',
							method:'POST',
							success: function(update_info, action) {

								storebplcycashprice.proxy.extraParams = {branch: branchselected, showall: showall};
								storebplcycashprice.load();
								storebplcyprice.proxy.extraParams = {branch: branchselected, showall: showall};
								storebplcyprice.load();
								storebplcycost.proxy.extraParams = {branch: branchselected, showall: showall};
								storebplcycost.load();
								storebplcysrp.proxy.extraParams = {branch: branchselected, showall: showall};
								storebplcysrp.load();
								storebplcyInstl.proxy.extraParams = {branch: branchselected, showall: showall};
								storebplcyInstl.load();

								Ext.Msg.alert('Success!', action.result.message);
								edit_window.close();
							},
							failure: function(update_info, action) {
								Ext.Msg.alert('Failed!', action.result.message);
							}
						});
						window.onerror = function(errorMsg, url, linenumber) { //, column, errorObj
							//alert('An error has occurred!')
							Ext.Msg.alert('System Error: ', errorMsg + ' Script: ' + url + ' Line: ' + linenumber);
							return true;
						}
					}
				}
			},{
				text:'Cancel',
				tooltip: 'Cancel updating branch policy',
				icon: '../js/ext4/examples/shared/icons/cancel.png',
				handler:function(){
					Ext.MessageBox.confirm('Confirm', 'Are you sure you want to close this form?', function (btn, text) {
						if (btn == 'yes') {
							edit_window.close();
						}
					});
				}
			}]
		});
		var records = store.getAt(rowIndex);
		Ext.getCmp('syspk').setValue(records.get('id'));
		Ext.getCmp('totalfields').setValue('1');
		Ext.getCmp('branchname').setValue(records.get('branch_name'));
		Ext.getCmp('branchcode').setValue(records.get('branch_code'));
		Ext.getCmp('category').setValue(records.get('category_id'));
		Ext.getCmp('moduletype').setValue(records.get('module_type'));
		switch (records.get('module_type')) {
			case "CSHPRCPLCY":
				Ext.getCmp('branchpolicy').setValue(records.get('plcycashprice_id'));
				break;
			case "PRCPLCY":
				Ext.getCmp('branchpolicy').setValue(records.get('plcyprice_id'));
				break;
			case "CSTPLCY":
				Ext.getCmp('branchpolicy').setValue(records.get('plcysplrcost_id'));
				break;
			case "SRPPLCY":
				Ext.getCmp('branchpolicy').setValue(records.get('plcysrp_id'));
				break;
			case "INSTLPLCY":
				Ext.getCmp('branchpolicy').setValue(records.get('plcyinstl_id'));
				break;
		};
		if(records.get('status') == 'Yes'){
			Ext.getCmp('yes').setValue(true);
		}else{
			Ext.getCmp('no').setValue(true);
		}
		edit_window.setPosition(500,110);
		edit_window.show();
	};
	//for policy setup column model
	var BrnchcolModel = [
		//new Ext.grid.RowNumberer(),
		{header:'<b>code</b>',dataIndex:'code',hidden: true},
		{header:'<b>Branch</b>', dataIndex:'name', sortable:true, width:403, tdCls: 'custom-column',
			renderer : function renderTip(value, meta, rec, rowIndex, colIndex, store) {
				meta.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		}
	];
	var CASHPRCPLCYModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Id</b>',dataIndex:'id',hidden: true},
		{header:'<b>Category</b>', dataIndex:'category_name', sortable:true, width:220},
		{header:'<b>Cash Price Code</b>', dataIndex:'plcycashprice_code', sortable:true, width:220},
		{header:'<b>Module Type</b>', dataIndex:'module_type', sortable:true, width:195},
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
				icon: '../js/ext4/examples/shared/icons/application_form_edit.png',
				tooltip: 'Edit',
				handler: function(grid, rowIndex, colIndex) {
					update_widow(storebplcycashprice, rowIndex, storecashpricecode);
				}
			},'-',{
				icon   : '../js/ext4/examples/shared/icons/fam/delete.png',
				tooltip : 'Delete',
				handler : function(grid, rowIndex, colIndex){
					var records = storebplcycashprice.getAt(rowIndex);
					var MsgConfirm = Ext.MessageBox.confirm('Confirm', 'Branch: <b>' + records.get('branch_name') + '</b><br\> Category: <b>' + records.get('category_name') + '</b><br\> Cash Price Code: <b>' + records.get('plcycashprice_code') + '</b><br\>  Are you sure you want to delete this record? ', function (btn, text) {
						if (btn == 'yes') {
							Ext.Ajax.request({
								method: 'POST',
								url: '?delete=info',
								waitMsg:'Deleting Record...please wait.',
								params: {
									syspk: records.get('id'),
									moduletype: records.get('module_type')
								},
								success: function (response){
									var data = Ext.decode(response.responseText);
									if (data.success == 'true') {
										storebplcycashprice.load();
										Ext.Msg.alert('Success', data.message);
										//alert(storebplcycashprice.getCount());
										if (storebplcycashprice.getCount() == 0){
											//alert('storebranchplcy');
											storebranchplcy.load();
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
	var PRCPLCYModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Id</b>',dataIndex:'id',hidden: true},
		{header:'<b>Category</b>', dataIndex:'category_name', sortable:true, width:220},
		{header:'<b>Price Code</b>', dataIndex:'plcyprice_code', sortable:true, width:220},
		{header:'<b>Module Type</b>', dataIndex:'module_type', sortable:true, width:195},
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
				icon: '../js/ext4/examples/shared/icons/application_form_edit.png',
				tooltip: 'Edit',
				handler: function(grid, rowIndex, colIndex) {
					update_widow(storebplcyprice, rowIndex, storepricecode);
				}
			},'-',{
				icon   : '../js/ext4/examples/shared/icons/fam/delete.png',
				tooltip : 'Delete',
				handler : function(grid, rowIndex, colIndex){
					var records = storebplcyprice.getAt(rowIndex);
					var MsgConfirm = Ext.MessageBox.confirm('Confirm', 'Branch: <b>' + records.get('branch_name') + '</b><br\> Category: <b>' + records.get('category_name') + '</b><br\> Price Code: <b>' + records.get('plcyprice_code') + '</b><br\>  Are you sure you want to delete this record? ', function (btn, text) {
						if (btn == 'yes') {
							Ext.Ajax.request({
								method: 'POST',
								url: '?delete=info',
								waitMsg:'Deleting Record...please wait.',
								params: {
									syspk: records.get('id'),
									moduletype: records.get('module_type')
								},
								success: function (response){
									var data = Ext.decode(response.responseText);
									if (data.success == 'true') {
										storebplcyprice.load();
										Ext.Msg.alert('Success', data.message);
										//alert(storebplcyprice.getCount());
										if (storebplcyprice.getCount() == 0){
											//alert('storebranchplcy');
											storebranchplcy.load();
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
	var COSTPLCYModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Id</b>',dataIndex:'id',hidden: true},
		{header:'<b>Category</b>', dataIndex:'category_name', sortable:true, width:220},
		{header:'<b>Supplier Cost Code</b>', dataIndex:'plcysplrcost_code', sortable:true, width:220},
		{header:'<b>Module Type</b>', dataIndex:'module_type', sortable:true, width:195},
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
				icon: '../js/ext4/examples/shared/icons/application_form_edit.png',
				tooltip: 'Edit',
				handler: function(grid, rowIndex, colIndex) {
					update_widow(storebplcycost, rowIndex, storecostcode);
				}
			},'-',{
				icon   : '../js/ext4/examples/shared/icons/fam/delete.png',
				tooltip : 'Delete',
				handler : function(grid, rowIndex, colIndex){
					var records = storebplcycost.getAt(rowIndex);
					var MsgConfirm = Ext.MessageBox.confirm('Confirm', 'Branch: <b>' + records.get('branch_name') + '</b><br\> Category: <b>' + records.get('category_name') + '</b><br\> Supplier Cost Code: <b>' + records.get('plcysplrcost_code') + '</b><br\>  Are you sure you want to delete this record? ', function (btn, text) {
						if (btn == 'yes') {
							Ext.Ajax.request({
								method: 'POST',
								url: '?delete=info',
								waitMsg:'Deleting Record...please wait.',
								params: {
									syspk: records.get('id'),
									moduletype: records.get('module_type')
								},
								success: function (response){
									var data = Ext.decode(response.responseText);
									if (data.success == 'true') {
										Ext.Msg.alert('Success', data.message);
										storebplcycost.load();
										if (storebplcycost.getCount() == 0){
											storebranchplcy.load();
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
	var SRPPLCYModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Id</b>',dataIndex:'id',hidden: true},
		{header:'<b>Category</b>', dataIndex:'category_name', sortable:true, width:220},
		{header:'<b>SRP Code</b>', dataIndex:'plcysrp_code', sortable:true, width:220},
		{header:'<b>Module Type</b>', dataIndex:'module_type', sortable:true, width:195},
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
				icon: '../js/ext4/examples/shared/icons/application_form_edit.png',
				tooltip: 'Edit',
				handler: function(grid, rowIndex, colIndex) {
					update_widow(storebplcysrp, rowIndex, storesrpcode);
				}
			},'-',{
				icon   : '../js/ext4/examples/shared/icons/fam/delete.png',
				tooltip : 'Delete',
				handler : function(grid, rowIndex, colIndex){
					var records = storebplcysrp.getAt(rowIndex);
					var MsgConfirm = Ext.MessageBox.confirm('Confirm?', 'Branch: <b>' + records.get('branch_name') + '</b><br\> Category: <b>' + records.get('category_name') + '</b><br\> SRP Code: <b>' + records.get('plcysrp_code') + '</b><br\>  Are you sure you want to delete this record? ', function (btn, text) {
						if (btn == 'yes') {
							Ext.Ajax.request({
								method: 'POST',
								url: '?delete=info',
								waitMsg:'Deleting Record...please wait.',
								params: {
									syspk: records.get('id'),
									moduletype: records.get('module_type')
								},
								success: function (response){
									var data = Ext.decode(response.responseText);
									if (data.success == 'true') {
										Ext.Msg.alert('Success', data.message);
										storebplcysrp.load();
										if (storebplcysrp.getCount() == 0){
											storebranchplcy.load();
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
	var INSTLPLCYModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Id</b>',dataIndex:'id',hidden: true},
		{header:'<b>Category</b>', dataIndex:'category_name', sortable:true, width:220},
		{header:'<b>Installment Rate Code</b>', dataIndex:'plcyinstl_code', sortable:true, width:220},
		{header:'<b>Module Type</b>', dataIndex:'module_type', sortable:true, width:195},
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
				icon: '../js/ext4/examples/shared/icons/application_form_edit.png',
				tooltip: 'Edit',
				handler: function(grid, rowIndex, colIndex) {
					update_widow(storebplcyInstl, rowIndex, storeInstplcy);
				}
			},'-',{
				icon   : '../js/ext4/examples/shared/icons/fam/delete.png',
				tooltip : 'Delete',
				handler : function(grid, rowIndex, colIndex){
					var records = storebplcyInstl.getAt(rowIndex);
					var MsgConfirm = Ext.MessageBox.confirm('Confirm?', 'Branch: <b>' + records.get('branch_name') + '</b><br\> Category: <b>' + records.get('category_name') + '</b><br\> Installment Rate Code: <b>' + records.get('plcyinstl_code') + '</b><br\>  Are you sure you want to delete this record? ', function (btn, text) {
						if (btn == 'yes') {
							Ext.Ajax.request({
								method: 'POST',
								url: '?delete=info',
								waitMsg:'Deleting Record...please wait.',
								params: {
									syspk: records.get('id'),
									moduletype: records.get('module_type')
								},
								success: function (response){
									var data = Ext.decode(response.responseText);
									if (data.success == 'true') {
										Ext.Msg.alert('Success', data.message);
										storebplcyInstl.load();
										if (storebplcyInstl.getCount() == 0){
											storebranchplcy.load();
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
	var add_policy =  function(btn){
		var form = btn.up('form');
		form.count += 1;
		if (form.count <= maxfields){
			form.add([{
				xtype: 'fieldcontainer',
				id: 'pricecontner'+form.count,
				layout: 'hbox',
				defaults: {msgTarget: 'under', anchor: '-5'},
				items:[{
					xtype: 'combobox',
					id: 'category' + form.count,
					name: 'category' + form.count,
					fieldLabel: '<b>Category '+ caption + form.count + '</b>',
					store: storecategory,
					displayField: 'name',
					readonly: true,
					labelWidth: 140,
					valueField: 'id',
					queryMode: 'local',
					emptyText:'Select category',
					allowBlank: false,
					forceSelection: true,
					selectOnFocus:true,
					editable: false,
					flex: 1,
					listeners: {
						/*select: function(combo, record, index) {
							var moduletype = Ext.getCmp('moduletype');
							if(moduletype.getValue() == 'INSTLPLCY'){
								storeInstplcy.proxy.extraParams = {category: combo.getValue()};
								storeInstplcy.load();
								MultiStore = storeInstplcy;
							}
						}*/
					}
				},{
					xtype: 'combobox',
					store: MultiStore,
					id: 'branchpolicy' + form.count,
					name: 'branchpolicy' + form.count,
					fieldLabel: '<b>Policy '+ caption + form.count + '</b>',
					displayField: 'name',
					labelWidth: 120,
					valueField: 'id',
					margin: '0 3 0 0',
					allowBlank: false,
					queryMode: 'local',
					emptyText:'Select policy '+ caption,
					forceSelection: true,
					selectOnFocus:true,
					editable: false,
					flex: 1,
					listeners: {
						select: function(combo, record, index) {
							var moduletype = Ext.getCmp('moduletype');
							if(moduletype.getValue() == 'INSTLPLCY'){
								Ext.getCmp('category' + form.count).setValue(record.get('catid'));
								Ext.getCmp('category' + form.count).Editable = true;
							}
						},
						change: function(combo, record, index){
							//check if combo is not empty. //enable button add field if combo is not empty.
							btn_add = Ext.getCmp('btn_add'+ form.count);
							var btn_num = btn_add.getId().charAt(btn_add.getId().length - 1); //get the number of active button
							var combo_num = combo.getId().charAt(combo.getId().length - 1); //get the number of active combo
							var lastcombo = combo.getId().slice(0,12) + btn_num; //get the id of last added combo
							
							//alert(combo_num);
							if (combo.getRawValue() == "" ){
								btn_add.disable();
							}else if (combo_num == btn_num){
								btn_add.enable();
							}else{
								if(lastcombo.getRawValue() != ""){
									btn_add.enable();
								}else{
									btn_add.disable();
								}
							}
						}
					}
				},{
					xtype: 'button',
					id: 'btn_add' + form.count,
					text: '',
					icon: '../js/ext4/examples/shared/icons/add.png',
					tooltip: 'Add policy price ' + form.count,
					disabled: true,
					width: 35,
					margin: '0 2 0 0',
					handler: add_policy
				},{
					xtype: 'button',
					id: 'btn_remove' + form.count,
					text: '',
					icon: '../js/ext4/examples/shared/icons/cross.png',
					tooltip: 'Remove policy price ' + form.count,
					width: 35,
					handler: removecombobox
				}]
			}]);
			var moduletype = Ext.getCmp('moduletype');
			Ext.getCmp('totalfields').setValue(form.count);
			formcount = form.count;
			formcount = formcount - 1
			if (formcount != 0){
				Ext.getCmp('btn_remove'+formcount).disable();
				Ext.getCmp('btn_add'+formcount).setVisible(false);
			}
			if(moduletype.getValue() == 'INSTLPLCY'){
				//Ext.getCmp('category' + formcount).readOnly = false;
			}else{
				//Ext.getCmp('category' + formcount).readOnly = true;
			}
		}else{
			Ext.Msg.alert('Maximum Controls Limit Exceeded', 'Sorry!, only '+ maxfields + ' fields are allowed. :)');
		}
	};
	var resetform = function(btn){
		var form = Ext.getCmp('form_submit');
		var moduletype = Ext.getCmp('moduletype');
		var totalfields = Ext.getCmp('totalfields');
		var counter = 1;

		addfieldcount = Ext.getCmp('totalfields').getValue();
		
		while (counter <= addfieldcount){ //count adding fields
			var cmpbtn_del = 'btn_remove'+counter;
			var cmpbtn_add = 'btn_add'+counter;
			var cmpCbx = 'branchpolicy'+counter;
			var cmpCntr = 'pricecontner'+counter;
			//delete/destroy components
			Ext.getCmp(cmpbtn_del).destroy();
			Ext.getCmp(cmpbtn_add).destroy();
			Ext.getCmp(cmpCbx).destroy();
			Ext.getCmp('category'+counter).destroy();
			Ext.getCmp(cmpCntr).destroy();

			Ext.getCmp('add_cashprice').enable();
			Ext.getCmp('add_price').enable();
			Ext.getCmp('add_cost').enable();
			Ext.getCmp('add_SIplcy').enable();
			Ext.getCmp('add_srp').enable();
			counter++;
		}
		form.count = 0;
		moduletype.reset();
		totalfields.reset();
		form.getForm().reset();
	};
	var removecombobox = function(thisButton, eventObject) {
		Ext.MessageBox.confirm("Remove Component:", "continue?" , function(btn){
			if(btn == 'yes') {
				var form = Ext.getCmp('form_submit');
				var formcount = form.count;
				formcount = formcount - 1;
				if(formcount != 0){
					Ext.getCmp('btn_add'+formcount).setVisible(true);
				}else{
					//handler: resetform();
					Ext.getCmp('add_cashprice').enable();
					Ext.getCmp('add_price').enable();
					Ext.getCmp('add_cost').enable();
					Ext.getCmp('add_srp').enable();
					Ext.getCmp('add_SIplcy').enable();
					//Ext.getCmp('btn_addpolicy').enable();
				}
				var moduletype = Ext.getCmp('moduletype');
				var cmpbtn = thisButton.getId();
				var btn_num = cmpbtn.substring(10,11);
				
				cbxprice = Ext.getCmp('branchpolicy'+btn_num);
				cntrprice = Ext.getCmp('pricecontner'+btn_num);
				//alert(cbxprice);
				Ext.getCmp(cbxprice.getId()).destroy();
				Ext.getCmp(cmpbtn).destroy();
				Ext.getCmp(cntrprice.getId()).destroy();
				
				addfieldcount = Ext.getCmp('totalfields');
				Ext.getCmp('totalfields').setValue( addfieldcount.getValue() - 1);
				form.count = form.count - 1;
				btn_num = btn_num -1
				if (btn_num != 0){
					btn_remove = Ext.getCmp('btn_remove'+btn_num);
					btn_remove.enable();
				}else{
					form.count = 0;
					Ext.getCmp('bplcywin').setTitle('Branch Policy Maintenance - Add');
				}
			}
		});
	};
	//var submit_form = Ext.create('Ext.form.Panel', {
	var submit_form = {// new Ext.create('Ext.form.Panel', {
		xtype: 'form',
		id: 'form_submit',
		model: 'brnchplcymodel',
		frame: true,
		trackResetOnLoad: true,
		defaultType: 'field',
		count: 0,
		defaults: {msgTarget: 'under', labelWidth: 75, anchor: '-5'}, //msgTarget: 'side', labelAlign: 'top'
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
				xtype: 'textfield',
				id: 'totalfields',
				name: 'totalfields',
				fieldLabel: '<b>number </b>',
				allowBlank: false,
				readOnly: true,
				hidden: true
			},{
				xtype: 'textfield',
				id: 'branchname',
				name: 'branchname',
				fieldLabel: 'branchname',
				allowBlank: false,
				hidden: true
			},{
				xtype: 'combobox',
				id: 'branchcode',
				name: 'branchcode',
				fieldLabel: '<b>Branch </b>',
				store: Branchstore,
				displayField: 'name',
				valueField: 'id',
				queryMode: 'local',
				emptyText:'Select Branch',
				padding: '2 0 0 0',
				allowBlank: false,
				forceSelection: true,
				selectOnFocus:true,
				editable: false,
				flex: 1,
				listeners: {
					afterrender: function(field) {
						field.focus();
					},
					select: function(combo, record, index) {
						Ext.getCmp('branchname').setValue(record.get('name'));
					}
				}
			},{
				xtype: 'fieldset', //'fieldcontainer',
				id: 'btn_addpolicy',
				collapsible: false,
				margin: '0 0 5 5',
				flex: 1,
				layout: {
					align: 'middle',
					pack: 'center',
					type: 'hbox'
				},
				items:[{
					xtype: 'button',
					id: 'add_cashprice',
					text: 'Cash Price',
					cls: 'addbuttonplcy',
					icon: '../js/ext4/examples/shared/icons/basket_go.png',
					tooltip: 'Click to add cash price code builder.',
					margin: '0 10 0 0',
					handler: add_policy,
					listeners: {
						click: function(){
							//storecashpricecode.proxy.extraParams = {plcyterm: record.get('number'), showall: showall };
							//storecashpricecode.load();
							MultiStore = storecashpricecode;
							Ext.getCmp('moduletype').setValue('CSHPRCPLCY');
							Ext.getCmp('add_cashprice').disable();
							Ext.getCmp('add_price').disable();
							Ext.getCmp('add_cost').disable();
							Ext.getCmp('add_SIplcy').disable();
							Ext.getCmp('add_srp').disable();
							//Ext.getCmp('btn_addpolicy').disable();
							caption = 'cash prc ';
							Ext.getCmp('bplcywin').setTitle('Branch Policy Maintenance - Add Cash Price Policy');
						}
					}
				},{
					xtype: 'button',
					id: 'add_price',
					text: 'Price Code',
					cls: 'addbuttonplcy',
					icon: '../js/ext4/examples/shared/icons/coins.png',
					tooltip: 'Click to add price code builder.',
					margin: '0 10 0 0',
					handler: add_policy,
					listeners: {
						click: function(){
							//storepricecode.proxy.extraParams = {plcyterm: record.get('number'), showall: showall };
							//storepricecode.load();
							MultiStore = storepricecode;
							Ext.getCmp('moduletype').setValue('PRCPLCY');
							Ext.getCmp('add_cashprice').disable();
							Ext.getCmp('add_price').disable();
							Ext.getCmp('add_cost').disable();
							Ext.getCmp('add_SIplcy').disable();
							Ext.getCmp('add_srp').disable();
							//Ext.getCmp('btn_addpolicy').disable();
							caption = 'price ';
							Ext.getCmp('bplcywin').setTitle('Branch Policy Maintenance - Add Price Policy');
						}
					}
				},{
					xtype: 'button',
					id: 'add_cost',
					text: 'Cost Code',
					cls: 'addbuttonplcy',
					icon: '../js/ext4/examples/shared/icons/money.png',
					tooltip: 'Click to add cost code builder.',
					margin: '0 10 0 0',
					handler: add_policy,
					listeners: {
						click: function(){
							MultiStore = storecostcode;
							Ext.getCmp('moduletype').setValue('CSTPLCY');
							Ext.getCmp('add_cashprice').disable();
							Ext.getCmp('add_price').disable();
							Ext.getCmp('add_cost').disable();
							Ext.getCmp('add_SIplcy').disable();
							Ext.getCmp('add_srp').disable();
							//Ext.getCmp('btn_addpolicy').disable();
							caption = 'cost ';
							Ext.getCmp('bplcywin').setTitle('Branch Policy Maintenance - Add Supplier Cost Policy');
						}
					}
				},{
					xtype: 'button',
					id: 'add_srp',
					text: 'SRP Code',
					cls: 'addbuttonplcy',
					icon: '../js/ext4/examples/shared/icons/table_edit.png',
					tooltip: 'Click to add srp code',
					margin: '0 10 0 0',
					handler: add_policy,
					listeners: {
						click: function(){
							MultiStore = storesrpcode;
							Ext.getCmp('moduletype').setValue('SRPPLCY');
							Ext.getCmp('add_cashprice').disable();
							Ext.getCmp('add_price').disable();
							Ext.getCmp('add_cost').disable();
							Ext.getCmp('add_SIplcy').disable();
							Ext.getCmp('add_srp').disable();
							//Ext.getCmp('btn_addpolicy').disable();
							caption = 'SRP ';
							Ext.getCmp('bplcywin').setTitle('Branch Policy Maintenance - Add SRP Policy');
						}
					}
				},{
					xtype: 'button',
					id: 'add_SIplcy',
					text: 'Inst. plcy',
					cls: 'addbuttonplcy',
					icon: '../js/ext4/examples/shared/icons/chart_pie.png',
					tooltip: 'Click to add sales installment policy builder.',
					handler: add_policy,
					listeners: {
						click: function(){
							MultiStore = storeInstplcy;
							Ext.getCmp('moduletype').setValue('INSTLPLCY');
							Ext.getCmp('add_cashprice').disable();
							Ext.getCmp('add_price').disable();
							Ext.getCmp('add_cost').disable();
							Ext.getCmp('add_SIplcy').disable();
							Ext.getCmp('add_srp').disable();
							//Ext.getCmp('btn_addpolicy').disable();
							caption = 'Inst. ';
							Ext.getCmp('bplcywin').setTitle('Branch Policy Maintenance - Add Installment Rate Policy');
						}
					}
				}]
			}]
	};
	var Open_window =  function(btn){
		var submit_window = Ext.create('Ext.Window',{
			id: 'bplcywin',
			width 	: 700,
			modal	: true,
			title: 'Branch Policy Maintenance - Add',
			plain 	: true,
			border 	: false,
			resizable: false,
			items:[submit_form],
			buttons:[{
				text: '<b>Save</b>',
				tooltip: 'Save branch policy',
				icon: '../js/ext4/examples/shared/icons/add.png',
				single : true,				
				handler:function(){
					var form_submit = Ext.getCmp('form_submit').getForm();
					if(form_submit.isValid()) {
						form_submit.submit({
							url: '?submit=info',
							waitMsg: 'Saving new policy for branch ' + Ext.getCmp('branchname').getValue() + '. please wait...',
							method:'POST',
							success: function(form_submit, action) {
								//show and load new added
								var Branchgrid = Ext.getCmp('Branchgrid');
								var branchcode = Ext.getCmp('branchcode');
								//auto select term added.
								storebranchplcy.load();
								storebranchplcy.on('load', function(){
									storebranchplcy.each( function (model, dataindex) {
										branchselected = model.get('code');
										if (branchselected == branchcode.getValue()){
											Branchgrid.getSelectionModel().select(dataindex);
											storebplcycashprice.proxy.extraParams = {branch: branchselected, showall: showall};
											storebplcycashprice.load();
											storebplcyprice.proxy.extraParams = {branch: branchselected, showall: showall};
											storebplcyprice.load();
											storebplcycost.proxy.extraParams = {branch: branchselected, showall: showall};
											storebplcycost.load();
											storebplcysrp.proxy.extraParams = {branch: branchselected, showall: showall};
											storebplcysrp.load();
											storebplcyInstl.proxy.extraParams = {branch: branchselected, showall: showall};
											storebplcyInstl.load();

											return false;
										}
									});
								});
								//console.log(action.response.responseText);
								Ext.MessageBox.confirm('Success!', action.result.message + '<br>Would you like to add more?', function (btn, text) {
									if (btn == 'yes') {
										//form_submit.reset();
										handler: resetform(btn);
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
				tooltip: 'Cancel adding branch policy',
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
			},{
				xtype: 'button',
				text: '<b>Reset</b>',
				tooltip: 'Reset form policy',
				icon: '../js/ext4/examples/shared/icons/arrow_refresh_small.png', //reset1.png
				handler: resetform
			}]
		});
		delfieldcount = '0';
		Ext.getCmp('totalfields').setValue(0);
		submit_window.setPosition(350,100);
		submit_window.show();
	};
	var tbar = [{
		xtype: 'searchfield',
		id:'search',
		name:'search',
		fieldLabel: '<b>Search</b>',
		labelWidth: 50,
		width: 290,
		emptyText: "Search by policy code...",
		scale: 'small',
		store: storebplcyprice,
		listeners: {
			change: function(field) {
				var Branchgrid = Ext.getCmp('Branchgrid');
				var selected = Branchgrid.getSelectionModel().getSelection();

				Ext.each(selected, function (item) {
					branchselected = item.data.code;
				});

				if(field.getValue() != ""){
					storebplcycashprice.proxy.extraParams = {branch: branchselected, showall: showall, query: field.getValue()};
					storebplcyprice.proxy.extraParams = {branch: branchselected, showall: showall, query: field.getValue()};
					storebplcycost.proxy.extraParams = {branch: branchselected, showall: showall, query: field.getValue()};
					storebplcysrp.proxy.extraParams = {branch: branchselected, showall: showall, query: field.getValue()};
					storebplcyInstl.proxy.extraParams = {branch: branchselected, showall: showall, query: field.getValue()};
				}else{
					storebplcycashprice.proxy.extraParams = {branch: branchselected, showall: showall};
					storebplcyprice.proxy.extraParams = {branch: branchselected, showall: showall};
					storebplcycost.proxy.extraParams = {branch: branchselected, showall: showall};
					storebplcysrp.proxy.extraParams = {branch: branchselected, showall: showall};
					storebplcyInstl.proxy.extraParams = {branch: branchselected, showall: showall};
				}
				storebplcycashprice.load();
				storebplcyprice.load();
				storebplcycost.load();
				storebplcysrp.load();
				storebplcyInstl.load();
			}
		}
	}, '-', {
		text:'<b>Add</b>',
		tooltip: 'Add new installment policy.',
		icon: '../js/ext4/examples/shared/icons/add.png',
		scale: 'small',
		handler: Open_window
		/*handler: function(){
			submit_form.getForm().reset();
			submit_window.show();
			Ext.getCmp('moduletype').setValue("INSTLPLCY");
			submit_window.setTitle('Branch Policy Maintenance - Add');
			submit_window.setPosition(350,100);
		}*/
	}, '->' ,{
		xtype:'splitbutton',
		text: '<b>Policy Maintenance</b>',
		tooltip: 'Select policy maintenance.',
		icon: '../js/ext4/examples/shared/icons/cog_edit.png',
		scale: 'small',
		menu:[{
			text: '<b>Cash Price Type</b>',
			icon: '../js/ext4/examples/shared/icons/table_gear.png',
			href: '../sales/manage/sales_cash_types.php?'
		},{
			text: '<b>Sales Price Type</b>',
			icon: '../js/ext4/examples/shared/icons/table_gear.png',
			href: '../sales/manage/sales_types.php?'
		},{
			text: '<b>Supplier Cost Types</b>',
			icon: '../js/ext4/examples/shared/icons/table_gear.png',
			href: '../purchasing/manage/supplier_costyps.php?'
		},{
			text: '<b>Standard Retail Price Types</b>',
			icon: '../js/ext4/examples/shared/icons/table_gear.png',
			href: '../inventory/manage/item_srp_area_type.php?'
		},{
			text: '<b>Sales Installment Policy Type</b>',
			icon: '../js/ext4/examples/shared/icons/table_gear.png',
			href: '../sales/manage/sales_installment_policy_type.php?'
		}, '-',{
			text: '<b>Items</b>',
			icon: '../js/ext4/examples/shared/icons/cart.png',
			href: '../inventory/manage/items.php',
			hrefTarget : '_blank'
		},{
			text: '<b>Item Categories</b>',
			icon: '../js/ext4/examples/shared/icons/chart_line.png',
			href: '../inventory/manage/item_categories.php',
			hrefTarget : '_blank'
		}]
	}];
	var plcy_panel =  Ext.create('Ext.panel.Panel', { 
        renderTo: 'salesinstalplcy',
		id: 'plcy_panel',
        frame: false,
		width: 1300,
        //labelAlign: 'left',
		layout: 'column',
		tbar: tbar,
		items: [{
			xtype: 'grid',
			id: 'Branchgrid',
			name: 'Branchgrid',
			columnWidth: 0.34,
			//width: 250,
			loadMask: true,
			frame: true,
			store:	storebranchplcy,
			columns: BrnchcolModel,
			columnLines: true,
			selModel: selModel,
			selType : 'checkboxmodel',
			bbar : {
				xtype : 'pagingtoolbar',
				hidden:true,
				store : storebranchplcy,
				pageSize : itemsPerPage,
				displayInfo : false,
				emptyMsg: "No records to display",
				doRefresh : function(){
					storebranchplcy.load();
				}
			},
			listeners: {
				cellclick: function(view, td, cellIndex, record, tr, rowIndex, e, eOpts) {
					var Branchgrid = Ext.getCmp('Branchgrid');
					var search = Ext.getCmp('search');

					Branchgrid.getSelectionModel().select(rowIndex);
					branchselected = record.get('code');
					
					if(search.getValue() != ""){
						storebplcycashprice.proxy.extraParams = {branch: branchselected, showall: showall, query: search.getValue() };
						storebplcyprice.proxy.extraParams = {branch: branchselected, showall: showall, query: search.getValue() };
						storebplcycost.proxy.extraParams = {branch: branchselected, showall: showall, query: search.getValue() };
						storebplcysrp.proxy.extraParams = {branch: branchselected, showall: showall, query: search.getValue() };
						storebplcyInstl.proxy.extraParams = {branch: branchselected, showall: showall, query: search.getValue() };
					}else{
						storebplcycashprice.proxy.extraParams = {branch: branchselected, showall: showall };
						storebplcyprice.proxy.extraParams = {branch: branchselected, showall: showall };
						storebplcycost.proxy.extraParams = {branch: branchselected, showall: showall };
						storebplcysrp.proxy.extraParams = {branch: branchselected, showall: showall };
						storebplcyInstl.proxy.extraParams = {branch: branchselected, showall: showall };
					}
					storebplcycashprice.load();
					storebplcyprice.load();
					storebplcycost.load();
					storebplcysrp.load();
					storebplcyInstl.load();
				},
				afterrender: function(grid) {
					var Branchgrid = Ext.getCmp('Branchgrid');
					storebranchplcy.on('load', function(){
						storebranchplcy.each( function (model, dataindex) {
							Branchgrid.getSelectionModel().select(0);
						});
					});
				}
			}
		},{
			xtype: 'splitter',
			width: 2
		},{
			xtype: 'tabpanel',
			columnWidth: 0.66, //0.8
			scale: 'small',
			items:[{
				xtype:'gridpanel',
				id: 'CshPricelist',
				//anchor:'100%',
				//title: '<div style="color: red;">Sales Pricing</div>',
				title: 'Cash Pricing',
				autoScroll: true,
				loadMask: true,
				store:	storebplcycashprice,
				columns: CASHPRCPLCYModel,
				columnLines: true,
				frame: true,
				layout:'fit',
				bbar : {
					xtype : 'pagingtoolbar',
					hidden: false,
					store : storebplcycashprice,
					pageSize : itemsPerPage,
					displayInfo : false,
					emptyMsg: "No records to display",
					doRefresh : function(){
						storebplcycashprice.load();
					},
					items:[{
						xtype: 'checkbox',
						id: 'cashprcstatus',
						name: 'cashprcstatus',
						boxLabel: 'Show also Inactive',
						listeners: {
							change: function(column, rowIdx, checked, eOpts){
								var Branchgrid = Ext.getCmp('Branchgrid');
								var search = Ext.getCmp('search');
								var selected = Branchgrid.getSelectionModel().getSelection();
								var branchselected
	
								Ext.each(selected, function (item) {
									branchselected = item.data.code;
								});
								
								if(checked){
									showall = false;
								}else{
									showall = true;
								}
								if(search.getValue() != ""){
									storebplcycashprice.proxy.extraParams = {branch: branchselected, showall: showall, query: search.getValue()};
								}else{
									storebplcycashprice.proxy.extraParams = {branch: branchselected, showall: showall};
								}
								storebplcycashprice.load();
							}
						}
					}]
				}
			},{
				xtype:'gridpanel',
				id: 'Pricelist',
				//anchor:'100%',
				//title: '<div style="color: red;">Sales Pricing</div>',
				title: 'Sales Pricing',
				autoScroll: true,
				loadMask: true,
				store:	storebplcyprice,
				columns: PRCPLCYModel,
				columnLines: true,
				frame: true,
				layout:'fit',
				bbar : {
					xtype : 'pagingtoolbar',
					hidden: false,
					store : storebplcyprice,
					pageSize : itemsPerPage,
					displayInfo : false,
					emptyMsg: "No records to display",
					doRefresh : function(){
						storebplcyprice.load();
					},
					items:[{
						xtype: 'checkbox',
						id: 'prcstatus',
						name: 'prcstatus',
						boxLabel: 'Show also Inactive',
						listeners: {
							change: function(column, rowIdx, checked, eOpts){
								var Branchgrid = Ext.getCmp('Branchgrid');
								var search = Ext.getCmp('search');
								var selected = Branchgrid.getSelectionModel().getSelection();
								var branchselected
	
								Ext.each(selected, function (item) {
									branchselected = item.data.code;
								});
								
								if(checked){
									showall = false;
								}else{
									showall = true;
								}
								if(search.getValue() != ""){
									storebplcyprice.proxy.extraParams = {branch: branchselected, showall: showall, query: search.getValue()};
								}else{
									storebplcyprice.proxy.extraParams = {branch: branchselected, showall: showall};
								}
								storebplcyprice.load();
							}
						}
					}]
				}
			},{
				xtype:'gridpanel',
				id: 'supplrcostlist',
				//anchor:'100%',
				title: 'Supplier Cost',
				autoScroll: true,
				loadMask: true,
				store:	storebplcycost,
				columns: COSTPLCYModel,
				columnLines: true,
				frame: true,
				layout:'fit',
				bbar : {
					xtype : 'pagingtoolbar',
					hidden: false,
					store : storebplcycost,
					pageSize : itemsPerPage,
					displayInfo : false,
					emptyMsg: "No records to display",
					doRefresh : function(){
						storebplcycost.load();
					},
					items:[{
						xtype: 'checkbox',
						id: 'cststatus',
						name: 'cststatus',
						boxLabel: 'Show also Inactive',
						listeners: {
							change: function(column, rowIdx, checked, eOpts){
								var Branchgrid = Ext.getCmp('Branchgrid');
								var search = Ext.getCmp('search');
								var selected = Branchgrid.getSelectionModel().getSelection();
								var branchselected
	
								Ext.each(selected, function (item) {
									branchselected = item.data.code;
								});
								
								if(checked){
									showall = false;
								}else{
									showall = true;
								}
								if(search.getValue() != ""){
									storebplcycost.proxy.extraParams = {branch: branchselected, showall: showall, query: search.getValue()};
								}else{
									storebplcycost.proxy.extraParams = {branch: branchselected, showall: showall};
								}
								storebplcycost.load();
							}
						}
					}]
				}
			},{
				xtype:'gridpanel',
				id: 'srplist',
				//anchor:'100%',
				title: 'Standard retail Price',
				autoScroll: true,
				loadMask: true,
				store:	storebplcysrp,
				columns: SRPPLCYModel,
				columnLines: true,
				frame: true,
				layout:'fit',
				bbar : {
					xtype : 'pagingtoolbar',
					hidden: false,
					store : storebplcysrp,
					pageSize : itemsPerPage,
					displayInfo : false,
					emptyMsg: "No records to display",
					doRefresh : function(){
						storebplcysrp.load();
					},
					items:[{
						xtype: 'checkbox',
						id: 'srpstatus',
						name: 'srpstatus',
						boxLabel: 'Show also Inactive',
						listeners: {
							change: function(column, rowIdx, checked, eOpts){
								var Branchgrid = Ext.getCmp('Branchgrid');
								var search = Ext.getCmp('search');
								var selected = Branchgrid.getSelectionModel().getSelection();
								var branchselected
	
								Ext.each(selected, function (item) {
									branchselected = item.data.code;
								});
								
								if(checked){
									showall = false;
								}else{
									showall = true;
								}
								if(search.getValue() != ""){
									storebplcysrp.proxy.extraParams = {branch: branchselected, showall: showall, query: search.getValue()};
								}else{
									storebplcysrp.proxy.extraParams = {branch: branchselected, showall: showall};
								}
								storebplcysrp.load();
							}
						}
					}]
				}
			},{
				xtype:'gridpanel',
				id: 'instlplcy',
				//anchor:'100%',
				title: 'Sales Installment Policy',
				autoScroll: true,
				loadMask: true,
				store:	storebplcyInstl,
				columns: INSTLPLCYModel,
				columnLines: true,
				frame: true,
				layout:'fit',
				bbar : {
					xtype : 'pagingtoolbar',
					hidden: false,
					store : storebplcyInstl,
					pageSize : itemsPerPage,
					displayInfo : false,
					emptyMsg: "No records to display",
					doRefresh : function(){
						storebplcyInstl.load();
					},
					items:[{
						xtype: 'checkbox',
						id: 'instlstatus',
						name: 'instlstatus',
						boxLabel: 'Show also Inactive',
						listeners: {
							change: function(column, rowIdx, checked, eOpts){
								var Branchgrid = Ext.getCmp('Branchgrid');
								var search = Ext.getCmp('search');
								var selected = Branchgrid.getSelectionModel().getSelection();
								var branchselected
	
								Ext.each(selected, function (item) {
									branchselected = item.data.code;
								});
								
								if(checked){
									showall = false;
								}else{
									showall = true;
								}
								if(search.getValue() != ""){
									storebplcyInstl.proxy.extraParams = {branch: branchselected, showall: showall, query: search.getValue()};
								}else{
									storebplcyInstl.proxy.extraParams = {branch: branchselected, showall: showall};
								}
								storebplcyInstl.load();
							}
						}
					}]
				}
			}]
		}]
	});
});
