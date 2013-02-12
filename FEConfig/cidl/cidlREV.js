/*
 * This is called from cidlRevEdit.php
 * 
 * A REST proxy is used for comunicating JSON data (CRUD operations) 
 * between client and server.
 * 
 */
Ext.require(['Ext.data.*', 'Ext.grid.*']);

Ext.define('ChangeRecordRow', {
    extend: 'Ext.data.Model',
    fields: [{
        name: 'id',
        type: 'int',
        useNull: true
    }
    , 
    'revision', 'remarks','affectedpages',
    { name: 'date', type: 'date', dateFormat: 'Y-n-j' }]

,
    validations: [
    {
        type: 'length',
        field: 'revision',
        min: 1
    }, {
        type: 'length',
        field: 'remarks',
        min: 1
    }]
});

function creategrid(keyfe,feconfig){
    var store = Ext.create('Ext.data.Store', {
        autoLoad: true,
        autoSync: true,
        model: 'ChangeRecordRow',
        proxy: {
            type: 'rest',
            reader: {
                type: 'json',
                root: 'data'
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
                 create: 'CIDLactions.php?keyfe=' + keyfe + '&action=create', // Called when saving new records
                   read: 'CIDLactions.php?keyfe=' + keyfe + '&action=read', // Called when reading existing records
                 update: 'CIDLactions.php?keyfe=' + keyfe + '&action=update', // Called when updating existing records
                destroy: 'CIDLactions.php?keyfe=' + keyfe + '&action=destroy' // Called when deleting existing records
            }
        }
 ,
        listeners: {
            write: function(store, operation){
                var record = operation.getRecords()[0],
                    name = Ext.String.capitalize(operation.action),
                    verb;
                if (name == 'Cancel') {
                	alert('cancel');
                }   
            }
        }
    });
    
    var rowEditing = Ext.create('Ext.grid.plugin.RowEditing');
    
    var grid = Ext.create('Ext.grid.Panel', {
        renderTo: 'editor-grid',
        plugins: [rowEditing],
        width: 625,
        height: 300,
   
        frame: true,
        title: 'CIDL Change Record',
        store: store,
        columns: [
        {
            header: 'Revision',
            width: 80,
            sortable: true,
            dataIndex: 'revision',
            field: {
                xtype: 'textfield'
            }
        }
        , {
            xtype: 'datecolumn',
            header: 'Date',
            dataIndex: 'date',
            width: 90,
            editor: {
                xtype: 'datefield',
                allowBlank: false,
                format: 'm/d/Y',
                maxValue: Ext.Date.format(new Date(), 'm/d/Y')
            }
        }
        , {
            header: 'Affected Pages',
            width: 220,
            sortable: true,
            dataIndex: 'affectedpages',
            field: {
                xtype: 'textfield'
            }
        }
        , {
            text: 'Reason/Initiation/Remarks',
            width: 220,
            sortable: true,
            dataIndex: 'remarks',
            field: {
                xtype: 'textfield'
            }
        }],
        dockedItems: [{
            xtype: 'toolbar',
            items: [{
                text: 'Add',
                icon:'../icons/add.png',
                handler: function(){

                	// Create a model instance with some default values when a new row is to be added.
                    var r = Ext.create('ChangeRecordRow', {
                    	id:0,
                    	email:'',
                        affectedpages: 'All',
                        date: new Date(),
                        remarks: 'Draft'
                    });

                    store.insert(0, r);
                    rowEditing.startEdit(0, 0);
                }
            }, '-', {
                itemId: 'delete',
                text: 'Delete',
                icon:'../icons/delete.png',
                disabled: true,
                handler: function(){
            		//Clear out the new row if the user presses Delete
                    var selection = grid.getView().getSelectionModel().getSelection()[0];
                    if (selection) {
                        store.remove(selection);
                    }
                }
            }
           ,'->' , {
                itemId: 'generate',
                text: 'Generate CIDL',
                icon:'../icons/report.png',
                handler: function(){
        	   		//Generate the CIDL pdf document when pressed.
                	CreateCIDL(feconfig,"","FEND-40.00.00.00-XXXX-A-LIS",40);
                    
                }
            }
            ]
        }]
    });
    grid.getSelectionModel().on('selectionchange', function(selModel, selections){
        grid.down('#delete').setDisabled(selections.length === 0);
    });
}

