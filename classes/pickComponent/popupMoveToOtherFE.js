/**
 * @fileoverview Pop-up window to select the configuration for a FE.
 */

/**
 * Displays a modal dialog box to select another front end to which test data should be moved.
 * @param fromDescription {string} description of where moving from. 
 * @param urlRoot {string} the root URL directory for the website
 * @param tdhIdArray {array of int} test data header IDs to be moved.
 * @returns null
 */
function popupMoveToOtherFE(fromDescription, urlRoot, tdhIdArray) {
    console.log(arguments[0]);
    console.log(arguments[1]);
    console.log(arguments[2]);
    console.log(arguments[3]);
    Ext.create('Ext.data.JsonStore', {
        storeId: 'cstore',
        fields: [{name : 'name'}, {name : 'id'}],
        autoLoad: true,
        proxy: {
            type: 'ajax',
            url: urlRoot + 'classes/pickComponent/pickComponent_Get.php?ctype=100&band=0',
            reader: {
                type: 'json',
                root: 'records',
                idProperty: 'id'   
            }
        },
    });
    var combo = {
        xtype: 'combo',
        id: 'combo',
        fieldLabel: 'to:',
        store: 'cstore',
        displayField: 'name',
        valueField: 'id',
        queryMode: 'local',
        typeAhead: true,
        minChars: 1,
        forceSelection: true,
        newCfg: 0,
        listeners: {
            'select' : function(combo, records, eOpts) {
                this.newCfg = records[0].data.id;
                console.log(records[0].data.name + ': ' + this.newCfg);
            }
        }
    };
    function onSubmit(btn) {
        var win = btn.up('window');
        var combo = win.down('combo');
        Ext.Ajax.request({
            url: urlRoot + 'classes/pickComponent/pickComponent_Submit.php',
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            jsonData: {
                "ctype": 100,
                "band": 0,
                "newCfg": combo.newCfg,
                "tdhIdArray": tdhIdArray
            },
            success: function(conn, response, options, eOpts) {
                var result = Ext.JSON.decode(conn.responseText);
                console.log(result);
                if (result.success) {
                    win.close();
                    document.location.reload(true)
                }
            },
            failure: function(conn, response, options, eOpts) {
                var result = Ext.JSON.decode(conn.responseText);
                console.log(result);
            }                
        });
    }
    var submit = {
        xtype: 'button',
        text: 'OK',
        width: 50,
        handler: function() {
            console.log('submit');
            onSubmit(this);
        }
    };
    var cancel = {
        xtype: 'button',
        text: 'Cancel',
        width: 50,
        handler: function() {
            console.log('cancel');
            var win = this.up('window');
            win.close();
        }
    };
    var bottomToolbar = {
        xtype: 'toolbar',
        ui: 'footer',
        dock: 'bottom',
        layout: {
            pack: 'end'
        },
        items: [
            submit,
            cancel
        ]
    };
    var panel = Ext.create('Ext.form.Panel', {
        layout: {
            type: 'vbox',
            align: 'stretch'
        },
        items : [
            combo            
        ],
        bbar: bottomToolbar
    });    
    window = Ext.create('Ext.window.Window', {
        width: 250,
        height: 90,
        constrain: true,
        resizable: true,
        modal: true,
        title: 'Move this test data from ' + fromDescription,
        border: false,
        items : [
            panel            
        ]
    }).show();
}