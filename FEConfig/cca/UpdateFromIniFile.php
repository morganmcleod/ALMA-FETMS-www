<?php

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.cca.php');

$fileName = $_FILES['filedata']['name'];
$tmpName  = $_FILES['filedata']['tmp_name'];

$cca = new CCA();
$cca->Initialize_CCA($_REQUEST['id'],$_REQUEST['fc']);
$cca->RequestValues_CCA($fileName,$tmpName);

$errorstring = '';
$errordetected = 0;
if (count($cca->ErrorArray) > 0){
	$errordetected = 1;
	for ($i = 0; $i < count($cca->ErrorArray); $i++){
		$errorstring .= $cca->ErrorArray[$i] . '\n';
	}
}

echo "{'success':'1','errordetected':'$errordetected','errors':'$errorstring','keyconfig':'$cca->keyId'}";
?>