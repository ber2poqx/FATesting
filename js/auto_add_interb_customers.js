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
	'Ext.ux.form.SearchField'
]);

Ext.onReady(function(){
	Ext.QuickTips.init();
	var itemsPerPage = 18;   // set the number of items you want per page on grid.
	var showall = false;
	var maxfields = 10; //change this number if you want to increase/decrease adding fields.
	//var url_branch = getUrlParameter('branch');
	//var url_debtorno = getUrlParameter('customer');

	////define model for policy installment
    Ext.define('interb_cust_model',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'customer_code', mapping:'customer_code'},
			{name:'customer_name', mapping:'customer_name'},
			{name:'address', mapping:'address'},
			{name:'tel_no', mapping:'tel_no'},
			{name:'barangay', mapping:'barangay'},
			{name:'Province', mapping:'Province'},
			{name:'Municipality', mapping:'Municipality'},
			{name:'Zip_Code', mapping:'Zip_Code'},
			{name:'TINNo', mapping:'TINNo'},
			{name:'Age', mapping:'Age'},
			{name:'Gender', mapping:'Gender'},
			{name:'Status', mapping:'Status'},
			{name:'father_name', mapping:'father_name'},
			{name:'mother_name', mapping:'mother_name'},
			{name:'branch_code', mapping:'branch_code'},
			{name:'trans_no', mapping:'trans_no'},
			{name:'type', mapping:'type'}
		]
	});
	Ext.define('comboModel',{
        extend: 'Ext.data.Model',
        fields: [
			{name:'id', mapping:'id'},
			{name:'name', mapping:'name'},
			{name:'value', mapping:'value'}
		]
    });
	//------------------------------------: stores :----------------------------------------
	var CustomerAreaStore = Ext.create('Ext.data.Store', {
		model: 'comboModel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_area=xx',
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
	var CollectorStore = Ext.create('Ext.data.Store', {
		model: 'comboModel',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
		proxy: {
			url: '?get_collector=xx',
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
	var genderstore = Ext.create('Ext.data.Store',{
		fields: ['id','name'],
		autoLoad: true,
		data : 	[
			{"value":"Male"},
            {"value":"Female"}
        ]
	});
	var Branchstore = Ext.create('Ext.data.Store', {
		name: 'Branchstore',
		fields:['id','name','area'],
		autoLoad : true,
        proxy: {
			url: '?getbranch=00',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'name',
			direction : 'ASC'
		}]
	});
	var InterB_Cust_store = Ext.create('Ext.data.Store', {
        model: 'interb_cust_model',
		autoLoad : true,
		pageSize: itemsPerPage, // items per page
        proxy: {
			url: '?get_InterB_Customers=zHun',
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'result',
				totalProperty  : 'total'
			}
		},
		simpleSortMode : true,
		sorters : [{
			property : 'user_name',
			direction : 'ASC'
		}]
	});

	var submit_form = Ext.create('Ext.form.Panel', {
		id: 'form_submit',
		model: 'interb_cust_model',
		frame: true,
		defaultType: 'field',
		defaults: {margin: '2 0 2 5', msgTarget: 'under', anchor: '-5'}, //msgTarget: 'side', labelAlign: 'top'
		items: [{
			xtype: 'textfield',
			id: 'cust_code',
			name: 'cust_code',
			fieldLabel: 'cust_code',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			id: 'branch_code',
			name: 'branch_code',
			fieldLabel: 'branch_code',
			allowBlank: false,
			hidden: true
		},{
			xtype: 'textfield',
			fieldLabel: '<b>Customer Name</b>',
			id: 'customer_name',
			name: 'customer_name',
			labelWidth: 120,
			width: 250,
			readOnly: true,
			allowBlank: false,
			fieldStyle: 'font-weight: bold; color: #210a04;'
		},{
			xtype: 'textfield',
			fieldLabel: '<b>Current Address</b>',
			id: 'address',
			name: 'address',
			labelWidth: 120,
			width: 250,
			//readOnly: true,
			allowBlank: false,
			fieldStyle: 'font-weight: bold; color: #210a04;'
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'textfield',
				fieldLabel: '<b>Marital status </b>',
				id: 'maritalstatus',
				name: 'maritalstatus',
				labelWidth: 120,
				width: 291,
				//readOnly: true,
				allowBlank: false,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'combobox',
				id: 'gender',
				name: 'gender',
				fieldLabel: '<b>Gender </b>',
				store: genderstore,
				displayField: 'value',
				valueField: 'value',
				queryMode: 'local',
				labelWidth: 80,
				width: 291,
				allowBlank: false,
				forceSelection: true,
				selectOnFocus:true,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype : 'datefield',
				id	  : 'age',
				name  : 'age',
				fieldLabel : '<b>Birth Date </b>',
				labelWidth: 120,
				width: 291,
				format : 'm/d/Y',
				allowBlank: false,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'textfield',
				fieldLabel: '<b>Phone No. </b>',
				id: 'phone',
				name: 'phone',
				labelWidth: 80,
				width: 291,
				//readOnly: true,
				allowBlank: false,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'textfield',
				fieldLabel: '<b>Municipality/City </b>',
				id: 'Municipality',
				name: 'Municipality',
				labelWidth: 120,
				width: 291,
				//readOnly: true,
				allowBlank: false,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'textfield',
				fieldLabel: '<b>Province </b>',
				id: 'province',
				name: 'province',
				labelWidth: 80,
				width: 291,
				//readOnly: true,
				allowBlank: false,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			}]
		},{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			margin: '2 0 2 5',
			items:[{
				xtype: 'textfield',
				fieldLabel: '<b>Barangay </b>',
				id: 'barangay',
				name: 'barangay',
				labelWidth: 120,
				width: 291,
				//readOnly: true,
				allowBlank: false,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			},{
				xtype: 'textfield',
				fieldLabel: '<b>Zip Code </b>',
				id: 'Zip_Code',
				name: 'Zip_Code',
				labelWidth: 80,
				width: 291,
				//readOnly: true,
				allowBlank: false,
				fieldStyle: 'font-weight: bold; color: #210a04;'
			}]
		},{
			xtype: 'textfield',
			fieldLabel: '<b>Father name</b>',
			id: 'father',
			name: 'father',
			labelWidth: 120,
			width: 250,
			//readOnly: true,
			allowBlank: false,
			fieldStyle: 'font-weight: bold; color: #210a04;'
		},{
			xtype: 'textfield',
			fieldLabel: '<b>Mother name </b>',
			id: 'mother',
			name: 'mother',
			labelWidth: 120,
			width: 250,
			//readOnly: true,
			allowBlank: false,
			fieldStyle: 'font-weight: bold; color: #210a04;'
		},{
			xtype: 'combobox',
			id: 'area',
			name: 'area',
			fieldLabel: '<b>Area </b>',
			allowBlank: false,
			store : CustomerAreaStore,
			displayField: 'name',
			valueField: 'id',
			queryMode: 'local',
			labelWidth: 120,
			forceSelection: true,
			selectOnFocus:true,
			fieldStyle: 'font-weight: bold; color: #210a04;',
			listeners: {
				select: function(combo, record, index) {
					CollectorStore.proxy.extraParams = {user_id: record.get('value')};
					CollectorStore.load();

					if(record.get('value') != Ext.getCmp('collector').getValue()){
						Ext.getCmp('collector').setValue(record.get('value'))
					}
				}
			}
		},{
			xtype: 'combobox',
			id: 'collector',
			name: 'collector',
			fieldLabel: '<b>Collector </b>',
			allowBlank: false,
			store : CollectorStore,
			displayField: 'name',
			valueField: 'id',
			queryMode: 'local',
			labelWidth: 120,
			forceSelection: true,
			selectOnFocus:true,
			fieldStyle: 'font-weight: bold; color: #210a04;',
			listeners: {
				
			}
		}]
	});
	var submit_window = Ext.create('Ext.Window',{
		width 	: 600,
		modal	: true,
		plain 	: true,
		border 	: false,
		resizable: false,
		closeAction:'hide',
		//closable: false,
		items:[submit_form],
		buttons:[{
			text: 'Save',
			tooltip: 'Save customer',
			icon: '../../js/ext4/examples/shared/icons/add.png',
			single : true,				
			handler:function(){
				var form_submit = Ext.getCmp('form_submit').getForm();
				if(form_submit.isValid()) {
					form_submit.submit({
						url: '?submit=info',
						waitMsg: 'Saving customer. please wait...',
						method:'POST',
						success: function(form_submit, action) {
							//show and load new added
							//InterB_Cust_store.load();
							Ext.Msg.alert('Success!', '<font color="green">' + action.result.message + '</font>');
							submit_window.close();
							window.open('../../sales/inquiry/customers_list.php?popup=1&client_id=customer_id');
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
			tooltip: 'Cancel builder',
			icon: '../../js/ext4/examples/shared/icons/cancel.png',
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
	//---------------------------------------------------------------------------------------
	//for policy setup column model
	var InterB_Customer_Header = [
		new Ext.grid.RowNumberer(),
		{header:'<b>Customer Code</b>', dataIndex:'customer_code', sortable:true, width:130},
		{header:'<b>Customer Name</b>', dataIndex:'customer_name', sortable:true, width:215},
		{header:'<b>Address</b>', dataIndex:'address', sortable:true, width:230},
		{header:'<b>Phone No.</b>', dataIndex:'tel_no', sortable:true, width:130},
		{header:'<b>Gender</b>', dataIndex:'Gender', sortable:true, width:75},
		{header:'<b>Action</b>',xtype:'actioncolumn', align:'center', width:95,
			items:[{
				icon: '../../js/ext4/examples/shared/icons/layout_content.png',
				tooltip: 'view details',
				handler: function(grid, rowIndex, colIndex) {
					var records = InterB_Cust_store.getAt(rowIndex);
					
					submit_form.getForm().reset();

					Ext.getCmp('branch_code').setValue(records.get('branch_code'));
					Ext.getCmp('cust_code').setValue(records.get('customer_code'));
					Ext.getCmp('customer_name').setValue(records.get('customer_name'));
					Ext.getCmp('address').setValue(records.get('address'));
					Ext.getCmp('maritalstatus').setValue(records.get('Status'));
					Ext.getCmp('gender').setValue(records.get('Gender'));
					Ext.getCmp('age').setValue(records.get('Age'));
					Ext.getCmp('Municipality').setValue(records.get('Municipality'));
					Ext.getCmp('province').setValue(records.get('Province'));
					Ext.getCmp('barangay').setValue(records.get('barangay'));
					Ext.getCmp('Zip_Code').setValue(records.get('Zip_Code'));
					Ext.getCmp('phone').setValue(records.get('tel_no'));
					Ext.getCmp('father').setValue(records.get('father_name'));
					Ext.getCmp('mother').setValue(records.get('mother_name'));

					submit_window.setTitle('Inter-Branch Customer Details - ' + Ext.getCmp('branchcode').getRawValue());
					submit_window.show();
					submit_window.setPosition(330,90);
				}
			},'-',{
				icon: '../../js/ext4/examples/shared/icons/print-preview-icon.png',
				tooltip: 'view installment ledger report',
				handler: function(grid, rowIndex, colIndex) {
					var records = InterB_Cust_store.getAt(rowIndex);
					
					window.open('../../reports/installment_inquiry.php?&trans_no=' + records.get('trans_no') + '&trans_type='+records.get('type')+ '&branch_code='+records.get('branch_code'));
				}
			}]
		}
	];
	var tbar = [{
		xtype: 'combobox',
		id: 'branchcode',
		name: 'branchcode',
		fieldLabel: '<b>Branch </b>',
		store: Branchstore,
		displayField: 'name',
		valueField: 'id',
		queryMode: 'local',
		emptyText:'Select Branch',
		labelWidth: 60,
		width: 350,
		anyMatch: true,
		forceSelection: true,
		selectOnFocus:true,
		fieldStyle : 'text-transform: capitalize; background-color: #F2F3F4; color:green; ',
		listeners: {
			select: function(combo, record, index) {
				var search = Ext.getCmp("search").getValue();

				InterB_Cust_store.proxy.extraParams = {branch: combo.getValue(), query: Ext.getCmp('search').getValue()};
				InterB_Cust_store.load();
			}
		}
	}, '-', {
		xtype: 'searchfield',
		id:'search',
		name:'search',
		fieldLabel: '<b>Search</b>',
		labelWidth: 50,
		width: 305,
		emptyText: "Search by name...",
		scale: 'small',
		store: InterB_Cust_store,
		listeners: {
			change: function(field) {

				InterB_Cust_store.proxy.extraParams = {branch: Ext.getCmp('branchcode').getValue(), query: field.getValue()};
				InterB_Cust_store.load();
			}
		}
	}, '->' ,{
		xtype:'splitbutton',
		//text: '<b>Maintenance</b>',
		tooltip: 'Select...',
		icon: '../../js/ext4/examples/shared/icons/cog_edit.png',
		scale: 'small',
		menu:[{
			text: '<b>Customer List</b>',
			icon: '../../js/ext4/examples/shared/icons/map_magnify.png',
			href: '../../sales/inquiry/customers_list.php?popup=1&client_id=customer_id',
			hrefTarget : '_blank'
		}]
	}];

	var builder_panel =  Ext.create('Ext.panel.Panel', { 
        renderTo: 'ext-form',
		id: 'builder_panel',
        frame: false,
		width: 915,
		tbar: tbar,
		items: [{
			xtype: 'grid',
			id: 'CheckOpUser_grid',
			name: 'CheckOpUser_grid',
			store:	InterB_Cust_store,
			columns: InterB_Customer_Header,
			columnLines: true,
			autoScroll:true,
			layout:'fit',
			frame: true,
			bbar : {
				xtype : 'pagingtoolbar',
				hidden: false,
				store : InterB_Cust_store,
				pageSize : itemsPerPage,
				displayInfo : false,
				emptyMsg: "No records to display",
				doRefresh : function(){
					InterB_Cust_store.load();
					
				}
			}
		}]
	});
	/*alert('a');
	InterB_Cust_store.proxy.extraParams = {query: url_debtorno, branch: url_branch };
	InterB_Cust_store.load();
	

	function getUrlParameter(sParam) {
		var sPageURL = decodeURIComponent(window.location.search.substring(1)),
			sURLVariables = sPageURL.split('&'),
			sParameterName,
			i;

		for (i = 0; i < sURLVariables.length; i++) {
			sParameterName = sURLVariables[i].split('=');

			if (sParameterName[0] === sParam) {
				return sParameterName[1] === undefined ? true : sParameterName[1];
			}
		}
	};*/
});
