<?php
include('mysql_connect.php');


$q = "SELECT AssyPN, Title FROM ShipItems";
$r = @mysql_query ($q, $dbc);


while ($row = mysql_fetch_array($r)) {
	$tempPTN = $row[0];
	$Description = $row[1];
	
	$qDesc = "SELECT ProductTreeNumber FROM ComponentTypes 
			WHERE ProductTreeNumber = '$tempPTN'";
	echo  $qDesc . "<br>";
			
	$rDesc = @mysql_query ($qDesc, $dbc);
	echo "NumRows: " . @mysql_num_rows($rDesc);
	if (@mysql_num_rows($rDesc) < 1){
		echo $tempPTN . ", " . $Description . "<br>";
		$qInsert = $q = "INSERT INTO ComponentTypes (ProductTreeNumber, Description) 
		VALUES ('$tempPTN', '$Description')";	
		$rInsert = @mysql_query ($qInsert, $dbc);
		
	}
}





?>