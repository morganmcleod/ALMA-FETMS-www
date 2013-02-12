<?php 
$page_title = 'View Full Record for the Selected Shipment';
include ('header.php');
	include ('mysql_connect.php'); 
	include ('ship_classes.php');
	
	$id = $_REQUEST['id'];
	echo '<h1>Selected Shipment Record</h1>';
	
	
	$Shipment = new Shipment_class();
	$Shipment->Initialize($id);
	$Shipment->Display_data();
	$Shipment->Import_csv();

@mysql_close($dbc);	
include ('footer.php');
?>