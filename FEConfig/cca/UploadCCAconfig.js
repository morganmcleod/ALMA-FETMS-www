
//Follows example from http://stackoverflow.com/questions/2514937/file-upload-using-ext-js

function CCAFileBrowse(id,fc)
{
	var fileinstructions = '<b><u>Types of files that may be uploaded:</b></u><br><br>';
	fileinstructions += '1. ZIP- Zipped package of configuration and test data.<br>';
	fileinstructions += '2. CSV or TXT- Single file of test data.<br>';
	fileinstructions += '3. FrontEndControlDLL.ini file. <br>';
	fileinstructions += '4. ALMA CCA configuration XML file. <br><br>';
	
	myuploadform= new Ext.FormPanel({
		title:'Select file for upload',
        fileUpload: true,
        width: 500,
        autoHeight: true,
        bodyStyle: 'padding: 10px 10px 10px 10px;',
        labelWidth: 50,
        defaults: {
            anchor: '95%',
            allowBlank: false,
            msgTarget: 'side'
        },
        items:[
        {
            xtype: 'fileuploadfield',
            id: 'filedata',
            emptyText: 'Select a file...',
            fieldLabel: 'File',
            bodyStyle: 'padding: 0px;',
            buttonText: 'Browse',
            height: '30px'
        },
        {
            id:'tab1',
            html :fileinstructions,
            height: '90px'
        }]
    });	

	dlgPopup = new Ext.Window({
		renderto:'fi-form',
		modal:true,
		layout:'fit',
		width:550,
		height:250,
		closable:true,
		resizable:false,
		plain:true,
		items:[myuploadform],

		buttons: [{
            text: 'Upload',
            handler: function(){
				
                if(myuploadform.getForm().isValid()){
                    form_action=1;
                    myuploadform.getForm().submit({
                        url: 'cca/UpdateFromIniFile.php?id=' + id + '&fc=' + fc,
                        waitMsg: 'Uploading file...',
	                    success: function(form,action){
	                    	var msg = action.result.errors;
                    		var keyconfig = action.result.keyconfig;

	                    	if (action.result.errordetected == 1) {
	                    		alert(msg);
//	                    		dlgPopup.close();
//	                    		location.href='ShowComponents.php?conf=' + keyconfig + '&fc=' + fc;
	                    	} else {
	                    		dlgPopup.close();
	                    		location.href='ShowComponents.php?conf=' + keyconfig + '&fc=' + fc;
	                    	}
	                    },
	                    failure: function(form,action){
	                        alert('File upload failed.');
	                    }
                    });
                }
            }
        },
        {
			text:'Cancel',
			handler:function() 
			{
				dlgPopup.close();
			}
		}
        ]
	});
	dlgPopup.show();
}
