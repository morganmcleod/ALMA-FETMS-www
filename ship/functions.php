<?php




function UpdateShipItemsPTN(){	
	include('mysql_connect.php');
	$q = "SELECT PN, keyId FROM ShipItems";
	$r = @mysql_query ($q, $dbc);
	while ($row = mysql_fetch_array($r)) {
		$tempPN = $row[0];
		$id = $row[1];
		$array = explode(".",$tempPN);
		if (strlen($array[0]) == 2){
			$PTNarray = explode("-",$tempPN);
			echo $tempPN . ", PTN: " . $PTNarray[0] . "<br>";
			$PTN = $PTNarray[0];
		$qInsert = "UPDATE ShipItems SET ProductTreeNumber='$PTN'
		WHERE keyId=$id LIMIT 1";
		$rInsert = @mysql_query ($qInsert, $dbc);
			
			
		}
		
	}
}//end function


function ErasePTN(){
include('mysql_connect.php');
	$q = "SELECT PN, keyId FROM ShipItems";
	$r = @mysql_query ($q, $dbc);
	while ($row = mysql_fetch_array($r)) {
		$tempPN = $row[0];
		$id = $row[1];
		$array = explode(".",$tempPN);
		if (strlen($array[0]) == 2){
			$PTNarray = explode("-",$tempPN);
			echo $tempPN . ", PTN: " . $PTNarray[0] . "<br>";
		}
		$PTN = $PTNarray[0];
		$qInsert = "UPDATE ShipItems SET ProductTreeNumber=''
		WHERE keyId=$id LIMIT 1";
		$rInsert = @mysql_query ($qInsert, $dbc);
	}
}


//ErasePTN();



UpdateShipItemsPTN();







?>