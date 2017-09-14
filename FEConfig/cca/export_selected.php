<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html401/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<link href="../images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
<body id = 'body' BGCOLOR='#19475E'>
<font color='#F0F0F0'>

<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_config_main);
require_once($site_classes . '/class.cca.php');
require_once($site_classes . '/class.testdata_table.php');
require_once($site_classes . '/IFSpectrum/IFSpectrum_impl.php');
require_once($site_classes . '/class.eff.php');
require_once($site_classes . '/class.finelosweep.php');
require_once($site_classes . '/class.noisetemp.php');

function deleteDir($dirPath) {
    if (!is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            self::deleteDir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}

$fc = $_REQUEST['fc'];
$id = $_REQUEST['keyId'];

$cca = new CCA();
$cca->Initialize_CCA($id, $fc, CCA::INIT_ALL);

$outPath = $main_write_directory . "ccaExport/";

if (!file_exists($outPath))
    mkdir($outPath);

$ccaName = "CCA" . $cca->GetValue('Band') . "-" . str_pad($cca->GetValue('SN'), 2, "0", STR_PAD_LEFT);

$outPath .= $ccaName . "/";

if (file_exists($outPath))
    deleteDir($outPath);

if (!file_exists($outPath))
    mkdir($outPath);

echo "Exporting selected results for $ccaName <br> to $outPath...<br><br>";

$outFile = $outPath . "FrontEndControlDLL.ini";
$handle = fopen($outFile, "w");
fwrite($handle, $cca->getFrontEndControlDLL_ini());
fclose($handle);

$td = new TestDataTable($cca->GetValue('Band'));
$td->setComponent($cca->GetValue('fkFE_ComponentType'), $cca->GetValue('SN'));
$r = $td->fetchTestDataHeaders(true);
$output = $td->groupHeaders($r);

$outFile = $outPath . "TestDataHeaders.csv";
$handle = fopen($outFile, "w");
fwrite($handle, "configId, tdhId, dataStatusDesc, group, description, link, TS, export\n");

foreach ($output as $row) {
    $destFile = "";
    switch($row['testDataType']) {
        case 7:     //IF Spectrum
            // Make a new IF Spectrum object
            $obj = new IFSpectrum_impl();
            $obj->Initialize_IFSpectrum(0, $cca->GetValue('Band'), $row['group'], $row['tdhId']);
            $destFile = $obj->Export($outPath);
            unset($obj);
            break;

        case 55:    // Beam Patterns
            $obj = new eff();
            $obj->Initialize_eff_TDH($row['tdhId']);
            $destFile = $obj->Export($outPath);
            unset($obj);
            break;

        case 58:    //Noise Temperature
            $obj = new NoiseTemperature();
            $obj->Initialize_NoiseTemperature($row['tdhId'], $fc);
            $destFile = $obj->Export($outPath);
            unset($obj);
            break;

        case 59:    //Fine LO Sweep
            $obj = new FineLOSweep();
            $obj->Initialize_FineLOSweep($row['tdhId'], $fc);
            $destFile = $obj->Export($outPath);
            unset($obj);
            break;

        default:
            $obj = new TestData_header();
            $obj->Initialize_TestData_header($row['tdhId'], $fc);
            $destFile = $obj->Export($outPath);
            unset($obj);
            break;
    }
    fwrite($handle, $row['configId']);
    fwrite($handle, ", ");
    fwrite($handle, $row['tdhId']);
    fwrite($handle, ", ");
    fwrite($handle, $row['dataStatusDesc']);
    fwrite($handle, ", ");
    fwrite($handle, $row['testDataType']);
    fwrite($handle, ", ");
    fwrite($handle, $row['description']);
    fwrite($handle, ", ");
    fwrite($handle, $row['group']);
    fwrite($handle, ", ");
    fwrite($handle, $row['link']);
    fwrite($handle, ", ");
    fwrite($handle, $row['TS']);
    fwrite($handle, ", ");
    fwrite($handle, basename($destFile));
    fwrite($handle, "\n");
}
fclose($handle);

echo "<br>Done.";

echo '<meta http-equiv="Refresh" content="4;url=../ShowComponents.php?conf='.$id.'&fc='.$fc.'">';
?>
</font>
</body>
</html>
