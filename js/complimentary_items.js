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
	'Ext.data.*',
	'Ext.selection.CellModel'
	]);
	
Ext.onReady(function(){
	Ext.QuickTips.init();
	var global_master_id;

	const queryString = window.location.search;
	const urlParams = new URLSearchParams(queryString);
	var BrCode = urlParams.get('BRCODE')
	
 	Ext.define('insurance', {
		extend : 'Ext.data.Model',
		fields  : [
			{name:'trans_id',mapping:'trans_no'},
			{name:'reference',mapping:'reference'},
			{name:'tran_date',mapping:'tran_date'},
			{name:'loc_code',mapping:'loc_code'},
			{name:'loc_name',mapping:'loc_name'},
			{name:'category_name',mapping:'category_name'},
			{name:'category_id',mapping:'category_id'},
			{name:'remarks',mapping:'remarks'},
			{name:'qty',mapping:'qty'},
			{name:'statusmsg',mapping:'status'},
			{name:'serialise_total_qty',mapping:'serialise_total_qty'},
			{name:'delivery_date',mapping:'delivery_date'}
		]
	});
	
	var columnModel =[
		{header:'ID', dataIndex:'trans_id', sortable:true, width:20, hidden: false},
		{header:'Reference', dataIndex:'reference', sortable:true, width:60},
		{header:'Trans Date', dataIndex:'tran_date', sortable:true, width:40},
		{header:'From Location', dataIndex:'loc_name', sortable:true, width:90, hidden: false},
		{header:'Category', dataIndex:'category_name', sortable:true, width:90},
		{header:'Total Items', dataIndex:'qty', sortable:true, width:50, align:'center'},
		{header:'Remarks', dataIndex:'remarks', sortable:true, width:150, align:'left'},
		{header:'Status', dataIndex:'statusmsg', sortable:true, width:50, hidden: true},
		{header	: 'Action',	xtype:'actioncolumn', align:'center', width:40,
			items:[
				{
					icon: '../js/ext4/examples/shared/icons/application_view_columns.png',
					tooltip: 'Serial Items Detail',
					hidden: true,
					handler: function(grid, rowIndex, colIndex) {
						var record = myInsurance.getAt(rowIndex);
						id = record.get('trans_id');
                        reference = record.get('reference');
                        brcode = record.get('loc_code');
                        catcode = record.get('category_id');
                        
						if(!windowItemSerialList){
							MTItemListingStore.proxy.extraParams = {
								catcode: catcode, 
								branchcode:brcode,
								reference:reference, 
								trans_id:id
							};
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
											dockedItems:[{
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
														/*specialkey: function(f,e){							
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
														}*/

														specialkey: function(f,e){							
															if (e.getKey() == e.ENTER) {
																
																var class_type = Ext.getCmp('searchSerialItemView').getValue();
																MTItemListingStore.proxy.extraParams = { 
																	stock_description:this.getValue(), 
																	class_type: class_type
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
													hidden: true,
													listeners: {
														specialkey: function(f,e){							
															if (e.getKey() == e.ENTER) {
																
																var class_type = Ext.getCmp('searchSerialView').getValue();
																MTItemListingStore.proxy.extraParams = { 
																	lot_no:this.getValue(), 
																	class_type: class_type
																}
																MTItemListingStore.load();								
															}
														}		
													}
												}]
											}],
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
					icon: '../js/ext4/examples/shared/icons/printer.png',
					tooltip: 'Complimentary Report',
					handler: function(grid, rowIndex, colIndex) {
						var record = myInsurance.getAt(rowIndex);
						reference = record.get('reference');
						var win = new Ext.Window({
							autoLoad:{
								url:'../reports/complimentary_items_report.php?reference='+reference,
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
				            src:'../reports/complimentary_items_report.php?reference='+reference,
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
                myInsurance.proxy.extraParams = { supplier_id: v }
  				myInsurance.load();
  			}
  		}

    })


	var MerchandiseTransStore = Ext.create('Ext.data.Store', {
		fields: ['id','stock_id','stock_description','trans_date','price','reference','qty','standard_cost', 'lot_no', 'chasis_no', 'category_id','serialise_id','color','type_out','transno_out','line_item','rr_date'],
		//autoLoad: false,
		//autoSync: true,
		metho:'GET',
		proxy : {
			type: 'ajax',
			url	: '?action=AddItem',
			reader:{
				type : 'json',
				root : 'result',
				totalProperty : 'total'
			}
		}
	});
	
	var columnTransferModel = [
		{header:'line', dataIndex:'line_item', sortable:true, width:50, align:'center', hidden: false},
		{header:'Trans', dataIndex:'transno_out', sortable:true, width:60, hidden: true},
		{header:'Type', dataIndex:'type_out', sortable:true, width:60, hidden: true},
		{header:'RR Date', dataIndex:'rr_date', sortable:true, width:60, align:'center', hidden: false},
		{header:'#', dataIndex:'id', sortable:true, width:50, align:'center', hidden: true},
		{header:'Model', dataIndex:'stock_id', sortable:true, width:80, renderer: columnWrap,hidden: false},
		{header:'Stock Description', dataIndex:'stock_description', sortable:true, renderer: columnWrap,hidden: false},
		{header:'Color', dataIndex:'color', sortable:true, renderer: columnWrap,hidden: false},
		{header:'Category', dataIndex:'category_id', sortable:true, width:100, renderer: columnWrap,hidden: true},
		{header:'Location', dataIndex:'loc_code', sortable:true,width:60, hidden: true},
		{header:'Standard Cost', dataIndex:'standard_cost', sortable:true, width:100, hidden: false, align:'right',
			renderer: Ext.util.Format.numberRenderer('0,000.00')
		},
		{header:'Qty', dataIndex:'qty', sortable:true, width:40, hidden: false, align:'center',
			editor:{
				completeOnEnter: true,
				field:{
					xtype:'numberfield',
					allowBlank: false,
					minValue:0,
					listeners : {
					    keyup: function(grid, rowIndex, colIndex) {
						
                    	},
						specialkey: function(f,e){
							if (e.getKey() == e.ENTER) {
								
							}
						}
					}
				}
			}
		},
		{header:'Engine No.', dataIndex:'lot_no', sortable:true, width:100,renderer: columnWrap, hidden: false},
		{header:'Chasis No.', dataIndex:'chasis_no', sortable:true, width:100,renderer: columnWrap, hidden: false},
		{header:'Action',xtype:'actioncolumn', align:'center', width:40, hidden: false,
					
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
						var AdjDate = Ext.getCmp('AdjDate').getValue();	
						
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
								GLItemsStore.load();
								GetTotalBalance();
						
							}
						});
					}
				}
			]
		}
	]
	
	 
	var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
        clicksToEdit: 1,
		rowIndex:-1,
		listeners:{
			beforeedit: function(cellEditor, context, eOpts){
        		rowIndex = context.rowIdx;
			}
		}
    });	
	var cellEditing1 = Ext.create('Ext.grid.plugin.CellEditing', {
        clicksToEdit: 1,
		rowIndex:-1,
		listeners:{
			beforeedit: function(cellEditor, context, eOpts){
        		rowIndex = context.rowIdx;
			}
		}
    });	

	Ext.define('GLListingModel', {
	    extend: 'Ext.data.Model',
	    fields: ['code_id', 'description', 'debit', 'credit', 'actualprice', 'line', 'class_id', 'memo', 'person_type_id', 'person_id', 'branch_id', 'person_name', 'mcode', 'master_file', 'mastertype', 'master_file_type','line_item']
	});

	var GLItemsStore = Ext.create('Ext.data.Store', {
		model: 'GLListingModel',
		storeId:'GLListingModel',
		autoLoad: true,
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
				read:'?action=display_gl_item',
				update:'?action=updateGLData'
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
	
    var MasterfileTypeStore = new Ext.create ('Ext.data.Store',{
		fields 	: 	['id','namecaption'],
		data 	: 	[{"id":"99","namecaption":"Not Applicable"},
					{"id":"2","namecaption":"Customer"},
                    {"id":"3","namecaption":"Supplier"}
                    //{"id":"6","namecaption":"Employee"}
                    ],
        autoLoad: true
	});
	Ext.define('MasterfileModel', {
		extend : 'Ext.data.Model',
		fields  : [
			{name:'id',mapping:'id'},
			{name:'namecaption',mapping:'namecaption'}
		]
	});
	var MasterfileStore = Ext.create('Ext.data.Store', {
		model : 'MasterfileModel',
		name : 'masterfilemodel',
		method : 'GET',
		proxy : {
			type: 'ajax',
			url	: '?action=masterfile',
			reader:{
				type : 'json',
				root : 'result',
				totalProperty : 'total'
			}
		},
		autoLoad: true
	});
		
	var columnJEModel = [
		{header:'#', dataIndex:'line_item', sortable:true, width:50, align:'center', hidden: false},
		{header:'#', dataIndex:'line', sortable:true, width:30, align:'center', hidden: true},
		{header:'Account Code', dataIndex:'code_id', sortable:true, width:100, align:'center', hidden: false},
		{header:'Account Name', dataIndex:'description', sortable:true, width:150, renderer: columnWrap,hidden: false},
		{header:'Mcode', dataIndex:'mcode', sortable:true, width:50, renderer: columnWrap,hidden: true},
		{header:'Person ID', dataIndex:'person_id', sortable:true, width:50, renderer: columnWrap,hidden: true},
		{header:'Masterfile Type', dataIndex:'master_file_type', sortable:true, width:100, renderer: columnWrap,hidden: false},
		{header:'Person Type', dataIndex:'mastertype', sortable:true, width:100, renderer: columnWrap,hidden: true,
			editor:{
				field:{
					xtype: 'combo',
					name:'masterfile_type',
                    id:'masterfile_type',
                    anchor:'100%',
                    typeAhead: true,
		            triggerAction: 'all',
		            store: MasterfileTypeStore,
					displayField  : 'namecaption',
					valueField    : 'namecaption',
					editable      : false,
					forceSelection: true,
					required: true,
					hiddenName: 'id',
					listeners: {
						select: function(cmb, rec, idx) {
						
							var record = GLItemsStore.getAt(rowIndex);
							record.set('person_type_id',rec.data.id);
							record.set('master_file_type',rec.data.id);
							var account_code = record.get('code_id');
							var person_type_id = record.get('person_type_id');
						
							if( person_type_id===99){
								MasterfileStore.load();
							}else{
								MasterfileStore.load({
									params: { 
										'masterfile_type': rec.data.id,
									 	'account_code':account_code
									 }
								});
							}
		
						}	
					}
				}
			}
		},
		{
			header:'Masterfile', dataIndex:'master_file', sortable:true, width:120,hidden: true,
			editor:{
				field:{
					xtype:'combo',
                    name:'masterfile2',
					id:'masterfile2',
                    anchor:'100%',
                    typeAhead: true,
		            triggerAction: 'all',
		            store: MasterfileStore,
					queryMode: 'local',
					displayField  : 'namecaption',
					valueField    : 'namecaption',
					hiddenName: 'id',
					listeners:{
						select: function(combo, rec, index){
							var record = GLItemsStore.getAt(rowIndex);
							record.set('mcode',rec.data.id);
							record.set('person_id',rec.data.id);
							record.commit();
							
						}
					} 
				}
			}
		},
		{
			header:'Debit', dataIndex:'debit', sortable:true, width:80, hidden: false, align:'right',
			editor:{
				field:{
                    xtype:'numberfield',
					hideTrigger: true,
					decimalPrecision: 2,
                    name:'debit_amount2',
                    id:'debit_amount2',
                    anchor:'100%',
					listeners : {
					    keyup: function(grid, rowIndex, colIndex) {
							GetTotalBalance();
                    	},
						specialkey: function(f,e){
							if (e.getKey() == e.ENTER) {
								GetTotalBalance();
							}
						}
					}										
                }
			},
			renderer:function(value, metaData, record, rowIdx, colIdx, store, view) {
				GetTotalBalance();
                return Ext.util.Format.number(value,'0,000.00');
            } 
			
			//Ext.util.Format.numberRenderer('0,000.00')
		},
		{header:'Credit', dataIndex:'credit', sortable:true, width:80, hidden: false, align:'right',
			renderer:function(value, metaData, record, rowIdx, colIdx, store, view) {
				GetTotalBalance();
                return Ext.util.Format.number(value,'0,000.00');
            }
		},
		{header:'Actual Price', dataIndex:'actualprice', sortable:true, width:80, hidden: true, align:'right'},
		{header:'Memo', dataIndex:'memo', sortable:true, width:150, renderer: columnWrap,hidden: false,
			editor:{
				field:{
					xtype:'textfield',
					name:'memo2',
					id:'memo2',
					anchor:'100%'
				}
			}
		},
		{header:'Action',xtype:'actioncolumn', align:'center', width:40,
			items:[
				{
					icon:'../js/ext4/examples/shared/icons/report_go.png',
					tooltip:'Edit',
					hidden: true,
					handler: function(grid, rowIndex, colIndex){
						var record = GLItemsStore.getAt(rowIndex);
						var id = record.get('line');
						var account_code = record.get('code_id');
						var account_name = record.get('description');
						var person_type_id = record.get('person_type_id');
						var person_id = record.get('person_id');
						var mcode = record.get('mcode');
						var master_file = record.get('master_file');
						var memo = record.get('memo');
						if(record.get('debit')>0)
							var amount=record.get('debit').split(',').join('');
						else
						var amount = record.get('credit').split(',').join('');
						
						if(!editjewindow){
							var editjewindow = Ext.create('Ext.Window',{
                               	width: 500,
                                layout:'fit',
								modal: true,
								closeAction:'destroy',
								items:[{
                                    xtype:'form',
									id:'updateje_form',
									url  : '?action=updateje',
									layout:'anchor',
									method:'POST',
                                    defaults 	: {
                    					msgTarget 	: 'side',
                    					border      : false,
                                        padding: '5 5 0 5',
                    					anchor		: '100%'
                    				},
                                    items:[{
											xtype:'textfield',
											value: id,
											name:'line_no',
											hidden: true
										},{
											xtype:'textfield',
											fieldLabel:'Account Code',
											value:account_code,
											name:'account_code',
											readOnly: true,
											hidden: true
										},{
											xtype:'textfield',
											fieldLabel:'Account Name',
											value:account_name,
											name:'account_name',
											readOnly: true,
											hidden: true
										},{
	                                        xtype:'combo',
	                                        fieldLabel:'Type',
	                                        name:'masterfile_type',
	                                        id:'masterfile_type',
	                                        anchor:'100%',
	                                        typeAhead: true,
								            triggerAction: 'all',
								            store: MasterfileTypeStore,
											displayField  : 'namecaption',
											valueField    : 'id',
											editable      : false,
											forceSelection: true,
											required: true,
											hiddenName: 'id',
											value: person_type_id,
											listeners: {
												select: function(cmb, rec, idx) {
													//var branch_combo = Ext.getCmp('branch_combo').getValue();
													MasterfileModel=Ext.getCmp('masterfile');
							                        MasterfileModel.clearValue();
													if( this.getValue()===99){
														MasterfileModel.store.load();
													}else{
														MasterfileModel.store.load({
															params: { 'masterfile_type': this.getValue(),
															 		'account_code':account_code
															 }
														});
													}
							
							                        MasterfileModel.enable();
							
												}/*,
												change: function(combo, value) {
													alert(value);
												}*/
										}
                                    },{
                                        xtype:'combo',
                                        fieldLabel:'Masterfile',
                                        name:'masterfile',
										id:'masterfile',
                                        anchor:'100%',
                                        typeAhead: true,
							            triggerAction: 'all',
							            store: MasterfileStore,
										queryMode: 'local',
										displayField  : 'namecaption',
										valueField    : 'id',
										hiddenName: 'id',
										value:master_file,
										listeners:{
											change: function(combo, value,index){
												return decodeHtmlEntity(combo.getRawValue());
											}
										} 
											
                                    },{
                                        xtype:'numberfield',
										hideTrigger: true,
										decimalPrecision: 2,
                                        fieldLabel:'Amount',
                                        name:'debit_amount',
                                        id:'debit_amount',
                                        anchor:'100%',
                                        required: true,
										value: amount											
                                    },{
                                        xtype:'textarea',
                                        fieldLabel:'Memo',
                                        name:'linememo',
                                        id:'linememo',
                                        anchor:'100%',
										value: memo
                                    }],
                                    buttons:[{
                                        text:'Save',
										handler: function(){
											var updateje_form = Ext.getCmp('updateje_form').getForm();
				
											if(updateje_form.isValid()) {
												updateje_form.submit({
													waitMsg:'Updating Data...',
													success : function (response) {
														updateje_form.reset();
														editjewindow.close();
														GLItemsStore.load();
														GetTotalBalance();
														//myInsurance.load();
													},
													failure : function (response) {
														Ext.MessageBox.show({
											                title: "Error Updating",
											                msg: "Please fill the required fields correctly.",
											                buttons: Ext.MessageBox.OK,
											                icon: Ext.MessageBox.WARNING
											            });
													}
												});
											}	
										}
                                    },{
                                        text:'Cancel',
                                        handler: function(){
                                            editjewindow.close();
											editjewindow=null;
                                        }
                                    }]

                                }]
                            });
                        }
						MasterfileModel=Ext.getCmp('masterfile');
                        MasterfileModel.clearValue();
						if( person_type_id===99){
							MasterfileModel.store.load();
						}else{
							MasterfileModel.store.load({
								params: { 'masterfile_type': person_type_id,
								 		'account_code':account_code,
										person_id:person_id
								 }
							});
						}

                        MasterfileModel.enable();
						Ext.getCmp('masterfile').setValue(master_file);
						Ext.getCmp('masterfile_type').setValue(person_type_id);
						editjewindow.setTitle('Edit JE Window');
						editjewindow.show();
					}
				},
				{
					icon:'../js/ext4/examples/shared/icons/cancel.png',
					tooltip:'Delete',
					handler: function(grid, rowIndex, colIndex){
						var record = GLItemsStore.getAt(rowIndex);
						var id = record.get('line');
						var account_code = record.get('code_id');
						var line_item = record.get('line_item');
						
						Ext.Ajax.request({
							url : '?action=delete_gl_entry&line_id='+id+'&account_code='+account_code+'&line_item='+line_item,
							method: 'POST',
							success: function(response){
								GLItemsStore.load();
								MerchandiseTransStore.load({params: { 
										view: 1
									 }});
								GLItemsStore.load();
								GetTotalBalance();
							},
							failure: function(response){
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
		fields: ['serialise_id', 'model', 'lot_no', 'chasis_no', 'color', 'item_description', 'stock_description', 'qty','category_id','reference'],
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
		fields: ['serialise_id', 'model', 'lot_no', 'chasis_no', 'standard_cost','color', 'item_description', 'stock_description', 'qty','category_id', 'serialised','type_out', 'transno_out', 'reference', 'tran_date'],
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

	var columnItemSerialView = [
		{header:'id', dataIndex:'serialise_id', sortable:true, width:60,hidden: true},
		{header:'Model', dataIndex:'model', sortable:true, width:60, renderer: columnWrap,hidden: false},
		{header:'Item Description', dataIndex:'stock_description', sortable:true, renderer: columnWrap,hidden: false},
		{header:'Color', dataIndex:'item_description', sortable:true, renderer: columnWrap,hidden: false},
		{header:'Category', dataIndex:'category_id', sortable:true, width:100, renderer: columnWrap,hidden: true},
		{header:'Standard<br/>Cost', dataIndex:'standard_cost', sortable:true, width:70, hidden: true, align:'right'},
		{header:'Qty', dataIndex:'qty', sortable:true, width:40, hidden: false, align:'center'},
		{header:'Engine No.', dataIndex:'lot_no', sortable:true, width:100,renderer: columnWrap, hidden: false},
		{header:'Chasis No.', dataIndex:'chasis_no', sortable:true, width:100,renderer: columnWrap, hidden: false},
		{header:'Action',xtype:'actioncolumn', align:'center', width:40, hidden: true}
	]


	var coalistingStore = Ext.create('Ext.data.Store', {
		fields: ['account_code', 'account_name', 'name',{name:'class_id',type:'int'},'class_name'],
		autoLoad: true,
		proxy : {
			type: 'ajax',
			url	: '?action=coa',
			reader:{
				type : 'json',
				root : 'result',
				totalProperty : 'total'
			}
		},
		sorters: {property: 'class_id', direction: 'ASC'},
		groupField: 'class_name'
	});
	
	var columncoalisting = [
		{header:'Account Code', dataIndex:'account_code', sortable:true, width:20,hidden: false},
		{header:'class id', dataIndex:'class_id', sortable:true, width:100,hidden: true},
		{header:'Class Name', dataIndex:'class_name', sortable:true, width:80,hidden: false},
		{header:'Category', dataIndex:'name', sortable:true, width:80,hidden: false},
		{header:'Account Name', dataIndex:'account_name', sortable:true, renderer: columnWrap,hidden: false},
		{header:'Action',xtype:'actioncolumn', align:'center', width:20, hidden: false,
			items:[
				{
					icon: '../js/ext4/examples/shared/icons/accept.png',
					tooltip: 'Accept',
					handler: function(grid, rowIndex, colIndex){
						var record = coalistingStore.getAt(rowIndex);
						var account_code = record.get('account_code');	
						Ext.Ajax.request({
							url : '?action=AddGLItem&account_code='+account_code,
							method: 'POST',
							success: function (response){
								GLItemsStore.load();
								GetTotalBalance();
							},
							failure: function (response){
							}
						});
					}
				}	
			]
		}
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
		//selModel: {selType: 'cellmodel'},
		plugins: [cellEditing1],
		//padding: '5px',
		border: false,
		frame:false,
		viewConfig:{
			stripeRows: true,
			listeners: {
            	refresh: function(view) {
					GetTotalBalance();
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
					var mheader = Ext.getCmp('masterfile_header').getValue();
					if(mheader==null){
						Ext.Msg.alert('Warning','Please select masterfile');
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
							width: 990,
							height:450,
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
										//selModel: {selType: 'cellmodel'},
										selModel: {
											selType: 'checkboxmodel',
											id: 'checkidbox',
											checkOnly: true,
											mode: 'Single'			
										},			
										plugins: [cellEditing1],
										id:'ItemSerialListing',
										store: ItemListingStore,
										columns: [
											{header:'id', dataIndex:'serialise_id', sortable:true, width:60,hidden: true},
											{header:'Serialise', dataIndex:'serialised', sortable:true, width:30, renderer: columnWrap,hidden: true},
											{header:'Type', dataIndex:'type_out', sortable:true, width:30, renderer: columnWrap,hidden: true},
											{header:'Transno', dataIndex:'transno_out', sortable:true, width:30, renderer: columnWrap,hidden: true},
											{header:'Reference', dataIndex:'reference', sortable:true, width:80, renderer: columnWrap,hidden: false},
											{header:'RR Date', dataIndex:'tran_date', sortable:true, width:50, renderer: columnWrap,hidden: false, renderer: Ext.util.Format.dateRenderer('m/d/Y')
											},
											{header:'Model', dataIndex:'model', sortable:true, width:60, renderer: columnWrap,hidden: false},
											{header:'Item Description', dataIndex:'stock_description', sortable:true, renderer: columnWrap,hidden: false},
											{header:'Color', dataIndex:'item_description', sortable:true, renderer: columnWrap,hidden: false},
											{header:'Category', dataIndex:'category_id', sortable:true, width:100, renderer: columnWrap,hidden: true},
											{header:'Standard<br/>Cost', dataIndex:'standard_cost', sortable:true, width:70, hidden: true, align:'right'},
											{header:'Qty', dataIndex:'qty', sortable:true, width:40, hidden: false, align:'center',
												editor:{
													field:{
														xtype:'numberfield'
													}
												}
											},
											{header:'Engine No.', dataIndex:'lot_no', sortable:true, width:100,renderer: columnWrap, hidden: false},
											{header:'Chasis No.', dataIndex:'chasis_no', sortable:true, width:100,renderer: columnWrap, hidden: false}
											/*{header:'Action',xtype:'actioncolumn', align:'center', width:40, hidden: false,
												items:[
													{
														icon: '../js/ext4/examples/shared/icons/accept.png',
														tooltip: 'Accept',
														handler: function(grid, rowIndex, colIndex){
															var rec = grid.getStore().getAt(rowIndex);
															
															var record = ItemListingStore.getAt(rowIndex);
															var serialise_id = record.get('serialise_id');	
															var model = record.get('model');	
															var sdescription = record.get('stock_description');	
															var color = record.get('item_description');	
															var category = record.get('category_id');	
															var qty = rec.get('qty');	
															var rr_date = rec.get('tran_date');	
															var lot_no = record.get('lot_no');	
															var chasis_no = record.get('chasis_no');	
															var AdjDate = Ext.getCmp('AdjDate').getValue();	
															var type_out = record.get('type_out');	
															var transno_out = record.get('transno_out');	
															var standard_cost = record.get('standard_cost');	
															var serialised = record.get('serialised');	
									
															//MerchandiseTransStore.proxy.extraParams = {action:'AddItem',serialise_id: serialise_id, AdjDate:AdjDate, model:model, sdescription:sdescription, color:color, category:category, qty:qty, lot_no:lot_no, chasis_no:chasis_no}
															MerchandiseTransStore.proxy.extraParams={
																serialise_id: serialise_id, 
																AdjDate: AdjDate, 
																model:model, 
																sdescription:sdescription, 
																color:color, 
																category:category, 
																qty:qty, 
																lot_no:lot_no, 
																chasis_no:chasis_no, 
																type_out:type_out, 
																transno_out:transno_out,
																standard_cost:standard_cost,
																serialised:serialised,
																rr_date: rr_date
															};
															MerchandiseTransStore.load();
															//GLItemsStore.proxy.extraParams={};
															GLItemsStore.load();
															//GetTotalBalance();
															
															//windowItemList.close();
															
															
														}
													}
												]
											}*/
										],
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
													specialkey: function(f,e){							
														if (e.getKey() == e.ENTER) {
															

															var catcode = Ext.getCmp('category').getValue();
															var brcode = Ext.getCmp('fromlocation').getValue();
															ItemListingStore.proxy.extraParams = { 
																query:this.getValue(), 
																catcode: catcode,
																branchcode: brcode
															}
															ItemListingStore.load();	
																
								
														}
													}
													/*specialkey: function(f,e){							
														if (e.getKey() == e.ENTER) {
															
															var class_typess = Ext.getCmp('searchSerialItem').getValue();
															ItemListingStore.proxy.extraParams = { 
																querystr:this.getValue(), 
																class_typess: class_typess
															}
															ItemListingStore.load();								
														}
													}*/								
												}
											},{
												iconCls:'clear-search'
												/*},{
													xtype:'textfield',
													name:'searchSerial',
													id:'searchSerial',
													fieldLabel:'Serial/Engine No.',
													labelWidth: 120,
													listeners: {
														specialkey: function(f,e){							
															if (e.getKey() == e.ENTER) {
																
																var class_types = Ext.getCmp('searchSerial').getValue();
																ItemListingStore.proxy.extraParams = { 
																	querystr:this.getValue(), 
																	class_types: class_types
																}
																ItemListingStore.load();								
														}
													}		
												}*/
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
							buttons:[{

									/*------Robert Added-02/28/2022------*/
									text:'Add Item',
									disabled: false,
									id:'btnAddItem',			
									handler: function(grid, rowIndex, colIndex) {	
												
										var grid = Ext.getCmp('ItemSerialListing');
										var rec = grid.getSelectionModel().getSelection()[0];
										var record = grid.getSelectionModel().getSelection()[0];

										var serialise_id = record.get('serialise_id');	
										var model = record.get('model');	
										var sdescription = record.get('stock_description');	
										var color = record.get('item_description');	
										var category = record.get('category_id');	
										var qty = rec.get('qty');	
										var rr_date = rec.get('tran_date');	
										var lot_no = record.get('lot_no');	
										var chasis_no = record.get('chasis_no');	
										var AdjDate = Ext.getCmp('AdjDate').getValue();	
										var type_out = record.get('type_out');	
										var transno_out = record.get('transno_out');	
										var standard_cost = record.get('standard_cost');	
										var serialised = record.get('serialised');	

										MerchandiseTransStore.proxy.extraParams={
											serialise_id: serialise_id, 
											AdjDate: AdjDate, 
											model:model, 
											sdescription:sdescription, 
											color:color, 
											category:category, 
											qty:qty, 
											lot_no:lot_no, 
											chasis_no:chasis_no, 
											type_out:type_out, 
											transno_out:transno_out,
											standard_cost:standard_cost,
											serialised:serialised,
											rr_date: rr_date
										};
										MerchandiseTransStore.load({
											scope: this,
											callback: function(records, operation, success){
												var countrec = MerchandiseTransStore.getCount();
												if(countrec>0){
													setButtonDisabled(false);
												}else{
													setButtonDisabled(true);
												}
										
												Ext.toast({
													icon   	: '../js/ext4/examples/shared/icons/accept.png',
												    html: '<b>' + 'Model:' + record.get('model') + ' <br><br/> ' + 'Serial #:' + record.get('lot_no') + '<b/>',
												    title: 'Selected Item',
												    width: 250,
												    bodyPadding: 10,
												    align: 'tr'
												});										
											}	
										});
										
										GLItemsStore.load();
										ItemListingStore.load();
										/*---------End Here---------*/	
									}		
								},{

									text:'Close',
									iconCls:'cancel-col',
									handler: function(){
										//GetTotalBalance();
										windowItemList.close();
									}
								}
							],
							listeners:{
				                 'close':function(win){
					                  GLItemsStore.load();
			                          //GetTotalBalance();
				                  },
				                 'hide':function(win){
									  GLItemsStore.load();
			                          //GetTotalBalance();
				                  }
					
					        }
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
		}]
	}

	var gridJE = {
		xtype:'grid',
		id:'mtgridje',
		anchor:'100%',
		forceFit: true,
		store: GLItemsStore,
		columns: columnJEModel,
		//plugins: [rowEditing],
		selModel: {selType: 'cellmodel'},
		plugins: [cellEditing],
		border: false,
		frame:false,
		viewConfig:{
			stripeRows: true,
			listeners: {
            	refresh: function(view) {
					GetTotalBalance();
				}
        	}
		},
		dockedItems:[{
			dock	: 'top',
			xtype	: 'toolbar',
			name 	: 'newMTsearch',
			items:[
				{
				icon   	: '../js/ext4/examples/shared/icons/fam/add.gif',
				tooltip	: 'New Entry',
				text 	: 'New Entry',
				handler: function(){
					var mheader = Ext.getCmp('masterfile_header').getValue();
					if(mheader==null){
						Ext.Msg.alert('Warning','Please select masterfile');
						return false;	
					}
					
					if(!windowItemListje){
						//var catcode = Ext.getCmp('category').getValue();
						//var brcode = Ext.getCmp('fromlocation').getValue();
						//coalistingStore.proxy.extraParams = {catcode: catcode, branchcode:brcode}
						//coalistingStore.load();	
							
						var windowItemListje = Ext.create('Ext.Window',{
							title:'Chart of Account',
							id:'windowItemListje',
							modal: true,
							width: 900,
							height:450,
							bodyPadding: 5,
							layout:'fit',
							closeAction:'destroy',
							items:[
								{
									xtype:'panel',
									autoScroll: true,
									frame:false,
									items:[{
										xtype:'grid',
										forceFit: true,
										layout:'fit',
										id:'coalisting',
										selModel: {
											selType: 'checkboxmodel',
											id: 'checkidbox',
											checkOnly: true,
											mode: 'Single'			
										},	
										store: coalistingStore,
										columns: [
											{header:'Account Code', dataIndex:'account_code', sortable:true, width:20,hidden: false},
											{header:'class id', dataIndex:'class_id', sortable:true, width:100,hidden: true},
											{header:'Class Name', dataIndex:'class_name', sortable:true, width:80,hidden: false},
											{header:'Category', dataIndex:'name', sortable:true, width:80,hidden: false},
											{header:'Account Name', dataIndex:'account_name', sortable:true, renderer: columnWrap,hidden: false}
											/*{header:'Action',xtype:'actioncolumn', align:'center', width:20, hidden: false,
												items:[
													{
														icon: '../js/ext4/examples/shared/icons/accept.png',
														tooltip: 'Accept',
														handler: function(grid, rowIndex, colIndex){
															var record = coalistingStore.getAt(rowIndex);
															var account_code = record.get('account_code');	
															var AdjDate = Ext.getCmp('AdjDate').getValue();	
											
															Ext.Ajax.request({
																url : '?action=AddGLItem&account_code='+account_code,
																method: 'POST',
																params:{
																	AdjDate:AdjDate
																},
																success: function (response){
																	GLItemsStore.load();
																	GetTotalBalance();
																	windowItemListje.close();
																	windowItemListje=null;
																},
																failure: function (response){
																}
															});
														}
													}	
												]
											}*/
										],
										dockedItems:[{
											dock:'top',
											xtype:'toolbar',
											name:'searchSerialBar',
											items:[{
												xtype:'combo',
										    	hidden: true,
										    	fieldLabel:'Group',
												labelWidth: 60,
										    	name:'search_coagroup',
										    	id:'search_coagroup',
										    	queryMode: 'local',
										    	triggerAction : 'all',
										    	displayField  : 'name',
										    	valueField    : 'id',
										    	editable      : true,
										    	forceSelection: false,
										    	allowBlank: true,
										    	required: false,
										    	hiddenName: 'id',
										    	typeAhead: true,
										    	selectOnFocus:true,
										    	//layout:'anchor',
										    	store: Ext.create('Ext.data.Store',{
										    		fields:['id','name','class_id'],
										    		autoLoad: true,
										    		proxy: {
										    			type:'ajax',
														method:'POST',
										    			url: '?action=coa_classtype',
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
										  				coalistingStore.proxy.extraParams = { class_type: v }
										  				coalistingStore.load();
										  			}
										  		}
											},{
												width	: 300,
												xtype	: 'textfield',
												name 	: 'searchcoa',
												id		:'searchcoa',
												fieldLabel: 'Account Name',
												labelWidth: 120,
												listeners : {
													specialkey: function(f,e){							
														if (e.getKey() == e.ENTER) {
															
															var class_type = Ext.getCmp('search_coagroup').getValue();
															coalistingStore.proxy.extraParams = { 
																description:this.getValue(), 
																class_type: class_type
															}
															coalistingStore.load();								
														}
													}						
												}
											},{
												iconCls:'clear-search',
												handler: function(){
													var class_type = Ext.getCmp('search_coagroup').getValue();
														coalistingStore.proxy.extraParams = { 
															description:Ext.getCmp('searchcoa').getValue(), 
															class_type: class_type
													}
													coalistingStore.load();			
												}
											}]
										}],
										features: [{
								            id: 'group',
								            ftype: 'groupingsummary',
								            groupHeaderTpl: '{name}',
								            hideGroupedHeader: true,
								            enableGroupingMenu: false
								        }]
										/*,
										bbar : {
											xtype : 'pagingtoolbar',
											store : coalistingStore,
											displayInfo : true
										}*/
									}]
								}
							],
							buttons:[{
									/*------Robert Added-02/28/2022------*/
									text:'Add Item',
									disabled: false,
									id:'btnAddItem',			
									handler: function(grid, rowIndex, colIndex) {	
												
										var grid = Ext.getCmp('coalisting');
										var record = grid.getSelectionModel().getSelection()[0];

										//var record = coalistingStore.getAt(rowIndex);
										var account_code = record.get('account_code');	
										var AdjDate = Ext.getCmp('AdjDate').getValue();	
										var account_name = record.get('account_name');	

						
										Ext.Ajax.request({
											url : '?action=AddGLItem&account_code='+account_code,
											method: 'POST',
											params:{
												AdjDate:AdjDate											
											},
											success: function (response){
												GLItemsStore.load();
												GetTotalBalance();
												//windowItemListje.close();
												//windowItemListje=null;

												Ext.toast({
													icon   	: '../js/ext4/examples/shared/icons/accept.png',
												    html: '<b>' + 'Accoun Code: <br>' + record.get('account_code') + '<br><br/>' + 'Account Name: <br>' + record.get('account_name') + '<b/>',
												    title: 'Selected Item',
												    width: 250,
												    bodyPadding: 10,
												    align: 'tr'
												});		
											},
											failure: function (response){
											}
										});

										coalistingStore.load();
										/*---------End Here---------*/	
									}
								},{		
									text:'Close',
									iconCls:'cancel-col',
									handler: function(){
										GetTotalBalance();
										windowItemListje.close();
										windowItemListje=null;
									}
								}
							]
						});	
					}						
					
					
					windowItemListje.show();
				}	
			}
			]	
		}]
	}

				
	var grid = Ext.create('Ext.grid.Panel', {
		renderTo: 'merchandisetransfer-grid',
		layout: 'fit',
		//height	: 550,
		title	: 'Complimentary Items Module',
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
					labelWidth: 50,
					hidden: true
				},Supplier_Filter,{
					icon   	: '../js/ext4/examples/shared/icons/fam/add.gif',
					tooltip	: 'New Transaction',
					text 	: 'New Transaction',
					hidden: false,
					handler : function(){
						if(!windowNewTransfer){
							var windowNewTransfer = Ext.create('Ext.Window',{
								title:'Complimentary Items Entry',
								modal: true,
								width: 1100,
								//height:500,
								bodyPadding: 5,
								layout:'anchor',
								items:[
									{
										xtype:'fieldset',
										//title:'Complimentary Header',
										layout:'anchor',
										defaultType:'textfield',
										fieldDefaults:{
											labelAlign:'right'
										},
										items:[
											{
												xtype:'fieldcontainer',
												layout:'hbox',
												margin: '2 0 2 5',
												items:[
													{
														xtype:'combobox',
														fieldLabel:'Location From',
														name:'fromlocation',
														id:'fromlocation',
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
                                                        labelWidth: 80,
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
                                                        labelWidth: 80,
														name:'trans_date',
														id:'AdjDate',
														listeners:{
															change: function(){
																MerchandiseTransStore.load({
																	params:{view:1}
																});
															}
														}
													},{
														xtype:'textfield',
														fieldLabel:'Reference #',
                                                        labelWidth: 90,
														name:'reference',
														id:'reference',
														layout:'anchor',
														anchor:'100%'
														//flex:1
													}
												]
											},{
												xtype:'fieldcontainer',
												layout:'hbox',
												margin: '2 0 2 5',
												items:[{
													xtype: 'combobox',
													fieldLabel:'Sent To',
													name:'masterfile_type_header',
								                    id:'masterfile_type_header',
								                    typeAhead: true,
										            triggerAction: 'all',
										            store: MasterfileTypeStore,
													displayField  : 'namecaption',
													valueField    : 'id',
													editable      : false,
													forceSelection: true,
													required: true,
													hiddenName: 'id',
													listeners: {
														select: function(cmb, rec, idx) {
															MasterfileModel=Ext.getCmp('masterfile_header');
									                        MasterfileModel.clearValue();
															if( this.getValue()===99){
																MasterfileModel.store.load();
															}else{
																MasterfileModel.store.load({
																	params: { 'masterfile_type': this.getValue()
																	 }
																});
															}
									
									                        MasterfileModel.enable();
									
														}
													}
												},
												{
			                                        xtype:'combo',
			                                        fieldLabel:'Masterfile',
			                                        name:'masterfile_header',
													id:'masterfile_header',
			                                        anchor:'100%',
			                                        typeAhead: true,
                                                    labelWidth: 80,
                                                    width: 775,
										            triggerAction: 'all',
										            store: MasterfileStore,
													queryMode: 'local',
													displayField  : 'namecaption',
													valueField    : 'id',
													hiddenName: 'id',
													//flex:1,
													listeners:{
														change: function(combo, value,index){
															return decodeHtmlEntity(combo.getRawValue());
														}
													} 
														
			                                    }
											]
											},{
												xtype:'fieldcontainer',
												margin: '2 0 2 5',
												width: 1051,
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
										xtype:'tabpanel',
										items:[
											{
												xtype:'panel',
												title:'Items',
												frame: true,
												padding:'5px',
												border: false,
												anchor:'100%',
												layout:'fit',
												id:'tabItemEntry',
												items:[gridMT]
											},
											{
												xtype:'panel',
												title:'Journal Entry',
												frame: true,
												padding:'5px',
												border:false,
												anchor:'100%',
												layout:'fit',
												id:'tabJEEntry',
												items:[gridJE]
											}
										],
										listeners: {
								            'tabchange': function (tabPanel, tab) {
								                //console.log(tabPanel.id + ' ' + tab.id);
												if(tab.id=='tabJEEntry'){
													GLItemsStore.load();
												}
												if(tab.id=='tabItemEntry'){
													MerchandiseTransStore.load({
														params:{view:1}
													});
												}
								            }
								        }
										
									},
									{
										xtype:'fieldcontainer',
										layout:'hbox',
										items:[
											{
												xtype:'textfield',
												fieldLabel:'DEBIT:',
												readOnly: true,
												fieldStyle: 'font-weight: bold; color: #003168;text-align: right;',
												id:'totaldebit'
											},
											{
												xtype:'textfield',
												fieldLabel:'CREDIT:',
												readOnly: true,
												fieldStyle: 'font-weight: bold; color: #003168;text-align: right;',
												id:'totalcredit'
											}
										]
									}
									
								],
								buttons:[
									{
										text:'Process',
										id:'btnProcess',
										disabled: true,
										handler:function(){
											var AdjDate = Ext.getCmp('AdjDate').getValue();	
											var catcode = Ext.getCmp('category').getValue();
											var FromStockLocation = Ext.getCmp('fromlocation').getValue();
											var memo_ = Ext.getCmp('memo').getValue();
											var totaldebit = Ext.getCmp('totaldebit').getValue();
											var totalcredit = Ext.getCmp('totalcredit').getValue();
											var reference = Ext.getCmp('reference').getValue();
											var person_id = Ext.getCmp('masterfile_header').getValue();
											var masterfile = Ext.getCmp('masterfile_header').getRawValue();
											
											var person_type = Ext.getCmp('masterfile_type_header').getValue();
											Ext.MessageBox.show({
												msg: 'Saving Date, please wait...',
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
													memo_: memo_,
													totaldebit:totaldebit,
													totalcredit: totalcredit,
													ref: reference,
													person_type: person_type,
													person_id: person_id,
													masterfile: masterfile
												},
												success: function (response){
													var jsonData = Ext.JSON.decode(response.responseText);
													if(jsonData.success==true){
														var AdjDate = jsonData.AdjDate;
														//Ext.getCmp('AdjDate').setValue(AdjDate);
														myInsurance.load();
														windowNewTransfer.close();
														Ext.MessageBox.alert('Success','Success Processing');
													}else{
														var ErrorMsg = jsonData.errmsg;
														Ext.MessageBox.alert('Error Processing',ErrorMsg);
													} 
													
													
													//MerchandiseTransStore.proxy.extraParams = {action: 'AddItem'}
													//MerchandiseTransStore.load();
												},
												failure: function (response){
													//Ext.MessageBox.hide();
													//var jsonData = Ext.JSON.decode(response.responseText);
													Ext.MessageBox.alert('Error','Error Processing');
												}
											});
											Ext.MessageBox.hide();
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
						var AdjDate = Ext.getCmp('AdjDate').getValue();	
						var FromStockLocation = Ext.getCmp('fromlocation').getValue();
											
						Ext.Ajax.request({
							url : '?action=NewTransfer',
							method: 'POST',
							params:{
								AdjDate:AdjDate,
								FromStockLocation:FromStockLocation
							},
							success: function (response){
								var jsonData = Ext.JSON.decode(response.responseText);
								var AdjDate = jsonData.AdjDate;
								var reference = jsonData.reference;
								Ext.getCmp('AdjDate').setValue(AdjDate);
								Ext.getCmp('reference').setValue(reference);
								
								MerchandiseTransStore.proxy.extraParams = {action: 'AddItem'}
								MerchandiseTransStore.load();
								GLItemsStore.load();
								GetTotalBalance();
						
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
			//var GridTitle = Ext.getCmp('grid').getTitle();
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
});

function GetTotalBalance(){
	Ext.Ajax.request({
		url : '?action=getTotalBalance',
		method: 'POST',
		success: function(response){
			var jsonData = Ext.JSON.decode(response.responseText);
			var DebitTotal = jsonData.TotalDebit;
			var CreditTotal = jsonData.TotalCredit;
			Ext.getCmp('totaldebit').setValue(DebitTotal);
			Ext.getCmp('totalcredit').setValue(CreditTotal);
			if(DebitTotal==CreditTotal && (DebitTotal!=0 || CreditTotal!=0)){
				setButtonDisabled(false);
			}else{
				setButtonDisabled(true);
			}
		}
	});	
	return true;
}

function setButtonDisabled(valpass=false){
	Ext.getCmp('btnProcess').setDisabled(valpass);
}
function decodeHtmlEntity(str) {
	return str.replace(/&#(\d+);/g, function(match, dec) {
		return String.fromCharCode(dec);
	});
};
