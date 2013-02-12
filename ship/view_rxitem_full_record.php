<?php
$page_title = "Received Item Record";

include('mysql_connect.php');
include('rxclasses.php');
include('header.php');
$id = $_REQUEST['id'];

$RxItem = new ReceivedItem_class();
$RxItem->Initialize($id);
$RxItem->Display_data();

include('footer.php');
?>