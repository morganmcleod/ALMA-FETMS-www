<?php
// Makes all the WCA XML files for the latest configuration.

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.wca.php');
require_once($site_dbConnect);
require (site_get_config_main());

$dbconnection = site_getDbConnection();

$fc = '40';
$_REQUEST['fc'] = $fc;

$q = "SELECT fkFE_Component FROM WCAs order by keyId";

$r = mysqli_query($dbconnection, $q);

while ($row = mysqli_fetch_array($r)) {
    $keyWCA = $row[0];
    $_REQUEST['keyId'] = $keyWCA;
    echo $keyWCA . "\n";
    $wca = new WCA;
    $wca->Initialize_WCA($keyWCA, $fc, WCA::INIT_ALL);

    $band = $wca->GetValue('Band');
    $SN = $wca->GetValue('SN');
    $xmlname = hexdec($wca->GetValue('ESN1'));

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

?>
