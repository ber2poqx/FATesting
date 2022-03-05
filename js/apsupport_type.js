var newURL = window.location.protocol + "//" + window.location.host + window.location.pathname;
Ext.Loader.setConfig({enabled: true});
Ext.Loader.setPath('Ext.ux', '../js/ext4/examples/ux/');
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
	var itemsPerPage = 5;   // set the number of items you want per page on grid.
	var showall = false;

	Ext.define('itmapsupp_model',{
		extend : 'Ext.data.Model',
		fields  : [
			{name:'id',mapping:'id'},
			{name:'apsupp_type',mapping:'apsupp_type'},
			{name:'distribution',mapping:'distribution'},
			{name:'status',mapping:'status'}
		]
	});
    Ext.define('comboModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'name', mapping:'name'}
		]
    });
	var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
        clicksToEdit: 2
    });
	var apsupport_store = Ext.create('Ext.data.Store', {
		model: 'itmapsupp_model',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_itemapsupp_type=xx',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'id',
			direction : 'ASC'
		}]
	});
	
	var submit_form = Ext.create('Ext.form.Panel', {
		id: 'form_submit',
		model: 'itmapsupp_model',
		frame: true,
		height: 488,
		defaultType: 'field',
		defaults: {msgTarget: 'under', anchor: '-5'}, //msgTarget: 'side', labelAlign: 'top'
		items: [{
			xtype: 'textfield',
			id: 'syspk',
			name: 'syspk',
			fieldLabel: 'syspk',
			//allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			fieldLabel: 'Type ',
			id: 'apsupp_type',
			name: 'apsupp_type',
			allowBlank: false,
			labelWidth: 100,
			margin: '2 0 2 0',
			fieldStyle: 'font-weight: bold; color: #210a04;'
		},{
			xtype: 'textfield',
			fieldLabel: 'Distribution ',
			id: 'distribution',
			name: 'distribution',
			allowBlank: false,
			labelWidth: 100,
			fieldStyle: 'font-weight: bold; color: #210a04;'
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			id: 'fldstatus',
			margin: '2 0 2 0',
			items:[{
				xtype: 'radiogroup',
				flex:1,
				fieldLabel: '<b>Active ? </b>',
				layout: {type: 'hbox'},
				items: [
					{boxLabel: '<b>Yes</b>', name: 'inactive',id:'yes', inputValue:0, margin : '0 15 0 0'},
					{boxLabel: '<b>No</b>', name: 'inactive', id:'no', inputValue:1}
				]
			}]
		}]
	});

	var submit_window = Ext.create('Ext.Window',{
		width 	: 450,
		height: 200,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form],
		buttons:[{
			text: 'Save',
			tooltip: 'Save ap support type',
			icon: '../js/ext4/examples/shared/icons/add.png',
			single : true,
			handler:function(){
				var form_submit = Ext.getCmp('form_submit').getForm();
				if(form_submit.isValid()) {
					form_submit.submit({
						url: '?submitype=info',
						waitMsg: 'Saving ap support type. please wait...',
						method:'POST',
						success: function(form_submit, action) {
							apsupport_store.proxy.extraParams = {query: Ext.getCmp('search').getValue()};
							apsupport_store.load();

							Ext.MessageBox.confirm('Success!', action.result.message + '<br>Would you like to add more?', function (btn, text) {
								if (btn == 'yes') {
									form_submit.reset();
									submit_window.setTitle('AP Support Type Maintenance - Add');
								}else{
									submit_window.close();
								}
							});
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
			tooltip: 'Cancel',
			icon: '../js/ext4/examples/shared/icons/cancel.png',
			handler:function(){
				Ext.MessageBox.confirm('Confirm:', 'Are you sure you wish to close this window?', function (btn, text) {
					if (btn == 'yes') {
						//Ext.Msg.alert('Close','close.');
						submit_form.getForm().reset();
						submit_window.close();
					}
				});
			}
		}]
	});

	var main_view = [
		new Ext.grid.RowNumberer(),
		{header:'<b>AP Support Type</b>', dataIndex:'apsupp_type', width:250},
		{header:'<b>Distribution</b>', dataIndex:'distribution', width:220,
			renderer : function(value, metaData, summaryData, dataIndex){
				metaData.tdAttr = 'data-qtip="' + value + '"';
				return value;
			}
		},
		{header:'<b>Active</b>', dataIndex:'status', sortable:true, width:82,
			renderer:function(value,metaData){
				if (value === 'Yes'){
					metaData.style="color:#229954";
				}else{
					metaData.style="color:#900C3F";
				}
				return "<b>" + value + "</b>";
			}
		},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:100,
			items:[{
				icon: '../js/ext4/examples/shared/icons/layout_content.png',
				tooltip: 'view details',
				handler: function(grid, rowIndex, colIndex) {
					var records = apsupport_store.getAt(rowIndex);

					submit_form.getForm().reset();

					Ext.getCmp('syspk').setValue(records.get('id'));
					Ext.getCmp('apsupp_type').setValue(records.get('apsupp_type'));
					Ext.getCmp('distribution').setValue(records.get('distribution'));
					Ext.getCmp('fldstatus').setVisible(true);
					if(records.get('status') == 'Yes'){
						Ext.getCmp('yes').setValue(true);
					}else{
						Ext.getCmp('no').setValue(true);
					}

					submit_window.setTitle('AP Support Type Details - ' + records.get('apsupp_type') );
					submit_window.show();
					submit_window.setPosition(420,100);
				}
			},'-',{
				icon   : '../js/ext4/examples/shared/icons/fam/delete.png',
				tooltip : 'Delete',
				handler : function(grid, rowIndex, colIndex){
					var records = apsupport_store.getAt(rowIndex);
					var MsgConfirm = Ext.MessageBox.confirm('Confirm', 'AP Support Type: <b>' + Ext.getCmp('apsupp_type').getValue() + '</b><br\> Are you sure you want to delete this record? ', function (btn, text) {
						if (btn == 'yes') {
							Ext.Ajax.request({
								method: 'POST',
								url: '?deletetype=info',
								waitMsg:'Deleting Record...please wait.',
								params: {
									syspk: records.get('id')
								},
								success: function (response){
									var data = Ext.decode(response.responseText);
									if (data.success == 'true') {

										apsupport_store.proxy.extraParams = {query: Ext.getCmp('search').getValue()};
										apsupport_store.load();

										Ext.Msg.alert('Success', data.message);
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
		labelWidth: 60,
		width: 300,
		emptyText: "Search here",
		scale: 'small',
		store: apsupport_store,
		listeners: {
			change: function(field) {
				apsupport_store.proxy.extraParams = {query: field.getValue()};
				apsupport_store.load();
			}
		}
	}, '-',{
		text:'<b>Add</b>',
		tooltip: 'Add new ap support type',
		icon: '../js/ext4/examples/shared/icons/add.png',
		scale: 'small',
		handler: function(){
			submit_form.getForm().reset();

			Ext.getCmp('fldstatus').setVisible(false);

			submit_window.show();
			submit_window.setTitle('AP Support Type Maintenance - Add');
			submit_window.setPosition(420,100);
		}
	}, '->',{
		xtype:'splitbutton',
		tooltip: 'list of reports',
		icon: '../js/ext4/examples/shared/icons/cog_edit.png',
		scale: 'small',
		/*menu:[{
			text: '<b>Sales Installment Policy Type</b>',
			icon: '../../js/ext4/examples/shared/icons/table_gear.png',
			href: 'sales_installment_policy_type.php?'
		},{
			text: '<b>Item Categories</b>',
			icon: '../../js/ext4/examples/shared/icons/chart_line.png',
			href: '../../inventory/manage/item_categories.php?',
		}, '-',{
			text: '<b>Items</b>',
			icon: '../../js/ext4/examples/shared/icons/cart.png',
			href: '../../inventory/manage/items.php?',
			hrefTarget : '_blank'
		}]*/
	}];

	var grid_panel =  Ext.create('Ext.panel.Panel', { 
        renderTo: 'ext-form',
		id: 'builder_panel',
        frame: false,
		width: 690,
		tbar: tbar,
		items: [{
			xtype: 'grid',
			id: 'gridItem',
			name: 'gridItem',
			store:	apsupport_store,
			columns: main_view,
			columnLines: true,
			autoScroll:true,
			layout:'fit',
			plugins: [cellEditing],
			frame: true,
			bbar : {
				xtype : 'pagingtoolbar',
				hidden: false,
				store : apsupport_store,
				pageSize : itemsPerPage,
				displayInfo : false,
				emptyMsg: "No records to display",
				doRefresh : function(){
					apsupport_store.load();
				}
			}
		}]
	});
});
