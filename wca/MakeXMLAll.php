<?php
// Makes all the WCA XML files for the latest configuration.

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.wca.php');
require_once($site_dbConnect);
require(site_get_config_main());

$dbconnection = site_getDbConnection();

$fc = '40';
$_REQUEST['fc'] = $fc;

$q = "SELECT FEC0.keyId
      FROM FE_Components AS FEC0 LEFT JOIN FE_Components AS FEC1
      ON FEC0.Band = FEC1.Band AND FEC0.SN = FEC1.SN AND FEC1.keyId > FEC0.keyId
      WHERE FEC1.keyId IS NULL
      AND FEC0.fkFE_ComponentType = 11
      AND FEC0.Band >= 1 AND FEC0.Band <= 10
      ORDER BY FEC0.Band, FEC0.SN;";

$r = mysqli_query($dbconnection, $q);

while ($row = mysqli_fetch_array($r)) {
    $keyWCA = $row[0];
    $_REQUEST['keyId'] = $keyWCA;
    echo $keyWCA . "\n";
    $wca = new WCA($keyWCA, $fc, WCA::INIT_ALL);

    $band = $wca->Band;
    $SN = $wca->SN;
    $xmlname = hexdec($wca->ESN1);

    $outdir = $wca_write_directory . "xml5";
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
