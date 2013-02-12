<?php
// This script allows user to select which table to view

$page_title = 'Select Type of Record';
include ('header.php');
echo '<h1>Browse, Add, or Edit Records</h1>';

include ('mysql_connect.php');


echo '<br><br><br><br><font size="+1" color = "#000099"><b>Component Types</b></font>
	<br>All possible types of component. Includes Product tree number, <br>and may include links to documentation';
?>
	<br>
	<a href="view_components_types_sort.php"><img src="pics/browseeditrecordsbutton.bmp"></a>
	<a href="add_record_component_type.php"><img src="pics/addrecordsbutton.bmp"></a>
<?php 

/*
echo '<br><br><br><br><font size="+1" color = "#000099"><b>Locations</b></font>
	<br>All possible locations for a Front End or Component';


	<br>
	<a href="view_locations_sort.php"><img src="pics/browseeditrecordsbutton.bmp"></a>
	<a href="add_record_location.php"><img src="pics/addrecordsbutton.bmp"></a>

*/

//include('import_cartassembly.php');
include ('footer.php');
?>