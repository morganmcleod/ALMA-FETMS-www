<?php

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.wca.php');

$fileName = $_FILES['filedata']['name'];
$tmpName  = $_FILES['filedata']['tmp_name'];
$fileSize = $_FILES['filedata']['size'];
$fileType = $_FILES['filedata']['type'];

$wca = new WCA();
$wca->Initialize_WCA($_REQUEST['id'],$_REQUEST['fc']);
$wca->Upload_INI_file($fileName,$tmpName);

$errorstring = '';
if (count($wca->ErrorArray) > 0){
	$errordetected = 1;
	for ($i = 0; $i < count($wca->ErrorArray); $i++){
		$errorstring .= $wca->ErrorArray[$i] . '\n';
	}
}

echo "{'success':'1','errordetected':'$errordetected','errors':'$errorstring','keyconfig':'$wca->keyId'}";
?>