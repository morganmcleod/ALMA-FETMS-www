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

<title>IF Spectrum</title>
</head>
<?php

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.ifspectrumplotter.php');
require_once($site_dbConnect);
require_once($site_FEConfig . '/jsFunctions.php');

$fc = isset($_REQUEST['fc']) ? $_REQUEST['fc'] : '';
$FEid = isset($_REQUEST['fe']) ? $_REQUEST['fe'] : '';
$band = isset($_REQUEST['b']) ? $_REQUEST['b'] : '';
$DataSetGroup = isset($_REQUEST['g']) ? $_REQUEST['g'] : '';
$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
$drawPlots = isset($_REQUEST['d']) ? $_REQUEST['d'] : 0;

$ifSpectrupPlotsLogger = new Logger('ifspectrumplots.php.txt', 'w');

if ($DataSetGroup == '' || $id != '') {
    // Get DataSet Group from testdata key ID
    $q = "SELECT `DataSetGroup`
    FROM `TestData_header`
    WHERE `keyId` = $id";
    $r = @mysql_query($q,$db);

    $DataSetGroup = @mysql_result($r,0,0);
}

if ($id == '') {
    // a non-empty ID is required for the javascript call to createIFSpectrumTabs() below.
    $id = '0';
}

$ifspec = new IFSpectrumPlotter();
$ifspec->Initialize_IFSpectrum($FEid,$DataSetGroup,$fc,$band);

$feconfig = $ifspec->FrontEnd->feconfig_latest;
$fesn = $ifspec->FrontEnd->GetValue('SN');
$ccasn = $ifspec->FrontEnd->ccas[$band]->GetValue('SN');

$title = "IF Spectrum Band $band DataSet $DataSetGroup";

include "header_ifspectrum.php";

if ($drawPlots == 1) {
    $ifspec->CreateNewProgressFile($fc,$DataSetGroup);
//    echo '<script type="text/javascript">window.location="../pbar/status.php?lf=' . $ifspec->progressfile . '";</script>';
}

echo "<body id = 'body3' onload='createIFSpectrumTabs($fc,$id,$FEid,$DataSetGroup,$band);' BGCOLOR='#19475E'>";

echo "<form action='".$_SERVER["PHP_SELF"]."' method='post' name='Submit' id='Submit'>";

if ($drawPlots == 1) {
    $ifspec->GeneratePlots();
}

?>

<div id="content_inside_main2">
    <div id="toolbar" style="margin-top:10px;"></div>
    <div id="tabs1"  ></div>
    <div id="tab_info" class="x-hide-display"></div>
    <div id="tab_spurious" class="x-hide-display"></div>
    <div id="tab_spurious2" class="x-hide-display"></div>
    <div id="tab_pwrvar2" class="x-hide-display"></div>
    <div id="tab_pwrvar31" class="x-hide-display"></div>
    <div id="tab_totpwr" class="x-hide-display"></div>
    <div id="tab_datasets" class="x-hide-display"></div>
    <div id="subtab" class="x-hide-display"></div>
</div>
</body>
</html>
