<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_config_main);
require_once($site_classes . '/class.cca.php');

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

var_dump($output);
// $outfile = $outPath . "TestDataHeaders.csv";
// $handle = fopen($outFile, "w");

// foreach ($output as $row) {

//     echo "$keyId, $configId, $dataDesc, $dataSetGroup, $dataStatusDesc, $testNotes, $testTS<br>";
// }

?>