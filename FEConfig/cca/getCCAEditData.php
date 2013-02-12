<?php

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.cca.php');
require_once($site_classes . '/class.fecomponent.php');
$fc=40;
if (isset($_REQUEST['fc'])){
	$fc=$_REQUEST['fc'];
}

$selected_key=$_POST['config'];
$tabtype=$_POST['tabtype'];

$fecomponent = new FEComponent();
$fecomponent->Initialize_FEComponent($selected_key,$fc, -1);

$cca = new CCA();
$cca->Initialize_CCA($selected_key,$fc);
switch($tabtype){
	case 2:
		$cca->Display_MixerParams_Edit();
		break;

	case 3:
		$cca->Display_PreampParams_Edit();
		break;

	case 4:
		$cca->Display_TempSensors_Edit();
		break;
}
unset($cca);

?>