<?php

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.wca.php');

$fileName = $_FILES['filedata']['name'];
$tmpName  = $_FILES['filedata']['tmp_name'];

$wca = new WCA($_REQUEST['id'], $_REQUEST['fc'], WCA::INIT_ALL);
$wca->UploadConfiguration($fileName, $tmpName);

$errorstring = '';
$errordetected = 0;
if (count($wca->ErrorArray) > 0) {
    $errordetected = 1;
    for ($i = 0; $i < count($wca->ErrorArray); $i++) {
        $errorstring .= $wca->ErrorArray[$i] . '\n';
    }
}

echo "{'success':'1','errordetected':'$errordetected','errors':'$errorstring','keyconfig':'$wca->keyId'}";
