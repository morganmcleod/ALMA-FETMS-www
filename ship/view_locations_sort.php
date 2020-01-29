<?php # Script 9.5 - #5

// This script retrieves all the records from the users table.
// This new version allows the results to be sorted in different ways.

$page_title = 'View Listing of the Location Records';
include ('header.php');
echo '<h1>Locations</h1>';

include ('mysql_connect.php');

// Number of records to show per page:
$display = 20;

// Determine how many pages there are...
if (isset($_GET['p']) && is_numeric($_GET['p'])) { // Already been determined.
	$pages = $_GET['p'];
} else { // Need to determine.
 	// Count the number of records:
	$q = "SELECT COUNT(keyId) FROM Locations";
	$r = mysql_query ($q, $dbc);
	$row = mysqli_fetch_array ($r, MYSQL_NUM);
	$records = $row[0];
	// Calculate the number of pages...
	if ($records > $display) { // More than 1 page.
		$pages = ceil ($records/$display);
	} else {
		$pages = 1;
	}
} // End of p IF.

// Determine where in the database to start returning results...
if (isset($_GET['s']) && is_numeric($_GET['s'])) {
	$start = $_GET['s'];
} else {
	$start = 0;
}

// Determine the sort...
// Default is by registration date.
$sort = (isset($_GET['sort'])) ? $_GET['sort'] : 'rd';

// Determine the sorting order:
switch ($sort) {
	case 'SortByDescription':
		$order_by = 'Description ASC';
		break;
	case 'SortByNotes':
		$order_by = 'Notes ASC';
		break;
	default:
		$order_by = 'Description ASC';
		$sort = 'SortByDescription';
		break;
}
	
// Make the query:
$q = "SELECT keyId AS ID, Description AS Descript, Notes AS Nts FROM Locations ORDER BY $order_by LIMIT $start, $display";		
$r = mysql_query ($q, $dbc);



// Table header:
echo '<table align="center" cellspacing="5" cellpadding="10" width="95%">
<tr>

	<td align="left"><b>Edit</b></td>
	<td align="left"><b>Delete</b></td>
	<td align="left"><b><a href="view_locations_sort.php?sort=SortByDescription">Description</a></b></td>
	<td align="left"><b><a href="view_locations_sort.php?sort=SortByNotes">Notes</a></b></td>


</tr>';

// Fetch and print all the records....
$bg = '#eeeeee'; 
while ($row = mysqli_fetch_array($r)) {
	$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee');
		echo '<tr bgcolor="' . $bg . '">
		<td align="left"><a href="edit_record_location.php?id=' . $row['ID'] . '">Edit</a></td>
		<td align="left"><a href="delete_record_location.php?id=' . $row['ID'] . '">Delete</a></td>
		<td align="left">' . $row['Descript'] . '</td>
		<td align="left">' . $row['Nts'] . '</td>	
	</tr>';
	
} // End of WHILE loop.

echo '</table>';




// Make the links to other pages, if necessary.
if ($pages > 1) {
	
	echo '<br /><p>';
	$current_page = ($start/$display) + 1;
	
	// If it's not the first page, make a Previous button:
	if ($current_page != 1) {
		echo '<a href="view_locations_sort.php?s=' . ($start - $display) . '&p=' . $pages . '&sort=' . $sort . '">Previous</a> ';
	}
	
	// Make all the numbered pages:
	for ($i = 1; $i <= $pages; $i++) {
		if ($i != $current_page) {
			echo '<a href="view_locations_sort.php?s=' . (($display * ($i - 1))) . '&p=' . $pages . '&sort=' . $sort . '">' . $i . '</a> ';
		} else {
			echo $i . ' ';
		}
	} // End of FOR loop.
	
	// If it's not the last page, make a Next button:
	if ($current_page != $pages) {
		echo '<a href="view_locations_sort.php?s=' . ($start + $display) . '&p=' . $pages . '&sort=' . $sort . '">Next</a>';
	}
	
	echo '</p>'; // Close the paragraph.
	
} // End of links section.
mysql_free_result ($r);
mysql_close($dbc);
include ('footer.php');
?>