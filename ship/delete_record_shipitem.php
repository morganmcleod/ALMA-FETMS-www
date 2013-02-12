<?php 
$page_title = 'Delete the Selected Shipment';
include ('header.php');
include ('ship_classes.php');
echo '<h1>Delete the Selected Shipment</h1>';
$id = $_REQUEST['id'];
$ShipItem = new Ship_item_class();
$ShipItem->Initialize($id);


if (isset($_POST['submitted'])) 
{
		if ($_POST['sure'] == 'Yes') 
		{ 	
			$ShipItem->RequestValues();
			if ($ShipItem->Password == $ShipItem->CorrectPassword){
				$ShipItem->Delete_record();	
			}
		}
		else 
		{ 
			echo '<p>The record has NOT been deleted.</p>';	
		}
		if ($ShipItem->Password != $ShipItem->CorrectPassword){
			echo "Incorrect password.";
		}
} 


if (!isset($_POST['submitted'])) 
{
	$ShipItem->Display_delete_form();
} 

include ('footer.php');
?>