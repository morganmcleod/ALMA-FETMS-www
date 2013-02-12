<?php

$dbname     = 'na_feic';
$dbserver   = 'sql5.cv.nrao.edu';
$dbusername = 'na_feic';
$dbpassword = 'qSfUO65a';

$dbc = mysql_connect($dbserver, $dbusername, $dbpassword)
or die ('Could not connect to MySQL: ' . mysql_connect_error() );
mysql_select_db($dbname, $dbc);

?>