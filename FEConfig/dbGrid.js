/*
 * This file is called from FEHome.php.
 * This function creates the ExtJS grid table whixh lists 
 * either front ends or components.
 * 
 * If argument is 100, the grid is for front ends. Otherwise it is components.
 */

var ListTitle = "Front Ends";

function creategrid(type, CreateToolbar) {
	if (CreateToolbar == 1) {

		ToolBar = new Ext.Toolbar({
			width : 1100,
			height : 35,

			renderTo : 'toolbar',
			items : [ {
				xtype : 'tbbutton',
				text : 'Add Front End',
				icon : 'icons/add.png',
				// cls:"ux-panel-header-btns",
				// cls: 'x-btn',
				handler : function() {
					window.location = 'AddFrontEnd.php';
				}
			}, {
				xtype : 'tbbutton',
				text : 'Create Component',
				icon : 'icons/add.png',
				style : 'margin: 1px;',
				handler : function() {
					window.location = 'AddComponents.php';
				}
			}, {
				xtype : 'tbbutton',
				text : 'Recent Tests',
				style : 'margin: 1px;',
				icon : 'icons/application_view_list.png',
				handler : function() {
					window.location = 'testdata/RecentTestList.php';
				}
			}, {
				xtype : 'tbbutton',
				text : 'Import CIDL',
				style : 'margin: 1px;',
				icon : 'icons/application_go.png',
				handler : function() {
					window.location = 'ImportCIDLform.php';
				}
			}, {
				xtype : 'combo',
				style : 'margin: 1px;',
				id : 'search2',
				name : 'state',
				width : 450,
				height : 20,
				displayField : 'stateName',
				hiddenValue : 'stateCode',
				store : 'StateStore',
				allowBlank : true,
				// forceSelection: true,
				caseSensitive : false,
				collapsible : true,
				emptyText : 'Select Component Type',
				typeAhead : true,
				queryMode : 'remote',
				minChars : 1,
				triggerAction : 'all',
				listeners : {
					'select' : function(combo, record) {
						ListTitle = record.data['stateName'];
						creategrid(record.data['stateCode'], 0);
					}
				}
			}

			]
		});
	}

	if (type == 100) {
		// This grid is for Front Ends
		// Column headers
		var store = new Ext.data.JsonStore({
			url : "GetFEData.php?ctype=" + type,
			id : 'config',
			fields : [ {
				name : 'config',
				type : 'int'
			}, {
				name : 'SN',
				sortType : 'asText'
			}, {
				name : 'Location',
				sortType : 'asText'
			}, {
				name : 'Status',
				sortType : 'asText'
			}, {
				name : 'Updated_By',
				sortType : 'asText'
			}, {
				name : 'Docs',
				sortType : 'asText'
			}, 'Notes', 'TS', 'keyFacility' ]
		});
		store.load();

		// Front End row values
		var grid = new Ext.grid.GridPanel({

			store : store,
			columns : [
					{
						header : 'SN',
						width : 40,
						sortable : true,
						dataIndex : 'SN'
					},
					{
						header : 'TS',
						width : 120,
						sortable : true,
						dataIndex : 'TS'
					},
					{
						header : 'Updated By',
						width : 70,
						sortable : true,
						dataIndex : 'Updated_By'
					},
					{
						header : 'Status',
						width : 103,
						sortable : true,
						dataIndex : 'Status'
					},
					{
						header : 'Location',
						width : 270,
						sortable : true,
						dataIndex : 'Location',
						align : 'left'
					},
					{
						header : 'Docs',
						width : 55,
						sortable : false,
						dataIndex : 'Docs',
						renderer : function(value, metaData, record, rowIndex,
								colIndex, store) {
							if (value.length > 5) {
								return '<a href="' + value
										+ '" target="_blank">Link</a>';
							}
						}
					},

					{
						header : 'Notes',
						width : 470,
						sortable : true,
						dataIndex : 'Notes',
						align : 'left',
						renderer : addTooltip
					} ],
			listeners : {
				rowclick : function(grid, rowIndex) {
					var record = grid.getStore().getAt(rowIndex);
					var keyval = record.get('config');
					var fc = record.get('keyFacility');
					location.href = "ShowFEConfig.php?key=" + keyval + "&fc="
							+ fc;
				}
			},

			stripeRows : true,
			autoHeight : true,
			autoWidth : true,
			Width : 910,
			title : ListTitle,
			loadMask : true
		});

	}

	else {
		// This grid is for components
		var store = new Ext.data.JsonStore({
			url : "GetFEData.php?ctype=" + type,
			id : 'config',
			fields : [ {
				name : 'config',
				type : 'int'
			},
			// {name:'SN', sortType:'asInt'},
			{
				name : 'SN'
			}, {
				name : 'Description',
				sortType : 'asText'
			}, {
				name : 'Status',
				sortType : 'asText'
			}, {
				name : 'Updated_By',
				sortType : 'asText'
			}, {
				name : 'Band',
				sortType : 'asInt'
			}, 'Notes', 'TS', 'keyFacility', 'FESN', 'Location' ]
		});
		store.load();

		var grid = new Ext.grid.GridPanel({
			// This gets the data for the components list table on FEHome.php
			// Component row values
			store : store,
			columns : [ {
				header : 'Band',
				width : 52,
				sortable : true,
				dataIndex : 'Band'
			}, {
				header : 'SN',
				width : 40,
				sortable : true,
				dataIndex : 'SN'
			},

			{
				header : 'TS',
				width : 120,
				sortable : true,
				dataIndex : 'TS'
			}, {
				header : 'Updated By',
				width : 75,
				sortable : true,
				dataIndex : 'Updated_By'
			}, {
				header : 'Status',
				width : 103,
				sortable : true,
				dataIndex : 'Status'
			}, {
				header : 'Location',
				width : 280,
				sortable : true,
				align : 'left',
				dataIndex : 'Location'
			}, {
				header : 'Notes',
				width : 370,
				sortable : true,
				align : 'left',
				dataIndex : 'Notes',
				renderer : addTooltip
			}, {
				header : 'Front End',
				width : 60,
				sortable : true,
				dataIndex : 'FESN'
			} ],
			listeners : {
				rowclick : function(grid, rowIndex) {
					var record = grid.getStore().getAt(rowIndex);
					var ser = record.get('SN');
					var keyval = record.get('config');
					var band = record.get('Band');
					var fc = record.get('keyFacility');
					location.href = "ShowComponents.php?conf=" + keyval
							+ "&fc=" + fc;

				},

				render : function(grid) {
					grid.body.mask('Loading...');
					var store = grid.getStore();
					store.load.defer(100, store);
					grid.body.unmask();
				}

			},
			stripeRows : true,
			autoHeight : true,
			remoteSort : true,

			sortInfo : {
				field : 'Band',
				direction : "ASC",
				field : 'SN',
				direction : "ASC"
			},

			Width : 1100,
			title : ListTitle,
			loadMask : true
		});
		store.setDefaultSort('Band', 'ASC');

	}

	var container = document.getElementById('db-grid');
	container.innerHTML = "";

	grid.render('db-grid');
}

StateStore = Ext.extend(Ext.data.JsonStore, {
	constructor : function(cfg) {
		cfg = cfg || {};
		StateStore.superclass.constructor.call(this, Ext.apply({
			storeId : 'StateStore',
			root : 'records',
			url : 'GetCtypes.php',
			idProperty : 'stateCode',
			messageProperty : 'message',
			fields : [ {
				name : 'stateName'
			}, {
				name : 'stateCode'
			} ]
		}, cfg));
	}
});
Ext.reg('stateStore', StateStore);
new StateStore();

function FixHyperlink(value) {
	var result = value;
	if (value.search("http") > -1) {// If url starts with http://, leave it as
									// is.
		result = value;
	}
	if (value.search("http") == -1) {// result = "http://" + value;
		if (value.match(/[!\\]/g) == null) {// Path is Internet addess, but
											// doesn't start with "http://".
			result = "http://" + value;
		} else {// Path is UNC, need to add a prefix
			if (/MSIE (\d+\.\d+);/.test(navigator.userAgent)) {// If browser is
																// Internet
																// Explorer
				result = "file://" + value;
			} else {// If browser is Firefox
				result = "file:///" + value;
			}
		}
	}
	return result;
}

function clear() {
	store.removeAll();
	ToolBar.doLayout();
}

function addTooltip(value, metadata) {
	metadata.attr = 'ext:qtip="' + value.replace(/\r?\n/g, '<br>') + '"';
	return value;
}
