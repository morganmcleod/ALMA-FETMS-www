<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_classes . '/class.spec_functions.php');
require_once($site_dbConnect);

function results_section_header($desc) {
    echo "<br><font size='+1' color='#f0fff0' face='sans-serif'><h><b>
        $desc
        </b></h></font>";
}

function table_header($width, &$tdh, $cols = 2, $filterChecked = false, $checkBox = "Select") {
    $table_ver = "1.2.5";
    /*
     * 1.2.5 Read VjSet, IjSet, ImagSet, VdSet, IdSet from CCA_SIS_Bias and CCA_LNA_Bias tables, if available.
     * 1.2.4 Band6 SB2 magnet current is always expected to be zero
     * 1.2.3 Sort each section with newest at top, large font section headers
     * 1.2.2 Fix coloring of Y-factor results.
     * 1.2.1 Include FETMS_Description in table headers.
     * 1.2.0 added CCA SIS Warm Resistance table.
     * 1.1.5 using PAIcheckBox to select TDHs for filtering.
     * 1.1.4 Fix display bugs when using 2 GHz (or other) steps in Band3_NT_results()
     * 1.1.3 Removed Notes from Band3_NT_results()
     * 1.1.2 Don't show query error for CCA NT table when no data is available.
     *       "Include in PAS Report" -> "for PAI"
     * 1.1.1 Fix SIS buggy alignment of PAS monitor data with control data.
     * 1.1.0 Now pulls specifications from new class that pulls from files instead of database.
     * 1.0.6 MM fixed query fetching LNA config data for band 4.
     * 1.0.5 MM fixed error in calculating average attenuation in IF_Power_results()
     */

    $testpage = 'testdata.php';

    $q = "SELECT Description FROM TestData_Types
        WHERE keyId = " . $tdh->fkTestData_Type . "";
    $r = mysqli_query($tdh->dbConnection, $q);
    $test_name = ADAPT_mysqli_result($r, 0, 0);

    // decide if the box is checked:
    $checked = "";
    if ($tdh->UseForPAI) $checked = "checked='checked'";

    // show the table if $filterChecked is false, or if UseForPAI is checked:
    if (!$filterChecked || $checked) {

        $leftCols = $cols - 1;

        // First title block line with check box
        echo "<div style= 'width:" . $width . "px'>";
        echo "<table id='table1'><tr class = 'alt'>";
        echo "<th colspan='$leftCols'>$test_name</th>";

        // call the PAIcheckBox JS function when the checkbox is clicked:
        $keyId = $tdh->keyId;
        $cboxId = "PAI_" . $keyId;

        echo "<th colspan='1' style='text-align:right'>";
        if ($checkBox) {
            echo "<input type='checkbox' name='$cboxId' id='$cboxId' $checked
                onchange=\"PAIcheckBox($keyId, document.getElementById('$cboxId').checked);\"> $checkBox</input>";
        }
        echo "</th></tr>";

        // second title block line
        $fetms = $tdh->GetFetmsDescription(" at: ");
        echo "<tr class = 'alt'><th colspan='100'>Measured" . $fetms . " " . $tdh->TS .
            ", TDH: <a href='$testpage?keyheader=" . $tdh->keyId . "&fc=40' target = 'blank'>" . $tdh->keyId . "</a>
            </th></tr>";

        //third title block line
        // check to see if it was a FE component test or a FE config test
        if ($tdh->fkFE_Config != 0) {
            echo "<tr class = 'alt'><th colspan='100'> FE Config: " . $tdh->fkFE_Config .
                ", Table SWVer: $table_ver, Meas SWVer: " . $tdh->Meas_SWVer . "
                </th></tr>";
        } else {
            echo "<tr class = 'alt'><th colspan='100'> FE Component: " . $tdh->fkFE_Components .
                ", Table SWVer: $table_ver, Meas SWVer: " . $tdh->Meas_SWVer . "
                </th></tr>";
        }
        return true;
    } else {
        return false;
    }
}

function band_results_table($FE_Config, $band, $Data_Status, $TestData_Type, $filterChecked) {

    $dbConnection = site_getDbConnection();
    $q = "SELECT keyId FROM TestData_header
        WHERE fkFE_Config = $FE_Config
        AND fkTestData_Type = $TestData_Type
        AND BAND = $band AND fkDataStatus = $Data_Status
        ORDER BY keyId DESC";
    $r = mysqli_query($dbConnection, $q) or die("QUERY FAILED: $q");

    $cnt = 0;
    while ($row = mysqli_fetch_array($r)) {

        switch ($TestData_Type) {
            case 1:
                LNA_results($row[0], $filterChecked);
                break;
            case 2:
                Temp_Sensor_results($row[0], $filterChecked);
                break;
            case 3:
                SIS_results($row[0], $filterChecked);
                break;
            case 60:
                SIS_Resistance_results($row[0], $filterChecked);
                break;
            case 6:
                IF_Power_results($row[0], $filterChecked);
                break;
            case 12:
                WCA_AMC_results($row[0], $filterChecked);
                break;
            case 13:
                WCA_PA_results($row[0], $filterChecked);
                break;
            case 14:
                WCA_MISC_results($row[0], $filterChecked);
                break;
            case 15:
                Y_factor_results($row[0], $filterChecked);
                break;
            case 39:
                I_V_Curve_results($row[0], $filterChecked);
                break;
        }
    }
}

function results_table($FE_Config, $Data_Status, $TestData_Type, $filterChecked) {
    $dbConnection = site_getDbConnection();
    $q = "SELECT keyId FROM TestData_header
        WHERE fkFE_Config = $FE_Config
        AND fkTestData_Type = $TestData_Type
        AND fkDataStatus = $Data_Status
        ORDER BY keyId DESC";
    $r = mysqli_query($dbConnection, $q) or die("QUERY FAILED: $q");
    while ($row = mysqli_fetch_array($r)) {
        switch ($TestData_Type) {
            case 4:
                Cryo_Temp_results($row[0], $filterChecked);
                break;
            case 5:
                FLOOG_results($row[0], $filterChecked);
                break;
            case 8:
                LPR_results($row[0], $filterChecked);
                break;
            case 9:
                Photomixer_results($row[0], $filterChecked);
                break;
            case 10:
                IF_Switch_Temp_results($row[0], $filterChecked);
                break;
            case 24:
                CPDS_results($row[0], $filterChecked);
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
function mon_data($number) {
    if (is_null($number))
        return "";
    else
        return number_format((float)$number, 2, '.', '');
}

function update_dataset($td_keyID, $data_set_group) {
    $tdh = new TestData_header($td_keyID, "40");
    $tdh->SetValue('DataSetGroup', $data_set_group);
    $tdh->Update();
}


// CPDS monitors
/**
 * echos a HTML table that contains CPDS monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function CPDS_results($td_keyID, $filterChecked) {
    $tdh = new TestData_header($td_keyID, "40");

    $col_name = array("Band", "+6V Voltage", "-6V Voltage", "+15V Voltage", "-15V Voltage", "+24V Voltage", "+8V Voltage", "+6V Current", "-6V Current", "+15V Current", "-15V Current", "+24V Current", "+8V Current");

    if (table_header(900, $tdh, count($col_name), $filterChecked)) {

        $q = "SELECT Band,  P6V_V, N6V_V, P15V_V, N15V_V, P24V_V, P8V_V, P6V_I, N6V_I, P15V_I, N15V_I, P24V_I, P8V_I
                FROM CPDS_monitor
                WHERE fkHeader = $td_keyID
                ORDER BY BAND ASC";
        $r = mysqli_query($tdh->dbConnection, $q) or die("QUERY FAILED: $q");

        // write table subheader
        echo "</tr>";
        foreach ($col_name  as $Col) {
            echo "<th>" . $Col . "</th>";
        }
        echo "</tr>";

        // Write data to table
        while ($row = mysqli_fetch_array($r)) {
            echo "<tr>";
            for ($i = 0; $i < 13; $i++) {
                echo "<td>$row[$i]</td>    ";
            }
            echo "<tr>";
        }
        echo "</table></div>";
    }
}


// LNA - Actual Readings
/**
 * echos a HTML table that contains CCA LNA monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function LNA_results($td_keyID, $filterChecked) {

    $tdh = new TestData_header($td_keyID, "40");

    if (table_header(700, $tdh, 8, $filterChecked)) {

        // get specifications array
        $new_spec = new Specifications();
        $spec = $new_spec->getSpecs('CCA_LNA_bias', 0);

        //get and save Monitor Data
        $q = "SELECT Pol, SB, Stage, FreqLO, VdSet, IdSet, VdRead, IdRead, VgRead
            FROM CCA_LNA_bias
            WHERE fkHeader = $td_keyID ORDER BY Pol ASC, SB ASC, Stage ASC";
        $r = mysqli_query($tdh->dbConnection, $q) or die("QUERY FAILED: $q");

        $FreqLO = 0;
        $Cntrl_FreqLO = 0;
        $output = array();
        while ($row = mysqli_fetch_array($r)) {
            // cache the LO frequency:
            if (!$FreqLO)
                $FreqLO = $row[3];
            // insert the results keyed by Pol, LNA, and Stage:
            $key = 'Pol' . $row[0] . " LNA" . $row[1];
            $stageKey = 'Stage ' . $row[2];
            $stageData = array(
                'VdSet' => mon_data($row[4]),
                'IdSet' => mon_data($row[5]),
                'VdRead' => mon_data($row[6]),
                'IdRead' => mon_data($row[7]),
                'VgRead' => mon_data($row[8])
            );
            if (!isset($output[$key]))
                $output[$key] = array();
            $output[$key][$stageKey] = $stageData;
        }

        // If any rows found, so FreqLO got assigned:
        if ($FreqLO) {
            //get and save Control Data
            $q_CompID = "SELECT MAX(FE_Components.keyId)
                FROM FE_Components JOIN FE_ConfigLink
                ON FE_Components.keyId = FE_ConfigLink.fkFE_Components
                WHERE  FE_ConfigLink.fkFE_Config = {$tdh->fkFE_Config}
                AND fkFE_ComponentType= 20 AND Band = {$tdh->Band}";

            // data queries
            $q = "SELECT Pol, SB, FreqLO, VD1, VD2, VD3, ID1, ID2, ID3
                  FROM CCA_PreampParams
                  WHERE fkComponent=($q_CompID)";

            $ord = " ORDER BY Pol ASC, SB ASC;";

            // default query looks for exact LO match
            $q_default = $q . " AND FreqLO= $FreqLO" . $ord;

            // alternate query matches any LO
            $q_any_lo = $q . $ord;

            // try the exact LO match query:
            $r = mysqli_query($tdh->dbConnection, $q_default) or die("QUERY FAILED: $q_default");

            // if no result, try the any LO query:
            $numRows = mysqli_num_rows($r);
            if (!$numRows)
                $r = mysqli_query($tdh->dbConnection, $q_any_lo) or die("QUERY FAILED: $q_any_lo");

            // Match up control data with monitor data:
            while ($row = mysqli_fetch_array($r)) {
                // cache the LO frequency:
                if (!$Cntrl_FreqLO)
                    $Cntrl_FreqLO = $row[2];
                // insert the results keyed by Pol, LNA, and Stage:
                $key = 'Pol' . $row[0] . " LNA" . $row[1];
                if (isset($output[$key])) {
                    for ($stage = 0; $stage < 3; $stage++) {
                        $stageKey = 'Stage ' . ($stage + 1);
                        if (isset($output[$key][$stageKey])) {
                            $output[$key][$stageKey]['VdSet'] = $row[3 + $stage];
                            $output[$key][$stageKey]['IdSet'] = $row[6 + $stage];
                        }
                    }
                }
            }
        }
        echo "<tr><th colspan='2' rowspan='2'>Device</th>
            <th colspan='2'>Control Values &nbsp; LO=$Cntrl_FreqLO Ghz</th>
            <th colspan='3'>Monitor Values &nbsp; LO=$FreqLO Ghz</th></tr>
            <th>Vd(V)</th>
            <th>Id(mA)</th>
            <th>Vd(V)</th>
            <th>Id(mA)</th>
            <th>Vg(V)</th>";

        if (!count($output)) {
            echo "<tr><td colspan='8'>NO DATA</td></tr>";
        } else {
            foreach ($output as $key => $device) {
                $first = true;
                foreach ($device as $stageKey => $row) {
                    echo "<tr><td width = '100px'>";
                    if ($first) {
                        echo $key;
                        $first = false;
                    }
                    echo "</td>";
                    echo "<td width = '75px'>" . $stageKey . "</td>";
                    echo "<td width = '75px'>" . (isset($row['VdSet']) ? $row['VdSet'] : "") . "</td>";
                    echo "<td width = '75px'>" . (isset($row['IdSet']) ? $row['IdSet'] : "") . "</td>";

                    // check to see if Vd is in spec
                    $mon_Vd = "";
                    if (isset($row['VdRead'])) {
                        $mon_Vd = $new_spec->numWithinPercent($row['VdRead'], $row['VdSet'], $spec['Vd_diff']);                        
                    }
                    echo "<td width = '75px'>$mon_Vd</td>";

                    // check to see if Id is in spec
                    $mon_Id = "";
                    if (isset($row['IdRead'])) {
                        $mon_Id = $new_spec->numWithinPercent($row['IdRead'], $row['IdSet'], $spec['Id_diff']);
                    }
                    echo "<td width = '75px'>$mon_Id</td>";

                    // display Vg:
                    $mon_Vg = "";
                    if (isset($row['VgRead']))
                        $mon_Vg = $row['VgRead'];
                    echo "<td width = '75px'>" . $mon_Vg . "</td></tr>";
                }
            }
        }
        echo "</table></div>";
    }
}


// SIS � Actual Readings
/**
 * echos a HTML table that contains SIS monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function SIS_results($td_keyID, $filterChecked) {

    //get and save Monitor Data
    $tdh = new TestData_header($td_keyID, "40");

    if (table_header(700, $tdh, 8, $filterChecked)) {

        // get specs object for SIS bias:
        $new_spec = new Specifications();
        $spec = $new_spec->getSpecs('CCA_SIS_bias', 0);

        $q = "SELECT Pol, SB, FreqLO, VjSet, IjSet, ImagSet, VjRead, IjRead, VmagRead, ImagRead
            FROM CCA_SIS_bias
            WHERE fkHeader = $td_keyID ORDER BY Pol ASC, SB ASC";
        $r = mysqli_query($tdh->dbConnection, $q) or die("QUERY FAILED: $q");

        $FreqLO = 0;
        $output = array();
        while ($row = mysqli_fetch_array($r)) {
            // cache the LO frequency:
            if (!$FreqLO)
                $FreqLO = $row[2];
            // insert the results keyed by Pol and SIS description:
            $key = 'Pol' . $row[0] . " SIS" . $row[1];
            $output[$key] = array(
                'VjSet' => mon_data($row[3]),
                'IjSet' => mon_data($row[4]),
                // Band6 SB2 magnet current is always expected to be zero:
                'ImagSet' => ($tdh->Band == "6" && $row[1] == "2") ? "0.00" : mon_data($row[5]),
                'VjRead' => mon_data($row[6]),
                'IjRead' => mon_data($row[7]),
                'VmagRead' => mon_data($row[8]),
                'ImagRead' => mon_data($row[9])
            );
        }

        // If any rows found, so FreqLO got assigned:
        if ($FreqLO) {

            //get and save Control Data
            $q_CompID = "SELECT DISTINCT MAX(FE_Components.keyId)
                FROM FE_Components JOIN FE_ConfigLink
                ON FE_Components.keyId = FE_ConfigLink.fkFE_Components
                WHERE  FE_ConfigLink.fkFE_Config =" . $tdh->fkFE_Config . "
                AND fkFE_ComponentType= 20 AND Band =" . $tdh->Band . "";

            $q = "SELECT Pol, SB, FreqLO, VJ, IJ, IMAG FROM CCA_MixerParams WHERE fkComponent = ($q_CompID)";

            $ord = " ORDER BY Pol ASC, SB ASC;";

            $q_default = $q . " AND FreqLO = $FreqLO" . $ord;
            $q_any_lo = $q . $ord;

            // try the exact LO match query:
            $r = mysqli_query($tdh->dbConnection, $q_default) or die("QUERY FAILED: $q_default");

            // if no result, try the any LO query:
            $numRows = mysqli_num_rows($r);
            if (!$numRows)
                $r = mysqli_query($tdh->dbConnection, $q_any_lo) or die("QUERY FAILED: $q_any_lo");

            $Cntrl_FreqLO = 0;
            // Match up control data with monitor data:
            while ($row = mysqli_fetch_array($r)) {
                if (!$Cntrl_FreqLO)
                    $Cntrl_FreqLO = $row[2];
                $key = 'Pol' . $row[0] . " SIS" . $row[1];
                if (isset($output[$key])) {
                    $output[$key]['VjSet'] = mon_data($row[3]);
                    $output[$key]['IjSet'] = mon_data($row[4]);
                    // Band6 SB2 magnet current is always expected to be zero:
                    $output[$key]['ImagSet'] = ($tdh->Band == "6" && $row[1] == "2") ? "0.00" : mon_data($row[5]);
                }
            }
        }

        echo "<tr><th rowspan='2'>Device</th>
            <th colspan='3'>Control Values &nbsp; LO=$Cntrl_FreqLO Ghz</th>
            <th colspan='4'>Monitor Values &nbsp; LO=$FreqLO Ghz</th></tr>
            <th>Bias Voltage (mV)</th>`
            <th>Bias Current (uA)</th>
            <th>Magnet Current (mA)</th>
            <th>Bias Voltage (mV)</th>
            <th>Bias Current (uA)</th>
            <th>Magnet Voltage (V)</th>
            <th>Magnet Current (mA)</th></tr>";

        if (!count($output)) {
            echo "<tr><td colspan='8'>NO DATA</td></tr>";
        } else {
            foreach ($output as $key => $row) {
                $VJ = "";
                if ($row['VjSet'] != "")
                    $VJ = $row['VjSet'];
                
                $IJ = "";
                if ($row['IjSet'] != "")
                    $IJ = $row['IjSet'];
                
                $IMAG = "";
                if ($row['ImagSet'] != "")
                    $IMAG = $row['ImagSet'];
                
                echo "<tr>
                      <td width = '100px'>$key</td>
                      <td width = '75px'>$VJ</td>
                      <td width = '75px'>$IJ</td>
                      <td width = '75px'>$IMAG</td>";

                // check to see if Bias voltage is in spec
                $mon_Bias_V = "";
                if (isset($row['VjRead'])) {
                    $mon_Bias_V = $row['VjRead'];
                    if (isset($row['VJ'])) {
                        $mon_Bias_V = $new_spec->numWithinPercent($mon_Bias_V, $row['VJ'], $spec['bias_V_diff']);
                    }
                }

                // check to see if Bias currrent is in spec
                $mon_Bias_I = "";
                if (isset($row['IjRead'])) {
                    $mon_Bias_I = $row['IjRead'];
                    if (isset($row['IJ'])) {
                        $mon_Bias_I = $new_spec->numWithinPercent($mon_Bias_I, $row['IJ'], $spec['bias_I_diff']);
                    }
                }

                // display magnet voltage:
                $mon_Mag_V = "";
                if (isset($row['VmagRead']))
                    $mon_Mag_V = $row['VmagRead'];

                // check to see if Magnet currrent is in spec
                $mon_Mag_I = "";
                if (isset($row['ImagRead'])) {
                    $mon_Mag_I = $row['ImagRead'];
                    if (isset($row['IMAG'])) {
                        $mon_Mag_I = $new_spec->numWithinPercent($mon_Mag_I, $row['IMAG'], $spec['magI_diff']);
                    }
                }

                echo "<td width = '75px'>$mon_Bias_V</td>
                      <td width = '75px'>$mon_Bias_I</td>
                      <td width = '75px'>$mon_Mag_V</td>
                      <td width = '75px'>$mon_Mag_I</td>
                      </tr>";
            }
        }
        echo "</table></div>";
    }
}

// SIS Warm Resistance
/**
 * echos a HTML table that contains warm SIS resistence
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 * Requires the following database changes:
 *

INSERT INTO TestData_Types (keyId,  TestData_TableName,  Description) VALUES (60, 'CCA_TEST_SISResistance', 'CCA SIS Warm Resistance');

CREATE TABLE CCA_TEST_SISResistance (
	keyId INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	fkHeader INT(10) UNSIGNED NULL DEFAULT NULL,
	TS TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	Pol TINYINT(3) UNSIGNED NULL DEFAULT NULL,
	SB TINYINT(3) UNSIGNED NULL DEFAULT NULL,
	ROhms DOUBLE NULL DEFAULT NULL,
	PRIMARY KEY (keyId),
	INDEX Index 1 (fkHeader)
)
COLLATE='latin1_swedish_ci'
ENGINE=MyISAM;
 *
 */
function SIS_Resistance_results($td_keyID, $filterChecked) {
    $tdh = new TestData_header($td_keyID, "40");

    if (table_header(475, $tdh, 2, $filterChecked)) {

        $new_spec = new Specifications();

        $q = "SELECT Pol, SB, ROhms
        FROM CCA_TEST_SISResistance
        WHERE fkHeader = $td_keyID ORDER BY Pol ASC, SB ASC";
        $r = mysqli_query($tdh->dbConnection, $q) or die("QUERY FAILED: $q");

        echo "<tr><th>Device</th><th colspan='2'>Resistance (Ohms)</th></tr>";

        while ($row = mysqli_fetch_array($r)) {
            $key = 'Pol' . $row[0] . " SIS" . $row[1];
            echo "<tr><td>$key</td><td>" . $row[2] . "</td></tr>";
        }
        echo "</table></div>";
    }
}


// Temperature Sensors � Actual Readings
/**
 * echos a HTML table that contains Temperature Sensor monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function Temp_Sensor_results($td_keyID, $filterChecked) {
    $tdh = new TestData_header($td_keyID, "40");

    if (table_header(475, $tdh, 2, $filterChecked)) {

        $new_spec = new Specifications();
        $band = $tdh->Band;

        echo "<tr><th>Monitor Point</th><th colspan='2'>Monitor Values (K)</th>";
        $col_name = array("4k", "110k", "Pol0_mixer", "Spare", "15k", "Pol1_mixer");
        if ($band == 1) \array_splice($col_name, 0, 1);
        $col_strg = implode(",", $col_name);
        $q = "SELECT $col_strg
            FROM CCA_TempSensors
            WHERE fkHeader= $td_keyID";

        $r = mysqli_query($tdh->dbConnection, $q) or die("QUERY FAILED: $q");
        $i = 0;
        foreach ($col_name  as $Col) {
            echo "<tr>";
            echo "<td width = '100px'>" . $Col . "</td>";
            // check to see if data Status is: Cold PAS, Cold PAI or Health check
            $test_type_array = array("1", "3", "4");
            if (in_array($tdh->fkDataStatus, $test_type_array)) {
                // check to see if line is a 4k stage
                $cold_array = array("4k", "Pol0_mixer", "Pol1_mixer");
                if (in_array($Col, $cold_array)) {
                    $num = ADAPT_mysqli_result($r, 0, $i);
                    // check to see if 4k stange meets spec
                    if ($band == 1) $num = $new_spec->chkNumAgnstSpec($num, "<", 18);
                    else $num = $new_spec->chkNumAgnstSpec($num, "<", 4);
                } else {
                    $num = ADAPT_mysqli_result($r, 0, $i);
                }
            } else {
                $num = ADAPT_mysqli_result($r, 0, $i);
            }
            echo "<td width = '300px'>$num</td></tr>";
            $i++;
        }
        echo "</table></div>";
    }
}


// WCA AMC Monitors
/**
 * echos a HTML table that contains WCA AMC monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function WCA_AMC_results($td_keyID, $filterChecked) {

    $tdh = new TestData_header($td_keyID, "40");

    if (table_header(475, $tdh, 2, $filterChecked)) {

        // get WCA_AMC_bias values
        $col_name = array("VDA", "VDB", "VDE", "IDA", "IDB", "IDE", "VGA", "VGB", "VGE", "MultD", "MultD_Current", "5Vsupply");
        $col_strg = implode(",", $col_name);

        $q = "SELECT $col_strg
            FROM WCA_AMC_bias
            WHERE fkHeader= $td_keyID";

        $r = mysqli_query($tdh->dbConnection, $q) or die("QUERY FAILED: $q");;

        echo "<tr><th>Monitor Point</th><th colspan='2'>Monitor Values</th>";

        $i = 0;
        foreach ($col_name  as $Col) {
            echo "<tr>
            <td width = '100px'>" . $Col . "</td>
            <td width = '300px'>" . ADAPT_mysqli_result($r, 0, $i) . "</td></tr>";
            $i++;
        }
        echo "</table></div>";
    }
}


// WCA PA Monitors
/**
 * echos a HTML table that contains WCA PA monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function WCA_PA_results($td_keyID, $filterChecked) {

    $tdh = new TestData_header($td_keyID, "40");

    if (table_header(475, $tdh, 2, $filterChecked)) {

        // get PA_bias values
        $col_name = array("VDp0", "VDp1", "IDp0", "IDp1", "VGp0", "VGp1", "3Vsupply", "5Vsupply");
        $col_strg = implode(",", $col_name);

        $q = "SELECT $col_strg
            FROM WCA_PA_bias
            WHERE fkHeader= $td_keyID";

        $r = mysqli_query($tdh->dbConnection, $q) or die("QUERY FAILED: $q");

        echo "<tr><th>Monitor Point</th><th colspan='2'>Monitor Values</th>";

        $i = 0;
        foreach ($col_name  as $Col) {
            echo "<tr>
            <td width = '100px'>" . $Col . "</td>
            <td width = '300px'>" . ADAPT_mysqli_result($r, 0, $i) . "</td></tr>";
            $i++;
        }
        echo "</table></div>";
    }
}


// WCA Misc Monitors
/**
 * echos a HTML table that contains WCA Misc monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function WCA_MISC_results($td_keyID, $filterChecked) {

    $tdh = new TestData_header($td_keyID, "40");

    if (table_header(475, $tdh, 2, $filterChecked)) {

        // get Misc_bias values
        $col_name = array("PLLtemp", "YTO_heatercurrent");
        $col_strg = implode(",", $col_name);

        $q = "SELECT $col_strg
            FROM WCA_Misc_bias
            WHERE fkHeader= $td_keyID";

        $r = mysqli_query($tdh->dbConnection, $q) or die("QUERY FAILED: $q");

        echo "<tr><th>Monitor Point</th><th colspan='2'>Monitor Values</th>";

        $i = 0;
        foreach ($col_name  as $Col) {
            echo "<tr>
            <td width = '100px'>" . $Col . "</td>
            <td width = '300px'>" . ADAPT_mysqli_result($r, 0, $i) . "</td></tr>";
            $i++;
        }
        echo "</table></div>";
    }
}

// FLOOG Total Power
/**
 * echos a HTML table that contains FLOOG monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function FLOOG_results($td_keyID, $filterChecked) {

    $tdh = new TestData_header($td_keyID, "40");

    if (table_header(475, $tdh, 2, $filterChecked)) {

        $q = "SELECT Band,  RefTotalPower FROM FLOOGdist
                WHERE fkHeader = $td_keyID";
        $r = mysqli_query($tdh->dbConnection, $q) or die("QUERY FAILED: $q");

        echo "<tr><th></th><th colspan='2'>Reference Total Power (dBm)</th><tr>";

        while ($row = mysqli_fetch_array($r)) {
            echo "<tr>
            <td width = '100px'>Band $row[0] WCA</td>
            <td width = '300px'>$row[1]</td></tr>";
        }
        echo "</table></div>";
    }
}


// Nominal IF power levels
/**
 * echos a HTML table that contains Nominal IF power level monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function IF_Power_results($td_keyID, $filterChecked) {

    $tdh = new TestData_header($td_keyID, "40");
    $band = $tdh->Band;

    if (table_header(475, $tdh, 3, $filterChecked)) {

        $q = "SELECT IFChannel, Power_0dB_gain, Power_15dB_gain
            FROM IFTotalPower
            WHERE fkHeader = $td_keyID";

        $r = mysqli_query($tdh->dbConnection, $q) or die("QUERY FAILED: $q");

        echo "<tr><th>IFChannel</th>
            <th>Power 0dB gain (dBm)</th>
            <th>Power 15dB gain (dBm)</th>";

        $atten_cnt = 0;
        $att_sum = 0;

        $new_spec = new Specifications();

        while ($row = mysqli_fetch_array($r)) {
            if ($band == 1 && ($row[0] == 2 || $row[0] == 3)) continue;
            echo "<tr>";
            $att_sum = $att_sum + abs($row[2] - $row[1]);
            $atten_cnt++;
            // check to see if the numbers meet spec
            $check1 = $new_spec->numInRange(($row[2] - 14), mon_data($row[1]), ($row[2] - 16));
            $check2 = $new_spec->numInRange(($row[1] + 16), mon_data($row[2]), ($row[1] + 14));
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
        $avg_atten = ($atten_cnt) ? mon_data($att_sum / $atten_cnt) : 0;
        //check to see if avgerage attunuation is in range
        $check3 = $new_spec->numInRange(16, $avg_atten, 14);
        echo "<tr><th width = '200px'>Average Attenutation (dB) </th>
            <th width = '200px' colspan='2' align='right'>" . $check3 . "</th>";
        echo "</table></div>";
    }
}


// IF switch temperature sensors
/**
 * echos a HTML table that contains IF switch Temperature sensor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function IF_Switch_Temp_results($td_keyID, $filterChecked) {

    $tdh = new TestData_header($td_keyID, "40");

    if (table_header(475, $tdh, 2, $filterChecked)) {

        $col_name = array("pol0sb1", "pol0sb2", "pol1sb1", "pol1sb2");
        $col_strg = implode(",", $col_name);
        $q = "SELECT $col_strg
            FROM IFSwitchTemps
            WHERE fkHeader= $td_keyID";

        echo "<tr><th></th><th colspan='2'>Monitor Values (K)</th>";

        $r = mysqli_query($tdh->dbConnection, $q) or die("QUERY FAILED: $q");
        $i = 0;
        foreach ($col_name  as $Col) {
            echo "<tr>";
            echo "<td width = '100px'>" . $Col . "</td>";
            echo "<td width = '300px'>" . ADAPT_mysqli_result($r, 0, $i) . "</td></tr>";
            $i++;
        }
        echo "</table></div>";
    }
}


// WCA PA Monitors
/**
 * echos a HTML table that contains WCA PA monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function LPR_results($td_keyID, $filterChecked) {

    $tdh = new TestData_header($td_keyID, "40");

    if (table_header(475, $tdh, 2, $filterChecked)) {

        $col_name = array("Laser Pump Temperature (K)", "Laser Drive Current (mA)", "Laser Photodetector Current (mA)", "Photodetector Current (mA)", "Photodetector Power (mW)", "Modulation Input (V)", "TempSensor0 (K)", "TempSensor1 (K)");
        $q = "SELECT LaserPumpTemp,  LaserDrive,  LaserPhotodetector,  Photodetector_mA,  Photodetector_mW,  ModInput,  TempSensor0,  TempSensor1
            FROM LPR_WarmHealth
            WHERE fkHeader= $td_keyID";

        $r = mysqli_query($tdh->dbConnection, $q) or die("QUERY FAILED: $q");

        echo "<tr><th>Monitor Point</th><th colspan='2'>Monitor Values </th>";

        $i = 0;
        foreach ($col_name  as $Col) {
            echo "<tr>";
            echo "<td width = '250px'>" . $Col . "</td>";
            echo "<td width = '150px'>" . ADAPT_mysqli_result($r, 0, $i) . "</td></tr>";
            $i++;
        }
        echo "</table></div>";
    }
}


// Photomixer Monitor Data
/**
 * echos a HTML table that contains Photomixer monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function Photomixer_results($td_keyID, $filterChecked) {

    $tdh = new TestData_header($td_keyID, "40");

    if (table_header(475, $tdh, 2, $filterChecked)) {

        $q = "SELECT Band, Vpmx,  Ipmx
            FROM Photomixer_WarmHealth
            WHERE fkHeader = $td_keyID";

        $r = mysqli_query($tdh->dbConnection, $q) or die("QUERY FAILED: $q");

        $col_name = array("Photomixer Voltage (V)", "Photomixer Current (mA)");

        echo "<tr><th>Monitor Point</th><th colspan='2'>Monitor Values </th>";

        while ($row = mysqli_fetch_array($r)) {
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
}


// Cryo-cooler Temperatures
/**
 * echos a HTML table that contains Cryo-cooler monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function Cryo_Temp_results($td_keyID, $filterChecked) {

    $tdh = new TestData_header($td_keyID, "40");

    if (table_header(475, $tdh, 2, $filterChecked)) {

        $col_name = array("4k_CryoCooler", "4k_PlateLink1", "4k_PlateLink2", "4k_PlateFarSide1", "4k_PlateFarSide2", "15k_CryoCooler", "15k_PlateLink", "15k_PlateFarSide", "15k_Shield", "110k_CryoCooler", "110k_PlateLink", "110k_PlateFarSide", "110k_Shield");
        $col_strg = implode(",", $col_name);
        $q = "SELECT $col_strg
            FROM CryostatTemps
            WHERE fkHeader = $td_keyID";

        echo "<tr><th>Monitor Point</th><th colspan='2'>Monitor Values (K)</th>";

        $r = mysqli_query($tdh->dbConnection, $q) or die("QUERY FAILED: $q");
        $i = 0;
        foreach ($col_name  as $Col) {
            echo "<tr>
            <td width = '250px'>" . $Col . "</td>
            <td width = '150px'>" . ADAPT_mysqli_result($r, 0, $i) . "</td></tr>";
            $i++;
        }
        echo "</table></div>";
    }
}


// Y-factor
/**
 * echos a HTML table that contains Y-factor monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function Y_factor_results($td_keyID, $filterChecked) {

    $tdh = new TestData_header($td_keyID, "40");
    $band = $tdh->Band;

    if (table_header(475, $tdh, 4, $filterChecked)) {

        // get specifications array
        //$spec=get_specs ( 15 , $tdh->Band );
        $new_spec = new Specifications();
        $spec = $new_spec->getSpecs('Yfactor', $tdh->Band);

        $col_name = array("IFchannel", "Phot_dBm", "Pcold_dBm", "Y", "FreqLO");
        $col_strg = implode(",", $col_name);
        $q = "SELECT $col_strg
            FROM Yfactor
            WHERE fkHeader= $td_keyID";
        $r = mysqli_query($tdh->dbConnection, $q) or die("QUERY FAILED: $q");

        $FreqLO = ADAPT_mysqli_result($r, 0, 4);
        mysqli_data_seek($r, 0);

        echo "<tr><th width = '199px'>LO= $FreqLO GHz</th>
            <th width = '92px'>Phot (dBm)</th>
            <th width = '92px'>Pcold (dBm)</th>
            <th width = '92px'>Y-Factor</th><tr>";

        $Ycnt = 0;
        $Ysum = 0;
        $Ymin = $spec['Ymin'];
        $Ymax = $spec['Ymax'];

        while ($row = mysqli_fetch_array($r)) {
            if ($band == 1 && ($row[0] == 2 || $row[0] == 3)) continue;
            $Ysum += $row[3];
            $Ycnt++;

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
            echo "<td>" . mon_data($row[1]) . "</td>
                <td>" . mon_data($row[2]) . "</td>";

            // check to see if Y factor is in spec
            $Y_factor = $new_spec->chkNumAgnstSpec(mon_data($row[3]), "range", $Ymin, $Ymax);
            echo "<td width = '75px'>$Y_factor</tr> ";
        }
        $Yavg = mon_data($Ysum / $Ycnt);
        $YavgText = $new_spec->chkNumAgnstSpec($Yavg, "range", $Ymin, $Ymax);
        echo "<tr><th colspan='3'>Average Y factor </th><th>$YavgText</th></tr>";
        echo "</table></div>";
    }
}

// I-V_Curve
/**
 * echos a HTML table that contains I-V Curve monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function I_V_Curve_results($td_keyID, $filterChecked) {
    $tdh = new TestData_header($td_keyID, "40");

    if (table_header(800, $tdh, 2, $filterChecked)) {
        global $site_storage;
        if ($tdh->PlotURL) echo "<td colspan='2'><img src= '{$site_storage}{$tdh->PlotURL}'></td>";
        echo "</table></div>";
    }
}

// Band 3 Noise Temperature Table
/**
 * echos a HTML table that contains Band 3 Noise Temperature monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function Band3_NT_results($td_keyID) {

    $tdh = new TestData_header($td_keyID, "40");

    //get specs
    $spec_names = array();
    for ($i = 1; $i < 6; $i++) {
        $spec_names[] = 'Bspec_bbTSSB' . $i . 'f';
        $spec_names[] = 'Bspec_bbTSSB' . $i . 's';
    }
    $new_spec = new Specifications();
    $spec = $new_spec->getSpecs('FEIC_NoiseTemperature', $tdh->Band, $spec_names);
    $specs = array();
    for ($i = 1; $i < 6; $i++) {
        $specs[$spec['Bspec_bbTSSB' . (string)$i . 'f']] = $spec['Bspec_bbTSSB' . (string)$i . 's'];
    }


    $col_name = array("FreqLO", "Pol0USB", "Pol0LSB", "Pol1USB", "Pol1LSB", "AvgNT");
    $col_strg = implode(",", $col_name);
    $q = "SELECT $col_strg
        FROM Noise_Temp_Band3_Results
        WHERE fkHeader= $td_keyID
        ORDER BY FreqLO;";
    $r = mysqli_query($tdh->dbConnection, $q) or die("QUERY FAILED: $q");

    table_header(800, $tdh, 7);

    // display data column header row
    for ($i = 0; $i < 7; $i++) {
        switch ($i) {
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
    $spec = $specs[92];

    while ($row = mysqli_fetch_array($r)) {
        $i = 0;
        echo "<tr>";
        for ($i = 0; $i < 7; $i++) {
            switch ($i) {
                case 0;
                    //Frequency column
                    $freq = ADAPT_mysqli_result($r, $cnt, $i);
                    echo "<td width = '300px'>$freq</td>";
                    break;
                case 5;
                    //average NT column
                    if (isset($specs[$freq]))
                        $spec = $specs[$freq];
                    $num = mon_data(ADAPT_mysqli_result($r, $cnt, $i));
                    $text = $new_spec->chkNumAgnstSpec($num, "<", $spec);
                    echo "<td width = '300px'>$text</td>";
                    break;
                case 6;
                    //spec column
                    echo "<td width = '300px'> less than $spec</td>";
                    break;
                default;
                    //only display 2 decimals on a float number
                    echo "<td width = '300px'>" . mon_data(ADAPT_mysqli_result($r, $cnt, $i)) . "</td>";
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
function Band3_CCA_NT_results($td_keyID) {

    $tdh = new TestData_header($td_keyID, "40");

    //get specs
    $spec_names = array();
    for ($i = 1; $i < 6; $i++) {
        $spec_names[] = 'Bspec_bbTSSB' . (string)$i . 'f';
        $spec_names[] = 'Bspec_bbTSSB' . (string)$i . 's';
    }
    //$specs=get_specs_by_spec_type ( 10 , $tdh->Band );
    $new_spec = new Specifications();
    $spec = $new_spec->getSpecs('FEIC_NoiseTemperature', $tdh->Band, $spec_names);
    $specs = array();
    for ($i = 1; $i < 6; $i++) {
        $specs[$spec['Bspec_bbTSSB' . (string)$i . 'f']] = $spec['Bspec_bbTSSB' . (string)$i . 's'];
    }

    //Query to get CCA Serial Number
    $q = "SELECT MAX(FE_Components.SN) FROM FE_Components, FE_ConfigLink, FE_Config
         WHERE FE_ConfigLink.fkFE_Config = " . $tdh->fkFE_Config . "
         AND FE_Components.fkFE_ComponentType = 20
         AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
         AND FE_Components.Band = " . $tdh->Band . "
         AND FE_Components.keyFacility =" . $tdh->keyFacility . "
         AND FE_ConfigLink.fkFE_ConfigFacility = FE_Config.keyFacility
         ORDER BY Band ASC";

    //Get CCA FE_Component keyid
    $q = "SELECT keyId FROM FE_Components
          WHERE SN = ($q) AND fkFE_ComponentType = 20
          AND band = {$tdh->Band}
          AND keyFacility ={$tdh->keyFacility}
          GROUP BY keyId ORDER BY keyId DESC";

    $r = mysqli_query($tdh->dbConnection, $q) or die("QUERY FAILED: $q");;
    while ($row = mysqli_fetch_array($r)) {
        $CCA_key[] = $row[0];
    }

    $cnt = 0;
    do {
        // check all CCA configurations for Noise Temperature data
        //get CCA Test Data key
        $q = "SELECT keyID FROM TestData_header WHERE fkTestData_Type = 42
            AND fkDataStatus = 7 AND fkFE_Components = $CCA_key[$cnt]
            AND keyFacility =" . $tdh->keyFacility . "";
        $r = mysqli_query($tdh->dbConnection, $q);

        $CCA_TD_key = ADAPT_mysqli_result($r, 0, 0);
        $cnt++;
    } while ($CCA_TD_key === FALSE && $cnt < count($CCA_key));

    $cca_tdh = new TestData_header($CCA_TD_key, "40");

    if ($CCA_TD_key) {
        // get and display table
        $col_name = array("Pol", "SB", "FreqLO", "CenterIF", "Treceiver");
        $col_strg = implode(",", $col_name);
        $q = "SELECT $col_strg
            FROM CCA_TEST_NoiseTemperature
            WHERE fkHeader= $CCA_TD_key AND CenterIF != 0
            ORDER BY Pol ASC, SB ASC, FreqLO ASC, CenterIF ASC";

        $r = mysqli_query($tdh->dbConnection, $q) or die("QUERY FAILED: $q");

        // read sort and average Noise Temperature Data
        $last_FREQ_LO = 0;

        $AVG_NT_FREQ_LO = array();

        while ($row = mysqli_fetch_array($r)) {
            if ($last_FREQ_LO != $row[2] && $last_FREQ_LO != 0) {
                $index = array_search($last_FREQ_LO, $AVG_NT_FREQ_LO);

                if ($index === FALSE  || $index === NULL) {
                    $AVG_NT_FREQ_LO[] = $last_FREQ_LO;
                }
                // calculate NT averages for a polarizaion and SB per a given Freq_LO
                // pol 0 SB1
                if ($last_pol == 0 && $last_sb == 1) {
                    $AVG_NT_Pol0_Sb1[] = array_sum($NT_Pol0_Sb1) / count($NT_Pol0_Sb1);
                    unset($NT_Pol0_Sb1);

                    // pol 0 SB2
                } else if ($last_pol == 0 && $last_sb == 2) {
                    $AVG_NT_Pol0_Sb2[] = array_sum($NT_Pol0_Sb2) / count($NT_Pol0_Sb2);
                    unset($NT_Pol0_Sb2);

                    // pol 1 SB1
                } else if ($last_pol == 1 && $last_sb == 1) {
                    $AVG_NT_Pol1_Sb1[] = array_sum($NT_Pol1_Sb1) / count($NT_Pol1_Sb1);
                    unset($NT_Pol1_Sb1);

                    // pol 1 SB2
                } else if ($last_pol == 1 && $last_sb == 2) {
                    $AVG_NT_Pol1_Sb2[] = array_sum($NT_Pol1_Sb2) / count($NT_Pol1_Sb2);
                    unset($NT_Pol1_Sb2);
                }
            }

            //save polarization and sidebands NT into an array
            // pol 0 SB1
            if ($row[0] == 0 && $row[1] == 1) {
                $NT_Pol0_Sb1[] = $row[4];

                // pol 0 SB2
            } else if ($row[0] == 0 && $row[1] == 2) {
                $NT_Pol0_Sb2[] = $row[4];

                // pol 1 SB1
            } else if ($row[0] == 1 && $row[1] == 1) {
                $NT_Pol1_Sb1[] = $row[4];

                // pol 1 SB2
            } else if ($row[0] == 1 && $row[1] == 2) {
                $NT_Pol1_Sb2[] = $row[4];
            }

            $last_FREQ_LO = $row[2];
            $last_pol = $row[0];
            $last_sb = $row[1];
            $last_NT = $row[4];
        }

        // calculate last average point
        $AVG_NT_Pol1_Sb2[] = array_sum($NT_Pol1_Sb2) / count($NT_Pol1_Sb2);

        // get TFETMS Average Data
        $q = "SELECT AvgNT,  FreqLO
            FROM Noise_Temp_Band3_Results
            WHERE fkHeader= $td_keyID";
        $r = mysqli_query($tdh->dbConnection, $q);
        $TFETMS = array();
        while ($row = mysqli_fetch_array($r)) {
            $TFETMS[$row[1]] = $row[0];
        }

        table_header(800, $cca_tdh, 8, false, false);
        $col_name = array("FreqLO (Ghz)", "Pol0USB (K)", "Pol0LSB (K)", "Pol1USB (K)", "Pol1LSB (K)", "AvgNT (K)", "Spec (K)", "T(FETMS)-\nT(HIA)-\n3Kmirrors (K)");
        // display data column header row
        foreach ($col_name  as $Col) {
            echo "<th width = '300px'>$Col</th>";
        }

        // display data rows
        $cnt = 0;
        $i = 0;
        echo "<tr>";
        foreach ($AVG_NT_FREQ_LO as $FREQ_LO) {
            //don't format frequency
            echo "<td width = '400px'>$FREQ_LO</td>";

            //only display 2 decimals on a float number
            echo "<td width = '300px'>" . mon_data($AVG_NT_Pol0_Sb1[$cnt]) . "</td>";
            echo "<td width = '300px'>" . mon_data($AVG_NT_Pol0_Sb2[$cnt]) . "</td>";
            echo "<td width = '300px'>" . mon_data($AVG_NT_Pol1_Sb1[$cnt]) . "</td>";
            echo "<td width = '300px'>" . mon_data($AVG_NT_Pol1_Sb2[$cnt]) . "</td>";
            $AVG =     mon_data(($AVG_NT_Pol0_Sb1[$cnt] + $AVG_NT_Pol0_Sb2[$cnt] + $AVG_NT_Pol1_Sb1[$cnt] + $AVG_NT_Pol1_Sb2[$cnt]) / 4);
            $temp_spec = $specs[$FREQ_LO] - 3;
            $text = $new_spec->chkNumAgnstSpec($AVG, "<", $temp_spec);
            echo "<td width = '300px'>$text</td>";
            echo "<td width = '400px'>less than $temp_spec</td>";
            if (isset($TFETMS[$FREQ_LO]))
                $result = mon_data($TFETMS[$FREQ_LO] - $AVG - 3);
            else
                $result = "";
            echo "<td width = '300px'>$result</td>";
            echo "</tr>";
            $cnt++;
        }
    }
}
