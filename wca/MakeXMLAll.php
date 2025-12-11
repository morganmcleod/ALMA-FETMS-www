<?php
// Makes all the WCA XML files for the latest configuration.

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.wca.php');
require_once($site_dbConnect);
require(site_get_config_main());

$dbConnection = site_getDbConnection();

$fc = '40';
$_REQUEST['fc'] = $fc;

$q = "SELECT keyId, LPAD(SN, 2, '0') AS SN, Band, TS
     FROM FE_Components
     WHERE Band IN (1, 2, 3, 4, 5, 6, 7, 8, 9, 10)
     AND Band <> 0
     AND fkFE_ComponentType = 11
     ORDER BY SN ASC;
";

$r = mysqli_query($dbConnection, $q);

while ($row = mysqli_fetch_array($r)) {
    $keyWCA = $row[0];
    $_REQUEST['keyId'] = $keyWCA;
    echo $keyWCA . "\n";
    $wca = new WCA($keyWCA, $fc, WCA::INIT_ALL);

    $band = $wca->Band;
    $SN = $wca->SN;
    $xmlname = hexdec($wca->ESN1);

    $outdir = $wca_write_directory . "xml2025-12-10";
    if (!file_exists($outdir))
        mkdir($outdir);

    $outdir .= "/B$band";
    if (!file_exists($outdir))
        mkdir($outdir);

    if (!$xmlname)
        $fn = $outdir . "/WCA$band-$SN.xml";
    else
        $fn = $outdir . "/$xmlname.xml";

    $xml = $wca->GetXmlFileContent();
    file_put_contents($fn, $xml);

    unset($wca);
}
echo "done";
