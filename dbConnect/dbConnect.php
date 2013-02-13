<?php

switch ($site_hostname){
    case "fetms.osf.alma.cl":
        $dbname     = 'fetms';
        $dbserver   = 'fetms.osf.alma.cl';
        $dbusername = 'fetms';
        $dbpassword = '!fetms';
        break;

    default:
        $dbname     = 'alma_feic';
        $dbserver   = 'sql5.cv.nrao.edu';
        $dbusername = 'na_feic';
        $dbpassword = 'qSfUO65a';
        break;
}

$db = mysql_pconnect($dbserver, $dbusername, $dbpassword)
OR die ('Could not connect to MySQL: ' . mysql_connect_error() );
mysql_select_db($dbname, $db);

function site_getDbConnection() {
    global $db;
    return $db;
}

?>