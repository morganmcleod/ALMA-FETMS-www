<?php
include('mysql_connect.php');
	header("Content-type: application/x-msdownload");
	header("Content-Disposition: attachment; filename=CPDSRegulatorModules.csv");
	header("Pragma: no-cache");
	header("Expires: 0");

	echo "SN,Date,Functional Tests,Visual Inspection,Notes,Log\r\n";

	$q = "SELECT * FROM CPDS_RegModules
		WHERE SN <> ''
		ORDER BY SN ASC;";
	$r = @mysql_query($q,$dbc);
	while($row = mysql_fetch_array($r)){
		echo $row['SN'] . "," . $row['Date'] . ",";
		
		if ($row['PassFail_Functional'] == 1){
			echo "PASS" . ",";
		}
		else{
			echo "FAIL" . ",";
		}
		if ($row['PassFail_Visual'] == 1){
			echo "PASS" . ",";
		}
		else{
			echo "FAIL" . ",";
		}

		echo str_replace(",",";",$row['Notes']) . ",";
		echo str_replace(",",";",$row['Log']) . ",";
		echo "\r\n";;
	}	
?>
