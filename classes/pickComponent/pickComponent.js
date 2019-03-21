// pickComponent.js
//
// a widget to present a drop-down list of FrontEnds or FEComponents so that the user can pick one


var tpl = Ext.create('Ext.Template', ['Hello {firstName} {lastName}!',
        ' Nice to meet you!']);
var formPanel = Ext.create('Ext.form.FormPanel', {
    itemId: 'formPanel',
    frame: true,
    layout: 'anchor',
    defaultType: 'textfield',
    defaults: {
        anchor: '-10',
        labelWidth: 65
    },
    items: [{
        fieldLabel: 'First name',
        name: 'firstName'
    }, {
        fieldLabel: 'Last name',
        name: 'lastName'
    }],
    buttons: [{
        text: 'Submit',
        handler: function() {
            var formPanel = this.up('#formPanel'), vals = formPanel
                    .getValues(), greeting = tpl.apply(vals);
            Ext.Msg.alert('Hello!', greeting);
        }
    }]
});

Ext.onReady(function() {
    Ext.create('Ext.data.JsonStore', {
        storeId: 'cstore',
        fields: [{name : 'name'}, {name : 'id'}],
        autoLoad: false,
        proxy: {
            type: 'ajax',
            url: 'pickComponent_Get.php',
            reader: {
                type: 'json',
                root: 'records',
                idProperty: 'id'   
            }
        },
    });
    
    Ext.create('Ext.form.field.ComboBox', {
        fieldLabel: 'Select Component',
        store: 'cstore',
        queryMode: 'remote',
        displayField: 'name',
        valueField: 'id',
        renderTo: 'toolbar',
        width : 400,
        height : 20    
    });

//    Ext.create('Ext.window.Window', {
//        height: 125,
//        width: 200,
//        closable: false,
//        title: 'Input needed.',
//        border: false,
//        layout: 'fit',
//        items: formPanel
//    }).show();
});


