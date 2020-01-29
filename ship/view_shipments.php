<?php # Script 9.5 - #5

$page_title = 'View Shipment Records';
include ('header.php');
echo '<h1>Shipments</h1>';

include ('mysql_connect.php');
//include ('selectshipmentviewoptions.php');

// Number of records to show per page:
$display = 25;
// Determine where in the database to start returning results...
if (isset($_GET['s']) && is_numeric($_GET['s'])) {
	$start = $_GET['s'];
} else {
	$start = 0;
}

// Determine how many pages there are...

$ComponentTypeView = "All";
$LocationView = "All";
$BandView = "All";

 // Count the number of records:
$q = "SELECT COUNT(keyId) FROM Shipments";	
$r = mysql_query ($q, $dbc);
$row = mysqli_fetch_array ($r, MYSQL_NUM);
$records = $row[0];
// Calculate the number of pages...
if ($records > $display) { // More than 1 page.
	$pages = ceil ($records/$display);
} else {
	$pages = 1;
}



// Determine the sorting order:
switch ($sort) {
	case 'SortBySN':
		$order_by = 'CAST(SN AS UNSIGNED) ASC';
		break;
	case 'SortByComponentType':
		$order_by = 'fkComponentType ASC';
		break;
	case 'SortByLocation':
		$order_by = 'fkLocation ASC, fkComponentType DESC';
		break;
	default:
		$order_by = 'Band DESC';
		if($BandView != "All"){
			$order_by = ' Band ASC, SN ASC ';
		}
		$sort = 'SortByBand';
		break;
}
	


//$q = "SELECT keyId, ShipToLocation, Notes, TS FROM Shipments ORDER BY keyId ASC LIMIT $start, $display";
$q = "SELECT keyId, ShipToLocation, Notes, ShipDate FROM Shipments ORDER BY keyId ASC";
$r = mysql_query ($q, $dbc);


// Table header:
echo '<b><br><br>
<table align="center" cellspacing="1" cellpadding="5" width="100%" bgcolor="#000000">
<tr bgcolor="#ffff66" font color = "#000000">
	<td align="center"><b>Edit</b></td>
	<td align="center"><b>Delete</b></td>
	<td align="center"><b>ShipToLocation</b></td>
	<td align="center"><b>Notes</b></td>
	<td align="center"><b>Date</b></td>
	<td align="center"><b></b></td>
	
</tr>';



// Fetch and print all the records....
$bg = '#eeeeee'; 
while ($row = mysqli_fetch_array($r)) {
	$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee');
		echo '<tr bgcolor="' . $bg . '">
		<td align="center"><a href="edit_record_shipment.php?id=' . $row[0] . '">Edit</a></td>
		<td align="center"><a href="delete_record_shipment.php?id=' . $row[0] . '">Delete</a></td>';
		$id_temp = $row[0];

		//This part retrieves the description from the Locations table
		$tempLoc = $row[1];
		$qLoc = "SELECT Description, Notes FROM Locations WHERE keyId = $tempLoc";		
		$rLoc = mysql_query ($qLoc, $dbc);
		$rowLoc = mysqli_fetch_array ($rLoc);
		$tempLoc = $rowLoc[0] . " (" . $rowLoc[1] . ")";
		echo '<td align="center">' . $tempLoc . '</td>';
		echo '<td align="center"><b>' . $row[2] . '</b></td>';
		echo '<td align="center"><b>' . $row[3] . '</b></td>	
		
		<td align="center">
		<a href="view_shipment_full_record.php?id=' . $id_temp . '">
		<img src="pics/viewrecordbutton.bmp"></a>
		</td>
		
		';
	echo '</tr>';
	
} // End of WHILE loop.

echo '</table></b>';


//mysql_free_result ($r);
mysql_close($dbc);

// Make the links to other pages, if necessary.
if ($pages > 1) {
	
	echo '<br /><p>';
	$current_page = ($start/$display) + 1;
	
	// If it's not the first page, make a Previous button:
	if ($current_page != 1) {
		echo '<a href="view_components_sort.php?s=' . ($start - $display) . '&p=' . $pages . '&sort=' . 
		$sort . '&ComponentTypeView=' . $ComponentTypeView . '&LocationView=' . $LocationView 
		. '&BandView=' . $BandView .'">Previous</a> ';
	}
	
	// Make all the numbered pages:
	for ($i = 1; $i <= $pages; $i++) {
		if ($i != $current_page) {
			echo '<a href="view_components_sort.php?s=' . (($display * ($i - 1))) . '&p=' . $pages . 
			'&sort=' . $sort . '&ComponentTypeView=' . $ComponentTypeView . '&LocationView=' . 
			$LocationView . '&BandView=' . $BandView .'">' . $i . '</a> ';
		} else {
			echo $i . ' ';
		}
	} // End of FOR loop.
	
	// If it's not the last page, make a Next button:
	if ($current_page != $pages) {
		echo '<a href="view_components_sort.php?s=' . ($start + $display) . '&p=' . $pages . '&sort=' 
		. $sort . '&ComponentTypeView=' . $ComponentTypeView . '&LocationView=' . $LocationView 
		. '&BandView=' . $BandView .'">Next</a>';
	}
	
	echo '</p>'; // Close the paragraph.
	
} // End of links section.


include ('footer.php');
?>