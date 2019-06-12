<?php

include('mysql_connect.php');

//This script looks for and replaces duplicate LogNumbers in the ReceivedItems table.




for ($i=65;$i<91;$i++){
	$tempLetter = chr($i);

	
	$qall = "SELECT keyId, LogNumber FROM ReceivedItems
			WHERE LogNumber LIKE '$tempLetter-%'
			ORDER BY LogNumber ASC;";
	$rall = mysqli_query($link, $qall,$dbc);
	while($rowall = mysqli_fetch_array($rall)){
		$temp_keyId = $rowall[0];
		$temp_LogNumber = $rowall[1];
		
		//echo "$temp_keyId,$temp_LogNumber<br>";
		
		$qdup = "SELECT keyId,LogNumber FROM ReceivedItems
				WHERE LogNumber = '$temp_LogNumber' ORDER BY LogNumber ASC;";
					
		$rdup = mysqli_query($link, $qdup,$dbc);
		$numrows = mysqli_num_rows($rdup);
		
		
		if ($numrows > 1){
			echo "Duplicate found...<br>";
			$arrayindex=0;
			while($rowreplace = mysqli_fetch_array($rdup)){
				if ($arrayindex > 0){
				$MaxLog=GetMaxNumber($tempLetter);
				echo "Old LogNo: $rowreplace[1], New LogNo: $MaxLog, keyId=$rowreplace[0]<br>";
				$qupdate = "UPDATE ReceivedItems SET LogNumber='$MaxLog' WHERE keyId=$rowreplace[0]
							LIMIT 1;";
				$rupdate=mysqli_query($link, $qupdate,$dbc);			
				
			    //echo "<br>$qupdate<br>";
				}
				$arrayindex++;
				
			}
			
			
		}
		
		$arraindex=0;
		
		
	}



}


mysql_close($dbc);



function GetMaxNumber($FirstLetter){
	include('mysql_connect.php');
	$q = "SELECT max(LogNumber) FROM ReceivedItems 
	WHERE LogNumber LIKE '$FirstLetter-%'
	AND LogNumber <> '';";

	$r = mysqli_query($link, $q,$dbc);
	$tempResult = ADAPT_mysqli_result($r,0);
	$tempNum = split("-",$tempResult);
	$newNum = substr("0000" . strval($tempNum[1]+1),-4,4);
	$tempResult = "$FirstLetter-$newNum";
	return $tempResult;
}



	

?>