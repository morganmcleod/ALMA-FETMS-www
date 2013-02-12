<?php
// called from dbGridConfigHistory.js
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.frontend.php');

$keyFE = $_REQUEST['keyfe'];
$fc = $_REQUEST['fc'];
$fe = new FrontEnd();
$fe->Initialize_FrontEnd_FromConfig($keyFE, $fc, -1);
$fe->SLN_History_JSON();
?>