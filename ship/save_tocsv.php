<?php

include('mysql_connect.php');
$id = $_REQUEST['id'];



$q = "Select x, y, XYval from test WHERE keyDataSet = $id";
$r = @mysql_query ($q, $dbc);

$count = 0;

//get data from table
while($row = mysql_fetch_array($r)){
	$xarray[$count] = $row[0];
	$yarray[$count] = $row[1];
	$XYarray[$count] = $row[2];
	$count = $count + 1;
}

$Xvals = arrayUnique($xarray);
$Yvals = arrayUnique($yarray);

//Sort arrays into ascending order
sort($Xvals);
sort($Yvals);



//Create and open the csv file
header("Content-type: application/x-msdownload");
header("Content-Disposition: attachment; filename=exported.csv");
header("Pragma: no-cache");
header("Expires: 0");

//require_once( "db.php" );

//Write out the first row, which has "XY" followed by the X indices
echo "XY,"; 
for($i=0;$i<sizeof($Xvals);$i++){	
echo $Xvals[$i] . ",";
}
echo "\r\n";


//
for ($j=0;$j<sizeof($Yvals);$j++){
	//Write current Y index value to first column
	echo $Yvals[$j] . ",";

	//Go through and write XYvals for this X,Y index
	for ($i=0;$i<sizeof($Xvals);$i++){
	$qXY = "Select XYval from test WHERE keyDataSet = 1 AND x = $Xvals[$i] AND y = $Yvals[$j]";
	$rXY = @mysql_query ($qXY, $dbc);
	$rowXY = mysql_fetch_array($rXY);
	$tempXYval = $rowXY[0];
	echo $tempXYval . ",";
	}
	echo "\r\n";
}



//Function to create an array of unique values from an array with repeated values
function arrayUnique($myArray)
{
    sort($myArray);
    $count = 1;
    $returnArray[0]=$myArray[0];
    
    for ($i=0; $i<sizeof($myArray)-1;$i++){
    $tempVal1 = $myArray[$i];
	$tempVal2 = $myArray[$i+1];

    	if ($tempVal2 != $tempVal1){
    		$returnArray[$count] = $tempVal2;
    		$count = $count+1;
    	}
    }//end for

    return $returnArray;
}



?>
