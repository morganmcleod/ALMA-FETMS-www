function createCCAPASTabs(ccaid,fc){
	   var tabs = new Ext.TabPanel({
	        renderTo: 'ccapastabs',
	        width:1000,
	        height:750,
	        activeTab: 0,
	        frame:true,
	        defaults:{ autoScroll:true},
	        bodyStyle:{"background-color":"#C9C9C9"},
	        
	        items:[
		            {title: 'Info'              ,  autoLoad:{url: 'getCCAPASData.php',params:{ccaid:ccaid,fc:fc,tabtype:'info'         }}},
		        	{title:'IV Curve'           ,  autoLoad:{url: 'getCCAPASData.php',params:{ccaid:ccaid,fc:fc,tabtype:'ivcurve'      }}},
		            {title:'Amplitude Stability',  autoLoad:{url: 'getCCAPASData.php',params:{ccaid:ccaid,fc:fc,tabtype:'ampstab'      }}},
		            {title:'IF Spectrum'        ,  autoLoad:{url: 'getCCAPASData.php',params:{ccaid:ccaid,fc:fc,tabtype:'ifsepctrum'   }}},
		            {title:'In Band Power'      ,  autoLoad:{url: 'getCCAPASData.php',params:{ccaid:ccaid,fc:fc,tabtype:'inbandpower'  }}},
		            {title:'Noise Temperature'  ,  autoLoad:{url: 'getCCAPASData.php',params:{ccaid:ccaid,fc:fc,tabtype:'noisetemp'    }}},
		            {title:'Power Variation'    ,  autoLoad:{url: 'getCCAPASData.php',params:{ccaid:ccaid,fc:fc,tabtype:'powervar'     }}},
		            {title:'Total Power'        ,  autoLoad:{url: 'getCCAPASData.php',params:{ccaid:ccaid,fc:fc,tabtype:'totalpower'   }}},
		            {title:'Pol Accuracy'       ,  autoLoad:{url: 'getCCAPASData.php',params:{ccaid:ccaid,fc:fc,tabtype:'polaccuracy'  }}},
		            {title:'Sideband Ratio'     ,  autoLoad:{url: 'getCCAPASData.php',params:{ccaid:ccaid,fc:fc,tabtype:'sidebandratio'}}},
		            {title:'Phase Drift   '     ,  autoLoad:{url: 'getCCAPASData.php',params:{ccaid:ccaid,fc:fc,tabtype:'phasedrift'   }}}
				  ]


	    });
	   

}