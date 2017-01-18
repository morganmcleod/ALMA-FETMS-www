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
require_once($site_classes . '/IFSpectrum/IFSpectrum_impl.php');

$fc = $_REQUEST['fc'];
$FEid = $_REQUEST['fe'];
$band = $_REQUEST['b'];
$keyId = $_REQUEST['id'];
$dataSetGroup = $_REQUEST['g'];
$tabtype = $_REQUEST['tabtype'];

//Instantiate a new IF Spectrum object
$ifspec = new IFSpectrum_impl();

// use its log file to record request params:
// $ifspec -> logger -> WriteLogFile('getIFspectrumplotdata.php: fc=' . $fc . ' FEid=' . $FEid . ' band=' . $band . ' group=' . $dataSetGroup . ' keyID=' . $keyId . ' tabtype=' . $tabtype);

// and initialize it:
$ifspec -> Initialize_IFSpectrum($FEid, $band, $dataSetGroup, $fc);
echo "<div style='background-color:#6C7070;width:1000px;'>";

$feconfig = $ifspec -> FrontEnd -> feconfig_id_latest;

switch($tabtype){
    case 1:
        //Info tab was selected
        $ifspec -> DisplayTDHinfo();
        break;

    case 'spurious_0':
        //Spurious Noise tab was selected, IF0 subtab was selected.
        echo "<div style='height:750px'>";
        if (isset($ifspec  ->  plotURLs[0]))
            echo "<img src='" . $ifspec -> plotURLs[0] -> GetValue('spurious_url2d'). "'>";
        echo "</div>";
        break;

    case 'spurious_1':
        //Spurious Noise tab was selected, IF1 subtab was selected.
        echo "<div style='height:750px'>";
        if (isset($ifspec  ->  plotURLs[1]))
            echo "<img src='" . $ifspec -> plotURLs[1] -> GetValue('spurious_url2d'). "'>";
        echo "</div>";
        break;

    case 'spurious_2':
        //Spurious Noise tab was selected, IF2 subtab was selected.
        echo "<div style='height:750px'>";
        if (isset($ifspec -> plotURLs[2]))
            echo "<img src='" . $ifspec -> plotURLs[2] -> GetValue('spurious_url2d'). "'>";
        echo "</div>";
        break;

    case 'spurious_3':
        //Spurious Noise tab was selected, IF3 subtab was selected.
        echo "<div style='height:750px'>";
        if (isset($ifspec -> plotURLs[3]))
            echo "<img src='" . $ifspec -> plotURLs[3] -> GetValue('spurious_url2d'). "'>";
        echo "</div>";
        break;

    case 'spurious2_0':
        //Spurious Noise (Expanded Plots) tab was selected, IF0 subtab was selected.
        echo "<div>";
        if (isset($ifspec  ->  plotURLs[0]))
            echo "<img src='" . $ifspec -> plotURLs[0] -> GetValue('spurious_url2d2'). "'>";
        echo "</div>";
        break;

    case 'spurious2_1':
        //Spurious Noise (Expanded Plots) tab was selected, IF1 subtab was selected.
        echo "<div>";
        if (isset($ifspec  ->  plotURLs[1]))
            echo "<img src='" . $ifspec -> plotURLs[1] -> GetValue('spurious_url2d2'). "'>";
        echo "</div>";
        break;

    case 'spurious2_2':
        //Spurious Noise (Expanded Plots) tab was selected, IF2 subtab was selected.
        echo "<div>";
        if (isset($ifspec -> plotURLs[2]))
            echo "<img src='" . $ifspec -> plotURLs[2] -> GetValue('spurious_url2d2'). "'>";
        echo "</div>";
        break;

    case 'spurious2_3':
        //Spurious Noise (Expanded Plots) tab was selected, IF3 subtab was selected.
        echo "<div>";
        if (isset($ifspec -> plotURLs[3]))
            echo "<img src='" . $ifspec -> plotURLs[3] -> GetValue('spurious_url2d2'). "'>";
        echo "</div>";
        break;

    case 'pwrvar2_0':
        //Power Variation (2 GHz) tab was selected, IF0 subtab was selected.
        echo "<div style='height:750px'><br><br>";
        if (isset($ifspec -> plotURLs[0]))
            echo "<img src='" . $ifspec -> plotURLs[0] -> GetValue('powervar_2GHz_url'). "'>";
        echo "</div>";
        break;

    case 'pwrvar2_1':
        //Power Variation (2 GHz) tab was selected, IF1 subtab was selected.
        echo "<div style='height:750px'><br><br>";
        if (isset($ifspec -> plotURLs[1]))
            echo "<img src='" . $ifspec -> plotURLs[1] -> GetValue('powervar_2GHz_url'). "'>";
        echo "</div>";
        break;

    case 'pwrvar2_2':
        //Power Variation (2 GHz) tab was selected, IF2 subtab was selected.
        echo "<div style='height:750px'><br><br>";
        if (isset($ifspec -> plotURLs[2]))
            echo "<img src='" . $ifspec -> plotURLs[2] -> GetValue('powervar_2GHz_url'). "'>";
        echo "</div>";
        break;

    case 'pwrvar2_3':
        //Power Variation (2 GHz) tab was selected, IF3 subtab was selected.
        echo "<div style='height:750px'><br><br>";
        if (isset($ifspec -> plotURLs[3]))
            echo "<img src='" . $ifspec -> plotURLs[3] -> GetValue('powervar_2GHz_url'). "'>";
        echo "</div>";
        break;

    case 'pwrvar31_0':
        //Power Variation (31 MHz) tab was selected, IF0 subtab was selected.
        echo "<div style='height:750px'><br><br>";
        if (isset($ifspec -> plotURLs[0]))
            echo "<img src='" . $ifspec -> plotURLs[0] -> GetValue('powervar_31MHz_url'). "'>";
        echo "</div>";
        break;

    case 'pwrvar31_1':
        //Power Variation (31 MHz) tab was selected, IF1 subtab was selected.
        echo "<div style='height:750px'><br><br>";
        if (isset($ifspec -> plotURLs[1]))
            echo "<img src='" . $ifspec -> plotURLs[1] -> GetValue('powervar_31MHz_url'). "'>";
        echo "</div>";
        break;

    case 'pwrvar31_2':
        //Power Variation (31 MHz) tab was selected, IF2 subtab was selected.
        echo "<div style='height:750px'><br><br>";
        if (isset($ifspec -> plotURLs[2]))
            echo "<img src='" . $ifspec -> plotURLs[2] -> GetValue('powervar_31MHz_url'). "'>";
        echo "</div>";
        break;

    case 'pwrvar31_3':
        //Power Variation (31 MHz) tab was selected, IF3 subtab was selected.
        echo "<div style='height:750px'><br><br>";
        if (isset($ifspec -> plotURLs[3]))
            echo "<img src='" . $ifspec -> plotURLs[3] -> GetValue('powervar_31MHz_url'). "'>";
        echo "</div>";
        break;

    case 'totpwr_0':
        //Total Power tab was selected, IF0 subtab was selected.
        echo "<div style='height:750px'><br><br>";
        $ifspec -> Display_TotalPowerTable(0);
        echo "<br><br><br></div>";
        break;

    case 'totpwr_1':
        //Total Power tab was selected, IF1 subtab was selected.
        echo "<div style='height:750px'><br><br>";
        $ifspec -> Display_TotalPowerTable(1);
        echo "<br><br><br></div>";
        break;

    case 'totpwr_2':
        //Total Power tab was selected, IF2 subtab was selected.
        echo "<div style='height:750px'><br><br>";
        $ifspec -> Display_TotalPowerTable(2);
        echo "<br><br><br></div>";
        break;

    case 'totpwr_3':
        //Total Power tab was selected, IF3 subtab was selected.
        echo "<div style='height:750px'><br><br>";
        $ifspec -> Display_TotalPowerTable(3);
        echo "<br><br><br></div>";
        break;

    case 'pwrvarfullband':
        //Power Variation Full Band tab was selected
        echo "<div style='height:750px'><br><br><br><br>";
        $ifspec -> DisplayPowerVarFullBandTable();
        echo "<br><br><br></div>";
        break;

}
echo "</div>";

?>