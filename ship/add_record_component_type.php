<?php # Script 8.3 - register.php
$page_title = 'Add a Component Type to the Database';
include ('header.php');

if (isset($_GET['pw'])){
	if ($_GET['pw'] == '0'){
		echo '<script language="javascript">window.alert("Incorrect Password!");</script>';
	}
}


// Check if the form has been submitted:
if (isset($_POST['submitted']))
 {
 	include ('mysql_connect.php'); // Connect to the db.
	//$errors = array(); // Initialize an error array.
	$ProductTreeNumber = trim($_POST['ProductTreeNumber']);
	$Description = trim($_POST['Description']);
	$Docs = trim($_POST['Docs']);
	$tempPassword = mysqli_real_escape_string($link, $dbc, trim($_POST['Password']));


	if ($tempPassword == "nrao")
	{

	// Make the query:
	$q = "INSERT INTO ComponentTypes (ProductTreeNumber, Description, Docs) VALUES ('$ProductTreeNumber', '$Description', '$Docs')";		
	$r = mysql_query ($q, $dbc); // Run the query.

	// Print a message:
	echo '<h1>Thank you!</h1>
	<p>You have entered a new record into the table.</p><p><br /></p>';	
	mysql_close($dbc); // Close the database connection.
	echo '<meta http-equiv="Refresh" content="1;url=vieweditaddrecords.php">';
	}
	else
	{
		mysql_close($dbc); // Close the database connection.
		header( 'Location: add_record_component_type.php?pw=0');
	}
	// Include the footer and quit the script:
	include ('footer.php'); 
	exit();
 }
 
 
//} // End of the main Submit conditional.
?>
<b>
<h1>Enter New Record for ComponentTypes Table</h1>
<form action="add_record_component_type.php" method="post">
	<p>Product Tree Number (Example- 40.01.01.01) : <input type="text" name="ProductTreeNumber" size="30" maxlength="30" value="<?php if (isset($_POST['SN'])) echo $_POST['SN']; ?>" /></p>
	<p>Description: <input type="text" name="Description" size="60" maxlength="60" value="<?php if (isset($_POST['Description'])) echo $_POST['Description']; ?>"  /> </p>
	<p>Docs: <input type="text" name="Docs" size="60" maxlength="80" value="<?php if (isset($_POST['Docs'])) echo $_POST['Docs']; ?>" /></p>
	<p><font color ="#CC0000" >
	Password: </font><input type="text" name="Password" size="10" maxlength="20" value="********"  />
	<p><input type="image" src="pics/submit.bmp" name="submit" value="SUBMIT" /></p>
	<input type="hidden" name="submitted" value="TRUE" /></b>
</form>
<?php
include ('footer.php');
?>