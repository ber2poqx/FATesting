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
	'Ext.selection.CellModel',
	'Ext.form.field.File',
	'Ext.ux.form.SearchField',
	'Ext.ux.form.NumericField'

]);

Ext.onReady(function(){
	Ext.QuickTips.init();
	var itemsPerPage = 24;   // set the number of items you want per page on grid.
	var showall = false;

	var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
        clicksToEdit: 1
    });

    Ext.define('COLLECTIONAMOUNT',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'collect_date', mapping:'collect_date'},
			{name:'amount', mapping:'amount'},
			{name:'collect_date1', mapping:'collect_date1'}		
		]
    });
    
	//------------------------------------: stores :----------------------------------------
	var CollectionAmountDetailsStore = Ext.create('Ext.data.Store', {
		model: 'COLLECTIONAMOUNT',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_percenatge_amount=00',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'YEAR_DATE',
			direction : 'DESC'
		}]
	});
	//-----------------------------------//
	var ColumnModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Date</b>', dataIndex:'collect_date', sortable:true, width:155,
			renderer: Ext.util.Format.dateRenderer = function(value){
				return '<span style="color:black;font-weight:bold;">' + Ext.util.Format.date(value, 'm/d/Y') +'</span>';
			}
		},
		{header:'<b>Amount</b>', dataIndex:'amount', sortable:true, width:155,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value, '00,000') +'</span>';
			}
		},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:113,
			items:[{
				icon: '../../js/ext4/examples/shared/icons/layout_content.png',
				tooltip: 'Update details',
				handler: function(grid, rowIndex, colIndex){
					submit_form.getForm().reset();
					var records = CollectionAmountDetailsStore.getAt(rowIndex);
					
					
					Ext.getCmp('buttonsave').setVisible(false);
					Ext.getCmp('buttonupdate').setVisible(true);
					Ext.getCmp('buttonupdate').setText('Update');
					Ext.getCmp('buttoncancel').setText('Cancel');

					Ext.getCmp('collect_date').setVisible(false);
					Ext.getCmp('collect_date1').setVisible(true);


					Ext.getCmp('id').setValue(records.get('id'));
					Ext.getCmp('amount').setValue(records.get('amount'));
					Ext.getCmp('collect_date').setValue(records.get('collect_date'));

					Ext.getCmp('collect_date1').setValue(records.get('collect_date'));


					submit_window.show();
					submit_window.setTitle('Collection Amount Target - Date :'+ '   ' + records.get('collect_date'));
					submit_window.setPosition(400,300);								
				}
			},'-',{
				icon   : '../../js/ext4/examples/shared/icons/fam/delete.png',
				tooltip: 'Delete',
				handler: function(grid, rowIndex, colIndex) {
					var records = CollectionAmountDetailsStore.getAt(rowIndex);
					var MsgConfirm = Ext.MessageBox.confirm('Confirm', 'Trans. No. : <b>' + records.get('id') + '   ' + '& Date:' + '   ' + records.get('collect_date') + '-' + '</b> <b><b>Are you sure you want to remove this record? </b>', function (btn, text) {	
						if (btn == 'yes') {
							Ext.Ajax.request({
								method: 'POST',
								url: '?delete=info',
								waitMsg:'Deleting Record...please wait.',
								params: {

									collect_date: records.get('collect_date'),
								},
								success: function (response){
									var data = Ext.decode(response.responseText);
									if (data.success == 'true') {
										Ext.Msg.alert('Success', data.message);
										CollectionAmountDetailsStore.load();
									}else{
										Ext.Msg.alert('Error', data.message);
									}
								}
							});
						}
					});
					MsgConfirm.defaultButton = 2;
				}
			}]
		}
	];
	

	var tbar = [{
		xtype: 'searchfield',
		id:'search',
		name:'search',
		fieldLabel: '<b>Search</b>',
		labelWidth: 50,
		width: 200,
		emptyText: "Search by Date",
		scale: 'large',
		store: CollectionAmountDetailsStore,
		listeners: {
			change: function(field) {
				var collectionamtGrd = Ext.getCmp('collectionamtGrd');

				var selected = collectionamtGrd.getSelectionModel().getSelection();
				var areaselected;
				Ext.each(selected, function (item) {
					areaselected = item.data.id;
				});

				if(field.getValue() != ""){
					CollectionAmountDetailsStore.proxy.extraParams = {REFRENCE: areaselected, showall: showall, query: field.getValue()};
					CollectionAmountDetailsStore.load();
				}else{
					CollectionAmountDetailsStore.proxy.extraParams = {REFRENCE: areaselected, showall: showall};
					CollectionAmountDetailsStore.load();
				}
			}
		}
	}, '-', {
		text:'<b>Add</b>',
		tooltip: 'Add new collection target transaction - Amount.',
		icon: '../../js/ext4/examples/shared/icons/add.png',
		scale: 'small',
		handler: function(){
			submit_form.getForm().reset();

			Ext.getCmp('buttonupdate').setVisible(false);
			Ext.getCmp('buttonsave').setVisible(true);

			Ext.getCmp('collect_date').setVisible(true);
			Ext.getCmp('collect_date1').setVisible(false);

			submit_window.show();
			submit_window.setTitle('Collection Amount Target - Add');
			submit_window.setPosition(400,300);
		}
	}];
	var submit_form = Ext.create('Ext.form.Panel', {
		id: 'form_submit',
		model: 'COLLECTIONAMOUNT',
		frame: false,
		border: true,
		defaults: {msgTarget: 'side', labelWidth: 95, anchor: '-10'}, 
		items: [{
			xtype: 'panel',
			id: 'mainpanel',
			items: [{
				xtype: 'panel',
				id: 'upanel',
				xtype: 'fieldcontainer',
				layout: 'hbox',
				margin: '2 0 2 0',
				items:[{				
					xtype : 'datefield',
					id	  : 'collect_date',
					name  : 'collect_date',
					fieldLabel : '<b>Date</b>',
					labelAlign:	'top',
					//labelWidth: 80,
					margin: '2 0 0 85',
					width: 130,
					format : 'm/d/Y',
					allowBlank: false,
					readOnly: false,				
					fieldStyle: 'font-weight: bold; color: #210a04;',
					value: Ext.Date.format(new Date(), 'Y-m-d')
				},{
					xtype : 'datefield',
					id	  : 'collect_date1',
					name  : 'collect_date1',
					fieldLabel : '<b>Date</b>',
					labelAlign:	'top',
					//labelWidth: 80,
					margin: '2 0 0 85',
					width: 130,
					format : 'm/d/Y',
					allowBlank: false,
					readOnly: true,				
					fieldStyle: 'font-weight: bold; color: #210a04;',
					value: Ext.Date.format(new Date(), 'Y-m-d'),
					hidden: true
				},{
					xtype : 'textfield',
					id	  : 'id',
					name  : 'id',
					margin: '2 0 0 85',
					width: 90,
					allowBlank: true,
					fieldStyle: 'font-weight: bold; color: #210a04;',
					readOnly: true,
					hidden: true
				},{	
					xtype: 'numericfield',
					id: 'amount',
					name: 'amount',
					fieldLabel: '<b>Amount</b>',
					allowBlank: true,
					readOnly: false,				
					width: 130,						
					margin: '2 0 0 20',
					labelAlign:	'top',
					//value: '%',						
					fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
				}]		
			}]
		}]
	});
	var submit_window = Ext.create('Ext.Window',{
		id: 'submit_window',
		width 	: 460,
		modal	: true,
		plain 	: true,
		border 	: true,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form],
		buttons:[{
			text: 'Save',
			id: 'buttonsave',
			tooltip: 'Save Collection Target - Amount',
			icon: '../../js/ext4/examples/shared/icons/add.png',
			single : true,
			handler:function(){
				var form_submit = Ext.getCmp('form_submit').getForm();
				if(form_submit.isValid()) {
					form_submit.submit({
						url: '?submit=repoinfo',
						waitMsg: 'Processing transaction. please wait...',
						method:'POST',
						submitEmptyText: false,
						success: function(form_submit, action) {
							CollectionAmountDetailsStore.load()
							Ext.Msg.alert('Success!', '<font color="green">' + action.result.message + '</font>');
							submit_window.close();
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
			text: 'Update',
			id: 'buttonupdate',
			tooltip: 'Update Collection Target - Amount',
			icon: '../../js/ext4/examples/shared/icons/add.png',
			single : true,
			handler:function(){
				var form_submit = Ext.getCmp('form_submit').getForm();
				if(form_submit.isValid()) {
					form_submit.submit({
						url: '?update=collectinfo',
						waitMsg: 'Processing transaction. please wait...',
						method:'POST',
						submitEmptyText: false,
						success: function(form_submit, action) {
							CollectionAmountDetailsStore.load()
							Ext.Msg.alert('Success!', '<font color="green">' + action.result.message + '</font>');
							submit_window.close();
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
			id: 'buttoncancel',
			tooltip: 'Cancel adding collection target - Amount',
			icon: '../../js/ext4/examples/shared/icons/cancel.png',
			handler:function(){
				Ext.MessageBox.confirm('Confirm:', 'Are you sure you wish to close this window?', function (btn, text) {
					if (btn == 'yes') {
						submit_window.close();					
					}
				});
			}
		}]
	});
	//------------------------------------: main grid :----------------------------------------
	var COLLECTION_AMOUNT_GRID =  Ext.create('Ext.panel.Panel', { 
        renderTo: 'ext-form',
		id: 'COLLECTION_AMOUNT_GRID',
        frame: false,
		width: 460,
		tbar: tbar,
		items: [{
			xtype: 'grid',
			title: 'Collection Amount Target - Details',
			id: 'collectionamtGrd',
			store:	CollectionAmountDetailsStore,
			columns: ColumnModel,
			columnLines: true,
			autoScroll:true,
			layout:'fit',
			frame: true,
			bbar : {
				xtype : 'pagingtoolbar',
				hidden: false,
				store : CollectionAmountDetailsStore,
				pageSize : itemsPerPage,
				displayInfo : false,
				emptyMsg: "No records to display",
				doRefresh : function(){
					CollectionAmountDetailsStore.load();
					
				}
			}
		}]
	});
});
