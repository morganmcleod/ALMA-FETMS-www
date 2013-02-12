<?php # Script 9.3 - edit_user.php

// This page is for editing a user record.
// This page is accessed through view_users.php.

$page_title = 'Edit the Selected Component Type Record';
include ('header.php');

echo '<h1>Edit the Selected Component Type Record</h1>';

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
if (isset($_POST['submitted'])) {

		$errors = array();
		$PTN = mysql_real_escape_string($_POST['PTN']);
		$DescriptionEdit = mysql_real_escape_string($_POST['Description']);
		$DocsEdit = mysql_real_escape_string($_POST['Docs']);
		$tempPassword = mysql_real_escape_string($_POST['Password']);

if ($tempPassword == "nrao")
{	
			
	if (empty($errors)) { // If everything's OK.
	
			// Make the query:
			$q = "UPDATE ComponentTypes SET ProductTreeNumber='$PTN', Description='$DescriptionEdit',Docs='$DocsEdit' WHERE keyId=$id LIMIT 1";
			$r = @mysql_query ($q, $dbc);
			if (mysql_affected_rows($dbc) == 1) { // If it ran OK.
			
				// Print a message:
				echo '<p>The record has been edited.</p>';	
							
			} 
			else { // If it did not run OK.
				echo '<p class="error">The record could not be edited due to a system error. We apologize for any inconvenience.</p>'; // Public message.
				echo '<p>' . mysql_error($dbc) . '<br />Query: ' . $q . '</p>'; // Debugging message.
			}
				
	} 
	else { // Report the errors.
		echo '<p class="error">The following error(s) occurred:<br />';
		foreach ($errors as $msg) { // Print each error.
			echo " - $msg<br />\n";
		}
		echo '</p><p>Please try again.</p>';
	} // End of if (empty($errors)) IF.
	
}//if ($tempPassword == "asdf5!")
	else
	{
		echo '<script language="javascript">window.alert("Incorrect Password!");</script>';
	}
} // End of submit conditional.

// Always show the form...

// Retrieve the user's information:
$q = "SELECT ProductTreeNumber, Description, Docs FROM ComponentTypes WHERE keyId=$id";		
$r = @mysql_query ($q, $dbc);


if (mysql_num_rows($r) == 1) { // Valid user ID, show the form.

	// Get the user's information:
	$row = mysql_fetch_array ($r, MYSQL_NUM);

	// Create the form:
	echo '<form action="edit_record_component_type.php" method="post">
<font size="+1">
<p>Product Tree Number: <input type="text" name="PTN" size="30" maxlength="30" value="' . $row[0] . '" /></p>
<p>Description: <input type="text" name="Description" size="60" maxlength="60" value="' . $row[1] . '" /></p>
<p>Docs: <input type="text" name="Docs" size="100" maxlength="200" value="' . $row[2] . '" /></p>
<p><font color ="#CC0000">Password: </font><input type="text" name="Password" size="10" maxlength="20" value="********"  /> </p>

<p><input type="image" src="pics/submit.bmp" name="submit" value="Submit" /></p>
<input type="hidden" name="submitted" value="TRUE" />
<input type="hidden" name="id" value="' . $id . '" />
</font>
</form>';

} else { // Not a valid user ID.
	echo '<p class="error">This page has been accessed in error.</p>';
}

mysql_close($dbc);
		
include ('footer.php');
?>