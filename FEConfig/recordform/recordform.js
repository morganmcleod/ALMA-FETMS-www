/*
 * 2011-04-13 jee starting work to complete editing grid
 * 2011-04-14 jee continued...added missing onRowAction
 * 2011-04-15 jee to populate SN combo box, check if description field is already populated
 * 2011-04-18 jee modified process_request to use original database programs from jsaki
 */
Ext.BLANK_IMAGE_URL = '../../ext/docs/resources/s.gif';

var UpdatedBy = ' ';

var sVersion = 'Ver. 0.92 (2012-01-25)';
// number of records returned per page
var NUM_RECS_PER_PAGE_JEE  = 6;

function showsubform(keyFE, fc) {
	waitMsg:'Loading';
	// if true, then show serial number combo box
	var isShowComboSN = true;

	Example.Grid1 = Ext.extend(Ext.grid.EditorGridPanel, {
	 layout:'fit'
	,border:false
	,stateful:false
	,stripeRows: true
	,clicksToEdit: '1'
	,columnLines: true
	,url:'recordform/process-request.php'
	,idName:'keyId'

	,initComponent:function() {
		this.recordForm = new Ext.ux.grid.RecordForm({
			 title:'Components Form'
			,iconCls:'icon-edit-record'
			,columnCount:1
			,ignoreFields:{keyId:true}
			,formConfig:{
				 labelWidth:80
				,buttonAlign:'right'
				,bodyStyle:'padding-top:10px'
			}
		});
		
		//description combo box
		this.storeComboDesc = new Ext.data.Store({
            reader: new Ext.data.JsonReader({
               id: 'keyId'
               ,fields:[
                        {name: 'keyId', type:'int'}
                       ,{name: 'Description', type:'string'}
                ]
            }),
               proxy:new Ext.data.HttpProxy({url: this.url})
           	  ,baseParams:{cmd:'getComboDesc'}
              ,remoteSort:true
              ,autoLoad: true
        });           // eo storeComboDesc
        
		//Description combo instance
		this.comboDesc = new Ext.form.ComboBox({
            id:'idComboDesc'
            ,typeAhead: true
            ,triggerAction: 'all'
            ,lazyRender:false
            ,store: this.storeComboDesc
            ,valueField: 'Description'
            ,displayField: 'Description'
            ,forceSelection: true                // can only use elements in drop-down
            ,minChars : 1  // minimum for autocomplete
            ,mode:'local'
          });
		
		this.comboBand=new Ext.form.ComboBox({
			id:'idComboBand'
			,triggerAction: 'all'
			// ,lazyRender:false
		    ,store: new Ext.data.SimpleStore({
		    	fields:['bands']
		       ,data:[['1'],['2'],['3'],['4'],['5'],['6'],['7'],['8'],['9'],['10'],['No band']]
		    })
            ,displayField: 'bands'
			,forceSelection: true    // can only use elements in drop-down
			,triggerAction:'all'
			,mode:'local'
			,editable:true
			,lazyRender:false
			,forceSelection:true
		});
		
		
		//serial number combo box
		this.storeComboSN = new Ext.data.Store({
            reader: new Ext.data.JsonReader({
               id: 'SN'
               ,fields:[
                       {name: 'SN', type:'string'}
                ]
                ,editable:true
            }),
            url: this.url
           , method: 'GET'
         });           // eo storeComboDesc
        
		//serial number combo instance
		this.comboSN = new Ext.form.ComboBox({
            id:'idComboSN'
            ,typeAhead: true
            ,triggerAction: 'all'
            ,store: this.storeComboSN
            ,valueField: 'SN'
            ,displayField: 'SN'
            ,forceSelection: true                // can only use elements in drop-down
            ,minChars : 2 
        });
		
		// create row actions
		this.rowActions = new Ext.ux.grid.RowActions({
			 actions:[{
				 iconCls:'icon-minus'
				,qtip:'Delete Record'
			}]
			,widthIntercept:Ext.isSafari ? 4 : 2
			,id:'actions'
			,getEditor:Ext.emptyFn
			,destroy:Ext.emptyFn
		});
		this.rowActions.on('action', this.onRowAction, this);
		
		Ext.apply(this, {
			store:new Ext.data.Store({
				reader:new Ext.data.JsonReader({
					 id:'keyId'
					,totalProperty:'totalCount'
					//,root:'rows'
					,fields:[
						 {name:'keyId', type:'int'}
	 					,{name:'Band', type:'string'}
						,{name:'SN', type:'string'}
						,{name:'Description', type:'string'}
						,{name:'UserCode', type:'string'}
					]
				})
				,proxy:new Ext.data.HttpProxy({url:this.url})
				,baseParams:{cmd:'getData', objName:'tableFE_Components', key:keyFE, innerJoin:0}
				,remoteSort:true
				,listeners:{
					load:{scope:this, fn:function(store) {
						// keep modified records across paging
						var modified = store.getModifiedRecords();
						for(var i = 0; i < modified.length; i++) {
							var r = store.getById(modified[i].id);
							if(r) {
								var changes = modified[i].getChanges();
								for(p in changes) {
									if(changes.hasOwnProperty(p)) {
										r.set(p, changes[p]);
									}
								}
							}
						}
					}}		// eo load
				}			// eo store listeners
			})
			,columns:[{ 
						 header:'Type'
					   	,dataIndex:'Description'
                        ,id:'description'
						,width:270
						,sortable:false
						,editor:this.comboDesc
						,editable:true
						
					 },{
						 header:'Band'
						,dataIndex:'Band'
						,width:100
						,sortable:false
						,align:'center'
						
						,editor:this.comboBand
						
					},{
						header:'SN'
						,id:'SN'
						,dataIndex:'SN'
							,queryMode:'local'
								,typeAhead:true
						,width:100
						,sortable:false
						,align:'center'
						,editor:this.comboSN
						,editable:true
					},this.rowActions
					]
		     
			,plugins:[this.rowActions, this.recordForm]
			,viewConfig: {
				forceFit: true
			}
			,buttons:[{
				 text:'Save'
				,iconCls:'icon-disk'
				,scope:this
				,handler:this.commitChanges
			},{
				 text:'Reset'
				,iconCls:'icon-undo'
				,scope:this
				,handler:function() {
					this.store.each(function(r){
						r.reject();
					});
					this.store.modified = [];
					// reload to clear empty blank records
					this.store.reload();
				}// eo handler
			}
			
			
			
			,{
		           xtype: 'combo',
		           style: 'margin: 1px;',
		           id: 'search2',
		           name: 'state',
		           width: 150,
		           height: 20,
		           //typeAhead:true,
		           displayField: 'UserName',
		           hiddenValue: 'UserCode',
		           store: 'UserStore',
		           //allowBlank: true,
		           //forceSelection: true,
		           caseSensitive : false,
		           collapsible: true,
		           emptyText:'Your name',
		           typeAhead: true,
	                queryMode: 'local',
	                minChars: 1,
	                triggerAction: 'all'
	                
		           ,listeners: 
		           {'select': 
		        	   function (combo,record)
		        	   { 
		        	   		UpdatedBy = record.data['UserCode'];
		
		        	   }
		           }
		     }
			
			
			
			
			]				// eo buttons
			,tbar:[{
				 text:'Add Record'
				,tooltip:'Add Record to Grid'
				,iconCls:'icon-plus'
				,id:'btn-add'
				,listeners:{
					click:{scope:this,fn:this.addRecord,buffer:200}					
				}
			
			
			
			}]
		}); // eo apply
		
		/*
		this.bbar = new Ext.PagingToolbar({
			 store:this.store
			,displayInfo:true
			,pageSize:NUM_RECS_PER_PAGE_JEE
		});
		*/

		// call parent
		Example.Grid1.superclass.initComponent.apply(this, arguments);
	} // eo function initComponent
		
	,onRender:function() {
		// call parent
		Example.Grid1.superclass.onRender.apply(this, arguments);
		// load store
		this.store.load({
			waitMsg:'Loading...',
		   params:{
				start:0
			   ,limit: NUM_RECS_PER_PAGE_JEE 
			}
		});

	} // eo function onRender

	,listeners:{
		beforeedit: function(e){
			var rec = e.record;
			var descField = rec.data['Description'];
			var bandField= rec.data['Band'];
			var isDescEmptyAndBand_Selected = false;
			var isBandEmptyAndSN_Selected= false;
			var isRowHaveValues = false;
			var isDescEmpty = true;
			var isBandEmpty= true;
			var message = '';
			if(e.column == 1)
			{
				if (descField == null) 
				{
					isDescEmpty = true;
				}
				else if (descField.length == 0) 
				{
					isDescEmpty = true;
				} 
				else 
				{
					isDescEmpty = false;
				}
			}
			else if(e.column == 2)
			{
				if (bandField == null) 
				{
					isBandEmpty = true;
				}
				else if (bandField.length == 0) 
				{
					isBandEmpty = true;
				} 
				else 
				{
					isBandEmpty = false;
				}
			}
			if (rec.dirty) 
			{
				// record already dirty, so it's new and already has entry in at least one field.
				if(e.column ==1)
				{
					if (isDescEmpty)
					{
						isDescEmptyAndBand_Selected = true;
					}
					else
					{
						isDescEmptyAndBand_Selected = false;
					}
				} 
				else 
				{
					// not band column
					isDescEmptyAndBand_Selected = false;
				} // eo band column
				
				// is SN now selected with no entry in band?
				if (e.column == 2)
				{
					// SN column selected					
					if (isBandEmpty)
					{
						isBandEmptyAndSN_Selected = true;
					}
					else
					{
						isBandEmptyAndSN_Selected = false;
                        //load SN store
                        var compSN=Ext.getCmp('idComboSN');
						compSN.store.load({
		       				params:{cmd:'getComboSN',ctype: descField,band:bandField}
		       			});
					}
				}
				else
				{
					isBandEmptyAndSN_Selected = false;
				} // eo sn column
			}
			else 
			{
				// row not dirty, but does it have data?
				isRowHaveValues = false;
				rec.fields.each(function(f){
					if (rec.data[f.name] == null) {
						// skip null values
					} else if (rec.data[f.name].length > 0) {
						isRowHaveValues = true;
					}; // eo else if length > 0
				});	   // eo stepping function
				//is row new (not dirty and no values)?
				if (!isRowHaveValues) 
				{
					if (e.column == 1)
					{
						isDescEmptyAndBand_Selected = true;
					} 
					else 
					{
						isDescEmptyAndBand_Selected = false;
					}
					if (e.column == 2)
					{
						isBandEmptyAndSN_Selected= true;
					} 
					else 
					{
						isBandEmptyAndSN_Selected = false;
					}
				}
			} 			// eo row not dirty
			
			if (isRowHaveValues)
			{
				//message = 'Modifying existing component not permitted.';
				// don't allow editing
				e.cancel = true;
				return false;
			}
			else if (isDescEmptyAndBand_Selected)
			{
				message = 'Select component type before band number.';
			}
			else if(isBandEmptyAndSN_Selected)
			{
				message= 'Select Band before SN';
			}
			if (message.length > 0)
			{
				Ext.Msg.show({
					title: 'Information'
					,msg: message
					,icon: Ext.Msg.INFO
					,buttons: Ext.Msg.OK
					,scope: this
				});
				// don't allow editing
				e.cancel = true;
				return false;
			}
			else 
			{
				// description has data, allow editing
				e.cancel = false;
				return true;
			}
		}	// eo beforeedit
	}		// eo listeners
	
	,addRecord:function() {
		var store = this.store;
		if(store.recordType) {
			var rec = new store.recordType({newRecord:true});
			rec.fields.each(function(f) {
				rec.data[f.name] = f.defaultValue || null;
				f.readOnly=false;
			});
			rec.commit();
			store.insert(0,rec);
            this.startEditing(0, 0);
			return rec;
		}
		this.commitChanges();


	
		return false;
	}	// eo function addRecord
	
	,onRowAction:function(grid, record, action, row, col) {
		switch(action) {
			case 'icon-minus':
				this.deleteRecord(record);
			break;

			case 'icon-edit-record':
				this.recordAdded = false;			// set this so cancel doesn't delete entire record 
				this.recordForm.show(record, grid.getView().getCell(row, col));
			break;
		}
	} // eo function onRowAction
	
	,commitChanges:function() {
		var records = this.store.getModifiedRecords();
		if(!records.length) {
			return;
		}
		var data = [];
		Ext.each(records, function(r, i) {
			var o = r.getChanges();
			if(r.data.newRecord) {
				o.newRecord = true;
			}
			o[this.idName] = r.get(this.idName);
			data.push(o);
		
		}, this);
		var o = {
			 url:this.url
			,method:'post'
			,callback:this.requestCallback
			,scope:this
			,params:{
				 cmd:'saveData'
				,objName:this.objName
				,data:Ext.encode(data)
				,fekey:keyFE
				,UserCode:UpdatedBy
			}
		};
		Ext.Ajax.request(o);
		//this.getStore().load();
		
		
		/*
		 * 
		 * CHANGE color from green back to white
		 */
		
		//this.store.load();
		
		
	} // eo function commitChanges
	
	,requestCallback:function(options, success, response) {
		if(true !== success) {
			this.showError(response.responseText);
			return;
		}
		try {
			var o = Ext.decode(response.responseText);
			if(o.deleterec == true)
			{
				location.reload();
				//this.store.load({params:{cmd:'getData',key:o.newconfig}});
			}
		}
		catch(e) {
			this.showError(response.responseText, 'Cannot decode JSON object');
			return;
		}
		if(true !== o.success) {
			this.showError(o.error || 'Unknown error');
			return;
		}

		switch(options.params.cmd) {
			case 'saveData':
				var records = this.store.getModifiedRecords();
				Ext.each(records, function(r, i) {
					if(o.insertIds && o.insertIds[i]) {
						r.set(this.idName, o.insertIds[i]);
						delete(r.data.newRecord);
					}
				});
				this.store.each(function(r) {
					r.commit();
				});
				this.store.modified = [];
//				this.store.commitChanges();
			break;

			case 'deleteData':
			break;
		}
	} // eo function requestCallback
	
	,showError:function(msg, title) {
		Ext.Msg.show({
			 title:title || 'Error'
			,msg:Ext.util.Format.ellipsis(msg, 2000)
			,icon:Ext.Msg.ERROR
			,buttons:Ext.Msg.OK
			,minWidth:1200 > String(msg).length ? 360 : 600
		});
	} // eo function showError

	,deleteRecord:function(record) {
		if(record.dirty || record.data['Description'] == null )
		{
			var index=this.getSelectionModel().getSelectedCell();
			if(!index)
			{
				return false;
			}
			var rec=this.store.getAt(index[0]);
			this.store.remove(rec);
            rec.data.newRecord=false;
		}
		else
		{
            Ext.Msg.show({
                 title:'Delete record?'
                ,msg:'Do you really want to delete SN: <b>' + record.get('SN') + '</b><br/>This action will reload the current page. You will lose any unsaved data.'
                ,icon:Ext.Msg.QUESTION
                ,buttons:Ext.Msg.YESNO
                ,scope:this
                ,fn:function(response) {
                    if('yes' !== response) {
                        return;
                    }
                    else
                    {
                        var o = {
                                 url:this.url
                                ,method:'post'
                                ,callback:this.requestCallback
                                ,scope:this
                                ,params:{
                                     cmd:'deleteData'
                                    ,compkey:record.get('keyId')
                                    ,fekey:keyFE
                                    ,UserCode:UpdatedBy
                                }
                            };
                            Ext.Ajax.request(o);
                    }
                }
            });
        }
	} // eo function deleteRecord
	
}); // eo extend

// register xtype
Ext.reg('examplegrid1', Example.Grid1);
Ext.reg('userStore', UserStore);
new UserStore();

/*
 * Adds zeros in front of number
 * Calling Params: num - number to format
 *                 count - total number of digits to return, if number is less than count, pad with zeros
 * Returns: Padded number
 * 
 * 2011-04-14 jee
 */
function zeroPad(num,count) { 
	var numZeropad = num + '';
	while(numZeropad.length < count) {	
		numZeropad = "0" + numZeropad; 
	}
	return numZeropad;
};

/*
 * Returns 'now() formated as HH:MM:SS.DDD
 * Calling Params: bMilliSecs - if false, then exclude milliseconds
 * Returns: formatted time
 * 
 * 2011-04-14 jee
 */
function timeStamp(bMilliSecs) { 
	var d = new Date();
	var timeNow = zeroPad(d.getHours(),2) + ":" + zeroPad(d.getMinutes(),2) + ":" + zeroPad(d.getSeconds(),2);
	if (bMilliSecs === false) {
		// don't include millisecs
	}
	else {
		timeNow += "." + zeroPad(d.getMilliseconds(),3);
	}
	return timeNow += ":";
};




// app entry point
Ext.onReady(function() {
	Ext.QuickTips.init();

	var page = new WebPage({
		version:Example.version
		,westContent:'west-content'
	});

	Ext.override(Ext.form.Field, {msgTarget:'side'});
	var win = new Ext.Window({
		 id:'rfwin'
        ,title:"Components list " + sVersion
		,iconCls:'icon-grid'
		,width:800
		,height:500
//		,stateful:false
		,x:226
		,y:110
		,plain:true
		,layout:'fit'
		,closable:false
		,border:false
		,maximizable:true
		,items:{xtype:'examplegrid1', id:'examplegrid1'}
		,plugins:[new Ext.ux.menu.IconMenu()]
		
	});		// eo window
	
	
	waitMsg:'Loading';
	win.show();
    
 /*   2011-04-26 jee this adds no value
  * // Listen to "exception" event fired by all proxies
    Ext.data.DataProxy.on('exception', function(proxy, type, action, exception) {
        if (!Ext.isIE) console.error(type + action +  exception);
    });
*/
 
	//captureEvents(Ext.getCmp('examplegrid1').store, 'examplegrid1Store');
	//captureEvents(Ext.getCmp('examplegrid1'), 'examplegrid1');
	//captureEvents(Ext.getCmp('idComboSN'), 'idComboSN');

	
	
	
}); // eo onReady



}



UserStore = Ext.extend(Ext.data.JsonStore, {
    constructor: function(cfg) {
        cfg = cfg || {};
        UserStore.superclass.constructor.call(this, Ext.apply({
            storeId: 'UserStore',
            root: 'records',
            url: 'recordform/getUsers.php',
            idProperty: 'userCode',
            messageProperty: 'message',
            fields: [
                {
                    name: 'UserName'
                },
                {
                    name: 'UserCode'
                }
            ]
        }, cfg));
    }
});
