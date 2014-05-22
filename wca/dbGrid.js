
function creategrid(band){
		var store = new Ext.data.JsonStore({
			url: "GetWCAData.php?band=" + band,
			id:'Band_selected',
			bg:'alt-row',
			fields:[{name:'SN', type:'string'},'Band','keyId','TS','keyFacility','bgcolor']
		});
		store.load();
		
			
		var grid = new Ext.grid.GridPanel({
				
				store: store,

					columns: [
					{header:'Band', width:100, sortable: true, dataIndex:'Band'},
					{header: 'SN', width:100, sortable: true, dataIndex:'SN' },
					{header:'Date Added', width:130, sortable: true, dataIndex:'TS'}
					],
				 listeners: {
		            rowclick: function(grid,rowIndex){
		    			var record= grid.getStore().getAt(rowIndex);
		    			var keyval=record.get('keyId');
		    			var fc=record.get('keyFacility');
		    			location.href="wca.php?keyId=" + keyval + "&fc=" + fc;
					}
		        },
		        
		        //Alternate background color of table rows
		        view: new Ext.grid.GridView({
			          getRowClass: function(record, rowIndex, rp, ds){ // rp = rowParams
			        	  	return record.get('bgcolor');
			          }
			    }),
			        
				stripeRows: true,
				autoHeight:true,
				title:'WCAs',
				loadMask:true
			
		});
		
	var container=document.getElementById('db-grid');
 	container.innerHTML="";


  	grid.render('db-grid');
  
}

function clear()
{
    store.removeAll();    
}
