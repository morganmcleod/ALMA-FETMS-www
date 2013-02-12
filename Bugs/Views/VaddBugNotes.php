<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<link rel="stylesheet" type="text/css" href="Display/style.css">
<title>Add Bug Notes</title>
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
<body>
<?php Include "Display/Header.php";?>
<form action="Controllers/CaddBugNotes.php" method="post" name="bugnotes">
<div id="wrap" style="height:650px;">
<br><br>
	<div id="basictable">
	<table><tr><th style="width:30%;">Description</th><th style="width:5%;">Responsible/ Entered By</th><th style="width:10%;">Entered On</th><th style="width:45%;">Notes</th><th style="width:10%;">Status</th></tr>
	<?php while($d=mysql_fetch_array($MainBugDetails))
		  {
		  	$desc=$d['Description'];
		  	$reported_by=$d['ReportedBy'];
		  	$assignedto=$d['AssignedTo'];
		  	$date_entered=$d['DateEntered'];
		  	$notes=$d['Notes'];
		  	$status=$d['TaskStatus'];
		  	$priority=$d['Priority'];
		  	
		  	echo "<tr><td>$desc</td>
		  	<td>$assignedto/ $reported_by</td>
		  	<td>$date_entered</td>
		  	<td bgcolor='#ffffff'>" . nl2br($notes) . "</td>
		  	<td>$status</td></tr>";

		  }
		  if($priority == '0')
		  {
		  	$enhancement="checked";
		  }
		  else
		   if($priority == '1')
		  {
		  	$critical="checked";
		  }
		  else if($priority == '2')
		  {
		  	$defect="checked";
		  }
	?>
	</table>
	</div>
	<br><br>
	<div id="content_inside_main" style="width:1220px;">
	<div id="wrapper">
	<div id="middle">
	<input type=hidden name='bugkey' id='bugkey' value=<?php echo $bugkey;?>>
	<table>
		
		<tr><td>Your Last Name:</td><td><input type=text name='reporter' id='reporter'></td></tr>
		<tr><td>Date Entered:</td><td><input type=text name='dtentered' id='dtentered' value="<?php $date=date("Y-m-d H:i:s");echo "$date"; ?>"></input></td></tr>
		<tr><td>Change Priority:</td><td><input type=radio name='priority' id='priority' value=0 <?php echo $enhancement; ?>>Enhancement
			<input type=radio name='priority' id='priority' value=1 <?php echo $critical; ?>>Critical
			<input type=radio name='priority' id='priority' value=2 <?php echo $defect; ?>>Defect
			<div id='fieldtag'>(Enhancement-additional features or changes, Critical-Program can't be used, Defect-Program continues running)</div> 
			</td></tr>
		<tr><td>Change person assigned to:
		
		<input type="hidden" name="originalreporter" value= '<?php echo $reported_by; ?>'>
		<input type="hidden" name="originalnotes" value= '<?php echo $desc; ?>'>
		</td>
		
		<td><select name='sweng' id='sweng'><option></option>
		<option value='Castro'>Castro</option>
		<option value='Crabtree'>Crabtree</option>
		<option value='Effland'>Effland</option>
		<option value='Lacasse'>Lacasse</option>
		<option value='McLeod'>McLeod</option>
		<option value='Nagaraj'>Nagaraj</option>
		</select></td></tr>
		<tr><td>Bug Status:</td><td><Select name='bugstatus' id='bugstatus'><option></option>
		<option value='Fixed'>Fixed</option><option value='Deferred'>Deferred</option>
		<option value='Closed'>Closed</option><option value='In Progress'>In Progress</option>
		</Select></td></tr>
		<tr><td>Notes:</td><td><textarea rows=20 cols=100 id='notes' name='notes'></textarea></td></tr>
		<tr><td><input type=submit name='submit' value='Enter new event in database'></input></td></tr>
	</table>
	</div>
	</div>
	</div>
</div>
</form>
<?php Include "Display/footer.php";?>
</body>
</html>
