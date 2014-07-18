<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_NT . '/noisetempcalc.php');
require_once($site_IF . '/IFCalc.php');
require_once($site_root . '/test/Library/plotter.php');

function spuriousNoise($band) {
	require(site_get_config_main());
	$IF = new IFCalc();
	$plt = new plotter();
	
	$feid = 87;
	if ($band == 3) {
		$dsg = 0;
	}
	if ($band == 6) {
		$dsg = 2;
	}
	
	for ($if=0; $if<=3; $if++) {
		$IF->setParams($band, $if, $feid, $dsg);
		$IF->deleteTables();
		$IF->createTables();
		$IF->getSpuriousData();
		$IF->deleteTables();
		
		$plt->setParams($IF->data, 'IFSpectrumLibrary', $band);
		$plt->findLOs();
		$plt->getSpuriousNoise();
		$plt->plotSize(900, 600);
		$plotOut = "Band$band Spurious IF$if";
		$plt->plotOutput($plotOut);
		$plt->plotTitle('Spurious Noise, FE-61, Band ' . $band . "SN 61 IF$if");
		$plt->plotGrid();
		$plt->plotKey(FALSE);
		$plt->plotBMargin(7);
		$y2tics = array();
		$att = array();
		$count = 1;
		foreach ($this->LO as $L) {
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
		
		echo "<img alt='plot' src='$main_write_directory$this->dir/$plotOut.png'>";
	}
		
}

function spuriousExpanded() {
	require(site_get_config_main());
	$IF = new IFCalc();
	$plt = new plotter();
	
	$feid = 87;
	if ($band == 3) {
		$dsg = 0;
	}
	if ($band == 6) {
		$dsg = 2;
	}
	
	for ($if=0; $if<=3; $if++) {
		$IF->setParams($band, $if, $feid, $dsg);
		$IF->deleteTables();
		$IF->createTables();
		$IF->getSpuriousData();
		$IF->deleteTables();
	
		$plt->setParams($IF->data, 'IFSpectrumLibrary', $band);
		$plt->findLOs();
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
		foreach ($this->LO as $L) {
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
	
		echo "<img alt='plot' src='$main_write_directory$this->dir/$plotOut.png'>";
	}
}

function noiseTemp($band) {
	$NT = new NTCalc();
	$plt = new plotter();
	
	if ($band == 3) {
		$keyId = 256;
		$sn = 60;
	}
	if ($band == 6) {
		$keyId = 258;
		$sn = 61;
	}
	
	$dsg = 0;
	$fc = 40;
	$NT->setParams($band, $dataSetGroup, $keyId, $fc, $sn);
	$NT->getCCAkeys();
	$NT->getData();
	$NT->getIRData();
	$NT->getCCANTData();
	
	$plt->setParams($NT->data, 'NoiseTempLibrary', $band);
	
	$plt->plotSize(900, 600);
	$saveas = ""
	$plt->plotOutput($saveas);
}

$this->spuriousNoise($band);

?>