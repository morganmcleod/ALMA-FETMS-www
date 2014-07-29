<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_NT . '/noisetempcalc.php');
require_once($site_IF . '/IFCalc.php');
require_once($site_root . '/test/Library/plotter.php');

function spuriousNoise($band, $if) {
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

	//for ($if=0; $if<=3; $if++) {
	$IF->setParams($band, $if, $feid, $dsg);
	$IF->deleteTables();
	$IF->createTables();
	$IF->getSpuriousData();
	$IF->deleteTables();

	$plt->setParams($IF->data, 'IFSpectrumLibrary', $band);
	$saveas = "SpuriousNoiseBand$band" . "_IF$if";
	$plt->save_data($saveas);
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
	
	echo "$plotOut <br>";
	//}
}

function spuriousExpanded($band) {
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
		
		echo "$plotOut <br>";
	}
}

function noiseTempRF($band) {
	require(site_get_config_main());
	$NT = new NTCalc();
	$plt = new plotter();

	if ($band == 3) {
		$keyId = 256;
		$sn = 60;
		$ymax = 66;
	}
	if ($band == 6) {
		$keyId = 258;
		$sn = 61;
		$ymax = 149.6;
	}

	$dsg = 0;
	$fc = 40;
	$NT->setParams($band, $dsg, $keyId, $fc, $sn);
	$NT->getCCAkeys();
	$NT->getData();
	$NT->getIRData();
	$NT->calcNoiseTemp();
	$NT->getCCANTData();

	for ($pol=0; $pol<=1; $pol++) {
		for ($sb=1; $sb<=2; $sb++) {
			if ($sb == 1) {
				$sideband = 'USB';
				$xvar = 'RF_usb';
			}
			if ($sb == 2) {
				$xvar = 'RF_lsb';
				$sideband = 'LSB';
			}
			$yvar = "$pol$sb";
			
			$plt->setParams($NT->data, 'NoiseTempLibrary', $band);
			
			$plt->plotSize(900, 600);
			$saveas = "Band$band Pol$pol Sb$sb RF";
			$plt->plotOutput($saveas);
			$plt->plotTitle('Receiver Noise Temperature Tssb corrected');
			$plt->plotLabels(array('x' => 'LO (GHz)', 'y' => 'Tssb Corrected (K)', 'y2' => 'Difference from Spec (%)'));
			$plt->plotYAxis(array('ymin' => 0, 'ymax' => $ymax, 'y2min' => 0, 'y2max' => 120));
			$plt->checkIFLim('CenterIF');
			$plt->createTempFile($xvar, "Tssb_corr$yvar", 0);
			$plt->createTempFile($xvar, "Trx$yvar", 1);
			$plt->createTempFile($xvar, "diff$yvar", 2);
			$att = array();
			$att[] = "lines lt 1 lw 3 title 'FEIC Meas Pol$pol $sideband'";
			$att[] = "lines lt 3 title 'Car Group Meas Pol$pol $sideband'";
			$att[] = "points lt -1 axes x1y2 title 'Diff relative to Spec'";
			$plt->plotData($att, count($att));
			$plt->setPlotter($plt->genPlotCode());
			system("$GNUPLOT $plt->plotter");
			
			echo "$saveas <br>";
		}
	}		
}

function noiseTempIF($band) {
	$NT = new NTCalc();
	$plt = new plotter();
	
	require(site_get_config_main());
	$NT = new NTCalc();
	$plt = new plotter();

	if ($band == 3) {
		$keyId = 256;
		$sn = 60;
		$ymax = 66;
	}
	if ($band == 6) {
		$keyId = 258;
		$sn = 61;
		$ymax = 149.6;
	}

	$dsg = 0;
	$fc = 40;
	$NT->setParams($band, $dsg, $keyId, $fc, $sn);
	$NT->getCCAkeys();
	$NT->getData();
	$NT->getIRData();
	$NT->calcNoiseTemp();
	$NT->getCCANTData();
	
	$plt->setParams($NT->data, 'NoiseTempLibrary', $band);
	
	$plt->plotSize(900, 600);
	$saveas = "Band$band IF";
	$plt->plotOutput($saveas);
	$plt->plotTitle('Receiver Noise Temperature Tssb Corrected');
	$plt->plotLabels(array('x' => 'IF (GHz)', 'y' => 'Tssb (K)'));
	$plt->plotYAxis(array('ymin' => 0, 'ymax' => $ymax));
	$plt->plotKey('outside');
	$plt->plotBMargin(6);
	$plt->createTempFile('CenterIF', 'Tssb_corr01', 0);
	$plt->createTempFile('CenterIF', 'Tssb_corr02', 1);
	$plt->createTempFile('CenterIF', 'Tssb_corr11', 2);
	$plt->createTempFile('CenterIF', 'Tssb_corr12', 3);
	$new_specs = new Specifications();
	$specs = $new_specs->getSpecs('FEIC_NoiseTemperature', $band);
	$plt->createSpecsFile('CenterIF', array('NT20', 'NT80'), array("lines lt -1 lw 3 title '" . $specs['NT20'] . " K (100%)'", "lines lt 0 lw 1 title '" . $specs['NT80'] . " K (80%)'"), TRUE);
	$att = array();
	$att[] = "lines lt 1 lw 1 title 'Pol0sb1'";
	$att[] = "lines lt 2 lw 1 title 'Pol0sb2'";
	$att[] = "lines lt 3 lw 1 title 'Pol1sb1'";
	$att[] = "lines lt 4 lw 1 title 'Pol1sb2'";
	$plt->plotData($att, count($att));
	$plt->setPlotter($plt->genPlotCode());
	system("$GNUPLOT $plt->plotter");
	
	echo "$saveas <br>";
}

function noiseTempRFAvg($band) {
	$NT = new NTCalc();
	$plt = new plotter();
	
	require(site_get_config_main());
	$NT = new NTCalc();
	$plt = new plotter();
	
	if ($band == 3) {
		$keyId = 256;
		$sn = 60;
		$ymax = 66;
	}
	if ($band == 6) {
		$keyId = 258;
		$sn = 61;
		$ymax = 149.6;
	}
	
	$dsg = 0;
	$fc = 40;
	$NT->setParams($band, $dsg, $keyId, $fc, $sn);
	$NT->getCCAkeys();
	$NT->getData();
	$NT->getIRData();
	$NT->calcNoiseTemp();
	$NT->getCCANTData();
	
	$plt->setParams($NT->data, 'NoiseTempLibrary', $band);
	
	$plt->plotSize(900, 600);
	$saveas = "Band$band Avg RF";
	$plt->plotOutput($saveas);
	$plt->plotTitle('Receiver Noise Temperature Tssb Corrected');
	$plt->plotLabels(array('x' => 'LO (GHz)', 'y' => 'Average Tssb (K)'));
	$plt->plotYAxis(array('ymin' => 0, 'ymax' => $ymax));
	$plt->plotKey('outside');
	$plt->plotBMargin(6);
	$plt->checkIFLim('CenterIF');
	$plt->createTempFile('FreqLO', 'Tssb_corr01', 0, TRUE);
	$plt->createTempFile('FreqLO', 'Tssb_corr02', 1, TRUE);
	$plt->createTempFile('FreqLO', 'Tssb_corr11', 2, TRUE);
	$plt->createTempFile('FreqLO', 'Tssb_corr12', 3, TRUE);
	$new_specs = new Specifications();
	$specs = $new_specs->getSpecs('FEIC_NoiseTemperature', $band);
	$plt->createSpecsFile('FreqLO', array('NT80'), array("lines lt -1 lw 3 title ' " . $specs['NT80'] . " K (80%)"));
	$att = array();
	$att[] = "linespoints lt 1 lw 1 title 'Pol0sb1'";
	$att[] = "linespoints lt 2 lw 1 title 'Pol0sb2'";
	$att[] = "linespoints lt 3 lw 1 title 'Pol1sb1'";
	$att[] = "linespoints lt 4 lw 1 title 'Pol1sb2'";
	$plt->plotData($att, count($att));
	$plt->setPlotter($plt->genPlotCode());
	system("$GNUPLOT $plt->plotter");
	
	echo "$saveas <br>";
}

function cleanData($data) {
	$newData = array();
	for ($i=0; $i<count($data) - 1; $i++) {
		if ($data[$i]['Freq_Hz'] != $data[$i+1]['Freq_Hz']) {
			if ($data[$i]['Power_dBm'] != $data[$i+1]['Power_dBm']) {
				$newData[] = $data[$i];
			}
		}
	}
	return $newData;
}
$IF = new IFCalc();
$IF->setParams(3, NULL, 87, 0);
$IF->deleteTables();
$IF->createTables();

$IF->IFChannel = 0;
$fwin = 2 * pow(10, 9);
$IF->getPowerData($fwin);

//spuriousNoise(3, 2);
/*
$band = 6;
if ($band == 3) {
	$dsg = 0;
	$keyId = 256;
}
if ($band == 6) {
	$dsg = 2;
	$keyId = 258;
}
$if = 0;
$feid = 87;
$fc = 40;
$sn = 60;
require(site_get_config_main());

$IF = new IFCalc();
$plt = new plotter();
$IF->setParams($band, $if, $feid, $dsg);
echo "params set <br>";
$IF->deleteTables();
echo "tables deleted <br>";
//$IF->createTables();
$IF->getSpuriousData();
echo "data retrieved <br>";
$IF->deleteTables();
echo "tables deleted <br>";

$plt->setParams($IF->data, 'IFSpectrumLibrary', $band);
$saveas = "SpuriousNoiseBand$band" . "_IF$if";
$plt->save_data($saveas);
echo "data saved<br>";
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

/*
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

//$bands = array(3, 6);
//$fwin = 31 * pow(10,6);
$fwin = 2 * pow(10,9);
//$win = "31 MHz";
$win = "2 GHz";
$plt = new plotter();
echo "Running band $band, IF $ifchannel, FEid $feid, DataSetGroup $dsg <br>";

$IF->setParams($band, $ifchannel, $feid, $dsg);
$IF->deleteTables();
echo "Tables deleted <br>";
//$IF->getSpuriousData();
$IF->createTables();
echo "Tables created <br>";
$IF->getPowerData($fwin);
echo "Data retrieved <br>";
$IF->deleteTables();//*//*
echo "Tables deleted <br>";
$plt = new plotter();
$plt->setParams($IF->data, 'IFSpectrumLibrary', $band);
echo "<Table border = '1'>";
$plt->print_data();
echo "</Table>";
$saveas = "PowerVarBand$band" . "_$win" . "_IF$ifchannel";
$plt->save_data($saveas);
/*
$plt = new plotter();
$plt->setParams(NULL, 'IFSpectrumLibrary', $band);
for ($ifchannel = 0; $ifchannel <= 3; $ifchannel++) {
	$plt->data = $plt->loadData("PowerVarBand" . $band . "_$win" . "_IF$ifchannel");
	//$plt->data = $plt->loadData("PowerVarBand" . $band . "_" . $win . "_IF$ifchannel");
	echo "<table border = '1'>";
	$plt->print_data();
	echo "</table>";
	//$plt->save_data("NTDataBand$band");
	
	//*/
	/*
$plt->findLOs();
$plt->getPowerVar();

$plt->plotSize(900, 600);
$plt->plotOutput("PowerVarBand$band" . "_$win" . "_IF$ifchannel");
$plt->plotTitle("Power Variation $win Window: FE-61, Band $band SN 60, IF$ifchannel");
$plt->plotGrid();
$plt->plotKey('outside');
$plt->plotBMargin(7);
if ($fwin == (31 * pow(10, 6))) {
	$plt->specs['spec_value'] = 1.35; //Set for 31 MHz window
}
$ymax = $plt->specs['spec_value'] + 1;
$plt->createSpecsFile('Freq_Hz', array('spec_value'), array("lines lt -1 lw 5 title 'Spec'"), FALSE);
$plt->plotLabels(array('x' => 'Center of Window (GHz)', 'y' => 'Power Variation in Window (dB)'));
$plt->plotYAxis(array('ymin' => 0, 'ymax' => $ymax));

$count = 1;
$att = array();
foreach ($plt->LO as $L) {
	$att[] = "lines lt $count title '" . $L . " GHz'";
	$count++;
}
if ($band == 6 && $win == '2 GHz') {
	$plt->band6powervar($ifchannel, $feid, $dsg, $att, count($att));
} else {
	$plt->plotData($att, count($att));
}
$plt->setPlotter($plt->genPlotCode());
system("$GNUPLOT $plt->plotter");
//}
//*/
//$plt->print_data();


?>