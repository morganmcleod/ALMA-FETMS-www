<?php # Script 9.3 - edit_user.php

// This page is for editing a user record.
// This page is accessed through view_users.php.

$page_title = 'Edit the Selected Location Record';
include ('header.php');
 
include ('mysql_connect.php');
echo '<h1>Edit the Selected Location Record</h1>';

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



// Check if the form has been submitted:
if (isset($_POST['submitted'])) {
  
		$errors = array();
		$DescriptionEdit = mysqli_real_escape_string($link, $_POST['Description']);
		$NotesEdit = mysqli_real_escape_string($link, $_POST['Notes']);
		$tempPassword = mysqli_real_escape_string($link, $_POST['Password']);
	
if ($tempPassword == "nrao")
{		
		
	if (empty($errors)) 
	{ // If everything's OK.
			// Make the query:
			include ('mysql_connect.php');
			$q = "UPDATE Locations SET Description='$DescriptionEdit', Notes='$NotesEdit' WHERE keyId=$id LIMIT 1";
			$r = mysql_query ($q, $dbc);
			if (mysql_affected_rows($dbc) == 1) 
			{ // If it ran OK.
				// Print a message:
				echo '<p>The record has been edited.</p>';	
			} 
			else 
			{ // Report the errors.
				echo '<p class="error">The following error(s) occurred:<br />';
				foreach ($errors as $msg) 
					{ // Print each error.
						echo " - $msg<br />\n";
					}

				echo '</p><p>Please try again.</p>';
			}
	} //if (empty($errors))
	
	}//if ($tempPassword == "asdf5!")
	else
	{
		echo '<script language="javascript">window.alert("Incorrect Password!");</script>';
	}
	
} //if (isset($_POST['submitted']))





// Always show the form...

// Retrieve the selected record information:
$qLoc = "SELECT Description, Notes FROM Locations WHERE keyId=$id";	
$rLoc = mysql_query ($qLoc, $dbc);

if (mysqli_num_rows($rLoc) == 1) { // Valid ID, show the form.
	// Get the information:
	$rowLoc = mysqli_fetch_array ($rLoc, MYSQL_NUM);
	$tempDescription = $rowLoc[0];
	$tempNotes = $rowLoc[1];
	
	// Create the form:
	echo '<form action="edit_record_location.php" method="post">
	<font size = "+1">
	
	<p>Description: <input type="text" name="Description" size="60" maxlength="60" value="' . $tempDescription . '" /></p>
	<p>Notes: <input type="text" name="Notes" size="60" maxlength="80" value="' . $tempNotes . '"  /> </p>
	<p><font color ="#CC0000" >
	Password: </font><input type="text" name="Password" size="10" maxlength="20" value="********"  /></p>

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