<?php
$dbname     = 'alma_feic';
$dbserver   = 'sql5.cv.nrao.edu';
$dbusername = 'na_feic';
$dbpassword = 'qSfUO65a';

$db = mysql_pconnect($dbserver, $dbusername, $dbpassword)
OR die ('Could not connect to MySQL: ' . mysql_connect_error() );
mysql_select_db($dbname, $db);

function site_getDbConnection() {
    global $db;
    return $db;
}

?>