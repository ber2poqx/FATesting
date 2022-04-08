var newURL = window.location.protocol + "//" + window.location.host + window.location.pathname;
Ext.Loader.setConfig({enabled: true});
Ext.Loader.setPath('Ext.ux', '../js/ext4/examples/ux/');
Ext.require(['Ext.toolbar.Paging',
    'Ext.ux.form.SearchField',
	'Ext.layout.container.Column',
    'Ext.tab.*',
	'Ext.window.MessageBox',
	'Ext.selection.CheckboxModel',
	'Ext.selection.CellModel',	
	'Ext.grid.*']);


Ext.onReady(function(){
	//Ext.QuickTips.init();
	var global_master_id, branchcode;

	var smCheckitem = Ext.create('Ext.selection.CheckboxModel',{
		mode: 'MULTI'
	});
	var cellEditing = Ext.create('Ext.grid.plugin.CellEditing',{
        clicksToEdit: 1
    });

	var GroupTypeStore = new Ext.create('Ext.data.Store',{
		fields 	: 	['id','groupname'],
		data 	: 	[
            //{"id":"0","groupname":"BLANK"},
            {"id":"1","groupname":"DES, Inc"},
            {'id':'2','groupname':'DES Marketing'}
        ],
        autoLoad:true
	});

	
	var ItemListingStore = Ext.create('Ext.data.Store', {
		fields: ['serialise_id', 'model', 'lot_no', 'chasis_no', 'standard_cost','color', 'item_description', 'stock_description', 'qty','category_id', 'serialised','type_out', 'transno_out', 'reference', 'tran_date','brand_id','brand_name', 'brand_id'],
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
	
 	Ext.define('insurance', {
		extend : 'Ext.data.Model',
		fields  : [
			{name:'id',mapping:'trans_id'},
			{name:'reference',mapping:'reference'},
			{name:'rrbrreference',mapping:'rrbrreference'},
			{name:'tolocation',mapping:'tolocation'},
			{name:'category',mapping:'category'},
			{name:'category_id',mapping:'category_id'},
			{name:'fromlocation',mapping:'fromlocation'},
			{name:'from_loc',mapping:'from_loc'},
			{name:'trans_date',mapping:'trans_date'},
			{name:'total_qty',mapping:'qty'},
			{name:'status_msg',mapping:'status_msg'},
			{name:'remarks',mapping:'remarks'},
			{name:'status',mapping:'status'},
			{name:'delivery_date',mapping:'delivery_date'}
		]
	});

	
	var columnModel =[
		{header:'ID', dataIndex:'id', sortable:true, width:20,hidden: true},
		{header:'MT Ref#', dataIndex:'reference', sortable:true, width:70, hidden: false},
		{header:'RR Ref#', dataIndex:'rrbrreference', sortable:true, width:80, hidden: false},
		{header:'Trans Date', dataIndex:'trans_date', sortable:true, width:60, align:'center'},
		{header:'From Location Code', dataIndex:'from_loc', sortable:true, width:90, hidden: true},
		{header:'From Location', dataIndex:'fromlocation', sortable:true, width:90},
		{header:'To Location', dataIndex:'tolocation', sortable:true, width:90, hidden: true},
		{header:'Category', dataIndex:'category', sortable:true, width:50},
		{header:'Total Items', dataIndex:'total_qty', sortable:true, width:50, align:'center'},
		{header:'Remarks', dataIndex:'remarks', sortable:true, align:'left', renderer: columnWrap},
		{header:'Status', dataIndex:'status_msg', sortable:true, width:40},
		{header	: 'Action',	xtype:'actioncolumn', align:'center', width:40,
			items:[
				{
					icon: '../js/ext4/examples/shared/icons/application_view_columns.png',
					tooltip: 'Serial Items Detail',
					handler: function(grid, rowIndex, colIndex) {
						var record = myInsurance.getAt(rowIndex);
						id = record.get('id');
                        reference = record.get('reference');
                        brcode = record.get('loc_code');
                        to_loc = record.get('tolocation');
                        from_loc = record.get('fromlocation');
                        from_loc_code = record.get('from_loc');
                        catcode = record.get('category_id');
                        category = record.get('category');
						rrbrreference = record.get('rrbrreference');
						//var RRBRReference;			
                        
						if(!windowItemSerialList){
							MTItemListingStore.proxy.extraParams = {catcode: catcode, branchcode:brcode,reference:reference, trans_id:id}
							MTItemListingStore.load({
								scope: this,
								callback: function(records, operation, success){
									//var countrec = MerchandiseTransStore.getCount();
									if(rrbrreference==null){
										setRcvdButtonDisabled(false);
									}else{
										setRcvdButtonDisabled(true);
										
									}
								}
							});	

							Ext.Ajax.request({
								url : '?action=receive_header',
								method: 'POST',
								params : {
  									from_loc : from_loc,
									MTreference: reference,
									trans_id: id,
									from_loc_code: from_loc_code
  								},
								success: function (response){
									var jsonData = Ext.JSON.decode(response.responseText);
									//branchcode = jsonData.branchcode;
									transdate = jsonData.AdjDate;
									RRBRReference = jsonData.RRBRReference;
									MTreference = jsonData.MTreference;
									Ext.getCmp('RRBranchReference').setValue(reference);
									Ext.getCmp('AdjDate').setValue(transdate);
									Ext.getCmp('RRBRCategory').setValue(category);
						
									//Ext.MessageBox.alert('Success!',"Process complete"+branchcode);
									//window.open('?action=downloadfile&pathfile='+pathfile);
								},
								failure: function (response){
									//var jsonData = Ext.JSON.decode(response.responseText);
									Ext.MessageBox.alert('Error','Error Processing');
								}
							});


							// Selection model
							var selModel1 = Ext.create('Ext.selection.CheckboxModel', {
							    columns: [
							        {xtype : 'checkcolumn', text : 'Active', dataIndex : 'id',width:10}
							        ],
							    checkOnly: true,
							    mode: 'multi',
							    enableKeyNav: false,
								listeners: {
							        selectionchange: function(value, meta, record, row, rowIndex, colIndex){
							            var selectedRecords = grid.getSelectionModel().getSelection();
							            var selectedParams = [];
							
							            // Clear input and reset vars
							            //$('#selected-libraries').empty();
							            var record = null;
							            var status = null;
							            var isPrimary = null;
							
							            // Loop through selected records
							            for(var i = 0, len = selectedRecords.length; i < len; i++){
							                record = selectedRecords[i];
							
							                // Is full library checked?
							                status = record.get('status');
							
							                // Is this primary library?
							                isPrimary = record.get('isPrimary');
							
							                // Build data object
							                selectedParams.push({
							                    id: record.getId(),
							                    status: status,
							                    primary: isPrimary
							                });
							            }
							            // JSON encode object and set hidden input
							            //$('#selected-libraries').val(JSON.stringify(selectedParams));
										//alert(JSON.stringify(selectedParams));
								}}
							});

							var grid1 = Ext.create('Ext.grid.Panel',{
									xtype:'grid',
									forceFit: true,
									layout:'fit',
									//selModel:selModel1,
									selModel: {
										selType: 'checkboxmodel',
										id: 'checkidbox',
										checkOnly: true,
										mode: 'Multi'			
									},	
									plugins: {
								        ptype: 'cellediting',
								        clicksToEdit: 1
								    },
									id:'ItemSerialListingView',
									store: MTItemListingStore,
									columns: columnItemSerial,
									//selModel: 'cellmodel',
								    //plugins: [cellEditing],
									dockedItems:[{
										dock:'top',
										xtype:'toolbar',
										name:'searchSerialBar',
										hidden: false,
										items:[{
											xtype:'textfield',
											fieldLabel:'MT Reference',
											id:'RRBranchReference',
											readOnly: true,
											//disabled: true,
											fieldStyle: 'font-weight: bold; color: #003168;'
										},{
											xtype:'textfield',
											id:'RRBRCategory',
											fieldLabel:'Category',
											readOnly: true,
											//disabled: true,
											fieldStyle: 'font-weight: bold; color: #003168;'
										},{
												xtype:'datefield',
												fieldLabel:'Received Date',
												name:'trans_date',
												id:'AdjDate',/*,
												value: new Date()*/
												readOnly: true
										},
										{
											xtype:'textfield',
											fieldLabel:'FROM Location Code',
											id:'from_loc_code',
											readOnly: true,
											//disabled: true,
											fieldStyle: 'font-weight: bold; color: #003168;',
											value: from_loc_code,
											hidden: true
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
									},
									viewConfig: {
										getRowClass: function (record, rowIndex) {
									      	var pfix = Ext.baseCSSPrefix;
										  	var disabledClass =  pfix + 'item-disabled ' + pfix + 'btn-disabled ' + pfix + 'btn-plain-toolbar-small';
											return record.get('status') === '2' ? disabledClass : '';
									    }
									}
								//}]								
							});	
							
							var windowItemSerialList = Ext.create('Ext.Window',{
								title:'Item Listing - Merchandise Transfer',
								id:'windowItemSSerialList',
								modal: true,
								width: 950,
								height:500,
								bodyPadding: 5,
								layout:'fit',
								items:[grid1],
								buttons:[{
										text:'Process',
										iconCls:'new',
										id:'btnProcess',
										handler: function(grid, rowIndex, colIndex) {	
										
											var grid = Ext.getCmp('ItemSerialListingView');
											var selected = grid.getSelectionModel().getSelection();
											var gridRepoData = [];
											count = 0;
											Ext.each(selected, function(record) {
												var ObjItem = {
													RRBRReference: Ext.getCmp('RRBranchReference').getValue(),
													from_loc_code: Ext.getCmp('from_loc_code').getValue(),
													from_loc_code: Ext.getCmp('from_loc_code').getValue(),
													trans_date: Ext.Date.format(Ext.getCmp('AdjDate').getValue(),"Y-m-d"),
													MTreference: record.get('reference'),	
													catcode: record.get('category_id'),

													rrbrreference: record.get('rrbrreference'),

													line_item: record.get('line_item'),
													model: record.get('model'),	
													qty: record.get('qty'),	
													currentqty: record.get('currentqty'),
													receivedqty: record.get('receivedqty'),
													lot_no: record.get('lot_no'),
													chasis_no: record.get('chasis_no'),
													item_code: record.get('item_code'),
													standard_cost: record.get('standard_cost')
												};
												gridRepoData.push(ObjItem);
											});

											//trans_dates: Ext.Date.format(Ext.getCmp('AdjDate').getValue(),"Y-m-d"),

											//Ext.Date.format(Ext.getCmp('AdjDate').getValue(),"Y-m-d"),
											
											Ext.MessageBox.confirm('Confirm', 'Do you want to Process?', function (btn, text) {
												if (btn == 'yes') {
													//Ext.getCmp('btnProcess').setDisabled(true);
													Ext.Ajax.request({
														url : '?action=save_rrbr',
														method: 'POST',
														params:{DataOnGrid: Ext.encode(gridRepoData), 
														trans_dates: Ext.Date.format(Ext.getCmp('AdjDate').getValue(),"Y-m-d")},
														/*success: function (response){
															
															myInsurance.load();
															windowItemSerialList.close();										
														},	
														failure: function (response){
															//Ext.getCmp('btnProcess').setDisabled(false);
													
															Ext.MessageBox.alert('Error', 'Processing ' + records.get('id'));
														}*/
														success: function(response){
															var jsonData = Ext.JSON.decode(response.responseText);
															var errmsg = jsonData.message;
															//Ext.getCmp('AdjDate').setValue(AdjDate);
															if(errmsg!=''){
																Ext.MessageBox.alert('Error',errmsg);
															}else{
																//MerchandiseTransStore.proxy.extraParams = {action: 'AddItem'}
																myInsurance.load();
																windowItemSerialList.close();										
																//Ext.MessageBox.alert('Success','Success Processing');
															}
														}
													});
												}
											});
											
										}
									},
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
						                       
                        //edit_insurance_win.show();
						//window.location.replace('serial_details.php?serialid='+id);
					}
				},{
					icon: '../js/ext4/examples/shared/icons/printer.png',
					handler: function(grid, rowIndex, colIndex) {
						var record = myInsurance.getAt(rowIndex);
						reference = record.get('rrbrreference');
						var win = new Ext.Window({
							autoLoad:{
								url:'../reports/rr_branch.php?reference='+reference,
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
				            src:'../reports/rr_branch.php?reference='+reference,
				            width:'100%',
				            height:'100%',
				            frameborder:0
				        }
						win.show();
						Ext.DomHelper.insertFirst(win.body, iframe)
						//window.open('../reports/merchandise_receipts.php?reference='+reference);
					},
	                getClass : function(value, meta, record, rowIx, ColIx, store) {
	                    // Determines at runtime whether to render the icon/link
						//record.get('rrbrreference')==null
						//record.data.rrbrreference
						if(record.get('rrbrreference') === null) {
                			return 'x-hidden-visibility';
            			}
	                    /*return (record.get('rrbrreference') === null) ?
	                            'x-grid-center-icon': //Show the action icon
	                            'x-hide-display';  //Hide the action icon*/
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
			url	: '?action=viewin',
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


	var Branches_Filter = Ext.create('Ext.form.ComboBox', {
    	xtype:'combo',
    	hidden: false,
    	fieldLabel:'From Branches',
		labelWidth: 100,
    	name:'from_location',
    	id:'from_location',
    	queryMode: 'local',
    	triggerAction : 'all',
    	displayField  : 'location_name',
    	valueField    : 'loc_code',
    	editable      : true,
    	forceSelection: false,
    	allowBlank: true,
    	required: false,
    	hiddenName: 'loc_code',
    	typeAhead: true,
    	selectOnFocus:true,
    	//layout:'anchor',
    	store: Ext.create('Ext.data.Store',{
    		fields:['loc_code','location_name'],
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
          listeners: {
  			select: function(cmb, rec, idx) {
  				var v = this.getValue();
                //var branch_combo = Ext.getCmp('branch_combo').getValue();
  				myInsurance.proxy.extraParams = { fromlocation: v }
  				myInsurance.load();
  			}
  		}

    });

	var Brand_Filter = Ext.create('Ext.form.ComboBox', 
		{
	    	xtype:'combo',
	    	hidden: false,
	    	fieldLabel:'Brand',
			labelWidth: 100,
	    	name:'brand',
	    	id:'brand',
	    	queryMode: 'local',
	    	triggerAction : 'all',
	    	displayField  : 'brand_name',
	    	valueField    : 'brand_id',
	    	editable      : true,
	    	forceSelection: false,
	    	allowBlank: true,
	    	required: false,
	    	hiddenName: 'loc_code',
	    	typeAhead: true,
	    	selectOnFocus:true,
	    	//layout:'anchor',
	    	store: Ext.create('Ext.data.Store',{
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
	    	}),
	          listeners: {
	  			select: function(cmb, rec, idx) {
	  				var v = this.getValue();
	                //var branch_combo = Ext.getCmp('branch_combo').getValue();
	  				//myInsurance.proxy.extraParams = { fromlocation: v }
	  				//myInsurance.load();
	  			}
	  		}
	
	    }
	);

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
	
	var MerchandiseTransStore = Ext.create('Ext.data.Store', {
	    storeId:'DetaiItemsTransferListStore',
		fields: ['stock_id','stock_description','item_description','trans_date','price','reference','currentqty','qty','standard_cost', 'lot_no', 'chasis_no', 'category_id','serialise_id','color','type_out','transno_out','rr_date','brand_name'],
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
				read:'?action=ManualAddItem',
				update:'?action=ManualupdateData'
			},
			writer:{
				type:'json',
				encode:true,
				rootProperty:'dataManualUpdate',
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
		{header:'#', dataIndex:'trans_id', sortable:true, width:70, align:'center', hidden: true},
		{header:'Trans No', dataIndex:'trans_no', sortable:true, width:100, hidden: true},
		{header:'Reference', dataIndex:'reference', sortable:true, width:100, hidden: true},
		{header:'Trans Date', dataIndex:'trans_date', sortable:true, width:100, hidden: true},
		{header:'Brand', dataIndex:'brand_name', sortable:true, width:100, renderer: columnWrap,hidden: false},
		{header:'Stock ID', dataIndex:'stock_id', sortable:true, width:180, renderer: columnWrap,hidden: false},
		{header:'Stock Description', dataIndex:'stock_description', sortable:true, width:280, renderer: columnWrap,hidden: false},
		{header:'Color', dataIndex:'color', sortable:true, width:180, renderer: columnWrap,hidden: false},
		{header:'Category', dataIndex:'category_id', sortable:true, width:180, renderer: columnWrap,hidden: true},
		{header:'Type', dataIndex:'type', sortable:true,width:100, hidden: true},
		{header:'Location', dataIndex:'loc_code', sortable:true,width:100, hidden: true},
		{header:'Price', dataIndex:'price', sortable:true, width:55, hidden: true},
		{header:'Qty', dataIndex:'qty', sortable:true, width:50, hidden: false},
		{header:'Standard Cost', dataIndex:'standard_cost', sortable:true, width:150,renderer: columnWrap, align:'right',hidden: false,
		renderer: Ext.util.Format.numberRenderer('0,000.00'),
			editor:{
				field:{
					xtype:'numberfield',
					name:'standard_cost',
					anchor:'100%'
				}
			}
		},
		{header:'Serial No.', dataIndex:'lot_no', sortable:true, width:150,renderer: columnWrap, hidden: false,
			editor:{
				field:{
					xtype:'textfield',
					name:'lot_no',
					anchor:'100%'
				}
			}
		},
		{header:'Chassis No.', dataIndex:'chasis_no', sortable:true, width:150,renderer: columnWrap, hidden: false,
			editor:{
				field:{
					xtype:'textfield',
					name:'chasis_no',
					anchor:'100%'
				}
			}
		},
		{header:'Action',xtype:'actioncolumn', align:'center', width:60,
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
								//console.log("count after load " + MerchandiseTransStore.getCount());
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
	var columnTransferModelNonSerial = [
		{header:'#', dataIndex:'trans_id', sortable:true, width:70, align:'center', hidden: true},
		{header:'Trans No', dataIndex:'trans_no', sortable:true, width:100, hidden: true},
		{header:'Reference', dataIndex:'reference', sortable:true, width:100, hidden: true},
		{header:'Trans Date', dataIndex:'trans_date', sortable:true, width:100, hidden: true},
		{header:'Brand', dataIndex:'brand_name', sortable:true, width:100, renderer: columnWrap,hidden: false},
		{header:'Stock ID', dataIndex:'stock_id', sortable:true, width:180, renderer: columnWrap,hidden: false},
		{header:'Stock Description', dataIndex:'stock_description', sortable:true, width:280, renderer: columnWrap,hidden: false},
		{header:'Category', dataIndex:'category_id', sortable:true, width:180, renderer: columnWrap,hidden: true},
		{header:'Type', dataIndex:'type', sortable:true,width:100, hidden: true},
		{header:'Location', dataIndex:'loc_code', sortable:true,width:100, hidden: true},
		{header:'Price', dataIndex:'price', sortable:true, width:55, hidden: true},
		{header:'Qty', dataIndex:'qty', sortable:true, width:50, hidden: false,
			editor:{
				field:{
					xtype:'numberfield',
					name:'qty',
					anchor:'100%'
				}
			}
		},
		{header:'Standard Cost', dataIndex:'standard_cost', sortable:true, width:150,renderer: columnWrap, align:'right', hidden: false,
		renderer: Ext.util.Format.numberRenderer('0,000.00'),
			editor:{
				field:{
					xtype:'numberfield',
					name:'standard_cost',
					anchor:'100%'
				}
			}
		},
		{header:'Serial No.', dataIndex:'lot_no', sortable:true, width:150,renderer: columnWrap, hidden: false,
			editor:{
				field:{
					xtype:'textfield',
					name:'lot_no',
					anchor:'100%'
				}
			}
		},
		{header:'Chassis No.', dataIndex:'chassis_no', sortable:true, width:150,renderer: columnWrap, hidden: true},
		{header:'Action',xtype:'actioncolumn', align:'center', width:60,
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
								//console.log("count after load " + MerchandiseTransStore.getCount());
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
		fields: ['trans_id', 'model', 'lot_no', 'chasis_no', 'color', 'item_description', 'stock_description', 'qty', 'currentqty', 'receivedqty', 'category_id','reference','rrbrreference','status','status_msg','item_code', 'standard_cost', 'line_item'],
		autoLoad: false,
		proxy : {
			type: 'ajax',
			url	: '?action=BRMTserialitems',
			reader:{
				type : 'json',
				root : 'result',
				totalProperty : 'total'
			}
		}
	});	
	
	
	var columnItemSerial = [
		{header:'id', dataIndex:'trans_id', sortable:true, width:60,hidden: true},
		{header:'Line Item', dataIndex:'line_item', sortable:true, width:50,hidden: true},
		{header:'reference', dataIndex:'reference', sortable:true, width:100,hidden: true},
		{header:'Model', dataIndex:'model', sortable:true, width:60, renderer: columnWrap,hidden: false},
		{header:'Item Code', dataIndex:'item_code', sortable:true, width:60, renderer: columnWrap,hidden: true},
		{header:'Item Description', dataIndex:'stock_description', sortable:true, renderer: columnWrap,hidden: false},
		{header:'Color', dataIndex:'item_description', sortable:true, renderer: columnWrap,hidden: false},
		{header:'Category', dataIndex:'category_id', sortable:true, width:100, renderer: columnWrap,hidden: true},
		{header:'Qty', dataIndex:'qty', sortable:true, width:50, hidden: false, align:'center',
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
		{header:'Standard Cost', dataIndex:'standard_cost', hidden:false, width:80, hidden: false},
		{header:'Engine No.', dataIndex:'lot_no', sortable:true, width:100,renderer: columnWrap, hidden: false},
		{header:'Chasis No.', dataIndex:'chasis_no', sortable:true, width:100,renderer: columnWrap, hidden: true},
		{header:'Status', dataIndex:'status_msg', sortable:true, width:50,renderer: columnWrap, hidden: false}
	]
	var columnItemNonSerial = [
		{header:'id', dataIndex:'trans_id', sortable:true, width:60,hidden: true},
		{header:'Line Item', dataIndex:'line_item', sortable:true, width:50,hidden: true},
		{header:'reference', dataIndex:'reference', sortable:true, width:100,hidden: true},
		{header:'Brand', dataIndex:'brand_name', sortable:true, width:60, renderer: columnWrap,hidden: false},
		{header:'Model', dataIndex:'model', sortable:true, width:60, renderer: columnWrap,hidden: false},
		{header:'Item Code', dataIndex:'item_code', sortable:true, width:60, renderer: columnWrap,hidden: true},
		{header:'Item Description', dataIndex:'stock_description', sortable:true, renderer: columnWrap,hidden: false},
		{header:'Color', dataIndex:'item_description', sortable:true, renderer: columnWrap,hidden: true},
		{header:'Category', dataIndex:'category_id', sortable:true, width:100, renderer: columnWrap,hidden: true},
		{header:'Qty', dataIndex:'qty', sortable:true, width:40, hidden: false, align:'center'},
		{header:'Standard<br/>Cost', dataIndex:'standard_cost', hidden:false, width:60, hidden: false, align:'right'},
		{header:'Engine No.', dataIndex:'lot_no', sortable:true, width:100,renderer: columnWrap, hidden: true},
		{header:'Chasis No.', dataIndex:'chasis_no', sortable:true, width:100,renderer: columnWrap, hidden: true},
		{header:'Status', dataIndex:'status_msg', sortable:true, width:50,renderer: columnWrap, hidden: false}
	]

	var rowEditing = Ext.create('Ext.grid.plugin.RowEditing', {
        listeners: {
            cancelEdit: function(rowEditing, context) {
                // Canceling editing of a locally added, unsaved record: remove it
                if (context.record.phantom) {
                    store.remove(context.record);
                }
            }
        }
    });

	var gridMT = {
		xtype:'gridpanel',
		id:'gridMT',
		anchor:'100%',
		forceFit: true,
		store: MerchandiseTransStore,
		columns: columnTransferModel,
		plugins: [cellEditing],
		border: false,
		frame:false,
		viewConfig:{
			stripeRows: true
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
					
					if(!windowItemListSerial){
						var catcode = Ext.getCmp('category').getValue();
						var brcode = Ext.getCmp('currentbranch').getValue();
						var AdjDate = Ext.getCmp('AdjDate').getValue();


						var Brand_Filter = Ext.create('Ext.form.ComboBox', 
							{
						    	xtype:'combo',
						    	hidden: false,
						    	fieldLabel:'Brand',
								labelWidth: 100,
						    	name:'serialise_brand',
						    	id:'serialise_brand',
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
						    	//layout:'anchor',
						    	store: Ext.create('Ext.data.Store',{
						    		fields:['brand_id','brand_name'],
						    		autoLoad: true,
						    		proxy: {
						    			type:'ajax',
						    			url: '?action=brand&category_id='+catcode,
						    			reader:{
						    				type : 'json',
						    				root : 'result',
						    				totalProperty : 'total'
						    			}
						    		}
						    	}),
						          listeners: {
						  			select: function(cmb, rec, idx) {
						  				var brand = this.getValue();
						                var catcode = Ext.getCmp('category').getValue();
										var brcode = Ext.getCmp('currentbranch').getValue();
										var AdjDate = Ext.getCmp('AdjDate').getValue();
										ItemListingStore.proxy.extraParams = {catcode: catcode, branchcode:brcode, trans_date:AdjDate, brand: brand}
										ItemListingStore.load();	
						  			}
						  		}
						
						    }
						);
							
						ItemListingStore.proxy.extraParams = {catcode: catcode, branchcode:brcode, trans_date:AdjDate}
						ItemListingStore.load();	
							
						var windowItemListSerial = Ext.create('Ext.Window',{
							title:'Stock Master Listing',
							id:'windowItemListSerial',
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
									xtype:'panel',
									layout:'hbox',
									items:[Brand_Filter,{
													xtype	: 'textfield',
													width	: 300,
													hidden: false,
													name 	: 'searchSerialItem1',
													id		:'searchSerialItem1',
													fieldLabel: 'Item Description',
													labelWidth: 120,
													listeners : {
														specialkey: function(f,e){							
															if (e.getKey() == e.ENTER) {
																
	
																var catcode = Ext.getCmp('category').getValue();
																var brcode = Ext.getCmp('currentbranch').getValue();
																ItemListingStore.proxy.extraParams = { 
																	query:this.getValue(), 
																	catcode: catcode,
																	branchcode: brcode
																}
																ItemListingStore.load();									
															}
														}						
													}
												}]
								},
										{
											xtype:'grid',
											forceFit: true,
											//flex:1,
											layout:'fit',
											id:'ItemSerialListing',
											store: ItemListingStore,
											columns: [
												{header:'Brand', dataIndex:'brand_name', sortable:true, width:60, renderer: columnWrap,hidden: false},
												{header:'Model', dataIndex:'model', sortable:true, width:60, renderer: columnWrap,hidden: false},
												{header:'Item Code', dataIndex:'item_code', sortable:true, width:60, renderer: columnWrap,hidden: false},
												{header:'Item Description', dataIndex:'stock_description', sortable:true, renderer: columnWrap,hidden: false},
												{header:'Color', dataIndex:'item_description', sortable:true, renderer: columnWrap,hidden: false},
												{header:'Category', dataIndex:'category_id', sortable:true, width:100, renderer: columnWrap,hidden: true},
												{header	:'Action',	xtype:'actioncolumn', align:'center', width:50,
												items:[
													{
														icon: '../js/ext4/examples/shared/icons/accept.png',
														tooltip: 'Select Item',
														handler: function(grid, rowIndex, colIndex) {
															var record = ItemListingStore.getAt(rowIndex);
															var item_code = record.get('item_code');
															var model = record.get('model');
															var stock_description = record.get('stock_description');
															var item_description = record.get('item_description');
															var color = record.get('color');
															var category = record.get('category_id');
															var serialised = record.get('serialised');
															MerchandiseTransStore.load({
																params:{item_code: item_code, AdjDate:AdjDate, model:model, stock_description:stock_description, item_description:item_description, color:color, category:category, serialised:serialised},
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
	
															windowItemListSerial.close();
														}
														
													}
												]
												
												}
											],
											/*dockedItems:[{
												dock:'top',
												xtype:'toolbar',
												name:'searchSerialBar',
												items:[Brand_Filter,{
													width	: 300,
													xtype	: 'textfield',
													hidden: false,
													name 	: 'searchSerialItem',
													id		:'searchSerialItem',
													fieldLabel: 'Item Description',
													labelWidth: 120,
													listeners : {
														specialkey: function(f,e){							
															if (e.getKey() == e.ENTER) {
																
	
																var catcode = Ext.getCmp('category').getValue();
																var brcode = Ext.getCmp('search_suppliers').getValue();
																ItemListingStore.proxy.extraParams = { 
																	query:this.getValue(), 
																	catcode: catcode,
																	branchcode: brcode
																}
																ItemListingStore.load();									
															}
														}						
													}
												},{
													iconCls:'clear-search',
													hidden: true
												}]
											}],*/
											bbar : {
												xtype : 'pagingtoolbar',
												store : ItemListingStore,
												displayInfo : true
											}
										}
									]
								}
							],
							buttons:[
								{
									text:'Close',
									iconCls:'cancel-col',
									handler: function(){
										windowItemListSerial.close();
									}
								}
							]
						});	
					}						
					
					windowItemListSerial.show();
				}	
			}
			]	
		}]
	}

	var gridMTNonSerialize = {
		xtype:'gridpanel',
		id:'gridMTNonSerialize',
		anchor:'100%',
		hidden: true,
		forceFit: true,
		store: MerchandiseTransStore,
		columns: columnTransferModelNonSerial,
		plugins: [cellEditing1],
		border: false,
		frame:false,
		viewConfig:{
			stripeRows: true
		},
		dockedItems:[{
			dock	: 'top',
			xtype	: 'toolbar',
			name 	: 'newMTsearchNonSerial',
			items:[
				{
				icon   	: '../js/ext4/examples/shared/icons/fam/add.gif',
				tooltip	: 'Select Item',
				text 	: 'Select Item',
				handler: function(){
					
					if(!windowItemListnon){
						var catcode = Ext.getCmp('category').getValue();
						var brcode = Ext.getCmp('currentbranch').getValue();
						ItemListingStore.proxy.extraParams = {catcode: catcode, branchcode:brcode}
						ItemListingStore.load();	
						
						var Brand_Filter = Ext.create('Ext.form.ComboBox', 
							{
						    	xtype:'combo',
						    	hidden: false,
						    	fieldLabel:'Brand',
								labelWidth: 100,
						    	name:'nonserialise_brand',
						    	id:'nonserialise_brand',
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
						    	//layout:'anchor',
						    	store: Ext.create('Ext.data.Store',{
						    		fields:['brand_id','brand_name'],
						    		autoLoad: true,
						    		proxy: {
						    			type:'ajax',
						    			url: '?action=brand&category_id='+catcode,
						    			reader:{
						    				type : 'json',
						    				root : 'result',
						    				totalProperty : 'total'
						    			}
						    		}
						    	}),
						          listeners: {
						  			select: function(cmb, rec, idx) {
						  				var brand = this.getValue();
						                var catcode = Ext.getCmp('category').getValue();
										var brcode = Ext.getCmp('currentbranch').getValue();
										var AdjDate = Ext.getCmp('AdjDate').getValue();
										ItemListingStore.proxy.extraParams = {catcode: catcode, branchcode:brcode, trans_date:AdjDate, brand: brand}
										ItemListingStore.load();	
						  			}
						  		}
						
						    }
						);
							
						var windowItemListnon = Ext.create('Ext.Window',{
							title:'Item Listing',
							id:'windowNonItemList',
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
										id:'ItemNonSerialListing',
										store: ItemListingStore,
										columns: [
											{header:'Brand', dataIndex:'brand_name', sortable:true, width:60, renderer: columnWrap,hidden: false},
											{header:'Model', dataIndex:'model', sortable:true, width:60, renderer: columnWrap,hidden: false},
											{header:'Item Description', dataIndex:'stock_description', sortable:true, renderer: columnWrap,hidden: false},
									
											{header:'Category', dataIndex:'category_id', sortable:true, width:100, renderer: columnWrap,hidden: true},
											{header	:'Action',	xtype:'actioncolumn', align:'center', width:50,
											items:[
												{
													icon: '../js/ext4/examples/shared/icons/accept.png',
													tooltip: 'Select',
													handler: function(grid, rowIndex, colIndex) {
														var record = ItemListingStore.getAt(rowIndex);
														var item_code = record.get('item_code');
														var model = record.get('model');
														var stock_description = record.get('stock_description');
														var item_description = record.get('item_description');
														var color = record.get('color');
														var category = record.get('category_id');
														var serialised = record.get('serialised');
														MerchandiseTransStore.load({
															params:{item_code: item_code, AdjDate:AdjDate, model:model, stock_description:stock_description, item_description:item_description, color:color, category:category, serialised:serialised},
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

														windowItemListnon.close();
													}
													
												}
											]
											
											}
										],
										dockedItems:[{
											dock:'top',
											xtype:'toolbar',
											name:'searchNonSerialBar',
											items:[Brand_Filter,{
												width	: 300,
												xtype	: 'textfield',
												name 	: 'searchNonSerialItem',
												id		:'searchNonSerialItem',
												fieldLabel: 'Item Description',
												labelWidth: 120,
												listeners : {
													specialkey: function(f,e){							
														if (e.getKey() == e.ENTER) {
															

															var catcode = Ext.getCmp('category').getValue();
															var brcode = Ext.getCmp('from_location').getValue();
															ItemListingStore.proxy.extraParams = { 
																query:this.getValue(), 
																catcode: catcode,
																branchcode: brcode
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
												hidden: true
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
										windowItemListnon.close();
									}
								}
							]
						});	
					}						
					
					windowItemListnon.show();
				}	
			}
			]	
		}],
		listeners:{
            validateedit: function(editor, e){
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
                    
                    return false;
                }else return true;


                //return true;
            }
        }
	}
		
	Ext.create('Ext.grid.Panel', {
		renderTo: 'merchandisetransfer-grid',
		layout: 'fit',
		//height	: 550,
		title	: 'Receiving Report Branch ',
		store	    :	myInsurance,
		id 		    : 'rrgrid',
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
				},Branches_Filter,{
					xtype:'textfield',
					fieldLabel:'Branch',
					name:'currentbranch',
					id:'currentbranch',
					hidden: true
				},
				{
					width	: 300,
					xtype	: 'textfield',
					name 	: 'searchSerialItem',
					id		:'searchSerialItem',
					fieldLabel: 'Serial/Engine#',
					labelWidth: 120,
					hidden: true,
					listeners : {
						specialkey: function(f,e){							
							if (e.getKey() == e.ENTER) {
								

								var catcode = Ext.getCmp('category').getValue();
								var brcode = Ext.getCmp('from_location').getValue();
								ItemListingStore.proxy.extraParams = { 
									query:this.getValue(), 
									catcode: catcode,
									branchcode: brcode
								}
								ItemListingStore.load();									
							}
						}						
					}
				},{
					iconCls:'clear-search',
					hidden: true
				},'->',{
					iconCls:'windform',
					hidden: true,
					handler: function(){
						//var catcode = Ext.getCmp('category').getValue();
						
						if(!windowNewTransfer){
							var brcode = Ext.getCmp('from_location').getValue();
							if(!brcode){
								Ext.Msg.alert('Error', 'Select From Branch');
								return;
							}
							var windowNewTransfer = Ext.create('Ext.Window',{
								title:'Merchandise Transfer - Manual Entry ',
								modal: true,
								width: 1200,
								//height:500,
								bodyPadding: 5,
								layout:'anchor',
								items:[
									{
										xtype:'fieldset',
										title:'Merchandise Transfer Header',
										layout:'anchor',
										defaultType:'textfield',
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
																var mtgridcol = Ext.getCmp('gridMT');
																var mtgridcolNon = Ext.getCmp('gridMTNonSerialize');
																
																if(v=='14'){
																	mtgridcol.show();
																	mtgridcolNon.hide();
																	
																}else{
																		
																	mtgridcol.hide();
																	mtgridcolNon.show();
																}
																
															}
														}
													},
													{
														xtype:'datefield',
														fieldLabel:'Trans Date',
														name:'trans_date',
														id:'AdjDate'/*,
														value: new Date()*/
													},{
												xtype:'textfield',
												name:'referencenumber',
												id:'manualreferencenum',
												fieldLabel:'Reference No.',
												labelWidth: 120
											}
												]
											},{
												xtype:'fieldcontainer',
												layout:'hbox',
												items:[{
														xtype:'textfield',
														name:'rsdno',
														id:'rsdno',
														fieldLabel:'RSD #'
													},{
														xtype:'textfield',
														name:'servedby',
														id:'servedby',
														fieldLabel:'Served By'
													}]
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
										id:'gridpanelserialize',
										//autoScroll: true,
										//layout:'fit',
										padding:'5px',
										border: false,
										items:[gridMTNonSerialize,gridMT]
									}
								],buttons:[
									{
										text:'Process Manual Transfer',
										id:'btnManualProcess',
										handler:function(){
											var AdjDate = Ext.getCmp('AdjDate').getValue();	
											var catcode = Ext.getCmp('category').getValue();
											var FromStockLocation = Ext.getCmp('from_location').getValue();
											var ToStockLocation = Ext.getCmp('currentbranch').getValue();
											var brreference = Ext.getCmp('manualreferencenum').getValue();
											var rsdno = Ext.getCmp('rsdno').getValue();
											var servedby = Ext.getCmp('servedby').getValue();
											var memo_ = Ext.getCmp('memo').getValue();
											if(brreference==null || brreference==''){
												Ext.MessageBox.alert('Error','Reference field should not be empty.');
												return false;
											}
											if(rsdno==null || rsdno==''){
												Ext.MessageBox.alert('Error','RSD field should not be empty.');
												return false;
											}
											if(servedby==null || servedby==''){
												Ext.MessageBox.alert('Error','Serve By field should not be empty.');
												return false;
											}
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
												url : '?action=SaveManualTransfer',
												method: 'POST',
												params:{
													AdjDate:AdjDate,
													catcode:catcode,
													FromStockLocation: FromStockLocation,
													ToStockLocation: ToStockLocation,
													memo_: memo_,
													reference: brreference,
													rsdno: rsdno,
													servedby: servedby 
												},
												success: function (response){
													var jsonData = Ext.JSON.decode(response.responseText);
													var AdjDate = jsonData.AdjDate;
													Ext.getCmp('AdjDate').setValue(AdjDate);
													windowNewTransfer.close();
													//MerchandiseTransStore.proxy.extraParams = {action: 'AddItem'}
													myInsurance.load();
												},
												failure: function (response){
													//Ext.MessageBox.hide();
													//var jsonData = Ext.JSON.decode(response.responseText);
													//Ext.MessageBox.alert('Error','Error Processing');
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
						//var AdjDate = Ext.getCmp('AdjDate').getValue();	
						Ext.Ajax.request({
							url : '?action=NewTransferManual',
							method: 'POST',
							success: function (response){
								var jsonData = Ext.JSON.decode(response.responseText);
								var AdjDate = jsonData.AdjDate;
								Ext.getCmp('AdjDate').setValue(AdjDate);
								
								//MerchandiseTransStore.proxy.extraParams = {action: 'AddItem'}
								//MerchandiseTransStore.load();
								//MerchandiseTransStore.proxy.extraParams = {action: 'view'}
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
							},
							failure: function (response){
								//Ext.MessageBox.hide();
								//var jsonData = Ext.JSON.decode(response.responseText);
								Ext.MessageBox.alert('Error','Error Processing');
							}
						});
						//Ext.getCmp('btnManualProcess').setDisabled(true);
						windowNewTransfer.show();
					}
				}
				]
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
			branchcode = jsonData.branchcode;
			branchname = jsonData.branch_name;
			Ext.getCmp('currentbranch').setValue(branchcode);
			var GridTitle = Ext.getCmp('rrgrid').getTitle();
			Ext.getCmp('rrgrid').setTitle(GridTitle+' - ['+branchname+']');
			myInsurance.proxy.extraParams = {
				branchcode: branchcode
			}
			myInsurance.load();
			//Ext.MessageBox.alert('Success!',"Process complete"+branchcode);
			//window.open('?action=downloadfile&pathfile='+pathfile);
		},
		failure: function (response){
			Ext.MessageBox.hide();
			var jsonData = Ext.JSON.decode(response.responseText);
			Ext.MessageBox.alert('Error','Error Processing');
		}
	});
	
	function setButtonDisabled(valpass=false){
		Ext.getCmp('btnManualProcess').setDisabled(valpass);
		
	}
	function setRcvdButtonDisabled(valpass=false){
		//Ext.getCmp('btnProcess').setDisabled(valpass);
		
	}
});
