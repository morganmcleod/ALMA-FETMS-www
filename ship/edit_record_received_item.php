<?php
$page_title = 'Edit a Received Item Record';
include ('header.php');
include('rxclasses.php');
// Check if the form has been submitted:

$id = $_REQUEST['id'];
if (isset($_REQUEST['submitted']))
 {
 	$RxItem = new ReceivedItem_class();
 	$RxItem->Initialize($id);
 	$RxItem->RequestValues();
 	
 	if ($RxItem->Password == $RxItem->CorrectPassword){
		$RxItem->UpdateRecord();
		echo '<p>Record has been edited.</p>';	
		echo '<meta http-equiv="Refresh" content="1;url=view_rxitem_full_record.php?id=' 
		. $RxItem->keyId . '">';
 	}
 	if ($RxItem->Password != $RxItem->CorrectPassword){
		echo '<p>Incorrect Password. Record NOT edited.</p>';	
 	}
 	
 }
 
 
if (!isset($_REQUEST['submitted'])){
	echo '<h1>Edit the Selected Record</h1>';
	$RxItem = new ReceivedItem_class();
	$RxItem->Initialize($id);
	$RxItem->BeingEdited = TRUE;
	$RxItem->RequestValues();
	$RxItem->Display_add_form("edit");
}

include ('footer.php');
?>
