<?php
include('header.php');
include('rxclasses.php');

$DamTable = new DamagesTable_class();
$DamTable->Initialize();

echo "<br><br>";
$DamTable->TableHeader();
$DamTable->TableRows();
$DamTable->TableFooter();
include('footer.php');
?>