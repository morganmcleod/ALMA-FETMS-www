<?php
$files_root = dirname(__FILE__);
$url_root = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/';

// facility code is part of database keys for the entire application:
$fc = 40;

// code which uses $rootdir_data assumes it has a terminal slash:
$rootdir_data = $files_root . '/';

// the beameff_64 application is deployed to a fixed location in the code:
$beameff_64 = $rootdir_data . "FEConfig/bp/beameff/beameff2_64";

// switch for operating as a CCA database
$FETMS_CCA_MODE = false;

// set up some variables specific to particular hosts:
$site_hostname = $_SERVER['SERVER_NAME'];

switch ($site_hostname) {
    case getenv('PHP_HOSTNAME'):
        $url_root .= "fetms/";
        $GNUplot = $GNUPLOT = '/usr/bin/gnuplot';
        $beameff_64 = $rootdir_data . "FEConfig/bp/beameff/beameff2_64";
        break;

    case "fetms-rhel8.osf.alma.cl":
        $url_root .= "fetms/";
        $GNUplot = $GNUPLOT = '/usr/bin/gnuplot';
        $beameff_64 = $rootdir_data . "FEConfig/bp/beameff/beameff2_64";
        break;

    case "webtest.cv.nrao.edu":
        // $FETMS_CCA_MODE = true;
        $url_root .= "php/ntc/ws-mtm/ALMA-FETMS-www/";
        $GNUplot = $GNUPLOT = '/usr/bin/gnuplot';
        $GNUPLOT_VER = 4.9;
        break;

    case "band1-fetms":
        $FETMS_CCA_MODE = true;
        $url_root .= "ALMA-FETMS-www/";
        $GNUplot = $GNUPLOT = 'C:/gnuplot/bin/gnuplot.exe';
        $beameff_64 = "C:/wamp64/www/ALMA-FETMS-beameff/WinExe/beam_eff2.exe";
        break;

    case "localhost":
        $url_root .= "";
        $GNUplot = $GNUPLOT = '/usr/bin/gnuplot';
        break;
    case "junco":
        $FETMS_CCA_MODE = false;
        $url_root .= "ALMA-FETMS-www/";
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

// echo '$files_root=' . $files_root . '<br><br>';
// echo '$rootdir_data=' . $rootdir_data . '<br><br>';
// echo '$url_root=' . $url_root . '<br><br>';
// echo '$beameff_64=' . $beameff_64 . '<br><br>';
// echo '$GNUplot=' . $GNUplot . '<br><br>';

// set up some additional directories and URLs based on the above paths:
$main_write_directory = $rootdir_data . "test_datafiles/";
$main_url_directory   = $url_root  . "test_datafiles/";

$log_write_directory = $main_write_directory . "logs/";
$log_url_directory = $main_url_directory . "logs/";

$cca_write_directory = $rootdir_data . "test_datafiles/";
$cca_url_directory = $url_root    . "test_datafiles/";

$wca_write_directory = $rootdir_data . "test_datafiles/";
$wca_url_directory   = $url_root  . "test_datafiles/";
