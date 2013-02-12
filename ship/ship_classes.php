<?php
class Shipment_class{
	
	var $keyId;
	var $Notes;
	var $ShipToLocation;
	var $ShipDate;
	var $Title;
	var $SICL;
	var $Status;
	var $StatusDescription;
	var $ShipToLocationInfo;
	var $TRF;
	var $TrackingNumber;
	var $TrackingNumber2;
	var $TrackingNumber3;
	var $TrackingNumber4;
	var $TrackingNumber5;
	var $TrackingNumber6;
	var $TrackingNumber7;
	var $TrackingNumber8;
	var $TrackingNumber9;
	var $TrackingNumber10;
	var $Shipper;
	var $shipper_selected;
	
	var $Password;
	var $CorrectPassword;
	
	
	function Initialize($in_keyId){
		include ('mysql_connect.php');
		$this->CorrectPassword = "nrao1234";
		
		$qInit = "SELECT Notes,ShipToLocation,ShipDate,Title,SICL,Status,TRF,TrackingNumber,
		TrackingNumber2,TrackingNumber3,TrackingNumber4,TrackingNumber5,TrackingNumber6,
        TrackingNumber7,TrackingNumber8,TrackingNumber9,TrackingNumber10,Shipper
		FROM Shipments WHERE keyId = $in_keyId;";
		$rInit = @mysql_query($qInit,$dbc);
		$rowInit = @mysql_fetch_array($rInit);
		$this->keyId = $in_keyId;
		$this->Notes = $rowInit[0];
		$this->ShipToLocation = $rowInit[1];
		
		$Location = new Location_class();
		$Location->Initialize($this->ShipToLocation);
		$this->ShipToLocationInfo = $Location->Info;
		$this->ShipDate = $rowInit[2];	
		$this->Title = $rowInit[3];
		$this->SICL = $rowInit[4];
		$this->Status = $rowInit[5];
		$this->TRF = $rowInit[6];
		$this->TrackingNumber = $rowInit[7];
		$this->TrackingNumber2 = $rowInit[8];
		$this->TrackingNumber3 = $rowInit[9];
		$this->TrackingNumber4 = $rowInit[10];
		$this->TrackingNumber5 = $rowInit[11];
		$this->TrackingNumber6 = $rowInit[12];
		$this->TrackingNumber7 = $rowInit[13];
		$this->TrackingNumber8 = $rowInit[14];
		$this->TrackingNumber9 = $rowInit[15];
		$this->TrackingNumber10 = $rowInit[16];
		$this->Shipper = $rowInit[17];
		
		
		$this->StatusDescription = "Has Shipped";
		if ($this->Status != '0'){
			$this->StatusDescription = "Has Not Shipped";
		}
	}
	
	function UpdateRecord(){
		include ('mysql_connect.php');
		$q = "UPDATE Shipments SET Notes='$this->Notes',ShipToLocation='$this->ShipToLocation',
		ShipDate='$this->ShipDate',Title='$this->Title',SICL='$this->SICL',Status='$this->Status',
		TRF='$this->TRF',Shipper='$this->Shipper',
		TrackingNumber = '$this->TrackingNumber',TrackingNumber2 = '$this->TrackingNumber2',
		TrackingNumber3 = '$this->TrackingNumber3',TrackingNumber4 = '$this->TrackingNumber4',
		TrackingNumber5 = '$this->TrackingNumber5',TrackingNumber6 = '$this->TrackingNumber6',
		TrackingNumber7 = '$this->TrackingNumber7',TrackingNumber8 = '$this->TrackingNumber8',
		TrackingNumber9 = '$this->TrackingNumber9',TrackingNumber10 = '$this->TrackingNumber10'
		WHERE keyId='$this->keyId' LIMIT 1";
		$r = @mysql_query ($q, $dbc);
	}
	
	
	
	function Delete_all_ship_items(){
		include ('mysql_connect.php');
		$q="DELETE FROM ShipItems WHERE fkShipment = $this->keyId;";
		$r=@mysql_query($q,$dbc);	
	}
	
	
	function Display_data(){
		include ('mysql_connect.php');
		
		//Get ShipLocation description
		$LocationNumber = $this->ShipToLocation;
		$qLocation = "SELECT Description, Notes FROM Locations WHERE keyId=$LocationNumber";		
		$rLocation = @mysql_query ($qLocation, $dbc);
		$rowLocation = mysql_fetch_array ($rLocation, MYSQL_NUM);
		$ShipToLocation = $rowLocation[0] . ' (' . $rowLocation[1] . ')';
		
	
		echo '
		<b><form action="view_frontend_full_record.php" method="post">
		<p><b><li><td align="left"><a href="edit_record_shipment.php?id=' . $this->keyId . '">Edit this record</li></td>
		<li><td align="left"><a href="delete_record_shipment.php?id=' . $this->keyId . '">
		Delete this record</a></td></li></p></b>
		<font size="+1">
		<p><div style="width:600px;height:100%;border:1px solid black;background-color: #fffff3"></p>
		<p>Title: <font color="#000066">'. $this->Title. '</font></p>
		<p>SICL: <font color="#000066">'. $this->SICL. '</font></p>
		<p>TRF/RMA#: <font color="#000066">'. $this->TRF. '</font></p>
		<p>Tracking Numbers
		<br>1: <font color="#000066">'. $this->TrackingNumber . '</font>
		<br>2: <font color="#000066">'. $this->TrackingNumber2 . '</font>
		<br>3: <font color="#000066">'. $this->TrackingNumber3 . '</font>
		<br>4: <font color="#000066">'. $this->TrackingNumber4 . '</font>
		<br>5: <font color="#000066">'. $this->TrackingNumber5 . '</font>
		<br>6: <font color="#000066">'. $this->TrackingNumber6 . '</font>
		<br>7: <font color="#000066">'. $this->TrackingNumber7 . '</font>
		<br>8: <font color="#000066">'. $this->TrackingNumber8 . '</font>
		<br>9: <font color="#000066">'. $this->TrackingNumber9 . '</font>
		<br>10: <font color="#000066">'. $this->TrackingNumber10 . '</font></p>
		<p>Shipper: <font color="#000066">'. $this->Shipper . '</font></p>
		
		<p><a href = "shippingchecklist.php?id=' . $this->keyId . '"><img src="pics/createsiclbutton.bmp"></a>
		<p><a href="http://edm.alma.cl/forums/alma/dispatch.cgi/iptfedocs/showMoreFolder/106637/0/def/f62a">
		<i>(Click here for SICL documents on ALMA EDM)</i></a></p>
		
		<p>ShipToLocation: <font color="#000066">'. $this->ShipToLocationInfo. '</font></p>
		<p>Date: <font color="#000066">'. $this->ShipDate . '</font></p>
		
		<p>Notes: <font color="#000066">'. stripslashes($this->Notes) . '</font></p>';
		
		if ($this->StatusDescription == "Has Not Shipped"){
		echo '<p>Status: <font color="#000066">'. $this->StatusDescription . '</font></p>';
		}
		
		
		echo '
		<br></b>
		</div>
		<br>
		</font></form>';
	
	//Display ShipItems	
	$q = "SELECT keyId 
	FROM ShipItems 
	WHERE fkShipment=$this->keyId";	
	
	echo $q . "<br>";
	$r = @mysql_query ($q, $dbc);
	
	echo "<h1>Shipment Items</h1><br>";
	$datatype = "shipment";
	$id = $this->keyId;
	include('save_to_csvfile_form.php');
	echo "<a href='delete_all_shipitems.php?id=$this->keyId'>
	<img src='pics/deleteallshipitemsbutton.bmp'>
	</a>";
	
	// Table header:
	echo '<b><table align="center" cellspacing="1" cellpadding="5" width="100%" bgcolor="#000000">
	<tr bgcolor="#ffff66" font color = "#000000">
		<td align="center"><b>Qty</b></td>
		<td align="center"><b>P/N</b></td>
		<td align="center"><b>Item</b></td>
		<td align="center"><b>Vendor</b></td>
		<td align="center"><b>Vendor P/N</b></td>
		<td align="center"><b>Drawing</b></td>
		<td align="center"><b>Assy P/N</b></td>
		<td align="center"><b>Delete</b></td>
	</tr></b>';
	
	
	// Fetch and print all the records....
	$bg = '#eeeeee'; 
	while ($row = mysql_fetch_array($r)) {
		$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee');
		
		
			
			$ship_item = new Ship_item_class();
			$ship_item->Initialize($row[0]);
	
			//if ((strtolower($ship_item->Vendor) != "vendor") && ($ship_item->Vendor != "")){
			if ((strtolower($ship_item->Vendor) != "vendor")){	
				echo '<tr bgcolor="' . $bg . '">';
				echo "
				<td align='center'>" . $ship_item->NetKitQty . "</td>
				
				<td align='center'>
				<a href='edit_record_shipitem.php?id=$ship_item->keyId'>
				" . str_replace('"','',$ship_item->PN) . "</a></td>
				<td align='center'>
				<a href='edit_record_shipitem.php?id=$ship_item->keyId'>
				" . str_replace('"','',$ship_item->Title) . "</a></td>
				<td align='center'>" . str_replace('"','',$ship_item->Vendor) . "</td>
				<td align='center'><b>" . str_replace('"','',$ship_item->VendorPN) . "</b></td>
				<td align='center'>" . str_replace('"','',$ship_item->DrawingNumber) . "</td>
				<td align='center'>" . str_replace('"','',$ship_item->AssyPN) . "</td>
				<td align='center'>
				<a href='delete_record_shipitem.php?id=$ship_item->keyId'>
				Delete</a></td>
				</tr>";	
			}
			
	} // End of WHILE loop.
	echo '</table>';
	

	
	mysql_close($dbc);
		
		
		
		
	}
	
	public function Display_add_form($action = "add"){
		include('mysql_connect.php');
		echo '
		<form name = "shipment" enctype="multipart/form-data" action="' . $_SERVER["PHP_SELF"] . '" method="post">
		<p>Title: <input type="text" name="Title" size="60" maxlength="200" value="'.$this->Title.'" /></p>
		<p>SICL: <input type="text" name="SICL" size="60" maxlength="200" value="'.$this->SICL.'" /></p>
		<p>TRF/RMA#: <input type="text" name="TRF" size="20" maxlength="200" value="'.$this->TRF.'" /></p>';
		
		
		
		
		
		
		
		
		echo '<p>Shipper:'; 
		
		echo '
		<select name = "shipper_selected" onChange= "submit()">';
			
			$qshipper = "SELECT DISTINCT(Shipper) 
						FROM Shipments 
						WHERE Shipper <> ''
						ORDER BY Shipper ASC";		
			$rshipper = @mysql_query ($qshipper, $dbc);
			$option_shipper .= "<option value='Other' selected = 'selected' >Other</option>";	
			while ($rowshipper = mysql_fetch_array($rshipper)) {
			
						if ($rowshipper[0] == $this->Shipper){	
				   		    $option_shipper .= "<option value='$rowshipper[0]' selected = 'selected' >$rowshipper[0]</option>";
						}
						else{
						$option_shipper .= "<option value='$rowshipper[0]'>$rowshipper[0]</option>";	
						}
				} 
			echo $option_shipper;
		echo '</select>';
		
		if (($this->shipper_selected == "Other") && ($_REQUEST['editing'] != 1)){
		//if ($this->shipper_selected == "Other"){	
		echo "<input type='text' name='Shipper' size='30' maxlength='200' value='$this->Shipper' /></p>";
		}
		
		echo '
		<p>Tracking Numbers: <br>
		<br>&nbsp;&nbsp;1:<input type="text" name="TrackingNumber" size="30" maxlength="200" value="'.$this->TrackingNumber.'" />
		<br>&nbsp;&nbsp;2:<input type="text" name="TrackingNumber2" size="30" maxlength="200" value="'.$this->TrackingNumber2.'" />
		<br>&nbsp;&nbsp;3:<input type="text" name="TrackingNumber3" size="30" maxlength="200" value="'.$this->TrackingNumber3.'" />
		<br>&nbsp;&nbsp;4:<input type="text" name="TrackingNumber4" size="30" maxlength="200" value="'.$this->TrackingNumber4.'" />
		<br>&nbsp;&nbsp;5:<input type="text" name="TrackingNumber5" size="30" maxlength="200" value="'.$this->TrackingNumber5.'" />
		<br>&nbsp;&nbsp;6:<input type="text" name="TrackingNumber6" size="30" maxlength="200" value="'.$this->TrackingNumber6.'" />
		<br>&nbsp;&nbsp;7:<input type="text" name="TrackingNumber7" size="30" maxlength="200" value="'.$this->TrackingNumber7.'" />
		<br>&nbsp;&nbsp;8:<input type="text" name="TrackingNumber8" size="30" maxlength="200" value="'.$this->TrackingNumber8.'" />
		<br>&nbsp;&nbsp;9:<input type="text" name="TrackingNumber9" size="30" maxlength="200" value="'.$this->TrackingNumber9.'" />
		<br>10:<input type="text" name="TrackingNumber10" size="30" maxlength="200" value="'.$this->TrackingNumber10.'" />
		
		</p>';
		
		$LocationSelector = new Location_selector_class();
		$LocationSelector->Display_selector($this->ShipToLocation);
		
		
		
		
		echo '
		</select></p>
		<p>Notes: <br><textarea rows="5" cols="50" name="Notes" size="60" maxlength="80" />'.$this->Notes.'</textarea></p>
		<p>Date: <input type="text" name="Date" size="10" maxlength="10" 
		value="' . $this->ShipDate . '"/></p>
		
		<p>';
		$StatusSelector = new Status_selector_class();
		$StatusSelector->Display_selector($this->Status);
		
		if ($action == "add"){
		
		
		echo '</p>
		<p>CSV file: <input name="csvfile" type="file" /></p>
		<p>
		<a href="example_format.csv"><i>(Click here for example CSV file format)</i></a></p>';
		}
		if ($action == "edit"){
		echo '
		<p>Password:<input type="text" name="Password" size="30" maxlength="200"  /></p>';
		}
		
		/*
		echo '
		<p><input type="image" src="pics/submit.bmp" name="submit" value="SUBMIT" />
		<input type="hidden" name="submitted" value="TRUE" />
		</p>';
		*/
		
		echo '<br><br><input type="submit" name = "submitted" value="Submit">';
		
		echo '
		<input type="hidden" name="id" value="'.$this->keyId.'" /></b>';
	}
	
	
	public function Import_csv(){
		if (isset($_REQUEST['submitted'])){
		
			
		$filename = $_FILES['csvfile']['name'];
		$filecontents = file(($_FILES['csvfile']['tmp_name']));
		$filesize = sizeof($filecontents);
		
		echo "Filesize= $filesize<br>";
		
		for($i=0; $i<($filesize); $i++) {
			
			
			$line = trim($filecontents[$i]); 
			$arr = explode(",", $line); 

	  		if (strtolower($tempShipItem->$arr[5]) != "vendor"){    
				$tempShipItem = new Ship_item_class(); 
				$tempShipItem->NewRecord();
				
			
				 
		        $tempShipItem->NetKitQty=str_replace('"','',$arr[0]);
		        
		        
		        $tempShipItem->Stock=mysql_real_escape_string(str_replace('"','',$arr[1]));
		        $tempShipItem->Unit=mysql_real_escape_string(str_replace('"','',$arr[2]));
		        $tempShipItem->PN=mysql_real_escape_string(str_replace('"','',$arr[3]));
		        $tempShipItem->Title=mysql_real_escape_string(str_replace('"','',$arr[4]));
		        $tempShipItem->Vendor=mysql_real_escape_string(str_replace('"','',$arr[5]));
		        $tempShipItem->VendorPN=mysql_real_escape_string(str_replace('"','',$arr[6]));
		        $tempShipItem->DrawingNumber=mysql_real_escape_string(str_replace('"','',$arr[7]));
		        $tempShipItem->StorageLocation=mysql_real_escape_string(str_replace('"','',$arr[8]));
		        $tempShipItem->AssyPN=mysql_real_escape_string(str_replace('"','',$arr[9]));
	  			
			    //Add new record to ComponentTypes table, if there is not one
			    //already for this ProductTreeNumber (AssyPN)
				$qDescr = "SELECT keyId FROM ComponentTypes WHERE ProductTreeNumber = '$AssyPN'";
				$rDescr = @mysql_query ($qDescr, $dbc);
				//echo "NumRows: " . @mysql_num_rows($rDescr);
				
				
				if (@mysql_num_rows($rDescr) < 1){
					//echo $tempPTN . ", " . $Description . "<br>";
					$qInsert = $q = "INSERT INTO ComponentTypes (ProductTreeNumber, Description) 
					VALUES ('$AssyPN', '$Title')";	
					

					
					$rInsert = @mysql_query ($qInsert, $dbc);
					$rDescr = @mysql_query ($qDescr, $dbc); 
				}
			   
				//keyId of ComponentType is also fkComponentType for new record
				$row_fkCtype = mysql_fetch_array($rDescr);
		
				$tempShipItem->fkComponentType= $row_fkCtype[0];	
				$tempShipItem->fkShipment=$this->keyId;
				$tempShipItem->UpdateRecord();
				
				
				
				echo '<meta http-equiv="Refresh" content="1;
				url=view_shipment_full_record.php?id=' . $this->keyId . '">';
				
				
				
			}//end if vendor

			unset($tempShipItem);
			
		}//end for loop
			
			mysql_close($dbc);
			unlink($filename);
			
		}
		
		if (!isset($_REQUEST['submitted'])){
			echo '
			<br><br>
			<h1>Upload CSV File</h2><br>
			<form name = "shipment" enctype="multipart/form-data" 
			action="' . $_SERVER["PHP_SELF"] . '" method="post">
			
			<p>CSV file: <input name="csvfile" type="file" /></p>
			<p>
			<a href="example_format.csv"><i>(Click here for example CSV file format)</i></a></p>
			<p><input type="submit" value="Submit"></p>
			<input type="hidden" name="submitted" value="TRUE" />
			<input type="hidden" name="id" value="'.$this->keyId.'" /></b>';
		}
	}
	
	
	
	public function RequestValues(){
		if (isset($_REQUEST['Notes'])){
			$this->Notes = trim(mysql_real_escape_string($_REQUEST['Notes']));
		}
		if (isset($_REQUEST['Location'])){	
			$this->ShipToLocation = $_REQUEST['Location'];
		}
		
		if (isset($_REQUEST['Date'])){
			$this->ShipDate = str_replace("/","-",mysql_real_escape_string($_REQUEST['Date']));
		}
		if (isset($_REQUEST['Title'])){
			$this->Title = trim(mysql_real_escape_string($_REQUEST['Title']));	
		}
		if (isset($_REQUEST['SICL'])){
			$this->SICL = trim(mysql_real_escape_string($_REQUEST['SICL']));
		}	
		if (isset($_REQUEST['TRF'])){			
			$this->TRF = trim($_REQUEST['TRF']);	
		}
		if (isset($_REQUEST['Status'])){
			$this->Status = $_REQUEST['Status'];
		}
		if (isset($_REQUEST['TrackingNumber'])){	
			$this->TrackingNumber = $_REQUEST['TrackingNumber'];
		}
		if (isset($_REQUEST['TrackingNumber2'])){	
			$this->TrackingNumber2 = $_REQUEST['TrackingNumber2'];
		}
		if (isset($_REQUEST['TrackingNumber3'])){
			$this->TrackingNumber3 = $_REQUEST['TrackingNumber3'];
		}
		if (isset($_REQUEST['TrackingNumber4'])){	
			$this->TrackingNumber4 = $_REQUEST['TrackingNumber4'];
		}
		if (isset($_REQUEST['TrackingNumber5'])){	
			$this->TrackingNumber5 = $_REQUEST['TrackingNumber5'];
		}
		if (isset($_REQUEST['TrackingNumber6'])){	
			$this->TrackingNumber6 = $_REQUEST['TrackingNumber6'];
		}
		if (isset($_REQUEST['TrackingNumber7'])){	
			$this->TrackingNumber7 = $_REQUEST['TrackingNumber7'];
		}
		if (isset($_REQUEST['TrackingNumber8'])){	
			$this->TrackingNumber8 = $_REQUEST['TrackingNumber8'];
		}
		if (isset($_REQUEST['TrackingNumber9'])){
			$this->TrackingNumber9 = $_REQUEST['TrackingNumber9'];
		}
		if (isset($_REQUEST['TrackingNumber10'])){	
			$this->TrackingNumber10 = $_REQUEST['TrackingNumber10'];
		}
		if (isset($_REQUEST['Shipper'])){	
			$this->Shipper = $_REQUEST['Shipper'];
		}
		
		$this->shipper_selected = "Other";
		if (isset($_REQUEST['shipper_selected'])){	
			$this->shipper_selected = $_REQUEST['shipper_selected'];
			if (($_REQUEST['shipper_selected'] != "Other") && $_REQUEST['editing'] != 1){
			$this->Shipper = $_REQUEST['shipper_selected'];
			}
		}
		if (isset($_REQUEST['Password'])){	
			$this->Password = $_REQUEST['Password'];
		}
		
	}
	
	public function NewRecord(){
		include('mysql_connect.php');
		$qNew = "INSERT INTO Shipments() VALUES();";
		$rNew = @mysql_query($qNew,$dbc);
		$qNew = "SELECT keyId FROM Shipments ORDER BY keyId DESC LIMIT 1;";
		$rNew = @mysql_query($qNew,$dbc);
		$rowNew = @mysql_fetch_array($rNew);
		$this->keyId = $rowNew[0];
	}
	
	
	public function Display_delete_form(){
		echo '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">
		<h3>' . $this->Notes . '</h3>
		<b><p><font size="+1">
		Are you sure you want to delete this record?<br />
		<input type="radio" name="sure" value="Yes" /> Yes 
		<input type="radio" name="sure" value="No" checked="checked" /> No</p>
		<p>Password: <input type="text" name="Password" size="10" maxlength="10" /><br></p>
		<p><input type="image" src="pics/submit.bmp" value="Submit" /></p>
		<input type="hidden" name="submitted" value="TRUE" />
		<input type="hidden" name="id" value="' . $this->keyId . '" />
		</form>';
	}
	
	
	public function Delete_record(){
		include('mysql_connect.php');
		$q = "DELETE FROM ShipItems WHERE fkShipment=$this->keyId";		
		$r = @mysql_query ($q, $dbc);
		$q = "DELETE FROM Shipments WHERE keyId=$this->keyId LIMIT 1";		
		$r = @mysql_query ($q, $dbc);	
		echo '<p>The record has been deleted.</p>';	
		echo '<meta http-equiv="Refresh" content="1;url=shipping.php">';
	}
	
}


class Location_selector_class{
	
	public function Display_selector($CurrentLocation = ''){
		
		include('mysql_connect.php');
		echo '<p>Location:<select name="Location">'; 
	
		$qLoc = "SELECT keyId FROM Locations";	
		$rLoc = @mysql_query ($qLoc, $dbc);

		while ($rowLoc = mysql_fetch_array($rLoc)) {
				$Location = new Location_class();
				$Location->Initialize($rowLoc[0]);
				if ($Location->keyId == $CurrentLocation){
					$option_blockLoc .= "<option value='$Location->keyId' selected = 'selected'>$Location->Info</option>";
				}
				else{
		   		$option_blockLoc .= "<option value='$Location->keyId'>$Location->Info</option>";
				}
		} // End of WHILE loop.
		
		echo $option_blockLoc;
		echo '</select></p>';
	
	}
}


class Status_selector_class{
	
	public function Display_selector($CurrentStatus = '0'){
		echo '<p>Status:<select name="Status">'; 
		if ($CurrentStatus == '0'){
			$option_blockStatus .= "<option value='0' selected = 'selected'>Has Shipped</option>";
			$option_blockStatus .= "<option value='1'>Has Not Shipped</option>";
		}
		else{
			$option_blockStatus .= "<option value='0' >Has Shipped</option>";
			$option_blockStatus .= "<option value='1' selected = 'selected'>Has Not Shipped</option>";
		}
		echo $option_blockStatus;
		echo '</select></p>';
	
	}
}


class ShipmentTable_class{
	var $DisplayQuery;
    var $PNView;
    var $VendorView;
    var $AssyPNView;
    var $StartDateView;
    var $EndDateView;
    var $LocationView;
    var $StatusView;
	
	function ShowTable(){
		include('mysql_connect.php');
		
	
		$this->DisplayQuery = "SELECT keyId, ShipToLocation,Title,ShipDate, SICL
							FROM Shipments 
							WHERE ShipToLocation LIKE $this->LocationView AND ShipDate >= $this->StartDateView
							AND ShipDate <= $this->EndDateView
							AND Status=$this->StatusView
							ORDER BY ShipDate DESC";
			
				
							
							
		//echo "query:<br>$this->DisplayQuery<br>";					
		
		$qTable = $this->DisplayQuery;
		$rTable = @mysql_query($qTable,$dbc);
		$Shipment = new Shipment_class;
		
		
		// Table header:
		echo '<b><table align="center" cellspacing="1" cellpadding="5" width="100%" bgcolor="#000000">
		<tr bgcolor="#ffff66">
			<td align="center"><b>Edit</b></td>
			<td align="center"><b>Delete</b></td>
			<td align="center"><b>Ship To Location</b></td>
			<td align="center"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Date&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</b></td>
			<td align="center"><b>Title</b></td>
			<td align="center"><b>View Record</b></td>
			
		</tr>';

		// Fetch and print all the records....
		$bg = '#eeeeee'; 
		$do_once = false;
		while ($rowTable = @mysql_fetch_array($rTable)) {
			
				$showrecord = true;
				$Shipment->Initialize($rowTable[0]);
				//Query ShipItems table, if no records meet criteria, don't display anything
				$qSI = "SELECT keyId
				FROM ShipItems 
				WHERE fkShipment = $Shipment->keyId AND
				PN LIKE $this->PNView 
				AND Vendor LIKE $this->VendorView
				AND fkComponentType LIKE $this->AssyPNView
				;";	
				
				//if ($Shipment->keyId == 334){
					//echo "<br>Query Shipitems= $qSI<br>"; 
				//}
				
				
				$rSI = @mysql_query ($qSI, $dbc);
				if (mysql_num_rows($rSI) == 0){
					$showrecord = false;
					//find out if there are any shipitem records for this shipment
					$qSI2 = "SELECT keyId FROM ShipItems WHERE fkShipment = $Shipment->keyId;";	
					$rSI2 = @mysql_query ($qSI2, $dbc);
					if (mysql_num_rows($rSI2)==0){
						$showrecord = true;
					}
					
					
				}
		
				if ($showrecord == true){
					//if ($do_once == false){
					//echo "query:<br>$qSI<br>";	
					//$do_once = true;
					//}
					
					
					$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee');
					echo '<b><tr bgcolor="' . $bg . '">';
				
			
					echo '
					<td align="center"><font color="#000000">
					<a href="edit_record_shipment.php?id=' . $Shipment->keyId . '&editing=1">
					Edit</a></td>';
					
					
					echo '<td align="center"><font color="#000000">
					<a href="delete_record_shipment.php?id=' . $Shipment->keyId  . '">
					Delete</td>';
					
				
					echo '<td align="left"><font color="#000000">' . $Shipment->ShipToLocationInfo . '</td>';
					echo '<td align="center"><font color="#000000">' . $Shipment->ShipDate . '</td>';
					echo '<td align="left"><font color="#000000"><b>' . stripslashes($Shipment->Title) . '</b></td>';
					echo '
					<td align="center">
					<a href="view_shipment_full_record.php?id=' . $Shipment->keyId . '">
					<img src="pics/viewrecordbutton.bmp"></a>
					</td>';
			
					echo '</tr>';
				}// end if show record = true
		} // End of WHILE loop.
		echo '</table></b>';
	}//end ShowTable function
}



class Location_class{
	var $keyId;
	var $Notes;
	var $Description;
	var $Info;
	
	
	function Initialize($in_keyId){
		include('mysql_connect.php');
		$qL = "SELECT Notes, Description FROM Locations WHERE keyId = $in_keyId;";
		$rL = @mysql_query($qL,$dbc);
		$rowL = @mysql_fetch_array($rL);
		
		$this->keyId = $in_keyId;
		$this->Notes = $rowL[0];
		$this->Description = $rowL[1];
		$this->Info = $this->Description . " ($this->Notes)";
	}
	
	
	
}


class Ship_item_class{
	var $keyId;
	var $fkShipment;
	var $NetKitQty;
	var $Stock;
	var $Unit;
	var $PN;
	var $Title;
	var $Vendor;
	var $VendorPN;
	var $DrawingNumber;
	var $StorageLocation;
	var $AssyPN;
	var $ProductTreeNumber;
	var $fkComponentType;
	
	var $Password;
	var $CorrectPassword;
	
	public function Initialize($In_keyId){
		include ('mysql_connect.php');
		$this->CorrectPassword = "nrao1234";
		
		$qInit = "SELECT fkShipment,NetKitQty,Stock,Unit,PN,Title,Vendor,
		         VendorPN,DrawingNumber,StorageLocation,AssyPN,ProductTreeNumber,fkComponentType
        		FROM ShipItems WHERE keyId = $In_keyId;";
        		
		$rInit = @mysql_query($qInit,$dbc);
		$rowInit = @mysql_fetch_array($rInit);
		$this->keyId = $In_keyId;
		$this->fkShipment = $rowInit[0];
		$this->NetKitQty = $rowInit[1];
		$this->Stock = $rowInit[2];
		$this->Unit = $rowInit[3];
		$this->PN = $rowInit[4];
		$this->Title = $rowInit[5];
		$this->Vendor = $rowInit[6];
		$this->VendorPN = $rowInit[7];
		$this->DrawingNumber = $rowInit[8];
		$this->StorageLocation = $rowInit[9];
		$this->AssyPN = $rowInit[10];
		$this->ProductTreeNumber = $rowInit[11];
		$this->fkComponentType = $rowInit[12];

	}
	
	public function RequestValues(){
		if (isset($_REQUEST['fkShipment'])){
			$this->fkShipment = $_REQUEST['fkShipment'];
		}
		if (isset($_REQUEST['NetKitQty'])){	
			$this->NetKitQty = $_REQUEST['NetKitQty'];
		}
		if (isset($_REQUEST['Stock'])){
			$this->Stock = $_REQUEST['Stock'];
		}
		if (isset($_REQUEST['Unit'])){
			$this->Unit = $_REQUEST['Unit'];	
		}
		if (isset($_REQUEST['PN'])){
			$this->PN = $_REQUEST['PN'];
		}	
		if (isset($_REQUEST['Title'])){			
			$this->Title = trim($_REQUEST['Title']);	
		}
		if (isset($_REQUEST['Vendor'])){
			$this->Vendor = $_REQUEST['Vendor'];
		}
		if (isset($_REQUEST['VendorPN'])){	
			$this->VendorPN = $_REQUEST['VendorPN'];
		}
		if (isset($_REQUEST['DrawingNumber'])){	
			$this->DrawingNumber = $_REQUEST['DrawingNumber'];
		}
		if (isset($_REQUEST['StorageLocation'])){
			$this->StorageLocation = $_REQUEST['StorageLocation'];
		}
		if (isset($_REQUEST['AssyPN'])){	
			$this->AssyPN = $_REQUEST['AssyPN'];
		}
		if (isset($_REQUEST['ProductTreeNumber'])){	
			$this->ProductTreeNumber = $_REQUEST['ProductTreeNumber'];
		}
		if (isset($_REQUEST['fkComponentType'])){	
			$this->fkComponentType = $_REQUEST['fkComponentType'];
		}
		if (isset($_REQUEST['Password'])){	
			$this->Password = $_REQUEST['Password'];
		}
		
	}
	
	public function NewRecord(){
		include('mysql_connect.php');
		$qNew = "INSERT INTO ShipItems() VALUES();";
		$rNew = @mysql_query($qNew,$dbc);
		$qNew = "SELECT keyId FROM ShipItems ORDER BY keyId DESC LIMIT 1;";
		$rNew = @mysql_query($qNew,$dbc);
		$rowNew = @mysql_fetch_array($rNew);
		$this->keyId = $rowNew[0];
	}
	
	function UpdateRecord(){
		include ('mysql_connect.php');
		$q = "UPDATE ShipItems SET fkShipment='$this->fkShipment',NetKitQty='$this->NetKitQty',
		Stock='$this->Stock',Unit='$this->Unit',PN='$this->PN',Title='$this->Title',
		Vendor='$this->Vendor',VendorPN='$this->VendorPN',
		DrawingNumber = '$this->DrawingNumber',StorageLocation = '$this->StorageLocation',
		AssyPN = '$this->AssyPN',ProductTreeNumber = '$this->ProductTreeNumber',
		fkComponentType = '$this->fkComponentType'
		WHERE keyId='$this->keyId' LIMIT 1";
		$r = @mysql_query ($q, $dbc);
	}
	
	public function Display_add_form($action = "add"){
		include('mysql_connect.php');
		echo '
		<form name = "shipitem" enctype="multipart/form-data" action="' . $_SERVER["PHP_SELF"] . '" method="post">
		<p>NetKitQty: <input type="text" name="NetKitQty" size="5" maxlength="80" value="'.$this->NetKitQty.'" /></p>
		<p>Stock: <input type="text" name="Stock" size="5" maxlength="80" value="'.$this->Stock.'" /></p>
		<p>Unit: <input type="text" name="Unit" size="5" maxlength="80" value="'.$this->Unit.'" /></p>
		<p>PN: <input type="text" name="PN" size="60" maxlength="200" value="'.$this->PN.'" /></p>
		<p>Title: <input type="text" name="Title" size="100" maxlength="200" value="'.$this->Title.'" /></p>
		<p>Vendor: <input type="text" name="Vendor" size="30" maxlength="200" value="'.$this->Vendor.'" /></p>
		<p>VendorPN: <input type="text" name="VendorPN" size="30" maxlength="200" value="'.$this->VendorPN.'" /></p>
		<p>Drawing Number: <input type="text" name="DrawingNumber" size="30" maxlength="200" value="'.$this->DrawingNumber.'" /></p>
		<p>Storage Location: <input type="text" name="StorageLocation" size="30" maxlength="200" value="'.$this->StorageLocation.'" /></p>
		<p>Assy PN: <input type="text" name="AssyPN" size="30" maxlength="200" value="'.$this->AssyPN.'" /></p>
		';

		if ($action == "edit"){
		echo '
		<p>Password:<input type="text" name="Password" size="30" maxlength="200"  /></p>';
		}
		
		
		echo '
		<p><input type="image" src="pics/submit.bmp" name="submit" value="SUBMIT" /></p>
		<input type="hidden" name="submitted" value="TRUE" />
		<input type="hidden" name="id" value="'.$this->keyId.'" /></b>';
	}
	
	
	
	public function Display_delete_form(){
		echo '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">
		<h3>' . $this->Title . '</h3>
		<b><p><font size="+1">
		Are you sure you want to delete this record?<br />
		<input type="radio" name="sure" value="Yes" /> Yes 
		<input type="radio" name="sure" value="No" checked="checked" /> No</p>
		<p>Password: <input type="text" name="Password" size="10" maxlength="10" /><br></p>
		<p><input type="image" src="pics/submit.bmp" value="Submit" /></p>
		<input type="hidden" name="submitted" value="TRUE" />
		<input type="hidden" name="id" value="' . $this->keyId . '" />
		</form>';
	}
	
	
	public function Delete_record(){
		include('mysql_connect.php');
		$q = "DELETE FROM ShipItems WHERE keyId=$this->keyId";		
		$r = @mysql_query ($q, $dbc);
		echo '<p>The record has been deleted.</p>';	
		echo '<meta http-equiv="Refresh" content="1;url=view_shipment_full_record.php?id='.$this->fkShipment.'">';
	}
	
	
	
	
	
}




?>