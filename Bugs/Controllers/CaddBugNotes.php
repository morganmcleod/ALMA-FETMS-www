<?php

include "../dbConnect.php";
//include()

function __autoload($class_name)
{
    require_once '../Queries/'. $class_name . '.php';
}

$addquery=new addQueries;
$genquery=new generalQueries;


if(isset($_POST['submit']))

{
	$assignedto_previously=$genquery->GetOneWithCriteria(AssignedTo,Tasks,keyTasks,$_POST['bugkey']);

    //escape special characters in notes
    $notes=addslashes($_POST['notes']);

	$BugNoteArray=array("fkTasks"=>$_POST['bugkey'],"EnteredBy"=>$_POST['reporter'],"TS"=>$_POST['dtentered'],
	"Priority"=>$_POST['priority'],"AssignedTo"=>$_POST['sweng'],"Status"=>$_POST['bugstatus'],
	"Notes"=>$notes,"AssignedTo_Previously"=>$assignedto_previously,"OriginalReporter"=>$_POST['originalreporter'],"OriginalNotes"=>$_POST['originalnotes']);


	if($BugNoteArray[AssignedTo] != "")
	{
		$AddBugNotes_query=$addquery->AddBugNotes_with_AssignedPreviously($BugNoteArray);
	}
	else
	{
		$AddBugNotes_query=$addquery->AddBugNotes($BugNoteArray);
	}


	echo "TEST<br><br>";

	echo $_POST['sweng'];

	switch (strtolower($_POST['sweng'])){
		case 'effland':
			$AssignedToEmail = 'jeffland@nrao.edu';
			break;
		case 'castro':
			$AssignedToEmail = 'jcastro@nrao.edu';
			break;
		case 'mcleod':
			$AssignedToEmail = 'mmcleod@nrao.edu';
			break;
		case 'crabtree':
			$AssignedToEmail = 'mmcleod@nrao.edu';
			break;
	}
	EmailSubmitter($BugNoteArray, $AssignedToEmail);
	echo "<script type='text/javascript' language='JavaScript'>window.location='../BugDetails.php?bugkey=$BugNoteArray[fkTasks]';</script>";

}


function EmailSubmitter($BugNoteArray, $AssignedToEmail){

	$submitters = array('effland','mcleod','gaines','meadows','shannon','castro','crady');
	$emails= array('jeffland@nrao.edu','mmcleod@nrao.edu','egaines@nrao.edu','jmeadows@nrao.edu','mshannon@nrao.edu','jcastro@nrao.edu','kcrady@nrao.edu');

	switch ($BugNoteArray[Priority]){
		case 0:
			$priority = "Enhancement";
			break;
		case 1:
			$priority = "Critical";
			break;
		case 2:
			$priority = "Defect";
			break;
		default:
			$priority = "Critical";

	}
	$taskstatus = $BugNoteArray[Status];
	$page_url= "https://safe.nrao.edu/php/ntc/bugs/BugDetails.php?bugkey=" . $BugNoteArray[fkTasks];



	for ($i=0;$i<count($submitters);$i++) {

		$x = strpos(strtolower($BugNoteArray[OriginalReporter]),$submitters[$i]);

		echo $BugNoteArray[OriginalReporter] . "<br>";
		echo $submitters[$i] . "<br>";

		if (strpos(strtolower($BugNoteArray[OriginalReporter]),$submitters[$i]) > -1){
			$subject="Action taken on submitted bug report. Status: $taskstatus";
				$message="<html>
				<head>
				</head>
				<body>
				 Date Assigned:  " . $BugNoteArray[TS] . "<br>
				 Assigned by: $BugNoteArray[OriginalReporter]
				 <br>
				 Priority: $priority
				 <br>
				 ----------------------------------------------------------------------------------------------------------
				 <br>
				 $BugNoteArray[OriginalReporter] wrote: " . nl2br(stripslashes(stripslashes($BugNoteArray[OriginalNotes]))) . "
				 <br><br>
				 $BugNoteArray[EnteredBy] wrote: " . nl2br(stripslashes(stripslashes($BugNoteArray[Notes]))) . "
				 <br><br>
				 Status: $taskstatus<br>
				 ----------------------------------------------------------------------------------------------------------
				 <br><br>
				 Click here for more details: <a href='$page_url'>$page_url</a>
				 </body>
				 </head>";

				$from="mail server"; //function in functions.php
				$headers= "From: $from" . "\r\n";
				$headers .= "Content-type: text/html; charset=iso-8859-1" . "\r\n";

				//email submitter
				mail($emails[$i],$subject,$message,$headers);
				//email the person assigned the bug
				mail($AssignedToEmail,"Bug $BugNoteArray[fkTasks] assigned to $BugNoteArray[AssignedTo]",$message,$headers);
		}
	}
}

?>

