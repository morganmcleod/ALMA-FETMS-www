<?php

$mySQL57 = true;

switch ($_SERVER['SERVER_NAME']) {
    case "fetms.osf.alma.cl":
    	// database credentials are kept in the /conf/ directory, not in the webserver document root:
    	require_once("/home/fetms.osf.alma.cl/conf/fetms-dbConnect.conf");
        break;

    case "webtest.cv.nrao.edu":
    case "webtest2.cv.nrao.edu":    // webtest2 is just a temporary name.  will revert back to webtest soon.
        // database credentials are kept in the /conf/ directory, not in the webserver document root:
        require_once("/home/webtest.cv.nrao.edu/conf/mtm-dbConnect.conf");
//         $dbname = 'alma_b1_fetms';
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
    case "junco":
//         include("C:/wamp64/dbConnect_band1.php");
//         include("C:/wamp64/dbConnect_private.php");
        include("C:/wamp64/dbConnect_OSF.php");
        $mySQL57 = true;
        break;

    default:
        die ("Unknown database credentials for server'" . $_SERVER['SERVER_NAME'] . "'");
}

$link = mysqli_connect($dbserver, $dbusername, $dbpassword);
if (!$link) {
    echo "<font size='+2' color='#ff0000' face='serif'><h><b>";
    die('Could not connect to MySQL: ' . ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
} 

$db_selected = mysqli_select_db($link, $dbname);
if (!$db_selected) {
    echo "<font size='+2' color='#ff0000' face='serif'><h><b>";
    die ('Accessing database(' .$dbname . ") " . ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
}

if ($mySQL57) {
    // workaround for MySQL 5.7, this application only:
    $q = "SET sql_mode=''";
    $r = mysqli_query($link, $q);
}

function site_getDbConnection() {
    global $link;
    return $link;
}

function site_warnProductionDb($dbname) {
    $server = $_SERVER['SERVER_NAME'];
    if ($server == 'localhost' || $server == 'junco' ||
        $server == 'webtest.cv.nrao.edu' || $server == 'webtest2.cv.nrao.edu')
    {
        echo "<font size='+2' color='#ff0000' face='serif'><h><b>
        On $server using database $dbname
        </b></h></font>";
    }
}

function ADAPT_mysqli_result($res, $row, $field=0) {
    $res->data_seek($row);
    $datarow = $res->fetch_array();
    return $datarow[$field];
} 

?>
