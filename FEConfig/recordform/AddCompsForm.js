Ext.BLANK_IMAGE_URL = '../../ext/docs/resources/s.gif';

function AddComponents()
{
// vim: sw=4:ts=4:nu:nospell:fdc=4
/**
 * Ext.ux.grid.RecordForm Plugin Example Application
 *
 * @author    Ing. Jozef Sakáloš
 * @copyright (c) 2008, by Ing. Jozef Sakáloš
 * @date      31. March 2008
 * @version   $Id: recordform.js 156 2009-09-19 23:31:02Z jozo $

 * @license recordform.js is licensed under the terms of
 * the Open Source LGPL 3.0 license.  Commercial use is permitted to the extent
 * that the code/component(s) do NOT become part of another Open Source or Commercially
 * licensed development library or toolkit without explicit permission.
 * 
 * License details: http://www.gnu.org/licenses/lgpl.html
 */

/*global Ext, Web, Example */

/*Ext.ns('Example');
Ext.BLANK_IMAGE_URL = 'ext/resources/images/default/s.gif';
Ext.state.Manager.setProvider(new Ext.state.CookieProvider);
Example.version = 'Beta 2'*/


	Example.Grid1 = Ext.extend(Ext.grid.EditorGridPanel, {
	 layout:'fit'
	,border:false
	,stateful:false
	,url:'recordform/process-comps.php'
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
		//combo box for component description
		this.storeCombo = new Ext.data.Store({
            reader: new Ext.data.JsonReader({
               id: 'keyId'
              ,fields:[
                  {name: 'Description', type:'string'}
                ]
            }),
            proxy:new Ext.data.HttpProxy({url: this.url})
           ,baseParams:{cmd:'getCombo'}
           ,remoteSort:true
           ,autoLoad:true
        }); // eo storeCombo
        
		//create combo instance
		this.combo = new Ext.form.ComboBox({
            id:'compType'
            ,triggerAction: 'all'
            ,typeAhead:true
            ,lazyRender:true
            ,store: this.storeCombo
            ,valueField: 'Description'
            ,displayField: 'Description'
            ,forceSelection: true                // can only use elements in drop-down
            ,minChars : 1                       // minimum for autocomplete
            ,mode:'local'
        });
        
        // create row actions
		this.rowActions = new Ext.ux.grid.RowActions({
			 actions:[{
				 iconCls:'icon-minus'
				,qtip:'Delete Record'
			},{
				 iconCls:'icon-edit-record'
				,qtip:'Edit Record'
			}]
			,widthIntercept:Ext.isSafari ? 4 : 2
			,id:'actions'
			,getEditor:Ext.emptyFn
		});
		this.rowActions.on('action', this.onRowAction, this);

		Ext.apply(this, {
			store:new Ext.data.Store({
				reader:new Ext.data.JsonReader({
					 id:'keyId'
					,totalProperty:'totalCount'
					,fields:[
						 {name:'keyId', type:'int'}
						,{name:'Description', type:'string'}
						,{name:'SN', type:'string'}
						,{name:'Band', type:'int'}
						,{name:'ESN1', type:'string'}
						,{name:'ESN2', type:'string'}
						,{name:'ProductTreeNumber', type:'string'}
						,{name:'Production_Status', type:'string'}
						]
				})
				,proxy:new Ext.data.HttpProxy({url:this.url})
				,baseParams:{cmd:'getData'}
				,remoteSort:true
				,listeners:{
					load:{scope:this, fn:function(store) {

						// keep modified records accros paging
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
					}}
				}
			})
			
			,columns:[{ 
				header:'Type'
			   ,dataIndex:'Description'
				,width:270
				,sortable:true
				,align:'center'
				,editor:this.combo
				
			 },{
				 header:'Band'
						,dataIndex:'Band'
							
						,width:100
						,sortable:true
						,align:'center'
						,editor:new Ext.form.NumberField({
							allowBlank:true
							,decimalPrecision:2
							,selectOnFocus:true
						})
					}
			
			,{
				 header:'SN'
				//,id:'SN'
				,dataIndex:'SN'
				,width:100
				,sortable:true
				,align:'center'
				,editor:new Ext.form.TextField({
					allowBlank:false
				})
			},{
				 header:'ESN1'
				,dataIndex:'ESN1'
				,width:150
				,sortable:true
				,align:'center'
				,editor:new Ext.form.TextField({
					 allowBlank:true
				})
			},{
				 header:'ESN2'
				,dataIndex:'ESN2'
				,width:150
				,sortable:true
				,align:'center'
				,editor:new Ext.form.TextField({
					 allowBlank:true
				})
			}
		
			]
			
			,plugins:[this.rowActions, this.recordForm]
			,viewConfig:{forceFit:true}
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
					this.store.each(function(r) {
						r.reject();
					});
					this.store.modified = [];
                }
			}]
			,tbar:[{
				 text:'Add Record'
				,tooltip:'Add Record to Grid'
				,iconCls:'icon-plus'
				,id:'btn-add'
				,style: 'margin: 1px;'
				,listeners:{
					click:{scope:this, fn:this.addRecord,buffer:200}
				}
			},{
				 text:'Show Form'
				,tooltip:'Add Record with Form'
				,iconCls:'icon-form-add'
				,style: 'margin: 1px;'	
				,listeners:{
					click:{scope:this, buffer:200, fn:function(btn) {
						this.recordForm.show(this.addRecord(), btn.getEl());
					}}
				}
			}
			
			/*{
					text:'Bulk upload'
				   ,tooltip:'Bulk upload using Excel'
				   ,iconCls:'icon-excel-add'
				   ,listeners:{
					   click:{fn:function(){
						 window.location='ComponentsTemplate.xlsm';
					   }}
				   }
                }*/
            ]
		}); // eo apply



		// call parent
		Example.Grid1.superclass.initComponent.apply(this, arguments);
	} // eo function initComponent
	
	,onRender:function() {
		// call parent
		Example.Grid1.superclass.onRender.apply(this, arguments);

    } // eo function onRender
	
	,addRecord:function() {
		var store = this.store;
		if(store.recordType) {
			var rec = new store.recordType({newRecord:true});
			rec.fields.each(function(f) {
				rec.data[f.name] = f.defaultValue || null;
			});
			rec.commit();
			store.add(rec);
			return rec;
		}
		return false;
	} // eo function addRecord

	,onRowAction:function(grid, record, action, row, col) {
		switch(action) {
			case 'icon-minus':
				this.deleteRecord(record);
			break;

			case 'icon-edit-record':
				this.recordForm.show(record, grid.getView().getCell(row, col));
			break;
		}
	} // eo onRowAction
	
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
			}
		};
		Ext.Ajax.request(o);
	} // eo function commitChanges
	
	,requestCallback:function(options, success, response) {
			var o = Ext.decode(response.responseText);
			if(o.message == 0)
			{
				showpanel();
			}
			else
			{
				this.showError(o.message, 'ALERT');
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
			,fn:function(response) {
				if(response == 'ok')
				{
					showpanel();
				}
			}
		});
	} // eo function showError
	
	,deleteRecord:function(record) {
		Ext.Msg.show({
			 title:'Delete record?'
			,msg:'Do you really want to delete <b>' + record.get('company') + '</b><br/>There is no undo.'
			,icon:Ext.Msg.QUESTION
			,buttons:Ext.Msg.YESNO
			,scope:this
			,fn:function(response) {
				if('yes' !== response) {
					return;
				}
            }
		});
	} // eo function deleteRecord
	

}); // eo extend

// register xtype
Ext.reg('examplegrid1', Example.Grid1);

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
       ,title:"Components grid"
		,iconCls:'icon-grid'
		,width:800
		,height:400
        ,x:220
		,y:115
		,plain:true
		,layout:'fit'
		,closable:false
		,border:false
		,maximizable:true
		,items:{xtype:'examplegrid1', id:'examplegrid1'}
		,plugins:[new Ext.ux.menu.IconMenu()]
		
	});
	win.show();

	var rf = new Ext.ux.grid.RecordForm({
		 formCt:'east-form'
		,autoShow:true
		,autoHide:false
        ,ignoreFields:{keyId:true}
		,formConfig:{border:true, frame:false, margins:'10 10 10 10'}
	});
	

}); // eo onReady
}
// eof
