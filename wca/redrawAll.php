<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.wca.php');
require_once($site_dbConnect);

$dbConnection = site_getDbConnection();

$fc = '40';
$_REQUEST['fc'] = $fc;

$q = "SELECT fkFE_Component FROM WCAs
      WHERE amnz_avgdsb_url LIKE 'http%' order by keyId limit 2";

$r = mysqli_query($dbConnection, $q);

while ($row = mysqli_fetch_array($r)) {
    $keyWCA = $row[0];
    $_REQUEST['keyId'] = $keyWCA;
    echo $keyWCA . "\n";
    $wca = new WCA($keyWCA, $fc, WCA::INIT_ALL);
    $_REQUEST['draw_all'] = 1;
    $wca->RequestValues_WCA();
    unset($wca);
}
echo "done";
