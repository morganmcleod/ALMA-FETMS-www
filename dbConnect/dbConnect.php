<?php

$mySQL57 = true;

switch ($_SERVER['SERVER_NAME']) {
    case getenv('PHP_HOSTNAME'):
    case "localhost":
        // database credentials are kept in the /conf/ directory, not in the webserver document root:
        // include("/home/fetms/conf/fetms-dbConnect.php");
        $dbname     = 'fetms';
        $dbserver   = 'host.docker.internal';
        $port       = 33306;
        $dbusername = 'fetms';
        $dbpassword = 'DhFNC6D3@8uE7X!';
        break;

    case "fetms-rhel8.osf.alma.cl":
        // database credentials are kept in the /conf/ directory, not in the webserver document root:
        include("/home/fetms.osf.alma.cl/conf/fetms-dbConnect.conf");
        break;

    case "200.2.0.121":
        // database credentials are kept in the /conf/ directory, not in the webserver document root:
        include("/home/fetms.osf.alma.cl/conf/fetms-dbConnect.conf");
        break;
    
    case "webtest.cv.nrao.edu":
        // database credentials are kept in the /conf/ directory, not in the webserver document root:
        include("/home/webtest.cv.nrao.edu/conf/mtm-dbConnect.conf");
        //         $dbname = 'alma_b1_fetms';
        break;

    case "safe.nrao.edu":
        // database credentials are kept in the /conf/ directory, not in the webserver document root:
        include("/home/safe.nrao.edu/conf/mtm-dbConnect.conf");
        break;

    case "band1-fetms":
        $mySQL57 = true;
        include("C:/wamp64/dbConnect_private.php");
        break;

    default:
        die("Unknown database credentials for server'" . $_SERVER['SERVER_NAME'] . "'");
}

$dbConnection = mysqli_connect($dbserver, $dbusername, $dbpassword, $dbname, $port);
if (!$dbConnection) {
    echo "<font size='+2' color='#ff0000' face='serif'><h><b>";
    die('Could not connect to MySQL: ' . ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
}

$db_selected = mysqli_select_db($dbConnection, $dbname);
if (!$db_selected) {
    echo "<font size='+2' color='#ff0000' face='serif'><h><b>";
    die('Accessing database(' . $dbname . ") " . ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
}

if ($mySQL57) {
    // workaround for MySQL 5.7, this application only:
    $q = "SET sql_mode=''";
    $r = mysqli_query($dbConnection, $q);
}

function site_getDbConnection() {
    global $dbConnection;
    return $dbConnection;
}

function site_warnProductionDb($dbname, $dbserver, $port) {
    $server = $_SERVER['SERVER_NAME'];
    if (
        $server == 'localhost' || $server == 'erl13' ||
        $server == 'webtest.cv.nrao.edu' || $server == 'webtest2.cv.nrao.edu'
    ) {
        echo "<font size='+2' color='#ff0000' face='serif'><h><b>
        On $server using database $dbname on host $dbserver:$port
        </b></h></font>";
    }
}

function ADAPT_mysqli_result($res, $row, $field = 0) {
    if (!$res) return FALSE;
    if (!mysqli_num_rows($res)) return FALSE;
    if (!mysqli_data_seek($res, $row)) return FALSE;
    $datarow = mysqli_fetch_array($res);
    if (!$datarow) return FALSE;
    return $datarow[$field];
}

?>
