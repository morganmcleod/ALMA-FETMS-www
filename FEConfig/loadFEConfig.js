function PAIcheckBox(id, checked) {
	var txt = new String("");
	if (checked)
		txt = "checked";
	
	var received = function (response) {
    };

	var failed = function (response) {
		alert("Error:  The database was not updated due to a network error.  id=" + id + " " + txt);
    };
	
	Ext.Ajax.request({
        //Send the JSON object
        url: 'updateTestDataUseForPAI.php?action=checkbox&key=' + id + '&checked=' + checked,                   
        success: received,
        failure: failed
    });
}

function createtabs(keyfe, fesn, fc, fe_id){
    
    //called from ShowFEConfig.php
    var tabs = new Ext.TabPanel({
        renderTo: 'tabs1',
        width:1000,
        activeTab: 0,
        frame:true,
        height:500,
        defaults:{autoScroll:true},
        items: [
                { 
                	title: 'Frontend Configuration',
                	autoLoad: {url: 'getFrontEndData.php', params:{band :'100',key: keyfe, fc: fc} } }
        ]
    });

    new Ext.Toolbar({
        width:1000,
        height:35,
    
        renderTo: 'toolbar',
        items: [
                {    
                    xtype: 'tbbutton',
                    text: 'Add or Remove Components',
                    icon: 'icons/cog.png',
                    style: 'margin: 1px;',
                    handler: function() {
                        window.location = 'UpdateFE.php?sn=' + fesn + '&fc=' + fc;
                    }
                },{
                    xtype: 'tbbutton',
                    text: 'Add Document',
                    icon: 'icons/add.png',
                    style: 'margin: 1px;',
                    handler: function() {
                        window.location = 'AddDocument.php?conf=' + keyfe + '&fc=' + fc;
                    }
                },{
                    xtype: 'tbbutton',
                    text: 'Edit Front End',
                    icon: 'icons/application_edit.png',
                    style: 'margin: 1px;',
                    handler: function() {
                        window.location = 'EditFrontEnd.php?id=' + keyfe + '&fc=' + fc;
                    }
                },{
                    xtype: 'tbbutton',
                    text: 'PAS Data Delivery',
                    icon: 'icons/application_go.png',
                    style: 'margin: 1px;',
                    handler: function() {
                        getWarmPASConfig(fesn);
                    }
                },{
                    xtype: 'tbbutton',
                    text: 'CIDL Report',
                    icon: 'icons/report.png',
                    style: 'margin: 1px;',
                    handler: function() {
                        window.location = 'cidl/cidlRevEdit.php?feconfig=' + keyfe + '&fc=' + fc;
                    }
                },{
                    xtype: 'tbbutton',
                    text: 'Cartridge Config',
                    icon:'icons/application_view_list.png',
                    style: 'margin: 1px;',
                    handler: function() {
                        window.location = 'export_to_ini.php?all=1&keyId=' + keyfe + '&fc=' + fc;
                    }
                } 
        ]
    });
    
    var more=tabs.add({
        title:'Documents',
        autoLoad: {url: 'getFrontEndData.php',params:{band :'docs',key: keyfe}}
    });
      
    //for components with band 0 or null
      var more=tabs.add({
        title:'Components',
        autoLoad: {url: 'getFrontEndData.php',params:{band :'other',key: keyfe}}
    });
      
      var more=tabs.add({
        title:'Band 1',
        autoLoad: {url: 'getFrontEndData.php',params:{band :'1',key: keyfe}}
    });
    var more=tabs.add({
        title:'Band 2',
        autoLoad: {url: 'getFrontEndData.php',params:{band :'2',key: keyfe}}
    });
    var more=tabs.add({
        title:'Band 3',
        autoLoad: {url: 'getFrontEndData.php',params:{band :'3',key: keyfe}}
    });
    var more=tabs.add({
        title:'Band 4',
        autoLoad: {url: 'getFrontEndData.php',params:{band :'4',key: keyfe}}
    });
    var more=tabs.add({
        title:'Band 5',
        autoLoad: {url: 'getFrontEndData.php',params:{band :'5',key: keyfe}}
    });
    var more=tabs.add({
        title:'Band 6',
        autoLoad: {url: 'getFrontEndData.php',params:{band :'6',key: keyfe}}
    });
    var more=tabs.add({
        title:'Band 7',
        autoLoad: {url: 'getFrontEndData.php',params:{band :'7',key: keyfe}}
    });
    var more=tabs.add({
        title:'Band 8',
        autoLoad: {url: 'getFrontEndData.php',params:{band :'8',key: keyfe}}
    });
    var more=tabs.add({
        title:'Band 9',
        autoLoad: {url: 'getFrontEndData.php',params:{band :'9',key: keyfe}}
    });
    var more=tabs.add({
        title:'Band 10',
        autoLoad: {url: 'getFrontEndData.php',params:{band :'10',key: keyfe}}
    });
}
