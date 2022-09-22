<?php
/*
 * This script is called from statusscript.js.
 *
 * Argument
 * lf- Base name of an INI file to be read.
 *
 * A PHP script has created an INI file which will be read by this script.
 * The contents of the INI file will be echoed out as XML to be used by statusscript.js.
 *
 * Key values of the INI file are:
 *
 * progress     -Number indicating percentage complete
 * message      -Static message shown throughout the process
 * plotmessage  -Temporary message indicating what specific part of the process is underway
 * image        -Url of the most recently generated plot
 * refurl       -Url to be opened after the process is complete
 * errormessage -Error message
 * abort	    -If this value is 1, then the live status update will halt.
 *
 */


/*
The INI file will be stored in a default directory, and it's base name will be
passed to this script as the argument 'lf'. The default directory is defined in config_main.php.
 */
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_config_main);

$progressfile = $main_write_directory . $_REQUEST['lf'] . ".txt";

//Parse ini file to get contents, and echo out an XML document.
$ini_array      = parse_ini_file($progressfile);
$progress       = $ini_array['progress'];
$pmessage       = str_replace("&", "&amp;", $ini_array['message']);
$mainmessage    = str_replace("&", "&amp;", $ini_array['plotmessage']);
$pimage         = $site_storage . str_replace("&", "&amp;", $ini_array['image']);
$pabort         = $ini_array['abort'];
$perror         = $ini_array['error'];
$perrormsg      = str_replace("&", "&amp;", $ini_array['errormessage']);
$purl           = str_replace("&", "&amp;", $ini_array['refurl']);

header('Pragma: no-cache');
// HTTP/1.1
header('Cache-Control: no-cache, must-revalidate');
// date in the past
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
// define XML content type
header('Content-type: text/xml');
// print XML header
print '<?xml version="1.0"?>';

?>
<DOCUMENT>
    <PROGRESS> <?php print $progress;     ?></PROGRESS>
    <PMESSAGE> <?php print $pmessage;     ?></PMESSAGE>
    <MAINMESSAGE> <?php print $mainmessage; ?></MAINMESSAGE>
    <PIMAGE> <?php print $pimage;       ?></PIMAGE>
    <PERROR> <?php print $perror;       ?></PERROR>
    <PERRORMSG> <?php print $perrormsg;    ?></PERRORMSG>
    <PURL> <?php print $purl;         ?></PURL>
    <PABORT> <?php print $pabort;      ?></PABORT>
</DOCUMENT>