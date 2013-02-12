<?php

$dbname     = 'dbSWTasks';
$dbserver   = 'sql5.cv.nrao.edu';
$dbusername = 'cdl_user';
$dbpassword = '34ve49GcxS97DsLK';

$db = mysql_pconnect($dbserver, $dbusername, $dbpassword)
OR die ('Could not connect to MySQL: ' . mysql_connect_error() );
mysql_select_db($dbname, $db);

?>
