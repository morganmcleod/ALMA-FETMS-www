<?php
include ('header.php');
include ('mysql_connect.php');
include ('ship_classes.php');

$id = $_REQUEST['id'];
$ShipItem = new Ship_item_class();
$ShipItem->Initialize($id);
 	
if (isset($_REQUEST['submitted']))
 {
 	
 	$ShipItem->RequestValues();
 	if ($ShipItem->Password == $ShipItem->CorrectPassword){
		$ShipItem->UpdateRecord();
		echo '<p>Record has been edited.</p>';	
		echo '<meta http-equiv="Refresh" content="1;url=view_shipment_full_record.php?id=' 
		. $ShipItem->fkShipment . '">';
 	}
 	if ($ShipItem->Password != $ShipItem->CorrectPassword){
		echo '<p>Incorrect Password. Record NOT edited.</p>';	
 	}
 	
 }
 
 
if (!isset($_REQUEST['submitted'])){
	echo '<h1>Edit the Selected Record</h1>';
	$ShipItem->RequestValues();
	$ShipItem->Display_add_form("edit");
}

include ('footer.php');
?>