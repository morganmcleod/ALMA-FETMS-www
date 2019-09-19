<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.wca.php');

$fc = isset($_REQUEST['fc']) ? $_REQUEST['fc'] : '';
$id = isset($_REQUEST['keyId']) ? $_REQUEST['keyId'] : '';
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'fec';
$xmlname = isset($_REQUEST['xmlname']) ? $_REQUEST['xmlname'] : false;
// type is expected to be 'fec', 'wca', or 'xml'

$wca = new WCA();
$wca->Initialize_WCA($id, $fc, WCA::INIT_ALL);

$band = $wca->GetValue('Band');
if ($type == 'wca')
    $fname = "WCA$band.ini";
else if ($type == 'fec')
    $fname = "FrontEndControlDLL.ini";
else if ($type == 'xml')
    $fname = $xmlname ? "$xmlname.xml" : "WCA$band.xml";
else
    $fname = '';

if ($type == 'xml')
    header("Content-type: text/xml");
else
    header("Content-type: text/plain");

header("Content-Disposition: attachment; filename=$fname");
header("Pragma: no-cache");
header("Expires: 0");
if ($type == 'xml')
    echo $wca->GetXmlFileContent();
else
    echo $wca->GetIniFileContent($type);

?>
