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

    Ext.define('COLLECTIONPERCENTAGE',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'collect_date0', mapping:'collect_date0'},
			{name:'collect_date1', mapping:'collect_date1'},
			{name:'collect_date2', mapping:'collect_date2'},
			{name:'collect_date3', mapping:'collect_date3'},
			{name:'collect_date4', mapping:'collect_date4'},
			{name:'collect_date5', mapping:'collect_date5'},
			{name:'collect_date6', mapping:'collect_date6'},
			{name:'collect_date7', mapping:'collect_date7'},
			{name:'collect_date8', mapping:'collect_date8'},
			{name:'collect_date9', mapping:'collect_date9'},
			{name:'collect_date10', mapping:'collect_date10'},
			{name:'collect_date11', mapping:'collect_date11'},
			{name:'month0', mapping:'month0'},
			{name:'month1', mapping:'month1'},
			{name:'month2', mapping:'month2'},
			{name:'month3', mapping:'month3'},
			{name:'month4', mapping:'month4'},
			{name:'month5', mapping:'month5'},
			{name:'month6', mapping:'month6'},
			{name:'month7', mapping:'month7'},
			{name:'month8', mapping:'month8'},
			{name:'month9', mapping:'month9'},
			{name:'month10', mapping:'month10'},
			{name:'month11', mapping:'month11'},
			{name:'percentage0', mapping:'percentage0'},
			{name:'percentage1', mapping:'percentage1'},
			{name:'percentage2', mapping:'percentage2'},
			{name:'percentage3', mapping:'percentage3'},
			{name:'percentage4', mapping:'percentage4'},
			{name:'percentage5', mapping:'percentage5'},
			{name:'percentage6', mapping:'percentage6'},
			{name:'percentage7', mapping:'percentage7'},
			{name:'percentage8', mapping:'percentage8'},
			{name:'percentage9', mapping:'percentage9'},
			{name:'percentage10', mapping:'percentage10'},
			{name:'percentage11', mapping:'percentage11'},


			{name:'YEAR', mapping:'collect_date'},
			{name:'YEARS', mapping:'collect_date0'},
			{name:'JANUARY', mapping:'percentage0'},
			{name:'FEBRUARY', mapping:'percentage1'},
			{name:'MARCH', mapping:'percentage2'},
			{name:'APRIL', mapping:'percentage3'},
			{name:'MAY', mapping:'percentage4'},
			{name:'JUNE', mapping:'percentage5'},
			{name:'JULY', mapping:'percentage6'},
			{name:'AUGUST', mapping:'percentage7'},
			{name:'SEPTEMBER', mapping:'percentage8'},
			{name:'OCTOBER', mapping:'percentage9'},
			{name:'NOVEMBER', mapping:'percentage10'},
			{name:'DECEMBER', mapping:'percentage11'}

		]
    });
    
	//------------------------------------: stores :----------------------------------------
	var CollectionTargerDetailsStore = Ext.create('Ext.data.Store', {
		model: 'COLLECTIONPERCENTAGE',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_percenatge_target=00',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'YEAR',
			direction : 'DESC'
		}]
	});
	//-----------------------------------//
	var ColumnModel = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Date</b>', dataIndex:'YEAR', sortable:true, width:75, renderer: Ext.util.Format.dateRenderer('Y'),
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:black;font-weight:bold;">' + Ext.util.Format.number(value) +'</span>';
			}
		},
		{header:'<b>January</b>', dataIndex:'JANUARY', sortable:true, width:90,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value+'%') +'</span>';
			}
		},
		{header:'<b>February</b>', dataIndex:'FEBRUARY', sortable:true, width:90,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value+'%') +'</span>';
			}
		},
		{header:'<b>March</b>', dataIndex:'MARCH', sortable:true, width:90,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value+'%') +'</span>';
			}
		},
		{header:'<b>April</b>', dataIndex:'APRIL', sortable:true, width:90,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value+'%') +'</span>';
			}
		},
		{header:'<b>May</b>', dataIndex:'MAY', sortable:true, width:90,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value+'%') +'</span>';
			}
		},
		{header:'<b>June</b>', dataIndex:'JUNE', sortable:true, width:90,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value+'%') +'</span>';
			}
		},
		{header:'<b>July</b>', dataIndex:'JULY', sortable:true, width:90,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value+'%') +'</span>';
			}
		},
		{header:'<b>August</b>', dataIndex:'AUGUST', sortable:true, width:85,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value+'%') +'</span>';
			}
		},
		{header:'<b>September</b>', dataIndex:'SEPTEMBER', sortable:true, width:100,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value+'%') +'</span>';
			}
		},
		{header:'<b>October</b>', dataIndex:'OCTOBER', sortable:true, width:90,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value+'%') +'</span>';
			}
		},
		{header:'<b>November</b>', dataIndex:'NOVEMBER', sortable:true, width:95,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value+'%') +'</span>';
			}
		},
		{header:'<b>December</b>', dataIndex:'DECEMBER', sortable:true, width:95,
			renderer: Ext.util.Format.Currency = function(value){
				return '<span style="color:green;font-weight:bold;">' + Ext.util.Format.number(value+'%') +'</span>';
			}
		},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:70,
			items:[{
				icon: '../../js/ext4/examples/shared/icons/layout_content.png',
				tooltip: 'Update details',
				handler: function(grid, rowIndex, colIndex){
					submit_form.getForm().reset();
					var records = CollectionTargerDetailsStore.getAt(rowIndex);
					
					
					Ext.getCmp('buttonsave').setVisible(false);
					Ext.getCmp('buttonupdate').setVisible(true);
					Ext.getCmp('buttonupdate').setText('Update');
					Ext.getCmp('buttoncancel').setText('Cancel');

					Ext.getCmp('percentage0').setValue(records.get('percentage0'));
					Ext.getCmp('percentage1').setValue(records.get('percentage1'));
					Ext.getCmp('percentage2').setValue(records.get('percentage2'));
					Ext.getCmp('percentage3').setValue(records.get('percentage3'));
					Ext.getCmp('percentage4').setValue(records.get('percentage4'));
					Ext.getCmp('percentage5').setValue(records.get('percentage5'));
					Ext.getCmp('percentage6').setValue(records.get('percentage6'));
					Ext.getCmp('percentage7').setValue(records.get('percentage7'));
					Ext.getCmp('percentage8').setValue(records.get('percentage8'));
					Ext.getCmp('percentage9').setValue(records.get('percentage9'));
					Ext.getCmp('percentage10').setValue(records.get('percentage10'));
					Ext.getCmp('percentage11').setValue(records.get('percentage11'));

					Ext.getCmp('collect_date0').setVisible(false);
					Ext.getCmp('collect_date1').setVisible(false);
					Ext.getCmp('collect_date2').setVisible(false);
					Ext.getCmp('collect_date3').setVisible(false);
					Ext.getCmp('collect_date4').setVisible(false);
					Ext.getCmp('collect_date5').setVisible(false);
					Ext.getCmp('collect_date6').setVisible(false);
					Ext.getCmp('collect_date7').setVisible(false);
					Ext.getCmp('collect_date8').setVisible(false);
					Ext.getCmp('collect_date9').setVisible(false);
					Ext.getCmp('collect_date10').setVisible(false);
					Ext.getCmp('collect_date11').setVisible(false);

					Ext.getCmp('collect_date0_v2').setVisible(true);
					Ext.getCmp('collect_date1_v2').setVisible(true);
					Ext.getCmp('collect_date2_v2').setVisible(true);
					Ext.getCmp('collect_date3_v2').setVisible(true);
					Ext.getCmp('collect_date4_v2').setVisible(true);
					Ext.getCmp('collect_date5_v2').setVisible(true);
					Ext.getCmp('collect_date6_v2').setVisible(true);
					Ext.getCmp('collect_date7_v2').setVisible(true);
					Ext.getCmp('collect_date8_v2').setVisible(true);
					Ext.getCmp('collect_date9_v2').setVisible(true);
					Ext.getCmp('collect_date10_v2').setVisible(true);
					Ext.getCmp('collect_date11_v2').setVisible(true);

					Ext.getCmp('collect_date0_v2').setValue(records.get('collect_date'));
					Ext.getCmp('collect_date1_v2').setValue(records.get('collect_date'));
					Ext.getCmp('collect_date2_v2').setValue(records.get('collect_date'));
					Ext.getCmp('collect_date3_v2').setValue(records.get('collect_date'));
					Ext.getCmp('collect_date4_v2').setValue(records.get('collect_date'));
					Ext.getCmp('collect_date5_v2').setValue(records.get('collect_date'));
					Ext.getCmp('collect_date6_v2').setValue(records.get('collect_date'));
					Ext.getCmp('collect_date7_v2').setValue(records.get('collect_date'));
					Ext.getCmp('collect_date8_v2').setValue(records.get('collect_date'));
					Ext.getCmp('collect_date9_v2').setValue(records.get('collect_date'));
					Ext.getCmp('collect_date10_v2').setValue(records.get('collect_date'));
					Ext.getCmp('collect_date11_v2').setValue(records.get('collect_date'));

					Ext.getCmp('collect_date0').setValue(records.get('collect_date'));
					Ext.getCmp('collect_date1').setValue(records.get('collect_date'));
					Ext.getCmp('collect_date2').setValue(records.get('collect_date'));
					Ext.getCmp('collect_date3').setValue(records.get('collect_date'));
					Ext.getCmp('collect_date4').setValue(records.get('collect_date'));
					Ext.getCmp('collect_date5').setValue(records.get('collect_date'));
					Ext.getCmp('collect_date6').setValue(records.get('collect_date'));
					Ext.getCmp('collect_date7').setValue(records.get('collect_date'));
					Ext.getCmp('collect_date8').setValue(records.get('collect_date'));
					Ext.getCmp('collect_date9').setValue(records.get('collect_date'));
					Ext.getCmp('collect_date10').setValue(records.get('collect_date'));
					Ext.getCmp('collect_date11').setValue(records.get('collect_date'));


					submit_window.show();
					submit_window.setTitle('Collection Percentage Target - YEAR :'+ records.get('YEAR'));
					submit_window.setPosition(400,30);
				}
			},'-',{
				icon   : '../../js/ext4/examples/shared/icons/fam/delete.png',
				tooltip: 'Delete',
				handler: function(grid, rowIndex, colIndex) {
					var records = CollectionTargerDetailsStore.getAt(rowIndex);
					var MsgConfirm = Ext.MessageBox.confirm('Confirm', 'YEAR. : <b>' + records.get('YEAR') + '-' + '</b> <b><b>Are you sure you want to remove this record? </b>', function (btn, text) {	
						if (btn == 'yes') {
							Ext.Ajax.request({
								method: 'POST',
								url: '?delete=info',
								waitMsg:'Deleting Record...please wait.',
								params: {

									collect_date: records.get('YEAR'),
									//type: records.get('trans_type')
								},
								success: function (response){
									var data = Ext.decode(response.responseText);
									if (data.success == 'true') {
										Ext.Msg.alert('Success', data.message);
										CollectionTargerDetailsStore.load();
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
		width: 290,
		emptyText: "Search by Year...",
		scale: 'large',
		store: CollectionTargerDetailsStore,
		listeners: {
			change: function(field) {
				var repoGrd = Ext.getCmp('repoGrd');

				var selected = repoGrd.getSelectionModel().getSelection();
				var areaselected;
				Ext.each(selected, function (item) {
					areaselected = item.data.id;
				});

				if(field.getValue() != ""){
					CollectionTargerDetailsStore.proxy.extraParams = {REFRENCE: areaselected, query: field.getValue()};
					CollectionTargerDetailsStore.load();
				}else{
					CollectionTargerDetailsStore.proxy.extraParams = {REFRENCE: areaselected};
					CollectionTargerDetailsStore.load();
				}
			}
		}
	}, '-', {
		text:'<b>Add</b>',
		tooltip: 'Add new collection target transaction - Percentage.',
		icon: '../../js/ext4/examples/shared/icons/add.png',
		scale: 'small',
		handler: function(){
			submit_form.getForm().reset();

			Ext.getCmp('collect_date0_v2').setVisible(false);
			Ext.getCmp('collect_date1_v2').setVisible(false);
			Ext.getCmp('collect_date2_v2').setVisible(false);
			Ext.getCmp('collect_date3_v2').setVisible(false);
			Ext.getCmp('collect_date4_v2').setVisible(false);
			Ext.getCmp('collect_date5_v2').setVisible(false);
			Ext.getCmp('collect_date6_v2').setVisible(false);
			Ext.getCmp('collect_date7_v2').setVisible(false);
			Ext.getCmp('collect_date8_v2').setVisible(false);
			Ext.getCmp('collect_date9_v2').setVisible(false);
			Ext.getCmp('collect_date10_v2').setVisible(false);
			Ext.getCmp('collect_date11_v2').setVisible(false);


			Ext.getCmp('collect_date0').setVisible(true);
			Ext.getCmp('collect_date1').setVisible(true);
			Ext.getCmp('collect_date2').setVisible(true);
			Ext.getCmp('collect_date3').setVisible(true);
			Ext.getCmp('collect_date4').setVisible(true);
			Ext.getCmp('collect_date5').setVisible(true);
			Ext.getCmp('collect_date6').setVisible(true);
			Ext.getCmp('collect_date7').setVisible(true);
			Ext.getCmp('collect_date8').setVisible(true);
			Ext.getCmp('collect_date9').setVisible(true);
			Ext.getCmp('collect_date10').setVisible(true);
			Ext.getCmp('collect_date11').setVisible(true);
			
			Ext.getCmp('buttonupdate').setVisible(false);
			Ext.getCmp('buttonsave').setVisible(true);
			submit_window.show();
			submit_window.setTitle('Collection Percentage Target - Add');
			submit_window.setPosition(400,30);
		}
	}];
	var submit_form = Ext.create('Ext.form.Panel', {
		id: 'form_submit',
		model: 'COLLECTIONPERCENTAGE',
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
						id	  : 'collect_date0',
						name  : 'collect_date0',
						fieldLabel : '<b>Year</b>',
						labelAlign:	'top',
						//labelWidth: 80,
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						readOnly: false,				
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y')
					},{	
						xtype : 'datefield',
						id	  : 'collect_date0_v2',
						name  : 'collect_date0_v2',
						fieldLabel : '<b>Year</b>',
						labelAlign:	'top',
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						readOnly: true,				
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y'),
						hidden: true
					},{
						xtype: 'textfield',
						id: 'month0',
						name: 'month0',
						fieldLabel: '<b>Month</b>',
						allowBlank: false,
						readOnly: true,				
						width: 60,						
						margin: '2 0 0 5',
						labelAlign:	'top',
						value: '01-01',
						fieldStyle: 'font-weight: bold;color: #210a04; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'percentage0',
						name: 'percentage0',
						fieldLabel: '<b>Percentage</b>',
						allowBlank: true,
						readOnly: false,				
						width: 85,						
						margin: '2 0 0 20',
						labelAlign:	'top',
						//value: '%',						
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{				
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{
						xtype : 'datefield',
						id	  : 'collect_date1',
						name  : 'collect_date1',
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y')
					},{
						xtype : 'datefield',
						id	  : 'collect_date1_v2',
						name  : 'collect_date1_v2',
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y'),
						readOnly: true,
						hidden: true
					},{
						xtype: 'textfield',
						id: 'month1',
						name: 'month1',						
						allowBlank: false,
						readOnly: true,				
						width: 60,						
						margin: '2 0 0 5',			
						value: '02-01',
						fieldStyle: 'font-weight: bold;color: #210a04; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'percentage1',
						name: 'percentage1',
						allowBlank: true,
						readOnly: false,				
						width: 85,						
						margin: '2 0 0 20',
						//value: '%',						
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{	
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{
						xtype : 'datefield',
						id	  : 'collect_date2',
						name  : 'collect_date2',
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y')
					},{
						xtype : 'datefield',
						id	  : 'collect_date2_v2',
						name  : 'collect_date2_v2',
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y'),
						readOnly: true,
						hidden: true
					},{
						xtype: 'textfield',
						id: 'month2',
						name: 'month2',						
						allowBlank: false,
						readOnly: true,				
						width: 60,						
						margin: '2 0 0 5',			
						value: '03-01',
						fieldStyle: 'font-weight: bold;color: #210a04; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'percentage2',
						name: 'percentage2',
						allowBlank: true,
						readOnly: false,				
						width: 85,						
						margin: '2 0 0 20',
						//value: '%',						
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{						
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{
						xtype : 'datefield',
						id	  : 'collect_date3',
						name  : 'collect_date3',
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y')
					},{
						xtype : 'datefield',
						id	  : 'collect_date3_v2',
						name  : 'collect_date3_v2',
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y'),
						readOnly: true,
						hidden: true
					},{
						xtype: 'textfield',
						id: 'month3',
						name: 'month3',						
						allowBlank: false,
						readOnly: true,				
						width: 60,						
						margin: '2 0 0 5',			
						value: '04-01',
						fieldStyle: 'font-weight: bold;color: #210a04; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'percentage3',
						name: 'percentage3',
						allowBlank: true,
						readOnly: false,				
						width: 85,						
						margin: '2 0 0 20',
						//value: '%',						
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{	
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{
						xtype : 'datefield',
						id	  : 'collect_date4',
						name  : 'collect_date4',
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y')
					},{
						xtype : 'datefield',
						id	  : 'collect_date4_v2',
						name  : 'collect_date4_v2',
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y'),
						readOnly: true,
						hidden: true
					},{
						xtype: 'textfield',
						id: 'month4',
						name: 'month4',						
						allowBlank: false,
						readOnly: true,				
						width: 60,						
						margin: '2 0 0 5',			
						value: '05-01',
						fieldStyle: 'font-weight: bold;color: #210a04; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'percentage4',
						name: 'percentage4',
						allowBlank: true,
						readOnly: false,				
						width: 85,						
						margin: '2 0 0 20',
						//value: '%',						
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{	
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{				
						xtype : 'datefield',
						id	  : 'collect_date5',
						name  : 'collect_date5',
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y')
					},{
						xtype : 'datefield',
						id	  : 'collect_date5_v2',
						name  : 'collect_date5_v2',
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y'),
						readOnly: true,
						hidden: true
					},{
						xtype: 'textfield',
						id: 'month5',
						name: 'month5',						
						allowBlank: false,
						readOnly: true,				
						width: 60,						
						margin: '2 0 0 5',			
						value: '06-01',
						fieldStyle: 'font-weight: bold;color:#210a04; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'percentage5',
						name: 'percentage5',
						allowBlank: true,
						readOnly: false,				
						width: 85,						
						margin: '2 0 0 20',
						//value: '%',						
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{																		
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{
						xtype : 'datefield',
						id	  : 'collect_date6',
						name  : 'collect_date6',
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y')
					},{
						xtype : 'datefield',
						id	  : 'collect_date6_v2',
						name  : 'collect_date6_v2',
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y'),
						readOnly: true,
						hidden: true
					},{
						xtype: 'textfield',
						id: 'month6',
						name: 'month6',						
						allowBlank: false,
						readOnly: true,				
						width: 60,						
						margin: '2 0 0 5',			
						value: '07-01',
						fieldStyle: 'font-weight: bold;color: #210a04; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'percentage6',
						name: 'percentage6',
						allowBlank: true,
						readOnly: false,				
						width: 85,						
						margin: '2 0 0 20',
						//value: '%',						
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{			
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{
						xtype : 'datefield',
						id	  : 'collect_date7',
						name  : 'collect_date7',
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y')
					},{
						xtype : 'datefield',
						id	  : 'collect_date7_v2',
						name  : 'collect_date7_v2',
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y'),
						readOnly: true,
						hidden: true
					},{
						xtype: 'textfield',
						id: 'month7',
						name: 'month7',						
						allowBlank: false,
						readOnly: true,				
						width: 60,						
						margin: '2 0 0 5',			
						value: '08-01',
						fieldStyle: 'font-weight: bold;color: #210a04; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'percentage7',
						name: 'percentage7',
						allowBlank: true,
						readOnly: false,				
						width: 85,						
						margin: '2 0 0 20',
						//value: '%',						
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{			
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{
						xtype : 'datefield',
						id	  : 'collect_date8',
						name  : 'collect_date8',
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y')
					},{
						xtype : 'datefield',
						id	  : 'collect_date8_v2',
						name  : 'collect_date8_v2',
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y'),
						readOnly: true,
						hidden: true
					},{
						xtype: 'textfield',
						id: 'month8',
						name: 'month8',						
						allowBlank: false,
						readOnly: true,				
						width: 60,						
						margin: '2 0 0 5',			
						value: '09-01',
						fieldStyle: 'font-weight: bold;color: #210a04; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'percentage8',
						name: 'percentage8',
						allowBlank: true,
						readOnly: false,				
						width: 85,						
						margin: '2 0 0 20',
						//value: '%',						
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{			
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{
						xtype : 'datefield',
						id	  : 'collect_date9',
						name  : 'collect_date9',
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y')
					},{
						xtype : 'datefield',
						id	  : 'collect_date9_v2',
						name  : 'collect_date9_v2',
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y'),
						readOnly: true,
						hidden: true
					},{
						xtype: 'textfield',
						id: 'month9',
						name: 'month9',						
						allowBlank: false,
						readOnly: true,				
						width: 60,						
						margin: '2 0 0 5',			
						value: '10-01',
						fieldStyle: 'font-weight: bold;color: #210a04; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'percentage9',
						name: 'percentage9',
						allowBlank: true,
						readOnly: false,				
						width: 85,						
						margin: '2 0 0 20',
						//value: '%',						
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{			
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{
						xtype : 'datefield',
						id	  : 'collect_date10',
						name  : 'collect_date10',
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y')
					},{
						xtype : 'datefield',
						id	  : 'collect_date10_v2',
						name  : 'collect_date10_v2',
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y'),
						readOnly: true,
						hidden: true
					},{
						xtype: 'textfield',
						id: 'month10',
						name: 'month10',						
						allowBlank: false,
						readOnly: true,				
						width: 60,						
						margin: '2 0 0 5',			
						value: '11-01',
						fieldStyle: 'font-weight: bold;color: #210a04; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'percentage10',
						name: 'percentage10',
						allowBlank: true,
						readOnly: false,				
						width: 85,						
						margin: '2 0 0 20',
						//value: '%',						
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{			
					xtype: 'fieldcontainer',
					layout: 'hbox',
					margin: '2 0 2 0',
					items:[{
						xtype : 'datefield',
						id	  : 'collect_date11',
						name  : 'collect_date11',
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y')
					},{
						xtype : 'datefield',
						id	  : 'collect_date11_v2',
						name  : 'collect_date11_v2',
						margin: '2 0 0 100',
						width: 90,
						format : 'Y',
						allowBlank: false,
						fieldStyle: 'font-weight: bold; color: #210a04;',
						value: Ext.Date.format(new Date(), 'Y'),
						readOnly: true,
						hidden: true
					},{
						xtype: 'textfield',
						id: 'month11',
						name: 'month11',						
						allowBlank: false,
						readOnly: true,				
						width: 60,						
						margin: '2 0 0 5',			
						value: '12-01',
						fieldStyle: 'font-weight: bold;color: #210a04; text-align: right;'
					},{
						xtype: 'numericfield',
						id: 'percentage11',
						name: 'percentage11',
						allowBlank: true,
						readOnly: false,				
						width: 85,						
						margin: '2 0 0 20',
						//value: '%',						
						fieldStyle: 'font-weight: bold;color: #008000; text-align: right;'
					}]
				},{	
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
			tooltip: 'Save Collection Target - Percentage',
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
							CollectionTargerDetailsStore.load()
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
			tooltip: 'Update Collection Target - Percentage',
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
							CollectionTargerDetailsStore.load()
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
			tooltip: 'Cancel adding collection target - Percentage',
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
		width: 1275,
		tbar: tbar,
		items: [{
			xtype: 'grid',
			title: 'Collection Percentage Target - Details',
			id: 'repoGrd',
			store:	CollectionTargerDetailsStore,
			columns: ColumnModel,
			columnLines: true,
			autoScroll:true,
			layout:'fit',
			frame: true,
			bbar : {
				xtype : 'pagingtoolbar',
				hidden: false,
				store : CollectionTargerDetailsStore,
				pageSize : itemsPerPage,
				displayInfo : false,
				emptyMsg: "No records to display",
				doRefresh : function(){
					CollectionTargerDetailsStore.load();
					
				}
			}
		}]
	});
});
