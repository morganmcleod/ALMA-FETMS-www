<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_NT . '/noisetempcalc.php');

$NT = new NTCalc();
$NT->setParams(6, 0);
$NT->testDataRet();
$NT->calcNoiseTemp();
$NT->print_data();
?>