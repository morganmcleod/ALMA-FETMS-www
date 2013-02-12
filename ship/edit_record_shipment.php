<?php 
include ('header.php');
include ('mysql_connect.php');
include ('ship_classes.php');

$id = $_REQUEST['id'];
$Shipment = new Shipment_class();
$Shipment->Initialize($id);
 	
if (isset($_REQUEST['submitted']))
 {
 	
 	$Shipment->RequestValues();
 	if ($Shipment->Password == $Shipment->CorrectPassword){
		$Shipment->UpdateRecord();
		echo '<p>Record has been edited.</p>';	
		echo '<meta http-equiv="Refresh" content="1;url=view_shipment_full_record.php?id=' 
		. $Shipment->keyId . '">';
 	}
 	if ($Shipment->Password != $Shipment->CorrectPassword){
		echo '<p>Incorrect Password. Record NOT edited.</p>';	
 	}
 	
 }
 
 
if (!isset($_REQUEST['submitted'])){
	echo '<h1>Edit the Selected Record</h1>';
	$Shipment->RequestValues();
	$Shipment->Display_add_form("edit");
}

include ('footer.php');
?>