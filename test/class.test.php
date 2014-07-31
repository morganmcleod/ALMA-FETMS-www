<?php
/**
 * ALMA - Atacama Large Millimeter Array
 * (c) Associated Universities Inc., 2006
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307  USA
 *
 * @author Aaron Beaudoin
 * Version 1.0 (07/30/2014)
 *
 *
 * Example code can be found in /test/class.test.php
 *
 */

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_NT . '/noisetempcalc.php');
require_once($site_IF . '/IFCalc.php');
require_once($site_root . '/test/Library/plotter.php');

/**
 * Creates spurious noise plots
 * 
 * @param int $band
 * @param int $if
 */
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

/**
 * Creates spurious noise expanded data for every IF
 * 
 * @param int $band
 */
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

/**
 * Creates corrected noise temperature vs. LO plots
 * 
 * @param int $band
 */
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

/**
 * Creates corrected noise temperature vs. IF plots
 * 
 * @param int $band
 */
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

/**
 * Creates average corrected noise temperature vs. LO plots
 * 
 * @param int $band
 */
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

/**
 * Creates power variation window plots for 2GHz window and 31 MHz window for a single IF.
 * 
 * @param int $Band
 * @param int $IFChannel
 * @param int $FEid
 * @param int $DataSetGroup
 * @param int $if
 */
function powerVar($band, $IFChannel, $FEid, $DataSetGroup) {
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
	
	$temp = "$TS, FE Configuration ###; TestData_header.DataSetGroup: $DataSetGroup; IFSpectrum Ver. $IF->version";
	$labels[] = $temp;
	require(site_get_config_main());
	$IF = new IFCalc();
	$IF->setParams($band, $IFChannel, $FEid, $DataSetGroup);
	/*$IF->deleteTables();
	$IF->createTables();
	$IF->getPowerData(31 * pow(10, 6));//*/
	
	$plt = new plotter();
	$plt->setParams(NULL, 'IFSpectrumLibrary', $band);
	$if = $IFChannel;
	
	$fwin = 2 * pow(10, 9); // Window size
	$win = "2 GHz";
	$trueSpec = $plt->specs['spec_value']; //Resets spec value to original value
	
	// Sets ymax limit
	if ($band == 6){
		$ymax = 9;
	} else {
		$ymax = $plt->specs['spec_value'] + 1;
	}
	
	//$IF->data = array();
	//$IF->getPowerData($fwin); // Gets power variation from database for 2 GHz window
	//$plt->data = $IF->data;
	$plt->data = $plt->loadData("PowerVarBand$band" . "_$win" . "_IF$if");
	//$plt->save_data("PowerVarBand$band" . "_$win" . "_IF$if");
	
	$plt->findLOs(); //Finds LO frequencies over band.
	$plt->getPowerVar(); // Creates temporary files for power variation over 2 GHz window plots
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
	if ($band == 6) { // Band 6 case
		$plt->plotAddLabel($labels, array(array(0.01, 0.01), array(0.01, 0.04)));
		$plt->band6powervar($if, $FEid, $DataSetGroup, $att, count($att));
	} else {
		$temp = "Max Power Variation: $IF->maxvar dB";
		$labels[] = $temp;
		$plt->plotAddLabel($labels, array(array(0.01, 0.01), array(0.01, 0.04), array(0.01, 0.07)));
		$plt->plotData($att, count($att));
	}
	$plt->setPlotter($plt->genPlotCode());
	system("$GNUPLOT $plt->plotter");
	
	$plt->resetPlotter();
	
	$fwin = 31 * pow(10, 6); // Sets window size to 31 MHz
	$win = "31 MHz";
	//$IF->data = array();
	//$IF->getPowerData($fwin); // Gets power variation data for 31 MHz window from database
	
	//$plt->data = $IF->data;
	$plt->data = $plt->loadData("PowerVarBand$band" . "_$win" . "_IF$if");
	//$plt->save_data("PowerVarBand$band" . "_$win" . "_IF$if");
	
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
	$plt->specs['spec_value'] = $trueSpec;
}

$band = 6;
$FEid = 87;
$DataSetGroup = 2;
for ($if=0; $if<=3; $if++) {
	powerVar($band, $if, $FEid, $DataSetGroup);
}

?>