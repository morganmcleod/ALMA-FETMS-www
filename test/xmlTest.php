<?php
date_default_timezone_set ('America/New_York');
ini_set('display_errors', '1');
$errorReportSettingsNo_E_NOTICE = E_ERROR | E_WARNING | E_PARSE;
$errorReportSettingsNormal = $errorReportSettingsNo_E_NOTICE | E_NOTICE;
error_reporting($errorReportSettingsNormal);

$xmlstr = <<<XML
<?xml version="1.0"?>
<ConfigData xsi:noNamespaceSchemaLocation="membuffer.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <ASSEMBLY value="ColdCart9"/>
    <MixerParams VJ12="0" VJ11="1.958" VJ02="0" VJ01="1.959" IJ12="0" IJ11="22.041" IJ02="0" IJ01="20.618" FreqLO="614.00E9"/>
    <MixerParams VJ12="0" VJ11="1.948" VJ02="0" VJ01="1.975" IJ12="0" IJ11="23.445" IJ02="0" IJ01="20.183" FreqLO="622.00E9"/>
    <MixerParams VJ12="0" VJ11="1.946" VJ02="0" VJ01="2.033" IJ12="0" IJ11="22.778" IJ02="0" IJ01="21.282" FreqLO="630.00E9"/>
    <MixerParams VJ12="0" VJ11="2.084" VJ02="0" VJ01="2.136" IJ12="0" IJ11="23.914" IJ02="0" IJ01="20.159" FreqLO="638.00E9"/>
    <MixerParams VJ12="0" VJ11="2.126" VJ02="0" VJ01="2.163" IJ12="0" IJ11="23.415" IJ02="0" IJ01="22.021" FreqLO="646.00E9"/>
    <MixerParams VJ12="0" VJ11="2.138" VJ02="0" VJ01="2.198" IJ12="0" IJ11="23.063" IJ02="0" IJ01="19.939" FreqLO="654.00E9"/>
    <MixerParams VJ12="0" VJ11="2.175" VJ02="0" VJ01="2.214" IJ12="0" IJ11="22.841" IJ02="0" IJ01="22.343" FreqLO="662.00E9"/>
    <MixerParams VJ12="0" VJ11="2.109" VJ02="0" VJ01="2.185" IJ12="0" IJ11="22.681" IJ02="0" IJ01="21.009" FreqLO="670.00E9"/>
    <MixerParams VJ12="0" VJ11="2.058" VJ02="0" VJ01="2.172" IJ12="0" IJ11="23.793" IJ02="0" IJ01="21.651" FreqLO="678.00E9"/>
    <MixerParams VJ12="0" VJ11="2.064" VJ02="0" VJ01="2.122" IJ12="0" IJ11="22.022" IJ02="0" IJ01="21.414" FreqLO="686.00E9"/>
    <MixerParams VJ12="0" VJ11="2.020" VJ02="0" VJ01="2.137" IJ12="0" IJ11="23.249" IJ02="0" IJ01="22.019" FreqLO="694.00E9"/>
    <MixerParams VJ12="0" VJ11="2.010" VJ02="0" VJ01="2.135" IJ12="0" IJ11="21.926" IJ02="0" IJ01="20.267" FreqLO="702.00E9"/>
    <MixerParams VJ12="0" VJ11="1.991" VJ02="0" VJ01="2.086" IJ12="0" IJ11="23.526" IJ02="0" IJ01="21.546" FreqLO="710.00E9"/>
    <MagnetParams FreqLO="614.000E9" IMag12="0" IMag11="10.2" IMag02="0" IMag01="9.1"/>
    <MagnetParams FreqLO="622.000E9" IMag12="0" IMag11="10.2" IMag02="0" IMag01="9.1"/>
    <MagnetParams FreqLO="630.000E9" IMag12="0" IMag11="10.2" IMag02="0" IMag01="9.1"/>
    <MagnetParams FreqLO="638.000E9" IMag12="0" IMag11="10.2" IMag02="0" IMag01="9.1"/>
    <MagnetParams FreqLO="646.000E9" IMag12="0" IMag11="10.2" IMag02="0" IMag01="9.1"/>
    <MagnetParams FreqLO="654.000E9" IMag12="0" IMag11="10.2" IMag02="0" IMag01="9.1"/>
    <MagnetParams FreqLO="662.000E9" IMag12="0" IMag11="10.2" IMag02="0" IMag01="9.1"/>
    <MagnetParams FreqLO="670.000E9" IMag12="0" IMag11="10.2" IMag02="0" IMag01="9.1"/>
    <MagnetParams FreqLO="678.000E9" IMag12="0" IMag11="10.2" IMag02="0" IMag01="9.1"/>
    <MagnetParams FreqLO="686.000E9" IMag12="0" IMag11="10.2" IMag02="0" IMag01="9.1"/>
    <MagnetParams FreqLO="694.000E9" IMag12="0" IMag11="10.2" IMag02="0" IMag01="9.1"/>
    <MagnetParams FreqLO="702.000E9" IMag12="0" IMag11="10.2" IMag02="0" IMag01="9.1"/>
    <MagnetParams FreqLO="710.000E9" IMag12="0" IMag11="10.2" IMag02="0" IMag01="9.1"/>
    <PreampParamsPol0Sb1 FreqLO="690.457E9" VG3="-2.19" VG2="-2.20" VG1="-2.54" VD3="0.5" VD2="0.6" VD1="1.0" ID3="3.52" ID2="3.03" ID1="4.05"/>
    <PreampParamsPol0Sb2 FreqLO="0.0"/>
    <PreampParamsPol1Sb1 FreqLO="690.457E9" VG3="-1.80" VG2="-1.85" VG1="-2.21" VD3="0.6" VD2="0.6" VD1="0.9" ID3="4.03" ID2="4.02" ID1="4.03"/>
    <PreampParamsPol1Sb2 FreqLO="0.0"/>
    <PolarizationOrientation PolYAngle="-1.5707963268" PolXAngle="-3.1415926536"/>
</ConfigData>
XML;

$ConfigData = new SimpleXMLElement($xmlstr);
$assy = (string) $ConfigData->ASSEMBLY['value'];
var_dump($assy);
list($band) = sscanf($assy, "ColdCart%d");
var_dump($band);
$magnetParams = $ConfigData->MagnetParams;
foreach($magnetParams as $param) {
    $FreqLO = (float) $param['FreqLO'] / 1E9;
    var_dump($FreqLO);
}
$preampParams = $ConfigData->PreampParamsPol0Sb1;
foreach($preampParams as $param)
    var_dump($param);

echo "<br><br>-----<br><br>";
var_dump($ConfigData);

?>