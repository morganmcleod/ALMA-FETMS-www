<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require(site_get_config_main());
require_once($site_classes . '/IFSpectrum/IFSpectrum_impl.php');
require_once($site_dbConnect);

$fc = isset($_REQUEST['fc']) ? $_REQUEST['fc'] : '';
$FEid = isset($_REQUEST['fe']) ? $_REQUEST['fe'] : '';
$band = isset($_REQUEST['b']) ? $_REQUEST['b'] : '';
$dataSetGroup = isset($_REQUEST['g']) ? $_REQUEST['g'] : '';
$TDHid = isset($_REQUEST['id']) ? $_REQUEST['id'] : '0';
$drawPlots = isset($_REQUEST['d']) ? $_REQUEST['d'] : 0;

// Make a new IF Spectrum object
$ifspec = new IFSpectrum_impl();
$ifspec -> Initialize_IFSpectrum($FEid, $band, $dataSetGroup, $TDHid);

// Use $dataSetGroup from the IFSpectrum_impl if we don't have it yet.
if (!$dataSetGroup)
    $dataSetGroup = $ifspec->getDataSetGroup();

if ($drawPlots) {
    // If drawing plots, create a file for the progress page to use:
    $ifspec->CreateNewProgressFile();

    // Force a redirect to the progress page
    header('Connection: close');
    ob_start();

    /* close out the server process, release to the client */
    header ('Content-Length: 0');
    header("location: $rootdir_url/FEConfig/pbar/status.php?lf=" . $ifspec -> getProgressFile());
    ob_end_flush();
    flush();

    /* end the forced redirect and continue with this script process */
    ignore_user_abort(true);

    // Do the work of generating plots:
    $ifspec -> GeneratePlots();

    // wait a bit before deleting the progress file:
    sleep(20);

    $ifspec -> DeleteProgressFile();
    exit();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">

<link rel="stylesheet" type="text/css" href="../tables.css">
<link rel="stylesheet" type="text/css" href="../buttons.css">

<link href="../images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
<link type="text/css" href="../../ext/resources/css/ext-all.css" media="screen" rel="Stylesheet" />
<link type="text/css" href="../Cartstyle.css" media="screen" rel="Stylesheet" />

<script src="../../ext/adapter/ext/ext-base.js" type="text/javascript"></script>
<script src="../../ext/ext-all.js"              type="text/javascript"></script>
<script src="loadIFSpectrum.js"                 type="text/javascript"></script>
<script src="../spin.js"                        type="text/javascript"></script>

<link rel="stylesheet" type="text/css" href="../headerbuttons.css">

<?php
require_once($site_FEConfig . '/jsFunctions.php');

$title = "Band $band - IF Spectrum - DataSet $dataSetGroup ";

echo "<title>$title</title>";
echo "</head>";

echo "<body id = 'body2' onload='createIFSpectrumTabs($fc, $TDHid, $FEid, $dataSetGroup, $band);' BGCOLOR='#19475E'>";

include "header_ifspectrum.php";

echo "<form action='".$_SERVER["PHP_SELF"]."' method='post' name='Submit' id='Submit'>";
?>

<div id="content_inside_main2">
	<div id="toolbar" style="margin-top:10px;"></div>
    <div id="tabs1"></div>
    <div id="tab_info" class="x-hide-display"></div>
    <div id="tab_spurious" class="x-hide-display"></div>
    <div id="tab_spurious2" class="x-hide-display"></div>
    <div id="tab_pwrvar2" class="x-hide-display"></div>
    <div id="tab_pwrvar31" class="x-hide-display"></div>
    <div id="pwrvarfullband" class="x-hide-display"></div>
    <div id="tab_totpwr" class="x-hide-display"></div>
</div>
</body>
</html>
