<?php
/*
 * This is called from loadIFSpectrum.js.
 *
 * The main purpose of this script is to populate the ExtJS tab structure with tables and plots.
 *
 * Arguments:
 *
 * fc- Facility code
 * keyId- Key value of a record in the TestData_header table
 * tabtype- Tab selected by the user
 *
 */

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.ifspectrumplotter.php');

$fc = $_REQUEST['fc'];
$FEid = $_REQUEST['fe'];
$band = $_REQUEST['b'];
$keyId = $_REQUEST['id'];
$DataSetGroup = $_REQUEST['g'];
$tabtype =$_REQUEST['tabtype'];

//Instantiate a new IF Spectrum object
$ifs = new IFSpectrumPlotter();

// use its log file to record request params:
// $ifs->logger->WriteLogFile('getIFspectrumplotdata.php: fc=' . $fc . ' FEid=' . $FEid . ' band=' . $band . ' group=' . $DataSetGroup . ' keyID=' . $keyId . ' tabtype=' . $tabtype);

// and initialize it:
$ifs->Initialize_IFSpectrum($FEid,$DataSetGroup,$fc,$band);

echo "<div style='background-color:#6C7070;width:1000px;'>";

switch($tabtype){
    case 1:
        //Info tab was selected
        $ifs->DisplayTDHinfo();
        break;

    case 'spurious_0':
        //Spurious Noise tab was selected, IF0 subtab was selected.
        echo "<div style='height:750px'>";
        if (isset($ifs -> urls[0]))
            echo "<img src='http://webtest.cv.nrao.edu/php/ntc/ws-atb/test_datafiles/IFSpectrumLibrary/Band$band Spurious IF0.png'>";
        echo "</div>";
        break;

    case 'spurious_1':
        //Spurious Noise tab was selected, IF1 subtab was selected.
        echo "<div style='height:750px'>";
        if (isset($ifs -> urls[1]))
            echo "<img src='http://webtest.cv.nrao.edu/php/ntc/ws-atb/test_datafiles/IFSpectrumLibrary/Band$band Spurious IF1.png'>";
        echo "</div>";
        break;

    case 'spurious_2':
        //Spurious Noise tab was selected, IF2 subtab was selected.
        echo "<div style='height:750px'>";
        if (isset($ifs->urls[2]))
            echo "<img src='http://webtest.cv.nrao.edu/php/ntc/ws-atb/test_datafiles/IFSpectrumLibrary/Band$band Spurious IF2.png'>";
        echo "</div>";
        break;

    case 'spurious_3':
        //Spurious Noise tab was selected, IF3 subtab was selected.
        echo "<div style='height:750px'>";
        if (isset($ifs->urls[3]))
            echo "<img src='http://webtest.cv.nrao.edu/php/ntc/ws-atb/test_datafiles/IFSpectrumLibrary/Band$band Spurious IF3.png'>";
        echo "</div>";
        break;

    case 'spurious2_0':
        //Spurious Noise (Expanded Plots) tab was selected, IF0 subtab was selected.
        echo "<div>";
        if (isset($ifs -> urls[0]))
            echo "<img src='http://webtest.cv.nrao.edu/php/ntc/ws-atb/test_datafiles/IFSpectrumLibrary/Band$band Spurious Expanded IF0.png'>";
        echo "</div>";
        break;

    case 'spurious2_1':
        //Spurious Noise (Expanded Plots) tab was selected, IF1 subtab was selected.
        echo "<div>";
        if (isset($ifs -> urls[1]))
            echo "<img src='http://webtest.cv.nrao.edu/php/ntc/ws-atb/test_datafiles/IFSpectrumLibrary/Band$band Spurious Expanded IF1.png'>";
        echo "</div>";
        break;

    case 'spurious2_2':
        //Spurious Noise (Expanded Plots) tab was selected, IF2 subtab was selected.
        echo "<div>";
        if (isset($ifs->urls[2]))
            echo "<img src='http://webtest.cv.nrao.edu/php/ntc/ws-atb/test_datafiles/IFSpectrumLibrary/Band$band Spurious Expanded IF2.png'>";
        echo "</div>";
        break;

    case 'spurious2_3':
        //Spurious Noise (Expanded Plots) tab was selected, IF3 subtab was selected.
        echo "<div>";
        if (isset($ifs->urls[3]))
            echo "<img src='http://webtest.cv.nrao.edu/php/ntc/ws-atb/test_datafiles/IFSpectrumLibrary/Band$band Spurious Expanded IF3.png'>";
        echo "</div>";
        break;

    case 'pwrvar2_0':
        //Power Variation (2 GHz) tab was selected, IF0 subtab was selected.
        echo "<div style='height:750px'><br><br>";
        if (isset($ifs->urls[0]))
            echo "<img src='http://webtest.cv.nrao.edu/php/ntc/ws-atb/test_datafiles/IFSpectrumLibrary/PowerVarBand$band" . "_2 GHz_IF0.png'>";
        echo "</div>";
        break;

    case 'pwrvar2_1':
        //Power Variation (2 GHz) tab was selected, IF1 subtab was selected.
        echo "<div style='height:750px'><br><br>";
        if (isset($ifs->urls[1]))
            echo "<img src='http://webtest.cv.nrao.edu/php/ntc/ws-atb/test_datafiles/IFSpectrumLibrary/PowerVarBand$band" . "_2 GHz_IF1.png'>";
        echo "</div>";
        break;

    case 'pwrvar2_2':
        //Power Variation (2 GHz) tab was selected, IF2 subtab was selected.
        echo "<div style='height:750px'><br><br>";
        if (isset($ifs->urls[2]))
            echo "<img src='http://webtest.cv.nrao.edu/php/ntc/ws-atb/test_datafiles/IFSpectrumLibrary/PowerVarBand$band" . "_2 GHz_IF2.png'>";
        echo "</div>";
        break;

    case 'pwrvar2_3':
        //Power Variation (2 GHz) tab was selected, IF3 subtab was selected.
        echo "<div style='height:750px'><br><br>";
        if (isset($ifs->urls[3]))
            echo "<img src='http://webtest.cv.nrao.edu/php/ntc/ws-atb/test_datafiles/IFSpectrumLibrary/PowerVarBand$band" . "_2 GHz_IF3.png'>";
        echo "</div>";
        break;

    case 'pwrvar31_0':
        //Power Variation (31 MHz) tab was selected, IF0 subtab was selected.
        echo "<div style='height:750px'><br><br>";
        if (isset($ifs->urls[0]))
            echo "<img src='http://webtest.cv.nrao.edu/php/ntc/ws-atb/test_datafiles/IFSpectrumLibrary/PowerVarBand$band" . "_31 MHz_IF0.png'>";
        echo "</div>";
        break;

    case 'pwrvar31_1':
        //Power Variation (31 MHz) tab was selected, IF1 subtab was selected.
        echo "<div style='height:750px'><br><br>";
        if (isset($ifs->urls[1]))
            echo "<img src='http://webtest.cv.nrao.edu/php/ntc/ws-atb/test_datafiles/IFSpectrumLibrary/PowerVarBand$band" . "_31 MHz_IF1.png'>";
        echo "</div>";
        break;

    case 'pwrvar31_2':
        //Power Variation (31 MHz) tab was selected, IF2 subtab was selected.
        echo "<div style='height:750px'><br><br>";
        if (isset($ifs->urls[2]))
            echo "<img src='http://webtest.cv.nrao.edu/php/ntc/ws-atb/test_datafiles/IFSpectrumLibrary/PowerVarBand$band" . "_31 MHz_IF2.png'>";
        echo "</div>";
        break;

    case 'pwrvar31_3':
        //Power Variation (31 MHz) tab was selected, IF3 subtab was selected.
        echo "<div style='height:750px'><br><br>";
        if (isset($ifs->urls[3]))
            echo "<img src='http://webtest.cv.nrao.edu/php/ntc/ws-atb/test_datafiles/IFSpectrumLibrary/PowerVarBand$band" . "_31 MHz_IF3.png'>";
        echo "</div>";
        break;

    case 'totpwr_0':
        //Total Power tab was selected, IF0 subtab was selected.
        echo "<div style='height:750px'><br><br>";
      	$plt = new plotter();
		$plt->setParams(NULL, 'IFSpectrumLibrary', $band);
		$plt->powerTotTables($DataSetGroup, $FEid, 0);
        echo "<br><br><br></div>";
        break;

    case 'totpwr_1':
        //Total Power tab was selected, IF1 subtab was selected.
        echo "<div style='height:750px'><br><br>";
        $plt = new plotter();
		$plt->setParams(NULL, 'IFSpectrumLibrary', $band);
		$plt->powerTotTables($DataSetGroup, $FEid, 1);
        echo "<br><br><br></div>";
        break;

    case 'totpwr_2':
        //Total Power tab was selected, IF2 subtab was selected.
        echo "<div style='height:750px'><br><br>";
        //$ifs->Display_TotalPowerTable(2);
        $plt = new plotter();
        $plt->setParams(NULL, 'IFSpectrumLibrary', $band);
        $plt->powerTotTables($DataSetGroup, $FEid, 2);
        echo "<br><br><br></div>";
        break;

    case 'totpwr_3':
        //Total Power tab was selected, IF3 subtab was selected.
        echo "<div style='height:750px'><br><br>";
        //$ifs->Display_TotalPowerTable(3);
        $plt = new plotter();
        $plt->setParams(NULL, 'IFSpectrumLibrary', $band);
        $plt->powerTotTables($DataSetGroup, $FEid, 3);
        echo "<br><br><br></div>";
        break;

    case 'pwrvarfullband':
        //Power Variation Full Band tab was selected
        echo "<div style='height:750px'><br><br><br><br>";
        $plt = new plotter();
        $plt->setParams(NULL, 'IFSpectrumLibrary', $band);
        $plt->powerVarTables($DataSetGroup, $FEid);
        //$ifs->DisplayPowerVarFullBandTable();
        echo "<br><br><br></div>";
        break;

    case 'dataset0':
        //DataSets tab was selected, IF0 subtab was selected.
        $ifs->DisplayDataSetSelector_Form('0');
        break;

    case 'dataset1':
        //DataSets tab was selected, IF1 subtab was selected.
        $ifs->DisplayDataSetSelector_Form('1');
        break;

    case 'dataset2':
        //DataSets tab was selected, IF2 subtab was selected.
        $ifs->DisplayDataSetSelector_Form('2');
        break;

    case 'dataset3':
        //DataSets tab was selected, IF3 subtab was selected.
        $ifs->DisplayDataSetSelector_Form('3');
        break;
}
echo "</div>";

?>