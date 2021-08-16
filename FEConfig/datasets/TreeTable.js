Ext.require([
    'Ext.tree.*',
    'Ext.data.*',
    'Ext.grid.*',
    'Ext.window.MessageBox'
]);

function CreateTree(FEid, band, datatype, dtype_desc, link, TDHid, compId) {

    Ext.define('TDH', {
        extend: 'Ext.data.Model',
        fields: [
            { name: 'id', type: 'string' },
            { name: 'groupnumber', type: 'string' },
            { name: 'config', type: 'string' },
            { name: 'ts', type: 'string' },
            { name: 'text', type: 'string' },
            { name: 'notes', type: 'textbox' }
        ]
    });

    var TDHStore = Ext.create('Ext.data.TreeStore', {
        model: 'TDH',
        proxy: {
            type: 'ajax',
            actionMethods: {
                create: 'POST',
                read: 'POST',
                update: 'POST',
                destroy: 'POST'
            },
            api: {
                create: 'TreeGridJSON.php?action=create&FEid=' + FEid + '&band=' + band + '&datatype=' + datatype + '&comp=' + compId,
                // Called when saving new records
                read: 'TreeGridJSON.php?action=read&FEid=' + FEid + '&band=' + band + '&datatype=' + datatype + '&comp=' + compId,
                // Called when reading existing records
                update: 'TreeGridJSON.php?action=update&FEid=' + FEid + '&band=' + band + '&datatype=' + datatype + '&comp=' + compId
                // Called when updating existing records
            }

        }
    });

    // A place to cache the original child node data we were sent so we can detect changes on SAVE:
    var ODArray = new Array();
    var ODSize = 0;

    var tree = Ext.create('Ext.tree.Panel', {
        title: dtype_desc + ' Data Sets Band ' + band,
        store: TDHStore,
        enableKeyEvents: true,
        rootVisible: false,
        useArrows: true,
        frame: true,
        renderTo: 'tree-div',
        width: 850,
        height: 550,

        columns: [
            {
                xtype: 'treecolumn', //this is so we know which column will show the tree
                text: 'Measurement Sets',
                dataIndex: 'text',
                width: 250,
                sortable: true,
                flex: 2,
                sm: true
            }, {
                header: 'Data Set Group',
                dataIndex: 'groupnumber',
                editor: 'textfield',
                width: 100,
                sortable: true
            }, {
                header: 'TS',
                dataIndex: 'ts',
                width: 130,
                sortable: true
            }, {
                header: 'Config',
                dataIndex: 'config',
                width: 60,
                sortable: true
            }, {
                header: 'Notes',
                dataIndex: 'notes',
                editor: {
                    xtype: 'textfield',
                    allowBlank: true,
                    enableKeyEvents: true
                },
                width: 330,
                sortable: true
            }
        ],

        selType: 'cellmodel',

        plugins: [
            Ext.create('Ext.grid.plugin.CellEditing', { clicksToEdit: 1 })
        ],

        listeners: {
            'load': function () {
                this.getView().node.cascadeBy(function (rec) {
                    if (rec.isLeaf()) {
                        // cache the data in each child node so we can only send the ones which have changed on SAVE:
                        ODArray[ODSize] = new Object;
                        ODArray[ODSize].subid = rec.get('id');
                        ODArray[ODSize].FEid = FEid;
                        ODArray[ODSize].datatype = datatype;
                        ODArray[ODSize].checked = (rec.get('checked')) ? '1' : '0';
                        ODSize++;
                    }
                });
            }
        }
    });

    tree.addDocked({
        dock: 'top',
        xtype: 'toolbar',
        items: [{
            xtype: 'button',
            text: 'SAVE',
            id: 'update',
            name: 'update',
            icon: '../icons/disk.png',
            scope: this,
            handler: function () {
                var IsChecked;
                var ODChecked;
                var ODCount = 0;
                var outputSize = 0;
                var JSONObjectArray = new Array();

                tree.getView().node.cascadeBy(function (rec) {
                    //Since ExtJS4 doesn't send updated child node values from client to server,
                    //we must iterate through the child nodes and send them one by one.
                    //This loop populates a JSON object.
                    if (rec.isLeaf()) {
                        IsChecked = rec.get('checked');
                        subid = rec.get('id');
                        ODChecked = (ODArray[ODCount].checked == '1');
                        // guard against IsChecked == null because we're getting an extra garbage record.
                        if (IsChecked != null && IsChecked != ODChecked) {
                            JSONObjectArray[outputSize] = new Object;
                            JSONObjectArray[outputSize].subid = subid;
                            JSONObjectArray[outputSize].FEid = FEid;
                            JSONObjectArray[outputSize].datatype = datatype;
                            JSONObjectArray[outputSize].checked = (IsChecked) ? '1' : '0';
                            outputSize++;
                        }
                        ODCount++;
                    }
                });

                if (outputSize > 0) {
                    Ext.MessageBox.wait('updating...');

                    var received = function (response) {
                        // repopulate table
                        TDHStore.read();
                        Ext.MessageBox.hide();
                    };

                    Ext.Ajax.request({
                        //Send the JSON object
                        url: 'TreeGridJSON.php?action=update_children&FEid=' + FEid + '&band=' + band + '&datatype=' + datatype + '&comp=' + compId,
                        // Called when saving new records
                        success: received,
                        jsonData: JSON.stringify(JSONObjectArray)
                    });
                }

                //Send the top level json records to the server.
                TDHStore.update();
            }
        }

            , {
            xtype: 'button',
            text: 'DONE',
            id: 'done',
            name: 'done',
            icon: '../icons/door_out.png',
            scope: this,
            handler: function () {
                window.location = link + TDHid;
            }
        }
        ]
    });
};

