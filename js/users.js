


function window_show(admin_id){
	var newURL = window.location.protocol + "//" + window.location.host + window.location.pathname;
	Ext.Loader.setConfig({enabled: true});
	Ext.Loader.setPath('Ext.ux', newURL+'/../../js/ext4/examples/ux/');
	Ext.require(['Ext.toolbar.Paging',
    'Ext.ux.form.SearchField',
	'Ext.layout.container.Column',
    'Ext.tab.*',
	'Ext.window.MessageBox',
	'Ext.grid.*']);
	


	Ext.define('DataObject1', {
		extend: 'Ext.data.Model',
		fields: ['branch_id','branch_area','branch_area_name','branch_code','branch_name','admin_id','admin_branches_id']
	});
	Ext.define('DataObject2', {
		extend: 'Ext.data.Model',
		fields: ['branch_id','branch_area','branch_area_name','branch_code','branch_name','admin_id','admin_branches_id','canrequest','admin_branches_canrequest','admin_branches_canreview','admin_branches_cannoted','admin_branches_canapprove']
	});

	var columnlist =[
		{header:'Area', dataIndex:'branch_area_name', sortable:true, width:120, align: 'left'},
		{header:'Branch Code', dataIndex:'branch_code', sortable:true, width:150, align: 'left'},
		{header:'Branch Name', dataIndex:'branch_name', sortable:true, flex:1, align: 'left'}
	];
	var columnlist2 =[
		{header:'Branch Area', dataIndex:'branch_area_name', sortable:true, width:100, align: 'left'},
		{header:'Branch', dataIndex:'branch_code', sortable:true, width:150, align: 'left',hidden:true},
		{header:'Branch Name', dataIndex:'branch_name', sortable:true, flex:1, align: 'left',hidden:false},
		{header:'Request', dataIndex:'admin_branches_canrequest', width:60},
		{header:'Review', dataIndex:'admin_branches_canreview', width:60},
		{header:'Noted', dataIndex:'admin_branches_cannoted', width:60},
		{header:'Approve', dataIndex:'admin_branches_canapprove', width:60},
		{xtype:'actioncolumn',header:'Action',width:50, align:'center',
			items:[
				{
					icon: '../js/ext4/examples/shared/icons/application_view_columns.png',
					tooltip: 'Preview',
					handler: function(grid, rowIndex, colIndex){														
						if(!canapproving){
							var canapproving = Ext.create('Ext.Window',{
								title:'Access Control ',
								width: 300,
								layout: 'anchor',
								defaultType: 'checkbox',
								defaults: {
									anchor: '100%',
									hideEmptyLabel: false
								},
								items: [{
									boxLabel: 'Can Request',
									name: 'canrequest',
									id:'canrequest',
									inputValue: 'canrequest'
								}, {
									boxLabel: 'Can Review',
									name: 'canreview',
									inputValue: 'canreview',
									id:'canreview'
								}, {
									boxLabel: 'Can Noted',
									name: 'cannoted',
									inputValue: 'cannoted',
									id:'cannoted'
								}, {
									boxLabel: 'Can Approved',
									name: 'canapprove',
									inputValue: 'canapprove',
									id:'canapprove'                                                                            
								}],
								buttons:[
									{
										text:'Save',
										handler: function(){
											var canrequest = Ext.getCmp('canrequest').getValue();
											var canreview = Ext.getCmp('canreview').getValue();
											var canapprove = Ext.getCmp('canapprove').getValue();														 var cannoted = Ext.getCmp('cannoted').getValue();
											Ext.Ajax.request({
												url : '?add_accesscontrol=1&admin_id='+admin_id+'&admin_branches_id='+admin_branches_id+'&admin_branches_canrequest='+canrequest+'&admin_branches_canreview='+canreview+'&admin_branches_cannoted='+cannoted+'&admin_branches_canapprove='+canapprove,
												method: 'POST',
												success: function (response){
													SecondTableStore.load({
														params:{admin_id: admin_id}
													});	
													canapproving.close();
													canapproving=null;
													Ext.example.msg("Branch Code Removed", 'Successfully remove Branch Code: '+admin_branches_id+' '+canrequest);
												},	
												failure: function (response){
													Ext.Msg.alert('Error', 'Removed Error');
												}
											});

										}
									},{
										text:'Close',
										handler: function(){
											canapproving.close();
											canapproving=null;
										}
									}
								]
							});
						}
						var record = SecondTableStore.getAt(rowIndex);
						admin_id = record.get('admin_id');
						branch_id = record.get('branch_id');
						admin_branches_id = record.get('admin_branches_id');
						admin_branches_canrequest = record.get('admin_branches_canrequest');
						admin_branches_canreview = record.get('admin_branches_canreview');
						admin_branches_cannoted = record.get('admin_branches_cannoted');
						admin_branches_canapprove = record.get('admin_branches_canapprove');
						
						if(admin_branches_canrequest==1){
							Ext.getCmp('canrequest').setValue(true);
						}else{
							Ext.getCmp('canrequest').setValue(false);
						}
						if(admin_branches_canreview==1){
							Ext.getCmp('canreview').setValue(true);
						}else{
							Ext.getCmp('canreview').setValue(false);
						}
                        if(admin_branches_cannoted==1){
							Ext.getCmp('cannoted').setValue(true);
						}else{
							Ext.getCmp('cannoted').setValue(false);
						}
						if(admin_branches_canapprove==1){
							Ext.getCmp('canapprove').setValue(true);
						}else{
							Ext.getCmp('canapprove').setValue(false);
						}
											
						canapproving.show();
					}
				}
			]
		}
	];	
	
	var FirstTableStore = Ext.create('Ext.data.Store', {
		model: 'DataObject1',
		autoLoad: true,
		proxy : {
			type: 'ajax',
			url	: '?branchlistingleft=1&admin_id='+admin_id,
			reader:{
				type : 'json',
				root : 'result',
				totalProperty : 'total'
			}
		}
	});	
	var SecondTableStore = Ext.create('Ext.data.Store', {
		model: 'DataObject2',
		autoLoad: true,
		proxy : {
			type: 'ajax',
			url	: '?branchlistingright=1&admin_id='+admin_id,
			reader:{
				type : 'json',
				root : 'result',
				totalProperty : 'total'
			}
		}
	});	
		
	var addsmiwindow = Ext.create('Ext.Window',{
			title:'Branch Users Setup',
			modal: true,			
			plain: true,
			closeAction: 'hide',
			items:[
				{
					xtype:'panel',
					width: 1200,
					height: 400,
					layout: {
						type:'hbox',
						align:'stretch',
						padding: 5
					},
					margins          : '0 2 0 0',
					frame: false,
					autoScroll: true,
					border: false,
					defaults     : { flex : 1 }, //auto stretch
					items:[
						{
							xtype		:'gridpanel',
							multiSelect: false,
							store		: FirstTableStore,
							columns 	: columnlist,
							//width:200,
							//forceFit 	: true,
							viewConfig: {
								stripeRows: true,
								plugins: {
									ptype: 'gridviewdragdrop',
									dragGroup: 'firstGridDDGroup',
									dropGroup: 'secondGridDDGroup'
								},
								listeners: {
									drop: function(node, data, dropRec, dropPosition) {
										var dropOn = dropRec ? ' ' + dropPosition + ' ' + dropRec.get('branch_code') : ' on empty view';
										Ext.Ajax.request({
											url : '?remove_branchaccess=1&branch_code='+data.records[0].get('branch_code')+'&admin_id='+admin_id+'&admin_branches_id='+data.records[0].get('admin_branches_id'),
											method: 'POST',
											success: function (response){
												FirstTableStore.load({
														params:{admin_id: admin_id}
													});	
												Ext.example.msg("Branch Code Removed", 'Successfully remove Branch Code: ' + data.records[0].get('branch_code'));
											},	
											failure: function (response){
												Ext.Msg.alert('Error', 'Removed Error');
											}
										});							
									}
								}
							}
						},
						{
							xtype		:'gridpanel',
							store		: SecondTableStore,
							columns 	: columnlist2,
							//flex:1,
							//forceFit 	: true,
							margins          : '0 0 0 3',
							viewConfig: {
								stripeRows: true,
								plugins: {
									ptype: 'gridviewdragdrop',
									dragGroup: 'secondGridDDGroup',
									dropGroup: 'firstGridDDGroup'
								},
								listeners: {
									drop: function(node, data, dropRec, dropPosition) {
										var dropOn = dropRec ? ' ' + dropPosition + ' ' + dropRec.get('branch_code') : ' on empty view';
										Ext.Ajax.request({
											url : '?save_branchaccess=1&branch_code='+data.records[0].get('branch_code')+'&admin_id='+admin_id,
											method: 'POST',
											success: function (response){
												FirstTableStore.load({
														params:{admin_id: admin_id}
													});	
												Ext.example.msg("Branch Code Added", 'Successfully added Branch Code: ' + data.records[0].get('branch_code'));
											},	
											failure: function (response){
												Ext.Msg.alert('Error', 'Searching Error');
											}
										});
									}
								}
 							}
						}
					]
				}
			],
			buttons:[
				{
					text:'Close',
					handler: function(){
						addsmiwindow.hide();	
					}
				}
			]
		}).show();	
}