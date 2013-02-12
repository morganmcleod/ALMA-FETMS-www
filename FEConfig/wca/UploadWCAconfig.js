
//Follows example from http://stackoverflow.com/questions/2514937/file-upload-using-ext-js

//Ext.require([
//    'Ext.form.field.File',
//    'Ext.form.Panel',
//    'Ext.window.MessageBox'
//]);


function WCAFileBrowse(id,fc)
{
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
            emptyText: 'Select FrontEndControlDLL.INI file...',
            fieldLabel: 'File',
            buttonText: 'Browse',
            height: '30px'	
        }]
        
    });
	
	
	dlgPopup = new Ext.Window({
		renderto:'fi-form',
		modal:true,
		layout:'fit',
		width:550,
		height:180,
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
                        url: 'wca/UpdateFromIniFile.php?id=' + id + '&fc=' + fc,
                        waitMsg: 'Uploading file...',
	                    success: function(form,action){
	                    	var msg = action.result.errors;
                    		var keyconfig = action.result.keyconfig;
                    		
	                    	if (action.result.errordetected == 1){
	                    		alert(msg);
	                    	}
	                    	if (action.result.errordetected == 0){
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
        }
		,
        {
			text:'Close',
			handler:function() 
			{
				dlgPopup.close();
				
			}
		}
        ]
		

	
	});
	
	
	dlgPopup.show();

	
	

}