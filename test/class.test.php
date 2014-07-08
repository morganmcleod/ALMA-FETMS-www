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
$plt = new plotter();
$plt->setParams(NULL, 'IFSpectrumLibrary', 3);
$plt->data = $plt->loadData();
$plt->print_data();

$LO = array('92', '96', '100', '104', '108');
$plt->getSpuriousExpanded($LO);
$plt->plotSize(900, 1500);
$plt->plotOutput('Band3 Spurious Expanded IF0');
$plt->plotTitle('Spurious Noise, FE-61, Band 3 SN 60 IF0');
$plt->plotLabels(array('x' => 'IF (GHz)', 'y' => 'Power(dB)'));
$plt->plotGrid();
$plt->plotKey(FALSE);
$plt->plotBMargin(7);

$y2tics = array();
$ytics = array();
$att = array();
$count = 1;
foreach ($LO as $L) {
	$y2tics[$L] = $plt->spurVal[$L][0];
	$ytics[$L] = $plt->spurVal[$L];
	$att[] = "lines lt $count title '" . $L . "GHz'";
	$count++;
}
$plt->plotYTics(array('ytics' => $ytics, 'y2tics' => $y2tics));
$plt->plotArrows();

$plt->plotData($att, count($att));
$plt->setPlotter($plt->genPlotCode());
system("$GNUPLOT $plt->plotter");

?>
<html>
<body>
<div>
<br>
<img alt="plot" src="http://webtest.cv.nrao.edu/php/ntc/ws-atb/test_datafiles/IFSpectrumLibrary/3spuriousIF0.png">
<br>
</div>
</body>
</html>