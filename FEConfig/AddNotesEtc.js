function AddNotes(keyfe,facility)
{
	//called from getFrontEndData.php
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
	
	var users=new Ext.data.JsonStore({
		url:"recordform/GetLocationAndStatus.php?which=" + 4,
		fields: ['Initials']
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
					        new Ext.form.TextField({
					        	id:'urls',
					        	name:'urls',
					        	fieldLabel:'Link',
					        	allowBlank:true,
					        	height:40,
					        	width:200
					        }),
					        new Ext.form.ComboBox({
						       	store:users,
							    displayField:'Initials',
							    valueField:'Initials',
							    id:'updatedby',
							    name:'updatedby',
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
							var updatedby=form.getValues()['updated_by'];
							var urls=form.getValues()['urls'];
							form.submit
							({
								method:'post',
								url: 'AddNotesAndStuff.php',
								params:{facility:facility,key:keyfe,notes:note,url:urls,locval:lockey,statval:statkey,updatedby:updatedby}
							});
							dlgPopup.close();
							setTimeout("location.reload(true)",1250);
						 }
					}, {
						text:'Cancel',
						handler:function() 
						{
							dlgPopup.close();
						}
					}]
				});
				
				dlgPopup.show();
		}
