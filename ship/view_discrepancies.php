<?php
include('header.php');
include('rxclasses.php');

$DisTable = new DiscrepanciesTable_class();
$DisTable->Initialize();

echo "<br><br>";
$DisTable->TableHeader();
$DisTable->TableRows();
$DisTable->TableFooter();
include('footer.php');
?>