<?php

$mySQL57 = false;

switch ($_SERVER['SERVER_NAME']) {
    case "fetms.osf.alma.cl":
    	// database credentials are kept in the /conf/ directory, not in the webserver document root:
    	require_once("/home/fetms.osf.alma.cl/conf/fetms-dbConnect.conf");
        break;

    case "webtest.cv.nrao.edu":
    case "webtest2.cv.nrao.edu":    // webtest2 is just a temporary name.  will revert back to webtest soon.
        // database credentials are kept in the /conf/ directory, not in the webserver document root:
        require_once("/home/webtest.cv.nrao.edu/conf/mtm-dbConnect.conf");
        break;

    case "safe.nrao.edu":
        // database credentials are kept in the /conf/ directory, not in the webserver document root:
        require_once("/home/safe.nrao.edu/conf/mtm-dbConnect.conf");
        break;

	case "band1-fetms":
	    $mySQL57 = true;
	    include("C:/wamp64/dbConnect_private.php");
	    break;

    case "localhost":
    case "finch":
        $mySQL57 = true;
//         include("C:/wamp64/dbConnect_OSF.php");
        include("C:/wamp64/dbConnect_private.php");
        break;
         
    default:
        die ("Unknown database credentials for server'" . $_SERVER['SERVER_NAME'] . "'");
}

$db = mysql_pconnect($dbserver, $dbusername, $dbpassword);
if (!$db) {
    echo "<font size='+2' color='#ff0000' face='serif'><h><b>";
    die('Could not connect to MySQL: ' . mysql_error());
}

$db_selected = mysql_select_db($dbname, $db);
if (!$db_selected) {
    echo "<font size='+2' color='#ff0000' face='serif'><h><b>";
    die ('Accessing database(' .$dbname . ") causes the following error:<br>" . mysql_error());
}

if ($mySQL57) {
    // workaround for MySQL 5.7, this application only:
    $q = "SET sql_mode=''";
    $r = @mysql_query($q, $db);
}

function site_getDbConnection() {
    global $db;
    return $db;
}

function site_warnProductionDb($dbname) {
    $server = $_SERVER['SERVER_NAME'];

    if ($server == 'webtest.cv.nrao.edu' || $server == 'webtest2.cv.nrao.edu') {
        echo "<font size='+2' color='#ff0000' face='serif'><h><b>
        On $server using database $dbname
        </b></h></font>";
    }
}

?>
