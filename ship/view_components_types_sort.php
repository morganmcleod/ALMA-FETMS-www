<?php 

// This script retrieves all the records from the FrontEndComponents table.
// The results may be sorted in different ways.

$page_title = 'View Listing of the Component Type Records';
include ('header.php');
echo '<h1>Front End Component Types</h1>';

include ('mysql_connect.php');

// Number of records to show per page:
$display = 900;

// Determine how many pages there are...
if (isset($_GET['p']) && is_numeric($_GET['p'])) { // Already been determined.
	$pages = $_GET['p'];
} else { // Need to determine.
 	// Count the number of records:
	$q = "SELECT COUNT(keyId) FROM ComponentTypes";
	$r = @mysql_query ($q, $dbc);
	$row = @mysql_fetch_array ($r, mysql_NUM);
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
	case 'SortByProductTreeNumber':
		$order_by = 'ProductTreeNumber ASC';
		break;
	case 'SortByDescription':
		$order_by = 'Description ASC';
		break;
	case 'SortByDocs':
		$order_by = 'Docs ASC';
		break;
	default:
		$order_by = 'ProductTreeNumber ASC';
		$sort = 'SortByProductTreeNumber';
		break;
}
	
// Make the query:
$q = "SELECT keyId AS IDTemp, ProductTreeNumber as PTNTemp, Description AS DescriptionTemp, Docs AS 
DocsTemp FROM ComponentTypes ORDER BY $order_by LIMIT $start, $display";		
$r = @mysql_query ($q, $dbc);



// Table header:
echo '<table align="center" cellspacing="5" cellpadding="10" width="85%">
<tr>

	<td align="left"><b>Edit</b></td>
	<td align="left"><b>Delete</b></td>
	<td align="left"><b><a href="view_components_types_sort.php?sort=SortByProductTreeNumber">Product Tree Number*</a></b></td>
	<td align="left"><b><a href="view_components_types_sort.php?sort=SortByDescription">Description</a></b></td>
	<td align="left"><b>Docs</b></td>

</tr>';

// Fetch and print all the records....
$bg = '#eeeeee'; 
while ($row = mysql_fetch_array($r)) {
	$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee');
		echo '<tr bgcolor="' . $bg . '">
		<td align="left"><a href="edit_record_component_type.php?id=' . $row['IDTemp'] . '">Edit</a></td>
		<td align="left"><a href="delete_record_component_type.php?id=' . $row['IDTemp'] . '">Delete</a></td>';
		
		$ProdTreeNo = "NONE";
		if ($row['PTNTemp'] != ""){
		$ProdTreeNo = $row['PTNTemp'];
		}
		
		echo '<td align="left"><a href="view_component_type_full_record.php?id=' . $row['IDTemp'] . '">' . $ProdTreeNo . '</td>';
		
		echo '
		<td align="left">' . $row['DescriptionTemp'] . '</td>';
		
		if ($row['DocsTemp'] != ""){
		echo '<td align="left"><a href="'. $row['DocsTemp'] .'" target="_blank">Link</td>';
		}
	echo '	
	</tr>
	';
} // End of WHILE loop.

echo '</table>';


mysql_free_result ($r);
mysql_close($dbc);

// Make the links to other pages, if necessary.
if ($pages > 1) {
	
	echo '<br /><p>';
	$current_page = ($start/$display) + 1;
	
	// If it's not the first page, make a Previous button:
	if ($current_page != 1) {
		echo '<a href="view_component_types_sort.php?s=' . ($start - $display) . '&p=' . $pages . '&sort=' . $sort . '">Previous</a> ';
	}
	
	// Make all the numbered pages:
	for ($i = 1; $i <= $pages; $i++) {
		if ($i != $current_page) {
			echo '<a href="view_component_types_sort.php?s=' . (($display * ($i - 1))) . '&p=' . $pages . '&sort=' . $sort . '">' . $i . '</a> ';
		} else {
			echo $i . ' ';
		}
	} // End of FOR loop.
	
	// If it's not the last page, make a Next button:
	if ($current_page != $pages) {
		echo '<a href="view_component_types_sort.php?s=' . ($start + $display) . '&p=' . $pages . '&sort=' . $sort . '">Next</a>';
	}
	
	echo '</p>'; // Close the paragraph.
	
} // End of links section.

echo '<p><font size = "+1">*Click to view full record</font></p>';
include ('footer.php');
?>