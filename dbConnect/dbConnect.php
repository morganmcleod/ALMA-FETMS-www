<?php

switch ($_SERVER['SERVER_NAME']) {
    case "fetms.osf.alma.cl":
        $dbname     = 'fetms';
        $dbserver   = 'localhost';
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

function site_warnProductionDb($dbname) {
    if ($_SERVER['SERVER_NAME'] == 'webtest.cv.nrao.edu') {
        if ($dbname == 'alma_feic') {
            echo "<font size = '+2' color = '#ff0000'><h><b>
            WARNING- Using production alma_feic database! Be careful!
            </b></h></font>";
        } else {
            echo "<font size = '+2' color = '#ff0000'><h><b>
            On webtest.cv.nrao.edu using database $dbname
            </b></h></font>";
        }
    }
}

?>