<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.cca.php');
require_once($site_classes . '/class.logger.php');

$fname = "FrontEndControlDLL.ini";
header("Content-type: text/plain");
header("Content-Disposition: attachment; filename=$fname");
header("Pragma: no-cache");
header("Expires: 0");

$fc = $_REQUEST['fc'];
$id = $_REQUEST['keyId'];

$cca = new CCA();
$cca->Initialize_CCA($id, $fc, CCA::INIT_ALL);
$result = $cca->getFrontEndControlDLL_ini();
echo $result;
unset($cca);

?>
