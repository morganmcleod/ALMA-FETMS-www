<?php 
$page_title = 'Delete the Selected Record';
include ('header.php');
include('rxclasses.php');
echo '<h1>Delete the Selected Record</h1>';
$id=$_REQUEST['id'];

if (isset($_POST['submitted'])) {
	

	if ($_REQUEST['sure'] == 'Yes') { // Delete the record.
		$RxItem = new ReceivedItem_class;	
		$RxItem ->Initialize($id);
		$RxItem->RequestValues();
			
			If ($RxItem->Password == $RxItem->CorrectPassword){
				$RxItem ->Delete_Record();
			}
			If ($RxItem->Password != $RxItem->CorrectPassword){
				echo "Incorrect password. Record NOT deleted.";
				echo '<meta http-equiv="Refresh" content="1;url=view_rxitem_full_record.php?id=' 
				. $id . '">';
			}
	} 
	if ($_REQUEST['sure'] == 'No') {
		echo '<p>The record has NOT been deleted.</p>';	
		echo '<meta http-equiv="Refresh" content="1;url=view_rxitem_full_record.php?id=' 
		. $id . '">';
	}

	
	
	
} 

if (!isset($_POST['submitted'])) {
	$RxItem = new ReceivedItem_class;
	$RxItem->Initialize($id);
	$RxItem->Display_delete_form();
} 

include ('footer.php');
?>