<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.wca.php');

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

function makeOutputDirectory($band, $deleteContents = false) {
    require(site_get_config_main());
    $outDir = $main_write_directory . 'pa_limits/';

    if (!file_exists($outDir))
        mkdir($outDir);

    $outDir .= "Band$band/";

    if ($deleteContents && file_exists($outDir))
        deleteDir($outDir);

    if (!file_exists($outDir))
        mkdir($outDir);

    return $outDir;
}

$q = "select Band, SN, max(keyId) from FE_Components
where fkFE_ComponentType = 11 and Band >= 7 and Band <= 9
group by Band, SN;";

$r = @mysql_query($q, site_getDbConnection());
$lastBand = false;

while ($row = @mysql_fetch_array($r)) {
    $band = $row[0];
    $sn = $row[1];
    $id = $row[2];

    $outDir = makeOutputDirectory($band, $band != $lastBand);
    $lastBand = $band;

    if (is_numeric($sn)) {

        $wca = new WCA();
        $wca->Initialize_WCA($id, 40, WCA::INIT_ALL);

        $fname = $outDir . "WCA$band-$sn.ini";

        $f = fopen($fname, 'w');
        fwrite($f, $wca->GetIniFileContent('wca'));
        fclose($f);

        echo "$fname: " . $id . "<br>";
    }
}
echo "done.<br>";

?>
