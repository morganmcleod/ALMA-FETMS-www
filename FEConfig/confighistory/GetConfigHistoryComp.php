<?php
// called from dbGridConfigHistoryComp.js
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.fecomponent.php');

$keyId = $_REQUEST['keyId'];
$fc = $_REQUEST['fc'];
$c = new FEComponent();
$c->Initialize_FEComponent($keyId, $fc);
$c->ComponentHistory_JSON();
unset($c);
?>
