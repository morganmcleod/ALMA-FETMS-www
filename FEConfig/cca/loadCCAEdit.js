function createCompTabs(band, comptype, compKey) {
    var tabs = new Ext.TabPanel({
        renderTo: 'tabs1',
        width: 1050,
        height: 630,
        activeTab: 0,
        frame: true,
        //height:400,
        defaults: { autoScroll: true },
        items: [
            {
                contentEl: 'parent1', title: 'CCA Configuration',
                autoLoad: { url: '../getComponentData.php', params: { band: band, type: comptype, config: compKey, tabtype: '1' } }
            },
            {
                title: 'Edit Mixer Params',
                autoLoad: { url: 'getCCAEditData.php', params: { band: band, type: comptype, config: compKey, tabtype: '2' } }
            },
            {
                title: 'Edit Preamp Params',
                autoLoad: { url: 'getCCAEditData.php', params: { band: band, type: comptype, config: compKey, tabtype: '3' } }
            },
            {
                title: 'Edit Temperature Sensors',
                autoLoad: { url: 'getCCAEditData.php', params: { band: band, type: comptype, config: compKey, tabtype: '4' } }
            }
        ]
    });
}