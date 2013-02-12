<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<link rel="stylesheet" type="text/css" href="Display/style.css">
<title>Add New Bug</title>
<script type="text/javascript">
function setDropdownVal(dropdownval)
{
	var s = document.getElementById('swmodule');
	for (var i=0; i< s.options.length; i++ ) 
	{
		if(s.options[i].value == dropdownval) 
        {
			s.options[i].selected = true;
            i=100;
            return;
        }
	}
}
</script>
</head>
<?php $ModuleKey=$_GET['modulekey']; ?>
<body onload=" setDropdownVal(<? echo $ModuleKey; ?>);">
<?php Include "Display/Header.php"; ?>
<form onsubmit="return AddNewBugValidate(this);" action="Controllers/CaddNewBug.php" method="post" name="newbug" >
<div id="content_inside_main">
	<div id="wrapper">
		<div id="middle">
			<table>
			<tr><td>Software Module:</td><td><select name='swmodule' id='swmodule'>
					<option></option><?php echo $swmodule_block;?></select></td></tr> 
			<tr><td>Bug Description:</td><td><textarea rows=2 cols=40 name='bugdesc' id='bugdesc'></textarea><div id='fieldtag'>(A brief description of the bug using less than about 20 words)</div></td></tr>
			<tr><td>Person Reporting Bug:</td><td><input type=text name='reporter' id='reporter'><div id='fieldtag'>(Enter your last name)</div></td></tr>
			
			</select>
			
			</td></tr>
			
			
			<tr><td>Date Entered:</td><td><input type=text name='dtentered' id='dtentered' value=<?php echo date("Y-m-d");?>></input></td></tr>
			<tr><td>Priority:</td><td><input type=radio name='priority' id='priority' value=0>Enhancement
			<input type=radio name='priority' id='priority' value=1>Critical
			<input type=radio name='priority' id='priority' value=2>Defect
			<div id='fieldtag'>(Enhancement-additional features or changes, Critical-Program can't be used, Defect-Program continues running)</div> 
			</td></tr>
			<tr><td>Notes:</td><td><textarea rows=5 cols=40 id='notes' name='notes'></textarea><div id='fieldtag'>(Any information that might be helpful in resolving the bug)</div></td></tr>
			</table>
		</div>	
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=submit name='submit' value='Submit'></input>
	</div>
</div>
</form>
<?php Include "Display/footer.php";?>
</body>
</html>