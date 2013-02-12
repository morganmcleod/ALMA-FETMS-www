Ext.namespace('Ext.ux.plugins');
Ext.ux.plugins.HeaderButtons = function(config)
{
	Ext.apply(this, config);
};

Ext.extend(Ext.ux.plugins.HeaderButtons, Ext.util.Observable,
{
	init: function(panel)
	{
        if (panel.hbuttons)
        {          
            Ext.apply(panel, 
            {
				onRender: panel.onRender.createSequence(function(ct, position)
				{
					if (this.headerButtons && this.headerButtons.length > 0)
					{
						var tb = this.header.createChild(
						{
							cls:'ux-panel-header-btns-ct', 
							cn:
							{
								cls:"ux-panel-header-btns",
								html:'<table cellspacing="0"><tbody><tr></tr></tbody></table><div class="x-clear"></div>'
							}
						}, this.header.first('span', true), true); // insert before header text (but after tools)
						
						var tr = tb.getElementsByTagName('tr')[0];
						for (var i = 0, len = this.headerButtons.length; i < len; i++)
						{
							var b = this.headerButtons[i];
							var td = document.createElement('td');
							td.className = 'ux-panel-header-btn-td';
							b.render(tr.appendChild(td));
						}
					}
			        
				}),

				addHeaderButton: function(config, handler, scope)
				{
					var bc = 
					{
						handler: handler,
						scope: scope,
						hideParent: true
					};
					if(typeof config == "string")
						bc.text = config;
					else
						Ext.apply(bc, config);
			        
					var btn = new Ext.Button(bc);
					btn.ownerCt = this;
					if(!this.headerButtons)
						this.headerButtons = [];
			        
					this.headerButtons.push(btn);
					return btn;
				}            
            });

            var btns = panel.hbuttons;
            
            panel.headerButtons = [];
            for (var i = 0, len = btns.length; i < len; i++) {
                if(btns[i].render)
					panel.headerButtons.push(btns[i]);
                else
                    panel.addHeaderButton(btns[i]);
            }
            
            delete panel.hbuttons;
        }
	}
});