/**
 * Get the initial list of categories using AJAX
 *
 * @return
 */
function init_widgets()
{
    new Ajax.Request('widget_data.php' , 
		     {
			 method: 'get',
			     asynchronus:true,
			     onComplete: function(response)
			     {
				 var categoriesXML = response.responseXML;
				 var catNames = categoriesXML.getElementsByTagName("catname");
				 for(var counter = 0; counter < catNames.length; counter++ )
				     {
					 document.forms['AddComponent'].widgetcat.options[counter] = new Option(catNames[counter].getElementsByTagName("name")[0].firstChild.nodeValue,catNames[counter].getElementsByTagName("name")[0].firstChild.nodeValue, false, false);
					 
				     }
			     }
		     });
}


/**
 * Get a list of widgets using AJAX
 *
 * @param coursename The name of the course whose numbers we are to retrieve
 *
 * @return
 */
function getwidgets(category)
{
    new Ajax.Request('widget_data.php?category='+category , 
		     {
			 method: 'get',
			     asynchronus:true,
			     onComplete: function(response)
			     {
				 var widgetsXML = response.responseXML;
				 var widgets = widgetsXML.getElementsByTagName("widget");
				 document.forms['AddComponent'].widgetid.options.length=0;
				 for(var counter = 0; counter < widgets.length; counter++ )
				     {
					
					 document.forms['AddComponent'].widgetid.options[counter] = new Option(widgets[counter].getElementsByTagName("name")[0].firstChild.nodeValue, widgets[counter].getElementsByTagName("name")[0].firstChild.nodeValue, false, false);
				     }
			     }
		     });
    
}




