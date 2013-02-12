<?php 
$page_title = 'View Listing of Shipments';
include ('header_wide.php');
include ('mysql_connect.php');
include ('selectviewoptions_shipments.php');
include('ship_classes.php');



	echo '<h1>Shipments</h1>';
	$ShipmentTable = new ShipmentTable_class;
	
	$ShipmentTable->AssyPNView = "'%'";
	$ShipmentTable->LocationView = "'%'";
	$ShipmentTable->PNView = "'%'";
	$ShipmentTable->VendorView = "'%'";
	$ShipmentTable->StartDateView = "'1900-10-10'";
	$ShipmentTable->EndDateView = "'3000-10-10'";
	$ShipmentTable->StatusView = "'0'";
	
	if (isset($_GET['AssyPNView'])){
		$ShipmentTable->AssyPNView = "'" . $_GET['AssyPNView'] . "'";
		if (($ShipmentTable->AssyPNView == '') || ($ShipmentTable->AssyPNView == "'All'")){
			$ShipmentTable->AssyPNView = "'%'";
		}
		$ShipmentTable->LocationView = $_GET['LocationView'];
		if ($ShipmentTable->LocationView == "All"){
			$ShipmentTable->LocationView = "'%'";
		}
		
		$ShipmentTable->StatusView = $_GET['StatusView'];
	
	
		$ShipmentTable->PNView = "'" . strtoupper($_GET['PNView']) . "%'";
		if ($ShipmentTable->PNView == ''){
			$ShipmentTable->PNView = "'%'";
		}
		
		$ShipmentTable->VendorView = "'" . $_GET['VendorView'] . "'";
		if (($ShipmentTable->VendorView == '') || ($ShipmentTable->VendorView == "'All'")){
			$ShipmentTable->VendorView = "'%'";
		}

		$ShipmentTable->StartDateView = "'" . str_replace("/","-",$_GET['StartDateView']) . "'";
		if ($ShipmentTable->StartDateView == "''"){
			$ShipmentTable->StartDateView = "'1900-10-10'";	
		}
	
		$ShipmentTable->EndDateView = "'" . str_replace("/","-",$_GET['EndDateView']) . "'";
		if ($ShipmentTable->EndDateView == "''"){
			$ShipmentTable->EndDateView = "'3000-10-10'";
		}
	}
	
	$ShipmentTable->ShowTable();

@mysql_close($dbc);
include ('footer.php');
?>