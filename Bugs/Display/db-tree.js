function getBugs(assignedto)
{
    //var Ebugs,Cbugs,Dbugs;
	var tree = new Ext.tree.ColumnTree({
        width: 1150,
        //height: 2500,
        autoHeight:true,
        rootVisible:false,
        autoScroll:true,
        title: 'Bugs',
        useArrows:true,

        columns:[
        {
        	header:'Project Name/Task ID',
        	width:200,
        	dataIndex:'subproj'
        },{
        	header:'Description',
        	width:270,
        	dataIndex:'description'
        },{
            header:'Assigned To',
            width:80,
            dataIndex:'assignto'
        },{
            header:'Reported By',
            width:80,
            dataIndex:'reportby'
        },{
            header:'Reported Date',
            width:90,
            dataIndex:'rdate'
        },{
        	header:'Notes',
        	width:350,
        	dataIndex:'tasknote'
        },{
        	header:'Priority',
        	width:80,
        	dataIndex:'priority',
        	renderer:function(myValue,node)
        	{
        		var depth=node.getDepth();
        		if(depth == 2)
        		{
        			if(myValue==0)
        			{
        				return '<img src="Display/enhancement.PNG" alt="Enhancement">';
        			}
        			else if(myValue == 1)
        			{
        				return '<img src="Display/critical.PNG" alt="Critical Bug">';
        			}	
        			else if(myValue==2)
        			{
        				return '<img src="Display/defect.PNG" alt="Defect Bug">';
        			}
        			else
        			{
        				return '<img src="Display/critical.PNG" alt="Critical Bug">';
        			}
        		}	
        	}
        }
        ],
        
        listeners: {
            click: function(node,key){
    			if(node.getDepth() == 2)
    			{
    				key=node.attributes.subproj;
    				location.href="BugDetails.php?bugkey=" + key;
    				//var NodeDepth=node.getDepth();
    				//ExpandBugNotes(key,NodeDepth);
    			}
            }
        },
        
        loader: new Ext.tree.TreeLoader({
        dataUrl:'Helpers/GetBugs.php?assignedto=' + assignedto,
        uiProviders:{
                		'col': Ext.tree.ColumnNodeUI
            		}
        }),
        root: new Ext.tree.AsyncTreeNode({
        	dataIndex:'subproj',
        	id:'subproj'
         })
 });
    	
    	var container=document.getElementById('db-tree');
 		container.innerHTML="";
 		tree.render('db-tree');
 		//PrintBugStats(Ebugs,Cbugs,Dbugs);
}

function ExpandBugNotes(bugkey,nodedepth)
{
	var win,dlgPopup, nav;
		
		var users= new Ext.data.SimpleStore({
		fields: ['users'],
		data:[['Castro'],['Crabtree'],['Effland'],['Lacasse'],['McLeod'],['Nagaraj']]
	});
	
	nav = new Ext.FormPanel({
					labelWidth:150,
					frame:true,
					width:350,
					collapsible:false,
					title:'Add Event',
					items:[
					       	//'Enter data',
					       	new Ext.form.TextField({
						    id:'reporter',
						    name:'reporter',
						    fieldLabel:'Your Name',
						    height:25,
							width:250,
							allowBlank:false
							}),  
							new Ext.form.ComboBox({
						        store:users,
						        displayField:'users',
						        id:'fixers',
						        name:'fixers',
						        fieldLabel:'Change person assigned to this',
						        editable:false,
						        mode:'local',
						        height:70,
						        width:250,
						        triggerAction:'all'
							}),
							new Ext.form.Checkbox({
								id:'resolved',
								name:'resolved',
								boxLabel:'Bug Fixed',
								fieldLabel:'Bug Status'
							}),
					        new Ext.form.TextArea({
					        id:'notes',
					    	name:'notes',
					    	fieldLabel:'Notes',
					    	allowBlank:true,
					    	height:100,
					    	width:250
					        })
					        ]
				});
				
				dlgPopup = new Ext.Window({
					renderto:'win_req_in',
					modal:true,
					layout:'fit',
					width:500,
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
							var reporter=form.getValues()['reporter'];
							var fixers=form.getValues()['fixers'];
							form.submit({
							url: 'HandleSubAssyUpdate.php',
							params:{bugkey:bugkey,notes:note,reporter:lockey,assignedto:fixers}
							});
							dlgPopup.close();
							}
					}, {
						text:'Close',
						handler:function() {
							dlgPopup.close();
							}
					}]
				});
				
				dlgPopup.show();
}

function PrintBugStats(Ebugs,Cbugs,Dbugs)
{
	var totalBugs=Ebugs + Cbugs + Dbugs;
	document.getElementById('bugstats').innerHTML=Ebugs;
}
		


