<?php

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.ccapas.php');
require_once($site_classes . '/class.logger.php');

$l= new Logger('CCASPAS.txt');

$l->WriteLogFile('this was called.');

$id = $_REQUEST['ccaid'];
$fc = $_REQUEST['fc'];
$tabtype = $_REQUEST['tabtype'];

$cca = new CCAPAS();
$cca->Initialize_CCA($id, $fc, CCA::INIT_ALL);

switch($tabtype){
	case 'info':
		$cca->DisplayTable_ComponentInformation();
		break;
	case 'ivcurve':
		$cca->Display_IVCurve();
		break;
	case 'ampstab':
		$cca->Display_AmplitudeStability();
		break;
	case 'inbandpower':
		$cca->Display_InBandPower();
		break;
	case 'polaccuracy':
		$cca->Display_PolAccuracy();
		break;
	case 'sidebandratio':
		$cca->Display_SidebandRatio();
		break;
	case 'phasedrift':
		$cca->Display_PhaseDrift();
		break;

}





unset($l);
unset($cca);


?>