function EditFE(keyFE, cansn, doc, notes) {
    var win, dlgPopup, nav;

    nav = new Ext.FormPanel({
        labelWidth: 75,
        frame: true,
        width: 300,
        collapsible: false,
        title: 'Update CAN SN and URL',
        items: [
            //'Enter data',
            new Ext.form.TextField({
                id: 'cansn',
                name: 'cansn',
                fieldLabel: 'CAN SN',
                allowBlank: true,
                height: 30,
                width: 200,
                value: cansn
            }),
            new Ext.form.TextField({
                id: 'url',
                name: 'url',
                fieldLabel: 'Docs',
                allowBlank: true,
                height: 40,
                width: 200,
                value: doc
            }),
            new Ext.form.TextArea({
                id: 'notes',
                name: 'notes',
                fieldLabel: 'Description',
                allowBlank: true,
                height: 70,
                width: 200,
                value: notes
            })

        ]
    });

    dlgPopup = new Ext.Window({
        renderto: 'win_req_in',
        modal: true,
        layout: 'fit',
        width: 400,
        height: 250,
        closable: true,
        resizable: false,
        plain: true,
        items: [nav],
        buttons: [{
            text: 'Submit',
            handler: function () {
                var form = nav.getForm();
                var cansn = form.getValues()['cansn'];
                var url = form.getValues()['url'];
                var note = form.getValues()['notes'];
                form.submit
                    ({
                        method: 'post',
                        url: 'UpdateFEData.php',
                        params: { canser: cansn, url: url, notes: note, key: keyFE }
                    });
                dlgPopup.close();
                setTimeout("location.reload(true)", 1250);
            }
        }, {
            text: 'Cancel',
            handler: function () {
                dlgPopup.close();
            }
        }]
    });

    dlgPopup.show();

}