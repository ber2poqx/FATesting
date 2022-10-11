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
	var itemsPerPage = 20;   // set the number of items you want per page on grid.
	var showall = false;

	var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
        clicksToEdit: 1
    });

    Ext.define('ProofCashModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'trans_no', mapping:'trans_no'},
			{name:'tran_date', mapping:'tran_date'},
			{name:'one_thousand', mapping:'one_thousand'},
			{name:'one_thousand_qty', mapping:'one_thousand_qty'},
			{name:'five_hundred', mapping:'five_hundred'},
			{name:'five_hundred_qty', mapping:'five_hundred_qty'},
			{name:'two_hundred', mapping:'two_hundred'},
			{name:'two_hundred_qty', mapping:'two_hundred_qty'},
			{name:'one_hundred', mapping:'one_hundred'},
			{name:'one_hundred_qty', mapping:'one_hundred_qty'},
			{name:'fifty', mapping:'fifty'},
			{name:'fifty_qty', mapping:'fifty_qty'},
			{name:'twenty', mapping:'twenty'},
			{name:'twenty_qty', mapping:'twenty_qty'},
			{name:'ten', mapping:'ten'},
			{name:'ten_qty', mapping:'ten_qty'},
			{name:'five', mapping:'five'},
			{name:'five_qty', mapping:'five_qty'},
			{name:'one', mapping:'one'},
			{name:'one_qty', mapping:'one_qty'},
			{name:'twenty_five_cent', mapping:'twenty_five_cent'},
			{name:'twenty_five_cent_qty', mapping:'twenty_five_cent_qty'},
			{name:'ten_cent', mapping:'ten_cent'},
			{name:'ten_cent_qty', mapping:'ten_cent_qty'},
			{name:'five_cent', mapping:'five_cent'},
			{name:'five_cent_qty', mapping:'five_cent_qty'},
			{name:'comments', mapping:'comments'},
			{name:'tran_date1', mapping:'tran_date1'}
		]
    });
    
	//------------------------------------: stores :----------------------------------------
	var ProofCashDetailsStore = Ext.create('Ext.data.Store', {
		model: 'ProofCashModel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_proofcash=00',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'tran_date',
			direction : 'DESC'
		}]
	});
	//-----------------------------------//
	var ColumnModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Date</b>', dataIndex:'tran_date', sortable:true, width:120, renderer: Ext.util.Format.dateRenderer('m/d/Y'),
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:black;font-weight:bold;">' + Ext.util.Format.number(value) +'</span>';
			}
		},
		{header:'<b>One Thousand</b>', dataIndex:'one_thousand', sortable:true, width:120,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
			}
		},
		{header:'<b>Qty</b>', dataIndex:'one_thousand_qty', sortable:true, width:50},
		{header:'<b>Five Hundred</b>', dataIndex:'five_hundred', sortable:true, width:120,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
			}
		},
		{header:'<b>Qty</b>', dataIndex:'five_hundred_qty', sortable:true, width:50},
		{header:'<b>Two hundred</b>', dataIndex:'two_hundred', sortable:true, width:120,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
			}
		},
		{header:'<b>Qty</b>', dataIndex:'two_hundred_qty', sortable:true, width:50},
		{header:'<b>One hundred</b>', dataIndex:'one_hundred', sortable:true, width:120,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value, '0,000.00') +'</span>';
			}
		},
		{header:'<b>Qty</b>', dataIndex:'one_hundred_qty', sortable:true, width:50},
		{header:'<b>Remarks</b>', dataIndex:'comments', sortable:true, width:210,
			renderer: function(value, metaData, record, rowIdx, colIdx, store) {
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:105,
			items:[{
				icon: '../../js/ext4/examples/shared/icons/layout_content.png',
				tooltip: 'Edit details',
				handler: function(grid, rowIndex, colIndex){
					submit_form.getForm().reset();
					var records = ProofCashDetailsStore.getAt(rowIndex);
					
					
					Ext.getCmp('buttonsave').setVisible(false);
					Ext.getCmp('buttonupdate').setVisible(true);
					Ext.getCmp('buttonupdate').setText('Update');
					Ext.getCmp('buttoncancel').setText('Cancel');

					Ext.getCmp('tran_date').setVisible(false);
					Ext.getCmp('tran_date1').setVisible(true);

					Ext.getCmp('one_thousand_qty').setValue(records.get('one_thousand_qty'));
					Ext.getCmp('five_hundred_qty').setValue(records.get('five_hundred_qty'));
					Ext.getCmp('two_hundred_qty').setValue(records.get('two_hundred_qty'));
					Ext.getCmp('one_hundred_qty').setValue(records.get('one_hundred_qty'));
					Ext.getCmp('fifty_qty').setValue(records.get('fifty_qty'));
					Ext.getCmp('twenty_qty').setValue(records.get('twenty_qty'));
					Ext.getCmp('ten_qty').setValue(records.get('tran_date'));
					Ext.getCmp('five_qty').setValue(records.get('five_qty'));
					Ext.getCmp('one_qty').setValue(records.get('one_qty'));
					Ext.getCmp('twenty_five_cent_qty').setValue(records.get('twenty_five_cent_qty'));
					Ext.getCmp('ten_cent_qty').setValue(records.get('ten_cent_qty'));
					Ext.getCmp('five_cent_qty').setValue(records.get('five_cent_qty'));
					Ext.getCmp('comments').setValue(records.get('comments'));

					Ext.getCmp('tran_date').setValue(records.get('tran_date'));
					Ext.getCmp('tran_date1').setValue(records.get('tran_date'));

				
					submit_window.show();
					submit_window.setTitle('Proof of Cash Details - Date :'+ '   ' + records.get('tran_date'));
					submit_window.setPosition(330,23);							
				}
			},'-',{
				icon   : '../../js/ext4/examples/shared/icons/fam/delete.png',
				tooltip: 'Delete',
				handler: function(grid, rowIndex, colIndex) {
					var records = ProofCashDetailsStore.getAt(rowIndex);
					var MsgConfirm = Ext.MessageBox.confirm('Confirm', 'Trans No. : <b>' + records.get('trans_no') + '</b><br\>Trans. Date: <b>' + records.get('tran_date') + '</b> <b><b>Are you sure you want to remove this record? </b>', function (btn, text) {	
						if (btn == 'yes') {
							Ext.Ajax.request({
								method: 'GET',
								url: '?delete=info',
								waitMsg:'Deleting Record...please wait.',
								params: {
									syspk: records.get('trans_no'),
									tran_date: records.get('tran_date'),
									//type: records.get('trans_type')
								},
								success: function (response){
									var data = Ext.decode(response.responseText);
									if (data.success == 'true') {
										Ext.Msg.alert('Success!', '<font color="green">' + data.message + '</font>');										
										ProofCashDetailsStore.load();
									}else{
										Ext.Msg.alert('Error', data.message);
									}
								}
							});
						}
					});
					MsgConfirm.defaultButton = 2;
				}

			},'-',{
				icon: '../../js/ext4/examples/shared/icons/printer.png',
				tooltip: 'view proof of cash report',
				handler: function(grid, rowIndex, colIndex) {
					var records = ProofCashDetailsStore.getAt(rowIndex);
					//window.open('../../reports/installment_inquiry.php?&trans_no=' + records.get('trans_no') + '&trans_type='+records.get('type'));

					//Rober -Add
					var win = new Ext.Window({
						autoLoad:{
							url:'../../reports/proof_of_cash_inquiry.php?&trans_no=' + records.get('trans_no'),
							discardUrl: true,
							nocache: true,
							text:"Loading...",
							timeout:60,
							scripts: false
						},
						width:'80%',
						height:'95%',
						title:'Preview Print',
						modal: true
					})
					
					var iframeid = win.getId() + '_iframe';


			        var iframe = {
			            id:iframeid,
			            tag:'iframe',
			            src:'../../reports/proof_of_cash_inquiry.php?&trans_no=' + records.get('trans_no'),
			            width:'100%',
			            height:'100%',
			            frameborder:0
			        }
					win.show();
					Ext.DomHelper.insertFirst(win.body, iframe)
					//---//
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
		width: 290,
		emptyText: "Search by Trans. Date...",
		scale: 'small',
		store: ProofCashDetailsStore,
		listeners: {
			change: function(field) {
				var repoGrd = Ext.getCmp('repoGrd');

				var selected = repoGrd.getSelectionModel().getSelection();
				var areaselected;
				Ext.each(selected, function (item) {
					areaselected = item.data.id;
				});

				if(field.getValue() != ""){
					ProofCashDetailsStore.proxy.extraParams = {REFRENCE: areaselected, showall: showall, query: field.getValue()};
					ProofCashDetailsStore.load();
				}else{
					ProofCashDetailsStore.proxy.extraParams = {REFRENCE: areaselected, showall: showall};
					ProofCashDetailsStore.load();
				}
			}
		}
	}, '-', {
		text:'<b>Add</b>',
		tooltip: 'Add new proof of cash transaction.',
		icon: '../../js/ext4/examples/shared/icons/add.png',
		scale: 'small',
		handler: function(){
			submit_form.getForm().reset();

			Ext.getCmp('buttonsave').setVisible(true);
			Ext.getCmp('buttonupdate').setVisible(false);
			
			Ext.getCmp('tran_date').setVisible(true);
			Ext.getCmp('tran_date1').setVisible(false);

			submit_window.show();
			submit_window.setTitle('Proof Of Cash Report - Add');
			submit_window.setPosition(330,23);
		}
	}];
	var submit_form = Ext.create('Ext.form.Panel', {
		id: 'form_submit',
		model: 'ProofCashModel',
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
						xtype: 'numericfield',
						id: 'one_thousand',
						name: 'one_thousand',
						fieldLabel: '<b>AMOUNT</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: false,
						readOnly: true,	
						width: 150,
						margin: '2 0 0 80',
						labelAlign:	'top',
						thousandSeparator: ',',
						minValue: 0,
						value: 1000,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'one_thousand_qty',
						name: 'one_thousand_qty',
						fieldLabel: '<b>QUANTITY</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: true,
						readOnly: false,				
						width: 150,						
						margin: '2 0 0 5',
						labelAlign:	'top',
						thousandSeparator: ',',
						minValue: 0,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{				
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{
						xtype: 'numericfield',
						id: 'five_hundred',
						name: 'five_hundred',
						//fieldLabel: '<b>Amount</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: false,
						readOnly: true,	
						margin: '2 0 0 80',						
						width: 150,						
						thousandSeparator: ',',
						minValue: 0,
						value: 500,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'five_hundred_qty',
						name: 'five_hundred_qty',
						//fieldLabel: '<b>Quantity</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: true,
						readOnly: false,				
						margin: '2 0 0 5',
						width: 150,						
						thousandSeparator: ',',
						minValue: 0,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{	
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{
						xtype: 'numericfield',
						id: 'two_hundred',
						name: 'two_hundred',
						//fieldLabel: '<b>Amount</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: false,
						readOnly: true,	
						margin: '2 0 0 80',
						width: 150,						
						thousandSeparator: ',',
						minValue: 0,
						value: 200,						
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'two_hundred_qty',
						name: 'two_hundred_qty',
						//fieldLabel: '<b>Quantity</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: true,
						readOnly: false,				
						margin: '2 0 0 5',
						width: 150,						
						thousandSeparator: ',',
						minValue: 0,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{						
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{
						xtype: 'numericfield',
						id: 'one_hundred',
						name: 'one_hundred',
						//fieldLabel: '<b>Amount</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: false,
						readOnly: true,	
						margin: '2 0 0 80',
						width: 150,						
						thousandSeparator: ',',
						minValue: 0,
						value: 100,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'one_hundred_qty',
						name: 'one_hundred_qty',
						//fieldLabel: '<b>Quantity</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: true,
						readOnly: false,				
						margin: '2 0 0 5',
						width: 150,						
						thousandSeparator: ',',
						minValue: 0,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{	
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{
						xtype: 'numericfield',
						id: 'fifty',
						name: 'fifty',
						//fieldLabel: '<b>Amount</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: false,
						readOnly: true,	
						margin: '2 0 0 80',
						width: 150,						
						thousandSeparator: ',',
						minValue: 0,
						value: 50,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'fifty_qty',
						name: 'fifty_qty',
						//fieldLabel: '<b>Quantity</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: true,
						readOnly: false,				
						margin: '2 0 0 5',
						width: 150,						
						thousandSeparator: ',',
						minValue: 0,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{	
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{
						xtype: 'numericfield',
						id: 'twenty',
						name: 'twenty',
						//fieldLabel: '<b>Amount</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: false,
						readOnly: true,	
						margin: '2 0 0 80',
						width: 150,						
						thousandSeparator: ',',
						minValue: 0,
						value: 20,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'twenty_qty',
						name: 'twenty_qty',
						//fieldLabel: '<b>Quantity</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: true,
						readOnly: false,				
						margin: '2 0 0 5',
						width: 150,						
						thousandSeparator: ',',
						minValue: 0,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{																		
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{
						xtype: 'numericfield',
						id: 'ten',
						name: 'ten',
						//fieldLabel: '<b>Amount</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: false,
						readOnly: true,	
						margin: '2 0 0 80',
						width: 150,						
						thousandSeparator: ',',
						minValue: 0,
						value: 10,					
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'ten_qty',
						name: 'ten_qty',
						//fieldLabel: '<b>Quantity</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: true,
						readOnly: false,				
						margin: '2 0 0 5',
						width: 150,						
						thousandSeparator: ',',
						minValue: 0,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{			
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{
						xtype: 'numericfield',
						id: 'five',
						name: 'five',
						//fieldLabel: '<b>Amount</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: false,
						readOnly: true,	
						margin: '2 0 0 80',
						width: 150,						
						thousandSeparator: ',',
						minValue: 0,
						value: 5,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'five_qty',
						name: 'five_qty',
						//fieldLabel: '<b>Quantity</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: true,
						readOnly: false,				
						margin: '2 0 0 5',
						width: 150,						
						thousandSeparator: ',',
						minValue: 0,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{			
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{
						xtype: 'numericfield',
						id: 'one',
						name: 'one',
						//fieldLabel: '<b>Amount</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: false,
						readOnly: true,	
						margin: '2 0 0 80',
						width: 150,						
						thousandSeparator: ',',
						minValue: 0,
						value: 1,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'one_qty',
						name: 'one_qty',
						//fieldLabel: '<b>Quantity</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: true,
						readOnly: false,				
						margin: '2 0 0 5',
						width: 150,						
						thousandSeparator: ',',
						minValue: 0,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{			
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{
						xtype: 'numericfield',
						id: 'twenty_five_cent',
						name: 'twenty_five_cent',
						//fieldLabel: '<b>Amount</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: false,
						readOnly: true,	
						margin: '2 0 0 80',
						width: 150,						
						thousandSeparator: ',',
						minValue: 0,
						value: 0.25,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'twenty_five_cent_qty',
						name: 'twenty_five_cent_qty',
						//fieldLabel: '<b>Quantity</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: true,
						readOnly: false,				
						margin: '2 0 0 5',
						width: 150,						
						thousandSeparator: ',',
						minValue: 0,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{			
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{
						xtype: 'numericfield',
						id: 'ten_cent',
						name: 'ten_cent',
						//fieldLabel: '<b>Amount</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: false,
						readOnly: true,	
						margin: '2 0 0 80',
						width: 150,						
						thousandSeparator: ',',
						minValue: 0,
						value: 0.10,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'ten_cent_qty',
						name: 'ten_cent_qty',
						//fieldLabel: '<b>Quantity</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: true,
						readOnly: false,				
						margin: '2 0 0 5',
						width: 150,						
						thousandSeparator: ',',
						minValue: 0,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{			
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{
						xtype: 'numericfield',
						id: 'five_cent',
						name: 'five_cent',
						//fieldLabel: '<b>Amount</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: false,
						readOnly: true,	
						margin: '2 0 0 80',
						width: 150,						
						thousandSeparator: ',',
						minValue: 0,
						value: 0.05,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'five_cent_qty',
						name: 'five_cent_qty',
						//fieldLabel: '<b>Quantity</b>',
						useThousandSeparator: true,
						decimalPrecision: 2,
						alwaysDisplayDecimals: true,
						allowNegative: false,
						allowBlank: true,
						readOnly: false,				
						margin: '2 0 0 5',
						width: 150,						
						thousandSeparator: ',',
						minValue: 0,
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{	
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{
						xtype: 	'textareafield',
						fieldLabel: 'REMARKS',
						id:	'comments',
						name: 'comments',
						labelAlign:	'top',
						allowBlank: true,
						maxLength: 254,
						margin: '2 0 0 80',
						width: 150,
						hidden: false
						
					},{
						xtype : 'datefield',
						id	  : 'tran_date',
						name  : 'tran_date',
						fieldLabel : '<b>TRANSACTION DATE</b>',
						labelAlign:	'top',
						//labelWidth: 80,
						margin: '2 0 0 5',
						width: 150,
						format : 'm/d/Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y-m-d')
					},{
						xtype : 'datefield',
						id	  : 'tran_date1',
						name  : 'tran_date1',
						fieldLabel : '<b>TRANSACTION DATE</b>',
						labelAlign:	'top',
						//labelWidth: 80,
						margin: '2 0 0 5',
						width: 150,
						format : 'm/d/Y',
						allowBlank: false,
						readOnly: true,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y-m-d'),
						hidden: true
					}]
				},{	
			}]
		}]
	});
	var submit_window = Ext.create('Ext.Window',{
		id: 'submit_window',
		width 	: 470,
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
			tooltip: 'Save Proof Cash Transaction',
			icon: '../../js/ext4/examples/shared/icons/add.png',
			single : true,
			handler:function(){
				var form_submit = Ext.getCmp('form_submit').getForm();
				if(form_submit.isValid()) {
					form_submit.submit({
						url: '?submit=repoinfo',
						waitMsg: 'Processing transaction. please wait...',
						method:'GET',
						submitEmptyText: false,
						success: function(form_submit, action) {
							ProofCashDetailsStore.load()
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
						method:'GET',
						submitEmptyText: false,
						success: function(form_submit, action) {
							ProofCashDetailsStore.load()
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
			tooltip: 'Cancel adding Proof of Cash',
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
	var REPO_GRID =  Ext.create('Ext.panel.Panel', { 
        renderTo: 'ext-form',
		id: 'REPO_GRID',
        frame: false,
		width: 1150,
		tbar: tbar,
		items: [{
			xtype: 'grid',
			id: 'repoGrd',
			title: 'Proof Of Cash Setup - Details',
			store:	ProofCashDetailsStore,
			columns: ColumnModel,
			columnLines: true,
			autoScroll:true,
			layout:'fit',
			frame: true,
			bbar : {
				xtype : 'pagingtoolbar',
				hidden: false,
				store : ProofCashDetailsStore,
				pageSize : itemsPerPage,
				displayInfo : false,
				emptyMsg: "No records to display",
				doRefresh : function(){
					ProofCashDetailsStore.load();
					
				}
			}
		}]
	});
});
