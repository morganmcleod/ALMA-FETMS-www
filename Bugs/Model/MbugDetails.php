<?php
include "dbConnect.php";
include "JSFunctions.php";

global $MainBugDetails,$BugTasks,$bugkey;

$bugkey=$_GET['bugkey'];

function __autoload($class_name) 
{
    require_once 'Queries/'. $class_name . '.php';
}

$getquery=new getQueries;

$MainBugDetails=$getquery->getBugDetails($bugkey);

$BugTasks=$getquery->getBugTasks($bugkey);

?>