<?php

include "dbConnect.php";
include "JSFunctions.php";

global $swmodule_block;

function __autoload($class_name) 
{
    require_once 'Queries/'. $class_name . '.php';
}

global $getquery; 
$getquery=new getQueries;

//get all devices in the database.
$swmodules_query=$getquery->getSoftwareModules();
while($swmodules=mysql_fetch_array($swmodules_query))
{
	$name=$swmodules[Name];
	$key=$swmodules[keyTaskSubProjects];
	$swmodule_block .="<option value=\"$key\">$name</option>";
}

?>