/**
 * @fileoverview loads the IF spectrum toolbars and tabs.
 */

/**
 * This is called from ifspectrumplots.php.
 * @param fc Facility code
 * @param tdhId Key value of record in TestData_header table
 * @param fe FrontEnds.keyId
 * @param datasetgroup 
 * @param band
 * @param popupCallback function to call for 'move to other fe' button.
 * @returns
 */
function createIFSpectrumTabs(fc, tdhId, fe, datasetgroup, band, popupCallback = false) {    
    Ext.create('Ext.toolbar.Toolbar', {
        renderTo: 'toolbar',
        width: 1000,
        height: 30,
        items: [
            {
                xtype: 'button', // default for Toolbars
                text: '<span style="font-weight:bold;">Generate Plots</span>',
                icon: '../icons/application_view_gallery.png',
                handler: function() {
                    //Open ifspectrumplots.php. The "d=1" parameter tells it to draw plots:
                    window.location = 'ifspectrumplots.php?d=1&g=' + datasetgroup + '&fc=' + fc + '&id=' + tdhId + '&fe=' + fe + '&b=' + band;
             	}
            },{
                text: '<span style="font-weight:bold;">Edit Data Sets</span>',
                icon: '../icons/cog.png',
                handler: function() {
                    //When this button is pressed, open datasets.php for data type 7.
                    window.location = '../datasets/datasets.php?d=7&fc=' + fc + '&id=' + tdhId + '&fe=' + fe + '&b=' + band;
             	}
            },{
                text: '<span style="font-weight:bold;">Move to Other FE</span>',
                icon: '../icons/arrow_switch.png',
                handler: function() {
                    if (popupCallback)
                        popupCallback();
                }
            }
        ]
    });
    Ext.define('IFSpectrum.tab.Panel', {
        extend: 'Ext.tab.Panel',
        constructor: function (config) {
            this.callParent(arguments);
        },
        activeTab: 0,
        frame: true,
        layout: 'auto',
        bodyStyle: { "background-color": "#6C7070" },
        defaults: { 
            layout: 'auto',
            listeners: {
                render: {
                    fn: function() {
                        this.loader.load();
                    }
                }
            }
        }
    });
    // create tabs for Spurious noise plots:
    Ext.create('IFSpectrum.tab.Panel', {
        id: 'subtabs_spurious',
        items: [
            {
                title: 'IF0',
                loader: {
                    autoLoad: false, 
                    url: 'getIFspectrumplotdata.php',
                    params: { id:tdhId, fe:fe, fc:fc, b:band, g:datasetgroup, tabtype:'spurious_0' }                    
                }
            },{
                title: 'IF1', 
                loader: {
                    autoLoad: false, 
                    url: 'getIFspectrumplotdata.php',
                    params: { id:tdhId, fe:fe, fc:fc, b:band, g:datasetgroup, tabtype:'spurious_1' }
                }
            },{
                title: 'IF2', 
                loader: {
                    autoLoad: false, 
                    url: 'getIFspectrumplotdata.php',
                    params: { id:tdhId, fe:fe, fc:fc, b:band, g:datasetgroup, tabtype:'spurious_2' }
                }
            },{
                title: 'IF3', 
                loader: {
                    autoLoad: false, 
                    url: 'getIFspectrumplotdata.php',
                    params: { id:tdhId, fe:fe, fc:fc, b:band, g:datasetgroup, tabtype:'spurious_3' }
                }
            }
        ]   
    });
    // create tabs for Spurious noise expanded plots:
    Ext.create('IFSpectrum.tab.Panel', {
        id: 'subtabs_spurious2',
        items: [
            {
                title:'IF0', 
                loader: {
                    autoLoad: false, 
                    url: 'getIFspectrumplotdata.php',
                    params: { id:tdhId, fe:fe, fc:fc, b:band, g:datasetgroup, tabtype:'spurious2_0' }
                }
            },{
                title:'IF1', 
                loader: {
                    autoLoad: false, 
                    url: 'getIFspectrumplotdata.php',
                    params:  {id:tdhId, fe:fe, fc:fc, b:band, g:datasetgroup, tabtype:'spurious2_1' }
                }
            },{
                title:'IF2', 
                loader: {
                    autoLoad: false, 
                    url: 'getIFspectrumplotdata.php',
                    params: { id:tdhId, fe:fe, fc:fc, b:band, g:datasetgroup, tabtype:'spurious2_2' }
                }
            },{
                title:'IF3', 
                loader: {
                    autoLoad: false, 
                    url: 'getIFspectrumplotdata.php',
                    params: { id:tdhId, fe:fe, fc:fc, b:band, g:datasetgroup, tabtype:'spurious2_3' }
                }
            }
        ]
    });
    // create tabs for Power Variation 2 GHz:
    Ext.create('IFSpectrum.tab.Panel', {
        id: 'subtabs_pwrvar2',
        items: [
            {
                title: 'IF0', 
                loader: {
                    autoLoad: false, 
                    url: 'getIFspectrumplotdata.php',
                    params: { id:tdhId, fe:fe, fc:fc, b:band, g:datasetgroup, tabtype:'pwrvar2_0' }
                }
            },{
                title: 'IF1', 
                loader: {
                    autoLoad: false, 
                    url: 'getIFspectrumplotdata.php',
                    params: { id:tdhId, fe:fe, fc:fc, b:band, g:datasetgroup, tabtype:'pwrvar2_1' }
                }                
            },{
                title: 'IF2', 
                loader: {
                    autoLoad: false, 
                    url: 'getIFspectrumplotdata.php',
                    params: { id:tdhId, fe:fe, fc:fc, b:band, g:datasetgroup, tabtype:'pwrvar2_2' }
                }                
            },{
                title: 'IF3', 
                loader: {
                    autoLoad: false, 
                    url: 'getIFspectrumplotdata.php',
                    params: { id:tdhId, fe:fe, fc:fc, b:band, g:datasetgroup, tabtype:'pwrvar2_3' }
                }                
            }
        ]
    });
    // create tabs for Power Variation 31 MHz:
    Ext.create('IFSpectrum.tab.Panel', {
        id: 'subtabs_pwrvar31',
        items: [
            {
                title: 'IF0', 
                loader: {
                    autoLoad: false, 
                    url: 'getIFspectrumplotdata.php',
                    params: { id:tdhId, fe:fe, fc:fc, b:band, g:datasetgroup, tabtype:'pwrvar31_0' }
                }
            },{
                title: 'IF1', 
                loader: {
                    autoLoad: false, 
                    url: 'getIFspectrumplotdata.php',
                    params: { id:tdhId, fe:fe, fc:fc, b:band, g:datasetgroup, tabtype:'pwrvar31_1' }
                }                
            },{
                title: 'IF2', 
                loader: {
                    autoLoad: false, 
                    url: 'getIFspectrumplotdata.php',
                    params: { id:tdhId, fe:fe, fc:fc, b:band, g:datasetgroup, tabtype:'pwrvar31_2' }
                }                
            },{
                title: 'IF3', 
                loader: {
                    autoLoad: false, 
                    url: 'getIFspectrumplotdata.php',
                    params: { id:tdhId, fe:fe, fc:fc, b:band, g:datasetgroup, tabtype:'pwrvar31_3' }
                }                
            }
        ]
    });
    // create tabs for Total and In-Band Power:
    Ext.create('IFSpectrum.tab.Panel', {
        id: 'subtabs_totpwr',
        items: [
            {
                title: 'IF0', 
                loader: {
                    autoLoad: false, 
                    url: 'getIFspectrumplotdata.php',
                    params: { id:tdhId, fe:fe, fc:fc, b:band, g:datasetgroup, tabtype:'totpwr_0' }
                }
            },{
                title: 'IF1', 
                loader: {
                    autoLoad: false, 
                    url: 'getIFspectrumplotdata.php',
                    params: { id:tdhId, fe:fe, fc:fc, b:band, g:datasetgroup, tabtype:'totpwr_1' }
                }                
            },{
                title: 'IF2', 
                loader: {
                    autoLoad: false, 
                    url: 'getIFspectrumplotdata.php',
                    params: { id:tdhId, fe:fe, fc:fc, b:band, g:datasetgroup, tabtype:'totpwr_2' }
                }                
            },{
                title: 'IF3', 
                loader: {
                    autoLoad: false, 
                    url: 'getIFspectrumplotdata.php',
                    params: { id:tdhId, fe:fe, fc:fc, b:band, g:datasetgroup, tabtype:'totpwr_3' }
                }                
            }
        ]
    });
    // Create main tabs panel:
    Ext.create('Ext.tab.Panel', {
        renderTo: 'tabs1',
        width: 1000,
        layout: 'auto',
        activeTab: 0,
        frame: true,
        bodyStyle: { "background-color": "#ff0000" },
        items: [
            {
                title: 'Info', 
                loader: {
                    autoLoad: true, 
                    url: 'getIFspectrumplotdata.php',
                    params: {id:tdhId, fe:fe, g:datasetgroup, fc:fc, tabtype:'1', b:band}
                }
            },{
                title: 'Spurious Noise',
                items: [
                    'subtabs_spurious'
                ]
            },{
                title: 'Spurious Noise (Expanded Plots)',
                items: [
                    'subtabs_spurious2'
                ]
            },{
                title: 'Power Variation (2 GHz)',
                items: [
                    'subtabs_pwrvar2'
                ]
            },{
                title: 'Power Variation (31 MHz)',
                items: [
                    'subtabs_pwrvar31'
                ]
            },{
                title: 'Power Variation Full Band', 
                loader: {
                    autoLoad: true, 
                    url: 'getIFspectrumplotdata.php',
                    params: { id:tdhId, fe:fe, g:datasetgroup, b:band, fc:fc, tabtype:'pwrvarfullband' }
                }
            },{
                title: 'Total and In-Band Power',
                items: [
                    'subtabs_totpwr'
                ]
            }
        ]
    });
}
