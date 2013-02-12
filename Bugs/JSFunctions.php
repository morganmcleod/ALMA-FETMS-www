<html>
<head>
<script type='text/javascript' language='JavaScript'>
function AddNewBugValidate(formname)
{
	var module_err = "";
	var bugdesc_err = "";
	var reporter_err = "";

	module_err += validateDropdown(document.newbug.swmodule);
	bugdesc_err += validateEmp(document.newbug.bugdesc);
	reporter_err += validateEmp(document.newbug.reporter);
  	
  	if (module_err != "")
 	{
    	alert("Please specify software module\n",0);
    	return false;
  	}
 	if (bugdesc_err !="") 
 	{
 		alert("Please add bug description\n",0);
 		return false;
 	}
 	if(reporter_err !="")
 	{
 		alert("Please enter your last name\n",0);
 		return false;
 	}
}
function validateEmp(fld)
{
    var error = "";
  
    if (fld.value.length == 0)
    {
        fld.style.background = 'DarkGray';
        error = "The required field has not been filled in.\n" ;
    }
    else
    {
        fld.style.background = 'White';
    }
    return error;   
}
function validateDropdown(fld)
{
	var error="";
	
	if(fld.options[fld.selectedIndex].value == "")
	{
		fld.style.background = 'DarkGray';
        error = "The required field has not been filled in.\n" ;
    }
    else
    {
    	fld.style.background = 'White';
    }
    return error;
}
</script>
</head>
</html>