<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<link rel="stylesheet" type="text/css" href="Display/style.css">
<link type="text/css" href="http://www.cv.nrao.edu/php-internal/ntc/Band6Plots/extjs/css/ext-all.css" media="screen" rel="Stylesheet" />
<link type="text/css" href="http://www.cv.nrao.edu/php-internal/ntc/Band6Plots/extjs/column-tree.css" media="screen" rel="Stylesheet" />
<script src="http://www.cv.nrao.edu/php-internal/ntc/Band6Plots/extjs/ext-base.js" type="text/javascript"></script>
<script src="http://www.cv.nrao.edu/php-internal/ntc/Band6Plots/extjs/ext-all.js" type="text/javascript"></script>
<script src="http://www.cv.nrao.edu/php-internal/ntc/Band6Plots/extjs/ColumnNodeUI.js" type="text/javascript"></script>
<script src="Display/db-tree.js" type="text/javascript"></script>
<title>Bugs Home</title>
<script type="text/javascript">
function setDropdownVal(dropdownval)
{
	var s = document.getElementById('sweng');
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
<?php $developer=$_GET['developer'];?>
<body onload="setDropdownVal(<?php echo $developer;?>); var val=document.getElementById('sweng').value; getBugs(val);">
<div id="win_req_in" class="x-hidden"></div>
<?php Include "Display/Header.php";?>
<div id="wrap">
<b>Get Bugs Assigned to:</b>&nbsp;<select name='sweng' id='sweng' onChange="getBugs(this.value);"><option value='Select'>Select...</option>
<option value='Castro'>Castro</option><option value='Crabtree'>Crabtree</option><option value='Effland'>Effland</option>
<option value='Lacasse'>Lacasse</option><option value='McLeod'>McLeod</option><option value='Nagaraj'>Nagaraj</option></select>
&nbsp;&nbsp;&nbsp;<a href="AddNewBug.php">Add a Bug</a>
<div id="nocontenttoponly">
<div id="wrapgrid">
<div id="db-tree">
</div>
<div id="bugstats"></div>
</div>
</div>
</div>
<?php Include "Display/footer.php";?>
</body>
</html>