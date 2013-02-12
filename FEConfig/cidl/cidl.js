function CreateCIDL(fecfg,preparedbyname,almadocnum,fc)
{
	var win,dlgPopup, nav;
	
	nav = new Ext.FormPanel({
					labelWidth:105,
					frame:true,
					width:300,
					collapsible:false,
					title:'Generate CIDL Report',
					items:[
					       	//'Enter data',
					       new Ext.form.TextField({
						        id:'preparedbyname',
						    	name:'preparedbyname',
						    	fieldLabel:'Prepared By',
						    	allowBlank:true,
						    	height:30,
						    	width:300,
						    	value:preparedbyname
                             
					        }),
					        new Ext.form.TextField({
					        	id:'almadocnumname',
					        	name:'almadocnumname',
					        	fieldLabel:'ALMA Document Number',
						    	allowBlank:true,
						    	height:40,
						    	width:300,
                                value:almadocnum
                                
					        }),
					       
					       {
					    	   xtype: 'box',
					    	   autoEl: {tag: 'a', href: 'http://edm.alma.cl/addto3third.html?projectcode=FEND&level1code=40&level2code=00&level3code=00&level4code=00', children: [{tag: 'div', html: 'Click here to get an ALMA Doc Number.'}]},
					    	   style: 'cursor:pointer;'
					    	 }
					       ]
    
				
	});
				
				dlgPopup = new Ext.Window({
					renderto:'win_req_in',
					modal:true,
					layout:'fit',
					width:350,
					height:200,
					closable:true,
					resizable:false,
					plain:true,
					items:[nav],
					
					buttons:[{
						text:'Submit',
						cls:'button2',
						handler:function(){
							var form=nav.getForm();
							var pb=form.getValues()['preparedbyname'];
							var adn=form.getValues()['almadocnumname'];
							form.submit
							({
							
								method:'post',
								url: 'GenerateCIDL.php',
								params:{}
							});
							dlgPopup.close();
							window.location.href = "../pbar/statuscidl.php?fecfg=" + fecfg + "&fc=" + fc + "&pb=" + pb + "&adn=" + adn + "&lf=" + fecfg + "_" + fc;
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