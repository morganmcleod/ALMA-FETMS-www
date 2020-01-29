<?php 
$page_title = 'Add a Shipment to the Database';
include ('header.php');
include ('ship_classes.php');
include ('mysql_connect.php'); 


if (!isset($_REQUEST['submitted']))
 {
 	echo '<b><h1>Enter New Record for a Shipment</h1></b>';
	$Shipment = new Shipment_class();
	$Shipment->ShipDate = date('Y-m-d');
	$Shipment->RequestValues();
	$Shipment->Display_add_form();
 }


if (isset($_REQUEST['submitted']))
 {
 	$Shipment = new Shipment_class();
 	$Shipment->NewRecord();
 	$Shipment->RequestValues();
 	$Shipment->UpdateRecord();
 	

		$filename = $_FILES['csvfile']['name'];
		$filecontents = file(($_FILES['csvfile']['tmp_name']));
		$filesize = sizeof($filecontents);
		
		for($i=0; $i<($filesize); $i++) { 
			$line = trim($filecontents[$i]); 
			$arr = explode(",", $line); 
	        $NetKitQty=str_replace('"','',$arr[0]);
	        $Stock=str_replace('"','',$arr[1]);
	        $Unit=str_replace('"','',$arr[2]);
	        $PN=str_replace('"','',$arr[3]);
	        $Title=str_replace('"','',$arr[4]);
	        $Vendor=str_replace('"','',$arr[5]);
	        $VendorPN=str_replace('"','',$arr[6]);
	        $DrawingNumber=str_replace('"','',$arr[7]);
	        $StorageLocation=str_replace('"','',$arr[8]);
	        $AssyPN=str_replace('"','',$arr[9]);
	        
	  if (strtolower($Vendor) != "vendor"){     
	  
	        
	    //Add new record to ComponentTypes table, if there is not one
	    //already for this ProductTreeNumber (AssyPN)
		$qDescr = "SELECT keyId FROM ComponentTypes WHERE ProductTreeNumber = '$AssyPN'";		
		$rDescr = mysql_query ($qDescr, $dbc);
		//echo "NumRows: " . mysqli_num_rows($rDescr);
		if (mysqli_num_rows($rDescr) < 1){
			//echo $tempPTN . ", " . $Description . "<br>";
			$qInsert = $q = "INSERT INTO ComponentTypes (ProductTreeNumber, Description) 
			VALUES ('$AssyPN', '$Title')";	
			$rInsert = mysql_query ($qInsert, $dbc);
			$rDescr = mysql_query ($qDescr, $dbc); 
		}
	   
		//keyId of ComponentType is also fkComponentType for new record
		$row_fkCtype = mysqli_fetch_array($rDescr);
		$fkComponentType = $row_fkCtype[0];	

		
	        $q = "INSERT INTO ShipItems (fkShipment, NetKitQty,Stock,Unit,PN,Title,Vendor,VendorPN,
			 DrawingNumber,StorageLocation,AssyPN,ProductTreeNumber, fkComponentType) VALUES 
			('$Shipment->keyId', '$NetKitQty','$Stock','$Unit','$PN','$Title','$Vendor','$VendorPN',
			 '$DrawingNumber','$StorageLocation','$AssyPN','$PTN','$fkComponentType')";		
			$r = mysql_query ($q, $dbc); // Run the query. 
		}//end if isnumeric PN
		}//end for loop
		
		
		$qGetLatestRecord = "SELECT keyId FROM Shipments ORDER BY keyId DESC LIMIT 1";		
		$rGetLatestRecord = mysql_query ($qGetLatestRecord, $dbc);
		$rowGetLatestRecord = mysqli_fetch_array($rGetLatestRecord);
		$keyIdJustCreated = $rowGetLatestRecord[0];	


	echo '<h1>Thank you!</h1>
	<p>You have entered a new record into the table.</p><p><br /></p>';
	unlink($filename);
	echo '<meta http-equiv="Refresh" content="1;url=view_shipment_full_record.php?id=' . $Shipment->keyId . '">';	
	include ('footer.php'); 
 }//end if ISSET 
	
include ('footer.php');
?>