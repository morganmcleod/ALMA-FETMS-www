<?php
include "../dbConnect.php";

function __autoload($class_name)
{
  require_once '../Queries/'. $class_name . '.php';
}

$addquery=new addQueries;
$genquery=new generalQueries;
$getquery=new getQueries;

if(isset($_POST['submit']))
{
	$subproject=$_POST['swmodule'];

	$assigned_to=$genquery->GetOneWithCriteria(PersonResponsible,TaskSubProjects,keyTaskSubProjects,$subproject);

	$subproject_name=$genquery->GetOneWithCriteria(Name,TaskSubProjects,keyTaskSubProjects,$subproject);

    //escape special characters
	$notes=addslashes($_POST['notes']);
    $bug_description=addslashes($_POST['bugdesc']);

	$BugReportArray=array("ModName"=>$subproject,"AssignedTo"=>$assigned_to,"Description"=>$bug_description,
	"ReportBy"=>$_POST['reporter'],"Date"=>$_POST['dtentered'],"Priority"=>$_POST['priority'],
	"Notes"=>$notes);

	$AddBug_query=$addquery->AddBugReport($BugReportArray);

	$bugkey=$getquery->GetLatestBugEntry();

	if($AddBug_query)
	{
			$email_note=$BugReportArray[Description];
			$page_url="https://safe.nrao.edu/php/ntc/bugs/BugDetails.php?bugkey=$bugkey";
			$page_url=preg_replace("/&/","%26",$page_url);
			if ($BugReportArray[AssignedTo]=='Nagaraj' ||
			    $BugReportArray[AssignedTo]=='Crabtree' ||
			    $BugReportArray[AssignedTo]=='Lacasse' ||
			    $BugReportArray[AssignedTo]=='McLeod')
			{
				$to="mmcleod@nrao.edu";
			}
			else if($BugReportArray[AssignedTo]=='Castro')
			{
				$to="jcastro@nrao.edu";
			}
			else if($BugReportArray[AssignedTo]=='Effland')
			{
				$to="jeffland@nrao.edu";
			}
			if($BugReportArray[Priority]==0)
			{
				$priority="Enhancement";
			}
			else if($BugReportArray[Priority]==1)
			{
				$priority="Critical";
			}
			else if($BugReportArray[Priority]==2)
			{
				$priority="Defect";
			}
			$subject="Bug assignment alert";
			$message="<html>
			<head>
			</head>
			<body>
			 Assigned by: $BugReportArray[ReportBy]
			 <br>
			 Priority: $priority
			 <br>
			 Module Name: $subproject_name
			 <br>
			 Description:<br>
			 ----------------------------------------------------------------------------------------------------------
			 <br>
			 $email_note
			 <br>
			 ----------------------------------------------------------------------------------------------------------
			 <br><br>
			 <a href='$page_url'>click here</a> for more details.
			 </body>
			 </head>";

			$from="mail server"; //function in functions.php
			$headers= "From: $from" . "\r\n";
			$headers .= "Content-type: text/html; charset=iso-8859-1" . "\r\n";
			mail($to,$subject,$message,$headers);
			echo "<script type='text/javascript'>alert('E-mail sent to: $to')</script>";
	}

	echo "<script type='text/javascript' language='JavaScript'>window.location='../ShowBugs.php?developer=\'$assigned_to\'';</script>";
}

?>