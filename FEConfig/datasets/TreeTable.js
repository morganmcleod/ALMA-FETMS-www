var gridId = Ext.id();

Ext.require([
    'Ext.tree.*',
    'Ext.data.*',
    'Ext.grid.*',
    'Ext.window.MessageBox'
]);

function CreateTree(FEid, band, datatype, dtype_desc, link){
    
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

    var store = Ext.create('Ext.data.TreeStore', {
        model:'TDH'       
        ,proxy: {
            type: 'rest',
            reader: {
                type: 'json'            
            },
            writer: {
                type: 'json'
            },
            actionMethods: {
                create : 'POST',
                read   : 'POST',
                update : 'POST',
                destroy: 'POST'
            },
     
            api: {
                 create: 'TreeGridJSON.php?action=create&FEid=' + FEid + '&band=' + band + '&datatype=' + datatype, // Called when saving new records
                   read: 'TreeGridJSON.php?action=read&FEid='   + FEid + '&band=' + band + '&datatype=' + datatype, // Called when reading existing records
                 update: 'TreeGridJSON.php?action=update&FEid=' + FEid + '&band=' + band + '&datatype=' + datatype // Called when updating existing records
            }
    
        },
        
        sorters: [{
            property: 'ts',
            direction: 'ASC'
        }]        
    });
    
    var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
        clicksToEdit: 1
    });
    
    
    Ext.define('DSG', {
        extend: 'Ext.data.Model',
        fields: [
            {name: 'datasetgroup',     type: 'string'},
            {name: 'TDHkeyId',         type: 'string'}
        ]
    });


    var DataSetGroupStore = Ext.create('Ext.data.Store', {
        id: 'dsgstore',
        model: 'DSG',
        proxy: {
           type: 'ajax',
           url: "TreeGridJSON.php?action=combobox&FEid=" + FEid  + '&datatype=' + datatype + '&band=' + band,

           reader: {
              type: 'json',
              root: 'data'
           },
           autoload:true
       }
    });
    
    var tree = Ext.create('Ext.tree.Panel', {
        //model:'TDH',
        plugins: [cellEditing],
        enableKeyEvents: true,
        store: store,
        rootVisible: false,
        useArrows: true,
        frame: true,
        title: dtype_desc + ' Data Sets Band ' + band,
        renderTo: 'tree-div',
        width: 800,
        height: 550,
        
        onSync: function(){
            alert('onSync()');
            //this.store.sync();            
        },
      
        columns: [
                  {
                      xtype: 'treecolumn', //this is so we know which column will show the tree
                      text: 'Measurement Sets',
                      width: 200,
                      flex: 2,
                      sortable: true,
                      dataIndex: 'text',
                      sm:true
                  },{
                      header: 'Data Set Group',
                      width: 100,
                      sortable: true,
                      dataIndex: 'groupnumber',
                      editor: {
                          xtype:'textfield',
                          allowBlank:false,
                          enableKeyEvents:true
                      }
                  },{
                      header: 'TS',
                      width: 130,
                      sortable: true,
                      dataIndex: 'ts'
                  },{
                      header: 'FE Config',
                      width: 60,
                      sortable: true,
                      dataIndex: 'feconfig'
                  },{
                      header: 'Notes',
                      width: 330,
                      sortable: true,
                      dataIndex: 'notes',
                      editor: {
                          xtype:'textfield',
                          allowBlank:true,
                          enableKeyEvents:true
                      }
                  }
                  
        ],
        
        selType: 'cellmodel',     
              plugins: [
                        Ext.create('Ext.grid.plugin.CellEditing', {clicksToEdit:2})
            ],
        
        dockedItems: [
                      {
                        xtype: 'toolbar',
                        items: [
                                {
                                    text: 'SAVE',
                                    id:'update',
                                    name:'update',
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
                                            DataSetGroupStore.load();
                                            // repopulate table
                                            store.read();
                                            Ext.MessageBox.hide();
                                        };
                                    
                                        Ext.Ajax.request({
                                            //Send the JSON object
                                            url: 'TreeGridJSON.php?action=update_children&FEid=' + FEid + '&band=' + band + '&datatype=' + datatype, // Called when saving new records                   
                                            success: received,
                                            jsonData:  JSON.stringify(JSONObjectArray)
                                        });
                                    
                                        //Send the top level json records to the server.
                                        store.update();
                                        Ext.MessageBox.wait('updating...');
                                    }
                                },{
                                    xtype : "combo",
                                    name : "GroupSelector",
                                    hiddenName : "datasetgroup",
                                    displayField: 'datasetgroup',
                                    valueField: 'TDHkeyId',
                                    fieldLabel : "Goto Group",
                                    id : "dsgcombo",
                                    store: DataSetGroupStore,
                                    listeners: {
                                        'select': function (combo,record) { 
                                            window.location = link + combo.getValue();          
                                        }
                                    }
                                }
                        ]
        }]
    });
};


/*

function pausecomp(millis) {
    var date = new Date();
    var curDate = null;
    do { curDate = new Date(); }
    while(curDate-date < millis);
}

*/
