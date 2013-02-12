
function creategridConfigHistoryComp(keyId,fc){
		var store = new Ext.data.JsonStore({
			url: "confighistory/GetConfigHistoryComp.php?keyId=" + keyId + "&fc=" + fc,
			fields:['TS','Location','Status','Who','Config','Link','Notes','keyConfig']
		});
		store.load();
		
		var grid = new Ext.grid.GridPanel({
				
				store: store,

					columns: [
					{header: 'TS', width:130, sortable: true, dataIndex:'TS' },
					{header:'Location', width:250, sortable: true, dataIndex:'Location'},
					{header:'Status', width:150, sortable: true, dataIndex:'Status'},
					{header:'Who', width:60, sortable: true, dataIndex:'Who'},
					{header: 'Link', width: 55, sortable: false, dataIndex: 'Link', renderer: function(value, metaData, record, rowIndex, colIndex, store) { 
						if (value.length > 5){
						return '<a href="'+value+'" target="_blank">Link</a>'; }
						}},
					{header:'Config#', width:60, sortable: true, dataIndex:'Config'},
					{header:'Notes', width:550, sortable: true, dataIndex:'Notes', renderer:addTooltip}
					
					],
					
				listeners: {
		            rowclick: function(grid,rowIndex){
		    			var record= grid.getStore().getAt(rowIndex);
		    			var keyval=record.get('Config');
		    			location.href="ShowComponents.php?conf=" + keyval + "&fc=" + fc;
					}
		        },		
		        
		        plugins: new Ext.ux.plugins.HeaderButtons(),
			    hbuttons:
			    [
			        {
			            text: 'Add Notes',
			            iconCls: 'icon-save',
			            handler: function() {
			        		window.location = 'UpdateSLN_Component.php?id=' + keyId + '&fc=' + fc;
			        	}
			        }
			    ],
			        
				stripeRows: true,
				autoHeight:true,
				Width:1005,
				title:'Component History',
				loadMask:true
			
		});

		
	var container=document.getElementById('db-grid-confighistorycomp');
 	container.innerHTML="";


  	grid.render('db-grid-confighistorycomp');
  
}

function clear()
{
    store.removeAll();    
}


function addTooltip(value, metadata){
    metadata.attr = 'ext:qtip="' + value.replace(/\n/g, '<br>') + '"';
    return value;
}

