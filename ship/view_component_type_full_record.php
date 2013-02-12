<?php # Script 9.3 - edit_user.php

// This page is for editing a user record.
// This page is accessed through view_users.php.

$page_title = 'View Full Record for the Select Component Type';
include ('header.php');

echo '<h1>Selected Component Type Record</h1>';

// Check for a valid user ID, through GET or POST:
if ( (isset($_GET['id'])) && (is_numeric($_GET['id'])) ) { // From view_users.php
	$id = $_GET['id'];
} elseif ( (isset($_POST['id'])) && (is_numeric($_POST['id'])) ) { // Form submission.
	$id = $_POST['id'];
} else { // No valid ID, kill the script.
	echo '<p class="error">This page has been accessed in error.</p>';
	include ('footer.php'); 
	exit();
}

include ('mysql_connect.php'); 

// Check if the form has been submitted:


// Always show the form...

// Retrieve the user's information:
$q = "SELECT ProductTreeNumber, Description, Docs FROM ComponentTypes WHERE keyId=$id";		
$r = @mysql_query ($q, $dbc);

if (mysql_num_rows($r) == 1) { // Valid user ID, show the form.

	// Get the user's information:
	$row = mysql_fetch_array ($r, MYSQL_NUM);

	
	// Create the form:
	echo '
	
	
	
<b><font size="+1"><form action="view_frontend_full_record.php" method="post">
<p><div style="width:800px;height:100%;border:1px solid black;background-color: #fffff3"></p>
<p>Product Tree Number: <font color="#000066">'. $row[0]. '</font></p>
<p>Description: <font color="#000066">'. $row[1]. '</font></p>
<p>Docs: <a href="'. $row[2]. '"><font color="#000066">'. $row[2]. '</font></a></p>

<br></b>
</div>
<br>
<p><b><li><td align="left"><a href="edit_record_component_type.php?id=' . $id . '">Edit this record</li></td>
<li><td align="left"><a href="delete_record_component_type.php?id=' . $id . '">Delete this record</a></td></li></p></b>
</font></form>';

} else { // Not a valid user ID.
	echo '<p class="error">This page has been accessed in error.</p>';
}

mysql_close($dbc);
		
include ('footer.php');
?>