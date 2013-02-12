<?php 
$page_title = 'Delete the Selected Shipment';
include ('header.php');
include ('ship_classes.php');
echo '<h1>Delete the Selected Shipment</h1>';
$id = $_REQUEST['id'];
$Shipment = new Shipment_class();
$Shipment->Initialize($id);


if (isset($_POST['submitted'])) 
{
		if ($_POST['sure'] == 'Yes') 
		{ 	
			$Shipment->RequestValues();
			if ($Shipment->Password == $Shipment->CorrectPassword){
				$Shipment->Delete_record();	
			}
		}
		else 
		{ 
			echo '<p>The record has NOT been deleted.</p>';	
		}
		if ($Shipment->Password != $Shipment->CorrectPassword){
			echo "Incorrect password.";
		}
} 


if (!isset($_POST['submitted'])) 
{
	$Shipment->Display_delete_form();
} 

include ('footer.php');
?>