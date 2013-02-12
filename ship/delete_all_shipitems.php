<?php 
$page_title = 'Delete the Selected Shipment';
include ('header.php');
include ('ship_classes.php');
echo '<h1>Delete Ship Items</h1>';
$id = $_REQUEST['id'];
$Shipment = new Shipment_class();
$Shipment->Initialize($id);


if (isset($_POST['submitted'])) 
{
		if ($_POST['sure'] == 'Yes') 
		{ 	
			$Shipment->RequestValues();
			if ($Shipment->Password == $Shipment->CorrectPassword){
				$Shipment->Delete_all_ship_items();	
			}
		}
		else 
		{ 
			echo '<p>The record has NOT been deleted.</p>';	
		}
		if ($Shipment->Password != $Shipment->CorrectPassword){
			echo "Incorrect password.";
		}
		echo '<meta http-equiv="Refresh" content="1;
		url=view_shipment_full_record.php?id=' . $Shipment->keyId . '">';	
} 


if (!isset($_POST['submitted'])) 
{
	$Shipment->Display_delete_form();
} 

include ('footer.php');
?>