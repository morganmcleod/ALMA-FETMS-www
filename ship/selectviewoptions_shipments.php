<?php
include('mysql_connect.php');
$CurrentAssyPN = $_GET['AssyPNView'];
$CurrentPN= $_GET['PNView'];
$CurrentVendor = $_GET['VendorView'];
$CurrentShipLocation = $_GET['LocationView'];
$CurrentStartDate = $_GET['StartDateView'];
$CurrentEndDate = $_GET['EndDateView'];
$CurrentSICL = $_GET['SICLView'];

$CurrentStatus = '0';
if (isset($_GET['StatusView'])){
	$CurrentStatus = $_GET['StatusView'];
}

?>

<br><br>
<center>
<div style="width:85%;height:110%;border:1px solid black" align="left">
<form name = "SelectViewOptions" action="view_shipments_sort.php" method="get">
<div align = "center">
<b>View Filter Options</b>
</div><font color = "#000099">
<?php 

if (isset($_GET['ComponentTypeView'])){
	$CurrentComponentType = $_GET['ComponentTypeView'];
	$CurrentLocation = $_GET['LocationView'];
	$CurrentBand = $_GET['BandView'];
}


	
	




	//This part creates and displays a drop-down list for
	//selecting a component type
	$option_blockTitle .= "<option value='All'>All</option>";
	echo'
	<br><font size="+1"><b>Item Description:</b></font><select name="AssyPNView">'; 
	//$qTitle = "SELECT DISTINCT fkComponentType FROM ShipItems ORDER BY Title ASC";
	
	$qTitle = 
	"SELECT DISTINCT(ShipItems.fkComponentType), ComponentTypes.Description FROM ShipItems,ComponentTypes 
	WHERE ShipItems.fkComponentType = ComponentTypes.keyId 
	ORDER BY ComponentTypes.Description ASC;";

	
	$rTitle = mysql_query ($qTitle, $dbc);
	while ($row = mysqli_fetch_array($rTitle)) {
			$tempAssyPN = $row[0];
			//Get Item description from ComponentTypes table
			$qCtypeDesc = "SELECT Description, keyId FROM ComponentTypes WHERE keyId
			 = '$tempAssyPN'";
			$rCtypeDesc = mysql_query ($qCtypeDesc, $dbc);
			$rowCtypeDesc = mysqli_fetch_array($rCtypeDesc);
		
			
			$DescTEMP=$rowCtypeDesc[0];
			$cTypeId = $rowCtypeDesc[1];
			if ($DescTEMP != ""){
				if ($tempAssyPN == $CurrentAssyPN){	
		   		    $option_blockTitle .= "<option value='$cTypeId' selected = 'selected'>$DescTEMP</option>";
				}
				else{
				$option_blockTitle .= "<option value='$cTypeId'>$DescTEMP</option>";
				}
			}
			
	} // End of WHILE loop.
	echo $option_blockTitle;
	echo '</select>';
	?>
	
	
	
	
	
	
	
	
	
	
	<br>
	<font size="+1"><b>ShipToLocation:</b></font><select name="LocationView" onChange="submit()"> 
	<?php 
	//This part creates and displays a drop-down list for
	//selecting a location
	$option_blockLoc .= "<option value='All'>All</option>";
	$qLoc = "SELECT keyId AS IDLoc, Description AS DescriptionLoc, Notes AS NotesLoc 
	FROM Locations ORDER BY Description ASC";		
	$rLoc = mysql_query ($qLoc, $dbc);
	while ($rowLoc = mysqli_fetch_array($rLoc)) {
			$keyIdTEMPLoc=$rowLoc[0];
			$DescTEMPLoc=$rowLoc[1];
			$NotesTEMPLoc=$rowLoc[2];
			if ($keyIdTEMPLoc == $CurrentShipLocation){	
	   		    $option_blockLoc .= "<option value='$keyIdTEMPLoc' selected = 'selected'>$DescTEMPLoc ($NotesTEMPLoc)</option>";
			}
			else{
			$option_blockLoc .= "<option value='$keyIdTEMPLoc'>$DescTEMPLoc ($NotesTEMPLoc)</option>";	
			}
	} // End of WHILE loop.
	echo $option_blockLoc . "</select>";
	?>
	
	
	<br>
	<font size="+1"><b>
	PN: 
	<input type = "text" name = "PNView" value =
	<?php
	echo $CurrentPN;	
	?>
	></b>
	
	
	
	<br><font size="+1"><b>Vendor:</b></font><select name="VendorView" onChange="submit()"> 
	<?php 
	$option_blockVendor .= "<option value='All'>All</option>";
	$qVendor = "SELECT DISTINCT Vendor 
	FROM ShipItems WHERE Vendor <> '' ORDER BY Vendor ASC";		
	$rVendor = mysql_query ($qVendor, $dbc);
	while ($rowVendor = mysqli_fetch_array($rVendor)) {
			$VendorTemp = $rowVendor[0];
			//if ($VendorrTemp != ""){
				if ($VendorTemp == $CurrentVendor){	
		   		    $option_blockVendor .= "<option value='$VendorTemp' selected = 'selected'>$VendorTemp</option>";
				}
				else{
				$option_blockVendor .= "<option value='$VendorTemp'>$VendorTemp</option>";
				}
			//}
	} // End of WHILE loop.
	echo $option_blockVendor . "</select>";
	//echo "<p>";
	//include('calendarform2.php');
	//echo "</p>";
	?>
	
	<br><font size="+1"><b>
	Start Date (YYYY-MM-DD): 
	<input type = "text" name = "StartDateView" value =
	<?php
	echo $CurrentStartDate;	
	?>
	></b></font>
	
	
	<br><font size="+1"><b>
	End Date (YYYY-MM-DD): 
	<input type = "text" name = "EndDateView" value =
	<?php
	echo $CurrentEndDate;	
	?>
	></b></font>
	</select><br>
	
	<b><font size="+1">Status:<select name="StatusView" onChange = "submit()"> 
	<?php 
	if ($CurrentStatus=='0'){
	$option_blockStatus = "<option value='0' selected = 'selected'>Has Shipped</option>"; 
	$option_blockStatus .= "<option value='1'>Has Not Shipped</option>"; 
	}
	if ($CurrentStatus=='1'){
	$option_blockStatus = "<option value='0' >Has Shipped</option>"; 
	$option_blockStatus .= "<option value='1' selected = 'selected'>Has Not Shipped</option>"; 
	}
	
	echo $option_blockStatus;
	?>
	</select></font></b></p>
	
	
	<?php /*
	<br>
	<font size="+1"><b>
	SICL: FEND-40.09.07.00-
	<input type = "text" size = "2" name = "SICLView" value =
	<?php
	echo $CurrentSICL;	
	?>
	>-A-LIS</b></font>
	*/?>
	
	
	
	
	
	<center>
	<p><input type="image" SRC="pics/applyfilteroptions.bmp" name="submit" value="APPLY FILTER OPTIONS" /></p>
	</center>
	</font>
</form>
</div>
</center>
<br><br><br>

