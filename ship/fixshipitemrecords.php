<?php
include("mysql_connect.php");
include "header.php";


$q = "select distinct(fkShipment) from ShipItems where fkComponentType = 0;";
$r = mysqli_query($link, $q,$dbc);

while($row=mysqli_fetch_array($r)){
	$badshipment=$row[0];
	$qbs = "SELECT Title from Shipments WHERE keyId = $badshipment;";
	$rbs = mysqli_query($link, $qbs,$dbc);
	$shiptitle = ADAPT_mysqli_result($rbs,0);
	
	echo "<b>Shipment Rec# $badshipment ($shiptitle)</b><br>";
	$q2 = "select Title,keyId from ShipItems 
	where fkComponentType = 0
	AND fkShipment = $badshipment
	;";
	$r2 = mysqli_query($link, $q2,$dbc);
	echo "<p>";
	while($row2=mysqli_fetch_array($r2)){
		$baditem=$row2[0];
		$keyId=$row2[1];
		echo "bad item: $baditem, keyId $keyId<br>";
		
		$qcom = "SELECT Description,keyId FROM ComponentTypes 
				WHERE Description = '$baditem';";
				//echo "<br>$qcom<br>";
		$rcom = mysqli_query($link, $qcom,$dbc);
		$rowcom=mysqli_fetch_array($rcom);
		$ctypedescription = $rowcom[0];
		$ctypekeyId = $rowcom[1];
		
		if ($ctypekeyId != ""){
		echo "<p><b>Description, keyId= $ctypedescription,$ctypekeyId</b>";
		echo "</p>";
		$qUpdate = "UPDATE ShipItems Set fkComponentType = $ctypekeyId
		WHERE keyId = $keyId LIMIT 1;";
		
		//Uncomment this to make it work
		//$rUpdate = mysqli_query($link, $qUpdate,$dbc);
		//echo "qUpdate= $qUpdate<br>";
		
		}

	}
	echo "</p><br>";
}





include "footer.php";
?>