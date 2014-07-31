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

$feconfig = $ifspec->FrontEnd->feconfig_latest;
$fesn = $ifspec->FrontEnd->GetValue('SN');
$ccasn = $ifspec->FrontEnd->ccas[$band]->GetValue('SN');

// $ifSpectrupPlotsLogger -> WriteLogFile('feconfig=' . $feconfig . ' fesn=' . $fesn);

$title = "IF Spectrum Band $band DataSet $DataSetGroup";
include "header_ifspectrum.php";

echo "<body id = 'body3' onload='createIFSpectrumTabs($fc,$id,$FEid,$DataSetGroup,$band);' BGCOLOR='#19475E'>";

echo "<form action='".$_SERVER["PHP_SELF"]."' method='post' name='Submit' id='Submit'>";

if ($drawPlots == 1){
	require(site_get_config_main());
	ini_set('memory_limit', '384M');
	//Get specifications for plots
	$ns = new Specifications();
	$specs = $ns->getSpecs('ifspectrum', $band);
	$trueSpec = $specs['spec_value'];
	
	//Initialize IF Spectrum calculation class.
	$IF = new IFCalc();
	$IF->setParams($band, NULL, $FEid, $DataSetGroup);
	$IF->deleteTables();
	$IF->createTables();
	$plt = new plotter();
	$iflim = $specs['maxch'];
	
	$labels = array();
	$dbpull = new IF_db();
	$temp = $dbpull->qtdh($DataSetGroup, $band, $FEid, TRUE);
	$keys = $temp[0];
	$TS = $temp[1];
	$temp = "TestData_header.keyId: $keys[0]";
	for ($i=1; $i<count($keys); $i++) {
		$temp .= ", $keys[$i]";
	}
	$labels[] = $temp;
	
	$temp = "$TS, FE Configuration $feconfig; TestData_header.DataSetGroup: $DataSetGroup; IFSpectrum Ver. $IF->version";
	$labels[] = $temp;
	
	// Create plots for spurious noise, spurious noise expanded, and power variation for every IF.
	for ($if=0; $if<=$iflim; $if++) {
		$IF->IFChannel = $if;
		$IF->getSpuriousData(); // Gets spurious noise data from database
		
		$plt->setParams($IF->data, 'IFSpectrumLibrary', $band);

		//$plt->data = $plt->loadData("SpuriousNoiseBand$band" . "_IF$if"); // Use if desired data is saved into txt files.
		$plt->save_data("SpuriousNoiseBand$band" . "_IF$if"); //Saves data for later use.
		
		$plt->findLOs(); //Finds LO frequencies over band.
		$plt->getSpuriousNoise(); // Creates temporary files with spurious noise data to be used by GNUPLOT
		$plt->plotSize(900, 600); // Sets plot size to 900 pixels x 600 pixels
		$plotOut = "Band$band Spurious IF$if"; // Plot file name
		$plt->plotOutput($plotOut);
		$plt->plotTitle("Spurious Noise, FE-$fesn, Band " . $band . "SN $ccasn IF$if");
		$plt->plotGrid();
		$plt->plotKey(FALSE);
		$plt->plotBMargin(7);
		$y2tics = array();
		$att = array();
		$count = 1;
		// Sets 2nd y axis tick values using data created in getSpuriousNoise()
		// Sets line attributes.
		foreach ($plt->LO as $L) {
			$y2tics[$L] = $plt->spurVal[$L];
			$att[] = "lines lt $count title '" . $L . " GHz'";
			$count++;
		}
		$plt->plotYTics(array('ytics' => FALSE, 'y2tics' => $y2tics));
		$plt->plotLabels(array('x' => 'IF (GHz)', 'y' => 'Power (dB)')); // Set x and y axis labels
		$plt->plotArrows(); // Creates vertical lines over IF range from specs ini file.
		$plt->plotAddLabel($labels, array(array(0.01, 0.01), array(0.01, 0.04)));
		$plt->plotData($att, count($att));
		$plt->setPlotter($plt->genPlotCode()); // Generates and saves plotting script
		system("$GNUPLOT $plt->plotter");
		
		$plt->resetPlotter(); // Resets attributes to be used by plotter.
		
		$plt->getSpuriousExpanded(); // Creates temporary files with spurious expanded noise data.
		$plt->plotSize(900, 1500);
		$plotOut = "Band$band Spurious Expanded IF$if";
		$plt->plotOutput($plotOut);
		$plt->plotTitle("Spurious Noise, FE-$fesn, Band $band SN $ccasn IF$if");
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
		$pltheight = count($plt->LO) * 300;
		$lbl1 = 30 / $pltheight;
		$lbl2 = $lbl1 + .01;
		$plt->plotAddLabel($labels, array(array(0.01, $lbl1), array(0.01, $lbl2)));
		$plt->plotData($att, count($att));
		$plt->setPlotter($plt->genPlotCode());
		system("$GNUPLOT $plt->plotter");
		
		$plt->resetPlotter();
	}
	for ($if=0; $if<=$iflim; $if++) {
		$IF->IFChannel = $if;
		$fwin = 2 * pow(10, 9); // Window size
		$win = "2 GHz";
		$plt->specs['spec_value'] = $trueSpec; //Resets spec value to original value
		
		// Sets ymax limit
		if ($band == 6){
			$ymax = 9;
		} else {
			$ymax = $specs['spec_value'] + 1;
		}
		
		$IF->data = array();
		$IF->getPowerData($fwin); // Gets power variation from database for 2 GHz window
		$plt->data = $IF->data;
		//$plt->data = $plt->loadData("PowerVarBand$band" . "_$win" . "_IF$if");
		$plt->save_data("PowerVarBand$band" . "_$win" . "_IF$if");
		
		$plt->findLOs(); //Finds LO frequencies over band.
		$plt->getPowerVar(); // Creates temporary files for power variation over 2 GHz window plots
		$plt->plotSize(900, 600);
		$saveas = "PowerVarBand$band" . "_$win" . "_IF$if";
		$plt->plotOutput($saveas);
		$plt->plotTitle("Power Variation $win Window: FE-$fesn, Band $band SN $ccasn, IF$if");
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
		if ($band == 6) { // Band 6 case
			$plt->plotAddLabel($labels, array(array(0.01, 0.01), array(0.01, 0.04)));
			$plt->band6powervar($if, $FEid, $DataSetGroup, $att, count($att));
		} else {
			$temp = "Max Power Variation: " . round($IF->maxvar, 2) . " dB";
			$labels[] = $temp;
			$plt->plotAddLabel($labels, array(array(0.01, 0.01), array(0.01, 0.04), array(0.01, 0.07)));
			array_pop($labels);
			$plt->plotData($att, count($att));
		}
		$plt->setPlotter($plt->genPlotCode());
		system("$GNUPLOT $plt->plotter");
		
		$plt->resetPlotter();
		
		$fwin = 31 * pow(10, 6); // Sets window size to 31 MHz
		$win = "31 MHz";
		$IF->data = array();
		$IF->getPowerData($fwin); // Gets power variation data for 31 MHz window from database
		
		$plt->data = $IF->data;
		//$plt->data = $plt->loadData("PowerVarBand$band" . "_$win" . "_IF$if");
		$plt->save_data("PowerVarBand$band" . "_$win" . "_IF$if");
		
		$plt->getPowerVar();
		$plt->plotSize(900, 600);
		$saveas = "PowerVarBand$band" . "_$win" . "_IF$if";
		$plt->plotOutput($saveas);
		$plt->plotTitle("Power Variation $win Window: FE-$fesn, Band $band SN $ccasn, IF$if");
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
		$temp = "Max Power Variation: " . round($IF->maxvar, 2) . " dB";
		$labels[] = $temp;
		$plt->plotAddLabel($labels, array(array(0.01, 0.01), array(0.01, 0.04), array(0.01, 0.07)));
		array_pop($labels);
		$plt->plotData($att, count($att));
		$plt->setPlotter($plt->genPlotCode());
		system("$GNUPLOT $plt->plotter");
		
		$plt->resetPlotter();
	}
	$IF->deleteTables(); // Deletes temporary tables.
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
