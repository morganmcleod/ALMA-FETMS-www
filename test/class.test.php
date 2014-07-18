<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_NT . '/noisetempcalc.php');
require_once($site_IF . '/IFCalc.php');
require_once($site_root . '/test/Library/plotter.php');

function ifspec ($band, $ifchannel, $feid, $datasetgroup) {
	$IF = new IFCalc();
	$IF->setParams($band, $ifchannel, $feid, $datasetgroup);
	$IF->getSpuriousData();
	$plt = new plotter();
	$plt->setParams($IF->data, 'IFSpectrumLibrary');
	//$plt->loadData();
	//$plt->print_data();
	$plt->save_data();
	plotIFSpectrum($plt, $band, $ifchannel);
	echo "Done <br>";
	//$IF->deleteTempTable();
}

/**
 * Initializes plotter for band3
 * 
 * @param string $plot- rf for RF plot, if for IF plot
 */
function band3 ($plot, $pol, $sb) {
	$NT = new NTCalc();
	$plt = new plotter();
	$NT->setParams(3, 0, 256, 40, 60);
	$plt->setParams($NT->data, 'NoiseTempLibrary');
	$plt->loadData();
	$NT->data = $plt->data;
	$plt->print_data();
	#$NT->getData();
	$NT->getCCAkeys();
	$NT->getIRData();
	$NT->calcNoiseTemp();
	$NT->getCCANTData();
	$plt->setData($NT->data, $NT->rx);
	$plt->print_data();
	$plt->save_data();
	if ($plot == 'rf') {
		plotRF($plt, 3, $pol, $sb, 66);
	}
	if ($plot == 'if') {
		plotIF($plt, 3, 66);
	}
}

/**
 * Initializes plotter for band6
 *
 * @param string $plot- rf for RF plot, if for IF plot
 */
function band6 ($plot, $pol, $sb) {
	$NT = new NTCalc();
	$plt = new plotter();
	$NT->setParams(6, 0, 258, 40, 61);
	//
	$NT->getData();
	$NT->getCCAkeys();
	$NT->getIRData();
	$NT->calcNoiseTemp();
	$NT->getCCANTData();
	$plt->setData($NT->data, $NT->rx);
	$plt->print_data();
	$plt->save_data();
	if ($plot == 'rf') {
		plotRF($plt, 6, $pol, $sb, 149.6);
	}
	if ($plot == 'if') {
		plotIF($plt, 6, 149.6);
	}
	if($plot == 'lo') {
		plotAvg($plt, 6, 149.6);
	}
}

/**
 * Creates parameters to plot RF vs. Tssb
 * 
 * @param object $plt- plotter
 * @param int $band
 * @param int $pol
 * @param int $sb
 * @param float $y- upper y limit
 */
function plotRF($plt, $band, $pol, $sb, $y) {
	$data = array();
	if ($sb == 1 ){
		$data[] = 'RF_usb';
	} else {
		$data[] = 'RF_lsb';
	}
	$data[] = "Tssb_corr$pol$sb";
	$data[] = "Trx$pol$sb";
	$data[] = "diff$pol$sb";
	$att = array();
	$att[] = "lines lt 1 lw 3 title 'FEIC Meas Pol0 USB'";
	$att[] = "lines lt 3 title 'Cart Group Meas Pol0 USB'";
	$att[] = "points lt -1 axes x1y2 title 'Diff relative to Spec'";
	$ylim = array(0, $y, 0, 120);
	$title = "Receiver Noise Temperature, 5-10 GHz IF, FE SN61, CCA6-61 WCA6-09, Pol $pol Sb$sb";
	$saveas = "Band$band Pol$pol Sb$sb RF";
	$labels = array("RF (GHz)", "Tssb Corrected (K)", "Difference from Spec (%)");
	$plt->generate_plots($band, $data, $att, $ylim, $title, $saveas, $labels, FALSE, FALSE);
}

/**
 * Creates parameters to plot RF vs. Tssb
 * 
 * @param object $plt- plotter
 * @param int $band
 * @param float $y- upper y limit
 */
function plotIF($plt, $band, $y) {
	$data = array();
	$data[] = 'CenterIF';
	$data[] = 'Tssb_corr01';
	$data[] = 'Tssb_corr02';
	$data[] = 'Tssb_corr11';
	$data[] = 'Tssb_corr12';
	$att = array();
	$att[] = "lines lt 1 lw 1 title 'Pol0sb1'";
	$att[] = "lines lt 2 lw 1 title 'Pol0sb2'";
	$att[] = "lines lt 3 lw 1 title 'Pol1sb1'";
	$att[] = "lines lt 4 lw 1 title 'Pol1sb2'";
	$ylim = array(0,$y);//149.6);//*/
	$title = "Receiver Noise Temperature Tssb corrected, FE SN61, CCA6-61 WCA6-09";
	$saveas = "Band$band IF";
	$labels = array("IF (GHz)", "Tssb (K)");
	$plt->generate_plots($band, $data, $att, $ylim, $title, $saveas, $labels, TRUE, FALSE);
}

function plotAvg($plt, $band, $y) {
	$data = array();
	$data[] = 'FreqLO';
	$data[] = 'Tssb_corr01';
	$data[] = 'Tssb_corr02';
	$data[] = 'Tssb_corr11';
	$data[] = 'Tssb_corr12';
	$att = array();
	$att[] = "linespoints lt 1 lw 1 title 'Pol0sb1'";
	$att[] = "linespoints lt 2 lw 1 title 'Pol0sb2'";
	$att[] = "linespoints lt 3 lw 1 title 'Pol1sb1'";
	$att[] = "linespoints lt 4 lw 1 title 'Pol1sb2'";
	$ylim = array(0, $y);
	$title = "Receiver Noise Temperature Tssb corrected, FE SN61, CCA6-61 WCA6-09";
	$saveas = "Band$band RF";
	$labels = array("LO (GHz)", "Average Tssb (K)");
	$plt->generate_plots($band, $data, $att, $ylim, $title, $saveas, $labels, TRUE, TRUE);
}

function plotIFSpectrum($plt, $band, $ifchannel) {
	$LO = array('92', '96', '100', '104', '108');
	$plt->genIFSpectrum($LO, $band, $ifchannel);
}

require(site_get_config_main());

$IF = new IFCalc();
$band = 6;
if ($band == 3) {
	$dsg = 0;
	$keyId = 256;
}
if ($band == 6) {
	$dsg = 2;
	$keyId = 258;
}
$ifchannel = 0;
$feid = 87;
$fc = 40;
$sn = 60;

//$fwin = 31 * pow(10,6);
$fwin = 2 * pow(10,9);
//$win = "31 MHz";
$win = "2 GHz";

/*
$IF->setParams($band, $ifchannel, $feid, $dsg);
$IF->deleteTables();
$IF->createTables();
//$IF->getSpuriousData();
$IF->getPowerData($fwin);
$IF->deleteTables();//*/

$plt = new plotter();
$plt->setParams(NULL, 'IFSpectrumLibrary', $band);
$plt->powerTables($dsg, $feid);
//$plt->save_data("PowerVarBand" . $band . "_" . $win . "_IF$ifchannel");
//$plt->data = $plt->loadData("PowerVarBand" . $band . "_" . $win . "_IF$ifchannel");
//echo "<table border = '1'>";
//$plt->print_data();
//echo "</table>";
//$plt->save_data("NTDataBand$band");

/*
$plt->findLOs();
$plt->getPowerVar();

$plt->plotSize(900, 600);
$plt->plotOutput("PowerVarBand$band" . "_$win" . "_IF$ifchannel");
$plt->plotTitle("Power Variation $win Window: FE-61, Band $band SN 60, IF$ifchannel");
$plt->plotGrid();
$plt->plotKey('outside');
$plt->plotBMargin(7);
//$plt->specs['spec_value'] = 1.35; //Set for 31 MHz window
//$plt->createSpecsFile('Freq_Hz', array('spec_value'), array("lines lt -1 lw 5 title 'Spec'"), FALSE);
$plt->plotLabels(array('x' => 'Center of Window (GHz)', 'y' => 'Power Variation in Window (dB)'));
$plt->plotYAxis(array('ymin' => 0, 'ymax' => 7));

$count = 1;
$att = array();
foreach ($plt->LO as $L) {
	$att[] = "lines lt $count title '" . $L . " GHz'";
	$count++;
}

$plt->band6powervar($ifchannel, $feid, $dsg, $att, count($att));

$keys = array_keys($plt->plotAtt);
foreach ($keys as $k) {
	echo "$k: " . $plt->plotAtt[$k] . "<br>";
}

$plt->setPlotter($plt->genPlotCode());
system("$GNUPLOT $plt->plotter");
//*/
//$plt->print_data();


?>
<html>
<body>
<div>
<br>
<img alt="plot" src="http://webtest.cv.nrao.edu/php/ntc/ws-atb/test_datafiles/IFSpectrumLibrary/PowerVarBand3_31MHz.png">
<br>
</div>
</body>
</html>