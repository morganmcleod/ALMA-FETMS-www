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
$fesn = $ifspec->FrontEnd->GetValue('SN');//*/

// $ifSpectrupPlotsLogger -> WriteLogFile('feconfig=' . $feconfig . ' fesn=' . $fesn);

$title = "IF Spectrum Band $band DataSet $DataSetGroup";
include "header_ifspectrum.php";

echo "<body id = 'body3' onload='createIFSpectrumTabs($fc,$id,$FEid,$DataSetGroup,$band);' BGCOLOR='#19475E'>";

/*
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
}//*/

echo "<form action='".$_SERVER["PHP_SELF"]."' method='post' name='Submit' id='Submit'>";

if ($drawPlots == 1){
	require(site_get_config_main());
	ini_set('memory_limit', '384M');
	$ns = new Specifications();
	$specs = $ns->getSpecs('ifspectrum', $band);
	$trueSpec = $specs['spec_value'];
	
	$IF = new IFCalc();
	$IF->setParams($band, NULL, $FEid, $DataSetGroup);
	$IF->deleteTables();
	$IF->createTables();
	$plt = new plotter();
	//$if = 0;
	for ($if=0; $if<=3; $if++) {
		/*
		$IF->setParams($band, $if, $FEid, $DataSetGroup);
		$IF->deleteTables();
		$IF->getSpuriousData();//*/
		$IF->IFChannel = $if;
		$IF->getSpuriousData();
		
		$plt->setParams($IF->data, 'IFSpectrumLibrary', $band);
		//$plt->data = $plt->loadData("SpuriousNoiseBand$band" . "_IF$if");
		$plt->save_data("SpuriousNoiseBand$band" . "_IF$if");
		
		$plt->findLOs();
		$plt->getSpuriousNoise();
		$plt->plotSize(900, 600);
		$plotOut = "Band$band Spurious IF$if";
		$plt->plotOutput($plotOut);
		$plt->plotTitle("Spurious Noise, FE-61, Band " . $band . "SN 61 IF$if");
		$plt->plotGrid();
		$plt->plotKey(FALSE);
		$plt->plotBMargin(7);
		$y2tics = array();
		$att = array();
		$count = 1;
		foreach ($plt->LO as $L) {
			$y2tics[$L] = $plt->spurVal[$L];
			$att[] = "lines lt $count title '" . $L . " GHz'";
			$count++;
		}
		$plt->plotYTics(array('ytics' => FALSE, 'y2tics' => $y2tics));
		$plt->plotLabels(array('x' => 'IF (GHz)', 'y' => 'Power (dB)'));
		$plt->plotArrows();
		$plt->plotData($att, count($att));
		$plt->setPlotter($plt->genPlotCode());
		system("$GNUPLOT $plt->plotter");
		
		$plt->resetPlotter();
		
		$plt->getSpuriousExpanded();
		$plt->plotSize(900, 1500);
		$plotOut = "Band$band Spurious Expanded IF$if";
		$plt->plotOutput($plotOut);
		$plt->plotTitle('Spurious Noise, FE-61, Band ' . $band . "SN 61 IF$if");
		$plt->plotGrid();
		$plt->plotKey(FALSE);
		$plt->plotBMargin(7);
		$y2tics = array();
		$ytics = array();
		$att = array();
		$count = 1;
		foreach ($plt->LO as $L) {
			$y2tics[$L] = $plt->spurVal[$L][0];
			$ytics[$L] = $plt->spurVal[$L];
			$att[] = "lines lt $count title '" . $L . " GHz'";
			$count++;
		}
		$plt->plotYTics(array('ytics' => $ytics, 'y2tics' => $y2tics));
		$plt->plotLabels(array('x' => 'IF (GHz)', 'y' => 'Power (dB)'));
		$plt->plotArrows();
		$plt->plotData($att, count($att));
		$plt->setPlotter($plt->genPlotCode());
		system("$GNUPLOT $plt->plotter");
		
		$plt->resetPlotter();
		$fwin = 2 * pow(10, 9);
		$win = "2 GHz";
		$specs['spec_value'] = $trueSpec;
		if ($band == 6){
			$ymax = 9;
		} else {
			$ymax = $specs['spec_value'] + 1;
		}
		
		$IF->data = array();
		$IF->getPowerData($fwin);
		$plt->data = $IF->data;
		//$plt->data = $plt->loadData("PowerVarBand$band" . "_$win" . "_IF$if");
		$plt->save_data("PowerVarBand$band" . "_$win" . "_IF$if");
		
		$plt->getPowerVar();
		$plt->plotSize(900, 600);
		$saveas = "PowerVarBand$band" . "_$win" . "_IF$if";
		$plt->plotOutput($saveas);
		$plt->plotTitle("Power Variation $win Window: FE-61, Band $band SN 61, IF$if");
		$plt->plotGrid();
		$plt->createSpecsFile('Freq_Hz', array('spec_value'), array("lines lt -1 lw 5 title 'Spec'"), FALSE);
		$plt->plotLabels(array('x' => 'Center of Window (GHz)', 'y' => 'Power Variation in Window (dB)'));
		$plt->plotBMargin(7);
		$plt->plotKey('outside');
		$plt->plotYAxis(array('ymin' => 0, 'ymax' => $ymax));
		$att = array();
		$count = 1;
		foreach ($plt->LO as $L) {
			$att[] = "lines lt $count title '$L GHz'";
			$count++;
		}
		if ($band == 6) {
			$plt->band6powervar($if, $FEid, $DataSetGroup, $att, count($att));
		} else {
			$plt->plotData($att, count($att));
		}
		$plt->setPlotter($plt->genPlotCode());
		system("$GNUPLOT $plt->plotter");
		
		$fwin = 31 * pow(10, 6);
		$win = "31 MHz";
		$plt->resetPlotter();
		
		$IF->data = array();
		$IF->getPowerData($fwin);
		$plt->data = $IF->data;
		//$plt->data = $plt->loadData("PowerVarBand$band" . "_$win" . "_IF$if");
		$plt->save_data("PowerVarBand$band" . "_$win" . "_IF$if");
		
		$plt->getPowerVar();
		$plt->plotSize(900, 600);
		$saveas = "PowerVarBand$band" . "_$win" . "_IF$if";
		$plt->plotOutput($saveas);
		$plt->plotTitle("Power Variation $win Window: FE-61, Band $band SN 61, IF$if");
		$plt->plotGrid();
		$plt->specs['spec_value'] = 1.35;
		$ymax = $plt->specs['spec_value'] + 1;
		$plt->createSpecsFile('Freq_Hz', array('spec_value'), array("lines lt -1 lw 5 title 'Spec'"), FALSE);
		$plt->plotLabels(array('x' => 'Center of Window (GHz)', 'y' => 'Power Variation in Window (dB)'));
		$plt->plotBMargin(7);
		$plt->plotKey('outside');
		$plt->plotYAxis(array('ymin' => 0, 'ymax' => $ymax));
		$att = array();
		$count = 1;
		foreach ($plt->LO as $L) {
			$att[] = "lines lt $count title '$L GHz'";
			$count++;
		}
		$plt->plotData($att, count($att));
		$plt->setPlotter($plt->genPlotCode());
		system("$GNUPLOT $plt->plotter");
		
		$plt->resetPlotter();
	}
	$IF->deleteTables();
	ini_set('memory_limit', '128M');
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
