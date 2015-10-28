function createBPTabs(fc,id,band,bpstatus){
    var buttontext = '';
    var pointingOption = 'nominal';
    
    //The "Generate Plots" button will have different text based on the current status.
    if (bpstatus == 1){
        //All three scans are complete, ready to process.
        buttontext = 'Generate Plots And Data (Ready to Process)';
    }
    if (bpstatus == 2){
        //All three scans are complete, and have already been processed.
        buttontext = 'Generate Plots And Data';
    }
    if (bpstatus == 3){
        //One or more scans still need to finish.
        buttontext = 'Generate Plots And Data (Not Ready to Process)';
    }
    
    //This toolbar contains the button for generating plots and efficiency data.
    ToolBar = new Ext.Toolbar({
    	width:1000,
    	height:35,
        renderTo: 'toolbar',
        items: [
            {	xtype: 'tbbutton',
            	text: buttontext,
            	icon:'../icons/application_view_gallery.png',
            	handler: function() {
            		//When the button is pressed, reload the page with drawplot=1.
            		window.location = 'bp.php?drawplot=1&keyheader=' + id + '&fc=' + fc + '&band=' + band + '&pointing=' + pointingOption;
            	}
            },
            '   ',
            {
                text: 'Use pointing...',
        	    menu: {
            		plain:  true,              // display no icons
            		items: [
            		    {	
            		    	text: 'Nominal subreflector direction (default)',
            		    	checked: true,
            		    	group: 'pointing',
                            handler: onItemClick,
            		    }, 
            		    {
                            text: 'Actual beam direction',
                            checked: false,
            		    	group: 'pointing',
                            handler: onItemClick
            		    },
            		    {
                            text: 'ACA 7 meter nominal',
                            checked: false,
            		    	group: 'pointing',
                            handler: onItemClick
            		    }
            		]

            	}
            }
        ]
    });
    
	function onItemClick(item) {
    	if (item.text.search('Nominal') >= 0) {
    		pointingOption = 'nominal';
    	}
    	if (item.text.search('Actual') >= 0) {
    		pointingOption = 'actual';
    	}
     	if (item.text.search('7 meter') >= 0) {
     		pointingOption = '7meter';
    	}
//    	Ext.Msg.alert('Status', 'Selected pointing \'' + pointingOption + '\'.');
    }
    
    //This is the tabbed structure 
    var tabs = new Ext.TabPanel({
    	renderTo: 'tabs1',
    	width:1000,
	    height:750,
	    activeTab: 0,
	    frame:true,
	    autoShow: true,
	    defaults:{ autoScroll:true},
	    bodyStyle:{"background-color":"#6C7070"},
	    items: [
		    {contentEl: 'parent1', title: 'Scan Info',autoLoad:{url: 'getBPdata.php',params:{id:id,fc:fc,tabtype:'1'}}},
		    {title:'Data Tables',          autoLoad:{url:'getBPdata.php' ,params:{id:id,fc:fc,tabtype:'2'}}},
		    {title:'Pointing Angles',      autoLoad:{url:'getBPdata.php' ,params:{id:id,fc:fc,tabtype:'3'}}},
		    {title:'Pol 0 Nearfield Plots',autoLoad:{url:'getBPdata.php' ,params:{id:id,fc:fc,tabtype:'4'}}},
		    {title:'Pol 0 Farfield Plots', autoLoad:{url:'getBPdata.php' ,params:{id:id,fc:fc,tabtype:'5'}}},
		    {title:'Pol 1 Nearfield Plots',autoLoad:{url:'getBPdata.php' ,params:{id:id,fc:fc,tabtype:'6'}}},
		    {title:'Pol 1 Farfield Plots', autoLoad:{url:'getBPdata.php' ,params:{id:id,fc:fc,tabtype:'7'}}}
		]
   });
}
