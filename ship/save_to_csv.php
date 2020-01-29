<?php

include('mysql_connect.php');
$id = $_REQUEST['id'];
$datatype = $_REQUEST['datatype'];
$RxFrom_selected = $_REQUEST['RxFrom_selected'];
$requestor_selected = $_REQUEST['requestor_selected'];


include('ship_classes.php');

//Create and open the csv file
header("Content-type: application/x-msdownload");
header("Content-Disposition: attachment; filename=exported.csv");
header("Pragma: no-cache");
header("Expires: 0");



//Shipment
if ($datatype == "shipment"){
	$Shipment = new Shipment_class;
	$Shipment->Initialize($id);
	
	
	//Get shipment info
	echo "Shipment Record\r\n";
	echo "Notes: " . $Shipment->Notes . "\r\n";
	echo "Ship Date: " . $Shipment->ShipDate . "\r\n";
	echo "ShipToLocation: " . $Shipment->ShipToLocationInfo . "\r\n\r\n\r\n";
	
	//get ShipItems
	echo "Qty,PN,Title,Vendor,VendorPN,DrawingNumber,AssemblyPN\r\n";
	$q = "Select NetKitQty,PN,Title,Vendor,VendorPN,DrawingNumber,
	AssyPN
	FROM ShipItems
	WHERE fkShipment = $Shipment->keyId";
	$r = mysql_query ($q, $dbc);
	//get data from table
	while($row = mysqli_fetch_array($r)){
		echo str_replace(",",";",$row[0]) . ',' . str_replace(",",";",$row[1]) . ',' . str_replace(",",";",$row[2]) . ',' . str_replace(",",";",$row[3]) . ',' .
		str_replace(",",";",$row[4]) . ',' . str_replace(",",";",$row[5]) . ',' . str_replace(",",";",$row[6]) .  ',' . "\r\n";
	}
}//end shipment


//Ship Items sorted
if ($datatype == "shipitems_sort"){
	$qArray = explode("LIMIT",$_REQUEST['qShipItems']);
	$q = $qArray[0] . ";";
	$r = mysql_query ($q, $dbc);


	
	echo "Selected Ship Items\r\n\r\n\r\n";
	echo "Item,Vendor,PN,Shipment Title,Ship Location,Date\r\n";
	
	while ($row = mysqli_fetch_array($r)) {

		$Title = str_replace(",",";",$row[0]);
		$Vendor = str_replace(",",";",$row[1]);
		if ($row[1]==""){
			$Vendor = "None";
		}
		$PN = $row[2];
		if ($row[2]==""){
			$PN = "None";
		}
		$Notes = str_replace(",",";",$row[3]);
		$ShipToLocationID = str_replace(",",";",$row[4]);
		$ShipDate = str_replace(",",";",$row[5]);
		$id_temp = str_replace(",",";",$row[6]);

		//This part retrieves the description from the Locations table
		$qLoc = "SELECT Description, Notes  FROM Locations WHERE keyId = $ShipToLocationID";		
		$rLoc = mysql_query ($qLoc, $dbc);
		$rowLoc = mysqli_fetch_array ($rLoc);
		$tempLoc = $rowLoc[0] . " (" . $rowLoc[1] . ")";


		echo $Title . ',' . $Vendor . ',' . $PN . ',' . $Notes . ',' . $tempLoc . ',' . $ShipDate . "\r\n";
	} // End of WHILE loop.


}//end ship items sorted


//AllRx
if ($datatype == "allrx"){
	

	echo "Received Items\r\n\r\n\r\n";
	echo "LogNumber,RxDate,Requestor,PO,ContentDescription,Qty,RxFrom,NR,PAS,VI,Damage,PackingList_SOW,Discrepancy,Staging,TIR,TIComplete,LoadedIntoInventory,AssignedLocation\r\n";
	
	//get ShipItems
	/*
	$q =  "SELECT 
		LogNumber,RxDate,Requestor,
		PO,ContentDescription,Qty,
		RxFrom,NR,PAS,
		VI,Damage,PackingList_SOW,
		Discrepancy,Staging,TIR,
		TIComplete,LoadedIntoInventory,AssignedLocation
		FROM ReceivedItems ORDER BY RxDate DESC";
*/
	
	
	$q =  "SELECT 
		LogNumber,RxDate,Requestor,
		PO,ContentDescription,Qty,
		RxFrom,NR,PAS,
		VI,Damage,PackingList_SOW,
		Discrepancy,Staging,TIR,
		TIComplete,LoadedIntoInventory,AssignedLocation
		FROM ReceivedItems 
		WHERE Requestor LIKE '$requestor_selected' 
		AND RxFrom LIKE '$RxFrom_selected'
		ORDER BY RxDate DESC;";
	
	
	
	$r = mysql_query ($q, $dbc);
	//get data from table
	while($row = mysqli_fetch_array($r)){
		echo str_replace(",",";",$row[0]) . ',' . str_replace(",",";",$row[1]) . 
		',' . str_replace(",",";",$row[2]) . ',' . str_replace(",",";",$row[3]) . ',' .
		str_replace("\n",";",str_replace("\r",";",str_replace(",",";",$row[4]))) . ',' . str_replace(",",";",$row[5]) . ',' . str_replace(",",";",$row[6]) .  ',' . 
		str_replace(",",";",$row[7]) . ',' . str_replace(",",";",$row[8]) . ',' . str_replace(",",";",$row[9]) .  ',' . 
		str_replace(",",";",$row[10]) . ',' . str_replace(",",";",$row[11]) . ',' . str_replace(",",";",$row[12]) .  ',' . 
		str_replace(",",";",$row[13]) . ',' . str_replace(",",";",$row[14]) . ',' . str_replace(",",";",$row[15]) .  ',' . 
		str_replace(",",";",$row[16]) . ',' . str_replace(",",";",$row[17]) . ',' . "\r\n";
	}
}//end shipment
?>
