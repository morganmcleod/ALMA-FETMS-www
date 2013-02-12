<?php
Class getQueries
{
	function getSoftwareModules()
	{
		//called from MaddNewBug.php
		$modules=mysql_query("SELECT keyTaskSubProjects,Name FROM TaskSubProjects ORDER BY Name")
		or die("Could not get software modules" .mysql_query());
		return $modules;
	}
	function getBugDetails($bugkey)
	{
		//called from MbugDetails.php
		$bugdetails=mysql_query("SELECT Priority,AssignedTo,ReportedBy,DateEntered,Description,Notes,TaskStatus
								FROM Tasks WHERE keyTasks='$bugkey'")
		or die("Could not get data" .mysql_error());
		
		return $bugdetails;
	}
	function getBugTasks($bugkey)
	{
		//called from MbugDetails.php
		$getBugTasks=mysql_query("SELECT TimeStamp,Notes,EntryBy,AssignedPrev,Status FROM TaskEvents WHERE fkTasks='$bugkey' 
								  ORDER BY TimeStamp DESC")
		or die("Could not get tasks" .mysql_error());
		return $getBugTasks;
	}
	function CountBugsAssigned()
	{
		//called from getPieStats.php
		$getBugsAssigned=mysql_query("SELECT AssignedTo,Count(keyTasks) FROM Tasks where DateCompleted is Null 
									  GROUP BY AssignedTo")
		or die("Could not count bugs" .mysql_error());
		return $getBugsAssigned;
	}
	function GetLatestBugEntry()
	{
		//called from CaddNewBug.php.
		$LatestBugKey_query=mysql_query("SELECT MAX(keyTasks) As MaxKey FROM Tasks");
		$LatestBugKey=mysql_result($LatestBugKey_query,0,'MaxKey');
		return $LatestBugKey;
	}
}
?>