<?php # Script 8.3 - register.php
$page_title = 'Add a Location to the Database';
include ('header.php');

if (isset($_GET['pw']))
{
	if ($_GET['pw'] == '0')
	{
		echo '<script language="javascript">window.alert("Incorrect Password!");</script>';
	}
	
}

// Check if the form has been submitted:
if (isset($_POST['submitted']))
 {
 	include ('mysql_connect.php'); // Connect to the db.
	$Description = $_POST['Description'];
	$Notes= $_POST['Notes'];
	$tempPassword = mysqli_real_escape_string($link, $_POST['Password']);
	
	
	if ($tempPassword == "nrao")
	{
		
		// Make the query:
		$qInsert = "INSERT INTO Locations (Description, Notes) VALUES ('$Description', '$Notes')";	
		
		$rInsert = mysql_query ($qInsert, $dbc); // Run the query.
	
		// Print a message:
		echo '<h1>Thank you!</h1>
		<p>You have entered a new record into the table.</p><p><br /></p>';	
		mysql_close($dbc); // Close the database connection.
	}
	
 	else
	{
		mysql_close($dbc); // Close the database connection.
		header( 'Location: add_record_location.php?pw=0');
	}

	// Include the footer and quit the script:
	include ('footer.php'); 
	exit();
 }
 
 
//} // End of the main Submit conditional.
?>
<b>
<h1>Enter New Record for Locations Table</h1>
<form action="add_record_location.php" method="post">
	<p>Description: <input type="text" name="Description" size="60" maxlength="60" value="<?php if (isset($_POST['Description'])) echo $_POST['Description']; ?>"  /> </p>
	<p>Notes: <input type="text" name="Notes" size="60" maxlength="80" value="<?php if (isset($_POST['Notes'])) echo $_POST['Notes']; ?>" /></p>
	<p><font color ="#CC0000" >
	Password: </font><input type="text" name="Password" size="10" maxlength="20" value="********"  />
	<p><input type="image" src="pics/submit.bmp" name="submit" value="SUBMIT" /></p>
	<input type="hidden" name="submitted" value="TRUE" /></b>
	
</form>

<?php
include ('footer.php');
?>