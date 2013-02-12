<?php
Class addQueries
{
	function AddBugReport($BugReportArray)
	{
		//called from CaddNewBug.php
		$addBugs=mysql_query("INSERT INTO Tasks(fkSubProject,DateEntered,AssignedTo,ReportedBy,Priority,Description,Notes,PriorityFlagSet)
							  VALUES('$BugReportArray[ModName]','$BugReportArray[Date]','$BugReportArray[AssignedTo]','$BugReportArray[ReportBy]',
							  '$BugReportArray[Priority]','$BugReportArray[Description]','$BugReportArray[Notes]','1')")
		or die("Could not insert data" .mysql_error());
		return $addBugs;
	}
	
	function AddBugNotes($BugNoteArray)
	{
		//called from CaddBugNotes.php
		if($BugNoteArray[Status] == "Fixed" or $BugNoteArray[Status] == "Closed")
		{
			$notes="$BugNoteArray[EnteredBy] " . $BugNoteArray[Status] . " this bug: " . $BugNoteArray[Notes]; 
			
			$addBugNotes=mysql_query("INSERT INTO TaskEvents(fkTasks,DateUpdated,EntryBy,Status,Notes,DateCompleted)
			VALUES('$BugNoteArray[fkTasks]','$BugNoteArray[TS]','$BugNoteArray[EnteredBy]','$BugNoteArray[Status]',
			'$notes','$BugNoteArray[TS]')");	
			
			$updateBugNotes=mysql_query("UPDATE Tasks SET DateCompleted='$BugNoteArray[TS]' WHERE keyTasks='$BugNoteArray[fkTasks]' AND DateCompleted IS NULL");
		}
		else 
		{
			$addBugNotes=mysql_query("INSERT INTO TaskEvents(fkTasks,DateUpdated,EntryBy,Status,Notes)
			VALUES('$BugNoteArray[fkTasks]','$BugNoteArray[TS]','$BugNoteArray[EnteredBy]','$BugNoteArray[Status]',
			'$BugNoteArray[Notes]')");
		}
		if (!empty($BugNoteArray[Status]))
		{
			$updateBugNotes=mysql_query("UPDATE Tasks SET Priority='$BugNoteArray[Priority]',
			TaskStatus='$BugNoteArray[Status]' WHERE keyTasks='$BugNoteArray[fkTasks]'");
		}
		else
		{
			$updateBugNotes=mysql_query("UPDATE Tasks SET Priority='$BugNoteArray[Priority]' WHERE keyTasks='$BugNoteArray[fkTasks]'");
		}		
		return $updateBugNotes;
	}
	function AddBugNotes_with_AssignedPreviously($BugNoteArray)
	{
		//called from CaddBugNotes.php
		if($BugNoteArray[Status] != "Fixed")
		{
			$notes="$BugNoteArray[EnteredBy] changed task assignment from $BugNoteArray[AssignedTo_Previously] to $BugNoteArray[AssignedTo]. " . $BugNoteArray[Notes];
			$addBugNotes=mysql_query("INSERT INTO TaskEvents(fkTasks,DateUpdated,EntryBy,Status,Notes,AssignedPrev)
			VALUES('$BugNoteArray[fkTasks]','$BugNoteArray[TS]','$BugNoteArray[EnteredBy]','$BugNoteArray[Status]',
			'$BugNoteArray[Notes]','$BugNoteArray[AssignedTo_Previously]')");
		}
		else
		{
			$notes="$BugNoteArray[EnteredBy] fixed this bug. " . $BugNoteArray[Notes];
			$addBugNotes=mysql_query("INSERT INTO TaskEvents(fkTasks,DateUpdated,EntryBy,Status,Notes,AssignedPrev,
			DateCompleted)
			VALUES('$BugNoteArray[fkTasks]','$BugNoteArray[TS]','$BugNoteArray[EnteredBy]','$BugNoteArray[Status]',
			'$notes','$BugNoteArray[AssignedTo_Previously]','$BugNoteArray[TS]')");
			
			$updateBugNotes=mysql_query("UPDATE Tasks SET DateCompleted='$BugNoteArray[TS]' WHERE keyTasks='$BugNoteArray[fkTasks]'");
		}
		$updateBugNotes=mysql_query("UPDATE Tasks SET Priority='$BugNoteArray[Priority]',
		TaskStatus='$BugNoteArray[Status]',AssignedTo='$BugNoteArray[AssignedTo]' WHERE keyTasks='$BugNoteArray[fkTasks]'");
		
		return $updateBugNotes;
	}
}
?>