<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_config_main);
require_once($site_classes . '/class.cca.php');
require_once($site_classes . '/class.testdata_table.php');
require_once($site_classes . '/IFSpectrum/IFSpectrum_impl.php');
require_once($site_classes . '/class.eff.php');

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

// echo $ccaName . "<br>" . $outPath;

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
fwrite($handle, "configId, tdhId, dataStatusDesc, group, description, link, TS\n");

foreach ($output as $row) {
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
    fwrite($handle, "\n");

    switch($row['testDataType']) {
        case 7:     //IF Spectrum
            // Make a new IF Spectrum object
            $ifspec = new IFSpectrum_impl();
            $ifspec->Initialize_IFSpectrum(0, $cca->GetValue('Band'), $row['group'], $row['tdhId']);
            $ifspec->Export($outPath);
            unset($ifspec);
            break;

        case 55:    // Beam Patterns
            $eff = new eff();
            $eff->Initialize_eff_TDH($row['tdhId']);
            $eff->Export($outPath);
            unset($eff);
            break;

        case 56:    //Pol Angles
            break;

        case 57:    //LO Lock Test
            break;

        case 58:    //Noise Temperature
            break;

        case 59:    //Fine LO Sweep
            break;

        default:
            break;
    }
}
fclose($handle);
// var_dump($output);

?>