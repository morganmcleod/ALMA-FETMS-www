<?php
include('mysql_connect.php');
include('class.generictable.php');
include('class.regmodule.php');

$keyId = $_REQUEST['keyId'];


$RegMod = new RegulatorModule;
$RegMod ->Initialize_RegulatorModule($keyId,$dbc);
$RegMod->RequestValues();

if (isset($_REQUEST['submitted'])){
	if ($RegMod->keyId == ''){
		$RegMod->NewRecord_RegulatorModule();
		$RegMod->RequestValues();
	}
	$RegMod->Update();
	echo "Record Updated<br><br>";
}
$RegMod->DisplayData_RegulatorModule();
unset($RegMod);

?>