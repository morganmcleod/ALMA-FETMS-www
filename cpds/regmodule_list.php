<?php
include('mysql_connect.php');
include ('class.generictable.php');
include ('class.regmodule.php');

if (isset($_REQUEST['exportcsv'])){
	echo '<meta http-equiv="Refresh" content="1;url=export_to_csv.php">';
}


echo '
<form action="' . $PHP_SELF . '" method="POST">';

echo "<br><input type='submit' name = 'exportcsv' value='EXPORT TO CSV'>";


$qList = "SELECT keyId FROM CPDS_RegModules
		WHERE SN <> ''
		ORDER BY SN ASC;";
$rList = @mysql_query($qList,$dbc);

//Table Header
echo "";
echo '<table align="left" cellspacing="1" cellpadding="1" width="90%" bgcolor="#000000">';
echo '<tr bgcolor="#ffff66">
		<td bgcolor="#000000" align = "center" colspan = "6"><font size="+1" color="#ffffff">Regulator Modules</font><b></b></td>
    </tr>';

echo '<tr bgcolor="#ffff66">
		<td><b>SN</b></td>
		<td><b>Date</b></td>
		<td><b>Functional Test</b></td>
		<td><b>Visual Inspection</b></td>
		<td><b>Notes</b></td>
		<td><b>Log</b></td>
    </tr>';


while ($rowList = @mysql_fetch_array($rList)){
	$bg_color = ($bg_color=="#ffffff" ? '#dddddd' : "#ffffff");
	$RegModule = new RegulatorModule;
	$RegModule ->Initialize_RegulatorModule($rowList[0],$dbc);
	
	echo "<tr bgcolor='$bg_color'>";
			$keyId = $rowList[0];
			echo "
			<td><a href='regmodule.php?keyId=$keyId'>".$RegModule->GetValue('SN')."</a></td>
			<td>".$RegModule->GetValue('Date')."</td>";
			if (strtoupper($RegModule->GetValue('PassFail_Functional')) == "1"){
				echo "<td bgcolor='#99ff66' ><b>PASS</b></td>";
			}
			if (strtoupper($RegModule->GetValue('PassFail_Functional')) == "0"){
				echo "<td><b>FAIL</b></td>";
			}
			if (strtoupper($RegModule->GetValue('PassFail_Visual')) == "1"){
				echo "<td bgcolor='#99ff66' ><b>PASS</b></td>";
			}
			if (strtoupper($RegModule->GetValue('PassFail_Visual')) == "0"){
				echo "<td bgcolor='#FA5858' ><b>FAIL</b></td>";
			}
			
			echo "
			<td>".$RegModule->GetValue('Notes')."</td>
			<td>".$RegModule->GetValue('Log')."</td>";
			echo "</tr>";

	unset($RegModule);
}

echo "</table>";


?>