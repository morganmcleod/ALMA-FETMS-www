<?php
include('mysql_connect.php');
include ('class.generictable.php');
include ('class.ifswitch_output_cable.php');

echo "<a href='http://www.cv.nrao.edu/php-internal/ntc/SWTasks/Bugs/AddNewBug.php?modulekey=42'>
				Submit Bug report or feature request</a><br><br><br>";


echo "<a href='ifcable.php?keyId=0'>Add new cable record</a><br><br>";


$shortlong = "%";
if (isset($_REQUEST['shortlong'])){
	$shortlong = $_REQUEST['shortlong'];
}


echo '
<form action="' . $PHP_SELF . '" method="POST">';
	echo "
	<select name='shortlong' onChange='submit()'>";
		if ($shortlong=="short"){
			$option_shortlong = "<option value='short' selected = 'selected'>Short</option>";
			$option_shortlong .= "<option value='long'>Long</option>";
			$option_shortlong .= "<option value='%'>Short/Long</option>";
		}
		if ($shortlong=="long"){
			$option_shortlong = "<option value='short'>Short</option>";
			$option_shortlong .= "<option value='long' selected = 'selected'>Long</option>";
			$option_shortlong .= "<option value='%'>Short/Long</option>";
		}
		if ($shortlong=="%"){
			$option_shortlong = "<option value='short'>Short</option>";
			$option_shortlong .= "<option value='long'>Long</option>";
			$option_shortlong .= "<option value='%' selected = 'selected'>Short/Long</option>";
		}	
	
		echo $option_shortlong;
	echo "</select></form>";




$qList = "SELECT keyId FROM IFswitch_output_cables
		WHERE SN <> '' 
		AND cabletype LIKE '$shortlong'  
		ORDER BY cabletype ASC, SN ASC;";
$rList = @mysql_query($qList,$dbc);

//Table Header
echo "";
echo '<table align="left" cellspacing="1" cellpadding="1" width="90%" bgcolor="#000000">';
echo '<tr bgcolor="#ffff66">
		<td bgcolor="#000000" align = "center" colspan = "3"><font size="+1" color="#ffffff">IF Cables</font><b></b></td>
		<td colspan = "4"><b>Measured Insertion Loss</b></td>
		<td colspan = "4"><b>Mfg. Supplied Insertion Loss</b></td>
		<td colspan = "2"><b>Measured VSWR (4-12 GHz)</b></td>
		<td colspan = "4"><b>Mfg. Supplied VSWR</b></td>
    </tr>';

echo '<tr bgcolor="#ffff66">
		<td><b>Type</b></td>
		<td><b>SN</b></td>
		<td><b>Insertion Loss</b></td>
		<td><b>4GHz</b></td>
		<td><b>8 GHz</b></td>
		<td><b>12 GHz</b></td>
		<td><b>18 GHz</b></td>
		<td><b>4GHz</b></td>
		<td><b>8 GHz</b></td>
		<td><b>12 GHz</b></td>
		<td><b>18 GHz</b></td>
		<td><b>BMA End</b></td>
		<td><b>SMP End</b></td>
		<td><b>4GHz</b></td>
		<td><b>8 GHz</b></td>
		<td><b>12 GHz</b></td>
		<td><b>18 GHz</b></td>
		<td><b>Pass/Fail</b></td>
    </tr>';

//Table data rows
while ($rowList = @mysql_fetch_array($rList)){
	$bg_color = ($bg_color=="#ffffff" ? '#dddddd' : "#ffffff");
	$IFcable = new IFswitch_output_cable;
	$IFcable ->Initialize_IFcable($rowList[0]);
	
	if (strtoupper($IFcable->propertyVals['PassFail']) == "FAIL"){
		$bg_color="#ff9999";
	}
	
	echo "<tr bgcolor='$bg_color'>
			<td>".$IFcable->GetValue('cabletype')."</td>
			";
			
			echo "<td><b><a href='ifcable.php?keyId=".$IFcable->GetValue('keyId')."'>".
			$IFcable->GetValue('SN')."</a></b></td>";

			echo "
			<td>".$IFcable->propertyVals['InsertionLoss']."</td>
			<td>".$IFcable->propertyVals['MeasIL_4GHz']."</td>
			<td>".$IFcable->propertyVals['MeasIL_8GHz']."</td>
			<td>".$IFcable->propertyVals['MeasIL_12GHz']."</td>
			<td>".$IFcable->propertyVals['MeasIL_18GHz']."</td>
			<td>".$IFcable->propertyVals['MfgIL_4GHz']."</td>
			<td>".$IFcable->propertyVals['MfgIL_8GHz']."</td>
			<td>".$IFcable->propertyVals['MfgIL_12GHz']."</td>
			<td>".$IFcable->propertyVals['MfgIL_18GHz']."</td>
			<td>".$IFcable->propertyVals['VSWR_Meas_BMAend']."</td>
			<td>".$IFcable->propertyVals['VSWR_Meas_SMPend']."</td>
			<td>".$IFcable->propertyVals['VSWR_Mfg_4GHz']."</td>
			<td>".$IFcable->propertyVals['VSWR_Mfg_8GHz']."</td>
			<td>".$IFcable->propertyVals['VSWR_Mfg_12GHz']."</td>
			<td>".$IFcable->propertyVals['VSWR_Mfg_18GHz']."</td>";
			
			
			if (strtoupper($IFcable->propertyVals['PassFail']) == "PASS"){
				echo "<td bgcolor='#99ff66' ><b>PASS</b></td>";
			}
			if (strtoupper($IFcable->propertyVals['PassFail']) == "FAIL"){
				echo "<td><b>FAIL</b></td>";
			}
			

			
			echo "</tr>";

	unset($IFcable );
}

echo "</table>";


?>