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
	'Ext.selection.CellModel',
	'Ext.form.field.File',
	'Ext.ux.form.SearchField',
	'Ext.ux.form.NumericField'

]);

Ext.onReady(function(){
	Ext.QuickTips.init();
	var itemsPerPage = 20;   // set the number of items you want per page on grid.
	var all = false;
	
	var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
		clicksToEdit: 1
	});

 	Ext.define('insurance', {
		extend : 'Ext.data.Model',
		fields  : [
			{name:'trans_id',mapping:'trans_id'},
			{name:'reference',mapping:'reference'},
			{name:'trans_date',mapping:'trans_date'},			
			{name:'loc_name',mapping:'loc_name'},
			{name:'category',mapping:'category'},
			{name:'category_id',mapping:'category_id'},
			{name:'remarks',mapping:'remarks'},
			{name:'qty',mapping:'qty'}			
		]
	});

	Ext.define('myrequeststock',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'stock_id', mapping:'stock_id'},
			{name:'item_code', mapping:'item_code'},
			{name:'stock_description', mapping:'stock_description'},
			{name:'trans_date', mapping:'trans_date'},
			{name:'category_id', mapping:'category_id'},
			{name:'serialise_id', mapping:'serialise_id'},
			{name:'color', mapping:'color'},
			{name:'line_item', mapping:'line_item'},
			{name:'qty', mapping:'qty', type: 'float'}
		]
	});

	var RequestHeader =[
		{header:'ID', dataIndex:'trans_id', sortable:true, width:20, hidden: true},
		{header:'Request Date', dataIndex:'trans_date', sortable:true, width:55,
			renderer: function(value, metaData, record, rowIndex, colIndex, store) {
				return '<span style="color:black; font-weight:bold;">' + value + '</span>';
			}	
		},
        {header:'Request No.', dataIndex:'reference', sortable:true, width:70,
			renderer: function(value, metaData, record, rowIndex, colIndex, store) {
				return '<span style="color:blue; font-weight:bold;">' + value + '</span>';
			}	
		},
		{header:'Request To', dataIndex:'loc_name', sortable:true, width:170,
			renderer: function(value, metaData, record, rowIndex, colIndex, store) {
				return '<span style="color:black; font-weight:bold;">' + value + '</span>';
			}	
		},
		{header:'Category', dataIndex:'category', sortable:true, width:50,
			renderer: function(value, metaData, record, rowIndex, colIndex, store) {
				return '<span style="color:green; font-weight:bold;">' + value + '</span>';
			}
		},
        {header:'Total Items', dataIndex:'qty', sortable:true, width:38, align:'center',
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:black; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') + '</span>';
			}
		},
		{header:'Remarks', dataIndex:'remarks', sortable:true, width:120,
			renderer: function(value, metaData, record, rowIndex, colIndex, store) {
				return '<span style="color:black; font-weight:bold;">' + value + '</span>';
			}
		},
		{header	: 'Action',	xtype:'actioncolumn', align:'center', width:40,
			items:[
				{
					icon: '../js/ext4/examples/shared/icons/printer.png',
					tooltip: 'View Request Stock Details',
					handler: function(grid, rowIndex, colIndex) {
						var record = RequeststockviewStore.getAt(rowIndex);
						rsd_trans_id = record.get('trans_id');
						
						window.open('../reports/request_stock_report.php?rsd_id='+rsd_trans_id);
					}
				}
			]
		}
	];

	var columnRequestModel = [
		{xtype: 'rownumberer'},
		{header:'#', dataIndex:'id', sortable:true, width:50, align:'center', hidden: true},
		{header:'Item Code', dataIndex:'item_code', width:150, sortable:true, renderer: columnWrap,hidden: false},
		{header:'Description', dataIndex:'stock_description', sortable:true, width:230, renderer: columnWrap,hidden: false},
		{header:'Color', dataIndex:'color', sortable:true, width:190, renderer: columnWrap,hidden: false},
		{header:'Category', dataIndex:'category_id', sortable:true, width:100, renderer: columnWrap,hidden: true},
		{header:'Location', dataIndex:'loc_code', sortable:true,width:100, hidden: true},
		{header:'Qty', dataIndex:'qty', sortable:true, width:90, hidden: false, align:'center',
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return '<span style="color:red; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:black; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			},
			summaryType: 'sum',
			summaryRenderer: function(value, summaryData, dataIndex){
				return '<span style="color:blue;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';									
			},
			editor:{
				field:{
					xtype:'textfield',
					name:'qty',
					id: 'qty',
					anchor:'100%',
					listeners: {
						afterrender: function(field) {
							field.focus(true);
						}
					}	
				}
			}	
		},
		{header:'Action',xtype:'actioncolumn', align:'center',
			items:[{
				icon:'../js/ext4/examples/shared/icons/cancel.png',
				tooltip:'Delete',
				handler: function(grid, rowIndex, colIndex){
					var record = StockRequestStore.getAt(rowIndex);				
					var line_item = record.get('line_item');
					var serialise_id = record.get('serialise_id');
					var AdjDate = Ext.getCmp('AdjDate').getValue();	
											
					Ext.Ajax.request({
						url : '?action=RemoveItem',
						method: 'GET',
						params:{serialise_id: serialise_id, AdjDate:AdjDate, line_item: line_item},
						success: function (response){
							StockRequestStore.load({
								params: {view: 1}, 
								scope: this,
								callback: function(records, operation, success){
									var countrec = StockRequestStore.getCount();
									if(countrec>0){
										setButtonDisabled(false);
									}else{
										setButtonDisabled(true);
									}								
								}
							});
						}
					});
				}
			}]
		}
	];
	
    var RequeststockviewStore = Ext.create('Ext.data.Store', {
		model : 'insurance',
		name : 'RequeststockviewStore',
		method : 'POST',
		pageSize: itemsPerPage, // items per page
		proxy : {
			type: 'ajax',
			url	: '?action=view',
			reader:{
				type : 'json',
				root : 'result',
				totalProperty : 'total'
			}
		},
		autoLoad: true
	});

	function columnWrap(val){
		return '<div style="white-space:normal !important;">'+ val +'</div>';
	}

	var BranchListingStore = Ext.create('Ext.data.Store', {
		fields: ['loc_code', 'location_name', 'branch_id'],
		autoLoad: false,
		//pageSize: itemsPerPage, // items per page
		proxy : {
			type: 'ajax',
			url	: '?action=branch_location',
			reader:{
				type : 'json',
				root : 'result',
				totalProperty : 'total'
			}
		}
	});

	var BrandListingStore = Ext.create('Ext.data.Store', {
		fields:['brand_id','brand_name'],
		autoLoad: false,
		proxy: {
			type:'ajax',
			url: '?action=brand',
			reader:{
				type : 'json',
				root : 'result',
				totalProperty : 'total'
			}
		}
	});

	var StockRequestStore = Ext.create('Ext.data.Store', {
	    storeId:'DetaiLRequestListStore',
		model: myrequeststock,
		name : 'StockRequestStore',
		autoLoad: false,
		autoSync: true,
		proxy : {
			type: 'ajax',
			url	: '?',
			reader:{
				type : 'json',
				rootProperty : 'result',
				totalProperty : 'total'
			},
			api:{
				read:'?action=AddItem',
				update:'?action=updateData'
			},
			writer:{
				type:'json',
				encode:true,
				rootProperty:'dataUpdate',
				allowSingle:false,
				writeAllFields: true
			},
			actionMethods:{
				read:'GET',
				update: 'GET'
			}
		}
	});

	var ItemListingStore = Ext.create('Ext.data.Store', {
		fields: ['model', 'color', 'item_description', 'stock_description', 'qty', 'category_id', 'serialised', 'brand_id', 'brand_name'],
		autoLoad: false,
		proxy : {
			type: 'ajax',
			url	: '?action=items_listing',
			reader:{
				type : 'json',
				root : 'result',
				totalProperty : 'total'
			}
		}
	});

	var columnItemSerial = [
		{header:'id', dataIndex:'serialise_id', sortable:true, width:60,hidden: true},
		{header:'Serialise', dataIndex:'serialised', sortable:true, width:30, renderer: columnWrap,hidden: true},
		{header:'Brand_id', dataIndex:'brand_id', sortable:true, width:30, renderer: columnWrap,hidden: true},
		{header:'Brand', dataIndex:'brand_name', sortable:true, width:60, renderer: columnWrap,hidden: false},
		{header:'Model', dataIndex:'model', sortable:true, width:100, renderer: columnWrap,hidden: false},
		{header:'Item Code', dataIndex:'item_code', sortable:true, width:150, renderer: columnWrap,hidden: false},
		{header:'Item Description', dataIndex:'stock_description', sortable:true, width:170, renderer: columnWrap,hidden: false},
		{header:'Color', dataIndex:'item_description', sortable:true, width:150, renderer: columnWrap,hidden: false},
		{header:'Category', dataIndex:'category_id', sortable:true, width:100,hidden: true}
	]

	Ext.define('windowNewTransfer', {
		extend: 'Ext.window.Window',
		id: 'windowNewTransfer',
		title:'Request Stock Delivery Entry',
		modal: true,
		width: 810,
		bodyPadding: 5,
		layout:'anchor',
		items:[{
				xtype:'fieldset',
				layout:'anchor',
				defaultType:'textfield',
				fieldDefaults:{labelAlign:'right'},
				items:[{
					xtype:'fieldcontainer',
					layout:'hbox',
					margin: '2 0 2 5',
					items:[{
						xtype:'combobox',
						fieldLabel:'Request To',
						name:'ToStockLocation',
						id:'ToStockLocation',
						queryMode:'local',
						triggerAction : 'all',
						displayField  : 'location_name',
						valueField    : 'loc_code',
						editable      : true,
						forceSelection: true,
						allowBlank: false,
						required: true,
						width:735,
						hiddenName: 'loc_code',
						typeAhead: true,
						readOnly: false,
						anyMatch: true,
						emptyText:'Select Request Location',
						fieldStyle : 'background-color: #F2F3F4; color:green; font-weight:bold;',
						selectOnFocus:true,
						store: Ext.create('Ext.data.Store',{
							fields: ['loc_code', 
									'location_name', 
									'delivery_address', 
									'phone', 
									'phone2'
							],
							autoLoad: true,
							proxy: {
								type:'ajax',
								url: '?action=branch_location',
								reader:{
									type : 'json',
									root : 'result',
									totalProperty : 'total'
								}
							}
						}),
					}]
				},{
					xtype:'fieldcontainer',
					layout:'hbox',
					margin: '2 0 2 5',
					items:[{
						xtype:'combobox',
						fieldLabel:'Category',
						name:'category',
						id:'category',
						queryModel:'local',
						triggerAction:'all',
						displayField  : 'description',
						valueField    : 'category_id',
						editable      : true,
						forceSelection: true,
						allowBlank: false,
						required: true,
						hiddenName: 'category_id',
						typeAhead: true,
						emptyText:'Select Category',
						fieldStyle : 'background-color: #F2F3F4; color:green; font-weight:bold;',                    									
						selectOnFocus:true,
						store: Ext.create('Ext.data.Store',{
							fields: ['category_id', 'description'],
							autoLoad: true,
							proxy: {
								type:'ajax',
								url: '?action=category',
								reader:{
									type : 'json',
									root : 'result',
									totalProperty : 'total'
								}
							}
						}),			
				},{			
					xtype:'textfield',
					name:'rsdno',
					id:'rsdno',
					fieldLabel:'RSD #',
					allowBlank: true,
					hidden:true
				},{
					xtype:'textfield',
					name:'servedby',
					id:'servedby',
					fieldLabel:'Request By',
					labelWidth:80,
					width: 278,
					fieldStyle : 'background-color: #F2F3F4; color:black; font-weight:bold;',
					readOnly: true
				},{
					xtype:'datefield',
					fieldLabel:'Date',
					name:'trans_date',
					id:'AdjDate',
					width: 181,
					labelWidth: 40,
					fieldStyle : 'background-color: #F2F3F4; color:black; font-weight:bold;'					
					}]
				},{
					xtype:'fieldcontainer',
					width:735,
					layout:'hbox',
					margin: '2 0 2 5',
					layout:'fit',
					items:[{
						xtype:'textareafield',
						fieldLabel:'Particulars',
						name:'memo',
						id:'memo',
						grow: true,
						anchor:'100%'
					}]
				},{
					xtype:'textfield',
					name:'fromlocation',
					id:'fromlocation',
					fieldLabel:'Branchcode',
					allowBlank: true,
					readOnly: true,
					hidden:true
				}]
		},{
			xtype:'panel',
			title:'Items',
			frame: true,
			anchor:'100%',
			layout:'fit',
			border: false,
			items:[{
				xtype:'grid',
				id:'rsdgrid',
				loadMask:true,
				anchor:'100%',
				store: StockRequestStore,
				columns: columnRequestModel,
				columnLines: true,
				height: 270,
				width: 400,
				autoScroll:true,
				layout:'fit',
				selModel: 'cellmodel',
				plugins: {
					ptype: 'cellediting',
					clicksToEdit: 1
				},
				features: [{
					ftype: 'summary'
				}],
				border: false,
				frame:false,
				dockedItems:[{
					dock	: 'top',
					xtype	: 'toolbar',
					name 	: 'newMTsearch',
					items:[{
						icon   	: '../js/ext4/examples/shared/icons/fam/add.gif',
						tooltip	: 'Select Item',
						text 	: 'Select Item',
						handler: function(){
							var categoryheader = Ext.getCmp('category').getValue();
							if(categoryheader==null){
								Ext.Msg.alert('Warning','Please select category');
								return false;	
							}else{
								var catcode = Ext.getCmp('category').getValue();								
								var brcode = Ext.getCmp('fromlocation').getValue();								
								var AdjDate = Ext.getCmp('AdjDate').getValue();

								ItemListingStore.proxy.extraParams = {catcode: catcode, branchcode:brcode, trans_date:AdjDate}
								ItemListingStore.load();

								BrandListingStore.proxy.extraParams = {catcode: catcode}
								BrandListingStore.load();

								var win = Ext.create('windowItemList');
								win.show();
							}
						}	
					}]
				}]
			}]
		}],buttons:[{
			text:'Process Request',
			disabled: true,
			id:'btnProcess',
			handler:function(btp){
				Ext.MessageBox.confirm('Confirm', 'Are you sure you want to Process this request?', ApprovalFunction);
				function ApprovalFunction(btn) {
					if(btn == 'yes') {
						setButtonDisabled(true);
						var gridData = StockRequestStore.getRange();
						var gridRepoData = [];													
						count = 0;													
						Ext.each(gridData, function(item) {
							var ObjItem = {							
								qty: item.get('qty')														
							};
							gridRepoData.push(ObjItem);
						});
						
						var AdjDate = Ext.getCmp('AdjDate').getValue();	
						var catcode = Ext.getCmp('category').getValue();
						var FromStockLocation = Ext.getCmp('fromlocation').getValue();
						var ToStockLocation = Ext.getCmp('ToStockLocation').getValue();
						var servedby = Ext.getCmp('servedby').getValue();
						var memo_ = Ext.getCmp('memo').getValue();
						if(ToStockLocation==null){
							setButtonDisabled(false);
							Ext.MessageBox.alert('Error','Select Branch to Transfer Location');
							return false;
						}
						if(catcode==null){
							setButtonDisabled(false);
							Ext.MessageBox.alert('Error','Select Category Item');
							return false;
						}													
						Ext.MessageBox.show({
							msg: 'Saving Transaction, please wait...',
							progressText: 'Saving...',
							width:300,
							wait:true,
							waitConfig: {interval:200},													
							iconHeight: 50
						});
									
						Ext.Ajax.request({
							url : '?action=SaveTransfer',
							method: 'POST',
							params:{
								AdjDate:AdjDate,
								catcode:catcode,
								FromStockLocation: FromStockLocation,
								ToStockLocation: ToStockLocation,
								memo_: memo_,
								servedby:servedby,
								requestdata: Ext.encode(gridRepoData),
								value:btn
							},
							success: function(response){
								Ext.MessageBox.hide();
								var jsonData = Ext.JSON.decode(response.responseText);
								var errmsg = jsonData.message;															
								if(errmsg!=''){
									setButtonDisabled(false);
									Ext.MessageBox.alert('Error',errmsg);
								}else{
									btp.up('window').close();							
									RequeststockviewStore.load();
									Ext.MessageBox.alert('Success','Success Processing');
								}													
							} 
						});										
					}
				}
			}
		},{
			text:'Close',
			handler: function(bt){
				Ext.MessageBox.confirm('Confirm:', 'Are you sure you wish to close this window?', function (btn, text) {
					if (btn == 'yes') {
						bt.up('window').close();	
					}
				});
			}
		}]
	});

	Ext.define('windowItemList', {
		extend: 'Ext.window.Window',
		title:'Item Listing',
		id:'windowItemList',
		modal: true,
		width: 1000,
		height:470,
		bodyPadding: 3,
		layout:'fit',
		items:[{
				xtype:'panel',
				autoScroll: true,
				frame:false,
				items:[{
					xtype:'grid',
					forceFit: true,
					layout:'fit',
					id:'ItemSerialListing',
					store: ItemListingStore,
					columns: columnItemSerial,
					selModel: {
						selType: 'checkboxmodel',
						id: 'checkidbox',
						checkOnly: true,
						mode: 'Multi'			
					},				
					dockedItems:[{
						dock:'top',
						xtype:'toolbar',
						name:'searchSerialBar',
						items:[{							
							xtype:'combo',
							hidden: false,
							fieldLabel:'Brand',
							width: 300,
							labelWidth: 100,
							name:'serialise_brand',
							id:'serialise_brand',
							store: BrandListingStore,
							queryMode: 'local',
							triggerAction : 'all',
							displayField  : 'brand_name',
							valueField    : 'brand_id',
							editable      : true,
							forceSelection: false,
							allowBlank: true,
							required: false,
							hiddenName: 'brand_id',
							typeAhead: true,
							selectOnFocus:true,
							anyMatch:true,					
							listeners: {
								select: function(cmb, rec, idx) {
									ItemListingStore.proxy.extraParams = {brand:this.getValue(), querystr:Ext.getCmp('searchSerial').getValue(), catcode:Ext.getCmp('category').getValue()}
									ItemListingStore.load();	
								}
							}
						},{
							xtype:'textfield',
							name:'searchSerial',
							id:'searchSerial',
							fieldLabel:'Item Code/Item Description',
							labelWidth: 180,
							listeners : {											
								change: function(field) {									
									ItemListingStore.proxy.extraParams = {brand:Ext.getCmp('serialise_brand').getValue(), querystr:field.getValue(), catcode:Ext.getCmp('category').getValue()}
									ItemListingStore.load();								
								}								
							}					
						}]
					}],
					bbar : {
						xtype : 'pagingtoolbar',
						store : ItemListingStore,
						pageSize : itemsPerPage,
						displayInfo : true
					}
				}]
		}],buttons:[{
			text:'Add Item',
			disabled: false,
			id:'btnAddItem',			
			handler: function(grid, rowIndex, colIndex) {	
				var grid = Ext.getCmp('ItemSerialListing');
				var selected = grid.getSelectionModel().getSelection();
				for (i = 0; i < selected.length; i++) {
					var record = selected[i];
						
					Ext.toast({
						icon   	: '../js/ext4/examples/shared/icons/accept.png',
						html: '<b>' + 'Model:' + record.get('model') + ' <br><br/>',
						title: 'Selected Item',
						width: 250,
						bodyPadding: 10,
						align: 'tr'
					});	
				}

				var grid = Ext.getCmp('ItemSerialListing');
				var selected = grid.getSelectionModel().getSelection();
				var gridRepoData = [];
				count = 0;
				Ext.each(selected, function(record) {
					var ObjItem = {						
						model: record.get('model'),	
						item_code: record.get('item_code'),	
						sdescription: record.get('stock_description'),	
						color: record.get('item_description'),	
						category: record.get('category_id'),	
						qty: record.get('qty'),						
						AdjDate: Ext.getCmp('AdjDate').getValue(),							
						serialised: record.get('serialised'),
						brand_id: record.get('brand_id')						
					};
					gridRepoData.push(ObjItem);
				});

				if (gridRepoData == "") {
					Ext.MessageBox.alert('Error','Please Select Item..');
					return false;
				}else{
					StockRequestStore.proxy.extraParams = {DataOnGrid: Ext.encode(gridRepoData)};
				}

				StockRequestStore.load({
					scope: this,
					callback: function(records, operation, success){
						var countrec = StockRequestStore.getCount();
						if(countrec>0){
							setButtonDisabled(false);
						}else{
							setButtonDisabled(true);
						}								
					}	
				});											
				ItemListingStore.load();
			}
		},{
			text:'Close',
			iconCls:'cancel-col',
			handler: function(bt){		
				bt.up('window').close();									
			}
		}]
	});

	var requeststockdelivery = Ext.create('Ext.grid.Panel', {
		renderTo: 'requeststock-grid',
		title	: 'Request Stock Listing',
		layout: 'fit',
		store	    : RequeststockviewStore,
		id 		    : 'requeststockdelivery',
		columns 	: RequestHeader,
		forceFit 	: true,
		frame		: false,
		columnLines	: true,
		sortableColumns :true,
		dockedItems: [{
            dock	: 'top',
            xtype	: 'toolbar',
			name 	: 'search',
            items: [{
				icon   	: '../js/ext4/examples/shared/icons/fam/add.gif',
				tooltip	: 'New Stock Request',
				text 	: 'New Stock Request',
				hidden: false,
				scale	: 'small',
				handler : function(){			
					Ext.Ajax.request({
						url : '?action=NewTransfer',
						method: 'POST',
						success: function(response){
							var jsonData = Ext.JSON.decode(response.responseText);
							var AdjDate = jsonData.AdjDate;
							var branchcode = jsonData.branchcode;
							Ext.getCmp('AdjDate').setValue(AdjDate);
							Ext.getCmp('fromlocation').setValue(branchcode);	
							
							StockRequestStore.proxy.extraParams = {action: 'AddItem'}
							StockRequestStore.load({
								scope: this,
								callback: function(records, operation, success){
									var countrec = StockRequestStore.getCount();
									if(countrec>0){
										setButtonDisabled(false);
									}else{
										setButtonDisabled(true);
										
									}
								}
							});
						}
					});
					var win = Ext.create('windowNewTransfer');
					win.show();
				    GetUserLogin();	
				}
			}]
		}],
		bbar : {
			xtype : 'pagingtoolbar',
			pageSize : itemsPerPage,
			store : RequeststockviewStore,
			displayInfo : true
		}
	});

	function setButtonDisabled(valpass=false){
		Ext.getCmp('btnProcess').setDisabled(valpass);
	}
	function GetUserLogin(){
		Ext.Ajax.request({
			url : '?get_userLogin=robert',
			async:false,
			success: function (response){	
				var result = Ext.JSON.decode(response.responseText);
				Ext.getCmp('servedby').setValue(result.servedby);
			}
		});
	};
});
