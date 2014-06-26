<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_NT . '/noisetempcalc.php');
require_once($site_NT . '/plotter.php');

/**
 * Initializes plotter for band3
 * 
 * @param string $plot- rf for RF plot, if for IF plot
 */
function band3 ($plot, $pol, $sb) {
	$NT = new NTCalc();
	$plt = new plotter();
	$NT->setParams(3, 0, 256, 40, 60);
	$NT->getData();
	$NT->getCCAkeys();
	$NT->getIRData();
	$NT->calcNoiseTemp();
	$NT->getSpecData();
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
	$NT->getSpecData();
	$plt->setData($NT->data, $NT->rx);
	$plt->print_data();
	$plt->save_data();
	if ($plot == 'rf') {
		plotRF($plt, 6, $pol, $sb, 149.6);
	}
	if ($plot == 'if') {
		plotIF($plt, 6, 149.6);
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
	$plt->generate_plots($band, $data, $att, $ylim, $title, $saveas, $labels, FALSE);
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
	$saveas = "Band$band IF";;
	$labels = array("IF (GHz)", "Tssb (K)");
	$plt->generate_plots($band, $data, $att, $ylim, $title, $saveas, $labels, TRUE);
}

band6('if', 0, 1);
?>
<html>
<body>
<div>
<br>
<img alt="plot" src="http://webtest.cv.nrao.edu/php/ntc/ws-atb/test_datafiles/NoiseTempLibrary/Band6 Pol0 Sb1.png">
<br>
</div>
</body>
</html>