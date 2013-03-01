Ext.require([
    'Ext.tree.*',
    'Ext.data.*',
    'Ext.grid.*',
    'Ext.window.MessageBox'
]);

function CreateTree(FEid, band, datatype, dtype_desc, link, idBack){

	Ext.define('TDH', {
        extend: 'Ext.data.Model',
        fields: [
            {name: 'id',          type: 'string'},
            {name: 'groupnumber', type: 'string'},
            {name: 'feconfig',    type: 'string'},
            {name: 'ts',          type: 'string'},
            {name: 'text',        type: 'string'},
            {name: 'notes',       type: 'textbox'}
        ]
    });

    var TDHStore = Ext.create('Ext.data.TreeStore', {
    	model:'TDH',       
        proxy: {
            type: 'ajax',
//            reader: {
//                type: 'json'            
//            },
//            writer: {
//                type: 'json'
//            },
            actionMethods: {
                create : 'POST',
                read   : 'POST',
                update : 'POST',
                destroy: 'POST'
            },     
            api: {
                 create: 'TreeGridJSON.php?action=create&FEid=' + FEid + '&band=' + band + '&datatype=' + datatype, // Called when saving new records
                   read: 'TreeGridJSON.php?action=read&FEid='   + FEid + '&band=' + band + '&datatype=' + datatype, // Called when reading existing records
                 update: 'TreeGridJSON.php?action=update&FEid=' + FEid + '&band=' + band + '&datatype=' + datatype  // Called when updating existing records
            }
    
        }
//        ,        
//        sorters: [{
//            property: 'ts',
//            direction: 'ASC'
//        }]        
    });
    
    //var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
      //  clicksToEdit: 1
    //});
    
    
//    Ext.define('DSG', {
//        extend: 'Ext.data.Model',
//        fields: [
//            {name: 'datasetgroup',     type: 'string'},
//            {name: 'TDHkeyId',         type: 'string'}
//        ]
//    });
//
//    var DataSetGroupStore = Ext.create('Ext.data.Store', {
//        id: 'dsgstore',
//        model: 'DSG',
//        proxy: {
//           type: 'ajax',
//           url: "TreeGridJSON.php?action=combobox&FEid=" + FEid  + '&datatype=' + datatype + '&band=' + band,
//           reader: {
//              type: 'json',
//              root: 'data'
//           },
//           autoload:true
//       }
//    });
    
    var tree = Ext.create('Ext.tree.Panel', {
    	title: dtype_desc + ' Data Sets Band ' + band,
    	store: TDHStore,
    	enableKeyEvents: true,
        rootVisible: false,
        useArrows: true,
        frame: true,
        renderTo: 'tree-div',
        width: 800,
        height: 550,
       
//        onSync: function(){
//            alert('onSync()');
//            //this.store.sync();            
//        },
      
        columns: [
              {
                  xtype: 'treecolumn', //this is so we know which column will show the tree
                  text: 'Measurement Sets',
                  dataIndex: 'text',
                  width: 200,
                  sortable: true,
                  flex: 2,
                  sm:true
              },{
                  header: 'Data Set Group',
                  dataIndex: 'groupnumber',
                  editor: 'textfield', 
                  width: 100,
                  sortable: true
              },{
                  header: 'TS',
                  dataIndex: 'ts',
                  width: 130,
                  sortable: true
              },{
                  header: 'FE Config',
                  dataIndex: 'feconfig',
                  width: 60,
                  sortable: true
              },{
                  header: 'Notes',
                  dataIndex: 'notes',
                  editor: {
                      xtype:'textfield',
                      allowBlank:true,
                      enableKeyEvents:true
                  },
                  width: 330,
                  sortable: true
              }
        ],

        selType: 'cellmodel',

        plugins: [
            Ext.create('Ext.grid.plugin.CellEditing', {clicksToEdit:1})
        ]
        
//        dockedItems: [{ 
//        	xtype: 'button',
//            text: 'SAVE',
//            id: 'update',
//            name: 'update',
//            icon:'../icons/disk.png'
//        },{
//            xtype : "combo",
//            name : "GroupSelector",
//            hiddenName : "datasetgroup",
//            displayField: 'datasetgroup',
//            valueField: 'TDHkeyId',
//            fieldLabel : "Goto Group",
//            id : "dsgcombo",
//            store: DataSetGroupStore,
//            listeners: {
//                'select': function (combo,record) { 
//                    window.location = link + combo.getValue();          
//                }
//            }
//        }]

});
    
    tree.addDocked({
        dock: 'top',
        xtype: 'toolbar',
        items: [{
	    	xtype: 'button',
	        text: 'SAVE',
	        id: 'update',
	        name: 'update',
	        icon:'../icons/disk.png',
	        scope: this,
            handler: function() {
               var IsChecked;
               var counter = 0;
               var JSONObjectArray = new Array();
           
               tree.getView().node.cascadeBy(function(rec) {
                   //Since ExtJS4 doesn't send updated child node values from client to server,
                   //we must iterate through the child nodes and send them one by one.
                   //This loop populates a JSON object.                                        
                   JSONObjectArray[counter]          = new Object;
                   JSONObjectArray[counter].subid    = rec.get('id');
                   JSONObjectArray[counter].FEid     = FEid;
                   JSONObjectArray[counter].datatype = datatype;
           
                   IsChecked = rec.get('checked');                                        
                   if (IsChecked == true){
                       JSONObjectArray[counter].checked  = '1';
                   } else {
                       JSONObjectArray[counter].checked  = '0';
                   }
                   counter+=1;
               });  
           
               var received = function (response) {
                   //Reload the combobox DataSet values.
//                   DataSetGroupStore.load();
                   // repopulate table
                   TDHStore.read();
                   Ext.MessageBox.hide();
               };
           
               Ext.Ajax.request({
                   //Send the JSON object
                   url: 'TreeGridJSON.php?action=update_children&FEid=' + FEid + '&band=' + band + '&datatype=' + datatype, // Called when saving new records                   
                   success: received,
                   jsonData:  JSON.stringify(JSONObjectArray)
               });
           
               //Send the top level json records to the server.
               TDHStore.update();
               Ext.MessageBox.wait('updating...');
           }
        }
        
        , { 
        	xtype: 'button',
	        text: 'DONE',
	        id: 'done',
	        name: 'done',
	        icon:'../icons/door_out.png',
	        scope: this,
            handler: function() {
            	window.location = link + idBack;
            }
        }
        
//        ,{
//        	xtype : "combo",
//        	fieldLabel : "Goto Group",        	
//        	store: DataSetGroupStore,
//        	queryMode: 'remote',        	
//        	valueField: 'TDHkeyId',
//        	name : "GroupSelector",
//        	hiddenName : "datasetgroup",
//        	displayField: 'datasetgroup',
//        	id : "dsgcombo",
//
//        	listeners: {
//        		scope: this,
//        		'select': function(combo,record) {
//        			window.location = link + combo.getValue();
//        		}
//        	}
//        }
                
        ]
    });
    
};

