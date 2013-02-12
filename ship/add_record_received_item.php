<?php
$page_title = 'Add a Received Item Record to the Database';
include ('header.php');
include('rxclasses.php');


if (isset($_REQUEST['submitted']))
 {
 	$RxItem = new ReceivedItem_class();
 	$RxItem->NewRecord();
 	$RxItem->RequestValues();
	$RxItem->UpdateRecord();
	echo '<h1>Thank you!</h1>
	<p>You have entered a new record into the table.</p><p><br /></p>';	
	echo '<meta http-equiv="Refresh" content="1;url=view_rxitem_full_record.php?id=' 
	. $RxItem->keyId . '">';
 }
 
 
if (!isset($_REQUEST['submitted'])){
	$RxItem = new ReceivedItem_class();
	$RxItem->RequestValues();
	$RxItem->Display_add_form();
}

include ('footer.php');
?>