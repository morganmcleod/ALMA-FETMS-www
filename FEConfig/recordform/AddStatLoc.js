function showpanel()
{
	var win,dlgPopup, nav;
	var locations=new Ext.data.JsonStore({
		url:"recordform/GetLocationAndStatus.php?which=" + 1,
		fields: ['Description' , 'keyId']
	});
	
	locations.load();
	
	var statuses=new Ext.data.JsonStore({
		url:"recordform/GetLocationAndStatus.php?which=" + 2,
		fields: ['Status' , 'keyStatusType']
	});
	
	statuses.load();
	
	var frontends=new Ext.data.JsonStore({
		url:"recordform/GetLocationAndStatus.php?which=" + 3,
		fields:['maxkey','SN']
	});
	
	frontends.load();
	
	var users=new Ext.data.JsonStore({
		url:"recordform/GetLocationAndStatus.php?which=" + 4,
		fields:['Initials']
	});
	
	users.load();
	
	
	nav = new Ext.FormPanel({
					labelWidth:75,
					frame:true,
					width:300,
					collapsible:false,
					title:'Add Status, Location and Notes',
					items:[
					       	//'Enter data',
					       	new Ext.form.ComboBox({
						       	store:frontends,
							    displayField:'SN',
							    valueField:'maxkey',
							    id:'fesn',
							    name:'fesn',
							    fieldLabel:'Add to Frontend',
							    editable: false,
							    mode:'local',
							    emptyText:'None',
							    height:70,
								width:200,
								triggerAction:'all',
								hiddenName:'feval',
								submitValue:false
							}),  
					       	new Ext.form.ComboBox({
						       	store:locations,
							    displayField:'Description',
							    valueField:'keyId',
							    id:'comploc',
							    name:'comploc',
							    fieldLabel:'Location',
							    editable: false,
							    mode:'local',
							    emptyText:'Select',
							    height:70,
								width:200,
								triggerAction:'all',
								hiddenName:'locval',
								submitValue:false
							}),  
							new Ext.form.ComboBox({
						       	store:statuses,
							    displayField:'Status',
							    valueField:'keyStatusType',
							    id:'compstat',
							    name:'compstat',
							    fieldLabel:'Status',
							    editable: false,
							    mode:'local',
							    emptyText:'Select',
							    height:70,
								width:200,
								triggerAction:'all',
								hiddenName:'statval',
								submitValue:false
								}),  
					        new Ext.form.TextArea({
						        id:'notes',
						    	name:'notes',
						    	fieldLabel:'Notes',
						    	allowBlank:true,
						    	height:70,
						    	width:200
					        }),
					        new Ext.form.ComboBox({
						       	store:users,
							    displayField:'Initials',
							    valueField:'Initials',
							    id:'updated_by',
							    name:'updated_by',
							    fieldLabel:'Updated By',
							    editable: false,
							    mode:'local',
							    emptyText:'Select',
							    height:70,
								width:200,
								triggerAction:'all',
								hiddenName:'updatedby',
								submitValue:false
							})
					       ]
				});
				
				dlgPopup = new Ext.Window({
					renderto:'win_req_in',
					modal:true,
					layout:'fit',
					width:400,
					height:300,
					closable:true,
					resizable:false,
					plain:true,
					items:[nav],
					buttons:[{
						text:'Submit',
						handler:function(){
							var form=nav.getForm();
							var note=form.getValues()['notes'];
							var lockey=form.getValues()['locval'];
							var statkey=form.getValues()['statval'];
							var updatedby=form.getValues()['updatedby'];
							var fe=form.getValues()['feval'];
							form.submit
							({
								method:'post',
                                url: 'recordform/AddStatLocAndNotes.php',
								params:{notes:note,locval:lockey,statval:statkey,fe:fe,updatedby:updatedby}
							});
							dlgPopup.close();
							
							}
					}, {
						text:'Close',
						handler:function() 
						{
							dlgPopup.close();
						}
					}]
				});
				
				dlgPopup.show();
	}

