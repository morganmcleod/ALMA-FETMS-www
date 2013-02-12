<?php
include ('class.generictable.php');
include ('class.ifswitch_output_cable.php');

echo "<a href='ifcable_list.php'>Back to Cable list</a><br>";

$IFcable = new IFswitch_output_cable;
$IFcable ->Initialize_IFcable($_REQUEST['keyId']);
$IFcable ->RequestValues_IFCable();


if (isset($_REQUEST['submitted'])){
	if ($IFcable->GetValue('keyId') == '0'){	
		$IFcable ->NewRecord_IFcable();
		$temp_keyId = $IFcable->GetValue('keyId');
		$IFcable ->RequestValues_IFCable();
		$IFcable->SetValue('keyId',$temp_keyId);
	}

	$IFcable->Update_IFcable();
	echo "Record Updated<br><br>";
}

$IFcable ->DisplayData_IFcable();
unset($IFcable );
?>