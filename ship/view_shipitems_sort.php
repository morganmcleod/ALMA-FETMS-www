<?php 

$page_title = 'View Listing of Shipment Items';
include ('header_wide.php');
echo '<h1>Shipment Items</h1>';

include ('mysql_connect.php');
include ('selectviewoptions_shipitems.php');

// Number of records to show per page:
$display = 20;

// Determine how many pages there are...

$AssyPNView = "All";
$LocationView = "All";
$PNView = "All";
$VendorView = "All";
$StartDateView = "1900-10-10";
$EndDateView = "3000-10-10";
$KeyWordView = "*";
$CurrentShipItemSpecificView = "All";

$initial=0;
if (isset($_REQUEST['initial'])){
	$initial = $_REQUEST['initial'];
}


if (isset($_GET['AssyPNView'])){
	
	$ONstatement = "";
	
	$AssyPNView = $_GET['AssyPNView'];
	if ($AssyPNView != "All"){
	
	//Get PTN from Component Types table, where keyId = AssyPNView
	$qPTN = "SELECT ProductTreeNumber FROM ComponentTypes WHERE
	keyId = '$AssyPNView'";
	$rPTN = mysql_query ($qPTN, $dbc);
	$rowPTN = mysqli_fetch_array($rPTN);
	
		
	$ONstatement = $ONstatement . ' AND ShipItems.AssyPN= "' . $rowPTN[0] . '" ';
	}
	$LocationView = $_GET['LocationView'];
	if ($LocationView != "All"){
		$ONstatement = $ONstatement . ' AND Shipments.ShipToLocation="' . $LocationView . '" ';
	}
	
	$KeyWordView = strtolower($_GET['KeyWordView']);
	if ($KeyWordView != "*"){
		$ONstatement = $ONstatement . ' AND ShipItems.Title LIKE "%' . $KeyWordView . '%" ';
	}
	
	
	
	$PNView = strtoupper($_GET['PNView']);
	if ($PNView==""){
		$PNView = "All";
	}
	
	
	if ($PNView != "All"){
		$ONstatement = $ONstatement . ' AND ShipItems.PN  LIKE "' . $PNView . '%" ';
	}
	

	$CurrentShipItemSpecificView = $_GET['ShipItemSpecificView'];

	if ($CurrentShipItemSpecificView != "All"){
		$ONstatement = $ONstatement . ' AND ShipItems.Title  LIKE "' . $CurrentShipItemSpecificView . '" ';
	}
	
	$VendorView = $_GET['VendorView'];
	if ($VendorView != "All"){
		$ONstatement = $ONstatement . ' AND ShipItems.Vendor="' . $VendorView . '" ';
	}
	
	if ($initial != 0){
		$VendorView = "vendor";
	}
	
	$StartDateView = str_replace("/","-",urldecode($_GET['StartDateView']));
	if ($StartDateView == ""){
		$StartDateView = "1900-10-10";	
	}
	$ONstatement = $ONstatement . ' AND Shipments.ShipDate >= "' . $StartDateView . '" ';
	
	$EndDateView = str_replace("/","-",urldecode($_GET['EndDateView']));
	if ($EndDateView == ""){
		$EndDateView = "3000-10-10";
	}
	$ONstatement = $ONstatement . ' AND Shipments.ShipDate <= "' . $EndDateView . '" ';
	$ONstatement = $ONstatement . ' AND Shipments.keyId = ShipItems.fkShipment ';
	
	
	/*echo $TitleView . "<br>";
	echo $LocationView . "<br>";
	echo $PNView . "<br>";
	echo $VendorView . "<br>";
	echo $StartDateView . "<br>";
	echo $EndDateView . "<br>";
*/
	if ($ONstatement != ""){
		$ONstatement = "ON" . $ONstatement;
		$ONstatement = str_replace("ON AND","ON",$ONstatement);
	}
	//echo "ON Statement: " . $ONstatement . "<br>";
}




$pages = 2;

if (isset($_GET['p']) && is_numeric($_GET['p'])) { // Already been determined.
	$pages = $_GET['p'];
} else { // Need to determine.
 	// Count the number of records:
 	if ($initial != "1"){
	$q = 'SELECT ShipItems.Title,ShipItems.Vendor,ShipItems.PN, Shipments.Notes, Shipments.ShipToLocation, 
	Shipments.ShipDate, Shipments.keyId FROM ShipItems INNER JOIN Shipments '
	. $ONstatement . 
	' ORDER BY Shipments.keyId ASC, Shipments.ShipToLocation ASC, ShipItems.Title ASC, Shipments.ShipDate ASC';	
	$r = mysql_query ($q, $dbc);
 	}
	$records = mysqli_num_rows($r);
	//echo $records . " records<br>";
	
	

	// Calculate the number of pages...
	if ($records > $display) { // More than 1 page.
		$pages = ceil ($records/$display);
	} else {
		$pages = 1;
	}
} // End of IF.

// Determine where in the database to start returning results...
if (isset($_GET['s']) && is_numeric($_GET['s'])) {
	$start = $_GET['s'];
} else {
	$start = 0;
}


/*
$qShipItems = 'SELECT ShipItems.Title,ShipItems.Vendor,ShipItems.PN, Shipments.Title, Shipments.ShipToLocation, 
Shipments.ShipDate, Shipments.keyId FROM ShipItems INNER JOIN Shipments '
. $ONstatement . 
' ORDER BY Shipments.keyId ASC, Shipments.ShipToLocation ASC, ShipItems.Title ASC, Shipments.ShipDate ASC 
LIMIT ' . $start . ',' . $display;	
*/

$qShipItems = 'SELECT ShipItems.Title,ShipItems.Vendor,ShipItems.PN, Shipments.Title, Shipments.ShipToLocation, 
Shipments.ShipDate, Shipments.keyId FROM ShipItems INNER JOIN Shipments '
. $ONstatement . 
' ORDER BY Shipments.keyId ASC, Shipments.ShipToLocation ASC, ShipItems.Title ASC, Shipments.ShipDate ASC;';	


//echo $qShipItems . "<br>";
//echo "CurrentShipItemSpecificView: $CurrentShipItemSpecificView";




if ($initial != "1"){
$r = mysql_query ($qShipItems, $dbc);


$datatype = "shipitems_sort";
include('save_to_csvfile_form.php');

// Table header:
echo '<b><table align="center" cellspacing="1" cellpadding="5" width="100%" bgcolor="#000000">
<tr bgcolor="#ffff66">
	<td align="center"><b>Item</b></td>
	<td align="center"><b>Vendor</b></td>
	<td align="center"><b>PN</b></td>
	<td align="center"><b>Shipment Title</b></td>
	<td align="center"><b>ShipLocation</b></td>
	<td align="center"><b>Date</b></td>
	<td align="center"><b>Shipment</b></td>
</tr>';

// Fetch and print all the records....
$bg = '#eeeeee'; 
while ($row = mysqli_fetch_array($r)) {
	$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee');
		echo '<b><tr bgcolor="' . $bg . '">';
		
		$Title = $row[0];
		$Vendor = $row[1];
		if ($row[1]==""){
			$Vendor = "None";
		}
		$PN = $row[2];
		if ($row[2]==""){
			$PN = "None";
		}
		$Notes = $row[3];
		$ShipToLocationID = $row[4];
		$ShipDate = $row[5];
		$id_temp = $row[6];
		
		//if (isset($_GET['AssyPNView'])){//Don't display records until user has selected a view filter
			echo '
			<td align="center"><font color="#000000">' . $Title . '</td>';
			echo '<td align="center"><font color="#000000">' . $Vendor . '</td>';
			echo '<td align="center"><font color="#000000">' . $PN . '</td>';
			echo '<td align="center"><font color="#000000">' . $Notes . '</td>';
			
			
			//This part retrieves the description from the Locations table
			$qLoc = "SELECT Description, Notes  FROM Locations WHERE keyId = $ShipToLocationID";		
			$rLoc = mysql_query ($qLoc, $dbc);
			$rowLoc = mysqli_fetch_array ($rLoc);
			$tempLoc = $rowLoc[0] . " (" . $rowLoc[1] . ")";
			echo '<td align="center"><font color="#000000">' . $tempLoc . '</td>';
			echo '<td align="center"><font color="#000000">' . $ShipDate . '</td>';
			echo '
			<td align="center">
			<a href="view_shipment_full_record.php?id=' . $id_temp . '">
			<img src="pics/viewrecordbutton.bmp"></a>
			</td>';	
		//}//end if Isset AssyPN
		
		
	echo '</tr>';
} // End of WHILE loop.
echo '</table></b>';

}//end if initial



//mysql_free_result ($r);
mysql_close($dbc);

/*
// Make the links to other pages, if necessary.
if ($pages > 1) {
	
	echo '<br /><p>';
	$current_page = ($start/$display) + 1;
	
	$PNView = $_REQUEST['PNView'];
	
	// If it's not the first page, make a Previous button:
	if ($current_page != 1) {
		
		
		$urlTempCurrent = "view_shipitems_sort.php?s=" . ($start - $display) . "&p=" . $pages . 
		"&AssyPNView=" . $AssyPNView . "&LocationView=" . 
		$LocationView . "&PNView=" . $PNView . "&VendorView=" . $VendorView .
		"&StartDateView=" . rawurlencode($StartDateView) . "&EndDateView=" . rawurlencode($EndDateView) .
		"&KeyWordView=" . $KeyWordView;
		
		echo "<a href='$urlTempCurrent'>Previous</a> ";
	}
	
	// Make all the numbered pages:
	for ($i = 1; $i <= $pages; $i++) {
		if ($i != $current_page) {
			$tempNumPage = "view_shipitems_sort.php?s=" . (($display * ($i - 1))) . "&p=" . $pages . 
			"&AssyPNView=" . $AssyPNView . "&LocationView=" . 
			$LocationView . "&PNView=" . $PNView . "&VendorView=" . $VendorView .
			"&StartDateView=" . rawurlencode($StartDateView) . "&EndDateView=" . rawurlencode($EndDateView) .
			"&KeyWordView=" . $KeyWordView;
			
			echo '<a href=' . $tempNumPage . '>' . $i . '</a> ';
		} else {
			echo $i . ' ';
		}
	} // End of FOR loop.
	
	// If it's not the last page, make a Next button:
	if ($current_page != $pages) {
		
		$urlNext = "view_shipitems_sort.php?s=" . ($start + $display) . "&p=" . $pages .  
		"&AssyPNView=" . $AssyPNView . "&LocationView=" . 
		$LocationView . "&PNView=" . $PNView . "&VendorView=" . $VendorView .
		"&StartDateView=" . rawurlencode($StartDateView) . "&EndDateView=" . rawurlencode($EndDateView) .
		"&KeyWordView=" . $KeyWordView;
		
		echo '<a href=' . $urlNext . '>Next</a>';
	}

	echo '</p>'; // Close the paragraph.
	
} // End of links section.

*/
mysql_close($dbc);
include ('footer.php');
?>