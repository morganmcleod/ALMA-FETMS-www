/**
 * @fileoverview a widget to present a drop-down list of FrontEnds or FEComponents so that the user can pick one.
 */

/**
 * Types of components which can be displayed by the pickComponent list below
 */
const ComponentTypes = {
  FE: '100',
  CCA: '20',
  WCA: '11'
};

/**
 * Displays a combobox to select either a FE configuration by FE SN or a component configuration by CCA/WCA SN. 
 * @param cType {ComponentTypes} which kind of component to display 
 * @param renderTo {string} the name of the div where the combobox should render
 * @param fieldLabel {string} to display to the left of the combobox
 * @param band {integer} in 0-10. Must be in 1-10 for cType = CCA or WCA 
 * @param selectCallback {function} to call with the selected name and id
 * @returns
 */ 
function pickComponent(cType, renderTo, fieldLabel, onSelectCallback = false, band = 0) {
    Ext.create('Ext.data.JsonStore', {
        storeId: 'cstore',
        fields: [{name : 'name'}, {name : 'id'}],
        autoLoad: true,
        proxy: {
            type: 'ajax',
            url: 'pickComponent_Get.php?ctype=' + cType + '&band=' + band,
            reader: {
                type: 'json',
                root: 'records',
                idProperty: 'id'   
            }
        },
    });
    
    Ext.create('Ext.form.field.ComboBox', {
        fieldLabel: fieldLabel,
        store: 'cstore',
        displayField: 'name',
        valueField: 'id',
        queryMode: 'local',
        typeAhead: true,
        minChars: 1,
        forceSelection: true,
        renderTo: renderTo,
        listeners: {
            'select' : function(combo, records, eOpts) {
                if (onSelectCallback)
                    onSelectCallback(records[0].data);
            }
        }
    });
};


