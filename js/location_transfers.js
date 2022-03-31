var newURL = window.location.protocol + "//" + window.location.host + window.location.pathname;

Ext.Loader.setConfig({enabled: true});
Ext.Loader.setPath('Ext.ux', '../js/ext4/examples/ux/');
Ext.require(['Ext.toolbar.Paging',
    'Ext.ux.form.SearchField',
	'Ext.layout.container.Column',
    'Ext.tab.*',
	'Ext.window.MessageBox',
	'Ext.selection.CheckboxModel',
	'Ext.grid.*','Ext.selection.CellModel']);




Ext.onReady(function(){
	Ext.QuickTips.init();
	var global_master_id;

	const queryString = window.location.search;
	//console.log(queryString);
	const urlParams = new URLSearchParams(queryString);
	var BrCode = urlParams.get('BRCODE')

	

	
 	Ext.define('insurance', {
		extend : 'Ext.data.Model',
		fields  : [
			{name:'trans_id',mapping:'trans_id'},
			{name:'reference',mapping:'reference'},
			{name:'tran_date',mapping:'tran_date'},
			{name:'loc_code',mapping:'loc_code'},
			{name:'loc_name',mapping:'loc_name'},
			{name:'fromloc',mapping:'fromloc'},
			{name:'category',mapping:'category'},
			{name:'category_id',mapping:'category_id'},
			{name:'remarks',mapping:'remarks'},
			{name:'qty',mapping:'qty'},
			{name:'serialise_total_qty',mapping:'serialise_total_qty'},
			{name:'delivery_date',mapping:'delivery_date'},
			{name:'approval',mapping:'approval'}
		]
	});

	 function Approval(val) {
		if(val == '0'){
			return '<span style="color:black;">Approved</span>';
		}
		else{
            return '<span style="color:red;font-weight: bold;">For Approval</span>';
        }
        return val;
    }
	var columnModel =[
		{header:'ID', dataIndex:'stock_moves_id', sortable:true, width:60, hidden: false},
		{header:'Reference', dataIndex:'reference', sortable:true, width:150},
		{header:'Trans Date', dataIndex:'tran_date', sortable:true, width:120},
		{header:'From Location', dataIndex:'fromloc', sortable:true, width:160},
		{header:'To Location', dataIndex:'loc_name', sortable:true, width:150},
		{header:'Category', dataIndex:'category', sortable:true, width:110},
		{header:'Total Items', dataIndex:'qty', sortable:true, width:90, align:'center', hidden: false},
		{header:'Remarks', dataIndex:'remarks', sortable:true, width:243, align:'center'},
		{header:'Approval', dataIndex:'approval', sortable:false, width:100, align:'center', hidden: false, renderer: Approval},
		{header	: 'Action',	xtype:'actioncolumn', align:'center', width:90,
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
                        approval = record.get('approval');
                        
                        //Ext.getCmp('status').setValue(status);
                        //edit_insurance_win.show();
						//window.location.replace('serial_details.php?serialid='+id);
						
						
						if(!windowItemSerialList){
							//var catcode = Ext.getCmp('category').getValue();
							//var brcode = Ext.getCmp('fromlocation').getValue();
							MTItemListingStore.proxy.extraParams = {catcode: catcode, branchcode:brcode,reference:reference,trans_id:id, approval:approval}
							MTItemListingStore.load();	
								
							var windowItemSerialList = Ext.create('Ext.Window',{
								title:'Item Listing',
								id:'windowItemSSerialList',
								modal: true,
								width: 1200,
								height:600,
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
											}],*/
											bbar : {
												xtype : 'pagingtoolbar',
												store : MTItemListingStore,
												displayInfo : true
											}
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
				},{
					icon:'../js/ext4/examples/shared/icons/application_lightning.png',
					tooltip: 'Approved',
					hidden: false,
					handler: function(grid, rowIndex, colIndex) {
						var record = myInsurance.getAt(rowIndex);
						reference = record.get('reference');
                        
	                    Ext.MessageBox.confirm('Confirm', 'Are you sure you want to Approved this record?', ApprovalFunction);
	                    function ApprovalFunction(btn) {
	                        Ext.Ajax.request({
								url : '?action=approval',
								method: 'POST',
								params:{
									reference: reference,
									value: btn
								},
								success: function (response){
									myInsurance.load();
									//windowItemSerialList.close();										
								},	
								failure: function (response){
									Ext.Msg.alert('Error', 'Processing ' + records.get('id'));
								}
							});
	                    };
	                },
	                getClass : function(value, meta, record, rowIx, ColIx, store) {
	                    if(record.get('approval') == 0 ) {
                			return 'x-hidden-visibility';
            			}
	                    
	                }
				},{
					icon:'../js/ext4/examples/shared/icons/application_lightning.png',
					tooltip: 'Disapproved',
					hidden: false,
					handler: function(grid, rowIndex, colIndex) {
						var record = myInsurance.getAt(rowIndex);
						reference = record.get('reference');
                        
	                    Ext.MessageBox.confirm('Confirm', 'Are you sure you want to Disapproved this record?', ApprovalFunction);
	                    function ApprovalFunction(btn) {
	                        Ext.Ajax.request({
								url : '?action=disapproval',
								method: 'POST',
								params:{
									reference: reference,
									value: btn
								},
								success: function (response){
									myInsurance.load();
									//windowItemSerialList.close();										
								},	
								failure: function (response){
									Ext.Msg.alert('Error', 'Processing ' + records.get('id'));
								}
							});
	                    };
	                },
	                getClass : function(value, meta, record, rowIx, ColIx, store) {
	                    if(record.get('approval') == 0 ) {
                			return 'x-hidden-visibility';
            			}
	                    
	                }
				},{
					icon: '../js/ext4/examples/shared/icons/printer.png',
					tooltip: 'View Report',
					hidden: false,
					handler: function(grid, rowIndex, colIndex) {
						var record = myInsurance.getAt(rowIndex);
						reference = record.get('reference');
						var win = new Ext.Window({
							autoLoad:{
								url:'../reports/inventory_transfer_location.php?reference='+reference,
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
				            src:'../reports/inventory_transfer_location.php?reference='+reference,
				            width:'100%',
				            height:'100%',
				            frameborder:0
				        }
						win.show();
						Ext.DomHelper.insertFirst(win.body, iframe)
						//window.open('../reports/merchandise_receipts.php?reference='+reference);
					},
					getClass : function(value, meta, record, rowIx, ColIx, store) {
	                    if(record.get('approval') > 0 ) {
                			return 'x-hidden-visibility';
            			}
	                    
	                }
				}
			]
		}
	];
	
	var myInsurance = Ext.create('Ext.data.Store', {
		model : 'insurance',
		name : 'myInsurance',
		method : 'POST',
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

	var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
        clicksToEdit: 1
    });
	
	var MerchandiseTransStore = Ext.create('Ext.data.Store', {
		fields: ['type_out','transno_out','stock_id','stock_description','trans_date','price','reference','currentqty','qty','standard_cost', 'lot_no', 'chasis_no', 'category_id','serialise_id','color','remarks'],
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
		{header:'#', dataIndex:'id', sortable:true, width:50, align:'center', hidden: true},
		{header:'Type Out', dataIndex:'type_out', sortable:true, width:60, renderer: columnWrap,hidden: true},
		{header:'TransOut', dataIndex:'transno_out', sortable:true, width:60, renderer: columnWrap,hidden: true},
		{header:'Model', dataIndex:'stock_id', sortable:true, width:60, renderer: columnWrap,hidden: false},
		{header:'Stock Description', dataIndex:'stock_description', sortable:true, renderer: columnWrap,hidden: false},
		{header:'Color', dataIndex:'color', sortable:true, renderer: columnWrap,hidden: false},
		{header:'Category', dataIndex:'category_id', sortable:true, width:100, renderer: columnWrap,hidden: true},
		{header:'Location', dataIndex:'loc_code', sortable:true,width:100, hidden: true},
		{header:'Standard<br/>Cost', dataIndex:'standard_cost', sortable:true, width:65, hidden: false, align:'right',
			renderer: Ext.util.Format.numberRenderer('0,000.00')
		},
		{header:'Current Qty', dataIndex:'currentqty', align:'center', sortable:true, width:40, hidden: true},
		{header:'Qty', dataIndex:'qty', sortable:true, width:40, hidden: false, align:'center',
			editor:{
				completeOnEnter: true,
				field:{
					xtype:'numberfield',
					allowBlank: false,
					minValue:0,
					listeners : {
					    keyup: function(grid, rowIndex, colIndex) {
						//var record = GRNItemsStore.getAt(rowIndex);
                        //console.log('Keup Logs');

                    },
						specialkey: function(f,e){
							if (e.getKey() == e.ENTER) {
								//alert('Hello World'+f.value());
							}
						}
					}
				}
			}
		},
		{header:'Engine No.', dataIndex:'lot_no', sortable:true, width:100,renderer: columnWrap, hidden: false},
		{header:'Chasis No.', dataIndex:'chasis_no', sortable:true, width:100,renderer: columnWrap, hidden: false},
		{header:'Remarks', dataIndex:'remarks', sortable:true, width:100,renderer: columnWrap, hidden: true,
			editor:{
				completeOnEnter:true,
				field:{
					xtype:'textfield',
					name:'remarks',
					id:'remarks'
				}
			}
		},
		{header:'Action',xtype:'actioncolumn', align:'center', width:40,
			items:[
				{
					icon:'../js/ext4/examples/shared/icons/cancel.png',
					tooltip:'Delete',
					handler: function(grid, rowIndex, colIndex){
						var record = MerchandiseTransStore.getAt(rowIndex);
						var id = record.get('id');
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
						MerchandiseTransStore.load({
							params:{action:'RemoveItem',id:id, serialise_id: serialise_id, AdjDate:AdjDate, model:model},
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
			]
		}
	]
	
	function columnWrap(val){
		return '<div style="white-space:normal !important;">'+ val +'</div>';
	}
	var MTItemListingStore = Ext.create('Ext.data.Store', {
		fields: ['serialise_id', 'model', 'lot_no', 'chasis_no', 'color', 'item_description', 'stock_description', 'currentqty','qty','category_id','reference','tolocation','item_type','remarks','approval','stock_moves_id'],
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
		fields: ['serialise_id', 'model', 'lot_no', 'chasis_no', 'standard_cost','color', 'item_description', 'stock_description', 'qty','category_id','type_out','transno_out','item_type'],
		autoLoad: false,
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
	
	var columnItemSerial = [
		{header:'id', dataIndex:'serialise_id', sortable:true, width:60,hidden: true},
		{header:'Serialise', dataIndex:'serialised', sortable:true, width:30, renderer: columnWrap,hidden: true},
		{header:'Type', dataIndex:'type_out', sortable:true, width:30, renderer: columnWrap,hidden: true},
		{header:'Item Type', dataIndex:'item_type', sortable:true, width:30, renderer: columnWrap,hidden: true},
		{header:'Transno', dataIndex:'transno_out', sortable:true, width:30, renderer: columnWrap,hidden: true},
		{header:'Reference', dataIndex:'reference', sortable:true, width:80, renderer: columnWrap,hidden: false},
		{header:'RR Date', dataIndex:'tran_date', sortable:true, width:50, renderer: columnWrap,hidden: false, renderer: Ext.util.Format.dateRenderer('m/d/Y')},
		{header:'Model', dataIndex:'model', sortable:true, width:60, renderer: columnWrap,hidden: false},
		{header:'Item Description', dataIndex:'stock_description', sortable:true, renderer: columnWrap,hidden: false},
		{header:'Color', dataIndex:'item_description', sortable:true, renderer: columnWrap,hidden: false},
		{header:'Category', dataIndex:'category_id', sortable:true, width:100, renderer: columnWrap,hidden: true},
		{header:'Standard<br/>Cost', dataIndex:'standard_cost', sortable:true, width:70, hidden: true, align:'right'},
		{header:'Qty', dataIndex:'qty', sortable:true, width:40, hidden: false, align:'center'},
		{header:'Engine No.', dataIndex:'lot_no', sortable:true, width:100,renderer: columnWrap, hidden: false},
		{header:'Chasis No.', dataIndex:'chasis_no', sortable:true, width:100,renderer: columnWrap, hidden: false},
		{header:'Action',xtype:'actioncolumn', align:'center', width:40, hidden: false,
			items:[
				{
					icon: '../js/ext4/examples/shared/icons/accept.png',
					tooltip: 'Accept',
					handler: function(grid, rowIndex, colIndex){
						var record = ItemListingStore.getAt(rowIndex);
						var serialise_id = record.get('serialise_id');	
						var model = record.get('model');	
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
						var item_type = record.get('item_type');	
						var rr_date = record.get('tran_date');	
															
						MerchandiseTransStore.load({
							params:{serialise_id: serialise_id, AdjDate:AdjDate, model:model, sdescription:sdescription, color:color, category:category, qty:qty, lot_no:lot_no, chasis_no:chasis_no, type_out:type_out, transno_out:transno_out, standard_cost:standard_cost,serialised:serialised, item_type:item_type, rr_date: rr_date},
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
			]
		}
	]

	var columnItemSerialView = [
		{header:'id', dataIndex:'serialise_id', sortable:true, width:60,hidden: true},
		{header:'SM ID', dataIndex:'stock_moves_id', sortable:true, width:60,hidden: false},
		{header:'To Location', dataIndex:'tolocation', sortable:true, width:60, renderer: columnWrap,hidden: false},
		{header:'Model', dataIndex:'model', sortable:true, width:60, renderer: columnWrap,hidden: false},
		{header:'Item Description', dataIndex:'stock_description', sortable:true, renderer: columnWrap,hidden: false},
		{header:'Color', dataIndex:'item_description', sortable:true, renderer: columnWrap,hidden: false},
		{header:'Category', dataIndex:'category_id', sortable:true, width:100, renderer: columnWrap,hidden: true},
		{header:'Standard<br/>Cost', dataIndex:'standard_cost', sortable:true, width:70, hidden: true, align:'right'},
		{header:'Current Qty', dataIndex:'currentqty', sortable:true, width:40, hidden: true, align:'center'},
		{header:'Qty', dataIndex:'qty', sortable:true, width:40, hidden: false, align:'center'},
		{header:'Engine No.', dataIndex:'lot_no', sortable:true, width:100,renderer: columnWrap, hidden: false},
		{header:'Chasis No.', dataIndex:'chasis_no', sortable:true, width:100,renderer: columnWrap, hidden: false},
		{header:'Item Type', dataIndex:'item_type', sortable:true, width:100,renderer: columnWrap, hidden: true},
		{header:'Remarks', dataIndex:'remarks', sortable:true, width:100,renderer: columnWrap, hidden: false},
		{header:'Action',xtype:'actioncolumn', align:'center', width:40, hidden: true}
	]
		
	var gridMT = {
		xtype:'grid',
		id:'mtgrid',
		anchor:'100%',
		forceFit: true,
		//flex:1,
		//autoScroll: true,
		//height: 500,
		store: MerchandiseTransStore,
		columns: columnTransferModel,
		selModel: {selType: 'cellmodel'},
		plugins: [cellEditing],
		//padding: '5px',
		border: false,
		frame:false,
		viewConfig:{
			stripeRows: true,
			listeners: {
            	refresh: function(view) {
					var countrec = MerchandiseTransStore.getCount();
					if(countrec>0){
						setButtonDisabled(false);
					}else{
						setButtonDisabled(true);
						
					}
				}
        	},
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
			items:[
				{
				icon   	: '../js/ext4/examples/shared/icons/fam/add.gif',
				tooltip	: 'Select Item',
				text 	: 'Select Item',
				handler: function(){
					
					if(!windowItemList){
						var catcode = Ext.getCmp('category').getValue();
						var brcode = Ext.getCmp('fromStockLocation').getValue();
						var AdjDate = Ext.getCmp('AdjDate').getValue();
						
						ItemListingStore.proxy.extraParams = {catcode: catcode, branchcode:brcode, trans_date:AdjDate}
						ItemListingStore.load();	
							
						var windowItemList = Ext.create('Ext.Window',{
							title:'Item Listing',
							id:'windowItemList',
							modal: true,
							width: 1200,
							height:600,
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
										id:'ItemSerialListing',
										store: ItemListingStore,
										columns: columnItemSerial,
										dockedItems:[{
											dock:'top',
											xtype:'toolbar',
											name:'searchSerialBar',
											items:[{
												width	: 300,
												xtype	: 'textfield',
												name 	: 'searchSerialItem',
												id		:'searchSerialItem',
												fieldLabel: 'Item Description',
												labelWidth: 120,
												listeners : {
													/*specialkey: function(f,e){							
														if (e.getKey() == e.ENTER) {

															var catcode = Ext.getCmp('category').getValue();
															var brcode = Ext.getCmp('fromStockLocation').getValue();
															ItemListingStore.proxy.extraParams = { 
																query:this.getValue(), 
																catcode: catcode,
																branchcode: brcode
															}
															ItemListingStore.load();									
														}
													}*/

													specialkey: function(f,e){							
														if (e.getKey() == e.ENTER) {
															
															var class_type = Ext.getCmp('searchSerialItem').getValue();
															ItemListingStore.proxy.extraParams = { 											 
																query:this.getValue()
															}
															ItemListingStore.load();								
														}
													}								
												}
											},{
												iconCls:'clear-search'
											},{
												xtype:'textfield',
												name:'searchSerial',
												id:'searchSerial',
												fieldLabel:'Serial/Engine No.',
												labelWidth: 120,
												listeners : {
													/*specialkey: function(f,e){							
														if (e.getKey() == e.ENTER) {
														    var catcode = Ext.getCmp('category').getValue();
															var brcode = Ext.getCmp('fromlocation').getValue();
															ItemListingStore.proxy.extraParams = { 
																serialquery:this.getValue(), 
																catcode: catcode,
																branchcode: brcode
															}
															ItemListingStore.load();									
														}
													}*/

													specialkey: function(f,e){							
														if (e.getKey() == e.ENTER) {
															
															var class_type = Ext.getCmp('searchSerial').getValue();
															ItemListingStore.proxy.extraParams = { 											 
																serialquery:this.getValue()														
															}
															ItemListingStore.load();								
														}
													}								
												}
											}]
										}],
										bbar : {
											xtype : 'pagingtoolbar',
											store : ItemListingStore,
											displayInfo : true
										}
									}]
								}
							],
							buttons:[
								{
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
			}
			]	
		}],
        listeners:{
            validateedit: function(editor, e){
                var record = MerchandiseTransStore.getAt(e.record.id);
			    var id = record.get('id');
                var currentqty = record.get('currentqty');
                var qty = record.get('qty');
                var stock_id = record.get('stock_id');
                var serial_no = record.get('lot_no');

                /*if(currentqty < qty){
                    alert('Sorry, Quantity '+qty+' is Greater than Available Quantity On Hand: '+currentqty);
                    return false;
                }else return true;
				*/

                //return true;
            }
        }
	}

	Ext.create('Ext.grid.Panel', {
		renderTo: 'merchandisetransfer-grid',
		layout: 'fit',
		//height	: 550,
		title	: 'Item Location Transfer',
		store	    :	myInsurance,
		id 		    : 'grid',
		columns 	: columnModel,
		//forceFit 	: true,
		frame: false,
		width: 1275,
		frame		: true,
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
					labelWidth: 50,
					hidden: true
				},Supplier_Filter,
				{
					xtype:'combobox',
					fieldLabel:'Transfer From',
					name:'fromStockLocation',
					id:'fromStockLocation',
					queryModel:'local',
					triggerAction : 'all',
					displayField  : 'location_name',
					valueField    : 'loc_code',
					editable      : true,
					forceSelection: true,
                    allowBlank: false,
					required: true,
					hiddenName: 'loc_code',
					typeAhead: true,
					emptyText:'--Select--',
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
							url: '?action=fromlocation',
							reader:{
								type : 'json',
								root : 'result',
								totalProperty : 'total'
							}
						}
					})
				},
				
				{
					icon   	: '../js/ext4/examples/shared/icons/fam/add.gif',
					tooltip	: 'New Item Transfer',
					text 	: 'New Item Transfer',
					hidden: false,
					handler : function(){
						
						var brcode = Ext.getCmp('fromStockLocation').getValue();
			   			if(brcode==null){
							Ext.MessageBox.alert('Error','Select Transfer from Location');
							return false;
						}
						if(!windowNewTransfer){
							var windowNewTransfer = Ext.create('Ext.Window',{
								title:'Merchandise Transfer Location Entry',
								modal: true,
								width: 1200,
								//height:500,
								bodyPadding: 5,
								layout:'anchor',
								items:[
									{
										xtype:'fieldset',
										title:'Merchandise Transfer Location Header',
										layout:'anchor',
										defaultType:'textfield',
										//frame: false,
										//border: false,
										//padding: '5px',
										fieldDefaults:{
											labelAlign:'right'
											
										},
										items:[
											{
												xtype:'fieldcontainer',
												layout:'hbox',
												items:[
													{
														xtype:'combobox',
														fieldLabel:'Transfer To',
														name:'ToStockLocation',
														id:'ToStockLocation',
														queryModel:'local',
														triggerAction : 'all',
                    									displayField  : 'location_name',
                    									valueField    : 'loc_code',
                    									editable      : true,
                    									forceSelection: true,
                                                        allowBlank: false,
                    									required: true,
                    									hiddenName: 'loc_code',
                    									typeAhead: true,
                    									emptyText:'--Select--',
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
																url: '?action=location&brcode='+brcode,
																reader:{
																	type : 'json',
																	root : 'result',
																	totalProperty : 'total'
																}
															}
                    									})
													},{
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
                    									emptyText:'--Select--',
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
																	Ext.ComponentQuery.query('grid gridcolumn[dataIndex^="color"]')[0].show();
																	
																}else{
																	Ext.ComponentQuery.query('grid gridcolumn[dataIndex^="lot_no"]')[0].setText('Serial No.');
																	Ext.ComponentQuery.query('grid gridcolumn[dataIndex^="chasis_no"]')[0].hide();
																	Ext.ComponentQuery.query('grid gridcolumn[dataIndex^="color"]')[0].hide();
																}
																//Ext.Msg.alert('Category', v);
																
															}
														}
													},{
														xtype:'datefield',
														fieldLabel:'Trans Date',
														name:'trans_date',
														id:'AdjDate'/*,
														value: new Date()*/
														,
														listeners:{
															change: function(){
																MerchandiseTransStore.load({
																	params:{view:1}
																});
															}
														}
													}
												]
											},{
												xtype:'fieldcontainer',
												//width:1200,
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
										frame: false,
										//autoScroll: true,
										//layout:'fit',
										padding:'5px',
										border: false,
										items:[gridMT]
									}
								],buttons:[
									{
										text:'Process Transfer',
										id:'btnProcess',
										handler:function(){
											setButtonDisabled(true);
											var AdjDate = Ext.getCmp('AdjDate').getValue();	
											var catcode = Ext.getCmp('category').getValue();
											var FromStockLocation = Ext.getCmp('fromStockLocation').getValue();
											var ToStockLocation = Ext.getCmp('ToStockLocation').getValue();
											var memo_ = Ext.getCmp('memo').getValue();
											if(ToStockLocation=='' || ToStockLocation==null){
												setButtonDisabled(false);
												Ext.MessageBox.alert('Error','Select Transfer to Location');
												return false;
											}
											if(catcode=='' || catcode==null){
												setButtonDisabled(false);
												Ext.MessageBox.alert('Error','Select Category');
												return false;
											}
											
											if(countitem()>0){
												setButtonDisabled(false);
												Ext.MessageBox.alert('Error','Select Item'+countitem());
												return false;
												
											}
											
														
											Ext.Ajax.request({
												url : '?action=SaveTransfer',
												method: 'POST',
												params:{
													AdjDate:AdjDate,
													catcode:catcode,
													FromStockLocation: FromStockLocation,
													ToStockLocation: ToStockLocation,
													memo_: memo_
												},
												success: function (response){
													var jsonData = Ext.JSON.decode(response.responseText);
													var AdjDate = jsonData.AdjDate;
													Ext.getCmp('AdjDate').setValue(AdjDate);
													MerchandiseTransStore.load();
													myInsurance.load();
													windowNewTransfer.close();
													//MerchandiseTransStore.proxy.extraParams = {action: 'AddItem'}
												},
												failure: function (response){
													setButtonDisabled(false);
													//Ext.MessageBox.hide();
													//var jsonData = Ext.JSON.decode(response.responseText);
													Ext.MessageBox.alert('Error','Error Saving Process');
												}
											});
											
											
										}
									},
									{
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
							success: function (response){
								var jsonData = Ext.JSON.decode(response.responseText);
								var AdjDate = jsonData.AdjDate;
								Ext.getCmp('AdjDate').setValue(AdjDate);
								
								//MerchandiseTransStore.proxy.extraParams = {action: 'AddItem'}
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
							failure: function (response){
								//Ext.MessageBox.hide();
								//var jsonData = Ext.JSON.decode(response.responseText);
								//Ext.MessageBox.alert('Error','Error Processing');
							}
						});
						
						windowNewTransfer.show();
						
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

	Ext.Ajax.request({
		url : '?action=getConfig',
		method: 'GET',
		success: function (response){
			Ext.MessageBox.hide();
			var jsonData = Ext.JSON.decode(response.responseText);
			var branchcode = jsonData.branchcode;
			var GridTitle = Ext.getCmp('grid').getTitle();
			//Ext.getCmp('grid').setTitle(GridTitle+' '+branchcode);
			//Ext.getCmp('fromlocation').setValue(branchcode);
			
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
		Ext.Ajax.request({
			url : '?action=getCountItem',
			method: 'GET',
			success: function (response){
				var jsonData = Ext.JSON.decode(response.responseText);
				return jsonData.countitem;
			}
		});
			
	}
	
	function setButtonDisabled(valpass=false){
		Ext.getCmp('btnProcess').setDisabled(valpass);
	}
});
