/*
 * This is called from ifspectrumplots.php.
 * 
 * Arguments:
 * 
 * fc- Facility code
 * id- Key value of record in TestData_header table
 * 
 */


function createIFSpectrumTabs(fc,id,fe,datasetgroup,band){
    
    ToolBar = new Ext.Toolbar({
         width:1000,
         height:35,

         
            renderTo: 'toolbar',
            items: [
            {
                xtype: 'tbbutton',
                text: 'Generate Plots And Data',
                icon:'../icons/application_view_gallery.png',
                handler: function() {
                //When this button is pressed, open ifspectrumplots.php. The "d=1" parameter tells it to draw plots.
                 window.location = '../ifspectrum/ifspectrumplots.php?d=1&g=' + datasetgroup + '&fc=' + fc + '&id=' + id + '&fe=' + fe + '&b=' + band;
             	}
            }
            ,{
                xtype: 'tbbutton',
                text: 'Edit Data Sets',
                icon:'../icons/cog.png',
                style: 'margin: 1px;',
                handler: function() {
                //When this button is pressed, open datasets.php for data type 7.
                    window.location = '../datasets/datasets.php?d=7&fc=' + fc + '&id=' + id + '&fe=' + fe + '&b=' + band;
             	}
            }
            ]
    });

       var tabs = new Ext.TabPanel({
            renderTo: 'tabs1',
            width:1000,
            activeTab: 0,
            frame:true,
            defaults:{autoHeight:true},
            bodyStyle:{"background-color":"#ff0000"},
            items:[
                    {contentEl: 'tab_info'     , title: 'Info', 
                        autoLoad:{url: 'getIFspectrumplotdata.php',params:{id:id,fe:fe,g:datasetgroup,fc:fc,tabtype:'1',b:band}}},
                    {contentEl: 'tab_spurious' , title:'Spurious Noise'},
                    {contentEl: 'tab_spurious2', title:'Spurious Noise (Expanded Plots)'},
                    {contentEl: 'tab_pwrvar2'  , title:'Power Variation (2 GHz)'},
                    {contentEl: 'tab_pwrvar31' , title:'Power Variation (31 MHz)'},            
                    {contentEl: 'pwrvarfullband',title:'Power Variation Full Band', 
                        autoLoad:{url:'getIFspectrumplotdata.php' ,params:{id:id,fe:fe,g:datasetgroup,b:band,fc:fc,tabtype:'pwrvarfullband'}}},
                    {contentEl: 'tab_totpwr'   , title:'Total and In-Band Power'  }
                   
                  ]
        });
       
       var subtabs = new Ext.TabPanel({
           //Tab for Spurious noise plots
           renderTo: 'tab_spurious',
           activeTab: 0,
           frame:true,
           bodyStyle:{"background-color":"#6C7070"},
          defaults:{autoHeight:true},
           
           items:[
                    //Tabs for Spurious noise plots for each IF channel
                    {title:'IF0', autoLoad:{url:'getIFspectrumplotdata.php' ,params:{id:id,fe:fe,fc:fc,b:band,g:datasetgroup,tabtype:'spurious_0'}}},
                    {title:'IF1', autoLoad:{url:'getIFspectrumplotdata.php' ,params:{id:id,fe:fe,fc:fc,b:band,g:datasetgroup,tabtype:'spurious_1'}}},
                    {title:'IF2', autoLoad:{url:'getIFspectrumplotdata.php' ,params:{id:id,fe:fe,fc:fc,b:band,g:datasetgroup,tabtype:'spurious_2'}}},
                    {title:'IF3', autoLoad:{url:'getIFspectrumplotdata.php' ,params:{id:id,fe:fe,fc:fc,b:band,g:datasetgroup,tabtype:'spurious_3'}}}
                  ]
           });
       
       var subtabs2 = new Ext.TabPanel({
           //Tab for Spurious noise expanded plots
           renderTo: 'tab_spurious2',
           activeTab: 0,
           frame:true,         
           bodyStyle:{"background-color":"#6C7070"},
           defaults:{autoHeight:true},
           
           items:[
                  
                      //Subtabs for Spurious noise expanded plots for each IF channel
                    {title:'IF0', autoLoad:{url:'getIFspectrumplotdata.php' ,params:{id:id,fe:fe,fc:fc,b:band,g:datasetgroup,tabtype:'spurious2_0'}}},
                    {title:'IF1', autoLoad:{url:'getIFspectrumplotdata.php' ,params:{id:id,fe:fe,fc:fc,b:band,g:datasetgroup,tabtype:'spurious2_1'}}},
                    {title:'IF2', autoLoad:{url:'getIFspectrumplotdata.php' ,params:{id:id,fe:fe,fc:fc,b:band,g:datasetgroup,tabtype:'spurious2_2'}}},
                    {title:'IF3', autoLoad:{url:'getIFspectrumplotdata.php' ,params:{id:id,fe:fe,fc:fc,b:band,g:datasetgroup,tabtype:'spurious2_3'}}}
                  ]
           });
       
       var subtabs3 = new Ext.TabPanel({
          //Tab for Power Variation 2 GHz
           renderTo: 'tab_pwrvar2',
           activeTab: 0,
           frame:true,
           bodyStyle:{"background-color":"#6C7070"},
           defaults:{autoHeight:true},
           
           items:[
                      //Subtabs for Power Variation 2 GHz plots
                    {title:'IF0', autoLoad:{url:'getIFspectrumplotdata.php' ,params:{id:id,fe:fe,fc:fc,b:band,g:datasetgroup,tabtype:'pwrvar2_0'}}},
                    {title:'IF1', autoLoad:{url:'getIFspectrumplotdata.php' ,params:{id:id,fe:fe,fc:fc,b:band,g:datasetgroup,tabtype:'pwrvar2_1'}}},
                    {title:'IF2', autoLoad:{url:'getIFspectrumplotdata.php' ,params:{id:id,fe:fe,fc:fc,b:band,g:datasetgroup,tabtype:'pwrvar2_2'}}},
                    {title:'IF3', autoLoad:{url:'getIFspectrumplotdata.php' ,params:{id:id,fe:fe,fc:fc,b:band,g:datasetgroup,tabtype:'pwrvar2_3'}}}
                  ]
           });
       var subtabs4 = new Ext.TabPanel({
           //Tab for Power Variation 31 MHz
           renderTo: 'tab_pwrvar31',
           activeTab: 0,
           frame:true,
           bodyStyle:{"background-color":"#6C7070"},
           defaults:{autoHeight:true},
           
           items:[
                    //Subtabs for Power Variation 31 MHz plots
                    {title:'IF0', autoLoad:{url:'getIFspectrumplotdata.php' ,params:{id:id,fe:fe,fc:fc,b:band,g:datasetgroup,tabtype:'pwrvar31_0'}}},
                    {title:'IF1', autoLoad:{url:'getIFspectrumplotdata.php' ,params:{id:id,fe:fe,fc:fc,b:band,g:datasetgroup,tabtype:'pwrvar31_1'}}},
                    {title:'IF2', autoLoad:{url:'getIFspectrumplotdata.php' ,params:{id:id,fe:fe,fc:fc,b:band,g:datasetgroup,tabtype:'pwrvar31_2'}}},
                    {title:'IF3', autoLoad:{url:'getIFspectrumplotdata.php' ,params:{id:id,fe:fe,fc:fc,b:band,g:datasetgroup,tabtype:'pwrvar31_3'}}}
                  ]
           });
       
       var subtabs4 = new Ext.TabPanel({
           //Tab for Total and In-Band Power
           renderTo: 'tab_totpwr',
           activeTab: 0,
           frame:true,
           bodyStyle:{"background-color":"#6C7070"},
           defaults:{autoHeight:true},
           
           items:[
                      //Subtabs for Total Power tables
                    {title:'IF0', autoLoad:{url:'getIFspectrumplotdata.php' ,params:{id:id,fe:fe,fc:fc,b:band,g:datasetgroup,tabtype:'totpwr_0'}}},
                    {title:'IF1', autoLoad:{url:'getIFspectrumplotdata.php' ,params:{id:id,fe:fe,fc:fc,b:band,g:datasetgroup,tabtype:'totpwr_1'}}},
                    {title:'IF2', autoLoad:{url:'getIFspectrumplotdata.php' ,params:{id:id,fe:fe,fc:fc,b:band,g:datasetgroup,tabtype:'totpwr_2'}}},
                    {title:'IF3', autoLoad:{url:'getIFspectrumplotdata.php' ,params:{id:id,fe:fe,fc:fc,b:band,g:datasetgroup,tabtype:'totpwr_3'}}}
                  ]
           });
       
}