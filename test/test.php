<?php

echo "TEST<br>";


$sn1 = "410.02.03.07.40";


if (strpos($sn1,".") > 0){
	$snarray = explode(".", $sn1); 
	
	echo "count= " . count($snarray);
	$snNEW = $snarray[count($snarray)-1];
	echo "sn NEW = $snNEW <br>";
}



?>