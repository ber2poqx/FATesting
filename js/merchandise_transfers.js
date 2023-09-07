var newURL = window.location.protocol + "//" + window.location.host + window.location.pathname;

Ext.Loader.setConfig({enabled: true});
Ext.Loader.setPath('Ext.ux', '../js/ext4/examples/ux/');
Ext.require(['Ext.toolbar.Paging',
'Ext.ux.form.SearchField',
'Ext.layout.container.Column',
'Ext.tab.*',
'Ext.window.MessageBox',
'Ext.selection.CheckboxModel',
'Ext.grid.*',
'Ext.selection.CellModel',
'Ext.form.*']);


Ext.onReady(function(){
	Ext.QuickTips.init();
	var itemsPerPage = 20;   // set the number of items you want per page on grid.
	var all = false;
	var global_master_id;
    const queryString = window.location.search;
	//console.log(queryString);
	const urlParams = new URLSearchParams(queryString);
	var BrCode = urlParams.get('BRCODE')
	var totalitem=0;
	
	/*------Added by Robert 02/22/2022-----*/
	/*var selCheckModel = Ext.create('Ext.selection.CheckboxModel', {
		checkOnly: true,
		mode: 'Single'
	});
	/*------------------------------------*/

	var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
		clicksToEdit: 1
	});

 	Ext.define('insurance', {
		extend : 'Ext.data.Model',
		fields  : [
			{name:'trans_id',mapping:'trans_id'},
			{name:'reference',mapping:'reference'},
			{name:'tran_date',mapping:'tran_date'},
			{name:'loc_code',mapping:'loc_code'},
			{name:'loc_name',mapping:'loc_name'},
			{name:'category',mapping:'category'},
			{name:'category_id',mapping:'category_id'},
			{name:'remarks',mapping:'remarks'},
			{name:'qty',mapping:'qty'},
			{name:'statusmsg',mapping:'status'},
			{name:'serialise_total_qty',mapping:'serialise_total_qty'},
			{name:'delivery_date',mapping:'delivery_date'}
		]
	});

	Ext.define('mymerchandise',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'stock_id', mapping:'stock_id'},
			{name:'stock_description', mapping:'stock_description'},
			{name:'trans_date', mapping:'trans_date'},
			{name:'price', mapping:'price'},
			{name:'reference', mapping:'reference'},
			{name:'currentqty', mapping:'currentqty'},
			{name:'qty', mapping:'qty', type: 'float'},
			{name:'standard_cost', mapping:'standard_cost', type: 'float'},
			{name:'lot_no', mapping:'lot_no'},
			{name:'chasis_no', mapping:'chasis_no'},
			{name:'category_id', mapping:'category_id'},
			{name:'serialise_id', mapping:'serialise_id'},
			{name:'color', mapping:'color'},
			{name:'type_out', mapping:'type_out'},
			{name:'transno_out', mapping:'transno_out'},
			{name:'line_item', mapping:'line_item'},
			{name:'rr_date', mapping:'rr_date'},
			{name:'subtotal_cost', mapping:'subtotal_cost', type: 'float'}
		]
	});

	Ext.define('mymtitemlist',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'serialise_id', mapping:'serialise_id'},
			{name:'model', mapping:'model'},
			{name:'lot_no', mapping:'lot_no'},
			{name:'chasis_no', mapping:'chasis_no'},
			{name:'color', mapping:'color'},
			{name:'item_description', mapping:'item_description'},
			{name:'stock_description', mapping:'stock_description'},
			{name:'qty', mapping:'qty', type: 'float'},
			{name:'category_id', mapping:'category_id'},
			{name:'reference', mapping:'reference'},
			{name:'status_msg', mapping:'status_msg'},
			{name:'unit_cost', mapping:'unit_cost', type: 'float'},
			{name:'total_cost', mapping:'total_cost', type: 'float'}
		]
	});

	function Status(val) {
		if(val == '0'){
			return '<span style="color:black;font-weight: bold">In-transit</span>';
		}else if(val == '1'){
            return '<span style="color:blue;font-weight: bold;">Partial</span>';
        }else if(val == '2'){
            return '<span style="color:green;font-weight: bold;">Received</span>';
        }
        return val;
    }

	var columnModel =[
		{header:'ID', dataIndex:'trans_id', sortable:true, width:20, hidden: true},
		{header:'Reference', dataIndex:'reference', sortable:true, width:75,
			renderer: function(value, metaData, record, rowIndex, colIndex, store) {
				return '<span style="color:blue; font-weight:bold;">' + value + '</span>';
			}	
		},
		{header:'Trans Date', dataIndex:'tran_date', sortable:true, width:40,
			renderer: function(value, metaData, record, rowIndex, colIndex, store) {
				return '<span style="color:black; font-weight:bold;">' + value + '</span>';
			}	
		},
		{header:'To Location', dataIndex:'loc_name', sortable:true, width:90,
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
		{header:'Remarks', dataIndex:'remarks', sortable:true, width:150,
			renderer: function(value, metaData, record, rowIndex, colIndex, store) {
				return '<span style="color:black; font-weight:bold;">' + value + '</span>';
			}
		},
		{header:'Status', dataIndex:'statusmsg', sortable:true, width:50,
			renderer: function(value, metaData, record, rowIndex, colIndex, store) {
				if (value == "Received"){
					return '<span style="color:green; font-weight:bold;">' + value + '</span>';
				}else if(value == "Partial"){
					return '<span style="color:blue; font-weight:bold;">' + value + '</span>';
				}else{
					return '<span style="color:black; font-weight:bold;">' + value + '</span>';
				}
			}
		},
		{header	: 'Action',	xtype:'actioncolumn', align:'center', width:40,
			items:[
				{
					icon: '../js/ext4/examples/shared/icons/application_view_columns.png',
					tooltip: 'Serial Items Detail',
					handler: function(grid, rowIndex, colIndex) {
						var record = myInsurance.getAt(rowIndex);
						id = record.get('trans_id');
                        reference = record.get('reference');
                        brcode = record.get('loc_code');
                        catcode = record.get('category_id');


						if(!windowItemSerialList){
							MTItemListingStore.proxy.extraParams = {catcode: catcode, branchcode:brcode,reference:reference,trans_id:id}
							MTItemListingStore.load();

							var windowItemSerialList = Ext.create('Ext.Window',{
								title:'Item Listing',
								id:'windowItemSSerialList',
								modal: true,
								width: 900,
								height:400,
								bodyPadding: 5,
								layout:'fit',
								items:[
									{
										xtype:'panel',
										autoScroll: true,
										frame:false,
										items:[{
											xtype:'grid',
											forceFit: true,
											//flex:1,
											layout:'fit',
											id:'ItemSerialListingView',
											store: MTItemListingStore,
											columns: columnItemSerialView,
											features: [{
												ftype: 'summary'
											}],
											/*dockedItems:[{
												dock:'top',
												xtype:'toolbar',
												name:'searchSerialBar',
												hidden: true,
												items:[{
													width	: 300,
													hidden: true,
													xtype	: 'textfield',
													name 	: 'searchSerialItem',
													id		:'searchSerialItemView',
													fieldLabel: 'Item Description',
													labelWidth: 120,
													listeners : {
														specialkey: function(f,e){							
															if (e.getKey() == e.ENTER) {
																
	
																var catcode = Ext.getCmp('category').getValue();
																var brcode = Ext.getCmp('fromlocation').getValue();
																MTItemListingStore.proxy.extraParams = { 
																	query:this.getValue(), 
																	catcode: catcode,
																	branchcode: brcode
																}
																MTItemListingStore.load();									
															}
														}						
													}
												},{
													iconCls:'clear-search',
													hidden: true
												},{
													xtype:'textfield',
													name:'searchSerial',
													id:'searchSerialView',
													fieldLabel:'Serial/Engine No.',
													labelWidth: 120,
													hidden: true
												}]
											}],
											bbar : {
												xtype : 'pagingtoolbar',
												store : MTItemListingStore,
												displayInfo : true
											}*/
										}]
									}
								],
								buttons:[
									{
										text:'Close',
										iconCls:'cancel-col',
										handler: function(){
											windowItemSerialList.close();
										}
									}
								]
							});	
						}						
						//var v = Ext.getCmp('category').getValue();
						if(catcode=='14'){
							Ext.ComponentQuery.query('#ItemSerialListingView gridcolumn[dataIndex^="chasis_no"]')[0].show();
							Ext.ComponentQuery.query('#ItemSerialListingView gridcolumn[dataIndex^="lot_no"]')[0].setText('Engine No.');
							Ext.ComponentQuery.query('#ItemSerialListingView gridcolumn[dataIndex^="item_description"]')[0].show();
						}else{
							Ext.ComponentQuery.query('#ItemSerialListingView gridcolumn[dataIndex^="lot_no"]')[0].setText('Serial No.');
							Ext.ComponentQuery.query('#ItemSerialListingView gridcolumn[dataIndex^="chasis_no"]')[0].hide();
							Ext.ComponentQuery.query('#ItemSerialListingView gridcolumn[dataIndex^="item_description"]')[0].hide();
						}
						windowItemSerialList.show();
						
					}
				},' ',{
					icon: '../js/ext4/examples/shared/icons/printer.png',
					tooltip: 'View Merchandise Transfer Report',
					handler: function(grid, rowIndex, colIndex) {
						var record = myInsurance.getAt(rowIndex);
						reference = record.get('reference');
						var win = new Ext.Window({
							autoLoad:{
								url:'../reports/merchandise_receipts.php?reference='+reference,
								discardUrl: true,
								nocache: true,
								text:"Loading...",
								timeout:60,
								scripts: false
							},
							width:'70%',
							height:'70%',
							title:'Preview Print',
							modal: true
						})
						
						var iframeid = win.getId() + '_iframe';


				        var iframe = {
				            id:iframeid,
				            tag:'iframe',
				            src:'../reports/merchandise_receipts.php?reference='+reference,
				            width:'100%',
				            height:'100%',
				            frameborder:0
				        }
						win.show();
						Ext.DomHelper.insertFirst(win.body, iframe)
						//window.open('../reports/merchandise_receipts.php?reference='+reference);
					}
				}
			]
		}
	];
	
	var myInsurance = Ext.create('Ext.data.Store', {
		model : 'insurance',
		name : 'myInsurance',
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
		}/*,
		simpleSortMode : true,
		sorters : [{property : 'id',direction : 'DESC'}]*/,
		autoLoad: true
	});

	var Supplier_Filter = Ext.create('Ext.form.ComboBox', {
    	xtype:'combo',
    	hidden: true,
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
	
	var MerchandiseTransStore = Ext.create('Ext.data.Store', {
	    storeId:'DetaiItemsTransferListStore',
		model: mymerchandise,
		name : 'MerchandiseTransStore',
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
	
	var columnTransferModel = [
		{xtype: 'rownumberer'},
		{header:'#', dataIndex:'id', sortable:true, width:50, align:'center', hidden: true},
		{header:'Type', dataIndex:'type_out', sortable:true, width:40, renderer: columnWrap,hidden: true},
		{header:'Trans No', dataIndex:'transno_out', sortable:true, width:40, renderer: columnWrap,hidden: true},
		{header:'RR Date', dataIndex:'rr_date', sortable:true, width:60, hidden: true},
		{header:'Model', dataIndex:'stock_id', sortable:true, renderer: columnWrap,hidden: false},
		{header:'Description', dataIndex:'stock_description', sortable:true, width:150, renderer: columnWrap,hidden: false},
		{header:'Color', dataIndex:'color', sortable:true, width:150, renderer: columnWrap,hidden: false},
		{header:'Category', dataIndex:'category_id', sortable:true, width:100, renderer: columnWrap,hidden: true},
		{header:'Location', dataIndex:'loc_code', sortable:true,width:100, hidden: true},
		{header:'Unit Cost', dataIndex:'standard_cost', sortable:true, hidden: false,
			renderer: function(value, metadata, record, rowIndex, colIndex, store) {
				if(value == 0){
					return '<span style="color:red; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:green; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') + '</span>'
				}
			}	
		},
        {header:'Current Qty', dataIndex:'currentqty', sortable:false, width:40, hidden: true, align:'center'},
		{header:'Qty', dataIndex:'qty', sortable:true, hidden: false, align:'center',
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
						},
						change: function(editor, e) {
							var ItemModel = Ext.getCmp('mtgrid').getSelectionModel();
							var GridRecords = ItemModel.getLastSelected();																																		 
							var newcost = (e * GridRecords.get('standard_cost'));
							GridRecords.set("subtotal_cost",(newcost));
						}
					}	
				}
			}	
			/*editor:{
				completeOnEnter: true,
				field:{
					xtype:'textfield',
					allowBlank: false,
					minValue:0,
					listeners: {
						afterrender: function(field) {
							field.focus(true);
						},
						change: function(editor, e) {
							var ItemModel = Ext.getCmp('mtgrid').getSelectionModel();
							var GridRecords = ItemModel.getLastSelected();																																		 
							var newcost = (e * GridRecords.get('standard_cost'));
							GridRecords.set("subtotal_cost",(newcost));
						}
					}	
				}
			}*/
		},
		{header:'Total', dataIndex:'subtotal_cost', sortable:true, hidden: false,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';	
			},
			summaryType: 'sum',
			summaryRenderer: function(value, summaryData, dataIndex){
				return '<span style="color:blue;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';									
			}	
		},
		{header:'Engine No.', dataIndex:'lot_no', sortable:true, width:150,renderer: columnWrap, hidden: false},
		{header:'Chasis No.', dataIndex:'chasis_no', sortable:true, width:150,renderer: columnWrap, hidden: false},
		{header:'Action',xtype:'actioncolumn', align:'center',
			items:[
				{
					icon:'../js/ext4/examples/shared/icons/cancel.png',
					tooltip:'Delete',
					handler: function(grid, rowIndex, colIndex){
						var record = MerchandiseTransStore.getAt(rowIndex);
						var id = record.get('id');
						var line_item = record.get('line_item');
						var serialise_id = record.get('serialise_id');
						var model = record.get('model');	
						var sdescription = record.get('stock_description');	
						var color = record.get('color');	
						var category = record.get('category_id');	
						var qty = record.get('qty');	
						var lot_no = record.get('lot_no');	
						var chasis_no = record.get('chasis_no');	
						var AdjDate = Ext.getCmp('AdjDate').getValue();	
						
						//MerchandiseTransStore.proxy.extraParams = {}											
						Ext.Ajax.request({
							url : '?action=RemoveItem',
							method: 'GET',
							params:{
								id:id, serialise_id: serialise_id, AdjDate:AdjDate, model:model, line_item: line_item
							},
							success: function (response){
								MerchandiseTransStore.load({params: { 
									view: 1
								}});											
							}
						});
					}
				}
			]
		}
	]
	
	function columnWrap(val){
		return '<div style="white-space:normal !important;">'+ val +'</div>';
	}

	var MTItemListingStore = Ext.create('Ext.data.Store', {
		model: mymtitemlist,
		name: 'MTItemListingStore',
		autoLoad: false,
		proxy : {
			type: 'ajax',
			url	: '?action=MTserialitems',
			reader:{
				type : 'json',
				root : 'result',
				totalProperty : 'total'
			}
		}
	});
	
	var ItemListingStore = Ext.create('Ext.data.Store', {
		fields: ['serialise_id', 'model', 'lot_no', 'chasis_no', 'standard_cost','item_code', 'item_description', 'stock_description', 'qty','category_id','type_out','transno_out','tran_date','reference','serialised', 'subtotal_cost'],
		autoLoad: false,
		pageSize: itemsPerPage, // items per page
		proxy : {
			type: 'ajax',
			url	: '?action=serial_items',
			reader:{
				type : 'json',
				root : 'result',
				totalProperty : 'total'
			}
		}
	});

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

	/*var columnBranches = [
		{header:'id', dataIndex:'branch_id', sortable:true, width:60,hidden: true},
		{header:'Branch Code', dataIndex:'loc_code', sortable:true, width:45, renderer: columnWrap,hidden: false},
		{header:'Branch Name', dataIndex:'location_name', sortable:true, width:200, renderer: columnWrap,hidden: false}
	]*/
	
	var columnItemSerial = [
		{header:'id', dataIndex:'serialise_id', sortable:true, width:60,hidden: true},
		{header:'Serialise', dataIndex:'serialised', sortable:true, width:30, renderer: columnWrap,hidden: true},
		{header:'Type', dataIndex:'type_out', sortable:true, width:30, renderer: columnWrap,hidden: true},
		{header:'Transno', dataIndex:'transno_out', sortable:true, width:30, renderer: columnWrap,hidden: true},
		{header:'Reference', dataIndex:'reference', sortable:true, width:80, hidden: false},
		{header:'RR Date', dataIndex:'tran_date', sortable:true, width:60, hidden: false},
		{header:'Model', dataIndex:'model', sortable:true, width:60, renderer: columnWrap,hidden: false},
		{header:'Item Description', dataIndex:'stock_description', sortable:true, width:80, renderer: columnWrap,hidden: false},
		{header:'Color', dataIndex:'item_description', sortable:true, width:70, renderer: columnWrap,hidden: false},
		{header:'Category', dataIndex:'category_id', sortable:true, width:100,hidden: true},
		{header:'Cost', dataIndex:'standard_cost', sortable:true, width:70, hidden: false, align:'right'},
		{header:'Qty', dataIndex:'qty', sortable:true, width:40, hidden: false, align:'center'},
		{header:'Engine No.', dataIndex:'lot_no', sortable:true, width:100,renderer: columnWrap, hidden: false},
		{header:'Chasis No.', dataIndex:'chasis_no', sortable:true, width:100,renderer: columnWrap, hidden: false}
	]
/*params:{serialise_id: serialise_id, AdjDate:AdjDate, model:model, sdescription:sdescription, color:color, category:category, qty:qty, lot_no:lot_no, chasis_no:chasis_no, type_out:type_out, transno_out:transno_out, standard_cost:standard_cost,serialised:serialised,rr_date:rr_date},*/
	var columnItemSerialView = [
		{header:'id', dataIndex:'serialise_id', sortable:true, width:60,hidden: true},
		{header:'Model', dataIndex:'model', sortable:true, width:70, renderer: columnWrap,hidden: false},
		{header:'Item Description', dataIndex:'stock_description', sortable:true, renderer: columnWrap,hidden: false},
		{header:'Color', dataIndex:'item_description', sortable:true, renderer: columnWrap,hidden: false},
		{header:'Category', dataIndex:'category_id', sortable:true, width:100, renderer: columnWrap,hidden: true},
		{header:'Unit Cost', dataIndex:'unit_cost', sortable:true, width:70, hidden: false,
			renderer: function(value, metadata, record, rowIndex, colIndex, store) {
				if(value == 0){
					return '<span style="color:red; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:green; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') + '</span>'
				}
			}	
		},
		{header:'Qty', dataIndex:'qty', sortable:true, width:50, hidden: false, align:'center',
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
						},
						change: function(editor, e) {
							var ItemModel = Ext.getCmp('ItemSerialListingView').getSelectionModel();
							var GridRecords = ItemModel.getLastSelected();																																		 
							var newcost = (e * GridRecords.get('unit_cost'));
							GridRecords.set("total_cost",(newcost));
						}
					}	
				}
			}		
		},
		{header:'Total', dataIndex:'total_cost', sortable:true, width:80, hidden: false,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';	
			},
			summaryType: 'sum',
			summaryRenderer: function(value, summaryData, dataIndex){
				return '<span style="color:blue;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';									
			}
		},
		{header:'Engine No.', dataIndex:'lot_no', sortable:true, width:100,renderer: columnWrap, hidden: false},
		{header:'Chasis No.', dataIndex:'chasis_no', sortable:true, width:100,renderer: columnWrap, hidden: false},
		{header:'Status', dataIndex:'status_msg', sortable:true, width:70,renderer: columnWrap, hidden: false, renderer: Status},
		{header:'Action',xtype:'actioncolumn', align:'center', width:40, hidden: true}
	]

	/*Ext.define('windowItemListBranch', {
		extend: 'Ext.window.Window',
		title:'Branches Listing',
		id:'windowItemListBranch',
		modal: true,
		width: 700,
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
				id:'branchlisting',
				store: BranchListingStore,
				columns: columnBranches,
				selModel: {
					selType: 'checkboxmodel',
					id: 'checkidboxbranch',
					checkOnly: true,
					mode: 'Single'			
				},				
				dockedItems:[{
					dock:'top',
					xtype:'toolbar',
					name:'searchSerialBar',
					items:[{
						width		: 300,
						xtype		: 'textfield',
						name 		: 'searchBranchList',
						id			:'searchBranchList',
						fieldLabel	: 'Branch',
						labelWidth	: 120,
						listeners 	: {													
							change: function(field) {
								var class_type = Ext.getCmp('searchBranchList').getValue();
								BranchListingStore.proxy.extraParams = { 											 
									query:field.getValue()
								}
								BranchListingStore.load();								
							}								
						}		
					}]
				}],
				bbar : {
					xtype : 'pagingtoolbar',
					store : BranchListingStore,
					pageSize : itemsPerPage,
					displayInfo : true
				}
			}]
		}],
		
		buttons:[{
			text:'Add Branch',
			disabled: false,
			id:'btnAddBranch',			
			handler: function(grid, rowIndex, colIndex) {
				var grid = Ext.getCmp('branchlisting');
				var selected = grid.getSelectionModel().getSelection();
				for (i = 0; i < selected.length; i++) {
					var record = selected[i];
					Ext.getCmp('ToStockLocation').setValue(record.get('loc_code'));
	
					Ext.toast({
						icon   	: '../js/ext4/examples/shared/icons/accept.png',
						html: '<b>' + 'Branch Name:' + record.get('location_name') + '<b/>',
						title: 'Selected Branch',
						width: 250,
						bodyPadding: 10,
						align: 'tr'
					});	
					BranchListingStore.load();					
					this.up('window').close();
				}
			}						
		},{
			text:'Close',
			iconCls:'cancel-col',
			handler: function(){				
				this.up('window').close();
			}
		}]
	});*/

	var gridMT = {
		xtype:'grid',
		id:'mtgrid',
        loadMask:true,
		anchor:'100%',
		//forceFit: true,
		store: MerchandiseTransStore,
		columns: columnTransferModel,
		columnLines: true,
		height: 270,
		width: 700,
		autoScroll:true,
		layout:'fit',
		//frame: true,
		//selModel: {selType: 'cellmodel'},
		//plugins: [cellEditing],
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
		viewConfig:{
			stripeRows: true,
			getRowClass: function (record, rowIndex) {
		      	var pfix = Ext.baseCSSPrefix;
			  	var disabledClass =  pfix + 'item-disabled ' + pfix + 'btn-disabled ' + pfix + 'btn-plain-toolbar-small';
				var AdjDate = Ext.getCmp('AdjDate').getValue();
				AdjDate = Ext.util.Format.date(AdjDate,'Y-m-d');
				var resultdis = record.get('rr_date') > AdjDate ? disabledClass : '';
				//console.log(resultdis);
				return resultdis;
		    }
		},
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
					}	
					if(!windowItemList){
						var catcode = Ext.getCmp('category').getValue();
						var brcode = Ext.getCmp('fromlocation').getValue();
						var AdjDate = Ext.getCmp('AdjDate').getValue();
						
						ItemListingStore.proxy.extraParams = {catcode: catcode, branchcode:brcode, trans_date:AdjDate}
						ItemListingStore.load();
						
						var windowItemList = Ext.create('Ext.Window',{
							title:'Item Listing',
							id:'windowItemList',
							modal: true,
							width: 1000,
							height:470,
							bodyPadding: 3,
							layout:'fit',
							items:[
								{
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
										//selModel: selCheckModel,
										//selType : 'checkboxmodel',
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
												width		: 300,
												xtype		: 'textfield',
												name 		: 'searchSerialItem',
												id			:'searchSerialItem',
												fieldLabel	: 'Item Description',
												labelWidth	: 120,
												listeners 	: {													
													change: function(field) {
														var class_type = Ext.getCmp('searchSerialItem').getValue();
														ItemListingStore.proxy.extraParams = { 											 
															query:field.getValue(), serialquery:Ext.getCmp('searchSerial').getValue(), catcode:Ext.getCmp('category').getValue()
														}
														ItemListingStore.load();								
													}								
												}
											},{
												xtype:'textfield',
												name:'searchSerial',
												id:'searchSerial',
												fieldLabel:'Serial/Engine No.',
												labelWidth: 120,
												listeners : {											
													change: function(field) {
															
														var class_type = Ext.getCmp('searchSerial').getValue();
														ItemListingStore.proxy.extraParams = { 											 
															query:Ext.getCmp('searchSerialItem').getValue(), serialquery:field.getValue(), catcode:Ext.getCmp('category').getValue()													
														}
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
								}
							],

							buttons:[{
										/*------Robert Added-02/25/2022------*/
										text:'Add Item',
										disabled: false,
										id:'btnAddItem',			
										handler: function(grid, rowIndex, colIndex) {	
										    var grid = Ext.getCmp('ItemSerialListing');
											var selected = grid.getSelectionModel().getSelection();
											for (i = 0; i < selected.length; i++) {
												var record = selected[i];
											   
											    var serialise_id = record.get('serialise_id');	
												var model = record.get('model');	
												var item_code = record.get('item_code');	
												var sdescription = record.get('stock_description');	
												var color = record.get('item_description');	
												var category = record.get('category_id');	
												var qty = record.get('qty');	
												var lot_no = record.get('lot_no');	
												var chasis_no = record.get('chasis_no');	
												var AdjDate = Ext.getCmp('AdjDate').getValue();	
												var type_out = record.get('type_out');	
												var transno_out = record.get('transno_out');	
												var standard_cost = record.get('standard_cost');	
												var serialised = record.get('serialised');	
												var rr_date = record.get('tran_date');
								
												Ext.toast({
													icon   	: '../js/ext4/examples/shared/icons/accept.png',
												    html: '<b>' + 'Model:' + record.get('model') + ' <br><br/> ' + 'Serial #:' + record.get('lot_no') + '<b/>',
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
													serialise_id: record.get('serialise_id'),	
													model: record.get('model'),	
													item_code: record.get('item_code'),	
												    sdescription: record.get('stock_description'),	
													color: record.get('item_description'),	
													category: record.get('category_id'),	
													qty: record.get('qty'),	
													lot_no: record.get('lot_no'),	
													chasis_no: record.get('chasis_no'),	
													AdjDate: Ext.getCmp('AdjDate').getValue(),	
													type_out: record.get('type_out'),	
													transno_out: record.get('transno_out'),
													standard_cost: record.get('standard_cost'),	
													serialised: record.get('serialised'),	
													rr_date: record.get('tran_date'), 
													subtotal_cost: record.get('subtotal_cost')
												};
												gridRepoData.push(ObjItem);
											});

											if (gridRepoData == "") {
												Ext.MessageBox.alert('Error','Please Select Item..');
												return false;
											}else{
												MerchandiseTransStore.proxy.extraParams = {DataOnGrid: Ext.encode(gridRepoData)};
											}

											MerchandiseTransStore.load({
												scope: this,
												callback: function(records, operation, success){
													var countrec = MerchandiseTransStore.getCount();
													if(countrec>0){
														setButtonDisabled(false);
													}else{
														setButtonDisabled(true);
													}								
												}	

											});											
											ItemListingStore.load();
										}
										/*---------End Here---------*/			
									},{
										text:'Close',
										iconCls:'cancel-col',
										handler: function(){
										windowItemList.close();
									}
								}
							]
						});
					}						
					
					var v = Ext.getCmp('category').getValue();
					if(v=='14'){
						Ext.ComponentQuery.query('#ItemSerialListing gridcolumn[dataIndex^="chasis_no"]')[0].show();
						Ext.ComponentQuery.query('#ItemSerialListing gridcolumn[dataIndex^="lot_no"]')[0].setText('Engine No.');
						Ext.ComponentQuery.query('#ItemSerialListing gridcolumn[dataIndex^="item_description"]')[0].show();
					}else{
						Ext.ComponentQuery.query('#ItemSerialListing gridcolumn[dataIndex^="lot_no"]')[0].setText('Serial No.');
						Ext.ComponentQuery.query('#ItemSerialListing gridcolumn[dataIndex^="chasis_no"]')[0].hide();
						Ext.ComponentQuery.query('#ItemSerialListing gridcolumn[dataIndex^="item_description"]')[0].hide();
					}
					windowItemList.show();
				}	
			}]
		}],
        /*listeners:{
            validateedit: function(editor, e){
				setButtonDisabled(false);

                var catcode = Ext.getCmp('category').getValue();
				var brcode = Ext.getCmp('fromlocation').getValue();

                var record = MerchandiseTransStore.getAt(e.record.id);
			    var id = record.get('id');
                var currentqty = record.get('currentqty');
                var qty = record.get('qty');
                var stock_id = record.get('stock_id');
                var serial_no = record.get('lot_no');

                if(currentqty < e.value){
                    alert('Sorry, Quantity '+e.value+' is Greater than Available Quantity On Hand: '+currentqty);
					setButtonDisabled(true);

                    return false;
                }else return true;
                //return true;
            }
        }*/
	}

	Ext.create('Ext.grid.Panel', {
		renderTo: 'merchandisetransfer-grid',
		layout: 'fit',
		title	: 'Merchandise Transfers Listing [Out-going] - ',
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
					xtype:'textfield',
					fieldLabel:'From Location',
					id:'fromlocation',
					hidden: true
				},{
					width	: 200,
					xtype	: 'searchfield',
					store	: myInsurance,
					name 	: 'search',
					fieldLabel: 'Search',
					labelWidth: 50,
					hidden: true
				},Supplier_Filter,{
					icon   	: '../js/ext4/examples/shared/icons/fam/add.gif',
					tooltip	: 'New Item Transfer',
					text 	: 'New Item Transfer',
					hidden: false,
					handler : function(){			
                        if(!windowNewTransfer){
							var windowNewTransfer = Ext.create('Ext.Window',{
								title:'Merchandise Transfer Entry',
								modal: true,
								width: 980,
								bodyPadding: 5,
								layout:'anchor',
								items:[
									{
										xtype:'fieldset',
										//title:'Merchandise Transfer Header',
										layout:'anchor',
										defaultType:'textfield',
										fieldDefaults:{labelAlign:'right'},
										items:[
											{
												xtype:'fieldcontainer',
												layout:'hbox',
												margin: '2 0 2 5',
												items:[{
													xtype:'combobox',
													fieldLabel:'Transfer To',
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
													width:785,
													hiddenName: 'loc_code',
													typeAhead: true,
													readOnly: false,
													anyMatch: true,
													emptyText:'Select Transder Location',
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
												}/*,{		
													xtype:'fieldcontainer',
													layout:'hbox',
													margin: '0 0 2 5',
													items:[{												
														xtype	: 'button',																									
														name 	: 'newsearchbranch',														
														icon   	: '../js/ext4/examples/shared/icons/application_form_add.png',
														tooltip	: 'Select Branches',
														text 	: 'Select Branch',
														handler: function(){												
															var win = Ext.create('windowItemListBranch');
															win.show();
															BranchListingStore.load();
														}
													}]
												}*/]
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
														listeners:{
															select: function(cmb, rec, idx){
																var v = this.getValue();
																//var mtgridcol = Ext.getCmp('mtgrid');
																if(v=='14'){
																	
																	Ext.ComponentQuery.query('grid gridcolumn[dataIndex^="chasis_no"]')[0].show();
																	Ext.ComponentQuery.query('grid gridcolumn[dataIndex^="lot_no"]')[0].setText('Engine No.');
																	//Ext.ComponentQuery.query('grid gridcolumn[dataIndex^="color"]')[0].show();
																	
																}else{
																	Ext.ComponentQuery.query('grid gridcolumn[dataIndex^="lot_no"]')[0].setText('Serial No.');
																	Ext.ComponentQuery.query('grid gridcolumn[dataIndex^="chasis_no"]')[0].hide();
																	//Ext.ComponentQuery.query('grid gridcolumn[dataIndex^="color"]')[0].hide();
																}
																//Ext.Msg.alert('Category', v);
																
															}
														}
													},{					
														xtype:'datefield',
														fieldLabel:'Trans Date',
														name:'trans_date',
														id:'AdjDate',
														width: 232,
														labelWidth: 80,
                    									fieldStyle : 'background-color: #F2F3F4; color:black; font-weight:bold;',
														listeners:{
															change: function(){
															MerchandiseTransStore.load({
																params:{view:1},
																scope: this,
																callback: function(records, operation, success){
																	var countrec = MerchandiseTransStore.getCount();
																	if(countrec>0){
																		setButtonDisabled(false);
																	}else{
																		setButtonDisabled(true);
																		
																	}
																}
															});
														}
													}
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
													fieldLabel:'Served By',
													labelWidth:80,
													width: 278,
                    								fieldStyle : 'background-color: #F2F3F4; color:black; font-weight:bold;',
													readOnly: true
												}]
											},{
												xtype:'fieldcontainer',
												width:785,
												layout:'hbox',
												margin: '2 0 2 5',
												layout:'fit',
												items:[{
													xtype:'textareafield',
													fieldLabel:'Memo',
													name:'memo',
													id:'memo',
													grow: true,
													anchor:'100%'
												}]
											}
										]	
									},
									{
										xtype:'panel',
										title:'Items',
										frame: true,
										anchor:'100%',
										layout:'fit',
										border: false,
										items:[gridMT]
									},{
										xtype:'fieldcontainer',
										layout:'center',
										margin: '2 0 1 0',
										items:[
											{
												xtype:'textfield',
												fieldLabel:'TOTAL COST:',
												readOnly: true,
												hidden: true,
												width: 210,
												labelWidth: 85,
												fieldStyle: 'font-weight: bold; color: #003168;text-align: right;',
												id:'totalcost'
											}
										]
									}
								],buttons:[{
										text:'Process Transfers',
										disabled: true,
										id:'btnProcess',
										handler:function(){
											Ext.MessageBox.confirm('Confirm', 'Are you sure you want to Process this transaction?', ApprovalFunction);
						                    function ApprovalFunction(btn) {
						                    	if(btn == 'yes') {
						                    		setButtonDisabled(true);
											        var gridData = MerchandiseTransStore.getRange();
													var gridRepoData = [];
													//var HaveErrors = 0;
													//var $message;

													count = 0;

													/*Ext.each(gridData, function(item) {
														if(item.get('qty') > item.get('currentqty')){
															//alert("Sorry,");
															//Ext.Msg.alert("Error","Agoy - Sorry, Quantity you entered");
															//return false;
															 $message = "Agoy - Sorry, Quantity you entered";
															 $HaveErrors = 1;
														}
														/*if(item.get('qty') > item.get('currentqty')){
															$message = "'Error','Agoy - Sorry, Quantity you entered' + item.get('qty') + "is Greater than Available Quantity On Hand:" + item.get('currentqty');
															HaveErrors = 1;
														}*/
														/*if($HaveErrors == 1){
															Ext.Msg.show({
																title: 'Error!',
																msg: '<font color="red">' + $message + '</font>',
																buttons: Ext.Msg.OK,
																icon: Ext.MessageBox.ERROR
															});
														}
													});*/
				
													Ext.each(gridData, function(item) {
														var ObjItem = {							
															qty: item.get('qty'),
															currentqty:item.get('currentqty'),
															standard_cost:item.get('standard_cost')													
														};
														gridRepoData.push(ObjItem);
													});
													
													var AdjDate = Ext.getCmp('AdjDate').getValue();	
													var catcode = Ext.getCmp('category').getValue();
													var FromStockLocation = Ext.getCmp('fromlocation').getValue();
													var ToStockLocation = Ext.getCmp('ToStockLocation').getValue();
													var rsdno = Ext.getCmp('rsdno').getValue();
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
													/*var counteritem =countitem(); 
													if(counteritem<=0){
														Ext.MessageBox.alert('Error','Select Item '+counteritem);
														return false;
														
													}*/
													
													Ext.MessageBox.show({
														msg: 'Saving Transaction, please wait...',
														progressText: 'Saving...',
														width:300,
														wait:true,
														waitConfig: {interval:200},
														//icon:'ext-mb-download', //custom class in msg-box.html
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
															rsdno: rsdno,
															servedby:servedby,
															qty: Ext.encode(gridRepoData),
															value:btn
														},
														success: function(response){
															Ext.MessageBox.hide();
															var jsonData = Ext.JSON.decode(response.responseText);
															var errmsg = jsonData.message;
															//Ext.getCmp('AdjDate').setValue(AdjDate);
															if(errmsg!=''){
																setButtonDisabled(false);
																Ext.MessageBox.alert('Error',errmsg);
															}else{
																windowNewTransfer.close();
																//MerchandiseTransStore.proxy.extraParams = {action: 'AddItem'}
																myInsurance.load();
																Ext.MessageBox.alert('Success','Success Processing');
															}													
														} 
													});
													//Ext.MessageBox.hide();
													//this.setDisabled(true);
												}
											}
										}
									},{
										text:'Close',
										handler: function(){
											windowNewTransfer.close();
										}
									}
								]
							});
						}
						//var AdjDate = Ext.getCmp('AdjDate').getValue();	
						Ext.Ajax.request({
							url : '?action=NewTransfer',
							method: 'POST',
							success: function(response){
								var jsonData = Ext.JSON.decode(response.responseText);
								var AdjDate = jsonData.AdjDate;
								Ext.getCmp('AdjDate').setValue(AdjDate);

								MerchandiseTransStore.proxy.extraParams = {action: 'AddItem'}
								MerchandiseTransStore.load({
									scope: this,
									callback: function(records, operation, success){
										var countrec = MerchandiseTransStore.getCount();
										if(countrec>0){
											setButtonDisabled(false);
										}else{
											setButtonDisabled(true);
											
										}
									}
								});
							},
							failure: function(response){
								//Ext.MessageBox.hide();
								//var jsonData = Ext.JSON.decode(response.responseText);
								//Ext.MessageBox.alert('Error','Error Processing');
							}
						});
						
						windowNewTransfer.show();
						GetUserLogin();	
					},
					scale	: 'small'
				},{
					xtype: 'searchfield',
					id:'search_ref',
					name:'search_ref',
					fieldLabel: '<b>Search</b>',
					labelWidth: 50,
					width: 290,
					emptyText: "Search by reference",
					scale: 'small',
                    fieldStyle : 'background-color: #F2F3F4; color:green; font-weight:bold;',
					store: myInsurance,
					listeners: {
						change: function(field) {
							myInsurance.proxy.extraParams = {search_ref: field.getValue()};
							myInsurance.load();
						}
					}
				},{
					xtype:'datefield',
					fieldLabel:'From Date',
					name:'filter_date1',
					id:'fromdate',
					width: 232,
					labelWidth: 80,
					fieldStyle : 'background-color: #F2F3F4; color:black; font-weight:bold;',
					store: myInsurance,
					listeners:{
						change: function(field){
							myInsurance.proxy.extraParams = {fromdate: field.getValue(), todate: Ext.getCmp('todate').getValue()};
							myInsurance.load();
						}
					}
				},{
					xtype:'datefield',
					fieldLabel:'To Date',
					name:'filter_date1',
					id:'todate',
					width: 232,
					labelWidth: 80,
					fieldStyle : 'background-color: #F2F3F4; color:black; font-weight:bold;',
					store: myInsurance,
					listeners:{
						change: function(field){
							myInsurance.proxy.extraParams = {fromdate: Ext.getCmp('fromdate').getValue(), todate: field.getValue()};
							myInsurance.load();
						}
					}
				}]
		}],
		bbar : {
			xtype : 'pagingtoolbar',
			pageSize : itemsPerPage,
			store : myInsurance,
			displayInfo : true
		}
	});

	Ext.Ajax.request({
		url : '?action=getConfig',
		method: 'GET',
		success: function (response){
			Ext.MessageBox.hide();
			var jsonData = Ext.JSON.decode(response.responseText);
			var branchcode = jsonData.branchcode;
			var GridTitle = Ext.getCmp('grid').getTitle();
			Ext.getCmp('grid').setTitle(GridTitle+' '+branchcode);
			Ext.getCmp('fromlocation').setValue(branchcode);

			myInsurance.proxy.extraParams = {
				branchcode: branchcode
			}
			myInsurance.load();
			//Ext.MessageBox.alert('Success!',"Process complete"+branchcode+GridTitle);
			//window.open('?action=downloadfile&pathfile='+pathfile);
		},
		failure: function (response){
			Ext.MessageBox.hide();
			var jsonData = Ext.JSON.decode(response.responseText);
			Ext.MessageBox.alert('Error','Error Processing');
		}
	});
	function countitem(){
		//var totalitem;
		
		Ext.Ajax.request({
			url : '?action=getCountItem',
			method: 'GET',
			success: function (response){
				var jsonData = Ext.JSON.decode(response.responseText);
				return totalitem=setcountitem(jsonData.countitem);
			}
		});
		//Ext.MessageBox.alert('Item Count','Total Response'+totalitem);
		return totalitem;	
	}
	function setcountitem(itemcount){
		totalitem=itemcount;
		return totalitem;
	}
	function setButtonDisabled(valpass=false){
		Ext.getCmp('btnProcess').setDisabled(valpass);
	}
	function setButtonDisabledforAdd(valpass=false){
		Ext.getCmp('btnAddItem').setDisabled(valpass);
	}
	/*----Added by Robert 02/22/2022*/
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
	/*function GetTotalCost(){
		Ext.Ajax.request({
			url : '?action=getTotalCost',
			method: 'POST',
			success: function(response){
				var jsonData = Ext.JSON.decode(response.responseText);
				var Total_Cost = jsonData.TotalCost;
				Ext.getCmp('totalcost').setValue(Total_Cost);
			}
		});	
		return true;
	}*/
	/*----------End Here--------*/
});
