


function window_show(trans_no){
	var newURL = window.location.protocol + "//" + window.location.host + window.location.pathname;
	Ext.Loader.setConfig({enabled: true});
	Ext.Loader.setPath('Ext.ux', newURL+'/../../js/ext4/examples/ux/');
	Ext.require(['Ext.toolbar.Paging',
    'Ext.ux.form.SearchField',
	'Ext.layout.container.Column',
    'Ext.tab.*',
	'Ext.window.MessageBox',
	'Ext.grid.*']);
	
	var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
        clicksToEdit: 1
    });
	
	var SerialItemStore = Ext.create('Ext.data.Store', {
		fields: ['serialise_id','serialise_qty','serialise_lot_no','serialise_manufacture_date','serialise_expire_date','serialise_out_qty','sods_id','serialise_avail_qty'],
		autoLoad: false,
		proxy : {
			type: 'ajax',
			url	: '?action=serialitemsdetails',
			reader:{
				type : 'json',
				root : 'result',
				totalProperty : 'total'
			}
		}
	});

	var columnSerialItemDetailsModel = [
		{header:'SODS ID', dataIndex:'sods_id', sortable:true, width:50},
		{header:'ID', dataIndex:'serialise_id', sortable:true, width:50},
		{header:'On-Hand<br/>Qty', dataIndex:'serialise_qty', sortable:false, width:80,align:'center'},
        {header:'Lot No.', dataIndex:'serialise_lot_no', sortable:false, width:150},
		{header:'Manufacture<br/>Date', dataIndex:'serialise_manufacture_date', sortable:false, align:'center',width:100},
		{header:'Expire<br/>Date', dataIndex:'serialise_expire_date', sortable:true,width:100,align:'center'},
		{header:'Qty Out', dataIndex:'serialise_out_qty', sortable:false,align:'center', width:80,
		renderer: function(value, metaData, record, rowIdx, colIdx, store, view){
			
            return value;
        },
		field:{
			xtype:'numberfield',
			//enableKeyEvents: true,
			//id:'recvalue',
			hidden: false,
			listeners: {
                    keyup: function(grid, rowIndex, colIndex) {
						//var record = GRNItemsStore.getAt(rowIndex);
                        //console.log('Keup Logs');
						
                    },
                    change: function() {
                        //console.log('On Change Log');
						//var record = GRNItemsStore.getAt(rowIndex);
						//if(value>record.get('serialise_qty')){
						//	Ext.MessageBox.alert('Error', "Qty Item Out should not be greater the Avail Qty");	
						//}
                    }
                }
		}},
		{header:'Available<br/>Qty', dataIndex:'serialise_avail_qty', sortable:false, width:80,align:'center'},
        {header:'Action', xtype:'actioncolumn', align:'center', width:50, hidden: false,
			items:[{
				icon: newURL+'/../../js/ext4/examples/shared/icons/delete.png',
				tooltip : 'Update',
				handler: function(grid, rowIndex, colIndex){
					var record = SerialItemStore.getAt(rowIndex);
					var deleteID = record.get('sods_id');
					Ext.MessageBox.confirm('Confirm', 'Do you want to delete record? ', function (btn, text) {
						if (btn == 'yes') {
							var grn_item_id = Ext.getCmp('grn_item_id').getValue();
							var grnid = Ext.getCmp('grnid').getValue();
							
							Ext.Ajax.request({
								url : '?action=delete',
								params : {
									sods_id : deleteID
								},
								method: 'POST',
								success: function (response){
									SerialItemStore.load({
										params: { 'item_code': grn_item_id,'id': grnid}
									});
									GRNItemsStore.load();									
								},	
								failure: function (response){
									SerialItemStore.load();
									Ext.Msg.alert('Error', 'Deleting ' + records.get('Name_fields'));
								}
							});
						}
					});	
						
				}
			}]/* ,
			getClass: function(v, meta, rec) {          // Or return a class from a function
				if (rec.get('request_status') != 9) {
					this.items[1].tooltip='Reply Message';
					return 'reply-col';
				} else {
					this.items[1].tooltip = 'Close Ticket';
					return 'noreply-col';
				}
			} */
		}
	];
	
	var GRNItemsStore = Ext.create('Ext.data.Store', {
		fields: ['id','grnitem_id','grnitem_name','grnitem_qty','grnitem_unit','grnitem_price','grnitem_discount','grnitem_total','grnitem_qty_sold'],
		autoLoad: true,
		proxy : {
			type: 'ajax',
			url	: '?action=serialitems&trans_no='+trans_no,
			reader:{
				type : 'json',
				root : 'result',
				totalProperty : 'total'
			}
		}
	});
	var columnSerialItemModel = [
		{header:'ID', dataIndex:'id', sortable:true, width:50},
		{header:'Item Code', dataIndex:'grnitem_id', sortable:true, width:100},
		{header:'Item Name', dataIndex:'grnitem_name', sortable:true},
        {header:'Qty', dataIndex:'grnitem_qty', sortable:false, width:70, align:'right'},
        {header:'Qty<br/>Served', dataIndex:'grnitem_qty_sold', sortable:false, width:70, align:'right'},
        {header:'Units', dataIndex:'grnitem_unit', sortable:false, width:50},
		{header:'Price', dataIndex:'grnitem_price', sortable:false, align:'right',width:100},
		{header:'Discount', dataIndex:'grnitem_discount', sortable:true},
		{header:'Total', dataIndex:'grnitem_total', sortable:false,align:'right', width:100},
		{header:'Action', xtype:'actioncolumn', align:'center', width:50, hidden: false,
			items:[{
				icon: newURL+'/../../js/ext4/examples/shared/icons/application_view_columns.png',
				tooltip : 'Update',
				handler: function(grid, rowIndex, colIndex){
					var record = GRNItemsStore.getAt(rowIndex);
					id = record.get('id');
					grnitem_id = record.get('grnitem_id');
					grnitem_name = record.get('grnitem_name');
					grnitem_qty = record.get('grnitem_qty');
					Ext.getCmp('grnqty').setValue(grnitem_qty);
					Ext.getCmp('grn_item_id').setValue(grnitem_id);
					Ext.getCmp('grnid').setValue(id);
					SerialItemStore.load({
						params: { 'item_code': grnitem_id,'id': id}
					});
					edit_insurance_win.setTitle(grnitem_name);
					edit_insurance_win.show();	
						
				}
			}]
		}
	];

	var insurance_info = {
		xtype           :'gridpanel',
		frame: false,
		collapsible     :false,
		forceFit 	: true,
		border: false,
		frame: false,
			viewConfig: {
				stripeRows: true
			},
			store		: SerialItemStore,
			columns 	: columnSerialItemDetailsModel,
			forceFit 	: true,
			plugins: [cellEditing]
	};


	var edit_insurance_win = Ext.create('Ext.Window', {
		width 	: 800,
		height:600,
		title 	: 'Available Serial Items',
		modal	: true,
		border 	: false,
		layout:'fit',
		closeAction: 'hide',
		//buttonAlign : 'center',
		items	:[{
				xtype:'textfield',
				id:'grnqty',
				name:'grnqty',
				hidden: true
			},{
				xtype:'textfield',
				id:'grn_item_id',
				name:'grn_item_id',
				hidden: true
			},{
				xtype:'textfield',
				id:'grnid',
				hidden: true
			},{
				xtype		: 'panel',
				frame 		: true,
				autoScroll: true,
				buttonAlign : 'center',
				defaultType : 'field',
				defaults 	: {
					msgTarget 	: 'side',
					border      : false,
					anchor		: '100%'
				},
				items : [insurance_info],
				buttons : [{
						text : 'Save',
						handler : function()
						{
							var grnqty_item = Ext.getCmp('grnqty').getValue();
							var grn_item_id = Ext.getCmp('grn_item_id').getValue();
							var grnid = Ext.getCmp('grnid').getValue();
							var text1="";
							var qty=0;
							var qty_out=0;
							var qty_out_total = 0;
							var errorqty='';
							SerialItemStore.each(function(record) {
								qty=record.get("serialise_qty");
								qty_out=record.get("serialise_out_qty");
								if(typeof qty_out == 'number')
								{
									if(qty_out>grnqty_item){
										errorqty="One of your Qty Out is greater then GRN Qty";
										//break;
									}else if(qty_out>qty){
										errorqty="One of your Qty Out is greater then Avail Qty";
										//break;
									}
									if(qty_out!=null){
										qty_out_total+=qty_out;
									}
								}
							});
							if(errorqty!=''){
								Ext.MessageBox.alert('Save Error', errorqty);
							}else if(qty_out_total==0 || qty_out_total==null || qty_out_total==''){
								Ext.MessageBox.alert('Save Error', "Qty Out should not be empty");
							}else if(qty_out_total>grnqty_item){
								Ext.MessageBox.alert('Save Error', "Qty Out should not be Greater then GRN Qty");
							}else{
								qty=0;
								qty_out=0;
								qty_out_total = 0;
								SerialItemStore.each(function(record) {
									qty=record.get("serialise_qty");
									qty_out=record.get("serialise_out_qty");
									serialise_id=record.get("serialise_id");
									if(typeof qty_out == 'number')
									{
										Ext.Ajax.request({
											url : '?action=save',
											method: 'POST',
											params : {
												qty_out : qty_out,
												item_serialise_id:serialise_id,
												grn_item_id:grnid												
											},
												success: function (response){
											},	
											failure: function (response){
												Ext.Msg.alert('Error', 'Searching Error');
											}
										});
									}
								});
								SerialItemStore.load({params:{'item_code': grn_item_id,'id': grnid}});
								GRNItemsStore.load();	
								Ext.MessageBox.alert("Branch Code Added", 'Successfully added');
									
							
							}
							
						}
					},{
						text : 'Close',
						handler: function(){
							//Ext.getCmp('edit_form').getForm().reset();
							edit_insurance_win.hide();
						}
					}]
			 }]
	});

	

	var preview_serialentry = Ext.create('Ext.Window',{
		title: 'Serial Items Listing '+trans_no,
		width: 900,
		height: 500,
		closeAction:'hide',
		items:[{
			xtype:'panel',
			frame: false,
			autoScroll: true,
			border: false,
			items:[{
					xtype		:'gridpanel',
					store		: GRNItemsStore,
					columns 	: columnSerialItemModel,
					forceFit 	: true,
					border: false,
					frame: false,
					viewConfig: {
						stripeRows: true
					}

				}

			]

		}],
		buttons:[
			{
				text:'Close',
				handler:function(){
					preview_serialentry.hide();
				}
			}
		],
		layout:'fit',
		modal: true
	}).show();
}