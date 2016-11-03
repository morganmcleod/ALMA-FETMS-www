<?php
if (!isset($site_root)) {
    $site_root = dirname(__FILE__);
}

// facility code is part of database keys for the entire application:
$fc = 40;

// code which uses $rootdir_data assumes it has a terminal slash:
$rootdir_data = $site_root . '/';

// the beameff_64 application is deployed to a fixed location in the code:
$beameff_64 = $rootdir_data . "FEConfig/bp/beameff/beameff2_64";

// switch for operating as a CCA database
$FETMS_CCA_MODE = false;

// set up some variables specific to particular hosts:
$site_hostname = $_SERVER['SERVER_NAME'];

switch ($site_hostname){
    case "safe.nrao.edu":
        $rootdir_url = "https://safe.nrao.edu/php/ntc/";
        $GNUplot = $GNUPLOT = '/usr/bin/gnuplot';
        break;

    case "fetms.osf.alma.cl":
        $rootdir_url = "http://fetms.osf.alma.cl/fetms/";
        $GNUplot = $GNUPLOT = '/usr/bin/gnuplot';
        $beameff_64 = $rootdir_data . "FEConfig/bp/beameff/beameff_64";
        break;

    case "webtest2.cv.nrao.edu":
        $rootdir_url = "https://webtest2.cv.nrao.edu/php/ntc/ws-mtm/ALMA-FETMS-www/";
        $GNUplot = $GNUPLOT = '/usr/bin/gnuplot';
        break;

    case "localhost":
    case "band1-fetms":
        $FETMS_CCA_MODE = false;
        $rootdir_url = "http://band1-fetms/ALMA-FETMS-www/";
       	$GNUplot = $GNUPLOT = 'C:/gnuplot/bin/gnuplot.exe';
       	$beameff_64 = "C:/wamp64/www/ALMA-FETMS-beameff/WinExe/beam_eff2.exe";
       	break;

    default:
        echo "<font size = '+3' color = '#ff0000'><h><b>
        This application is not configured for host $site_hostname. <br>
        Add a case in " . __FILE__ . "<br><br>";
        echo "</b></h></font>";
        break;
}

//echo '$site_root=' . $site_root . '<br><br>';
//echo '$rootdir_data=' . $rootdir_data . '<br><br>';
//echo '$rootdir_url=' . $rootdir_url . '<br><br>';
//echo '$beameff_64=' . $beameff_64 . '<br><br>';
//echo '$GNUplot=' . $GNUplot . '<br><br>';

// set up some additional directories and URLs based on the above paths:
$main_write_directory = $rootdir_data . "test_datafiles/";
$main_url_directory   = $rootdir_url  . "test_datafiles/";

$log_write_directory = $main_write_directory . "logs/";
$log_url_directory = $main_url_directory . "logs/";

$cca_write_directory = $rootdir_data . "test_datafiles/";
$cca_url_directory = $rootdir_url    . "test_datafiles/";

$wca_write_directory = $rootdir_data . "test_datafiles/";
$wca_url_directory   = $rootdir_url  . "test_datafiles/";

?>
