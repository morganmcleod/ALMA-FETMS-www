function createCompTabs(band, comptype, compKey, fc, CompDescription) {

    if ((comptype != 20) && (comptype != 11)) {

        new Ext.Toolbar({
            width: 1050,
            height: 35,

            renderTo: 'toolbar',
            items: [{
                xtype: 'tbbutton',
                text: 'Edit ' + CompDescription,

                icon: 'icons/application_edit.png',
                handler: function () {
                    window.location = 'EditComponent.php?id=' + compKey
                        + '&fc=' + fc;
                }
            }]
        });
    }
    if ((comptype != 20) && (comptype != 217) && (comptype != 218)
        && (comptype != 219) && (comptype != 220) && (comptype != 222)) {
        var tabs = new Ext.TabPanel({
            renderTo: 'tabs1',
            width: 1050,
            activeTab: 0,
            frame: true,
            defaults: {
                autoHeight: true
            },

            items: [{
                contentEl: 'parent1',
                title: 'Configuration',
                autoLoad: {
                    url: 'getComponentData.php',
                    params: {
                        fc: fc,
                        band: band,
                        type: comptype,
                        config: compKey,
                        tabtype: '1'
                    }
                }
            }, {
                contentEl: 'parent1',
                title: 'Test Data',
                autoLoad: {
                    url: 'getComponentData.php',
                    params: {
                        fc: fc,
                        band: band,
                        type: comptype,
                        config: compKey,
                        tabtype: 'testdata'
                    }
                }
            }]
        });

    }
    if (comptype == 20) {
        /* CCA */
        var tabs = new Ext.TabPanel({
            renderTo: 'tabs1',
            width: 1050,
            activeTab: 0,
            frame: true,
            defaults: {
                autoHeight: true
            },

            items: [{
                contentEl: 'parent1',
                title: 'Configuration',
                autoLoad: {
                    url: 'getComponentData.php',
                    params: {
                        fc: fc,
                        band: band,
                        type: comptype,
                        config: compKey,
                        tabtype: '1'
                    }
                }
            }, {
                contentEl: 'parent1',
                title: 'Test Data',
                autoLoad: {
                    url: 'getComponentData.php',
                    params: {
                        fc: fc,
                        band: band,
                        type: comptype,
                        config: compKey,
                        tabtype: 'testdata'
                    }
                }
            }]
        });

        new Ext.Toolbar(
            {
                width: 1050,
                height: 35,

                renderTo: 'toolbar',
                items: [{
                        xtype: 'tbbutton',
                        text: 'Edit ' + CompDescription,
                        style: 'margin: 1px;',
                        icon: 'icons/application_edit.png',
                        handler: function () {
                            window.location = 'EditComponent.php?id='
                                + compKey + '&fc=' + fc;
                        }
                    }, {
                        xtype: 'tbbutton',
                        text: CompDescription + '.INI for FEMC',
                        style: 'margin: 1px;',
                        icon: 'icons/control_equalizer_blue.png',
                        handler: function () {
                            window.location = 'cca/export_cca_tempsensors.php?keyId='
                                + compKey + '&fc=' + fc;
                        }
                    }, {
                        xtype: 'tbbutton',
                        text: 'Edit Op Params',
                        style: 'margin: 1px;',
                        icon: 'icons/cog.png',
                        handler: function () {
                            window.location = 'cca/EditCCAConfig.php?conf='
                                + compKey + '&fc=' + fc;
                        }
                    }, {
                        xtype: 'tbbutton',
                        text: 'Get Config INI',
                        style: 'margin: 1px;',
                        icon: 'icons/script.png',
                        handler: function () {
                            window.location = 'cca/export_to_ini_cca.php?keyId='
                                + compKey + '&fc=' + fc;
                        }
                    }, {
                        xtype: 'tbbutton',
                        text: 'Get Config XML',
                        style: 'margin: 1px;',
                        icon: 'icons/script_code_red.png',
                        handler: function () {
                            window.location = 'export_to_xml_2021.php?keyId='
                                + compKey + '&fc=' + fc + '&comptype=' + comptype;
                        }
                    }, {
                        xtype: 'tbbutton',
                        text: 'Upload Config',
                        style: 'margin: 1px;',
                        icon: 'icons/application_get.png',
                        handler: function () {
                            CCAFileBrowse(compKey, fc);
                        }
                    }
                ]
            });
    }

    if ((comptype == 217) || (comptype == 218) || (comptype == 219)
        || (comptype == 220) || (comptype == 222)) {
        var tabs = new Ext.TabPanel({
            renderTo: 'tabs1',
            width: 1050,
            activeTab: 0,
            frame: true,
            defaults: {
                autoHeight: true
            },

            items: [{
                contentEl: 'parent1',
                title: 'Configuration',
                autoLoad: {
                    url: 'getComponentData.php',
                    params: {
                        fc: fc,
                        band: band,
                        type: comptype,
                        config: compKey,
                        tabtype: '1'
                    }
                }
            }]
        });
    }

    if (comptype == 20 || comptype == 11 || comptype == 6) {
        if ((band != 0) || (comptype == 6)) {
            tabs.add({
                contentEl: 'parent2',
                title: 'Operating Parameters'
            });
        }

        if (comptype == 20) {
            //CCA
            var subtabs = new Ext.TabPanel({
                renderTo: 'parent2',
                width: 1050,
                activeTab: 0,
                frame: true,
                defaults: {
                    autoHeight: true
                },

                items: [{
                    title: 'Mixer Params',
                    autoLoad: {
                        url: 'getComponentData.php',
                        params: {
                            fc: fc,
                            band: band,
                            type: comptype,
                            config: compKey,
                            tabtype: '2'
                        }
                    },
                    frame: true
                }, {
                    title: 'Preamp Params',
                    autoLoad: {
                        url: 'getComponentData.php',
                        params: {
                            fc: fc,
                            band: band,
                            type: comptype,
                            config: compKey,
                            tabtype: '3'
                        }
                    }
                }, {
                    title: 'Temperature Sensors',
                    autoLoad: {
                        url: 'getComponentData.php',
                        params: {
                            fc: fc,
                            band: band,
                            type: comptype,
                            config: compKey,
                            tabtype: '4'
                        }
                    }
                }]
            });

        }

        if (comptype == 6) {
            //Cryostat
            var subtabs = new Ext.TabPanel({
                renderTo: 'parent2',
                width: 1200,
                activeTab: 0,
                frame: true,
                defaults: {
                    autoHeight: true
                },
                items: [{
                    title: 'Temperature Sensors',
                    autoLoad: {
                        url: 'getComponentData.php',
                        params: {
                            fc: fc,
                            band: band,
                            type: comptype,
                            config: compKey,
                            tabtype: '2'
                        }
                    }
                }, {
                    title: 'Upload Data Files',
                    autoLoad: {
                        url: 'getComponentData.php',
                        params: {
                            fc: fc,
                            band: band,
                            type: comptype,
                            config: compKey,
                            tabtype: '5'
                        }
                    }
                }]
            });
        }

        if (comptype == 11) {
            //WCA

            new Ext.Toolbar(
                {
                    width: 1050,
                    height: 35,
                    defaults: {
                        autoScroll: true
                    },

                    renderTo: 'toolbar',
                    items: [
                        {
                            xtype: 'tbbutton',
                            text: 'Edit ' + CompDescription,
                            style: 'margin: 1px;',
                            icon: 'icons/application_edit.png',
                            handler: function () {
                                window.location = 'EditComponent.php?id='
                                    + compKey + '&fc=' + fc;
                            }
                        },
                        {
                            xtype: 'tbbutton',
                            text: CompDescription + '.INI for FEMC',
                            style: 'margin: 1px;',
                            icon: 'icons/control_equalizer_blue.png',
                            handler: function () {
                                window.location = '../wca/export_to_ini_wca.php?keyId='
                                    + compKey
                                    + '&fc='
                                    + fc
                                    + '&type=wca';
                            }
                        }, {
                            xtype: 'tbbutton',
                            text: 'Get Config INI',
                            style: 'margin: 1px;',
                            icon: 'icons/script.png',
                            handler: function () {
                                window.location = '../wca/export_to_ini_wca.php?keyId='
                                    + compKey + '&fc=' + fc;
                            }
                        }, {
                            xtype: 'tbbutton',
                            text: 'Get Config XML',
                            style: 'margin: 1px;',
                            icon: 'icons/script_code_red.png',
                            handler: function () {
                                window.location = 'export_to_xml_2021.php?keyId='
                                    + compKey + '&fc=' + fc + '&comptype=' + comptype;
                            }
                        }, {
                            xtype: 'tbbutton',
                            text: 'Upload Config',
                            style: 'margin: 1px;',
                            icon: 'icons/application_get.png',
                            handler: function () {
                                WCAFileBrowse(compKey, fc);
                            }
                        }]
                });

            var subtabs = new Ext.TabPanel({
                renderTo: 'parent2',
                width: 1200,
                activeTab: 0,
                frame: true,
                defaults: {
                    autoHeight: true
                },

                items: [{
                    title: 'General',
                    autoLoad: {
                        url: 'getComponentData.php',
                        params: {
                            fc: fc,
                            band: band,
                            type: comptype,
                            config: compKey,
                            tabtype: '2'
                        }
                    }
                }, {
                    title: 'Max Safe Operating Parameters',
                    autoLoad: {
                        url: 'getComponentData.php',
                        params: {
                            fc: fc,
                            band: band,
                            type: comptype,
                            config: compKey,
                            tabtype: '3'
                        }
                    }
                }, {
                    title: 'LO Parameters',
                    autoLoad: {
                        url: 'getComponentData.php',
                        params: {
                            fc: fc,
                            band: band,
                            type: comptype,
                            config: compKey,
                            tabtype: '4'
                        }
                    }
                }]
            });
        }
    }
}