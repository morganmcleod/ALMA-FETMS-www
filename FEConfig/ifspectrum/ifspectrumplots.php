<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">

<link rel="stylesheet" type="text/css" href="../tables.css">
<link rel="stylesheet" type="text/css" href="../buttons.css">

<link href="../images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
<link type="text/css" href="../../ext/resources/css/ext-all.css" media="screen" rel="Stylesheet" />
<link type="text/css" href="../Cartstyle.css" media="screen" rel="Stylesheet" />

<!-- script src="../jQuery.js"                      type="text/javascript"></script -->
<script src="../../ext/adapter/ext/ext-base.js" type="text/javascript"></script>
<script src="../../ext/ext-all.js"              type="text/javascript"></script>
<script src="loadIFSpectrum.js"                 type="text/javascript"></script>
<script src="../spin.js"                        type="text/javascript"></script>

<!--  script type="text/javascript" src="Ext.ux.plugins.HeaderButtons.js"></script -->

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

//     $ifSpectrupPlotsLogger -> WriteLogFile('Got DataSetGroup using TDHid.');
}

if ($id == '') {
    // a non-empty ID is required for the javascript call to createIFSpectrumTabs() below.
    $id = '0';
}

// $ifSpectrupPlotsLogger -> WriteLogFile('fc=' . $fc . ' FEid=' . $FEid . ' band=' . $band . ' group=' . $DataSetGroup . ' TDHid=' .$id);

$ifspec = new IFSpectrumPlotter();
$ifspec->Initialize_IFSpectrum($FEid,$DataSetGroup,$fc,$band);

$feconfig = $ifspec->FrontEnd->feconfig->keyId;
$fesn = $ifspec->FrontEnd->GetValue('SN');

// $ifSpectrupPlotsLogger -> WriteLogFile('feconfig=' . $feconfig . ' fesn=' . $fesn);

$title = "IF Spectrum Band $band DataSet $DataSetGroup";
include "header_ifspectrum.php";

echo "<body id = 'body3' onload='createIFSpectrumTabs($fc,$id,$FEid,$DataSetGroup,$band);' BGCOLOR='#19475E'>";

if ($drawPlots == 1) {
	$ifspec->CreateNewProgressFile($fc,$DataSetGroup);

	echo "new file= " . $ifspec->progressfile;
	echo  '<script type="text/javascript">window.location="../pbar/status.php?lf=' . $ifspec->progressfile . '";</script>';
	//Show a spinner while plots are being drawn.

    echo "<div id='spinner' style='position:absolute;
            left:400px;
            top:25px;'>
            <font color = '#00ff00'><b>
            &nbsp &nbsp &nbsp &nbsp
            &nbsp &nbsp &nbsp &nbsp
            &nbsp &nbsp &nbsp &nbsp
            &nbsp &nbsp &nbsp &nbsp
            &nbsp &nbsp &nbsp &nbsp
            Drawing Plots...
            </font></b></div>";

	echo "<script type='text/javascript'>
			var opts = {
                lines: 12, // The number of lines to draw
                length: 10, // The length of each line
                width: 3, // The line thickness
                radius: 10, // The radius of the inner circle
                color: '#00ff00', // #rgb or #rrggbb
                speed: 1, // Rounds per second
                trail: 60, // Afterglow percentage
                shadow: false, // Whether to render a shadow
            };
			var target = document.getElementById('spinner');
			var spinner = new Spinner(opts).spin(target);
		</script>";
}

echo "<form action='".$_SERVER["PHP_SELF"]."' method='post' name='Submit' id='Submit'>";

if ($drawPlots == 1){
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
</form>
</body>
</html>
