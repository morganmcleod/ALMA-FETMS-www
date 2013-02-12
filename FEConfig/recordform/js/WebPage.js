// vim: sw=4:ts=4:nu:nospell:fdc=4
/**
 * @class WebPage
 *
 * WebPage Layout Generator
 *
 * @author    Ing. Jozef Sakáloš
 * @copyright (c) 2008, by Ing. Jozef Sakáloš
 * @date      6. April 2008
 * @version   1.0
 * @revision  $Id: WebPage.js 642 2009-03-20 21:25:51Z jozo $
 *
 * @license WebPage.js is licensed under the terms of the Open Source
 * LGPL 3.0 license. Commercial use is permitted to the extent that the 
 * code/component(s) do NOT become part of another Open Source or Commercially
 * licensed development library or toolkit without explicit permission.
 *
 *<p>License details: <a href="http://www.gnu.org/licenses/lgpl.html"
 * target="_blank">http://www.gnu.org/licenses/lgpl.html</a></p>
 */
 
/*global Ext, WebPage */
 
Ext.ns('WebPage');
 
WebPage = function(config) {
	Ext.apply(this, config, {
		 autoRender:true
		,autoTitle:true
		,langCombo:false
		,ctCreate:{tag:'div', id:'ct-wrap', cn:[{tag:'div', id:'ct'}]}
	});

	if(this.autoRender) {
		this.render();
	}
};
Ext.override(WebPage, {
	
	
	render:function() {
		var body = Ext.getBody();
		var dh = Ext.DomHelper;

		// create wrap and container
		this.wrap = dh.insertFirst(body, this.ctCreate, true);
		this.ct = Ext.get('ct');

		if(this.width) {
			this.ct.setWidth(this.width);
		}

		this.north = dh.append(this.ct, {tag:'div', id:'north'}, true);
		if(this.northHeight) {
			this.north.setHeight(this.northHeight);
		}

		this.nav = dh.append(this.ct, {tag:'div', id:'navlinks'}, true);
		if(this.navHeight) {
			this.nav.setHeight(this.navHeight);
		}

		if(this.adRowContent) {
			this.adrow = dh.append(this.ct, {tag:'div', id:'adrow'}, true);
			if(this.adrowHeight) {
				this.adrow.setHeight(this.adrowHeight);
			}
		}

		this.west = dh.append(this.ct, {tag:'div', id:'west'}, true);
		if(this.westWidth) {
			this.west.setWidth(this.westWidth);
		}

		this.center = dh.append(this.ct, {tag:'div', id:'center'}, true);
		if(this.westWidth && this.eastWidth) {
			this.center.setWidth(this.ct.getWidth() - this.westWidth - this.eastWidth);
		}

		this.east = dh.append(this.ct, {tag:'div', id:'east'}, true);
		if(this.eastWidth) {
			this.east.setWidth(this.eastWidth);
		}

		this.south = dh.append(this.ct, {tag:'div', id:'south'}, true);
		if(this.southHeight) {
			this.south.setHeight(this.southHeight);
		}

		// {{{
		// north content
		if(this.northContent) {
			this.north.appendChild(this.northContent);
			this.northContent = Ext.get(this.northContent).removeClass('x-hidden');
		}
		else if(this.autoTitle) {
			var title = Ext.fly('page-title');
			if(title) {
				title = title.dom.innerHTML;
				title += this.version ? ' - ver.: ' + this.version : '';
			}
			this.north.createChild({tag:'h1', html:title});
		}

		// theme select combo
		
		// }}}

		if(this.navlinks) {
			this.navlinksTpl.overwrite(this.nav, {navlinks:this.navlinks});
		}

		if(this.adrow) {
			this.adrow.appendChild(this.adRowContent);
			this.adRowContent = Ext.get(this.adRowContent).removeClass('x-hidden');
		}

		if(this.westContent) {
			this.west.appendChild(this.westContent);
			this.westContent = Ext.get(this.westContent).removeClass('x-hidden');
		}

		if(this.centerContent) {
			this.center.appendChild(this.centerContent);
			this.centerContent = Ext.get(this.centerContent).removeClass('x-hidden');
		}

		if(this.eastContent) {
			this.east.appendChild(this.eastContent);
			this.eastContent = Ext.get(this.eastContent).removeClass('x-hidden');
		}

		if(this.southContent) {
			this.south.appendChild(this.southContent);
			this.southContent = Ext.get(this.southContent).removeClass('x-hidden');
		}

		

	} // eo function render
});
 
// eof

