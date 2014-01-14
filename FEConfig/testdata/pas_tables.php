<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_FEConfig . '/testdata/spec_functions.php');
require_once($site_dbConnect);

function table_header ( $width , &$tdh ){
    $table_ver = "1.0.4";
    $testpage = 'testdata.php';

    $q = "SELECT `Description` FROM `TestData_Types`
        WHERE `keyId` = ".$tdh->GetValue('fkTestData_Type')."";
    $r = @mysql_query($q,$tdh->dbconnection);
    $test_name = @mysql_result($r,0,0);

    // decide if the box is checked
    if ($tdh->GetValue('DataSetGroup') == 1){
        $value = "checked";
    } else {
        $value = "unchecked";
    }

    // First title block line with check box
    echo "<div style= 'width:".$width."px'>
        <table id='table1'>
        <tr class = 'alt'><th colspan='100'>$test_name";
        // cheesy for loop to put spaces in between table title and check box
        for ($i = 1; $i <= pow($width/27.5,1.48)-(strlen($test_name)*1.75); $i++) {
            echo "&nbsp";
        }
    echo "<input type='hidden' name='checkbox[]' value='".$tdh->GetValue('keyId')."'>
        <input type='checkbox' name='checkbox[]' value='".$tdh->GetValue('keyId')."' $value>
        Include in PAS Report<br></th></tr>";

    // second title block line
    echo "<tr class = 'alt'><th colspan='100'>".$tdh->GetValue('TS')."
        , TestData_header.key_ID: <a href='$testpage?keyheader=".$tdh->GetValue('keyId')."&fc=40' target = 'blank'>".$tdh->GetValue('keyId')."</a>
        </th></tr>";

    //third title block line
    // check to see if it was a FE component test or a FE config test
    if ($tdh->GetValue('fkFE_Config') != 0){
        echo "<tr class = 'alt'><th colspan='100'> FE Config: ".$tdh->GetValue('fkFE_Config')."
            , Table SWVer: $table_ver, Meas SWVer: ".$tdh->GetValue('Meas_SWVer')."
            </th></tr>";
    } else {
        echo "<tr class = 'alt'><th colspan='100'> FE Component: ".$tdh->GetValue('fkFE_Components')."
            , Table SWVer: $table_ver, Meas SWVer: ".$tdh->GetValue('Meas_SWVer')."
            </th></tr>";
    }

    //forth title block line
    echo "<tr class = 'alt'><th colspan='100'> Test Data Notes: ".$tdh->GetValue('Notes')."
        </th></tr>";
}


function band_results_table($FE_Config,$band,$Data_Status,$TestData_Type){

    $db = site_getDbConnection();
    $q = "SELECT keyId FROM `TestData_header`
        WHERE `fkFE_Config` = $FE_Config
        AND `fkTestData_Type` = $TestData_Type
        AND BAND = $band AND fkDataStatus = $Data_Status";
    $r = @mysql_query($q,$db) or die("QUERY FAILED: $q");

    $cnt = 0;
    while ($row = @mysql_fetch_array($r)){

        switch ( $TestData_Type ){
            case 1:
                LNA_results($row[0]);
                break;
            case 2:
                Temp_Sensor_results($row[0]);
                break;
            case 3:
                SIS_results($row[0]);
                break;
            case 6:
                IF_Power_results($row[0]);
                break;
            case 12:
                WCA_AMC_results($row[0]);
                break;
            case 13:
                WCA_PA_results($row[0]);
                break;
            case 14:
                WCA_MISC_results($row[0]);
                break;
            case 15:
                Y_factor_results($row[0]);
                break;
            case 39:
                I_V_Curve_results($row[0]);
                break;
        }
    }
}

function results_table($FE_Config,$Data_Status,$TestData_Type){
    $db = site_getDbConnection();
    $q = "SELECT keyId FROM `TestData_header`
        WHERE `fkFE_Config` = $FE_Config
        AND `fkTestData_Type` = $TestData_Type
        AND fkDataStatus = $Data_Status";
    $r = @mysql_query($q,$db) or die("QUERY FAILED: $q");
    while ($row = @mysql_fetch_array($r)){
        switch ($TestData_Type){
            case 4:
                Cryo_Temp_results($row[0]);
                break;
            case 5:
                FLOOG_results($row[0]);
                break;
            case 8:
                LPR_results($row[0]);
                break;
            case 9:
                Photomixer_results($row[0]);
                break;
            case 10:
                IF_Switch_Temp_results($row[0]);
                break;
            case 24:
                CPDS_results($row[0]);
                break;

        }
    }
}


/**
 * returns a number formated to display only two decimal places
 *
 * @param $number (float) - number to format
 *
*/
function mon_data ($number){
    return number_format((float)$number, 2, '.', '');
}

function update_dataset($td_keyID,$data_set_group){
    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID,"40");
    $tdh->SetValue('DataSetGroup',$data_set_group);
    $tdh->Update();
}


// CPDS monitors
/**
 * echos a HTML table that contains CPDS monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
*/
function CPDS_results($td_keyID){
    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID,"40");

    $q = "SELECT `Band`, `P6V_V`,`N6V_V`,`P15V_V`,`N15V_V`,`P24V_V`,`P8V_V`,`P6V_I`,`N6V_I`,`P15V_I`,`N15V_I`,`P24V_I`,`P8V_I`
            FROM `CPDS_monitor`
            WHERE `fkHeader` = $td_keyID
            ORDER BY BAND ASC";
    $r = @mysql_query($q,$tdh->dbconnection) or die("QUERY FAILED: $q");

    table_header ( 900,$tdh);

    // write table subheader
    $Col_name = array("Band", "+6V Voltage", "-6V Voltage", "+15V Voltage", "-15V Voltage", "+24V Voltage", "+8V Voltage","+6V Current","-6V Current","+15V Current","-15V Current","+24V Current","+8V Current");
    echo "</tr>";
    foreach ($Col_name  as $Col) {
        echo "<th>".$Col."</th>";
        $i++;
    }
    echo "</tr>";

    // Write data to table
    while ($row = @mysql_fetch_array($r)){
        echo "<tr>";
        for ($i = 0; $i < 13; $i++) {
            echo "<td>$row[$i]</td>    ";
        }
        echo "<tr>";
    }
    echo "</table></div>";
}


// LNA - Actual Readings
/**
 * echos a HTML table that contains CCA LNA monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
*/
function LNA_results($td_keyID){

    // get specifications array
    $spec = get_specs(1,0);

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID,"40");

    //get and save Monitor Data
    $q = "SELECT Pol, SB, Stage, VdRead, IdRead, VgRead, FreqLO
        FROM CCA_LNA_bias
        WHERE fkHeader = $td_keyID ORDER BY `Pol`ASC, `SB` ASC, Stage ASC";
    $r = @mysql_query($q,$tdh->dbconnection) or die("QUERY FAILED: $q");

    // format data to put in table
    $cnt=0;
    while ($row = @mysql_fetch_array($r)){
        for ($i = 0; $i < 6; $i++) {
            // format monitor data only for floats
            if ($i > 2){
                $mon_data = mon_data ($row[$i]);
            } else {
                $mon_data = $row[$i];
            }
            $LNA_Mon[$cnt][$i] = $mon_data;
        }
    $cnt++;
    }
    // get LO freq to get Control Data
    $FreqLO = @mysql_result($r,0,6);

    //get and save Control Data
    $q_CompID = "SELECT DISTINCT FE_Components.keyId
        FROM `FE_Components` JOIN `FE_ConfigLink`
        ON FE_Components.keyId = FE_ConfigLink.fkFE_Components
        WHERE  FE_ConfigLink.fkFE_Config =" .$tdh->GetValue('fkFE_Config')."
        AND `fkFE_ComponentType`= 20 AND Band =". $tdh->GetValue('Band')."";

    // data queries
        // default query
        $q_default = "SELECT `VD1`,`VD2`,`VD3`,`ID1`,`ID2`,`ID3`,`VG1`,`VG2`,`VG3`,`FreqLO`
                FROM `CCA_PreampParams`
                WHERE `fkComponent`=($q_CompID)
                AND `FreqLO`= $FreqLO ORDER BY `Pol`ASC, `SB` ASC";

        // query for band 6, 7, 9
        $q_6_7_9 = "SELECT `VD1`,`VD2`,`VD3`,`ID1`,`ID2`,`ID3`,`VG1`,`VG2`,`VG3`,`FreqLO`
                FROM `CCA_PreampParams`
                WHERE `fkComponent`=($q_CompID)
                ORDER BY `Pol`ASC, `SB` ASC";

    switch( $tdh->GetValue('Band') ){
        case 6:
            $q = $q_6_7_9;
            break;
        case 7:
            $q = $q_6_7_9;
            break;
        case 9:
            $q = $q_6_7_9;
            break;

        Default:
            $q = $q_default;
            break;

    }

    $r = @mysql_query($q,$tdh->dbconnection) or die("QUERY FAILED: $q");

    // reformat data to put in table
    $cnt=0;    // initialize counter
    while ($row = @mysql_fetch_array($r)){
        for ($i = 0; $i <= 2; $i++) {
            $index = $i + (3* $cnt);
            $LNA_Cntrl[$index][0] = $row[$i];
            $LNA_Cntrl[$index][1] = $row[$i+3];
            $LNA_Cntrl[$index][2] = $row[$i+6];
        }
    $cnt++;
    }
    $Cntrl_FreqLO = @mysql_result($r,0,9);

    table_header ( 700,$tdh);
    echo "<tr><th colspan='2' rowspan='2'>Device</th>
        <th colspan='3'>Control Values: (LO $Cntrl_FreqLO Ghz)</th>
        <th colspan='3'>Monitor Values: (LO $FreqLO Ghz)</th></tr>
        <th>Vd(V)</th>
        <th>Id(mA)</th>
        <th>Vg(V)</th>
        <th>Vd(V)</th>
        <th>Id(mA)</th>
        <th>Vg(V)</th>";

    $prev_SB = -1;
    $prev_Pol = -1;
    $cnt = count($LNA_Cntrl);
    if ($cnt ==0){
        $cnt = count($LNA_Mon);
    }

    for ($i = 0; $i < $cnt; $i++) {

        echo "<tr>";
        // don't display cell unless is has changed
        if ( $prev_SB  != $LNA_Mon[$i][1] || $prev_Pol  != $LNA_Mon[$i][0]){
            echo "<td width = '100px'>Pol".    $LNA_Mon[$i][0]." LNA".$LNA_Mon[$i][1]. "</td>";
        } else {
            echo "<td width = '100px'></td>";
        }
        $prev_Pol =$LNA_Mon[$i][0];
        $prev_SB = $LNA_Mon[$i][1];

        echo "<td width = '75px'> Stage ".$LNA_Mon[$i][2]."</td>
            <td width = '75px'>".$LNA_Cntrl[$i][0]."</td>
            <td width = '75px'>".$LNA_Cntrl[$i][1]."</td>
            <td width = '75px'>".$LNA_Cntrl[$i][2]."</td>";

        // check to see if Vd is in spec
        $mon_Vd = num_within_percent( $LNA_Mon[$i][3], $LNA_Cntrl[$i][0], $spec[11] );
        echo "<td width = '75px'>$mon_Vd</td> ";

        // check to see if Id is in spec
        $mon_Id = num_within_percent( $LNA_Mon[$i][4], $LNA_Cntrl[$i][1], $spec[12] );
        echo "<td width = '75px'>$mon_Id</td>
            <td width = '75px'>".$LNA_Mon[$i][5]."</td></tr>";
    }
    echo "</table></div>";
}


// SIS – Actual Readings
/**
 * echos a HTML table that contains SIS monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
*/
function SIS_results($td_keyID){

    // get specifications array
    $spec = get_specs(3,0);

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID,"40");

    //get and save Monitor Data
    $q = "SELECT `Pol`,`SB`,`VjRead`,`IjRead`,`VmagRead`,`ImagRead`, FreqLO
        FROM `CCA_SIS_bias`
        WHERE `fkHeader` = $td_keyID ORDER BY `Pol`ASC, `SB` ASC";
    $r = @mysql_query($q,$tdh->dbconnection) or die("QUERY FAILED: $q");

    // format data to put in table
    $cnt=0;
    while ($row = @mysql_fetch_array($r)){
        for ($i = 0; $i < 6; $i++) {
            // format monitor data only for floats
            if ($i > 1){
                $mon_data = mon_data ($row[$i]);
            } else {
                $mon_data = $row[$i];
            }
            $SIS_Mon[$cnt][$i] = $mon_data;
        }
        $cnt++;
    }
    // get LO freq to get Control Data
    $FreqLO = @mysql_result($r,0,6);

    //get and save Control Data
    $q_CompID = "SELECT DISTINCT FE_Components.keyId
        FROM `FE_Components` JOIN `FE_ConfigLink`
        ON FE_Components.keyId = FE_ConfigLink.fkFE_Components
        WHERE  FE_ConfigLink.fkFE_Config =". $tdh->GetValue('fkFE_Config')."
        AND `fkFE_ComponentType`= 20 AND Band =". $tdh->GetValue('Band')."";

    $q = "SELECT `VJ`,`IJ`,`IMAG`,`Pol`, `SB` FROM `CCA_MixerParams`
        WHERE `fkComponent` = ($q_CompID) AND `FreqLO` = $FreqLO
        ORDER BY `Pol`ASC, `SB` ASC";

    $r = @mysql_query($q,$tdh->dbconnection);

    if (!$r) {
    	echo "No data for TDH=$td_keyID and LO=$FreqLO<br>";

    } else {
	    // reformat data to put in table
	    $cnt=0;
	    while ($row = @mysql_fetch_array($r)){
	        for ($i = 0; $i < 4; $i++) {
	            $SIS_Cntrl[$cnt][$i] = $row[$i];
	        }
	        $cnt++;
	    }

	    table_header ( 700,$tdh);
	    echo "<tr><th rowspan='2'>Device</th>
	        <th colspan='3'>Control Values</th>
	        <th colspan='4'>Monitor Values</th></tr>
	        <th>Bias Voltage (mV)</th>
	        <th>Bias Current (uA)</th>
	        <th>Magnet Current (mA)</th>
	        <th>Bias Voltage (mV)</th>
	        <th>Bias Current (uA)</th>
	        <th>Magnet Voltage (V)</th>
	        <th>Magnet Current (mA)</th>";

	    $cnt = count($SIS_Cntrl);
	    if ($cnt ==0){
	        $cnt = count($SIS_Mon);
	    }
	    for ($i = 0; $i < $cnt; $i++) {
	        echo "<tr>
	        <td width = '100px'>Pol".$SIS_Mon[$i][0]." SIS".$SIS_Mon[$i][1]. "</td>
	        <td width = '75px'>".$SIS_Cntrl[$i][0]."</td>
	        <td width = '75px'>".$SIS_Cntrl[$i][1]."</td>
	        <td width = '75px'>".$SIS_Cntrl[$i][2]."</td>";

	        // check to see if Bias voltage is in spec
	        $mon_Bias_V = num_within_percent( $SIS_Mon[$i][2], $SIS_Cntrl[$i][0], $spec[13] );
	        echo "<td width = '75px'>$mon_Bias_V</td> ";

	        // check to see if Bias currrent is in spec
	        $mon_Bias_I = num_within_percent( $SIS_Mon[$i][3], $SIS_Cntrl[$i][1], $spec[14] );
	        echo "<td width = '75px'>$mon_Bias_I</td> ";

	        echo "<td width = '75px'>".$SIS_Mon[$i][4]."</td>";

	        // check to see if Magnet currrent is in spec
	        $mon_Mag_I = num_within_percent( $SIS_Mon[$i][5], $SIS_Cntrl[$i][2], $spec[15] );
	        echo "<td width = '75px'>$mon_Mag_I</td> ";
	    }
	    echo "</table></div>";
    }
}

// Temperature Sensors – Actual Readings
/**
 * echos a HTML table that contains Temperature Sensor monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
*/
function Temp_Sensor_results($td_keyID){

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID,"40");

    table_header ( 475,$tdh);
    echo "<tr><th>Monitor Point</th>
        <th colspan='2'>Monitor Values (K)</th>";

    $Col_name = array("4k", "110k", "Pol0_mixer", "Spare", "15k", "Pol1_mixer");
    $col_strg = implode(",",$Col_name);
    $q = "SELECT $col_strg
        FROM CCA_TempSensors
        WHERE fkHeader= $td_keyID";

    $r = @mysql_query($q,$tdh->dbconnection) or die("QUERY FAILED: $q");
    $i=0;
    foreach ($Col_name  as $Col) {
        echo "<tr>";
        echo "<td width = '100px'>".$Col."</td>";
        // check to see if data Status is: Cold PAS, Cold PAI or Health check
        $test_type_array = array("1", "3", "4");
        if(in_array($tdh->GetValue('fkDataStatus'), $test_type_array)){
            // check to see if line is a 4k stage
            $cold_array = array("4k", "Pol0_mixer", "Pol1_mixer");
            if(in_array($Col, $cold_array)){
                $num= @mysql_result($r,0,$i);
                // check to see if 4k stange meets spec
                $num = chk_num_agnst_spec( $num, "<", 4);
            } else {
                $num = @mysql_result($r,0,$i);
            }
        } else {
            $num = @mysql_result($r,0,$i);
        }
        echo "<td width = '300px'>$num</td></tr>";
        $i++;
    }
    echo "</table></div>";
}


// WCA AMC Monitors
/**
 * echos a HTML table that contains WCA AMC monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
*/
function WCA_AMC_results($td_keyID){

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID,"40");

    // get WCA_AMC_bias values
    $col_name = array("VDA", "VDB", "VDE", "IDA", "IDB", "IDE","VGA","VGB","VGE","MultD","MultD_Current","5Vsupply");
    $col_strg = implode(",",$col_name);

    $q = "SELECT $col_strg
        FROM WCA_AMC_bias
        WHERE fkHeader= $td_keyID";

    $r = @mysql_query($q,$tdh->dbconnection) or die("QUERY FAILED: $q");;

    table_header ( 475,$tdh);
    echo "<tr><th>Monitor Point</th>
        <th colspan='2'>Monitor Values</th>";

    $i=0;
    foreach ($col_name  as $Col) {
        echo "<tr>
        <td width = '100px'>".$Col."</td>
        <td width = '300px'>".@mysql_result($r,0,$i)."</td></tr>";
        $i++;
    }
    echo "</table></div>";
}


// WCA PA Monitors
/**
 * echos a HTML table that contains WCA PA monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
*/
function WCA_PA_results($td_keyID){

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID,"40");

    // get PA_bias values
    $col_name = array("VDp0", "VDp1", "IDp0", "IDp1", "VGp0", "VGp1","3Vsupply","5Vsupply");
    $col_strg = implode(",",$col_name);

    $q = "SELECT $col_strg
        FROM WCA_PA_bias
        WHERE fkHeader= $td_keyID";

    $r = @mysql_query($q,$tdh->dbconnection) or die("QUERY FAILED: $q");

    table_header ( 475,$tdh);    echo "<tr><th>Monitor Point</th>
        <th colspan='2'>Monitor Values</th>";

    $i=0;
    foreach ($col_name  as $Col) {
        echo "<tr>
        <td width = '100px'>".$Col."</td>
        <td width = '300px'>".@mysql_result($r,0,$i)."</td></tr>";
        $i++;
    }
    echo "</table></div>";
}


// WCA Misc Monitors
/**
 * echos a HTML table that contains WCA Misc monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
*/
function WCA_MISC_results($td_keyID){

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID,"40");

    // get Misc_bias values
    $col_name = array("PLLtemp", "YTO_heatercurrent");
    $col_strg = implode(",",$col_name);

    $q = "SELECT $col_strg
        FROM WCA_Misc_bias
        WHERE fkHeader= $td_keyID";

    $r = @mysql_query($q,$tdh->dbconnection) or die("QUERY FAILED: $q");

    table_header ( 475,$tdh);
        echo "<tr><th>Monitor Point</th>
        <th colspan='2'>Monitor Values</th>";

    $i=0;
    foreach ($col_name  as $Col) {
        echo "<tr>
        <td width = '100px'>".$Col."</td>
        <td width = '300px'>".@mysql_result($r,0,$i)."</td></tr>";
        $i++;
    }
    echo "</table></div>";
}

// FLOOG Total Power
/**
 * echos a HTML table that contains FLOOG monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
*/
function FLOOG_results($td_keyID){

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID,"40");

    $q = "SELECT `Band`, `RefTotalPower` FROM `FLOOGdist`
            WHERE `fkHeader` = $td_keyID";
    $r = @mysql_query($q,$tdh->dbconnection) or die("QUERY FAILED: $q");

    table_header ( 475,$tdh);
    echo "<tr> <th></th>
    <th colspan='2'>Reference Total Power (dBm)</th>
    <tr>";

    while ($row = @mysql_fetch_array($r)){
        echo "<tr>
        <td width = '100px'>Band $row[0] WCA</td>
        <td width = '300px'>$row[1]</td></tr>";
    }
    echo "</table></div>";
}


// Nominal IF power levels
/**
 * echos a HTML table that contains Nominal IF power level monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
*/
function IF_Power_results($td_keyID){

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID,"40");

    $q = "SELECT `IFChannel`,`Power_0dB_gain`,`Power_15dB_gain`
        FROM `IFTotalPower`
        WHERE `fkHeader` = $td_keyID";

    $r = @mysql_query($q,$tdh->dbconnection) or die("QUERY FAILED: $q");


    table_header ( 475,$tdh);
    echo "<tr><th>IFChannel</th>
        <th>Power 0dB gain (dBm)</th>
        <th>Power 15dB gain (dBm)</th>";
    $atten_cnt=0;

    while ($row = @mysql_fetch_array($r)){
        echo "<tr>";
        $att_sum = $att_sum + (abs($row[2]) - abs($row[1]));
        $atten_cnt++;
        // check to see if the numbers meet spec
        $check1=num_in_range(($row[2]-14),mon_data($row[1]),($row[2]-16));
        $check2=num_in_range(($row[1]+16),mon_data($row[2]),($row[1]+14));
        switch ($row[0]) {

            case 0:
                echo "<td width = '200px'>IF0 Pol 0 USB</td>";
                echo "<td width = '200px'>$check1</td>";
                echo "<td width = '200px'>$check2</td></tr>";
                break;

            case 1:
                echo "<td width = '200px'>IF1 Pol 1 USB</td>";
                echo "<td width = '200px'>$check1</td>";
                echo "<td width = '200px'>$check2</td></tr>";
                break;

            case 2:
                echo "<td width = '200px'>IF2 Pol 0 LSB</td>";
                echo "<td width = '200px'>$check1</td>";
                echo "<td width = '200px'>$check2</td></tr>";
                break;

            case 3:
                echo "<td width = '200px'>IF3 Pol 1 LSB</td>";
                echo "<td width = '200px'>$check1</td>";
                echo "<td width = '200px'>$check2</td></tr>";
                break;

        }
    }
    $avg_atten = mon_data($att_sum /$atten_cnt);
    //check to see if avgerage attunuation is in range
    $check3=num_in_range(-14,$avg_atten,-16);
    echo "<tr><th width = '200px'>Average Attenutation (dB) </th>
        <th width = '200px' colspan='2' align='right'>" .$check3."</th>";
    echo "</table></div>";
}


// IF switch temperature sensors
/**
 * echos a HTML table that contains IF switch Temperature sensor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
*/
function IF_Switch_Temp_results($td_keyID){

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID,"40");

    $Col_name = array("pol0sb1", "pol0sb2", "pol1sb1", "pol1sb2");
    $col_strg = implode(",",$Col_name);
    $q = "SELECT $col_strg
        FROM IFSwitchTemps
        WHERE fkHeader= $td_keyID";

    table_header ( 475,$tdh);
    echo "<tr><th></th>
        <th colspan='2'>Monitor Values (K)</th>";

    $r = @mysql_query($q,$tdh->dbconnection) or die("QUERY FAILED: $q");
    $i=0;
    foreach ($Col_name  as $Col) {
        echo "<tr>";
        echo "<td width = '100px'>".$Col."</td>";
        echo "<td width = '300px'>".@mysql_result($r,0,$i)."</td></tr>";
        $i++;
    }
    echo "</table></div>";
}


// WCA PA Monitors
/**
 * echos a HTML table that contains WCA PA monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
*/
function LPR_results($td_keyID){

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID,"40");

    $Col_name = array("Laser Pump Temperature (K)", "Laser Drive Current (mA)", "Laser Photodetector Current (mA)", "Photodetector Current (mA)","Photodetector Power (mW)","Modulation Input (V)","TempSensor0 (K)","TempSensor1 (K)");
    $q = "SELECT `LaserPumpTemp`, `LaserDrive`, `LaserPhotodetector`, `Photodetector_mA`, `Photodetector_mW`, `ModInput`, `TempSensor0`, `TempSensor1`
        FROM `LPR_WarmHealth`
        WHERE `fkHeader`= $td_keyID";

    $r = @mysql_query($q,$tdh->dbconnection) or die("QUERY FAILED: $q");

    table_header ( 475,$tdh);
    echo "<tr><th>Monitor Point</th>
        <th colspan='2'>Monitor Values </th>";
    $i=0;
    foreach ($Col_name  as $Col) {
        echo "<tr>";
        echo "<td width = '250px'>".$Col."</td>";
        echo "<td width = '150px'>".@mysql_result($r,0,$i)."</td></tr>";
        $i++;
    }
    echo "</table></div>";
}


// Photomixer Monitor Data
/**
 * echos a HTML table that contains Photomixer monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
*/
function Photomixer_results($td_keyID){

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID,"40");

    $q = "SELECT `Band`,`Vpmx`, `Ipmx`
        FROM `Photomixer_WarmHealth`
        WHERE `fkHeader` = $td_keyID";

    $r = @mysql_query($q,$tdh->dbconnection) or die("QUERY FAILED: $q");

    $Col_name = array("Photomixer Voltage (V)", "Photomixer Current (mA)");

    table_header ( 475,$tdh);
    echo "<tr><th>Monitor Point</th>
    <th colspan='2'>Monitor Values </th>";

    while ($row = @mysql_fetch_array($r)){
        echo "<tr>
        <td width = '250px' ALIGN='LEFT'> Band $row[0]</td>
        <td width = '150px'></td></tr>
        <td width = '250px'>Photomixer Voltage (V)</td>
        <td width = '150px'>$row[1]</td></tr>
        <td width = '250px'>Photomixer Current (mA)</td>
        <td width = '150px'>$row[2]</td></tr>";
    }
    echo "</table></div>";
}


// Cryo-cooler Temperatures
/**
 * echos a HTML table that contains Cryo-cooler monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
*/
function Cryo_Temp_results($td_keyID){

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID,"40");

    $Col_name = array("4k_CryoCooler", "4k_PlateLink1","4k_PlateLink2","4k_PlateFarSide1","4k_PlateFarSide2","15k_CryoCooler","15k_PlateLink","15k_PlateFarSide","15k_Shield","110k_CryoCooler","110k_PlateLink","110k_PlateFarSide","110k_Shield");
    $col_strg = implode(",",$Col_name);
    $q = "SELECT $col_strg
        FROM `CryostatTemps`
        WHERE `fkHeader` = $td_keyID";

    table_header ( 475,$tdh);
    echo "<tr><th>Monitor Point</th>
    <th colspan='2'>Monitor Values (K)</th>";

    $r = @mysql_query($q,$tdh->dbconnection) or die("QUERY FAILED: $q");
    $i=0;
    foreach ($Col_name  as $Col) {
        echo "<tr>
        <td width = '250px'>".$Col."</td>
        <td width = '150px'>".@mysql_result($r,0,$i)."</td></tr>";
        $i++;
    }
    echo "</table></div>";

}


// Y-factor
/**
 * echos a HTML table that contains Y-factor monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
*/
function Y_factor_results($td_keyID){

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID,"40");

    // get specifications array
    $spec=get_specs ( 15 , $tdh->GetValue('Band') );

    $Col_name = array("IFchannel","Phot_dBm","Pcold_dBm","Y","FreqLO" );
    $col_strg = implode(",",$Col_name);
    $q = "SELECT $col_strg
        FROM Yfactor
        WHERE fkHeader= $td_keyID";
    $r = @mysql_query($q,$tdh->dbconnection) or die("QUERY FAILED: $q");

    $FreqLO = @mysql_result($r,0,4);
    mysql_data_seek    ($r,0);

    table_header ( 475,$tdh);
    echo "<tr><th width = '199px'>LO= $FreqLO GHz</th>
        <th width = '92px'>Phot (dBm)</th>
        <th width = '92px'>Pcold (dBm)</th>
        <th width = '92px'>Y-Factor</th><tr>";

    $atten_cnt=0;

    while ($row = @mysql_fetch_array($r)){
        $att_sum = $att_sum + $row[3];
        $atten_cnt++;

        switch ($row[0]) {
            case 0:
                echo "<td>IF0 Pol 0 USB</td>";
                break;

            case 1:
                echo "<td>IF1 Pol 1 USB</td>";
                break;

            case 2:
                echo "<td>IF2 Pol 0 LSB</td>";
                break;

               case 3:
                echo "<td>IF3 Pol 1 LSB</td>";
                break;
        }
        echo "<td>".mon_data($row[1])."</td>
            <td>".mon_data($row[2])."</td>";

        // check to see if Y factor is in spec
        $Y_factor = chk_num_agnst_spec( mon_data($row[3]), ">", $spec[15] );
        echo "<td width = '75px'>$Y_factor</tr> ";
    }
    $avg_atten = mon_data($att_sum /$atten_cnt);
    $avg_atten_text = chk_num_agnst_spec( $avg_atten , ">", $spec[15] );
    echo "<tr><th colspan='3'>Average Y factor </th>
        <th>$avg_atten_text</th>";
    echo "</table></div>";
}


// I-V_Curve
/**
 * echos a HTML table that contains I-V Curve monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
*/
function I_V_Curve_results($td_keyID){
    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID,"40");

    table_header ( 925,$tdh);
    echo "<td><img src= '" . $tdh->GetValue('PlotURL') . "'></td>";
    echo "</table></div>";

}

// Band 3 Noise Temperature Table
/**
 * echos a HTML table that contains Band 3 Noise Temperature monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
*/
function Band3_NT_results($td_keyID){

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID,"40");
    //get specs
    $specs=get_specs_by_spec_type ( 10 , $tdh->GetValue('Band') );

    $col_name = array("FreqLO","Pol0USB","Pol0LSB","Pol1USB","Pol1LSB","AvgNT" );
    $col_strg = implode(",",$col_name);
    $q = "SELECT $col_strg
        FROM `Noise_Temp_Band3_Results`
        WHERE fkHeader= $td_keyID";
    $r = @mysql_query($q,$tdh->dbconnection) or die("QUERY FAILED: $q");

    table_header ( 800,$tdh);

    // display data column header row
    for ($i = 0; $i < 7; $i++) {
        switch ($i){
            case 0;
                //diplay "Ghz" for Frequency
                echo "<th width = '300px'>$col_name[$i] (Ghz)</th>";
                break;
            case 6;
                //diplay "Ghz" for Frequency
                echo "<th width = '300px'>Spec (K)</th>";
                break;
            default;
                //Display "K" for noise temps
                echo "<th width = '300px'>$col_name[$i] (K)</th>";
                break;
        }
    }

    // display data rows
    $cnt = 0;
    while ($row = @mysql_fetch_array($r)){
        $i=0;
        echo "<tr>";
        for ($i = 0; $i < 7; $i++) {
            switch ($i){
                case 0;
                    //Frequency column
                    $freq = @mysql_result($r,$cnt,$i);
                    echo "<td width = '300px'>$freq</td>";
                    break;
                case 5;
                    //average NT column
                    $num = mon_data (@mysql_result($r,$cnt,$i));
                    $text=chk_num_agnst_spec( $num, "<", $specs[$freq]);
                    echo "<td width = '300px'>$text</td>";
                    break;
                case 6;
                    //spec column
                    echo "<td width = '300px'> less than $specs[$freq]</td>";
                    break;
                default;
                    //only display 2 decimals on a float number
                    echo "<td width = '300px'>".mon_data (@mysql_result($r,$cnt,$i))."</td>";
                    break;
            }
        }
        echo "</tr>";
        $cnt++;
    }
    echo "</table></div>";
}

// Band 3 CCA Noise Temperature Table
/**
 * echos a HTML table that contains Band 3 CCA Noise Temperature monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
*/
function Band3_CCA_NT_results($td_keyID){

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID,"40");

    //get specs
    $specs=get_specs_by_spec_type ( 10 , $tdh->GetValue('Band') );

    //Query to get CCA Serial Number
    $q ="SELECT MAX(FE_Components.SN) FROM FE_Components, FE_ConfigLink, FE_Config
         WHERE FE_ConfigLink.fkFE_Config = " .$tdh->GetValue('fkFE_Config'). "
         AND FE_Components.fkFE_ComponentType = 20
         AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
         AND FE_Components.Band = " . $tdh->GetValue('Band') . "
         AND FE_Components.keyFacility =" . $tdh->GetValue('keyFacility') ."
         AND FE_ConfigLink.fkFE_ConfigFacility = FE_Config.keyFacility
         ORDER BY Band ASC";
    //Get CCA FE_Component keyid
    $q ="SELECT keyId FROM FE_Components
            WHERE SN = ($q) AND fkFE_ComponentType = 20
            AND band = " . $tdh->GetValue('Band') . "
            AND keyFacility =" . $tdh->GetValue('keyFacility') ."
            GROUP BY keyId DESC";

        $r = @mysql_query($q,$tdh->dbconnection) or die("QUERY FAILED: $q");;
    while ($row = @mysql_fetch_array($r)){
            $CCA_key[]=$row[0];
        }

    $cnt=0;
    do {    // check all CCA configurations for Noise Temperature data
        //get CCA Test Data key
        $q = "SELECT keyID FROM TestData_header WHERE fkTestData_Type = 42
            AND fkDataStatus = 7 AND fkFE_Components = $CCA_key[$cnt]
            AND keyFacility =" . $tdh->GetValue('keyFacility') ."";
        $r = @mysql_query($q,$tdh->dbconnection);

        $CCA_TD_key = @mysql_result($r,0,0);
        $cnt++;
    } while ($CCA_TD_key === FALSE && $cnt < count($CCA_key));


    $cca_tdh = new TestData_header();
    $cca_tdh->Initialize_TestData_header($CCA_TD_key,"40");

    // get and display table
    $col_name = array("Pol","SB","FreqLO","CenterIF","Treceiver");
    $col_strg = implode(",",$col_name);
    $q = "SELECT $col_strg
        FROM `CCA_TEST_NoiseTemperature`
        WHERE fkHeader= $CCA_TD_key AND `CenterIF` != 0
        ORDER BY `Pol` ASC, `SB` ASC, `FreqLO` ASC, `CenterIF` ASC";

    $r = @mysql_query($q,$tdh->dbconnection) or die("QUERY FAILED: $q");

    // read sort and average Noise Temperature Data
    $last_FREQ_LO = 0;

    $AVG_NT_FREQ_LO = array();

    while ($row = @mysql_fetch_array($r)){
        if ($last_FREQ_LO != $row[2] && $last_FREQ_LO!= 0){
            $index=array_search($last_FREQ_LO,$AVG_NT_FREQ_LO);

            if ($index === FALSE  || $index === NULL){
                $AVG_NT_FREQ_LO[] = $last_FREQ_LO;
            }
            // calculate NT averages for a polarizaion and SB per a given Freq_LO
            // pol 0 SB1
            if ($last_pol == 0 && $last_sb == 1){
                $AVG_NT_Pol0_Sb1[] = array_sum($NT_Pol0_Sb1)/count($NT_Pol0_Sb1);
                unset($NT_Pol0_Sb1);

            // pol 0 SB2
            } else if ($last_pol == 0 && $last_sb == 2){
                $AVG_NT_Pol0_Sb2[] = array_sum($NT_Pol0_Sb2)/count($NT_Pol0_Sb2);
                unset($NT_Pol0_Sb2);

            // pol 1 SB1
            } else if ($last_pol == 1 && $last_sb == 1){
                $AVG_NT_Pol1_Sb1[] = array_sum($NT_Pol1_Sb1)/count($NT_Pol1_Sb1);
                unset($NT_Pol1_Sb1);

            // pol 1 SB2
            } else if ($last_pol == 1 && $last_sb == 2){
                $AVG_NT_Pol1_Sb2[] = array_sum($NT_Pol1_Sb2)/count($NT_Pol1_Sb2);
                unset($NT_Pol1_Sb2);
            }
        }

        //save polarization and sidebands NT into an array
        // pol 0 SB1
        if ($row[0] == 0 && $row[1] == 1){
            $NT_Pol0_Sb1[] = $row[4];

        // pol 0 SB2
        } else if ($row[0] == 0 && $row[1] == 2){
            $NT_Pol0_Sb2[] = $row[4];

        // pol 1 SB1
        } else if ($row[0] == 1 && $row[1] == 1){
            $NT_Pol1_Sb1[] = $row[4];

        // pol 1 SB2
        } else if ($row[0] == 1 && $row[1] == 2){
            $NT_Pol1_Sb2[] = $row[4];
        }


    $last_FREQ_LO = $row[2];
    $last_pol = $row[0];
    $last_sb = $row[1];
    $last_NT = $row[4];
    }

    // calculate last average point
    $AVG_NT_Pol1_Sb2[] = array_sum($NT_Pol1_Sb2)/count($NT_Pol1_Sb2);

    // get TFETMS Average Data
    $q = "SELECT `AvgNT`, `FreqLO`
        FROM `Noise_Temp_Band3_Results`
        WHERE fkHeader= $td_keyID";
    $r = @mysql_query($q,$tdh->dbconnection);

    while ($row = @mysql_fetch_array($r)){
            $TFETMS[$row[1]]=$row[0];
    }

    table_header ( 800,$cca_tdh);
    $col_name = array("FreqLO (Ghz)","Pol0USB (K)","Pol0LSB (K)","Pol1USB (K)","Pol1LSB (K)","AvgNT (K)","Spec (K)","T(FETMS)-\nT(HIA)-\n3Kmirrors (K)");
    // display data column header row
    foreach ($col_name  as $Col) {
                echo "<th width = '300px'>$Col</th>";
    }

    // display data rows
    $cnt = 0;
    $i=0;
    echo "<tr>";
    foreach ($AVG_NT_FREQ_LO as $FREQ_LO){
        //don't format frequency
        echo "<td width = '400px'>$FREQ_LO</td>";

        //only display 2 decimals on a float number
        echo "<td width = '300px'>".mon_data ($AVG_NT_Pol0_Sb1[$cnt])."</td>";
        echo "<td width = '300px'>".mon_data ($AVG_NT_Pol0_Sb2[$cnt])."</td>";
        echo "<td width = '300px'>".mon_data ($AVG_NT_Pol1_Sb1[$cnt])."</td>";
        echo "<td width = '300px'>".mon_data ($AVG_NT_Pol1_Sb2[$cnt])."</td>";
        $AVG =     mon_data (($AVG_NT_Pol0_Sb1[$cnt]+$AVG_NT_Pol0_Sb2[$cnt]+$AVG_NT_Pol1_Sb1[$cnt]+$AVG_NT_Pol1_Sb2[$cnt])/4);
        $spec = $specs[$FREQ_LO] - 3;
        $text=chk_num_agnst_spec( $AVG, "<", $spec);
        echo "<td width = '300px'>$text</td>";
        echo "<td width = '400px'>less than $spec</td>";
        $result = mon_data ($TFETMS[$FREQ_LO] - $AVG - 3);
        echo "<td width = '300px'>$result</td>";
        echo "</tr>";
        $cnt++;
    }
}




?>