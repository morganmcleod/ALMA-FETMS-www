<?php
include "../dbConnect.php";

function __autoload($class_name) 
{
    require_once 'Queries/'. $class_name . '.php';
}

$getqueries=new getQueries;

$count_bugs_assigned_query=$getqueries->CountBugsAssigned();

while($result=mysql_fetch_object($count_bugs_assigned_query))
{
	$numbugs[]=$result;
}

echo json_encode($numbugs);