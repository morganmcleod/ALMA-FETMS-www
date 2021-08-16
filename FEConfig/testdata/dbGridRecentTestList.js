// Original example from http://myxaab.wordpress.com/2011/07/09/display-json-data-using-extjs4-store/
//This uses ExtJS 4

function creategrid(type, selectortype) {

    Ext.QuickTips.init();
    Ext.define('UsgsList', {
        extend: 'Ext.data.Model',
        fields: [
            { name: 'SN', type: 'string' },
            { name: 'Band', type: 'string' },
            { name: 'keyId', type: 'string' },
            { name: 'TS', type: 'string' },
            { name: 'TestType', type: 'string' },
            { name: 'fkTestData_Type', type: 'string' },
            { name: 'keyFacility', type: 'string' },
            { name: 'keyFrontEnd', type: 'string' },
            { name: 'DataSetGroup', type: 'string' },
            { name: 'Notes', type: 'string' }
        ],
        idProperty: 'fid'
    });

    var store = Ext.create('Ext.data.Store', {
        id: 'store',
        model: 'UsgsList',
        proxy: {
            type: 'ajax',
            url: "GetRecentTestList.php?type=" + type + "&stype=" + selectortype,
            reader: {
                type: 'json',
                root: 'data'
            }
        }
    });

    var grid = Ext.create('Ext.grid.Panel', {
        deferredRender: false,
        width: 1150,
        height: 650,
        title: 'Recent Tests',
        store: store,
        loadMask: true,
        columns: [
            { header: 'FE SN', align: 'center', width: 60, sortable: true, dataIndex: 'SN' },
            { header: 'Band', align: 'center', width: 60, sortable: true, dataIndex: 'Band' },
            { header: 'Test Type', width: 320, sortable: true, dataIndex: 'TestType' },
            { header: 'Date', width: 140, sortable: true, dataIndex: 'TS' },
            { header: 'Notes', align: 'left', width: 620, sortable: true, dataIndex: 'Notes', renderer: addTooltip }
        ],
        listeners: {
            itemclick: function (grid, record, item, index, e) {
                var record = grid.getStore().getAt(index);
                var keyval = record.get('keyId');
                var fc = record.get('keyFacility');
                var fe = record.get('keyFrontEnd');
                var band = record.get('Band');
                var testtype = record.get('fkTestData_Type');
                if (testtype == '55') {
                    location.href = "../bp/bp.php?keyheader=" + keyval + "&fc=" + fc;
                }
                else if (testtype == '7') {
                    location.href = "../ifspectrum/ifspectrumplots.php?fc=" + fc + "&fe=" + fe + "&b=" + band + "&id=" + keyval;
                }
                else {
                    location.href = "testdata.php?keyheader=" + keyval + "&fc=" + fc;
                }
            }
        }
    });

    // trigger the data store load
    store.load();
    var container = document.getElementById('db-grid');
    container.innerHTML = "";
    grid.render('db-grid');
};

function addTooltip(value, metadata) {
    metadata.tdAttr = 'data-qtip="' + value.replace(/\r?\n/g, '<br>') + '"';
    return value;
}

function clear() {
    store.removeAll();
}
