<?php 

// This page is for deleting a record.

$page_title = 'Delete the Selected Location Record';
include ('header.php');

echo '<h1>Delete the Selected Location Record</h1>';

// Check for a valid keyId, through GET or POST:
if ( (isset($_GET['id'])) && (is_numeric($_GET['id'])) ) { // From view_users.php
	$id = $_GET['id'];
} elseif ( (isset($_POST['id'])) && (is_numeric($_POST['id'])) ) { // Form submission.
	$id = $_POST['id'];
} else { // No valid ID, kill the script.
	echo '<p class="error">This page has been accessed in error.</p>';
	include ('footer.php'); 
	exit();
}

require_once ('mysql_connect.php');

// Check if the form has been submitted:
if (isset($_POST['submitted'])) 
{
	$tempPassword = $_POST['Password'];

	if ($tempPassword == "nrao")
	{
		if ($_POST['sure'] == 'Yes') 
		{ // Delete the record.
			// Make the query:
			$q = "DELETE FROM Locations WHERE keyId=$id LIMIT 1";		
			$r = @mysql_query ($q, $dbc);
				if (mysql_affected_rows($dbc) == 1) 
				{ // If it ran OK.
					echo '<p>The record has been deleted.</p>';	
				}//if (mysql_affected_rows($dbc) == 1)  
			
				else 
				{ // If the query did not run OK.
					echo '<p class="error">The record could not be deleted due to a system error.</p>'; // Public message.
					echo '<p>' . mysql_error($dbc) . '<br />Query: ' . $q . '</p>'; // Debugging message.
				}
		}//if ($_POST['sure'] == 'Yes')  
		else 
		{ // No confirmation of deletion.
			echo '<p>The record has NOT been deleted.</p>';	
		}
	}//if ($tempPassword == "asdf5!")	
	
	
	else
	{
		mysql_close($dbc); // Close the database connection.
	}
	
	

} else { // Show the form.

	// Retrieve the user's information:
	$q = "SELECT Description FROM Locations WHERE keyId=$id";
	$r = @mysql_query ($q, $dbc);
	
	if (mysql_num_rows($r) == 1) { // Valid keyId, show the form.

		// Get the user's information:
		$row = mysql_fetch_array ($r, MYSQL_NUM);
		
		// Create the form:
		echo '<form action="delete_record_location.php" method="post">
	<h3>Description: ' . $row[0] . '</h3>
	<b><p><font size="+1">
	Are you sure you want to delete this record?<br />
	<input type="radio" name="sure" value="Yes" /> Yes 
	<input type="radio" name="sure" value="No" checked="checked" /> No</p>
	<p><font color ="CC0000" >
	Password: </font><input type="text" name="Password" size="10" maxlength="20" value="********"  /></p>
	</b></font>
	<p><input type="image" src="pics/submit.bmp" value="Submit" /></p>
	<input type="hidden" name="submitted" value="TRUE" />
	<input type="hidden" name="id" value="' . $id . '" />
	</form>';
	
	} else { // Not a valid user ID.
		echo '<p class="error">This page has been accessed in error.</p>';
	}

} // End of the main submission conditional.

mysql_close($dbc);
		
include ('footer.php');
?>