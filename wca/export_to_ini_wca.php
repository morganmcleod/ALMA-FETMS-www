<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.wca.php');

$fc = isset($_REQUEST['fc']) ? $_REQUEST['fc'] : '';
$id = isset($_REQUEST['keyId']) ? $_REQUEST['keyId'] : '';
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'fec';
// type is expected to be 'fec' or 'wca'

$wca = new WCA();
$wca->Initialize_WCA($id, $fc, WCA::INIT_ALL);

$band = $wca->GetValue('Band');
$fname = ($type=='wca') ? "WCA$band.ini" : "FrontEndControlDLL.ini";

header("Content-type: text/plain");
header("Content-Disposition: attachment; filename=$fname");
header("Pragma: no-cache");
header("Expires: 0");
echo $wca->GetIniFileContent($type);

?>
