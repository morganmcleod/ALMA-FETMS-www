<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<link rel="stylesheet" type="text/css" href="Display/style.css">

<title>Bug Details</title>
</head>
<body>
<?php 
Include "Display/Header.php";
include('class.generictable.php');

$bug = new GenericTable();
$bug->Initialize('Tasks',$bugkey,'keyTasks');
$project = new GenericTable();
$project->Initialize('TaskSubProjects',$bug->GetValue('fkSubProject'),'keyTaskSubProjects');


?>
<div style='width:1200px'>
	
	
	<br><br>

	<table id='table1'>
		<tr class='alt'>
			<th colspan ='5'>Project: <?php echo $project->GetValue('Name'); ?> </th>
		</tr>
		<tr>
			<th style="width:30%;">Description</th>
			<th style="width:5%;" >Responsible/ Entered By</th>
			<th style="width:10%;">Entered On</th>
			<th style="width:45%;">Notes</th>
			<th style="width:10%;">Status</th>
		</tr>
<?php while($d=mysql_fetch_array($MainBugDetails))
	  {
	  	$desc=$d['Description'];
	  	$reported_by=$d['ReportedBy'];
	  	$assignedto=$d['AssignedTo'];
	  	$date_entered=$d['DateEntered'];
	  	$notes=$d['Notes'];
	  	$status=$d['TaskStatus'];
	  	
	  	echo "<tr>
	  	      	<td>$desc</td>
	  	      	<td>$assignedto/ $reported_by</td>
	  	      	<td>$date_entered</td>
	  	      	<td bgcolor='#ffffff' style='text-align:left'>".nl2br(stripslashes($notes))."</td>
	  	      	<td>$status</td>
	  	      </tr>";

	  }
?>
	</table>
	<br><br><br>
	<table id='table1'>
	<tr class='alt'><th colspan='4'>Task History <input type=button name='addnotes' id='addnotes' value="Add Notes" onClick="location.href='AddBugNotes.php?bugkey=<?php echo $bugkey; ?>';"></th></tr>
	<tr><th style="width:10%;">Date</th><th style="width:20%">Activity</th><th style="width:30%;">Notes</th>
	<th style="width:10%">Status</th></tr>
	<?php while($m=mysql_fetch_array($BugTasks))
		  {
		  	$date=$m['TimeStamp'];
		  	$subnote=$m['Notes'];
		  	$entryby=$m['EntryBy'];
		  	$assignedprev=$m['AssignedPrev'];
		  	$DateCompleted=$m['DateCompleted'];
		  	$status=$m['Status'];
		  	
		  	if($assignedprev != NULL)
		  	{
		  		$activity="$entryby changed task assignment from $assignedprev.";
		  	}
		  	else if($status == "Fixed")
		  	{
		  		$activity="$entryby fixed bug.";
		  	}
		  	else
		  	{
		  		$activity="$entryby added notes.";
		  	}
		  	
		  	echo "<tr><td>$date</td><td>$activity</td><td style='text-align:left' bgcolor='#ffffff'>".nl2br(stripslashes($subnote))."</td>
		  	<td>$status</td></tr>";
		  }
		
	?>
	</table>
	

</div>
</body>
</html>