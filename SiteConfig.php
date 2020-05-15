<?php

date_default_timezone_set ('America/New_York');
ini_set('display_errors', '1');
ini_set('memory_limit', '384M');  // needed for IF spectrum plotting.
$errorReportSettingsNo_E_NOTICE = E_ERROR | E_WARNING | E_PARSE;
$errorReportSettingsNormal = $errorReportSettingsNo_E_NOTICE | E_NOTICE;
error_reporting($errorReportSettingsNormal);
set_time_limit(1800);     // ridiculously long = 30 minutes!

// site_root defaults to the server's document root, but that won't work on any NRAO servers:
$files_root = dirname(__FILE__);
$site_classes = $files_root . "/classes";
$site_libraries = $files_root . "/libraries";
$site_FEConfig = $files_root . "/FEConfig";
$site_config_main = $files_root . "/config_main.php";
$site_dbConnect = $files_root . "/dbConnect/dbConnect.php";
$site_dBcode = $files_root . "/dBcode";

// function to get the path to the classes directory:
function site_get_classes() {
    global $site_classes;
    return $site_classes;
}

// function to get the path to the config_main file:
function site_get_config_main() {
    global $site_config_main;
    return $site_config_main;
}

?>
