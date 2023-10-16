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
	var itemsPerPage = 20;
	var all = false;

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
			{name:'delivery_date',mapping:'delivery_date'},
			{name:'postdate',mapping:'postdate'},
			{name:'prepared_by',mapping:'prepared_by'},
			{name:'approved_by',mapping:'approved_by'},
			{name:'reviewed_by',mapping:'reviewed_by'}
		]
	});

	Ext.define('Modelitemlisting',{
		extend : 'Ext.data.Model',
		fields  : [
			{name:'trans_no',mapping:'trans_no'},
			{name:'stock_id',mapping:'stock_id'},
			{name:'lot_no',mapping:'lot_no'},
			{name:'chasis_no',mapping:'chasis_no'},
			{name:'color',mapping:'color'},
			{name:'item_description',mapping:'item_description'},
			{name:'qty',mapping:'qty'},
			{name:'category_id',mapping:'category_id'},
			{name:'reference',mapping:'reference'},
			{name:'standard_cost',mapping:'standard_cost', type: 'float'},
			{name:'subtotal_cost', mapping:'subtotal_cost', type: 'float'},
			{name:'prepared_by',mapping:'prepared_by'},
			{name:'approver_by',mapping:'approver_by'},
			{name:'reviewer_by',mapping:'reviewer_by'}			
		]
	});

	Ext.define('GlEntryitemlisting',{
		extend : 'Ext.data.Model',
		fields  : [
			{name:'trans_no',mapping:'trans_no'},
			{name:'account_code_gl',mapping:'account_code_gl'},
			{name:'account_name_gl',mapping:'account_name_gl'},
			{name:'mcode_gl',mapping:'mcode_gl'},
			{name:'masterfile_gl',mapping:'masterfile_gl'},
			{name:'debit_gl',mapping:'debit_gl', type: 'float'},
			{name:'credit_gl',mapping:'credit_gl', type: 'float'},
			{name:'memo_gl',mapping:'memo_gl'},
			{name:'reference',mapping:'reference'}
		]
	});

	function Status(val) {
		if(val == 'Draft'){
			return '<span style="color:red;font-weight:bold;">For Approval</span>';
		}else if(val == 'Approved'){
            return '<span style="color:green;font-weight: bold;">Approved</span>';
        }else if(val == 'Closed'){
            return '<span style="color:blue;font-weight: bold;">Closed</span>';
        }else{
        	return '<span style="color:black;font-weight: bold;">Disapproved</span>';
        }
        return val;
    }
	
	var columnModel =[
		{header:'ID', dataIndex:'trans_id', sortable:true, width:25, hidden: true},
		{header:'Reference', dataIndex:'reference', sortable:true, width:150,
			renderer: function(value, metaData, summarydata, dataIndex){
				return '<span style="color:blue; font-weight:bold;">' + value + '</span>';
			}
		},
		{header:'Trans Date', dataIndex:'tran_date', sortable:true, width:75,
			renderer: function(value, metaData, summarydata, dataIndex){
				return '<span style="color:black; font-weight:bold;">' + value + '</span>';
			}
		},
		{header:'Post Date', dataIndex:'postdate', sortable:true, width:75,
			renderer: function(value, metaData, summarydata, dataIndex){
				return '<span style="color:black; font-weight:bold;">' + value + '</span>';
			}
		},
		{header:'From Location', dataIndex:'loc_name', sortable:true, width:235, hidden: false,
			renderer: function(value, metaData, summarydata, dataIndex){
				return '<span style="color:black; font-weight:bold;">' + value + '</span>';
			}
		},
		{header:'Category', dataIndex:'category_name', sortable:true, width:80,
 			renderer: function(value, metaData, summarydata, dataIndex){
				return '<span style="color:green; font-weight:bold;">' + value + '</span>';
			}
		},
		{header:'Total Items', dataIndex:'qty', sortable:true, width:70, align:'center',
			renderer: function(value, metaData, summarydata, dataIndex){
				return '<span style="color:black; font-weight:bold;">' + Ext.util.Format.number(value, '00,000.00') + '</span>';
			}
		},
		{header:'Remarks', dataIndex:'remarks', sortable:true, align:'left', width:160, renderer: columnWrap,
			renderer: function(value, metaData, summarydata, dataIndex){
				return '<span style="color:black; font-weight:bold;">' + value + '</span>';
			}
		},
		{header:'Status', dataIndex:'statusmsg', sortable:true, width:80, renderer: Status},
		{header	: 'Action',	xtype:'actioncolumn', align:'center', width:40,
			items:[
				{
					icon: '../js/ext4/examples/shared/icons/application_view_columns.png',
					tooltip: 'Items Details / Approved / Disapproved / Posting',
					hidden: false,
					handler: function(grid, rowIndex, colIndex) {
						var record = myInsurance.getAt(rowIndex);
						id = record.get('trans_no');
                        reference = record.get('reference');
                        brcode = record.get('loc_code');
                        catcode = record.get('category_id');
                        prepared = record.get('prepared_by');
                        aprroved = record.get('approved_by');                        

						if(!windowItemSerialList){
							MTItemListingStore.proxy.extraParams = {
								catcode: catcode, 
								branchcode:brcode,
								reference:reference,
								trans_no:id
							};
							GlCompliEntyStore.proxy.extraParams = {
								branchcode:brcode,
								reference:reference
							};
							MTItemListingStore.load();	
							GlCompliEntyStore.load();
							UserRoleIdStore.load();

							var robert = UserRoleIdStore.getAt(0);
							user_id = robert.get('user_id');						
							
							var windowItemSerialList = Ext.create('Ext.Window',{
								title:'Item Details / Gl Entry Details',
								id:'windowItemSSerialList',
								modal: true,
								width: 990,
								height:450,
								bodyPadding: 5,
								layout:'fit',
								dockedItems:[{
									dock:'top',
									xtype:'toolbar',
									name:'postingdate',
									hidden: false,
									items:[{
										xtype:'datefield',
										fieldLabel:'Posting Date',
										name:'trans_date',
										id:'PostDate',
    									fieldStyle: 'background-color: #F2F3F4; color: black; font-weight: bold;',
    									value: new Date(),
    									readOnly: false
									},{
										xtype:'combo',
										fieldLabel:'Approved By',
										name:'approvedby_updt',
										id:'approvedby_updt',
										anchor:'100%',
										typeAhead:true,
										anyMatch:true,
										labelWidth: 100,
										width: 400,
										forceSelection: true,
										allowBlank: false,
										queryMode:'local',
										triggerAction: 'all',
										displayField  : 'real_name',
										valueField    : 'id',
										hiddenName: 'id',
										hidden: false,
										fieldStyle: 'background-color: #F2F3F4; color: green; font-weight: bold;',
										emptyText:'Select Approver',
										store: Ext.create('Ext.data.Store',{
											fields: ['id', 'real_name'],
											autoLoad: true,
											proxy: {
												type:'ajax',
												url: '?action=approvedby_user',
												reader:{
													type : 'json',
													root : 'result',
													totalProperty : 'total'
												}
											}
										})
									},{
										xtype:'combo',
										fieldLabel:'Reviewed By',
										name:'reviewedby_updt',
										id:'reviewedby_updt',											
										anchor:'100%',
										typeAhead:true,
										anyMatch:true,
										labelWidth: 100,
										width: 400,
										forceSelection: true,
										allowBlank: false,
										queryMode:'local',
										triggerAction: 'all',
										displayField  : 'real_name',
										valueField    : 'id',
										hiddenName: 'id',
										hidden: false,
										fieldStyle: 'background-color: #F2F3F4; color: green; font-weight: bold;',
										emptyText:'Select Reviewer',
										store: Ext.create('Ext.data.Store',{
											fields: ['id', 'real_name'],
											autoLoad: true,
											proxy: {
												type:'ajax',
												url: '?action=reviwedby_user',
												reader:{
													type : 'json',
													root : 'result',
													totalProperty : 'total'
												}
											}
										})
									}]
								}],
								items:[
									{
										xtype: 'tabpanel',
										id: 'complitabpanel',
										autoScroll: true,
										activeTab: 0,
										width: 860,
										height: 270,
										scale: 'small',
										items:[{
											xtype:'gridpanel',
											id: 'ItemSerialListingView',
											forceFit: true,
											layout:'fit',
											title: 'Item Details',
											icon: '../js/ext4/examples/shared/icons/lorry_flatbed.png',
											loadMask: true,
											//height: 130,
											store:	MTItemListingStore,
											columns: columnItemSerialView,
											//selModel: smCheckAmortGrid,
											columnLines: true,
											features: [{ftype: 'summary'}],
											/*bbar : {
												xtype : 'pagingtoolbar',
												store : MTItemListingStore,
												displayInfo : true
											},*/
											viewConfig : {
												listeners : {
													cellclick : function(view, cell, cellIndex, record, row, rowIndex, e) {
														//alert( record.get("totalpayment") + ' ' + (rowIndex+1));
														//Ext.getCmp("total_amount").setValue(record.get("totalpayment"));
														//Ext.getCmp('tenderd_amount').setValue();
														//Ext.getCmp('tenderd_amount').focus(false, 200);
													}
												}
											}
										},{
											xtype:'gridpanel',
											id: 'GlSerialView',
											autoScroll: true,
											forceFit: true,
											layout:'fit',
											title: 'GL Entry',
											icon: '../js/ext4/examples/shared/icons/vcard.png',
											loadMask: true,
											//height: 250,
											store:	GlCompliEntyStore,
											columns: columnGlEntryView,
											columnLines: true,
											features: [{ftype: 'summary'}],
											/*bbar : {
												xtype : 'pagingtoolbar',
												store : GlCompliEntyStore,
												displayInfo : true
											},*/
											viewConfig : {
												listeners : {
													cellclick : function(view, cell, cellIndex, record, row, rowIndex, e) {
														//alert( record.get("penalty") + ' ' + (rowIndex+1));
													}
												}
											}
										}]
									}
								],
								buttons:[
									{
										text:'Approved',
										id: 'approved_btn',
										icon: '../js/ext4/examples/shared/icons/accept.png',
										handler: function(grid, rowIndex, colIndex) {
				                        
						                    Ext.MessageBox.confirm('Confirm', 'Are you sure you want to Approved this record?', ApprovalFunction);
						                    function ApprovalFunction(btn) {
						                    	if(btn == 'yes') {
						                    		Ext.MessageBox.show({
														msg: 'Approved Transaction, please wait...',
														progressText: 'Saving...',
														width:300,
														wait:true,
														waitConfig: {interval:200},
														//icon:'ext-mb-download', //custom class in msg-box.html
														iconHeight: 50
													});
							                        Ext.Ajax.request({
														url : '?action=approval',
														method: 'POST',
														params:{
															reference: reference,
															value: btn
														},
														success: function (response){
															Ext.Msg.alert('Success','Success Processing');
															myInsurance.load();
															windowItemSerialList.close();										
														},	
														failure: function (response){
															Ext.Msg.alert('Error', 'Processing ' + records.get('id'));
														}
													});
												}
						                    };
						                }
									},{
										text:'Post',
										id: 'post_tran_btn',
										icon: '../js/ext4/examples/shared/icons/add.png',
										hidden: false,
										handler: function() {
											Ext.MessageBox.confirm('Confirm', 'Are you sure you want to Post this record?', ApprovalFunction);
						                    function ApprovalFunction(btn) {
						                    	if(btn == 'yes') {
													var PostDate = Ext.getCmp('PostDate').getValue();	

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
														url : '?action=posting_transaction',
														method: 'POST',
														params:{
															reference:reference,
															trans_no:id,
															PostDate:PostDate,
															value:btn
														},
														success: function(response){
															var jsonData = Ext.JSON.decode(response.responseText);
															var errmsg = jsonData.errmsg;
															//Ext.getCmp('AdjDate').setValue(AdjDate);
															if(errmsg!=''){
																Ext.MessageBox.alert('Error',errmsg);
															}else{
																windowItemSerialList.close();
																myInsurance.load();
																Ext.MessageBox.alert('Success','Success Processing');
															}	
														} 
													});
													//Ext.MessageBox.hide();
												}
											};
										}
									},{
										text:'Disapproved',
										id: 'disapproved_btn',
										icon: '../js/ext4/examples/shared/icons/fam/cross.gif',
										handler: function(grid, rowIndex, colIndex) {
				                        
						                    Ext.MessageBox.confirm('Confirm', 'Are you sure you want to Disapproved this record?', ApprovalFunction);
						                    function ApprovalFunction(btn) {
						                    	if(btn == 'yes') {
						                    		Ext.MessageBox.show({
														msg: 'Disapproved Transaction, please wait...',
														progressText: 'Saving...',
														width:300,
														wait:true,
														waitConfig: {interval:200},
														//icon:'ext-mb-download', //custom class in msg-box.html
														iconHeight: 50
													});
							                        Ext.Ajax.request({
														url : '?action=disapproval',
														method: 'POST',
														params:{
															reference: reference,
															value: btn
														},
														success: function (response){
															Ext.Msg.alert('Success','Success Processing');
															myInsurance.load();
															windowItemSerialList.close();										
														},	
														failure: function (response){
															Ext.Msg.alert('Error', 'Processing ' + records.get('id'));
														}
													});
												}
						                    };
						                }
									},{
										text:'Update Approver/Reviewer',
										id: 'apprvdreviwer_btn',
										icon: '../js/ext4/examples/shared/icons/add.png',
										handler: function() {
											var approver_user = Ext.getCmp('approvedby_updt').getValue();
											var reviewer_user = Ext.getCmp('reviewedby_updt').getValue();

						                    Ext.MessageBox.confirm('Confirm', 'Are you sure you want to Update this record?', ApprovalFunction);
						                    function ApprovalFunction(btn) {
						                    	if(btn == 'yes') {	
													if(approver_user==null){														
														Ext.MessageBox.alert('Error','Please, Select Approver');
														return false;
													}
													if(reviewer_user==null){														
														Ext.MessageBox.alert('Error','Please, Select Reviewer');
														return false;
													}
						                    		Ext.MessageBox.show({
														msg: 'Update Transaction, please wait...',
														progressText: 'Saving...',
														width:300,
														wait:true,
														waitConfig: {interval:200},
														//icon:'ext-mb-download', //custom class in msg-box.html
														iconHeight: 50
													});
							                        Ext.Ajax.request({
														url : '?action=updateapprvd_revwd',
														method: 'POST',
														params:{
															reference: reference,
															approver_user: approver_user,
															reviewer_user: reviewer_user,
															value: btn
														},
														success: function (response){
															Ext.Msg.alert('Success','Success Processing');
															myInsurance.load();
															windowItemSerialList.close();										
														},	
														failure: function (response){
															Ext.Msg.alert('Error', 'Processing ' + records.get('id'));
														}
													});
												}
						                    };
						                }
									},{
										text:'Close',
										iconCls:'cancel-col',
										handler: function(){
											windowItemSerialList.close();
										}
									}
								]
							});							
						}
						
						if(user_id == prepared) {
							Ext.getCmp('approved_btn').setVisible(false);
							Ext.getCmp('disapproved_btn').setVisible(false);
							if(record.get('status') == 'Approved') {
								Ext.getCmp('post_tran_btn').setVisible(true);
								Ext.getCmp('apprvdreviwer_btn').setVisible(false);
								Ext.getCmp('approvedby_updt').setVisible(false);
								Ext.getCmp('reviewedby_updt').setVisible(false);
							}else if(record.get('status') == 'Draft') {
								Ext.getCmp('post_tran_btn').setVisible(false);
							}
						}else if(user_id == aprroved) {
							Ext.getCmp('post_tran_btn').setVisible(false);
							Ext.getCmp('apprvdreviwer_btn').setVisible(false);
							Ext.getCmp('approvedby_updt').setVisible(false);
							Ext.getCmp('reviewedby_updt').setVisible(false);
							if(record.get('status') == 'Approved') {
								Ext.getCmp('approved_btn').setVisible(false);
								Ext.getCmp('PostDate').setVisible(false);
							}												
						}else{
							Ext.getCmp('disapproved_btn').setVisible(false);
							Ext.getCmp('approved_btn').setVisible(false);
							Ext.getCmp('post_tran_btn').setVisible(false);
							Ext.getCmp('PostDate').setVisible(false);
							Ext.getCmp('approvedby_updt').setVisible(false);
							Ext.getCmp('reviewedby_updt').setVisible(false);
							Ext.getCmp('apprvdreviwer_btn').setVisible(false);
						}

						if(record.get('status') == 'Closed') {
							Ext.getCmp('disapproved_btn').setVisible(false);
							Ext.getCmp('approved_btn').setVisible(false);
							Ext.getCmp('post_tran_btn').setVisible(false);
							Ext.getCmp('PostDate').setVisible(false);
							Ext.getCmp('approvedby_updt').setVisible(false);
							Ext.getCmp('reviewedby_updt').setVisible(false);
							Ext.getCmp('apprvdreviwer_btn').setVisible(false);
						}else if(record.get('status') == 'Draft') {
							Ext.getCmp('PostDate').setVisible(false);
						}else if(record.get('status') == 'Disapproved') {
							Ext.getCmp('disapproved_btn').setVisible(false);
							Ext.getCmp('approved_btn').setVisible(false);
							Ext.getCmp('post_tran_btn').setVisible(false);
							Ext.getCmp('PostDate').setVisible(false);
							Ext.getCmp('approvedby_updt').setVisible(false);
							Ext.getCmp('reviewedby_updt').setVisible(false);
							Ext.getCmp('apprvdreviwer_btn').setVisible(false);
						}

						//var v = Ext.getCmp('category').getValue();
						if(catcode=='14'){
							Ext.ComponentQuery.query('#ItemSerialListingView gridcolumn[dataIndex^="chasis_no"]')[0].show();
							Ext.ComponentQuery.query('#ItemSerialListingView gridcolumn[dataIndex^="lot_no"]')[0].setText('Engine No.');
							Ext.ComponentQuery.query('#ItemSerialListingView gridcolumn[dataIndex^="item_description"]')[0].show();
							Ext.ComponentQuery.query('#ItemSerialListingView gridcolumn[dataIndex^="standard_cost"]')[0].show();
							Ext.ComponentQuery.query('#ItemSerialListingView gridcolumn[dataIndex^="subtotal_cost"]')[0].hide();
						}else{
							Ext.ComponentQuery.query('#ItemSerialListingView gridcolumn[dataIndex^="lot_no"]')[0].setText('Serial No.');
							Ext.ComponentQuery.query('#ItemSerialListingView gridcolumn[dataIndex^="chasis_no"]')[0].hide();
							Ext.ComponentQuery.query('#ItemSerialListingView gridcolumn[dataIndex^="color"]')[0].hide();
							Ext.ComponentQuery.query('#ItemSerialListingView gridcolumn[dataIndex^="standard_cost"]')[0].hide();
							Ext.ComponentQuery.query('#ItemSerialListingView gridcolumn[dataIndex^="subtotal_cost"]')[0].show();
						}
						windowItemSerialList.show();	
					}
				},{
					icon: '../js/ext4/examples/shared/icons/printer.png',
					tooltip: 'Complimentary Report',
					handler: function(grid, rowIndex, colIndex) {
						var record = myInsurance.getAt(rowIndex);
						reference = record.get('reference');
						/*var win = new Ext.Window({
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
						Ext.DomHelper.insertFirst(win.body, iframe)*/
						window.open('../reports/complimentary_items_report.php?reference='+reference);
					},
					getClass : function(value, meta, record, rowIx, ColIx, store) {
	                    if(record.get('status') != 'Closed') {
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
		pageSize: itemsPerPage,
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
		fields: ['id','stock_id','stock_description','trans_date','price','reference','currentqty','qty','standard_cost', 'lot_no', 'chasis_no', 'category_id','serialise_id','color','type_out','transno_out','line_item','rr_date'],
		autoLoad: false,
		autoSync: true,
		metho:'GET',
		proxy : {
			type: 'ajax',
			url	: '?',
			reader:{
				type : 'json',
				root : 'result',
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
		{header:'line', dataIndex:'line_item', sortable:true, width:50, align:'center', hidden: true},
		{header:'Trans', dataIndex:'transno_out', sortable:true, width:60, renderer: columnWrap, hidden: true},
		{header:'Type', dataIndex:'type_out', sortable:true, width:60, renderer: columnWrap, hidden: true},
		{header:'RR Date', dataIndex:'rr_date', sortable:true, width:60, align:'center', renderer: columnWrap, hidden: false},
		{header:'#', dataIndex:'id', sortable:true, width:50, align:'center', renderer: columnWrap, hidden: true},
		{header:'Model', dataIndex:'stock_id', sortable:true, width:80, renderer: columnWrap,hidden: false},
		{header:'Stock Description', dataIndex:'stock_description', sortable:true, renderer: columnWrap,hidden: false},
		{header:'Color', dataIndex:'color', sortable:true, renderer: columnWrap,hidden: false},
		{header:'Category', dataIndex:'category_id', sortable:true, width:100, renderer: columnWrap,hidden: true},
		{header:'Location', dataIndex:'loc_code', sortable:true,width:60, renderer: columnWrap, hidden: true},
		{header:'Unit Cost', dataIndex:'standard_cost', sortable:true, width:100, renderer: columnWrap, hidden: false, align:'right',
			renderer: function(value, metadata, summaryData, record, rowIndex, colIndex, store) {
				if(value == 0){
					return '<span style="color:red; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:green; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') + '</span>'
				}
			}
		},
		{header:'Current Qty', dataIndex:'currentqty', sortable:false, width:40, renderer: columnWrap, hidden: true, align:'center'},
		{header:'Qty', dataIndex:'qty', sortable:true, width:60, hidden: false, renderer: columnWrap, align:'center',
			renderer: function(value, metadata, summaryData, record, rowIndex, colIndex, store) {
				GetTotalBalance();
				if(value == 0){
					return '<span style="color:red; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:black; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') + '</span>'
				}
			},
			editor:{
				completeOnEnter: true,
				field:{
					xtype:'numberfield',
					allowBlank: false,
					hideTrigger: true,
					decimalPrecision: 2,
					listeners : {
					    keyup: function(grid, rowIndex, colIndex) {
							GetTotalBalance();
							GLItemsStore.load();
                    	},
						specialkey: function(f,e){
							if (e.getKey() == e.ENTER) {
								GLItemsStore.load();
								GetTotalBalance();
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

						//item_model: record.get('model'),
						Ext.getCmp('item_model').setValue('');

						
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
	    fields: ['code_id', 'description', 'debit', 'credit', 'actualprice', 'line', 'class_id', 'memo', 'person_type_id', 'person_id', 'branch_id', 'person_name', 'mcode', 'master_file', 'mastertype', 'master_file_type','line_item', 'suggest_entry', 'suggest_description']
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

	//Added on 10/10/2022
	var StatusFileStore = new Ext.create ('Ext.data.Store',{
		fields 	: 	['stat_id', 'namecaption'],
		data 	: 	[{"stat_id":"","namecaption":"All"},
					{"stat_id":"Approved","namecaption":"Approved"},
					{"stat_id":"Draft","namecaption":"For Approval"},
                    {"stat_id":"Closed","namecaption":"Closed"},
                    {"stat_id":"Disapproved","namecaption":"Disapproved"}],
        autoLoad: true
	});
	
    var MasterfileTypeStore = new Ext.create ('Ext.data.Store',{
		fields 	: 	['id','namecaption'],
		data 	: 	[{"id":"99","namecaption":"Not Applicable"},
					{"id":"2","namecaption":"Customer"},
                    {"id":"3","namecaption":"Supplier"},
                    {"id":"4","namecaption":"Branch"}
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
		{xtype: 'rownumberer'},
		{header:'#', dataIndex:'line_item', sortable:true, width:50, align:'center', hidden: true},
		{header:'#', dataIndex:'line', sortable:true, width:30, align:'center', hidden: true},
		{header:'Account Code', dataIndex:'code_id', sortable:true, width:90, align:'center', hidden: false, 
			renderer: function(value, metaData, record, rowIndex, colIndex, store) {
				return '<span style="color:blue; font-weight:bold;">' + value + '</span>';
			}
		},
		{header:'Account Name', dataIndex:'description', sortable:true, width:150, renderer: columnWrap,hidden: false},
		{header:'Mcode', dataIndex:'mcode', sortable:true, width:50, renderer: columnWrap,hidden: true},
		{header:'Person ID', dataIndex:'person_id', sortable:true, width:50, renderer: columnWrap,hidden: true},
		{header:'Masterfile Type', dataIndex:'master_file_type', sortable:true, width:100, renderer: columnWrap,hidden: true},
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
		{header:'Masterfile', dataIndex:'master_file', sortable:true, width:115,hidden: false},
		{header:'Debit', dataIndex:'debit', sortable:true, width:80, hidden: false, align:'right',
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
			renderer : function(value, metaData, summaryData, dataIndex){
				GetTotalBalance();
				if (value==0) {
					return '<span style="color:red; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:green; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			}
			//Ext.util.Format.numberRenderer('0,000.00')
		},
		{header:'Credit', dataIndex:'credit', sortable:true, width:80, hidden: false, align:'right',
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return '<span style="color:red; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:green; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			}
		},
		{header:'Actual Price', dataIndex:'actualprice', sortable:true, width:80, hidden: true, align:'right'},
		{header:'Memo', dataIndex:'memo', sortable:true, width:145, renderer: columnWrap,hidden: false,
			editor:{
				field:{
					xtype:'textfield',
					name:'memo2',
					id:'memo2',
					anchor:'100%'
				}
			}
		},
		{header:'Suggested Code', dataIndex:'suggest_entry', sortable:true, width:50, renderer: columnWrap,hidden: true},
		{header:'Suggested Entry', dataIndex:'suggest_description', sortable:true, width:100, renderer: columnWrap,hidden: false},
		{header:'Action',xtype:'actioncolumn', align:'center', width:60,
			items:[{
					icon:'../js/ext4/examples/shared/icons/report_add.png',
					tooltip:'Add Suggested Entry',
					id:'suggestedentry',					
					handler: function(grid, rowIndex, colIndex){
						var record = GLItemsStore.getAt(rowIndex);
						var id = record.get('line');
						var account_code = record.get('code_id');				
						var person_type_id = record.get('person_type_id');						
						var master_file = record.get('master_file');	
						var suggest_entry = record.get('suggest_entry');	
											
						if(!editsuggestwindow){
							var editsuggestwindow = Ext.create('Ext.Window',{
                               	width: 500,
                                layout:'fit',
								modal: true,
								closeAction:'destroy',
								items:[{
                                    xtype:'form',
									id:'updatesuggest_form',
									url  : '?action=updatesuggest',
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
											xtype:'combo',
											fieldLabel:'Chart of Account',
											name:'suggestetry_add',
											id:'suggestetry_add',
											anchor:'100%',
											labelWidth:115,
											typeAhead: true,
											anyMatch: true,
											required: true,
											triggerAction: 'all',
											store: coasuggestlistingStore,
											queryMode: 'local',
											displayField  : 'account_name',
											valueField    : 'account_code',
											hiddenName: 'account_code',
											value:suggest_entry,
											listeners:{
												change: function(combo, value,index){
													return decodeHtmlEntity(combo.getRawValue());
												}
											} 
										}
									],
                                    buttons:[{
                                        text:'Save',
										id:'btnUpdateSug',
										handler: function(){
											var updatesuggest_form = Ext.getCmp('updatesuggest_form').getForm();
											var suggestentry = Ext.getCmp('suggestetry_add').getValue();
											if(suggestentry==null){
												Ext.MessageBox.alert('Error','Please Select Chart of Accounts');
												return false;
											}
											Ext.getCmp('suggest_blocking').setValue(suggestentry);
									
											if(updatesuggest_form.isValid()) {
												updatesuggest_form.submit({
													waitMsg:'Updating Data...',
													success : function (response) {
														updatesuggest_form.reset();
														editsuggestwindow.close();
														GLItemsStore.load();
														GetTotalBalance();														
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
                                            editsuggestwindow.close();
											editsuggestwindow=null;
                                        }
                                    }]

                                }]
                            });
                        }
						//setSaveSuggestButtonHidden(account_code);						
						Ext.getCmp('suggestetry_add').setValue(suggest_entry);
						editsuggestwindow.setTitle('Add Suggested Entry');
						editsuggestwindow.show();
					},
					getClass : function(value, meta, record, rowIx, ColIx, store) {
	                    if(record.get('master_file_type') != 4) {
                			return 'x-hidden-visibility';
            			}	 
						
						var found = record.get('description');
						const found_bc = Boolean(found.match('^(.*)Branch Current'));							

	                    if(found_bc == false) {
                			return 'x-hidden-visibility';
            			}	
	                }					
				},{
					icon:'../js/ext4/examples/shared/icons/report_edit.png',
					tooltip:'Edit Masterfile',
					hidden: false,
					handler: function(grid, rowIndex, colIndex){
						var record = GLItemsStore.getAt(rowIndex);
						var id = record.get('line');
						var account_code = record.get('code_id');				
						var person_type_id = record.get('person_type_id');
						var person_id = record.get('person_id');
						var master_file = record.get('master_file');	
						var description = record.get('description');							
											
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
										anyMatch: true,
										required: true,
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
											
                                    }],
                                    buttons:[{
                                        text:'Save',
										id:'btnUpdateM',
										handler: function(){
											var updateje_form = Ext.getCmp('updateje_form').getForm();
											var masterfile = Ext.getCmp('masterfile').getValue();
											var masterfile_type = Ext.getCmp('masterfile_type').getValue();
											if(masterfile==null){
												Ext.MessageBox.alert('Error','Please Select Masterfile');
												return false;
											}

											if(masterfile_type==null){
												Ext.MessageBox.alert('Error','Please Select Type');
												return false;
											}
				
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

						//setSaveButtonHidden(account_code);						
						//setSaveButtonHidden1(description);
												
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
						editjewindow.setTitle('Edit Masterfile Window');
						editjewindow.show();
					},
					getClass : function(value, meta, record, rowIx, ColIx, store) {
						var found = record.get('description');
						const found_bc = Boolean(found.match('^(.*)Branch Current'));	
						const match_hoc = Boolean(found.match('^(.*)Home Office Current'));
						const match_merchandise = Boolean(found.match('^(.*)Merchandise Inventory'));

	                    if(found_bc == true) {
                			return 'x-hidden-visibility';
            			}	 
						if(match_hoc == true) {
                			return 'x-hidden-visibility';
            			}		
						if(match_merchandise == true) {
                			return 'x-hidden-visibility';
            			}					
	                }
				},{
					icon:'../js/ext4/examples/shared/icons/cancel.png',
					tooltip:'Delete',
					handler: function(grid, rowIndex, colIndex){
						var record = GLItemsStore.getAt(rowIndex);
						var id = record.get('line');
						var account_code = record.get('code_id');
						var line_item = record.get('line_item');
						
						Ext.MessageBox.confirm('Confirm', 'Do you want to Delete this entry?', function (btn, text) {
							if (btn == 'yes') {
								Ext.Ajax.request({
									url : '?action=delete_gl_entry&line_id='+id+'&account_code='+account_code+'&line_item='+line_item,
									method: 'POST',
									success: function(response){
										//GLItemsStore.load();
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
		model: 'Modelitemlisting',
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

	var GlCompliEntyStore = Ext.create('Ext.data.Store', {
		model: 'GlEntryitemlisting',
		name: 'GlCompliEntyStore',
		autoLoad: false,
		proxy : {
			type: 'ajax',
			url	: '?action=GLEntryItems',
			reader:{
				type : 'json',
				root : 'result',
				totalProperty : 'total'
			}
		}
	});
	
	var ItemListingStore = Ext.create('Ext.data.Store', {
		fields: ['serialise_id', 'model', 'lot_no', 'chasis_no', 'standard_cost','item_code', 'item_description', 'stock_description', 'qty','category_id', 'serialised','type_out', 'transno_out', 'reference', 'tran_date', 'inventory_account'],
		autoLoad: false,
		pageSize: itemsPerPage,
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

	var UserRoleIdStore = Ext.create('Ext.data.Store', {
		fields: ['role_id'],
		autoLoad: false,
		pageSize: itemsPerPage,
		proxy : {
			type: 'ajax',
			url	: '?action=UserRoleId_apprv',
			reader:{
				type : 'json',
				root : 'result',
				totalProperty : 'total'
			}
		}
	});

	var columnItemSerialView = [
		{header:'ID', dataIndex:'trans_no', sortable:true, width:60, renderer: columnWrap,hidden: true},
		{header:'Model', dataIndex:'model', sortable:true, width:70, renderer: columnWrap,hidden: false},
		{header:'Item Description', dataIndex:'item_description', sortable:true, renderer: columnWrap,hidden: false},
		{header:'Color', dataIndex:'color', sortable:true, renderer: columnWrap,hidden: false},
		{header:'Category', dataIndex:'category_id', sortable:true, width:100, renderer: columnWrap,hidden: true},
		{header:'Qty', dataIndex:'qty', sortable:true, width:40, hidden: false, align:'center'},
		{header:'Total Cost', dataIndex:'standard_cost', sortable:true, width:80, hidden: false, align:'center',
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return '<span style="color:red; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:green; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			},
			summaryType: 'sum',
			summaryRenderer: function(value, summaryData, dataIndex){
				return '<span style="color:blue;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';									
			}
		},
		{header:'Total Cost', dataIndex:'subtotal_cost', sortable:true, hidden: false,
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
		{header:'Approver', dataIndex:'approver_by', sortable:true, width:80,renderer: columnWrap, hidden: false},
		{header:'Reviewer', dataIndex:'reviewer_by', sortable:true, width:80,renderer: columnWrap, hidden: false},
		{header:'Action',xtype:'actioncolumn', align:'center', width:40, hidden: true}
	]

	var columnGlEntryView = [
		{header:'ID', dataIndex:'trans_no', sortable:true, width:60, renderer: columnWrap,hidden: true},
		{header:'Account Code', dataIndex:'account_code_gl', sortable:true, width:80, renderer: columnWrap,hidden: false, align:'center',
			renderer: function(value, metaData, record, rowIndex, colIndex, store) {
				return '<span style="color:blue; font-weight:bold;">' + value + '</span>';
			}
		},
		{header:'Account Description', dataIndex:'account_name_gl', sortable:true, width:140, renderer: columnWrap,hidden: false},
		{header:'Mcode', dataIndex:'mcode_gl', sortable:true, width:50, renderer: columnWrap,hidden: false, align:'center'},
		{header:'Masterfile', dataIndex:'masterfile_gl', sortable:true, renderer: columnWrap,hidden: false, align:'center'},
		{header:'Debit', dataIndex:'debit_gl', sortable:true, width:80, hidden: false, align:'center',
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return '<span style="color:red; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:green; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			},
			summaryType: 'sum',
			summaryRenderer: function(value, summaryData, dataIndex){
				return '<span style="color:blue;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';									
			}
		},
		{header:'Credit', dataIndex:'credit_gl', sortable:true, width:80,renderer: columnWrap, hidden: false, align: 'center',
			renderer : function(value, metaData, summaryData, dataIndex){
				if (value==0) {
					return '<span style="color:red; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00');
				}else{
					return '<span style="color:green; font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
				}
			},
			summaryType: 'sum',
			summaryRenderer: function(value, summaryData, dataIndex){
				return '<span style="color:blue;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';									
			}
		},
		{header:'Memo', dataIndex:'memo_gl', sortable:true, renderer: columnWrap,hidden: false}
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

	var coasuggestlistingStore = Ext.create('Ext.data.Store', {
		fields: ['account_code', 'account_name', 'name',{name:'class_id',type:'int'},'class_name'],
		autoLoad: true,
		proxy : {
			type: 'ajax',
			url	: '?action=coasuggest_entry',
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
		height: 270,
		autoScroll:true,
        loadMask:true,
		//flex:1,
		//autoScroll: true,
		//height: 500,
		store: MerchandiseTransStore,
		columns: columnTransferModel,
		//selModel: {selType: 'cellmodel'},
		//plugins: [cellEditing1],
		selModel: 'cellmodel',
	    plugins: {
	        ptype: 'cellediting',
	        clicksToEdit: 1
	    },
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
					var categoryheader = Ext.getCmp('category').getValue();
					if(categoryheader==null){
						Ext.Msg.alert('Warning','Please select category');
						return false;	
					}	
					if(!windowItemList){
						var catcode = Ext.getCmp('category').getValue();
						var brcode = Ext.getCmp('fromlocation').getValue();
						var AdjDate = Ext.getCmp('AdjDate').getValue();
						var returntrans = Ext.getCmp('newtrans_no').getValue();
						var filtertype = Ext.getCmp('filter_type').getValue();
						ItemListingStore.proxy.extraParams = {catcode: catcode, branchcode:brcode, trans_date:AdjDate, returntrans:returntrans, filtertype:filtertype}
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
										//selModel: {selType: 'cellmodel'},
										selModel: {
											selType: 'checkboxmodel',
											id: 'checkidbox',
											checkOnly: true,
											mode: 'Multi'			
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
											{header:'RR Date', dataIndex:'tran_date', sortable:true, width:60, renderer: columnWrap,hidden: false, renderer: Ext.util.Format.dateRenderer('m/d/Y')
											},
											{header:'Model', dataIndex:'model', sortable:true, width:60, renderer: columnWrap,hidden: false},
											{header:'Item Description', dataIndex:'stock_description', sortable:true, renderer: columnWrap,hidden: false},
											{header:'Color', dataIndex:'item_description', sortable:true, renderer: columnWrap,hidden: false},
											{header:'Category', dataIndex:'category_id', sortable:true, width:100, renderer: columnWrap,hidden: true},
											{header:'Standard Cost', dataIndex:'standard_cost', sortable:true, width:70, hidden: false, align:'right'},
											{header:'Qty', dataIndex:'qty', sortable:true, width:50, hidden: false, align:'center',
												editor:{
													completeOnEnter: true,
													field:{
														xtype:'numberfield',
														allowBlank: false,
														hideTrigger: true,
														decimalPrecision: 2,
														listeners : {
														    keyup: function(grid, rowIndex, colIndex) {
															
									                    	},
															specialkey: function(f,e){
																if (e.getKey() == e.ENTER) {
																	
																}
															}
														}
													}
												},
												renderer:function(value, metaData, record, rowIdx, colIdx, store, view) {
									                return Ext.util.Format.number(value,'0,000.00');
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
													change: function(field){		
														var class_type = Ext.getCmp('searchSerialItem').getValue();
														ItemListingStore.proxy.extraParams = { 											 
															query:field.getValue(), serialquery: Ext.getCmp('searchSerial').getValue(), catcode: Ext.getCmp('category').getValue()
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
												listeners: {	
													change: function(field){		
														var class_type = Ext.getCmp('searchSerial').getValue();
														ItemListingStore.proxy.extraParams = { 											 
															query: Ext.getCmp('searchSerialItem').getValue(), serialquery:field.getValue(), catcode: Ext.getCmp('category').getValue()														
														}
														ItemListingStore.load();																															
													}		
												}
											}]
										}],
										bbar : {
											xtype : 'pagingtoolbar',
											store : ItemListingStore,
											pageSize: itemsPerPage,
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
											var rr_date = record.get('tran_date');	
											var lot_no = record.get('lot_no');	
											var chasis_no = record.get('chasis_no');	
											var AdjDate = Ext.getCmp('AdjDate').getValue();	
											var type_out = record.get('type_out');	
											var transno_out = record.get('transno_out');	
											var standard_cost = record.get('standard_cost');	
											var serialised = record.get('serialised');	
											var inventory_account = record.get('inventory_account');	

											Ext.toast({
												icon   	: '../js/ext4/examples/shared/icons/accept.png',
											    html: '<b>' + 'Model:' + record.get('model') + ' <br><br/> ' + 'Serial #:' + record.get('lot_no') + '<b/>',
											    title: 'Selected Item',
											    width: 250,
											    bodyPadding: 10,
											    align: 'tr'
											});		
										}

										//Ext.getCmp('item_model').setValue(record.get('model'));
										var masterfile_header = Ext.getCmp('masterfile_type_header').getValue();
										var mastercode = Ext.getCmp('masterfile_header').getValue();
										var mastercode_dtls = Ext.getCmp('masterfile_header').getRawValue();

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
												rr_date: record.get('tran_date'),	
												lot_no: record.get('lot_no'),	
												chasis_no: record.get('chasis_no'),	
												AdjDate: Ext.getCmp('AdjDate').getValue(),	
												type_out: record.get('type_out'),	
												transno_out: record.get('transno_out'),	
												standard_cost: record.get('standard_cost'),	
												serialised: record.get('serialised'),
												inventory_account: record.get('inventory_account'),
												masterfile: Ext.getCmp('masterfile_header').getRawValue(),
												mcode: Ext.getCmp('masterfile_header').getValue()												
											};
											gridRepoData.push(ObjItem);
										});

										if (gridRepoData == "") {
											Ext.MessageBox.alert('Error','Please Select Item..');
											return false;
										} else{
											MerchandiseTransStore.proxy.extraParams = {DataOnGrid: Ext.encode(gridRepoData), masterfile_header, mastercode, mastercode_dtls};
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
		layout:'fit',
		store: GLItemsStore,
		columns: columnJEModel,
		height: 270,
		autoScroll:true,
		//plugins: [rowEditing],
		//selModel: {selType: 'cellmodel'},
		//plugins: [cellEditing],
		selModel: 'cellmodel',
	    plugins: {
	        ptype: 'cellediting',
	        clicksToEdit: 1
	    },
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

										var mastertype_header = Ext.getCmp('masterfile_type_header').getValue();
															
										Ext.Ajax.request({
											url : '?action=AddGLItem&account_code='+account_code,
											method: 'POST',
											params:{
												AdjDate:AdjDate,
												mastertype_header:mastertype_header									
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
		title	: 'Complimentary Items',
		store	    : myInsurance,
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
				id: 'newtransaction',
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
							items:[{
									xtype:'fieldset',
									//title:'Complimentary Header',
									layout:'anchor',
									defaultType:'textfield',
									fieldDefaults:{
										labelAlign:'right'
									},
									items:[{
											xtype:'fieldcontainer',
											layout:'hbox',
											margin: '2 0 2 5',
											items:[{
												xtype:'combobox',
												fieldLabel:'Location From',
												name:'fromlocation',
												id:'fromlocation',
												queryMode:'local',
												triggerAction : 'all',
            									displayField  : 'location_name',
            									valueField    : 'loc_code',
            									editable      : true,
            									forceSelection: true,
                                                allowBlank: false,
            									required: true,
            									hiddenName: 'loc_code',
            									typeAhead: true,
            									width: 525,
            									emptyText:'Select Location',
            									fieldStyle: 'background-color: #F2F3F4; color: green; font-weight: bold;',
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
												queryMode:'local',
												triggerAction:'all',
												displayField  : 'description',
            									valueField    : 'category_id',
            									editable      : true,
            									forceSelection: true,
                                                allowBlank: false,
                                                labelWidth: 70,
            									required: true,
            									hiddenName: 'category_id',
            									typeAhead: true,
            									emptyText:'Select Category',
            									fieldStyle: 'background-color: #F2F3F4; color: green; font-weight: bold;',
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
                                           		width: 230,
												name:'trans_date',
												id:'AdjDate',
            									fieldStyle: 'background-color: #F2F3F4; color: black; font-weight: bold;',
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
												layout:'hbox',
												margin: '2 0 2 5',
												items:[{
												xtype:'textfield',
													fieldLabel:'Reference #',
                                               		width: 350, 
													name:'reference',
													id:'reference',
													layout:'anchor',
													anchor:'100%',
													fieldStyle: 'background-color: #F2F3F4; color: blue; font-weight: bold;',
													readOnly: true
													//flex:1
												},{
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
													labelWidth: 60,
													hiddenName: 'id',
													fieldStyle: 'background-color: #F2F3F4; color: green; font-weight: bold;',							
													listeners: {
														select: function(cmb, rec, idx) {
														MasterfileModel=Ext.getCmp('masterfile_header');
								                        MasterfileModel.clearValue();
														if( this.getValue()==99){
															MasterfileModel.store.load();
														}else if(this.getValue()==4) {
															Ext.ComponentQuery.query('grid gridcolumn[dataIndex^="suggest_description"]')[0].show();
															MasterfileModel.store.load({
																params: { 'masterfile_type': this.getValue()
																}
															});
														}else{
															Ext.ComponentQuery.query('grid gridcolumn[dataIndex^="suggest_description"]')[0].hide();
															MasterfileModel.store.load({
																params: { 'masterfile_type': this.getValue()
																}
															});
														}								
								                        MasterfileModel.enable();									
													}
												}
											},{
		                                        xtype:'combo',
		                                        fieldLabel:'Masterfile',
		                                        name:'masterfile_header',
												id:'masterfile_header',
		                                        anchor:'100%',
		                                        typeAhead: true,
                                                labelWidth: 80,
                                                width: 415,
												anyMatch: true,
									            triggerAction: 'all',
									            store: MasterfileStore,
												queryMode: 'local',
												displayField  : 'namecaption',
												valueField    : 'id',
												hiddenName: 'id',
												fieldStyle: 'background-color: #F2F3F4; color: green; font-weight: bold;',
												//flex:1,
												listeners:{
													change: function(combo, value,index){
														return decodeHtmlEntity(combo.getRawValue());
													}
												} 	
		                                    }]
										},{
											xtype:'fieldcontainer',
											layout:'hbox',
											margin: '2 0 2 5',
											items:[{
												xtype:'combo',
												fieldLabel:'Approved By',
												name:'approvedby',
												id:'approvedby',
												anchor:'100%',
												typeAhead:true,
												anyMatch:true,
												labelWidth: 100,
												width: 500,
												forceSelection: true,
                                                allowBlank: false,
												queryMode:'local',
												triggerAction: 'all',
												displayField  : 'real_name',
												valueField    : 'id',
												hiddenName: 'id',
												fieldStyle: 'background-color: #F2F3F4; color: green; font-weight: bold;',
            									emptyText:'Select Approver',
												store: Ext.create('Ext.data.Store',{
            										fields: ['id', 'real_name'],
                                            		autoLoad: true,
													proxy: {
														type:'ajax',
														url: '?action=approvedby_user',
														reader:{
															type : 'json',
															root : 'result',
															totalProperty : 'total'
														}
													}
            									})
											},{
												xtype:'combo',
												fieldLabel:'Reviewed By',
												name:'reviewedby',
												id:'reviewedby',											
												anchor:'100%',
												typeAhead:true,
												anyMatch:true,
												labelWidth: 100,
												width: 500,
												forceSelection: true,
                                                allowBlank: false,
												queryMode:'local',
												triggerAction: 'all',
												displayField  : 'real_name',
												valueField    : 'id',
												hiddenName: 'id',
												fieldStyle: 'background-color: #F2F3F4; color: green; font-weight: bold;',
            									emptyText:'Select Reviewer',
												store: Ext.create('Ext.data.Store',{
            										fields: ['id', 'real_name'],
                                            		autoLoad: true,
													proxy: {
														type:'ajax',
														url: '?action=reviwedby_user',
														reader:{
															type : 'json',
															root : 'result',
															totalProperty : 'total'
														}
													}
            									})
											}]
										},{
											xtype:'fieldcontainer',
											margin: '2 0 2 5',
											width: 1000,
											layout:'fit',
											items:[{
												xtype:'textareafield',
												fieldLabel:'Memo',
												name:'memo',
												id:'memo',
												grow: false,
												anchor:'100%'
											}]
										},{
											xtype:'textfield',
											fieldLabel:'Suggest Add:',
											readOnly: true,
											fieldStyle: 'font-weight: bold; color: #003168;text-align: right;',
											id:'suggest_blocking',
											name:'suggest_blocking',
											hidden: true
										},{
											xtype:'fieldcontainer',
											layout:'hbox',
											margin: '2 0 2 5',
											items:[
												{
													xtype:'textfield',
													fieldLabel:'Type:',
													readOnly: true,
													fieldStyle: 'font-weight: bold; color: #003168;text-align: right;',
													id:'filter_type',
													name:'filter_type',
													hidden: true
												},{
													xtype:'textfield',
													fieldLabel:'Trans No:',
													readOnly: true,
													fieldStyle: 'font-weight: bold; color: #003168;text-align: right;',
													id:'newtrans_no',
													name:'newtrans_no',
													hidden: true
												}
											]
										}
									]	
								},{
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
										},{
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
												/*MerchandiseTransStore.load({
													params:{view:1}
												});*/
											}
							            }
							        }
									
								},{
									xtype:'fieldcontainer',
									layout:'hbox',
									items:[
										{
											xtype:'textfield',
											fieldLabel:'DEBIT:',
											readOnly: true,
											fieldStyle: 'font-weight: bold; color: #003168;text-align: right;',
											id:'totaldebit'
										},{
											xtype:'textfield',
											fieldLabel:'CREDIT:',
											readOnly: true,
											fieldStyle: 'font-weight: bold; color: #003168;text-align: right;',
											id:'totalcredit'
										},{
											xtype:'textfield',
											fieldLabel:'MODEL:',
											readOnly: true,
											fieldStyle: 'font-weight: bold; color: #003168;text-align: right;',								
											id:'item_model',
											hidden: true					      
										}
									]
								}
							],
							buttons:[{
									text:'Process',
									id:'btnProcess',
									disabled: true,
									handler:function(){

										var gridData = MerchandiseTransStore.getRange();
										var gridRepoData = [];
										count = 0;
										Ext.each(gridData, function(item) {
											var ObjItem = {							
												qty: item.get('qty'),
												currentqty:item.get('currentqty'),												
												stock_id:item.get('stock_id'),
												standard_cost:item.get('standard_cost')													
											};
											gridRepoData.push(ObjItem);
										});

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

										var item_models = Ext.getCmp('item_model').getValue();

										var approver = Ext.getCmp('approvedby').getValue();
										var reviwer = Ext.getCmp('reviewedby').getValue(); 
										var suggest_blocking = Ext.getCmp('suggest_blocking').getValue();

										Ext.MessageBox.confirm('Confirm', 'Do you want to Process this transaction?', function (btn, text) {
											if (btn == 'yes') {
												if(FromStockLocation==null){
													setButtonDisabled(false);
													Ext.MessageBox.alert('Error','Select Branch FromLocation');
													return false;
												}
												if(catcode==null){
													setButtonDisabled(false);
													Ext.MessageBox.alert('Error','Select Category Item');
													return false;
												}
												if(person_id==null){
													setButtonDisabled(false);
													Ext.MessageBox.alert('Error','Please select masterfile');
													return false;
												}
												if(approver==null){
													setButtonDisabled(false);
													Ext.MessageBox.alert('Error','Select Approver');
													return false;
												}
												if(reviwer==null){
													setButtonDisabled(false);
													Ext.MessageBox.alert('Error','Select Reviewer');
													return false;
												}
												if (person_type == 4) {
													if (suggest_blocking=='') {
														setButtonDisabled(false);
														Ext.MessageBox.alert('Error','Please select suggested entry');
														return false;
													}													
												}
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
														memo_: memo_,
														totaldebit:totaldebit,
														totalcredit: totalcredit,
														ref: reference,
														person_type: person_type,
														person_id: person_id,
														masterfile: masterfile,
														item_models: item_models,
														approver: approver,
														reviwer: reviwer,
														Dataongrid: Ext.encode(gridRepoData)
													},
													success: function(response){
														Ext.MessageBox.hide();
														var jsonData = Ext.JSON.decode(response.responseText);
														var errmsg = jsonData.errmsg;
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
											}
										});
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
					GetUrlParamsType();	
				},
				scale	: 'small'
			},{
				xtype: 'combobox',
				fieldLabel:'Status:',
				name:'compli_status',
                id:'compli_status',
                typeAhead: true,
	            triggerAction: 'all',
	            store: StatusFileStore,
				displayField  : 'namecaption',
				valueField    : 'stat_id',
				editable      : false,
				forceSelection: true,
				required: true,
				labelWidth: 60,
				hiddenName: 'stat_id',
				emptyText:'Select Status',
				fieldStyle: 'background-color: #F2F3F4; color: green; font-weight: bold;',
				listeners: {
					select: function(combo, record, index) {
						myInsurance.proxy.extraParams = {comp_stat: this.getValue(), search_ref: Ext.getCmp('search_ref').getValue()}
						myInsurance.load();		
					}
				}	
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
						myInsurance.proxy.extraParams = {comp_stat: Ext.getCmp('compli_status').getValue(), search_ref: field.getValue()};
						myInsurance.load();
					}
				}
			}]
		}],
		bbar : {
			xtype : 'pagingtoolbar',
			store : myInsurance,
			pageSize: itemsPerPage,
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

	Ext.Ajax.request({
		url : '?action=UserRoleId',
		method: 'GET',
		success: function (response){
			Ext.MessageBox.hide();
			var jsonData = Ext.JSON.decode(response.responseText);
			var user_role = jsonData.user_role;
			if(user_role == 19){
				Ext.getCmp('newtransaction').setDisabled(true);
			}else{
				Ext.getCmp('newtransaction').setDisabled(false);
			}

			UserRoleIdStore.load();
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
			if(DebitTotal==CreditTotal /*&& (DebitTotal!=0 || CreditTotal!=0)*/){
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
/*function setSaveButtonHidden(account_code=0) {
	if(account_code == 104001) {
		Ext.getCmp('btnUpdateM').setVisible(false);
	}else if(account_code == 104002) {
		Ext.getCmp('btnUpdateM').setVisible(false);
	}else if(account_code == 104003) {
		Ext.getCmp('btnUpdateM').setVisible(false);
	}else if(account_code == 104004) {
		Ext.getCmp('btnUpdateM').setVisible(false);
	}else if(account_code == 104005) {
		Ext.getCmp('btnUpdateM').setVisible(false);
	}else if(account_code == 104006) {
		Ext.getCmp('btnUpdateM').setVisible(false);
	}else if(account_code == 104007) {
		Ext.getCmp('btnUpdateM').setVisible(false);
	}else if(account_code == 104008) {
		Ext.getCmp('btnUpdateM').setVisible(false);
	}else if(account_code == 104009) {
		Ext.getCmp('btnUpdateM').setVisible(false);
	}else if(account_code == 104010) {
		Ext.getCmp('btnUpdateM').setVisible(false);
	}
};*/
/*function setSaveButtonHidden1(description) {
	var found = escapeRegExp(description);
	const match_found =	Boolean(found.match('^(.*)Branch Current'));	
	const match_found_ho =	Boolean(found.match('^(.*)Home Office Current'));
	if(match_found == true) {
		Ext.getCmp('btnUpdateM').setVisible(false);
	}
	if(match_found_ho == true) {
		Ext.getCmp('btnUpdateM').setVisible(false);
	}
};*/
function GetUrlParamsType(){
	let form_values = {}
	let url_string = window.location.search.substring(1);
	let url_string_array = url_string.split('&');

	url_string_array.forEach(function( value ){
		let this_item = value.split('=');
		let this_key = this_item[0];
		let this_value = this_item[1];
		form_values[this_key] = unescape(this_value);
	});

	var sales_return = form_values['NewSalesReturn'];
	var filter_type = form_values['Filter_type'];
	var category_return = form_values['Category'];
	
	Ext.getCmp('newtrans_no').setValue(sales_return);
	Ext.getCmp('filter_type').setValue(filter_type);
	Ext.getCmp('category').setValue(category_return);
};
/*function escapeRegExp(str) {
	return str.replace(/[\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\&");
}*/
