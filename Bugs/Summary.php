<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<link rel="stylesheet" type="text/css" href="Display/style.css">
<title>Summary</title>
</head>
<body>

<?php
include('Display/Header.php');
include('dbConnect.php');
include('class.generictable.php');

$AssignedTo = '%';
if (isset($_REQUEST['AssignedTo'])){
	$AssignedTo  = $_REQUEST['AssignedTo'];
}

echo '<form action="' . $PHP_SELF . '" method="POST">';
echo "Assigned To: <select name='AssignedTo' onChange=submit()>";

echo "<option value = '%'  selected = 'selected'>Show All</option>";

$q = "SELECT DISTINCT(AssignedTo) FROM Tasks ORDER BY AssignedTo ASC;";
$r = @mysql_query($q,$db);
while($row = @mysql_fetch_array($r)){
	if ($row[0] == $AssignedTo){
		echo "<option value = '$row[0]' selected = 'selected'>$row[0]</option>";
	}
	else{
		echo "<option value = '$row[0]'>$row[0]</option>";
	}
}
echo "</select>";
echo "</form><br><br>";



$qSubTasks = "SELECT keyTaskSubProjects, Name FROM TaskSubProjects ORDER BY Name ASC;";

$rSubTasks = @mysql_query($qSubTasks,$db);

while ($rowST = @mysql_fetch_array($rSubTasks)){
	$qTask = "SELECT keyTasks FROM Tasks WHERE fkSubProject = $rowST[0] 
	AND ((TaskStatus is null) or (TaskStatus <> 'Closed'))
	AND AssignedTo LIKE '$AssignedTo'
	ORDER BY AssignedTo ASC, TimeStamp DESC;";
	$rTask = @mysql_query($qTask,$db);
	
	if (@mysql_numrows($rTask) > 0){
	echo "<a href='#$rowST[0]'>" . $rowST[1] . "</a><br>";
	}
}

$rSubTasks = @mysql_query($qSubTasks,$db);
while ($rowST = @mysql_fetch_array($rSubTasks)){
	$subtask = new GenericTable();
	
	$subtask->Initialize('TaskSubProjects',$rowST[0],'keyTaskSubProjects');
	
	
	$qTask = "SELECT keyTasks FROM Tasks WHERE fkSubProject = $rowST[0] 
	AND ((TaskStatus is null) or (TaskStatus <> 'Closed'))
	AND AssignedTo LIKE '$AssignedTo' 
	ORDER BY AssignedTo ASC, TimeStamp DESC;";
	$rTask = @mysql_query($qTask,$db);
	
	if (@mysql_numrows($rTask) > 0){
	
	echo "<div style='width:1000px'>";
	
	echo "<br><a name='$subtask->keyId'></a>";
	echo "<table id='table1'>";
	echo "<tr class = 'alt'><th colspan='7'>" . $subtask->GetValue('Name') . "</th></tr>";
	echo "<tr><th width='40px'>Task # </th>
	      <th>Assigned To</th>
	      <th>Reported By</th>
	      <th>Date Entered</th>
	      <th>Status</th>
	      <th>Description</th>
	      <th>Notes</th>
	      
	      
	      
	      ";
	
	
	
	
	$rowcount = 1;
	while ($rowTask = @mysql_fetch_array($rTask)){
		$TaskID = $rowTask[0];
		$Task = new GenericTable();
		$Task->Initialize('Tasks',$TaskID,'keyTasks');
		
		$bg = "class = 'alt'";
		if ($rowcount % 2){
			$bg = "";
		}
		
		echo "<tr $bg>";
		
		$url = "BugDetails.php?bugkey=$Task->keyId";
		echo "<td width='40px'><a href='$url' target='blank'>". $Task->keyId ."</a></td>";
		echo "<td width='60px'>". $Task->GetValue('AssignedTo') ."</td>";
		echo "<td width='60px'>". $Task->GetValue('ReportedBy') ."</td>";
		echo "<td width='100px'>". $Task->GetValue('DateEntered') ."</td>";
		echo "<td width='40px'>". $Task->GetValue('TaskStatus') ."</td>";
		echo "<td width='240px'>". $Task->GetValue('Description') ."</td>";
		echo "<td width='300px'>". nl2br($Task->GetValue('Notes')) ."</td></tr>";
		
		echo "</tr>
		<tr class='alt2'><th colspan='7'></th></tr>
		";
		unset($Task);
		$rowcount+=1;
		
	}
	
	
	echo "</table></div>";
	}
	
	
	unset ($subtask);
	
	
}







?>